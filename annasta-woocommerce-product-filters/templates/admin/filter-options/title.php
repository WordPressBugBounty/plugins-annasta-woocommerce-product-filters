<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td><label for="<?php echo $filter->prefix; ?>title"><?php esc_html_e( 'Filter title', 'annasta-filters' ); ?></label></td>
                <td><input name="<?php echo $filter->prefix; ?>title" id="<?php echo $filter->prefix; ?>title" type="text" value="<?php echo esc_attr( $value ); ?>"></td>
              </tr>