# Browser-test targets for this module, sourced by the shared runner
# (~/Sites/0_ss-mods-maintenance/tools/browser/run.sh) and by .github/workflows/browser-tests.yml.
# Plain bash assignments only. CI tests only the targets with an empty SS<n>_SRC_REF (= this
# checkout). main serves both majors.

BROWSER_PACKAGE="restruct/silverstripe-shortcodable"
BROWSER_TARGETS="ss5 ss6"

SS5_RECIPE="^5"
SS5_PHP="8.3"
SS5_PORT="8897"
SS5_SRC_REF=""

SS6_RECIPE="^6"
SS6_PHP="8.3"
SS6_PORT="8898"
SS6_SRC_REF=""
# TinyMCE is a separate module on Silverstripe 6 and recipe-cms does not include it; without it
# there is no editor to put the shortcode button in (see the README's requirements).
SS6_EXTRA_REQUIRE="silverstripe/htmleditor-tinymce:^1"

# The fixture shortcodes are registered in fixtures/_config/shortcodable.yml (the module reads
# Shortcodable.shortcodable_classes from config only).
