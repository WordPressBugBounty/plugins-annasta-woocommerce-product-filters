<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_preset_frontend' ) ) {
  class A_W_F_preset_frontend extends A_W_F_preset {

    public $caller_id;
    public $is_url_query;
    public $filters = array();
    public $counts = array();
    public $sbs_count = 1;
    
    public function __construct( $preset_id, $caller_id = null ) {

      parent::__construct( $preset_id );

      $this->caller_id = $caller_id;
      
      foreach( A_W_F::$presets[$this->id]['filters'] as $filter_id => $filter_position ) {
        $filter = new A_W_F::$front->filters_manager( $this->id, $filter_id );
        $this->filters[$filter->filter_name] = $filter;
      }
    }

    public function get_html() {
      
      $html = '';

      $wrapper_classes = array(
        'awf-preset-wrapper',
        'awf-preset-' . $this->id . '-wrapper',
        'awf-' . $this->layout . '-preset'
      );
      
      $wrapper_options = ' data-preset-id="' . $this->id . '"';;

      switch( $this->type ) {
        case 'url':
          $this->is_url_query = true;
          $wrapper_classes[] = 'awf-url';
          break;
        case 'ajax':
          $wrapper_classes[] = 'awf-ajax';
          break;
        case 'ajax-button':
        case 'ajax-delegated':
          $wrapper_classes[] = 'awf-ajax';
          $wrapper_classes[] = 'awf-button';
          break;
        case 'sbs':
          $wrapper_classes[] = 'awf-sbs';
          $wrapper_classes[] = 'awf-sbs-' . $this->sbs_type;
          if( 'yes' === $this->sbs_next_btn ) { $wrapper_classes[] = 'awf-sbs-next-btn-on'; }
          if( 'yes' === $this->sbs_back_btn ) { $wrapper_classes[] = 'awf-sbs-back-btn-on'; }
          if( ! empty( $this->sbs_redirect ) ) { $wrapper_options .= ' data-sbs-redirect="' . esc_url( $this->sbs_redirect ) . '"'; }
          $wrapper_options .= ' data-sbs-total="' . count( $this->filters ) . '"';
          
          if( 'button' === $this->sbs_submission ) {
            $wrapper_classes[] = 'awf-button';
            
          } elseif( 'button-last' === $this->sbs_submission ) {
            $wrapper_classes[] = 'awf-button';
            $wrapper_classes[] = 'awf-sbs-submit-last';
            
          } elseif( 'instant-last' === $this->sbs_submission ) {
            $wrapper_classes[] = 'awf-sbs-submit-last';
          }
          
          break;
        default:
          $wrapper_classes[] = 'awf-' . sanitize_key( $this->type );
          break;
      }

      switch( $this->display_mode ) {
        case 'togglable-on-s':
          $wrapper_classes[] = 'awf-' . $this->display_mode . '-preset';
          $wrapper_classes[] = 'awf-' . $this->togglable_mode . '-mode';
          if( 'yes' === get_option( 'awf_popup_fix_close_btn', 'no' ) ) { $wrapper_classes[] = 'awf-fix-popup-close-btn'; }
          $wrapper_options .= ' data-responsive-width="' . $this->responsive_width . '"';

          break;

        case 'togglable':

          if( empty( A_W_F::$front->get_access_to['awf-togglable-call'] ) ) { return ''; }

          $wrapper_classes[] = 'awf-' . $this->display_mode . '-preset';
          $wrapper_classes[] = 'awf-' . $this->togglable_mode . '-mode';
          if( 'yes' === get_option( 'awf_popup_fix_close_btn', 'no' ) ) { $wrapper_classes[] = 'awf-fix-popup-close-btn'; }
          
          break;
        default: break;
      }

      $html .= '<div id="' . $this->caller_id . '-preset-' . $this->id . '-wrapper" class="' . implode( ' ', $wrapper_classes ) . '"' . $wrapper_options . '>';
      
      if( in_array( $this->display_mode, array( 'togglable', 'togglable-on-s' ) ) ) {
        $close_label = get_option( 'awf_popup_close_btn_label', __( 'Close filters', 'annasta-filters' ) );

        $html .= '<div class="awf-togglable-preset-close-btn" title="' . esc_attr( $close_label ) . '"><i class="fas fa-times"></i><span>' . esc_html( $close_label ) . '</span></div>';
      }
      
      $html .= '<div class="awf-preset-title">' . esc_html( $this->title ) . '</div>';

      if( 'yes' === $this->show_title_badges ) {
        $html .= '<div class="awf-active-badges-container"></div>';
				
      } else {
				if( 'none' !== $this->reset_btn ) {
					$html .= '<div class="awf-active-badges-container" style="display:none;"></div>';
				}
			}

      if( 'top' === $this->reset_btn || 'both' === $this->reset_btn ) {
        $html .= $this->reset_btn_html( 'top' );
      }

      if( ! empty( $this->description ) ) {
        $html .= '<div class="awf-preset-description">' . esc_html( $this->description ) . '</div>';
      }

      $html .= '<form class="awf-filters-form" action="' . esc_url( A_W_F::$front->current_url ) . '" method="post">';
      
      if( 'sbs' === $this->type && 'yes' === $this->sbs_back_btn ) {
        $html .= '<div class="awf-sbs-back-btn-container"><button type="button" class="awf-sbs-back-btn">' . esc_html__( 'Back', 'annasta-filters' ) . '</button></div>';
      }

      foreach( $this->filters as $filter ) {
        $html .= $filter->get_html();
      }

      if( 'bottom' === $this->reset_btn || 'both' === $this->reset_btn ) {
        $html .= $this->reset_btn_html( 'bottom' );
      }
      
      if( 'ajax-button' === $this->type ) {
        $html .= '<div class="awf-btn-container"><button type="button" class="awf-apply-filter-btn">' . esc_html( $this->filter_btn_label ) . '</button></div>';
        
      } elseif( 'sbs' === $this->type ) {
        $sbs_btns = '';
        
        if( 'yes' === $this->sbs_next_btn ) {
          $sbs_btns .= '<button type="button" class="awf-sbs-next-btn">' . esc_html__( 'Next', 'annasta-filters' ) . '</button>';
        }
        if( 'button' === substr( $this->sbs_submission, 0, 6 ) ) {
          $sbs_btns .= '<button type="button" class="awf-apply-filter-btn">' . esc_html( $this->filter_btn_label ) . '</button>';
        }
        
        if( ! empty( $sbs_btns ) ) { $html .= '<div class="awf-btn-container">' . $sbs_btns . '</div>'; }
        
      } elseif( 'form' === $this->type ) {
        if( ! empty( A_W_F::$front->is_sc_page ) ) {
          $html .= '<input type="hidden" name="awf_sc_page" value="' . A_W_F::$front->is_sc_page . '">';

        } elseif( ! empty( A_W_F::$front->is_archive ) ) {
          $html .= '<input type="hidden" name="awf_archive_page" value="' . A_W_F::$front->vars->tax[A_W_F::$front->is_archive] . '">';

          $insert_archive_tax = true;
          foreach( $this->filters as $filter ) {
            if( 'taxonomy' === $filter->module && A_W_F::$front->is_archive === $filter->settings['taxonomy'] ) {
              $insert_archive_tax = false;
              break;
            }
          }
    
          if( $insert_archive_tax ) {
            $html .= '<input type="hidden" name="' . A_W_F::$front->vars->tax[A_W_F::$front->is_archive] . '[]" value="' . implode( ',', A_W_F::$front->query->tax[A_W_F::$front->is_archive] ) . '">';
          }
        }

        $html .= '<div class="awf-btn-container"><button type="submit" name="awf_submit" value="1" class="awf-form-submit-btn">' . esc_html( $this->filter_btn_label ) . '</button></div>';
      }

      $html .= '</form>';
      $html .= '</div>';
      
      return $html;
    }

    private function reset_btn_html( $position = 'top' ) {
      $html = '<div class="awf-reset-btn-container awf-' . $position . '-reset-btn-container" style="display:none;"><button type="button" title="' . esc_attr__( 'Clear all filters', 'annasta-filters' ) . '" class="awf-reset-btn">' . esc_html( $this->reset_btn_label ) . '</button></div>';

      return $html;
    }

  }
}

?>