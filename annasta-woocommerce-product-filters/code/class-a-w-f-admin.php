<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if(! class_exists('A_W_F_admin') ) {
  
  class A_W_F_admin {
    
    /** Current running instance of A_W_F_admin or A_W_F_premium_admin object
     *
     * @since 1.0.0
     * @var A_W_F_admin (or A_W_F_premium_admin) object
     */
    protected static $instance;
    
    /** Allowed filter control types (single select, multiple select, range select)
     *
     * @since 1.0.0
     * @var array
     */
    public $filter_types;
    
    /** Allowed filter styles
     *
     * @since 1.0.0
     * @var array
     */
    public $filter_styles;
    
    /** Extentions' type and style limitations
     *
     * @since 1.0.0
     * @var array
     */
    public $filter_style_limitations;
    
    protected function __construct() {
      if ( version_compare( A_W_F_VERSION, get_option( 'awf_version', '0.0.0' ) ) > 0 ) {
        add_action( 'plugins_loaded', array( $this, 'after_plugin_activation' ), 30 );
      }
      
      add_action( 'admin_menu', array( $this, 'add_plugin_menu' ) );
      add_filter( 'plugin_action_links_' . plugin_basename( A_W_F_PLUGIN_FILE ), array( $this, 'plugin_settings_link' ) );
      add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
      add_filter( 'woocommerce_get_settings_pages', array( $this, 'set_plugin_settings_tab' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 20 );
      add_action( 'wp_ajax_awf_admin', array( $this, 'ajax_controller' ) );
      
      add_action( 'before_delete_post', array( $this, 'on_product_deletion') );
      add_action( 'wp_trash_post', array( $this, 'on_product_trashing') );
      add_filter( 'untrashed_post', array( $this, 'on_product_untrashing') );
      add_action( 'woocommerce_update_product', array( $this, 'on_product_update' ) );
      add_action( 'created_product_cat', array( $this, 'on_product_cat_created' ), 10, 2 );
      add_action( 'delete_product_cat', array( $this, 'on_product_cat_deleted' ), 10, 4 );
      
      $this->filter_types = array(
        'single' => array(
          'label' => __( 'Single item selection', 'annasta-filters' ),
          'styles' => array( 'radios', 'icons', 'labels', 'colours', 'tags' )
        ),
        'multi' => array(
          'label' => __( 'Multiple items selection', 'annasta-filters' ),
          'styles' => array( 'checkboxes', 'icons', 'colours', 'tags' )
        ),
        'range' => array(
          'label' => __( 'Range selection', 'annasta-filters' ),
          'styles' => array( 'range-slider', 'radios', 'icons', 'labels', 'range-stars' )
        ),
        'date' => array(
          'label' => __( 'Dates selection', 'annasta-filters' ),
          'styles' => array( 'daterangepicker' )
        )
      );

      $this->filter_styles = array(
        'checkboxes' => __( 'System checkboxes', 'annasta-filters' ),
        'radios' => __( 'System radio buttons', 'annasta-filters' ),
        'range-slider' => __( 'Range slider', 'annasta-filters' ),
        'range-stars' => __( 'Stars', 'annasta-filters' ),
        'labels' => __( 'Labels', 'annasta-filters' ),
        'icons' => __( 'Custom icons', 'annasta-filters' ),
        'images' => __( 'Images', 'annasta-filters' ),
        'colours' => __( 'Color boxes', 'annasta-filters' ),
        'custom-terms' => __( 'Custom term icons and labels', 'annasta-filters' ),
        'tags' => __( 'Tags', 'annasta-filters' ),
        'daterangepicker' => __( 'Date picker', 'annasta-filters' ),
      );

      $this->filter_style_limitations = array(
        'taxonomy' => array(
          'single' => array( 'radios', 'labels', 'icons', 'images', 'colours', 'tags', 'custom-terms' ),
          'multi' => array( 'checkboxes', 'icons', 'images', 'colours', 'tags', 'custom-terms' ),
        ),
        'price' => array(
          'range' => array( 'range-slider', 'radios', 'icons', 'labels', 'images', 'custom-terms' )
        ),
        'stock' => array(
          'single' => array( 'radios', 'icons', 'labels', 'images', 'tags', 'custom-terms' )
        ),
        'featured' => array(
          'multi' => array( 'checkboxes', 'icons', 'labels', 'images', 'custom-terms' )
        ),
        'rating' => array(
          'range' => array( 'radios', 'icons', 'labels', 'images', 'custom-terms' )
        ),
        'onsale' => array(
          'multi' => array( 'checkboxes', 'icons', 'labels', 'images', 'custom-terms' )
        ),
        'ppp' => array(
          'single' => array( 'radios', 'icons', 'labels', 'images', 'tags' )
        ),
        'orderby' => array(
          'single' => array( 'radios', 'icons', 'labels', 'images', 'tags', 'custom-terms' )
        ),
        'meta' => array(
          'single' => array( 'radios', 'labels', 'icons', 'images', 'colours', 'tags', 'custom-terms' ),
          'multi' => array( 'checkboxes', 'icons', 'images', 'colours', 'tags', 'custom-terms' ),
          'range' => array( 'range-slider', 'radios', 'icons', 'labels', 'images', 'custom-terms' ),
          'date' => array( 'daterangepicker' ),
        ),
      );
    }
    
    public function add_plugin_menu() {
      add_menu_page(
          __( 'annasta Filters Settings', 'annasta-filters' ),
          __( 'annasta Filters', 'annasta-filters' ),
          'manage_woocommerce',
          'annasta-filters',
          array( $this, 'safe_redirect_to_settings' ),
          'dashicons-filter',
          56
      );
      
      add_submenu_page( 'annasta-filters', '', __( 'Filter presets', 'annasta-filters' ), 'manage_woocommerce', 'annasta-filters', array( $this, 'safe_redirect_to_settings' ), 0 );
      
      add_submenu_page( 'annasta-filters', '', __( 'Product lists', 'annasta-filters' ), 'manage_woocommerce', 'annasta-filters-product-list-settings', array( $this, 'safe_redirect_to_product_list_settings' ), 1 );
      
      add_submenu_page( 'annasta-filters', '', __( 'Style settings', 'annasta-filters' ), 'manage_woocommerce', 'annasta-filters-styles-settings', array( $this, 'safe_redirect_to_styles_settings' ), 2 );
      
      add_submenu_page( 'annasta-filters', '', __( 'SEO settings', 'annasta-filters' ), 'manage_woocommerce', 'annasta-filters-seo-settings', array( $this, 'safe_redirect_to_seo_settings' ), 4 );
      
      add_submenu_page( 'annasta-filters', '', __( 'Plugin settings', 'annasta-filters' ), 'manage_woocommerce', 'annasta-filters-plugin-settings', array( $this, 'safe_redirect_to_plugin_settings' ), 5 );
    }
    
    public function after_plugin_activation() {
      
      if( version_compare( PHP_VERSION, '5.5' ) < 0 ) {
        add_action( 'admin_notices', array( $this, 'display_php_version_warning' ) );
      }
        
      if( false === get_option( 'awf_version', false ) ) {
        /* Fresh installation */

        update_option( 'awf_global_wrapper', 'yes' );
        update_option( 'awf_use_wc_orderby', 'yes' );
        update_option( 'awf_range_slider_style', 'bars' );
				
			} else {
        /* Updates */
        
        if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.2.0', '<' ) ) { update_option( 'awf_redirect_archives', 'yes' ); }
        
        if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.2.8', '<' ) ) {
          if( ! empty( $color_image_style = get_option( 'awf_color_image_style', false ) ) ) {
            update_option( 'awf_color_filter_style', $color_image_style );
            update_option( 'awf_image_filter_style', $color_image_style );
          }
          delete_option( 'awf_color_image_style' );
        }
        
        if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.3.0', '<' ) ) {
					update_option( 'awf_custom_style', 'deprecated-1-3-0' );
					if( 'yes' === get_option( 'awf_remove_wc_orderby', 'no' ) ) { update_option( 'awf_display_wc_orderby', 'no' ); }
        }
        
        if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.0', '<' ) ) {
          /* Deprecated options: awf_display_wc_shop_title, awf_display_wc_orderby, awf_shop_title_badges */

          if( false === get_option( 'awf_product_list_template_options', false ) ) {
            if( 'no' === get_option( 'awf_display_wc_shop_title', 'yes' ) ) { update_option( 'awf_remove_wc_shop_title', 'yes' ); }
            if( 'no' === get_option( 'awf_display_wc_orderby', 'yes' ) ) { update_option( 'awf_remove_wc_orderby', 'yes' ); }
            if( 'yes' === get_option( 'awf_shop_title_badges', 'no' ) ) {
              update_option( 'awf_product_list_template_options', array( 'active_badges' => array( array( 'hook' => 'js', 'priority' => 15 ) ) ) );
            }
          }

          update_option( 'awf_force_wrapper_reload', 'yes' );
        }
        
        if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.9', '<' ) ) {
					update_option( 'awf_get_parameters_support', 'yes' );
        }
        
        if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.6.0', '<' ) ) {
					update_option( 'awf_ajax_mode', 'dedicated_ajax' );
        }

        /* Presets and Filters updates */
        $update_presets = false;
        
        foreach( A_W_F::$presets as $preset_id => $preset ) {
          
          if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.1.2', '<' ) ) {
            
            $ajax_on = get_option( 'awf_preset_' . $preset_id . '_ajax_on', '' );
            $button_on = get_option( 'awf_preset_' . $preset_id . '_button_on', '' );
            $type = 'ajax';

            if( 'yes' === $ajax_on ) {
              if( 'yes' === $button_on ) {
                $type = 'ajax-button';
              }

            } else {
              if( 'yes' === $button_on ) {
                $type = 'form';
              } else {
                $type = 'url';
              }
            }

            update_option( 'awf_preset_' . $preset_id . '_type', $type );
            delete_option( 'awf_preset_' . $preset_id . '_ajax_on' );
            delete_option( 'awf_preset_' . $preset_id . '_button_on' );
          }
          
          if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.2.7', '<' ) ) {
            
            foreach( $preset['associations'] as $i => $association_id ) {
              if( in_array( $association_id, array( 'all', 'shop-pages' ) ) ) { continue; }
              
              $association_data = explode( '--', $association_id );
              
              if( ! isset( $association_data[0] ) || 'wp_page' === $association_data[0] ) { continue; }
              if( ! isset( $association_data[1] ) || in_array( $association_data[1], array( 'archive-pages', 'shop-pages' ) ) ) { continue; }
              
              A_W_F::$presets[$preset_id]['associations'][$i] = $association_id . '--shop-page';
              $update_presets = true;
            }
          }
          
          foreach( $preset['filters'] as $filter_id => $position ) {
            $filter = new A_W_F_filter( $preset_id, $filter_id );
            
            if( version_compare( get_option( 'awf_version' ), '1.0.7', '<' ) ) {
              
              if( ! in_array( $filter->module, array( 'featured', 'onsale', 'ppp' ) )
                  && ! array_key_exists( 'active_prefix', $filter->settings )
              ) {
                $active_prefix = '';
                
                if( isset( $filter->settings['style_options']['badge_label'] ) ) {
                  $active_prefix = sanitize_text_field( $filter->settings['style_options']['badge_label'] );
                  unset( $filter->settings['style_options']['badge_label'] );
                }

                $position = 3;
                if( 'taxonomy' === $filter->module ) { $position = 4; }

                $filter->settings = array_merge(
                  array_slice( $filter->settings, 0, $position, true ),
                  array( 'active_prefix' => $active_prefix ),
                  array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true )
                );
              }
            }
            
            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.1.2', '<' ) ) {
              if( 'range-slider' === $filter->settings['style'] ) {
                if( ! isset( $filter->settings['type_options']['decimals'] ) ) { $filter->settings['type_options']['decimals'] = intval( 0 ); }
                if( ! isset( $filter->settings['style_options']['step'] ) ) { $filter->settings['style_options']['step'] = floatval( 1 ); }
                if( ! isset( $filter->settings['style_options']['value_prefix'] ) ) { $filter->settings['style_options']['value_prefix'] = ''; }
                if( ! isset( $filter->settings['style_options']['value_postfix'] ) ) { $filter->settings['style_options']['value_postfix'] = ''; }
              }
            }
            
            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.2.8', '<' ) ) {
              if( ! isset( $filter->settings['is_collapsible'] ) ) {
                $position = intval( array_search( 'type', array_keys( $filter->settings ) ) );
                
                if( ! empty( $position ) ) {
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    array( 'is_collapsible' => false, 'collapsed_on' => false ),
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }
              }
            }

            if( A_W_F::$premium && version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.0', '<' ) ) {
              if( 'taxonomy' === $filter->module ) {
                $position = intval( array_search( 'reset_all', array_keys( $filter->settings ) ) );

                if( ! empty( $position ) && ! isset( $filter->settings['reset_active'] ) && ! isset( $filter->settings['reset_active_label'] ) ) {
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    array( 'reset_active' => false, 'reset_active_label' => _x( 'Clear filters', 'Label for single filter reset button', 'annasta-filters' ) ),
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }
              }

              if( in_array( $filter->module, array( 'taxonomy', 'price', 'stock', 'ppp', 'orderby', 'meta' ) ) ) {
                $position = intval( array_search( 'type', array_keys( $filter->settings ) ) );

                if( ! empty( $position ) && ! isset( $filter->settings['active_dropdown_title'] ) ) {
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    array( 'active_dropdown_title' => false ),
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }
              }
            }

            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.5', '<' ) ) {
              if( 'taxonomy' === $filter->module ) {
                $position = intval( array_search( 'children_collapsible', array_keys( $filter->settings ) ) );

                if( ! empty( $position ) && ! isset( $filter->settings['display_children'] ) ) {
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    array(
                      'hierarchical_level' => 1,
                      'display_children' => true,
                    ),
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }

                if( A_W_F::$premium ) {
                  $position = intval( array_search( 'show_search', array_keys( $filter->settings ) ) );

                  if( ! empty( $position ) && ! isset( $filter->settings['hierarchical_sbs'] ) ) {
                    $filter->settings = array_merge(
                      array_slice( $filter->settings, 0, $position, true),
                      array( 'hierarchical_sbs' => false, 'hide_preset_submit_btn' => false ),
                      array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                    );
                  }
                }
              }
            }

            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.6', '<' ) ) {
              if( isset( $filter->settings['style'] ) && 'custom-terms' === $filter->settings['style'] && isset( $filter->settings['style_options']['term_icons'] ) && isset( $filter->settings['style_options']['term_icons_solids'] ) && ! isset( $filter->settings['style_options']['term_icons_hover'] ) ) {
                $filter->settings['style_options']['term_icons_hover'] = $filter->settings['style_options']['term_icons'];
                $filter->settings['style_options']['term_icons_active'] = $filter->settings['style_options']['term_icons'];
                $filter->settings['style_options']['term_icons_active_hover'] = $filter->settings['style_options']['term_icons'];
                $filter->settings['style_options']['term_icons_hover_solids'] = $filter->settings['style_options']['term_icons_solids'];
                $filter->settings['style_options']['term_icons_active_solids'] = $filter->settings['style_options']['term_icons_solids'];
                $filter->settings['style_options']['term_icons_active_hover_solids'] = $filter->settings['style_options']['term_icons_solids'];
              }
            }

            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.7', '<' ) ) {
              if( isset( $filter->settings['excluded_items'] ) && ! isset( $filter->settings['terms_limitation_mode'] ) ) {
                $position = intval( array_search( 'excluded_items', array_keys( $filter->settings ) ) );

                $filter->settings = array_merge(
                  array_slice( $filter->settings, 0, $position, true),
                  array( 'terms_limitation_mode' => 'exclude', 'included_items' => array() ),
                  array_slice( $filter->settings, $position, count( $filter->settings ) - $position, true)
                );
              }
            }

            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.5.5', '<' ) ) {
              if( isset( $filter->settings['height_limit'] ) && ! isset( $filter->settings['shrink_height_limit'] ) ) {
                $position = 1 + intval( array_search( 'height_limit', array_keys( $filter->settings ) ) );

                $filter->settings = array_merge(
                  array_slice( $filter->settings, 0, $position, true),
                  array( 'shrink_height_limit' => false ),
                  array_slice( $filter->settings, $position, count( $filter->settings ) - $position, true)
                );
              }
            }

            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.7.0', '<' ) ) {
              if( ! isset( $filter->settings['button_submission'] ) ) {
                $position = intval( array_search( 'show_title', array_keys( $filter->settings ) ) );
                
                if( ! empty( $position ) ) {
                  ++$position;
        
                  $new_options = array( 'button_submission' => false );
                  if( 'search' === $filter->module ) {
                    $new_options['submit_on_change'] = false;
                  }
        
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    $new_options,
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }
        
                if( ! empty( $filter->settings['type_options']['button_submission'] ) ) {
                  $filter->settings['button_submission'] = true;
                  $filter->settings['style_options']['submit_button_label'] = empty( $filter->settings['type_options']['submit_button_label'] ) ? '' : $filter->settings['type_options']['submit_button_label'];

                } elseif( ! empty( $filter->settings['style_options']['show_range_btn'] ) ) {
                  $filter->settings['button_submission'] = true;
                  $filter->settings['style_options']['submit_button_label'] = __( 'Filter', 'Submit button label', 'annasta-filters' );
                }
        
                if( ! empty( $filter->settings['hide_preset_submit_btn'] ) && 'ajax-button' === get_option( 'awf_preset_' . $preset_id . '_type', 'ajax' ) ) {
                  update_option( 'awf_preset_' . $preset_id . '_type', 'ajax-delegated' );
                }
              }
            }

            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.7.1', '<' ) ) {
              if( isset( $filter->settings['hide_empty'] ) && ! isset( $filter->settings['hide_filter'] ) ) {
                $position = intval( array_search( 'hide_empty', array_keys( $filter->settings ) ) );

                $value =  '';
                if( 'yes' === get_option( 'awf_hide_empty_filters', 'no' ) && 'hidden' === $filter->settings['hide_empty'] ) { $value = 'zero'; }
                
                if( ! empty( $position ) ) {
                  ++$position;
        
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    array( 'hide_filter' => $value ),
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }
              }
            }

            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.7.2', '<' ) ) {
              if( isset( $filter->settings['force_reload'] ) && ! isset( $filter->settings['redirect_to_archive'] ) ) {
                $position = intval( array_search( 'force_reload', array_keys( $filter->settings ) ) );
                
                if( ! empty( $position ) ) {
                  ++$position;
        
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    array( 'redirect_to_archive' => false ),
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }
              }
            }

            update_option( $filter->prefix. 'settings', $filter->settings );
          }
        }
        
        if( $update_presets ) { update_option( 'awf_presets', A_W_F::$presets ); }
        
      }
      
      /* Options and actions added by versions after the plugin activation */
      
      if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.1.0', '<' ) ) {
        if( empty( get_option( 'awf_product_cat_pretty_name' ) ) ) { update_option( 'awf_product_cat_pretty_name', 'product-categories' ); }
        if( empty( get_option( 'awf_product_tag_pretty_name' ) ) ) { update_option( 'awf_product_tag_pretty_name', 'product-tags' ); }
      }
      
      if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.6.7', '<' ) ) {
        add_action( 'init', array( 'A_W_F', 'build_query_vars' ) );
      }

      $this->generate_styles_css();
      
      update_option( 'awf_version', A_W_F_VERSION );
    }
    
    public function display_php_version_warning() {
      echo '<div class="notice notice-error"><p>',
      sprintf( esc_html__( 'annasta Woocommerce Product Filters requires PHP Version 5.5 or later to function. Your server currently runs PHP version %1$s. Please, install the newer version of PHP on your server for the plugin to function properly.', 'annasta-filters' ), PHP_VERSION ),
      '</p></div>';
    }
    
    public function set_plugin_settings_tab( $tabs ) {
      $tabs[] = new A_W_F_settings();
      
      return $tabs;
    }
    
    public function plugin_settings_link( $links ) {
      if ( current_user_can( 'manage_woocommerce' ) ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=annasta-filters' ) ) . '" aria-label="' . esc_attr__( 'View annasta Filters settings', 'annasta-filters' ) . '">' . esc_html__( 'Settings', 'annasta-filters' ) . '</a>';

        array_unshift( $links, $settings_link );
      }
      
      return $links;
    }
    
    public function plugin_row_meta( $links, $file ) {
      if ( strpos( $file, 'annasta-woocommerce-product-filters.php' ) !== false ) {
        $new_links = array(
            'documentation' => '<a href="' . esc_url( 'https://annasta.net/plugins/annasta-woocommerce-product-filters/documentation/' ) . '" aria-label="' . esc_attr__( 'View annasta Filters documentation', 'annasta-filters' ) . '" target="_blank">' . esc_html__( 'Documentation', 'annasta-filters' ) . '</a>'
        );

        $links = array_merge( $links, $new_links );
      }

      return $links;
    }

    public function redirect_to_presets_tab( $args = array() ) {
      $redirect_url = add_query_arg( $args, admin_url( 'admin.php?page=wc-settings&tab=annasta-filters' ) );

      wp_redirect( $redirect_url );
      exit();
    }

    public function safe_redirect_to_settings() {
      $redirect_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters' );

      wp_safe_redirect( $redirect_url );
      exit();
    }

    public function safe_redirect_to_product_list_settings() {
      $redirect_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=product-list-settings' );

      wp_safe_redirect( $redirect_url );
      exit();
    }

    public function safe_redirect_to_styles_settings() {
      $redirect_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=styles-settings' );

      wp_safe_redirect( $redirect_url );
      exit();
    }

    public function safe_redirect_to_seo_settings() {
      $redirect_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=seo-settings' );

      wp_safe_redirect( $redirect_url );
      exit();
    }

    public function safe_redirect_to_plugin_settings() {
      $redirect_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=plugin-settings' );

      wp_safe_redirect( $redirect_url );
      exit();
    }
    
    public function enqueue_admin_scripts( $hook ) {
      
      if( isset( $_GET['tab'] ) && 'annasta-filters' === $_GET['tab'] ) {
        
        if( isset( $_GET['awf-preset'] ) ) {
          wp_enqueue_style( 'wp-color-picker' );
          wp_enqueue_style( 'awf-nouislider-styles', A_W_F_PLUGIN_URL . '/styles/nouislider.min.css', array() );
          wp_enqueue_script( 'awf-nouislider', A_W_F_PLUGIN_URL . '/code/js/nouislider.min.js', array() );
          wp_enqueue_script( 'awf-wnumb', A_W_F_PLUGIN_URL . '/code/js/wNumb.js', array() );
        }

        wp_enqueue_style( 'awf-styles', A_W_F_PLUGIN_URL . '/styles/awf-admin.css', false, A_W_F::$plugin_version );
        A_W_F::enqueue_style_options_css();
        wp_enqueue_style( 'awf-fontawesome', A_W_F_PLUGIN_URL . '/styles/fontawesome-all.min.css', array() );
        wp_enqueue_script( 'awf-admin', A_W_F_PLUGIN_URL . '/code/js/awf-admin.js', array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable', 'jquery-blockui' ), A_W_F::$plugin_version );
        
        if( version_compare( WC_VERSION, '3.6', '>=' ) ) {
          wp_enqueue_style( 'awf-select2-fix', A_W_F_PLUGIN_URL . '/styles/awf-select2-fix.css', false, A_W_F::$plugin_version );
        }

        wp_localize_script( 'awf-admin', 'awf_js_data', array(
          'awf_ajax_referer' => wp_create_nonce( 'awf_ajax_nonce' ),
          'l10n' => array(
            'range_change_confirmation' => esc_html__( 'Changing the range type will force a preset update. Some of the current range settings might be lost. Are you ready to proceed?', 'annasta-filters' ),
            'add_seo_filters_btn_label' => esc_html__( 'Insert annasta filters list', 'annasta-filters' ),
            'apply_filter_template_confirmation' => esc_html__( 'Any unsaved changes to preset or filters will be lost! Please use the Cancel button to go back to preset settings to save any changes before proceeding. To ensure the proper template application the page needs to reload twice. Apply filter template and reload this page?', 'annasta-filters' ),
            'apply_preset_template_confirmation' => esc_html__( 'The settings and filters of the current preset will be changed to reflect the chosen template. To ensure the proper template application the page needs to reload twice. Apply preset clone or template and reload this page?', 'annasta-filters' ),
            'preset_cloning_error' => esc_html__( 'An error has occured during preset cloning. Please contact the plugin development team.', 'annasta-filters' ),
            'wrappers_detection_btn_label' => esc_html__( 'Wrapper auto-detection', 'annasta-filters' )
          ),
        ) );
      }
    }
    
    public function ajax_controller() {
      
      $nonce_check = check_ajax_referer( 'awf_ajax_nonce', 'awf_ajax_referer', false );
      if( empty( $nonce_check ) ) {
        wp_send_json_error( array( 'awf_error_message' => __( 'Your session has expired. Please reload the page and try again.', 'annasta-filters' ) ), 403 );
      }
      
      if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( array( 'awf_error_message' => __( 'Error: permission denied.', 'annasta-filters' ) ), 403 );
      }

      if( 'regenerate_customizer_css' === $_POST['awf_action'] ) {
        echo $this->generate_customizer_css( $_POST['awf_customizer_settings'] );
				
			} elseif( 'update_presets_positions' === $_POST['awf_action'] ) {
        if( $this instanceof A_W_F_premium_admin ) { $this->update_presets_positions(); }
				
			} elseif( 'toggle-filters-button-fixed-position' === $_POST['awf_action'] ) {
        $this->toggle_filters_button_fixed_position( boolval( $_POST['awf_button_is_fixed'] ) );
        
      } elseif( 'add_product_list_template_option' === $_POST['awf_action'] ) {
        $this->add_product_list_template_option();
        
      } elseif( 'update_product_list_template_option' === $_POST['awf_action'] ) {
        $this->update_product_list_template_option();
        
      } elseif( 'delete_product_list_template_option' === $_POST['awf_action'] ) {
        $this->delete_product_list_template_option();
        
      } elseif( 'update_seo_filters_positions' === $_POST['awf_action'] ) {
        if( $this instanceof A_W_F_premium_admin ) { $this->update_seo_filters_positions(); }
        
      } elseif( 'clear_awf_cache' === $_POST['awf_action'] ) {
        $this->clear_awf_cache();
        
      } elseif( 'wrapper_detection' === $_POST['awf_action'] ) {
        $this->detect_products_html_wrapper();
        
      } elseif( 'toggle-global-wrapper' === $_POST['awf_action'] ) {
        if( 'yes' === get_option( 'awf_global_wrapper', 'no' ) ) {
          update_option( 'awf_global_wrapper', 'no' );
        } else {
          update_option( 'awf_global_wrapper', 'yes' );
        }

      } elseif( isset( $_POST['awf_preset'] ) ) {

        $preset_id = (int) $_POST['awf_preset'];
        
        if( ! isset( A_W_F::$presets[$preset_id] ) ) {
          wp_send_json_error( array( 'awf_error_message' => __( 'Error: preset doesn\'t exist', 'annasta-filters' ) ), 400 );
        }

        if( 'add-preset-association' === $_POST['awf_action'] ) {
          $this->add_preset_association( $preset_id );
          
        } else if( 'delete-preset-association' === $_POST['awf_action'] ) {
          $this->delete_preset_association( $preset_id );
          
        } else if( 'build-taxonomy-associations' === $_POST['awf_action'] ) {
          echo $this->build_taxonomy_associations( $preset_id );

        } else if( 'popup-preset-display-mode' === $_POST['awf_action'] ) {
          $this->display_preset_display_mode_popup( $preset_id );

        } else if( 'update-preset-display-mode' === $_POST['awf_action'] ) {
          $this->update_preset_display_mode( $preset_id, sanitize_key( $_POST['awf_display_mode'] ) );

        } else {
          
          if( 'add-filter' === $_POST['awf_action'] ) {
            $filter = $this->add_filter( $preset_id, sanitize_text_field( $_POST['awf_filter'] ) );
            include( A_W_F_PLUGIN_PATH . 'templates/admin/filter.php' );
            
          } elseif( 'update_filters_positions' === $_POST['awf_action'] ) {
            $this->update_positions( $preset_id );
            
          } elseif( isset( $_POST['awf_filter'] ) ) {

            $filter_id = (int) $_POST['awf_filter'];
            
            if( ! isset( A_W_F::$presets[$preset_id]['filters'][$filter_id] ) ) {
              wp_send_json_error( array( 'awf_error_message' => __( 'Error: filter doesn\'t exist', 'annasta-filters' ) ), 400 );
            }
            
            $filter = new A_W_F_filter( $preset_id, $filter_id );

            if( 'delete-filter' === $_POST['awf_action'] ) {
              echo $this->delete_filter( $filter );

            } elseif( 'rebuild-styles' === $_POST['awf_action'] ) {
              echo $this->build_style_options( $filter, sanitize_key( $_POST['awf_filter_type'] ) );

            } else if( 'rebuild-style-options' === $_POST['awf_action'] ) {
              echo $this->get_style_options_html( $filter, sanitize_key( $_POST['awf_filter_style'] ) );

            } else if( 'rebuild-range-type-options' === $_POST['awf_action'] ) {
              $filter->settings['type'] = 'range';
              $filter->settings['type_options']['range_type'] = isset( $_POST['awf_filter_range_type'] ) ? sanitize_key( $_POST['awf_filter_range_type'] ) : '';
      
              $this->display_range_type( $filter );
              
            } else if( 'add-custom-range-value' === $_POST['awf_action'] ) {
              $this->add_custom_range_value( $filter, floatval( str_replace( array( wc_get_price_thousand_separator(), wc_get_price_decimal_separator() ), array( '', '.' ), $_POST['awf_new_range_value'] ) ) );

            } else if( 'delete-custom-range-value' === $_POST['awf_action'] ) {
              $this->delete_custom_range_value( $filter, floatval( str_replace( array( wc_get_price_thousand_separator(), wc_get_price_decimal_separator() ), array( '', '.' ), $_POST['awf_delete_range_value'] ) ) );

            } else if( 'update-terms-limitation-mode' === $_POST['awf_action'] ) {
              $this->update_filter_terms_limitation_mode( $filter, sanitize_key( $_POST['awf_terms_limitation_mode'] ) );
              echo $this->build_terms_limitations( $filter );

            } else if( 'add-terms-limitation' === $_POST['awf_action'] ) {
              $this->add_filter_terms_limitation( $filter, intval( $_POST['awf_add_terms_limitation'] ) );
              echo $this->build_terms_limitations( $filter );

            } else if( 'remove-terms-limitation' === $_POST['awf_action'] ) {
              $this->remove_filter_terms_limitation( $filter, intval( $_POST['awf_remove_terms_limitation'] ) );
              echo $this->build_terms_limitations( $filter );
              
            } else if( 'add-ppp-value' === $_POST['awf_action'] ) {
              $this->add_ppp_value( $filter );

            } else if( 'remove-ppp-value' === $_POST['awf_action'] ) {
              $this->remove_ppp_value( $filter );
        
            } else {
              if( $this instanceof A_W_F_premium_admin ) { $this->premium_ajax_controller( $filter ); }
            }
              
          }
        }
      } else {
        if( $this instanceof A_W_F_premium_admin ) { $this->premium_ajax_controller(); }
      }

      wp_die();
    }
    
    private function add_product_list_template_option() {

      $new_option = sanitize_key( $_POST['awf_template_option'] );
      $default_options = $this->get_product_list_template_options();

      if( isset( $default_options[$new_option] ) ) {

        $template_options = get_option( 'awf_product_list_template_options', array() );

        $template_options[$new_option][] = array();
        end( $template_options[$new_option] );
        $id = key( $template_options[$new_option] );

        $template_options[$new_option][$id]['hook'] = 'woocommerce_before_shop_loop';
        $template_options[$new_option][$id]['priority'] = (int) 15;

        if( 'awf_preset' === $new_option ) {
          $presets = $this->get_presets_names();
          $presets = array_keys( $presets );

          $template_options[$new_option][$id]['preset'] = (int) array_shift( $presets );
        }

        update_option( 'awf_product_list_template_options', $template_options );

      }

      $this->display_product_list_settings_template_options();

    }
    
    private function update_product_list_template_option() {

      $option = sanitize_key( $_POST['awf_template_option'] );
      $option_id = intval( $_POST['awf_template_option_id'] );
      $option_hook = sanitize_key( $_POST['awf_template_option_hook'] );
      $option_priority = intval( $_POST['awf_template_option_priority'] );

      $template_options = get_option( 'awf_product_list_template_options', array() );

      if( isset( $template_options[$option][$option_id] ) ) {

        $hooks = $this->get_product_list_template_option_hooks( $option );
        if( isset( $hooks[$option_hook] ) ) {
          $template_options[$option][$option_id]['hook'] = $option_hook;
        }
        
        $template_options[$option][$option_id]['priority'] = $option_priority;

        if( isset( $template_options[$option][$option_id]['preset'] ) && ! empty( $_POST['awf_template_option_extra'] ) ) {
          $template_options[$option][$option_id]['preset'] = intval( $_POST['awf_template_option_extra'] );
        }
      }

      update_option( 'awf_product_list_template_options', $template_options );
    }
    
    private function delete_product_list_template_option() {

      $option = sanitize_key( $_POST['awf_template_option'] );
      $setting_id = (int) $_POST['awf_template_setting_id'];
      $template_options = get_option( 'awf_product_list_template_options', array() );

      if( isset( $template_options[$option] ) && isset( $template_options[$option][$setting_id] ) ) {
        $template_options[$option] = array_diff_key( $template_options[$option], array( $setting_id => array() ) );
        array_filter( $template_options ); /* clean up the empty values */

        update_option( 'awf_product_list_template_options', $template_options );
      }

      $this->display_product_list_settings_template_options();

    }
    
    private function add_preset_association( $preset_id ) {

      $association_id = sanitize_text_field( $_POST['awf_association'] );

      if( in_array( $association_id, A_W_F::$presets[$preset_id]['associations'] ) ) {
        wp_send_json_error( array( 'awf_error_message' => __( 'Error completing request: association already exists.', 'annasta-filters' ) ), 400 );
        
      } else {
        
        if( 'all' === $association_id ) {
          A_W_F::$presets[$preset_id]['associations'] = array( 'all' );
          
        } else {
          
          if( false !== ( $key = array_search( 'all', A_W_F::$presets[$preset_id]['associations'] ) ) ) {
            unset( A_W_F::$presets[$preset_id]['associations'][$key] );
          }
          
          $taxonomies = get_object_taxonomies( 'product', 'names' );
          $taxonomies = array_diff( $taxonomies, A_W_F::$excluded_taxonomies );
          
          if( 'shop-pages' === $association_id ) {
            foreach( A_W_F::$presets[$preset_id]['associations'] as $i => $association ) {
              $association_data = explode( '--', $association );
              if( isset( $association_data[1] ) && 'archive-pages' === $association_data[1] ) { continue; }
              if( isset( $association_data[2] ) && 'archive-page' === $association_data[2] ) { continue; }
              
              if( in_array( $association_data[0], $taxonomies ) ) { unset( A_W_F::$presets[$preset_id]['associations'][$i] ); }
            }
            
          } else {
            $all_associations = $this->get_all_associations();
            if( ! isset( $all_associations[$association_id] ) ) {
              wp_send_json_error( array( 'awf_error_message' => __( 'Request couldn\'t be completed: invalid association id.', 'annasta-filters' ) ), 400 );
            }
            
            $new_association_data = explode( '--', $association_id );
            
            if( in_array( $new_association_data[0], $taxonomies ) ) {
              
              if( isset( $new_association_data[2] ) ) {
                
                switch( $new_association_data[2] ) {
                  case 'shop-page':
                    foreach( A_W_F::$presets[$preset_id]['associations'] as $i => $association ) {
                      if( in_array( $association, array( 'all', 'shop-pages', $new_association_data[0] . '--shop-pages' ) ) ) {
                        unset( A_W_F::$presets[$preset_id]['associations'][$i] );
                      }
                    }
                    break;
                    
                  case 'archive-page':
                    foreach( A_W_F::$presets[$preset_id]['associations'] as $i => $association ) {
                      if( in_array( $association, array( 'all', 'archive-pages', $new_association_data[0] . '--archive-pages' ) ) ) {
                        unset( A_W_F::$presets[$preset_id]['associations'][$i] );
                      }
                    }
                    break;
                    
                  default: break;
                }
                
              } elseif( isset( $new_association_data[1] ) ) {
                
                switch( $new_association_data[1] ) {
                  case 'shop-pages':
                    foreach( A_W_F::$presets[$preset_id]['associations'] as $i => $association ) {
                      $association_data = explode( '--', $association );

                      if( $new_association_data[0] === $association_data[0] || in_array( $association, array( 'all', 'shop-pages' ) ) ) {
                        if( isset( $association_data[2] ) ) {
                          if( 'archive-page' === $association_data[2] ) { continue; }
                        } else {
                          if( isset( $association_data[1] ) && 'archive-pages' === $association_data[1] ) { continue; }
                        }

                        unset( A_W_F::$presets[$preset_id]['associations'][$i] );
                      }
                    }
                    break;
                    
                  case 'archive-pages':
                    foreach( A_W_F::$presets[$preset_id]['associations'] as $i => $association ) {
                      $association_data = explode( '--', $association );

                      if( $new_association_data[0] === $association_data[0] || in_array( $association, array( 'all' ) ) ) {
                        if( isset( $association_data[2] ) ) {
                          if( 'shop-page' === $association_data[2] ) { continue; }
                        } else {
                          if( isset( $association_data[1] ) && 'shop-pages' === $association_data[1] ) { continue; }
                        }

                        unset( A_W_F::$presets[$preset_id]['associations'][$i] );
                      }
                    }
                    break;
                    
                  default: break;
                }
                
              }

            }
          }
          
          A_W_F::$presets[$preset_id]['associations'][] = $association_id;
        }
        
        A_W_F::$presets[$preset_id]['associations'] = array_values( A_W_F::$presets[$preset_id]['associations'] );
        update_option( 'awf_presets', A_W_F::$presets );

        $this->display_associations( $preset_id );
      }
    }

    private function delete_preset_association( $preset_id ) {

      $association_id = sanitize_text_field( $_POST['awf_association'] );

      if ( false !== ( $key = array_search( $association_id, A_W_F::$presets[$preset_id]['associations'] ) ) ) {
        unset( A_W_F::$presets[$preset_id]['associations'][$key] );
        update_option( 'awf_presets', A_W_F::$presets );
        
        $this->display_associations( $preset_id );
        
      } else {
        wp_send_json_error( array( 'awf_error_message' => __( 'Request couldn\'t be completed: wrong preset or association.', 'annasta-filters' ) ), 400 );
      }
    }

    public function add_filter( $preset_id, $filter_name ) {

      $filter_data = $this->build_new_filter_data( $filter_name );

      if( empty( $filter_data ) || ! in_array( $filter_data['module'], A_W_F::$modules ) || ! isset( A_W_F::$presets[$preset_id] ) ) {
        wp_send_json_error( array( 'awf_error_message' => __( 'Error creating filter: invalid preset, filter, or taxonomy.', 'annasta-filters' ) ), 400 );
      }

      A_W_F::$presets[$preset_id]['filters'][] = count( A_W_F::$presets[$preset_id]['filters'] );
      end( A_W_F::$presets[$preset_id]['filters'] );
      $new_filter_id = key( A_W_F::$presets[$preset_id]['filters'] );
      
      update_option( 'awf_presets', A_W_F::$presets );

      $prefix = A_W_F_filter::get_prefix( $preset_id, $new_filter_id );
      $settings = $this->get_module_defaults( $filter_data );

      update_option( $prefix . 'name', $filter_name );
      update_option( $prefix . 'module', $filter_data['module'] );
      update_option( $prefix . 'settings', $settings );

      return new A_W_F_filter( $preset_id, $new_filter_id );
    }

    protected function build_new_filter_data( $filter_name ) {
      $filter_data = array();
      $all_filters = A_W_F::$admin->get_all_filters();

      if( isset( $all_filters[$filter_name] ) ) {
        if( 0 === strpos( $filter_name, 'taxonomy--' ) ) {
          $filter_name_data = explode( '--', $filter_name );
          $taxonomy = array_pop( $filter_name_data );
          $filter_data['module'] = 'taxonomy';
          $filter_data['taxonomy'] = get_taxonomy( $taxonomy );

          if( empty( $filter_data['taxonomy'] ) ) {
            return array();
          }

        } else {
          $filter_data['module'] = $filter_name;
          $filter_data['title'] = $this->get_filter_title( $filter_name );
        }
      }

      return $filter_data;
    }

    public function delete_filter( $filter ) {
      $ajax_response = array();

      if( wp_doing_ajax() ) {
        if( isset( A_W_F::$presets[$filter->preset_id]['filters'][$filter->id] ) ) {

          $ajax_response['option_value'] = esc_attr( $filter->name );

          if(  'taxonomy' === $filter->module ) {
            if( $taxonomy = get_taxonomy( $filter->settings['taxonomy'] ) ) {
              $ajax_response['option_label'] = esc_html( $taxonomy->label );
            }

            if( ! empty( $filter->settings['show_count'] ) || ( isset( $filter->settings['hide_empty'] ) && 'none' !== $filter->settings['hide_empty'] ) ) {
              $this->clear_product_counts_cache();
            }

          } else {
            $ajax_response['option_label'] = esc_html( $this->get_filter_title( $ajax_response['option_value'] ) );
          }

          unset( A_W_F::$presets[$filter->preset_id]['filters'][$filter->id] );

          if( ! empty( A_W_F::$presets[$filter->preset_id]['filters'] ) ) {
            asort( A_W_F::$presets[$filter->preset_id]['filters'], SORT_NUMERIC );
            A_W_F::$presets[$filter->preset_id]['filters'] = array_flip( A_W_F::$presets[$filter->preset_id]['filters'] );
            A_W_F::$presets[$filter->preset_id]['filters'] = array_values( A_W_F::$presets[$filter->preset_id]['filters'] );
            A_W_F::$presets[$filter->preset_id]['filters'] = array_flip( A_W_F::$presets[$filter->preset_id]['filters'] );
          }

          update_option( 'awf_presets', A_W_F::$presets );
          
          if( 'meta' === $filter->module ) {
            A_W_F::build_query_vars();
            $ajax_response = array();
          }

        } else {
          wp_send_json_error( array( 'awf_error_message' => sprintf( __( 'Error: a problem occured while deleting filter %1$s.', 'annasta-filters' ), $filter->preset_id . '-' . $filter->id ) ), 400 );
        }
      }

      delete_option( $filter->prefix . 'name' );
      delete_option( $filter->prefix . 'module' );
      delete_option( $filter->prefix . 'settings' );

      return json_encode( $ajax_response );
    }

    private function update_positions( $preset_id ) {

      $positions = isset( $_POST['awf_filters_positions'] ) && is_array( $_POST['awf_filters_positions'] ) ?  array_map( 'intval', $_POST['awf_filters_positions'] ) : array();

      $filters = array();
      foreach( $positions as $position => $filter_id ) {
        $filters[$filter_id] = (int) $position;
      }

      $check_ids = array_diff( $filters, A_W_F::$presets[$preset_id]['filters'] );

      if( count( $check_ids ) === 0 ) {

        A_W_F::$presets[$preset_id]['filters'] = $filters;
        update_option( 'awf_presets', A_W_F::$presets );

      } else {
        wp_send_json_error( array( 'awf_error_message' => __( 'An error occured when updating filters\' positions.', 'annasta-filters' ) ), 400 );
      }

    }
      
    public function update_filter( $filter ) {
      $response = array();

      if( ! isset( $_POST[$filter->prefix . 'title'] ) ) {
        return $response;
      }

      $old_settings = $filter->settings;
      $filter->settings['style_options'] = array();

      foreach( $filter->settings as $setting => $value ) {
        if( is_null( $value ) ) { continue; }

        switch( $setting ) {
          case 'title':
          case 'active_prefix':
          case 'reset_active_label':
          case 'placeholder':
          case 'show_search_placeholder':
            $filter->settings[$setting] = $this->get_sanitized_text_field_setting( $filter->prefix . $setting );
            break;
          case 'type':
          case 'style':
          case 'meta_name':
            if( isset( $_POST[$filter->prefix . $setting] ) ) {
              $filter->settings[$setting] = sanitize_key( $_POST[$filter->prefix . $setting] );
            }
            break;
          case 'show_title':
          case 'button_submission':
          case 'submit_on_change':
          case 'show_active':
          case 'block_deselection':
          case 'reset_all':
          case 'force_reload':
          case 'reset_active':
          case 'redirect_to_archive':
          case 'is_collapsible':
          case 'collapsed_on':
          case 'display_children':
          case 'children_collapsible':
          case 'children_collapsible_on':
          case 'hierarchical_sbs':
          case 'show_search':
          case 'shrink_height_limit':
          case 'autocomplete':
          case 'show_in_row':
            $filter->settings[$setting] = $this->get_sanitized_checkbox_setting( $filter, $setting );
            break;
          case 'show_count':
            $filter->settings[$setting] = $this->get_sanitized_checkbox_setting( $filter, $setting );
            if( $filter->settings[$setting] !== $value ) { $response['clear_counts_cache'] = true; }
            break;
          case 'hide_empty':
            $filter->settings[$setting] = sanitize_key( $_POST[$filter->prefix . $setting] );
            if( $old_settings[$setting] !== $filter->settings[$setting] ) { $response['clear_counts_cache'] = true; }
            break;
          case 'hierarchical_level':
            if( isset( $_POST[$filter->prefix . $setting] ) ) { $filter->settings[$setting] = (int) $_POST[$filter->prefix . $setting]; }
            break;
          case 'height_limit':
            $filter->settings[$setting] = (int) $_POST[$filter->prefix . $setting];
            break;
          case 'sort_by':
          case 'sort_order':
            $filter->settings[$setting] = sanitize_key( $_POST[$filter->prefix . $setting] );
            break;
          case 'hide_filter':
            $filter->settings[$setting] = empty( $_POST[$filter->prefix . $setting] ) ? '' : sanitize_key( $_POST[$filter->prefix . $setting] );
            break;
          default: break;
        }
      }

      if( ! empty( $filter->settings['terms_limitation_mode'] ) && 'active' === $filter->settings['terms_limitation_mode'] ) {
        $filter->settings['style_options']['display_active_filter_siblings'] = $this->get_sanitized_checkbox_setting( $filter, 'display_active_filter_siblings' );
        $filter->settings['style_options']['hide_active_filter_parents'] = $this->get_sanitized_checkbox_setting( $filter, 'hide_active_filter_parents' );
        $filter->settings['style_options']['active_filter_level_up'] = $this->get_sanitized_checkbox_setting( $filter, 'active_filter_level_up' );
        $filter->settings['style_options']['active_filter_level_up_tip'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'active_filter_level_up_tip' );
      }

      $filter->settings['style_options']['submit_button_label'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'submit_button_label' );

      if( 'search' === $filter->module && ! empty( $filter->settings['autocomplete'] ) ) {
        $filter->settings['type_options']['autocomplete_filtered'] = $this->get_sanitized_checkbox_setting( $filter, 'autocomplete_filtered' );
        $filter->settings['type_options']['ac_display_product_cat'] = $this->get_sanitized_checkbox_setting( $filter, 'ac_display_product_cat' );
        $filter->settings['type_options']['ac_product_cat_header'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'ac_product_cat_header' );
        $filter->settings['type_options']['ac_display_product_tag'] = $this->get_sanitized_checkbox_setting( $filter, 'ac_display_product_tag' );
        $filter->settings['type_options']['ac_product_tag_header'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'ac_product_tag_header' );
        $filter->settings['type_options']['ac_products_header'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'ac_products_header' );
        $filter->settings['type_options']['autocomplete_show_img'] = $this->get_sanitized_checkbox_setting( $filter, 'autocomplete_show_img' );
        $filter->settings['type_options']['autocomplete_show_price'] = $this->get_sanitized_checkbox_setting( $filter, 'autocomplete_show_price' );
        $filter->settings['type_options']['autocomplete_view_all'] = $this->get_sanitized_checkbox_setting( $filter, 'autocomplete_view_all' );
        $filter->settings['type_options']['autocomplete_after'] = $this->get_sanitized_int_setting( $filter->prefix . 'autocomplete_after', 2 );

        $filter->settings['type_options']['autocomplete_results_count'] = $this->get_sanitized_int_setting( $filter->prefix . 'autocomplete_results_count', 5 );
        if( $filter->settings['type_options']['autocomplete_results_count'] > 25 ) {
          $filter->settings['type_options']['autocomplete_results_count'] = 25;
        } elseif( empty( $filter->settings['type_options']['autocomplete_results_count'] ) ) {
          $filter->settings['type_options']['autocomplete_results_count'] = 5;
        }
      }

      if( 'range' === $filter->settings['type'] ) {
        $filter->settings['type_options']['range_type'] = sanitize_key( $_POST[$filter->prefix . 'range_type'] );
        
        if( 'auto_range' === $filter->settings['type_options']['range_type']
           || 'custom_range' === $filter->settings['type_options']['range_type'] )
        {
          $filter->settings['type_options']['precision'] = round( floatval( $_POST[$filter->prefix . 'precision'] ), 2, PHP_ROUND_HALF_UP );
          $filter->settings['type_options']['decimals'] = absint( $_POST[$filter->prefix . 'decimals'] );
          if( 2 < $filter->settings['type_options']['decimals'] ) { $filter->settings['type_options']['decimals'] = 2; }
          
          if( isset( $_POST[$filter->prefix . 'value_prefix'] ) ) {
            $_POST[$filter->prefix . 'value_prefix'] = $this->convert_edge_spaces_to_nbsp( $_POST[$filter->prefix . 'value_prefix'] );
          }
          $filter->settings['style_options']['value_prefix'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'value_prefix' );
          
          if( isset( $_POST[$filter->prefix . 'value_postfix'] ) ) {
            $_POST[$filter->prefix . 'value_postfix'] = $this->convert_edge_spaces_to_nbsp( $_POST[$filter->prefix . 'value_postfix'] );
          }          
          $filter->settings['style_options']['value_postfix'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'value_postfix' );
        }

        if( 'auto_range' === $filter->settings['type_options']['range_type'] ) {

          $range_min = round( floatval( $_POST[$filter->prefix . 'range_min'] ), 2, PHP_ROUND_HALF_UP );
          $range_max = round( floatval( $_POST[$filter->prefix . 'range_max'] ), 2, PHP_ROUND_HALF_UP );

          if( $range_min === $range_max ) { $range_max += 1; }
          elseif( $range_min > $range_max ) {
            $temp = $range_max;
            $range_max = $range_min;
            $range_min = $temp;
          }

          $range_segments = (int) $_POST[$filter->prefix . 'range_segments'];
          if( $range_segments < 1 ) { $range_segments = 1; }
          
          $increment = ( $range_max - $range_min ) / $range_segments;
          $increment = round( $increment, 2, PHP_ROUND_HALF_UP );

          $filter->settings['type_options']['range_values'] = array();

          for( $v = $range_min; $v < $range_max; $v += $increment ) {
            $filter->settings['type_options']['range_values'][] = round( $v, 2, PHP_ROUND_HALF_UP );
          }
          
          while( count( $filter->settings['type_options']['range_values'] ) > $range_segments ) {
            array_pop( $filter->settings['type_options']['range_values'] );
          }
          
          $filter->settings['type_options']['range_values'][] = $range_max;
          
        } elseif( 'custom_range' === $filter->settings['type_options']['range_type'] ) {
          $filter->settings['type_options']['range_values'] = $old_settings['type_options']['range_values'];
        }
      }

      if( 'icons' === $filter->settings['style'] ) {
        $filter->settings['style_options']['icons'][] = $this->get_sanitized_text_field_setting( $filter->prefix . 'unselected_icon' );
        $filter->settings['style_options']['solid'][] = $this->get_sanitized_checkbox_setting( $filter, 'unselected_icon_solid' ) ? 'awf-solid' : '';
        $filter->settings['style_options']['icons'][] = $this->get_sanitized_text_field_setting( $filter->prefix . 'unselected_icon_hover' );
        $filter->settings['style_options']['solid'][] = $this->get_sanitized_checkbox_setting( $filter, 'unselected_icon_hover_solid' ) ? 'awf-solid' : '';
        $filter->settings['style_options']['icons'][] = $this->get_sanitized_text_field_setting( $filter->prefix . 'selected_icon' );
        $filter->settings['style_options']['solid'][] = $this->get_sanitized_checkbox_setting( $filter, 'selected_icon_solid' ) ? 'awf-solid' : '';
        $filter->settings['style_options']['icons'][] = $this->get_sanitized_text_field_setting( $filter->prefix . 'selected_icon_hover' );
        $filter->settings['style_options']['solid'][] = $this->get_sanitized_checkbox_setting( $filter, 'selected_icon_hover_solid' ) ? 'awf-solid' : '';

      } elseif( 'range-slider' === $filter->settings['style'] ) {
        
        if( isset( $old_settings->settings['children_collapsible'] ) ) { $filter->settings['children_collapsible'] = $filter->settings['children_collapsible_on'] = false; }
        if( isset( $old_settings->settings['show_in_row'] ) ) { $filter->settings['show_in_row'] = false; }
        if( isset( $old_settings->settings['show_search'] ) ) { $filter->settings['show_search'] = false; }
        
        $filter->settings['height_limit'] = (int) 0;
        
        if( 'auto_range' === $filter->settings['type_options']['range_type']
           || 'custom_range' === $filter->settings['type_options']['range_type'] )
        {
          $filter->settings['style_options']['step'] = empty( $_POST[$filter->prefix . 'step'] ) ? floatval( 1 ) : (float) $_POST[$filter->prefix . 'step'];
          $filter->settings['style_options']['step'] = abs( $filter->settings['style_options']['step'] );
          $filter->settings['style_options']['slider_tooltips'] = empty( $_POST[$filter->prefix . 'slider_tooltips'] ) ? 'above_handles' : sanitize_key( $_POST[$filter->prefix . 'slider_tooltips'] );
        }

      } elseif( 'daterangepicker' === $filter->settings['style'] ) {

        $filter->settings['button_submission'] = false;

        $filter->settings['style_options']['date_picker_type'] = empty( $_POST[$filter->prefix . 'date_picker_type'] ) ? 'single' : sanitize_key( $_POST[$filter->prefix . 'date_picker_type'] );
        $filter->settings['style_options']['db_date_format'] = empty( $_POST[$filter->prefix . 'db_date_format'] ) ? 'c' : sanitize_key( $_POST[$filter->prefix . 'db_date_format'] );
        $filter->settings['style_options']['daterangepicker_placeholder'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'daterangepicker_placeholder' );

      } elseif( 'colours' === $filter->settings['style'] ) {

        if( ! isset( $_POST[$filter->prefix . 'show_label'] ) ) { $filter->settings['style_options']['hide_label'] = true; }
        $filter_terms = $filter->get_filter_terms( false );

        $filter->settings['style_options']['colours'] = array();

        foreach( $filter_terms as $mt ) {
          if( isset( $_POST[$filter->prefix . 'term_' . $mt->term_id . '_colour'] ) ) {
            $filter->settings['style_options']['colours'][$mt->term_id] = $this->get_sanitized_text_field_setting( $filter->prefix . 'term_' . $mt->term_id . '_colour' );
          }
        }
      }

      if( $this instanceof A_W_F_premium_admin ) {
        $this->update_premium_filter( $filter, $old_settings, $response );
        
      } else {
        if( ! empty( $filter->settings['is_collapsible'] ) ) {
          $filter->settings['show_title'] = true;
        }
      }

      update_option( $filter->prefix. 'settings', $filter->settings );

      return( $response );
    }

    protected function add_custom_range_value( $filter, $new_value ) {

      if( 'custom_range' !== $filter->settings['type_options']['range_type'] ) {
        wp_send_json_error( array( 'awf_warning_message' => __( 'Please save preset before adding or deleting values of the range.', 'annasta-filters' ) ), 400 );
      }

      if( isset( $filter->settings['type_options']['range_values'] ) && ! in_array( $new_value, $filter->settings['type_options']['range_values'] ) ) {
        $filter->settings['type_options']['range_values'][] = round( $new_value, 2, PHP_ROUND_HALF_UP );
        asort( $filter->settings['type_options']['range_values'], SORT_NUMERIC );
        $filter->settings['type_options']['range_values'] = array_values( $filter->settings['type_options']['range_values'] );

        update_option( $filter->prefix. 'settings', $filter->settings );
      }

      $this->display_range_type( $filter );
    }

    protected function delete_custom_range_value( $filter, $value ) {

      if( ! isset( $filter->settings['type_options']['range_values'] ) || count( $filter->settings['type_options']['range_values'] ) < 3  ) {
        wp_send_json_error( array( 'awf_warning_message' => __( 'This range value can not be deleted, because a range needs at least 2 values to work. If you want to change this value, first add the new value, and then delete the unneeded one.', 'annasta-filters' ) ), 400 );
      }

      if( 'custom_range' !== $filter->settings['type_options']['range_type'] ) {
        wp_send_json_error( array( 'awf_warning_message' => __( 'Please save preset before adding or deleting values of the range.', 'annasta-filters' ) ), 400 );
      }

      if( false !== ( $key = array_search( $value, $filter->settings['type_options']['range_values'] ) ) ) {
        
        unset( $filter->settings['type_options']['range_values'][$key] );
        $filter->settings['type_options']['range_values'] = array_values( $filter->settings['type_options']['range_values'] );

        update_option( $filter->prefix. 'settings', $filter->settings );
      }
    }

    protected function update_filter_terms_limitation_mode( &$filter, $new_limitation_mode ) {
      
      if( ! isset( $filter->settings['terms_limitation_mode'] ) ) { return; }

      $filter->settings['terms_limitation_mode'] = $new_limitation_mode;

      switch( $filter->settings['terms_limitation_mode'] ) {
        case 'exclude': break;
        case 'include':
          if( ! isset( $filter->settings['included_items'] ) ) { $filter->settings['included_items'] = array(); }
          break;
        case 'active':
          if( ! isset( $filter->settings['excluded_items'] ) ) { $filter->settings['excluded_items'] = array(); }
          break;
        default:
          $filter->settings['terms_limitation_mode'] = 'exclude';
          break;
      }

      update_option( $filter->prefix. 'settings', $filter->settings );
    }

    public function add_filter_terms_limitation( &$filter, $term_id ) {

      if( empty( $filter->settings['terms_limitation_mode'] ) || ! in_array( $filter->settings['terms_limitation_mode'], array( 'exclude', 'include', 'active' ) ) ) {
        return;
      }

      $all_items = $filter->get_filter_terms( false );
      $all_items_ids = wp_list_pluck( $all_items, 'term_id' );

      $limitations_list = $this->setup_filter_terms_limitation_settings( $filter );

      if( in_array( $term_id, $all_items_ids ) && ! in_array( $term_id, $filter->settings[$limitations_list] ) ) {
        $filter->settings[$limitations_list][] = $term_id;
        update_option( $filter->prefix. 'settings', $filter->settings );
      }
    }

    public function remove_filter_terms_limitation( &$filter, $term_id ) {

      if( empty( $filter->settings['terms_limitation_mode'] ) || ! in_array( $filter->settings['terms_limitation_mode'], array( 'exclude', 'include', 'active' ) ) ) {
        return;
      }

      $limitations_list = $this->setup_filter_terms_limitation_settings( $filter );

      if( in_array( $term_id, $filter->settings[$limitations_list] ) ) {
        $filter->settings[$limitations_list] = array_diff( $filter->settings[$limitations_list], array( $term_id ) );
        $filter->settings[$limitations_list] = array_values( $filter->settings[$limitations_list] );
        update_option( $filter->prefix. 'settings', $filter->settings );
      }
    }
    
    public function add_ppp_value( $filter ) {
      $value = (int) $_POST['awf_add_ppp_value'];
      
      $filter->settings['ppp_values'][$value] = mb_strimwidth( sanitize_text_field( stripslashes( $_POST['awf_add_ppp_label'] ) ), 0, 100, '...' );
      ksort( $filter->settings['ppp_values'] );
      update_option( $filter->prefix. 'settings', $filter->settings );
      
      echo $this->build_ppp_values_list( $filter, intval( get_option( 'awf_ppp_default', 0 ) ) );
    }
  
    public function remove_ppp_value( $filter ) {

      $value = (int) $_POST['awf_remove_ppp_value'];
      
      unset( $filter->settings['ppp_values'][$value] );
      update_option( $filter->prefix. 'settings', $filter->settings );

      echo $this->build_ppp_values_list( $filter, intval( get_option( 'awf_ppp_default', 0 ) ) );
    }
    
    public function build_associations_lists() {

      $associations_by_preset = array();
      foreach( A_W_F::$presets as $preset_id => $preset ) {

        $preset_associations = array();

        foreach( $preset['associations'] as $association_id ) {
          if( $association_id === 'all' ) {
            $preset_associations[] = __( 'All pages', 'annasta-filters' );
            
          } else if( $association_id === 'shop-pages' ) {
            $preset_associations[] = __( 'Shop pages', 'annasta-filters' );
            
          } else if( 0 === strpos( $association_id, 'wp_page--' ) ) {
            $page_id = (int) substr( $association_id, strlen( 'wp_page--' ) );
            $preset_associations[] = get_the_title( $page_id );
            
          } else if( false !== strpos( $association_id, '--' ) ) {

            $association_data = explode( '--', $association_id );
            $association_taxonomy = get_taxonomy( $association_data[0] );
            if( ! is_object( $association_taxonomy ) ) { continue; }
            
            if( 'archive-pages' === $association_data[1] ) {
              $preset_associations[] = ucfirst( sprintf( __( '%s taxonomy archive pages', 'annasta-filters' ), $association_taxonomy->label) );
              
            } elseif( 'shop-pages' === $association_data[1] ) {
              $preset_associations[] = ucfirst( sprintf( __( 'Shop pages with %s filters', 'annasta-filters' ), $association_taxonomy->label) );
              
            } elseif( isset( $association_data[2] ) && in_array( $association_data[2], array( 'shop-page', 'archive-page' ) ) ) {
              $association_term = get_term_by( 'slug', $association_data[1], $association_data[0] );

              if( is_object( $association_term ) ) {
                $preset_associations['taxonomies'][$association_data[2]][$association_taxonomy->name][] = $association_term->name;
              }
            }
            
          }
        }

        if( isset( $preset_associations['taxonomies'] ) ) {
          
          foreach( $preset_associations['taxonomies'] as $page_type => $taxonomies ) {
            foreach( $taxonomies as $tax => $terms ) {
              if( 'shop-page' === $page_type ) {
                $preset_associations[] = ucfirst( sprintf( __( 'shop pages with enabled %s filters', 'annasta-filters' ), implode( ', ', $terms ) ) );

              } elseif( 'archive-page' === $page_type ) {
                $preset_associations[] = ucfirst( sprintf( __( '%1$s archive pages', 'annasta-filters' ), implode( ', ', $terms ) ) );
              }
            }
          }

          unset( $preset_associations['taxonomies'] );
        }

        $associations_by_preset[$preset_id] = implode( ' / ', $preset_associations );
      }

      return $associations_by_preset;
    }
    
    protected function get_all_associations( $include_taxonomies = true ) {
      $all_associations = array(
        'all' => __( 'All pages', 'annasta-filters' ),
        'shop-pages' => __( 'Shop pages', 'annasta-filters' ),
      );

      $prefix = $arrow = '';

      if( ! $include_taxonomies ) {
        $arrow = '»&nbsp;&nbsp;';
        $prefix = 'awf-open--';
      }
      
      $taxonomies = get_object_taxonomies( 'product', 'objects' );
      foreach( $taxonomies as $t ) {
        if( in_array( $t->name, A_W_F::$excluded_taxonomies ) ) { continue; }

        $terms = get_terms( array( 'taxonomy' => $t->name, 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name' ) );
        
        if( $t->public && $t->publicly_queryable ) {
          $all_associations[$prefix . $t->name . '--archive-pages'] = $arrow . sprintf( __( '%s taxonomy archive pages', 'annasta-filters' ), $t->label);
          if( $include_taxonomies ) { $all_associations += $this->build_associations_taxonomy_terms( $terms, 0, true ); }
        }
        
        $all_associations[$prefix . $t->name . '--shop-pages'] = $arrow . sprintf( __( 'Shop pages with enabled %s filters', 'annasta-filters' ), $t->label);
        if( $include_taxonomies ) { $all_associations += $this->build_associations_taxonomy_terms( $terms ); }
      }
      
      $wp_pages = get_all_page_ids();
      $wp_pages = array_diff( $wp_pages, array( wc_get_page_id( 'shop' ) ) );

      foreach( $wp_pages as $page_id ) {
        $all_associations['wp_page--' . $page_id] = __( 'WP page: ', 'annasta-filters' ) . get_the_title( $page_id );
      }
      
      return $all_associations;
    }
    
    public function display_dashboard() {
      ?>

<table id="awf-dashboard-header" class="awf-ts-h awf-ts-1" data-ts="1">
  <thead>
    <tr>
      <th><h3><span><?php esc_html_e( 'annasta Highlights', 'annasta-filters' ) ?></span></h3></th>
    </tr>
  </thead>
</table>

<table id="awf-dashboard-wrapper" class="awf-ts awf-ts-1">
  <tbody>
    <tr>
      <td>
        <div class="awf-dashboard-content">
          <div class="awf-dashboard-column-1 awf-dashboard-slider">
            <div class="awf-dashboard-slide"><div id="awf-dashboard-ajax-mode" class="awf-dashboard-item">
              <i class="fas fa-lightbulb"></i>
              <div>
<?php
$ajax_mode = get_option( 'awf_ajax_mode', 'compatibility_mode' );

if( 'compatibility_mode' === $ajax_mode ) {
  echo sprintf( wp_kses( __( '<div class="awf-dashboard-slide-header">AJAX compatibility mode is on. </div><a href="%1$s">Try the dedicated AJAX mode for faster loads!</a>', 'annasta-filters' ), array( 'div' => array( 'class' => array() ), 'a' => array( 'href' => array() ), 'strong' => array() ) ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=product-list-settings&awf-goto=awf_ajax_mode' ) ) );

} else {
  echo sprintf( wp_kses( __( 'If you ran into AJAX styling issues, try the <strong><a href="%1$s">Enhanced compatibility AJAX mode</a></strong>.', 'annasta-filters' ), array(  'div' => array( 'class' => array() ), 'a' => array( 'href' => array() ), 'strong' => array() ) ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=product-list-settings&awf-goto=awf_ajax_mode' ) ) );
}
?>
              </div>
            </div></div>

<?php if( 'togglable' === get_option( 'awf_preset_1_display_mode' ) ) : ?>

            <div class="awf-dashboard-slide"><div id="awf-dashboard-disable-togglable" class="awf-dashboard-item">
              <i class="fas fa-lightbulb"></i> 
              <div>
<?php
echo sprintf( wp_kses( __( '<div class="awf-dashboard-slide-header">Place your filters into a sidebar or header</div>Learn about disabling the modal sidebar mode in our <a href="%1$s" target="_blank"><strong>Getting Started tutorial</strong></a>.', 'annasta-filters' ), array( 'div' => array( 'class' => array() ), 'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() ) ), esc_url( 'https://annasta.net/plugins/annasta-woocommerce-product-filters/tutorials/getting-started/#display-options' ) );
?>
              </div>
            </div></div>
            
            <div class="awf-dashboard-slide"><div id="awf-dashboard-toggle-btn" class="awf-dashboard-item">
              <i class="fas fa-info-circle"></i> 
              <div>
<?php
echo sprintf( wp_kses( __( '<div class="awf-dashboard-slide-header">Customize the "Filters" button</div><div>Go to annasta Filters > Plugin settings > <a href="%1$s" target="_blank"><strong>"Filters" toggle button settings</strong></a>.</div>', 'annasta-filters' ), array(  'div' => array( 'class' => array() ), 'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() ) ), admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=plugin-settings&awf-expanded-sections=1' ) );
?>
              </div>
            </div></div>
<?php endif; ?>

            <div class="awf-dashboard-slide"><div id="awf-dashboard-customizer" class="awf-dashboard-item">
              <i class="dashicons dashicons-admin-appearance"></i>
              <div>
<?php
echo sprintf( wp_kses( __( 'Use the <strong><a href="%1$s">annasta Filters section of Wordpress Customizer</a></strong> to modify your filters\' appearance.', 'annasta-filters' ), array( 'a' => array( 'href' => array() ), 'strong' => array() ) ), esc_url( admin_url( 'customize.php?autofocus[panel]=annasta-filters' ) ) );
?>
              </div>
            </div></div>

          </div>

          <div class="awf-dashboard-column-2">
            <div id="awf-dashboard-annasta-support" class="awf-dashboard-item">
            <i class="fas fa-book"></i>
            <div>
<?php
echo sprintf( wp_kses( __( '<a href="%1$s" target="_blank">annasta Filters Support page</a>', 'annasta-filters' ), array( 'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() ) ), esc_url( 'https://annasta.net/plugins/annasta-woocommerce-product-filters/support/' ) );
?>
            </div>
          </div>
        </div>
      </td>
    </tr>
    </tbody>
</table>

      <?php
    }
    
    public function display_associations( $preset_id ) {
      $all_associations = $this->get_all_associations();
      $select_associations = $this->get_all_associations( false );
      
      $preset_associations = array_intersect_key( $all_associations, array_flip( A_W_F::$presets[$preset_id]['associations'] ) );
      $associations_select = array_diff_key( $select_associations, $preset_associations );

      include( A_W_F_PLUGIN_PATH . 'templates/admin/preset-associations.php' );
    }
    
    private function build_taxonomy_associations( $preset_id ) {
      $associations = array();
      $options_html = '';

      $request = explode( '--', $_POST['awf_request'] );

      if( 3 !== count( $request ) ) { return ''; }

      $request = array_map( 'sanitize_title', $request );
      $request = array_map( 'urldecode', $request );

      $type = array_pop( $request );
      $taxonomy = array_pop( $request );

      $terms = get_terms( array( 'taxonomy' => $taxonomy, 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name' ) );

      if(
        ! in_array( $type, array( 'archive-pages', 'shop-pages' ) )
        || in_array( $taxonomy, A_W_F::$excluded_taxonomies )
        || is_wp_error( $terms )
        || empty( $terms )
      ) {
        return '';
      }

      $associations[$taxonomy . '--' . $type] = __( 'All', 'annasta-filters' );

      switch( $type ) {
        case 'archive-pages':
          $associations += $this->build_associations_taxonomy_terms( $terms, 0, true );
          break;
        case 'shop-pages':
          $associations += $this->build_associations_taxonomy_terms( $terms );
          break;
        default: break;
      }

      $preset_associations = array_flip( A_W_F::$presets[$preset_id]['associations'] );

      foreach( $associations as $name => $label ) {
        if( isset( $preset_associations[$name] ) ) { continue; }
        $options_html .= '<option value="' . $name . '">' . $label . '</option>';
      }

      return $options_html;
    }
    
    private function build_associations_taxonomy_terms( $terms, $indentation = 0, $archive = false ) {
      $options = array();

      foreach( $terms as $term ) {

        $association_id = $term->taxonomy . '--' . urldecode( $term->slug );
        
        if( $archive ) {
          $association_id .= '--archive-page';
          $options[$association_id] = str_repeat( '»', $indentation ) . ( empty( $indentation) ? '' : '&nbsp;&nbsp;' ) . sprintf( __( '%1$s archive pages', 'annasta-filters' ), $term->name );
          
        } else {
          $association_id .= '--shop-page';
          $options[$association_id] = str_repeat( '»', $indentation ) . ( empty( $indentation) ? '' : '&nbsp;&nbsp;' ) . sprintf( __( 'Shop pages with enabled %s filter', 'annasta-filters' ), $term->name );
        }

        if( is_taxonomy_hierarchical( $term->taxonomy ) ) {
          $child_terms = get_terms( array( 'taxonomy' => $term->taxonomy, 'parent' => $term->term_id, 'hide_empty' => false, 'orderby' => 'name' ) );
          if(! empty( $child_terms ) ) {
            $options += $this->build_associations_taxonomy_terms( $child_terms, $indentation + 1, $archive );
          }
        }
      }

      return $options;
    }

    public function build_type_select( $filter ) {

      $html = '<select name="' . $filter->prefix . 'type" id="' . $filter->prefix . 'type" class="awf-filter-type-select">';

      if( isset( $this->filter_style_limitations[$filter->module] ) ) {
        foreach( $this->filter_style_limitations[$filter->module] as $type => $styles ) {
          $types[$type]['label']= $this->filter_types[$type]['label'];
        }
      } else {
        $types = $this->filter_types;
      }

      foreach( $types as $type => $data ) {
        $html .= '<option value="' . esc_attr( $type ) . '"';
        if( $filter->settings['type'] === $type ) { $html .= ' selected="selected"'; }
        $html .= '>' . esc_html( $data['label'] ) . '</option>';
      }

      $html .= '</select>';

      return $html;
    }

    public function display_range_type( $filter ) {      
      ob_start();
      require( A_W_F_PLUGIN_PATH . 'templates/admin/filter-options/type_options.php' );
      $html = ob_get_clean();
      
      echo $html;
    }

    public function build_range_type_options( $filter ) {
      $html = '';
      $old_settings = get_option( $filter->prefix. 'settings', array() );
      
      if( empty( $filter->settings['type_options']['range_values'] )
         || ( 'range' === $old_settings['type']
             && ( $old_settings['type_options']['range_type'] !== $filter->settings['type_options']['range_type'] && 'taxonomy_range' === $old_settings['type_options']['range_type'] )
            )
      ) {
        if( 'price' === $filter->module || 'rating' === $filter->module ) {
          $defaults = $this->get_module_defaults( array( 'module' => $filter->module, 'taxonomy' => (object) array( 'name' => '', 'label' => '' ), 'title' => '' ) );
          $filter->settings['type_options']['range_values'] = isset( $defaults['type_options']['range_values'] ) ? $defaults['type_options']['range_values'] : array( floatval( 0 ), floatval( 100 ) );
          
        } else {
          $filter->settings['type_options']['range_values'] = array( floatval( 0 ), floatval( 1000 ) );
        }

        update_option( $filter->prefix. 'settings', $filter->settings );
      }

      if( 'auto_range' === $filter->settings['type_options']['range_type'] ) {
        $html .= $this->build_auto_range( $filter );

      } elseif( 'custom_range' === $filter->settings['type_options']['range_type'] ) {
        $html .= $this->build_custom_range( $filter );

      } else {
        if( $this instanceof A_W_F_premium_admin ) { $html .= $this->build_taxonomy_range( $filter ); }
        update_option( $filter->prefix. 'settings', $filter->settings );
        
        return $html;
      }
      
      $html .= '<div class="awf-range-type-advanced"><div class="awf-range-type-options-row">';

      $html .= '<div>';
      $html .= '<label for="' . $filter->prefix . 'precision">' . esc_html__( 'Precision', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This setting controls the creation of radio-buttoned lists of ranges. With the default value of 0 a range with values 0, 10, 20, 30 will give you the following list of ranges: 0-10, 10-20, 20-30, without differences between the end and start values of adjacent range segments. Setting precision to 0.01 will alter the list to 0-9.99, 10-19.99, 20-29.99 etc. The smallest allowed value is 0.01.', 'annasta-filters' ) . '"></span>';
      $html .= '<input id="' . $filter->prefix . 'precision" name="' . $filter->prefix . 'precision" type="text" value="' . esc_attr( isset( $filter->settings['type_options']['precision'] ) ? $filter->settings['type_options']['precision'] : '0' ) . '" style="width: 5em;">';
      $html .= '</div>';

      $html .= '<div>';
      $html .= '<label for="' . $filter->prefix . 'decimals">' . esc_html__( 'Number of decimals', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Define the amount of range values\' decimals (digits to the right of the decimal point). This value controls only the display format of range values, internally they are automatically rounded to 2 decimal points. ', 'annasta-filters' ) . '"></span>';
      $html .= '<input id="' . $filter->prefix . 'decimals" name="' . $filter->prefix . 'decimals" type="text" value="' . esc_attr( isset( $filter->settings['type_options']['decimals'] ) ? $filter->settings['type_options']['decimals'] : '0' ) . '" style="width: 5em;">';
      
      $html .= '</div>';

      $html .= '<div>';
      $html .= '<label for="' . $filter->prefix . 'value_prefix">' . esc_html__( 'Value prefix', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Symbol or word before the value (for currency symbols etc)', 'annasta-filters' ) . '"></span>';
      $html .= '<input id="' . $filter->prefix . 'value_prefix" name="' . $filter->prefix . 'value_prefix" type="text" value="' . esc_attr( empty( $filter->settings['style_options']['value_prefix'] ) ? '' : $filter->settings['style_options']['value_prefix'] ) . '" style="width: 5em;">';
      $html .= '</div>';

      $html .= '<div>';
      $html .= '<label for="' . $filter->prefix . 'value_postfix">' . esc_html__( 'Value postfix', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Symbol or word after the value (for currency symbols etc)', 'annasta-filters' ) . '"></span>';
      $html .= '<input id="' . $filter->prefix . 'value_postfix" name="' . $filter->prefix . 'value_postfix" type="text" value="' . esc_attr( empty( $filter->settings['style_options']['value_postfix'] ) ? '' : $filter->settings['style_options']['value_postfix'] ) . '" style="width: 5em;">';
      
      $html .= '</div></div>';
      
      $html .= '</div>';

      return $html;
    }

    protected function build_auto_range( $filter ) {

      $segments_count = count( $filter->settings['type_options']['range_values'] ) - 1;
      
      $html = '<div>';
      $html .= '<label for="' . $filter->prefix . 'range_min">' . esc_html__( 'Minimum value', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This setting defines the left-most (lowest possible) value of the range control.', 'annasta-filters' ) . '"></span>';
      $html .= '<input name="' . $filter->prefix . 'range_min" id="' . $filter->prefix . 'range_min" type="text" value="' . esc_attr( $filter->settings['type_options']['range_values'][0] ) . '" style="width: 10em;">';
      $html .= '</div><div>';
      $html .= '<label for="' . $filter->prefix . 'range_max">' . esc_html__( 'Maximum value', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This setting defines the right-most (highest possible) value of the range control.', 'annasta-filters' ) . '"></span>';
      $html .= '<input name="' . $filter->prefix . 'range_max" id="' . $filter->prefix . 'range_max" type="text" value="' . esc_attr( $filter->settings['type_options']['range_values'][$segments_count] ) . '" style="width: 10em;">';
      $html .= '</div><div>';
      $html .= '<label for="' . $filter->prefix . 'range_segments">' . esc_html__( 'Range divisions', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Define the amount of range segments. In a range slider this will control the amount of poles with labeled values displayed on the range scale. This setting has to be equal to or greater than 1. WARNING: please don\'t set very small ( less than 0.1 ) differences between the poles, it may result in an uneven segments distribution.', 'annasta-filters' ) . '"></span>';
      $html .= '<input name="' . $filter->prefix . 'range_segments" id="' . $filter->prefix . 'range_segments" type="text" value="' . $segments_count . '" style="width: 5em;">';
      $html .= '</div>';

      return $html;
    }

    protected function build_custom_range( $filter ) {
      $html = '<div>';

      foreach( $filter->settings['type_options']['range_values'] as $i => $value ) {
        $html .= '<div class="awf-custom-range-value-container">';
        $html .= '<button type="button" class="button button-secondary';
        $html .= ' awf-delete-custom-range-value-btn" title="' . esc_attr__( 'Delete value', 'annasta-filters' ) . '"';
        $html .= '>' . esc_html( number_format( $value, 2, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) ) . '</button>';
        $html .= '</div>';
      }

      $html .= '</div>';

      $html .= '<div>';
      $html .= '<input class="awf-new-range-value" type="text" value="" style="width: 10em;">';
      $html .= '<button type="button" class="button button-secondary awf-add-custom-range-value-btn"';
      $html .= ' title="' . esc_attr__( 'Add new value to the range', 'annasta-filters' ) . '">' . esc_html__( 'Add value', 'annasta-filters' );
      $html .= '</button>';
      $html .= '</div>';

      return $html;
    }

    public function build_style_options( $filter, $type = null ) {
      if( is_null( $type ) ) {
        $type = $filter->settings['type'];
      } else { 
        if( ! in_array( $filter->settings['style'], $this->filter_types[$type]['styles'] ) ) { $filter->settings['style'] = null; }
      }

      if( empty( $type ) || ! isset( $this->filter_types[$type] ) ) {
        return;
      } else {
        $filter->settings['type'] = $type;
      }

      if( isset( $this->filter_style_limitations[$filter->module] ) ) {
        $styles = array_intersect( $this->filter_types[$type]['styles'], $this->filter_style_limitations[$filter->module][$type] ) ;
      } else {
        $styles = $this->filter_types[$type]['styles'];
      }
      
      if( 'range' === $type && 'range' === $filter->settings['type'] && isset( $filter->settings['type_options']['range_type'] ) && 'taxonomy_range' === $filter->settings['type_options']['range_type'] ) {
        $styles = array( 'range-slider' );
        $filter->settings['style'] = 'range-slider';
      }

      if( is_null( $filter->settings['style'] ) ) { $filter->settings['style'] = reset( $styles ); }

      $select_html = '<select name="' . $filter->prefix . 'style" id="' . $filter->prefix . 'style" class="awf-filter-style-select">';
      $options_html = '<div id="' . $filter->prefix . 'style_options_container" class="awf-style-options-container">';

      foreach( $styles as $value ) {
        $select_html .= '<option value="' . esc_attr( $value ) . '"';
        if( $filter->settings['style'] === $value ) {
          $select_html .= ' selected="selected"';
          $options_html .= $this->get_style_options_html( $filter, $value );
        }
        $select_html .= '>' . esc_html( $this->filter_styles[$value] );
        $select_html .= '</option>';
      }

      $select_html .= '</select>';
      $select_html .= '<button type="button" title="' . esc_attr__( 'Toggle style options', 'annasta-filters' ) . '" class="button button-secondary awf-icon awf-style-options-btn"></button>';
      $options_html .= '</div>';

      return '<div class="awf-filter-style-select-wrapper">' . $select_html . '</div>' . $options_html;
    }

    private function get_style_options_html( $filter, $style ) {
      $html = '';
      $method = 'build_' . str_replace( '-', '_', $style ) . '_options_html';
      
      if( method_exists( $this, $method ) ) {
        $html .= $this->{$method}( $filter );
      }
      
      return $html;
    }

    public function build_daterangepicker_options_html( $filter ) {
      $db_date_formats = A_W_F::get_db_date_formats();
      $db_date_format_options = array();
      foreach( $db_date_formats as $type => $data ) {
        $db_date_format_options[$type] = $data['label'];
      }
      
      $html = '<div class="awf-daterangepicker-options-container">';
      
      $html .= '<div class="awf-options-row">';
      $html .= '<label for="' . $filter->prefix . 'date_picker_type">' . esc_html__( 'Date picker type', 'annasta-filters' ) . '</label>';
      $html .= A_W_F::$admin->build_select_html( array(
        'name' => $filter->prefix . 'date_picker_type', 
        'id' => $filter->prefix . 'date_picker_type', 
        'class' => 'awf-date-picker-type-select', 
        'options' => array( 'single' => __( 'Single date picker', 'annasta-filters' ), 'range' => __( 'Dates range picker', 'annasta-filters' ) ), 
        'selected' => isset( $filter->settings['style_options']['date_picker_type'] ) ? $filter->settings['style_options']['date_picker_type'] : ''
      ) );
      $html .= '</div>';
      
      $html .= '<div class="awf-options-row">';
      $html .= '<label for="' . $filter->prefix . 'db_date_format">' . esc_html__( 'Database date format', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Select the date format in which date values are stored in the database.', 'annasta-filters' ) . '"></span>';
      $html .= A_W_F::$admin->build_select_html( array(
        'name' => $filter->prefix . 'db_date_format', 
        'id' => $filter->prefix . 'db_date_format', 
        'class' => 'awf-date-format-select', 
        'options' => $db_date_format_options, 
        'selected' => isset( $filter->settings['style_options']['db_date_format'] ) ? $filter->settings['style_options']['db_date_format'] : ''
      ) );
      $html .= '</div>';
      
      $html .= '<div class="awf-options-row">';
      $html .= '<label for="' . $filter->prefix . 'daterangepicker_placeholder">' . esc_html__( 'Placeholder text', 'annasta-filters' ) . '</label>';
      $html .= '<input id="' . $filter->prefix . 'daterangepicker_placeholder" type="text" name="' . $filter->prefix . 'daterangepicker_placeholder" value="' . esc_attr( empty( $filter->settings['style_options']['daterangepicker_placeholder'] ) ? __( 'Select date...', 'annasta-filters' ) : $filter->settings['style_options']['daterangepicker_placeholder'] ) . '">';
      $html .= '</div>';
      
      $html .= '</div>';
      
      return $html;
    }

    public function build_icons_options_html( $filter ) {

      if( isset( $filter->settings['style_options']['icons'] ) ) {
        $icons = $filter->settings['style_options']['icons'];
        $solid_icons_class = $filter->settings['style_options']['solid'];
        $solid_icons = array_map( function( $is_solid ) {
          if( ! empty( $is_solid ) ) {
            return ' checked="checked"';
          }
          return '';
        }, $solid_icons_class );
        
      } else {
        $icons = array( '', '', '', '' );
        $solid_icons_class = array( '', '', 'awf-solid', '' );
        $solid_icons = array( '', '', ' checked="checked"', '' );
      }

      $html = '<div class="awf-icons-options-container">';
      $html .= '<h4>' . esc_html__( 'Set icons', 'annasta-filters' ) . '</h4>';
      $html .= '<table><tbody>';

      $unselected_id = $filter->prefix . 'unselected_icon';
      $html .= '<tr><td>';
      $html .= '<label for="' . $unselected_id . '">' . esc_html__( 'Inactive filter', 'annasta-filters' ) . '</label>';
      $html .= '</td><td>';
      $html .= '<input id="' . $unselected_id . '" type="text" name="' . $unselected_id . '" value="' . esc_attr( $icons[0] ) . '" class="awf-filter-icon awf-unselected-icon ' . sanitize_html_class( $solid_icons_class[0] ) . '">';
      $html .= '</td><td>';
      $html .= '<label><input type="checkbox" name="' . $unselected_id . '_solid" value="yes"' . $solid_icons[0] . ' class="awf-solid-icon">' . esc_html__( 'Solid style', 'annasta-filters' ) . '</label>';
      $html .= '</td></tr>';

      $unselected_hover_id = $filter->prefix . 'unselected_icon_hover';
      $html .= '<tr><td>';
      $html .= '<label for="' . $unselected_hover_id . '">' . esc_html__( 'Inactive filter hover', 'annasta-filters' ) . '</label>';
      $html .= '</td><td>';
      $html .= '<input id="' . $unselected_hover_id . '" type="text" name="' . $unselected_hover_id . '" value="' . esc_attr( $icons[1] ) . '" class="awf-filter-icon awf-unselected-icon-hover ' . sanitize_html_class( $solid_icons_class[1] ) . '">';
      $html .= '</td><td>';
      $html .= '<label><input type="checkbox" name="' . $unselected_hover_id . '_solid" value="yes"' . $solid_icons[1] . ' class="awf-solid-icon">' . esc_html__( 'Solid style', 'annasta-filters' ) . '</label>';
      $html .= '</td></tr>';

      $selected_id = $filter->prefix . 'selected_icon';
      $html .= '<tr><td>';
      $html .= '<label for="' . $selected_id . '">' . esc_html__( 'Active filter', 'annasta-filters' ) . '</label>';
      $html .= '</td><td>';
      $html .= '<input id="' . $selected_id . '" type="text" name="' . $selected_id . '" value="' . esc_attr( $icons[2] ) . '" class="awf-filter-icon awf-selected-icon ' . sanitize_html_class( $solid_icons_class[2] ) . '">';
      $html .= '</td><td>';
      $html .= '<label><input type="checkbox" name="' . $selected_id . '_solid" value="yes"' . $solid_icons[2] . ' class="awf-solid-icon">' . esc_html__( 'Solid style', 'annasta-filters' ) . '</label>';
      $html .= '</td></tr>';

      $selected_hover_id = $filter->prefix . 'selected_icon_hover';
      $html .= '<tr><td>';
      $html .= '<label for="' . $selected_hover_id . '">' . esc_html__( 'Active filter hover', 'annasta-filters' ) . '</label>';
      $html .= '</td><td>';
      $html .= '<input id="' . $selected_hover_id . '" type="text" name="' . $selected_hover_id . '" value="' . esc_attr( $icons[3] ) . '" class="awf-filter-icon awf-selected-icon-hover ' . sanitize_html_class( $solid_icons_class[3] ) . '">';
      $html .= '</td><td>';
      $html .= '<label><input type="checkbox" name="' . $selected_hover_id . '_solid" value="yes"' . $solid_icons[3] . ' class="awf-solid-icon">' . esc_html__( 'Solid style', 'annasta-filters' ) . '</label>';
      $html .= '</td></tr>';

      $html .= '</tbody></table>';
      $html .= '</div>';
      
      $html .= '<div class="awf-icons-preview-container">';
      $html .= '<h4>' . esc_html__( 'Preview', 'annasta-filters' ) . '</h4>';
        
      $preview_terms = $filter->get_limited_terms();

      foreach( $preview_terms as $i => $term ) {
        if( $i === 6 ) { break; }
        $html .= '<label class="';
        if( $i === 2 || ( $i === 5 && 'multi' === $filter->settings['type'] ) ) {
          $html .= 'awf-selected-icon-preview"><span class="awf-filter-icon ' . sanitize_html_class( $solid_icons_class[2] ) . '">' . esc_html( $icons[2] );
        }
        else { $html .= 'awf-unselected-icon-preview"><span class="awf-filter-icon ' . sanitize_html_class( $solid_icons_class[0] ) . '">' . esc_html( $icons[0] ); }

        $html .= '</span>' . esc_html( $term->name ) . '</label>';
      }
      
      $html .= '</div>';

      $html .= '<div class="awf-icons-examples-container" data-tip="' . esc_attr__( 'Copied to clipboard', 'annasta-filters' ) . '">';
      $html .= '<h4>' . esc_html__( 'Click an icon to copy to clipboard, then paste to the chosen box', 'annasta-filters' );
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Some icons are available only in the solid version, so make sure to toggle the \'Solid style\' checkbox if the icon doesn\'t display properly. Go to Fontawesome Icons Gallery for more amazing icons for your shop!', 'annasta-filters' ) . '"></span>' . '</h4>';
      
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example awf-solid" title="' . esc_attr__( 'Solid style only', 'annasta-filters' ) . '"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      
      $html .= '</div>';

      return $html;
    }

    public function build_colours_options_html( $filter ) {
      $terms_by_parent = $filter->build_terms_by_parent( $filter->get_filter_terms() );
      $first_parent = 0;

      $html = '<ul>';
      $html .= '<li>';
      $html .= '<input id="' . $filter->prefix . 'show_label" type="checkbox" name="' . $filter->prefix . 'show_label" value="yes"';
      if( ! isset( $filter->settings['style_options']['hide_label'] ) ) { $html .= ' checked="checked"'; }
      $html .= '>';
      $html .= '<label for="' . $filter->prefix . 'show_label">' . esc_html__( 'Display label', 'annasta-filters' ) . '</label>';
      $html .= '</li>';
      $html .= '</ul>';

      $html .= '<table class="awf-filter-options-secondary-table awf-terms-colours-container"><tbody>';
      $html .= $this->build_terms_colours_list( $filter, $terms_by_parent, $first_parent );
      $html .= '</tbody></table>';

      return $html;
    }

    protected function build_terms_colours_list( $filter, $terms_by_parent, $parent_id = 0 ) {
      $terms_html = '';

      foreach ( $terms_by_parent[$parent_id] as $term ) {
        $terms_html .= '<tr class="awf-term-colour-container">';
        $terms_html .= '<td>' . esc_html( $term->name ) . '</td>';
        $terms_html .= '<td>';
        $terms_html .= '<input type="text" name="' . $filter->prefix . 'term_' . sanitize_html_class( $term->term_id ) . '_colour" value="';
        if( isset( $filter->settings['style_options']['colours'] ) && isset( $filter->settings['style_options']['colours'][$term->term_id] ) ) {
          $terms_html .= esc_attr( $filter->settings['style_options']['colours'][$term->term_id] );
        }
        $terms_html .= '" class="awf-colorpicker" >';
        $terms_html .= '</td>';
        $terms_html .= '</tr>';

        if( isset( $terms_by_parent[$term->term_id] ) ) {
          $terms_html .= $this->build_terms_colours_list( $filter, $terms_by_parent, $term->term_id );
        }
      }

      return $terms_html;
    }

    public function build_range_slider_options_html( $filter ) {
      $html = '<div class="awf-range-slider-options-container">';
      
      if( in_array( $filter->settings['type_options']['range_type'], array( 'auto_range', 'custom_range' ) ) ) {
        $html .= '<div class="awf-range-slider-steps-container">';
        $html .= '<label for="' . $filter->prefix . 'step">' . esc_html__( 'Slider step', 'annasta-filters' ) . '</label>';
        $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This controls the smallest step that a value in a range slider control can jump to.', 'annasta-filters' ) . '"></span>';
        $html .= '<input id="' . $filter->prefix . 'step" name="' . $filter->prefix . 'step" type="text" value="' . esc_attr( empty( $filter->settings['style_options']['step'] ) ? '1' : $filter->settings['style_options']['step'] ) . '" style="width: 5em;">';
        $html .= '</div>';
        
        $html .= '<div class="awf-range-slider-tooltips-container">';
        $html .= '<label for="' . $filter->prefix . 'slider_tooltips">' . esc_html__( 'Tooltips display', 'annasta-filters' ) . '</label>';
        $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This option controls the display of tooltip labels for the current values of range slider.', 'annasta-filters' ) . '"></span>';
        
        $tooltips_options = array( 'none' => __( 'None', 'annasta-filters' ), 'above_handles' => __( 'Above slider handles', 'annasta-filters' ) );
        if( $this instanceof A_W_F_premium_admin ) { $this->add_premium_tooltips_options( $filter, $tooltips_options ); }
        
        $html .= A_W_F::$admin->build_select_html( array(
          'name' => $filter->prefix . 'slider_tooltips',
          'id' => $filter->prefix . 'slider_tooltips',
          'selected' => empty( $filter->settings['style_options']['slider_tooltips'] ) ? 'above_handles' : $filter->settings['style_options']['slider_tooltips'],
          'options' => $tooltips_options
        ) );
        $html .= '</div>';
      }

      if( A_W_F::$premium && 'taxonomy_range' !== $filter->settings['type_options']['range_type'] ) {

        $html .= '<div class="awf-range-slider-hide-slider-labels-container">';
        $html .= '<input type="checkbox" id="' . $filter->prefix . 'hide_slider_labels" name="' . $filter->prefix . 'hide_slider_labels" class="awf-range-slider-hide-slider-labels" value="yes"';
        if( ! empty( $filter->settings['style_options']['hide_slider_labels'] ) ) { $html .= ' checked="checked"'; }
        $html .= '>';
        $html .= '<label for="' . $filter->prefix . 'hide_slider_labels">' . esc_html__( 'Hide value labels', 'annasta-filters' ) . '</label>';
        $html .= '</div>';

        $html .= '<div class="awf-range-slider-hide-slider-poles-container">';
        $html .= '<input type="checkbox" id="' . $filter->prefix . 'hide_slider_poles" name="' . $filter->prefix . 'hide_slider_poles" class="awf-range-slider-hide-slider-poles" value="yes"';
        if( ! empty( $filter->settings['style_options']['hide_slider_poles'] ) ) { $html .= ' checked="checked"'; }
        $html .= '>';
        $html .= '<label for="' . $filter->prefix . 'hide_slider_poles">' . esc_html__( 'Hide poles', 'annasta-filters' ) . '</label>';
        $html .= '</div>';

        $html .= '<div class="awf-range-slider-hide-slider-container">';
        $html .= '<input type="checkbox" id="' . $filter->prefix . 'hide_slider" name="' . $filter->prefix . 'hide_slider" class="awf-range-slider-hide-slider" value="yes"';
        if( ! empty( $filter->settings['style_options']['hide_slider'] ) ) { $html .= ' checked="checked"'; }
        $html .= '>';
        $html .= '<label for="' . $filter->prefix . 'hide_slider">' . esc_html__( 'Hide slider', 'annasta-filters' ) . '</label>';
        $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Hide the slider in cases when you wish to submit range values exclusively through interactive tooltips controls.', 'annasta-filters' ) . '"></span>';
        $html .= '</div>';
      }
      
      $html .= '</div>';

      if( in_array( $filter->settings['type_options']['range_type'], array( 'auto_range', 'custom_range', 'taxonomy_range' ) ) ) {
        $html .= '<button name="save" class="button-primary woocommerce-save-button" type="submit" value="' . esc_attr__( 'Save and refresh range preview', 'annasta-filters' ) . '">' . esc_html__( 'Submit changes to refresh preview', 'annasta-filters' ) . '</button>';
      }
      
      if( ! empty( $filter->settings['type_options']['range_values'] ) && count( $filter->settings['type_options']['range_values'] ) > 1 ) {
        $tooltips = empty( $filter->settings['style_options']['slider_tooltips'] ) ? 'above_handles' : $filter->settings['style_options']['slider_tooltips'];
        
        if( 'interactive_above' === $tooltips && method_exists( $this, 'get_interactive_tooltips_html' ) ) {
          if( $this instanceof A_W_F_premium_admin ) { $html .= $this->get_interactive_tooltips_html(); }
        }
        
        $html .= '<div class="awf-range-slider-preview';
        if( ! empty( $filter->settings['style_options']['hide_slider_labels'] ) ) { $html .= ' awf-rsp-hide-labels';}
        if( ! empty( $filter->settings['style_options']['hide_slider_poles'] ) ) { $html .= ' awf-rsp-hide-poles';}
        if( ! empty( $filter->settings['style_options']['hide_slider'] ) ) { $html .= ' awf-rsp-hide-slider';}
        $html .= '"';
        $html .= ' data-step="' . esc_attr( empty( $filter->settings['style_options']['step'] ) ? '1' : esc_attr( $filter->settings['style_options']['step'] ) ) . '"';
        
        if( 'taxonomy_range' === $filter->settings['type_options']['range_type'] ) {
          $html .= ' data-tooltips="none"';
          $html .= ' data-taxonomy-range="1"';
          $html .= ' data-labels="';
          if( ! empty( $filter->settings['type_options']['range_labels'] ) ) {
            $html .= esc_attr( implode( '_+_', $filter->settings['type_options']['range_labels'] ) );
          }
          $html .= '"';
          $html .= ' data-values="' . esc_attr( implode( '_+_', $filter->settings['type_options']['range_values'] ) ) . '"';
          
        } else {
          $html .= ' data-tooltips="' . esc_attr( $tooltips ) . '"';
          $html .= ' data-prefix="' . esc_attr( empty( $filter->settings['style_options']['value_prefix'] ) ? '' : $filter->settings['style_options']['value_prefix'] ) . '"';
          $html .= ' data-postfix="' . esc_attr( empty( $filter->settings['style_options']['value_postfix'] ) ? '' : $filter->settings['style_options']['value_postfix'] ) . '"';
          $html .= ' data-decimals="' . esc_attr( empty( $filter->settings['type_options']['decimals'] ) ? 0 : $filter->settings['type_options']['decimals'] ) . '"';
          $html .= ' data-decimals-separator="' . esc_attr( wc_get_price_decimal_separator() ) . '"';
          $html .= ' data-thousand-separator="' . esc_attr( wc_get_price_thousand_separator() ) . '"';
          $html .= ' data-values="' . esc_attr( implode( '_+_', $filter->settings['type_options']['range_values'] ) ) . '"';
        }

        $html .= '>';
        $html .= '</div>';
        $html .= '<div class="awf-info-notice">' .
        sprintf( wp_kses( __( 'Customize your sliders appearance in the <strong><a href="%1$s" target="_blank">"annasta Filters" section of WordPress Customizer</a></strong>.', 'annasta-filters' ), array(  'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() ) ), esc_url( admin_url( 'customize.php?autofocus[panel]=annasta-filters' ) ) ) .
        '</div>';
      }

      return $html;
    }

    protected function setup_filter_terms_limitation_settings( &$filter ) {

      $limitations_list =  'excluded_items';

      if( ! empty( $filter->settings['terms_limitation_mode'] ) && 'include' === $filter->settings['terms_limitation_mode'] ) {
        $limitations_list = 'included_items';
      }

      if( ! isset( $filter->settings[$limitations_list] ) ) { $filter->settings[$limitations_list] = array(); }

      return $limitations_list;
    }

    public function build_terms_limitations( $filter ) {

      if( empty( $filter->settings['terms_limitation_mode'] ) || ! in_array( $filter->settings['terms_limitation_mode'], array( 'exclude', 'include', 'active' ) ) ) { return ''; }

      $limitations_list = $this->setup_filter_terms_limitation_settings( $filter );

      $terms = $filter->get_filter_terms();

      if( empty( $terms ) ) return '<div>' . esc_html__( 'This filter has no terms', 'annasta-filters' ) . '</div>';

      $terms_by_id = array();
      foreach( $terms as $term ) {
        $terms_by_id[$term->term_id] = $term;
      }
      
      /* Cleanup current terms limitations array */
      if( ! wp_doing_ajax() ) {
        $delete_terms = array_diff( $filter->settings[$limitations_list], array_keys( $terms_by_id ) );
        if( ! empty( $delete_terms ) ) {
          $filter->settings[$limitations_list] = array_diff( $filter->settings[$limitations_list], $delete_terms );
          update_option( $filter->prefix. 'settings', $filter->settings );
        }
      }
      /* endof Cleanup current terms limitations array */

      $terms_for_select = $filter->build_terms_by_parent( $terms );
      $add_label = $remove_label = '';

      if( 'included_items' === $limitations_list ) {
        $terms_for_select = empty( $terms_for_select[0] ) ? array( 0 => array() ) : array( 0 => $terms_for_select[0] );
        $add_label = __( 'Add to selected', 'annasta-filters' );
        $remove_label = __( 'Remove from selected', 'annasta-filters' );

      } elseif( 'excluded_items' === $limitations_list ) {
        $add_label = __( 'Exclude', 'annasta-filters' );
        $remove_label = __( 'Remove from exclusions', 'annasta-filters' );
      }

      $html = '<table class="awf-filter-options-secondary-table awf-terms-limitations-table awf-' . $filter->settings['terms_limitation_mode'] . '-lm">';

      if( 'active' === $filter->settings['terms_limitation_mode'] ) {
        $html .= '<thead><tr><th colspan="2">';

        if( 'range' === $filter->settings['type'] ) {
          $html .= '<div class="awf-info-notice">' . esc_html__( 'Active filters mode doesn\'t support ranges!', 'annasta-filters' ) . '</div>';

        } else {
          $html .= '<div style="margin:10px 0 25px;">' . esc_html__( 'Limit the filter display to the hierarchical branches of the currently selected terms. Use the additional settings below to further control the lists of filtering options.', 'annasta-filters' ) . '</div>';

          $premium_markup = ' awf-premium-option-container';
          if( A_W_F::$premium ) { $premium_markup = ''; }

          $html .= '<div class="awf-fo-flex2' . $premium_markup . '"><label for="' . $filter->prefix. 'hide_active_filter_parents" class="awf-label">' . __( 'Hide active filter parents', 'annasta-filters' ) . '</label><input type="checkbox" name="' . $filter->prefix. 'hide_active_filter_parents" id="' . $filter->prefix. 'hide_active_filter_parents" value="yes"' . ( ! empty( $filter->settings['style_options']['hide_active_filter_parents'] ) ? ' checked="checked"' : '' ) .'></div>';
          $html .= '<div class="awf-fo-flex2' . $premium_markup . '" style="align-items:center;"><label for="' . $filter->prefix. 'active_filter_level_up" class="awf-label">' . __( 'Enable one level up button', 'annasta-filters' ) . '</label><input type="checkbox" name="' . $filter->prefix. 'active_filter_level_up" id="' . $filter->prefix. 'active_filter_level_up" value="yes"' . ( ! empty( $filter->settings['style_options']['active_filter_level_up'] ) ? ' checked="checked"' : '' ) .'><div style="display:flex;align-items:center;justify-content:flex-end;flex-grow:1;"><label for="' . $filter->prefix. 'active_filter_level_up_tip" class="awf-secondary-label">' . __( 'Level up tip', 'annasta-filters' ) . '</label><input id="' . $filter->prefix . 'active_filter_level_up_tip" name="' . $filter->prefix . 'active_filter_level_up_tip" type="text" value="' . esc_attr( isset( $filter->settings['style_options']['active_filter_level_up_tip'] ) ? $filter->settings['style_options']['active_filter_level_up_tip'] : '' ) . '" style="width:200px;"></div></div>';

          
          $html .= '<div class="awf-fo-flex2"><label for="' . $filter->prefix. 'display_active_filter_siblings" class="awf-label">' . __( 'Display active filters siblings', 'annasta-filters' ) . '</label><input type="checkbox" name="' . $filter->prefix. 'display_active_filter_siblings" id="' . $filter->prefix. 'display_active_filter_siblings" value="yes"' . ( ! empty( $filter->settings['style_options']['display_active_filter_siblings'] ) ? ' checked="checked"' : '' ) .'><span class="awf-secondary-label">' . esc_html__( 'Siblings of an active filter belonging to the last hierarchical level will always be displayed.', 'annasta-filters' ) . '</span></div></br>' .

          '<div class="awf-info-notice">' . '<div>' . esc_html__( 'Active filters list refreshes on page loads. If this renders it incompatible with some AJAX-based options, use the URL filtering style or the "Force page reloads" filter setting.', 'annasta-filters' ) . '</div></div></br>' .
          
          '<h3>' . esc_html__( 'Excluded terms', 'annasta-filters' ) . '</h3>' .

          '<div class="awf-info-notice">' . '<div>' . esc_html__( 'In the Active Filters mode the excluded items list will affect the filter display when none of its options is selected.', 'annasta-filters' ) . '</div></div></br>';
        }
        
        $html .= '</th></tr></thead>';
      }

      $html .= '<tbody>';

      foreach( $filter->settings[$limitations_list] as $ei ) {
        if( isset( $terms_by_id[$ei] ) ) {
          $html .= '<tr id="awf-terms-limitation_' . $filter->preset_id . '_' . $filter->id . '_' . sanitize_html_class( $ei ) . '" class="awf-terms-limitation-container"><td>' . esc_html( $terms_by_id[$ei]->name ) . '</td>';
          $html .= '<td class="awf-terms-limitation-btn-container"><button type="button" class="button button-secondary awf-fa-icon awf-fas-icon awf-fa-delete-btn awf-remove-terms-limitation-btn"';
          $html .= ' title="' . esc_attr( $remove_label ) . '"></button></td>';
          $html .= '</tr>';
        }
      }

      $html .= '</tbody>';

      $select_options = $this->build_terms_limitations_select( $filter->settings[$limitations_list], $terms_for_select );

      if( ! empty( $select_options ) ) {
        $html .= '<tfoot><tr>';
        $html .= '<td>';
        $html .= '<select id="awf-terms-limitations-' . $filter->preset_id . '-' . $filter->id . '">';
        $html .= $select_options;
        $html .= '</select>';
        $html .= '</td>';
        $html .= '<td class="awf-terms-limitation-btn-container">';
        $html .= '<button type="button" class="button button-secondary awf-add-terms-limitation-btn" title="' . esc_attr( $add_label ) . '">' . esc_html( $add_label ) . '</button>';
        $html .= '</td>';
        $html .= '</tr></tfoot>';
      }

      $html .= '</table>';

      return $html;
    }

    protected function build_terms_limitations_select( $limited_terms, $terms_by_parent, $parent_id = 0, $indentation = '' ) {
      $options_html = '';
			
			if( isset( $terms_by_parent[$parent_id] ) ) {
				foreach ( $terms_by_parent[$parent_id] as $term ) {
					if( in_array( $term->term_id, $limited_terms ) ) continue;
					$options_html .= '<option value="' . esc_attr( $term->term_id ) . '">';
					$options_html .= $indentation . esc_html( $term->name );
					$options_html .= '</option>';
					if( isset( $terms_by_parent[$term->term_id] ) ) {
						$options_html .= $this->build_terms_limitations_select( $limited_terms, $terms_by_parent, $term->term_id, $indentation . '&nbsp;&nbsp;' );
					}
				}
			}

      return $options_html;
    }

    public function build_ppp_values_list( $filter, $ppp_default ) {
      $html = '';

      foreach( $filter->settings['ppp_values'] as $value => $label ) {
        $html .= '<tr id="awf_ppp_value_' . $filter->preset_id . '_' . $filter->id . '_' . sanitize_html_class( $value ) .'" class="awf-ppp-value-container">';
        $html .= '<td>';
        if( -1 !== $value ) { $html .= $value; }
        $html .= ' ' . esc_html( $label );
        $html .= '</td>';
        $html .= '<td>';
        if( $value === $ppp_default ) {
          $html .= '<span class="dashicons dashicons-yes" title="' . esc_attr__( 'This is the default products per page value for your shop. You can change it in the Plugin Settings tab.', 'annasta-filters' ) . '"></span>';
        }
        $html .= '</td>';
        $html .= '<td class="awf-buttons-column"><button type="button" class="button button-secondary awf-fa-icon awf-fas-icon awf-fa-delete-btn awf-remove-ppp-value-btn" title="' . esc_attr__( 'Delete value', 'annasta-filters' ) . '"></button></td>';
        $html .= '</tr>';
      }

      return $html;
    }
    
    public function display_presets_list_title_buttons( $admin_url = '' ) {}

    public function awf_display_preset_btns( $preset_id, $admin_url ) {}
    
    public function awf_display_presets_list_footer( $admin_url = '' ) {
      echo '<div style="padding-left:5px;">', sprintf( wp_kses( __( '<strong><a href="%1$s">Upgrade</a></strong> to <strong>annasta Woocommerce Product Filters Premium</strong> to manage multiple presets!', 'annasta-filters' ), array(  'a' => array( 'href' => array() ), 'strong' => array() ) ), esc_url( a_w_f_fs()->get_upgrade_url() ) ), '</div>';
    }

    protected function display_preset_display_mode_popup( $preset_id, $options_only = false ) {
      $display_modes = A_W_F::$admin->get_display_modes();
      $customizer_options = get_option( 'awf_customizer_options', array() );
      $current_dm = get_option( 'awf_preset_' . $preset_id . '_display_mode', 'visible' );

      $options_html = '<div class="awf-icon-options-container">';
      
      foreach( $display_modes as $dm => $dm_label ) {
        $wrapper_classes = array( 'awf-icon-option-wrapper' );
        $checked = $icon_s_url = '';

        if( $dm === $current_dm ) {
          $wrapper_classes[] = 'awf-active-icon-option-wrapper';
          $checked = ' checked';
          $icon_s_url = A_W_F_PLUGIN_URL . '/styles/images/display-mode-' . $dm . '.png';
        }

        $options_html .= '<div class="' . implode( ' ', $wrapper_classes ) . '" data-icon-s-url="' . esc_url( $icon_s_url ) . '" data-title="' . esc_attr( $dm_label ) . '">' .
        '<div class="awf-icon-option-container" data-display-mode="' . esc_attr( $dm ) . '">' .
        '<div class="awf-icon-option-icon-container"><img src="' . A_W_F_PLUGIN_URL . '/styles/images/display-mode-' . $dm . '-L.png" class="awf-display-mode-icon" /></div>' .
        '<input type="radio" class="awf-display-mode-option-' . esc_attr( $dm ) . '" name="awf-display-mode" value="' . esc_attr( $dm ) . '"' . $checked . ' />' .
        '<div class="awf-icon-option-label"><span>' . esc_html( $dm_label ) . '<span>' .

        ( ('togglable' === $dm ) ? '<span class="awf-togglable-visibility-notice">' . esc_html__( 'Auto-inserted!', 'annasta-filters' ) . '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'You don\'t need to insert your preset via widget/shortcode when using the "Controlled by "Filters" button" visibility mode.', 'annasta-filters' ) . '"></span></span>' : '' ) .

        '</div>' .
        '</div></div>'
        ;
      }

      $options_html .= '</div>';

      $fix_btn_position_notice_html =
      '<div id="awf-fix-toggle-btn-notice" class="">' .
      '<input id="awf_toggle_filters_button_fixed_position" class="awf-popup-option" type="checkbox" value="1"' .
      ( ( isset( $customizer_options['awf_filters_button_fixed_position'] ) &&  ('yes' === $customizer_options['awf_filters_button_fixed_position'] ) ) ? ' checked' : '' ) .
      '>' .
      '<label for="awf_toggle_filters_button_fixed_position">' . esc_html__( 'Fix the "Filters" button page position', 'annasta-filters' ) . '</label>' . 
      '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Enable the floating "Filters" button mode.', 'annasta-filters' ) . '"></span>' . 
      '</div>'
      ;

      $btn_customization_notice_html =
      '<div id="awf-btn-customization-notice" class="">' .
      sprintf( wp_kses( __( 'To <strong>customize the "Filters" button</strong> go to annasta Filters > Plugin settings > <a href="%1$s" target="_blank"><strong>"Filters" toggle button settings</strong></a>.', 'annasta-filters' ), array( 'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() ) ), admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=plugin-settings&awf-expanded-sections=1' ) ) .
      '</div>'
      ;

      $preset_insertion_notice_html =
      '<div id="awf-preset-insertion-notice" class="">' .
      sprintf( wp_kses( __( 'Don\'t forget to <a href="%1$s" target="_blank"><strong>insert this preset</strong></a> into the needed sidebar, header, or other area of your site.', 'annasta-filters' ), array( 'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() ) ), esc_url( 'https://annasta.net/plugins/annasta-woocommerce-product-filters/tutorials/getting-started/#inserting-preset-into-the-site' ) ) .
      '</div>'
      ;

      $notices_html = '';

      switch( $current_dm ) {
        case 'visible':
        case 'visible-on-s':
        case 'visible-on-l':
          $notices_html .= $preset_insertion_notice_html;
          break;
        case 'togglable-on-s':
          $notices_html .= $preset_insertion_notice_html;
        case 'togglable':
          $notices_html .= $fix_btn_position_notice_html;
          $notices_html .= $btn_customization_notice_html;
          break;
        default:
          break;
      }

      if( ! empty( $notices_html ) ) {
        $options_html .= '<div class="awf-info-notice">' . $notices_html . '</div>';
      }

      if( $options_only ) {
        echo $options_html;
        return;
      }

      $options_html = '<div class="awf-overlay-popup-row awf-display-mode-options-row" data-preset-id="' . intval( $preset_id) . '">' . $options_html . '</div>';

      $html = '<div class="awf-overlay-popup awf-display-mode-popup">' .
      '<div title="' . esc_attr__( 'Close', 'annasta-filters' ) . '" class="awf-fa-icon awf-close-overlay-popup-btn"></div>' .

      '<div class="awf-overlay-popup-row awf-overlay-popup-header-row" title="' . sprintf( esc_attr__( 'Update Visibility setting for preset #%1$s', 'annasta-filters' ), $preset_id ) . '">' .
      '<span>' . esc_html( get_option( 'awf_preset_' . $preset_id . '_name' ) ) . '</span><span class="dashicons dashicons-arrow-right-alt2"></span><span>' . esc_html__( 'Visibility', 'annasta-filters' ) . '</span>' .
      '</div>' .

      $options_html .

      '<div class="awf-overlay-popup-row"><button type="button" title="' . esc_attr__( 'Close', 'annasta-filters' ) . '" class="button button-secondary awf-overlay-popup-done-btn">' . esc_html__( 'Done', 'annasta-filters' ) . '</button></div>' .

      '</div>'
      ;

      echo $html;
    }

    protected function update_preset_display_mode( $preset_id, $display_mode ) {
      $display_modes = A_W_F::$admin->get_display_modes();
      if( isset( $display_modes[$display_mode] ) ) {
        update_option( 'awf_preset_' . $preset_id . '_display_mode', $display_mode );
      }

      $this->generate_styles_css();
      $this->display_preset_display_mode_popup( $preset_id, true );
    }

    protected function toggle_filters_button_fixed_position( $is_fixed ) {
      $customizer_options = get_option( 'awf_customizer_options', array() );
      $customizer_options['awf_filters_button_fixed_position'] = $is_fixed ? 'yes' : 'no';
      
      update_option( 'awf_customizer_options', $customizer_options );
      $this->generate_styles_css();
    }

    protected function get_module_defaults( $filter_data ) {
      $method = 'get_' . $filter_data['module'] . '_defaults';
      if( method_exists( $this, $method ) ) {
        return $this->$method( $filter_data );
      }

      return array();
    }

    protected function get_taxonomy_defaults( $filter_data ) {
      $settings = array(
        'taxonomy'                => urldecode( $filter_data['taxonomy']->name ),
        'title'                   => $filter_data['taxonomy']->label,
        'show_title'              => true,
        'button_submission'       => false,
        'active_prefix'           => '',
        'show_active'             => false,
        'block_deselection'       => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'multi',
        'type_options'            => array(),
        'style'                   => 'icons',
        'style_options'           => array(),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'terms_limitation_mode'   => 'exclude',
        'excluded_items'          => array(),
        'included_items'          => array(),
        'sort_by'                 => 'admin',
        'sort_order'              => 'none',
        'height_limit'            => '0',
        'shrink_height_limit'     => true,
        'show_count'              => false,
      );

      if( $filter_data['taxonomy']->hierarchical ) {
        $position = intval( array_search( 'show_search', array_keys( $settings ) ) );
        
        $settings = array_merge(
          array_slice( $settings, 0, $position, true),
          array(
            'hierarchical_level' => 1,
            'display_children' => true,
            'children_collapsible' => true,
            'children_collapsible_on' => true,
          ),
          array_slice( $settings, $position, count( $settings ) - 1, true)
        );
      }

      return $settings;
    }

    protected function get_search_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'button_submission'       => false,
        'submit_on_change'        => false,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => null,
        'type_options'            => array(),
        'style'                   => null,
        'style_options'           => array(),
        'placeholder'             => __( 'Search products...', 'annasta-filters' ),
        'autocomplete'            => false,
        'height_limit'            => '0',
        'shrink_height_limit'     => true,
      ) );
    }

    protected function get_price_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'button_submission'       => false,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'range',
        'type_options'            => array(
          'range_type'            => 'auto_range',
          'range_values'          => array( floatval( 0 ), floatval( $this->get_products_max_price() ) ),
          'decimals'              => intval( 0 )
        ),
        'style'                   => 'range-slider',
        'style_options'           => array( 'step' => floatval( 1 ), 'value_prefix' => '', 'value_postfix' => '' ),
        'show_in_row'             => false,
        'height_limit'            => '0',
        'shrink_height_limit'     => true,
      ) );
    }

    protected function get_stock_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'button_submission'       => false,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'single',
        'style'                   => 'radios',
        'style_options'           => array(),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'terms_limitation_mode'   => 'exclude',
        'excluded_items'          => array(),
        'included_items'          => array(),
        'height_limit'            => '0',
        'shrink_height_limit'     => true,
      ) );
    }

    protected function get_featured_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'button_submission'       => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'multi',
        'style'                   => 'checkboxes',
        'style_options'           => array(),
        'height_limit'            => '0',
        'shrink_height_limit'     => true,
      ) );
    }

    protected function get_onsale_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'button_submission'       => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'multi',
        'style'                   => 'checkboxes',
        'style_options'           => array(),
        'height_limit'            => '0',
        'shrink_height_limit'     => true,
      ) );
    }

    protected function get_rating_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'button_submission'       => false,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'range',
        'type_options'            => array( 
          'range_type' => 'custom_range',
          'range_values' => array( round( floatval( 1 ), 2, PHP_ROUND_HALF_UP ), round( floatval( 2 ), 2, PHP_ROUND_HALF_UP ), round( floatval( 3 ), 2, PHP_ROUND_HALF_UP ), round( floatval( 4 ), 2, PHP_ROUND_HALF_UP ), round( floatval( 5 ), 2, PHP_ROUND_HALF_UP ), round( floatval( 5.01 ), 2, PHP_ROUND_HALF_UP ) ),
          'precision' => round( floatval( 0.01 ), 2, PHP_ROUND_HALF_UP ),
          'decimals' => intval( 0 ),
        ),
        'style'                   => 'radios',
        'style_options'           => array( 'step' => floatval( 1 ), 'value_prefix' => '', 'value_postfix' => '' ),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'height_limit'            => '0',
        'shrink_height_limit'     => true,
      ) );
    }
    
    protected function get_ppp_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'button_submission'       => false,
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'single',
        'style'                   => 'radios',
        'style_options'           => array(),
        'ppp_values'              => array( 12 => __( 'products per page', 'annasta-filters' ) ),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'height_limit'            => '0',
        'shrink_height_limit'     => true,
      ) );
    }
    
    protected function get_orderby_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'button_submission'       => false,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'single',
        'style'                   => 'radios',
        'style_options'           => array(),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'terms_limitation_mode'   => 'exclude',
        'excluded_items'          => array(),
        'included_items'          => array(),
        'height_limit'            => '0',
        'shrink_height_limit'     => true,
      ) );
    }
    
    protected function get_meta_defaults( $filter_data ) {
      return( array(
        'meta_name'               => '',
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'button_submission'       => false,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'single',
        'type_options'            => array(),
        'style'                   => 'radios',
        'style_options'           => array(),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'terms_limitation_mode'   => 'exclude',
        'excluded_items'          => array(),
        'included_items'          => array(),
        'height_limit'            => '0',
        'shrink_height_limit'     => true,
      ) );
    }

    public function get_all_filters() {
      $filters = array_flip( array_diff( A_W_F::$modules, array( 'taxonomy' ) ) );
      foreach( $filters as $filter_name => $label ) { $filters[$filter_name] = $this->get_filter_title( $filter_name ); }

      $taxonomies = get_object_taxonomies( 'product', 'objects' );

      foreach( $taxonomies as $t ) {
        if( in_array( $t->name, A_W_F::$excluded_taxonomies ) ) continue;

        $filters['taxonomy--' . $t->name] = $t->label;
      }
      
      return $filters;
    }

    public function get_filter_title( $filter_name ) {
      switch( $filter_name ) {
        case 'search': return esc_html__( 'Products Search', 'annasta-filters' ); break;
        case 'ppp': return esc_html__( 'Products Per Page', 'annasta-filters' ); break;
        case 'stock': return esc_html__( 'Products Stock Status Filter', 'annasta-filters' ); break;
        case 'price': return esc_html__( 'Products Price Filter', 'annasta-filters' ); break;
        case 'featured': return esc_html__( 'Featured Products Filter', 'annasta-filters' ); break;
        case 'onsale': return esc_html__( 'Products on Sale Filter', 'annasta-filters' ); break;
        case 'rating': return esc_html__( 'Products Rating Filter', 'annasta-filters' ); break;
        case 'orderby': return esc_html__( 'Products Sort by Control', 'annasta-filters' ); break;
        case 'meta': return esc_html__( 'Products Meta Data Filter', 'annasta-filters' ); break;
        default: return ''; break;
      }
    }

    public function get_default_filter_label( $module, $settings ) {
      $label = '';

      switch( $module ) {
        case 'taxonomy':
          $taxonomy = get_taxonomy( $settings['taxonomy'] );
          if( $taxonomy ) {
            $label = $taxonomy->label;
          }
          break;
        case 'meta':
          $label = sprintf( esc_html__( '%1$s Meta Data Filter', 'annasta-filters' ), $settings['meta_name'] );
          break;
        default:
          $label = $this->get_filter_title( $module );
      }

      return $label;
    }

    protected function get_presets_names() {
      $presets = array();
      
      foreach( A_W_F::$presets as $preset_id => $preset_data ) {
        $presets[$preset_id] = __( get_option( 'awf_preset_' . $preset_id . '_name', '' ) );
      }
      
      return $presets;
    }

    public function get_preset_settings( $preset ) {
      $prefix = 'awf_preset_' . $preset->id . '_';

      return array(

        10 => array(
          'id' => 'awf_preset_settings_section_1', 
          'type' => 'title',
          'name' => 0 === $preset->id ? '' : sprintf( __( 'Preset id: %1$s', 'annasta-filters' ), $preset->id ),
        ),

        20 => array( 
          'id'       => $prefix. 'name', 
          'type'     => 'text',
          'name'     => __( 'Preset name', 'annasta-filters' ),
          'default'  => $preset->name,
        ),

        30 => array( 
          'id'       => $prefix. 'title', 
          'type'     => 'text',
          'name'     => __( 'Preset title', 'annasta-filters' ),
          'default'  => $preset->title,
          'desc_tip' => __( 'This will show as your filters\' header. Leave blank if not needed.', 'annasta-filters' )
        ),

        40 => array( 
          'id'       => $prefix. 'description', 
          'type'     => 'textarea',
          'name'     => __( 'Preset description', 'annasta-filters' ),
          'default'  => $preset->description,
          'desc_tip' => __( 'Display custom text under the preset title. Leave blank if not needed.', 'annasta-filters' )
        ),

        50 => array( 
          'id'       => $prefix. 'show_title_badges', 
          'type'     => 'checkbox',
          'name'     => __( 'Active filter badges', 'annasta-filters' ),
          'default'  => $preset->show_title_badges,
          'desc_tip' => __( 'Display active filter badges with reset buttons on top of the preset filters.', 'annasta-filters' )
        ),

        60 => array(
          'id'       => $prefix. 'reset_btn', 
          'type'    => 'select',
          'name'    => __( 'Reset all button', 'annasta-filters' ),
          'default' => $preset->reset_btn,
          'options' => array(
            'none'        => __( 'None', 'annasta-filters' ),
            'top'       => __( 'At the top of the preset', 'annasta-filters' ),
            'bottom'  => __( 'At the bottom of the preset', 'annasta-filters' ),
            'both' => __( 'Both at the top and at the bottom', 'annasta-filters' )
          ),
          'desc'    => __( 'This controls the display of \'Clear all\' buttons that will reset all the existing active filters.', 'annasta-filters' ),
          'desc_tip' =>  true,
        ),

        70 => array( 
          'id'       => $prefix. 'reset_btn_label', 
          'type'     => 'text',
          'name'     => __( 'Reset button label', 'annasta-filters' ),
          'default'  => $preset->reset_btn_label,
        ),

        80 => array( 
          'id'       => $prefix. 'filter_btn_label', 
          'type'     => 'text',
          'name'     => __( 'Submit button label', 'annasta-filters' ),
          'default'  => $preset->filter_btn_label,
        ),

        90 => array(
          'id'       => $prefix. 'type', 
          'type'    => 'select',
          'name'    => __( 'Filtering style', 'annasta-filters' ),
          'default' => $preset->type,
          'options' => array(
            'ajax'            => __( 'AJAX with instant submission', 'annasta-filters' ),
            'ajax-button'     => __( 'AJAX with button submission', 'annasta-filters' ),
            'ajax-delegated'  => __( 'AJAX with delegated submission', 'annasta-filters' ),
            'url'             => __( 'URL filters', 'annasta-filters' ),
            'form'            => __( 'Form with button submission', 'annasta-filters' ),
            'sbs'             => __( 'Step-by-step filters', 'annasta-filters' ),
          ),
          'class' => 'awf-preset-type',
        ),

        100 => array( 'type' => 'sectionend', 'id' => 'awf_preset_settings_section_1' ),
        
        110 => array(
          'id' => 'awf_preset_settings_sbs_section', 
          'type' => 'title',
          'name' => __( 'Step by step filters settings', 'annasta-filters' ),
        ),

        120 => array(
          'id'       => $prefix. 'sbs_type', 
          'type'    => 'select',
          'name'    => __( 'Step by step style', 'annasta-filters' ),
          'default' => $preset->sbs_type,
          'options' => array(
            'unhide'   => __( 'Add filters one by one', 'annasta-filters' ),
            'show-one' => __( 'Display one filter at a time', 'annasta-filters' ),
          ),
          'class' => 'awf-sbs-type',
        ),
        
        130 => array(
          'id'       => $prefix. 'sbs_submission', 
          'type'    => 'select',
          'name'    => __( 'Filters submission', 'annasta-filters' ),
          'default' => $preset->sbs_submission,
          'options' => array(
            'instant'   => __( 'Update products list with each filter application', 'annasta-filters' ),
            'instant-last' => __( 'Update products list when the last filter is applied', 'annasta-filters' ),
            'button' => __( 'Submit button displayed for all the steps', 'annasta-filters' ),
            'button-last' => __( 'Submit button displayed after the last filter selection', 'annasta-filters' ),
          ),
        ),

        140 => array( 
          'id'       => $prefix. 'sbs_next_btn', 
          'type'     => 'checkbox',
          'name'     => __( 'Next button', 'annasta-filters' ),
          'default'  => $preset->sbs_next_btn,
          'desc_tip' => __( 'Trigger the moves to a next filter by a click on the "Next" button. By default the transitions from one filter to the next happen automatically when at least one filter option gets selected, which means that you will <strong>need</strong> to use the Next button to enable multi-selection with certain styles.', 'annasta-filters' )
        ),

        150 => array( 
          'id'       => $prefix. 'sbs_back_btn', 
          'type'     => 'checkbox',
          'name'     => __( 'Back button', 'annasta-filters' ),
          'default'  => $preset->sbs_back_btn,
          'desc_tip' => __( 'Back button can be useful when displaying one filter at a time.', 'annasta-filters' )
        ),

        160 => array( 
          'id'       => $prefix. 'sbs_redirect', 
          'type'     => 'text',
          'name'     => __( 'Redirect URL', 'annasta-filters' ),
          'default'  => $preset->sbs_redirect,
          'desc_tip' => __( 'Enter the URL of the page to which you wish to apply the filters. Leave blank to filter the current page, or redirect to the shop page from a non-products page. ATTENTION: for the redirection to a taxonomy archive page (product categories, tags, brands) to work properly, add the "archive-filter=1" parameter after the "?" sign of your url string, like so: https://mysite.com/brand/brand-1/?archive-filter=1.', 'annasta-filters' )
        ),
        
        170 => array( 'type' => 'sectionend', 'id' => 'awf_preset_settings_sbs_section' ),
        
        180 => array(
          'id' => 'awf_preset_settings_section_2', 
          'type' => 'title',
          'name' => __( 'Display options', 'annasta-filters' ),
        ),

        190 => array(
          'id'       => $prefix. 'layout', 
          'type'    => 'select',
          'name'    => __( 'Layout', 'annasta-filters' ),
          'default' => $preset->layout,
          'options' => array(
            '1-column'        => __( '1 column', 'annasta-filters' ),
            '2-column'        => __( '2 columns', 'annasta-filters' ),
            '3-column'        => __( '3 columns', 'annasta-filters' ),
            '4-column'       => __( '4 columns', 'annasta-filters' )
          ),
          'desc'    => __( 'Choose multicolumn layouts for headers and footers. 1-column layout is good for sidebars.', 'annasta-filters' ),
          'desc_tip' =>  true,
          'class' =>  'awf-preset-layout',
        ),

        200 => array(
          'id'       => $prefix. 'display_mode', 
          'type'    => 'radio',
          'name'    => __( 'Visibility', 'annasta-filters' ),
          'default' => $preset->display_mode,
          'options' => A_W_F::$admin->get_display_modes(),
          'desc'    => __( 'If enabled, the "Filters" button will be displayed to toggle the filters visibility. Button-controlled presets will only work on pages with filterable products lists (shop/ taxonomy archives/ shortcode pages).', 'annasta-filters' ),
          'desc_tip' =>  true,
          'class' => 'awf-preset-display-mode',
        ),

        210 => array(
          'id'       => $prefix. 'togglable_mode', 
          'type'    => 'select',
          'name'    => __( '"Filters" button mode', 'annasta-filters' ),
          'default' => $preset->togglable_mode,
          'options' => array(
            'above-products'      => __( 'Display preset filters under the "Filters" button', 'annasta-filters' ),
            'left-popup-sidebar'  => __( 'Display preset filters in a popup sidebar', 'annasta-filters' ),
          ),
          'desc'    => __( 'Choose the preset style when its visibility is controlled by the "Filters" button (see the "Visibility" setting).', 'annasta-filters' ),
          'desc_tip' =>  true,
          'class' => 'awf-preset-togglable-mode',
        ),

        220 => array( 
          'id'       => $prefix. 'responsive_width', 
          'type'     => 'text',
          'name'     => __( 'Responsive width', 'annasta-filters' ),
          'default'  => $preset->responsive_width,
          'css'      => 'width: 100px;',
          'desc_tip' => __( 'Use in combination with the "Visibility" setting: set the screen width enabling the responsive behaviour, in pixels.', 'annasta-filters' )
        ),
        
        230 => array( 'type' => 'sectionend', 'id' => 'awf_preset_settings_section_2' ),
      );
    }

    public function get_display_modes() {
      $display_modes = array(
        'visible'           => __( 'Visible', 'annasta-filters' ),
        'visible-on-s'    => __( 'Visible on screens narrower than the Responsive width', 'annasta-filters' ),
        'visible-on-l'     => __( 'Visible on screens wider than the Responsive width', 'annasta-filters' ),
        'togglable'         => __( 'Controlled by "Filters" button', 'annasta-filters' ),
        'togglable-on-s'  => __( 'Visible on screens wider than the Responsive width, controlled by "Filters" button on narrower screens', 'annasta-filters' ),
      );

      return $display_modes;
    }

    public function display_ts_header( $options ) {
      /* $options['class']: awf-ts-collapsed awf-ts-enforced, $options['suffix']: additional class for the table forced into ts, $options['desc_tip'] */

      $classes = 'form-table awf-ts-h awf-ts-' . $options['value' ] . ' ' . $options['class'];
      
      echo
      '<table class="', esc_attr( $classes ), '" data-ts="', esc_attr( $options['value'] ), '" data-forced-class="', esc_attr( $options['suffix'] ), '">',
      '<tr>',
        '<td colspan="2" scope="row">', '<h3><span>',
        esc_html( $options['title' ] ),
        ( empty( $options['desc_tip'] ) ? '' : '<span class="woocommerce-help-tip" data-tip="' . esc_attr( $options['desc_tip'] ) . '"></span>' ),
        '</span></h3>', '</td>',
      '</tr>',
      '</table>'
      ;
    }
    
    public function get_product_list_settings() {
      return array(

        10 => array(
          'id' => 'awf_product_list_settings_general_section',
          'type' => 'title',
          'name' => __( 'Woocommerce Product List Settings', 'annasta-filters' ),
        ),
				
				20 => array( 'type' => 'awf_product_list_settings_notice', 'id' => 'awf_product_list_settings_notice' ),

        30 => array( 
          'id'       => 'awf_theme_support', 
          'type'     => 'checkbox',
          'name'     => __( 'Theme support', 'annasta-filters' ),
          'default'  => get_option( 'awf_theme_support', 'yes' ),
          'desc_tip' => $this->get_awf_theme_support_tip(),
        ),

        40 => array( 
          'id'       => 'awf_shop_columns',
          'type'     => 'text',
          'name'     => __( 'Product columns', 'annasta-filters' ),
          'default'  => get_option( 'awf_shop_columns', '' ),
          'desc_tip' => __( 'Set this to the amount of product columns that you wish to be displayed by your shop. Leave blank for the Woocommerce default. WARNING: this option will only work with themes that support the relevant Woocommerce built-in setting. If your theme doesn\'t respond to this setting, set it through your theme customizer, and enter the same value here.', 'annasta-filters' ),
          'css'      => 'width: 50px;'
        ),

        50 => array( 
          'id'       => 'awf_ppp_default',
          'type'     => 'text',
          'name'     => __( 'Products per page', 'annasta-filters' ),
          'default'  => get_option( 'awf_ppp_default', '' ),
          'desc_tip' => __( 'Set your preferred products per page value here. It will be used unless user selects different value on a products per page control. Leave blank to use the Woocommerce default. WARNING: this option will only work with themes that support the built-in Woocommerce products per page setting. If your theme doesn\'t respond to this setting, set it to the amount of products displayed when you first load your shop page.', 'annasta-filters' ),
          'css'      => 'width: 50px;'
        ),
				
        60 => array( 
          'id'       => 'awf_ajax_pagination',
          'type'     => 'select',
          'options'  => array(
            'none'              => __( 'Default', 'annasta-filters' ),
            'page_numbers'      => __( 'AJAX pagination', 'annasta-filters' ),
            'infinite_scroll'   => __( 'Infinite Scroll', 'annasta-filters' ),
            'more_button'       => __( '"Load More" button', 'annasta-filters' ),
          ),
          'name'     => __( 'Pagination', 'annasta-filters' ),
          'default'  => get_option( 'awf_ajax_pagination', 'none' ),
          'desc_tip' => __( 'Select "Default" to leave the default theme pagination, or set to the AJAX pagination style that suits your shop. AJAX pagination options may not work with some themes, you are welcome to contact us if you wish to add support for your theme.', 'annasta-filters' )
        ),
        
        90 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_general_section' ),

        100 => array( 'id' => 'awf_product_list_settings_ajax_section_heading', 'type' => 'title' ),
        101 => array( 'type' => 'awf_settings_ts_header', 'id' => 'awf_product_list_settings_ajax_section_heading_ts_header', 'title' => __( 'AJAX options', 'annasta-filters' ), 'class' => 'awf-ts-enforced', 'value' => '1' ),
        102 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_ajax_section_heading' ),

        110 => array(
          'id' => 'awf_product_list_settings_ajax_section',
          'type' => 'title',
          'name' => '',
        ),
        
        120 => array( 
          'id'       => 'awf_ajax_mode',
          'type'     => 'select',
          'options'  => array(
            'compatibility_mode' => __( 'Enhanced compatibility', 'annasta-filters' ),
            'dedicated_ajax'      => __( 'Dedicated AJAX', 'annasta-filters' ),
          ),
          'name'     => __( 'AJAX mode', 'annasta-filters' ),
          'default'  => get_option( 'awf_ajax_mode', 'compatibility_mode' ),
          'desc_tip' => __( 'Enhanced compatibility mode will work with most themes. Try the dedicated AJAX mode for faster AJAX calls.', 'annasta-filters' )
        ),

        130 => array( 
          'id'       => 'awf_ajax_scroll_on', 
          'type'     => 'checkbox',
          'name'     => __( 'Scroll to AJAX results', 'annasta-filters' ),
          'default'  => get_option( 'awf_ajax_scroll_on', 'no' ),
          'desc_tip' => __( 'Enable an animated scroll to the top of the products list on AJAX filter application.', 'annasta-filters' )
        ),
        
        140 => array( 
          'id'       => 'awf_ajax_scroll_adjustment', 
          'type'     => 'text',
          'name'     => __( 'AJAX scroll adjustment', 'annasta-filters' ),
          'default'  => get_option( 'awf_ajax_scroll_adjustment', -100 ),
          'desc_tip' => __( 'Tweak (in pixels) the point to which the page scrolls after AJAX updates. Use negative numbers for the downward scroll.', 'annasta-filters' ),
          'css'      => 'width: 50px;'
        ),

        160 => array( 'type' => 'awf_product_list_settings_custom_selectors_section', 'id' => 'awf_product_list_settings_custom_selectors' ),
        170 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_ajax_section' ),
        
        200 => array( 'id' => 'awf_product_list_settings_archive_options_section_heading', 'type' => 'title' ),
        201 => array( 'type' => 'awf_settings_ts_header', 'id' => 'awf_product_list_settings_archive_options_section_heading_ts_header', 'title' => __( 'Archive pages settings', 'annasta-filters' ), 'class' => 'awf-ts-enforced awf-ts-collapsed', 'value' => '4' ),
        202 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_archive_options_section_heading' ),

        210 => array(
          'id' => 'awf_product_list_settings_archive_options_section',
          'type' => 'title',
          'name' => '',
        ),

        220 => array( 
          'id'       => 'awf_archive_components_support',
          'type'     => 'checkbox',
          'name'     => __( 'Archive pages support', 'annasta-filters' ),
          'default'  => get_option( 'awf_archive_components_support', 'yes' ),
          'desc_tip' => __( 'Update archive titles and descriptions to match activated filters.', 'annasta-filters' ),
        ),
				
        230 => array( 
          'id'       => 'awf_breadcrumbs_support',
          'type'     => 'checkbox',
          'name'     => __( 'Breadcrumbs support', 'annasta-filters' ),
          'default'  => get_option( 'awf_breadcrumbs_support', 'yes' ),
          'desc_tip' => __( 'Uncheck to disable breadcrumbs adjustments on taxonomy archive pages.', 'annasta-filters' ),
        ),

        240 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_archive_options_section' ),

        300 => array( 'id' => 'awf_product_list_settings_template_section_heading', 'type' => 'title' ),
        301 => array( 'type' => 'awf_settings_ts_header', 'id' => 'awf_product_list_settings_template_section_heading_ts_header', 'title' => __( 'Add elements', 'annasta-filters' ), 'class' => 'awf-ts-collapsed', 'value' => '2' ),
        302 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_template_section_heading' ),

        310 => array(
          'id' => 'awf_product_list_settings_template_section',
          'type' => 'title',
          'name' => '',
        ),
				320 => array( 'type' => 'awf_product_list_settings_template_options', 'id' => 'awf_product_list_settings_template_options' ),
        330 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_template_section' ),

        400 => array( 'id' => 'awf_product_list_settings_remove_from_template_section_heading', 'type' => 'title' ),
        401 => array( 'type' => 'awf_settings_ts_header', 'id' => 'awf_product_list_settings_remove_from_template_section_heading_ts_header', 'title' => __( 'Remove elements', 'annasta-filters' ), 'class' => 'awf-ts-enforced awf-ts-collapsed', 'value' => '3' ),
        402 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_remove_from_template_section_heading' ),

        410 => array(
          'id' => 'awf_product_list_settings_remove_from_template_section',
          'type' => 'title',
          'name' => '',
        ),
        
        420 => array( 
          'id'       => 'awf_remove_wc_shop_title', 
          'type'     => 'checkbox',
          'name'     => __( 'Shop title', 'annasta-filters' ),
          'default'  => get_option( 'awf_remove_wc_shop_title', 'no' ),
          'desc_tip' => __( 'Remove Woocommerce page title from shop page.', 'annasta-filters' )
        ),

        430 => array( 
          'id'       => 'awf_remove_wc_orderby', 
          'type'     => 'checkbox',
          'name'     => __( 'Sort by', 'annasta-filters' ),
          'default'  => get_option( 'awf_remove_wc_orderby', 'no' ),
          'desc_tip' => __( 'Remove or hide all the native Woocommerce Sort by controls.', 'annasta-filters' )
        ),

        499 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_remove_from_template_section' ),

			);
		}

    public function display_product_list_settings_custom_selectors_section() {
      $selectors = get_option( 'awf_custom_selectors', array() );
      $current_theme = wp_get_theme();
      $theme_selectors = apply_filters( 'awf_js_data', array() );

      $wc_default_message = esc_attr__( 'Leave blank for the default WooCommerce selector', 'annasta-filters' );
      $theme_default_message = sprintf( esc_attr__( 'Leave blank for the %1$s theme default selector', 'annasta-filters' ), $current_theme->__get( 'name' ) );

      echo
        '<tr><th scope="row" class="titledesc" style="padding: 0;"></th><td style="padding: 0;"></td></tr>',
					
        '<tr id="awf-custom-selectors-heading">',
        '<th colspan="2" scope="row" class="">',
        '<h5>', esc_html__( 'Custom selectors', 'annasta-filters' ), '</h5>',
				'</th>',
        '</tr>',

        '<tr class="awf-custom-selectors-notice">',
        '<th colspan="2" scope="row" class="awf-info-notice-container"><span class="awf-info-notice">',
        esc_html__( 'If the products, pagination, result counts or other native WooCommerce elements do not get properly updated in AJAX mode, use this section to enable detection for the non-standard HTML structure of your theme. Enter classes (preceded by dots), combination of classes, or element ids (preceded by the "#" sign) into the relevant fields.', 'annasta-filters' ),
        '</span></th>',
        '</tr>'
      ;

        echo
        '<tr valign="top" class="awf-custom-selector-row">',
        '<th scope="row" class="titledesc"><label for="awf_custom_selectors_products">', esc_html__( 'Products list selector', 'annasta-filters' ), '<span class="woocommerce-help-tip" data-tip="',
          ( isset( $theme_selectors['products_container'] ) ? $theme_default_message . ' (' . $theme_selectors['products_container'] . ')' : $wc_default_message . ' (.products)' ),
        '"></span></label></th>',
        '<td class="forminp forminp-text">',
          '<input name="awf_custom_selectors_products" id="awf_custom_selectors_products" type="text" style="" value="', ( isset( $selectors['products'] ) ? $selectors['products'] : '' ), '">',
        '</td>',
        '</tr>',

        '<tr valign="top" class="awf-custom-selector-row">',
        '<th scope="row" class="titledesc"><label for="awf_custom_selectors_product">', esc_html__( 'Single product selector', 'annasta-filters' ), '<span class="woocommerce-help-tip" data-tip="',
          ( isset( $theme_selectors['ajax_pagination']['product_container'] ) ? $theme_default_message . ' (' . $theme_selectors['ajax_pagination']['product_container'] . ')' : $wc_default_message . ' (.product)' ),
        '"></span></label></th>',
        '<td class="forminp forminp-text">',
          '<input name="awf_custom_selectors_product" id="awf_custom_selectors_product" type="text" style="" value="', ( isset( $selectors['product'] ) ? $selectors['product'] : '' ), '">',
        '</td>',
        '</tr>',

        '<tr valign="top" class="awf-custom-selector-row">',
        '<th scope="row" class="titledesc"><label for="awf_custom_selectors_pagination">', esc_html__( 'Pagination selector', 'annasta-filters' ), '<span class="woocommerce-help-tip" data-tip="',
          ( isset( $theme_selectors['pagination_container'] ) ? $theme_default_message . ' (' . $theme_selectors['pagination_container'] . ')' : $wc_default_message . ' (.woocommerce-pagination)' ),
        '"></span></label></th>',
        '<td class="forminp forminp-text">',
          '<input name="awf_custom_selectors_pagination" id="awf_custom_selectors_pagination" type="text" style="" value="', ( isset( $selectors['pagination'] ) ? $selectors['pagination'] : '' ), '">',
        '</td>',
        '</tr>',

        '<tr valign="top" class="awf-custom-selector-row">',
        '<th scope="row" class="titledesc"><label for="awf_custom_selectors_page_number">', esc_html__( 'Page number selector', 'annasta-filters' ), '<span class="woocommerce-help-tip" data-tip="',
          ( isset( $theme_selectors['ajax_pagination']['page_number'] ) ? $theme_default_message . ' (' . $theme_selectors['ajax_pagination']['page_number'] . ')' : $wc_default_message . ' (a.page-numbers)' ),
        '"></span></label></th>',
        '<td class="forminp forminp-text">',
          '<input name="awf_custom_selectors_page_number" id="awf_custom_selectors_page_number" type="text" style="" value="', ( isset( $selectors['page_number'] ) ? $selectors['page_number'] : '' ), '">',
        '</td>',
        '</tr>',

        '<tr valign="top" class="awf-custom-selector-row">',
        '<th scope="row" class="titledesc"><label for="awf_custom_selectors_pagination_next">', esc_html__( 'Next page selector', 'annasta-filters' ), '<span class="woocommerce-help-tip" data-tip="',
          ( isset( $theme_selectors['ajax_pagination']['next'] ) ? $theme_default_message . ' (' . $theme_selectors['ajax_pagination']['next'] . ')' : $wc_default_message . ' (.next)' ),
        '"></span></label></th>',
        '<td class="forminp forminp-text">',
          '<input name="awf_custom_selectors_pagination_next" id="awf_custom_selectors_pagination_next" type="text" style="" value="', ( isset( $selectors['pagination_next'] ) ? $selectors['pagination_next'] : '' ), '">',
        '</td>',
        '</tr>',

        '<tr valign="top" class="awf-custom-selector-row">',
        '<th scope="row" class="titledesc"><label for="awf_custom_selectors_orderby">', esc_html__( 'Sort by selector', 'annasta-filters' ), '<span class="woocommerce-help-tip" data-tip="',
          ( isset( $theme_selectors['orderby_container'] ) ? $theme_default_message . ' (' . $theme_selectors['orderby_container'] . ')' : $wc_default_message . ' (.woocommerce-ordering)' ),
        '"></span></label></th>',
        '<td class="forminp forminp-text">',
          '<input name="awf_custom_selectors_orderby" id="awf_custom_selectors_orderby" type="text" style="" value="', ( isset( $selectors['orderby'] ) ? $selectors['orderby'] : '' ), '">',
        '</td>',
        '</tr>',

        '<tr valign="top" class="awf-custom-selector-row">',
        '<th scope="row" class="titledesc"><label for="awf_custom_selectors_no_result">', esc_html__( 'No results selector', 'annasta-filters' ), '<span class="woocommerce-help-tip" data-tip="',
          ( isset( $theme_selectors['no_result_container'] ) ? $theme_default_message . ' (' . $theme_selectors['no_result_container'] . ')' : $wc_default_message . ' (.woocommerce-info)' ),
        '"></span></label></th>',
        '<td class="forminp forminp-text">',
          '<input name="awf_custom_selectors_no_result" id="awf_custom_selectors_no_result" type="text" style="" value="', ( isset( $selectors['no_result'] ) ? $selectors['no_result'] : '' ), '">',
        '</td>',
        '</tr>',

        '<tr valign="top" class="awf-custom-selector-row awf-products-html-wrapper-wrapper">',
        '<th scope="row" class="titledesc"><label for="awf_products_html_wrapper">', esc_html__( 'Products HTML wrapper', 'annasta-filters' ), '<span class="woocommerce-help-tip" data-tip="',
          esc_attr__( 'Limit AJAX updates to custom page element.</br></br>Enter jQuery selector for the wrapper element that includes both WooCommerce product list and pagination.', 'annasta-filters' ),
        '"></span></label></th>',
        '<td class="forminp forminp-text">',
          '<div id="awf_products_html_wrapper_container">',
            '<input name="awf_products_html_wrapper" id="awf_products_html_wrapper" type="text" style="" value="', get_option( 'awf_products_html_wrapper', '' ), '">',
            '<span class="woocommerce-help-tip" data-tip="',
            esc_attr__( 'The wrapper detection algorithm relies on the products list and pagination selectors. If the detection fails, use the custom selector options above to assign theme-specific values.', 'annasta-filters' ),
            '"></span>',
            '<button type="button" id="awf_wrapper_detection_btn" class="button button-secondary awf-s-btn">', esc_html__( 'Detect wrapper', 'annasta-filters' ), '</button>',
          '</div>',
          '<div id="awf_products_html_wrapper_error"></div>',
          '<div id="awf_products_html_wrapper_message"></div>',
        '</td>',
        '</tr>',

        '<tr valign="top" class="awf-custom-selector-row">',
        '<th scope="row" class="titledesc"><label for="awf_force_wrapper_reload">', esc_html__( 'Force wrapper reload', 'annasta-filters' ),
        '</label></th>',
        '<td class="forminp forminp-text">',
          '<input id="awf_force_wrapper_reload" name="awf_force_wrapper_reload" type="checkbox" value="yes"' . ( 'yes' === get_option( 'awf_force_wrapper_reload', 'no' ) ? ' checked' : '' ) . '>',
          '<span class="description">' . esc_html__( 'Reload the products list header and footer on each AJAX call. Set the HTML container that wraps the products list with its header and footer in the Products HTML wrapper field above. WARNING: this setting is incompatible with some themes and options.', 'annasta-filters' ) . '</span>',
        '</td>',
        '</tr>',
        
        '<tr valign="top" class="awf-custom-selector-row"', ( empty( get_option( 'awf_products_html_wrapper', '' ) ) ? '' : ' style="display:none;"' ), '>',
        '<th scope="row" class="titledesc" style="padding-top:25px;padding-right:0;text-align:right;"><div><span class="woocommerce-help-tip" data-tip="',
        ( 'yes' === get_option( 'awf_global_wrapper', 'no' ) ? esc_attr__( 'Enable the automatic wrapper detection whenever the Products HTML wrapper is not set. This may help avoid conflicts with multiple product lists and optimize scripts execution speed, but is incompatible with some themes.', 'annasta-filters' ) : esc_attr__( 'Disable the wrapper detection scripts to fix pagination and other AJAX update issues. To manually limit the AJAX updates scope use the Products HTML wrapper field above.', 'annasta-filters' ) ),
        '" style="margin-left:-5px;"></span></div></th>',
        '<td class="forminp forminp-text" style="padding:25px 50px 25px 5px;text-align:left;">',
        '<button type="button" id="awf_global_wrapper_btn" class="button button-secondary awf-s-btn" style="margin-right:5px;width:100%;">', ( 'yes' === get_option( 'awf_global_wrapper', 'no' ) ? esc_html__( 'Auto-shrink the wrapper', 'annasta-filters' ) : esc_html__( 'Disable wrapper shrinkage', 'annasta-filters' ) ), '</button>',
        '',
        '</td>',
        '</tr>'
        ;

    }

    public function display_product_list_settings_notice() {
      echo
        '<tr><th scope="row" class="titledesc" style="padding: 0;"></th><td style="padding: 0;"></td></tr>',
					
        '<tr>',
        '<th colspan="2" scope="row" class="awf-info-notice-container"><span class="awf-info-notice">',
					
				wp_kses( __( 'Some of the options offered by this section may not be supported by your theme. You are welcome to contact us if you wish us to look into it.', 'annasta-filters' ), array( 'strong' => array() ) ),
			
				'</span></th>',
        '</tr>'
      ;
    }

    protected function get_product_list_template_option_hooks( $option ) {
      $hooks = array(
        'woocommerce_before_shop_loop' => 'woocommerce_before_shop_loop',
        'woocommerce_after_shop_loop' => 'woocommerce_after_shop_loop',
      );

      if( ! in_array( $option, array( 'pagination', 'default_wc_pagination', 'result_count', 'orderby' ) ) ) {
        $hooks = array(
          'woocommerce_before_main_content' => 'woocommerce_before_main_content',
          'woocommerce_archive_description' => 'woocommerce_archive_description',
          'woocommerce_after_main_content' => 'woocommerce_after_main_content',
        ) + $hooks;
        if( ! in_array( $option, array( 'product_categories' ) ) ) {
          $hooks += array(
            'woocommerce_no_products_found' => 'woocommerce_no_products_found',
            'woocommerce_shortcode_products_loop_no_results' => 'woocommerce_shortcode_products_loop_no_results',
          );
        }
      }

      return apply_filters( 'awf_product_list_template_option_hooks', $hooks, $option );
    }

    protected function get_product_list_template_options() {
      $options = array(
        'shop_title' => __( 'Shop title', 'annasta-filters' ),
        'orderby' => __( 'Sort by control', 'annasta-filters' ),
        'pagination' => __( 'Pagination', 'annasta-filters' ),
        'default_wc_pagination' => __( 'Default WooCommerce pagination', 'annasta-filters' ),
        'result_count' => __( 'Results count message', 'annasta-filters' ),
        'product_categories' => __( 'Product categories / subcategories', 'annasta-filters' ),
      );

      if( 'no' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
        $options = array( 'awf_preset' => __( 'annasta Filters preset', 'annasta-filters' ) ) + $options;
      }

      return $options;
    }

    public function display_product_list_settings_template_options() {

      $default_options = $this->get_product_list_template_options();
      $template_options = get_option( 'awf_product_list_template_options', array() );
      $ts_classes = array( 'awf-ts', 'awf-ts-2', 'awf-ts-collapsed' );

      if( ! wp_doing_ajax() ) {
        echo
        '<table class="form-table ' . join( ' ', $ts_classes ) . '" style="margin-bottom:0;padding:0 10px 25px;border-bottom: 1px solid #ececec;">',
          '<tr>',
          '<th colspan="2" scope="row" class="awf-info-notice-container"><span class="awf-info-notice">',
            
          wp_kses( __( 'Filters insertion and some other options of this section will only work with <strong>Force wrapper reload</strong> option disabled (see AJAX options > Custom Selectors).', 'annasta-filters' ), array( 'strong' => array() ) ),
        
          '</span></th>',
          '</tr>',
        '</table>'
        ;

      } else {
        if( ( $key = array_search( 'awf-ts-collapsed', $ts_classes ) ) !== false) {
            unset( $ts_classes[$key] );
        }
      }

      echo
        '<table class="widefat awf-template-options-table ' . join( ' ', $ts_classes ) . '">',
        '<thead>'
      ;

      $template_options_select = array( 'id' => 'awf-template-options-select', 'options' => $default_options, 'selected' => null );

      echo
        '<tr>',
        '<th colspan="4" class="awf-add-select-row">',
          A_W_F::$admin->build_select_html( $template_options_select ),
          '<button type="button" id="awf-add-template-option-btn" class="button button-secondary awf-fa-icon-text-btn awf-fa-add-btn" title="', esc_attr__( 'Add to product lists', 'annasta-filters' ), '">', esc_html__( 'Add', 'annasta-filters' ), '</button>',
        '</th>',
        '</tr>',
        '</thead>',
        '<tbody>'
      ;

      foreach( $template_options as $option => $options ) {

        if( ! isset( $default_options[$option] ) ) { continue; }

        foreach( $options as $id => $data ) {
          $setting_id = 'awf_template_option_' . $option . '_' . $id;
          $option_label = '';

          if( 'awf_preset' === $option ) {
            $preset_select = A_W_F::$admin->build_select_html(
              array( 'id' => $setting_id . '_preset_id', 'name' => $setting_id . '_preset', 'options' => $this->get_presets_names(), 'selected' => $data['preset'] )
            );
            $option_label = '<label>' . esc_html( $default_options['awf_preset'] ) . '</label>' . $preset_select;

          } else {
            $option_label = '<label>' . esc_html( $default_options[$option] ) . '</label>';
          }

          $hooks = $this->get_product_list_template_option_hooks( $option );
          $hooks_select = A_W_F::$admin->build_select_html(
            array( 'id' => $setting_id . '_hook', 'class' => 'awf_template_option_active_badges_hook', 'name' => $setting_id . '_hook', 'options' => $hooks, 'selected' => $data['hook'] )
          );

          echo
            '<tr class="awf-template-option-' . str_replace( '_', '-', $option ) . '" data-option="' . $option . '" data-option-id="' . $id . '">',
            '<td class="awf-template-option-name">', $option_label,
            '</td>',
            '<td class="awf-template-option-hook">', $hooks_select, '</td>',
            '<td class="awf-template-option-priority">', 
            '<input name="' . $setting_id . '_priority" type="text" value="' . esc_attr( isset( $data['priority'] ) ? $data['priority'] : '15' ) . '" style="width: 5em;">',
            '</td>',
            '<td class="awf-buttons-column">',
              '<button type="button" class="button button-secondary awf-fa-icon awf-fas-icon awf-fa-delete-btn awf-delete-template-option-btn" title="',
              esc_attr( 'Remove', 'annasta-filters' ), '" data-option="', esc_attr( $option ), '" data-setting-id="', esc_attr( $id ), '"></button>',
            '</td>',
            '</tr>'
            ;
        }
      }

      echo '</tbody>';

      if( 0 < count( $template_options ) ) {
        echo
          '<tfoot>',
          '<tr>',
            '<th></th>',
            '<th class="awf-template-option-hook-label"><i class="fas fa-angle-up"></i>', esc_html__( 'Hook', 'annasta-filters' ), '</th>',
            '<th><i class="fas fa-angle-up"></i>', esc_html__( 'Priority', 'annasta-filters' ), '</th>',
            '<th></th>',
          '</tr>',
          '</tfoot>'
        ;
      }

      echo '</table>';

    }
		
    public function update_product_list_settings() {
      $template_options = get_option( 'awf_product_list_template_options', array() );

      foreach( $template_options as $option => $options ) {

        foreach( $options as $id => $data ) {
          $setting_id = 'awf_template_option_' . $option . '_' . $id . '_';
          $hooks = $this->get_product_list_template_option_hooks( $option );

          if( isset( $_POST[$setting_id . 'hook'] ) && isset( $hooks[$_POST[$setting_id . 'hook']] ) ) {
            $template_options[$option][$id]['hook'] = $_POST[$setting_id . 'hook'];
          }
          
          if( isset( $_POST[$setting_id . 'priority'] ) ) {
            $template_options[$option][$id]['priority'] = (int) $_POST[$setting_id . 'priority'];
          }
          
          if( isset( $_POST[$setting_id . 'preset'] ) ) {
            $template_options[$option][$id]['preset'] = (int) $_POST[$setting_id . 'preset'];
          }
        }
      }

      update_option( 'awf_product_list_template_options', $template_options );
    }
		
    public static function get_awf_custom_style_options() {
      return array(
        'none'        => __( 'Default', 'annasta-filters' ),
        'deprecated-1-3-0'   => __( 'Deprecated since version 1.3.0', 'annasta-filters' ),
      );
		}
    
    public function get_styles_settings() {

      return array(

        10 => array(
          'id' => 'awf_styles_settings_tab',
          'type' => 'title',
          'name' => __( 'Style Settings', 'annasta-filters' ),
        ),
        
        20 => array( 
          'id'       => 'awf_custom_style',
          'type'     => 'select',
          'options'  => self::get_awf_custom_style_options(),
          'name'     => __( 'Filters style', 'annasta-filters' ),
          'default'  => get_option( 'awf_custom_style', 'none' ),
        ),

        30 => array( 
          'id'       => 'awf_pretty_scrollbars', 
          'type'     => 'checkbox',
          'name'     => __( 'Enable pretty scrollbars', 'annasta-filters' ),
          'default'  => get_option( 'awf_pretty_scrollbars', 'no' ),
          'desc_tip' => __( 'In filters with limited height replace the standard browser scrollbars with minimalistic.', 'annasta-filters' ),
        ),
                
        35 => array( 'type' => 'awf_styles_settings_custom_options_1', 'id' => 'awf_styles_settings_custom_options_1' ),

        40 => array( 
          'id'       => 'awf_range_slider_style',
          'type'     => 'select',
          'options'  => A_W_F_admin::get_range_slider_style_options(),
          'name'     => __( 'Range slider style', 'annasta-filters' ),
          'default'  => get_option( 'awf_range_slider_style', 'minimalistic' ),
        ),
        
        50 => array( 
          'id'       => 'awf_color_filter_style',
          'type'     => 'select',
          'options'  => A_W_F_admin::get_color_filter_style_options(),
          'name'     => __( 'Color box style', 'annasta-filters' ),
          'default'  => get_option( 'awf_color_filter_style', 'square' ),
        ),

        70 => array( 
          'id'       => 'awf_fontawesome_font_enqueue',
          'type'     => 'select',
          'options'  => A_W_F_admin::get_fontawesome_font_enqueue_options(),
          'name'     => __( 'Font Awesome support', 'annasta-filters' ),
          'default'  => get_option( 'awf_fontawesome_font_enqueue', 'awf' ),
          'desc_tip' => __( 'Enable Font Awesome support for filters icons. Set to "Disabled" if the full Font Awesome 5 Free support is provided by your theme. The "Extended" option provides basic Font Awesome support for the whole site.', 'annasta-filters' ),
        ),

        80 => array( 
          'id'       => 'awf_excluded_customizer_sections',
          'type'     => 'multiselect',
          'options'  => A_W_F_admin::get_all_customizer_sections(),
          'name'     => __( 'Excluded Customizer sections', 'annasta-filters' ),
          'default'  => get_option( 'awf_excluded_customizer_sections', array() ),
          'desc_tip' => __( 'If your Customizer page gets slow, remove the unneeded sections. The active settings of the removed sections will continue working.', 'annasta-filters' ),
          'class'      => 'chosen_select'
        ),

        array( 'type' => 'sectionend', 'id' => 'awf_styles_settings_tab' ),
      );

    }

    public static function get_all_customizer_sections() {
      $sections = array(
          'awf_general_customizer'          => __( 'General', 'annasta-filters' ),
          'awf_filters_button_customizer'  => __( '"Filters" Toggle Button', 'annasta-filters' ),
          'awf_popup_sidebar_customizer'  => __( 'Popup sidebar', 'annasta-filters' ),
          'awf_preset_title_customizer'  => __( 'Preset Title', 'annasta-filters' ),
          'awf_preset_description_customizer'  => __( 'Preset Description', 'annasta-filters' ),
          'awf_submit_btn_customizer'  => __( 'Submit Button', 'annasta-filters' ),
          'awf_active_badge_customizer'  => __( 'Active Filter Badges', 'annasta-filters' ),
          'awf_reset_btn_customizer'  => __( 'Reset All Button', 'annasta-filters' ),
          'awf_filter_title_customizer'  => __( 'Filter Title', 'annasta-filters' ),
          'awf_filter_label_customizer'  => __( 'Filter Label', 'annasta-filters' ),
          'awf_icons_customizer'  => __( 'Custom Icons', 'annasta-filters' ),
          'awf_search_customizer'  => __( 'String search', 'annasta-filters' ),
          'awf_sliders_customizer'  => __( 'Sliders', 'annasta-filters' ),
      );

      if( A_W_F::$premium ) {
        $sections = A_W_F_premium_admin::add_premium_customizer_sections( $sections );
      }

      return $sections;
    }

    public static function get_customizer_sections() {

      $sections = self::get_all_customizer_sections();
      $exclusions = get_option( 'awf_excluded_customizer_sections', array() );

      $sections = array_diff_key( $sections, array_flip( $exclusions ) );

      return $sections;
    }

    public static function get_range_slider_style_options() {
			return apply_filters( 'awf_range_slider_style_options', array(
				'none'          => __( 'Default', 'annasta-filters' ),
				'rounded'  => __( 'Rounded', 'annasta-filters' ),
				'bars'  => __( 'Bars', 'annasta-filters' ),
        'marker' => __( 'Markers', 'annasta-filters' ),
				'minimalistic'  => __( 'Minimalistic Rounded 3D', 'annasta-filters' ),
			) );
		}

    public static function get_color_filter_style_options() {
			return apply_filters( 'awf_color_filter_style_options', array( 'square' => __( 'Square', 'annasta-filters' ) ) );
		}

    public static function get_loader_style_options() {
			return array(
        0 => __( 'Default', 'annasta-filters' ),
        1 => __( 'Circle 1', 'annasta-filters' ),
        2 => __( 'Circle 2', 'annasta-filters' ),
        3 => __( 'Ripples', 'annasta-filters' ),
        4 => __( 'Arrows', 'annasta-filters' ),
        5 => __( 'Asterisk', 'annasta-filters' ),
        6 => __( 'Hearts', 'annasta-filters' ),
      );
		}

    public static function get_loader_speed_options() {
			return array(
        'vslow' => __( 'Very Slow', 'annasta-filters' ),
        'slow' => __( 'Slow', 'annasta-filters' ),
        'medium' => __( 'Medium', 'annasta-filters' ),
        'fast' => __( 'Fast', 'annasta-filters' ),
        'vfast' => __( 'Very Fast', 'annasta-filters' ),
      );
		}

    public static function get_fontawesome_font_enqueue_options() {
			return array( 'awf' => __( 'Filters only', 'annasta-filters' ), 'yes' => __( 'Extended', 'annasta-filters' ), 'no' => __( 'Disabled', 'annasta-filters' ) );
		}

    public function display_styles_settings_custom_options_1() {
      $customizer_options = get_option( 'awf_customizer_options', array() );

      if( empty( $customizer_options['awf_loader_style'] ) ) { $customizer_options['awf_loader_style'] = '0'; }
      if( empty( $customizer_options['awf_loader_size'] ) ) { $customizer_options['awf_loader_size'] = 50; }
      if( empty( $customizer_options['awf_loader_speed'] ) ) { $customizer_options['awf_loader_speed'] = 'vfast'; }
      if( empty( $customizer_options['awf_loader_color'] ) ) { $customizer_options['awf_loader_color'] = ''; }
      if( empty( $customizer_options['awf_loader_opacity'] ) ) { $customizer_options['awf_loader_opacity'] = '1'; }
      if( empty( $customizer_options['awf_fix_loader'] ) ) { $customizer_options['awf_fix_loader'] = ''; }
      if( empty( $customizer_options['awf_overlay_color'] ) ) { $customizer_options['awf_overlay_color'] = ''; }
      if( empty( $customizer_options['awf_overlay_opacity'] ) ) { $customizer_options['awf_overlay_opacity'] = '0.5'; }

      echo
      '<tr>',
        '<th scope="row" class="titledesc">',
          '<label for="awf_loader_style">', esc_html__( 'AJAX loader', 'annasta-filters' ), '</label>',
        '</th>',
        '<td class="forminp forminp-radio">',
          '<style id="awf-loader-css">',  A_W_F_admin::get_loader_css( $customizer_options ), '</style>',
          '<div id="awf-loader-style-container" class="awf-loader-style-' . $customizer_options['awf_loader_style'] . ' awf-loader-speed-' . $customizer_options['awf_loader_speed'] . '">',
          '<div id="awf-loader-options-container">',
              A_W_F::$admin->build_select_html(
                array(
                  'id' => 'awf-loader-style',
                  'name' => 'awf_loader_style',
                  'options' => A_W_F_admin::get_loader_style_options(),
                  'selected' => (int) $customizer_options['awf_loader_style']
                )
              ),
              '<div id="awf-loader-preview"><div id="awf-overlay-preview"></div></div>',
            '</div>',
            '<div id="awf-loader-extra-options-container">',
              '<span style="margin-bottom: 20px;">',
              '<label for="awf-fix-loader" class="awf-secondary-label">', esc_html__( 'Fix in the center', 'annasta-filters' ), '</label>',
              '<input type="checkbox" id="awf-fix-loader" name="awf_fix_loader" value="1"', ( empty( $customizer_options['awf_fix_loader'] ) ? '' : ' checked' ) , '>',
              '</span>',
              '<span>',
              '<label for="awf-loader-color" class="awf-secondary-label">', esc_html__( 'Loader color', 'annasta-filters' ), '</label>',
              '<input type="text" id="awf-loader-color" name="awf_loader_color" value="',
              esc_attr( $customizer_options['awf_loader_color'] ), 
              '" class="awf-colorpicker" >',
              '</span>',
              '<span>',
              '<label for="awf-loader-opacity" class="awf-secondary-label">', esc_html__( 'Loader opacity', 'annasta-filters' ), '</label>',
              '<input id="awf-loader-opacity" name="awf_loader_opacity" type="number" step=".1" min="0" max="1" value="',
              esc_attr( $customizer_options['awf_loader_opacity'] ), 
              '">',
              '</span>',  
              '<span>',
              '<label for="awf-loader-size" class="awf-secondary-label">', esc_html__( 'Loader size', 'annasta-filters' ), '</label>',
              '<input id="awf-loader-size" name="awf_loader_size" type="number" value="',
              esc_attr( $customizer_options['awf_loader_size'] ), 
              '">',
              '</span>',
              '<span>',
              '<label for="awf-loader-speed" class="awf-secondary-label">', esc_html__( 'Speed', 'annasta-filters' ), '</label>',
              A_W_F::$admin->build_select_html(
                array(
                  'id' => 'awf-loader-speed',
                  'name' => 'awf_loader_speed',
                  'options' => A_W_F_admin::get_loader_speed_options(),
                  'selected' => $customizer_options['awf_loader_speed']
                )
                ),
              '</span>',
              '<span style="margin-top: 20px;">',
              '<label for="awf-overlay-color" class="awf-secondary-label">', esc_html__( 'Overlay color', 'annasta-filters' ), '</label>',
              '<input type="text" id="awf-overlay-color" name="awf_overlay_color" value="',
              esc_attr( $customizer_options['awf_overlay_color'] ), 
              '" class="awf-colorpicker" >',
              '</span>',
              '<span>',
              '<label for="awf-overlay-opacity" class="awf-secondary-label">', esc_html__( 'Overlay opacity', 'annasta-filters' ), '</label>',
              '<input id="awf-overlay-opacity" name="awf_overlay_opacity" type="number" step=".1" min="0" max="1" value="',
              esc_attr( $customizer_options['awf_overlay_opacity'] ), 
              '">',
              '</span>',
            '</div>',
          '</div>',
        '</td>',
      '</tr>'
      ;
    }

    public function get_loader_css( $customizer_options, $full_css = true ) {

      $loader_style = 0;
      if( ! empty( $customizer_options['awf_loader_style'] ) ) { $loader_style = $customizer_options['awf_loader_style']; }

      $css = $options = $animation_speed = '';

      $loader_color = 'var(--awf-loader-color)';
      $loader_size = 'var(--awf-loader-size)';

      if( ! $full_css ) {

        $loader_size =  empty( $customizer_options['awf_loader_size'] ) ? '50px' : $customizer_options['awf_loader_size'] . 'px';
        $loader_color =  empty( $customizer_options['awf_loader_color'] ) ? 'inherit' : $customizer_options['awf_loader_color'];
        $loader_opacity =  empty( $customizer_options['awf_loader_opacity'] ) ? '1' : $customizer_options['awf_loader_opacity'];

        $options = '
          height: ' . $loader_size . ';
          width: ' . $loader_size . ';
          line-height: ' . $loader_size . ';
          font-size: ' . $loader_size . ';
          opacity: ' . $loader_opacity . ';
        ';

        if( ! empty( $customizer_options['awf_loader_color'] ) ) {
          $options .= 'color:' . $customizer_options['awf_loader_color'] . ';';
        }

        $delay = 'animation-delay:-0.375s;';
        if( ! empty( $customizer_options['awf_loader_speed'] ) ) {
          switch( $customizer_options['awf_loader_speed'] ) {
            case 'fast':
              $animation_speed = 'animation-duration:1s;';
              $delay = 'animation-delay:-0.5s';
              break;
            case 'medium':
              $animation_speed = 'animation-duration:1.25s;';
              $delay = 'animation-delay:-0.625s;';
              break;
            case 'slow':
              $animation_speed = 'animation-duration:1.5s;';
              $delay = 'animation-delay:-0.75s;';
              break;
            case 'vslow':
              $animation_speed = 'animation-duration:1.75s;';
              $delay = 'animation-delay:-0.875s;';
              break;
            default:
              break;
          }
        }
      }

      if( $full_css || 0 === $loader_style ) {
        if( ! $full_css ) {
          $margin = empty( $customizer_options['awf_loader_size'] ) ? '-15px' : '-' . ( intval( $customizer_options['awf_loader_size'] ) / 2 ) . 'px';

          $css .= '
          .awf-filterable .blockUI.blockOverlay::before{'. $options . 'margin-top: ' . $margin . ';margin-left: ' . $margin . ';' . $animation_speed . '}';
        }
      }

      if( $full_css || 1 === $loader_style ) {
        if( $full_css ) {
          $css .= '.awf-loader-style-1 #awf-loader-preview::before{content: "\f1ce";}';

        } else {
          $css .= '
          .awf-filterable .blockUI::before{content: "" !important;display:none !important;}
          .awf-filterable .blockUI.blockMsg .awf-loader::before{content: "\f1ce";' . $options . $animation_speed . '}';
        }
      }

      if( $full_css || 2 === $loader_style ) {
        if( 'inherit' === $loader_color ) { $loader_color = '#666666'; }
        
        $style = '
          content: " ";
          display: block;
          border-radius: 50%;
          border: 10px solid ' . $loader_color . ';
          border-color: ' . $loader_color . ' transparent ' . $loader_color . ' transparent;
          animation: fa-spin 0.75s linear infinite;
        ';

        if( $full_css ) {
          $css .= '.awf-loader-style-2 #awf-loader-preview::before{' . $style . '}';

        } else {
          $css .= '
          .awf-filterable .blockUI::before{content: "" !important;display:none !important;}
          .awf-filterable .blockUI.blockMsg .awf-loader::before{' . $style . $options . $animation_speed . '}';
        }
      }

      if( $full_css || 3 === $loader_style ) {
        $style = '
          content: "\f111";
          position: absolute;
          display: block;
          font-weight: normal;
          animation: awf-ripples 0.75s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        ';

        if( $full_css ) {
          $css .= '
            .awf-loader-style-3 #awf-loader-preview::before,
            .awf-loader-style-3 #awf-loader-preview::after{' . $style . '}';
        } else {

          $css .= '
            .awf-filterable .blockUI::before{content: "" !important;display:none !important;}

            .awf-filterable .blockUI.blockMsg .awf-loader::before,
            .awf-filterable .blockUI.blockMsg .awf-loader::after{' . $style . $options . $animation_speed . '}
            .awf-filterable .blockUI.blockMsg .awf-loader::after{' . $delay . '}
          ';
        }
      }

      if( $full_css || 4 === $loader_style ) {
        if( $full_css ) {
          $css .= '.awf-loader-style-4 #awf-loader-preview::before{content: "\f2f1"}';
        } else {
          $css .= '
            .awf-filterable .blockUI::before{content: "" !important;display:none !important;}
            .awf-filterable .blockUI.blockMsg .awf-loader::before{content: "\f2f1";' . $options . $animation_speed . '}';
        }
      }

      if( $full_css || 5 === $loader_style ) {
        if( $full_css ) {
          $css .= '.awf-loader-style-5 #awf-loader-preview::before{content:"\f069";}';
        } else {
          $css .= '
            .awf-filterable .blockUI::before{content: "" !important;display:none !important;}
            .awf-filterable .blockUI.blockMsg .awf-loader::before{content:"\f069";' . $options . $animation_speed . '}';
        }
      }

      if( $full_css || 6 === $loader_style ) {
        $style = '
        content: "\f004";
        position: absolute;
        animation: awf-ripples 0.75s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        ';

        if( $full_css ) {
          $css .= '
            .awf-loader-style-6 #awf-loader-preview::before,
            .awf-loader-style-6 #awf-loader-preview::after {
            ' . $style . '}';
        } else {

          $css .= '
            .awf-filterable .blockUI::before{content:"" !important;display:none !important;}

            .awf-filterable .blockUI.blockMsg .awf-loader::before,
            .awf-filterable .blockUI.blockMsg .awf-loader::after{' . $style . $options . $animation_speed . '}
            .awf-filterable .blockUI.blockMsg .awf-loader::after{' . $delay . '}';
        }
      }

      if( ! $full_css ) {
        if( ! empty( $customizer_options['awf_fix_loader'] ) ) {
          $overlay_color = empty( $customizer_options['awf_overlay_color'] ) ? '#ffffff' : $customizer_options['awf_overlay_color'];
          $overlay_opacity = empty( $customizer_options['awf_overlay_opacity'] ) ? '0.5' : $customizer_options['awf_overlay_opacity'];

          $css .= '
          .awf-filterable .blockUI.blockOverlay{
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            background: ' . $overlay_color . ' !important;
            opacity: ' . $overlay_opacity . ' !important;
          }
          .awf-filterable .blockUI.blockMsg .awf-loader{
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            display: flex !important;
            justify-content: center;
            align-items: center;
          }';
  
        } else {
          if( ! empty( $customizer_options['awf_overlay_color'] ) ) {
            $css .= '.awf-filterable .blockUI.blockOverlay{background:' . $customizer_options['awf_overlay_color'] . ' !important;}';
          }

          if( ! empty( $customizer_options['awf_overlay_opacity'] ) ) {
            $css .= '.awf-filterable .blockUI.blockOverlay{opacity:' . $customizer_options['awf_overlay_opacity'] . ' !important;}';
          }
        }

      }

      return $css;
    }

    public function display_user_css_settings() {
      echo
        '<table class="form-table">',
        '<tbody>',

        '<tr>',
        '<th colspan="2" scope="row" class="awf-info-notice-container"><span class="awf-info-notice">',
					
				sprintf( wp_kses( __( 'For further modification of filters appearance <strong><a href="%1$s">go to the annasta Filters section of Wordpress Customizer</a></strong>.', 'annasta-filters' ), array(  'a' => array( 'href' => array() ), 'strong' => array() ) ), esc_url( admin_url( 'customize.php?autofocus[panel]=annasta-filters' ) ) ),
			
				'</span></th>',
        '</tr>',

        '</tbody>',
        '</table>'
      ;
      echo
        '<table class="form-table">',
        '<tbody>',
				
        '<tr>',
        '<th scope="row" class="titledesc">' , '<label for="awf_user_css">', esc_html__( 'Custom CSS', 'annasta-filters' ), '</label></th>',
        '<td class="forminp forminp-textarea">',
        '<textarea name="awf_user_css" id="awf_user_css" class="awf-code-textarea" placeholder="">',
        stripcslashes( get_option( 'awf_user_css', '' ) ), 
        '</textarea>',
        '</td>',
        '</tr>',

        '</tbody>',
        '</table>'
      ;
    }
    
    public function get_seo_settings() {

      return array(
        array(
          'id' => 'awf_seo_titles_tab',
          'type' => 'title',
          'name' => __( 'SEO Settings', 'annasta-filters' ),
        ),
        
        array( 
          'id'       => 'awf_page_title',
          'type'     => 'select',
          'options'  => array(
            'wc_default'    => __( 'Woocommerce default', 'annasta-filters' ),
            'awf_default'    => __( 'annasta Default title', 'annasta-filters' ),
            'seo'        => __( 'Autogenerated list of annasta filters', 'annasta-filters' ),
          ),
          'name'     => __( 'Page title', 'annasta-filters' ),
          'default'  => get_option( 'awf_page_title', 'wc_default' ),
          'desc_tip' => __( 'Page (HTML document) title can be seen as the name of the page at the top of the browser window (or tab). It is also taken into account by the search engines indexing the pages of your shop.', 'annasta-filters' )
        ),
        
        array( 
          'id'       => 'awf_shop_title',
          'type'     => 'select',
          'options'  => array(
            'wc_default'    => __( 'Woocommerce default', 'annasta-filters' ),
            'awf_default'    => __( 'annasta Default title', 'annasta-filters' ),
            'seo'        => __( 'Autogenerated list of annasta filters', 'annasta-filters' ),
          ),
          'name'     => __( 'Shop title', 'annasta-filters' ),
          'default'  => get_option( 'awf_shop_title', 'wc_default' ),
          'desc_tip' => __( 'The shop page heading above the products list can be left as is, changed with the help of the annasta Default title setting below, or get dynamically adjusted with each filters application, depending on the filters combination.', 'annasta-filters' )
        ),
        
        array( 
          'id'       => 'awf_default_page_title',
          'type'     => 'text',
          'name'     => __( 'Default title', 'annasta-filters' ),
          'default'  => get_option( 'awf_default_page_title', _x( 'Shop', 'Default page title', 'annasta-filters' ) ),
          'desc_tip' => __( 'Choose the "annasta Default title" in the "Page title" or "Shop title" setting to display this string as a title for your shop pages. This title will also be used with the autogenerated filters lists whenever there are no active filters applied to the shop.', 'annasta-filters' ),
        ),
        
        array( 'type' => 'sectionend', 'id' => 'awf_seo_titles_tab' ),
        
        array(
          'id' => 'awf_seo_meta_description_tab',
          'type' => 'title',
          'name' => __( 'Meta Description', 'annasta-filters' ),
        ),

        array( 
          'id'       => 'awf_add_seo_meta_description', 
          'type'     => 'checkbox',
          'name'     => __( 'Add meta description', 'annasta-filters' ),
          'default'  => get_option( 'awf_add_seo_meta_description', 'no' ),
          'desc_tip' => __( 'Add the meta "description" tag to the pages of your shop.', 'annasta-filters' )
        ),
        
        array( 
          'id'       => 'awf_seo_meta_description',
          'type'     => 'textarea',
          'name'     => __( 'Meta description', 'annasta-filters' ),
          'default'  => stripcslashes( trim( get_option( 'awf_seo_meta_description', 'Browse our shop for {annasta_filters}!' ) ) ),
        ),
        
        array( 'type' => 'sectionend', 'id' => 'awf_seo_meta_description_tab' ),
        
        array(
          'id' => 'awf_seo_settings_tab',
          'type' => 'title',
          'name' => __( 'Filters List Generation', 'annasta-filters' ),
        ),
        
        array( 
          'id'       => 'awf_seo_filters_title_prefix',
          'type'     => 'text',
          'name'     => __( 'Filters prefix', 'annasta-filters' ),
          'default'  => get_option( 'awf_seo_filters_title_prefix', 'Shop for' ),
          'desc_tip' => __( 'String to add before the active filters list.', 'annasta-filters' ),
          'css'      => 'width: 200px;'
        ),
        
        array( 
          'id'       => 'awf_seo_filters_separator',
          'type'     => 'text',
          'name'     => __( 'Filters separator', 'annasta-filters' ),
          'default'  => get_option( 'awf_seo_filters_separator', ' - ' ),
          'desc_tip' => __( 'Enter the string that you wish to be used between the different filter groups in the SEO adjusted page and shop title. An example of a title generated using the default value of " - " would be "Fruit, Berries - Red, Green, Purple - Small, Medium".', 'annasta-filters' ),
          'css'      => 'width: 100px;'
        ),
        
        array( 
          'id'       => 'awf_seo_filter_values_separator',
          'type'     => 'text',
          'name'     => __( 'Filter values separator', 'annasta-filters' ),
          'default'  => get_option( 'awf_seo_filter_values_separator', ', ' ),
          'desc_tip' => __( 'Choose a combination of characters to be used between the values of the same filter (for instance, multiple colors selected in a product colors filter). An example of a title created using the default value of ", " would be "Fruit, Berries - Red, Green, Purple - Small, Medium".', 'annasta-filters' ),
          'css'      => 'width: 100px;'
        ),
        
        array( 'type' => 'sectionend', 'id' => 'awf_seo_settings_tab' ),
      );
    }

    public function display_custom_seo_settings() {
      $example_query = (object) array( 'awf' => array(), 'tax' => array() );
      
      $taxonomies = get_object_taxonomies( 'product', 'names' );
      $taxonomies = array_diff( $taxonomies, A_W_F::$excluded_taxonomies );
      
      foreach( $taxonomies as $taxonomy ) {
        $slugs = get_terms( array( 
          'taxonomy' => $taxonomy, 
          'hide_empty' => false,
          'menu_order' => false,
          'orderby' => 'none',
          'fields' => 'slugs',
        ) );

        if( ! is_array( $slugs ) ) { continue; }

        if( 'product_cat' === $taxonomy ) { $slugs = array_diff( $slugs, array( 'uncategorized' ) ); }

        $example_query->tax[$taxonomy] = array_slice( $slugs, 0, rand( 1, 3 ) );
      }
      
      $example_query->awf = array(
        'search' => 'keywords',
        'stock' => 'instock',
        'onsale' => 'yes',
        'featured' => 'yes'
      );
      
      $example_query->range = array(
        'min_price' => '1',
        'max_price' => '50',
        'min_rating' => '3',
        'max_rating' => '5',
      );
      
      ?>
      <table class="widefat awf-seo-page-title-example-table">
        <thead><tr><th><strong><?php esc_html_e( 'Preview for a title with autogenerated filters list', 'annasta-filters' ); ?></strong></th></tr></thead>
        <tbody><tr><td><span><?php echo esc_html( A_W_F::get_seo_title( $example_query ) ); ?></span></td></tr></tbody>
      </table>
      <?php
      
      if( $this instanceof A_W_F_premium_admin ) { $this->display_premium_seo_settings(); }
    }

    public function update_seo_settings() {
            
      $update_filters = false;
      
      if( ! isset( $_POST['awf_default_shop_title'] ) ) {
        update_option( 'awf_default_shop_title', get_option( 'awf_default_page_title', _x( 'Shop', 'Default page title', 'annasta-filters' ) ) );
      } else {
        $update_filters = true;
      }
      
      if( ! isset( $_POST['awf_seo_filters_separator'] ) ) { $_POST['awf_seo_filters_separator'] = ' - '; }
      update_option( 'awf_seo_filters_separator', sanitize_text_field( $this->convert_edge_spaces_to_nbsp( $_POST['awf_seo_filters_separator'] ) ) );
      
      if( ! isset( $_POST['awf_seo_filter_values_separator'] ) ) { $_POST['awf_seo_filter_values_separator'] = ', '; }
      update_option( 'awf_seo_filter_values_separator', sanitize_text_field( $this->convert_edge_spaces_to_nbsp( $_POST['awf_seo_filter_values_separator'] ) ) );
            
      $seo_settings = $this->get_seo_filters_list( $update_filters );
      update_option( 'awf_seo_filters_settings', $seo_settings );
    }
    
    protected function get_seo_filters_list( $update = false ) {
      $seo_settings = array();
      $saved_seo_settings = get_option( 'awf_seo_filters_settings', array() );
      $query_vars = get_option( 'awf_query_vars', array( 'tax' => array(), 'awf' => array(), 'range' => array(), 'meta' => array(), 'misc' => array() ) );
      $position = count( $saved_seo_settings );
      
      foreach( $query_vars['tax'] as $taxonomy => $taxonomy_var_name ) {
        $filter_name = 'taxonomy_' . $taxonomy;
        $this->build_seo_filter_settings( $seo_settings, $saved_seo_settings, $filter_name, $update, $position );
      }
      
      foreach( A_W_F::$modules as $module ) {
        if( in_array( $module, array( 'taxonomy', 'ppp', 'orderby', 'meta' ) ) ) { continue; }
        $this->build_seo_filter_settings( $seo_settings, $saved_seo_settings, $module, $update, $position );
      }
      
      foreach( $query_vars['meta'] as $meta_name => $meta_var_name ) {
        $filter_name = 'meta_filter_' . $meta_name;
        $this->build_seo_filter_settings( $seo_settings, $saved_seo_settings, $filter_name, $update, $position );
        if( ! A_W_F::$premium ) {
          $defaults = $this->get_seo_filter_settings_defaults( $filter_name );
          $seo_settings[$filter_name]['prefix'] = $defaults['prefix'];
        }
      }
      
      uasort( $seo_settings, function( $a, $b ) {
        return $a['position'] - $b['position'];
      });
      
      return apply_filters( 'awf_seo_filters_settings', $seo_settings );
    }
    
    protected function build_seo_filter_settings( &$settings, $saved_settings, $filter_name, $update, &$position ) {
      $settings[$filter_name] = $this->get_seo_filter_settings_defaults( $filter_name );
      
      if( isset( $saved_settings[$filter_name] ) && isset( $saved_settings[$filter_name]['position'] ) ) {
        $settings[$filter_name]['position'] = $saved_settings[$filter_name]['position'];
      } else {
        $settings[$filter_name]['position'] = ++$position;
      }

      if( $update ) {
        if( $this instanceof A_W_F_premium_admin ) { $this->update_seo_filter_settings( $settings[$filter_name], $saved_settings, $filter_name ); }

      } elseif( isset( $saved_settings[$filter_name] ) ) {
        foreach( $settings[$filter_name] as $setting => $value ) {
          if( isset( $saved_settings[$filter_name][$setting] ) ) {
            $settings[$filter_name][$setting] = $saved_settings[$filter_name][$setting];
          }
        }
      }
    }
    
    protected function get_seo_filter_settings_defaults( $module = false ) {
      $filter_defaults = array( 'enabled' => true, 'empty' => '', 'prefix' => '', 'postfix' => '', 'range_separator' => ' - ' );
      
      if( $module ) {
        switch( $module) {
          case 'onsale':
            $filter_defaults['labels'] = array( 'yes' => __( 'on sale', 'annasta-filters' ) );
            break;
          case 'featured':
            $filter_defaults['labels'] = array( 'yes' => __( 'featured', 'annasta-filters' ) );
            break;
          case 'stock':
            $filter_defaults['labels'] = array(
              'instock' => __( 'in stock', 'annasta-filters' ),
              'outofstock' => __( 'out of stock', 'annasta-filters' ),
              'onbackorder' => __( 'on backorder', 'annasta-filters' ),
            );
            break;
          case 'search':
            $filter_defaults['prefix'] = '"';
            $filter_defaults['postfix'] = '"';
            break;
          case 'price':            
            $filter_defaults['prefix'] = __( 'prices ', 'annasta-filters' );
            break;
          case 'rating':
            $filter_defaults['prefix'] = __( 'rating ', 'annasta-filters' );
            $filter_defaults['postfix'] = ' stars';
            break;
          default: break;
        }
      }
      
      if( 0 === strpos( $module, 'meta_filter_' ) ) {
        $meta_name = substr( $module, strlen( 'meta_filter_' ) );

        foreach( A_W_F::$presets as $preset_id => $preset ) {
          foreach( $preset['filters'] as $filter_id => $position ) {
            if( 'meta' === get_option( A_W_F_filter::get_prefix( $preset_id, $filter_id, '' ) . 'module', '' ) ) {
              $filter = new A_W_F_filter( $preset_id, $filter_id );
              if( $filter->settings['meta_name'] === $meta_name && ! empty( $filter->name ) ) {
                $filter_defaults['prefix'] = $filter->settings['title'] . ' ';
                break 2;
              }
            }
          }
        }
      }
      
      return $filter_defaults;
    }

    protected function get_products_max_price() {
      $max_price = 1000000;

      if ( version_compare( WC_VERSION, '3.6', '>=' ) ) {
        global $wpdb;

        $db_row = $wpdb->get_row( "SELECT MAX( max_price ) as max_price FROM {$wpdb->wc_product_meta_lookup}" );

        if( ! empty( $db_row ) && 0 < $db_row->max_price ) {
          if( 1000 < $db_row->max_price ) {
            $max_price = ceil( $db_row->max_price / 100 ) * 100;

          } else {
            $max_price = ceil( $db_row->max_price / 10 ) * 10;
          }
        }
      }
      
      return $max_price;
    }

    protected function get_awf_theme_support_tip() {
      $current_template = strtolower( get_template() );
      $current_theme = wp_get_theme( $current_template );
      $msg = '';
      
      if( file_exists( A_W_F_PLUGIN_PATH . 'code/themes-support/' . sanitize_title( $current_template ) . '.php' ) ) {
        $msg = sprintf( __( 'Enable built-in support for %1$s theme', 'annasta-filters' ), $current_theme->__get( 'name' ) );
      } else {
        $msg = sprintf( __( 'There are no incompatibility issues registered for the %1$s theme.', 'annasta-filters' ), $current_theme->__get( 'name' ) );
      }
      
      switch( $current_template ) {
        case( 'astra' ):
          $msg .= '<br><br><span class="awf-theme-support-notice">';
          $msg .= __( 'Please use the Astra theme Customizer (and not the "Product columns" and "Products per page" settings of the current page) to adjust the amount of shop columns and products per page. annasta Filters will use the theme settings.', 'annasta-filters' );
          $msg .= '</span>';
          break;
        case( 'ecommerce-gem' ):
          $msg .= '<br><br><span class="awf-theme-support-notice">';
          $msg .= __( 'Please use the eCommerce Gem theme Customizer (and not the "Product columns" and "Products per page" settings of the current page) to adjust the amount of shop columns and products per page. annasta Filters will use the theme settings.', 'annasta-filters' );
          $msg .= '</span>';
          break;
        default: break;
      }
      
      return $msg;
    }

    public function get_plugin_settings() {
      $wp_pages_options = array();
      $wp_pages = get_all_page_ids();
      $wc_shop_page = wc_get_page_id( 'shop' );

      foreach( $wp_pages as $page_id ) {
        if( intval( $page_id ) === intval( $wc_shop_page ) ) { continue; }
        $wp_pages_options[$page_id] = get_the_title( $page_id );
      }

      return array(
        
        1 => array(
          'id' => 'awf_plugin_settings_tab_header',
          'type' => 'title',
          'name' => __( 'Plugin Settings', 'annasta-filters' ),
        ),
        2 => array( 'type' => 'sectionend', 'id' => 'awf_plugin_settings_tab_header' ),

        10 => array( 'id' => 'awf_plugin_settings_toggle_btn_section_heading', 'type' => 'title' ),
        11 => array( 'type' => 'awf_settings_ts_header', 'id' => 'awf_plugin_settings_toggle_btn_section_heading_ts_header', 'title' => __( '"Filters" toggle button settings', 'annasta-filters' ), 'class' => 'awf-ts-enforced', 'value' => '1', 'desc_tip' => __( 'The "Filters" toggle button gets enabled by the preset\'s Visibility setting.', 'annasta-filters' ) ),
        12 => array( 'type' => 'sectionend', 'id' => 'awf_plugin_settings_toggle_btn_section_heading' ),

        20 => array(
          'id' => 'awf_plugin_settings_toggle_btn_section',
          'type' => 'title',
          'name' => '',
        ),

        30 => array(
          'id'       => 'awf_toggle_btn_label',
          'type'     => 'text',
          'name'     => __( 'Filters toggle button label', 'annasta-filters' ),
          'default'  => get_option( 'awf_toggle_btn_label', __( 'Filters', 'annasta-filters' ) ),
          'desc_tip' => __( 'Customize label for the "Filters" toggle button.', 'annasta-filters' ),
        ),
        
        40 => array(
          'id'       => 'awf_popup_close_btn_label',
          'type'     => 'text',
          'name'     => __( 'Close togglable popup label', 'annasta-filters' ),
          'default'  => get_option( 'awf_popup_close_btn_label', __( 'Close filters', 'annasta-filters' ) ),
          'desc_tip' => __( 'Customize the label of the togglable popup "Close" button. Leave blank to remove the label.', 'annasta-filters' ),
        ),

        
        45 => array(
          'id'       => 'awf_toggle_btn_position_before',
          'type'     => 'text',
          'name'     => __( 'Position before custom selector', 'annasta-filters' ),
          'default'  => get_option( 'awf_toggle_btn_position_before', '' ),
          'desc_tip' => __( 'Leave blank to auto-insert the button above product lists.</br></br>The custom selector should be unique. To ensure this, it is preferrable to use the element ID (for example: #selector). You can also use a unique class (example: .class) or the combination of classes (.class-1.class-2.class-3).', 'annasta-filters' ),
        ),

        46 => array( 'type' => 'awf_plugin_settings_toggle_btn_customizer_options', 'id' => 'awf_plugin_settings_toggle_btn_customizer_options' ),

        49 => array( 'type' => 'sectionend', 'id' => 'awf_plugin_settings_toggle_btn_section' ),

        50 => array(
          'id' => 'awf_plugin_settings_tab',
          'type' => 'title',
          'name' => '',
        ),

        60 => array(
          'id'       => 'awf_shortcode_pages',
          'type'     => 'multiselect',
          'options'     => $wp_pages_options,
          'name'     => __( 'Filter [products] shortcodes on', 'annasta-filters' ),
          'default'  => get_option( 'awf_shortcode_pages', array() ),
          'desc_tip' => __( 'Declare the pages with WooCommerce [products] shortcodes that you wish to filter. Filters placed on undeclared pages will redirect to the main WooCommerce shop page.', 'annasta-filters' ),
          'class'      => 'chosen_select'
        ),
        
        80 => array(
          'id'       => 'awf_dynamic_price_ranges',
          'type'     => 'checkbox',
          'name'     => __( 'Dynamic price sliders', 'annasta-filters' ),
          'default'  => get_option( 'awf_dynamic_price_ranges', 'no' ),
          'desc_tip' => __( 'Recalculate price slider ranges for each filters combination.', 'annasta-filters' )
        ),
        
        85 => array(
          'id'       => 'awf_ss_engine',
          'type'     => 'select',
          'options'  => array(
            'wc' => __( 'WooCommerce default', 'annasta-filters' ),
            'relevanssi' => 'Relevanssi',
            'aws' => 'Advanced Woo Search'
          ),
          'name'     => __( 'Preferred string search engine', 'annasta-filters' ),
          'default'  => get_option( 'awf_ss_engine', 'wc' ),
          'desc_tip' => __( 'This setting will affect the work of the annasta Product Search controls and their autocompletes.<br><br>The selected 3d party search engine will be called as long as its respective plugin is activated. String search will default to the native WooCommerce search whenever the preferred 3d party plugin is not found.', 'annasta-filters' )
        ),

        90 => array(
          'id'       => 'awf_include_parents_in_associations',
          'type'     => 'checkbox',
          'name'     => __( 'Display parent presets on child pages', 'annasta-filters' ),
          'default'  => get_option( 'awf_include_parents_in_associations', 'yes' ),
          'desc_tip' => __( 'Enable the display of filter presets associated with term\'s parents on child term pages. For example, in a case of Clothes > Jeans category > subcategory structure, enabling this option will make all the presets associated with Clothes category also display for Jeans subcategory.', 'annasta-filters' )
        ),

        100 => array(
          'id'       => 'awf_include_children_on',
          'type'     => 'checkbox',
          'name'     => __( 'Include subterms\' products', 'annasta-filters' ),
          'default'  => get_option( 'awf_include_children_on', 'yes' ),
          'desc_tip' => __( 'When a parent term of a hierarchical taxonomy is selected, include products belonging to its children terms (for example, subcategories) in the filtered results.', 'annasta-filters' )
        ),

        110 => array(
          'id'       => 'awf_variations_stock_support',
          'type'     => 'checkbox',
          'name'     => __( 'Stock filter variations support', 'annasta-filters' ),
          'default'  => get_option( 'awf_variations_stock_support', 'no' ),
          'desc_tip' => __( 'Enable stock filter support for variable products. WARNING: this beta option may slow down products display on sites with many products and/or slow servers.', 'annasta-filters' )
        ),

        120 => array(
          'id'       => 'awf_get_parameters_support',
          'type'     => 'checkbox',
          'name'     => __( '3d party parameters support', 'annasta-filters' ),
          'default'  => get_option( 'awf_get_parameters_support', 'no' ),
          'desc_tip' => __( 'Include non-filtering URL parameters (analytics, language etc) in filters\' URLs.', 'annasta-filters' )
        ),

        130 => array(
          'id'       => 'awf_hierarchical_archive_permalinks',
          'type'     => 'checkbox',
          'name'     => __( 'Hierarchical archive links', 'annasta-filters' ),
          'default'  => get_option( 'awf_hierarchical_archive_permalinks', 'no' ),
          'desc_tip' => __( 'Use hierarchical permalinks with paths of type "category/subcatergory/sub-subcategory" in same-taxonomy filters working on archive pages (for example, product categories filter on a category archive page). WARNING: this option only supports single item selection for the same-taxonomy filters of their respective archive pages. On such pages all multi-select filters will be forced into the single-select mode.', 'annasta-filters' )
        ),

        140 => array(
          'id'       => 'awf_redirect_archives', 
          'type'     => 'checkbox',
          'name'     => __( 'Redirect archives to shop', 'annasta-filters' ),
          'default'  => get_option( 'awf_redirect_archives', 'no' ),
          'desc_tip' => __( 'Force the redirection of all the archive pages for the products-related taxonomies (such as categories, tags, brands) to the shop page with the corresponding taxonomy filter applied. For example, a product tag page will be redirected from https://mysite.com/tags/tag-1 to https://mysite.com/shop/?product-tags=tag-1). WARNING: this option is not supported by some themes.', 'annasta-filters' )
        ),

        150 => array(
          'id'       => 'awf_force_products_display_on', 
          'type'     => 'checkbox',
          'name'     => __( 'Force products display', 'annasta-filters' ),
          'default'  => get_option( 'awf_force_products_display_on', 'yes' ),
          'desc_tip' => __( 'Disable the built-in WooCommerce categories and subcategories display. This display is not supported by the filters AJAX mode. Go to annasta Filters > Product lists > <strong>Add elements</strong> to add the categories/subcategories block supported by the filters.', 'annasta-filters' )
        ),
        
				190 => array( 'type' => 'awf_custom_awf_plugin_settings', 'id' => 'awf_custom_awf_plugin_settings' ),

        199 => array( 'type' => 'sectionend', 'id' => 'awf_plugin_settings_tab' ),

      );
    }
        
    public function display_plugin_settings_toggle_btn_customizer_options() {

      $customizer_options = get_option( 'awf_customizer_options', array() );

      echo
        '<tr>',
          '<th scope="row" class="titledesc">',
            '<label for="awf_filters_button_fixed_position">', esc_html__( 'Fix page position (px)', 'annasta-filters' ), '</label>',
            '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Configure the floating "Filters" button.</br></br>Set only 2 of the Top/Right/Bottom/Left values and leave the rest blank. For example, to fix the button position in the bottom right corner of the page, set the Right and the Bottom to 50, and leave the Left and the Top fields empty.', 'annasta-filters' ) . '"></span>',
          '</th>',
          '<td class="forminp forminp-checkbox">',
            '<input id="awf_filters_button_fixed_position" name="awf_customizer_options[awf_filters_button_fixed_position]" type="checkbox" value="1"',
            ( ( isset( $customizer_options['awf_filters_button_fixed_position'] ) &&  ('yes' === $customizer_options['awf_filters_button_fixed_position'] ) ) ? ' checked' : '' ), 
            '>',

            '<div id="awf-filters-button-fixed-position-coordinates-container" class="awf-collapsed">',
              '<span style="margin-bottom:5px;">',
              '<label for="awf_filters_button_fixed_top" class="awf-secondary-label awf-top-secondary-label">', esc_html__( 'Top', 'annasta-filters' ), '</label>',
              '<input id="awf_filters_button_fixed_top" name="awf_filters_button_fixed_top" type="number" style="width:80px;margin-right:10px;" value="',
              esc_attr( ( isset( $customizer_options['awf_filters_button_fixed_top'] ) && '' !== $customizer_options['awf_filters_button_fixed_top'] ) ? intval( $customizer_options['awf_filters_button_fixed_top'] ) : '' ), 
              '">',
              '</span>',
              '<span style="margin-bottom:5px;">',
              '<label for="awf_filters_button_fixed_right" class="awf-secondary-label awf-top-secondary-label">', esc_html__( 'Right', 'annasta-filters' ), '</label>',
              '<input id="awf_filters_button_fixed_right" name="awf_filters_button_fixed_right" type="number" style="width:80px;margin-right:10px;" value="',
                esc_attr( ( isset( $customizer_options['awf_filters_button_fixed_right'] ) && '' !== $customizer_options['awf_filters_button_fixed_right'] ) ? intval( $customizer_options['awf_filters_button_fixed_right'] ) : '' ), 
              '">',
              '</span>',
              '<span style="margin-bottom:5px;">',
              '<label for="awf_filters_button_fixed_bottom" class="awf-secondary-label awf-top-secondary-label">', esc_html__( 'Bottom', 'annasta-filters' ), '</label>',
              '<input id="awf_filters_button_fixed_bottom" name="awf_filters_button_fixed_bottom" type="number" style="width:80px;margin-right:10px;" value="',
              esc_attr( ( isset( $customizer_options['awf_filters_button_fixed_bottom'] ) && '' !== $customizer_options['awf_filters_button_fixed_bottom'] ) ? intval( $customizer_options['awf_filters_button_fixed_bottom'] ) : '' ), 
              '">',
              '</span>',
              '<span style="margin-bottom:5px;">',
              '<label for="awf_filters_button_fixed_left" class="awf-secondary-label awf-top-secondary-label">', esc_html__( 'Left', 'annasta-filters' ), '</label>',
              '<input id="awf_filters_button_fixed_left" name="awf_filters_button_fixed_left" type="number" style="width:80px;margin-right:10px;" value="',
              esc_attr( ( isset( $customizer_options['awf_filters_button_fixed_left'] ) && '' !== $customizer_options['awf_filters_button_fixed_left'] ) ? intval( $customizer_options['awf_filters_button_fixed_left'] ) : '' ), 
              '">',
              '</span>',
            '</div>',
          '</td>',
        '</tr>',

        '<tr>',
        '<td colspan="2" scope="row" style="font-size:.9em;">',
        '<div id="awf-filters-btn-settings-footer-notices">',
          '<div>',
            '<i class="dashicons dashicons-admin-appearance"></i>',
            '<span>',
            sprintf( wp_kses( __( '<a href="%1$s">Click here to customize the button appearance in the Wordpress Customizer</a>.', 'annasta-filters' ), array(  'a' => array( 'href' => array() ), 'strong' => array() ) ), esc_url( admin_url( 'customize.php?autofocus[section]=awf_filters_button_customizer' ) ) ),
            '</span>',
          '</div>',
          '<div>',
            '<i class="fas fa-book"></i>',
            '<span>',
            sprintf( wp_kses( __( '<a href="%1$s" target="_blank">Read about the "Filters" button settings</a> in plugin Documentation.', 'annasta-filters' ), array(  'a' => array( 'href' => array(), 'target' => array() ), 'strong' => array() ) ), esc_url( 'https://annasta.net/plugins/annasta-woocommerce-product-filters/documentation/plugin-settings/filters-toggle-button-settings/' ) ),
            '</span>',
          '</div>',

          '<div id="awf_toggle_btn_position_before_container">',
            '<div id="awf-filters-btn-shortcode-notice">',
              '<i class="fas fa-info-circle"></i>',
              '<span id="awf-filters-btn-shortcode" title="', esc_attr_e( 'Click to copy shortcode to clipboard', 'annasta-filters' ), '" data-tip="', esc_attr__( 'Shortcode copied to clipboard', 'annasta-filters' ), '">',
              sprintf( wp_kses( __( 'You can also use the %1$s shortcode to customize the "Filters" button insertion point.', 'annasta-filters' ), array( 'strong' => array() ) ), '<strong>[annasta_filters_toggle_button]</strong>' ),
              '</span>',
            '</div>',
          '</div>',
        '</div>',
        '</td>',
        '</tr>'
      ;

    }

    public function display_custom_awf_plugin_settings() {
      echo
        '<table class="form-table">',
        '<tbody>',

        '<tr>',
        '<th scope="row" class="titledesc">' , '<label for="awf_user_js">', esc_html__( 'Custom Javascript', 'annasta-filters' ), '</label></th>',
        '<td class="forminp forminp-textarea">',
        '<textarea name="awf_user_js" id="awf_user_js" class="awf-code-textarea" placeholder="">',
        stripcslashes( get_option( 'awf_user_js', '' ) ), 
        '</textarea>',
        '</td>',
        '</tr>',

        '<tr>',
        '<th scope="row" class="titledesc">' ,
        '<label for="awf_counts_cache_days">', esc_html__( 'Product counts cache lifespan', 'annasta-filters' ),
        '<span class="woocommerce-help-tip" data-tip="', esc_html__( 'Amount of days to keep the product counts cache transients in the database. Set to 0 to completely disable any cache produced by filters, including the Woocommerce products loop cache created during AJAX calls.', 'annasta-filters' ), '"></span>',
        '</label></th>',
        '<td class="forminp forminp-number">',
        '<input name="awf_counts_cache_days" id="awf_counts_cache_days" type="number" style="width: 60px;margin-right: 50px;" value="', get_option( 'awf_counts_cache_days', '10' ), '">',
        '<button type="button" id="awf_clear_awf_cache_btn" class="button button-secondary awf-fa-icon-text-btn awf-s-btn awf-fa-eraser-btn">', esc_html__( 'Clear filters cache', 'annasta-filters' ), '</button>',
        '</td>',
        '</tr>',

        '</tbody>',
        '</table>'
      ;
    }

    public function generate_styles_css() {

      $languages = array();

      if( class_exists( 'SitePress' ) ) {
        $languages = apply_filters( 'wpml_active_languages', NULL );
        $current_language = apply_filters( 'wpml_current_language', NULL );

        if( is_array( $languages ) ) {
          $languages = array_keys( $languages );
          $languages = array_diff( $languages, array( $current_language ) );

        } else {
          $languages = array();
        }
      }

      $css = '/* annasta Woocommerce Product Filters autogenerated style options css */';
			
      /*
			if( 'yes' === get_option( 'awf_remove_wc_shop_title', 'no' ) ) {
				$css .= '.woocommerce-products-header__title{display:none;}';
			}
      */

			if( 'yes' === get_option( 'awf_remove_wc_orderby', 'no' ) ) {
				$css .= '.woocommerce-ordering{display:none;}';
			}
      
      if( $this instanceof A_W_F_premium_admin ) { $css .= $this->generate_premium_css(); }

      foreach( A_W_F::$presets as $preset_id => $preset ) {
        
        $display_mode = get_option( 'awf_preset_' . $preset_id . '_display_mode', 'visible' );
        switch( $display_mode ) {
          case 'visible-on-s':
            $responsive_width = (int) get_option( 'awf_preset_' . $preset_id . '_responsive_width', '768' );
            if( ! empty( $responsive_width ) ) {
              $css .= '@media(min-width:' . $responsive_width . 'px){.awf-preset-wrapper.awf-preset-' . $preset_id . '-wrapper{display:none;}}';
            }
            
            break;
          case 'visible-on-l':
            $responsive_width = (int) get_option( 'awf_preset_' . $preset_id . '_responsive_width', '768' );
            if( ! empty( $responsive_width ) ) {
              $css .= '@media(max-width:' . $responsive_width . 'px){.awf-preset-wrapper.awf-preset-' . $preset_id . '-wrapper{display:none;}}';
            }

            break;
          case 'togglable':
            $css .= '.awf-preset-wrapper.awf-preset-' . $preset_id . '-wrapper{opacity:0;}';

            break;
          case 'togglable-on-s':
            $responsive_width = (int) get_option( 'awf_preset_' . $preset_id . '_responsive_width', '768' );
            $css .= '@media(max-width:' . $responsive_width . 'px){.awf-preset-wrapper.awf-preset-' . $preset_id . '-wrapper{opacity:0;}body:not(.awf-filterable) .awf-preset-wrapper.awf-preset-' . $preset_id . '-wrapper{display:none;}}';

            break;
          default: break;
        }
        
        foreach( $preset['filters'] as $filter_id => $position ) {

          $filter = new A_W_F_filter( $preset_id, $filter_id );

          if( ! empty( $filter->settings['height_limit'] ) ) {
            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container {position:relative;';
            if( empty( $filter->settings['shrink_height_limit'] ) ) {
              $css .= 'height:';
            } else {
              $css .= 'max-height:';
            }
            $css .= $filter->settings['height_limit'] . 'px;overflow:hidden;';
            if ( 'yes' !== get_option( 'awf_pretty_scrollbars' ) && ! ( ! empty( $filter->settings['style_options']['height_limit_style'] ) && 'toggle' === $filter->settings['style_options']['height_limit_style'] ) ) { $css .= 'padding-right:0.5em;overflow-y:auto;'; }
            $css .= '}';
          }

          if( 'icons' === $filter->settings['style'] && isset( $filter->settings['style_options']['icons'] ) ) {

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons label::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][0] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][0] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:400;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-filter-container:not(.awf-hover-off) label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][1] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][1] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:400;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-filter-container.awf-empty-disabled.awf-empty label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][0] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][0] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:400;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-active label::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][2] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][2] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:400;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-hierarchical-sbs-active-parent label::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][2] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][2] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:400;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-active:not(.awf-hover-off) label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][3] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][3] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:400;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-hierarchical-sbs-active-parent:not(.awf-hover-off) label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][3] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][3] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:400;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-active.awf-empty-disabled.awf-empty label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][2] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][2] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:400;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-hierarchical-sbs-active-parent.awf-empty-disabled.awf-empty label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][2] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][2] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:400;'; }
            $css .= '}';

          } else if( 'colours' === $filter->settings['style'] ) {
            if( isset( $filter->settings['style_options']['colours'] ) ) {
              foreach( $filter->settings['style_options']['colours'] as $term_id => $colour ) {
                $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container label.awf-term-' . $term_id . '::after {';
                $css .= 'background-color:' . $colour . ';}';
                if( ! empty( $languages ) && isset( $filter->settings['taxonomy'] ) ) {
                  foreach( $languages as $language ) {
                    $language_term_id = apply_filters( 'wpml_object_id', $term_id, $filter->settings['taxonomy'], TRUE, $language );
                    if( $language_term_id !== $term_id ) {
                      $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container label.awf-term-' . $language_term_id . '::after {';
                      $css .= 'background-color:' . $colour . ';}';
                    }
                  }
                }
              }
            }
          }
          
          if( $this instanceof A_W_F_premium_admin ) { $css .= $this->generate_premium_filter_css( $filter, $languages ); }
        }
      }
			
			$css .= $this->generate_customizer_css();
      
      $user_css = stripcslashes( trim( get_option( 'awf_user_css', '' ) ) );
      
      if(! empty( $user_css ) ) {
        $css .= '/* User CSS */';
        $css .= $user_css;
      }
      
      $awf_uploads_folder = trailingslashit( wp_upload_dir()['basedir'] ) . 'annasta-filters/css';
      if( wp_mkdir_p( $awf_uploads_folder ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        $old_files = list_files( $awf_uploads_folder, 1 );
        if( $old_files ) {
          foreach( $old_files as $file ) {
            unlink( $file );
          }
        }
        
        $filename = 'style-options-' . time() . '.css';
        file_put_contents( trailingslashit( $awf_uploads_folder ) . $filename, $css );
        update_option( 'awf_style_options_file', $filename );
      }
    }
		
    /**
     * Generate custom CSS based on Customizer settings
     *
     * @param boolean|array $options
     * @return void
     */
    protected function generate_customizer_css( $options = false ) {
			
      $customizer_preview = false;
			$css = '';
			$style = ( false !== $options && isset( $options['awf_custom_style'] ) ) ? $options['awf_custom_style'] : get_option( 'awf_custom_style', 'none' );

			if( false === $options ) {
				$options = get_option( 'awf_customizer_options', array() );
        $options = array_filter( $options, function( $v ) {
          return ( ! is_null( $v ) && $v !== '' );
        } );
				
			} else {
				/* Customizer AJAX regeneration */
				
        $customizer_preview = true;
				$current_options = get_option( 'awf_customizer_options', array() );
        $current_options = array_filter( $current_options, function( $v ) {
          return ( ! is_null( $v ) && $v !== '' );
        } );
				$default_options = self::get_awf_custom_style_defaults( $style );

				foreach( $options as $option => $value ) {
					if( 'true' === $value ) { $options[$option] = 'yes'; }
					elseif( 'false' === $value ) { $options[$option] = ''; }
					
					if( '' === $value && isset( $current_options[$option] ) && isset( $default_options[$option] ) ) {
						$options[$option] = $default_options[$option];
					}
				}

        if( ! empty( $options['awf_range_slider_style'] ) ) {
          update_option( 'awf_range_slider_style', $options['awf_range_slider_style'] );
        }
			}
			
      $css .= $this->get_loader_css( $options, false );

      if( $customizer_preview ) {
        $css .='
        .awf-filters-container .awf-filter-container.awf-range-slider-container,.awf-filters-container .awf-taxonomy-range-slider-container{font-size:inherit;}
        .noUi-horizontal {
          height: 18px;
        }
        .noUi-target {
          background: #FAFAFA;
          border-radius: 4px;
          border: 1px solid #D3D3D3;
          box-shadow: inset 0 1px 1px #F0F0F0, 0 3px 6px -5px #BBB;
        }
        .noUi-horizontal .noUi-handle {
          width: 34px;
          height: 28px;
          right: -17px;
          top: -6px;
          background: #FFF;
          border: 1px solid #D9D9D9;
          border-radius: 3px;
          cursor: default;
          box-shadow: inset 0 0 1px #FFF, inset 0 1px 7px #EBEBEB, 0 3px 6px -3px #BBB;
        }
        .noUi-horizontal .noUi-handle::before,
        .noUi-horizontal .noUi-handle::after {
          content: "";
          display: block;
          position: absolute;
          margin: 0;
          height: 14px;
          width: 1px;
          background: #E8E7E6;
          left: 14px;
          top: 6px;
          border: none;
        }
        .noUi-horizontal .noUi-handle::after {
          left: 17px;
        }
        .noUi-pips-horizontal {
          margin-top: 0;
          padding: 10px 0;
          height: 80px;
          top: 100%;
          left: 0;
          width: 100%;
        }
        .noUi-connect {
          background: #3FB8AF;
        }
        .noUi-marker-horizontal.noUi-marker {
          background: #CCC;
        }
        .noUi-marker-horizontal.noUi-marker {
          margin-left: -1px;
          width: 2px;
          height: 5px;
        }
        .noUi-marker-horizontal.noUi-marker-normal {
          display: block;
        }
        .noUi-marker-horizontal.noUi-marker-large {
          height: 15px;
          background: #AAA;
          border-radius:0;
        }
        .noUi-value{ margin-top: 0; }
        .noUi-horizontal .noUi-tooltip {
          display: block;
          bottom: 120%;
          left: 50%;
          margin-top: 0;
          margin-bottom: 0;
          padding: 5px;
          line-height:inherit;
          color: #000;
          background: #fff;
          border: 1px solid #D9D9D9;
          border-radius: 3px;
        }
        .noUi-horizontal .noUi-tooltip::before{ display:none; }
        ';
      }

			$value = isset( $options['awf_range_slider_style'] ) ? $options['awf_range_slider_style'] : get_option( 'awf_range_slider_style', 'minimalistic' );

      switch( $value ) {
        case 'minimalistic':
          $css .= '.noUi-horizontal{height:3px;}.noUi-horizontal .noUi-handle{top:-15px;width:29px;height:28px;box-shadow:inset 0 0 1px #FFF,inset 0 1px 7px #EBEBEB,0 3px 6px -3px #BBB;border:1px solid #D9D9D9;border-radius:50%;}.noUi-horizontal .noUi-handle::before,.noUi-horizontal .noUi-handle::after{display:none;}.noUi-pips-horizontal{margin-top:-13px;}.noUi-marker-horizontal.noUi-marker-large{height:5px;width:5px;border-radius:50%;background:#3FB8AF;}.noUi-marker-horizontal.noUi-marker-normal{display:none;}';

          break;

        case 'rounded':
          $css .= '.awf-filters-container .awf-filter-container.awf-range-slider-container,.awf-filters-container .awf-taxonomy-range-slider-container{font-size:12px;}.noUi-horizontal{height:3px;}.noUi-connect{background:#eb2222;}.noUi-horizontal .noUi-handle{top:-8.5px;right:-10px;height:20px;width:20px;background:#666;box-shadow:none;border:none;border-radius:50%;}.noUi-horizontal .noUi-handle::before,.noUi-horizontal .noUi-handle::after{display:none;}.noUi-pips-horizontal{margin-top:5px;}.noUi-marker-horizontal.noUi-marker-large{height:10px;}.noUi-horizontal .noUi-tooltip{margin-bottom:5px;line-height:12px;background:#fbfbfb;}.noUi-horizontal .noUi-tooltip::before{content:"\f0d7";position:absolute;display:block;top:auto;bottom:0;left:50%;margin-bottom:-7.5px;transform:translate(-50%, 0);line-height:12px;color:#ccc;font-family: "AWF FA","Font Awesome 5 Free";font-size:12px;font-weight:900;}';
          $css .= '.awf-slider-tooltips-below{margin-top:40px;}';

          break;

        case 'bars':
          $css .= '.awf-filters-container .awf-filter-container.awf-range-slider-container,.awf-filters-container .awf-taxonomy-range-slider-container{font-size:12px;}.noUi-horizontal{height:10px;}.noUi-target{border-radius:0;box-shadow:none;}.noUi-connects{border-radius:0;}.noUi-connect{background:#eb2222;}.noUi-horizontal .noUi-handle{border:none;background:transparent;box-shadow:none;}.noUi-horizontal .noUi-handle::before{content:"";display:block;position:absolute;margin:-3px 0 0 2px;width:3px;height:14px;background:#eb2222;border:none;box-shadow:none;z-index:1;}.noUi-horizontal .noUi-handle::after{display:none;}.noUi-pips-horizontal{margin-top:-2px;}.noUi-marker-horizontal.noUi-marker-large{height:10px;}.noUi-horizontal .noUi-tooltip{margin-bottom:5px;line-height:12px;background:#fbfbfb;}.noUi-horizontal .noUi-tooltip::before{content:"\f0d7";position:absolute;display:block;top:auto;bottom:0;left:50%;margin-bottom:-7.5px;transform:translate(-50%, 0);line-height:12px;color:#ccc;font-family: "AWF FA","Font Awesome 5 Free";font-size:12px;font-weight:900;}';
          $css .= '.awf-slider-tooltips-below{margin-top:40px;}';

          break;

          case 'marker':
            $css .= '.awf-filters-container .awf-filter-container.awf-range-slider-container,.awf-filters-container .awf-taxonomy-range-slider-container{font-size:12px;}.noUi-horizontal{height:3px;}.noUi-target{border-radius:0;box-shadow:none;}.noUi-connects{border-radius:0;}.noUi-connect{background:#eb2222;}
            .noUi-horizontal .noUi-handle{top:-7px;right:-6px;width:12px;height:16px;background:#666;border:none;box-shadow:none;}
            .noUi-horizontal .noUi-handle::before{display: none;}
            .noUi-horizontal .noUi-handle::after{content:"\f0d7";top:11px;left:0;width:0px;line-height:12px;color:#666;font-family: "AWF FA", "Font Awesome 5 Free";font-size:19px;font-weight:900;text-shadow:none;border:none;background:none;box-shadow:none;}
            .noUi-pips-horizontal{margin-top:5px;}.noUi-marker-horizontal.noUi-marker-large{height:10px;}.noUi-horizontal .noUi-tooltip{margin-bottom:5px;line-height:12px;background:#fbfbfb;}.noUi-horizontal .noUi-tooltip::before{content:"\f0d7";position:absolute;display:block;top:auto;bottom:0;left:50%;margin-bottom:-7.5px;transform:translate(-50%, 0);line-height:12px;color:#ccc;font-family: "AWF FA","Font Awesome 5 Free";font-size:12px;font-weight:900;}'
            ;
          $css .= '.awf-slider-tooltips-below{margin-top:40px;}';

          break;

        case 'none':
          $css .= '.awf-slider-tooltips-below{margin-top:40px;}';

          break;

        default: break;
      }
			
			if( $this instanceof A_W_F_premium_admin ) { $css .= $this->generate_premium_customizer_css( $options ); }
			
			/* Continue only if the current style supports Customizer */
			if( 'none' !== $style ) { return $css; }

      if( isset( $options['awf_sliders_sf_color'] ) ) {
        $css .= '.noUi-pips,.noUi-tooltip{color:' . $this->sanitize_css_color( $options['awf_sliders_sf_color'] ) . ';}';
      }

      if( isset( $options['awf_sliders_slider_font_size'] ) ) {
        $css .= '.awf-filters-container .awf-filter-container.awf-range-slider-container,.awf-filters-container .awf-taxonomy-range-slider-container{font-size:' . $this->absint_or_string_maybe_to_px( $options['awf_sliders_slider_font_size'] ) . ';}';
        if( is_numeric( $options['awf_sliders_slider_font_size'] ) ) {
          $css .= '.noUi-horizontal .noUi-tooltip{line-height:' . $this->absint_or_string_maybe_to_px( $options['awf_sliders_slider_font_size'] ) . ';}';
        }
      }

      if( isset( $options['awf_sliders_sb_color'] ) ) {
        $css .= '.noUi-target{background:' . $this->sanitize_css_color( $options['awf_sliders_sb_color'] ) . ';}';
      }

      if( isset( $options['awf_sliders_slider_color'] ) ) {
        $css .= '.noUi-connect{background:' . $this->sanitize_css_color( $options['awf_sliders_slider_color'] ) . ';}';
      }

      if( ! empty( $options['awf_sliders_sp_color'] ) ) {
        $css .= '.noUi-marker-horizontal.noUi-marker-large{background:' . $this->sanitize_css_color( $options['awf_sliders_sp_color'] ) . ';}';
        $css .= '.noUi-marker-horizontal.noUi-marker-normal{background:' . $this->change_color_transparency( $options['awf_sliders_sp_color'], -0.4 ) . ';}';
      }

      if( ! empty( $options['awf_sliders_st_color'] ) ) {
        $css .= '.noUi-horizontal .noUi-tooltip{color:' . $this->sanitize_css_color( $options['awf_sliders_st_color'] ) . ';}';
      }

      if( ! empty( $options['awf_sliders_st_background_color'] ) ) {
        $color = $this->sanitize_css_color( $options['awf_sliders_st_background_color'] );
        $css .= '.noUi-horizontal .noUi-tooltip{background:' . $color . ';}';

        if( ! ( $customizer_preview && $options['awf_sliders_st_background_color'] === $default_options['awf_sliders_st_background_color'] ) ) {
          $css .= '.noUi-horizontal .noUi-tooltip{border-color:' . $color . ';}.noUi-horizontal .noUi-tooltip::before{color:' . $color . ';}';
        }
      }

      if( isset( $options['awf_sliders_width'] ) ) {
        $css .= '.awf-filters-container .awf-filter-container.awf-range-slider-container,.awf-filters-container .awf-taxonomy-range-slider-container{width:' . $this->absint_or_string_maybe_to_percent( $options['awf_sliders_width'] ) . ';}';

        if( $customizer_preview && $options['awf_sliders_width'] === $default_options['awf_sliders_width'] ) {
          $css .= '.awf-filters-container .awf-filter-container.awf-range-slider-container,.awf-filters-container .awf-taxonomy-range-slider-container{margin-left:1.5em;margin-right:1.5em;}';
        } else {
          $css .= '.awf-filters-container .awf-filter-container.awf-range-slider-container,.awf-filters-container .awf-taxonomy-range-slider-container{margin-left:auto;margin-right:auto;}@media (max-width: 1024px){.awf-filters-container .awf-filter-container.awf-range-slider-container,.awf-filters-container .awf-taxonomy-range-slider-container{margin: 70px auto;transform:scale(1);}.awf-interactive-slider-tooltips-container{transform:scale(1);}}';
        }
      }

      if( ! empty( $options['awf_sliders_sh_color'] ) ) {
        $color = $this->sanitize_css_color( $options['awf_sliders_sh_color'] );

        switch( $value ) {
          case 'bars':
          case 'minimalistic_bars':
            $css .= '.noUi-horizontal .noUi-handle::before{background:' . $color . ';}';
            break;
          case 'marker':
            $css .= '.noUi-horizontal .noUi-handle{background:' . $color . ';}.noUi-horizontal .noUi-handle::after{color:' . $color . ';}';
            break;
          case 'none':
          case 'minimalistic':
          case 'rounded_3d':
            $css .= '.noUi-horizontal .noUi-handle{background:' . $color . ';border-color:' . $this->change_color_transparency( $color, -0.25 ) . ';}';
            break;
          case 'marker_3d':
            $css .= '.noUi-horizontal .noUi-handle{background:linear-gradient(0deg, #fff 0%, #fff 10%, ' . $color . ' 100%);border-color:' . $this->change_color_transparency( $color, -0.25 ) . ';}';
            break;
          default:
            $css .= '.noUi-horizontal .noUi-handle{background:' . $color . ';}';
            break;
        }
      }
			
			$temp_css = '';
			
			$customizer_default_font = isset( $options['awf_default_font'] ) ? $options['awf_default_font'] : get_option( 'awf_default_font' );
			if( ! empty( $customizer_default_font ) ) {
				$temp_css = 'font-family:' . sanitize_text_field( str_replace( '+', ' ', $customizer_default_font ) ) . ';';
			}
			
			if( ! empty( $options['awf_preset_color'] ) ) {
				$temp_css .= 'color:' . $this->sanitize_css_color( $options['awf_preset_color'] ) . ';';
				$css .= '.awf-thl .awf-thl-container{color:' . $this->change_color_transparency( $options['awf_preset_color'], -0.25 ) . ';}';
				$css .= '.awf-thl .awf-thl-container:hover{color:' . $this->sanitize_css_color( $options['awf_preset_color'] ) . ';}';
			}
			
			if( isset( $options['awf_preset_font_size'] ) ) {
				$temp_css .= 'font-size:' . $this->absint_or_string_maybe_to_px( $options['awf_preset_font_size'] ) . ';';
			}
			
			if( isset( $options['awf_preset_line_height'] ) ) {
        if( $customizer_preview && ( $default_options['awf_preset_line_height'] === $options['awf_preset_line_height'] ) ) {
          $temp_css .= 'line-height:' . $default_options['awf_preset_line_height'] . ';';
        } else {
          $temp_css .= 'line-height:' . $this->absint_or_string_maybe_to_px( $options['awf_preset_line_height'] ) . ';';
        }
			}
			
			if( ! empty( $temp_css ) ) { $css .= '.awf-preset-wrapper{' . $temp_css . '}'; }
      
      $temp_css = '';

      if( $customizer_preview && ! isset( $options['awf_filters_button_fixed_position'] ) && ( isset( $current_options['awf_filters_button_fixed_position'] ) && 'yes' === $current_options['awf_filters_button_fixed_position'] ) ) {
        $options['awf_filters_button_fixed_position'] = 'yes';
      }

      if( isset( $options['awf_filters_button_fixed_position'] ) ) {
        switch( $options['awf_filters_button_fixed_position'] ) {
          case 'yes':
            /* checked, both in Customizer AJAX preview and during Publish */
            $fixed_from = 0;
            $fixed_till = 0;

            if( isset( $options['awf_filters_button_fixed_from'] ) ) {
              if( is_numeric( $options['awf_filters_button_fixed_from'] ) ) {
                $fixed_from = $this->intval_or_string_maybe_to_px( $options['awf_filters_button_fixed_from'] );
              }
            } elseif( ! empty( $current_options['awf_filters_button_fixed_from'] ) ) {
              $fixed_from = $this->intval_or_string_maybe_to_px( $current_options['awf_filters_button_fixed_from'] );
            }
            
            if( isset( $options['awf_filters_button_fixed_till'] ) ) {
              if( is_numeric( $options['awf_filters_button_fixed_till'] ) ) {
                $fixed_till = $this->intval_or_string_maybe_to_px( $options['awf_filters_button_fixed_till'] );
              }
              
            } elseif( isset( $current_options['awf_filters_button_fixed_till'] ) && is_numeric( $current_options['awf_filters_button_fixed_till'] ) ) {
              $fixed_till = $this->intval_or_string_maybe_to_px( $current_options['awf_filters_button_fixed_till'] );
            }

            $css .= '@media (min-width:' . $fixed_from . ')';
            if( ! empty( $fixed_till ) ) {
              $css .= ' and (max-width:' . $fixed_till . ')';
            }
            $css .= ' {';
            
            $css .= '.awf-togglable-preset-btn {position:fixed;';

            foreach( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
              $option = 'awf_filters_button_fixed_' . $side;
              
              if( isset( $options[$option] ) && '' !== $options[$option] ) {
                $css .= $side . ':' . $this->intval_or_string_maybe_to_px( $options[$option] ) . ';';
              } elseif( isset( $current_options[$option] ) && '' !== $current_options[$option] ) {
                $css .= $side . ':' . $this->intval_or_string_maybe_to_px( $current_options[$option] ) . ';';
              }
            }

            $css .= '}';

            $css .= '.awf-togglable-preset-on .awf-togglable-preset-btn{display:none;}';

            $css .= '}';

            break;
          case 'no':
            /* unchecked during Publish */
            break;
          case false:
            /* unchecked in Customizer AJAX preview */
            $temp_css .= 'position:initial;top:initial;right:initial;bottom:initial;left:initial;';
            $css .= '.awf-togglable-preset-on .awf-togglable-preset-btn{display:inherit;}';
            break;
          default:
            break;
        }
      }
          
      if( isset( $options['awf_filters_button_z_index'] ) ) {
        $temp_css .= 'z-index:' . sanitize_key( $options['awf_filters_button_z_index'] ) . ';';
      }
      
      if( isset( $options['awf_filters_button_rotation'] ) ) {
        $temp_css .= 'transform:rotate(' . intval( $options['awf_filters_button_rotation'] ) . 'deg);';
      }

      if( ! empty( $options['awf_filters_button_background_color'] ) ) {
        $temp_css .= 'background-color:' . $this->sanitize_css_color( $options['awf_filters_button_background_color'] ) . ';';
      }
      
      if( ! empty( $temp_css ) ) {
        $css .= '.awf-togglable-preset-btn{' . $temp_css . '}';
        $temp_css = '';
      }
      
      if( ! empty( $options['awf_filters_button_hover_background_color'] ) ) {
        $temp_css .= 'background-color:' . $this->sanitize_css_color( $options['awf_filters_button_hover_background_color'] ) . ';';
      }

			if( ! empty( $options['awf_filters_button_hover_color'] ) ) {
				$temp_css .= 'color:' . $this->sanitize_css_color( $options['awf_filters_button_hover_color'] ) . ';';
			}
            
      if( ! empty( $temp_css ) ) {
        $css .= '.awf-togglable-preset-btn:hover{' . $temp_css . '}';
        $temp_css = '';
      }

			if( isset( $options['awf_filters_button_hide_icon'] ) ) {
				switch( $options['awf_filters_button_hide_icon'] ) {
					case 'yes':
						$css .= '.awf-togglable-preset-btn i.awf-togglable-preset-btn-icon{display:none;}';
						break;
					case false:
						$css .= '.awf-togglable-preset-btn i.awf-togglable-preset-btn-icon{display:inline-block;}';
						break;
					default: break;
				}
			}
      
      if( empty( $options['awf_filters_button_custom_icon'] ) ) {
        if( empty( $options['awf_filters_button_icon'] ) ) {
          if( $customizer_preview ) {
            if( empty( $current_options['awf_filters_button_icon'] ) ) {
              $temp_css .= 'content:"\\' . sanitize_key( $default_options['awf_filters_button_icon'] ) . '";';
            } else {
              $temp_css .= 'content:"\\' . sanitize_key( $current_options['awf_filters_button_icon'] ) . '";';
            }
          }
        } else {
          $temp_css .= 'content:"\\' . sanitize_key( $options['awf_filters_button_icon'] ) . '";';
        }
      } else {
        $temp_css .= 'content:"\\' . sanitize_key( $options['awf_filters_button_custom_icon'] ) . '";';
      }
      
      if( ! empty( $options['awf_filters_button_icon_size'] ) ) {
        $temp_css .= 'font-size:' . floatval( $options['awf_filters_button_icon_size'] ) . 'em;';
      }
      
      if( isset( $options['awf_filters_button_icon_padding_right'] ) ) {
        $temp_css .= $this->get_padding_css( $options, 'awf_filters_button_icon_padding_', array( 'right' ) );
      }

      $temp_css .= $this->get_borders_css( $options, 'awf_filters_button_icon_border_', array( 'right' ) );

      if( ! empty( $temp_css ) ) {
        $css .= '.awf-togglable-preset-btn i.awf-togglable-preset-btn-icon::before{' . $temp_css . '}';
        $temp_css = '';
      }
      
      if( ! empty( $options['awf_popup_sidebar_popup_position'] ) ) {
        if( 'right' === $options['awf_popup_sidebar_popup_position'] ) {
          $temp_css .= 'right:0;left:auto;border-right:none;border-left: 1px solid #eeeeee;transform-origin:right;';
          $css .= '#awf-fixed-popup-close-btn {transform-origin:right;}';
        } else {
          if( $customizer_preview ) {
            $temp_css .= 'right:inherit;left:inherit;border-right:inherit;border-left:inherit;transform-origin:inherit;';
            $css .= '#awf-fixed-popup-close-btn {transform-origin:inherit;}';
          }
        }
      }

      if( isset( $options['awf_popup_sidebar_animation_duration'] ) && '' !== $options['awf_popup_sidebar_animation_duration'] ) {
        $temp_css .= 'transition:transform ' . absint( $options['awf_popup_sidebar_animation_duration'] ) . 'ms ease-in-out, opacity .5s;';
        $css .= '.awf-togglable-preset-on #awf-fixed-popup-close-btn {transition-delay:' . absint( $options['awf_popup_sidebar_animation_duration'] ) . 'ms;}';
      }

      if( isset( $options['awf_popup_sidebar_width'] ) && '' !== $options['awf_popup_sidebar_width'] ) {
        $temp_css .= 'width:' . $this->absint_or_string_maybe_to_px( $options['awf_popup_sidebar_width'] ) . ';';
      }

      if( ! empty( $options['awf_popup_sidebar_background_color'] ) ) {
        $temp_css .= 'background-color:' . $this->sanitize_css_color( $options['awf_popup_sidebar_background_color'] ) . ';';
      }
      
      if( ! empty( $temp_css ) ) {
        $css .= '.awf-togglable-preset.awf-left-popup-sidebar-mode,.awf-togglable-preset-mode-on .awf-togglable-on-s-preset.awf-left-popup-sidebar-mode{' . $temp_css . '}';
        $temp_css = '';
      }
      
      if( isset( $options['awf_popup_sidebar_close_btn_rotation'] ) ) {
        /* Keep before awf_popup_fix_close_btn & awf_popup_sidebar_close_btn_fixed_position_small_screens_fix */
        if( '' === $options['awf_popup_sidebar_close_btn_rotation'] ) {
          if( $customizer_preview ) {
            $css .= '#awf-fixed-popup-close-btn.awf-togglable-preset-close-btn, .awf-togglable-preset-close-btn{transform:rotate(0);}';
          }
        } else {
          $css .= '#awf-fixed-popup-close-btn.awf-togglable-preset-close-btn, .awf-togglable-preset-close-btn{transform:rotate(' . intval( $options['awf_popup_sidebar_close_btn_rotation'] ) . 'deg);}';
        }
      }

      $popup_close_btn_position = '.awf-togglable-preset-close-btn{';

      foreach( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
        $option = 'awf_popup_sidebar_close_btn_fixed_' . $side;
        
        if( isset( $options[$option] ) && '' !== $options[$option] ) {
          $popup_close_btn_position .= $side . ':' . $this->intval_or_string_maybe_to_px( $options[$option] ) . ';';
        } elseif( isset( $current_options[$option] ) && '' !== $current_options[$option] ) {
          $popup_close_btn_position .= $side . ':' . $this->intval_or_string_maybe_to_px( $current_options[$option] ) . ';';
        }
      }

      $popup_close_btn_position .= '}';
      $css .= $popup_close_btn_position;

      if( 'yes' === get_option( 'awf_popup_fix_close_btn', 'no' ) && isset( $options['awf_popup_sidebar_close_btn_fixed_position_small_screens_fix'] ) ) {
        switch( $options['awf_popup_sidebar_close_btn_fixed_position_small_screens_fix'] ) {
          case 'yes':
            $css .= '@media(max-width: 450px) {#awf-fixed-popup-close-btn.awf-togglable-preset-close-btn{transform:rotate(0);top:0;left:0;right:auto;bottom:auto;}}';
            break;
          default:
            if( $customizer_preview ) {
              $css .= '@media(max-width: 450px) {' . $popup_close_btn_position . '#awf-fixed-popup-close-btn.awf-togglable-preset-close-btn{transform:rotate(' . ( isset( $options['awf_popup_sidebar_close_btn_rotation'] ) ? intval( $options['awf_popup_sidebar_close_btn_rotation'] ) : ( isset( $current_options['awf_popup_sidebar_close_btn_rotation'] ) ? intval( $current_options['awf_popup_sidebar_close_btn_rotation'] ) : '0' ) ) . 'deg);}}';
            }
            break;
        }
      }

      if( ! empty( $options['awf_popup_sidebar_close_btn_text_align'] ) ) { $css .= '.awf-togglable-preset.awf-left-popup-sidebar-mode .awf-togglable-preset-close-btn, .awf-togglable-preset-mode-on .awf-togglable-on-s-preset.awf-left-popup-sidebar-mode .awf-togglable-preset-close-btn{text-align:' . sanitize_text_field( $options['awf_popup_sidebar_close_btn_text_align'] ) . '; justify-content:' . sanitize_text_field( $options['awf_popup_sidebar_close_btn_text_align'] ) . ';}'; }
      
      if( isset( $options['awf_popup_sidebar_close_btn_font_size'] ) ) { $temp_css .= 'font-size:' . $this->absint_or_string_maybe_to_px( $options['awf_popup_sidebar_close_btn_font_size'] ) . ';'; }
      if( ! empty( $options['awf_popup_sidebar_close_btn_font_weight'] ) ) { $temp_css .= 'font-weight:' . $this->absint_or_string( $options['awf_popup_sidebar_close_btn_font_weight'] ) . ';'; }
      if( ! empty( $options['awf_popup_sidebar_close_btn_text_transform'] ) ) { $temp_css .= 'text-transform:' . sanitize_text_field( $options['awf_popup_sidebar_close_btn_text_transform'] ) . ';'; }

      if( ! empty( $options['awf_popup_sidebar_close_btn_color'] ) ) { $temp_css .= 'color:' . $this->sanitize_css_color( $options['awf_popup_sidebar_close_btn_color'] ) . ';'; }
      
      $temp_css .= $this->get_margins_css( $options, 'awf_popup_sidebar_close_btn_margin_' );
      $temp_css .= $this->get_padding_css( $options, 'awf_popup_sidebar_close_btn_padding_' );
      $temp_css .= $this->get_borders_css( $options, 'awf_popup_sidebar_close_btn_border_' );

      if( ! empty( $options['awf_popup_sidebar_close_btn_background_color'] ) ) {
        $temp_css .= 'background-color:' . $this->sanitize_css_color( $options['awf_popup_sidebar_close_btn_background_color'] ) . ';';
      }

      if( ! empty( $temp_css ) ) {
        $css .= '.awf-togglable-preset-close-btn{' . $temp_css . '}';
        $temp_css = '';
      }
            
      if( ! empty( $options['awf_popup_sidebar_close_btn_icon_size'] ) ) {
        $css .= '.awf-togglable-preset-close-btn i{font-size:' . ( is_numeric( $options['awf_popup_sidebar_close_btn_icon_size'] ) ? floatval( $options['awf_popup_sidebar_close_btn_icon_size'] ) . 'em' : sanitize_key( $options['awf_popup_sidebar_close_btn_icon_size'] ) ) . ';}';
      }
      
      if( ! empty( $options['awf_popup_sidebar_close_btn_hover_color'] ) ) {
        $css .= '.awf-togglable-preset-close-btn:hover{color:' . $this->sanitize_css_color( $options['awf_popup_sidebar_close_btn_hover_color'] ) . ';}';
      }
      
      if( ! empty( $options['awf_popup_sidebar_close_btn_hover_background_color'] ) ) {
        $css .= '.awf-togglable-preset-close-btn:hover{background-color:' . $this->sanitize_css_color( $options['awf_popup_sidebar_close_btn_hover_background_color'] ) . ';}';
      }

			if( ! empty( $options['awf_active_badge_reset_icon_position'] ) ) {
				$css .= '.awf-active-badge{flex-direction:' . sanitize_key( $options['awf_active_badge_reset_icon_position'] ) . ';}';
			}
			
			if( ! empty( $options['awf_active_badge_justify_content'] ) ) {
				$css .= '.awf-active-badge{justify-content:' . sanitize_key( $options['awf_active_badge_justify_content'] ) . ';}';
			}
			
			if( ! empty( $options['awf_active_badge_hover_color'] ) ) {
				$css .= '.awf-active-badge:hover{color:' . $this->sanitize_css_color( $options['awf_active_badge_hover_color'] ) . ';}';
			}
			
			if( isset( $options['awf_active_badge_font_size'] ) ) {
				$css .= '.awf-active-badges-container{font-size:' . $this->absint_or_string_maybe_to_px( $options['awf_active_badge_font_size'] ) . ';}';
			}
			
			if( ! empty( $options['awf_reset_btn_hover_color'] ) ) {
				$css .= 'button.awf-reset-btn:hover{color:' . $this->sanitize_css_color( $options['awf_reset_btn_hover_color'] ) . ';}';
			}
			
			if( isset( $options['awf_reset_btn_width'] ) ) {
				$css .= 'button.awf-reset-btn{width:' . $this->absint_or_string_maybe_to_percent( $options['awf_reset_btn_width'] ) . ';}';
			}
			
			if( ! empty( $options['awf_reset_btn_background_color'] ) ) {
				$css .= 'button.awf-reset-btn{background-color:' . $this->sanitize_css_color( $options['awf_reset_btn_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_reset_btn_hover_background_color'] ) ) {
				$css .= 'button.awf-reset-btn:hover{background-color:' . $this->sanitize_css_color( $options['awf_reset_btn_hover_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_submit_btn_hover_color'] ) ) {
				$css .= 'button.awf-apply-filter-btn:hover{color:' . $this->sanitize_css_color( $options['awf_submit_btn_hover_color'] ) . ';}';
			}
			
			if( isset( $options['awf_submit_btn_width'] ) ) {
				$css .= 'button.awf-apply-filter-btn{width:' . $this->absint_or_string_maybe_to_percent( $options['awf_submit_btn_width'] ) . ';}';
			}
			
			if( ! empty( $options['awf_submit_btn_background_color'] ) ) {
				$css .= 'button.awf-apply-filter-btn{background-color:' . $this->sanitize_css_color( $options['awf_submit_btn_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_submit_btn_hover_background_color'] ) ) {
				$css .= 'button.awf-apply-filter-btn:hover{background-color:' . $this->sanitize_css_color( $options['awf_submit_btn_hover_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_filter_title_collapse_btn_icon'] ) ) {
				$css .= '.awf-filter-wrapper:not(.awf-dropdown) .awf-collapse-btn::before{content:"\\' . sanitize_text_field( str_replace( '\\', '', $options['awf_filter_title_collapse_btn_icon'] ) ) . '";}';

        if( 'f068' === $options['awf_filter_title_collapse_btn_icon'] ) {
          $css .= '.awf-filter-wrapper:not(.awf-dropdown).awf-collapsed .awf-collapse-btn::before{content:"\\f067";transform: scaleY(-1) rotate(90deg);}';
        } else {
          $css .= '.awf-filter-wrapper:not(.awf-dropdown).awf-collapsed .awf-collapse-btn::before{content:"\\' . sanitize_text_field( str_replace( '\\', '', $options['awf_filter_title_collapse_btn_icon'] ) ) . '";transform:inherit;}';
        }
			}
			
			if( ! empty( $options['awf_dropdown_collapse_btn_icon'] ) ) {
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-collapse-btn::before{content:"\\' . sanitize_text_field( str_replace( '\\', '', $options['awf_dropdown_collapse_btn_icon'] ) ) . '";}';

        if( 'f068' === $options['awf_dropdown_collapse_btn_icon'] ) {
          $css .= '.awf-filter-wrapper.awf-dropdown.awf-collapsed .awf-collapse-btn::before{content:"\\f067";transform: scaleY(-1) rotate(90deg);}';
        } else {
          $css .= '.awf-filter-wrapper.awf-dropdown.awf-collapsed .awf-collapse-btn::before{content:"\\' . sanitize_text_field( str_replace( '\\', '', $options['awf_dropdown_collapse_btn_icon'] ) ) . '";transform:inherit;}';
        }
			}
			
			if( isset( $options['awf_dropdown_height'] ) ) {
				$height = $this->absint_or_string_maybe_to_px( $options['awf_dropdown_height'] );
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filter-title-container{height:' . $height . ';max-height:' . $height . ';}';
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filters-container{top:' . $height . ';}';
			}
			
			if( ! empty( $options['awf_dropdown_background_color'] ) ) {
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filter-title-container{background-color:' . $this->sanitize_css_color( $options['awf_dropdown_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_dropdown_filters_container_background_color'] ) ) {
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filters-container, .awf-dropdown.awf-button-filter .awf-submit-btn-container{background-color:' . $this->sanitize_css_color( $options['awf_dropdown_filters_container_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_dropdown_filters_container_border_color'] ) ) {
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filters-container{border-color:' . $this->sanitize_css_color( $options['awf_dropdown_filters_container_border_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_dropdown_filters_container_box_shadow_color'] ) ) {
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filters-container{box-shadow:0px 1px 2px 0px ' . $this->sanitize_css_color( $options['awf_dropdown_filters_container_box_shadow_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_dropdown_z_index'] ) ) {
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filters-container, .awf-filter-wrapper.awf-dropdown .awf-submit-btn-container, .awf-filter-wrapper.awf-dropdown .awf-thl-container{z-index:' . absint( $options['awf_dropdown_z_index'] ) . ';}';
			}
			
			if( ! empty( $options['awf_filter_label_hover_color'] ) ) {
				$css .= '.awf-filter-container label:hover,.awf-filter-container.awf-active label:hover{color:' . $this->sanitize_css_color( $options['awf_filter_label_hover_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_filter_label_active_color'] ) ) {
				$css .= '.awf-filter-container.awf-active label{color:' . $this->sanitize_css_color( $options['awf_filter_label_active_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_icons_hover_color'] ) ) {
				$css .= '.awf-style-icons .awf-filter-container:not(.awf-hover-off) label:hover::before,.awf-style-custom-terms .awf-filter-container:not(.awf-hover-off) label:hover::before{color:' . $this->sanitize_css_color( $options['awf_icons_hover_color'] ) . ';}';
			}
      
      if( ! empty( $options['awf_search_font_size'] ) ) {
        $css .= '.awf-filter-container.awf-product-search-container{font-size:' . $this->absint_or_string_maybe_to_px( $options['awf_search_font_size'] ) . ';}';
      }
      
      if( ! empty( $options['awf_search_icon_size'] ) ) {
        $css .= '.awf-filter-container.awf-product-search-container::before{font-size:' . floatval( $options['awf_search_icon_size'] ) . 'em;}';
        $css .= '.awf-filter-container.awf-product-search-container button.awf-clear-search-btn{font-size:' . floatval( $options['awf_search_icon_size'] ) . 'em;}';
      }
      
      if( ! empty( $options['awf_search_icon_color'] ) ) {
        $css .= '.awf-filter-container.awf-product-search-container::before{color:' . $this->sanitize_css_color( $options['awf_search_icon_color'] ) . ';}';
        $css .= '.awf-filter-container.awf-product-search-container button.awf-clear-search-btn{color:' . $this->sanitize_css_color( $options['awf_search_icon_color'] ) . ';}';
      }

			if( ! empty( $options['awf_search_background_color'] ) ) {
				$css .= '.awf-filter-container.awf-product-search-container input[type=search].awf-filter{background-color:' . $this->sanitize_css_color( $options['awf_search_background_color'] ) . ';}';
			}

			if( isset( $options['awf_search_height'] ) ) {
				$height = $this->absint_or_string_maybe_to_px( $options['awf_search_height'] );

				$css .= '.awf-filter-container.awf-product-search-container, .awf-preset-wrapper:not(.awf-1-column-preset) .awf-filter-container.awf-product-search-container{line-height:' . $height . ';}';
				$css .= '.awf-filter-container.awf-product-search-container input[type=search].awf-filter, .awf-preset-wrapper:not(.awf-1-column-preset) .awf-filter-container.awf-product-search-container .awf-filter{height:' . $height . ';line-height:' . $height . ';}';
			}

      if( isset( $options['awf_search_ac_color'] ) ) {
        $css .= '.awf-filter-container.awf-product-search-container .awf-product-search-autocomplete-container{--awf-ac-base-color:' . $this->sanitize_css_color( $options['awf_search_ac_color'] ) . ';}';
      }      

      if( isset( $options['awf_search_ac_font_size'] ) ) {
        $css .= '.awf-filter-container.awf-product-search-container .awf-product-search-autocomplete-container{font-size:' . $this->absint_or_string_maybe_to_px( $options['awf_search_ac_font_size'] ) . ';}';
      }

      if( isset( $options['awf_search_ac_background_color'] ) ) {
        $css .= '.awf-filter-container.awf-product-search-container .awf-product-search-autocomplete-container{background:' . $this->sanitize_css_color( $options['awf_search_ac_background_color'] ) . ';}';
      }      

      if( isset( $options['awf_search_ac_width'] ) ) {
        $units = '%';

        if( isset( $options['awf_search_ac_width_units'] ) ) {
          $units = $options['awf_search_ac_width_units'];
        } elseif( isset( $current_options['awf_search_ac_width_units'] ) ) {
          $units = $current_options['awf_search_ac_width_units'];
        }

        $css .= '.awf-filter-container.awf-product-search-container .awf-product-search-autocomplete-container{min-width:' . absint( $options['awf_search_ac_width'] ) . $units . ';}';
      }
			
			$awf_customizer_sections = array(
				'awf_filters_button' => '.awf-togglable-preset-btn',
				'awf_popup_sidebar' => '.awf-togglable-preset.awf-left-popup-sidebar-mode, .awf-togglable-preset-mode-on .awf-togglable-on-s-preset.awf-left-popup-sidebar-mode',
				'awf_preset_title' => '.awf-preset-title',
				'awf_preset_description' => '.awf-preset-description',
				'awf_active_badge' => '.awf-active-badge',
				'awf_reset_btn' => 'button.awf-reset-btn',
				'awf_submit_btn' => 'button.awf-apply-filter-btn',
				'awf_filter_title' => '.awf-filter-wrapper:not(.awf-dropdown) .awf-filter-title',
				'awf_dropdown' => '.awf-dropdown .awf-filter-title',
				'awf_filter_label' => '.awf-filter-container label',
				'awf_icons' => '.awf-style-icons label::before, .awf-style-custom-terms label::before',
				'awf_search' => '.awf-filter-container.awf-product-search-container input[type=search].awf-filter',
			);
			
			foreach( $awf_customizer_sections as $section => $selector ) {
				$primary_selector_css = $secondary_selector_css = '';
				
				$current_css = 'primary_selector_css';
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown', 'awf_filter_label' ) ) ) { $current_css = 'secondary_selector_css'; }

        ${$current_css} .= $this->get_margins_css( $options, $section . '_margin_' );
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown' ) ) ) { $current_css = 'primary_selector_css'; }
				elseif( in_array( $section, array( 'awf_active_badge' ) ) ) { $current_css = 'secondary_selector_css'; }

        ${$current_css} .= $this->get_padding_css( $options, $section . '_padding_' );
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown' ) ) ) { $current_css = 'secondary_selector_css'; }
				elseif( in_array( $section, array( 'awf_active_badge' ) ) ) { $current_css = 'primary_selector_css'; }
				
				$option = $section . '_line_height';
				if( isset( $options[$option] ) ) { ${$current_css} .= 'line-height:' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';'; }
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown', 'awf_filter_label' ) ) ) { $current_css = 'primary_selector_css'; }
				
				if( ! empty( $options[$section . '_text_align'] ) ) { ${$current_css} .= 'text-align:' . sanitize_key( $options[$section . '_text_align'] ) . ';'; }
				if( ! empty( $options[$section . '_color'] ) ) { ${$current_css} .= 'color:' . $this->sanitize_css_color( $options[$section . '_color'] ) . ';'; }
				if( ! empty( $options[$section . '_font_family'] ) ) { ${$current_css} .= 'font-family:' . sanitize_text_field( $options[$section . '_font_family'] ) . ';'; }
				if( isset( $options[$section . '_font_size'] ) ) { ${$current_css} .= 'font-size:' . $this->absint_or_string_maybe_to_px( $options[$section . '_font_size'] ) . ';'; }
				if( ! empty( $options[$section . '_font_weight'] ) ) { ${$current_css} .= 'font-weight:' . $this->absint_or_string( $options[$section . '_font_weight'] ) . ';'; }
				if( ! empty( $options[$section . '_text_transform'] ) ) { ${$current_css} .= 'text-transform:' . sanitize_text_field( $options[$section . '_text_transform'] ) . ';'; }
				
				if( isset( $options[$section . '_font_style_italic'] ) ) {
					switch( $options[$section . '_font_style_italic'] ) {
						case 'yes':
							${$current_css} .= 'font-style:italic;';
							break;
						case false:
							${$current_css} .= 'font-style:normal;';
							break;
						default: break;
					}
				}
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown', 'awf_filter_label' ) ) ) { $current_css = 'secondary_selector_css'; }

        ${$current_css} .= $this->get_borders_css( $options, $section . '_border_' );
				
				$option = $section . '_border_radius';
				if( isset( $options[$option] ) ) { ${$current_css} .= 'border-radius:' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';'; }
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown', 'awf_filter_label' ) ) ) { $current_css = 'primary_selector_css'; }
				
				if( isset( $options[$section . '_white_space_nowrap'] ) ) {
					switch( $options[$section . '_white_space_nowrap'] ) {
						case 'yes':
							${$current_css} .= 'white-space:nowrap;';
							break;
						case false:
							${$current_css} .= 'white-space:normal;';
							break;
						default: break;
					}
				}
				
				if( ! empty( $primary_selector_css ) ) { $css .= $selector . '{' . $primary_selector_css . '}'; }
				
				if( ! empty( $secondary_selector_css ) ) {
					switch( $section ) {
						case 'awf_active_badge':
							$css .= '.awf-active-badge>span{' . $secondary_selector_css . '}';
							break;
						case 'awf_filter_title':
							$css .= '.awf-filter-wrapper:not(.awf-dropdown) .awf-filter-title-container{' . $secondary_selector_css . '}';
							break;
						case 'awf_dropdown':
							$css .= '.awf-dropdown .awf-filter-title-container{' . $secondary_selector_css . '}';
							break;
						case 'awf_filter_label':
							$css .= '.awf-filters-container li.awf-filter-container{' . $secondary_selector_css . '}';
							break;
						default: break;
					}
				}
			}
			
			return $css;
		}

    function get_margins_css( $options, $option_prefix, $sides = array( 'top', 'right', 'bottom', 'left' ) ) {
      $css = '';

      foreach( $sides as $side ) {
        $option = $option_prefix . $side;

        if( isset( $options[$option] ) ) {
          $css .= 'margin-' . $side . ':' . $this->intval_or_string_maybe_to_px( $options[$option] ) . ';';
        }
      }

      return $css;
    }

    function get_padding_css( $options, $option_prefix, $sides = array( 'top', 'right', 'bottom', 'left' ) ) {
      $css = '';

      foreach( $sides as $side ) {
        $option = $option_prefix . $side;

        if( isset( $options[$option] ) && '' !== $options[$option] ) {
          $css .= 'padding-' . $side . ':' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';';
        }
      }

      return $css;
    }

    function get_borders_css( $options, $option_prefix, $sides = array( 'top', 'right', 'bottom', 'left' ) ) {
      $css = '';
      				
      foreach( $sides as $side ) {
        $option = $option_prefix . $side . '_style';
        if( ! empty( $options[$option] ) ) { $css .= 'border-' . $side . '-style:' . sanitize_text_field( $options[$option] ) . ';'; }
        
        $option = $option_prefix . $side . '_width';
        if( isset( $options[$option] ) ) { $css .= 'border-' . $side . '-width:' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';'; }
        
        $option = $option_prefix . $side . '_color';
        if( ! empty( $options[$option] ) ) { $css .= 'border-' . $side . '-color:' . $this->sanitize_css_color( $options[$option] ) . ';'; }
      }

      return $css;
    }
		
		public function absint_or_string_maybe_to_px( $value ) {
			if( is_numeric( $value ) ) {
				return absint( $value ) . 'px';
			}
			
			return sanitize_text_field( $value );
		}
		
		public function absint_or_string_maybe_to_percent( $value ) {
			if( is_numeric( $value ) ) {
				return absint( $value ) . '%';
			}
			
			return sanitize_text_field( $value );
		}
		
		public function absint_or_string( $value ) {
			if( is_numeric( $value ) ) {
				return absint( $value );
			}
			
			return sanitize_text_field( $value );
		}
		
		public function intval_or_string_maybe_to_px( $value ) {
			if( is_numeric( $value ) ) {
				return intval( $value ) . 'px';
			}
			
			return sanitize_text_field( $value );
		}
		
		public function sanitize_css_color( $value ) {
			if( 'inherit' === $value || 'transparent' === $value ) {
				return $value;
			}
			
			return A_W_F_admin::sanitize_hex_rgba_color( $value );
		}

		public static function sanitize_hex_rgba_color( $value ) {
			
			$pattern = '/^#[a-zA-Z0-9]{3,6}|rgba\((\s*\d+\s*,){3}[\d\.]+\)$/';
			
			preg_match( $pattern, $value, $matches );
			
			if ( isset( $matches[0] ) ) {
				if ( is_string( $matches[0] ) ) {
					return $matches[0];
				}
				if ( is_array( $matches[0] ) && isset( $matches[0][0] ) ) {
					return $matches[0][0];
				}
			}
			
			return '';
		}
		
		public function change_color_transparency( $color, $adjustment ) {
      if( 0 === strpos( $color, '#' ) ) {
        if( 4 === strlen( $color ) ) { $color =  $color . substr( $color, 1 ); }
        list( $r, $g, $b, $a ) = sscanf( $color, "#%02x%02x%02x%02x");
        if( empty( $a ) ) { $a = 1; }
        $color = 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $a . ')';
      }

			if( false === strpos( $color, 'rgba' ) ) { return $color; }

      $rgba = explode(')', ( explode( '(', $color )[1] ) )[0];
      $rgba = str_replace(' ', '', $rgba);

      if( ! empty( $rgba ) ) {
        $rgba = explode( ',', $rgba );
        if( isset( $rgba[3] ) ) {
          $rgba[3] = floatval( $rgba[3] ) + floatval( $adjustment );
        }
        $color = 'rgba(' . implode( ',', $rgba ) . ')';
      }

      return $color;
		}
		
		public static function get_awf_custom_style_defaults( $style ) {
			
			if( ! in_array( $style, array( 'none' ) ) ) { $style = 'none'; }
			
			$defaults = array(
				'awf_default_font' => 'inherit',
				'awf_preset_color' => 'inherit',
				'awf_preset_font_size' => 'inherit',
				'awf_preset_line_height' => '1.6em',
			);

      $customizer_sections = array_keys( A_W_F_admin::get_customizer_sections() );
			
			foreach( $customizer_sections as $section ) {
        
        if (substr( $section, -11 ) === '_customizer' ) {
          $section = substr( $section, 0, -11 );
        }
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown' ) ) ) {
					$defaults[$section . '_collapse_btn_icon'] = 'f078';
				}
				
				$defaults[$section . '_margin_top'] = '0';
				$defaults[$section . '_margin_right'] = '0';
				$defaults[$section . '_margin_bottom'] = '0';
				$defaults[$section . '_margin_left'] = '0';
				
				if( 'awf_dropdown' === $section ) {
					$defaults[$section . '_height'] = '38';
				}
				
				$defaults[$section . '_padding_top'] = '0';
				$defaults[$section . '_padding_right'] = '0';
				$defaults[$section . '_padding_bottom'] = '0';
				$defaults[$section . '_padding_left'] = '0';
				
				$defaults[$section . '_line_height'] = 'inherit';
				$defaults[$section . '_text_align'] = 'inherit';
				
				$defaults[$section . '_color'] = 'inherit';
				$defaults[$section . '_font_family'] = 'inherit';
				$defaults[$section . '_font_size'] = 'inherit';
				$defaults[$section . '_font_weight'] = 'inherit';
				$defaults[$section . '_text_transform'] = 'inherit';
				$defaults[$section . '_font_style_italic'] = false;
				
				
				foreach( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
					switch( $section ) {
						case 'awf_filters_button':
							$defaults[$section . '_icon_border_right_style'] = '';
							$defaults[$section . '_icon_border_right_width'] = '';
							$defaults[$section . '_icon_border_right_color'] = '';
							$defaults[$section . '_border_' . $side . '_style'] = 'solid';
							$defaults[$section . '_border_' . $side . '_width'] = '2';
							$defaults[$section . '_border_' . $side . '_color'] = '#888888';
              $defaults[$section . '_fixed_' . $side] = 'auto';
							break;
						case 'awf_popup_sidebar':
							$defaults[$section . '_padding_' . $side] = '20';
							$defaults[$section . '_border_' . $side . '_style'] = 'none';
							$defaults[$section . '_border_' . $side . '_width'] = 'inherit';
							$defaults[$section . '_border_' . $side . '_color'] = 'inherit';
							$defaults[$section . '_close_btn_margin_' . $side] = '0';
							$defaults[$section . '_close_btn_padding_' . $side] = '0';
							$defaults[$section . '_close_btn_border_' . $side . '_style'] = 'none';
							$defaults[$section . '_close_btn_border_' . $side . '_width'] = 'inherit';
							$defaults[$section . '_close_btn_border_' . $side . '_color'] = 'inherit';
							$defaults[$section . '_close_btn_fixed_' . $side] = 'auto';
							break;
						case 'awf_dropdown':
							$defaults[$section . '_border_' . $side . '_style'] = 'solid';
							$defaults[$section . '_border_' . $side . '_width'] = '1';
							$defaults[$section . '_border_' . $side . '_color'] = '#d1d1d1';
							break;
						default:
							$defaults[$section . '_border_' . $side . '_style'] = 'none';
							$defaults[$section . '_border_' . $side . '_width'] = 'inherit';
							$defaults[$section . '_border_' . $side . '_color'] = 'inherit';
							break;
					}
					
				}
				
				$defaults[$section . '_border_radius'] = '0';
				
				if( 'awf_preset_description' !== $section ) {
					$defaults[$section . '_white_space_nowrap'] = false;
				}
				
				switch( $section ) {
					case 'awf_filters_button':
						$defaults[$section . '_margin_bottom'] = '20';
						$defaults[$section . '_padding_right'] = '10';
						$defaults[$section . '_padding_left'] = '10';
						$defaults[$section . '_line_height'] = '36';
						$defaults[$section . '_color'] = '#999999';
						$defaults[$section . '_hover_color'] = 'inherit';
						$defaults[$section . '_font_size'] = '14';
						$defaults[$section . '_font_weight'] = '400';
            $defaults[$section . '_rotation'] = '0';
						$defaults[$section . '_background_color'] = 'transparent';
						$defaults[$section . '_hover_background_color'] = '#fbfbfb';
						$defaults[$section . '_border_radius'] = '2';
						$defaults[$section . '_z_index'] = '999998';
            $defaults[$section . '_fixed_position'] = false;
            $defaults[$section . '_fixed_from'] = '0';
            $defaults[$section . '_fixed_till'] = '';
						$defaults[$section . '_hide_icon'] = false;
            $defaults[$section . '_icon'] = 'f0c9';
						$defaults[$section . '_custom_icon'] = '';
						$defaults[$section . '_icon_size'] = '0.9';
						$defaults[$section . '_icon_padding_right'] = '0';

						break;
					case 'awf_popup_sidebar':
            $defaults[$section . '_popup_position'] = 'left';
            $defaults[$section . '_width'] = '400';
						$defaults[$section . '_animation_duration'] = '120';
						$defaults[$section . '_border_right_color'] = '#eeeeee';
						$defaults[$section . '_border_right_size'] = '1px';
						$defaults[$section . '_border_right_style'] = 'solid';
            $defaults[$section . '_background_color'] = '#ffffff';
            $defaults[$section . '_close_btn_icon_size'] = 'inherit';
            $defaults[$section . '_close_btn_font_weight'] = 'inherit';
            $defaults[$section . '_close_btn_text_transform'] = 'uppercase';
            $defaults[$section . '_close_btn_text_align'] = 'right';
            $defaults[$section . '_close_btn_color'] = '#cccccc';
            $defaults[$section . '_close_btn_hover_color'] = '#999999';
            $defaults[$section . '_close_btn_font_size'] = '16';
            $defaults[$section . '_close_btn_padding_top'] = '5';
            $defaults[$section . '_close_btn_padding_bottom'] = '5';
            $defaults[$section . '_close_btn_background_color'] = 'transparent';
						$defaults[$section . '_close_btn_hover_background_color'] = 'transparent';
						$defaults[$section . '_close_btn_rotation'] = '0';
						break;
					case 'awf_preset_title':
						$defaults[$section . '_margin_bottom'] = '15';
						$defaults[$section . '_text_align'] = 'left';
						$defaults[$section . '_font_size'] = '1.5em';
						$defaults[$section . '_font_weight'] = '500';
						break;
					case 'awf_preset_description':
						$defaults[$section . '_margin_bottom'] = '15';
						$defaults[$section . '_text_align'] = 'left';
						$defaults[$section . '_font_size'] = '0.8em';
						$defaults[$section . '_font_weight'] = '200';
						break;
					case 'awf_active_badge':
						$defaults[$section . '_hover_color'] = 'inherit';
						$defaults[$section . '_reset_icon_position'] = 'row-reverse';
						$defaults[$section . '_justify_content'] = 'space-between';
						$defaults[$section . '_line_height'] = '1.5em';
						break;
					case 'awf_reset_btn':
						$defaults[$section . '_width'] = 'auto';
						$defaults[$section . '_background_color'] = 'inherit';
						$defaults[$section . '_hover_background_color'] = 'inherit';
						break;
					case 'awf_submit_btn':
						$defaults[$section . '_width'] = 'auto';
						$defaults[$section . '_background_color'] = 'inherit';
						$defaults[$section . '_hover_background_color'] = 'inherit';
						break;
					case 'awf_filter_title':
						$defaults[$section . '_margin_bottom'] = '10';
						$defaults[$section . '_padding_right'] = '20';
						$defaults[$section . '_text_align'] = 'left';
						$defaults[$section . '_font_size'] = '1.2em';
						$defaults[$section . '_font_weight'] = '300';
						break;
					case 'awf_dropdown':
						$defaults[$section . '_padding_right'] = '22';
						$defaults[$section . '_line_height'] = '36';
						$defaults[$section . '_height'] = '38';
						$defaults[$section . '_background_color'] = 'inherit';
						$defaults[$section . '_filters_container_background_color'] = '#ffffff';
						$defaults[$section . '_filters_container_border_color'] = '#cccccc';
						$defaults[$section . '_filters_container_box_shadow_color'] = 'rgba(0, 0, 0, 0.1)';
						$defaults[$section . '_z_index'] = '3';
						break;
					case 'awf_filter_label':
						$defaults[$section . '_hover_color'] = '#000000';
						$defaults[$section . '_active_color'] = 'inherit';
						break;
          case 'awf_icons':
            $defaults[$section . '_hover_color'] = 'inherit';
            $defaults[$section . '_font_size'] = '0.9em';
            $defaults[$section . '_margin_right'] = '5';
            $defaults[$section . '_margin_left'] = '1';
    
            break;
          case 'awf_search':
            $defaults[$section . '_height'] = '45';
            $defaults[$section . '_icon_size'] = '1.1em';
            $defaults[$section . '_icon_color'] = 'inherit';
            $defaults[$section . '_ac_color'] = '#666666';
            $defaults[$section . '_ac_font_size'] = '12px';
            $defaults[$section . '_ac_width'] = '100';
            $defaults[$section . '_ac_width_units'] = '%';
            $defaults[$section . '_ac_background_color'] = '#ffffff';
    
            break;
          case 'awf_sliders':
            $defaults[$section . '_width'] = 'auto';
            $defaults[$section . '_sf_color'] = '#999';
            $defaults[$section . '_slider_font_size'] = 'inherit';
            $defaults[$section . '_sb_color'] = '#FAFAFA';
            $defaults[$section . '_slider_color'] = '#666';
            $defaults[$section . '_sh_color'] = '#666';
            $defaults[$section . '_sp_color'] = '#aaa';
            $defaults[$section . '_st_color'] = 'inherit';
            $defaults[$section . '_st_background_color'] = '#fff';
            switch( get_option( 'awf_range_slider_style', 'minimalistic' ) ) {
              case 'none':
              case 'minimalistic':
                $defaults[$section . '_slider_color'] = '#3FB8AF';
                $defaults[$section . '_sh_color'] = '#fff';
                break;
              case 'rounded':
              case 'bars':
              case 'marker':
                $defaults[$section . '_slider_color'] = '#dd333';
                $defaults[$section . '_st_background_color'] = '#fbfbfb';
                $defaults[$section . '_slider_font_size'] = '12px';
                break;
              case 'rounded_3d':
              case 'marker_3d':
                $defaults[$section . '_sh_color'] = '#fff';
                $defaults[$section . '_slider_color'] = '#dd333';
                $defaults[$section . '_st_background_color'] = '#fbfbfb';
                $defaults[$section . '_slider_font_size'] = '12px';
                break;

              default: break;
            }
						$defaults[$section . '_color'] = '#999';
						$defaults[$section . '_font_size'] = '0.9em';

            break;

					default: break;
				}
			}

			return $defaults;
		}
		
    /* Clear product counts cache on product updates and/or deletes */ 
    public function on_product_update( $product_id ) {  $this->clear_awf_cache(); }

    public function on_product_deletion( $product_id ) {
      if( 'product' === get_post_type( $product_id ) && 'trash' !== get_post_status( $product_id ) ) {
        $this->clear_awf_cache();
      }
    }

    public function on_product_trashing( $product_id ) {
      if( 'product' === get_post_type( $product_id ) ) {
        $this->clear_awf_cache();
      }
    }

    public function on_product_untrashing() {
      if( 'product' === $GLOBALS['post_type'] ) {
        $this->clear_awf_cache();
      }
    }
  
    public function on_product_cat_created( $term_id, $taxonomy_id ) {  $this->clear_product_counts_cache(); }
    public function on_product_cat_deleted( $term_id, $taxonomy_id, $deleted_term, $object_ids ) {  $this->clear_product_counts_cache(); }

    public function clear_product_counts_cache() {

      global $wpdb;

      $transient_name = '_transient_awf_counts_%';

      for( $i = 1; $i <= 5; $i++ ) {
        $sql = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s' LIMIT 10000";

        $transients = $wpdb->get_results( $wpdb->prepare( $sql, $transient_name ), ARRAY_A );

        if ( $transients && ! is_wp_error( $transients ) && is_array( $transients ) ) {

          foreach ( $transients as $transient ) {
            if ( is_array( $transient ) ) { $transient = current( $transient ); }
              delete_transient( str_replace( '_transient_', '', $transient ) );
          }

        } else {
          break;
        }
      }
    }
    /* endof Clear product counts cache on product updates and/or deletes */

    protected function clear_expired_awf_cache() {

      global $wpdb;

      $now = time();

      $transients_names = array( '_transient_timeout_awf_vss_products_cache_%', '_transient_timeout_awf_vss_variations_cache_%', '_transient_timeout_awf_vss_variable_term_id' );

      foreach( $transients_names as $transient_name ) {
        $sql = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s' AND option_value < $now LIMIT 5000";
        $transients = $wpdb->get_col( $wpdb->prepare( $sql, $transient_name ) );

        foreach ( $transients as $transient ) {
          delete_transient( str_replace( '_transient_timeout_', '', $transient ) );
        }
      }
    }

    public function clear_awf_cache() {

      global $wpdb;

      $transients_names = array( '_transient_timeout_awf_vss_products_cache_%', '_transient_timeout_awf_vss_variations_cache_%', '_transient_timeout_awf_vss_variable_term_id' );

      foreach( $transients_names as $transient_name ) {
        $sql = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s' LIMIT 5000";
        $transients = $wpdb->get_col( $wpdb->prepare( $sql, $transient_name ) );

        foreach ( $transients as $transient ) {
          delete_transient( str_replace( '_transient_timeout_', '', $transient ) );
        }
      }

      $this->clear_product_counts_cache();
    }

    public function detect_products_html_wrapper() {
      $response = array();

      $shop_page_id = wc_get_page_id( 'shop' );

			if( 'page' === get_option( 'show_on_front' ) && intval( get_option( 'page_on_front' ) ) === $shop_page_id ) {
				$shop_url = get_home_url();
				
			} else {
				if( empty( get_option( 'permalink_structure' ) ) ) {
					$shop_url = get_post_type_archive_link( 'product' );
				} else {
					$shop_url = get_permalink( $shop_page_id );
				}
			}

      $dom = new DomDocument();
      $dom->preserveWhiteSpace = false;

      $libxml_use_internal_errors_backup = (bool) libxml_use_internal_errors( true );

      if( ! $dom->loadHTMLFile( $shop_url ) ) {
        echo ( json_encode( array( 'error' => __( 'Wrapper detection aborted: couldn\'t load DOM', 'annasta-filters' ) ) ) );
        wp_die();
      }

      libxml_clear_errors();
      libxml_use_internal_errors( $libxml_use_internal_errors_backup );

      $xpath = new DomXPath( $dom );

      $selectors = get_option( 'awf_custom_selectors', array() );

      $products_classes = empty( $selectors['products'] ) ? '.products' : trim( $selectors['products'] );
      $response['messages'][] = 'Products selector: ' . $products_classes;
      $products_query = $this->get_xpath_query( $products_classes );
      $products_nodes = $xpath->query( $products_query );

      if( ! $products_nodes || 1 !== count( $products_nodes ) ) {
        $response['error'] = __( 'Wrapper detection aborted: couldn\'t find a unique products container. Set up the correct products list selector and try again.', 'annasta-filters' );

        echo ( json_encode( $response ) );
        wp_die();
      }

      $wrapper_node = false;
      $response['messages'][] = 'Products: ' . $products_nodes[0]->getNodePath();

      $products_node = $products_nodes[0];
      $products_parents = array();
      while( $products_node->parentNode ) {
        $products_parents[] = $products_node = $products_node->parentNode;
      }

      $pagination_classes = empty( $selectors['pagination'] ) ? '.woocommerce-pagination' : trim( $selectors['pagination'] );
      $pagination_query = $this->get_xpath_query( $pagination_classes );
      $pagination_nodes = $xpath->query( $pagination_query );

      if( $pagination_nodes && 0 < count( $pagination_nodes ) ) {
        $response['messages'][] = 'Pagination: ' . $pagination_nodes[0]->getNodePath();

        $pagination_node = $pagination_nodes[0];
        while( $pagination_node->parentNode ) {
          $pagination_node = $pagination_node->parentNode;
          foreach( $products_parents as $pp ) {
            if( $pp->isSameNode( $pagination_node ) ) {
              $wrapper_node = $pagination_node;
              $response['messages'][] = 'Wrapper: ' . $pagination_node->getNodePath();
              break 2;
            }
          }
        }

      } else {
        if( $products_nodes[0]->parentNode ) {
          $wrapper_node = $products_nodes[0]->parentNode;
          $response['error'] = __( 'Couldn\'t detect WooCommerce pagination containers! Please make sure that the shop pagination works properly, and if needed, set the custom pagination selector.', 'annasta-filters' );
          $response['messages'][] = 'Wrapper (products only): ' . $products_nodes[0]->parentNode->getNodePath();
        }
      }
      
      if( $wrapper_node ) {
        $i = 0;

        while( ( $wrapper_node ) && ( $i < 2 ) ) {

          if( ! empty( $wrapper_node->attributes ) ) {
            foreach ($wrapper_node->attributes as $name => $attr ) {
              if( 'id' === $name ) {
                $wrappers[] = '#' . $attr->nodeValue;
                if( 0 === $i ) { break 2; }

              } elseif( 'class' === $name && ! empty( $attr->nodeValue ) ) {
                $classes = explode( ' ', $attr->nodeValue );
                $classes = array_filter( $classes, function( $value ) { return !is_null( $value ) && $value !== ''; });

                if( ! empty( $classes ) ) { $wrappers[] = '.' . implode( '.', $classes ); }
              }
            }
          }

          $i++;
          $wrapper_node = $wrapper_node->parentNode;
        }
      }

      if( empty( $wrappers ) ) {
        $response['error'] = __( 'Couldn\'t detect the wrapper', 'annasta-filters' );

      } else {
        $response['messages'][] = $wrappers;
        $response['wrapper'] = array_shift( $wrappers );

        if( ! empty( $wrappers ) ) {
          $response['message'] = sprintf( __( 'If the new HTML wrapper doesn\'t work, make sure that you have the "Force wrapper reload" option disabled, and try the following selectors: %1$s', 'annasta-filters' ), '<br>' . implode( '<br>', $wrappers ) );
        }
      }

      echo json_encode( $response );
    }

    protected function get_xpath_query( $selector ) {

      if( false !== strpos( $selector, ' ' ) ) {
        echo ( json_encode( array( 'error' => __( 'Search algorithm doesn\'t support spaces in the products and pagination selectors.', 'annasta-filters' ) ) ) );
        wp_die();
      }

      $selector = ltrim( $selector, '.' );
      $classes = explode( '.', $selector );
      $xpath_classes = array();

      foreach( $classes as $class ) {
        if( false !== ( $pos = strpos( $selector, '#' ) ) ) {
          if( 0 === $pos ) {
            return( "//*[@id='" . substr( $class, 1 ) . "']" );
            break;
          }

        } else {
          $xpath_classes[] = "contains(concat(' ', normalize-space(@class), ' '), ' $class ')";
        }
      }

      if( ! empty( $xpath_classes ) ) {
        return "//*[" . implode( ' and ', $xpath_classes ) . "]";
      }

      echo ( json_encode( array( 'error' => __( 'Unable to detect the products and pagination selectors.', 'annasta-filters' ) ) ) );
      wp_die();
    }
    
    final function __clone() {} // prevent cloning
    final function __wakeup() {} // prevent serialization
    
    public static function get_instance() {
      if( is_null( self::$instance ) ) {
        $called_class = get_called_class();
        self::$instance = new $called_class;
      }
      return self::$instance;
    }
    
    /** Helper Functions */
    
    protected function get_sanitized_checkbox_setting( $filter, $setting, $no = false ) {
      $setting_name = $filter->prefix . $setting;

      if( isset( $_POST[$setting_name] ) ) { return ( 'yes' === $_POST[$setting_name] ); }
      else { return $no; }
    }
    
    protected function get_sanitized_text_field_setting( $setting, $default = '' ) {
      if( isset( $_POST[$setting] ) ) {
        return sanitize_text_field( stripslashes( $_POST[$setting] ) );
      }
      
      return $default;
    }
    
    protected function get_sanitized_int_setting( $setting, $default = 0 ) {
      if( isset( $_POST[$setting] ) ) { return (int) $_POST[$setting]; }
      
      return (int) $default;
    }
    
    public function display_admin_notice( $msg, $type = 'error', $dismissable = ' is-dismissible' ) {
      /* $type can take the following values: error, warning, info, success */ 
      echo ('<div class="notice notice-' . $type . $dismissable . '"><p>' . esc_html( $msg ) . '</p></div>');
    }
    
    public function build_select_html( $options ) {
      $html = '<select';
      if( isset( $options['id'] ) ) { $html .= ' id="' . esc_attr( $options['id'] ) . '"'; }
      if( isset( $options['name'] ) ) { $html .= ' name="' . esc_attr( $options['name'] ) . '"'; }
      if( isset( $options['class'] ) ) { $html .= ' class="' . sanitize_html_class( $options['class'] ) . '"'; }
      if( isset( $options['custom'] ) ) { $html .= $options['custom']; }
      $html .= '>';

      if( isset( $options['options'] ) ) {
        foreach( $options['options'] as $value => $label ) {
          $html .= '<option value="'. esc_attr( $value ) . '"';
          if( isset( $options['selected'] ) && $value === $options['selected'] ) { $html .= ' selected'; }
          if( isset( $options['disabled'] ) && in_array( $value, $options['disabled'] ) ) { $html .= ' disabled'; }
          $html .= '>' . esc_html( $label ) . '</option>';
        }
      }

      $html .= '</select>';

      return $html;
    }
    
    protected function convert_edge_spaces_to_nbsp( $string ) {
      $int_one = intval( 1 );
      
      if( 0 === strpos( $string, ' ' ) ) { $string = str_replace( ' ', "\xc2\xa0", $string, $int_one ); }
      if( ' ' === substr( $string, -1, 1 ) ) { $string = substr_replace( $string, "\xc2\xa0", -1, 1 ); }
      
      return $string;
    }

  //A_W_F::format_print_r( '' );
  }
}
?>