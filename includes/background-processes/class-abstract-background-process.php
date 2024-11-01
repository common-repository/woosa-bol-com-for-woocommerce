<?php
/**
 * Abstract background process
 *
 * @since 1.1.1
 */

namespace Woosa\Bol;

defined( 'ABSPATH' ) || exit;


abstract class Abstract_Background_Process extends \WP_Background_Process {

   protected $queue_lock_time = 3 * MINUTE_IN_SECONDS;

   public function __construct() {
      parent::__construct();

      add_action( $this->identifier . '_default_time_limit', [ $this, 'change_time_limit' ] );
   }


   /**
    * @since 1.1.1
    * @return string
    */
   public function get_action(){
      return $this->action;
   }



   /**
    * Change background process time limit.
    * You can override this method in your class
    *
    * @param int $limit
    *
    * @return int
    */
   public function change_time_limit( $limit ) {
      return 2 * MINUTE_IN_SECONDS;
   }



   /**
    * Fix - if it's ajax background process request ($this->dispatch()), it dies after 20 seconds
    * @since 1.1.1
    */
   public function maybe_handle() {
      ignore_user_abort( true );
      parent::maybe_handle();
   }



   /**
    * Save queue
    *
    * @return $this
    */
   public function save() {
      $key = $this->generate_key();

      // add only one queue per time
      if ( ! empty( $this->data ) && $this->is_queue_empty() ) {
         update_site_option( $key, $this->data );
      }

      return $this;
   }
}
