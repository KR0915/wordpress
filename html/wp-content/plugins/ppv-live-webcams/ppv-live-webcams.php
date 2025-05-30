<?php
/*
Plugin Name: Paid Videochat Turnkey Site - HTML5 PPV Live Webcams
Plugin URI: https://paidvideochat.com
Description: <strong>Paid Videochat Turnkey Site - HTML5 PPV Live Webcams</strong>: pay per view / minute (PPV/PPM) videochat sites.  Includes custom registration (performers, clients, studios), live streaming webcams list, public lobby for each performer with live stream, mult-performer in show checkin, custom tips/gifts with notification, pay per minute private 2 way calls and group videochat shows, HTML5 WebRTC live video streaming, adaptive interface for mobile browsers, snapshots/pictures and videos profile, video conference mode with split view streaming, presentation/collaboration mode with file sharing and image/video display, paid questions/messages.  <a href='https://videowhisper.com/tickets_submit.php?topic=PPV-Live-Webcams'>Contact Support</a> | <a href='admin.php?page=live-webcams&tab=setup'>Setup</a>
Version: 7.3.13
Author: VideoWhisper.com
Author URI: https://videowhisper.com/
Contributors: videowhisper
Text Domain: ppv-live-webcams
Domain Path: /languages/
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/ 

defined( 'ABSPATH' ) or exit;

require_once plugin_dir_path( __FILE__ ) . '/inc/options.php';
require_once plugin_dir_path( __FILE__ ) . '/inc/requirements.php';
require_once plugin_dir_path( __FILE__ ) . '/inc/h5videochat.php';
require_once plugin_dir_path( __FILE__ ) . '/inc/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . '/inc/streams.php';

use VideoWhisper\LiveWebcams;

if ( ! class_exists( 'VWliveWebcams' ) ) {
	class VWliveWebcams {


		use VideoWhisper\LiveWebcams\Options;
		use VideoWhisper\LiveWebcams\Requirements;
		use VideoWhisper\LiveWebcams\H5Videochat;
		use VideoWhisper\LiveWebcams\Shortcodes;
		use VideoWhisper\LiveWebcams\Streams;

		public function __construct() {         }


		public function VWliveWebcams() {
			// constructor
			self::__construct();
		}

		// ! Plugin Hooks

		function init() {
			// setup post
			self::webcam_post();

			// prevent wp from adding <p> that breaks JS
			remove_filter( 'the_content', 'wpautop' );

			// move wpautop filter to BEFORE shortcode is processed
			add_filter( 'the_content', 'wpautop', 1 );

			// then clean AFTER shortcode
			add_filter( 'the_content', 'shortcode_unautop', 100 );


			//admin filter users
			add_action('restrict_manage_users', array( 'VWliveWebcams', 'restrict_manage_users' ) );
			add_filter('pre_get_users', array( 'VWliveWebcams', 'pre_get_users' ) );

			add_action( 'user_edit_form_tag', array( 'VWliveWebcams', 'user_edit_form_tag' ) );

			// cors
			// add_filter('allowed_http_origins', array('VWliveWebcams','allowed_http_origins') );

			$options = self::getOptions();

			if ( $options['corsACLO'] ?? false ) {
				$http_origin = get_http_origin();

				$found   = 0;
				$domains = explode( ',', $options['corsACLO'] );
				foreach ( $domains as $domain ) {
					if ( $http_origin == trim( $domain ) ) {
						$found = 1;
					}
				}

				if ( $found ) {
					header( 'Access-Control-Allow-Origin: ' . $http_origin );
					header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE, HEAD' ); // POST, GET, OPTIONS, PUT, DELETE, HEAD
					header( 'Access-Control-Allow-Credentials: true' );
					header( 'Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With' ); // Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With

					if ( 'OPTIONS' == $_SERVER['REQUEST_METHOD'] ) {
						status_header( 200 );
						exit();
					}
				}
			}

			// use a cookie for visitor username persistence
			if ( ! is_user_logged_in() ) {
				if ( !isset( $_COOKIE['htmlchat_username'] )  ) {
					$userName = 'G_' . base_convert( time() % 36 * rand( 0, 36 * 36 ), 10, 36 );
					setcookie( 'htmlchat_username', $userName );
				}
			}

			if ( $options['redirectWpLogin'] ?? false) {
				if ( $options['p_videowhisper_login'] ?? false) {
					// redirect WP login

					// WP tracks the current page - global the variable to access it
					global $pagenow;

					// Check if a $_GET['action'] is set, and if so, load it into $action variable
					$action = ( isset( $_GET['action'] ) ) ? sanitize_text_field( $_GET['action'] ) : '';

					// Check if we're on the login page, and ensure the action is not 'logout'
					if ( $pagenow == 'wp-login.php' && ( ! $action || ( $action && ! in_array( $action, array( 'logout', 'lostpassword', 'rp', 'resetpass' ) ) ) ) ) {

						// Redirect
						wp_redirect( get_permalink( $options['p_videowhisper_login'] ) );

						// Stop execution to prevent the page loading for any reason
						exit();
					}
				}
			}

		}


		/*
		function allowed_http_origins($origins)
		{

			$options = get_option('VWliveWebcamsOptions');

			if ($options['corsACLO'])
			{
				$domains = explode(',', $options['corsACLO']);
				foreach ($domains as $domain) $origins[] = trim($domain);
			}

			return $origins;

		}

		*/
		function activation() {
			 self::webcam_post();
			flush_rewrite_rules();

			if ( ! wp_next_scheduled( 'vwlw_hourly' ) ) {
				wp_schedule_event( time(), 'hourly', 'vwlw_hourly' );
			}
		}

		static function vwlw_hourly() {
			self::processTimeout(); // clears stalling ffmpeg
		}



