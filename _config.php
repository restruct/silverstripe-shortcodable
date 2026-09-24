<?php

use Shortcodable\Shortcodable;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
//use SilverStripe\TinyMCE\TinyMCEConfig;

// Register classes added via yml config.
// NOTE: Shortcodes must be registered via YAML config (not programmatically in _config.php)
// because placeholder codes are collected here for TinyMCE. If your shortcode isn't showing
// placeholders, ensure it's registered in yml config, not in another module's _config.php.
Shortcodable::register_classes(Config::inst()->get(Shortcodable::class, 'shortcodable_classes'));

// enable shortcodable buttons and add to HtmlEditorConfig
$htmlEditorNames = Config::inst()->get(Shortcodable::class, 'htmleditor_names');
if (is_array($htmlEditorNames)) {

    $shortcodableModuleResourceLoader = ModuleLoader::inst()->getManifest()->getModule('restruct/silverstripe-shortcodable');

    $sc_placeholder_code_class_map = Shortcodable::get_shortcodable_classes_with_placeholders();
    $sc_placeholder_codes = array_keys($sc_placeholder_code_class_map);

    foreach ($htmlEditorNames as $htmlEditorName) {
        # HTMLEditorConfig::get() exists in framework on both Silverstripe 5 and 6 and returns whatever
        # config class is configured for that editor. TinyMCEConfig itself moved on Silverstripe 6
        # (SilverStripe\Forms\HTMLEditor -> SilverStripe\TinyMCE, in the separate
        # silverstripe/htmleditor-tinymce module), so naming it directly fatals on one major or the other.
//        TinyMCEConfig::get($htmlEditorName)
        $editorConfig = HTMLEditorConfig::get($htmlEditorName);
        # Only a TinyMCE config has enablePlugins()/addButtonsToLine(). On Silverstripe 6 without
        # silverstripe/htmleditor-tinymce installed there is no TinyMCE at all, and calling these on the
        # framework's plain config would fatal every request - so skip rather than break the site.
        if (!Shortcodable::is_tinymce_config($editorConfig)) {
            continue;
        }
        $editorConfig
            ->enablePlugins([
                'shortcodable' => $shortcodableModuleResourceLoader->getResource('client/dist/js/editor_plugin.js'),
            ])
            ->addButtonsToLine(1, '| shortcodable')
            ->setOption('shortcodable_placeholder_codes', $sc_placeholder_codes);
    }
}
