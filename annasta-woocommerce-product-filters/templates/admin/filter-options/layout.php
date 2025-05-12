<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

              <tr class="awf-hide-for-range-slider awf-hide-for-daterangepicker">
                <td><label for="<?php echo $filter->prefix; ?>layout"><?php esc_html_e( 'Layout', 'annasta-filters' ); ?></label></td>
                <td><div class="awf-fo-flex2">
<?php
  $select_options = array(
    'name' => $filter->prefix . 'layout',
    'id' => $filter->prefix . 'layout',
    'selected' => $value,
    'options' => array(
      'row'      => __( 'Display in row (align center)', 'annasta-filters' ),
      'row-left' => __( 'Display in row (align left)', 'annasta-filters' ),
      '1-column' => __( '1 column', 'annasta-filters' ),
      '2-column' => __( '2 columns', 'annasta-filters' ),
      '3-column' => __( '3 columns', 'annasta-filters' ),
    )
  );

  echo A_W_F::$admin->build_select_html( $select_options );
?>

                    <label for="<?php echo $filter->prefix; ?>sslayout" style="width:100%;text-align:right;align-self:center;margin-left:25px;white-space:nowrap;"><?php esc_html_e( 'On smaller screens', 'annasta-filters' ); ?><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Layout on screens smaller than the preset Responsive Width', 'annasta-filters' ); ?>"></span></label>

<?php

if( isset( $filter->settings['sslayout'] ) ) {
  $select_2_options = array(
    'name' => $filter->prefix . 'sslayout',
    'id' => $filter->prefix . 'sslayout',
    'selected' => $filter->settings['sslayout'],
    'options' => array(
      ''         => __( 'Same as on wide screens', 'annasta-filters' ),
      'row'      => __( 'Display in row (align center)', 'annasta-filters' ),
      'row-left' => __( 'Display in row (align left)', 'annasta-filters' ),
      '1-column' => __( '1 column', 'annasta-filters' ),
      '2-column' => __( '2 columns', 'annasta-filters' ),
      '3-column' => __( '3 columns', 'annasta-filters' ),
    )
  );

  echo A_W_F::$admin->build_select_html( $select_2_options );
}
?>
                </div></td>
              </tr>