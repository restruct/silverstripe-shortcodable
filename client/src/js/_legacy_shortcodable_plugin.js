/* global tinymce, window */

//import plugin from "./TinyMCE_shortcodable";
//import tinymce from 'tinyM';
const commandName = 'shortcodable';

(function () {
    if (typeof tinyMCE !== 'undefined') {

        tinyMCE.create('tinymce.plugins.shortcodable', {
            getInfo: function () {
                return {
                    longname: 'Shortcodable - Shortcode UI plugin for SilverStripe',
                    author: 'Shea Dawson',
                    authorurl: 'http://www.livesource.co.nz/',
                    infourl: 'http://www.livesource.co.nz/',
                    version: "1.0"
                };
            },

            init: function (editor) {
                var me = tinyMCE.activeEditor.plugins.shortcodable;

                editor.addButton(commandName, {
                    title: 'Insert Shortcode',
                    cmd: 'shortcodable',
                    'class': 'mce_shortcode'
                });

                editor.addCommand(commandName, () => {
                    const field = window.jQuery(`#${editor.id}`).entwine('ss');

                    field.openShortcodeDialog();
                });

                editor.on('LoadContent', function(e) {
                    var newContent = me.replaceShortcodesWithPlaceholders(e.content, e);
                    editor.execCommand('mceSetContent', false, newContent, false);
                });


                /*
                // On load replace shorcode with placeholder.
                ed.onLoadContent.add(function (ed, o) {
                    var newContent = me.replaceShortcodesWithPlaceholders(o.content, ed);
                    ed.execCommand('mceSetContent', false, newContent, false);
                });

                 */

                editor.on('dblclick', function(e) {
                    editor.execCommand(commandName);
                });

                /*
                ed.onDblClick.add(function (ed, e) {
                    var dom = ed.dom, node = e.target;
                    if (node.nodeName === 'IMG' && dom.hasClass(node, 'shortcode-placeholder') && e.button !== 2) {
                        ed.execCommand('shortcodable');
                    }
                });

                 */
            },

            // replace shortcode strings with placeholder images
            replaceShortcodesWithPlaceholders: function (content, editor) {
                var plugin = tinyMCE.activeEditor.plugins.shortcodable;
                var placeholderClasses = 'null';// jQuery('#' + editor.id).entwine('ss').getPlaceholderClasses();

                if (placeholderClasses) {
                    return content.replace(/\[([a-z_]+)\s*([^\]]*)\]/gi, function (found, name, params) {
                        var id = plugin.getAttribute(params, 'id');
                        if (placeholderClasses.indexOf(name) != -1) {
                            var src = encodeURI('admin/shortcodable/shortcodePlaceHolder/' + name + '/' + id + '?Shortcode=[' + name + ' ' + params + ']');
                            var img = jQuery('<img/>')
                                .attr('class', 'shortcode-placeholder mceItem')
                                .attr('title', name + ' ' + params)
                                .attr('src', src);
                            return img.prop('outerHTML');
                        }

                        return found;
                    });
                } else {
                    return content;
                }
            },

            // replace placeholder tags with shortcodes
            replacePlaceholdersWithShortcodes: function (co) {
                var content = jQuery(co);
                content.find('.shortcode-placeholder').each(function () {
                    var el = jQuery(this);
                    var shortCode = '[' + tinymce.trim(el.attr('title')) + ']';
                    el.replaceWith(shortCode);
                });
                var originalContent = '';
                content.each(function () {
                    if (this.outerHTML !== undefined) {
                        originalContent += this.outerHTML;
                    }
                });
                return originalContent;
            },

            // get an attribute from a shortcode string by it's key
            getAttribute: function (string, key) {
                var attr = new RegExp(key + '=\"([^\"]+)\"', 'g').exec(string);
                return attr ? tinymce.DOM.decode(attr[1]) : '';
            }
        });

        // Adds the plugin class to the list of available TinyMCE plugins
        tinymce.PluginManager.add(commandName, tinymce.plugins.shortcodable);
        //tinymce.PluginManager.add(commandName, (editor) => plugin.init(editor));

    }
})();
