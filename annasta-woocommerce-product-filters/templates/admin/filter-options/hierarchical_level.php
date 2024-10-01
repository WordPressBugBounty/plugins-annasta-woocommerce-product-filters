<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<?php if ( A_W_F::$premium ): include( A_W_F_PLUGIN_PATH . 'templates/admin/filter-options/premium/hierarchical_level.php' ); ?>
<?php else: ?>

              <tr class="awf-hide-for-range-type">
                <td><label for="<?php echo $filter->prefix; ?>hierarchical_level"><?php esc_html_e( 'Hierarchical level', 'annasta-filters' ); ?></label><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Customize the starting hierarchical level display of the current filter.', 'annasta-filters' ); ?>"></span></td>
                <td>
                <?php
  $select_options = array(
    'name' => $filter->prefix . 'hierarchical_level',
    'id' => $filter->prefix . 'hierarchical_level',
    'selected' => $filter->settings['hierarchical_level'],
    'options' => array(
      '1' => '1',
      '2' => '2',
      '3' => '3',
      '4' => '4',
      '5' => '5',
    )
  );

  echo A_W_F::$admin->build_select_html( $select_options );
?>
                </td>
              </tr>

<?php endif; ?>