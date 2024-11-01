<?php
/**
 * Accounts Settings tab
 *
 * @since 1.1.1
 */

namespace Woosa\Bol;


class Settings_Account extends Abstract_Settings{



   /**
    * Constructor.
    *
    * @since 1.1.1
    */
   public function __construct() {

		$this->id    = apply_filters(PREFIX . '_account_tab_id', 'account');
		$this->label = apply_filters(PREFIX .'_account_tab_label', __( 'Account', 'woosa-bol-com-for-woocommerce' ));

      add_action('woocommerce_admin_field_' . PREFIX . '_multiple_accounts', array($this, 'output_multiple_accounts'));

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
            'title' => apply_filters(PREFIX . '_account_tab_section_title', __('Bol.com account', 'woosa-bol-com-for-woocommerce')),
            'type'  => 'title',
            'desc'  => '',
         ],
         [
            'title' => '',
            'id'    => PREFIX . '_multiple_accounts',
            'type'  => PREFIX . '_multiple_accounts',
         ],
         [
            'type'  => 'sectionend',
         ],
      ];

		return apply_filters( PREFIX . '_get_settings_' . $this->id, $settings );
   }



   /**
    * Save settings.
    *
    * @since 1.1.1
    */
   public function save(){

      self::save_extra_fields($_POST);

      parent::save();

   }



   /**
    * Saves extra/custom fields
    *
    * @since 1.1.2 - fix empty option value
    * @since 1.1.1
    * @return void
    */
   private function save_extra_fields($payload){

      $key = PREFIX . '_accounts';
      $default_account = [
         Accounts::generate_id('1') => Accounts::default_account()
      ];

      if(isset($payload[$key])){

         $current = get_option($key);
         $current = empty($current) ? $default_account : $current;
         $new = Utility::rgar($payload, $key);

         update_option($key, array_replace_recursive($current, $new));
      }
   }



   /**
    * Outpus the list of accounts
    *
    * @since 1.1.1
    * @return string
    */
   public function output_multiple_accounts(){
      ?>
      <tr>
         <td class="forminp" style="padding: 0px;">
            <div class="<?php echo PREFIX;?>-wrap-boxes">

            <?php
            $index = 0;

            foreach(Accounts::get_accounts() as $account_id => $item):

               $account = new API($account_id);

               $publish_products = Utility::rgar($item, 'publish_products', 'yes');

               $show_order_commission = Utility::rgar($item, 'show_order_commission', 'no');
               $excl_vat = Utility::rgar($item, 'excl_vat', 'no');
               ?>

               <div class="<?php echo PREFIX;?>-white-box">

                  <input type="hidden" name="<?php echo PREFIX;?>_accounts[<?php echo $account_id;?>][account_id]" value="<?php echo esc_attr( $account_id );?>">

                  <table>
                     <?php if( Core::has_addon('woosa-bol-multi-account') ):?>
                        <tr>
                           <th colspan="2" style="padding: 0;">
                              <h3 id="<?php echo esc_attr( $account_id );?>"><?php echo Accounts::get_name($index+1);?></h3>
                           </td>
                        </tr>
                     <?php endif;?>
                     <tr>
                        <th colspan="2" style="font-weight: normal;">
                           <b><?php _e('Status:', 'woosa-bol-com-for-woocommerce');?></b> <?php $account->render_status();?>
                        </th>
                     </tr>
                     <tr>
                        <th><?php _e('Client ID', 'woosa-bol-com-for-woocommerce');?></th>
                        <td>
                           <input type="text" name="<?php echo PREFIX;?>_accounts[<?php echo esc_attr( $account_id );?>][client_api_id]" value="<?php echo esc_attr( $item['client_api_id'] );?>">
                        </td>
                     </tr>
                     <tr>
                        <th><?php _e('Client Secret', 'woosa-bol-com-for-woocommerce');?></th>
                        <td>
                           <input type="password" name="<?php echo PREFIX;?>_accounts[<?php echo esc_attr( $account_id );?>][client_api_secret]" value="<?php echo esc_attr( $item['client_api_secret'] );?>">
                        </td>
                     </tr>
                     <tr>
                        <th>
                           <label><?php _e('Publish products', 'woosa-bol-com-for-woocommerce');?> <?php echo wc_help_tip(__('Choose whether or not to publish products in this account.', 'woosa-bol-com-for-woocommerce'));?></label>
                        </th>
                        <td>
                           <label><input type="radio" name="<?php echo PREFIX;?>_accounts[<?php echo esc_attr( $account_id );?>][publish_products]" value="yes" <?php checked($publish_products, 'yes');?> > <?php _e('Yes', 'woosa-bol-com-for-woocommerce');?></label>&nbsp;&nbsp;
                           <label><input type="radio" name="<?php echo PREFIX;?>_accounts[<?php echo esc_attr( $account_id );?>][publish_products]" value="no" <?php checked($publish_products, 'no');?> > <?php _e('No', 'woosa-bol-com-for-woocommerce');?></label>&nbsp;&nbsp;
                        </td>
                     </tr>
                     <tr>
                        <td colspan="2" style="padding:0;">
                           <fieldset class="<?php echo PREFIX;?>-fieldset">
                              <legend><?php _e('Available in PRO Version', 'woosa-bol-com-for-woocommerce');?></legend>
                              <table>
                                 <tr>
                                    <th>
                                       <label><?php _e('Import orders', 'woosa-bol-com-for-woocommerce');?> <?php echo wc_help_tip(__('Choose whether or not to import orders from this account. Interval: 15 minutes', 'woosa-bol-com-for-woocommerce'));?></label>
                                    </th>
                                    <td>
                                       <label><input type="radio" disabled="disabled" value="yes"> <?php _e('Yes', 'woosa-bol-com-for-woocommerce');?></label>&nbsp;&nbsp;
                                       <label><input type="radio" disabled="disabled" value="no" checked > <?php _e('No', 'woosa-bol-com-for-woocommerce');?></label>&nbsp;&nbsp;
                                    </td>
                                 </tr>
                                 <tr>
                                    <th>
                                       <label><?php _e('Import invoices', 'woosa-bol-com-for-woocommerce');?> <?php echo wc_help_tip(__('Choose whether or not to import invoices from this account. Interval: monthly', 'woosa-bol-com-for-woocommerce'));?></label>
                                    </th>
                                    <td>
                                       <label><input type="radio" disabled="disabled" value="yes" <?php checked($import_invoices, 'yes');?> > <?php _e('Yes', 'woosa-bol-com-for-woocommerce');?></label>&nbsp;&nbsp;
                                       <label><input type="radio" disabled="disabled" checked value="no" <?php checked($import_invoices, 'no');?> > <?php _e('No', 'woosa-bol-com-for-woocommerce');?></label>&nbsp;&nbsp;
                                    </td>
                                 </tr>
                                 <tr>
                                    <th>
                                       <label><?php _e('Import returns', 'woosa-bol-com-for-woocommerce');?> <?php echo wc_help_tip(__('Choose whether or not to import returns from this account. Interval: daily.', 'woosa-bol-com-for-woocommerce'));?></label>
                                    </th>
                                    <td>
                                       <label><input type="radio" disabled="disabled" value="yes" <?php checked($import_returns, 'yes');?> > <?php _e('Yes', 'woosa-bol-com-for-woocommerce');?></label>&nbsp;&nbsp;
                                       <label><input type="radio" disabled="disabled" checked value="no" <?php checked($import_returns, 'no');?> > <?php _e('No', 'woosa-bol-com-for-woocommerce');?></label>&nbsp;&nbsp;
                                    </td>
                                 </tr>
                                 <tr>
                                    <th>
                                       <label><?php _e('Display commission', 'woosa-bol-com-for-woocommerce');?><?php echo wc_help_tip(__('Choose whether or not to show bol.com commission for imported orders', 'woosa-bol-com-for-woocommerce'));?></label>
                                    </th>
                                    <td class="forminp">
                                       <label><input type="radio" disabled="disabled" value="yes" <?php checked($show_order_commission, 'yes');?> > <?php _e('Yes', 'woosa-bol-com-for-woocommerce');?></label>&nbsp;&nbsp;
                                       <label><input type="radio" disabled="disabled" checked value="no" <?php checked($show_order_commission, 'no');?>> <?php _e('No', 'woosa-bol-com-for-woocommerce');?></label>
                                    </td>
                                 </tr>
                                 <tr>
                                    <th>
                                       <label><?php _e('Exclude VAT', 'woosa-bol-com-for-woocommerce');?><?php echo wc_help_tip(__('Choose whether or not to exclude VAT calculation for imported order', 'woosa-bol-com-for-woocommerce'));?></label>
                                    </th>
                                    <td class="forminp">
                                       <label><input type="radio" disabled="disabled" value="yes" <?php checked($excl_vat, 'yes');?> > <?php _e('Yes', 'woosa-bol-com-for-woocommerce');?></label>&nbsp;&nbsp;
                                       <label><input type="radio" disabled="disabled" checked value="no" <?php checked($excl_vat, 'no');?>> <?php _e('No', 'woosa-bol-com-for-woocommerce');?></label>
                                    </td>
                                 </tr>
                              </table>
                           </fieldset>
                        </td>
                     </tr>
                  </table>

                  <?php if($index > 0):?>
                     <button type="button" class="button button-small" data-<?php echo PREFIX;?>-account-action="remove" data-<?php echo PREFIX;?>-account-key="<?php echo esc_attr( $account_id );?>" title="<?php _e('Remove account', 'woosa-bol-com-for-woocommerce');?>"><?php _e('Remove', 'woosa-bol-com-for-woocommerce');?></button>
                  <?php endif;?>

               </div>

            <?php $index++; endforeach;?>

            <?php do_action(PREFIX . '_after_account_settings');?>
            </div>
         </td>
      </tr>
      <?php
   }


}