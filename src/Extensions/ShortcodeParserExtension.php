<?php

namespace Shortcodable\Extensions;

use Shortcodable\Shortcodable;
use SilverStripe\Assets\Shortcodes\ImageShortcodeProvider;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\DebugView;
use SilverStripe\View\Parsers\ShortcodeParser;

class ShortcodeParserExtension
    extends Extension
{
    // Check the shortcode wrapper element and wrap in wrap el if block type
    public function onBeforeParse(&$content)
    {
        // Since we cannot read the 'identifier' (ShortcodeParser::$active_instance), instead we filter on registered
        // shortcodes of the current instance to prevent running this on instances it shouldn't (eg 'regenerator');
        /** @var ShortcodeParser $parser */
        $parser = $this->owner;
        $sc_parser_instance_shortcodes = array_keys($parser->getRegisteredShortcodes());
        $sc_block_wrapped_map = [];
        foreach(Shortcodable::get_shortcodable_sc_class_map() as $sc_tag => $sc_class){
            if (in_array($sc_tag, $sc_parser_instance_shortcodes) && Config::inst()->get($sc_class, 'shortcode_close_parent')) {
                $sc_block_wrapped_map[] = $sc_tag;
            }
        }
        if(!count($sc_block_wrapped_map)) {
            return;
        }
        
        // Assemble regex string: https://regex101.com/r/FriwNk/1
        // ~> appended .{0,10} to also select a part of the string directly following our schortcode tag
        $regex = '<([a-z0-9]+)[^>]*?>.*?(\[(' . implode('|', $sc_block_wrapped_map) . ')[^]]*?\]([^[]*?\[/\3\])*).{0,10}';
        $content = preg_replace_callback(
            '%'.$regex.'%i',
            function ($matches) use ($sc_block_wrapped_map) {
                $full_matched_string = $matches[0];
                $parent_el = $matches[1]; // [1]=>"p" <-- current wrapping element (to close & reopen)
                $full_sc = $matches[2]; // [2]=>"[currentyear]" <-- full shortcode string (incl [sc attr="xy"]wrapped [/sc] verstions
                $sc_tag = $matches[3]; // [3]=> "currentyear" <-- shortcode 'tag'
                
                // eg </p>[currentyear]<p>
                $replacement_html = "</{$parent_el}>{$full_sc}<{$parent_el}>";
                // replace (eg [currentyear] -> </p>[currentyear]<p>)
                $return_str = str_replace($full_sc, $replacement_html, $full_matched_string);

                $replace_items = [
                    // <br></p>[currentyear]<p> -> </p>[currentyear]<p> (removes <br> before)
                    "<br>$replacement_html"                 => $replacement_html,
                    // <p></p>[currentyear] -> [currentyear]
                    "<{$parent_el}></{$parent_el}>$full_sc" => $full_sc,
                    // [currentyear]<p></p> -> [currentyear]
                    "$full_sc<{$parent_el}></{$parent_el}>" => $full_sc,
                ];
                
                return str_replace(array_keys($replace_items), array_values($replace_items), $return_str);
            },
            (string) $content
        );
    }
}
