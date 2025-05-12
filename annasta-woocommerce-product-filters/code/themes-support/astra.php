<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

add_action( 'init', function() {
  if( function_exists( 'astra_get_option' ) ) {
    if( 'disabled' !== astra_get_option( 'shop-quick-view-enable' ) ) {
      add_action( 'wp_enqueue_scripts', function() {
        wp_register_script( 'awf-astra-support', A_W_F_PLUGIN_URL . '/code/themes-support/js/astra-support.js', array( 'awf' ), A_W_F::$plugin_version );
        wp_enqueue_script( 'awf-astra-support' );
      } );
    }
  }
} );

if( wp_doing_ajax() && isset( $_REQUEST['awf_front'] ) && isset( $_GET['awf_action'] ) && 'filter' === $_GET['awf_action'] ) {
  remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
  remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
  remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
  remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );

  add_action( 'woocommerce_before_shop_loop_item', 'astra_woo_shop_thumbnail_wrap_start', 6 );

  /** Add sale flash before shop loop. */
  add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_show_product_loop_sale_flash', 9 );

  add_action( 'woocommerce_after_shop_loop_item', 'astra_woo_shop_thumbnail_wrap_end', 8 );

  /** Add Out of Stock to the Shop page */
  add_action( 'woocommerce_shop_loop_item_title', 'astra_woo_shop_out_of_stock', 8 );

  remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

  /** Shop Page Product Content Sorting */
  add_action( 'woocommerce_after_shop_loop_item', 'astra_woo_woocommerce_shop_product_content' );

  add_action( 'init', function() {
    if( function_exists( 'astra_get_option' ) && 'dedicated_ajax' === get_option( 'awf_ajax_mode', 'compatibility_mode' ) ) {
  
      if( empty( get_option( 'awf_shop_columns', 0 ) ) ) {
        add_filter( 'awf_set_shop_columns', function( $columns ) {
          $grid = astra_get_option( 'shop-grids' );

          if( wp_is_mobile() && isset( $grid['mobile'] ) ) {
            $columns = $grid['mobile'];
          } else {
            if( isset( $grid['desktop'] ) ) {
              $columns = $grid['desktop'];
            }
          }
    
          return $columns;
        });
      }
  
      if( empty( get_option( 'awf_ppp_default', 0 ) ) ) {
        add_filter( 'awf_set_ppp_default', function( $ppp ) {
          $ppp = astra_get_option( 'shop-no-of-products', 12 );
    
          return $ppp;
        });
      }
    }
  } );
  
}

?>