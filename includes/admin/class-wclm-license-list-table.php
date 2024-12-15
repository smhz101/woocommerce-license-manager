<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WCLM_License_List_Table extends WP_List_Table {

	private $api_client;
	private $notices = array();

	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'License', 'woocommerce-license-manager' ),
				'plural'   => __( 'Licenses', 'woocommerce-license-manager' ),
				'ajax'     => false,
			)
		);

		// Initialize API client.
		$settings = get_option( 'wclm_settings' );
		$username = isset( $settings['api_username'] ) ? $settings['api_username'] : '';
		$password = isset( $settings['api_password'] ) ? $settings['api_password'] : '';

		$this->api_client = new WCLM_API_Client( $username, $password );

		// Process any actions.
		$this->process_bulk_action();
	}

	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'licenseKey' => __( 'License Key', 'woocommerce-license-manager' ),
			'clientName' => __( 'Client', 'woocommerce-license-manager' ),
			'status'     => __( 'Status', 'woocommerce-license-manager' ),
			'expiresAt'  => __( 'Expiry Date', 'woocommerce-license-manager' ),
		);
		return $columns;
	}

	protected function get_primary_column_name() {
		return 'licenseKey';
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="license[]" value="%s" />',
			$item['id']
		);
	}

	public function get_bulk_actions() {
		$actions = array(
			'renew'  => __( 'Renew', 'woocommerce-license-manager' ),
			'revoke' => __( 'Revoke', 'woocommerce-license-manager' ),
		);
		return $actions;
	}

	public function get_views() {
		$status_counts  = $this->get_status_counts();
		$current_status = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : 'all';

		$status_labels = array(
			'all'     => __( 'All', 'woocommerce-license-manager' ),
			'active'  => __( 'Active', 'woocommerce-license-manager' ),
			'expired' => __( 'Expired', 'woocommerce-license-manager' ),
			'revoked' => __( 'Revoked', 'woocommerce-license-manager' ),
		);

		$views = array();

		foreach ( $status_counts as $status => $count ) {
			$class = ( $current_status === $status ) ? 'current' : '';

			if ( $status === 'all' ) {
				$url = esc_url( remove_query_arg( 'status' ) );
			} else {
				$url = esc_url( add_query_arg( 'status', $status ) );
			}

			$label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucfirst( $status );

			$views[ $status ] = sprintf(
				'<a href="%s" class="%s">%s (%d)</a>',
				$url,
				$class,
				$label,
				$count
			);
		}

		return $views;
	}

	private function get_status_counts() {
		$counts = array();

		// Fetch all licenses from API,
		$licenses = $this->api_client->get_all_licenses();

		if ( isset( $licenses['licenses'] ) ) {
			$counts['all'] = count( $licenses['licenses'] );
			foreach ( $licenses['licenses'] as $license ) {
				$status = strtolower( $license['status'] );
				if ( isset( $counts[ $status ] ) ) {
					++$counts[ $status ];
				} else {
					$counts[ $status ] = 1;
				}
			}
		}

		// Ensure all expected statuses have a count, even if zero.
		$expected_statuses = array( 'all', 'active', 'expired', 'revoked' );
		foreach ( $expected_statuses as $status ) {
			if ( ! isset( $counts[ $status ] ) ) {
				$counts[ $status ] = 0;
			}
		}

		return $counts;
	}

	public function prepare_items() {
		$per_page     = 20; // Set the number of items per page.
		$current_page = $this->get_pagenum();

		// Get the current status filter.
		$current_status = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : 'all';

		// Fetch licenses from API.
		$licenses = $this->api_client->get_all_licenses();

		$data = array();

		if ( isset( $licenses['licenses'] ) ) {
			foreach ( $licenses['licenses'] as $license ) {
				$license_status = strtolower( $license['status'] );

				// Apply status filter
				if ( $current_status === 'all' || $current_status === $license_status ) {
					$data[] = array(
						'licenseKey' => $license['licenseKey'],
						'clientName' => isset( $license['client']['name'] ) ? $license['client']['name'] : '',
						'status'     => ucfirst( $license['status'] ),
						'expiresAt'  => date( 'Y-m-d', strtotime( $license['expiresAt'] ) ),
						'id'         => $license['id'],
					);
				}
			}
		}

		/*** Search Handling */
		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
			$search = strtolower( $_REQUEST['s'] );
			$data   = array_filter(
				$data,
				function ( $item ) use ( $search ) {
					if ( strpos( strtolower( $item['licenseKey'] ), $search ) !== false ) {
						return true;
					}
					if ( strpos( strtolower( $item['clientName'] ), $search ) !== false ) {
						return true;
					}
					if ( strpos( strtolower( $item['status'] ), $search ) !== false ) {
						return true;
					}
					return false;
				}
			);
		}

		/*** Sorting */
		$sortable = $this->get_sortable_columns();
		$orderby  = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'licenseKey';
		$order    = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';

		usort(
			$data,
			function ( $a, $b ) use ( $orderby, $order ) {
				$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
				return ( $order === 'asc' ) ? $result : -$result;
			}
		);

		/*** Pagination */
		$total_items = count( $data );

		$data = array_slice( $data, ( $current_page - 1 ) * $per_page, $per_page );

		$this->items = $data;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'licenseKey' => array( 'licenseKey', false ),
			'clientName' => array( 'clientName', false ),
			'status'     => array( 'status', false ),
			'expiresAt'  => array( 'expiresAt', false ),
		);
		return $sortable_columns;
	}

	public function no_items() {
		_e( 'No licenses found.', 'woocommerce-license-manager' );
	}

	public function process_bulk_action() {
		// Handle bulk actions
		if ( ( isset( $_POST['action'] ) && $_POST['action'] != -1 ) || ( isset( $_POST['action2'] ) && $_POST['action2'] != -1 ) ) {
			$action      = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$license_ids = isset( $_POST['license'] ) ? array_map( 'intval', $_POST['license'] ) : array();

			if ( ! empty( $license_ids ) ) {
				foreach ( $license_ids as $license_id ) {
					switch ( $action ) {
						case 'renew':
							$this->renew_license( $license_id );
							break;
						case 'revoke':
							$this->revoke_license( $license_id );
							break;
					}
				}
			}
		}

		// Handle single actions
		if ( isset( $_GET['action'] ) && isset( $_GET['license'] ) ) {
			$action     = sanitize_text_field( $_GET['action'] );
			$license_id = intval( $_GET['license'] );

			switch ( $action ) {
				case 'renew':
					$this->renew_license( $license_id );
					break;
				case 'revoke':
					$this->revoke_license( $license_id );
					break;
				case 'notify':
					$this->notify_client( $license_id );
					break;
				case 'view':
					$this->view_license( $license_id );
					exit;
				case 'edit':
					$this->edit_license( $license_id );
					exit;
			}
		}
	}

	private function add_notice( $message, $type = 'success' ) {
		$this->notices[] = array(
			'message' => $message,
			'type'    => $type,
		);
	}

	public function display_notices() {
		foreach ( $this->notices as $notice ) {
			printf(
				'<div class="%1$s notice is-dismissible"><p>%2$s</p></div>',
				esc_attr( $notice['type'] === 'error' ? 'notice-error' : 'notice-success' ),
				esc_html( $notice['message'] )
			);
		}
	}

	private function renew_license( $license_id ) {
		// Fetch the license.
		$license = $this->api_client->request( 'GET', '/api/licenses/' . intval( $license_id ) );
		if ( isset( $license['license'] ) ) {
			// Extend the license expiration date by 1 year.
			$expires_at = new DateTime( $license['license']['expiresAt'] );
			$expires_at->modify( '+1 year' );

			$data = array(
				'expiresAt' => $expires_at->format( 'Y-m-d\TH:i:s\Z' ),
			);

			$response = $this->api_client->request( 'PUT', '/api/licenses/' . intval( $license_id ), $data );

			if ( isset( $response['license'] ) ) {
				$this->add_notice( __( 'License renewed successfully.', 'woocommerce-license-manager' ) );
			} else {
				$error_message = isset( $response['message'] ) ? $response['message'] : __( 'Failed to renew license.', 'woocommerce-license-manager' );
				$this->add_notice( $error_message, 'error' );
			}
		} else {
			$this->add_notice( __( 'License not found.', 'woocommerce-license-manager' ), 'error' );
		}
	}

	private function revoke_license( $license_id ) {
		$data = array(
			'status' => 'revoked',
		);

		$response = $this->api_client->request( 'PUT', '/api/licenses/' . intval( $license_id ), $data );

		if ( isset( $response['license'] ) ) {
			$this->add_notice( __( 'License revoked successfully.', 'woocommerce-license-manager' ) );
		} else {
			$error_message = isset( $response['message'] ) ? $response['message'] : __( 'Failed to revoke license.', 'woocommerce-license-manager' );
			$this->add_notice( $error_message, 'error' );
		}
	}

	private function notify_client( $license_id ) {
		// Fetch the license
		$license = $this->api_client->request( 'GET', '/api/licenses/' . intval( $license_id ) );
		if ( isset( $license['license'] ) ) {
			$client_email = isset( $license['license']['client']['contact_email'] ) ? $license['license']['client']['contact_email'] : '';

			if ( $client_email ) {
				// Prepare email content
				$subject = __( 'License Notification', 'woocommerce-license-manager' );
				$message = sprintf( __( 'Dear customer, your license %s has an update.', 'woocommerce-license-manager' ), $license['license']['licenseKey'] );
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );

				// Send email
				wp_mail( $client_email, $subject, $message, $headers );

				$this->add_notice( __( 'Client notified successfully.', 'woocommerce-license-manager' ) );
			} else {
				$this->add_notice( __( 'Client email not available.', 'woocommerce-license-manager' ), 'error' );
			}
		} else {
			$this->add_notice( __( 'License not found.', 'woocommerce-license-manager' ), 'error' );
		}
	}

	private function view_license( $license_id ) {
		// Fetch the license
		$license = $this->api_client->request( 'GET', '/api/licenses/' . intval( $license_id ) );
				echo '<pre>';
				var_export( $license );
				echo '</pre>';

		if ( isset( $license['license'] ) ) {
			// Display license details
			echo '<div class="wrap">';
			echo '<h1>' . __( 'View License', 'woocommerce-license-manager' ) . '</h1>';
			echo '<table class="form-table">';
			echo '<tr><th>' . __( 'License Key', 'woocommerce-license-manager' ) . '</th><td>' . esc_html( $license['license']['licenseKey'] ) . '</td></tr>';
			echo '<tr><th>' . __( 'Client Name', 'woocommerce-license-manager' ) . '</th><td>' . esc_html( $license['license']['client']['name'] ) . '</td></tr>';
			echo '<tr><th>' . __( 'Client Email', 'woocommerce-license-manager' ) . '</th><td>' . esc_html( $license['license']['client']['contact_email'] ) . '</td></tr>';
			echo '<tr><th>' . __( 'Status', 'woocommerce-license-manager' ) . '</th><td>' . esc_html( ucfirst( $license['license']['status'] ) ) . '</td></tr>';
			echo '<tr><th>' . __( 'Expires At', 'woocommerce-license-manager' ) . '</th><td>' . esc_html( date( 'Y-m-d', strtotime( $license['license']['expiresAt'] ) ) ) . '</td></tr>';
			// Add more fields as needed
			echo '</table>';
			echo '<a href="' . admin_url( 'admin.php?page=' . $_REQUEST['page'] ) . '" class="button">' . __( 'Back to Licenses', 'woocommerce-license-manager' ) . '</a>';
			echo '</div>';
		} else {
			$this->add_notice( __( 'License not found.', 'woocommerce-license-manager' ), 'error' );
			$this->display_notices();
		}
	}

	private function edit_license( $license_id ) {
		// Check if the form is submitted
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'wclm_edit_license' ) ) {
			$status     = sanitize_text_field( $_POST['status'] );
			$expires_at = sanitize_text_field( $_POST['expiresAt'] );

			$data = array(
				'status'    => $status,
				'expiresAt' => date( 'Y-m-d\TH:i:s\Z', strtotime( $expires_at ) ),
			);

			$response = $this->api_client->request( 'PUT', '/api/licenses/' . intval( $license_id ), $data );

			if ( isset( $response['license'] ) ) {
				$this->add_notice( __( 'License updated successfully.', 'woocommerce-license-manager' ) );
			} else {
				$error_message = isset( $response['message'] ) ? $response['message'] : __( 'Failed to update license.', 'woocommerce-license-manager' );
				$this->add_notice( $error_message, 'error' );
			}
		}

		// Fetch the license
		$license = $this->api_client->request( 'GET', '/api/licenses/' . intval( $license_id ) );
		if ( isset( $license['license'] ) ) {
			// Display edit form
			echo '<div class="wrap">';
			echo '<h1>' . __( 'Edit License', 'woocommerce-license-manager' ) . '</h1>';
			echo '<form method="post">';
			wp_nonce_field( 'wclm_edit_license' );
			echo '<table class="form-table">';
			echo '<tr><th>' . __( 'License Key', 'woocommerce-license-manager' ) . '</th><td>' . esc_html( $license['license']['licenseKey'] ) . '</td></tr>';
			echo '<tr><th><label for="status">' . __( 'Status', 'woocommerce-license-manager' ) . '</label></th><td>';
			echo '<select name="status" id="status">';
			$statuses = array(
				'active'  => __( 'Active', 'woocommerce-license-manager' ),
				'expired' => __( 'Expired', 'woocommerce-license-manager' ),
				'revoked' => __( 'Revoked', 'woocommerce-license-manager' ),
			);
			foreach ( $statuses as $value => $label ) {
				$selected = ( strtolower( $license['license']['status'] ) === $value ) ? 'selected' : '';
				echo '<option value="' . esc_attr( $value ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select></td></tr>';
			echo '<tr><th><label for="expiresAt">' . __( 'Expires At', 'woocommerce-license-manager' ) . '</label></th><td>';
			echo '<input type="date" name="expiresAt" id="expiresAt" value="' . esc_attr( date( 'Y-m-d', strtotime( $license['license']['expiresAt'] ) ) ) . '" /></td></tr>';
			// Add more fields as needed
			echo '</table>';
			submit_button( __( 'Update License', 'woocommerce-license-manager' ) );
			echo '</form>';
			echo '<a href="' . admin_url( 'admin.php?page=' . $_REQUEST['page'] ) . '" class="button">' . __( 'Back to Licenses', 'woocommerce-license-manager' ) . '</a>';
			echo '</div>';
		} else {
			$this->add_notice( __( 'License not found.', 'woocommerce-license-manager' ), 'error' );
			$this->display_notices();
		}
	}

	public function column_licenseKey( $item ) {
		$actions = array(
			'view'   => sprintf(
				'<a href="?page=%s&action=%s&license=%s">' . __( 'View', 'woocommerce-license-manager' ) . '</a>',
				esc_attr( $_REQUEST['page'] ),
				'view',
				$item['id']
			),
			'edit'   => sprintf(
				'<a href="?page=%s&action=%s&license=%s">' . __( 'Edit', 'woocommerce-license-manager' ) . '</a>',
				esc_attr( $_REQUEST['page'] ),
				'edit',
				$item['id']
			),
			'notify' => sprintf(
				'<a href="?page=%s&action=%s&license=%s">' . __( 'Notify', 'woocommerce-license-manager' ) . '</a>',
				esc_attr( $_REQUEST['page'] ),
				'notify',
				$item['id']
			),
		);

		return sprintf( '%1$s %2$s', $item['licenseKey'], $this->row_actions( $actions ) );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'clientName':
			case 'status':
			case 'expiresAt':
				return $item[ $column_name ];
			default:
				return '';
		}
	}

	public function display_add_license_form() {
		echo '<div class="wrap">';
		echo '<h1>' . __( 'Add New License', 'woocommerce-license-manager' ) . '</h1>';

		// Process the form submission if it is a POST request.
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'wclm_add_license' ) ) {
			$this->process_add_license_form();
		}

		echo '<form method="post">';
		wp_nonce_field( 'wclm_add_license' );

		echo '<table class="form-table">';

		// Fetch users for the dropdown.
		$users = get_users();
		echo '<tr>';
		echo '<th><label for="user-list">' . __( 'Users', 'woocommerce-license-manager' ) . '</label></th>';
		echo '<td>';
		echo '<select name="userId" id="user-list" required>';
		echo '<option value="">' . __( 'Select a user', 'woocommerce-license-manager' ) . '</option>';
		foreach ( $users as $user ) {
			echo '<option value="' . esc_attr( $user->ID ) . '">' . esc_html( $user->display_name ) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		// Fetch products for the dropdown.
		$products = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => -1,
			)
		);
		echo '<tr>';
		echo '<th><label for="product-id">' . __( 'Select Product', 'woocommerce-license-manager' ) . '</label></th>';
		echo '<td>';
		echo '<select name="productId" id="product-id" required>';
		echo '<option value="">' . __( 'Select a product', 'woocommerce-license-manager' ) . '</option>';
		foreach ( $products as $product ) {
			echo '<option value="' . esc_attr( $product->get_id() ) . '">' . esc_html( $product->get_name() ) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		// Placeholder for variations dropdown, dynamically populated via JS.
		echo '<tr id="variation-row" style="display:none;">';
		echo '<th><label for="variation-id">' . __( 'Select Variation', 'woocommerce-license-manager' ) . '</label></th>';
		echo '<td>';
		echo '<select name="variationId" id="variation-id">';
		echo '<option value="">' . __( 'Select a variation', 'woocommerce-license-manager' ) . '</option>';
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		// Fetch license types for the dropdown.
		$license_types = $this->api_client->get_license_types();
		echo '<tr>';
		echo '<th><label for="license-type">' . __( 'Select License Type', 'woocommerce-license-manager' ) . '</label></th>';
		echo '<td>';
		echo '<select name="licenseTypeId" id="license-type" required>';
		echo '<option value="">' . __( 'Select a license type', 'woocommerce-license-manager' ) . '</option>';

		if ( isset( $license_types['licenseTypes'] ) && is_array( $license_types['licenseTypes'] ) ) {
			foreach ( $license_types['licenseTypes'] as $type ) {
				echo '<option value="' . esc_attr( $type['id'] ) . '">' . esc_html( $type['name'] ) . '</option>';
			}
		} else {
			echo '<option value="">' . __( 'No license types available', 'woocommerce-license-manager' ) . '</option>';
		}

		echo '</select>';
		echo '</td>';
		echo '</tr>';

		// Order ID input.
		echo '<tr>';
		echo '<th><label for="order-id">' . __( 'Order ID', 'woocommerce-license-manager' ) . '</label></th>';
		echo '<td><input type="number" name="orderId" id="order-id" required /></td>';
		echo '</tr>';

		echo '<tr><th><label for="expiresAt">' . __( 'Expiry Date', 'woocommerce-license-manager' ) . '</label></th>';
		echo '<td><input type="date" name="expiresAt" id="expiresAt" required /></td></tr>';

		echo '<tr><th><label for="status">' . __( 'Status', 'woocommerce-license-manager' ) . '</label></th>';
		echo '<td><select name="status" id="status" required>';
		echo '<option value="active">' . __( 'Active', 'woocommerce-license-manager' ) . '</option>';
		echo '<option value="expired">' . __( 'Expired', 'woocommerce-license-manager' ) . '</option>';
		echo '<option value="revoked">' . __( 'Revoked', 'woocommerce-license-manager' ) . '</option>';
		echo '</select></td></tr>';

		echo '</table>';

		submit_button( __( 'Add License', 'woocommerce-license-manager' ) );
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Processes the form submission to add a new license.
	 *
	 * Validates the input data, fetches the related user and product information,
	 * and sends the license creation data to the API.
	 *
	 * @return void
	 */
	private function process_add_license_form() {

		$user_id         = intval( $_POST['userId'] );
		$product_id      = intval( $_POST['productId'] );
		$license_type_id = intval( $_POST['licenseTypeId'] );
		$expires_at      = sanitize_text_field( $_POST['expiresAt'] );
		$status          = sanitize_text_field( $_POST['status'] );

		// Validate required fields.
		if ( empty( $user_id ) || empty( $product_id ) || empty( $license_type_id ) || empty( $expires_at ) || empty( $status ) ) {
			$this->add_notice( __( 'All fields are required.', 'woocommerce-license-manager' ), 'error' );
			return;
		}

		// Fetch product and user data (as previously discussed).
		$product = wc_get_product( $product_id );
		$user    = get_userdata( $user_id );

		if ( ! $product || ! $user ) {
			$this->add_notice( __( 'Invalid product or user selected.', 'woocommerce-license-manager' ), 'error' );
			return;
		}

		// Select the primary role for the user.
		$primary_role = ! empty( $user->roles ) ? $user->roles[0] : 'customer';

		// Prepare API data.
		$data = array(
			'clientId'      => $user_id,
			'licenseTypeId' => $license_type_id,
			'expiresAt'     => date( 'Y-m-d\TH:i:s\Z', strtotime( $expires_at ) ),
			'status'        => $status,
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

		// Call the API using the encapsulated method.
		$response = $this->api_client->create_license( $data );

		if ( $response ) {
			$this->add_notice( __( 'License added successfully.', 'woocommerce-license-manager' ) );
		} else {
			$this->add_notice( __( 'Failed to add license.', 'woocommerce-license-manager' ), 'error' );
		}
	}
}
