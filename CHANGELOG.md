# Changelog

This file covers the 4.x line (Silverstripe 4 and 5). Later releases are documented on `main`.

## 4.0.18 (2026-11-02)

### Fixed

- **The attributes of a shortcode selected in the editor are parsed without creating DOM nodes.**
  Opening the dialog on a selected shortcode built it as an element in the CMS document to read its
  attributes. They are now parsed in an inert document; values, entities and lower-cased attribute
  names come out as before. Upgrading is recommended.
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
