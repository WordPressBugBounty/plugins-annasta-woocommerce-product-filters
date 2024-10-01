<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php 
  $tr_class = 'awf-hide-for-range-type';
  if ( 'taxonomy' === $filter->module ) { $tr_class = ''; }
  ?>
              <tr class="<?php echo $tr_class; ?>">
                <td><label for="<?php echo $filter->prefix; ?>sort_by"><?php esc_html_e( 'Sort filter items by', 'annasta-filters' ); ?></label></td>
                <td>
<?php
  $select_options = array(
    'name' => $filter->prefix . 'sort_by',
    'id' => $filter->prefix . 'sort_by',
    'selected' => $filter->settings['sort_by'],
    'options' => array(
      'admin' => __( 'Admin sort order', 'annasta-filters' ),
      'name' => __( 'Item name', 'annasta-filters' ),
      'term_id' => __( 'Order of creation', 'annasta-filters' ),
      'numeric' => __( 'Numeric', 'annasta-filters' ),
    )
  );

  echo A_W_F::$admin->build_select_html( $select_options );
?>
                </td>
              </tr>