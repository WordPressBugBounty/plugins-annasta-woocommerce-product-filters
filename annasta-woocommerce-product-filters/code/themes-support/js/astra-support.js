jQuery( document ).on( 'awf_after_ajax_products_update', function() {
  if( 'undefined' !== typeof( AstraProQuickView ) ) {
    AstraProQuickView.init();
  }
});