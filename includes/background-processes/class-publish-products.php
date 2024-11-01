<?php
/**
 * Publish products
 *
 * This publishs products.
 *
 * @since 1.0.5
 */

namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Publish_Products extends Abstract_Background_Process {


	/**
	 * Unique action.
    *
    * @since 1.0.5
	 */
   protected $action = PREFIX.'_publish_products';



	/**
	 * Task
	 *
	 * Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
    * @since 1.0.5
	 * @param int $item - product id
	 * @return false
	 */
	protected function task( $item ) {

      $current_item = get_option(PREFIX .'_current_published_product', '0');

      //set action
      update_post_meta($item, PREFIX.'_action', 'publish');

      //remove product accounts list so that it will use all available accounts
      delete_post_meta($item, PREFIX.'_accounts');

      Product::process_offer($item);

      $current_item++;

      update_option(PREFIX .'_current_published_product', $current_item);

		return false;
	}



	/**
	 * Complete
	 *
    * @since 1.0.7 - added a general function for clearing cache
	 * @since 1.0.5
	 */
	protected function complete() {
      parent::complete();

      delete_option(PREFIX.'_publish_products_started');
      delete_option(PREFIX.'_current_published_product');

      Core::clear_all_caches();
	}

}