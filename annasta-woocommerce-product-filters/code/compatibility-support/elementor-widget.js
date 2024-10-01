jQuery( document ).ready( function( $ ) {

  a_w_f.initialize_preset_js = function( $preview_container ) {
    if( 'undefined' !== typeof PerfectScrollbar ) {
      $preview_container.find( '.awf-pretty-scrollbars' ).each( function( i, container ) {
        a_w_f.pretty_scrollbars.push( new PerfectScrollbar( container, { suppressScrollX: true } ) );
      });
    }
    
    $preview_container.find( '.awf-filter' ).each( function( i, filter ) {
      a_w_f.set_filter_events( $( filter ) );
    });
    
    $preview_container.find( '.awf-range-slider-container' ).each( function( i, el ) {
      a_w_f.build_range_slider( el );
    });

    $preview_container.find( '.awf-taxonomy-range-slider-container' ).each( function( i, el ) {
      a_w_f.build_taxonomy_range_slider( el );
    });

  };

  elementorFrontend.hooks.addAction( 'frontend/element_ready/awf_elementor_widget.default', function( $widget ) {
    a_w_f.initialize_preset_js( $widget );
  } );
  
  elementorFrontend.hooks.addAction( 'frontend/element_ready/shortcode.default', function( $widget ) {

    if( 0 < $widget.find( '.awf-preset-preview-html' ).first().length ) {
      a_w_f.initialize_preset_js( $widget );
    }

  } );

});