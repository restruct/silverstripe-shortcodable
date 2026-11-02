import type { Page } from '@playwright/test';
import { test, expect, editor, newPage, openDialog, openPage } from './support';

// Reading the attributes of a shortcode that is selected as plain text when the dialog is opened
// (shortcodable.js getCurrentEditorSelectionAsParsedShortcodeData). The selection is text typed by
// any CMS editor, so parsing it must never create live elements in the CMS document: attributes
// are data, not markup.

/**
 * Select the text of the Content editor's one paragraph (not the paragraph itself), through
 * TinyMCE's own selection API. A keyboard select-all is platform-dependent: Meta+A on macOS selects
 * the text, Control+A on Linux (CI) selects the whole <p>, whose content then does not start with
 * '[' and is never parsed, so a spec relying on it passed without testing anything.
 */
async function selectAll(page: Page): Promise<void> {
    await editor(page).locator('body p').click();
    await page.evaluate(() => {
        const ed = (window as any).tinyMCE.activeEditor;
        const text = ed.getBody().querySelector('p').firstChild;
        const rng = ed.dom.createRng();
        rng.setStart(text, 0);
        rng.setEnd(text, text.length);
        ed.selection.setRng(rng);
    });
}

test.describe('Selected shortcode attributes', () => {
    // Unregistered names typed as text. A data: URL that is not an image makes an <img> fail without
    // any network request, so on a live element the handler would fire within a tick; the second
    // case's src would be fetched from this host. One shortcode per case: the selection is parsed
    // as a single shortcode, so a second one in the same text would only add junk attributes.
    for (const [label, content] of [
        ['a handler attribute never runs', '<p>[img src="data:," onerror="window.__scbProbe = 1"]</p>'],
        ['a src is never fetched', '<p>[img src="/scb-probe-fetch.png"]</p>'],
    ]) {
        test(`are read as data: ${label}`, async ({ page }) => {
            const { id } = await newPage(page, content);
            await openPage(page, id);

            const fetched: string[] = [];
            page.on('request', (r) => {
                if (r.url().includes('scb-probe-fetch')) {
                    fetched.push(r.url());
                }
            });

            await selectAll(page);
            const posted = new URLSearchParams(await openDialog(page));
            // The selection was parsed at all (else the checks below pass without testing anything).
            expect(posted.get('ShortcodeType'), 'the selected shortcode was parsed').toBe('img');
            // Give an error event (and a fetch) time to happen if an element was created.
            await page.waitForTimeout(750);

            expect(await page.evaluate(() => (window as any).__scbProbe), 'the onerror attribute did not run').toBeUndefined();
            expect(fetched, 'the src attribute was not fetched').toEqual([]);
        });
    }

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
