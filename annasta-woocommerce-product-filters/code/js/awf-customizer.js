/**
 * annasta Woocommerce Product Filters live preview functionality for Wordpress Customizer
 */

var a_w_f_customizer = { settings: {} };

( function( $ ) {
	
	if ( ! wp || ! wp.customize ) { return; }
	
	a_w_f_customizer.udate_awf_preview_css = function( css ) {
		const preview_iframe = wp.customize.previewer.preview.iframe[0];
		
		if( 'undefined' !== typeof( preview_iframe ) ) {
			$( preview_iframe ).contents().find( 'style#awf-preview-css' ).text( css );

			if( 'undefined' !== typeof( preview_iframe.contentWindow.a_w_f_customizer_preview ) ) {
				preview_iframe.contentWindow.a_w_f_customizer_preview.unblock_presets();
			}
		}
	};
	
	wp.customize.bind( 'saved', function () {
		
		if( wp.customize.awf_customizer_dirty ) {
			wp.customize.previewer.refresh();
		}
		
		wp.customize.awf_customizer_dirty = false;
	} );
		
	wp.customize.bind( 'ready', function () {
		
		wp.customize.awf_customizer_dirty = false;
		
		wp.customize.panel( 'annasta-filters' ).notifications.add( new wp.customize.Notification( 'awf_notification', {
			message: awf_customizer_data.i18n.awf_notification,
			type: 'info',
			dismissible: true
		} ) );
		
		$( '.awf-customizer-reset-section-button' ).on( 'click', function() {
			_.each( wp.customize.section( $( this ).attr( 'data-section' ) ).controls(), function( control ) {
				
				var value = wp.customize( control.setting.id ).get();
				
				if( true === value && 'checkbox' === control.params.type ) {
					wp.customize( control.setting.id ).set( false );
					
				} else if( 0 !== value.length ) {
					if(  -1 !== $.inArray( control.params.type, ['color','awf-alpha-color'] ) ) {
						control.container.find( '.wp-picker-clear').trigger( 'click' );
						
					} else if( -1 !== $.inArray( control.params.type, ['text','number','select'] ) && -1 === $.inArray( control.params.settings.default, ['awf_toggle_btn_label','awf_popup_close_btn_label', 'awf_range_slider_style', 'awf_customizer_options[awf_popup_sidebar_popup_position]'] ) ) {
						wp.customize( control.setting.id ).set( '' );
					}
				}
				
			} );
		} );
		
		wp.customize.awf_regenerate_css = function() {
			
			var changed_settings = {};
			
			if( 'undefined' !== typeof( wp.customize.previewer.preview ) && 'undefined' !== typeof( wp.customize.previewer.preview.iframe[0].contentWindow.a_w_f_customizer_preview ) ) {
				wp.customize.previewer.preview.iframe[0].contentWindow.a_w_f_customizer_preview.block_presets();
			}

			$.each( wp.customize.dirtyValues(), function( setting_id, value ) {
				if( setting_id in a_w_f_customizer.settings ) {
			
					if( 'awf_custom_style' === setting_id && 'deprecated-1-3-0' !== value ) {
						if( $.inArray( 'awf_custom_style_notification', wp.customize.notifications.get() ) ) {
							wp.customize.notifications.remove( 'awf_custom_style_notification' );
						}
						
						wp.customize.control( 'awf_custom_style' ).notifications.remove( 'awf_custom_style_control_notification' );

					} else if( 'awf_range_slider_style' === setting_id || 'awf_customizer_options[awf_popup_sidebar_popup_position]' === setting_id ) {
						
						var sid = ( 'awf_range_slider_style' === setting_id ) ? setting_id : 'awf_popup_sidebar_popup_position';

						wp.customize.control( sid ).notifications.add( new wp.customize.Notification( sid + '_control_notification', {
							message: awf_customizer_data.i18n.awf_publish_to_preview_notification,
							type: 'info',
							dismissible: true
							} ) );
					}
					
					changed_settings[a_w_f_customizer.settings[setting_id]] = value;
				}
			} );
			
			if( ! $.isEmptyObject( changed_settings ) ) {
				
				wp.customize.awf_customizer_dirty = true;
				
				if( 'undefined' !== typeof( wp.customize( 'awf_custom_style' ) ) && 'none' !== wp.customize( 'awf_custom_style' ).get() ) {
					wp.customize.notifications.add( new wp.customize.Notification( 'awf_custom_style_notification', {
						message: awf_customizer_data.i18n.awf_custom_style_notification,
						type: 'warning',
						dismissible: true
					} ) );
					
					wp.customize.control( 'awf_custom_style' ).notifications.add( new wp.customize.Notification( 'awf_custom_style_control_notification', {
						message: awf_customizer_data.i18n.awf_custom_style_control_notification
					} ) );
				}
				
				$.ajax({
					type:     "post",
					url:      "admin-ajax.php",
					dataType: "html",
					data:     {
						action: 'awf_admin',
						awf_action: 'regenerate_customizer_css',
						awf_customizer_settings: changed_settings,
						awf_ajax_referer: awf_customizer_data.awf_ajax_referer
					},
					success:  function( css ) {
						a_w_f_customizer.udate_awf_preview_css( css );
					},
					error: function( response ) { console.log( response ); }
				});
				
			} else {
				a_w_f_customizer.udate_awf_preview_css( '' );
			}
		};
		
		var sections = wp.customize.panel( 'annasta-filters' ).sections();
		$.each( sections, function( i, section ) {

			_( section.controls() ).each( function ( control ) {
				
				a_w_f_customizer.settings[control.setting.id] = control.id;
				
				if( 'refresh' !== control.setting.transport ) {
					wp.customize( control.setting.id, function( setting ) {
						setting.bind( _.debounce( wp.customize.awf_regenerate_css, 500 ) );
					} );
				}

			});
		});
		
		wp.customize.previewer.bind( 'ready', function() {
			wp.customize.awf_regenerate_css();
			wp.customize.previewer.bind( 'focus-annasta-filters-panel', function() { wp.customize.control( 'awf_custom_style' ).focus(); } );
		} );

	} );
	
} )( jQuery );
