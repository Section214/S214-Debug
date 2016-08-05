<?php
/**
 * Render admin pages
 *
 * @package     S214_Debug\Logs
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once S214_DEBUG_DIR . 'includes/admin/logs-list-table.php';


/**
 * Add menu item
 *
 * @since       1.0.0
 * @return      void
 */
function s214_debug_logs_menu() {
	global $s214_debug_logs_page;

	$s214_debug_logs_page = add_submenu_page( 'tools.php', __( 'Section214 Debug', 's214-debug' ), __( 'Section214 Debug', 's214-debug' ), 'manage_options', 's214-debug-logs', 's214_debug_logs_page' );
}
add_action( 'admin_menu', 's214_debug_logs_menu' );

/**
 * Render the logs page
 *
 * @since       1.0.0
 * @return      void
 */
function s214_debug_logs_page() {
	?>
	<div class="wrap">

		<div id="icon-tools" class="icon32"><br/></div>
		<h2><?php _e( 'Section214 Logs', 'rcp' ); ?></h2>

		<form method="get" id="s214-debug-logs">
			<input type="hidden" name="page" value="s214-debug-logs"/>
			<?php

			$logs_table = new S214_Debug_Logs_List_Table();

			//Fetch, prepare, sort, and filter our data...
			$logs_table->prepare_items();

			$logs_table->views();

			$logs_table->display();
		?>
		</form>
	</div>
	<?php
}