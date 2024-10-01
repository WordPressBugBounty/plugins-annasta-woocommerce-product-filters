<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php 
  $tr_class = 'awf-hide-for-range-type';
  if ( 'taxonomy' === $filter->module ) { $tr_class = ''; }
  ?>
              <tr class="<?php echo $tr_class; ?>">
                <td><label for="<?php echo $filter->prefix; ?>sort_order"><?php esc_html_e( 'Filter items sort order', 'annasta-filters' ); ?></label></td>
                <td>
<?php
  $select_options = array(
    'name' => $filter->prefix . 'sort_order',
    'id' => $filter->prefix . 'sort_order',
    'selected' => $filter->settings['sort_order'],
    'options' => array(
      'asc' => __( 'Ascending', 'annasta-filters' ),
      'desc' => __( 'Descending', 'annasta-filters' ),
    )
  );

  echo A_W_F::$admin->build_select_html( $select_options );
?>
                </td>
              </tr>