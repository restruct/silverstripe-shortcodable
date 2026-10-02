<?php

namespace Restruct\ScbBrowser;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * BROWSER-TEST FIXTURE ONLY - a plain-class shortcode without attributes and without its own
 * placeholder, so the editor shows the module's default SVG placeholder for it.
 *
 * The runner copies tests/browser/fixtures/ into the scratch host's app/; in the module itself it
 * sits behind tests/browser/_manifest_exclude, so no real install ever loads it.
 */
class ScbYearShortcode
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode = 'scb_year';

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        return '2026';
    }

    public function getShortcodeLabel(): string
    {
        return 'Browser year';
    }
}
