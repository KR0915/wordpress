<?php // phpcs:ignore
/**
 * Mhub_API Class
 *
 * This class manages the Mhub_API functionality for the Meeting Hub plugin.
 *
 * @package SOVLIX\MHUB
 */

namespace SOVLIX\MHUB;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mhub_API' ) ) {
	/**
	 * Mhub_API Class
	 *
	 * Handles Mhub_API-related tasks and functionality for the Meeting Hub plugin.
	 *
	 * @since 1.0.0
	 */
	class Mhub_API {
		/**
		 * Mhub_API constructor.
		 *
		 * Initializes the Mhub_API class.
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_api' ) );
		}

		/**
		 * Register the Mhub_API
		 *
		 * Performs tasks related to registering the Mhub_API for the Meeting Hub plugin.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function register_api() {
			$meetings = new API\Mhub_Meetings();
			$meetings->register_routes();
			$settings = new API\Mhub_Settings();
			$settings->register_routes();
			$zoom = new API\Mhub_Zoom();
			$zoom->register_routes();
			$zoom_reports = new API\Mhub_Zoom_Reports();
			$zoom_reports->register_routes();
			$webex = new API\Mhub_Webex();
			$webex->register_routes();
		}
	}
}
