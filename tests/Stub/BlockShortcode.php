<?php

namespace Shortcodable\Tests\Stub;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;

/**
 * A block-level shortcode: $shortcode_close_parent makes ShortcodeParserExtension close the wrapping
 * element before the shortcode and reopen it after, and gives it the full-size default placeholder.
 */
class BlockShortcode implements TestOnly
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode = 'sc_block';

    private static $shortcode_close_parent = true;

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        return '<div class="block">B</div>';
    }
}
