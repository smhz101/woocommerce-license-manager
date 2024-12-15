<?php

class WCLM_License_Manager {

	private $api_client;

	public function __construct() {
		// Retrieve API credentials from settings
		$settings = get_option( 'wclm_settings' );
		$username = isset( $settings['api_username'] ) ? $settings['api_username'] : '';
		$password = isset( $settings['api_password'] ) ? $settings['api_password'] : '';

		$this->api_client = new WCLM_API_Client( $username, $password );

		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_license_duration_field' ) );
	}

	// Add license duration field to product page
	public function add_license_duration_field() {
		global $product;

		if ( $product->is_type( 'variable' ) ) {
			// For variable products, we use JavaScript to display the license duration field
			echo '<div id="wclm_license_duration_field"></div>';
		} else {
			$is_license_product = get_post_meta( $product->get_id(), '_wclm_is_license_product', true ) === 'yes';
			if ( $is_license_product ) {
				$default_duration = get_post_meta( $product->get_id(), '_wclm_default_license_duration', true );
				if ( empty( $default_duration ) ) {
					$default_duration = 1;
				}
				echo '<div class="license-duration">
                    <label for="license_duration">' . __( 'License Duration (Years):', 'woocommerce-license-manager' ) . '</label>
                    <input type="number" id="license_duration" name="license_duration" value="' . esc_attr( $default_duration ) . '" min="1" max="5" />
                  </div>';
			}
		}
	}

	// Capture license duration in cart item data
	public function add_cart_item_data( $cart_item_data, $product_id ) {
		if ( isset( $_POST['license_duration'] ) && ! empty( $_POST['license_duration'] ) ) {
			$cart_item_data['license_duration'] = intval( $_POST['license_duration'] );
			$cart_item_data['unique_key']       = md5( microtime() . rand() );
		}
		return $cart_item_data;
	}

