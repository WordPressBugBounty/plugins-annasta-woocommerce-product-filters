<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_preset' ) ) {
  
  class A_W_F_preset {

    public $id;
    public $name;
    public $type;
    public $layout;
    public $display_mode;
    public $togglable_mode;
    public $responsive_width;
    public $title;
    public $description;
    public $show_title_badges;
    public $reset_btn;
    public $reset_btn_label;
    public $filter_btn_label;
    public $sbs_type;
    public $sbs_submission;
    public $sbs_next_btn;
    public $sbs_back_btn;
    public $sbs_redirect;

    public function __construct( $preset_id ) {
      $this->id = (int) $preset_id;
      
      $this->name               = get_option( 'awf_preset_' . $preset_id . '_name', 'New Preset' );
      $this->type               = get_option( 'awf_preset_' . $preset_id . '_type', 'ajax' );
      $this->title              = get_option( 'awf_preset_' . $preset_id . '_title', '' );
      $this->description        = get_option( 'awf_preset_' . $preset_id . '_description', '' );
      $this->show_title_badges  = get_option( 'awf_preset_' . $preset_id . '_show_title_badges', 'yes' );
      $this->reset_btn          = get_option( 'awf_preset_' . $preset_id . '_reset_btn', 'top' );
      $this->reset_btn_label    = get_option( 'awf_preset_' . $preset_id . '_reset_btn_label', __( 'Clear all', 'annasta-filters' ) );
      $this->filter_btn_label   = get_option( 'awf_preset_' . $preset_id . '_filter_btn_label', __( 'Filter', 'annasta-filters' ) );
      
      $this->sbs_type           = get_option( 'awf_preset_' . $preset_id . '_sbs_type', 'unhide' );
      $this->sbs_submission     = get_option( 'awf_preset_' . $preset_id . '_sbs_submission', __( 'Filter', 'annasta-filters' ) );
      $this->sbs_next_btn       = get_option( 'awf_preset_' . $preset_id . '_sbs_next_btn', 'no' );
      $this->sbs_back_btn       = get_option( 'awf_preset_' . $preset_id . '_sbs_back_btn', 'no' );
      $this->sbs_redirect       = get_option( 'awf_preset_' . $preset_id . '_sbs_redirect', '' );
      
      $this->layout             = get_option( 'awf_preset_' . $preset_id . '_layout', '1-column' );
      $this->display_mode       = get_option( 'awf_preset_' . $preset_id . '_display_mode', 'visible' );
      $this->togglable_mode     = get_option( 'awf_preset_' . $preset_id . '_togglable_mode', 'left_sidebar' );
      $this->responsive_width   = get_option( 'awf_preset_' . $preset_id . '_responsive_width', '768' );
    }
    
    public function get_preset_options() {
      return get_object_vars( $this );
    }

  }
}
?>