<?php
/**
 * bol.com accounts
 *
 * Collection of methods for processing data in all available accounts.
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Accounts{



   /**
    * Renders offer status
    *
    * @since 1.1.1
    * @param string $product_id
    * @param string $account_id
    * @return string
    */
   public static function render_offer_status($product_id, $account_id = ''){

      $account_id = empty($account_id) ? Accounts::generate_id('1') : $account_id;
      $status   = get_post_meta($product_id, PREFIX."_{$account_id}_offer_status", true);
      $statuses = API::offer_statuses();

      /**
       * backward compatibility (only for first account)
       * @since 1.1.1
       */
      if(empty($status) && $account_id === Accounts::generate_id('1')){
         $status = get_post_meta($product_id, PREFIX."_offer_status", true);
      }

      if(isset($statuses[$status])){
         $output = '<span style="color: '.Utility::rgars($statuses, "{$status}/color").'">'.Utility::rgars($statuses, "{$status}/title").'</span>';
      }else{
         $output = '<span>'.Utility::rgars($statuses, "not_published/title").'</span>';
      }

      if( ! Core::has_addon('woosa-bol-multi-account')){

         $product = wc_get_product($product_id);

         if($product->is_type('variation')){
            echo '<span><b>'.__('Status', 'woosa-bol-com-for-woocommerce').':</b> '.$output.'</span>';
         }else{
            echo '<p><b>'.__('Status', 'woosa-bol-com-for-woocommerce').':</b> '.$output.'</p>';
         }

      }else{

         return $output;
      }
   }



   /**
    * Renders offer error.
    *
    * @since 1.1.1
    * @param int $product_id
    * @param string $account_id
    * @return string
    */
   public static function render_offer_error($product_id, $account_id = ''){

      $account_id = empty($account_id) ? Accounts::generate_id('1') : $account_id;
      $error = get_post_meta($product_id, PREFIX."_{$account_id}_product_error", true);

      /**
       * backward compatibility (only for first account)
       * @since 1.1.1
       */
      if(empty($error) && $account_id === Accounts::generate_id('1')){
         $error = get_post_meta($product_id, PREFIX."_product_error", true);
      }

      if(Core::has_addon('woosa-bol-multi-account')){
         if( ! empty($error) ){
            return '<span style="color: #a30000;">'.$error.'</span>';
         }else{
            return '<span>'.__('No errors', 'woosa-bol-com-for-woocommerce').'</span>';
         }
      }

      if( ! Core::has_addon('woosa-bol-multi-account') && ! empty($error) ){
         echo '<div class="'.PREFIX.'-error-message">'.$error.'</div>';
      }
   }



   /**
    * Renders offer reduction.
    *
    * @since 1.1.1
    * @param int $product_id
    * @param string $account_id
    * @return string
    */
   public static function render_offer_reduction($product_id, $account_id = ''){

      $account_id = empty($account_id) ? Accounts::generate_id('1') : $account_id;
      $status     = get_post_meta($product_id, PREFIX."_{$account_id}_offer_status", true);
      $price      = Product::get_price($product_id);
      $data       = self::get_offer_reduction($product_id, $account_id);
      $reduction  = $data['reduction'];
      $max_price  = $data['max_price'];
      $msg        = '<span>'.__('Not available', 'woosa-bol-com-for-woocommerce').'</span>';

      if( $reduction > 0 && $max_price > 0){

         if('published' === $status || 'paused' === $status){
            if($price > $max_price){
               $msg = '<span>'.sprintf(__('Adjust your price to %s or lower to receive a cost reduction of %s', 'woosa-bol-com-for-woocommerce'), wc_price($max_price), wc_price($reduction)).'</span>';

            }else{
               $msg = '<span>'.sprintf(__('You receive %s as cost reduction', 'woosa-bol-com-for-woocommerce'), wc_price($reduction)).'</span>';
            }
         }

      }

      if(Core::has_addon('woosa-bol-multi-account')){
         return $msg;
      }else{
         echo $msg;
      }
   }



   /**
    * Gets offer reductions
    *
    * @since 1.1.1
    * @param int|string $product_id
    * @param string $account_id
    * @return void
    */
   public static function get_offer_reduction($product_id, $account_id = ''){

      $account_id  = empty($account_id) ? Accounts::generate_id('1') : $account_id;
      $commissions = get_post_meta($product_id, PREFIX."_{$account_id}_ean_commissions", true);
      $price       = Product::get_price($product_id);


      /**
       * backward compatibility (only for first account)
       * @since 1.1.1
       */
      if(empty($commissions) && $account_id === Accounts::generate_id('1')){
         $commissions = get_post_meta($product_id, PREFIX."_ean_commissions", true);
      }

      return self::_get_reductions($commissions, $price);
   }



   /**
    * Gets reductions based on price.
    *
    * @since 1.1.1
    * @param object $commissions
    * @param string $price
    * @param string $mode
    * @return array
    */
   protected static function _get_reductions($commissions, $price, $mode = 'lost'){

      $reduction = '0';
      $max_price = '0';

      if(isset($commissions->reductions)){

         foreach($commissions->reductions as $item){

            $condition = 'lost' === $mode ? $item->maximumPrice < $price : $item->maximumPrice >= $price;

            if( $condition ){
               $reduction = $item->costReduction;
               $max_price = $item->maximumPrice;
               break;
            }
         }

         if($reduction === '0' && $max_price === '0'){
            return self::_get_reductions($commissions, $price, 'received');
         }
      }

      return [
         'reduction' => $reduction,
         'max_price' => $max_price,
      ];
   }



   /**
    * Generates a unique string.
    *
    * This is based on site's domain.
    *
    * @since 1.1.1
    * @param string $string
    * @return string
    */
   public static function generate_id($string){

      $salt = parse_url(home_url(), PHP_URL_HOST);

      return substr(hash('md5', $salt.$string), 0, 10);
   }



   /**
    * Returns a readable name for the given account.
    *
    * @since 1.1.1
    * @param int $index
    * @return string
    */
   public static function get_name($index){
      return sprintf('Bol - %s', $index);
   }



   /**
    * Gets list of accounts.
    *
    * @since 1.1.2 - make sure option value is always an array
    * @since 1.1.1
    * @param array $include - an array with certain accounts
    * @return array
    */
   public static function get_accounts($include = []){

      $id = self::generate_id('1');
      $accounts = array_filter((array) get_option(PREFIX . '_accounts'));

      if( ! isset($accounts[$id]) ){
         $accounts[$id] = self::default_account();
      }

      if( ! Core::has_addon('woosa-bol-multi-account') ){
         return [$id => $accounts[$id]];
      }

      if( is_array($include) && ! empty($include) ){
         foreach($accounts as $account_id => $item){
            if( ! in_array($account_id, $include)){
               unset($accounts[$account_id]);
            }
         }
      }

      return $accounts;
   }



   /**
    * Retrieves a specific account by the given id.
    *
    * @since 1.0.0
    * @param string $account_id
    * @return array|null
    */
   public static function get_account($account_id){

      $all = self::get_accounts();

      return Utility::rgar($all, $account_id, null);
   }



   /**
    * Returns the default account.
    *
    * @since 1.1.1
    * @return array
    */
   public static function default_account(){

      return apply_filters(PREFIX . '_default_account', [
         'client_api_id' => '',
         'client_api_secret' => '',
         'publish_products' => 'yes',
         'import_orders' => 'no',
         'import_invoices' => 'no',
         'import_returns' => 'no',
         'show_order_commission' => 'no',
         'excl_vat' => 'no',
         'account_id' => self::generate_id('1'),
      ]);
   }



   /**
    * Gets list of open orders.
    *
    * @since 1.1.1
    * @return array
    */
   public static function get_orders(){

      $orders = [];

      foreach(self::get_accounts() as $account_id => $item){

         $api = new API($account_id);
         $import_orders = Utility::rgar($api->account, 'import_orders', 'yes');

         if( 'yes' === $import_orders ){
            $orders = array_merge($orders, $api->get_orders());
         }
      }

      return $orders;
   }



   /**
    * Gets list of invoices.
    *
    * @since 1.1.1
    * @return array
    */
   public static function get_invoices(){

      $invoices = [];

      foreach(self::get_accounts() as $account_id => $item){

         $api = new API($account_id);
         $import_invoices = Utility::rgar($api->account, 'import_invoices', 'yes');

         if( 'yes' === $import_invoices ){
            $invoices = array_merge($invoices, $api->get_invoices());
         }
      }

      return $invoices;
   }



   /**
    * Gets list of returns.
    *
    * @since 1.1.1
    * @return array
    */
   public static function get_returns(){

      $returns = [];

      foreach(self::get_accounts() as $account_id => $item){

         $api = new API($account_id);
         $import_returns = Utility::rgar($api->account, 'import_returns', 'no');

         if( 'yes' === $import_returns ){
            $returns = array_merge($returns, $api->get_returns());
         }
      }

      return $returns;
   }



   /**
    * Creates an offer.
    *
    * @since 1.1.1
    * @param int $product_id
    * @param array $payload
    * @return void
    */
   public static function create_offer($product_id, $payload){

      $include = Utility::rgar($payload, 'accounts');

      foreach(self::get_accounts($include) as $account_id => $item){

         $offer_id = get_post_meta($product_id, PREFIX."_{$account_id}_offer_id", true);


         /**
          * backward compatibility (only for first account)
          * @since 1.1.1
          */
         if( empty($offer_id) && $account_id === self::generate_id('1') ){
            $offer_id = get_post_meta($product_id, PREFIX."_offer_id", true);
         }


         if(empty($offer_id)){

            $api = new API($account_id);
            $request = $api->create_offer($payload);
            $publish_products = Utility::rgar($api->account, 'publish_products', 'yes');

            if( 'yes' !== $publish_products ){
               $msg = sprintf(__('Publishing products in this account is disabled, please %sgo to settings%s to enable it.', 'woosa-bol-com-for-woocommerce'), '<a href="'.admin_url('edit.php?post_type=bol_invoice&page=bol-settings&tab=accounts').'">', '</a>');
               update_post_meta($product_id, PREFIX."_{$account_id}_product_error", $msg);
            }else{

               if(isset($request->id)){

                  Product::set_published_product($product_id);

                  Product::set_offer_action_status($product_id, [
                     'process_id' => $request->id,
                     'account_id' => $account_id,
                     'temp_status' => 'pending',
                  ]);

               }else{

                  update_post_meta($product_id, PREFIX."_{$account_id}_offer_status", 'not_published');

                  if(isset($request->debug->violations)){
                     foreach($request->debug->violations as $error){
                        if(strtoupper($error->name) == 'EAN'){
                           //we show this error message in case the EAN is invalid or is not recognized by bol.com
                           return update_post_meta($product_id, PREFIX."_{$account_id}_product_error", __('The EAN code is invalid (please check again) or is not recognized by bol.com, in this case you should log in into bol.com account and add this manually.', 'woosa-bol-com-for-woocommerce'));
                        }
                     }
                  }

                  $msg = is_string($request) ? $request : __('Something went wrong while trying to publish this offer.', 'woosa-bol-com-for-woocommerce');
                  update_post_meta($product_id, PREFIX."_{$account_id}_product_error", $msg);
               }
            }
         }
      }
   }



   /**
    * Update an offer stock on bol.com (for all accounts)
    *
    * @since 1.1.1
    * @param int $product_id
    * @param array $payload
    * @return void
    */
   public static function update_offer_stock($product_id, $payload){


      /**
       * backward compatibility
       * @since 1.1.1
       */
      delete_post_meta($product_id, PREFIX."_product_error");


      foreach(self::get_accounts() as $account_id => $item){

         $offer_id                  = get_post_meta($product_id, PREFIX."_{$account_id}_offer_id", true);
         $status                    = get_post_meta($product_id, PREFIX."_{$account_id}_offer_status", true);
         $stock_amount              = (int) Utility::rgar($payload, 'stock_amount');
         $stock_managed_by_retailer = Utility::rgar($payload, 'stock_managed_by_retailer');

         //remove current error
         delete_post_meta($product_id, PREFIX."_{$account_id}_product_error");


         /**
          * backward compatibility (only for first account)
          * @since 1.1.1
          */
         if( empty($offer_id) && $account_id === self::generate_id('1') ){
            $offer_id = get_post_meta($product_id, PREFIX."_offer_id", true);
            $status = get_post_meta($product_id, PREFIX."_offer_status", true);
         }


         if(empty($offer_id)){
            update_post_meta($product_id, PREFIX . "_{$account_id}_offer_status", 'not_published');
            update_post_meta($product_id, PREFIX . "_{$account_id}_product_error", __('Offer stock could not be updated because there is no offer id found.', 'woosa-bol-com-for-woocommerce'));
         }else{

            $api = new API($account_id);
            $request = $api->update_offer_stock($offer_id, array(
               "amount" => $stock_amount,
               "managedByRetailer" => $stock_managed_by_retailer
            ));

            if(isset($request->id)){
               Product::set_offer_action_status($product_id, [
                  'process_id' => $request->id,
                  'account_id' => $account_id,
                  'status' => $status,
                  'temp_status' => 'updating',
               ]);
            }else{
               $msg = is_string($request) ? $request : __('Something went wrong while trying to update offer stock.', 'woosa-bol-com-for-woocommerce');
               update_post_meta($product_id, PREFIX."_{$account_id}_product_error", $msg);
            }

         }
      }
   }



   /**
    * Deletes an offer.
    *
    * @since 1.0.0
    * @param int $product_id
    * @param array $payload
    * @return void
    */
    public static function delete_offer($product_id, $payload){

      $include = Utility::rgar($payload, 'accounts');

      foreach(self::get_accounts($include) as $account_id => $item){

         $offer_id = get_post_meta($product_id, PREFIX."_{$account_id}_offer_id", true);


         /**
          * backward compatibility (only for first account)
          * @since 1.1.1
          */
         if( empty($offer_id) && $account_id === self::generate_id('1') ){
            $offer_id = get_post_meta($product_id, PREFIX."_offer_id", true);
         }


         //we consider it was already removed if there is no offer id found
         if(empty($offer_id)){
            update_post_meta($product_id, PREFIX . "_{$account_id}_offer_status", 'not_published');
         }else{

            $api     = new API($account_id);
            $request = $api->delete_offer($offer_id);

            if(isset($request->id)){

               Product::remove_published_product($product_id);

               Product::set_offer_action_status($product_id, [
                  'process_id' => $request->id,
                  'account_id' => $account_id,
                  'temp_status' => 'deleting',
               ]);

            }else{
               $msg = is_string($request) ? $request : __('Something went wrong while trying to delete this offer.', 'woosa-bol-com-for-woocommerce');
               update_post_meta($product_id, PREFIX."_{$account_id}_product_error", $msg);
            }
         }
      }
   }



   /**
    * Saves commissions from all available accounts in the given product
    *
    * @since 1.1.1
    * @param int $product_id
    * @param array $payload
    * @return void
    */
   public static function save_commissions($product_id, $payload){

      $include = Utility::rgar($payload, 'accounts');

      foreach(self::get_accounts($include) as $account_id => $item){

         $api = new API($account_id);
         $request = $api->get_commissions($payload['ean_code'], $payload);

         if( isset($request->ean) ){
            update_post_meta($product_id, PREFIX."_{$account_id}_ean_commissions", $request);
         }
      }
   }

}