# Changelog

This file covers the 4.x line (Silverstripe 4 and 5). Later releases are documented on `main`.

## 4.0.17

### Fixed

- **The placeholder image endpoint (`admin/shortcodable/placehold.img`) validates and escapes its
  parameters.** They were interpolated into the SVG unescaped. Sizes now accept a positive integer or
  `100%`, colours 3 or 6 hex digits; anything else falls back to `default_placeholder`. Font and text
  are escaped for XML, a non-scalar font or text falls back to the default, and the font size is
  capped at `default_placeholder.full_height`. Upgrading is recommended.
- **Placeholder text with non-ASCII characters no longer breaks the SVG.** `htmlentities()` produced
  HTML named entities such as `&eacute;`, which XML does not define.
