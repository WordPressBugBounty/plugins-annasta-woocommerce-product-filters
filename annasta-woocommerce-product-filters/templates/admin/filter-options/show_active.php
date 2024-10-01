<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td>
                  <label for="<?php echo $filter->prefix; ?>show_active"><?php esc_html_e( 'Show active filter badges', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'This will display active filter badges under the filter title, or on top of the filter if title is omitted.', 'annasta-filters' ); ?>"></span>
                </td>

                <td>
                <div class="awf-fo-flex1">
                  <input type="checkbox" name="<?php echo $filter->prefix; ?>show_active" id="<?php echo $filter->prefix; ?>show_active" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?>>
<?php if( isset( $filter->settings['active_prefix'] ) ) : ?>
                  <div class="awf-fo-flex1-child">
                    <label for="<?php echo $filter->prefix; ?>active_prefix"><?php esc_html_e( 'Filter value prefix', 'annasta-filters' ); ?><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'You will see this prefix in front of the filter value in active filter badges and filter label hover tips. Leave blank if not needed.', 'annasta-filters' ); ?>"></span></label>
                    <input name="<?php echo $filter->prefix; ?>active_prefix" id="<?php echo $filter->prefix; ?>active_prefix" type="text" value="<?php echo esc_attr( $filter->settings['active_prefix'] ); ?>">
                  </div>
<?php endif; ?>
                </div>
                </td>
              </tr>