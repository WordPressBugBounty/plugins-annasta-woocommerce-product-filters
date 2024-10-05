<?php
/**
 *	Adds annasta Filters panel with options to the Wordpress Customizer
 *
 *	@since 1.3.0
 */

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_customizer' ) ) {
  
  class A_W_F_customizer {
		
		public $section_titles;
		public static $current_style;
    
    public function __construct( $wp_customizer ) {

			$this->section_titles = A_W_F_admin::get_customizer_sections();

			if( empty( $this->section_titles ) ) {
				return;
			}
			
			foreach( glob( A_W_F_PLUGIN_PATH . "code/customizer-controls/*.php") as $file){
				require $file;
			}
			
			add_action( 'customize_preview_init', array( $this, 'enqueue_preview_scripts' ) );
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'customize_save_after', array( $this, 'generate_css_file' ) );
			
			self::$current_style = get_option( 'awf_custom_style', 'none' );
			
			$this->register_customizer( $wp_customizer );
    }
		
		function register_customizer( $wp_customizer ) {
			
			/* annasta Filters Panel */
			
			$wp_customizer->add_panel( 'annasta-filters', array(
					'title'      => __( 'annasta Filters', 'annasta-filters' ),
					'priority'       => 199,
					'capability'     => 'edit_theme_options',
					'theme_supports' => '',
			) );
			
			/** General Section */

			if( isset( $this->section_titles['awf_general_customizer'] ) ) {

        $wp_customizer->add_section( 'awf_general_customizer', array(
            'title'      	=> $this->section_titles['awf_general_customizer'],
            'panel'      	=> 'annasta-filters',
            'priority'    => 1,
            'capability'  => 'edit_theme_options',
        ) );
        
        $wp_customizer->add_setting( 'awf_custom_style', array(
            'type' => 'option',
            'default'   => 'none',
            'transport' => 'refresh',
            'sanitize_callback' => 'sanitize_key',
            'sanitize_js_callback' 	=> 'sanitize_key',
        ) );
              
        $wp_customizer->add_control( new WP_Customize_Control(
            $wp_customizer,
            'awf_custom_style',
            array(
              'label'          => __( 'Filters style', 'annasta-filters' ),
              'section'        => 'awf_general_customizer',
              'type'           => 'select',
              'choices'        => array( 'none' => __( 'Default', 'annasta-filters' ), 'deprecated-1-3-0' => __( 'Deprecated: no Customizer support', 'annasta-filters' ) )
        ) ) );
        
        $wp_customizer->add_setting( 'awf_customizer_options[awf_preset_color]', array(
            'type' => 'option',
            'default'   => '',
            'transport' => 'postMessage',
            'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
            'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
        ) );
        
        $wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
            $wp_customizer,
            'awf_preset_color',
            array(
              'label'      => __( 'Base font color', 'annasta-filters' ),
              'settings'   => 'awf_customizer_options[awf_preset_color]',
              'section'    => 'awf_general_customizer',
        ) ) );
        
        $wp_customizer->add_setting( 'awf_default_font', array(
            'type' => 'option',
            'default'   => 'inherit',
            'transport' => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        $wp_customizer->add_control( new WP_Customize_Control(
            $wp_customizer,
            'awf_default_font',
            array(
              'label'          => __( 'Filters font', 'annasta-filters' ),
              'section'        => 'awf_general_customizer',
              'type'           => 'select',
              'choices'        => A_W_F_customizer::get_google_fonts_choices()
        ) ) );
        
        $wp_customizer->add_setting( 'awf_default_font_enqueue', array(
            'type' => 'option',
            'default'   => 'yes',
            'transport' => 'refresh',
            'sanitize_callback'    => 'wc_bool_to_string',
            'sanitize_js_callback' => 'wc_string_to_bool',
        ) );
        
        $wp_customizer->add_control( new WP_Customize_Control(
            $wp_customizer,
            'awf_default_font_enqueue',
            array(
              'label'          => __( 'Add the filters font to your site. Uncheck if the selected font is already provided by your theme.', 'annasta-filters' ),
              'section'        => 'awf_general_customizer',
              'type'           => 'checkbox',
        ) ) );
        
        $wp_customizer->add_setting( 'awf_customizer_options[awf_preset_font_size]', array(
            'type' => 'option',
            'default'   => '',
            'transport' => 'postMessage',
            'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
        ) );
        
        $wp_customizer->add_control( new WP_Customize_Control(
            $wp_customizer,
            'awf_preset_font_size',
            array(
              'label'          => __( 'Base font size (px)', 'annasta-filters' ),
              'settings'   		 => 'awf_customizer_options[awf_preset_font_size]',
              'section'        => 'awf_general_customizer',
              'type'           => 'number',
        ) ) );
        
        $wp_customizer->add_setting( 'awf_customizer_options[awf_preset_line_height]', array(
            'type' => 'option',
            'default'   => '',
            'transport' => 'postMessage',
            'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
        ) );
        
        $wp_customizer->add_control( new WP_Customize_Control(
            $wp_customizer,
            'awf_preset_line_height',
            array(
              'label'          => __( 'Base line height (px)', 'annasta-filters' ),
              'settings'   		 => 'awf_customizer_options[awf_preset_line_height]',
              'section'        => 'awf_general_customizer',
              'type'           => 'number',
        ) ) );
        
        $wp_customizer->add_setting( 'awf_pretty_scrollbars', array(
            'type' => 'option',
            'default'   => 'no',
            'transport' => 'refresh',
            'sanitize_callback'    => 'wc_bool_to_string',
            'sanitize_js_callback' => 'wc_string_to_bool',
        ) );
        
        $wp_customizer->add_control( new WP_Customize_Control(
            $wp_customizer,
            'awf_pretty_scrollbars',
            array(
              'label'						=> __( 'In filters with limited height replace the standard browser scrollbars with minimalistic.', 'annasta-filters' ),
              'section'					=> 'awf_general_customizer',
              'type'						=> 'checkbox',
        ) ) );
        
        $wp_customizer->add_setting( 'awf_color_filter_style', array(
            'type' => 'option',
            'default'   => 'square',
            'transport' => 'postMessage',
            'sanitize_callback' => 'sanitize_key',
        ) );
        
        $wp_customizer->add_control( new WP_Customize_Control(
            $wp_customizer,
            'awf_color_filter_style',
            array(
              'label'          => __( 'Color box style', 'annasta-filters' ),
              'section'        => 'awf_general_customizer',
              'type'           => 'select',
              'choices'        => A_W_F_admin::get_color_filter_style_options()
        ) ) );
        
        if( A_W_F::$premium ) {
          $wp_customizer->add_setting( 'awf_image_filter_style', array(
              'type' => 'option',
              'default'   => 'square',
              'transport' => 'postMessage',
              'sanitize_callback' => 'sanitize_key',
          ) );
          
          $wp_customizer->add_control( new WP_Customize_Control(
              $wp_customizer,
              'awf_image_filter_style',
              array(
                'label'          => __( 'Image filter style', 'annasta-filters' ),
                'section'        => 'awf_general_customizer',
                'type'           => 'select',
                'choices'        => A_W_F_premium_admin::get_image_filter_style_options()
          ) ) );
        }
        
        $wp_customizer->add_setting( 'awf_fontawesome_font_enqueue', array(
            'type' => 'option',
            'default'   => 'awf',
            'transport' => 'refresh',
            'sanitize_callback'    => 'sanitize_key',
            'sanitize_js_callback' => 'sanitize_key',
        ) );
        
        $wp_customizer->add_control( new WP_Customize_Control(
            $wp_customizer,
            'awf_fontawesome_font_enqueue',
            array(
              'label'          => __( 'Font Awesome support', 'annasta-filters' ),
              'section'        => 'awf_general_customizer',
              'type'           => 'select',
              'choices'        => A_W_F_admin::get_fontawesome_font_enqueue_options()
        ) ) );
			}
			
      /** "Filters" Button Section */

      if( isset( $this->section_titles['awf_filters_button_customizer'] ) ) {
      
        $wp_customizer->add_section( 'awf_filters_button_customizer', array(
            'title'      => $this->section_titles['awf_filters_button_customizer'],
            'panel'      => 'annasta-filters',
            'priority'    => 2,
            'capability' => 'edit_theme_options',
        ) );
        
        $this->add_section_settings( $wp_customizer, 'awf_filters_button', array(
          'reset_section_button' => '',
          'color' => '',
          'hover_color' => '',
          'font_family' => '',
          'font_size' => '',
          'font_weight' => '',
          'text_transform' => '',
          'font_style_italic' => '',
          'white_space_nowrap' => '',
          'z_index' => '',
          'fixed_position' => '',
          'fixed_position_coordinates' => '',
          'rotation' => '',
          'borders' => '',
          'border_radius' => '',
          'margins' => '',
          'padding' => '',
          'line_height' => '',
          'background_color' => '',
          'hover_background_color' => '',
          'btn_label' => '',
          'hide_icon' => '',
          'icon' => '',
          'custom_icon' => '',
          'icon_size' => '',
          'icon_padding_right' => '',
          'icon_border_right' => '',
        ) );
      }
      
      /** Popup sidebar section */

      if( isset( $this->section_titles['awf_popup_sidebar_customizer'] ) ) {
      
        $wp_customizer->add_section( 'awf_popup_sidebar_customizer', array(
            'title'      => $this->section_titles['awf_popup_sidebar_customizer'],
            'panel'      => 'annasta-filters',
            'priority'    => 3,
            'capability' => 'edit_theme_options',
        ) );
        
        $this->add_section_settings( $wp_customizer, 'awf_popup_sidebar', array(
          'reset_section_button' => '',
          'popup_position' => '',
          'width' => '',
          'animation_duration' => '',
          'background_color' => '',
          'margins' => '',
          'padding' => '',
          'borders' => '',
          'awf_title' => __( 'Close button settings', 'annasta-filters' ),
          'close_btn_label' => '',
          'close_btn_color' => '',
          'close_btn_hover_color' => '',
          'close_btn_font_size' => '',
          'close_btn_icon_size' => '',
          'close_btn_font_weight' => '',
          'close_btn_text_transform' => '',
          'close_btn_text_align' => '',
          'close_btn_margins' => __( 'Button margins (px)', 'annasta-filters' ),
          'close_btn_padding' => __( 'Button padding (px)', 'annasta-filters' ),
          'close_btn_borders' => '',
          'close_btn_background_color' => '',
          'close_btn_hover_background_color' => '',
          'close_btn_rotation' => '',
          'close_btn_fixed_position_coordinates' => '',
          'fix_close_btn' => '',
        ) );

        $wp_customizer->add_setting( 'awf_customizer_options[awf_popup_sidebar_close_btn_fixed_position_small_screens_fix]', array(
            'type' => 'option',
            'default'   => 'no',
            'transport' => 'postMessage',
            'sanitize_callback'    => 'wc_bool_to_string',
            'sanitize_js_callback' => 'wc_string_to_bool',
        ) );
        
        $wp_customizer->add_control( new WP_Customize_Control(
            $wp_customizer,
            'awf_popup_sidebar_close_btn_fixed_position_small_screens_fix',
            array(
              'label'          => __( 'Move fixed button to the top left corner on smaller screens', 'annasta-filters' ),
              'settings'   		 => 'awf_customizer_options[awf_popup_sidebar_close_btn_fixed_position_small_screens_fix]',
              'section'        => 'awf_popup_sidebar_customizer',
              'type'           => 'checkbox',
        ) ) );
      }

      /** Preset Title Section */

      if( isset( $this->section_titles['awf_preset_title_customizer'] ) ) {
      
        $wp_customizer->add_section( 'awf_preset_title_customizer', array(
            'title'      => $this->section_titles['awf_preset_title_customizer'],
            'panel'      => 'annasta-filters',
            'priority'    => 4,
            'capability' => 'edit_theme_options',
        ) );
        
        $this->add_section_settings( $wp_customizer, 'awf_preset_title', array(
          'reset_section_button' => '',
          'color' => '',
          'font_family' => '',
          'font_size' => '',
          'font_weight' => '',
          'text_transform' => '',
          'font_style_italic' => '',
          'white_space_nowrap' => '',
          'text_align' => '',
          'borders' => '',
          'border_radius' => '',
          'margins' => '',
          'padding' => '',
          'line_height' => '',
        ) );
      }
      
      /** Preset Description Section */

      if( isset( $this->section_titles['awf_preset_description_customizer'] ) ) {
        
        $wp_customizer->add_section( 'awf_preset_description_customizer', array(
            'title'      => $this->section_titles['awf_preset_description_customizer'],
            'panel'      => 'annasta-filters',
            'priority'   => 5,
            'capability' => 'edit_theme_options',
        ) );
        
        $this->add_section_settings( $wp_customizer, 'awf_preset_description', array(
          'reset_section_button' => '',
          'color' => '',
          'font_family' => '',
          'font_size' => '',
          'font_weight' => '',
          'text_transform' => '',
          'font_style_italic' => '',
          'text_align' => '',
          'borders' => '',
          'border_radius' => '',
          'margins' => '',
          'padding' => '',
          'line_height' => '',
        ) );
      }
        
			/** Submit Button Section */

      if( isset( $this->section_titles['awf_submit_btn_customizer'] ) ) {

        $wp_customizer->add_section( 'awf_submit_btn_customizer', array(
            'title'      => $this->section_titles['awf_submit_btn_customizer'],
            'panel'      => 'annasta-filters',
            'priority'    => 6,
            'capability' => 'edit_theme_options',
        ) );
        
        $this->add_section_settings( $wp_customizer, 'awf_submit_btn', array(
          'reset_section_button' => '',
          'color' => '',
          'hover_color' => '',
          'font_family' => '',
          'font_size' => '',
          'font_weight' => '',
          'text_transform' => '',
          'font_style_italic' => '',
          'white_space_nowrap' => '',
          'borders' => '',
          'border_radius' => '',
          'margins' => '',
          'padding' => '',
          'line_height' => '',
          'width' => '',
          'background_color' => '',
          'hover_background_color' => '',
        ) );
      }

      /** Active Filters Badges Section */

      if( isset( $this->section_titles['awf_active_badge_customizer'] ) ) {
      
        $wp_customizer->add_section( 'awf_active_badge_customizer', array(
            'title'      => $this->section_titles['awf_active_badge_customizer'],
            'panel'      => 'annasta-filters',
            'priority'    => 7,
            'capability' => 'edit_theme_options',
        ) );
        
        $this->add_section_settings( $wp_customizer, 'awf_active_badge', array(
          'reset_section_button' => '',
          'color' => '',
          'hover_color' => '',
          'font_family' => '',
          'font_size' => '',
          'font_weight' => '',
          'text_transform' => '',
          'font_style_italic' => '',
          'white_space_nowrap' => '',
          'reset_icon_position' => '',
          'justify_content' => '',
          'borders' => '',
          'border_radius' => '',
          'margins' => '',
          'padding' => '',
          'line_height' => '',
        ) );
      }
      
      /** Reset All Button Section */
      
      if( isset( $this->section_titles['awf_reset_btn_customizer'] ) ) {

        $wp_customizer->add_section( 'awf_reset_btn_customizer', array(
            'title'      => $this->section_titles['awf_reset_btn_customizer'],
            'panel'      => 'annasta-filters',
            'priority'    => 8,
            'capability' => 'edit_theme_options',
        ) );
        
        $this->add_section_settings( $wp_customizer, 'awf_reset_btn', array(
          'reset_section_button' => '',
          'color' => '',
          'hover_color' => '',
          'font_family' => '',
          'font_size' => '',
          'font_weight' => '',
          'text_transform' => '',
          'font_style_italic' => '',
          'white_space_nowrap' => '',
          'borders' => '',
          'border_radius' => '',
          'margins' => '',
          'padding' => '',
          'line_height' => '',
          'width' => '',
          'background_color' => '',
          'hover_background_color' => '',
        ) );
      }

      /** Filter Title Section */
      
      if( isset( $this->section_titles['awf_filter_title_customizer'] ) ) {

        $wp_customizer->add_section( 'awf_filter_title_customizer', array(
            'title'      => $this->section_titles['awf_filter_title_customizer'],
            'panel'      => 'annasta-filters',
            'priority'   => 9,
            'capability' => 'edit_theme_options',
        ) );
        
        $this->add_section_settings( $wp_customizer, 'awf_filter_title', array(
          'reset_section_button' => '',
          'color' => '',
          'font_family' => '',
          'font_size' => '',
          'font_weight' => '',
          'text_transform' => '',
          'font_style_italic' => '',
          'white_space_nowrap' => '',
          'collapse_btn_icon' => __( 'Filter collapse icon', 'annasta-filters' ),
          'text_align' => '',
          'borders' => '',
          'border_radius' => '',
          'margins' => '',
          'padding' => '',
          'line_height' => '',
        ) );
      }
      
      /** Dropdown Section */
      
      if( isset( $this->section_titles['awf_dropdown_customizer'] ) ) {

        $wp_customizer->add_section( 'awf_dropdown_customizer', array(
            'title'      => $this->section_titles['awf_dropdown_customizer'],
            'panel'      => 'annasta-filters',
            'priority'   => 10,
            'capability' => 'edit_theme_options',
        ) );

        $this->add_section_settings( $wp_customizer, 'awf_dropdown', array(
          'reset_section_button' => '',
          'color' => '',
          'font_family' => '',
          'font_size' => '',
          'font_weight' => '',
          'text_transform' => '',
          'font_style_italic' => '',
          'white_space_nowrap' => '',
          'collapse_btn_icon' => __( 'Dropdown collapse icon', 'annasta-filters' ),
          'borders' => '',
          'border_radius' => '',
          'margins' => '',
          'padding' => '',
          'background_color' => '',
          'line_height' => '',
          'height' => '',
        ) );
      
        $wp_customizer->add_setting( 'awf_customizer_options[awf_dropdown_filters_container_background_color]', array(
            'type' => 'option',
            'default'   => '',
            'transport' => 'postMessage',
            'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
            'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
        ) );
        
        $wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
            $wp_customizer,
            'awf_dropdown_filters_container_background_color',
            array(
              'label'      => __( 'Dropdown list background color', 'annasta-filters' ),
              'settings'   => 'awf_customizer_options[awf_dropdown_filters_container_background_color]',
              'section'    => 'awf_dropdown_customizer',
        ) ) );
      
        $wp_customizer->add_setting( 'awf_customizer_options[awf_dropdown_filters_container_border_color]', array(
            'type' => 'option',
            'default'   => '',
            'transport' => 'postMessage',
            'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
            'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
        ) );
        
        $wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
            $wp_customizer,
            'awf_dropdown_filters_container_border_color',
            array(
              'label'      => __( 'Dropdown list border color', 'annasta-filters' ),
              'settings'   => 'awf_customizer_options[awf_dropdown_filters_container_border_color]',
              'section'    => 'awf_dropdown_customizer',
        ) ) );
      
        $wp_customizer->add_setting( 'awf_customizer_options[awf_dropdown_filters_container_box_shadow_color]', array(
            'type' => 'option',
            'default'   => '',
            'transport' => 'postMessage',
            'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
            'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
        ) );
        
        $wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
            $wp_customizer,
            'awf_dropdown_filters_container_box_shadow_color',
            array(
              'label'      => __( 'Dropdown list shadow color', 'annasta-filters' ),
              'settings'   => 'awf_customizer_options[awf_dropdown_filters_container_box_shadow_color]',
              'section'    => 'awf_dropdown_customizer',
        ) ) );

      $this->add_section_settings( $wp_customizer, 'awf_dropdown', array(
        'z_index' => 'Dropdown list z-index',
      ) );
				
			}

      /** Filter Label Section */

      if( isset( $this->section_titles['awf_filter_label_customizer'] ) ) {
        
        $wp_customizer->add_section( 'awf_filter_label_customizer', array(
            'title'      => $this->section_titles['awf_filter_label_customizer'],
            'panel'      => 'annasta-filters',
            'priority'   => 11,
            'capability' => 'edit_theme_options',
        ) );

        $this->add_section_settings( $wp_customizer, 'awf_filter_label', array(
          'reset_section_button' => '',
          'color' => '',
          'hover_color' => '',
          'active_color' => '',
          'font_family' => '',
          'font_size' => '',
          'font_weight' => '',
          'text_transform' => '',
          'font_style_italic' => '',
          'white_space_nowrap' => '',
          'borders' => '',
          'border_radius' => '',
          'margins' => '',
          'padding' => '',
          'line_height' => '',
        ) );
      }
      
      /** Custom Icons Section */

      if( isset( $this->section_titles['awf_icons_customizer'] ) ) {

        $wp_customizer->add_section( 'awf_icons_customizer', array(
            'title'      => $this->section_titles['awf_icons_customizer'],
            'panel'      => 'annasta-filters',
            'priority'   => 12,
            'capability' => 'edit_theme_options',
        ) );

        $this->add_section_settings( $wp_customizer, 'awf_icons', array(
          'reset_section_button' => '',
          'color' => '',
          'hover_color' => '',
          'font_size' => '',
          'margins' => '',
          'line_height' => '',
        ) );
      }
      
      /** String Search Section */

      if( isset( $this->section_titles['awf_search_customizer'] ) ) {

        $wp_customizer->add_section( 'awf_search_customizer', array(
            'title'      => $this->section_titles['awf_search_customizer'],
            'panel'      => 'annasta-filters',
            'priority'   => 13,
            'capability' => 'edit_theme_options',
        ) );

        $this->add_section_settings( $wp_customizer, 'awf_search', array(
          'reset_section_button' => '',
          'color' => '',
          'font_size' => '',
          'icon_size' => __( 'Icons size (em relative to font size)', 'annasta-filters' ),
          'icon_color' => __( 'Icons color', 'annasta-filters' ),
          'borders' => '',
          'border_radius' => '',
          'height' => '',
          'background_color' => '',
          'awf_title' => __( 'Autocomplete settings', 'annasta-filters' ),
          'ac_color' => __( 'Base font color', 'annasta-filters' ),
          'ac_font_size' => __( 'Base font size (px)', 'annasta-filters' ),
          'ac_width' => __( 'Minimum width', 'annasta-filters' ),
          'ac_width_units' => '',
          'ac_background_color' => '',
        ) );
      }
      
      /** Sliders Section */

      if( isset( $this->section_titles['awf_sliders_customizer'] ) ) {

        $wp_customizer->add_section( 'awf_sliders_customizer', array(
            'title'      => $this->section_titles['awf_sliders_customizer'],
            'panel'      => 'annasta-filters',
            'priority'   => 14,
            'capability' => 'edit_theme_options',
        ) );

        $this->add_section_settings( $wp_customizer, 'awf_sliders', array(
          'reset_section_button' => '',
        ) );
        
        $wp_customizer->add_setting( 'awf_range_slider_style', array(
            'type'							=> 'option',
            'default'						=> 'minimalistic',
            'transport'					=> 'postMessage',
            'sanitize_callback' => 'sanitize_key',
        ) );

        $wp_customizer->add_control( new WP_Customize_Control(
            $wp_customizer,
            'awf_range_slider_style',
            array(
              'label'          	=> __( 'Range slider style', 'annasta-filters' ),
              'section'        	=> 'awf_sliders_customizer',
              'type'           	=> 'select',
              'choices'        	=> A_W_F_admin::get_range_slider_style_options()
        ) ) );

        $this->add_section_settings( $wp_customizer, 'awf_sliders', array(
          'sf_color' => __( 'Font color', 'annasta-filters' ),
          'sf_size' => __( 'Font size (px)', 'annasta-filters' ),
          'width' => __( 'Slider width (%)', 'annasta-filters' ),
          'sb_color' => __( 'Base color', 'annasta-filters' ),
          'slider_color' => __( 'Slider color', 'annasta-filters' ),
          'sh_color' => __( 'Handles color', 'annasta-filters' ),
          'sp_color' => __( 'Poles color', 'annasta-filters' ),
          'st_color' => __( 'Tooltips font color', 'annasta-filters' ),
          'st_background' => __( 'Tooltips background', 'annasta-filters' ),
        ) );
      }
    }
		
		protected function add_section_settings( $wp_customizer, $section, $settings = array() ) {

      foreach( $settings as $setting => $label ) {

        $subsection = '';
        $awf_title_count = 1;

				switch( $setting ) {
          case 'close_btn_font_size':
            $setting = 'font_size';
            $subsection = '_close_btn';

            break;

          case 'ac_font_size':
            $setting = 'font_size';
            $subsection = '_ac';

            break;

          case 'ac_color':
            $setting = 'color';
            $subsection = '_ac';

            break;

          case 'sf_size':
            $setting = 'font_size';
            $subsection = '_slider';

            break;
  
          case 'sf_color':
            $setting = 'color';
            $subsection = '_sf';

            break;

          case 'sb_color':
            $setting = 'color';
            $subsection = '_sb';

            break;

          case 'slider_color':
            $setting = 'color';
            $subsection = '_slider';

            break;

          case 'sh_color':
            $setting = 'color';
            $subsection = '_sh';

            break;

          case 'sp_color':
            $setting = 'color';
            $subsection = '_sp';

            break;

          case 'st_color':
            $setting = 'color';
            $subsection = '_st';

            break;

          case 'st_background':
            $setting = 'color';
            $subsection = '_st_background';

            break;

          case 'ac_background_color':
            $setting = 'background_color';
            $subsection = '_ac';

            break;

          case 'close_btn_color':
            $setting = 'color';
            $subsection = '_close_btn';

            break;

          case 'close_btn_hover_color':
            $setting = 'hover_color';
            $subsection = '_close_btn';

            break;

          case 'close_btn_font_weight':
            $setting = 'font_weight';
            $subsection = '_close_btn';

            break;

          case 'close_btn_text_transform':
            $setting = 'text_transform';
            $subsection = '_close_btn';

            break;

          case 'close_btn_icon_size':
            $setting = 'icon_size';
            $subsection = '_close_btn';

            break;

          case 'close_btn_fixed_position_coordinates':
            $setting = 'fixed_position_coordinates';
            $subsection = '_close_btn';

            break;

          case 'close_btn_text_align':
            $setting = 'text_align';
            $subsection = '_close_btn';

            break;

          case 'close_btn_borders':
            $setting = 'borders';
            $subsection = '_close_btn';

            break;

          case 'close_btn_margins':
            $setting = 'margins';
            $subsection = '_close_btn';

            break;

          case 'close_btn_padding':
            $setting = 'padding';
            $subsection = '_close_btn';

            break;

          case 'ac_width':
            $setting = 'width';
            $subsection = '_ac';

            break;

          case 'close_btn_background_color':
            $setting = 'background_color';
            $subsection = '_close_btn';

            break;

          case 'close_btn_hover_background_color':
            $setting = 'hover_background_color';
            $subsection = '_close_btn';

            break;

          case 'close_btn_rotation':
            $setting = 'rotation';
            $subsection = '_close_btn';

            break;

          case 'ac_width_units':
            $setting = 'width_units';
            $subsection = '_ac';

            break;

          default: break;
        }

				switch( $setting ) {
					case 'reset_section_button':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_reset_section_button]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => 'sanitize_text_field',
						) );
									
						$wp_customizer->add_control( new A_W_F_customizer_control_reset_section_button(
								$wp_customizer,
								$section . '_reset_section_button',
								array(
									'settings'   		 => 'awf_customizer_options[' . $section . '_reset_section_button]',
									'section'        => $section . '_customizer',
						) ) );

						break;

					case 'color':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_color]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
								'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
						) );

						$wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
								$wp_customizer,
								$section . $subsection . '_color',
								array(
									'label'      => empty( $label ) ? __( 'Font Color', 'annasta-filters' ) : $label,
									'settings'   => 'awf_customizer_options[' . $section . $subsection . '_color]',
									'section'    => $section . '_customizer',
						) ) );

						break;

					case 'hover_color':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_hover_color]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
								'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
						) );
						
						$wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
								$wp_customizer,
								$section . $subsection . '_hover_color',
								array(
									'label'      => empty( $label ) ? __( 'Font color on hover', 'annasta-filters' ) : $label,
									'settings'   => 'awf_customizer_options[' . $section . $subsection . '_hover_color]',
									'section'    => $section . '_customizer',
						) ) );

						break;

					case 'active_color':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_active_color]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
								'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
						) );
						
						$wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
								$wp_customizer,
								$section . '_active_color',
								array(
									'label'      => __( 'Active filter color', 'annasta-filters' ),
									'settings'   => 'awf_customizer_options[' . $section . '_active_color]',
									'section'    => $section . '_customizer',
						) ) );

						break;

					case 'font_family':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_font_family]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => 'sanitize_text_field',
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_font_family',
								array(
									'label'          => __( 'Font family', 'annasta-filters' ),
									'description'    => __( 'Enter a valid font (or comma-separated multiple fonts), enqueued or imported via CSS stylesheet.', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_font_family]',
									'section'        => $section . '_customizer',
									'type'           => 'text',
						) ) );

						break;
  
					case 'font_size':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_font_size]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_font_size',
								array(
									'label'          => empty( $label ) ? __( 'Font size (px)', 'annasta-filters' ) : $label,
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_font_size]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );

						break;

					case 'font_weight':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_font_weight]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_font_weight',
								array(
									'label'          => __( 'Font weight', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_font_weight]',
									'section'        => $section . '_customizer',
									'type'           => 'select',
									'choices'				 => self::get_font_weight_choices()
						) ) );

						break;

					case 'text_transform':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_text_transform]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => 'sanitize_key',
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_text_transform',
								array(
									'label'          => __( 'Font style', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_text_transform]',
									'section'        => $section . '_customizer',
									'type'           => 'select',
									'choices'				 => self::get_text_transform_choices()
						) ) );

						break;

					case 'font_style_italic':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_font_style_italic]', array(
								'type' => 'option',
								'default'   => 'no',
								'transport' => 'postMessage',
								'sanitize_callback'    => 'wc_bool_to_string',
								'sanitize_js_callback' => 'wc_string_to_bool',
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_font_style_italic',
								array(
									'label'          => __( 'Italic', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_font_style_italic]',
									'section'        => $section . '_customizer',
									'type'           => 'checkbox',
						) ) );

						break;

					case 'white_space_nowrap':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_white_space_nowrap]', array(
								'type' => 'option',
								'default'   => 'no',
								'transport' => 'postMessage',
								'sanitize_callback'    => 'wc_bool_to_string',
								'sanitize_js_callback' => 'wc_string_to_bool',
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_white_space_nowrap',
								array(
									'label'          => __( 'Force one-line display', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_white_space_nowrap]',
									'section'        => $section . '_customizer',
									'type'           => 'checkbox',
						) ) );

						break;

          case 'btn_label':
                  
            $wp_customizer->add_setting( 'awf_toggle_btn_label', array(
                'type'							=> 'option',
                'default'						=> get_option( 'awf_toggle_btn_label', __( 'Filters', 'annasta-filters' ) ),
                'transport'					=> 'postMessage',
                'sanitize_callback' => 'sanitize_text_field',
            ) );
            
            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                'awf_toggle_btn_label',
                array(
                  'label'          	=> __( 'Toggle button label', 'annasta-filters' ),
                  'description'    => __( 'Leave blank to display the icon', 'annasta-filters' ),
                  'section'        	=> $section . '_customizer',
                  'type'           	=> 'text',
            ) ) );

            break;

          case 'icon':
            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_icon]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback' => 'sanitize_key',
            ) );
            
            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                $section . '_icon',
                array(
                  'label'          => __( 'Icon', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . '_icon]',
                  'section'        => $section . '_customizer',
                  'type'           => 'select',
                  'choices'				 => self::get_toggle_button_icon_choices()
            ) ) );

            break;

          case 'custom_icon':
            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_custom_icon]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback' => 'sanitize_text_field',
            ) );
            
            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                $section . '_custom_icon',
                array(
                  'label'          => __( 'Custom icon', 'annasta-filters' ),
                  'description'    => __( 'Enter the unicode of any solid Font Awesome 5 icon to override the icon setting (example: f142)', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . '_custom_icon]',
                  'section'        => $section . '_customizer',
                  'type'           => 'text',
            ) ) );

            break;

          case 'hide_icon':
            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_hide_icon]', array(
                'type' => 'option',
                'default'   => 'no',
                'transport' => 'postMessage',
                'sanitize_callback'    => 'wc_bool_to_string',
                'sanitize_js_callback' => 'wc_string_to_bool',
            ) );
            
            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                $section . '_hide_icon',
                array(
                  'label'          => __( 'Hide icon', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . '_hide_icon]',
                  'section'        => $section . '_customizer',
                  'type'           => 'checkbox',
            ) ) );

            break;

          case 'icon_size':
            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_icon_size]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_float_or_empty' ),
            ) );
            
            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                $section . $subsection . '_icon_size',
                array(
                  'label'          => ( empty( $label ) ) ? __( 'Icon size (em relative to font size)', 'annasta-filters' ) : $label,
                  'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_icon_size]',
                  'section'        => $section . '_customizer',
                  'type'           => 'number',
            ) ) );

            break;

          case 'icon_color':
            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_icon_color]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
                'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
            ) );
            
            $wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
              $wp_customizer,
              $section . $subsection . '_icon_color',
              array(
                'label'      => ( empty( $label ) ) ? __( 'Icon color', 'annasta-filters' ) : $label,
                'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_icon_color]',
                'section'        => $section . '_customizer',
            ) ) );

            break;

          case 'icon_padding_right':
            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_icon_padding_right]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
            ) );
            
            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                $section . '_icon_padding_right',
                array(
                  'label'          => __( 'Icon padding right (px)', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . '_icon_padding_right]',
                  'section'        => $section . '_customizer',
                  'type'           => 'number',
            ) ) );

            break;

          case 'awf_title':
            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_awf_title_' . $awf_title_count . ']', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback' => 'sanitize_text_field',
            ) );

            $wp_customizer->add_control( new A_W_F_customizer_control_title(
                $wp_customizer,
                $section . '_awf_title_' . $awf_title_count,
                array(
                  'label'          => $label,
                  'settings'   		 => 'awf_customizer_options[' . $section . '_awf_title_' . $awf_title_count . ']',
                  'section'        => $section . '_customizer',
                  'type'           => 'awf-title',
            ) ) );

            $awf_title_count++;

            break;

          case 'icon_border_right':
            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_icon_border_right_awf_title]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback' => 'sanitize_text_field',
            ) );

            $wp_customizer->add_control( new A_W_F_customizer_control_title(
                $wp_customizer,
                $section . '_icon_border_right_awf_title',
                array(
                  'label'          => __( 'Icon border right', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . '_icon_border_right_awf_title]',
                  'section'        => $section . '_customizer',
                  'type'           => 'awf-title',
            ) ) );

            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_icon_border_right_color]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
                'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
            ) );

            $wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
                $wp_customizer,
                $section . '_icon_border_right_color',
                array(
                  'label'      => __( 'Color', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . '_icon_border_right_color]',
                  'section'    => $section . '_customizer',
            ) ) );

            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_icon_border_right_width]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
            ) );

            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                $section . '_icon_border_right_width',
                array(
                  'label'          => __( 'Width', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . '_icon_border_right_width]',
                  'section'        => $section . '_customizer',
                  'type'           => 'number',
            ) ) );

            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_icon_border_right_style]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback' => 'sanitize_key',
            ) );

            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                $section . '_icon_border_right_style',
                array(
                  'label'          => __( 'Style', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . '_icon_border_right_style]',
                  'section'        => $section . '_customizer',
                  'type'           => 'select',
                  'choices'				 => self::get_border_style_choices()
            ) ) );

            break;

            case 'popup_position':
              $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_popup_position]', array(
                  'type' => 'option',
                  'default'   => 'left',
                  'transport' => 'postMessage',
                  'sanitize_callback' => 'sanitize_key',
              ) );
              
              $wp_customizer->add_control( new WP_Customize_Control(
                  $wp_customizer,
                  $section . '_popup_position',
                  array(
                    'label'          => __( 'Sidebar position', 'annasta-filters' ),
                    'settings'   		 => 'awf_customizer_options[' . $section . '_popup_position]',
                    'section'        => $section . '_customizer',
                    'type'           => 'select',
                    'choices'				 => array( 'left' => __( 'Left', 'annasta-filters' ), 'right' => __( 'Right', 'annasta-filters' ) )
              ) ) );
  
              break;

            case 'animation_duration':
            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_animation_duration]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
            ) );
            
            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                $section . '_animation_duration',
                array(
                  'label'          => __( 'Animation duration (milliseconds)', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . '_animation_duration]',
                  'section'        => $section . '_customizer',
                  'type'           => 'number',
            ) ) );
  
              break;
  
          case 'close_btn_label':
                  
            $wp_customizer->add_setting( 'awf_popup_close_btn_label', array(
                'type'							=> 'option',
                'default'						=> get_option( 'awf_popup_close_btn_label', __( 'Close filters', 'annasta-filters' ) ),
                'transport'					=> 'postMessage',
                'sanitize_callback' => 'sanitize_text_field',
            ) );
            
            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                'awf_popup_close_btn_label',
                array(
                  'label'          	=> __( 'Button label', 'annasta-filters' ),
                  'description'    => __( 'Leave blank to display only the icon', 'annasta-filters' ),
                  'section'        	=> $section . '_customizer',
                  'type'           	=> 'text',
            ) ) );

            break;

          case 'fix_close_btn':
						$wp_customizer->add_setting( 'awf_popup_fix_close_btn', array(
                'type' => 'option',
                'default'   => get_option( 'awf_popup_fix_close_btn', 'no' ),
                'transport' => 'postMessage',
                'sanitize_callback'    => 'wc_bool_to_string',
                'sanitize_js_callback' => 'wc_string_to_bool',
            ) );
            
            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                'awf_popup_fix_close_btn',
                array(
                  'label'          => __( 'Fix close button page position', 'annasta-filters' ),
                  'description'   => __( 'Publish for correct previews', 'annasta-filters' ),
                  'section'        => $section . '_customizer',
                  'type'           => 'checkbox',
            ) ) );

            break;
    
					case 'z_index':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_z_index]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_z_index',
								array(
									'label'          => ( empty( $label ) ) ? __( 'Z-index', 'annasta-filters' ) : $label,
									'settings'   		 => 'awf_customizer_options[' . $section . '_z_index]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );

						break;

					case 'fixed_position':

						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_fixed_position]', array(
								'type' => 'option',
								'default'   => 'no',
								'transport' => 'postMessage',
								'sanitize_callback'    => 'wc_bool_to_string',
								'sanitize_js_callback' => 'wc_string_to_bool',
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_fixed_position',
								array(
									'label'          => __( 'Fix page position', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_fixed_position]',
									'section'        => $section . '_customizer',
									'type'           => 'checkbox',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_fixed_from]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_fixed_from',
								array(
									'label'          => __( 'Fix position starting with device width', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_fixed_from]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_fixed_till]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_fixed_till',
								array(
									'label'          => __( 'Fix position up to device width', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_fixed_till]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );

            break;

          case 'fixed_position_coordinates':

						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_fixed_position_awf_title]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback' => 'sanitize_text_field',
            ) );
            
            $wp_customizer->add_control( new A_W_F_customizer_control_title(
                $wp_customizer,
                $section . $subsection . '_fixed_position_awf_title',
                array(
                  'label'          => __( 'Button position (px)', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_fixed_position_awf_title]',
                  'section'        => $section . '_customizer',
                  'type'           => 'awf-title',
            ) ) );

						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_fixed_top]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_fixed_top',
								array(
									'label'          => __( 'Top', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_fixed_top]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_fixed_right]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_fixed_right',
								array(
									'label'          => __( 'Right', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_fixed_right]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_fixed_bottom]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_fixed_bottom',
								array(
									'label'          => __( 'Bottom', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_fixed_bottom]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_fixed_left]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_fixed_left',
								array(
									'label'          => __( 'Left', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_fixed_left]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );

						break;
	
					case 'collapse_btn_icon':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_collapse_btn_icon]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => 'sanitize_text_field',
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_collapse_btn_icon',
								array(
									'label'          => empty( $label ) ? __( 'Collapse icon', 'annasta-filters' ) : $label,
									'settings'   		 => 'awf_customizer_options[' . $section . '_collapse_btn_icon]',
									'section'        => $section . '_customizer',
									'type'           => 'select',
									'choices'				 => self::get_collapse_icon_choices()
						) ) );

						break;

					case 'reset_icon_position':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_reset_icon_position]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => 'sanitize_key',
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_reset_icon_position',
								array(
									'label'          => __( 'Reset icon position', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_reset_icon_position]',
									'section'        => $section . '_customizer',
									'type'           => 'select',
									'choices'				 => array( '' => __( 'Right (default)', 'annasta-filters' ), 'row' => __( 'Left', 'annasta-filters' ) )
						) ) );

						break;

					case 'justify_content':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_justify_content]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => 'sanitize_key',
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_justify_content',
								array(
									'label'          => __( 'Badge alignment', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_justify_content]',
									'section'        => $section . '_customizer',
									'type'           => 'select',
									'choices'				 => array( '' => __( 'Justify (default)', 'annasta-filters' ), 'center' => __( 'Center', 'annasta-filters' ) )
						) ) );

						break;

					case 'text_align':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_text_align]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => 'sanitize_key',
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_text_align',
								array(
									'label'          => __( 'Text alignment', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_text_align]',
									'section'        => $section . '_customizer',
									'type'           => 'select',
									'choices'				 => self::get_text_align_choices()
						) ) );

						break;

					case 'borders':
						$borders_titles = array( 'top' => __( 'Top border', 'annasta-filters' ), 'right' => __( 'Right border', 'annasta-filters' ), 'bottom' => __( 'Bottom border', 'annasta-filters' ), 'left' => __( 'Left border', 'annasta-filters' ) );
						
						foreach( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
							$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_border_' . $side . '_awf_title]', array(
									'type' => 'option',
									'default'   => '',
									'transport' => 'postMessage',
									'sanitize_callback' => 'sanitize_text_field',
							) );

							$wp_customizer->add_control( new A_W_F_customizer_control_title(
									$wp_customizer,
									$section . $subsection . '_border_' . $side . '_awf_title',
									array(
										'label'          => $borders_titles[$side],
										'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_border_' . $side . '_awf_title]',
										'section'        => $section . '_customizer',
										'type'           => 'awf-title',
							) ) );

							$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_border_' . $side . '_color]', array(
									'type' => 'option',
									'default'   => '',
									'transport' => 'postMessage',
									'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
									'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
							) );

							$wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
									$wp_customizer,
									$section . $subsection . '_border_' . $side . '_color',
									array(
										'label'      => __( 'Color', 'annasta-filters' ),
										'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_border_' . $side . '_color]',
										'section'    => $section . '_customizer',
							) ) );

							$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_border_' . $side . '_width]', array(
									'type' => 'option',
									'default'   => '',
									'transport' => 'postMessage',
									'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
							) );

							$wp_customizer->add_control( new WP_Customize_Control(
									$wp_customizer,
									$section . $subsection . '_border_' . $side . '_width',
									array(
										'label'          => __( 'Width', 'annasta-filters' ),
										'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_border_' . $side . '_width]',
										'section'        => $section . '_customizer',
										'type'           => 'number',
							) ) );

							$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_border_' . $side . '_style]', array(
									'type' => 'option',
									'default'   => '',
									'transport' => 'postMessage',
									'sanitize_callback' => 'sanitize_key',
							) );

							$wp_customizer->add_control( new WP_Customize_Control(
									$wp_customizer,
									$section . $subsection . '_border_' . $side . '_style',
									array(
										'label'          => __( 'Style', 'annasta-filters' ),
										'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_border_' . $side . '_style]',
										'section'        => $section . '_customizer',
										'type'           => 'select',
										'choices'				 => self::get_border_style_choices()
							) ) );
							
						}

						break;

					case 'border_radius':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_border_radius]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );
									
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_border_radius',
								array(
									'label'          => __( 'Border radius (px)', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_border_radius]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						break;

					case 'margins':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_margins_awf_title]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => 'sanitize_text_field',
						) );
						
						$wp_customizer->add_control( new A_W_F_customizer_control_title(
								$wp_customizer,
								$section . $subsection . '_margins_awf_title',
								array(
									'label'          => empty( $label ) ? __( 'Margins (px)', 'annasta-filters' ) : $label,
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_margins_awf_title]',
									'section'        => $section . '_customizer',
									'type'           => 'awf-title',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_margin_top]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_margin_top',
								array(
									'label'          => __( 'Top', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_margin_top]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_margin_right]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_margin_right',
								array(
									'label'          => __( 'Right', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_margin_right]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_margin_bottom]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_margin_bottom',
								array(
									'label'          => __( 'Bottom', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_margin_bottom]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_margin_left]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_margin_left',
								array(
									'label'          => __( 'Left', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_margin_left]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );

						break;

					case 'padding':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_padding_awf_title]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' => 'sanitize_text_field',
						) );
						
						$wp_customizer->add_control( new A_W_F_customizer_control_title(
								$wp_customizer,
								$section . $subsection . '_padding_awf_title',
								array(
									'label'          => empty( $label ) ? __( 'Padding (px)', 'annasta-filters' ) : $label,
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_padding_awf_title]',
									'section'        => $section . '_customizer',
									'type'           => 'awf-title',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_padding_top]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_padding_top',
								array(
									'label'          => __( 'Top', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_padding_top]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_padding_right]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_padding_right',
								array(
									'label'          => __( 'Right', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_padding_right]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_padding_bottom]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_padding_bottom',
								array(
									'label'          => __( 'Bottom', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_padding_bottom]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );
						
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_padding_left]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_padding_left',
								array(
									'label'          => __( 'Left', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_padding_left]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );

						break;

					case 'height':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_height]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_height',
								array(
									'label'          => __( 'Height (px)', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_height]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );

						break;

					case 'line_height':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . '_line_height]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );
						
						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . '_line_height',
								array(
									'label'          => __( 'Line height (px)', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . '_line_height]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );

						break;

          case 'width_units':

            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_width_units]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback' => 'sanitize_key',
            ) );
            
            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                $section . $subsection . '_width_units',
                array(
                  'label'          => empty( $label ) ? '&nbsp;' : $label,
                  'description'    => '&nbsp;',
                  'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_width_units]',
                  'section'        => $section . '_customizer',
                  'type'           => 'select',
                  'choices'				 => array( '' => '%', 'px' => 'px' )
            ) ) );

            break;
            
					case 'width':
            $units = '%';

            if( 'awf_popup_sidebar' === $section ) {
              $units = 'px';
            }

						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_width]', array(
								'type' => 'option',
								'default'   => '',
								'transport' => 'postMessage',
								'sanitize_callback' 		=> array( 'A_W_F_customizer', 'sanitize_absint_or_empty' ),
						) );

						$wp_customizer->add_control( new WP_Customize_Control(
								$wp_customizer,
								$section . $subsection . '_width',
								array(
									'label'          => empty( $label ) ? sprintf( __( 'Width (%s)', 'annasta-filters' ), $units ) : $label,
									'description'    => __( 'Leave blank for auto width', 'annasta-filters' ),
									'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_width]',
									'section'        => $section . '_customizer',
									'type'           => 'number',
						) ) );

						break;

					case 'background_color':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_background_color]', array(
								'type' 									=> 'option',
								'default'   						=> '',
								'transport' 						=> 'postMessage',
								'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
								'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
						) );

						$wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
								$wp_customizer,
								$section . $subsection . '_background_color',
								array(
									'label'      		=> __( 'Background color', 'annasta-filters' ),
									'description'   => ( in_array( $section, array( 'awf_submit_btn', 'awf_reset_btn' ) ) ? __( 'When resetting this field, push Publish button to see the changes. For transparent background set to rgba(0,0,0,0).', 'annasta-filters' ) : '' ),
									'settings'   		=> 'awf_customizer_options[' . $section . $subsection . '_background_color]',
									'section'    		=> $section . '_customizer',
									'show_opacity'  => true,
						) ) );

						break;
  
					case 'hover_background_color':
						$wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_hover_background_color]', array(
								'type' 									=> 'option',
								'default'   						=> '',
								'transport' 						=> 'postMessage',
								'sanitize_callback' 		=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
								'sanitize_js_callback' 	=> array( 'A_W_F_admin', 'sanitize_hex_rgba_color' ),
						) );

						$wp_customizer->add_control( new A_W_F_customizer_control_alpha_color_control(
								$wp_customizer,
								$section . $subsection . '_hover_background_color',
								array(
									'label'      		=> __( 'Background color on hover', 'annasta-filters' ),
									'description'   => __( 'When resetting this field, push Publish button to see the changes.', 'annasta-filters' ),
									'settings'   		=> 'awf_customizer_options[' . $section . $subsection . '_hover_background_color]',
									'section'    		=> $section . '_customizer',
									'show_opacity'  => true,
						) ) );

						break;

          case 'rotation':

            $wp_customizer->add_setting( 'awf_customizer_options[' . $section . $subsection . '_rotation]', array(
                'type' => 'option',
                'default'   => '',
                'transport' => 'postMessage',
                'sanitize_callback'		 => array( 'A_W_F_customizer', 'sanitize_int_or_empty' ),
            ) );

            $wp_customizer->add_control( new WP_Customize_Control(
                $wp_customizer,
                $section . $subsection . '_rotation',
                array(
                  'label'          => __( 'Rotation', 'annasta-filters' ),
                  'settings'   		 => 'awf_customizer_options[' . $section . $subsection . '_rotation]',
                  'section'        => $section . '_customizer',
                  'type'           => 'number',
            ) ) );

            break;

					default: break;
				}

			}
			
		}
				
		function enqueue_preview_scripts() {
			wp_enqueue_style( 'awf-customizer-preview', A_W_F_PLUGIN_URL . '/styles/awf-customizer-preview.css', false, A_W_F::$plugin_version );
			wp_enqueue_script( 'awf-customizer-preview', A_W_F_PLUGIN_URL . '/code/js/awf-customizer-preview.js', array( 'jquery' ), A_W_F::$plugin_version, true );
			wp_localize_script( 'awf-customizer-preview', 'awf_customizer_preview_data', array(
				'i18n' => array( 'awf_focus_panel_button_label' => __( 'Click to customize annasta Filters', 'annasta-filters' ) ),
			) );
		}
		
		function enqueue_scripts() {
			wp_enqueue_style( 'awf-customizer', A_W_F_PLUGIN_URL . '/styles/awf-customizer.css', false, A_W_F::$plugin_version );
			wp_enqueue_script( 'awf-customizer', A_W_F_PLUGIN_URL . '/code/js/awf-customizer.js', array( 'jquery' ), A_W_F::$plugin_version, true );
			wp_localize_script( 'awf-customizer', 'awf_customizer_data', array(
				'awf_ajax_referer' => wp_create_nonce( 'awf_ajax_nonce' ),
				'i18n' => array(
					'awf_notification' => __( 'Please edit only the options that you need changed. Leave the rest blank or set to default.', 'annasta-filters' ),
					'awf_custom_style_notification' => __( 'Your current annasta Filters style does not provide Customizer support. To enable customization, go to annasta Filters > General > Filters style and select a supported style.', 'annasta-filters' ),
					'awf_custom_style_control_notification' => __( 'To enable filters customization please select "Default" or other supported style and press the Publish button.', 'annasta-filters' ),
					'awf_publish_to_preview_notification' => __( 'Publish for correct previews!', 'annasta-filters' ),
				),
			) );
		}
		
		public function header_css() {
			$customizer_default_font = get_option( 'awf_default_font' );
			if( ! in_array( $customizer_default_font, array( '', 'inherit' ) ) ) {
    ?>
         <style type="text/css">
             <?php echo '.awf-preset-wrapper{font-family:' , $customizer_default_font , ';}'; ?>
         </style>
    <?php
				
			}
		}
		
    public function generate_css_file() {
			if( ! empty( A_W_F::$admin ) ) { A_W_F::$admin->generate_styles_css(); }
		}

		public static function sanitize_int_or_empty( $value ) {
			if( '' === $value ) { return $value; }
			else { return intval( $value ); }
		}

		public static function sanitize_absint_or_empty( $value ) {
			if( '' === $value ) { return $value; } else { return absint( $value ); }
		}

		public static function sanitize_float_or_empty( $value ) {
			if( '' === $value ) { return $value; } else { return floatval( $value ); }
		}
		
		public static function get_border_style_choices() {
			return array( '' => __( 'Default', 'annasta-filters' ), 'none' => __( 'None', 'annasta-filters' ), 'solid' => __( 'Solid', 'annasta-filters' ), 'dashed' => __( 'Dashed', 'annasta-filters' ), 'dotted' => __( 'Dotted', 'annasta-filters' ), 'double' => __( 'Double', 'annasta-filters' ), 'groove' => __( 'Groove', 'annasta-filters' ), 'hidden' => __( 'Hidden', 'annasta-filters' ), 'inset' => __( 'Inset', 'annasta-filters' ), 'outset' => __( 'Outset', 'annasta-filters' ), 'ridge' => __( 'Ridge', 'annasta-filters' ), 'inherit' => __( 'inherit', 'annasta-filters' ) );
		}
		
		public static function get_font_weight_choices() {
			return array( '' => __( 'Default', 'annasta-filters' ), '100' => __( '100 - Thin', 'annasta-filters' ), '200' => __( '200 - Light', 'annasta-filters' ), '300' => __( '300 Book', 'annasta-filters' ), '400' => __( '400 Normal', 'annasta-filters' ), '500' => __( '500 Medium', 'annasta-filters' ), '600' => __( '600 Semibold', 'annasta-filters' ), '700' => __( '700 Bold', 'annasta-filters' ), '800' => __( '800 Heavy', 'annasta-filters' ), '900' => __( '900 Black', 'annasta-filters' ) );
		}
		
		public static function get_text_transform_choices() {
			return array( '' => __( 'Default', 'annasta-filters' ), 'none' => __( 'None', 'annasta-filters' ), 'capitalize' => __( 'Capitalize', 'annasta-filters' ), 'uppercase' => __( 'Uppercase', 'annasta-filters' ), 'lowercase' => __( 'Lowercase', 'annasta-filters' ) );
		}
		
		public static function get_toggle_button_icon_choices() {
			return array( '' => __( 'Bars', 'annasta-filters' ), 'f1de' => __( 'Sliders', 'annasta-filters' ), 'f0ae' => __( 'Tasks', 'annasta-filters' ), 'f0b0' => __( 'Filter', 'annasta-filters' ), 'f662' => __( 'Filter + dollar sign', 'annasta-filters' ), 'f085' => __( 'Cogs', 'annasta-filters' ), 'f013' => __( 'Cog', 'annasta-filters' ), 'f4fe' => __( 'User Cog', 'annasta-filters' ) );
		}
		
		public static function get_text_align_choices() {
			return array( '' => __( 'Default', 'annasta-filters' ), 'left' => __( 'Left', 'annasta-filters' ), 'right' => __( 'Right', 'annasta-filters' ), 'center' => __( 'Center', 'annasta-filters' ), 'justify' => __( 'Justify', 'annasta-filters' ) );
		}
		
		public static function get_collapse_icon_choices() {
			return array( '' => __( 'Default', 'annasta-filters' ), 'f107' => __( 'Angle', 'annasta-filters' ), 'f078' => __( 'Chevron', 'annasta-filters' ), 'f0d7' => __( 'Caret', 'annasta-filters' ), 'f068' => __( 'Plus / Minus', 'annasta-filters' ) );
		}
		
		public static function get_google_fonts_choices() {
			
			$fonts = array( '' => __( 'Theme default', 'annasta-filters' ), 'ABeeZee' => 'ABeeZee', 'Abel' => 'Abel', 'Abril+Fatface' => 'Abril Fatface', 'Aclonica' => 'Aclonica', 'Acme' => 'Acme', 'Actor' => 'Actor', 'Adamina' => 'Adamina', 'Advent+Pro' => 'Advent Pro', 'Aguafina+Script' => 'Aguafina Script', 'Akronim' => 'Akronim', 'Aladin' => 'Aladin', 'Aldrich' => 'Aldrich', 'Alef' => 'Alef', 'Alegreya' => 'Alegreya', 'Alegreya+SC' => 'Alegreya SC', 'Alegreya+Sans' => 'Alegreya Sans', 'Alegreya+Sans+SC' => 'Alegreya Sans SC', 'Alex+Brush' => 'Alex Brush', 'Alfa+Slab+One' => 'Alfa Slab One', 'Alice' => 'Alice', 'Alike' => 'Alike', 'Alike+Angular' => 'Alike Angular', 'Allan' => 'Allan', 'Allerta' => 'Allerta', 'Allerta+Stencil' => 'Allerta Stencil', 'Allura' => 'Allura', 'Almendra' => 'Almendra', 'Almendra+Display' => 'Almendra Display', 'Almendra+SC' => 'Almendra SC', 'Amarante' => 'Amarante', 'Amaranth' => 'Amaranth', 'Amatic+SC' => 'Amatic SC', 'Amatica+SC' => 'Amatica SC', 'Amethysta' => 'Amethysta', 'Amiko' => 'Amiko', 'Amiri' => 'Amiri', 'Amita' => 'Amita', 'Anaheim' => 'Anaheim', 'Andada' => 'Andada', 'Andika' => 'Andika', 'Angkor' => 'Angkor', 'Annie+Use+Your+Telescope' => 'Annie Use Your Telescope', 'Anonymous+Pro' => 'Anonymous Pro', 'Antic' => 'Antic', 'Antic+Didone' => 'Antic Didone', 'Antic+Slab' => 'Antic Slab', 'Anton' => 'Anton', 'Arapey' => 'Arapey', 'Arbutus' => 'Arbutus', 'Arbutus+Slab' => 'Arbutus Slab', 'Architects+Daughter' => 'Architects Daughter', 'Archivo+Black' => 'Archivo Black', 'Archivo+Narrow' => 'Archivo Narrow', 'Aref+Ruqaa' => 'Aref Ruqaa', 'Arima+Madurai' => 'Arima Madurai', 'Arimo' => 'Arimo', 'Arizonia' => 'Arizonia', 'Armata' => 'Armata', 'Artifika' => 'Artifika', 'Arvo' => 'Arvo', 'Arya' => 'Arya', 'Asap' => 'Asap', 'Asar' => 'Asar', 'Asset' => 'Asset', 'Assistant' => 'Assistant', 'Astloch' => 'Astloch', 'Asul' => 'Asul', 'Athiti' => 'Athiti', 'Atma' => 'Atma', 'Atomic+Age' => 'Atomic Age', 'Aubrey' => 'Aubrey', 'Audiowide' => 'Audiowide', 'Autour+One' => 'Autour One', 'Average' => 'Average', 'Average+Sans' => 'Average Sans', 'Averia+Gruesa+Libre' => 'Averia Gruesa Libre', 'Averia+Libre' => 'Averia Libre', 'Averia+Sans+Libre' => 'Averia Sans Libre', 'Averia+Serif+Libre' => 'Averia Serif Libre', 'Bad+Script' => 'Bad Script', 'Baloo' => 'Baloo', 'Baloo+Bhai' => 'Baloo Bhai', 'Baloo+Da' => 'Baloo Da', 'Baloo+Thambi' => 'Baloo Thambi', 'Balthazar' => 'Balthazar', 'Bangers' => 'Bangers', 'Basic' => 'Basic', 'Battambang' => 'Battambang', 'Baumans' => 'Baumans', 'Bayon' => 'Bayon', 'Belgrano' => 'Belgrano', 'Belleza' => 'Belleza', 'BenchNine' => 'BenchNine', 'Bentham' => 'Bentham', 'Berkshire+Swash' => 'Berkshire Swash', 'Bevan' => 'Bevan', 'Bigelow+Rules' => 'Bigelow Rules', 'Bigshot+One' => 'Bigshot One', 'Bilbo' => 'Bilbo', 'Bilbo+Swash+Caps' => 'Bilbo Swash Caps', 'BioRhyme' => 'BioRhyme', 'BioRhyme+Expanded' => 'BioRhyme Expanded', 'Biryani' => 'Biryani', 'Bitter' => 'Bitter', 'Black+Ops+One' => 'Black Ops One', 'Bokor' => 'Bokor', 'Bonbon' => 'Bonbon', 'Boogaloo' => 'Boogaloo', 'Bowlby+One' => 'Bowlby One', 'Bowlby+One+SC' => 'Bowlby One SC', 'Brawler' => 'Brawler', 'Bree+Serif' => 'Bree Serif', 'Bubblegum+Sans' => 'Bubblegum Sans', 'Bubbler+One' => 'Bubbler One', 'Buda' => 'Buda', 'Buenard' => 'Buenard', 'Bungee' => 'Bungee', 'Bungee+Hairline' => 'Bungee Hairline', 'Bungee+Inline' => 'Bungee Inline', 'Bungee+Outline' => 'Bungee Outline', 'Bungee+Shade' => 'Bungee Shade', 'Butcherman' => 'Butcherman', 'Butterfly+Kids' => 'Butterfly Kids', 'Cabin' => 'Cabin', 'Cabin+Condensed' => 'Cabin Condensed', 'Cabin+Sketch' => 'Cabin Sketch', 'Caesar+Dressing' => 'Caesar Dressing', 'Cagliostro' => 'Cagliostro', 'Cairo' => 'Cairo', 'Calligraffitti' => 'Calligraffitti', 'Cambay' => 'Cambay', 'Cambo' => 'Cambo', 'Candal' => 'Candal', 'Cantarell' => 'Cantarell', 'Cantata+One' => 'Cantata One', 'Cantora+One' => 'Cantora One', 'Capriola' => 'Capriola', 'Cardo' => 'Cardo', 'Carme' => 'Carme', 'Carrois+Gothic' => 'Carrois Gothic', 'Carrois+Gothic+SC' => 'Carrois Gothic SC', 'Carter+One' => 'Carter One', 'Catamaran' => 'Catamaran', 'Caudex' => 'Caudex', 'Caveat' => 'Caveat', 'Caveat+Brush' => 'Caveat Brush', 'Cedarville+Cursive' => 'Cedarville Cursive', 'Ceviche+One' => 'Ceviche One', 'Changa' => 'Changa', 'Changa+One' => 'Changa One', 'Chango' => 'Chango', 'Chathura' => 'Chathura', 'Chau+Philomene+One' => 'Chau Philomene One', 'Chela+One' => 'Chela One', 'Chelsea+Market' => 'Chelsea Market', 'Chenla' => 'Chenla', 'Cherry+Cream+Soda' => 'Cherry Cream Soda', 'Cherry+Swash' => 'Cherry Swash', 'Chewy' => 'Chewy', 'Chicle' => 'Chicle', 'Chivo' => 'Chivo', 'Chonburi' => 'Chonburi', 'Cinzel' => 'Cinzel', 'Cinzel+Decorative' => 'Cinzel Decorative', 'Clicker+Script' => 'Clicker Script', 'Coda' => 'Coda', 'Coda+Caption' => 'Coda Caption', 'Codystar' => 'Codystar', 'Coiny' => 'Coiny', 'Combo' => 'Combo', 'Comfortaa' => 'Comfortaa', 'Coming+Soon' => 'Coming Soon', 'Concert+One' => 'Concert One', 'Condiment' => 'Condiment', 'Content' => 'Content', 'Contrail+One' => 'Contrail One', 'Convergence' => 'Convergence', 'Cookie' => 'Cookie', 'Copse' => 'Copse', 'Corben' => 'Corben', 'Cormorant' => 'Cormorant', 'Cormorant+Garamond' => 'Cormorant Garamond', 'Cormorant+Infant' => 'Cormorant Infant', 'Cormorant+SC' => 'Cormorant SC', 'Cormorant+Unicase' => 'Cormorant Unicase', 'Cormorant+Upright' => 'Cormorant Upright', 'Courgette' => 'Courgette', 'Cousine' => 'Cousine', 'Coustard' => 'Coustard', 'Covered+By+Your+Grace' => 'Covered By Your Grace', 'Crafty+Girls' => 'Crafty Girls', 'Creepster' => 'Creepster', 'Crete+Round' => 'Crete Round', 'Crimson+Text' => 'Crimson Text', 'Croissant+One' => 'Croissant One', 'Crushed' => 'Crushed', 'Cuprum' => 'Cuprum', 'Cutive' => 'Cutive', 'Cutive+Mono' => 'Cutive Mono', 'Damion' => 'Damion', 'Dancing+Script' => 'Dancing Script', 'Dangrek' => 'Dangrek', 'David+Libre' => 'David Libre', 'Dawning+of+a+New+Day' => 'Dawning of a New Day', 'Days+One' => 'Days One', 'Dekko' => 'Dekko', 'Delius' => 'Delius', 'Delius+Swash+Caps' => 'Delius Swash Caps', 'Delius+Unicase' => 'Delius Unicase', 'Della+Respira' => 'Della Respira', 'Denk+One' => 'Denk One', 'Devonshire' => 'Devonshire', 'Dhurjati' => 'Dhurjati', 'Didact+Gothic' => 'Didact Gothic', 'Diplomata' => 'Diplomata', 'Diplomata+SC' => 'Diplomata SC', 'Domine' => 'Domine', 'Donegal+One' => 'Donegal One', 'Doppio+One' => 'Doppio One', 'Dorsa' => 'Dorsa', 'Dosis' => 'Dosis', 'Dr+Sugiyama' => 'Dr Sugiyama', 'Droid+Sans' => 'Droid Sans', 'Droid+Sans+Mono' => 'Droid Sans Mono', 'Droid+Serif' => 'Droid Serif', 'Duru+Sans' => 'Duru Sans', 'Dynalight' => 'Dynalight', 'EB+Garamond' => 'EB Garamond', 'Eagle+Lake' => 'Eagle Lake', 'Eater' => 'Eater', 'Economica' => 'Economica', 'Eczar' => 'Eczar', 'Ek+Mukta' => 'Ek Mukta', 'El+Messiri' => 'El Messiri', 'Electrolize' => 'Electrolize', 'Elsie' => 'Elsie', 'Elsie+Swash+Caps' => 'Elsie Swash Caps', 'Emblema+One' => 'Emblema One', 'Emilys+Candy' => 'Emilys Candy', 'Engagement' => 'Engagement', 'Englebert' => 'Englebert', 'Enriqueta' => 'Enriqueta', 'Erica+One' => 'Erica One', 'Esteban' => 'Esteban', 'Euphoria+Script' => 'Euphoria Script', 'Ewert' => 'Ewert', 'Exo' => 'Exo', 'Exo+2' => 'Exo 2', 'Expletus+Sans' => 'Expletus Sans', 'Fanwood+Text' => 'Fanwood Text', 'Farsan' => 'Farsan', 'Fascinate' => 'Fascinate', 'Fascinate+Inline' => 'Fascinate Inline', 'Faster+One' => 'Faster One', 'Fasthand' => 'Fasthand', 'Fauna+One' => 'Fauna One', 'Federant' => 'Federant', 'Federo' => 'Federo', 'Felipa' => 'Felipa', 'Fenix' => 'Fenix', 'Finger+Paint' => 'Finger Paint', 'Fira+Mono' => 'Fira Mono', 'Fira+Sans' => 'Fira Sans', 'Fjalla+One' => 'Fjalla One', 'Fjord+One' => 'Fjord One', 'Flamenco' => 'Flamenco', 'Flavors' => 'Flavors', 'Fondamento' => 'Fondamento', 'Fontdiner+Swanky' => 'Fontdiner Swanky', 'Forum' => 'Forum', 'Francois+One' => 'Francois One', 'Frank+Ruhl+Libre' => 'Frank Ruhl Libre', 'Freckle+Face' => 'Freckle Face', 'Fredericka+the+Great' => 'Fredericka the Great', 'Fredoka+One' => 'Fredoka One', 'Freehand' => 'Freehand', 'Fresca' => 'Fresca', 'Frijole' => 'Frijole', 'Fruktur' => 'Fruktur', 'Fugaz+One' => 'Fugaz One', 'GFS+Didot' => 'GFS Didot', 'GFS+Neohellenic' => 'GFS Neohellenic', 'Gabriela' => 'Gabriela', 'Gafata' => 'Gafata', 'Galada' => 'Galada', 'Galdeano' => 'Galdeano', 'Galindo' => 'Galindo', 'Gentium+Basic' => 'Gentium Basic', 'Gentium+Book+Basic' => 'Gentium Book Basic', 'Geo' => 'Geo', 'Geostar' => 'Geostar', 'Geostar+Fill' => 'Geostar Fill', 'Germania+One' => 'Germania One', 'Gidugu' => 'Gidugu', 'Gilda+Display' => 'Gilda Display', 'Give+You+Glory' => 'Give You Glory', 'Glass+Antiqua' => 'Glass Antiqua', 'Glegoo' => 'Glegoo', 'Gloria+Hallelujah' => 'Gloria Hallelujah', 'Goblin+One' => 'Goblin One', 'Gochi+Hand' => 'Gochi Hand', 'Gorditas' => 'Gorditas', 'Goudy+Bookletter+1911' => 'Goudy Bookletter 1911', 'Graduate' => 'Graduate', 'Grand+Hotel' => 'Grand Hotel', 'Gravitas+One' => 'Gravitas One', 'Great+Vibes' => 'Great Vibes', 'Griffy' => 'Griffy', 'Gruppo' => 'Gruppo', 'Gudea' => 'Gudea', 'Gurajada' => 'Gurajada', 'Habibi' => 'Habibi', 'Halant' => 'Halant', 'Hammersmith+One' => 'Hammersmith One', 'Hanalei' => 'Hanalei', 'Hanalei+Fill' => 'Hanalei Fill', 'Handlee' => 'Handlee', 'Hanuman' => 'Hanuman', 'Happy+Monkey' => 'Happy Monkey', 'Harmattan' => 'Harmattan', 'Headland+One' => 'Headland One', 'Heebo' => 'Heebo', 'Henny+Penny' => 'Henny Penny', 'Herr+Von+Muellerhoff' => 'Herr Von Muellerhoff', 'Hind' => 'Hind', 'Hind+Guntur' => 'Hind Guntur', 'Hind+Madurai' => 'Hind Madurai', 'Hind+Siliguri' => 'Hind Siliguri', 'Hind+Vadodara' => 'Hind Vadodara', 'Holtwood+One+SC' => 'Holtwood One SC', 'Homemade+Apple' => 'Homemade Apple', 'Homenaje' => 'Homenaje', 'IM+Fell+DW+Pica' => 'IM Fell DW Pica', 'IM+Fell+DW+Pica+SC' => 'IM Fell DW Pica SC', 'IM+Fell+Double+Pica' => 'IM Fell Double Pica', 'IM+Fell+Double+Pica+SC' => 'IM Fell Double Pica SC', 'IM+Fell+English' => 'IM Fell English', 'IM+Fell+English+SC' => 'IM Fell English SC', 'IM+Fell+French+Canon' => 'IM Fell French Canon', 'IM+Fell+French+Canon+SC' => 'IM Fell French Canon SC', 'IM+Fell+Great+Primer' => 'IM Fell Great Primer', 'IM+Fell+Great+Primer+SC' => 'IM Fell Great Primer SC', 'Iceberg' => 'Iceberg', 'Iceland' => 'Iceland', 'Imprima' => 'Imprima', 'Inconsolata' => 'Inconsolata', 'Inder' => 'Inder', 'Indie+Flower' => 'Indie Flower', 'Inika' => 'Inika', 'Inknut+Antiqua' => 'Inknut Antiqua', 'Irish+Grover' => 'Irish Grover', 'Istok+Web' => 'Istok Web', 'Italiana' => 'Italiana', 'Italianno' => 'Italianno', 'Itim' => 'Itim', 'Jacques+Francois' => 'Jacques Francois', 'Jacques+Francois+Shadow' => 'Jacques Francois Shadow', 'Jaldi' => 'Jaldi', 'Jim+Nightshade' => 'Jim Nightshade', 'Jockey+One' => 'Jockey One', 'Jolly+Lodger' => 'Jolly Lodger', 'Jomhuria' => 'Jomhuria', 'Josefin+Sans' => 'Josefin Sans', 'Josefin+Slab' => 'Josefin Slab', 'Joti+One' => 'Joti One', 'Judson' => 'Judson', 'Julee' => 'Julee', 'Julius+Sans+One' => 'Julius Sans One', 'Junge' => 'Junge', 'Jura' => 'Jura', 'Just+Another+Hand' => 'Just Another Hand', 'Just+Me+Again+Down+Here' => 'Just Me Again Down Here', 'Kadwa' => 'Kadwa', 'Kalam' => 'Kalam', 'Kameron' => 'Kameron', 'Kanit' => 'Kanit', 'Kantumruy' => 'Kantumruy', 'Karla' => 'Karla', 'Karma' => 'Karma', 'Katibeh' => 'Katibeh', 'Kaushan+Script' => 'Kaushan Script', 'Kavivanar' => 'Kavivanar', 'Kavoon' => 'Kavoon', 'Kdam+Thmor' => 'Kdam Thmor', 'Keania+One' => 'Keania One', 'Kelly+Slab' => 'Kelly Slab', 'Kenia' => 'Kenia', 'Khand' => 'Khand', 'Khmer' => 'Khmer', 'Khula' => 'Khula', 'Kite+One' => 'Kite One', 'Knewave' => 'Knewave', 'Kotta+One' => 'Kotta One', 'Koulen' => 'Koulen', 'Kranky' => 'Kranky', 'Kreon' => 'Kreon', 'Kristi' => 'Kristi', 'Krona+One' => 'Krona One', 'Kumar+One' => 'Kumar One', 'Kumar+One+Outline' => 'Kumar One Outline', 'Kurale' => 'Kurale', 'La+Belle+Aurore' => 'La Belle Aurore', 'Laila' => 'Laila', 'Lakki+Reddy' => 'Lakki Reddy', 'Lalezar' => 'Lalezar', 'Lancelot' => 'Lancelot', 'Lateef' => 'Lateef', 'Lato' => 'Lato', 'League+Script' => 'League Script', 'Leckerli+One' => 'Leckerli One', 'Ledger' => 'Ledger', 'Lekton' => 'Lekton', 'Lemon' => 'Lemon', 'Lemonada' => 'Lemonada', 'Libre+Baskerville' => 'Libre Baskerville', 'Libre+Franklin' => 'Libre Franklin', 'Life+Savers' => 'Life Savers', 'Lilita+One' => 'Lilita One', 'Lily+Script+One' => 'Lily Script One', 'Limelight' => 'Limelight', 'Linden+Hill' => 'Linden Hill', 'Lobster' => 'Lobster', 'Lobster+Two' => 'Lobster Two', 'Londrina+Outline' => 'Londrina Outline', 'Londrina+Shadow' => 'Londrina Shadow', 'Londrina+Sketch' => 'Londrina Sketch', 'Londrina+Solid' => 'Londrina Solid', 'Lora' => 'Lora', 'Love+Ya+Like+A+Sister' => 'Love Ya Like A Sister', 'Loved+by+the+King' => 'Loved by the King', 'Lovers+Quarrel' => 'Lovers Quarrel', 'Luckiest+Guy' => 'Luckiest Guy', 'Lusitana' => 'Lusitana', 'Lustria' => 'Lustria', 'Macondo' => 'Macondo', 'Macondo+Swash+Caps' => 'Macondo Swash Caps', 'Mada' => 'Mada', 'Magra' => 'Magra', 'Maiden+Orange' => 'Maiden Orange', 'Maitree' => 'Maitree', 'Mako' => 'Mako', 'Mallanna' => 'Mallanna', 'Mandali' => 'Mandali', 'Marcellus' => 'Marcellus', 'Marcellus+SC' => 'Marcellus SC', 'Marck+Script' => 'Marck Script', 'Margarine' => 'Margarine', 'Marko+One' => 'Marko One', 'Marmelad' => 'Marmelad', 'Martel' => 'Martel', 'Martel+Sans' => 'Martel Sans', 'Marvel' => 'Marvel', 'Mate' => 'Mate', 'Mate+SC' => 'Mate SC', 'Maven+Pro' => 'Maven Pro', 'McLaren' => 'McLaren', 'Meddon' => 'Meddon', 'MedievalSharp' => 'MedievalSharp', 'Medula+One' => 'Medula One', 'Meera+Inimai' => 'Meera Inimai', 'Megrim' => 'Megrim', 'Meie+Script' => 'Meie Script', 'Merienda' => 'Merienda', 'Merienda+One' => 'Merienda One', 'Merriweather' => 'Merriweather', 'Merriweather+Sans' => 'Merriweather Sans', 'Metal' => 'Metal', 'Metal+Mania' => 'Metal Mania', 'Metamorphous' => 'Metamorphous', 'Metrophobic' => 'Metrophobic', 'Michroma' => 'Michroma', 'Milonga' => 'Milonga', 'Miltonian' => 'Miltonian', 'Miltonian+Tattoo' => 'Miltonian Tattoo', 'Miniver' => 'Miniver', 'Miriam+Libre' => 'Miriam Libre', 'Mirza' => 'Mirza', 'Miss+Fajardose' => 'Miss Fajardose', 'Mitr' => 'Mitr', 'Modak' => 'Modak', 'Modern+Antiqua' => 'Modern Antiqua', 'Mogra' => 'Mogra', 'Molengo' => 'Molengo', 'Molle' => 'Molle', 'Monda' => 'Monda', 'Monofett' => 'Monofett', 'Monoton' => 'Monoton', 'Monsieur+La+Doulaise' => 'Monsieur La Doulaise', 'Montaga' => 'Montaga', 'Montez' => 'Montez', 'Montserrat' => 'Montserrat', 'Montserrat+Alternates' => 'Montserrat Alternates', 'Montserrat+Subrayada' => 'Montserrat Subrayada', 'Moul' => 'Moul', 'Moulpali' => 'Moulpali', 'Mountains+of+Christmas' => 'Mountains of Christmas', 'Mouse+Memoirs' => 'Mouse Memoirs', 'Mr+Bedfort' => 'Mr Bedfort', 'Mr+Dafoe' => 'Mr Dafoe', 'Mr+De+Haviland' => 'Mr De Haviland', 'Mrs+Saint+Delafield' => 'Mrs Saint Delafield', 'Mrs+Sheppards' => 'Mrs Sheppards', 'Mukta+Vaani' => 'Mukta Vaani', 'Muli' => 'Muli', 'Mystery+Quest' => 'Mystery Quest', 'NTR' => 'NTR', 'Neucha' => 'Neucha', 'Neuton' => 'Neuton', 'New+Rocker' => 'New Rocker', 'News+Cycle' => 'News Cycle', 'Niconne' => 'Niconne', 'Nixie+One' => 'Nixie One', 'Nobile' => 'Nobile', 'Nokora' => 'Nokora', 'Norican' => 'Norican', 'Nosifer' => 'Nosifer', 'Nothing+You+Could+Do' => 'Nothing You Could Do', 'Noticia+Text' => 'Noticia Text', 'Noto+Sans' => 'Noto Sans', 'Noto+Serif' => 'Noto Serif', 'Nova+Cut' => 'Nova Cut', 'Nova+Flat' => 'Nova Flat', 'Nova+Mono' => 'Nova Mono', 'Nova+Oval' => 'Nova Oval', 'Nova+Round' => 'Nova Round', 'Nova+Script' => 'Nova Script', 'Nova+Slim' => 'Nova Slim', 'Nova+Square' => 'Nova Square', 'Numans' => 'Numans', 'Nunito' => 'Nunito', 'Odor+Mean+Chey' => 'Odor Mean Chey', 'Offside' => 'Offside', 'Old+Standard+TT' => 'Old Standard TT', 'Oldenburg' => 'Oldenburg', 'Oleo+Script' => 'Oleo Script', 'Oleo+Script+Swash+Caps' => 'Oleo Script Swash Caps', 'Open+Sans' => 'Open Sans', 'Open+Sans+Condensed' => 'Open Sans Condensed', 'Oranienbaum' => 'Oranienbaum', 'Orbitron' => 'Orbitron', 'Oregano' => 'Oregano', 'Orienta' => 'Orienta', 'Original+Surfer' => 'Original Surfer', 'Oswald' => 'Oswald', 'Over+the+Rainbow' => 'Over the Rainbow', 'Overlock' => 'Overlock', 'Overlock+SC' => 'Overlock SC', 'Ovo' => 'Ovo', 'Oxygen' => 'Oxygen', 'Oxygen+Mono' => 'Oxygen Mono', 'PT+Mono' => 'PT Mono', 'PT+Sans' => 'PT Sans', 'PT+Sans+Caption' => 'PT Sans Caption', 'PT+Sans+Narrow' => 'PT Sans Narrow', 'PT+Serif' => 'PT Serif', 'PT+Serif+Caption' => 'PT Serif Caption', 'Pacifico' => 'Pacifico', 'Palanquin' => 'Palanquin', 'Palanquin+Dark' => 'Palanquin Dark', 'Paprika' => 'Paprika', 'Parisienne' => 'Parisienne', 'Passero+One' => 'Passero One', 'Passion+One' => 'Passion One', 'Pathway+Gothic+One' => 'Pathway Gothic One', 'Patrick+Hand' => 'Patrick Hand', 'Patrick+Hand+SC' => 'Patrick Hand SC', 'Pattaya' => 'Pattaya', 'Patua+One' => 'Patua One', 'Pavanam' => 'Pavanam', 'Paytone+One' => 'Paytone One', 'Peddana' => 'Peddana', 'Peralta' => 'Peralta', 'Permanent+Marker' => 'Permanent Marker', 'Petit+Formal+Script' => 'Petit Formal Script', 'Petrona' => 'Petrona', 'Philosopher' => 'Philosopher', 'Piedra' => 'Piedra', 'Pinyon+Script' => 'Pinyon Script', 'Pirata+One' => 'Pirata One', 'Plaster' => 'Plaster', 'Play' => 'Play', 'Playball' => 'Playball', 'Playfair+Display' => 'Playfair Display', 'Playfair+Display+SC' => 'Playfair Display SC', 'Podkova' => 'Podkova', 'Poiret+One' => 'Poiret One', 'Poller+One' => 'Poller One', 'Poly' => 'Poly', 'Pompiere' => 'Pompiere', 'Pontano+Sans' => 'Pontano Sans', 'Poppins' => 'Poppins', 'Port+Lligat+Sans' => 'Port Lligat Sans', 'Port+Lligat+Slab' => 'Port Lligat Slab', 'Pragati+Narrow' => 'Pragati Narrow', 'Prata' => 'Prata', 'Preahvihear' => 'Preahvihear', 'Press+Start+2P' => 'Press Start 2P', 'Pridi' => 'Pridi', 'Princess+Sofia' => 'Princess Sofia', 'Prociono' => 'Prociono', 'Prompt' => 'Prompt', 'Prosto+One' => 'Prosto One', 'Proza+Libre' => 'Proza Libre', 'Puritan' => 'Puritan', 'Purple+Purse' => 'Purple Purse', 'Quando' => 'Quando', 'Quantico' => 'Quantico', 'Quattrocento' => 'Quattrocento', 'Quattrocento+Sans' => 'Quattrocento Sans', 'Questrial' => 'Questrial', 'Quicksand' => 'Quicksand', 'Quintessential' => 'Quintessential', 'Qwigley' => 'Qwigley', 'Racing+Sans+One' => 'Racing Sans One', 'Radley' => 'Radley', 'Rajdhani' => 'Rajdhani', 'Rakkas' => 'Rakkas', 'Raleway' => 'Raleway', 'Raleway+Dots' => 'Raleway Dots', 'Ramabhadra' => 'Ramabhadra', 'Ramaraja' => 'Ramaraja', 'Rambla' => 'Rambla', 'Rammetto+One' => 'Rammetto One', 'Ranchers' => 'Ranchers', 'Rancho' => 'Rancho', 'Ranga' => 'Ranga', 'Rasa' => 'Rasa', 'Rationale' => 'Rationale', 'Ravi+Prakash' => 'Ravi Prakash', 'Redressed' => 'Redressed', 'Reem+Kufi' => 'Reem Kufi', 'Reenie+Beanie' => 'Reenie Beanie', 'Revalia' => 'Revalia', 'Rhodium+Libre' => 'Rhodium Libre', 'Ribeye' => 'Ribeye', 'Ribeye+Marrow' => 'Ribeye Marrow', 'Righteous' => 'Righteous', 'Risque' => 'Risque', 'Roboto' => 'Roboto', 'Roboto+Condensed' => 'Roboto Condensed', 'Roboto+Mono' => 'Roboto Mono', 'Roboto+Slab' => 'Roboto Slab', 'Rochester' => 'Rochester', 'Rock+Salt' => 'Rock Salt', 'Rokkitt' => 'Rokkitt', 'Romanesco' => 'Romanesco', 'Ropa+Sans' => 'Ropa Sans', 'Rosario' => 'Rosario', 'Rosarivo' => 'Rosarivo', 'Rouge+Script' => 'Rouge Script', 'Rozha+One' => 'Rozha One', 'Rubik' => 'Rubik', 'Rubik+Mono+One' => 'Rubik Mono One', 'Rubik+One' => 'Rubik One', 'Ruda' => 'Ruda', 'Rufina' => 'Rufina', 'Ruge+Boogie' => 'Ruge Boogie', 'Ruluko' => 'Ruluko', 'Rum+Raisin' => 'Rum Raisin', 'Ruslan+Display' => 'Ruslan Display', 'Russo+One' => 'Russo One', 'Ruthie' => 'Ruthie', 'Rye' => 'Rye', 'Sacramento' => 'Sacramento', 'Sahitya' => 'Sahitya', 'Sail' => 'Sail', 'Salsa' => 'Salsa', 'Sanchez' => 'Sanchez', 'Sancreek' => 'Sancreek', 'Sansita+One' => 'Sansita One', 'Sarala' => 'Sarala', 'Sarina' => 'Sarina', 'Sarpanch' => 'Sarpanch', 'Satisfy' => 'Satisfy', 'Scada' => 'Scada', 'Scheherazade' => 'Scheherazade', 'Schoolbell' => 'Schoolbell', 'Scope+One' => 'Scope One', 'Seaweed+Script' => 'Seaweed Script', 'Secular+One' => 'Secular One', 'Sevillana' => 'Sevillana', 'Seymour+One' => 'Seymour One', 'Shadows+Into+Light' => 'Shadows Into Light', 'Shadows+Into+Light+Two' => 'Shadows Into Light Two', 'Shanti' => 'Shanti', 'Share' => 'Share', 'Share+Tech' => 'Share Tech', 'Share+Tech+Mono' => 'Share Tech Mono', 'Shojumaru' => 'Shojumaru', 'Short+Stack' => 'Short Stack', 'Shrikhand' => 'Shrikhand', 'Siemreap' => 'Siemreap', 'Sigmar+One' => 'Sigmar One', 'Signika' => 'Signika', 'Signika+Negative' => 'Signika Negative', 'Simonetta' => 'Simonetta', 'Sintony' => 'Sintony', 'Sirin+Stencil' => 'Sirin Stencil', 'Six+Caps' => 'Six Caps', 'Skranji' => 'Skranji', 'Slabo+13px' => 'Slabo 13px', 'Slabo+27px' => 'Slabo 27px', 'Slackey' => 'Slackey', 'Smokum' => 'Smokum', 'Smythe' => 'Smythe', 'Sniglet' => 'Sniglet', 'Snippet' => 'Snippet', 'Snowburst+One' => 'Snowburst One', 'Sofadi+One' => 'Sofadi One', 'Sofia' => 'Sofia', 'Sonsie+One' => 'Sonsie One', 'Sorts+Mill+Goudy' => 'Sorts Mill Goudy', 'Source+Code+Pro' => 'Source Code Pro', 'Source+Sans+Pro' => 'Source Sans Pro', 'Source+Serif+Pro' => 'Source Serif Pro', 'Space+Mono' => 'Space Mono', 'Special+Elite' => 'Special Elite', 'Spicy+Rice' => 'Spicy Rice', 'Spinnaker' => 'Spinnaker', 'Spirax' => 'Spirax', 'Squada+One' => 'Squada One', 'Sree+Krushnadevaraya' => 'Sree Krushnadevaraya', 'Sriracha' => 'Sriracha', 'Stalemate' => 'Stalemate', 'Stalinist+One' => 'Stalinist One', 'Stardos+Stencil' => 'Stardos Stencil', 'Stint+Ultra+Condensed' => 'Stint Ultra Condensed', 'Stint+Ultra+Expanded' => 'Stint Ultra Expanded', 'Stoke' => 'Stoke', 'Strait' => 'Strait', 'Sue+Ellen+Francisco' => 'Sue Ellen Francisco', 'Suez+One' => 'Suez One', 'Sumana' => 'Sumana', 'Sunshiney' => 'Sunshiney', 'Supermercado+One' => 'Supermercado One', 'Sura' => 'Sura', 'Suranna' => 'Suranna', 'Suravaram' => 'Suravaram', 'Suwannaphum' => 'Suwannaphum', 'Swanky+and+Moo+Moo' => 'Swanky and Moo Moo', 'Syncopate' => 'Syncopate', 'Tangerine' => 'Tangerine', 'Taprom' => 'Taprom', 'Tauri' => 'Tauri', 'Taviraj' => 'Taviraj', 'Teko' => 'Teko', 'Telex' => 'Telex', 'Tenali+Ramakrishna' => 'Tenali Ramakrishna', 'Tenor+Sans' => 'Tenor Sans', 'Text+Me+One' => 'Text Me One', 'The+Girl+Next+Door' => 'The Girl Next Door', 'Tienne' => 'Tienne', 'Tillana' => 'Tillana', 'Timmana' => 'Timmana', 'Tinos' => 'Tinos', 'Titan+One' => 'Titan One', 'Titillium+Web' => 'Titillium Web', 'Trade+Winds' => 'Trade Winds', 'Trirong' => 'Trirong', 'Trocchi' => 'Trocchi', 'Trochut' => 'Trochut', 'Trykker' => 'Trykker', 'Tulpen+One' => 'Tulpen One', 'Ubuntu' => 'Ubuntu', 'Ubuntu+Condensed' => 'Ubuntu Condensed', 'Ubuntu+Mono' => 'Ubuntu Mono', 'Ultra' => 'Ultra', 'Uncial+Antiqua' => 'Uncial Antiqua', 'Underdog' => 'Underdog', 'Unica+One' => 'Unica One', 'UnifrakturCook' => 'UnifrakturCook', 'UnifrakturMaguntia' => 'UnifrakturMaguntia', 'Unkempt' => 'Unkempt', 'Unlock' => 'Unlock', 'Unna' => 'Unna', 'VT323' => 'VT323', 'Vampiro+One' => 'Vampiro One', 'Varela' => 'Varela', 'Varela+Round' => 'Varela Round', 'Vast+Shadow' => 'Vast Shadow', 'Vesper+Libre' => 'Vesper Libre', 'Vibur' => 'Vibur', 'Vidaloka' => 'Vidaloka', 'Viga' => 'Viga', 'Voces' => 'Voces', 'Volkhov' => 'Volkhov', 'Vollkorn' => 'Vollkorn', 'Voltaire' => 'Voltaire', 'Waiting+for+the+Sunrise' => 'Waiting for the Sunrise', 'Wallpoet' => 'Wallpoet', 'Walter+Turncoat' => 'Walter Turncoat', 'Warnes' => 'Warnes', 'Wellfleet' => 'Wellfleet', 'Wendy+One' => 'Wendy One', 'Wire+One' => 'Wire One', 'Work+Sans' => 'Work Sans', 'Yanone+Kaffeesatz' => 'Yanone Kaffeesatz', 'Yantramanav' => 'Yantramanav', 'Yatra+One' => 'Yatra One', 'Yellowtail' => 'Yellowtail', 'Yeseva+One' => 'Yeseva One', 'Yesteryear' => 'Yesteryear', 'Yrsa' => 'Yrsa', 'Zeyada' => 'Zeyada' );
						
			return $fonts;
		}
		
  }
}

?>