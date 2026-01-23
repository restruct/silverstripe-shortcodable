// import _ from 'underscore';

(function($) {

    shortcodable = {

        controller_url: 'admin/shortcodable',

        initialized: false,

        // Store selection bookmark so we can restore it after modal closes (TinyMCE 6 loses selection on focus loss)
        selectionBookmark: null,
        selectionWasCollapsed: false,

        init: function() {
            // console.log('DEBUG: shortcodable – INIT');
            this.initialized = true;

            // handle change on shortcode-type field
            // _.debounce(function, wait, true);
            $('body').on('change', 'select.shortcode-type', function(event) {
                // console.log('DEBUG: select.shortcode-type – CHANGE');
                // console.log(event);
                $.post(shortcodable.controller_url, $(this).parents('form').serializeArray(), function(data){
                        // (use the intermediary xhr_buffer element in order to have jQuery parse/activate listeners etc
                        simpler.modal.bodyHtml = $('#xhr_buffer').html(data).html();
                    })
                    .fail(function(response) { shortcodable.handleErrorResponse(response); })
                ;
            });

            // shortcode form submit button handler
            $('body').on('submit', '#Form_ShortcodeForm', function(event) {
                // console.log('DEBUG: #Form_ShortcodeForm – SUBMIT');
                // console.log(event);
                event.preventDefault();
                shortcodable.insertShortcode();
                simpler.modal.show = false;
                return false;
            });
        },

        openDialog: function() {
            // Save selection state before modal steals focus (TinyMCE 6 fix)
            shortcodable.selectionWasCollapsed = tinyMCE.activeEditor.selection.isCollapsed();
            shortcodable.selectionBookmark = tinyMCE.activeEditor.selection.getBookmark(2, true);

            simpler.modal.show = true;
            simpler.modal.title = 'Insert/edit shortcode';
            simpler.modal.closeBtn = false;
            simpler.modal.closeTxt = 'Close';
            simpler.modal.saveBtn = false;
            simpler.modal.saveTxt = 'Insert shortcode';
            simpler.modal.bodyHtml = simpler.spinner;

            // console.log(shortcodable.getCurrentEditorSelectionAsParsedShortcodeData());
            $.post(shortcodable.controller_url, shortcodable.getCurrentEditorSelectionAsParsedShortcodeData(), function(data){
                    // (use the intermediary xhr_buffer element in order to have jQuery parse/activate listeners etc
                    simpler.modal.bodyHtml = $('#xhr_buffer').html(data).html();
                })
                .fail(function(response) { shortcodable.handleErrorResponse(response); })
            ;
        },

        // convenience method for handling error response from Shortcode controller (eg shortcode not implemented correctly)
        handleErrorResponse: function(response) {
            var errorFeedback = response.responseText
                .split('<div class="header info error">').pop()
                .split('<div class="info">').shift()
                || response.responseText;
            simpler.modal.bodyHtml = '<div class="alert alert-danger"><strong>ERROR loading shortcode </strong>(‘'
                + response.statusText + '’):</div>' + errorFeedback;
        },

        // insert shortcode into editor
        insertShortcode: function() {
            let shortcodeData = $('#Form_ShortcodeForm').serializeArray();
            // [ {name: "ShortcodeType", value: "featuredimage"} {name: "title", value: "test"} {name: "SecurityID", value: "..."} ]
            let shortcodeParts = [ '...' ]; // @[0] to be replaced with actual shortcode
            $.each(shortcodeData, function( index, data ){
                if(data.name==="SecurityID") return;
                if(data.name==="ShortcodeType") {
                    shortcodeParts[0] = data.value;
                } else {
                    shortcodeParts.push(`${data.name}="${data.value}"`);
                }
            });
            let shortcode = `[${shortcodeParts.join(' ')}]`;

            if (shortcode.length) {
                // Restore selection bookmark before inserting (TinyMCE 6 fix)
                if (shortcodable.selectionBookmark) {
                    tinyMCE.activeEditor.selection.moveToBookmark(shortcodable.selectionBookmark);
                    // If original selection was collapsed (cursor only), ensure we don't replace adjacent content
                    if (shortcodable.selectionWasCollapsed) {
                        tinyMCE.activeEditor.selection.collapse(false);
                    }
                }
                tinyMCE.activeEditor.selection.setContent(shortcode);
                tinyMCE.activeEditor.undoManager.add(); // make snapshot so setContent is undo-able
                shortcodable.selectionBookmark = null; // clear bookmark

//                this.modifySelection(function(ed){
//                    var shortcodable = tinyMCE.activeEditor.plugins.shortcodable;
//                    ed.replaceContent(shortcode);
//                    var newContent = shortcodable.replaceShortcodesWithPlaceholders(ed.getContent(), ed.getInstance());
//                    ed.setContent(newContent);
//                });
            }
        },

        // @TODO try & 'grow' selection to shortcode and parse
        getCurrentEditorSelectionAsParsedShortcodeData: function() {
            if(tinyMCE.activeEditor.selection.isCollapsed()){
                return;
            }
            // We get 'regular' content (may also be html), as when explicitly requesting 'text' format,
            // placeholder images will be excluded (simply an empty string '' was returned instead)
            // var selectedTxt = tinymce.activeEditor.selection.getContent({format: 'text'}).trim();
            var selectedTxt = tinymce.activeEditor.selection.getContent().trim();
            // console.log('90: '+selectedTxt);
            // Convert a selection containing placeholder image (if any) to 'plain' shortcode
            selectedTxt = shortcodable.placeholdersToShortcodes(selectedTxt, tinyMCE.activeEditor);
            // console.log('93: '+selectedTxt);

            // Check if we're dealing with a shortcode in the first place
            if( selectedTxt.length<=2 || selectedTxt.charAt(0)!=='[' || selectedTxt.slice(-1)!==']') {
                // console.log('97: returning');
                return;
            }

            // Now 'parse' the shortcode attributes by creating it in jQuery as if it's an HTML node;
            var shortcodeData = { 'ShortcodeType': selectedTxt.slice(1,-1).split(' ').shift() };
            $.each($(`<${selectedTxt.slice(1,-1)} />`).get(0).attributes, function(index, attr){
                shortcodeData[attr.name] = attr.value
            });
            // console.log('106: ', shortcodeData);

            return shortcodeData;
        },

        // get a specific attribute value from a shortcode string by its key, eg 'id' from '[myshortcode id="1" other="etc"]
        parseAttributeValue: function (string, key) {
            var attr = new RegExp(key + '=\"([^\"]+)\"', 'g').exec(string);
            // via tinymce.DOM.decode to decode any HTML entities, such as &aring;
            return attr ? tinymce.DOM.decode(attr[1]) : '';
        },

        // Substitute shortcodes with placeholder images
        shortcodesToPlaceholders: function(source, editor) {
            var placeholder_shortcodes = $(editor.targetElm).data('shortcodableplaceholdercodes');
            if (placeholder_shortcodes) {
                return source.replace(/\[([a-z_]+)\s*([^\]]*)\]/gi, function (found, name, params) {
                    if (placeholder_shortcodes.indexOf(name) !== -1) {
                       var id = shortcodable.parseAttributeValue(params, 'id');
                       var src = encodeURI(shortcodable.controller_url + '/placeholder/' + name + '/' + id + '?sc=[' + name + ' ' + params + ']');
                       var el = jQuery('<img/>')
                           .attr('type', 'image/svg+xml')
                           .attr('class', 'sc-placeholder mceItem mceNonEditable')
                           .attr('title', name + ' ' + params)
                           .attr('src', src);
                       // console.log(el.prop('outerHTML'));
                       return el.prop('outerHTML');
                    }
                    return found;
                });
            }
            // else/default:
            return source;
        },

        // Substitutes placeholder images with their shortcode equivalents
        placeholdersToShortcodes: function(source, editor) {
            // find & replace .sc-placeholder images in the html
            var wrappedContent = jQuery('<div>' + source + '</div>');
            wrappedContent.find('.sc-placeholder').each(function () {
                var el = jQuery(this);
                el.replaceWith('[' + tinymce.trim(el.attr('title')) + ']');
            });
            return wrappedContent.html();
        }

    };

})(jQuery);
