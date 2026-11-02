<?php

namespace Restruct\ScbBrowser;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;

/**
 * BROWSER-TEST FIXTURE ONLY - README screenshot demo: [button label="..." link="..." style="..."],
 * a plain class with attribute fields in the dialog and the module's default placeholder.
 * Registered only with SCB_README_SHOWCASE set (fixtures/_config/showcase.yml).
 * See ScbYearShortcode for why this never loads in a real install.
 */
class ScbShowcaseButton
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode = 'button';

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        return sprintf(
            '<a class="btn btn-%s" href="%s">%s</a>',
            htmlspecialchars($attrs['style'] ?? 'primary'),
            htmlspecialchars($attrs['link'] ?? '#'),
            htmlspecialchars($attrs['label'] ?? '')
        );
    }

    public function getShortcodeLabel(): string
    {
        return 'Call-to-action button';
    }

    public function getShortcodeFields(): FieldList
    {
        return FieldList::create(
            TextField::create('label', 'Button text'),
            TextField::create('link', 'Link'),
            DropdownField::create('style', 'Style', [
                'primary' => 'Primary (filled)',
                'secondary' => 'Secondary (outline)',
            ])
        );
    }
}
