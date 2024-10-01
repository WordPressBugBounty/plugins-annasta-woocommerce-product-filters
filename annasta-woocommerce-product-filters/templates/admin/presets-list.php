<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<?php $display_modes = A_W_F::$admin->get_display_modes(); ?>

<h2><?php esc_html_e( 'Filter Presets', 'annasta-filters' ); ?><?php A_W_F::$admin->display_presets_list_title_buttons( $this->settings_url ); ?></h2>


<table class="widefat awf-presets-table ui-sortable">
  <thead>
    <tr>
      <th></th>
      <th><?php esc_html_e( 'ID', 'annasta-filters' ) ?></th>
      <th><?php esc_html_e( 'Preset', 'annasta-filters' ) ?></th>
      <th><?php esc_html_e( 'Visibility', 'annasta-filters' ) ?></th>
      <th></th>
		</tr>
  </thead>
	<tbody>

  <?php foreach( A_W_F::$presets as $preset_id => $preset ) :
    $filters_list = '<ol>';

    foreach( $preset['filters'] as $filter_id => $position ) :
      $filter = new A_W_F_filter( $preset_id, $filter_id );
      $filters_list .= '<li>' . esc_html( A_W_F::$admin->get_default_filter_label( $filter->module, $filter->settings ) ) . '</li>';
    endforeach;
    $filters_list .= '</ol>';
    
  ?>
  <?php $dm = get_option( 'awf_preset_' . $preset_id . '_display_mode', 'visible' ); ?>
    <tr data-id="<?php echo esc_attr( $preset_id ); ?>">
      <td class="sort-handle" title="<?php esc_attr_e( 'Move up or down to arrange presets in a convenient order.', 'annasta-filters' ); ?>"></td>
      <td class="awf-preset-id-column"><?php echo esc_html( $preset_id ); ?></td>
      <td class="awf-preset-name-column"><?php echo esc_html( get_option( 'awf_preset_' . $preset_id . '_name', '' ) ); ?></td>
      <td class="awf-associations-column">
        <div class="awf-associations-column-contents">

          <button class="awf-annasta-icon-btn awf-edit-display-mode-btn" title="<?php echo isset( $display_modes[$dm] ) ? esc_attr( $display_modes[$dm] ) : ''; ?>" data-preset-id="<?php echo esc_attr( $preset_id ); ?>">
            <img src="<?php echo esc_url( A_W_F_PLUGIN_URL . '/styles/images/display-mode-' . $dm . '.png' ); ?>" width="23" class="awf-display-mode-icon-s" />
          </button>

          <div class="awf-associations-container">
            <span class="awf-associations-list">
<?php
  if( empty( $associations_by_preset[$preset_id] ) ) {
    echo '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'To display this preset, add page associations in the "Display on" section of preset settings.', 'annasta-filters' ) . '"></span>';
    echo '<span style="margin-right: 5px;">', wp_kses( __( '<strong>ATTENTION!</strong>', 'annasta-filters' ), array( 'strong' => array() ) ), '</span>';
    echo sprintf( wp_kses( __( '<a href="%1$s">No pages are associated with this preset.</a>', 'annasta-filters' ), array(  'a' => array( 'href' => array() ), 'strong' => array() ) ), admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&awf-preset=' . $preset_id . '&awf-goto=awf-associations-select' ) );

  } else {
    echo esc_html( $associations_by_preset[$preset_id] );
  }
?>
            </span>
          </div>
        </div>
      </td>
      <td class="awf-buttons-column">
        <div class="awf-preset-hover-btns">
          <i class="fas fa-list-alt awf-preset-filters-list-btn">
            <div style="display:none;" class="awf-preset-filters-list">
              <div class="awf-preset-filters-list-title"><?php echo esc_html__( 'Filters', 'annasta-filters' ); ?></div>
              <?php echo( $filters_list ); ?>
            </div>
          </i>
          <span class="dashicons dashicons-shortcode awf-preset-shortcode-btn" title="<?php esc_attr_e( 'Click to copy shortcode to clipboard', 'annasta-filters' ) ?>" data-tip="<?php esc_attr_e( 'Shortcode copied to clipboard', 'annasta-filters' ) ?>">
            <div style="display:none;" class="awf-preset-shortcode-container">
              <span class="awf-preset-shortcode">[annasta_filters preset_id=<?php echo esc_attr( $preset_id ); ?>]</span>
            </div>
          </span>
        </div>
        <?php A_W_F::$admin->awf_display_preset_btns( $preset_id, $this->settings_url ); ?>
        <a class="button button-secondary awf-fa-icon awf-fas-icon awf-fa-edit-btn awf-edit-preset-btn" href="<?php echo esc_url( add_query_arg( array( 'awf-preset' => $preset_id ), $this->settings_url ) ); ?>" title="<?php esc_attr_e( 'Edit preset', 'annasta-filters' ); ?>"></a>
      </td>
    </tr>
  <?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="5">
        <?php A_W_F::$admin->awf_display_presets_list_footer( $this->settings_url ); ?>
			</td>
		</tr>
	</tfoot>
</table>