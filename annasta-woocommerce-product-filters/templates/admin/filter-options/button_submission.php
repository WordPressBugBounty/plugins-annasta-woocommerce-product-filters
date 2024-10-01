<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

              <tr class="awf-hide-for-daterangepicker">
                <td>
                  <label for="<?php echo $filter->prefix; ?>button_submission"><?php esc_html_e( 'Enable button submission', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Adding submit button will disable instant filter submission.', 'annasta-filters' ); ?>"></span>
                </td>
                <td>
                  <div class="awf-fo-flex1">
                    <input type="checkbox" name="<?php echo $filter->prefix; ?>button_submission" id="<?php echo $filter->prefix; ?>button_submission" value="yes"<?php if( ! empty( $filter->settings['button_submission'] ) ) { echo ' checked="checked"'; } ?>>
                    
                    <div class="awf-fo-flex1-child">
                      <label for="<?php echo $filter->prefix; ?>submit_button_label"><?php esc_html_e( 'Submit button label', 'annasta-filters' ); ?></label>
                      <input name="<?php echo $filter->prefix; ?>submit_button_label" id="<?php echo $filter->prefix; ?>submit_button_label" type="text" value="<?php echo empty( $filter->settings['style_options']['submit_button_label'] ) ? esc_html_x( 'Filter', 'Submit button label', 'annasta-filters' ) : esc_attr( $filter->settings['style_options']['submit_button_label'] ); ?>">
                    </div>
                  </div>
                </td>
              </tr>