<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td>
                  <label for="<?php echo $filter->prefix; ?>placeholder"><?php esc_html_e( 'Placeholder text', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Set the hint to display inside of an empty search box. Leave blank if not needed.', 'annasta-filters' ); ?>"></span>
                </td>
                <td><input name="<?php echo $filter->prefix; ?>placeholder" id="<?php echo $filter->prefix; ?>placeholder" type="text" value="<?php echo esc_attr( $value ); ?>"></td>
              </tr>