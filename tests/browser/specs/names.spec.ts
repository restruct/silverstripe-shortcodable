import { test, expect, editor, newPage, openPage, placeholders, save, savedContent } from './support';

// Which shortcode names get a placeholder (shortcodable.js shortcodesToPlaceholders). A name is
// what Silverstripe's ShortcodeParser accepts, \w+ (letters, digits, underscore), and it ends at
// whitespace or the closing bracket. Issue #11: the old pattern stopped a name at its first digit,
// so [scb_year2] was taken for [scb_year] with an attribute "2" and saved back as [scb_year 2].

test.describe('Shortcode names', () => {
    test('[scb_year2] is not mistaken for [scb_year] and is saved unchanged', async ({ page }) => {
        const { id } = await newPage(page, '<p>Text [scb_year2] end</p>');
        await openPage(page, id);
        // Not a registered shortcode: no placeholder.
        await expect(placeholders(page)).toHaveCount(0);

        // Edit, so the editor content is serialised again on save.
        await editor(page).locator('body p').click();
        await page.keyboard.press('End');
        await page.keyboard.type(' x');
        await save(page, id);

        const content = await savedContent(page, id);
        expect(content).toContain('[scb_year2] end x');
        expect(content).not.toContain('[scb_year 2]');
    });

    test('names with digits get a placeholder; a longer name or a hyphen does not match a shorter one', async ({ page }) => {
        const { id } = await newPage(page, '<p>Plain text</p>');
        await openPage(page, id);

        // The conversion itself, with a placeholder list that holds a name with a digit (the editor
        // gets its list from config; a TinyMCE target carries it in data-config).
        const result = await page.evaluate(() => {
            const target = document.createElement('div');
            target.setAttribute('data-config', JSON.stringify({ shortcodable_placeholder_codes: ['h2_title', 'button', 'scb_year'] }));
            const html = (window as any).shortcodable.shortcodesToPlaceholders(
                '<p>[h2_title] [button-large] [scb_year2] [scb_year] [button  id="3" label="Go"]</p>',
                { targetElm: target },
            );
            // Read back in a parsed (inert) document: placeholder images in the live page would be
            // fetched from a URL relative to this one and log a 404.
            const doc = new DOMParser().parseFromString(html, 'text/html');
            return {
                titles: Array.from(doc.querySelectorAll('img.sc-placeholder')).map((img) => img.getAttribute('title')),
                text: doc.body.textContent,
            };
        });

        // No trailing space for a shortcode without attributes; one space before the attributes.
        expect(result.titles).toEqual(['h2_title', 'scb_year', 'button id="3" label="Go"']);
        expect(result.text).toContain('[button-large]');
        expect(result.text).toContain('[scb_year2]');
    });
});
