<?php

namespace Shortcodable\Tests;

use Shortcodable\Shortcodable;
use Shortcodable\Tests\Stub\BlockShortcode;
use Shortcodable\Tests\Stub\SimpleShortcode;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
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
