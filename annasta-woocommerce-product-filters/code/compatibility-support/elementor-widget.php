<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

/**
 * annasta WooCommerce Product Filters Widget for Elementor.
 *
 * Inserts annasta WooCommerce Product Filters preset.
 *
 * @since 1.7.3
 * 
 */
class A_W_F_elementor_widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve annasta WooCommerce Product Filters widget name.
	 *
	 * @since 1.7.3
	 * @access public
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'awf_elementor_widget';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve annasta WooCommerce Product Filters widget title.
	 *
	 * @since 1.7.3
	 * @access public
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'annasta WooCommerce Filters', 'annasta-filters' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve annasta WooCommerce Product Filters widget icon.
	 *
	 * @since 1.7.3
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'awf-elementor-widget-icon';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the annasta WooCommerce Product Filters widget belongs to.
	 *
	 * @since 1.7.3
	 * @access public
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'woocommerce-elements' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the annasta WooCommerce Product Filters widget belongs to.
	 *
	 * @since 1.7.3
	 * @access public
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'annasta', 'filter', 'filters', 'search' ];
	}

	/**
	 * Get custom help URL.
	 *
	 * Retrieve a URL where the user can get more information about the widget.
	 *
	 * @since 1.7.3
	 * @access public
	 * @return string Widget help URL.
	 */
	public function get_custom_help_url() {
		return 'https://annasta.net//troubleshoot/elementor/';
	}

	/**
	 * Register annasta WooCommerce Product Filters widget controls.
	 *
	 * Adds the select box with the list of the existing annasta Filters presets.
	 *
	 * @since 1.7.3
	 * @access protected
	 */
	protected function register_controls() {
    $presets = $this->get_presets();
    reset( $presets );
    $default = key( $presets );

		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'annasta-filters' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

    $this->add_control(
      'awf_preset',
      [
        'type' => \Elementor\Controls_Manager::SELECT,
        'label' => esc_html__( 'Filters preset', 'annasta-filters' ),
        'options' => $presets,
        'default' => $default,
      ]
    );

		$this->end_controls_section();
	}

  /**
	 * Whether the reload preview is required or not.
	 *
	 * Used to determine whether the reload preview is required.
	 *
	 * @since 1.7.3
	 * @access public
	 *
	 * @return bool Whether the reload preview is required.
	 */
	public function is_reload_preview_required() {
		return true;
	}

	/**
	 * Render annasta WooCommerce Product Filters widget output on the frontend.
	 *
	 * Generate the annasta Filters preset HTML.
	 *
	 * @since 1.7.3
	 * @access protected
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		if ( empty( $settings['awf_preset'] ) ) {
			return;
		}

    if( empty( A_W_F::$front ) ) {
      if( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        A_W_F::$preview_mode = true;
        A_W_F::get_instance()->initialize_frontend();
        A_W_F::$front->get_access_to['elementor_preview'] = true;
        A_W_F::$front->set_query_vars();
        echo( A_W_F::$front->display_widget( $settings['awf_preset'], array() ) );
        A_W_F::$front = false;
      }

    } else {
      echo( A_W_F::$front->display_widget( $settings['awf_preset'], array() ) );
    }
	}
  
  /**
	 * Get the list of annasta WooCommerce Product Filters presets.
	 *
	 * Returns the array of annasta WooCommerce Product Filters presets.
   * Array keys are the preset ids, with the values containing the corresponding preset label.
	 *
	 * @since 1.7.3
	 * @access private
   * @return array annasta Filters presets.
	 */

  private function get_presets() {
    $awf_presets = array();
    
    if( empty( A_W_F::$presets ) ) {
      $awf_presets[''] = __( 'Please create annasta Filters Preset', 'annasta-filters' );
      
    } else {
      foreach( A_W_F::$presets as $preset_id => $preset_data ) {
        $awf_presets[strval( $preset_id )] = __( get_option( 'awf_preset_' . $preset_id . '_name', '' ) );
      }
    }

    return $awf_presets;
  }

}