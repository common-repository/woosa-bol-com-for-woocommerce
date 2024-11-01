<?php
/**
 * Template for admin addons page
 *
 * @since 1.1.1
 */


namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e('Addons', 'woosa-bol-com-for-woocommerce');?></h1>

   <ul class="<?php echo PREFIX;?>-addons">
      <?php foreach(Addons::get_list() as $item):?>
         <li class="<?php echo PREFIX;?>-addons__item">
            <div class="<?php echo PREFIX;?>-addons__thumbnail"><img src="<?php echo esc_attr( $item['thumbnail'] );?>" /></div>
            <h3><?php echo $item['title'];?></h3>
            <div style="height: 40px;"><?php echo $item['description'];?></div>
            <p>
               <?php if( Core::has_addon($item['slug']) ):?>
                  <button type="button" disabled="disabled" class="button" target="_blank"><?php _e('Installed', 'woosa-bol-com-for-woocommerce');?></button>
               <?php else:?>
                  <a href="<?php echo esc_attr( $item['url'] );?>" class="button button-primary" target="_blank"><?php _e('Read more', 'woosa-bol-com-for-woocommerce');?></a>
               <?php endif;?>
            </p>
         </li>
      <?php endforeach;?>
   </ul>

</div>
