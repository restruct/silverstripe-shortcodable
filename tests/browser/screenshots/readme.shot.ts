import { test, expect, type Locator, type Page } from '@playwright/test';
import * as path from 'path';

// Generates the README screenshots in docs/images/. Not a test suite: run it with
// screenshots/playwright.config.ts (see README.md next to this file). The content comes from the
// showcase fixtures (tests/browser/fixtures/ScbShowcase*.php, registered when SCB_README_SHOWCASE
// is set): four shortcodes and a published "Plan your visit" page that uses all of them.

const IMAGES = path.join(__dirname, '..', '..', '..', 'docs', 'images');

// The shortcode button and the dialog, as the browser specs address them (../specs/support.ts).
const BUTTON = '#Form_EditForm_Content_Holder button[aria-label="Insert/edit shortcode"]';
const DIALOG = '#simplerAdminModal';

/** Open the showcase page in the CMS and wait until all four placeholders have loaded. */
async function openShowcasePage(page: Page): Promise<void> {
    await page.goto('/admin/pages');
    await page.locator('.cms-tree a').filter({ hasText: 'Plan your visit' }).first().click();
    await expect(page.locator(BUTTON)).toBeVisible();
    const placeholders = page.frameLocator('#Form_EditForm_Content_ifr').locator('img.sc-placeholder');
    await expect(placeholders).toHaveCount(4);
    await expect
        .poll(() => placeholders.evaluateAll((imgs) => imgs.every((i) => (i as HTMLImageElement).complete && (i as HTMLImageElement).naturalWidth > 0)), {
            message: 'all placeholders loaded',
        })
        .toBe(true);
}

/** A placeholder image in the editor, by its shortcode name. */
function placeholder(page: Page, name: string): Locator {
    return page.frameLocator('#Form_EditForm_Content_ifr').locator(`img.sc-placeholder[title^="${name} "]`);
}

/** Open the dialog with the toolbar button and wait for its form (fetched with AJAX). */
async function openDialog(page: Page): Promise<Locator> {
    await page.locator(BUTTON).click();
    const dialog = page.locator(DIALOG);
    await expect(dialog.locator('form#Form_ShortcodeForm')).toBeVisible();
    // Bootstrap's fade-in.
    await page.waitForTimeout(600);
    return dialog;
}

/** Clip a screenshot to a box with some margin around it, kept inside the viewport. */
function around(box: { x: number; y: number; width: number; height: number }, margin: number, page: Page) {
    const vp = page.viewportSize()!;
    const x = Math.max(0, Math.floor(box.x - margin));
    const y = Math.max(0, Math.floor(box.y - margin));
    return {
        x,
        y,
        width: Math.min(vp.width - x, Math.ceil(box.width + 2 * margin)),
        height: Math.min(vp.height - y, Math.ceil(box.height + 2 * margin)),
    };
}

test.use({ viewport: { width: 1280, height: 900 } });

test('Editor: shortcodes shown as placeholders, the shortcode button in the toolbar', async ({ page }) => {
    await openShowcasePage(page);

    // Point at the button (its hover state marks it in the toolbar).
    await page.locator(BUTTON).hover();
    await page.waitForTimeout(300);

    // From the "Content" label down to the end of the content: the page name and URL segment
    // fields above it add nothing (and the URL segment would show the test host's address).
    const holder = (await page.locator('#Form_EditForm_Content_Holder').boundingBox())!;
    const last = (await placeholder(page, 'contact').boundingBox())!;
    await page.screenshot({
        path: path.join(IMAGES, 'editor-placeholders.png'),
        clip: {
            x: holder.x - 16,
            y: holder.y - 8,
            width: holder.width + 32,
            height: Math.ceil(last.y + last.height + 24 - (holder.y - 8)),
        },
    });
});

test('Dialog: editing a selected shortcode', async ({ page }) => {
    await openShowcasePage(page);

    // Select the button placeholder, then open the dialog: it opens with that shortcode's values.
    await placeholder(page, 'button').click();
    const dialog = await openDialog(page);
    await expect(dialog.locator('input[name="label"]')).toHaveValue('Book a tour');
    await page.mouse.move(5, 895);

    await page.screenshot({
        path: path.join(IMAGES, 'dialog-edit.png'),
        clip: around((await dialog.locator('.modal-content').boundingBox())!, 24, page),
    });
});

test('Dialog: choosing a shortcode type', async ({ page }) => {
    await openShowcasePage(page);

    // Cursor at the end of the first paragraph, nothing selected: a new shortcode.
    const editorBody = page.frameLocator('#Form_EditForm_Content_ifr').locator('body p').first();
    await editorBody.click({ position: { x: 5, y: 5 } });
    await page.keyboard.press('End');
    const dialog = await openDialog(page);
    // The type dropdown is a chosen.js field; open its list.
    await dialog.locator('#Form_ShortcodeForm_ShortcodeType_chosen').click();
    await expect(dialog.locator('#Form_ShortcodeForm_ShortcodeType_chosen .chosen-results li')).toHaveCount(5);
    await page.waitForTimeout(300);

    const content = (await dialog.locator('.modal-content').boundingBox())!;
    const list = (await dialog.locator('#Form_ShortcodeForm_ShortcodeType_chosen .chosen-drop').boundingBox())!;
    const bottom = Math.max(content.y + content.height, list.y + list.height);
    await page.screenshot({
        path: path.join(IMAGES, 'dialog-types.png'),
        clip: around({ x: content.x, y: content.y, width: content.width, height: bottom - content.y }, 24, page),
    });
});
