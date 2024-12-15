<?php

class WCLM_API_Client {

	// private $api_url = 'https://license.wpauctionify.com';
	private $api_url = 'http://localhost:3000';
	private $username;
	private $password;
	private $token;
	private $token_expires;

	public function __construct( $username = '', $password = '' ) {
		if ( ! empty( $username ) && ! empty( $password ) ) {
			$this->username = $username;
			$this->password = $password;
		} else {
			$settings            = get_option( 'wclm_settings' );
			$this->username      = $settings['api_username'] ?? '';
			$this->password      = $settings['api_password'] ?? '';
			$this->token         = $settings['api_token'] ?? '';
			$this->token_expires = $settings['token_expires'] ?? 0;
		}
	}

	public function get_token() {
		return $this->token;
	}

	public function get_token_expires() {
		return $this->token_expires;
	}

	public function authenticate() {
		$response = wp_remote_post(
			$this->api_url . '/api/auth/login',
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => json_encode(
					array(
						'email'    => $this->username,
						'password' => $this->password,
					)
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'API Authentication Error: ' . $response->get_error_message() );
			return array();
		} else {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $body['token'] ) ) {
				$this->token         = $body['token'];
				$this->token_expires = $this->get_token_expiration( $this->token );

				// Save token and expiration time to settings.
				$settings                  = get_option( 'wclm_settings' );
				$settings['api_token']     = $this->token;
				$settings['token_expires'] = $this->token_expires;
				update_option( 'wclm_settings', $settings );

				return $body;
			} else {
				error_log( 'API Authentication Failed: ' . ( $body['message'] ?? 'Unknown error' ) );
				return array();
			}
		}
	}

	private function get_token_expiration( $token ) {
		$token_parts = explode( '.', $token );
		if ( count( $token_parts ) !== 3 ) {
			return time() + 3600; // Default to 1 hour if token is invalid
		}

		$payload         = $token_parts[1];
		$decoded_payload = json_decode( base64_decode( str_replace( '_', '/', str_replace( '-', '+', $payload ) ) ), true );

		if ( isset( $decoded_payload['exp'] ) ) {
			return $decoded_payload['exp'];
		} else {
			return time() + 3600; // Default to 1 hour if 'exp' claim is missing
		}
	}

	private function ensure_token() {
		if ( empty( $this->token ) || $this->token_expires <= time() ) {
			// Re-authenticate to get a new token
			$this->authenticate();
		}
	}

	public function request( $method, $endpoint, $data = array() ) {
		$this->ensure_token();

		$args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->token,
			),
			'timeout' => 15,
		);

		if ( $method === 'GET' ) {
			$response = wp_remote_get( $this->api_url . $endpoint, $args );
		} else {
			$args['body'] = json_encode( $data );
			if ( $method === 'POST' ) {
				$response = wp_remote_post( $this->api_url . $endpoint, $args );
			} else {
				$args['method'] = $method;
				$response       = wp_remote_request( $this->api_url . $endpoint, $args );
			}
		}

		if ( is_wp_error( $response ) ) {
			error_log( 'API Request Error: ' . $response->get_error_message() );
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle unauthorized error due to token expiration.
		if ( isset( $body['message'] ) && 'Unauthorized' === $body['message'] ) {
			// Re-authenticate and retry the request.
			$this->authenticate();

			// Update the Authorization header with the new token.
			$args['headers']['Authorization'] = 'Bearer ' . $this->token;

			if ( 'GET' === $method ) {
				$response = wp_remote_get( $this->api_url . $endpoint, $args );
			} elseif ( 'POST' === $method ) {
					$response = wp_remote_post( $this->api_url . $endpoint, $args );
			} else {
				$args['method'] = $method;
				$response       = wp_remote_request( $this->api_url . $endpoint, $args );
			}

			if ( is_wp_error( $response ) ) {
				error_log( 'API Request Error After Re-authentication: ' . $response->get_error_message() );
				return array();
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		return $body;
	}

	public function validate_license( $license_key ) {
		return $this->request(
			'POST',
			'/api/validate-license',
			array(
				'license_key' => $license_key,
			)
		);
	}

	public function activate_license( $license_key, $domain ) {
		return $this->request(
			'POST',
			'/api/activate-license',
			array(
				'license_key' => $license_key,
				'domain'      => $domain,
			)
		);
	}

	public function deactivate_license( $license_key, $domain ) {
		return $this->request(
			'POST',
			'/api/deactivate-license',
			array(
				'license_key' => $license_key,
				'domain'      => $domain,
			)
		);
	}


	// Existing methods (create_license, get_license_types, etc.) remain the same...

	// Get All License.
	public function get_all_licenses() {
		$endpoint = '/api/licenses';
		$response = $this->request( 'GET', $endpoint );

		if ( isset( $response['licenses'] ) ) {
			return $response;
		} else {
			error_log( 'Failed to retrieve license types from API: ' . print_r( $response, true ) );
			return null;
		}
	}

	// Create License.
	public function create_license( $data ) {
		$endpoint = '/api/licenses';
		$response = $this->request( 'POST', $endpoint, $data );

		error_log( '[' . $endpoint . '] ' . print_r( $data, true ) );

		if ( isset( $response['license'] ) ) {
				return $response;
		} else {
				error_log( 'Failed to create license via API: ' . print_r( $response, true ) );
				return null;
		}
	}

	// Get License Types.
	public function get_license_types() {
		$endpoint = '/api/license-types';
		$response = $this->request( 'GET', $endpoint );

		if ( isset( $response['licenseTypes'] ) ) {
				return $response;
		} else {
				error_log( 'Failed to retrieve license types from API: ' . print_r( $response, true ) );
				return null;
		}
	}

	// Create License Type.
	public function create_license_type( $data ) {
			$endpoint = '/api/license-types';
			$response = $this->request( 'POST', $endpoint, $data );

		if ( isset( $response['licenseType'] ) ) {
				return $response;
		} else {
				error_log( 'Failed to create license type via API: ' . print_r( $response, true ) );
				return null;
		}
	}

	/**
	 * Get All Clients.
	 */
	public function get_clients() {
			$endpoint = '/api/clients';
			$response = $this->request( 'GET', $endpoint );

		if ( isset( $response['clients'] ) ) {
				return $response;
		} else {
				error_log( 'Failed to retrieve clients from API: ' . print_r( $response, true ) );
				return null;
		}
	}

	/**
	 * Get Client by ID.
	 */
	public function get_client_by_id( $id ) {
		$endpoint = '/api/clients/' . $id;
		$response = $this->request( 'GET', $endpoint );

		if ( isset( $response['clients'] ) ) {
				return $response;
		} else {
				error_log( 'Failed to retrieve clients from API: ' . print_r( $response, true ) );
				return null;
		}
	}

	// Create Client.
	public function create_client( $data ) {
		$endpoint = '/api/clients';
		$response = $this->request( 'POST', $endpoint, $data );

		if ( isset( $response['client'] ) ) {
				return $response;
		} else {
				error_log( 'Failed to create client via API: ' . print_r( $response, true ) );
				return null;
		}
	}

	// Additional methods for other API endpoints...

	public function renew_license( $license_id, $data ) {
		return $this->request( 'PUT', '/api/licenses/' . intval( $license_id ), $data );
	}

	public function revoke_license( $license_id ) {
		return $this->request( 'PUT', '/api/licenses/' . intval( $license_id ), array( 'status' => 'revoked' ) );
	}

	public function notify_client( $client_id, $message ) {
		// Implement notification logic if API supports it.
	}

	// Get a specific License Type.
	public function get_license_type( $licenseTypeId ) {
		$endpoint = '/api/license-types/' . intval( $licenseTypeId );
		$response = $this->request( 'GET', $endpoint );

		if ( isset( $response['licenseType'] ) ) {
				return $response;
		} else {
				error_log( 'Failed to retrieve license type from API: ' . print_r( $response, true ) );
				return null;
		}
	}
}
