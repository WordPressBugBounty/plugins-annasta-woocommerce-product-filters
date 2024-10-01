<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! is_null( A_W_F::$front ) ) {
  $awf_template_options = get_option( 'awf_product_list_template_options', array() );
  
  if( ! empty( $awf_template_options['default_wc_pagination'] ) ) {
    add_action( 'wp_enqueue_scripts', function() {
      wp_register_script( 'awf-rife-free-support', A_W_F_PLUGIN_URL . '/code/themes-support/js/rife-free-support.js', array( 'awf' ), A_W_F::$plugin_version );
      wp_enqueue_script( 'awf-rife-free-support' );
    } );
  }
}

?>