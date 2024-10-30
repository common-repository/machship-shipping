<?php
namespace Woo_Machship_Shipping\Common;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Woo_Machship_Product_Settings {

  private $productID;
  private $parentId;

  private $product_settings;
  private $global_settings;

  public function __construct($productID, $settings, $parentID = 0) {
    $this->productID = $productID;
    $this->parentId = ($parentID == 0) ? $productID : $parentID;
    $this->global_settings = $settings;
  }

  /**
   * @return mixed
   */
  public function woo_machship_getProductSettings(){
    $parent_settings    = get_post_custom($this->parentId);
    $show_plugin        = isset($parent_settings['_show_plugin']) ? $parent_settings['_show_plugin'][0] : "";
    $show_plugin_value  = $show_plugin == "" && $this->global_settings['product_page'] != 'hide' ? '1' : $show_plugin; //if initial setup,show plugin on product page by default
    $show_plugin_value  = Woo_Machship_Custom::woo_machship_machship_product_status(Woo_Machship_Custom::woo_machship_get_shipping_settings(), null, $show_plugin_value); // we just need to overwrite the existing value

    // on create of new product: default to show plugin
    if (isset($_REQUEST['post_type']) && ($_REQUEST['post_type'] == 'product' && empty($_REQUEST['action']))) {
      $show_plugin_value = 1;
    }

    $this->product_settings['show_plugin']        = $show_plugin_value;
    $product_location                             = json_decode(isset($parent_settings['_product_locations']) ? $parent_settings['_product_locations'][0] : ""); //use the original maybe_unserialize for data that are already stored
    $product_settings                             = $this->parentId == $this->productID ? $parent_settings : get_post_custom($this->productID);
    $this->product_settings['_no_of_cartons']     = isset($product_settings['_no_of_cartons']) ? $product_settings['_no_of_cartons'][0] : "";
    $this->product_settings['_carton_items']      = isset($product_settings['_carton_items']) ? $product_settings['_carton_items'][0] : "";

    $this->product_settings['_international_harmonisation_code'] = $product_settings['_international_harmonisation_code'][0] ?? '';
    $this->product_settings['_international_country_manufacture'] = $product_settings['_international_country_manufacture'][0] ?? '';

    $this->product_settings['_pack_individually'] = $product_settings['_pack_individually'][0] ?? "";

    if(empty($product_location)){
      //use maybe_serialize, for old data before updating to json
      $product_location   = maybe_unserialize(isset($product_settings['_product_locations']) ? $product_settings['_product_locations'][0] : "");
    }

    //if empty data on product settings for warehouses
    if(!is_array($product_location) && empty($product_location) && !empty($this->global_settings)){
      $product_location = array_keys($this->global_settings['warehouse_locations']); //return all available warehouses
    }

    $this->product_settings['_product_locations'] = $product_location;
    return $this->product_settings;
  }
}