<?php
/**
 * General Settings tab
 *
 * @since 1.1.1
 */

namespace Woosa\Bol;


class Settings_Tools extends Abstract_Settings{



   /**
    * Constructor.
    */
   public function __construct() {

		$this->id    = 'tools';
		$this->label = __( 'Tools', 'woosa-bol-com-for-woocommerce' );

      add_action('woocommerce_admin_field_' . PREFIX . '_tools_content', array($this, 'output_tools_content'));

      self::run_action();

		parent::__construct();
   }



	/**
	 * Get settings array.
	 *
    * @since 1.1.1
	 * @return array
	 */
	public function get_settings() {

		$settings = [
         [
            'title' => '',
            'type'  => 'title',
         ],
         [
            'title' => '',
            'id'    => PREFIX . '_tools_content',
            'type'  => PREFIX . '_tools_content',
         ],
         [
            'type'  => 'sectionend',
         ],
      ];

		return apply_filters( PREFIX . '_get_settings_' . $this->id, $settings );
   }



   /**
    * Runs the tool action
    *
    * @since 1.1.1
    * @return void
    */
   public static function run_action(){

      if( ! wp_verify_nonce( Utility::rgar($_GET, '_wpnonce'), 'wsa-nonce' ) ) return;

      //delete cache
      if( 'yes' === Utility::rgar($_GET, 'clear_cache') ){

         delete_transient(PREFIX.'_recheck_processes');
         delete_transient(PREFIX.'_plugin_checked');

         Utility::show_notice(__('Caching data has been cleared.', 'woosa-bol-com-for-woocommerce'), 'success');
      }

   }



   /**
    * Outputs the page content
    *
    * @since 1.1.1
    * @return string
    */
   public function output_tools_content(){
      ?>

      <div>
         <h3><?php _e('Clear cache', 'woosa-bol-com-for-woocommerce');?></h3>
         <p><em><?php _e('This tool will clear all caching data which is stored in this moment.', 'woosa-bol-com-for-woocommerce');?></em></p>
         <p><a href="<?php echo admin_url('edit.php?post_type=bol_invoice&page=bol-settings&tab=tools&clear_cache=yes&_wpnonce='.wp_create_nonce( 'wsa-nonce' ));?>" class="button"><?php _e('Click to clear', 'woosa-bol-com-for-woocommerce');?></a></p>
         <hr/>
      </div>

      <fieldset class="<?php echo PREFIX;?>-fieldset">
         <legend><?php _e('Available in PRO Version', 'woosa-bol-com-for-woocommerce');?></legend>
         <div>
            <h3><?php _e('Clear background process', 'woosa-bol-com-for-woocommerce');?></h3>
            <p><em><?php _e('This tool will clear all background processes which are running (or got stuck) in this moment such as: importing orders/invoices/returns or syncronizing product stock', 'woosa-bol-com-for-woocommerce');?></em></p>
            <p><button type="button" disabled="disabled" class="button"><?php _e('Click to clear', 'woosa-bol-com-for-woocommerce');?></button></p>
            <hr/>
         </div>
         <div>
            <h3><?php _e('Reset cron jobs', 'woosa-bol-com-for-woocommerce');?></h3>
            <p><em><?php _e('This tool will reset all WP cron jobs such as: importing orders/invoices/returns and syncronizing product stock', 'woosa-bol-com-for-woocommerce');?></em></p>
            <p><button type="button" disabled="disabled" class="button"><?php _e('Click to reset', 'woosa-bol-com-for-woocommerce');?></button></p>
            <hr/>
         </div>
      </fieldset>

      <p>
         <a class="button button-primary button-large" href="https://www.woosa.nl/product/bol-woocommerce-plugin/" target="_blank"><?php _e('Let\'s get the PRO version', 'woosa-bol-com-for-woocommerce');?></a>
      </p>

      <style>
      p.submit{
         display: none;
      }
      </style>

      <?php
   }

}