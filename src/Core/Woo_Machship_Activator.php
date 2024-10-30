<?php

namespace Woo_Machship_Shipping\Core;

// Abort if this file is called directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( Woo_Machship_Activator::class ) ) {
  /**
   * Fired during plugin activation
   *
   * This class defines all code necessary to run during the plugin's activation.
   **/
  class Woo_Machship_Activator {

    /**
     * Short Description.
     *
     * Long Description.
     */
    public static function woo_machship_activate() {
      if (!class_exists( 'Woocommerce' ) ) {
        wp_die('This plugin requires a woocommerce installation');
      }
    }

  }
}