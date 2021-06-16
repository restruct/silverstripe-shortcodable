<?php

use Shortcodable\Shortcodable;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Forms\HTMLEditor\HtmlEditorConfig;


// enable shortcodable buttons and add to HtmlEditorConfig
$htmlEditorNames = Config::inst()->get(Shortcodable::class, 'htmleditor_names');
if (is_array($htmlEditorNames)) {
    $shortcodableModuleResourceLoader = ModuleLoader::inst()->getManifest()->getModule('restruct/silverstripe-shortcodable');
    foreach ($htmlEditorNames as $htmlEditorName) {
        HtmlEditorConfig::get($htmlEditorName)
            ->enablePlugins([
                'shortcodable' => $shortcodableModuleResourceLoader->getResource('client/dist/js/editor_plugin.js'),
            ])
            ->addButtonsToLine(1, '| shortcodable');
    }
}

// register classes added via yml config
Shortcodable::register_classes(Config::inst()->get(Shortcodable::class, 'shortcodable_classes'));

//// register shortcodes added via yml config
//Shortcodable::register_multiple(Config::inst()->get(Shortcodable::class, 'shortcode_class_map'));
