<?php

namespace Shortcodable\Tests\Stub;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;

/**
 * Overrides the default placeholder size through the per-class `placeholder_settings` config.
 */
class SizedShortcode implements TestOnly
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode = 'sc_sized';

    private static $placeholder_settings = [
        'width' => 321,
        'height' => 123,
    ];

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        return 'SIZED';
    }
}
