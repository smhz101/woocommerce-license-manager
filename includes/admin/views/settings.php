<?php

$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'connection';

?>

<div class="wrap">
	<h1><?php _e( 'License Manager Settings', 'woocommerce-license-manager' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="?page=wclm_settings&tab=connection" class="nav-tab <?php echo $active_tab == 'connection' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Connection', 'woocommerce-license-manager' ); ?></a>
		<a href="?page=wclm_settings&tab=license_types" class="nav-tab <?php echo $active_tab == 'license_types' ? 'nav-tab-active' : ''; ?>"><?php _e( 'License Types', 'woocommerce-license-manager' ); ?></a>
	</h2>

	<?php
	switch ( $active_tab ) {
		case 'license_types':
			include_once WCLM_PLUGIN_DIR . 'includes/admin/views/license-types.php';
			break;
		case 'connection':
		default:
			include_once WCLM_PLUGIN_DIR . 'includes/admin/views/connection-settings.php';
			break;
	}
	?>
</div>