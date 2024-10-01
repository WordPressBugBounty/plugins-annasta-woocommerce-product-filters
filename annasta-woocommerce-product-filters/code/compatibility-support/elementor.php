<?php

function awf_shortcode_elementor_preview( $widget_content, $widget ) {

  if( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {

    if( 'shortcode' === $widget->get_name() ) {
      $settings = $widget->get_settings();
      if( strpos( $settings['shortcode'], 'annasta_filters ' ) && empty( A_W_F::$front ) ) {
        A_W_F::$preview_mode = true;
        A_W_F::get_instance()->initialize_frontend();
        A_W_F::$front->get_access_to['elementor_preview'] = true;
        A_W_F::$front->set_query_vars();
        $widget_content = do_shortcode( $settings['shortcode'] );
        A_W_F::$front = false;
      }
    }
  }

  return $widget_content;
}
add_filter( 'elementor/widget/render_content', 'awf_shortcode_elementor_preview', 10, 2 );

function awf_register_elementor_widget( $widgets_manager ) {
  require_once( A_W_F_PLUGIN_PATH . 'code/compatibility-support/elementor-widget.php' );
  $widgets_manager->register( new A_W_F_elementor_widget() );
}
add_action( 'elementor/widgets/register', 'awf_register_elementor_widget' );

function awf_enqueue_elementor_editor_styles() {

  wp_register_style( 'awf-elementor-preview', A_W_F_PLUGIN_URL . '/code/compatibility-support/elementor-widget.css' );
  wp_enqueue_style( 'awf-elementor-preview' );

}
add_action( 'elementor/editor/after_enqueue_styles', 'awf_enqueue_elementor_editor_styles' );
add_action( 'elementor/preview/enqueue_styles', 'awf_enqueue_elementor_editor_styles' );

function awf_enqueue_elementor_preview_scripts() {

  wp_register_script( 'awf-elementor-preview', A_W_F_PLUGIN_URL . '/code/compatibility-support/elementor-widget.js', array( 'jquery' ) );
  wp_enqueue_script( 'awf-elementor-preview' );

}
add_action( 'elementor/preview/enqueue_scripts', 'awf_enqueue_elementor_preview_scripts' );

function awf_add_elementor_js_data( $js_data ) {

  if( ! empty( $js_data['no_result_container'] ) ) {
    $selectors = get_option( 'awf_custom_selectors', array() );

    if( empty( $selectors['no_result'] ) || ( $selectors['no_result'] !== $js_data['no_result_container'] ) ) {
      $js_data['no_result_container'] = '.elementor-products-nothing-found,' . $js_data['no_result_container'];
    }
  }

  return $js_data;
}
if( defined( 'ELEMENTOR_PRO_VERSION' ) ) { add_filter( 'awf_js_data', 'awf_add_elementor_js_data' ); }

?>