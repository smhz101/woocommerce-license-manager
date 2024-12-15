<?php
$license_data = $license['license'];
?>
<div class="wrap">
	<h1><?php _e( 'License Details', 'woocommerce-license-manager' ); ?></h1>
	<table class="form-table">
		<tr>
			<th><?php _e( 'License Key', 'woocommerce-license-manager' ); ?></th>
			<td><?php echo esc_html( $license_data['licenseKey'] ); ?></td>
		</tr>
		<tr>
			<th><?php _e( 'Client', 'woocommerce-license-manager' ); ?></th>
			<td><?php echo esc_html( $license_data['client']['name'] ); ?></td>
		</tr>
		<tr>
			<th><?php _e( 'Status', 'woocommerce-license-manager' ); ?></th>
			<td><?php echo esc_html( ucfirst( $license_data['status'] ) ); ?></td>
		</tr>
		<tr>
			<th><?php _e( 'Expires At', 'woocommerce-license-manager' ); ?></th>
			<td><?php echo date( 'Y-m-d', strtotime( $license_data['expiresAt'] ) ); ?></td>
		</tr>
		<!-- Add more fields as needed -->
	</table>
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wclm_license_manager' ) ); ?>" class="button"><?php _e( 'Back to Licenses', 'woocommerce-license-manager' ); ?></a></p>
</div>
