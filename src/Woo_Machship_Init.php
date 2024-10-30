<?php
namespace Woo_Machship_Shipping;

use Woo_Machship_Shipping\Admin\Woo_Machship_Shipping_Admin;
use Woo_Machship_Shipping\Admin\Woo_Machship_Shipping_Admin_Import_Export;
use Woo_Machship_Shipping\Common\Woo_Machship_Custom;
use Woo_Machship_Shipping\Frontend\Woo_Machship_Shipping_Public;
use Woo_Machship_Shipping\Woo_Machship_PluginData;
use Woo_Machship_Shipping\Core\Woo_Machship_Loader;

if ( !defined( 'ABSPATH' ) ) exit;
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woo_Machship_Shipping
 * @author     FusedSoftware <help@fusedsoftware.com>
 */
if ( ! class_exists( Woo_Machship_Init::class ) ) {
  class Woo_Machship_Init {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Woo_Machship_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;
    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
      $this->version = Woo_Machship_PluginData::woo_machship_plugin_version();
      $this->plugin_name = Woo_Machship_PluginData::woo_machship_plugin_text_domain();

      $this->woo_machship_load_dependencies();
      $this->woo_machship_define_admin_hooks();
      $this->woo_machship_define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Loader. Orchestrates the hooks of the plugin.
     * - i18n. Defines internationalization functionality.
     * - Admin. Defines all hooks for the admin area.
     * - Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function woo_machship_load_dependencies() {
      $this->loader = new Woo_Machship_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function woo_machship_define_admin_hooks() {
      $admin = new Woo_Machship_Shipping_Admin();

      $this->loader->woo_machship_add_action( 'admin_enqueue_scripts', $admin, 'woo_machship_enqueue_scripts_styles' );

      $this->loader->woo_machship_add_action( 'woocommerce_shipping_init', $admin, 'woo_machship_shipping_method_init' );

      $this->loader->woo_machship_add_filter( 'woocommerce_shipping_methods', $admin, 'woo_machship_shipping_method');
      // register product metabox
      $this->loader->woo_machship_add_action( 'add_meta_boxes', $admin, 'woo_machship_product_add_meta_box');
      // save product meta
      $this->loader->woo_machship_add_action( 'save_post', $admin, 'woo_machship_product_save_meta_data', 10, 2 );

      // product migration box
      $this->loader->woo_machship_add_action( 'admin_menu', $admin, 'woo_machship_manage_admin_menu', 70 );
      $this->loader->woo_machship_add_action( 'admin_action_woo_machship_product_box_update', $admin, 'woo_machship_product_box_update');

      // support for phone order plugin
      $this->loader->woo_machship_add_action( "wpo_set_cart_customer", $admin, 'set_backend_to_address_details' , 10, 3);
      $this->loader->woo_machship_add_action( 'woocommerce_checkout_order_processed', $admin, 'woo_machship_backend_order_process', 10, 3 );
      $this->loader->woo_machship_add_action( 'wpo_order_updated', $admin, 'woo_machship_backend_update_order', 10, 2);
      $this->loader->woo_machship_add_action( 'woocommerce_order_status_processing', $admin, 'woo_machship_status_change', 10, 1);
      $this->loader->woo_machship_add_action( 'woocommerce_order_status_on-hold', $admin, 'woo_machship_status_change', 10, 1);

      $this->loader->woo_machship_add_action( 'wp_ajax_machship_reset_cache', $admin, 'woo_machship_reset_cache' );


      $adminImportExport = new Woo_Machship_Shipping_Admin_Import_Export();
      // export/import box data
      $this->loader->woo_machship_add_action( 'admin_menu', $adminImportExport, 'woo_machship_manage_admin_menu', 70 );
      $this->loader->woo_machship_add_action( 'admin_enqueue_scripts', $adminImportExport, 'woo_machship_enqueue_scripts_styles');
      $this->loader->woo_machship_add_action( 'wp_ajax_machship_shipping_import_box_data', $adminImportExport, 'woo_machship_import_box_data' );
      $this->loader->woo_machship_add_action( 'wp_ajax_machship_shipping_exporting_box_data', $adminImportExport, 'woo_machship_export_box_data_generate_nonce' );
      $this->loader->woo_machship_add_action( 'wp_ajax_machship_shipping_export_box_data', $adminImportExport, 'woo_machship_export_box_data' );
      $this->loader->woo_machship_add_action( 'rest_api_init', $adminImportExport, 'woo_machship_export_box_data_api' );

      $this->loader->woo_machship_add_filter( 'woocommerce_default_address_fields', $admin, 'woo_machship_shipping_city_position');

      // cron
      $this->loader->woo_machship_add_action('woo_machship_cron_box_migration', $admin, 'woo_machship_process_box_migration', 10, 3);

      // get client token
      $this->loader->woo_machship_add_action('wp_ajax_generate_client_token', $admin, 'woo_machship_generate_client_token');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function woo_machship_define_public_hooks() {

      $settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();

      if (
        empty($settings) ||
        empty($settings['enabled']) ||
        $settings['enabled'] !== 'yes'
      ) {
        // stops init hook below when this is plugin is disabled for public
        return;
      }

      $public = new Woo_Machship_Shipping_Public();

      $this->loader->woo_machship_add_action( 'wp_enqueue_scripts', $public, 'woo_machship_enqueue_scripts_styles' );
      $this->loader->woo_machship_add_action( 'wp', $public, 'woo_machship_dynamic_hook' );

      $this->loader->woo_machship_add_action( 'async_logging', $public, 'woo_machship_async_logging_cb' );

      $this->loader->woo_machship_add_action( 'wp_ajax_get_location', $public, 'woo_machship_get_location' );
      $this->loader->woo_machship_add_action( 'wp_ajax_nopriv_get_location', $public, 'woo_machship_get_location' );
      $this->loader->woo_machship_add_action( 'wp_ajax_woo_machship_set_shipping_suburb', $public, 'woo_machship_set_shipping_suburb' );
      $this->loader->woo_machship_add_action( 'wp_ajax_nopriv_woo_machship_set_shipping_suburb', $public, 'woo_machship_set_shipping_suburb' );
      $this->loader->woo_machship_add_action( 'wp_ajax_woo_machship_set_fields_on_session', $public, 'woo_machship_set_fields_on_session' );
      $this->loader->woo_machship_add_action( 'wp_ajax_nopriv_woo_machship_set_fields_on_session', $public, 'woo_machship_set_fields_on_session' );
      $this->loader->woo_machship_add_action( 'wp_ajax_woo_machship_find_shipping_costs',$public, 'woo_machship_find_shipping_costs' );
      $this->loader->woo_machship_add_action( 'wp_ajax_nopriv_woo_machship_find_shipping_costs', $public, 'woo_machship_find_shipping_costs' );

      // ajax requests
      $this->loader->woo_machship_add_action( 'wp_ajax_get_location', $public, 'woo_machship_get_location' );
      $this->loader->woo_machship_add_action( 'wp_ajax_nopriv_get_location', $public, 'woo_machship_get_location' );
      $this->loader->woo_machship_add_action( 'wp_ajax_woo_machship_set_shipping_suburb', $public, 'woo_machship_set_shipping_suburb' );
      $this->loader->woo_machship_add_action( 'wp_ajax_nopriv_woo_machship_set_shipping_suburb', $public, 'woo_machship_set_shipping_suburb' );
      $this->loader->woo_machship_add_action( 'wp_ajax_woo_machship_set_fields_on_session', $public, 'woo_machship_set_fields_on_session' );
      $this->loader->woo_machship_add_action( 'wp_ajax_nopriv_woo_machship_set_fields_on_session', $public, 'woo_machship_set_fields_on_session' );
      $this->loader->woo_machship_add_action( 'wp_ajax_woo_machship_find_shipping_costs',$public, 'woo_machship_find_shipping_costs' );
      $this->loader->woo_machship_add_action( 'wp_ajax_nopriv_woo_machship_find_shipping_costs', $public, 'woo_machship_find_shipping_costs' );

      // set address
      $this->loader->woo_machship_add_filter( 'woocommerce_shipping_calculator_enable_state', $public, 'woo_machship_filter_shipping_state' );
      $this->loader->woo_machship_add_filter( 'woocommerce_shipping_calculator_enable_city', $public, 'woo_machship_filter_shipping_city' );
      $this->loader->woo_machship_add_filter( 'woocommerce_shipping_calculator_enable_postcode', $public,'woo_machship_filter_shipping_postcode' );

      $this->loader->woo_machship_add_action( 'woocommerce_after_order_notes', $public,'woo_machship_add_machship_checkout_hidden_field' );

      $this->loader->woo_machship_add_action( 'woocommerce_checkout_update_order_review', $public, 'woo_machship_checkout_update_order_review' );
      $this->loader->woo_machship_add_action( 'woocommerce_order_status_processing', $public, 'woo_machship_checkout_order_processed_func',  10, 1 );
      $this->loader->woo_machship_add_action( 'woocommerce_order_status_on-hold', $public, 'woo_machship_checkout_order_processed_func',  10, 1 );
      $this->loader->woo_machship_add_action( 'woocommerce_order_status_changed', $public, 'woo_machship_fusedship_webhook_send',  10, 1 );

      $this->loader->woo_machship_add_action( 'woocommerce_before_checkout_billing_form', $public, 'woo_machship_add_machship_checkout_residential_field_review_order', 10, 1);
      $this->loader->woo_machship_add_action( 'woocommerce_cart_totals_before_shipping', $public, 'woo_machship_add_machship_cart_residential_field', 10, 1);

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function woo_machship_run() {
      $this->loader->woo_machship_run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function woo_machship_get_plugin_name() {
      return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     */
    public function woo_machship_get_loader() {
      return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function woo_machship_get_version() {
      return $this->version;
    }

  }
}