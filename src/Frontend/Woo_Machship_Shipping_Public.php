<?php

namespace Woo_Machship_Shipping\Frontend;

use Woo_Machship_Shipping\API\Woo_Machship_Fusedship_API;
use Woo_Machship_Shipping\API\Woo_Machship_API;
use Woo_Machship_Shipping\Common\Woo_Machship_Fusedship;
use Woo_Machship_Shipping\Woo_Machship_PluginData;
use Woo_Machship_Shipping\Common\Woo_Machship_Custom;

use WP_REST_Server;

if (!defined('ABSPATH')) exit;

class  Woo_Machship_Shipping_Public
{
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
	 * @var
	 */
	private $shipping_id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct()
	{
		$this->version = Woo_Machship_PluginData::woo_machship_plugin_version();
		$this->plugin_name = Woo_Machship_PluginData::woo_machship_plugin_text_domain();
		$this->shipping_id = 'woo_machship_shipping';
	}

	/**
	 * register custom scripts
	 */
	public function woo_machship_enqueue_scripts_styles()
	{
		global $woocommerce;
		$items = [];

		if (isset($woocommerce->cart))
			$items =   $woocommerce->cart->get_cart();

		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
		$disabled_items = Woo_Machship_Custom::woo_machship_count_disabled_items($items, $settings);

		if (
			!empty($settings) && isset($settings['enabled']) && $settings['enabled'] == 'yes' && isset($settings['machship_token']) &&
			!empty($settings['machship_token']) && isset($settings['warehouse_locations']) && !empty($settings['warehouse_locations'])
		) {

			if ((is_cart() || is_checkout()) && $disabled_items == count($items)) {
				return;
			}
			// Register styles
			wp_register_style($this->plugin_name, Woo_Machship_PluginData::woo_machship_plugin_dir_url() . 'assets/public/css/woo-machship-shipping.css', array(), $this->version, 'all');

			// Register scripts
			wp_register_script($this->plugin_name, Woo_Machship_PluginData::woo_machship_plugin_dir_url() . 'assets/public/js/woo-machship-shipping.js', array('jquery'), $this->version, true);

			$page = '';
			if (is_product()) $page = 'product';
			elseif (is_cart()) $page = 'cart';
			elseif (is_checkout()) $page = 'checkout';

			// localize script
			wp_localize_script($this->plugin_name, 'woo_machship', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'page_is' => $page,
				'nonce' => wp_create_nonce('woocommerce-shipping-calculator')
			));

			if (is_product() || is_cart() || is_checkout()) {

				// Enqueue styles
				wp_enqueue_style($this->plugin_name);

				// Enqueue scripts
				wp_enqueue_script($this->plugin_name);

			}
		}

