# Silverstripe 4 shortcode module

Adds a ![](docs/screens/button.png) button to HTMLEditorField for CMS users to insert Shortcodes in page content.<br />
(TODO:) Shortcodes can optionally be represented in TinyMCE with a placeholder image.

![](docs/screens/dialog.png)
This module is a SS4-ish partly rewrite of sheadawson/silverstripe-shortcodable.

## Configuration
Register DataObjects or classes as shortcodable via Yaml config:

```yml
---
name: my_shortcodables
Only:
    classexists: Shortcodable\Shortcodable
---
Shortcodable\Shortcodable:
    shortcodable_classes:
        - My\Namespaced\Shortcodes\CurrentYearShortcode
        - My\Namespaced\Shortcodes\SomeOtherShortcode
        - ...
---
```


## Required methods on shortcodable objects/classes

Implement these methods on your shortcodable classes (may also be added via an Extension):

**(Required) shortcode parser callback:**<br />
`public static function parse_shortcode(...)`<br />
**OR:**<br />
`public function MyCustomParser(...)` combined with:<br />
`private static $shortcode_callback = 'MyCustomParser'`<br />

**Parser method arguments:** (see [shortcode documentation](https://docs.silverstripe.org/en/4/developer_guides/extending/shortcodes/#parameter-values)) <br />
   `($attrs, $content=null, $parser=null, $shortcode, $info)`

**NOTE: the parser method gets called on a singleton object instance.**<br />
   (So there's no `$this->ID` or `$this->owner->ID` etc.)


**Optional:**
- `private static $shortcode` (optional, else short ClassName will be used as [ClassName])
- `getShortcodeLabel()` (optional nice description for in dropdown, `singular_name()` or else `ClassName.Shortname` will be used as fallback)
- `getShortcodeFields()` (optional, attribute-formfields for popup)
- `getShortcodableRecords()` (optional, if applied to DataObject)
- `getShortcodePlaceholder($attributes)` (optional)


## Status

-[x] TinyMCE button/plugin
-[x] Shortcode form in popup dialog
-[x] Custom/configurable shortcode independent of class name
-[x] Custom/configurable shortcode parser callback methods
-[x] Inserting new & editing existing shortcode (+undo)
-[x] Re-implement DataObjects as shortcodable (incl. getShortcodableRecords etc)
-[ ] Re-implement placeholders
-[ ] Check/re-implement(?) [BOM fix](https://github.com/sheadawson/silverstripe-shortcodable/pull/5) and in [ShortcodeController](https://github.com/sheadawson/silverstripe-shortcodable/blob/master/src/Controller/ShortcodableController.php#L240)
-[ ] Check/re-implement(?) [P/DIV wrapper fix](https://github.com/sheadawson/silverstripe-shortcodable/pull/51/files)
-[ ] Check/implement(?) [wrapping shortcodes](https://github.com/sheadawson/silverstripe-shortcodable/pull/73)
-[ ] ...


## Refs:
- [General shortcode documentation](https://docs.silverstripe.org/en/4/developer_guides/extending/shortcodes/)
- [Shortcodable V2 documentation](https://github.com/sheadawson/silverstripe-shortcodable/blob/e2e2f1a2fa981d56e3c8ba63808fbe05da3d20f0/README.md)
