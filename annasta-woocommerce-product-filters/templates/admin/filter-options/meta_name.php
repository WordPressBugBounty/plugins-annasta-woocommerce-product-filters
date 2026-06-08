<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td><label for="<?php echo esc_attr( $filter->prefix ); ?>meta_name"><?php esc_html_e( 'Meta data name', 'annasta-filters' ); ?></label></td>
                <td><input name="<?php echo esc_attr( $filter->prefix ); ?>meta_name" id="<?php echo esc_attr( $filter->prefix ); ?>meta_name" type="text" value="<?php echo esc_attr( $value ); ?>"></td>
              </tr>