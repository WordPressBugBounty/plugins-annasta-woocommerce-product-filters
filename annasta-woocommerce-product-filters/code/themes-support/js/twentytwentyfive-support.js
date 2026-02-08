jQuery( document ).ready( function( $ ){
  if( 'undefined' !== typeof( awf_data ) && 'ajax_pagination' in awf_data ) {
    awf_data.ajax_pagination.block_pagination = true;
  }
});