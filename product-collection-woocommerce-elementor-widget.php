<?php
/*
Plugin Name: Product Collection WooCommerce Elementor Widget
Plugin URI: https://github.com/IbrahimRumani/product-collection-woocommerce-elementor-widget
Description: A simple widget to Display WooCommerce Products in Elementor based on various criterias.
Version: 1.0.0
Requires at least: 4.5
Tested up to:6.3
Requires PHP:5.6
Author: InfoBahn
Author URI: https://infobahn.io?utm_source=wordpress&utm_medium=plugin_uri&utm_campaign=wc_elementor_plugin
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Check if WooCommerce and Elementor are active
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))
    && in_array('elementor/elementor.php', apply_filters('active_plugins', get_option('active_plugins')))
) {
    require_once plugin_dir_path(__FILE__) . 'widgets/product-collections.php';
}
