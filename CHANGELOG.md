# Changelog

## 5.1.0 (2026-09-25)

**One line for Silverstripe 5 and 6.** `main` now declares `silverstripe/framework ^5 || ^6` on PHP
`^8.1`. Silverstripe 4 is no longer supported or tested; the `4.x` tags stay available for projects
still on it. Upgrade notes: [UPGRADING.md](UPGRADING.md).

The placeholder image fix below was also released for the 4.x line as 4.0.17 (branch `v4`, formerly `ss4-5`).

### Fixed

- **The 5.0.x releases could not be installed from Packagist.** They required
  `restruct/silverstripe-simpler ~1.0`, and simpler has no 1.x release, so Composer refused every
  5.0.x tag. The constraint is now `~0.2 || ^1`: the simpler line matching each Silverstripe major.
- **Silverstripe 6 without `silverstripe/htmleditor-tinymce` fatal on every request** with
  `Class "SilverStripe\TinyMCE\TinyMCEConfig" not found` from `_config.php`. `recipe-cms` 6 does not
  include TinyMCE, so a default install hit this. The editor config is now fetched through
  `HTMLEditorConfig::get()`, and the plugin and button are only added to a TinyMCE config
  (`Shortcodable::is_tinymce_config()`); other editors are skipped.
- **The shortcode dialog's modal is loaded explicitly** through simpler's `AdminExtension` with
  `simpler_include_modal: true`, as on the 4.x line. `simpler-silverstripe.js` on its own does not
  provide `simpler.modal`, which the dialog uses.
- **The placeholder image endpoint (`admin/shortcodable/placehold.img`) validates and escapes its
  parameters.** They were interpolated into the SVG unescaped. Sizes now accept a positive integer or
  `100%`, colours 3 or 6 hex digits; anything else falls back to `default_placeholder`. Font and text
  are escaped for XML. Upgrading is recommended.
- **Placeholder text with non-ASCII characters no longer breaks the SVG.** `htmlentities()` produced
  HTML named entities such as `&eacute;`, which XML does not define.

### Changed

- `ShortcodableAdminController::shortcodePlaceholderImage()` returns its `HTTPResponse` instead of
  calling `output()` itself.
- The editor stylesheet is registered on whichever `TinyMCEConfig` class the installed Silverstripe
  provides (`SilverStripe\Forms\HTMLEditor\TinyMCEConfig` on 5, `SilverStripe\TinyMCE\TinyMCEConfig`
  on 6).
- `composer.json`: `silverstripe/vendor-plugin ^2 || ^3`, explicit `php ^8.1`, a `suggest` for
  `silverstripe/htmleditor-tinymce`, and a `funding` entry.
- `composer.json` requires `silverstripe/admin ^2 || ^3`. The module's config and controller build on
  `LeftAndMain`, which is part of admin, not framework; it was previously only pulled in indirectly.

### Added

- A behavioural PHPUnit suite (registration, tag and label maps, the TinyMCE button, the block-level
  wrapper rewrite, the admin endpoints and their permission check), run by GitHub Actions against
  Silverstripe 5 and 6. The Silverstripe 3 era `.travis.yml` is removed.
- README: requirements, every configuration option with its default, and how to run the tests.

## 5.0.0 - 5.0.5

Silverstripe 6 only, with TinyMCE 6 selection handling and inline Elemental editor placeholder fixes.
Not installable from Packagist (see 5.1.0).
