<?php
/**
 * This is responsible for extending WooCommerce products
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Product{


   /**
    * Instance of publishing products background-process.
    *
    * @since 1.1.0
    */
   public static $publish_products;


   /**
    * The instance of this class.
    *
    * @since 1.0.0
    * @var null|object
    */
   protected static $instance = null;



	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
   }



   /**
    * @since 1.0.0
    */
   public function __construct(){

      add_action('init', __CLASS__ . '::init_background_process');

      add_filter('manage_edit-product_columns', __CLASS__ . '::table_head_columns');
      add_action('manage_product_posts_custom_column', __CLASS__ . '::table_content_columns', 10, 2);

      add_filter('woocommerce_product_data_tabs', __CLASS__ . '::add_settings_tab');
      add_action('woocommerce_product_data_panels', __CLASS__ . '::output_settings_tab', 10, 2);
      add_action('woocommerce_process_product_meta', __CLASS__.'::save_settings_tab');

      add_filter('bulk_actions-edit-product', __CLASS__ . '::add_bulk_actions');
      add_action('handle_bulk_actions-edit-product', __CLASS__ . '::handle_bulk_actions', 10, 3);

      add_action('woocommerce_variation_set_stock', [__CLASS__, 'trigger_updating_offer_stock']);
      add_action('woocommerce_product_set_stock', [__CLASS__, 'trigger_updating_offer_stock']);

   }



   /**
    * Initiates background processes
    *
    * @since 1.0.6
    * @return void
    */
   public static function init_background_process(){

      self::$publish_products = new Publish_Products();
   }



   /**
    * Saves meta values
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.1.0
    * @param int $post_id
    * @param int $account_id
    * @param array $values
    * @return void
    */
   public static function save_meta($post_id, $values){

      //save meta
      foreach($values as $key => $value){
         if( $key == 'ean_code'){
            self::set_ean_code($post_id, $value);
         }else{
            update_post_meta($post_id, PREFIX.'_'.$key, $value);
         }
      }
   }



   /**
    * Gets meta values
    *
    * @since 1.1.0
    * @param int $product_id
    * @param string $key
    * @param string $default
    * @return string
    */
   public static function get_meta($product_id, $key, $default = ''){

      $meta = get_post_meta($product_id, $key, true);

      if(empty($meta)){
         return $default;
      }

      return $meta;
   }



   /**
    * Calculates price addition
    *
    * @since 1.1.0
    * @param string $price
    * @param string $addition
    * @return string
    */
   public static function calculate_price_addition($price, $addition){

      if(strpos($addition, '%') !== false){

         $addition = str_replace('%', '', $addition);
         $addition =  $addition === '' ? null : $addition / 100;

         if( ! is_null($addition) && ! empty($price) ){
            $price += $price * $addition;
         }

      }elseif( ! empty($addition) && ! empty($price) ){
         $price += $addition;
      }

      return $price;
   }



   /**
    * Gets product price.
    *
    * @since 1.1.1
    * @param int $product_id
    * @return string
    */
   public static function get_price($product_id){

      $product = wc_get_product($product_id);
      $price = Utility::format_price( self::get_meta($product->get_id(), PREFIX.'_price', '0.00') );

      $use_wc_price   = Settings::get_option('use_wc_price', 'no', true);
      $price_addition = Settings::get_option('price_addition', '0', true);

      if('yes' === $use_wc_price){
         $price = Utility::format_price( self::calculate_price_addition($product->get_price(), $price_addition) );
      }

      return $price;
   }



   /**
    * Table head of product columns
    *
    * @since 1.0.0
    * @param object $columns
    * @return $new_columns
    */
   public static function table_head_columns( $columns ){

      $new_columns = array();

      foreach ( $columns as $column_name => $column_info ) {

         $new_columns[ $column_name ] = $column_info;

         if ( 'product_cat' === $column_name ) {

            $new_columns[PREFIX.'_offer_status'] = sprintf(__( 'Bol.com %s', 'woosa-bol-com-for-woocommerce' ), wc_help_tip(__('Status of this product in bol.com', 'woosa-bol-com-for-woocommerce')));

         }
      }

      return $new_columns;
   }



   /**
    * Table content of product columns
    *
    * @since 1.1.1 - added support for multiple accounts
    * @since 1.0.3 - added status for variable product
    * @since 1.0.1
    * @param object $column
    * @param int $product_id
    * @return string
    */
   public static function table_content_columns($column, $product_id){

      if ( $column === PREFIX.'_offer_status'){

         $product  = wc_get_product($product_id);
         $price    = Product::get_price($product_id);
         $statuses = API::offer_statuses();
         $list     = [];
         $reductions = [];

         foreach(Accounts::get_accounts() as $account_id => $item){

            $commissions = get_post_meta($product_id, PREFIX."_{$account_id}_ean_commissions", true);
            $status      = get_post_meta($product_id, PREFIX."_{$account_id}_offer_status", true);
            $error       = get_post_meta($product_id, PREFIX."_{$account_id}_product_error", true);


            /**
             * backward compatibility (only for first account)
             * @since 1.1.1
             */
            if(empty($error) && $account_id === Accounts::generate_id('1')){
               $error = get_post_meta($product_id, PREFIX."_product_error", true);
            }

            /**
             * backward compatibility (only for first account)
             * @since 1.1.1
             */
            if(empty($status) && $account_id === Accounts::generate_id('1')){
               $status = get_post_meta($product_id, PREFIX."_offer_status", true);
            }


            if($product->is_type('variable')){

               $var_statuses = [];

               foreach($product->get_available_variations() as $item){

                  $price  = Product::get_price($item['variation_id']);
                  $status = get_post_meta($item['variation_id'], PREFIX."_{$account_id}_offer_status", true);
                  $error  = get_post_meta($item['variation_id'], PREFIX."_{$account_id}_product_error", true);

                  /**
                   * backward compatibility (only for first account)
                   * @since 1.1.1
                   */
                  if(empty($error) && $account_id === Accounts::generate_id('1')){
                     $error = get_post_meta($item['variation_id'], PREFIX."_product_error", true);
                  }

                  /**
                   * backward compatibility (only for first account)
                   * @since 1.1.1
                   */
                  if(empty($status) && $account_id === Accounts::generate_id('1')){
                     $status = get_post_meta($item['variation_id'], PREFIX."_offer_status", true);
                  }

                  $list[] = $status;

                  if(!empty($error)){
                     $list[] = 'error';
                  }

                  if( 'published' === $status || 'paused' === $status){

                     $data        = Accounts::get_offer_reduction($item['variation_id'], $account_id);
                     $reduction   = $data['reduction'];
                     $max_price   = $data['max_price'];

                     if($reduction > 0 && $max_price > 0){
                        if($price > $max_price){
                           $list[] = 'reduction_lost';
                        }else{
                           $list[] = 'reduction_won';
                        }
                     }
                  }
               }

            }else{

               $list[] = $status;

               if(!empty($error)){
                  $list[] = 'error';
               }

               if( 'published' === $status || 'paused' === $status){

                  $data        = Accounts::get_offer_reduction($product_id, $account_id);
                  $reduction   = $data['reduction'];
                  $max_price   = $data['max_price'];

                  if($reduction > 0 && $max_price > 0){
                     if($price > $max_price){
                        $list[] = 'reduction_lost';
                     }else{
                        $list[] = 'reduction_won';
                     }
                  }
                  $reductions[] = $data;
               }
            }


         }

         $list = array_count_values(array_map(function($value) {
            return $value == "" ? 'not_published' : $value;
         }, $list));

         foreach($list as $key => $number){

            $title = Utility::rgars($statuses, "{$key}/title");
            $icon = Utility::rgars($statuses, "{$key}/icon");
            $color = Utility::rgars($statuses, "{$key}/color");
            $counter = Core::has_addon('woosa-bol-multi-account') ? "({$number})" : '';

            if('error' === $key){
               $title = __('Some errors were found, check the product!', 'woosa-bol-com-for-woocommerce');
            }

            if('reduction_lost' === $key){
               $title = __('Adjust your price to receive the reduction, check the product!', 'woosa-bol-com-for-woocommerce');
               $icon = Utility::rgars($statuses, "info/icon");
               $color = Utility::rgars($statuses, "info/color");
            }

            if('reduction_won' === $key){
               $title = __('This product has a cost reduction!', 'woosa-bol-com-for-woocommerce');
               echo '<span class="woocommerce-help-tip" data-tip="'.$counter.' '.$title.'">
                     <span style="color: #46b450;">&euro;</span>
                  </span>';
            }else{
               echo '<span class="woocommerce-help-tip" data-tip="'.$counter.' '.$title.'">
                  <span class="'.$icon.'" style="color: '.$color.'"></span>
               </span>';
            }
         }

      }

   }



   /**
    * Process product as offer for bol.com
    *
    * @since 1.1.1 - added support for multiple accounts
    *              - in case stock is greater than 999 then send to bol.com only 999
    * @since 1.1.0 - save EAN commissions and possible reductions
    * @since 1.0.6 - set EAN code only if it does not exist
    * @since 1.0.4 - format price to 2 decimals
    * @since 1.0.3
    * @param int $post_id
    * @return void
    */
   public static function process_offer($post_id){

      $product = wc_get_product($post_id);

      //stop if no product
      if( ! $product instanceof \WC_Product ) return;

      $action = self::get_meta($product->get_id(), PREFIX.'_action', 'no');
      $stock_amount = $product->get_stock_quantity() > '999' ? 999 : (int) $product->get_stock_quantity();

      $payload = [
         'accounts'                  => self::get_meta($product->get_id(), PREFIX.'_accounts', []),
         'price'                     => Product::get_price($product->get_id()),
         'shipping_cost'             => Utility::format_price( self::get_meta($product->get_id(), PREFIX.'_shipping_cost', Settings::get_option('shipping_cost', '0.00', true)) ),
         'reference_code'            => self::get_meta($product->get_id(), PREFIX.'_reference_code'),
         'fulfilment_delivery_code'  => self::get_meta($product->get_id(), PREFIX.'_fulfilment_delivery_code', Settings::get_option('fulfilment_delivery_code', '24uurs-23', true)),
         'fulfilment_type'           => self::get_meta($product->get_id(), PREFIX.'_fulfilment_type', Settings::get_option('fulfilment_type', 'FBR', true)),
         'condition_name'            => self::get_meta($product->get_id(), PREFIX.'_condition_name', Settings::get_option('condition_name', 'NEW', true)),
         'stock_managed_by_retailer' => self::get_meta($product->get_id(), PREFIX.'_stock_managed_by_retailer', Settings::get_option('condition_name', 'NEW', true)),
         'stock_amount'              => $stock_amount,
         'ean_code'                  => self::get_ean_code_by_source($product->get_id()),
      ];

      //add shipping cost
      $payload['price'] = number_format($payload['price'] + $payload['shipping_cost'], 2, '.', '');

      Accounts::save_commissions($product->get_id(), $payload);

      //stop if no action
      if($action == 'no') return;

      //check for required fields
      if( ! self::fields_are_valid($product->get_id(), $payload) ) return;

      //perform the action
      switch($action){

         case 'delete':
            Accounts::delete_offer($product->get_id(), $payload);
            break;

         case 'publish':

            if( ! self::reached_publish_limit() ){
               Accounts::create_offer($product->get_id(), $payload);
            }
            break;
      }

   }



   /**
    * Adds a product in the published list.
    *
    * @since 1.1.2
    * @param int|string $product_id
    * @return void
    */
   public static function set_published_product($product_id){

      $list = get_option(PREFIX . '_published_products', []);

      if( ! in_array($product_id, $list) ){
         array_push($list, $product_id);

         if(count($list) >= Core::$publish_limit) {
            update_option(PREFIX . '_reached_publish_limit', 'yes');
         }

         update_option(PREFIX . '_published_products', $list);
      }
   }



   /**
    * Removes a product from the published list.
    *
    * @since 1.1.2
    * @param int|string $product_id
    * @return void
    */
   public static function remove_published_product($product_id){

      $list = get_option(PREFIX . '_published_products', []);
      $key = array_search($product_id, $list);

      if( $key !== false){
         unset($list[$key]);

         if(count($list) < Core::$publish_limit) {
            update_option(PREFIX . '_reached_publish_limit', 'yes');
         }

         update_option(PREFIX . '_published_products', $list);
      }
   }



   /**
    * Checks whether or not the max limit is reached.
    *
    * @since 1.1.2
    * @return bool
    */
   public static function reached_publish_limit(){

      $list = get_option(PREFIX . '_published_products', []);

      if(count($list) >= Core::$publish_limit) {
         update_option(PREFIX . '_reached_publish_limit', 'yes');
         return true;
      }

      delete_option(PREFIX . '_reached_publish_limit');

      return false;
   }



   /**
    * Checkes whether required fields are valid or not
    *
    * @since 1.1.0
    * @param int $product_id
    * @param array $payload
    * @return bool
    */
   public static function fields_are_valid($product_id, $payload){

      //clear old error
      delete_post_meta($product_id, PREFIX.'_product_error');

      //clear error per account
      foreach(Accounts::get_accounts() as $account_id => $item){
         delete_post_meta($product_id, PREFIX."_{$account_id}_product_error");
      }

      $valid = true;
      $messages = [
         'price'                     => __('Please provide a valid product price (between 1 and 9999).', 'woosa-bol-com-for-woocommerce'),
         'ean_code'                  => __('Please provide the EAN code.', 'woosa-bol-com-for-woocommerce'),
         'condition_name'            => __('Please provide the product condition.', 'woosa-bol-com-for-woocommerce'),
         'stock_amount'              => __('Please provide a valid stock amount (between 0 and 999).', 'woosa-bol-com-for-woocommerce'),
         'stock_managed_by_retailer' => __('Please specify who will manage the stock amount.', 'woosa-bol-com-for-woocommerce'),
         'fulfilment_delivery_code'  => __('Please provide the time of delivery.', 'woosa-bol-com-for-woocommerce'),
         'fulfilment_type'           => __('Please specify who will fulfill the shipping.', 'woosa-bol-com-for-woocommerce'),
         'reference_code'            => __('Please provide a valid reference (between 0 and 20 characters).', 'woosa-bol-com-for-woocommerce'),
      ];


      foreach($payload as $key => $value){

         if( '' === $value ){
            $valid = false;
         }

         if( 'reference_code' === $key){
            $valid = true;//mark as valid because it's not required
            if(strlen($value) > 20){
               $valid = false;
            }
         }

         if( 'stock_amount' === $key){
            if( $value < 0 || $value > 999){
               $valid = false;
            }
         }

         if( 'price' === $key){
            if( $value < 1 || $value > 9999){
               $valid = false;
            }
         }

         if( ! $valid ){

            $accounts = Utility::rgar($payload, 'accounts');

            foreach(Accounts::get_accounts($accounts) as $account_id => $item){
               update_post_meta($product_id, PREFIX."_{$account_id}_product_error", $messages[$key]);
            }

            break;
         }

      }

      return $valid;

   }



   /**
    * Add custom settings tab
    *
    * @since 1.0.3 - added tab for variable product
    * @since 1.0.0
    * @param array $tabs
    * @return array
    */
   public static function add_settings_tab($tabs){

      $tabs[PREFIX.'_simple'] = array(
         'label'    => 'Bol.com',
         'target'   => PREFIX.'_simple_data',
         'class'    => array('show_if_simple'),
         'priority' => 28,
      );

      $tabs[PREFIX.'_variable'] = array(
         'label'    => 'Bol.com',
         'target'   => PREFIX.'_variable_data',
         'class'    => array('show_if_variable'),
         'priority' => 28,
      );


      return $tabs;
   }



   /**
    * Output content of custom settings tab
    *
    * @since 1.0.3 - added template support for variable products
    * @since 1.0.0
    * @return string
    */
   public static function output_settings_tab($object = '', $type = ''){

      global $post;

      $post = empty($object) ? $post : $object;


      if('auto-draft' === $post->post_status){

         $msg = __('You first need to publish your WooCommerce product, before you can publish it on bol.com', 'woosa-bol-com-for-woocommerce');

         echo '<div id="'.PREFIX.'_variable_data" class="panel woocommerce_options_panel hidden">
            <div style="padding: 15px;">'.$msg.'</div>
         </div>';
         echo '<div id="'.PREFIX.'_simple_data" class="panel woocommerce_options_panel hidden">
            <div style="padding: 15px;">'.$msg.'</div>
         </div>';

         //set a flag for new created products
         update_post_meta($post->ID, PREFIX . '_is_auto_draft', 'yes');

         return;
      }


      $tpl_variable = $tpl_simple = '';
      $product = wc_get_product($post->ID);
      $statuses = API::offer_statuses();

      //template for variable product
      if(($product->is_type('variable') && empty($type)) || $type == 'variable'){

         $variations = $product->get_available_variations();

         $tpl_variable = Utility::get_tpl(PLUGIN_DIR.'/templates/panel-product-variable.php', array(
            'post' => $post,
            'product' => $product,
            'variations' => $variations,
            'statuses' => $statuses,
         ));

         if(!empty($type)) return $tpl_variable;


      //template for simple product
      }else if(($product->is_type('simple') && empty($type)) || $type == 'simple'){


         $ean_code = get_post_meta($product->get_id(), PREFIX.'_ean_code', true);
         $price    = get_post_meta($product->get_id(), PREFIX.'_price', true) ?: $product->get_price();

         $reference_code = get_post_meta($product->get_id(), PREFIX.'_reference_code', true);
         $reference_code = empty($reference_code) ? $product->get_sku() : $reference_code;
         $reference_code = substr($reference_code, 0, 20);

         $shipping_cost = get_post_meta($product->get_id(), PREFIX.'_shipping_cost', true);
         $shipping_cost = empty($shipping_cost) ? Settings::get_option('shipping_cost', '0.00', true) : $shipping_cost;

         $stock_amount = (int) $product->get_stock_quantity();

         $stock_managed_by_retailer = get_post_meta($product->get_id(), PREFIX.'_stock_managed_by_retailer', true);
         $stock_managed_by_retailer = empty($stock_managed_by_retailer) ? Settings::get_option('stock_managed_by_retailer', 'true', true) : $stock_managed_by_retailer;

         $fulfilment_delivery_code = get_post_meta($product->get_id(), PREFIX.'_fulfilment_delivery_code', true);
         $fulfilment_delivery_code = empty($fulfilment_delivery_code) ? Settings::get_option('fulfilment_delivery_code', '24uurs-23', true) : $fulfilment_delivery_code;

         $fulfilment_type = get_post_meta($product->get_id(), PREFIX.'_fulfilment_type', true);
         $fulfilment_type = empty($fulfilment_type) ? Settings::get_option('fulfilment_type', 'FBR', true) : $fulfilment_type;

         $condition_name = get_post_meta($product->get_id(), PREFIX.'_condition_name', true);
         $condition_name = empty($condition_name) ? Settings::get_option('condition_name', 'NEW', true) : $condition_name;


         $tpl_simple = Utility::get_tpl(PLUGIN_DIR.'/templates/panel-product-simple.php', array(
            'product'                   => $product,
            'price'                     => $price,
            'ean_code'                  => $ean_code,
            'reference_code'            => $reference_code,
            'shipping_cost'             => $shipping_cost,
            'stock_amount'              => $stock_amount,
            'stock_managed_by_retailer' => $stock_managed_by_retailer,
            'fulfilment_delivery_code'  => $fulfilment_delivery_code,
            'fulfilment_type'           => $fulfilment_type,
            'condition_name'            => $condition_name,
         ));

         if(!empty($type)) return $tpl_simple;
      }

      echo '<div id="'.PREFIX.'_variable_data" class="panel woocommerce_options_panel hidden">'.$tpl_variable.'</div>';
      echo '<div id="'.PREFIX.'_simple_data" class="panel woocommerce_options_panel hidden">'.$tpl_simple.'</div>';
   }



   /**
    * Save custom settings tab
    *
    * @since 1.0.3 - save accordind to product type
    * @since 1.0.2 - format number of prices
    *              - added update price, stock and reference code actions
    * @since 1.0.0
    * @param int $product_id
    * @return void
    */
   public static function save_settings_tab($product_id){

      if(isset($_POST[PREFIX.'_offer'])){

         $type = Utility::rgar($_POST, 'product-type');
         $payload = Utility::rgars($_POST, PREFIX.'_offer');

         if( 'simple' === $type ){

            self::save_meta($product_id, $payload);
            self::process_offer($product_id);

         }elseif( 'variable' === $type ){

            foreach($payload as $variation_id => $meta){
               self::save_meta($variation_id, $meta);
               self::process_offer($variation_id);
            }
         }

      }
   }



   /**
    * Add custom bulk actions
    *
    * @since 1.0.5
    * @param object $bulk_actions
    * @return $bulk_actions
    */
    public static function add_bulk_actions($bulk_actions) {

      $bulk_actions[PREFIX.'_publish'] = __( 'Bol: Publish', 'woosa-bol-com-for-woocommerce');
      $bulk_actions[PREFIX.'_reset'] = __( 'Bol: Reset', 'woosa-bol-com-for-woocommerce');

      return $bulk_actions;
   }



   /**
    * Handle custom bulk actions
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.6 - delete flag if there are no items
    * @since 1.0.5
    * @param string $redirect_to
    * @param string $doaction
    * @param array $post_ids
    * @return void
    */
   public static function handle_bulk_actions($redirect_to, $doaction, $post_ids){

      if($doaction == PREFIX.'_reset'){

         foreach($post_ids as $id){

            /**
             * backward compatibility for old offer status
             * @since 1.1.1
             */
            delete_post_meta($id, PREFIX.'_offer_status');
            /**
             * backward compatibility for old offer error
             * @since 1.1.1
             */
            delete_post_meta($id, PREFIX.'_product_error');


            foreach(Accounts::get_accounts() as $account_id => $item){
               delete_post_meta($id, PREFIX."_{$account_id}_offer_id");
               delete_post_meta($id, PREFIX."_{$account_id}_offer_status");
               delete_post_meta($id, PREFIX."_{$account_id}_product_error");
            }
         }
      }


      return $redirect_to;

   }



   /**
    * Sets action status
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.6 - changed function name
    * @since 1.0.2
    * @param int $product_id
    * @param array $data - additional data
    * @return void
    */
   public static function set_offer_action_status($product_id, $data){

      $process_id = Utility::rgar($data, 'process_id');
      $account_id = Utility::rgar($data, 'account_id');
      $status = Utility::rgar($data, 'status');
      $temp_status = Utility::rgar($data, 'temp_status');
      $list       = get_option(PREFIX.'_check_processes', []);

      if( ! isset($list[$process_id]) ){
         $list[$process_id] = [
            'account_id' => $account_id,
            'product_id' => $product_id,
            'status' => $status,
         ];
      }

      update_post_meta($product_id, PREFIX."_{$account_id}_offer_status", $temp_status);
      update_post_meta($product_id, PREFIX."_{$account_id}_process_status_id", $process_id);

      update_option(PREFIX.'_check_processes', $list);

      //set to re-check
      set_transient(PREFIX.'_recheck_processes', 'yes', \MINUTE_IN_SECONDS);
   }



   /**
    * Retrieves product ID by EAN code.
    *
    * @since 1.0.6 - returns post ID and includes `product_variation` post type in the query as well
    * @since 1.0.0
    * @param string $value
    * @return int|null
    */
   public static function get_id_by_ean_code($value){

      if( '' !== $value ){

         $query = new \WP_Query(array(
            'posts_per_page' => 1,
            'post_type' => ['product', 'product_variation'],
            'post_status' => 'any',
            'meta_query' => array(
               array(
                  'key' => PREFIX.'_ean_code',
                  'value' => $value,
                  'compare' => '=',
               )
            )
         ));

         if(isset($query->posts[0])) return (int) $query->posts[0]->ID;
      }

      return null;
   }



   /**
    * Get EAN code based on the source
    *
    * @since 1.1.0
    * @param int $product_id
    * @return string
    */
   public static function get_ean_code_by_source($product_id){

      $source = Settings::get_option('ean_source', 'default', true);
      $default = get_post_meta($product_id, PREFIX.'_ean_code', true);

      switch($source){

         case 'custom_field':

            $field_name = Settings::get_option('ean_custom_field_name', false, true);
            if( !empty($field_name) ){
               $ean = get_post_meta($product_id, $field_name, true);
            }

            break;


         case 'sku':

            $product = wc_get_product($product_id);

            if($product instanceof \WC_Product){
               $ean = $product->get_sku();
            }

            break;

      }

      if( !empty($ean) ) return $ean;

      return $default;
   }



   /**
    * Sets EAN code only if it does not exist.
    *
    * @since 1.0.6
    * @param int $post_id
    * @param string $value
    * @return bool
    */
   public static function set_ean_code($post_id, $value){

      $product = is_null(self::get_id_by_ean_code($value)) ? '' : wc_get_product( self::get_id_by_ean_code($value) );

      if( $product instanceof \WC_Product ){

         $product_id = $product->get_id();

         //only if it's different
         if($product_id != $post_id){

            foreach(Accounts::get_accounts() as $account_id => $item){
               update_post_meta($post_id, PREFIX."_{$account_id}_product_error", sprintf(
                  __('This EAN code %s already exists for this product: %s', 'woosa-bol-com-for-woocommerce'),
                  $value,
                  "(ID: {$product_id}) ".get_the_title($product_id)
               ));
            }

            return false;
         }

      }

      update_post_meta($post_id, PREFIX.'_ean_code', $value);

      return true;
   }



   /**
    * Updates offer stock after Woo product stock has been changed
    *
    * @since 1.1.1 - skip synchronization when imported orders reduced the stock
    *              - in case stock is greater than 999 then send to bol.com only 999
    * @since 1.0.6
    * @param object $product
    * @return void
    */
   public static function trigger_updating_offer_stock( $product ){

      $skip = get_transient( $product->get_id().'_skip_sync_stock');
      $stock = $product->get_stock_quantity() > '999' ? 999 : (int) $product->get_stock_quantity();
      $stock_managed_by = get_post_meta($product->get_id(), PREFIX.'_stock_managed_by_retailer', true);
      $is_auto_draft = get_post_meta($product->get_id(), PREFIX.'_is_auto_draft', true);
      $fulfilment_type = get_post_meta($product->get_id(), PREFIX.'_fulfilment_type', true);
      $fulfilment_type = empty($fulfilment_type) ? Settings::get_option('fulfilment_type', 'FBR', true) : $fulfilment_type;

      if($skip || 'FBB' === $fulfilment_type) return;

      //this means it's a new product just created, do not do sync the stock for this but remove this flag
      if( 'yes' === $is_auto_draft ){
         return delete_post_meta($product->get_id(), PREFIX.'_is_auto_draft');
      }

      $valid = self::fields_are_valid($product->get_id(), [
         'stock_amount' => $stock
      ]);

      if($valid){
         Accounts::update_offer_stock($product->get_id(), [
            'stock_amount' => $stock,
            'stock_managed_by_retailer' => $stock_managed_by,
         ]);
      }
   }



   /**
    * Renders a dropdown with offer actions
    *
    * @since 1.1.1
    * @param string $product_id
    * @return string
    */
   public static function render_dropdown_actions($product_id){

      $hide = false;
      $product = wc_get_product($product_id);
      $name = $product->is_type('variation') ? PREFIX . "_offer[{$product_id}][action]" : PREFIX . "_offer[action]";
      $accounts = get_post_meta($product_id, PREFIX . '_accounts', true);

      foreach(Accounts::get_accounts($accounts) as $account_id => $item){
         $status = get_post_meta($product_id, PREFIX . "_{$account_id}_offer_status", true);

         if( 'updating' === $status || 'deleting' === $status || 'pending' === $status ){
            $hide = true;
            break;
         }
      }

      if($hide) return;
      ?>
      <p class="form-field">
         <label><?php echo __('Action', 'woosa-bol-com-for-woocommerce');?></label>
         <select class="short" name="<?php echo esc_attr( $name );?>">
            <option value="no"><?php _e('No action', 'woosa-bol-com-for-woocommerce');?></option>
            <option value="publish"><?php _e('Publish', 'woosa-bol-com-for-woocommerce');?></option>
            <option disabled="disabled" value="update_price"><?php _e('Update price', 'woosa-bol-com-for-woocommerce');?></option>
            <option disabled="disabled" value="update"><?php _e('Update reference code, fulfilment & delivery time', 'woosa-bol-com-for-woocommerce');?></option>
            <option disabled="disabled" value="update_stock_management"><?php _e('Update stock management', 'woosa-bol-com-for-woocommerce');?></option>
            <option disabled="disabled" value="pause"><?php _e('Pause / Unpause', 'woosa-bol-com-for-woocommerce');?></option>
            <option value="delete"><?php _e('Delete', 'woosa-bol-com-for-woocommerce');?></option>
         </select>
         <?php echo wc_help_tip(__('Choose what action to apply in bol.com for this product', 'woosa-bol-com-for-woocommerce'));?>
      </p>
      <?php
   }

}