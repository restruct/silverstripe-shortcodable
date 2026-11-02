# README screenshots

`readme.shot.ts` writes the PNGs in `docs/images/` that the module README shows. It is not a test
suite: it runs with its own config, `screenshots/playwright.config.ts`, which the browser tests and
CI never load, so a normal test run never rewrites the images. Never edit the PNGs by hand;
regenerate them.

What it needs:

- a Silverstripe 6 host with this module and `silverstripe/htmleditor-tinymce` installed, and the
  browser fixtures from `tests/browser/fixtures/` copied into it (PHP into `app/src/`, `_config/`
  into `app/_config/`);
- `SCB_README_SHOWCASE=1` in that host's environment, then the database built. With it set,
  `fixtures/_config/showcase.yml` registers the four showcase shortcodes (`ScbShowcase*`) instead
  of the browser specs' own, and `ScbShowcaseContact` seeds two contacts and a published
  "Plan your visit" page that uses all four;
- `BROWSER_TARGET_URLS` pointing at that host (only the `ss6` target is used, else the first).

The shared runner in the maintainers' workspace does all of that (`targets.sh` already requires
TinyMCE on Silverstripe 6):

```bash
BROWSER_ONLY=ss6 BROWSER_ENV="SCB_README_SHOWCASE=1" \
  tools/browser/run.sh silverstripe-shortcodable --config=screenshots/playwright.config.ts
```

The runner rewrites the host's `BROWSER_ENV` block on every run, so a later spec run on the same
host, without the variable, gets the specs' own shortcodes back (the seeded page stays; the specs
do not look at it).

Against a host you started yourself, from `tests/browser/`:

```bash
npm ci
BROWSER_TARGET_URLS="ss6=http://127.0.0.1:8898" npx playwright test --config=screenshots/playwright.config.ts
```
