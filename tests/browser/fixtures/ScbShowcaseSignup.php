<?php

namespace Restruct\ScbBrowser;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * BROWSER-TEST FIXTURE ONLY - README screenshot demo: [signup_form], a block-level shortcode
 * ($shortcode_close_parent) with the default placeholder sized through placeholder_settings.
 * Registered only with SCB_README_SHOWCASE set (fixtures/_config/showcase.yml).
 * See ScbYearShortcode for why this never loads in a real install.
 */
class ScbShowcaseSignup
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode = 'signup_form';

    private static $shortcode_close_parent = true;

    private static $placeholder_settings = [
        'width' => 560,
        'height' => 80,
    ];

    public static function parse_shortcode($attrs, $content = null, $parser = null, $shortcode = null, $info = [])
    {
        return '<form class="signup"><label>Email <input type="email" name="email"></label>'
            . '<button type="submit">Sign up</button></form>';
    }

    public function getShortcodeLabel(): string
    {
        return 'Newsletter sign-up form';
    }
}