		if (is_checkout() || is_account_page()) {

			wp_register_script($this->plugin_name . '-address-book', Woo_Machship_PluginData::woo_machship_plugin_dir_url() . 'assets/public/js/woo-machship-address-book.js', ['jquery'], $this->version, true);

			wp_enqueue_script($this->plugin_name . '-address-book');

			if (is_account_page()) {
				// localize script
				wp_localize_script($this->plugin_name . '-address-book', 'woo_machship', array(
					'ajaxurl' => admin_url('admin-ajax.php'),
					'page_is' => 'editaddressbook',
					'nonce' => wp_create_nonce('woocommerce-shipping-calculator')
				));
			}

		}
	}

	private function woo_machship_get_allowed_post_html() {
		$allowed_html = wp_kses_allowed_html( 'post' );
		$allowed_html['div']['style'] = 1;
		$allowed_html['input'] = [
			'type' => true,
			'id' => true,
			'name' => true,
			'value' => true,
			'placeholder' => true,
			'autocomplete' => true,
			'class' => true,
			'style' => 1
		];

		$allowed_html['select'] = [
			'id'	=> true,
			'name' => true,
			'class' => true,
			'style' => true
		];

		$allowed_html['option'] = [
			'value' => true,
			'selected' => true,
		];

		$allowed_html['script'] = [
			'src' => true,
			'id' => true
		];

		return $allowed_html;
	}

	/**
	 * single product widget
	 */
	public function woo_machship_product_widget()
	{
		global $product;

		$has_empty_dimension = false;

		if ($product->get_type() == 'simple') {
			$carton_items = get_post_meta($product->get_id(), '_carton_items', true);
			//sometimes the above was returning an array - we suspect from other themes/plugins - so we added this handling.
			//we need an object, that's why we re-encode it.
			if(is_array($carton_items)) {
				$carton_items = json_encode($carton_items);
			}
			$cartons__ = json_decode($carton_items);

			if (!empty($carton_items) && !empty($cartons__)) {
				foreach ($cartons__  as $item) {
					$length = floatval($item->length);
					$width =  floatval($item->width);
					$height =  floatval($item->height);
					$weight =  floatval($item->weight);

					if ($length == 0 || $width == 0 || $height == 0 || $weight == 0) {
						$has_empty_dimension = true;
						break;
					}
				}
			}
		}

		if ($product->get_type() == 'variable') {
			$variations = $product->get_available_variations();
			if (!empty($variations)) {
				$carton_items = array();
				foreach ($variations as $variation) {
					$variationID = $variation['variation_id'];
					$carton_items = get_post_meta($variationID, '_carton_items', true);
					//sometimes the above was returning an array - we suspect from other themes/plugins - so we added this handling.
					//we need an object, that's why we re-encode it.
					if(is_array($carton_items)) {
						$carton_items = json_encode($carton_items);
					}
					$cartons__ = json_decode($carton_items);

					if (!empty($carton_items) && !empty($cartons__)) {
						foreach ($cartons__  as $item) {
							$length = floatval($item->length);
							$width =  floatval($item->width);
							$height =  floatval($item->height);
							$weight =  floatval($item->weight);

							if ($length == 0 || $width ==  0 || $height == 0 || $weight == 0) {
								$has_empty_dimension = true;
								break;
							}
						}
					}
				}
			}
		}

		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
		$show_plugin  = Woo_Machship_Custom::woo_machship_machship_product_status($settings, $product->get_id());

		$show_plugin_value  = $show_plugin == "" && $settings['product_page'] != 'hide' ? '1' : $show_plugin; //if initial setup,show plugin on product page by default

		if (
			!empty($settings) && isset($settings['enabled']) && $settings['enabled'] == 'yes' && isset($settings['machship_token']) &&
			!empty($settings['machship_token']) && isset($settings['warehouse_locations']) && !empty($settings['warehouse_locations'])
		) {
			if ($has_empty_dimension === false && $show_plugin_value == '1' && Woo_Machship_Custom::woo_machship_check_if_active() == true) { //check if we need to show based on product setting
				echo wp_kses(Woo_Machship_Custom::woo_machship_shipping_quote_form_html('', $settings), self::woo_machship_get_allowed_post_html());
			}
		}

		if ($has_empty_dimension === true && current_user_can('administrator')) {
			echo "<p>Machship Error: No product quote widget shown as there is insufficient box data stored on this item.</p>";
		}
	}

	public function register_rest_woo_machship_test()
	{
		register_rest_route('woo-machship/api/v1', '/test', [
			'methods' => WP_REST_Server::READABLE,
			'callback' => [$this, 'woo_machship_test']
		]);
	}

	/**
	 * register hooks that have dynamic action/filter value
	 */
	public function woo_machship_dynamic_hook()
	{
		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();

		// Display Quick Quote on product page
		if ($settings['product_page'] != 'hide') {
			$product_page_position = isset($settings['product_position']) ? $settings['product_position'] : 'woocommerce_after_single_product_summary';

			if ($product_page_position === 'other') {
				$product_page_position = $settings['product_position_other'] ?? 'woocommerce_after_single_product_summary';
			}

			add_action($product_page_position, array($this, 'woo_machship_product_widget'));
		}

		// Display Quick Quote On cart page, not needed for now
		if (get_option('woocommerce_enable_shipping_calc') == 'no') {
			add_filter('woocommerce_cart_ready_to_calc_shipping', array($this, 'woo_machship_disable_shipping_calc_on_cart'), 99);
		}

		add_filter('cfw_enable_zip_autocomplete', '__return_false');
	}

	/**
	 * Trigger when checkout page is visited
	 *
	 * @param array $data
	 * @return void
	 */
	public function woo_machship_async_logging_cb($data)
	{
		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
		$token = $settings['fusedship_token'];
		$integrationId = $settings['fusedship_id'];
		$fusedship = new Woo_Machship_Fusedship_API($token, $integrationId);
    	$fusedship->woo_machship_saveLog($data);
	}

	/**
	 * register widget on cart
	 */
	public function woo_machship_for_cart()
	{
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();

		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
		$disabled_items = Woo_Machship_Custom::woo_machship_count_disabled_items($items, $settings);

		if (
			!empty($settings) && isset($settings['enabled']) && $settings['enabled'] == 'yes' && isset($settings['machship_token']) &&
			!empty($settings['machship_token']) && isset($settings['warehouse_locations']) && !empty($settings['warehouse_locations'])
		) {
			if (Woo_Machship_Custom::woo_machship_check_if_active() == true && $disabled_items < count($items)) {
				echo wp_kses(Woo_Machship_Custom::woo_machship_shipping_quote_form_html('cart', $settings), self::woo_machship_get_allowed_post_html());
			}
		}
	}

	public function woo_machship_get_location()
	{
		check_ajax_referer( 'woocommerce-shipping-calculator', 'nonce' );

		if (isset(WC()->session)) {
			if (WC()->session->__isset('Shipping_Method_Selected')) {
				WC()->session->set('Shipping_Method_Selected',  array());
			}
		}

		if (!isset($_REQUEST['keyword'])) {
			echo wp_json_encode([
				'type' => 'fail',
				'message' => 'keyword is required'
			]);
			die();
		}

		$keyword = sanitize_text_field(trim($_REQUEST["keyword"]));

		// connect to machship
		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
		$token = $settings['machship_token'];
		$machship = new Woo_Machship_API($token);
		$locations = $machship->woo_machship_getLocationsWithSearchOptions($keyword);

		$result = "";
		$suburbs = [];
		$postcodes = [];
		if (!$locations->object) {
			$result = "No location found";
		} else {
			$inc = 1;
			foreach ($locations->object as $location) {
				$postcode = sprintf("%04d", $location->postcode);
				$result .= '<div class="radio-item">
					<label for="woo-ms-suburb-' . $inc . '">
							<input type="radio" id="woo-ms-suburb-' . $inc . '" name="woo_ms_suburb" class="woo-machship-suburb-item" value="' . $postcode . '" data-locationid="' . $location->id . '" data-suburb="' . $location->suburb . '" data-state="' . $location->state->code . '" data-postcode="' . $postcode . '">
							<strong>' . $location->suburb . ' ' . $location->state->code . '</strong> - ' . $postcode . '
					</label>
				</div>';
				$suburbs[] = $location->suburb;
				$postcodes[] = $postcode;
				$inc++;
			}
		}

		if (isset($_REQUEST["address_suburb"]) && !empty($locations->object)) {
			$result = $postcodes;
		}

		if (isset($_REQUEST["address_postcode"])) {
			$keyword = sprintf("%04d", $keyword);
			$rawLocation = [
				'rawLocations' => [
					[
						"suburb"	=> sanitize_text_field($_REQUEST['address_suburb']),
      					"postcode" 	=> $keyword
					]
				]
			];

			$getLocation = $machship->woo_machship_getReturnLocations($rawLocation);
			if (!empty($getLocation->object)) {
				$result = json_decode(json_encode($getLocation->object), true);
			}
			else {
				$result = 'No location found';
			}
		}

		echo wp_json_encode([
			'type' => 'success',
			'result' => $result
		]);
		wp_die();
	}

	/**
	 * @param $show_shipping
	 * @return bool
	 */
	public function woo_machship_disable_shipping_calc_on_cart($show_shipping)
	{
		if (is_cart()) {
			return false;
		}
		return $show_shipping;
	}

	/**
	 * register widget on checkout
	 */
	public function woo_machship_for_checkout()
	{
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();

		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
		$disabled_items = Woo_Machship_Custom::woo_machship_count_disabled_items($items, $settings);

		if (
			!empty($settings) && isset($settings['enabled']) && $settings['enabled'] == 'yes' && isset($settings['machship_token']) &&
			!empty($settings['machship_token']) && isset($settings['warehouse_locations']) && !empty($settings['warehouse_locations'])
		) {
			if (Woo_Machship_Custom::woo_machship_check_if_active() == true && $disabled_items < count($items)) {
				echo wp_kses(Woo_Machship_Custom::woo_machship_shipping_quote_form_html('checkout', $settings), self::woo_machship_get_allowed_post_html());
			}
		}
	}

	/**
	 * set TO location details to session
	 */
	public function woo_machship_set_shipping_suburb()
	{
		$nonce = $_POST['nonce'] ?? '';
		wp_verify_nonce( $nonce, 'woocommerce-shipping-calculator' );


		WC()->session->set_customer_session_cookie(true);

		if (isset($_POST['tosuburb']))   WC()->session->set('woo-machship-tosuburb', sanitize_text_field($_POST['tosuburb']));
		if (isset($_POST['tostate']))     WC()->session->set('woo-machship-tostate', sanitize_text_field($_POST['tostate']));
		if (isset($_POST['topostcode']))   WC()->session->set('woo-machship-topostcode', sanitize_text_field($_POST['topostcode']));
		if (isset($_POST['tolocationid'])) WC()->session->set('woo-machship-tolocationID', sanitize_text_field($_POST['tolocationid']));
		exit;
	}



	/**
	 * get quotes
	 */
	public function woo_machship_find_shipping_costs()
	{
		$nonce = $_POST['nonce'] ?? '';
		wp_verify_nonce( $nonce, 'woocommerce-shipping-calculator', 'nonce' );

		WC()->session->set_customer_session_cookie(true);

		global $woocommerce;


		$packages =  $woocommerce->cart->get_shipping_packages();
		foreach ($packages as $package_key => $package) {
			$session_key  = 'shipping_for_package_' . $package_key;
			$stored_rates = WC()->session->__unset($session_key);
		}

		WC()->session->set('Shipping_Method_Selected',  array());
		WC()->session->__unset('woo-machship-phone-order');
		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();


		if ($settings['disable_checkout_suburb_search'] != "yes" || $_POST['page_is'] == 'product') {

			$tosuburb  = !empty($_POST['tosuburb']) ?  sanitize_text_field($_POST['tosuburb']) : WC()->session->get('woo-machship-tosuburb');
			$tostate  = !empty($_POST['tostate']) ? sanitize_text_field($_POST['tostate']) : WC()->session->get('woo-machship-tostate');
			$topostcode  = !empty($_POST['topostcode']) ? sanitize_text_field($_POST['topostcode']) : WC()->session->get('woo-machship-topostcode');
			$toLocationID = !empty($_POST['tolocationid']) ? sanitize_text_field($_POST['tolocationid']) : WC()->session->get('woo-machship-tolocationID');

			WC()->session->set('woo-machship-tosuburb', $tosuburb);
			WC()->session->set('woo-machship-tostate', $tostate);
			WC()->session->set('woo-machship-topostcode', $topostcode);
			WC()->session->set('woo-machship-tolocationID', $toLocationID);

			WC()->customer->set_shipping_state(WC()->session->get('woo-machship-tostate'));
			WC()->customer->set_shipping_city(WC()->session->get('woo-machship-tosuburb'));
			WC()->customer->set_shipping_postcode(WC()->session->get('woo-machship-topostcode'));
		} else {

			$tosuburb  = !empty($_POST['s_city']) ?  sanitize_text_field($_POST['s_city']) : "";
			$tostate  = !empty($_POST['s_state']) ? sanitize_text_field($_POST['s_state']) : "";
			$topostcode  = !empty($_POST['s_postcode']) ? sanitize_text_field($_POST['s_postcode']) : "";

			WC()->session->set('woo-machship-tosuburb', $tosuburb);
			WC()->session->set('woo-machship-tostate', $tostate);
			WC()->session->set('woo-machship-topostcode', $topostcode);
			WC()->customer->set_shipping_state($tostate);
			WC()->customer->set_shipping_city($tosuburb);
			WC()->customer->set_shipping_postcode($topostcode);
		}
		WC()->customer->set_calculated_shipping(true);

		if (isset($_POST['is_residential'])) {
			$is_residential = sanitize_text_field($_POST['is_residential']);
			WC()->session->set('is_residential', $is_residential);
		} else {
			$is_residential = false;
		}

		if ($_POST['page_is'] == 'product') {
			$_product =  wc_get_product(sanitize_text_field($_POST['productId']));
			$product_data = $_product->get_data();
			$product_type = $_product->get_type();

			$product_data = apply_filters('machship_product_filter', $product_data);

			if(!$product_data) {
				wp_die();
			}

			$items = [];

			$productid = isset($_POST['productId']) ? sanitize_text_field($_POST['productId']) : '';

			$product_quantity = isset($_POST['product_quantity']) ? sanitize_text_field($_POST['product_quantity']) : 1;

			if ($product_type == 'composite' && isset($_POST['components']) && !empty($_POST['components'])) {
				// add component to data_items
				$selected_components = $_POST['components']['components'];

				if (is_array($selected_components)) {
					foreach ($selected_components as $comp) {
						$comp_data = wc_get_product(sanitize_text_field($comp));
						$comp_data = $comp_data->get_data();
						$comp_data['quantity'] = $product_quantity;
						$items[] = $comp_data;
					}
				}

			} else {
				$product_data['quantity'] = $product_quantity;
				$items[] = $product_data;
			}



			try {
				$results = Woo_Machship_Custom::woo_machship_send_routes_request($settings, $toLocationID, $items, $is_residential);

				if (!empty($results['mode']) && $results['mode'] === 'dynamic') {
					echo wp_kses_post($results['output']);
					exit;
				}

				if (!empty($results['code'])) {
					echo esc_html($results['message']);
					wp_die();
				}


				$shippingPrices = $settings['no_available_shipping'];
				if (
					!empty($results['result']) &&
					!empty($_POST['page_is']) &&
					!empty($_POST['productId'])
				) {

					$shippingPrices = Woo_Machship_Custom::woo_machship_get_shipping_prices(
						$results['result'],
						$settings,
						sanitize_text_field($_POST['page_is'])
					);

				}

				$contact_us = '<p class="contact-us">' . $settings['contact_us'] . '</p>';
				$shippingPrices = empty($shippingPrices) ? $settings['no_available_shipping'] : $shippingPrices . $contact_us;

				WC()->session->set('Woo_Machship_shippingPrices_Display', $shippingPrices);
				echo wp_kses_post($shippingPrices);
				exit;
			} catch (\Exception $e) {
				wp_send_json_error($e->getMessage(), 400);
				wp_die();
			}

		}
	}

	/**
	 * @param $order_id
	 */
	public function woo_machship_checkout_order_processed_func($order_id)
	{
		$order = wc_get_order($order_id);
		Woo_Machship_Custom::woo_machship_createOrderPayload($order, $order_id);
	}

	public function woo_machship_fusedship_webhook_send($order_id)
	{
		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();

		if (
			empty($settings['fusedship_webhook']) ||
			$settings['fusedship_webhook'] === 'no' ||
			empty($settings['fusedship_webhook_status'])
		) {
			// error_log("[woo_machship_fusedship_webhook_send] stops webhook isn't enabled or no subscribe status");
			// dont do anything
			return;
		}


		$order = wc_get_order($order_id);

		// Get the new order status
		$new_status = $order->get_status();


		$webhookStatusList = array_map(function($status) {
			return str_replace('wc-', '', $status);
		}, $settings['fusedship_webhook_status']);

		if (in_array($new_status, $webhookStatusList)) {

			// will send webhook to fusedship
			// wrap with try/catch since this should not interfere with woocommerce order
			try {

				$woomachFusedship = new Woo_Machship_Fusedship($settings);
				$woomachFusedship->woo_machship_init();

				// prepare payload
				$payload = [
					'order_id' => $order_id,
					'data' => $order->get_data(),
					'status' => $new_status
				];

				$result = $woomachFusedship->woo_machship_webhook($payload);

				// TODO result should be recorded somewhere
				// error_log("[woo_machship_fusedship_webhook_send] result " . json_encode($result));

			} catch (\Exception $e) {
				error_log("[woo_machship_fusedship_webhook_send] error " . $e->getMessage());
			}


		}
	}

	/**
	 * set fields on session
	 */
	public function woo_machship_set_fields_on_session()
	{

		check_ajax_referer( 'woocommerce-shipping-calculator', 'nonce' );

		global $woocommerce;
		$packages =  $woocommerce->cart->get_shipping_packages();
		foreach ($packages as $package_key => $package) {
			$session_key  = 'shipping_for_package_' . $package_key;
			$stored_rates = WC()->session->__unset($session_key);
		}

		WC()->session->set('Shipping_Method_Selected',  array());
		$post_data = array();

		$tosuburb  = !empty($_POST['tosuburb']) ? sanitize_text_field($_POST['tosuburb']) : WC()->session->get('woo-machship-tosuburb');
		$tostate  = !empty($_POST['tostate']) ? sanitize_text_field($_POST['tostate']) : WC()->session->get('woo-machship-tostate');
		$topostcode  = !empty($_POST['topostcode']) ? sanitize_text_field($_POST['topostcode']) : WC()->session->get('woo-machship-topostcode');
		$toLocationID = !empty($_POST['tolocationid']) ? sanitize_text_field($_POST['tolocationid']) : WC()->session->get('woo-machship-tolocationID');

		$is_residential = !empty($_POST['is_residential']) ? sanitize_text_field($_POST['is_residential']) : 0;

		WC()->session->set('woo-machship-post_data', $post_data);
		WC()->session->set('woo-machship-tosuburb', $tosuburb);
		WC()->session->set('woo-machship-tostate', $tostate);
		WC()->session->set('woo-machship-topostcode', $topostcode);
		WC()->session->set('woo-machship-tolocationID', $toLocationID);

		WC()->customer->set_shipping_state(WC()->session->get('woo-machship-tostate'));
		WC()->customer->set_shipping_city(WC()->session->get('woo-machship-tosuburb'));
		WC()->customer->set_shipping_postcode(WC()->session->get('woo-machship-topostcode'));
		WC()->customer->set_calculated_shipping(true);
		WC()->session->__unset('woo-machship-phone-order');

		WC()->session->set('is_residential', $is_residential);

		$destination = array(
			'state' => $tosuburb,
			'postcode' => $topostcode,
		);
		// add session
		WC()->session->set('selected_shipping_destination', $destination);
	}

	/**
	 * set shipping state of there's value in the session
	 * @return mixed
	 */
	public function woo_machship_filter_shipping_state($true)
	{
		WC()->customer->set_shipping_state(WC()->session->get('woo-machship-tostate'));
		return $true;
	}

	/**
	 * set shipping suburb of there's value in the session
	 * @param $true
	 * @return mixed
	 */
	public function woo_machship_filter_shipping_city($true)
	{
		WC()->customer->set_shipping_city(WC()->session->get('woo-machship-tosuburb'));
		return $true;
	}

	/**
	 *  set shipping postcode of there's value in the session
	 * @param $true
	 * @return mixed
	 */
	public function woo_machship_filter_shipping_postcode($true)
	{
		WC()->customer->set_shipping_postcode(WC()->session->get('woo-machship-topostcode'));
		return $true;
	}

	/**
	 * add hidden fields on checkout
	 */
	public function woo_machship_add_machship_checkout_hidden_field()
	{

		global $woocommerce;

		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();
		$items = $woocommerce->cart->get_cart();
		$disabled_items = Woo_Machship_Custom::woo_machship_count_disabled_items($items, $settings);

		if ($disabled_items < count($items)) {
			echo '<div id="user_link_hidden_checkout_field">
								<input type="hidden" class="input-hidden" name="billing_state" id="hidden-billing_state" value="">
					</div>';

			echo '<div id="user_link_hidden_checkout_field">
									<input type="hidden" class="input-hidden" name="shipping_state" id="hidden-shipping_state" value="">
					</div>';
			echo '<div id="location_id_hidden_checkout_field">
									<input type="hidden" class="input-hidden" name="ToLocationID" id="hidden-ToLocationID" value="">
					</div>';
		}
	}

	/**
	 * checkout order review
	 */
	public function woo_machship_checkout_update_order_review()
	{
		$packages = WC()->cart->get_shipping_packages();

		foreach ($packages as $key => $value) {
			$shipping_session = "shipping_for_package_" . $key;

			unset(WC()->session->$shipping_session);
		}
		WC()->cart->calculate_shipping();
		return;
	}

	public function woo_machship_add_machship_cart_residential_field()
	{
		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();

		echo "<tr style='border-bottom: 0; padding-bottom: 0; display:none;'><td colspan=2>";
		echo '<input type="hidden" class="input-hidden" id="disable_checkout_suburb_search" name="disable_checkout_suburb_search" id="hidden-disable_checkout_suburb_search" value="' . esc_attr($settings['disable_checkout_suburb_search']) . '">';
		echo "</td></tr>";

		if ($settings['has_residential'] == "yes") {
			echo "<tr style='border-bottom: 0; padding-bottom: 0;'><td colspan=2>";
			woocommerce_form_field('is_residential', array(
				'type'          => 'radio', // text, textarea, select, radio, checkbox, password, about custom validation a little later
				'required'  => true, // actually this parameter just adds "*" to the field
				'class'         => array('checkout-is-residential'), // array only, read more about classes and styling in the previous step
				'label'         => '',
				'label_class'   => array(), // sometimes you need to customize labels, both string and arrays are supported
				'options' => array( // options for <select> or <input type="radio" />
					1  => 'Residential',
					0  => 'Business',
				)
			), (WC()->session->get('is_residential')) ? WC()->session->get('is_residential') : 0);
			echo "
				<style>
					.page-id-8 .shipping {
						display: table-row;
					}
				</style>
			</td></tr>";
		}
	}

	public function woo_machship_add_machship_checkout_residential_field_review_order()
	{
		$settings = Woo_Machship_Custom::woo_machship_get_shipping_settings();

		echo "<tr style='border-bottom: 0; padding-bottom: 0;'><td colspan=2>";
		echo '<input type="hidden" class="input-hidden" id="disable_checkout_suburb_search" name="disable_checkout_suburb_search" id="hidden-disable_checkout_suburb_search" value="' . esc_attr($settings['disable_checkout_suburb_search']) . '">';
		echo "</td></tr>";

		if ($settings['has_residential'] == "yes") {
			echo "<tr style='border-bottom: 0; padding-bottom: 0;'><td colspan=2>";
			woocommerce_form_field('is_residential', array(
				'type'          => 'radio', // text, textarea, select, radio, checkbox, password, about custom validation a little later
				'required'  => true, // actually this parameter just adds "*" to the field
				'class'         => array(), // array only, read more about classes and styling in the previous step
				'label'         => '',
				'label_class'   => array(), // sometimes you need to customize labels, both string and arrays are supported
				'options' => array( // options for <select> or <input type="radio" />
					1  => 'Residential',
					0  => 'Business',
				)
			), (WC()->session->get('is_residential')) ? WC()->session->get('is_residential') : 0);
			echo "
			<script>
				jQuery(document).ready(function() {
					jQuery('input[name=is_residential]').on('click', function() {
						jQuery(document.body).trigger('update_checkout', {
							update_shipping_method: true
						});
					});
				})
			</script>
			</td></tr>";
		}
	}
}
