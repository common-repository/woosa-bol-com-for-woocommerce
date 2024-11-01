<?php
/**
 * Main class which sets all together
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;

use Dompdf\Dompdf;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Core{


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

      require_once PLUGIN_DIR . '/vendor/autoload.php';

      //check for dependencies
      if(self::has_dependencies() === false){
         return;
      }


      //enqueue css and js files
      Assets::enqueue();

      //--- AJAX calls
      AJAX::init();

      //--- extend Woo products
      Product::instance();

      //--- invoices
      Invoices::instance();

      //--- returns
      Returns::instance();

      //--- settings page
      Settings::instance();

      //--- Addons page
      Addons::instance();

      //--- hooks
      self::run_hooks();

   }



   /**
    * Runs general hooks.
    *
    * @since 1.0.0
    * @return void
    */
    public static function run_hooks(){

      add_action('admin_init', [__CLASS__, 'init_plugin_action_links']);
      add_action('admin_head',[__CLASS__, 'show_admin_notices']);

      add_action('init', [Handle_Process_Status::class, 'handle']);

      add_action('manage_posts_extra_tablenav', [__CLASS__, 'maybe_render_blank_state']);

      add_action('upgrader_process_complete', [__CLASS__, 'on_update'], 10, 2);

      add_filter('is_protected_meta', [__CLASS__, 'maybe_show_protected_meta'], 10, 3);

   }



   /**
    * Shows notice messages
    *
    * @since 1.0.7 - added warning message for cURL version
    * @since 1.0.0
    * @return void
    */
   public static function show_admin_notices(){

      $curl_version = '7.52.1';


      //publish limitation warning
      if( count(get_option(PREFIX . '_published_products', [])) >= self::$publish_limit){
         Utility::show_important_notice(sprintf(
            __('You have reached the maximum limit (%s) of published products, please check our PRO version of %s for unlimited products and other awesome features!', 'woosa-bol-com-for-woocommerce'),
            self::$publish_limit,
            '<a href="https://www.woosa.nl/product/bol-woocommerce-plugin/" target="_blank">'.PLUGIN_NAME.'</a>'
         ), 'warning');
      }


      //cURL warning
      if(version_compare(Utility::rgar(curl_version(), 'version'), $curl_version, '<')){
         Utility::show_notice(sprintf(
            __('Your server must have at least cURL %s, please contact your hosting provider to upgrade this, otherwise this plugin might not work properly!', 'woosa-bol-com-for-woocommerce'),
            '<code>'.$curl_version.'</code>'
         ), 'warning');
      }


      //show notice message for active processes
      foreach(self::background_processes() as $key => $item){
         if($item['started'] !== false){
            return Utility::show_notice(sprintf(
               $item['message'].' %s',
               self::progress_bar($key)
            ), 'warning');
         }
      }

   }



   /**
    * Renders PRO version message on certain post types.
    *
    * @since 1.1.2
    * @param string $which
    * @return string
    */
   public static function maybe_render_blank_state( $which ) {

      global $post_type;

      if ( ('bol_invoice' === $post_type || 'bol_return' === $post_type ) && 'bottom' === $which ) {

         ?>
         <div class="bol-blank-state">
            <h2><?php _e('Available in PRO version', 'woosa-bol-com-for-woocommerce');?></h2>
            <p><?php _e('Get the PRO version and enjoy the awesome features and ofcourse unlimited support & updates!');?></p>
            <a class="button button-primary button-large" href="https://www.woosa.nl/product/bol-woocommerce-plugin/" target="_blank"><?php _e('Let\'s get the PRO version', 'woosa-bol-com-for-woocommerce');?></a>
         </div>
         <?php
         echo '<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub  { display: none; } </style>';
      }

	}



   /**
    * Displays a progress bar with the active process
    *
    * @since 1.0.6
    * @return string
    */
   public static function progress_bar($mode = ''){

      foreach(self::background_processes() as $key => $item){

         if($item['started'] !== false){
            $progress_bar = Utility::rgar($item, 'progress_bar');

            if(is_callable($progress_bar)){
               $percentage = $progress_bar();
               $show_percentage = $percentage > 8 ? $percentage.'%' : '';

               ob_start();
               ?>

               <div class="<?php echo PREFIX.'-progress-bar';?>">
                  <div style="width: <?php echo $percentage?>%" class="<?php echo PREFIX.'-progress-bar__inner';?>"><span><?php echo $show_percentage?></span></div>
               </div>

               <?php
               return ob_get_clean();
            }
         }

      }

   }



   /**
    * List of background processes
    *
    * @since 1.1.1 - add import returns
    * @since 1.0.6
    * @return array
    */
   public static function background_processes(){

      return [
         'publish_products' => [
            'message' => __('Publishing products...', 'woosa-bol-com-for-woocommerce'),
            'started' => get_option(PREFIX.'_publish_products_started'),
            'clear' => function(){
               delete_option(PREFIX.'_publish_products_started');
               delete_option(PREFIX.'_total_products_to_publish');
               delete_option(PREFIX.'_current_published_product');
               self::delete_old_batches(Product::$publish_products->get_action());
            },
            'progress_bar' => function(){
               $total = get_option(PREFIX.'_total_products_to_publish');
               $current = get_option(PREFIX.'_current_published_product', '0');
               $percentage = floor($current * 100);

               return $percentage > 0 ? floor($percentage / $total) : 0;
            }
         ],
      ];
   }



   /**
    * Hides our metas but it shows them if debug is enabled
    *
    * @since 1.1.0
    * @param bool $protected
    * @param string $meta_key
    * @param string $meta_type
    * @return bool
    */
   public static function maybe_show_protected_meta($protected, $meta_key, $meta_type){

      if(strpos($meta_key, PREFIX.'_') !== false && \WP_DEBUG === false){
         $protected = true;
      }

      return $protected;
   }



   /**
    * Deletes old batches by given unique action
    *
    * @since 1.1.1
    * @param string $action
    * @return void
    */
    protected static function delete_old_batches( $action ) {

      global $wpdb;

      $query = "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s";
      $query = $wpdb->prepare($query, '%' . 'wp_' . $action . '_batch_%');

      $wpdb->query( $query );

      //remove the event as well
      wp_clear_scheduled_hook("wp_{$action}_cron");
   }



   /**
    * Init the action links available in plugins list page
    *
    * @since 1.0.0
    * @return void
    */
   public static function init_plugin_action_links(){

      //add plugin action and meta links
      self::plugin_links(array(
         'actions' => array(
            PLUGIN_SETTINGS_URL => __('Settings', 'woosa-bol-com-for-woocommerce'),
            admin_url('admin.php?page=wc-status&tab=logs') => __('Logs', 'woosa-bol-com-for-woocommerce'),
            admin_url('plugins.php?action='.PREFIX.'_check_updates') => __('Check for Updates', 'woosa-bol-com-for-woocommerce')
         ),
         'meta' => array(
            // '#1' => __('Docs', 'woosa-bol-com-for-woocommerce'),
            // '#2' => __('Visit website', 'woosa-bol-com-for-woocommerce')
         ),
      ));

   }



   /**
    * Add plugin action and meta links
    *
    * @since 1.0.3 - changed to `plugin_action_links_`
    * @since 1.0.0
    * @param array $sections
    * @return void
    */
   public static function plugin_links($sections = array()) {

      //actions
      if(isset($sections['actions'])){

         $actions = $sections['actions'];

         add_filter('plugin_action_links_'.PLUGIN_BASENAME, function($links) use ($actions){

            foreach(array_reverse($actions) as $url => $label){
               $link = '<a href="'.esc_url($url).'">'.$label.'</a>';
               array_unshift($links, $link);
            }

            return $links;

         });
      }

      //meta row
      if(isset($sections['meta'])){

         $meta = $sections['meta'];

         add_filter( 'plugin_row_meta', function($links, $file) use ($meta){

            if(PLUGIN_BASENAME == $file){

               foreach($meta as $url => $label){
                  $link = '<a href="'.esc_url($url).'">'.$label.'</a>';
                  array_push($links, $link);
               }
            }

            return $links;

         }, 10, 2 );
      }

   }



   /**
    * Check whether the given addon is active.
    *
    * @since 1.1.1
    * @param string $slug
    * @return boolean
    */
   public static function has_addon($slug){
      return self::has_dependencies(["{$slug}/{$slug}.php" => $slug], false);
   }



   /**
    * Check whether the required dependencies are met
    * also can show a notice message
    *
    * @since 1.1.1 - fixed issue for multisite installations
    * @since 1.0.0
    * @param array $plugins - an array with `path => name` of the pplugin
    * @param boolean $show_notice
    * @param boolean $wp_die - whether or not to show the message with wp_die()
    * @return boolean
    */
   public static function has_dependencies($plugins = [], $show_notice = true, $wp_die = false){

      $valid = true;
      $plugins = empty($plugins) ? [
         'woocommerce/woocommerce.php' => 'WooCommerce',
      ] : array_filter((array) $plugins);

      $active_plugins = self::get_active_plugins();

      foreach($plugins as $path => $name){

         if(!in_array($path, $active_plugins)){

            $msg = sprintf(
               __('This plugin requires %s plugin to be installed and active!', 'woosa-bol-com-for-woocommerce'),
               "<b>{$name}</b>"
            );

            if($wp_die){
               wp_die($msg);
            }

            if($show_notice){
               Utility::show_notice($msg, 'error');
            }

            $valid = false;
         }
      }

      return $valid;

   }



   /**
    * Get active plugins
    *
    * @since 1.1.1
    * @return array
    * */
   public static function get_active_plugins(){

      $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

      if (is_multisite()) {
         $active_sitewide_plugins = get_site_option('active_sitewide_plugins');

         foreach ($active_sitewide_plugins as $path => $item) {
            $active_plugins[] = $path;
         }
      }

      return $active_plugins;
   }



   /**
    * Clears all caches
    *
    * @since 1.0.7
    * @return void
    */
   public static function clear_all_caches(){

      //clear WP cache
      wp_cache_flush();

      //clear WP Rocket cache
      if( function_exists( 'rocket_clean_domain' ) ) {
         rocket_clean_domain();
      }

   }



   /**
    * Run on plugin activation
    *
    * @since 1.0.0
    * @return void
    */
   public static function on_activation(){

      if(version_compare(phpversion(), '7.1', '<')){
         wp_die(sprintf(
            __('Your server must have at least PHP 7.1! Please upgrade! %sGo back%s', 'woosa-bol-com-for-woocommerce'),
            '<a href="'.esc_url(admin_url('plugins.php')).'">',
            '</a>'
         ));
      }

      if(version_compare(get_bloginfo('version'), '4.5', '<')){
         wp_die(sprintf(
            __('You need at least Wordpress 4.5! Please upgrade! %sGo back%s', 'woosa-bol-com-for-woocommerce'),
            '<a href="'.esc_url(admin_url('plugins.php')).'">',
            '</a>'
         ));
      }

      self::has_dependencies([], false, true);

      self::clear_all_caches();

   }



   /**
    * Run on plugin deactivation
    *
    * @since 1.0.0
    * @return void
    */
   public static function on_deactivation(){

      self::clear_all_caches();

   }



   /**
    * Run on plugin update process
    *
    * @since 1.0.2
    * @param object $upgrader_object
    * @param array $options
    * @return void
    */
   public static function on_update( $upgrader_object, $options ) {

      if($options['action'] == 'update' && $options['type'] == 'plugin' ){

         foreach($options['plugins'] as $plugin){

            if($plugin == PLUGIN_BASENAME){

            }
         }
      }
   }



   /**
    * Run when plugin is deleting
    *
    * @since 1.0.0
    * @return void
    */
   public static function on_uninstall(){

      //settings
      delete_option(PREFIX.'_settings');
   }


   /**
    * The maximum number of published offers.
    *
    * @var integer
    */
   public static $publish_limit = 5;


}
Core::instance();