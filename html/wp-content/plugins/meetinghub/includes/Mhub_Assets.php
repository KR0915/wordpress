<?php // phpcs:ignore
/**
 * Mhub_Assets Class
 *
 * This class handles the loading of assets for the Meeting Hub plugin.
 *
 * @package SOVLIX\MHUB
 */

namespace SOVLIX\MHUB;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mhub_Assets' ) ) {
	/**
	 * Mhub_Assets Class
	 *
	 * Manages the loading of assets, such as styles and scripts, for the Meeting Hub plugin.
	 *
	 * @since 1.0.0
	 */
	class Mhub_Assets {

		/**
		 * Mhub_Assets constructor.
		 *
		 * Initializes the Mhub_Assets class and sets up actions for enqueueing assets.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'mhub_admin_enqueue' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'mhub_frontend_enqueue' ), 9999 );
		}

		/**
		 * Meeting Hub enqueue for admin.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function mhub_admin_enqueue() {
			$create_meeting_url        = admin_url( 'admin.php?page=meetinghub#/meeting/create' );
			$oauth_data                = get_option( 'mhub_zoom_global_oauth_data' );
			$mhub_google_account       = get_option( 'mhub_google_meet_account' );
			$server_auth_prepare       = mhub_is_server_auth_prepare();
			$mhub_jitsi_settings       = get_option( 'mhub_jitsi_settings' );
			$mhub_webex_settings       = get_option( 'mhub_webex_settings' );
			$mhub_zoom_settings        = get_option( 'mhub_zoom_settings' );
			$mhub_google_meet_settings = get_option( 'mhub_google_meet_settings' );
			$current_user_id           = get_current_user_id();
			$webex_access_token        = get_user_meta( $current_user_id, 'mhub_webex_access_token', true );
			$jwt_token                 = get_transient( 'mhub_jitsi_jwt_token' );

			$jitsi_active_status = '';

			if ( ! empty( $mhub_jitsi_settings ) ) {
				$domain_type = $mhub_jitsi_settings['domain_type'];

				if ( 'jitsi_random_public' === $domain_type ) {
					$jitsi_active_status = 'connected';
				} elseif ( 'jitsi_self_hosted' === $domain_type ) {
					$custom_domain = $mhub_jitsi_settings['custom_domain'];
					if ( ! empty( $custom_domain ) ) {
						$jitsi_active_status = 'connected';
					}
				} elseif ( 'jitsi_jass_premium' === $domain_type && ! empty( $jwt_token ) ) {
					$jitsi_active_status = 'connected';
				}
			}

			wp_enqueue_media();
			wp_enqueue_style( 'dashicons' );
			$screen = get_current_screen();

			wp_enqueue_style( 'mhub-admin-style', MHUB_ASSETS . '/css/admin.css', array(), MHUB_VERSION, 'all' );
			wp_enqueue_style( 'mhub-admin-fonts', MHUB_ASSETS . '/css/fonts.css', array(), MHUB_VERSION, 'all' );

			if ( $screen && ( strpos( $screen->id, 'meetinghub' ) !== false ) ) {

				wp_enqueue_script( 'mhub-admin-build', MHUB_ASSETS . '/dist/main.build.js', array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n' ), MHUB_VERSION, true );
				wp_enqueue_script( 'mhub-admin-js', MHUB_ASSETS . '/js/admin.js', array( 'jquery' ), MHUB_VERSION, true );

				// Set script translations.
				wp_set_script_translations( 'mhub-admin-build', 'meetinghub', plugin_dir_path( MHUB_FILE ) . 'languages/' );

				// Localize the script and pass the create meeting URL.
				wp_localize_script(
					'mhub-admin-build',
					'mhubMeetingsData',
					array(
						'createMeetingUrl'          => $create_meeting_url,
						'oauthData'                 => ! empty( $oauth_data ) && $server_auth_prepare ? 'connected' : '',
						'webex_auth'                => ! empty( $webex_access_token ) ? 'connected' : '',
						'google_account'            => $mhub_google_account,
						'jitsi_active_status'       => $jitsi_active_status,
						'mhub_jitsi_settings'       => $mhub_jitsi_settings,
						'mhub_webex_settings'       => $mhub_webex_settings,
						'mhub_zoom_settings'        => $mhub_zoom_settings,
						'mhub_google_meet_settings' => $mhub_google_meet_settings,
						'mhub_timezone'             => mhub_timezone_offset(),
						'mhub_password'             => wp_generate_password( 8, false ),
						'zoom_users'                => get_option( 'mhub_zoom_users' ),
						'installed'                 => mhub_pro_plugin_exists(),
						'active'                    => mhub_fs()->can_use_premium_code__premium_only(),
						'license'                   => mhub_fs()->can_use_premium_code__premium_only(),
						'pricing_url'               => 'https://sovlix.com/meetinghub-pricing/',
						'google_meet_redirect_url'  => admin_url( 'admin.php?page=meetinghub-settings&tab=google-meet' ),
						'assets_path'               => MHUB_ASSETS,
						'checkout_url'              => mhub_fs()->get_upgrade_url(),
						'is_paying'                 => mhub_fs()->is_paying(),
						'ajax_url'                  => admin_url( 'admin-ajax.php' ),
						'nonce'                     => wp_create_nonce( 'mhub_admin_nonce' ),
						'siteurl'                   => get_site_url(),
						'mce_btn_name'              => __( 'Meeting', 'meetinghub' ),
						'hide_floating_create_btn'  => false,
						'hide_floating_update_btn'  => false,
						'is_admin'                  => is_admin(),
						'strings'                   => Mhub_Translate::strings(),
					)
				);

				wp_enqueue_style( 'mhub-admin-build-style', MHUB_ASSETS . '/dist/main.build.css', array(), MHUB_VERSION, 'all' );
			}
		}

		/**
		 * Meeting Hub enqueue for frontend.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function mhub_frontend_enqueue() {

			$oauth_data                = get_option( 'mhub_zoom_global_oauth_data' );
			$server_auth_prepare       = mhub_is_server_auth_prepare();
			$mhub_jitsi_settings       = get_option( 'mhub_jitsi_settings' );
			$mhub_webex_settings       = get_option( 'mhub_webex_settings' );
			$mhub_zoom_settings        = get_option( 'mhub_zoom_settings' );
			$mhub_google_meet_settings = get_option( 'mhub_google_meet_settings' );
			$current_user_id           = get_current_user_id();
			$webex_access_token        = get_user_meta( $current_user_id, 'mhub_webex_access_token', true );
			$jwt_token                 = get_transient( 'mhub_jitsi_jwt_token' );
			$mhub_google_account       = get_option( 'mhub_google_meet_account' );
			wp_enqueue_style( 'dashicons' );

			$jitsi_active_status = '';

			if ( ! empty( $mhub_jitsi_settings ) ) {
				$domain_type = $mhub_jitsi_settings['domain_type'];
				if ( 'jitsi_random_public' === $domain_type ) {
					$jitsi_active_status = 'connected';
				} elseif ( 'jitsi_self_hosted' === $domain_type ) {
					$custom_domain = $mhub_jitsi_settings['custom_domain'];
					if ( ! empty( $custom_domain ) ) {
						$jitsi_active_status = 'connected';
					}
				} elseif ( 'jitsi_jass_premium' === $domain_type && ! empty( $jwt_token ) ) {
					$jitsi_active_status = 'connected';
				}
			}

			if ( is_user_logged_in() ) {

				wp_enqueue_script( 'mhub-frontend-main-build', MHUB_ASSETS . '/dist/main.build.js', array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n' ), MHUB_VERSION, true );

				wp_enqueue_media();
				wp_enqueue_style( 'dashicons' );

				wp_localize_script(
					'mhub-frontend-main-build',
					'mhubMeetingsData',
					array(
						'oauthData'                 => ! empty( $oauth_data ) && $server_auth_prepare ? 'connected' : '',
						'webex_auth'                => ! empty( $webex_access_token ) ? 'connected' : '',
						'jitsi_active_status'       => $jitsi_active_status,
						'mhub_jitsi_settings'       => $mhub_jitsi_settings,
						'mhub_webex_settings'       => $mhub_webex_settings,
						'google_account'            => $mhub_google_account,
						'mhub_zoom_settings'        => $mhub_zoom_settings,
						'mhub_google_meet_settings' => $mhub_google_meet_settings,
						'mhub_timezone'             => mhub_timezone_offset(),
						'mhub_password'             => wp_generate_password( 8, false ),
						'zoom_users'                => get_option( 'mhub_zoom_users' ),
						'installed'                 => mhub_pro_plugin_exists(),
						'active'                    => mhub_fs()->can_use_premium_code__premium_only(),
						'license'                   => mhub_fs()->can_use_premium_code__premium_only(),
						'checkout_url'              => 'https://sovlix.com/meetinghub-pricing/',
						'siteurl'                   => get_site_url(),
						'hide_floating_create_btn'  => true,
						'hide_floating_update_btn'  => true,
						'mce_btn_name'              => __( 'Meeting', 'meetinghub' ),
						'is_admin'                  => is_admin(),
						'ajax_url'                  => admin_url( 'admin-ajax.php' ),
						'nonce'                     => wp_create_nonce( 'mhub_admin_nonce' ),
						'strings'                   => Mhub_Translate::strings(),
					)
				);
			}

			wp_enqueue_style( 'mhub-admin-fonts', MHUB_ASSETS . '/css/fonts.css', array(), MHUB_VERSION, 'all' );

			wp_enqueue_script( 'mhub-external-api-js', MHUB_ASSETS . '/js/external_api.js', array(), MHUB_VERSION, true );
			if ( ! mhub_fs()->can_use_premium_code__premium_only() ) {
				wp_enqueue_script( 'mhub-jitsi-meet-js', MHUB_ASSETS . '/js/jitsi-meet.js', array( 'jquery' ), MHUB_VERSION, true );

				$mhub_jitsi_settings_serialized = get_option( 'mhub_jitsi_settings' );
				$mhub_jitsi_settings            = maybe_unserialize( $mhub_jitsi_settings_serialized );
				$jwt_token                      = get_transient( 'mhub_jitsi_jwt_token' );

				// Localize the script and pass the create meeting URL.
				wp_localize_script(
					'mhub-jitsi-meet-js',
					'mhub_frontend',
					array(
						'mhub_jitsi_settings' => $mhub_jitsi_settings,
						'jwt'                 => $jwt_token,
					)
				);
			}

			wp_enqueue_script( 'mhub-frontend-js', MHUB_ASSETS . '/js/frontend.js', array( 'jquery', 'moment' ), MHUB_VERSION, true );

			wp_enqueue_script( 'mhub-frontend-build', MHUB_ASSETS . '/dist/frontend.build.js', array( 'wp-blocks', 'wp-element', 'wp-i18n' ), MHUB_VERSION, true );

			wp_enqueue_style( 'mhub-frontend-build-style', MHUB_ASSETS . '/dist/main.build.css', array(), MHUB_VERSION, 'all' );

			// Localize script to pass data to JavaScript.
			wp_localize_script(
				'mhub-frontend-build',
				'mhub_frontend_data',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'mhub_frontend_nonce' ),
				)
			);

			wp_localize_script(
				'mhub-frontend-js',
				'mhub_frontend_params',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'mhub_frontend_nonce' ),
				)
			);

			wp_enqueue_script( 'mhub-countdown-js', MHUB_ASSETS . '/js/countdown.js', array( 'jquery', 'moment' ), MHUB_VERSION, true );

			wp_localize_script(
				'mhub-countdown-js',
				'mhubtran',
				array(
					'strings' => Mhub_Translate::strings(),
				)
			);
			wp_enqueue_style( 'mhub-meeting-css', MHUB_ASSETS . '/css/meeting-style.css', array(), MHUB_VERSION, 'all' );
		}
	}
}
