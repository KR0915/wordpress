<?php
/**
 * Mhub_Zoom API Class
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

if ( ! class_exists( 'Mhub_Zoom' ) ) {
	/**
	 * Mhub_Zoom Class
	 */
	class Mhub_Zoom extends WP_REST_Controller {
		/**
		 * Zoom Api class.
		 *
		 * @var Mhub_Zoom_Api
		 */
		private $zoom_api;

		/**
		 * Mhub_Zoom constructor
		 */
		public function __construct() {
			$this->namespace = 'meetinghub/v2';
			$this->rest_base = 'zoom';
			$this->zoom_api  = \SOVLIX\MHUB\Zoom\Mhub_Zoom_Api::instance();
		}

		/**
		 * Register REST API routes for users
		 *
		 * @since 1.0.0
		 */
		public function register_routes() {

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/users',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'mhub_get_zoom_users' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'mhub_create_zoom_user' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/meetings',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'mhub_get_zoom_meetings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/recordings',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'mhub_get_zoom_recordings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
						'args'                => array(
							'fromDate' => array(
								'required'          => true,
								'validate_callback' => function ( $param, $request, $key ) {
									return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $param );
								},
							),
							'toDate'   => array(
								'required'          => true,
								'validate_callback' => function ( $param, $request, $key ) {
									return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $param );
								},
							),
						),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/meeting_recordings',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'mhub_get_zoom_recordings_by_meeting_id' ),
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
		 * Retrieves users.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function mhub_get_zoom_users( $request ) {
			$zoom_response = json_decode( $this->zoom_api->list_users(), true );
			return $zoom_response;
		}

		/**
		 * Creates one item from the collection.
		 *
		 * @param \WP_REST_Request $request Post request.
		 *
		 * @return \WP_Error|WP_REST_Response
		 */
		public function mhub_create_zoom_user( $request ) {
			$posted        = json_decode( $request->get_body() );
			$zoom_response = json_decode( $this->zoom_api->create_user( $posted ), true );
			return $zoom_response;
		}

		/**
		 * Retrieves Zoom meetings.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function mhub_get_zoom_meetings( $request ) {
			$host_id      = $request->get_param( 'host_id' );
			$meeting_type = $request->get_param( 'meeting_type' );

			// Check if host_id parameter is provided.
			if ( empty( $host_id ) ) {
				return new WP_Error( 'missing_parameter', __( 'Host ID is required.', 'meetinghub' ), array( 'status' => 400 ) );
			}

			if ( 'meeting' === $meeting_type ) {
				$zoom_response = json_decode( $this->zoom_api->list_meetings( $host_id ), true );
			}

			if ( 'webinar' === $meeting_type ) {
				$zoom_response = json_decode( $this->zoom_api->list_webinar( $host_id ), true );
			}

			return $zoom_response;
		}

		/**
		 * Retrieves zoom recordings.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function mhub_get_zoom_recordings( $request ) {
			$from_date = $request->get_param( 'fromDate' );
			$to_date   = $request->get_param( 'toDate' );

			// Ensure dates are in the correct format.
			$from_date_formatted = gmdate( 'Y-m-d', strtotime( $from_date ) );
			$to_date_formatted   = gmdate( 'Y-m-d', strtotime( $to_date ) );
			$host_id             = $request->get_param( 'host_id' );

			// Ensure dates are in the correct format.
			$from_date_formatted = gmdate( 'Y-m-d', strtotime( $from_date ) );
			$to_date_formatted   = gmdate( 'Y-m-d', strtotime( $to_date ) );

			$zoom_response = json_decode( $this->zoom_api->list_recording( $host_id, $from_date_formatted, $to_date_formatted ), true );

			return $zoom_response;
		}

		/**
		 * Retrieve Zoom recordings by meeting ID.
		 *
		 * This function interacts with the Zoom API to retrieve recordings for a specific meeting.
		 * It extracts the meeting ID from the request parameters, makes an API call to Zoom to fetch the recordings,
		 * and returns the decoded JSON response.
		 *
		 * @param WP_REST_Request $request The REST API request object containing the meeting ID.
		 *
		 * @return array The decoded JSON response from the Zoom API, which includes the recordings for the specified meeting.
		 */
		public function mhub_get_zoom_recordings_by_meeting_id( $request ) {
			$meeting_id    = $request->get_param( 'meeting_id' );
			$zoom_response = json_decode( $this->zoom_api->recordings_by_meeting( $meeting_id ), true );
			return $zoom_response;
		}
	}
}
