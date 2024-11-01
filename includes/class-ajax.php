<?php
/**
  * AJAX Requests
 *
 * This class is used for processing AJAX requests.
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class AJAX{


   /**
    * Catches AJAX requests.
    *
    * @since 1.1.1
    */
   public static function init(){

      add_action('wp_ajax_'.PREFIX.'_get_product_tab_tpl', __CLASS__ . '::process_product_tab_tpl');
   }



   /**
    * Return template according to product type
    *
    * @since 1.0.3
    * @return void
    */
   public static function process_product_tab_tpl(){

      //check to make sure the request is from same server
      if(!check_ajax_referer( 'wsa-nonce', 'security', false )){
         return;
      }

      $post_id = Utility::rgar($_POST, 'post_id');
      $type    = Utility::rgar($_POST, 'type');
      $tpl     = Product::output_settings_tab(get_post($post_id), $type);

      wp_send_json(array(
         'tpl' => $tpl,
      ));

   }

}