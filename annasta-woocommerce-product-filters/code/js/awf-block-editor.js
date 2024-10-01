/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ (function(module) {

module.exports = window["wp"]["element"];

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
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

const {
  __
} = wp.i18n;
const {
  createBlock,
  registerBlockType
} = wp.blocks;
const {
  InspectorControls,
  BlockControls,
  useBlockProps
} = wp.blockEditor;
const {
  PanelBody,
  ToolbarGroup,
  ToolbarButton,
  SelectControl,
  ToggleControl,
  Disabled,
  Placeholder,
  Fragment,
  Button,
  Spinner
} = wp.components;
const {
  serverSideRender: ServerSideRender
} = wp;
const {
  useState,
  useEffect
} = wp.element;
const annasta_presets = awf_block_data.presets;

if ('undefined' !== typeof wp && 'hooks' in wp && 'addAction' in wp.hooks) {
  wp.hooks.addAction('awf-block-preview-loaded', 'a_w_f_namespace', function (attributes) {
    jQuery('.awf-preset-' + attributes.annastaPreset + '-wrapper .awf-range-slider-container').each(function (i, el) {
      if ('undefined' === typeof el.noUiSlider) {
        a_w_f.build_range_slider(el);
      }
    });

    if ('premium' in a_w_f) {
      jQuery('.awf-preset-' + attributes.annastaPreset + '-wrapper .awf-taxonomy-range-slider-container').each(function (i, el) {
        if ('undefined' === typeof el.noUiSlider) {
          a_w_f.build_taxonomy_range_slider(el);
        }
      });
    }
  });
}

registerBlockType('a-w-f/awf-block', {
  apiVersion: 2,
  title: __('annasta WooCommerce Filters', 'annasta-filters'),
  description: __('Display annasta Filters preset', 'annasta-filters'),
  keywords: [__('filters', 'annasta-filters'), __('product filters', 'annasta-filters'), __('Woocommerce', 'annasta-filters'), __('Woocommerce filters', 'annasta-filters')],
  icon: 'filter',
  category: 'widgets',
  attributes: {
    blockID: {
      type: 'string'
    },
    annastaPreset: {
      type: 'string'
    },
    blockEditMode: {
      type: 'boolean',
      default: true
    }
  },
  transforms: {
    from: [{
      type: 'block',
      blocks: ['core/legacy-widget'],
      isMatch: _ref => {
        let {
          idBase,
          instance
        } = _ref;
        return idBase === 'awf_widget' && !!(instance !== null && instance !== void 0 && instance.raw);
      },
      transform: _ref2 => {
        var _instance$raw;

        let {
          instance
        } = _ref2;
        return createBlock('a-w-f/awf-block', {
          annastaPreset: instance === null || instance === void 0 ? void 0 : (_instance$raw = instance.raw) === null || _instance$raw === void 0 ? void 0 : _instance$raw.preset_id,
          blockEditMode: true
        });
      }
    }]
  },
  example: {
    attributes: {}
  },

  edit(props) {
    if ('undefined' === props.attributes.blockEditMode) {
      props.attributes.blockEditMode = true;
    }

    if (!props.attributes.blockID) {
      props.setAttributes({
        blockID: props.clientId
      });
    }

    const updatePresetId = new_preset_id => {
      props.setAttributes({
        annastaPreset: new_preset_id.toString()
      });
    };

    if (!props.attributes.annastaPreset) {
      var preset_id = 1 < annasta_presets.length ? annasta_presets[1]['value'] : 0;
      updatePresetId(preset_id);
    }

    const [isEditing, setIsEditing] = useState(props.attributes.blockEditMode);
    const blockProps = useBlockProps({
      className: 'awf-block-wrapper'
    });

    const togglePreview = () => {
      setIsEditing(!isEditing);
      props.setAttributes({
        blockEditMode: !isEditing
      });
    };

    const getBlockControls = () => {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(BlockControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(ToolbarGroup, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(ToolbarButton, {
        icon: 'visibility',
        label: __('Preview', 'annasta-filters'),
        onClick: togglePreview,
        isActive: !isEditing
      }, __('Preview', 'annasta-filters'))));
    };

    const renderEditMode = () => {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "awf-block-edit"
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
        className: "awf-block-edit-title"
      }, __('annasta WooCommerce Filters', 'annasta-filters')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "awf-block-edit-container"
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
        value: props.attributes.annastaPreset,
        options: Object.values(annasta_presets),
        onChange: updatePresetId
      })));
    };

    const triggerPreviewLoader = attributes => {
      return _ref3 => {
        let {
          children,
          showLoader
        } = _ref3;
        useEffect(() => {
          return () => {
            wp.hooks.doAction('awf-block-preview-loaded', attributes);
          };
        });
        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          style: {
            position: 'relative'
          }
        }, showLoader && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          style: {
            position: 'absolute',
            top: '50%',
            left: '50%',
            marginTop: '-9px',
            marginLeft: '-9px'
          }
        }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Spinner, null)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          style: {
            opacity: showLoader ? '0.3' : 1
          }
        }, children));
      };
    };

    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", blockProps, getBlockControls(), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(InspectorControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
      className: "awf-block-panel"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
      label: __('Filters Preset', 'annasta-filters'),
      value: props.attributes.annastaPreset,
      options: Object.values(annasta_presets),
      onChange: updatePresetId
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(ToggleControl, {
      style: {
        marginTop: '50px'
      },
      label: __('Preview', 'annasta-filters'),
      checked: !props.attributes.blockEditMode,
      onChange: togglePreview
    }))), isEditing ? renderEditMode() : (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Disabled, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(ServerSideRender, {
      block: "a-w-f/awf-block",
      attributes: {
        blockID: props.attributes.blockID,
        annastaPreset: props.attributes.annastaPreset
      },
      urlQueryArgs: {
        'awf-block-preview': 1
      },
      LoadingResponsePlaceholder: triggerPreviewLoader(props.attributes)
    })))];
  },

  save() {
    return null;
  }

});
}();
/******/ })()
;