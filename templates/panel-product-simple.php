<?php
/**
 *
 * @since 1.0.0
 */


namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


$fbb_hide = 'FBB' === $fulfilment_type ? 'display: none;' : 'display: block;';
?>

<?php
/**
 * @since 1.1.1
 */
do_action(PREFIX . '_start_product_settings_tab', $product);
?>

<div class="options_group">

   <?php Accounts::render_offer_error($product->get_id()); ?>

   <?php Accounts::render_offer_status($product->get_id())?>

   <?php Product::render_dropdown_actions($product->get_id()); ?>

   <p class="form-field">
      <label title="<?php _e('Required', 'woosa-bol-com-for-woocommerce');?>"><?php printf(__('Price (%s)', 'woosa-bol-com-for-woocommerce'), get_woocommerce_currency_symbol());?> <span style="color: #a30000;">*</span></label>
      <input type="text" class="short" name="<?php echo PREFIX;?>_offer[price]" value="<?php echo Utility::wc_format_price($price);?>" />
      <?php echo wc_help_tip('The price for this product that you want to publish with', 'woosa-bol-com-for-woocommerce');?>
      <br/>
      <?php Accounts::render_offer_reduction($product->get_id()); ?>
   </p>

   <p class="form-field">
      <label><?php printf(__('Shipping cost (%s)', 'woosa-bol-com-for-woocommerce'), get_woocommerce_currency_symbol());?></label>
      <input type="text" class="short" name="<?php echo PREFIX;?>_offer[shipping_cost]" value="<?php echo Utility::wc_format_price($shipping_cost);?>" />
      <?php echo wc_help_tip('The shipping cost you want to add to the total price of this product', 'woosa-bol-com-for-woocommerce');?>
   </p>
   <p class="form-field">
      <label title="<?php _e('Required', 'woosa-bol-com-for-woocommerce');?>"><?php _e('EAN code', 'woosa-bol-com-for-woocommerce');?> <span style="color: #a30000;">*</span></label>
      <input type="text" class="short" name="<?php echo PREFIX;?>_offer[ean_code]" value="<?php echo esc_attr( $ean_code );?>" />
      <?php echo wc_help_tip('The EAN number associated with this product', 'woosa-bol-com-for-woocommerce');?>
   </p>
   <p class="form-field">
      <label><?php _e('Reference code', 'woosa-bol-com-for-woocommerce');?></label>
      <input type="text" class="short" name="<?php echo PREFIX;?>_offer[reference_code]" value="<?php echo esc_attr( $reference_code );?>" maxlength="20" />
      <?php echo wc_help_tip('A reference code that helps you identify this product on bol.com (max. 20 characters)', 'woosa-bol-com-for-woocommerce');?>
   </p>

   <p class="form-field" style="<?php echo $fbb_hide;?>" data-fulfilment_type-target="fbr">
      <label title="<?php _e('Required', 'woosa-bol-com-for-woocommerce');?>"><?php _e('Stock quantity', 'woosa-bol-com-for-woocommerce');?> <span style="color: #a30000;">*</span></label>
      <input type="hidden" name="<?php echo PREFIX;?>_offer[stock_amount]" value="<?php echo esc_attr( $stock_amount );?>"/>
      <?php echo $stock_amount;?> - <a href="#" data-set-stock="field" data-target=".inventory_options a"><?php _e('Set quantity', 'woosa-bol-com-for-woocommerce');?></a>
      <?php echo wc_help_tip(__('Whenever you set this it will also update automatically the offer stock in bol.com', 'woosa-bol-com-for-woocommerce')); ?>
   </p>
   <p class="form-field" style="<?php echo $fbb_hide;?>" data-fulfilment_type-target="fbr">
      <label><?php _e('Stock managed by', 'woosa-bol-com-for-woocommerce');?></label>
      <select class="short" name="<?php echo PREFIX;?>_offer[stock_managed_by_retailer]">
         <option value="true" <?php selected($stock_managed_by_retailer, 'true');?>><?php _e('Retailer', 'woosa-bol-com-for-woocommerce');?></option>
         <option value="false" <?php selected($stock_managed_by_retailer, 'false');?>><?php _e('Bol.com', 'woosa-bol-com-for-woocommerce');?></option>
      </select>
      <?php echo wc_help_tip(__('Choose whether the retailer manages the stock levels or bol.com', 'woosa-bol-com-for-woocommerce')); ?>
   </p>

   <p class="form-field">
      <label><?php _e('Delivery time', 'woosa-bol-com-for-woocommerce');?></label>
      <select class="short" name="<?php echo PREFIX;?>_offer[fulfilment_delivery_code]">
         <?php foreach(API::delivery_codes() as $key => $label):?>
            <option value="<?php echo esc_attr( $key );?>" <?php selected($fulfilment_delivery_code, $key);?>><?php echo esc_html( $label );?></option>
         <?php endforeach;?>
      </select>
      <?php echo wc_help_tip(__('Choose the delivery promise', 'woosa-bol-com-for-woocommerce')); ?>
   </p>
   <p class="form-field">
      <label><?php _e('Fulfill by', 'woosa-bol-com-for-woocommerce');?></label>
      <select class="short" name="<?php echo PREFIX;?>_offer[fulfilment_type]" data-additional-fields="fulfilment_type">
         <option value="FBR" <?php selected($fulfilment_type, 'FBR');?>><?php _e('Retailer', 'woosa-bol-com-for-woocommerce');?></option>
         <option value="FBB" <?php selected($fulfilment_type, 'FBB');?>><?php _e('Bol.com', 'woosa-bol-com-for-woocommerce');?></option>
      </select>
      <?php echo wc_help_tip(__('Specifies whether the shipment will be fulfilled by the retailer or bol.com', 'woosa-bol-com-for-woocommerce')); ?>
   </p>
   <p class="form-field">
      <label><?php _e('Condition', 'woosa-bol-com-for-woocommerce');?></label>
      <select class="short" name="<?php echo PREFIX;?>_offer[condition_name]">
         <?php foreach(API::condition_names() as $key => $label):?>
            <option value="<?php echo esc_attr( $key );?>" <?php selected($condition_name, $key);?>><?php echo esc_html( $label );?></option>
         <?php endforeach;?>
      </select>
      <?php echo wc_help_tip(__('Choose the product condition', 'woosa-bol-com-for-woocommerce')); ?>
   </p>

</div>


<?php
/**
 * @since 1.1.1
 */
do_action(PREFIX . '_end_product_settings_tab', $product);
?>

<p>
   <span style="color: #a30000;">*</span> - <i><?php _e('These are required fields, please make sure to set a value for them!', 'woosa-bol-com-for-woocommerce');?></i>
</p>
