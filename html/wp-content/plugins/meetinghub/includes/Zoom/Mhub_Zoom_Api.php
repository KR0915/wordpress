<?php
/**
 * File containing the Mhub_Zoom_Api class for connecting to Zoom API V2.
 *
 * @package SOVLIX\MHUB\Zoom
 */

namespace SOVLIX\MHUB\Zoom;

use Firebase\JWT\JWT;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mhub_Zoom_Api' ) ) {
	/**
	 * Class Mhub_Zoom_Api
	 *
	 * Description: Connecting Zoom API V2 for various functionalities.
	 */
	class Mhub_Zoom_Api {
		/**
		 * Number of attempts to revalidate OAuth. Used to control the revalidation flow.
		 *
		 * @var int
		 */
		public static $oauth_revalidate_attempts = 0;

		/**
		 * Zoom API KEY.
		 *
		 * @var string
		 */
		public $zoom_api_key;

		/**
		 * Zoom API Secret.
		 *
		 * @var string
		 */
		public $zoom_api_secret;

		/**
		 * Hold my instance.
		 *
		 * @var Mhub_Zoom_Api|null
		 */
		protected static $instance;

		/**
		 * API endpoint base
		 *
		 * @var string
		 */
		private $api_url = 'https://api.zoom.us/v2/';

		/**
		 * Create only one instance so that it may not Repeat
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Mhub_Zoom_Api constructor.
		 *
		 * @param string $zoom_api_key    Zoom API key.
		 * @param string $zoom_api_secret Zoom API secret.
		 */
		public function __construct( $zoom_api_key = '', $zoom_api_secret = '' ) {
			$this->zoom_api_key    = $zoom_api_key;
			$this->zoom_api_secret = $zoom_api_secret;
		}

		/**
		 * Send request to API.
		 *
		 * @param string $called_function API function to call.
		 * @param mixed  $data           Data to send in the request.
		 * @param string $request        Type of request (GET, POST, DELETE, PATCH, PUT).
		 *
		 * @return array|bool|string|\WP_Error
		 */
		protected function send_request( $called_function, $data, $request = 'GET' ) {
			$initial_request = $request;
			$request_url     = $this->api_url . $called_function;
			$bearer_token    = $this->get_bearer_token();

			$args = array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $bearer_token,
					'Content-Type'  => 'application/json',
				),
			);

			if ( 'GET' === $request ) {
				$args['body'] = ! empty( $data ) ? $data : array();
				$request      = wp_remote_get( $request_url, $args );
			} elseif ( 'DELETE' === $request ) {
				$args['body']   = ! empty( $data ) ? wp_json_encode( $data ) : array();
				$args['method'] = 'DELETE';
				$request        = wp_remote_request( $request_url, $args );
			} elseif ( 'PATCH' === $request ) {
				$args['body']   = ! empty( $data ) ? wp_json_encode( $data ) : array();
				$args['method'] = 'PATCH';
				$request        = wp_remote_request( $request_url, $args );
			} elseif ( 'PUT' === $request ) {
				$args['body']   = ! empty( $data ) ? wp_json_encode( $data ) : array();
				$args['method'] = 'PUT';
				$request        = wp_remote_request( $request_url, $args );
			} else {
				$args['body']   = ! empty( $data ) ? wp_json_encode( $data ) : array();
				$args['method'] = 'POST';
				$request        = wp_remote_post( $request_url, $args );
			}

			if ( is_wp_error( $request ) ) {
				$this->log_message( $request->get_error_message(), $request->get_error_code(), $request );

				return false;
			} else {
				$response_code = wp_remote_retrieve_response_code( $request );
				$response_body = wp_remote_retrieve_body( $request );
				$debug_log     = get_option( 'zoom_api_enable_debug_log' );

				if ( 401 === (int) $response_code ) {
					// only regenerate access token if it's already active.
					Mhub_S2SO_Auth::get_instance()->regenerate_access_token_and_save();
					// only retry twice.
					if ( self::$oauth_revalidate_attempts <= 2 ) {
						++self::$oauth_revalidate_attempts;

						// resend the request after regenerating access token.
						return $this->send_request( $called_function, $data, $initial_request );
					} else {
						self::$oauth_revalidate_attempts = 0;
						if ( ! empty( $debug_log ) ) {
							$this->log_message( $response_body, $response_code, $request );
						}
					}
				}

				// If Debug log is enabled.
				if ( ! empty( $debug_log ) ) {
					if ( 400 === (int) $response_code ) {
						$this->log_message( $response_body, $response_code, $request );
					} elseif ( 401 === (int) $response_code ) {
						$this->log_message( $response_body, $response_code, $request );
					} elseif ( 403 === (int) $response_code ) {
						$this->log_message( $response_body, $response_code, $request );
					} elseif ( 404 === (int) $response_code ) {
						$this->log_message( $response_body, $response_code, $request );
					} elseif ( 409 === (int) $response_code ) {
						$this->log_message( $response_body, $response_code, $request );
					} elseif ( 429 === (int) $response_code ) {
						$this->log_message( $response_body, $response_code, $request );
					}
				}
			}

			return $response_body;
		}

		/**
		 * Check if given string is a correct JSON object.
		 *
		 * @param string $get_string The string to check.
		 *
		 * @return bool
		 */
		public function is_json( $get_string ) {
			json_decode( $get_string );

			return json_last_error() === JSON_ERROR_NONE;
		}

		/**
		 * Check if valid XML.
		 *
		 * @param string $xml The XML string to check.
		 *
		 * @return bool
		 */
		public function is_valid_xml( $xml ) {
			// phpcs:ignore
			$doc = @simplexml_load_string( $xml );
			if ( $doc ) {
				return true; // this is valid.
			} else {
				return false; // this is not valid.
			}
		}

		/**
		 * Log the message.
		 *
		 * @param mixed $response_body Response body.
		 * @param int   $response_code Response code.
		 * @param mixed $request       Request object.
		 */
		public function log_message( $response_body, $response_code, $request ) {
			$message  = $response_code . ' ::: ';
			$message .= wp_remote_retrieve_response_message( $request );

			$error_data = array();

			if ( ! empty( $response_body ) ) {

				// Response body validation.
				if ( $this->is_valid_xml( $response_body ) ) {
					$response_body = simplexml_load_string( $response_body );
				} elseif ( $this->is_json( $response_body ) ) {
					$response_body = json_decode( $response_body );
				}

				if ( ! empty( $response_body ) && ! empty( $response_body->message ) ) {
					$message .= ' ::: MESSAGE => ' . $response_body->message;
				} elseif ( ! empty( $response_body ) && is_string( $response_body ) ) {
					$message .= ' ::: MESSAGE => ' . $response_body;
				}

				if ( ! empty( $response_body ) && ! empty( $response_body->errors ) && is_object( $response_body->errors ) && ! empty( $response_body->errors->message ) ) {
					$message .= ' ::: ERRORS => ' . $response_body->errors->message;
				}
			}
			$error = new \WP_Error( $response_code, $message, $error_data );
		}

		/**
		 * Get Bearer Token for authorization.
		 *
		 * @return string
		 */
		private function get_bearer_token() {
			// @todo this will need to be modified for each user scenario
			$oauth_data = get_option( 'mhub_zoom_global_oauth_data' );
			if ( ! empty( $oauth_data ) ) {
				return $oauth_data->access_token;
			} else {
				return $this->generate_jwt_key();
			}
		}

		/**
		 * Generate JWT key.
		 *
		 * @return string|false
		 */
		private function generate_jwt_key() {
			$key    = $this->zoom_api_key;
			$secret = $this->zoom_api_secret;

			$token = array(
				'iss' => $key,
				'exp' => time() + 3600, // 60 seconds as suggested
			);

			if ( empty( $secret ) ) {
				return false;
			}

			return JWT::encode( $token, $secret, 'HS256' );
		}

		/**
		 * Creates a User.
		 *
		 * @param array $posted_data Data to post for creating a user.
		 *
		 * @return array|bool|string
		 */
		public function create_user( $posted_data ) {
			$create_user_array              = array();
			$create_user_array['action']    = $posted_data->zoom_user_action;
			$create_user_array['user_info'] = array(
				'email'      => $posted_data->email,
				'type'       => $posted_data->type,
				'first_name' => $posted_data->first_name,
				'last_name'  => $posted_data->last_name,
			);

			$create_user_array = apply_filters( 'mhub_create_user', $create_user_array );

			return $this->send_request( 'users', $create_user_array, 'POST' );
		}

		/**
		 * List users with optional pagination and additional arguments.
		 *
		 * @param int   $page Page number for pagination.
		 * @param array $args Additional arguments for listing users.
		 *
		 * @return array
		 */
		public function list_users( $page = 1, $args = array() ) {
			$defaults = array(
				'page_size'   => 300,
				'page_number' => absint( $page ),
			);

			// Parse incoming $args into an array and merge it with $defaults.
			$args             = wp_parse_args( $args, $defaults );
			$list_users_array = apply_filters( 'mhub_list_users', $args );

			return $this->send_request( 'users', $list_users_array, 'GET' );
		}

		/**
		 * Retrieve user information by user ID.
		 *
		 * @param int $user_id User ID to fetch information.
		 *
		 * @return array|bool|string
		 */
		public function get_user_info( $user_id ) {
			$get_user_info_array = array();
			$get_user_info_array = apply_filters( 'mhub_get_user_info', $get_user_info_array );

			return $this->send_request( 'users/' . $user_id, $get_user_info_array );
		}

		/**
		 * Delete a user by user ID.
		 *
		 * @param int $userid User ID to delete.
		 *
		 * @return array|bool|string
		 */
		public function delete_user( $userid ) {
			return $this->send_request( 'users/' . $userid, false, 'DELETE' );
		}

		/**
		 * List meetings for a given host ID with optional pagination and additional arguments.
		 *
		 * @param int   $host_id Host ID for listing meetings.
		 * @param array $args    Additional arguments for listing meetings.
		 *
		 * @return array
		 */
		public function list_meetings( $host_id, $args = false ) {
			$defaults = array(
				'page_size' => 300,
			);

			// Parse incoming $args into an array and merge it with $defaults.
			$args = wp_parse_args( $args, $defaults );
			$args = apply_filters( 'mhub_zoom_list_meetings', $args );

			return $this->send_request( 'users/' . $host_id . '/meetings', $args, 'GET' );
		}

		/**
		 * Create a Zoom meeting with specified data and user ID.
		 *
		 * @param int   $user_id User ID for creating the meeting.
		 * @param array $data    Meeting data.
		 *
		 * @return array|bool|string|void|WP_Error
		 */
		public function create_zoom_meeting( $user_id, $data = array() ) {

			$create_zoom_meeting_data = apply_filters( 'mhub_zoom_create_meeting', $data );
			if ( ! empty( $create_zoom_meeting_data ) ) {
				return $this->send_request( 'users/' . $user_id . '/meetings', $create_zoom_meeting_data, 'POST' );
			} else {
				return;
			}
		}

		/**
		 * Update information for a Zoom meeting identified by its ID.
		 *
		 * @param int   $zoom_meeting_id The Zoom meeting ID.
		 * @param array $data            Data to update for the meeting.
		 *
		 * @return array|bool|string|void|WP_Error
		 */
		public function update_zoom_meeting( $zoom_meeting_id, $data = array() ) {

			$update_zoom_meeting_data = apply_filters( 'mhub_zoom_update_meeting', $data );

			if ( ! empty( $update_zoom_meeting_data ) ) {
				$response = $this->send_request( 'meetings/' . $zoom_meeting_id, $update_zoom_meeting_data, 'PATCH' );
				return $response;
			} else {
				return;
			}
		}

		/**
		 * Retrieve information for a Zoom meeting by its ID.
		 *
		 * @param int   $id   The ID of the Zoom meeting.
		 * @param array $args Additional arguments for retrieving meeting information.
		 *
		 * @return array
		 */
		public function get_meeting_info( $id, $args = array() ) {
			$get_meeting_info_array = apply_filters( 'mhub_get_meeting_info', $args );
			return $this->send_request( 'meetings/' . $id, $get_meeting_info_array, 'GET' );
		}

		/**
		 * Retrieve details for a past Zoom meeting by its ID.
		 *
		 * @param int $meetingid The ID of the past Zoom meeting.
		 *
		 * @return array|bool|string|WP_Error
		 */
		public function get_past_meeting_details( $meetingid ) {
			return $this->send_request( 'past_meetings/' . $meetingid . '/instances', false, 'GET' );
		}

		/**
		 * Delete a Zoom meeting by its ID.
		 *
		 * @param int $meeting_id The ID of the Zoom meeting to delete.
		 *
		 * @return array|bool|string|WP_Error
		 */
		public function delete_meeting( $meeting_id ) {
			return $this->send_request( 'meetings/' . $meeting_id, false, 'DELETE' );
		}

		/**
		 * End a Zoom meeting using the meeting status endpoint.
		 * Reference: https://developers.zoom.us/docs/api/rest/reference/zoom-api/methods/#operation/meetingStatus
		 *
		 * @param int $meeting_id The ID of the Zoom meeting to end.
		 *
		 * @return array|bool|string|WP_Error
		 */
		public function end_meeting( $meeting_id ) {
			return $this->send_request( '/meetings/' . $meeting_id . '/status', array( 'action' => 'end' ), 'PUT' );
		}

		/**
		 * Register participants for a Zoom webinar.
		 *
		 * @param int    $webinar_id The ID of the Zoom webinar.
		 * @param string $first_name Participant's first name.
		 * @param string $last_name  Participant's last name.
		 * @param string $email      Participant's email address.
		 *
		 * @return array|bool|string|WP_Error
		 */
		public function register_webinar_participants( $webinar_id, $first_name, $last_name, $email ) {
			$post_data               = array();
			$post_data['first_name'] = $first_name;
			$post_data['last_name']  = $last_name;
			$post_data['email']      = $email;

			return $this->send_request( 'webinars/' . $webinar_id . '/registrants', $post_data, 'POST' );
		}

		/**
		 * List webinars for a specified user with optional pagination and additional arguments.
		 *
		 * @param int   $user_id The ID of the Zoom user.
		 * @param array $args   Additional arguments for listing webinars.
		 *
		 * @return bool|mixed
		 */
		public function list_webinar( $user_id, $args = array() ) {
			$defaults = array(
				'page_size' => 300,
			);

			// Parse incoming $args into an array and merge it with $defaults.
			$args = wp_parse_args( $args, $defaults );
			$args = apply_filters( 'mhub_zoom_list_webinar', $args );

			return $this->send_request( 'users/' . $user_id . '/webinars', $args, 'GET' );
		}

		/**
		 * Create a Zoom webinar for a specified user.
		 *
		 * @param int   $user_id The ID of the Zoom user initiating the webinar creation.
		 * @param array $data    Additional data for configuring the webinar.
		 *
		 * @return array|bool|string|void|WP_Error The result of the Zoom API request.
		 */
		public function create_webinar( $user_id, $data = array() ) {
			$post_data = apply_filters( 'mhub_create_webinar', $data );

			return $this->send_request( 'users/' . $user_id . '/webinars', $post_data, 'POST' );
		}

		/**
		 * Update information for a Zoom webinar identified by its ID.
		 *
		 * @param string $webinar_id The ID of the Zoom webinar to update.
		 * @param array  $data       Data to update for the webinar.
		 *
		 * @return array|bool|string|void|WP_Error The result of the Zoom API request.
		 */
		public function update_webinar( $webinar_id, $data = array() ) {
			$post_data      = apply_filters( 'mhub_update_webinar', $data );
			$agenda         = strip_tags( html_entity_decode( $data['agenda'] ), null );
			$data['agenda'] = substr( $agenda, 0, 1999 );
			return $this->send_request( 'webinars/' . $webinar_id, $post_data, 'PATCH' );
		}

		/**
		 * Get information for a Zoom webinar identified by its ID.
		 *
		 * @param string $id The ID of the Zoom webinar.
		 *
		 * @return array|bool|string|WP_Error The result of the Zoom API request.
		 */
		public function get_webinar_info( $id ) {
			$get_meeting_info_array = apply_filters( 'mhub_get_webinar_info', array() );

			return $this->send_request( 'webinars/' . $id, $get_meeting_info_array, 'GET' );
		}

		/**
		 * List participants for a Zoom webinar.
		 *
		 * @param string $webinar_id The ID of the Zoom webinar.
		 * @param array  $args      Additional arguments for listing participants.
		 *
		 * @return bool|mixed The result of the Zoom API request.
		 */
		public function list_webinar_participants( $webinar_id, $args = array() ) {
			$defaults = array(
				'page_size' => 300,
			);

			// Parse incoming $args into an array and merge it with $defaults.
			$args = wp_parse_args( $args, $defaults );
			$args = apply_filters( 'mhub_list_webinar_participants', $args );

			return $this->send_request( 'webinars/' . $webinar_id . '/registrants', $args, 'GET' );
		}

		/**
		 * Delete a Zoom webinar by its ID.
		 *
		 * @param int $webinar_id The ID of the Zoom webinar to delete.
		 *
		 * @return array|bool|string|WP_Error
		 */
		public function delete_webinar( $webinar_id ) {
			return $this->send_request( 'webinars/' . $webinar_id, false, 'DELETE' );
		}

		/**
		 * Retrieves the daily report for a specified month and year.
		 *
		 * Retrieves the daily report for the specified month and year by sending a GET request to the API endpoint.
		 *
		 * @param int $month The month for which the report is requested.
		 * @param int $year The year for which the report is requested.
		 * @return array|null Returns an array containing the daily report data if successful, or null if the request fails.
		 */
		public function get_daily_report( $month, $year ) {
			$get_daily_report_array          = array();
			$get_daily_report_array['year']  = $year;
			$get_daily_report_array['month'] = $month;

			return $this->send_request( 'report/daily', $get_daily_report_array, 'GET' );
		}

		/**
		 * Retrieves the account report for a specified Zoom account range.
		 *
		 * Retrieves the account report for the specified range of Zoom accounts by sending a GET request to the API endpoint.
		 * The default page size for the report is set to 300.
		 *
		 * @param int $zoom_account_from The starting Zoom account ID.
		 * @param int $zoom_account_to The ending Zoom account ID.
		 * @return array|null Returns an array containing the account report data if successful, or null if the request fails.
		 */
		public function get_account_report( $zoom_account_from, $zoom_account_to ) {
			$get_account_report_array              = array();
			$get_account_report_array['from']      = $zoom_account_from;
			$get_account_report_array['to']        = $zoom_account_to;
			$get_account_report_array['page_size'] = 300;

			return $this->send_request( 'report/users', $get_account_report_array, 'GET' );
		}

		/**
		 * List Zoom recordings for a specific host within a date range.
		 *
		 * This function retrieves a list of Zoom recordings for a given host ID within a specified date range.
		 * If the date range is not provided, it defaults to the past two months. The date range data is filtered
		 * through the 'mhub_zoom_recordings' filter before making the request.
		 *
		 * @param string      $host_id The ID of the Zoom host user.
		 * @param string|null $from The start date of the recordings in 'Y-m-d' format. Defaults to two months ago if not provided.
		 * @param string|null $to The end date of the recordings in 'Y-m-d' format. Defaults to the current date if not provided.
		 *
		 * @return array|WP_Error The response from the Zoom API, which includes the list of recordings, or an error if the request fails.
		 */
		public function list_recording( $host_id, $from, $to ) {
			$data['from'] = ! empty( $from ) ? $from : gmdate( 'Y-m-d', strtotime( '-2 month', time() ) );
			$data['to']   = ! empty( $to ) ? $to : gmdate( 'Y-m-d' );
			$data         = apply_filters( 'mhub_zoom_recordings', $data );

			return $this->send_request( 'users/' . $host_id . '/recordings', $data, 'GET' );
		}

		/**
		 * Retrieve Zoom recordings for a specific meeting by meeting ID.
		 *
		 * This function interacts with the Zoom API to fetch recordings for a specified meeting ID.
		 * It sends a GET request to the Zoom API and returns the response.
		 *
		 * @param string $meeting_id The ID of the Zoom meeting.
		 *
		 * @return array|WP_Error The response from the Zoom API, which includes the recordings for the specified meeting, or an error if the request fails.
		 */
		public function recordings_by_meeting( $meeting_id ) {
			return $this->send_request( 'meetings/' . $meeting_id . '/recordings', false, 'GET' );
		}
	}

}
