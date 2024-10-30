<?php

use Woo_Machship_Shipping\Common\Woo_Machship_Custom;
use Woo_Machship_Shipping\API\Woo_Machship_API;

// Abort if this file is called directly.
if (!defined('ABSPATH')) exit;

if (!class_exists(Woo_Machship_Load_Shipping_Method::class)) {

	/**
	 * Woo_Machship_Load_Shipping_Method
	 * This is a Shipping Method of WooCommerce
	 * where we can calculate the shipping cost by extending the WC_Shipping_Method class
	 * then overwriting the calculate_shipping function
	 */
	class Woo_Machship_Load_Shipping_Method extends \WC_Shipping_Method
	{

		/**
		 * company locations
		 * @var
		 */
		private $ms_company_locations;

		/**
		 * machship carriers
		 * @var
		 */
		private $ms_carriers;

		/**
		 * machship services
		 * @var
		 */
		private $ms_services;
		private $ms_accounts;

		/**
		 * @var
		 */
		private $machship;

		/**
		 * @var
		 */
		private $token;

		public function __construct($instance_id = 0)
		{
			$this->id = 'woo_machship_shipping';
			$this->instance_id  = absint($instance_id);
			$this->method_title = __('Machship Shipping', 'machship-shipping');
			$this->method_description = __('Machship Custom Shipping Method', 'machship-shipping');

			$this->supports = array(
				'shipping-zones',
				'settings'
			);
			// make sure this only runs in the admin
			if (!is_admin()) {
				return;
			}

			$this->woo_machship_shipping_method_init();

			$this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
			$this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Machship Shipping', 'machship-shipping');
		}

		public function woo_machship_shipping_method_init()
		{

			$this->woo_machship_init_machship();
			$this->woo_machship_init_form_fields();
			// predefined settings from WC_Shipping_Method
			$this->init_settings();

			// predefined settings from WC_Shipping_Method
			// Save settings in admin if you have any defined
			add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
		}

		/**
		 * @override function from WC_Shipping_Method
		 * calculate_shipping function.
		 *
		 * @access public
		 * @param mixed $package
		 * @return void
		 */
		public function calculate_shipping($package = array())
		{
			global $woocommerce;
			$package = array();

			$settings = $this->settings;
			$items = $woocommerce->cart->get_cart();
			$disabled_items = Woo_Machship_Custom::woo_machship_count_disabled_items($items, $settings);

			if ($disabled_items < count($items)) {
				$rates = Woo_Machship_Custom::woo_machship_cart_quote_request($items);

				if (!empty($rates)) {
					foreach ($rates as $rate) {
						$this->add_rate($rate);
					}
				}
			}
		}

		/**
		 * get data from machship
		 */
		public function woo_machship_init_machship()
		{
			$this->token = $this->get_option('machship_token');

			if (empty($this->machship) && !empty($this->token)) {
				$this->machship = new Woo_Machship_API($this->token);
			} else {

				$this->ms_services = [];
				$this->ms_carriers = [];
				$this->ms_company_locations = [];
				return;
			}

			// we need to remove the cache for location so we can retrieve the latest
			delete_transient( 'woo_machship_cached_locations' );

			$loc = $this->woo_machship_getCachedLocations();
			$this->ms_company_locations = !empty($loc) ? $loc->object : [];
			$this->woo_machship_initCachedCarrierAccountsAndServices();
		}

		/**
		 * Define settings field for this shipping
		 * @return void
		 */
		public function woo_machship_init_form_fields()
		{

			$product_meta_keys = $this->getOrSetProductMetaKeys();

			$order_status_opt = Woo_Machship_Custom::woo_machship_createOrderStatusOpt();
			$form_fields = array(
				'refresh_cached' => array('type' => 'refresh_cached'),
				'enabled' => array(
					'title' => esc_attr('Enable'),
					'type' => 'checkbox',
					'label' => __('Check this to enable Machship shipping', 'machship-shipping'),
					'default' => 'no',
					'id' => 'enable'
				),

				'mode' => array(
					'label'   => 'Mode',
					'type'      => 'radio',
					'default'   => 'dynamic',
					'options'   => array(
						'legacy' => 'Legacy Mode',
						'dynamic' => 'Dynamic Mode'
					),
					'description' => __('Legacy Mode - will use the woocommerce machship shipping plugin quote. <br> Dynamic Mode - will use Fusedship liverate', 'machship-shipping'),
					'desc_tip' => true,
					'class' => 'fusedship-required'
				),

				array('type' => 'separator', 'colspan' => 2),

				array(
					'title' => __('Machship Credentials', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'machship_credential',
				),
				'machship_token' => array(
					'title' => __('Machship API Token', 'machship-shipping'),
					'type' => 'text',
					'placeholder' => 'Enter Machship token',
					'default' => '',
					'required' => true,
				),
				'machship_site_url' => array(
					'title' => __('machship site url', 'machship-shipping'),
					'type' => 'text',
					'description' => __('This is to validate if the current site has the '),
					'default' => get_bloginfo( 'url' )
				),

				array('type' => 'separator', 'colspan' => 2),
				array(
					'title' => __('Widget Description', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'widget_description'
				),
				'widget_title' => array(
					'title'   => __('Title', 'machship-shipping'),
					'type'    => 'text',
					'default' => __('Quick Quote', 'machship-shipping'),
				),
				'widget_description' => array(
					'title'   => __('Description', 'machship-shipping'),
					'type'    => 'textarea',
					'default' => __('Enter your postcode and suburb to generate your quote.', 'machship-shipping')
				),
				'has_residential' => array(
					'label'   => 'Ask Users for Residential/Business',
					'type'      => 'radio',
					'default'   => 'no',
					'options'   => array(
						'no' => 'No',
						'yes' => 'Yes'
					)
				),
				'disable_checkout_suburb_search' => array(
					'label'   => 'Disable Checkout Suburb Suggest',
					'type'      => 'radio',
					'default'   => 'yes',
					'options'   => array(
						'no' => 'No',
						'yes' => 'Yes'
					)
				),

				array('type' => 'separator', 'colspan' => 2),

				// LEGACY FIELDS WILL BE HERE

				array(
					'title' => __('Shipping Margin', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'shipping_margin',
				),
				'margin_fixed_percent' => array(
					'title' => __('Margin Type:', 'machship-shipping'),
					'type' => 'select',
					'default' => 'Fixed',
					'options'  => array(
						'$' => 'Fixed ($)',
						'%' => 'Percentage (%)'
					),
					'css' => 'width: auto; min-width: 170px;',
					'class' => 'shipping_margin',
				),
				'simple_margin_amount' => array(
					'title' => esc_attr('Margin Amount', 'machship-shipping'),
					'type' => 'number',
					'default' => '0',
					'class' => 'small-text',
					'css' => 'width: auto;',
					'class' => 'shipping_margin',
				),
				array(
					'title' => __('Messages', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'woo-machship-messages',
					'class' => 'woo-machship-messages'
				),
				'no_available_shipping' => array(
					'title' => __('No Available Shipping', 'machship-shipping'),
					'type' => 'text',
					'description' => __('Your message if there is no available shipping option', 'machship-shipping'),
					'default' => __('There are no shipping methods available. Please contact us for a shipping quote.', 'machship-shipping'),
					'desc_tip'  => true,
					'class' => 'no_available_shipping'
				),
				'contact_us' => array(
					'title' => __('Contact Us', 'machship-shipping'),
					'type' => 'textarea',
					'description' => __('For additional services', 'machship-shipping'),
					'default' => '',
					'desc_tip'  => true,
					'class' => 'contact_us',
					'css'      => 'width: 400px; max-width: 80%;'
				),
				'fusedship_product_attributes' => array(
					'title' => __('Add attributes to live rates', 'machship-shipping'),
					'type' => 'multiselect',
					'description' => __('Add the selected product meta key as data attributes to live rates', 'machship-shipping'),
					'default' => '',
					'desc_tip'  => true,
					'options' => $product_meta_keys,
					'class' => 'fs_product_attr_list'
				),
				array('type' => 'separator', 'colspan' => 2),
				array(
					'title' => __('Your Warehouse Location', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'warehouse_location_repeater',
				),
				'warehouse_locations' => array(
					'type' => 'warehouse_locations',
					'fields' => array(
						'warehouse_name' => array(
							'title' => __('Warehouse Name', 'machship-shipping'),
							'type' => 'text',
							'id' => 'warehouse-name',
							'placeholder' => __('Name', 'machship-shipping'),
							'label' => __('Warehouse Name', 'machship-shipping')
						),
						'company_location' => array(
							'title' => __('Address', 'machship-shipping'),
							'type'  => 'select',
							'id'    => 'company-location',
							'label' => __('Warehouse Name', 'machship-shipping'),
							'options' => $this->ms_company_locations
						),
						'company_id' => array(
							'type' => 'hidden',
							'id' => 'company_id',
						),
						'location_id' => array(
							'type' => 'hidden',
							'id' => 'location_id',
						),
						'postcode' => array(
							'type' => 'hidden',
							'id' => 'postcode',
						),
						'suburb' => array(
							'type' => 'hidden',
							'id' => 'suburb',
						)
					)
				),
				array('type' => 'separator',  'colspan' => 3),
				array(
					'title' => __('Carrier Groups', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'carrier_groups_repeater',
					'class' => 'legacy-fields'
				),
				'carrier_groups' => array(
					'type' => 'carrier_groups',
					'fields' => array(
						'exclude_for_residential' => array(
							'title' => esc_attr('Exclude for Residential', 'machship-shipping'),
							'type' => 'select-yes-no',
							'id' => 'exclude_for_residential'
						),
						'group_name' => array(
							'title' => __('Group Name', 'machship-shipping'),
							'type' => 'text',
							'id' => 'group-name',
						),
						'group_description' => array(
							'title' => __('Description', 'machship-shipping'),
							'type'  => 'textarea',
							'id'    => 'group-description',
						),
						'min_weight' => array(
							'title' => __('Min. Weight', 'machship-shipping'),
							'type' => 'text',
							'id' => 'min-weight',
						),
						'max_weight' => array(
							'title' => __('Max. Weight', 'machship-shipping'),
							'type' => 'text',
							'id' => 'max-weight',
						),
						'machship_surcharges' => array(
							'title'   => __('Add Surcharges', 'machship-shipping'),
							'type'    => 'text',
							'default' => '',
							'placeholder' => '(comma delimited)'
						),
						'machship_question_ids' => array(
							'title'   => __('Add QuestionIds', 'machship-shipping'),
							'type'    => 'text',
							'default' => '',
							'placeholder' => '(comma delimited)'
						),
						'carrier_group_order' => array(
							'title'   => __('Order', 'machship-shipping'),
							'type'    => 'number',
							'default' => '',
						),
						'carrier_services' => array(
							'fields' => array(
								'carrier_name' => array(
									'type' => 'hidden'
								)
							)
						)
					)
				),
				array('type' => 'separator', 'colspan' => 2),
				array(
					'title'  => __('Quick Quote Positions', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'id'  => 'quick_quote_positions',
				),
				'product_page' => array(
					'label'     => 'Product Page',
					'type'      => 'radio',
					'default'   => 'popup',
					'options'   => array(
						'hide' => 'Hide quick quote on the product page',
						'popup' => 'Display in a pop up',
						'on_page' => 'Display on the page itself'
					)
				),
				'popup_button_text' => array(
					'title' => __('Popup Button text', 'machship-shipping'),
					'type' => 'text',
					'description' => '',
					'default' => __('Quick Shipping Quote', 'machship-shipping'),
					'desc_tip'  => true,
					'class' => 'popup_button_text_input'
				),
				'product_position' => array(
					'title' => __('Position', 'machship-shipping'),
					'type' => 'select',
					'default' => 'woocommerce_after_single_product_summary',
					'desc_tip'  => true,
					'options'  => array(
						'woocommerce_after_single_product_summary' => esc_attr('woocommerce_after_single_product_summary'),
						'woocommerce_before_add_to_cart_form' => esc_attr('woocommerce_before_add_to_cart_form'),
						'woocommerce_before_add_to_cart_button' => esc_attr('woocommerce_before_add_to_cart_button'),
						'woocommerce_after_add_to_cart_button' => esc_attr('woocommerce_after_add_to_cart_button'),
						'woocommerce_product_meta_start' => esc_attr('woocommerce_product_meta_start'),
						'woocommerce_product_meta_end' => esc_attr('woocommerce_product_meta_end'),
						'woocommerce_after_single_product' => esc_attr('woocommerce_after_single_product'),
						'other' => esc_attr('other')
					),
					'css' => 'width: auto;'
				),
				'product_position_other' => array(
					'type' => 'text',
					'placeholder' => 'Enter Other Position',
					'default' => '',
					'required' => false,
				),
				array('type' => 'separator', 'colspan' => 2),
				array(
					'title'  => __('Quote Display Options', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'id'  => 'quote_response',
				),
				'fusedship_quote_description_hideshow' => array(
					'title' => __('Cart & Checkout', 'machship-shipping'),
					'type' => 'checkbox',
					'label' => __('Hide quote description', 'machship-shipping'),
					'default' => 'no'
				),
				array('type' => 'separator', 'colspan' => 2),
				array(
					'title'  => __('Tax Settings', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'id'  => 'tax_settings',
					'class' => 'legacy-fields'
				),
				'tax_exclude_product' => array(
					'title'  => __('Exclude GST from Product Page Widget', 'machship-shipping'),
					'type' => 'checkbox',
					'label' => __(' ', 'machship-shipping'),
					'default' => 'no'
				),
				'tax_exclude_shipping_price' => array(
					'title'  => __('Exclude GST from cart & checkout shipping price', 'machship-shipping'),
					'type' => 'checkbox',
					'label' => __(' ', 'machship-shipping'),
					'default' => 'no'
				),
				array('type' => 'separator', 'colspan' => 2),
				array(
					'title'  => __('Advance Settings', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'id'  => 'machship_adv_settings',
				),
				'force_enable_products' => array(
					'title' => __('Force Enable For All Products', 'machship-shipping'),
					'type' => 'checkbox',
					'label' => __('ADVANCED - Do not enable without being advised', 'machship-shipping'),
					'default' => 'no'
				),

				array('type' => 'separator', 'colspan' => 2),

				array(
					'title' => __('Fusedship Credentials', 'machship-shipping'),
					'type'  => 'title',
					'desc'  => '',
					'class' => 'fusedship-credentials',
				),
				'fusedship_id' => array(
					'title' => __('Fusedship ID', 'machship-shipping'),
					'type' => 'text',
					'placeholder' => 'Enter Fusedship Integration ID',
					'default' => '',
					'required' => false,
					'class' => 'fusedship-required'
				),
				'fusedship_token' => array(
					'title' => __('Fusedship Token', 'machship-shipping'),
					'type' => 'text',
					'placeholder' => 'Enter Fusedship Token',
					'default' => '',
					'required' => false,
					'class' => 'fusedship-required'
				),

				'fusedship_webhook' => array(
					'title' => __('Fusedship Webhook', 'machship-shipping'),
					'type' => 'checkbox',
					'label' => __('Enable webhook that sends order updates to Fusedship', 'machship-shipping'),
					'default' => 'no'
				),
				'fusedship_webhook_status' => array(
					'title' => __('Webhook Order Status', 'machship-shipping'),
					'type' => 'multiselect',
					'default' => [],
					'desc_tip'  => true,
					'options' => wc_get_order_statuses(),
					'css' => 'width: auto;'
				),
			);

			$this->form_fields = $form_fields;
		}

		private function getOrSetProductMetaKeys()
		{
			$product_meta_keys = get_transient('woo_machship_cached_product_meta_keys');

			if (empty($product_meta_keys)) {

				global $wpdb;

				// get postmeta (this is the table where product meta is stored)
				// then get all the meta that has posts (products) and group them by postmeta meta_key
				$query = "SELECT {$wpdb->postmeta}.meta_key FROM {$wpdb->postmeta}  INNER JOIN {$wpdb->posts} ON ( {$wpdb->posts}.id = {$wpdb->postmeta}.post_id ) WHERE 1=1  AND {$wpdb->postmeta}.meta_key != '' AND ({$wpdb->posts}.post_type = 'product' OR {$wpdb->posts}.post_type = 'product_variation')  AND {$wpdb->posts}.post_status = 'publish' GROUP BY {$wpdb->postmeta}.meta_key ORDER BY {$wpdb->postmeta}.meta_key DESC";
				$results = $wpdb->get_results($query);

				$product_meta_keys = array();
				if (!empty($results)) {
					$product_meta_keys = array();
					foreach ($results as $result) {
						$product_meta_keys[$result->meta_key] = $result->meta_key;
					}

					set_transient('woo_machship_cached_product_meta_keys', $product_meta_keys, 3 * HOUR_IN_SECONDS);
				}
			}

			return $product_meta_keys;
		}



		/**
		 * set to transient with 1 day expiration
		 * set/get cached company locations
		 * @return mixed
		 */
		public function woo_machship_getCachedLocations()
		{
			$locations = get_transient('woo_machship_cached_locations');

			if (empty($locations) && !empty($this->token)) {
				$locations = $this->machship->woo_machship_getAllCompanyLocations();
				set_transient('woo_machship_cached_locations', $locations, 24 * HOUR_IN_SECONDS);
			}

			return $locations;
		}

		public function woo_machship_initCachedCarrierAccountsAndServices()
		{

			$carriers = get_transient('woo_machship_cached_carriers');
			$carrierServices = get_transient('woo_machship_cached_services');

			// in this migration, we need to validate cached thoroughly
			if (
				!empty($carriers)             &&
				!empty($carrierServices)      &&
				!is_object($carriers)         &&
				!is_object($carrierServices)
			) {
				$this->ms_carriers = $carriers;
				$this->ms_services = $carrierServices;
				return;
			}

			$res = $this->machship->woo_machship_getCarrierAccountAndServices();

			$results = $res ? $res->object : [];

			$accounts = [];
			$carriers = [];
			$carrierServices = [];

			// 1. Loop Carriers
			foreach ($results as $carrier) {
				$carriers[] = $carrier;

				// 2. Loop Carrier Accounts
				foreach ($carrier->carrierAccounts as $account) {

					$carrierId = intval($account->carrierId);

					// we use carrier id as index so we can group them easily
					$accounts[$carrierId][] = $account;


					// 3. Loop Carrier Services
					if(isset($carrierServices[$carrierId])){
						$carrierServices[$carrierId] = array_merge( $carrierServices[$carrierId], $account->carrierServices );
					} else {
						$carrierServices[$carrierId] = $account->carrierServices;
					}
				}
			}

			$this->ms_carriers = $carriers;
			$this->ms_services = $carrierServices;
			$this->ms_accounts = $accounts;

			set_transient('woo_machship_cached_carriers', $carriers, 24 * HOUR_IN_SECONDS);
			set_transient('woo_machship_cached_services', $carrierServices, 24 * HOUR_IN_SECONDS);
		}

		/**
		 * @return mixed
		 */
		public function woo_machship_flattenedArrayServices()
		{
			return call_user_func_array('array_merge', $this->ms_services);
		}

		/**
		 * @param array $haystack
		 * @param $needle
		 * @return array
		 */
		public function woo_machship_array_column_recursive(array $haystack, $needle)
		{
			$found = [];
			array_walk_recursive($haystack, function ($value, $key) use (&$found, $needle) {
				if ($key == $needle)
					$found[] = $value;
			});
			return $found;
		}



		// ************************************* [ GENERATE CUSTOMIZE HTML ] ************************************* //

		/**
		 * Separator HTML
		 */
		public function generate_separator_html($key, $data)
		{
			ob_start(); ?>
			<tr class="separator">
				<td colspan="<?php echo esc_attr($data['colspan']); ?>" style="padding: 0;">
					<hr>
				</td>
			</tr>
		<?php
			return ob_get_clean();
		}

		/**
		 * Warehouse Repeater HTML
		 */
		public function generate_warehouse_locations_html($key, $data)
		{
			$location_repeater = $this->get_option($key);
			$field_key = $this->get_field_key($key);

			$allowed_html = [
				'td' => [
					'style' => true,
					'class' => true,
				],

				'select' => [
					'name' => true,
					'class' => true
				],

				'button' => [
					'class' => true
				],

				'option' => [
					'data-companyid' => true,
					'data-locationid' => true,
					'data-postcode' => true,
					'data-suburb' => true,
					'data-addressline1' => true,
					'value' => true,
					'selected' => true,
				],

				'input' => [
					'style' => true,
					'type' => true,
					'placeholder' => true,
					'name' => true,
					'class' => true,
				]
			];


			ob_start();
			?>
			<style>
				div.from_suburb_list {
					width: 200px;
					display: inline-block
				}

				div.from_suburb_list li {
					display: block;
					border: 1px solid #ccc;
					padding: 7px 10px;
				}

				div.from_suburb_list li:hover {
					background: #ffffff;
					cursor: pointer;
					vertical-align: top;
				}

				.woo_machship_find_suburb {
					display: inline-block
				}

				.loader-quote-product {
					display: inline-block;
					vertical-align: bottom;
				}

				.form-table td.vtop {
					vertical-align: top !important;
				}
			</style>
			<tr>
				<td>Warehouse Name</td>
				<td>Machship Company Locations</td>
			</tr>
			<?php
			if (isset($data['fields']) && !empty($data['fields']) && empty($location_repeater)) {
			?>
				<tr valign="top" class="row_loc default_loc">
					<?php
					foreach ($data['fields'] as $key2 => $field) {
						$name = $field_key . "[$key2][]";
						switch ($field['type']) {
							case 'text':
								echo "<td style='width:40%' class='vtop'><input style='width:100%;' type='text' placeholder='" . esc_attr($field['title']) . "' name='" . esc_attr($name) . "' class='" . esc_attr($key2) . "' /></td>";
								break;
							case 'select':
								$options = !empty($field['options']) ? $field['options'] : [];
								$opt_html = '<option>Select Location</option>';

								// sort option by name
								$columns = array_column($options, 'name');
								array_multisort($columns, SORT_ASC, $options);

								foreach ($options as $option) {
									$address2 = !empty($option->addressLine2) ? ', ' . $option->addressLine2 . ', ' : '';
									$address = $option->name . ' - ' . $option->addressLine1 . $address2 . ' ' . $option->location->suburb . ' ' . $option->location->state->code . ' ' . $option->location->postcode;
									$opt_html .= '<option data-companyid="' . esc_attr($option->companyId) . '" data-locationid="' . esc_attr($option->location->id) . '"  data-postcode="' . esc_attr($option->location->postcode) . '" data-suburb="' . esc_attr($option->location->suburb) . '" data-addressline1="' . esc_attr($option->addressLine1) . '" value="' . esc_attr($option->id) . '">' . esc_html($address) . '</option>';
								}
								echo "<td style='width:60%' class='vtop'><select name='" . esc_attr($name) . "' class='company-location-select'>" . wp_kses($opt_html, $allowed_html) . "</select></td>";
								break;
							case 'hidden':
								echo "<input type='hidden' value='' name='" . esc_attr($name) . "' class='" . esc_attr($key2) . "'/>";
								break;
						}
					?>
					<?php
					} ?>
				</tr>
				<?php } else {
				$i = 0;
				if (!empty($location_repeater)) {
					foreach ($location_repeater as $id => $loc) {
				?>
						<tr valign="top" class="row_loc">
							<?php
							foreach ($data['fields'] as $key2 => $field) {
								$name = $field_key . "[$key2][]";
								switch ($field['type']) {
									case 'text':
										echo "<td style='width:40%' class='vtop'><span class='warehouse-idx'>" . esc_html($i) . "</span><input type='text' placeholder='" . esc_attr($field['title']) . "' name='" . esc_attr($name) . "' value='" . esc_attr($loc[$key2]) . "' class='" . esc_attr($key2) . "'/></td>";
										break;
									case 'select':
										$options = !empty($field['options']) ? $field['options'] : [];
										$opt_html = '<option>Select Location</option>';

										// sort option by name
										$columns = array_column($options, 'name');
										array_multisort($columns, SORT_ASC, $options);

										foreach ($options as $option) {
											$selected = $option->id == $loc[$key2] ? 'selected="selected"' : '';
											$address2 = !empty($option->addressLine2) ? ', ' . $option->addressLine2 . ', ' : '';
											$address = $option->name . ' - ' . $option->addressLine1 . $address2 . ' ' . $option->location->suburb . ' ' . $option->location->state->code . ' ' . $option->location->postcode;
											$opt_html .= '<option data-companyid="' . esc_attr($option->companyId) . '" data-locationid="' . esc_attr($option->location->id) . '"  data-postcode="' . esc_attr($option->location->postcode) . '" data-suburb="' . esc_attr($option->location->suburb) . '" data-addressline1="' . esc_attr($option->addressLine1) . '" value="' . esc_attr($option->id) . '" ' . $selected . '>' . esc_html($address) . '</option>';
										}
										$row_loc = "<td style='width:60%' class='vtop'><select name='" . esc_attr($name) . "' class='company-location-select'>" . $opt_html . "</select>";

										if ($i != 0) {
											$row_loc .= "<button class='button button-primary remove_location'>−</button>";
										}
										$row_loc .= "</td>";



										echo wp_kses($row_loc, $allowed_html);
										break;
									case 'hidden':
										echo "<input type='hidden' name='" . esc_attr($name) . "' value='" . esc_attr($loc[$key2]) . "' class='" . esc_attr($key2) . "'/>";
										break;
								}
							}
							?>
						</tr>
			<?php
						$i++;
					} //end foreach locations
				} //end if
			}
			?>
			<tr>
				<td colspan="3"><button class="button button-primary add_location_repeater">+ Add Location</button></td>
			</tr>
			<?php
			return ob_get_clean();
		}

		public function generate_refresh_cached_html($key, $data)
		{
			ob_start(); ?>
			<div id="woo-machship-reset">
				<button id="woo_machship_reset_cache" class="button button-primary" type="button">
					<?php echo esc_html__('Reset cache', 'machship-shipping') ?>
				</button>
				<p class="reset-cache-error">An error occured...</p>
			</div>
			<?php return ob_get_clean();
		}

		public function generate_carrier_groups_html($key, $data)
		{
			$carrier_group_repeater = $this->get_option($key);
			$field_key = $this->get_field_key($key);
			ob_start();
			?>
			<style>
				.group_loc fieldset {
					padding: 20px;
					border: 1px solid #000000;
				}
			</style>
			<?php add_thickbox(); ?>
			<tr>
				<td colspan="3" class='forminp' align="right"><button class="button button-primary add_group">+ Add Group</button></td>
			</tr>
			<?php
			if (isset($data['fields']) && !empty($data['fields']) && empty($carrier_group_repeater)) {
			?>
				<tr valign="top" class="group_loc default_group" id="carrier_group_0">
					<td colspan="3" class="forminp">
						<fieldset>
							<table class="form-table">
								<?php
								foreach ($data['fields'] as $key2 => $field) {
									$name = $field_key . "[$key2][]";
									if ($key2 != 'carrier_services') {
										$placeholder = isset($field['placeholder']) ? $field['placeholder'] : $field['title'];
								?>
										<tr valign="top" class="row_group default_group">
											<th scope="row" class="titledesc">
												<label for="woocommerce_woo_machship_shipping_name"><?php echo esc_attr($field['title']); ?></label>
											</th>
											<?php
											switch ($field['type']) {
												case 'text':
													echo "<td class='forminp'><input type='text' placeholder='" . esc_attr($placeholder) . "' name='" . esc_attr($name) . "' class='" . esc_attr($key2) . "'/></td>";
													break;
												case 'textarea':
													echo "<td class='forminp'><textarea name='" . esc_attr($name) . "'></textarea></td>";
													break;
												case 'number':
													echo "<td class='forminp'><input type='number' placeholder='" . esc_attr($field['title']) . "' name='" . esc_attr($name) . "' class='" . esc_attr($key2) . "'/></td>";
													break;
												case 'select-yes-no':
													echo "<td class='forminp'>
												<select name='" . esc_attr($name) . "'>
													<option value='no' selected>No</option>
													<option value='yes'>Yes</option>
												</select>
											</td>";

													break;
											}
											?>
										</tr>
									<?php } else { ?>
										<tr class="default_carrier_services" id="carrier_group_service_1">
											<th>
												<h3>Carrier Services</h3>
											</th>
											<td colspan="2" class='forminp' align="center">
												<a title="Add Carrier Services" data-groupnum="0" href="#TB_inline?width=600&amp;height=500&amp;inlineId=carrier_services_modal" class="button button-primary add_carrier carrier_services_btn">
													+ Add Carrier Services </a>
											</td>
										</tr>
										<tr class="carrier_services_row">
											<td colspan="2">
												<table class="form-table widefat striped table_carrier_services">
													<tr>
														<td><strong>Carrier Name</strong></td>
														<td><strong>Service Name</strong></td>
														<td></td>
													</tr>
												</table>
											</td>
										</tr>

								<?php  }
								} ?>
							</table>
						</fieldset>
					</td>
				</tr>
				<?php } else {
				$i = 0;
				if (!empty($carrier_group_repeater)) {
					foreach ($carrier_group_repeater as $id => $group) {
				?>
						<tr valign="top" class="group_loc" id="carrier_group_<?php echo esc_attr($i); ?>">
							<td colspan="3" class="forminp">
								<fieldset>
									<?php if ($i != 0) { ?>
										<button class="button button-primary remove_group_btn">Remove Group</button>
									<?php } ?>
									<table class="form-table">
										<?php
										foreach ($data['fields'] as $key2 => $field) {
											$name = $field_key . "[$key2][]";
											if ($key2 != 'carrier_services') {
												$placeholder = isset($field['placeholder']) ? $field['placeholder'] : $field['title'];
												$value = isset($group[$key2]) ? $group[$key2] : '';
										?>
												<tr valign="top" class="row_group default_group">
													<th scope="row" class="titledesc">
														<label for="woocommerce_woo_machship_shipping_name"><?php echo esc_html($field['title']); ?></label>
													</th>
													<?php
													switch ($field['type']) {
														case 'text':
															echo "<td class='forminp'><input type='text' placeholder='" . esc_attr($placeholder) . "' name='" . esc_attr($name) . "' value='" . esc_attr($value) . "' class='" .esc_attr($key2) . "'/></td>";
															break;
														case 'textarea':
															echo "<td class='forminp'><textarea name='" . esc_attr($name) . "'>" . esc_html($group[$key2]) . "</textarea></td>";
															break;
														case 'number':
															echo "<td class='forminp'><input type='number' placeholder='" . esc_attr($placeholder) . "' name='" . esc_attr($name) . "' value='" . esc_attr($value) . "' class='" . esc_attr($key2) . "'/></td>";
															break;
														case 'select-yes-no':
															echo "<td class='forminp'>
																<select name='" . esc_attr($name) . "'>
																	<option value='no' " . (($value == 'no') ? "selected" : "") . ">No</option>
																	<option value='yes' " . (($value == 'yes') ? "selected" : "") . ">Yes</option>
																</select>
															</td>";

															break;
													}
													?>
												</tr>
											<?php } else { ?>
												<tr class="default_carrier_services" id="carrier_group_service_<?php echo esc_attr($i); ?>">
													<th>
														<h3>Carrier Services</h3>
													</th>
													<td colspan="2" class='forminp' align="center">
														<a title="Add Carrier Services" data-groupnum="<?php echo esc_attr($i); ?>" href="#TB_inline?width=600&amp;height=500&amp;inlineId=carrier_services_modal" class="button button-primary add_carrier carrier_services_btn">
															+ Add Carrier Services </a>
													</td>
												</tr>
												<tr class="carrier_services_row">
													<td colspan="2">
														<table class="form-table widefat striped table_carrier_services">
															<tr>
																<td><strong>Carrier Name</strong></td>
																<td><strong>Service Name</strong></td>
																<td></td>
															</tr>
															<?php
															if (!empty($this->ms_carriers) && !empty($group[$key2]) && isset($group[$key2]['carrier_id']) && !empty($group[$key2]['carrier_id'])) {
																foreach ($group[$key2]['carrier_id'] as $cServiceKey => $c) {
																	$carrierIndex = array_search($c, array_column($this->ms_carriers, 'id'));

																	if (empty($carrierIndex) && $carrierIndex !== 0) {
																		// skip this because its a duplicate carrier
																		continue;
																	}

																	$carrierData = $this->ms_carriers[$carrierIndex];
																	$serviceID = $group[$key2]['service_id'][$cServiceKey];
																	$serviceIndex = array_search($serviceID, array_column($this->ms_services[$c], 'id'));

																	if (empty($serviceIndex) && $serviceIndex !== 0) {
																		// skip this because its a duplicate carrier service
																		continue;
																	}


																	$serviceData = $this->ms_services[$c][$serviceIndex];

															?>
																	<tr class="row_service row_service_<?php echo esc_attr($serviceID); ?>">
																		<td><?php echo esc_html($carrierData->name); ?></td>
																		<td><?php echo esc_html($serviceData->name); ?></td>
																		<td><button data-id="<?php echo esc_attr($serviceID); ?>" class="button button-primary remove_service">&minus;</button></td>
																		<input type="hidden" name="<?php echo esc_attr($field_key . "[carrier_services][$i][service_id][]"); ?>" class="hidden_service_field" value="<?php echo esc_attr($serviceID); ?>" />
																		<input type="hidden" name="<?php echo esc_attr($field_key . "[carrier_services][$i][carrier_id][]"); ?>" class="hidden_carrier_field" value="<?php echo esc_attr($c); ?>" />
																	</tr>
															<?php
																}
															}
															?>
														</table>
													</td>
												</tr>

										<?php  }
										} ?>
									</table>
								</fieldset>
							</td>
						</tr>
			<?php
						$i++;
					} //end foreach locations
				} //end if
			}
			?>
			<tr>
				<td colspan="3" class='forminp' align="right"><button class="button button-primary add_group">+ Add Group</button></td>
			</tr>
			<table style="display: none">
				<tr>
					<td id="carrier_services_modal" style="display: none;">
						<table class="form-table striped">
							<tr>
								<td colspan="3" align="right">
									<label>Filter By Carrier</label>
									<select id="filter-by-carrier">
										<option value=""></option>
										<?php
										if (!empty($this->ms_carriers)) {
											foreach ($this->ms_carriers as $carrier) {
										?>
												<option value="<?php echo esc_attr($carrier->id); ?>"><?php echo esc_html($carrier->name); ?></option>
										<?php
											}
										}
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td align="center"><label>Carrier</label></td>
								<td align="center"><label>Service<label></td>
								<td align="center">
									<label>Select<label>
											<input id="select_all_carrier_services" type="checkbox" />
								</td>
							</tr>
							<tbody>
								<?php
								if (!empty($this->ms_services)) {
									foreach ($this->ms_services as  $carrierID => $services) {
										$index = array_search($carrierID, array_column($this->ms_carriers, 'id'));
										$cName = $this->ms_carriers[$index]->name;
										foreach ($services as $service) {
								?>
											<tr class="carrier carrier_<?php echo esc_attr($carrierID); ?>">
												<td align="center"><?php echo esc_html($cName); ?></td>
												<td align="center"><?php echo esc_html($service->name); ?></td>
												<td align="center"><input data-carrierid="<?php echo esc_attr($carrierID); ?>" data-cname="<?php echo esc_attr($cName); ?>" data-name="<?php echo esc_attr($service->name); ?>" type="checkbox" class="select_service" value="<?php echo esc_attr($service->id); ?>" /></td>
											</tr>
								<?php
										}
									}
								}
								?>
								<tr style="background-color:#ffffff;position: absolute;right:0;width: 100%;border-top: 1px solid #cccccc;bottom: -45px">
									<td colspan="3" align="center"><input data-groupnum="" data-fieldname="<?php echo esc_attr($field_key); ?>" id="add_to_service_list" type="button" class="button button-primary" value="Add Selected Carrier Services" /></td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</table>
			<?php
			return ob_get_clean();
		}

		/**
		 * Multiple checkboxes
		 */
		public function generate_multicheckbox_html($key, $data)
		{

			$field_key = $this->get_field_key($key);
			$data['desc_tip'] = isset($data['desc_tip']) ? $data['desc_tip'] : '';

			ob_start(); ?>

			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo wp_kses_post($this->get_tooltip_html($data)); // WPCS: XSS ok.
																																																						?></label>
				</th>
				<td class="forminp">
					<?php
					$values = $this->get_option($key);
					if (empty($values) && !empty($data['default'])) {
						$values = $data['default'];
					}

					foreach ($data['options'] as $key => $option) {
						$disabled = isset($option['disabled']) ? $option['disabled'] : false; ?>

						<fieldset class="multicheckbox">
							<label for="<?php echo esc_attr($field_key . '-' . $key); ?>">
								<input class="<?php echo esc_attr($field_key); ?> <?php echo esc_attr($data['class']); ?>" type="checkbox" name="<?php echo esc_attr($field_key); ?>[]" id="<?php echo esc_attr($field_key . '-' . $key); ?>" value="<?php echo esc_attr($option['value']); ?>" <?php checked(in_array($option['value'], $values)); ?> <?php if ($disabled) echo "onclick='return false;' readonly"; ?> /> <?php echo wp_kses_post($option['label']); ?></label>
						</fieldset>
					<?php } ?>
					<?php echo wp_kses_post($this->get_description_html($data)); // WPCS: XSS ok.
					?>
				</td>
			</tr>

			<?php
				return ob_get_clean();
		}

		/**
		 * @override functions from WC_Shipping_Method
		 * Validate to remove default validations
		 */
		public function validate_warehouse_locations_field($key, $value)
		{
			if (is_array($value)) {
				$loc_arr = [];
				$warehouse_name = array_map('sanitize_text_field', $value['warehouse_name']);
				foreach ($warehouse_name  as $key => $location) {
					$loc_arr[] = array(
						'warehouse_name' => sanitize_text_field($location),
						'company_location' => sanitize_text_field($value['company_location'][$key]),
						'company_id' => sanitize_text_field($value['company_id'][$key]),
						'location_id' => sanitize_text_field($value['location_id'][$key]),
						'suburb' => sanitize_text_field($value['suburb'][$key]),
						'postcode' => sanitize_text_field($value['postcode'][$key]),
					);
				}
			}
			$value = $loc_arr;
			return $value;
		}

		/**
		 * @override function from WC_Shipping_Method
		 * @param $key
		 * @param $value
		 * @return mixed
		 */
		public function validate_carrier_groups_field($key, $value)
		{
			if (is_array($value)) {
				$group_arr = [];

				$group_name = array_map('sanitize_text_field', $value['group_name']);
				foreach ($group_name  as $key => $group) {
					$group_arr[] = array(
						'exclude_for_residential' => $value['exclude_for_residential'][$key],
						'group_name' => sanitize_text_field($group),
						'group_description' => sanitize_text_field($value['group_description'][$key]),
						'min_weight' => sanitize_text_field($value['min_weight'][$key]),
						'max_weight' => sanitize_text_field($value['max_weight'][$key]),
						'machship_surcharges' => sanitize_text_field($value['machship_surcharges'][$key]),
						'machship_question_ids' => sanitize_text_field($value['machship_question_ids'][$key]),
						'carrier_group_order' => sanitize_text_field($value['carrier_group_order'][$key]),
						'carrier_services' => isset($value['carrier_services'][$key]) ? $value['carrier_services'][$key] : []
					);
				}
			}
			$value = $group_arr;
			return $value;
		}

		/**
		 * @param $key
		 * @param $data
		 * @return string
		 */
		public function generate_radio_html($key, $data)
		{
			$field_key = $this->get_field_key($key);
			$data['desc_tip'] = isset($data['desc_tip']) ? $data['desc_tip'] : '';
			$class = $data['class'] ?? '';

			ob_start(); ?>

			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['label']); ?> <?php echo wp_kses_post($this->get_tooltip_html($data)); // WPCS: XSS ok.
																																																						?></label>
				</th>
				<td class="forminp">
					<?php
					$values = $this->get_option($key);
					if (empty($values) && !empty($data['default'])) {
						$values = $data['default'];
					}

					foreach ($data['options'] as $key => $option) {
					?>
						<fieldset class="radio">
							<label for="<?php echo esc_attr($field_key . '-' . $key); ?>">
								<input class="<?php echo esc_attr($field_key); ?> <?php echo esc_attr($class); ?>" type="radio" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key . '-' . $key); ?>" value="<?php echo esc_attr($key); ?>" <?php checked($key, $values); ?> /> <?php echo wp_kses_post($option); ?></label>
						</fieldset>
					<?php } ?>
					<?php echo wp_kses_post($this->get_description_html($data)); // WPCS: XSS ok.
					?>
				</td>
			</tr>

			<?php
			return ob_get_clean();
		}

	}
}
