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
		
		public $url_query;
		public $page_associations;
		public $page_parent_associations;
		public $permalinks_on = false;
		public $shop_on_frontpage;
		public $shop_page_id;
		public $shop_url;
		public $current_url;
		public $seo_parts;

		protected $is_wp_page = false;
		public $is_sc_page = false;
		public $is_archive = false;

		public $counts;
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

			$this->get_access_to['remove_pagination_args'] = array( 'awf_front', 'awf_action', 'awf_query', 'awf_ajax_extras', 'awf_sc', 'awf_sc_page', 'awf_archive_page' );

			add_filter( 'paginate_links', function( $href ) {
				return remove_query_arg( $this->get_access_to['remove_pagination_args'], $href );
			});
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

				if( empty( intval( get_option( 'awf_counts_cache_days', '10' ) ) ) ) {
					$sc_args['cache'] = false;
				}

				do_action( 'awf_ajax_filter_before_wc_products_shortcode' );
				
				$this->do_wc_products_shortcode( $sc_args );

			} else if( 'get_search_autocomplete' === $_GET['awf_action'] ) {
				if( isset( $_GET['awf_sc_page'] ) ) { $this->is_sc_page = (int) $_GET['awf_sc_page']; }
				
				$filter_data = sanitize_title( $_GET['awf_filter'] );
				$filter_data = explode( '-filter-', $filter_data );
				$filter_data = array_pop( $filter_data );
				$filter_data = explode( '-', str_replace( '-wrapper', '', $filter_data) );
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
				);
				
				if( class_exists( 'SitePress' ) ) {
					$this->maybe_add_wpml_adjustments();
					add_filter( 'woocommerce_shortcode_products_query', array( $this, 'add_wpml_to_shortcode_products_query' ), 10, 3 );
					add_filter( 'wcml_load_multi_currency_in_ajax', '__return_true', 10, 1 );
				}

				if( empty( $filter->settings['type_options']['autocomplete_filtered'] ) ) {
					$this->query->awf['search'] = sanitize_text_field( $_GET['awf_query'][$this->vars->awf['search']] );
					
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

			} else if( 'update_filters' === $_GET['awf_action'] ) {

// $microtime = microtime();

				$response = array();
				$this->awf_settings['include_children'] = ( 'yes' === get_option( 'awf_include_children_on', 'yes' ) );
				$this->set_query_vars();

				if( ! empty( $_GET['awf_sc_attrs'] ) ) {
					if( ! empty( $_GET['awf_sc_attrs']['category'] ) ) {
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
				$this->prepare_product_counts();

				foreach( $_GET['awf_callers'] as $caller ) {
					$caller = sanitize_text_field( $caller );

					$pieces = substr( $caller, 0, -strlen( '-wrapper' ) );
					$pieces = explode( '-', $pieces );
					$preset_id = (int) array_pop( $pieces );

					if( isset( A_W_F::$presets[$preset_id] ) ) { $preset = new A_W_F_preset_frontend( $preset_id ); }
				}

				foreach( $this->counts as $taxonomy => $counts ) {
					$response['counts'][$this->vars->tax[$taxonomy]] = $counts;
				}

				if( 'yes' === get_option( 'awf_dynamic_price_ranges', 'no' ) ) {
					$response['price_filter_min_max'] = $this->get_price_filter_min_max();
				}
				
// $response['time'] = microtime() - $microtime ;
				echo( json_encode( $response ) );
			}

			die();
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
		
		protected function build_ajax_queries() {
			
			if( ! empty( $_GET['awf_query'] ) ) {
				
				foreach( $_GET['awf_query'] as $var => $value ) {
					if( ( false !== ( $taxonomy = array_search( $var, $this->vars->tax ) ) ) ) {
						$this->query->tax[$taxonomy] = array_map( 'sanitize_text_field', explode( ',', $value ) );
						
					} elseif( false !== ( $awf_var_name = array_search( $var, $this->vars->awf ) ) ) {
						$this->query->awf[$awf_var_name] = sanitize_text_field( $value );

					} else if( false !== ( $meta_var_name = array_search( $var, $this->vars->meta ) ) ) {
						$this->query->meta[$meta_var_name] = array_map( 'sanitize_text_field', explode( ',', $value ) );
						
					} elseif( false !== ( $range_var_name = array_search( $var, $this->vars->range ) ) ) {
						$this->query->range[$range_var_name] = (float) $value;
					}
				}
				
				$this->set_numeric_taxonomy_ranges();
			}
			
			if( isset( $_GET['awf_query'][$this->vars->misc['archive']] )
				&& false !== ( $tax = array_search( $_GET['awf_archive_page'], $this->vars->tax ) )
				&& isset( $this->query->tax[$tax] )
			) {
				$this->is_archive = $tax;
				$this->setup_archive( implode( ',', $this->query->tax[$tax] ) );
			}

			$this->sort_query();
		}
		
		public function initialize() {

			$this->permalinks_on = ! empty( get_option( 'permalink_structure' ) );
			
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

		public function wc_query( $query, $class_wc_query ) {}

		public function before_wc_query( $query ) {

			if( ! $query->is_main_query() || is_admin() ) { return; }
			
			$is_shop = false;
			$product_taxonomies = array_keys( $this->vars->tax );

			if( $query->is_post_type_archive( 'product' ) ) {
				$is_shop = true;
				$this->filter_on = true;
				
			} elseif( $query->is_tax( $product_taxonomies ) ) {
				$this->is_archive = true;
				$this->filter_on = true;
			
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
							$terms = $value;
						} else {
							$terms = explode( ',', $value );
						}

						$this->query->tax[$var_name] = array_map( 'sanitize_text_field', $terms );

					} else if( false !== ( $awf_var_name = array_search( $var, $this->vars->awf ) ) ) {
						$this->query->awf[$awf_var_name] = sanitize_text_field( $value );

					} else if( false !== ( $meta_var_name = array_search( $var, $this->vars->meta ) ) ) {
						$this->query->meta[$meta_var_name] = explode( ',', $value );
						$this->query->meta[$meta_var_name] = array_map( 'sanitize_text_field', $this->query->meta[$meta_var_name] );

					} else if( false !== ( $range_var_name = array_search( $var, $this->vars->range ) ) ) {
						$this->query->range[$range_var_name] = (float) $value;
					}
				}
				
				$this->set_numeric_taxonomy_ranges();

				if( empty( $this->query->awf['search'] ) && ! empty( $query->get( 's' ) ) ) {
					$this->query->awf['search'] = $query->query['s'];
				}

				if( $this->is_archive ) {
					if( 1 === count( array_intersect_key( $query->tax_query->queried_terms, $this->vars->tax ) )
						&& ( ! empty( $query->get( $this->vars->misc['archive'] ) ) || ! count( $this->query->tax ) )
					) {
						$queried_object = $query->get_queried_object();
						$this->is_archive = $queried_object->taxonomy;
						$this->setup_archive( $query->get( $this->is_archive, '' ) );

						if( 'yes' === get_option( 'awf_archive_components_support', 'yes' ) ) {
							add_filter( 'woocommerce_page_title', array( $this, 'adjust_page_title' ) );
							add_filter( 'document_title_parts', array( $this, 'adjust_document_title' ) );
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
			}
			
			$this->url_query = $this->get_url_query();
			$this->sort_query();
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

					if ( $woocommerce_wpml && isset( $woocommerce_wpml->url_translation ) ) {
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
			
			$this->current_url = user_trailingslashit( get_term_link( reset( $archive_terms ), $this->is_archive ) );
			remove_filter( 'term_link', array( $this, 'adjust_hierarchical_term_links' ), 10 );
			remove_filter( 'term_link', array( $this, 'adjust_wpml_custom_taxonomy_base' ), 15 );
			$this->current_url = urldecode( $this->current_url );

			if( is_wp_error( $this->current_url ) ) {
				$this->current_url = $this->shop_url;

			} else {
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

		public function adjust_hierarchical_term_links( $termlink, $term, $taxonomy ) {
			$slug = $term->slug;
			$t    = get_taxonomy( $taxonomy );
	
			if ( isset( $t->rewrite['hierarchical'] ) && $t->rewrite['hierarchical'] ) {
				global $wp_rewrite;

				$termlink = $wp_rewrite->get_extra_permastruct( $taxonomy );
				$termlink = str_replace( "%$taxonomy%", $slug, $termlink );

				$termlink = home_url( user_trailingslashit( $termlink, 'category' ) );
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
						'value' => NULL,
						'compare' => 'NOT IN',
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

          switch( get_option( 'awf_ss_engine', 'wc' ) ) {
            case 'relevanssi':

              if( defined( 'RELEVANSSI_PREMIUM' ) ) {
                $sq = new WP_Query( array(
                  's'           		=> $this->query->awf['search'],
                  'fields'					=> 'ids',
                  'posts_per_page' 	=> -1,
                  'post_types'  		=> array( 'product', 'product_variation' ),
                  'relevanssi'  		=> true,
                ) );

                $ss_posts = array_merge( array( 0 ), $sq->posts );
              }
              
              break;

            case 'aws':

              if( function_exists( 'aws_search' ) ) {
                if( isset( $_GET['type_aws'] ) ) {

                  $ss_bypass = true;
    
                  add_action( 'wp', function() {
                    add_filter( 'aws_search_results_products_ids', array( $this, 'aws_search_get_ids' ), 10, 2 );
                    aws_search( $this->query->awf['search'] );
                    remove_filter( 'aws_search_results_products_ids', array( $this, 'aws_search_limit_to_ids' ), 10 );
    
                    if( isset( $this->get_access_to['aws_search_results'] ) ) {
                      if( empty( $this->get_access_to['post__in_cache'] ) ) {
                        $this->get_access_to['post__in_cache'] = array_merge( array( 0 ), $this->get_access_to['aws_search_results'] );
                      } else {
                        $this->get_access_to['post__in_cache'] = array_merge( array( 0 ), array_intersect( $this->get_access_to['post__in_cache'], $this->get_access_to['aws_search_results'] ) );
                      }
    
                      unset( $this->get_access_to['aws_search_results'] );
                    }
                  }, 10 );
    
                } else {

                  add_filter( 'aws_search_results_products_ids', array( $this, 'aws_search_get_ids' ), 10, 2 );
                  $ss_posts = aws_search( $this->query->awf['search'] );
                  remove_filter( 'aws_search_results_products_ids', array( $this, 'aws_search_limit_to_ids' ), 10 );
    
                  if( isset( $this->get_access_to['aws_search_results'] ) && is_array( $this->get_access_to['aws_search_results'] ) ) {
                    $ss_posts = array_merge( array( 0 ), $this->get_access_to['aws_search_results'] );
                    unset( $this->get_access_to['aws_search_results'] );
                  } else {
                    $ss_posts = array( 0 );
                  }
                }
              }

              break;

            default: break;
          }

          if( ! $ss_bypass ) {
            if( empty( $ss_posts ) ) {

              $data_store = WC_Data_Store::load( 'product' );
              $ss_posts = array_merge( array( 0 ), $data_store->search_products( wc_clean( wp_unslash( $this->query->awf['search'] ) ), '', true, true ) );
            }

            if( empty( $post__in ) ) { $post__in = $ss_posts;
            } else { $post__in = array_intersect( $post__in, $ss_posts ); }

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
		
		public function aws_search_get_ids( $product_ids, $s ) {

			$this->get_access_to['aws_search_results'] = $product_ids;
			$product_ids = array();

			return $product_ids;
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

      if( isset( $this->query->awf['stock'] ) ) {					
        $availability = $this->query->awf['stock'];
        $operator='IN';

      } elseif( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) ) {
        $availability = 'outofstock';
        $operator='NOT IN';

      } else {
        return;
      }

      if( 'yes' === get_option( 'woocommerce_attribute_lookup_enabled', 'no' ) ) {

        global $wpdb;
        
        $availability = esc_sql( $availability );
        $operator = esc_sql( $operator );

        $products = get_transient( 'awf_vss_products_cache_' . sanitize_key( $operator . $availability ) );
        
        if( false === $products ) {
          $variable_term_id = get_transient( 'awf_vss_variable_term_id' );

          if( false === $variable_term_id ) {
            $sql = $wpdb->prepare( "SELECT term_id FROM {$wpdb->terms} WHERE name=%s", 'variable' );
            $variable_term = $wpdb->get_row( $sql, OBJECT );

            if( $variable_term && ! empty( $variable_term->term_id ) ) {
              $variable_term_id = $variable_term->term_id;
            } else {
              $variable_term_id = 0;
            }

            set_transient( 'awf_vss_variable_term_id', $variable_term_id, DAY_IN_SECONDS * 30 );
          }

          if( empty( $variable_term_id ) ) {
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
              $variable_term_id
            );
          }

          $products = $wpdb->get_col( $sql );

          set_transient( 'awf_vss_products_cache_' . sanitize_key( $operator . $availability ), $products, HOUR_IN_SECONDS * 5 );
        }

        $variations = get_transient( 'awf_vss_variations_cache_' . sanitize_key( $operator . $availability ) );

        if( false === $variations ) {
          $sql = $wpdb->prepare(
            "SELECT DISTINCT attributes_lookup.product_id
              FROM {$wpdb->prefix}wc_product_attributes_lookup AS attributes_lookup
              INNER JOIN {$wpdb->prefix}wc_product_meta_lookup AS product_lookup ON product_lookup.product_id = attributes_lookup.product_id
            WHERE
              attributes_lookup.is_variation_attribute = 1
              AND product_lookup.stock_status {$operator} ('%s')
            LIMIT 30000
          ",
          $availability
          );

          $variations = $wpdb->get_col( $sql );

          set_transient( 'awf_vss_variations_cache_' . sanitize_key( $operator . $availability ), $variations, HOUR_IN_SECONDS * 5 );
        }

        $attributes_filters = array_filter( $this->query->tax, function( $v, $k ) {
          if( 0 === strpos( $k, 'pa_' ) && ! empty( $v ) ) { return true; }
          return false;
        }, ARRAY_FILTER_USE_BOTH );

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

                $ids = $default_lang_ids;
              }

              $ids = esc_sql( implode( ',', $ids ) );

              $filtered_out_variations = $wpdb->get_col(
                "SELECT DISTINCT product_id
                  FROM {$wpdb->prefix}wc_product_attributes_lookup
                WHERE
                  is_variation_attribute = 1
                  AND taxonomy = '{$taxonomy}'
                  AND term_id NOT IN ({$ids})
                LIMIT 30000
                "
              );

              $variations = array_diff( $variations, $filtered_out_variations );
            }
          }
        }

        if( ! empty( $variations ) ) {
          $variations_in = esc_sql( implode( ',', $variations ) );

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
						AND postmeta.meta_value {$operator} ('{$availability}')
					LIMIT 30000"
				);

				$products_ids = array_merge( $products_ids, $variable_products_ids );

			} else {

				if( 'NOT IN' === $operator ) { $availability = 'instock'; }

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
								$in_clause[] = "('attribute_" . esc_sql( $meta_key ) . "', '" . esc_sql( $meta_value ) . "')";
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
							WHERE ({$in_clauses}) AND (posts.ID IN ({$attributes_count_check}))
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
									$in_clause[] = "('attribute_" . esc_sql( $meta_key ) . "', '" . esc_sql( $meta_value ) . "')";
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
								WHERE ({$in_clauses}) AND (posts.ID IN ({$attributes_count_check}))
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
										(pm{$i}.meta_key, pm{$i}.meta_value) IN (('attribute_" . esc_sql( $meta_key ) . "', '" . esc_sql( $meta_value ) . "'))
										OR pm{$i}.meta_key IN ({$unfiltered_attributes}) OR (pm{$i}.meta_key, pm{$i}.meta_value) IN (('_stock_status', '{$availability}'))
									GROUP BY pm{$i}.post_id
									HAVING COUNT(*) = 3
								)";

								if( 'NOT IN' === $operator ) {
									$i++;

									$clauses[] = "posts.ID IN (
										SELECT pm{$i}.post_id FROM {$wpdb->postmeta} AS pm{$i}
										WHERE
											(pm{$i}.meta_key, pm{$i}.meta_value) IN (('attribute_" . esc_sql( $meta_key ) . "', '" . esc_sql( $meta_value ) . "'))
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
			$termlink = add_query_arg( $this->get_access_to['product_categories_url_parameters'], $termlink );

			return $termlink;
		}

		/**
		 * Build product counts for the requested taxonomy terms
		 * 
		 * The counts reflect the state of the current active filters.
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

      if( isset( $terms_by_parent[0] ) ) {

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

        $this->update_counts_cache = true;
      }
    }

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

					$this->get_access_to['counts_categories_for_sc'] = array(
						'taxonomy'         => 'product_cat',
						'terms'            => $categories,
						'field'            => $field,
						'operator'         => $this->get_access_to['sc_attributes']['cat_operator'],
		
						/*
						* When cat_operator is AND, the children categories should be excluded,
						* as only products belonging to all the children categories would be selected.
						*/
						'include_children' => 'AND' === $this->get_access_to['sc_attributes']['cat_operator'] ? false : true,
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

		public function add_search_autocomplete_all_results_link() {
			$url_query_args = $this->get_url_query();
			
			if( isset( $url_query_args[$this->vars->awf['search']] ) ) {
				$url_query_args[$this->vars->awf['search']] = urlencode( $url_query_args[$this->vars->awf['search']] );
			}

      $total = '';
      if( ! empty( $total = wc_get_loop_prop( 'total' ) ) ) {
        $total = ' <span class="awf-ac-total-results">' . $total . '</span>';
      }

      $label = isset( $this->get_access_to['autocomplete_filter']->settings['style_options']['autocomplete_view_all_label'] ) ? $this->get_access_to['autocomplete_filter']->settings['style_options']['autocomplete_view_all_label'] : __( 'View all results', 'annasta-filters' );
			
			echo '<div class="awf-s-autocomplete-view-all-container"><a href="' , add_query_arg( $url_query_args, $this->current_url ) , '">' , esc_html( $label ) , $total, '</a></div>' , PHP_EOL;
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

      if( ! empty( $this->query->awf['search'] ) ) {
        $terms = get_terms( array(
          'taxonomy'   => $taxonomy,
          'hide_empty' => false,
          'name__like' => $this->query->awf['search'],
          'number' => intval( $this->get_access_to['autocomplete_results_count'] ),
        ) );
      }

      if( ! empty( $terms ) ) {
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
          $p = false;

          if( empty( get_option( 'awf_multilingual_support' ) ) ) {
            $p = mb_stripos( $t->name, $this->query->awf['search'] );

          } else {
            if( in_array( 'greek', get_option( 'awf_multilingual_support' ) ) ) {
              $p = mb_stripos( $this->remove_accents_el( $t->name ), $this->remove_accents_el( $this->query->awf['search'] ) );

            } else {
              $p = mb_stripos( $t->name, $this->query->awf['search'] );
            }
          }
          
          if( false !== $p ) {
            $s = mb_substr( $t->name, $p, mb_strlen( $this->query->awf['search'] ) );
            $t->name = str_replace( $s, '<strong>' . $s . '</strong>', $t->name );
          }

          $html .= '<div class="awf-ac-taxonomy-term awf-ac-' . esc_attr( $t->slug ) . '"><a href="' . get_term_link( $t->term_id, $taxonomy ) . '">' . $t->name . '</a></div>';
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
        
		protected function remove_accents_el( $string ) {
      $chars = array(
          'Ά' => 'Α',
          'ά' => 'α',
          'Έ' => 'Ε',
          'έ' => 'α',
          'Ί' => 'Ι',
          'ί' => 'ι',
          'ΐ' => 'ϊ',
          'Ύ' => 'Υ',
          'ύ' => 'υ',
          'ΰ' => 'ϋ',
          'Ή' => 'Η',
          'ή' => 'η',
          'Ό' => 'Ο',
          'ό' => 'ο',
          'Ώ' => 'Ω',
          'ώ' => 'ω',
      );

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

		public function adjust_wc_pagination( $args ) {
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

		public function add_awf_sc_class( $out, $pairs, $atts, $shortcode ) {
			if( empty( $out['class'] ) ) { $out['class'] = 'awf-sc'; } else { $out['class'] .= ' awf-sc'; }

			if( 'sale_products' === $shortcode ) {
				$out['awf_on_sale_sc'] = 'yes';
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
				echo '<input type="hidden" name="' . $name . '" value="' . $value . '" class="awf-sc-var">';
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

		public function redirect_archives() {
			if( $this->is_archive ) {
				unset( $this->url_query[$this->vars->misc['archive']] );
				$this->url_query[$this->vars->tax[$this->is_archive]] = implode( ',', $this->query->tax[$this->is_archive] );
				wp_redirect( add_query_arg( $this->url_query, $this->shop_url ) );
				die;
			}
		}

		public function enqueue_scripts( $hook ) {
			
			$google_fonts = array();
			
			wp_enqueue_style( 'awf-nouislider', A_W_F_PLUGIN_URL . '/styles/nouislider.min.css', array(), A_W_F::$plugin_version );
			wp_enqueue_script( 'awf-wnumb', A_W_F_PLUGIN_URL . '/code/js/wNumb.js', array() );
			
			if ( 'yes' === get_option( 'awf_pretty_scrollbars' ) ) {
				wp_enqueue_style( 'awf-pretty-scrollbars', A_W_F_PLUGIN_URL . '/styles/perfect-scrollbar.css' );
				wp_enqueue_script( 'awf-pretty-scrollbars', A_W_F_PLUGIN_URL . '/code/js/perfect-scrollbar.min.js', array(), A_W_F::$plugin_version );
			}
			
			if ( ! empty( get_option( 'awf_daterangepicker_enabled' ) ) ) {
				wp_enqueue_style( 'awf-daterangepicker', A_W_F_PLUGIN_URL . '/styles/daterangepicker.css' );
				wp_enqueue_script( 'awf-moment', A_W_F_PLUGIN_URL . '/code/js/moment.min.js', array(), A_W_F::$plugin_version );
				wp_enqueue_script( 'awf-daterangepicker', A_W_F_PLUGIN_URL . '/code/js/daterangepicker.js', array( 'jquery', 'awf-moment' ), A_W_F::$plugin_version );
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
			wp_enqueue_script( 'awf', A_W_F_PLUGIN_URL . '/code/js/awf.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'awf-nouislider' ), A_W_F::$plugin_version );

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

			$js_data = array( 
				'filters_url' => $current_url_pieces[0],
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'ajax_mode' => get_option( 'awf_ajax_mode', 'compatibility_mode' ),
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
				)
			);
			
			if( $this->is_archive ) {
				$js_data['archive_page'] = $this->vars->tax[$this->is_archive];
				$js_data['archive_identifier'] = $this->vars->misc['archive'];
				$js_data['query'][$this->vars->tax[$this->is_archive]] = implode( ',', $this->query->tax[$this->is_archive] );
				unset( $js_data['query'][$this->is_archive] );

				if( $this->permalinks_on ) {
					$js_data['filters_url'] = $js_data['filters_url'];
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

				$selectors = get_option( 'awf_custom_selectors', array() );

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
			
			if( is_null( $this->page_associations ) ) {
				$this->build_page_associations();
				$this->prepare_product_counts();
			}

			$associated = false;

			if( A_W_F::$preview_mode ) {

				if( is_null( $this->preset ) ) {
					$this->preset = new A_W_F_preset_frontend( $preset_id, $caller_id );
					$html = '';
					$preset_name = empty( $this->preset->name ) ? sprintf( __( 'Filters Preset #%1$s', 'annasta-filters' ), $preset_id ) : $this->preset->name;

					if( in_array( $this->preset->display_mode, array( 'togglable', 'togglable-on-s' ) ) ) {
						$html .= '<h3 class="awf-preset-preview-title">annasta WooCommerce Filters&nbsp;&nbsp;&gt;&nbsp;&nbsp;<span>' . $preset_name . '</span></h3>';
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
							$html .= '<h3 class="awf-preset-preview-title">annasta WooCommerce Filters&nbsp;&nbsp;&gt;&nbsp;&nbsp;<span>' . $preset_name . '</span></h3>';
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
					$url_query[$this->is_archive] = $url_query[$this->vars->tax[$this->is_archive]];
					unset( $url_query[$this->vars->tax[$this->is_archive]] );
				}
			}
			
			return $url_query;
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
									echo '<div class="awf-ajax-term-description term-description"><p>', $term->description, '</p></div>';
								} else {
									echo '<div class="term-description" style="display: none;"><p>', $term->description, '</p></div>';
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
						echo '<div class="awf-wc-shop-title" style="display: none;">', woocommerce_page_title( false ), '</div>';
					} else {
						echo '<h1 class="awf-ajax-page-title woocommerce-products-header__title page-title">', woocommerce_page_title( false ), '</h1>';
					}

				} else {
					if( $this->is_archive ) {
						echo '<div class="awf-wc-shop-title" style="display: none;">', $this->get_archive_title(), '</div>';

					} else {
						if( 'seo' === get_option( 'awf_shop_title', 'wc_default' ) ) {
							echo '<div class="awf-wc-shop-title" style="display: none;">', woocommerce_page_title( false ), '</div>';
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

		public function prepare_product_counts() {

			if( is_array( $this->counts ) ) { return; }

			$counts_cache_query = clone $this->query;

			unset( $counts_cache_query->awf['orderby'] );
			unset( $counts_cache_query->awf['ppp'] );

			if( ! empty( $this->get_nondefault_language() ) ) {
				$counts_cache_query->awf_lang = $this->language;
			}

			// $counts_cache_query = apply_filters( 'awf_product_counts_query_cache', $counts_cache_query );

			$this->counts_cache_name = 'awf_counts_' . md5( wp_json_encode( $counts_cache_query ) );
			$this->counts = get_transient( $this->counts_cache_name );
			
			if( false === $this->counts ) {
				$this->counts = array();
			} else {
				$this->update_existing_counts_cache = true;
			}

		}

		public function update_counts_cache() {

			if( true === $this->update_counts_cache && ! empty( $this->counts_cache_name ) && ! empty( $this->counts ) ) {

				if( ! empty( $lifespan = intval( get_option( 'awf_counts_cache_days', '10' ) ) ) ) {

					if( $this->update_existing_counts_cache ) {
						$expiration = intval( get_transient( 'timeout_' . $this->counts_cache_name ) ) - time();
					} else {
						$expiration = DAY_IN_SECONDS * $lifespan;
					}

					if( $expiration <= 0 ) { $expiration = 1; }

					set_transient( $this->counts_cache_name, $this->counts, $expiration );
				}
				
				$cleanup_transients = get_transient( 'awf_counts_cleanup' );

				if( false === $cleanup_transients ) {
					$this->clear_expired_counts_cache();
				}
			}
		}

		public function clear_expired_counts_cache() {

      global $wpdb;

      $transient_name = '_transient_timeout_awf_counts_%';
			$now = time();

			$sql = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s' AND option_value < $now LIMIT 5000";
			$transients = $wpdb->get_col( $wpdb->prepare( $sql, $transient_name ) );

			$counter =  0;

      while( $transients && ++$counter < 5 ) {

				foreach ( $transients as $transient ) {
					delete_transient( str_replace( '_transient_timeout_', '', $transient ) );
				}

				$transients = $wpdb->get_col( $wpdb->prepare( $sql, $transient_name ) );

      }

			if( $counter < 5 ) {
				set_transient( 'awf_counts_cleanup', '1', DAY_IN_SECONDS * 3 );
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