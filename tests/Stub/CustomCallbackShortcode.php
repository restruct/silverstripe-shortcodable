<?php

namespace Shortcodable\Tests\Stub;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;

/**
 * No $shortcode config (so the short class name is the tag), no label (so the short class name is the
 * label), and a custom parser method named through $shortcode_callback instead of parse_shortcode.
 */
class CustomCallbackShortcode implements TestOnly
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode_callback = 'renderCustom';

    public function renderCustom($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        return 'CUSTOM:' . $shortcode;
    }
}
