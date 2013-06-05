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

