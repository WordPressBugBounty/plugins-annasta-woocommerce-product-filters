<?php

defined( 'ABSPATH' ) or die( 'Access denied' );
if ( !class_exists( 'A_W_F' ) ) {
    class A_W_F {
        /** Current running instance of A_W_F object
         *
         * @since 1.0.0
         * @var A_W_F object
         */
        private static $instance;

        /** Current running instance of annasta admin
         *
         * @since 1.0.0
         * @var mixed
         */
        public static $admin = null;

        /** Current running instance of annasta frontend
         *
         * @since 1.0.0
         * @var A_W_F_frontend|A_W_F_premium_frontend object
         */
        public static $front = null;

        /** Premium version check
         *
         * @since 1.2.5
         * @var boolean
         */
        public static $premium = false;

        /** Wc products taxonomy names that aren't allowed to become a filter taxonomy
         *
         * @since 1.0.0
         * @var array
         */
        public static $excluded_taxonomies = array('product_type', 'product_visibility', 'product_shipping_class');

        /** Master array of presets
         *
         * @since 1.0.0
         * @var array
         */
        public static $presets = array();

        /** An array of ids for all the available modules
         *
         * @since 1.0.0
         * @var array
         */
        public static $modules = array(
            'taxonomy',
            'search',
            'price',
            'stock',
            'featured',
            'rating',
            'onsale',
            'ppp',
            'orderby',
            'meta'
        );

        /** Callers id count for the current session
         *
         * @since 1.0.0
         * @var int
         */
        public static $caller_id = 1;

        /** Current theme compatibility support class, if exists, or false
         *
         * @since 1.2.6
         * @var theme compatibility support class or boolean false when no support class exists
         */
        public static $theme_support = false;

        /** Caching avoidance workaround
         *
         * @since 1.0.0
         * @var string
         */
        public static $plugin_version;

        /**
         *
         * Preview mode indicator
         *
         * @since 1.5.3
         * @var boolean
         */
        public static $preview_mode = false;

        /**
         *
         * Preview mode set by a REST preview route, reset after the route callback
         *
         * @since 1.8.5
         * @var boolean
         */
        private static $rest_preview_mode = false;

        private function __construct() {
            self::$plugin_version = A_W_F_VERSION;
            if ( !class_exists( 'WooCommerce' ) ) {
                if ( is_admin() ) {
                    add_action( 'load-plugins.php', array($this, 'add_wc_absence_warning') );
                }
                return;
            }
            spl_autoload_register( array($this, 'awf_autoloader') );
            if ( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ) {
                new A_W_F_gutenberg();
            }
            new A_W_F_nav_menus();
            new A_W_F_terms_blocks();
            add_action( 'init', array($this, 'load_textdomain') );
            add_action( 'widgets_init', array($this, 'register_widget') );
            add_action( 'init', array($this, 'register_shortcode') );
            add_action( 'customize_register', array($this, 'register_customizer') );
            add_filter( 'rewrite_rules_array', array($this, 'add_rewrite_rules'), 10 );
            add_action(
                'update_option_awf_query_vars',
                array($this, 'flush_rewrite_rules_on_labels_change'),
                10,
                2
            );
            add_action( 'add_option_awf_ss_engine', array($this, 'clear_counts_cache_on_search_change') );
            add_action( 'update_option_awf_ss_engine', array($this, 'clear_counts_cache_on_search_change') );
            add_action( 'add_option_awf_ss_sources', array($this, 'clear_counts_cache_on_search_change') );
            add_action( 'update_option_awf_ss_sources', array($this, 'clear_counts_cache_on_search_change') );
            add_filter(
                'rest_request_before_callbacks',
                array($this, 'set_rest_preview_mode'),
                10,
                3
            );
            add_filter(
                'rest_request_after_callbacks',
                array($this, 'unset_rest_preview_mode'),
                10,
                3
            );
            add_action( 'awf_cron_cache_reset', array($this, 'cron_cache_reset') );
            add_action( 'awf_counts_cache_cleanup', array('A_W_F', 'cleanup_counts_cache') );
            if ( 'none' !== get_option( 'awf_cache_reset_mode', 'default' ) ) {
                add_action( 'before_delete_post', array($this, 'on_product_deletion') );
                add_action( 'wp_trash_post', array($this, 'on_product_trashing') );
                add_action( 'untrashed_post', array($this, 'on_product_untrashing') );
                add_action( 'woocommerce_update_product', array($this, 'on_product_update') );
                add_action( 'woocommerce_product_set_stock_status', array($this, 'on_stock_status_change') );
                add_action( 'woocommerce_variation_set_stock_status', array($this, 'on_stock_status_change') );
                add_action( 'created_product_cat', array($this, 'on_product_cat_change') );
                add_action( 'delete_product_cat', array($this, 'on_product_cat_change') );
            }
            self::$presets = get_option( 'awf_presets', array() );
            if ( !is_array( self::$presets ) ) {
                self::$presets = array();
            }
            if ( is_admin() ) {
                if ( wp_doing_ajax() && isset( $_REQUEST['awf_front'] ) ) {
                    add_action( 'wp_ajax_awf', array($this, 'frontend_ajax_controller') );
                    add_action( 'wp_ajax_nopriv_awf', array($this, 'frontend_ajax_controller') );
                    add_action( 'wp_ajax_awf_get_nonce', array($this, 'frontend_nonce_controller') );
                    add_action( 'wp_ajax_nopriv_awf_get_nonce', array($this, 'frontend_nonce_controller') );
                } else {
                    $this->initialize_admin();
                }
            } else {
                $this->initialize_frontend();
            }
            if ( 'yes' === get_option( 'awf_theme_support', 'yes' ) ) {
                $theme_template = sanitize_title( strtolower( get_template() ) );
                $theme_support_file = A_W_F_PLUGIN_PATH . 'code/themes-support/' . $theme_template . '.php';
                if ( file_exists( $theme_support_file ) ) {
                    include_once $theme_support_file;
                    $theme_class_name = 'A_W_F_' . $theme_template . '_theme_support';
                    if ( class_exists( $theme_class_name ) ) {
                        self::$theme_support = new $theme_class_name();
                    }
                }
            }
            if ( 'yes' === get_option( 'awf_compatibility_support', 'yes' ) ) {
                include_once A_W_F_PLUGIN_PATH . 'code/compatibility-support.php';
            }
        }

        private function initialize_admin() {
            self::$admin = A_W_F_admin::get_instance();
        }

        public function initialize_frontend() {
            self::$front = A_W_F_frontend::get_instance();
        }

        /* Block preview (ServerSideRender) in the post, widgets and Customizer editors */
        public function set_rest_preview_mode( $response, $handler, $request ) {
            if ( preg_match( '#^/wp/v2/block-renderer/a-w-f/awf-block/?$#i', $request->get_route() ) ) {
                self::$preview_mode = self::$rest_preview_mode = true;
            }
            return $response;
        }

        /* Batch requests run several routes in one process */
        public function unset_rest_preview_mode( $response, $handler, $request ) {
            if ( self::$rest_preview_mode ) {
                self::$preview_mode = self::$rest_preview_mode = false;
            }
            return $response;
        }

        /* wp_is_serving_rest_request() since WP 6.5 */
        public static function is_rest_request() {
            if ( function_exists( 'wp_is_serving_rest_request' ) ) {
                return wp_is_serving_rest_request();
            }
            return defined( 'REST_REQUEST' ) && REST_REQUEST;
        }

        public static function get_instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Plugin activation
         *
         * Bulk activation (Plugins screen) sends the bulk actions nonce and no plugin parameter. The rewrite rules
         * are deleted, so that WordPress builds them with the plugin's rules on the next request.
         */
        public static function activate_plugin() {
            if ( !current_user_can( 'activate_plugins' ) ) {
                return;
            }
            $plugin = ( isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '' );
            if ( '' === $plugin && isset( $_REQUEST['action'] ) && 'activate-selected' === $_REQUEST['action'] ) {
                check_admin_referer( 'bulk-plugins' );
            } else {
                check_admin_referer( "activate-plugin_{$plugin}" );
            }
            delete_option( 'rewrite_rules' );
            if ( empty( get_option( 'awf_presets' ) ) ) {
                self::$presets = array(
                    1 => array(
                        'position'     => 1,
                        'filters'      => array(),
                        'associations' => array(
                            0 => 'all',
                        ),
                        'overrides'    => array(),
                    ),
                );
                update_option( 'awf_presets', self::$presets );
                update_option( 'awf_preset_1_name', __( 'Default Preset', 'annasta-filters' ) );
                update_option( 'awf_preset_1_type', 'ajax' );
                update_option( 'awf_preset_1_display_mode', 'togglable' );
                update_option( 'awf_preset_1_togglable_mode', 'left-popup-sidebar' );
                $customizer_options = get_option( 'awf_customizer_options', array() );
                $customizer_options['awf_filter_title_collapse_btn_icon'] = 'f068';
                $customizer_options['awf_filters_button_fixed_position'] = 'yes';
                $customizer_options['awf_filters_button_fixed_top'] = $customizer_options['awf_filters_button_fixed_left'] = '';
                $customizer_options['awf_filters_button_fixed_right'] = intval( 200 );
                $customizer_options['awf_filters_button_fixed_bottom'] = intval( 100 );
                $customizer_options['awf_filters_button_padding_right'] = $customizer_options['awf_filters_button_padding_left'] = intval( 20 );
                $customizer_options['awf_filters_button_line_height'] = '45px';
                $customizer_options['awf_filters_button_background_color'] = '#eb2222';
                $customizer_options['awf_filters_button_hover_background_color'] = '#0073aa';
                $customizer_options['awf_filters_button_border_top_style'] = 'none';
                $customizer_options['awf_filters_button_border_right_style'] = 'none';
                $customizer_options['awf_filters_button_border_bottom_style'] = 'none';
                $customizer_options['awf_filters_button_border_left_style'] = 'none';
                $customizer_options['awf_filters_button_color'] = 'rgba(255,255,255,0.9)';
                $customizer_options['awf_filters_button_font_size'] = '15';
                $customizer_options['awf_filters_button_text_transform'] = 'uppercase';
                $customizer_options['awf_filters_button_white_space_nowrap'] = 'yes';
                $customizer_options['awf_filters_button_icon'] = 'f085';
                $customizer_options['awf_filters_button_icon_size'] = '1.25';
                $customizer_options['awf_filters_button_icon_padding_right'] = '15';
                $customizer_options['awf_filters_button_icon_border_right_color'] = 'rgba(255,255,255,0.9)';
                $customizer_options['awf_filters_button_icon_border_right_width'] = '1';
                $customizer_options['awf_filters_button_icon_border_right_style'] = 'solid';
                $customizer_options['awf_popup_sidebar_close_btn_font_size'] = '15';
                $customizer_options['awf_popup_sidebar_close_btn_icon_size'] = '1.25';
                $customizer_options['awf_popup_sidebar_close_btn_padding_top'] = '10';
                $customizer_options['awf_popup_sidebar_close_btn_padding_right'] = '20';
                $customizer_options['awf_popup_sidebar_close_btn_padding_bottom'] = '10';
                $customizer_options['awf_popup_sidebar_close_btn_padding_left'] = '15';
                $customizer_options['awf_popup_sidebar_close_btn_margin_top'] = '-20';
                $customizer_options['awf_popup_sidebar_close_btn_margin_right'] = '-20';
                $customizer_options['awf_popup_sidebar_close_btn_margin_left'] = '-20';
                $customizer_options['awf_popup_sidebar_close_btn_margin_bottom'] = '35';
                $customizer_options['awf_popup_sidebar_close_btn_text_align'] = 'right';
                $customizer_options['awf_loader_style'] = intval( 3 );
                $customizer_options['awf_loader_color'] = '#eb2222';
                $customizer_options['awf_loader_size'] = intval( 125 );
                $customizer_options['awf_loader_speed'] = 'slow';
                $customizer_options['awf_overlay_opacity'] = '0.8';
                update_option( 'awf_customizer_options', $customizer_options );
                require 'class-a-w-f-admin.php';
                require 'class-a-w-f-filter.php';
                self::$admin = A_W_F_admin::get_instance();
                $search_filter = self::$admin->add_filter( intval( 1 ), 'search' );
                $search_filter->settings['active_prefix'] = __( 'Search', 'annasta-filters' ) . ':';
                $search_filter->settings['autocomplete'] = true;
                $search_filter->settings['type_options']['ac_display_product_cat'] = true;
                $search_filter->settings['type_options']['ac_product_cat_header'] = mb_strtoupper( __( 'Categories', 'annasta-filters' ) );
                $search_filter->settings['type_options']['ac_products_header'] = mb_strtoupper( __( 'Products', 'annasta-filters' ) );
                $search_filter->settings['type_options']['autocomplete_show_img'] = true;
                $search_filter->settings['type_options']['autocomplete_show_price'] = true;
                $search_filter->settings['type_options']['autocomplete_view_all'] = true;
                $search_filter->settings['type_options']['autocomplete_after'] = 2;
                $search_filter->settings['type_options']['autocomplete_results_count'] = 5;
                update_option( $search_filter->prefix . 'settings', $search_filter->settings );
                if ( false !== get_taxonomy( 'product_cat' ) ) {
                    $pc_filter = self::$admin->add_filter( intval( 1 ), 'taxonomy--product_cat' );
                    $pc_filter->settings['button_submission'] = true;
                    $pc_filter->settings['is_collapsible'] = true;
                    $pc_filter->settings['show_count'] = true;
                    $pc_filter->settings['style_options']['icons'] = array(
                        '',
                        '',
                        '',
                        ''
                    );
                    $pc_filter->settings['style_options']['solid'] = array(
                        '',
                        '',
                        'awf-solid',
                        ''
                    );
                    $pc_filter->settings['style_options']['submit_button_label'] = __( 'Filter', 'annasta-filters' );
                    if ( false !== ($uncat = get_term_by( 'slug', 'uncategorized', 'product_cat' )) ) {
                        $pc_filter->settings['excluded_items'] = array($uncat->term_id);
                    }
                    update_option( $pc_filter->prefix . 'settings', $pc_filter->settings );
                }
                $price_filter = self::$admin->add_filter( intval( 1 ), 'price' );
                $price_filter->settings['active_prefix'] = __( 'Prices', 'annasta-filters' ) . ':';
                update_option( $price_filter->prefix . 'settings', $price_filter->settings );
            }
        }

        public static function deactivate_plugin() {
            if ( !current_user_can( 'activate_plugins' ) ) {
                return;
            }
            require 'class-a-w-f-admin.php';
            self::clear_awf_cache( true );
            wp_clear_scheduled_hook( 'awf_counts_cache_cleanup' );
            if ( !is_null( self::$instance ) ) {
                remove_filter( 'rewrite_rules_array', array(self::$instance, 'add_rewrite_rules'), 10 );
            }
            flush_rewrite_rules( false );
        }

        public static function uninstall_plugin() {
            if ( !current_user_can( 'activate_plugins' ) ) {
                return;
            }
            self::clear_awf_cache( true );
            wp_clear_scheduled_hook( 'awf_counts_cache_cleanup' );
            unregister_widget( 'A_W_F_widget' );
            $all_options = wp_load_alloptions();
            $awf_options = array(
                'awf_version',
                'awf_premium',
                'awf_query_vars',
                'awf_seo_filters_settings',
                'awf_page_title',
                'awf_shop_title',
                'awf_default_page_title',
                'awf_default_shop_title',
                'awf_add_seo_meta_description',
                'awf_seo_meta_description',
                'awf_seo_filters_title_prefix',
                'awf_seo_filters_title_postfix',
                'awf_seo_filters_separator',
                'awf_seo_filter_values_separator',
                'awf_shortcode_pages',
                'awf_shop_title_badges',
                'awf_remove_wc_orderby',
                'awf_ajax_scroll_on',
                'awf_ajax_scroll_adjustment',
                'awf_force_products_display_on',
                'awf_shop_columns',
                'awf_redirect_search',
                'awf_products_html_wrapper',
                'awf_pretty_scrollbars',
                'awf_color_filter_style',
                'awf_image_filter_style',
                'awf_user_css',
                'awf_ppp_default',
                'awf_include_children_on',
                'awf_cat_views_on',
                'awf_presets',
                'awf_custom_style',
                'awf_range_slider_style',
                'awf_style_options_file',
                'awf_badges_exceptions',
                'awf_reset_all_exceptions',
                'awf_badge_reset_label',
                'awf_redirect_archives',
                'awf_user_js',
                'awf_ajax_pagination',
                'awf_theme_support',
                'awf_daterangepicker_enabled',
                'awf_customizer_options',
                'awf_default_font',
                'awf_default_font_enqueue',
                'awf_fontawesome_font_enqueue',
                'awf_display_wc_shop_title',
                'awf_display_wc_orderby',
                'awf_remove_wc_shop_title',
                'awf_product_list_template_options',
                'awf_force_wrapper_reload',
                'awf_hierarchical_archive_permalinks',
                'awf_variations_stock_support',
                'awf_toggle_btn_label',
                'awf_include_parents_in_associations',
                'awf_ppp_limit',
                'awf_hide_empty_filters',
                'awf_dynamic_price_ranges',
                'awf_counts_cache_days',
                'awf_breadcrumbs_support',
                'awf_templates',
                'awf_get_parameters_support',
                'awf_compatibility_support',
                'awf_popup_close_btn_label',
                'awf_archive_components_support',
                'awf_ajax_mode',
                'awf_custom_selectors',
                'awf_toggle_btn_position_before',
                'awf_global_wrapper',
                'awf_use_wc_orderby',
                'awf_popup_fix_close_btn',
                'awf_ss_engine',
                'awf_ss_sources',
                'awf_multilingual_support',
                'awf_excluded_customizer_sections',
                'awf_force_published_status',
                'awf_cm_v2',
                'awf_rewrite_rules',
                'awf_rewritten_pages',
                'awf_add_canonical',
                'awf_add_noindex_filtered',
                'awf_add_noindex_no_results',
                'awf_add_nofollow',
                'awf_canonical_parameters',
                'awf_cache_reset_mode',
                'awf_enable_cron_cache_reset',
                'awf_cache_reset_counts_rebuild',
                'awf_style_options_css',
                'awf_counts_cache_version'
            );
            foreach ( $all_options as $name => $value ) {
                if ( 0 !== strpos( $name, 'awf_' ) ) {
                    continue;
                }
                if ( stristr( $name, 'awf_preset_' ) || stristr( $name, 'awf_filter_' ) || substr( $name, -12 ) === '_pretty_name' || in_array( $name, $awf_options ) ) {
                    delete_option( $name );
                }
            }
            flush_rewrite_rules();
        }

        public function add_wc_absence_warning() {
            add_action( 'admin_notices', array($this, 'display_wc_absence_warning') );
        }

        public function display_wc_absence_warning() {
            echo '<div class="notice notice-warning is-dismissible"><p>', esc_html__( 'annasta Filters for WooCommerce requires WooCommerce plugin to function properly. Please install WooCommerce online shop to use the annasta Filters for WooCommerce plugin.', 'annasta-filters' ), '</p></div>';
        }

        public function load_textdomain() {
            load_plugin_textdomain( 'annasta-filters', false, plugin_basename( dirname( A_W_F_PLUGIN_FILE ) ) . '/languages' );
        }

        public function frontend_ajax_controller() {
            if ( !check_ajax_referer( 'awf_nonce', 'awf_nonce', false ) ) {
                wp_die( -1 );
            }
            if ( !empty( $_GET['awf_action'] ) ) {
                $this->initialize_frontend();
                self::$front->ajax_controller();
            } else {
                die;
            }
        }

        public function frontend_nonce_controller() {
            wp_die( wp_create_nonce( 'awf_nonce' ) );
        }

        public function register_widget() {
            register_widget( 'A_W_F_widget' );
        }

        public function register_shortcode() {
            add_shortcode( 'annasta_filters', array($this, 'filters_shortcode') );
        }

        public function filters_shortcode( $atts ) {
            if ( !empty( self::$front ) && !empty( self::$presets ) ) {
                $caller_id = 'shortcode-' . self::$caller_id++;
                $filtered_atts = shortcode_atts( array(
                    'shortcode_id' => $caller_id,
                    'preset_id'    => '',
                    'preset_name'  => '',
                ), $atts );
                if ( !empty( $filtered_atts['preset_name'] ) ) {
                    foreach ( self::$presets as $id => $data ) {
                        $name = get_option( 'awf_preset_' . $id . '_name', '' );
                        if ( $name === $filtered_atts['preset_name'] ) {
                            $filtered_atts['preset_id'] = $id;
                            break;
                        }
                    }
                }
                $filtered_atts['preset_id'] = (int) $filtered_atts['preset_id'];
                if ( isset( self::$presets[$filtered_atts['preset_id']] ) ) {
                    return self::$front->display_shortcode( $filtered_atts );
                }
            }
            return '';
        }

        public function register_customizer( $wp_customizer ) {
            new A_W_F_customizer($wp_customizer);
        }

        /** Get the default font of the current annasta Filters style
         * 
         * @since 1.3.0
         * 
         * Use only the Google fonts from the list returned by the A_W_F_customizer::get_google_fonts_choices()
         * @return (string) Google font name, has to be url-encoded (spaces changed to +)
         */
        public static function get_awf_custom_style_default_font() {
            switch ( get_option( 'awf_custom_style', 'none' ) ) {
                default:
                    return 'inherit';
                    break;
            }
        }

        public static function build_query_vars() {
            $vars = array(
                'tax'   => array(),
                'awf'   => array(
                    'orderby'  => 'orderby',
                    'search'   => 's-filter',
                    'ppp'      => 'ppp',
                    'stock'    => 'availability',
                    'featured' => 'featured',
                    'onsale'   => 'onsale',
                ),
                'meta'  => array(),
                'range' => array(
                    'min_price'  => 'price-min',
                    'max_price'  => 'price-max',
                    'min_rating' => 'rating-min',
                    'max_rating' => 'rating-max',
                ),
                'misc'  => array(
                    'archive' => get_option( 'awf_archive_identifier_pretty_name', 'archive-filter' ),
                ),
            );
            $daterangepicker_enabled = false;
            $taxonomies = get_object_taxonomies( 'product', 'objects' );
            /* Polylang fix */
            if ( function_exists( 'pll_default_language' ) ) {
                A_W_F::$excluded_taxonomies[] = 'lang';
                A_W_F::$excluded_taxonomies[] = 'language';
            }
            foreach ( $taxonomies as $t ) {
                if ( in_array( $t->name, A_W_F::$excluded_taxonomies ) ) {
                    continue;
                }
                $decoded_taxonomy = urldecode( sanitize_title( $t->name ) );
                if ( empty( $t->query_var ) ) {
                    $taxonomy_var_name = $decoded_taxonomy;
                } else {
                    $taxonomy_var_name = urldecode( sanitize_title_with_dashes( $t->query_var ) );
                }
                if ( !isset( $vars['tax'][$t->name] ) ) {
                    if ( $pretty_name = get_option( 'awf_' . $t->name . '_pretty_name', false ) ) {
                        $vars['tax'][$decoded_taxonomy] = urldecode( sanitize_title( $pretty_name ) );
                    } else {
                        $vars['tax'][$decoded_taxonomy] = $taxonomy_var_name . '-filter';
                    }
                }
            }
            foreach ( $vars['awf'] as $var => $name ) {
                $pretty_name = get_option( 'awf_' . $var . '_awf_module_pretty_name', '' );
                if ( !empty( $pretty_name ) ) {
                    $vars['awf'][$var] = urldecode( sanitize_title_with_dashes( $pretty_name ) );
                }
            }
            foreach ( A_W_F::$presets as $preset_id => $preset ) {
                foreach ( $preset['filters'] as $filter_id => $position ) {
                    $filter = new A_W_F_filter($preset_id, $filter_id);
                    if ( 'meta' === $filter->module ) {
                        if ( empty( $filter->settings['meta_name'] ) ) {
                            continue;
                        }
                        if ( 'date' === $filter->settings['type'] ) {
                            $daterangepicker_enabled = true;
                            $db_date_format = ( isset( $filter->settings['style_options']['db_date_format'] ) ? $filter->settings['style_options']['db_date_format'] : 'c' );
                            $meta_query_var = 'awf_date_filter_' . $db_date_format . '_' . $filter->settings['meta_name'];
                            $pretty_name = get_option( $meta_query_var . '_awf_meta_pretty_name', '' );
                            if ( empty( $pretty_name ) ) {
                                $vars['meta'][$meta_query_var] = $filter->settings['meta_name'] . '-' . $db_date_format . '-date-filter';
                            } else {
                                $vars['meta'][$meta_query_var] = $pretty_name;
                            }
                        } else {
                            $pretty_name = get_option( 'awf_' . $filter->settings['meta_name'] . '_awf_meta_pretty_name', '' );
                            if ( empty( $pretty_name ) ) {
                                $meta_parameter = $filter->settings['meta_name'] . '-filter';
                            } else {
                                $meta_parameter = $pretty_name;
                            }
                            $vars['meta'][$filter->settings['meta_name']] = $meta_parameter;
                            if ( 'range' === $filter->settings['type'] ) {
                                $vars['range']['min_' . $filter->settings['meta_name']] = $meta_parameter . '-min';
                                $vars['range']['max_' . $filter->settings['meta_name']] = $meta_parameter . '-max';
                            }
                        }
                    } elseif ( 'taxonomy' === $filter->module && isset( $vars['tax'][$filter->settings['taxonomy']] ) ) {
                        if ( 'range' === $filter->settings['type'] && 'taxonomy_range' !== $filter->settings['type_options']['range_type'] ) {
                            $vars['range']['min_' . $filter->settings['taxonomy']] = $vars['tax'][$filter->settings['taxonomy']] . '-min';
                            $vars['range']['max_' . $filter->settings['taxonomy']] = $vars['tax'][$filter->settings['taxonomy']] . '-max';
                        }
                    }
                }
            }
            update_option( 'awf_query_vars', $vars );
            update_option( 'awf_daterangepicker_enabled', $daterangepicker_enabled );
        }

        public static function get_db_date_formats() {
            $db_date_formats = array(
                'a' => array(
                    'format' => 'U',
                    'label'  => __( 'Unix Time Stamp', 'annasta-filters' ),
                ),
                'b' => array(
                    'format' => 'Ymd',
                    'label'  => __( 'YYYYMMDD', 'annasta-filters' ),
                ),
                'c' => array(
                    'format' => 'Y/m/d',
                    'label'  => __( 'YYYY/MM/DD', 'annasta-filters' ),
                ),
                'd' => array(
                    'format' => 'Y-m-d',
                    'label'  => __( 'YYYY-MM-DD', 'annasta-filters' ),
                ),
                'e' => array(
                    'format' => 'Y/m/d H:i:s',
                    'label'  => __( 'YYYY/MM/DD HH:MI:SS', 'annasta-filters' ),
                ),
                'f' => array(
                    'format' => 'Y-m-d H:i:s',
                    'label'  => __( 'YYYY-MM-DD HH:MI:SS', 'annasta-filters' ),
                ),
            );
            return $db_date_formats;
        }

        public static function get_seo_meta_description( $query ) {
            $description = stripcslashes( get_option( 'awf_seo_meta_description', '' ) );
            $parts = self::get_seo_parts( $query );
            $filters = '';
            if ( isset( $parts['empty_filters'] ) ) {
                $filters .= implode( ' ', $parts['empty_filters'] ) . ' ';
            } else {
                $parts['empty_filters'] = '';
            }
            $filters .= implode( get_option( 'awf_seo_filters_separator', ' - ' ), $parts['filters'] ) . ' ';
            if ( isset( $parts['disabled'] ) ) {
                $filters .= implode( get_option( 'awf_seo_filters_separator', ' - ' ), $parts['disabled'] );
            }
            $filters = str_replace( " ", ' ', $filters );
            $filters = str_replace( '  ', ' ', trim( $filters ) );
            if ( empty( str_replace( $parts['empty_filters'], '', $filters ) ) ) {
                return self::get_seo_title( $query, 'shop' );
            }
            $description = str_replace( '{annasta_filters}', $filters, $description );
            return $description;
        }

        public static function get_seo_title( $query, $type = 'page' ) {
            $parts = self::get_seo_parts( $query );
            $title = '';
            if ( empty( $parts['filters'] ) ) {
                $title = get_option( 'awf_default_' . $type . '_title', _x( 'Shop', 'Default page title', 'annasta-filters' ) );
            } else {
                $title = get_option( 'awf_seo_filters_title_prefix', __( 'Shop for ', 'annasta-filters' ) ) . ' ';
                if ( isset( $parts['empty_filters'] ) ) {
                    $title .= implode( ' ', $parts['empty_filters'] ) . ' ';
                }
                $title .= implode( get_option( 'awf_seo_filters_separator', ' - ' ), $parts['filters'] ) . ' ';
                $title .= get_option( 'awf_seo_filters_title_postfix', '' );
            }
            $title = str_replace( " ", ' ', $title );
            $title = str_replace( '  ', ' ', trim( $title ) );
            return $title;
        }

        private static function get_seo_parts( $query ) {
            $get_all = ( 'yes' === get_option( 'awf_add_seo_meta_description', 'no' ) ? true : false );
            $seo_parts = array(
                'filters' => array(),
            );
            if ( !empty( self::$front ) ) {
                if ( empty( self::$front->seo_parts ) ) {
                    self::$front->seo_parts = array(
                        'filters' => array(),
                    );
                    $seo_parts =& self::$front->seo_parts;
                } else {
                    return self::$front->seo_parts;
                }
            }
            $filters_settings = get_option( 'awf_seo_filters_settings', array() );
            foreach ( $filters_settings as $filter => $settings ) {
                $part_name = 'filters';
                if ( empty( $settings['enabled'] ) ) {
                    if ( $get_all ) {
                        $part_name = 'disabled';
                    } else {
                        continue;
                    }
                }
                if ( 0 === strpos( $filter, 'taxonomy_' ) ) {
                    $taxonomy = substr( $filter, strlen( 'taxonomy_' ) );
                    if ( isset( $query->range['min_' . $taxonomy] ) && isset( $query->range['max_' . $taxonomy] ) ) {
                        $seo_parts[$part_name][] = $settings['prefix'] . $query->range['min_' . $taxonomy] . $settings['range_separator'] . $query->range['max_' . $taxonomy] . $settings['postfix'];
                    } elseif ( !empty( $query->tax[$taxonomy] ) ) {
                        $names = array();
                        foreach ( $query->tax[$taxonomy] as $slug ) {
                            $term = get_term_by( 'slug', $slug, $taxonomy );
                            if ( !empty( $term ) ) {
                                $names[] = $term->name;
                            }
                        }
                        $seo_parts[$part_name][] = $settings['prefix'] . implode( get_option( 'awf_seo_filter_values_separator', ', ' ), $names ) . $settings['postfix'];
                    } elseif ( !empty( $settings['empty'] ) ) {
                        $seo_parts['empty_filters'][] = $settings['empty'];
                    }
                } elseif ( 0 === strpos( $filter, 'meta_filter_' ) ) {
                    $meta_name = substr( $filter, strlen( 'meta_filter_' ) );
                    if ( isset( $query->meta[$meta_name] ) ) {
                        if ( 2 === count( $query->meta[$meta_name] ) ) {
                            if ( 0 === strpos( $meta_name, 'awf_date_filter' ) ) {
                                $seo_parts[$part_name][] = $settings['prefix'] . implode( ' - ', array(wp_date( get_option( 'date_format' ), intval( $query->meta[$meta_name][0] ) ), wp_date( get_option( 'date_format' ), intval( $query->meta[$meta_name][1] ) )) ) . $settings['postfix'];
                            }
                        } else {
                            if ( 0 === strpos( $meta_name, 'awf_date_filter' ) ) {
                                $seo_parts[$part_name][] = $settings['prefix'] . wp_date( get_option( 'date_format' ), intval( $query->meta[$meta_name][0] ) ) . $settings['postfix'];
                            } else {
                                $seo_parts[$part_name][] = $settings['prefix'] . implode( get_option( 'awf_seo_filter_values_separator', ', ' ), $query->meta[$meta_name] ) . $settings['postfix'];
                            }
                        }
                    } elseif ( isset( $query->range['min_' . $meta_name] ) && isset( $query->range['max_' . $meta_name] ) ) {
                        $seo_parts[$part_name][] = $settings['prefix'] . $query->range['min_' . $meta_name] . $settings['range_separator'] . $query->range['max_' . $meta_name] . $settings['postfix'];
                    } elseif ( !empty( $settings['empty'] ) ) {
                        $seo_parts['empty_filters'][] = $settings['empty'];
                    }
                } else {
                    if ( !empty( $query->awf[$filter] ) ) {
                        if ( 'search' === $filter ) {
                            $seo_parts[$part_name][] = str_replace( array('\\"', "\\'"), array('"', "'"), $settings['prefix'] . $query->awf[$filter] . $settings['postfix'] );
                        } elseif ( isset( $settings['labels'] ) && isset( $settings['labels'][$query->awf[$filter]] ) ) {
                            $seo_parts[$part_name][] = $settings['prefix'] . $settings['labels'][$query->awf[$filter]] . $settings['postfix'];
                        }
                    } elseif ( isset( $query->range['min_' . $filter] ) && isset( $query->range['max_' . $filter] ) ) {
                        $min = $query->range['min_' . $filter];
                        $max = $query->range['max_' . $filter];
                        if ( 'price' === $filter ) {
                            $min = wp_strip_all_tags( wc_price( $min ) );
                            $max = wp_strip_all_tags( wc_price( $max ) );
                        }
                        $seo_parts[$part_name][] = $settings['prefix'] . $min . $settings['range_separator'] . $max . $settings['postfix'];
                    } else {
                        if ( !empty( $settings['empty'] ) ) {
                            $seo_parts['empty_filters'][] = $settings['empty'];
                        }
                    }
                }
            }
            return apply_filters( 'awf_seo_filters', $seo_parts );
        }

        /**
         * Filters rewrite rules, inserted before the matching WordPress rules
         *
         * The rule bases are taken from WordPress' own rules, exactly as registered: the shop archive (full shop page path,
         * front, decoded slugs), the term archives (taxonomies without a query var are queried as taxonomy=name&term=slug),
         * and the full page paths of the shortcode pages (child pages, decoded like the shop base). WPML: WCML translates only
         * WooCommerce's own shop rules (shop/, feed, page), so the shop rules are added for the shop page path of every language.
         * The pages that got rules are saved in awf_rewritten_pages: the frontend only rewrites their links.
         *
         * @param array $wp_rules
         * @return array
         */
        public function add_rewrite_rules( $wp_rules ) {
            $awf_rules = get_option( 'awf_rewrite_rules', array() );
            unset($awf_rules[0]);
            $awf_rules = array_filter( $awf_rules );
            $rewritten_pages = array();
            if ( 0 === count( $awf_rules ) ) {
                update_option( 'awf_rewritten_pages', $rewritten_pages );
                return $wp_rules;
            }
            global $wp_rewrite;
            $query_vars = (object) get_option( 'awf_query_vars', array(
                'tax'  => array(),
                'awf'  => array(),
                'meta' => array(),
            ) );
            $vars_by_page = array();
            foreach ( $awf_rules as $page => $parameters ) {
                $vars_by_page[$page] = array();
                foreach ( $parameters as $parameter ) {
                    if ( isset( $query_vars->tax[$parameter] ) ) {
                        $vars_by_page[$page][$parameter] = $query_vars->tax[$parameter];
                    }
                }
                if ( 0 === count( $vars_by_page[$page] ) ) {
                    unset($vars_by_page[$page]);
                }
            }
            foreach ( $vars_by_page as $page => $vars ) {
                $slugs = array_values( $vars );
                $is_page = 0 === strpos( $page, 'awf_shortcode_page-' );
                $rewrite = array(
                    'rewrite' => '',
                    'matches' => array(),
                );
                foreach ( $slugs as $var ) {
                    $rewrite['rewrite'] .= '(?:/' . $var . '-([^/]+))?';
                    $rewrite['matches'][] = array($var, false);
                }
                $paged = '(?:/' . $wp_rewrite->pagination_base . '/([0-9]{1,}))?';
                if ( !$is_page ) {
                    $rewrite['rewrite'] .= $paged;
                    $rewrite['matches'][] = array('paged', false);
                }
                $awf_rewrites = array();
                $position = 0;
                switch ( $page ) {
                    case 'shop':
                        $key = array_search( 'index.php?post_type=product&paged=$matches[1]', $wp_rules, true );
                        if ( false === $key ) {
                            break;
                        }
                        $position = (int) array_search( $key, array_keys( $wp_rules ), true );
                        $position++;
                        if ( 'page' === get_option( 'show_on_front' ) && intval( get_option( 'page_on_front' ) ) === wc_get_page_id( 'shop' ) ) {
                            $slugs = array_values( $vars );
                            switch ( count( $slugs ) ) {
                                case 1:
                                    $awf_rewrites['(' . implode( '|', $slugs ) . ')-([^/]+)' . $paged . '/?$'] = 'index.php?post_type=product&$matches[1]=$matches[2]&paged=$matches[3]';
                                    break;
                                case 2:
                                    $awf_rewrites['(' . implode( '|', $slugs ) . ')-([^/]+)' . $paged . '/?$'] = 'index.php?post_type=product&$matches[1]=$matches[2]&paged=$matches[3]';
                                    $awf_rewrites[$slugs[0] . '-([^/]+)/' . $slugs[1] . '-([^/]+)' . $paged . '/?$'] = 'index.php?post_type=product&' . $slugs[0] . '=$matches[1]&' . $slugs[1] . '=$matches[2]&paged=$matches[3]';
                                    break;
                                default:
                                    break;
                            }
                        } else {
                            $base = $this->get_rewrite_rule_base( $key, '/' . $wp_rewrite->pagination_base . '/([0-9]{1,})/?$' );
                            if ( false !== $base ) {
                                $bases = array($base);
                                if ( class_exists( 'SitePress' ) ) {
                                    $bases = array_unique( array_merge( $bases, $this->get_wpml_shop_page_paths() ) );
                                }
                                foreach ( $bases as $shop_base ) {
                                    $awf_rewrites[$shop_base . $rewrite['rewrite'] . '/?$'] = 'index.php?post_type=product&' . $this->build_matches( $rewrite['matches'] );
                                }
                            }
                        }
                        break;
                    default:
                        if ( $is_page ) {
                            $parts = explode( '-', $page );
                            if ( 2 === count( $parts ) ) {
                                $page_id = (int) array_pop( $parts );
                                $page_path = get_page_uri( $page_id );
                                if ( empty( $page_path ) ) {
                                    continue 2;
                                }
                                $permastruct = $wp_rewrite->get_page_permastruct();
                                $permastruct = trim( $permastruct, '/' );
                                $permastruct = str_replace( '%pagename%', urldecode( $page_path ), $permastruct );
                                $awf_rewrites[$permastruct . $rewrite['rewrite'] . '/?$'] = 'index.php?page_id=' . $page_id . '&' . $this->build_matches( $rewrite['matches'] );
                            }
                        } else {
                            $t = get_taxonomy( $page );
                            if ( $t && !empty( $t->rewrite['slug'] ) ) {
                                $tax_query = ( empty( $t->query_var ) ? 'taxonomy=' . $page . '&term=' : $t->query_var . '=' );
                                $key = array_search( 'index.php?' . $tax_query . '$matches[1]&paged=$matches[2]', $wp_rules, true );
                                if ( false === $key ) {
                                    break;
                                }
                                $base = $this->get_rewrite_rule_base( $key, '/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$' );
                                if ( false === $base ) {
                                    break;
                                }
                                $position = (int) array_search( $key, array_keys( $wp_rules ), true );
                                $awf_rewrites[$base . $rewrite['rewrite'] . '/?$'] = 'index.php?' . $tax_query . '$matches[1]&' . $this->build_matches( $rewrite['matches'], array(), 2 );
                            }
                        }
                        break;
                }
                if ( 0 < count( $awf_rewrites ) ) {
                    $wp_rules = array_merge( array_slice(
                        $wp_rules,
                        0,
                        $position,
                        true
                    ), $awf_rewrites, array_slice(
                        $wp_rules,
                        $position,
                        count( $wp_rules ),
                        true
                    ) );
                    $rewritten_pages[] = $page;
                }
            }
            update_option( 'awf_rewritten_pages', $rewritten_pages );
            return $wp_rules;
        }

        /**
         * Base of a WordPress pagination rule, e.g. "shop" from "shop/page/([0-9]{1,})/?$"
         *
         * @since 1.8.5
         * @param string $rule The WordPress rewrite rule regex.
         * @param string $pagination_pattern The pagination part of the regex.
         * @return string|false The base, or false if the rule doesn't end with the pagination pattern.
         */
        private function get_rewrite_rule_base( $rule, $pagination_pattern ) {
            $length = strlen( $pagination_pattern );
            if ( strlen( $rule ) <= $length || substr( $rule, -$length ) !== $pagination_pattern ) {
                return false;
            }
            return substr( $rule, 0, -$length );
        }

        /**
         * WPML: the shop page paths of all the active languages
         *
         * @since 1.8.5
         * @return array Decoded shop page paths, as in the WooCommerce shop archive rules.
         */
        private function get_wpml_shop_page_paths() {
            $paths = array();
            $shop_page_id = wc_get_page_id( 'shop' );
            $languages = apply_filters( 'wpml_active_languages', null, array(
                'skip_missing' => 0,
            ) );
            if ( 0 < $shop_page_id && is_array( $languages ) ) {
                foreach ( array_keys( $languages ) as $language ) {
                    $page_id = apply_filters(
                        'wpml_object_id',
                        $shop_page_id,
                        'page',
                        false,
                        $language
                    );
                    if ( $page_id && get_post( $page_id ) ) {
                        $paths[] = urldecode( get_page_uri( $page_id ) );
                    }
                }
            }
            return $paths;
        }

        /* $i: the index of the first match, for rules whose earlier matches are already mapped */
        private function build_matches( $matches, $add = array(), $i = 1 ) {
            $response = array();
            $matches = array_merge( $matches, $add );
            foreach ( $matches as $match ) {
                $k = ( empty( $match[0] ) ? '$matches[' . $i++ . ']' : $match[0] );
                $response[] = ( empty( $match[1] ) ? $k . '=$matches[' . $i++ . ']' : $k . '=' . $match[1] );
            }
            return implode( '&', $response );
        }

        /**
         * Rebuild the rewrite rules when the taxonomy parameters' labels change
         *
         * The rules are built from the labels, the links use the current ones.
         *
         * @since 1.8.5
         * @param mixed $old_value The previous awf_query_vars value.
         * @param mixed $value The new awf_query_vars value.
         */
        /**
         * The counts and search results caches of searched pages depend on the string search settings
         *
         * @since 1.8.5
         */
        public function clear_counts_cache_on_search_change() {
            self::clear_product_counts_cache();
        }

        public function flush_rewrite_rules_on_labels_change( $old_value, $value ) {
            $old_labels = ( isset( $old_value['tax'] ) ? $old_value['tax'] : array() );
            $new_labels = ( isset( $value['tax'] ) ? $value['tax'] : array() );
            if ( $old_labels === $new_labels ) {
                return;
            }
            $awf_rules = get_option( 'awf_rewrite_rules', array() );
            unset($awf_rules[0]);
            if ( array_filter( $awf_rules ) ) {
                flush_rewrite_rules( false );
            }
        }

        /**
         * Version of the filters cache, part of the counts and variations stock cache transient names
         *
         * @since 1.8.5
         * @return int
         */
        public static function get_cache_version() {
            return (int) get_option( 'awf_counts_cache_version', 1 );
        }

        /**
         * Invalidates the product counts cache
         *
         * Bumping the cache version invalidates every entry at once, also when the transients live in a persistent
         * object cache, where they can't be selected from the options table. The outdated rows of the options table
         * are removed in the background (see cleanup_counts_cache()).
         *
         * @param boolean $delete_now Delete all the counts transients of the options table right away (deactivation, uninstall)
         */
        public static function clear_product_counts_cache( $delete_now = false ) {
            update_option( 'awf_counts_cache_version', self::get_cache_version() + 1, true );
            if ( $delete_now ) {
                self::cleanup_counts_cache( true );
            } else {
                delete_transient( 'awf_counts_cleanup' );
                self::schedule_counts_cache_cleanup();
            }
        }

        /* Runs the cleanup in a WP Cron request instead of the current one */
        public static function schedule_counts_cache_cleanup( $delay = 0 ) {
            if ( !wp_next_scheduled( 'awf_counts_cache_cleanup' ) ) {
                wp_schedule_single_event( time() + $delay, 'awf_counts_cache_cleanup' );
            }
        }

        /**
         * Deletes the expired and outdated (previous cache versions) product counts transients from the options table
         *
         * Up to 5 batches of 5,000 per run; a larger cache continues in the next cron run.
         *
         * @since 1.8.5
         * @param boolean $all Delete all the counts transients
         */
        public static function cleanup_counts_cache( $all = false ) {
            global $wpdb;
            if ( $all ) {
                $sql = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 5000", $wpdb->esc_like( '_transient_awf_counts_' ) . '%' );
                $prefix = '_transient_';
            } else {
                $sql = $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND ( option_value < %d OR option_name NOT LIKE %s ) LIMIT 5000",
                    $wpdb->esc_like( '_transient_timeout_awf_counts_' ) . '%',
                    time(),
                    $wpdb->esc_like( '_transient_timeout_awf_counts_' . self::get_cache_version() . '_' ) . '%'
                );
                $prefix = '_transient_timeout_';
            }
            for ($i = 0; $i < 5; $i++) {
                $transients = $wpdb->get_col( $sql );
                if ( empty( $transients ) ) {
                    if ( !$all ) {
                        set_transient( 'awf_counts_cleanup', '1', DAY_IN_SECONDS * 3 );
                    }
                    return;
                }
                foreach ( $transients as $transient ) {
                    delete_transient( substr( $transient, strlen( $prefix ) ) );
                }
            }
            if ( !$all ) {
                self::schedule_counts_cache_cleanup( MINUTE_IN_SECONDS );
            }
        }

        /**
         * Clears the variations stock and the product counts caches
         *
         * The counts cache version is also part of the variations stock cache names.
         *
         * @param boolean $delete_now Delete the counts transients right away
         */
        public static function clear_awf_cache( $delete_now = false ) {
            global $wpdb;
            $transients_names = array('_transient_timeout_awf_vss_products_cache_%', '_transient_timeout_awf_vss_variations_cache_%', '_transient_timeout_awf_vss_variable_term_id');
            foreach ( $transients_names as $transient_name ) {
                $sql = "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '%s' LIMIT 5000";
                $transients = $wpdb->get_col( $wpdb->prepare( $sql, $transient_name ) );
                foreach ( $transients as $transient ) {
                    delete_transient( str_replace( '_transient_timeout_', '', $transient ) );
                }
            }
            delete_transient( 'awf_meta_keys' );
            self::clear_product_counts_cache( $delete_now );
        }

        /* Automatic cache resets (awf_cache_reset_mode) */
        protected function reset_cache_on_change( $counts_only = false ) {
            switch ( get_option( 'awf_cache_reset_mode', 'default' ) ) {
                case 'default':
                    if ( $counts_only ) {
                        self::clear_product_counts_cache();
                    } else {
                        self::clear_awf_cache();
                    }
                    break;
                case 'cron':
                    update_option( 'awf_enable_cron_cache_reset', '1' );
                    break;
                default:
                    break;
            }
        }

        /**
         * Product saves: cache reset in wp-admin, cron, WP-CLI and product management REST requests
         *
         * Checkout stock reductions save the products too: there only the stock status changes reset the cache
         * (on_stock_status_change()), to avoid a permanently cold cache.
         *
         * @param int $product_id
         */
        public function on_product_update( $product_id ) {
            if ( is_admin() || wp_doing_cron() || defined( 'WP_CLI' ) && WP_CLI || $this->is_product_management_rest_request() ) {
                $this->reset_cache_on_change();
            }
        }

        public function on_stock_status_change( $product_id ) {
            $this->reset_cache_on_change();
        }

        public function on_product_deletion( $product_id ) {
            if ( 'product' === get_post_type( $product_id ) && 'trash' !== get_post_status( $product_id ) ) {
                $this->reset_cache_on_change();
            }
        }

        public function on_product_trashing( $product_id ) {
            if ( 'product' === get_post_type( $product_id ) ) {
                $this->reset_cache_on_change();
            }
        }

        public function on_product_untrashing( $post_id ) {
            if ( 'product' === get_post_type( $post_id ) ) {
                $this->reset_cache_on_change();
            }
        }

        public function on_product_cat_change( $term_id ) {
            $this->reset_cache_on_change( true );
        }

        /* REST requests other than the Store API (cart and checkout requests of the block themes) */
        protected function is_product_management_rest_request() {
            if ( !self::is_rest_request() ) {
                return false;
            }
            $route = ( isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '' );
            return 0 !== strpos( ltrim( $route, '/' ), 'wc/store' );
        }

        public static function cron_cache_reset() {
            if ( '1' === get_option( 'awf_enable_cron_cache_reset' ) ) {
                self::clear_awf_cache();
                if ( !empty( get_option( 'awf_cache_reset_counts_rebuild' ) ) ) {
                    $url = get_permalink( wc_get_page_id( 'shop' ) );
                    wp_safe_remote_get( $url );
                    $categories = get_terms( array(
                        'taxonomy'   => 'product_cat',
                        'parent'     => 0,
                        'hide_empty' => false,
                    ) );
                    if ( !is_wp_error( $categories ) ) {
                        foreach ( $categories as $c ) {
                            $url = get_term_link( $c );
                            if ( !is_wp_error( $url ) ) {
                                wp_safe_remote_get( $url );
                            }
                        }
                    }
                }
            }
            delete_option( 'awf_enable_cron_cache_reset' );
        }

        private function awf_autoloader( $class ) {
            if ( 0 !== strpos( $class, 'A_W_F_' ) ) {
                return;
            }
            $class = strtolower( $class );
            $path = A_W_F_PLUGIN_PATH . 'code/';
            $file = 'class-' . str_replace( '_', '-', $class ) . '.php';
            $file_path = $path . $file;
            if ( is_readable( $file_path ) ) {
                include $file_path;
            }
        }

        public static function enqueue_style_options_css() {
            $uploads_dir = wp_upload_dir();
            $path = '/annasta-filters/css/' . get_option( 'awf_style_options_file' );
            if ( file_exists( $uploads_dir['basedir'] . $path ) ) {
                $path = $uploads_dir['baseurl'] . $path;
                if ( is_ssl() ) {
                    $path = str_replace( 'http://', 'https://', $path );
                }
                wp_enqueue_style(
                    'awf-style-options',
                    $path,
                    array(),
                    A_W_F::$plugin_version
                );
            } else {
                $css = get_option( 'awf_style_options_css', '' );
                if ( !empty( $css ) ) {
                    wp_register_style( 'awf-style-options', false );
                    wp_enqueue_style( 'awf-style-options' );
                    wp_add_inline_style( 'awf-style-options', $css );
                }
            }
        }

        /**
         * WPML: translated taxonomy base slug in a URL or permastruct (WPML core filter, for the taxonomies WCML doesn't handle)
         *
         * The base is replaced only as a whole path segment, so that placeholders like %pwb-brand% stay intact.
         *
         * @param string $taxonomy
         * @param string $url
         * @return string
         */
        public static function wpml_translate_taxonomy_base_slug( $taxonomy, $url ) {
            if ( !class_exists( 'SitePress' ) ) {
                return $url;
            }
            $tax_obj = get_taxonomy( $taxonomy );
            if ( empty( $tax_obj->rewrite['slug'] ) ) {
                return $url;
            }
            $original_slug = $tax_obj->rewrite['slug'];
            $translated_slug = apply_filters(
                'wpml_get_translated_slug',
                $original_slug,
                $taxonomy,
                null,
                'taxonomy'
            );
            if ( !empty( $translated_slug ) && $translated_slug !== $original_slug ) {
                $url = preg_replace(
                    '#(^|/)' . preg_quote( trim( $original_slug, '/' ), '#' ) . '(?=/|$)#',
                    '${1}' . addcslashes( trim( $translated_slug, '/' ), '\\$' ),
                    $url,
                    1
                );
            }
            return $url;
        }

        /* Returns the language to restore with wpml_restore_language(), or false when no switch was needed */
        public static function wpml_switch_to_default_language() {
            if ( !class_exists( 'SitePress' ) ) {
                return false;
            }
            $current_language = apply_filters( 'wpml_current_language', null );
            $default_language = apply_filters( 'wpml_default_language', null );
            if ( empty( $current_language ) || empty( $default_language ) || $current_language === $default_language ) {
                return false;
            }
            do_action( 'wpml_switch_language', $default_language );
            return $current_language;
        }

        public static function wpml_restore_language( $language ) {
            if ( !empty( $language ) ) {
                do_action( 'wpml_switch_language', $language );
            }
        }

        final function __clone() {
        }

        // prevent cloning
        final function __wakeup() {
        }

        // prevent serialization
        /** Helper Functions */
        public static function format_print_r( $print, $hide = false ) {
            if ( wp_doing_ajax() ) {
                error_log( print_r( $print, true ) );
            } else {
                if ( $hide ) {
                    print '<pre style="display:none;">';
                } else {
                    print "<pre>";
                }
                print_r( $print );
                print "</pre>";
            }
            //A_W_F::format_print_r( '' );
        }

    }

}