<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr class="filter-type-container">
                <td><label for="<?php echo $filter->prefix; ?>type"><?php esc_html_e( 'Filter type', 'annasta-filters' ); ?></label></td>
                <td>
                  <?php echo A_W_F::$admin->build_type_select( $filter ); ?>
                </td>
              </tr>