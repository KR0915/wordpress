<?php
/**
 * Meeting Hub Functions
 *
 * @package MHUB
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mhub_delete_meeting' ) ) {
	/**
	 * Delete Meeting
	 *
	 * @param int $id Delete Id.
	 *
	 * @return int|boolean
	 *
	 * @since 1.0.0
	 */
	function mhub_delete_meeting( $id ) {
		delete_post_meta( $id, 'mhub__meeting_settings', null );
		$deleted = wp_delete_post( $id );
		return $deleted;
	}
}


if ( ! function_exists( 'mhub_meetings' ) ) {
	/**
	 * Fetch jitsi all meetings
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	function mhub_meetings() {

		$user_id = get_current_user_id();

		$args = array(
			'post_type'      => 'mhub_meeting',
			'post_status'    => 'published',
			'posts_per_page' => -1,
		);

		// If the user does not have the manage_options capability, limit the query to their own meetings.
		if ( ! current_user_can( 'manage_options' ) ) {
			$args['author'] = $user_id;
		}

		$wp_query = new \WP_Query( $args );
		$results  = $wp_query->posts;

		$meetings = array();

		foreach ( $results as $result ) {
			$meeting         = array();
			$settings        = get_post_meta( $result->ID, 'mhub__meeting_settings', true );
			$settings        = maybe_unserialize( $settings );
			$settings['url'] = get_permalink( $result->ID );

			$meeting['id']       = $result->ID;
			$meeting['title']    = $result->post_title;
			$meeting['settings'] = $settings;
			$meeting['date']     = $result->post_date;
			$meetings[]          = $meeting;

		}

		wp_reset_postdata();

		return $meetings;
	}

}


if ( ! function_exists( 'mhub_meeting' ) ) {

	/**
	 * Fetch a single meeting from the DB
	 *
	 * @param int $id Meeting Id.
	 *
	 * @return object
	 *
	 * @since 1.0.0
	 */
	function mhub_meeting( $id ) {
		$meeting = array();

		$post                = get_post( $id );
		$settings            = get_post_meta( $id, 'mhub__meeting_settings', true );
		$meeting_description = get_post_meta( $id, 'meeting_description', true );

		$meeting['id']                  = $post->ID;
		$meeting['title']               = $post->post_title;
		$meeting['settings']            = maybe_unserialize( $settings );
		$meeting['meeting_description'] = $meeting_description;
		$meeting['date']                = $post->post_date;

		return $meeting;
	}
}

if ( ! function_exists( 'mhub_random_domain' ) ) {

	/**
	 * GetRandomDomain
	 *
	 * This function returns a randomly selected domain from a predefined list.
	 *
	 * @return string The randomly selected domain.
	 */
	function mhub_random_domain() {
		static $shuffled_domains = null;

		if ( null === $shuffled_domains ) {
			$domains = array(
				'webconf.viviers-fibre.net',
				'jitsi.member.fsf.org',
				'meet.evolix.org',
				'video.devloprog.org',
			);

			shuffle( $domains );
			$shuffled_domains = $domains;
		}

		$random_index = wp_rand( 0, count( $shuffled_domains ) - 1 );
		return $shuffled_domains[ $random_index ];
	}
}

if ( ! function_exists( 'mhub_generate_random_room' ) ) {

	/**
	 * GenerateRandomString
	 *
	 * This function generates a random string of a specified length using alphanumeric characters.
	 *
	 * @param int $length The length of the random string to generate.
	 * @return string The randomly generated string.
	 */
	function mhub_generate_random_room( $length ) {
		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$result     = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$random_index = wp_rand( 0, strlen( $characters ) - 1 );
			$result      .= $characters[ $random_index ];
		}

		return $result;
	}
}

