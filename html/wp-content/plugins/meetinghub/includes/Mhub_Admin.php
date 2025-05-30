<?php // phpcs:ignore
/**
 * Mhub_Admin Class
 *
 * This class defines the administration functionality for the Meeting Hub plugin.
 *
 * @package SOVLIX\MHUB
 */

namespace SOVLIX\MHUB;

use Firebase\JWT\JWT;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mhub_Admin' ) ) {
	/**
	 * Mhub_Admin Class
	 *
	 * Responsible for handling administration-related tasks for the Meeting Hub plugin.
	 *
	 * @since 1.0.0
	 */
	class Mhub_Admin {
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
		 * Mhub_Admin constructor.
		 *
		 * Initializes the admin class.
		 */
		public function __construct() {
			new Admin\Mhub_Menu();
			$this->zoom_api  = \SOVLIX\MHUB\Zoom\Mhub_Zoom_Api::instance();
			$this->webex_api = \SOVLIX\MHUB\API\Webex_Api::get_instance();

			$sdk_prepared        = $this->mhub_is_sdk_prepare();
			$auth_prepared       = $this->mhub_is_server_auth_prepare();
			$webex_auth_prepared = $this->mhub_webex_auth_prepare();

			$code   = filter_input( INPUT_GET, 'code' );
			$revoke = filter_input( INPUT_GET, 'revoke' );
			$page   = filter_input( INPUT_GET, 'page' );
			$state  = filter_input( INPUT_GET, 'state' );
			$tab    = filter_input( INPUT_GET, 'tab' );

			$google_meet = Googlemeet\Mhub_Meet::get_instance();

			if ( ! empty( $code ) ) {
				if ( ! empty( $state ) ) {
					// Handle Webex token fetch.
					$this->webex_api->fetch_access_token();
				} elseif ( 'google-meet' === $tab ) {
					// Handle Google Meet integration.
					if ( ! $google_meet->is_app_permitted() ) {
						$save_token = $google_meet->save_token( $code );

						// Redirect based on whether the app is permitted after saving the token.
						if ( false !== $google_meet->is_app_permitted() ) {
							wp_safe_redirect( admin_url( 'admin.php?page=meetinghub-settings#/google_meet' ) );
						} else {
							wp_safe_redirect( admin_url( 'admin.php?page=meetinghub-settings#/google_meet' ) );
						}
						exit;
					}
				} else {
					// Handle Zoom redirection.
					wp_safe_redirect( admin_url( 'admin.php?page=meetinghub-settings#/zoom' ) );
					exit;
				}
			}

			if ( ! empty( $revoke ) ) {
				$this->webex_api->revoke_access_token();
			}

			$current_user_id = get_current_user_id();

			// Check if the current page is meetinghub-settings.
			if ( 'meetinghub-settings' === $page ) {

				if ( ! $auth_prepared ) {
					add_action( 'admin_notices', array( $this, 'mhub_display_auth_notice' ) );
				}

				if ( ! $sdk_prepared ) {
					add_action( 'admin_notices', array( $this, 'mhub_display_sdk_notice' ) );
				}

				$webex_access_token = get_user_meta( $current_user_id, 'mhub_webex_access_token', true );

				if ( ! $webex_auth_prepared ) {
					add_action( 'admin_notices', array( $this, 'mhub_display_webex_auth_notice' ) );
				}
			}

			add_filter( 'plugin_action_links', array( $this, 'mhub_settings_link' ), 11, 2 );

			if ( ! $sdk_prepared ) {
				update_option( 'mhub_zoom_global_oauth_data', '' );
			}

			if ( ! $webex_auth_prepared ) {
				update_user_meta( $current_user_id, 'mhub_webex_access_token', '' );
			}

			$mhub_zoom_global_oauth = get_option( 'mhub_zoom_global_oauth_data' );

			if ( empty( $mhub_zoom_global_oauth ) ) {
				$this->mhub_store_users();
			}
		}

		/**
		 * Display admin notice to inform about the need to add Meeting SDK for Zoom functionalities.
		 */
		public function mhub_display_sdk_notice() {
			?>	
				<div class="notice notice-warning is-dismissible">
					<p>
						<?php esc_html_e( 'Please configure the Zoom Meeting SDK to enable additional Zoom Client functionalities and ensure "Join In Browser" functionality works correctly. ', 'meetinghub' ); ?>
						<a href="<?php echo esc_url( 'https://youtu.be/Q0Zt80PjvTE' ); ?>" target="_blank"><?php esc_html_e( 'Configure Zoom SDK Settings', 'meetinghub' ); ?></a>
					</p>
				</div>
			<?php
		}

		/**
		 * Display admin notice to inform about the need to add webex auth functionalities.
		 */
		public function mhub_display_webex_auth_notice() {
			?>
				
				<div class="notice notice-warning is-dismissible">
					<p>
						<?php esc_html_e( 'Please configure the Webex  Auth credentails to enable additional Webex Client. ', 'meetinghub' ); ?>
						<a href="<?php echo esc_url( 'https://youtu.be/vV3EwUNJusk' ); ?>" target="_blank"><?php esc_html_e( 'Configure Webex Settings', 'meetinghub' ); ?></a>
					</p>
				</div>
			<?php
		}

		/**
		 * Display admin notice to inform about the need to add Server to Server OAuth Credentials for Zoom functionalities.
		 */
		public function mhub_display_auth_notice() {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php esc_html_e( 'Please configure Server to Server OAuth Credentials to enable the creation of Zoom meetings and webinars, along with additional functionalities.', 'meetinghub' ); ?>
					<a href="<?php echo esc_url( 'https://youtu.be/ApSm4QJXLGc' ); ?>" target="_blank"><?php esc_html_e( 'Configure OAuth Credentials', 'meetinghub' ); ?></a>
				</p>
			</div>
			<?php
		}

		/**
		 * Check if the Zoom SDK is prepared by verifying the presence of API key and the absence of API secret.
		 *
		 * @return bool Whether the Zoom SDK is prepared or not.
		 */
		public function mhub_is_sdk_prepare() {
			$status = false;

			// Get Zoom settings from options.
			$zoom_settings = get_option( 'mhub_zoom_settings', true );

			if ( is_array( $zoom_settings ) && ! empty( $zoom_settings ) ) {
				// Extract API key and API secret from settings.
				$api_key    = $zoom_settings['sdk_client_id'];
				$api_secret = $zoom_settings['sdk_client_secret'];

				// Check if API key is not empty and API secret is empty.
				if ( ! empty( $api_key ) && ! empty( $api_secret ) ) {
					$status = true;
					update_option( 'mhub_is_sdk_prepare', 'yes' );
				} else {
					update_option( 'mhub_is_sdk_prepare', 'no' );
				}
			}
			return $status;
		}

		/**
		 * Check if the server authentication for Zoom is prepared by verifying the presence
		 * of OAuth account ID, OAuth client ID, and OAuth client secret in the Zoom settings.
		 *
		 * @return bool Whether the server authentication is prepared or not.
		 */
		public function mhub_is_server_auth_prepare() {
			$status = false;

			// Get Zoom settings from options.
			$zoom_settings = get_option( 'mhub_zoom_settings' );

			if ( is_array( $zoom_settings ) && ! empty( $zoom_settings ) ) {
				// Extract API key and API secret from settings.
				$account_id    = isset( $zoom_settings['oauth_account_id'] ) ? $zoom_settings['oauth_account_id'] : '';
				$client_id     = isset( $zoom_settings['oauth_client_id'] ) ? $zoom_settings['oauth_client_id'] : '';
				$client_secret = isset( $zoom_settings['oauth_client_secret'] ) ? $zoom_settings['oauth_client_secret'] : '';

				// Check if API key is not empty and API secret is empty.
				if ( ! empty( $account_id ) && ! empty( $client_id ) && ! empty( $client_secret ) ) {
					update_option( 'mhub_is_server_auth_prepare', 'yes' );
					$status = true;
				} else {
					update_option( 'mhub_is_server_auth_prepare', 'no' );
				}
			}

			return $status;
		}

		/**
		 * Prepare Webex authentication.
		 *
		 * This function retrieves Webex settings from the WordPress options, checks if the client ID and client secret are present,
		 * and updates the 'mhub_webex_auth_prepare' option accordingly. If both the client ID and client secret are present,
		 * it sets the option to 'yes' and returns true. Otherwise, it sets the option to 'no' and returns false.
		 *
		 * @return bool True if the client ID and client secret are both present, false otherwise.
		 */
		public function mhub_webex_auth_prepare() {
			$status = false;

			// Get webex settings from options.
			$webex_settings = get_option( 'mhub_webex_settings', true );

			if ( is_array( $webex_settings ) && ! empty( $webex_settings ) ) {
				// Extract API key and API secret from settings.
				$client_id     = $webex_settings['client_id'] ? $webex_settings['client_id'] : '';
				$client_secret = $webex_settings['client_secret'] ? $webex_settings['client_secret'] : '';

				// Check if API key is not empty and API secret is empty.
				if ( ! empty( $client_id ) && ! empty( $client_secret ) ) {
					update_option( 'mhub_webex_auth_prepare', 'yes' );
					$status = true;
				} else {
					update_option( 'mhub_webex_auth_prepare', 'no' );
				}
			}

			return $status;
		}

		/**
		 * Adds a "Settings" link to the plugin actions on the WordPress Plugins page.
		 *
		 * This function is a WordPress filter function that specifically targets the "Meeting Hub" plugin.
		 *
		 * @param array  $actions     The existing array of action links for the plugin.
		 * @param string $plugin_file The file path of the plugin being processed.
		 *
		 * @return array The modified array of action links, including the additional "Settings" link if the plugin is "Meeting Hub".
		 *
		 * @since 1.2.0
		 */
		public function mhub_settings_link( $actions, $plugin_file ) {
			if ( 'meetinghub/meetinghub.php' === $plugin_file ) {
				$settings_link = '<a href="' . admin_url( 'admin.php?page=meetinghub-settings' ) . '">' . esc_html__( 'Settings', 'meetinghub' ) . '</a>';
				if ( ! in_array( $settings_link, $actions, true ) ) {
					array_push( $actions, $settings_link );
				}
			}

			return $actions;
		}

		/**
		 * Retrieves user data from the Zoom API and stores it in the WordPress options table.
		 *
		 * This function retrieves user data from the Zoom API using the provided Zoom API instance,
		 * decodes the JSON response, and stores the user data in the WordPress options table.
		 * User data includes user ID, email, and display name.
		 * If the retrieved user data is not empty and in the expected format, it is stored in the options table.
		 *
		 * @since 1.0.0
		 */
		public function mhub_store_users() {
			$page      = 1;
			$user_data = $this->zoom_api->list_users( $page );
			$user_data = json_decode( $user_data, true );

			if ( ! empty( $user_data ) && isset( $user_data['users'] ) && is_array( $user_data['users'] ) && count( $user_data['users'] ) > 0 ) {
				$users_data = array();
				foreach ( $user_data['users'] as $user ) {
					$user_id      = $user['id'];
					$email        = $user['email'];
					$display_name = $user['display_name'];

					$users_data[] = array(
						'id'           => $user_id,
						'email'        => $email,
						'display_name' => $display_name,
					);
				}

				update_option( 'mhub_zoom_users', $users_data );
			} else {
				update_option( 'mhub_zoom_users', array() );
			}
		}
	}
}
