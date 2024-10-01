<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_filter' ) ) {
  
  class A_W_F_filter {
    
    public $id;
    public $preset_id;
    public $name;
    public $module;
    public $default_value = false;
    public $settings;
    
    public $prefix;
    
    public function __construct( $preset_id, $filter_id ) {
      $this->preset_id = (int) $preset_id;
      $this->id = (int) $filter_id;
      
      $this->prefix = self::get_prefix( $this->preset_id, $this->id );
      
      $this->module = sanitize_key( get_option( $this->prefix . 'module', '' ) );
      
      $this->name = get_option( $this->prefix . 'name', '' );
      $this->settings = get_option( $this->prefix . 'settings', array() );
    }
    
    public static function get_prefix( $preset_id, $filter_id ) {
      return 'awf_filter_' . $preset_id . '-' . $filter_id . '_';
    }
    
    public function get_filter_terms( $sort = true ) {

      $terms = array();

      if( 'ppp' === $this->module ) {
        foreach( $this->settings['ppp_values'] as $value => $label ) {
          $label = ( -1 === $value ) ? esc_html( $label ) : esc_html( $value . ' ' . $label );
          $terms[] = ( object ) array( 'slug' => $value, 'name' => $label, 'parent' => 0, 'term_id' => -1 );
        }

        if( ! empty( A_W_F::$front->awf_settings['ppp_default'] ) ) { $this->default_value = A_W_F::$front->awf_settings['ppp_default']; }

      } elseif( 'orderby' === $this->module ) {
        if( 'no' === get_option( 'awf_use_wc_orderby', 'no' ) ) {
          $terms = array(
            ( object ) array( 'slug' => 'menu_order', 'name' => esc_html__( 'Default sorting', 'annasta-filters' ), 'parent' => 0, 'term_id' => -6 ),
            ( object ) array( 'slug' => 'popularity', 'name' => esc_html__( 'Sort by popularity', 'annasta-filters' ), 'parent' => 0, 'term_id' => -1 ),
            ( object ) array( 'slug' => 'rating', 'name' => esc_html__( 'Sort by average rating', 'annasta-filters' ), 'parent' => 0, 'term_id' => -2 ),
            ( object ) array( 'slug' => 'date', 'name' => esc_html__( 'Sort by latest', 'annasta-filters' ), 'parent' => 0, 'term_id' => -3 ),
            ( object ) array( 'slug' => 'price', 'name' => esc_html__( 'Sort by price: low to high', 'annasta-filters' ), 'parent' => 0, 'term_id' => -4 ),
            ( object ) array( 'slug' => 'price-desc', 'name' => esc_html__( 'Sort by price: high to low', 'annasta-filters' ), 'parent' => 0, 'term_id' => -5 ),
          );

          $this->default_value = get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );

        } else {
          $wc_orderby = apply_filters(
            'woocommerce_catalog_orderby',
            array(
              'menu_order' => __( 'Default sorting', 'woocommerce' ),
              'popularity' => __( 'Sort by popularity', 'woocommerce' ),
              'rating'     => __( 'Sort by average rating', 'woocommerce' ),
              'date'       => __( 'Sort by latest', 'woocommerce' ),
              'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
              'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
            )
          );

          $id = -1;

          foreach( $wc_orderby as $slug => $label ) {
            $terms[] = ( object ) array( 'slug' => $slug, 'name' => esc_html( $label ), 'parent' => 0, 'term_id' => $id-- );
          }

          $this->default_value = apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) );
        }
        
      } elseif( 'price' === $this->module || 'rating' === $this->module ) {
        $terms = $this->get_range_type_terms();

      } elseif( 'stock' === $this->module ) {
        $terms = array(
          ( object ) array( 'slug' => 'all', 'name' => esc_html__( 'All', 'annasta-filters' ), 'parent' => 0, 'term_id' => -1 ),
          ( object ) array( 'slug' => 'instock', 'name' => esc_html__( 'In stock', 'annasta-filters' ), 'parent' => 0, 'term_id' => -2 ),
          ( object ) array( 'slug' => 'outofstock', 'name' => esc_html__( 'Out of stock', 'annasta-filters' ), 'parent' => 0, 'term_id' => -3 ),
          ( object ) array( 'slug' => 'onbackorder', 'name' => esc_html__( 'Awaited', 'annasta-filters' ), 'parent' => 0, 'term_id' => -4 ),
        );

        $this->default_value = 'all';

      } elseif( 'featured' === $this->module ) {
        $terms = array(
          ( object ) array( 'slug' => 'yes', 'name' => esc_html__( 'Featured products', 'annasta-filters' ), 'parent' => 0, 'term_id' => -1 ),
        );

      } elseif( 'onsale' === $this->module ) {
        $terms = array(
          ( object ) array( 'slug' => 'yes', 'name' => esc_html__( 'On sale', 'annasta-filters' ), 'parent' => 0, 'term_id' => -1 ),
        );

      } elseif( 'meta' === $this->module ) {
        if( 'range' === $this->settings['type'] ) {
          $terms = $this->get_range_type_terms();
          
        } else {
          global $wpdb;
          $meta_values = $wpdb->get_col(
            $wpdb->prepare( "
              SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
              LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
              WHERE pm.meta_key = '%s' 
              AND meta_value != ''
              AND meta_value != 'a:0:{}'
              AND p.post_status = 'publish'
              ORDER BY pm.meta_value", 
              $this->settings['meta_name']
            ) 
          );
          
          $term_id = -1;
          
          foreach( $meta_values as $mv ) {
            $terms[] = ( object ) array(
              'parent' => 0,
              'term_id' => $term_id,
              'slug' => is_numeric( $mv ) ? (float) $mv : sanitize_title( $mv ),
              'name' => $mv,
            );
            
            $term_id--;
          }
        }
        
      } elseif( 'taxonomy' === $this->module ) {
        
        if( 'range' === $this->settings['type'] && 'taxonomy_range' !== $this->settings['type_options']['range_type'] ) {
          $terms = $this->get_range_type_terms();
          
        } else {
          $terms_query = array( 
            'taxonomy' => $this->settings['taxonomy'], 
            'hide_empty' => false,
            'menu_order' => false,
            'orderby' => 'none',
          );

          if( $sort && isset( $this->settings['sort_by'] ) ) {
            if( 'admin' === $this->settings['sort_by'] ) {
              $terms_query['menu_order'] = true;
              $terms_query['orderby'] = 'name';
            } else {
              $terms_query['orderby'] = $this->settings['sort_by'];
            }
            $terms_query['order'] = strtoupper( $this->settings['sort_order'] );
          }

          $taxonomy_terms = get_terms( $terms_query );
          if( ! is_wp_error( $taxonomy_terms ) ) { 
            $terms = $taxonomy_terms;

            foreach( $terms as $term ) {
              $term->slug = urldecode( $term->slug );
            }
          }
        }

        if( $sort && isset( $this->settings['sort_by'] ) && 'numeric' === $this->settings['sort_by'] ) {
          usort( $terms, function( $a, $b ) {
            if( property_exists( $a, 'slug' ) && property_exists( $b, 'slug' ) ) {
              $ai = (int) $a->slug;
              $bi = (int) $b->slug;
              if ($ai == $bi) { return 0; }

              if( isset( $this->settings['sort_order'] ) && 'desc' === $this->settings['sort_order'] ) {
                return ( $ai > $bi ) ? -1 : 1;
              } else {
                return ( $ai < $bi ) ? -1 : 1;
              }
            }

            return 0;
          });
        }
      }

      return $terms;
    }
    
    protected function get_range_type_terms() {
      $terms = array();
      
      $last_i = count( $this->settings['type_options']['range_values'] ) - 1;
      $decimals = empty( $this->settings['type_options']['decimals'] ) ? 0 : $this->settings['type_options']['decimals'];
      $decimal_separator = wc_get_price_decimal_separator();
      $thousand_separator = wc_get_price_thousand_separator();

      foreach( $this->settings['type_options']['range_values'] as $i => $value ) {
        if( $i < $last_i ) {
          $next_value_i = $i + 1;
          $next_value = 0;

          if( $next_value_i === $last_i ) {
            $next_value = $this->settings['type_options']['range_values'][$next_value_i];
          } else {
            $next_value = $this->settings['type_options']['range_values'][$next_value_i] - $this->settings['type_options']['precision'];
            $next_value = round( $next_value, 2, PHP_ROUND_HALF_UP );
          }

          $formatted_value = number_format( $value, $decimals, $decimal_separator, $thousand_separator );
          $formatted_next_value = number_format( $next_value, $decimals, $decimal_separator, $thousand_separator );
          $prefix = isset( $this->settings['style_options']['value_prefix'] ) ? $this->settings['style_options']['value_prefix'] : '';
          $postfix = isset( $this->settings['style_options']['value_postfix'] ) ? $this->settings['style_options']['value_postfix'] : '';

          if( $formatted_value === $formatted_next_value ) {
            $name = $prefix . $formatted_value . $postfix;
          } else {
            $name = $prefix . $formatted_value . $postfix . ' - ' . $prefix . $formatted_next_value . $postfix;
          }

          $terms[] = ( object ) array( 'slug' => (float) $value, 'name' => $name, 'parent' => 0, 'term_id' => -1*($i+1), 'next_value' => (float) $next_value );
        }
      }
      
      return $terms;
    }
    
    public function get_limited_terms( $sort = true ) {

      $all_terms = $this->get_filter_terms( $sort );
      
      if( empty( $this->settings['terms_limitation_mode'] ) ) {
        return $all_terms;

      } else {
        
        $limited_terms = array();

        switch( $this->settings['terms_limitation_mode'] ) {
          
          case 'active':
            if( ! empty( $this->settings['taxonomy'] ) && ! empty( A_W_F::$front->query->tax[$this->settings['taxonomy']] ) ) {
              $terms_by_id = $active_terms = array();

              foreach( $all_terms as $i => $term ) {
                $terms_by_id[$term->term_id] = $term;
                $terms_by_id[$term->term_id]->active_filter_i = $i;
                if( in_array( $term->slug, A_W_F::$front->query->tax[$this->settings['taxonomy']] ) ) {
                  if( ! empty( $this->settings['style_options']['display_active_filter_siblings'] ) && empty( $term->parent ) ) {
                    $active_terms = $limited_terms = array();
                    break 1;
                  }

                  $active_terms[$term->term_id] = $term;
                  $limited_terms[$term->term_id] = $term;
                }
              }
            }

            if( ! empty( $active_terms ) ) {
              foreach( $active_terms as $term ) {
                $t = $term;
                while( true ) {
                  if( ! empty( $t->parent ) && isset( $terms_by_id[$t->parent] ) ) {
                    $t = $terms_by_id[$t->parent];

                  } else { break; }

                  if( isset( $limited_terms[$t->parent] ) ) {
                    $limited_terms[$t->term_id] = $t;
                    break;
                  } else {
                    $limited_terms[$t->term_id] = $t;
                  }
                }

                if( empty( $this->settings['style_options']['display_active_filter_siblings'] ) ) {
                  $children = get_term_children( $term->term_id, $this->settings['taxonomy'] );

                  if( empty( $children ) && isset( $active_terms[$term->term_id] ) ) {
                    $children = get_term_children( $term->parent, $this->settings['taxonomy'] );
                  }

                } else {
                  if( isset( $active_terms[$term->term_id] ) ) {
                    $children = get_term_children( $term->parent, $this->settings['taxonomy'] );
                  } else {
                    $children = get_term_children( $term->term_id, $this->settings['taxonomy'] );

                    if( empty( $children ) && isset( $active_terms[$term->term_id] ) ) {
                      $children = get_term_children( $term->parent, $this->settings['taxonomy'] );
                    }
                  }
                }

                foreach( $children as $child_id ) {
                  if( ! isset( $limited_terms[$child_id] ) ) {
                    $limited_terms[$child_id] = $terms_by_id[$child_id];
                  }
                }
              }

              usort( $limited_terms, function ( $a, $b ) {
                return $a->active_filter_i - $b->active_filter_i;
              });

              break;
            }

            /* Roll to 'exclude' */
              
          case 'exclude':
            if( empty( $this->settings['excluded_items'] ) ) {
              return $all_terms;

            } else {
              $limited_terms_ids = $this->settings['excluded_items'];

              if( class_exists( 'SitePress' ) ) {
                $limited_terms_ids = $this->settings['excluded_items'] = $this->build_wpml_limited_terms( $limited_terms_ids );
              }

              $terms_by_parent = $this->build_terms_by_parent( $all_terms );
              foreach( $this->settings['excluded_items'] as $term_id ) {
                $limited_terms_ids = array_merge( $limited_terms_ids, $this->build_term_limitation_children( $terms_by_parent, $term_id ) );
              }

              foreach( $all_terms as $term ) {
                if( ! in_array( $term->term_id, $limited_terms_ids ) ) {
                  $limited_terms[] = $term;
                }
              }
            }

            break;

          case 'include':
            if( empty( $this->settings['included_items'] ) ) {
              return $all_terms;

            } else {
              $limited_terms_ids = $this->settings['included_items'];

              if( class_exists( 'SitePress' ) ) {
                $limited_terms_ids = $this->settings['included_items'] = $this->build_wpml_limited_terms( $limited_terms_ids );
              }

              $terms_by_parent = $this->build_terms_by_parent( $all_terms );
              foreach( $this->settings['included_items'] as $term_id ) {
                $limited_terms_ids = array_merge( $limited_terms_ids, $this->build_term_limitation_children( $terms_by_parent, $term_id ) );
              }

              foreach( $all_terms as $term ) {
                if( in_array( $term->term_id, $limited_terms_ids ) ) {
                  $limited_terms[] = $term;
                }
              }
            }

            break;

          default:
            return $all_terms;
            break;
        }

        return $limited_terms;
      }
      
      return $all_terms;
    }
    
    protected function build_term_limitation_children( $terms_by_parent, $parent_id ) {
      $children_ids = array();
			
			if( isset( $terms_by_parent[$parent_id] ) ) {
				foreach( $terms_by_parent[$parent_id] as $term ) {
					$children_ids[] = $term->term_id;
					if( isset( $terms_by_parent[$term->term_id] ) ) {
            $children_ids = array_merge( $children_ids, $this->build_term_limitation_children( $terms_by_parent, $term->term_id ) );
					}
				}
			}

      return $children_ids;
    }
    
    public function build_terms_by_parent( $terms ) {

      $terms_by_parent = array();

      foreach( $terms as $term ) {
        $terms_by_parent[$term->{'parent'}][] = $term;
      }

      return $terms_by_parent;
    }

    protected function build_wpml_limited_terms( $limited_terms_ids ) {
      global $sitepress;
      $sitepress_ids = array();

      if( ! empty( $this->settings['taxonomy'] ) && ( ( $current_language = $sitepress->get_current_language() ) !== $sitepress->get_default_language() ) ) {
        foreach( $limited_terms_ids as $term_id ) {
          $current_lang_id = apply_filters( 'wpml_object_id', $term_id, $this->settings['taxonomy'], FALSE, $current_language );
          if( $current_lang_id ) {
            $sitepress_ids[] = $current_lang_id;
          }
        }
      
      } else {
        return $limited_terms_ids;
      }

      return $sitepress_ids;
    }
    
  }
}

?>