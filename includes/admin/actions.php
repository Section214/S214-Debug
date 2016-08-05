<?php
/**
 * Admin actions
 *
 * @package     S214_Debug\Admin\Actions
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Process all actions sent via POST and GET by looking for the 's214-debug-action'
 * request and running do_action() to call the function
 *
 * @since       1.0.0
 * @return      void
 */
function s214_debug_process_actions() {
    if( isset( $_POST['s214-debug-action'] ) ) {
        do_action( 's214_debug_' . $_POST['s214-debug-action'], $_POST );
    }

    if( isset( $_GET['s214-debug-action'] ) ) {
        do_action( 's214_debug_' . $_GET['s214-debug-action'], $_GET );
    }
}
add_action( 'admin_init', 's214_debug_process_actions' );
