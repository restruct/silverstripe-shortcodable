<?php

namespace Restruct\ScbBrowser;

use Page;
use Shortcodable\Shortcodable;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * BROWSER-TEST FIXTURE ONLY - lets a spec start from a known page and read back what was saved:
 *   GET /admin/scb-reset/page?title=...&content=...  creates a draft Page with that Content and
 *       answers {"id": page ID, "records": {title: ID}} (the two ScbRecords, created once);
 *   GET /admin/scb-reset/saved?id=N   answers {"content": the draft Content as stored};
 *   GET /admin/scb-reset/parsed?content=...  answers an HTML page whose body is that content run
 *       through the shortcode parser, as a template renders HTMLText (for the DOM-structure specs).
 *
 * A LeftAndMain because the admin routes those by url_segment with no YAML; LeftAndMain's own
 * access check applies, so only the logged-in admin can call it. See ScbYearShortcode for why this
 * never loads in a real install.
 */
class ScbResetAdmin extends LeftAndMain
{
    private static $url_segment = 'scb-reset';

    private static $menu_title = 'Shortcodable browser reset';

    private static $allowed_actions = ['page', 'saved', 'parsed'];

    public function page(HTTPRequest $request): HTTPResponse
    {
        $records = [];
        foreach (['Alpha record', 'Bravo record'] as $title) {
            $record = ScbRecord::get()->filter('Title', $title)->first();
            if (!$record) {
                $record = ScbRecord::create(['Title' => $title]);
                $record->write();
            }
            $records[$title] = $record->ID;
        }

        $page = Page::create([
            'Title' => (string) ($request->getVar('title') ?: 'Shortcodable browser page'),
            'Content' => (string) $request->getVar('content'),
        ]);
        $page->write();

        return $this->json(['id' => $page->ID, 'records' => $records]);
    }

    public function saved(HTTPRequest $request): HTTPResponse
    {
        $page = Page::get()->byID((int) $request->getVar('id'));
        if (!$page) {
            return $this->httpError(404, 'no such page');
        }
        return $this->json(['content' => (string) $page->Content]);
    }

    public function parsed(HTTPRequest $request): HTTPResponse
    {
        # The block fixture is registered for this request only, not in fixtures/_config: registered
        # there it would also be listed in the dialog's shortcode type dropdown, which the dialog specs
        # check option by option.
        Config::modify()->merge(Shortcodable::class, 'shortcodable_classes', [ScbBlockShortcode::class]);
        Shortcodable::register_class(ScbBlockShortcode::class);

        $body = ShortcodeParser::get_active()->parse((string) $request->getVar('content'));
        # A bare document: no CMS assets, and an empty data: favicon so the browser's automatic
        # /favicon.ico request cannot log a 404 console error.
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>render</title>'
            . '<link rel="icon" href="data:,"></head><body>' . $body . '</body></html>';
        return HTTPResponse::create($html)->addHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function json(array $data): HTTPResponse
    {
        return HTTPResponse::create(json_encode($data))->addHeader('Content-Type', 'application/json');
    }
}
