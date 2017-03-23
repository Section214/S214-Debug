<?php
/**
 * Plugin Name:     Section214 Debugging Suite
 * Plugin URI:      https://section214.com
 * Description:     Provides a simple debugging suite for Section214 plugins
 * Version:         1.0.0
 * Author:          Daniel J Griffiths
 * Author URI:      https://section214.com
 * Text Domain:     s214-debug
 *
 * @package         S214_Debug
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'S214_Debug' ) ) {


	/**
	 * Main S214_Debug class
	 *
	 * @since       1.0.0
	 */
	class S214_Debug {


		/**
		 * @var         S214_Debug $instance The one true S214_Debug
		 * @since       1.0.0
		 */
		private static $instance;


		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      self::$instance The one true S214_Debug
		 */
		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new S214_Debug();
				self::$instance->setup_constants();
				self::$instance->load_textdomain();
				self::$instance->includes();
			}
			//s214_debug_log_error( 'Test', 'Test Message', 'Test User' );

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function setup_constants() {
			// Plugin version
			define( 'S214_DEBUG_VER', '1.0.0' );

			// Plugin path
			define( 'S214_DEBUG_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin URL
			define( 'S214_DEBUG_URL', plugin_dir_url( __FILE__ ) );
		}


		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function includes() {
			if ( ! class_exists( 'S214_Logger' ) ) {
				require_once S214_DEBUG_DIR . 'includes/class.logger.php';
			}

			require_once S214_DEBUG_DIR . 'includes/functions.php';
			require_once S214_DEBUG_DIR . 'includes/scripts.php';

			if ( is_admin() ) {
				require_once S214_DEBUG_DIR . 'includes/admin/actions.php';
				require_once S214_DEBUG_DIR . 'includes/admin/pages.php';
			}
		}


		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function load_textdomain() {
			// Set filter for language directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 's214_debug_language_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), '' );
			$mofile = sprintf( '%1$s-%2$s.mo', 's214-debug', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/s214-debug/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/s214-debug/ folder
				load_textdomain( 's214-debug', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/s214-debug/languages/ folder
				load_textdomain( 's214-debug', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 's214-debug', false, $lang_dir );
			}
		}
	}
}


/**
 * The main function responsible for returning the one true S214_Debug
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      S214_Debug The one true S214_Debug
 */
function s214_debug() {
	return S214_Debug::instance();
}
add_action( 'plugins_loaded', 's214_debug' );