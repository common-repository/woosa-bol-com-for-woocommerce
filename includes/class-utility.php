<?php
/**
 * This is an utility class with useful methods
 *
 * @since 1.0.0
 */

namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Utility{


	/**
	 * Get content of a given file
	 *
	 * @since 1.0.0
	 * @param string $file
	 * @param mixed $vars
	 * @return mixed
	 */
	public static function get_tpl($file, $vars = array()){

      extract($vars);

		$content = '';

		if(file_exists($file)){

			ob_start();

			include $file;

			$content = ob_get_clean();
		}

		return $content;
	}



   /**
    * Print array/obj in a readable format
    *
    * @since 1.0.0
    * @param array|object $data
    * @param boolean $exit
    * @return void
    */
   public static function pr($data, $exit = false){

      echo '<pre>'.print_r($data, 1).'</pre>';

      if($exit) exit;
   }



	/**
	 * Get a specific property of an array without needing to check if that property exists.
	 *
	 * Provide a default value if you want to return a specific value if the property is not set.
	 *
	 * @since  1.0.0
	 * @param array  $array   Array from which the property's value should be retrieved.
	 * @param string $prop    Name of the property to be retrieved.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 *
	 * @return null|string|mixed The value
	 */
	public static function rgar( $array, $prop, $default = null, $sanitize = 'text' ) {

      $default = self::sanitize($default, $sanitize);

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
         return $default;
		}

		if ( isset( $array[$prop] ) ) {
			$value = is_string($array[$prop]) ? self::sanitize($array[$prop], $sanitize) : $array[$prop];
		} else {
			$value = '';
		}

		return empty( $value ) && $default !== null ? $default : $value;
	}



	/**
	 * Gets a specific property within a multidimensional array.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array  $array   The array to search in.
	 * @param string $name    The name of the property to find.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 * @param string $sanitize Optional. the sanitize method
	 *
	 * @return null|string|mixed The value
	 */
	public static function rgars( $array, $name, $default = null, $sanitize = 'text' ) {

      $default = self::sanitize($default, $sanitize);

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		$names = explode( '/', $name );
		$val   = $array;
		foreach ( $names as $current_name ) {
			$val = self::rgar( $val, $current_name, $default, $sanitize );
		}

		return $val;
   }



   /**
    * Sanitize method
    *
    * @since 1.0.0
    * @param string $string
    * @param string $method
    * @return string
    */
   public static function sanitize($string, $method = 'text'){

      switch($method){

         case 'email': return sanitize_email($string); break;

         case 'key': return sanitize_key($string); break;

         case 'html_class': return sanitize_html_class($string); break;
      }

      return sanitize_text_field( $string );
   }



   /**
    * Displays admin network notice.
    *
    * @since 1.0.0
    * @param string $msg
    * @param string $type
    * @return string
    */
    public static function show_network_notice($msg, $type = 'error', $html = false){

      add_action('network_admin_notices', function() use ($msg, $type, $html){
         if($html){
            echo '<div class="wsa-notice notice notice-'.$type.'"><b>'.PLUGIN_NAME.':</b> '.$msg.'</div>';
         }else{
            echo '<div class="wsa-notice notice notice-'.$type.'"><p><b>'.PLUGIN_NAME.':</b> '.$msg.'</p></div>';
         }
      });
   }



   /**
    * Displays admin notice.
    *
    * @since 1.0.0
    * @param string $msg
    * @param string $type
    * @return string
    */
    public static function show_notice($msg, $type = 'error', $html = false){

      add_action('admin_notices', function() use ($msg, $type, $html){
         if($html){
            echo '<div class="wsa-notice notice notice-'.$type.'"><b>'.PLUGIN_NAME.':</b> '.$msg.'</div>';
         }else{
            echo '<div class="wsa-notice notice notice-'.$type.'"><p><b>'.PLUGIN_NAME.':</b> '.$msg.'</p></div>';
         }
      });
   }



   /**
    * Displays admin notice.
    *
    * @since 1.1.2
    * @param string $msg
    * @param string $type
    * @return string
    */
    public static function show_important_notice($msg, $type = 'error'){

      add_action('admin_notices', function() use ($msg, $type, $html){
         echo '<div class="wsa-notice wsa-important-notice notice">'.$msg.'</div>';
      });
   }



   /**
    * Log errors in a error.log file in the root of the plugin folder
    *
    * @since 1.0.0
    * @param mixed $message
    * @param string $file
    * @param string $line
    * @return void
    */
    public static function error_log($message, $file = '', $line = ''){

      if(!is_string($message)){
         $message = print_r( $message, true );
      }
      if(!empty($file) && !empty($line)){
         $message = "{$message} thrown in {$file}:{$line}";
      }

      error_log('['.date('Y-m-d h:s:i').'] '.$message.PHP_EOL, 3, ERROR_PATH);
   }



   /**
	 * Logging method for Woocommerce
	 *
    * @since 1.0.0
    * @param string $message
    * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
    * @param string $file
    * @param string $line
    * @param string $source
    * @return void
    */
	public static function wc_log( $message, $level = 'info', $file = '', $line = '', $source = PLUGIN_FOLDER ) {

      if(function_exists('wc_get_logger')){

         $message = !is_string($message) ? print_r( $message, true ) : $message;

         if(!empty($file) && !empty($line)){
            $message = "{$message} thrown in {$file}:{$line}";
         }

         $log = wc_get_logger();
         $log->log( $level, $message, array( 'source' => $source ) );

      }else{

         self::error_log($message, $file, $line);
      }
	}



   /**
    * Log errors in Woocommerce logs
    *
    * @since 1.0.0
    * @param mixed $message
    * @param string $file
    * @param string $line
    * @return void
    */
   public static function wc_error_log($message, $file = '', $line = ''){

      if(function_exists('wc_get_logger')){

         $message = !is_string($message) ? print_r( $message, true ) : $message;

         self::wc_log($message, 'error', $file, $line);

      }else{

         self::error_log($message, $file, $line);
      }
   }



   /**
    * Log debug info in Woocommerce logs
    *
    * @since 1.0.0
    * @param mixed $message
    * @param string $file
    * @param string $line
    * @return void
    */
   public static function wc_debug_log($message, $file = '', $line = ''){

      if(function_exists('wc_get_logger')){

         $message = !is_string($message) ? print_r( $message, true ) : $message;

         self::wc_log($message, 'debug', $file, $line);

      }else{

         self::error_log($message, $code);
      }
   }



   /**
    * Convert an object to an array
    *
    * @since 1.0.0
    * @param object $obj
    * @return array
    */
   public static function obj_to_arr($obj){
      return json_decode(json_encode($obj), true);
   }



   /**
    * Calculate sum of two numbers
    *
    * @param string $x
    * @param string $y
    * @return void
    */
   public static function sum($x, $y){

      if(!is_numeric($x) || !is_numeric($y)){
         throw new \InvalidArgumentException;
      }

      return $x + $y;
   }


   public static function maybe_json_decode($str){

      if(is_string($str) && is_array(json_decode($str, true))) return json_decode($str);

      return $str;
   }


   /**
    * Return formatted price using WC settings
    *
    * @param int|float $price
    *
    * @return string
    */
   public static function wc_format_price($price){
      if(is_numeric($price)){
         $price = number_format( $price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
      }

      return $price;
   }


   /**
    * Return normalized price using WC settings
    *
    * @param string $price
    *
    * @return float
    */
   public static function wc_normalize_price($price) {
      if(is_numeric($price)){
         $price = str_replace(wc_get_price_decimal_separator(), '.', str_replace(wc_get_price_thousand_separator(), '', $price));
      }

      return $price;
   }



   /**
    * Formats price by keeping only a dot for decimals
    *
    * @since 1.1.2
    * @param string $price
    * @return string
    */
   public static function format_price($price){

      if('' !== $price){

         $price = str_replace(',', '.', $price);
         $price = preg_replace('/\.(?=.*\.)/', '', $price);

         return $price;

      }
   }

}