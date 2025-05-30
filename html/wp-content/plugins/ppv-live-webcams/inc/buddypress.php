<?php
// BuddyPress Integration

// https://codex.buddypress.org/developer/function-examples/bp_activity_add/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class liveWebcamsGroup extends BP_Group_Extension {

	var $visibility = 'public'; // 'public' will show your extension to non-group members, 'private' means you have to be a member of the group to view your extension.

	var $enable_create_step = true; // If your extension does not need a creation step, set this to false
	var $enable_nav_item    = true; // If your extension does not need a navigation item, set this to false
	var $enable_edit_item   = true; // If your extension does not need an edit screen, set this to false


	public function __construct() {
		 $this->name = 'Webcams';
		$this->slug  = 'webcams';

		$this->create_step_position = 21;
		$this->nav_item_position    = 31;
	}


	public function liveWebcamsGroup() {
		// constructor
		self::__construct();

	}


	 function create_screen( $group_id = null ) {
		if ( ! bp_is_group_creation_step( $this->slug ) ) {
			return false;
		}
		?>

		<p><?php _e( 'To setup and update room or access as performer, go to Admin - Webcams after setting up group.', 'ppv-live-webcams' ); ?></p>

		<?php

		wp_nonce_field( 'groups_create_save_' . $this->slug );
	}


	 function create_screen_save( $group_id = null ) {
		global $bp;

		check_admin_referer( 'groups_create_save_' . $this->slug );

		/* Save any details submitted here */
		groups_update_groupmeta( $bp->groups->new_group_id, 'my_meta_name', 'value' );
	}


	 function edit_screen( $group_id = null ) {
		if ( ! bp_is_group_admin_screen( $this->slug ) ) {
			return false;
		}
		?>
				<h2><?php echo esc_attr( $this->name ); ?></h2>
		<?php
		global $bp;
		$root_url = get_bloginfo( 'url' ) . '/';

		if ( class_exists( 'VWliveWebcams' ) ) {
			VWliveWebcams::enqueueUI();

			// https://github.com/buddypress/BuddyPress/blob/master/src/bp-groups/classes/class-bp-groups-component.php
			// https://github.com/buddypress/BuddyPress/blob/master/src/bp-groups/classes/class-bp-groups-group.php

			$current_user = wp_get_current_user();

			global $wpdb;
			$options = get_option( 'VWliveWebcamsOptions' );
			$postID  = $wpdb->get_var( $wpdb->prepare( 
				"SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s LIMIT 0,1", 
				$bp->groups->current_group->slug, 
				$options['custom_post'] 
			) );

			if ( ! $postID ) {
				$postID = VWliveWebcams::webcamPost( $bp->groups->current_group->slug, VWliveWebcams::performerName( $current_user, $options ), $current_user->ID );
				echo '<p>' . __( 'Group room was created.', 'ppv-live-webcams' ) . '</p>';
			}

			if ( $postID ) {

				update_post_meta( $postID, 'buddypressGroup', $bp->groups->current_group->id );

				$performerIDs = get_post_meta( $postID, 'performerID', false );
				if ( ! in_array( $current_user->ID, $performerIDs ) ) {
					add_post_meta( $postID, 'performerID', $current_user->ID );
					$performerIDs = get_post_meta( $postID, 'performerID', false );
				}

				$performers = array();
				if ( $performerIDs ) {
					if ( count( $performerIDs ) ) {
						foreach ( $performerIDs as $performerID ) {
									$performer    = get_userdata( $performerID );
									$performers[] = $performer->user_login;
						}
					}
				}

				echo '<p>' . __( 'Group room is ready and you have been added to performer list.', 'ppv-live-webcams' ) . '</p>';

				$post = get_post( $postID );
				echo '<p>' . esc_html( $post->post_title ) . ' / ' . esc_html( $post->post_type ) . ' / ' . esc_html( $post->post_status ) . '<br> ' . esc_html( implode( ', ', $performers ) ) . '</p>';

				echo '<p><a class="ui button primary" href="' . get_permalink( $postID ) . '">' . __( 'Access Room Page', 'ppv-live-webcams' ) . '</a></p>';

				if ( $options['p_videowhisper_webcams_performer'] ) {
					echo '<p><a class="ui button primary" href="' . get_permalink( $options['p_videowhisper_webcams_performer'] ) . '">' . __( 'Access Performer Dashboard', 'ppv-live-webcams' ) . '</a></p>';
				}

				// echo do_shortcode('[videowhisper_videochat room="' .$bp->groups->current_group->slug. '"]');

				VWliveWebcams::billSessions();

			} else {
				echo 'Error: Could not create room!';
			}
		} else {
			echo 'Error: Live Webcams plugin not loaded.';
		}

	}

	 function edit_screen_save( $group_id = null ) {
		global $bp;

		if ( ! isset( $_POST['save'] ) ) {
			return false;
		}

		check_admin_referer( 'groups_edit_save_' . $this->slug );

		if ( ! $success ) {
			bp_core_add_message( __( 'There was an error saving, please try again', 'ppv-live-webcams' ), 'error' );
		} else {
			bp_core_add_message( __( 'Settings saved successfully', 'ppv-live-webcams' ) );
		}

		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/' . $this->slug );
	}

	 function display( $group_id = null ) {
		/* Use this function to display the actual content of your group extension when the nav item is selected */
		global $bp;
		// $root_url = get_bloginfo( "url" ) . "/";

		global $wpdb;
		$options = get_option( 'VWliveWebcamsOptions' );
		$postID  = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s LIMIT 0,1",
			$bp->groups->current_group->slug,
			$options['custom_post']
		) );

		if ( ! $postID ) {
			echo __( 'Webcams room was not setup, yet. Group administrator can set it up from Manage - Webcams.', 'ppv-live-webcams' );
		} else {

			echo '<p><center><a class="ui button primary" href="' . get_permalink( $postID ) . '">' . __( 'Access Room Page', 'ppv-live-webcams' ) . '</a></center></p>';

			echo do_shortcode( '[videowhisper_videochat room="' . $bp->groups->current_group->slug . '"]' );
		}
	}


	 function widget_display() {
		?>
		<div class="info-group">
			<h4><?php echo esc_attr( $this->name ); ?></h4>
			
		<?php
				global $wpdb;
		$options = get_option( 'VWliveWebcamsOptions' );
		$postID  = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s LIMIT 0,1",
			$bp->groups->current_group->slug,
			$options['custom_post']
		) );

		if ( ! $postID ) {
			echo __( 'Webcams room was not setup, yet. Group administrator can set it up from Manage - Webcams.', 'ppv-live-webcams' );
		} else {

			echo '<a class="ui button primary" href="' . get_permalink( $postID ) . '">' . __( 'Access Room Page', 'ppv-live-webcams' ) . '</a>';
		}

		?>
		</div>
		<?php
	}


}

bp_register_group_extension( 'liveWebcamsGroup' );
