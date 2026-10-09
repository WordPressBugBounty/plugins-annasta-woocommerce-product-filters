<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_nav_menus' ) ) {

	/**
	 * annasta menu items (Appearance > Menus): product categories / brands lists (A_W_F_terms_list) expanded into regular menu items
	 *
	 * Premium settings are displayed and saved by the A_W_F::$admin methods overridden in A_W_F_premium_admin.
	 *
	 * @since 1.8.5
	 */
	class A_W_F_nav_menus {

		/** Menu item type of the annasta menu items */
		const ITEM_TYPE = 'awf_terms';

		/** URL of the items added from the annasta boxes, converted to ITEM_TYPE on save (see save_item_settings()) */
		const URL_MARKER = '#awf-menu-';

		/** Post meta of the menu item settings */
		const SETTINGS_META = '_awf_menu_item_settings';

		/** Ids of the generated term menu items */
		protected $next_item_id = -1000000;

		public function __construct() {

			add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_menu_item' ) );

			if( is_admin() ) {
				add_action( 'admin_head-nav-menus.php', array( $this, 'add_meta_boxes' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ) );
				add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'display_item_settings' ), 10, 2 );
				add_action( 'wp_update_nav_menu_item', array( $this, 'save_item_settings' ), 10, 3 );

			} else {
				add_filter( 'wp_nav_menu_objects', array( $this, 'expand_menu_items' ), 10, 2 );
			}
		}

		/* "annasta Product Categories", "annasta Product Brands" */
		protected function get_item_title( $taxonomy_label ) {
			/* translators: %s: taxonomy label */
			return sprintf( __( 'annasta %s', 'annasta-filters' ), $taxonomy_label );
		}

		protected function get_item_settings( $item_id ) {
			return A_W_F_terms_list::normalize_settings( get_post_meta( $item_id, self::SETTINGS_META, true ) );
		}

		public function setup_menu_item( $menu_item ) {

			if( ! is_object( $menu_item ) || ! isset( $menu_item->type ) || self::ITEM_TYPE !== $menu_item->type ) { return $menu_item; }

			$taxonomies = A_W_F_terms_list::get_taxonomies();

			if( isset( $taxonomies[$menu_item->object] ) ) {
				$menu_item->type_label = $this->get_item_title( $taxonomies[$menu_item->object] );
				if( '' === $menu_item->title ) { $menu_item->title = $taxonomies[$menu_item->object]; }

				$settings = $this->get_item_settings( $menu_item->ID );
				$menu_item->url = 'shop' === $settings['link'] ? wc_get_page_permalink( 'shop' ) : '';

			} else {
				$menu_item->type_label = __( 'annasta Filters', 'annasta-filters' );
				$menu_item->url = '';
				$menu_item->_invalid = true;
			}

			return $menu_item;
		}

		/* Admin */

		public function add_meta_boxes() {
			foreach( A_W_F_terms_list::get_taxonomies() as $taxonomy => $label ) {
				add_meta_box( 'awf-nav-menu-items-' . $taxonomy, $this->get_item_title( $label ), array( $this, 'display_meta_box' ), 'nav-menus', 'side', 'low', array( 'taxonomy' => $taxonomy, 'label' => $label ) );
			}
		}

		/**
		 * Menu item settings layout: "Display mode" above the "Navigation Label" (renamed "Dropdown label"), the core "Menu Parent" / "Menu Order" (WP 6.7+) below the annasta settings,
		 * dropdown settings (label, its link, the dropdown hiding, columns) hidden for the "Row of top level items" display mode.
		 * "Add to Menu" of the annasta boxes: their single item has no visible checkbox, it is checked before the core click handler (#menu-settings-column).
		 */
		public function enqueue_admin_script( $hook ) {
			if( 'nav-menus.php' !== $hook ) { return; }

			$premium_script = is_null( A_W_F::$admin ) ? '' : A_W_F::$admin->get_nav_menu_script();

			wp_add_inline_script( 'nav-menu', "jQuery( function( $ ) {
	var title_label = " . wp_json_encode( __( 'Dropdown label', 'annasta-filters' ) ) . ";

	var arrange_settings = function( \$items ) {
		\$items.find( '.awf-menu-item-settings' ).each( function() {
			var \$settings = $( this );
			var \$panel = \$settings.closest( '.menu-item-settings' );

			var \$title_label = \$panel.find( 'label' ).has( '.edit-menu-item-title' ).first();

			\$title_label.contents().filter( function() { return 3 === this.nodeType && '' !== $.trim( this.nodeValue ); } ).first().replaceWith( title_label );
			\$title_label.closest( 'p' ).addClass( 'awf-menu-item-dropdown-label' ).before( \$settings.find( '.awf-menu-item-display-mode' ) );
			\$settings.after( \$panel.children( '.field-move-combo' ) );

			toggle_dropdown_settings( \$panel );
		} );
	};

	var toggle_dropdown_settings = function( \$panel ) {
		var is_dropdown = 'dropdown' === \$panel.find( '.awf-menu-item-display' ).val();

		\$panel.find( '.awf-menu-item-dropdown-label, .awf-menu-item-dropdown-settings' ).toggle( is_dropdown );
	};

	arrange_settings( $( '#menu-to-edit' ) );

	$( '.awf-nav-menu-box' ).on( 'click', '.submit-add-to-menu', function() {
		$( this ).closest( '.awf-nav-menu-box' ).find( '.menu-item-checkbox' ).prop( 'checked', true );
	} );

	$( document ).on( 'menu-item-added', function( e, \$markup ) {
		arrange_settings( \$markup );
	} );

	$( document ).on( 'change', '.awf-menu-item-display', function() {
		toggle_dropdown_settings( $( this ).closest( '.menu-item-settings' ) );
	} );
" . $premium_script . "
} );" );
		}

		/* Same markup as the core boxes, for the core "Add to Menu" script; the single item gets checked by the enqueue_admin_script() script */
		public function display_meta_box( $object, $box ) {
			global $_nav_menu_placeholder;

			if( empty( $box['args']['taxonomy'] ) ) { return; }

			$taxonomy = $box['args']['taxonomy'];
			$label = $box['args']['label'];
			$box_id = 'awf-nav-menu-items-' . $taxonomy;

			$_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? $_nav_menu_placeholder - 1 : -1;
			$i = (int) $_nav_menu_placeholder;

			?>
			<div id="<?php echo esc_attr( $box_id ); ?>" class="posttypediv awf-nav-menu-box">
				<div id="tabs-panel-<?php echo esc_attr( $box_id ); ?>" class="tabs-panel tabs-panel-active" style="display:none;">
					<ul id="<?php echo esc_attr( $box_id ); ?>-checklist" class="categorychecklist form-no-clear">
						<li>
							<label class="menu-item-title">
								<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr( $i ); ?>][awf-menu-item]" value="<?php echo esc_attr( $taxonomy ); ?>" /> <?php echo esc_html( $this->get_item_title( $label ) ); ?>
							</label>
							<input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-type]" value="custom" />
							<input type="hidden" class="menu-item-title" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-title]" value="<?php echo esc_attr( $label ); ?>" />
							<input type="hidden" class="menu-item-url" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-url]" value="<?php echo esc_attr( self::URL_MARKER . $taxonomy ); ?>" />
							<input type="hidden" class="menu-item-classes" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-classes]" value="" />
						</li>
					</ul>
				</div>
				<p class="button-controls wp-clearfix">
					<span class="add-to-menu">
						<input type="submit" class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'annasta-filters' ); ?>" name="add-awf-nav-menu-item" id="submit-<?php echo esc_attr( $box_id ); ?>" />
						<span class="spinner"></span>
					</span>
				</p>
			</div>
			<?php
		}

		public function display_item_settings( $item_id, $menu_item ) {

			if( ! isset( $menu_item->type ) || self::ITEM_TYPE !== $menu_item->type ) { return; }

			$taxonomies = A_W_F_terms_list::get_taxonomies();
			if( ! isset( $taxonomies[$menu_item->object] ) ) { return; }

			$item_id = (int) $item_id;
			$settings = $this->get_item_settings( $item_id );
			$name = 'awf-menu-item[' . $item_id . ']';
			$id = 'edit-awf-menu-item-' . $item_id . '-';
			$admin = A_W_F::$admin;

			?>
			<div class="awf-menu-item-settings">
				<input type="hidden" name="<?php echo esc_attr( $name . '[submitted]' ); ?>" value="1" />

				<p class="awf-menu-item-display-mode description description-wide">
					<label for="<?php echo esc_attr( $id . 'display' ); ?>"><?php esc_html_e( 'Display mode', 'annasta-filters' ); ?><br />
						<select id="<?php echo esc_attr( $id . 'display' ); ?>" class="widefat awf-menu-item-display" name="<?php echo esc_attr( $name . '[display]' ); ?>">
							<option value="dropdown"<?php selected( $settings['display'], 'dropdown' ); ?>><?php esc_html_e( 'Dropdown', 'annasta-filters' ); ?></option>
							<option value="top_level_row"<?php selected( $settings['display'], 'top_level_row' ); ?>><?php esc_html_e( 'Row of top level items', 'annasta-filters' ); ?></option>
						</select>
					</label>
				</p>

				<div class="awf-menu-item-dropdown-settings"<?php if( 'top_level_row' === $settings['display'] ) { echo ' style="display:none;"'; } ?>>
				<p class="description description-wide">
					<label for="<?php echo esc_attr( $id . 'link' ); ?>"><?php esc_html_e( 'Dropdown label link', 'annasta-filters' ); ?><br />
						<select id="<?php echo esc_attr( $id . 'link' ); ?>" class="widefat" name="<?php echo esc_attr( $name . '[link]' ); ?>">
							<option value="none"<?php selected( $settings['link'], 'none' ); ?>><?php esc_html_e( 'No link', 'annasta-filters' ); ?></option>
							<option value="shop"<?php selected( $settings['link'], 'shop' ); ?>><?php esc_html_e( 'Shop page', 'annasta-filters' ); ?></option>
						</select>
					</label>
				</p>

				<p class="description description-wide">
					<label for="<?php echo esc_attr( $id . 'hide_if_empty' ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( $id . 'hide_if_empty' ); ?>" name="<?php echo esc_attr( $name . '[hide_if_empty]' ); ?>" value="yes"<?php checked( $settings['hide_if_empty'], 'yes' ); ?> />
						<?php esc_html_e( 'Hide dropdown when it has no terms', 'annasta-filters' ); ?>
					</label>
				</p>

				<?php if( $admin ) { $admin->display_nav_menu_columns_setting( $settings, $name, $id ); } ?>
				</div>

				<p class="description description-wide">
					<label for="<?php echo esc_attr( $id . 'limit' ); ?>"><?php esc_html_e( 'Top level terms limit (0 = all)', 'annasta-filters' ); ?><br />
						<input type="number" min="0" step="1" id="<?php echo esc_attr( $id . 'limit' ); ?>" class="widefat" name="<?php echo esc_attr( $name . '[limit]' ); ?>" value="<?php echo esc_attr( $settings['limit'] ); ?>" />
					</label>
				</p>

				<?php
					$has_terms_setting = $admin ? $admin->display_nav_menu_terms_setting( $menu_item, $settings, $name, $id ) : false;
				?>

				<p class="description description-wide">
					<label for="<?php echo esc_attr( $id . 'terms_link' ); ?>"><?php esc_html_e( 'Term links', 'annasta-filters' ); ?><br />
						<select id="<?php echo esc_attr( $id . 'terms_link' ); ?>" class="widefat" name="<?php echo esc_attr( $name . '[terms_link]' ); ?>">
							<option value="archive"<?php selected( $settings['terms_link'], 'archive' ); ?>><?php esc_html_e( 'Term archive', 'annasta-filters' ); ?></option>
							<option value="filtered"<?php selected( $settings['terms_link'], 'filtered' ); ?>><?php esc_html_e( 'Filtered shop page', 'annasta-filters' ); ?></option>
						</select>
					</label>
				</p>

				<?php if( is_taxonomy_hierarchical( $menu_item->object ) ) { ?>
				<p class="description description-wide">
					<label for="<?php echo esc_attr( $id . 'depth' ); ?>"><?php esc_html_e( 'Hierarchical levels limit (0 = all)', 'annasta-filters' ); ?><br />
						<input type="number" min="0" step="1" id="<?php echo esc_attr( $id . 'depth' ); ?>" class="widefat" name="<?php echo esc_attr( $name . '[depth]' ); ?>" value="<?php echo esc_attr( $settings['depth'] ); ?>" />
					</label>
				</p>
				<?php } ?>

				<p class="description description-wide">
					<?php
						if( ! $has_terms_setting && 'product_cat' === $menu_item->object ) {
							self::display_exclude_default_setting( $settings, $name, $id );
							echo '<br />';
						}
					?>
					<label for="<?php echo esc_attr( $id . 'show_count' ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( $id . 'show_count' ); ?>" name="<?php echo esc_attr( $name . '[show_count]' ); ?>" value="yes"<?php checked( $settings['show_count'], 'yes' ); ?> />
						<?php esc_html_e( 'Show products count', 'annasta-filters' ); ?>
					</label>
					<br />
					<label for="<?php echo esc_attr( $id . 'hide_empty' ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( $id . 'hide_empty' ); ?>" name="<?php echo esc_attr( $name . '[hide_empty]' ); ?>" value="yes"<?php checked( $settings['hide_empty'], 'yes' ); ?> />
						<?php esc_html_e( 'Hide empty terms', 'annasta-filters' ); ?>
					</label>
					<?php if( $admin ) { $admin->display_nav_menu_empty_terms_settings( $settings, $name, $id ); } ?>
				</p>

				<p class="description description-wide">
					<label for="<?php echo esc_attr( $id . 'orderby' ); ?>"><?php esc_html_e( 'Order', 'annasta-filters' ); ?><br />
						<select id="<?php echo esc_attr( $id . 'orderby' ); ?>" class="widefat" name="<?php echo esc_attr( $name . '[orderby]' ); ?>">
							<option value="default"<?php selected( $settings['orderby'], 'default' ); ?>><?php esc_html_e( 'Default', 'annasta-filters' ); ?></option>
							<option value="name"<?php selected( $settings['orderby'], 'name' ); ?>><?php esc_html_e( 'Name', 'annasta-filters' ); ?></option>
						</select>
					</label>
				</p>

			</div>
			<?php
		}

		/* Also displayed inside the premium terms setting (A_W_F_premium_admin::display_nav_menu_terms_setting()) */
		public static function display_exclude_default_setting( $settings, $name, $id ) {
			?>
			<label for="<?php echo esc_attr( $id . 'exclude_default' ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( $id . 'exclude_default' ); ?>" class="awf-exclude-default" name="<?php echo esc_attr( $name . '[exclude_default]' ); ?>" value="yes"<?php checked( $settings['exclude_default'], 'yes' ); ?> />
				<?php esc_html_e( 'Exclude Uncategorized', 'annasta-filters' ); ?>
			</label>
			<?php
		}

		/**
		 * wp_update_nav_menu_item: the custom links added from the annasta boxes (marker URL) become annasta menu items,
		 * the settings of the existing ones are saved
		 *
		 * @param int $menu_id
		 * @param int $menu_item_db_id
		 * @param array $args
		 */
		public function save_item_settings( $menu_id, $menu_item_db_id, $args = array() ) {

			$menu_item_db_id = (int) $menu_item_db_id;

			if( isset( $args['menu-item-type'] ) && 'custom' === $args['menu-item-type'] && isset( $args['menu-item-url'] ) && 0 === strpos( $args['menu-item-url'], self::URL_MARKER ) ) {
				$taxonomy = sanitize_key( substr( $args['menu-item-url'], strlen( self::URL_MARKER ) ) );

				if( array_key_exists( $taxonomy, A_W_F_terms_list::get_taxonomies() ) ) {
					update_post_meta( $menu_item_db_id, '_menu_item_type', self::ITEM_TYPE );
					update_post_meta( $menu_item_db_id, '_menu_item_object', $taxonomy );
					update_post_meta( $menu_item_db_id, '_menu_item_url', '' );
				}

				return;
			}

			if( ! isset( $_POST['awf-menu-item'][$menu_item_db_id]['submitted'] ) ) { return; }
			if( ! current_user_can( 'edit_theme_options' ) ) { return; }
			if( ! isset( $_POST['update-nav-menu-nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['update-nav-menu-nonce'] ) ), 'update-nav_menu' ) ) { return; }
			if( self::ITEM_TYPE !== get_post_meta( $menu_item_db_id, '_menu_item_type', true ) ) { return; }

			$data = wp_unslash( $_POST['awf-menu-item'][$menu_item_db_id] );
			if( ! is_array( $data ) ) { return; }

			$settings = A_W_F_terms_list::sanitize_settings( $data, $this->get_item_settings( $menu_item_db_id ) );
			if( ! is_null( A_W_F::$admin ) ) { $settings = A_W_F::$admin->sanitize_nav_menu_settings( $settings, $data ); }

			update_post_meta( $menu_item_db_id, self::SETTINGS_META, $settings );
		}

		/* Frontend */

		/**
		 * wp_nav_menu_objects: the annasta menu items expanded into regular menu items of their terms
		 *
		 * Items replaced by their terms ("Row of top level items") or hidden ("Hide dropdown when it has no terms")
		 * pass the items placed under them to their own parent.
		 *
		 * @param array $items
		 * @param stdClass $args
		 * @return array
		 */
		public function expand_menu_items( $items, $args ) {

			if( empty( $items ) || ! ( A_W_F::$front instanceof A_W_F_frontend ) ) { return $items; }

			$has_awf_items = false;
			foreach( $items as $item ) {
				if( isset( $item->type ) && self::ITEM_TYPE === $item->type ) { $has_awf_items = true; break; }
			}
			if( ! $has_awf_items ) { return $items; }

			$expanded = array();
			$replaced = array();

			foreach( $items as $item ) {

				if( ! isset( $item->type ) || self::ITEM_TYPE !== $item->type ) {
					$expanded[] = $item;
					continue;
				}

				$settings = $this->get_item_settings( $item->ID );
				$tree = A_W_F_terms_list::get_tree( $item->object, $settings );

				if( 'top_level_row' === $settings['display'] ) {
					$parent_id = (int) $item->menu_item_parent;
					$replaced[(int) $item->db_id] = $parent_id;

					$this->add_term_items( $expanded, $tree['nodes'], $item, $settings, $parent_id );

				} else {

					if( empty( $tree['nodes'] ) && 'yes' === $settings['hide_if_empty'] ) {
						$replaced[(int) $item->db_id] = (int) $item->menu_item_parent;
						continue;
					}

					$item->classes = (array) $item->classes;
					$item->classes[] = 'awf-menu-terms';

					if( ! empty( $tree['nodes'] ) ) {
						$item->classes[] = 'menu-item-has-children';

						$columns = A_W_F::$front->get_terms_list_columns( $settings );
						if( 1 < $columns ) {
							$item->classes[] = 'awf-menu-columns-' . $columns;
							A_W_F::$front->enqueue_terms_list_css();
						}
					}

					if( $tree['current'] ) {
						$item->classes[] = 'current-menu-ancestor';
						$item->current_item_ancestor = true;

						if( 'parent' === $tree['current'] ) {
							$item->classes[] = 'current-menu-parent';
							$item->current_item_parent = true;
						}
					}

					$expanded[] = $item;
					$this->add_term_items( $expanded, $tree['nodes'], $item, $settings, (int) $item->db_id );
				}
			}

			if( ! empty( $replaced ) ) {
				foreach( $expanded as $item ) {
					while( isset( $replaced[(int) $item->menu_item_parent] ) ) {
						$item->menu_item_parent = $replaced[(int) $item->menu_item_parent];
					}
				}
			}

			$sorted = array();
			$i = 1;
			foreach( $expanded as $item ) { $sorted[$i++] = $item; }

			return $sorted;
		}

		/**
		 * Menu items of the terms tree nodes (no dimmed items in the main menu)
		 *
		 * @param array $items Menu items, the term items are appended
		 * @param array $nodes Terms tree nodes (A_W_F_terms_list::get_tree())
		 * @param object $menu_item The annasta menu item
		 * @param array $settings
		 * @param int $parent_id Menu parent of the nodes
		 * @param int $level Terms tree level of the nodes
		 */
		protected function add_term_items( &$items, $nodes, $menu_item, $settings, $parent_id, $level = 0 ) {

			foreach( $nodes as $node ) {
				if( 0 === $level && 'top_level_row' === $settings['display'] ) { $node['dim'] = false; }

				$term_item = $this->create_term_item( $menu_item, $node, $settings, $parent_id );
				$items[] = $term_item;

				if( ! empty( $node['children'] ) ) {
					$this->add_term_items( $items, $node['children'], $menu_item, $settings, $term_item->db_id, $level + 1 );
				}
			}
		}

		protected function create_term_item( $menu_item, $node, $settings, $parent_id ) {

			$term = $node['term'];
			$term_item = clone $menu_item;

			$term_item->ID = $term_item->db_id = $this->next_item_id--;
			$term_item->menu_item_parent = (string) $parent_id;
			$term_item->object_id = (string) $term->term_id;
			$term_item->object = $term->taxonomy;
			$term_item->type = 'taxonomy';
			$term_item->post_title = $term->name;
			$term_item->post_content = $term_item->post_excerpt = '';
			$term_item->title = esc_html( $term->name );
			$term_item->url = $node['url'];
			$term_item->target = $term_item->attr_title = $term_item->description = $term_item->xfn = '';
			$term_item->current = $term_item->current_item_ancestor = $term_item->current_item_parent = false;
			$term_item->classes = array( 'menu-item', 'menu-item-type-taxonomy', 'menu-item-object-' . $term->taxonomy, 'awf-menu-term', 'awf-menu-term-' . $term->term_id );

			if( 'yes' === $settings['show_count'] ) {
				$term_item->title .= ' <span class="awf-menu-term-count">(' . esc_html( $node['count'] ) . ')</span>';
			}

			if( 0 === $node['count'] ) { $term_item->classes[] = 'awf-menu-term-empty'; }

			if( $node['dim'] ) {
				$term_item->classes[] = 'awf-menu-term-dim';
				A_W_F::$front->enqueue_terms_list_css();
			}

			if( ! empty( $node['children'] ) ) { $term_item->classes[] = 'menu-item-has-children'; }

			if( $node['current'] ) {
				$term_item->classes[] = 'current-menu-item';
				$term_item->current = true;
			}

			if( $node['children_current'] ) {
				$term_item->classes[] = 'current-menu-ancestor';
				$term_item->current_item_ancestor = true;

				if( 'parent' === $node['children_current'] ) {
					$term_item->classes[] = 'current-menu-parent';
					$term_item->current_item_parent = true;
				}
			}

			return $term_item;
		}

	}
}
