<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( defined( 'YITH_WOOCOMPARE' ) ) {
	if( wp_doing_ajax() && isset( $_REQUEST['awf_front'] ) && isset( $_GET['awf_action'] ) && 'filter' === $_GET['awf_action'] ) {
		$_REQUEST['context'] = 'frontend';
		$_REQUEST['action'] = 'annasta_woocommerce_product_filters';

		add_filter( 'yith_woocompare_actions_to_check_frontend', function( $actions ) {
			$actions[] = 'annasta_woocommerce_product_filters';
			return $actions;
		} );
	}
}

/* Perfect Brands conflict resolution */

if( defined( 'PWB_PLUGIN_VERSION' ) ) {
	add_action( 'pre_update_option_awf_query_vars', function( $new_value, $old_value, $option_name ) {
		if( isset( $new_value['tax']['pwb-brand'] ) && false === get_option( 'awf_pwb-brand_pretty_name', false ) ) {
      $new_value['tax']['pwb-brand'] = 'filter-pwb-brand';
		}

		return $new_value;
	}, 10, 3 );
}

function awf_initialize_elementor_compatibility_support () {
  include_once( A_W_F_PLUGIN_PATH . 'code/compatibility-support/elementor.php' );
}
add_filter( 'elementor/init', 'awf_initialize_elementor_compatibility_support' );

if( class_exists('RankMath') ) {
	include_once( A_W_F_PLUGIN_PATH . 'code/compatibility-support/rank_math.php' );
}

?>