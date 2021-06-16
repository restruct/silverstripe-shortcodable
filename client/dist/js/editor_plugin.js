/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!****************************************!*\
  !*** ./client/src/js/editor_plugin.js ***!
  \****************************************/
var shortcodable_editorplugin = {
  editor: null,
  getInfo: function getInfo() {
    return {
      longname: 'shortcodable_editorplugin - Shortcode UI plugin for SilverStripe',
      version: "2.0"
    };
  },
  init: function init(editor) {
    // save some references
    this.editor = editor;
    shortcodable.init(); //        ed.addButton('shortcodable_editorplugin', {
    //            title: 'Insert Shortcode',
    //            cmd: 'shortcodable_editorplugin',
    //            'class': 'mce_shortcode'
    //        });
    //        ed.addCommand('shortcodable_editorplugin', function (ed) {
    //            jQuery('#' + this.id).entwine('ss').openShortcodeDialog();
    //        });
    //        // On load replace shorcode with placeholder.
    //        ed.onLoadContent.add(function (ed, o) {
    //            var newContent = me.replaceShortcodesWithPlaceholders(o.content, ed);
    //            ed.execCommand('mceSetContent', false, newContent, false);
    //        });
    //        ed.onDblClick.add(function (ed, e) {
    //            var dom = ed.dom, node = e.target;
    //            if (node.nodeName === 'IMG' && dom.hasClass(node, 'shortcode-placeholder') && e.button !== 2) {
    //                ed.execCommand('shortcodable_editorplugin');
    //            }
    //        });
  },
  // source -> WYSIWYG: Substitute shortcodes with image placeholders
  fromSrc: function fromSrc(source, editor) {
    // return source;
    return shortcodable.shortcodesToPlaceholders(source, editor);
  },
  // replace placeholder tags with shortcodes
  // WYSIWYG -> source: Substitutes the placeholders with the tokens
  toSrc: function toSrc(source, editor) {
    // return source;
    return shortcodable.placeholdersToShortcodes(source, editor);
  }
}; //
// TinyMCE plugin definitions
//

(function () {
  if (typeof tinymce !== 'undefined') {
    //
    // TinyMCE~3 definition (SS3 version)
    //
    if (tinymce.majorVersion < 4) {//            tinymce.create('tinymce.plugins.shortcodable_editorplugin', {
      //                getInfo : function() {
      //                    return shortcodable_editorplugin.info;
      //                },
      //                init : function(editor, url) {
      ////                    // init the 'core'
      ////                    shortcodable_editorplugin.init(editor);
      //                    // Listeners
      //                    editor.onBeforeSetContent.add( function(ed, e) {
      //                        e.content = shortcodable_editorplugin.fromSrc(e.content);
      //                    } );
      //                    editor.onPostProcess.add( function(ed, e) {
      //                        e.content = shortcodable_editorplugin.toSrc(e.content);
      //                    } );
      //                },
      //                createControl : function (n, cm) {
      //                    // add button (@TODO: check if TinyMCE
      //                    editor.addButton('shortcodable_editorplugin', {
      //                        classes: 'shortcodable_editorplugin',
      //                        tooltip: 'Insert/edit shortcode',
      //                        onclick: function (e) {
      //                            alert('TODO: implement a TinyMCE 3 version of thi plugin...')
      //                        }
      //                    });
      //                },
      //            });
      //            // Adds the plugin class to the list of available TinyMCE plugins
      //            tinymce.PluginManager.add("shortcodable_editorplugin", tinymce.plugins.shortcodable_editorplugin);
    } //
    // TinyMCE 4+ definition (front-end/newest version)
    //
    else {
        tinymce.PluginManager.add('shortcodable', function (editor, url) {
          // init (set listeners etc)
          shortcodable_editorplugin.init(editor); // Set some listeners
          //                editor.onBeforeSetContent.add( function(e, editor) {

          editor.on('beforeSetContent', function (e) {
            e.content = shortcodable_editorplugin.fromSrc(e.content, editor);
          }); //                editor.onPostProcess.add( function(ed, editor) {

          editor.on('postProcess', function (e) {
            e.content = shortcodable_editorplugin.toSrc(e.content, editor);
          }); // add button

          editor.addButton('shortcodable', {
            classes: 'shortcodable',
            tooltip: 'Insert/edit shortcode',
            onclick: shortcodable.openDialog //                    onclick: function (event) {
            //                        jQuery('#' + editor.id).entwine('ss').openShortcodeDialog();
            //                    }
            ////                        tinyMCE.activeEditor.selection.setContent(shortcodable_editorplugin.fromSrc('{$' + e.control.settings.field + '}'));
            //
            //                        // Tries with the TinyMCE 'dialog' component (SS uses a BS one...)
            //                        // Open a URL as content: admin/shortcodable_editorplugin/ShortcodeForm/forTemplate
            ////                        tinymce.activeEditor.windowManager.open({
            ////                           title: 'Insert/edit shortcode',
            ////                           url: 'admin/shortcodable_editorplugin/ShortcodeForm/forTemplate'
            ////                        });
            //                        // Normal TinyMCE 'modal' (dialog)
            ////                        tinymce.activeEditor.windowManager.open({
            ////                            title: 'Insert/edit shortcode', // The dialog's title - displayed in the dialog header
            ////                            body: {
            ////                                type: 'panel', // The root body type - a Panel or TabPanel
            ////                                items: [ // A list of panel components
            ////
            ////                                ]
            ////                            },
            ////                            buttons: [ // A list of footer buttons
            ////                            ]
            ////                        });
            //                    }

          });
        });
      }
  }
})();
/******/ })()
;