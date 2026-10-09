<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_terms_list' ) ) {

	/**
	 * Terms lists of the annasta menu items (A_W_F_nav_menus): settings and the terms tree, with the empty terms
	 * detected by the annasta product counts (of the whole shop, or premium: of the current archive)
	 *
	 * Premium settings are applied by the A_W_F::$front methods overridden in A_W_F_premium_frontend.
	 *
	 * @since 1.8.5
	 */
	class A_W_F_terms_list {

		/** Current archive terms, taxonomy => slugs */
		protected static $archive_terms = null;

		/* Taxonomies of the terms lists found on the installation */
		public static function get_taxonomies() {
			$taxonomies = array();

			if( taxonomy_exists( 'product_cat' ) ) { $taxonomies['product_cat'] = __( 'Product Categories', 'annasta-filters' ); }
			if( taxonomy_exists( 'product_brand' ) ) { $taxonomies['product_brand'] = __( 'Product Brands', 'annasta-filters' ); }

			return $taxonomies;
		}

		public static function get_default_settings() {
			return array(
				'display'         => 'dropdown',
				'collapse'        => 'yes',
				'link'            => 'none',
				'hide_if_empty'   => 'no',
				'terms_link'      => 'archive',
				'exclude_default' => 'yes',
				'hide_empty'      => 'yes',
				'dynamic'         => 'no',
				'show_count'      => 'no',
				'depth'           => 0,
				'orderby'         => 'default',
				'limit'           => 0,
				'terms_mode'      => 'all',
				'terms'           => array(),
				'columns'         => 1,
				'dim_empty'       => 'no',
			);
		}

		/* Saved settings with the defaults */
		public static function normalize_settings( $settings ) {
			if( ! is_array( $settings ) ) { $settings = array(); }

			return array_merge( self::get_default_settings(), $settings );
		}

		/**
		 * Sanitized free settings
		 *
		 * The "list" display is used by the blocks outside of the Navigation block and by the shortcodes only.
		 *
		 * @param array $data Submitted values (unslashed)
		 * @param array $settings Current settings
		 * @return array
		 */
		public static function sanitize_settings( $data, $settings ) {

			$settings['display'] = ( isset( $data['display'] ) && in_array( $data['display'], array( 'list', 'dropdown', 'top_level_row' ), true ) ) ? $data['display'] : 'dropdown';
			$settings['link'] = ( isset( $data['link'] ) && 'shop' === $data['link'] ) ? 'shop' : 'none';
			$settings['terms_link'] = ( isset( $data['terms_link'] ) && 'filtered' === $data['terms_link'] ) ? 'filtered' : 'archive';
			$settings['orderby'] = ( isset( $data['orderby'] ) && 'name' === $data['orderby'] ) ? 'name' : 'default';
			$settings['limit'] = isset( $data['limit'] ) ? absint( $data['limit'] ) : 0;
			$settings['depth'] = isset( $data['depth'] ) ? absint( $data['depth'] ) : 0;

			foreach( array( 'hide_if_empty', 'collapse', 'exclude_default', 'hide_empty', 'show_count' ) as $setting ) {
				$settings[$setting] = isset( $data[$setting] ) ? 'yes' : 'no';
			}

			return $settings;
		}

		/**
		 * The terms tree of a terms list
		 *
		 * Nodes: 'term' => WP_Term, 'url', 'count' (null without "Hide empty terms" / "Show products count"),
		 * 'dim' (premium "Dim empty terms": no products in the current archive), 'current', 'children_current',
		 * 'children'.
		 * Current states: false, 'parent' (a term of that level is current), 'ancestor' (a deeper term is current).
		 *
		 * Terms excluded from the list pass their children to their closest allowed ancestor. The counts are those of the
		 * whole shop, or of the current archive (premium "Dynamically adjust hidden items in archives"); premium "Dim empty terms"
		 * always uses the current archive. With links to the filtered shop, the terms of the shop's filter are current.
		 *
		 * @param string $taxonomy
		 * @param array $settings Normalized settings
		 * @return array 'nodes' => top level nodes, 'current' => current state of the top level
		 */
		public static function get_tree( $taxonomy, $settings ) {

			$tree = array( 'nodes' => array(), 'current' => false );

			if( ! taxonomy_exists( $taxonomy ) || ! ( A_W_F::$front instanceof A_W_F_frontend ) ) { return $tree; }

			$terms_args = array( 'taxonomy' => $taxonomy, 'hide_empty' => false );
			if( 'name' === $settings['orderby'] ) { $terms_args['orderby'] = 'name'; $terms_args['order'] = 'ASC'; }

			$hierarchical = is_taxonomy_hierarchical( $taxonomy );
			$depth = $hierarchical ? (int) $settings['depth'] : 0;
			if( 1 === $depth ) { $terms_args['parent'] = 0; }

			$terms = get_terms( $terms_args );
			if( is_wp_error( $terms ) || empty( $terms ) ) { return $tree; }

			$terms_by_id = array();
			foreach( $terms as $term ) { $terms_by_id[(int) $term->term_id] = $term; }

			$allowed = array_fill_keys( array_keys( $terms_by_id ), true );

			if( 'product_cat' === $taxonomy && 'yes' === $settings['exclude_default'] ) {
				$default_id = (int) get_option( 'default_product_cat', 0 );
				if( class_exists( 'SitePress' ) ) { $default_id = (int) apply_filters( 'wpml_object_id', $default_id, $taxonomy, true ); }

				unset( $allowed[$default_id] );
			}

			$allowed = A_W_F::$front->filter_terms_list_terms( $allowed, $terms_by_id, $settings, $taxonomy );

			$children = array();
			foreach( $terms as $term ) {
				$term_id = (int) $term->term_id;
				if( ! isset( $allowed[$term_id] ) ) { continue; }

				$parent = $hierarchical ? (int) $term->parent : 0;
				while( 0 !== $parent && ! isset( $allowed[$parent] ) ) {
					$parent = isset( $terms_by_id[$parent] ) ? (int) $terms_by_id[$parent]->parent : 0;
				}

				$children[$parent][] = $term;
			}

			$args = array(
				'taxonomy'      => $taxonomy,
				'children'      => $children,
				'settings'      => $settings,
				'depth'         => $depth,
				'limit'         => (int) $settings['limit'],
				'dim_empty'     => A_W_F::$front->is_terms_list_dim_empty( $settings ),
				'counts'        => array(),
				'dim_counts'    => array(),
				'current_slugs' => array(),
			);

			$archive_context = self::get_counts_context( $taxonomy );
			$args['has_counts'] = 'yes' === $settings['hide_empty'] || 'yes' === $settings['show_count'];

			if( $args['has_counts'] ) {
				$context = A_W_F::$front->is_terms_list_dynamic( $settings ) ? $archive_context : array();
				$args['counts'] = A_W_F::$front->get_terms_list_counts( $taxonomy, $context );
			}

			if( $args['dim_empty'] ) {
				$args['dim_counts'] = A_W_F::$front->get_terms_list_counts( $taxonomy, $archive_context );
			}

			$archive_terms = self::get_archive_terms();
			if( isset( $archive_terms[$taxonomy] ) ) { $args['current_slugs'] = $archive_terms[$taxonomy]; }

			$args['filtered_link'] = 'filtered' === $settings['terms_link'] && isset( A_W_F::$front->vars->tax[$taxonomy] ) && ! empty( A_W_F::$front->shop_url );
			if( $args['filtered_link'] && empty( $args['current_slugs'] ) && is_shop() && ! empty( A_W_F::$front->query->tax[$taxonomy] ) ) {
				$args['current_slugs'] = array_values( array_map( 'strval', (array) A_W_F::$front->query->tax[$taxonomy] ) );
			}

			list( $tree['nodes'], $tree['current'] ) = self::build_nodes( 0, 0, $args );

			return $tree;
		}

		/**
		 * Nodes of one level of the terms tree (top level limit, levels limit, empty terms hidden unless they have children)
		 *
		 * @return array array( nodes, current state of the level )
		 */
		protected static function build_nodes( $term_parent_id, $level, $args ) {

			$nodes = array();
			$current = false;

			if( empty( $args['children'][$term_parent_id] ) ) { return array( $nodes, $current ); }

			foreach( $args['children'][$term_parent_id] as $term ) {

				if( 0 === $level && 0 < $args['limit'] && count( $nodes ) >= $args['limit'] ) { break; }

				$slug = urldecode( $term->slug );
				$count = isset( $args['counts'][$slug] ) ? (int) $args['counts'][$slug] : 0;

				if( 0 === $args['depth'] || $level + 1 < $args['depth'] ) {
					list( $child_nodes, $children_current ) = self::build_nodes( (int) $term->term_id, $level + 1, $args );
				} else {
					$child_nodes = array();
					$children_current = false;
				}

				if( 'yes' === $args['settings']['hide_empty'] && 0 === $count && empty( $child_nodes ) ) { continue; }

				$node = array(
					'term'             => $term,
					'url'              => self::get_term_url( $term, $args['filtered_link'] ),
					'count'            => $args['has_counts'] ? $count : null,
					'dim'              => $args['dim_empty'] && empty( $args['dim_counts'][$slug] ),
					'current'          => in_array( $slug, $args['current_slugs'], true ),
					'children_current' => $children_current,
					'children'         => $child_nodes,
				);

				if( $node['current'] && false === $current ) { $current = 'parent'; }
				if( $children_current ) { $current = 'parent' === $current ? 'parent' : 'ancestor'; }

				$nodes[] = $node;
			}

			return array( $nodes, $current );
		}

		/* Term archive, or filtered shop URL built like the filters' links (parameters' labels, rewrites) */
		protected static function get_term_url( $term, $filtered_link ) {

			if( $filtered_link ) {
				return A_W_F::$front->get_url( A_W_F::$front->shop_url, array( A_W_F::$front->vars->tax[$term->taxonomy] => urldecode( $term->slug ) ), 'shop' );
			}

			$url = get_term_link( $term );

			return is_wp_error( $url ) ? '' : $url;
		}

		/* The current product_cat / product_brand archive terms (multi-term archives included) */
		public static function get_archive_terms() {

			if( ! is_null( self::$archive_terms ) ) { return self::$archive_terms; }

			self::$archive_terms = array();
			$taxonomies = array_keys( self::get_taxonomies() );

			if( ! empty( $taxonomies ) && is_tax( $taxonomies ) ) {
				$term = get_queried_object();

				if( $term instanceof WP_Term ) {
					$front = A_W_F::$front;

					if( $front->is_archive === $term->taxonomy && ! empty( $front->query->tax[$term->taxonomy] ) ) {
						$slugs = (array) $front->query->tax[$term->taxonomy];
					} else {
						$slugs = array( urldecode( $term->slug ) );
					}

					self::$archive_terms[$term->taxonomy] = array_values( array_map( 'strval', $slugs ) );
				}
			}

			return self::$archive_terms;
		}

		/* The list's own taxonomy doesn't narrow its counts */
		protected static function get_counts_context( $taxonomy ) {
			return array_diff_key( self::get_archive_terms(), array( $taxonomy => '' ) );
		}

	}
}
