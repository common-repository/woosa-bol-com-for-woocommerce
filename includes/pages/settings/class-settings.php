<?php
/**
 * This is responsible for admin settings page
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;


class Settings{

   /**
    * Setting pages.
    *
    * @var array
    */
   private $tabs = [];


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

      add_action('admin_init', [$this, 'init_tabs']);

      add_filter('woocommerce_screen_ids', [__CLASS__, 'set_wc_screen_ids'] );

      add_action('admin_menu', [$this, 'menu_page']);
      add_action('admin_init', [__CLASS__, 'authorize_app']);

   }



   /**
    * Add settings screen to Woocommerce list to benefits of its scripts/styles
    *
    * @since 1.0.3
    * @param array $screen
    * @return void
    */
   public static function set_wc_screen_ids( $screen ){

      $screen[] = 'bol_invoice_page_bol-settings';

      return $screen;
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
         __('Settings', 'woosa-bol-com-for-woocommerce'),
         __('Settings', 'woosa-bol-com-for-woocommerce'),
         'manage_options',
         'bol-settings',
         [$this, 'output_page']
      );
   }



   /**
    * Output admin menu page
    *
    * @since 1.1.1
    * @return void
    */
   public function output_page() {

      global $current_tab, $current_section;

      if( !current_user_can( 'manage_options' ) )  {
         wp_die( __( 'You do not have sufficient permissions to access this page.', 'woosa-bol-com-for-woocommerce') );
      }

      $current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) );
      $current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) );


      if ( ! empty( $_POST['save'] ) ){
			self::save();
		}

      $tabs = apply_filters( PREFIX . '_settings_tabs_array', array() );

      echo Utility::get_tpl(PLUGIN_DIR . '/templates/settings-page.php', [
         'current_tab' => $current_tab,
         'tabs' => $tabs,
      ]);
   }



   /**
    * Gets an setting option.
    *
    * Returns a setting option. It has backward compatibility for old settings.
    *
    * @since 1.1.1
    * @param string $key
    * @param false|string $default
    * @param boolean $legacy
    * @return string
    */
   public static function get_option($key, $default = false, $legacy = false){

      $option = get_option(PREFIX . "_{$key}", $default);

      if($legacy){

         $exist = get_option(PREFIX . "_{$key}");

         if($exist === false){
            $settings = array_filter((array) get_option(PREFIX.'_settings'));

            if(isset($settings[$key])) return $settings[$key];
         }
      }

      return $option;
   }



   /**
    * Triggers actions to save settings
    *
    * @since 1.1.1
    */
   public static function save(){

      global $current_tab;

      do_action( PREFIX . '_settings_save_' . $current_tab );
      do_action( PREFIX . '_update_options_' . $current_tab );

      \WC_Admin_Settings::add_message( __( 'Your settings have been saved.', 'woosa-bol-com-for-woocommerce' ) );
   }



   /**
    * Authorize website to use Bol.com app
    *
    * @since 1.1.1
    * @return void
    */
   public static function authorize_app(){

      if( isset($_GET['account_id']) && isset($_GET['account_action']) && wp_verify_nonce(Utility::rgar($_GET, '_wpnonce'), PREFIX.'_account_action') ){

         $account = new API( Utility::rgar($_GET, 'account_id', Accounts::generate_id('1')) );
         $action  = Utility::rgar($_GET, 'account_action');
         $tab = Core::has_addon('woosa-bol-multi-account') ? 'accounts' : 'account';

         if('authorize' === $action){

            $authorized = $account->authorize();
            $auth_result = PREFIX.'_unauthorized';

            if($authorized){
               $auth_result = PREFIX.'_authorized';
            }

            wp_redirect(add_query_arg(
               [
                  'tab' => $tab,
                  'action' => $auth_result,
               ],
               PLUGIN_SETTINGS_URL
            ));

            exit;
         }


         if('revoke' === $action){

            $revoked = $account->revoke();
            $rev_result = PREFIX.'_unremoved';

            if($revoked){
               $rev_result = PREFIX.'_revoked';
            }

            wp_redirect(add_query_arg(
               [
                  'tab' => $tab,
                  'action' => $rev_result,
               ],
               PLUGIN_SETTINGS_URL
            ));
            exit;
         }

      }


      if(Utility::rgar($_GET, 'action') === PREFIX.'_authorized'){
         Utility::show_notice(__('The authorization has been done successfully!', 'woosa-bol-com-for-woocommerce'), 'success');
      }

      if(Utility::rgar($_GET, 'action') === PREFIX.'_unauthorized'){
         Utility::show_notice(__('The authorization has failed, please make sure you provided a valid Client ID and Client Secret!', 'woosa-bol-com-for-woocommerce'));
      }

      if(Utility::rgar($_GET, 'action') === PREFIX.'_revoked'){
         Utility::show_notice(__('The authorization has been revoked.', 'woosa-bol-com-for-woocommerce'), 'success');
      }

      if(Utility::rgar($_GET, 'action') === PREFIX.'_unremoved'){
         Utility::show_notice(__('The authorization couldn\'t be revoked.', 'woosa-bol-com-for-woocommerce'));
      }
   }



   /**
    * Initiates settings tabs
    *
    * @since 1.1.1
    * @return void
    */
   public function init_tabs(){

      new Settings_General();
      new Settings_Account();
      new Settings_Tools();

   }


}