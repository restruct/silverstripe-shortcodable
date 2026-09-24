# Upgrading

## To 5.1

5.1 supports Silverstripe 5 and 6 from one line. The public API (`Shortcodable::register_class()`,
the `shortcodable_classes` config, and the methods a shortcodable class implements) is unchanged.

### From 4.x on Silverstripe 5

Change the constraint from `^4` (or `~4.0`) to `^5.1`. Nothing else is required: simpler stays on its
`0.x` line (`~0.2`), and the dialog is loaded the same way as on 4.0.16.

Differences you may notice:

- The editor stylesheet `client/dist/styles/editor.css` is now added to TinyMCE. It keeps shortcode
  placeholder images inline where a theme's editor styles make images block-level.
- The shortcode insert/replace code is the newer TinyMCE 6 selection handling from the 5.0.x line.
- `admin/shortcodable/placehold.img` now rejects invalid `w`, `h`, `bg`, `fg` values and uses the
  `default_placeholder` values instead. If you call it yourself, pass sizes as whole pixels or `100%`
  and colours as hex without `#`.

### On Silverstripe 6

Require TinyMCE, which Silverstripe 6 ships as a separate module and `recipe-cms` does not include:

```bash
composer require silverstripe/htmleditor-tinymce
```

Without it the site boots, but no shortcode button is added because there is no TinyMCE editor.

If you were on 5.0.x through a VCS or path repository, switch to `^5.1` from Packagist.
