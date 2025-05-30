<?php
/**
 * Register post type
 *
 * @package SOVLIX\MHUB
 */

namespace SOVLIX\MHUB;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mhub_Post_Types' ) ) {
	/**
	 * The Mhub_Post_Types class
	 *
	 * @since 1.0.0
	 */
	class Mhub_Post_Types {
		/**
		 * Mhub_Post_Types constructor.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'mhub_register_post_type' ) );
			add_filter( 'single_template', array( $this, 'mhub_meeting_hub_template' ) );

			// Hook into save_post to flush rewrite rules when a meeting is saved or updated.
			add_action( 'save_post', array( $this, 'mhub_flush_rewrite_rules_on_save' ) );
		}

		/**
		 * Register meeting post type
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function mhub_register_post_type() {
			$labels = array(
				'name'               => _x( 'Meeting', 'Post type general name', 'meetinghub' ),
				'singular_name'      => _x( 'Meeting', 'Post type singular name', 'meetinghub' ),
				'menu_name'          => _x( 'Meetings', 'Admin Menu text', 'meetinghub' ),
				'name_admin_bar'     => _x( 'Meeting', 'Add New on Toolbar', 'meetinghub' ),
				'add_new'            => __( 'Add New', 'meetinghub' ),
				'add_new_item'       => __( 'Add New Meeting', 'meetinghub' ),
				'new_item'           => __( 'New Meeting', 'meetinghub' ),
				'edit_item'          => __( 'Edit Meeting', 'meetinghub' ),
				'view_item'          => __( 'View Meeting', 'meetinghub' ),
				'all_items'          => __( 'All Meetings', 'meetinghub' ),
				'search_items'       => __( 'Search Meetings', 'meetinghub' ),
				'parent_item_colon'  => __( 'Parent Meetings:', 'meetinghub' ),
				'not_found'          => __( 'No meetings found.', 'meetinghub' ),
				'not_found_in_trash' => __( 'No meetings found in trash.', 'meetinghub' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => array(
					'slug'       => 'meeting',
					'with_front' => false,
				),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'excerpt' ),
			);

			register_post_type( 'mhub_meeting', $args );
		}

		/**
		 * Redirect to Single meeting template
		 *
		 * @param mixed $single Single meeting template.
		 *
		 * @return mixed
		 *
		 * @since 1.0.0
		 */
		public function mhub_meeting_hub_template( $single ) {
			global $post;
			if ( 'mhub_meeting' === $post->post_type ) {

				if ( ! locate_template( 'mhub-single-meeting.php' ) && file_exists( MHUB_FRONTEND . '/mhub-single-meeting.php' ) ) {
					return MHUB_FRONTEND . '/mhub-single-meeting.php';
				}
			}
			return $single;
		}

		/**
		 * Flush rewrite rules when a meeting is saved or updated
		 *
		 * @param int $post_id Post ID.
		 */
		public function mhub_flush_rewrite_rules_on_save( $post_id ) {
			$post_type = get_post_type( $post_id );

			if ( 'mhub_meeting' === $post_type ) {
				flush_rewrite_rules();
			}
		}
	}
}
