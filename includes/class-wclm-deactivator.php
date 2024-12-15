<?php

class WCLM_Deactivator {

	public static function deactivate() {
		// Flush rewrite rules on deactivation
		flush_rewrite_rules();

		// Clear scheduled event
		wp_clear_scheduled_hook( 'wclm_daily_license_check' );
	}
}
