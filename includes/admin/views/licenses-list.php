<?php

if ( ! class_exists( 'WCLM_License_List_Table' ) ) {
	include_once WCLM_PLUGIN_DIR . 'includes/admin/class-wclm-license-list-table.php';
}

$license_list_table = new WCLM_License_List_Table();
$license_list_table->prepare_items();

?>
<div class="wrap">
	<h1><?php _e( 'Licenses', 'woocommerce-license-manager' ); ?></h1>
	<?php $license_list_table->display_add_license_form(); ?>

	<form method="post">
		<?php

		// Display notices.
		$license_list_table->display_notices();

		// Display subsubsub filters.
		$license_list_table->views();

		// Display search form.
		$license_list_table->search_box( __( 'Search Licenses', 'woocommerce-license-manager' ), 'license_search' );

		$license_list_table->display();
		?>
	</form>
</div>
