<?php

namespace Woo_Machship_Shipping\Core;

// Abort if this file is called directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( Woo_Machship_Deactivator::class ) ) {
  /**
   * Fired during plugin deactivation
   *
   * This class defines all code necessary to run during the plugin's deactivation.
   **/
  class Woo_Machship_Deactivator {

    /**
     * Short Description.
     *
     * Long Description.
     */
    public static function woo_machship_deactivate() {

    }
  }
}