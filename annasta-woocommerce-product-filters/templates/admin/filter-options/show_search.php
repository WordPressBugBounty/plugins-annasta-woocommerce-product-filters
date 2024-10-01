<?php if( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr class="awf-hide-for-range-slider awf-hide-for-daterangepicker">
                <td>
                  <label for="<?php echo $filter->prefix; ?>show_search"><?php esc_html_e( 'Display items search', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Filter terms\' search box works with radio buttons, checkboxes, custom icons, labels, and tags', 'annasta-filters' ); ?>"></span>
                </td>
                <td>
                  <div class="awf-fo-flex1">
                    <input type="checkbox" name="<?php echo $filter->prefix; ?>show_search" id="<?php echo $filter->prefix; ?>show_search" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?>>

<?php if( isset( $filter->settings['show_search_placeholder'] ) ) : ?>
                    <div class="awf-fo-flex1-child">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>show_search_placeholder" style="vertical-align: baseline;"><?php esc_html_e( 'Items search box placeholder', 'annasta-filters' ); ?></label>
                      <input type="text" name="<?php echo esc_attr( $filter->prefix ); ?>show_search_placeholder" id="<?php echo esc_attr( $filter->prefix ); ?>show_search_placeholder" value="<?php if( ! empty( $filter->settings['show_search_placeholder'] ) ) { echo esc_attr( $filter->settings['show_search_placeholder'] ); } ?>" style="width:auto;">
                    </div>
<?php endif; ?>
                  </div>
                </td>
              </tr>