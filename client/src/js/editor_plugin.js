var shortcodable_editorplugin = {

    editor : null,

    getInfo: function () {
        return {
            longname: 'shortcodable_editorplugin - Shortcode UI plugin for SilverStripe',
            version: "2.0"
        };
    },

    init: function (editor) {
        // save some references
        this.editor = editor;
        if(!shortcodable.initialized) {
            shortcodable.init();
        }
    },

    // Plaintxt source [shortcodetags] -> WYSIWYG HTML: Substitute shortcodes with image placeholders
    fromSrc : function(source, editor){
        // return source;
        return shortcodable.shortcodesToPlaceholders(source, editor);
    },

    // Replace HTML placeholder tags (<img>) with shortcodes
    toSrc : function(source, editor){
        return shortcodable.placeholdersToShortcodes(source, editor);
    },

};

// TinyMCE plugin definitions
(function() {
    if (typeof tinymce === 'undefined') {
        return;
    }

    if(tinymce.majorVersion <= 5){ // Silverstripe 4 - TinyMCE 4

        tinymce.PluginManager.add('shortcodable', function(editor, url) {

            // init (set listeners etc)
            shortcodable_editorplugin.init(editor);

            // Set some listeners
            editor.on('beforeSetContent', function(e) {
                e.content = shortcodable_editorplugin.fromSrc(e.content, editor);
            });
            editor.on('postProcess', function(e) {
                e.content = shortcodable_editorplugin.toSrc(e.content, editor);
            });

            // add button
            editor.addButton('shortcodable', {
                classes: 'shortcodable',
                tooltip: 'Insert/edit shortcode',
                onclick: shortcodable.openDialog,
            });

        });

    } else { // Silverstripe 5 - TinyMCE 6 https://docs.silverstripe.org/en/5/changelogs/5.0.0/#tinymce6

        tinymce.PluginManager.add('shortcodable', function(editor, url) {

            // init (set listeners etc)
            shortcodable_editorplugin.init(editor);

            // Set some listeners
            editor.on('beforeSetContent', function(e) {
                e.content = shortcodable_editorplugin.fromSrc(e.content, editor);
            });
            editor.on('postProcess', function(e) {
                e.content = shortcodable_editorplugin.toSrc(e.content, editor);
            });

            // add button (https://github.com/silverstripe/silverstripe-htmleditor-tinymce/blob/1/client/src/plugins/TinyMCE_sslink.js)
            editor.ui.registry.addButton('shortcodable', {
                classes: 'shortcodable',
                tooltip: 'Insert/edit shortcode',
                onAction: shortcodable.openDialog,
                icon: 'shortcodable',
            });
            editor.ui.registry.addIcon(
                'shortcodable',
                '<svg width="16" height="16" viewBox="0 0 16 16">' +
                        '<path d="M 0 0 L 0 16 L 5.34375 16 L 5.34375 14.414062 L 1.5859375 14.414062 L 1.5859375 1.5859375 L 5.34375 1.5859375 L 5.34375 0 L 0 0 z M 10.65625 0 L 10.65625 1.5859375 L 14.414062 1.5859375 L 14.414062 14.414062 L 10.65625 14.414062 L 10.65625 16 L 16 16 L 16 0 L 10.65625 0 z" />' +
                        '</svg>'
            );

        });

    }

})();
