<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists('A_W_F_frontend') ) {

	class A_W_F_frontend {

		/** Current running instance of A_W_F_frontend object
		 *
		 * @since 1.0.0
		 * @var A_W_F_frontend object
		 */
		protected static $instance;

		public $filters_manager;

		public $awf_settings = array();

		public $filter_on = false;

		/** List of all the AWF variables
		 *
		 * @since 1.2.5
		 * @var StdClass object with the following array properties: tax, awf, meta, range
		 */
		public $vars;
		public $query;

		public $url_query = array();
		public $page_associations;
		public $page_parent_associations;
		public $permalinks_on = false;
		public $shop_on_frontpage;
		public $shop_page_id;
		public $shop_url;
		public $current_url;
		public $url_page = 'shop';

		protected $is_wp_page = false;
		public $is_sc_page = false;
		public $is_archive = false;

		public $seo_parts;

		/** List of rewrite rules
		 *
		 * @since 1.8.2
		 * @var array
		 */
		public $rewrites = array();
		public $current_rewrites;
		public $canonical_parameters = array();
		/** 
		 * Robots noindex of the current page, see is_noindex_page()
		 * 
		 * @since 1.8.5
		 */
		protected $noindex = null;

		public $counts;
		const META_COUNTS_PREFIX = 'awf-meta--';
		protected $counts_cache_name;
		protected $update_counts_cache;
		protected $update_existing_counts_cache;

		public $get_access_to = array();
		public $preset;

		/** Current site language for the sites with multiple language support
		 *
		 * @since 1.2.6
		 * @var NULL / Boolean / String
		 */
		public $language = null;

		protected function __construct() {

			if( isset( $_REQUEST['awf_action'] ) && ! isset( $_REQUEST['action'] ) && 'filter' === $_REQUEST['awf_action'] ) {
				$this->initialize_ajax_compatibility_mode();
			}

			$this->query = (object) array( 'awf' => array(), 'tax' => array(), 'meta' => array(), 'range' => array() );

			add_action( 'init', array( $this, 'register_shortcodes' ) );
			add_action( 'init', array( $this, 'initialize' ), 20 );
			add_action( 'init', array( $this, 'edit_woocommerce_before_shop_loop' ), 100 );
			add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
			add_action( 'pre_get_posts', array( $this, 'before_wc_query' ), 0 );
			// add_action( 'woocommerce_product_query', array( $this, 'wc_query' ), 20, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'restore_wc_add_to_cart_script' ), 20 );

			if( 'yes' === get_option( 'awf_variations_stock_support', 'no' ) ) {
				add_filter( 'awf_product_counts_query', array( $this, 'add_variations_stock_support_to_product_counts' ) );
			}

			if( ! empty( get_option( 'awf_user_js', '' ) ) ) {
				add_action( 'wp_footer', array( $this, 'load_footer_js' ), 20 );
			}

			if( 'yes' === get_option( 'awf_add_seo_meta_description', 'no' ) ) {
				add_action( 'wp_head', array( $this, 'add_meta_description' ) );
			}

			if( 'yes' === get_option( 'awf_redirect_archives', 'no' ) ) {
				add_action( 'template_redirect', array( $this, 'redirect_archives' ), 20 );
			}

			add_action( 'wp_footer', array( $this, 'add_to_wp_footer' ) );
			add_action( 'wp_footer', array( $this, 'display_togglable_presets' ), 100 );
			add_action( 'shutdown', array( $this, 'update_counts_cache' ) );

			$this->filters_manager = 'A_W_F_filter_frontend';
		}

		protected function initialize_ajax_compatibility_mode() {

			$this->get_access_to['is_acm'] = true;

			if( isset( $_REQUEST['page_number'] ) ) {
				$page_number = intval( $_REQUEST['page_number'] );

				$url = wp_get_referer();
				if( empty( $url ) ) {
					wp_send_json_error( __( 'Error requesting paged URL', 'annasta-filters' ), 400 );
					die();
				}
				$url = esc_url_raw( $url );

				if( wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
					wp_send_json_error( __( 'Error requesting paged URL', 'annasta-filters' ), 400 );
					die();
				}

				$parameters = array(
					'awf_action' => 'filter',
					'awf_paged' => $page_number
				);

				if( isset( $_REQUEST['awf_sc_page'] ) ) {
					$parameters['product-page'] = $page_number;
				} else {
					$parameters['paged'] = $page_number;
				}

				$url = add_query_arg( $parameters, $url );

				$response = wp_safe_remote_get( $url );
				if( is_wp_error( $response ) ) {
					wp_send_json_error( __( 'Error requesting paged URL', 'annasta-filters' ), 400 );
					die();
				}

				$html = wp_remote_retrieve_body( $response );
				if( is_wp_error( $html ) ) {
					wp_send_json_error( __( 'Error requesting paged URL', 'annasta-filters' ), 400 );
					die();
				}

				echo $html;
				die();
			}

			if( isset( $_REQUEST['awf_paged'] ) ) {

				if( 'page_numbers' === get_option( 'awf_ajax_pagination', 'none' ) ) {
					$this->add_ajax_compatibility_mode_support();
				} else {
					add_action( 'woocommerce_before_shop_loop', array( $this, 'display_ajax_pagination_resut_count' ), 1000 );
				}

			} else {
				$this->add_ajax_compatibility_mode_support();
			}

		}

		protected function add_ajax_compatibility_mode_support() {

			if( 'yes' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
				add_action( 'woocommerce_before_shop_loop', array( $this, 'add_ajax_document_title' ) );
				add_action( 'woocommerce_no_products_found', array( $this, 'add_ajax_document_title' ) );
				if( 'yes' === get_option( 'awf_breadcrumbs_support', 'yes' ) ) {
					add_action( 'woocommerce_before_shop_loop', array( $this, 'add_awf_breadcrumbs_support' ) );
					add_action( 'woocommerce_no_products_found', array( $this, 'add_awf_breadcrumbs_support' ) );
				}

			} else {
				add_action( 'woocommerce_before_shop_loop', array( $this, 'add_ajax_products_header' ), 5 );
				add_action( 'woocommerce_no_products_found', array( $this, 'add_ajax_products_header' ) );
				add_action( 'awf_add_ajax_products_header_title', array( $this, 'add_ajax_products_header_title' ) );
			}

			$this->get_access_to['remove_pagination_args'] = array( 'awf_front', 'awf_action', 'awf_query', 'awf_ajax_extras', 'awf_sc', 'awf_sc_page', 'awf_archive_page', 'awf_cm_v2', 'awf_rewrite', 'rewrite', 'sc_page' );

			add_filter( 'paginate_links', function( $href ) {
				return remove_query_arg( $this->get_access_to['remove_pagination_args'], $href );
			});

			add_action( 'wp', function() {
				if( isset( $_REQUEST['awf_rewrite'] ) ) {
					$this->setup_ajax_rewrites();
					if( $this->current_rewrites ) {
						$this->get_access_to['remove_pagination_args'] = array_merge( $this->get_access_to['remove_pagination_args'], array_values( $this->current_rewrites ) );
						add_filter( 'woocommerce_pagination_args', array( $this, 'adjust_wc_pagination' ) );
					}
				}
			} );
		}

		public function ajax_controller() {

			if( 'filter' === $_GET['awf_action'] ) {
				$sc_args = array();

				if( isset( $_GET['awf_sc_page'] ) ) {
					$this->is_sc_page = (int) $_GET['awf_sc_page'];
					$this->initialize();

					add_action( 'woocommerce_shortcode_before_products_loop', array( $this, 'insert_sc_ajax_vars' ) );          
					add_action( 'woocommerce_shortcode_before_sale_products_loop', array( $this, 'insert_sc_ajax_vars' ) );
					add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'insert_sc_ajax_vars' ) );
					add_action( 'woocommerce_shortcode_sale_products_loop_no_results', array( $this, 'insert_sc_ajax_vars' ) );

					$sc_args = isset( $_GET['awf_sc'] ) ? $_GET['awf_sc'] : array();
					if( isset( $sc_args['awf_on_sale_sc'] ) ) {
						$sc_args['on_sale'] = 'yes';
					}

				} else {
					$this->initialize();

					$sc_args = array( 'paginate' => true );

					if( ! isset( $this->query->awf['orderby'] ) ) {
						$sc_args['orderby'] = get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
					}

					if( ! empty( $this->awf_settings['shop_columns'] ) ) { $sc_args['columns'] = $this->awf_settings['shop_columns']; }

					if( empty( $this->awf_settings['ppp_default'] ) ) {
						$sc_args['limit'] = ( empty( $this->awf_settings['shop_columns'] ) ? wc_get_default_products_per_row() : $this->awf_settings['shop_columns'] ) * wc_get_default_product_rows_per_page();

					} else {
						$sc_args['limit'] = $this->awf_settings['ppp_default'];
					}
				}

				$page_number = false;

				if( isset( $_GET['page_number'] ) && ! empty( $sc_args['paginate'] ) ) {
					$page_number = (int) $_GET['page_number'];

					add_action( 'woocommerce_before_shop_loop', array( $this, 'display_ajax_pagination_resut_count' ), 1000 );
				}

				if( class_exists( 'SitePress' ) ) {
					$this->maybe_add_wpml_adjustments();
					add_filter( 'woocommerce_shortcode_products_query', array( $this, 'add_wpml_to_shortcode_products_query' ), 10, 3 );
					add_filter( 'wcml_load_multi_currency_in_ajax', '__return_true', 10, 1 );
				}

				$this->build_ajax_queries();

				$this->setup_ajax_rewrites();

				$_GET = $this->get_url_query();

				add_filter( 'woocommerce_shortcode_products_query', array( $this, 'wc_shortcode_query' ), 10, 3 );

				add_action( 'woocommerce_before_shop_loop', array( $this, 'add_ajax_products_header' ), 5 );
				add_action( 'awf_add_ajax_products_header_title', array( $this, 'add_ajax_products_header_title' ) );
				add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'display_no_results_msg' ) );
				add_action( 'woocommerce_shortcode_sale_products_loop_no_results', array( $this, 'display_no_results_msg' ) );
				$this->edit_woocommerce_before_shop_loop();

				if( $page_number ) {
					$_GET['product-page'] = $page_number;
					if( version_compare( WC_VERSION, '3.3.3', '<' ) ) { set_query_var( 'product-page', $page_number ); }
				}

				add_filter( 'woocommerce_pagination_args', array( $this, 'adjust_wc_pagination' ) );
				add_filter( 'paginate_links', function( $href ) {
					$remove_pagination_args = array_keys( $_REQUEST );

					return remove_query_arg( $remove_pagination_args, $href );
				});

				/* Fix for the main WC "date" orderby implicitly implying "date-desc" */
				if( isset( $this->query->awf['orderby'] ) && 'date' === $this->query->awf['orderby'] ) {
					$this->query->awf['orderby'] = 'date-desc';
				}

				if( empty( intval( get_option( 'awf_counts_cache_days', '10' ) ) ) || ! empty( $this->query->awf['search'] ) ) {
					$sc_args['cache'] = false;
				}

				do_action( 'awf_ajax_filter_before_wc_products_shortcode' );

				$this->do_wc_products_shortcode( $sc_args );

			} else if( 'get_search_autocomplete' === $_GET['awf_action'] ) {
				if( isset( $_GET['awf_sc_page'] ) ) { $this->is_sc_page = (int) $_GET['awf_sc_page']; }

				$filter_data = sanitize_title( isset( $_GET['awf_filter'] ) && is_string( $_GET['awf_filter'] ) ? $_GET['awf_filter'] : '' );
				$filter_data = explode( '-filter-', $filter_data );
				$filter_data = array_pop( $filter_data );
				$filter_data = explode( '-', str_replace( '-wrapper', '', $filter_data) );

				if( ! isset( $filter_data[1] ) ) {
					echo '';
					die();
				}

				$filter = new A_W_F_filter( $filter_data[0], $filter_data[1] );

				if( empty( $filter->settings['autocomplete'] ) ) {
					echo '';
					die();
				}

				$this->initialize();

				$this->get_access_to['autocomplete_filter'] = $filter;
				$this->get_access_to['autocomplete_results_count'] = intval( $filter->settings['type_options']['autocomplete_results_count'] );
				if( empty( $this->get_access_to['autocomplete_results_count'] ) ) {
					$this->get_access_to['autocomplete_results_count'] = 5;
				}

				$sc_args = array(
					'paginate' => true,
					'columns' => 1,
					'limit' => $this->get_access_to['autocomplete_results_count'],
					'page' => 1,
					'cache' => false,
				);

				if( class_exists( 'SitePress' ) ) {
					$this->maybe_add_wpml_adjustments();
					add_filter( 'woocommerce_shortcode_products_query', array( $this, 'add_wpml_to_shortcode_products_query' ), 10, 3 );
					add_filter( 'wcml_load_multi_currency_in_ajax', '__return_true', 10, 1 );
				}

				if( empty( $filter->settings['type_options']['autocomplete_filtered'] ) ) {
					$search = isset( $_GET['awf_query'][$this->vars->awf['search']] ) ? $_GET['awf_query'][$this->vars->awf['search']] : '';
					$this->query->awf['search'] = is_string( $search ) ? sanitize_text_field( $search ) : '';

				} else {
					$this->build_ajax_queries();

					unset( $this->query->awf['ppp'] );
					if( ! isset( $this->query->awf['orderby'] ) ) {
						$sc_args['orderby'] = get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
					}
				}

				add_filter( 'woocommerce_shortcode_products_query', array( $this, 'wc_shortcode_query' ), 10, 3 );

				remove_all_actions( 'woocommerce_before_shop_loop' );
				remove_all_actions( 'woocommerce_before_shop_loop_item' );
				remove_all_actions( 'woocommerce_before_shop_loop_item_title' );
				remove_all_actions( 'woocommerce_after_shop_loop_item_title' );
				remove_all_actions( 'woocommerce_after_shop_loop_item' );
				remove_all_actions( 'woocommerce_after_shop_loop' );
				remove_all_actions( 'woocommerce_shortcode_products_loop_no_results' );

				add_action( 'woocommerce_shortcode_before_products_loop', array( $this, 'add_ac_taxonomy_wrapper' ), 5 );
				add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'add_ac_taxonomy_wrapper' ), 5 );
				add_action( 'woocommerce_shortcode_before_products_loop', array( $this, 'close_div' ), 15 );
				add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'close_div' ), 15 );

				add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
				if( ! empty( $filter->settings['type_options']['autocomplete_show_img'] ) ) {
					add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
				}
				if( ! empty( $filter->settings['type_options']['autocomplete_show_price'] ) ) {
					add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
				}
				if( ! empty( $filter->settings['type_options']['ac_display_product_cat'] ) ) {
					add_action( 'woocommerce_shortcode_before_products_loop', array( $this, 'add_ac_categories' ), 10 );
					add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'add_ac_categories' ), 10 );
				}
				if( ! empty( $filter->settings['type_options']['ac_display_product_tag'] ) ) {
					add_action( 'woocommerce_shortcode_before_products_loop', array( $this, 'add_ac_tags' ), 10 );
					add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'add_ac_tags' ), 10 );
				}
				if( ! empty( $filter->settings['type_options']['ac_products_header'] ) ) {
					add_action( 'woocommerce_shortcode_before_products_loop', array( $this, 'add_ac_products_header' ), 20 );
				}

				add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );

				if( ! empty( $filter->settings['type_options']['autocomplete_view_all'] ) ) {
					add_action( 'woocommerce_shortcode_after_products_loop', array( $this, 'add_search_autocomplete_all_results_link' ), 10 );
				}

				$this->do_wc_products_shortcode( $sc_args );

			} else if( 'request_url' === $_GET['awf_action'] ) {
				$response = array();

				$this->awf_settings['include_children'] = ( 'yes' === get_option( 'awf_include_children_on', 'yes' ) );
				$this->set_query_vars();
				$this->build_ajax_queries();
				$this->setup_ajax_rewrites();

				$url = $this->current_url;
				$url_filters = $this->url_query;

				if( $this->current_rewrites ) {
					$response['url'] = A_W_F::$front->get_url( $url, $url_filters );
				} else {
					$response['url'] = add_query_arg( $url_filters, $url );
				}

				wp_send_json( $response );

			} else if( 'update_filters' === $_GET['awf_action'] ) {

				$response = array();

				$this->awf_settings['include_children'] = ( 'yes' === get_option( 'awf_include_children_on', 'yes' ) );
				$this->set_query_vars();

				if( ! empty( $_GET['awf_sc_attrs'] ) && is_array( $_GET['awf_sc_attrs'] ) ) {
					if( ! empty( $_GET['awf_sc_attrs']['category'] ) && is_string( $_GET['awf_sc_attrs']['category'] ) ) {
						$this->get_access_to['sc_attributes'] = $_GET['awf_sc_attrs'];
						add_filter( 'awf_product_counts_query', array( $this, 'add_sc_attributes_to_product_counts' ) );
					}

					if( ! empty( $_GET['awf_sc_attrs']['awf_on_sale_sc'] ) ) {
						$_GET['awf_query'][$this->vars->awf['onsale']] = 'yes';
					}
				}

				if( class_exists( 'SitePress' ) ) {
					$this->maybe_add_wpml_adjustments();
					add_filter( 'wcml_load_multi_currency_in_ajax', '__return_true', 10, 1 );
				}

				$this->build_ajax_queries();

				if( isset( $_GET['awf_rewrite']['rewrite'] ) ) {
					$this->setup_canonical_parameters();
					$this->setup_ajax_rewrites();
				}

				$this->prepare_product_counts();

				if( ! empty( $_GET['awf_callers'] ) && is_array( $_GET['awf_callers'] ) ) {
					foreach( $_GET['awf_callers'] as $caller ) {
						$caller = sanitize_text_field( $caller );

						$pieces = substr( $caller, 0, -strlen( '-wrapper' ) );
						$pieces = explode( '-', $pieces );
						$preset_id = (int) array_pop( $pieces );

						if( isset( A_W_F::$presets[$preset_id] ) ) {
							$preset = new A_W_F_preset_frontend( $preset_id );

							if( ! empty( $_GET['awf_rewrite'] ) ) {
								$response['links'][$preset->id] = $preset->get_filters_links();
							}
						}
					}
				}

				if( isset( $this->get_access_to['nofollow'] ) ) {
					$response['nofollow'] = $this->get_access_to['nofollow'];
				}

				foreach( $this->counts as $counts_key => $counts ) {
					if( 0 === strpos( $counts_key, self::META_COUNTS_PREFIX ) ) {
						$meta_name = substr( $counts_key, strlen( self::META_COUNTS_PREFIX ) );
						if( isset( $this->vars->meta[$meta_name] ) ) { $response['counts'][$this->vars->meta[$meta_name]] = $counts; }

					} elseif( isset( $this->vars->tax[$counts_key] ) ) {
						$response['counts'][$this->vars->tax[$counts_key]] = $counts;
					}
				}

				if( 'yes' === get_option( 'awf_dynamic_price_ranges', 'no' ) ) {
					$response['price_filter_min_max'] = $this->get_price_filter_min_max();
				}

				wp_send_json( $response );
			}

			die();
		}

		protected function build_ajax_queries() {

			if( ! empty( $_GET['awf_query'] ) && is_array( $_GET['awf_query'] ) ) {

				foreach( $_GET['awf_query'] as $var => $value ) {
					if( ! is_scalar( $value ) ) { continue; }

					if( ( false !== ( $taxonomy = array_search( $var, $this->vars->tax, true ) ) ) ) {
						$this->query->tax[$taxonomy] = array_map( 'sanitize_text_field', explode( ',', $value ) );

					} elseif( false !== ( $awf_var_name = array_search( $var, $this->vars->awf, true ) ) ) {
						$this->query->awf[$awf_var_name] = sanitize_text_field( $value );

					} else if( false !== ( $meta_var_name = array_search( $var, $this->vars->meta, true ) ) ) {
						$this->query->meta[$meta_var_name] = array_map( 'sanitize_text_field', explode( ',', $value ) );

					} elseif( false !== ( $range_var_name = array_search( $var, $this->vars->range, true ) ) ) {
						$this->query->range[$range_var_name] = (float) $value;
					}
				}

				$this->set_numeric_taxonomy_ranges();
			}

			if(
				isset( $_GET['awf_query'][$this->vars->misc['archive']] )
				&& ! isset( $_GET['awf_rewrite'] )
				&& false !== ( $tax = array_search( $_GET['awf_archive_page'], $this->vars->tax ) )
				&& isset( $this->query->tax[$tax] )
			) {
				$this->is_archive = $this->url_page = $tax;
				$this->setup_archive( implode( ',', $this->query->tax[$tax] ) );
			}

			$this->sort_query();
		}

		public function initialize() {

			$this->permalinks_on = ! empty( get_option( 'permalink_structure' ) );
			$this->set_rewrites();

			if( $this->rewrites ) {
				add_action( 'parse_request', array( $this, 'remove_empty_rewrites' ) );
			}

			$this->shop_page_id = wc_get_page_id( 'shop' );
			if( 'page' === get_option( 'show_on_front' ) && intval( get_option( 'page_on_front' ) ) === $this->shop_page_id ) {
				$this->shop_on_frontpage = true;
			}

			$this->set_awf_settings();
			$this->set_query_vars();
			$this->setup_urls();

			if( isset( $_POST['awf_submit'] ) ) {
				$url_query = array();

				foreach( $_POST as $var => $value ) {
					if( is_array( $value ) ) { $value = implode( ',', $value ); }
					if( '' !== $value ) { $url_query[$var] = $value; }
				}

				if( isset( $_POST['awf_sc_page'] ) ) {
					$url = get_permalink( intval( $_POST['awf_sc_page'] ) );
					unset( $url_query['awf_sc_page'] );

				} elseif( isset( $_POST['awf_archive_page'] )
					&& false !== ( $tax = array_search( $_POST['awf_archive_page'], $this->vars->tax ) )
					&& isset( $url_query[$_POST['awf_archive_page']] )
			) {
					$this->is_archive = $tax;
					$this->setup_archive( $url_query[$_POST['awf_archive_page']] );
					$url = $this->current_url;
					$url_query = array_diff_key( $url_query, array( 'awf_archive_page' => '', $_POST['awf_archive_page'] => '' ) );
					$url_query[$this->vars->misc['archive']] = 1;

				} else {
					$url = $this->shop_url;
				}

				unset( $url_query['awf_submit'] );

				$url = add_query_arg( $url_query, $url );

				wp_redirect( esc_url_raw( $url ) );
				exit();
			}

			if( isset( $_REQUEST['awf_cm_v2'] ) ) {

				add_filter( 'get_pagenum_link', function( $url, $pagenum ) {

					if( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() && $this->current_rewrites ) {

						$parts = explode( '?', $url );
						$p = array_shift( $parts );

						if( ! empty( $p ) ) {
							global $wp_rewrite;
							$count = 0;

							$page = '/' . $wp_rewrite->pagination_base . '/' . $pagenum;
							$p = str_replace( $page, '', $p, $count );
							$p = $this->get_url( $p, $this->url_query );

							$parts = explode( '?', $p );
							$p = array_shift( $parts );
							$q = array_shift( $parts );

							if( $count > 0 ) {
								$p = rtrim( $p, '/' ) . $page;
							}

							$p = user_trailingslashit( $p );

							if( $q ) {
								$p .= '?' . $q;
							}

							$url = $p;
						}
					}

					$remove_params = array( 'awf_cm_v2', 'awf_rewrite' );
					if( $this->current_rewrites ) {
						$remove_params = array_merge( $remove_params, array_values( $this->current_rewrites ) );
					}

					$url = remove_query_arg( $remove_params, $url );

					return $url;

				}, 10, 2 );

				switch( get_option( 'awf_ajax_pagination', 'none' ) ) {
					case 'infinite_scroll':
					case 'more_button':
						add_action( 'woocommerce_before_shop_loop', array( $this, 'display_ajax_pagination_resut_count' ), 1000 );
						add_filter( 'render_block_woocommerce/product-results-count', array( $this, 'add_block_ajax_pagination_result_count' ) );

						if( ! isset( $_REQUEST['paged'] ) && ! isset( $_REQUEST['product-page'] ) ) {
							$this->add_ajax_compatibility_mode_support();
						}
						break;
					default:
						$this->add_ajax_compatibility_mode_support();
						break;
				}
			}

			if( $this->shop_on_frontpage && isset( $this->get_access_to['force_ppt_on_fps'] ) ) {
				add_action( 'parse_request', function( $q ) {

					if( $q->query_vars ) {
						$awf_vars = array( 'paged' );

						foreach( $this->vars as $arr ) {
							$awf_vars = array_merge( array_values( $awf_vars ), $arr );
						}

						if( ! array_diff( array_keys( $q->query_vars ), $awf_vars ) ) {
							$q->query_vars['post_type'] = 'product';
						}
					}

				});
			}
		}

		protected function set_awf_settings() {

			if( 0 < ( $this->awf_settings['shop_columns'] = (int) apply_filters( 'awf_set_shop_columns', get_option( 'awf_shop_columns', 0 ) ) ) && ! wp_doing_ajax() ) {
				add_filter( 'loop_shop_columns', array( $this, 'set_shop_columns' ), 20 );
			}

			$this->awf_settings['ppp_default'] = (int) apply_filters( 'awf_set_ppp_default', get_option( 'awf_ppp_default', 0 ) );
			if( ! wp_doing_ajax() ) { add_filter( 'loop_shop_per_page', array( $this, 'set_products_per_page' ), 20 ); }

			$this->awf_settings['include_children'] = ( 'yes' === get_option( 'awf_include_children_on', 'yes' ) );
		}

		public function set_query_vars() {
			$this->vars = (object) get_option( 'awf_query_vars', array( 'tax' => array(), 'awf' => array(), 'range' => array(), 'meta' => array(), 'misc' => array() ) );

			if( empty( $this->vars->misc['archive'] ) ) {
				A_W_F::build_query_vars();
				$this->vars = (object) get_option( 'awf_query_vars', array( 'tax' => array(), 'awf' => array(), 'range' => array(), 'meta' => array(), 'misc' => array() ) );
			}
		}

		public function register_shortcodes() {      
			add_shortcode( 'annasta_filters_toggle_button', array( $this, 'toggle_button_shortcode' ) );
		}

		public function toggle_button_shortcode( $atts ) {
			return '<div class="annasta-toggle-filters-button" style="display:none;"></div>';
		}

		public function register_query_vars( $vars ) {

			foreach( $this->vars->tax as $var ) {
				if( ! in_array( $var, $vars ) ) { $vars[] = $var; }
			}

			foreach( $this->vars->awf as $var ) {
				if( ! in_array( $var, $vars ) ) { $vars[] = $var; }
			}

			foreach( $this->vars->range as $var ) {
				if( ! in_array( $var, $vars ) ) { $vars[] = $var; }
			}

			foreach( $this->vars->meta as $var ) {
				if( ! in_array( $var, $vars ) ) { $vars[] = $var; }
			}

			foreach( $this->vars->misc as $var ) {
				if( ! in_array( $var, $vars ) ) { $vars[] = $var; }
			}

			return $vars;
		}

		public function setup_urls() {

			if( ! empty( $this->shop_on_frontpage ) ) {
				$this->shop_url = $this->current_url = get_home_url() . '/';

			} else {
				if( $this->permalinks_on ) {
					$this->shop_url = $this->current_url = get_permalink( $this->shop_page_id );
				} else {
					$this->shop_url = $this->current_url = get_post_type_archive_link( 'product' );
				}
			}

			if ( false !== $this->is_sc_page ) { $this->current_url = get_permalink( $this->is_sc_page ); }
		}

		/**
		 * Context for previews rendered outside of the frontend page flow (Elementor editor AJAX), where initialize() and before_wc_query() never run
		 *
		 * The preset is reset on every call: each preview renders its own (see the preview mode branch of get_preset_html()).
		 *
		 * @since 1.8.5
		 */
		public function prepare_preview_context() {
			$this->preset = null;

			if( ! is_null( $this->shop_url ) ) { return; }

			$this->permalinks_on = ! empty( get_option( 'permalink_structure' ) );
			$this->set_rewrites();

			$this->shop_page_id = wc_get_page_id( 'shop' );
			if( 'page' === get_option( 'show_on_front' ) && intval( get_option( 'page_on_front' ) ) === $this->shop_page_id ) {
				$this->shop_on_frontpage = true;
			}

			$this->set_awf_settings();
			$this->set_query_vars();
			$this->setup_urls();
			$this->url_query = array();
		}

		/**
		 * Removes the empty rewritten taxonomy query vars
		 *
		 * Query vars can hold arrays (?var[]=x), so each rewrite label is checked directly.
		 *
		 * @param WP $q
		 */
		public function remove_empty_rewrites( $q ) {
			foreach( reset( $this->rewrites ) as $parameter ) {
				if( isset( $this->vars->tax[$parameter] ) && isset( $q->query_vars[$this->vars->tax[$parameter]] ) && '' === $q->query_vars[$this->vars->tax[$parameter]] ) {
					unset( $q->query_vars[$this->vars->tax[$parameter]] );
				}
			}
		}

		public function wc_query( $query, $class_wc_query ) {
			// A_W_F::format_print_r($query);
		}

		public function before_wc_query( $query ) {

			if( ! $query->is_main_query() || is_admin() ) { return; }

			$is_shop = false;
			$product_taxonomies = array_keys( $this->vars->tax );
			$queried_taxonomies = empty( $query->tax_query->queried_terms ) ? array() : array_keys( $query->tax_query->queried_terms );

			if( $query->is_post_type_archive( 'product' ) ) {
				$is_shop = true;
				$this->filter_on = true;

			} elseif( $query->is_tax( $product_taxonomies ) ) {
				$this->is_archive = true;
				$this->filter_on = true;

				if( $this->rewrites ) {
					$rewrite_taxonomies = array_intersect( $queried_taxonomies, array_keys( $this->rewrites ) );
					if( 1 === count( $rewrite_taxonomies ) ) {
						$rewrite_page = array_shift( $rewrite_taxonomies );
					}
				}

			} else {

				if( ! empty( $query->get( 'page_id' ) ) ) {
					$this->is_wp_page = $query->get( 'page_id' ) . '';

				} elseif( $query->queried_object instanceof WP_Post ) {
					$this->is_wp_page = $query->queried_object_id . '';
				}

				if( ! empty( $this->shop_on_frontpage ) && ( $query->is_home() && ! (bool) $query->is_posts_page ) || (int) $this->is_wp_page === $this->shop_page_id ) {
					$is_shop = true;
					$this->filter_on = true;

				} elseif( in_array( $this->is_wp_page, get_option( 'awf_shortcode_pages', array() ) ) ) {
					$this->is_sc_page = $this->is_wp_page;
					$this->filter_on = true;

					$this->current_url = get_permalink( $this->is_wp_page );

					add_filter( 'woocommerce_shortcode_products_query', array( $this, 'wc_shortcode_query' ), 10, 3 );
					add_filter( 'shortcode_atts_products', array( $this, 'add_awf_sc_class' ), 10, 4 );
					add_filter( 'shortcode_atts_sale_products', array( $this, 'add_awf_sc_class' ), 10, 4 );
					$this->set_sc_attributes_from_content( $this->is_wp_page );
					add_action( 'woocommerce_shortcode_before_products_loop', array( $this, 'insert_sc_ajax_vars' ) );
					add_action( 'woocommerce_shortcode_before_sale_products_loop', array( $this, 'insert_sc_ajax_vars' ) );
					add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'insert_sc_ajax_vars' ) );
					add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'display_sc_page_no_results_message' ), 20 );
					add_action( 'woocommerce_shortcode_sale_products_loop_no_results', array( $this, 'insert_sc_ajax_vars' ) );
					add_action( 'woocommerce_shortcode_sale_products_loop_no_results', array( $this, 'display_sc_page_no_results_message' ), 20 );
				}
			}

			if( $this->filter_on ) {

				foreach( $query->query as $var => $value ) {
					if( false !== ( $var_name = array_search( $var, $this->vars->tax ) ) ) {

						if( is_array( $value ) ) {
							$terms = array_map( 'urldecode', $value );
							$terms = array_map( 'sanitize_text_field', $terms );
						} else {
							$terms = explode( ',', sanitize_text_field( urldecode( $value ) ) );
						}

						$this->query->tax[$var_name] = $terms;

					} else if( false !== ( $awf_var_name = array_search( $var, $this->vars->awf ) ) ) {
						if( is_string( $value ) ) {
							$this->query->awf[$awf_var_name] = sanitize_text_field( urldecode( $value ) );
						}

					} else if( false !== ( $meta_var_name = array_search( $var, $this->vars->meta ) ) ) {
						if( is_string( $value ) ) {
							$this->query->meta[$meta_var_name] = explode( ',', sanitize_text_field( urldecode( $value ) ) );
						}

					} else if( false !== ( $range_var_name = array_search( $var, $this->vars->range ) ) ) {
						$this->query->range[$range_var_name] = (float) $value;
					}
				}

				$this->set_numeric_taxonomy_ranges();

				if( empty( $this->query->awf['search'] ) && ! empty( $query->get( 's' ) ) ) {
					$this->query->awf['search'] = sanitize_text_field( urldecode( $query->query['s'] ) );

					if( ! isset( $_GET['type_aws'] ) ) {
						add_filter( 'posts_search', array( $this, 'remove_core_products_search' ), 1000, 2 );
					}
				}

				if( $this->is_archive ) {
					if(
						1 === count( array_intersect( $queried_taxonomies, $product_taxonomies ) )
						&& ( ! empty( $query->get( $this->vars->misc['archive'] ) ) || ! count( $this->query->tax ) || ! empty( $rewrite_page ) )
					) {
						$queried_object = $query->get_queried_object();
						$this->is_archive = $queried_object->taxonomy;
						$this->setup_archive( $query->get( $this->is_archive, '' ) );

						if( 'yes' === get_option( 'awf_archive_components_support', 'yes' ) ) {
							add_filter( 'woocommerce_page_title', array( $this, 'adjust_page_title' ) );
							add_filter( 'document_title_parts', array( $this, 'adjust_document_title' ) );
							add_filter( 'get_the_archive_title', array( $this, 'adjust_archive_title' ), 10, 2 );
							add_filter( 'render_block_core/term-description', array( $this, 'adjust_term_description_block' ), 10, 3 );
							add_filter( 'woocommerce_taxonomy_archive_description_raw', array( $this, 'adjust_taxonomy_archive_description' ), 10, 2 );
							add_filter( 'awf_js_data', function( $js_data ) {
								$js_data['archive_components_support'] = 'yes';

								return $js_data;
							} );
						}
						if( 'yes' === get_option( 'awf_breadcrumbs_support', 'yes' ) ) {
							add_filter( 'woocommerce_get_breadcrumb', array( $this, 'adjust_breadcrumbs' ), 10, 2 );
						}

					} else {
						foreach( $product_taxonomies as $t ) {
							if( isset( $query->tax_query->queried_terms[$t] ) ) {
								$this->query->tax[$t] = (array) $query->get( $t );
							}
						}

						$this->is_archive = false;
						$is_shop = true;
					}
				}

				if( $is_shop ) {
					if( empty( $this->get_access_to['block_pte'] ) ) {
						$query->set( 'post_type', 'product' );
						$query->is_post_type_archive = true;
						$query->is_archive = true;
					}

					add_filter( 'woocommerce_page_title', array( $this, 'adjust_page_title' ) );
					add_filter( 'document_title_parts', array( $this, 'adjust_document_title' ) );

					if( class_exists( 'SitePress' ) ) {
						$this->maybe_add_wpml_adjustments();
					}
				}

				if( false === $this->is_sc_page ) {
					if( 'yes' === get_option( 'awf_force_products_display_on', 'yes' ) ) { add_filter( 'woocommerce_is_filtered', '__return_true' ); }
					add_filter( 'woocommerce_product_query_tax_query', array( $this, 'set_wc_tax_query' ) );
					add_filter( 'woocommerce_product_query_meta_query', array( $this, 'set_wc_meta_query' ) );
					add_filter( 'loop_shop_post_in', array( $this, 'get_wc_post__in' ) );

					if( isset( $this->query->awf['stock'] ) && 'outofstock' === $this->query->awf['stock'] && 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) ) {
						add_filter( 'woocommerce_product_query_tax_query', array( $this, 'unhide_outofstock' ) );
					}
				}

				if( 'yes' === get_option( 'awf_force_published_status', 'yes' ) ) {
					$query->set( 'post_status', 'publish' );
				} else {
					if( ! current_user_can( 'manage_woocommerce' ) ) {
						$query->set( 'post_status', 'publish' );
					}
				}

				if( 'yes' === get_option( 'awf_add_canonical', 'no' ) ) { $this->setup_canonical_link(); }

				if( 'yes' === get_option( 'awf_add_noindex_filtered', 'no' ) || 'yes' === get_option( 'awf_add_noindex_no_results', 'no' ) ) {
					$this->setup_robots_noindex();
				}

				$this->sort_query();
				$this->url_query = $this->get_url_query();

				if( $this->is_archive ) {
					$this->url_page = $this->is_archive;
				} elseif( $this->is_sc_page ) {
					$this->url_page = 'awf_shortcode_page-' . $this->is_sc_page;
				}

				$this->setup_rewrites();
				$this->setup_canonical_parameters();

			} else {
				$this->url_query = array();
			}

		}

		protected function sort_query() {
			ksort( $this->query->tax );
			ksort( $this->query->awf );
			ksort( $this->query->meta );
			ksort( $this->query->range );

			array_walk( $this->query->tax, function( &$value, $key ) {
				if( is_array( $value ) ) { sort( $value ); }
			});

			array_walk( $this->query->meta, function( &$value, $key ) {
				if( is_array( $value ) ) { sort( $value ); }
			});
		}

		public function get_url_query() {

			$url_query = array();
			$delete_params = array( 's' => '', 'paged' => '', 'product-page' => '' );

			foreach( $this->query->tax as $var => $value ) {
				$url_query[$this->vars->tax[$var]] = implode( ',', $value );
			}

			foreach( $this->query->awf as $var => $value ) {
				$url_query[$this->vars->awf[$var]] = $value;
			}

			foreach( $this->query->meta as $var => $value ) {
				$url_query[$this->vars->meta[$var]] = implode( ',', $value );
			}

			foreach( $this->query->range as $var => $value ) {
				$url_query[$this->vars->range[$var]] = $value;

				$var = substr( $var, 4 );
				if( isset( $this->vars->tax[$var] ) ) { $delete_params[$this->vars->tax[$var]] = ''; }
			}

			if( $this->filter_on && 'yes' === get_option( 'awf_get_parameters_support', 'no' ) ) {
				$url_query = array_merge( $url_query, $_GET );
			}

			$url_query = array_diff_key( $url_query, $this->vars->tax, $delete_params );

			if( isset( $url_query[$this->vars->awf['search']] ) ) {
				$url_query[$this->vars->awf['search']] = str_replace( array( '\"', "\'" ), array( '"', "'" ), $url_query[$this->vars->awf['search']] );
			}

			if( $this->is_archive ) {
				$url_query[$this->vars->misc['archive']] = 1;

				if( $this->permalinks_on ) {
					unset( $url_query[$this->vars->tax[$this->is_archive]] );
				} else {
					if( ! is_bool( $this->is_archive ) ) {
						$url_query[$this->is_archive] = $url_query[$this->vars->tax[$this->is_archive]];
						unset( $url_query[$this->vars->tax[$this->is_archive]] );
					}
				}
			}

			ksort( $url_query );

			return $url_query;
		}

		/**
		 * Load the rewrite settings of the pages that have rewrite rules
		 *
		 * @since 1.8.5
		 */
		protected function set_rewrites() {
			$this->rewrites = array();

			if( ! $this->permalinks_on ) { return; }

			$rewrites = get_option( 'awf_rewrite_rules', array() );
			unset( $rewrites[0] );
			$rewrites = array_filter( $rewrites );

			$rewritten_pages = get_option( 'awf_rewritten_pages', false );
			if( is_array( $rewritten_pages ) ) {
				$rewrites = array_intersect_key( $rewrites, array_flip( $rewritten_pages ) );
			}

			$this->rewrites = $rewrites;
		}

		protected function setup_rewrites() {

			if( isset( $this->rewrites[$this->url_page] ) ) {
				$this->current_rewrites = array();

				foreach( $this->rewrites[$this->url_page] as $parameter ) {
					if( isset( $this->vars->tax[$parameter] ) ) {
						$this->current_rewrites[$parameter] = $this->vars->tax[$parameter];
					}
				}
			}

		}

		public function setup_ajax_rewrites() {

			$this->permalinks_on = ! empty( get_option( 'permalink_structure' ) );
			$this->set_rewrites();

			$this->shop_page_id = wc_get_page_id( 'shop' );
			if( 'page' === get_option( 'show_on_front' ) && intval( get_option( 'page_on_front' ) ) === $this->shop_page_id ) {
				$this->shop_on_frontpage = true;
			}

			$archive = false;

			if( isset( $_GET['awf_rewrite']['archive_page'] ) ) {
				$archive = array_search( urldecode( sanitize_title( $_GET['awf_rewrite']['archive_page'] ) ), $this->vars->tax );

				if( isset( $this->query->tax[$archive] ) ) {
					$this->is_archive = $this->url_page = $archive;
				}

			} elseif( isset( $_GET['awf_rewrite']['sc_page'] ) ) {
				$this->is_sc_page = (int) $_GET['awf_rewrite']['sc_page'];
				$this->url_page = 'awf_shortcode_page-' . $this->is_sc_page;
			}

			// $this->set_awf_settings();

			$this->setup_urls();
			if( $this->is_archive ) { $this->setup_archive( implode( ',', $this->query->tax[$this->is_archive] ) ); }
			$this->setup_rewrites();
			$this->url_query = $this->get_url_query();
		}

		/**
		 * Filters URL: the rewritten parameters of the page as path segments, the others as query arguments
		 *
		 * Rewrite segments already present in the URL (pagination links) are not repeated; they are compared
		 * as whole segments, so "color-red" doesn't match "color-red-dark".
		 *
		 * @param string $url
		 * @param array $parameters Query var => value
		 * @param string|false $page Rewrites page, the current one by default
		 * @return string
		 */
		public function get_url( $url, $parameters, $page = false ) {

			if( ! $page ) {
				$page = $this->url_page;
			}

			$rewrite_parameters = array();

			if( ! isset( $this->get_access_to['rewrite'][$page] ) ) {

				if( isset( $this->rewrites[$page] ) ) {
					$this->get_access_to['rewrite'][$page] = array_map( function( $v ) {
						return isset( $this->vars->tax[$v] ) ? $this->vars->tax[$v] : false;
					}, $this->rewrites[$page] );
					$this->get_access_to['rewrite'][$page] = array_filter( $this->get_access_to['rewrite'][$page] );
					$this->get_access_to['rewrite'][$page] = array_flip( $this->get_access_to['rewrite'][$page] );

				} else {
					$this->get_access_to['rewrite'][$page] = array();
				}
			}

			if( $this->get_access_to['rewrite'][$page] ) {
				$rewrites = array_intersect_key( $this->get_access_to['rewrite'][$page], $parameters ); // 1st array ensures proper sorting

				if( $rewrites ) {
					$url_segments = array_map( 'urldecode', explode( '/', trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ) ) );

					foreach( $rewrites as $parameter => $values ) {
						$rewrite_parameters[$parameter] = $parameter . '-' . $parameters[$parameter];
						unset( $parameters[$parameter] );

						if( in_array( urldecode( $rewrite_parameters[$parameter] ), $url_segments, true ) ) {
							$rewrite_parameters = array_diff_key( $rewrite_parameters, array( $parameter => '' ) );
						}
					}

					$url = trailingslashit( $url ) . implode( '/', $rewrite_parameters );
				}
			}

			ksort( $parameters );
			$url = user_trailingslashit( $url );
			$url = add_query_arg( $parameters, $url );

			return $url;
		}

		private function setup_archive( $archive_terms_string ) {
			$archive_terms = explode( ',', $archive_terms_string );

			foreach( $archive_terms as &$at ) {
				$at = urldecode( sanitize_title( $at ) );
			}
			unset( $at );

			if( 'no' === get_option( 'awf_hierarchical_archive_permalinks', 'no' ) ) {
				add_filter( 'term_link', array( $this, 'adjust_hierarchical_term_links' ), 10, 3 );

				if( class_exists( 'SitePress' ) ) {
					global $woocommerce_wpml;

					if( $woocommerce_wpml && isset( $woocommerce_wpml->url_translation ) ) {
						if( 'product_cat' === $this->is_archive || 'product_tag' == $this->is_archive ) {
							add_filter( 'term_link', [ $woocommerce_wpml->url_translation, 'translate_taxonomy_base' ], 15, 3 );

						} else {
							if( in_array( substr( $this->is_archive, 3 ), wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_name' ) ) ) {
								add_filter( 'term_link', [ $woocommerce_wpml->url_translation, 'translate_taxonomy_base' ], 15, 3 );
							} else {
								add_filter( 'term_link', array( $this, 'adjust_wpml_custom_taxonomy_base' ), 15, 3 );
							}
						}
					}
				}
			}

			$tl = get_term_link( reset( $archive_terms ), $this->is_archive );

			remove_filter( 'term_link', array( $this, 'adjust_hierarchical_term_links' ), 10 );
			remove_filter( 'term_link', array( $this, 'adjust_wpml_custom_taxonomy_base' ), 15 );

			if( is_wp_error( $tl ) ) {
				$this->current_url = $this->shop_url;

			} else {
				$this->current_url = user_trailingslashit( $tl );
				$this->current_url = urldecode( $this->current_url );

				if( 1 < count( $archive_terms ) ) {
					if( $this->permalinks_on ) {
						$replace = user_trailingslashit( '/' . reset( $archive_terms ) );
						$pos = strrpos( $this->current_url, $replace );
						if ( $pos !== false ) {
							$this->current_url = substr_replace( $this->current_url, user_trailingslashit( '/' . implode( ',', $archive_terms ) ), $pos, strlen( $replace ) );
						}
					}
				}
			}

			$this->query->tax[$this->is_archive] = $archive_terms;
		}

		/**
		 * Non-hierarchical term links for hierarchical taxonomies ("Hierarchical archive permalinks" off)
		 *
		 * WPML: WCML translates only the bases of product_cat, product_tag and the attributes (it treats any other
		 * taxonomy as an attribute); the other taxonomy bases are translated with the WPML core filter.
		 *
		 * @param string $termlink
		 * @param WP_Term $term
		 * @param string $taxonomy
		 * @return string
		 */
		public function adjust_hierarchical_term_links( $termlink, $term, $taxonomy ) {
			$slug = $term->slug;
			$t    = get_taxonomy( $taxonomy );

			if( isset( $t->rewrite['hierarchical'] ) && $t->rewrite['hierarchical'] ) {
				global $wp_rewrite;

				$termlink = $wp_rewrite->get_extra_permastruct( $taxonomy );
				if( $termlink ) {

					if( class_exists( 'SitePress' ) ) {
						global $woocommerce_wpml;

						if( $woocommerce_wpml && isset( $woocommerce_wpml->url_translation )
							&& ( in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) || taxonomy_is_product_attribute( $taxonomy ) )
						) {
							$base = $woocommerce_wpml->url_translation->get_translated_tax_slug( $taxonomy );
							if( ! empty( $base['translated_slug'] ) ) {
								$termlink = str_replace( $base['slug'], $base['translated_slug'], $termlink );
							} else {
								$termlink = A_W_F::wpml_translate_taxonomy_base_slug( $taxonomy, $termlink );
							}

						} else {
							$termlink = A_W_F::wpml_translate_taxonomy_base_slug( $taxonomy, $termlink );
						}
					}

					$termlink = str_replace( "%$taxonomy%", $slug, $termlink );
					$termlink = home_url( user_trailingslashit( $termlink, 'category' ) );
				}
			}

			return $termlink;
		}

		public function adjust_wpml_custom_taxonomy_base( $termlink, $term, $taxonomy ) {
			$t = get_taxonomy( $taxonomy );

			if( ! empty( $t->rewrite['slug'] ) ) {
				$base = rtrim( ltrim( $t->rewrite['slug'], '/' ), '/' );

				if( taxonomy_is_product_attribute( $taxonomy ) ) {
					$name = sprintf( 'URL attribute slug: %s', $base );
				} else {
					$name = sprintf( 'URL %s tax slug', $t->query_var );
				}

				$translated_base = apply_filters( 'wpml_translate_single_string', $base, 'WordPress', $name );

				if( $translated_base !== $base ) {
					$termlink = str_replace( '/' . $base . '/', '/' . $translated_base . '/', $termlink );
				}
			}

			return $termlink;
		}

		public function wc_shortcode_query( $args, $attrs, $type ) {

			if( isset( $this->query->awf['ppp'] ) ) {
				$args['posts_per_page'] = (int) $this->query->awf['ppp'];

				if( absint( get_option( 'awf_ppp_limit', '200' ) ) < $args['posts_per_page'] || -1 === $args['posts_per_page'] ) {
					$args['posts_per_page'] = absint( get_option( 'awf_ppp_limit', '200' ) );
				}
			}

			if( isset( $this->query->awf['stock'] ) && 'outofstock' === $this->query->awf['stock'] && 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) ) {
				$args['tax_query'] = $this->unhide_outofstock( $args['tax_query'] );
			}

			$tax_query = $this->set_wc_tax_query( array() );
			$args['tax_query'] = array_merge( $args['tax_query'], $tax_query );

			$meta_query = $this->set_wc_meta_query( array() );
			$args['meta_query'] = array_merge( $args['meta_query'], $meta_query );

			if( empty( $args['post__in'] ) ) { $args['post__in'] = array(); }
			$args['post__in'] = $this->get_wc_post__in( $args['post__in'] );

			if( isset( $this->query->awf['orderby'] ) ) {
				$pieces = explode( '-', $this->query->awf['orderby'] );
				if( empty( $pieces[1] ) ) { $pieces[1] = 'ASC'; } else {
					$pieces[1] = in_array( strtoupper( $pieces[1] ), array( 'ASC', 'DESC') ) ? strtoupper( $pieces[1] ) : 'ASC' ;
				}

				$ordering_args = WC()->query->get_catalog_ordering_args( $pieces[0], $pieces[1] );
				$args['orderby']        = $ordering_args['orderby'];
				$args['order']          = $ordering_args['order'];
				if ( $ordering_args['meta_key'] ) {
					$args['meta_key']       = $ordering_args['meta_key'];
				}
			}

			if( 'yes' === get_option( 'awf_force_published_status', 'yes' ) ) {
				$args['post_status'] = 'publish';

			} else {
				if( current_user_can( 'manage_woocommerce' ) ) {
					$args['post_status'] = array( 'publish', 'private' );
				} else {
					$args['post_status'] = 'publish';
				}
			}

			return $args;
		}

		public function set_wc_tax_query( $query ) {

			foreach( $this->query->tax as $var => $terms ) {
				$operator = get_option( 'awf_' . $var . '_query_operator', 'IN' );

				$query[] = array(
					'taxonomy' => $var,
					'field' => 'slug',
					'terms' => $terms,
					'operator' => $operator,
					'include_children' => 'AND' === $operator ? false : $this->awf_settings['include_children'],
				);
			}

			if( isset( $this->query->awf['stock'] ) ) {
				if( in_array( $this->query->awf['stock'], array( 'instock', 'outofstock' ) ) ) {
					if( 'no' === get_option( 'awf_variations_stock_support', 'no' ) ) {
						$this->{ 'set_visibility_' . $this->query->awf['stock'] }( $query );
					}
				}
			}

			if( isset( $this->query->awf['featured'] ) ) {
				$this->set_visibility_featured( $query );
			}

			return $query;
		}

		public function set_wc_meta_query( $query ) {

			if( isset( $this->query->awf['stock'] ) ) {
				if( 'no' === get_option( 'awf_variations_stock_support', 'no' ) ) {
					switch( $this->query->awf['stock'] ) {
						case 'onbackorder':
							$query[] = array(
								'key' => '_stock_status',
								'value' => 'onbackorder'
							);
							break;
						case 'instock':
							$query[] = array(
								'key' => '_stock_status',
								'value' => 'onbackorder',
								'compare' => 'NOT IN'
							);
							break;
						default:
							break;
					}
				}
			}

			if( ( isset( $this->query->range['min_price'] ) || isset( $this->query->range['max_price'] ) ) ) {

				if( version_compare( WC_VERSION, '3.6', '>=' ) ) {
					add_action( 'woocommerce_product_query', function() {
						remove_filter( 'posts_clauses', array( WC()->query, 'price_filter_post_clauses' ), 10 );
					} );
				}

				$min_price = isset( $this->query->range['min_price'] ) ? floatval( wp_unslash( $this->query->range['min_price'] ) ) : 0;
				$max_price = isset( $this->query->range['max_price'] ) ? floatval( wp_unslash( $this->query->range['max_price'] ) ) : PHP_INT_MAX;

				if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
					$tax_rates = WC_Tax::get_rates( '' );

					if ( $tax_rates ) {
						$min_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $min_price, $tax_rates ) );
						$max_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $max_price, $tax_rates ) );
					}
				}

				if( class_exists( 'SitePress' ) && function_exists( 'wcml_get_woocommerce_currency_option' ) ) {
					$default_currency = wcml_get_woocommerce_currency_option();
					$current_currency = apply_filters( 'wcml_price_currency', NULL );

					if( $default_currency !== $current_currency ) {
						$wcml_options = get_option( '_wcml_settings' );
						if( isset( $wcml_options['currency_options'][$current_currency]['rate'] ) ) {
							$exchange_rate = $wcml_options['currency_options'][$current_currency]['rate'];

							$min_price = $min_price / $exchange_rate;
							$max_price = $max_price / $exchange_rate;
						}
					}
				}

				$query['price_filter'] = array(
					'key'     => '_price',
					'value'   => array( $min_price, $max_price ),
					'compare' => 'BETWEEN',
					'type'    => 'DECIMAL(12,' . wc_get_price_decimals() . ')',
				);
			}

			if( ( isset( $this->query->range['min_rating'] ) || isset( $this->query->range['max_rating'] ) ) & ! isset( $query['awf_rating_filter']['awf_rating_filter'] ) ) {
				$range_min = isset( $this->query->range['min_rating'] ) ? (float) $this->query->range['min_rating'] : (float) 0.01;
				if( floatval(0) === $range_min ) { $range_min = (float) 0.01; }
				$range_max = isset( $this->query->range['max_rating'] ) ? (float) $this->query->range['max_rating'] : (float) 5;

				$query['awf_rating_filter'] = array(
					'key' => '_wc_average_rating',
					'value' => array( $range_min, $range_max ),
					'compare' => 'BETWEEN',
					'type' => 'DECIMAL(3,2)',
					'awf_rating_filter' => true,
				);
			}

			foreach( $this->vars->meta as $meta => $meta_name ) {
				if( isset( $this->query->meta[$meta] ) ) {

					if( 'awf_date_filter_' === substr( $meta, 0, 16) ) {

						if( empty( $this->query->meta[$meta] ) ) { continue; }

						$date_format = explode( '_', $meta );
						$date_format = $date_format[3];
						$date_formats = A_W_F::get_db_date_formats();

						if( ! isset( $date_formats[$date_format] ) ) { continue; }

						if( 2 === count( $this->query->meta[$meta] ) ) {

							$db_values = array( gmdate( $date_formats[$date_format]['format'], intval( $this->query->meta[$meta][0] ) ), gmdate( $date_formats[$date_format]['format'], intval( $this->query->meta[$meta][1] ) ) );
							$db_date_type = null;

							switch( $date_format ) {
								case( 'c' ):
								case( 'd' ):
									$db_date_type = 'DATE';
									break;

								case( 'e' ):
								case( 'f' ):
									$db_date_type = 'DATETIME';
									break;

								default:
									$db_date_type = 'NUMERIC';
									break;
							}

							$query[] = array(
								'key'     => substr( $meta, 18 ),
								'value'   => $db_values,
								'compare' => 'BETWEEN',
								'type' => $db_date_type,
							);

						} else {

							$db_date_type = null;
							$db_compare = null;

							switch( $date_format ) {
								case( 'c' ):
								case( 'd' ):
									$db_date_type = 'DATE';
									$db_compare = '=';
									break;

								case( 'e' ):
								case( 'f' ):
									$db_date_type = 'DATETIME';
									$db_compare = '=';
									break;

								default:
									$db_date_type = false;
									$db_compare = 'IN';
									break;
							}

							$meta_query = array(
								'key'         => substr( $meta, 18 ),
								'value'       => gmdate( $date_formats[$date_format]['format'], intval( $this->query->meta[$meta][0] ) ),
								'db_compare'  => $db_compare,
							);
							if( $db_date_type ) { $meta_query['type'] = $db_date_type; }

							$query[] = $meta_query;
						}

					} else {
						$query[] = array(
							'key'     => $meta,
							'value'   => $this->query->meta[$meta],
							'compare' => 'IN',
						);
					}

				} elseif( isset( $this->query->range['min_' . $meta] ) && isset( $this->query->range['max_' . $meta] ) ) {
					$query[] = array(
						'key' => $meta,
						'compare' => 'EXISTS',
					);

					$query[] = array(
						'key' => $meta,
						'value' => '',
						'compare' => '!=',
					);

					$query[] = array(
						'key' => $meta,
						'value' => array( $this->query->range['min_' . $meta], $this->query->range['max_' . $meta] ),
						'compare' => 'BETWEEN',
						'type' => 'numeric',
					);
				}
			}

			return $query;
		}

		protected function set_numeric_taxonomy_ranges() {}

		/**
		 * Product ids restricting the products queries: on sale products and the string search results
		 *
		 * On the Advanced Woo Search results page (type_aws) AWS runs the main query itself, so its results
		 * are only added to the cached ids (counts) once the main query is done.
		 *
		 * @param array $post__in
		 * @return array
		 */
		public function get_wc_post__in( $post__in ) {

			if( isset( $this->get_access_to['post__in_cache'] ) ) {
				$post__in = $this->get_access_to['post__in_cache'];

			} else {

				if( isset( $this->query->awf['onsale'] ) ) {
					$onsale_posts = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
					if( empty( $post__in ) ) { $post__in = $onsale_posts;
					} else { $post__in = array_intersect( $post__in, $onsale_posts ); }
				}

				if( ! empty( $this->query->awf['search'] ) ) {

					$ss_posts = array();
					$ss_bypass = false;
					$search = wp_unslash( $this->query->awf['search'] );

					switch( get_option( 'awf_ss_engine', 'wc' ) ) {
						case 'wc_extended':
							$ss_posts = $this->get_search_results( 'wc_extended', $search );
							break;

						case 'relevanssi':
							if( function_exists( 'relevanssi_do_query' ) ) {
								$ss_posts = $this->get_search_results( 'relevanssi', $search );
							}

							break;

						case 'aws':
							if( function_exists( 'aws_search' ) ) {
								if( isset( $_GET['type_aws'] ) ) {
									$ss_bypass = true;

									add_action( 'wp', function() use( $search ) {
										$aws_posts = $this->get_search_results( 'aws', $search );

										if( empty( $this->get_access_to['post__in_cache'] ) ) {
											$this->get_access_to['post__in_cache'] = $aws_posts;
										} else {
											$this->get_access_to['post__in_cache'] = array_merge( array( 0 ), array_intersect( $this->get_access_to['post__in_cache'], $aws_posts ) );
										}
									}, 10 );

								} else {
									$ss_posts = $this->get_search_results( 'aws', $search );
								}
							}

							break;

						default: break;
					}

					if( ! $ss_bypass ) {
						if( empty( $ss_posts ) ) {
							$ss_posts = $this->get_search_results( 'wc', $search );
						}

						if( empty( $post__in ) ) { $post__in = $ss_posts;
						} else { $post__in = array_merge( array( 0 ), array_intersect( $post__in, $ss_posts ) ); }

					}

				}

				$this->get_access_to['post__in_cache'] = $post__in;
			}

			if( 'yes' === get_option( 'awf_variations_stock_support', 'no' ) ) {
				$this->add_stock_posts_with_variations( $post__in );
			}

			$post__in = array_unique( $post__in );

			return $post__in;
		}

		/**
		 * Native shop searches (?s=): removes the core search clause of the main query
		 *
		 * The core search only looks into titles, excerpts and contents, so it would drop the SKU and
		 * meta data matches of the plugin search. Applied only when the plugin search restricts the query
		 * (non-empty post__in); the search terms stay in the query, so the relevance ordering is kept.
		 *
		 * @since 1.8.5
		 * @param string $search
		 * @param WP_Query $query
		 * @return string
		 */
		public function remove_core_products_search( $search, $query ) {
			if( $query->is_main_query() && $query->is_search() && isset( $this->get_access_to['post__in_cache'] ) && ! empty( $query->get( 'post__in' ) ) ) {
				return '';
			}

			return $search;
		}

		/**
		 * Product ids found by a string search engine for the "String search in" sources
		 *
		 * - WooCommerce: its own search_products() for the default sources (title & content + SKU), the plugin's query otherwise;
		 *   "WooCommerce extended" matches any of the search terms.
		 * - Relevanssi: its index columns restricted to the content / terms sources; SKU (a Relevanssi custom field only when
		 *   indexed as such) and meta data through the plugin's query.
		 * - Advanced Woo Search: its sources set for the call; meta data through the plugin's query (no custom fields in AWS free).
		 * The 3d party engines search all of their index until "String search in" is saved.
		 *
		 * Cached in transients with the counts prefix, so the counts cache resets and cleanup apply.
		 *
		 * @since 1.8.5
		 * @param string $engine wc, wc_extended, relevanssi or aws
		 * @param string $search Unslashed search string
		 * @return array Product ids led by 0, so that a search without results still restricts the query
		 */
		protected function get_search_results( $engine, $search ) {

			$cache_name = '';
			$lifespan = intval( get_option( 'awf_counts_cache_days', '10' ) );
			$sources = $this->get_search_sources();

			if( ! empty( $lifespan ) ) {
				$cache_key = array( 'engine' => $engine, 'search' => $search, 'sources' => $sources );

				if( ! empty( $this->get_nondefault_language() ) ) { $cache_key['lang'] = $this->language; }
				if( current_user_can( 'read_private_products' ) ) { $cache_key['private'] = 1; }

				$cache_name = 'awf_counts_' . A_W_F::get_cache_version() . '_search_' . md5( wp_json_encode( $cache_key ) );

				if( isset( $this->get_access_to['search_results'][$cache_name] ) ) {
					return $this->get_access_to['search_results'][$cache_name];
				}

				$ids = get_transient( $cache_name );
				if( is_array( $ids ) ) { return $this->get_access_to['search_results'][$cache_name] = $ids; }
			}

			switch( $engine ) {
				case 'relevanssi':
					$engine_sources = $sources['restrict'] ? array_values( array_intersect( $sources['core'], array( 'content', 'terms' ) ) ) : null;
					$ids = ( is_null( $engine_sources ) || ! empty( $engine_sources ) ) ? $this->get_relevanssi_search_ids( $search, $engine_sources ) : array();

					if( $sources['restrict'] ) {
						$ids = array_merge( $ids, $this->search_product_sources( $search, array_values( array_intersect( $sources['core'], array( 'sku' ) ) ), $sources['meta'] ) );
					}

					break;

				case 'aws':
					$engine_sources = $sources['restrict'] ? $sources['core'] : null;
					$ids = ( is_null( $engine_sources ) || ! empty( $engine_sources ) ) ? $this->get_aws_search_ids( $search, $engine_sources ) : array();

					$ids = array_merge( $ids, $this->search_product_sources( $search, array(), $sources['meta'] ) );
					break;

				case 'wc_extended':
					$ids = $this->search_product_sources( $search, $sources['core'], $sources['meta'], 'OR' );
					break;

				default:
					if( empty( $sources['meta'] ) && array( 'content', 'sku' ) === $sources['core'] ) {
						$data_store = WC_Data_Store::load( 'product' );
						$ids = $data_store->search_products( wc_clean( $search ), '', true, true );

					} else {
						$ids = $this->search_product_sources( $search, $sources['core'], $sources['meta'] );
					}

					break;
			}

			$ids = array_values( array_unique( array_merge( array( 0 ), array_map( 'absint', (array) $ids ) ) ) );

			if( ! empty( $cache_name ) ) {
				set_transient( $cache_name, $ids, DAY_IN_SECONDS * $lifespan );

				if( false === get_transient( 'awf_counts_cleanup' ) ) {
					A_W_F::schedule_counts_cache_cleanup();
				}

				$this->get_access_to['search_results'][$cache_name] = $ids;
			}

			return $ids;
		}

		/**
		 * Advanced Woo Search product ids, without its results limit (100 by default)
		 *
		 * AWS versions without the 'ids' output return all the results data: the ids are then taken from its products.
		 *
		 * @since 1.8.5
		 * @param string $search
		 * @param array|null $sources "String search in" core sources, null for the AWS settings
		 * @return array
		 */
		protected function get_aws_search_ids( $search, $sources = null ) {

			add_filter( 'aws_page_results', array( $this, 'get_aws_results_limit' ), 1000 );

			if( ! is_null( $sources ) ) {
				$this->get_access_to['aws_sources'] = $sources;
				add_filter( 'aws_search_data_parameters', array( $this, 'set_aws_search_sources' ), 1000 );
			}

			$results = aws_search( $search, 'ids' );

			remove_filter( 'aws_page_results', array( $this, 'get_aws_results_limit' ), 1000 );
			remove_filter( 'aws_search_data_parameters', array( $this, 'set_aws_search_sources' ), 1000 );

			if( ! is_array( $results ) ) { return array(); }

			if( isset( $results['products'] ) ) {
				return is_array( $results['products'] ) ? wp_list_pluck( $results['products'], 'id' ) : array();
			}

			return $results;
		}

		public function get_aws_results_limit( $limit ) {
			return absint( apply_filters( 'awf_ss_results_limit', 10000 ) );
		}

		/**
		 * Advanced Woo Search sources of the "String search in" setting
		 *
		 * @since 1.8.5
		 * @param array $data AWS search data
		 * @return array
		 */
		public function set_aws_search_sources( $data ) {
			$map = array(
				'content' => array( 'title', 'content', 'excerpt' ),
				'sku'     => array( 'sku' ),
				'terms'   => array( 'category', 'tag' ),
			);

			$search_in = array();

			foreach( (array) $this->get_access_to['aws_sources'] as $source ) {
				if( isset( $map[$source] ) ) { $search_in = array_merge( $search_in, $map[$source] ); }
			}

			$data['search_in'] = $search_in;

			return $data;
		}

		/**
		 * Relevanssi product ids, optionally restricted to the index columns of the "String search in" sources
		 *
		 * post_type is passed along with Relevanssi's post_types, so that the query still searches products
		 * if Relevanssi doesn't take it. Variations are replaced by their parents.
		 *
		 * @since 1.8.5
		 * @param string $search
		 * @param array|null $sources content / terms, null for all of the index
		 * @return array
		 */
		protected function get_relevanssi_search_ids( $search, $sources = null ) {

			if( ! is_null( $sources ) ) {
				$this->get_access_to['relevanssi_sources'] = $sources;
				add_filter( 'relevanssi_where', array( $this, 'restrict_relevanssi_sources' ), 1000 );
			}

			$sq = new WP_Query( array(
				's'              => $search,
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'post_type'      => array( 'product', 'product_variation' ),
				'post_types'     => array( 'product', 'product_variation' ),
				'relevanssi'     => true,
			) );

			remove_filter( 'relevanssi_where', array( $this, 'restrict_relevanssi_sources' ), 1000 );

			return $this->map_variations_to_parents( $sq->posts );
		}

		/**
		 * relevanssi_where: index rows of the title / content / excerpt columns, and of the product categories and tags
		 * (taxonomy_detail holds a JSON object of the matched taxonomies)
		 *
		 * @since 1.8.5
		 * @param string $query_restrictions
		 * @return string
		 */
		public function restrict_relevanssi_sources( $query_restrictions ) {
			$conditions = array();

			foreach( (array) $this->get_access_to['relevanssi_sources'] as $source ) {
				if( 'content' === $source ) {
					$conditions[] = 'relevanssi.title > 0 OR relevanssi.content > 0 OR relevanssi.excerpt > 0';

				} elseif( 'terms' === $source ) {
					$conditions[] = "relevanssi.taxonomy_detail LIKE '%\"product_cat\"%' OR relevanssi.taxonomy_detail LIKE '%\"product_tag\"%'";
				}
			}

			if( ! empty( $conditions ) ) {
				$query_restrictions .= ' AND ( ' . implode( ' OR ', $conditions ) . ' )';
			}

			return $query_restrictions;
		}

		/**
		 * Sources of the "String search in" setting
		 *
		 * Before the setting is saved: title & content + SKU (the WooCommerce search), 3d party engines unrestricted.
		 *
		 * @since 1.8.5
		 * @return array 'core' (content / sku / terms), 'meta' (meta keys), 'restrict' (false before the setting is saved)
		 */
		protected function get_search_sources() {

			if( isset( $this->get_access_to['search_sources'] ) ) { return $this->get_access_to['search_sources']; }

			$saved = get_option( 'awf_ss_sources', false );
			$core = $meta = array();

			foreach( ( false === $saved ? array( 'content', 'sku' ) : (array) $saved ) as $source ) {
				if( in_array( $source, array( 'content', 'sku', 'terms' ), true ) ) {
					$core[] = $source;

				} elseif( is_string( $source ) && 0 === strpos( $source, 'meta--' ) && 6 < strlen( $source ) ) {
					$meta[] = substr( $source, 6 );
				}
			}

			if( empty( $core ) && empty( $meta ) ) { $core = array( 'content' ); }

			$core = array_values( array_unique( $core ) );
			$meta = array_values( array_unique( $meta ) );
			sort( $core );
			sort( $meta );

			return $this->get_access_to['search_sources'] = array( 'core' => $core, 'meta' => $meta, 'restrict' => ( false !== $saved ) );
		}

		/**
		 * Product ids matching the search string in the given sources
		 *
		 * Follows WooCommerce's search: "or" groups, quoted phrases, stopwords, and the whole group matched as a sentence
		 * when it has only short terms / stopwords or 10+ terms. Each term (OR relation: any term) has to match one of the
		 * sources. Variations count for their parents; a numeric search also finds the product of that id.
		 *
		 * @since 1.8.5
		 * @param string $search
		 * @param array $sources content / sku / terms
		 * @param array $meta_keys
		 * @param string $relation AND (all terms) or OR (any term)
		 * @return array
		 */
		protected function search_product_sources( $search, $sources, $meta_keys, $relation = 'AND' ) {
			global $wpdb;

			if( empty( $sources ) && empty( $meta_keys ) ) { return array(); }

			$has_gtin = defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '9.2', '>=' );
			$term_groups = stristr( $search, ' or ' ) ? preg_split( '/\s+or\s+/i', $search ) : array( $search );
			$search_queries = array();

			foreach( $term_groups as $term_group ) {
				$terms = array( $term_group );

				if( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $term_group, $matches ) ) {
					$valid_terms = $this->get_valid_search_terms( $matches[0] );

					if( 0 < count( $valid_terms ) && 10 > count( $valid_terms ) ) { $terms = $valid_terms; }
				}

				$term_queries = array();

				foreach( $terms as $term ) {
					$like = '%' . $wpdb->esc_like( $term ) . '%';
					$conditions = array();

					if( in_array( 'content', $sources, true ) ) {
						$conditions[] = $wpdb->prepare( '( posts.post_title LIKE %s OR posts.post_excerpt LIKE %s OR posts.post_content LIKE %s )', $like, $like, $like );
					}

					if( in_array( 'sku', $sources, true ) ) {
						$conditions[] = $has_gtin ? $wpdb->prepare( '( lookup.sku LIKE %s OR lookup.global_unique_id LIKE %s )', $like, $like ) : $wpdb->prepare( '( lookup.sku LIKE %s )', $like );
					}

					if( in_array( 'terms', $sources, true ) ) {
						$conditions[] = $wpdb->prepare(
							"EXISTS ( SELECT 1 FROM {$wpdb->term_relationships} tr
							INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy IN ( 'product_cat', 'product_tag' )
							INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
							WHERE tr.object_id = posts.ID AND t.name LIKE %s )",
							$like
						);
					}

					if( ! empty( $meta_keys ) ) {
						$placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
						// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- placeholders built above
						$conditions[] = $wpdb->prepare( "EXISTS ( SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = posts.ID AND pm.meta_key IN ( {$placeholders} ) AND pm.meta_value LIKE %s )", array_merge( $meta_keys, array( $like ) ) );
					}

					$term_queries[] = '( ' . implode( ' OR ', $conditions ) . ' )';
				}

				$search_queries[] = implode( ( 'OR' === $relation ? ' OR ' : ' AND ' ), $term_queries );
			}

			$join = in_array( 'sku', $sources, true ) ? "LEFT JOIN {$wpdb->wc_product_meta_lookup} lookup ON lookup.product_id = posts.ID" : '';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- conditions prepared above
			$ids = $wpdb->get_col( "SELECT DISTINCT IF( posts.post_type = 'product_variation', posts.post_parent, posts.ID ) FROM {$wpdb->posts} posts {$join} WHERE posts.post_type IN ( 'product', 'product_variation' ) AND ( ( " . implode( ' ) OR ( ', $search_queries ) . " ) )" );

			if( in_array( 'content', $sources, true ) && is_numeric( $search ) ) {
				$ids = array_merge( $ids, $this->map_variations_to_parents( array( absint( $search ) ) ) );
			}

			return $ids;
		}

		/**
		 * Search terms without the quotes, single letters and stopwords (WooCommerce's rules, WC_Data_Store_WP::get_valid_search_terms())
		 *
		 * The stopwords are WooCommerce's list in its own translation.
		 *
		 * @since 1.8.5
		 * @param array $terms
		 * @return array
		 */
		protected function get_valid_search_terms( $terms ) {
			$valid_terms = array();

			// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			$stopwords = array_map( 'wc_strtolower', array_map( 'trim', explode( ',', _x( 'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www', 'Comma-separated list of search stopwords in your language', 'woocommerce' ) ) ) );
			$stopwords = apply_filters( 'wp_search_stopwords', $stopwords );

			foreach( $terms as $term ) {
				$term = preg_match( '/^".+"$/', $term ) ? trim( $term, "\"'" ) : trim( $term, "\"' " );

				if( '' === $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) ) { continue; }
				if( in_array( wc_strtolower( $term ), $stopwords, true ) ) { continue; }

				$valid_terms[] = $term;
			}

			return $valid_terms;
		}

		/**
		 * Replaces the variation ids of a list with their parent product ids
		 *
		 * @since 1.8.5
		 * @param array $ids
		 * @return array
		 */
		protected function map_variations_to_parents( $ids ) {
			global $wpdb;

			$ids = array_filter( array_map( 'absint', (array) $ids ) );
			if( empty( $ids ) ) { return array(); }

			$parents = array();

			foreach( array_chunk( $ids, 5000 ) as $chunk ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- absint ids
				$rows = $wpdb->get_results( "SELECT ID, post_parent FROM {$wpdb->posts} WHERE ID IN (" . implode( ',', $chunk ) . ") AND post_type = 'product_variation'" );

				foreach( $rows as $row ) {
					$parents[(int) $row->ID] = (int) $row->post_parent;
				}
			}

			foreach( $ids as $i => $id ) {
				if( isset( $parents[$id] ) ) { $ids[$i] = $parents[$id]; }
			}

			return array_values( array_unique( $ids ) );
		}

		public function unhide_outofstock( $tax_query ) {

			foreach( $tax_query as $i => $q ) {
				if( is_array( $q ) && isset( $q['taxonomy'] ) && 'product_visibility' === $q['taxonomy'] && isset( $q['operator'] ) && 'NOT IN' === $q['operator'] ) {
					if( isset( $q['terms'] ) && is_array( $q['terms'] ) && isset( $q['field'] ) && 'term_taxonomy_id' === $q['field'] ) {
						$product_visibility_terms  = wc_get_product_visibility_term_ids();
						$tax_query[$i]['terms'] = array_diff( $q['terms'], array( $product_visibility_terms['outofstock'] ) );

						add_filter( 'woocommerce_product_is_visible', array( $this, 'adjust_outofstock_visibility' ), 20, 2 );
					}
				}
			}

			return $tax_query;
		}

		public function adjust_outofstock_visibility( $visible, $product_id ) {
			$product = wc_get_product( $product_id );

			if( $product ) {
				/* Remove the woocommerce_hide_out_of_stock_items condition from WC_Product > is_visible_core */
				$visible = true;

				$visible = 'visible' === $product->get_catalog_visibility() || ( is_search() && 'search' === $product->get_catalog_visibility() ) || ( ! is_search() && 'catalog' === $product->get_catalog_visibility() );

				if ( 'trash' === $product->get_status() ) {
					$visible = false;
				} elseif ( 'publish' !== $product->get_status() && ! current_user_can( 'edit_post', $product->get_id() ) ) {
					$visible = false;
				}

				if ( $product->get_parent_id() ) {
					$parent_product = wc_get_product( $product->get_parent_id() );

					if ( $parent_product && 'publish' !== $parent_product->get_status() ) {
						$visible = false;
					}
				}	
			}

			return $visible;
		}

		public function set_default_visibility( &$tax_query ) {
			$terms = array( 'exclude-from-catalog' );
			if( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) && ( ! isset( $this->query->awf['stock'] ) || 'all' === $this->query->awf['stock'] ) ) {
				if( 'no' === get_option( 'awf_variations_stock_support', 'no' ) ) {
					$terms[] = 'outofstock';
				}
			}

			$tax_query[] = array(
				'taxonomy'         => 'product_visibility',
				'terms'            => $terms,
				'field'            => 'name',
				'operator'         => 'NOT IN',
				'include_children' => false,
			);
		}

		public function set_visibility_instock( &$tax_query ) {
			if( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) ) { return; }

			$tax_query[] = array(
					'taxonomy'         => 'product_visibility',
					'terms'            => array( 'outofstock' ),
					'field'            => 'name',
					'operator'         => 'NOT IN',
					'include_children' => false,
			);
		}

		public function set_visibility_outofstock( &$tax_query ) {

			$tax_query[] = array(
					'taxonomy'         => 'product_visibility',
					'terms'            => array( 'outofstock' ),
					'field'            => 'name',
					'operator'         => 'IN',
					'include_children' => false,
			);
		}

		public function set_visibility_featured( &$tax_query ) {
			$tax_query[] = array(
				'taxonomy'         => 'product_visibility',
				'terms'            => 'featured',
				'field'            => 'name',
				'operator'         => 'IN',
				'include_children' => false,
			);
		}

		private function add_stock_posts_with_variations( &$post__in ) {

			$condition = $this->get_vss_stock_condition();
			if( false === $condition ) { return; }

			list( $availability, $operator ) = $condition;

			if( 'yes' === get_option( 'woocommerce_attribute_lookup_enabled', 'no' ) ) {

				global $wpdb;

				$sets = $this->get_vss_lookup_sets( $availability, $operator, $this->get_query_attributes_filters() );
				$products = $sets['products'];
				$variations = $sets['variations'];

				if( ! empty( $variations ) ) {
					$variations_in = implode( ',', array_map( 'intval', $variations ) );

					$variations_parents = $wpdb->get_col(
						"SELECT DISTINCT posts.post_parent FROM {$wpdb->posts} as posts
						WHERE posts.post_type = 'product_variation'
							AND posts.ID IN ({$variations_in})
						LIMIT 30000"
					);

					$products = array_merge( $products, $variations_parents );
				}

			} else {
				$products = $this->get_stock_posts_with_variations_without_lookup( $availability, $operator );
			}

			if( class_exists( 'SitePress' ) && ! empty( $this->get_nondefault_language() ) ) {
				$current_lang_ids = array();
				foreach( $products as $id ) {
					$current_lang_ids[] = apply_filters( 'wpml_object_id', $id, 'product', TRUE, $this->language );
				}

				$products = array_filter( $current_lang_ids );
			}

			if( empty( $post__in ) ) {
				$post__in = array_merge( array( 0 ), $products );
			} else {
				$post__in = array_merge( array( 0 ), array_intersect( $post__in, $products ) );
			}

		}

		/**
		 * Stock condition of the variations stock support
		 *
		 * @since 1.8.5
		 * @return array|false array( availability, operator ), false without a stock condition
		 */
		protected function get_vss_stock_condition() {
			if( isset( $this->query->awf['stock'] ) ) {
				return array( $this->query->awf['stock'], 'IN' );

			} elseif( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) ) {
				return array( 'outofstock', 'NOT IN' );
			}

			return false;
		}

		/* Active attribute filters of the query */
		protected function get_query_attributes_filters() {
			return array_filter( $this->query->tax, function( $v, $k ) {
				if( 0 === strpos( $k, 'pa_' ) && ! empty( $v ) ) { return true; }
				return false;
			}, ARRAY_FILTER_USE_BOTH );
		}

		/**
		 * Variations stock support with the attributes lookup table: the products available without variations,
		 * and the available variations not excluded by the attribute filters
		 *
		 * - Non-variable products: the "variable" product type term is looked up by slug (its name could match terms of other taxonomies).
		 * - Variations: all the enabled (published) ones with the stock status, as the attributes lookup table only holds
		 *   the variations of taxonomy attributes (products with custom attributes only would be missing).
		 * - Attribute filters remove the variations defined by other terms of the taxonomy. "Any <attribute>" variations have
		 *   one row for every term of their product, so they stay when one of those terms is selected. WPML: translated variations
		 *   have rows with the translated terms, untranslated ones with the default language terms, so both are used.
		 * Both sets are cached in transients versioned with the counts cache (valid in persistent object caches too).
		 *
		 * @since 1.8.5
		 * @param string $availability
		 * @param string $operator IN / NOT IN
		 * @param array $attributes_filters Attribute taxonomy => selected slugs
		 * @return array 'products' => non-variable product ids, 'variations' => variation ids
		 */
		protected function get_vss_lookup_sets( $availability, $operator, $attributes_filters ) {

				global $wpdb;

				$availability = esc_sql( $availability );
				$operator = 'NOT IN' === $operator ? 'NOT IN' : 'IN';
				$vss_cache_suffix = A_W_F::get_cache_version() . '_' . sanitize_key( $operator . $availability );

				$products = get_transient( 'awf_vss_products_cache_' . $vss_cache_suffix );

				if( false === $products ) {
					$variable_term = get_term_by( 'slug', 'variable', 'product_type' );
					$variable_tt_id = $variable_term instanceof WP_Term ? (int) $variable_term->term_taxonomy_id : 0;

					if( empty( $variable_tt_id ) ) {
						/* Alternative backup procedure */

						$sql = $wpdb->prepare(
							"SELECT DISTINCT posts.ID AS product_id FROM {$wpdb->posts} as posts
								INNER JOIN {$wpdb->prefix}wc_product_meta_lookup AS products_lookup ON posts.ID = products_lookup.product_id
							WHERE
								posts.post_type = 'product'
								AND posts.ID NOT IN (SELECT DISTINCT post_parent FROM {$wpdb->posts} as p2 WHERE p2.post_type = %s AND p2.post_parent > 0)
								AND products_lookup.stock_status {$operator} ('{$availability}')
							LIMIT 30000",
							'product_variation'
						);

					} else {
						$sql = $wpdb->prepare(
							"SELECT DISTINCT posts.ID AS product_id FROM {$wpdb->posts} as posts
								INNER JOIN {$wpdb->prefix}wc_product_meta_lookup AS products_lookup ON posts.ID = products_lookup.product_id
							WHERE
								posts.post_type = 'product'
								AND posts.ID NOT IN (SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d)
								AND products_lookup.stock_status {$operator} ('{$availability}')
							LIMIT 30000",
							$variable_tt_id
						);
					}

					$products = $wpdb->get_col( $sql );

					set_transient( 'awf_vss_products_cache_' . $vss_cache_suffix, $products, HOUR_IN_SECONDS * 5 );
				}

				$variations = get_transient( 'awf_vss_variations_cache_' . $vss_cache_suffix );

				if( false === $variations ) {
					$sql = $wpdb->prepare(
						"SELECT posts.ID
							FROM {$wpdb->posts} AS posts
							INNER JOIN {$wpdb->prefix}wc_product_meta_lookup AS product_lookup ON product_lookup.product_id = posts.ID
						WHERE
							posts.post_type = 'product_variation'
							AND posts.post_status = 'publish'
							AND product_lookup.stock_status {$operator} (%s)
						LIMIT 30000
					",
					$availability
					);

					$variations = $wpdb->get_col( $sql );

					set_transient( 'awf_vss_variations_cache_' . $vss_cache_suffix, $variations, HOUR_IN_SECONDS * 5 );
				}

				if( ! empty( $attributes_filters ) ) {

					foreach( $attributes_filters as $taxonomy => $slugs ) {
						$taxonomy = esc_sql( $taxonomy );
						$ids = get_terms( $taxonomy, array( 'hide_empty' => false, 'slug' => $slugs, 'fields' => 'ids' ) );

						if( ! empty( $ids ) ) {
							if( class_exists( 'SitePress' ) && ! empty( $this->get_nondefault_language() ) ) {

								global $sitepress;
								$default_language = $sitepress->get_default_language();
								$default_lang_ids = array();

								foreach( $ids as $id ) {
									$default_lang_ids[] = apply_filters( 'wpml_object_id', $id, $taxonomy, TRUE, $default_language );
								}

								$ids = array_unique( array_merge( $ids, $default_lang_ids ) );
							}

							$ids = implode( ',', array_map( 'intval', $ids ) );

							$filtered_out_variations = $wpdb->get_col(
								"SELECT product_id
									FROM {$wpdb->prefix}wc_product_attributes_lookup
								WHERE
									is_variation_attribute = 1
									AND taxonomy = '{$taxonomy}'
								GROUP BY product_id
								HAVING SUM( term_id IN ({$ids}) ) = 0
								LIMIT 30000
								"
							);

							$variations = array_diff( $variations, $filtered_out_variations );
						}
					}
				}

				return array( 'products' => $products, 'variations' => $variations );
		}

		/**
		 * Variations stock support without the attributes lookup table: the product ids available through their variations
		 *
		 * With attribute filters, a variation matches by its (meta_key, meta_value) pairs: the term slug (encoded for
		 * non-Latin slugs, while the query holds decoded ones) or an empty value for "Any <attribute>". A variation has
		 * a single row per attribute, so these alternatives don't change the HAVING COUNT(*) checks.
		 *
		 * @param string $availability
		 * @param string $operator IN / NOT IN
		 * @return array
		 */
		private function get_stock_posts_with_variations_without_lookup( $availability, $operator='IN' ) {
			global $wpdb;

			$availability = esc_sql( $availability );
			$products_ids = array();

			$attributes_filters = array_filter( $this->query->tax, function( $v, $k ) {
				if( 0 === strpos( $k, 'pa_' ) && ! empty( $v ) ) { return true; }
				return false;
			}, ARRAY_FILTER_USE_BOTH);

			$products_ids = $wpdb->get_col(
				"SELECT DISTINCT posts.ID AS product_id FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_stock_status'
				WHERE posts.post_type = 'product'
				AND posts.ID NOT IN (SELECT DISTINCT post_parent FROM {$wpdb->posts} as p2 WHERE p2.post_type = 'product_variation' AND p2.post_parent > 0 )
				AND postmeta.meta_value {$operator} ('{$availability}')
				LIMIT 30000"
			);

			if( empty( $attributes_filters ) ) {

				$variable_products_ids = $wpdb->get_col(
					"SELECT DISTINCT posts.post_parent FROM {$wpdb->posts} AS posts
					INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_stock_status'
					WHERE posts.post_type = 'product_variation'
						AND posts.post_status = 'publish'
						AND postmeta.meta_value {$operator} ('{$availability}')
					LIMIT 30000"
				);

				$products_ids = array_merge( $products_ids, $variable_products_ids );

			} else {

				if( 'NOT IN' === $operator ) { $availability = 'instock'; }

				$attribute_pairs = function( $meta_key, $meta_value ) {
					$pairs = array();

					foreach( array_unique( array( $meta_value, sanitize_title( $meta_value ), '' ) ) as $value ) {
						$pairs[] = "('attribute_" . esc_sql( $meta_key ) . "', '" . esc_sql( $value ) . "')";
					}

					return implode( ',', $pairs );
				};

				$combinations = array( array() );

				foreach( $attributes_filters as $attribute => $slugs) {
					foreach( $combinations as $combination ) {
							foreach( $slugs as $slug ) {
								array_push( $combinations, array_merge( array( $attribute => $slug ), $combination ) );
							}
					}
				}
				array_shift( $combinations );

				$combinations_by_count = array();

				foreach( $combinations as $i => $combination ) {
					$combinations_by_count[count( $combination )][] = $combination;
				}

				foreach( $combinations_by_count as $count => $combinations ) {
					$in_clauses = array();
					$i = 0;

					$attributes_count_check = $wpdb->get_col( "
						SELECT postmeta.post_id FROM {$wpdb->postmeta} AS postmeta
						WHERE postmeta.meta_key LIKE 'attribute_%' OR (postmeta.meta_key, postmeta.meta_value) IN (('_stock_status', '{$availability}'))
						GROUP BY postmeta.post_id
						HAVING COUNT(*) = " . ( $count + 1 ) . "
						LIMIT 30000
					" );

					if( ! empty( $attributes_count_check ) ) {

						$attributes_count_check = implode( ',', $attributes_count_check );

						foreach( $combinations as $variation ) {
							$i++;
							$in_clause = array( "('_stock_status', '{$availability}')" );

							foreach( $variation as $meta_key => $meta_value ) {
								$in_clause[] = $attribute_pairs( $meta_key, $meta_value );
							}

							$in_clauses[] = "
								posts.ID IN (
									SELECT pm{$i}.post_id FROM {$wpdb->postmeta} AS pm{$i}
									WHERE (pm{$i}.meta_key, pm{$i}.meta_value) IN (" . implode( ',', $in_clause ) . ")
									GROUP BY pm{$i}.post_id
									HAVING COUNT(*) = " . ( $count + 1 ) . "
								)
							";

						}

						$in_clauses = implode( ' OR ', $in_clauses );

						$filtered_variations_parents = $wpdb->get_col(
							"SELECT DISTINCT posts.post_parent FROM {$wpdb->posts} AS posts
							WHERE posts.post_type = 'product_variation' AND posts.post_status = 'publish'
								AND ({$in_clauses}) AND (posts.ID IN ({$attributes_count_check}))
							LIMIT 30000"
						);

						$products_ids = array_merge( $products_ids, $filtered_variations_parents );
					}

					if( 'NOT IN' === $operator ) {

						$attributes_count_check = $wpdb->get_col( "
							SELECT postmeta.post_id FROM {$wpdb->postmeta} AS postmeta
							WHERE postmeta.meta_key LIKE 'attribute_%' OR (postmeta.meta_key, postmeta.meta_value) IN (('_stock_status', 'onbackorder'))
							GROUP BY postmeta.post_id
							HAVING COUNT(*) = " . ( $count + 1 ) . "
							LIMIT 30000
						" );

						if( ! empty( $attributes_count_check ) ) {
							$attributes_count_check = implode( ',', $attributes_count_check );
							$in_clauses = array();

							foreach( $combinations as $variation ) {
								$i++;
								$in_clause = array( "('_stock_status', 'onbackorder')" );

								foreach( $variation as $meta_key => $meta_value ) {
									$in_clause[] = $attribute_pairs( $meta_key, $meta_value );
								}
								$in_clauses[] = "
									posts.ID IN (
										SELECT pm{$i}.post_id FROM {$wpdb->postmeta} AS pm{$i}
										WHERE (pm{$i}.meta_key, pm{$i}.meta_value) IN (" . implode( ',', $in_clause ) . ")
										GROUP BY pm{$i}.post_id
										HAVING COUNT(*) = " . ( $count + 1 ) . "
									)
								";
							}

							$in_clauses = implode( ' OR ', $in_clauses );

							$backordered_variations_parents = $wpdb->get_col(
								"SELECT DISTINCT posts.post_parent FROM {$wpdb->posts} AS posts
								WHERE posts.post_type = 'product_variation' AND posts.post_status = 'publish'
									AND ({$in_clauses}) AND (posts.ID IN ({$attributes_count_check}))
								LIMIT 30000"
							);

							$products_ids = array_merge( $products_ids, $backordered_variations_parents );
						}
					}
				}

				if( ! empty( $combinations_by_count[1] ) ) {
					/* Include variations with unfiltered second attribute (supports up to 2-attribute variations!) */

					$all_attributes = $wpdb->get_col( "
						SELECT DISTINCT postmeta.meta_key FROM {$wpdb->postmeta} AS postmeta
						WHERE postmeta.meta_key LIKE 'attribute_%'
						LIMIT 30000
					" );

					$filtered_attributes = array_keys( $attributes_filters );
					$filtered_attributes = array_map( function( $filter ) { return 'attribute_' . $filter; }, $filtered_attributes );

					$unfiltered_attributes = array_diff( $all_attributes, $filtered_attributes );
					$unfiltered_attributes = array_map( function( $attribute ) { return "'" . esc_sql( $attribute ) . "'"; }, $unfiltered_attributes );
					$unfiltered_attributes = implode( ',', $unfiltered_attributes );

					$i = 0;

					if( ! empty( $unfiltered_attributes ) ) {
						$clauses = array();

						foreach( $combinations_by_count[1] as $variation ) {
							foreach( $variation as $meta_key => $meta_value ) {
								$i++;

								$clauses[] = "posts.ID IN (
									SELECT pm{$i}.post_id FROM {$wpdb->postmeta} AS pm{$i}
									WHERE
										(pm{$i}.meta_key, pm{$i}.meta_value) IN (" . $attribute_pairs( $meta_key, $meta_value ) . ")
										OR pm{$i}.meta_key IN ({$unfiltered_attributes}) OR (pm{$i}.meta_key, pm{$i}.meta_value) IN (('_stock_status', '{$availability}'))
									GROUP BY pm{$i}.post_id
									HAVING COUNT(*) = 3
								)";

								if( 'NOT IN' === $operator ) {
									$i++;

									$clauses[] = "posts.ID IN (
										SELECT pm{$i}.post_id FROM {$wpdb->postmeta} AS pm{$i}
										WHERE
											(pm{$i}.meta_key, pm{$i}.meta_value) IN (" . $attribute_pairs( $meta_key, $meta_value ) . ")
											OR pm{$i}.meta_key IN ({$unfiltered_attributes}) OR (pm{$i}.meta_key, pm{$i}.meta_value) IN (('_stock_status', 'onbackorder'))
										GROUP BY pm{$i}.post_id
										HAVING COUNT(*) = 3
									)";
								}
							}
						}

						if( ! empty( $clauses ) ) {
							$clauses = implode( ' OR ', $clauses );

							$half_filtered_variations = $wpdb->get_col( "
								SELECT DISTINCT posts.post_parent FROM {$wpdb->posts} AS posts
								WHERE
									posts.post_type = 'product_variation'
									AND posts.post_status = 'publish'
									AND {$clauses}
								LIMIT 30000
							" );

							$products_ids = array_merge( $products_ids, $half_filtered_variations );
						}

					}
				}

			}

			if( empty( $products_ids ) ) { $products_ids = array( 0 ); }

			return $products_ids;
		}

		public function set_shop_columns() {
			return $this->awf_settings['shop_columns'];
		}

		public function set_products_per_page( $ppp ) {

			if( isset( $this->query->awf['ppp'] ) ) {
				$ppp = $this->query->awf['ppp'] = (int) $this->query->awf['ppp'];

				if( $ppp > absint( get_option( 'awf_ppp_limit', '200' ) ) || -1 === $ppp ) {
					$ppp = $this->query->awf['ppp'] = absint( get_option( 'awf_ppp_limit', '200' ) );
				}

			} elseif( ! empty( $this->awf_settings['ppp_default'] ) ) {
				$ppp = $this->awf_settings['ppp_default'];
			}

			return $ppp;
		}

		protected function setup_canonical_parameters() {}
		protected function build_canonical_parameters( &$parameters ) {}
		public function set_canonical_nofollow( $url, $all_parameters ) {}

		/**
		 * Canonical annotations
		 *
		 * The canonical of an SEO plugin or of WordPress (singular shortcode pages) gets adjusted,
		 * the plugin's own tag is only printed when nothing else outputs one.
		 *
		 * @since 1.8.5
		 */
		protected function setup_canonical_link() {
			if( defined( 'WPSEO_VERSION' ) ) {
				add_filter( 'wpseo_canonical', array( $this, 'filter_canonical_url' ), 20 );

			} elseif( class_exists( 'RankMath' ) ) {
				add_filter( 'rank_math/frontend/canonical', array( $this, 'filter_canonical_url' ), 20 );

			} elseif( false !== $this->is_sc_page && has_action( 'wp_head', 'rel_canonical' ) ) {
				add_filter( 'get_canonical_url', array( $this, 'filter_core_canonical_url' ), 20, 2 );

			} else {
				add_action( 'wp_head', array( $this, 'add_canonical_link' ), 5 );
			}
		}

		public function add_canonical_link() {
			$canonical_url = $this->get_canonical_url();

			if( ! empty( $canonical_url ) ) {
				echo PHP_EOL . '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />' . PHP_EOL;
			}
		}

		/**
		 * Canonical URL of an SEO plugin or of WordPress, replaced with the filters' canonical URL
		 *
		 * Pages without a canonical (the SEO plugin's choice) stay without one. Noindexed pages lose theirs:
		 * a canonical pointing to another URL would send conflicting signals.
		 *
		 * @since 1.8.5
		 * @param string|false $canonical The canonical URL, empty when no canonical is output.
		 * @return string|false Adjusted canonical URL.
		 */
		public function filter_canonical_url( $canonical ) {
			if( empty( $canonical ) ) { return $canonical; }

			if( $this->is_noindex_page() ) { return ''; }

			$canonical_url = $this->get_canonical_url();

			return empty( $canonical_url ) ? $canonical : $canonical_url;
		}

		/**
		 * WordPress canonical of the filterable shortcode page (get_canonical_url filter)
		 *
		 * The filter also serves the canonicals of other posts requested on the page.
		 *
		 * @since 1.8.5
		 * @param string $canonical The canonical URL.
		 * @param WP_Post $post The post of the canonical.
		 * @return string Adjusted canonical URL.
		 */
		public function filter_core_canonical_url( $canonical, $post = null ) {
			if( ! ( $post instanceof WP_Post ) || (int) $post->ID !== (int) $this->is_sc_page ) { return $canonical; }

			return $this->filter_canonical_url( $canonical );
		}

		/**
		 * Canonical URL of the current filterable page
		 *
		 * Filtering parameters are stripped (premium: except the "Indexable parameters" ones),
		 * paginated pages keep their page number (each page of a series is canonical to itself):
		 * product-page on shortcode pages, the pagination segment after the rewritten filters' segments
		 * (as in the pagination links) with permalinks.
		 *
		 * @since 1.8.5
		 * @return string Canonical URL, empty for noindexed pages.
		 */
		public function get_canonical_url() {

			if( $this->is_noindex_page() ) { return ''; }

			$parts = explode( '?', $this->current_url );
			$canonical_url = array_shift( $parts );

			if( empty( $canonical_url ) ) { return ''; }

			$parameters = array();
			$this->build_canonical_parameters( $parameters );

			if( false !== $this->is_sc_page ) {
				$page = isset( $_GET['product-page'] ) ? absint( $_GET['product-page'] ) : 0;
				if( 1 < $page ) { $parameters['product-page'] = $page; }

				return $this->get_url( $canonical_url, $parameters );
			}

			$canonical_url = $this->get_url( $canonical_url, $parameters );
			$paged = absint( get_query_var( 'paged' ) );

			if( 1 < $paged ) {
				if( $this->permalinks_on ) {
					global $wp_rewrite;

					$parts = explode( '?', $canonical_url, 2 );
					$canonical_url = trailingslashit( $parts[0] ) . user_trailingslashit( $wp_rewrite->pagination_base . '/' . $paged, 'paged' );
					if( isset( $parts[1] ) ) { $canonical_url .= '?' . $parts[1]; }

				} else {
					$canonical_url = add_query_arg( 'paged', $paged, $canonical_url );
				}
			}

			return $canonical_url;
		}

		/**
		 * Robots meta noindex for filtered pages / filtered pages without results
		 *
		 * Applied through the SEO plugin's robots filter when one is active, or WordPress' wp_robots (WP 5.7+).
		 *
		 * @since 1.8.5
		 */
		protected function setup_robots_noindex() {
			if( defined( 'WPSEO_VERSION' ) ) {
				add_filter( 'wpseo_robots_array', array( $this, 'adjust_seo_plugin_robots' ), 20 );

			} elseif( class_exists( 'RankMath' ) ) {
				add_filter( 'rank_math/frontend/robots', array( $this, 'adjust_seo_plugin_robots' ), 20 );

			} else {
				add_filter( 'wp_robots', array( $this, 'adjust_wp_robots' ), 20 );
			}
		}

		/**
		 * wp_robots: noindex, follow (a site-wide nofollow is kept)
		 *
		 * @since 1.8.5
		 * @param array $robots
		 * @return array
		 */
		public function adjust_wp_robots( $robots ) {
			if( $this->is_noindex_page() ) {
				unset( $robots['index'] );
				$robots['noindex'] = true;
				if( empty( $robots['nofollow'] ) ) { $robots['follow'] = true; }
			}

			return $robots;
		}

		/**
		 * Yoast SEO and Rank Math robots arrays: array( 'index' => 'noindex', 'follow' => 'follow', ... )
		 *
		 * @since 1.8.5
		 * @param array $robots
		 * @return array
		 */
		public function adjust_seo_plugin_robots( $robots ) {
			if( $this->is_noindex_page() && is_array( $robots ) ) {
				$robots['index'] = 'noindex';
			}

			return $robots;
		}

		/**
		 * Whether the current page gets the robots noindex directive
		 *
		 * @since 1.8.5
		 * @return bool
		 */
		public function is_noindex_page() {

			if( is_null( $this->noindex ) ) {
				$this->noindex = false;

				if( 'yes' === get_option( 'awf_add_noindex_filtered', 'no' ) && $this->has_noncanonical_filters() ) {
					$this->noindex = true;

				} elseif( 'yes' === get_option( 'awf_add_noindex_no_results', 'no' )
					&& false === $this->is_sc_page // shortcode results are only known after the page head
					&& $this->has_active_filters()
					&& isset( $GLOBALS['wp_query'] ) && 0 === (int) $GLOBALS['wp_query']->found_posts
				) {
					$this->noindex = true;
				}
			}

			return $this->noindex;
		}

		/**
		 * Active filters, sorting, products per page or search, except the archive's own single term
		 *
		 * @since 1.8.5
		 * @param bool $noncanonical Ignore the parameters kept in canonical links (premium).
		 * @return bool
		 */
		protected function has_active_filters( $noncanonical = false ) {
			$tax = $this->query->tax;

			if( $this->is_archive && isset( $tax[$this->is_archive] ) ) {
				if( 1 < count( $tax[$this->is_archive] ) ) { return true; }
				unset( $tax[$this->is_archive] );
			}

			foreach( array_keys( $tax ) as $taxonomy ) {
				if( ! $noncanonical || ! isset( $this->vars->tax[$taxonomy] ) || ! in_array( $this->vars->tax[$taxonomy], $this->canonical_parameters, true ) ) {
					return true;
				}
			}

			foreach( array_keys( $this->query->meta ) as $meta_name ) {
				if( ! $noncanonical || ! isset( $this->vars->meta[$meta_name] ) || ! in_array( $this->vars->meta[$meta_name], $this->canonical_parameters, true ) ) {
					return true;
				}
			}

			return ! empty( $this->query->awf ) || ! empty( $this->query->range );
		}

		protected function has_noncanonical_filters() {
			return $this->has_active_filters( true );
		}

		public function add_meta_description() {
			if( is_shop() || ( is_product_category() && ( 'yes' === get_option( 'awf_archive_components_support', 'yes' ) ) ) ) {
				echo '<meta name="description" content="' . esc_attr( A_W_F::get_seo_meta_description( $this->query ) ) . '" />' . PHP_EOL;
			}
		}

		public function edit_woocommerce_before_shop_loop() {

			if( 'yes' === get_option( 'awf_remove_wc_shop_title', 'no' ) ) {
				add_filter( 'woocommerce_show_page_title', array( $this, 'return_false' ) );
			}

			if( 'yes' === get_option( 'awf_remove_wc_orderby', 'no' ) ) {
				remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 ); // WC

				if ( class_exists( 'Storefront' ) ) {
					remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
					remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
				}
			}

			$template_options = get_option( 'awf_product_list_template_options', array() );

			foreach( $template_options as $option => $options ) {
				foreach( $options as $id => $data ) {
					$hooks = array();

					if( empty( $this->is_sc_page )
						&& wp_doing_ajax() && isset( $_REQUEST['awf_action'] ) && 'filter' === $_REQUEST['awf_action']
					) {
						switch( $data['hook'] ) {
							case( 'woocommerce_before_main_content' ):
								if( 'no' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
									$hooks[] = 'woocommerce_shortcode_before_products_loop';
									$hooks[] = 'woocommerce_shortcode_before_sale_products_loop';
								}
								break;
							case( 'woocommerce_archive_description' ):
								$hooks[] = 'woocommerce_shortcode_before_products_loop';
								$hooks[] = 'woocommerce_shortcode_before_sale_products_loop';
								break;
							case( 'woocommerce_after_main_content' ):
								if( 'no' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
									$hooks[] = 'woocommerce_shortcode_after_products_loop';
									$hooks[] = 'woocommerce_shortcode_after_sale_products_loop';
								}
								break;
							case( 'woocommerce_no_products_found' ):
								$hooks[] = 'woocommerce_shortcode_products_loop_no_results';
								$hooks[] = 'woocommerce_shortcode_sale_products_loop_no_results';
								break;
							default:
								$hooks[] = $data['hook'];
								break;
						}

					} else {
						$hooks[] = $data['hook'];
					}

					switch( $option ) {
						case( 'awf_preset' ):
							if( ! is_ajax() && 'no' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
								foreach( $hooks as $hook ) {
									add_action( $hook, function() use ( $data ) {
										echo do_shortcode( '[annasta_filters preset_id=' . $data['preset'] . ']' );
									}, $data['priority'] );
								}
							}

							break;
						case( 'shop_title' ):
							foreach( $hooks as $hook ) {
								add_action( $hook, array( $this, 'display_shop_title' ), $data['priority'] );
							}
							break;
						case( 'orderby' ):
							foreach( $hooks as $hook ) {
								add_action( $hook, 'woocommerce_catalog_ordering', $data['priority'] );
							}
							break;
						case( 'pagination' ):
							foreach( $hooks as $hook ) {
								add_action( $hook, 'woocommerce_pagination', $data['priority'] );
							}
							break;
						case( 'default_wc_pagination' ):
							foreach( $hooks as $hook ) {
								add_action( $hook, array( $this, 'display_default_wc_pagination' ), $data['priority'] );
							}
							break;
						case( 'result_count' ):
							foreach( $hooks as $hook ) {
								add_action( $hook, 'woocommerce_result_count', $data['priority'] );
							}
							break;
						case( 'active_badges' ):
							if( $this instanceof A_W_F_premium_frontend ) {
								foreach( $hooks as $hook ) {
									$hook_data = array( 'hook' => $hook, 'priority' => $data['priority'] );
									$this->hook_active_badges( $hook_data );
								}
							}
							break;
						case( 'reset_btn' ):
							if( $this instanceof A_W_F_premium_frontend ) {
								foreach( $hooks as $hook ) {
									add_action( $hook, array( $this, 'display_reset_btn' ), $data['priority'] );
								}
							}
							break;
						case( 'product_categories' ):
							foreach( $hooks as $hook ) {
								if( in_array( $hook, array(
									'woocommerce_no_products_found',
									'woocommerce_shortcode_products_loop_no_results',
									'woocommerce_shortcode_sale_products_loop_no_results'
								) ) ) {
									add_action( $hook, array( $this, 'display_empty_product_categories' ), $data['priority'] );
								} else {
									add_action( $hook, array( $this, 'display_product_categories' ), $data['priority'] );
								}
							}

							break;
						default: break;
					}
				}
			}
		}

		/**
		 * Display the list of relevant non-empty product categories or subcategories
		 * 
		 * This display can be enabled by setting up the needed hook in
		 * the "Product categories / subcategories" option of the Product lists > Add elements section
		 * 
		 * @since 1.5.5
		 * @return void
		 */
		public function display_product_categories() {

			if( $this->is_sc_page && ! get_option( 'awf_display_categories_on_sc_pages', false ) ) {
				return;
			}

			$categories = array();
			$columns = (int) apply_filters( 'awf_set_shop_columns', get_option( 'awf_shop_columns', 0 ) );
			$classes = array( 'awf-product-categories' );
			if( ! empty( $columns ) ) {
				$classes[] = 'columns-' . $columns;
			}

			$this->prepare_product_counts();
			if( ! isset( $this->counts['product_cat'] ) ) {
				$this->build_taxonomy_counts( 'product_cat' );
			}

			add_filter( 'woocommerce_product_subcategories_hide_empty', array( $this, 'return_false' ), 10 );
			add_filter( 'product_cat_class', array( $this, 'adjust_product_category_class' ), 10, 3 );

			$this->get_access_to['product_categories_url_parameters'] = $this->get_url_query();
			unset( $this->get_access_to['product_categories_url_parameters'][$this->vars->tax['product_cat']] );
			$this->get_access_to['product_categories_url_parameters'][$this->vars->misc['archive']] = 1;
			if( $this->is_archive && 'product_cat' !== $this->is_archive ) {
				$this->get_access_to['product_categories_url_parameters'][$this->vars->tax[$this->is_archive]] = implode( ',', $this->query->tax[$this->is_archive] );
			}
			add_filter( 'term_link', array( $this, 'adjust_product_category_link' ), 10, 3 );

			/* @Todo WPML functions hooked to 'term_link' ('home_url') sometimes return scrambled characters of non-latin languages (Greek)
			if( class_exists( 'SitePress' ) ) {}
			*/

			echo '<ul class="' . implode( ' ', $classes ) . '">';

			if( empty( $this->query->tax['product_cat'] ) ) {
				if( ! empty( $this->counts['product_cat'] ) ) {
					$categories = woocommerce_get_product_subcategories( 0 );
				}

			} else {
				foreach( $this->query->tax['product_cat'] as $slug ) {
					$category = get_term_by( 'slug', $slug, 'product_cat');
					$categories = array_merge( $categories, woocommerce_get_product_subcategories( $category->term_id ) );
				}
			}

			if( ! empty( $categories ) ) {
				foreach( $categories as $category ) {

					$category->slug = urldecode($category->slug);

					if( isset( $this->counts['product_cat'][$category->slug] ) ) {

						if( $this->counts['product_cat'][$category->slug] > 0 ) {
							$category->count = $this->counts['product_cat'][$category->slug];

							wc_get_template(
								'content-product_cat.php',
								array(
									'category' => $category,
								)
							);
						}
					}
				}
			}

			echo '</ul>';

			remove_filter( 'woocommerce_product_subcategories_hide_empty', array( $this, 'return_false' ), 10 );
			remove_filter( 'product_cat_class', array( $this, 'adjust_product_category_class' ), 10 );
			remove_filter( 'term_link', array( $this, 'adjust_product_category_link' ), 10 );
		}

		public function display_empty_product_categories() {
			echo '<ul class="awf-product-categories"></ul>';
		}

		public function adjust_product_category_class( $classes, $class, $category ) {
			$classes = array_diff( $classes, array( 'product' ) );

			return $classes;
		}

		public function adjust_product_category_link( $termlink, $term, $taxonomy ) {

			if( isset( $this->rewrites[$taxonomy] ) ) {

				$rewrites = array_map( function( $v ) {
					return isset( $this->vars->tax[$v] ) ? $this->vars->tax[$v] : false;
				}, $this->rewrites[$taxonomy] );
				$rewrites = array_filter( $rewrites );
				$rewrites = array_intersect_key( array_flip( $rewrites ), $this->get_access_to['product_categories_url_parameters'] ); // 1st array ensures proper sorting

				if( $rewrites ) {
					$rewrite_parameters = array();
					$parameters = $this->get_access_to['product_categories_url_parameters'];

					foreach( $rewrites as $parameter => $values ) {
						$rewrite_parameters[$parameter] = $parameter . '-' . $parameters[$parameter];
						unset( $parameters[$parameter] );
					}

					$termlink = user_trailingslashit( trailingslashit( $termlink ) . implode( '/', $rewrite_parameters ) );
					return add_query_arg( $parameters, $termlink );
				}

			} else {
				$termlink = add_query_arg( $this->get_access_to['product_categories_url_parameters'], $termlink );
			}

			return $termlink;
		}

		/**
		 * Build product counts for the requested taxonomy terms
		 *
		 * The counts reflect the state of the current active filters.
		 * Grouped counts (one products query per filters state, terms grouped in SQL); per term queries only for
		 * the variations stock support without the attributes lookup table (since 1.8.5).
		 *
		 * @since 1.5.5
		 * @param string $taxonomy The taxonomy for which to calculate the counts.
		 * @param boolean|object $filter The A_W_F_filter object if called from the filter, false otherwise.
		 * @return void The counts are saved in the corresponding taxonomy's key of the $counts property
		 */
		public function build_taxonomy_counts( $taxonomy, $filter = false ) {

			$terms_by_parent = array();

			if( false === $filter ) {
				$terms_query = array( 
					'taxonomy' => $taxonomy,
					'hierarchical' => true,
					'hide_empty' => false,
				);

				$taxonomy_terms = get_terms( $terms_query );

				if( ! is_wp_error( $taxonomy_terms ) ) { 
					foreach( $taxonomy_terms as $term ) {
						$term->slug = urldecode( $term->slug );
						$terms_by_parent[$term->{'parent'}][] = $term;
					}
				}

			} else {
				$terms_by_parent = $filter->build_terms_by_parent( $filter->get_filter_terms() );
			}

			if( ! isset( $terms_by_parent[0] ) ) { return; }

			if( $this->use_taxonomy_counts_walker( $taxonomy ) ) {
				$this->build_walker_taxonomy_counts( $taxonomy, $terms_by_parent );

			} elseif( $this->is_vss_counted_taxonomy( $taxonomy ) ) {
				$this->build_grouped_vss_taxonomy_counts( $taxonomy, $terms_by_parent );

			} else {
				$this->build_grouped_taxonomy_counts( $taxonomy, $terms_by_parent );
			}

			$this->update_counts_cache = true;
		}

		/**
		 * Per term counts, for the variations stock support without the attributes lookup table:
		 * the stock check of get_stock_posts_with_variations_without_lookup() depends on the counted term
		 *
		 * @since 1.8.5 (the counts of all taxonomies before)
		 * @param string $taxonomy
		 * @param array $terms_by_parent All the terms of the taxonomy, by parent id
		 */
		protected function build_walker_taxonomy_counts( $taxonomy, $terms_by_parent ) {

			if( isset( $this->query->tax[$taxonomy] ) ) {
				$this->get_access_to['counts_taxonomy_backup'] = $this->query->tax[$taxonomy];

				if( 'AND' === strtoupper( get_option( 'awf_' . $taxonomy . '_query_operator', 'IN' ) ) ) {
					$this->get_access_to['counts_and_operator'] = true;
				}

			} else {
				$this->get_access_to['counts_taxonomy_backup'] = false;
			}

			$query_args = array(
				'post_type' => 'product',
				'fields' => 'ids',
				'post_status' => 'publish',
				'ignore_sticky_posts' => true,
				'no_found_rows' => true,
				'posts_per_page' => -1,
				'paged' => '',
			);

			if( isset( $this->get_access_to['counts_meta_query'] ) ) {
				$query_args['meta_query'] = $this->get_access_to['counts_meta_query'];
			} else {
				$this->get_access_to['counts_meta_query'] = $query_args['meta_query'] = $this->set_wc_meta_query( array() );
			}

			if( isset( $this->get_access_to['post__in_cache'] ) ) {
				$query_args['post__in'] = $this->get_access_to['post__in_cache'];
			} else {
				$query_args['post__in'] = $this->get_wc_post__in( array() );
			}

			$this->taxonomy_counts_walker( $taxonomy, $terms_by_parent, $query_args );

			if( false === $this->get_access_to['counts_taxonomy_backup'] ) {
				unset( $this->query->tax[$taxonomy] );

			} else {
				$this->query->tax[$taxonomy] = $this->get_access_to['counts_taxonomy_backup'];
				$this->get_access_to['counts_taxonomy_backup'] = $this->get_access_to['counts_and_operator'] = false;
			}
		}

		/**
		 * Attribute taxonomies with the variations stock support and a stock condition: the stock check of
		 * add_stock_posts_with_variations() depends on the counted term
		 *
		 * @since 1.8.5
		 */
		protected function is_vss_counted_taxonomy( $taxonomy ) {
			return 'yes' === get_option( 'awf_variations_stock_support', 'no' )
				&& 0 === strpos( $taxonomy, 'pa_' )
				&& false !== $this->get_vss_stock_condition()
			;
		}

		/**
		 * Per term queries are only kept for the variations stock support without the attributes lookup table
		 *
		 * @since 1.8.5
		 */
		protected function use_taxonomy_counts_walker( $taxonomy ) {
			return $this->is_vss_counted_taxonomy( $taxonomy ) && 'yes' !== get_option( 'woocommerce_attribute_lookup_enabled', 'no' );
		}

		/**
		 * Grouped counts of an attribute taxonomy with the variations stock support (attributes lookup table)
		 *
		 * Same result as the per term queries, where add_stock_posts_with_variations() checks the variations against the counted term:
		 * - the products query leaves out the taxonomy's own selection (also from the stock check of the other attribute filters);
		 * - products available without variations count for all their terms;
		 * - variable products count for a term when one of their available variations, not excluded by the other attribute filters,
		 *   doesn't vary by the taxonomy, or has a row of that term ("Any" variations have rows for all the product's terms);
		 * - AND operator: the products must have all the selected terms (none, when one of them is unknown, as in the AND tax query),
		 *   and variations matching one of them qualify for every term.
		 * WPML: term ids of all the languages are mapped to the displayed terms, an unmapped term still means that the variation
		 * varies by the taxonomy, and the variations of the default language product also stand for its translations.
		 *
		 * @since 1.8.5
		 * @param string $taxonomy
		 * @param array $terms_by_parent All the terms of the taxonomy, by parent id
		 */
		protected function build_grouped_vss_taxonomy_counts( $taxonomy, $terms_by_parent ) {
			global $wpdb;

			list( $availability, $operator ) = $this->get_vss_stock_condition();
			$is_and = 'AND' === strtoupper( get_option( 'awf_' . $taxonomy . '_query_operator', 'IN' ) );
			$selection = isset( $this->query->tax[$taxonomy] ) ? $this->query->tax[$taxonomy] : array();

			$tax_query_backup = $this->query->tax;
			unset( $this->query->tax[$taxonomy] );

			$product_ids = $this->get_counts_product_ids();
			$attributes_filters = $this->get_query_attributes_filters();

			$this->query->tax = $tax_query_backup;

			$counts = $term_slugs = $term_map = array();

			foreach( $terms_by_parent as $terms ) {
				foreach( $terms as $term ) {
					$counts[$term->slug] = 0;
					$term_slugs[$term->term_id] = $term->slug;
					$term_map[$term->term_id] = $term->term_id;
				}
			}

			$term_map += $this->get_wpml_default_language_term_map( $taxonomy, $term_slugs );

			$selected_ids = array();

			if( $is_and && ! empty( $selection ) ) {
				foreach( $selection as $slug ) {
					$term_id = array_search( $slug, $term_slugs, true );

					if( false === $term_id ) {
						$this->counts[$taxonomy] = $counts;
						return;
					}

					$selected_ids[$term_id] = true;
				}
			}

			$sets = $this->get_vss_lookup_sets( $availability, $operator, $attributes_filters );
			$nonvariable_products = array_flip( $sets['products'] );
			$available_variations = array_flip( $sets['variations'] );

			$wpml_default_language = false;
			if( class_exists( 'SitePress' ) && ! empty( $this->get_nondefault_language() ) ) {
				global $sitepress;
				$wpml_default_language = $sitepress->get_default_language();
			}

			foreach( array_chunk( $product_ids, 5000 ) as $chunk ) {
				$ids = implode( ',', array_map( 'intval', $chunk ) );

				$rows = $wpdb->get_results( $wpdb->prepare(
					"SELECT tr.object_id, tt.term_id FROM {$wpdb->term_relationships} AS tr
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					WHERE tt.taxonomy = %s AND tr.object_id IN ({$ids})",
					$taxonomy
				), ARRAY_N );

				$products_terms = array();
				foreach( $rows as $row ) {
					if( isset( $term_map[(int) $row[1]] ) ) { $products_terms[(int) $row[0]][$term_map[(int) $row[1]]] = true; }
				}

				$variable_products = array();

				foreach( $products_terms as $product_id => $product_terms ) {
					if( ! empty( $selected_ids ) && array_diff_key( $selected_ids, $product_terms ) ) { continue; }

					if( isset( $nonvariable_products[$product_id] ) ) {
						foreach( $product_terms as $term_id => $v ) { $counts[$term_slugs[$term_id]]++; }
					} else {
						$variable_products[$product_id] = $product_id;
					}
				}

				if( empty( $variable_products ) ) { continue; }

				$parents = $variable_products;

				if( $wpml_default_language ) {
					foreach( $variable_products as $product_id ) {
						$default_id = (int) apply_filters( 'wpml_object_id', $product_id, 'product', false, $wpml_default_language );
						if( $default_id && $default_id !== $product_id ) { $parents[$default_id] = $product_id; }
					}
				}

				$parent_ids = implode( ',', array_map( 'intval', array_keys( $parents ) ) );

				$variation_rows = $wpdb->get_results(
					"SELECT ID, post_parent FROM {$wpdb->posts}
					WHERE post_type = 'product_variation' AND post_parent IN ({$parent_ids})",
					ARRAY_N
				);

				$products_variations = $variation_ids = array();

				foreach( $variation_rows as $row ) {
					if( isset( $available_variations[(int) $row[0]] ) ) {
						$products_variations[$parents[(int) $row[1]]][] = (int) $row[0];
						$variation_ids[] = (int) $row[0];
					}
				}

				$variations_terms = array();

				foreach( array_chunk( $variation_ids, 5000 ) as $variations_chunk ) {
					$vids = implode( ',', $variations_chunk );

					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT product_id, term_id FROM {$wpdb->prefix}wc_product_attributes_lookup
						WHERE taxonomy = %s AND is_variation_attribute = 1 AND product_id IN ({$vids})",
						$taxonomy
					), ARRAY_N );

					foreach( $rows as $row ) {
						$variations_terms[(int) $row[0]][] = isset( $term_map[(int) $row[1]] ) ? $term_map[(int) $row[1]] : 0;
					}
				}

				foreach( $products_variations as $product_id => $product_variations ) {
					$qualifies_for_all = false;
					$qualified_terms = array();

					foreach( $product_variations as $variation_id ) {
						if( ! isset( $variations_terms[$variation_id] ) ) {
							$qualifies_for_all = true;
							break;
						}

						foreach( $variations_terms[$variation_id] as $term_id ) {
							$qualified_terms[$term_id] = true;
							if( isset( $selected_ids[$term_id] ) ) { $qualifies_for_all = true; }
						}
					}

					foreach( $products_terms[$product_id] as $term_id => $v ) {
						if( $qualifies_for_all || isset( $qualified_terms[$term_id] ) ) { $counts[$term_slugs[$term_id]]++; }
					}
				}
			}

			$this->counts[$taxonomy] = $counts;
		}

		/**
		 * WPML: default language term id => displayed term id, for the terms of the current language
		 *
		 * @since 1.8.5
		 * @param string $taxonomy
		 * @param array $term_slugs Slugs of the current language terms, by term_id
		 * @return array
		 */
		protected function get_wpml_default_language_term_map( $taxonomy, $term_slugs ) {
			$term_map = array();

			if( ! class_exists( 'SitePress' ) || empty( $this->get_nondefault_language() ) ) { return $term_map; }

			global $sitepress;
			$default_language = $sitepress->get_default_language();

			foreach( $term_slugs as $term_id => $slug ) {
				$default_term_id = (int) apply_filters( 'wpml_object_id', $term_id, $taxonomy, false, $default_language );
				if( $default_term_id && $default_term_id !== (int) $term_id ) { $term_map[$default_term_id] = $term_id; }
			}

			return $term_map;
		}

		/**
		 * Taxonomy counts with one products query and grouped term relationships, instead of one query per term
		 *
		 * IN operator: the taxonomy's own selection is left out of the products query, each term counts the products it would add.
		 * AND operator: the selection stays, each term counts the products of the narrowed selection.
		 * Hierarchical taxonomies with "include children": product - terms pairs, every product counted once for its terms
		 * and their ancestors. Otherwise a GROUP BY count (term relationships are unique per product and term).
		 *
		 * @since 1.8.5
		 * @param string $taxonomy
		 * @param array $terms_by_parent All the terms of the taxonomy, by parent id
		 */
		protected function build_grouped_taxonomy_counts( $taxonomy, $terms_by_parent ) {
			global $wpdb;

			$is_and = 'AND' === strtoupper( get_option( 'awf_' . $taxonomy . '_query_operator', 'IN' ) );
			$include_children = ! $is_and && ! empty( $this->awf_settings['include_children'] ) && is_taxonomy_hierarchical( $taxonomy );

			$tax_query_backup = $this->query->tax;
			if( ! $is_and ) { unset( $this->query->tax[$taxonomy] ); }

			$product_ids = $this->get_counts_product_ids();

			$this->query->tax = $tax_query_backup;

			$counts = $term_slugs = $term_parents = $tt_terms = array();

			foreach( $terms_by_parent as $terms ) {
				foreach( $terms as $term ) {
					$counts[$term->slug] = 0;
					$term_slugs[$term->term_id] = $term->slug;
					$term_parents[$term->term_id] = (int) $term->parent;
					if( isset( $term->term_taxonomy_id ) ) { $tt_terms[(int) $term->term_taxonomy_id] = $term->term_id; }
				}
			}

			$tt_terms += $this->get_wpml_display_as_translated_tt_terms( $taxonomy, $term_slugs );

			foreach( array_chunk( $product_ids, 5000 ) as $chunk ) {
				$ids = implode( ',', array_map( 'intval', $chunk ) );

				if( $include_children ) {
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT tr.object_id, tr.term_taxonomy_id FROM {$wpdb->term_relationships} AS tr
						INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
						WHERE tt.taxonomy = %s AND tr.object_id IN ({$ids})",
						$taxonomy
					), ARRAY_N );

					$products_terms = array();
					foreach( $rows as $row ) {
						if( isset( $tt_terms[(int) $row[1]] ) ) { $products_terms[$row[0]][] = $tt_terms[(int) $row[1]]; }
					}

					foreach( $products_terms as $product_terms ) {
						$counted = array();

						foreach( $product_terms as $term_id ) {
							while( ! empty( $term_id ) && isset( $term_slugs[$term_id] ) && ! isset( $counted[$term_id] ) ) {
								$counted[$term_id] = true;
								$term_id = $term_parents[$term_id];
							}
						}

						foreach( $counted as $term_id => $v ) { $counts[$term_slugs[$term_id]]++; }
					}

				} else {
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT tr.term_taxonomy_id, COUNT(*) FROM {$wpdb->term_relationships} AS tr
						INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
						WHERE tt.taxonomy = %s AND tr.object_id IN ({$ids})
						GROUP BY tr.term_taxonomy_id",
						$taxonomy
					), ARRAY_N );

					foreach( $rows as $row ) {
						if( isset( $tt_terms[(int) $row[0]] ) ) { $counts[$term_slugs[$tt_terms[(int) $row[0]]]] += (int) $row[1]; }
					}
				}
			}

			$this->counts[$taxonomy] = $counts;
		}

		/**
		 * WPML "display as translated" products keep the default language terms: their term_taxonomy_ids are mapped to the displayed terms
		 *
		 * @since 1.8.5
		 * @param string $taxonomy
		 * @param array $term_slugs Slugs of the current language terms, by term_id
		 * @return array Default language term_taxonomy_id => current language term_id
		 */
		protected function get_wpml_display_as_translated_tt_terms( $taxonomy, $term_slugs ) {
			$tt_terms = array();

			if( ! class_exists( 'SitePress' ) || empty( $this->get_nondefault_language() ) ) { return $tt_terms; }

			global $sitepress;
			if( ! $sitepress->is_display_as_translated_post_type( 'product' ) ) { return $tt_terms; }

			$default_language = $sitepress->get_default_language();

			foreach( $term_slugs as $term_id => $slug ) {
				$default_term_id = (int) apply_filters( 'wpml_object_id', $term_id, $taxonomy, false, $default_language );

				if( $default_term_id && $default_term_id !== (int) $term_id ) {
					$default_term = get_term( $default_term_id, $taxonomy );
					if( $default_term instanceof WP_Term ) { $tt_terms[(int) $default_term->term_taxonomy_id] = $term_id; }
				}
			}

			return $tt_terms;
		}

		/**
		 * The products matching the current filters, for the grouped counts
		 *
		 * Same arguments as the products of the per term counts (awf_product_counts_query included), shared by the counts
		 * of all the taxonomies leaving the same filters in the query.
		 *
		 * @since 1.8.5
		 * @param boolean $full_meta_query False when a meta filter is left out of the query (meta data counts)
		 * @return array Product ids
		 */
		protected function get_counts_product_ids( $full_meta_query = true ) {
			$signature = md5( wp_json_encode( array( $this->query->tax, $this->query->meta ) ) );

			if( isset( $this->get_access_to['counts_product_ids'][$signature] ) ) {
				return $this->get_access_to['counts_product_ids'][$signature];
			}

			$query_args = array(
				'post_type' => 'product',
				'fields' => 'ids',
				'post_status' => 'publish',
				'ignore_sticky_posts' => true,
				'no_found_rows' => true,
				'posts_per_page' => -1,
				'paged' => '',
			);

			if( ! $full_meta_query ) {
				$query_args['meta_query'] = $this->set_wc_meta_query( array() );
			} elseif( isset( $this->get_access_to['counts_meta_query'] ) ) {
				$query_args['meta_query'] = $this->get_access_to['counts_meta_query'];
			} else {
				$this->get_access_to['counts_meta_query'] = $query_args['meta_query'] = $this->set_wc_meta_query( array() );
			}

			if( isset( $this->get_access_to['post__in_cache'] ) ) {
				$query_args['post__in'] = $this->get_access_to['post__in_cache'];
			} else {
				$query_args['post__in'] = $this->get_wc_post__in( array() );
			}

			$query_args['tax_query'] = $this->set_wc_tax_query( array() );
			$this->set_default_visibility( $query_args['tax_query'] );

			if( 'yes' !== get_option( 'awf_force_published_status', 'yes' ) && current_user_can( 'manage_woocommerce' ) ) {
				$query_args['post_status'] = array( 'publish', 'private' );
			}

			$query_args = apply_filters( 'awf_product_counts_query', $query_args );

			$query = new WP_Query( $query_args );

			return $this->get_access_to['counts_product_ids'][$signature] = array_map( 'intval', $query->posts );
		}

		/**
		 * Build product counts for the values of a meta data filter (single and multiple items selection)
		 *
		 * The values of a meta data filter are OR-ed (IN): the filter's own selection is left out of the products query.
		 * The counted values are sanitized like the filter items (A_W_F_filter::get_filter_terms()).
		 *
		 * @since 1.8.5
		 * @param string $meta_name The filter's meta key
		 * @param object $filter The A_W_F_filter object
		 * @return void The counts are saved in the META_COUNTS_PREFIX . $meta_name key of the $counts property
		 */
		public function build_meta_counts( $meta_name, $filter ) {
			global $wpdb;

			$counts_key = self::META_COUNTS_PREFIX . $meta_name;
			$counts = array();

			foreach( $filter->get_filter_terms() as $term ) {
				$counts[(string) $term->slug] = 0;
			}

			if( ! empty( $counts ) ) {
				$meta_query_backup = $this->query->meta;
				unset( $this->query->meta[$meta_name] );

				$product_ids = $this->get_counts_product_ids( $meta_query_backup === $this->query->meta );

				$this->query->meta = $meta_query_backup;

				foreach( array_chunk( $product_ids, 5000 ) as $chunk ) {
					$ids = implode( ',', array_map( 'intval', $chunk ) );

					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT meta_value, COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
						WHERE meta_key = %s AND post_id IN ({$ids})
						GROUP BY meta_value",
						$meta_name
					), ARRAY_N );

					foreach( $rows as $row ) {
						$slug = sanitize_text_field( $row[0] );
						if( isset( $counts[$slug] ) ) { $counts[$slug] += (int) $row[1]; }
					}
				}
			}

			$this->counts[$counts_key] = $counts;
			$this->update_counts_cache = true;
		}

		/* See build_walker_taxonomy_counts() */
		protected function taxonomy_counts_walker( $taxonomy, $terms_by_parent, $query_args, $parent_id = 0 ) {

			if( empty( $terms_by_parent[$parent_id] ) ) { return; }

			foreach ( $terms_by_parent[$parent_id] as $term ) {

				if( empty( $this->get_access_to['counts_and_operator'] ) ) {
					$this->query->tax[$taxonomy] = array( $term->slug );
				} else {
					$this->query->tax[$taxonomy] = array_merge( $this->get_access_to['counts_taxonomy_backup'], array( $term->slug ) );
				}

				$query_args['tax_query'] = $this->set_wc_tax_query( array() );
				$this->set_default_visibility( $query_args['tax_query'] );

				if( 'yes' === get_option( 'awf_force_published_status', 'yes' ) ) {
					$query_args['post_status'] = 'publish';

				} else {
					if( current_user_can( 'manage_woocommerce' ) ) {
						$query_args['post_status'] = array( 'publish', 'private' );
					} else {
						$query_args['post_status'] = 'publish';
					}
				}

				$query_args = apply_filters( 'awf_product_counts_query', $query_args );

				$query = new WP_Query( $query_args );

				$this->counts[$taxonomy][$term->slug] = (int) $query->post_count;

				if( isset( $terms_by_parent[$term->term_id] ) ) {
					$this->taxonomy_counts_walker( $taxonomy, $terms_by_parent, $query_args, $term->term_id );
				}
			}

			return;
		}

		public function add_variations_stock_support_to_product_counts( $args ) {
			$args['post__in'] = $this->get_wc_post__in( array() );

			return $args;
		}

		public function add_sc_attributes_to_product_counts( $query_args ) {

			if( ! empty( $this->get_access_to['sc_attributes']['category'] ) ) {
				if( empty( $this->get_access_to['counts_categories_for_sc'] ) ) {
					$categories = array_map( 'sanitize_title', explode( ',', $this->get_access_to['sc_attributes']['category'] ) );
					$field      = 'slug';

					if ( is_numeric( $categories[0] ) ) {
						$field      = 'term_id';
						$categories = array_map( 'absint', $categories );
						// Check numeric slugs.
						foreach ( $categories as $cat ) {
							$the_cat = get_term_by( 'slug', $cat, 'product_cat' );
							if ( false !== $the_cat ) {
								$categories[] = $the_cat->term_id;
							}
						}
					}

					$cat_operator = $this->get_sc_cat_operator( $this->get_access_to['sc_attributes'] );

					$this->get_access_to['counts_categories_for_sc'] = array(
						'taxonomy'         => 'product_cat',
						'terms'            => $categories,
						'field'            => $field,
						'operator'         => $cat_operator,

						/*
						* When cat_operator is AND, the children categories should be excluded,
						* as only products belonging to all the children categories would be selected.
						*/
						'include_children' => 'AND' === $cat_operator ? false : true,
					);
				}

				if( ! empty( $this->get_access_to['counts_categories_for_sc'] ) ) {
					$query_args['tax_query'][] = $this->get_access_to['counts_categories_for_sc'];
				}
			}

			if( ! empty( $this->get_access_to['sc_attributes']['awf_on_sale_sc'] ) ) {

				if( empty( $this->get_access_to['post__in_cache_for_sc'] ) ) {
					$onsale_posts = wc_get_product_ids_on_sale();

					if( empty( $query_args['post__in'] ) ) {
						$this->get_access_to['post__in_cache_for_sc'] = array_merge( array( 0 ), $onsale_posts );
					} else {
						$this->get_access_to['post__in_cache_for_sc'] = array_merge( array( 0 ), array_intersect( $query_args['post__in'], $onsale_posts ) );
					}
				}

				if( ! empty( $this->get_access_to['post__in_cache_for_sc'] ) ) {
					$query_args['post__in'] = $this->get_access_to['post__in_cache_for_sc'];
				}
			}

			return $query_args;
		}

		/* The cat_operator attribute of the products shortcode, defaulting to IN like WooCommerce */
		protected function get_sc_cat_operator( $attributes ) {
			$operator = isset( $attributes['cat_operator'] ) ? strtoupper( trim( (string) $attributes['cat_operator'] ) ) : 'IN';

			return in_array( $operator, array( 'IN', 'NOT IN', 'AND' ), true ) ? $operator : 'IN';
		}

		/**
		 * The shortcode attributes that narrow the product counts, normalized for the counts cache key
		 *
		 * @since 1.8.5
		 * @return array
		 */
		protected function get_counts_sc_context() {
			$context = array();

			if( empty( $this->get_access_to['sc_attributes'] ) || ! is_array( $this->get_access_to['sc_attributes'] ) ) { return $context; }

			$attributes = $this->get_access_to['sc_attributes'];

			if( ! empty( $attributes['category'] ) ) {
				$categories = array_map( 'sanitize_title', explode( ',', (string) $attributes['category'] ) );
				sort( $categories );

				$context['category'] = $categories;
				$context['cat_operator'] = $this->get_sc_cat_operator( $attributes );
			}

			if( ! empty( $attributes['awf_on_sale_sc'] ) ) {
				$context['on_sale'] = 1;
			}

			return $context;
		}

		/**
		 * Shortcode pages: the counts get the attributes of the page's products shortcode before any preset is displayed
		 *
		 * Without this the attributes only reached the counts when the shortcode was rendered before the presets (add_awf_sc_class()).
		 * Pages without a products shortcode in their content (page builders) keep that behaviour. Escaped [[products]]
		 * shortcodes are skipped; with several products shortcodes no attributes are used, as in the AJAX counts updates.
		 *
		 * @since 1.8.5
		 * @param int|string $page_id
		 */
		protected function set_sc_attributes_from_content( $page_id ) {
			$post = get_post( (int) $page_id );
			if( ! $post || ( ! has_shortcode( $post->post_content, 'products' ) && ! has_shortcode( $post->post_content, 'sale_products' ) ) ) { return; }

			preg_match_all( '/' . get_shortcode_regex( array( 'products', 'sale_products' ) ) . '/', $post->post_content, $matches, PREG_SET_ORDER );

			$shortcodes = array();
			foreach( $matches as $match ) {
				if( '[' === $match[1] && ']' === $match[6] ) { continue; }
				$shortcodes[] = $match;
			}

			if( empty( $shortcodes ) ) { return; }

			$this->get_access_to['sc_attributes_from_content'] = true;

			if( 1 !== count( $shortcodes ) ) { return; }

			$attributes = shortcode_parse_atts( $shortcodes[0][3] );
			if( ! is_array( $attributes ) ) { $attributes = array(); }
			if( 'sale_products' === $shortcodes[0][2] ) { $attributes['awf_on_sale_sc'] = 'yes'; }

			if( ! empty( $attributes['category'] ) || ! empty( $attributes['awf_on_sale_sc'] ) ) {
				$this->get_access_to['sc_attributes'] = $attributes;
				add_filter( 'awf_product_counts_query', array( $this, 'add_sc_attributes_to_product_counts' ) );
			}
		}

		public function add_search_autocomplete_all_results_link() {
			$url_query_args = $this->get_url_query();

			if( isset( $url_query_args[$this->vars->awf['search']] ) ) {
				$url_query_args[$this->vars->awf['search']] = urlencode( $url_query_args[$this->vars->awf['search']] );
			}

			$total = '';
			if( ! empty( $total = wc_get_loop_prop( 'total' ) ) ) {
				$total = ' <span class="awf-ac-total-results">' . absint( $total ) . '</span>';
			}

			$label = isset( $this->get_access_to['autocomplete_filter']->settings['style_options']['autocomplete_view_all_label'] ) ? $this->get_access_to['autocomplete_filter']->settings['style_options']['autocomplete_view_all_label'] : __( 'View all results', 'annasta-filters' );

			echo '<div class="awf-s-autocomplete-view-all-container"><a href="' , esc_url( add_query_arg( $url_query_args, $this->current_url ) ) , '">' , esc_html( $label ) , $total, '</a></div>' , PHP_EOL;
		}

		public function add_ac_categories() {
			$this->add_ac_taxonomy();
		}

		public function add_ac_tags() {
			$this->add_ac_taxonomy( 'product_tag' );
		}

		protected function add_ac_taxonomy( $taxonomy = 'product_cat' ) {

			$html = '';

			if( empty( $this->get_access_to['autocomplete_filter'] ) ) { return $html; }

			$terms = array();
			$search = empty( $this->query->awf['search'] ) ? '' : wp_unslash( $this->query->awf['search'] );

			if( '' !== $search ) {
				$terms = get_terms( array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'name__like' => $search,
					'number' => intval( $this->get_access_to['autocomplete_results_count'] ),
				) );
			}

			if( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				switch( $taxonomy ) {
					case 'product_cat':
						$class_tax = 'pc';
						break;
					case 'product_tag':
						$class_tax = 'tag';
						break;
					default:
						$class_tax = esc_attr( $taxonomy );
						break;
				}

				foreach( $terms as $t ) {
					$link = get_term_link( $t, $taxonomy );
					if( is_wp_error( $link ) ) { continue; }

					$p = mb_stripos( $this->normalize_accents( $t->name ), $this->normalize_accents( $search ) );

					if( false === $p ) {
						$label = esc_html( $t->name );

					} else {
						$length = mb_strlen( $search );
						$label = esc_html( mb_substr( $t->name, 0, $p ) ) . '<strong>' . esc_html( mb_substr( $t->name, $p, $length ) ) . '</strong>' . esc_html( mb_substr( $t->name, $p + $length ) );
					}

					$html .= '<div class="awf-ac-taxonomy-term awf-ac-' . esc_attr( $t->slug ) . '"><a href="' . esc_url( $link ) . '">' . $label . '</a></div>';
				}

				if( ! empty( $html ) ) {
					if( ! empty( $this->get_access_to['autocomplete_filter']->settings['type_options']['ac_' . $taxonomy . '_header'] ) ) {
						$html = '<div class="awf-ac-taxonomy-header">'. esc_html( $this->get_access_to['autocomplete_filter']->settings['type_options']['ac_' . $taxonomy . '_header'] ) . '</div>' . PHP_EOL . $html;
					}

					$html = '<div class="awf-ac-taxonomy-container awf-ac-' . $class_tax . '-container">' . $html . '</div>';
				}
			}

			echo $html;
		}

		/**
		 * Greek and common Latin accented letters replaced by their base letters
		 *
		 * One character for one character: positions found in the result match the original.
		 *
		 * @since 1.8.5
		 * @param string $string
		 * @return string
		 */
		protected function normalize_accents( $string ) {
			static $chars = null;

			if( is_null( $chars ) ) {
				$map = array(
					'Α' => 'ΆΑ', 'α' => 'ά', 'Ε' => 'Έ', 'ε' => 'έ', 'Η' => 'Ή', 'η' => 'ή', 'Ι' => 'ΊΪ', 'ι' => 'ίϊΐ',
					'Ο' => 'Ό', 'ο' => 'ό', 'Υ' => 'ΎΫ', 'υ' => 'ύϋΰ', 'Ω' => 'Ώ', 'ω' => 'ώ',
					'A' => 'ÀÁÂÃÄÅĀĂĄ', 'a' => 'àáâãäåāăą', 'C' => 'ÇĆĈĊČ', 'c' => 'çćĉċč', 'D' => 'ĎĐ', 'd' => 'ďđ',
					'E' => 'ÈÉÊËĒĔĖĘĚ', 'e' => 'èéêëēĕėęě', 'G' => 'ĜĞĠĢ', 'g' => 'ĝğġģ', 'H' => 'ĤĦ', 'h' => 'ĥħ',
					'I' => 'ÌÍÎÏĨĪĬĮİ', 'i' => 'ìíîïĩīĭįı', 'J' => 'Ĵ', 'j' => 'ĵ', 'K' => 'Ķ', 'k' => 'ķ',
					'L' => 'ĹĻĽĿŁ', 'l' => 'ĺļľŀł', 'N' => 'ÑŃŅŇ', 'n' => 'ñńņň', 'O' => 'ÒÓÔÕÖØŌŎŐ', 'o' => 'òóôõöøōŏő',
					'R' => 'ŔŖŘ', 'r' => 'ŕŗř', 'S' => 'ŚŜŞŠȘ', 's' => 'śŝşšș', 'T' => 'ŢŤŦȚ', 't' => 'ţťŧț',
					'U' => 'ÙÚÛÜŨŪŬŮŰŲ', 'u' => 'ùúûüũūŭůűų', 'W' => 'Ŵ', 'w' => 'ŵ', 'Y' => 'ÝŸŶ', 'y' => 'ýÿŷ',
					'Z' => 'ŹŻŽ', 'z' => 'źżž',
				);

				$chars = array();
				foreach( $map as $base => $accented ) {
					foreach( preg_split( '//u', $accented, -1, PREG_SPLIT_NO_EMPTY ) as $char ) {
						if( $char !== $base ) { $chars[$char] = $base; }
					}
				}
			}

			return strtr( $string, $chars );
		}

		public function add_ac_products_header() {
			echo '<div class="awf-ac-products-header">'. esc_html( $this->get_access_to['autocomplete_filter']->settings['type_options']['ac_products_header'] ) . '</div>' . PHP_EOL;
		}

		public function add_ac_taxonomy_wrapper() {
			echo '<div class="awf-ac-taxonomies-wrapper">';
		}

		public function close_div() {
			echo '</div>';
		}

		/**
		 * WooCommerce pagination links of the filtered pages
		 *
		 * With rewrites, array query vars (?var[]=x) stay query arguments, as they can't become path segments.
		 *
		 * @param array $args
		 * @return array
		 */
		public function adjust_wc_pagination( $args ) {

			if( $this->current_rewrites ) {
				$current_url = $this->get_url( $this->current_url, array_filter( $_GET, 'is_scalar' ) );
				$parts = explode( '?', $current_url );

				if( $parts ) {
					if( ! empty( $this->is_sc_page ) ) {
						$args['base'] = esc_url_raw( add_query_arg( array( 'product-page' => '%#%' ), $current_url ) );

					} else {
						global $wp_rewrite;
						$base = array_shift( $parts );
						$args['base'] = str_replace( 999999999, '%#%', trailingslashit( $base ) . user_trailingslashit( $wp_rewrite->pagination_base . '/' . 999999999, 'paged' ) );
					}

					$args['add_args'] = array_diff_key( $_GET, array_flip( $this->current_rewrites ) );
				}

			} else {
				if( ! empty( $this->is_sc_page ) ) {
					$args['base'] = esc_url_raw( add_query_arg( array( 'product-page' => '%#%' ), $this->current_url ) );

				} else {
					if( $this->permalinks_on ) {
						global $wp_rewrite;

						$args['base'] = str_replace( 999999999, '%#%', trailingslashit( $this->current_url ) . user_trailingslashit( $wp_rewrite->pagination_base . '/' . 999999999, 'paged' ) );

					} else {
						$args['base'] = esc_url_raw( add_query_arg( array( 'paged' => '%#%' ), $this->current_url ) );
					}

					$args['format'] = '';
				}

				$args['add_args'] = $_GET;
			}

			unset( $args['add_args']['product-page'] );

			return $args;
		}

		protected function do_wc_products_shortcode( $args ) {

			$shortcode = '[products';

			foreach( $args as $k => $v ) {
				if( in_array( $k, array( 'limit', 'columns', 'rows', 'page' ) ) ) {
				$shortcode .= ' ' . $k . '="' . intval( $v ) . '"';

				} elseif( in_array( $k, array( 'paginate', 'cache' ) ) ) {

					if( is_string( $v ) ) { $v = strtolower( $v ); }
					elseif( is_bool( $v ) ) { $v = wc_bool_to_string( $v ); }

					$shortcode .= ' ' . $k . '=' . ( in_array( $v, array( 'false', '0', 'no' ), true ) ? 'false' : 'true' ) . '';

				} else {
					$shortcode .= ' ' . sanitize_key( $k ) . '="' . sanitize_text_field( $v ) . '"';
				}
			}

			$shortcode .= ']';

			if( ! isset( $GLOBALS['post'] ) ) { $GLOBALS['post'] = null; } // fix for the WC shortcode throwing notice for the missing 'post'

			echo do_shortcode( $shortcode );
		}

		/**
		 * Products shortcodes: the awf-sc class, and their attributes in the counts
		 *
		 * The attributes are not taken here when they were already set from the page content (set_sc_attributes_from_content()),
		 * or when the counts (and their cache key) were prepared before the shortcode was rendered.
		 *
		 * @param array $out
		 * @param array $pairs
		 * @param array $atts
		 * @param string $shortcode
		 * @return array
		 */
		public function add_awf_sc_class( $out, $pairs, $atts, $shortcode ) {
			if( empty( $out['class'] ) ) { $out['class'] = 'awf-sc'; } else { $out['class'] .= ' awf-sc'; }

			if( 'sale_products' === $shortcode ) {
				$out['awf_on_sale_sc'] = 'yes';
			}

			if( ! empty( $this->get_access_to['sc_attributes_from_content'] ) || is_array( $this->counts ) ) { return $out; }

			if( 'sale_products' === $shortcode ) {
				$this->get_access_to['sc_attributes'] = $out;
				add_filter( 'awf_product_counts_query', array( $this, 'add_sc_attributes_to_product_counts' ) );

			} elseif( ! empty( $out['category'] ) ) {
				$this->get_access_to['sc_attributes'] = $out;
				add_filter( 'awf_product_counts_query', array( $this, 'add_sc_attributes_to_product_counts' ) );
			}

			return $out;
		}

		public function insert_sc_ajax_vars( $attrs ) {
			foreach( $attrs as $name => $value ) {
				if( 'class' === $name ) { continue; }
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="awf-sc-var">';
			}
		}

		public function display_sc_page_no_results_message( $attrs ) {
			wc_no_products_found();
		}

		public function display_ajax_pagination_resut_count() {
			$total = wc_get_loop_prop( 'total' );
			$last  = min( $total, wc_get_loop_prop( 'per_page' ) * wc_get_loop_prop( 'current_page' ) );

			echo '<div class="awf-ajax-pagination-result-count" style="display: none;">';
			printf( _nx( 'Showing %1$d&ndash;%2$d of %3$d result', 'Showing %1$d&ndash;%2$d of %3$d results', $total, 'with first and last result', 'woocommerce' ), '1', $last, $total );
			echo '</div>';
		}

		/**
		 * Block themes: adds the Load more / infinite scroll results count message to the Product Results Count block output
		 *
		 * @since 1.8.5
		 */
		public function add_block_ajax_pagination_result_count( $block_content ) {
			ob_start();
			$this->display_ajax_pagination_resut_count();

			return $block_content . ob_get_clean();
		}

		public function redirect_archives() {
			if( $this->is_archive ) {
				unset( $this->url_query[$this->vars->misc['archive']] );
				$this->url_query[$this->vars->tax[$this->is_archive]] = implode( ',', $this->query->tax[$this->is_archive] );
				wp_redirect( add_query_arg( $this->url_query, $this->shop_url ) );
				die;
			}
		}

		/**
		 * Block themes: WooCommerce dequeues wc-add-to-cart when the Product Button block renders (the block uses the Interactivity API),
		 * but the product buttons inserted by the filters AJAX are not hydrated and rely on the classic handler (see parse_ajax_response in awf.js)
		 *
		 * @since 1.8.5
		 */
		public function restore_wc_add_to_cart_script() {
			if( $this->filter_on && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() && 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) ) {
				wp_enqueue_script( 'wc-add-to-cart' );
			}
		}

		public function enqueue_scripts( $hook ) {

			$google_fonts = array();
			$js_dependencies = array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-blockui', 'awf-nouislider' );

			if( A_W_F::$premium ) {
				$js_dependencies[] = 'awf-premium';
			}

			wp_enqueue_style( 'awf-nouislider', A_W_F_PLUGIN_URL . '/styles/nouislider.min.css', array(), A_W_F::$plugin_version );
			wp_enqueue_script( 'awf-wnumb', A_W_F_PLUGIN_URL . '/code/js/wNumb.js', array() );

			if ( 'yes' === get_option( 'awf_pretty_scrollbars' ) ) {
				$js_dependencies[] = 'awf-pretty-scrollbars';
				wp_enqueue_style( 'awf-pretty-scrollbars', A_W_F_PLUGIN_URL . '/styles/perfect-scrollbar.css' );
				wp_enqueue_script( 'awf-pretty-scrollbars', A_W_F_PLUGIN_URL . '/code/js/perfect-scrollbar.min.js', array(), A_W_F::$plugin_version );
			}

			if ( ! empty( get_option( 'awf_daterangepicker_enabled' ) ) ) {
				$js_dependencies[] = 'awf-daterangepicker';
				wp_enqueue_style( 'awf-daterangepicker', A_W_F_PLUGIN_URL . '/styles/daterangepicker.css' );
				wp_enqueue_script( 'awf-daterangepicker', A_W_F_PLUGIN_URL . '/code/js/daterangepicker.js', array( 'jquery', 'moment' ), A_W_F::$plugin_version );
			}

			switch( get_option( 'awf_fontawesome_font_enqueue', 'awf' ) ) {
				case 'awf':
					wp_enqueue_style( 'awf-font-awesome', A_W_F_PLUGIN_URL . '/styles/awf-font-awesome.css', false, A_W_F::$plugin_version );
				case 'yes':
					wp_enqueue_style( 'awf-font-awesome', A_W_F_PLUGIN_URL . '/styles/awf-font-awesome-5-free.css', false, A_W_F::$plugin_version );
					wp_enqueue_style( 'awf-font-awesome-all', A_W_F_PLUGIN_URL . '/styles/fontawesome-all.min.css', array( 'awf-font-awesome' ), A_W_F::$plugin_version );
					break;
				default: break;
			}

			if( 'yes' === get_option( 'awf_fontawesome_font_enqueue', 'yes' ) ) {
				wp_enqueue_style( 'awf-font-awesome', A_W_F_PLUGIN_URL . '/styles/awf-font-awesome.css', false, A_W_F::$plugin_version );
			}

			$awf_style_file = 'awf.css';
			$awf_custom_style = get_option( 'awf_custom_style', 'none' );
			if( 'none' !== $awf_custom_style ) { $awf_style_file = 'custom-styles/awf-' . $awf_custom_style . '.css'; }

			if( 'yes' === get_option( 'awf_default_font_enqueue', 'yes' ) ) {
				$font = get_option( 'awf_default_font' );
				if( empty( $font ) ) { $font = A_W_F::get_awf_custom_style_default_font(); }
				if( 'inherit' !== $font ) { $google_fonts[] = 'family=' . $font . ':wght@100;200;300;400;500;600;700;800'; }
			}

			if( ! empty( $google_fonts ) ) {
				wp_enqueue_style( 'awf-google-fonts', 'https://fonts.googleapis.com/css2?' . implode( '&', $google_fonts), false, false );
			}

			wp_enqueue_style( 'awf', A_W_F_PLUGIN_URL . '/styles/' . $awf_style_file, false, A_W_F::$plugin_version );

			A_W_F::enqueue_style_options_css();

			wp_enqueue_script( 'awf-nouislider', A_W_F_PLUGIN_URL . '/code/js/nouislider.min.js', array( 'awf-wnumb' ) );
			wp_enqueue_script( 'awf', A_W_F_PLUGIN_URL . '/code/js/awf.js', $js_dependencies, A_W_F::$plugin_version );

			wp_localize_script( 'awf', 'awf_data', $this->build_js_data() );
		}

		public function load_footer_js() {
?><script type="text/javascript">
<?php echo stripcslashes( get_option( 'awf_user_js', '' ) ); ?>

</script>
<?php
		}

		protected function build_js_data() {
			$current_url_pieces = explode( '?', $this->current_url );
			$current_url_pieces[1] = isset( $current_url_pieces[1] ) ? wp_parse_args( $current_url_pieces[1] ) : array();
			$selectors = get_option( 'awf_custom_selectors', array() );

			$js_data = array(
				'filters_url' => $current_url_pieces[0],
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'awf_nonce' ),
				'ajax_mode' => get_option( 'awf_ajax_mode', 'compatibility_mode' ),
				'cm_v2' => get_option( 'awf_cm_v2', 'no' ),
				'query' => array_merge( (array) $this->url_query, $current_url_pieces[1] ),
				'wrapper_reload' => get_option( 'awf_force_wrapper_reload', 'no' ),
				'reset_all_exceptions' => $this->get_reset_all_exceptions( array_keys( $current_url_pieces[1] ) ),
				'togglable_preset' => array( 
					'insert_btn_before_container' => get_option( 'awf_toggle_btn_position_before', '' ),
					'close_preset_on_ajax_update' => true,
				),
				'i18n' => array(
					'badge_reset_label' => esc_attr( get_option( 'awf_badge_reset_label', '' ) ),
					'togglable_preset_btn_label' => esc_attr( get_option( 'awf_toggle_btn_label', __( 'Filters', 'annasta-filters' ) ) ),
					'cc_button_label' => esc_attr( __( 'Toggle children collapse', 'annasta-filters' ) ),
				)
			);

			if( $this->rewrites ) {
				$js_data['rewrite'] = 'yes';
				if( isset( $this->rewrites[$this->url_page] ) ) {
					$js_data['rewrite_page'] = $this->url_page;
					if( $this->current_rewrites ) {
						$js_data['current_rewrites'] = array_values( $this->current_rewrites );
					}
				}
			}

			if( $this->is_archive ) {
				$js_data['archive_page'] = $this->vars->tax[$this->is_archive];
				$js_data['archive_identifier'] = $this->vars->misc['archive'];
				$js_data['query'][$this->vars->tax[$this->is_archive]] = implode( ',', $this->query->tax[$this->is_archive] );
				unset( $js_data['query'][$this->is_archive] );

				if( $this->permalinks_on ) {
					$js_data['archive_page_switch'] = user_trailingslashit( '/' . $js_data['query'][$this->vars->tax[$this->is_archive]] );
					$js_data['archive_page_trailingslash'] = user_trailingslashit( '' );

				} else {
					$js_data['archive_page_tax'] = $this->is_archive;
				}
			}

			if( $this->permalinks_on ) { $js_data['permalinks_on'] = 'yes'; }

			if( false === $this->filter_on ) {
				$js_data['redirect_ajax'] = 'yes';
				$js_data['products_container'] = empty( $selectors['products'] ) ? '.products' : $selectors['products'];

			} else {
				$js_data['pagination_container'] = empty( $selectors['pagination'] ) ? '.woocommerce-pagination' : $selectors['pagination'];
				/* $js_data['pagination_after'] - not set by default, can be used to control pagination insertion */
				$js_data['orderby_container'] = empty( $selectors['orderby'] ) ? '.woocommerce-ordering' : $selectors['orderby'];
				$js_data['result_count_container'] = '.woocommerce-result-count';
				if( empty( $selectors['no_result'] ) ) {
					$js_data['no_result_container'] = '.woocommerce-info';

					if( version_compare( WC_VERSION, '8.5', '>=' ) ) { $js_data['no_result_container'] .= ',.woocommerce-no-products-found'; }

				} else {
					$js_data['no_result_container'] = $selectors['no_result'];
				}

				if( false === $this->is_sc_page ) {

					if( ! empty( get_option( 'awf_products_html_wrapper' ) ) ) {
						$js_data['products_wrapper'] = get_option( 'awf_products_html_wrapper' );
					}

				} else {
					$js_data['sc_page'] = $this->is_sc_page;
					$js_data['products_wrapper'] = '.awf-sc';
				}

				$js_data['products_container'] = empty( $selectors['products'] ) ? '.products' : $selectors['products'];

				$template_options = get_option( 'awf_product_list_template_options', array() );
				if( isset( $template_options['active_badges'] ) && false !== array_search( 'js', array_column( $template_options['active_badges'], 'hook' ) ) ) {
					$js_data['title_badges'] = 'yes';
				}

				if( 'none' !== ( $pagination_type = get_option( 'awf_ajax_pagination', 'none' ) ) ) {
					$js_data['ajax_pagination'] = array(
						'type' => $pagination_type,
						'page_number' => empty( $selectors['page_number'] ) ? 'a.page-numbers' : $selectors['page_number'],
						'next' => empty( $selectors['pagination_next'] ) ? '.next' : $selectors['pagination_next'],
						'prev' => '.prev',
						'product_container' => empty( $selectors['product'] ) ? '.product' : $selectors['product'],
					);

					if( 'more_button' === $pagination_type ) { $js_data['i18n']['ajax_pagination_more_button'] = __( 'Load more products', 'annasta-filters' ); }
				}

				if( 'yes' === get_option( 'awf_ajax_scroll_on', 'no' ) ) {
					$js_data['ajax_scroll'] = (int) get_option( 'awf_ajax_scroll_adjustment', -100 );
				}

				if( 'yes' === get_option( 'awf_global_wrapper', 'no' ) && empty( $js_data['products_wrapper'] ) && 'no' === $js_data['wrapper_reload'] ) {
					$js_data['products_wrapper'] = 'body';
				}

			}

			if( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
				$js_data['cm_v2'] = 'yes';
				$js_data['block_theme'] = 'yes';

				if( isset( $js_data['products_container'] ) ) {
					$js_data['products_container'] .= ', .wp-block-woocommerce-product-template';
				}

				if( isset( $js_data['pagination_container'] ) ) {
					$js_data['pagination_container'] .= ', .wp-block-query-pagination';
				}

				if( isset( $js_data['no_result_container'] ) ) {
					$js_data['no_result_container'] .= ', .wp-block-woocommerce-product-collection-no-results';
				}

				if( isset( $js_data['ajax_pagination']['next'] ) ) {
					$js_data['ajax_pagination']['next'] .= ', .wp-block-query-pagination-next';
					$js_data['ajax_pagination']['prev'] .= ', .wp-block-query-pagination-previous';
				}
			}

			$js_data = apply_filters( 'awf_js_data', $js_data );
			if( empty( $js_data['query'] ) ) { $js_data['query'] = array(); }
			$js_data['query'] = new ArrayObject( $js_data['query'] );

			return $js_data;
		}

		public function display_block( $preset_id, $block_id ) {

			if( empty( $block_id ) ) {
				$block_id = 'caller-' . A_W_F::$caller_id++;
			}

			$block_id = 'awf-block-' . $block_id;

			return $this->get_preset_html( $preset_id, $block_id );
		}

		public function display_shortcode( $parameters ) {
			return $this->get_preset_html( $parameters['preset_id'], $parameters['shortcode_id'] );
		}

		public function display_widget( $preset_id, $parameters ) {

			if( empty( $parameters['id'] ) ) { $parameters['id'] = 'awf-widget-' . A_W_F::$caller_id; }

			A_W_F::$caller_id++;

			echo $this->get_preset_html( intval( $preset_id ), $parameters['id'] );
		}

		protected function get_preset_html( $preset_id, $caller_id ) {

			if( ! isset( A_W_F::$presets[$preset_id] ) || isset( $this->get_access_to['is_acm'] ) ) { return ''; };

			if( A_W_F::is_rest_request() && ! A_W_F::$preview_mode ) {
				return '<!-- annasta Filters preset #' . intval( $preset_id ) . ' -->';
			}

			if( is_null( $this->page_associations ) ) {
				$this->build_page_associations();
				$this->prepare_product_counts();
			}

			$associated = false;

			if( A_W_F::$preview_mode ) {

				if( is_null( $this->preset ) ) {
					$this->preset = new A_W_F_preset_frontend( $preset_id, $caller_id );
					$html = '';
					$preset_name = esc_html( empty( $this->preset->name ) ? sprintf( __( 'Filters Preset #%1$s', 'annasta-filters' ), $preset_id ) : $this->preset->name );

					if( in_array( $this->preset->display_mode, array( 'togglable', 'togglable-on-s' ) ) ) {
						$html .= '<h3 class="awf-preset-preview-title">annasta Filters for WooCommerce&nbsp;&nbsp;&gt;&nbsp;&nbsp;<span>' . $preset_name . '</span></h3>';
						$html .= '<div class="awf-preset-preview-notice"><div class="awf-preset-preview-notice-heading">' . __( 'Preview unavailable.', 'annasta-filters' ) . '</div><div class="awf-preset-preview-notice-description">';

						switch( $this->preset->display_mode ) {
							case 'togglable':
								$html .= wp_kses( __( '<p><strong>You can remove this block! </strong>This preset is set to be controlled by "Filters" toggle button. In this <strong>Visibility</strong> mode both the preset and the "Filters" button will be auto-inserted into the filterable pages allowed by the <strong>Display on</strong> section.</p>', 'annasta-filters' ), array( 'strong' => array(), 'p' => array() ) );
								break;
							case 'togglable-on-s':
								$html .= wp_kses( __( '<p>This preset is set to the hybrid <strong>Visibility</strong> mode, and will display regularly or get controlled by "Filters" toggle button depending on its display settings.', 'annasta-filters' ), array( 'strong' => array() ) );
								break;
							default: break;
						}

						$html .= '</div></div>';

					} else {
						if( empty( A_W_F::$front->get_access_to['elementor_preview'] ) ) {
							$html .= '<h3 class="awf-preset-preview-title">annasta Filters for WooCommerce&nbsp;&nbsp;&gt;&nbsp;&nbsp;<span>' . $preset_name . '</span></h3>';
						}

						$html .= '<div class="awf-preset-preview-html">' . $this->preset->get_html() . '</div>';
					}

					return $html;
				}
			}

			$preset_associations = (array) A_W_F::$presets[$preset_id]['associations'];

			if( class_exists( 'SitePress' ) && ! empty( $this->get_nondefault_language() ) ) {
				$preset_associations = $this->get_wpml_translated_preset_associations( $preset_id );
			}

			foreach( $preset_associations as $association_id ) {
				if( $this->in_page_associations( $association_id ) ) {
					$associated = true;
					break;
				}
			}

			if( ! $associated ) { return ''; }

			$preset_id = apply_filters( 'awf_display_preset_id', $preset_id );

			$this->preset = new A_W_F_preset_frontend( $preset_id, $caller_id );

			return $this->preset->get_html();
		}

		protected function build_page_associations() {

			$this->page_associations = array();
			$this->page_parent_associations = array();

			foreach( $this->query->tax as $taxonomy => $terms ) {

				foreach( $terms as $slug ) {
					if( false !== ( $term = get_term_by( 'slug', $slug, $taxonomy ) ) ) {
						$this->add_associations( $term, $taxonomy );
					}
				}
			}
		}

		protected function add_associations( $term, $taxonomy, $is_parent = false ) {
			$term->slug = urldecode( $term->slug );

			if( $term->parent > 0 ) {
				if( false !== ( $parent = get_term_by( 'id', $term->parent, $taxonomy ) ) ) {
					$this->add_associations( $parent, $taxonomy, true );
				}
			}

			if(
				! isset( $this->page_associations[$taxonomy] )
				|| ! isset( $this->page_associations[$taxonomy][$term->parent] )
				|| ! in_array( $term->slug, $this->page_associations[$taxonomy][$term->parent] )
			) {

				switch( $is_parent ) {
					case( true ):
						if( 'no' === get_option( 'awf_include_parents_in_associations', 'yes' ) ) {
							$this->page_parent_associations[$taxonomy][$term->parent][] = $term->slug;
							break;
						}
					default:
						$this->page_associations[$taxonomy][$term->parent][] = $term->slug;
						break;
				}

			}

			return;
		}

		public function in_page_associations( $association_id ) {

			if( ( $association_id === 'all' )
				|| ( $association_id === 'shop-pages' && is_shop() )
				|| ( $this->is_archive && $association_id === $this->is_archive . '--archive-pages' )
				|| ( false !== $this->is_wp_page && $association_id === 'wp_page--' . $this->is_wp_page )
				) {
					return true;
			}

			$association = explode( '--', $association_id );

			if( isset( $this->page_associations[$association[0]] ) ) {

				if( isset( $association[2] ) ) {
					if( ( 'shop-page' === $association[2] && is_shop() ) || ( 'archive-page' === $association[2] && $this->is_archive ) ) {
						foreach( $this->page_associations[$association[0]] as $term ) {
							if( in_array( $association[1], $term ) ) {
								return true;
							}
						}
					}

				} elseif( isset( $association[1] ) && 'shop-pages' === $association[1] && is_shop() ) {
					return true;
				}
			}

			return false;
		}

		public function get_reset_all_exceptions( $exceptions = array() ) {
			$exceptions = array_merge( $exceptions, array( 'ppp', 'orderby' ) );

			if( $this->is_archive ) {
				array_push( $exceptions, $this->is_archive, $this->vars->misc['archive'] );
			}

			return $exceptions;
		}

		public function display_default_wc_pagination() {
			if( ! wc_get_loop_prop( 'is_paginated' ) || ! woocommerce_products_will_display() ) {
				return;
			}

			$args = array(
				'total'   => wc_get_loop_prop( 'total_pages' ),
				'current' => wc_get_loop_prop( 'current_page' ),
				'base'    => esc_url_raw( add_query_arg( 'product-page', '%#%', false ) ),
				'format'  => '?product-page=%#%',
			);

			if ( ! wc_get_loop_prop( 'is_shortcode' ) ) {
				$args['format'] = '';
				$args['base']   = esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
			}

			$total   = isset( $total ) ? $total : wc_get_loop_prop( 'total_pages' );
			$current = isset( $current ) ? $current : wc_get_loop_prop( 'current_page' );
			$base    = isset( $base ) ? $base : esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
			$format  = isset( $format ) ? $format : '';

			if ( $total <= 1 ) {
				return;
			}

			echo '<nav class="woocommerce-pagination awf-woocommerce-pagination">';

			echo paginate_links(
				apply_filters(
					'woocommerce_pagination_args',
					array( // WPCS: XSS ok.
						'base'      => $base,
						'format'    => $format,
						'add_args'  => false,
						'current'   => max( 1, $current ),
						'total'     => $total,
						'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
						'next_text' => is_rtl() ? '&larr;' : '&rarr;',
						'type'      => 'list',
						'end_size'  => 3,
						'mid_size'  => 3,
					)
				)
			);

			echo '</nav>';		
		}

		public function display_shop_title() {
			if( ! $this->is_sc_page ) {
				echo '<h1 class="woocommerce-products-header__title page-title">', woocommerce_page_title( false ), '</h1>';
			}
		}

		public function display_no_results_msg( $attrs ) {

			$this->add_ajax_products_header();

			wc_no_products_found();
		}

		public function add_ajax_document_title() {
			echo '<div class="awf-document-title" style="display: none;">', wp_get_document_title(), '</div>';
		}

		public function add_ajax_products_header() {
			echo '<header class="woocommerce-products-header">';

			if( ! $this->is_sc_page ) {
				if( wp_doing_ajax() ) {
					add_filter( 'woocommerce_page_title', array( $this, 'adjust_page_title' ) );
					add_filter( 'document_title_parts', array( $this, 'adjust_document_title' ) );
				}

				if( $this->is_archive ) {
					$term = get_term_by( 'slug', reset( $this->query->tax[$this->is_archive] ), $this->is_archive );
					if( $term ) {

						if( 'yes' === get_option( 'awf_archive_components_support', 'yes' ) ) {

							do_action( 'awf_add_ajax_products_header_title', $term->name );
							$this->add_ajax_document_title();
							if( 'yes' === get_option( 'awf_add_seo_meta_description', 'no' ) ) {
								echo '<div class="awf-meta-description" style="display: none;">' , esc_attr( A_W_F::get_seo_meta_description( $this->query ) ) , '</div>';
							}

							if( 1 === count( $this->query->tax[$this->is_archive] ) ) {
								if( 'yes' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
									echo '<div class="awf-ajax-term-description term-description">', wp_kses_post( wpautop( strip_shortcodes( $term->description ) ) ), '</div>';
								} else {
									echo '<div class="term-description" style="display: none;">', wp_kses_post( wpautop( strip_shortcodes( $term->description ) ) ), '</div>';
								}

							} else {
								echo '<div class="term-description" style="display: none;"><p></p></div>';
							}
						}

						if( 'yes' === get_option( 'awf_breadcrumbs_support', 'yes' ) ) {
							$this->add_awf_breadcrumbs_support();
						}
					}

				} else {
					do_action( 'awf_add_ajax_products_header_title', woocommerce_page_title( false ) );
					$this->add_ajax_document_title();
					if( 'yes' === get_option( 'awf_add_seo_meta_description', 'no' ) ) {
						echo '<div class="awf-meta-description" style="display: none;">' , esc_attr( A_W_F::get_seo_meta_description( $this->query ) ) , '</div>';
					}
				}
			}

			echo '</header>';
		}

		public function add_ajax_products_header_title( $title = true ) {

			if( empty( $title ) ) {
				if( 'yes' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
					echo '<div class="awf-wc-shop-title" style="display: none;"></div>';
				} else {
					if( 'seo' === get_option( 'awf_shop_title', 'wc_default' ) ) {
						echo '<div class="awf-wc-shop-title" style="display: none;"></div>';
					}
				}

			} else {
				if( 'yes' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
					if( 'yes' === get_option( 'awf_remove_wc_shop_title', 'no' ) ) {
						echo '<div class="awf-wc-shop-title" style="display: none;">', wp_kses_post( woocommerce_page_title( false ) ), '</div>';
					} else {
						echo '<h1 class="awf-ajax-page-title woocommerce-products-header__title page-title">', wp_kses_post( woocommerce_page_title( false ) ), '</h1>';
					}

				} else {
					if( $this->is_archive ) {
						echo '<div class="awf-wc-shop-title" style="display: none;">', wp_kses_post( $this->get_archive_title() ), '</div>';

					} else {
						if( 'seo' === get_option( 'awf_shop_title', 'wc_default' ) ) {
							echo '<div class="awf-wc-shop-title" style="display: none;">', wp_kses_post( woocommerce_page_title( false ) ), '</div>';
						}
					}
				}
			}

		}

		/**
		 * Function to adjust the WooCommerce page title.
		 * 
		 * Hooked to the woocommerce_page_title filter.
		 *
		 * @deprecated 1.5.5 in favour of adjust_page_title.
		 * @param string $title page title
		 * @return string adjusted page title
		 */

		public function adjust_shop_title( $title ) {
			wc_deprecated_function(  __METHOD__, '1.5.5', 'adjust_page_title method' );

			return $this->adjust_page_title( $title );
		}

		/**
		 * Adjust the WooCommerce page title.
		 * 
		 * Hooked to the woocommerce_page_title filter.
		 *
		 * @since 1.5.5
		 * @param string $title page title
		 * @return string adjusted page title
		 */
		public function adjust_page_title( $title ) {

			if( $this->is_archive ) {
				if( wp_doing_ajax() || 1 < count( $this->query->tax[$this->is_archive] ) ) {
					$title = $this->get_archive_title();
				}

			} else {
				switch( get_option( 'awf_shop_title', 'wc_default' ) ) {
					case 'awf_default':
						$title = get_option( 'awf_default_shop_title', _x( 'Shop', 'Default page title', 'annasta-filters' ) );
						break;
					case 'seo':
						$title = A_W_F::get_seo_title( $this->query, 'shop' );
						break;
					default:
						break;
				}
			}

			return $title;
		}

		public function adjust_document_title( $title ) {
			/* $title = array( 'title', 'page', 'tagline', 'site' ) */

			if( $this->is_archive ) {
				if( wp_doing_ajax() || 1 < count( $this->query->tax[$this->is_archive] ) ) {
					$title['title'] = $this->get_archive_title();
				}

			} else {
				if( is_shop() || wp_doing_ajax() ) {
					switch( get_option( 'awf_page_title', 'wc_default' ) ) {
						case 'awf_default':
							$title['title'] = get_option( 'awf_default_page_title', _x( 'Shop', 'Default page title', 'annasta-filters' ) );
							$title['tagline'] = '';
							$title['site'] = '';
							break;
						case 'seo':
							$title['title'] = A_W_F::get_seo_title( $this->query );
							$title['tagline'] = '';
							$title['site'] = '';
							break;
						default:
							$title['title'] = get_the_title( $this->shop_page_id );
							break;
					}
				}
			}

			return $title;
		}

		/**
		 * Adjust the archive title of multi-term archives
		 *
		 * Hooked to the get_the_archive_title filter (Query Title block of block themes, the_archive_title() in themes).
		 * Only the term name is replaced, the prefix and markup around it are kept: with a prefix WordPress wraps
		 * the term name ("Category: <span>Name</span>"), so a plain replacement could hit the prefix.
		 *
		 * @since 1.8.5
		 * @param string $title Archive title, with the prefix.
		 * @param string $original_title Archive title without the prefix (the term name).
		 * @return string Adjusted archive title.
		 */
		public function adjust_archive_title( $title, $original_title = '' ) {

			if( $this->is_archive && 1 < count( $this->query->tax[$this->is_archive] ) && '' !== $original_title ) {
				$archive_title = esc_html( $this->get_archive_title() );

				if( $title === $original_title ) {
					$title = $archive_title;

				} else {
					$title = str_replace( '<span>' . $original_title . '</span>', '<span>' . $archive_title . '</span>', $title );
				}
			}

			return $title;
		}

		/**
		 * Block themes: Term Description block of the filterable archives
		 *
		 * Multi-term archives get no description, and an empty description gets a hidden placeholder
		 * (the block outputs nothing then), so that AJAX updates always have an element to update.
		 *
		 * @since 1.8.5
		 * @param string $block_content Rendered block.
		 * @param array $parsed_block Parsed block.
		 * @param WP_Block $block Block instance.
		 * @return string Adjusted block.
		 */
		public function adjust_term_description_block( $block_content, $parsed_block = array(), $block = null ) {

			if( $block instanceof WP_Block && isset( $block->context['termId'] ) ) { return $block_content; }

			if( '' === trim( $block_content ) || ( $this->is_archive && 1 < count( $this->query->tax[$this->is_archive] ) ) ) {
				$block_content = '<div class="wp-block-term-description" style="display:none;"></div>';
			}

			return $block_content;
		}

		protected function get_archive_title() {

			$terms = array();

			if( ! empty( $this->query->tax[$this->is_archive] ) ) {

				foreach( $this->query->tax[$this->is_archive] as $slug ) {
					$term = get_term_by( 'slug', $slug, $this->is_archive );
					if( false !== $term ) { $terms[] = $term->name; }
				}
			}

			return implode( ', ', $terms );
		}

		/**
		 * WooCommerce taxonomy archive description adjustments
		 * 
		 * Hooked to the woocommerce_taxonomy_archive_description_raw filter
		 * Note: woocommerce_taxonomy_archive_description_raw filter is available since WooCommerce version 6.7.0
		 * 
		 * @since 1.5.5
		 * @param string $description WooCommerce taxonomy archive description.
		 * @param object $term Term object.
		 * @return string adjusted WooCommerce taxonomy archive description.
		 */
		public function adjust_taxonomy_archive_description( $description, $term ) {

			if( ! empty( $this->query->tax[$this->is_archive] )
				&& ( empty( $description ) || 1 < count( $this->query->tax[$this->is_archive] ) )
			) {
				return '<p></p>';
			}

			return $description;
		}

		public function add_awf_breadcrumbs_support() {
			if( ! empty( $this->query->tax[$this->is_archive] ) && empty( $_GET['page_number'] ) ) {

				$terms = array();

				foreach( $this->query->tax[$this->is_archive] as $slug ) {
					$term = get_term_by( 'slug', $slug, $this->is_archive );
					if( false !== $term ) { $terms[] = $term->name; }
				}

				$crumbs = '<span id="awf-breadcrumbs-support" style="display: none;">';
				$crumbs .= implode( get_option( 'awf_seo_filter_values_separator', ', ' ), $terms );				
				$crumbs .= '</span>';

				echo $crumbs;
			}
		}

		public function adjust_breadcrumbs( $crumbs, $class ) {

			if( is_archive() && ! empty( $this->query->tax[$this->is_archive] ) ) {

				$crumbs_to_remove = 1;

				if( ! empty( get_query_var('paged') ) ) { $crumbs_to_remove++; }

				$queried_object = $GLOBALS['wp_query']->get_queried_object();
				if( 0 !== intval( $queried_object->parent ) ) {
					$ancestors = get_ancestors( $queried_object->term_id, $queried_object->taxonomy );
					$crumbs_to_remove += count( $ancestors );			
				}

				array_splice( $crumbs, -1 * $crumbs_to_remove );

				$terms = array();

				foreach( $this->query->tax[$this->is_archive] as $slug ) {
					$term = get_term_by( 'slug', $slug, $this->is_archive );
					if( false !== $term ) { $terms[] = $term->name; }
				}

				$crumbs[] = array( implode( get_option( 'awf_seo_filter_values_separator', ', ' ), $terms ) );

			}

			return $crumbs;
		}

		public function add_to_wp_footer() {
			echo '<div id="awf-filters-listbox-describedby" style="display:none;">' . esc_html__( 'Use "Up" and "Down" arrows to move between options', 'annasta-filters' ) . '</div>';
			echo '<div id="awf-cc-access-msg" style="display:none;">' . esc_html__( 'Click the right arrow to expand children, left arrow to collapse.', 'annasta-filters' ) . '</div>';
			echo '<div id="awf-accessibility-alert" style="display:none;position:fixed;top:0;left:0;margin:0;margin-left:-9999px;padding:0;height:0px;width:0px;opacity:0;"></div>';
		}

		public function display_togglable_presets() {

			$this->get_access_to['awf-togglable-call'] = true;

			foreach( array_keys( A_W_F::$presets ) as $preset_id ) {
				if( 'togglable' === get_option( 'awf_preset_' . $preset_id . '_display_mode', 'visible' ) ) {
					echo do_shortcode( '[annasta_filters preset_id=' . $preset_id . ']' );
				}
			}

			$this->get_access_to['awf-togglable-call'] = false;
		}

		public function get_nondefault_language() {

			if( ! is_null( $this->language ) ) { return $this->language; }

			$this->language = false;

			if( class_exists( 'SitePress' ) ) {
				/* WPML */

				/* ICL_LANGUAGE_CODE keeps the initial language before any $sitepress->switch_lang() would occur */
				/* or $sitepress->get_current_language() !== $sitepress->get_default_language() */

				if( apply_filters( 'wpml_default_language', NULL ) !== ( $language = apply_filters( 'wpml_current_language', NULL ) ) ) {
					$this->language = $language;
				}

			} else if( function_exists( 'pll_default_language' ) ) {
				/* Polylang */

				if( pll_default_language() !== ( $language = pll_current_language() ) ) {
					$this->language = $language;
				}

			} else if( function_exists( 'qtranxf_getLanguageDefault' ) ) {
				/* qTranslate */

				if( qtranxf_getLanguageDefault() !== ( $language = qtranxf_getLanguage() ) ) {
					$this->language = $language;
				}
			}

			return $this->language;
		}

		protected function get_wpml_translated_preset_associations( $preset_id ) {
			$preset_associations = array();

			foreach( A_W_F::$presets[$preset_id]['associations'] as $association_id ) {
				$association_data = explode( '--', $association_id );

				if( 3 === count( $association_data ) ) {
					$translated_term = get_term_by( 'slug', $association_data[1], $association_data[0] );

					if( empty( $translated_term ) ) {
						$preset_associations[] = $association_id;

					} else {
						$association_data[1] = urldecode( $translated_term->slug );
						$preset_associations[] = implode( '--', $association_data );
					}

				} else {
					$preset_associations[] = $association_id;
				}
			}

			return $preset_associations;
		}

		public function add_wpml_to_shortcode_products_query( $args, $attrs, $type ) {
			/* Add language argument to avoid caching issues */

			global $sitepress;
			$args['awf_lang'] = $sitepress->get_current_language();

			return $args;
		}

		protected function maybe_add_wpml_adjustments() {
			global $sitepress;

			if( $sitepress->is_display_as_translated_post_type( 'product' ) && ! empty( $this->get_nondefault_language() ) ) {
				if( class_exists( 'WPML_Display_As_Translated_Tax_Query_Factory' ) ) {
					( new WPML_Display_As_Translated_Tax_Query_Factory() )->create()->add_hooks();
					add_filter( 'posts_where', array( $this, 'add_wpml_wp_query_adjustments' ), 9, 2 ); // priority 9 before WPML hook
					add_filter( 'posts_where', array( $this, 'remove_wpml_wp_query_adjustments' ), 11, 2 ); // priority 11 after WPML hook
				}
			}
		}

		public function add_wpml_wp_query_adjustments( $where, WP_Query $query ) {
			$query->{'awf_query_backup'} = array(
				'is_archive' => $query->is_archive,
				'is_tax' => $query->is_tax,
			);

			$query->is_archive = true;
			$query->is_tax = true;

			return $where;
		}

		public function remove_wpml_wp_query_adjustments( $where, WP_Query $query ) {
			$query->is_archive = isset( $query->awf_query_backup['is_archive'] ) ? $query->awf_query_backup['is_archive'] : $query->is_archive;
			$query->is_tax = isset( $query->awf_query_backup['is_tax'] ) ? $query->awf_query_backup['is_tax'] : $query->is_tax;

			unset( $query->awf_query_backup );

			return $where;
		}

		public function get_price_filter_min_max() {
			global $wpdb;

			$query_args = apply_filters( 'awf_dynamic_price_range_args', array() );

			if( empty( $query_args ) ) {
				$query_args = array(
					'post_type' => 'product',
					'fields' => 'ids',
					'post_status' => 'publish',
					'ignore_sticky_posts' => true,
					'no_found_rows' => true,
					'posts_per_page' => -1,
					'paged' => '',
				);

				if( isset( $this->get_access_to['counts_meta_query'] ) ) {
					$query_args['meta_query'] = $this->get_access_to['counts_meta_query'];
				} else {
					$this->get_access_to['counts_meta_query'] = $query_args['meta_query'] = $this->set_wc_meta_query( array() );
				}

				unset( $query_args['meta_query']['price_filter'] );

				$query_args['post__in'] = $this->get_wc_post__in( array() );

				$query_args['tax_query'] = $this->set_wc_tax_query( array() );
				$this->set_default_visibility( $query_args['tax_query'] );
			}

			$query = new WP_Query( $query_args );

			if( ! empty( $query->posts ) ) {
				$sql = "
				SELECT MIN( min_price ) as min_price, MAX( max_price ) as max_price
				FROM {$wpdb->wc_product_meta_lookup}
				WHERE product_id IN (" . implode( ',', $query->posts ) . ")";

				$min_max_prices = $wpdb->get_row( $sql, ARRAY_A );

				if( empty( $min_max_prices['min_price'] ) ) {
					$min_max_prices['min_price'] = 0;
				}

				if( empty( $min_max_prices['max_price'] ) ) {
					$min_max_prices['max_price'] = 1;
				}

				if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
					$tax_class = apply_filters( 'awf_price_filter_tax_class', '' );
					$tax_rates = WC_Tax::get_rates( $tax_class );

					if( $tax_rates ) {
						$min_max_prices['min_price'] += WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $min_max_prices['min_price'], $tax_rates ) );
						$min_max_prices['max_price'] += WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $min_max_prices['max_price'], $tax_rates ) );
					}
				}

				if( class_exists( 'SitePress' ) && function_exists( 'wcml_get_woocommerce_currency_option' ) ) {
					$current_currency = apply_filters( 'wcml_price_currency', NULL );

					if( $current_currency !== wcml_get_woocommerce_currency_option() ) {
						$min_max_prices['min_price'] = apply_filters( 'wcml_raw_price_amount', $min_max_prices['min_price'], $current_currency );
						$min_max_prices['max_price'] = apply_filters( 'wcml_raw_price_amount', $min_max_prices['max_price'], $current_currency );
					}
				}

				return $min_max_prices;
			}

			return array( 'min_price' => 0, 'max_price' => 1 );
		}

		/**
		 * Product counts of a taxonomy for the terms lists (A_W_F_terms_list)
		 *
		 * The products are narrowed only by the context terms (the current archive), not by the filters of the page:
		 * the page's counts state is set aside while the counts are built, and restored afterwards. The shortcode
		 * attributes of a shortcode page don't narrow these counts either.
		 *
		 * Cached in transients with the counts prefix, so the counts cache resets and cleanup apply.
		 *
		 * @since 1.8.5
		 * @param string $taxonomy
		 * @param array $context Taxonomy => term slugs
		 * @return array Term slug => products count
		 */
		public function get_terms_list_counts( $taxonomy, $context = array() ) {

			ksort( $context );
			array_walk( $context, function( &$slugs ) { sort( $slugs ); } );

			$cache_key = array( 'taxonomy' => $taxonomy, 'context' => $context );

			if( ! empty( $this->get_nondefault_language() ) ) {
				$cache_key['lang'] = $this->language;
			}

			if( 'yes' !== get_option( 'awf_force_published_status', 'yes' ) && current_user_can( 'manage_woocommerce' ) ) {
				$cache_key['private'] = 1;
			}

			$cache_name = 'awf_counts_' . A_W_F::get_cache_version() . '_menu_' . md5( wp_json_encode( $cache_key ) );

			if( isset( $this->get_access_to['menu_counts'][$cache_name] ) ) {
				return $this->get_access_to['menu_counts'][$cache_name];
			}

			$counts = get_transient( $cache_name );

			if( ! is_array( $counts ) ) {

				if( is_null( $this->vars ) ) { $this->set_query_vars(); }
				if( ! isset( $this->awf_settings['include_children'] ) ) {
					$this->awf_settings['include_children'] = ( 'yes' === get_option( 'awf_include_children_on', 'yes' ) );
				}

				$query_backup = $this->query;
				$counts_backup = $this->counts;
				$update_counts_cache_backup = $this->update_counts_cache;
				$access_keys = array( 'post__in_cache' => '', 'counts_meta_query' => '', 'counts_product_ids' => '' );
				$access_backup = array_intersect_key( $this->get_access_to, $access_keys );
				$this->get_access_to = array_diff_key( $this->get_access_to, $access_keys );

				$sc_counts_priority = has_filter( 'awf_product_counts_query', array( $this, 'add_sc_attributes_to_product_counts' ) );
				if( false !== $sc_counts_priority ) {
					remove_filter( 'awf_product_counts_query', array( $this, 'add_sc_attributes_to_product_counts' ), $sc_counts_priority );
				}

				$this->query = (object) array( 'awf' => array(), 'tax' => $context, 'meta' => array(), 'range' => array() );
				$this->counts = array();

				$this->build_taxonomy_counts( $taxonomy );

				$counts = isset( $this->counts[$taxonomy] ) ? $this->counts[$taxonomy] : array();

				$this->query = $query_backup;
				$this->counts = $counts_backup;
				$this->update_counts_cache = $update_counts_cache_backup;
				$this->get_access_to = array_merge( array_diff_key( $this->get_access_to, $access_keys ), $access_backup );

				if( false !== $sc_counts_priority ) {
					add_filter( 'awf_product_counts_query', array( $this, 'add_sc_attributes_to_product_counts' ), $sc_counts_priority );
				}

				if( ! empty( $lifespan = intval( get_option( 'awf_counts_cache_days', '10' ) ) ) ) {
					set_transient( $cache_name, $counts, DAY_IN_SECONDS * $lifespan );

					if( false === get_transient( 'awf_counts_cleanup' ) ) {
						A_W_F::schedule_counts_cache_cleanup();
					}
				}
			}

			return $this->get_access_to['menu_counts'][$cache_name] = $counts;
		}

		/* Terms lists premium settings (A_W_F_premium_frontend) */
		public static function sanitize_terms_list_premium_settings( $settings, $data ) { return $settings; }
		public function filter_terms_list_terms( $allowed, $terms_by_id, $settings, $taxonomy ) { return $allowed; }
		public function is_terms_list_dim_empty( $settings ) { return false; }
		public function is_terms_list_dynamic( $settings ) { return false; }
		public function get_terms_list_columns( $settings ) { return 1; }
		public function enqueue_terms_list_css() {}

		/**
		 * Loads the cached counts of the current filters state
		 *
		 * The cache key holds the query (without ordering and products per page), the language, the shortcode page
		 * attributes (the shortcode page itself doesn't narrow the counts), private products visibility and the WCML currency.
		 */
		public function prepare_product_counts() {

			if( is_array( $this->counts ) ) { return; }

			$counts_cache_query = clone $this->query;

			unset( $counts_cache_query->awf['orderby'] );
			unset( $counts_cache_query->awf['ppp'] );

			if( ! empty( $this->get_nondefault_language() ) ) {
				$counts_cache_query->awf_lang = $this->language;
			}

			$sc_context = $this->get_counts_sc_context();
			if( ! empty( $sc_context ) ) {
				$counts_cache_query->awf_sc = $sc_context;
			}

			if( 'yes' !== get_option( 'awf_force_published_status', 'yes' ) && current_user_can( 'manage_woocommerce' ) ) {
				$counts_cache_query->awf_private = 1;
			}

			if( ( isset( $this->query->range['min_price'] ) || isset( $this->query->range['max_price'] ) ) && class_exists( 'SitePress' ) && function_exists( 'wcml_get_woocommerce_currency_option' ) ) {
				$counts_cache_query->awf_currency = apply_filters( 'wcml_price_currency', NULL );
			}

			// $counts_cache_query = apply_filters( 'awf_product_counts_query_cache', $counts_cache_query );

			$this->counts_cache_name = 'awf_counts_' . A_W_F::get_cache_version() . '_' . md5( wp_json_encode( $counts_cache_query ) );
			$this->counts = get_transient( $this->counts_cache_name );

			if( false === $this->counts ) {
				$this->counts = array();
			} else {
				$this->update_existing_counts_cache = true;
			}

		}

		/**
		 * Saves the counts of the current filters state
		 *
		 * An extended entry keeps its original expiration (persistent object caches don't expose it: full lifespan there).
		 * The expired rows are removed in a WP Cron request, not in the visitor's one, whatever the cache reset mode.
		 */
		public function update_counts_cache() {

			if( true === $this->update_counts_cache && ! empty( $this->counts_cache_name ) && ! empty( $this->counts ) ) {

				if( ! empty( $lifespan = intval( get_option( 'awf_counts_cache_days', '10' ) ) ) ) {

					$expiration = DAY_IN_SECONDS * $lifespan;

					if( $this->update_existing_counts_cache && ! wp_using_ext_object_cache() ) {
						$timeout = intval( get_option( '_transient_timeout_' . $this->counts_cache_name ) );
						if( $timeout > 0 ) {
							$expiration = $timeout - time();
						}
					}

					if( $expiration <= 0 ) { $expiration = 1; }

					set_transient( $this->counts_cache_name, $this->counts, $expiration );
				}

				if( false === get_transient( 'awf_counts_cleanup' ) ) {
					A_W_F::schedule_counts_cache_cleanup();
				}
			}
		}

		public function return_false() {
			return false;
		}

		final function __clone() {}
		final function __wakeup() {}
		public static function get_instance() {
			if( is_null( self::$instance ) ) {
				$called_class = get_called_class();
				self::$instance = new $called_class;
			}
			return self::$instance;
		}

//A_W_F::format_print_r($query);

	}
}

?>