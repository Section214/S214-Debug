<?php
/**
 * Helper functions
 *
 * @package     S214_Debug\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Helper function for logging errors
 *
 * @since       1.0.0
 * @param       string $title The error title
 * @param       string $content The error content
 * @param       string $logged_by The plugin logging the error
 * @return      mixed The ID of the new log item
 */
function s214_debug_log_error( $title, $content, $logged_by = 'S214 Debug' ) {
	$log_data = array(
		'post_title'   => $title,
		'post_content' => $content,
		'post_parent'  => 0,
		'log_type'     => 'error'
	);

	$log_meta = array(
		'logged_by' => $logged_by
	);

	return S214_Logger::insert_log( $log_data, $log_meta );
}