<?php
/**
 * This is responsible for admin settings page
 *
 * @since 1.1.1
 */

namespace Woosa\Bol;


abstract class Abstract_Settings{

   /**
    * Setting page id.
    *
    * @since 1.1.1
    * @var string
    */
   protected $id = '';

   /**
    * Setting page label.
    *
    * @since 1.1.1
    * @var string
    */
   protected $label = '';



   /**
    * Constructor.
    *
    * @since 1.1.1
    */
   public function __construct() {

      add_filter( PREFIX . '_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
      add_action( PREFIX . '_sections_' . $this->id, array( $this, 'output_sections' ) );
      add_action( PREFIX . '_settings_' . $this->id, array( $this, 'output' ) );
      add_action( PREFIX . '_settings_save_' . $this->id, array( $this, 'save' ) );
   }



   /**
    * Get settings page ID.
    *
    * @since 1.1.1
    * @return string
    */
   public function get_id() {
      return $this->id;
   }



   /**
    * Get settings page label.
    *
    * @since 1.1.1
    * @return string
    */
   public function get_label() {
      return $this->label;
   }



   /**
    * Add this page to settings.
    *
    * @since 1.1.1
    * @param array $pages
    * @return mixed
    */
   public function add_settings_page( $pages ) {
      $pages[ $this->id ] = $this->label;

      return $pages;
   }



   /**
    * Get settings array.
    *
    * @since 1.1.1
    * @return array
    */
   public function get_settings() {
      return apply_filters( PREFIX . '_get_settings_' . $this->id, array() );
   }



   /**
    * Get sections.
    *
    * @since 1.1.1
    * @return array
    */
   public function get_sections() {
      return apply_filters( PREFIX . '_get_sections_' . $this->id, array() );
   }



   /**
    * Output sections.
    *
    * @since 1.1.1
    */
   public function output_sections() {
      global $current_section;

      $sections = $this->get_sections();

      if ( empty( $sections ) || 1 === sizeof( $sections ) ) {
         return;
      }

      echo '<ul class="subsubsub">';

      $array_keys = array_keys( $sections );

      foreach ( $sections as $id => $label ) {
         echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
      }

      echo '</ul><br class="clear" />';
   }



   /**
    * Output the settings.
    *
    * @since 1.1.1
    */
   public function output() {

      \WC_Admin_Settings::output_fields( $this->get_settings() );
   }



   /**
    * Save settings.
    *
    * @since 1.1.1
    */
   public function save() {

      \WC_Admin_Settings::save_fields( $this->get_settings() );

   }

}