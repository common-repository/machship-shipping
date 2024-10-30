<?php
namespace Woo_Machship_Shipping\Admin;

use Woo_Machship_Shipping\API\Woo_Machship_Fusedship_API;
use Woo_Machship_Shipping\Woo_Machship_PluginData;
use Woo_Machship_Shipping\Common\Woo_Machship_Custom;
use Woo_Machship_Shipping\Common\Woo_Machship_Product_Settings;

if ( !defined( 'ABSPATH' ) ) exit;

class  Woo_Machship_Shipping_Admin {
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

  /**
   * initialize the custom shipping class
   */
  public function woo_machship_shipping_method_init() {
    if( ! class_exists('Woo_Machship_Load_Shipping_Method') ) {
      require_once( Woo_Machship_PluginData::woo_machship_plugin_dir_path(). 'Woo_Machship_Load_Shipping_Method.php' );
    }
  }

  /**
   * register shipping method
   * @param $methods
   * @return mixed
   */
  public function woo_machship_shipping_method($methods){
    // the string is a class name that we registered in woo_machship_shipping_method_init
    $methods['woo_machship_shipping'] = 'Woo_Machship_Load_Shipping_Method';
    return $methods;

  }

  public function woo_machship_shipping_city_position( $checkout_fields ) {
	$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();

	if(isset($settings['disable_checkout_suburb_search']) && $settings['disable_checkout_suburb_search'] != "yes"){
    $checkout_fields['state']['priority'] = 90;
    $checkout_fields['city']['priority'] = 80;
    $checkout_fields['postcode']['priority'] = 70;
	}

	return $checkout_fields;
  }

  /**
   * @param string $hook_sufix
   */
  public function woo_machship_enqueue_scripts_styles($hook_sufix = '' ) {
    $allow_screens = array(
      'woocommerce_page_wc-settings',
      'product',
      'woocommerce_page_woo-machship-product-box-migration'
    );
    $screen = get_current_screen()->id;
    // Current sections
    $settings_sec = isset( $_GET['section'] ) ? sanitize_text_field($_GET['section']) : '';

    // Register styles
    wp_register_style( $this->plugin_name, Woo_Machship_PluginData::woo_machship_plugin_dir_url(). 'assets/admin/css/woo-machship-shipping.css', array(), $this->version, 'all' );

    // Register scripts
    wp_register_script( $this->plugin_name, Woo_Machship_PluginData::woo_machship_plugin_dir_url() . 'assets/admin/js/woo-machship-shipping.js', array( 'jquery' ), $this->version, true );

    // localize script
    wp_localize_script($this->plugin_name, 'woo_machship', array(
      'nonce' => wp_create_nonce('woocommerce-shipping-calculator')
    ));

    if( in_array( $screen, $allow_screens) &&
      (
        $settings_sec == 'woo_machship_shipping' ||
        $hook_sufix == 'post.php' ||
        $hook_sufix == 'woocommerce_page_woo-machship-product-box-migration'
      ) ) {

      // Enqueue styles
      wp_enqueue_style( $this->plugin_name );

      // Enqueue scripts
      wp_enqueue_script( $this->plugin_name );

    }
  }

  /*
   * Just like Laravel this is where we preload the variables/data that we need
   * then load the view and use the variables/data we preloaded here
   */
  public function woo_machship_product_add_meta_box() {
    add_meta_box( 'woo_machship_meta', __( 'Machship Box Settings', 'machship-shipping' ), array($this, 'woo_machship_product_meta_fields'), 'product', 'normal', 'default' );
  }

