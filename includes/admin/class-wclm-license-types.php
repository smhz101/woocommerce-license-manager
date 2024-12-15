<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WCLM_License_Types {

	public function __construct() {
		add_action( 'init', array( $this, 'register_license_type_post_type' ) );
		add_action( 'save_post_wclm_license_type', array( $this, 'save_license_type_meta' ) );
	}

	public function register_license_type_post_type() {
		$labels = array(
			'name'               => __( 'License Types', 'woocommerce-license-manager' ),
			'singular_name'      => __( 'License Type', 'woocommerce-license-manager' ),
			'menu_name'          => __( 'License Types', 'woocommerce-license-manager' ),
			'name_admin_bar'     => __( 'License Type', 'woocommerce-license-manager' ),
			'add_new'            => __( 'Add New', 'woocommerce-license-manager' ),
			'add_new_item'       => __( 'Add New License Type', 'woocommerce-license-manager' ),
			'new_item'           => __( 'New License Type', 'woocommerce-license-manager' ),
			'edit_item'          => __( 'Edit License Type', 'woocommerce-license-manager' ),
			'view_item'          => __( 'View License Type', 'woocommerce-license-manager' ),
			'all_items'          => __( 'All License Types', 'woocommerce-license-manager' ),
			'search_items'       => __( 'Search License Types', 'woocommerce-license-manager' ),
			'parent_item_colon'  => __( 'Parent License Types:', 'woocommerce-license-manager' ),
			'not_found'          => __( 'No license types found.', 'woocommerce-license-manager' ),
			'not_found_in_trash' => __( 'No license types found in Trash.', 'woocommerce-license-manager' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'wclm_license_manager',
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
		);

		register_post_type( 'wclm_license_type', $args );
	}

	public function save_license_type_meta( $post_id ) {
		// Check nonce and permissions here (omitted for brevity)

		if ( isset( $_POST['wclm_site_limit'] ) ) {
			update_post_meta( $post_id, '_wclm_site_limit', intval( $_POST['wclm_site_limit'] ) );
		}

		// Create or update license type in the API
		$license_type_id = get_post_meta( $post_id, '_wclm_license_type_id', true );

		$api_client = new WCLM_API_Client();
		$data       = array(
			'name'        => get_the_title( $post_id ),
			'siteLimit'   => intval( $_POST['wclm_site_limit'] ),
			'description' => '',
		);

		if ( $license_type_id ) {
			// Update existing license type
			$response = $api_client->request( 'PUT', '/api/license-types/' . $license_type_id, $data );
		} else {
			// Create new license type
			$response = $api_client->create_license_type( $data );
			if ( isset( $response['licenseType']['id'] ) ) {
				update_post_meta( $post_id, '_wclm_license_type_id', $response['licenseType']['id'] );
			} else {
				error_log( 'Failed to create license type via API: ' . print_r( $response, true ) );
			}
		}
	}
}

// new WCLM_License_Types();
