<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr class="awf-hide-for-range-slider awf-hide-for-daterangepicker">
                <td><label for="<?php echo $filter->prefix; ?>show_in_row"><?php esc_html_e( 'Display items in a row', 'annasta-filters' ); ?></label></td>
                <td><input type="checkbox" name="<?php echo $filter->prefix; ?>show_in_row" id="<?php echo $filter->prefix; ?>show_in_row" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?>></td>
              </tr>