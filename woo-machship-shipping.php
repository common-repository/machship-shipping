<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://machship.com/
 * @since             1.0.15
 * @package
 *
 * @wordpress-plugin
 * Plugin Name:       Machship Shipping
 * Plugin URI:        https://machship.com/
 * Description:       Machship Shipping for Woocommerce
 * Version:           1.5.7
 * Author:            Machship
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       machship-shipping
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

/**
 * Autoloading, via Composer.
 *
 * @link https://getcomposer.org/doc/01-basic-usage.md#autoloading
 */
require_once(__DIR__ . '/vendor/autoload.php');

use Woo_Machship_Shipping\Core\Woo_Machship_Activator;
use Woo_Machship_Shipping\Core\Woo_Machship_Deactivator;

// Runs during plugin activation
function woo_machship_shipping_activate() {
  Woo_Machship_Activator::woo_machship_activate();
}

// Runs during plugin deactivation
function woo_machship_shipping_deactivate() {
  Woo_Machship_Deactivator::woo_machship_deactivate();
}

register_activation_hook( __FILE__, 'woo_machship_shipping_activate' );
register_deactivation_hook( __FILE__, 'woo_machship_shipping_deactivate' );

add_action( 'plugins_loaded', 'woo_machship_shipping_run', 999 );

use Woo_Machship_Shipping\Woo_Machship_Init;
function woo_machship_shipping_run(){
  // Begin execution of the plugin.
  $init = new Woo_Machship_Init();
  $init->woo_machship_run();
}
