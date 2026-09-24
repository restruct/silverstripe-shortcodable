<?php

namespace Shortcodable\Tests;

use Shortcodable\Controllers\ShortcodableAdminController;
use Shortcodable\Shortcodable;
use Shortcodable\Tests\Stub\BlockShortcode;
use Shortcodable\Tests\Stub\CustomCallbackShortcode;
use Shortcodable\Tests\Stub\PlaceholderShortcode;
use Shortcodable\Tests\Stub\ShortcodableRecord;
use Shortcodable\Tests\Stub\SimpleShortcode;
use Shortcodable\Tests\Stub\SizedShortcode;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * The CMS endpoints under admin/shortcodable: the dialog form, the placeholder resolver and the SVG
 * placeholder image - including their CMS permission check.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6). Keep it
 * free of doc-comment metadata and assertions removed after PHPUnit 9.
 */
class ShortcodableAdminControllerTest extends FunctionalTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        ShortcodableRecord::class,
    ];

    private const SHORTCODABLE_CLASSES = [
        'sc_simple' => SimpleShortcode::class,
        'CustomCallbackShortcode' => CustomCallbackShortcode::class,
        'sc_block' => BlockShortcode::class,
        'sc_placeholder' => PlaceholderShortcode::class,
        'sc_sized' => SizedShortcode::class,
        'sc_record' => ShortcodableRecord::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Config::modify()->set(Shortcodable::class, 'shortcodable_classes', array_values(self::SHORTCODABLE_CLASSES));
        // Mirror what _config.php does at boot for YAML-configured classes. It matters: the placeholder
        // endpoint reads attributes through ShortcodeParser::extractTags(), which drops tags that have no
        // handler registered.
        Shortcodable::register_classes(array_values(self::SHORTCODABLE_CLASSES));
    }

    protected function tearDown(): void
    {
        foreach (array_keys(self::SHORTCODABLE_CLASSES) as $tag) {
            ShortcodeParser::get()->unregister($tag);
        }
        parent::tearDown();
    }

    private function logInAsCmsUser(): void
    {
        $this->logInWithPermission('CMS_ACCESS_LeftAndMain');
    }

    /**
     * The SVG action as a client receives it. Up to 5.0.x it echoed the response itself via output()
     * and returned nothing, so the body can arrive either way; capture both so this helper does not
     * depend on which.
     */
    private function fetchPlaceholderImage(array $query): array
    {
        ob_start();
        $response = $this->get('admin/shortcodable/placehold.img?' . http_build_query($query));
        $echoed = ob_get_clean();

        return [$response, (string) $response->getBody() . $echoed];
    }

    public function testEndpointsRequireCmsAccess()
    {
        // Not logged in: neither the dialog nor the placeholder image may be served
        $this->autoFollowRedirection = false;
        foreach (['admin/shortcodable', 'admin/shortcodable/placehold.img?txt=x', 'admin/shortcodable/placeholder/sc_simple'] as $url) {
            ob_start();
            $response = $this->get($url);
            $echoed = ob_get_clean();
            $this->assertNotSame(200, $response->getStatusCode(), "$url answered 200 without login");
            $this->assertStringNotContainsString('<svg', $echoed . $response->getBody(), $url);
        }
    }

    public function testDialogListsEveryShortcodeByLabel()
    {
        $this->logInAsCmsUser();
        $response = $this->post('admin/shortcodable', ['ShortcodeType' => '']);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertStringContainsString('name="ShortcodeType"', $body);
        $this->assertStringContainsString('Simple test shortcode', $body);
        $this->assertStringContainsString('CustomCallbackShortcode', $body);
        $this->assertStringContainsString('Test record', $body);
        // No type chosen yet: nothing to insert
        $this->assertStringNotContainsString('action_insert', $body);
    }

    public function testDialogForAChosenShortcodeShowsItsAttributeFieldsAndInsertAction()
    {
        $this->logInAsCmsUser();
        $response = $this->post('admin/shortcodable', ['ShortcodeType' => 'sc_simple', 'colour' => 'teal']);

        $body = $response->getBody();
        $this->assertStringContainsString('Simple test shortcode', $body);
        // getShortcodeFields() field, loaded from the posted values
        $this->assertMatchesRegularExpression('/<input[^>]+name="colour"[^>]+value="teal"/', $body);
        $this->assertStringContainsString('action_insert', $body);
    }

    public function testDialogForADataObjectShortcodeOffersItsRecords()
    {
        $record = ShortcodableRecord::create(['Title' => 'Pick me']);
        $record->write();

        $this->logInAsCmsUser();
        $response = $this->post('admin/shortcodable', ['ShortcodeType' => 'sc_record']);

        $body = $response->getBody();
        $this->assertStringContainsString('name="id"', $body);
        $this->assertMatchesRegularExpression('/<option[^>]+value="' . $record->ID . '"[^>]*>\s*Pick me/', $body);
    }

    public function testPlaceholderUsesTheClassOwnPlaceholderWithParsedAttributes()
    {
        $this->logInAsCmsUser();
        $response = $this->get('admin/shortcodable/placeholder/sc_placeholder?' . http_build_query([
            'sc' => '[sc_placeholder foo="bar"]',
        ]));

        $this->assertSame('PH:{"foo":"bar"}', $response->getBody());
    }

    public function testPlaceholderDefaultsToAnSvgSizedToTheShortcodeText()
    {
        $this->logInAsCmsUser();
        $this->autoFollowRedirection = false;
        $response = $this->get('admin/shortcodable/placeholder/sc_simple?sc=' . urlencode('[sc_simple]'));

        $this->assertSame(302, $response->getStatusCode());
        $query = $this->redirectQuery($response);
        // 60 + floor(fontsize 16 / 1.8 * strlen('[sc_simple]') = 11)
        $this->assertEquals(60 + floor(16 / 1.8 * 11), $query['w']);
        $this->assertEquals(46, $query['h']);
        $this->assertSame('[sc_simple]', $query['txt']);
    }

    public function testBlockShortcodePlaceholderIsFullSize()
    {
        $this->logInAsCmsUser();
        $this->autoFollowRedirection = false;
        $query = $this->redirectQuery($this->get('admin/shortcodable/placeholder/sc_block?sc=' . urlencode('[sc_block]')));

        $this->assertEquals(1000, $query['w']);
        $this->assertEquals(460, $query['h']);
    }

    public function testPlaceholderSettingsConfigOverridesTheSize()
    {
        $this->logInAsCmsUser();
        $this->autoFollowRedirection = false;
        $query = $this->redirectQuery($this->get('admin/shortcodable/placeholder/sc_sized?sc=' . urlencode('[sc_sized]')));

        $this->assertEquals(321, $query['w']);
        $this->assertEquals(123, $query['h']);
    }

    public function testDefaultPlaceholderConfigChangesTheSize()
    {
        Config::modify()->merge(ShortcodableAdminController::class, 'default_placeholder', [
            'height' => 77,
        ]);
        $this->logInAsCmsUser();
        $this->autoFollowRedirection = false;
        $query = $this->redirectQuery($this->get('admin/shortcodable/placeholder/sc_simple?sc=' . urlencode('[sc_simple]')));

        $this->assertEquals(77, $query['h']);
    }

    public function testPlaceholderForAnUnknownShortcodeIsEmpty()
    {
        $this->logInAsCmsUser();
        $this->autoFollowRedirection = false;
        $response = $this->get('admin/shortcodable/placeholder/not_registered?sc=' . urlencode('[not_registered]'));

        $this->assertNotSame(302, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
    }

    public function testPlaceholderImageRendersTheRequestedSvg()
    {
        $this->logInAsCmsUser();
        [$response, $svg] = $this->fetchPlaceholderImage(['w' => 300, 'h' => 50, 'txt' => '[sc_simple]', 'bg' => 'bada55']);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('width="300"', $svg);
        $this->assertStringContainsString('height="50"', $svg);
        $this->assertStringContainsString('fill="#bada55"', $svg);
        $this->assertStringContainsString('>[sc_simple]</text>', $svg);
    }

    public function testPlaceholderImageWithNonAsciiTextIsWellFormedXml()
    {
        // htmlentities() produced HTML named entities (&eacute;), which XML does not define, so the
        // SVG failed to parse and the editor showed a broken image
        $this->logInAsCmsUser();
        [, $svg] = $this->fetchPlaceholderImage(['txt' => "[caf\u{e9} title=\"\u{fc}ber\"]"]);

        $this->assertNotFalse(@simplexml_load_string($svg), 'placeholder SVG is not well-formed XML');
        $this->assertStringContainsString("caf\u{e9}", $svg);
    }

    public function testPlaceholderImageCapsAndExpandsToTheConfiguredFullSize()
    {
        $this->logInAsCmsUser();
        [, $svg] = $this->fetchPlaceholderImage(['w' => '100%', 'h' => 99999, 'txt' => 'x']);

        $this->assertStringContainsString('width="1000"', $svg);
        $this->assertStringContainsString('height="460"', $svg);
    }

    /**
     * Regression: every request variable of placehold.img was interpolated into the SVG unescaped
     * (only `txt` went through htmlentities, and without ENT_QUOTES on PHP 8.0). The SVG is served
     * same-origin as image/svg+xml, so opening a crafted link ran script in a logged-in CMS session.
     */
    public function testPlaceholderImageEscapesEveryRequestVariable()
    {
        $this->logInAsCmsUser();
        $payload = '"/><script>alert(1)</script><x a="';
        [, $svg] = $this->fetchPlaceholderImage([
            'w' => '1" onload="alert(1)',
            'h' => $payload,
            'bg' => $payload,
            'fg' => $payload,
            'ff' => $payload,
            'txtsize' => $payload,
            'txt' => $payload,
        ]);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringNotContainsString('<script', $svg);
        $this->assertStringNotContainsString('onload=', $svg);
        $this->assertStringNotContainsString('<x ', $svg);
        // Still a well-formed SVG document
        $this->assertNotFalse(@simplexml_load_string($svg), 'placeholder SVG is not well-formed XML');
    }

    public function testPlaceholderImageFallsBackToDefaultsForInvalidColoursAndSizes()
    {
        $this->logInAsCmsUser();
        [, $svg] = $this->fetchPlaceholderImage(['w' => 'wide', 'h' => '-5', 'bg' => 'red;x', 'fg' => 'zzzzzz']);

        $this->assertStringContainsString('width="240"', $svg);
        $this->assertStringContainsString('height="46"', $svg);
        $this->assertStringContainsString('fill="#338dc1"', $svg);
        $this->assertStringContainsString('fill="#ffffff"', $svg);
    }

    private function redirectQuery(HTTPResponse $response): array
    {
        $location = (string) $response->getHeader('Location');
        $this->assertStringContainsString('admin/shortcodable/placehold.img?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return $query;
    }
}
