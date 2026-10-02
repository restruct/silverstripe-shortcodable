<?php

namespace Restruct\ScbBrowser;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

/**
 * BROWSER-TEST FIXTURE ONLY - a DataObject shortcode: the dialog offers its records in an "id"
 * dropdown plus one attribute field (caption), and its placeholder comes from
 * getShortcodePlaceHolder(), which the placeholder endpoint calls with the parsed attributes.
 * See ScbYearShortcode for why this never loads in a real install.
 */
class ScbRecord extends DataObject
{
    private static $table_name = 'ScbBrowser_Record';

    private static $singular_name = 'Browser record';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $shortcode = 'scb_record';

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        $record = static::get()->byID((int) ($attrs['id'] ?? 0));
        return $record ? $record->Title : '';
    }

    public function getShortcodeFields(): FieldList
    {
        return FieldList::create(TextField::create('caption', 'Caption'));
    }

    /**
     * Its own placeholder: the default SVG, but green and with the record title and caption, so a
     * spec can tell that this method (not the default) answered, and with which attributes.
     */
    public function getShortcodePlaceHolder($attributes)
    {
        $controller = \Shortcodable\Controllers\ShortcodableAdminController::singleton();
        $txt = sprintf('%s / %s', $this->Title, $attributes['caption'] ?? '');
        return $controller->redirect($controller->Link('placehold.img') . '?' . http_build_query([
            'w' => 300, 'h' => 50, 'bg' => '2e7d32', 'txt' => $txt,
        ]));
    }
}
