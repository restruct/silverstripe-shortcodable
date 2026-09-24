<?php

namespace Shortcodable\Tests\Stub;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;

/**
 * A plain (non-DataObject) shortcodable class using every optional hook the README documents:
 * a configured $shortcode, the default parse_shortcode callback, a label and attribute fields.
 *
 * Extensible is what provides hasMethod(), which Shortcodable calls on every registered class.
 */
class SimpleShortcode implements TestOnly
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode = 'sc_simple';

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        ksort($attrs);
        return 'SIMPLE' . json_encode($attrs);
    }

    public function getShortcodeLabel()
    {
        return 'Simple test shortcode';
    }

    public function getShortcodeFields()
    {
        return FieldList::create(TextField::create('colour', 'Colour'));
    }
}
