<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_neve_theme_support' ) ) {
  
  class A_W_F_neve_theme_support {
    
    public function __construct() {
      
      if( wp_doing_ajax() && isset( $_REQUEST['awf_front'] ) && isset( $_GET['awf_action'] ) && 'filter' === $_GET['awf_action'] && ! isset( $_GET['awf_sc_page'] ) ) {
        add_action( 'awf_ajax_filter_before_wc_products_shortcode', array( $this, 'ajax_fixes') );
      }
    }
    
    public function ajax_fixes() {
      remove_action( 'awf_add_ajax_products_header_title', array( A_W_F::$front, 'add_ajax_products_header_title' ) );
      
      add_action( 'nv_woo_header_bits', 'woocommerce_catalog_ordering', 30 );
      add_action( 'woocommerce_before_shop_loop', array( $this, 'before_shop_loop_0' ), 0 );
    }

    public function before_shop_loop_0() {
      echo '<div class="nv-bc-count-wrap">';
      woocommerce_result_count();
      echo '</div>';

      echo '<div class="nv-woo-filters">';
      $this->sidebar_toggle();
      do_action( 'nv_woo_header_bits' );
      echo '</div>';
    }
    
    public function sidebar_toggle() {

      if ( ! $this->should_render_sidebar_toggle() ) {
        return;
      }

      $button_text  = apply_filters( 'neve_filter_woo_sidebar_open_button_text', __( 'Filter', 'neve' ) . '»' );
      $button_attrs = apply_filters( 'neve_woocommerce_sidebar_filter_btn_data_attrs', '' );
      echo '<a class="nv-sidebar-toggle" ' . wp_kses_post( $button_attrs ) . '>' . esc_html( $button_text ) . '</a>';
    }
    
    /**
     * Check if we should render the mobile sidebar toggle.
     *
     * @return bool
     */
    private function should_render_sidebar_toggle() {
      if ( ! is_active_sidebar( 'shop-sidebar' ) ) {
        return false;
      }
      
      $theme_mod = apply_filters( 'neve_sidebar_position', get_theme_mod( 'neve_shop_archive_sidebar_layout', 'right' ) );
      if ( $theme_mod !== 'right' && $theme_mod !== 'left' ) {
        return false;
      }

      return true;
    }
  }
}
?>