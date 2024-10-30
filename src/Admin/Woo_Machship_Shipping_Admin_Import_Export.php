<?php
namespace Woo_Machship_Shipping\Admin;

use Woo_Machship_Shipping\Woo_Machship_PluginData;
use Woo_Machship_Shipping\Common\Woo_Machship_Custom;
use Woo_Machship_Shipping\Common\Woo_Machship_Product_Settings;

if ( !defined( 'ABSPATH' ) ) exit;

class  Woo_Machship_Shipping_Admin_Import_Export {

  private $suffix = '-import-export';

  /**
   * The ID of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $plugin_name    The ID of this plugin.
   */
  private $plugin_name;

  /**
   * The version of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $version    The current version of this plugin.
   */
  private $version;

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_name       The name of this plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct() {
    $this->version = Woo_Machship_PluginData::woo_machship_plugin_version();
    $this->plugin_name = Woo_Machship_PluginData::woo_machship_plugin_text_domain();
  }

  public function woo_machship_enqueue_scripts_styles() {
    $screen = get_current_screen()->id;

    $enqueueKey = $this->plugin_name . $this->suffix;

    // Register styles
    wp_register_style( $enqueueKey, Woo_Machship_PluginData::woo_machship_plugin_dir_url(). 'assets/admin/css/woo-machship-shipping-import-export.css', array(), $this->version, 'all' );

    // Register scripts
    wp_register_script( $enqueueKey, Woo_Machship_PluginData::woo_machship_plugin_dir_url() . 'assets/admin/js/woo-machship-shipping-import-export.js', array( 'jquery' ), $this->version, true );

    if ($screen === 'woocommerce_page_woo-machship-import-export-box-data') {

      wp_localize_script($enqueueKey, 'woo_machship', array(
        'nonce' => wp_create_nonce('woocommerce-shipping-calculator')
      ));


      // Enqueue styles
      wp_enqueue_style( $enqueueKey );

      // Enqueue scripts
      wp_enqueue_script( $enqueueKey );
    }

  }

  /**
   * register menu for product box migration
   */
  public function woo_machship_manage_admin_menu() {

    // this is the menu for import/export box migration
    add_submenu_page(
      'woocommerce',
      __( 'Machship Import/Export Box Data', 'machship-shipping' ),
      __( 'Machship Import/Export Box Data', 'machship-shipping' ),
      'manage_options',
      'woo-machship-import-export-box-data',
      array( $this, 'woo_machship_import_export_box_data' )
    );

  }

  /**
   * import/export box data
   */
  public function woo_machship_import_export_box_data() {
    $path = Woo_Machship_PluginData::woo_machship_plugin_dir_path();
    require_once( $path. '/templates/admin/woo-machship-import-export-box-data-page.php' );
  }

  private function csvToArray($csvFile) {
    $file_to_read = fopen($csvFile, 'r');
    while (!feof($file_to_read) ) {
        $row = fgetcsv($file_to_read, 1000, ',');

        // skip if empty
        if (empty($row)) {
          continue;
        }

        $lines[] = $row;
    }
    fclose($file_to_read);

    return $lines;
  }

  public function woo_machship_import_box_data($request) {
    // import box data here
    check_ajax_referer( 'woocommerce-shipping-calculator', 'nonce' );

    // Goal :
    // 1. validate the import data and if it is csv
    // 2. identify the headers key as the name and value as the index
    // 3. set mapper for the machship product settings
    // 4. loop through the csv and map the data
    // 5. then begin import
    // 6. return the successful import total


    if (!isset($_FILES['import_data'])) {
      wp_send_json_error( array( 'message' => 'No data received' ) );
    }

    $csv = $this->csvToArray($_FILES['import_data']['tmp_name']);
    $importData = [];

    // Pop the first row in preparation for the headers
    $headerRow = array_shift($csv);
    $headers = [];
    foreach ($headerRow as $idx => $row) {

      // remove double quote
      $row = str_replace('"', '', $row);
      // remove special character
      $row = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $row);

      $headers[$row] = $idx;
    }

    // prepare the mapper for the machship product settings

