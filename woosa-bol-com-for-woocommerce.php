<?php
/**
 * Plugin Name: Woosa - bol.com for WooCommerce - light version
 * Description: Sell your products on bol.com and realize more revenue within your WooCommerce webshop.
 * Version: 1.1.2
 * Author: Woosa
 * Author URI:  https://www.woosa.nl
 * Text Domain: woosa-bol-com-for-woocommerce
 * Domain Path: /languages
 * Network: false
 *
 * WC requires at least: 3.5.0
 * WC tested up to: 3.6.1
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


define(__NAMESPACE__ . '\PREFIX', 'bol');

define(__NAMESPACE__ . '\PLUGIN_VERSION', '1.1.2');

define(__NAMESPACE__ . '\PLUGIN_NAME', 'Woosa - bol.com for WooCommerce');

define(__NAMESPACE__ . '\PLUGIN_URL', untrailingslashit(plugin_dir_url(__FILE__)));

define(__NAMESPACE__ . '\PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));

define(__NAMESPACE__ . '\PLUGIN_BASENAME', plugin_basename(PLUGIN_DIR) . '/'.basename(__FILE__));

define(__NAMESPACE__ . '\PLUGIN_FOLDER', plugin_basename(PLUGIN_DIR));

define(__NAMESPACE__ . '\PLUGIN_INSTANCE', sanitize_title(crypt($_SERVER['SERVER_NAME'], $salt = PLUGIN_FOLDER)));

define(__NAMESPACE__ . '\PLUGIN_SETTINGS_URL', admin_url('edit.php?post_type=bol_invoice&page=bol-settings'));

define(__NAMESPACE__ . '\ERROR_PATH', plugin_dir_path(__FILE__) . 'error.log');


//init
if(!class_exists( __NAMESPACE__ . '\Core')){
	include_once PLUGIN_DIR . '/includes/class-core.php';
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\Core::on_activation');
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\Core::on_deactivation');
register_uninstall_hook(__FILE__, __NAMESPACE__ . '\Core::on_uninstall');

//load translation, make sure this hook runs before all, so we set priority to 1
add_action('init', function(){
   load_plugin_textdomain( 'woosa-bol-com-for-woocommerce', false, dirname(plugin_basename( __FILE__ )) . '/languages/' );
}, 1);