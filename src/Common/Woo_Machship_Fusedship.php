<?php
namespace Woo_Machship_Shipping\Common;

use Woo_Machship_Shipping\API\Woo_Machship_Fusedship_API;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Woo_Machship_Fusedship {


    private $settings;
    private $fusedship;

    public function __construct($settings) {
        $this->settings = $settings;
    }

    public function woo_machship_init() {
        // Initialize whats needed for this class
        $token = $this->settings['fusedship_token'];
        $integrationId = $this->settings['fusedship_id'];

        // throw error if token/integrationId is missing
        if (empty($token) || empty($integrationId)) {
            throw new \Exception("Error! Can't Initialize Woo_Machship_Fusedship without integration token and id");
        }

        // Init Library
        $this->fusedship = new Woo_Machship_Fusedship_API($token, $integrationId);
    }

    // ------------ LIVERATE --------------------------------------------------

    public function woo_machship_quoteRequest($items, $isResidential) {

        $suburb = isset($_REQUEST['city']) ? sanitize_text_field($_REQUEST['city']) : null;
        $postcode = isset($_REQUEST['postcode']) ? sanitize_text_field($_REQUEST['postcode']) : null;

        $force_enable = false;
        if (!empty($this->settings['force_enable_products']) && $this->settings['force_enable_products'] == 'yes') {
            $force_enable = true;
        }

        // prepare payload
        $payload = [
            'items' => [],
            'location' => [
                'suburb'   => $suburb ?? WC()->session->get('woo-machship-tosuburb') ?? WC()->session->get('woo-machship-phone-order-tosuburb') ?? WC()->customer->get_shipping_city(),
                'state'    => WC()->session->get('woo-machship-tostate'),
                'postcode' => $postcode ?? WC()->session->get('woo-machship-topostcode') ?? WC()->session->get('woo-machship-phone-order-topostcode') ?? WC()->customer->get_shipping_postcode(),
            ]
        ];


        // validate payload and make sure the location data is complete
        if (
            empty($payload['location']['suburb']) ||
            empty($payload['location']['postcode'])
        ) {

            error_log("[quoteRequest] stops quote request. Payload location is incomplete : " . json_encode($payload['location']));

            return [];
        }

        // overwrite the location is shipping address is set
        $payload['location'] = Woo_Machship_Custom::woo_machship_setAddress($payload['location']);

        $items = json_decode(json_encode($items), true);

        // we can cleanup items here that we send to fusedship
        $payload['items'] = array_map(function($item) use ($force_enable) {
            // get show plugin value from meta aswell
            $metas = $item['meta_data'] ?? $item['product_data']['meta_data'];
            $showMeta = $this->getMeta($metas, '_show_plugin');
            $isPackIndividually = $this->getMeta($metas, '_pack_individually');

            if($force_enable) {
                $showMeta = 1;
            } else {
                $showMeta = $showMeta[0]['value'] ?? null;
            }

            // check if the product has product meta selected in admin settings
            $hasProductMeta = $this->settings['fusedship_product_attributes'] ?? [];
            $productsValues = [];
            if ($hasProductMeta) {
                // loop through the product/product variations meta data
                // then check if the key matches up in the selected product meta settings
                // then store the value in the productsValues array
                foreach ($metas as $meta) {
                    if (in_array($meta['key'], $hasProductMeta)) {
                        $productsValues[$meta['key']] = $meta['value'];
                    }
                }
            }
            return [
                'id'       => $item['id'] ?? '',
                'name'     => $item['name'] ?? $item['product_data']['name'] ?? '',
                'price'    => $item['price'] ?? $item['product_data']['price'] ?? '',
                'sku'      => $item['sku'] ?? $item['product_data']['sku'] ?? '',
                'weight'   => $item['weight'] ?? $item['product_data']['weight'] ?? '',
                'length'   => $item['length'] ?? $item['product_data']['length'] ?? '',
                'width'    => $item['width'] ?? $item['product_data']['width'] ?? '',
                'height'   => $item['height'] ?? $item['product_data']['height'] ?? '',
                'quantity' => $item['quantity'] ?? $item['product_data']['quantity'] ?? '',
                'loc_ids'  => $item['loc_ids'] ?? [],
                'boxes'    => $item['boxes'] ?? [],
                'international_data' => $item['international_data'] ?? [],
                'shipping_class_id' => $item['shipping_class_id'] ?? $item['product_data']['shipping_class_id'] ?? null,
                'show_plugin' => $showMeta,
                'pack_individual' => $isPackIndividually[0]['value'] ?? 0,
                'product_attributes' => $productsValues
            ];
        }, $items);

        // set the is_residential flag ONLY if the setting is set
        if (
            isset($this->settings['has_residential']) &&
            $this->settings['has_residential'] === 'yes'
        ) {

            $payload['is_residential'] = filter_var($isResidential ?? false, FILTER_VALIDATE_BOOLEAN) ? "1" : "0";
        }


        return $this->fusedship->woo_machship_quote($payload);
    }

    public function woo_machship_renderQuotes($quotes) {

        // add woo machshipping price
        $marginType = $this->settings['margin_fixed_percent'] ?? null;
        $marginAmount = $this->settings['simple_margin_amount'] ?? null;

        $output = '';
        foreach ($quotes as $quote) {

            $price = $quote['margin_price'];

            if ($marginType && $marginAmount) {
                $price = Woo_Machship_Custom::woo_machship_calculate_margin_amount($price, $marginAmount, $marginType);
            }

            $output .= "<div class='shipping-type-result woo-machship-sqf-fields'>";

            if (!empty($quote['error'])) {
                $output .= "<p>{$quote['message']}</p>";
            } else {

                $output .= "<strong for='methodcode_{$quote['method_code']}'>";
                $output .= "<span class='lbl_inner' style='padding-left:0;'>";
                $output .= $quote['method_title'] . ': $' . $price;
                $output .= "</span>";
                $output .= "</strong>";
                $output .= "<p>" . $quote['method_description'] . "</p>";
            }

            $output .= "</div>";
        }

        return $output;
    }

    public function woo_machship_mapWCShippingMethods($quotes) {

        $shipMethods = [];

        // add woo machshipping price
        $marginType = $this->settings['margin_fixed_percent'] ?? null;
        $marginAmount = $this->settings['simple_margin_amount'] ?? null;

        foreach ($quotes as $quote) {
            $price = $quote['margin_price'];

            if ($marginType && $marginAmount) {
                $price = Woo_Machship_Custom::woo_machship_calculate_margin_amount($price, $marginAmount, $marginType);
            }

            $label = $quote['method_title'];
            if (!empty($quote['method_description'])) {
                $label = $quote['method_title'] . " - " .  $quote['method_description'];
            }

            $shipMethods[] = [
                'id' => 'woo_machship_shipping:' .$quote['method_code'],
                'cost' => $price,
                'label' => $label
            ];
        }

        return $shipMethods;
    }

    private function getMeta($metas, $metaName) {
        $hasValue = array_values(
            array_filter($metas, function($meta) use($metaName) {
                return $meta['key'] === $metaName;
            })
        );
        if (empty($hasValue)) {
            return false;
        }
        return $hasValue;
    }



    // ------------- WEBHOOK --------------------------------------------------

    public function woo_machship_webhook($payload) {

        return $this->fusedship->woo_machship_webhook($payload);
    }

}
