<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr class="awf-hide-for-range-type">
                <td><label for="<?php echo $filter->prefix; ?>show_count"><?php esc_html_e( 'Show products\' counts', 'annasta-filters' ); ?></label></td>
                <td><input type="checkbox" name="<?php echo $filter->prefix; ?>show_count" id="<?php echo $filter->prefix; ?>show_count" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?>></td>
              </tr>