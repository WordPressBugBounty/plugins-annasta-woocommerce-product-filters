<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( wp_doing_ajax() && isset( $_REQUEST['awf_front'] ) && isset( $_GET['awf_action'] ) ) {

  if( 'filter' === $_GET['awf_action'] ) {

    if( empty( get_option( 'awf_ppp_default' ) ) ) {
      add_filter( 'awf_set_ppp_default', function() { return get_theme_mod( 'ocean_woo_shop_posts_per_page', '12' ); } );
    }
  
    if ( true == get_theme_mod( 'ocean_woo_off_canvas_filter', false ) ) {
      add_action( 'woocommerce_before_shop_loop', function() {
        // See themes/oceanwp/inc/woocommerc/woocommerce-config.php > off_canvas_filter_button()
  
        $text = get_theme_mod( 'ocean_woo_off_canvas_filter_text' );
        $text = oceanwp_tm_translation( 'ocean_woo_off_canvas_filter_text', $text );
        $text = $text ? $text: esc_html__( 'Filter', 'oceanwp' );
  
        $output = '<a href="#" class="oceanwp-off-canvas-filter"><i class="icon-menu"></i><span class="off-canvas-filter-text">'. esc_html( $text ) .'</span></a>';
  
        echo apply_filters( 'oceanwp_off_canvas_filter_button_output', $output );
      }, 11 );
    }
  
    if ( get_theme_mod( 'ocean_woo_grid_list', true ) ) {
      add_action( 'woocommerce_before_shop_loop', function() {
        // See themes/oceanwp/inc/woocommerc/woocommerce-config.php > grid_list_buttons()
  
        // Titles
        $grid_view = esc_html__( 'Grid view', 'oceanwp' );
        $list_view = esc_html__( 'List view', 'oceanwp' );
  
        // Active class
        if ( 'list' == get_theme_mod( 'ocean_woo_catalog_view', 'grid' ) ) {
          $list = 'active ';
          $grid = '';
        } else {
          $grid = 'active ';
          $list = '';
        }
  
        $output = sprintf( '<nav class="oceanwp-grid-list"><a href="#" id="oceanwp-grid" title="%1$s" class="%2$sgrid-btn"><span class="icon-grid"></span></a><a href="#" id="oceanwp-list" title="%3$s" class="%4$slist-btn"><span class="icon-list"></span></a></nav>', esc_html( $grid_view ), esc_attr( $grid ), esc_html( $list_view ), esc_attr( $list ) );
  
        echo wp_kses_post( apply_filters( 'oceanwp_grid_list_buttons_output', $output ) );
      }, 11 );
  
    }
  
  } elseif( 'get_search_autocomplete' === $_GET['awf_action'] ) {
    add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 11 );
  }
  
}

?>