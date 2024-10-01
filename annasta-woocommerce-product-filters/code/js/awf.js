/* annasta Woocommerce Product Filters */

var a_w_f = typeof( a_w_f ) === 'undefined' ? {} : a_w_f;
a_w_f.pretty_scrollbars = [];
a_w_f.daterangepickers = {};

var awf_data = typeof( awf_data ) === 'undefined' ? { query: {} } : awf_data;

jQuery( document ).ready( function( $ ){
  'use strict';

  a_w_f.set_filter_events = function( $filter ) {
    
    var $preset_wrapper = $filter.closest( '.awf-preset-wrapper' );
    var $filter_container = $filter.closest( '.awf-filter-container' );
    
    if( 'premium' in a_w_f ) { a_w_f.set_premium_filter_events( $filter_container, $filter ); }
    
    if( $preset_wrapper.hasClass( 'awf-url' ) && ( $filter.is(':checkbox') || $filter.is(':radio') ) ) {
      if( $filter.hasClass( 'awf-button-filter' ) ) {
        $filter.on( 'click', function( e ) {
          e.stopPropagation();
        });

        $filter.closest( 'a' ).on( 'click', function( e ) {
          e.preventDefault();
          $filter.trigger( 'click' );
        });
  
      } else {
        $filter.on( 'click', function() {
          window.location.href = $( this ).closest( 'a' ).attr( 'href' );
        });
      }
    }
    
    if( $filter_container.hasClass( 'awf-active' ) ) {
      if( $filter_container.closest( 'ul.awf-children-container' ).first().hasClass( 'awf-collapsed' ) ) {
        $filter_container.parents( 'ul.awf-children-container' ).removeClass( 'awf-collapsed' ).prev( 'li.awf-parent-container' ).removeClass( 'awf-collapsed-on' );
      }
      $filter_container.removeClass( 'awf-collapsed-on' ).next( 'ul.awf-children-container' ).removeClass( 'awf-collapsed' );
    }
      
    if( $filter_container.hasClass( 'awf-product-search-container' ) ) {

      $filter.on( 'keydown', function( event ) {
        if( event.keyCode === 13 ) {
          if( $filter.hasClass( 'awf-button-filter' ) ) {
            $filter.closest( '.awf-filter-wrapper' ).find( '.awf-apply-filter-btn, .awf-submit-btn' ).first().focus();
          } else {
            $filter.trigger( 'blur' );
          }

          return false;
        }
      });

      $filter.siblings( '.awf-clear-search-btn' ).on( 'focusin', function() {
        $filter.addClass( 'awf-cancel-product-search-update' );
        $( '#' + $filter.attr( 'id' ) + '-autocomplete-container' ).first().html( '' );
        $filter.val( '' ).trigger( 'focus' );
      } );
      
      $filter.on( 'blur', function() {
        if( $filter.hasClass( 'awf-cancel-product-search-update' ) ) {
          $filter.removeClass( 'awf-cancel-product-search-update' );
          if( 0 === $filter.val().length ) { $filter.trigger( 'change' ); }
        }
      });

      if( $filter_container.hasClass( 'awf-search-autocomplete' ) ) {
        var $autocomplete = $( '#' + $filter.attr( 'id' ) + '-autocomplete-container' ).first();

        $filter.on( 'focusin', function() {
          $autocomplete.removeClass( 'awf-collapsed' );
        });

        $filter_container.on( 'focusout', function( e ) { 
          if( $filter_container.has( e.relatedTarget ).length === 0 ) { $autocomplete.addClass( 'awf-collapsed' ); }
        });

        a_w_f.autocomplete_cid = 0;
        var debounce_autocomplete = null;

        $filter.on('input', function() {
          var cid = a_w_f.autocomplete_cid = ++a_w_f.autocomplete_cid;
          clearTimeout( debounce_autocomplete );

          debounce_autocomplete = setTimeout( function() {
            if( cid === a_w_f.autocomplete_cid ) {
              a_w_f.get_search_autocomplete_products( $filter_container, $filter, $autocomplete );
            }
          }, 400);
        } );
        
        $filter.on( 'change', function() {

          setTimeout( function() {
            if( $autocomplete.has( document.activeElement ).length === 0 ) {

              var old_value = ( $filter.attr( 'data-taxonomy' ) in awf_data.query ) ? awf_data.query[$filter.attr( 'data-taxonomy' )] : '';

              if( $filter.val() !== old_value ) {

                if( ! $filter.hasClass( 'awf-cancel-product-search-update' ) ) {
                  a_w_f.product_search_onchange( $filter );
                  if( ( ! $preset_wrapper.hasClass( 'awf-button' ) && ! $filter.hasClass( 'awf-button-filter' ) ) || $filter.hasClass( 'awf-submit-on-change') ) {
                    a_w_f.apply_filter( true, $preset_wrapper );
                  }
                }

                $filter.removeClass( 'awf-cancel-product-search-update' );
              }
            }
          }, 100 );
        });
        
      } else {

        $filter.on( 'change', function( e ) {
          var old_value = ( $filter.attr( 'data-taxonomy' ) in awf_data.query ) ? awf_data.query[$filter.attr( 'data-taxonomy' )] : '';

          if( $filter.val() !== old_value ) {

            setTimeout( function() {
              if( ! $filter.hasClass( 'awf-cancel-product-search-update' ) ) {
                a_w_f.product_search_onchange( $filter );
                if( ( ! $preset_wrapper.hasClass( 'awf-button' ) && ! $filter.hasClass( 'awf-button-filter' ) ) || $filter.hasClass( 'awf-submit-on-change') ) {
                  a_w_f.apply_filter( true, $preset_wrapper );
                }
              }

              $filter.removeClass( 'awf-cancel-product-search-update' );
            }, 100 );
          }
        });
      }

    } else if( $filter_container.hasClass( 'awf-daterangepicker-container' ) ) {
      
      a_w_f.setup_daterangepicker( $filter, $filter_container, $preset_wrapper );
  
    } else {
      /* No jQuery solution https://github.com/jquery/jquery/issues/2871*/
      $filter.siblings('label').each( function() {
        this.addEventListener( 'touchstart', function() { $filter_container.addClass( 'awf-hover-off' ); }, { passive: true } );
      } );
      
      $filter.on( 'click', function() {
        a_w_f.filter_onclick( $filter );

        if( $filter.is( '[data-archive-permalink]' ) ) {
          awf_data.filters_url = $filter.attr( 'data-archive-permalink' );
          awf_data.archive_page_switch = 'archive switch unavailable';
        }
      });
      
      if( ! $preset_wrapper.hasClass( 'awf-button' ) && ! $filter.hasClass( 'awf-button-filter' ) ) {
        $filter.on( 'click', function() { a_w_f.apply_filter( $filter, $preset_wrapper ); });
      }
    }
  };

  a_w_f.product_search_onchange = function( $searchbox ) {

    if( $searchbox.hasClass( 'awf-button-filter' ) && ! ( 'pre_button_query' in awf_data ) ) {
      awf_data.pre_button_query = $.extend( true, {}, awf_data.query );
    }
    
    $( '.awf-product-search-container .awf-filter' ).each( function( i, el ) {
      
      var $container = $( el ).closest( '.awf-filter-container' );

      if( 0 === $( $searchbox ).val().length ) { $container.removeClass( 'awf-active' ); }
      else { $container.addClass( 'awf-active' ); }
      
      if( el === $searchbox[0] ) { a_w_f.update_query( $searchbox, $container, true ); }
      else { $( el ).val( $( $searchbox ).val() ); }
      
    });
  };
  
  a_w_f.get_search_autocomplete_products = function( $filter_container, $filter, $autocomplete ) {

    var $autocompletes = $( '.awf-product-search-autocomplete-container' );

    if( $autocomplete.attr( 'data-after' ) < $filter.val().length ) {
      $filter_container.addClass( 'awf-autocomplete-searching' );
      
      var data = { 
        action: 'awf', 
        awf_action: 'get_search_autocomplete', 
        awf_front: 1,
        awf_filter: $filter.closest( '.awf-filter-wrapper' ).first().attr( 'id' ),
        awf_ajax_extras: ( 'ajax_extras' in awf_data ) ? awf_data.ajax_extras : ''
      };
      
      if( 'sc_page' in awf_data ) { data.awf_sc_page = awf_data.sc_page; }
      else if( 'archive_page' in awf_data ) { data.awf_archive_page = awf_data.archive_page; }
      
      data.awf_query = $.extend( true, {}, awf_data.query );
      data.awf_query[$filter.attr('data-taxonomy')] = $filter.val();
      
      $.ajax({
        type:     "get",
        url:      awf_data.ajax_url,
        dataType: "html",
        data:     data,
        success:  function( response ) {
          if( response ) {
            var $response = $( response );
            if( $response.hasClass( 'woocommerce' ) ) {
              $response.removeClass().addClass( 'awf-ac-container' ).find( awf_data.products_container ).removeClass().addClass( 'awf-ac-products-container' );
              $autocompletes.empty().append( $response );
            }

            $filter_container.removeClass( 'awf-autocomplete-searching' );
            
            if( $autocompletes.hasClass( 'awf-pretty-scrollbars' ) ) {
              $.each( a_w_f.pretty_scrollbars, function( i, ps ) {
                $autocompletes.each( function() {
                  if( ps.element === this ) {
                    ps.update();
                    return false;
                  }
                });
              });
            }
          }
        },
        error: function( response ) { console.log( response ); }
      });
      
    } else {
      $autocompletes.empty();
    }
  };
  
  a_w_f.setup_daterangepicker = function( $filter, $filter_container, $preset_wrapper ) {
      
      if( 'undefined' !== typeof daterangepicker && 'undefined' !== typeof moment ) {
        var $daterangepicker = $filter_container.find( '.awf-daterangepicker' );
        var daterangepicker_options = {
          'showDropdowns': true,
          'isUTC': true,
          locale: { cancelLabel: $daterangepicker.attr( 'data-clear-btn-label' ) }
        };
        
        if( $daterangepicker.hasClass( 'awf-single-daterangepicker' ) ) {
          daterangepicker_options.singleDatePicker = true;
        }
        
        if( $daterangepicker.hasClass( 'awf-timepicker' ) ) {
          daterangepicker_options.timePicker = true;
          daterangepicker_options.timePicker24Hour = true;
          daterangepicker_options.locale.format = 'DD/MM/YY HH:mm';
          $daterangepicker.addClass( '' );
          
        } else {
          daterangepicker_options.locale.format = 'DD/MM/YYYY';
        }
        
        if( 0 < $filter.val().length ) {
          var values = $filter.val().split( ',' );
          $.each( values, function( i, el ) {
            if( 0 === i ) {
              daterangepicker_options.startDate = moment.unix( el ).utc().format( daterangepicker_options.locale.format );
            } else if( 1 === i ) {
              daterangepicker_options.endDate = moment.unix( el ).utc().format( daterangepicker_options.locale.format );
            }
          });
        }
        
        if( 'filter_daterangepicker_options' in a_w_f ) {
          daterangepicker_options = a_w_f.filter_daterangepicker_options( $daterangepicker, daterangepicker_options );
        }
        
        $daterangepicker.daterangepicker( daterangepicker_options, function( start, end ) {
          
          if( $daterangepicker.hasClass( 'awf-single-daterangepicker' ) ) {
            if( 'timePicker' in daterangepicker_options ) {
              $filter.val( start.utc( true ).unix() );
            } else { $filter.val( start.utc( true ).startOf( 'date' ).unix() ); }
            
          } else {
            if( 'timePicker' in daterangepicker_options ) {
              $filter.val( start.utc( true ).unix() + ',' + end.utc( true ).unix() );
            } else { $filter.val( start.utc( true ).startOf( 'date' ).unix() + ',' + end.utc( true ).startOf( 'date' ).unix() ); }
          }
        });
        
        a_w_f.daterangepickers[$daterangepicker.attr( 'id' )] = $daterangepicker;
        
        $daterangepicker.on( 'apply.daterangepicker', function( event, picker ) {
          a_w_f.daterangepicker_update_values( $daterangepicker, $filter, $filter_container );
          if( ! $preset_wrapper.hasClass( 'awf-button' ) ) { a_w_f.apply_filter( true, $preset_wrapper ); }
        });
        $daterangepicker.on( 'cancel.daterangepicker', function() {
          $filter.val( '' );
          a_w_f.daterangepicker_update_values( $daterangepicker, $filter, $filter_container );
          if( ! $preset_wrapper.hasClass( 'awf-button' ) ) { a_w_f.apply_filter( true, $preset_wrapper ); }
        });
        $daterangepicker.on( 'hide.daterangepicker', function() {
          if( 0 === $filter.val().length ) { $daterangepicker.val( '' ); }
        });
        
        if( 0 === $filter.val().length ) { $daterangepicker.val( '' ); }
      }
  };
  
  a_w_f.filter_onclick = function( $filter ) {
    var $container = $filter.closest( '.awf-filter-container' );
    var taxonomy = $filter.attr( 'data-taxonomy' );
    var $filter_wrappers =  $( '.awf-filter-wrapper[data-taxonomy="' + taxonomy + '"]' );

    if( $filter.hasClass( 'awf-button-filter' ) && ! ( 'pre_button_query' in awf_data ) ) {
      awf_data.pre_button_query = $.extend( true, {}, awf_data.query );
    }
    
    if( $container.hasClass( 'awf-active' ) ) {
      
      if( $container.hasClass( 'awf-block-deselection' ) && -1 === awf_data.query[taxonomy].toString().indexOf( ',' ) ) {
        $filter.prop( 'checked', true );
        return;
      }

      if( $filter.is( ':radio' ) ) {
        
        if( $container.hasClass( 'awf-range-filter-container' ) ) {
          var $slider = $filter_wrappers.find( '.awf-style-range-slider .awf-filter-container' ).first();
          if( $slider.length > 0 ) {
            a_w_f.reset_filter_value( $filter, $slider );
            return;
          }

          $filter_wrappers.filter( '.awf-range' ).find( 'input.awf-filter[value="' + $filter.val() + '"][data-next-value="' + $filter.attr( 'data-next-value' ) + '"]' ).prop( 'checked', false ).closest( '.awf-filter-container' ).removeClass( 'awf-active' );

        } else {
          $filter_wrappers.filter( '.awf-single' ).find( '.awf-filter-container.awf-active' ).removeClass( 'awf-active' ).find( 'input' ).prop( 'checked', false );
          $filter_wrappers.filter( '.awf-multi' ).find( 'input.awf-filter[value="' + $filter.val() + '"]' ).prop( 'checked', false ).closest( '.awf-filter-container' ).removeClass( 'awf-active' );
          
          var $defaults = $filter_wrappers.find( '.awf-default' );
          if( 0 < $defaults.length ) {
            $filter = $defaults.first();
            $container = $filter.closest( '.awf-filter-container' );
            $defaults.prop( 'checked', true ).closest( '.awf-filter-container' ).addClass( 'awf-active' );
          }
        }

      } else {
        $filter_wrappers.find( 'input.awf-filter[value="' + $filter.val() + '"]' ).prop( 'checked', false ).closest( '.awf-filter-container' ).removeClass( 'awf-active' );
      }
      
    } else {

      if( $filter.is( ':radio' ) ) {

        var $filter_containers = $filter_wrappers.find( '.awf-filter-container' );
        $filter_containers.removeClass( 'awf-active' ).find( 'input' ).prop( 'checked', false );
        
        if( $container.hasClass( 'awf-range-filter-container' ) ) {
          var $slider = $filter_wrappers.find( '.awf-style-range-slider .awf-filter-container' ).first();
          if( $slider.length > 0 ) {
            var min = parseFloat( $filter.val() );
            var max = parseFloat( $filter.attr( 'data-next-value' ) );
            $slider[0].noUiSlider.set( [min, max] );
            a_w_f.range_slider_update_values( $slider[0], [min, max], $slider[0].noUiSlider.options.range.min[0], $slider[0].noUiSlider.options.range.max[0] );
            
            return;
          }

          $filter_containers.find( 'input.awf-filter[value="' + $filter.val() + '"][data-next-value="' + $filter.attr( 'data-next-value' ) + '"]' ).prop( 'checked', true ).closest( '.awf-filter-container' ).addClass( 'awf-active' );

        } else {
          $filter_containers.find( 'input.awf-filter[value="' + $filter.val() + '"]' ).prop( 'checked', true ).closest( '.awf-filter-container' ).addClass( 'awf-active' );
        }
                
      } else {
        $filter_wrappers.filter( '.awf-single' ).find( '.awf-filter-container.awf-active' ).removeClass( 'awf-active' ).find( 'input' ).prop( 'checked', false );
        $filter_wrappers.find( 'input.awf-filter[value="' + $filter.val() + '"]' ).prop( 'checked', true ).closest( '.awf-filter-container' ).addClass( 'awf-active' );
        $filter_wrappers.filter( '.awf-multi' ).find( 'input.awf-filter[value="' + $filter.val() + '"]' ).each( function() {
          a_w_f.uncheck_parents_and_children( this );
        });
      }
    }

    if( $filter.hasClass( 'awf-hierarchical-sbs-taxonomy' ) ) { a_w_f.hierarchical_sbs_onclick( $filter, $container, taxonomy ); }

    if( $filter_wrappers.hasClass( 'awf-active-dropdown-title' ) ) {
      a_w_f.set_active_dropdown_title( $filter_wrappers );
    }

    a_w_f.update_query( $filter, $container, true );
  };
  
  a_w_f.uncheck_parents_and_children = function( checkbox ) {
    $( checkbox ).parents( 'ul' ).prev( 'li.awf-filter-container.awf-active' ).each( function( i, container ) {
      $( container ).removeClass( 'awf-active' ).find( '.awf-filter' ).each( function( ii, filter ) {
        $( filter ).prop( 'checked', false );
        a_w_f.update_query( $( filter ), $( container ), false );
      });
    });
    
    $( checkbox ).parents( 'li.awf-filter-container' ).next( 'ul' ).find( '.awf-filter-container.awf-active' ).each( function( i, container ) {
      $( container ).removeClass( 'awf-active' ).find( '.awf-filter' ).each( function( ii, filter ) {
        $( filter ).prop( 'checked', false );
        a_w_f.update_query( $( filter ), $( container ), false );
      });
    });
  };
  
  a_w_f.reset_filter_value = function( $filter, $container ) {
    
    if( $container.hasClass( 'awf-product-search-container' ) ) {
      $filter.val( '' );
      a_w_f.product_search_onchange( $filter );
      
    } else if( $container.hasClass( 'awf-daterangepicker-container' ) ) {
      $filter.val( '' );
      a_w_f.daterangepicker_update_values( $container.find( '.awf-daterangepicker' ), $filter, $container );
      
    } else if( $container.hasClass( 'awf-range-slider-container' ) ) {
      var range_slider = $container[0];
      var min = range_slider.noUiSlider.options.range.min[0];
      var max = range_slider.noUiSlider.options.range.max[0];
      range_slider.noUiSlider.set( [min, max] );
      a_w_f.range_slider_update_values( range_slider, [min, max], min, max );
      
    } else if( $filter.hasClass( 'awf-taxonomy-range-value' ) ) {
      if( 'premium' in a_w_f ) { a_w_f.reset_taxonomy_range( $container ); }

    } else {
      a_w_f.filter_onclick( $filter );
    }
  };
  
  a_w_f.update_query = function( $filter, $container, build_badges ) {
    var taxonomy = $filter.attr( 'data-taxonomy' );
    var values = [];
    var i = -1;
    
    if( taxonomy in awf_data.query ) {
      if( $filter.is( ':checkbox' ) ) {
        values = awf_data.query[taxonomy].split( ',' );
        i = values.indexOf( $filter.val() );
      }
    }
    
    if( $container.hasClass( 'awf-active' ) ) {
      if( $filter.hasClass( 'awf-default' ) && ! ( $container.hasClass( 'awf-range-slider-container' ) && 1 === $container.find( '.awf-default' ).length ) ) {
        values = [];
        
      } else {
        if( i === -1 ) {
          values.push( $filter.val() );
        }
      }
      
    } else {
      if( values.length > 0 ) {
        if( i > -1 ) { values.splice( i, 1 ); }
      }
    }
    
    if( values.length > 0 ) {
      values.sort();
      awf_data.query[taxonomy] = values.join( ',' );
      
      if( $container.hasClass( 'awf-range-filter-container' ) ) {
        awf_data.query[$filter.attr( 'data-max-name' )] = $filter.attr( 'data-next-value' );
      }

    } else {
      if( ( 'archive_page' in awf_data ) && taxonomy === awf_data.archive_page && build_badges ) {
        a_w_f.filter_onclick( $filter );
        return;
      }

      delete awf_data.query[taxonomy];
      
      if( $container.hasClass( 'awf-range-filter-container' ) ) {
        delete awf_data.query[$filter.attr( 'data-max-name' )];
      }
    }
    
    if( 'sc_page' in awf_data ) { delete awf_data.query['product-page']; }
    
    if( build_badges ) {
      if( ( 'premium' in a_w_f ) && ( a_w_f.filter_reset_taxonomies.indexOf( taxonomy ) >= 0 ) ) { a_w_f.adjust_reset_active( taxonomy, $filter ); }
    }
  };
    
  a_w_f.build_active_badges = function() {
    $( '.awf-active-badge' ).remove();
    
    $.each( awf_data.query, function( key, values ) {

      var $active_filter_containers = $( '.awf-filter-wrapper[data-taxonomy="' + key + '"] .awf-filter-container.awf-active' );
      if( 0 === $active_filter_containers.length ) { return true; }
      
      var $filter;
      var $filter_container;
      
      if( $active_filter_containers.hasClass( 'awf-range-slider-container' ) ) {
        $filter_container = $active_filter_containers.filter( '.awf-range-slider-container' ).first();
        $filter = $filter_container.find( '.awf-filter' ).first();
        
        if( 0 === $( '.awf-active-badge[data-taxonomy="' + $filter.attr( 'data-taxonomy' ) + '"]' ).first().length ) {
          a_w_f.create_active_badge( $filter_container.find( '.awf-filter[name="' + key + '"]' ).first(), $filter_container );
        }

      } else if( $active_filter_containers.hasClass( 'awf-taxonomy-range-container' ) ) {
        $filter = $active_filter_containers.find( '.awf-filter.awf-taxonomy-range-value' ).first();
        
        if( 0 === $( '.awf-active-badge[data-taxonomy="' + $filter.attr( 'data-taxonomy' ) + '"]' ).first().length ) {
          a_w_f.create_active_badge( $filter, $filter.closest( '.awf-filter-container' ) );
        }

      } else {
        
        if( $active_filter_containers.hasClass( 'awf-range-filter-container' ) ) {
          $filter_container = $active_filter_containers.filter( '.awf-range-filter-container' ).first();
          $filter = $filter_container.find( '.awf-filter' ).first();
          
          if( 0 === $( '.awf-active-badge[data-taxonomy="' + $filter.attr( 'data-taxonomy' ) + '"]' ).length ) {
            a_w_f.create_active_badge( $filter, $filter_container );
          }

        } else if( $active_filter_containers.hasClass( 'awf-product-search-container' ) ) {
          $filter_container = $active_filter_containers.filter( '.awf-product-search-container' ).first();
          a_w_f.create_active_badge( $filter_container.find( '.awf-filter' ).first(), $filter_container );

        } else if( $active_filter_containers.hasClass( 'awf-daterangepicker-container' ) ) {
          $filter_container = $active_filter_containers.filter( '.awf-daterangepicker-container' ).first();
          a_w_f.create_active_badge( $filter_container.find( '.awf-filter' ).first(), $filter_container );

        } else {
          var slugs = values.split( ',' );

          if( ( 'archive_page' in awf_data ) && key === awf_data.archive_page && 1 === slugs.length ) {
            return;
          }
                
          $.each( slugs, function( i, slug ) {
            $filter = $active_filter_containers.find( '.awf-filter[value="' + slug + '"]' ).first();

            if( 0 < $filter.length ) {
              a_w_f.create_active_badge( $filter, $filter.closest( '.awf-filter-container' ) );
            }
          });
        }
      }
    });

    if( 0 < $( '.awf-active-badge' ).first().length ) {
      $( '.awf-reset-btn-container' ).show();
    } else {
      $( '.awf-reset-btn-container' ).hide();
    }
		
    if( 'premium' in a_w_f ) { a_w_f.adjust_dropdown_options(); }
    
  };

  a_w_f.create_active_badge = function( $filter, $container ) {
    
    if( $filter.hasClass( 'awf-default' ) && ! $filter.hasClass( 'awf-range-slider-value' ) ) { return; }

    var label;
    var classes = ['awf-active-badge'];
    var badge_taxonomy = $filter.attr( 'data-taxonomy');
    var preset_wrapper_id = '#' + $filter.closest( '.awf-preset-wrapper' ).attr( 'id' );
    
    if( $filter.hasClass( 'awf-range-slider-value' ) ) {

      if( 2 === $container.find( '.awf-default').length ) { return; }
      badge_taxonomy = $filter.attr( 'data-taxonomy' );
      
      var format = wNumb( {
        decimals: $container.attr( 'data-decimals' ),
        mark:     $container.attr( 'data-decimals-separator' ),
        thousand: $container.attr( 'data-thousand-separator' ),
        prefix:   $container.attr( 'data-prefix' ),
        suffix:   $container.attr( 'data-postfix' )
      } );
      
      label = $container.attr( 'data-label' ) + ' ' + format.to( parseFloat( $container.attr( 'data-min' ) ) ) + ' - ' + format.to( parseFloat( $container.attr( 'data-max' ) ) );
      
    } else if( $filter.hasClass( 'awf-taxonomy-range-value' ) ) {
      if( 'premium' in a_w_f ) {
        label = a_w_f.create_taxonomy_range_badge( $container );
      }
      
    } else if( $container.hasClass( 'awf-range-filter-container' ) ) {
      badge_taxonomy = $filter.attr( 'data-taxonomy' );
      label = $filter.siblings( 'label' ).attr( 'data-badge-label' );
      
    } else if( $container.hasClass( 'awf-product-search-container' ) ) {
      label = $filter.siblings( 'label' ).attr( 'data-badge-label' ) + ' ' + $filter.val();
      
    } else if( $container.hasClass( 'awf-daterangepicker-container' ) ) {
      label = $filter.attr( 'data-label' ) + ' ' + $filter.siblings( '.awf-daterangepicker' ).first().val();
      
    } else {
      label = $filter.siblings( 'label' ).attr( 'data-badge-label' );

      if( $container.hasClass( 'awf-block-deselection' ) && ( badge_taxonomy in awf_data.query ) && -1 === awf_data.query[badge_taxonomy].toString().indexOf( ',' ) ) {
        classes.push( 'awf-is-last-active' );
      }
    }
		
		if( typeof( label ) === 'undefined' ) { label = ''; }
    
    var $badge = $( '<div>', {
      class: classes.join( ' ' ),
			title: awf_data.i18n.badge_reset_label,
			'data-taxonomy': badge_taxonomy
    }).append( 
      $( '<i class="fas fa-times"></i>' ),
      '<span>' + label + '</span>'
    );
		
		$badge.on( 'click', function() {
      var $badge_filter = $filter;

			a_w_f.reset_filter_value( $badge_filter, $container );

			if( ( 'sbs' in a_w_f ) && ( 0 < $( '.awf-sbs .awf-filter-wrapper[data-taxonomy="' + badge_taxonomy + '"], .awf-sbs .awf-filter-wrapper[data-taxonomy-max="' + badge_taxonomy + '"]' ).first().length ) ) {

				$( '.awf-sbs' ).each( function( i, preset_wrapper ) {
					var $preset_wrapper = $( preset_wrapper );
					var $filter_wrapper = $preset_wrapper.find( '.awf-filter-wrapper[data-taxonomy="' + badge_taxonomy + '"], .awf-sbs .awf-filter-wrapper[data-taxonomy-max="' + badge_taxonomy + '"]' ).first();

					if( 0 < $filter_wrapper.length ) {
						var ii = parseInt( $filter_wrapper.attr( 'data-sbs-i' ) );
						var $reset_next = $preset_wrapper.find( '.awf-sbs-' + (ii + 1) );
						if ( 0 < $reset_next.length ) { a_w_f.reset_filter( $reset_next.attr( 'data-taxonomy' ) ); }

						if( 'redirect_ajax' in awf_data ) {
							a_w_f.update_sbs( $preset_wrapper, ii, false );
						} else {
							if( $preset_wrapper.hasClass( 'awf-sbs-next-btn-on' ) ) {
								if( ! preset_wrapper.hasAttribute( 'data-sbs-redirect' ) ) { $preset_wrapper.addClass( 'awf-sbs-redirect' ); }
								a_w_f.update_sbs( $preset_wrapper, ii, ( 0 < $reset_next.length ? false : true ) );

							} else {
								a_w_f.update_sbs( $preset_wrapper, ii, true );
							}
						}
					}
				});

			} else {

        if( a_w_f.force_reload && ( a_w_f.force_reload.indexOf( badge_taxonomy ) >= 0 ) ) { a_w_f.page_reload = true; }
        a_w_f.apply_filter( true, $( preset_wrapper_id ) );
			}
		} );
    
    $badge.clone( true ).appendTo( $( '.awf-filter-wrapper[data-taxonomy="' + badge_taxonomy + '"] .awf-filters-container' ).siblings( '.awf-active-badges-container' ) );
    
    if( ! $filter.hasClass( 'awf-no-active-badge' ) ) {
      $badge.clone( true ).appendTo( $( '.awf-preset-wrapper > .awf-active-badges-container' ) );
      $badge.clone( true ).appendTo( $( '.awf-active-badges-container.awf-extra-badges' ) );
      $badge.appendTo( $( '#awf-title-badges-storage' ) );
    }
  };

  a_w_f.apply_filter = function( $filter, $preset_wrapper ) {

    if( $preset_wrapper.hasClass( 'awf-url' ) ) {
      
      if( $filter instanceof jQuery ) {
        a_w_f.update_url();
      } else {
        window.location.href = a_w_f.build_url( $.extend( true, {}, awf_data.query ) );
      }
      
    } else if( $preset_wrapper.hasClass( 'awf-ajax' ) ) {

      if( 'redirect_ajax' in awf_data ) {
        a_w_f.load_new_history_state();

      } else {
        if( a_w_f.page_reload ) {
          a_w_f.load_new_history_state();

        } else {
          delete awf_data.pre_button_query;
          a_w_f.build_active_badges();
          a_w_f.update_url();
          a_w_f.ajax_filter( $filter );
        }
      }
      
    } else if( $preset_wrapper.hasClass( 'awf-sbs' ) ) {
      if( 'sbs' === $filter ) {
        
        if( ( 'redirect_ajax' in awf_data ) || $preset_wrapper[0].hasAttribute( 'data-sbs-redirect' ) ) {
          if( $preset_wrapper.hasClass( 'awf-sbs-redirect' ) ) {
            if( $preset_wrapper[0].hasAttribute( 'data-sbs-redirect' ) ) {
              var url = $preset_wrapper.attr( 'data-sbs-redirect' );
              var q = $.extend( true, {}, awf_data.query );
              
              delete q.post_type;
              
              if( ! ( 'permalinks_on' in awf_data ) ) { delete q.page_id; }
              
              if( ( 'archive_page' in awf_data ) ) {
                delete q[awf_data.archive_identifier];
                if( 0 === $preset_wrapper.find( '.awf-filter-wrapper[data-taxonomy="' + awf_data.archive_page + '"]' ).length ) {
                  delete q[awf_data.archive_page];
                }
              }
              
              url = url.split('?');
              
              if( 2 === url.length ) {
                var params = url[1].split('&');
                $.each( params, function( i, p ){
                  var param_data = p.split( '=' );
                  if( 2 === param_data.length ) { q[param_data[0]] = param_data[1]; }
                } );
              }
              
              if( ! $.isEmptyObject( q ) ) { url[0] += '?' + $.param( q ).replace( /%2C/g, ',' ); }
              a_w_f.load_new_history_state( url[0] ); // redirect to the requested page
              
            } else {
              a_w_f.load_new_history_state(); // to shop / archive / sc page
            }
          } else {
            if( $preset_wrapper[0].hasAttribute( 'data-sbs-redirect' ) ) {
              delete awf_data.pre_button_query;

              a_w_f.update_url();
              a_w_f.ajax_filter( true );
            } else {
              a_w_f.update_counts();
            }
          }

        } else {
          delete awf_data.pre_button_query;
          
          a_w_f.update_url();
          a_w_f.ajax_filter( true );
        }
        
        $preset_wrapper.removeClass( 'awf-sbs-redirect' );
      }
    }
  };
  
  a_w_f.ajax_filter = function( $filter ) {
    $( document ).trigger( 'awf_ajax_filter' );

    $( 'body' ).addClass( 'awf-loading-ajax' );
    
    var ajax_data = {
      action: 'awf', 
      awf_front: 1, 
      awf_action: 'filter',
      awf_query: awf_data.query,
      awf_ajax_extras: ( 'ajax_extras' in awf_data ) ? awf_data.ajax_extras : ''
    };
    
    if( 'archive_page' in awf_data ) { ajax_data.awf_archive_page = awf_data.archive_page; }
    
    if( 'ajax_pagination' in awf_data ) {

      if( 'pre_button_query' in awf_data ) { ajax_data.awf_query = awf_data.pre_button_query; }
      
      if( awf_data.ajax_pagination_loading ) {
        ajax_data.page_number = awf_data.ajax_pagination_number;
        
        if( 'page_numbers' === awf_data.ajax_pagination.type ) { awf_data.ajax_pagination_loading = false; }              
      }
      
      if( 'infinite_scroll' === awf_data.ajax_pagination.type ) {
        var ajax_pagination_loading_query = JSON.stringify( ajax_data.awf_query );
        
        if( awf_data.ajax_pagination_loading && awf_data.ajax_pagination_loading_query !== ajax_pagination_loading_query ) {
          delete ajax_data.page_number;
          awf_data.ajax_pagination_number = 0;
          awf_data.ajax_pagination_end_reached = false;
        }
        
        awf_data.ajax_pagination_loading_query = ajax_pagination_loading_query;
      }
    }
    
    if( 'sc_page' in awf_data ) {
      ajax_data.awf_sc_page = awf_data.sc_page;

      var sc_data;
      var $awf_sc = $( '.woocommerce.awf-sc' );

      if( 1 < $awf_sc.length ) {
        awf_data.ajax_mode = 'dedicated_ajax';
      }
      
      $awf_sc.each( function( i, sc_wrapper ) {
        sc_data = $.extend( true, {}, ajax_data );
        sc_data.awf_sc = {};
        var $sc_wrapper = $( sc_wrapper );
        
        $sc_wrapper.find( '.awf-sc-var' ).each( function( ii, input ) {
          sc_data.awf_sc[$( input ).attr( 'name' )] = $( input ).val();
        } );

        a_w_f.update_products( sc_data, $sc_wrapper );
      });
      
      if( ! ( 'page_number' in ajax_data ) ) {
        if( 1 === $awf_sc.length ) {
          a_w_f.update_counts( { 'sc_attrs': sc_data.awf_sc } );
        } else {
          a_w_f.update_counts();
        }
      }
      
    } else {
      a_w_f.update_products( $.extend( true, {}, ajax_data ), false );
      if( ! ( 'page_number' in ajax_data ) ) { a_w_f.update_counts(); }
    }
  };
  
  a_w_f.update_products = function( ajax_data, $sc_wrapper ) {
    
    var $wrapper = ( false === $sc_wrapper ) ? a_w_f.products_wrappers : $sc_wrapper;
    var $loader = $( '<div class="awf-loader"></div>' );
    
    if( 0 === $wrapper.length ) { return; }
    
    if( 'ajax_pagination' in awf_data ) {
      
      if( awf_data.ajax_pagination_loading ) {
        if( 'infinite_scroll' === awf_data.ajax_pagination.type || 'more_button' === awf_data.ajax_pagination.type ) {
          $( '<div class="awf-infinite-scroll-loader" style="position: relative; display: block; width: 100%; height: 100px;"></div>' ).block({ message: $loader }).insertAfter( $wrapper.find( awf_data.products_container ) );
        }
        
      } else { $wrapper.block({ message: $loader }); }
      
    } else {
      $wrapper.block({ message: $loader });
    }

    var url = window.location.href;

    if( 'dedicated_ajax' === awf_data.ajax_mode ) {
      url = awf_data.ajax_url;
    } else {
      delete ajax_data.action;
    }

    if( ! ( 'skip_loader_adjustment' in awf_data ) ) {
      if( 'fixed' !== $loader.css( 'position' ) ) {
        if( ! ( 'max_padding' in a_w_f ) ) {
          var wrapper_top = parseInt( $wrapper.offset().top );
          var loader_height = parseInt( window.getComputedStyle( $loader.get(0), ':before').height );

          a_w_f.max_padding = parseInt( wrapper_top + $wrapper.innerHeight() - loader_height*1.5 - 10 );
          a_w_f.base_padding = ( document.documentElement.clientHeight / 2 ) - ( loader_height / 2 ) - wrapper_top;
        }

        var padding_top = parseInt( document.documentElement.scrollTop + a_w_f.base_padding );
        if( padding_top > a_w_f.max_padding ) { padding_top = a_w_f.max_padding; }

        $loader.css( 'padding-top', padding_top );
      }
    }
    
    $.ajax({
      type:       'get',
      url:        url,
      dataType:   'html',
      data:       ajax_data,
      success:  function( response ) {
        if( response ) {
            
          if( 'ajax_pagination' in awf_data
             && 'infinite_scroll' === awf_data.ajax_pagination.type
             && JSON.stringify( ajax_data.awf_query ) !== awf_data.ajax_pagination_loading_query
            )
          {
            $( document ).trigger( 'awf_ajax_products_update_cancellation' );
            return;
          }
          
          var $response;

          try {
            $response = $( response );
            
          } catch( error ) {
            if( 'debug' in awf_data ) {
              $response = $( '' );
              console.log( 'Error retrieving filtered products: ' + error );
            } else {
              window.location.reload();
            }
          }

          if( ( 'ajax_pagination' in awf_data ) && awf_data.ajax_pagination_loading && 'page_number' in ajax_data ) {

            if( ( 'ajax_scroll' in awf_data ) && 'more_button' === awf_data.ajax_pagination.type ) {
              $( [document.documentElement, document.body] ).animate( { scrollTop: awf_data.ajax_pagination.last_product.offset().top + awf_data.ajax_pagination.last_product.height() - parseInt( awf_data.ajax_scroll, 10 )  }, 500, 'swing' );
            }
            
            $wrapper.find( awf_data.ajax_pagination.product_container ).last().after( $response.find( awf_data.ajax_pagination.product_container ) );
            a_w_f.setup_ajax_pagination( $wrapper );
            
            var $awf_result_count = $response.find( '.awf-ajax-pagination-result-count' ).first();
            if( 0 < $awf_result_count.length ) {
              $wrapper.find( awf_data.result_count_container ).text( $awf_result_count.text() );
            } else {
              $wrapper.find( awf_data.result_count_container ).text( '' );
            }

            $( '.awf-infinite-scroll-loader' ).remove();

          } else {
            
            if( 'yes' === awf_data.wrapper_reload ) {
              if( 'dedicated_ajax' === awf_data.ajax_mode ) {
                $wrapper.html( $response.html() );
              } else {
                $wrapper.html( a_w_f.extract_products_wrappers( $response ).html() );
                a_w_f.update_breadcrumbs( $response );
              }

            } else {

              var $pagination_containers = $wrapper.find( awf_data.pagination_container );
              var $new_pagination = $response.find( awf_data.pagination_container ).first();

              if( 0 === $new_pagination.length ) {
                $pagination_containers.html( '' );

              } else {
                if( 0 === $pagination_containers.length ) {
                  if( ! ( 'pagination_after' in awf_data ) ) {
                    a_w_f.setup_pagination_after( $response );
                  }

                  $wrapper.find( awf_data.pagination_after ).after( $new_pagination );

                } else {
                  $pagination_containers.replaceWith( $new_pagination );
                }
              }

              $( '.awf-pagination-more-btn-container' ).remove();

              var $result_count = $response.find( awf_data.result_count_container ).first();
              if( 0 === $result_count.length ) {
                $wrapper.find( awf_data.result_count_container ).html( '' );
              } else {
                $wrapper.find( awf_data.result_count_container ).replaceWith( $result_count );
              }
                  
              var $new_products = $response.find( awf_data.products_container ).first();
              var $no_result_container = $wrapper.find( awf_data.no_result_container ).first();

              $no_result_container.html( '' ).hide();

              if( 0 === $new_products.length ) {
                if( 0 === $no_result_container.length ) {
                  a_w_f.products_wrappers.find( awf_data.products_container ).before( $response.find( awf_data.no_result_container ).first() );
                } else {
                  $no_result_container.replaceWith( $response.find( awf_data.no_result_container ).first() );
                }

                $no_result_container.show();
                $wrapper.find( awf_data.products_container ).html( '' );

              } else {
                $wrapper.find( awf_data.products_container ).html( $new_products.html() );
              }

              $wrapper.unblock();
            }
            
            if( 'ajax_scroll' in awf_data ) {
              $( [document.documentElement, document.body] ).animate( { scrollTop: a_w_f.products_wrappers.offset().top - parseInt( awf_data.ajax_scroll, 10 ) }, 500, 'swing' );
            }
            
            if( 'ajax_pagination' in awf_data ) {
              if( 'infinite_scroll' === awf_data.ajax_pagination.type || 'more_button' === awf_data.ajax_pagination.type ) {
                awf_data.ajax_pagination_number = 1;
              }
        
              a_w_f.setup_ajax_pagination( $wrapper );
            }

            a_w_f.update_orderby( $wrapper );
          }
          
          if( false === $sc_wrapper ) {
            a_w_f.update_breadcrumbs( $response );

            var $document_title = $response.find( '.awf-document-title' ).first();
            if( 0 < $document_title.length ) { document.title = $("<div/>").html( $document_title.first().html() ).text(); }

            var $shop_title = $response.find( '.awf-wc-shop-title' ).first();

            if( 'archive_page' in awf_data ) {
              if( 'archive_components_support' in awf_data ) {
                if( 0 < $shop_title.length ) { $( 'h1.woocommerce-products-header__title' ).html( $shop_title.html() ); }

                var $archive_description = $response.find( '.term-description' ).first();
                if( 0 < $archive_description.length ) { $( '.term-description' ).html( $archive_description.html() ); }
              }

            } else {
              if( 0 < $shop_title.length ) { $( 'h1.woocommerce-products-header__title' ).html( $shop_title.html() ); }
            }

            var $meta_description = $response.find( '.awf-meta-description' ).first();
            if( 0 < $meta_description.length && 0 < document.querySelectorAll( 'meta[name="description"]' ).length ) {
              document.querySelector( 'meta[name="description"]' ).setAttribute( 'content', $meta_description.text() );
            }
          }
          
          a_w_f.build_products_wrappers();
          $( 'body' ).removeClass( 'awf-loading-ajax' );
          
          $( document ).trigger( 'awf_after_ajax_products_update', [ $response ] );
        }
      },
      error: function( response ) {
        if( 'debug' in awf_data ) {
          console.log( response );
        } else {
          window.location.reload();
        }
      }
    });
  };
  
  a_w_f.update_counts = function( data ) {

    if( ( 'undefined' === typeof data ) || ( 'string' === typeof data ) ) {
      data = {};
    }
    
    var callers = [];

    $( '.awf-preset-wrapper' ).each( function( i, el ) {
      callers.push( $( el ).attr( 'id' ) );
    });

    $.ajax({
      type:     "get",
      url:      awf_data.ajax_url,
      dataType: "json",
      data:     { 
        action: 'awf', 
        awf_action: 'update_filters',
        awf_front: 1, 
        awf_query: awf_data.query,
        awf_ajax_extras: ( 'ajax_extras' in awf_data ) ? awf_data.ajax_extras : '',
        awf_archive_page: ( 'archive_page' in awf_data ) ? awf_data.archive_page : '',
        awf_sc_attrs: ( 'sc_attrs' in data ) ? data.sc_attrs : {},
        awf_callers: callers,
      },
      success:  function( response ) {
        if( response ) {
          $.each( response.counts, function( taxonomy, slugs ) {
            $.each( slugs, function( slug, count ) {
              var $filters = $( 'input.awf-filter[data-taxonomy="' + taxonomy + '"][value="' + slug + '"]' );
              
              $filters.each( function( i, el ) {
                var $filter = $( el );
                var $container = $filter.closest( '.awf-filter-container' );
                $container.find( '.awf-filter-count' ).text(count);
                
                if( count > 0 ) {
                  $container.removeClass( 'awf-empty' );
                  if( $container.hasClass( 'awf-empty-disabled' ) ) { $filter.prop( 'disabled', false ); }
                } else {
                  $container.addClass( 'awf-empty' );
                  if( $container.hasClass( 'awf-empty-disabled' ) ) { $filter.prop( 'disabled', true ); }
                }
              });
              
            });
          });

          if( 'price_filter_min_max' in response ) {
            if( 'min_price' in response.price_filter_min_max && 'max_price' in response.price_filter_min_max ) {
              var min_price = parseFloat( response.price_filter_min_max.min_price );
              var max_price = parseFloat( response.price_filter_min_max.max_price );

              $( '.awf-style-range-slider.awf-filters-price-min .awf-range-slider-container' ).each( function( i, slider) {
                var $slider = $( slider );

                var slider_min = Math.floor( min_price );
                var slider_max = Math.ceil( max_price );
                var step = Math.ceil( parseFloat( $slider.attr( 'data-step' ) ) );
                
                if( (slider_max - slider_min) < step || (slider_min % step) !== 0 || (slider_max % step) !== 0 ) {
                  slider_min = slider_min - (slider_min % step);
                  slider_max = slider_max + (step - (slider_max % step));
                }

                $slider.attr( 'data-min-limit', slider_min ).attr( 'data-max-limit', slider_max );

                var $min_control = $slider.find( '.awf-range-slider-min' ).clone( true );
                var $max_control = $slider.find( '.awf-range-slider-max' ).clone( true );

                if( $min_control.hasClass( 'awf-default' ) && $max_control.hasClass( 'awf-default' ) ) {
                  $min_control.val( slider_min );
                  $max_control.val( slider_max );
                  $slider.attr( 'data-min', slider_min ).attr( 'data-max', slider_max );
                }

                if( 'undefined' !== typeof( slider.noUiSlider ) ) {
                  slider.noUiSlider.destroy();
                }

                $slider.prepend( $max_control ).prepend( $min_control );
                a_w_f.build_range_slider( slider, slider_min, slider_max );
              } );
            }

          }

          a_w_f.update_hrefs();
          
          $( document ).trigger( 'awf_after_counts_update' );
          
          $.each( a_w_f.pretty_scrollbars, function( i, ps ) { ps.update(); });
        }
      },
      error: function( response ) { console.log( response ); }
    });
  };
  
  a_w_f.update_orderby = function( $wrappers ) {

    var $containers = $( awf_data.orderby_container );

    if( false === $wrappers ) {
      $wrappers = a_w_f.products_wrappers;

    } else {
      if( 'yes' === awf_data.wrapper_reload ) {
        $wrappers.find( '.woocommerce-ordering select.orderby' ).on( 'change', function() { $( this ).closest( 'form' ).submit(); });

      } else {
        var $selects = $containers.find( 'select' );

        $containers.find( 'input:hidden' ).remove();

        if( 'orderby' in awf_data.query ) {
          if( $selects.first().val() !== awf_data.query['orderby'] ) {
            $selects.val( awf_data.query['orderby'] );
          }

        } else {
          if( 'menu_order' !== $selects.first().val() && $selects.first().find( 'option[value="menu_order"]' ).length > 0 ) {
            $selects.val( 'menu_order' );
          }
        }

        $.each( awf_data.query, function( k, v ) {
          if( 'orderby' !== k ) {
            $containers.append( '<input type="hidden" name="' + k + '" value="' + v  + '">' );
          }
        });
      }
    }
    
    if( 'permalinks_on' in awf_data ) {
      if( 'archive_page' in awf_data ) {
        $containers.find( 'input:hidden[name="' + awf_data.archive_page + '"]' ).remove();
      }

    } else {
      if( 'yes' === awf_data.wrapper_reload ) {
        if( ( 'post_type' in awf_data.query ) ) {
          $wrappers.find( 'form.woocommerce-ordering' ).append( $( '<input type="hidden" name="post_type" value="product" />' ) );
        } else if( 'sc_page' in awf_data ) {
          $wrappers.find( 'form.woocommerce-ordering' ).append( $( '<input type="hidden" name="page_id" value="' + awf_data.sc_page + '" />' ) );
        }
      }

      if( 'archive_page' in awf_data ) {
        $containers.find( 'input:hidden[name="' + awf_data.archive_page + '"],input:hidden[name="' + awf_data.archive_page_tax + '"]' ).remove();
        $containers.append( '<input type="hidden" name="' + awf_data.archive_page_tax + '" value="' + awf_data.query[awf_data.archive_page]  + '">' );
      }
    }
  };
  
  a_w_f.update_breadcrumbs = function( $ajax_response ) {
    
    if( 0 === a_w_f.woocommerce_breadcrumbs.length ) { return; }

    if( 'archive_page' in awf_data ) {
      var $replace_breadcrumb = $ajax_response.find( '#awf-breadcrumbs-support' );
      if( 0 < $replace_breadcrumb.length ) {
        a_w_f.woocommerce_breadcrumbs.contents().last()[0].textContent = $replace_breadcrumb.text();
      }
    }
  };
  
  a_w_f.reset_all_filters = function( excluded_taxonomies ) {
    excluded_taxonomies = $.merge( excluded_taxonomies, awf_data.reset_all_exceptions );
    
    $.each( awf_data.query, function( taxonomy ) {
      if( $.inArray( taxonomy, excluded_taxonomies) !== -1 ) { return; }
      a_w_f.reset_filter( taxonomy );
    });
  };
  
  a_w_f.reset_filter = function( taxonomy ) {
    var $wrappers = $( '.awf-filter-wrapper[data-taxonomy="' + taxonomy + '"] .awf-filters-container' );

    if( 0 >= $wrappers.length ) {
      delete awf_data.query[taxonomy];
      return;
      
    } else if( ! ( taxonomy in awf_data.query ) ) {
      if( $wrappers.hasClass( 'awf-style-range-slider' ) ) {
        $wrappers.filter( '.awf-style-range-slider' ).find( '.awf-filter' ).each( function( i, filter ) {
          if( $( filter ).attr( 'data-taxonomy' ) in awf_data.query ) {
            taxonomy = $( filter ).attr( 'data-taxonomy' );
            return true;
          }
        } );
      }

      if( ! ( taxonomy in awf_data.query ) )  { return; }
    }

    if( a_w_f.force_reload && ( a_w_f.force_reload.indexOf( taxonomy ) >= 0 ) ) { a_w_f.page_reload = true; }
    
    var values;

    if( $wrappers.hasClass( 'awf-product-search' ) || $wrappers.hasClass( 'awf-style-daterangepicker' ) ) {
      values = [awf_data.query[taxonomy]];
    } else {
      values = awf_data.query[taxonomy].toString().split( ',' );
    }

    $.each( values, function( i, v ) {

      if( $wrappers.hasClass( 'awf-style-range-slider' ) ) {
        var $wrapper = $wrappers.filter( '.awf-style-range-slider' ).first();
        if( taxonomy in awf_data.query ) {
          a_w_f.reset_filter_value( $wrapper.find( '.awf-filter' ).first(), $wrapper.find( '.awf-filter-container' ).first() );
        }

      } else if( $( $wrappers[0] ).hasClass( 'awf-product-search' ) || $( $wrappers[0] ).hasClass( 'awf-style-daterangepicker' ) ) {
        a_w_f.reset_filter_value( $( $wrappers[0] ).find( '.awf-filter' ).first(), $( $wrappers[0] ).find( '.awf-filter-container' ).first() );

      } else {

        var $filter = $wrappers.find( '.awf-filter[value="' + v + '"]' ).first();

        if( 0 === $filter.length ) { return true; }

        if( $filter[0].hasAttribute( 'data-max-name' ) && ( $filter.attr( 'data-max-name' ) in awf_data.query ) ) {
          var $range_filter = $wrappers.find( '.awf-filter[value="' + v + '"][data-next-value="' + awf_data.query[$filter.attr( 'data-max-name' )] + '"]' ).first();
          if( 0 !== $range_filter.length ) {
            a_w_f.reset_filter_value( $range_filter, $range_filter.closest( '.awf-filter-container' ) );
          }

        } else {
          a_w_f.reset_filter_value( $filter, $filter.closest( '.awf-filter-container' ) );
        }
      }

    });
  };
      
  a_w_f.apply_filters_reset = function( $preset_wrapper ) {
    if( 0 < $preset_wrapper.length ) {

      if( ! $preset_wrapper.hasClass( 'awf-ajax' ) && ! $preset_wrapper.hasClass( 'awf-url' ) && ! $preset_wrapper.hasClass( 'awf-sbs' ) ) {
        a_w_f.update_url();
        $preset_wrapper.find( '.awf-form-submit-btn' ).first().trigger( 'click' );

      } else {
        if( 'sbs' in a_w_f ) {
          $( '.awf-sbs' ).each( function( i, wrapper ) {
            if( 'redirect_ajax' in awf_data ) {
              a_w_f.update_sbs( $( wrapper ), 0, false );

            } else {
              if( ( $( wrapper ).hasClass( 'awf-sbs-next-btn-on' ) || $( wrapper ).hasClass( 'awf-button' ) ) && ! wrapper.hasAttribute( 'data-sbs-redirect' ) ) {
                $( wrapper ).addClass( 'awf-sbs-redirect' );
              }

              a_w_f.update_sbs( $( wrapper ), 0, true );
            }
          } );

        } else {
          a_w_f.apply_filter( true, $preset_wrapper );
        }
      }

    } else {
      a_w_f.load_new_history_state();
    }
  };
  
  a_w_f.build_url = function( query, archive_url ) {

    var url = awf_data.filters_url;
    
    if( ( 'archive_page' in awf_data ) ) {
      query[awf_data.archive_identifier] = 1;

      if( 'permalinks_on' in awf_data ) {
        if( 'undefined' === typeof archive_url ) {
          if( awf_data.archive_page in query ) {
            url = url.replace( awf_data.archive_page_switch, '/' + query[awf_data.archive_page] + awf_data.archive_page_trailingslash );
          }

        } else {
          url = archive_url;
        }
        
      } else {
        query[awf_data.archive_page_tax] = query[awf_data.archive_page];
      }

      delete query[awf_data.archive_page];
    }
    
    if( 'ajax_pagination_url' in awf_data ) {
      if( 'sc_page' in awf_data ) {
        if( 1 < awf_data.ajax_pagination_number ) { query['product-page'] = awf_data.ajax_pagination_number; }
        
      } else {
        if( 'permalinks_on' in awf_data ) {
          var url_parts = awf_data.ajax_pagination_url.split('?');
          if( 1 < url_parts.length ) { url = url_parts[0]; } else { url = awf_data.ajax_pagination_url; }

        } else {
          if( 1 < awf_data.ajax_pagination_number ) { query.paged = awf_data.ajax_pagination_number; }
        }
      }
      
      delete awf_data.ajax_pagination_url;
      
    }
    
    if( ! $.isEmptyObject( query ) ) { url += '?' + $.param( query ).replace( /%2C/g, ',' ); }

    return url;
  };
  
  a_w_f.update_hrefs = function() {
    $( '.awf-url .awf-filter-wrapper' ).each( function( i, wrapper ) {
      
      if( $( wrapper ).hasClass( 'awf-reset-all' ) ) {
        if( 'premium' in a_w_f ) { a_w_f.update_reset_all_hrefs( $( wrapper ) ); }
        
      } else if( $( wrapper ).hasClass( 'awf-single' ) || $( wrapper ).hasClass( 'awf-range' ) ) {
        $( wrapper ).find( '.awf-filter' ).each( function( ii, filter ) {
          
          var $filter = $( filter );
          var query = $.extend( true, {}, awf_data.query );
          var taxonomy = $filter.attr( 'data-taxonomy' );
          
          if( ! ( taxonomy in query ) ) {
            query[taxonomy] = '';
          }
          
          if( $filter.hasClass( 'awf-default' ) || $filter.val() === query[taxonomy] ) {
            if( ! ( ( 'archive_page' in awf_data ) && taxonomy === awf_data.archive_page ) ) {
              delete query[taxonomy];

              if( $filter.is( '[data-max-name]' ) ) { delete query[$filter.attr( 'data-max-name' )]; }
            }
            
          } else {
            query[taxonomy] = $filter.val();
            if( $filter.is( '[data-max-name]' ) ) { query[$filter.attr( 'data-max-name' )] = $filter.attr( 'data-next-value' ); }
          }
          
          if( $filter.is( '[data-archive-permalink]' ) ) {
            $filter.closest( 'a' ).attr( 'href', a_w_f.build_url( query, $filter.attr( 'data-archive-permalink' ) ) );
          } else {
            $filter.closest( 'a' ).attr( 'href', a_w_f.build_url( query ) );
          }
              
        });
        
      } else if( $( wrapper ).hasClass( 'awf-multi' ) ) {
        
        $( wrapper ).find( '.awf-filter' ).each( function( ii, filter ) {
          
          var $filter = $( filter );
          var query = $.extend( true, {}, awf_data.query );
          var taxonomy = $filter.attr( 'data-taxonomy' );
          var values;
          if( taxonomy in query ) { values = query[taxonomy].split( ',' ); } else { values = []; }
          
          if( $filter.is( ':checked' ) ) {
            values = $.grep( values, function( v ) { return v !== $filter.val(); } );

          } else {
            $filter.parents( 'ul' ).prev( 'li.awf-filter-container' ).find( '.awf-filter' ).each( function( i, f ) {
              values = $.grep( values, function( v ) { return v !== $( f ).val(); } );
            });

            $filter.parents( 'li.awf-filter-container' ).next( 'ul' ).find( '.awf-filter' ).each( function( i, f ) {
              values = $.grep( values, function( v ) { return v !== $( f ).val(); } );
            });
            
            values.push( $filter.val() );
          }
          
          if( values.length > 0 ) {
            values.sort();
            query[taxonomy] = values.join( ',' );

          } else {
            if( ( 'archive_page' in awf_data ) && taxonomy === awf_data.archive_page ) {
              query[taxonomy] = $filter.val();
            } else {
              delete query[taxonomy];
            }
          }
          
          $filter.closest( 'a' ).attr( 'href', a_w_f.build_url( query ) );
        });
      }
    });
  };
  
  a_w_f.range_slider_onchange = function( range_container, values, min, max ) {
    var $range_container = $( range_container );

    if( ( $range_container.hasClass( 'awf-button-filter' ) || $range_container.hasClass( 'awf-range-btn' ) ) && ! ( 'pre_button_query' in awf_data ) ) {
      awf_data.pre_button_query = $.extend( true, {}, awf_data.query );
    }
    
    if( $range_container.hasClass( 'awf-reset-all' ) && typeof a_w_f.range_slider_reset_all === 'function' ) {
      a_w_f.range_slider_reset_all( $range_container );
    }

    a_w_f.range_slider_update_values( range_container, values, min, max );

    var $preset_wrapper = $range_container.closest( '.awf-preset-wrapper' );

    if( $preset_wrapper.hasClass( 'awf-sbs' ) ) {

      if( '1' === $range_container.attr( 'data-force-sbs-redirect' ) ) {
        $preset_wrapper.addClass( 'awf-sbs-redirect' );
      }

      a_w_f.update_sbs( $preset_wrapper, $range_container.closest( '.awf-filter-wrapper' ).first().attr( 'data-sbs-i' ), ( '1' === $range_container.attr( 'data-apply-sbs' ) ) );
    }

    if( ! ( $range_container.hasClass( 'awf-range-btn' ) || $preset_wrapper.hasClass( 'awf-button' ) ) ) {
      a_w_f.apply_filter( true, $preset_wrapper );
    }
  };
    
  a_w_f.range_slider_update_values = function( range_container, values, min, max ) {
    var $range_container = $( range_container );
    var taxonomy = $range_container.find( '.awf-filter.awf-range-slider-min').first().attr( 'data-taxonomy' );

    values[0] = parseFloat( values[0] );
    values[1] = parseFloat( values[1] );
    
    $( '.awf-filter-wrapper[data-taxonomy="' + taxonomy + '"] .awf-range-slider-container' ).each( function( i, filter_container ) {
      
      var $min_filter = $( filter_container ).find( '.awf-range-slider-min' );
      var $max_filter = $( filter_container ).find( '.awf-range-slider-max' );
      
      $min_filter.val( values[0] );
      $max_filter.val( values[1] );

      $( filter_container ).attr( 'data-min', values[0] ).attr( 'data-max', values[1] );

      if( values[0] === parseFloat( min ) ) { $min_filter.addClass( 'awf-default' ); } else { $min_filter.removeClass( 'awf-default' ); }
      if( values[1] === parseFloat( max ) ) { $max_filter.addClass( 'awf-default' ); } else { $max_filter.removeClass( 'awf-default' ); }
      
      if( filter_container === range_container ) {
        
        $( filter_container ).addClass( 'awf-active' );
        
        a_w_f.update_query( $min_filter, $range_container, false );
        a_w_f.update_query( $max_filter, $( range_container ), true );

        $( '.awf-filter-wrapper[data-taxonomy="' + taxonomy + '"] .awf-range-filter-container.awf-active' ).removeClass( 'awf-active' ).find( 'input.awf-filter' ).prop( 'checked', false );
        $( '.awf-filter-wrapper[data-taxonomy="' + taxonomy + '"] .awf-range-filter-container input[value="' + values[0] + '"][data-next-value="' + values[1] + '"]' ).prop( 'checked', true ).closest( '.awf-filter-container' ).addClass( 'awf-active' );
        
      } else {
        filter_container.noUiSlider.set( [values[0], values[1]] );
        $( filter_container ).removeClass( 'awf-active' );
      }
    });
  };
  
  a_w_f.daterangepicker_update_values = function( $daterangepicker, $filter, $filter_container ) {

    if( $filter.hasClass( 'awf-button-filter' ) && ! ( 'pre_button_query' in awf_data ) ) {
      awf_data.pre_button_query = $.extend( true, {}, awf_data.query );
    }
    
    $( '.awf-style-daterangepicker .awf-filter[data-taxonomy="' + $filter.attr( 'data-taxonomy' ) + '"]' ).each( function( i, el ) {
      
      var $el = $( el );
      var is_caller = ( $el.attr( 'id' ) === $filter.attr( 'id' ) );
      var $el_daterangepicker = $el.siblings( '.awf-daterangepicker' );
      var $el_filter_container = $el.closest( '.awf-filter-container' );
      
      if( 0 === $filter.val().length ) {
        
        $el_daterangepicker.data( 'daterangepicker' ).setStartDate( moment().utc().format( $el_daterangepicker.data( 'daterangepicker' ).locale.format ) );
        
        if( $el_daterangepicker.data( 'daterangepicker' ).singleDatePicker ) {
          $el_daterangepicker.data( 'daterangepicker' ).setEndDate( moment().utc().format( $el_daterangepicker.data( 'daterangepicker' ).locale.format ) );
        } else {
          $el_daterangepicker.data( 'daterangepicker' ).setEndDate( moment().utc().add( 2, 'd' ).format( $el_daterangepicker.data( 'daterangepicker' ).locale.format ) );
        }
        
        $el.val( '' );
        $el_daterangepicker.val( '' );
        $el_filter_container.removeClass( 'awf-active' );
        
      } else {
        if( ! is_caller ) {
          $el.val( $filter.val() );
          $el_daterangepicker.val( $daterangepicker.val() );
          
          $el_daterangepicker.data( 'daterangepicker' ).setStartDate( $daterangepicker.data( 'daterangepicker' ).startDate.format( $el_daterangepicker.data( 'daterangepicker' ).locale.format ) );
          $el_daterangepicker.data( 'daterangepicker' ).setEndDate( $daterangepicker.data( 'daterangepicker' ).endDate.format( $el_daterangepicker.data( 'daterangepicker' ).locale.format ) );
        }
        
        $el_filter_container.addClass( 'awf-active' );
      }
    });
    
    a_w_f.update_query( $filter, $filter_container, true );
  };
  
  a_w_f.update_sbs = function( $preset_wrapper, i, apply_filters ) {
    if( $preset_wrapper.hasClass( 'awf-sbs-redirect' )
        && ( ( 'redirect_ajax' in awf_data ) || $preset_wrapper[0].hasAttribute( 'data-sbs-redirect' ) )
      ) {
      a_w_f.apply_filter( 'sbs', $preset_wrapper );
      return;
    }
    
    var current_i = i = parseInt( i );
    var next_i = i + 1;
    var total = parseInt( $preset_wrapper.attr( 'data-sbs-total' ) );
    var gap = false;
    var filter_wrappers = {};
    
    for( var fi = i; fi <= total; fi++ ) {
      if( fi === 0 ) { continue; }
      
      var $filter_wrapper = $( $preset_wrapper ).find( '.awf-sbs-' + fi );
      if( 0 === $filter_wrapper.length ) { continue; }
      
      var has_active_filters = a_w_f.filter_wrapper_has_active( $filter_wrapper );
      
      if( 0 === i ) {
        if( has_active_filters ) {
          if( gap ) {
            a_w_f.reset_filter( $filter_wrapper.attr( 'data-taxonomy' ) );
          } else {
            current_i++; next_i++;
          }

        } else {
          gap = true;
        }

      } else {
        if( has_active_filters ) {
          if( gap ) {
            a_w_f.reset_filter( $filter_wrapper.attr( 'data-taxonomy' ) );
          } else {
            if( fi === next_i && ( $preset_wrapper.hasClass( 'awf-button' ) || fi < total ) ) {
              current_i = fi; next_i = current_i + 1;
            }
          }
          
        } else {
          if( current_i >= 1 && fi === current_i ) { next_i = current_i; current_i--; }
          gap = true;
        }
      }
      
      if( $preset_wrapper.hasClass( 'awf-sbs-unhide' ) && fi < next_i ) {
        $filter_wrapper.removeClass( 'awf-hidden' );
      } else {
        $filter_wrapper.addClass( 'awf-hidden' );
      }
      
      filter_wrappers[fi] = $filter_wrapper;
    }
    
    if( next_i < 2 ) { $preset_wrapper.addClass( 'awf-sbs-first' ); } else { $preset_wrapper.removeClass( 'awf-sbs-first' ); }
    $preset_wrapper.removeClass( 'awf-sbs-next-btn-hidden' ).removeClass( 'awf-sbs-last' );
    
    var show = next_i;
    var update_counts = true;
    
    if( $preset_wrapper.hasClass( 'awf-sbs-next-btn-on' ) ) {
      
      if( current_i < 1 || next_i > total || ( next_i === total && apply_filters ) ) {
        $preset_wrapper.addClass( 'awf-sbs-next-btn-hidden' );
      }
      
      if( current_i === i ) { update_counts = false; }
      if( next_i === 2 && i > 0 && i !== next_i ) { $preset_wrapper.addClass( 'awf-sbs-first' ); }
      
      if( i > 0 && i === current_i ) { show = current_i; }
    }
    
    if( show > total ) { show = total; } else {
      if( ! ( show in filter_wrappers ) ) {
        var $fw = $( $preset_wrapper ).find( '.awf-sbs-' + show );
        if( 0 < $fw.length ) { filter_wrappers[show] = $fw; }
      }
    }
    
    if( show in filter_wrappers ) { 
      var $loading = filter_wrappers[show].has( '.awf-filters-container:not(.awf-style-range-slider):not(.awf-product-search):not(.awf-style-daterangepicker)' );
      
      if( 0 < $loading.length ) {
        filter_wrappers[show].addClass( 'awf-sbs-loading' );
        
        $( document ).one( 'awf_after_counts_update', function() {
          filter_wrappers[show].removeClass( 'awf-sbs-loading' );
        }); 
      }
      
      filter_wrappers[show].removeClass( 'awf-hidden' );
    }
    
    if( $preset_wrapper.hasClass( 'awf-sbs-submit-last' ) && next_i > total ) {
      
      if( ! gap ) {
        
        $preset_wrapper.addClass( 'awf-sbs-last' );
        
        if( apply_filters ) {
          if( $preset_wrapper.hasClass( 'awf-sbs-next-btn-on' ) ) {
            apply_filters = false;
          } else {
            if( ! $preset_wrapper.hasClass( 'awf-button' ) ) { $preset_wrapper.addClass( 'awf-sbs-redirect' ); }
          }

        } else {
          
          if( ! $preset_wrapper.hasClass( 'awf-button' ) ) {
            if( current_i === total && i !== 0 ) { $preset_wrapper.addClass( 'awf-sbs-redirect' ); }
          }
          
        }
      }

    } else {
      
      if( apply_filters ) {
        if( $preset_wrapper.hasClass( 'awf-sbs-submit-last' ) ) {
          apply_filters = false;
          if( $preset_wrapper.hasClass( 'awf-sbs-next-btn-on' ) ) {
            update_counts = true;
          }

        } else {
          if( $preset_wrapper.hasClass( 'awf-button' ) ) { apply_filters = false; } else { $preset_wrapper.addClass( 'awf-sbs-redirect' ); }
        }
      }
    }
    
    if( next_i > total && ( i === (next_i - 1) ) ) {
      update_counts = false;
      
      if( $preset_wrapper.hasClass( 'awf-sbs-next-btn-on' ) && ! $preset_wrapper.hasClass( 'awf-button' ) ) {
        $preset_wrapper.addClass( 'awf-sbs-redirect' );
      }
    }
    
    if( apply_filters || $preset_wrapper.hasClass( 'awf-sbs-redirect' ) ) {
      a_w_f.apply_filter( 'sbs', $preset_wrapper );
      
    } else {
      if( update_counts ) {
        a_w_f.update_counts();
        
      } else {
        if( show in filter_wrappers ) {
          filter_wrappers[show].removeClass( 'awf-sbs-loading' );
          
          var $has_ps = filter_wrappers[show].find( '.awf-pretty-scrollbars' );

          if( 0 < $has_ps.length ) {
            $.each( a_w_f.pretty_scrollbars, function( i, ps ) { if( ps.element === $has_ps[0] ) { ps.update(); return false; } });
          }
        }
      }
    }
  };
      
  a_w_f.filter_wrapper_has_active = function( $filter_wrapper ) {
    if( $filter_wrapper.attr( 'data-taxonomy' ) in awf_data.query ) {
      return true;
    } else {
      if( $filter_wrapper[0].hasAttribute( 'data-taxonomy-max' ) && ( $filter_wrapper.attr( 'data-taxonomy-max' ) in awf_data.query ) ) {
        return true;
      }
    }

    return false;
  };
  
  a_w_f.update_url = function() {
    var url = a_w_f.build_url( $.extend( true, {}, awf_data.query ) );
    window.history.pushState( { awf_ajax_call: true }, '', url );
  };
  
  a_w_f.build_products_wrappers = function() {
    if( 'products_wrapper' in awf_data ) {
      a_w_f.products_wrappers = $( awf_data.products_wrapper );

    } else if( 'products_container' in awf_data ) {
      a_w_f.products_wrappers = $( awf_data.products_container ).parent();
      
      if( 0 === a_w_f.products_wrappers.length ) {
        a_w_f.products_wrappers = $( awf_data.no_result_container ).first().parent();
      }
			
    } else {
			a_w_f.products_wrappers = $([]);
		}
  };
  
  a_w_f.extract_products_wrappers = function( $html ) {
    var $wrappers;

    if( 'products_wrapper' in awf_data ) {
      $wrappers = $html.find( awf_data.products_wrapper );

    } else if( 'products_container' in awf_data ) {
      $wrappers = $html.find( awf_data.products_container ).parent();
      
      if( 0 === $wrappers.length ) {
        $wrappers = $html.find( awf_data.no_result_container ).first().parent();
      }
    }

    if( 'undefined' === typeof( $wrappers ) ) {
      $wrappers = $([]);
    }

    return $wrappers;
  };
  
  a_w_f.setup_pagination_after = function( $container ) {
    var classes_array = [];

    $container.find( awf_data.pagination_container ).each( function() {
      var classes = $( this ).prev().attr( 'class' );
      
      if( 'undefined' === typeof( classes ) ) {
        classes = '';
      } else {
        classes = classes.split( /\s+/ ).join( '.' );
      }

      if( 0 < classes.length ) {
        classes_array.push( '.' + classes );
      }
    } );

    if( 0 === classes_array.length ) {
      awf_data.pagination_after = awf_data.products_container;
    } else {
      awf_data.pagination_after = classes_array.join( ',' );
    }
  }
  
  a_w_f.setup_ajax_pagination = function( $wrappers ) {

    var $pagination_containers = $wrappers.find( awf_data.pagination_container );
    
    awf_data.ajax_pagination_loading = false;
    
    if( 'page_numbers' === awf_data.ajax_pagination.type ) {
      if( 0 === $pagination_containers.length ) { return; }
      
      var $page_numbers = $pagination_containers.find( awf_data.ajax_pagination.page_number );

      $page_numbers
        .off( 'click' )
        .on( 'click', function( event ) {
          event.preventDefault();
        
          var $page_number = $( this );
          var number = '';

          if( $page_number.hasClass( 'next' ) ) {
            $page_number = $pagination_containers.find( '.current' ).first().parent().next().find( awf_data.ajax_pagination.page_number );
          } else if( $page_number.hasClass( 'prev' ) ) {
            $page_number = $pagination_containers.find( '.current' ).first().parent().prev().find( awf_data.ajax_pagination.page_number );
          }

          number = $page_number.text().replace( /[^0-9]/gi, '' );
          if( 0 === number.length ) { return; }

          awf_data.ajax_pagination_number = parseInt( number, 10 );
          awf_data.ajax_pagination_url = $page_number.attr( 'href' );
          awf_data.ajax_pagination_loading = true;

          a_w_f.update_url();
          a_w_f.ajax_filter( true );
      });

    } else if( 'infinite_scroll' === awf_data.ajax_pagination.type ) {
      if( ( 'sc_page' in awf_data ) && 1 < a_w_f.products_wrappers.length ) { return; }
      
      $pagination_containers.hide();
      
      a_w_f.setup_ajax_pagination_next( $pagination_containers.first() );
      
      awf_data.ajax_pagination.last_product = $wrappers.find( awf_data.ajax_pagination.product_container ).last();
      if( 0 === awf_data.ajax_pagination.last_product.length ) { return; }

      if( ! ( 'initiated' in awf_data.ajax_pagination ) && ! awf_data.ajax_pagination_end_reached ) {
        var $window = $( window );
        awf_data.ajax_pagination_loading_query = JSON.stringify( awf_data.query );
        
        $( document ).on( 'awf_after_ajax_products_update awf_ajax_products_update_cancellation', function() {
          var $w = $( window );
          if( $w.height() >= $( document ).height() ) { $w.trigger( 'scroll' ); }
        } );

        $window.on( 'scroll touchstart', function() {
            var $w = $( this );

            if( 0 === awf_data.ajax_pagination.last_product.length ) { return; }

            if ( ! awf_data.ajax_pagination_end_reached
                && ! awf_data.ajax_pagination_loading
                && ( $w.scrollTop() + $w.height() ) >= ( awf_data.ajax_pagination.last_product.offset().top - ( 2 * awf_data.ajax_pagination.last_product.height() ) )
            ) {
              awf_data.ajax_pagination_loading = true;
              a_w_f.ajax_filter( true );
            }
        });
        
        if( $window.height() >= $( document ).height() ) { $window.trigger( 'scroll' ); }
        
        awf_data.ajax_pagination.initiated = true;
      }
      
    } else if( 'more_button' === awf_data.ajax_pagination.type ) {
      
      if( 0 === $pagination_containers.length ) { return; }
      
      if( ( 'sc_page' in awf_data ) && 1 < a_w_f.products_wrappers.length ) { return; }
      
      awf_data.ajax_pagination.last_product = $wrappers.find( awf_data.ajax_pagination.product_container ).last();
      if( 0 === awf_data.ajax_pagination.last_product.length ) { return; }
      
      $pagination_containers.hide();

      a_w_f.setup_ajax_pagination_next( $pagination_containers.first() );
      
      if ( ! awf_data.ajax_pagination_end_reached ) {
        $( '<div class="awf-pagination-more-btn-container"><button type="button" title="' + awf_data.i18n.ajax_pagination_more_button + '" class="awf-pagination-more-btn">' + awf_data.i18n.ajax_pagination_more_button + '</button></div>' )
          .on( 'click', function() {
            var $button = $( this );
          
            awf_data.ajax_pagination_loading = true;
            a_w_f.ajax_filter( true );
          
            $button.remove();
          })
          .insertAfter( $wrappers.find( awf_data.products_container ) );
      }
    }
  };
      
  a_w_f.setup_ajax_pagination_next = function( $container ) {
    if( ! ( 'ajax_pagination_number' in awf_data ) ) { awf_data.ajax_pagination_number = 1; }
    
    if( 1 === awf_data.ajax_pagination_number ) {
      awf_data.ajax_pagination_last = 0;
      
      var $last_container = $container.find( awf_data.ajax_pagination.page_number + ':not(.next)' ).last();
      if( 0 < $last_container.length ) {
        var text = $last_container.text().replace( /[^0-9]/gi, '' );
        if( 0 < text.length ) { awf_data.ajax_pagination_last = parseInt( text, 10 ); }
      }
    }
    
    var next_number = parseInt( ( awf_data.ajax_pagination_number + 1 ), 10 );
    
    if( next_number > awf_data.ajax_pagination_last ) {
      awf_data.ajax_pagination_end_reached = true;
      
    } else {
      awf_data.ajax_pagination_number = next_number;
      awf_data.ajax_pagination_end_reached = false;
    }
  };
  
  a_w_f.setup_togglable_preset = function() {

    a_w_f.togglable_preset = $( '.awf-togglable-preset' );

    if( 0 < a_w_f.togglable_preset.length ) {
      if( 1 < a_w_f.togglable_preset.length ) {
        a_w_f.togglable_preset.each( function( i, el ) {
          if( 0 < i ) { $( el ).remove(); }
        });
        
        a_w_f.togglable_preset = $( '.awf-togglable-preset' );
      }

    } else {
      a_w_f.togglable_preset = $( '.awf-togglable-on-s-preset' );

      if( 1 < a_w_f.togglable_preset.length ) {
        a_w_f.togglable_preset.each( function( i, el ) {
          if( 0 < i ) { $( el ).removeClass( 'awf-togglable-on-s-preset' ); }
        });

        a_w_f.togglable_preset = $( '.awf-togglable-on-s-preset' );
      }
    }

    if( 0 < a_w_f.togglable_preset.length ) {
      
      a_w_f.togglable_preset.css( 'opacity', '1' );
      a_w_f.insert_togglable_preset_btn();
      
      $( document ).on( 'awf_ajax_filter', function() {
        if( a_w_f.togglable_preset.hasClass( 'awf-above-products-mode' ) ) {
          if( a_w_f.togglable_preset.hasClass( 'awf-togglable-preset' )
             || ( a_w_f.togglable_preset.hasClass( 'awf-togglable-on-s-preset' ) && $( 'body' ).hasClass( 'awf-togglable-preset-mode-on' ) )
          ) {
            a_w_f.move_togglable_preset_to_placeholder();
          }
        }
        
        if( awf_data.togglable_preset.close_preset_on_ajax_update ) { a_w_f.close_togglable_preset(); }
      } );
      
      $( document ).on( 'awf_after_ajax_products_update', function() {
        if( 0 === $( '.awf-togglable-preset-btn' ).length ) { a_w_f.insert_togglable_preset_btn(); }
        
        if( a_w_f.togglable_preset.hasClass( 'awf-above-products-mode' ) ) {
          if( a_w_f.togglable_preset.hasClass( 'awf-togglable-preset' )
             || ( a_w_f.togglable_preset.hasClass( 'awf-togglable-on-s-preset' ) && $( 'body' ).hasClass( 'awf-togglable-preset-mode-on' ) )
          ) {
            a_w_f.move_togglable_preset_above_products();
          }
          
        }
      } );

      if( a_w_f.togglable_preset.hasClass( 'awf-togglable-on-s-preset' ) ) {
        
        if( a_w_f.togglable_preset.attr( 'data-responsive-width' ) >= window.innerWidth ) {
          
          if( a_w_f.togglable_preset.hasClass( 'awf-left-popup-sidebar-mode' ) ) {
            a_w_f.togglable_preset.hide();
            setTimeout( function() {
              a_w_f.togglable_preset.show();
            }, 1000);
          }

          $( 'body' ).addClass( 'awf-togglable-preset-mode-on' );
          
          if( a_w_f.togglable_preset.hasClass( 'awf-above-products-mode' ) ) {
            a_w_f.move_togglable_preset_above_products();
          }
        }

        $( window ).resize( function() {
          if( a_w_f.togglable_preset.attr( 'data-responsive-width' ) >= window.innerWidth ) {
            if( ! $( 'body' ).hasClass( 'awf-togglable-preset-mode-on' ) ) {
              $( 'body' ).addClass( 'awf-togglable-preset-mode-on' );
              if( a_w_f.togglable_preset.hasClass( 'awf-above-products-mode' ) ) {
                a_w_f.move_togglable_preset_above_products();
              }
            }
            
          } else {
            if( $( 'body' ).hasClass( 'awf-togglable-preset-mode-on' ) ) {
              $( 'body' ).removeClass( 'awf-togglable-preset-mode-on' );
              if( a_w_f.togglable_preset.hasClass( 'awf-above-products-mode' ) ) {
                a_w_f.move_togglable_preset_to_placeholder();
              }
            }
          }
        });
        
      } else {
        if( a_w_f.togglable_preset.hasClass( 'awf-above-products-mode' ) ) {
          a_w_f.move_togglable_preset_above_products();
        }
      }
      
      if( a_w_f.togglable_preset.hasClass( 'awf-fix-popup-close-btn' ) && ! a_w_f.togglable_preset.hasClass( 'awf-above-products-mode' ) && 0 === $( '#awf-fixed-popup-close-btn' ).length ) {
        a_w_f.togglable_preset.find( '.awf-togglable-preset-close-btn' ).first().appendTo( 'body' ).attr( 'id', 'awf-fixed-popup-close-btn');
      }

      $( '.awf-togglable-preset-close-btn' ).on( 'click', a_w_f.close_togglable_preset );        
    }
  };
  
  a_w_f.move_togglable_preset_above_products = function() {
    a_w_f.togglable_preset.after( '<div id="awf-togglable-preset-placeholder"></div>' );
    a_w_f.togglable_preset.insertAfter( $( '.awf-togglable-preset-btn' ) );
  };
  
  a_w_f.move_togglable_preset_to_placeholder = function() {
    a_w_f.togglable_preset.insertBefore( $( '#awf-togglable-preset-placeholder' ) );
    $( '#awf-togglable-preset-placeholder' ).remove();
  };
  
  a_w_f.insert_togglable_preset_btn = function() {

    var $togglable_preset_btn = $( '<div class="awf-togglable-preset-btn"><i class="fas fa-bars awf-togglable-preset-btn-icon"></i><span>' + awf_data.i18n.togglable_preset_btn_label + '</span></div>' );

    var $position = $( '.annasta-toggle-filters-button' ).first();
    
    if( 0 < $position.length ) {
      $togglable_preset_btn.insertBefore( $position );

    } else {

      if( 0 < awf_data.togglable_preset.insert_btn_before_container.length ) {
        $position = $( awf_data.togglable_preset.insert_btn_before_container ).first();
      }

      if( 0 < $position.length ) {
        $togglable_preset_btn.insertBefore( $position );

      } else if( 0 < a_w_f.products_wrappers.length ) {
        $togglable_preset_btn.prependTo( a_w_f.products_wrappers.first() );

        if( 'fixed' === $togglable_preset_btn.css( 'position' ) ) {
          $togglable_preset_btn.appendTo( $( 'body' ) );

        } else {
          if( 'body' === awf_data.products_wrapper ) {
            $togglable_preset_btn.insertBefore( $( [awf_data.products_container, awf_data.no_result_container].join( ',' ) ).first() );
          }
        }
      } else {
        return;
      }
    }

    if( 'fixed' === $togglable_preset_btn.css( 'position' ) ) {
      a_w_f.togglable_preset.removeClass( 'awf-above-products-mode' ).addClass( 'awf-left-popup-sidebar-mode' );
    }
    
    if( a_w_f.togglable_preset.hasClass( 'awf-togglable-preset' ) ) {
      $togglable_preset_btn.addClass( 'awf-show-togglable-preset-btn' );
    }
    
    $togglable_preset_btn.off( 'click' ).on( 'click', function() {
      
      if( a_w_f.togglable_preset.hasClass( 'awf-left-popup-sidebar-mode' ) ) {
        $( 'body' ).addClass( 'awf-togglable-preset-on' );
        
        a_w_f.togglable_preset.before( '<div class="awf-togglable-preset-overlay"></div>' );
        $( '.awf-togglable-preset-overlay' ).on( 'click', a_w_f.close_togglable_preset );
        
      } else if( a_w_f.togglable_preset.hasClass( 'awf-above-products-mode' ) ) {
        $( 'body' ).toggleClass( 'awf-togglable-preset-on' );
      }

      if( ! a_w_f.togglable_preset.find( '.awf-pretty-scrollbars' ).first().hasClass( 'ps--active-y' ) ) {
        $.each( a_w_f.pretty_scrollbars, function( i, ps ) {
          if( a_w_f.togglable_preset.has( $( ps.element ) ) ) { ps.update(); }
        });
      }
      
    });
  };
  
  a_w_f.close_togglable_preset = function() {
    $( 'body' ).removeClass( 'awf-togglable-preset-on' );
    $( '.awf-togglable-preset-overlay' ).remove();
  };
  
  a_w_f.toggle_collapsible = function( $filter_title_container ) {

    var $fw = $filter_title_container.closest( '.awf-filter-wrapper' );
    $fw.toggleClass( 'awf-collapsed' );

    if( ! $fw.hasClass( 'awf-collapsed' ) ) {
      var $has_ps = $fw.find( '.awf-pretty-scrollbars' ).first();

      if( 0 < $has_ps.length ) {
        $.each( a_w_f.pretty_scrollbars, function( i, ps ) {
          if( ps.element === $has_ps[0] ) {
            ps.update();
            return false;
          }
        });
      }

      if( $fw.hasClass( 'awf-thl' ) ) {
        a_w_f.update_thl( $fw );
      }
    }
  };
 
  a_w_f.build_range_slider = function ( range_container ) {
    var $range_container = $( range_container );

    var range_values = $range_container.attr( 'data-values' ).split( '--' );
    $( range_values ).each( function( i, v ) {
      range_values[i] = parseFloat( v );
    });

    var min = parseFloat( range_values[0] );
    var max = parseFloat( range_values[range_values.length-1] );

    if( $range_container.hasClass( 'awf-price-range-slider-container' ) ) {
      
      var min_limit = parseFloat( $range_container.attr( 'data-min-limit' ) );
      var max_limit = parseFloat( $range_container.attr( 'data-max-limit' ) );

      if( ( min !== min_limit) || ( max !== max_limit) ) {
        range_values = range_values.filter( function( value ) {
          if( value <= min_limit || value >= max_limit ) { return false; }

          return true;
        });

        range_values.unshift( min_limit );
        range_values.push( max_limit );

        min = min_limit;
        max = max_limit;
      }
    }

    var format = {
      decimals: $range_container.attr( 'data-decimals' ),
      mark: $range_container.attr( 'data-decimals-separator' ),
      thousand: $range_container.attr( 'data-thousand-separator' ),
      prefix: $range_container.attr( 'data-prefix' ),
      suffix: $range_container.attr( 'data-postfix' ) 
    }
    
    var display_tooltips = false;

    if( ( format.mark === format.thousand ) || 'disable_thousand_separator' in awf_data ) {
      $range_container.attr( 'data-thousand-separator', '' );
      format.thousand = '';
    }
    
    if( 'above_handles' === $range_container.attr( 'data-tooltips' ) ) {
      display_tooltips = [ wNumb( format ), wNumb( format ) ];
    }
    
    noUiSlider.create( range_container, {
      range: {
        'min': [min],
        'max': [max]
      },
      start: [parseFloat( $range_container.find( '.awf-range-slider-min' ).val() ), parseFloat( $range_container.find( '.awf-range-slider-max' ).val() )],
      step: parseFloat( $range_container.attr( 'data-step' ) ),
      pips: {
        mode: 'values',
        values: range_values,
        density: 5,
        format: wNumb( format )
      },
      connect: true,
      tooltips: display_tooltips,
      behaviour: 'drag'
    });
    
    range_container.noUiSlider.on( 'change', function( values, handle ) {
      a_w_f.range_slider_onchange( range_container, values, min, max );
    });
    
    if( 'premium' in a_w_f ) { a_w_f.set_interactive_slider_tooltips( range_container ); }
  };
 
  a_w_f.get_ajax_history_state = function () {
    var history_state = window.history.state;

    if( 'object' === typeof( history_state ) && null !== history_state ) {
      history_state.awf_ajax_call = true;
    } else {
      history_state = { awf_ajax_call: true };
    }

    return history_state;
  };
  
  a_w_f.load_new_history_state = function( url ) {

    if( 'undefined' === typeof( url ) ) {
      url = a_w_f.build_url( $.extend( true, {}, awf_data.query ) );
    }

    var history_state = a_w_f.get_ajax_history_state();

    window.history.replaceState( history_state, document.title, window.location.href );
    window.history.pushState( history_state, '', url );
    window.location.reload();
  };

  $( window ).on( 'popstate', function( event ) {
    if( ( 'undefined' !== typeof( event.originalEvent.state ) && null !== event.originalEvent.state ) && ( 'awf_ajax_call' in event.originalEvent.state ) ) {
      window.location.reload();
    }
  });
  
  if( ! ( 'redirect_ajax' in awf_data ) ) {

    window.history.replaceState( a_w_f.get_ajax_history_state(), document.title, window.location.href );

    $( window ).on( 'beforeunload', function() {
      var state = window.history.state;

      if( 'object' === typeof( state ) && null !== state ) {
        delete state.awf_ajax_call;
      }

      window.history.replaceState( state, document.title, window.location.href );
    });

    $( 'body' ).addClass( 'awf-filterable' );
    
    a_w_f.build_products_wrappers();

    a_w_f.page_reload = false;
    if( 'no' === awf_data.wrapper_reload ) {
      if( a_w_f.products_wrappers.find( awf_data.products_container ).length < a_w_f.products_wrappers.length && 0 < a_w_f.products_wrappers.find( awf_data.no_result_container ).first().length ) {
        a_w_f.page_reload = true;
      }
    }
  
    a_w_f.update_orderby( false );
    a_w_f.setup_togglable_preset();
    
    if( ( 'title_badges' in awf_data ) || 'yes' === awf_data.wrapper_reload ) {
      $( '<div id="awf-title-badges-storage" style="display: none;" class="awf-active-badges-container"></div>' ).insertAfter( a_w_f.products_wrappers );
    }

    if( 'ajax_pagination' in awf_data ) {
      $( '.awf-preset-wrapper.awf-ajax.awf-button' ).find( '.awf-filter, .awf-range-slider-container, .awf-taxonomy-range-slider-container' ).addClass( 'awf-button-filter' );
      a_w_f.setup_ajax_pagination( a_w_f.products_wrappers );
    }
    
    $( '.awf-force-reload .awf-filter' ).each( function( i, filter ) {
      var $filter = $( filter );
      if( 0 === i ) {
        if( a_w_f.force_reload ) {
          if( ! ( $filter.attr( 'data-taxonomy' ) in a_w_f.force_reload ) ) {
            a_w_f.force_reload.push( $filter.attr( 'data-taxonomy' ) );
          }
        } else {
          a_w_f.force_reload = [$filter.attr( 'data-taxonomy' )];
        }
      }
      $filter.on( 'click', function() { a_w_f.page_reload = true; });
    });

    a_w_f.woocommerce_breadcrumbs = $( '.woocommerce-breadcrumb' );
    
    if( 'undefined' !== typeof $.blockUI ) {
      $.blockUI.defaults.message = '<div class="awf-loader"></div>';
      $.blockUI.defaults.overlayCSS = { backgroundColor: '#fff', opacity: 0.5, cursor: 'none' };
      $.blockUI.defaults.css = { border: 'none' };
    }
  }
  
  if( 'undefined' !== typeof PerfectScrollbar ) {
    $( '.awf-pretty-scrollbars' ).each( function( i, container ) {
      a_w_f.pretty_scrollbars.push( new PerfectScrollbar( container, { suppressScrollX: true } ) );
    });
  }
  
  $( '.awf-filter' ).each( function( i, filter ) {
    a_w_f.set_filter_events( $( filter ) );
  });
  
  $( '.awf-range-slider-container' ).each( function( i, el ) {
    var $el = $( el );

    if( i === 0 ) { $el.addClass( 'awf-active' );  }
    a_w_f.build_range_slider( el );

    if( $el.hasClass( 'awf-range-btn' ) ) {
      $el.parent().find( '.awf-apply-filter-btn' ).on( 'click', function() {
        a_w_f.build_active_badges();
      } );
    }
  });
  
  $( '.awf-sbs' ).each( function( nn, wrapper ) {
    a_w_f.sbs = 'yes';
    var $wrapper = $( wrapper );
    var apply = true;
    var force_redirect = false;
    
    if( $wrapper.hasClass( 'awf-sbs-submit-last' ) ) { apply = false; }
    
    if( $wrapper.hasClass( 'awf-button' ) ) {
      apply = false;
      $wrapper.find( '.awf-apply-filter-btn' ).on( 'click', function() {
        if( ( 'redirect_ajax' in awf_data ) || wrapper.hasAttribute( 'data-sbs-redirect' ) ) { $wrapper.addClass( 'awf-sbs-redirect' ); }
        a_w_f.apply_filter( 'sbs', $wrapper );
      });
    } else {
      if( ! $wrapper.hasClass( 'awf-sbs-submit-last' ) ) { force_redirect = true; }
    }
    
    if( $wrapper.hasClass( 'awf-sbs-next-btn-on' ) ) {
      apply = false;
      var redirect_next = force_redirect;
      force_redirect = false;
      
      $wrapper.find( '.awf-sbs-next-btn' ).on( 'click', function() {
        if( redirect_next ) { $wrapper.addClass( 'awf-sbs-redirect' ); }
        a_w_f.update_sbs( $wrapper, 0, true );
      });
      
    }
    
    if( $wrapper.hasClass( 'awf-sbs-back-btn-on' ) ) {
      $wrapper.find( '.awf-sbs-back-btn' ).on( 'click', function() {
        var i = parseInt( $wrapper.find( '.awf-filter-wrapper:not(.awf-hidden)' ).last().attr( 'data-sbs-i' ) ) - 1;
        
        if( $wrapper.hasClass( 'awf-sbs-next-btn-on' ) ) {
          a_w_f.reset_filter( $wrapper.find( '.awf-filter-wrapper.awf-sbs-' + (i + 1) ).attr( 'data-taxonomy' ) );
        } else {
          a_w_f.reset_filter( $wrapper.find( '.awf-filter-wrapper.awf-sbs-' + i ).attr( 'data-taxonomy' ) );
          if( i < 2 ) { i = 0; }
        }
        
        a_w_f.update_sbs( $wrapper, i, apply );
      });
    }
    
    $wrapper.find( '.awf-filter[type="checkbox"], .awf-filter[type="radio"]' ).on( 'click', function() {
      if( force_redirect ) { $wrapper.addClass( 'awf-sbs-redirect' ); }
      a_w_f.update_sbs( $wrapper, $( this ).closest( '.awf-filter-wrapper' ).first().attr( 'data-sbs-i' ), apply );
    });
    $wrapper.find( '.awf-filter[type="search"]' ).on( 'change', function() {
      if( force_redirect ) { $wrapper.addClass( 'awf-sbs-redirect' ); }
      a_w_f.update_sbs( $wrapper, $( this ).closest( '.awf-filter-wrapper' ).first().attr( 'data-sbs-i' ), apply );
    });
    $wrapper.find( '.awf-range-slider-container' ).each( function( i, range ) {
      $( range ).attr( 'data-force-sbs-redirect', ( force_redirect ? '1' : '0' ) ).attr( 'data-apply-sbs', ( apply ? '1' : '0' ) ).closest( '.awf-filter-wrapper' ).first().addClass( 'awf-sbs-range-slider' );
    });
    $wrapper.find( '.awf-taxonomy-range-slider-container' ).each( function( i, slider ) {
      $( slider ).attr( 'data-force-sbs-redirect', ( force_redirect ? '1' : '0' ) ).attr( 'data-apply-sbs', ( apply ? '1' : '0' ) );
    });
    $wrapper.find( '.awf-daterangepicker' ).each( function( i, daterangepicker ) {
      $( daterangepicker ).on( 'apply.daterangepicker cancel.daterangepicker', function() {
        if( force_redirect ) { $wrapper.addClass( 'awf-sbs-redirect' ); }
        a_w_f.update_sbs( $wrapper, $( daterangepicker ).closest( '.awf-filter-wrapper' ).first().attr( 'data-sbs-i' ), apply );
      });
    });
    
    a_w_f.update_sbs( $wrapper, 0, false );
  });
  
  $( document ).on( 'click', '.awf-url label', function() {
    if( $( this ).siblings( 'input' ).first().is( ':disabled' ) ) { return; }
    
    $( this ).siblings( 'input' ).prop( 'checked', true );    
  });
  
  $( document ).on( 'click', '.awf-apply-filter-btn', function() {
    a_w_f.apply_filter( true, $( this ).closest( '.awf-preset-wrapper' ) );
  });
  
  $( document ).on( 'submit', '.awf-filters-form', function() {
    a_w_f.update_url();
    
    $( this ).find( '.awf-filter' ).each( function( i, filter ) {
      var name = $( filter ).attr( 'name' );
      
      var brackets_pos = name.lastIndexOf( '[]' );
      if( -1 < brackets_pos ) { name = name.substr( 0, brackets_pos ); }
      
      if( ! ( name in awf_data.query ) ) {
        $( filter ).attr( 'disabled', 'disabled' ); }
    });
  });
  
  $( document ).on( 'click', '.awf-collapsible .awf-filter-title-container', function() { a_w_f.toggle_collapsible( $( this ) ); });

  a_w_f.cc_filters = $( '.awf-collapsible-children' );

  if( 0 < a_w_f.cc_filters.length ) {

    a_w_f.cc_filters.each( function( i, filter ) {
      var $fsc = $( filter );

      $fsc.on( 'click', '.awf-parent-container:not(.awf-hide-collapse-button)', function( event ) {
        
        if( ( 'target' in event ) && ! $( event.target ).hasClass( 'awf-filter-container' ) ) { return; }

        var $filter_container = $( this );
        var $filter_wrapper = $filter_container.closest( '.awf-filter-wrapper' );
        $filter_container.toggleClass( 'awf-collapsed-on' );
        $filter_container.next( '.awf-children-container' ).toggleClass( 'awf-collapsed' );
        
        var $a_filter = $filter_container.find( '.awf-filter' ).first();
        if( $a_filter.length > 0 ) {
          $.each( a_w_f.pretty_scrollbars, function( i, ps ) {
            if( 0 < $( ps.element ).closest( '.awf-filter-wrapper' ).is( '[data-taxonomy="' + $a_filter.attr( 'data-taxonomy' ) + '"]' ) ) {
              ps.update();
            }
          });

          if( $filter_wrapper.hasClass( 'awf-thl' ) ) { a_w_f.update_thl( $filter_wrapper ); }
          if( $filter_wrapper.hasClass( 'awf-thl' ) || $filter_wrapper.hasClass( 'awf-adjust-dd-footer' ) ) {
            a_w_f.adjust_dropdown_footer( $filter_wrapper );
          }
        }
      });

      if( ( 'premium' in a_w_f ) && 0 < $fsc.find( '.awf-empty-hidden').first().length ) {
        a_w_f.adjust_cc_filters( $fsc );

        $( document ).on( 'awf_after_counts_update', function() {
          a_w_f.adjust_cc_filters( $fsc );
        } );
      }
    } );
  }
  
  $( document ).on( 'input', '.awf-terms-search', function() {
    awf_search_filter_terms( $( this ) );
  });
  
  $( document ).on( 'keyup', '.awf-terms-search', function( event ) {
    if( event.keyCode === 8 || event.keyCode === 46 ) { awf_search_filter_terms( $( this ) ); }
  });
  
  $( document ).on( 'click', '.awf-clear-terms-search-btn', function() {
    awf_search_filter_terms( $( this ).siblings( '.awf-terms-search' ).val( '' ) );
  });
  
  awf_register_reset_btns();
  a_w_f.build_active_badges();

  $( '.awf-block-deselection-container .awf-filter-container' ).addClass( 'awf-block-deselection' ).each( function() {
    var taxonomy = '';
    
    $( this ).find( '.awf-filter' ).each( function() {
      taxonomy = $( this ).attr( 'data-taxonomy' );
  
      $( this ).on( 'click', function() {
        if( taxonomy in awf_data.query && -1 === awf_data.query[taxonomy].toString().indexOf( ',' ) ) {
          $( '.awf-filter[data-taxonomy="' + taxonomy + '"][value="' + awf_data.query[taxonomy] + '"]' ).closest( '.awf-filter-container' ).addClass( 'awf-is-last-active' );
          $( '.awf-active-badge[data-taxonomy="' + taxonomy + '"]' ).addClass( 'awf-is-last-active' );
        } else {
          $( '.awf-filter-wrapper[data-taxonomy="' + taxonomy + '"] .awf-filter-container' ).removeClass( 'awf-is-last-active' );
          $( '.awf-active-badge[data-taxonomy="' + taxonomy + '"]' ).removeClass( 'awf-is-last-active' );
        }
      } );
    });

    if( taxonomy in awf_data.query && -1 === awf_data.query[taxonomy].toString().indexOf( ',' ) ) {
      $( '.awf-filter[data-taxonomy="' + taxonomy + '"][value="' + awf_data.query[taxonomy] + '"]' ).closest( '.awf-filter-container' ).addClass( 'awf-is-last-active' );
      $( '.awf-active-badge[data-taxonomy="' + taxonomy + '"]' ).addClass( 'awf-is-last-active' );  
    }

  } );
  
  if( 0 < $( '.awf-product-categories' ).length ) {
    $( document ).on( 'awf_after_ajax_products_update', function( event, $response ) {
      var $categories_list = $response.find( '.awf-product-categories' ).first();
      if( 0 < $categories_list.length ) {
        $( '.awf-product-categories' ).replaceWith( $categories_list );
      } else {
        $( '.awf-product-categories' ).html( '' );
      }
    } );
  }

  $( document ).trigger( 'awf_after_setup' );
  
  function awf_search_filter_terms( $input ) {

    var $filter_containers = $input.closest( '.awf-filters-container' ).find( '.awf-filter-container' ).filter( function( i, el ) {
      var hidden = $( el ).hasClass( 'awf-empty-hidden' ) && $( el ).hasClass( 'awf-empty' );

      if( hidden && $( el ).hasClass( 'awf-unhide-active' ) && $( el ).hasClass( 'awf-active' ) ) {
        hidden = false;
      }
      
      return ! hidden;
    });
    
    var search = $input.val().toLowerCase();
    
    if ( '' === search ) {
      $filter_containers.removeClass( 'awf-hidden' );
      
    } else {
      for( var i = ( $filter_containers.length - 1 ); i >= 0; i-- ) {
        var $row = $( $filter_containers[i] );
        var $label = $row.find( ' > label, > a > label' ).first();
        var label = $label.clone().children().remove().end().text();
        
        if( 0 >= label.length ) {
          label = $label.attr( 'data-badge-label' );
          if( typeof( label ) === 'undefined' ) { label = ''; }
        }
        
        if( label.toLowerCase().indexOf( search ) > -1 ) { $row.removeClass( 'awf-hidden' ); } else { $row.addClass( 'awf-hidden' ); }
      }
    }
  }
  
  function awf_register_reset_btns() {
    $( document ).on( 'click', '.awf-reset-btn', function() {
      var $reset_btn = $( this );
      var $preset_wrapper;

      if( $reset_btn.hasClass( 'awf-extra-reset-btn' ) ) {
        $preset_wrapper = $( '.awf-preset-wrapper.awf-ajax' ).first();
        if( 0 === $preset_wrapper.length ) { $preset_wrapper = $( '.awf-preset-wrapper' ).first(); }

      } else {
        $preset_wrapper = $reset_btn.closest( '.awf-preset-wrapper' );
      }
      
      a_w_f.reset_all_filters( [] );
      a_w_f.apply_filters_reset( $preset_wrapper );
    });
  }
  
});