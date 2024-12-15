<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WCLM_Product_Settings {

	public function __construct() {
		// Add a custom tab
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_license_product_data_tab' ) );
		// Add custom fields to the custom tab
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_license_product_data_fields' ) );
		// Save custom fields
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_license_product_data_fields' ) );

		// For variations
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_license_fields_to_variations' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_license_fields_in_variations' ), 10, 2 );
		add_filter( 'woocommerce_available_variation', array( $this, 'add_custom_data_to_variations' ) );
	}

	// Add a custom tab
	public function add_license_product_data_tab( $tabs ) {
		$tabs['wclm_license_tab'] = array(
			'label'    => __( 'License Settings', 'woocommerce-license-manager' ),
			'target'   => 'wclm_license_product_data',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 21,
		);
		return $tabs;
	}

	// Add custom fields to the custom tab
	public function add_license_product_data_fields() {
		global $post;

		// Fetch license types from API
		$api_client    = new WCLM_API_Client();
		$license_types = $api_client->get_license_types();

		$options = array( '' => __( 'Select a License Type', 'woocommerce-license-manager' ) );
		if ( isset( $license_types['licenseTypes'] ) && ! empty( $license_types['licenseTypes'] ) ) {
			foreach ( $license_types['licenseTypes'] as $license_type ) {
				$options[ $license_type['id'] ] = $license_type['name'];
			}
		}

		// Get selected license type
		$selected_license_type = get_post_meta( $post->ID, '_wclm_license_type_id', true );

		?>
		<div id="wclm_license_product_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				// Checkbox to enable license for this product
				woocommerce_wp_checkbox(
					array(
						'id'          => '_wclm_is_license_product',
						'label'       => __( 'Enable License for this Product', 'woocommerce-license-manager' ),
						'description' => __( 'Check this to enable license management for this product.', 'woocommerce-license-manager' ),
					)
				);

				// Number input for license sites limit
				woocommerce_wp_text_input(
					array(
						'id'                => '_wclm_license_sites_limit',
						'label'             => __( 'License Sites Limit', 'woocommerce-license-manager' ),
						'description'       => __( 'The number of sites this license will cover.', 'woocommerce-license-manager' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
					)
				);

				// Number input for default license duration
				woocommerce_wp_text_input(
					array(
						'id'                => '_wclm_default_license_duration',
						'label'             => __( 'Default License Duration (Years)', 'woocommerce-license-manager' ),
						'description'       => __( 'The default duration of the license in years.', 'woocommerce-license-manager' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
					)
				);

				// Dropdown to select license type
				woocommerce_wp_select(
					array(
						'id'          => '_wclm_license_type_id',
						'label'       => __( 'License Type', 'woocommerce-license-manager' ),
						'description' => __( 'Select the license type for this product.', 'woocommerce-license-manager' ),
						'options'     => $options,
						'value'       => $selected_license_type,
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	// Save custom fields
	public function save_license_product_data_fields( $post_id ) {
		$is_license_product = isset( $_POST['_wclm_is_license_product'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wclm_is_license_product', $is_license_product );

		if ( isset( $_POST['_wclm_license_sites_limit'] ) ) {
			update_post_meta( $post_id, '_wclm_license_sites_limit', sanitize_text_field( $_POST['_wclm_license_sites_limit'] ) );
		}

		if ( isset( $_POST['_wclm_default_license_duration'] ) ) {
			update_post_meta( $post_id, '_wclm_default_license_duration', sanitize_text_field( $_POST['_wclm_default_license_duration'] ) );
		}

		if ( isset( $_POST['_wclm_license_type_id'] ) ) {
			update_post_meta( $post_id, '_wclm_license_type_id', sanitize_text_field( $_POST['_wclm_license_type_id'] ) );
		}
	}

	// Add license fields to variations
	public function add_license_fields_to_variations( $loop, $variation_data, $variation ) {

		// Fetch license types from API
		$api_client    = new WCLM_API_Client();
		$license_types = $api_client->get_license_types();

		$options = array( '' => __( 'Select a License Type', 'woocommerce-license-manager' ) );
		if ( isset( $license_types['licenseTypes'] ) && ! empty( $license_types['licenseTypes'] ) ) {
			foreach ( $license_types['licenseTypes'] as $license_type ) {
				$options[ $license_type['id'] ] = $license_type['name'];
			}
		}

		// Get selected license type
		$selected_license_type = get_post_meta( $variation->ID, '_wclm_license_type_id', true );

		?>
		<div class="form-row form-row-first">
			<?php
			// Dropdown to select license type
			woocommerce_wp_select(
				array(
					'id'          => '_wclm_license_type_id_' . $variation->ID,
					'name'        => '_wclm_license_type_id[' . $variation->ID . ']',
					'label'       => __( 'License Type', 'woocommerce-license-manager' ),
					'description' => __( 'Select the license type for this variation.', 'woocommerce-license-manager' ),
					'options'     => $options,
					'value'       => $selected_license_type,
				)
			);
			?>
		</div>
		<div class="form-row form-row-full">
			<?php
			woocommerce_wp_checkbox(
				array(
					'id'    => '_wclm_is_license_product_' . $variation->ID,
					'name'  => '_wclm_is_license_product[' . $variation->ID . ']',
					'label' => __( 'Enable License for this Variation', 'woocommerce-license-manager' ),
					'value' => get_post_meta( $variation->ID, '_wclm_is_license_product', true ),
				)
			);
			?>
		</div>
		<div class="form-row form-row-first">
			<?php
			woocommerce_wp_text_input(
				array(
					'id'                => '_wclm_license_sites_limit_' . $variation->ID,
					'name'              => '_wclm_license_sites_limit[' . $variation->ID . ']',
					'label'             => __( 'License Sites Limit', 'woocommerce-license-manager' ),
					'value'             => get_post_meta( $variation->ID, '_wclm_license_sites_limit', true ),
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => '1',
						'step' => '1',
					),
				)
			);
			?>
		</div>
		<div class="form-row form-row-last">
			<?php
			woocommerce_wp_text_input(
				array(
					'id'                => '_wclm_default_license_duration_' . $variation->ID,
					'name'              => '_wclm_default_license_duration[' . $variation->ID . ']',
					'label'             => __( 'Default License Duration (Years)', 'woocommerce-license-manager' ),
					'value'             => get_post_meta( $variation->ID, '_wclm_default_license_duration', true ),
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => '1',
						'step' => '1',
					),
				)
			);
			?>
		</div>
		<?php
	}

	// Save variation custom fields
	public function save_license_fields_in_variations( $variation_id, $i ) {
		$is_license_product = isset( $_POST['_wclm_is_license_product'][ $variation_id ] ) ? 'yes' : 'no';
		update_post_meta( $variation_id, '_wclm_is_license_product', $is_license_product );

		if ( isset( $_POST['_wclm_license_sites_limit'][ $variation_id ] ) ) {
			update_post_meta( $variation_id, '_wclm_license_sites_limit', sanitize_text_field( $_POST['_wclm_license_sites_limit'][ $variation_id ] ) );
		}

		if ( isset( $_POST['_wclm_default_license_duration'][ $variation_id ] ) ) {
			update_post_meta( $variation_id, '_wclm_default_license_duration', sanitize_text_field( $_POST['_wclm_default_license_duration'][ $variation_id ] ) );
		}

		if ( isset( $_POST['_wclm_license_type_id'][ $variation_id ] ) ) {
			update_post_meta( $variation_id, '_wclm_license_type_id', sanitize_text_field( $_POST['_wclm_license_type_id'][ $variation_id ] ) );
		}
	}

	// Add custom data to variations on the front end
	public function add_custom_data_to_variations( $variation ) {
		$variation['is_license_product']       = get_post_meta( $variation['variation_id'], '_wclm_is_license_product', true );
		$variation['license_sites_limit']      = get_post_meta( $variation['variation_id'], '_wclm_license_sites_limit', true );
		$variation['default_license_duration'] = get_post_meta( $variation['variation_id'], '_wclm_default_license_duration', true );
		return $variation;
	}
}

new WCLM_Product_Settings();
