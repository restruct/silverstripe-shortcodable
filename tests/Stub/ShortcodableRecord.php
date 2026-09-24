<?php

namespace Shortcodable\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * A DataObject registered as shortcodable: the dialog must offer its records in an `id` dropdown,
 * and its label falls back to singular_name().
 */
class ShortcodableRecord extends DataObject implements TestOnly
{
    private static $table_name = 'ShortcodableTestRecord';

    private static $singular_name = 'Test record';

    private static $shortcode = 'sc_record';

    private static $db = [
        'Title' => 'Varchar',
    ];

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        return 'RECORD';
    }
}
