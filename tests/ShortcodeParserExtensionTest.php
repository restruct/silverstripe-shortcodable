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
     * Issue #9 (open, waiting on a decision): which elements a block shortcode may close when its
     * parent is a container that can already hold block content (td, li, blockquote, section) or
     * pre. 5.1.0 only looked within one line, so these multi-line forms were left alone; the
     * tag walk now closes/splits the container. No behaviour is asserted here on purpose: the
     * inputs are kept so the #9 decision has its cases ready. Each one is parsed so the rows at
     * least run, and the test is reported as incomplete.
     */
    public function testContainerParentsAwaitIssue9()
    {
        $cases = [
            'table cell, multi-line' => "<table><tr>\n<td>\n[sc_block]\n</td>\n</tr></table>",
            'list item, multi-line' => "<ul>\n<li>\n[sc_block]\n</li>\n</ul>",
            'blockquote, multi-line' => "<blockquote>\n[sc_block]\n</blockquote>",
            'section, multi-line' => "<section>\n[sc_block]\n</section>",
            'pre, multi-line' => "<pre>\nline\n[sc_block]\n</pre>",
            'table cell, one line' => '<table><tr><td>[sc_block]</td></tr></table>',
            'list item, one line' => '<ul><li>One</li><li>[sc_block]</li></ul>',
            'div, one line' => '<div>[sc_block]</div>',
        ];
        $report = [];
        foreach ($cases as $label => $input) {
            $report[] = sprintf("%s\n    input:  %s\n    output: %s", $label, json_encode($input), json_encode(ShortcodeParser::get()->parse($input)));
        }
        $this->markTestIncomplete("Container parents: behaviour to be decided in issue #9, not asserted.\n" . implode("\n", $report));
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