if ( ! function_exists( 'mhub_prepare_zoom_meeting_data' ) ) {
	/**
	 * Prepare data for creating a Zoom meeting.
	 *
	 * This function prepares data required for creating a Zoom meeting based on the provided parameters.
	 *
	 * @param string $title The title of the meeting.
	 * @param array  $meeting_settings The settings for the meeting, including start date, agenda, timezone, etc.
	 * @param int    $duration The duration of the meeting in minutes.
	 *
	 * @return array The prepared meeting data including topic, agenda, start time, timezone, password, duration, and settings.
	 */
	function mhub_prepare_zoom_meeting_data( $title, $meeting_settings, $duration ) {
		$start_time           = $meeting_settings['startDateTime'];
		$alternative_host_ids = '';

		// Create a DateTime object from the given string.
		$datetime = new DateTime( $start_time );

		// Set the time zone to UTC.
		$datetime->setTimezone( new DateTimeZone( 'UTC' ) );

		// Format the date and time to ISO 8601 format in UTC.
		$utc_time = $datetime->format( 'Y-m-d\TH:i:s\Z' );

		if ( ! empty( $meeting_settings['alternative_host'] ) ) {
			$alternative_host_arr = array();

			foreach ( $meeting_settings['alternative_host'] as $meeting_host ) {
				$alternative_host_arr[] = $meeting_host->value;
			}

			if ( count( $alternative_host_arr ) > 1 ) {
				$alternative_host_ids = implode( ',', $alternative_host_arr );
			} else {
				$alternative_host_ids = $alternative_host_arr[0];
			}
		}

		$meeting_arr = array(
			'topic'      => $title,
			'agenda'     => ! empty( $meeting_settings['agenda'] ) ? $meeting_settings['agenda'] : '',
			'start_time' => $utc_time,
			'timezone'   => $meeting_settings['meeting_timezone'],
			'password'   => ! empty( $meeting_settings['password'] ) ? $meeting_settings['password'] : false,
			'duration'   => $duration,
			'settings'   => array(
				'meeting_authentication' => ! empty( $meeting_settings['meeting_authentication'] ) ? true : false,
				'join_before_host'       => ! empty( $meeting_settings['join_before_host'] ) ? true : false,
				'host_video'             => ! empty( $meeting_settings['option_host_video'] ) ? true : false,
				'participant_video'      => ! empty( $meeting_settings['option_participants_video'] ) ? true : false,
				'mute_upon_entry'        => ! empty( $meeting_settings['option_mute_participants'] ) ? true : false,
				'auto_recording'         => ! empty( $meeting_settings['auto_recording'] ) ? $meeting_settings['auto_recording'] : 'none',
				'alternative_hosts'      => isset( $alternative_host_ids ) ? $alternative_host_ids : '',
				'waiting_room'           => isset( $meeting_settings['disable_waiting_room'] ) && ( 'yes' === $meeting_settings['disable_waiting_room'] ) ? false : true,
			),
		);

		return $meeting_arr;
	}
}

if ( ! function_exists( 'mhub_prepare_webinar' ) ) {
	/**
	 * Prepare data for creating a Zoom webinar.
	 *
	 * This function prepares data required for creating a Zoom webinar based on the provided parameters.
	 *
	 * @param string $title The title of the webinar.
	 * @param array  $meeting_settings The settings for the webinar, including start date, agenda, timezone, etc.
	 * @param int    $duration The duration of the webinar in minutes.
	 *
	 * @return array The prepared webinar data including topic, agenda, start time, timezone, password, duration, and settings.
	 */
	function mhub_prepare_webinar( $title, $meeting_settings, $duration ) {
		$start_time           = $meeting_settings['startDateTime'];
		$alternative_host_ids = '';

		// Create a DateTime object from the given string.
		$datetime = new DateTime( $start_time );

		// Set the time zone to UTC.
		$datetime->setTimezone( new DateTimeZone( 'UTC' ) );

		// Format the date and time to ISO 8601 format in UTC.
		$utc_time = $datetime->format( 'Y-m-d\TH:i:s\Z' );

		if ( ! empty( $meeting_settings['alternative_host'] ) ) {
			$alternative_host_arr = array();

			foreach ( $meeting_settings['alternative_host'] as $meeting_host ) {
				$alternative_host_arr[] = $meeting_host->value;
			}

			if ( count( $alternative_host_arr ) > 1 ) {
				$alternative_host_ids = implode( ',', $alternative_host_arr );
			} else {
				$alternative_host_ids = $alternative_host_arr[0];
			}
		}

		$webinar_arrr = array(
			'topic'      => $title,
			'agenda'     => ! empty( $meeting_settings['agenda'] ) ? $meeting_settings['agenda'] : '',
			'start_time' => $utc_time,
			'timezone'   => $meeting_settings['meeting_timezone'],
			'password'   => ! empty( $meeting_settings['password'] ) ? $meeting_settings['password'] : false,
			'duration'   => $duration,
			'settings'   => array(
				'host_video'             => ! empty( $meeting_settings['option_host_video'] ) ? true : false,
				'panelists_video'        => ! empty( $meeting_settings['panelists_video'] ) ? true : false,
				'practice_session'       => ! empty( $meeting_settings['practice_session'] ) ? true : false,
				'hd_video'               => ! empty( $meeting_settings['hd_video'] ) ? true : false,
				'allow_multiple_devices' => ! empty( $meeting_settings['allow_multiple_devices'] ) ? true : false,
				'alternative_hosts'      => isset( $alternative_host_ids ) ? $alternative_host_ids : '',
				'auto_recording'         => ! empty( $meeting_settings['auto_recording'] ) ? $meeting_settings['auto_recording'] : 'none',
			),
		);

		return $webinar_arrr;
	}
}


