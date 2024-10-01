<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<h3><?php esc_html_e( 'Preset Filters', 'annasta-filters' ) ?></h3>

<table class="widefat awf-preset-filters-table">
  <thead>
    <tr>
      <th colspan="2">
<?php
  echo A_W_F::$admin->build_select_html( array( 'id' => 'awf_filters_select', 'options' => $filters_select, 'selected' => null ) );
?>
        <button id="awf-add-filter" class="button button-secondary awf-fa-icon-text-btn awf-fa-add-btn" type="button" title="<?php esc_attr_e( 'Add filter or control', 'annasta-filters' ); ?>"><?php esc_attr_e( 'Add', 'annasta-filters' ); ?></button>
      </th>
    </tr>
  </thead>
  <tbody class="ui-sortable">
<?php 
  foreach( A_W_F::$presets[$this->preset->id]['filters'] as $filter_id => $position ) { 
    $filter = new A_W_F_filter( $this->preset->id, $filter_id );
    include( A_W_F_PLUGIN_PATH . 'templates/admin/filter.php' );
  }
?>
  </tbody>
  <tfoot>
  </tfoot>
</table>
