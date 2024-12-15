<?php
/**
 * WCLM_Activator Class File
 *
 * This file contains the WCLM_Activator class, responsible for handling
 * plugin activation tasks such as flushing rewrite rules and scheduling events.
 *
 * @package WooCommerce_License_Manager
 * @since 1.0.0
 */

/**
 * Class WCLM_Activator
 *
 * Handles plugin activation tasks, such as flushing rewrite rules
 * and scheduling events.
 *
 * @package WooCommerce_License_Manager
 * @since 1.0.0
 */
class WCLM_Activator {

	/**
	 * Activation hook callback.
	 *
	 * This function is called during the plugin activation. It flushes
	 * the rewrite rules and schedules a daily event for license checks.
	 *
	 * @return void
	 */
	public static function activate() {
		// Flush rewrite rules for the new endpoint.
		flush_rewrite_rules();

		// Schedule daily event if not already scheduled.
		if ( ! wp_next_scheduled( 'wclm_daily_license_check' ) ) {
			wp_schedule_event( time(), 'daily', 'wclm_daily_license_check' );
		}
	}
}