  /**
   * template for product metabox
   */
  public function woo_machship_product_meta_fields() {
    global $post;
    $product_data = wc_get_product($post->ID);

    //get shipping settings
    $settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();

    // get mode of our plugin
    $is_dynamic = isset($settings['mode']) && $settings['mode'] === 'dynamic' ? true : false;

    // get product settings
    $pSettings = new Woo_Machship_Product_Settings($post->ID, $settings);
    $p_settings = $pSettings->woo_machship_getProductSettings();

    $show_plugin_value  = Woo_Machship_Custom::woo_machship_machship_product_status($settings, null, $p_settings['show_plugin']);
    $pack_individually = $p_settings['_pack_individually'];
    $carton_items		= $p_settings['_carton_items'];
    $no_of_cartons = $p_settings['_no_of_cartons'];
    $path = Woo_Machship_PluginData::woo_machship_plugin_dir_path();
    $product_locations = $p_settings['_product_locations'];

    $internationalFields = [
      'harmonisation_code' => $p_settings['_international_harmonisation_code'],
      'country_manufacture' => $p_settings['_international_country_manufacture'],
    ];

    $packageTypes = Woo_Machship_Custom::woo_machship_getCachedPackageTypes($settings);

    $countries = WC()->countries->get_countries();

    require_once(  $path. '/templates/admin/woo-machship-product-metabox.php' );
  }

