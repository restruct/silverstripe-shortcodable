import { test, expect } from './support';

// The placeholder image endpoint as the browser meets it. Its input sanitising is covered by
// ShortcodableAdminControllerTest; here only what needs a real session: CMS access is required.

test.describe('placehold.img endpoint', () => {
    test('serves an SVG of the requested size to a CMS user', async ({ page }) => {
        const response = await page.request.get('/admin/shortcodable/placehold.img?w=120&h=30&txt=Hi');
        expect(response.status()).toBe(200);
        expect(response.headers()['content-type']).toContain('image/svg+xml');
        const body = await response.text();
        expect(body).toContain('width="120"');
        expect(body).toContain('height="30"');
        expect(body).toContain('>Hi</text>');
    });

    test('is not served to a visitor without CMS access', async ({ browser, baseURL }) => {
        const context = await browser.newContext({ baseURL, storageState: { cookies: [], origins: [] } });
        try {
            const response = await context.request.get('/admin/shortcodable/placehold.img?w=120&h=30&txt=Hi', { maxRedirects: 0 });
            // Silverstripe answers a permission failure with a redirect to the login form.
            expect(response.headers()['content-type'] ?? '').not.toContain('image/svg+xml');
            expect([302, 403]).toContain(response.status());
        } finally {
            await context.close();
        }
    });
});
