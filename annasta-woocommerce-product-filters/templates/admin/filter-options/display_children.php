<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr class="awf-hide-for-range-type awf-hide-for-hierarchical-sbs">
                <td>
                  <label for="<?php echo $filter->prefix; ?>display_children"><?php esc_html_e( 'Display hierarchical children', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Control the display of hierarchical sub-levels.', 'annasta-filters' ); ?>"></span>
                </td>
                <td>
                  <input type="checkbox" name="<?php echo $filter->prefix; ?>display_children" id="<?php echo $filter->prefix; ?>display_children" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?>>
                  <div style="height: 15px;"></div>
                  <label for="<?php echo $filter->prefix; ?>children_collapsible" class="awf-secondary-label"><?php esc_html_e( 'Make hierarchical children collapsible', 'annasta-filters' ); ?></label>
                  <input type="checkbox" name="<?php echo $filter->prefix; ?>children_collapsible" id="<?php echo $filter->prefix; ?>children_collapsible" value="yes"<?php if( ! empty( $filter->settings['children_collapsible'] ) ) { echo ' checked="checked"'; } ?>>
                  <label for="<?php echo $filter->prefix; ?>children_collapsible_on" class="awf-secondary-label"><?php esc_html_e( 'Initialize with children collapsed', 'annasta-filters' ); ?>
                    <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Active filters will remain uncollapsed.', 'annasta-filters' ); ?>"></span>
                  </label>
                  <input type="checkbox" name="<?php echo $filter->prefix; ?>children_collapsible_on" id="<?php echo $filter->prefix; ?>children_collapsible_on" value="yes"<?php if( ! empty( $filter->settings['children_collapsible_on'] ) ) { echo ' checked="checked"'; } ?>>
                </td>
              </tr>