  /**
   * @param $post_id
   * @param $post
   * @return mixed
   */
  public function woo_machship_product_save_meta_data($post_id, $post) {
    // Check the current post details
    if ( empty( $post_id ) || empty( $post ) || is_int( wp_is_post_revision( $post ) )
      || is_int( wp_is_post_autosave( $post ) ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      || ( empty( $_POST['post_ID'] ) || $post_id != sanitize_text_field($_POST['post_ID']) )
      || ( ! current_user_can( 'edit_post', $post_id ) ) )
    {
      return $post_id;
    };

    if( $post->post_type != 'product' ) return;


    if(isset($_POST['woo_machship_show_plugin'])){
      $show_plugin = sanitize_text_field($_POST['woo_machship_show_plugin']) == '1' ? 1 : 0;
      update_post_meta($post_id, '_show_plugin', $show_plugin );
    }

    // made it this way because ISSET is not working on checkbox
    // if the value is '0' it would skip this: to solve the problem I added else condition
    if(isset($_POST['pack_individually'])){
      $pack_individually = '1';
    }
    else {
      $pack_individually = '0';
    }
    update_post_meta($post_id, '_pack_individually', $pack_individually );

    // Update international fields
    if (isset($_POST['international_harmonisation_code'])) {
      update_post_meta($post_id, '_international_harmonisation_code', sanitize_text_field($_POST['international_harmonisation_code']) );
    }

    if (isset($_POST['international_country_manufacture'])) {
      update_post_meta($post_id, '_international_country_manufacture', sanitize_text_field($_POST['international_country_manufacture']) );
    }


    // Update cartons
    if( isset($_POST['no_of_cartons']) ) {

      $items_array   = array();
      for( $i=0; $i < sanitize_text_field( $_POST['no_of_cartons'] ); $i++ ) {
        $items_array[] = array(
          'height' => sanitize_text_field(floatval($_POST['carton_height'][$i])),
          'width'  => sanitize_text_field(floatval($_POST['carton_width'][$i])),
          'length' => sanitize_text_field(floatval($_POST['carton_length'][$i])),
          'weight' => sanitize_text_field(floatval($_POST['carton_weight'][$i])),
          'packaging_type' => sanitize_text_field($_POST['packaging_type'][$i])
        );

      }

      update_post_meta( $post_id, '_no_of_cartons', sanitize_text_field( $_POST['no_of_cartons'] ) );
      update_post_meta( $post_id, '_carton_items', json_encode($items_array) );

    } else if(isset($_POST['woo-machship-variation-no-of-cartons']) && !empty($_POST['woo-machship-variation-no-of-cartons'])){
      $variation_no_cartons = array_map( 'sanitize_text_field', $_POST['woo-machship-variation-no-of-cartons'] );
      foreach($variation_no_cartons as $key=>$var_cartons){
        $items_array   = array();

        for( $i=0; $i < sanitize_text_field( $var_cartons ); $i++ ) {

          $items_array[] = array(
            'height' => sanitize_text_field(floatval($_POST['carton_height'][$key][$i])),
            'width'  => sanitize_text_field(floatval($_POST['carton_width'][$key][$i])),
            'length' => sanitize_text_field(floatval($_POST['carton_length'][$key][$i])),
            'weight' => sanitize_text_field(floatval($_POST['carton_weight'][$key][$i])),
            'packaging_type' => sanitize_text_field($_POST['packaging_type'][$key][$i])
          );

        }

        update_post_meta( $key, '_no_of_cartons', sanitize_text_field((int)$var_cartons));
        update_post_meta( $key, '_carton_items', json_encode($items_array) );
      }
    }

    if($_POST['action'] != "inline-save"){
      if( isset($_POST['woo_machship_product_shipping_locations']) ) {
        $product_locations = array_map( 'sanitize_text_field', $_POST['woo_machship_product_shipping_locations'] );
        update_post_meta( $post_id, '_product_locations', json_encode($product_locations));
      } else {
        update_post_meta( $post_id, '_product_locations', array());
      }
    }

  }

  /**
   * register menu for product box migration
   */
  public function woo_machship_manage_admin_menu() {
    // this is the menu for the product box migration
    add_submenu_page(
      'woocommerce',
      __( 'Machship Product Box Migration', 'machship-shipping' ),
      __( 'Machship Product Box Migration', 'machship-shipping' ),
      'manage_options',
      'woo-machship-product-box-migration',
      array( $this, 'woo_machship_product_box_migration' )
    );

  }


  /**
   * migration box
   */
  public function woo_machship_product_box_migration(){
    $overwrite = get_option( 'woo-machship-product-box_overwrite_settings','no');
    $settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
    $packaging_types = Woo_Machship_Custom::woo_machship_getCachedPackageTypes($settings);
    $path = Woo_Machship_PluginData::woo_machship_plugin_dir_path();
    require_once( $path. '/templates/admin/woo-machship-product-migration-page.php' );
  }

  /**
   * update product box
   */
  public function woo_machship_product_box_update() {
    status_header(200);

    $overwrite = isset($_POST['woo-machship-product-box_overwrite_settings']) ? sanitize_text_field($_POST['woo-machship-product-box_overwrite_settings']) : "no";
    $package_type = sanitize_text_field($_POST['woo-machship-product-box_package_type']);
    $warehouse = array_map( 'sanitize_text_field', $_POST['woo_macship_product_shipping_locations']);

    if ( get_option('woo-machship-product-box_overwrite_settings') !== false ) {
      update_option( 'woo-machship-product-box_overwrite_settings', $overwrite );
    } else {
      $deprecated = null;
      $autoload = 'no';
      add_option( 'woo-machship-product-box_overwrite_settings', $overwrite, $deprecated, $autoload );
    }


    $result = $this->woo_machship_process_box_migration($overwrite, $package_type, $warehouse);


    wp_redirect(sanitize_url($_POST['_wp_http_referer'])."&product_box_migration=" . $result['status'] ."&message". $result['message']);

    exit;
  }

  /**
   * Processing box migration
   *
   * @param string $overwrite
   * @param string $package_type
   * @param array $warehouse
   * @return array ['status' => 'false', 'message' => 'validation fials']
   */
  public function woo_machship_process_box_migration($overwrite, $package_type, $warehouse) {


    if (is_null($overwrite) || empty($package_type) || empty($warehouse)) {
      error_log("[processing_box_migration] validation fails : " . json_encode([
        'overwrite' => $overwrite,
        'package_type' => $package_type,
        'warehouse' => $warehouse
      ]));
      return ['status' => 'false', 'message' => 'validation fials'];
    }

    $args = array(
      'status' => 'publish',
      'posts_per_page'=> -1

    );
    $settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
    $products = wc_get_products( $args );
    foreach($products as $product){
      $productID = $product->get_id();
      $pSettings = new Woo_Machship_Product_Settings($productID, $settings);
      $p_settings = $pSettings->woo_machship_getProductSettings();

      //get warehouses
      $existing_warehouses = $p_settings['_product_locations'];

      if(!empty($existing_warehouses)){
        if($overwrite == 'yes'){
          update_post_meta( $productID, '_product_locations', json_encode($warehouse));
        }else{
          //do not migrate
        }
      }else{
        update_post_meta( $productID, '_product_locations', json_encode($warehouse));
      }

      if($product->get_type() == 'simple'){

        //get carton items for each product
        $carton_items = json_decode($p_settings['_carton_items']);
        $dimensions = $product->get_dimensions(false);
        $weight = $product->get_weight();

        $items_array   = array();

        if(!empty($carton_items)){
          if($overwrite == 'yes'){

            $height = $dimensions['height'] ?? 1;
            $width = $dimensions['width'] ?? 1;
            $length = $dimensions['length'] ?? 1;

            $items_array[] = array(
              'height' => round(floatval($height), 2),
              'width'  => round(floatval($width)), 2,
              'length' => round(floatval($length), 2),
              'weight' => $weight,
              'packaging_type'=> $package_type,
            );

            update_post_meta( $productID, '_no_of_cartons', sanitize_text_field(1) );
            update_post_meta(  $productID, '_carton_items', json_encode($items_array) );
          }else{
            //do not migrate
          }

        }else{
          $items_array[] = array(
            'height' => round(floatval($dimensions['height']), 2),
            'width'  => round(floatval($dimensions['width']), 2),
            'length' => round(floatval($dimensions['length']), 2),
            'weight' => $weight,
            'packaging_type'=> $package_type
          );

          update_post_meta( $productID, '_no_of_cartons', sanitize_text_field(1) );
          update_post_meta( $productID, '_carton_items', json_encode($items_array) );
        }

      } else if($product->get_type() == 'variable'){
        $variations = $product->get_available_variations();

        foreach($variations as $variation){

          $dimensions = $variation['dimensions'];
          $weight = $variation['weight'];
          $carton_items = json_decode(get_post_meta( $variation['variation_id'], '_carton_items', true  ));

          $items_array   = array();

          if(!empty($carton_items)){
            if($overwrite == 'yes'){

              $height = $dimensions['height'] ?? 1;
              $width = $dimensions['width'] ?? 1;
              $length = $dimensions['length'] ?? 1;

              $items_array[] = array(
                'height' => round(floatval($height), 2),
                'width'  => round(floatval($width), 2),
                'length' => round(floatval($length), 2),
                'weight' => $weight,
                'packaging_type'=> $package_type,
              );

              update_post_meta( $variation['variation_id'], '_no_of_cartons', sanitize_text_field( 1 ) );
              update_post_meta( $variation['variation_id'] , '_carton_items', json_encode($items_array) );
            }else{
              //do not migrate
            }

          }else{
            $items_array[] = array(
              'height' => round(floatval($dimensions['height']), 2),
              'width'  => round(floatval($dimensions['width']), 2),
              'length' => round(floatval($dimensions['length']), 2),
              'weight' => $weight,
              'packaging_type'=> $package_type,
            );

            update_post_meta( $variation['variation_id'], '_no_of_cartons', sanitize_text_field( 1 ) );
            update_post_meta( $variation['variation_id'] , '_carton_items', json_encode($items_array) );
          }
        }
      }
    }

    return ['status' => 'true', 'message' => 'success'];
  }

  public function woo_machship_set_backend_to_address_details($cart_customer, $id, $customer_data) {
     $postcode = isset($customer_data['shipping_postcode']) ? $customer_data['shipping_postcode'] : $customer_data['billing_postcode'];
     $state = isset($customer_data['shipping_state']) ? $customer_data['shipping_state'] : $customer_data['billing_state'];
     $city = isset($customer_data['shipping_city']) ? $customer_data['shipping_city'] : $customer_data['shipping_city'];

     WC()->session->set( 'woo-machship-phone-order', 1);
     WC()->session->set( 'woo-machship-phone-order-tosuburb', sanitize_text_field($city));
     WC()->session->set( 'woo-machship-phone-order-tostate', sanitize_text_field($state));
     WC()->session->set( 'woo-machship-phone-order-topostcode', sanitize_text_field($postcode));
  }

  /**
   * @param $order_id
   * @param $posted_data
   * @param $order
   */
  public function woo_machship_backend_order_process($order_id, $posted_data, $order) {
    Woo_Machship_Custom::woo_machship_createOrderPayload($order, $order_id);
  }

  /**
   * @param $order
   * @param $cart
   */
  public function woo_machship_backend_update_order($order, $cart) {
    Woo_Machship_Custom::woo_machship_createOrderPayload($order, $order->get_id());
  }

  /**
   * @param $order_id
   */
  public function woo_machship_status_change($order_id) {
    $order = wc_get_order( $order_id );
    Woo_Machship_Custom::woo_machship_createOrderPayload($order, $order_id);
  }

  /**
   * To clear and reset machship cached data
   * eg. locations, carriers, services
   *
   * @return array response
   */
  public function woo_machship_reset_cache() {

    check_ajax_referer( 'woocommerce-shipping-calculator', 'nonce' );

    delete_transient( 'woo_machship_cached_locations' );
    delete_transient( 'woo_machship_cached_carriers' );
    delete_transient( 'woo_machship_cached_services' );
    delete_transient( 'woo_machship_cached_product_meta_keys' );

    wp_send_json(['success' => true]);
  }


  /**
   * use to generate or just get the client token from FusedShip
   * and to add default quote settings in FuseShip Live Rate settings (if not yet set)
   *
   * @return array response
   */
  public function woo_machship_generate_client_token() {
    check_ajax_referer( 'woocommerce-shipping-calculator', 'nonce' );

    $urllink = $_REQUEST['urlLink'] ?? "";

    // get all machship settings
    $settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();

    $integrationId = $settings['fusedship_id'] ?? 0;
    $integrationToken = $settings['fusedship_token'] ?? 0;

    if (empty($integrationToken) || empty($integrationId)) {
      return ['message' => 'fail', 'data' => ['message' => 'Integration Token or Id is not set']];
    }

    // we need to compare first if the current token is matches the old one we have
    // let's check if the fs_old_token is set, if not, we will use the current token
    $oldToken = !isset($settings['fs_old_token']) ? $integrationToken : $settings['fs_old_token'];
    if ($oldToken === $integrationToken) {
      return ['message' => 'success', 'data' => ['message' => 'Token is the same']];
    }

    // create payload to POST to FusedShip
    $payload = [
      'integration_token' => $integrationToken,
      'integration_id' => $integrationId,
      'urlLink' => $urllink
    ];

    // feed in the url link instead of token
    // that will serve as the token
    $fusedship = new Woo_Machship_Fusedship_API($integrationToken, $integrationId);
    $response = $fusedship->woo_machship_createOrUpdateSettings($payload);

    // convert the response to array
    $response = json_decode(json_encode($response), true);

    if (!empty($response['message']) && $response['message'] == 'success' && !empty($response['data']['client_token'])) {
      // overwrite the token
      $settings['fusedship_token'] = sanitize_text_field($response['data']['client_token']);
      $settings['fusedship_id'] = sanitize_text_field($response['data']['integration_id']);

      // let's create new field in WP settings called fs_old_token
      $settings['fs_old_token'] = sanitize_text_field($response['data']['client_token']);

      // update all the settings we have (because we don't have a single update function for this)
      update_option('woocommerce_woo_machship_shipping_settings', $settings);
    }

    return $response;
  }

}