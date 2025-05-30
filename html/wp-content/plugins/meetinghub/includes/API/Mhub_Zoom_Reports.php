<?php
/**
 * Mhub_Zoom_Reports API Class
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

if ( ! class_exists( 'Mhub_Zoom_Reports' ) ) {
	/**
	 * Mhub_Zoom_Reports Class
	 */
	class Mhub_Zoom_Reports extends WP_REST_Controller {
		/**
		 * Zoom Api class.
		 *
		 * @var Mhub_Zoom_Api
		 */
		private $zoom_api;

		/**
		 * Mhub_Zoom_Reports constructor
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
				'/' . $this->rest_base . '/reports',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'mhub_get_zoom_reports' ),
						'permission_callback' => array( $this, 'check_permissions' ),
						'args'                => array(
							'month' => array(
								'required'          => true,
								'validate_callback' => function ( $param, $request, $key ) {
									return is_numeric( $param ) && $param > 0 && $param <= 12;
								},
							),
							'year'  => array(
								'required'          => true,
								'validate_callback' => function ( $param, $request, $key ) {
									return is_numeric( $param ) && strlen( $param ) === 4;
								},
							),
						),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/reports/account',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'mhub_get_zoom_account_reports' ),
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
		 * Retrieves daily reports.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function mhub_get_zoom_reports( $request ) {
			$month = $request->get_param( 'month' );
			$year  = $request->get_param( 'year' );

			$zoom_response = json_decode( $this->zoom_api->get_daily_report( $month, $year ), true );
			return $zoom_response;
		}

		/**
		 * Retrieves account reports.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_Error|WP_REST_Response
		 */
		public function mhub_get_zoom_account_reports( $request ) {
			$from_date = $request->get_param( 'fromDate' );
			$to_date   = $request->get_param( 'toDate' );

			// Ensure dates are in the correct format.
			$from_date_formatted = gmdate( 'Y-m-d', strtotime( $from_date ) );
			$to_date_formatted   = gmdate( 'Y-m-d', strtotime( $to_date ) );

			$zoom_response = json_decode( $this->zoom_api->get_account_report( $from_date_formatted, $to_date_formatted ), true );
			return $zoom_response;
		}
	}
}
