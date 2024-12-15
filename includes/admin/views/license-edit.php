<?php
$license_data = $license['license'];
?>
<div class="wrap">
	<h1><?php _e( 'Edit License', 'woocommerce-license-manager' ); ?></h1>
	<form method="post">
		<?php wp_nonce_field( 'wclm_edit_license' ); ?>
		<table class="form-table">
			<tr>
				<th><?php _e( 'License Key', 'woocommerce-license-manager' ); ?></th>
				<td><?php echo esc_html( $license_data['licenseKey'] ); ?></td>
			</tr>
			<tr>
				<th><label for="status"><?php _e( 'Status', 'woocommerce-license-manager' ); ?></label></th>
				<td>
					<select name="status" id="status">
						<?php
						$statuses = array( 'active', 'expired', 'revoked', 'deactivated' );
						foreach ( $statuses as $status ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $status ),
								selected( $license_data['status'], $status, false ),
								esc_html( ucfirst( $status ) )
							);
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="expiresAt"><?php _e( 'Expires At', 'woocommerce-license-manager' ); ?></label></th>
				<td>
					<input type="date" name="expiresAt" id="expiresAt" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( $license_data['expiresAt'] ) ) ); ?>" />
				</td>
			</tr>
			<!-- Add more editable fields as needed -->
		</table>
		<?php submit_button( __( 'Save Changes', 'woocommerce-license-manager' ) ); ?>
	</form>
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wclm_license_manager' ) ); ?>" class="button"><?php _e( 'Back to Licenses', 'woocommerce-license-manager' ); ?></a></p>
</div>
