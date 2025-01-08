<?php
/**
 * Plugin Name: WooCommerce License Manager
 * Plugin URI: https://wpthemepress.com/
 * Description: A plugin to manage and sell licenses via WooCommerce.
 * Version: 1.0.0
 * Author: Muzammil Hussain
 * Author URI: https://muzammil.dev/
 * Text Domain: woocommerce-license-manager
 * Domain Path: /languages
 *
 * @package WooCommerce_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'WCLM_VERSION', '1.0.0' );
define( 'WCLM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCLM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include necessary class files.
require_once WCLM_PLUGIN_DIR . 'includes/class-wclm-activator.php';
require_once WCLM_PLUGIN_DIR . 'includes/class-wclm-deactivator.php';
require_once WCLM_PLUGIN_DIR . 'includes/class-wclm.php';

// Register activation hook.
register_activation_hook( __FILE__, array( 'WCLM_Activator', 'activate' ) );

// Register deactivation hook.
register_deactivation_hook( __FILE__, array( 'WCLM_Deactivator', 'deactivate' ) );

/**
 * Check license expirations and send notifications.
 *
 * This function fetches license data via the API client and checks if any licenses
 * are expiring within 15 days. If a license is expiring, it sends a notification email
 * to the associated client.
 *
 * @return void
 */
function wclm_check_license_expirations() {
	$api_client = new WCLM_API_Client();

	// Fetch licenses from the API.
	$licenses = $api_client->request( 'GET', '/api/licenses' );

	// Process the licenses.
	if ( isset( $licenses['licenses'] ) ) {
		foreach ( $licenses['licenses'] as $license ) {
			$expires_at = new DateTime( $license['expiresAt'] );
			$now        = new DateTime();
			$interval   = $now->diff( $expires_at );

			// Check if license is expiring within 15 days.
			if ( $interval->days <= 15 && $interval->invert === 0 ) {
				// Send notification to client.
				$client_email = $license['client']['contactEmail'];
				$subject      = __( 'Your License is About to Expire', 'woocommerce-license-manager' );
				$message      = sprintf(
					/* translators: 1: Client name, 2: License key, 3: Expiry date. */
					__( 'Dear %1$s, your license %2$s is expiring on %3$s. Please renew it to continue using our services.', 'woocommerce-license-manager' ),
					$license['client']['name'],
					$license['licenseKey'],
					$expires_at->format( 'Y-m-d' )
				);
				wp_mail( $client_email, $subject, $message );
			}
		}
	}
}

/**
 * Schedule the daily license check on activation.
 */
function wclm_schedule_daily_license_check() {
	if ( ! wp_next_scheduled( 'wclm_daily_license_check' ) ) {
		wp_schedule_event( time(), 'daily', 'wclm_daily_license_check' );
	}
}
add_action( 'wclm_daily_license_check', 'wclm_check_license_expirations' );

/**
 * Initialize the WooCommerce License Manager plugin.
 *
 * This function instantiates the main plugin class and runs it.
 *
 * @return void
 */
function wclm_init() {
	$plugin = new WCLM();
	$plugin->run();
}

// Hook the initialization to 'plugins_loaded'.
add_action( 'plugins_loaded', 'wclm_init' );

// Hook the scheduling function to activation.
register_activation_hook( __FILE__, 'wclm_schedule_daily_license_check' );

/**
 * Hook the unscheduling function to deactivation.
 */
function wclm_unschedule_daily_license_check() {
	wp_clear_scheduled_hook( 'wclm_daily_license_check' );
}
register_deactivation_hook( __FILE__, 'wclm_unschedule_daily_license_check' );
