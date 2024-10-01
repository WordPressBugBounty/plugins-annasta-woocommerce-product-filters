<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_widget' ) ) {
  class A_W_F_widget extends WP_Widget {

    public function __construct() {
      
      $settings = array(
        'description' => esc_html__( 'Filters for WooCommerce products.', 'annasta-filters' ),
        'show_instance_in_rest' => true,
      );
      parent::__construct( 'awf_widget', __( 'annasta WooCommerce Filters Widget', 'annasta-filters' ), $settings );
      add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }
    
    /**
     * Output widget.
     *
     * @see WP_Widget
     *
     * @param array $args Arguments.
     * @param array $instance Widget instance.
     */

    public function widget( $args, $instance ) {
			if( ! empty( A_W_F::$front ) && isset( $instance['preset_id'] ) ) {
				A_W_F::$front->display_widget( $instance['preset_id'], $args );
			}
    }

    /**
     * Back-end widget settings form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
      $presets = $this->get_presets();
      $new_class = '';
      
      if( empty( $instance['preset_id'] ) ) {
        $instance['preset_id'] = '1';
        $new_class = ' awf-widget-add-new';
      }
      
      if( ! isset( $presets[$instance['preset_id']] ) ) { $instance['preset_id'] = 'none'; }
      if( 'none' === $instance['preset_id'] && ! isset( $presets['none'] ) ) {
        $presets['none'] = __( 'Please select the Filters Preset', 'annasta-filters' );
      }
        
      ?>
      <p>
      <input class="widefat awf-widget-title<?php echo $new_class; ?>" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="hidden" value="<?php echo esc_attr( $presets[$instance['preset_id']] ); ?>">
      <label for="<?php echo esc_attr( $this->get_field_id( 'preset_id' ) ); ?>" class="awf-widget-label"><?php esc_html_e( 'Filters Preset', 'annasta-filters' ); ?></label> 
      <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'preset_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'preset_id' ) ); ?>">
      <?php 
      foreach( $presets as $preset => $label ) : ?>
        <option value="<?php echo esc_attr( $preset ); ?>"
      <?php if( (string) $preset === $instance['preset_id'] ) { echo ' selected="selected"'; } ?>
        ><?php echo esc_html( $label ); ?></option>
      <?php endforeach; ?>
      </select>
      </p>
      <?php 

    }

    /**
     * Sanitize widget settings as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {

      $instance = array( 'preset_id' => 'none', 'title' => '' );
      $presets = $this->get_presets();

      if( isset( $presets[$new_instance['preset_id']] ) ) {
        $instance['preset_id'] = $new_instance['preset_id'];
        $instance['title'] = $presets[$new_instance['preset_id']];
        
      } else {
        if( isset( $presets['none'] ) ) { $instance['title'] = $presets['none']; }
      }

      return $instance;
    }

    private function get_presets() {
      $awf_presets = array();
      
      if( empty( A_W_F::$presets ) ) {
        $awf_presets['none'] = __( 'Please create annasta Filters Preset', 'annasta-filters' );
        
      } else {
        foreach( A_W_F::$presets as $preset_id => $preset_data ) {
          $awf_presets[strval( $preset_id )] = __( get_option( 'awf_preset_' . $preset_id . '_name', '' ) );
        }
      }

      return $awf_presets;
    }

    public function enqueue_admin_scripts( $hook ) {
      wp_enqueue_style( 'awf-widget', A_W_F_PLUGIN_URL . '/styles/awf-widget.css', false, A_W_F::$plugin_version );
      wp_enqueue_script( 'awf-widget', A_W_F_PLUGIN_URL . '/code/js/awf-widget.js', array( 'jquery' ), A_W_F::$plugin_version );
    }

  }
}

?>