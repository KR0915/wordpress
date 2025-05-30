<?php //phpcs:ignore
/**
 * Main Plugin File
 *
 * The primary entry point for the Meeting Hub plugin.
 *
 * @package SOVLIX\MHUB
 */

namespace SOVLIX\MHUB;

use Firebase\JWT\JWT;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if the main class already exists.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'Meeting_Hub' ) ) {
	/**
	 * Meeting Hub Plugin Main Class
	 *
	 * Sets up and initializes the Meeting Hub plugin.
	 *
	 * @since 1.0.0
	 */
	class Meeting_Hub {
		/**
		 * Class constructor.
		 *
		 * Hooks into the 'plugins_loaded' action to initiate the plugin.
		 */
		private function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
			add_action( 'init', array( $this, 'mhub_localization_setup' ) );
		}

		/**
		 * Initialize a singleton instance of the plugin.
		 *
		 * @return Meeting_Hub An instance of the Meeting_Hub class.
		 * @since  1.0.0
		 */
		public static function init() {
			static $instance = false;

			if ( ! $instance ) {
				$instance = new self();
			}

			return $instance;
		}

		/**
		 * Initialize the Meeting Hub plugin.
		 *
		 * Initiates the necessary components for the plugin, such as Assets,
		 * and either the Admin or Frontend components based on the current context.
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function init_plugin() {

			// Initialize Ajax component for asynchronous actions.
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				new Mhub_Ajax();
			}

			new Mhub_Assets();

			if ( is_admin() ) {
				new Mhub_Admin();
			} else {
				new Mhub_Frontend();
			}

			new Mhub_Post_Types();
			new Mhub_API();
			new Mhub_Shortcodes();
			new Mhub_Gutenberg();
			$mhub_meet = Googlemeet\Mhub_Meet::get_instance();
			$this->mhub_jitsi_jwt();

			// Get the list of active plugins.
			$active_plugins = get_option( 'active_plugins' );

			// Check if the Elementor plugin is active.
			if ( in_array( 'elementor/elementor.php', $active_plugins, true ) ) {
				new Elementor\Mhub_Elementor_Integrator();
			}
		}

		/**
		 * Sets up localization for the Meeting Hub plugin.
		 *
		 * This function loads the plugin text domain for translation purposes.
		 * Text domain: meetinghub
		 *
		 * @since 1.12.0
		 */
		public function mhub_localization_setup() {
			load_plugin_textdomain( 'meetinghub', false, MHUB_PATH . '/languages/' );
		}

		/**
		 * Generate a JSON Web Token (JWT) for Jitsi integration.
		 *
		 * This function generates a JWT token for use in integrating with Jitsi, a video conferencing platform.
		 * The JWT token includes user information, such as name, email, and avatar, along with permissions and features settings.
		 * If the user is logged in, their information is included in the JWT payload. Otherwise, default values are used.
		 *
		 * @return string The generated JWT token.
		 */
		public function mhub_jitsi_jwt() {
			// Retrieve Jitsi settings from options.
			$jitsi_settings_serialized = get_option( 'mhub_jitsi_settings' );
			$jitsi_settings            = maybe_unserialize( $jitsi_settings_serialized );

			// Check if Jitsi settings are empty or not configured properly.
			if ( empty( $jitsi_settings ) ) {
				delete_transient( 'mhub_jitsi_jwt_token' );
				return '';
			}

			if ( 'jitsi_jass_premium' !== $jitsi_settings['domain_type'] ) {
				delete_transient( 'mhub_jitsi_jwt_token' );
				return '';
			}

			if ( empty( $jitsi_settings['app_id'] ) || empty( $jitsi_settings['api_key'] ) || empty( $jitsi_settings['private_key'] ) ) {
				delete_transient( 'mhub_jitsi_jwt_token' );
				return '';
			}

			// Check if a token is already stored.
			$stored_token = get_transient( 'mhub_jitsi_jwt_token' );

			// if ( ! empty( $stored_token ) ) {
			// 	return $stored_token;
			// }

			// Initialize user variables.
			$user_avatar_url   = '';
			$user_name         = '';
			$user_email        = '';
			$user_id           = '';
			$user_is_moderator = false;

			// Get user information if user is logged in.
			if ( is_user_logged_in() ) {
				$current_user      = wp_get_current_user();
				$user_id           = $current_user->ID;
				$user_avatar_url   = get_avatar_url( $current_user->ID );
				$user_name         = $current_user->display_name;
				$user_email        = $current_user->user_email;
				$user_is_moderator = current_user_can( 'edit_posts' );
			}

			// Extract Jitsi settings.
			$api_key                  = $jitsi_settings['api_key'];
			$api_id                   = $jitsi_settings['app_id'];
			$private_key              = trim( $jitsi_settings['private_key'] );
			$livestreaming_is_enabled = $jitsi_settings['enable_livestreaming'];
			$recording_is_enabled     = $jitsi_settings['enable_recording'];
			$outbound_is_enabled      = $jitsi_settings['enable_outbound'];
			$transcription_is_enabled = $jitsi_settings['enable_transcription'];
			$exp_delay_sec            = 7200;
			$nbf_delay_sec            = 0;

			/**
			 * Generate a Jitsi JWT token.
			 *
			 * @param string $api_key The API key for Jitsi integration.
			 * @param string $app_id The App ID for Jitsi integration.
			 * @param string $user_email The email address of the user.
			 * @param string $user_name The name of the user.
			 * @param bool   $user_is_moderator Whether the user is a moderator or not.
			 * @param string $user_avatar_url The URL of the user's avatar.
			 * @param string $user_id The unique ID of the user.
			 * @param bool   $live_streaming_enabled Whether livestreaming is enabled for the user.
			 * @param bool   $recording_enabled Whether recording is enabled for the user.
			 * @param bool   $outbound_enabled Whether outbound calls are enabled for the user.
			 * @param bool   $transcription_enabled Whether transcription is enabled for the user.
			 * @param int    $exp_delay The expiration delay in seconds for the JWT token.
			 * @param int    $nbf_delay The not before delay in seconds for the JWT token.
			 * @param string $private_key The private key used for encoding the JWT token.
			 *
			 * @return string|null The generated JWT token or null if generation fails.
			 */
			function create_jaas_token(
				$api_key,
				$app_id,
				$user_email,
				$user_name,
				$user_is_moderator,
				$user_avatar_url,
				$user_id,
				$live_streaming_enabled,
				$recording_enabled,
				$outbound_enabled,
				$transcription_enabled,
				$exp_delay,
				$nbf_delay,
				$private_key
			) {
				try {
					// Validate private key.
					$private_key_resource = openssl_pkey_get_private( $private_key );

					if ( ! $private_key_resource ) {
						return null; // Return null if the private key is invalid.
					}

					$payload = array(
						'iss'     => 'chat',
						'aud'     => 'jitsi',
						'exp'     => time() + $exp_delay,
						'nbf'     => time() - $nbf_delay,
						'room'    => '*',
						'sub'     => $app_id,
						'context' => array(
							'user'     => current_user_can( 'edit_posts' ) ? array(
								'moderator' => 'true',
								'email'     => $user_email,
								'name'      => $user_name,
								'avatar'    => $user_avatar_url,
								'id'        => $user_id,
							) : array(
								'moderator' => 'false',
							),
							'features' => array(
								'recording'     => $recording_enabled ? 'true' : 'false',
								'livestreaming' => $live_streaming_enabled ? 'true' : 'false',
								'transcription' => $transcription_enabled ? 'true' : 'false',
								'outbound-call' => $outbound_enabled ? 'true' : 'false',
							),
						),
					);

					$payload_json = wp_json_encode( $payload );
					// Attempt to sign the payload.
					$success = openssl_sign( $payload_json, $signature, $private_key_resource, OPENSSL_ALGO_SHA256 );

					// Conditionally free the private key resource if PHP version is less than 8.0.
					if ( version_compare( PHP_VERSION, '8.0.0', '<' ) ) {
						openssl_free_key( $private_key_resource );
					}

					// Check if signing was successful.
					if ( ! $success ) {
						// Return null if signing failed.
						return null;
					}

					return JWT::encode( $payload, $private_key, 'RS256', $api_key );
				} catch ( Exception $e ) {
					// Log the error or handle it in an appropriate manner.
					// Returning null for now.
					return null;
				}
			}

			// Generate the JWT token.
			$token = create_jaas_token(
				$api_key,
				$api_id,
				$user_email,
				$user_name,
				$user_is_moderator,
				$user_avatar_url,
				$user_id,
				$livestreaming_is_enabled,
				$recording_is_enabled,
				$outbound_is_enabled,
				$transcription_is_enabled,
				$exp_delay_sec,
				$nbf_delay_sec,
				$private_key
			);

			// Check if token generation failed.
			if ( null === $token ) {
				// Token generation failed, handle the error appropriately.
				// For now, return an empty string.
				return '';
			}

			// Store the token in the options table.
			set_transient( 'mhub_jitsi_jwt_token', $token, 5 );

			// Return the JWT token.
			return $token;
		}
	}

	// Kick-off the Meeting Hub plugin.
	Meeting_Hub::init();
}
