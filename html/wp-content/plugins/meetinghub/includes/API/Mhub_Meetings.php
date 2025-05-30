<?php //phpcs:ignore
/**
 * Routes for meetings
 *
 * @package SOVLIX\MHUB
 */

namespace SOVLIX\MHUB\API;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//phpcs:ignore
use WP_REST_Controller;
//phpcs:ignore
use WP_REST_Server;
//phpcs:ignore
use WP_Error;

if ( ! class_exists( 'Mhub_Meetings' ) ) {
	/**
	 * Mhub_Meetings Class
	 */
	class Mhub_Meetings extends \WP_REST_Controller {
		/**
		 * Zoom Api class.
		 *
		 * @var Mhub_Zoom_Api
		 */
		private $zoom_api;

		/**
		 * Webex Api class.
		 *
		 * @var Mhub_Webex_Api
		 */
		private $webex_api;

		/**
		 * Google meet Api class.
		 *
		 * @var Mhub_Events
		 */
		private $google_meet_api;

		/**
		 * Mhub_Meetings constructor
		 */
		public function __construct() {
			$this->namespace       = 'mhub/v1';
			$this->rest_base       = 'meetings';
			$this->zoom_api        = \SOVLIX\MHUB\Zoom\Mhub_Zoom_Api::instance();
			$this->webex_api       = \SOVLIX\MHUB\API\Webex_Api::get_instance();
			$this->google_meet_api = new \SOVLIX\MHUB\Googlemeet\Mhub_Events();
		}

		/**
		 * Register REST API routes
		 *
		 * @since 1.0.0
		 */
		public function register_routes() {

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_items' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_item' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),

				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)',
				array(
					'args' => array(
						'id' => array(
							'description' => __( 'Unique identifier for the object.', 'meetinghub' ),
							'type'        => 'integer',
						),
					),
				//phpcs:ignore
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				//phpcs:ignore
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				//phpcs:ignore
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/delete-multiple',
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_multiple_items' ),
					'permission_callback' => array( $this, 'check_permissions' ),
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
			return is_user_logged_in();
			//return current_user_can( 'edit_posts' );
		}

		/**
		 * Retrieves all meetings.
		 *
		 * @param mixed $request Post request.
		 *
		 * @return mixed
		 */
		public function get_items( $request ) {

			$data     = array();
			$meetings = mhub_meetings();

			foreach ( $meetings as $meeting ) {
				$response = $this->prepare_item_for_response( $meeting, $request );
				$data[]   = $this->prepare_response_for_collection( $response );
			}

			$response = rest_ensure_response( $data );
			return $response;
		}

		/**
		 * Get the address, if the ID is valid.
		 *
		 * @param int $id Supplied ID.
		 *
		 * @return Object|\WP_Error
		 */
		protected function get_meeting( $id ) {
			$meeting = mhub_meeting( $id );

			if ( ! $meeting ) {
				return new WP_Error(
					'rest_contact_invalid_id',
					__( 'Invalid contact ID.', 'meetinghub' ),
					array( 'status' => 404 )
				);
			}

			return $meeting;
		}

		/**
		 * Retrieves one item from the collection.
		 *
		 * @param \WP_REST_Request $request Post request.
		 *
		 * @return \WP_Error|\WP_REST_Response
		 */
		public function get_item( $request ) {
			$meeting  = mhub_meeting( $request['id'] );
			$response = $this->prepare_item_for_response( $meeting, $request );
			$response = rest_ensure_response( $response );

			return $response;
		}

		/**
		 * Creates one item from the collection.
		 *
		 * @param \WP_REST_Request $request Post request.
		 *
		 * @return \WP_Error|WP_REST_Response
		 */
		public function create_item( $request ) {
			$meeting = $this->prepare_item_for_database( $request );

			if ( is_wp_error( $meeting ) ) {
				return $meeting;
			}

			$meeting_settings              = maybe_unserialize( $meeting['settings'] );
			$serialized_with_zoom_password = null;

			if ( 'jitsi_meet' === $meeting_settings['selected_platform'] ) {
				// Add meeting.
				$meeting_id = mhub_insert_meeting( $meeting['title'], $meeting['settings'], $meeting['meeting_description'] );
			}

			if ( 'webex' === $meeting_settings['selected_platform'] ) {
				$duration = ! empty( $meeting_settings['duration_hours'] ) || ! empty( $meeting_settings['duration_minutes'] ) ? mhub_convert_to_minutes( $meeting_settings['duration_hours'], $meeting_settings['duration_minutes'] ) : 40;

				$webex_meeting_data = mhub_prepare_webex_meeting_data( $meeting['title'], $meeting_settings, $duration );

				$webex_response = $this->webex_api->create_meeting( $webex_meeting_data );

				if ( ! empty( $webex_response ) && ! isset( $webex_response['errors'] ) ) {
					$meeting_id = mhub_insert_meeting( $meeting['title'], $meeting['settings'], $meeting['meeting_description'] );
					update_post_meta( $meeting_id, 'mhub_webex_meeting_id', $webex_response['id'] );
					update_post_meta( $meeting_id, 'mhub_webex_join_link', $webex_response['webLink'] );
					update_post_meta( $meeting_id, 'mhub_webex_meeting_duration', $duration );
					update_post_meta( $meeting_id, 'mhub_webex_details', $webex_response );
				}

				return $webex_response;
			}

			// Check if the 'password' key exists and if it is empty.
			if ( 'zoom' === $meeting_settings['selected_platform'] ) {
				if ( isset( $meeting_settings['password'] ) && empty( $meeting_settings['password'] ) ) {
					$auto_generated_password       = wp_generate_password( 8, false );
					$meeting_settings['password']  = $auto_generated_password;
					$serialized_with_zoom_password = maybe_serialize( $meeting_settings );
				}
			}

			if ( 'zoom' === $meeting_settings['selected_platform'] ) {

				$duration = ! empty( $meeting_settings['duration_hours'] ) || ! empty( $meeting_settings['duration_minutes'] ) ? mhub_convert_to_minutes( $meeting_settings['duration_hours'], $meeting_settings['duration_minutes'] ) : 40;

				$user_id = isset( $meeting_settings['host_id'] ) ? $meeting_settings['host_id'] : '';

				if ( $user_id ) {
					if ( 2 === intval( $meeting_settings['meeting_type'] ) ) {
						if ( mhub_fs()->can_use_premium_code__premium_only() ) {
							$zoom_meeting_data = mhub_pro_prepare_zoom_meeting_data( $meeting['title'], $meeting_settings, $duration );
						} else {
							$zoom_meeting_data = mhub_prepare_zoom_meeting_data( $meeting['title'], $meeting_settings, $duration );
						}

						$zoom_response = json_decode( $this->zoom_api->create_zoom_meeting( $user_id, $zoom_meeting_data ), true );

						if ( ! empty( $zoom_response ) && ! isset( $zoom_response['code'] ) ) {
							// Add meeting.
							$meeting_meta = isset( $serialized_with_zoom_password ) ? $serialized_with_zoom_password : $meeting['settings'];
							$meeting_id   = mhub_insert_meeting( $meeting['title'], $meeting_meta, $meeting['meeting_description'] );

							update_post_meta( $meeting_id, 'mhub_zoom_meeting_duration', $duration );
							update_post_meta( $meeting_id, 'meetinghub_zoom_details', $zoom_response );
							update_post_meta( $meeting_id, 'meetinghub_zoom_join_url', $zoom_response['join_url'] );
							update_post_meta( $meeting_id, 'meetinghub_zoom_start_url', $zoom_response['start_url'] );
							update_post_meta( $meeting_id, 'meetinghub_zoom_meeting_id', $zoom_response['id'] );
							update_post_meta( $meeting_id, 'meetinghub_zoom_meeting_password', $meeting_settings['password'] );
						}

						return $zoom_response;
					}

					if ( 1 === intval( $meeting_settings['meeting_type'] ) ) {

						if ( mhub_fs()->can_use_premium_code__premium_only() ) {
							$zoom_webinar_data = mhub_pro_prepare_webinar( $meeting['title'], $meeting_settings, $duration );
						} else {
							$zoom_webinar_data = mhub_prepare_webinar( $meeting['title'], $meeting_settings, $duration );
						}

						$webinar_response = json_decode( $this->zoom_api->create_webinar( $user_id, $zoom_webinar_data ), true );

						if ( ! empty( $webinar_response ) && ! isset( $webinar_response['code'] ) ) {

							$meeting_meta = isset( $serialized_with_zoom_password ) ? $serialized_with_zoom_password : $meeting['settings'];
							$meeting_id   = mhub_insert_meeting( $meeting['title'], $meeting_meta, $meeting['meeting_description'] );

							update_post_meta( $meeting_id, 'meetinghub_zoom_details', $webinar_response );
							update_post_meta( $meeting_id, 'meetinghub_zoom_join_url', $webinar_response['join_url'] );
							update_post_meta( $meeting_id, 'meetinghub_zoom_start_url', $webinar_response['start_url'] );
							update_post_meta( $meeting_id, 'meetinghub_zoom_webinar_id', $webinar_response['id'] );
							update_post_meta( $meeting_id, 'meetinghub_zoom_meeting_password', $meeting_settings['password'] );
							update_post_meta( $meeting_id, 'mhub_zoom_meeting_duration', $duration );
						}

						return $webinar_response;
					}
				} else {
					$zoom_response = (object) array(
						'code'    => '404',
						'message' => 'No hosts found. Please check and verify your API keys are working correctly.',
					);

					return $zoom_response;

				}
			}

			if ( 'google_meet' === $meeting_settings['selected_platform'] ) {
				$duration = ! empty( $meeting_settings['duration_hours'] ) || ! empty( $meeting_settings['duration_minutes'] ) ? mhub_convert_to_minutes( $meeting_settings['duration_hours'], $meeting_settings['duration_minutes'] ) : 40;

				$google_meet_data = array();

				if ( mhub_fs()->can_use_premium_code__premium_only() && function_exists( 'mhub_pro_prepare_google_meet_data' ) ) {
					$google_meet_data = mhub_pro_prepare_google_meet_data( $meeting['title'], $meeting_settings, $duration );
				} else {
					$google_meet_data = mhub_prepare_google_meet_data( $meeting['title'], $meeting_settings, $duration );
				}

				$google_meet_response = $this->google_meet_api->create_meeting( $google_meet_data );

				//phpcs:ignore
				if ( ! empty( $google_meet_response ) && isset( $google_meet_response->id, $google_meet_response->hangoutLink ) ) {
					$meeting_id = mhub_insert_meeting( $meeting['title'], $meeting['settings'], $meeting['meeting_description'] );
					update_post_meta( $meeting_id, 'mhub_google_meeting_id', $google_meet_response->id );
					//phpcs:ignore
					update_post_meta( $meeting_id, 'mhub_google_join_link', $google_meet_response->hangoutLink );
					update_post_meta( $meeting_id, 'mhub_google_meeting_duration', $duration );
				}

				return $google_meet_response;
			}

			$response = array(
				'meeting_inserted' => true,
			);

			return rest_ensure_response( $response );
		}

		/**
		 * Updates one item from the collection.
		 *
		 * @param \WP_REST_Request $request Post request.
		 *
		 * @return \WP_Error|\WP_REST_Response
		 */
		public function update_item( $request ) {
			$meeting = $this->prepare_item_for_database( $request );

			$meeting_settings              = maybe_unserialize( $meeting['settings'] );
			$serialized_with_zoom_password = null;

			if ( 'jitsi_meet' === $meeting_settings['selected_platform'] ) {
				// Update.
				mhub_update_meeting( $meeting['title'], $meeting['settings'], $meeting['id'], $meeting['meeting_description'] );
			}

			// Webex meeting update.
			if ( 'webex' === $meeting_settings['selected_platform'] ) {
				$duration = ! empty( $meeting_settings['duration_hours'] ) || ! empty( $meeting_settings['duration_minutes'] ) ? mhub_convert_to_minutes( $meeting_settings['duration_hours'], $meeting_settings['duration_minutes'] ) : 40;

				$webex_meeting_id = get_post_meta( $meeting['id'], 'mhub_webex_meeting_id', true );

				$webex_meeting_data = mhub_prepare_webex_meeting_data( $meeting['title'], $meeting_settings, $duration );
				$webex_response     = $this->webex_api->update_meeting( $webex_meeting_id, $webex_meeting_data );

				if ( ! empty( $webex_response ) && ! isset( $webex_response['errors'] ) ) {
					$meeting_id = mhub_update_meeting( $meeting['title'], $meeting['settings'], $meeting['id'], $meeting['meeting_description'] );
					update_post_meta( $meeting['id'], 'mhub_webex_join_link', $webex_response['webLink'] );
					update_post_meta( $meeting['id'], 'mhub_webex_meeting_duration', $duration );
					update_post_meta( $meeting['id'], 'mhub_webex_details', $webex_response );
				}

				return $webex_response;
			}

			// Check if the 'password' key exists and if it is empty.
			if ( 'zoom' === $meeting_settings['selected_platform'] ) {
				if ( isset( $meeting_settings['password'] ) && empty( $meeting_settings['password'] ) ) {
					$auto_generated_password       = wp_generate_password( 8, false );
					$meeting_settings['password']  = $auto_generated_password;
					$serialized_with_zoom_password = maybe_serialize( $meeting_settings );
				}
			}

			if ( 'zoom' === $meeting_settings['selected_platform'] ) {
				$zoom_meeting_id = get_post_meta( $meeting['id'], 'meetinghub_zoom_meeting_id', true );
				$zoom_webinar_id = get_post_meta( $meeting['id'], 'meetinghub_zoom_webinar_id', true );

				$duration = ! empty( $meeting_settings['duration_hours'] ) || ! empty( $meeting_settings['duration_minutes'] ) ? mhub_convert_to_minutes( $meeting_settings['duration_hours'], $meeting_settings['duration_minutes'] ) : 40;

				$user_id = isset( $meeting_settings['host_id'] ) ? $meeting_settings['host_id'] : '';

				if ( $user_id ) {
					if ( 2 === intval( $meeting_settings['meeting_type'] ) ) {

						if ( mhub_fs()->can_use_premium_code__premium_only() ) {
							$zoom_meeting_data = mhub_pro_prepare_zoom_meeting_data( $meeting['title'], $meeting_settings, $duration );

						} else {
							$zoom_meeting_data = mhub_prepare_zoom_meeting_data( $meeting['title'], $meeting_settings, $duration );
						}

						$zoom_response = json_decode( $this->zoom_api->update_zoom_meeting( $zoom_meeting_id, $zoom_meeting_data ) );

						if ( empty( $zoom_response ) ) {
							$meeting_meta = isset( $serialized_with_zoom_password ) ? $serialized_with_zoom_password : $meeting['settings'];

							mhub_update_meeting( $meeting['title'], $meeting_meta, $meeting['id'], $meeting['meeting_description'] );
							$mhub_zoom_details = json_decode( $this->zoom_api->get_meeting_info( $zoom_meeting_id ) );

							if ( ! empty( $mhub_zoom_details ) ) {
								update_post_meta( $meeting['id'], 'meetinghub_zoom_details', $mhub_zoom_details );
							}

							update_post_meta( $meeting['id'], 'meetinghub_zoom_meeting_password', $meeting_settings['password'] );
							update_post_meta( $meeting['id'], 'mhub_zoom_meeting_duration', $duration );

							$response = array(
								'meeting_updated' => true,
							);

							return rest_ensure_response( $response );

						} else {
							return $zoom_response;
						}
					}

					if ( 1 === intval( $meeting_settings['meeting_type'] ) ) {
						if ( mhub_fs()->can_use_premium_code__premium_only() ) {
							$zoom_webinar_data = mhub_pro_prepare_webinar( $meeting['title'], $meeting_settings, $duration );
						} else {
							$zoom_webinar_data = mhub_prepare_webinar( $meeting['title'], $meeting_settings, $duration );
						}

						$webinar_response = json_decode( $this->zoom_api->update_webinar( $zoom_webinar_id, $zoom_webinar_data ) );

						if ( empty( $webinar_response ) ) {
							$meeting_meta = isset( $serialized_with_zoom_password ) ? $serialized_with_zoom_password : $meeting['settings'];

							mhub_update_meeting( $meeting['title'], $meeting_meta, $meeting['id'], $meeting['meeting_description'] );
							$mhub_zoom_details = json_decode( $this->zoom_api->get_webinar_info( $zoom_meeting_id ) );

							if ( ! empty( $mhub_zoom_details ) ) {
								update_post_meta( $meeting['id'], 'meetinghub_zoom_details', $mhub_zoom_details );
							}

							update_post_meta( $meeting['id'], 'meetinghub_zoom_meeting_password', $meeting_settings['password'] );
							update_post_meta( $meeting['id'], 'mhub_zoom_meeting_duration', $duration );

							$response = array(
								'meeting_updated' => true,
							);

							return rest_ensure_response( $response );

						} else {
							return $webinar_response;
						}
					}
				} else {
					$zoom_response = array(
						'code'    => '404',
						'message' => 'No hosts found. Please check and verify your API keys are working correctly.',
					);
					return $zoom_response;
				}
			}

			if ( 'google_meet' === $meeting_settings['selected_platform'] ) {
				$duration = ! empty( $meeting_settings['duration_hours'] ) || ! empty( $meeting_settings['duration_minutes'] ) ? mhub_convert_to_minutes( $meeting_settings['duration_hours'], $meeting_settings['duration_minutes'] ) : 40;

				$google_meeting_id = get_post_meta( $meeting['id'], 'mhub_google_meeting_id', true );

				$google_meet_response = $this->google_meet_api->update_meeting( $google_meeting_id, $meeting['title'], $meeting_settings );

				if ( ! empty( $google_meet_response ) && isset( $google_meet_response->id, $google_meet_response->hangoutLink ) ) {
					$meeting_id = mhub_update_meeting( $meeting['title'], $meeting['settings'], $meeting['id'], $meeting['meeting_description'] );

					update_post_meta( $meeting['id'], 'mhub_google_meeting_id', $google_meet_response->id );
					//phpcs:ignore
					update_post_meta( $meeting['id'], 'mhub_google_join_link', $google_meet_response->hangoutLink );
					update_post_meta( $meeting['id'], 'mhub_google_meeting_duration', $duration );
				}

				return $google_meet_response;
			}

			$response = array(
				'meeting_updated' => true,
			);

			return rest_ensure_response( $response );
		}

		/**
		 * Prepares one item for create or update operation.
		 *
		 * @param \WP_REST_Request $request Post request.
		 *
		 * @return \WP_Error|object
		 */
		protected function prepare_item_for_database( $request ) {
			$posted = json_decode( $request->get_body() );

			$prepared = array();
			$settings = array();

			if ( isset( $posted ) ) {
				foreach ( $posted as $key => $post ) {
					$settings[ $key ] = $post;
				}

				unset( $settings['title'] );
				unset( $settings['meeting_description'] );

				$settings['host'] = get_current_user_id();

				if ( isset( $posted->id ) ) {
					$prepared['id'] = $posted->id;
				}

				$prepared['title']               = sanitize_text_field( $posted->title );
				$prepared['settings']            = maybe_serialize( $settings );
				$prepared['meeting_description'] = $posted->meeting_description;
			}

			return $prepared;
		}

		/**
		 * Prepares the item for the REST response.
		 *
		 * @param mixed            $item    WordPress representation of the item.
		 * @param \WP_REST_Request $request Request object.
		 *
		 * @return \WP_Error|WP_REST_Response
		 */
		public function prepare_item_for_response( $item, $request ) {
			$data   = array();
			$fields = $this->get_fields_for_response( $request );

			if ( isset( $item['id'] ) && in_array( 'id', $fields, true ) ) {
				$data['id'] = (int) $item['id'];
			}

			if ( isset( $item['title'] ) && in_array( 'title', $fields, true ) ) {
				$data['title'] = $item['title'];
			}

			if ( isset( $item['settings'] ) && in_array( 'settings', $fields, true ) ) {
				$data['settings'] = $item['settings'];
			}

			if ( isset( $item['meeting_description'] ) && in_array( 'meeting_description', $fields, true ) ) {
				$data['meeting_description'] = $item['meeting_description'];
			}

			if ( isset( $item['date'] ) && in_array( 'date', $fields, true ) ) {
				$data['date'] = mysql_to_rfc3339( $item['date'] );
			}

			$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
			$data    = $this->filter_response_by_context( $data, $context );

			$response = rest_ensure_response( $data );
			$response->add_links( $this->prepare_links( $item ) );

			return $response;
		}


		/**
		 * Deletes one item from the collection.
		 *
		 * @param \WP_REST_Request $request Request object.
		 *
		 * @return \WP_Error|WP_REST_Response
		 */
		public function delete_item( $request ) {
			$serialized_settings = get_post_meta( $request['id'], 'mhub__meeting_settings', true );
			$settings            = maybe_unserialize( $serialized_settings );

			if ( 'zoom' === $settings['selected_platform'] && '2' === $settings['meeting_type'] ) {
				$zoom_meeting_id      = get_post_meta( $request['id'], 'meetinghub_zoom_meeting_id', true );
				$zoom_meeting_deleted = $this->zoom_api->delete_meeting( $zoom_meeting_id );
			}

			if ( 'zoom' === $settings['selected_platform'] && '1' === $settings['meeting_type'] ) {
				$zoom_webinar_id      = get_post_meta( $request['id'], 'meetinghub_zoom_webinar_id', true );
				$zoom_webinar_deleted = $this->zoom_api->delete_webinar( $zoom_webinar_id );
			}

			if ( 'webex' === $settings['selected_platform'] ) {
				$webex_meeting_id      = get_post_meta( $request['id'], 'mhub_webex_meeting_id', true );
				$webex_meeting_deleted = $this->webex_api->delete_meeting( $webex_meeting_id );
			}

			if ( 'google_meet' === $settings['selected_platform'] ) {
				$google_meeting_id      = get_post_meta( $request['id'], 'mhub_google_meeting_id', true );
				$google_meeting_deleted = $this->google_meet_api->delete_meeting( $google_meeting_id );
			}

			$deleted = mhub_delete_meeting( $request['id'] );

			if ( ! $deleted ) {
				return new WP_Error(
					'rest_not_deleted',
					__( 'Sorry, the address could not be deleted.', 'meetinghub' ),
					array( 'status' => 400 )
				);
			}

			$data = array(
				'deleted' => true,
			);

			$response = rest_ensure_response( $data );

			return $data;
		}

		/**
		 * Deletes multiple items from the collection.
		 *
		 * @param \WP_REST_Request $request Request object.
		 *
		 * @return \WP_Error|WP_REST_Response
		 */
		public function delete_multiple_items( $request ) {
			$ids = $request->get_param( 'ids' );

			if ( empty( $ids ) || ! is_array( $ids ) ) {
				return new \WP_Error(
					'rest_invalid_param',
					__( 'Invalid or missing parameter: ids.', 'meetinghub' ),
					array( 'status' => 400 )
				);
			}

			$response_data = array();

			foreach ( $ids as $id ) {

				$serialized_settings = get_post_meta( $id, 'mhub__meeting_settings', true );
				$settings            = maybe_unserialize( $serialized_settings );

				if ( 'zoom' === $settings['selected_platform'] && '2' === $settings['meeting_type'] ) {
					$zoom_meeting_id      = get_post_meta( $id, 'meetinghub_zoom_meeting_id', true );
					$zoom_meeting_deleted = $this->zoom_api->delete_meeting( $zoom_meeting_id );
				}

				if ( 'zoom' === $settings['selected_platform'] && '1' === $settings['meeting_type'] ) {
					$zoom_webinar_id      = get_post_meta( $id, 'meetinghub_zoom_webinar_id', true );
					$zoom_webinar_deleted = $this->zoom_api->delete_webinar( $zoom_webinar_id );
				}

				if ( 'webex' === $settings['selected_platform'] ) {
					$webex_meeting_id      = get_post_meta( $id, 'mhub_webex_meeting_id', true );
					$webex_meeting_deleted = $this->webex_api->delete_meeting( $webex_meeting_id );
				}

				if ( 'google_meet' === $settings['selected_platform'] ) {
					$google_meeting_id      = get_post_meta( $id, 'mhub_google_meeting_id', true );
					$google_meeting_deleted = $this->google_meet_api->delete_meeting( $google_meeting_id );
				}

				$deleted = mhub_delete_meeting( $id );

				if ( is_wp_error( $deleted ) ) {
					// Handle error if needed.
					$response_data[] = array(
						'id'     => $id,
						'status' => 'error',
					);
				} else {
					$response_data[] = array(
						'id'     => $id,
						'status' => 'deleted',
					);
				}
			}

			return rest_ensure_response( $response_data );
		}


		/**
		 * Prepares links for the request.
		 *
		 * @param mixed $item Get Post.
		 *
		 * @return array Links for the given post.
		 */
		protected function prepare_links( $item ) {
			$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

			$links = array();

			if ( isset( $item['id'] ) ) {
				$links['self'] = array(
					'href' => rest_url( trailingslashit( $base ) . $item['id'] ),
				);
			}

			$links['collection'] = array(
				'href' => rest_url( $base ),
			);

			return $links;
		}


		/**
		 * Retrieves the contact schema, conforming to JSON Schema.
		 *
		 * @return array
		 */
		public function get_item_schema() {
			if ( $this->schema ) {
				return $this->add_additional_fields_schema( $this->schema );
			}

			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'contact',
				'type'       => 'object',
				'properties' => array(
					'id'                  => array(
						'description' => __( 'Unique identifier for the object.', 'meetinghub' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'title'               => array(
						'description' => __( 'Name of the meeting.', 'meetinghub' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
						'arg_options' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'settings'            => array(
						'description' => __( 'Meeting setting', 'meetinghub' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
					'meeting_description' => array(
						'description' => __( 'Meeting setting', 'meetinghub' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
					'date'                => array(
						'description' => __( "The date the object was published, in the site's timezone.", 'meetinghub' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
				),
			);

			$this->schema = $schema;

			return $this->add_additional_fields_schema( $this->schema );
		}
	}
}
