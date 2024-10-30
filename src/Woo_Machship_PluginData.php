<?php

namespace Woo_Machship_Shipping;

// Abort if this file is called directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( Woo_Machship_PluginData::class ) ) {
  /**
   * The basic information about this plugin, like its texts (text domain and display name) and file locations.
   */
  class Woo_Machship_PluginData {

    /**
     * Get this plugin's version.
     *
     * @TODO Keep current with readme.txt header and changelog + plugin header.
     *
     * @return string
     */
    public static function woo_machship_plugin_version(): string {
			return '1.5.7';
		}

  /**
   * Get this plugin's text domain.
   *
   * Must match the plugin's main directory and its main PHP filename.
   *
   * @return string
   */
  public static function woo_machship_plugin_text_domain(): string {
    return 'woo-machship-shipping';
  }

		/**
     * Get this plugin's text domain with underscores instead of hyphens.
     *
     * Used for saving options. Also useful for building namespaced hook names, class names, URLs, etc.
     *
     * @return string 'woo_machship_shipping'
     */
		public static function woo_machship_plugin_text_domain_underscores(): string {
    return str_replace( '-', '_', self::woo_machship_plugin_text_domain() );
  }

		/**
     * Get the plugin's display name.
     *
     * Useful for headings, for example.
     *
     * @return string
     */
		public static function woo_machship_get_plugin_display_name(): string {
    return esc_html_x( 'Woo_Machship_Shipping', 'Plugin name for display', 'machship-shipping' );
  }

		/**
     * Get this plugin's directory path, relative to this file's location.
     *
     * This file should be in `/src` and we want one level above.
     * Example: /app/public/wp-content/mu-plugins/woo-machship-shipping/
     *
     * @return string
     */
		public static function woo_machship_plugin_dir_path(): string {
    return trailingslashit( realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' ) );
  }

		/**
     * Get this plugin's directory URL.
     *
     * Example: https://example.com/wp-content/plugins/woo-machship-shipping/
     *
     * @return string
     */
		public static function woo_machship_plugin_dir_url(): string {
    return plugin_dir_url( self::woo_machship_main_plugin_file() );
  }

		/**
     * Get this plugin's basename.
     *
     * @return string 'woo-machship-shipping/woo-machship-shipping.php'
     */
		public static function woo_machship_plugin_basename(): string {
    return plugin_basename( self::woo_machship_main_plugin_file() );
  }

		/**
     * Get this plugin's directory relative to this file's location.
     *
     * This file should be in `/src` and we want two levels above.
     * Example: /app/public/wp-content/plugins/
     *
     * @return string
     */
		public static function woo_machship_all_plugins_dir(): string {
    return trailingslashit( realpath( self::woo_machship_plugin_dir_path() . '..' ) );
  }

		/**
     * Get this plugin's main plugin file.
     *
     * WARNING: Assumes the file exists - so don't make an epic fail!!!
     *
     * @return string
     */
		private static function woo_machship_main_plugin_file(): string {
    return self::woo_machship_plugin_dir_path() . self::woo_machship_plugin_text_domain() . '.php';
  }

	}
}
