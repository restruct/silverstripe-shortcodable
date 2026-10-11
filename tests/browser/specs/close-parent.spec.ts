import { test, expect } from './support';

// Issue #8: a shortcode with $shortcode_close_parent must close the element that actually wraps it.
// The content is rendered through the shortcode parser (/admin/scb-reset/render) and the DOM the
// browser builds from that response is compared with the DOM of the expected markup. Wrong
// closing tags do not show as a string difference here but as a different tree: the browser
// repairs `<p></h3>` into a stray empty <h3>, and leaves block output inside an unclosed <p>.
// The exhaustive case table is ShortcodeParserExtensionTest; these are the structural cases.

const B = '<div class="scb-block">BLOCK</div>';

const cases: [string, string, string][] = [
    ['heading and paragraph before (the issue)', '<h3>Address</h3><p>Street 1</p><p>[scb_block]</p>', `<h3>Address</h3><p>Street 1</p>${B}`],
    ['heading before, text around', '<h2>Title</h2><p>Some [scb_block] content</p>', `<h2>Title</h2><p>Some </p>${B}<p> content</p>`],
    ['nested in a div', '<div class="wrap"><p>Some [scb_block] content</p></div>', `<div class="wrap"><p>Some </p>${B}<p> content</p></div>`],
    ['inside strong inside p', '<p>Some <strong>bold [scb_block] text</strong> end</p>', `<p>Some <strong>bold </strong></p>${B}<p><strong> text</strong> end</p>`],
    ['two in one paragraph', '<p>A [scb_block] B [scb_block] C</p>', `<p>A </p>${B}<p> B </p>${B}<p> C</p>`],
    ['hr sibling before', '<hr><p>[scb_block]</p>', `<hr>${B}`],
    ['top level, between paragraphs', '<p>Intro</p>[scb_block]<p>Outro</p>', `<p>Intro</p>${B}<p>Outro</p>`],
    // Issue #9 (b): a container that can hold block content is left whole, on one line or several;
    // only p, h1-h6, pre, address, dt and summary (plus inline ancestors) are closed
    ['list item, multi-line (#9)', '<ul>\n<li>\n[scb_block]\n</li>\n</ul>', `<ul>\n<li>\n${B}\n</li>\n</ul>`],
    ['table cell, one line (#9)', '<table><tr><td>[scb_block]</td></tr></table>', `<table><tr><td>${B}</td></tr></table>`],
    ['div, one line (#9)', '<div>[scb_block]</div>', `<div>${B}</div>`],
    ['strong inside a list item (#9)', '<ul><li><strong>a [scb_block] b</strong></li></ul>', `<ul><li><strong>a </strong>${B}<strong> b</strong></li></ul>`],
    ['pre is closed (#9)', '<pre>line\n[scb_block]\n</pre>', `<pre>line\n</pre>${B}`],
    ['inline shortcode stays inline', '<h3>Year</h3><p>It is [scb_year] now</p>', '<h3>Year</h3><p>It is 2026 now</p>'],
];

test.describe('shortcode_close_parent output structure', () => {
    for (const [label, content, expected] of cases) {
        test(label, async ({ page }) => {
            const response = await page.goto(`/admin/scb-reset/parsed?content=${encodeURIComponent(content)}`);
            expect(response?.status()).toBe(200);
            // Serialise both trees the same way: the live document's body, and the expected markup
            // parsed by the same browser.
            const [actualTree, expectedTree] = await page.evaluate((exp) => {
                const parsed = new DOMParser().parseFromString(`<!DOCTYPE html><body>${exp}</body>`, 'text/html');
                return [document.body.innerHTML, parsed.body.innerHTML];
            }, expected);
            expect(actualTree).toBe(expectedTree);
        });
    }
});
