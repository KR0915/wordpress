<?php
/**
 * File: Mhub_Meet.php
 *
 * Manages Google Meet integration for the MeetingHub plugin, including handling Google API credentials,
 * tokens, and Calendar API interactions. Provides methods for user authentication and meeting management.
 *
 * @package SOVLIX\MHUB
 */

namespace SOVLIX\MHUB\Googlemeet;

if ( ! class_exists( 'Mhub_Meet' ) ) {
	/**
	 * Class Mhub_Meet
	 *
	 * This class is responsible for integrating Google Meet with the MeetingHub plugin. It handles the
	 * initialization of Google Client, managing API credentials and tokens, and facilitating OAuth flows.
	 */
	class Mhub_Meet {
		/**
		 * Holds the single instance of the class.
		 *
		 * This property ensures that the class follows the singleton pattern,
		 * allowing only one instance to be created and reused throughout the application.
		 *
		 * @var null|self The single instance of the class, or null if not yet instantiated.
		 */
		private static $instance = null;
		/**
		 * Path to the credentials file.
		 *
		 * @var string
		 */
		private $credential_path;

		/**
		 * Path to the token file.
		 *
		 * @var string
		 */
		private $token_path;

		/**
		 * Upload directory for credentials.
		 *
		 * @var string
		 */
		public $upload_dir;

		/**
		 * Username for authentication.
		 *
		 * @var string
		 */
		public $username;

		/**
		 * Google API client instance.
		 *
		 * @var \Google_Client
		 */
		public $client;

		/**
		 * Google API service instance.
		 *
		 * @var \Google_Service_Calendar
		 */
		public $service;

		/**
		 * Application name for the Google API client.
		 *
		 * @var string
		 */
		private $app_name = 'MeetingHub';

		/**
		 * Google API callback URL for authentication.
		 *
		 * @var string
		 */
		private $google_callback_url;

		/**
		 * List of required Google API scopes.
		 *
		 * @var array
		 */
		private $required_scopes = array(
			\Google_Service_Calendar::CALENDAR,
			\Google_Service_Calendar::CALENDAR_EVENTS,
		);

		/**
		 * Current calendar type
		 *
		 * @var string
		 */
		public $current_calendar;

		/**
		 * Constructor initializes paths and hooks.
		 */
		public function __construct() {
			$owner_id               = null;
			$current_calendar       = 'primary';
			$this->current_calendar = $current_calendar;

			$this->username            = md5( wp_get_current_user()->user_login );
			$this->upload_dir          = trailingslashit( wp_upload_dir()['basedir'] ) . 'mhub-json/';
			$this->credential_path     = $this->upload_dir . 'credential.json';
			$this->token_path          = $this->upload_dir . 'token.json';
			$this->google_callback_url = admin_url() . 'admin.php?page=meetinghub-settings&tab=google-meet';

			$this->initialize_directory();
			$this->initialize_google_client();

			// Register AJAX for uploading credentials.
			add_action( 'wp_ajax_mhub_upload_google_meet_credential', array( $this, 'upload_credentials' ) );
		}

		/**
		 * Returns the single instance of the class.
		 */
		public static function get_instance(): self {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Initializes the upload directory.
		 */
		private function initialize_directory() {
			if ( ! file_exists( $this->upload_dir ) ) {
				wp_mkdir_p( $this->upload_dir );
			}

			if ( ! file_exists( $this->upload_dir . 'index.php' ) ) {
				file_put_contents( $this->upload_dir . 'index.php', '<?php //silence is golden' );
			}
		}

		/**
		 * Initializes the Google client and service if credentials are available.
		 */
		private function initialize_google_client() {
			if ( $this->is_credential_loaded() ) {
				try {
					$this->validate_json_service_account_file( $this->credential_path );
					$this->client = new \Google_Client();
					$this->client->setApplicationName( $this->app_name );
					$this->client->setAuthConfig( $this->credential_path );
					$this->client->setRedirectUri( $this->google_callback_url );
					$this->client->addScope( $this->required_scopes );
					$this->client->setAccessType( 'offline' );
					$this->client->setApprovalPrompt( 'force' );
					if ( $this->assign_token_to_client() ) {
						$this->service = new \Google_Service_Calendar( $this->client );
					}
				} catch ( \Throwable $th ) {
					$this->handle_initialization_error( $th );
				}
			}
		}

		/**
		 * Handles initialization errors and clears invalid credentials if necessary.
		 *
		 * Removes stored credentials if they exist and displays an admin notice with the error
		 * message when in the admin area.
		 *
		 * @param \Throwable $th The exception or error that caused the initialization failure.
		 *
		 * @return void
		 */
		private function handle_initialization_error( $th ) {
			if ( file_exists( $this->credential_path ) ) {
				wp_delete_file( $this->credential_path );
			}
			if ( is_admin() ) {
				add_action(
					'admin_notices',
					function () use ( $th ) {
						printf(
							'<div class="%1$s"><p>%2$s</p></div>',
							esc_attr( 'notice notice-error is-dismissible' ),
							esc_html( $th->getMessage() )
						);
					}
				);
			}
		}

		/**
		 * Validates the JSON service account configuration file.
		 *
		 * @param string $file_path Path to the JSON configuration file.
		 *
		 * @return bool True if the file is valid.
		 * @throws \Exception If the file does not exist, contains invalid JSON, or is missing required keys.
		 */
		public function validate_json_service_account_file( $file_path ) {
			if ( ! file_exists( $file_path ) ) {
				throw new \Exception(
					esc_html__( 'File does not exist', 'meetinghub' )
				);
			}

			$data = file_get_contents( $file_path );
			$json = json_decode( $data, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new \Exception(
					esc_html__( 'Invalid JSON file', 'meetinghub' )
				);
			}

			$required_keys = array(
				'client_id',
				'client_secret',
				'project_id',
				'auth_uri',
				'token_uri',
			);

			foreach ( $required_keys as $key ) {
				if ( ! array_key_exists( $key, isset( $json['web'] ) ? $json['web'] : array() ) ) {
					throw new \Exception(
						sprintf(
							'%1$s %2$s',
							esc_html( $key ),
							esc_html__( 'does not exist in your JSON file', 'meetinghub' )
						)
					);
				}
			}

			return true;
		}

		/**
		 * Return consent screen url
		 *
		 * @since v2.1.0
		 *
		 * @return string  consent screen URL
		 */
		public function get_consent_screen_url() {
			return $this->client->createAuthUrl();
		}

		/**
		 * AJAX handler for uploading credentials.
		 *
		 * @return void
		 */
		public function upload_credentials() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					esc_html__( 'You are not authorized to perform this operation', 'meetinghub' )
				);
				return;
			}

			// Verify the nonce using the default `_ajax_nonce` key.
			if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ), 'mhub_admin_nonce' ) ) {
				wp_send_json_error(
					esc_html__( 'Invalid nonce. Operation not allowed.', 'meetinghub' )
				);
				return;
			}

			$file = isset( $_FILES['credential'] ) ? $_FILES['credential'] : null;

			if ( ! $file || UPLOAD_ERR_OK !== $file['error'] ) {
				wp_send_json_error(
					esc_html__( 'File upload error.', 'meetinghub' )
				);
				return;
			}

			if ( ! move_uploaded_file( $file['tmp_name'], $this->credential_path ) ) {
				wp_send_json_error(
					esc_html__( 'Credential upload failed, please try again!', 'meetinghub' )
				);
				return;
			}

			try {
				$this->validate_json_service_account_file( $this->credential_path );
				wp_send_json_success(
					esc_html__( 'Credential uploaded successfully!', 'meetinghub' )
				);
			} catch ( \Throwable $th ) {
				wp_delete_file( $this->credential_path );
				wp_send_json_error(
					esc_html( $th->getMessage() )
				);
			}
		}


		/**
		 * Checks if credentials are loaded.
		 */
		public function is_credential_loaded() {
			return file_exists( $this->credential_path );
		}

		/**
		 * Assigns the existing token, or tries to refresh if expired.
		 */
		public function assign_token_to_client() {
			if ( ! file_exists( $this->token_path ) ) {
				return false;
			}

			$access_token = json_decode( file_get_contents( $this->token_path ), true );
			if ( ! $access_token ) {
				// If access_token is null or invalid, return false.
				return false;
			}

			$this->client->setAccessToken( $access_token );

			if ( $this->client->isAccessTokenExpired() ) {
				$refresh_token = $this->client->getRefreshToken();
				if ( ! $refresh_token ) {
					return false;
				}

				try {
					$new_token = $this->client->fetchAccessTokenWithRefreshToken( $refresh_token );
					return $this->save_token( null, $new_token );
				} catch ( \Exception $e ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Saves the Google API token.
		 *
		 * @param string|null $code  The authorization code.
		 * @param array|null  $token The token data. If null, it will fetch using the code.
		 *
		 * @return bool True if the token is saved successfully, false otherwise.
		 * @throws \InvalidArgumentException If the authorization code is missing.
		 * @throws \RuntimeException If saving the token fails.
		 */
		public function save_token( $code = null, $token = null ) {
			// Optional: Validate if the user has the required permissions.
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			try {
				// Fetch the token using the code if not provided.
				if ( ! $token ) {
					if ( ! $code ) {
						throw new \InvalidArgumentException( 'Authorization code is required.' );
					}
					$token = $this->client->fetchAccessTokenWithAuthCode( $code );
					$this->client->setAccessToken( $token );
					$token = $this->client->getAccessToken();
				}

				// Encode token to JSON and save to the file.
				$saved = file_put_contents( $this->token_path, wp_json_encode( $token ) );

				if ( false === $saved ) {
					throw new \RuntimeException( 'Failed to save token to file.' );
				}

				return true;
			} catch ( \Throwable $th ) {
				// Log the error for debugging (optional).
				return false;
			}
		}


		/**
		 * Checks if the application is permitted via the consent screen.
		 */
		public function is_app_permitted() {
			if ( is_null( $this->credential_path ) || is_null( $this->token_path ) ) {
				return false;
			}
			return $this->assign_token_to_client() === false ? false : true;
		}
	}
}
