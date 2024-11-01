<?php
/**
 * Products Settings tab
 *
 * @since 1.1.1
 */

namespace Woosa\Bol;


class Settings_General extends Abstract_Settings{



   /**
    * Constructor.
    *
    * @since 1.1.1
    */
   public function __construct() {

		$this->id    = 'general';
      $this->label = __( 'General', 'woosa-bol-com-for-woocommerce' );

      add_action('woocommerce_admin_field_' . PREFIX . '_fieldlist', array($this, 'output_fieldlist'));
      add_action('woocommerce_admin_field_' . PREFIX . '_use_wc_price', array($this, 'output_use_wc_price'));

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
            'title' => __('General Settings', 'woosa-bol-com-for-woocommerce'),
            'type'  => 'title',
            'desc'  => '',
         ],
         [
            'default' => 'default',
            'id'    => PREFIX . '_fieldlist',
            'type'  => PREFIX . '_fieldlist',
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
    * @since 1.1.1
    * @return void
    */
   private function save_extra_fields($payload){

      $extra = [
         PREFIX . '_ean_custom_field_name',
         PREFIX . '_price_addition',
      ];

      foreach($extra as $option){
         if(isset($payload[$option])){
            update_option($option, Utility::rgar($payload, $option));
         }
      }
   }



   /**
    * Ouputs EAN source field
    *
    * @since 1.1.1
    * @return string
    */
   public static function output_fieldlist($value){

      ?>
      <tr>
         <td colsapn="2" style="padding: 0;">
            <fieldset class="<?php echo PREFIX;?>-fieldset <?php echo PREFIX;?>-fieldset--maxwith">
               <legend><?php _e('Available in PRO Version', 'woosa-bol-com-for-woocommerce');?></legend>
               <table>
                  <tr>
                     <th><label><?php _e('EAN source', 'woosa-bol-com-for-woocommerce'); ?> <?php echo wc_help_tip(__('Choose the source of the product EAN code.', 'woosa-bol-com-for-woocommerce')); ?></label></th>
                     <td class="forminp">
                        <select disabled="disabled"  data-extra-fields="yes">
                           <option value="default"><?php _e('Default', 'woosa-bol-com-for-woocommerce');?></option>
                           <option value="custom_field"><?php _e('Use a specific custom field', 'woosa-bol-com-for-woocommerce');?></option>
                           <option value="sku"><?php _e('Use the product SKU', 'woosa-bol-com-for-woocommerce');?></option>
                        </select>
                        <p data-extra-field-for="custom_field" style="display:none;">
                           <label style="font-style:italic; font-size:12px;"><?php _e('Specify the field name', 'woosa-bol-com-for-woocommerce');?></label><br/>
                           <input type="text" value="">
                        </p>
                     </td>
                  </tr>
                  <tr>
                     <th><label><?php _e('Use WooCommerce price', 'woosa-bol-com-for-woocommerce'); ?> <?php echo wc_help_tip(__('Choose whether or not to use the default WooCommerce product price at publishing on bol.com.', 'woosa-bol-com-for-woocommerce')); ?></label></th>
                     <td class="forminp">
                        <label>
                           <input type="hidden" value="no">
                           <input type="checkbox" disabled="disabled" data-extra-fields="yes" value="yes"> <?php _e('Yes', 'woosa-bol-com-for-woocommerce');?>
                        </label>
                        <p data-extra-field-for="yes" style="display:none;">
                           <label style="font-style:italic; font-size:12px;"><?php _e('Adjust the price (percentage/fixed amount)', 'woosa-bol-com-for-woocommerce');?></label><br/>
                           <input type="text" value="" placeholder="e.g. 10% or 10.00">
                        </p>
                     </td>
                  </tr>
                  <tr>
                     <th><label><?php echo sprintf(__('Shipping cost (%s)', 'woosa-bol-com-for-woocommerce'), get_woocommerce_currency_symbol()); ?> <?php echo wc_help_tip(__('The shipping cost that will be added to the product price at publishing on bol.com.', 'woosa-bol-com-for-woocommerce')); ?></label></th>
                     <td class="forminp">
                        <label>
                           <input type="number" disabled="disabled"value="0.00">
                        </label>
                     </td>
                  </tr>
                  <tr>
                     <th><label><?php _e('Stock managed by', 'woosa-bol-com-for-woocommerce'); ?> <?php echo wc_help_tip(__('Choose whether the retailer manages the stock levels or bol.com', 'woosa-bol-com-for-woocommerce')); ?></label></th>
                     <td class="forminp">
                        <select disabled="disabled">
                           <option value="FBR"><?php _e('Retailer', 'woosa-bol-com-for-woocommerce');?></option>
                           <option value="FBB"><?php _e('Bol.com', 'woosa-bol-com-for-woocommerce');?></option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <th><label><?php _e('Delivery time', 'woosa-bol-com-for-woocommerce'); ?> <?php echo wc_help_tip(__('Choose the delivery promise', 'woosa-bol-com-for-woocommerce')); ?></label></th>
                     <td class="forminp">
                        <select disabled="disabled">
                           <?php foreach(API::delivery_codes() as $key => $item):?>
                              <option value="<?php echo esc_attr( $key );?>"><?php echo $item;?></option>
                           <?php endforeach;?>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <th><label><?php _e('Fulfill by', 'woosa-bol-com-for-woocommerce'); ?> <?php echo wc_help_tip(__('Specifies whether the shipment will be fulfilled by the retailer or bol.com', 'woosa-bol-com-for-woocommerce')); ?></label></th>
                     <td class="forminp">
                        <select disabled="disabled">
                           <option value="FBR"><?php _e('Retailer', 'woosa-bol-com-for-woocommerce');?></option>
                           <option value="FBB"><?php _e('Bol.com', 'woosa-bol-com-for-woocommerce');?></option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <th><label><?php _e('Condition', 'woosa-bol-com-for-woocommerce'); ?> <?php echo wc_help_tip(__('Specifies whether the shipment will be fulfilled by the retailer or bol.com', 'woosa-bol-com-for-woocommerce')); ?></label></th>
                     <td class="forminp">
                        <select disabled="disabled">
                           <?php foreach(API::condition_names() as $key => $item):?>
                              <option value="<?php echo esc_attr( $key );?>"><?php echo $item;?></option>
                           <?php endforeach;?>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <th><label><?php _e('Address format', 'woosa-bol-com-for-woocommerce'); ?> <?php echo wc_help_tip(__('Choose the address format for the imported orders', 'woosa-bol-com-for-woocommerce')); ?></label></th>
                     <td class="forminp">
                        <select disabled="disabled">
                           <option value="format_1">123AB Hillside Avenue</option>
                           <option value="format_2">Hillside Avenue 123AB</option>
                        </select>
                     </td>
                  </tr>
               </table>
            </fieldset>

            <br/><p>
               <a class="button button-primary button-large" href="https://www.woosa.nl/product/bol-woocommerce-plugin/" target="_blank"><?php _e('Let\'s get the PRO version', 'woosa-bol-com-for-woocommerce');?></a>
            </p>

            <style>
            p.submit{
               display: none;
            }
            </style>
         </td>
      </tr>
      <?php

   }

}