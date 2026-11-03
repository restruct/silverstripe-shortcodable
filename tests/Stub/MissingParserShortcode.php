<?php

namespace Shortcodable\Tests\Stub;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;

/**
 * A misconfigured shortcodable class: it has no parse_shortcode() and no $shortcode_callback, so
 * registering it must fail loudly. Never put it in a shortcodable_classes list that is registered
 * in setUp(), or every test in that class fails.
 */
class MissingParserShortcode implements TestOnly
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $shortcode = 'sc_missing_parser';
}
