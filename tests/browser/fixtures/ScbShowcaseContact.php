<?php

namespace Restruct\ScbBrowser;

use Page;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;

/**
 * BROWSER-TEST FIXTURE ONLY - README screenshot demo: [contact id="N" show="..."], a DataObject
 * shortcode (the dialog lists its records) with its own getShortcodePlaceHolder(), a small card.
 *
 * With SCB_README_SHOWCASE set it also seeds the demo content on every database build: two contacts
 * and a published "Plan your visit" page whose Content uses all four showcase shortcodes.
 * Registered only with that variable set (fixtures/_config/showcase.yml).
 * See ScbYearShortcode for why this never loads in a real install.
 */
class ScbShowcaseContact extends DataObject
{
    private static $table_name = 'ScbBrowser_ShowcaseContact';

    private static $singular_name = 'Contact card';

    private static $db = [
        'Title' => 'Varchar',
        'Phone' => 'Varchar',
    ];

    private static $shortcode = 'contact';

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        $contact = static::get()->byID((int) ($attrs['id'] ?? 0));
        if (!$contact) {
            return '';
        }
        $html = '<strong>' . htmlspecialchars($contact->Title) . '</strong>';
        if (($attrs['show'] ?? 'all') === 'all') {
            $html .= ' ' . htmlspecialchars($contact->Phone);
        }
        return '<span class="contact">' . $html . '</span>';
    }

    public function getShortcodeFields(): FieldList
    {
        return FieldList::create(
            DropdownField::create('show', 'Show', [
                'all' => 'Name and phone number',
                'name' => 'Name only',
            ])
        );
    }

    /**
     * Its own placeholder: a contact card drawn as SVG, with the record's name. Returned as a
     * response, which the placeholder endpoint passes on.
     */
    public function getShortcodePlaceHolder($attributes)
    {
        $name = htmlspecialchars((string) $this->Title, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $detail = ($attributes['show'] ?? 'all') === 'all'
            ? htmlspecialchars((string) $this->Phone, ENT_QUOTES | ENT_XML1, 'UTF-8')
            : 'name only';
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="250" height="56" viewBox="0 0 250 56">
  <rect x="0.5" y="0.5" width="249" height="55" rx="6" fill="#f3f7fa" stroke="#9fb4c7"/>
  <circle cx="28" cy="28" r="15" fill="#338dc1"/>
  <circle cx="28" cy="23" r="5" fill="#fff"/>
  <path d="M19 37a9 7 0 0 1 18 0z" fill="#fff"/>
  <text x="54" y="24" font-family="Helvetica, Arial, sans-serif" font-size="14" font-weight="bold" fill="#1f2b36">{$name}</text>
  <text x="54" y="42" font-family="Helvetica, Arial, sans-serif" font-size="12" fill="#5a6b7b">{$detail}</text>
</svg>
SVG;
        return HTTPResponse::create($svg)->addHeader('Content-Type', 'image/svg+xml');
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        if (!Environment::getEnv('SCB_README_SHOWCASE')) {
            return;
        }

        $ids = [];
        foreach (['Visitor centre' => '555-0142', 'Press office' => '555-0177'] as $title => $phone) {
            $contact = static::get()->filter('Title', $title)->first() ?: static::create(['Title' => $title]);
            $contact->Phone = $phone;
            $contact->write();
            $ids[$title] = $contact->ID;
        }

        $page = Page::get()->filter('URLSegment', 'plan-your-visit')->first()
            ?: Page::create(['Title' => 'Plan your visit', 'URLSegment' => 'plan-your-visit']);
        $page->Content = '<p>We are open every day of the year. Annual passes run until 31 December [year].</p>'
            . '<p>Groups of ten or more can book a guided tour: '
            . '[button label="Book a tour" link="/tours" style="primary"]</p>'
            . '<h2>Stay in touch</h2>'
            . '<p>[signup_form]</p>'
            . '<p>Questions about your visit? Call [contact id="' . $ids['Visitor centre'] . '" show="all"]</p>';
        $page->write();
        $page->publishRecursive();
    }
}
