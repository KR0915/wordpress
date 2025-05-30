<?php
/**
 * Mhub_Webex API Class
 *
 * This class manages the API functionality for the Meeting Hub plugin settings.
 *
 * @package SOVLIX\MHUB\API
 */

namespace SOVLIX\MHUB\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mhub_Webex' ) ) {
	/**
	 * Mhub_Webex Class
	 */
	class Mhub_Webex extends WP_REST_Controller {
		/**
		 * Webex Api class.
		 *
		 * @var Mhub_Webex_Api
		 */
		private $webex_api;

		/**
		 * Mhub_Webex constructor
		 */
		public function __construct() {
			$this->namespace = 'meetinghub/v1';
			$this->rest_base = 'webex';
			$this->webex_api = \SOVLIX\MHUB\API\Webex_Api::get_instance();
		}

		/**
		 * Register REST API routes for settings
		 *
		 * @since 1.0.0
		 */
		public function register_routes() {
			// New route for webex settings.
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/auth-url',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_webex_auth_url' ),
						'permission_callback' => '__return_true',
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/fetch-token',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'fetch_webex_access_token' ),
						'permission_callback' => '__return_true',
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/revoke-access-token',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'revoke_webex_access_token' ),
						'permission_callback' => '__return_true',
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/meetings',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_webex_meetings' ),
						'permission_callback' => '__return_true',
					),
				)
			);
		}

		/**
		 * Check if the current user has permission to edit posts.
		 *
		 * This function checks if the current user has the capability to edit posts.
		 * It returns true if the user has the 'edit_posts' capability, and false otherwise.
		 *
		 * @return bool True if the current user can edit posts, false otherwise.
		 */
		public function check_permissions() {
			return current_user_can( 'edit_posts' );
		}

		/**
		 * Retrieves the Webex authorization URL.
		 *
		 * This function returns the authorization URL for Webex, which can be used to initiate the OAuth flow.
		 *
		 * @return WP_REST_Response The REST response containing the authorization URL.
		 */
		public function get_webex_auth_url() {
			return rest_ensure_response( array( 'auth_url' => $this->webex_api->get_auth_uri() ) );
		}

		/**
		 * Fetches the Webex access token.
		 *
		 * This function returns the current access token data for the Webex API, which includes the token and its metadata.
		 *
		 * @param WP_REST_Request $request The request object.
		 * @return WP_REST_Response The REST response containing the access token data.
		 */
		public function fetch_webex_access_token( $request ) {
			return rest_ensure_response( array( 'access_token' => $this->webex_api->access_token_data ) );
		}

		/**
		 * Callback function to revoke Webex access token.
		 *
		 * @param WP_REST_Request $request Request object.
		 * @return WP_Error|WP_REST_Response
		 */
		public function revoke_webex_access_token( $request ) {
			// Call the revoke_access_token method from the Webex_Api instance.
			return rest_ensure_response( array( 'disconnect_url' => admin_url( 'admin.php?page=meetinghub-settings' ) ) );
		}

		/**
		 * Retrieve a list of Webex meetings.
		 *
		 * This function interacts with the Webex API to retrieve a list of meetings.
		 * It makes use of the Webex API client to send a request for the list of meetings.
		 *
		 * @param WP_REST_Request $request The REST API request object.
		 *
		 * @return WP_REST_Response|array The response from the Webex API, which includes the list of meetings.
		 */
		public function get_webex_meetings( $request ) {
			$webex_response = $this->webex_api->list_meetings();
			return $webex_response;
		}
	}
}
