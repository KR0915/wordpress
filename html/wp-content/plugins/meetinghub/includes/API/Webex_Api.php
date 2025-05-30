<?php
/**
 * Mhub_Webex API Class
 *
 * This class manages the API functionality for the Meeting Hub plugin settings.
 *
 * @package SOVLIX\MHUB\API
 */

namespace SOVLIX\MHUB\API;

/**
 * Class Webex_Api
 *
 * This class handles communication with the Webex API.
 */
class Webex_Api {
	/**
	 * The URI for authorization.
	 *
	 * @var string
	 */
	const AUTHORIZE_URI = 'https://webexapis.com/v1/authorize';

	/**
	 * The URI to obtain an access token.
	 *
	 * @var string
	 */
	const ACCESS_TOKEN_URI = 'https://webexapis.com/v1/access_token';

	/**
	 * The base URI for the Webex API.
	 *
	 * @var string
	 */
	private $api_base_uri = 'https://webexapis.com/v1/';

	/**
	 * The redirect URL after authentication.
	 *
	 * @var string
	 */
	private $redirect_url;

	/**
	 * The Webex client ID.
	 *
	 * @var string
	 */
	private $client_id = '';

	/**
	 * The Webex client secret.
	 *
	 * @var string
	 */
	private $client_secret = '';

	/**
	 * Data for the access token.
	 *
	 * @var mixed
	 */
	public $access_token_data;

	/**
	 * The current user ID.
	 *
	 * @var int
	 */
	private $current_user_id;

	/**
	 * The Webex settings from the WordPress options.
	 *
	 * @var array
	 */
	private $webex_settings;

	/**
	 * Singleton instance of the class.
	 *
	 * @var Webex_Api|null
	 */
	private static $instance = null;

	/**
	 * Webex_Api constructor.
	 */
	public function __construct() {
		$this->webex_settings = get_option( 'mhub_webex_settings', true );
		$this->redirect_url   = admin_url( 'admin.php?page=meetinghub-settings' );

		if ( is_array( $this->webex_settings ) && ! empty( $this->webex_settings ) ) {
			$this->client_id     = $this->webex_settings['client_id'];
			$this->client_secret = $this->webex_settings['client_secret'];
		}

		$this->current_user_id = get_current_user_id();
		$this->load_access_token();
	}

	/**
	 * Gets the singleton instance of the class.
	 *
	 * @return Webex_Api
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Loads the access token from the database.
	 */
	private function load_access_token() {
		$this->access_token_data = get_user_meta( $this->current_user_id, 'mhub_webex_access_token', true );
	}

	/**
	 * Saves the access token to the database.
	 *
	 * @param array $token_data The token data to save.
	 */
	private function save_access_token( $token_data ) {
		update_user_meta( $this->current_user_id, 'mhub_webex_access_token', $token_data );
	}

	/**
	 * Gets the authorization URI for Webex.
	 *
	 * @return string
	 */
	public function get_auth_uri() {
		$params = array(
			'response_type' => 'code',
			'client_id'     => $this->client_id,
			'redirect_uri'  => $this->redirect_url,
			'scope'         => 'meeting:schedules_read meeting:schedules_write',
			'state'         => admin_url( 'admin.php?page=meetinghub-settings' ),
		);

		return add_query_arg( $params, self::AUTHORIZE_URI );
	}

	/**
	 * Fetches the access token from Webex using the authorization code.
	 */
	public function fetch_access_token() {
		$code = filter_input( INPUT_GET, 'code' );
		if ( empty( $code ) ) {
			return;
		}

		$response = wp_remote_post(
			self::ACCESS_TOKEN_URI,
			array(
				'body' => array(
					'grant_type'    => 'authorization_code',
					'client_id'     => $this->client_id,
					'client_secret' => $this->client_secret,
					'code'          => $code,
					'redirect_uri'  => $this->redirect_url,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			// Handle error.
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$this->save_access_token( $body );
		wp_safe_redirect( admin_url( 'admin.php?page=meetinghub-settings#/webex' ) );
		exit;
	}

	/**
	 * Revokes the access token.
	 */
	public function revoke_access_token() {
		delete_user_meta( $this->current_user_id, 'mhub_webex_access_token' );
		wp_safe_redirect( admin_url( 'admin.php?page=meetinghub-settings#/webex' ) );
		exit;
	}

	/**
	 * Sends a request to the Webex API.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param string $method The HTTP method (default: 'GET').
	 * @param array  $data The data to send with the request.
	 * @return mixed The API response.
	 */
	private function api_request( $endpoint, $method = 'GET', $data = array() ) {
		if ( empty( $this->access_token_data['access_token'] ) ) {
			return false;
		}

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->access_token_data['access_token'],
				'Content-Type'  => 'application/json',
			),
			'body'    => 'GET' !== $method ? wp_json_encode( $data ) : array(),
		);

		$response = wp_remote_request( $this->api_base_uri . $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Gets the current user's info.
	 *
	 * @return mixed
	 */
	public function get_user_info() {
		return $this->api_request( 'people/me' );
	}

	/**
	 * Lists meetings.
	 *
	 * @param array $params The parameters for the request.
	 * @return mixed The list of meetings.
	 */
	public function list_meetings( $params = array() ) {
		return $this->api_request( 'meetings', 'GET', $params );
	}

	/**
	 * Gets a meeting by ID.
	 *
	 * @param string $id The meeting ID.
	 * @return mixed The meeting details.
	 */
	public function get_meeting( $id ) {
		return $this->api_request( 'meetings/' . $id, 'GET' );
	}

	/**
	 * Creates a new meeting.
	 *
	 * @param array $data The data for the new meeting.
	 * @return mixed The created meeting.
	 */
	public function create_meeting( $data ) {
		return $this->api_request( 'meetings', 'POST', $data );
	}

	/**
	 * Updates a meeting by ID.
	 *
	 * @param string $id The meeting ID.
	 * @param array  $data The data to update the meeting with.
	 * @return mixed The updated meeting.
	 */
	public function update_meeting( $id, $data ) {
		return $this->api_request( 'meetings/' . $id, 'PUT', $data );
	}

	/**
	 * Deletes a meeting by ID.
	 *
	 * @param string $id The meeting ID.
	 * @return mixed The response from the API.
	 */
	public function delete_meeting( $id ) {
		return $this->api_request( 'meetings/' . $id, 'DELETE' );
	}

	/**
	 * Gets a user by ID.
	 *
	 * @param string|null $user_id The user ID (optional).
	 * @return mixed The user details.
	 */
	public function get_user( $user_id = null ) {
		if ( $user_id ) {
			return $this->api_request( 'people/' . $user_id, 'GET' );
		} else {
			return $this->api_request( 'people', 'GET' );
		}
	}

	/**
	 * Lists recordings.
	 *
	 * @param array $params The parameters for the request.
	 * @return mixed The list of recordings.
	 */
	public function list_recordings( $params = array() ) {
		return $this->api_request( 'recordings', 'GET', $params );
	}
}
