<?php 

if ( ! class_exists( 'WCLM_License_List_Table' ) ) {
	include_once WCLM_PLUGIN_DIR . 'includes/admin/class-wclm-license-list-table.php';
}

$wclm_license_list_table = new WCLM_License_List_Table();
$wclm_license_list_table->display_add_license_form();
