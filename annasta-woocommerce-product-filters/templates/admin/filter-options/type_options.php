<?php if( ! defined( 'ABSPATH' ) ) { exit; } ?>

<?php
if( 'range' === $filter->settings['type'] ) :
  $module_range_types = array(
    'taxonomy'  => array( 'auto_range', 'custom_range', 'taxonomy_range' ),
    'price'     => array( 'auto_range', 'custom_range' ),
    'rating'    => array( 'auto_range', 'custom_range' ),
    'meta'      => array( 'auto_range', 'custom_range' ),
  );

  if( ! in_array( $filter->settings['type_options']['range_type'], $module_range_types[$filter->module] ) ) {
    $filter->settings['type_options']['range_type'] = reset( $module_range_types[$filter->module] );
  }

  $range_types_labels = array(
    'auto_range'      => __( 'Automatically calculated numeric values', 'annasta-filters' ),
    'custom_range'    => __( 'Custom numeric values', 'annasta-filters' ),
    'taxonomy_range'  => __( 'Non-numeric filter terms', 'annasta-filters' )
  );

  $select_options = array();
  foreach( $module_range_types[$filter->module] as $rt ) {
    $select_options[$rt] = $range_types_labels[$rt];
  }
?>
              <tr class="range-type-container">
                <td><label for="<?php echo $filter->prefix; ?>range_type"><?php esc_html_e( 'Range values', 'annasta-filters' ); ?></label></td>
                <td>
<?php
  echo A_W_F::$admin->build_select_html( array(
    'name' => $filter->prefix . 'range_type', 
    'id' => $filter->prefix . 'range_type', 
    'class' => 'awf-range-type-select', 
    'options' => $select_options, 
    'selected' => $filter->settings['type_options']['range_type']
  ) );

  if( 'price' === $filter->module && 'yes' === get_option( 'awf_dynamic_price_ranges', 'no' ) && 'range-slider' === $filter->settings['style'] ) {
    echo
      '<br><br>',
      '<span class="awf-info-notice">',
      esc_html__( 'The minimum and maximum slider values will fluctuate depending on filters combination. You can disable this behavior in annasta Filters > Plugin settings > Dynamic price sliders.', 'annasta-filters' ),
      '</span>'
    ;
  }
?>
                  <div class="awf-range-type-options">
<?php
  echo A_W_F::$admin->build_range_type_options( $filter );
?>
                  </div><!--endof .awf-range-type-options-->
                </td>
              </tr>

<?php endif; ?>
