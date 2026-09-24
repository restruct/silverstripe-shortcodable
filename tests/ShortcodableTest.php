<?php

namespace Shortcodable\Tests;

use Shortcodable\Shortcodable;
use Shortcodable\Tests\Stub\BlockShortcode;
use Shortcodable\Tests\Stub\CustomCallbackShortcode;
use Shortcodable\Tests\Stub\ShortcodableRecord;
use Shortcodable\Tests\Stub\SimpleShortcode;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Behavioural tests for the Shortcodable registry: registration with the shortcode parser, the
 * tag/label maps the CMS dialog is built from, and the TinyMCE wiring done in _config.php.
 *
 * Compatibility note: this suite runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11
 * (Silverstripe 6). Keep it free of doc-comment metadata (@test, @dataProvider), make any data
 * provider static, and avoid assertions removed after PHPUnit 9.
 */
class ShortcodableTest extends SapphireTest
{
    /**
     * Tags registered on the shared default parser by a test; removed again in tearDown() because
     * ShortcodeParser::get() is a process-wide instance that Config restoration does not reset.
     */
    private $registeredTags = [];

    protected function tearDown(): void
    {
        foreach ($this->registeredTags as $tag) {
            ShortcodeParser::get()->unregister($tag);
        }
        $this->registeredTags = [];
        parent::tearDown();
    }

    private function register(string $class, string $expectedTag): void
    {
        $this->registeredTags[] = $expectedTag;
        Shortcodable::register_class($class);
    }

    public function testRegisterClassUsesConfiguredShortcodeAndDefaultParser()
    {
        $this->register(SimpleShortcode::class, 'sc_simple');

        $this->assertArrayHasKey('sc_simple', ShortcodeParser::get()->getRegisteredShortcodes());
        $this->assertSame(
            '<p>SIMPLE{"b":"2","colour":"red"}</p>',
            ShortcodeParser::get()->parse('<p>[sc_simple colour="red" b="2"]</p>')
        );
    }

    public function testRegisterClassFallsBackToShortClassNameAndUsesConfiguredCallback()
    {
        $this->register(CustomCallbackShortcode::class, 'CustomCallbackShortcode');

        $this->assertArrayHasKey('CustomCallbackShortcode', ShortcodeParser::get()->getRegisteredShortcodes());
        // The configured $shortcode_callback, not parse_shortcode, renders it
        $this->assertSame(
            '<p>CUSTOM:CustomCallbackShortcode</p>',
            ShortcodeParser::get()->parse('<p>[CustomCallbackShortcode]</p>')
        );
    }

    public function testRegisterClassIgnoresClassesThatDoNotExist()
    {
        $before = ShortcodeParser::get()->getRegisteredShortcodes();
        Shortcodable::register_class('Shortcodable\\Tests\\Stub\\DoesNotExist');

        $this->assertSame($before, ShortcodeParser::get()->getRegisteredShortcodes());
    }

    public function testRegisterClassesRegistersEachClass()
    {
        $this->registeredTags = ['sc_simple', 'sc_block'];
        Shortcodable::register_classes([SimpleShortcode::class, BlockShortcode::class]);

        $registered = ShortcodeParser::get()->getRegisteredShortcodes();
        $this->assertArrayHasKey('sc_simple', $registered);
        $this->assertArrayHasKey('sc_block', $registered);
    }

    public function testShortcodeClassInfoMapsTagsToClassesAndLabels()
    {
        Config::modify()->set(Shortcodable::class, 'shortcodable_classes', [
            SimpleShortcode::class,
            CustomCallbackShortcode::class,
            ShortcodableRecord::class,
        ]);

        $info = Shortcodable::shortcode_class_info();

        $this->assertSame([
            'sc_simple' => SimpleShortcode::class,
            'CustomCallbackShortcode' => CustomCallbackShortcode::class,
            'sc_record' => ShortcodableRecord::class,
        ], $info['sc_class_map']);

        $this->assertSame([
            // getShortcodeLabel() wins
            'sc_simple' => 'Simple test shortcode',
            // no label method and no singular_name(): short class name
            'CustomCallbackShortcode' => 'CustomCallbackShortcode',
            // DataObject without getShortcodeLabel(): singular_name()
            'sc_record' => 'Test record',
        ], $info['sc_label_map']);
    }

