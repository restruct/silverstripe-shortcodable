/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "lib/Injector":
/*!***************************!*\
  !*** external "Injector" ***!
  \***************************/
/***/ ((module) => {

module.exports = Injector;

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = React;

/***/ }),

/***/ "react-dom":
/*!***************************!*\
  !*** external "ReactDom" ***!
  \***************************/
/***/ ((module) => {

module.exports = ReactDom;

/***/ }),

/***/ "lib/TinyMCEActionRegistrar":
/*!*****************************************!*\
  !*** external "TinyMCEActionRegistrar" ***!
  \*****************************************/
/***/ ((module) => {

module.exports = TinyMCEActionRegistrar;

/***/ }),

/***/ "i18n":
/*!***********************!*\
  !*** external "i18n" ***!
  \***********************/
/***/ ((module) => {

module.exports = i18n;

/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/***/ ((module) => {

module.exports = jQuery;

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
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
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
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!***********************************************!*\
  !*** ./client/src/js/TinyMCE_shortcodable.js ***!
  \***********************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! i18n */ "i18n");
/* harmony import */ var i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lib_TinyMCEActionRegistrar__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! lib/TinyMCEActionRegistrar */ "lib/TinyMCEActionRegistrar");
/* harmony import */ var lib_TinyMCEActionRegistrar__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lib_TinyMCEActionRegistrar__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react-dom */ "react-dom");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_dom__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var lib_Injector__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! lib/Injector */ "lib/Injector");
/* harmony import */ var lib_Injector__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(lib_Injector__WEBPACK_IMPORTED_MODULE_5__);
/* global tinymce, window */




 //import {createInsertMenu} from 'containers/InsertLinkModal/InsertLinkModal';


var commandName = 'shortcodable';
var modalId = 'insert-link__dialog-wrapper--phone';
var sectionConfigKey = 'SilverStripe\\Admin\\LeftAndMain';
var formName = 'ShortcodeForm';
var FormBuilderModal = (0,lib_Injector__WEBPACK_IMPORTED_MODULE_5__.loadComponent)('FormBuilderModal'); //const InsertShortCodeModal = loadComponent(createInsertMenu(sectionConfigKey, formName));

var plugin = {
  init: function init(editor) {
    editor.addButton(commandName, {
      icon: false,
      text: 'Shortcodes',
      title: 'Insert Shortcode',
      cmd: 'shortcodable',
      'class': 'mce_shortcode',
      tooltip: commandName,
      onclick: function onclick() {
        var field = window.jQuery("#".concat(editor.id)).entwine('ss');
        field.openShortcodeDialog();
      }
    });
    editor.on('dblclick', function (e) {
      editor.execCommand(commandName);
    });
  }
};
jquery__WEBPACK_IMPORTED_MODULE_4___default().entwine('ss', function ($) {
  $('textarea.htmleditor').entwine({
    openShortcodeDialog: function openShortcodeDialog() {
      var dialog = $("#".concat(modalId));

      if (!dialog.length) {
        dialog = $("<div id=\"".concat(modalId, "\" />"));
        $('body').append(dialog);
      }

      dialog.addClass('shortcodable__dialog-wrapper');
      dialog.setElement(this);
      dialog.open();
    },
    getPlaceholderClasses: function getPlaceholderClasses() {
      var classes = $(this).data('placeholderclasses');

      if (classes) {
        return classes.split(',');
      }
    },
    onbeforesubmitform: function onbeforesubmitform(e) {
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
    onchange: function onchange() {
      this.parents('form:first').reloadForm('type', this.val());
    }
  }); // add shortcode controller url to cms-editor-dialogs

  $('#cms-editor-dialogs').entwine({
    onmatch: function onmatch() {
      this.attr('data-url-shortcodeform', 'admin/shortcodable/ShortcodeForm/forTemplate');
    }
  });
  /**
   * Assumes that $('.insert-link__dialog-wrapper').entwine({}); is defined for shared functions
   */

  $("#".concat(modalId)).entwine({
    renderModal: function renderModal(isOpen) {
      var _this = this;

      var handleHide = function handleHide() {
        return _this.close();
      };

      var handleInsert = function handleInsert() {
        return _this.handleInsert.apply(_this, arguments);
      };

      var attrs = this.getOriginalAttributes();
      var selection = tinymce.activeEditor.selection;
      var selectionContent = selection.getContent() || '';
      var tagName = selection.getNode().tagName; //const requireLinkText = tagName !== 'A' && selectionContent.trim() === '';
      // create/update the react component

      react_dom__WEBPACK_IMPORTED_MODULE_3___default().render( /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement(FormBuilderModal, {
        isOpen: isOpen,
        onInsert: handleInsert,
        onClosed: handleHide,
        title: i18n__WEBPACK_IMPORTED_MODULE_0___default()._t('Admin.LINK_PHONE', 'Shortcode'),
        bodyClassName: "modal__dialog",
        className: "shortcodable__dialog-wrapper",
        fileAttributes: attrs,
        identifier: "Admin.InsertShortcodeModal" //requireLinkText={requireLinkText}

      }), this[0]);
    },
    getOriginalAttributes: function getOriginalAttributes() {
      var editor = this.getElement().getEditor();
      var node = $(editor.getSelectedNode());
      var phone = (node.attr('href') || '').replace(/^tel:/, '');
      return {
        Link: phone,
        Description: node.attr('title')
      };
    },
    buildAttributes: function buildAttributes(data) {
      var attributes = this._super(data);

      var href = '';
      var phone = attributes.href.replace(/^tel:/, '');

      if (phone) {
        href = "tel:".concat(phone);
      }

      attributes.href = href;
      delete attributes.target;
      return attributes;
    }
  });
}); // Adds the plugin class to the list of available TinyMCE plugins

tinymce.PluginManager.add(commandName, function (editor) {
  return plugin.init(editor);
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (plugin);
})();

/******/ })()
;