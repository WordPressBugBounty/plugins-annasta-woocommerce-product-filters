jQuery( document ).on( 'awf_after_ajax_products_update', function() {
  if( 'undefined' !== typeof( Flatsome ) ) {
    Flatsome.attach( 'quick-view', a_w_f.products_wrappers );
    Flatsome.attach( 'tooltips', a_w_f.products_wrappers )
    Flatsome.attach( 'add-qty', a_w_f.products_wrappers )
    Flatsome.attach( 'wishlist', a_w_f.products_wrappers )
  }
});
