<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

add_action( 'init', function() {
  if( ! empty( A_W_F::$front ) ) {
    A_W_F::$front->get_access_to['force_ppt_on_fps'] = true;
  }

  add_action( 'wp_enqueue_scripts', function() {
    wp_register_script( 'awf-twentytwentyfive-support', A_W_F_PLUGIN_URL . '/code/themes-support/js/twentytwentyfive-support.js', array( 'jquery' ), A_W_F::$plugin_version );
    wp_enqueue_script( 'awf-twentytwentyfive-support' );
  }, 5 );

} );