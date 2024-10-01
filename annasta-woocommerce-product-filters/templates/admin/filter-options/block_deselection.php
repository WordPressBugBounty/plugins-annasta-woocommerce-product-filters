<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td>
                  <label for="<?php echo $filter->prefix; ?>block_deselection"><?php esc_html_e( 'Block deactivation', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Prevents filter deselection (complete reset) in single or multiselect modes.', 'annasta-filters' ); ?>"></span>
                </td>
                <td><input type="checkbox" name="<?php echo $filter->prefix; ?>block_deselection" id="<?php echo $filter->prefix; ?>block_deselection" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?>></td>
              </tr>