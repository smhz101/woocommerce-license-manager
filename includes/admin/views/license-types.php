<?php

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	check_admin_referer( 'wclm_save_license_type' );

	$license_type_name   = sanitize_text_field( $_POST['license_type_name'] );
	$license_site_limit  = intval( $_POST['license_site_limit'] );
	$license_description = sanitize_textarea_field( $_POST['license_description'] );

	$api_client = new WCLM_API_Client();
	$data       = array(
		'name'        => $license_type_name,
		'siteLimit'   => $license_site_limit,
		'description' => $license_description,
	);

	$response = $api_client->create_license_type( $data );

	if ( isset( $response['licenseType']['id'] ) ) {
		echo '<div class="updated"><p>' . __( 'License Type created successfully.', 'woocommerce-license-manager' ) . '</p></div>';
	} else {
		$error_message = isset( $response['message'] ) ? $response['message'] : __( 'An unknown error occurred.', 'woocommerce-license-manager' );
		echo '<div class="error"><p>' . sprintf( __( 'Failed to create License Type: %s', 'woocommerce-license-manager' ), $error_message ) . '</p></div>';
	}
}

// Fetch existing license types
$api_client    = new WCLM_API_Client();
$license_types = $api_client->get_license_types();

?>

<h2><?php _e( 'License Types', 'woocommerce-license-manager' ); ?></h2>

<h3><?php _e( 'Existing License Types', 'woocommerce-license-manager' ); ?></h3>

<?php if ( isset( $license_types['licenseTypes'] ) && ! empty( $license_types['licenseTypes'] ) ) : ?>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php _e( 'ID', 'woocommerce-license-manager' ); ?></th>
				<th><?php _e( 'Name', 'woocommerce-license-manager' ); ?></th>
				<th><?php _e( 'Site Limit', 'woocommerce-license-manager' ); ?></th>
				<th><?php _e( 'Description', 'woocommerce-license-manager' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $license_types['licenseTypes'] as $license_type ) : ?>
				<tr>
					<td><?php echo esc_html( $license_type['id'] ); ?></td>
					<td><?php echo esc_html( $license_type['name'] ); ?></td>
					<td><?php echo esc_html( $license_type['siteLimit'] ); ?></td>
					<td><?php echo esc_html( $license_type['description'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php else : ?>
	<p><?php _e( 'No License Types found.', 'woocommerce-license-manager' ); ?></p>
<?php endif; ?>

<h3><?php _e( 'Create New License Type', 'woocommerce-license-manager' ); ?></h3>

<form method="post">
	<?php wp_nonce_field( 'wclm_save_license_type' ); ?>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="license_type_name"><?php _e( 'Name', 'woocommerce-license-manager' ); ?></label>
			</th>
			<td>
				<input type="text" name="license_type_name" id="license_type_name" class="regular-text" required />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="license_site_limit"><?php _e( 'Site Limit', 'woocommerce-license-manager' ); ?></label>
			</th>
			<td>
				<input type="number" name="license_site_limit" id="license_site_limit" class="small-text" min="1" step="1" required />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="license_description"><?php _e( 'Description', 'woocommerce-license-manager' ); ?></label>
			</th>
			<td>
				<textarea name="license_description" id="license_description" class="large-text" rows="3"></textarea>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Create License Type', 'woocommerce-license-manager' ) ); ?>
</form>