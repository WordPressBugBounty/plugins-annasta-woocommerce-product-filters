<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td class="awf-filter-style-container-label-container"><label for="<?php echo $filter->prefix; ?>style"><?php esc_html_e( 'Filter style', 'annasta-filters' ); ?></label></td>
                <td id="awf-filter-<?php echo $filter->preset_id . '-' . $filter->id; ?>-style-container" class="awf-filter-style-container"><?php echo A_W_F::$admin->build_style_options( $filter ); ?></td>
              </tr>
