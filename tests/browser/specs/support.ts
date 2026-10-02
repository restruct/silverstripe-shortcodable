import { test as base, expect, type FrameLocator, type Locator, type Page } from '@playwright/test';

// Shared fixtures and helpers for the shortcodable specs.
//
// The CMS screen is the ordinary page edit form (admin/pages/edit/show/<id>) with its Content
// HTMLEditorField. The fixtures in tests/browser/fixtures/ (copied into the scratch host by the
// runner) add two shortcodes, registered through fixtures/_config/shortcodable.yml:
//   [scb_year]                         plain class, default SVG placeholder;
//   [scb_record id="N" caption="..."]  DataObject with records and a caption field, and its own
//                                      getShortcodePlaceHolder() (a green 300x50 SVG);
// plus /admin/scb-reset to create a page with given Content and read back what was saved.

/**
 * test, extended with an automatic console guard: every spec fails if the page logs a console
 * error or throws an uncaught exception at any point, page load included. "Failed to load
 * resource" (any 4xx/5xx asset or request) arrives as a console error too, so a missing module
 * asset or a broken placeholder URL is caught here as well.
 */
export const test = base.extend<{ consoleGuard: void }>({
    consoleGuard: [
        async ({ page }, use, testInfo) => {
            const errors: string[] = [];
            page.on('console', (msg) => {
                if (msg.type() === 'error') {
                    errors.push(`console.error: ${msg.text()} (${msg.location().url})`);
                }
            });
            page.on('pageerror', (err) => errors.push(`uncaught: ${err.message}`));

            await use();

            if (errors.length) {
                await testInfo.attach('console-errors', { body: errors.join('\n'), contentType: 'text/plain' });
            }
            expect(errors, 'no console errors or uncaught exceptions').toEqual([]);
        },
        { auto: true },
    ],
});

export { expect };

export type Fixture = { id: number; records: Record<string, number> };

/** A fresh draft page with this Content (and the two ScbRecords, created once per host). */
export async function newPage(page: Page, content: string): Promise<Fixture> {
    const response = await page.request.get('/admin/scb-reset/page', { params: { content } });
    expect(response.status(), 'fixture page created').toBe(200);
    return response.json();
}

/** The page's Content as stored in the database (draft). */
export async function savedContent(page: Page, id: number): Promise<string> {
    const response = await page.request.get('/admin/scb-reset/saved', { params: { id: String(id) } });
    expect(response.status(), 'saved content read back').toBe(200);
    return (await response.json()).content;
}

/** Open a page's edit form with a full page load and wait until TinyMCE shows the shortcode button. */
export async function openPage(page: Page, id: number): Promise<void> {
    await page.goto(`/admin/pages/edit/show/${id}`);
    await expect(shortcodeButton(page)).toBeVisible();
}

/** The shortcodable toolbar button of the Content editor (TinyMCE turns the tooltip into aria-label). */
export function shortcodeButton(page: Page): Locator {
    return page.locator('#Form_EditForm_Content_Holder button[aria-label="Insert/edit shortcode"]');
}

/** The Content editor's document (TinyMCE's iframe). */
export function editor(page: Page): FrameLocator {
    return page.frameLocator('#Form_EditForm_Content_ifr');
}

/** All shortcode placeholder images in the editor. */
export function placeholders(page: Page): Locator {
    return editor(page).locator('img.sc-placeholder');
}

/** Wait until an <img> has actually loaded and decoded; returns its intrinsic size. */
export async function loadedSize(img: Locator): Promise<{ w: number; h: number }> {
    await expect
        .poll(() => img.evaluate((el: HTMLImageElement) => el.complete && el.naturalWidth > 0), {
            message: 'placeholder image loaded',
        })
        .toBe(true);
    return img.evaluate((el: HTMLImageElement) => ({ w: el.naturalWidth, h: el.naturalHeight }));
}

/** The shortcode dialog (simpler's Bootstrap modal). */
export function dialog(page: Page): Locator {
    return page.locator('#simplerAdminModal');
}

/**
 * Click the toolbar button and wait for the dialog's form. The form is fetched with a POST to
 * admin/shortcodable, which must be an AJAX request; its body is returned (the shortcode data the
 * dialog was opened with, URL-encoded, or '' for a new shortcode).
 */
export async function openDialog(page: Page): Promise<string> {
    const posted = page.waitForRequest((r) => r.method() === 'POST' && /\/admin\/shortcodable$/.test(r.url()));
    await shortcodeButton(page).click();
    const request = await posted;
    expect(['xhr', 'fetch'], 'the dialog form is fetched with AJAX').toContain(request.resourceType());
    expect((await request.response())?.status(), 'dialog form response status').toBe(200);
    await expect(dialog(page)).toBeVisible();
    await expect(dialog(page).locator('form#Form_ShortcodeForm')).toBeVisible();
    return request.postData() ?? '';
}

/** Pick an option of a chosen.js dropdown in the dialog by its label (the <select> itself is hidden). */
export async function choose(page: Page, field: string, label: string): Promise<void> {
    await dialog(page).locator(`#Form_ShortcodeForm_${field}_chosen`).click();
    await dialog(page).locator(`#Form_ShortcodeForm_${field}_chosen .chosen-results li`, { hasText: label }).click();
}

/** Choose a shortcode type: the dialog re-fetches its form for that type (AJAX POST again). */
export async function chooseType(page: Page, label: string): Promise<void> {
    const posted = page.waitForRequest((r) => r.method() === 'POST' && /\/admin\/shortcodable$/.test(r.url()));
    await choose(page, 'ShortcodeType', label);
    const request = await posted;
    expect(['xhr', 'fetch'], 'the type form is fetched with AJAX').toContain(request.resourceType());
    await expect(dialog(page).locator('#Form_ShortcodeForm_action_insert')).toBeVisible();
}

/**
 * Save the page with the CMS Save button and wait for the AJAX save to answer 200. The short
 * wait first lets the CMS change tracker settle: on Silverstripe 6 a throttled re-scan that runs
 * after the save has replaced the form throws "Cannot read properties of null (reading
 * 'prepValueForChangeTracker')" in core admin code (measured on featuredimages, 2026-10-02).
 */
export async function save(page: Page, id: number): Promise<void> {
    await page.waitForTimeout(300);
    const url = new RegExp(`/admin/pages/edit/EditForm/${id}(\\?|$)`);
    const posted = page.waitForRequest((r) => r.method() === 'POST' && url.test(r.url()));
    await page.locator('#Form_EditForm_action_save').click();
    const request = await posted;
    expect(['xhr', 'fetch'], 'the save is an AJAX request').toContain(request.resourceType());
    expect((await request.response())?.status(), 'save response status').toBe(200);
    await expect(page.locator('#Form_EditForm_action_save')).toHaveText(/Saved/);
}
