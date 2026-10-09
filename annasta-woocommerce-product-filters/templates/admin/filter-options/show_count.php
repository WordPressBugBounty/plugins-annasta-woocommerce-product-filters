<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
							<tr class="awf-hide-for-range-type awf-hide-for-daterangepicker">
								<td><label for="<?php echo esc_attr( $filter->prefix ); ?>show_count"><?php esc_html_e( 'Show products\' counts', 'annasta-filters' ); ?></label></td>
								<td><input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>show_count" id="<?php echo esc_attr( $filter->prefix ); ?>show_count" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?>></td>
							</tr>
							<tr class="awf-hide-for-range-type awf-hide-for-daterangepicker">
								<td><label for="<?php echo esc_attr( $filter->prefix ); ?>hide_active"><?php esc_html_e( 'Hide active items', 'annasta-filters' ); ?></label><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Hide the selected terms from the list of filter options. Use together with the active filter badges or the reset button, so that shoppers could deselect the hidden terms.', 'annasta-filters' ); ?>"></span></td>
								<td class="<?php echo empty( $filter->settings['hide_active'] ) ? '' : 'awf-hide-active-on'; ?>">
									<div class="awf-fo-flex2" style="align-items:center;margin-bottom:0;">
										<input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>hide_active" id="<?php echo esc_attr( $filter->prefix ); ?>hide_active" class="awf-hide-active-checkbox" value="yes"<?php if( ! empty( $filter->settings['hide_active'] ) ) { echo ' checked="checked"'; } ?> style="margin-left:0;">
<?php if( isset( $filter->settings['display_children'] ) ) : ?>
										<div class="awf-hide-active-parents-container">
											<label for="<?php echo esc_attr( $filter->prefix ); ?>hide_active_exclude_parents" class="awf-secondary-label"><?php esc_html_e( 'Exclude hierarchical parents with available children', 'annasta-filters' ); ?><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Keep the selected terms that have children available for filtering visible. Otherwise the whole hierarchical branch of a selected term gets hidden.', 'annasta-filters' ); ?>"></span></label>
											<input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>hide_active_exclude_parents" id="<?php echo esc_attr( $filter->prefix ); ?>hide_active_exclude_parents" value="yes"<?php if( ! empty( $filter->settings['hide_active_exclude_parents'] ) ) { echo ' checked="checked"'; } ?>>
										</div>
<?php endif; ?>
									</div>
								</td>
							</tr>
