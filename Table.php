<?php

// Takes care of creating, updating and deleting database tables

class scbTable {
	protected $name;
	protected $columns;
	protected $upgrade_method;

	function __construct( $name, $file, $columns, $upgrade_method = 'dbDelta' ) {
		$this->name = $name;
		$this->columns = $columns;
		$this->upgrade_method = $upgrade_method;

		scb_register_table( $name );

		if ( $file ) {
			scbUtil::add_activation_hook( $file, array( $this, 'install' ) );
			scbUtil::add_uninstall_hook( $file, array( $this, 'uninstall' ) );
		}
	}

	function install() {
		scb_install_table( $this->name, $this->columns, $this->upgrade_method );
	}

	function uninstall() {
		scb_uninstall_table( $this->name );
	}
}

/**
 * Register a table with $wpdb
 *
 * @param string $key The key to be used on the $wpdb object
 * @param string $name The actual name of the table, without $wpdb->prefix
 */
function scb_register_table( $key, $name = false ) {
	global $wpdb;

	if ( !$name )
		$name = $key;

	$wpdb->tables[] = $name;
	$wpdb->$key = $wpdb->prefix . $name;
}

/**
 * Runs the SQL query for installing/upgrading a table
 *
 * @param string $key The key used in scb_register_table()
 * @param string $columns The SQL columns for the CREATE TABLE statement
 * @param array $opts Various other options
 */
function scb_install_table( $key, $columns, $opts = array() ) {
	global $wpdb;

	$full_table_name = $wpdb->$key;

	if ( is_string( $opts ) ) {
		$opts = array( 'upgrade_method' => $opts );
	}

	$opts = wp_parse_args( $opts, array(
		'upgrade_method' => 'dbDelta',
		'table_options' => '',
	) );

	$charset_collate = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";
	}

	$table_options = $charset_collate . ' ' . $opts['table_options'];

	if ( 'dbDelta' == $opts['upgrade_method'] ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( "CREATE TABLE $full_table_name ( $columns ) $table_options" );
		return;
	}

	if ( 'delete_first' == $opts['upgrade_method'] )
		$wpdb->query( "DROP TABLE IF EXISTS $full_table_name;" );

	$wpdb->query( "CREATE TABLE IF NOT EXISTS $full_table_name ( $columns ) $table_options;" );
}

function scb_uninstall_table( $key ) {
	global $wpdb;

	$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->$key );
}

