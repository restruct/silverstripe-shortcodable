<?php

namespace Restruct\ScbBrowser;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * BROWSER-TEST FIXTURE ONLY - README screenshot demo: [year], no attributes, the module's default
 * placeholder. Registered only with SCB_README_SHOWCASE set (fixtures/_config/showcase.yml).
 * See ScbYearShortcode for why this never loads in a real install.
 */
class ScbShowcaseYear
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode = 'year';

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        return date('Y');
    }

    public function getShortcodeLabel(): string
    {
        return 'Current year';
    }
}
