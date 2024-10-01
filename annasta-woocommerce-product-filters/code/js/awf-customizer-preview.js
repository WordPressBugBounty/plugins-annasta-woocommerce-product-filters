var a_w_f_customizer_preview = {};

( function( $ ) {
	a_w_f_customizer_preview.block_presets = function() {
		$( '.awf-preset-wrapper' ).block({ message: '' });
	};

	a_w_f_customizer_preview.unblock_presets = function() {
		$( '.awf-preset-wrapper' ).unblock();
	};
} )( jQuery );

jQuery( document ).ready( function( $ ){
	'use strict';
	
	$( 'head' ).append( '<style id="awf-preview-css" type="text/css"></style>' );
	
	$( '.awf-preset-wrapper:not(.awf-togglable-preset), .awf-togglable-preset-btn:visible' ).first().before( '<button aria-label="' + awf_customizer_preview_data.i18n.awf_focus_panel_button_label + '" title="' + awf_customizer_preview_data.i18n.awf_focus_panel_button_label + '" class="awf-focus-panel-button"></button>' );
	
	$( '.awf-focus-panel-button' ).on( 'click', function() {
		wp.customize.preview.send( 'focus-annasta-filters-panel' );
	});
	
} );