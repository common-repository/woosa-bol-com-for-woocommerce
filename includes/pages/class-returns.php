<?php
/**
 * This is responsible for admin returns page
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;


class Returns{


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

      add_action('init', __CLASS__.'::register_post_type');

      add_filter('manage_edit-bol_return_columns', __CLASS__ . '::table_head_columns');
   }



   /**
    * Table head of columns
    *
    * @since 1.1.1
    * @param object $columns
    * @return array
    */
    public static function table_head_columns( $columns ){

      $new_columns = array();

      foreach ( $columns as $column_name => $column_info ) {

         $new_columns[ $column_name ] = $column_info;

         if( 'title' === $column_name ) {
            $new_columns[PREFIX.'_status'] = __('Status', 'woosa-bol-com-for-woocommerce');
         }
      }

      return $new_columns;
   }



   /**
    * Regiter new post type
    *
    * @since 1.1.1
    * @return void
    */
    public static function register_post_type() {

      register_post_type('bol_return', array(
         'label'               => __( 'Return', 'woosa-bol-com-for-woocommerce' ),
         'description'         => __( 'Display imported returns', 'woosa-bol-com-for-woocommerce' ),
         'labels'              => array(
            'name'                  => __( 'Returns', 'woosa-bol-com-for-woocommerce' ),
            'singular_name'         => __( 'Return', 'woosa-bol-com-for-woocommerce' ),
            'menu_name'             => __( 'Bol.com', 'woosa-bol-com-for-woocommerce' ),
            'name_admin_bar'        => __( 'Bol.com', 'woosa-bol-com-for-woocommerce' ),
            'archives'              => __( 'Item Archives', 'woosa-bol-com-for-woocommerce' ),
            'attributes'            => __( 'Item Attributes', 'woosa-bol-com-for-woocommerce' ),
            'parent_item_colon'     => __( 'Parent Item:', 'woosa-bol-com-for-woocommerce' ),
            'all_items'             => __( 'Returns', 'woosa-bol-com-for-woocommerce' ),
            'add_new_item'          => __( 'Add new Return', 'woosa-bol-com-for-woocommerce' ),
            'add_new'               => __( 'Add return', 'woosa-bol-com-for-woocommerce' ),
            'new_item'              => __( 'New return', 'woosa-bol-com-for-woocommerce' ),
            'edit_item'             => __( 'Edit return', 'woosa-bol-com-for-woocommerce' ),
            'update_item'           => __( 'Update return', 'woosa-bol-com-for-woocommerce' ),
            'view_item'             => __( 'View return', 'woosa-bol-com-for-woocommerce' ),
            'view_items'            => __( 'View returns', 'woosa-bol-com-for-woocommerce' ),
            'search_items'          => __( 'Search returns', 'woosa-bol-com-for-woocommerce' ),
            'not_found'             => __( 'Not found', 'woosa-bol-com-for-woocommerce' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'woosa-bol-com-for-woocommerce' ),
            'featured_image'        => __( 'Featured Image', 'woosa-bol-com-for-woocommerce' ),
            'set_featured_image'    => __( 'Set featured image', 'woosa-bol-com-for-woocommerce' ),
            'remove_featured_image' => __( 'Remove featured image', 'woosa-bol-com-for-woocommerce' ),
            'use_featured_image'    => __( 'Use as featured image', 'woosa-bol-com-for-woocommerce' ),
            'insert_into_item'      => __( 'Insert into item', 'woosa-bol-com-for-woocommerce' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'woosa-bol-com-for-woocommerce' ),
            'items_list'            => __( 'returns list', 'woosa-bol-com-for-woocommerce' ),
            'items_list_navigation' => __( 'Returns list navigation', 'woosa-bol-com-for-woocommerce' ),
            'filter_items_list'     => __( 'Filter returns list', 'woosa-bol-com-for-woocommerce' ),
         ),
         'public'              => false,
         'show_ui'             => true,
         'show_in_menu'        => 'edit.php?post_type=bol_invoice',
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