<?php
/**
 * Class Mhub_S2SO_Auth
 *
 * Description: This class handles Zoom OAuth authentication, access token generation, and storage.
 *
 * @package SOVLIX\MHUB\Zoom
 */

namespace SOVLIX\MHUB\Zoom;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mhub_S2SO_Auth' ) ) {
	/**
	 * Class Mhub_S2SO_Auth
	 *
	 * @package SOVLIX\MHUB\Zoom
	 */
	class Mhub_S2SO_Auth {
		/**
		 * Instance of the class.
		 *
		 * @var Mhub_S2SO_Auth|null
		 */
		public static $instance = null;

		/**
		 * Zoom settings array.
		 *
		 * @var array
		 */
		private $zoom_settings;

		/**
		 * Get an instance of the class.
		 *
		 * @return Mhub_S2SO_Auth|null
		 */
		public static function get_instance() {
			return is_null( self::$instance ) ? self::$instance = new self() : self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->zoom_settings = get_option( 'mhub_zoom_settings', true );
		}

		/**
		 * Generate access token from Zoom OAuth credentials.
		 *
		 * @param string $account_id    Zoom account ID.
		 * @param string $client_id     OAuth client ID.
		 * @param string $client_secret OAuth client secret.
		 *
		 * @return mixed|\WP_Error
		 */
		private function generate_access_token( $account_id, $client_id, $client_secret ) {

			if ( empty( $account_id ) ) {
				return new \WP_Error( 'Account ID', 'Account ID is missing' );
			} elseif ( empty( $client_id ) ) {
				return new \WP_Error( 'Client ID', 'Client ID is missing' );
			} elseif ( empty( $client_secret ) ) {
				return new \WP_Error( 'Client Secret', 'Client Secret is missing' );
			}

			// phpcs:ignore
			$base64_encoded = base64_encode( $client_id . ':' . $client_secret );
			// phpcs:ignore
			$result        = new \WP_Error( 0, 'Something went wrong' );

			$args = array(
				'method'  => 'POST',
				'headers' => array(
					'Authorization' => "Basic $base64_encoded",
				),
				'body'    => array(
					'grant_type' => 'account_credentials',
					'account_id' => $account_id,
				),
			);

			$request_url      = 'https://zoom.us/oauth/token';
			$response         = wp_remote_post( $request_url, $args );
			$response_code    = wp_remote_retrieve_response_code( $response );
			$response_message = wp_remote_retrieve_response_message( $response );

			// phpcs:ignore
			if ( $response_code == 200 && strtolower( $response_message ) == 'ok' ) {
				$response_body         = wp_remote_retrieve_body( $response );
				$decoded_response_body = json_decode( $response_body );
				if ( isset( $decoded_response_body->access_token ) && ! empty( $decoded_response_body->access_token ) ) {
					$result = $decoded_response_body;
					// phpcs:ignore
				} elseif ( isset( $decoded_response_body->errorCode ) && ! empty( $decoded_response_body->errorCode ) ) {
					// phpcs:ignore
					$result = new \WP_Error( $decoded_response_body->errorCode, 
					// phpcs:ignore
					$decoded_response_body->errorMessage );
				}
			} else {
				$result = new \WP_Error( $response_code, $response_message );
			}

			return $result;
		}

		/**
		 * Generate and save access token.
		 *
		 * @param string $account_id    Zoom account ID.
		 * @param string $client_id     OAuth client ID.
		 * @param string $client_secret OAuth client secret.
		 *
		 * @return mixed|\WP_Error
		 */
		public function generate_and_save_access_token( $account_id, $client_id, $client_secret ) {
			$result = $this->generate_access_token( $account_id, $client_id, $client_secret );

			if ( ! is_wp_error( $result ) ) {
				// @todo - implement a per-person option to allow other users to add their own API Credentials and generate their access token.
				update_option( 'mhub_zoom_global_oauth_data', $result );
			}

			return $result;
		}

		/**
		 * Regenerate access token from saved keys.
		 *
		 * @return void
		 */
		public function regenerate_access_token_and_save() {

			if ( is_array( $this->zoom_settings ) && ! empty( $this->zoom_settings ) ) {
				$account_id    = $this->zoom_settings['oauth_account_id'];
				$client_id     = $this->zoom_settings['oauth_client_id'];
				$client_secret = $this->zoom_settings['oauth_client_secret'];
			} else {
				$account_id    = '';
				$client_id     = '';
				$client_secret = '';
			}

			$result = $this->generate_and_save_access_token( $account_id, $client_id, $client_secret );
			// phpcs:ignore
			if ( is_wp_error( $result ) ) {
				// @todo log error if regenerating access token unsuccessful.
			}
		}
	}
}
