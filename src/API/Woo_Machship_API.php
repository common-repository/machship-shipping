<?php

namespace Woo_Machship_Shipping\API;

use Woo_Machship_Shipping\Common\Woo_Machship_Custom;

if ( !defined( 'ABSPATH' ) ) exit;

class Woo_Machship_API {
    private $token;
    private $url;

    private $isLegacy = false;

    const URL_LIVE = 'https://live.machship.com/apiv2';

    /**
     * Machship constructor.
     * @param $token
     */
    public function __construct($token, $is_demo = true)
    {
        $mode = Woo_Machship_Custom::woo_machship_getAPIMode();
        $this->url = self::URL_LIVE;

        if (defined('WOO_MACHSHIP_MODE')) {
            $mode = WOO_MACHSHIP_MODE;
        }

        if ($mode == 'demo' && defined('WOO_MACHSHIP_URL_DEMO')) {
            $this->url =  WOO_MACHSHIP_URL_DEMO;
        }


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

        $url = $this->isLegacy ? str_replace('apiv2', 'api', $this->url) : $this->url;
        $url .= $endpoint;


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
            error_log("[Machship][request] response error " . $response->get_error_message());
        }

        // TODO might handle code better in the future


        // reset legacy mode
        $this->isLegacy = false;

        // return just the body
        return is_object($res) ? $res : json_decode($res);

    }

    /**
     * COMPANY SERVICES
     */

    /**
     * @param null $id
     * @return mixed
     */
    public function woo_machship_getAllCompanies($id = null)
    {
        $url = isset($id)
            ? "/companies/getAll?atOrBelowCompanyId={$id}"
            : "/companies/getAll";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * CARRIER SERVICES
     */


    /**
     * Gets carrier accounts and services in one endpoint and doesnt require admin level machship key
     * @return mixed
     */
    public function woo_machship_getCarrierAccountAndServices() {
        $path = '/companies/getAvailableCarriersAccountsAndServices';
        return $this->woo_machship_request('GET', $path);
    }

    /**
     * @deprecated Will use the new endpoint "getCarrierAccountAndServices()" for getting carrier account and services
     * @return mixed
     */
    public function woo_machship_getAllCarriers()
    {
        $path = "/carriers/GetAllCarriers?retrieveSize=400&startIndex=1";
        $this->isLegacy = true;

        return $this->woo_machship_request('GET', $path);
    }

    /**
     * @deprecated Will use the new endpoint "getCarrierAccountAndServices()" for getting carrier account and services
     * @param $id
     * @return mixed
     */
    public function woo_machship_getCarrierServices($id)
    {
        $path = "/carrierservices/SearchAllServicesByCarrierId?id={$id}&retrieveSize=4000&startIndex=1";
        $this->isLegacy = true;

        return $this->woo_machship_request('GET', $path);
    }

    /**
     * @deprecated Will use the new endpoint "getCarrierAccountAndServices()" for getting carrier account and services
     * @param $id
     * @return mixed
     */
    public function woo_machship_getCarrierAccounts($id)
    {
        $path = "/carrierAccounts/GetAllCarrierAccounts?carrierId={$id}&retrieveSize=400&startIndex=1";
        $this->isLegacy = true;

        return $this->woo_machship_request('GET', $path);
    }

    /**
     * Gets all carrier zones by carrier identifier.
     * @param      int  $carrier_id  The carrier identifier
     * @param      string  $search      The search
     *
     * @return     Object  All carrier zones by carrier identifier.
     */
    public function woo_machship_getAllCarrierZonesByCarrierId($carrier_id, $search = "") {
        $path = "/carrierZones/GetAllCarrierZonesByCarrierId?id=$carrier_id&retrieveSize=400&startIndex=1&searchText=$search";
        $this->isLegacy = true;

        return $this->woo_machship_request('GET', $path);
    }

    /**
     * COMPANY ITEM SERVICES
     */

    /**
     * @param null $company_id
     * @param int $start_index
     * @param int $retrieve_size
     * @return mixed
     */
    public function woo_machship_getAllCompanyItem($company_id = null, $start_index = 1, $retrieve_size = 200)
    {
        $url = isset($company_id)
            ? "/items/getAll?companyId={$company_id}&startIndex={$start_index}&retrieveSize={$retrieve_size}"
            : "/items/getAll?startIndex={$start_index}&retrieveSize={$retrieve_size}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function woo_machship_getCompanyItem($id)
    {
        $url = "/items/get?id=$id";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $company_id
     * @param $sku
     * @return mixed
     */
    public function woo_machship_getCompanyItemBySku($company_id, $sku)
    {
        $url = "/items/getBySku?companyId={$company_id}&sku={$sku}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function woo_machship_getCompanyItemComplex($id)
    {
        $url = "/items/getComplex?id={$id}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param null $company_id
     * @param int $start_index
     * @param int $retrieve_size
     * @return mixed
     */
    public function woo_machship_getCompanyItemAllComplex($company_id = null, $start_index = 1, $retrieve_size = 200)
    {
        $url = isset($company_id)
            ? "/items/getAllComplex?companyId={$company_id}&startIndex={$start_index}&retrieveSize={$retrieve_size}"
            : "/items/getAllComplex?startIndex={$start_index}&retrieveSize={$retrieve_size}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $sku
     * @param null $company_id
     * @return mixed
     */
    public function woo_machship_getCompanyItemBySkuComplex($sku, $company_id = null)
    {
        $url = isset($company_id)
            ? "/items/getComplex?sku={$sku}&companyId={$company_id}"
            : "/items/getComplex?sku={$sku}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * Gets the company item valid item types.
     * @return mixed
     */
    public function woo_machship_getCompanyItemValidItemTypes() {
        $path = '/companyItems/GetCompanyValidItemTypes';
        $this->isLegacy = true;

        return $this->woo_machship_request('GET', $path);
    }

    /**
     * COMPANY LOCATION SERVICES
     */

    /**
     * @param $id
     * @return $this
     */
    public function woo_machship_getCompanyLocation($id)
    {
        $url = "/companyLocations/get?id={$id}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param null $company_id
     * @return mixed
     */
    public function woo_machship_getAllCompanyLocations($company_id = null)
    {
        $url = isset($company_id)
            ? "/companyLocations/getAll?companyId={$company_id}"
            : "/companyLocations/getAll";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * CONSIGNMENT SERVICES
     */

    /**
     * @param $id
     * @return mixed
     */
    public function woo_machship_getConsignment($id)
    {
        $url = "/consignments/getConsignment?id={$id}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function woo_machship_getUnmanifestedConsignmentForEdit($id)
    {
        $url = "/consignments/getUnmanifestedConsignmentForEdit?id={$id}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function woo_machship_getConsignmentByPendingConsignmentId($id)
    {
        $url = "/consignments/getConsignmentByPendingConsignmentId?id={$id}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $consignment_ids
     * @return mixed
     */
    public function woo_machship_returnConsignments($consignment_ids)
    {
        $url = "/consignments/returnConsignments";

        return $this->woo_machship_request('POST', $url, $consignment_ids);
    }

    /**
     * @param $carrier_consignment_ids
     * @return mixed
     */
    public function woo_machship_returnConsignmentsByCarrierConsignmentId($carrier_consignment_ids)
    {
        $url = "/consignments/returnConsignmentsByCarrierConsignmentId";

        return $this->woo_machship_request('POST', $url, $carrier_consignment_ids);
    }

    /**
     * @param $references
     * @return mixed
     */
    public function woo_machship_returnConsignmentsByReference1($references)
    {
        $url = "/consignments/returnConsignmentsByReference1";

        return $this->woo_machship_request('POST', $url, $references);
    }

    /**
     * @param $references
     * @return mixed
     */
    public function woo_machship_returnConsignmentsByReference2($references)
    {
        $url = "/consignments/returnConsignmentsByReference2";

        return $this->woo_machship_request('POST', $url, $references);
    }

    /**
     * @param $consignment_ids
     * @param null $since_date_created_utc
     * @return mixed
     */
    public function woo_machship_returnConsignmentStatuses($consignment_ids, $since_date_created_utc = null)
    {
        $url = isset($since_date_created_utc)
            ? "/consignments/returnConsignmentStatuses?sinceDateCreatedUtc={$since_date_created_utc}"
            : "/consignments/returnConsignmentStatuses";

        return $this->woo_machship_request('POST', $url, $consignment_ids);
    }

    /**
     * @param null $company_id
     * @param int $start_index
     * @param int $retrieve_size
     * @param null $carrier_id
     * @param bool $include_child_companies
     * @return mixed
     */
    public function woo_machship_getUnmanifestedConsignments(
        $company_id = null,
        $start_index = 1,
        $retrieve_size = 200,
        $carrier_id = null,
        $include_child_companies = false
    ) {
        $url = isset($company_id)
            ? "/consignments/getUnmanifested?companyId={$company_id}&retrieveSize={$retrieve_size}&includeChildCompanies={$include_child_companies}"
            : "/consignments/getUnmanifested?retrieveSize={$retrieve_size}&includeChildCompanies={$include_child_companies}";

        $url .= isset($carrier_id) ? "&carrierId={$carrier_id}" : "";
        $url .= "&startIndex={$start_index}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param null $company_id
     * @param int $start_index
     * @param int $retrieve_size
     * @param null $carrier_id
     * @param bool $include_child_companies
     * @return mixed
     */
    public function woo_machship_getActiveConsignments(
        $company_id = null,
        $start_index = 1,
        $retrieve_size = 200,
        $carrier_id = null,
        $include_child_companies = false
    ) {
        $url = isset($company_id)
            ? "/consignments/getActive?companyId={$company_id}&retrieveSize={$retrieve_size}&includeChildCompanies={$include_child_companies}"
            : "/consignments/getActive?retrieveSize={$retrieve_size}&includeChildCompanies={$include_child_companies}";

        $url .= isset($carrier_id) ? "&carrierId={$carrier_id}" : "";
        $url .= "&startIndex={$start_index}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param null $company_id
     * @param int $start_index
     * @param int $retrieve_size
     * @param null $carrier_id
     * @param bool $include_child_companies
     * @param bool $include_deleted_consignments
     * @return mixed
     */
    public function woo_machship_getAllConsignments(
        $company_id = null,
        $start_index = 1,
        $retrieve_size = 200,
        $carrier_id = null,
        $include_child_companies = false,
        $include_deleted_consignments = false
    ) {
        $url = isset($company_id)
            ? "/consignments/getAll?companyId={$company_id}&retrieveSize={$retrieve_size}&includeChildCompanies={$include_child_companies}&includeDeletedConsignments={$include_deleted_consignments}"
            : "/consignments/getAll?retrieveSize={$retrieve_size}&includeChildCompanies={$include_child_companies}&includeDeletedConsignments={$include_deleted_consignments}";

        $url .= isset($carrier_id) ? "&carrierId={$carrier_id}" : "";
        $url .= "&startIndex={$start_index}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $start_date
     * @param $end_date
     * @param null $company_id
     * @param bool $include_child_companies
     * @return mixed
     */
    public function woo_machship_getCompletedConsignments(
        $start_date,
        $end_date,
        $company_id = null,
        $include_child_companies = false
    ) {
        $url = isset($company_id)
            ? "/consignments/getCompleted?companyId={$company_id}&startDate={$start_date}&endDate={$end_date}&includeChildCompanies={$include_child_companies}"
            : "/consignments/getCompleted?startDate={$start_date}&endDate={$end_date}&includeChildCompanies={$include_child_companies}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $consignment
     * @return mixed
     */
    public function woo_machship_createConsignment($consignment)
    {
        $url = "/consignments/createConsignment";

        return $this->woo_machship_request('POST', $url, $consignment);
    }

    /**
     * @param $consignment
     * @return mixed
     */
    public function woo_machship_editUnmanifestedConsignment($consignment)
    {
        $url = "/consignments/editUnmanifestedConsignment";

        return $this->woo_machship_request('POST', $url, $consignment);
    }

    /**
     * @param $consignment
     * @return mixed
     */
    public function woo_machship_createConsignmentWithComplexItems($consignment)
    {
        $url = "/consignments/createConsignmentwithComplexItems";

        return $this->woo_machship_request('POST', $url, $consignment);
    }

    /**
     * @param $ids
     * @return mixed
     */
    public function woo_machship_deleteUnmanifestedConsignments($ids)
    {
        $url = "/consignments/deleteUnmanifestedConsignments";

        return $this->woo_machship_request('POST', $url, $ids);
    }

    /**
     * @param $consignment_id
     * @return mixed
     */
    public function woo_machship_getConsignmentAttachments($consignment_id)
    {
        $url = "/consignments/getAttachments";

        return $this->woo_machship_request('POST', $url, $consignment_id);
    }

    /**
     * @param null $id
     * @return mixed
     */
    public function woo_machship_getConsignmentForClone($id = null)
    {
        $url = isset($id)
            ? "/consignments/getConsignmentForClone?id={$id}"
            : "/consignments/getConsignmentForClone";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * LOCATION SERVICES
     */

    /**
     * @return $this
     */
    public function woo_machship_getAllLocations()
    {
        $url = "/locations/getAllLocations";
        return $this->woo_machship_request('GET', $url);
    }

    public function woo_machship_getLocations($params = [])
    {
        $s = implode(' ', $params);
        $url = "/locations/getLocations?s={$s}";
        return $this->woo_machship_request('GET', $url);
    }

    public function woo_machship_getLocationsWithSearchOptions($search = null)
    {
        $url = "/locations/returnLocationsWithSearchOptions?retrievalSize=50";

        $payload = ['retrievalSize' => 50];

        if(isset($search)) {
            $url = "/locations/returnLocationsWithSearchOptions?retrievalSize=50&s={$search}";
            $payload['s'] = $search;
        }

        return $this->woo_machship_request('POST', $url, $payload);
    }

    public function woo_machship_getReturnLocations($params){
        $url = "/locations/returnLocations";
        return $this->woo_machship_request('POST', $url, $params);
    }

    /**
     * PENDING CONSIGNMENT SERVICES
     */

    /**
     * @param $id
     * @return mixed
     */
    public function woo_machship_getPendingConsignment($id)
    {
        $url = "/pendingConsignments/getPendingConsignment?id={$id}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $pending_consignments_id
     * @return mixed
     */
    public function woo_machship_returnPendingConsignments($pending_consignments_id)
    {
        $url = "/pendingConsignments/returnPendingConsignments";

        return $this->woo_machship_request('POST', $url, $pending_consignments_id);
    }

    /**
     * @param $references
     * @return mixed
     */
    public function woo_machship_returnPendingConsignmentsByReference1($references)
    {
        $url = "/pendingConsignments/returnPendingConsignmentsByReference1";

        return $this->woo_machship_request('POST', $url, $references);
    }

    /**
     * @param $references
     * @return mixed
     */
    public function woo_machship_returnPendingConsignmentsByReference2($references)
    {
        $url = "/pendingConsignments/returnPendingConsignmentsByReference2";

        return $this->woo_machship_request('POST', $url, $references);
    }

    /**
     * @param $pending_consignment
     * @return mixed
     */
    public function woo_machship_createPendingConsignment($pending_consignment)
    {
        $url = "/pendingConsignments/createPendingConsignment";

        return $this->woo_machship_request('POST', $url, $pending_consignment);
    }

    /**
     * @param $ids
     * @return mixed
     */
    public function woo_machship_deletePendingConsignment($ids)
    {
        $url = "/pendingConsignments/deletePendingConsignments";

        return $this->woo_machship_request('POST', $url, $ids);
    }

    /**
     * QUOTE SERVICES
     */

    /**
     * @param $quote
     * @return mixed
     */
    public function woo_machship_createQuote($quote)
    {
        $url = "/quotes/createQuote";

        return $this->woo_machship_request('POST', $url, $quote);
    }

    /**
     * @param $quote
     * @return mixed
     */
    public function woo_machship_createQuoteWithComplexItems($quote)
    {
        $url = "/quotes/createQuoteWithComplexItems";

        return $this->woo_machship_request('POST', $url, $quote);
    }

    /**
     * @param null $company_id
     * @return mixed
     */
    public function woo_machship_getAllQuotes($company_id = null)
    {
        $url = isset($company_id)
            ? "/quotes/getAll?companyId={$company_id}"
            : "/quotes/getAll";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function woo_machship_getQuote($id)
    {
        $url = "/quotes/getQuote?id={$id}";

        return $this->woo_machship_request('GET', $url);
    }

    /**
     * @param $quoteIds
     * @return mixed
     */
    public function woo_machship_returnQuotes($quoteIds)
    {
        $url = "/quotes/returnQuotes";

        return $this->woo_machship_request('POST', $url, $quoteIds);
    }

    // ------------------- CUSTOM FUNCTIONS ---------------------------------------------

    /**
     * Gets the suburbs by postcode.
     * This function will fetch all company location and search a suburb from a postcode
     * @param      integer  $postcode  The postcode
     *
     * @return     array  The suburb by postcode.
     */
    public function woo_machship_getSuburbByPostcode(int $postcode) {
        $results = $this->woo_machship_getAllCompanyLocations();
        // error_log("[getSuburbByPostcode] results : " . json_encode($results));

        if (empty($results->object)) {
            return;
        }

        $suburbs = [];
        foreach ($results->object as $object) {
            if ($object->location && $object->location->postcode == $postcode) {
                $suburbs[] = $object->location->suburb;
            }
        }

        return $suburbs;
    }

  /**
   * @param $data
   * @return mixed
   */
  public function woo_machship_returnRoutes($data) {
      $url = "/routes/returnroutes";

      return $this->woo_machship_request('POST', $url, $data);
  }

  /**
   * @param $data
   * @return mixed
   */
  public function woo_machship_multiRequestRoutes($data) {

    // error_log("[multiRequestRoutes] routes " . json_encode($data));

    $result = [];
    foreach ($data as $i => $payload) {
        $result[$i] = $this->woo_machship_returnRoutes($payload);
    }

    // error_log("[multiRequestRoutes] routes multiple response " . json_encode($result));

    // Note :
    // Wordpress doesn't have multi request http ready
    // A Library is also available Requests::request_multiple but we have to require them to install the library to work

    return $result;

  }
}
