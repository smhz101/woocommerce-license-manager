<?php

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	check_admin_referer( 'wclm_save_settings' );

	$api_username = sanitize_text_field( $_POST['api_username'] );
	$api_password = sanitize_text_field( $_POST['api_password'] );

	// Authenticate with the API and get tokens
	$api_client    = new WCLM_API_Client( $api_username, $api_password );
	$auth_response = $api_client->authenticate();

	if ( isset( $auth_response['token'] ) ) {
		// Save credentials and tokens to the database
		update_option(
			'wclm_settings',
			array(
				'api_username'  => $api_username,
				'api_password'  => $api_password,
				'api_token'     => $api_client->get_token(),
				'token_expires' => $api_client->get_token_expires(),
			)
		);
		echo '<div class="updated"><p>' . __( 'Settings saved and authenticated successfully.', 'woocommerce-license-manager' ) . '</p></div>';
	} else {
		echo '<div class="error"><p>' . __( 'Authentication failed. Please check your API credentials.', 'woocommerce-license-manager' ) . '</p></div>';
	}
}

$settings      = get_option( 'wclm_settings' );
$api_username  = isset( $settings['api_username'] ) ? $settings['api_username'] : '';
$api_password  = isset( $settings['api_password'] ) ? $settings['api_password'] : '';
$api_token     = isset( $settings['api_token'] ) ? $settings['api_token'] : '';
$token_expires = isset( $settings['token_expires'] ) ? $settings['token_expires'] : 0;

?>

<?php if ( ! empty( $api_token ) && $token_expires > time() ) : ?>
	<div class="notice notice-success">
		<p><?php _e( 'Connected to License Manager API successfully.', 'woocommerce-license-manager' ); ?></p>
		<p><?php printf( __( 'Token expires on: %s', 'woocommerce-license-manager' ), date( 'Y-m-d H:i:s', $token_expires ) ); ?></p>
	</div>
<?php else : ?>
	<div class="notice notice-error">
		<p><?php _e( 'Not connected to License Manager API.', 'woocommerce-license-manager' ); ?></p>
	</div>
<?php endif; ?>

<form method="post">
	<?php wp_nonce_field( 'wclm_save_settings' ); ?>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="api_username"><?php _e( 'API Username', 'woocommerce-license-manager' ); ?></label>
			</th>
			<td>
				<input type="text" name="api_username" id="api_username" value="<?php echo esc_attr( $api_username ); ?>" class="regular-text" required />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="api_password"><?php _e( 'API Password', 'woocommerce-license-manager' ); ?></label>
			</th>
			<td>
				<input type="password" name="api_password" id="api_password" value="<?php echo esc_attr( $api_password ); ?>" class="regular-text" required />
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Save Settings and Authenticate', 'woocommerce-license-manager' ) ); ?>
</form>
