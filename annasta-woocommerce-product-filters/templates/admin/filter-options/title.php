<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td><label for="<?php echo esc_attr( $filter->prefix ); ?>title"><?php esc_html_e( 'Filter title', 'annasta-filters' ); ?></label></td>
                <td><input name="<?php echo esc_attr( $filter->prefix ); ?>title" id="<?php echo esc_attr( $filter->prefix ); ?>title" type="text" value="<?php echo esc_attr( $value ); ?>"></td>
              </tr>