<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr class="awf-show-title-container"<?php if( ! empty( $filter->settings['is_dropdown'] ) || ! empty( $filter->settings['is_collapsible'] ) ) { echo ' style="display: none;"'; } ?>>
                <td><label for="<?php echo esc_attr( $filter->prefix ); ?>show_title"><?php esc_html_e( 'Show title bar', 'annasta-filters' ); ?></label></td>
                <td><input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>show_title" id="<?php echo esc_attr( $filter->prefix ); ?>show_title" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?>></td>
              </tr>