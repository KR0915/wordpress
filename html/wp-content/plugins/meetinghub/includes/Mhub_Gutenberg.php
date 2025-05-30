<?php
/**
 * Mhub_Gutenberg Class
 *
 * This class manages the frontend functionality for the Meeting Hub plugin.
 *
 * @package SOVLIX\MHUB
 */

namespace SOVLIX\MHUB;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mhub_Gutenberg' ) ) {
	/**
	 * Mhub_Gutenberg Class
	 *
	 * Handles frontend-related tasks and functionality for the Meeting Hub plugin.
	 *
	 * @since 1.0.0
	 */
	class Mhub_Gutenberg {

		/**
		 * Mhub_Gutenberg constructor.
		 *
		 * Initializes the frontend class.
		 */
		public function __construct() {
			add_action( 'enqueue_block_editor_assets', array( $this, 'register_block_assets' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		}

		/**
		 * Register block assets.
		 *
		 * Enqueues block editor script and styles.
		 *
		 * @since 1.0.0
		 */
		public function register_block_assets() {
			// Enqueue block editor script.
			wp_enqueue_script(
				'mhub-gutenberg-editor',
				MHUB_ASSETS . '/dist/block.build.js',
				array( 'wp-blocks', 'wp-components', 'wp-editor' ),
				filemtime( MHUB_PATH . '/assets/dist/block.build.js' ),
				true
			);

			$mhub_jitsi_settings = get_option( 'mhub_jitsi_settings' );

			// Localize script with zoom_users data.
			wp_localize_script(
				'mhub-gutenberg-editor',
				'mhubGutenbergData',
				array(
					'zoom_users'          => get_option( 'mhub_zoom_users' ),
					'mhub_jitsi_settings' => $mhub_jitsi_settings,
					'ajax_url'            => admin_url( 'admin-ajax.php' ),
					'nonce'               => wp_create_nonce( 'mhub_admin_nonce' ),
				)
			);

			wp_enqueue_style(
				'mhub-gutenberg-editor-style',
				MHUB_ASSETS . '/dist/block.build.css',
				array( 'wp-edit-blocks' ),
				filemtime( MHUB_PATH . '/assets/dist/block.build.css' ),
				null
			);
		}

		/**
		 * Enqueue frontend assets.
		 *
		 * Enqueues frontend styles.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_frontend_assets() {
			wp_enqueue_style(
				'mhub-frontend-style',
				MHUB_ASSETS . '/dist/block.build.css',
				array(),
				filemtime( MHUB_PATH . '/assets/dist/block.build.css' )
			);
		}
	}
}
