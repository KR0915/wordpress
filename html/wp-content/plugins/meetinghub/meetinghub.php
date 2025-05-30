<?php
/**
 * Plugin Name:      MeetingHub
 * Description:      Best and super easy WordPress webinar plugin to create instant meeting with Zoom, Jitsi Meet, Webex and more.
 * Version:          1.23.2
 * Author:           Sovlix
 * Author URI:       https://sovlix.com/
 * License:          GPL-2.0+
 * License URI:      http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:      meetinghub
 * Requires at least: 5.0
 * Tested up to:     6.8
 *
 * @package MHUB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';


if ( ! function_exists( 'mhub_fs' ) ) {
	// Create a helper function for easy SDK access.
	function mhub_fs() {
		global $mhub_fs;
		if ( ! isset( $mhub_fs ) ) {
			// Activate multisite network integration.
			if ( ! defined( 'WP_FS__PRODUCT_15716_MULTISITE' ) ) {
				define( 'WP_FS__PRODUCT_15716_MULTISITE', true );
			}
			// Include Freemius SDK.
			require_once __DIR__ . '/vendor/freemius/wordpress-sdk/start.php';

			add_filter( 'fs_playground_anonymous_mode_meetinghub', '__return_false' );

			$mhub_fs = fs_dynamic_init( array(
				'id'                  => '15716',
				'slug'                => 'meetinghub',
				'premium_slug'        => 'meetinghub-pro',
				'type'                => 'plugin',
				'public_key'          => 'pk_91bed431275e5aa68a06a65aa4071',
				'is_premium'          => false,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'parallel_activation' => array(
					'enabled'                  => true,
					'premium_version_basename' => 'meetinghub-pro/meetinghub-pro.php',
				),
				'menu'                => array(
					'slug'       => 'meetinghub',
					'first-path' => 'admin.php?page=meetinghub-settings',
					'contact'    => false,
					'support'    => false,
					'network'    => true,
				),
				'is_live'        => true,
			) );
		}
		return $mhub_fs;
	}

	// Init Freemius.
	mhub_fs();
	// Signal that SDK was initiated.
	do_action( 'mhub_fs_loaded' );
}


if ( ! defined( 'MHUB_VERSION' ) ) {
	define( 'MHUB_VERSION', '1.23.2' );
	define( 'MHUB_FILE', __FILE__ );
	define( 'MHUB_PATH', dirname( MHUB_FILE ) );
	define( 'MHUB_INCLUDES', MHUB_PATH . '/includes' );
	define( 'MHUB_FRONTEND', MHUB_PATH . '/includes/Frontend' );
	define( 'MHUB_TEMPLATES', MHUB_PATH . '/templates' );
	define( 'MHUB_URL', plugin_dir_url( MHUB_FILE ) );
	define( 'MHUB_ASSETS', MHUB_URL . 'assets' );
	define( 'MHUB_CSS_DIR', MHUB_URL . 'assets/css/' );
	define( 'MHUB_BUILD_CSS_DIR', MHUB_URL . 'assets/dist/' );
	define( 'MHUB_JS_DIR', MHUB_URL . 'assets/dist/' );

	// Load plugin base file.
	include_once MHUB_INCLUDES . '/mhub-plugin.php';
}