if ( ! function_exists( 'mhub_is_server_auth_prepare' ) ) {
	/**
	 * Check if the server authentication for Zoom is prepared by verifying the presence
	 * of OAuth account ID, OAuth client ID, and OAuth client secret in the Zoom settings.
	 *
	 * @return bool Whether the server authentication is prepared or not.
	 */
	function mhub_is_server_auth_prepare() {
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

}

if ( ! function_exists( 'mhub_timezone_offset' ) ) {
	/**
	 * Retrieve the timezone offset in hours and minutes.
	 *
	 * This function fetches the timezone offset from WordPress options. If the offset is empty,
	 * it defaults to 'UTC'. The offset is returned in the format specified by WordPress options.
	 *
	 * @return string The timezone offset in hours and minutes.
	 */
	function mhub_timezone_offset() {
		$fetched_offset = get_option( 'timezone_string' );
		$offset         = empty( $fetched_offset ) ? 'UTC' : $fetched_offset;
		return $offset;
	}

}

if ( ! function_exists( 'mhub_convert_to_minutes' ) ) {
	/**
	 * Convert hours and minutes to minutes.
	 *
	 * This function takes the given hour and minute values and converts them into minutes.
	 * It multiplies the hour value by 60 and adds the minute value to get the total minutes.
	 *
	 * @param int $hour The hour value to be converted.
	 * @param int $minute The minute value to be converted.
	 * @return int The total minutes calculated from the provided hour and minute values.
	 */
	function mhub_convert_to_minutes( $hour, $minute ) {
		$hour   = $hour * 60;
		$result = $hour + $minute;
		return $result;
	}
}

if ( ! function_exists( 'mhub_pro_plugin_exists' ) ) {
	/**
	 * Checks if the MeetingHub Pro plugin is installed.
	 *
	 * @return bool True if MeetingHub Pro plugin exists, false otherwise.
	 */
	function mhub_pro_plugin_exists() {
		return file_exists( WP_PLUGIN_DIR . '/meetinghub-pro/meetinghub-pro.php' );
	}
}


if ( ! function_exists( 'mhub_insert_meeting' ) ) {
	/**
	 * Inserts a new meeting post into the database.
	 *
	 * @param string $title              The title of the meeting.
	 * @param array  $meeting_settings   The settings for the meeting.
	 * @param string $meeting_description The description of the meeting.
	 *
	 * @return int|WP_Error The ID of the inserted meeting post on success, or a WP_Error object on failure.
	 */
	function mhub_insert_meeting( $title, $meeting_settings, $meeting_description ) {
		$un_meeting_settings       = maybe_unserialize( $meeting_settings );
		$meeting_description_array = json_decode( $meeting_description, true );
		$meeting_description       = isset( $meeting_description_array['content'] ) ? wp_kses_post( $meeting_description_array['content'] ) : '';

		$new_meeting = array(
			'post_title'   => $title,
			'post_type'    => 'mhub_meeting',
			'post_status'  => 'publish',
			'post_content' => '',
			'meta_input'   => array(
				'mhub__meeting_settings' => $meeting_settings,
				'meeting_description'    => $meeting_description,
			),

		);

		$meeting_id = wp_insert_post( $new_meeting );

		$thumbnail_id = $un_meeting_settings['image_id'];

		// Set post thumbnail.
		if ( ! empty( $thumbnail_id ) ) {
			set_post_thumbnail( $meeting_id, $thumbnail_id );
		}

		if ( is_wp_error( $meeting_id ) ) {
			$meeting_id->add_data( array( 'status' => 400 ) );
			return $meeting_id;
		}

		return $meeting_id;
	}

}

if ( ! function_exists( 'mhub_update_meeting' ) ) {
	/**
	 * Updates an existing meeting post in the database.
	 *
	 * @param string $title              The new title of the meeting.
	 * @param array  $meeting_settings   The new settings for the meeting.
	 * @param int    $meeting_id         The ID of the meeting post to update.
	 * @param string $meeting_description The new description of the meeting.
	 *
	 * @return int|WP_Error The number of affected rows on success, or a WP_Error object on failure.
	 */
	function mhub_update_meeting( $title, $meeting_settings, $meeting_id, $meeting_description ) {
		$un_meeting_settings       = maybe_unserialize( $meeting_settings );
		$meeting_description_array = json_decode( $meeting_description, true );
		$meeting_description       = isset( $meeting_description_array['content'] ) ? wp_kses_post( $meeting_description_array['content'] ) : '';

		$arg = array(
			'ID'           => $meeting_id,
			'post_title'   => $title,
			'post_type'    => 'mhub_meeting',
			'post_status'  => 'publish',
			'post_content' => '',
			'meta_input'   => array(
				'mhub__meeting_settings' => $meeting_settings,
				'meeting_description'    => $meeting_description,
			),
		);

		$updated = wp_update_post( $arg );

		$thumbnail_id = isset( $un_meeting_settings['image_id'] ) ? $un_meeting_settings['image_id'] : '';

		// Check if the image ID is empty and remove the thumbnail if it is.
		if ( empty( $thumbnail_id ) ) {
			delete_post_thumbnail( $meeting_id );
		} else {
			// Set the post thumbnail if the image ID is not empty.
			set_post_thumbnail( $meeting_id, $thumbnail_id );
		}

		if ( ! $updated ) {
			return new WP_Error(
				'rest_not_updated',
				__( 'Sorry, the address could not be updated.', 'meetinghub' ),
				array( 'status' => 400 )
			);
		}
	}
}

if ( ! function_exists( 'mhub_prepare_webex_meeting_data' ) ) {
	/**
	 * Prepares meeting data for Webex API.
	 *
	 * @param string $title The title of the meeting.
	 * @param array  $meeting_settings Meeting settings including start time, agenda, password, and more.
	 * @param int    $duration The duration of the meeting in minutes.
	 *
	 * @return array Formatted meeting data for Webex API.
	 */
	function mhub_prepare_webex_meeting_data( $title, $meeting_settings, $duration ) {
		$gmt_array      = mhub_get_gmt_offset( $meeting_settings['meeting_timezone'] );
		$gmt_offset_val = $gmt_array['gmt_offset_val'];

		$start_timestamp  = strtotime( $meeting_settings['startDateTime'] ) + ( $gmt_offset_val );
		$start_time       = gmdate( 'Y-m-d H:i:s', $start_timestamp );
		$duration_seconds = $duration * 60;

		// Calculate the end time by adding the duration to the start timestamp.
		$end_timestamp = $start_timestamp + $duration_seconds;
		$end_time      = gmdate( 'Y-m-d H:i:s', $end_timestamp );

		$meeting_arr = array(
			'title'                    => $title,
			'agenda'                   => isset( $meeting_settings['agenda'] ) ? $meeting_settings['agenda'] : null,
			'password'                 => isset( $meeting_settings['password'] ) ? $meeting_settings['password'] : null,
			'start'                    => $start_time,
			'end'                      => $end_time,
			'timezone'                 => isset( $meeting_settings['meeting_timezone'] ) ? $meeting_settings['meeting_timezone'] : null,
			'enabledAutoRecordMeeting' => isset( $meeting_settings['auto_record'] ) ? (bool) $meeting_settings['auto_record'] : false,
			'enabledJoinBeforeHost'    => isset( $meeting_settings['join_before_host'] ) ? (bool) $meeting_settings['join_before_host'] : false,
			'enabledBreakoutSessions'  => isset( $meeting_settings['breakout_sessions'] ) ? (bool) $meeting_settings['breakout_sessions'] : false,
			'enableAutomaticLock'      => isset( $meeting_settings['automatic_lock'] ) ? (bool) $meeting_settings['automatic_lock'] : false,
		);

		if ( ! $meeting_arr['enabledJoinBeforeHost'] ) {
			$meeting_arr['enableConnectAudioBeforeHost'] = false;
		}

		if ( $meeting_arr['enableAutomaticLock'] ) {
			$meeting_arr['automaticLockMinutes'] = isset( $meeting_settings['lock_minutes'] ) ? (int) $meeting_settings['lock_minutes'] : 0;
		}

		return $meeting_arr;
	}
}

if ( ! function_exists( 'mhub_get_gmt_offset' ) ) {
	/**
	 * Get the GMT offset and its value for a given timezone.
	 *
	 * @param string $timezone The timezone identifier, e.g., 'Asia/Dhaka'.
	 *
	 * @return array An array containing the GMT offset string and its value.
	 */
	function mhub_get_gmt_offset( $timezone ) {
		$dtz               = new DateTimeZone( $timezone );
		$offset_in_seconds = $dtz->getOffset( new DateTime() );
		$hours             = floor( abs( $offset_in_seconds ) / 3600 );
		$minutes           = floor( ( abs( $offset_in_seconds ) % 3600 ) / 60 );
		$gmt_offset        = ( $offset_in_seconds < 0 ? '-' : '+' ) . sprintf( '%02d:%02d', $hours, $minutes );

		list( $hours, $minutes ) = explode( ':', $gmt_offset );
		$gmt_offset_val          = ( $hours * 3600 ) + ( $minutes * 60 );

		return array(
			'gmt_offset'     => $gmt_offset,
			'gmt_offset_val' => $gmt_offset_val,
		);
	}
}

if ( ! function_exists( 'mhub_prepare_google_meet_data' ) ) {
	/**
	 * Prepares meeting data for Webex API.
	 *
	 * @param string $title The title of the meeting.
	 * @param array  $meeting_settings Meeting settings including start time, agenda, password, and more.
	 * @param int    $duration The duration of the meeting in minutes.
	 *
	 * @return array Formatted meeting data for Webex API.
	 */
	function mhub_prepare_google_meet_data( $title, $meeting_settings, $duration ) {
		$gmt_array      = mhub_get_gmt_offset( $meeting_settings['meeting_timezone'] );
		$gmt_offset_val = $gmt_array['gmt_offset_val'];

		// Calculate timestamps.
		$start_timestamp    = strtotime( $meeting_settings['startDateTime'] ) + $gmt_offset_val;
		$iso_start_datetime = gmdate( 'Y-m-d\TH:i:sP', $start_timestamp );
		$duration_seconds   = $duration * 60;
		$end_timestamp      = $start_timestamp + $duration_seconds;
		$iso_end_datetime   = gmdate( 'Y-m-d\TH:i:sP', $end_timestamp );

		// Prepare meeting array.
		$meeting_arr = array(
			'summary'        => $title,
			'description'    => $meeting_settings['meeting_summary'],
			'start'          => array(
				'dateTime' => $iso_start_datetime,
				'timeZone' => $meeting_settings['meeting_timezone'],
			),
			'end'            => array(
				'dateTime' => $iso_end_datetime,
				'timeZone' => $meeting_settings['meeting_timezone'],
			),
			'timezone'       => $meeting_settings['meeting_timezone'],
			'attendees'      => isset( $meeting_settings['attendees'] ) ? $meeting_settings['attendees'] : array(),
			'reminders'      => array(
				'useDefault' => false,
				'overrides'  => array(
					array(
						'method'  => 'email',
						'minutes' => isset( $meeting_settings['reminder_time'] ) ? $meeting_settings['reminder_time'] : 30,
					),
					array(
						'method'  => 'popup',
						'minutes' => isset( $meeting_settings['reminder_time'] ) ? $meeting_settings['reminder_time'] : 30,
					),
				),
			),
			'sendUpdates'    => isset( $meeting_settings['send_updates'] ) ? $meeting_settings['send_updates'] : 'all',
			'transparency'   => isset( $meeting_settings['transparency'] ) ? $meeting_settings['transparency'] : 'transparent',
			'visibility'     => isset( $meeting_settings['event_visibility'] ) ? $meeting_settings['event_visibility'] : 'public',
			'status'         => isset( $meeting_settings['event_status'] ) ? $meeting_settings['event_status'] : 'confirmed',
			'conferenceData' => array(
				'createRequest' => array(
					'requestId' => 'meet_demo_' . microtime( true ),
				),
			),
		);

		return $meeting_arr;
	}

}
