# Changelog

## 5.2.2 (2026-11-03)

### Fixed

- **The placeholder of a shortcode whose record was deleted no longer ends in a 500** (#6). The
  placeholder endpoint looked the `ObjectID` up with `byID()` and called a method on the result, which
  is `null` once the record is gone. The class singleton now answers instead, as it already did for a
  shortcode without an ID, so the editor shows a placeholder.

### Changed

- **A shortcodable class without its parser method throws a `LogicException`** (#5), with the same
  message, instead of `user_error(..., E_USER_ERROR)`, which PHP 8.4 deprecates.
  **Behaviour change:** on a misconfigured site this is now an uncaught (but catchable) exception
  instead of a fatal error; every request still fails until the class is fixed.

## 5.2.1 (2026-11-02)

### Security

- **Script in a selected shortcode's attributes no longer runs when the shortcode dialog is opened**
  ([GHSA-c6p4-wpcv-vj6x](https://github.com/restruct/silverstripe-shortcodable/security/advisories/GHSA-c6p4-wpcv-vj6x),
  stored XSS, Medium). Opening the dialog on a selected shortcode built it as an element in the CMS
  document to read its attributes, so a CMS editor could type `[img src="x" onerror="..."]` as text
  and have it run in the session of whoever selected it and opened the dialog. The attributes are
  now parsed in an inert document; values, entities and lower-cased attribute names come out as
  before. Upgrading is recommended.

### Fixed

- **Shortcode names with digits are matched whole** (#11). The placeholder pattern stopped a name at
  its first digit, so `[year2]` was taken for `[year]` with an attribute `2` and saved back as
  `[year 2]`, and a registered name such as `h2_title` got no placeholder. Names are now matched as
  Silverstripe's `ShortcodeParser` matches them (`\w+`, ending at whitespace or `]`).
  **Behaviour change:** a shortcode without attributes has no trailing space in its placeholder
  title and `?sc=` any more (`[year]`, was `[year ]`), so its default placeholder is one character
  narrower.

### Changed

- `composer.json` declares the licence as MIT, as `license.md` already did.
- UPGRADING: a fresh Silverstripe 5 install gets simpler 1.x; `~0.2` only keeps an existing 0.2/0.3.

## 5.2.0 (2026-10-11)

### Changed

- **`shortcode_close_parent` only closes elements that cannot hold block content** (#9). A block
  shortcode now closes `<p>`, `<h1>`-`<h6>`, `<pre>` and `<summary>`, whose content model is phrasing
  content, plus any inline elements (`<strong>`, `<a>`) between it and that element. Containers that
  can already hold block content (`<div>`, `<li>`, `<td>`, `<th>`, `<dt>`, `<dd>`, `<address>`,
  `<blockquote>`, `<section>`, `<article>`, `<figure>` and the like) are left whole, so a shortcode in a list item or table cell no longer
  breaks the list or table. Inline elements inside such a container are still closed and reopened
  around the shortcode.
  **Behaviour change:** 5.1.0 closed a container when it was on the same line as the shortcode, so
  `<div>[sc]</div>` came out as just the shortcode output, without the `<div>`. It now stays
  `<div>[sc output]</div>`; the same goes for `<li>[sc]</li>` and `<td>[sc]</td>`. Content that
  relied on the wrapping `<div>` being removed needs the shortcode moved out of it.

### Fixed

- **`shortcode_close_parent` closes the shortcode's own wrapping element** (#8). It closed the first
  element it could find earlier on the same line instead, so `<h3>Address</h3><p>[sc]</p>` came out
  as `<p></h3>[sc]<h3></p>`, which browsers repair into a stray empty `<h3>` with the `<p>` left open.
  Also wrong before: an outer `<div>` was closed instead of the `<p>` inside it, a `<hr>` or a tag
  inside an HTML comment could be taken as the parent, a second block shortcode in the same
  paragraph was left inside it, a shortcode outside any element got a stray empty `<p>`, and
  `[sc_block_other]` was handled as `[sc_block]`. The content is now walked tag by tag. When the
  shortcode sits inside inline elements (`<strong>`, `<a>`), those are closed too, up to the block
  element holding them, and reopened after it with their attributes.
  **Multi-line content:** 5.1.0 only looked within one line, so a paragraph or heading on a line of
  its own (`<p>\n[sc]\n</p>`) was left alone; it is now closed as well. Containers are not, on any
  number of lines (see Changed above).
  The walk also covers what the 5.1.0 regex got right or did not need: comma-separated shortcode
  attributes (`[sc,id=1]`), custom elements with a hyphen in their name (`<my-el>`, read whole and
  treated as inline), reopened inline elements without their `id` (or a link's `name`), so an
  anchor is not repeated, and a `>` inside a quoted attribute value.
  **Limit:** a block shortcode inside more than 10 nested inline elements, or inside inline
  elements whose opening tags add up to more than 8 KB, is left where it is, untouched. Closing
  and reopening every level for every shortcode made the output grow with nesting depth (or tag
  length) times the number of shortcodes. With the limits, the markup reopened per shortcode is
  bounded (10 elements, 8 KB), so the time and the output size grow linearly with the content.

## 5.1.0 (2026-09-25)

**One line for Silverstripe 5 and 6.** `main` now declares `silverstripe/framework ^5 || ^6` on PHP
`^8.1`. Silverstripe 4 is no longer supported or tested; the `4.x` tags stay available for projects
still on it. Upgrade notes: [UPGRADING.md](UPGRADING.md).

The placeholder image fix below was also released for the 4.x line as 4.0.17 (branch `v4`, formerly `ss4-5`).

### Fixed

- **The 5.0.x releases could not be installed from Packagist.** They required
  `restruct/silverstripe-simpler ~1.0`, and simpler had no 1.x release until 1.0.0 (2026-09-25), so Composer refused every
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