    foreach ($csv as $row) {

      $newRow = [];
      $newItems = [];
      $productId = null;
      foreach ($headers as $hkey => $hidx) {

        // incase the cell is empty, skip
        if (empty($row[$hidx])) {
          continue;
        }

        // cleanup extra spaces
        $value = trim($row[$hidx]);

        if ($hkey === 'product_id') {
          $productId = (int)$value;
          continue;
        }

        // if cell is for box settings then store it in item
        if (in_array($hkey, ['height', 'width', 'length', 'weight', 'packaging_type'])) {
          $newItems[$hkey] = $value;
          continue;
        }

        $newRow[$hkey] = $value;
      }

      // if row doesn't have product id then lets skip it
      if (empty($productId)) {
        continue;
      }

      // if it exist then we need to merge the data (specifically the box settings)
      if (isset($importData[$productId])) {
        $importData[$productId]['_carton_items'] = array_merge($importData[$productId]['_carton_items'], [$newItems]);
        $importData[$productId]['_no_of_cartons'] += 1;
        continue;
      }

      // now we need to map the items
      $newRow['_carton_items'] = [$newItems];
      $newRow['_no_of_cartons'] = 1;

      // add existing data
      $importData[$productId] = $newRow;
    }

    // // import data is a set of settings per product id
    $importCtr = 0;
    foreach ($importData as $prodId => $settings) {

      $isImportSuccess = false;

      // must validate each settings before updating since sometimes client might have the wrong data
      if (isset($settings['is_machship_enabled'])) {
        update_post_meta($prodId, '_show_plugin', sanitize_text_field($settings['is_machship_enabled']) );
        $isImportSuccess = true;
      }

      if (isset($settings['_no_of_cartons'])) {
        update_post_meta($prodId, '_no_of_cartons', sanitize_text_field( $settings['_no_of_cartons'] ) );
        $isImportSuccess = true;
      }

      if (isset($settings['_carton_items'])) {
        update_post_meta($prodId, '_carton_items', sanitize_text_field(json_encode($settings['_carton_items'])) );
        $isImportSuccess = true;
      }

      if (isset($settings['international_harmonisation_code'])) {
        update_post_meta($prodId, '_international_harmonisation_code', sanitize_text_field($settings['international_harmonisation_code']) );
        $isImportSuccess = true;
      }

      if (isset($settings['international_country_manufacture'])) {
        update_post_meta($prodId, '_international_country_manufacture', sanitize_text_field($settings['international_country_manufacture']) );
        $isImportSuccess = true;
      }

      if (isset($settings['product_locations'])) {
        $prodLocs = is_string($settings['product_locations']) ? $settings['product_locations'] : json_encode($settings['product_locations']);
        update_post_meta($prodId, '_product_locations', sanitize_text_field($prodLocs));
        $isImportSuccess = true;
      }

      if ($isImportSuccess) {
        $importCtr++;
      }
    }

