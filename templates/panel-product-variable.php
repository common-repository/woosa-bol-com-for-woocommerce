<?php
/**
 *
 * @since 1.0.3
 */


namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

?>

<?php foreach($variations as $item):

   $var_id = $item['variation_id'];
   $variation = new \WC_Product_Variation($var_id);

   $price = get_post_meta($var_id, PREFIX.'_price', true) ?: $variation->get_price();
   $price = Utility::wc_format_price($price);

   $ean_code = get_post_meta($var_id, PREFIX.'_ean_code', true);

   $shipping_cost = get_post_meta($var_id, PREFIX.'_shipping_cost', true);
   $shipping_cost = empty($shipping_cost) ? Settings::get_option('shipping_cost', '0.00', true) : $shipping_cost;
   $shipping_cost = Utility::wc_format_price($shipping_cost);

   $stock_amount = (int) $variation->get_stock_quantity();

   $stock_managed_by_retailer = get_post_meta($var_id, PREFIX.'_stock_managed_by_retailer', true);
   $stock_managed_by_retailer = empty($stock_managed_by_retailer) ? Settings::get_option('stock_managed_by_retailer', 'true', true) : $stock_managed_by_retailer;

   $fulfilment_delivery_code = get_post_meta($var_id, PREFIX.'_fulfilment_delivery_code', true);
   $fulfilment_delivery_code = empty($fulfilment_delivery_code) ? Settings::get_option('fulfilment_delivery_code', '24uurs-23', true) : $fulfilment_delivery_code;

   $fulfilment_type = get_post_meta($var_id, PREFIX.'_fulfilment_type', true);
   $fulfilment_type = empty($fulfilment_type) ? Settings::get_option('fulfilment_type', 'FBR', true) : $fulfilment_type;

   $fbb_hide = 'FBB' === $fulfilment_type ? 'display: none;' : 'display: block;';

   $condition_name = get_post_meta($var_id, PREFIX.'_condition_name', true);
   $condition_name = empty($condition_name) ? Settings::get_option('condition_name', 'NEW', true) : $condition_name;


   if($variation->variation_is_active()):
   ?>

      <div class="<?php echo PREFIX;?>-variation">
         <div class="<?php echo PREFIX;?>-variation__name">
            <?php echo $variation->get_name();?>
            <?php Accounts::render_offer_status($var_id)?>
         </div>

         <div class="<?php echo PREFIX;?>-variation__content">

            <?php Accounts::render_offer_error($var_id); ?>

            <?php
            /**
             * @since 1.1.1
             */
            do_action(PREFIX . '_start_product_settings_tab', $variation);
            ?>

            <div class="options_group">

               <?php Product::render_dropdown_actions($var_id); ?>

               <p class="form-field">
                  <label title="<?php _e('Required', 'woosa-bol-com-for-woocommerce');?>"><?php printf(__('Price (%s)', 'woosa-bol-com-for-woocommerce'), get_woocommerce_currency_symbol());?> <span style="color: #a30000;">*</span></label>
                  <input type="text" class="short" name="<?php echo PREFIX;?>_offer[<?php echo esc_attr( $var_id );?>][price]" value="<?php echo esc_attr( $price );?>" />
                  <?php echo wc_help_tip('The price for this product that you want to publish with', 'woosa-bol-com-for-woocommerce');?>
                  <br/>
                  <?php Accounts::render_offer_reduction($var_id); ?>
               </p>

               <p class="form-field">
                  <label><?php printf(__('Shipping cost (%s)', 'woosa-bol-com-for-woocommerce'), get_woocommerce_currency_symbol());?></label>
                  <input type="text" class="short" name="<?php echo PREFIX;?>_offer[<?php echo esc_attr( $var_id );?>][shipping_cost]" value="<?php echo esc_attr( $shipping_cost );?>" />
                  <?php echo wc_help_tip('The shipping cost you want to add to the total price of this product', 'woosa-bol-com-for-woocommerce');?>
               </p>
               <p class="form-field">
                  <label title="<?php _e('Required', 'woosa-bol-com-for-woocommerce');?>"><?php _e('EAN code', 'woosa-bol-com-for-woocommerce');?> <span style="color: #a30000;">*</span></label>
                  <input type="text" class="short" name="<?php echo PREFIX;?>_offer[<?php echo esc_attr( $var_id );?>][ean_code]" value="<?php echo esc_attr( $ean_code );?>" />
                  <?php echo wc_help_tip('The EAN number associated with this product', 'woosa-bol-com-for-woocommerce');?>
               </p>
               <p class="form-field">
                  <label><?php echo __('Reference code', 'woosa-bol-com-for-woocommerce');?></label>
                  <input type="text" class="short" name="<?php echo PREFIX;?>_offer[<?php echo esc_attr( $var_id );?>][reference_code]" value="<?php echo esc_attr( substr($variation->get_sku(), 0, 20) );?>" maxlength="20" />
                  <?php echo wc_help_tip('A reference code that helps you identify this product on bol.com (max. 20 characters)', 'woosa-bol-com-for-woocommerce');?>
               </p>
               <p class="form-field" style="<?php echo $fbb_hide;?>" data-fulfilment_type-target="fbr">
                  <label><?php _e('Stock quantity', 'woosa-bol-com-for-woocommerce');?></label>
                  <input type="hidden" name="<?php echo PREFIX;?>_offer[<?php echo esc_attr( $var_id );?>][stock_amount]" value="<?php echo esc_attr( $stock_amount );?>"/>
                  <?php echo $stock_amount;?> - <a href="#" data-set-stock="field" data-target=".variations_options a"><?php _e('Set quantity', 'woosa-bol-com-for-woocommerce');?></a>
                  <?php echo wc_help_tip(__('Whenever you set this it will also update automatically the offer stock in bol.com', 'woosa-bol-com-for-woocommerce')); ?>
               </p>
               <p class="form-field" style="<?php echo $fbb_hide;?>" data-fulfilment_type-target="fbr">
                  <label title="<?php _e('Required', 'woosa-bol-com-for-woocommerce');?>"><?php _e('Stock managed by', 'woosa-bol-com-for-woocommerce');?></label>
                  <select class="short" name="<?php echo PREFIX;?>_offer[<?php echo esc_attr( $var_id );?>][stock_managed_by_retailer]">
                     <option value="true" <?php selected($stock_managed_by_retailer, 'true');?>><?php _e('Retailer', 'woosa-bol-com-for-woocommerce');?></option>
                     <option value="false" <?php selected($stock_managed_by_retailer, 'false');?>><?php _e('Bol.com', 'woosa-bol-com-for-woocommerce');?></option>
                  </select>
                  <?php echo wc_help_tip(__('Choose whether the retailer manages the stock levels or bol.com', 'woosa-bol-com-for-woocommerce')); ?>
               </p>
               <p class="form-field">
                  <label><?php _e('Delivery time', 'woosa-bol-com-for-woocommerce');?></label>
                  <select class="short" name="<?php echo PREFIX;?>_offer[<?php echo esc_attr( $var_id );?>][fulfilment_delivery_code]">
                     <?php foreach(API::delivery_codes() as $key => $label):?>
                        <option value="<?php echo esc_attr( $key );?>" <?php selected($fulfilment_delivery_code, $key);?>><?php echo esc_html( $label );?></option>
                     <?php endforeach;?>
                  </select>
                  <?php echo wc_help_tip(__('Choose the delivery promise', 'woosa-bol-com-for-woocommerce')); ?>
               </p>
               <p class="form-field">
                  <label><?php _e('Fulfill by', 'woosa-bol-com-for-woocommerce');?></label>
                  <select class="short" name="<?php echo PREFIX;?>_offer[<?php echo esc_attr( $var_id );?>][fulfilment_type]" data-additional-fields="fulfilment_type">
                     <option value="FBR" <?php selected($fulfilment_type, 'FBR');?>><?php _e('Retailer', 'woosa-bol-com-for-woocommerce');?></option>
                     <option value="FBB" <?php selected($fulfilment_type, 'FBB');?>><?php _e('Bol.com', 'woosa-bol-com-for-woocommerce');?></option>
                  </select>
                  <?php echo wc_help_tip(__('Specifies whether the shipment will be fulfilled by the retailer or bol.com', 'woosa-bol-com-for-woocommerce')); ?>
               </p>
               <p class="form-field">
                  <label><?php _e('Condition', 'woosa-bol-com-for-woocommerce');?></label>
                  <select class="short" name="<?php echo PREFIX;?>_offer[<?php echo esc_attr( $var_id );?>][condition_name]">
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
            do_action(PREFIX . '_endproduct_settings_tab', $variation);
            ?>

            <p>
               <span style="color: #a30000;">*</span> - <i><?php _e('These are required fields, please make sure to set a value for them!', 'woosa-bol-com-for-woocommerce');?></i>
            </p>
         </div>
      </div>

   <?php endif;?>

<?php endforeach;?>

<p>
   <button type="button" class="button button-secondary" id="<?php echo PREFIX;?>-refresh-action"><?php _e('Refresh', 'woosa-bol-com-for-woocommerce');?></button>
</p>
