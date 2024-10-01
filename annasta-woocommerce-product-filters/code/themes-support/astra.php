<?php

defined( 'ABSPATH' ) or die( 'Access denied' );


if( function_exists( 'astra_get_option' ) ) {

  if( 'disabled' !== astra_get_option( 'shop-quick-view-enable' ) ) {
    add_action( 'wp_enqueue_scripts', function() {
      wp_register_script( 'awf-astra-support', A_W_F_PLUGIN_URL . '/code/themes-support/js/astra-support.js', array( 'awf' ), A_W_F::$plugin_version );
      wp_enqueue_script( 'awf-astra-support' );
    } );
  }

  add_filter( 'awf_set_shop_columns', function( $columns ) {
    $astra_columns = astra_get_option( 'shop-grids' );
    $columns = $astra_columns['desktop'];

    return $columns;
  });

  add_filter( 'awf_set_ppp_default', function( $ppp ) {
    $ppp = astra_get_option( 'shop-no-of-products', 12 );

    return $ppp;
  });
}

if( wp_doing_ajax() && isset( $_REQUEST['awf_front'] ) && isset( $_GET['awf_action'] ) && 'filter' === $_GET['awf_action'] ) {
  remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
  remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
  remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
  remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );

  //    add_action( 'woocommerce_before_shop_loop', 'woocommerce_pagination', 50 );
  add_action( 'woocommerce_before_shop_loop_item', 'astra_woo_shop_thumbnail_wrap_start', 6 );

  /** Add sale flash before shop loop. */
  add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_show_product_loop_sale_flash', 9 );

  add_action( 'woocommerce_after_shop_loop_item', 'astra_woo_shop_thumbnail_wrap_end', 8 );

  /** Add Out of Stock to the Shop page */
  add_action( 'woocommerce_shop_loop_item_title', 'astra_woo_shop_out_of_stock', 8 );

  remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

  /** Shop Page Product Content Sorting */
  add_action( 'woocommerce_after_shop_loop_item', 'astra_woo_woocommerce_shop_product_content' );
}

?>