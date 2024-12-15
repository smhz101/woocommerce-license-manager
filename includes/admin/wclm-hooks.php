<?php

add_action( 'wp_ajax_get_variations', 'wclm_get_variations' );

function wclm_get_variations() {
		$product_id = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0;

	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'woocommerce-license-manager' ) ) );
	}

		$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		wp_send_json_error( array( 'message' => __( 'No variations available.', 'woocommerce-license-manager' ) ) );
	}

		$variations = array();
	foreach ( $product->get_available_variations() as $variation ) {
		$variations[] = array(
			'id'   => $variation['variation_id'],
			'name' => wc_get_formatted_variation( new WC_Product_Variation( $variation['variation_id'] ), true ),
		);
	}

		wp_send_json_success( array( 'variations' => $variations ) );
}