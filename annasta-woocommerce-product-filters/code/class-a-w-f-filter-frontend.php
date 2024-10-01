<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_filter_frontend' ) ) {
  
  class A_W_F_filter_frontend extends A_W_F_filter {

    public $terms;
    public $filter_name;
    public $var_name = '';
    protected $max_var_name;
    protected $terms_by_parent = array();
    protected $active_values = array();
    protected $input_classes;
    protected $hierarchical_level = 1;

    public function __construct( $preset_id, $filter_id ) {
      parent::__construct( $preset_id, $filter_id );

      $this->terms = $this->get_limited_terms();
      
      $this->filter_name = $this->module;
      if( isset( $this->settings['hierarchical_level'] ) ) { $this->hierarchical_level = (int) $this->settings['hierarchical_level']; }

      if( 'taxonomy' === $this->module ) {
        if( ! isset( A_W_F::$front->vars->tax[$this->settings['taxonomy']] ) ) { return; }
        
        $this->filter_name = $this->settings['taxonomy'];
        
        if( 'range' === $this->settings['type'] && 'taxonomy_range' !== $this->settings['type_options']['range_type'] ) {
          $this->setup_numeric_taxonomy_range();
          
        } else {
          if( A_W_F::$front->is_archive === $this->settings['taxonomy'] ) {
            if( 'yes' === get_option( 'awf_hierarchical_archive_permalinks', 'no' ) ) {
              if( 'multi' === $this->settings['type'] ) { $this->settings['type'] = 'single'; }
            }
          }

          $this->var_name = A_W_F::$front->vars->tax[$this->settings['taxonomy']];

          if( isset( A_W_F::$front->query->tax[$this->settings['taxonomy']] ) ) {
            $this->active_values = A_W_F::$front->query->tax[$this->settings['taxonomy']];
          }

          if( ( ! empty( $this->settings['show_count'] ) || ( isset( $this->settings['hide_empty'] ) && 'none' !== $this->settings['hide_empty'] ) )
            && ! isset( A_W_F::$front->counts[$this->settings['taxonomy']] )
          ) {
            A_W_F::$front->build_taxonomy_counts( $this->settings['taxonomy'], $this );
          }
        }

      } else {

        if( 'price' === $this->module || 'rating' === $this->module ) {
          $this->set_range_active_values();
                    
        } elseif( 'meta' === $this->module ) {
          
          if( empty( $this->settings['meta_name'] ) ) { return; }
          
          $this->filter_name = $this->settings['meta_name'];
          
          if( 'range' === $this->settings['type'] ) {
            $this->set_range_active_values();

          } elseif( 'date' === $this->settings['type'] ) {
            $var = 'awf_date_filter_' . ( empty( $this->settings['style_options']['db_date_format'] ) ? 'c' : $this->settings['style_options']['db_date_format'] ) . '_' . $this->settings['meta_name'];
              
            if( isset( A_W_F::$front->vars->meta[$var] ) ) {
              $this->var_name = A_W_F::$front->vars->meta[$var];

              if( isset( A_W_F::$front->query->meta[$var] ) ) {
                $this->active_values = A_W_F::$front->query->meta[$var];
              }
            }

          } else {
            $this->var_name = A_W_F::$front->vars->meta[$this->settings['meta_name']];

            if( isset( A_W_F::$front->query->meta[$this->settings['meta_name']] ) ) {
              $this->active_values = A_W_F::$front->query->meta[$this->settings['meta_name']];
            }
          }
                    
        } else {
          $this->var_name = A_W_F::$front->vars->awf[$this->module];

          if( isset( A_W_F::$front->query->awf[$this->module] ) ) {
            $this->active_values[] = A_W_F::$front->query->awf[$this->module];
          }
        }
      }
    }

    protected function set_range_active_values() {
      $var = 'min_' . $this->filter_name;
      $max_var = 'max_' . $this->filter_name;
      $this->var_name = A_W_F::$front->vars->range[$var];
      $this->max_var_name = A_W_F::$front->vars->range[$max_var];
      
      if( isset( A_W_F::$front->query->range[$var] ) ) {
        $this->active_values['min'] = A_W_F::$front->query->range[$var];
      } else {
        $this->active_values['min'] = (float) $this->settings['type_options']['range_values'][0];
      }

      if( isset( A_W_F::$front->query->range[$max_var] ) ) {
        $this->active_values['max'] = A_W_F::$front->query->range[$max_var];
      } else {
        $this->active_values['max'] = (float) $this->settings['type_options']['range_values'][count( $this->settings['type_options']['range_values'] ) - 1];
      }
    }

    public function get_html() {
      
      if( true !== ( $display_filter_html = apply_filters( 'awf_display_filter_html', true, $this ) ) || empty( $this->var_name ) ) {
        if( is_string( $display_filter_html ) ) {
          return $display_filter_html;
        }

        return '';
      }

      $this->terms_by_parent = $this->build_terms_by_parent( $this->terms );
      
      $this->input_classes = array( 'awf-filter' );
      if( in_array( $this->settings['style'], array( 'labels', 'icons', 'images', 'colours', 'tags', 'custom-terms', 'range-stars' ) ) ) {
        $this->input_classes[] = 'awf-hidden';
      }

      $wrapper_classes = array( 'awf-filter-wrapper' );
      $wrapper_options = '';
      $wrapper_append = '';
      
      if( ! empty( $this->settings['type'] ) ) {
        $wrapper_classes[] = 'awf-' . sanitize_html_class( $this->settings['type'] );
      }
        
      if( ! empty( $this->settings['button_submission'] ) ) {

        $wrapper_classes[] = $this->input_classes[] = 'awf-button-filter';

        if( empty( $this->settings['height_limit'] ) || ! empty( $this->settings['shrink_height_limit'] ) ) {
          $wrapper_classes[] = 'awf-adjust-dd-footer';
        }

        if( 'range-slider' !== $this->settings['style'] ) {
          if( 'form' === A_W_F::$front->preset->type ) {
            $wrapper_append .= '<button type="submit" name="awf_submit" class="awf-submit-btn" value="1">' . esc_html( $this->settings['style_options']['submit_button_label'] ) . '</button>';
  
          } else {
            $wrapper_append .= '<div class="awf-submit-btn-container awf-' . $this->preset_id . '-' . $this->id . '-submit-btn-container"><button type="button" class="awf-apply-filter-btn">' . esc_html( $this->settings['style_options']['submit_button_label'] ) . '</button></div>';
          }
        }
      }

      if( 'sbs' === A_W_F::$front->preset->type ) {
        $wrapper_classes[] = 'awf-hidden';
        $wrapper_classes[] = 'awf-sbs-' . A_W_F::$front->preset->sbs_count;
        $wrapper_options .= ' data-sbs-i="' . A_W_F::$front->preset->sbs_count . '"';
        
        A_W_F::$front->preset->sbs_count++;
      }

      if( ! empty( $this->settings['terms_limitation_mode'] ) && 'active' === $this->settings['terms_limitation_mode'] ) {
        $wrapper_classes[] = 'awf-af';
        if( empty( $this->settings['style_options']['display_active_filter_siblings'] ) ) {
          $wrapper_classes[] = 'awf-hide-afss';
        }
      }

      $html = $this->edit_filter_wrapper( $wrapper_classes, $wrapper_options, $wrapper_append );

      $html = '<div id="' . A_W_F::$front->preset->caller_id . '-filter-' . $this->preset_id . '-' . $this->id . '-wrapper"';
      $html .= ' class="' . implode( ' ', $wrapper_classes ) . '" data-taxonomy="' . esc_attr( $this->var_name ) . '"' . $wrapper_options;
      if( isset( $this->active_values['max'] ) ) { $html .= ' data-taxonomy-max="' . esc_attr( $this->max_var_name ) . '"'; };
      $html .= '>';

      if( ! empty( $this->settings['show_title'] ) ) {
        $html .= '<div class="awf-filter-title-container"><div class="awf-filter-title">' . esc_html( $this->settings['title'] ) . '</div>';
        $html .= $this->collapse_btn_html();
        $html .= '</div>';
      }

      if( ! empty( $this->settings['reset_active'] ) ) {
        $html .= '<div class="awf-reset-active-container" data-taxonomy="' . esc_attr( $this->var_name ) . '"' . ( empty( $this->max_var_name ) ? '' : ' data-taxonomy-max="' . esc_attr( $this->max_var_name ) . '"' )  . ( empty( $this->active_values ) || ( isset( $this->active_values['min'] ) && ! isset( A_W_F::$front->query->range['min_' . $this->filter_name] ) ) ? ' style="display:none;"' : '' ) . '><span>' . esc_html( $this->settings['reset_active_label'] ) . '</span></div>';
      }

      if( ! empty( $this->settings['show_active'] ) ) {
        $html .= '<div class="awf-active-badges-container"></div>';
      }

      $html .= '<div class="awf-filters-container awf-filters-' . $this->preset_id . '-' . $this->id . '-container';
      $html .= ' awf-filters-' . $this->var_name;
      if( 'search' === $this->module ) { $html .= ' awf-product-search'; }
      if( ! empty( $this->settings['style'] ) ) { $html .= ' awf-style-' . sanitize_html_class( $this->settings['style'] ); }
      if( ! empty( $this->settings['show_in_row'] ) ) { $html .= ' awf-show-in-row'; }
      if( ! empty( $this->settings['children_collapsible'] ) ) { $html .= ' awf-collapsible-children'; }
      if( isset( $this->settings['style_options']['hide_label'] ) ) { $html .= ' awf-hide-label'; }
      if( ! empty( $this->settings['height_limit'] ) ) {
        if( empty( $this->settings['style_options']['height_limit_style'] ) || 'scrollbars' === $this->settings['style_options']['height_limit_style'] || ( 'form' === A_W_F::$front->preset->type ) ) {
          $html .= ' awf-scrollbars-on';
          if( 'yes' === get_option( 'awf_pretty_scrollbars' ) ) { $html .= ' awf-pretty-scrollbars'; }
        }
      }
      if( ! empty( $this->settings['force_reload'] ) || ! empty( $this->settings['redirect_to_archive'] ) ) {
        $html .= ' awf-force-reload';
      }
      if( ! empty( $this->settings['block_deselection'] ) ) { $html .= ' awf-block-deselection-container'; }
      $html .= ' awf-hierarchical-level-' . $this->hierarchical_level;
      $html .= '"';
      $html .= '>';

      if( isset( $this->settings['show_search'] ) && ! empty( $this->settings['show_search'] ) ) {
        $placeholder = '';
        if( empty( $this->settings['show_search_placeholder'] ) ) {
          $placeholder = sprintf( esc_attr__( 'Search %1$s', 'annasta-filters' ), strtolower( $this->settings['title'] ) );
        } else {
          $placeholder = esc_attr( $this->settings['show_search_placeholder'] );
        }

        $html .= '<div class="awf-terms-search-container">';
        $html .= '<input type="text" placeholder="' . $placeholder . '" class="awf-terms-search">';
        $html .= '<button type="button" class="awf-clear-terms-search-btn"><i class="fas fa-times awf-clear-search-input"></i></button>';
        $html .= '</div>';
      }

      if( 'single' === $this->settings['type'] ) {
        if( empty( $this->active_values ) && false !== $this->default_value ) { $this->active_values[] = $this->default_value; }
      }

      if( 'range-slider' === $this->settings['style'] ) {
        if( 'taxonomy_range' === $this->settings['type_options']['range_type'] ) {
          if( $this instanceof A_W_F_premium_filter_frontend ) {
            $html .= $this->taxonomy_range_slider_html();
          }
        } else {
          $html .= $this->range_slider_html();
        }

      } else if( 'search' === $this->module ) {
        $html .= $this->product_search_html();

      } else if( 'daterangepicker' === $this->settings['style'] ) {
        $html .= $this->daterangepicker_html();

      } else {
        if( isset( $this->terms_by_parent[0] ) ) {
          $html .= '<ul>' . $this->terms_list_html() . '</ul>';
        }
      }

      $html .= '</div>' . $wrapper_append . '</div>';
      return $html;
    }

    protected function product_search_html() {

      $html = '<div class="awf-filter-container awf-product-search-container';
      
      if( ! empty( $this->settings['autocomplete'] ) ) {
        $html .= ' awf-search-autocomplete';
      }
      
      if( ! empty( $this->settings['submit_on_change'] ) ) {
        $this->input_classes[] = 'awf-submit-on-change';
      }

      if( isset( $this->active_values[0] ) ) {
        $html .= ' awf-active';
      } else {
        $this->active_values[0] = '';
      }
      $html .= '">';

      $html .= '<label for="' . A_W_F::$front->preset->caller_id . '-' . $this->var_name . '" class="screen-reader-text" data-badge-label="' . ( empty( $this->settings['active_prefix'] ) ? '' : esc_attr( $this->settings['active_prefix'] ) ) . '">';
      $html .= esc_html__( 'Search products:', 'annasta-filters' ) . '</label>';
      $html .= '<input type="search" id="' . A_W_F::$front->preset->caller_id . '-' . $this->var_name . '"';
      $html .= ' name="' . esc_attr( $this->var_name ) . '" value="' . esc_attr( stripcslashes($this->active_values[0]) ) . '" data-taxonomy="' . esc_attr( $this->var_name ) . '"';
      $html .= ' class="' . implode( ' ', $this->input_classes ) . '"';
      
      if( ! empty( $this->settings['placeholder'] ) ) {
        $html .= ' placeholder="' . esc_attr( $this->settings['placeholder'] ) . '"';
      }
      
      if( ! empty( $this->settings['autocomplete'] ) ) {
        $html .= ' autocomplete="off">';
        $html .= '<button type="button" class="awf-clear-search-btn"><i class="fas fa-times awf-clear-search-input"></i></button>';
        $html .= '<div id="' . A_W_F::$front->preset->caller_id . '-' . $this->var_name . '-autocomplete-container" class="awf-product-search-autocomplete-container awf-collapsed';
        
        if( ! empty( $this->settings['style_options']['autocomplete_height_limit'] ) ) {
          if( 'yes' === get_option( 'awf_pretty_scrollbars' ) ) { $html .= ' awf-pretty-scrollbars'; }
        }
        
        $html .= '" data-after="' . $this->settings['type_options']['autocomplete_after'] . '"></div>';

      } else {
        $html .= '><button type="button" class="awf-clear-search-btn"><i class="fas fa-times awf-clear-search-input"></i></button>';
      }

      $html .= '</div>';

      return $html;
    }

    protected function range_slider_html() {
      $min_var = 'min_' .  $this->filter_name;
      $max_var = 'max_' .  $this->filter_name;

      $min_range_value = $min_range_limit = (float) $this->settings['type_options']['range_values'][0];
      $max_range_value = $max_range_limit = (float) $this->settings['type_options']['range_values'][count( $this->settings['type_options']['range_values'] ) - 1];

      $step = (float) $this->settings['style_options']['step'];

      if( 'price' === $this->module && 'yes' === get_option( 'awf_dynamic_price_ranges', 'no' ) ) {
        if( ! isset( A_W_F::$front->get_access_to['price_filter_min_max'] ) ) {
          $min_max = A_W_F::$front->get_access_to['price_filter_min_max'] = A_W_F::$front->get_price_filter_min_max();
        } else {
          $min_max = A_W_F::$front->get_access_to['price_filter_min_max'];
        }

        $min_range_limit = (float) array_shift( $min_max );
        $max_range_limit = (float) array_shift( $min_max );

        $min_range_limit = floor( $min_range_limit );
        $max_range_limit = ceil( $max_range_limit );
        $step = ceil( $step );

        if( (($max_range_limit - $min_range_limit) < $step) || $min_range_limit % $step !== 0 || $max_range_limit % $step !== 0 ) {
          $min_range_limit = $min_range_limit - ( $min_range_limit % $step );
          $max_range_limit = $max_range_limit + ($step - ($max_range_limit % $step));
        }

        if( $this->active_values['min'] === $min_range_value && $this->active_values['max'] === $max_range_value ) {
          $this->active_values['min'] = $min_range_limit;
          $this->active_values['max'] = $max_range_limit;
        }

      }
      
      $html = '';

      if( ! empty( $this->settings['style_options']['slider_tooltips'] )
         && 'interactive_above' === $this->settings['style_options']['slider_tooltips']
      ) {
        $html .= $this->interactive_tooltips_html();
      }
      
      $html .= '<div class="awf-filter-container awf-range-slider-container';
      if( ! empty( $this->settings['button_submission'] ) ) { $html .= ' awf-range-btn'; }
      
      if(
        (
          ( isset( A_W_F::$front->query->range[$min_var] ) && A_W_F::$front->query->range[$min_var] === $this->active_values['min'] )
           || ( ! isset( A_W_F::$front->query->range[$min_var] ) && $this->active_values['min'] === $min_range_limit )
        ) && (
          ( isset( A_W_F::$front->query->range[$max_var] ) && A_W_F::$front->query->range[$max_var] === $this->active_values['max'] )
          || ( ! isset( A_W_F::$front->query->range[$max_var] ) && $this->active_values['max'] === $max_range_limit )
        )
      ) {

        $html .= ' awf-active';
      }
      
      if( 'price' === $this->module ) { $html .= ' awf-price-range-slider-container'; }
      
      $html .= $this->add_range_slider_container_classes();
      $html .= '"';
      
      $html .= ' data-min="' . esc_attr( $this->active_values['min'] ) . '" data-max="' . esc_attr( $this->active_values['max'] ) . '"';
      $html .= ' data-min-limit="' . esc_attr( $min_range_limit ) . '" data-max-limit="' . esc_attr( $max_range_limit ) . '"';
      $html .= ' data-values="' . esc_attr( implode( '--', $this->settings['type_options']['range_values'] ) ) . '"';
      $html .= ' data-step="' . esc_attr( $step ) . '"';
      $html .= ' data-label="' . esc_attr( empty( $this->settings['active_prefix'] ) ? '' : $this->settings['active_prefix'] ) . '"';
      $html .= ' data-tooltips="' . esc_attr( empty( $this->settings['style_options']['slider_tooltips'] ) ? 'above_handles' : $this->settings['style_options']['slider_tooltips'] ) . '"';
      $html .= ' data-prefix="' . esc_attr( $this->settings['style_options']['value_prefix'] ) . '"';
      $html .= ' data-postfix="' . esc_attr( $this->settings['style_options']['value_postfix'] ) . '"';
      $html .= ' data-decimals="' . esc_attr( $this->settings['type_options']['decimals'] ) . '"';
      $html .= ' data-decimals-separator="' . esc_attr( wc_get_price_decimal_separator() ) . '"';
      $html .= ' data-thousand-separator="' . esc_attr( wc_get_price_thousand_separator() ) . '"';
      
      $html .= '>';

      $html .= '<input type="hidden" name="' . esc_attr( $this->var_name ) . '" value="' . esc_attr( $this->active_values['min'] ) . '"';
      $html .= ' data-taxonomy="' . esc_attr( $this->var_name ) . '" data-filter-name="' . $this->filter_name . '"';
      $html .= ' class="' . implode( ' ', $this->input_classes ) . ' awf-range-slider-value awf-range-slider-min';

      if( $this->active_values['min'] === $min_range_limit ) {
        $html .= ' awf-default';
      }

      $html .= '">';

      $html .= '<input type="hidden" name="' . $this->max_var_name . '"';
      $html .= ' value="' . esc_attr( $this->active_values['max'] ) . '" data-taxonomy="' . esc_attr( $this->max_var_name ) . '" data-filter-name="' . $this->filter_name . '"';
      $html .= ' class="' . implode( ' ', $this->input_classes ) . ' awf-range-slider-value awf-range-slider-max';

      if( $this->active_values['max'] === $max_range_limit ) {
        $html .= ' awf-default';
      }

      $html .= '">';

      $html .= '</div>';

      if( ! empty( $this->settings['button_submission'] ) ) {
        if( 'form' === A_W_F::$front->preset->type ) {
          $html .= '<button type="submit" name="awf_submit" value="1">' . esc_html( $this->settings['style_options']['submit_button_label'] ) . '</button>';

        } else {
          $html .= '<button type="button" class="awf-apply-filter-btn">' . esc_html( $this->settings['style_options']['submit_button_label'] ) . '</button>';
        }
      }

      return $html;
    }

    protected function daterangepicker_html() {
      
      $html = '<div class="awf-filter-container awf-daterangepicker-container';
      if( ! empty( $this->active_values ) ) {
        $html .= ' awf-active';
      }
      
      $html .= '">';
      
      $html .= '<input id="' . A_W_F::$front->preset->caller_id . '-filter-' . $this->preset_id . '-' . $this->id . '-daterangepicker" type="text" class="awf-daterangepicker';
      if( isset( $this->settings['style_options']['date_picker_type'] ) ) {
        $html .= ' awf-' . $this->settings['style_options']['date_picker_type'] . '-daterangepicker';
      }
      if( in_array( $this->settings['style_options']['db_date_format'], array( 'a', 'e', 'f') ) ) {
        $html .= ' awf-timepicker';
      }
      $html .= '"';
      
      $html .= ' placeholder="' . esc_attr( empty( $this->settings['style_options']['daterangepicker_placeholder'] ) ? '' : $this->settings['style_options']['daterangepicker_placeholder'] ) . '" data-clear-btn-label="' . esc_attr_x( 'Clear', 'Datepicker "Clear" button label', 'annasta-filters' ) . '"';
      
      $html .= '>';
      
      $html .= '<input type="hidden" id="' . A_W_F::$front->preset->caller_id . '-' . $this->var_name . '"';
      $html .= ' name="' . esc_attr( $this->var_name ) . '" value="' . esc_attr( implode( ',', $this->active_values ) ) . '" data-taxonomy="' . esc_attr( $this->var_name ) . '"';
      $html .= ' class="awf-filter"';
      $html .= ' data-label="' . esc_attr( empty( $this->settings['active_prefix'] ) ? '' : $this->settings['active_prefix'] ) . '"';
      $html .= '>';
      
      $html .= '</div>';

      return $html;
    }

    protected function terms_list_html( $parent_id = 0, $level = 1, $print = true ) {

      $html = '';

      foreach( $this->terms_by_parent[$parent_id] as $i => $term ) {

        $slug_for_classes = str_replace( array( ',', '.' ), '-', $term->slug );

        if( $print && $level >= $this->hierarchical_level ) {
          
          $input_id = A_W_F::$front->preset->caller_id . '-' . $this->var_name . '-' . $slug_for_classes;
          $container_classes = array( 'awf-filter-container', 'awf-' . $this->var_name . '-' . $slug_for_classes . '-container' );
          $input_classes = $this->input_classes;
          $input_props = array();
          $product_count_html = '';

          if( $term->slug === $this->default_value ) { $input_classes[] = 'awf-default'; }
          
          if( 'range' === $this->settings['type'] ) {
            $container_classes[] = 'awf-range-filter-container';
            
            if( $term->slug === $this->active_values['min'] && $term->next_value === $this->active_values['max'] ) {
              $container_classes[] = 'awf-active';
              $input_props[] = 'checked="checked"';
            }
            
          } else {
            if( in_array( $term->slug, $this->active_values ) ) {
              $container_classes[] = 'awf-active';
              $input_props[] = 'checked="checked"';
            }
          }
          
          if( ! empty( $this->settings['display_children'] ) && ! empty( $this->settings['children_collapsible'] ) && isset( $this->terms_by_parent[$term->term_id] ) ) {
            $container_classes[] = 'awf-parent-container';
            if( ! empty( $this->settings['children_collapsible_on'] ) ) {
              $container_classes[] = 'awf-collapsed-on';
            }
          }

          if( 'taxonomy' === $this->module && 'range' !== $this->settings['type'] && isset( A_W_F::$front->counts[$this->settings['taxonomy']] ) ) {
            
            if( ! isset( A_W_F::$front->counts[$this->settings['taxonomy']][$term->slug] ) ) { A_W_F::$front->counts[$this->settings['taxonomy']][$term->slug] = 0; }
            
            $this->set_hide_empty( $term->slug, $container_classes, $input_props );

            if( ! empty( $this->settings['show_count'] ) ) {
              $product_count_html = '<span class="awf-filter-count">' . esc_html( A_W_F::$front->counts[$this->settings['taxonomy']][$term->slug] ) . '</span>';
            }
          }

          $html .= '<li class="' . implode( ' ', $container_classes ) . '">';
          $filter_html = '';

          if( 'single' === $this->settings['type'] ) {
            $filter_html .= '<input type="radio" id="' . $input_id . '" name="' . esc_attr( $this->var_name ) . '"';

            if( 'taxonomy' === $this->module && A_W_F::$front->is_archive === $this->settings['taxonomy'] && 'yes' === get_option( 'awf_hierarchical_archive_permalinks', 'no' ) && A_W_F::$front->permalinks_on ) {
              $archive_permalink = get_term_link( $term->slug, A_W_F::$front->is_archive );
              if( ! is_wp_error( $archive_permalink ) ) {
                $filter_html .= ' data-archive-permalink="' . esc_attr( $archive_permalink ) . '"';
              }
            }

          } else if( 'multi' === $this->settings['type'] ) {
            $filter_html .= '<input type="checkbox" id="' . $input_id . '" name="' . esc_attr( $this->var_name ) . '[]"';

          } else if( 'range' === $this->settings['type'] ) {
            $filter_html .= '<input type="radio" id="' . $input_id . '" name="' . esc_attr( $this->var_name ) . '"';
            $filter_html .= ' data-filter-name="' . $this->filter_name . '"';
            $filter_html .= ' data-max-name="' . $this->max_var_name . '"';
            
            if( in_array( $this->settings['type_options']['range_type'], array( 'auto_range', 'custom_range' ) ) ) {
              $filter_html .= ' data-next-value="' . esc_attr( $term->next_value ) . '"';
              $filter_html .= ' data-decimals="' . esc_attr( $this->settings['type_options']['decimals'] ) . '"';
            }
          }

          $filter_html .= ' value="' . esc_attr( $term->slug ) . '" data-taxonomy="' . esc_attr( $this->var_name ) . '"';

          if( ! empty( $this->settings['redirect_to_archive'] ) && in_array( 'awf-redirect-to-archive', $this->input_classes ) ) {
            $archive_permalink = get_term_link( $term->slug, $this->settings['taxonomy'] );
            if( ! is_wp_error( $archive_permalink ) ) {
              $filter_html .= ' data-archive-permalink="' . esc_attr( $archive_permalink ) . '"';
            }
          }

          $filter_html .= ' class="' . implode( ' ', $input_classes ) . '" ' . implode( ' ', $input_props ) . '>';

          $filter_html .= '<label';
          if( true !== A_W_F::$front->preset->is_url_query ) { $filter_html .= ' for="' . $input_id . '"'; };

          $filter_html .= ' class="';
          
          if( in_array( $this->settings['style'], array( 'images', 'colours', 'custom-terms' ) ) ) { 
            $filter_html .= 'awf-term-' . sanitize_html_class( $term->term_id );
            
          } elseif( 'range-stars' === $this->settings['style'] ) {
            $filter_html .= 'awf-' . intval( $term->next_value ) . '-stars';
          }
          
          $filter_html .= '"';
          
          $this->set_term_label( $term );
          
          $title_attr = $term->name;
          if( ! empty( $this->settings['active_prefix'] ) ) { $title_attr = $this->settings['active_prefix'] . ' ' . $title_attr; }
          $title_attr = esc_attr( $title_attr );
          $filter_html .= ' title="' . $title_attr . '" data-badge-label="' . $title_attr . '"';
          
          $filter_html .= '>';

          if( isset( $this->settings['style_options']['hide_label'] ) ) {
            $filter_html .= '<span class="awf-count-wo-label">' . $product_count_html . '</span>';
            
          } else {
            $filter_html .= $term->name . $product_count_html;
          }

          $filter_html .= '</label>';

          if( true === A_W_F::$front->preset->is_url_query ) {

            $href = '';

            if( ! in_array( 'disabled', $input_props ) ) {
              if( empty( $this->settings['reset_all'] ) ) {
                $f = 'get_' . $this->settings['type'] . '_type_term_url';
                if( method_exists( $this, $f ) ) { $href = $this->$f( $term ); }
                
              } else {
                if( $this instanceof A_W_F_premium_filter_frontend ) { $href = $this->get_reset_all_url( $term ); }
              }
            }
            
            if( empty( $href ) ) { $href = 'javascript:void(0);'; } else { $href = esc_url( $href ); }

            $html .= '<a href="' . $href . '">';
            $html .= $filter_html;
            $html .= '</a>';

          } else {
            $html .= $filter_html;
          }

          $html .= '</li>';
        }

        if( isset( $this->terms_by_parent[$term->term_id] ) ) {
          $new_level = $level + 1;

          if( empty( $this->settings['display_children'] ) ) {
            if( $new_level < $this->hierarchical_level ) {
              $html .= $this->terms_list_html( $term->term_id, $new_level, false );

            } elseif( $new_level === $this->hierarchical_level ) {
              $html .= '<ul class="awf-children-container';
              $html .= '" data-parent="' . esc_attr( $term->slug ) . '">';
              $html .= $this->terms_list_html( $term->term_id, $new_level );
              $html .= '</ul>';
            }

          } else {
            if( $new_level < $this->hierarchical_level ) {
              $html .= $this->terms_list_html( $term->term_id, $new_level, false );

            } else {
              $html .= '<ul class="awf-children-container';
              if( ! empty( $this->settings['children_collapsible_on'] ) && $new_level !== $this->hierarchical_level ) { $html .= ' awf-collapsed'; }
              $html .= '" data-parent="' . esc_attr( $term->slug ) . '">';
              $html .= $this->terms_list_html( $term->term_id, $new_level );
              $html .= '</ul>';
            }
          }
        }

      }

      return $html;
    }

    public function get_single_type_term_url( $term ) {
      $url_filters = A_W_F::$front->url_query;

      $url = A_W_F::$front->current_url;

      if( $term->slug === $this->default_value ) { 
        if( isset( $url_filters[$this->var_name] ) ) {
          unset( $url_filters[$this->var_name] );
        }
        
      } else {
        if( 'taxonomy' === $this->module && A_W_F::$front->is_archive === $this->settings['taxonomy'] ) {

          if( 'yes' === get_option( 'awf_hierarchical_archive_permalinks', 'no' ) ) {
            $url = get_term_link( $term->slug, A_W_F::$front->is_archive );
            if( is_wp_error( $url ) ) { $url = A_W_F::$front->current_url; }

          } else {
						$replace = user_trailingslashit( '/' . implode( ',', A_W_F::$front->query->tax[A_W_F::$front->is_archive] ) );
						$pos = strrpos( A_W_F::$front->current_url, $replace );
						if ( $pos !== false ) {
              $url = substr_replace( A_W_F::$front->current_url, user_trailingslashit( '/' . $term->slug ), $pos, strlen( $replace ) );
            }
          }

          if( ! A_W_F::$front->permalinks_on ) {
            $url_filters[A_W_F::$front->is_archive] = $term->slug;
          }

          unset( $url_filters[$this->var_name] );

        } else {
          if( in_array( $term->slug, $this->active_values ) ) {
            unset( $url_filters[$this->var_name] );
            
          } else {
            $url_filters[$this->var_name] = $term->slug;
          }
        }
      }
			
			if( isset( $url_filters[A_W_F::$front->vars->awf['search']] ) ) {
				$url_filters[A_W_F::$front->vars->awf['search']] = urlencode( $url_filters[A_W_F::$front->vars->awf['search']] );
			}

      return add_query_arg( $url_filters, $url );
    }

    public function get_multi_type_term_url( $term ) {
      $url_filters = A_W_F::$front->url_query;
      $url = A_W_F::$front->current_url;
      $href_terms = $this->active_values;

      if( empty( $this->active_values ) ) {
        $url_filters[$this->var_name] = $term->slug;

      } else {
        $key = array_search( $term->slug, $href_terms );

        if ( $key === false) {
          if( 'taxonomy' === $this->module ) {
            if( 0 !== $term->parent ) {
              $this->remove_ancestors( $term, $href_terms );
            }

            if( isset( A_W_F::$front->page_associations[$this->settings['taxonomy']][$term->term_id] ) || isset( A_W_F::$front->page_parent_associations[$this->settings['taxonomy']][$term->term_id] ) ) {
              $children = get_terms( array(
                'taxonomy' => $this->settings['taxonomy'],
                'fields' => 'slugs',
                'child_of' => (int) $term->term_id,
                'hide_empty' => false
              ));

              $href_terms = array_diff( $href_terms, $children );
            }
          }

          $href_terms[] = $term->slug;

        } else {
          unset( $href_terms[$key] );
        }

        if( empty( $href_terms ) ) { 
          unset( $url_filters[$this->var_name] );

        } else {
          sort( $href_terms );
          $url_filters[$this->var_name] = implode( ',', $href_terms );
        }
      }

      if( 'taxonomy' === $this->module && A_W_F::$front->is_archive === $this->settings['taxonomy'] ) {
        if( isset( $url_filters[$this->var_name] ) ) {

          if( A_W_F::$front->permalinks_on ) {
            $replace = user_trailingslashit( '/' . implode( ',', A_W_F::$front->query->tax[A_W_F::$front->is_archive] ) );
            $pos = strrpos( A_W_F::$front->current_url, $replace );
            if ( $pos !== false ) {
              $url = substr_replace( A_W_F::$front->current_url, user_trailingslashit( '/' . implode( ',', $href_terms ) ), $pos, strlen( $replace ) );
            }

          } else {
            $url_filters[A_W_F::$front->is_archive] = implode( ',', $href_terms );
          }

          unset( $url_filters[$this->var_name] );
        }
      }
    
			if( isset( $url_filters[A_W_F::$front->vars->awf['search']] ) ) {
				$url_filters[A_W_F::$front->vars->awf['search']] = urlencode( $url_filters[A_W_F::$front->vars->awf['search']] );
			}

      return add_query_arg( $url_filters, $url );
    }
    
    public function get_range_type_term_url( $term ) {
      $url_filters = A_W_F::$front->url_query;
      $url = A_W_F::$front->current_url;
      
      if( in_array( $term->slug, $this->active_values ) && in_array( $term->next_value, $this->active_values ) ) {
        unset( $url_filters[$this->var_name] );
        unset( $url_filters[$this->max_var_name] );
        
      } else {
        $url_filters[$this->var_name] = $term->slug;
        $url_filters[$this->max_var_name] = $term->next_value;
      }
			
			if( isset( $url_filters[A_W_F::$front->vars->awf['search']] ) ) {
				$url_filters[A_W_F::$front->vars->awf['search']] = urlencode( $url_filters[A_W_F::$front->vars->awf['search']] );
			}

      return add_query_arg( $url_filters, $url );
    }

    protected function remove_ancestors( $term, &$query_terms ) {

      if( false !== ( $parent = get_term_by( 'id', $term->parent, $term->taxonomy ) ) ) {

        if( false !== ( $key = array_search( $parent->slug, $query_terms ) ) ) {
          unset( $query_terms[$key] );
        }

        if( 0 !== $parent->parent ) {
          $this->remove_ancestors( $parent, $query_terms );
        }
      }

      return;
    }
    
    protected function edit_filter_wrapper( &$classes, &$options, &$append ) {
      if( 'ppp' === $this->module ) { $this->input_classes[] = 'awf-no-active-badge'; }
      
      if( ! empty( $this->settings['is_collapsible'] ) ) {
        $classes[] = 'awf-collapsible';
        if( ! empty( $this->settings['collapsed_on'] ) ) { $classes[] = 'awf-collapsed'; }
        $options .= ' tabindex="' . ( 100 + intval( A_W_F::$presets[A_W_F::$front->preset->id]['filters'][$this->id] ) ) . '"';
      }
    }
    
    protected function collapse_btn_html() {
      if( ! empty( $this->settings['is_collapsible'] ) ) {
        return '<div class="awf-collapse-btn"></div>';
      } else {
        return '';
      }
    }
    
    protected function interactive_tooltips_html() { return ''; }
    protected function set_term_label( &$term ) {}
    protected function set_hide_empty( $slug, &$container_classes, &$input_props ) {}
    protected function add_range_slider_container_classes() { return ''; }
    protected function setup_numeric_taxonomy_range() {}

  }
}

?>