<?php
/**
 * Handle process status
 *
 * This is handling bol.com process status.
 *
 * @link https://api.bol.com/retailer/public/redoc/v3#operation/get-process-status
 *
 * @since 1.0.6
 */

namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Handle_Process_Status{


   /**
    * Handles process status.
    *
    * This checks every minute every process status from the list, once a status is `SUCCESS` it will be removed from the list.
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.6
    * @return void
    */
   public static function handle(){

      $processes = get_option(PREFIX.'_check_processes', []);

      if(count($processes) > 0 && get_transient(PREFIX.'_recheck_processes') === false){

         foreach($processes as $process_id => $process){

            $product_id = Utility::rgar($process, 'product_id');
            $account_id = Utility::rgar($process, 'account_id');
            $status     = Utility::rgar($process, 'status');

            $api     = new API($account_id);
            $request = $api->get_process_status($process_id);

            if( ! isset($request->status) || 404 == $request->status ){
               unset($processes[$process_id]);
            }

            if(isset($request->eventType)){

               switch(strtoupper($request->eventType)){

                  case 'CREATE_OFFER':

                     self::handle_create_offer_process($request, $process);

                     break;


                  case 'UPDATE_OFFER': case 'UPDATE_OFFER_PRICE': case 'UPDATE_OFFER_STOCK':

                     switch(strtoupper($request->status)){

                        case 'SUCCESS':

                           update_post_meta($product_id, PREFIX."_{$account_id}_offer_status", $status);

                           break;

                        case 'FAILURE': case 'TIMEOUT':

                           update_post_meta($product_id, PREFIX."_{$account_id}_offer_status", $status);
                           update_post_meta($product_id, PREFIX."_{$account_id}_product_error", $request->errorMessage);

                           break;

                     }

                     break;


                  case 'DELETE_OFFER':

                     switch(strtoupper($request->status)){

                        case 'SUCCESS':

                           $keep_error = get_post_meta($product_id, PREFIX."_{$account_id}_keep_error", true);

                           if($keep_error !== 'yes'){
                              delete_post_meta($product_id, PREFIX."_{$account_id}_product_error");
                           }

                           update_post_meta($product_id, PREFIX."_{$account_id}_offer_status", 'not_published');

                           delete_post_meta($product_id, PREFIX."_{$account_id}_offer_id");


                           /**
                            * backward compatibility
                            * @since 1.1.1
                            */
                           delete_post_meta($product_id, PREFIX."_offer_id");

                           /**
                            * backward compatibility
                            * @since 1.1.1
                            */
                           delete_post_meta($product_id, PREFIX."_product_error");

                           break;

                        case 'FAILURE': case 'TIMEOUT':

                           update_post_meta($product_id, PREFIX."_{$account_id}_product_error", $request->errorMessage);

                           break;

                     }

                     break;
               }


               if(strtoupper($request->status) !== 'PENDING'){
                  unset($processes[$process_id]);
               }
            }

         }


         if(count($processes) > 0){

            update_option(PREFIX.'_check_processes', $processes);

            //set to re-check
            set_transient(PREFIX.'_recheck_processes', 'yes', \MINUTE_IN_SECONDS);

         }else{
            delete_option(PREFIX.'_check_processes');
         }
      }
   }



   /**
    * Handles the `CREATE_OFFER` process event type.
    *
    * @since 1.1.1 - support for multiple accounts
    * @since 1.0.6
    * @param object $request
    * @param array $process - the process data
    * @return void
    */
   public static function handle_create_offer_process($request, $process){

      if( ! isset($request->status) ) return;

      $product_id = Utility::rgar($process, 'product_id');
      $account_id = Utility::rgar($process, 'account_id');

      switch(strtoupper($request->status)){

         case 'SUCCESS':
            self::update_offer_status($product_id, $request->entityId, $account_id);

            break;

         case 'FAILURE':

            $pattern = "/Duplicate found: retailer offer '(.*)' already has EAN .*/";
            preg_match($pattern, $request->errorMessage, $matches);

            if(isset($matches[1])) {
               $offer_id = $matches[1];
               self::update_offer_status($product_id, $offer_id, $account_id);
            }

            break;

         case 'TIMEOUT':

            update_post_meta($product_id, PREFIX."_{$account_id}_product_error", $request->errorMessage);
            update_post_meta($product_id, PREFIX."_{$account_id}_offer_status", 'not_published');

            break;
      }

   }



   /**
    * Updates the offer status
    *
    * @since 1.1.1 - support for multiple accounts
    * @param string $product_id
    * @param string $offer_id
    * @param string $account_id
    */
   public static function update_offer_status($product_id, $offer_id, $account_id) {

      $api = new API($account_id);
      $offer = $api->get_offer($offer_id);

      if(isset($offer->offerId)) {

         update_post_meta($product_id, PREFIX."_{$account_id}_offer_status", 'published');
         update_post_meta($product_id, PREFIX."_{$account_id}_offer_id", $offer_id);

         if(count($offer->notPublishableReasons) > 0) {

            $index = 1;
            $reasons = [];
            $skip = [4003];

            foreach($offer->notPublishableReasons as $reason) {
               if(isset($reason->code) && isset($reason->description)) {

                  if(in_array($reason->code, $skip)) continue;

                  if($reason->code == 3000){
                     $reason->description = sprintf(__('It looks like you do not have enough permissions to sell offers fulfilled by bol.com. Please %scontact bol.com%s for more details.', 'woosa-bol-com-for-woocommerce'), '<a href="https://partnerplatform.bol.com/services/logistiek-via-bol-com" target="_blank">', '</a>');
                  }
                  $reasons[] = $index.') '.$reason->description;

                  $index ++;
               }
            }

            if(count($reasons) > 0) {
               $error = implode(' ', $reasons);

               update_post_meta($product_id, PREFIX."_{$account_id}_product_error", $error);
               update_post_meta($product_id, PREFIX."_{$account_id}_offer_status", 'not_published');

               //mark to keep the error message even if the offer is deleted
               update_post_meta($product_id, PREFIX."_{$account_id}_keep_error", 'yes');

               delete_post_meta($product_id, PREFIX."_{$account_id}_offer_id");


               /**
                * backward compatibility
                * @since 1.1.1
                */
               delete_post_meta($product_id, PREFIX."_offer_id");

               /**
                * backward compatibility
                * @since 1.1.1
                */
               delete_post_meta($product_id, PREFIX."_product_error");

               //delete the offer as long as we have reasons not to publish it
               $api->delete_offer($offer_id);
            }

         }

      } else {

         update_post_meta($product_id, PREFIX."_{$account_id}_product_error", __('An error has occurred while trying to retrieve the offer from bol.com', 'woosa-bol-com-for-woocommerce'));
      }
   }


}