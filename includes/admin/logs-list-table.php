<?php
/**
 * Log table functions
 *
 * @package     S214_Debug\Logs
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


if( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


/**
 * Extend the WP_List_Table class
 *
 * @access      public
 * @since       1.0.0
 * @return      void
 */
class S214_Debug_Logs_List_Table extends WP_List_Table {


	/**
	 * Get things started
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	function __construct(){
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
			'singular' => 'log',     // Singular name of the listed records
			'plural'   => 'logs',    // Plural name of the listed records
			'ajax'     => false      // Does this table support ajax?
		) );
	}


	/**
	 * Render the column contents
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      string The contents of each column
	 */
	function column_default( $item, $column_name ){
		switch( $column_name ){
			case 'log_error' :
				return get_the_title( $item->ID );
			case 'plugin' :
				return get_post_meta( $item->ID, '_wp_log_logged_by', true );
			case 'date' :
				$date = strtotime( get_post_field( 'post_date', $item->ID ) );

				return date_i18n( get_option( 'date_format' ), $date ) . ' ' . __( 'at', 's214-debug' ) . ' ' . date_i18n( get_option( 'time_format' ), $date );
			default:
				return $item[$column_name];
		}
	}


	/**
	 * Render the checkbox column
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      string HTML Checkbox
	 */
	function column_cb( $item ){
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'log',
			/*$2%s*/ $item->ID
		);
	}


	/**
	 * Render the error message column
	 *
	 * @access      public
	 * @since       1.0.0
	 * @param       object $item Contains all the data of the log
	 * @return      void
	 */
	public function column_message( $item ) {
		?>
		<a href="#TB_inline?width=640&amp;inlineId=log-message-<?php echo $item->ID; ?>" class="thickbox"><?php _e( 'View Log Message', 's214-debug' ); ?></a>
		<div id="log-message-<?php echo $item->ID; ?>" style="display:none;">
			<?php
			$log_message = get_post_field( 'post_content', $item->ID );
			$serialized  = strpos( $log_message, '{"' );

			// Check to see if the log message contains serialized information
			if ( $serialized !== false ) {
				$length = strlen( $log_message ) - $serialized;
				$intro  = substr( $log_message, 0, - $length );
				$data   = substr( $log_message, $serialized, strlen( $log_message ) - 1 );

				echo wpautop( $intro );
				echo '<strong>' . wpautop( __( 'Log data:', 's214-debug' ) ) . '</strong>';
				echo '<div style="word-wrap: break-word;">' . wpautop( $data ) . '</div>';
			} else {
				// No serialized data found
				echo wpautop( $log_message );
			}
			?>
		</div>
		<?php
	}


	/**
	 * Setup our table columns
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      array
	 */
	function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
			'plugin'    => __( 'Plugin', 's214-debug' ),
			'log_error' => __( 'Error', 's214-debug' ),
			'message'   => __( 'Message', 's214-debug' ),
			'date'      => __( 'Date', 's214-debug' )
		);

		return $columns;
	}


	/**
	 * Register our bulk actions
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      array
	 */
	function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 's214-debug' ),
		);

		return $actions;
	}


	/**
	 * Process bulk action requests
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	function process_bulk_action() {
		$ids = isset( $_GET['log'] ) ? $_GET['log'] : false;

		if( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		foreach( $ids as $id ) {
			// Detect when a bulk action is being triggered...
			if( 'delete' === $this->current_action() ) {
				wp_delete_post( $id );
			}
		}
	}


	/**
	 * Load all of our data
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	function prepare_items() {
		$per_page = 20;
		$paged    = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$columns  = $this->get_columns();
		$hidden   = array(); // no hidden columns

		$this->_column_headers = array( $columns, $hidden, array() ) ;
		$this->process_bulk_action();

		$meta_query = array();

		if( isset( $_GET['user'] ) ) {
			$meta_query[] = array(
				'key'   => '_wp_log_user_id',
				'value' => absint( $_GET['user'] )
			);
		}

		$this->items = WP_Logging::get_connected_logs( array(
			'log_type'       => 's214_error',
			'paged'          => $paged,
			'posts_per_page' => $per_page,
			'meta_query'     => $meta_query
		) );

		$current_page = $this->get_pagenum();

		$total_items = WP_Logging::get_log_count( 0, 's214_error', $meta_query );

		$this->set_pagination_args( array(
			'total_items' => $total_items,                    //WE have to calculate the total number of items
			'per_page'    => $per_page,                       //WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page ) //WE have to calculate the total number of pages
		) );
	}

}