<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

add_filter( 'awf_js_data', function( $js_data ) {

  if( isset( $js_data['ajax_pagination'] ) ) {
    $selectors = get_option( 'awf_custom_selectors', array() );
    if( empty( $selectors['page_number'] ) ) {
      $js_data['ajax_pagination']['page_number'] = 'a.page-number';
    }
  }

  return $js_data;
} );

add_action( 'wp_enqueue_scripts', function() {
  wp_register_script( 'awf-flatsome-support', A_W_F_PLUGIN_URL . '/code/themes-support/js/flatsome-support.js', array( 'awf' ), A_W_F::$plugin_version );
  wp_enqueue_script( 'awf-flatsome-support' );
} );

?>