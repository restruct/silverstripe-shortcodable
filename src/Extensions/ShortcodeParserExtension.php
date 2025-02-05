<?php

namespace Shortcodable\Extensions;

use Shortcodable\Shortcodable;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\View\Parsers\ShortcodeParser;

class ShortcodeParserExtension
    extends Extension
{
    // Check the shortcode wrapper element and wrap in wrap el if block type
    public function onBeforeParse(&$content)
    {
        /** @var ShortcodeParser $parser */
        $parser = $this->owner;
        $sc_block_wrapped_map = [];
        foreach(Shortcodable::get_shortcodable_sc_class_map() as $sc_tag => $sc_class){
            if (Config::inst()->get($sc_class, 'shortcode_close_parent')) {
                $sc_block_wrapped_map[] = $sc_tag;
            }
        }
        if(!count($sc_block_wrapped_map)) return;

        // assemble regex string and replace: https://regex101.com/r/FriwNk/1
        // (appended .{0,10} to also select a part of the string directly following our schortcode tag)
        $regex = '<([a-z0-9]+)[^>]*?>.*?(\[(' . implode('|', $sc_block_wrapped_map) . ')[^]]*?\]([^[]*?\[/\3\])*).{0,10}';
        $content = preg_replace_callback(
            '%'.$regex.'%i',
            function ($matches) use ($sc_block_wrapped_map) {
                $full_matched_string = $matches[0];
//                [1]=>"p" <-- current wrapping element (to close & reopen)
                $match_parent_block_el = $matches[1];
//                [2]=>"[currentyear]" <-- full shortcode string (incl [sc attr="xy"]wrapped [/sc] verstions
                $match_full_sc_string = $matches[2];
//                [3]=> "currentyear" <-- shortcode 'tag'
                $match_sc_tag = $matches[3];

                // replace and 'clean up' - fix/remove '<br></p>' & <p></p> situations
                $parent_closed_reopened_replacement = "</{$match_parent_block_el}>{$match_full_sc_string}<{$match_parent_block_el}>";
                $return_str = str_replace($matches[2], $parent_closed_reopened_replacement, $full_matched_string);
                $return_str = str_replace("<br>$parent_closed_reopened_replacement", $parent_closed_reopened_replacement, $return_str);
                $return_str = str_replace("$match_full_sc_string<{$match_parent_block_el}></{$match_parent_block_el}>", $match_full_sc_string, $return_str);

                return $return_str;
            },
            (string) $content
        );
    }
}
