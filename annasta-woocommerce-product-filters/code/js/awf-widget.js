jQuery( document ).ready( function( $ ) {
  
  'use strict';
  
  $( document ).on( 'widget-added', function(event, widget) {
    $('.awf-widget-add-new:not(#widget-awf_widget-__i__-title)').each( function( i, el ) {
      $( el ).trigger( 'change' ).removeClass( 'awf-widget-add-new' );
    });
  });
  
  
});