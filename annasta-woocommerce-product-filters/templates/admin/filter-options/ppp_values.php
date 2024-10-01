<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td><label><?php esc_html_e( 'Products Per Page', 'annasta-filters' ); ?></label><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'For the products list to display properly the products per page values should be numbers divisible by the amount of products columns.', 'annasta-filters' ); ?>"></span></td>
                <td>
                  <table class="awf-ppp-values-table awf-filter-options-secondary-table">
                    <tbody>
<?php
  $ppp_default = (int) get_option( 'awf_ppp_default', 0 );
  echo A_W_F::$admin->build_ppp_values_list( $filter, $ppp_default );
?>
                    </tbody>
                    
                    <tfoot>
                      <tr>
                        <td colspan="2">
                          <div class="awf-add-ppp-value-container">
                            <div><label for="<?php echo $filter->prefix; ?>add_ppp_value"><?php esc_html_e( 'Add value', 'annasta-filters' ); ?></label><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'To update a label add the same value again with the new label. Use the value of -1 for the all products option.', 'annasta-filters' ); ?>"></span><input id="<?php echo $filter->prefix; ?>add_ppp_value" type="text" class="awf-add-ppp-value" size="5"></div>
                            <div><label for="<?php echo $filter->prefix; ?>add_ppp_label"><?php esc_html_e( 'label', 'annasta-filters' ); ?></label><input id="<?php echo $filter->prefix; ?>add_ppp_label" type="text" class="awf-add-ppp-label"></div>
                          </div>
                        </td>
                        <td class="awf-buttons-column">
                          <button type="button" class="button button-secondary awf-add-ppp-value-btn awf-icon awf-add-btn" title="<?php esc_attr_e( 'Add products per page value', 'annasta-filters' ); ?>"></button>
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </td>
              </tr>
              <tr>
                <td></td>
                <td>
<?php 
echo '<strong><span class="awf-info-notice">', sprintf( esc_html__( 'To prevent server overloads all products per page values will always be limited to %1$s, even when -1 (show all) is used.', 'annasta-filters' ), esc_html( intval( get_option( 'awf_ppp_limit', '200' ) ) ) ), '</span></strong>';

if( 0 !== $ppp_default && ! isset( $filter->settings['ppp_values'][$ppp_default] ) ) {
  echo
    '<br><br>',
    '<span class="awf-info-notice">',
    sprintf( esc_html__( 'The default products per page value is set to %1$s. You can include it in the list of values for it to show as the default (checked) value of your products per page control. You can change the default products per page value in the annasta Filters > Product Lists tab.', 'annasta-filters' ), esc_html( $ppp_default ) ),
    '</span>'
  ;
}
?>
                </td>                    
              </tr>