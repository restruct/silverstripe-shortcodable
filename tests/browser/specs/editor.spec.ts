import { test, expect, editor, loadedSize, newPage, openPage, placeholders, shortcodeButton } from './support';

// What the editor shows before anyone opens the dialog: the button, the module's assets, and
// shortcodes in the Content replaced by placeholder images (editor_plugin.js fromSrc).

test.describe('Editor integration', () => {
    test('the shortcode button is in the Content toolbar and the editor stylesheet is loaded', async ({ page }) => {
        const { id } = await newPage(page, '<p>Plain text</p>');
        await openPage(page, id);
        // The plugin's own SVG icon, not TinyMCE's "missing icon" fallback.
        await expect(shortcodeButton(page).locator('svg path')).toHaveCount(1);
        // _config/config.yml adds editor.css to TinyMCEConfig.editor_css (a different class per
        // major), which TinyMCE loads into its iframe.
        await expect(editor(page).locator('link[href*="silverstripe-shortcodable/client/dist/styles/editor.css"]')).toHaveCount(1);
    });

    test('an existing shortcode shows as the default SVG placeholder', async ({ page }) => {
        const svg = page.waitForResponse((r) => r.url().includes('/admin/shortcodable/placehold.img'), { timeout: 10_000 });
        const { id } = await newPage(page, '<p>Before [scb_year] after</p>');
        await openPage(page, id);

        const img = placeholders(page);
        await expect(img).toHaveCount(1);
        await expect(img).toHaveAttribute('title', 'scb_year ');
        await expect(img).toHaveAttribute('src', /^admin\/shortcodable\/placeholder\/scb_year\/\?sc=/);
        // The placeholder action redirects to placehold.img, which answers an SVG.
        const response = await svg;
        expect(response.status()).toBe(200);
        expect(response.headers()['content-type']).toContain('image/svg+xml');
        // Default size: 46 px high, 60 + fontsize/1.8 per character of "[scb_year ]" wide.
        expect(await loadedSize(img)).toEqual({ w: 157, h: 46 });
        // The text around it is untouched.
        await expect(editor(page).locator('body p')).toContainText('Before');
        await expect(editor(page).locator('body p')).toContainText('after');
    });

    test("a shortcode class's own getShortcodePlaceHolder() gets the parsed attributes", async ({ page }) => {
        const { records } = await newPage(page, '');
        const alpha = records['Alpha record'];
        const custom = page.waitForResponse((r) => r.url().includes('/admin/shortcodable/placehold.img') && r.url().includes('bg=2e7d32'), { timeout: 10_000 });
        const { id } = await newPage(page, `<p>[scb_record id="${alpha}" caption="Hello"]</p>`);
        await openPage(page, id);

        const img = placeholders(page);
        await expect(img).toHaveCount(1);
        // The record ID travels in the URL path, the full shortcode in ?sc=.
        await expect(img).toHaveAttribute('src', new RegExp(`^admin/shortcodable/placeholder/scb_record/${alpha}\\?sc=`));
        // ScbRecord::getShortcodePlaceHolder() answered (green, 300x50) with the record's title and
        // the caption attribute parsed out of the shortcode.
        const response = await custom;
        expect(new URL(response.url()).searchParams.get('txt')).toBe('Alpha record / Hello');
        expect(await loadedSize(img)).toEqual({ w: 300, h: 50 });
    });
});
