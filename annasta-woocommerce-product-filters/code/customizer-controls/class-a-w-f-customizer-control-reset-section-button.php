<?php
/**
 * Reset all button control for annasta Filters Customizer section
 *
 * @package     annasta Woocommerce Product Filters Wordpress Plugin
 * @since       1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( class_exists( 'WP_Customize_Control' ) ) {
	
	class A_W_F_customizer_control_reset_section_button extends WP_Customize_Control {

		/**
		 * Render the control's content.
		 * Allows the content to be overriden without having to rewrite the wrapper in $this->render().
		 *
		 * @access protected
		 */
		protected function render_content() {
			?>
<button type="button" class="awf-customizer-reset-section-button" data-section="<?php esc_attr_e( $this->section ); ?>"><?php esc_html_e( 'Reset all', 'annasta-filters' ); ?></button>
			<?php
		}
	}
	
}

