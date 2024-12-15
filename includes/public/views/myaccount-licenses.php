<?php

$current_user = wp_get_current_user();

$api_client = new WCLM_API_Client();

// Get the client's ID from the API based on the user's email
$clients   = $api_client->get_clients();
$client_id = null;

foreach ( $clients['clients'] as $client ) {
	if ( $client['contactEmail'] === $current_user->user_email ) {
		$client_id = $client['id'];
		break;
	}
}

$licenses = array();

if ( $client_id ) {
	// Get licenses for this client
	$all_licenses = $api_client->request( 'GET', '/api/licenses' );
	if ( isset( $all_licenses['licenses'] ) ) {
		foreach ( $all_licenses['licenses'] as $license ) {
			if ( $license['clientId'] == $client_id ) {
				$licenses[] = array(
					'license_key'  => $license['licenseKey'],
					'product_name' => $license['licenseType']['name'],
					'status'       => ucfirst( $license['status'] ),
					'expires_at'   => date( 'Y-m-d', strtotime( $license['expiresAt'] ) ),
				);
			}
		}
	}
}

?>
<h2><?php _e( 'Validate a License', 'woocommerce-license-manager' ); ?></h2>
<form method="post">
	<?php wp_nonce_field( 'wclm_validate_license' ); ?>
	<p>
		<label for="validate_license_key"><?php _e( 'License Key:', 'woocommerce-license-manager' ); ?></label>
		<input type="text" name="validate_license_key" id="validate_license_key" required />
	</p>
	<p>
		<button type="submit" name="wclm_action" value="validate_license" class="button"><?php _e( 'Validate License', 'woocommerce-license-manager' ); ?></button>
	</p>
</form>

<table class="shop_table shop_table_responsive my_account_orders">
	<thead>
		<tr>
			<th><?php _e( 'License Key', 'woocommerce-license-manager' ); ?></th>
			<th><?php _e( 'Product', 'woocommerce-license-manager' ); ?></th>
			<th><?php _e( 'Status', 'woocommerce-license-manager' ); ?></th>
			<th><?php _e( 'Expires At', 'woocommerce-license-manager' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( ! empty( $licenses ) ) : ?>
			<?php foreach ( $licenses as $license ) : ?>
				<tr>
					<td>
						<?php echo esc_html( $license['license_key'] ); ?>
						<form method="post">
								<?php wp_nonce_field( 'wclm_license_action' ); ?>
								<input type="hidden" name="license_key" value="<?php echo esc_attr( $license['license_key'] ); ?>" />
								<?php if ( $license['status'] === 'active' ) : ?>
										<button type="submit" name="wclm_action" value="deactivate" class="button"><?php _e( 'Deactivate', 'woocommerce-license-manager' ); ?></button>
								<?php else : ?>
										<button type="submit" name="wclm_action" value="activate" class="button"><?php _e( 'Activate', 'woocommerce-license-manager' ); ?></button>
								<?php endif; ?>
						</form>
					</td>
					<td><?php echo esc_html( $license['product_name'] ); ?></td>
					<td><?php echo esc_html( $license['status'] ); ?></td>
					<td><?php echo esc_html( $license['expires_at'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="4"><?php _e( 'No licenses found.', 'woocommerce-license-manager' ); ?></td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>
