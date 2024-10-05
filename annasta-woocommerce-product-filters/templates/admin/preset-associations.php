<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<?php if( empty( $preset_associations ) ): ?>
      <div class="awf-info-notice-container" style="margin-bottom:5px;">
<?php
  echo '<span class="awf-info-notice awf-attention-notice">', wp_kses( __( '<strong>ATTENTION!</strong> This preset <strong>is not displayed</strong> on any of the pages of your site. Please add page associations below.', 'annasta-filters' ), array( 'strong' => array() ) ), '</span>';
?>
      </div>
<?php endif; ?>

<table class="widefat awf-associations-table">
<?php if( ! empty( $associations_select ) ): ?>
  <thead>
    <tr>
      <th colspan="2">
<?php
$associations_select = array( 'id' => 'awf-associations-select', 'options' => $associations_select, 'selected' => null );
echo A_W_F::$admin->build_select_html( $associations_select );

echo '<select id="awf-taxonomy-associations-select" style="display:none;"></select>'
?>
        <button type="button" id="awf-add-association-btn" class="button button-secondary awf-fa-icon-text-btn awf-fa-add-btn" title="<?php esc_attr_e( 'Add page association', 'annasta-filters' ); ?>"><?php esc_attr_e( 'Add', 'annasta-filters' ); ?></button>
      </th>
    </tr>
  </thead>
<?php endif; ?>
  <tbody>
<?php foreach( $preset_associations as $association_id => $label ) : ?>
    <tr>
      <td class="awf-association-name"><?php echo esc_html( $label ); ?></td>
      <td class="awf-buttons-column">
        <button type="button" class="button button-secondary awf-fa-icon awf-fas-icon awf-fa-delete-btn awf-delete-association-btn" title="<?php esc_attr_e( 'Remove page association', 'annasta-filters' ); ?>" data-association="<?php echo esc_attr( $association_id ); ?>"></button>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
