<?php

namespace Shortcodable\Tests\Stub;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;

/**
 * Provides its own placeholder via getShortcodePlaceHolder(), which the placeholder endpoint must
 * call with the attributes parsed out of the `sc` request variable.
 */
class PlaceholderShortcode implements TestOnly
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode = 'sc_placeholder';

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        return 'PLACEHOLDER';
    }

    public function getShortcodePlaceHolder($attributes)
    {
        return HTTPResponse::create('PH:' . json_encode($attributes));
    }
}
