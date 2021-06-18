<?php

namespace Shortcodable;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Shortcodable
 * Manages shortcodable configuration and register shortcodable objects
 *
 * @author shea@livesource.co.nz
 **/
class Shortcodable
{
    use Configurable;

    /** @Config array editor configs to include Shortcodable on/in */
    private static $htmleditor_names = [];

    /** @Config array facilitates registering shortcodable classes via yml config */
    private static $shortcodable_classes = [];

    public static function register_classes(array $classes)
    {
        if (is_array($classes) && count($classes)) {
            foreach ($classes as $class) {
                self::register_class($class);
            }
        }
    }

    public static function register_class($class)
    {
        if (class_exists($class)) {
            $shortcode = Config::inst()->get($class, 'shortcode') ?: ClassInfo::shortName($class);
            $parseMethodName = Config::inst()->get($class, 'shortcode_callback') ?: 'parse_shortcode';

            if (!singleton($class)->hasMethod($parseMethodName)) {
                user_error("Failed to register \"$class\" with Shortcodable: missing/unconfigured shortcode parser method (looked for: $parseMethodName)", E_USER_ERROR);
            }
//            if (!$shortcode = Config::inst()->get($class, 'shortcode')) {
//                user_error("Registering \"$class\" with short classname as shortcode: missing \$shortcode config value", E_USER_NOTICE);
//            }

            ShortcodeParser::get()->register($shortcode, [singleton($class), $parseMethodName]);
        }
    }

    /**
     * Returns nested arrays with shortcodable tags & info:
     [
       ["sc_class_map"] => [
         ["currentyear"] => "Restruct\Silverstripe\AdminTweaks\Shortcodes\CurrentYearShortcode",
         ["featuredimage"] => "Restruct\Silverstripe\AdminTweaks\Shortcodes\FeaturedImageShortcode",
         ... etc
       ],
       ["sc_label_map"] => [
         ["currentyear"] => "Actueel jaartal",
         ["featuredimage"] => "Pagina afbeelding van huidige pagina invoegen",
         ... etc
       ]
     ]
     *
     * @return array
     */
    public static function shortcode_class_info()
    {
        $classes = [
            'sc_class_map' => [],
            'sc_label_map' => [],
        ];

        $classList = Config::inst()->get(Shortcodable::class, 'shortcodable_classes');
        if (is_array($classList)) {
            foreach ($classList as $class) {
                $classInst = singleton($class);
                $classShortcode = Config::inst()->get($class, 'shortcode') ?: ClassInfo::shortName($class);
                if($classInst->hasMethod('getShortcodeLabel')) {
                    $classDescription = $classInst->getShortcodeLabel();
                } elseif ($classInst->hasMethod('singular_name')) {
                    $classDescription = $classInst->singular_name();
                } else {
                    $classDescription = ClassInfo::shortName($class);
                }

                $classes['sc_class_map'][$classShortcode] = $class;
                $classes['sc_label_map'][$classShortcode] = $classDescription;
            }
        }

        return $classes;
    }

    public static function get_shortcodable_classes_with_placeholders()
    {
        $shortcodableInfo = static::shortcode_class_info();

        $placeholderClasses = [];
        foreach ($shortcodableInfo['sc_class_map'] as $shortcode => $class) {
            if (singleton($class)->hasMethod('getShortcodePlaceHolder')) {
                $placeholderClasses[$shortcode] = $class;
            }
        }

        return $placeholderClasses;
    }

    public static function get_shortcodable_sc_class_map()
    {
        $shortcodableInfo = static::shortcode_class_info();

        return $shortcodableInfo['sc_class_map'];
    }
}
