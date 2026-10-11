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
        // Since we cannot read the 'identifier' (ShortcodeParser::$active_instance), instead we filter on registered
        // shortcodes of the current instance to prevent running this on instances it shouldn't (eg 'regenerator');
        /** @var ShortcodeParser $parser */
        $parser = $this->getOwner();
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
        
        # Issue #8: the regex below took the FIRST opening tag that its lazy `.*?` could reach on the
        # line as the parent, not the shortcode's own wrapping element - so an earlier sibling (an <h3>
        # before the <p>), an outer <div>, a void <hr> or a tag inside a comment got closed instead.
        # It is kept (disabled) for reference; closeParentElements() walks the tags in order and
        # closes the element that actually wraps the shortcode.
        // // Assemble regex string: https://regex101.com/r/FriwNk/1
        // // ~> appended .{0,10} to also select a part of the string directly following our schortcode tag
        # (the next disabled line is split at '?' . '>' only because a literal question mark + '>' ends PHP
        # mode even inside a // comment; the string is otherwise unchanged)
        // $regex = '<([a-z0-9]+)[^>]*?' . '>.*?(\[(' . implode('|', $sc_block_wrapped_map) . ')[^]]*?\]([^[]*?\[/\3\])*).{0,10}';
        // $content = preg_replace_callback(
        //     '%'.$regex.'%i',
        //     function ($matches) use ($sc_block_wrapped_map) {
        //         $full_matched_string = $matches[0];
        //         $parent_el = $matches[1]; // [1]=>"p" <-- current wrapping element (to close & reopen)
        //         $full_sc = $matches[2]; // [2]=>"[currentyear]" <-- full shortcode string (incl [sc attr="xy"]wrapped [/sc] verstions
        //         $sc_tag = $matches[3]; // [3]=> "currentyear" <-- shortcode 'tag'
        //
        //         // eg </p>[currentyear]<p>
        //         $replacement_html = "</{$parent_el}>{$full_sc}<{$parent_el}>";
        //         // replace (eg [currentyear] -> </p>[currentyear]<p>)
        //         $return_str = str_replace($full_sc, $replacement_html, $full_matched_string);
        //
        //         $replace_items = [
        //             // <br></p>[currentyear]<p> -> </p>[currentyear]<p> (removes <br> before)
        //             "<br>$replacement_html"                 => $replacement_html,
        //             // <p></p>[currentyear] -> [currentyear]
        //             "<{$parent_el}></{$parent_el}>$full_sc" => $full_sc,
        //             // [currentyear]<p></p> -> [currentyear]
        //             "$full_sc<{$parent_el}></{$parent_el}>" => $full_sc,
        //         ];
        //
        //         return str_replace(array_keys($replace_items), array_values($replace_items), $return_str);
        //     },
        //     (string) $content
        // );
        $content = $this->closeParentElements((string) $content, $sc_block_wrapped_map);
    }

    /**
     * Elements that never hold content, so they are never a shortcode's parent.
     */
    private const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source',
        'track', 'wbr',
    ];

    /**
     * Inline (phrasing) elements: block-level output inside one of these is still inside the block
     * element around it, so the closing continues outwards to that block element.
     */
    private const INLINE_ELEMENTS = [
        'a', 'abbr', 'b', 'bdi', 'bdo', 'cite', 'code', 'data', 'del', 'dfn', 'em', 'font', 'i', 'ins',
        'kbd', 'label', 'mark', 'q', 's', 'samp', 'small', 'span', 'strike', 'strong', 'sub', 'sup',
        'time', 'tt', 'u', 'var',
    ];

    /**
     * Block elements a block shortcode closes: those whose content model is phrasing content only,
     * so block output cannot stay inside them (issue #9, option b). Every other block element - div,
     * li, td, th, dd, blockquote, section, article, figure and the like - can already hold block
     * content, so a shortcode directly inside one is left in place and the element is not split.
     * address and dt are not listed: in the WHATWG HTML standard both take flow content (with some
     * descendants excluded), so they can hold a <div>; closing a <dt> would also leave the block
     * output directly inside the <dl>. summary is phrasing content (optionally with headings).
     */
    private const CLOSABLE_ELEMENTS = [
        'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'summary',
    ];

    /**
     * Close the element(s) wrapping each block shortcode before it and reopen them after it, eg
     * `<p>Some [sc] content</p>` becomes `<p>Some </p>[sc]<p> content</p>`.
     *
     * The content is walked tag by tag with a stack of open elements, so the element closed is the
     * shortcode's own parent. If that parent is inline (<strong>, <a>), the inline elements are closed
     * up to and including the block element holding them. Only block elements that cannot hold block
     * content are closed (CLOSABLE_ELEMENTS: <p>, headings, <pre>, ...); a container such as <div>,
     * <li> or <td> is left whole, and only inline elements inside it are closed. An element without attributes that would be
     * left empty on either side is dropped rather than closed/reopened, as is a <br> directly before
     * the shortcode.
     *
     * @param string $content
     * @param string[] $tags shortcode tags configured with shortcode_close_parent
     * @return string
     */
    private function closeParentElements(string $content, array $tags): string
    {
        $tagAlternation = implode('|', array_map(function ($tag) {
            return preg_quote($tag, '%');
        }, $tags));
        # One pass over comments, HTML tags and block shortcodes, in document order. A shortcode inside
        # a tag's attribute is part of the tag match, so it is (rightly) never treated as an element.
        # The tag name must end at whitespace, ']', '/' or ',' (ShortcodeParser's own tag regex accepts
        # `[tag,attr=x]`), so [sc_block_other] is not [sc_block].
        # Enclosed content (`[sc]...[/sc]`) is matched as in the original regex: no '[' inside it.
        # Element names may contain hyphens (custom elements, eg <my-el>), so the name runs on to
        # whitespace, '/' or '>' rather than to a word boundary, which would cut it at the hyphen.
        # Two scans are bounded so the pass stays linear: an unterminated comment runs to the end of
        # the content (as it does for an HTML parser), instead of every '<!--' re-scanning to the end;
        # and a shortcode's attributes stop at the next '[', so many '[tag' without a ']' do not each
        # scan the rest of the content.
        # A tag runs to the first '>' outside a quoted attribute value: `<a title="a>b">` is one tag.
        # Stopping at the first '>' (as `[^>]*>` did) cut it at `a>` and pulled the text after the
        # tag into the reopened copy. The quantifiers are possessive so an unterminated quote fails
        # the tag at once (it is then text) instead of backtracking, which keeps the scan linear.
        $pattern = '%(?<comment><!--.*?(?:-->|$))'
            . '|(?<tag></?(?<name>[a-z][a-z0-9-]*)(?=[\s/>])(?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+>)'
            . '|(?<sc>\[(?<sctag>' . $tagAlternation . ')(?=[\s\],/])[^\]\[]*\](?:[^\[]*?\[/\k<sctag>\])*)%is';
        # Tokenise: text between the matches, plus each match with its kind. One match at a time
        # (preg_match from an offset, not preg_match_all) and kept as three flat lists rather than an
        # array per token: on large content the full match set and per-token arrays cost several
        # hundred MB.
        $kinds = $strings = $names = [];
        $cursor = 0;
        $length = strlen($content);
        while ($cursor < $length && preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE, $cursor)) {
            [$string, $offset] = $match[0];
            if ($offset > $cursor) {
                $kinds[] = 'text';
                $strings[] = substr($content, $cursor, $offset - $cursor);
                $names[] = null;
            }
            if (isset($match['sc']) && $match['sc'][1] !== -1) {
                $kinds[] = 'sc';
                $names[] = null;
            } elseif (isset($match['tag']) && $match['tag'][1] !== -1) {
                $kinds[] = 'tag';
                $names[] = strtolower($match['name'][0]);
            } else {
                $kinds[] = 'text';
                $names[] = null;
            }
            $strings[] = $string;
            # An empty match cannot happen (every alternative starts with a literal), but never loop on one
            $cursor = $offset + max(1, strlen($string));
        }
        if (!in_array('sc', $kinds, true)) {
            # No block shortcode outside a tag or comment: nothing to do
            return $content;
        }
        if ($cursor < $length) {
            $kinds[] = 'text';
            $strings[] = substr($content, $cursor);
            $names[] = null;
        }

        # The output is collected as a list of parts, one per token or inserted tag, and joined once at
        # the end. The clean-ups below only look at, or drop, the last few parts: working on the whole
        # output string instead (a regex or rtrim() over it, per shortcode) made the walk quadratic.
        $out = [];
        # Open elements, outermost first: [name, original opening tag]
        $stack = [];
        $count = count($kinds);
        for ($i = 0; $i < $count; $i++) {
            $kind = $kinds[$i];
            $string = $strings[$i];
            $name = $names[$i];

            if ($kind === 'tag') {
                $out[] = $string;
                $this->trackTag($stack, $string, $name);
                continue;
            }
            if ($kind !== 'sc' || !$stack) {
                # Text, comments, and a block shortcode with no wrapping element: nothing to close
                $out[] = $string;
                continue;
            }

            # The elements to close, innermost first: inline ancestors up to the first block one, and
            # that block one only if it cannot hold block content (CLOSABLE_ELEMENTS). A container
            # (<div>, <li>, <td>, <blockquote>, ...) stops the walk without being closed: splitting it
            # would break the list or table around it (issue #9).
            $chain = [];
            for ($s = count($stack) - 1; $s >= 0; $s--) {
                if ($this->isInline($stack[$s][0])) {
                    $chain[] = $stack[$s];
                    continue;
                }
                if (in_array($stack[$s][0], self::CLOSABLE_ELEMENTS)) {
                    $chain[] = $stack[$s];
                }
                break;
            }
            if (!$chain) {
                # Directly inside a container: nothing to close, so nothing to clean up either (a <br>
                # before the shortcode stays, as it does at the top level)
                $out[] = $string;
                continue;
            }

            # <br></p>[sc] -> </p>[sc] (a line break right before block output only adds a blank line)
            $last = $this->lastNonBlankPart($out);
            if ($last !== null && preg_match('%^<br\s*/?>$%i', $out[$last])) {
                $this->dropPartsFrom($out, $last);
            }
            foreach ($chain as [$elName, $openTag]) {
                # <p></p>[sc] -> [sc]: the element would be left empty, so drop its opening tag instead.
                # Only a bare opening tag, as before: one with attributes (an id used as an anchor, a
                # class) is closed and kept, even if empty. The whitespace after it goes too.
                $last = $this->lastNonBlankPart($out);
                if ($last !== null && preg_match('%^<[a-z0-9-]+\s*>$%i', $openTag)
                    && (strcasecmp($out[$last], $openTag) === 0 || $out[$last] === "<{$elName}>")
                ) {
                    $this->dropPartsFrom($out, $last);
                } else {
                    $out[] = "</{$elName}>";
                }
            }
            $out[] = $string;

            # [sc]<p></p> -> [sc]: closing tags directly after the shortcode (whitespace aside) are
            # consumed instead of reopening an element that would be left empty
            while ($chain) {
                $next = $i + 1;
                while ($next < $count && $kinds[$next] === 'text' && trim($strings[$next]) === '') {
                    $next++;
                }
                if ($next < $count && $kinds[$next] === 'tag' && $names[$next] === $chain[0][0]
                    && strpos($strings[$next], '</') === 0
                ) {
                    $this->trackTag($stack, $strings[$next], $names[$next]);
                    array_shift($chain);
                    $i = $next;
                    continue;
                }
                break;
            }

            # Reopen what is still open, outermost first. The block element is reopened bare, as
            # before (an id must not be duplicated); inline ones keep their attributes, so a link or
            # a styled span carries on after the shortcode - minus their id (and a link's name
            # anchor), which must stay unique.
            foreach (array_reverse($chain) as [$elName, $openTag]) {
                $out[] = $this->isInline($elName) ? $this->withoutUniqueAttributes($openTag, $elName) : "<{$elName}>";
            }
        }

        return implode('', $out);
    }

    /**
     * Drop the output parts from this index to the end. array_pop() rather than array_splice(),
     * which rebuilds the whole array and so would make every clean-up cost the full output size.
     *
     * @param string[] $out
     * @param int $from
     */
    private function dropPartsFrom(array &$out, int $from): void
    {
        while (count($out) > $from) {
            array_pop($out);
        }
    }

    /**
     * Index of the last output part that is not whitespace-only text, or null if there is none.
     * Parts are whole tokens, so a tag is always a part of its own.
     *
     * @param string[] $out
     * @return int|null
     */
    private function lastNonBlankPart(array $out): ?int
    {
        for ($p = count($out) - 1; $p >= 0; $p--) {
            if (trim($out[$p]) !== '') {
                return $p;
            }
        }
        return null;
    }

    /**
     * An opening tag without its id attribute, and without name on a link (an anchor target), so
     * a reopened copy of the element does not repeat them.
     */
    private function withoutUniqueAttributes(string $openTag, string $name): string
    {
        $attributes = $name === 'a' ? 'id|name' : 'id';
        # Whitespace before the name, so data-id or aria-name are left alone; quoted or bare values
        return preg_replace('%\s+(?:' . $attributes . ')\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>"\']+)%i', '', $openTag);
    }

    /**
     * Whether an element is inline (phrasing) content. Custom elements (a hyphen in the name) are
     * phrasing content in HTML, so they count as inline too.
     */
    private function isInline(string $name): bool
    {
        return in_array($name, self::INLINE_ELEMENTS) || strpos($name, '-') !== false;
    }

    /**
     * Update the open-element stack for one tag.
     *
     * @param array $stack open elements, outermost first: [name, original opening tag]
     * @param string $tag the tag as written
     * @param string $name its lowercased element name
     */
    private function trackTag(array &$stack, string $tag, string $name): void
    {
        if (strpos($tag, '</') === 0) {
            # Closing tag: pop up to and including its element; a stray closing tag is ignored
            for ($s = count($stack) - 1; $s >= 0; $s--) {
                if ($stack[$s][0] === $name) {
                    array_splice($stack, $s);
                    break;
                }
            }
            return;
        }
        if (in_array($name, self::VOID_ELEMENTS) || substr(rtrim($tag, '> '), -1) === '/') {
            # Void or self-closed: never open
            return;
        }
        $stack[] = [$name, $tag];
    }
}
