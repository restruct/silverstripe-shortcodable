# Changelog

This file covers the 4.x line (Silverstripe 4 and 5). Later releases are documented on `main`.

## 4.0.19 (2026-11-03)

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

## 4.0.18 (2026-11-02)

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
  Silverstripe's `ShortcodeParser` matches them (`\w+`, ending at whitespace or `]`). A shortcode
  without attributes has no trailing space in its placeholder title any more.

## 4.0.17

### Fixed

- **The placeholder image endpoint (`admin/shortcodable/placehold.img`) validates and escapes its
  parameters.** They were interpolated into the SVG unescaped. Sizes now accept a positive integer or
  `100%`, colours 3 or 6 hex digits; anything else falls back to `default_placeholder`. Font and text
  are escaped for XML, a non-scalar font or text falls back to the default, and the font size is
  capped at `default_placeholder.full_height`. Upgrading is recommended.
- **Placeholder text with non-ASCII characters no longer breaks the SVG.** `htmlentities()` produced
  HTML named entities such as `&eacute;`, which XML does not define.
