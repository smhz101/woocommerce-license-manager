<?php

namespace WCLM;

class Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'License Manager', 'woocommerce-license-manager' ),
			__( 'License Manager', 'woocommerce-license-manager' ),
			'manage_options',
			'wclm_license_manager',
			array( $this, 'render_license_manager_page' ),
			'dashicons-admin-network',
			56
		);
	}

	public function render_license_manager_page() {
		// Render the licenses list table
		include_once WCLM_PLUGIN_DIR . 'templates/admin/licenses-list.php';
	}
}
