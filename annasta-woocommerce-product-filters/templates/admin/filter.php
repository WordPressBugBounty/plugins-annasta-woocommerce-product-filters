<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

$filter_enabled = true;

?>

    <tr id="awf-filter-<?php echo $filter->preset_id . '-' . $filter->id; ?>" class="awf-filter-wrapper awf-filter-collapsed<?php if( 'range-slider' === $filter->settings['style'] ) { echo ' awf-range-slider-filter'; } ?>">
      <td colspan="2">
      <table>
        <tr class="awf-filter-header">
          <td class="awf-filter-priority sort-handle" title="<?php esc_attr_e( 'Drag and drop filter into the needed position', 'annasta-filters' ); ?>"><?php echo esc_html( A_W_F::$presets[$filter->preset_id]['filters'][$filter->id] + 1 ); ?></td>
          <td class="awf-preset-filter-title">
						<?php
						
							echo esc_html( $filter->settings['title'] );
						
							$filter_label = '';
						
							if( 'taxonomy' === $filter->module ) {
								if( $taxonomy = get_taxonomy( $filter->settings['taxonomy'] ) ) {
									$filter_label = $taxonomy->label;
									
								} else {
									$filter_enabled = false;
									$filter_label = $filter->settings['taxonomy'];
								}
								
							} else {
								$filter_label = A_W_F::$admin->get_filter_title( $filter->module );
								
								if( 'meta' === $filter->module && isset( $filter->settings['meta_name'] ) ) {
									$filter_label = sprintf( '%1$s: %2$s', $filter_label, $filter->settings['meta_name'] );
								}
							}
						
							if( $filter_enabled ) {
								echo '<br><span class="awf-preset-filter-title-label">' . esc_html( $filter_label ) . '</span>';
							} else {
								echo '<br><span class="awf-preset-filter-title-label" style="color: #eb2222;">' . esc_html( sprintf( __( '"%s" taxonomy associated with this filter was not found!', 'annasta-filters' ), $filter_label ) ) . '</span>';
							}
							
						?>
					</td>
          <td class="awf-buttons-column">
            <button class="button button-secondary awf-fa-icon awf-fas-icon awf-fa-delete-btn awf-delete-filter-btn" type="button" title="<?php esc_attr_e( 'Remove filter', 'annasta-filters' ); ?>"></button>
<?php if( A_W_F::$premium ) : ?>
            <button class="button button-secondary awf-fa-icon awf-fas-icon awf-popup-filter-templates-btn" type="button" title="<?php esc_attr_e( 'Import settings from another filter or template', 'annasta-filters' ); ?>" data-filter-id="<?php echo esc_attr( $filter->id ); ?>"></button>
<?php endif; ?>
            <a class="button button-secondary awf-icon awf-filter-toggle-btn" title="<?php esc_attr_e( 'Show filter options', 'annasta-filters' ); ?>" data-toggle-title="<?php esc_attr_e( 'Hide filter options', 'annasta-filters' ); ?>"></a>
          </td>
        </tr>
        <tr class="awf-filter-options-container">
          <td colspan="3" class="awf-filter-options">
            <h4></h4>
            <table class="awf-filter-options-table">
<?php
if( $filter_enabled ) {
  foreach( $filter->settings as $name => $value ) {
    if( is_null( $value ) ) { continue; }
    $file = A_W_F_PLUGIN_PATH . 'templates/admin/filter-options/' . $name . '.php';
    if( file_exists( $file ) ) {
      include( $file );
    } else {
      if( A_W_F::$premium ) {
        $file = A_W_F_PLUGIN_PATH . 'templates/admin/filter-options/premium/' . $name . '.php';
        if( file_exists( $file ) ) { include( $file ); }
      }
    }
  }
}
?>
            </table>
        
            <a class="button button-secondary awf-icon awf-collapse-filter-btn" title="<?php esc_attr_e( 'Hide filter options', 'annasta-filters' ); ?>"></a>
            
          </td>
        </tr><!-- end of .awf-filter-options-container-->
      </table>
      </td>
    </tr><!-- end of .awf-filter-wrapper-->