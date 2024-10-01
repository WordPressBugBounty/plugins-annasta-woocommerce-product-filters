<?php
/**
 * Custom title control for annasta Filters Customizer section
 *
 * @package     annasta Woocommerce Product Filters Wordpress Plugin
 * @since       1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( class_exists( 'WP_Customize_Control' ) ) {
	
	class A_W_F_customizer_control_title extends WP_Customize_Control {

		public $type = 'awf-title';

		/**
		 * Render the control's content.
		 * Allows the content to be overriden without having to rewrite the wrapper in $this->render().
		 *
		 * @access protected
		 */
		protected function render_content() {
			?>

			<label>
				<?php if ( ! empty( $this->label ) ) : ?>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $this->description ) ) : ?>
					<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
				<?php endif; ?>
			</label>

			<?php
		}
	}
	
}

