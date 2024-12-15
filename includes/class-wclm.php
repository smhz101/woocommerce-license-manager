<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WCLM {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'woocommerce-license-manager';
		$this->version     = WCLM_VERSION;

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once WCLM_PLUGIN_DIR . 'includes/class-wclm-loader.php';
		require_once WCLM_PLUGIN_DIR . 'includes/class-wclm-license-manager.php';
		require_once WCLM_PLUGIN_DIR . 'includes/api/class-wclm-api-client.php';
		require_once WCLM_PLUGIN_DIR . 'includes/admin/class-wclm-admin-menu.php';
		require_once WCLM_PLUGIN_DIR . 'includes/public/class-wclm-frontend.php';

		require_once WCLM_PLUGIN_DIR . 'includes/admin/wclm-hooks.php';

		require_once WCLM_PLUGIN_DIR . 'includes/admin/class-wclm-license-types.php';
		require_once WCLM_PLUGIN_DIR . 'includes/admin/class-wclm-product-settings.php';

		$this->loader = new WCLM_Loader();
	}

	private function define_admin_hooks() {
		$plugin_admin = new WCLM_Admin_Menu( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
	}

	private function define_public_hooks() {
		$plugin_public = new WCLM_Frontend( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init', $plugin_public, 'add_license_endpoint' );
		$this->loader->add_filter( 'woocommerce_account_menu_items', $plugin_public, 'add_account_menu_item' );
		$this->loader->add_action( 'woocommerce_account_licenses_endpoint', $plugin_public, 'licenses_endpoint_content' );

		// Frontend license duration field and processing
		$license_manager = new WCLM_License_Manager();
		$this->loader->add_action( 'woocommerce_before_add_to_cart_button', $license_manager, 'add_license_duration_field' );
		$this->loader->add_filter( 'woocommerce_add_cart_item_data', $license_manager, 'add_cart_item_data', 10, 2 );
		$this->loader->add_action( 'woocommerce_before_calculate_totals', $license_manager, 'adjust_cart_item_price', 20, 1 );
		$this->loader->add_filter( 'woocommerce_get_item_data', $license_manager, 'display_license_duration_in_cart', 10, 2 );
		$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $license_manager, 'add_license_duration_to_order_items', 10, 4 );
		$this->loader->add_action( 'woocommerce_order_status_completed', $license_manager, 'process_order' );

		$this->loader->add_action( 'template_redirect', $plugin_public, 'handle_license_actions' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}
}
