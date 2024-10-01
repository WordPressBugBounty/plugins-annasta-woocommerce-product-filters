<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr class="awf-hide-for-range-slider awf-hide-for-daterangepicker">
                <td>
                  <label for="<?php echo $filter->prefix; ?>height_limit"><?php esc_html_e( 'Limit filter height', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Limit the height of filters\' container. Scroll bars will appear if the total height of filter items exceeds this setting. Leave blank or set to zero for no height limit.', 'annasta-filters' ); ?>"></span>
                </td>
                <td>
                  <input name="<?php echo $filter->prefix; ?>height_limit" id="<?php echo $filter->prefix; ?>height_limit" type="text" value="<?php echo esc_attr( $value ); ?>" style="width: 5em;">
                  <span style="padding-right:2em;"><?php esc_html_e( 'pixels', 'annasta-filters' ); ?></span>

                  <label for="<?php echo $filter->prefix; ?>height_limit_style" class="awf-secondary-label"><?php esc_html_e( 'Limitation style', 'annasta-filters' ); ?></label>

                  <?php
  $select_options = array(
    'name' => $filter->prefix . 'height_limit_style',
    'id' => $filter->prefix . 'height_limit_style',
    'selected' => empty( $filter->settings['style_options']['height_limit_style'] ) ? 'scrollbars' : $filter->settings['style_options']['height_limit_style'],
    'custom' => ' style="max-width:170px; margin-right:2em;"',
    'options' => array(
      'scrollbars' => __( 'Scrollbars', 'annasta-filters' ),
      'toggle' => __( '"Show more" button', 'annasta-filters' ),
    )
  );

  if( ! A_W_F::$premium ) {
    $select_options['options']['toggle'] = '(premium) ' . $select_options['options']['toggle'];
    $select_options['disabled'] = array( 'toggle' );
  }

  echo A_W_F::$admin->build_select_html( $select_options );
?>

                  <input type="checkbox" style="margin-right:-5px;" name="<?php echo $filter->prefix; ?>shrink_height_limit" id="<?php echo $filter->prefix; ?>shrink_height_limit" value="yes"<?php if( ! empty( $filter->settings['shrink_height_limit'] ) ) { echo ' checked="checked"'; } ?>>
                  <label for="<?php echo $filter->prefix; ?>shrink_height_limit" class="awf-secondary-label">
                    <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Adjust height limit for smaller lists, for example when filter options get hidden via the Empty items style setting.', 'annasta-filters' ); ?>"></span>
                    <?php esc_html_e( 'Auto-shrink for shorter options list', 'annasta-filters' ); ?>
                  </label>
                </td>
              </tr>