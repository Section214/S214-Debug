<?php
/**
 * Load scripts
 *
 * @package     S214_Debug\Scripts
 * @since       1.0.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Load admin scripts
 *
 * @since       1.0.0
 * @param       string $hook Page hook
 * @return      void
 */
function s214_debug_load_admin_scripts( $hook ) {
	if ( $hook == 'tools_page_s214-debug-logs' ) {
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

		wp_enqueue_style( 's214-debug', S214_DEBUG_URL . 'assets/css/admin.css' );
	}
}
add_action( 'admin_enqueue_scripts', 's214_debug_load_admin_scripts', 100 );