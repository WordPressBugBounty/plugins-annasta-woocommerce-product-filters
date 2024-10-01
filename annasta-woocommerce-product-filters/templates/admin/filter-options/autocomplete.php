<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td><label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete"><?php esc_html_e( 'Enable autocomplete', 'annasta-filters' ); ?></label></td>
                <td>
                  <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?> class="awf-autocomplete-option">
                  <div class="awf-ac-options-container<?php if( empty( $value ) ) { echo ' awf-collapsed'; } ?>">
                    <h2><?php esc_html_e( 'Autocomplete settings', 'annasta-filters' ); ?></h2>
                    
                    <div class="awf-ac-options-row awf-ac-filtered">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_filtered" class="awf-secondary-label"><?php esc_html_e( 'Apply filters to autocomplete results', 'annasta-filters' ); ?>
                        <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Filter suggestions with the currently active filters. For example, when you have the Red color filter applied and enter \'apple\' in the search field, the autocomplete will display products associated with the Red color AND the title / description containing the word \'apple\'.', 'annasta-filters' ); ?>"></span>
                      </label>
                      <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_filtered" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_filtered" value="yes"<?php if( ! empty( $filter->settings['type_options']['autocomplete_filtered'] ) ) { echo ' checked="checked"'; } ?>>
                    </div>
                    
                    <div class="awf-ac-options-row awf-ac-after">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_after"><?php esc_html_e( 'Begin autocomplete after', 'annasta-filters' ); ?></label>
                      <input type="text" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_after" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_after" value="<?php if( ! empty( $filter->settings['type_options']['autocomplete_after'] ) ) { echo esc_attr( $filter->settings['type_options']['autocomplete_after'] ); } else { echo '2'; } ?>" style="width:80px;min-width:80px;">
                      <label class="awf-secondary-label"><?php esc_html_e( 'characters', 'annasta-filters' ); ?></label>
                    </div>
                    
                    <div class="awf-ac-options-row awf-ac-rc">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_results_count"><?php esc_html_e( 'Show the maximum of', 'annasta-filters' ); ?></label>
                      <input type="text" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_results_count" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_results_count" value="<?php if( ! empty( $filter->settings['type_options']['autocomplete_results_count'] ) ) { echo esc_attr( $filter->settings['type_options']['autocomplete_results_count'] ); } else { echo '5'; } ?>" style="width:80px;min-width:80px;">
                      <label class="awf-secondary-label"><?php esc_html_e( 'results', 'annasta-filters' ); ?></label>
                    </div>

                    <div class="awf-ac-options-row awf-ac-cats">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>ac_display_product_cat"><?php esc_html_e( 'Enable categories search', 'annasta-filters' ); ?></label>
                      <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>ac_display_product_cat" id="<?php echo esc_attr( $filter->prefix ); ?>ac_display_product_cat" value="yes"<?php if( ! empty( $filter->settings['type_options']['ac_display_product_cat'] ) ) { echo ' checked="checked"'; } ?>>
                    </div>
                    
                    <div class="awf-ac-options-row awf-ac-cats-header">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>ac_product_cat_header"><?php esc_html_e( 'Categories header', 'annasta-filters' ); ?>
                        <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Leave blank if not needed.', 'annasta-filters' ); ?>"></span>
                      </label>
                      <input type="text" name="<?php echo esc_attr( $filter->prefix ); ?>ac_product_cat_header" id="<?php echo esc_attr( $filter->prefix ); ?>ac_product_cat_header" value="<?php if( ! empty( $filter->settings['type_options']['ac_product_cat_header'] ) ) { echo esc_attr( $filter->settings['type_options']['ac_product_cat_header'] ); } else { echo ''; } ?>">
                    </div>

                    <div class="awf-ac-options-row awf-ac-tags">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>ac_display_product_tag"><?php esc_html_e( 'Enable tags search', 'annasta-filters' ); ?>
                      </label>
                      <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>ac_display_product_tag" id="<?php echo esc_attr( $filter->prefix ); ?>ac_display_product_tag" value="yes"<?php if( ! empty( $filter->settings['type_options']['ac_display_product_tag'] ) ) { echo ' checked="checked"'; } ?>>
                    </div>

                    <div class="awf-ac-options-row awf-ac-tags-header">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>ac_product_tag_header"><?php esc_html_e( 'Tags header', 'annasta-filters' ); ?>
                        <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Leave blank if not needed.', 'annasta-filters' ); ?>"></span>
                      </label>
                      <input type="text" name="<?php echo esc_attr( $filter->prefix ); ?>ac_product_tag_header" id="<?php echo esc_attr( $filter->prefix ); ?>ac_product_tag_header" value="<?php if( ! empty( $filter->settings['type_options']['ac_product_tag_header'] ) ) { echo esc_attr( $filter->settings['type_options']['ac_product_tag_header'] ); } else { echo ''; } ?>">
                    </div>

                    <h3><?php esc_html_e( 'Products list', 'annasta-filters' ); ?></h3>

                    <div class="awf-ac-options-row">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>ac_products_header"><?php esc_html_e( 'Products header', 'annasta-filters' ); ?>
                        <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Leave blank if not needed.', 'annasta-filters' ); ?>"></span>
                      </label>
                      <input type="text" name="<?php echo esc_attr( $filter->prefix ); ?>ac_products_header" id="<?php echo esc_attr( $filter->prefix ); ?>ac_products_header" value="<?php if( ! empty( $filter->settings['type_options']['ac_products_header'] ) ) { echo esc_attr( $filter->settings['type_options']['ac_products_header'] ); } else { echo ''; } ?>">
                    </div>
                    
                    <div class="awf-ac-options-row">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_img"><?php esc_html_e( 'Display products\' images', 'annasta-filters' ); ?></label>
                      <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_img" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_img" value="yes"<?php if( ! empty( $filter->settings['type_options']['autocomplete_show_img'] ) ) { echo ' checked="checked"'; } ?>>
                    </div>
                    
                    <div class="awf-ac-options-row">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_price"><?php esc_html_e( 'Display products\' prices', 'annasta-filters' ); ?></label>
                      <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_price" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_price" value="yes"<?php if( ! empty( $filter->settings['type_options']['autocomplete_show_price'] ) ) { echo ' checked="checked"'; } ?>>

                    </div>
                    
                    <div class="awf-ac-options-row awf-ac-view-all">
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_view_all"><?php esc_html_e( 'Show "View all results" link', 'annasta-filters' ); ?></label>
                      <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_view_all" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_view_all" value="yes"<?php if( ! empty( $filter->settings['type_options']['autocomplete_view_all'] ) ) { echo ' checked="checked"'; } ?>>
                    </div>
                    
                    <?php if( method_exists( A_W_F::$admin, 'display_premium_autocomplete_options' ) ) { A_W_F::$admin->display_premium_autocomplete_options( $filter ); } ?>
                  </div>
                </td>
              </tr>