    public function testShortcodeClassInfoIsEmptyWhenNothingIsConfigured()
    {
        Config::modify()->set(Shortcodable::class, 'shortcodable_classes', []);

        $this->assertSame(
            ['sc_class_map' => [], 'sc_label_map' => []],
            Shortcodable::shortcode_class_info()
        );
        $this->assertSame([], Shortcodable::get_shortcodable_sc_class_map());
        $this->assertSame([], Shortcodable::get_shortcodable_classes_with_placeholders());
    }

    public function testEveryShortcodableClassGetsAPlaceholder()
    {
        // Since the default SVG placeholder exists, classes without getShortcodePlaceHolder() count too
        Config::modify()->set(Shortcodable::class, 'shortcodable_classes', [
            SimpleShortcode::class,
            BlockShortcode::class,
        ]);

        $this->assertSame(
            ['sc_simple' => SimpleShortcode::class, 'sc_block' => BlockShortcode::class],
            Shortcodable::get_shortcodable_classes_with_placeholders()
        );
    }

    public function testIsTinymceConfigRejectsAnythingElse()
    {
        $this->assertFalse(Shortcodable::is_tinymce_config(null));
        $this->assertFalse(Shortcodable::is_tinymce_config(new \stdClass()));
    }

    public function testShortcodableButtonIsAddedToTheCmsEditor()
    {
        // _config.php runs at boot against the default `htmleditor_names` config (cms). This asserts
        // the result on whichever TinyMCEConfig class this Silverstripe major provides.
        // Skip on the presence of a TinyMCE class, NOT on is_tinymce_config(): skipping on the method
        // under test would turn a broken detection into a silent skip instead of a failure.
        if (!class_exists('SilverStripe\\TinyMCE\\TinyMCEConfig') && !class_exists('SilverStripe\\Forms\\HTMLEditor\\TinyMCEConfig')) {
            $this->markTestSkipped('No TinyMCE installed (Silverstripe 6 without silverstripe/htmleditor-tinymce)');
        }
        $config = HTMLEditorConfig::get('cms');
        $this->assertTrue(Shortcodable::is_tinymce_config($config), get_class($config) . ' not recognised as TinyMCE');

        $this->assertArrayHasKey('shortcodable', $config->getPlugins());
        $this->assertStringContainsString(
            'client/dist/js/editor_plugin.js',
            (string) $config->getPlugins()['shortcodable']
        );
        // addButtonsToLine() stores the string as given, separator included
        $this->assertContains('| shortcodable', $config->getButtons()[1]);
        $this->assertIsArray($config->getOption('shortcodable_placeholder_codes'));
    }

    public function testEditorCssIsConfiguredOnThisMajorsTinymceConfig()
    {
        // _config/config.yml declares editor_css once per TinyMCEConfig namespace, each behind a
        // classexists guard. Assert the entry landed on the class this major actually provides.
        if (class_exists('SilverStripe\\TinyMCE\\TinyMCEConfig')) {
            $class = 'SilverStripe\\TinyMCE\\TinyMCEConfig';
        } elseif (class_exists('SilverStripe\\Forms\\HTMLEditor\\TinyMCEConfig')) {
            $class = 'SilverStripe\\Forms\\HTMLEditor\\TinyMCEConfig';
        } else {
            $this->markTestSkipped('No TinyMCE installed (Silverstripe 6 without silverstripe/htmleditor-tinymce)');
        }

        $this->assertContains(
            'restruct/silverstripe-shortcodable:client/dist/styles/editor.css',
            (array) Config::inst()->get($class, 'editor_css'),
            "$class editor_css does not include the module's editor.css"
        );
    }

    public function testLeftAndMainLoadsSimplerWithItsModal()
    {
        // The shortcode dialog is simpler's modal, which simpler only loads when its AdminExtension is
        // applied AND simpler_include_modal is set
        $this->assertTrue(
            LeftAndMain::has_extension('Restruct\\Silverstripe\\Simpler\\AdminExtension'),
            'LeftAndMain does not have simpler\'s AdminExtension'
        );
        $this->assertTrue(Config::inst()->get(LeftAndMain::class, 'simpler_include_modal'));
    }
}