//! BuddyPress / BuddyBoss

		static function bp_user_can_create_groups( $can_create ) {
			$current_user = wp_get_current_user();

			if ( ! self::any_in_array( self::getRolesPerformer(), $current_user->roles ) ) {
				return false;
			}

			return $can_create;
		}

		static function bp_setup_nav() {
			 global $bp;

			 if ( !isset($bp->displayed_user->id) ) return;
			 
			 $userID = $bp->displayed_user->id;
			 $user = get_userdata( $userID );
			 
			if ( !isset( $user->roles ) ) return; 
			
			if ( ! self::any_in_array( self::getRolesPerformer(), $user->roles ) ) return; //rooms and paid question tabs only for performer profiles

		
			//video questions / messages
			$options = self::getOptions();

			if ($options['buddypressConnect'] ?? false) bp_core_new_nav_item(
				array(
					'name'                    => __( 'Connect', 'ppv-live-webcams' ),
					'slug'                    => 'connect',
					'screen_function'         => array( 'VWliveWebcams', 'bp_connect_screen' ),
					'position'                => 40,
					'show_for_displayed_user' => true,
					'parent_url'              => $bp->displayed_user->domain,
					'parent_slug'             => $bp->profile->slug,
					'default_subnav_slug'     => 'connect',
				)
			);

			if ($options['buddypressRooms'] ?? false) bp_core_new_nav_item(
				array(
					'name'                    => __( 'Rooms', 'ppv-live-webcams' ),
					'slug'                    => 'rooms',
					'screen_function'         => array( 'VWliveWebcams', 'bp_rooms_screen' ),
					'position'                => 40,
					'show_for_displayed_user' => true,
					'parent_url'              => $bp->displayed_user->domain,
					'parent_slug'             => $bp->profile->slug,
					'default_subnav_slug'     => 'rooms',
				)
			);
			
	
			if ($options['messages'] && $options['buddypressMessages']) 
			bp_core_new_nav_item(
				array(
					'name'                    => __( 'Video Questions', 'ppv-live-webcams' ),
					'slug'                    => 'questions',
					'screen_function'         => array( 'VWliveWebcams', 'bp_questions_screen' ),
					'position'                => 40,
					'show_for_displayed_user' => true,
					'parent_url'              => $bp->displayed_user->domain,
					'parent_slug'             => $bp->profile->slug,
					'default_subnav_slug'     => 'questions',
				)
			);
			
		}

		static function bp_connect_screen() {
			// Add title and content here - last is to call the members plugin.php template.
			add_action( 'bp_template_title', array( 'VWliveWebcams', 'bp_connect_title' ) );
			add_action( 'bp_template_content', array( 'VWliveWebcams', 'bp_connect_content' ) );
			bp_core_load_template( 'buddypress/members/single/plugins' );
		}
		
		static function bp_rooms_screen() {

			// Add title and content here - last is to call the members plugin.php template.
			add_action( 'bp_template_title', array( 'VWliveWebcams', 'bp_rooms_title' ) );
			add_action( 'bp_template_content', array( 'VWliveWebcams', 'bp_rooms_content' ) );
			bp_core_load_template( 'buddypress/members/single/plugins' );
		}


		static function bp_questions_screen() {

			// Add title and content here - last is to call the members plugin.php template.
			add_action( 'bp_template_title', array( 'VWliveWebcams', 'bp_questions_title' ) );
			add_action( 'bp_template_content', array( 'VWliveWebcams', 'bp_questions_content' ) );
			bp_core_load_template( 'buddypress/members/single/plugins' );
		}

		static function bp_connect_title() {
			echo __( 'Connect', 'ppv-live-webcams' );
		}
		
		static function bp_rooms_title() {
			echo __( 'Rooms', 'ppv-live-webcams' );
		}

		static function bp_questions_title() {
			echo __( 'Video Questions', 'ppv-live-webcams' );
		}


		static function bp_connect_content() {
			//connect to user room
			
			global $bp;
			$userID = $bp->displayed_user->id;		
			$currentID = get_current_user_id();

			$selectWebcam = get_user_meta( $userID, 'currentWebcam', true );
			 
			$options = self::getOptions();
			
			if (!$selectWebcam) echo __( 'Not Available', 'ppv-live-webcams' );

			self::enqueueUI();

			if ($userID == $currentID ) 
			{
				//performer features
				if ( shortcode_exists( 'videowhisper_support_conversations' ) && $options['buddypressSupport'] ) 
				{
					
					//echo do_shortcode( '[videowhisper_support_conversations]' ); //broken layout by BP
					
						$optionsSupport = get_option( 'VWsupportOptions' );
						if ( $optionsSupport['p_videowhisper_support_conversations'] ?? false )   echo '<a class="ui button fluid" href="' . get_permalink( $optionsSupport['p_videowhisper_support_conversations'] ?? 0 ) . '"><i class="list alternate icon"></i> ' . __('My Conversations', 'ppv-live-webcams') . '</a>';
				}				
				
				if ( $options['p_videowhisper_webcams_performer'] ?? false ) echo '<a class="ui button fluid" href="' . get_permalink( $options['p_videowhisper_webcams_performer'] ) . '"><i class="cog icon"></i> ' . __('Performer Dashboard', 'ppv-live-webcams') . ' / ' . __('Go Live', 'ppv-live-webcams')  . '</a>';
			}else
			{
				//other users
				if (shortcode_exists( 'videowhisper_support' ) && $options['buddypressSupport'] ) echo do_shortcode( '[videowhisper_support params="creator:' . intval( $userID ) . '"]' ); 	

			}

			$room = get_post(intval($selectWebcam));
									
			if ($room && $options['buddypressEmbed']) echo do_shortcode( '[videowhisper_cam_app webcam_id="' . intval($selectWebcam) . '"]' );
			
			if ($room) 
			{
				echo '<a class="ui button fluid" href="' . get_permalink( intval($selectWebcam) ) . '"><i class="users icon"></i> ' . esc_attr($room->post_title) . ' - ' . __('Room Page', 'ppv-live-webcams') . '</a>';
				
				if ( $options['buddypressConnectMessages'] ?? false ) if ($currentID) 
				if ($userID != $currentID ) echo  do_shortcode( '[videowhisper_cam_message post_id="' . intval( $room->ID ) . '"]' );
				else echo '<a class="ui button fluid" href="' . esc_url( add_query_arg('messages','1', get_permalink( $options['p_videowhisper_webcams_performer'] ) ) ) . '"><i class="mail icon"></i> ' . __('My Video Messages', 'ppv-live-webcams') . '</a>';

			}
			else echo 'Error: Selected webcam room was not found!';
			
	
		}

		static function bp_rooms_content() {
		//rooms list
			global $bp;
			$userID = $bp->displayed_user->id;

			echo do_shortcode( '[videowhisper_webcams author_id="' . intval( $userID ) . '" menu="0" select_status="0" select_order="0" select_category="0" select_layout="0" select_tags="0" select_name="0"]' );

			/*
			   $options = self::getOptions();
				 $args = array(
				'post_type'      => $options['custom_post'],
				'post_status' => 'publish',
				'author'         => $userID,
				'orderby'        => 'date',
				'order'          => 'DESC'
			);
			$webcams = get_posts( $args );
			foreach ($webcams as $webcam)
			 echo '<a class="" href="' . get_permalink( $webcam->ID ). ' ">' . esc_html($webcam->post_title) . '<br>' .  get_the_post_thumbnail( $webcam->ID, array( 150, 150 ), array( 'class' => 'ui rounded image' ) )  . '</a><br>';
			*/

		}


		static function bp_questions_content() {

			global $bp;
			$userID = $bp->displayed_user->id;

			   $options = self::getOptions();
			   
			   //only users can send [paid] questions
			   
			  if ( ! is_user_logged_in() ) {
			echo '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' header"> <i class="sign in icon"></i> ' . __( 'Login to send questions!', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="1" role="' . esc_attr( $options['roleClient'] ) . '"]' );
			
			return;
		}

				 $args = array(
				'post_type'      => sanitize_text_field( $options['custom_post'] ),
				'post_status' => 'publish',
				'author'         => intval( $userID ),
				'meta_query'       => array(
					'relation' => 'OR',
					array(
						'key'   => 'question_closed',
						'value' => '0',
					),
		
					array(
						'key'     => 'question_closed',
						'compare' => 'NOT EXISTS',
					),
				),
				'meta_value'     => 0,
				'meta_compare'   => '=',
				'orderby'        => 'date',
				'order'          => 'DESC'
			);

			$webcams = get_posts( $args );
			
			if (!$webcams || !count($webcams)) echo __('User does not have rooms with questions open.', 'ppv-live-webcams');
			else foreach ($webcams as $webcam)
			 echo '<h4><a href="' . get_permalink( intval( $webcam->ID ) ). '">' . esc_html($webcam->post_title) .'</a></h4>' .  do_shortcode( '[videowhisper_cam_message post_id="' . intval( $webcam->ID ) . '"]' );
		}


		static function bp_pre_user_query_construct( $query_array ) {
			
			$options = self::getOptions();
				
			switch ($options['buddypressDirectory'])
			{
			
			case 'verified':
				$query_array->query_vars['meta_key'] = 'vwVerified';
				$query_array->query_vars['meta_value'] = 1;
			break;
			
			case 'webcam':
				$query_array->query_vars['meta_key'] = 'currentWebcam';
				break;
			}
		//$query_array->query_vars['member_type__in'] = [];
		}   
 
 
		function plugins_loaded() {
			 $options = get_option( 'VWliveWebcamsOptions' );

			 // user access update (updates with 10s precision)
			if ( is_user_logged_in() ) {
				$ztime  = time();
				$userID = get_current_user_id();

				// this user's access time
				$accessTime = intval( get_user_meta( $userID, 'accessTime', true ) );
				if ( $ztime - $accessTime > 10 ) {
					update_user_meta( $userID, 'accessTime', $ztime );
				}

				// any user access time
				$userAccessTime = intval( get_option( 'userAccessTime', 0 ) );
				if ( $ztime - $accessTime > 10 ) {
					update_option( 'userAccessTime', $ztime );
				}
			}

			add_filter( 'wp_nav_menu_items', array( 'VWliveWebcams', 'wp_nav_menu_items' ), 10, 2 );

			// translations
			load_plugin_textdomain( 'ppv-live-webcams', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			if ($options['frontendURLs'] ?? false)
			{
				add_filter( 'register_url', array( 'VWliveWebcams', 'register_url' ) );
				add_filter( 'login_url', array( 'VWliveWebcams', 'login_url' ), 10, 3 );
			}

			// restrict bp groups to performers
			if ( $options['buddypressGroupPerformer'] ?? false ) {
				add_filter( 'bp_user_can_create_groups', array( 'VWliveWebcams', 'bp_user_can_create_groups' ) );
			}

			add_action( 'show_user_profile', array( 'VWliveWebcams', 'user_profile_fields' ) );
			add_action( 'edit_user_profile', array( 'VWliveWebcams', 'user_profile_fields' ) );
			add_action( 'personal_options_update', array( 'VWliveWebcams', 'save_user_profile_fields' ) );
			add_action( 'edit_user_profile_update', array( 'VWliveWebcams', 'save_user_profile_fields' ) );
			
			
			
			if ( $options['buddypressDirectory'] ?? false ) add_action( 'bp_pre_user_query_construct',  array( 'VWliveWebcams', 'bp_pre_user_query_construct' ), 1, 1 );
	
			//terawallet fix
			if ( $GLOBALS['woo_wallet'] ?? false )
			{
			add_filter('woo_wallet_disallow_negative_transaction', '__return_false', 1);
			add_filter('woo_wallet_payment_is_available', '__return_true');
			}

			add_action( 'bp_setup_nav', array( $this, 'bp_setup_nav' ) );

			// settings link in plugins view
			$plugin = plugin_basename( __FILE__ );
			add_filter( "plugin_action_links_$plugin", array( 'VWliveWebcams', 'settings_link' ) );

			// webcam post handling
			add_filter( 'the_content', array( 'VWliveWebcams', 'the_content' ) );
			add_filter( 'pre_get_posts', array( 'VWliveWebcams', 'pre_get_posts' ) );

			// admin webcam posts
			add_filter( 'manage_' . ( $options['custom_post'] ?? 'webcam' ) . '_posts_columns', array( 'VWliveWebcams', 'columns_head_webcam' ), 10 );
			add_filter( 'manage_edit-' . ( $options['custom_post'] ?? 'webcam' ) . '_sortable_columns', array( 'VWliveWebcams', 'columns_register_sortable' ) );
			add_action( 'manage_' . ( $options['custom_post'] ?? 'webcam' ) . '_posts_custom_column', array( 'VWliveWebcams', 'columns_content_webcam' ), 10, 2 );
			add_filter( 'request', array( 'VWliveWebcams', 'duration_column_orderby' ) );

			add_action( 'wp_head', array( $this, 'wp_head' ) );

			add_action( 'quick_edit_custom_box', array( 'VWliveWebcams', 'quick_edit_custom_box' ), 10, 2 );
			add_action( 'save_post', array( 'VWliveWebcams', 'save_post' ) );

			// custom content template
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

			// crons
			add_action( 'vwlw_hourly', array( 'VWliveWebcams', 'vwlw_hourly' ) );

			// notify admin about requirements
			if ( current_user_can( 'administrator' ) ) {
				self::requirements_plugins_loaded();
			}

			// admin users
			add_filter( 'manage_users_columns', array( 'VWliveWebcams', 'manage_users_columns' ) );
			add_action( 'manage_users_custom_column', array( 'VWliveWebcams', 'manage_users_custom_column' ), 10, 3 );
			add_filter( 'manage_users_sortable_columns', array( 'VWliveWebcams', 'manage_users_sortable_columns' ) );
			add_action( 'pre_user_query', array( 'VWliveWebcams', 'pre_user_query' ) );

			add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 10, 2 ); // login before output

			// !shortcode definitions
			add_shortcode( 'videowhisper_sms_number', array( $this, 'videowhisper_sms_number' ) );

			add_shortcode( 'videowhisper_room_rate', array( $this, 'videowhisper_room_rate' ) );
		
			add_shortcode( 'videowhisper_reports', array( $this, 'videowhisper_reports' ) );

			add_shortcode( 'videowhisper_role_change', array( $this, 'videowhisper_role_change' ) );

			add_shortcode( 'videowhisper_callnow', array( $this, 'videowhisper_callnow' ) );
			
			add_shortcode( 'videowhisper_streams', array( $this, 'videowhisper_streams' ) );

			add_shortcode( 'videowhisper_login_form', array( $this, 'videowhisper_login_form' ) );
			add_shortcode( 'videowhisper_register_form', array( $this, 'videowhisper_register_form' ) );
			add_shortcode( 'videowhisper_register_activate', array( $this, 'videowhisper_register_activate' ) );
			add_shortcode( 'videowhisper_password_form', array( $this, 'videowhisper_password_form' ) );

			add_shortcode( 'videowhisper_cam_message', array( $this, 'videowhisper_cam_message' ) );
			add_shortcode( 'videowhisper_cam_messages', array( $this, 'videowhisper_cam_messages' ) );
			add_shortcode( 'videowhisper_cam_messages_performer', array( $this, 'videowhisper_cam_messages_performer' ) );

			// h5v
			add_shortcode( 'videowhisper_cam_calls', array( $this, 'videowhisper_cam_calls' ) );

			add_shortcode( 'videowhisper_cam_instant', array( $this, 'videowhisper_cam_instant' ) );
			add_shortcode( 'videowhisper_cam_random', array( 'VWliveWebcams', 'videowhisper_cam_random' ) );
			add_shortcode( 'videowhisper_match', array( 'VWliveWebcams', 'videowhisper_match' ) );
			add_shortcode( 'videowhisper_match_form', array( 'VWliveWebcams', 'videowhisper_match_form' ) );

			add_shortcode( 'videowhisper_cam_app', array( 'VWliveWebcams', 'videowhisper_cam_app' ) );

			add_shortcode( 'videowhisper_webcams', array( 'VWliveWebcams', 'videowhisper_webcams' ) );
			add_shortcode( 'videowhisper_webcams_performer', array( 'VWliveWebcams', 'videowhisper_webcams_performer' ) );
			add_shortcode( 'videowhisper_webcams_studio', array( 'VWliveWebcams', 'videowhisper_webcams_studio' ) );
			add_shortcode( 'videowhisper_webcams_client', array( 'VWliveWebcams', 'videowhisper_webcams_client' ) );

			add_shortcode( 'videowhisper_account_records', array( 'VWliveWebcams', 'videowhisper_account_records' ) );

			add_shortcode( 'videowhisper_webcams_logout', array( 'VWliveWebcams', 'videowhisper_webcams_logout' ) );

			add_shortcode( 'videowhisper_follow', array( 'VWliveWebcams', 'videowhisper_follow' ) );
			add_shortcode( 'videowhisper_follow_list', array( 'VWliveWebcams', 'videowhisper_follow_list' ) );

			add_shortcode( 'videowhisper_videochat', array( 'VWliveWebcams', 'videowhisper_videochat' ) );

			add_shortcode( 'videowhisper_camvideo', array( 'VWliveWebcams', 'videowhisper_camvideo' ) );
			add_shortcode( 'videowhisper_campreview', array( 'VWliveWebcams', 'videowhisper_campreview' ) );
			add_shortcode( 'videowhisper_caminfo', array( 'VWliveWebcams', 'videowhisper_caminfo' ) );

			add_shortcode( 'videowhisper_camprofile', array( 'VWliveWebcams', 'videowhisper_camprofile' ) );

			// html5
			add_shortcode( 'videowhisper_camhls', array( 'VWliveWebcams', 'videowhisper_camhls' ) );
			add_shortcode( 'videowhisper_cammpeg', array( 'VWliveWebcams', 'videowhisper_cammpeg' ) );

			add_shortcode( 'videowhisper_htmlchat', array( 'VWliveWebcams', 'videowhisper_htmlchat' ) );

			add_shortcode( 'videowhisper_cam_webrtc_broadcast', array( 'VWliveWebcams', 'videowhisper_cam_webrtc_broadcast' ) );
			add_shortcode( 'videowhisper_cam_webrtc_playback', array( 'VWliveWebcams', 'videowhisper_cam_webrtc_playback' ) ); // only video

			// buddypress: disable redirect BP registration (without roles)
			if ( $options['registrationFormRole'] ?? false ) {
				remove_action( 'bp_init', 'bp_core_wpsignup_redirect' );
				remove_filter( 'register_url', 'bp_get_signup_page' );
				add_filter( 'bp_get_signup_slug', array( 'VWliveWebcams', 'bp_get_signup_slug' ) );
			}

			// web app ajax calls
			add_action( 'wp_ajax_vmls_location', array( 'VWliveWebcams', 'vmls_location' ) );
			add_action( 'wp_ajax_nopriv_vmls_location', array( 'VWliveWebcams', 'vmls_location' ) );

			add_action( 'wp_ajax_vmls_notify', array( 'VWliveWebcams', 'vmls_notify' ) );
			add_action( 'wp_ajax_nopriv_vmls_notify', array( 'VWliveWebcams', 'vmls_notify' ) );

			add_action( 'wp_ajax_vmls_stream', array( 'VWliveWebcams', 'vmls_stream' ) );
			add_action( 'wp_ajax_nopriv_vmls_stream', array( 'VWliveWebcams', 'vmls_stream' ) );
			
			add_action( 'wp_ajax_vmls_recordsform', array( 'VWliveWebcams', 'vmls_recordsform' ) );
			add_action( 'wp_ajax_nopriv_vmls_recordsform', array( 'VWliveWebcams', 'vmls_recordsform' ) );

			add_action( 'wp_ajax_vmls_app', array( 'VWliveWebcams', 'vmls_app' ) );
			add_action( 'wp_ajax_nopriv_vmls_app', array( 'VWliveWebcams', 'vmls_app' ) );

			add_action( 'wp_ajax_vmls', array( 'VWliveWebcams', 'vmls_callback' ) );
			add_action( 'wp_ajax_nopriv_vmls', array( 'VWliveWebcams', 'vmls_callback' ) );

			add_action( 'wp_ajax_vmls_cams', array( 'VWliveWebcams', 'vmls_cams_callback' ) );
			add_action( 'wp_ajax_nopriv_vmls_cams', array( 'VWliveWebcams', 'vmls_cams_callback' ) );

			add_action( 'wp_ajax_vmls_htmlchat', array( 'VWliveWebcams', 'vmls_htmlchat_callback' ) );
			add_action( 'wp_ajax_nopriv_vmls_htmlchat', array( 'VWliveWebcams', 'vmls_htmlchat_callback' ) );

			add_action( 'wp_ajax_vmls_playlist', array( $this, 'vmls_playlist' ) );
			add_action( 'wp_ajax_nopriv_vmls_playlist', array( $this, 'vmls_playlist' ) );

			// sql fast session processing tables
			// check db
			$vmls_db_version = '6.8.1';

			$installed_ver = get_option( 'vmls_db_version' );

			if ( $installed_ver != $vmls_db_version ) {
				global $wpdb;

				$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';
				$table_private  = $wpdb->prefix . 'vw_vmls_private';
				$table_chatlog  = $wpdb->prefix . 'vw_vmls_chatlog';
				$table_follow   = $wpdb->prefix . 'vw_vmls_follow';
				$table_actions  = $wpdb->prefix . 'vw_vmls_actions';
				$table_messages = $wpdb->prefix . 'vw_vmls_messages';
				
				$table_payouts = $wpdb->prefix . 'vw_vmls_payouts';

				$table_party = $wpdb->prefix . 'vw_vmls_party';

				$wpdb->flush();

				$sql = "DROP TABLE IF EXISTS `$table_sessions`;
		CREATE TABLE `$table_sessions` (
		  `id` int(11) NOT NULL auto_increment,
		  `session` varchar(64) NOT NULL,
		  `username` varchar(64) NOT NULL,
		  `uid` int(11) NOT NULL,
		  `broadcaster` tinyint(4) NOT NULL,
		  `room` varchar(64) NOT NULL,
		  `rid` int(11) NOT NULL,
		  `rsdate` int(11) NOT NULL,
		  `redate` int(11) NOT NULL,
		  `roptions` text NOT NULL,
		  `meta` text NOT NULL,
		  `rmode` tinyint(4) NOT NULL,
		  `message` text NOT NULL,
		  `ip` text NOT NULL,
		  `sdate` int(11) NOT NULL,
		  `edate` int(11) NOT NULL,
		  `status` tinyint(4) NOT NULL,
		  `type` tinyint(4) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `status` (`status`),
		  KEY `broadcaster` (`broadcaster`),
		  KEY `type` (`type`),
		  KEY `rid` (`rid`),
		  KEY `uid` (`uid`),
		  KEY `rmode` (`rmode`),
		  KEY `rsdate` (`rsdate`),
		  KEY `redate` (`redate`),
		  KEY `sdate` (`sdate`),
		  KEY `edate` (`edate`),
		  KEY `room` (`room`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='VideoWhisper: Sessions 2015-2019@videowhisper.com' AUTO_INCREMENT=1 ;

		DROP TABLE IF EXISTS `$table_private`;
		CREATE TABLE `$table_private` (
		  `id` int(11) NOT NULL auto_increment,
  		  `room` varchar(64) NOT NULL,
		  `performer` varchar(64) NOT NULL,
		  `client` varchar(64) NOT NULL,
		  `pid` int(11) NOT NULL,
		  `cid` int(11) NOT NULL,
		  `rid` int(11) NOT NULL,
		  `ps` int(11) NOT NULL,
		  `cs` int(11) NOT NULL,			  
		  `psdate` int(11) NOT NULL,
		  `pedate` int(11) NOT NULL,
		  `csdate` int(11) NOT NULL,
		  `cedate` int(11) NOT NULL,
		  `status` tinyint(4) NOT NULL,
		  `call` tinyint(4) NOT NULL,
		  `meta` text NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `room` (`room`),
		  KEY `performer` (`performer`),
		  KEY `client` (`client`),
		  KEY `rid` (`rid`),
		  KEY `pid` (`pid`),
		  KEY `cid` (`cid`),
		  KEY `ps` (`ps`),
		  KEY `cs` (`cs`),			  
		  KEY `psdate` (`psdate`),
		  KEY `pedate` (`pedate`),
		  KEY `csdate` (`csdate`),
		  KEY `cedate` (`cedate`),
		  KEY `call` (`call`),
		  KEY `status` (`status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='VideosWhisper: Private Sessions 2015-2019@videowhisper.com' AUTO_INCREMENT=1 ;

		DROP TABLE IF EXISTS `$table_chatlog`;
		CREATE TABLE `$table_chatlog` (
		  `id` int(11) unsigned NOT NULL auto_increment,
		  `username` varchar(64) NOT NULL,
		  `user_id` int(11) unsigned NOT NULL,
		  `room` varchar(64) NOT NULL,
		  `room_id` int(11) unsigned NOT NULL,
		  `message` text NOT NULL,
		  `mdate` int(11) NOT NULL,
		  `type` tinyint(4) NOT NULL,
		  `private_uid` int(11) unsigned NOT NULL,
		  `meta` TEXT,
		  PRIMARY KEY  (`id`),
		  KEY `room` (`room`),
		  KEY `mdate` (`mdate`),
		  KEY `type` (`type`),
		  KEY `private_uid` (`private_uid`),
		  KEY `user_id` (`user_id`),
		  KEY `room_id` (`room_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='VideoWhisper: Chat Logs 2018-2019@videowhisper.com' AUTO_INCREMENT=1;

		DROP TABLE IF EXISTS `$table_actions`;
		CREATE TABLE `$table_actions` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `user_id` int(11) unsigned NOT NULL,
		  `room_id` int(11) unsigned NOT NULL,
		  `target_id` int(11) unsigned NOT NULL,
		  `action` varchar(64) NOT NULL,
		  `mdate` int(11) NOT NULL,
		  `meta` TEXT,
		  `status` tinyint(4) NOT NULL,
		  `answer` tinyint(4) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `mdate` (`mdate`),
		  KEY `user_id` (`user_id`),
		  KEY `room_id` (`room_id`),
		  KEY `target_id` (`target_id`),
		  KEY `action` (`action`),
		  KEY `status` (`status`),
		  KEY `answer` (`status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='VideoWhisper: Actions 2019@videowhisper.com' AUTO_INCREMENT=1;

		DROP TABLE IF EXISTS `$table_messages`;
		CREATE TABLE `$table_messages` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `sender_id` int(20) NOT NULL,
  `webcam_id` int(20) NOT NULL,
  `reply_id` int(20) NOT NULL,
  `sdate` int(11) NOT NULL,
  `ldate` int(11) NOT NULL,
  `meta` TEXT,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `webcam_id` (`webcam_id`),
  KEY `reply_id` (`reply_id`),
  KEY `cdate` (`sdate`),
  KEY `ldate` (`ldate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VideoWhisper:  Webcam Messages 2019@videowhisper.com';

DROP TABLE IF EXISTS `$table_payouts`;
CREATE TABLE `$table_payouts` (
`id` int(20) NOT NULL AUTO_INCREMENT,
`uid` int(20) NOT NULL,
`amount` decimal(8,2) NOT NULL,
`method` varchar(64) NOT NULL,
`csv` TEXT,
`meta` TEXT,
`ptime` int(11) NOT NULL,
PRIMARY KEY (`id`),
KEY `uid` (`uid`),
KEY `amount` (`amount`),
KEY `method` (`method`),
KEY `ptime` (`ptime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VideoWhisper:Payouts 2023@videowhisper.com';
";

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta( $sql );

				if ( ! $installed_ver ) {
					add_option( 'vmls_db_version', $vmls_db_version );
				} else {
					update_option( 'vmls_db_version', $vmls_db_version );
				}

				$wpdb->flush();

			}

		}


		function wp_head() {
			// echo '<!--wp_head ' . is_single() . ' ' .  get_the_ID() . ' -->';

			// implement social share tags

			// only for custom_post
			if ( ! is_single() ) {
				return;
			}

			$options = get_option( 'VWliveWebcamsOptions' );
			$postID  = get_the_ID();
			if ( ! get_post_type( $postID ) == $options['custom_post'] ) {
				return;
			}

			$post = get_post( $postID );

			echo '<meta property="og:title" content="' . esc_attr( htmlentities( $post->post_title ) ) . '">';
			echo '<meta property="og:description" content="' . ( esc_attr( $post->post_content ) ) . '">';
			echo '<meta property="og:image" content="' . esc_attr( self::webcamThumbSrc( $postID, sanitize_file_name( $post->post_title ), $options, '', false ) ) . '">';
			echo '<meta property="og:url" content="' . esc_url( get_the_permalink() ). '">';
			echo '<meta name="twitter:card" content="summary_large_image">';
			echo '<meta property="og:type" content="website">';
		}

		static function register_url( $url ) {
			if( is_admin() ) {
				return $url;
			}
	
			$options = self::getOptions();
			$register_page = ( $options['p_videowhisper_register_client'] ?? false ) ? get_permalink( $options['p_videowhisper_register_client'] ) : $url;
			return $register_page;
		}

		static function login_url($login_url, $redirect, $force_reauth)
		{
			$options = self::getOptions();
			$login_page = ( $options['p_videowhisper_login'] ?? false ) ? get_permalink( $options['p_videowhisper_login'] ) : $login_url;
			if ($redirect ?? false) $login_page = add_query_arg( 'redirect_to', $redirect, $login_page );
			return $login_page;
		}

		// ! Backend: Full Page App Template
		function add_meta_boxes() {
			$options = self::getOptions();

			$postTypes = explode( ',', $options['templateTypes'] );

			$showFor = array();
			foreach ( $postTypes as $postType ) {
				if ( post_type_exists( trim( $postType ) ) ) {
					$showFor[] = trim( $postType );
				}
			}

			if ( count( $showFor ) ) {
				add_meta_box(
					'videowhisper_live_webcams',           // Unique ID
					'Videochat Template for PPV Live Webcams',  // Box title
					array( $this, 'meta_box_html' ),  // Content callback, must be of type callable
					$showFor,                   // Post types
					'side' // Context
				);
			}
		}


		function meta_box_html( $post ) {
			$postTemplate = get_post_meta( $post->ID, 'videowhisper_template', true );
			?>

<h4>Videochat Template</h4>
<select name="videowhisper_template" id="videowhisper_template">
  <option value="" <?php echo ! $postTemplate ? 'selected' : ''; ?>>Default</option>
  <option value="+app" <?php echo $postTemplate == '+app' ? 'selected' : ''; ?>>Full Page (App)</option>
  <option value="+plugin" <?php echo $postTemplate == '+plugin' ? 'selected' : ''; ?>>Minimal with Theme Header & Footer</option>
</select> <?php echo esc_html( $postTemplate ); ?>
	<br>Use content Update button to save changes. Full page app template is recommended for <A href="admin.php?page=live-webcams&tab=app">HTML5 Videochat App</a>.
			<?php
		}


		/*
		//remove widgets
		function sidebars_widgets( $sidebars_widgets )
		{
			if (!is_single()) return $sidebars_widgets;

			$options = get_option('VWliveWebcamsOptions');

			$postID = get_the_ID();
			if (! get_post_type( $postID ) == $options['custom_post']) return $sidebars_widgets;

			// foreach ($sidebars_widgets as $key=>$value) unset($sidebars_widgets[$key]);
			$sidebars_widgets = array( false );

			return $sidebars_widgets;
		}

		//remove sidebar
		function get_sidebar( $name )
		{
			if (!is_single())  return $name;

			$options = get_option('VWliveWebcamsOptions');

			$postID = get_the_ID();
			if (! get_post_type( $postID ) == $options['custom_post']) return $name;

			// Avoid recurrsion: remove itself
			remove_filter( current_filter(), __FUNCTION__ );
			return get_sidebar( 'webcams' );
		}
		*/

		static function bp_get_signup_slug( $slug ) {
			return 'wp-login.php?action=register';
		}


		// ! Webcam Post Type
		function webcam_post() {
			$options = self::getOptions();

			// only if missing
			if ( post_type_exists( $options['custom_post'] ?? 'webcam') ) {
				return;
			}

			$labels = array(
				'name'                     => _x( 'Webcams', 'Post Type General Name', 'ppv-live-webcams' ),
				'singular_name'            => _x( 'Webcam', 'Post Type Singular Name', 'ppv-live-webcams' ),
				'menu_name'                => __( 'Webcams', 'ppv-live-webcams' ),
				'parent_item_colon'        => __( 'Parent Webcam:', 'ppv-live-webcams' ),
				'all_items'                => __( 'All Webcams', 'ppv-live-webcams' ),
				'view_item'                => __( 'View Webcam', 'ppv-live-webcams' ),
				'add_new_item'             => __( 'Add New Webcam', 'ppv-live-webcams' ),
				'add_new'                  => __( 'New Webcam', 'ppv-live-webcams' ),
				'edit_item'                => __( 'Edit Webcam', 'ppv-live-webcams' ),
				'update_item'              => __( 'Update Webcam', 'ppv-live-webcams' ),
				'search_items'             => __( 'Search Webcams', 'ppv-live-webcams' ),
				'not_found'                => __( 'No webcams found', 'ppv-live-webcams' ),
				'not_found_in_trash'       => __( 'No webcams found in Trash', 'ppv-live-webcams' ),

				// BuddyPress Activity
				'bp_activity_admin_filter' => __( 'New room created', 'ppv-live-webcams' ),
				'bp_activity_front_filter' => __( 'Rooms', 'ppv-live-webcams' ),
				// translators: %1$s: user name, %2$s: room link
				'bp_activity_new_post'     => __( '%1$s created a new <a href="%2$s">room</a>', 'ppv-live-webcams' ),
				// translators: %1$s: user name, %2$s: room link, %3$s: site name
				'bp_activity_new_post_ms'  => __( '%1$s created a new <a href="%2$s">room</a>, on the site %3$s', 'ppv-live-webcams' ),
			);

			$supports = array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields', 'page-attributes', 'buddypress-activity' );
			if ( isset($options['comments']) && $options['comments'] ) {
				$supports[] = 'comments';
			}

			$args = array(
				'label'               => __( 'webcam', 'ppv-live-webcams' ),
				'description'         => __( 'Live Webcams', 'ppv-live-webcams' ),
				'labels'              => $labels,
				'supports'            => $supports,
				'taxonomies'          => array( 'category', 'post_tag' ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'menu_position'       => 5,
				'can_export'          => true,
				'has_archive'         => false,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'menu_icon'           => 'dashicons-video-alt2',
				'capability_type'     => 'post',
				'capabilities'        => array(
					'create_posts' => 'do_not_allow', // false < WP 4.5, credit @Ewout
				),
				'map_meta_cap'        => true, // Set to `false`, if users are not allowed to edit/delete existing posts
			);

			// BuddyPress Activity
			if ( function_exists( 'bp_is_active' ) ) {
				if ( bp_is_active( 'activity' ) ) {
					$args['bp_activity'] = array(
						'component_id' => buddypress()->activity->id,
						'action_id'    => 'new_room',
						'contexts'     => array( 'activity', 'member' ),
						'position'     => 39,
					);
				}
			}

			register_post_type( $options['custom_post'] ?? 'webcam', $args );
			
			add_rewrite_rule( 'ajax$', '/wp-admin/admin-ajax.php', 'top' );

		}


		static function the_title( $title, $postID = null ) {
			$options = self::getOptions();

			if ( !isset($options['custom_post']) || !$options['custom_post'] ) return $title;

			if ( get_post_type( $postID ) != $options['custom_post'] ) {
				return $title;
			}

			$label = get_post_meta( $postID, 'vw_roomLabel', true );
			if ( $label ) {
				return $label;
			}

			// disable room name breakers
			$findthese   = array(
				'#Protected:#',
				'#Private:#',
			);
			$replacewith = array(
				'', // What to replace "Protected:" with
				'', // What to replace "Private:" with
			);
			$title       = preg_replace( $findthese, $replacewith, $title );

			return $title;
		}


		static function post_title( $postID ) {
			$post_title = get_the_title( $postID );
			if ( $post_title ) {
				return $post_title;
			}

			// else get direclty
			$post = get_post( $postID );
			return sanitize_file_name( $post->post_title ?? '' );
		}


		static function the_content( $content ) {
			// ! webcam post page

			$options = self::getOptions();


			$addCode = '<!-- VideWhisper.com / PaidVideochat / the_content -->';


			$isModerator = 0;
			if  ( is_user_logged_in() ) $isModerator = self::isModerator( get_current_user_id(), $options);
			
			// global geo blocking: applies to all content
			if (  $options['geoIP'] && $options['geoBlocking'] && !$isModerator ) {
				$clientLocation = self::detectLocation( 'all' ); // array
				if ( $clientLocation ) {
					if ( ! empty( $clientLocation ) ) {
						$banLocations = explode( ',', $options['geoBlocking'] );
						if ( is_array( $banLocations ) ) {
							foreach ( $banLocations as $key => $value ) {
								$banLocations[ $key ] = trim( $value );
							}

							$matches = array_intersect( $clientLocation, $banLocations );

							if ( $clientLocation ) {
								if ( is_array( $matches ) ) {
									if ( ! empty( $matches ) ) {
																		return $addCode . '<div class="ui ' . $options['interfaceClass'] . ' message"><h3 class="ui header">' . __( 'Access Forbidden!', 'ppv-live-webcams' ) . '</h3> ' . htmlspecialchars( $options['geoBlockingMessage'] ) . ' (' . count( $matches ) . ')' . '</div>';
									}
								}
							}
						}
					}
				}
			}

			if ( ! is_single() ) {
				return $content; // other listings
			}

			$postID = get_the_ID();
			if ( get_post_type( $postID ) != $options['custom_post'] ) {
				return $content; // othery post types
			}
			
			$room = self::post_title( $postID );

			// password protected
			if ( post_password_required( $postID ) && !$isModerator ) {
				return $addCode . '<!-- Password Protected Room -->' .  $content;
			}

			// webcam post geo blocking
			// ! banCountries if not owner or admin
			$banLocations = get_post_meta( $postID, 'vw_banCountries', false );

			if ( $banLocations && !$isModerator ) {
				if ( is_array( $banLocations ) ) {
					$current_user   = wp_get_current_user();
					$post_author_id = get_post_field( 'post_author', $postID );

					if ( $post_author_id != $current_user->ID && ! in_array( 'administrator', $current_user->roles ) ) {
						foreach ( $banLocations as $key => $value ) {
							$banLocations[ $key ] = trim( $value );
						}

						$clientLocation = self::detectLocation( 'all' ); // array
						// $banLocations = str_getcsv($vw_banCountries, ',');

						$matches = array_intersect( $clientLocation, $banLocations );

						if ( $clientLocation ) {
							if ( is_array( $matches ) ) {
								if ( ! empty( $matches ) ) {
									
									
										if ( !self::isPerformer( $current_user->ID, $postID ) ) return $addCode . '<div class="ui ' . $options['interfaceClass'] . ' message">' . __('This content is not available in your location!', 'ppv-live-webcams') . '</div>' . ' (' . count( $matches ) . ')';
										
										else $addCode .= '<div class="ui ' . $options['interfaceClass'] . ' message">This content is restricted for your location but you can access as performer.</div>';
								}
							}
						}
					}
				}
			}

			$view = isset($_GET['view']) ? sanitize_text_field( $_GET['view'] ) : '';

			if ( in_array( $view, array( 'profile', 'content', 'messages' ) ) ) {
				self::enqueueUI();

				if ( $view == 'profile' ) {
					$addCode .= self::webcamProfile( $postID, $options );
				}
				if ( $view == 'content' ) {
					$addCode .= self::webcamContent( $postID, $room, $options, $content );
				}
				if ( $view == 'messages' ) {
					$addCode .= self::webcamMessages( $postID, $options, $room );
				}

				$addCode .= self::webcamLinks( $postID, $options );

				return $addCode . '<!-- PaidVideochat / the_content view: ' . $view . ' -->' ;

			}

			// ! render webcam post page: room = sanitized post title
			$stream = get_post_meta( $postID, 'performer', true );
			if ( ! $stream ) {
				$stream = $room; // use room name
			}

			$hideApp = false;

			$addCode .= '<a name="vws-room"></a>';

			$isPerformer = self::isAuthor( $postID );

			// fake performer
			$playlistActive = get_post_meta( $postID, 'vw_playlistActive', true );

			// detect if performer online
			if ( $options['performerOffline'] != 'show' && ! $playlistActive ) {
				if ( ! $isPerformer ) {
					if ( ! self::webcamOnline( $postID ) ) {
						$addCode .= '<div id="performerStatus" class="ui ' . $options['interfaceClass'] . ' segment">' . $options['performerOfflineMessage'] . '</div>';
						if ( $options['performerOffline'] == 'hide' ) {
							$hideApp = true;							
						}
					}
				}
			}

			if ( $isModerator ) $hideApp = false;
			
			// paid room
			$groupCPM = floatval( get_post_meta( $postID, 'groupCPM', true ) );
			if ( !$isPerformer && !$isModerator) {
				if ( $groupCPM ) {
					$userID  = get_current_user_id();
					$balance = self::balance( $userID ); // use complete balance to avoid double amount checking

					$ppvMinInShow = self::balanceLimit( $groupCPM, 2, $options['ppvMinInShow'], $options );

					if ( $userID ) { // user
						if ( $groupCPM + $ppvMinInShow > $balance ) {
							$addCode .= '<div id="warnGroupCPM" class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'This is a paid room. A minimum balance required to access.', 'ppv-live-webcams' ) . '<br>' . ( $groupCPM + $ppvMinInShow ) . '/' . $balance . '<br><a class="ui button primary qbutton" href="' . get_permalink( $options['balancePage'] ) . '">' . __( 'Wallet', 'ppv-live-webcams' ) . '</a>' . '</div>';
							$hideApp  = true;
						}
					}

					if ( ! $userID ) {
						$addCode .= '<div id="warnGroupCPM" class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'This is a paid room. Login is required to access a paid room.', 'ppv-live-webcams' ) . '<br><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'ppv-live-webcams' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'ppv-live-webcams' ) . '</a></div>';
						$hideApp  = true;

					}
				}
			}

			// show app

			if ( ! $hideApp ) {
				$addCode .= do_shortcode('[videowhisper_videochat room="' . $room . '"]');
				
				// ip camera or playlist: update snapshot
				if ( get_post_meta( $postID, 'reStreams', true ) || get_post_meta( $postID, 'vw_playlistActive', true ) ) {
					self::streamSnapshot( $stream, true, $postID );
				}

				// update thumbnail if missing
				$dir           = $options['uploadsPath'] . '/_snapshots';
				$thumbFilename = "$dir/$stream.jpg";

				// only if snapshot exists but missing post thumb (not uploaded or generated previously)
				if ( file_exists( $thumbFilename ) && ! get_post_thumbnail_id( $postID ) ) {

					self::delete_associated_media( $postID, false );

					$wp_filetype = wp_check_filetype( basename( $thumbFilename ), null );

					$attachment = array(
						'guid'           => $thumbFilename,
						'post_mime_type' => $wp_filetype['type'],
						'post_title'     => sanitize_file_name( $stream ),
						'post_content'   => '',
						'post_status'    => 'inherit',
					);

					$attach_id = wp_insert_attachment( $attachment, $thumbFilename, $postID );
					set_post_thumbnail( $postID, $attach_id );

					require_once ABSPATH . 'wp-admin/includes/image.php';
					$attach_data = wp_generate_attachment_metadata( $attach_id, $thumbFilename );
					wp_update_attachment_metadata( $attach_id, $attach_data );
				}
			} else $addCode .= '<!-- hideApp -->';
			

			// under videochat

			// semantic ui : profile
			self::enqueueUI();

			$addCode .= '<!-- PaidVideochat / the_content / room content -->';

			$addCode .= self::webcamContent( $postID, $room, $options, $content );
			$addCode .= self::webcamLinks( $postID, $options );

			return $addCode;
		}


		static function query_vars( $vars ) {
			$vars[] = 'page';
			return $vars;
		}


		public static function pre_get_posts( $query ) {

			// add webcams to post listings
			if ( is_category() || is_tag() ) {
				$query_type = get_query_var( 'post_type' );

				$options = get_option( 'VWliveWebcamsOptions' );

				if ( $query_type ) {
					if ( is_array( $query_type ) ) {
						if ( in_array( 'post', $query_type ) && ! in_array( $options['custom_post'], $query_type ) ) {
							$query_type[] = $options['custom_post'];
						}
					}
				} else // default
					{
					$query_type = array( 'post', $options['custom_post'] );
				}

				$query->set( 'post_type', $query_type );
			}

			return $query;
		}

		static function columns_head_webcam( $defaults ) {
			$defaults['featured_image']        = 'Snapshot';
			$defaults['edate']                 = 'Last Online';
			$defaults['vw_costPerMinute']      = 'Custom CPM';
			$defaults['vw_costPerMinuteGroup'] = 'Custom Group CPM';
			$defaults['vw_earningRatio']       = 'Custom Earning Ratio';
			$defaults['customRoomLink']        = 'Custom Link';

			$defaults['vw_featured'] = 'Featured';
			$defaults['vwSuspended'] = 'Suspended';

			return $defaults;
		}


		static function columns_register_sortable( $columns ) {
			$columns['edate']            = 'edate';
			$columns['vw_costPerMinute'] = 'vw_costPerMinute';
			$columns['vw_costPerMinute'] = 'vw_costPerMinuteGroup';
			$columns['vw_earningRatio']  = 'vw_earningRatio';
			$columns['vw_featured']      = 'vw_featured';
			$columns['vwSuspended']      = 'vwSuspended';

			return $columns;
		}


		static function columns_content_webcam( $column_name, $post_id ) {

			if ( $column_name == 'featured_image' ) {

				$options = get_option( 'VWliveWebcamsOptions' );

				global $wpdb;
				$postName = sanitize_file_name( $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE ID = %d and post_type=%s LIMIT 1", $post_id, sanitize_text_field( $options['custom_post'] ) ) ) );

				if ( $postName ) {
					$options       = get_option( 'VWliveWebcamsOptions' );
					$dir           = $options['uploadsPath'] . '/_thumbs';
					$thumbFilename = "$dir/" . $postName . '.jpg';

					$url = self::roomURL( $postName );

					if ( file_exists( $thumbFilename ) ) {
						echo '<a href="' . esc_url_raw( $url ) . '"><IMG src="' . esc_url( self::path2url( $thumbFilename ) ) . '" width="' . esc_attr( $options['thumbWidth'] ) . 'px" height="' . esc_attr( $options['thumbHeight'] ) . 'px"></a>';
					}
				}
			}

			if ( $column_name == 'edate' ) {
				$edate = get_post_meta( $post_id, 'edate', true );
				if ( $edate ) {
					echo ' ' . esc_html( self::format_age( time() - $edate ) ) ;

					echo '<br><a class="button secondary" href="admin.php?page=live-webcams-reports&roomid=' . intval($post_id) . '">Reports</a>';
					echo '<br><a class="button secondary" href="admin.php?page=live-webcams-setup&roomid=' . intval($post_id) . '">Setup</a>';

				}
			}

			if ( $column_name == 'vw_costPerMinute' ) {
				echo esc_html( get_post_meta( $post_id, 'vw_costPerMinute', true ) );
			}

			if ( $column_name == 'vw_costPerMinuteGroup' ) {
				echo esc_html( get_post_meta( $post_id, 'vw_costPerMinuteGroup', true ) );
			}

			if ( $column_name == 'customRoomLink' ) {
				echo esc_html( get_post_meta( $post_id, 'customRoomLink', true ) );
			}

			if ( $column_name == 'vw_earningRatio' ) {
				echo esc_html( get_post_meta( $post_id, 'vw_earningRatio', true ) );
			}

			if ( $column_name == 'vw_featured' ) {
				$featured = get_post_meta( $post_id, 'vw_featured', true );
				if ( empty( $featured ) ) {
					update_post_meta( $post_id, 'vw_featured', 0 );
				}

				echo $featured ? __( 'Yes', 'ppv-live-webcams' ) . ' (' . esc_html( $featured ) . ')' : __( 'No', 'ppv-live-webcams' );
			}

			if ( $column_name == 'vwSuspended' ) {
				$suspended = get_post_meta( $post_id, 'vwSuspended', true ) ;

				echo esc_html( $suspended ?? false ? self::format_age( time() - $suspended ) : '-');
			}
		}


		public static function duration_column_orderby( $vars ) {
			if ( isset( $vars['orderby'] ) && 'edate' == $vars['orderby'] ) {
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => 'edate',
						'orderby'  => 'meta_value_num',
					)
				);
			}

			return $vars;
		}


		public static function quick_edit_custom_box( $column_name, $post_type ) {
			$options = get_option( 'VWliveWebcamsOptions' );

			static $printNonce = true;
			if ( $printNonce ) {
				$printNonce = false;
				wp_nonce_field( plugin_basename( __FILE__ ), $options['custom_post'] . '_edit_nonce' );
			}

			?>
	<fieldset class="inline-edit-col-right inline-edit-book">
	  <div class="inline-edit-col column-<?php echo esc_html( $column_name ); ?>">
		<label class="inline-edit-group">
			<?php
			switch ( $column_name ) {
				case 'vwSuspended':
					?>
				<span class="title">Suspended</span><input name="vwSuspended" value="<?php echo time()?>"/>
				<BR>Set 0 to remove suspension or suspension time.
					<?php
					break;
				case 'vw_costPerMinute':
					?>
				<span class="title">New Custom CPM</span><input name="vw_costPerMinute" />
				<BR>Custom cost per minute for private shows. Ex: 0.5
					<?php
					break;
				case 'vw_costPerMinuteGroup':
					?>
				<span class="title">New Custom Group CPM</span><input name="vw_costPerMinuteGroup" />
				<BR>Custom cost per minute for group shows. Replaces paid group CPM. Ex: 0.5
					<?php
					break;
				case 'vw_earningRatio':
					?>
				<span class="title">New Custom Earning Ratio</span><input name="vw_earningRatio"/>
				<BR>Fraction earned by performer. Ex: 0.80 Min: 0 Max: 1
					<?php
					break;
				case 'vw_featured':
					?>
				<span class="title">New Featured Level</span><input name="vw_featured"/>
				<BR>Higher featured show first in listings. Ex: 1 Default: 0 (not featured)
					<?php
					break;
				case 'customRoomLink':
					?>
				<span class="title">New Custom Room Link</span><input name="customRoomLink"/>
				<BR>Define a custom link for room Enter button in listings.
					<?php
					break;

			}
			?>
		</label>
	  </div>
	</fieldset>
			<?php
		}


		static function save_post( $post_id ) {
			if ( isset( $_REQUEST['videowhisper_template'] ) ) {
				$postTemplate = sanitize_text_field( $_REQUEST['videowhisper_template'] );
				update_post_meta( $post_id, 'videowhisper_template', $postTemplate );
			}

			$options = get_option( 'VWliveWebcamsOptions' );

			$slug = sanitize_text_field( $options['custom_post'] ?? 'webcam' );

			if ( $slug !== sanitize_text_field( $_POST['post_type'] ?? '' ) ) {
				return;
			}
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			$_POST += array( "{$slug}_edit_nonce" => '' );
			if ( ! wp_verify_nonce(
				$_POST[ "{$slug}_edit_nonce" ],
				plugin_basename( __FILE__ )
			) ) {
				return;
			}

			if ( isset( $_REQUEST['vwSuspended'] ) ) {
				$value = intval($_REQUEST['vwSuspended']);
				if ($value) update_post_meta( $post_id, 'vwSuspended', 	$value  );
				else delete_post_meta( $post_id, 'vwSuspended' );
			}

			if ( isset( $_REQUEST['vw_costPerMinute'] ) ) {
				update_post_meta( $post_id, 'vw_costPerMinute', sanitize_text_field( $_REQUEST['vw_costPerMinute'] ) );
			}

			if ( isset( $_REQUEST['vw_costPerMinuteGroup'] ) ) {
				update_post_meta( $post_id, 'vw_costPerMinuteGroup', sanitize_text_field( $_REQUEST['vw_costPerMinuteGroup'] ) );
			}

			if ( isset( $_REQUEST['vw_earningRatio'] ) ) {
				update_post_meta( $post_id, 'vw_earningRatio', sanitize_text_field( $_REQUEST['vw_earningRatio'] ) );
			}

			if ( isset( $_REQUEST['vw_featured'] ) ) {
				update_post_meta( $post_id, 'vw_featured', sanitize_text_field( $_REQUEST['vw_featured'] ) );
			}

			if ( isset( $_REQUEST['customRoomLink'] ) ) {
				update_post_meta( $post_id, 'customRoomLink', sanitize_text_field( $_REQUEST['customRoomLink'] ) );
			}

		}


		static function webcamPost( $name = '', $performer = '', $performerID = 0, $studioID = 0 ) {
			// retrieves default performer webcam listing or creates it if necessary


			$current_user = '';

			if ($performerID) $current_user = get_userdata( $performerID );
			
			if ( is_user_logged_in() && !$current_user ) {
				$current_user = wp_get_current_user();
			}
			
			if ( !$current_user ) return; 
			

			$options = get_option( 'VWliveWebcamsOptions' );


			if ( ! $name ) {
				$post_title = self::performerName( $current_user, $options );

			} else {
				$post_title = sanitize_file_name( $name );
			}

			global $wpdb;
			$pid = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = '%s' AND post_type='" . $options['custom_post'] . "'", $post_title ) );

			if ( ! $pid ) { // creating
				$post = array(
					'post_name'   => sanitize_title_with_dashes( $post_title ),
					'post_title'  => sanitize_file_name( $post_title ),
					'post_author' => $current_user->ID,
					'post_type'   => $options['custom_post'],
					'post_status' => 'publish',
				);

				$pid = wp_insert_post( $post );
				
				//other meta
				update_post_meta( $pid, 'edate', 0 ); //last broadcast date (0 = never)
				update_post_meta( $pid, 'viewers', 0);
				update_post_meta( $pid, 'maxViewers', 0 );
				update_post_meta( $pid, 'maxDate', time() );

				if ( ! $performer ) {
					$performer = $post_title;
				}

				if ( $performer ) {
					update_post_meta( $pid, 'performer', $performer );
				}

				if ( ! $performerID ) {
					$performerID = $current_user->ID;
				}
				if ( $performerID ) {
					// no need to assign if already owner
					if ( $current_user->ID != $performerID ) {
						update_post_meta( $pid, 'performerID', $performerID );
					}

					// set as selected webcam for this performer if he has no cam
					$selectWebcam = get_user_meta( $performerID, 'currentWebcam', true );
					if ( ! $selectWebcam ) {
						update_user_meta( $performerID, 'currentWebcam', $pid );
					}
				}

				if ( $studioID ) {
					update_post_meta( $pid, 'studioID', $studioID );
				}

			}

			return $pid;
		}


		function single_template( $single_template ) {

			if ( ! is_singular() ) {
				return $single_template; // not single page/post
			}

			// forced template
			switch ( $_GET['vwtemplate'] ?? '' ) {
				case 'app':
					$single_template_new = dirname( __FILE__ ) . '/template-app.php';
					if ( file_exists( $single_template_new ) ) {
						return $single_template_new;
					}
					break;

				case 'plugin':
					$single_template_new = dirname( __FILE__ ) . '/template-webcam.php';
					if ( file_exists( $single_template_new ) ) {
						return $single_template_new;
					}
					break;

			}

			$postID = get_the_ID();

			// custom template
			$postTemplate = get_post_meta( $postID, 'videowhisper_template', true );
			if ( $postTemplate == '+app' ) {
				$single_template_new = dirname( __FILE__ ) . '/template-app.php';
				if ( file_exists( $single_template_new ) ) {
					return $single_template_new;
				}
			}
			if ( $postTemplate == '+plugin' ) {
				$single_template_new = dirname( __FILE__ ) . '/template-webcam.php';
				if ( file_exists( $single_template_new ) ) {
					return $single_template_new;
				}
			}

			$options = get_option( 'VWliveWebcamsOptions' );
			// webcam post template
			if ( get_post_type( $postID ) != $options['custom_post'] ) {
				return $single_template;
			}

			if ( $options['postTemplate'] == '+app' ) {
				$single_template_new = dirname( __FILE__ ) . '/template-app.php';
				if ( file_exists( $single_template_new ) ) {
					return $single_template_new;
				}
			}
			if ( $options['postTemplate'] == '+plugin' ) {
				$single_template_new = dirname( __FILE__ ) . '/template-webcam.php';
				if ( file_exists( $single_template_new ) ) {
					return $single_template_new;
				}
			}

			$single_template_new = get_template_directory() . '/' . $options['postTemplate'];

			if ( file_exists( $single_template_new ) ) {
				return $single_template_new;
			} else {
				return $single_template;
			}
		}


		static function importArchives( $postID ) {
			// import recorded archived sessions with VSV

			$options = get_option( 'VWliveWebcamsOptions' );

			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			if ( ! is_plugin_active( 'video-share-vod/video-share-vod.php' ) ) {
				return; // requires VideoShareVOD.com plugin installed and active
			}

			$archivedSessions = get_post_meta( $postID, 'archivedSessions', true );
			if ( ! $archivedSessions ) {
				return;
			}

			$post = get_post( $postID );

			// $archivedSession = array('performer' =>$performerName, 'sessionStart' => time(), 'groupMode'=>$groupMode);

			$ignored    = array( '.', '..', '.svn', '.htaccess' );
			$extensions = array( 'flv', 'mp4', 'f4v', 'm4v' );

			foreach ( $archivedSessions as $key => $archivedSession ) {
				$fileList = scandir( $options['streamsPath'] );

				$prefix  = $archivedSession['performer'];
				$prefixL = strlen( $prefix );

				foreach ( $fileList as $fileName ) {

					if ( in_array( $fileName, $ignored ) ) {
						continue;
					}
					if ( ! in_array( strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) ), $extensions ) ) {
						continue;
					}
					if ( $prefixL ) {
						if ( substr( $fileName, 0, $prefixL ) != $prefix ) {
							continue;
						}
					}

						$filepath = $options['streamsPath'] . '/' . $fileName;

					// found!

					$modified = filemtime( $filepath );

					$playlists = array( $archivedSession['performer'], $post->post_title, $archivedSession['groupMode'], $post->post_title . ' ' . $archivedSession['groupMode'], $post->post_title . ' ' . $archivedSession['groupMode'] . ' ' . date( 'j M Y', $modified ) );

					$tags = array( $archivedSession['performer'], $post->post_title, $archivedSession['groupMode'], date( 'j M Y', $modified ) );

					$title = $post->post_title . ' ' . $archivedSession['groupMode'] . ' ' . $archivedSession['performer'] . ' ' . date( 'G:i j M Y', $modified );

					$description = $title . ' ' . $filepath;

					// import
					VWvideoShare::importFile( $filepath, $title, $post->post_author, $playlists, $category, $tags, $description );

					// clean
					unlink( $filepath );

					// remove from list
					unset( $archivedSessions['key'] );
					update_post_meta( $postID, 'archivedSessions', $archivedSessions );

					// import one file only
					break;
				}
			}

			// end: reset list
			update_post_meta( $postID, 'archivedSessions', array() );

		}




		static function vsvVideoURL( $video_teaser, $options = null ) {
			if ( ! $video_teaser ) {
				return '';
			}

			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}
			$streamPath = '';

			// use conversion if available
			$videoAdaptive = get_post_meta( $video_teaser, 'video-adaptive', true );
			if ( $videoAdaptive ) {
				$videoAlts = $videoAdaptive;
			} else {
				$videoAlts = array();
			}

			foreach ( array( 'high', 'mobile' ) as $frm ) {
				if ( array_key_exists( $frm, $videoAlts ) ) {
					if ( $alt = $videoAlts[ $frm ] ) {
						if ( file_exists( $alt['file'] ) ) {
							$ext = pathinfo( $alt['file'], PATHINFO_EXTENSION );
							if ( $options['hls_vod'] ?? false ) {
								$streamPath = self::path2stream( $alt['file'] );
							} else {
								$streamPath = self::path2url( $alt['file'] );
							}
							break;
						}
					}
				}
			};

				// user original
			if ( ! $streamPath ) {
				$videoPath = get_post_meta( $video_teaser, 'video-source-file', true );
				$ext       = pathinfo( $videoPath, PATHINFO_EXTENSION );

				if ( in_array( $ext, array( 'flv', 'mp4', 'm4v' ) ) ) {
					// use source if compatible
					if ( $options['hls_vod'] ?? false ) {
						$streamPath = self::path2stream( $videoPath );
					} else {
						$streamPath = self::path2url( $videoPath );
					}
				}
			}

			if ( $options['hls_vod'] ?? false ) {
				$streamURL = $options['hls_vod'] . '_definst_/' . $streamPath . '/manifest.mpd';
			} else {
				$streamURL = $streamPath;
			}

			return $streamURL;
		}


		static function balanceLimit( $cpm, $minutes, $default = 0, $options = null ) {
			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}
			if ( ! $options['autoBalanceLimits'] ) {
				return $default;
			}

			$limit = $cpm * $minutes;

			return max( $limit, $default ); // maximum balance minimum required
		}


		// ! user sessions vw_vmls_sessions
		static function sessionValid( $sessionID, $userID ) {
			// returns true if session is valid

			global $wpdb;
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

			$sqlS    = $wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d AND uid=%d AND status=0 LIMIT 1", $sessionID, $userID);
			$session = $wpdb->get_row( $sqlS );

			if ( $session ) {
				return $session;
			} else {
				return false;
			}
		}


		static function updateOnlineBroadcaster( $sessionID ) {

			global $wpdb;
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

			$sqlS    = $wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d AND broadcaster <> '0' AND status='0' LIMIT 0,1", $sessionID);
			$session = $wpdb->get_row( $sqlS );

			if ( ! $session ) {
				return 'Broadcaster session missing or closed.';
			}

			$ztime = time();

			$sql = $wpdb->prepare("UPDATE `$table_sessions` SET edate=%d WHERE id=%d", $ztime, $sessionID);
			$wpdb->query($sql);

			$postID = $session->rid;
			update_post_meta( $postID, 'edate', $ztime );

			self::updateViewers( $postID, $session->room );

			/*
				$table_private = $wpdb->prefix . "vw_vmls_private";
				//update perfomer private show status (present in private chats)
				$shows =  $wpdb->get_var("SELECT count(id) as no FROM `$table_private` where status='0' and room='" . $session->room . "' and pid='" . $session->uid . "'");
				if ($shows) $shows =1; else $shows = 0;
				update_post_meta($postID, 'privateShow', $shows);
			*/

			self::billSessions();

		}


		// self::privateSessionUpdate($session, $post, $isPerformer, $privateUID, $options, $end);

		static function privateSessionUpdate( $session, $post, $isPerformer, $privateUID, $options, $end = 0, &$returnInfo = null ) {

			// called by app/session control to update private session
			// $session is main session, used to update private session

			// $end : request to end this session

			// note: if private session is detected, public session is swiched to a free one if paid (from updateOnlineViewer)

			$ztime = time();

			$clientCPM = self::clientCPM( $post->post_title, $options, $post->ID ); // default, if needed to create new session
			$balance   = self::balance( $session->uid, false, $options );

			global $wpdb;
			$table_private  = $wpdb->prefix . 'vw_vmls_private';
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions'; // to update meta

			$sqlEnd  = '';
			$sqlEndC = '';

			if ( ! $end ) {
				$end = 0;
			} else {
				$sqlEnd  = ', status=1';
				$sqlEndC = "OR status='1'";
			}

					$call = 0;
					$room_random = self::is_true( get_post_meta( $post->ID, 'room_random', true ) );
					if ($room_random) $call = 2;
			
			// make sure: detect
			$isPerformer2 = self::isPerformer( $session->uid, $post->ID );

			$sqlS = '';
			$sqlI = '';

			$cost = 0;
			$earn = 0;
			$other = '';
		
				// free call?
				$freeCall = 0;
				if ( $session->meta ) {
					$userMeta = unserialize( $session->meta );
				}
				if ( ! is_array( $userMeta ) ) {
					$userMeta = array();
				}
				if ( array_key_exists( 'callMode', $userMeta ) ) {
					if ( $userMeta['callMode'] == 'free' ) {
						$freeCall = 1;
					}
				}
					
					
			if ( $isPerformer2 ) {
				// performer
								
				// retrieve or create session
				$sqlS = $wpdb->prepare("SELECT * FROM $table_private WHERE rid=%d AND pid=%d AND cid=%d AND (status=0 $sqlEndC) ORDER BY status ASC, id DESC", $post->ID, $session->uid, $privateUID);
				$pSession = $wpdb->get_row( $sqlS );

				if ( ! $pSession ) { //create
					if ( $end ) {
						$disconnect = 'No session found to end!';
					}

					if ($call) return; //calls need to be setup externally, not on access
	
					$user           = get_userdata( $privateUID );
					$clientUsername = self::clientName( $user, $options );

					$pSessionMeta              = array();
					$pSessionMeta['clientCPM'] = $clientCPM;
					$pSessionMeta['first']     = 'performer';
					$pSessionMetaS             = serialize( $pSessionMeta );

					$sqlI = $wpdb->prepare(
						"INSERT INTO `$table_private` ( `performer`, `pid`, `ps`, `rid`, `client`, `cid`, `room`, `psdate`, `pedate`, `status`, `meta`, `call` ) VALUES ( %s, %d, %d, %d, %s, %d, %s, %d, %d, %d, %s, %d )",
						$session->username, $session->uid, $session->id, $post->ID, $clientUsername, $privateUID, $post->post_title, $ztime, $ztime, 0, $pSessionMetaS, $call
					);
					$wpdb->query( $sqlI );
					$pSession = $wpdb->get_row( $sqlS );
				}//create end

				// update id and time
				$sdate = $pSession->psdate;
				if ( ! $sdate ) {
					$sdate = $ztime; // first time = start time
				}

				$sql = $wpdb->prepare("UPDATE `$table_private` SET pid = %d, psdate = %d, pedate = %d $sqlEnd WHERE id = %d", $session->uid, $sdate, $ztime, $pSession->id);
				$wpdb->query( $sql );

				// info
				$timeUsed = $ztime - $sdate;

				$cost         = self::performerCost( $pSession );
				$ppvMinInShow = self::balanceLimit( $options['ppvPerformerPPM'], 2, $options['ppvMinInShow'], $options );

				//balance check
				if ( $cost > 0 ) {
					if ( $cost + $ppvMinInShow > $balance ) {
						$disconnect = 'Not enough funds left for performer to continue session. Minimum extra required: ' . $ppvMinInShow . " Session Cost:$cost Balance:$balance";
					}
				}
				
				if (!$freeCall) 
				{
						$clientCost         = self::clientCost( $pSession );
						$performerRatio = self::performerRatio( $session->room, $options, $post->ID );
						$earn = number_format( $clientCost * $performerRatio / 60, 2, '.', '' );
						
						$userMeta['privateEarn']        = $earn;
						$userMeta['performerRatio']     = $performerRatio;

				}
		
				$other = $pSession->client;
				
			} else {
				// client
 
				// retrieve or create session //OR ( `call`='2' AND cid='0' )
				$sqlS     = $wpdb->prepare("SELECT * FROM $table_private WHERE rid=%d AND cid=%d AND pid=%d AND (status=0 $sqlEndC) ORDER BY status ASC, id DESC", $post->ID, $session->uid, $privateUID);
				$pSession = $wpdb->get_row( $sqlS );

				if ( ! $pSession ) {
					if ( $end ) {
						$disconnect = 'No session found to end!';
					}

					if ($call) return; //calls need to be setup by performer
					
					$user              = get_userdata( $privateUID );
					$performerUsername = self::performerName( $user, $options );

					$pSessionMeta              = array();
					$pSessionMeta['clientCPM'] = $clientCPM;
					$pSessionMeta['first']     = 'client';
					$pSessionMetaS             = serialize( $pSessionMeta );

					$sqlI = $wpdb->prepare(
						"INSERT INTO `$table_private` ( `rid`, `client`, `cid`, `cs`, `performer`, `pid`,  `room`, `csdate`, `cedate`, `status`, `meta` ) VALUES ( %d, %s, %d, %d, %s, %d, %s, %d, %d, %d, %s )",
						$post->ID, $session->username, $session->uid, $session->id, $performerUsername, $privateUID, $post->post_title, $ztime, $ztime, 0, $pSessionMetaS
					);

					$wpdb->query( $sqlI );
					$pSession = $wpdb->get_row( $sqlS );
				}//create end client session 

				// update id and time

				$sdate = $pSession->csdate;
				if ( ! $sdate ) {
					$sdate = $ztime; // first time = start time
				}

				$sql = $wpdb->prepare("UPDATE `$table_private` SET cid = %d, csdate = %d, cedate = %d $sqlEnd WHERE id = %d", $session->uid, $sdate, $ztime, $pSession->id);
				$wpdb->query( $sql );

				// info
				$timeUsed = $ztime - $sdate;


				//balance check
				if ( ! $freeCall ) {
					$cost         = self::clientCost( $pSession );
					$ppvMinInShow = self::balanceLimit( $clientCPM, 2, $options['ppvMinInShow'], $options );
					if ( $cost > 0 ) {
						if ( $cost + $ppvMinInShow > $balance ) {
							$disconnect = __( 'Not enough funds left for client to continue session. Minimum required: ', 'ppv-live-webcams' ) . $ppvMinInShow;
						}
					}
				} else {
					$cost = 0;
				}
				
				
				$other = $pSession->performer;

			}

			// performer/client common
			
			$returnInfo = (array) $pSession;

			if ( $options['debugMode'] ?? false ) {
				$returnInfo['sqlS'] = $sqlS;
			}
			if ( $options['debugMode'] ?? false ) {
				$returnInfo['sqlI'] = $sqlI;
			}

			if ( $pSession->status > 0 ) {
				$disconnect = __( 'Session was already ended.', 'ppv-live-webcams' );
			}

			// balance info

			// estimate pending balance
			$balancePending = self::balance( $session->uid, true, $options );

			$returnInfo['time'] = max( 0, min( $pSession->pedate, $pSession->cedate ) - max( $pSession->psdate, $pSession->csdate ) );

			$returnInfo['cost']           = $cost;
			$returnInfo['earn']           = $earn;
			$returnInfo['other']           = $other;

			$returnInfo['balance']        = number_format( $balance, 2, '.', ''  );
			$returnInfo['balancePending'] = number_format( $balancePending, 2, '.', ''  );

			$credits_info = '';
			if ( $cost > 0 ) {
				$credits_info .= $cost . htmlspecialchars( $options['currency'] ) . '/';
			}
			$credits_info .= $balance . htmlspecialchars( $options['currency'] );
			if ( $balancePending ) {
				$credits_info .= ' (' . $balancePending . htmlspecialchars( $options['currency'] ) . ')';
			}
			$returnInfo['balanceInfo'] = $credits_info;

			if ( $disconnect ?? false ) {
				self::notificationMessage( $disconnect, $session, 0 ); // send to public where user will be returned
			}

			// update main user session meta
			$userMeta['privateUpdate']      = $ztime;
			$userMeta['privateOther'] = $other;
			$userMeta['privateUID']         = $privateUID;
			$userMeta['privateCost']        = $cost;
			$userMeta['balance']            = $balance;
			$userMeta['balancePending']     = $balancePending;
			$userMeta['privateIsPerformer'] = $isPerformer2;

			$userMetaS                      = serialize( $userMeta );
			$sql = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $session->id);
			$wpdb->query($sql);

			$returnInfo['disconnect'] = $disconnect ?? false;
			return $disconnect ?? false;
		}


		static function sessionUpdate( $username = '', $room = '', $broadcaster = 0, $type = 1, $strict = 1, $updated = 1, $clean = 1, $options = null, $userID = 0, $postID = 0, $ip = '', $userMeta=[] ) {

			// called by rtmp session control, app ajax
			// return $session vw_vmls_sessions

			// strict = create new if not that type
			// updated = return updated session unless missing (otherwise return old for delta calculations)
			// $userID == -1 : autodetect on username as login

			if ( ! $username ) {
				return;
			}
			$ztime = time();

			if ( ! $options ) {
				$options = self::getOptions();
			}

			// detect userID if not provided
			if ( ! $userID || $userID == '-1' ) {
					$user = get_user_by( $options['userName'], $username );
				if ( $user ) {
					$userID = $user->ID;
				}

				if ( ! $user ) {
					$user = get_user_by( 'login', $username );
				}
				if ( $user ) {
					$userID = $user->ID;
				}
			}

			if ( ! $userID ) {
				$userID = 0;
			}
			
			
			if ( !is_array($userMeta) ) $userMeta = [];

			if ( ! $broadcaster ) {
				// viewer (client)

	
				// supports visitors
				return self::updateOnlineViewer( $username, $room, $postID, $type, '', $options, $userID, $strict, $ip, 1, $userMeta ); // for viewer/client

			} else {
				
				// performer
				global $wpdb;
				$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

				$cnd = " AND broadcaster <> '0'";
				if ( $strict ) {
					$cnd .= " AND `type`='$type'";
				}
				if ( $userID ) {
					$cnd .= " AND `uid` = '$userID'"; // if $userID provided, strict check on it
				}

				if ( ! $userID ) {
					$user = get_user_by( $options['performerName'], $username );
					if ( $user ) {
						$userID = $user->ID;
					}
				}
				if ( ! $postID ) {
					$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type=%s LIMIT 0,1", $room, $options['custom_post'] ) );
				}

				// online broadcasting session
				$sqlS    = $wpdb->prepare( "SELECT * FROM $table_sessions WHERE session=%s AND status=0 AND rid=%d $cnd ORDER BY edate DESC, id DESC LIMIT 1", $username, $postID );
				$session = $wpdb->get_row( $sqlS );

				if ( ! $session ) {
					$userMeta['createdBy'] = 'sessionUpdate';
					if ( $options['debugMode'] ?? false ) {
						$userMeta['notFound'] = $sqlS;
					}
					
					$userMetaS = serialize( $userMeta );

					$sql = $wpdb->prepare( "INSERT INTO `$table_sessions` ( `session`, `username`, `room`, `message`, `sdate`, `edate`, `status`, `type`, `uid`, `rid`, `ip`,`broadcaster`, `meta`) VALUES ( %s, %s, %s, '', %d, %d, 0, %d, %d, %d, %s, %d, %s )", $username, $username, $room, $ztime, $ztime, $type, $userID, $postID, $ip, $broadcaster, $userMetaS );

				} else {
					$sql = $wpdb->prepare( "UPDATE `$table_sessions` set edate=%d, room=%s, username=%s where id =%d", $ztime, $room, $username, $session->id );
				}
				$wpdb->query( $sql );
				
				if ( $postID ) {
					update_post_meta( $postID, 'edate', $ztime );
				}

				// update private shows
				if ( $userID ) {
					self::updatePrivateShow( $postID, $userID );
				}

				// update viewers
				self::updateViewers( $postID, $room, $options );

				$error = $wpdb->last_error;

				if ( $updated || ! $session ) {
					$session = $wpdb->get_row( $sqlS );
				}

				if ( ! $session ) {
					return 'ERROR: No session / ' . esc_html( $sqlS ) . ' / ' . esc_html($sql) . ' / MySQL Error: ' . $error;
				}
			}

			if ( $clean ) {
				self::billSessions();
			}

			return $session;
		}


		static function updatePrivateShow( $postID, $userID ) {
			self::billSessions();

			global $wpdb;
			$table_private = $wpdb->prefix . 'vw_vmls_private';

			// update perfomer private show status (present in private chats)
			$shows = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) as no FROM `$table_private` WHERE status=%d AND psdate > %d AND csdate > %d AND rid=%d AND pid=%d", 0, 0, 0, $postID, $userID ) );
			if ( $shows ) {
				$shows = 1;
			} else {
				$shows = 0;
			}
			update_post_meta( $postID, 'privateShow', $shows );
		}


		// ! Watcher Online Status for App, AJAX chat, session control
		static function updateOnlineViewer( $username, $room, $postID = 0, $type = 2, $current_user = '', $options = '', $userID = 0, $strict = 1, $ip = '', $returnSession = 0, $userMeta = [] ) {
			 // this should only be called for viewer (client) not performer!
			// $type: 1 = flash full, 2 = html5 chat, 3 = flash video, 4 = html5 video, 5 = voyeur flash, 6 = voyeur html5
			// 7=webrtc performer, 8=webrtc viewer
			// 9=external broadcaster, 10=external viewer
			// 11 h5v app

			// returns $disconnect string unless $returnSession

			if ( ! $room && ! $postID ) {
				return; // no room, no update
			}

			$disconnect = '';
			$s     = $u = $username;
			$r     = $room;
			$ztime = time();

			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}

			if ( ! $userID ) {
				if ( ! $current_user ) {
					$current_user = wp_get_current_user();
				}

				$uid = 0;
				if ( $current_user ) {
					$uid = $current_user->ID;
				}
			} else {
				$uid = $userID;
			}

			global $wpdb;
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

			if ( ! $postID ) {
				$postID = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s and post_type=%s LIMIT 0,1', $room, $options['custom_post'] ) );
			}

			$isPerformer = self::isPerformer( $userID, $postID ); //performer previewing own video
			if ($isPerformer) return ''; //no updates for performer preview

			// retrieve room info

			$groupCPM     = get_post_meta( $postID, 'groupCPM', true );
			$performer    = get_post_meta( $postID, 'performer', true );
			$sessionStart = get_post_meta( $postID, 'sessionStart', true );
			$checkin      = get_post_meta( $postID, 'checkin', true );
			$privateShow  = get_post_meta( $postID, 'privateShow', true );


			$groupMode = get_post_meta( $postID, 'groupMode', true );
			if ( ! $groupMode ) {
				$groupMode = 'Free';
			}

			$roomInterface = get_post_meta( $postID, 'roomInterface', true );

			// special user mode
			$userMode = 'chat';

			$groupParameters = get_post_meta( $postID, 'groupParameters', true );


			// 2way
			if ( isset($groupParameters['2way']) && $groupParameters['2way'] ) {
				$mode2way = get_post_meta( $postID, 'mode2way', true );

				if ( is_array( $mode2way ) ) {
					$m2update = false;

					if ( array_key_exists( $uid, $mode2way ) ) {
						$mode2way[ $uid ] = $ztime;
						$groupCPM         = $groupParameters['cpm2'];
						$m2update         = true;
						$userMode         = '2way';
					}

					foreach ( $mode2way as $key => $value ) {
						if ( $ztime - $value > $options['onlineTimeout'] ) {
							unset( $mode2way[ $key ] );
							$m2update = true;
						}
					}
					if ( $m2update ) {
						update_post_meta( $postID, 'mode2way', $mode2way );
					}
				}
			}

			// voyeur
			// is in voyeur mode (list in room meta)
			if ( $groupParameters ) {
				if ( is_array( $groupParameters ) ) {
					if ( array_key_exists( 'voyeur', $groupParameters ) ) {
						if ( $groupParameters['voyeur'] ) {
							$mvupdate = false;
							$modeVoyeur = get_post_meta( $postID, 'modeVoyeur', true );
							if ( ! is_array( $modeVoyeur ) ) {
								$modeVoyeur = array();
							}

							if ( array_key_exists( $uid, $modeVoyeur ) ) {
								$isVoyeur           = 1;
								$userMode           = 'voyeur';
								$groupCPM           = $groupParameters['cpmv'];
								$modeVoyeur[ $uid ] = $ztime;
								$mvupdate           = true;
							}

							foreach ( $modeVoyeur as $key => $value ) {
								if ( $ztime - $value > $options['onlineTimeout'] ) {
									unset( $modeVoyeur[ $key ] );
									$mvupdate = true;
								}
							}

							if ( $mvupdate ) {
								update_post_meta( $postID, 'modeVoyeur', $modeVoyeur ); // update voyeur list in room meta
							}
						}
					}
				}
			}

			// room options for this session type
			if ( !$isPerformer && $groupCPM ) {
				$rmode = 1;
			} else {
				$rmode = 0;
			}

					$roomOptions = array(
						'performer'     => $performer,
						'roomInterface' => $roomInterface,
						'cpm'           => $groupCPM,
						'userMode'      => $userMode,
						'groupMode'     => $groupMode,
						'sessionStart'  => $sessionStart,
						'checkin'       => $checkin,
					);

					$roptions = serialize( $roomOptions );

					$redate = intval( get_post_meta( $postID, 'edate', true ) );

					// strict mode
					$cnd = '';
					if ( $strict ) {
						$cnd = " AND `type`='$type'";
					}

					// if in paid mode and performer goes in private: switch to free group session (except for voyeur mode)
					if ( $rmode && $privateShow && ! $isVoyeur ) {
						$roomOptions['cpm'] = 0;
						$rmode              = 0;

						// close previous paid session if any
						$sqlU = $wpdb->prepare("UPDATE `$table_sessions` SET status='1' WHERE session=%s AND status='0' AND room=%s $cnd LIMIT 1", $s, $room);
						$wpdb->query( $sqlU );
					}

					// to consider: exit if new group session with different mode was started
					// if ($rmode != $session->rmode) $disconnect = __("Performer started a different room type: current session type ended. Please re-enter!","ppv-live-webcams");

					// create or update session
					// status: 0 current, 1 closed, 2 billed

					if ( $uid ) {
						$cfind = "uid = '$uid'";
					} else {
						$cfind = "session='$s'";
					}

					$sqlS    = "SELECT * FROM `$table_sessions` WHERE $cfind AND status='0' AND room='$room' $cnd AND rmode='$rmode' ORDER BY edate DESC, id DESC LIMIT 1";
					$session = $wpdb->get_row( $sqlS );

					if ( ! $ip ) {
						$clientIP = self::get_ip_address(); // detect ip if not provided ($ip required on session control)
					} else {
						$clientIP = $ip;
					}

					if ( ! $session ) {

						if ( $ztime - $redate > intval( $options['onlineTimeout'] ) ) {
							$rsdate = 0; // performer offline
						} else {
							$rsdate = $redate; // performer online: mark room start date
						}

						$userMeta['createdBy'] = 'updateOnlineViewer';
						$userMeta['isPerformer'] = $isPerformer;

						if ( $options['debugMode'] ?? false ) {
							$userMeta['notFound'] = $sqlS;
						}
						// $userMeta['balance'] =  self::balance($userID, true, $options);

						if ( isset($session) ) if ( self::isModerator($session->uid, $options, $current_user) ) $userMeta['moderator'] = 1;


						$userMetaS = serialize( $userMeta );

						$sql = $wpdb->prepare(
							"INSERT INTO `$table_sessions` ( `session`, `username`, `uid`, `room`, `rid`, `roptions`, `rsdate`, `redate`, `rmode`, `message`, `sdate`, `edate`, `status`, `type`, `ip`, `broadcaster`, `meta`) VALUES ( %s, %s, %d, %s, %d, %s, %d, %d, %d, '', %d, %d, 0, %d, %s, 0, %s )",
							$s, $u, $uid, $r, $postID, $roptions, $rsdate, $redate, $rmode, $ztime, $ztime, $type, $clientIP, $userMetaS
						);
						$wpdb->query( $sql );

						$session = $wpdb->get_row( $sqlS );
					} else {
						$id = $session->id;

						// performer was offline and came online: update room start time (rsdate)
						if ( $session->rsdate == 0 && $redate > $session->sdate ) {
							$rsdate = $redate;
						} else {
							$rsdate = $session->rsdate; // keep unchanged (0 or start time)
						}

						$sql = $wpdb->prepare("UPDATE `$table_sessions` SET edate=%d, rsdate=%d, redate=%d, roptions=%s WHERE id=%d LIMIT 1", $ztime, $rsdate, $redate, $roptions, $id);
						$wpdb->query( $sql );
					}

					// get $userMeta
					if ( $session->meta ) {
						$userMeta = unserialize( $session->meta );
					}
					if ( ! is_array( $userMeta ) ) {
						$userMeta = array();
					}
					$updateMeta = 0;

					if ($isPerformer) return ''; //no limitations for performer preview

					//suspended room
					if ( self::roomSuspended( $postID, $options ) ) $disconnect = __( 'This room was suspended.', 'ppv-live-webcams' );

					// check if client banned
					$bans = get_post_meta( $postID, 'bans', true );
					if ( $bans ) {
						if ( is_array( $bans ) ) {

							// clean expired bans
							foreach ( $bans as $key => $ban ) {
								if ( $ban['expires'] < time() ) {
									unset( $bans[ $key ] );
									$bansUpdate = 1;
								}
							}
							if ( $bansUpdate ) {
								update_post_meta( $postID, 'bans', $bans );
							}

							foreach ( $bans as $ban ) {
								if ( $clientIP == $ban['ip'] || ( $uid && $uid == $ban['uid'] ) ) {
									$disconnect = __( 'You are banned from accessing this room!', 'ppv-live-webcams' );
								}
							}
						}
					}

					
					$balance = self::balance( $uid ); // use complete balance to avoid double amount checking

					// billing and limitations
					if ( $groupCPM ) {

						$cost    = self::clientGroupCost( $session, $groupCPM, $sessionStart );

						$ppvMinInShow = self::balanceLimit( $groupCPM, 2, $options['ppvMinInShow'], $options );

						if ( $cost > 0 ) {
							if ( $cost + $ppvMinInShow > $balance ) {
								$disconnect = __( 'Not enough funds left for client to continue group chat session.', 'ppv-live-webcams' );
							}
						}

						if ( ! $uid ) {
							$disconnect = __( 'Only registered and logged in users can access paid sessions.', 'ppv-live-webcams' );
						}

						$userMeta['balance']      = $balance;
						$userMeta['cost']         = $cost;
						$userMeta['groupCPM']     = $groupCPM;
						$userMeta['ppvMinInShow'] = $ppvMinInShow;
						$updateMeta               = 1;

					} else // free mode limits
					{
						if ( $uid ) {
							$cnd = "uid='$uid'";
						} else {
							if ( ! $clientIP ) {
								$clientIP = self::get_ip_address();
							}
							$cnd = "ip='$clientIP' AND uid='0'";
						}

						$h24      = time() - 86400;
						$sqlC     = $wpdb->prepare("SELECT SUM(edate-sdate) FROM `$table_sessions` WHERE $cnd AND broadcaster='0' AND sdate > %d", $h24);
						$freeTime = $wpdb->get_var( $sqlC );

						if ( $uid ) {
							if ( $freeTime > $options['freeTimeLimit'] && $options['freeTimeLimit'] > 0 ) {

							if ( !$options['freeTimeBalance'] ) $disconnect = __( 'Free chat daily time limit reached: You can only access paid group rooms today.', 'ppv-live-webcams' ) . ' (' . $freeTime . 's > ' . $options['freeTimeLimit'] . 's ' . $cnd . ')';
												elseif ( $balance < $options['freeTimeBalance'] ) $disconnect = __( 'Free chat daily time limit reached. You get unlimited free chat time if you add a minimum balance.', 'ppv-live-webcams' ) . ' (' . $options['freeTimeBalance'] . $options['currency'] . ')';
							}
						}
						if ( ! $uid ) {
							if ( $freeTime > $options['freeTimeLimitVisitor'] && $options['freeTimeLimitVisitor'] > 0 ) {
								$disconnect = __( 'Free chat daily visitor time limit reached: Register and login for more chat time today!', 'ppv-live-webcams' );
							}
						}
					}

					if ( isset( $disconnect ) ) {
						$userMeta['disconnect'] = $disconnect;
						$updateMeta             = 1;
					}

					// update session info
					if ( $updateMeta ) {
						$userMeta['updatedBy'] = 'updateOnlineViewer';
						$userMeta['isPerformer'] = $isPerformer;
						$userMetaS             = serialize( $userMeta );
						$sql = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d LIMIT 1", $userMetaS, $session->id);
						$wpdb->query( $sql );
					}

					if ( $returnSession ) {
						$session = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_sessions` WHERE id = %d", $session->id ) );
						return $session;
					}

					return $disconnect;

		}


		// ! AJAX HTML Chat
		function vmls_htmlchat_callback() {
			$options = get_option( 'VWliveWebcamsOptions' );
			// output clean
			ob_clean();

			// Handling the supported tasks:

			global $wpdb;
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';
			$table_chatlog  = $wpdb->prefix . 'vw_vmls_chatlog';

			$room   = sanitize_file_name( $_GET['room'] );
			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type=%s LIMIT 0,1", $room, $options['custom_post'] ) );
			if ( ! $postID ) {
				throw new Exception( 'HTML Chat: Room not found: ' . esc_html( $room) );
			}

			// user
			$username    = '';
			$user_id     = 0;
			$isPerformer = 0;

			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();

				$isPerformer = self::isAuthor( $postID ); // is current user performer?

				if ( isset( $current_user ) ) {
					$user_id = $current_user->ID;

					$userName = $options['userName'];
					if ( ! $userName ) {
						$userName = 'user_nicename';
					}
					if ( $current_user->$userName ) {
						$username = urlencode( sanitize_file_name( $current_user->$userName ) );
					}
				}
			} else {
				if ( $_COOKIE['htmlchat_username'] ) {
					$username = sanitize_text_field( $_COOKIE['htmlchat_username'] );
				} else {
					$username = 'H_' . base_convert( time() % 36 * rand( 0, 36 * 36 ), 10, 36 );
					setcookie( 'htmlchat_username', $username );
				}
			}

			$ztime = time();

			switch ( $_GET['task'] ) {

				// ! tips ajax
				case 'getBalance':
					$balance = 0;
					if ( is_user_logged_in() ) {
						$balance = self::balance( $current_user->ID, true, $options ); // get live balance (preview)
					}

					$response = array(
						'balance' => $balance,
					);

					break;

				case 'sendTip':
					$error = '';
					if ( ! isset( $current_user ) ) {
						$error = 'Login is required to access balance and tip!';
					}

					if ( $options['tipCooldown'] ?? false ) {
						$lastTip = intval( get_user_meta( $current_user->ID, 'vwTipLast', true ) );
						if ( $lastTip + $options['tipCooldown'] > time() ) {
							$error = 'Already sent tip recently!';
						}
					}

					$message = sanitize_text_field( $_POST['label'] );
					$amount  = intval( $_POST['amount'] );
					$note    = sanitize_text_field( $_POST['note'] );
					$sound   = sanitize_text_field( $_POST['sound'] );
					$image   = sanitize_text_field( $_POST['image'] );

					$meta          = array();
					$meta['sound'] = $sound;
					$meta['image'] = $image;
					$metaS         = serialize( $meta );

					if ( ! $message ) {
						$error = 'No message!';
					}

					if ( $error ) {
						$response = array(
							'status'   => 0,
							'insertID' => 'error',
							'success'  => 0,
							'error'    => $error,
						);

					} else {
						$message = preg_replace( '/([^\s]{12})(?=[^\s])/', '$1' . '<wbr>', $message ); // break long words <wbr>:Word Break Opportunity
						$message = "<I>$message</I>"; // mark system message for tip

						$private = 0;

						// msg type: 1 flash, 2 web ext, 3 notification (own)
						$sql = $wpdb->prepare(
							"INSERT INTO `$table_chatlog` ( `username`, `room`, `message`, `mdate`, `type`, `meta`, `user_id`, `private_uid`) VALUES (%s, %s, %s, %d, %d, %s, %d, %d)",
							$username, $room, $message, $ztime, 2, $metaS, $user_id, $private
						);
						$wpdb->query( $sql );

						$response = array(
							'status'   => 1,
							'insertID' => $wpdb->insert_id,
						);

						// also update chat log file
						if ( $message ) {

							$message = strip_tags( $message, '<p><a><img><font><b><i><u>' );

							$message = date( 'F j, Y, g:i a', $ztime ) . " <b>$username</b>: $message";

							// generate same private room folder for both users
							if ( $private ) {
								if ( $private > $session ) {
									$proom = $session . '_' . $private;
								} else {
									$proom = $private . '_' . $session;
								}
							}

							$dir = $options['uploadsPath'];
							if ( ! file_exists( $dir ) ) {
								mkdir( $dir );
							}

							$dir .= "/$room";
							if ( ! file_exists( $dir ) ) {
								mkdir( $dir );
							}

							if ( $proom ) {
								$dir .= "/$proom";
								if ( ! file_exists( $dir ) ) {
									mkdir( $dir );
								}
							}

							$day = date( 'y-M-j', time() );

							$dfile = fopen( $dir . "/Log$day.html", 'a' );
							fputs( $dfile, $message . '<BR>' );
							fclose( $dfile );
						}

						// tip

						$balance = self::balance( $current_user->ID, true, $options );

						$response['success']         = 1;
						$response['balancePrevious'] = $balance;
						$response['postID']          = $postID;
						$response['userID']          = $current_user->ID;
						$response['amount']          = $amount;

						if ( $amount > $balance ) {
							$response['success'] = 0;
							$response['error']   = 'Tip amount greater than balance!';
							$response['balance'] = $balance;
						} else {

							$ztime = time();

							// client cost
							$paid = number_format( $amount, 2, '.', '' );
							self::transaction( 'ppv_tip', $current_user->ID, - $paid, 'Tip for <a href="' . self::roomURL( $room ) . '">' . $room . '</a>. (' . $label . ')', $ztime );
							$response['paid'] = $paid;

							// performer earning
							$post     = get_post( $postID );
							$received = number_format( $amount * $options['tipRatio'], 2, '.', '' );
							self::transaction( 'ppv_tip_earning', $post->post_author, $received, 'Tip from ' . $username . ' (' . $label . ')', $ztime );

							// save last tip time
							update_user_meta( $current_user->ID, 'vwTipLast', time() );

							$response['broadcaster'] = $post->post_author;
							$response['received']    = $received;

							// update balance and report
							$response['balance'] = self::balance( $current_user->ID, true, $options );

						}
					}

					break;

				// htmlchat
				case 'checkLogged':
					$response = array( 'logged' => false );

					if ( isset( $current_user ) ) {
						$response['logged'] = true;

						$response['loggedAs'] = array(
							'name'   => $username,
							'avatar' => get_avatar_url( $current_user->ID ),
							'userID' => $current_user->ID,
						);

					}

					if ( ! $isPerformer ) {
						$disconnected = self::updateOnlineViewer( $username, $room );
					}

					if ( $disconnected ) {
						$response['disconnect'] = $disconnected;
						$response['logged']     = false;
					}

					break;

				case 'submitChat':
					// $response = Chat::submitChat();

					if ( ! isset( $current_user ) ) {
						throw new Exception( 'You are not logged in!' );
					}

					$message = sanitize_text_field( $_POST['chatText'] );
					$message = preg_replace( '/([^\s]{12})(?=[^\s])/', '$1' . '<wbr>', $message ); // break long words <wbr>:Word Break Opportunity

					$private = 0; // htmlchat only public mode
					$sql = $wpdb->prepare(
						"INSERT INTO `$table_chatlog` ( `username`, `room`, `message`, `mdate`, `type`, `user_id`, `private_uid`) VALUES ( %s, %s, %s, %d, %d, %d, %d )",
						$username, $room, $message, $ztime, 2, $user_id, $private
					);
					$wpdb->query( $sql );

					$response = array(
						'status'   => 1,
						'insertID' => $wpdb->insert_id,
					);

					// also update chat log file
					if ( $message ) {

						$message = strip_tags( $message, '<p><a><img><font><b><i><u>' );

						$message = date( 'F j, Y, g:i a', $ztime ) . " <b>$username</b>: $message";

						// generate same private room folder for both users
						if ( $private ) {
							if ( $private > $session ) {
								$proom = $session . '_' . $private;
							} else {
								$proom = $private . '_' . $session;
							}
						}

						$dir = $options['uploadsPath'];
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}

						$dir .= "/$room";
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}

						if ( $proom ) {
							$dir .= "/$proom";
							if ( ! file_exists( $dir ) ) {
								mkdir( $dir );
							}
						}

						$day = date( 'y-M-j', time() );

						$dfile = fopen( $dir . "/Log$day.html", 'a' );
						fputs( $dfile, $message . '<BR>' );
						fclose( $dfile );
					}

					break;

				case 'getUsers':
					// old session cleanup

					// close sessions
					$closeTime = time() - max( intval( $options['ppvCloseAfter'] ), 70 ); // > client statusInterval
					$sql       = $wpdb->prepare("UPDATE `$table_sessions` SET status = 1 WHERE status = 0 AND edate < %d", $closeTime);
					$wpdb->query( $sql );

					// $response = Chat::getUsers();

					$users = array();

					// type 5,6 voyeur: do not show
					$sql      = $wpdb->prepare("SELECT * FROM `$table_sessions` WHERE room=%s AND status=%d AND type < %d", $room, 0, 5);
					$userRows = $wpdb->get_results( $sql );

					if ( $wpdb->num_rows > 0 ) {
						foreach ( $userRows as $userRow ) {
							$user         = array();
							$user['name'] = $userRow->session;

							// avatar
							$uid = $userRow->user_id;
							if ( ! $uid ) {
								$wpUser = get_user_by( $userName, $userRow->session );
								if ( ! $wpUser ) {
									$wpUser = get_user_by( 'login', $chatRow->username );
								}
								$uid = $wpUser->ID;
							}

							$user['avatar'] = get_avatar_url( $uid );

							$users [] = $user;
						}
					}
					$response = array(
						'users' => $users,
						'total' => count( $userRows ),
					);

					break;

				case 'getChats':
					if ( ! $isPerformer ) {
						$disconnect = self::updateOnlineViewer( $username, $room );
					}

					if ( ! $disconnect ) {
						// clean old chat logs

						$chatlog = intval($options['chatlog'] ?? 900);
						if (!$chatlog) $chatlog = 900;

						
						$closeTime = time() - $chatlog; // only keep for 15min
						$sql       = $wpdb->prepare("DELETE FROM `$table_chatlog` WHERE mdate < %d", $closeTime);
						$wpdb->query( $sql );

						// retrieve only messages since user came online
						$sdate = 0;
						if ( $session ) {
							$sdate = $session->sdate;
						}

						$chats = array();

						$lastID = intval( $_GET['lastID'] );

						$cndNotification = $wpdb->prepare("AND (type < %d OR (type=%d AND user_id=%d AND username=%s))", 3, 3, $session->uid, $session->username); // chat message or own notification (type 3)

						$sql = $wpdb->prepare("SELECT * FROM `$table_chatlog` WHERE room=%s $cndNotification AND private_uid = %d AND id > %d AND mdate > %d ORDER BY mdate DESC LIMIT 0,20", $room, 0, $lastID, $sdate);
						$sql = "SELECT * FROM ($sql) items ORDER BY mdate ASC";

						$chatRows = $wpdb->get_results( $sql );

						if ( $wpdb->num_rows > 0 ) {
							foreach ( $chatRows as $chatRow ) {
								$chat = array();

								if ( $chatRow->meta ) {
									$meta = unserialize( $chatRow->meta );

									if ( $meta['sound'] ) {
										$chat['sound'] = $meta['sound'];
									}
									if ( $meta['image'] ) {
										$chat['image'] = $meta['image'];
									}
								}

								$chat['id']     = $chatRow->id;
								$chat['author'] = $chatRow->username;
								$chat['text']   = $chatRow->message;

								$chat['time'] = array(
									'hours'   => gmdate( 'H', $chatRow->mdate ),
									'minutes' => gmdate( 'i', $chatRow->mdate ),
								);

								// avatar
								$uid = $chatRow->user_id;
								if ( ! $uid ) {
									$wpUser = get_user_by( $userName, $chatRow->username );
									if ( ! $wpUser ) {
										$wpUser = get_user_by( 'login', $chatRow->username );
									}
									$uid = $wpUser->ID;
								}

								$chat['avatar'] = get_avatar_url( $uid );

								$chats[] = $chat;
							}
						}

						$response = array( 'chats' => $chats );
					} else {
						$response = array(
							'chats'      => array(),
							'disconnect' => $disconnect,
						);

					}

					break;

				default:
					throw new Exception( 'HTML Chat: Wrong task' );
			}

			echo json_encode( $response );

			die();
		}


		// ! tools
		static function fixPath( $p ) {

			// adds ending slash if missing

			// $p=str_replace('\\','/',trim($p));
			return ( substr( $p, -1 ) != '/' ) ? $p .= '/' : $p;
		}


		static function path2stream( $path, $withExtension = true, $withPrefix = true ) {
			$options = get_option( 'VWliveWebcamsOptions' );

			$stream = substr( $path, strlen( $options['streamsPath'] ) );
			if ( $stream[0] == '/' ) {
				$stream = substr( $stream, 1 );
			}

			if ( $withPrefix ) {
				$ext    = pathinfo( $stream, PATHINFO_EXTENSION );
				$prefix = $ext . ':';
			} else {
				$prefix = '';
			}

			if ( ! file_exists( $options['streamsPath'] . '/' . $stream ) ) {
				return '';
			} elseif ( $withExtension ) {
				return $prefix . $stream;
			} else {
				return $prefix . pathinfo( $stream, PATHINFO_FILENAME );
			}
		}


		static function stream2path( $stream ) {

			$options = get_option( 'VWliveWebcamsOptions' );

			// mp4:
			if ( strstr( $stream, ':' ) ) {
				$stream = substr( $stream, strpos( $stream, ':' ) + 1 );
			}
			$path = $options['streamsPath'] . '/' . $stream;

			return $path;
		}


		static function varSave( $path, $var ) {
			file_put_contents( $path, serialize( $var ) );
		}


		static function varLoad( $path ) {
			if ( ! file_exists( $path ) ) {
				return false;
			}

			return unserialize( file_get_contents( $path ) );
		}


		static function stringSave( $path, $var ) {
			file_put_contents( $path, $var );
		}


		static function stringLoad( $path ) {
			if ( ! file_exists( $path ) ) {
				return false;
			}

			return file_get_contents( $path );
		}


		// ! Playlist AJAX handler

		static function updatePlaylist( $stream, $active = true ) {
			// updates playlist for channel $stream in global playlist
			if ( ! $stream ) {
				return;
			}

			$options = get_option( 'VWliveWebcamsOptions' );

			$uploadsPath = $options['uploadsPath'];
			if ( ! file_exists( $uploadsPath ) ) {
				mkdir( $uploadsPath );
			}
			$playlistPathGlobal = $uploadsPath . '/playlist_global.txt';
			if ( ! file_exists( $playlistPathGlobal ) ) {
				self::varSave( $playlistPathGlobal, array() );
			}

			$upath = $uploadsPath . "/$stream/";
			if ( ! file_exists( $upath ) ) {
				mkdir( $upath );
			}
			$playlistPath = $upath . 'playlist.txt';
			if ( ! file_exists( $playlistPath ) ) {
				self::varSave( $playlistPath, array() );
			}

			$playlistGlobal = self::varLoad( $playlistPathGlobal );
			$playlist       = self::varLoad( $playlistPath );

			if ( $active ) {
				$playlistGlobal[ $stream ] = $playlist;
			} else {
				unset( $playlistGlobal[ $stream ] );
			}

			self::varSave( $playlistPathGlobal, $playlistGlobal );

			self::updatePlaylistSMIL();
		}


		static function updatePlaylistSMIL() {
			$options = get_option( 'VWliveWebcamsOptions' );

			// ! update Playlist SMIL
			$streamsPath = self::fixPath( $options['streamsPath'] );
			$smilPath    = $streamsPath . 'playlist.smil';

			$smilCode .= <<<HTMLCODE
<smil>
    <head>
    </head>
    <body>

HTMLCODE;

			if ( $options['playlists'] ?? false ) {

				$uploadsPath = $options['uploadsPath'];
				if ( ! file_exists( $uploadsPath ) ) {
					mkdir( $uploadsPath );
				}
				$playlistPathGlobal = $uploadsPath . '/playlist_global.txt';
				if ( ! file_exists( $playlistPathGlobal ) ) {
					self::varSave( $playlistPathGlobal, array() );
				}
				$playlistGlobal = self::varLoad( $playlistPathGlobal );

				$streams = array_keys( $playlistGlobal );
				foreach ( $streams as $stream ) {
					$smilCode .= '<stream name="' . $stream . '"></stream>
				';
				}

				foreach ( $streams as $stream ) {
					foreach ( $playlistGlobal[ $stream ] as $item ) {
						$vids = 0;

						$smilCodeV = '';
						if ( $item['Videos'] ) {
							if ( is_array( $item['Videos'] ) ) {
								foreach ( $item['Videos'] as $video ) {
									if ( file_exists( self::stream2path( $video['Video'] ) ) ) {
										$smilCodeV .= '
		<video src="' . $video['Video'] . '" start="' . $video['Start'] . '" length="' . $video['Length'] . '"/>';
										$vids++;
									}
								}
							}
						};

						if ( $vids ) {
							$smilCode .= '
        <playlist name="' . $stream . $item['Id'] . '" playOnStream="' . $stream . '" repeat="' . ( $item['Repeat'] ? 'true' : 'false' ) . '" scheduled="' . $item['Scheduled'] . '">';
							$smilCode .= $smilCodeV;
							$smilCode .= '
		</playlist>';
						}
					}
				}
			}
			$smilCode .= <<<HTMLCODE

    </body>
</smil>
HTMLCODE;

			file_put_contents( $smilPath, $smilCode );
		}


		static function playlistsTroubleshoot( $verbose = false, $save = false ) {

			$options = get_option( 'VWliveWebcamsOptions' );

			if ( ! $options['playlists'] ) {
				return;
			}

			$uploadsPath = $options['uploadsPath'];
			if ( ! file_exists( $uploadsPath ) ) {
				mkdir( $uploadsPath );
			}
			$playlistPathGlobal = $uploadsPath . '/playlist_global.txt';
			if ( ! file_exists( $playlistPathGlobal ) ) {
				self::varSave( $playlistPathGlobal, array() );
			}
			$playlistGlobal = self::varLoad( $playlistPathGlobal );

			$streams = array_keys( $playlistGlobal );

			foreach ( $streams as $stream ) {
				foreach ( $playlistGlobal[ $stream ] as $item ) {
					$vids = 0;
					if ( $item['Videos'] ) {
						if ( is_array( $item['Videos'] ) ) {
							foreach ( $item['Videos'] as $video ) {
								if ( ! file_exists( self::stream2path( $video['Video'] ) ) ) {
									if ( $verbose ) {
										echo '<br>Video missing for ' . esc_html( $stream ) . ' - Stream: ' . esc_html( $video['Video'] ) . ' Path: ' . esc_html( self::stream2path( $video['Video'] ) );
									}
								} else {
									$vids++;
								}
							}
						}
					}

					if ( ! $vids ) {
						if ( $verbose ) {
							echo '<br>No videos found for ' . esc_html( $stream ) . ' : playlist will not be added.';
						}
					}
				}
			}
		}


		function vmls_playlist() {
			ob_clean();

			$postID = (int) $_GET['webcam'];

			if ( ! $postID ) {
				echo 'No webcam post ID provided!';
				die;
			}

			$channel = get_post( $postID );
			if ( ! $channel ) {
				echo 'Webcam post not found!';
				die;
			}

			$current_user = wp_get_current_user();

			// requires owner of performer
			if ( ! self::isAuthor( $postID ) ) {
				echo 'Access to playlist not permitted (different room owner)!';
				die;
			}

			$stream = sanitize_file_name( $channel->post_title );

			$options = get_option( 'VWliveWebcamsOptions' );

			$uploadsPath = $options['uploadsPath'];
			if ( ! file_exists( $uploadsPath ) ) {
				mkdir( $uploadsPath );
			}

			$upath = $uploadsPath . "/$stream/";
			if ( ! file_exists( $upath ) ) {
				mkdir( $upath );
			}

			$playlistPath = $upath . 'playlist.txt';

			if ( ! file_exists( $playlistPath ) ) {
				self::varSave( $playlistPath, array() );
			}

			switch ( $_GET['task'] ) {
				case 'list':
					$rows = self::varLoad( $playlistPath );

					// sort rows by order
					if ( count( $rows ) ) {
						// sort
						function cmp_by_order( $a, $b ) {

							if ( $a['Order'] == $b['Order'] ) {
								return 0;
							}
							return ( $a['Order'] < $b['Order'] ) ? -1 : 1;
						}

						usort( $rows, 'cmp_by_order' ); // sort

						// update Ids to match keys (order)
						$updated = 0;
						foreach ( $rows as $key => $value ) {
							if ( $rows[ $key ]['Id'] != $key ) {
								$rows[ $key ]['Id'] = $key;
								$updated            = 1;
							}
						}
						if ( $updated ) {
							self::varSave( $playlistPath, $rows );
						}
					}

					// Return result to jTable
					$jTableResult            = array();
					$jTableResult['Result']  = 'OK';
					$jTableResult['Records'] = $rows;
					print json_encode( $jTableResult );

					break;

				case 'videolist':
					$ItemId       = (int) $_GET['item'];
					$jTableResult = array();

					$playlist = self::varLoad( $playlistPath );

					if ( $schedule = $playlist[ $ItemId ] ) {
						if ( ! $schedule['Videos'] ) {
							$schedule['Videos'] = array();
						}

						// sort videos

						// sort rows by order
						if ( count( $schedule['Videos'] ) ) {

							// sort
							function cmp_by_order( $a, $b ) {

								if ( $a['Order'] == $b['Order'] ) {
									return 0;
								}
								return ( $a['Order'] < $b['Order'] ) ? -1 : 1;
							}

							usort( $schedule['Videos'], 'cmp_by_order' ); // sort

							// update Ids to match keys (order)
							$updated = 0;
							foreach ( $schedule['Videos'] as $key => $value ) {
								if ( $schedule['Videos'][ $key ]['Id'] != $key ) {
									$schedule['Videos'][ $key ]['Id'] = $key;
									$updated                          = 1;
								}
							}

							$playlist[ $ItemId ] = $schedule;
							if ( $updated ) {
								self::varSave( $playlistPath, $playlist );
							}
						}

						$jTableResult['Records'] = $schedule['Videos'];
						$jTableResult['Result']  = 'OK';
					} else {
						$jTableResult['Result']  = 'ERROR';
						$jTableResult['Message'] = "Schedule $ItemId not found!";
					}

					print json_encode( $jTableResult );
					break;

				case 'videoupdate':
					// delete then add new

					$playlist = self::varLoad( $playlistPath );
					$ItemId   = (int) $_POST['ItemId'];
					$Id       = (int) $_POST['Id'];

					$jTableResult = array();
					if ( $playlist[ $ItemId ] ) {

						// find and remove record with that Id
						foreach ( $playlist[ $ItemId ]['Videos'] as $key => $value ) {
							if ( $value['Id'] == $Id ) {
								unset( $playlist[ $ItemId ]['Videos'][ $key ] );
								break;
							}
						}

						self::varSave( $playlistPath, $playlist );
					}

				case 'videoadd':
					$playlist = self::varLoad( $playlistPath );
					$ItemId   = (int) $_POST['ItemId'];

					$jTableResult = array();
					if ( $schedule = $playlist[ $ItemId ] ) {
						if ( ! $schedule['Videos'] ) {
							$schedule['Videos'] = array();
						}

						$maxOrder = 0;
						$maxId    = 0;
						foreach ( $schedule['Videos'] as $item ) {
							if ( $item['Order'] > $maxOrder ) {
								$maxOrder = $item['Order'];
							}
							if ( $item['Id'] > $maxId ) {
								$maxId = $item['Id'];
							}
						}

						$item           = array();
						$item['Video']  = sanitize_text_field( $_POST['Video'] );
						$item['Id']     = (int) $_POST['Id'];
						$item['Order']  = (int) $_POST['Order'];
						$item['Start']  = (int) $_POST['Start'];
						$item['Length'] = (int) $_POST['Length'];

						if ( ! $item['Order'] ) {
							$item['Order'] = $maxOrder + 1;
						}
						if ( ! $item['Id'] ) {
							$item['Id'] = $maxId + 1;
						}

						$playlist[ $ItemId ]['Videos'][] = $item;

						self::varSave( $playlistPath, $playlist );

						$jTableResult['Result'] = 'OK';
						$jTableResult['Record'] = $item;
					} else {
						$jTableResult['Result']  = 'ERROR';
						$jTableResult['Message'] = "Schedule $ItemId not found!";
					}

					// Return result to jTable
					print json_encode( $jTableResult );

					break;

				case 'videoremove':
					$playlist = self::varLoad( $playlistPath );
					$ItemId   = (int) $_GET['item'];
					$Id       = (int) $_POST['Id'];

					$jTableResult = array();
					if ( $schedule = $playlist[ $ItemId ] ) {

						// find and remove record with that Id
						foreach ( $playlist[ $ItemId ]['Videos'] as $key => $value ) {
							if ( $value['Id'] == $Id ) {
								unset( $playlist[ $ItemId ]['Videos'][ $key ] );
								break;
							}
						}

						self::varSave( $playlistPath, $playlist );

						$jTableResult['Result']    = 'OK';
						$jTableResult['Remaining'] = $playlist[ $ItemId ]['Videos'];
					} else {
						$jTableResult['Result']  = 'ERROR';
						$jTableResult['Message'] = "Schedule $ItemId not found!";
					}

					// Return result to jTable
					print json_encode( $jTableResult );

					break;

				case 'source':
					// retrieve videos owned by user (from all channels)

					if ( is_plugin_active( 'video-share-vod/video-share-vod.php' ) ) {
						$optionsVSV        = get_option( 'VWvideoShareOptions' );
						$custom_post_video = $optionsVSV['custom_post'];
						if ( ! $custom_post_video ) {
							$custom_post_video = 'video';
						}
					} else {
						$custom_post_video = 'video';
					}

					// query
					$args = array(
						'post_type' => $custom_post_video,
						'author'    => $current_user->ID,
						'orderby'   => 'post_date',
						'order'     => 'DESC',
					);

					$postslist = get_posts( $args );
					$rows      = array();

					$jTableResult = array();

					if ( count( $postslist ) > 0 ) {
						foreach ( $postslist as $item ) {
							$row                = array();
							$row['DisplayText'] = $item->post_title;

							$video_id = $item->ID;

							// retrieve video stream
							$streamPath = '';
							$videoPath  = get_post_meta( $video_id, 'video-source-file', true );
							$ext        = pathinfo( $videoPath, PATHINFO_EXTENSION );

							// use conversion if available
							$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
							if ( $videoAdaptive ) {
								$videoAlts = $videoAdaptive;
							} else {
								$videoAlts = array();
							}

							foreach ( array( 'high', 'mobile' ) as $frm ) {
								if ( array_key_exists( $frm, $videoAlts ) ) {
									if ( $alt = $videoAlts[ $frm ] ) {
										if ( file_exists( $alt['file'] ) ) {
												$ext        = pathinfo( $alt['file'], PATHINFO_EXTENSION );
												$streamPath = self::path2stream( $alt['file'] );
												break;
										}
									}
								}
							};

							// user original
							if ( ! $streamPath ) {
								if ( in_array( $ext, array( 'flv', 'mp4', 'm4v' ) ) ) {
									// use source if compatible
									$streamPath = self::path2stream( $videoPath );
								}
							}

							$row['Value'] = $streamPath;
							$rows[]       = $row;
						}

						$jTableResult['Result']  = 'OK';
						$jTableResult['Options'] = $rows;
					} else {
						$jTableResult['Result']  = 'ERROR';
						$jTableResult['Message'] = "No video posts (type:$custom_post_video) found for current user. Add some videos first!";
					}

					// Return result to jTable

					print json_encode( $jTableResult );

					break;

				case 'update':
					// delete then create new
					$Id = (int) $_POST['Id'];

					$playlist = self::varLoad( $playlistPath );
					if ( ! is_array( $playlist ) ) {
						$playlist = array();
					}

					foreach ( $playlist as $key => $value ) {
						if ( $value['Id'] == $Id ) {
							unset( $playlist[ $key ] );
							break;
						}
					}

					self::varSave( $playlistPath, $playlist );

				case 'create':
					$playlist = self::varLoad( $playlistPath );
					if ( ! is_array( $playlist ) ) {
						$playlist = array();
					}

					$maxOrder = 0;
					$maxId    = 0;
					foreach ( $playlist as $item ) {
						if ( $item['Order'] > $maxOrder ) {
 							$maxOrder = $item['Order'];
						}
						if ( $item['Id'] > $maxId ) {
							$maxId = $item['Id'];
						}
					}

					$item              = array();
					$item['Id']        = (int) $_POST['Id'];
					$item['Video']     = sanitize_text_field( $_POST['Video'] );
					$item['Repeat']    = (int) $_POST['Repeat'];
					$item['Scheduled'] = sanitize_text_field( $_POST['Scheduled'] );
					$item['Order']     = (int) $_POST['Order'];
					if ( ! $item['Order'] ) {
						$item['Order'] = $maxOrder + 1;
					}
					if ( ! $item['Id'] ) {
						$item['Id'] = $maxId + 1;
					}
					if ( ! $item['Scheduled'] ) {
						$item['Scheduled'] = date( 'Y-m-j h:i:s' );
					}

					$playlist[ $item['Id'] ] = $item;

					self::varSave( $playlistPath, $playlist );

					// Return result to jTable
					$jTableResult           = array();
					$jTableResult['Result'] = 'OK';
					$jTableResult['Record'] = $item;
					print json_encode( $jTableResult );
					break;

				case 'delete':
					$Id = (int) $_POST['Id'];

					$playlist = self::varLoad( $playlistPath );
					if ( ! is_array( $playlist ) ) {
						$playlist = array();
					}

					foreach ( $playlist as $key => $value ) {
						if ( $value['Id'] == $Id ) {
							unset( $playlist[ $key ] );
							break;
						}
					}

					self::varSave( $playlistPath, $playlist );

					// Return result to jTable
					$jTableResult           = array();
					$jTableResult['Result'] = 'OK';
					print json_encode( $jTableResult );
					break;

				default:
					echo 'Action not supported!';
			}

			die;

		}


		static function label( $key, $default, $options ) {
			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}

			if ( ! $options['labels'] ) {
				return $default;
			}
			if ( ! is_array( $options['labels'] ) ) {
				return $default;
			}
			if ( ! array_key_exists( $key, $options['labels'] ) ) {
				return $default;
			}

			return $options['labels'][ $key ];
		}


		// string contains any term for list (ie. banning)
		static function containsAny( $name, $list ) {
			$items = explode( ',', $list );

			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					if ( stristr( $name, trim( $item ) ) ) {
						return $item;
					}
				}
			}

			return 0;
		}


		// if any element from array1 in array2
		static function any_in_array( $array1, $array2 ) {
			if ( ! is_array( $array1 ) ) {
				return false;
			}
			if ( ! is_array( $array2 ) ) {
				return false;
			}

			foreach ( $array1 as $value ) {
				if ( in_array( $value, $array2 ) ) {
					return true;
				}
			}

			return false;
		}


		// if any key matches any listing (csv); for
		static function inList( $keys, $data ) {
			if ( ! $keys ) {
				return 0;
			}
			if ( ! $data ) {
				return 0;
			}
			if ( strtolower( trim( $data ) ) == 'all' ) {
				return 1;
			}
			if ( strtolower( trim( $data ) ) == 'none' ) {
				return 0;
			}

			$list = explode( ',', strtolower( trim( $data ) ) );
			if ( in_array( 'all', $list ) ) {
				return 1;
			}

			foreach ( $keys as $key ) {
				foreach ( $list as $listing ) {
					if ( strtolower( trim( $key ) ) == trim( $listing ) ) {
						return 1;
					}
				}
			}

					return 0;
		}


		static function getCurrentURL() {
			$currentURL = home_url( add_query_arg( null, null ) );
			return $currentURL;
		}


		/**
		 * Retrieves the best guess of the client's actual IP address.
		 * Takes into account numerous HTTP proxy headers due to variations
		 * in how different ISPs handle IP addresses in headers between hops.
		 */
		static function get_ip_address() {
			$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' );
			foreach ( $ip_keys as $key ) {
				if ( array_key_exists( $key, $_SERVER ) === true ) {
					foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
						// trim for safety measures
						$ip = trim( $ip );
						// attempt to validate IP
						if ( self::validate_ip( $ip ) ) {
							return $ip;
						}
					}
				}
			}

			return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : false;
		}


		/**
		 * Ensures an ip address is both a valid IP and does not fall within
		 * a private network range.
		 */
		static function validate_ip( $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
				return false;
			}
			return true;
		}


		// ! room features
		static function roomFeatures() {
			return array(
				'videos'             => array(
					'name'        => 'Videos',
					'description' => 'Can upload and import videos, integrating Video Share VOD plugin. Roles also need to be configured in Video Share VOD plugin settings.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'pictures'           => array(
					'name'        => 'Pictures',
					'description' => 'Can upload and import pictures, integrating Picture Gallery plugin. Roles also need to be configured in Picture Gallery plugin settings.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'costPerMinute'      => array(
					'name'        => 'Cost per Minute',
					'description' => 'Can specify cost per minute for private shows. Replaces default show CPM.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'costPerMinuteGroup' => array(
					'name'        => 'Group Cost per Minute',
					'description' => 'Can specify cost per minute for group shows. Replaces default paid group CPM.',
					'installed'   => 1,
					'default'     => 'Super Admin, Administrator, Editor',
				),			
				/*'slots2way'          => array(
					'name'        => '2 Way Slots',
					'description' => 'Can specify 2 way slots for other participants to start webcam. Replaces default group 2 way slots.',
					'installed'   => 1,
					'default'     => 'Super Admin, Administrator, Editor',
				), */
				'uploadPicture'      => array(
					'name'        => 'Upload Room Picture',
					'description' => 'Can upload custom room picture.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'roomDescription'    => array(
					'name'        => 'Room Description',
					'description' => 'Can write description for room (profile, bio, schedule). Shows on cam page.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'roomLabel'          => array(
					'name'        => 'Room Label',
					'description' => 'Can define a room label. Shows in cam listings instead of name.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'roomBrief'          => array(
					'name'        => 'Room Brief',
					'description' => 'Can write brief for room. Shows in cam listings.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'banCountries'       => array(
					'name'        => 'Ban Countries',
					'description' => 'Can restrict access based on user country.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'roomTags'           => array(
					'name'        => 'Room Tags',
					'description' => 'Can write tags for room. Shows in cam listings.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'roomCategory'       => array(
					'name'        => 'Room Category',
					'description' => 'Can select a category.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'accessList'         => array(
					'name'        => 'Access List',
					'description' => 'Can specify list of user logins, roles, emails that can access the room (public chat). If disabled, users can access as configured in Client section.',
					'installed'   => 1,
					'default'     => 'None',
				),
				'accessPrice'        => array(
					'name'        => 'Access Price',
					'description' => 'Can setup a price for public room access. Uses MicroPayments plugin to control page access.',
					'type'        => 'number',
					'installed'   => 1,
					'default'     => 'All',
				),
				'accessPassword'     => array(
					'name'        => 'Access Password',
					'description' => 'Can specify a password to protect room page access.',
					'installed'   => 1,
					'default'     => 'Super Admin, Administrator, Editor',
				),
/*				'transcode'          => array(
					'name'        => 'Transcode',
					'description' => 'Shows transcoding interface with web broadcasting interface.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'logoHide'           => array(
					'name'        => 'Hide Logo',
					'description' => 'Hides logo from room video.',
					'installed'   => 1,
					'default'     => 'None',
				),
				'logoCustom'         => array(
					'name'        => 'Custom Logo',
					'description' => 'Can setup a custom logo. Overrides hide logo feature.',
					'installed'   => 1,
					'default'     => 'None',
				), 
				'schedulePlaylists'  => array(
					'name'        => 'Video Scheduler',
					'description' => 'Can schedule existing videos to play as if performer was live.',
					'installed'   => 1,
					'default'     => 'None',
				), 
				'private2way'        => array(
					'name'        => '2 Way Private Videochat',
					'description' => 'Can toggle 2 way videochat.',
					'installed'   => 1,
					'default'     => 'All',
				),
				'multicam'           => array(
					'name'        => 'Multiple Cameras',
					'description' => 'Can start multiple cameras. ',
					'installed'   => 1,
					'default'     => 'All',
				),*/
				'presentationMode'   => array(
					'name'        => 'Collaboration / Presentation Mode',
					'description' => 'Can toggle collaboration / presentation mode from room setup.',
					'installed'   => 1,
					'default'     => 'All',
				),
			);
		}


		static function delete_associated_media( $id, $unlink = false ) {

			$htmlCode = 'Cleanup: ';

			$media = get_children(
				array(
					'post_parent' => $id,
					'post_type'   => 'attachment',
				)
			);
			if ( empty( $media ) ) {
				return $htmlCode;
			}

			foreach ( $media as $file ) {

				if ( $unlink ) {
					$filename  = get_attached_file( $file->ID );
					$htmlCode .= " Removing $filename #" . $file->ID;
					if ( file_exists( $filename ) ) {
						unlink( $filename );
					}
				}

				wp_delete_attachment( $file->ID );
			}

			return $htmlCode;
		}


		function videowhisper_webcams_logout( $atts ) {
			// $pid = $options['p_videowhisper_webcams_logout'];

			$room    = sanitize_file_name( $_GET['room'] );
			$message = sanitize_textarea_field( $_GET['message'] );

			$options = get_option( 'VWliveWebcamsOptions' );

			$htmlCode = '<h3 class="ui header">' . __( 'You Were Disconnected from Chat Room', 'ppv-live-webcams' ) . '</H3>';

			switch ( $message ) {
				case __( 'You have been disconnected from server.', 'ppv-live-webcams' ):
				case __( 'Free daily time limit reached: Check paid rooms!', 'ppv-live-webcams' ):
				case __( 'Free daily visitor time limit reached: Register for more!', 'ppv-live-webcams' ):
				default:
					$htmlCode .= '<p>' . $message . '</p>';
			}

			return $htmlCode;
		}


		// ! usernames and meta in chat
		static function performerName( $user, $options ) {
			// returns performer name in room, for $user

			return sanitize_file_name( $user->user_nicename );

						$webcamName = $options['webcamName'];
			if ( ! $webcamName ) {
				$webcamName = 'user_nicename';
			}

			if ( $user->$webcamName ) {
				$name = $user->$webcamName;
			}
			if ( ! $name ) {
				$name = $user->user_nicename;
			}

			return sanitize_file_name( $name );
		}


		static function performerNameID( $id, $options ) {
			// returns performer name in room, for user $id

			$user = get_user_by( 'id', $id );
			if ( ! $user ) {
				return;
			}

			$webcamName = $options['webcamName'];
			if ( ! $webcamName ) {
				$webcamName = 'user_nicename';
			}

			if ( $user->$webcamName ) {
				$name = $user->$webcamName;
			}
			if ( ! $name ) {
				$name = $user->user_nicename;
			}

			return sanitize_file_name( $name );

		}


		static function clientName( $user, $options ) {

			return sanitize_file_name( $user->user_nicename );

			// returns client name in room, for $user

			$userName = $options['userName'];
			if ( ! $userName ) {
				$userName = 'user_nicename';
			}

			return sanitize_file_name( $user->$userName );
		}


		static function performerLink( $id, $options ) {

			$name = self::performerNameID( $id, $options );

			// if (!$options['performerProfile']) return $name;

			$user = get_userdata( $id );
			if ( ! $user ) {
				return '';
			}

			// buddyPress profile url
			$bp_url = '';
			if ( function_exists( 'bp_members_get_user_url' ) ) {
				$bp_url = bp_members_get_user_url( $id );
			}

			$url = $options['performerProfile'] ? $options['performerProfile'] . urlencode( $user->user_nicename ) : ( $bp_url ? $bp_url : get_author_posts_url( $id ) );

			// $url = $options['performerProfile'] ? $options['performerProfile'] .  urlencode($user->user_nicename) : get_author_posts_url($sqlRow->uid);

			return '<a href="' . $url . '"><i class="small user icon"></i>' . $name . '</a>';
		}


		// ! Dashboards


		// ! Follow
		function videowhisper_follow( $atts ) {
			// AJAX button to follow webcam, saves follower as webcam listing meta
		}


		function videowhisper_follow_list( $atts ) {
			// list followed channels based on videowhisper_webcams
		}


		static function detectLocation( $resolution = 'country', $ip = '', $options = null ) {
			// GeoIP2



			if (!$options) $options = self::getOptions();
			
			if ( !$options['geoIP'] ) return [];
			
			if ( ! $ip ) $ip = self::get_ip_address();

			$c = '';
	
			try {
				switch ( $resolution ) {

					case 'all': // array
						$res = array();
						$inf = '';					

						if ( $options['geoIP'] >= 1)
						if ( $ev = getenv( 'GEOIP_CONTINENT_CODE' ) ) {
							$res[] = esc_sql( htmlspecialchars( $ev ) );
						}

						if ( $options['geoIP'] >= 2)						
						if ( $ev = getenv( 'GEOIP_COUNTRY_NAME' ) ) {
							$res[] = esc_sql( htmlspecialchars( $ev ) );
						}
						
						if ( $options['geoIP'] >= 3)						
						if ( $ev = getenv( 'GEOIP_REGION_NAME' ) ) {
							$res[] = esc_sql( htmlspecialchars( $ev ) );
						}
						
						if ( $options['geoIP'] >= 4)						
						if ( $ev = getenv( 'GEOIP_CITY' ) ) {
							$res[] = esc_sql( htmlspecialchars( $ev ) );
						}
						
						if ( ! empty( $res ) ) return $res;
						
						if ( $options['geoIP'] == 2) return [ self::detectLocation('continent'),  self::detectLocation('country') ];
						if ( $options['geoIP'] == 1) return [ self::detectLocation('continent') ];
						
							if ( $options['geoIP'] >= 3) if ( function_exists( 'geoip_record_by_name' ) )  $inf = @geoip_record_by_name( $ip );		
																			
							if ( $inf ) {							
								if ( $options['geoIP'] >= 1)
								if ( $ev = $inf['continent_code'] ) {
									$res[] = esc_sql( htmlspecialchars( $ev ) );
								}
								
								if ( $options['geoIP'] >= 2)
								if ( $ev = $inf['country_name'] ) {
									$res[] = esc_sql( htmlspecialchars( $ev ) );
								}
								
								if ( $options['geoIP'] >= 3)
								if ( $ev = $inf['region'] ) {
									$res[] = esc_sql( htmlspecialchars( $ev ) );
								}
								
								if ( $options['geoIP'] >= 4)
								if ( $ev = $inf['city'] ) {
									$res[] = esc_sql( htmlspecialchars( $ev ) );
								}
								if ( ! empty( $res ) ) {
									return $res;
								}
							}
	

					break;

					case 'continent':
					
						if ( $options['geoIP'] < 1) return;
						
						if ( $ev = getenv( 'GEOIP_CONTINENT_CODE' ) ) {
							return $ev;
						}

						if ( function_exists( 'geoip_continent_code_by_name' ) ) {
						$c = esc_sql( geoip_continent_code_by_name( $ip ) );
						}
						if ( $c ) {
							return $c;
						}
						
						break;

					case 'country':
					
						if ( $options['geoIP'] < 2) return;

						if ( function_exists( 'geoip_country_name_by_name' ) ) {
							$c = esc_sql( geoip_country_name_by_name( $ip ) );
						}
						if ( $c ) {
							return $c;
						}

						if ( $ev = esc_sql( getenv( 'GEOIP_COUNTRY_NAME' ) ) ) {
							return $ev;
						}
						break;

					case 'region':
					
						if ( $options['geoIP'] < 3) return;

						if ( $ev = getenv( 'GEOIP_REGION_NAME' ) ) {
							return $ev;
						}

						if ( function_exists( 'geoip_region_name_by_code' ) ) {
						$c = esc_sql( geoip_region_name_by_code( $ip ) );
						}
						if ( $c ) {
							return $c;
						}
						

						break;

					case 'city':
					
						if ( $options['geoIP'] < 4) return;

						if ( function_exists( 'geoip_record_by_name' ) ) {
							$inf = @geoip_record_by_name( $ip );
							if ( $inf ) {
								return esc_sql( htmlspecialchars( $inf['city'] ) );
							}
						}

						if ( $ev = esc_sql( htmlspecialchars( getenv( 'GEOIP_CITY' ) ) ) ) {
							return $ev;
						}
						break;
				}
			} catch ( Exception $e ) {
				echo  'Exception: ' . esc_html( $e->getMessage() ) ;
				return '';
			}

			return '';
		}


		// ! Performer Registration and Login

		static function register_form() {
			$options = get_option( 'VWliveWebcamsOptions' );

			if ( ! $options['registrationFormRole'] ) {
				return;
			}

			$roles = array( $options['roleClient'], $options['rolePerformer'] );

			if ( $options['studios'] ) {
				$roles[] = $options['roleStudio']; // add studio if enabled
			}

			echo '<label for="role"> ' . __( 'Role', 'ppv-live-webcams' ) . '<br><select id="role" name="role" class="ui dropdown v-select">';
			foreach ( $roles as $role ) {
				// create role if missing
				if ( ! $oRole = get_role( $role ) ) {
					add_role( $role, ucwords( $role ), array( 'read' => true ) );
					$oRole = get_role( $role );
				}

				echo '<option value="' . esc_attr( $role ) . '">' . esc_html( ucwords( $oRole->name ) ) . '</option>';

			}
			echo '</select></label>';
		}


		static function user_register( $user_id, $password = '', $meta = array() ) {
			$options = get_option( 'VWliveWebcamsOptions' );
			if ( ! $options['registrationFormRole'] ) {
				return;
			}

			$userdata         = array();
			$userdata['ID']   = intval( $user_id );
			$userdata['role'] = sanitize_file_name( $_POST['role'] ?? 'subscriber');

			// restrict registration roles
			$roles = array( $options['roleClient'], $options['rolePerformer'] );

			if ( in_array( $userdata['role'], $roles ) ) {
				wp_update_user( $userdata );
			}
		}


		static function login_logo() {

			$options = self::getOptions();

			if ( $options['loginLogo'] ?? false ) {
				?>
	<style type="text/css">
		 #login h1 a, .login h1 a  {
			background-image: url(<?php echo esc_url_raw( $options['loginLogo'] ); ?>);
			background-size: 240px 80px;
			width: 240px;
			height: 80px;
		}
	</style>
				<?php
			}
		}


		static function login_redirect( $redirect_to, $request, $user ) {

			global $user;

			// wp_users & wp_usermeta
			// $user = get_userdata(get_current_user_id());

			if ( isset( $user->roles ) && is_array( $user->roles ) ) {
				// check for admins
				if ( in_array( 'administrator', $user->roles ) ) {
					// redirect them to the default place
					return $redirect_to;
				} else {

					$options = self::getOptions();

					// performer to dashboard
					if ( in_array( $options['rolePerformer'], $user->roles ) ) {
						$pid = $options['p_videowhisper_webcams_performer'];
						if ( $pid ) {
							return get_permalink( $pid );
						} else {
							return $redirect_to;
						}
					}

					// studio to dashboard
					if ( in_array( $options['roleStudio'], $user->roles ) ) {
						$pid = $options['p_videowhisper_webcams_studio'];
						if ( $pid ) {
							return get_permalink( $pid );
						} else {
							return $redirect_to;
						}
					}

					// client to webcams list
					if ( in_array( $options['roleClient'], $user->roles ) ) {
						$pid = $options['p_videowhisper_webcams'];
						if ( $pid ) {
							return get_permalink( $pid );
						} else {
							return $redirect_to;
						}
					}
				}
			} else {
				return $redirect_to;
			}
		}


		// ! Billing Integration: MyCred, WooWallet


		// room donations goal

		// progressive goals

		static function goalIndependent( $postID, $name, $add = 0, $options = null ) {
			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}
			if ( ! $postID ) {
				return 0; // goal is per post
			}

			$goals = get_post_meta( $postID, 'goals', true );

			foreach ( $goals as $ix => $goal ) {
				if ( $goal['name'] == $name ) {
					$goal['current']   += $add;
					$goal['cumulated'] += $add;

					if ( ! $goal['started'] ) {
						$goal['started'] = time(); // start on first gift
					}

					$goals[ $ix ] = $goal;

					update_post_meta( $postID, 'goals', $goals );

					return $goal;
				}
			}

			// otherwise add to regular goal
			self::goal( $postID, $add, $options );
		}

		static function goal( $postID, $add = 0, $options = null) {

			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}

			if ( ! $postID ) {
				return 0; // goal is per post
			}

			$goal  = get_post_meta( $postID, 'goal', true );
			$goals = get_post_meta( $postID, 'goals', true );

			$saveGoal = 0;

			if ( ! $goals ) {
				$goals = $options['goalsDefault'];

			}

			if ( ! is_array( $goals ) || empty( $goals ) ) {
				$goals = array(
					0 => array(
						'ix'          => 0,
						'name'        => 'Goal',
						'description' => 'Default. No custom goals setup.',
						'amount'      => 100,
						'current'     => 0,
						'cumulated'   => 0,
					),
				);
			}

			if ( ! $goal ) {
				$goal = array_values( $goals )[0]; // first goal
			}

			if ( ! array_key_exists( 'name', $goal ) ) {
				$goal = array_values( $goals )[0]; // first goal
			}

			// reset to first goal after reset days
			if ( $goal['started'] ?? false ) {
				if ( $goal['reset'] ?? false ) {
					if ( time() - 86400 * $goal['reset'] > $goal['started'] ) {
						$goal     = array_values( $goals )[0]; // first goal
						$saveGoal = 1;
					}
				}
			}

			// $goal['goals0'] = serialize($goals[0]);

			// complete any goal
			$completed            = '';
			$completedDescription = '';

			if ( $add ) {
				
				if ( ! $goal['started'] ) {
					$goal['started'] = time(); // start on first gift
				}

				if ( $goal['current'] + $add < $goal['amount'] ) {
					$goal['current']   += $add;
					$goal['cumulated'] += $add;

	
					// update current goal
					$ix           = $goal['ix'];
					$goals[ $ix ] = $goal;


				} else {
					
					// for next
					$delta = $goal['current'] + $add - $goal['amount'];

					$goal['cumulated'] += $goal['amount'] - $goal['current'];
					$goal['current']    = $goal['amount'];
				
				
					// update current goal
					$ix           = $goal['ix'];
					$goals[ $ix ] = $goal;


					while ($delta > 0)
					{
					//find next goal (progrssive)
					
					$newGoal = '';
					
					// for ($k=0; $k < count($goals); $k++ )
					foreach ( $goals as $k => $value ) {
						if ( $value['name'] == $goal['name'] ) { // current goal found
							if ( array_key_exists( $k + 1, $goals ) ) {
								 $newGoal       = $goals[ $k + 1 ];
								 $newGoal['ix'] = $k + 1;
								 break;

							}
						}
					}

					//repeat last goal if no next goal not found
					if ( ! $newGoal ) {
						$newGoal       = end($goals);
						$newGoal['ix'] = $newGoal['ix'] + 1;
					}

					/*
					if ( ! $newGoal ) {
						$newGoal = array_values( $goals )[0]; // back to first goal if last gola not found
					}
					*/
					
					$nAdd = min( $delta, $newGoal['amount'] ); //partial or full
					$delta = $delta - $nAdd; //rest for another goal

					$newGoal['started']   = time();
					$newGoal['current']  += $nAdd;
					$newGoal['cumulated'] += $nAdd;

					$completed            = stripslashes( $goal['name'] );
					$completedDescription = stripslashes( $goal['description'] );

					// update new goal
					$nix           = $newGoal['ix'];
					$goals[ $nix ] = $newGoal;
					
					$goal = $newGoal;

					}
				}
				
			//add	
			}

			if ( ! $goal['amount'] ) {
				$goal['amount'] = 100; // avoid division by zero in case of misconfiguration
			}
			$goal['current'] = round( $goal['current'], 2 );

			$goal['progress'] = round( $goal['current'] * 100 / $goal['amount'] );

			$goal['name']        = stripslashes( $goal['name'] );
			$goal['description'] = stripslashes( $goal['description'] );

			if ( $add || $saveGoal ) {
				update_post_meta( $postID, 'goal', $goal );

				// update current goal
				$ix           = $goal['ix'];
				$goals[ $ix ] = $goal;

				update_post_meta( $postID, 'goals', $goals );
			}

			$goal['completed']            = $completed;
			$goal['completedDescription'] = $completedDescription;

			return $goal;
		}



		static function balances( $userID, $options = null ) {
			// get html code listing balances
			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}
			if ( ! $options['walletMulti'] ) {
				return ''; // disabled
			}

			$balances = self::walletBalances( $userID, '', $options );

			$walletTransfer = sanitize_text_field( $_GET['walletTransfer'] ?? '' );
			$walletTransfer2 = sanitize_text_field( $_GET['walletTransfer2'] ?? '' );

			$htmlCode = '';
			
			global $wp;
			foreach ( $balances as $key => $value ) {
				$htmlCode .= '<br>' . $key . ': ' . $value;

				if ( $options['walletMulti'] == 2 && $walletTransfer != $key && $options['wallet'] != $key && $value > 0 ) {
					$htmlCode .= ' <a class="ui button compact tiny basic" href=' . add_query_arg( array( 'walletTransfer' => $key ), $wp->request ) . ' data-tooltip="' . __('Transfer to Active Balance', 'ppv-live-webcams') . '">' . __('Transfer to', 'ppv-live-webcams') . ' ' . $options['wallet'] . ' </a>';
				}
				
				if ($options['wallet2'] && $options['walletMulti'] == 2 && $walletTransfer2 != $key && $options['wallet2'] != $key && $value > 0 ) {
					$htmlCode .= ' <a class="ui button compact tiny basic" href=' . add_query_arg( array( 'walletTransfer2' => $key ), $wp->request ) . ' data-tooltip="' . __('Transfer to Secondary Balance', 'ppv-live-webcams') . '">' . __('Transfer to', 'ppv-live-webcams') . ' ' . $options['wallet2'] .  ' </a>';
				}
				
				//transfer to primary or auto
				if ( $walletTransfer == $key || ( $value > 0 && $options['walletMulti'] == 3 && $options['wallet'] != $key && $options['wallet2'] != $key ) ) {
					self::walletTransfer( $key, $options['wallet'], get_current_user_id(), $options );
					$htmlCode .= ' ' . __('Transferred to active balance.', 'ppv-live-webcams') . ' (' . $options['wallet'] . ')';
				}
				
				//transfer to secondary
				if ( $walletTransfer2 == $key ) {
					self::walletTransfer( $key, $options['wallet2'], get_current_user_id(), $options );
					$htmlCode .= ' ' . __('Transferred to secondary balance.', 'ppv-live-webcams') . ' (' . $options['wallet2'] . ')';
				}
				
			}

			return $htmlCode;
		}


		static function walletBalances( $userID, $view = 'view', $options = null ) {
			$balances = array();
			if ( ! $userID ) {
				return $balances;
			}

			//micropayments: snapshot in user meta
			if ( class_exists('VWpaidMembership') ) $balances['MicroPayments'] = number_format( floatval ( get_user_meta($userID, 'micropayments_balance', true) ), 2, '.', '' );

			// woowallet
			if ( $GLOBALS['woo_wallet'] ?? false) {
				$wooWallet = $GLOBALS['woo_wallet'];

				if ( $wooWallet ) {
					if ( $wooWallet->wallet ) {
						$balances['WooWallet'] = $wooWallet->wallet->get_wallet_balance( $userID, $view );
					}
				}
			}

			// mycred
			if ( function_exists( 'mycred_get_users_balance' ) ) {
				$balances['MyCred'] = mycred_get_users_balance( $userID );
			}

			return $balances;
		}


		static function walletTransfer( $source, $destination, $userID, $options = null ) {
			// transfer balance from a wallet to another wallet

			if ( $source == $destination ) {
				return;
			}

			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}

			$balances = self::walletBalances( $userID, '', $options );

			if ( $balances[ $source ] > 0 ) {
				self::walletTransaction( $destination, $balances[ $source ], $userID, "Wallet balance transfer from $source to $destination.", 'wallet_transfer' );
				self::walletTransaction( $source, - $balances[ $source ], $userID, "Wallet balance transfer from $source to $destination.", 'wallet_transfer' );
			}

		}


		static function walletTransaction( $wallet, $amount, $user_id, $entry, $ref, $ref_id = null, $data = null ) {
			// transactions on all supported wallets
			// $wallet : MyCred/WooWallet

			if ( $amount == 0 ) {
				return; // no transaction
			}

			//micropayments			
			if ( $wallet == 'MicroPayments' ) if ( class_exists('VWpaidMembership') )
			{
				\VWpaidMembership::micropayments_transaction ($user_id, $ref, $amount, $entry);
			} 


			// mycred
			if ( $wallet == 'MyCred' ) {
				if ( $amount > 0 ) {
					if ( function_exists( 'mycred_add' ) ) {
						mycred_add( $ref, $user_id, $amount, $entry, $ref_id, $data );
					}
				} else {
					if ( function_exists( 'mycred_subtract' ) ) {
						mycred_subtract( $ref, $user_id, $amount, $entry, $ref_id, $data );
					}
				}
			}

			// woowallet https://github.com/malsubrata/woo-wallet/blob/master/includes/class-woo-wallet-wallet.php
			if ( $wallet == 'WooWallet' ) {
				if ( $GLOBALS['woo_wallet'] ) {
					$wooWallet = $GLOBALS['woo_wallet'];

					if ( $amount > 0 ) {
						$wooWallet->wallet->credit( $user_id, $amount, $entry );
					} else {
						$wooWallet->wallet->debit( $user_id, -$amount, $entry );
					}
				}
			}

		}


		static function balance( $userID, $live = false, $options = null ) {
			// get current user balance (as value)
			// $live also estimates active (incomplete) session costs for client

			if ( ! $userID ) {
				return 0;
			}

			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}

			$balance = 0;

			$balances = self::walletBalances( $userID, '', $options );

			if ( $options['wallet'] ) {
				if ( array_key_exists( $options['wallet'], $balances ) ) {
					$balance = $balances[ $options['wallet'] ];
				}
			}
			
			//update local balance
			$localBalance = floatval( get_user_meta( $userID, 'vw_ppv_balance', true ) );
			if ($balance != $localBalance) update_user_meta( $userID, 'vw_ppv_balance', $balance );

			if ( $live ) {
				$updated = intval( get_user_meta( $userID, 'vw_ppv_tempt', true ) );

				if ( time() - $updated < 15 ) { // updated recently: use that estimation
					$temp = floatval( get_user_meta( $userID, 'vw_ppv_temp', true ) );
				} else {
					$temp = self::billSessions( $userID, 0, false ); // estimate charges for current sessions
				}

				$balance = $balance - $temp; // deduct temporary charge
			}

			return $balance;
		}

		static function transaction( $ref = 'ppv_live_webcams', $user_id = 1, $amount = 0, $entry = 'PPV Live Webcams transaction.', $ref_id = null, $data = null, $options = null ) {
			// ref = explanation ex. ppv_client_payment
			// entry = explanation ex. PPV client payment in room.
			// utils: ref_id (int|string|array) , data (int|string|array|object)

			if ( $amount == 0 ) {
				return; // nothing
			}

			if ( ! $options ) {
				$options = self::getOptions();
			}

			// active wallet
			$wallet = $options['wallet'];
			if ( ! $wallet ) $wallet = 'MicroPayments';
				
			//missing MicroPayments: try WooWallet
			if ($wallet == 'MicroPayments') if ( !class_exists('VWpaidMembership') ) if ( $GLOBALS['woo_wallet'] ) $wallet = 'WooWallet';
			
			//missing WooWallet: try MicroPayments
			if ($wallet == 'WooWallet') if ( ! $GLOBALS['woo_wallet'] ) if ( class_exists('VWpaidMembership') ) $wallet = 'MicroPayments';
						
		
			self::walletTransaction( $wallet, $amount, $user_id, $entry, $ref, $ref_id, $data );

			//update local balance if affected
			if ( $options['wallet'] && $options['wallet'] == $wallet ) {
				$balances = self::walletBalances( $user_id, '', $options );
				if ( array_key_exists( $options['wallet'], $balances ) ) {
					$balance = $balances[ $options['wallet'] ];
					
					//update local balance
					$localBalance = floatval( get_user_meta( $user_id, 'vw_ppv_balance', true ) );
					if ($balance != $localBalance) update_user_meta( $user_id, 'vw_ppv_balance', $balance );
				}
			}
		}


		// ! PPV Calculations

		static function billCount( $uid, $options = null ) {
			// counts number of paid sessions (billed)
			// $uid = performer id

			if ( ! $uid ) {
				return;
			}
			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}

			global $wpdb;
			$table_private  = $wpdb->prefix . 'vw_vmls_private';
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

			$sql     = $wpdb->prepare("SELECT COUNT(id) as no FROM $table_private WHERE status = %d AND pid=%d", 2, $uid);
			$clients = $wpdb->get_row( $sql );
			update_user_meta( $uid, 'paidSessionsPrivate', $clients->no );

			$sql     = $wpdb->prepare("SELECT COUNT(id) as no FROM $table_sessions WHERE status = %d AND uid=%d", 2, $uid);
			$clients = $wpdb->get_row( $sql );
			update_user_meta( $uid, 'paidSessionsGroup', $clients->no );

		}


		static function billCountRoom( $rid, $options = null ) {
			// counts number of paid sessions (billed)
			// $rid = room id

			if ( ! $rid ) {
				return;
			}
			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}

			global $wpdb;
			$table_private  = $wpdb->prefix . 'vw_vmls_private';
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

			$sql     = $wpdb->prepare("SELECT COUNT(id) as no FROM $table_private WHERE status = %d AND rid = %d", 2, $rid);
			$clients = $wpdb->get_row( $sql );
			update_post_meta( $rid, 'paidSessionsPrivate', $clients->no );

			$sql     = $wpdb->prepare("SELECT COUNT(id) as no FROM $table_sessions WHERE status = %d AND rid = %d", 2, $rid);
			$clients = $wpdb->get_row( $sql );
			update_post_meta( $rid, 'paidSessionsGroup', $clients->no );
		}


		static function billSessions( $uid = 0, $rid = 0, $complete = true ) {
			// $uid = process for user_id, returns estimate
			// $rid = process for postID (wecam/room)
			// $complete = if not, just estimate temp charge for client ($uid required)

			$options = get_option( 'VWliveWebcamsOptions' );

			$closeTime = time() - max( intval( $options['ppvCloseAfter'] ), 70 );
			$billTime  = time() - intval( $options['ppvBillAfter'] );
			$logTime   = time() - intval( $options['ppvKeepLogs'] );

			global $wpdb;
			$table_private  = $wpdb->prefix . 'vw_vmls_private';
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

			$cStatus = 'status=1';

			// temporary charge per account (!$complete)
			$temp = 0;
			if ( ! $complete ) {
				$cStatus  = '(status=0 OR status=1)'; // process open, closed
				$billTime = time() + 1; // process all including recent, !! +1 because is updated on same request
			}

			// ! bill Private videochat sessions

			if ( $complete ) {
				// force clean and close sessions terminated abruptly

				// delete where only 1 entered (other could have accepted and quit) or entered but stayed 0s, except calls
				$sql = $wpdb->prepare("DELETE FROM `$table_private` WHERE `call` = '0' AND (status=0 OR status=1) AND ((cedate=0 AND pedate < %d) OR (pedate=0 AND cedate < %d) OR (psdate > 0 AND pedate=psdate AND pedate < %d) OR (csdate>0 AND cedate=csdate AND cedate < %d))", $closeTime, $closeTime, $closeTime, $closeTime);
				$wpdb->query( $sql );

				// close rest, where both entered
				$sql = $wpdb->prepare("UPDATE `$table_private` SET status='1' WHERE psdate > 0 AND csdate > 0 AND status='0' AND pedate < %d AND cedate < %d", $closeTime, $closeTime);
				$wpdb->query( $sql );
			}

			// bill private sessions
			if ( $uid ) {
				$cnd = $wpdb->prepare("AND (pid=%d OR cid=%d)", $uid, $uid);
			} else {
				$cnd = '';
			}

			$sql      = $wpdb->prepare("SELECT * FROM $table_private WHERE $cStatus AND pedate < %d AND cedate < %d $cnd", $billTime, $billTime);
			$sessions = $wpdb->get_results( $sql );

			if ( $wpdb->num_rows > 0 ) {
				foreach ( $sessions as $session ) {
					$temp += self::billPrivateSession( $session, $complete, $uid );
				}
			}

			if ( $complete ) {
				// clean private session logs, except calls
				$sql = $wpdb->prepare("DELETE FROM `$table_private` WHERE `call` = %d AND pedate < %d AND cedate < %d AND status = %d $cnd", 0, $logTime, $logTime, 2);
				$wpdb->query( $sql );
			}

			// ! bill Group videochat sessions
			if ( $uid ) {
				$cnd = $wpdb->prepare("AND uid=%d", $uid);
			} else {
				$cnd = '';
			}
			if ( $rid ) {
				$cnd .= $wpdb->prepare(" AND rid=%d", $rid);
			}

			if ( $complete ) {
				
				// delete viewer session where performer did not enter (redate) until user left (edate)
				$sql = $wpdb->prepare("DELETE FROM `$table_sessions` WHERE (status=0 OR status=1) AND (rsdate = 0 AND edate < %d AND sdate <  %d ) AND broadcaster = 0", $closeTime, $closeTime);
				$wpdb->query( $sql );

				// update rest to status = 1
				$sql = $wpdb->prepare("UPDATE `$table_sessions` SET status='1' WHERE status='0' AND edate < %d", $closeTime);
				$wpdb->query( $sql );
			}

			$sql      = $wpdb->prepare("SELECT * FROM $table_sessions WHERE $cStatus AND edate < %d $cnd", $billTime);
			$sessions = $wpdb->get_results( $sql );
			if ( $wpdb->num_rows > 0 ) {
				foreach ( $sessions as $session ) {
					$temp += self::billGroupSession( $session, $complete );
				}
			}

			if ( $complete ) {
				// clean group session logs
				$sql = $wpdb->prepare("DELETE FROM `$table_sessions` WHERE edate < %d AND sdate < %d AND status ='2' $cnd", $logTime, $logTime);
				$wpdb->query( $sql );
			}

			// update temp charge
			if ( $uid ) {
				if ( ! $complete ) {
					update_user_meta( $uid, 'vw_ppv_temp', $temp );
					update_user_meta( $uid, 'vw_ppv_tempt', time() );
				} else {
					update_user_meta( $uid, 'vw_ppv_temp', 0 );
					update_user_meta( $uid, 'vw_ppv_tempt', time() );
				}
			}

			return $temp;

		}


		static function clientCPM( $room_name, $options = '', $postID = 0, $type = '' ) {
			
			//cost per minute in private
			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}
			
			
			// custom room cost per minute
			if ( ! $postID ) {
				global $wpdb;
				$postID = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s and post_type=%s LIMIT 0,1', $room_name, $options['custom_post'] ) );
			}

			$CPM = get_post_meta( $postID, 'vw_costPerMinute' . $type, true );
			if ( $CPM == '' ) {
				$CPM = $options[ 'ppvPPM' . $type ];
			}

			if ( $options['ppvPPMmin'] ) {
				if ( $CPM < $options['ppvPPMmin'] ) {
					$CPM = $options['ppvPPMmin'];
				}
			}
			if ( $options['ppvPPMmax'] ) {
				if ( $CPM > $options['ppvPPMmax'] ) {
					$CPM = $options['ppvPPMmax'];
				}
			}

					return round( $CPM, 2 ); // 2 decimals
		}


		static function performerRatio( $room_name, $options = '', $postID = null ) {

			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}

			$Ratio = 1; 

			// custom performer ratio
			if ( ! $postID ) {
				global $wpdb;
				$postID = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s and post_type=%s LIMIT 0,1', $room_name, $options['custom_post'] ) );
			}

			if ( $postID ) {
				$Ratio = floatval(get_post_meta( $postID, 'vw_earningRatio', true ));
				$bonusRatio = floatval(get_post_meta( $postID, 'vw_bonusRatio', true ));
				
				if ($bonusRatio > $Ratio) $Ratio = $bonusRatio; //performer gets best ratio
			}

			if ( !$Ratio ) {
				$Ratio = floatval($options['ppvRatio']);
			}

			return round( $Ratio, 2 );
		}


		static function billGroupSession( $session, $complete = true ) {
			// bills group session
			// if not $complete just returns temporary charge estimation

			if ( ! $session->uid ) {
				return 0; // no user, now wallet, no cost
			}

			if ( $session->status >= 2 ) {
				return 0; // already billed (0 live, 1 closed, 2 billed)
			}

			// performer (broadcaster) should not get billed in group session
			if ( $session->broadcaster ) return 0;
			
			//extra check for performer (for own playbacks sessions like in conference / preview)
			if ( self::isPerformer( $session->uid, $session->rid ) ) return 0;

			$options = self::getOptions();
			
			global $wpdb;
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';


			//moderator sessions don't get charged
		    if ( self::isModerator($session->uid, $options) ) return 0;
		    

			$roomOptions  = unserialize( $session->roptions );
			$clientCPM    = $roomOptions['cpm'] ?? 0;
			$sessionStart = $roomOptions['sessionStart'] ?? 0;

			$checkin = $roomOptions['checkin'] ?? '';

			if ( ! $clientCPM ) {
				return 0;
			}

			// temp calc or active session
			if ( ! $complete || $session->status == 0 ) {
				return self::clientGroupCost( $session, $clientCPM, $sessionStart );
			}

			$end   = min( $session->edate, $session->redate ); // when first left
			$start = max( $session->sdate, $session->rsdate ); // when last entered

			$totalDuration = $end - $start;
			$duration      = $totalDuration - $options['ppvGraceTime'];
			if ( $duration < 0 ) {
				return; // graced - nothing to bill
			}

			$timeStamp = date( 'M j G:i', $start ) . ' ' . ceil( $duration / 60 ) . 'm';

			$charge = number_format( $duration * $clientCPM / 60, 2, '.', '' );

			if ( ! $complete ) {
				return $charge;
			}
			
			//not closed, yet: close first before bill
			if ( ! $session->status ) return $charge;

			$performerRatio = self::performerRatio( $session->room, $options );

			// checkin perfomer payments
			$divider = 1;
			$share   = 0;

			if ( $checkin ) {

				if ( ! is_array( $checkin ) ) {
					$checkin = array( $checkin );
				}

				$divider = count( $checkin );
				if ( ! $divider ) {
					return;
				}

				$share = number_format( $duration * $clientCPM / ( $divider * 60 ), 2, '.', '' );
			}

			// update session meta with billing info
			if ( $session->meta ) {
				$sessionMeta = unserialize( $session->meta );
			}
			if ( ! is_array( $sessionMeta ) ) {
				$sessionMeta = array();
			}

			$sessionMeta['b_clientCPM']      = $clientCPM;
			$sessionMeta['b_ppvGrace']       = $options['ppvGraceTime'];
			$sessionMeta['b_ppvDuration']    = $duration;
			$sessionMeta['b_clientCost']     = $charge;
			$sessionMeta['b_performerRatio'] = $performerRatio;
			$sessionMeta['b_performerCount'] = $divider;
			$sessionMeta['b_performerShare'] = $share;
			$sessionMeta['b_performerEarn']  = $share * $performerRatio;	
			$sessionMeta['b_wallet']         = $options['wallet'];					
			$sessionMeta['b_sessionTime']    = $timeStamp;
			$sessionMeta['b_time']           = time();
			$sessionMeta['b_request_user_id'] = get_current_user_id();

			$sessionMetaS = serialize( $sessionMeta );

			// mark group session as billed

			$sql = $wpdb->prepare("UPDATE `$table_sessions` SET `status`='2', `meta`=%s WHERE id=%d", $sessionMetaS, $session->id);
			$wpdb->query( $sql );


			// transactions after marking session as billed
			
		    // client cost
			if ( $clientCPM > 0 ) {
				self::transaction( 'ppm_group', $session->uid, - $charge,  __( 'PPM group', 'ppv-live-webcams' ) . ' ' . $roomOptions['groupMode'] . ' ' . $roomOptions['userMode'] . ' ' . __( 'session in', 'ppv-live-webcams' ) . ' <a href="' . self::roomURL( $session->room ) . '">' . $session->room . '</a> #' . $session->id .' ' . $timeStamp, $session->id );
			}

			// checkin perfomer payments (current performer always checked in)
			if ( $checkin ) {
				foreach ( $checkin as $performerID ) {
					self::transaction( 'ppm_group_earn', $performerID, $share * $performerRatio, __( 'Earning from PPM group', 'ppv-live-webcams' ) . ' ' . $roomOptions['groupMode'] . ' ' . $roomOptions['userMode'] . __( ' session with ', 'ppv-live-webcams' ) . $session->username . ' #' . $session->id .' ' . $timeStamp, $session->id );
				}
			}

			return 0; // billed: no temp charge
		}


		static function billPrivateSession( $session, $complete = true, $uid = 0 ) {
			// bills a private session
			// $uid, provide temp calc

			if ( ! $complete || $session->status == 0 || $session->status >= 2 ) {
				
				if ( ! $uid ) {
					return 0; // temp estimate for who?
				}
				
				if ( $session->cid == $uid ) {
					return self::clientCost( $session );
				}
				
				if ( $session->pid == $uid ) {
					return self::performerCost( $session );
				}
			}

			$options = get_option( 'VWliveWebcamsOptions' );

			// get session meta
			if ( $session->meta ) {
				$sessionMeta = unserialize( $session->meta );
			}
			if ( ! is_array( $sessionMeta ) ) {
				$sessionMeta = array();
			}

			if ( array_key_exists( 'callMode', $sessionMeta ) ) {
				if ( $sessionMeta['callMode'] == 'free' || $sessionMeta['callMode'] == 'free-audio' || $sessionMeta['callMode'] == 'free-text' ) {
					return 0;
				}
			}

			if ( array_key_exists( 'clientCPM', $sessionMeta ) ) {
				$clientCPM = $sessionMeta['clientCPM'];
			} else {
				$clientCPM                   = self::clientCPM( $session->room, $options );
				$sessionMeta['b_sessionCPM'] = 'Warning:OnBill';
			}

			$performerRatio = self::performerRatio( $session->room, $options );

			if ( ! $options['ppvPerformerPPM'] && ! $clientCPM ) {
				return;
			}
			if ( ! $session ) {
				return 0;
			}
			if ( $session->pedate == 0 || $session->cedate == 0 ) {
				return 0; // did not enter both
			}

			$start         = max( $session->psdate, $session->csdate );
			$end           = min( $session->pedate, $session->cedate );			
			$totalDuration = $end - $start;
			$duration      = $totalDuration - $options['ppvGraceTime'];
			if ( $duration < 0 ) {
				return 0; // graced - nothing to bill
			}

			$startDate = ' ' . date( DATE_RFC2822, $start );

			$timeStamp = date( 'M j G:i', $start ) . ' ' . ceil( $duration / 60 ) . 'm';

			$charge = number_format( $duration * $clientCPM / 60, 2, '.', '' );

			if ( ! $complete ) {
				return $charge; // just return estimate
			}

			// performer earning
			$performerEarning = number_format( $duration * $clientCPM * $performerRatio / 60, 2, '.', '' );

			// performer cost
			$peformerCost = number_format( $duration * $options['ppvPerformerPPM'] / 60, 2, '.', '' );


			// update session meta with billing info
			$sessionMeta['b_clientCPM']        = $clientCPM;
			$sessionMeta['ppvPerformerPPM']	   = $options['ppvPerformerPPM'];
			$sessionMeta['b_ppvGrace']         = $options['ppvGraceTime'];
			$sessionMeta['b_ppvDuration']      = $duration;
			$sessionMeta['b_clientCost']       = $charge;
			$sessionMeta['b_performerRatio']   = $performerRatio;
			$sessionMeta['b_performerEarning'] = $performerEarning;
			$sessionMeta['b_peformerCost']     = $peformerCost;
			$sessionMeta['b_wallet']           = $options['wallet'];			
			$sessionMeta['b_sessionTime']      = $timeStamp;
			$sessionMeta['b_previousStatus']   = $session->status;
			$sessionMeta['b_time']             = time();
			$sessionMeta['b_request_user_id']  = get_current_user_id();

			$sessionMetaS = serialize( $sessionMeta );

			// mark private session as billed
			global $wpdb;
			$table_private = $wpdb->prefix . 'vw_vmls_private';

			$sql = $wpdb->prepare("UPDATE `$table_private` SET `status`='2', `meta`=%s WHERE `id`=%d", $sessionMetaS, $session->id);
			$wpdb->query( $sql );

			// call transactions after, to avoid any wallet plugin errors before saving billed status
			
			// client cost
			if ( $clientCPM > 0 ) {
				self::transaction( 'ppm_private', $session->cid, - $charge, 'PPM private session #' . $session->id . ' with <a href="' . self::roomURL( $session->room ) . '">' . $session->performer . '</a>  #' . $session->id .' ' . $timeStamp, $session->id );
			}

			// performer earning
			if ( $performerEarning > 0 ) {
				self::transaction( 'ppm_private_earn', $session->pid, $performerEarning, __( 'Earning from PPM private session:', 'ppv-live-webcams' ) .  ' ' . $session->client . ' #' . $session->id . ' ' . $timeStamp, $session->id );
			}

			// performer cost
			if ( $options['ppvPerformerPPM'] > 0 ) {
				self::transaction( 'ppm_private_performer', $session->pid, - $peformerCost, __( 'Performer cost for PPM private session:', 'ppv-live-webcams' ) . ' ' . $session->performer . ' #' . $session->id . ' ' . $timeStamp, $session->id );
			}

			return 0;
		}


		// calculate current cost (no processing)
		static function clientCost( $session ) {
			// private session cost

			// free call
			if ( $session->meta ) {
				$sessionMeta = unserialize( $session->meta );
			}
			if ( ! is_array( $sessionMeta ) ) {
				$sessionMeta = array();
			}
			if ( array_key_exists( 'callMode', $sessionMeta ) ) {
				if ( $sessionMeta['callMode'] == 'free' ) {
					return 0;
				}
			}

				// default
				$options = get_option( 'VWliveWebcamsOptions' );
			$clientCPM   = self::clientCPM( $session->room, $options );

			if ( ! $clientCPM ) {
				return 0;
			}
			if ( ! $session ) {
				return 0;
			}
			if ( $session->pedate == 0 || $session->cedate == 0 ) {
				return 0; // did not enter both
			}

			// duration when both online: max(psdate,csdate)->min(pedate,cedate)
			$duration = min( $session->pedate, $session->cedate ) - max( $session->psdate, $session->csdate ) - $options['ppvGraceTime'];
			if ( $duration < 0 ) {
				return 0; // grace
			}

			return number_format( $duration * $clientCPM / 60, 2, '.', '' );
		}


		// calculate current group cost (no processing)
		static function clientGroupCost( $session, $clientCPM, $sessionStart = 0 ) {

			// $roomOptions['sessionStart']

			$options = get_option( 'VWliveWebcamsOptions' );

			if ( ! $clientCPM ) {
				return 0;
			}
			if ( ! $session ) {
				return 0;
			}
			if ( ! $session->uid ) {
				return 0; // no user, no wallet
			}
			if ( $session->edate == 0 ) {
				return 0; // did not enter
			}

			// duration when both online: max(psdate,csdate)->min(pedate,cedate)
			$duration = min( $session->edate, $session->redate ) - max( $sessionStart, $session->sdate ) - $options['groupGraceTime'];
			if ( $duration < 0 ) {
				return 0; // grace
			}

			//subscription?
			if ($session->rid)
			{
			$content_tier = get_post_meta( $session->rid, 'vw_subscription_tier', true ); 
			
			if ($content_tier) // room can be accessed with subscription
			{
				$author_id = get_post_field ('post_author', $session->rid);
				if ($author_id) 
				{
					$client_tier = intval( get_user_meta( $session->uid, 'vw_client_subscription_' . $author_id, true ) ); //client subscription tier
					
					if ($client_tier >= $content_tier) return 0; //free group session for this client, as subscribed to required or higher tier
				}
			}
			
			}

			return number_format( $duration * $clientCPM / 60, 2, '.', '' );
		}


		static function performerCost( $session ) {
			// $session = private session

			$options = get_option( 'VWliveWebcamsOptions' );

			if ( ! $options['ppvPerformerPPM'] ) {
				return 0;
			}
			if ( ! $session ) {
				return 0;
			}
			if ( $session->pedate == 0 || $session->cedate == 0 ) {
				return 0; // did not enter both
			}

			// duration when both online: max(psdate,csdate)->min(pedate,cedate)
			$duration = min( $session->pedate, $session->cedate ) - max( $session->psdate, $session->csdate ) - $options['ppvGraceTime'];
			if ( $duration < 0 ) {
				return 0; // grace
			}

			return number_format( $duration * $options['ppvPerformerPPM'] / 60, 2, '.', '' );
		}


		// ! Online user functions

		static function currentUserSession( $room ) {
			if ( ! is_user_logged_in() ) {
				return 0;
			}

			$current_user = wp_get_current_user();

			$options = get_option( 'VWliveWebcamsOptions' );

			$username1 = $current_user->${$options['userName']};
			$username2 = $current_user->${$options['webcamName']};

			global $wpdb;
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

			$sql     = $wpdb->prepare("SELECT * FROM `$table_sessions` WHERE (session=%s OR session=%s) AND room=%s AND status=%d LIMIT 1", $username1, $username2, $room, 1);
			$session = $wpdb->get_row( $sql );

			return $session;
		}


		static function webcamOnline( $postID ) {
			$last = time() - (int) get_post_meta( $postID, 'edate', true );

			$options = get_option( 'VWliveWebcamsOptions' );

			if ( $last < $options['onlineTimeout'] ) {
				return true;
			} else {
				return false;
			}
		}
		


		static function isPerformer( $userID, $postID ) {
			// is specified user a performer for this room
			if ( ! $userID ) {
				return 0;
			}
			if ( ! $postID ) {
				return 0;
			}

			$current_user = get_userdata( $userID );
			if ( ! $current_user ) {
				return 0;
			}
			
			if ( ! $current_user->ID ) {
				return 0;
			}

			$post = get_post( $postID );
			if ( ! $post ) {
				return 0;
			}

			// owner
			if ( $post->post_author == $current_user->ID ) {
				return 1;
			}

			// performer (post owner is studio)
			if ( get_post_meta( $postID, 'performerID', true ) == $current_user->ID ) {
				return 2;
			}

			// multi performer posts (array)
			$performerIDs = get_post_meta( $postID, 'performerID', false );
			if ( $performerIDs ) {
				if ( is_array( $performerIDs ) ) {
					if ( in_array( $current_user->ID, $performerIDs ) ) {
						return 3;
					}
				}
			}

			
			//neither
			return 0;

		}


		static function isAuthor( $postID ) {
			// is current user author (owner or assigned perfomer)
			// includes post author and assigned by studio (single or multi perfomer room as performerID)

			if ( ! $postID ) {
				return 0;
			}

			$current_user = wp_get_current_user();
			if ( ! isset( $current_user ) ) {
				return 0;
			}
			if ( ! $current_user ) {
				return 0;
			}
			if ( ! $current_user->ID ) {
				return 0;
			}

			$post = get_post( $postID );
			if ( ! $post ) {
				return 0;
			}

			// owner
			if ( $post->post_author == $current_user->ID ) {
				return 1;
			}

			// performer (post owner is studio)
			if ( get_post_meta( $postID, 'performerID', true ) == $current_user->ID ) {
				return 1;
			}

			// multi performer posts
			$performerIDs = get_post_meta( $postID, 'performerID', false );
			if ( $performerIDs ) {
				if ( is_array( $performerIDs ) ) {
					if ( in_array( $current_user->ID, $performerIDs ) ) {
						return 1;
					}
				}
			}

					return 0;
		}


		static function updateViewers( $postID, $room, $options = null ) {

			if ( ! self::timeTo( $room . '-updateViewers', 30, $options ) ) {
				return;
			}

			if ( ! $options ) {
				$options = self::getOptions();
			}

			global $wpdb;
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

			// close sessions
			$closeTime = time() - max( intval( $options['ppvCloseAfter'] ), 70 ); // > client statusInterval
			$sql       = $wpdb->prepare("UPDATE `$table_sessions` SET status = 1 WHERE status = 0 AND edate < %d", $closeTime);
			$wpdb->query( $sql );

			// update viewers
			$viewers = $wpdb->get_var( $wpdb->prepare("SELECT count(id) as no FROM `$table_sessions` WHERE status = %d AND room = %s", 0, $room) );
			update_post_meta( $postID, 'viewers', $viewers );

			$maxViewers = intval( get_post_meta( $postID, 'maxViewers', true ) );

			if ( !$maxViewers || $viewers >= $maxViewers ) {
				update_post_meta( $postID, 'maxViewers', $viewers );
				update_post_meta( $postID, 'maxDate', time() );
			}

		}


		static function timeTo( $action, $expire = 60, $options = '' ) {
			// if $action was already done in last $expire, return false

			if ( ! $options ) $options = self::getOptions();
		

			$cleanNow = false;

			$ztime = time();

			$lastClean = 0;

			if (!isset( $options['uploadsPath'] )) return true;

			// saves in specific folder			
			$timersPath = $options['uploadsPath'];
			if ( ! file_exists( $timersPath ) ) {
				mkdir( $timersPath );
			}
			$timersPath .= '/_timers/';
			if ( ! file_exists( $timersPath ) ) {
				mkdir( $timersPath );
			}

			$lastCleanFile = $timersPath . $action . '.txt';

			if ( ! file_exists( $dir = dirname( $lastCleanFile ) ) ) {
				mkdir( $dir );
			} elseif ( file_exists( $lastCleanFile ) ) {
				$lastClean = file_get_contents( $lastCleanFile );
			}

			if ( ! $lastClean ) {
				$cleanNow = true;
			} elseif ( $ztime - $lastClean > $expire ) {
				$cleanNow = true;
			}

			if ( $cleanNow ) {
				file_put_contents( $lastCleanFile, $ztime );
			}

				return $cleanNow;

		}


		// ! App Calls

		static function editParameters( $default = '', $update = array(), $remove = array() ) {
			// adjust parameters string by update(add)/remove

			parse_str( substr( $default, 1 ), $params );

			// remove

			if ( count( $update ) ) {
				foreach ( $params as $key => $value ) {
					if ( in_array( $key, $update ) ) {
						unset( $params[ $key ] );
					}
				}
			}

			if ( count( $remove ) ) {
				foreach ( $params as $key => $value ) {
					if ( in_array( $key, $remove ) ) {
						unset( $params[ $key ] );
					}
				}
			}

							// add updated
			if ( count( $update ) ) {
				foreach ( $update as $key => $value ) {
					$params[ $key ] = $value;
				}
			}

								return '&' . http_build_query( $params );
		}


		static function rexit( $output ) {
			echo esc_html( $output );
			exit;

		}


		static function isRoomPerformer( $post, $current_user ) {

			if ( $post->post_author == $current_user->ID ) {
				return 1;
			}

			// assigned performer
			$performerIDs = get_post_meta( $post->ID, 'performerID', false );
			if ( $performerIDs ) {
				if ( is_array( $performerIDs ) ) {
					if ( in_array( $current_user->ID, $performerIDs ) ) {
						return 1;
					}
				}
			}

					return 0;
		}


		static function webSessionSave( $username, $canKick = 0, $debug = '0', $ip = '' ) {
			// generates a session file record on web server for rtmp login check
			// means: this user was allowed by web server (previous web login or key), for more advanced control during session use rtmp seasion control

			$username = sanitize_file_name( $username );

			if ( $username ) {
				$options = get_option( 'VWliveWebcamsOptions' );
				$webKey  = sanitize_text_field( $options['webKey'] );
				$ztime   = time();

				$ztime = time();
				$info  = 'VideoWhisper=1&login=1&webKey=' . urlencode( $webKey ) . '&start=' . $ztime . '&ip=' . urlencode( $ip ) . '&canKick=' . $canKick . '&debug=' . urlencode( $debug );

				$dir = $options['uploadsPath'];
				if ( ! file_exists( $dir ) ) {
					mkdir( $dir );
				}
				// @chmod($dir, 0777);
				$dir .= '/_sessions';
				if ( ! file_exists( $dir ) ) {
					mkdir( $dir );
				}
				// @chmod($dir, 0777);

				$dfile = fopen( $dir . "/$username", 'w' );
				fputs( $dfile, $info );
				fclose( $dfile );
			}

		}


		static function vmls_callback() {

			//used by external streaming servers to manage streaming sessions
			//task: rtmp_login, rtmp_logout, rtmp_status

			$options = self::getOptions();
			global $wpdb;

			ob_clean();

			switch ( $_GET['task'] ) {

				// ! WebLogin ajax calls
				// ! rtmp_logout
				case 'rtmp_logout':

					if ($options['rtmpServer'] != 'wowza' ) break; //only used for specific rtmp server type

					// rtmp server notifies client disconnect here
					$session = sanitize_file_name(  $_GET['s'] ?? '' ); //sanitize_file_name to prevent directory traversal
					
					if ( ! $session ) {
						exit;
					}

					$dir     = sanitize_text_field( $options['uploadsPath'] );

					echo 'logout=';
					$filename1 = $dir . "/_sessions/$session";

					if ( file_exists( $filename1 ) ) {
						echo esc_attr( unlink( $filename1 ) ); // remove session file if exists
					}
					?>
				
					<?php
					break;

				// ! rtmp_login
				case 'rtmp_login':
					 
					if ($options['rtmpServer'] != 'wowza' ) break; //only used for specific rtmp server type

					// when external app connects to streaming server, it will call this to confirm and then accept/reject
					// rtmp server should check login like rtmp_login.php?s=$session&p[]=$username&p[]=$room&p[]=$key&p[]=$broadcaster&p[]=$broadcasterID&p[]=$IP
					// p[] = params sent with rtmp address (key, channel)

					$session = sanitize_file_name( $_GET['s'] ?? '' ); //sanitize_file_name to prevent directory traversal
					if ( ! $session ) {
						exit;
					}

					$p = isset( $_GET['p'] ) ? (array) $_GET['p'] : array();
					if ( count( $p ) ) {
						$username  = sanitize_text_field( $p[0] ); // or sessionID
						$room      = sanitize_file_name( urldecode( $p[1] ) ); // room, webcam listing post name
						$key       = sanitize_text_field($p[2]);
						$performer = $broadcaster = ( $p[3] === 'true' || $p[3] === '1' || intval($p[3]) > 0 ); // performer
						$userID    = $broadcasterID = intval( $p[4] ); // userID
					}

					$ip = '';
					if ( count( $p ) >= 5 ) {
						$ip = sanitize_text_field( $p[5] ); // ip detected from streaming server
					}
//Ex: admin-ajax.php?action=vmls&task=rtmp_login&s=1986206356&p[]=1986206356&p[]=PPVCamDemo&p[]=45a083b15f27b287470b99576afffd77&p[]=true&p[]=1&p[]=81.196.145.91


					$postID = 0;
					$ztime  = time();

					$wpdb->flush();
					$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type = %s LIMIT 0,1", $room, $options['custom_post'] ) );

					// verify if performer when trying to access as performer user (prevent hijacking)
					$invalid = 0;
					// only invalidate if trying to access as performer username as clients can broadcast in 2w
					if ( $broadcaster && $username == get_post_meta( $postID, 'performer' ) ) {
						if ( ! self::isPerformer( $userID, $postID ) ) {
							$invalid = 1;
						}
					}


					// verify
					$verified = 0;
					
					// rtmp key login for external apps: only for external apps is validated based on secret key, local app sessions should be already validated
					if ( ! $invalid ) {
						if ( $performer ) {
							$validKey = md5( 'vw' . $options['webKey'] . $userID . $postID );

							if ( $key == $validKey ) {
								$verified = 1;

								self::webSessionSave( $session, 1, 'rtmp_login_broadcaster', $ip );

								// detect transcoding to not alter source info
								$transcoding   = 0;
								$stream_webrtc = $room . '_webrtc';
								$stream_hls    = 'i_' . $room;
								if ( $username == $stream_hls || $username == $stream_webrtc ) {
									$transcoding = 1;
								}

								if ( $postID && ! $transcoding ) {
									update_post_meta( $postID, 'stream-protocol', 'rtmp' );
									update_post_meta( $postID, 'stream-type', 'external' );
									update_post_meta( $postID, 'stream-updated', $ztime );
								}
							}
						} else
						  {
							$validKeyView = md5( 'vw' . $options['webKey'] . $postID );
							if ( $key == $validKeyView ) {
								$verified = 1;

								self::webSessionSave( $session, 0, 'rtmp_login_viewer', $ip );
							}
						}
					}

					// after previously validaded session (above or by local apps login), returning result that was saved above

					// validate web login to streaming server
					$dir       = sanitize_text_field( $options['uploadsPath'] );
					$filename1 = $dir . "/_sessions/$session";
					if ( file_exists( $filename1 ) ) {
						echo esc_url_raw( implode( '', file( $filename1 ) ) );
						if ( $broadcaster ) {
							echo '&role=' . esc_attr(trim( $broadcaster ));
						}
					} else {
						// VideoWhisper=1&login=0&nsf=1&v=0&i=1&s=1482012032&p=5540&u=1
						echo 'VideoWhisper=1&login=0&nsf=no-session-file&v=' . esc_html( $verified ) . '&i=' . esc_html( $invalid ) .  '&s=' . esc_html( $session ) . '&p=' . esc_html( $postID ) . '&u=' . esc_html( $userID ) ;
						
						
					}

					// also update RTMP server IP in settings after authentication
					if ( $verified ) {

						if ( in_array( $options['webStatus'], array( 'auto', 'enabled' ) ) ) {
							$ip = self::get_ip_address();

							if ( ! strstr( $options['rtmp_restrict_ip'], $ip ) ) {
								$options['rtmp_restrict_ip'] .= ( $options['rtmp_restrict_ip'] ? ',' : '' ) . $ip;
								$updateOptions                = 1;
								echo '&rtmp_restrict_ip=' . esc_html( $options['rtmp_restrict_ip'] );
							}
						}

						// also enable webStatus if on auto (now secure with IP restriction enabled)
						if ( $options['webStatus'] == 'auto' ) {
							$options['webStatus'] = 'enabled';
							$updateOptions        = 1;
							echo '&webStatus=' . esc_attr( $options['webStatus'] );
						}

						if ( $updateOptions ) {
							update_option( 'VWliveWebcamsOptions', $options );
						}
					}

					?>
				
					<?php
					break;

				// ! rtmp_status : call from rtmp server, session control for rtmp, webrtc streams
				case 'rtmp_status':
					// remove_action('template_redirect', 'redirect_canonical');
					// remove_filter('template_redirect','redirect_canonical');

					// allow such requests only if feature is enabled (by default is not)
					if ( ! in_array( $options['webStatus'], array( 'enabled', 'strict' ) ) ) {
						self::rexit( 'denied=webStatusNotEnabled-' . $options['webStatus'] );
					}

					// allow only status updates from configured server IP
					if ( $options['rtmp_restrict_ip'] ) {
						$allowedIPs = explode( ',', $options['rtmp_restrict_ip'] );
						$requestIP  = self::get_ip_address();

						$found = 0;
						foreach ( $allowedIPs as $allowedIP ) {
							if ( $requestIP == trim( $allowedIP ) ) {
								$found = 1;
							}
						}

						if ( ! $found ) {
							self::rexit( 'denied=NotFromAllowedIP-' . $requestIP );
						}
					} else {
						self::rexit( 'denied=StatusServerIPnotConfigured' );
					}

					self::requirementMet( 'rtmp_status' );

					// start logging
					$dir       = $options['uploadsPath'];
					$filename1 = $dir . '/_rtmpStatus.txt';
					$dfile     = fopen( $filename1, 'w' );

					fputs( $dfile, 'VideoWhisper Log for RTMP Session Control' . "\r\n" );
					fputs( $dfile, 'Server Date: ' . "\r\n" . date( 'D M j G:i:s T Y' ) . "\r\n" );
					fputs( $dfile, '$_POST:' . "\r\n" . serialize( $_POST ) );

					$debugInfo = ':debugMode:';

					$ztime = time();

					$controlUsers    = array();
					$controlSessions = array();

					// ! RTP (WebRTC) sessions
					$rtpsessiondata = sanitize_text_field( stripslashes( $_POST['rtpsessions'] ) );
					file_put_contents( $options['uploadsPath'] . '/sessionsRTP', $rtpsessiondata );

					if ( version_compare( phpversion(), '7.0', '<' ) ) {
						$rtpsessions = unserialize( $rtpsessiondata );  // request is from trusted server
					} else {
						$rtpsessions = unserialize( $rtpsessiondata, array() );
					}

					$webrtc_test = 0;
					if ( is_array( $rtpsessions ) ) {
						foreach ( $rtpsessions as $rtpsession ) {
							$disconnect  = '';
							$streamQuery = array();
							$session     = '';

							// TransferIn = publish into server
							$zombie = 0;
							if ( $rtpsession['TransferIn'] == '0' && $rtpsession['TransferOut'] == '0' ) {
								$zombie = 1;
							}

							if ( ! $zombie ) {
								// active session start

								if ( ! $options['webrtc'] ) {
									$disconnect = 'WebRTC is disabled.';
								}

								$stream = sanitize_file_name( $rtpsession['streamName'] );

								if ( $rtpsession['streamQuery'] ) {

									parse_str( $rtpsession['streamQuery'], $streamQuery );

									if ( $userID = (int) $streamQuery['userID'] ) {
										$user = get_userdata( $userID );

										// $userName =  $options['userName']; if (!$userName) $userName='user_nicename';
										// if ($user->$userName) $username = urlencode(sanitize_file_name($user->$userName));

										$username = urlencode( sanitize_file_name( $user->user_nicename ) );
									}
								}

								if ( ! $username ) {
									$username = $stream; // fallback (external stream?)
								}

								$postID = 0;
								if ( $channel_id = intval( $streamQuery['channel_id'] ) ) {
									$postID = $channel_id;
									$post   = get_post( $channel_id );

								} else {
									$disconnect = 'No channel ID.';
								}

								$transcoding = intval( $streamQuery['transcoding'] ); // just a transcoding

								// WebRTC session vars

								$r = $streamQuery['room'];
								if ( ! $r ) {
									$r = $stream;
								}
								$u = $username;

								if ( $rtpsession['streamPublish'] == 'true' && $userID && ! $disconnect && ! $transcoding ) {

									$controlSession['streamMode'] = 'streamPublish';

									$s = sanitize_file_name( $username );
									$m = 'WebRTC Broadcaster';

									// webrtc broadcast test
									if ( ! $webrtc_test ) {
										self::requirementMet( 'webrtc_test' );
										$webrtc_test = 1;
									}

									$keyBroadcast = md5( 'vw' . $options['webKey'] . $userID . $channel_id );
									if ( $streamQuery['key'] != $keyBroadcast ) {
										$disconnect = 'WebRTC broadcast key mismatch.';
									}

									if ( ! $post ) {
										$disconnect = 'Channel post not found.';
									}
									// elseif (!VWliveWebcams::isPerformer($userID,$postID)) $disconnect = 'Only channel performers can broadcast.';

									if ( self::roomSuspended( $channel_id, $options ) ) $disconnect = __( 'This room was suspended.', 'ppv-live-webcams' );

									if ( $options['bannedNames'] ) {
										if ( $ban = self::containsAny( $r, $options['bannedNames'] ) ) {
																		$disconnect = "Room banned ($ban)!";
										}
									}

									if ( ! $disconnect ) {
										// clients can also broadcast in 2way (app)
										$isPerformer = self::isPerformer( $userID, $postID );

										// update public session
										$session = self::sessionUpdate( $s, $r, $isPerformer, 7, 0, 0, 0, $options, $userID, $postID, $streamQuery['ip'] );

										// update private session if in private mode
										$privateUID = intval( $streamQuery['privateUID'] );
										if ( $privateUID ) {
											$disconnect = self::privateSessionUpdate( $session, $post, $isPerformer, $privateUID, $options );
										}

										// generate external snapshot for external broadcaster
										if ( $isPerformer ) {
											// generate snapshot only if active for at least 5s and enable

											if ( $options['rtpSnapshots'] && floatval( $rtpsession['runSeconds'] ) > 5 ) {
												self::streamSnapshot( $session->session, false, $postID );
											}

											if ( $postID ) {
												update_post_meta( $postID, 'edate', $ztime );
												update_post_meta( $postID, 'btime', $btime );

												update_post_meta( $postID, 'stream-protocol', 'rtsp' );
												update_post_meta( $postID, 'stream-type', 'webrtc' );

												self::updateViewers( $postID, $r, $options );
											}

											$streamMode = get_post_meta( $postID, 'stream-mode', true ); // safari on pc encoding profile issues

											// transcode stream (from RTSP) if safari_pc (incorrect profile for h264)
											/*
											if (!$disconnect) if ($options['transcodingAuto']>=2)
											if ($streamMode == 'safari_pc')
												VWliveWebcams::transcodeStreamWebRTC($stream, $postID, $options);
												*/

										}
										// do recording if enabled and necessary, for all participants
										self::streamRecord( $session, $stream, 'rtsp', $postID, $options );

									}

									// end WebRTC broadcaster session
								}

								if ( $rtpsession['streamPlay'] == 'true' && ! $disconnect ) {

									// $s = $username .'_'. $stream;

									// sessionUpdate($username='', $room='', $broadcaster=0, $type=1, $strict=1, $updated=1, $clean=1, $options=null, $userID = 0, $postID=0, $ip = '')
									$isPerformer = self::isPerformer( $userID, $postID );

									// don't update from performer preview session
									if ( ! $isPerformer ) {
										$session = self::sessionUpdate( $username, $r, 0, 8, 0, 0, 0, $options, $userID, $postID, $streamQuery['ip'] );
									}

									$controlSession['streamMode'] = 'streamPlay';
									// end WebRTC playback
								}

								$controlSession['disconnect'] = $disconnect;

								$controlSession['session']  = $s;
								$controlSession['dS']       = strval( $dS );
								$controlSession['type']     = $session->type;
								$controlSession['room']     = $r;
								$controlSession['username'] = $u;

								// $controlSession['query'] = $rtpsession['streamQuery'];

								$controlSessions[ strval( $rtpsession['sessionId'] ) ] = $controlSession;
								// active session end
							}

							if ( $options['debugMode'] ) {
								$debugInfo .= "\r\n - RTP Session ($username #$userID @ $r #$postID)\r\n stream=(" . $rtpsession['streamQuery'] . ")\r\n streamPOST=" . serialize( $rtpsession ) . "\r\n session=" . serialize( $session ) . ( $zombie ? ' CONNECTING or ZOMBIE' : '' );
							}

							// end  foreach ($rtpsessions as $rtpsession)
						}
					}

					$controlSessionsS = serialize( $controlSessions );

					// debug update
					fputs( $dfile, "\r\nControl RTP Sessions: " . "\r\n" . $controlSessionsS );

					// users - RTMP clients
					$userdata = sanitize_text_field( stripslashes( $_POST['users'] ) );
					file_put_contents( $options['uploadsPath'] . '/sessionsUsers', $userdata );

					if ( version_compare( phpversion(), '7.0', '<' ) ) {
						$users = unserialize( $userdata );  // request is from trusted server
					} else {
						$users = unserialize( $userdata, array() );
					}

					$rtmp_test = 0;

					if ( is_array( $users ) ) {
						foreach ( $users as $user ) {

							// $rooms = explode(',',$user['rooms']); $r = $rooms[0];
							$r      = $user['rooms'];
							$stream = $username = $s = $user['session'];
							$u      = $user['username']; // empty

							$ztime      = time();
							$disconnect = '';

							if ( $options['bannedNames'] ) {
								if ( $ban = self::containsAny( $s, $options['bannedNames'] ) ) {
														$disconnect = "Name banned ($s,$ban)!";
								}
							}

							$isFfmpeg = 0;
							if ( in_array( substr( $user['session'], 0, 11 ), array( 'ffmpegSnap_', 'ffmpegInfo_', 'ffmpegView_', 'ffmpegSave_', 'ffmpegPush_') ) ) {
								$isFfmpeg = 1;
							}

							// kill snap/info sessions //+ , 'ffmpegView_'
							if ( in_array( substr( $user['session'], 0, 11 ), array( 'ffmpegSnap_', 'ffmpegInfo_') ) ) {
								if ( $options['ffmpegTimeout'] ) {
									if ( $user['runSeconds'] ) {
										if ( $user['runSeconds'] > $options['ffmpegTimeout'] ) {
																			$disconnect = 'FFMPEG timeout.';
										}
									}
								}
							}

							// channel broadcaster
							if ( ! $isFfmpeg )  //FFmpeg = system sessions, not user sessions
							{
								if ( $user['role'] == '1' ) {

									// an user is connected on rtmp: works
									if ( ! $rtmp_test ) {
										self::requirementMet( 'rtmp_test' );
										$rtmp_test = 1;
									}

									if ( ! $r ) {
										$r = $s; // use session as room if missing in older rtmp side
									}

									// sessionUpdate($username='', $room='', $broadcaster=0, $type=1, $strict=1, $updated=1);
									$session = self::sessionUpdate( $username, $r, 1, 9, 0, 0, 0, $options, -1 ); // not strict in case this is existing flash user

									if ( $session->type >= 2 ) {

										// update session meta
										$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

										if ( $session->meta ) {
											$userMeta = unserialize( $session->meta );
										}
										if ( ! is_array( $userMeta ) ) {
											$userMeta = array();
										}

										$userMeta['external']       = true;
										$userMeta['externalUpdate'] = $ztime;
										$userMetaS                  = serialize( $userMeta );

										$sql = "UPDATE `$table_sessions` set meta='$userMetaS' WHERE id ='" . $session->id . "'";
										$wpdb->query( $sql );

										// update post
										$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type=%s LIMIT 0,1", $session->room, $options['custom_post'] ) );

										if ( self::roomSuspended( $postID, $options ) ) $disconnect = __( 'This room was suspended.', 'ppv-live-webcams' );

										if ( $options['bannedNames'] ) {
											if ( $ban = self::containsAny( $r, $options['bannedNames'] ) ) {
												$disconnect = "Room name banned ($ban)!";
											}
										}

											// generate external snapshot for external broadcaster
										if ( $session->broadcaster ) {
											self::streamSnapshot( $session->session, true, $postID );
										}

											// detect transcoding to avoid altering source info
											$transcoding = 0;
										$stream_webrtc   = $session->room . '_webrtc';
										$stream_hls      = 'i_' . $session->room;
										if ( $s == $stream_hls || $s == $stream_webrtc ) {
											$transcoding = 1;
										}

										if ( $postID && ! $transcoding ) {

											$streamType = get_post_meta( $postID, 'stream-type', true );

											update_post_meta( $postID, 'edate', $ztime );
											//update_post_meta( $postID, 'btime', $channel->btime );

											update_post_meta( $postID, 'stream-protocol', 'rtmp' );
											update_post_meta( $postID, 'stream-type', 'external' );
											update_post_meta( $postID, 'stream-updated', $ztime );
											// update_post_meta($postID, '§§§§§§§§§§§', $ztime);

											// type changed
											if ( $streamType != 'external' ) {
												update_post_meta( $postID, 'updated_stream', $ztime );
											}

											self::updateViewers( $postID, $r, $options );
										}

										// transcode stream (from RTMP)
										if ( ! $disconnect ) {
											if ( $options['transcodingAuto'] >= 2 ) {
												self::transcodeStream( $session->room );
											}

											// do recording if enabled and necessary
											self::streamRecord( $session, $stream, 'rtmp', $postID, $options );

											// notify broadcaster live for external broadcasts
											$userWP = get_userdata( $session->uid );
											if ( $userWP ) {
												self::notifyLive( $userWP, $postID, $options );
											}
										}
									}
								} else // subscriber viewer
								{

									$session = self::sessionUpdate( $username, $r, 0, 10, 0, 0, 0, $options, -1 ); // not strict in case this is existing flash user

									/*
									if ( $session->type >= '2' ) {
										// update post
										$postID = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" . $r . "' and post_type='" . $options['custom_post'] . "' LIMIT 0,1" );
										if ( $postID ) {
											update_post_meta( $postID, 'wtime', $channel->wtime );
										}
									}
									*/
								}
							}

								$controlUser['disconnect'] = $disconnect;

								$controlUser['session']  = $s;
								$controlUser['dS']       = strval( $dS );
								$controlUser['type']     = $session->type;
								$controlUser['room']     = $r;
								$controlUser['username'] = $session->username;

								$controlUsers[ strval( $user['session'] ) ] = $controlUser;

							if ( $options['debugMode'] ) {
								$debugInfo .= "\r\n - RTMP Session: ($username @ $r #$postID) " . serialize( $session ) . ' userMetaS' . $userMetaS;
							}
						}
					}

					$controlUsersS = serialize( $controlUsers );

					fputs( $dfile, "\r\nControl RTMP Users: " . "\r\n" . $controlUsersS );

					fputs( $dfile, "\r\n" . $debugInfo );
					fclose( $dfile );

					$appStats = sanitize_text_field( stripslashes( $_POST['aS'] ) );
					file_put_contents( $options['uploadsPath'] . '/sessionsApp', $appStats );

					echo esc_html( 'VideoWhisper=1&usersCount=' . count( $users ) . '&controlUsers=' . urlencode( $controlUsersS ) . ' &controlSessions=' . urlencode( $controlSessionsS ) );

					// clean sessions in db
					self::billSessions();
					// rtmp_status end
					break;

				default:
					echo 'task=' . esc_html( $_GET['task'] ) . '&status=notImplemented';
			}

			// end vwcns_callback
			die();
		}


		// ! Presentation Functions


		// ! Utility Functions

		static function handle_upload( $file, $destination ) {
			// ex $_FILE['myfile']

			if ( ! function_exists( 'wp_handle_upload' ) ) {
			    require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			$movefile = wp_handle_upload( $file, array( 'test_form' => false ) );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				if ( ! $destination ) {
					return 0;
				}
				rename( $movefile['file'], $destination ); // $movefile[file, url, type]
				return 0;
			} else {
				/*
				 * Error generated by _wp_handle_upload()
				 * @see _wp_handle_upload() in wp-admin/includes/file.php
				 */
				return $movefile['error']; // return error
			}

		}

		static function roomURL( $room ) {

			$options = get_option( 'VWliveWebcamsOptions' );

			global $wpdb;

			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type = %s LIMIT 0,1", sanitize_file_name( $room ), sanitize_text_field( $options['custom_post'] ) ) );

			if ( $postID ) {
				return get_post_permalink( $postID );
			}
		}

	static function path2url( $file, $Protocol = 'https://' ) {
			if ( is_ssl() && $Protocol == 'http://' ) {
				$Protocol = 'https://';
			}

			$url = $Protocol . $_SERVER['HTTP_HOST'];

			// on godaddy hosting uploads is in different folder like /var/www/clients/ ..
			$upload_dir = wp_upload_dir();
			if ( strstr( $file, $upload_dir['basedir'] ) ) {
				return $upload_dir['baseurl'] . str_replace( $upload_dir['basedir'], '', $file );
			}

			// folder under WP path
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( strstr( $file, get_home_path() ) ) {
				return site_url() . '/' . str_replace( get_home_path(), '', $file );
			}

			// under document root
			if ( strstr( $file, $_SERVER['DOCUMENT_ROOT'] ) ) {
				return $url . str_replace( $_SERVER['DOCUMENT_ROOT'], '', $file );
			}

			return $url . $file;
		}



		static function format_time( $t, $f = ':' ) {
			// t = seconds, f = separator
			return sprintf( '%02d%s%02d%s%02d', floor( $t / 3600 ), $f, ( $t / 60 ) % 60, $f, $t % 60 );
		}


		static function format_age( $t ) {
			if ( $t < 41 ) {
				return __( 'LIVE', 'ppv-live-webcams' );
			}
			
			if ( $t + 3 > time() ) {
				return __( 'Never', 'ppv-live-webcams' );
			}
			
			return sprintf( '%d%s%d%s%d%s', floor( $t / 86400 ), 'd ', floor( $t / 3600 ) % 24, 'h ', floor( $t / 60 ) % 60, 'm' );
		}


		// ! Admin Side

		function admin_head() {

			$options = get_option( 'VWliveWebcamsOptions' );
			if ( get_post_type() != ( $options['custom_post'] ?? 'webcam' ) ) {
				return;
			}

			// hide add button
			echo '<!-- VideoWhisper / PaidVideochat / admin_head --><style type="text/css">
			.column-title
			{
				width: 150px !important;
			}

			.column-edate
			{
				width: 75px !important;
			}

			.column-vw_costPerMinute
			{
				width: 50px !important;
			}

			.column-vw_costPerMinuteGroup
			{
				width: 50px !important;
			}

			.column-vw_earningRatio
			{
				width: 50px !important;
			}

			.column-vw_featured
			{
				width: 50px !important;
			}

			.column-vwSuspended
			{
				width: 50px !important;
			}

			.column-customRoomLink
			{
				width: 50px !important;
			}

    #favorite-actions {display:none;}
    .add-new-h2{display:none;}
    .tablenav{display:none;}
    </style>';
		}


		static function settings_link( $links ) {
			$settings_link = '<a href="admin.php?page=live-webcams">' . __( 'Settings', 'ppv-live-webcams' ) . '</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}


		static function adminStudio() {
			?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>Assign Performers to Studios</h2>
Assigns existing user to studio (as performer): assigns performer role to user if necessary, creates a webcam listings and selects it as default for user, assigns performer and listing to studio to show in studio dashboard.
<br>Studio is a special role type that can be <a href="admin.php?page=live-webcams&tab=studio">enabled from settings </a>, depending on project.
<br>Warning: This will change user role to performer. If a different role is required, it should be adjusted after.
</div>
			<?php
			$options = self::getOptions();

			// input
			$user_id = intval( $_GET['user_id'] ?? 0);
			$user    = sanitize_text_field( $_POST['user'] ?? '');
			$studio  = sanitize_text_field( $_POST['studio'] ?? '' );

			// output

			if ( $user_id || $user ) {
				if ( $user ) {
					if ( filter_var( $user, FILTER_VALIDATE_EMAIL ) ) {
						$member = get_user_by( 'email', $user );
					} else {
						$member = get_user_by( 'login', $user );
					}

					if ( ! $member ) {
						echo __( 'User not found by email or login: ', 'ppv-live-webcams' ) . esc_html( $user );
					}
				}

				if ( $user_id > 0 ) {
					$member = get_userdata( $user_id );

					if ( ! $member ) {
						echo __( 'User not found by ID: ', 'ppv-live-webcams' ) . esc_html( $user_id );
					}
				}

				$currentUser = wp_get_current_user();

				if ( $member ) {
					if ( in_array( 'administrator', $member->roles ) || $user_id == $currentUser->ID ) {
										echo '<b>Assigning yourself or an administrator as performer is not permitted: changing role can break access to backend!</b> #' . esc_html( $member->ID );
										$member = null;
					}
				}

				if ( $member ) {
					if ( $studio ) {

						if ( filter_var( $studio, FILTER_VALIDATE_EMAIL ) ) {
							$memberStudio = get_user_by( 'email', $studio );
						} else {
							$memberStudio = get_user_by( 'login', $studio );
						}

						if ( ! $memberStudio ) {
							echo __( 'Studio not found: ', 'ppv-live-webcams' ) . esc_html( $studio );
						} else {

							// assign to studio
							update_user_meta( $member->ID, 'studioID', $memberStudio->ID );
							update_user_meta( $member->ID, 'studioLogin', $memberStudio->user_login );
							update_user_meta( $member->ID, 'studioDisabled', 0 );

							// set performer role
							wp_update_user(
								array(
									'ID'   => $member->ID,
									'role' => sanitize_text_field( $options['rolePerformer'] ),
								)
							);

							// also create a webcam listing
							// $newPerformer = get_userdata($member->ID);
							$name     = self::performerName( $member, $options );
							$webcamID = self::webcamPost( $name, $name, $user_id, $memberStudio->ID );
							update_user_meta( $member->ID, 'currentWebcam', $webcamID );

							echo '<div class="notice">Assigned Performer to Studio';
							echo '<br>Performer ID: ' . esc_html( $member->ID );
							echo '<br>Studio ID: ' . esc_html( $memberStudio->ID );
							echo '<br>Webcam ID: ' . esc_html( $webcamID ) . ' Webcam Listing: <a href="' . get_permalink( $webcamID ) . '">' . esc_html( $name ) . '</a>';
							echo '<br> <a class="button" href="admin.php?page=live-webcams-studio">Assign New</a>';
							echo '</div>';
						}
					} else {
										// pick studio
										echo '<div class=""><form action="admin.php?page=live-webcams-studio" method="post">';
										echo '<h3 class="ui header">Assign user to studio, as performer:</h3>';
										echo '<label>Studio<label><BR><input size="16" maxlength="32" type="text" name="studio" id="studio" value=""/> Studio member email or login.';
										echo '<BR><label>User Login<label><BR><input type="hidden" name="user" id="user" value="' . esc_attr( $member->user_login ) . '"/> ' . esc_html( $member->user_login );
										echo ': This user will become performer and get assigned to studio. Warning: Do not test assigning admin user as performer because this will change role and remove admin access. <BR><INPUT class="button primary" TYPE="submit" name="Assign" id="assign" value="Assign">';
										echo '</form></div>';

					}
				}
			} else {
				// pick user & studio
				echo '<div class=""><form action="admin.php?page=live-webcams-studio" method="post">';
				echo '<h3 class="ui header">Assign user to studio, as performer:</h3>';
				echo '<label>Studio<label><BR><input size="16" maxlength="32" type="text" name="studio" id="studio" value=""/><BR>Studio member email or login. Performer and webcam listing will show in studio dashboard for this member.';
				echo '<BR><label>User<label><BR><input size="16" maxlength="32" type="text" name="user" id="user" value=""/><BR>User email or login. This user will become performer and get assigned to studio. Warning: Do not test assigning admin user as performer because this will change role and remove admin access.';
				echo '<BR><INPUT class="button primary" TYPE="submit" name="Assign" id="assign" value="Assign">';
				echo '</form></div>';
			}

		}


		static function humanSize( $value ) {
			if ( $value > 1000000000000 ) {
				return number_format( $value / 1000000000000, 2, '.', ''  ) . 't';
			}
			if ( $value > 1000000000 ) {
				return number_format( $value / 1000000000, 2, '.', '' ) . 'g';
			}
			if ( $value > 1000000 ) {
				return number_format( $value / 1000000, 2, '.', ''  ) . 'm';
			}
			if ( $value > 1000 ) {
				return number_format( $value / 1000, 2, '.', ''  ) . 'k';
			}
			return $value;
		}


		static function adminStreams() {
			?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>Stream Session Control / PPV Live Webcams - HTML5 Paid Videochat</h2>
</div>
			<?php

			$options = get_option( 'VWliveWebcamsOptions' );

			if ( in_array( $options['webStatus'], array( 'enabled', 'strict', 'auto' ) ) ) {
				if ( file_exists( $path = $options['uploadsPath'] . '/_rtmpStatus.txt' ) ) {
					$url = self::path2url( $path );
					echo 'Found: <a target=_blank href="' . esc_url_raw( $url ) . '">last status request</a> ' . date( 'D M j G:i:s T Y', $ft = filemtime( $path ) );
					echo ' (' . ( time() - $ft ) . 's ago)';
					echo '<h4>Last App Instance Info</h4>';
					$sessionsVars = self::varLoad( $options['uploadsPath'] . '/sessionsApp' );
					if ( is_array( $sessionsVars ) ) {
						if ( array_key_exists( 'appInstanceInfo', $sessionsVars ) ) {
							echo 'Last App Instance: ' . esc_html( $sessionsVars['appInstanceInfo'] );
						}

						ksort( $sessionsVars );

						echo '<h3>Streaming Host Limits</h3>';
						foreach ( $sessionsVars as $key => $value ) {
							if ( substr( $key, 0, 5 ) == 'limit' ) {
												echo esc_html( $key ) . ': ' . esc_html( $value ) . ( strstr( strtolower( $key ), 'rate' ) && ! strstr( strtolower( $key ), 'disconnect' ) ? 'bytes = ' . esc_html( self::humanSize( 8 * $value ) . 'bits' ) : '' )  . '<br>';
							}
						}

						echo '<h3>Disconnects & Rejects</h3>';
						foreach ( $sessionsVars as $key => $value ) {
							if ( substr( $key, 0, 6 ) == 'reject' || substr( $key, 0, 10 ) == 'disconnect' ) {
												echo esc_html( $key ) . ': ' . esc_html( $value ) . '<br>';
							}
						}

						echo '<h3>All Parameters</h3>';
						foreach ( $sessionsVars as $key => $value ) {
							echo esc_html( $key ) . ': ' . esc_html( $value ) . ( strstr( strtolower( $key ), 'rate' ) && ! strstr( strtolower( $key ), 'disconnect' ) ? ' = ' . esc_html( self::humanSize( 8 * $value ) ) : '' ) . '; ';
						}

						?>
						<br>- Rates are in bytes. Multiply bytes by 8 to get bits.
						<br>- WebRTC streams are reported as RTP.
						<br>- App sessions are temporary, active only while there are active streams. A new session with new stats is created after inactivity.
						<br style="clear:both">
						<?php

					}

					echo '<h3>Last Status Request Log</h3>';
					echo '<textarea readonly cols="120" rows="8">' . esc_textarea( file_get_contents( $path ) ) . '</textarea>';

				} else {
					echo 'Warning: Status log file not found!';
				}
			} else {
				echo 'Warning: webStatus not enabled/strict/auto!';
			}

		}


		function adminLive() {
			?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>Live Admin for VideoWhisper PPV Live Webcams - Paid Videochat</h2>
</div>

Flash was discontinued.
			<?php
			$swfurl  = plugin_dir_url( __FILE__ ) . 'videowhisper/videowhisperAdmin.swf?ssl=1';
			$swfurl .= '&prefix=' . urlencode( admin_url() . 'admin-ajax.php?action=vmls&task=' );
			$swfurl .= '&extension=' . urlencode( '_none_' );
			$swfurl .= '&ws_res=' . urlencode( plugin_dir_url( __FILE__ ) . 'videowhisper/' );
			$swfurl  = esc_url_raw( $swfurl );

			$bgcolor = '#333333';
			/*
			echo <<<HTMLCODE
			<div id="videowhisper_container" style="width:100%; height:800px">
			<object id="videowhisper_admin" width="100%" height="100%" type="application/x-shockwave-flash" data="$swfurl">
			<param name="movie" value="$swfurl"></param><param bgcolor="$bgcolor"><param name="scale" value="noscale" /> </param><param name="salign" value="lt"></param><param name="allowFullScreen"
			value="true"></param><param name="allowscriptaccess" value="always"></param>
			</object>
			</div>
			<br style="clear:both">
			HTMLCODE;
			*/
			?>
This Flash based tool allows monitoring all RTMP connected users from Flash apps or external RTMP encoders like OBS, identifying their usage, IP, spying on the publishing webcam/microphone streams.
Select rtmp side app to connect to from <a href="admin.php?page=live-webcams&tab=server
">server settings</a>.
<br>To work with session control, application will join first created room to generate access keys.
			<?php

			$options = get_option( 'VWliveWebcamsOptions' );

			// get first room to use for session control
			$args = array(
				'post_type'      => $options['custom_post'],
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'offset'         => 0,
				'orderby'        => 'post_date',
				'order'          => 'ASC',
			);

			$postslist = get_posts( $args );
			if ( count( $postslist ) > 0 ) {
				foreach ( $postslist as $item ) {
					$keyView     = md5( 'vw' . $options['webKey'] . $item->ID );
					$rtmp_server = sanitize_text_field( $options['rtmp_server_admin'] ) . '?' . urlencode( $username ) . '&' . urlencode( $item->post_title ) . '&' . $keyView . '&0&videowhisper';

					echo 'Using room #' . esc_html( $item->ID ) . ' (' . esc_html( $item->post_title ) . ') with address keys ' . esc_html( $rtmp_server ) . ' to access RTMP streaming app.';
				}
			}

		}



		static function adminSessions() {

			$filterMode = sanitize_file_name( $_GET['filterMode'] ?? '' );

			if ( ! $filterMode ) {
				$filterMode = 'paid';
			}
			?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>Client Session Logs: <?php echo esc_html( $filterMode ); ?></h2>
</div>

<a href="admin.php?page=live-webcams-sessions&filterMode=all">All</a> |
<a href="admin.php?page=live-webcams-sessions&filterMode=paid">Paid</a> |
<a href="admin.php?page=live-webcams-sessions&filterMode=private">Private Calls</a>

			<?php

			self::billSessions();

			global $wpdb;

			if ( $filterMode == 'private' ) {

				$cnd = '';

				$table_sessions = $wpdb->prefix . 'vw_vmls_private';

				$sql      = $wpdb->prepare("SELECT * FROM $table_sessions $cnd ORDER by cedate DESC");
				$sessions = $wpdb->get_results( $sql );

				if ( $wpdb->num_rows > 0 ) {
					echo '<table class="widefat fixed">';
					echo '<thead><tr>';
					echo '<td>Client<BR># CID</td>';
					echo '<td>Performer<BR># PID</td>';
					echo '<td>Client Start<br>Client End</td>';
					echo '<td>Performer Start<br>Performer End</td>';
					echo '<td>Room<BR># ID</td>';
					echo '<td>Status</td>';
					echo '<td>Meta</td>';
					echo '</tr></thead>';

					foreach ( $sessions as $session ) {
						echo '<tr>';
						echo '<td>' . esc_html( $session->client ) . '<BR># ' . esc_html( $session->cid ) . '</td>';
						echo '<td>' . esc_html( $session->performer ) . '<BR># ' . esc_html( $session->pid ) . '</td>';
						echo '<td>' . ( $session->csdate ? date( 'Y M j H:i:s', $session->csdate ) : '--' ) . '<BR>' . ( $session->cedate ? date( 'M j H:i:s', $session->cedate ) : '--' ) . '<BR>' . intval( $session->cedate - $session->csdate ) . 's</td>';
						echo '<td>' . ( $session->psdate > 0 ? date( 'M j H:i:s', $session->psdate ) : '--' ) . '<BR>' . date( 'M j H:i:s', $session->pedate ) . '<BR>' . ( $session->psdate > 0 ? intval( $session->pedate - $session->psdate ) : '--' ) . 's</td>';
						echo '<td>' . esc_html( $session->room ) . '<br>R# ' . esc_html( $session->rid ) .'<br>S# ' . esc_html( $session->id ) . '</td>';

						$statusLabel = '';
						switch ( $session->status ) {
							case '0':
								$statusLabel = 'Active';
								break;
							case '1':
								$statusLabel = 'Ended';
								break;
							case '2':
								$statusLabel = 'Billed';
								break;
						}
						echo '<td>' . esc_html( $session->status ) . '<BR>' . esc_html( $statusLabel ) . '</td>';
						echo '<td>' . esc_html( print_r( unserialize( $session->meta ) , true ) ) . '</td>';

						echo '</tr>';
					}

					echo '</table>';
				}
			} else {
				// group mode
				if ( $filterMode == 'paid' ) {
					$cnd = 'WHERE rmode<>0';
				}
				if ( $filterMode == 'all' ) {
					$cnd = '';
				}

				$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

				$sql      = $wpdb->prepare("SELECT * FROM $table_sessions $cnd ORDER by edate DESC");
				$sessions = $wpdb->get_results( $sql );

				if ( $wpdb->num_rows > 0 ) {
					echo '<table class="widefat fixed">';
					echo '<thead><tr>';
					echo '<td>User<BR>IP</td>';
					echo '<td>Room<BR>Room ID</td>';
					echo '<td>Start<br>End</td>';
					echo '<td>Performer Active<br>With User</td>';
					echo '<td>Paid Mode</td>';
					echo '<td>Status</td>';
					echo '<td>Type</td>';
					echo '<td>Room Options</td>';
					echo '<td>Meta</td>';
					echo '</tr></thead>';

					foreach ( $sessions as $session ) {
						echo '<tr>';
						echo '<td>' . esc_html( $session->session ) . '<BR>' . esc_html( $session->ip ) . '<BR>' . ( $session->broadcaster ? 'Broadcaster' : 'Client' ) . ' U# ' . esc_html( $session->uid ) . '<BR>S# ' . esc_html( $session->id ) . '</td>';
						echo '<td>' . esc_html( $session->room ) . '<BR># ' . esc_html( $session->rid ) . '</td>';
						echo '<td>' . date( 'M j H:i:s', $session->sdate ) . '<BR>' . date( 'M j H:i:s', $session->edate ) . '<BR>' . intval( $session->edate - $session->sdate ) . 's</td>';
						echo '<td>' . ( $session->rsdate > 0 ? date( 'M j H:i:s', $session->rsdate ) : '--' ) . '<BR>' . ( $session->rsdate > 0 ? intval( $session->redate - $session->rsdate ) : '--' ) . 's</td>';
						echo '<td>' . esc_html( $session->rmode ) . '</td>';

						$statusLabel = '';
						switch ( $session->status ) {
							case '0':
								$statusLabel = 'Live';
								break;
							case '1':
								$statusLabel = 'Ended';
								break;
							case '2':
								$statusLabel = 'Billed';
								break;
						}
						echo '<td>' . esc_html( $session->status ) . '<BR>' . esc_html( $statusLabel ) . '</td>';
						echo '<td>' . esc_html( $session->type ) . '</td>';
						echo '<td>' . esc_html( print_r( unserialize( $session->roptions ) , true ) ) . '</td>';
						echo '<td>' . esc_html( print_r( unserialize( $session->meta ) , true ) ) . '</td>';

						echo '</tr>';
					}

					echo '</table>';
				}
			}
			?>
			* This section also updates billing.
			<?php
		}



	}


}

