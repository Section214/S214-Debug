<?php
/**
 * Class for logging events and errors
 *
 * @package     S214_Debug\Logger
 * @since       2.0.0
 */

class S214_Logger {
	
	
	/**
	 * Class constructor
	 *
	 * @access      public
	 * @since       2.0.0
	 * @return      void
	 */
	public function __construct() {
		// Create the log post type
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Create types taxonomy and default types
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		
		// Make a cron job to start pruning
		add_action( 's214_logging_prune_routine', array( $this, 'prune_logs' ) );
	}


	/**
	 * Allows you to tie in a cron job and prune old logs
	 *
	 * @access      public
	 * @since       2.0.0
	 * @return      void
	 */
	public function prune_logs() {
		$should_we_prune = apply_filters( 's214_logging_should_we_prune', false );
		
		if ( ! $should_we_prune ) {
			return;
		}
		
		$logs_to_prune = $this->get_logs_to_prune();
		
		if ( isset( $logs_to_prune ) && ! empty( $logs_to_prune ) ) {
			$this->prune_old_logs( $logs_to_prune );
		}
	}
	
	
	/**
	 * Delete old logs we don't want anymore
	 *
	 * @access      public
	 * @since       2.0.0
	 * @param       array $logs The logs to prune
	 * @return      void
	 */
	public function prune_old_logs( $logs ) {
		$force = apply_filters( 's214_logging_force_delete_log', true );
		
		foreach ( $logs as $log ) {
			$id = is_int( $log ) ? $log : $log->ID;
			wp_delete_post( $id, $force );
		}
	}
	
	
	/**
	 * Returns an array of posts to prune
	 *
	 * @access      private
	 * @since       2.0.0
	 * @return      array $old_logs The logs to prune_logs
	 */
	private function get_logs_to_prune() {
		$how_old = apply_filters( 's214_logging_prune_when', '2 weeks ago' );
		
		$args = array(
			'post_type'      => 's214_log',
			'posts_per_page' => '100',
			'date_query'     => array(
				array(
					'column' => 'post_date_gmt',
					'before' => (string) $how_old
				)
			)
		);
		
		$old_logs = get_posts( apply_filters( 's214_logging_prune_query_args', $args ) );
		
		return $old_logs;
	}


	/**
	 * Set up the default log types and allow for new ones to be created
	 *
	 * @access      private
	 * @since       2.0.0
	 * @return      array
	 */
	private static function log_types() {
		$terms = array(
			'error', 'warn', 'event'
		);

		return apply_filters( 's214_log_types', $terms );
	}


	/**
	 * Registers the s214_log Post Type
	 *
	 * @access      public
	 * @since       2.0.0
	 * @return      void
	 */
	public function register_post_type() {
		$log_args = array(
			'labels'          => array( 'name' => __( 'Logs', 's214-debug' ) ),
			'public'          => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'supports'        => array( 'title', 'editor' ),
			'can_export'      => false
		);

		register_post_type( 's214_log', apply_filters( 's214_logging_post_type_args', $log_args ) );
	}


	/**
	 * Registers the Type Taxonomy
	 *
	 * @access      public
	 * @since       2.0.0
	 * @return      void
	 */
	public function register_taxonomy() {
		register_taxonomy( 's214_log_type', 's214_log', array( 'public' => defined( 'WP_DEBUG' ) && WP_DEBUG ) );
		
		$types = self::log_types();
		
		foreach ( $types as $type ) {
			if ( ! term_exists( $type, 's214_log_type' ) ) {
				wp_insert_term( $type, 's214_log_type' );
			}
		}
	}


	/**
	 * Check if a log type is valid
	 *
	 * @access      private
	 * @since       2.0.0
	 * @param       string $type The type to check
	 * @return      array
	 */
	private static function valid_type( $type ) {
		return in_array( $type, self::log_types() );
	}


	/**
	 * Create new log entry
	 *
	 * This is just a simple and fast way to log something. Use self::insert_log()
	 * if you need to store custom meta data
	 *
	 * @access      public
	 * @since       2.0.0
	 * @param       string $title The title of the log entry
	 * @param       string $message The content of the log entry
	 * @param       int $parent The parent entry, if applicable
	 * @param       string $type The type of log entry
	 * @return      int The ID of the new log entry
	 */
	public static function add( $title = '', $message = '', $parent = 0, $type = null ) {
		$log_data = array(
			'post_title'   => $title,
			'post_content' => $message,
			'post_parent'  => $parent,
			'log_type'     => $type
		);

		return self::insert_log( $log_data );
	}


