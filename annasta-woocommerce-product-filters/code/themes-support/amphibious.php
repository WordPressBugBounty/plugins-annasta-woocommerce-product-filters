<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

add_action( 'init', function() {
	if( ! empty( A_W_F::$front ) && 'yes' === get_option( 'awf_theme_support', 'yes' ) ) {
		A_W_F::$front->get_access_to['block_pte'] = true;
	}
}, 1000 );

?>