import type { Page } from '@playwright/test';
import { test, expect, editor, newPage, openDialog, openPage } from './support';

// Reading the attributes of a shortcode that is selected as plain text when the dialog is opened
// (shortcodable.js getCurrentEditorSelectionAsParsedShortcodeData). The selection is text typed by
// any CMS editor, so parsing it must never create live elements in the CMS document: attributes
// are data, not markup.

/** Select everything in the Content editor (the one paragraph the fixture page holds). */
async function selectAll(page: Page): Promise<void> {
    await editor(page).locator('body p').click();
    await page.keyboard.press('ControlOrMeta+a');
}

test.describe('Selected shortcode attributes', () => {
    test('are read as data: handler attributes never run and nothing is fetched', async ({ page }) => {
        // An unregistered name with an event-handler attribute and a src, typed as text. A data: URL
        // that is not an image makes an <img> fail without any network request, so on a live element
        // the handler would fire within a tick; the second src would be fetched from this host.
        const { id } = await newPage(
            page,
            '<p>[img src="data:," onerror="window.__scbProbe = 1"] [img src="/scb-probe-fetch.png"]</p>',
        );
        await openPage(page, id);

        const fetched: string[] = [];
        page.on('request', (r) => {
            if (r.url().includes('scb-probe-fetch')) {
                fetched.push(r.url());
            }
        });

        await selectAll(page);
        await openDialog(page);
        // Give an error event (and a fetch) time to happen if an element was created.
        await page.waitForTimeout(750);

        expect(await page.evaluate(() => (window as any).__scbProbe), 'the onerror attribute did not run').toBeUndefined();
        expect(fetched, 'the src attribute was not fetched').toEqual([]);
    });

    test('quoted, unquoted, entity and non-ASCII values are parsed as before', async ({ page }) => {
        const { id } = await newPage(
            page,
            `<p>[scb_plain title="Two words" single='it is' bare=plain amp="a &amp; b &lt;c&gt;" Mixed="Case" accent="café ü"]</p>`,
        );
        await openPage(page, id);

        await selectAll(page);
        const posted = new URLSearchParams(await openDialog(page));
        // Attribute names come out lower-cased, as the HTML attribute parser has always returned them.
        expect(Object.fromEntries(posted)).toEqual({
            ShortcodeType: 'scb_plain',
            title: 'Two words',
            single: 'it is',
            bare: 'plain',
            amp: 'a & b <c>',
            mixed: 'Case',
            accent: 'café ü',
        });
    });
});
