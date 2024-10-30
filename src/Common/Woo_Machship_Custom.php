<?php
namespace Woo_Machship_Shipping\Common;

use Woo_Machship_Shipping\Woo_Machship_PluginData;
use Woo_Machship_Shipping\Common\Woo_Machship_Fusedship;
use Woo_Machship_Shipping\API\Woo_Machship_API;
use Woo_Machship_Shipping\Common\Woo_Machship_Product_Settings;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Woo_Machship_Custom {

  private static $ecRequest;
  private static $ecResponse;
  private static $msRequest;
  private static $msResponse;
  private static $allowLogging;

  public static $requestPostData = [];

  /**
   * @var string
   */
  private static $shipping_id = 'woo_machship_shipping';

  /**
   * @return mixed
   */
  public static function woo_machship_getAPIMode() {
    $path = Woo_Machship_PluginData::woo_machship_plugin_dir_path();
    $mode = require  $path. 'config/mode.php';
    return $mode['api_mode'];
  }

  /**
   * get shipping settings
   */
  public static function woo_machship_get_shipping_settings() {
    return get_option( 'woocommerce_woo_machship_shipping_settings' );
  }

  /**
   * Method getStoredRates
   *
   * This is a stored rates that requested initially
   * return this if there's no changes found in MS Request
   *
   * @return Array
   */
  public static function woo_machship_getMSStoredResponse() {
    if (WC()->session->__isset('woo_machship_stored_rates')) {
      return WC()->session->get('woo_machship_stored_rates');
    }
    return [];
  }

  public static function woo_machship_setMSStoredResponse($rates) {
    WC()->session->set('woo_machship_stored_rates', $rates);
  }

  /**
   * Method getMSSRequest
   *
   * @return Array
   */
  public static function woo_machship_getMSRequest() {
    if (WC()->session->__isset('woo_machship_ms_request')) {
      return WC()->session->get('woo_machship_ms_request');
    }
    return [];
  }

  public static function woo_machship_setMSRequest($msRequest) {
    WC()->session->__unset('woo_machship_ms_request');
    WC()->session->set('woo_machship_ms_request', $msRequest);
  }

  private static function woo_machship_setAllowLogging($bool) {
    self::$allowLogging = $bool;
  }

  private static function woo_machship_getAllowLogging() {
    return self::$allowLogging;
  }

  /**
   * @param $items
   * @param $settings
   * @return int
   */
  public static function woo_machship_count_disabled_items($items, $settings) {

    $disabled_item = 0;
    foreach($items as $item){
      $parent_product = $item['product_id'];

      $show_plugin  = self::woo_machship_machship_product_status($settings, $parent_product);

      $show_plugin_value  = $show_plugin == "" ? '1' : $show_plugin; //if initial setup,show plugin on product page by default

      if($show_plugin_value == "0"){
        $disabled_item++;
      }
    }
    return $disabled_item;
  }

  /**
   * @return bool
   */
  public static function woo_machship_check_if_active(){
    $active_shipping = self::woo_machship_get_zone_active_shipping();

    if(!empty($active_shipping) && in_array(self::$shipping_id, $active_shipping)){
      return true;
    } else {
      return false;
    }
  }

  /**
   * @return array
   */
  public static function woo_machship_get_zone_active_shipping(){
    $shipping_packages =  \WC()->cart->get_shipping_packages();
    // Get the WC_Shipping_Zones instance object for the first package
    $shipping_zone = wc_get_shipping_zone( reset( $shipping_packages ) );
    $zone_id   = $shipping_zone->get_id(); // Get the zone ID
    $zones = \WC_Shipping_Zones::get_zones();
    $shipping_methods = isset($zones[$zone_id]) ? $zones[$zone_id]['shipping_methods']: array();
    $active_methods = array();

    if(!empty($shipping_methods)){
      foreach($shipping_methods as $shipping_method){
        if($shipping_method->enabled == 'yes'){
          $active_methods[] = $shipping_method->id;
        }
      }
    }

    return $active_methods;
  }

	/**
	 * get quotes from machship
	 * @params $items
	 */
	public static function woo_machship_cart_quote_request($items)
	{

		global $woocommerce;

		$settings = self::woo_machship_get_shipping_settings();
		$post_data = array();
		$data_items = array();

		$request = [];
		foreach ($_REQUEST as $rkey => $rvalue) {
		  $request[$rkey] = sanitize_text_field($rvalue);
		}

		self::$ecRequest['result'] = $request;
		self::$ecRequest['request_at'] = (new \DateTime(current_datetime()->format('Y-m-d H:i:s')))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

		if (isset($request['post_data'])) {
			parse_str(sanitize_text_field($request['post_data']), $post_data);
		}

		$post_data = empty($post_data) ? WC()->session->get('woo-machship-post_data') : $post_data;

        // check if the ship_to_different_address is set
        // then we need to overwrite the billing address to use the shipping address
        if (!empty($post_data['ship_to_different_address'])) {
        self::$requestPostData = $post_data;
        }

		$toLocationID = !empty($post_data['ToLocationID']) ? sanitize_text_field($post_data['ToLocationID']) : WC()->session->get('woo-machship-tolocationID');
		WC()->session->set('Woo_Machship_shippingPrices_Display', '');

		$packages =  $woocommerce->cart->get_shipping_packages();
		foreach ($packages as $package_key => $package) {
			$session_key  = 'shipping_for_package_' . $package_key;
			$stored_rates = WC()->session->__unset($session_key);
		}

		if (isset(WC()->session)) {
			if (WC()->session->__isset('Shipping_Method_Selected')) {
				WC()->session->set('Shipping_Method_Selected',  array());
			}
		}

		$is_residential = false;
		$is_phone_order = false;
		if (isset($post_data['is_residential']) or WC()->session->get('is_residential')) {
			$is_residential = isset($post_data['is_residential']) ? $post_data['is_residential'] : WC()->session->get('is_residential');
			WC()->session->set('is_residential', $is_residential);
		} else {
			$is_phone_order = WC()->session->get('woo-machship-phone-order');
			$is_residential = $is_phone_order && $settings['has_residential'] == "yes";
		}

    // if dynamic mode
    // we need to get the item's complete information
    if (!empty($settings['mode']) && $settings['mode'] === 'dynamic') {
      foreach ($items as $idx => $item) {
        $product = wc_get_product($item['product_id']);
        if ($product) {
          $items[$idx]['product_data'] = $product->get_data();
        }
      }
    }

    try {
		  $get_routes = self::woo_machship_send_routes_request($settings, $toLocationID, $items, $is_residential);

      // We have to handle dynamic here for cart/checkout
      if (!empty($get_routes['mode']) && $get_routes['mode'] === 'dynamic') {

        return $get_routes['woo_shipping_methods'];
      }


      $final_rates = [];

      if (!empty($get_routes['result'])) {
        foreach ($get_routes['result'] as $routeResult) {

          if(!is_array($routeResult)) {
            continue; //had error where it was a string that throws issue in woo_machship_processRates
          }

          if($is_phone_order && $settings['has_residential'] == "yes") {
            $routeResult['prefix_resi'] = true; // this is a missing (got deleted) starting from version 1.1.2
          }
          $processRates = self::woo_machship_processRates($routeResult);

          $final_rates = self::woo_machship_mergeRates($final_rates, $processRates['result']);


        }

        if (!empty($get_routes['result'][0])) {
          $getResult = call_user_func_array('array_merge', $get_routes['result'][0]);
          $getResult = json_decode(json_encode($getResult), true);
          $totalSellPrice = $getResult['consignmentTotal']['totalSellPrice'] ?? '';
          if (!empty($totalSellPrice)) {
          // remove sensitive info
          $getResult['consignmentTotal'] = [
            'totalSellPrice' => $totalSellPrice
          ];
          }

          // this hook can use to overwrite the rates total price
          // getResult shows the carrier group details, despatch details and customer selected location
          $final_rates = apply_filters('machship_set_final_rates', $final_rates, $getResult);
        }

        $final_rates = self::woo_machship_combineRates($final_rates);

      } else {
        WC()->session->__unset('shipping_machship_methods');
      }

      // we can still logs the empty final rates in sync.fusedship
      self::$ecResponse['result'] = $final_rates;
      self::$ecResponse['response_at'] = (new \DateTime(current_datetime()->format('Y-m-d H:i:s')))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

      // saving log in async
      if (
          !empty($settings['fusedship_id']) &&
          !empty($settings['fusedship_token']) &&
          self::woo_machship_getAllowLogging()
      ) {
        self::woo_machship_asyncProccessLogging(true);
      }
      else {
        // reset
        self::woo_machship_asyncProccessLogging(false);
      }

		  return $final_rates;
    } catch(\Exception $e) {
      error_log("[WooMachship][cart_quote_request] error " . $e->getMessage() . " " . $e->getTraceAsString());
      return [];
    }
	}

  public static function woo_machship_setAddress($toLocation) {
    if (self::$requestPostData) {
      return [
        'postcode' => self::$requestPostData['shipping_postcode'],
        'suburb' => self::$requestPostData['shipping_city'],
        'state' => self::$requestPostData['shipping_state']
      ];
    }
    return $toLocation;
  }

  private static function woo_machship_asyncProccessLogging($bool)
  {
    $data = [
      'ms' => [
        'request'  => self::$msRequest,
        'response' => self::$msResponse,
      ],
      'ec' => [
        'request'  => self::$ecRequest,
        'response' => self::$ecResponse,
      ],
    ];

    if ($bool) {
      wp_schedule_single_event(time(), 'async_logging', [$data]);
    }
    else {
      wp_clear_scheduled_hook( 'async_logging', array( [$data] ));
    }
  }

	private static function woo_machship_processRates($routeResult) {
    $output = '';
    $found_rates = array();
    $save_to_session = array();
    $settings = self::woo_machship_get_shipping_settings();
    $is_phone_order = WC()->session->get('woo-machship-phone-order');
    $allMethods = [];

    usort($routeResult, function ($a, $b) {
      // makesure these are numeric values
      return floatval($a['carrierGroupDetails']['order']) - floatval($b['carrierGroupDetails']['order']);
    });

    foreach ($routeResult as $option) {
      $session_data = array();
      $price_data = self::woo_machship_show_pricing_for_shipping($option, 'cart',  0, $settings);
      if (count($price_data) > 0) {

      $mthds = array(
        'CarrierName'   => $price_data['CarrierName'],
        'CarrierId'     => $price_data['CarrierId'],
        'CarrierServiceId' => $price_data['CarrierServiceId'],
        'CarrierServiceName' => $price_data['CarrierServiceName'],
        'Tax'           => $price_data['Tax'],
        'Total'         => $price_data['Total'],
        'FromCompanyLocationId' => $option['fromCompanyLocationId'],
        'ToLocationId' => $price_data['ToLocationId'],
        'CompanyId'   => $option['companyId'],
        'QuestionIds' => json_encode($option['carrierGroupDetails']['question_ids']),
        'MethodName' => $option['carrierGroupDetails']['group_name'], // for comparison of selected shipping method
      );

      // handle the despatch ETA properly
      // for BlastOne - otherwise unused
      if (!empty($option['despatchOptions'])) {
        $despatchETA =  $option['despatchOptions'][0]->etaLocal ?? $option['despatchOptions'][0]['etaLocal'];
        $mthds['warehouse_machship_delivery_time'] = date("m/d/Y", strtotime($despatchETA)); // additional list of meta to save
      }

      $shippingDescription = $option['carrierGroupDetails']['group_description'];
      $title = $option['carrierGroupDetails']['group_name'];
      $session_data['title'] = $title . ' $' . number_format($price_data['Total'], 2);
      $session_data['description'] = $shippingDescription;
      $session_data['address'] = '';

      $rate_id = 'woo_machship_shipping:' . $option['carrierGroupDetails']['group_name'];
      $save_to_session[$rate_id] = $session_data;

      $shipping_rate = $price_data['Total'];

      if ($is_phone_order && $settings['has_residential'] == "yes") {
        if(empty($option['prefix_resi'])) {
          $title = "Business - " . $title;
        } else {
          $title = "Residential - " . $title;
        }
      }

      $allMethods[$rate_id] = $mthds;
      $arr = array(
        'id'    => $rate_id,
        'label'    => $title,
        'cost'    => $shipping_rate,
        'meta_data'  => $mthds,
        'calc_tax' => 'per_order'
      );

      $found_rates[$rate_id] = $arr;

      $output .= "<div class='shipping-type-result woo-machship-sqf-fields'>";
      $output .= self::woo_machship_print_calculated_advance_margin_product($price_data, $title);
      $output .= "<p>" . $shippingDescription . "</p></div>";
      }


    }
    WC()->session->set('Shipping_Method_Data', $allMethods);

    $index = implode('_', array_keys($routeResult));

    return ['index' => $index, 'result' => $found_rates];
  }

  private static function woo_machship_mergeRates($finalRates, $newRates) {
    $toAddRates = [];

    if (empty($finalRates)) {
      $finalRates[] = $newRates;
      return $finalRates;
    }

    foreach ($finalRates as $fKey => &$rates) {
      // get all the group names only
      $groupNames = array_keys($rates);
      foreach ($rates as $key => &$rate) {

        // get the group names from incoming rates
        $groupRateNames = array_keys($newRates);

        // check if there's different or new group name
        $hasNewGroups = array_diff($groupRateNames, $groupNames);

        // new rates can contain multiple groups
        foreach ($newRates as $nkey => $newRate) {
          if ($nkey === $key) {
            // and add up the cost
            $rate['cost'] += $newRate['cost'];
            $exist = true;
          }

          if ($hasNewGroups) {
            foreach ($hasNewGroups as $gName) {
              $genRate = null;
              if ($gName == $nkey) {
                $keyName = (string) $gName;
                $genRate = [
                    $keyName => $newRates[$gName]
                ];
                // check if toAdd has already the groupName
                if (empty($toAddRates)) {
                  $toAddRates[] = $genRate;
                }
                else {
                  foreach ($toAddRates as $aKey => $addRate) {
                    if ($aKey != $gName) {
                      $toAddRates[] = $genRate;
                    }
                  }
                }
              }
            }
          }
        }
      }

      // avoid memory leak
      unset($rate);
    }

    // avoid memory leak
    unset($rates);

    // add/merge rates after the loop - redundancy issue that's why it's outside
    if (!empty($toAddRates)) {
        $finalRates = array_merge($finalRates, $toAddRates);
    }

    return $finalRates;
  }


  private static function woo_machship_combineRates($finalRates) {

    $rateGrouped = [];
    foreach ($finalRates as $rates) {
      $keys = array_keys($rates);
      if (empty($keys)) { continue; }

      foreach ($keys as $key) {

        $cost = 0;

        // if rate has been created already
        if (!empty($rateGrouped[$key])) {
          $cost = $rateGrouped[$key]['cost'];
        }

        $cost += $rates[$key]['cost'];
        $label = $rates[$key]['label'];
        $id = $rates[$key]['id'];

        $rateGrouped[$key] = [
          'id' => $id,
          'cost' => $cost,
          'label' => $label
        ];
      }
    }

    // then we need to combine groups

    $finalRateGroup = [];
    foreach ($finalRates as $indexRate => $rates) {
      $keys = array_keys($rates);

      // be mindful to break here so it wont reset finalRategroup
      if (empty($keys)) {
        continue;
      }

      // we have to temporary store $finalRateGroup because we use it to loop as we combine every batch
      $lastFinalRate = $finalRateGroup;
      // then we have to clean it up so old rates will get remove
      $finalRateGroup = [];
      foreach ($keys as $key) {

        if (empty($rateGrouped[$key])) {
          // bug lets reset final group to original
          $finalRateGroup = $lastFinalRate;
          continue;
        }

        if ($indexRate === 0) {
          $finalRateGroup[] = $rateGrouped[$key];
          continue;
        } else {

          foreach ($lastFinalRate as $lfr) {
          $finalRateGroup[] = [
            'id' => implode(' / ', [$lfr['id'], $rateGrouped[$key]['id']]),
            'cost' => $lfr['cost'] + $rateGrouped[$key]['cost'],
            'label' => implode(' / ', [$lfr['label'], $rateGrouped[$key]['label']])
          ];
          }
        }


      }

    }

    return $finalRateGroup;
  }

	/**
	 * get quotes from machship
	 * @params $items
	 */
	public static function woo_machship_send_routes_request($settings, $toLocationID, $items, $is_residential)
	{
		global $woocommerce;
		$data_items = array();
		if (!empty($items)) {

			$is_dynamic = !empty($settings['mode']) && $settings['mode'] === 'dynamic';

			$matched_warehouse = array();
			$other_warehouse = array();

			$i = 0;

			$packageTypes = maybe_unserialize(self::woo_machship_getCachedPackageTypes($settings));


			foreach ($items as $idx => $item) {

				$item = apply_filters('machship_product_filter', $item);

				if(!$item) {
					continue;
				}

				$item['product_id'] = $item['product_id'] ?? $item['id'];

				if (isset($item['variation_id']) && $item['variation_id'] > 0) { //it is a variation
					$parent_product = $item['product_id'];
					$product_id  = $item['variation_id'];
				} else {
					$parent_product = 0;
					$product_id = $item['product_id'];
				}

				$pSettings = new Woo_Machship_Product_Settings($product_id, $settings, $parent_product);
				$p_settings = $pSettings->woo_machship_getProductSettings();

				$show_plugin_value  = self::woo_machship_machship_product_status($settings, null, $p_settings['show_plugin']); // if initial setup,show plugin on product page by default

				if ($show_plugin_value == "0") {
					continue; //exclude products where machship quote box is hidden
				}

				$items[$idx]['international_data'] = [
					'harmonisation_code' => $p_settings['_international_harmonisation_code'],
					'country_manufacture' => $p_settings['_international_country_manufacture'],
				];

				$item_location = is_array($p_settings['_product_locations']) ? $p_settings['_product_locations'] : array();
				$unmatched_warehouse = [];

				if ($i == 0) {
				  $matched_warehouse = $item_location;
				} else {
				  // we have to determine here if there is a new warehouse

				  // if there is no array intersect here meaning its from a different warehouse
				  if (empty(array_intersect($item_location, $matched_warehouse))) {
					  // so we need to store them to add a separate request for them
					  $unmatched_warehouse = $item_location;
				  }
				}

				// get product box dimensions
				$rows = $p_settings['_carton_items'];
				$productName = get_the_title($product_id);

				if (!empty($rows)) {
					$locs = self::woo_machship_get_selected_warehouse($item_location, $settings);
					$loc_ids = array();
					foreach ($locs as $lid) {
						$loc_ids[] = $lid['location_id'];
					}

					$rows = json_decode($rows);

					$rows = apply_filters('machship_product_box_filter', $rows, $product_id, $item['quantity']);

					$boxes = [];
					$items[$idx]['loc_ids'] = $loc_ids;

					foreach ($rows as $row) {
						$row->ItemType = (isset($row->packaging_type)) ? intval($row->packaging_type) : 1;
						$row->quantity = isset($row->quantity) ? $row->quantity : $item['quantity'];

						$rowItem = array(
							"itemType" => $row->ItemType,
							"name" => self::woo_machship_trim_text($productName, 50),
							"quantity" => $row->quantity ?? 0,
							"height" => round(floatval($row->height) ?? 0,2),
							"weight" => round(floatval($row->weight) ?? 0,2),
							"length" => round(floatval($row->length) ?? 0,2),
							"width" => round(floatval($row->width) ?? 0,2),
							"loc_ids" => $loc_ids
						);

						// capture boxes for dynamic
						if ($is_dynamic) {
							$box = array_merge($rowItem, [
								// we need to map package type because only woomachship understand this
								'itemType' => $packageTypes[$row->ItemType]
							]);

							// unset unnecessary data
							unset($box['loc_ids']);
							//it's always 1 for dynamic mode as there is no box count field in woo
							$box['quantity'] = 1;

							$boxes[] = $box;
						}

						// store data items normally if this is empty
						if (empty($unmatched_warehouse)) {
							$data_items[] = $rowItem;
					 	} else {
							$other_warehouse[implode('-', $unmatched_warehouse)][] = $rowItem;
						}
					}

					if (!empty($boxes)) {
						$items[$idx]['boxes'] = $boxes;
					}

				}
				$i++;
			}

			// apply filter to all items
			$data_items = apply_filters('machship_all_box_filter', $data_items);

			// if data items and is not dynamic mode
			if (empty($data_items) && !$is_dynamic) {
				return ['error' => true, 'code' => 1, 'message' => 'No items with boxes'];
			}

			// handle the dynamic fusedship here
			// identify mode from settings
			// if dynamic just request it directly to fusedship
			// else continue
			if ($is_dynamic) {
				$woomachFusedship = new Woo_Machship_Fusedship($settings);
				$woomachFusedship->woo_machship_init();
				$result = $woomachFusedship->woo_machship_quoteRequest($items, $is_residential);

				$result = json_decode(json_encode($result), true);

        // handle the error properly here
        if (empty($result['data'])) {
          return [
            'error' => true,
            'result' => $result,
            'output' => '',
            'woo_shipping_methods' => '',
            'mode' => 'dynamic'
          ];
        }

        $result = self::setQuoteResponseDesc($settings, $result);

				return [
					'error' => false,
					'result' => $result,
					'output' => $woomachFusedship->woo_machship_renderQuotes($result['data']),
					'woo_shipping_methods' => $woomachFusedship->woo_machship_mapWCShippingMethods($result['data']),
					'mode' => 'dynamic'
				];
			}

      // NOTE! BELOW TEMPORARY COMMENT FOR TEST
			$result = [self::woo_machship_get_quotes_from_items_settings_location($data_items, $settings, $matched_warehouse, $toLocationID, $is_residential)];


			if (!empty($other_warehouse)) {

				foreach ($other_warehouse as $whkey => $oItems) {

					$result[] = self::woo_machship_get_quotes_from_items_settings_location($oItems, $settings, explode('-', $whkey), $toLocationID, $is_residential);
				}
			}

      $result = self::setQuoteResponseDesc($settings, $result);

			return ['error' => false, 'result' => $result];

		}

		if (empty($data_items)) {
			return ['error' => true, 'code' => 0, 'message' => 'No items'];
		}

	}

  private static function setQuoteResponseDesc($settings, $result) {
    // this is only available for cart and checkout
    if (is_cart() || is_checkout()) {
      // add isset validation - the client might haven't have the fields yet
      if (isset($settings['fusedship_quote_description_hideshow']) && ($settings['fusedship_quote_description_hideshow'] == 'yes' && isset($result['data']))) {
        foreach ($result['data'] as $key => $value) {
          $result['data'][$key]['method_description'] = "";
        }
      }
    }

    return $result;
  }

  private static function woo_machship_get_quotes_from_items_settings_location($data_items, $settings, $warehouse, $toLocationID, $isResidential) {

    $productWeights = [];

    foreach($data_items as $it) {
      $productWeights[] = $it["weight"];
    }

    arsort($productWeights);
    $carriers = self::woo_machship_checkCarrierGroupProductBelong($productWeights, $settings['carrier_groups']);

    $warehouse_loc =  self::woo_machship_get_selected_warehouse($warehouse, $settings);
    $warehouse_loc = apply_filters('machship_warehouses_filter', $warehouse_loc);

    if (empty($warehouse_loc) && empty($carriers)) { //check warehouse is not empty
      return ['error' => true, 'code' => 2, 'message' => 'No valid carriers or warehouses'];
    }

    $quotes = self::woo_machship_generateMSRequest($carriers, $warehouse_loc, $toLocationID, $data_items, $settings, $isResidential);
    return self::woo_machship_getIncludedServicesGroupByCarriers($quotes['quotes'], $settings, array_keys($carriers), $quotes['hasQuestionIds'], $isResidential);

  }

  /**
   * @param string $page
   * @return string
   */
  public static function woo_machship_shipping_quote_form_html(string $page = null, $settings) {
    $page = self::woo_machship_validateParam($page, '');

    if( empty($settings['enabled']) || $settings['enabled'] != 'yes' ) return;

    ob_start();
    global $post, $product, $woocommerce;
    $path = Woo_Machship_PluginData::woo_machship_plugin_dir_path();
    $uniqueID = 'woo-machship-shipping-quote-form-' . uniqid();
    $class = 'woo-machship-shipping-quote-form';
    $title = !empty($settings['widget_title']) ? $settings['widget_title'] : 'Quick Quote';
    $desc = !empty($settings['widget_description']) ? $settings['widget_description'] : 'Enter your postcode and suburb to generate your quote';
    $idWrapper = 'woo_machship_checkout_form';
    $productid = '';
    $has_residential = !empty($settings['has_residential'])?$settings['has_residential']:"no";

    if( is_product() ) {
      $idWrapper = 'woo_machship_single_product_form';
      $productid	= $post->ID;
      $class .= ' sqf-single-product-page';
    } elseif( is_cart() ) {
      $class .= ' sqf-cart-page';
      $idWrapper = 'machship_cart_form';
    } elseif( is_checkout() ) {
      $class .= ' sqf-checkout-page';
    }

    // Check if not product
    if( ! is_product() ) {
      $items = $woocommerce->cart->get_cart();

      foreach( $items as $item ) {
        $product_id = $item['product_id'];
        $show_plugin_value  = self::woo_machship_machship_product_status($settings, $product_id);

        if($show_plugin_value == "0"){
          continue; //exclude products where woo machship quote box is hidden
        }

        if(isset($item['variation_id']) && $item['variation_id'] > 0){
          $rows = get_post_meta( $item['variation_id'], '_carton_items', true );
        }else{
          $rows = get_post_meta( $product_id, '_carton_items', true );
        }
      }
    }

    $toLocationId = WC()->session->get( 'woo-machship-tolocationID' );
    $toSuburb = WC()->session->get( 'woo-machship-tosuburb' );
    $toState = WC()->session->get( 'woo-machship-tostate' );
    $toPostcode = WC()->session->get( 'woo-machship-topostcode' );

    if (is_product()) {
      $shippingPrices_Display = '';
      if(isset($settings['product_page']) && $settings['product_page'] == "popup"){
        require_once(  $path. 'templates/public/template-quote-form/popup.php' );
      }else{
        require_once(  $path. 'templates/public/template-quote-form/default.php' );
      }

    } else {
      $post_data = WC()->session->get('woo-machship-post_data');
      $shippingPrices_Display = '';

      if (!empty($post_data)) {
        $shippingPrices_Display = WC()->session->get( 'Woo_Machship_shippingPrices_Display' );
      }

      require_once(  $path. 'templates/public/template-quote-form/cart.php' );
    }

    return ob_get_clean();
  }

  /**
   * @param $settings
   * @return array
   */
  public static function woo_machship_getIncludeServices($settings) {
    $services = [];
    if ($settings['carrier_groups']) {
      foreach ($settings['carrier_groups'] as $key=>$group) {
        $services[$key]['group_index'] = $key;
        $services[$key]['group_name'] = $group['group_name'];
        $services[$key]['group_description'] = $group['group_description'];

        if (isset($group['carrier_services']['service_id'])) {
          $services[$key]['services'] = $group['carrier_services']['service_id'];
        }
        $services[$key]['machship_surcharges'] = $group['machship_surcharges'];
        $services[$key]['carrier_group_order'] = $group['carrier_group_order'];
        $services[$key]['question_ids'] = (!empty($group['machship_question_ids']))?explode(",",$group['machship_question_ids']):[];
        $services[$key]['exclude_for_residential'] = $group['exclude_for_residential'];
      }
    }

    return $services;
  }

  /**
   * @param $routes
   * @param $settings
   * @return array
   */
  public static function woo_machship_getIncludedServicesGroupByCarriers($whLocation, $settings, $carrierGroupsToBeIncluded = array(), $withQuestionIds = array(), $is_residential = false) {
    $carriers = [];
    foreach($whLocation as $whKey => $carrierGroupRoutes) {
      // if multiple response due to multiple requests
      $includedGroups = $carrierGroupsToBeIncluded;
      if (is_array($carrierGroupRoutes)) {
        // check which one has question ids
        //print_r($withQuestionIds);
        // withQuestionIds is the carrier Group with question id on
        foreach($withQuestionIds as $gid) {

          if(isset($carrierGroupRoutes[$gid]) && isset($carrierGroupRoutes[$gid]->object)) {
            if (isset($carrierGroupRoutes[$gid]->object->routes)) {
              foreach($carrierGroupRoutes[$gid]->object->routes as $k => $route) {

                // if the price for this service is LESS THAN what is attach to the carrier - then this would return data
                $carrierData = self::woo_machship_addCarrierToList($carriers, $route, $settings, $includedGroups, $gid, $is_residential);

                if ($carrierData){
                  foreach($carrierData as $carrierDetails) {
                    $carriers[$carrierDetails['carrier_index']] = $carrierDetails['data'];
                  }
                }
              }
            }

            // delete the carrier group routes so it won't repeat - even if the $carrierGroupRoutes[$gid]->object->routes is null
            unset($carrierGroupRoutes[$gid]);
            if (($gKey = array_search($gid, $includedGroups )) !== false) {
              unset($includedGroups[$gKey]);
            }
          }

        }

        #echo "<pre>";
        #print_r($carriers);
        #echo "</pre>";

        if (!empty($carrierGroupRoutes)) {
          $remainingData = array_values($carrierGroupRoutes);

          // we need to removed the null routes
          // to get the right data
          foreach ($remainingData as $rKey => $rData) {
            if (empty($rData->object->routes)) {
              unset($remainingData[$rKey]);
            }
          }

          if(isset($remainingData[0]->object->routes) && !empty($includedGroups)) {
            foreach($remainingData[0]->object->routes as $route) {
              $carrierData = self::woo_machship_addCarrierToList($carriers, $route, $settings, $includedGroups, -1, $is_residential);
              if ($carrierData){
                foreach($carrierData as $carrierDetails) {
                  $carriers[$carrierDetails['carrier_index']] = $carrierDetails['data'];
                }
              }
            }
          }
        }
      } else {
        if(isset($carrierGroupRoutes->object) && isset($carrierGroupRoutes->object->routes)){
          foreach($carrierGroupRoutes->object->routes as $route) {
            $carrierData = self::woo_machship_addCarrierToList($carriers, $route, $settings, $includedGroups, -1, $is_residential);

            if ($carrierData){
              foreach($carrierData as $carrierDetails) {
                $carriers[$carrierDetails['carrier_index']] = $carrierDetails['data'];
              }
            }
          }
        }
      }
    }

    return $carriers;

  }

  /**
   * @param $carrierGroups
   * @param $serviceID
   * @return array
   */
  public static function woo_machship_getCarrierGroupByServiceID($settings, $serviceID, $carrierGroupTobeIncluded) {
     $carrierGroups = self::woo_machship_getIncludeServices($settings);
     $matchingGroups = [];
     foreach($carrierGroups as $key=>$carrier) {
       $services = $carrier['services'];
       if (!empty($carrierGroupTobeIncluded) && in_array($key, $carrierGroupTobeIncluded)) {

         if(!empty($services) && in_array($serviceID, $services)) {
           $matchingGroups[] = $carrier;
         }
       } else if (empty($carrierGroupTobeIncluded) && !empty($services) && in_array($serviceID, $services)){
         $matchingGroups[] = $carrier;
       }
     }

     return $matchingGroups;
  }

  /**
   * @param $input
   * @param $length
   * @param bool $ellipses
   * @param bool $strip_html
   * @return string
   */
  public static function woo_machship_trim_text($input, $length, $ellipses = true, $strip_html = true) {
    //strip tags, if desired
    if ($strip_html) {
      $input = strip_tags($input);
    }

    //no need to trim, already shorter than trim length
    if (strlen($input) <= $length) {
      return $input;
    }

    //find last space within length
    $last_space = strrpos(substr($input, 0, $length), ' ');
    $trimmed_text = substr($input, 0, $last_space);

    //add ellipses (...)
    if ($ellipses) {
      $trimmed_text .= '...';
    }

    return $trimmed_text;
  }

  /**
   * @param $warehouses
   * @param array $settings
   * @return array
   */
  public static function woo_machship_get_selected_warehouse($warehouses, $settings = array()){
    $settings = !empty($settings) ? $settings : self::woo_machship_get_shipping_settings();
    $all_warehouses = $settings['warehouse_locations'];
    $warehouse_arr = array();

    if(is_array($warehouses) && !empty($warehouses)){
      foreach($warehouses as $warehouse){
        $warehouse_arr[$warehouse] = $all_warehouses[$warehouse];
      }
    }

    return $warehouse_arr;
  }

  /**
   * @param $result
   * @param string $qType
   */
  public static function woo_machship_get_shipping_prices($result, $settings, string $qType = null) {
    $qType = self::woo_machship_validateParam($qType, 'product');

    WC()->session->set_customer_session_cookie( true );
    $output = '';
    $inc = 1;
    $final_rates = [];

    $p_id = sanitize_text_field($_POST['productId']);

    usort($result, function($a, $b) {
      return $a['carrierGroupDetails']['order'] - $b['carrierGroupDetails']['order'];
    });

    foreach ($result as $groups) {
      foreach($groups as $option) {

        $price_data = self::woo_machship_show_pricing_for_shipping( $option, $qType,  $p_id, $settings);
        $selected_option = '';

        if ($inc == 1){
          if( $qType == 'cart' ) {
            WC()->session->set( 'Shipping_Method_Selected', array(
              'CarrierName'   => $price_data['CarrierName'],
              'CarrierId'     => $price_data['CarrierId'],
              'CarrierServiceId'=> $price_data['CarrierServiceId'],
              'CarrierServiceName'=> $price_data['CarrierServiceName'],
              'Tax'           => $price_data['Tax'],
              'Total'         => $price_data['Total'],
              'ToLocationId'=> $price_data['ToLocationId']
            ) );

          } else {
            $selected_option = 'checked="checked"';
          }
        } else {
          $selected_option = '';
        }

        $title = $option['carrierGroupDetails']['group_name'];
        $shippingDescription = $option['carrierGroupDetails']['group_description'];

        if( is_array($price_data) && !empty($price_data) ) {
          $output .= "<div class='shipping-type-result woo-machship-sqf-fields'>";
          $output .= self::woo_machship_print_calculated_advance_margin_product($price_data, $title);
          $output .= "<p>" . $shippingDescription . "</p></div>";

          $final_rates[] = [
            'id' => "woo_machship_shipping:$title",
            'cost' => $price_data['Total'] ?? 0,
            'label' => $title,
          ];
        }

        $inc++;
      }
    }

    return $output;
  }

  public static function woo_machship_show_pricing_for_shipping($result, string $page_type = null, int $product_id = null, $settings) {
    $output = array();

    $page_type = self::woo_machship_validateParam($page_type, 'product');
    $product_id = self::woo_machship_validateParam($product_id, 0);

    if( !empty($result['carrierService']) ) {
      $marginAmount   = $settings['simple_margin_amount'];
      $marginType     = $settings['margin_fixed_percent'];
      $electiveSurcharges = !empty($result['carrierGroupDetails']['surcharges']) ? array_values(array_filter(array_map('trim',explode(',',$result['carrierGroupDetails']['surcharges'])))) : [];

      $isProductExTax = $page_type === 'product' && !empty($settings['tax_exclude_product']) && $settings['tax_exclude_product'] === 'yes';
      $isCartExTax = $page_type === 'cart' && !empty($settings['tax_exclude_shipping_price']) && $settings['tax_exclude_shipping_price'] === 'yes';

      $raw_amount = $isProductExTax || $isCartExTax ? $result['consignmentTotal']->totalSellBeforeTax : $result['consignmentTotal']->totalSellPrice;
      $new_amount = $raw_amount;

      $output = array(
        'CarrierName'   => $result['carrier']->name,
        'CarrierId'     => $result['carrier']->id,
        'CarrierServiceId'=> $result['carrierService']->id,
        'CarrierServiceName'=> $result['carrierService']->name,
        'RawTotal'      => $raw_amount,
        'Tax'           => $result['consignmentTotal']->totalTaxSellPrice,
        'ToLocationId'  => $result['toAddress']->location->id
      );

      // add electiveSurcharges
      $machShipElectiveSurcharges = $result['electiveSurcharges'];
      if (!empty($machShipElectiveSurcharges) && !empty($electiveSurcharges)) {
         foreach($machShipElectiveSurcharges as $eSurcharges) {
          if (in_array($eSurcharges->name, $electiveSurcharges)){
            $output[$eSurcharges->name] = $eSurcharges->sellPrice;
            $new_amount += $eSurcharges->sellPrice;
          }
         }
      }

      $new_amount = self::woo_machship_calculate_margin_amount( $new_amount,  $marginAmount, $marginType);
      $output['Total'] = round($new_amount, 2);
    }
    return $output;
  }

  /**
   * @param string $amount
   * @param string $margin_value
   * @param string $margin_type
   * @return float
   */
  public static function woo_machship_calculate_margin_amount( $amount = '', $margin_value = '', $margin_type = '' ) {

    $margin_value = floatval($margin_value);

    if( $margin_type == '$' ) {
      $new_amount = floatval( $margin_value + $amount );
    } else {
      $new_amount = floatval( $amount + (($margin_value*$amount)/100) );
    }

    return $new_amount;
  }

  /**
   * @param $price_data
   * @param $title
   * @return string
   */
  public static function woo_machship_print_calculated_advance_margin_product($price_data, $title) {
    $shipping_rate = '$' . number_format($price_data['Total'], 2);
    $string =  '<strong for="' . $price_data['CarrierName'] . '_carrierId_' . $price_data['CarrierId'] . '" data-original="'.$price_data['Total'].'"> <span class="lbl_inner" style="padding-left:0;">' . $title.': ' .$shipping_rate.'</span></strong>';
    return $string;
  }

  /**
   * @param $productWeights
   * @param $carrierGroups
   * @return array
   */
  public static function woo_machship_checkCarrierGroupProductBelong($productWeights, $carrierGroups) {
    $carriers = [];

    //get the heaviest
    $weight = array_values($productWeights)[0];
    foreach($carrierGroups as $key=>$group) {
      $min = floatval($group['min_weight']);
      $max = floatval($group['max_weight']);

      if(isset($group['carrier_services']['carrier_id']) && is_array($group['carrier_services']['carrier_id'])){
        $carriersArr = array_unique($group['carrier_services']['carrier_id']);
        $carrierIDs = array_map('intval', $carriersArr);

        if (empty($max) && empty($min)) {
          $carriers[$key] = $carrierIDs;
        } else if (!empty($min) && empty($max) && $weight >= $min) {
          $carriers[$key] = $carrierIDs;
        } else if (empty($min) && !empty($max) && $weight <= $max) {
          $carriers[$key] = $carrierIDs;
        } else if (!empty($min) && !empty($max) && ($weight >= $min && $weight <= $max)) {
          $carriers[$key] = $carrierIDs;
        }
      }

    }

    return $carriers;
  }

  /**
   * @param $settings
   * @return bool|mixed
   */
  public static function woo_machship_getCachedPackageTypes($settings) {
    $packageTypes = get_transient( 'woo_machship_cached_packageTypes' );
    if (empty($packageTypes) && !empty($settings['machship_token'])) {
      $packageTypes = [];
      $machship = new Woo_Machship_API($settings['machship_token']);
      $packagesRes = $machship->woo_machship_getCompanyItemValidItemTypes();

      if ($packagesRes && isset($packagesRes->object)) {
        $machshipPackageTypes = $packagesRes->object;
        foreach($machshipPackageTypes as $p) {
          $packageTypes[$p->id] = $p->name;
        }
        set_transient('woo_machship_cached_packageTypes', $packageTypes);
      }
    }

    return $packageTypes;
  }

  /**
   * @param $settings
   * @return mixed
   */
  public function woo_machship_getCachedLocations($settings) {
    $allLocations = get_transient( 'woo_machship_cached_allLocations' );

    if ($allLocations === false) {
      $machship = new Woo_Machship_API($settings['machship_token']);
      $machshipLocations = $machship->woo_machship_getAllLocations()->object;
      set_transient('woo_machship_cached_allLocations', $machshipLocations, 7 * DAY_IN_SECONDS);
    }

    return $allLocations;
  }

  //create an array of order status with value and label fields
  public static function woo_machship_createOrderStatusOpt(){
    $order_statuses = wc_get_order_statuses();
    $order_status_opt = array();
    foreach($order_statuses as $key=>$status){
      $new_key = str_replace('wc-', "", $key);
      $order_statuses[$new_key] = $status;
      unset($order_statuses[$key]);
      $order_status_opt[] = array('value'=>$new_key, 'label'=>$status);
    }

    return $order_status_opt;
  }

  /**
   * get question ids by carrier group index
   * @param $carrierGroups
   * @param $carrierGroupIndex
   * @return array
   */
  public static function woo_machship_hasQuestionIds($carrierGroups, $carrierGroupIndex) {
    $questionIds = [];

    if ($carrierGroups && isset($carrierGroups[$carrierGroupIndex])) {
      $questionIds = !empty($carrierGroups[$carrierGroupIndex]['machship_question_ids']) ? array_values(array_filter(explode(',',$carrierGroups[$carrierGroupIndex]['machship_question_ids']))) : [];
    }

    return $questionIds;
  }

  /**
   * @param $carriers
   * @param $warehouses
   * @param $toLocationID
   * @param $data_items
   * @param $settings
   * @return array
   */
  public static function woo_machship_generateMSRequest($carriers, $warehouses, $toLocationID, $data_items, $settings, $is_residential = false) {

    $generateRequest = [];
    $carrierIds = [];
    $quotes = [];
    $API_data = [];
    $hasQuestionIds = [];
	  $is_phone_order = WC()->session->get('woo-machship-phone-order');
    self::woo_machship_setAllowLogging(false); // init

    if(!empty($warehouses) && !empty($carriers)){
      foreach($carriers as $key=>$carrierValues) {
        $questionIds = self::woo_machship_hasQuestionIds($settings['carrier_groups'], $key);
        if (!empty($questionIds) || $is_residential) {
          // create a separate api call with question ids
          $params['questionIds'] = $questionIds;
          if($is_residential){
            $params['questionIds'][] = 13;
          }
          // $carrierIds = array_values($carrierValues);
          $params['carrierIds'] = array_values($carrierValues);

          $params = apply_filters('machship_despatchdate_filter', $params);

          $API_data[$key] = $params;
          $hasQuestionIds[] = $key;
        } else {
          if(is_array($carrierValues)){
            $carrierIds = array_merge($carrierIds, $carrierValues);
          }
        }
      }

      if(!empty($carrierIds)) {
        $API_data[] = array(
            "carrierIds" => array_values(array_unique($carrierIds))
          );
      }

      if ($is_phone_order == 1) {
        $toLocation = array(
          "suburb" => WC()->session->get('woo-machship-phone-order-tosuburb'),
          "postcode" => WC()->session->get('woo-machship-phone-order-topostcode')
        );
        $toLocation = self::woo_machship_setAddress($toLocation);
      } else if ($settings['disable_checkout_suburb_search'] == "yes") {
        $toLocation = array(
          "suburb" => WC()->customer->get_shipping_city(),
          "postcode" => WC()->customer->get_shipping_postcode()
        );

        $toLocation = self::woo_machship_setAddress($toLocation);

        $toLocation = apply_filters('machship_set_selected_shipping_dest', $toLocation);

      }
      if (empty($toLocation)) {

        $suburb = isset($_REQUEST['city']) ? sanitize_text_field($_REQUEST['city']) : null;
        $postcode = isset($_REQUEST['postcode']) ? sanitize_text_field($_REQUEST['postcode']) : null;

        $toLocation = [
          'suburb' => $suburb ?? WC()->session->get('woo-machship-phone-order-tosuburb') ?? WC()->customer->get_shipping_city(),
          'postcode' => $postcode ?? WC()->session->get('woo-machship-phone-order-topostcode') ?? WC()->customer->get_shipping_postcode(),
        ];

        $toLocation = self::woo_machship_setAddress($toLocation);
      }
      $savedToLocation = $toLocation;

      self::$msRequest = ['result' => []];
      $toLocationInfo = [];
      foreach($warehouses as $wKey => $location){
        // create multi calls sync
        $API_single_request = $paramsArr = [];
        if (count($API_data) > 1) {
          foreach($API_data as $key=>$apiParams) {
            $paramsArr[$key] = $apiParams;
            $paramsArr[$key]['fromLocationId'] = $location['location_id'];
            $paramsArr[$key]['fromCompanyLocationId'] = $location['company_location'];

            if ($is_phone_order == 1 || $settings['disable_checkout_suburb_search'] == "yes") {
              unset($paramsArr[$key]['toLocationId']);
              $paramsArr[$key]['toLocation'] = $toLocation;
            } else {
              $paramsArr[$key]['toLocationId'] = $toLocationID;
            }

            $paramsArr[$key]['items'] = array();
            foreach($data_items as $dt){
              // we need to check if the location_id and loc_ids are set first
              if (isset($dt['location_id']) && isset($location['loc_ids'])) {
                if(in_array($location['location_id'], $dt['loc_ids'])){
                  $paramsArr[$key]['items'][] = $dt;
                }
              }
              else {
                $paramsArr[$key]['items'][] = $dt;
              }
            }

            // this hook is to get the nearest warehouse location from customer location/selected location
            $paramsArr[$key] = apply_filters('machship_set_from_location', $paramsArr[$key], $location, 'fromLocationId');
          }
          $generateRequest[$wKey] = $paramsArr;
          $generateRequest[$wKey]['req_location_id'] = $location['location_id'];

          self::$msRequest['result'][$location['location_id']] = $paramsArr;
          self::$ecRequest['result']['items'] = $data_items;

          // get the single tolocation info for logging
          $toLocationInfo = $paramsArr[$key]['toLocation'] ?? $paramsArr[$key]['toLocationId'];
        } else {

          $API_single_request = array(
            "fromCompanyLocationId" => $location['company_location'],
            "toLocationId" => $toLocationID,
            "items"=> $data_items,
            "carrierIds" => array_values(array_unique($carrierIds)),
            "questionIds" => isset($API_data[0]['questionIds']) ? $API_data[0]['questionIds'] : []
          );

          $API_single_request = apply_filters('machship_despatchdate_filter', $API_single_request);

          // this hook is to get the nearest warehouse location from customer location/selected location
          $API_single_request = apply_filters('machship_set_from_location', $API_single_request, $location);

          if($is_residential && !in_array(13, $API_single_request['questionIds'])){
            $API_single_request['questionIds'][] = 13;
          }

          if ($is_phone_order == 1 || $settings['disable_checkout_suburb_search'] == "yes") {
            unset($API_single_request['toLocationId']);
            $API_single_request['toLocation'] = $toLocation;
          } else {
            $API_single_request['toLocationId'] = $toLocationID;
          }

          $generateRequest[$wKey] = $API_single_request;
          $generateRequest[$wKey]['req_location_id'] = $location['location_id'];

          self::$msRequest['result'][$location['location_id']] = $API_single_request;
          self::$ecRequest['result'][$location['location_id']] = [
            'fromCompanyLocationId' => $location['company_location'],
            'toLocationId'   => $API_single_request['toLocationId'],
            'items'          => $data_items,
          ];

          // get the single tolocation info for logging
          $toLocationInfo = $API_single_request['toLocation'] ?? $API_single_request['toLocationId'];
        }
      }

      // store the toLocationInfo in ecom request for validation later
      self::$ecRequest['result']['toLocationDetails'] = $savedToLocation;
      if (array_key_exists('post_data', self::$ecRequest['result'])) {
        unset(self::$ecRequest['result']['post_data']);
      }

      self::$ecRequest['result']['warehouse_locations'] = $settings['warehouse_locations'];
      // make sure that shipping method in ecrequest's result is available
      if (!empty(self::$ecRequest['result']['shipping_method'])) {
        foreach ($settings['carrier_groups'] as $key => $carrier) {
          if (str_contains(self::$ecRequest['result']['shipping_method'][0], $carrier['group_name'])) {
            self::$ecRequest['result']['carrier_group'] = $settings['carrier_groups'][$key];
            break;
          }
        }
      }

      // validate if toLocation has any data
      if (empty($toLocationInfo)) {
        self::$msResponse = ['result' => ['toLocation details empty']];
        self::woo_machship_setAllowLogging(true); // let's log it so we know the request didn't proceed because of toLocation
        return array('quotes' => [], 'hasQuestionIds' => $hasQuestionIds);
      }

      // let's stop the request to MACHSHIP if there's no changes in msRequest
      if (
        (
          !empty(self::woo_machship_getMSRequest()) &&
          !empty(self::woo_machship_getMSStoredResponse())
        ) &&
        self::woo_machship_getMSRequest() == self::$msRequest
      ) {
        self::woo_machship_setAllowLogging(false);
        return array('quotes' => self::woo_machship_getMSStoredResponse(), 'hasQuestionIds' => $hasQuestionIds);
      }

      // send the request to MachShip
      $quotes = self::woo_machship_sendMSRequest($generateRequest, $API_data, $settings['machship_token']);
    }

    return array('quotes' => $quotes, 'hasQuestionIds' => $hasQuestionIds);
  }

  public static function woo_machship_sendMSRequest($generateRequest, $apiData, $token){

    $machship = new Woo_Machship_API($token);
    $quotes = [];

    self::$msResponse = ['result' => []];

    // set the MS Request for validating if the request should be added to our logs
    self::woo_machship_setMSRequest(self::$msRequest);

    // WAREHOUSES or generateRequest
    foreach ($generateRequest as $apiRequest) {
      $req_location_id = $apiRequest['req_location_id'];
      // remove the added field before sending
      unset($apiRequest['req_location_id']);

      if (count($apiData) > 1) {
        $quoteResponse = $machship->woo_machship_multiRequestRoutes($apiRequest);
        self::$msResponse['result'][$req_location_id] = $quoteResponse;
        $quotes[] = $quoteResponse;
      }
      else {
        $quoteResponse = $machship->woo_machship_returnRoutes($apiRequest);
        self::$msResponse['result'][$req_location_id] = $quoteResponse;
        $quotes[] = $quoteResponse;
      }
    }

    // store the rates we got in session. so we can just use it again without making another request
    self::woo_machship_setMSStoredResponse($quotes);

    self::woo_machship_setAllowLogging(true);
    return $quotes;
  }

  /**
   * @param $carriers
   * @param $routes
   * @param $settings
   * @param $carrierGroupsToBeIncluded
   * @return array
   */
  public static function woo_machship_addCarrierToList($carriers, $route, $settings, $carrierGroupsToBeIncluded, $question_id_index = -1, $is_residential = false) {
      $checkService = self::woo_machship_getCarrierGroupByServiceID($settings, $route->carrierService->id, $carrierGroupsToBeIncluded);
      $carrierDataArr = [];
      if ($checkService) {
        $toInsert = false;

        #echo $gid;
        #echo $question_id_index;
        #echo "<pre>";
        #print_r($checkService);
        #echo "</pre>";

        foreach($checkService as $matchedGroup) {
          $carrier_index = $matchedGroup['group_index'];

          if($is_residential && $matchedGroup['exclude_for_residential'] == "yes"){
            continue;
          }

          if($question_id_index != -1 && $question_id_index != $carrier_index){
            continue;
          }

          if($route->consignmentTotal->totalSellPrice == 0) {
            continue;
          }



          if (!array_key_exists($carrier_index, $carriers) && !empty($matchedGroup)) {

            $toInsert = true;
          } else {

            $toInsert = false;
            $existingCarrierGroup = $carriers[$carrier_index];
            // check if existing index has less than the current totalSellPrice
            if ($route->consignmentTotal->totalSellPrice < $existingCarrierGroup['consignmentTotal']->totalSellPrice) {
              $toInsert = true;
            }
          }

          //if question_id_index == carrier_index
          #f($question_id_index != -1 && $question_id_index != $carrier_index){
          #  $toInsert = false;
          #}

          if ($toInsert) {

            $questionIds = $matchedGroup['question_ids'];
            if ($is_residential && !in_array('13', $questionIds)) {
              array_push($questionIds, '13');
            }

            $carrierData['carrierGroupDetails'] = array(
              'group_description' => $matchedGroup['group_description'],
              'group_name'        => $matchedGroup['group_name'],
              'surcharges'        => $matchedGroup['machship_surcharges'],
              'order'             => $matchedGroup['carrier_group_order'],
              'question_ids'      => $questionIds
            );
            $carrierData['carrier'] = $route->carrier;
            $carrierData['carrierService'] = $route->carrierService;
            $carrierData['consignmentTotal'] = $route->consignmentTotal;
            $carrierData['electiveSurcharges'] = $route->electiveSurcharges;
            $carrierData['fromCompanyLocationId'] = $route->fromCompanyLocationId;
            $carrierData['toAddress'] = $route->toAddress;
            $carrierData['companyId'] = $route->companyId;

            $carrierData = apply_filters('machship_despatch_option', $carrierData, $route);

            $carrierDataArr[] = array('carrier_index'=>$carrier_index, 'data'=>$carrierData);


            //if($is_residential) break;
          }
        }
      }

      return $carrierDataArr;
  }

  /**
   * @param $orderID
   */
  public static function woo_machship_createOrderPayload($order, $order_id) {
    delete_post_meta( $order_id, 'machship_order_payload');
    $created_via = get_post_meta($order_id, '_created_via', true);

    if( $created_via == 'eBay') {
      return ;
    }

    $payload = [
      "items" => []
    ];

    $settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
    $packageTypes = maybe_unserialize(Woo_Machship_Custom::woo_machship_getCachedPackageTypes($settings));
    $items = $order->get_items();


    //order items
    foreach( $items as $item ) {
      $item_product = $item->get_product();
      $parent_id = $item_product->get_parent_id() == 0 ? $item_product->get_id() : $item_product->get_parent_id(); //check if variation or not
      $show_plugin_value  = self::woo_machship_machship_product_status($settings, $parent_id);

      if($show_plugin_value == "0"){
        continue; //exclude product
      }

      $rows = get_post_meta($item_product->get_id(), '_carton_items', true);
      $product_title = html_entity_decode( get_the_title($item_product->get_id()), ENT_QUOTES, 'UTF-8' );
      $item_quantity = $item->get_quantity();

      $psettings = get_post_custom($parent_id);

      $internationalData = [
        '_harmonized_code' => $psettings['_international_harmonisation_code'][0] ?? '',
        '_country_manufacture_code' => $psettings['_international_country_manufacture'][0] ?? '',
      ];

      if( !empty($rows) ) {
        foreach( json_decode($rows) as $row ) {
          $payload['items'][] = array(
            'name'                =>  $product_title,
            'itemType'            => isset($packageTypes[intval($row->packaging_type)]) ? $packageTypes[intval($row->packaging_type)] : "",
            'height'              => round(floatval($row->height) ?? 0,2),
            'weight'              => round(floatval($row->weight) ?? 0,2),
            'length'              => round(floatval($row->length) ?? 0,2),
            'width'               => round(floatval($row->width) ?? 0,2),
            'quantity'            => $item_quantity,
            'international_data'  => $internationalData,
            'price'               => $item_product->get_price(),
            'sku'                 => $item_product->get_sku(),
            'pack_individually'   => $psettings['_pack_individually'][0] ?? false,
            'dynamic_mode'        => isset($settings['mode']) && $settings['mode'] === 'dynamic' ? true : false
          );
        }
      }
    }

    // shipping meta data

	if (isset(WC()->session)) {
	    $allMethods = WC()->session->get('Shipping_Method_Data');

		if(!empty( $allMethods)) {

		    foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
		      // Get the data in an unprotected array
		      $item_data = $item->get_data();

		      $shippingId = $item_data['id'];
		      $selectedMethod = array_filter(array_map(function($method) use($item_data) {
			return $method['MethodName'] == $item_data['name'] ?  $method : null;
		      }, $allMethods));

		      if (!empty($selectedMethod)) {
			foreach ($selectedMethod as $fieldName => $fieldValue) {
			  foreach ($fieldValue as $metaKey => $metaValue) {
			    if ($metaKey != 'MethodName') {
			      wc_update_order_item_meta($shippingId, $metaKey, $metaValue);
			    }
			  }
			}
		      }
		    }
		}
	}

    /**
     * store additional data to order meta
     */
    update_post_meta( $order_id, 'machship_order_payload', json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    $dataToUnset = [
      'Shipping_Method_Selected',
      'Shipping_Method_Data',
      'woo-machship-tosuburb',
      'woo-machship-tostate',
      'woo-machship-topostcode',
      'woo-machship-tolocationID',
      'Woo_Machship_shippingPrices_Display',
      'shipping_machship_methods',
      'woo-machship-post_data',
      'shipping_calculated_cost_machship',
      'woo-machship-phone-order',
      'woo-machship-phone-order-tosuburb',
      'woo-machship-phone-order-tostate',
      'woo-machship-phone-order-topostcode',
      'woo_machship_stored_rates',
      'woo_machship_ms_request'
    ];

    if (isset(WC()->session)) {
      foreach($dataToUnset as $val) {
        if(WC()->session->__isset($val)){
          WC()->session->__unset($val);
        }
      }
    }

    if (isset(WC()->customer)) {
      WC()->customer->set_shipping_state(null);
      WC()->customer->set_shipping_city(null);
      WC()->customer->set_shipping_postcode( null);
    }
  }

  /**
	 * Method woo_machship_machship_product_status
	 * Fetch if product is enabled for quoting
	 *
	 * @param Array $settings
	 * @param Integer $id
	 * @param Integer $pluginVal
	 *
	 * @return String
	 */
  public static function woo_machship_machship_product_status ($settings, $id = null, $pluginVal = null) {

    if (!empty($settings['force_enable_products']) && $settings['force_enable_products'] == 'yes') {
      return "1";
    }
    if ($pluginVal !== null) {
      return $pluginVal;
    }
    return get_post_meta( $id, '_show_plugin', true );
  }

  public static function woo_machship_validateParam($param, $value) {
    return empty($param) ? $value : $param;
  }
}
