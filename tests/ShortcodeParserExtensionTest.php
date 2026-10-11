<?php

namespace Shortcodable\Tests;

use Shortcodable\Shortcodable;
use Shortcodable\Tests\Stub\BlockShortcode;
use Shortcodable\Tests\Stub\SimpleShortcode;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Parsers\HTMLValue;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * ShortcodeParserExtension closes and reopens the wrapping element around shortcodes configured with
 * $shortcode_close_parent, before the parser renders them - and only on parser instances the shortcode
 * is actually registered on.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class ShortcodeParserExtensionTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();
        // The extension reads which tags are block-level from the Shortcodable class map
        Config::modify()->set(Shortcodable::class, 'shortcodable_classes', [
            SimpleShortcode::class,
            BlockShortcode::class,
        ]);
        Shortcodable::register_classes([SimpleShortcode::class, BlockShortcode::class]);
    }

    protected function tearDown(): void
    {
        ShortcodeParser::get()->unregister('sc_simple');
        ShortcodeParser::get()->unregister('sc_block');
        parent::tearDown();
    }

    public function testBlockShortcodeClosesAndReopensItsParentElement()
    {
        $this->assertSame(
            '<p>Some </p><div class="block">B</div><p> content</p>',
            ShortcodeParser::get()->parse('<p>Some [sc_block] content</p>')
        );
    }

    public function testEmptyParentElementsLeftBehindAreRemoved()
    {
        // Shortcode at the very start of the paragraph: no empty <p></p> may be left before it
        $this->assertSame(
            '<div class="block">B</div><p> content</p>',
            ShortcodeParser::get()->parse('<p>[sc_block] content</p>')
        );
    }

    public function testInlineShortcodeStaysInsideItsParentElement()
    {
        $this->assertSame(
            '<p>Some SIMPLE[] content</p>',
            ShortcodeParser::get()->parse('<p>Some [sc_simple] content</p>')
        );
    }

    /**
     * Issue #8: the element closed and reopened must be the shortcode's own wrapping element, whatever
     * precedes or surrounds it. Each case is input => expected output ('B' stands for the block stub's
     * rendered output). All cases run and every mismatch is reported together, so a failure shows the
     * whole table rather than the first broken row (no @dataProvider: see the compatibility note).
     *
     * The comparison is structural: ShortcodeParser::parse() itself loads the content into a DOM
     * (HTMLValue, an HTML5 parser that repairs misnested markup the way a browser does) and returns
     * that DOM serialised, so the expected markup is put through the same parser. A wrong element
     * closed by the extension shows up as a different tree (a stray <h3></h3>, a block left inside
     * a <p>), not as a formatting difference.
     */
    public function testCloseParentClosesTheShortcodesOwnWrappingElement()
    {
        $b = '<div class="block">B</div>';
        $cases = [
            // The case from the issue: an earlier sibling block must not be taken as the parent
            'heading and paragraph before' => [
                '<h3>Address</h3><p>Street 1</p><p>[sc_block]</p>',
                "<h3>Address</h3><p>Street 1</p>$b",
            ],
            'heading before, text around' => [
                '<h2>Title</h2><p>Some [sc_block] content</p>',
                "<h2>Title</h2><p>Some </p>$b<p> content</p>",
            ],
            'sibling after' => [
                '<p>Some [sc_block] content</p><h3>Next</h3>',
                "<p>Some </p>$b<p> content</p><h3>Next</h3>",
            ],
            'siblings on separate lines' => [
                "<h3>A</h3>\n<p>[sc_block]</p>\n<p>after</p>",
                "<h3>A</h3>\n$b\n<p>after</p>",
            ],
            // Only the direct block parent is closed, not an outer container
            'nested in a div' => [
                '<div class="wrap"><p>Some [sc_block] content</p></div>',
                "<div class=\"wrap\"><p>Some </p>$b<p> content</p></div>",
            ],
            'heading as parent' => [
                '<h2>Title [sc_block]</h2>',
                "<h2>Title </h2>$b",
            ],
            // Inline parents are closed up to and including the block element that holds them, and
            // reopened with their own attributes so a link or class carries on after the shortcode
            'inside strong inside p' => [
                '<p>Some <strong>bold [sc_block] text</strong> end</p>',
                "<p>Some <strong>bold </strong></p>$b<p><strong> text</strong> end</p>",
            ],
            'inside a link inside p' => [
                '<p><a href="/x">see [sc_block] here</a></p>',
                "<p><a href=\"/x\">see </a></p>$b<p><a href=\"/x\"> here</a></p>",
            ],
            'only child of strong inside p' => [
                '<p><strong>[sc_block]</strong></p>',
                $b,
            ],
            'first child of a paragraph with attributes' => [
                '<p class="lead" id="intro">[sc_block] after</p>',
                "<p class=\"lead\" id=\"intro\"></p>$b<p> after</p>",
            ],
            // Several block shortcodes in one parent: each one closes the (reopened) parent
            'two in one paragraph' => [
                '<p>A [sc_block] B [sc_block] C</p>',
                "<p>A </p>$b<p> B </p>$b<p> C</p>",
            ],
            'two adjacent' => [
                '<p>[sc_block][sc_block]</p>',
                "$b$b",
            ],
            'inline shortcode before a block one' => [
                '<p>[sc_simple] and [sc_block]</p>',
                '<p>SIMPLE[] and </p>' . $b,
            ],
            // First/last child: no empty parent element may be left on that side
            'first child' => [
                '<p>[sc_block] content</p>',
                "$b<p> content</p>",
            ],
            'last child' => [
                '<p>Some [sc_block]</p>',
                "<p>Some </p>$b",
            ],
            'only child with whitespace around' => [
                "<p>\n[sc_block]\n</p>",
                $b,
            ],
            // Void elements are never taken as the parent; a trailing <br> is dropped as before
            'br directly before' => [
                '<p>Line<br>[sc_block]</p>',
                "<p>Line</p>$b",
            ],
            'self-closed br earlier in the paragraph' => [
                '<p>Line<br />text [sc_block] end</p>',
                "<p>Line<br />text </p>$b<p> end</p>",
            ],
            'img before in the same paragraph' => [
                '<p><img src="/a.png" alt=""> [sc_block] end</p>',
                "<p><img src=\"/a.png\" alt=\"\"> </p>$b<p> end</p>",
            ],
            'hr sibling before' => [
                '<hr><p>[sc_block]</p>',
                "<hr>$b",
            ],
            'comment containing a tag before' => [
                '<!-- <h3> --><p>[sc_block]</p>',
                "<!-- <h3> -->$b",
            ],
            'uppercase tags' => [
                '<P>Some [sc_block] x</P>',
                "<P>Some </P>$b<P> x</P>",
            ],
            'enclosing shortcode with content' => [
                '<p>Before [sc_block]inner[/sc_block] after</p>',
                "<p>Before </p>$b<p> after</p>",
            ],
            // A shortcode with no wrapping element has nothing to close
            'top level, between paragraphs' => [
                '<p>Intro</p>[sc_block]<p>Outro</p>',
                "<p>Intro</p>$b<p>Outro</p>",
            ],
            // ShortcodeParser also accepts a comma between the tag and its attributes
            'comma-separated attributes' => [
                '<p>Some [sc_block,id=1] x</p>',
                "<p>Some </p>$b<p> x</p>",
            ],
            // A custom element's name includes hyphens; custom elements are phrasing content, so
            // they are closed like the inline elements, up to the block element holding them
            'custom element inside p' => [
                '<p><my-el>x [sc_block] y</my-el></p>',
                "<p><my-el>x </my-el></p>$b<p><my-el> y</my-el></p>",
            ],
            // A reopened inline element must not repeat an id (or a link's name anchor)
            'link with id and name inside p' => [
                '<p><a id="top" name="top" href="/x">see [sc_block] here</a></p>',
                "<p><a id=\"top\" name=\"top\" href=\"/x\">see </a></p>$b<p><a href=\"/x\"> here</a></p>",
            ],
            'span with id inside p' => [
                "<p><span class=\"c\" id='s1' data-id=\"1\">a [sc_block] b</span></p>",
                "<p><span class=\"c\" id=\"s1\" data-id=\"1\">a </span></p>$b<p><span class=\"c\" data-id=\"1\"> b</span></p>",
            ],
            // Only real id/name attributes are dropped, not `id=` text inside another attribute's value
            'id= inside a quoted title' => [
                '<p><span title=" id=1" class="c" id="s">x [sc_block] y</span></p>',
                "<p><span title=\" id=1\" class=\"c\" id=\"s\">x </span></p>$b<p><span title=\" id=1\" class=\"c\"> y</span></p>",
            ],
            'id= at the end of a quoted title before href' => [
                '<p><a title="see id=" href="/x" name=top>x [sc_block] y</a></p>',
                "<p><a title=\"see id=\" href=\"/x\" name=\"top\">x </a></p>$b<p><a title=\"see id=\" href=\"/x\"> y</a></p>",
            ],
            // Tags inside a comment are not elements
            'comment containing tags inside the parent' => [
                '<p>a <!-- </p><div> --> [sc_block] b</p>',
                "<p>a <!-- </p><div> --> </p>$b<p> b</p>",
            ],
            // Only closing tags are consumed after the shortcode, never an opening tag
            'opening tag directly after' => [
                '<p>[sc_block]<em>x</em></p>',
                "$b<p><em>x</em></p>",
            ],
            // Element names are compared case-insensitively
            'uppercase inline and block tags' => [
                '<P>x <STRONG>b [sc_block] c</STRONG> y</P>',
                "<p>x <strong>b </strong></p>$b<p><strong> c</strong> y</p>",
            ],
            // A self-closed non-void element (XHTML style) does not open, so it is not the parent
            'self-closed span before' => [
                '<p>a <span /> [sc_block] b</p>',
                "<p>a <span /> </p>$b<p> b</p>",
            ],
            // Without a wrapping element nothing is cleaned up either: the <br> stays
            'top level after a br' => [
                'Line<br>[sc_block]',
                "Line<br>$b",
            ],
            // A stray closing tag (nothing open by that name) leaves the open elements alone
            'stray closing tag inside the parent' => [
                '<p>a </span> [sc_block] b</p>',
                "<p>a </span> </p>$b<p> b</p>",
            ],
            'opening tag of the same element directly after' => [
                '<p><strong>a [sc_block]<strong>x</strong> b</strong></p>',
                "<p><strong>a </strong></p>$b<p><strong><strong>x</strong> b</strong></p>",
            ],
            // A '>' inside a quoted attribute value does not end the tag, double or single quoted
            'link with > in a double-quoted title' => [
                '<p><a title="a>b" href="/x">x [sc_block] y</a></p><p>Next paragraph</p>',
                "<p><a title=\"a>b\" href=\"/x\">x </a></p>$b<p><a title=\"a>b\" href=\"/x\"> y</a></p><p>Next paragraph</p>",
            ],
            'link with > in a single-quoted title' => [
                "<p><a title='a>b' href=\"/x\">x [sc_block] y</a></p><p>Next</p>",
                "<p><a title=\"a>b\" href=\"/x\">x </a></p>$b<p><a title=\"a>b\" href=\"/x\"> y</a></p><p>Next</p>",
            ],
            // A tag that merely starts with the block tag's name is another shortcode
            'other shortcode sharing the prefix' => [
                '<p>Some [sc_block_other] x</p>',
                '<p>Some [sc_block_other] x</p>',
            ],
        ];

        $failures = [];
        foreach ($cases as $label => [$input, $expected]) {
            $actual = ShortcodeParser::get()->parse($input);
            $expected = HTMLValue::create($expected)->getContent();
            if ($actual !== $expected) {
                $failures[] = sprintf("%s\n    input:    %s\n    expected: %s\n    actual:   %s", $label, json_encode($input), json_encode($expected), json_encode($actual));
            }
        }
        $this->assertSame([], $failures, count($failures) . ' of ' . count($cases) . " cases failed:\n" . implode("\n", $failures));
    }

    /**
     * Issue #9, option (b): a block shortcode closes only block elements that cannot hold block
     * content (p, h1-h6, pre, summary) plus inline ancestors. A container that can
     * already hold block content (div, li, td, blockquote, section, ...) is left whole, on one line
     * or several: splitting it would delete or break the list item, table cell or quote around it.
     * Input => expected output, compared structurally as in the #8 table above.
     */
    public function testContainerParentsAreLeftWhole()
    {
        $b = '<div class="block">B</div>';
        $cases = [
            // Containers, multi-line: 5.1.0 left these alone (it only looked within one line)
            'table cell, multi-line' => [
                "<table><tr>\n<td>\n[sc_block]\n</td>\n</tr></table>",
                "<table><tr>\n<td>\n$b\n</td>\n</tr></table>",
            ],
            'list item, multi-line' => [
                "<ul>\n<li>\n[sc_block]\n</li>\n</ul>",
                "<ul>\n<li>\n$b\n</li>\n</ul>",
            ],
            'blockquote, multi-line' => [
                "<blockquote>\n[sc_block]\n</blockquote>",
                "<blockquote>\n$b\n</blockquote>",
            ],
            'section, multi-line' => [
                "<section>\n[sc_block]\n</section>",
                "<section>\n$b\n</section>",
            ],
            // Containers, one line: 5.1.0 closed these (and dropped the emptied element); now left whole
            'table cell, one line' => [
                '<table><tr><td>[sc_block]</td></tr></table>',
                "<table><tr><td>$b</td></tr></table>",
            ],
            'list item, one line' => [
                '<ul><li>One</li><li>[sc_block]</li></ul>',
                "<ul><li>One</li><li>$b</li></ul>",
            ],
            'div, one line' => [
                '<div>[sc_block]</div>',
                "<div>$b</div>",
            ],
            'list item with text around' => [
                '<ul><li>One [sc_block] two</li></ul>',
                "<ul><li>One $b two</li></ul>",
            ],
            'definition description' => [
                '<dl><dt>T</dt><dd>x [sc_block] y</dd></dl>',
                "<dl><dt>T</dt><dd>x $b y</dd></dl>",
            ],
            // address and dt take flow content (WHATWG), so they are containers too
            'address' => [
                '<address>Street [sc_block] 1</address>',
                "<address>Street $b 1</address>",
            ],
            'definition term' => [
                '<dl><dt>Term [sc_block]</dt><dd>x</dd></dl>',
                "<dl><dt>Term $b</dt><dd>x</dd></dl>",
            ],
            // A <br> before the shortcode stays when nothing is closed, as at the top level
            'br before, inside a div' => [
                '<div>Line<br>[sc_block]</div>',
                "<div>Line<br>$b</div>",
            ],
            // Inline ancestors inside a container are still closed and reopened, the container is not
            'strong inside a list item' => [
                '<ul><li><strong>a [sc_block] b</strong></li></ul>',
                "<ul><li><strong>a </strong>$b<strong> b</strong></li></ul>",
            ],
            'only child of a bare em inside a table cell' => [
                '<table><tr><td><em>[sc_block]</em></td></tr></table>',
                "<table><tr><td>$b</td></tr></table>",
            ],
            // An inline element with attributes is closed and kept even if left empty, as in a <p>
            'only child of a link inside a table cell' => [
                '<table><tr><td><a href="/x">[sc_block]</a></td></tr></table>',
                "<table><tr><td><a href=\"/x\"></a>$b</td></tr></table>",
            ],
            // A closable element inside a container: only that element is closed
            'paragraph inside a list item' => [
                '<ul><li><p>x [sc_block] y</p></li></ul>',
                "<ul><li><p>x </p>$b<p> y</p></li></ul>",
            ],
            // The closable elements besides p and h1-h6 (those are in the #8 table): pre and summary
            'pre, multi-line' => [
                "<pre>\nline\n[sc_block]\n</pre>",
                "<pre>\nline\n</pre>$b",
            ],
            'h4' => [
                '<h4>Title [sc_block]</h4>',
                "<h4>Title </h4>$b",
            ],
            'summary' => [
                '<details><summary>S [sc_block]</summary>body</details>',
                "<details><summary>S </summary>{$b}body</details>",
            ],
        ];

        $failures = [];
        foreach ($cases as $label => [$input, $expected]) {
            $actual = ShortcodeParser::get()->parse($input);
            $expected = HTMLValue::create($expected)->getContent();
            if ($actual !== $expected) {
                $failures[] = sprintf("%s\n    input:    %s\n    expected: %s\n    actual:   %s", $label, json_encode($input), json_encode($expected), json_encode($actual));
            }
        }
        $this->assertSame([], $failures, count($failures) . ' of ' . count($cases) . " cases failed:\n" . implode("\n", $failures));
    }

    /**
     * The tag walk must stay linear in the content size. Each input below took tens of seconds to
     * minutes when every shortcode re-scanned or copied the output built so far (or the rest of the
     * content), against milliseconds for the 5.1.0 regex. Only the extension's onBeforeParse hook is
     * run, not the whole parse, so the DOM pass in ShortcodeParser does not dominate the timing. The
     * limit is generous on purpose: it separates linear from quadratic, it does not benchmark.
     */
    public function testCloseParentStaysLinearOnLargeContent()
    {
        $cases = [
            '60000 block shortcodes' => str_repeat('<p>a [sc_block] b</p>', 60000),
            '60000 after a line break' => str_repeat("<p>a<br>\n [sc_block]\n</p>", 60000),
            '20000 unterminated comments' => str_repeat('<!-- x ', 20000) . '<p>[sc_block]</p>',
            '40000 shortcode openings without ]' => str_repeat('<p>[sc_block x</p>', 40000),
            // A stray closing tag (none of its name open) must not scan the open-element stack:
            // this took about 6 s when it did
            '20000 stray closing tags inside 20000 open divs' => str_repeat('<div>', 20000) . str_repeat('</b>', 20000) . '<p>[sc_block]</p>',
            // Past MAX_INLINE_DEPTH nothing is closed or reopened (see testInlineDepthIsCapped)
            '1600 nested spans, 1600 shortcodes' => '<p>' . str_repeat('<span class="s">', 1600) . str_repeat('[sc_block] ', 1600) . '</p>',
        ];
        foreach ($cases as $label => $content) {
            $start = microtime(true);
            ShortcodeParser::get()->extend('onBeforeParse', $content);
            $seconds = microtime(true) - $start;
            $this->assertLessThan(1.5, $seconds, sprintf('%s took %.1f s', $label, $seconds));
        }
    }

    /**
     * Inline ancestors are closed and reopened up to MAX_INLINE_DEPTH (10) levels; deeper than that
     * the shortcode is left in place. Without the cap every shortcode closed and reopened every
     * level, so the output grew with depth x shortcodes (400 levels and 400 shortcodes: 11 KB in,
     * 3.7 MB out). Only onBeforeParse is run, so the raw result is compared.
     */
    public function testInlineDepthIsCapped()
    {
        $nested = function (int $depth, string $inner) {
            return '<p>' . str_repeat('<span class="s">', $depth) . $inner . str_repeat('</span>', $depth) . '</p>';
        };

        // At the cap: the spans and the paragraph are closed before the shortcode
        $content = $nested(10, 'a [sc_block] b');
        ShortcodeParser::get()->extend('onBeforeParse', $content);
        $this->assertStringContainsString(str_repeat('</span>', 10) . '</p>[sc_block]<p>', $content);

        // One level deeper: left as it was
        $input = $nested(11, 'a [sc_block] b');
        $content = $input;
        ShortcodeParser::get()->extend('onBeforeParse', $content);
        $this->assertSame($input, $content);

        // Many levels and many shortcodes: the output stays the size of the input
        $input = '<p>' . str_repeat('<span class="s">', 400) . str_repeat('[sc_block] ', 400) . '</p>';
        $content = $input;
        ShortcodeParser::get()->extend('onBeforeParse', $content);
        $this->assertSame(strlen($input), strlen($content));
    }

    public function testParserInstanceWithoutTheShortcodeRegisteredIsLeftAlone()
    {
        // A separate parser instance (like the 'regenerator' one) that does not know sc_block
        $otherParser = ShortcodeParser::get('shortcodable-test-other');
        $this->assertArrayNotHasKey('sc_block', $otherParser->getRegisteredShortcodes());

        $this->assertSame(
            '<p>Some [sc_block] content</p>',
            $otherParser->parse('<p>Some [sc_block] content</p>')
        );
    }
}
