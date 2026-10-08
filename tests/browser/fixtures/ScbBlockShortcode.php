<?php

namespace Restruct\ScbBrowser;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * BROWSER-TEST FIXTURE ONLY - a block-level shortcode ($shortcode_close_parent), so a spec can check
 * which element ShortcodeParserExtension closes around its output (issue #8) in the DOM a browser
 * builds from the rendered content. Registered by ScbResetAdmin::parsed() for that request only.
 * See ScbYearShortcode for why this never loads in a real install.
 */
class ScbBlockShortcode
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode = 'scb_block';

    private static $shortcode_close_parent = true;

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        return '<div class="scb-block">BLOCK</div>';
    }

    public function getShortcodeLabel(): string
    {
        return 'Browser block';
    }
}
