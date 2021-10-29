/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./client/src/js/shortcodable.js":
/*!***************************************!*\
  !*** ./client/src/js/shortcodable.js ***!
  \***************************************/
/***/ (() => {

(function ($) {
  shortcodable = {
    controller_url: 'admin/shortcodable',
    init: function init() {
      // handle change on shortcode-type field
      $('body').on('change', 'select.shortcode-type', function () {
        $.post(shortcodable.controller_url, $(this).parents('form').serializeArray(), function (data) {
          // (use the intermediary xhr_buffer element in order to have jQuery parse/activate listeners etc
          simpler.modal.bodyHtml = $('#xhr_buffer').html(data).html();
        });
      }); // shortcode form submit button handler

      $('body').on('submit', '#Form_ShortcodeForm', function (event) {
        event.preventDefault();
        shortcodable.insertShortcode();
        simpler.modal.show = false;
        return false;
      });
    },
    openDialog: function openDialog() {
      simpler.modal.show = true;
      simpler.modal.title = 'Insert/edit shortcode';
      simpler.modal.closeBtn = false;
      simpler.modal.closeTxt = 'Close';
      simpler.modal.saveBtn = false;
      simpler.modal.saveTxt = 'Insert shortcode';
      simpler.modal.bodyHtml = simpler.spinner;
      console.log(shortcodable.getCurrentEditorSelectionAsParsedShortcodeData());
      $.post(shortcodable.controller_url, shortcodable.getCurrentEditorSelectionAsParsedShortcodeData(), function (data) {
        // (use the intermediary xhr_buffer element in order to have jQuery parse/activate listeners etc
        simpler.modal.bodyHtml = $('#xhr_buffer').html(data).html();
      });
    },
    // insert shortcode into editor
    insertShortcode: function insertShortcode() {
      var shortcodeData = $('#Form_ShortcodeForm').serializeArray(); // [ {name: "ShortcodeType", value: "featuredimage"} {name: "title", value: "test"} {name: "SecurityID", value: "..."} ]

      var shortcodeParts = ['...']; // @[0] to be replaced with actual shortcode

      $.each(shortcodeData, function (index, data) {
        if (data.name === "SecurityID") return;

        if (data.name === "ShortcodeType") {
          shortcodeParts[0] = data.value;
        } else {
          shortcodeParts.push("".concat(data.name, "=\"").concat(data.value, "\""));
        }
      });
      var shortcode = "[".concat(shortcodeParts.join(' '), "]");

      if (shortcode.length) {
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
    getCurrentEditorSelectionAsParsedShortcodeData: function getCurrentEditorSelectionAsParsedShortcodeData() {
      if (tinyMCE.activeEditor.selection.isCollapsed()) {
        return;
      } // We get 'regular' content (may also be html), as when explicitly requesting 'text' format,
      // placeholder images will be excluded (simply an empty string '' was returned instead)
      // var selectedTxt = tinymce.activeEditor.selection.getContent({format: 'text'}).trim();


      var selectedTxt = tinymce.activeEditor.selection.getContent().trim();
      console.log('78: ' + selectedTxt); // Convert a selection containing placeholder image (if any) to 'plain' shortcode

      selectedTxt = shortcodable.placeholdersToShortcodes(selectedTxt, tinyMCE.activeEditor);
      console.log('81: ' + selectedTxt); // Check if we're dealing with a shortcode in the first place

      if (selectedTxt.length <= 2 || selectedTxt.charAt(0) !== '[' || selectedTxt.slice(-1) !== ']') {
        console.log('85: returning');
        return;
      } // Now 'parse' the shortcode attributes by creating it in jQuery as if it's an HTML node;


      var shortcodeData = {
        'ShortcodeType': selectedTxt.slice(1, -1).split(' ').shift()
      };
      $.each($("<".concat(selectedTxt.slice(1, -1), " />")).get(0).attributes, function (index, attr) {
        shortcodeData[attr.name] = attr.value;
      });
      console.log('94: ', shortcodeData);
      return shortcodeData;
    },
    // get a specific attribute value from a shortcode string by its key, eg 'id' from '[myshortcode id="1" other="etc"]
    parseAttributeValue: function parseAttributeValue(string, key) {
      var attr = new RegExp(key + '=\"([^\"]+)\"', 'g').exec(string); // via tinymce.DOM.decode to decode any HTML entities, such as &aring;

      return attr ? tinymce.DOM.decode(attr[1]) : '';
    },
    // Substitute shortcodes with placeholder images
    shortcodesToPlaceholders: function shortcodesToPlaceholders(source, editor) {
      var placeholder_shortcodes = $(editor.targetElm).data('shortcodableplaceholdercodes');

      if (placeholder_shortcodes) {
        return source.replace(/\[([a-z_]+)\s*([^\]]*)\]/gi, function (found, name, params) {
          if (placeholder_shortcodes.indexOf(name) !== -1) {
            var id = shortcodable.parseAttributeValue(params, 'id');
            var src = encodeURI(shortcodable.controller_url + '/placeholder/' + name + '/' + id + '?sc=[' + name + ' ' + params + ']');
            var el = jQuery('<img/>').attr('type', 'image/svg+xml').attr('class', 'sc-placeholder mceItem mceNonEditable').attr('title', name + ' ' + params).attr('src', src);
            console.log(el.prop('outerHTML'));
            return el.prop('outerHTML');
          }

          return found;
        });
      } // else/default:


      return source;
    },
    // Substitutes placeholder images with their shortcode equivalents
    placeholdersToShortcodes: function placeholdersToShortcodes(source, editor) {
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

/***/ }),

/***/ "./client/src/styles/shortcodable.scss":
/*!*********************************************!*\
  !*** ./client/src/styles/shortcodable.scss ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					result = fn();
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/js/shortcodable": 0,
/******/ 			"styles/shortcodable": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			for(moduleId in moreModules) {
/******/ 				if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 					__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 				}
/******/ 			}
/******/ 			if(runtime) var result = runtime(__webpack_require__);
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkIds[i]] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunkbase_package_json"] = self["webpackChunkbase_package_json"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["styles/shortcodable"], () => (__webpack_require__("./client/src/js/shortcodable.js")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["styles/shortcodable"], () => (__webpack_require__("./client/src/styles/shortcodable.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;