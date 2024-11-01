<?php
/**
 * This is responsible for admin invoices page
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;


class Invoices{


   /**
    * The instance of this class
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

      add_action('init', __CLASS__.'::register_post_type');

      add_filter('manage_edit-bol_invoice_columns', __CLASS__ . '::table_head_columns');
   }



   /**
    * Head of table columns
    *
    * @since 1.0.0
    * @param object $columns
    * @return array
    */
    public static function table_head_columns( $columns ){

      $new_columns = [];

      foreach($columns as $key => $value){

         if($key == 'cb'){
            $new_columns[$key] = $value;
            $new_columns['_title'] = __('Invoice ID', 'woosa-bol-com-for-woocommerce');
         }
      }

      return $new_columns;
   }



   /**
    * Regiter new post type
    *
    * @since 1.0.3
    * @return void
    */
    public static function register_post_type() {

      register_post_type('bol_invoice', array(
         'label'               => __( 'Invoice', 'woosa-bol-com-for-woocommerce' ),
         'description'         => __( 'Display imported invoices', 'woosa-bol-com-for-woocommerce' ),
         'labels'              => array(
            'name'                  => __( 'Invoices', 'woosa-bol-com-for-woocommerce' ),
            'singular_name'         => __( 'Invoice', 'woosa-bol-com-for-woocommerce' ),
            'menu_name'             => __( 'Bol.com', 'woosa-bol-com-for-woocommerce' ),
            'name_admin_bar'        => __( 'Bol.com', 'woosa-bol-com-for-woocommerce' ),
            'archives'              => __( 'Item Archives', 'woosa-bol-com-for-woocommerce' ),
            'attributes'            => __( 'Item Attributes', 'woosa-bol-com-for-woocommerce' ),
            'parent_item_colon'     => __( 'Parent Item:', 'woosa-bol-com-for-woocommerce' ),
            'all_items'             => __( 'Invoices', 'woosa-bol-com-for-woocommerce' ),
            'add_new_item'          => __( 'Add new invoice', 'woosa-bol-com-for-woocommerce' ),
            'add_new'               => __( 'Add invoice', 'woosa-bol-com-for-woocommerce' ),
            'new_item'              => __( 'New invoice', 'woosa-bol-com-for-woocommerce' ),
            'edit_item'             => __( 'Edit invoice', 'woosa-bol-com-for-woocommerce' ),
            'update_item'           => __( 'Update invoice', 'woosa-bol-com-for-woocommerce' ),
            'view_item'             => __( 'View invoice', 'woosa-bol-com-for-woocommerce' ),
            'view_items'            => __( 'View invoices', 'woosa-bol-com-for-woocommerce' ),
            'search_items'          => __( 'Search invoices', 'woosa-bol-com-for-woocommerce' ),
            'not_found'             => __( 'Not found', 'woosa-bol-com-for-woocommerce' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'woosa-bol-com-for-woocommerce' ),
            'featured_image'        => __( 'Featured Image', 'woosa-bol-com-for-woocommerce' ),
            'set_featured_image'    => __( 'Set featured image', 'woosa-bol-com-for-woocommerce' ),
            'remove_featured_image' => __( 'Remove featured image', 'woosa-bol-com-for-woocommerce' ),
            'use_featured_image'    => __( 'Use as featured image', 'woosa-bol-com-for-woocommerce' ),
            'insert_into_item'      => __( 'Insert into item', 'woosa-bol-com-for-woocommerce' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'woosa-bol-com-for-woocommerce' ),
            'items_list'            => __( 'Invoices list', 'woosa-bol-com-for-woocommerce' ),
            'items_list_navigation' => __( 'Invoices list navigation', 'woosa-bol-com-for-woocommerce' ),
            'filter_items_list'     => __( 'Filter invoices list', 'woosa-bol-com-for-woocommerce' ),
         ),
         'public'              => false,
         'show_ui'             => true,
         'show_in_menu'        => true,
         'menu_icon'           => PLUGIN_URL.'/assets/images/bol-icon-logo.png',
         'publicly_queryable'  => false,
         'exclude_from_search' => true,
         'hierarchical'        => false,
         'show_in_nav_menus'   => false,
         'rewrite'             => false,
         'query_var'           => false,
         'map_meta_cap'        => true,
         'supports'            => array('title'),
         'has_archive'         => false,
         'capability_type'     => 'post',
         'capabilities' => array(
            'create_posts' => 'do_not_allow'
         ),
      ));

   }

}