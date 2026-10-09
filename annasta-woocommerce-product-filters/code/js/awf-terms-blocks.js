/**
 * "annasta Product Categories" / "annasta Product Brands" blocks editor (no build step)
 *
 * Premium controls are added through the awf.termsBlocks.displayControls, awf.termsBlocks.termsControls
 * and awf.termsBlocks.emptyTermsControls filters (A_W_F_premium_admin::get_terms_blocks_editor_script()).
 *
 * @since 1.8.5
 */
( function( wp, data ) {

	if( ! wp || ! data ) { return; }

	var el = wp.element.createElement;
	var be = wp.blockEditor;
	var c = wp.components;
	var l = data.labels;

	/* Navigation block (WP 6.5+): its client side registration replaces the server side allowedBlocks */
	wp.hooks.addFilter( 'blocks.registerBlockType', 'a-w-f/terms-blocks-navigation', function( settings, name ) {
		if( 'core/navigation' === name && Array.isArray( settings.allowedBlocks ) ) {
			settings.allowedBlocks = settings.allowedBlocks.concat( Object.keys( data.blocks ) );
		}

		return settings;
	} );

	/**
	 * Editor preview inside the Navigation block: like the core Page List, the block element is a wp-block-navigation__container list,
	 * so its items follow the Navigation layout and gap. The items come from the block renderer (A_W_F_terms_blocks::render_block()).
	 */
	var NavigationPreview = function( p ) {
		var state = wp.element.useState( null );
		var html = state[0];
		var set_html = state[1];
		var request_key = JSON.stringify( [ p.name, p.attributes, p.args ] );

		wp.element.useEffect( function() {
			var active = true;

			var timeout = setTimeout( function() {
				var query = { context: 'edit', attributes: p.attributes };
				Object.keys( p.args ).forEach( function( key ) { query[ key ] = p.args[ key ]; } );

				wp.apiFetch( { path: wp.url.addQueryArgs( '/wp/v2/block-renderer/' + p.name, query ) } ).then( function( response ) {
					if( active ) { set_html( response && response.rendered ? response.rendered : '' ); }
				} ).catch( function() {
					if( active ) { set_html( '' ); }
				} );
			}, 300 );

			return function() {
				active = false;
				clearTimeout( timeout );
			};
		}, [ request_key ] );

		var content = html;
		if( null === html ) {
			content = '<li class="wp-block-navigation-item"><span class="wp-block-navigation-item__content">&hellip;</span></li>';
		} else if( '' === html ) {
			content = '<li class="wp-block-navigation-item"><span class="wp-block-navigation-item__content awf-terms-list-empty">' + wp.escapeHtml.escapeHTML( l.empty ) + '</span></li>';
		}

		var list_props = {};
		Object.keys( p.block_props ).forEach( function( key ) { list_props[ key ] = p.block_props[ key ]; } );
		list_props.dangerouslySetInnerHTML = { __html: content };

		return el( 'ul', list_props );
	};

	/**
	 * Block editor: sidebar settings and preview
	 *
	 * Inside the Navigation block: no List display (switched to Dropdown), no "Collapse hierarchical children" (the Navigation
	 * block controls its own submenus), and the preview in the Navigation markup with its submenu settings (the legacy
	 * openSubmenusOnClick first). Premium: "Exclude Uncategorized" is part of the terms setting; free version: a disabled
	 * "Dynamically adjust hidden items in archives" with the premium tag. Outside of the Navigation block the preview is
	 * rendered anew for each display mode, and the style options (block supports) are applied by the editor to the wrapper.
	 */
	var edit = function( props ) {
		var a = props.attributes;
		var block = data.blocks[ props.name ];

		var set = function( key, parse ) {
			return function( value ) {
				var attributes = {};
				attributes[ key ] = parse ? parse( value ) : value;
				props.setAttributes( attributes );
			};
		};

		var to_int = function( value ) {
			var n = parseInt( value, 10 );
			return ( isNaN( n ) || n < 0 ) ? 0 : n;
		};

		var select = function( key, label, options ) {
			return el( c.SelectControl, { key: key, label: label, value: a[ key ], options: options, onChange: set( key ), __nextHasNoMarginBottom: true, __next40pxDefaultSize: true } );
		};

		var toggle = function( key, label ) {
			return el( c.ToggleControl, { key: key, label: label, checked: !! a[ key ], onChange: set( key ), __nextHasNoMarginBottom: true } );
		};

		var number = function( key, label ) {
			return el( c.TextControl, { key: key, label: label, type: 'number', min: 0, value: String( a[ key ] ), onChange: set( key, to_int ), __nextHasNoMarginBottom: true, __next40pxDefaultSize: true } );
		};

		var context = props.context || {};
		var in_navigation = 'undefined' !== typeof context.showSubmenuIcon;

		wp.element.useEffect( function() {
			if( in_navigation && 'list' === a.display ) { props.setAttributes( { display: 'dropdown' } ); }
		}, [ in_navigation, a.display ] );

		var display_options = [ { label: l.dropdown, value: 'dropdown' }, { label: l.top_level_row, value: 'top_level_row' } ];
		if( ! in_navigation ) { display_options.unshift( { label: l.list, value: 'list' } ); }

		var display_controls = [ select( 'display', l.display, display_options ) ];

		if( 'dropdown' === a.display ) {
			display_controls.push(
				el( c.TextControl, { key: 'label', label: l.label, value: a.label, placeholder: block.label, onChange: set( 'label' ), __nextHasNoMarginBottom: true, __next40pxDefaultSize: true } ),
				select( 'link', l.link, [ { label: l.none, value: 'none' }, { label: l.shop, value: 'shop' } ] ),
				toggle( 'hideIfEmpty', l.hide_if_empty )
			);
		}

		display_controls = display_controls.concat( wp.hooks.applyFilters( 'awf.termsBlocks.displayControls', [], props, block ) );

		var premium_terms_controls = wp.hooks.applyFilters( 'awf.termsBlocks.termsControls', [], props, block );

		var terms_controls = [ number( 'limit', l.limit ) ].concat( premium_terms_controls );

		terms_controls.push( select( 'termsLink', l.terms_link, [ { label: l.archive, value: 'archive' }, { label: l.filtered, value: 'filtered' } ] ) );

		if( block.hierarchical ) {
			terms_controls.push( number( 'depth', l.depth ) );

			if( ! in_navigation ) { terms_controls.push( toggle( 'collapse', l.collapse ) ); }
		}

		if( block.is_categories && 0 === premium_terms_controls.length ) { terms_controls.push( toggle( 'excludeDefault', l.exclude_default ) ); }

		terms_controls.push( toggle( 'showCount', l.show_count ), toggle( 'hideEmpty', l.hide_empty ) );
		var empty_terms_controls = wp.hooks.applyFilters( 'awf.termsBlocks.emptyTermsControls', [], props, block );

		if( 0 === empty_terms_controls.length ) {
			empty_terms_controls.push( el( c.ToggleControl, {
				key: 'dynamicPremium',
				label: el( wp.element.Fragment, null,
					l.dynamic_premium + ' ',
					el( 'span', { style: { display: 'inline-block', padding: '3px', lineHeight: '12px', fontSize: '10px', fontWeight: 200, color: '#fff', background: '#d00909', borderRadius: '2px', verticalAlign: 'middle' } }, l.premium )
				),
				checked: false,
				disabled: true,
				onChange: function() {},
				__nextHasNoMarginBottom: true
			} ) );
		}

		terms_controls = terms_controls.concat( empty_terms_controls );
		terms_controls.push( select( 'orderby', l.orderby, [ { label: l.default, value: 'default' }, { label: l.name, value: 'name' } ] ) );

		var preview_args = {};

		if( in_navigation ) {
			preview_args = {
				awf_navigation_preview: 1,
				awf_submenu_icon: context.showSubmenuIcon ? 1 : 0,
				awf_submenu_visibility: ( 'undefined' !== typeof context.openSubmenusOnClick && null !== context.openSubmenusOnClick ) ? ( context.openSubmenusOnClick ? 'click' : 'hover' ) : ( context.submenuVisibility || 'hover' )
			};
		}

		var block_props = be.useBlockProps( in_navigation ? { className: 'wp-block-navigation__container awf-terms-navigation-preview' } : {} );

		var inspector = el( be.InspectorControls, null,
			el( c.PanelBody, { title: l.display_panel }, display_controls ),
			el( c.PanelBody, { title: l.terms_panel }, terms_controls )
		);

		if( in_navigation ) {
			return el( wp.element.Fragment, null,
				inspector,
				el( NavigationPreview, { name: props.name, attributes: a, args: preview_args, block_props: block_props } )
			);
		}

		return el( 'div', block_props,
			inspector,
			el( wp.serverSideRender, {
				key: a.display,
				block: props.name,
				attributes: a,
				skipBlockSupportAttributes: true,
				EmptyResponsePlaceholder: function() { return el( 'p', { className: 'awf-terms-list-empty' }, l.empty ); }
			} )
		);
	};

	Object.keys( data.blocks ).forEach( function( name ) {
		wp.blocks.registerBlockType( name, {
			edit: edit,
			save: function() { return null; }
		} );
	} );

} )( window.wp, window.awf_terms_blocks_data );
