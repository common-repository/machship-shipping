<?php

namespace Woo_Machship_Shipping\API;

use Woo_Machship_Shipping\Common\Woo_Machship_Custom;

class Woo_Machship_Fusedship_API
{
    private $url;
    private $integrationId;
    private $token;

    const PROD_URL = 'https://sync.fusedship.com';

    public function __construct($token, $integrationId)
    {
        $mode = Woo_Machship_Custom::woo_machship_getAPIMode();
        $this->url = self::PROD_URL;

        if (defined('WOO_MACHSHIP_MODE')) {
            $mode = WOO_MACHSHIP_MODE;
        }

        if ($mode === 'demo' && defined('WOO_MACHSHIP_FUSEDSHIP_URL_DEMO')) {
            $this->url = WOO_MACHSHIP_FUSEDSHIP_URL_DEMO;
        }

        $this->integrationId = $integrationId;

        $this->token = $token;
    }

    private function woo_machship_request($method, $endpoint, $data = []) {


        // http options
        $options = [
            'headers' => [
                'Token' => $this->token,
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache'
            ],
            'timeout' => 45,
        ];


        $url = $this->url . $endpoint;


        // error_log("[Fusedship][request] full url " . $url);

        $response = "";

        switch ($method) {
            case 'GET':
                $response = wp_remote_get($url, $options);
                break;

            case 'POST':
                $options['body'] = json_encode($data);
                $response = wp_remote_post($url, $options);
                break;
        }

        $code = wp_remote_retrieve_response_code($response);
        $res = wp_remote_retrieve_body($response);

        // Check for errors
        if (is_wp_error($response)) {
            error_log("[Fusedship][request] response error " . $response->get_error_message());
        }



        // TODO might handle code better in the future


        // return just the body
        return is_object($res) ? $res : json_decode($res);

    }

    public function woo_machship_saveLog($payload)
    {
        $url = '/live-rates/logs/mshipping_wp/' . $this->integrationId;

        return $this->woo_machship_request('POST', $url, $payload);
    }

    public function woo_machship_quote($payload)
    {
        // error_log("[Fusedship][quote] init");

        if (empty($this->token)) {
            throw new \Exception("Invalid request quote, no token set");
        }

        $url = '/woocommerce/' . $this->token . '/rate';


        return $this->woo_machship_request('POST', $url, $payload);

    }

    public function woo_machship_webhook($payload)
    {
        if (empty($this->integrationId)) {
            throw new \Exception("Invalid request webhook, no integration id set");
        }

        $url = '/webhooks/' . $this->integrationId . '?token=' . $this->token;

        return $this->woo_machship_request('POST', $url, $payload);
    }

    public function woo_machship_createOrUpdateSettings($payload)
    {
        $url = "/woocommerce/create-update-default-settings";

        return $this->woo_machship_request('POST', $url, $payload);
    }
}
