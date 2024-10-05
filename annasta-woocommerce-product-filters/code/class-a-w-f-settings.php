<?php
/**
 * annasta WooCommerce Filters Admin Settings
 *
 */

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_settings' ) ) {
  
  class A_W_F_settings extends WC_Settings_Page {

    public $preset;
    public $settings_url;

    public function __construct() {
      $this->id    = 'annasta-filters';
      $this->label = __( 'annasta Filters', 'annasta-filters' );
      $this->settings_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters' );

      if( isset( $_GET['tab'] ) && 'annasta-filters' === $_GET['tab'] ) {
          
        if( isset( $_GET['section'] ) ) {

          add_action( 'woocommerce_admin_field_awf_settings_ts_header', array( A_W_F::$admin, 'display_ts_header' ) );

					if( 'plugin-settings' === $_GET['section'] ) {
						add_action( 'woocommerce_admin_field_awf_plugin_settings_toggle_btn_customizer_options', array( A_W_F::$admin, 'display_plugin_settings_toggle_btn_customizer_options' ) );
						add_action( 'woocommerce_admin_field_awf_custom_awf_plugin_settings', array( A_W_F::$admin, 'display_custom_awf_plugin_settings' ) );

						if( A_W_F::$premium ) {
							add_action( 'woocommerce_admin_field_awf_advanced_plugin_settings', array( A_W_F::$admin, 'display_advanced_plugin_settings' ) );
						}

					} elseif( 'product-list-settings' === $_GET['section'] ) {
						add_action( 'woocommerce_admin_field_awf_product_list_settings_notice', array( A_W_F::$admin, 'display_product_list_settings_notice' ) );
            add_action( 'woocommerce_admin_field_awf_product_list_settings_custom_selectors_section', array( A_W_F::$admin, 'display_product_list_settings_custom_selectors_section' ) );
						add_action( 'woocommerce_admin_field_awf_product_list_settings_template_options', array( A_W_F::$admin, 'display_product_list_settings_template_options' ) );

					} elseif( 'styles-settings' === $_GET['section'] ) {
						add_action( 'woocommerce_admin_field_awf_styles_settings_custom_options_1', array( A_W_F::$admin, 'display_styles_settings_custom_options_1' ) );
					}
          
        } elseif( isset( $_GET['awf-preset'] ) ) {
          $preset_id = (int) $_GET['awf-preset'];

          if( isset( A_W_F::$presets[$preset_id] ) ) {
            $this->preset = new A_W_F_preset( $preset_id );
          }
        }

        add_action( 'update_option_awf_variations_stock_support', array( $this, 'after_awf_variations_stock_support_update' ), 10, 3 );
      }

      add_action( 'woocommerce_settings_' . $this->id,      array( $this, 'output' ) );
      add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ), 10 );

      add_action( 'pre_update_option_awf_ppp_default', array( $this, 'pre_awf_ppp_default_update' ), 10, 3 );
      add_action( 'update_option_awf_pretty_scrollbars', array( $this, 'after_awf_pretty_scrollbars_update' ), 10, 3 );
      
      if( A_W_F::$premium ) {
        add_action( 'woocommerce_settings_save_' . $this->id, array( A_W_F::$admin, 'save_premium_settings' ), 20 );
      }
      
      parent::__construct();
    }

    public function get_sections() {
      $sections = array(
        ''         => __( 'Filter presets', 'annasta-filters' ),
        'product-list-settings' => __( 'Product Lists', 'annasta-filters' ),
        'styles-settings' => __( 'Styles', 'annasta-filters' ),
        'seo-settings' => __( 'SEO', 'annasta-filters' ),
        'plugin-settings' => __( 'Plugin Settings', 'annasta-filters' )
      );

      if( A_W_F::$premium ) {
        $position = intval( array_search( 'seo-settings', array_keys( $sections ) ) );
        
        $sections = array_merge(
          array_slice( $sections, 0, $position, true),
          array(
            'templates-settings' => __( 'Templates', 'annasta-filters' ),
          ),
          array_slice( $sections, $position, count( $sections ) - 1, true)
        );
      }

      return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }

    public function get_settings( $current_section = '' ) {

      $settings = array();

      if ( 'plugin-settings' == $current_section ) {
        return A_W_F::$admin->get_plugin_settings();

      } elseif( 'product-list-settings' == $current_section ) {
        return A_W_F::$admin->get_product_list_settings();

      } elseif( 'styles-settings' == $current_section ) {
        return A_W_F::$admin->get_styles_settings();

      } elseif( 'templates-settings' == $current_section ) {
        return A_W_F::$admin->get_templates_settings();

      } elseif( 'seo-settings' == $current_section ) {
        return A_W_F::$admin->get_seo_settings();

      } else {
        if( ! empty( $this->preset ) ) {
          return A_W_F::$admin->get_preset_settings( $this->preset );
        }
      }

      return $settings;
    }

    public function output() {

      global $current_section;

      $settings = $this->get_settings( $current_section );

      echo '<div id="awf-settings"';
      if( empty( $current_section ) ) { echo ' class="awf-preset-settings"'; } else { echo ' class="awf-tab-' . $current_section . '"'; }
      echo '>';

      if ( 'plugin-settings' == $current_section  ) {
        WC_Admin_Settings::output_fields( $settings );
        
      } elseif( 'product-list-settings' == $current_section ) {
        WC_Admin_Settings::output_fields( $settings );
        
      } elseif( 'styles-settings' == $current_section ) {
        WC_Admin_Settings::output_fields( $settings );
        A_W_F::$admin->display_user_css_settings();
        
      } elseif( 'templates-settings' == $current_section ) {
        $GLOBALS['hide_save_button'] = true;
        WC_Admin_Settings::output_fields( $settings );
        A_W_F::$admin->display_custom_templates_settings();
        
      } elseif( 'seo-settings' == $current_section ) {
        WC_Admin_Settings::output_fields( $settings );
        A_W_F::$admin->display_custom_seo_settings();
        
      } else {

        if( ! empty( $this->preset ) ) {
          $this->preset = new A_W_F_preset( $this->preset->id ); // refresh for cases it's a save
          $this->display_preset_breadcrumb();

          WC_Admin_Settings::output_fields( $settings );

          $customizer_options = get_option( 'awf_customizer_options', array() );

          echo '<div id="awf-active-visibility-notices">',
          
          '<div id="awf-active-visibility-preset-insertion-notice" class="awf-active-visibility-notice">',
            '<i class="fas fa-exclamation-circle"></i><span>',
            sprintf( wp_kses( __( 'Don\'t forget to <a href="%1$s" target="_blank"><strong>insert this preset</strong></a> into the needed sidebar, header, or other area of your site.', 'annasta-filters' ), array( 'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() ) ), esc_url( 'https://annasta.net/plugins/annasta-woocommerce-product-filters/tutorials/getting-started/#inserting-preset-into-the-site' ) ),
          '</span></div>',

          '<div id="awf-active-visibility-fix-toggle-btn" class="awf-active-visibility-notice">',
            '<input id="awf_toggle_filters_button_fixed_position" type="checkbox" value="1"',
            ( ( isset( $customizer_options['awf_filters_button_fixed_position'] ) &&  ('yes' === $customizer_options['awf_filters_button_fixed_position'] ) ) ? ' checked' : '' ), 
            '>',
            '<label for="awf_toggle_filters_button_fixed_position">', esc_html__( 'Fix the "Filters" button page position', 'annasta-filters' ), '</label>',
            '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Enable the floating "Filters" button mode.', 'annasta-filters' ) . '"></span>',
          '</div>',

          '<div id="awf-active-visibility-toggle-btn-notice" class="awf-active-visibility-notice">',
            sprintf( wp_kses( __( 'To <strong>further customize the "Filters" button</strong> go to annasta Filters > Plugin settings > <a href="%1$s" target="_blank"><strong>"Filters" toggle button settings</strong></a>.', 'annasta-filters' ), array( 'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() ) ), admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=plugin-settings&awf-expanded-sections=1' ) ),
          '</div>',

          '</div>';

          echo '<span id="awf-togglable-visibility-notice" style="display:none;">', esc_html__( 'Auto-inserted!', 'annasta-filters' ), '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'You don\'t need to insert your preset via widget/shortcode when using the "Controlled by "Filters" button" visibility mode.', 'annasta-filters' ) . '"></span></span>';

          echo '<span id="awf-togglable-mode-notice" class="awf-info-notice awf-attention-notice" style="display:none;">',
          sprintf( wp_kses( __( 'Presets cannot be displayed under the floating "Filters" button. Please disable the <a href="%1$s" target="_blank"><strong>Fix page position</strong></a> option of the annasta Filters > Plugin settings > <a href="%2$s" target="_blank"><strong>"Filters" toggle button settings</strong></a> admin section to be able to use this mode.', 'annasta-filters' ), array( 'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() ) ), esc_url( 'https://annasta.net/plugins/annasta-woocommerce-product-filters/documentation/plugin-settings/fix-toggle-button-page-position/' ), admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=plugin-settings&awf-expanded-sections=1' ) ),
          '</span>';

          if( A_W_F::$premium ) {
            echo '<button type="button" id="awf-popup-custom-layout-btn" class="button button-secondary awf-fa-icon awf-fas-icon awf-fa-edit-btn" style="display:none;" title="', esc_html__( 'Customize columns', 'annasta-filters' ), '"></button>';
          }

          echo '<input id="awf-preset-id" type="hidden" value="', esc_attr( $this->preset->id ), '">';
          echo '<h3>', esc_html__( 'Display on', 'annasta-filters' ), '<span class="woocommerce-help-tip" data-tip="', esc_attr__( 'Select the pages of your site on which you wish to display the current preset.', 'annasta-filters' ), '"></span></h3>';
          
          A_W_F::$admin->display_associations( $this->preset->id );

          $this->display_filters();

        } else {
          A_W_F::$admin->display_dashboard();
          $this->display_presets_list();
        }
      }

      echo '<div class="awf-spinner-overlay" style="display: none;"></div>';
      echo '</div><!-- #awf-settings -->';

    }

    public function save() {

      global $current_section;

      $settings = $this->get_settings( $current_section );
      WC_Admin_Settings::save_fields( $settings );

      if( ! empty( $this->preset ) ) {
        $clear_counts_cache = false;
        $meta_names = array();

        foreach( A_W_F::$presets[$this->preset->id]['filters'] as $filter_id => $position ) {

          $filter = new A_W_F_filter( $this->preset->id, $filter_id );
          $response = A_W_F::$admin->update_filter( $filter );
          
          if( ! empty( $filter->settings['meta_name'] ) ) { $meta_names[$filter->id] = $filter->settings['meta_name']; }
          if( isset( $response['clear_counts_cache'] ) ) { $clear_counts_cache = true; }
        }

        if( ! empty( $meta_names ) ) {
          if( count( $meta_names ) !== count( array_unique( $meta_names ) ) ) {
            $reset_meta_names = array_diff_key( $meta_names, array_unique( $meta_names ) );

            foreach( $reset_meta_names as $filter_id => $meta_name ) {
              $filter = new A_W_F_filter( $this->preset->id, $filter_id );
              $filter->settings['meta_name'] = '';
              update_option( $filter->prefix. 'settings', $filter->settings );
              
              WC_Admin_Settings::add_error( sprintf( __( 'Duplicate meta data names are not allowed in the same preset. The meta data name setting of the %1$s filter has been reset.', 'annasta-filters' ), $filter->settings['title'] ) );
            }
          }
        }
        
        A_W_F::build_query_vars();
        
        if( $clear_counts_cache ) { A_W_F::$admin->clear_product_counts_cache(); }
        A_W_F::$admin->generate_styles_css();

      } elseif( 'product-list-settings' == $current_section ) {
        $custom_selectors = array();
        $theme_template = sanitize_title( strtolower( get_template() ) );

        if( isset( $_POST['awf_products_html_wrapper'] ) ) {
          update_option( 'awf_products_html_wrapper', trim( sanitize_text_field( $_POST['awf_products_html_wrapper'] ) ) );
        }

        if( isset( $_POST['awf_force_wrapper_reload'] ) ) {
          update_option( 'awf_force_wrapper_reload', 'yes' );
        } else {
          update_option( 'awf_force_wrapper_reload', 'no' );
        }

        if( isset( $_POST['awf_custom_selectors_products'] ) ) {
          $custom_selectors['products'] = trim( sanitize_text_field( $_POST['awf_custom_selectors_products'] ) );
        }

        if( isset( $_POST['awf_custom_selectors_product'] ) ) {
          $custom_selectors['product'] = trim( sanitize_text_field( $_POST['awf_custom_selectors_product'] ) );
        }

        if( isset( $_POST['awf_custom_selectors_pagination'] ) ) {
          $custom_selectors['pagination'] = trim( sanitize_text_field( $_POST['awf_custom_selectors_pagination'] ) );
        }

        if( isset( $_POST['awf_custom_selectors_page_number'] ) ) {
          $custom_selectors['page_number'] = trim( sanitize_text_field( $_POST['awf_custom_selectors_page_number'] ) );
        }

        if( isset( $_POST['awf_custom_selectors_pagination_next'] ) ) {
          $custom_selectors['pagination_next'] = trim( sanitize_text_field( $_POST['awf_custom_selectors_pagination_next'] ) );
        }

        if( isset( $_POST['awf_custom_selectors_orderby'] ) ) {
          $custom_selectors['orderby'] = trim( sanitize_text_field( $_POST['awf_custom_selectors_orderby'] ) );
        }

        if( isset( $_POST['awf_custom_selectors_no_result'] ) ) {
          $custom_selectors['no_result'] = trim( sanitize_text_field( $_POST['awf_custom_selectors_no_result'] ) );
        }

        if( isset( $_POST['awf_custom_theme_selectors_enabled'] ) ) {
          $custom_selectors['disable_theme_support'][$theme_template] = false;
        } else {
          $custom_selectors['disable_theme_support'][$theme_template] = true;
        }

        update_option( 'awf_custom_selectors', $custom_selectors );

        A_W_F::$admin->update_product_list_settings();
				A_W_F::$admin->generate_styles_css();

      } elseif( 'styles-settings' == $current_section ) {
        $customizer_options = get_option( 'awf_customizer_options', array() );

        if( isset( $_POST['awf_loader_style'] ) ) { $customizer_options['awf_loader_style'] = intval( $_POST['awf_loader_style'] ); }
        if( isset( $_POST['awf_loader_size'] ) ) { $customizer_options['awf_loader_size'] = intval( $_POST['awf_loader_size'] ); }
        if( isset( $_POST['awf_loader_speed'] ) ) { $customizer_options['awf_loader_speed'] = sanitize_title( $_POST['awf_loader_speed'] ); }
        if( isset( $_POST['awf_loader_color'] ) ) { $customizer_options['awf_loader_color'] = sanitize_hex_color( $_POST['awf_loader_color'] ); }
        if( isset( $_POST['awf_loader_opacity'] ) ) { $customizer_options['awf_loader_opacity'] = floatval( $_POST['awf_loader_opacity'] ); }
        if( isset( $_POST['awf_fix_loader'] ) ) { $customizer_options['awf_fix_loader'] = 'yes'; } else { $customizer_options['awf_fix_loader'] = ''; }
        if( isset( $_POST['awf_overlay_color'] ) ) { $customizer_options['awf_overlay_color'] = sanitize_hex_color( $_POST['awf_overlay_color'] ); }
        if( isset( $_POST['awf_overlay_opacity'] ) ) { $customizer_options['awf_overlay_opacity'] = floatval( $_POST['awf_overlay_opacity'] ); }

        update_option( 'awf_customizer_options', $customizer_options );

        update_option( 'awf_user_css', trim( $_POST['awf_user_css'] ) );
        A_W_F::$admin->generate_styles_css();
        
      } elseif( 'seo-settings' == $current_section ) {
        A_W_F::$admin->update_seo_settings();
        
      } elseif( 'plugin-settings' == $current_section ) {
        
        $customizer_options = get_option( 'awf_customizer_options', array() );
        
        if( isset( $_POST['awf_customizer_options']['awf_filters_button_fixed_position'] ) ) {
          $customizer_options['awf_filters_button_fixed_position'] = 'yes';

        } else {
          $customizer_options['awf_filters_button_fixed_position'] = 'no';
        }
        
        foreach( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
          $option = 'awf_filters_button_fixed_' . $side;

          if( isset( $_POST[$option] ) ) {
            if( '' === $_POST[$option] ) {
              $customizer_options[$option] = '';
            } else {
              $customizer_options[$option] = intval( $_POST[$option] );
            }
          }
        }

        update_option( 'awf_customizer_options', $customizer_options );

        update_option( 'awf_user_js', trim( $_POST['awf_user_js'] ) );
        update_option( 'awf_counts_cache_days', intval( $_POST['awf_counts_cache_days'] ) );

        A_W_F::$admin->generate_styles_css();
      }
    }

    public function pre_awf_ppp_default_update( $new_value, $old_value, $option_name ) {
      $old_value = absint( $old_value );
      $new_value =  absint( $new_value );

      if( $old_value !== $new_value ) {
        if( $new_value > absint( get_option( 'awf_ppp_limit', '200' ) ) ) {
          $new_value = absint( get_option( 'awf_ppp_limit', '200' ) );
        }
      }

      if( empty( $new_value ) ) { $new_value = ''; }

      return $new_value;
    }

    public function after_awf_pretty_scrollbars_update( $old_value, $new_value, $option_name ) {
      if( $old_value !== $new_value ) {
        A_W_F::$admin->generate_styles_css();
      }
    }

    public function after_awf_variations_stock_support_update( $old_value, $new_value, $option_name ) {
      if( $old_value !== $new_value ) {
        A_W_F::$admin->clear_awf_cache();
      }
    }

    private function display_presets_list() {
      global $hide_save_button;
      $hide_save_button = true;

      $associations_by_preset = A_W_F::$admin->build_associations_lists();

      include( A_W_F_PLUGIN_PATH . 'templates/admin/presets-list.php' );
    }

    private function display_preset_breadcrumb() {
      echo
        '<div class="awf-preset-breadcrumb">',
        '<a href="', esc_url( $this->settings_url ), '">',
        esc_html__( 'Filter Presets', 'annasta-filters' ), '</a>',
        '<span class="dashicons dashicons-arrow-right-alt2 awf-breadcrumb-separator"></span>',
        '<span class="awf-breadcrumb-preset-name">', esc_html( $this->preset->name ), '</span>'
      ;

      if( A_W_F::$premium ) {
        echo '<button class="button button-secondary awf-fa-icon awf-fas-icon awf-popup-preset-templates-btn" type="button" title="', esc_attr__( 'Import settings from another preset or template', 'annasta-filters' ), '" data-preset-id="', esc_attr( $this->preset->id ), '"></button>'
        ;
      }

      echo '</div>';
    }   

    public function display_filters() {
      $filters = A_W_F::$admin->get_all_filters();
      $filters_select = array_diff_key( $filters, array_flip( array_diff( $this->get_preset_filters(), array( 'meta' ) ) ) );

      include( A_W_F_PLUGIN_PATH . 'templates/admin/filters-list.php' );
    }

    private function get_preset_filters() {
      $filters = array();

        foreach( A_W_F::$presets[$this->preset->id]['filters'] as $filter_id => $position ) {
          $prefix = A_W_F_filter::get_prefix( $this->preset->id, $filter_id );
          $name = get_option( $prefix . 'name' );

          $filters[] = $name;
        }

      return $filters;
    }

  }
}

?>