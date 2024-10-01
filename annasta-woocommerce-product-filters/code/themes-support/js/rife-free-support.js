jQuery( document ).ready( function( $ ){
  if( 'undefined' !== typeof( a_w_f ) ) {
		$( '.woocommerce-pagination').not( '.awf-woocommerce-pagination' ).remove();
		$( '.woocommerce-pagination').addClass( 'navigation' );
		$( document ).on( 'awf_after_ajax_products_update', function() {
			$( '.woocommerce-pagination').addClass( 'navigation' );
		} );
  }
});