<?php
/**
 * bol.com API
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;


class API{


   /**
    * List of accounts
    *
    * @since 1.1.1
    * @var array
    */
   public $accounts;


   /**
    * The currnet account
    *
    * @since 1.1.1
    * @var array
    */
   public $account;


   /**
    * The key to identify the account
    *
    * @since 1.1.1
    * @var string
    */
   public $account_id;



   /**
    *
    * @since 1.1.1
    * @param integer $account_id
    */
   public function __construct($account_id){

      $this->account_id = $account_id;
      $this->accounts    = Accounts::get_accounts();
      $this->account     = Utility::rgar( $this->accounts, $account_id);
   }



   /**
    * Guzzle API client
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.5
    * @param array $headers
    * @return object
    */
   public function client($options = []){

      if(isset($options['headers'])){
         $options['headers'] = $this->headers( $options['headers'] );
      }else{
         $options['headers'] = $this->headers();
      }

      $client = new Client( $options );

      return $client;
   }



   /**
    * Checks whether or not the account is authorized
    *
    * @since 1.1.1
    * @return boolean
    */
   public function is_authorized(){

      if( '' === Utility::rgars($this->account, 'authorized/access_token') ) return false;

      return true;
   }



   /**
    * Shows account status
    *
    * @since 1.1.1
    * @return string
    */
   public function get_status(){

      if( $this->is_authorized() ){
         return __('Authorized', 'woosa-bol-com-for-woocommerce');
      }

      return __('Unauthorized', 'woosa-bol-com-for-woocommerce');
   }



   /**
    * Renders the account status.
    *
    * @since 1.1.1
    * @return string
    */
   public function render_status(){

      $color = $this->is_authorized() ? 'green' : '#cc0000';
      $status = '<span style="color: '.$color.';">'.$this->get_status().'</span>';
      $action = $this->is_authorized() ? ' ('. sprintf(__( '%sClick to revoke%s', 'woosa-bol-com-for-woocommerce' ), '<a href="'.esc_url($this->action_url('revoke')).'">', '</a>') . ')' : ' ('. sprintf(__( '%sClick to authorize%s', 'woosa-bol-com-for-woocommerce' ), '<a href="'.esc_url($this->action_url()).'">', '</a>') . ')';

      if(empty($this->account['client_api_id']) || empty($this->account['client_api_secret'])){
         $action = '';
      }

      $html = $status.$action;

      echo $html;
   }



   /**
    * Builds action URL
    *
    * @since 1.1.1
    * @return void
    */
   public function action_url($action = 'authorize'){

      return add_query_arg(array(
         'account_id' => $this->account_id,
         'account_action' => $action,
         '_wpnonce' => wp_create_nonce(PREFIX.'_account_action'),
      ), PLUGIN_SETTINGS_URL);
   }



   /**
    * Revokes authorization of an account
    *
    * @since 1.1.1
    * @return true
    */
   public function revoke(){

      unset($this->account['authorized']);

      $this->update_settings();

      return true;
   }



   /**
    * Updates plugin settings
    *
    * @since 1.1.1
    * @return void
    */
   public function update_settings(){

      //update account in the list
      $this->accounts[$this->account_id] = $this->account;

      //update accounts list
      update_option(PREFIX.'_accounts', $this->accounts);
   }



   /**
    * Authorizes application
    *
    * This gives us the required token in order to be able to make requests to bol.com.
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.5 - changed to Guzzle API client
    *              - added a limit of attempts
    * @since 1.0.0
    * @return bool
    */
   public function authorize(){

      $attempts = Utility::rgars($this->account, 'authorized/attempts', 0);

      if($attempts == 5){

         //remove authorize data, this will force admin to authorize manually
         unset($this->account['authorized']);

         $this->update_settings();

         return false;
      }


      try{

         $options = [
            'headers' => array(
               'Authorization' => 'Basic ' . base64_encode( $this->account['client_api_id'] . ':' . $this->account['client_api_secret'] ),
               'Accept' => 'application/json'
            )
         ];

         $request = $this->client( $options )->request('POST', 'https://login.bol.com/token?grant_type=client_credentials', []);

         if($request->getStatusCode() == 200){

            $body = json_decode( $request->getBody()->getContents() );

            //save authorization token
            $this->account['authorized'] = Utility::obj_to_arr($body);

            //reset attemps
            $this->account['authorized']['attempts'] = 0;

            $this->update_settings();

            return true;
         }

      }catch(ClientException $e){

         Utility::wc_error_log(\json_decode($e->getResponse()->getBody()->getContents()), __FILE__, __LINE__);
         Utility::wc_error_log($e->getRequest(), __FILE__, __LINE__);
         Utility::wc_error_log($e->getResponse(), __FILE__, __LINE__);

         $attempts++;

         $this->account['authorized']['attempts'] = $attempts;

         $this->update_settings();
      }

      return false;
   }



   /**
    * Sends the request.
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.5 - changed to Guzzle API client
    * @since 1.0.0
    * @param string $url
    * @param array $payload - what to send in the request (body)
    * @param string $method
    * @param array $options - options for API client (headers, timeout, etc)
    * @return object
    */
   public function request($url, $payload = array(), $method = 'GET', $options = []){

      //no account then stop here
      if( ! isset($this->accounts[$this->account_id]) ) return;

      if( ! $this->is_authorized() ){
         return sprintf('This account (%s) is not authorized.', $this->account_id);
      }

      try{

         $request = $this->client( $options )->request($method, $url, $payload);
         $body = $request->getBody()->getContents();

      }catch(ClientException $e){

         $code = $e->getResponse()->getStatusCode();

         if($code == 401){

            $authorized = $this->authorize();

            //send again the request
            if($authorized){
               return $this->request($url, $payload, $method);
            }

         }else{

            $body = $e->getResponse()->getBody()->getContents();

            Utility::wc_error_log([
               '_ACCOUNT_ID' => $this->account_id,
               '_METHOD' => $method,
               '_ENDPOINT' => $url,
               '_REQUEST_PAYLOAD' => isset($payload['body']) ? json_decode($payload['body']) : $payload,
               '_REQUEST_RESPONSE' => json_decode($body)
            ], __FILE__, __LINE__);
         }
      }

      if(\WP_DEBUG){
         Utility::wc_debug_log([
            '_ACCOUNT_ID' => $this->account_id,
            '_METHOD' => $method,
            '_ENDPOINT' => $url,
            '_REQUEST_PAYLOAD' => isset($payload['body']) ? json_decode($payload['body']) : $payload,
            '_REQUEST_RESPONSE' => json_decode($body)
         ], __FILE__, __LINE__);
      }


      if(self::is_json($body)){
         return json_decode($body);
      }

      return $body;
   }



   /**
    * Creates an offer.
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.5 - changed to Guzzle API client
    * @since 1.0.0
    * @param array $payload
    * @return object
    */
   public function create_offer($payload){

      $ean_code                  = Utility::rgar($payload, 'ean_code');
      $condition_name            = Utility::rgar($payload, 'condition_name');
      $reference_code            = Utility::rgar($payload, 'reference_code');
      $price                     = Utility::rgar($payload, 'price');
      $stock_amount              = (int) Utility::rgar($payload, 'stock_amount');
      $stock_managed_by_retailer = Utility::rgar($payload, 'stock_managed_by_retailer');
      $fulfilment_type           = Utility::rgar($payload, 'fulfilment_type');
      $fulfilment_delivery_code  = Utility::rgar($payload, 'fulfilment_delivery_code');
      $fulfilment = array(
         "type" => $fulfilment_type
      );


      if($fulfilment_type == 'FBR'){
         $fulfilment['deliveryCode'] = $fulfilment_delivery_code;
      }

      $send_payload = [
         "ean" => $ean_code,
         "condition" => array(
            "name" => $condition_name,
         ),
         "referenceCode" => $reference_code,
         "onHoldByRetailer" => false,
         "unknownProductTitle" => get_the_title($post_id),
         "pricing" => array(
            "bundlePrices" => array(
               array(
                  "quantity" => 1,
                  "price" => $price
               )
            )
         ),
         "stock" => array(
            "amount" => $stock_amount,
            "managedByRetailer" => $stock_managed_by_retailer == 'true' ? true : false
         ),
         "fulfilment" => $fulfilment
      ];

      return $this->request( self::endpoint('offers'), ['body' => json_encode($send_payload)], 'POST' );

   }



   /**
    * Retreives an offer.
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.5 - changed to Guzzle API client
    * @since 1.0.0
    * @param int $id
    * @return object
    */
   public function get_offer($id){

      return $this->request( self::endpoint("offers/{$id}"), [], 'GET' );
   }



   /**
    * Updates an offer stock.
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.5 - changed to Guzzle API client
    * @since 1.0.0
    * @param int $id
    * @param array $payload
    * @return object
    */
   public function update_offer_stock($id, $payload){

      return $this->request( self::endpoint("offers/{$id}/stock"), ['body' => json_encode($payload)], 'PUT' );
   }



   /**
    * Deletes an offer.
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.5 - changed to Guzzle API client
    * @since 1.0.0
    * @param int $id
    * @return object
    */
   public function delete_offer($id){

      return $this->request( self::endpoint("offers/{$id}"), array(), 'DELETE' );
   }



   /**
    * Gets status of a process by a given id.
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.5 - changed to Guzzle API client
    * @since 1.0.0
    * @param int $id
    * @return object
    */
   public function get_process_status($id){

      return $this->request( self::endpoint("process-status/{$id}"), [], 'GET' );
   }



   /**
    * Gets list of commissions
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.1.0
    * @param string $ean
    * @param array $payload
    * @return object
    */
   public function get_commissions($ean, $payload = []){

      $ean = trim($ean);
      $params = array_filter([
         'condition' => Utility::rgar($payload, 'condition_name'),
         'price' => Utility::rgar($payload, 'price'),
      ]);

      if(empty($ean)) return [];

      return $this->request( self::endpoint("commission/{$ean}"), ['query' => $params], 'GET' );
   }



   /**
    * Base API URL
    *
    * @since 1.0.7 - added parameter for demo URL
    * @since 1.0.6 - changed base URL to midlayer
    * @since 1.0.2 - added static demo env
    * @since 1.0.0
    * @param string $endpoint
    * @param bool $demo
    * @return void
    */
   public static function endpoint($endpoint, $demo = false){

      if($demo){
         return 'https://api.bol.com/retailer-demo/'.ltrim($endpoint, '/');
      }

      if( defined('\WOOSA_TEST') && \WOOSA_TEST ) return 'https://midlayer-dev.woosa.nl/bol/retailer/'.ltrim($endpoint, '/');

      if( defined('\WOOSA_STA') && \WOOSA_STA ) return 'https://midlayer-sta.woosa.nl/bol/retailer/'.ltrim($endpoint, '/');

      return 'https://midlayer.woosa.nl/bol/retailer/'.ltrim($endpoint, '/');
   }



   /**
    * Checks whether or not we use demo API environment
    *
    * @since 1.0.6
    * @return boolean
    */
   public static function is_demo_env(){

      if(defined('\WOOSA_TEST') && \WOOSA_TEST || defined('\WOOSA_STA') && \WOOSA_STA) return true;

      return false;
   }



   /**
    * Required API request headers
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.7 - added `X-Woosa-Email` item
    * @since 1.0.6 - added midlayer headers
    * @since 1.0.0
    * @param array $items
    * @return void
    */
   public function headers($items = array()){

      $default = array(
         'Authorization'   => 'Bearer '. Utility::rgars($this->account, 'authorized/access_token', null),
         'Accept'          => 'application/vnd.retailer.v3+json',
         'Content-Type'    => 'application/vnd.retailer.v3+json',
         'User-Agent'      => 'Woosa/'.PLUGIN_VERSION,
         'X-Woosa-Host'    => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null,
         'X-Woosa-Licence' => null,
         'X-Woosa-Email'   => null,
         'X-Woosa-Version' => 'light',
      );

      if(is_array($items)){
         return array_merge($default, $items);
      }

      return $default;
   }



   /**
    * Checks whether the given string is JSON format
    *
    * @since 1.1.1
    * @param string $string
    * @return boolean
    */
   public static function is_json($string) {
      json_decode($string);
      return (json_last_error() == JSON_ERROR_NONE);
   }



   /**
    * Offer (product) conditions
    *
    * @since 1.0.0
    * @return array
    */
   public static function condition_names(){

      return apply_filters(PREFIX.'_condition_names', array(
         'NEW'        => __('New', 'woosa-bol-com-for-woocommerce'),
         'AS_NEW'     => __('As new', 'woosa-bol-com-for-woocommerce'),
         'GOOD'       => __('Good', 'woosa-bol-com-for-woocommerce'),
         'REASONABLE' => __('Reasonable', 'woosa-bol-com-for-woocommerce'),
         'MODERATE'   => __('Moderate', 'woosa-bol-com-for-woocommerce'),
      ));
   }


   /**
    * Delivery time codes
    *
    * @since 1.0.4 - removed 3-4d
    * @since 1.0.0
    * @return array
    */
   public static function delivery_codes(){

      return apply_filters(PREFIX.'_delivery_codes', array(
         "24uurs-23" => self::delivery_code_text('23'),
         "24uurs-22" => self::delivery_code_text('22'),
         "24uurs-21" => self::delivery_code_text('21'),
         "24uurs-20" => self::delivery_code_text('20'),
         "24uurs-19" => self::delivery_code_text('19'),
         "24uurs-18" => self::delivery_code_text('18'),
         "24uurs-17" => self::delivery_code_text('17'),
         "24uurs-16" => self::delivery_code_text('16'),
         "24uurs-15" => self::delivery_code_text('15'),
         "24uurs-14" => self::delivery_code_text('14'),
         "24uurs-13" => self::delivery_code_text('13'),
         "24uurs-12" => self::delivery_code_text('12'),
         "1-2d"      => self::delivery_code_text('1-2', 'days'),
         "2-3d"      => self::delivery_code_text('2-3', 'days'),
         "3-5d"      => self::delivery_code_text('3-5', 'days'),
         "4-8d"      => self::delivery_code_text('4-8', 'days'),
         "1-8d"      => self::delivery_code_text('1-8', 'days'),
      ));
   }



   /**
    * Output description text for a delivery code
    *
    * @since 1.0.2
    * @param string $hour
    * @param string $type
    * @return string
    */
   public static function delivery_code_text($hour, $type = 'hour'){

      if($type == 'hour'){
         return sprintf(__('Ordered before %s on working days, delivered the next working day.', 'woosa-bol-com-for-woocommerce'), $hour);
      }

      return sprintf(__('%s working days.', 'woosa-bol-com-for-woocommerce'), $hour);
   }



   /**
    * Cancel order reasons
    *
    * @since 1.0.0
    * @return boolean
    */
   public static function cancel_order_reasons(){

      return apply_filters(PREFIX.'_cancel_order_reasons', array(
         'OUT_OF_STOCK'          => __('Out of stock', 'woosa-bol-com-for-woocommerce'),
         'REQUESTED_BY_CUSTOMER' => __('Requested by customer', 'woosa-bol-com-for-woocommerce'),
         'BAD_CONDITION'         => __('Bad condition', 'woosa-bol-com-for-woocommerce'),
         'HIGHER_SHIPCOST'       => __('Higher shipcost', 'woosa-bol-com-for-woocommerce'),
         'INCORRECT_PRICE'       => __('Incorrect price', 'woosa-bol-com-for-woocommerce'),
         'NOT_AVAIL_IN_TIME'     => __('Not available in time', 'woosa-bol-com-for-woocommerce'),
         'NO_BOL_GUARANTEE'      => __('No BOL quarantee', 'woosa-bol-com-for-woocommerce'),
         'ORDERED_TWICE'         => __('Ordered twice', 'woosa-bol-com-for-woocommerce'),
         'RETAIN_ITEM'           => __('Retain item', 'woosa-bol-com-for-woocommerce'),
         'TECH_ISSUE'            => __('Technical issue', 'woosa-bol-com-for-woocommerce'),
         'UNFINDABLE_ITEM'       => __('Unfindable item', 'woosa-bol-com-for-woocommerce'),
         'OTHER'                 => __('Other', 'woosa-bol-com-for-woocommerce'),
      ));
   }



   /**
    * List of allowed transporters
    *
    * @since 1.0.0
    * @return boolean
    */
   public static function transporters(){

      return apply_filters(PREFIX.'_transporters', array(
         'BRIEFPOST'       => 'Briefpost',
         'UPS'             => 'UPS',
         'TNT'             => 'PostNL',
         'TNT-EXTRA'       => 'PostNL extra@home',
         'TNT_BRIEF'       => 'PostNL Briefpost (Track & Trace required)',
         'TNT-EXPRESS'     => 'TNT Express',
         'DYL'             => 'Dynalogic',
         'DPD-NL'          => 'DPD Nederland',
         'DPD-BE'          => 'DPD België',
         'BPOST_BE'        => 'Bpost België',
         'BPOST_BRIEF'     => 'Bpost Briefpost (Track & Trace required)',
         'DHLFORYOU'       => 'DHLFORYOU',
         'GLS'             => 'GLS',
         'FEDEX_NL'        => 'FedEx Nederland',
         'FEDEX_BE'        => 'FedEx Belgie',
         'OTHER'           => 'Anders',
         'DHL'             => 'DHL',
         'DHL_DE'          => 'DHL Germany',
         'DHL-GLOBAL-MAIL' => 'DHL Global mail',
         'TSN'             => 'Transportservice Nederland',
         'FIEGE'           => 'Fiege',
         'TRANSMISSION'    => 'TransMission',
         'PARCEL-NL'       => 'Parcel.nl',
         'LOGOIX'          => 'LogoiX',
         'PACKS'           => 'Packs',
         'COURIER'         => 'Bezorgafspraak',
         'RJP'             => 'NedWRK (Red je pakketje)',
      ));
   }



   /**
    * List of return reasons
    *
    * @since 1.0.0
    * @return boolean
    */
   public static function return_reasons(){

      return apply_filters(PREFIX.'_return_reasons', array(
         'RETURN_RECEIVED'                 => __('Return received', 'woosa-bol-com-for-woocommerce'),
         'EXCHANGE_PRODUCT'                => __('Exchange product', 'woosa-bol-com-for-woocommerce'),
         'RETURN_DOES_NOT_MEET_CONDITIONS' => __('Return does not meet conditions', 'woosa-bol-com-for-woocommerce'),
         'REPAIR_PRODUCT'                  => __('Repair product', 'woosa-bol-com-for-woocommerce'),
         'CUSTOMER_KEEPS_PRODUCT_PAID'     => __('Customer keeps product paid', 'woosa-bol-com-for-woocommerce'),
         'STILL_APPROVED'                  => __('Still approved', 'woosa-bol-com-for-woocommerce'),
      ));
   }



   /**
    * List of order return status
    *
    * @since 1.0.0
    * @return array
    */
   public static function order_return_statuses(){

      return array(
         'completed' => array(
            'title' => __('Return completed', 'woosa-bol-com-for-woocommerce'),
            'color' => '#46b450',
            'icon' => 'dashicons dashicons-yes',
         ),
         'no_return' => array(
            'title' => __('No return available', 'woosa-bol-com-for-woocommerce'),
            'color' => '',
            'icon' => 'dashicons dashicons-minus',
         ),
      );
   }



   /**
    * List of offer status
    *
    * @since 1.0.0
    * @return array
    */
   public static function offer_statuses(){

      return array(
         'published' => array(
            'title' => __('Published', 'woosa-bol-com-for-woocommerce'),
            'color' => '#46b450',
            'icon' => 'dashicons dashicons-yes',
         ),
         'not_published' => array(
            'title' => __('Not published', 'woosa-bol-com-for-woocommerce'),
            'color' => '',
            'icon' => 'dashicons dashicons-minus',
         ),
         'paused' => array(
            'title' => __('Paused', 'woosa-bol-com-for-woocommerce'),
            'color' => '#ffb900',
            'icon' => 'dashicons dashicons-hidden',
         ),
         'pending' => array(
            'title' => __('Pending', 'woosa-bol-com-for-woocommerce'),
            'color' => '#18ace6',
            'icon' => 'dashicons dashicons-clock',
         ),
         'updating' => array(
            'title' => __('Updating', 'woosa-bol-com-for-woocommerce'),
            'color' => '#18ace6',
            'icon' => 'dashicons dashicons-update',
         ),
         'deleting' => array(
            'title' => __('Deleting', 'woosa-bol-com-for-woocommerce'),
            'color' => '#a44',
            'icon' => 'dashicons dashicons-no-alt',
         ),
         'error' => array(
            'title' => __('Error', 'woosa-bol-com-for-woocommerce'),
            'color' => '#a44',
            'icon' => 'dashicons dashicons-warning',
         ),
         'info' => array(
            'title' => __('Information', 'woosa-bol-com-for-woocommerce'),
            'color' => '#0073aa',
            'icon' => 'dashicons dashicons-info',
         ),
      );
   }



   /**
    * List of actions per offer status
    *
    * @since 1.0.2
    * @return array
    */
   public static function offer_action_per_status(){

      return array(
         'publish'      => array(
            'status' => 'not_published',
            'label' => __('Publish', 'woosa-bol-com-for-woocommerce'),
         ),
         'republish'    => array(
            'status' => 'paused',
            'label' => __('Unpause', 'woosa-bol-com-for-woocommerce'),
         ),
         'paused'      => array(
            'status' => 'published',
            'label' => __('Put on pause', 'woosa-bol-com-for-woocommerce'),
         ),
         'update_price' => array(
            'status' => 'published',
            'label' => __('Update price', 'woosa-bol-com-for-woocommerce'),
         ),
         'update'       => array(
            'status' => 'published',
            'label' => __('Update reference code', 'woosa-bol-com-for-woocommerce'),
         ),
         'delete'       => array(
            'status' => array('published', 'paused'),
            'label' => __('Delete', 'woosa-bol-com-for-woocommerce'),
         )
      );
   }


}