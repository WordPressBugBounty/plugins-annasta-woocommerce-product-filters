<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_terms_blocks' ) ) {

	/**
	 * "annasta Product Categories" / "annasta Product Brands" blocks and shortcodes: terms lists (A_W_F_terms_list), the blocks also inside the Navigation block
	 *
	 * Inside the Navigation block (WP 6.5+) the items use the core Page List markup, colors and submenu settings.
	 * Premium editor controls are added by A_W_F_premium_admin::get_terms_blocks_editor_script().
	 *
	 * @since 1.8.5
	 */
	class A_W_F_terms_blocks {

		/** Block names by taxonomy */
		protected $block_names = array(
			'product_cat'   => 'a-w-f/product-categories',
			'product_brand' => 'a-w-f/product-brands',
		);

		protected $block_folders = array(
			'product_cat'   => 'product-categories',
			'product_brand' => 'product-brands',
		);

		protected $shortcode_names = array(
			'product_cat'   => 'annasta_product_categories',
			'product_brand' => 'annasta_product_brands',
		);

		public function __construct() {
			add_action( 'init', array( $this, 'register_blocks' ), 20 );
			add_action( 'init', array( $this, 'register_shortcodes' ), 20 );
			add_filter( 'register_block_type_args', array( $this, 'add_to_navigation_block' ), 10, 2 );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_data' ) );
		}

		/* Block name => taxonomy, for the taxonomies found on the installation */
		protected function get_blocks() {
			$blocks = array();

			foreach( A_W_F_terms_list::get_taxonomies() as $taxonomy => $label ) {
				$blocks[$this->block_names[$taxonomy]] = $taxonomy;
			}

			return $blocks;
		}

		public function register_blocks() {

			wp_register_script( 'awf-terms-blocks-editor', A_W_F_PLUGIN_URL . '/code/js/awf-terms-blocks.js', array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-hooks', 'wp-server-side-render', 'wp-api-fetch', 'wp-url', 'wp-escape-html' ), A_W_F::$plugin_version );

			foreach( $this->get_blocks() as $taxonomy ) {
				register_block_type( A_W_F_PLUGIN_PATH . 'blocks/' . $this->block_folders[$taxonomy], array(
					'render_callback' => function( $attributes, $content, $block ) use ( $taxonomy ) {
						return $this->render_block( $taxonomy, $attributes, $block );
					},
				) );
			}
		}

		/* Server side list of the Navigation block children (REST block types); the editor gets the blocks from the awf-terms-blocks.js filter */
		public function add_to_navigation_block( $args, $block_name ) {
			if( 'core/navigation' === $block_name && isset( $args['allowed_blocks'] ) && is_array( $args['allowed_blocks'] ) ) {
				$args['allowed_blocks'] = array_values( array_unique( array_merge( $args['allowed_blocks'], array_values( $this->block_names ) ) ) );
			}

			return $args;
		}

		public function enqueue_editor_data() {

			$taxonomies = A_W_F_terms_list::get_taxonomies();
			$blocks = array();

			foreach( $this->get_blocks() as $block_name => $taxonomy ) {
				$blocks[$block_name] = array(
					'taxonomy'      => $taxonomy,
					'label'         => $taxonomies[$taxonomy],
					'hierarchical'  => is_taxonomy_hierarchical( $taxonomy ),
					'is_categories' => 'product_cat' === $taxonomy,
				);
			}

			wp_localize_script( 'awf-terms-blocks-editor', 'awf_terms_blocks_data', array(
				'blocks' => $blocks,
				'labels' => array(
					'display_panel'   => __( 'Display', 'annasta-filters' ),
					'terms_panel'     => __( 'Terms list', 'annasta-filters' ),
					'display'         => __( 'Display mode', 'annasta-filters' ),
					'list'            => __( 'List', 'annasta-filters' ),
					'dropdown'        => __( 'Dropdown', 'annasta-filters' ),
					'top_level_row'   => __( 'Row of top level items', 'annasta-filters' ),
					'collapse'        => __( 'Collapse hierarchical children', 'annasta-filters' ),
					'label'           => __( 'Dropdown label', 'annasta-filters' ),
					'link'            => __( 'Dropdown label link', 'annasta-filters' ),
					'shop'            => __( 'Shop page', 'annasta-filters' ),
					'none'            => __( 'No link', 'annasta-filters' ),
					'hide_if_empty'   => __( 'Hide dropdown when it has no terms', 'annasta-filters' ),
					'limit'           => __( 'Top level terms limit (0 = all)', 'annasta-filters' ),
					'terms_link'      => __( 'Term links', 'annasta-filters' ),
					'archive'         => __( 'Term archive', 'annasta-filters' ),
					'filtered'        => __( 'Filtered shop page', 'annasta-filters' ),
					'depth'           => __( 'Hierarchical levels limit (0 = all)', 'annasta-filters' ),
					'exclude_default' => __( 'Exclude Uncategorized', 'annasta-filters' ),
					'show_count'      => __( 'Show products count', 'annasta-filters' ),
					'hide_empty'      => __( 'Hide empty terms', 'annasta-filters' ),
					'dynamic_premium' => __( 'Dynamically adjust hidden items in archives', 'annasta-filters' ),
					'premium'         => __( 'premium', 'annasta-filters' ),
					'orderby'         => __( 'Order', 'annasta-filters' ),
					'default'         => __( 'Default', 'annasta-filters' ),
					'name'            => __( 'Name', 'annasta-filters' ),
					'empty'           => __( 'No terms to display.', 'annasta-filters' ),
				),
			) );

			if( ! is_null( A_W_F::$admin ) ) {
				$script = A_W_F::$admin->get_terms_blocks_editor_script( $blocks );
				if( ! empty( $script ) ) { wp_add_inline_script( 'awf-terms-blocks-editor', $script, 'before' ); }
			}
		}

		/* Block attributes as terms list settings (the checkbox settings are set when their attribute is present) */
		protected function get_settings( $attributes ) {

			$data = array(
				'display'    => isset( $attributes['display'] ) ? $attributes['display'] : 'list',
				'link'       => isset( $attributes['link'] ) ? $attributes['link'] : 'none',
				'terms_link' => isset( $attributes['termsLink'] ) ? $attributes['termsLink'] : 'archive',
				'orderby'    => isset( $attributes['orderby'] ) ? $attributes['orderby'] : 'default',
				'limit'      => isset( $attributes['limit'] ) ? $attributes['limit'] : 0,
				'depth'      => isset( $attributes['depth'] ) ? $attributes['depth'] : 0,
				'terms_mode' => isset( $attributes['termsMode'] ) ? $attributes['termsMode'] : 'all',
				'terms'      => isset( $attributes['terms'] ) ? (array) $attributes['terms'] : array(),
				'columns'    => isset( $attributes['columns'] ) ? $attributes['columns'] : 1,
			);

			$checkboxes = array(
				'hideIfEmpty'    => 'hide_if_empty',
				'collapse'       => 'collapse',
				'excludeDefault' => 'exclude_default',
				'showCount'      => 'show_count',
				'hideEmpty'      => 'hide_empty',
				'dynamic'        => 'dynamic',
				'dimEmpty'       => 'dim_empty',
			);

			foreach( $checkboxes as $attribute => $setting ) {
				if( ! empty( $attributes[$attribute] ) ) { $data[$setting] = 'yes'; }
			}

			$settings = A_W_F_terms_list::sanitize_settings( $data, A_W_F_terms_list::get_default_settings() );

			$front_class = get_class( A_W_F::$front );

			return $front_class::sanitize_terms_list_premium_settings( $settings, $data );
		}

		/* Shortcodes */

		public function register_shortcodes() {
			foreach( A_W_F_terms_list::get_taxonomies() as $taxonomy => $label ) {
				add_shortcode( $this->shortcode_names[$taxonomy], function( $atts ) use ( $taxonomy ) {
					return $this->render_shortcode( $taxonomy, $atts );
				} );
			}
		}

		/**
		 * [annasta_product_categories] / [annasta_product_brands]: the block settings as shortcode attributes, rendered as the block outside of the Navigation block
		 *
		 * Attributes: display (list / dropdown / top_level_row), label, label_link (none / shop), hide_if_empty, limit, terms_link (archive / filtered),
		 * levels, collapse, exclude_uncategorized, show_count, hide_empty, orderby (default / name), class; premium: columns, terms_mode (all / include / exclude),
		 * terms (ids or slugs), dynamic, dim_empty. Yes / no attributes take "yes" or "no".
		 */
		public function render_shortcode( $taxonomy, $atts ) {

			$atts = shortcode_atts( array(
				'display'               => 'list',
				'label'                 => '',
				'label_link'            => 'none',
				'hide_if_empty'         => 'no',
				'limit'                 => 0,
				'terms_link'            => 'archive',
				'levels'                => 0,
				'collapse'              => 'yes',
				'exclude_uncategorized' => 'yes',
				'show_count'            => 'no',
				'hide_empty'            => 'yes',
				'orderby'               => 'default',
				'columns'               => 1,
				'terms_mode'            => 'all',
				'terms'                 => '',
				'dynamic'               => 'no',
				'dim_empty'             => 'no',
				'class'                 => '',
			), $atts, $this->shortcode_names[$taxonomy] );

			$yes = function( $value ) { return 'yes' === strtolower( trim( (string) $value ) ); };

			$attributes = array(
				'display'        => $atts['display'],
				'collapse'       => $yes( $atts['collapse'] ),
				'label'          => sanitize_text_field( $atts['label'] ),
				'link'           => $atts['label_link'],
				'hideIfEmpty'    => $yes( $atts['hide_if_empty'] ),
				'limit'          => $atts['limit'],
				'termsLink'      => $atts['terms_link'],
				'depth'          => $atts['levels'],
				'excludeDefault' => $yes( $atts['exclude_uncategorized'] ),
				'showCount'      => $yes( $atts['show_count'] ),
				'hideEmpty'      => $yes( $atts['hide_empty'] ),
				'orderby'        => $atts['orderby'],
				'columns'        => $atts['columns'],
				'termsMode'      => $atts['terms_mode'],
				'terms'          => $this->get_shortcode_term_ids( $atts['terms'], $taxonomy ),
				'dynamic'        => $yes( $atts['dynamic'] ),
				'dimEmpty'       => $yes( $atts['dim_empty'] ),
			);

			$classes = array( 'awf-terms-list-shortcode', 'awf-' . str_replace( '_', '-', $this->shortcode_names[$taxonomy] ) );
			foreach( explode( ' ', (string) $atts['class'] ) as $class ) {
				$class = sanitize_html_class( $class );
				if( '' !== $class ) { $classes[] = $class; }
			}

			return $this->render_block( $taxonomy, $attributes, null, $classes );
		}

		/* Comma separated term ids or slugs as default language term ids (the saved terms lists are in the default language) */
		protected function get_shortcode_term_ids( $terms, $taxonomy ) {

			$ids = array();
			if( '' === trim( (string) $terms ) ) { return $ids; }

			$default_language = class_exists( 'SitePress' ) ? apply_filters( 'wpml_default_language', null ) : null;

			foreach( explode( ',', (string) $terms ) as $term ) {
				$term = trim( $term );
				if( '' === $term ) { continue; }

				if( ctype_digit( $term ) ) {
					$id = (int) $term;
				} else {
					$term_object = get_term_by( 'slug', sanitize_title( $term ), $taxonomy );
					if( ! $term_object ) { continue; }
					$id = (int) $term_object->term_id;
				}

				if( $default_language ) { $id = (int) apply_filters( 'wpml_object_id', $id, $taxonomy, true, $default_language ); }

				$ids[] = $id;
			}

			return $ids;
		}

		/**
		 * Block / shortcode output: Navigation block items, or the standalone list
		 *
		 * Inside the Navigation block the List display is shown as a Dropdown. The editor preview inside the Navigation block
		 * (awf-terms-blocks.js NavigationPreview, where the list element is the block's own) gets the submenu settings in the
		 * block renderer request.
		 *
		 * @param string $taxonomy
		 * @param array $attributes Block attributes
		 * @param WP_Block|null $block
		 * @param array|null $shortcode_classes Shortcodes: classes of the list (blocks use get_block_wrapper_attributes())
		 * @return string
		 */
		public function render_block( $taxonomy, $attributes, $block, $shortcode_classes = null ) {

			if( ! ( A_W_F::$front instanceof A_W_F_frontend ) ) { return ''; }

			$settings = $this->get_settings( $attributes );

			$navigation_preview = is_null( $shortcode_classes ) && A_W_F::is_rest_request() && ! empty( $_GET['awf_navigation_preview'] );
			$in_navigation = $navigation_preview || ( $block instanceof WP_Block && array_key_exists( 'showSubmenuIcon', $block->context ) );

			if( $in_navigation && 'list' === $settings['display'] ) { $settings['display'] = 'dropdown'; }

			$tree = A_W_F_terms_list::get_tree( $taxonomy, $settings );

			if( empty( $tree['nodes'] ) && ( 'dropdown' !== $settings['display'] || 'yes' === $settings['hide_if_empty'] ) ) { return ''; }

			$taxonomies = A_W_F_terms_list::get_taxonomies();
			$label = isset( $attributes['label'] ) ? trim( $attributes['label'] ) : '';

			$args = array(
				'settings'          => $settings,
				'label'             => '' === $label ? $taxonomies[$taxonomy] : $label,
				'label_url'         => 'shop' === $settings['link'] ? wc_get_page_permalink( 'shop' ) : '',
				'columns'           => A_W_F::$front->get_terms_list_columns( $settings ),
				'class'             => 'wp-block-' . str_replace( '/', '-', $this->block_names[$taxonomy] ) . ( empty( $attributes['className'] ) ? '' : ' ' . $attributes['className'] ),
				'hierarchical'      => is_taxonomy_hierarchical( $taxonomy ),
				'shortcode_classes' => $shortcode_classes,
			);

			if( 1 < $args['columns'] && 'top_level_row' !== $settings['display'] ) { A_W_F::$front->enqueue_terms_list_css(); }

			if( $in_navigation && ! $navigation_preview ) {
				return $this->render_navigation_items( $tree, $args, $block->context );
			}

			if( $navigation_preview ) {
				$context = array(
					'showSubmenuIcon'   => ! empty( $_GET['awf_submenu_icon'] ),
					'submenuVisibility' => isset( $_GET['awf_submenu_visibility'] ) ? sanitize_key( wp_unslash( $_GET['awf_submenu_visibility'] ) ) : 'hover',
				);

				return $this->render_navigation_items( $tree, $args, $context );
			}

			return $this->render_list( $tree, $args );
		}

		/* Navigation block */

		/**
		 * Navigation block children: Page List markup (wp-includes/blocks/page-list.php), with the submenu directives
		 * the Navigation block only adds to its own submenus and page lists (block_core_navigation_add_directives_to_submenu())
		 */
		protected function render_navigation_items( $tree, $args, $context ) {

			$nav = array(
				'settings'    => $args['settings'],
				'visibility'  => $this->get_submenu_visibility( $context ),
				'show_icon'   => ! empty( $context['showSubmenuIcon'] ),
				'colors'      => function_exists( 'block_core_page_list_build_css_colors' ) ? block_core_page_list_build_css_colors( array(), $context ) : array(),
				'interactive' => false,
			);

			if( 'top_level_row' === $args['settings']['display'] ) {
				$html = $this->render_navigation_nodes( $tree['nodes'], $nav, 0, $args['class'] );

			} else {
				$classes = array( $args['class'], 'awf-menu-terms' );
				if( 1 < $args['columns'] ) { $classes[] = 'awf-menu-columns-' . $args['columns']; }
				if( $tree['current'] ) { $classes[] = 'current-menu-ancestor'; }

				$html = $this->render_navigation_item( array(
					'title'         => esc_html( $args['label'] ),
					'url'           => $args['label_url'],
					'classes'       => $classes,
					'current'       => false,
					'children_html' => $this->render_navigation_nodes( $tree['nodes'], $nav, 1 ),
				), $nav, 0 );
			}

			if( $nav['interactive'] && function_exists( 'wp_enqueue_script_module' ) ) {
				wp_enqueue_script_module( '@wordpress/block-library/navigation/view' );
			}

			return $html;
		}

		/* "hover", "click" or "always"; the legacy openSubmenusOnClick first, as in block_core_page_list_get_submenu_visibility() */
		protected function get_submenu_visibility( $context ) {
			if( isset( $context['openSubmenusOnClick'] ) ) { return empty( $context['openSubmenusOnClick'] ) ? 'hover' : 'click'; }
			if( isset( $context['submenuVisibility'] ) && in_array( $context['submenuVisibility'], array( 'hover', 'click', 'always' ), true ) ) { return $context['submenuVisibility']; }

			return 'hover';
		}

		protected function render_navigation_nodes( $nodes, &$nav, $depth, $top_class = '' ) {

			$html = '';

			foreach( $nodes as $node ) {
				$html .= $this->render_navigation_item( $this->get_node_item( $node, $nav['settings'], $depth, $top_class ), $nav, $depth, $node );
			}

			return $html;
		}

		/* The item of a terms tree node, see render_navigation_item() (no dimmed items in the row of top level items) */
		protected function get_node_item( $node, $settings, $depth, $top_class = '' ) {

			$term = $node['term'];
			$classes = array( 'awf-menu-term', 'awf-menu-term-' . $term->term_id );
			if( 0 === $depth && '' !== $top_class ) { $classes[] = $top_class; }

			$title = esc_html( $term->name );
			if( 'yes' === $settings['show_count'] ) { $title .= ' <span class="awf-menu-term-count">(' . esc_html( $node['count'] ) . ')</span>'; }

			if( 0 === $node['count'] ) { $classes[] = 'awf-menu-term-empty'; }

			if( $node['dim'] && ! ( 0 === $depth && 'top_level_row' === $settings['display'] ) ) {
				$classes[] = 'awf-menu-term-dim';
				A_W_F::$front->enqueue_terms_list_css();
			}

			if( $node['current'] ) { $classes[] = 'current-menu-item'; }
			if( $node['children_current'] ) { $classes[] = 'current-menu-ancestor'; }

			return array(
				'title'         => $title,
				'url'           => $node['url'],
				'classes'       => $classes,
				'current'       => $node['current'],
				'children_html' => '',
			);
		}

		/**
		 * A Navigation block item; submenu items get the overlay colors
		 *
		 * @param array $item 'title' (HTML), 'url', 'classes', 'current', 'children_html' (used when there's no $node)
		 * @param array $nav Navigation context, 'interactive' set when submenus are rendered
		 * @param int $depth
		 * @param array|null $node Terms tree node, for its children
		 */
		protected function render_navigation_item( $item, &$nav, $depth, $node = null ) {

			if( ! is_null( $node ) && ! empty( $node['children'] ) ) {
				$item['children_html'] = $this->render_navigation_nodes( $node['children'], $nav, $depth + 1 );
			}

			$has_children = '' !== $item['children_html'];
			$classes = array_merge( array( 'wp-block-navigation-item' ), $item['classes'] );
			$style = '';
			$directives = '';

			if( $has_children ) {
				$classes[] = 'has-child';

				if( 'click' === $nav['visibility'] ) {
					$classes[] = 'open-on-click';
				} elseif( 'hover' === $nav['visibility'] && $nav['show_icon'] ) {
					$classes[] = 'open-on-hover-click';
				} elseif( 'always' === $nav['visibility'] ) {
					$classes[] = 'open-always';
				}

				$directives = ' data-wp-interactive="core/navigation" data-wp-context="' . esc_attr( '{ "submenuOpenedBy": { "click": false, "hover": false, "focus": false }, "type": "submenu", "modal": null, "previousFocus": null }' ) . '" data-wp-watch="callbacks.initMenu" data-wp-on--focusout="actions.handleMenuFocusout" data-wp-on--keydown="actions.handleMenuKeydown" tabindex="-1"';

				if( 'hover' === $nav['visibility'] ) {
					$directives .= ' data-wp-on--pointerenter="actions.openMenuOnHover" data-wp-on--pointerleave="actions.closeMenuOnHover"';
				}

				$nav['interactive'] = true;
			}

			if( 0 < $depth && ! empty( $nav['colors']['overlay_css_classes'] ) ) { $classes = array_merge( $classes, $nav['colors']['overlay_css_classes'] ); }
			if( 0 < $depth && ! empty( $nav['colors']['overlay_inline_styles'] ) ) { $style = ' style="' . esc_attr( $nav['colors']['overlay_inline_styles'] ) . '"'; }

			$icon = $this->get_submenu_icon();
			$toggle_directives = ' data-wp-on--click="actions.toggleMenuOnClick" data-wp-bind--aria-expanded="state.isSubmenuOpen"';
			/* translators: Accessibility text. %s: Parent item title. */
			$aria_label = sprintf( __( '%s submenu', 'annasta-filters' ), wp_strip_all_tags( $item['title'] ) );

			$html = '<li class="' . esc_attr( implode( ' ', array_filter( $classes ) ) ) . '"' . $style . $directives . '>';

			if( $has_children && 'click' === $nav['visibility'] ) {
				$html .= '<button aria-label="' . esc_attr( $aria_label ) . '" class="wp-block-navigation-item__content wp-block-navigation-submenu__toggle" aria-expanded="false"' . $toggle_directives . '>' . $item['title'] . '</button>';
				$html .= '<span class="wp-block-page-list__submenu-icon wp-block-navigation__submenu-icon">' . $icon . '</span>';

			} else {
				$html .= '<a class="wp-block-navigation-item__content"' . ( '' === $item['url'] ? '' : ' href="' . esc_url( $item['url'] ) . '"' ) . ( $item['current'] ? ' aria-current="page"' : '' ) . '>' . $item['title'] . '</a>';
			}

			if( $has_children ) {
				if( $nav['show_icon'] && 'click' !== $nav['visibility'] ) {
					$html .= '<button aria-label="' . esc_attr( $aria_label ) . '" class="wp-block-navigation__submenu-icon wp-block-navigation-submenu__toggle" aria-expanded="false"' . $toggle_directives . '>' . $icon . '</button>';
				}

				$html .= '<ul class="wp-block-navigation__submenu-container" data-wp-on--focus="actions.openMenuOnFocus">' . $item['children_html'] . '</ul>';
			}

			return $html . '</li>';
		}

		protected function get_submenu_icon() {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" focusable="false"><path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path></svg>';
		}

		/* Outside of the Navigation block */

		/**
		 * List: the terms list. Dropdown: the label opens the list. Row of top level items: top level terms in a row, the ones with children open them as dropdowns.
		 * "Collapse hierarchical children" (hierarchical taxonomies): the children lists become popups, like the submenus of a menu: the first level
		 * drops down under its parent, the deeper levels open on the side. Popups open on hover and with their toggle buttons (touch screens).
		 * Toggles and popup positions: a_w_f.setup_terms_lists() (awf.js).
		 * Premium columns: a CSS grid filled column by column (--awf-rows), as multi-column layouts split the popups of their items.
		 */
		protected function render_list( $tree, $args ) {

			$display = $args['settings']['display'];
			$list = array(
				'settings' => $args['settings'],
				'collapse' => $args['hierarchical'] && 'yes' === $args['settings']['collapse'],
				'row'      => 'top_level_row' === $display,
			);

			$classes = array( 'awf-terms-list', 'awf-terms-list-' . str_replace( '_', '-', $display ) );
			if( $list['collapse'] ) { $classes[] = 'awf-terms-list-collapsible'; }

			$columns_style = '';
			if( 1 < $args['columns'] && 'top_level_row' !== $display ) {
				$columns_style = '--awf-rows:' . (int) ceil( count( $tree['nodes'] ) / $args['columns'] ) . ';';
			}

			$style = '';

			if( 'dropdown' === $display ) {
				$label_classes = array( 'awf-terms-list-label', 'awf-menu-terms' );
				if( '' !== $columns_style ) { $label_classes[] = 'awf-menu-columns-' . $args['columns']; }
				if( $tree['current'] ) { $label_classes[] = 'current-menu-ancestor'; }

				$items_html = $this->render_list_dropdown( array(
					'title'   => esc_html( $args['label'] ),
					'url'     => $args['label_url'],
					'classes' => $label_classes,
					'current' => false,
				), $this->render_list_nodes( $tree['nodes'], $list, 1 ), $columns_style );

			} else {
				if( '' !== $columns_style ) {
					$classes[] = 'awf-menu-columns-' . $args['columns'];
					$classes[] = 'awf-terms-columns';
					$style = $columns_style;
				}

				$items_html = $this->render_list_nodes( $tree['nodes'], $list, 0 );
			}

			if( is_null( $args['shortcode_classes'] ) ) {
				$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ), 'style' => $style ) );
			} else {
				$wrapper_attributes = 'class="' . esc_attr( implode( ' ', array_merge( $classes, $args['shortcode_classes'] ) ) ) . '"' . ( '' === $style ? '' : ' style="' . esc_attr( $style ) . '"' );
			}

			return '<ul ' . $wrapper_attributes . '>' . $items_html . '</ul>';
		}

		/* Items of the standalone list; in the row of top level items, the top level terms with children are dropdowns */
		protected function render_list_nodes( $nodes, $list, $depth ) {

			$html = '';

			foreach( $nodes as $node ) {
				$item = $this->get_node_item( $node, $list['settings'], $depth );
				$children_html = empty( $node['children'] ) ? '' : $this->render_list_nodes( $node['children'], $list, $depth + 1 );

				if( '' !== $children_html && $list['row'] && 0 === $depth ) {
					$html .= $this->render_list_dropdown( $item, $children_html );
					continue;
				}

				$toggle = '';
				$children_classes = 'awf-terms-list-children';

				if( '' !== $children_html && $list['collapse'] ) {
					$item['classes'][] = 'awf-terms-popup-item';
					$item['classes'][] = 'awf-terms-has-children';
					$children_classes .= ' awf-terms-popup';

					/* translators: Accessibility text. %s: Parent item title. */
					$toggle = '<button type="button" class="awf-terms-toggle" aria-expanded="false" aria-label="' . esc_attr( sprintf( __( '%s submenu', 'annasta-filters' ), wp_strip_all_tags( $item['title'] ) ) ) . '">' . $this->get_submenu_icon() . '</button>';
				}

				$html .= '<li class="' . esc_attr( implode( ' ', $item['classes'] ) ) . '">';
				$html .= '<a href="' . esc_url( $item['url'] ) . '"' . ( $item['current'] ? ' aria-current="page"' : '' ) . '>' . $item['title'] . '</a>' . ( '' === $toggle ? '' : '&#8288;' . $toggle );
				if( '' !== $children_html ) { $html .= '<ul class="' . $children_classes . '">' . $children_html . '</ul>'; }
				$html .= '</li>';
			}

			return $html;
		}

		/**
		 * A dropdown item: the label (a link with a toggle button, or the toggle button itself) and its popup
		 *
		 * @param array $item 'title' (HTML), 'url', 'classes', 'current'
		 * @param string $children_html Items of the popup
		 * @param string $columns_style Premium columns of the popup (grid rows variable)
		 */
		protected function render_list_dropdown( $item, $children_html, $columns_style = '' ) {

			if( '' === $children_html ) {
				$label = '' === $item['url'] ? '<span>' . $item['title'] . '</span>' : '<a href="' . esc_url( $item['url'] ) . '">' . $item['title'] . '</a>';

				return '<li class="' . esc_attr( implode( ' ', $item['classes'] ) ) . '">' . $label . '</li>';
			}

			$item['classes'][] = 'awf-terms-popup-item';
			$item['classes'][] = 'awf-terms-dropdown';

			if( '' === $item['url'] ) {
				$label = '<button type="button" class="awf-terms-toggle awf-terms-label-toggle" aria-expanded="false"><span class="awf-terms-dropdown-label">' . $item['title'] . '</span>' . $this->get_submenu_icon() . '</button>';

			} else {
				/* translators: Accessibility text. %s: Parent item title. */
				$aria_label = sprintf( __( '%s submenu', 'annasta-filters' ), wp_strip_all_tags( $item['title'] ) );

				$label = '<a href="' . esc_url( $item['url'] ) . '"' . ( $item['current'] ? ' aria-current="page"' : '' ) . '>' . $item['title'] . '</a>';
				$label .= '&#8288;<button type="button" class="awf-terms-toggle" aria-expanded="false" aria-label="' . esc_attr( $aria_label ) . '">' . $this->get_submenu_icon() . '</button>';
			}

			$popup = '' === $columns_style ? '<ul class="awf-terms-list-children awf-terms-popup">' : '<ul class="awf-terms-list-children awf-terms-popup awf-terms-columns" style="' . esc_attr( $columns_style ) . '">';

			return '<li class="' . esc_attr( implode( ' ', $item['classes'] ) ) . '">' . $label . $popup . $children_html . '</ul></li>';
		}

	}
}