	// Adjust price based on license duration
	public function adjust_cart_item_price( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['license_duration'] ) ) {
				$product        = $cart_item['data'];
				$original_price = $product->get_price();

				// Check if the product is variable and get the variation price
				if ( $product->is_type( 'variation' ) ) {
					$original_price = $product->get_price();
				}

				$duration  = $cart_item['license_duration'];
				$new_price = $original_price * $duration;
				$product->set_price( $new_price );
			}
		}
	}

	// Display license duration in cart and checkout
	public function display_license_duration_in_cart( $item_data, $cart_item ) {
		if ( isset( $cart_item['license_duration'] ) ) {
			$item_data[] = array(
				'key'   => __( 'License Duration', 'woocommerce-license-manager' ),
				'value' => $cart_item['license_duration'] . ' ' . __( 'Year(s)', 'woocommerce-license-manager' ),
			);
		}
		return $item_data;
	}

	// Save license duration to order items
	public function add_license_duration_to_order_items( $item, $cart_item_key, $values, $order ) {
		if ( isset( $values['license_duration'] ) ) {
			$item->add_meta_data( '_license_duration', $values['license_duration'], true );
		}
	}

	// Process order and generate license.
	public function process_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			error_log( 'Invalid Order ID: ' . $order_id );
			return;
		}

		$items = $order->get_items();
		foreach ( $items as $item_id => $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				error_log( 'Item does not have a valid product. Order ID: ' . $order_id . ', Item ID: ' . $item_id );
				continue;
			}

			$product_id         = $product->get_id();
			$is_license_product = get_post_meta( $product_id, '_wclm_is_license_product', true ) === 'yes';

			if ( $is_license_product ) {
				// Get variation ID.
				$variation_id    = $item->get_variation_id();
				$license_type_id = null;

				if ( $variation_id ) {
					// Get licenseTypeId from variation meta.
					$license_type_id = get_post_meta( $variation_id, '_wclm_license_type_id', true );
				} else {
					// For simple products.
					$license_type_id = get_post_meta( $product_id, '_wclm_license_type_id', true );
				}

				if ( ! $license_type_id ) {
					error_log( 'No license type selected for product ID: ' . $product_id );
					$order->add_order_note( __( 'License creation failed: No license type selected for product.', 'woocommerce-license-manager' ) );
					continue;
				}

				$user = get_userdata( $order->get_user_id() );

				if ( ! $product || ! $user ) {
					$this->add_notice( __( 'Invalid product or user selected.', 'woocommerce-license-manager' ), 'error' );
					return;
				}

				// Select the primary role for the user.
				$primary_role = ! empty( $user->roles ) ? $user->roles[0] : 'customer';

				// Prepare data for API call.
				$data = array(
					'licenseTypeId' => intval( $license_type_id ),
					'clientId'      => $this->get_or_create_client( $order ),
					'expiresAt'     => $this->calculate_expiry_date_from_license_type( $license_type_id ),
					'status'        => 'active',
					'product'       => array(
						'productId'    => $product_id,
						'productName'  => $product->get_name(),
						'productSku'   => $product->get_sku(),
						'productPrice' => $product->get_price(),
						'productType'  => $product->get_type(),
					),
					'user'          => array(
						'name'         => $user->display_name,
						'userName'     => $user->user_login,
						'userEmail'    => $user->user_email,
						'contactPhone' => $user->get( 'billing_phone' ) ?: '',
						'address'      => $user->get( 'billing_address_1' ) ?: '',
						'userRoles'    => $primary_role,
					),
				);

				error_log( 'License data prepared: ' . print_r( $data, true ) );

				$response = $this->api_client->create_license( $data );

				if ( ! $response ) {
					error_log( 'API response is empty or null. Data: ' . print_r( $data, true ) );
				}

				if ( isset( $response['license']['licenseKey'] ) ) {
					// Save license key to order item meta.
					$item->add_meta_data( 'License Key', $response['license']['licenseKey'], true );
					$item->save();

					// Send license key to customer.
					$this->send_license_email( $order, $response['license']['licenseKey'] );
					error_log( 'License key created successfully for Order ID: ' . $order_id . ', License Key: ' . $response['license']['licenseKey'] );
				} else {
					$error_message = isset( $response['message'] ) ? $response['message'] : __( 'An unknown error occurred.', 'woocommerce-license-manager' );
					error_log( 'License Creation Failed for Order ID: ' . $order_id . ', Product ID: ' . $product_id . ', Error: ' . $error_message );
					$order->add_order_note( __( 'License creation failed: ', 'woocommerce-license-manager' ) . $error_message );
				}
			} else {
				error_log( 'Product is not a license product. Order ID: ' . $order_id . ', Product ID: ' . $product_id );
			}
		}
	}

	private function calculate_expiry_date_from_license_type( $licenseTypeId ) {
		// Fetch license type details from API
		$license_type = $this->api_client->get_license_type( $licenseTypeId );

		if ( isset( $license_type['licenseType']['name'] ) ) {
			// Attempt to extract duration from the name
			$name = $license_type['licenseType']['name'];
			preg_match( '/(\d+)\s*Year/', $name, $matches );
			if ( isset( $matches[1] ) ) {
				$duration_years = intval( $matches[1] );
			} else {
				$duration_years = 1; // Default duration if not specified
			}
		} else {
			$duration_years = 1; // Default duration if not specified
		}

		$expires_at = date( 'Y-m-d\TH:i:s\Z', strtotime( '+' . $duration_years . ' years' ) );
		return $expires_at;
	}

	private function get_license_type_id( $license_sites ) {
		// Implement logic to retrieve or create license types via API
		// For simplicity, assume licenseTypeId is the same as site limit
		$license_types = $this->api_client->get_license_types();

		foreach ( $license_types['licenseTypes'] as $type ) {
			if ( intval( $type['siteLimit'] ) === intval( $license_sites ) ) {
				return $type['id'];
			}
		}

		// If not found, create a new license type
		$new_type = $this->api_client->create_license_type(
			array(
				'name'        => $license_sites . '-Site License',
				'siteLimit'   => intval( $license_sites ),
				'description' => 'License for ' . $license_sites . ' sites.',
			)
		);

		return $new_type['licenseType']['id'];
	}

	/**
	 * Get client object or create a new one.
	 *
	 * This method checks if a client exists based on the user ID or email
	 * associated with the WooCommerce order. If the client does not exist,
	 * it creates a new client via an external API.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 *
	 * @return int|null The client ID if successfully retrieved or created, or null on failure.
	 */
	private function get_or_create_client( $order ) {
		// Check if client exists.
		$user_id = $order->get_user_id();
		$email   = $order->get_billing_email();
		$phone   = $order->get_billing_phone();
		$wp_user = get_userdata( $user_id );

		if ( ! $wp_user ) {
			error_log( "Error: User not found for user ID {$user_id}." );
			return null;
		}

		// Get client from the API.
		$client = $this->api_client->get_client_by_id( $user_id );

		// If client does not exist, create a new one.
		if ( ! $client ) {
			$_user_object = array(
				'name'          => $wp_user->display_name,
				'username'      => $wp_user->user_login,
				'contact_email' => $email,
				'contact_phone' => $phone ? $phone : null,
				'address'       => $order->get_billing_address_1(),
				'role'          => implode( ',', $wp_user->roles ),
			);

			$new_client = $this->api_client->create_client( $_user_object );

			error_log( "1 ::: $new_client>>>>>> " . print_r( $new_client, true ) );

			if ( isset( $new_client['client']['id'] ) ) {
				return $new_client['client']['id'];
			} else {
				$error_details = isset( $new_client['error'] ) ? json_encode( $new_client['error'], JSON_PRETTY_PRINT ) : 'Unknown error.';
				error_log(
					'Error: Failed to create new client. Provided data: ' .
					json_encode( $_user_object, JSON_PRETTY_PRINT ) .
					'. API response: ' . $error_details
				);
				return null;
			}
		}

		error_log( "2 ::: $client>>>>>> " . print_r( $client, true ) );

		// Return the existing client ID.
		if ( isset( $client['id'] ) ) {
			return $client['id'];
		} else {
			error_log( 'Error: Retrieved client data does not contain an ID. Response: ' . print_r( $client, true ) );
			return null;
		}
	}


	private function calculate_expiry_date( $license_duration ) {
		$current_date = new DateTime();
		$current_date->modify( '+' . intval( $license_duration ) . ' year' );
		return $current_date->format( 'Y-m-d\TH:i:s\Z' );
	}

	private function send_license_email( $order, $license_key ) {
		$to       = $order->get_billing_email();
		$subject  = __( 'Your License Key', 'woocommerce-license-manager' );
		$message  = __( 'Thank you for your purchase. Here is your license key:', 'woocommerce-license-manager' ) . "\n\n";
		$message .= $license_key;

		wp_mail( $to, $subject, $message );
	}
}
