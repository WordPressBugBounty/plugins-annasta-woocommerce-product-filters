<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<?php if ( A_W_F::$premium ): include( A_W_F_PLUGIN_PATH . 'templates/admin/filter-options/premium/is_collapsible.php' ); ?>
<?php else: ?>

              <tr>
                <td>
                  <label for="<?php echo $filter->prefix; ?>is_collapsible"><?php esc_html_e( 'Collapsible', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Make the filter collapsible by adding collapse buttons to the right of the filter title, allowing users to toggle the opened and closed state of the filter options.', 'annasta-filters' ); ?>"></span>
                </td>
                <td>
                  <input type="checkbox" name="<?php echo $filter->prefix; ?>is_collapsible" id="<?php echo $filter->prefix; ?>is_collapsible" value="yes" class="awf-is-collapsible"<?php if( ! empty( $filter->settings['is_collapsible'] ) ) { echo ' checked="checked"'; } ?>>
                  <input type="checkbox" name="<?php echo $filter->prefix; ?>collapsed_on" id="<?php echo $filter->prefix; ?>collapsed_on" value="yes"<?php if( ! empty( $filter->settings['collapsed_on'] ) ) { echo ' checked="checked"'; } ?>>
                  <label for="<?php echo $filter->prefix; ?>collapsed_on" class="awf-secondary-label"><?php esc_html_e( 'Initialize in a collapsed state', 'annasta-filters' ); ?></label>
                </td>
              </tr>

<?php endif; ?>