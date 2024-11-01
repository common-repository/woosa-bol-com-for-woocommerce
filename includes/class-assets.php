<?php
/**
 * This is responsible for enqueuing JS and CSS files
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Assets{


   /**
    * Enqueue files
    *
    * @since 1.0.0
    * @return void
    */
   public static function enqueue(){

      //---- CSS
      add_action('admin_enqueue_scripts', __CLASS__ . '::admin_styles', 9999);
      add_action('wp_enqueue_scripts', __CLASS__ . '::frontend_styles', 9999);

      //---- JS
      add_action('admin_enqueue_scripts', __CLASS__ . '::admin_scripts', 9999);
      add_action('wp_enqueue_scripts', __CLASS__ . '::frontend_scripts', 9999);
   }



   /**
    * Enqueue styles in admin area
    *
    * @since 1.0.0
    * @return void
    */
   public static function admin_styles(){

      wp_enqueue_style( 'thickbox' );

      wp_enqueue_style(
         __NAMESPACE__ . '_datetimepicker',
         PLUGIN_URL .'/assets/css/jquery.datetimepicker.min.css',
         array(),
         PLUGIN_VERSION
      );

      wp_enqueue_style(
         __NAMESPACE__ . '_admin',
         PLUGIN_URL .'/assets/css/admin.css',
         array(),
         PLUGIN_VERSION
      );

   }



   /**
    * Enqueue styles in frontend
    *
    * @since 1.0.0
    * @return void
    */
   public static function frontend_styles(){

      wp_enqueue_style(
         __NAMESPACE__ . '_frontend',
         PLUGIN_URL .'/assets/css/frontend.css',
         array(),
         PLUGIN_VERSION
      );

   }



   /**
    * Enqueue scripts in admin area
    *
    * @since 1.0.0
    * @return void
    */
    public static function admin_scripts(){

      wp_enqueue_script( 'thickbox' );

      wp_enqueue_script(
         __NAMESPACE__ . '_datetimepicker',
         PLUGIN_URL .'/assets/js/jquery.datetimepicker.full.min.js',
         array('jquery'),
         PLUGIN_VERSION,
         true
      );

      wp_enqueue_script(
         __NAMESPACE__ . '_admin',
         PLUGIN_URL .'/assets/js/admin.js',
         array('jquery'),
         PLUGIN_VERSION,
         true
      );

      wp_localize_script( 'jquery', 'woosa_'.PREFIX, array(
         'ajax' => array(
            'url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wsa-nonce' )
         ),
         'prefix' => PREFIX,
         'translation' => array(
            'btn' => array(
               'processing' => __('Processing...', 'woosa-bol-com-for-woocommerce'),
            ),
            'confirmHandleReturn' => __('Are you sure you want to mark this retun as handled?', 'woosa-bol-com-for-woocommerce'),
         )
      ));
   }



   /**
    * Enqueue scripts in frontend
    *
    * @since 1.0.0
    * @return void
    */
   public static function frontend_scripts(){

      wp_enqueue_script(
         __NAMESPACE__ . '_frontend',
         PLUGIN_URL .'/assets/js/frontend.js',
         array('jquery'),
         PLUGIN_VERSION,
         true
      );


      wp_localize_script( 'jquery', 'woosa_'.PREFIX, array(
         'ajax' => array(
            'url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wsa-nonce' )
         ),
         'prefix' => PREFIX
      ));
   }

}