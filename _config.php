<?php

use Shortcodable\Shortcodable;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\TinyMCE\TinyMCEConfig;

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
        TinyMCEConfig::get($htmlEditorName)
            ->enablePlugins([
                'shortcodable' => $shortcodableModuleResourceLoader->getResource('client/dist/js/editor_plugin.js'),
            ])
            ->addButtonsToLine(1, '| shortcodable')
            ->setOption('shortcodable_placeholder_codes', $sc_placeholder_codes);
    }
}
