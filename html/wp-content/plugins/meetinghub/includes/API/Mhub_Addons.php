<?php
/**
 * Mhub_Settings API Class
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

if ( ! class_exists( 'Mhub_Settings' ) ) {
	/**
	 * Mhub_Settings Class
	 */
	class Mhub_Settings extends WP_REST_Controller {
		/**
		 * Mhub_Settings constructor
		 */
		public function __construct() {
			$this->namespace = 'meetinghub/v2';
			$this->rest_base = 'settings';
		}

		/**
		 * Register REST API routes for settings
		 *
		 * @since 1.0.0
		 */
		public function register_routes() {

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/zoom',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_zoom_settings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'save_zoom_settings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
				)
			);

			// New route for webex settings.
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/webex',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_webex_settings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'save_webex_settings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
				)
			);

			// New route for google meet settings.
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/google_meet',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_google_meet_settings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'save_google_meet_settings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
				)
			);

			// New route for Jitsi settings.
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/jitsi',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_jitsi_settings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'save_jitsi_settings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
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
		 * Retrieves settings.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_zoom_settings( $request ) {
			$settings = get_option( 'mhub_zoom_settings', array() );
			$response = rest_ensure_response( $settings );
			return $response;
		}

		/**
		 * Retrieves jitsi settings api.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_jitsi_settings( $request ) {
			$settings = get_option( 'mhub_jitsi_settings', array() );
			$response = rest_ensure_response( $settings );
			return $response;
		}

		/**
		 * Retrieves webex settings api.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_webex_settings( $request ) {
			$settings = get_option( 'mhub_webex_settings', array() );
			$response = rest_ensure_response( $settings );
			return $response;
		}

		/**
		 * Retrieves google meet settings api.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_google_meet_settings( $request ) {
			$settings = get_option( 'mhub_google_meet_settings', array() );
			$response = rest_ensure_response( $settings );
			return $response;
		}

		/**
		 * Save Zoom settings.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function save_zoom_settings( $request ) {
			$data = json_decode( $request->get_body() );

			// Validate and sanitize the data.
			$validated_data = $this->validate_and_sanitize_zoom_settings( $data );

			if ( is_wp_error( $validated_data ) ) {
				return $validated_data;
			}

			// Update settings in the options.
			update_option( 'mhub_zoom_settings', $validated_data );

			$response = array(
				'zoom_settings_saved' => true,
				'zoom_api_settings'   => $validated_data,
			);

			return rest_ensure_response( $response );
		}

		/**
		 * Save jitsi settings Api.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function save_jitsi_settings( $request ) {
			$data = json_decode( $request->get_body() );

			// Validate and sanitize the data.
			$validated_data = $this->validate_and_sanitize_jitsi_settings( $data );

			if ( is_wp_error( $validated_data ) ) {
				return $validated_data;
			}

			// Update settings in the options.
			update_option( 'mhub_jitsi_settings', $validated_data );

			$response = array(
				'jitsi_settings_saved' => true,
				'jitsi_settings'       => $validated_data,
			);

			return rest_ensure_response( $response );
		}

		/**
		 * Save Webex settings Api
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function save_webex_settings( $request ) {
			$data = json_decode( $request->get_body() );

			// Validate and sanitize the data.
			$validated_data = $this->validate_and_sanitize_webex_settings( $data );

			if ( is_wp_error( $validated_data ) ) {
				return $validated_data;
			}

			// Update settings in the options.
			update_option( 'mhub_webex_settings', $validated_data );

			$response = array(
				'webex_settings_saved' => true,
				'webex_settings'       => $validated_data,
			);

			return rest_ensure_response( $response );
		}

		/**
		 * Save Google meet settings Api
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function save_google_meet_settings( $request ) {
			$data = json_decode( $request->get_body() );

			// Validate and sanitize the data.
			$validated_data = $this->validate_and_sanitize_google_meet_settings( $data );

			if ( is_wp_error( $validated_data ) ) {
				return $validated_data;
			}

			// Update settings in the options.
			update_option( 'mhub_google_meet_settings', $validated_data );

			$response = array(
				'google_meet_settings_saved' => true,
				'google_meet_settings'       => $validated_data,
			);

			return rest_ensure_response( $response );
		}

		/**
		 * Validate and sanitize settings.
		 *
		 * @param object $data Mhub_Settings data.
		 *
		 * @return WP_Error|array Validated and sanitized settings data.
		 */
		private function validate_and_sanitize_zoom_settings( $data ) {
			// Sanitize data before saving.
			$sanitized_data = array(
				'oauth_account_id'          => isset( $data->oauth_account_id ) ? sanitize_text_field( $data->oauth_account_id ) : '',
				'oauth_client_id'           => isset( $data->oauth_client_id ) ? sanitize_text_field( $data->oauth_client_id ) : '',
				'oauth_client_secret'       => isset( $data->oauth_client_secret ) ? sanitize_text_field( $data->oauth_client_secret ) : '',
				'sdk_client_id'             => isset( $data->sdk_client_id ) ? sanitize_text_field( $data->sdk_client_id ) : '',
				'sdk_client_secret'         => isset( $data->sdk_client_secret ) ? sanitize_text_field( $data->sdk_client_secret ) : '',
				'enable_recurring_meeting'  => isset( $data->enable_recurring_meeting ) ? sanitize_text_field( $data->enable_recurring_meeting ) : '',
				'meeting_timezone'          => isset( $data->meeting_timezone ) ? sanitize_text_field( $data->meeting_timezone ) : '',
				'enable_should_register'    => isset( $data->enable_should_register ) ? sanitize_text_field( $data->enable_should_register ) : '',
				'disable_waiting_room'      => isset( $data->disable_waiting_room ) ? sanitize_text_field( $data->disable_waiting_room ) : '',
				'meeting_authentication'    => isset( $data->meeting_authentication ) ? sanitize_text_field( $data->meeting_authentication ) : '',
				'join_before_host'          => isset( $data->join_before_host ) ? sanitize_text_field( $data->join_before_host ) : '',
				'option_mute_participants'  => isset( $data->option_mute_participants ) ? sanitize_text_field( $data->option_mute_participants ) : '',
				'practice_session'          => isset( $data->practice_session ) ? sanitize_text_field( $data->practice_session ) : '',
				'allow_multiple_devices'    => isset( $data->allow_multiple_devices ) ? sanitize_text_field( $data->allow_multiple_devices ) : '',
				'auto_recording'            => isset( $data->auto_recording ) ? sanitize_text_field( $data->auto_recording ) : '',
				'hide_sidebar'              => isset( $data->hide_sidebar ) ? sanitize_text_field( $data->hide_sidebar ) : '',
				'hide_header_footer'        => isset( $data->hide_header_footer ) ? sanitize_text_field( $data->hide_header_footer ) : '',
				'option_host_video'         => isset( $data->option_host_video ) ? sanitize_text_field( $data->option_host_video ) : '',
				'option_participants_video' => isset( $data->option_participants_video ) ? sanitize_text_field( $data->option_participants_video ) : '',
				'panelists_video'           => isset( $data->panelists_video ) ? sanitize_text_field( $data->panelists_video ) : '',
				'hd_video'                  => isset( $data->hd_video ) ? sanitize_text_field( $data->hd_video ) : '',
			);

			return $sanitized_data;
		}

		/**
		 * Validate and sanitize jitsi settings api.
		 *
		 * @param object $data Mhub Settings API data.
		 *
		 * @return WP_Error|array Validated and sanitized settings data.
		 */
		private function validate_and_sanitize_jitsi_settings( $data ) {
			// Sanitize data before saving.
			$sanitized_data = array(
				'domain_type'               => isset( $data->domain_type ) ? sanitize_text_field( $data->domain_type ) : '',
				'custom_domain'             => isset( $data->custom_domain ) ? sanitize_text_field( $data->custom_domain ) : '',
				'app_id'                    => isset( $data->app_id ) ? sanitize_text_field( $data->app_id ) : '',
				'api_key'                   => isset( $data->api_key ) ? sanitize_text_field( $data->api_key ) : '',
				'private_key'               => isset( $data->private_key ) ? $data->private_key : '',
				'yourself_muted'            => isset( $data->yourself_muted ) ? sanitize_text_field( $data->yourself_muted ) : '',
				'audio_muted'               => isset( $data->audio_muted ) ? sanitize_text_field( $data->audio_muted ) : '',
				'audio_only'                => isset( $data->audio_only ) ? sanitize_text_field( $data->audio_only ) : '',
				'start_silent'              => isset( $data->start_silent ) ? sanitize_text_field( $data->start_silent ) : '',
				'start_with_video_muted'    => isset( $data->start_with_video_muted ) ? sanitize_text_field( $data->start_with_video_muted ) : '',
				'start_with_screen_sharing' => isset( $data->start_with_screen_sharing ) ? sanitize_text_field( $data->start_with_screen_sharing ) : '',
				'video_resolution'          => isset( $data->video_resolution ) ? sanitize_text_field( $data->video_resolution ) : '',
				'max_full_resolution'       => isset( $data->max_full_resolution ) ? sanitize_text_field( $data->max_full_resolution ) : '',
				'video_muted_after'         => isset( $data->video_muted_after ) ? sanitize_text_field( $data->video_muted_after ) : '',
				'height'                    => isset( $data->height ) ? sanitize_text_field( $data->height ) : '',
				'width'                     => isset( $data->width ) ? sanitize_text_field( $data->width ) : '',
				'enable_inviting'           => isset( $data->enable_inviting ) ? sanitize_text_field( $data->enable_inviting ) : '',
				'hide_inviting'             => isset( $data->hide_inviting ) ? sanitize_text_field( $data->hide_inviting ) : '',
				'enable_recording'          => isset( $data->enable_recording ) ? sanitize_text_field( $data->enable_recording ) : '',
				'enable_simulcast'          => isset( $data->enable_simulcast ) ? sanitize_text_field( $data->enable_simulcast ) : '',
				'enable_livestreaming'      => isset( $data->enable_livestreaming ) ? sanitize_text_field( $data->enable_livestreaming ) : '',
				'enable_welcome_page'       => isset( $data->enable_welcome_page ) ? sanitize_text_field( $data->enable_welcome_page ) : '',
				'enable_transcription'      => isset( $data->enable_transcription ) ? sanitize_text_field( $data->enable_transcription ) : '',
				'enable_outbound'           => isset( $data->enable_outbound ) ? sanitize_text_field( $data->enable_outbound ) : '',
				'hide_sidebar'              => isset( $data->hide_sidebar ) ? sanitize_text_field( $data->hide_sidebar ) : '',
				'enable_should_register'    => isset( $data->enable_should_register ) ? sanitize_text_field( $data->enable_should_register ) : '',
				'enable_recurring_meeting'  => isset( $data->enable_recurring_meeting ) ? sanitize_text_field( $data->enable_recurring_meeting ) : '',
				'meeting_timezone'          => isset( $data->meeting_timezone ) ? sanitize_text_field( $data->meeting_timezone ) : '',
				'hide_header_footer'        => isset( $data->hide_header_footer ) ? sanitize_text_field( $data->hide_header_footer ) : '',
			);

			return $sanitized_data;
		}

		/**
		 * Validate and sanitize webex settings api.
		 *
		 * @param object $data Mhub Settings API data.
		 *
		 * @return WP_Error|array Validated and sanitized settings data.
		 */
		private function validate_and_sanitize_webex_settings( $data ) {
			// Sanitize data before saving.
			$sanitized_data = array(
				'client_id'                => isset( $data->client_id ) ? sanitize_text_field( $data->client_id ) : '',
				'client_secret'            => isset( $data->client_secret ) ? sanitize_text_field( $data->client_secret ) : '',
				'meeting_timezone'         => isset( $data->meeting_timezone ) ? sanitize_text_field( $data->meeting_timezone ) : '',
				'hide_sidebar'             => isset( $data->hide_sidebar ) ? sanitize_text_field( $data->hide_sidebar ) : '',
				'hide_header_footer'       => isset( $data->hide_header_footer ) ? sanitize_text_field( $data->hide_header_footer ) : '',
				'auto_record'              => isset( $data->auto_record ) ? sanitize_text_field( $data->auto_record ) : '',
				'breakout_sessions'        => isset( $data->breakout_sessions ) ? sanitize_text_field( $data->breakout_sessions ) : '',
				'automatic_lock'           => isset( $data->automatic_lock ) ? sanitize_text_field( $data->automatic_lock ) : '',
				'lock_minutes'             => isset( $data->lock_minutes ) ? sanitize_text_field( $data->lock_minutes ) : '',
				'enable_should_register'   => isset( $data->enable_should_register ) ? sanitize_text_field( $data->enable_should_register ) : '',
				'enable_recurring_meeting' => isset( $data->enable_recurring_meeting ) ? sanitize_text_field( $data->enable_recurring_meeting ) : '',
				'join_before_host'         => isset( $data->join_before_host ) ? sanitize_text_field( $data->join_before_host ) : '',
			);

			return $sanitized_data;
		}

		/**
		 * Validate and sanitize google meet settings api.
		 *
		 * @param object $data Mhub Settings API data.
		 *
		 * @return WP_Error|array Validated and sanitized settings data.
		 */
		private function validate_and_sanitize_google_meet_settings( $data ) {
			// Sanitize data before saving.
			$sanitized_data = array(
				'meeting_timezone'         => isset( $data->meeting_timezone ) ? sanitize_text_field( $data->meeting_timezone ) : '',
				'hide_sidebar'             => isset( $data->hide_sidebar ) ? sanitize_text_field( $data->hide_sidebar ) : '',
				'hide_header_footer'       => isset( $data->hide_header_footer ) ? sanitize_text_field( $data->hide_header_footer ) : '',
				'reminder_time'            => isset( $data->reminder_time ) ? sanitize_text_field( $data->reminder_time ) : '',
				'event_status'             => isset( $data->event_status ) ? sanitize_text_field( $data->event_status ) : '',
				'send_updates'             => isset( $data->send_updates ) ? sanitize_text_field( $data->send_updates ) : '',
				'transparency'             => isset( $data->transparency ) ? sanitize_text_field( $data->transparency ) : '',
				'event_visibility'         => isset( $data->event_visibility ) ? sanitize_text_field( $data->event_visibility ) : '',
				'enable_should_register'   => isset( $data->enable_should_register ) ? sanitize_text_field( $data->enable_should_register ) : '',
				'enable_recurring_meeting' => isset( $data->enable_recurring_meeting ) ? sanitize_text_field( $data->enable_recurring_meeting ) : '',
			);

			return $sanitized_data;
		}
	}
}