// instantiate
if ( class_exists( 'VWliveWebcams' ) ) {
	$liveWebcams = new VWliveWebcams();
}

// Actions and Filters
if ( isset( $liveWebcams ) ) {
	register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	register_activation_hook( __FILE__, array( &$liveWebcams, 'activation' ) );

	add_action( 'init', array( &$liveWebcams, 'init' ) );

	add_action( 'plugins_loaded', array( &$liveWebcams, 'plugins_loaded' ) );

	// admin
	add_action( 'admin_menu', array( &$liveWebcams, 'admin_menu' ) );
	add_action( 'admin_bar_menu', array( &$liveWebcams, 'admin_bar_menu' ), 90 );

	add_action( 'admin_head', array( &$liveWebcams, 'admin_head' ) );

	// register
	add_action( 'register_form', array( &$liveWebcams, 'register_form' ) );
	add_action( 'user_register', array( &$liveWebcams, 'user_register' ) );

	add_filter( 'wp_authenticate_user', array( &$liveWebcams, 'wp_authenticate_user' ), 10, 2 );

	// login
	add_action( 'login_enqueue_scripts', array( 'VWliveWebcams', 'login_logo' ) );
	// add_filter( 'login_headertitle', array('VWliveWebcams','login_headertitle') );
	// add_filter( 'login_headerurl', array('VWliveWebcams','login_headerurl') );
	add_filter( 'login_redirect', array( 'VWliveWebcams', 'login_redirect' ), 10, 3 );

	add_filter( 'the_title', array( 'VWliveWebcams', 'the_title' ), 10, 2 );
	add_filter( 'query_vars', array( 'VWliveWebcams', 'query_vars' ) );


	// add_filter( 'sidebars_widgets', array(&$liveWebcams,'sidebars_widgets') );
	// add_action( 'get_sidebar', array(&$liveWebcams,'get_sidebar') );

	// page template
	add_filter( 'single_template', array( &$liveWebcams, 'single_template' ) );
	add_filter( 'page_template', array( &$liveWebcams, 'single_template' ) );

	/* Only load code that needs BuddyPress to run once BP is loaded and initialized. */
	function liveWebcamsBP_init() {
		if ( class_exists( 'BP_Group_Extension' ) ) {
			require dirname( __FILE__ ) . '/inc/buddypress.php';
		}
	}


	add_action( 'bp_init', 'liveWebcamsBP_init' );

}



?>