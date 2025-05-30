<?php //phpcs:ignore

/**
 * File: Mhub_Ajax.php
 *
 * The Mhub_Ajax class handles AJAX requests and provides functionalities for generating referral coupons.
 *
 * @package ECRE
 * @since   1.0.0
 */

namespace SOVLIX\MHUB;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mhub_Ajax' ) ) {
	/**
	 * The Mhub_Ajax class handles AJAX requests and provides functionalities for generating referral coupons.
	 *
	 * @since 1.0.0
	 */
	class Mhub_Ajax {
		/**
		 * Mhub_Ajax constructor.
		 *
		 * Initializes the class and sets up AJAX request handlers.
		 */
		public function __construct() {
			add_action( 'wp_ajax_mhub_zoom_meeting_sign', array( $this, 'mhub_generate_signature' ) );
			add_action( 'wp_ajax_nopriv_mhub_zoom_meeting_sign', array( $this, 'mhub_generate_signature' ) );
			add_action( 'wp_ajax_mhub_meeting_action', array( $this, 'handle_mhub_meeting_action' ) );
			add_action( 'wp_ajax_nopriv_mhub_meeting_action', array( $this, 'handle_mhub_meeting_action' ) );
			add_action( 'wp_ajax_mhub_meeting_list', array( $this, 'mhub_meeting_lists' ) );
			add_action( 'wp_ajax_nopriv_mhub_meeting_list', array( $this, 'mhub_meeting_lists' ) );
			add_action( 'wp_ajax_mhub_google_meeting_list', array( $this, 'mhub_google_meeting_lists' ) );
			add_action( 'wp_ajax_nopriv_mhub_google_meeting_list', array( $this, 'mhub_google_meeting_lists' ) );
			add_action( 'wp_ajax_mhub_reset_google_meet_credential', array( $this, 'reset_credential' ) );
			add_action( 'wp_ajax_mhub_check_google_meet_credential', array( $this, 'check_credential_exists' ) );
			add_action( 'wp_ajax_mhub_check_google_meet_permission', array( $this, 'mhub_check_google_meet_permission' ) );
			add_action( 'wp_ajax_mhub_get_link', array( $this, 'mhub_get_link' ) );
			add_action( 'wp_ajax_mhub_renew_zoom_oauth', array( $this, 'mhub_renew_zoom_oauth_callback' ) );
		}

		/**
		 * Handles AJAX requests for fetching meeting lists.
		 *
		 * This function verifies the nonce for security, fetches the list of meetings,
		 * and returns the meetings as a JSON response. It is intended to be used as a
		 * callback for AJAX requests in WordPress.
		 *
		 * @since 1.13.1
		 *
		 * @return void
		 */
		public function mhub_meeting_lists() {
			// Verify nonce for security.
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mhub_frontend_nonce' ) ) {
				wp_send_json_error( 'Invalid nonce.' );
			}

			$meetings = mhub_meetings();

			wp_send_json_success( $meetings );
		}

		/**
		 * Handles AJAX requests to fetch a list of Google Meet meetings.
		 *
		 * This function verifies the nonce for security, interacts with the Google Meet API
		 * through the `Mhub_Events` class, and returns the list of meetings in JSON format.
		 *
		 * @return void
		 *
		 * @throws WP_Error If the nonce verification fails.
		 */
		public function mhub_google_meeting_lists() {
			// Verify nonce for security.
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mhub_admin_nonce' ) ) {
				wp_send_json_error( 'Invalid nonce.' );
			}

			$google_api = new \SOVLIX\MHUB\Googlemeet\Mhub_Events();

			$google_meet_response = $google_api->get_all_meetings();

			wp_send_json_success( $google_meet_response );
		}

		/**
		 * Generate Signature
		 */
		public function mhub_generate_signature() {

			$request = file_get_contents( 'php://input' );
			$request = json_decode( $request );
			//phpcs:ignore
			$meeting_number = $request->meetingNumber;
			$role           = $request->role;
			$zoom_settings  = get_option( 'mhub_zoom_settings', true );
			$api_key        = $zoom_settings['sdk_client_id'];
			$api_secret     = $zoom_settings['sdk_client_secret'];

			$time = time() * 1000 - 30000;
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$data = base64_encode( $api_key . $meeting_number . $time . $role );

			$hash = hash_hmac( 'sha256', $data, $api_secret, true );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$_sig = $api_key . '.' . $meeting_number . '.' . $time . '.' . $role . '.' . base64_encode( $hash );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$res     = rtrim( strtr( base64_encode( $_sig ), '+/', '-_' ), '=' );
			$results = array( $res );
			echo wp_json_encode( $results );
			wp_die();
		}

		/**
		 * Handle end meeting action
		 */
		public function handle_mhub_meeting_action() {
			// Verify nonce for security.
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mhub_frontend_nonce' ) ) {
				wp_send_json_error( 'Invalid nonce.' );
			}

			$response = '';

			// Retrieve and sanitize posted data.
			$meeting_id     = isset( $_POST['meeting_id'] ) ? sanitize_text_field( wp_unslash( $_POST['meeting_id'] ) ) : '';
			$post_id        = isset( $_POST['post_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) : '';
			$meeting_status = isset( $_POST['meeting_status'] ) ? sanitize_text_field( wp_unslash( $_POST['meeting_status'] ) ) : '';

			if ( 'end' === $meeting_status ) {
				update_post_meta( $post_id, 'mhub_meeting_status', 'end' );
				$response = 'Meeting ended successfully.';
			}

			if ( 'resume' === $meeting_status ) {
				update_post_meta( $post_id, 'mhub_meeting_status', 'resume' );
				$response = 'Meeting resumed successfully.';
			}

			if ( 'start' === $meeting_status ) {
				update_post_meta( $post_id, 'mhub_meeting_start_status', 'start' );
				$response = 'Meeting started successfully.';
			}

			if ( 'stop' === $meeting_status ) {
				update_post_meta( $post_id, 'mhub_meeting_start_status', 'stop' );
				$response = 'Meeting stoped successfully.';
			}

			// Example: Respond with a success message.
			wp_send_json_success( $response );
		}

		/**
		 * Reset user credentials and tokens by deleting the associated files.
		 *
		 * Deletes the `credential.json` and `token.json` files for the current user.
		 * Sends a JSON response with the operation result.
		 *
		 * @return void
		 */
		public function reset_credential() {
			// Generate file names based on the current user's login name.
			$credential_file_name = 'credential.json';
			$token_file_name      = 'token.json';

			// File paths.
			$upload_dir           = \trailingslashit( wp_upload_dir()['basedir'] ) . 'mhub-json/';
			$credential_file_path = $upload_dir . $credential_file_name;
			$token_file_path      = $upload_dir . $token_file_name;

			// Initialize response messages.
			$messages = array();

			// Attempt to delete the credential file.
			if ( file_exists( $credential_file_path ) ) {
				if ( unlink( $credential_file_path ) ) {
					$messages[] = __( 'Credential file reset successfully!', 'meetinghub' );
				} else {
					$messages[] = __( 'Failed to reset credential file.', 'meetinghub' );
				}
			} else {
				$messages[] = __( 'Credential file does not exist.', 'meetinghub' );
			}

			// Attempt to delete the token file.
			if ( file_exists( $token_file_path ) ) {
				if ( unlink( $token_file_path ) ) {
					$messages[] = __( 'Token file reset successfully!', 'meetinghub' );
				} else {
					$messages[] = __( 'Failed to reset token file.', 'meetinghub' );
				}
			} else {
				$messages[] = __( 'Token file does not exist.', 'meetinghub' );
			}

			// Check if any success messages exist.
			if ( array_filter( $messages, fn( $msg ) => str_contains( $msg, 'successfully' ) ) ) {
				wp_send_json_success( implode( ' ', $messages ) );
			} else {
				wp_send_json_error( implode( ' ', $messages ) );
			}
		}

		/**
		 * Check if the user's credential file exists.
		 *
		 * Verifies the existence of the `credential.json` file for the current user.
		 * Sends a JSON response with the result.
		 *
		 * @return void
		 */
		public function check_credential_exists() {
			$file_path = trailingslashit( wp_upload_dir()['basedir'] ) . 'mhub-json/credential.json';

			if ( file_exists( $file_path ) ) {
				wp_send_json_success( array( 'message' => 'Credential exists' ) );
			} else {
				wp_send_json_error( array( 'message' => 'Credential not found' ) );
			}

			wp_die();
		}

		/**
		 * Retrieve the Google Meet consent screen URL.
		 *
		 * Uses the `Mhub_Meet` instance to generate and return the consent screen URL.
		 * Sends a JSON response with the URL or an error message.
		 *
		 * @return void
		 */
		public function mhub_get_link() {
			$google_meet = Googlemeet\Mhub_Meet::get_instance();
			$link        = $google_meet->get_consent_screen_url();

			if ( ! empty( $link ) ) {
				wp_send_json_success( array( 'link' => $link ) );
			} else {
				wp_send_json_error( __( 'Failed to retrieve the consent screen URL.', 'meetinghub' ) );
			}
		}

		/**
		 * Check Google Meet permissions and update the plugin settings.
		 *
		 * Verifies if the Google Meet application is permitted.
		 * Updates the `mhub_google_meet_account` option based on the permission status.
		 * Sends a JSON response with the permission status or an error message.
		 *
		 * @return void
		 */
		public function mhub_check_google_meet_permission() {
			// Check if the user has sufficient permissions (optional but recommended).
			if ( ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => 'Unauthorized access' ), 403 );
				return;
			}

			// Initialize Google Meet instance.
			try {
				$google_meet = Googlemeet\Mhub_Meet::get_instance();
				$permission  = $google_meet->is_app_permitted();

				if ( $permission ) {
					update_option( 'mhub_google_meet_account', 'connected' );
				} else {
					update_option( 'mhub_google_meet_account', '' );
				}

				// Send success response with permission status.
				wp_send_json_success( array( 'permission' => $permission ) );
			} catch ( Exception $e ) {
				// Handle any exceptions and send an error response.
				update_option( 'mhub_google_meet_account', '' );
				wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
			}
		}

		/**
		 * Handles the renewal of Zoom OAuth tokens via AJAX.
		 *
		 * This function verifies the request, checks user permissions, retrieves
		 * the stored Zoom OAuth credentials, and attempts to renew the access token.
		 * If successful, it saves the new token; otherwise, it returns an error response.
		 *
		 * @since 1.0.0
		 * @return void Outputs a JSON response indicating success or failure.
		 */
		public function mhub_renew_zoom_oauth_callback() {
			// Verify nonce.
			$nonce = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mhub_admin_nonce' ) ) {
				wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
			}

			// Check user capability.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Unauthorized request.' ) );
			}

			// Get Zoom settings from options.
			$zoom_settings = get_option( 'mhub_zoom_settings' );

			// Validate settings.
			if ( ! is_array( $zoom_settings ) || empty( $zoom_settings ) ) {
				update_option( 'mhub_zoom_global_oauth_data', '' );
				wp_send_json_error( array( 'message' => 'Renew failed. Set OAuth credentials first.' ) );
			}

			// Extract credentials.
			$account_id    = $zoom_settings['oauth_account_id'] ?? '';
			$client_id     = $zoom_settings['oauth_client_id'] ?? '';
			$client_secret = $zoom_settings['oauth_client_secret'] ?? '';

			// Ensure all required fields are set.
			if ( empty( $account_id ) || empty( $client_id ) || empty( $client_secret ) ) {
				update_option( 'mhub_zoom_global_oauth_data', '' );
				wp_send_json_error( array( 'message' => 'Renew failed. Missing OAuth credentials.' ) );
			}

			// Attempt to renew the access token.
			$zoom_auth = \SOVLIX\MHUB\Zoom\Mhub_S2SO_Auth::get_instance();
			$result    = $zoom_auth->generate_and_save_access_token( $account_id, $client_id, $client_secret );

			if ( is_wp_error( $result ) ) {
				update_option( 'mhub_zoom_global_oauth_data', '' );
				wp_send_json_error( array( 'message' => 'OAuth renewal failed: ' . $result->get_error_message() ) );
			}

			// Respond with success.
			wp_send_json_success( array( 'message' => 'OAuth Token Renewed Successfully' ) );
		}
	}
}
