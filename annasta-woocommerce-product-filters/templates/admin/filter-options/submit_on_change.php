<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

              <tr>
                <td>
                  <label for="<?php echo $filter->prefix; ?>submit_on_change"><?php esc_html_e( 'Force instant submission', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Force the search control submission when user presses the "Enter" key or the focus moves to another control. Can be used in presets with non-instant Filtering Style or to bypass the "Clear search field" (x) button tabulation precedence.', 'annasta-filters' ); ?>"></span>
                </td>
                <td>
                  <input type="checkbox" name="<?php echo $filter->prefix; ?>submit_on_change" id="<?php echo $filter->prefix; ?>submit_on_change" value="yes"<?php if( ! empty( $filter->settings['submit_on_change'] ) ) { echo ' checked="checked"'; } ?>>
                </td>
              </tr>