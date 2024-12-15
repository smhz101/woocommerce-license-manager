<?php

$api_client = new WCLM_API_Client();
$summary    = $api_client->request( 'GET', '/api/license-status-summary' );

?>
<div class="wrap">
	<h1><?php _e( 'License Status Summary', 'woocommerce-license-manager' ); ?></h1>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php _e( 'Status', 'woocommerce-license-manager' ); ?></th>
				<th><?php _e( 'Count', 'woocommerce-license-manager' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( isset( $summary['summary'] ) ) : ?>
				<?php foreach ( $summary['summary'] as $item ) : ?>
					<tr>
						<td><?php echo esc_html( ucfirst( $item['status'] ) ); ?></td>
						<td><?php echo esc_html( $item['count'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="2"><?php _e( 'No data available.', 'woocommerce-license-manager' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
