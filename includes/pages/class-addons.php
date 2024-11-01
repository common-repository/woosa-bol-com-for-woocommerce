<?php
/**
 * This is responsible for admin addons page
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;


class Addons{


   /**
    * The instance of this class
    *
    * @since 1.1.1
    * @var null|object
    */
   protected static $instance = null;


	/**
	 * Return an instance of this class.
	 *
	 * @since     1.1.1
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
    * @since 1.1.1
    */
   public function __construct(){

      add_action('admin_menu', [$this, 'menu_page']);
   }



   /**
    * Add admin menu page
    *
    * @since 1.0.0
    * @return void
    */
   public function menu_page() {

      add_submenu_page(
         'edit.php?post_type=bol_invoice',
         __('Addons', 'woosa-bol-com-for-woocommerce'),
         __('Addons', 'woosa-bol-com-for-woocommerce'),
         'manage_options',
         'bol-addons',
         [$this, 'output_page']
      );
   }



   /**
    * Outpus the page content.
    *
    * @since 1.1.1
    * @return string
    */
   public function output_page(){

      echo Utility::get_tpl(PLUGIN_DIR . '/templates/addons-page.php', []);
   }



   /**
    * Gets available addons.
    *
    * @since 1.1.1
    * @return array
    */
   public static function get_list(){

      return $addons = [
         [
            'slug' => 'woosa-bol-multi-account',
            'title'       => __('Woosa - multi-account for bol.com', 'woosa-bol-com-for-woocommerce'),
            'thumbnail'   => PLUGIN_URL .'/assets/images/addons/bol-com-addon.jpg',
            'description' => __('This extension allows customers to sell products on bol.com by using up to 5 accounts.', 'woosa-bol-com-for-woocommerce'),
            'url'         => 'https://www.woosa.nl/product/bol-com-addon-multi-account/'
         ],
      ];
   }

}