    return wp_send_json([
      'success' => true,
      'message' => 'Success! Imported ' . $importCtr . ' product' . ($importCtr > 1 ? 's' : '')
    ]);
  }

  // ---------------------------------- IMPLEMENTING EXPORT ---------------------------------------
  // - set ajax for generating nonce for export action => machship_shipping_export_box_data
  // - register new rest route for exporting box data => machship-shipping/api/v1/box-data/export
  // - after new route is exposed, it will be called by the frontend to download the exported box data csv

  public function woo_machship_export_box_data_generate_nonce() {
    check_ajax_referer( 'woocommerce-shipping-calculator', 'nonce' );
    $nonce = wp_create_nonce( 'machship-shipping-export-nonce' );

    $url = add_query_arg( array(
      'action' => 'machship_shipping_export_box_data',
      'nonce' => $nonce,
    ), admin_url( 'admin-ajax.php' ) );

    return wp_send_json([
      'success' => true,
      'new_nonce' => $nonce,
      'url' => $url
    ]);
  }

  public function woo_machship_export_box_data_api() {
    register_rest_route(
      'machship-shipping/api/v1',
      '/box-data/export',
      [
        'methods' => \WP_REST_Server::READABLE,
        'callback' => [$this, 'woo_machship_export_box_data'],
        'permission_callback' => '__return_true'
      ]
    );
  }

  public function woo_machship_export_box_data($request) {

    // Check if nonce is set
    if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'machship-shipping-export-nonce' ) ) {
      // Nonce is not valid
      wp_die( 'Security check failed ' . $_GET['nonce'], 'Error', array( 'response' => 403 ) );
    }

    // get the params here
    $includePublished = sanitize_text_field($_GET['include_published']);
    $includeMSEnabled = sanitize_text_field($_GET['include_enabled_ms']);

    $settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
    $products = wc_get_products([
      'posts_per_page' => -1
    ]);

    // this would include the variations
    $products = $this->getAllProducts($products);


    // set the header here
    $csvData = [];
    $csvData[0] = [
      'product_id',
      'sku',
      'product_name',
      'height',
      'width',
      'length',
      'weight',
      'packaging_type',

      'is_machship_enabled',
      'international_harmonisation_code',
      'international_country_manufacture',
      'product_locations'
    ];

    // then loop through the product and its box settings
    foreach ($products as $product) {

      if ($includePublished == 'no' && $product->get_status == 'publish') {
        // skip since we dont want to include published product
        continue;
      }

      $msProductSettings = new Woo_Machship_Product_Settings($product['id'], $settings);


      $settingsData = $msProductSettings->woo_machship_getProductSettings();

      // now we need to identify what will be included or not
      if ($includeMSEnabled == 'no' && $settingsData['show_plugin'] == 1) {
        // skip since we dont want to include MS enabled
        continue;
      }

      // HOTFIX! for some reason the carton items will have empty array [[]]
      if (!empty($settingsData['_carton_items'])) {
        $settingsData['_carton_items'] = json_decode($settingsData['_carton_items'], true);
        $settingsData['_carton_items'] = array_filter($settingsData['_carton_items']);
      }

      if (!empty($settingsData['_carton_items'])) {
        $cartonItems = $settingsData['_carton_items'];
        foreach ($cartonItems as $item) {
          $csvData[] = [
            $product['id'],
            $product['sku'],
            $product['name'],
            $item['height'] ?? '',
            $item['width'] ?? '',
            $item['length'] ?? '',
            $item['weight'] ?? '',
            $item['packaging_type'],
            $settingsData['show_plugin'],
            $settingsData['_international_harmonisation_code'],
            $settingsData['_international_country_manufacture'],
            json_encode($settingsData['_product_locations'])
          ];
        }
      } else {
        $csvData[] = [
          $product['id'],
          $product['sku'],
          $product['name'],
          '',
          '',
          '',
          '',
          '',
          $settingsData['show_plugin'],
          $settingsData['_international_harmonisation_code'],
          $settingsData['_international_country_manufacture'],
          json_encode($settingsData['_product_locations'])
        ];
      }


    }

    header("Content-type: text/csv", true, 200);
    header("Content-Disposition: attachment; filename=exported-box-settings.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $fp = fopen('php://output', 'wb');
    foreach ($csvData as $line) {
      // though CSV stands for "comma separated value"
      // in many countries (including France) separator is ";"
      fputcsv($fp, $line, ',');
    }
    fclose($fp);
    exit();
  }

  /**
   * This function serve as to get all the products and include the variations
   *
   * @param array $products
   * @return array
   */
  private function getAllProducts($products) {
    // purpose of this function is to get all the products and include the variations
    $allProducts = [];

    foreach ($products as $product) {
      // need to identify if its a normal product or not
      if ($product->get_type() == 'variable') {
        $variations = $product->get_available_variations();

        foreach ($variations as $variation) {
          $allProducts[] = [
            'id' => $variation['variation_id'],
            'sku' => $product->get_sku(),
            'name' => $product->get_name()
          ];
        }

        continue;
      }

      $allProducts[] = [
        'id' => $product->get_id(),
        'sku' => $product->get_sku(),
        'name' => $product->get_name(),
      ];
    }

    return $allProducts;

  }

}