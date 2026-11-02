// import _ from 'underscore';

(function() {
    const $ = jQuery;

    shortcodable = {

        controller_url: 'admin/shortcodable',

        initialized: false,

        // Saved selection bookmark (restored before inserting shortcode)
        savedBookmark: null,

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
            // Save selection bookmark before focus moves to modal
            // (only when there's an actual selection, not just a cursor)
            shortcodable.savedBookmark = tinyMCE.activeEditor.selection.isCollapsed()
                ? null
                : tinyMCE.activeEditor.selection.getBookmark(2);

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
                // Restore saved selection (may have been lost when focus moved to modal)
                if (shortcodable.savedBookmark) {
                    tinyMCE.activeEditor.selection.moveToBookmark(shortcodable.savedBookmark);
                    shortcodable.savedBookmark = null; // Clear after use
                }
                tinyMCE.activeEditor.selection.setContent(shortcode);
                tinyMCE.activeEditor.undoManager.add(); // make snapshot so setContent is undo-able

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
            // (no longer in jQuery: $('<...>') builds the element in the live CMS document, where
            // an attribute such as onerror runs and a src is fetched. The selection is text any
            // CMS editor can type, so it is parsed in an inert document instead, see parseAttributes)
            var shortcodeName = selectedTxt.slice(1,-1).split(' ').shift();
            var shortcodeData = { 'ShortcodeType': shortcodeName };
//            $.each($(`<${selectedTxt.slice(1,-1)} />`).get(0).attributes, function(index, attr){
//                shortcodeData[attr.name] = attr.value
//            });
            $.each(shortcodable.parseAttributes(selectedTxt.slice(1,-1).slice(shortcodeName.length)), function(name, value){
                shortcodeData[name] = value;
            });
            // console.log('106: ', shortcodeData);

            return shortcodeData;
        },

        // An HTML document without a browsing context: elements created in it never run event
        // handlers or scripts and never fetch resources. Created once, on first use.
        inertDocument: null,
        getInertDocument: function() {
            if (!shortcodable.inertDocument) {
                shortcodable.inertDocument = document.implementation.createHTMLDocument('');
            }
            return shortcodable.inertDocument;
        },

        // Parse a shortcode's attribute string (everything after the name, eg ' id="1" title=x') into
        // {name: value}. The HTML attribute parser of an inert document does the work, so quoting,
        // entities and (lower-cased) names come out exactly as they did when this went through
        // jQuery, but nothing in the string can act on the CMS document. A fixed tag name keeps the
        // shortcode's own name (which may be 'table', 'tr', ...) out of the parsing.
        parseAttributes: function(attrString) {
            var wrapper = shortcodable.getInertDocument().createElement('div');
            wrapper.innerHTML = '<span ' + attrString + '></span>';
            var attrs = {};
            var el = wrapper.firstChild;
            if (el && el.attributes) {
                $.each(el.attributes, function(index, attr){
                    attrs[attr.name] = attr.value;
                });
            }
            return attrs;
        },

        // get a specific attribute value from a shortcode string by its key, eg 'id' from '[myshortcode id="1" other="etc"]
        parseAttributeValue: function (string, key) {
            var attr = new RegExp(key + '=\"([^\"]+)\"', 'g').exec(string);
            // via tinymce.DOM.decode to decode any HTML entities, such as &aring;
            return attr ? tinymce.DOM.decode(attr[1]) : '';
        },

        // Substitute shortcodes with placeholder images
        shortcodesToPlaceholders: function(source, editor) {
            // Try multiple sources for shortcode placeholder codes:
            // 1. data attribute (traditional server-rendered fields)
            // 2. data-config JSON (React-rendered inline editors)
            var placeholder_shortcodes = $(editor.targetElm).data('shortcodableplaceholdercodes');

            if (!placeholder_shortcodes) {
                // Try parsing from data-config (TinyMCE config JSON)
                var configStr = $(editor.targetElm).attr('data-config');
                if (configStr) {
                    try {
                        var config = JSON.parse(configStr);
                        placeholder_shortcodes = config.shortcodable_placeholder_codes;
                    } catch (e) {
                        console.warn('Could not parse data-config:', e);
                    }
                }
            }

            if (placeholder_shortcodes) {
                // A name is what ShortcodeParser accepts (\w+) and ends at whitespace or ']' (#11:
                // [a-z_]+ followed by \s* stopped a name at its first digit, so [year2] was taken
                // for [year] with an attribute '2' and saved back as [year 2])
//                return source.replace(/\[([a-z_]+)\s*([^\]]*)\]/gi, function (found, name, params) {
                return source.replace(/\[(\w+)(?:\s+([^\]]*))?\]/g, function (found, name, params) {
                    // no attributes: params is undefined, and the shortcode text gets no trailing space
                    params = params || '';
                    var shortcodeText = params ? name + ' ' + params : name;
                    if (placeholder_shortcodes.indexOf(name) !== -1) {
                       var id = shortcodable.parseAttributeValue(params, 'id');
//                       var src = encodeURI(shortcodable.controller_url + '/placeholder/' + name + '/' + id + '?sc=[' + name + ' ' + params + ']');
                       var src = encodeURI(shortcodable.controller_url + '/placeholder/' + name + '/' + id + '?sc=[' + shortcodeText + ']');
                       var el = $('<img/>')
                           .attr('type', 'image/svg+xml')
                           .attr('class', 'sc-placeholder mceItem mceNonEditable')
//                           .attr('title', name + ' ' + params)
                           .attr('title', shortcodeText)
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
            // (in an inert document rather than the live CMS one, so nothing in the content can run
            // or load while it is rewritten; innerHTML directly, as jQuery's .html() may evaluate scripts)
//            var wrappedContent = $('<div>' + source + '</div>');
            var wrapper = shortcodable.getInertDocument().createElement('div');
            wrapper.innerHTML = source;
            var wrappedContent = $(wrapper);
            wrappedContent.find('.sc-placeholder').each(function () {
                var el = $(this);
                el.replaceWith('[' + tinymce.trim(el.attr('title')) + ']');
            });
            return wrappedContent.html();
        }

    };

})();
