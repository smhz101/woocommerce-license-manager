<?php

class WCLM_Admin_Menu {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
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

		// add_submenu_page(
		// 'wclm_license_manager',
		// __( 'License Types', 'woocommerce-license-manager' ),
		// __( 'License Types', 'woocommerce-license-manager' ),
		// 'manage_options',
		// 'edit.php?post_type=wclm_license_type'
		// );

		add_submenu_page(
			'wclm_license_manager',
			__( 'Settings', 'woocommerce-license-manager' ),
			__( 'Settings', 'woocommerce-license-manager' ),
			'manage_options',
			'wclm_settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'wclm_license_manager',
			__( 'Reports', 'woocommerce-license-manager' ),
			__( 'Reports', 'woocommerce-license-manager' ),
			'manage_options',
			'wclm_reports',
			array( $this, 'render_reports_page' )
		);
	}

	public function render_license_manager_page() {
		include_once WCLM_PLUGIN_DIR . 'includes/admin/views/licenses-list.php';
	}

	public function render_settings_page() {
		include_once WCLM_PLUGIN_DIR . 'includes/admin/views/settings.php';
	}

	public function render_reports_page() {
		include_once WCLM_PLUGIN_DIR . 'includes/admin/views/reports.php';
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, WCLM_PLUGIN_URL . 'assets/css/admin-styles.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		// Enqueue your custom admin JavaScript.
		wp_enqueue_script(
			'wclm-admin-js',
			WCLM_PLUGIN_URL . 'assets/js/wclm-admin.js',
			array( 'jquery' ),
			WCLM_VERSION,
			true
		);

		wp_localize_script(
			'wclm-admin-js',
			'wclmAdmin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'i18n'     => array(
					'select_variation' => __( 'Select a variation', 'woocommerce-license-manager' ),
				),
			)
		);
	}
}
