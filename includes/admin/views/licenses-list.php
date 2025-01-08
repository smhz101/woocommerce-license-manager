<?php

if ( ! class_exists( 'WCLM_License_List_Table' ) ) {
	include_once WCLM_PLUGIN_DIR . 'includes/admin/class-wclm-license-list-table.php';
}

$wclm_license_list_table = new WCLM_License_List_Table();
$wclm_license_list_table->prepare_items();

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e( 'Licenses', 'woocommerce-license-manager' ); ?></h1>
	<a href="<?php echo admin_url( 'admin.php?page=wclm_add_new_license' ); ?>" class="page-title-action">Add New License</a>
	
	<hr class="wp-header-end">
	<h2 class="screen-reader-text">Filter pages list</h2>

	<form method="post">
		<?php

		// Display notices.
		$wclm_license_list_table->display_notices();

		// Display subsubsub filters.
		$wclm_license_list_table->views();

		// Display search form.
		$wclm_license_list_table->search_box( __( 'Search Licenses', 'woocommerce-license-manager' ), 'license_search' );

		$wclm_license_list_table->display();
		?>
	</form>
</div>
