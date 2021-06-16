/* global tinymce, window */
import i18n from 'i18n';
import TinyMCEActionRegistrar from 'lib/TinyMCEActionRegistrar';
import React from 'react';
import ReactDOM from 'react-dom';
import jQuery from 'jquery';
//import {createInsertMenu} from 'containers/InsertLinkModal/InsertLinkModal';
import {loadComponent} from 'lib/Injector';

const commandName = 'shortcodable';

const modalId = 'insert-link__dialog-wrapper--phone';
const sectionConfigKey = 'SilverStripe\\Admin\\LeftAndMain';
const formName = 'ShortcodeForm';

const FormBuilderModal = loadComponent('FormBuilderModal');

//const InsertShortCodeModal = loadComponent(createInsertMenu(sectionConfigKey, formName));

const plugin = {
    init(editor) {

        editor.addButton(commandName, {
            icon: false,
            text: 'Shortcodes',
            title: 'Insert Shortcode',
            cmd: 'shortcodable',
            'class': 'mce_shortcode',
            tooltip: commandName,
            onclick: ()=>{
                const field = window.jQuery(`#${editor.id}`).entwine('ss');
                field.openShortcodeDialog();
            }
        });

        editor.on('dblclick', function(e) {
            editor.execCommand(commandName);
        });

    },
};

jQuery.entwine('ss', ($) => {
    $('textarea.htmleditor').entwine({
        openShortcodeDialog() {
            let dialog = $(`#${modalId}`);

            if (!dialog.length) {
                dialog = $(`<div id="${modalId}" />`);
                $('body').append(dialog);
            }
            dialog.addClass('shortcodable__dialog-wrapper');

            dialog.setElement(this);
            dialog.open();
        },
        getPlaceholderClasses: function() {
            var classes = $(this).data('placeholderclasses');
            if (classes) {
                return classes.split(',');
            }
        },
        onbeforesubmitform: function(e) {
            // Save the updated content here, rather than _after_ replacing the placeholders
            // otherwise you're replacing the shortcode html with the shortcode, then writing
            // the html back to the textarea value, overriding what the shortcode conversion
            // process has done
            this._super(e);

            var shortcodable = tinyMCE.activeEditor.plugins.shortcodable;
            if (shortcodable) {
                var ed = this.getEditor();
                var newContent = shortcodable.replacePlaceholdersWithShortcodes($(this).val(), ed);
                $(this).val(newContent);
            }
        }
    });

    $('select.shortcode-type').entwine({
        onchange: function(){
            this.parents('form:first').reloadForm('type', this.val());
        }
    });

    // add shortcode controller url to cms-editor-dialogs
    $('#cms-editor-dialogs').entwine({
        onmatch: function(){
            this.attr('data-url-shortcodeform', 'admin/shortcodable/ShortcodeForm/forTemplate');
        }
    });


    /**
     * Assumes that $('.insert-link__dialog-wrapper').entwine({}); is defined for shared functions
     */
    $(`#${modalId}`).entwine({
        renderModal(isOpen) {
            const handleHide = () => this.close();
            const handleInsert = (...args) => this.handleInsert(...args);
            const attrs = this.getOriginalAttributes();
            const selection = tinymce.activeEditor.selection;
            const selectionContent = selection.getContent() || '';
            const tagName = selection.getNode().tagName;
            //const requireLinkText = tagName !== 'A' && selectionContent.trim() === '';

            // create/update the react component
            ReactDOM.render(
                <FormBuilderModal
                    isOpen={isOpen}
                    onInsert={handleInsert}
                    onClosed={handleHide}
                    title={i18n._t('Admin.LINK_PHONE', 'Shortcode')}
                    bodyClassName="modal__dialog"
                    className="shortcodable__dialog-wrapper"
                    fileAttributes={attrs}
                    identifier="Admin.InsertShortcodeModal"
                    //requireLinkText={requireLinkText}
                />,
                this[0]
            );

        },

        getOriginalAttributes() {
            const editor = this.getElement().getEditor();
            const node = $(editor.getSelectedNode());

            let phone = (node.attr('href') || '').replace(/^tel:/, '');

            return {
                Link: phone,
                Description: node.attr('title'),
            };
        },

        buildAttributes(data) {
            const attributes = this._super(data);

            let href = '';

            let phone = attributes.href.replace(/^tel:/, '');

            if (phone) {
                href = `tel:${phone}`;
            }
            attributes.href = href;

            delete attributes.target;

            return attributes;
        },
    });
});


// Adds the plugin class to the list of available TinyMCE plugins
tinymce.PluginManager.add(commandName, (editor) => plugin.init(editor));
export default plugin;
