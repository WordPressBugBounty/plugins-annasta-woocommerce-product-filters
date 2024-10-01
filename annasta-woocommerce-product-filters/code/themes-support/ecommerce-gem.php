<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

add_filter( 'awf_set_shop_columns', 'ecommerce_gem_product_columns' );

add_filter( 'awf_set_ppp_default', function( $ppp ) {
  if( function_exists( 'ecommerce_gem_get_option' ) ) {
    $ppp = absint( ecommerce_gem_get_option( 'product_per_page' ) );
  }

  return $ppp;
});

?>