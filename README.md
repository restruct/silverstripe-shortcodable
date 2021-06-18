# Silverstripe 4 shortcode module

Adds a ![](docs/screens/button.png) button to HTMLEditorField for CMS users to insert Shortcodes in page content.<br />
Shortcodes can optionally be represented in TinyMCE with a placeholder image.

![](docs/screens/dialog.png)
This module is a partial-to-largely rewrite of sheadawson/silverstripe-shortcodable.<br>
It depends on [Silverstripe Simpler](https://github.com/restruct/silverstripe-simpler) for some non-react UI functionalities (mainly the modal dialog).

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


## Shortcode placeholder images
To display a nice image instead of the raw shortcode in the CMS editor, implement a getShortcodePlaceHolder method on your shortcodable object:

```php
/**
* Redirect to an image OR return image data directly to be displayed as shortcode placeholder in the editor
* (getShortcodePlaceHolder gets loaded as/from the 'src' attribute of an <img> tag)
*
* @param array $attributes attribute key-value pairs of the shortcode
* @return \SilverStripe\Control\HTTPResponse
**/
public function getShortcodePlaceHolder($attributes)
{
    // Flavour one: redirect to image URL (for this example we're also including the attributes array in the URL)
    Controller::curr()->redirect('https://www.silverstripe.org/apple-touch-icon-76x76.png?attrs='.json_encode($attributes));

    // Flavour two: output image/svg data directly (any bitmap but may also be SVG)
    // Hint: process attributes eg to show a set of image thumbnails wrapped in an SVG as gallery-placeholder
    $response = Controller::curr()->getResponse();
    $response->addHeader('Content-Type','image/svg+xml');
    $response->addHeader('Vary','Accept-Encoding');
    $response->setBody('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
      <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
      <path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
    </svg>');
    $response->output();
}
```

## Status

- [x] TinyMCE button/plugin
- [x] Shortcode form in popup dialog
- [x] Custom/configurable shortcode independent of class name
- [x] Custom/configurable shortcode parser callback methods
- [x] Inserting new & editing existing shortcode (+undo)
- [x] Re-implement DataObjects as shortcodable (incl. getShortcodableRecords etc)
- [x] Re-implement placeholders
- [x] Check/re-implement(?) [BOM fix](https://github.com/sheadawson/silverstripe-shortcodable/pull/5) and in [ShortcodeController](https://github.com/sheadawson/silverstripe-shortcodable/blob/master/src/Controller/ShortcodableController.php#L240)
- [ ] Check/re-implement(?) [P/DIV wrapper fix](https://github.com/sheadawson/silverstripe-shortcodable/pull/51/files)
- [ ] Check/implement(?) [wrapping shortcodes](https://github.com/sheadawson/silverstripe-shortcodable/pull/73)
- [ ] ...


## Refs:
- [General shortcode documentation](https://docs.silverstripe.org/en/4/developer_guides/extending/shortcodes/)
- [Shortcodable V2 documentation](https://github.com/sheadawson/silverstripe-shortcodable/blob/e2e2f1a2fa981d56e3c8ba63808fbe05da3d20f0/README.md)
