<?php

class WCLM_Frontend {

	private $plugin_name;
	private $version;
	private $api_client;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Initialize API client
		$settings = get_option( 'wclm_settings' );
		$username = isset( $settings['api_username'] ) ? $settings['api_username'] : '';
		$password = isset( $settings['api_password'] ) ? $settings['api_password'] : '';

		$this->api_client = new WCLM_API_Client( $username, $password );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		if ( is_product() ) {
			$product = wc_get_product( get_the_ID() );

			if ( $product->is_type( 'variable' ) ) {
				return;
			}

			wp_enqueue_script( 'wclm-frontend', WCLM_PLUGIN_URL . 'assets/js/wclm-frontend.js', array( 'jquery' ), WCLM_VERSION, true );

			// Localize script to pass parameters.
			wp_localize_script(
				'wclm-frontend',
				'wclm_frontend_params',
				array(
					'label'    => __( 'License Duration (Years):', 'woocommerce-license-manager' ),
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);
		}
	}

	public function add_license_endpoint() {
		add_rewrite_endpoint( 'licenses', EP_ROOT | EP_PAGES );
	}

	public function add_account_menu_item( $items ) {
		$items['licenses'] = __( 'Licenses', 'woocommerce-license-manager' );
		return $items;
	}

	public function licenses_endpoint_content() {
		include_once WCLM_PLUGIN_DIR . 'includes/public/views/myaccount-licenses.php';
	}

	public function handle_license_actions() {
		if ( isset( $_POST['wclm_action'] ) && isset( $_POST['license_key'] ) ) {
			$license_key = sanitize_text_field( $_POST['license_key'] );
			$domain      = home_url(); // Use the site's URL as the domain

			$api_client = new WCLM_API_Client();

			if ( $_POST['wclm_action'] === 'validate_license' ) {
				check_admin_referer( 'wclm_validate_license' );
				$license_key = sanitize_text_field( $_POST['validate_license_key'] );

				$response = $api_client->validate_license( $license_key );

				if ( isset( $response['status'] ) && $response['status'] === 'valid' ) {
						wc_add_notice( __( 'License is valid.', 'woocommerce-license-manager' ), 'success' );
				} else {
						wc_add_notice( __( 'License is invalid.', 'woocommerce-license-manager' ), 'error' );
				}
			}

			if ( $_POST['wclm_action'] === 'activate' ) {
				$response = $api_client->activate_license( $license_key, $domain );
				if ( isset( $response['status'] ) && $response['status'] === 'activated' ) {
					wc_add_notice( __( 'License activated successfully.', 'woocommerce-license-manager' ), 'success' );
				} else {
					wc_add_notice( __( 'Failed to activate license.', 'woocommerce-license-manager' ), 'error' );
				}
			} elseif ( $_POST['wclm_action'] === 'deactivate' ) {
				$response = $api_client->deactivate_license( $license_key, $domain );
				if ( isset( $response['status'] ) && $response['status'] === 'deactivated' ) {
					wc_add_notice( __( 'License deactivated successfully.', 'woocommerce-license-manager' ), 'success' );
				} else {
					wc_add_notice( __( 'Failed to deactivate license.', 'woocommerce-license-manager' ), 'error' );
				}
			}

			wp_redirect( wc_get_account_endpoint_url( 'licenses' ) );
			exit;
		}
	}
}
