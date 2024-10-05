<?php

defined( 'ABSPATH' ) or die( 'Access denied' );
/*
* Plugin Name:  annasta Woocommerce Product Filters
* Description:  Filter the products of your Woocommerce shop by category, custom taxonomies, attributes, price, stock, on sale products and more!
* Version:      1.7.5
*
* Author:       annasta.net
* Author URI:   https://www.annasta.net
* License:      GPLv2 or later
* License URI:  https://www.gnu.org/licenses/gpl.html
*
* Text Domain:  annasta-filters
* Domain Path:  /languages
*
* Requires Plugins: woocommerce
* WC requires at least: 4.5
* WC tested up to: 9.3
*
*/
if ( !defined( 'A_W_F_PLUGIN_PATH' ) ) {
    define( 'A_W_F_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( !defined( 'A_W_F_PLUGIN_FILE' ) ) {
    define( 'A_W_F_PLUGIN_FILE', __FILE__ );
}
if ( !defined( 'A_W_F_PLUGIN_URL' ) ) {
    define( 'A_W_F_PLUGIN_URL', plugins_url( '', __FILE__ ) );
}
if ( !defined( 'A_W_F_VERSION' ) ) {
    define( 'A_W_F_VERSION', '1.7.5' );
}
if ( function_exists( 'a_w_f_fs' ) ) {
    a_w_f_fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'a_w_f_fs' ) ) {
        // Create a helper function for easy SDK access.
        function a_w_f_fs() {
            global $a_w_f_fs;
            if ( !isset( $a_w_f_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $a_w_f_fs = fs_dynamic_init( array(
                    'id'             => '3789',
                    'slug'           => 'annasta-woocommerce-product-filters',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_bb5ec96ed2ca320da281f38c4a0ac',
                    'is_premium'     => false,
                    'premium_suffix' => 'Premium',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                        'slug'    => 'annasta-filters',
                        'contact' => false,
                        'support' => false,
                    ),
                    'is_live'        => true,
                ) );
            }
            return $a_w_f_fs;
        }

        // Init Freemius.
        a_w_f_fs();
        // Signal that SDK was initiated.
        do_action( 'a_w_f_fs_loaded' );
    }
}
require A_W_F_PLUGIN_PATH . 'code/class-a-w-f.php';
if ( class_exists( 'A_W_F' ) ) {
    register_activation_hook( A_W_F_PLUGIN_FILE, array('A_W_F', 'activate_plugin') );
    register_deactivation_hook( A_W_F_PLUGIN_FILE, array('A_W_F', 'deactivate_plugin') );
    a_w_f_fs()->add_action( 'after_uninstall', array('A_W_F', 'uninstall_plugin') );
    add_action( 'plugins_loaded', array('A_W_F', 'get_instance') );
}
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );