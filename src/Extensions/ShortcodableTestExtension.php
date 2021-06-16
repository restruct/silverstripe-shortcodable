<?php

namespace Shortcodable\Extensions;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;

class ShortcodableTestExtension
extends Extension
{
    private static $shortcode_callback = 'MyCustomShortcodeParser';

    public static function parse_shortcode($attrs, $content, $parser, $shortcode, $info)
    {
        return '<strong>SHORTCODE PARSED: parse_shortcode()</strong>';
    }

    public function MyCustomShortcodeParser($attrs, $content, $parser, $shortcode, $info)
    {
        return '<strong>SHORTCODE PARSED: MyCustomShortcodeParser()</strong>';
    }
}