	/**
	 * Stores a log entry
	 *
	 * @access      public
	 * @since       2.0.0
	 * @param       array $log_data The data for the new log entry
	 * @param       array $log_meta Custom meta data for the entry
	 * @return      int The ID of the newly created log item
	 */
	public static function insert_log( $log_data = array(), $log_meta = array() ) {
		$defaults = array(
			'post_type'    => 's214_log',
			'post_status'  => 'publish',
			'post_parent'  => 0,
			'post_content' => '',
			'log_type'     => false
		);

		$args = wp_parse_args( $log_data, $defaults );

		do_action( 's214_pre_insert_log' );

		// Store the log entry
		$log_id = wp_insert_post( $args );
;
		// Set the log type, if any
		if ( $log_data['log_type'] && self::valid_type( $log_data['log_type'] ) ) {
			wp_set_object_terms( $log_id, $log_data['log_type'], 's214_log_type', false );
		}


		// Set log meta, if any
		if ( $log_id && ! empty( $log_meta ) ) {
			foreach ( (array) $log_meta as $key => $meta ) {
				update_post_meta( $log_id, '_s214_log_' . sanitize_key( $key ), $meta );
			}
		}

		do_action( 's214_post_insert_log', $log_id );

		return $log_id;
	}


	/**
	 * Update an existing log item
	 *
	 * @access      public
	 * @since       2.0.0
	 * @param       array $log_data The data for the log entry
	 * @param       array $log_meta The meta data for the log entry
	 * @return      bool True if successful, false otherwise
	 */
	public static function update_log( $log_data = array(), $log_meta = array() ) {
		do_action( 's214_pre_update_log', $log_id );

		$defaults = array(
			'post_type'   => 's214_log',
			'post_status' => 'publish',
			'post_parent' => 0
		);

		$args = wp_parse_args( $log_data, $defaults );

		// Store the log entry
		$log_id = wp_update_post( $args );

		if ( $log_id && ! empty( $log_meta ) ) {
			foreach ( (array) $log_meta as $key => $meta ) {
				if ( ! empty( $meta ) ) {
					update_post_meta( $log_id, '_s214_log_' . sanitize_key( $key ), $meta );
				}
			}
		}

		do_action( 's214_post_update_log', $log_id );
	}


	/**
	 * Easily retrieves log items for a particular object ID
	 *
	 * @access      public
	 * @since       2.0.0
	 * @param       int $object_id The ID of the object
	 * @param       string $type The type of entries to retrieve
	 * @param       int $paged The page to retrieve
	 * @return      array
	 */
	public static function get_logs( $object_id = 0, $type = null, $paged = null ) {
		return self::get_connected_logs( array( 'post_parent' => $object_id, 'paged' => $paged, 'log_type' => $type ) );
	}


	/**
	 * Retrieve all connected logs
	 *
	 * Used for retrieving logs related to particular items
	 *
	 * @access      public
	 * @since       2.0.0
	 * @param       array $args The args for the item to retrieve
	 * @return      array / false
	 */
	public static function get_connected_logs( $args = array() ) {
		$defaults = array(
			'post_parent'    => 0,
			'post_type'      => 's214_log',
			'posts_per_page' => 10,
			'post_status'    => 'publish',
			'paged'          => get_query_var( 'paged' ),
			'log_type'       => false
		);

		$query_args = wp_parse_args( $args, $defaults );

		if ( $query_args['log_type'] && self::valid_type( $query_args['log_type'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 's214_log_type',
					'field'    => 'slug',
					'terms'    => $query_args['log_type']
				)
			);
		}

		$logs = get_posts( $query_args );

		if ( $logs ) {
			return $logs;
		}

		// No logs found
		return false;
	}


	/**
	 * Retrieves number of log entries connected to particular object ID
	 *
	 * @access      public
	 * @since       2.0.0
	 * @param       int $object_id The ID to retrieve logs for
	 * @param       string $type The type of logs to retrieve
	 * @param       array $meta_query A meta query to apply to the search
	 * @return      int
	 */
	public static function get_log_count( $object_id = 0, $type = null, $meta_query = null ) {
		$query_args = array(
			'post_parent'    => $object_id,
			'post_type'      => 's214_log',
			'posts_per_page' => -1,
			'post_status'    => 'publish'
		);

		if ( ! empty( $type ) && self::valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 's214_log_type',
					'field'    => 'slug',
					'terms'    => $type
				)
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$logs = new WP_Query( $query_args );

		return (int) $logs->post_count;
	}
}
$GLOBALS['s214_logs'] = new S214_Logger();