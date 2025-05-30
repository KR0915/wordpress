<?php
/**
 * MeetingHub Zoom single Template
 *
 * Description: This template displays Zoom meeting join links for a specific post.
 *
 * @package MeetingHub
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$start_url        = get_post_meta( get_the_ID(), 'meetinghub_zoom_start_url', true );
$meeting_id       = get_post_meta( get_the_ID(), 'meetinghub_zoom_meeting_id', true );
$webinar_id       = get_post_meta( get_the_ID(), 'meetinghub_zoom_webinar_id', true );
$join_url         = get_post_meta( get_the_ID(), 'meetinghub_zoom_join_url', true );
$meeting_duration = get_post_meta( get_the_ID(), 'mhub_zoom_meeting_duration', true );
$meeting_type     = intval( $options['meeting_type'] );
$zoom_api         = \SOVLIX\MHUB\Zoom\Mhub_Zoom_Api::instance();
$zoom_response    = null;

if ( 1 === $meeting_type ) {
	$zoom_response = json_decode( $zoom_api->get_webinar_info( $webinar_id ) );
}

if ( 2 === $meeting_type ) {
	$zoom_response = json_decode( $zoom_api->get_meeting_info( $meeting_id ) );
}


$start_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $options['startDateTime'] ) + $gmt_offset_val );


// Recurring data.
$recurring_next_occurrence;
$recurring_end_date;
$recurring_repeat_interval;
$recurring_repeat_name;
$recurring_duration;
$recurrence;

$next_meeting_time   = '';
$meeting_deleted     = false;
$meeting_not_created = false;
$is_schedule         = false;
$is_weekly_schedule  = false;

if ( $options['enable_recurring_meeting'] && ! empty( $zoom_response->occurrences ) ) {
	$recurrence                = $zoom_response->recurrence;
	$recurring_repeat_interval = $recurrence->repeat_interval;

	if ( 1 === $recurrence->type ) {
		$is_schedule           = false;
		$recurring_repeat_name = 1 < $recurring_repeat_interval ? 'days' : 'day';
	} elseif ( 2 === $recurrence->type ) {
		$is_schedule = true;

		// Convert weekly_days numbers to actual day names (Sunday as day 1).
		if ( ! empty( $recurrence->weekly_days ) ) {
			$weekly_days = explode( ',', $recurrence->weekly_days );
			$day_names   = array(
				1 => 'Sunday',
				2 => 'Monday',
				3 => 'Tuesday',
				4 => 'Wednesday',
				5 => 'Thursday',
				6 => 'Friday',
				7 => 'Saturday',
			);

			// Convert the numeric days into day names.
			$recurring_repeat_name = ( 1 < $recurring_repeat_interval ) ? 'weeks' : 'week';

			$recurring_repeat_name .= ' on ' . implode(
				', ',
				array_map(
					function ( $day ) use ( $day_names ) {
						return $day_names[ (int) $day ]; // Get day name from map.
					},
					$weekly_days
				)
			);

		} else {
			$recurring_repeat_name = 'week';
		}
	} elseif ( 3 === $recurrence->type ) {
		$is_schedule           = true;
		$recurring_repeat_name = 1 < $recurring_repeat_interval ? 'months' : 'month';
	}

	// Process occurrences.
	$recurring_next_occurrence = $zoom_response->occurrences[0];
	$next_meeting_time         = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $recurring_next_occurrence->start_time ) );
	$recurring_duration        = $recurring_next_occurrence->duration;
	$recurring_end_date        = gmdate( 'd M Y', strtotime( end( $zoom_response->occurrences )->start_time ) );
}

if ( isset( $zoom_response->code ) ) {
	if ( 3001 === $zoom_response->code ) {
		$meeting_deleted = true;
	}

	if ( 2300 === $zoom_response->code ) {
		$meeting_not_created = true;
	}
}

if ( ! $options['enable_recurring_meeting'] ) {

	if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) <= 0 ) {
		$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) - $gmt_offset_val );
		$is_schedule       = true;
	}

	if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) >= 0 ) {
		$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) );
	}
}


if ( $mhub_is_pro_active ) {
	$attendee_login = 'login_successful' === $login_status ? true : false;
}

$enable_user_zone = isset( $options['display_time_user_zone'] ) ? $options['display_time_user_zone'] : '';

?>
<div class="meetinghub-wrapper meetinghub-wrapper-flex" data-mhub-starttime="<?php echo esc_attr( $next_meeting_time ); ?>" data-mhub-timezone="<?php echo esc_attr( $options['meeting_timezone'] ); ?>" data-mhub-title="<?php echo esc_attr( $meeting_title ); ?>" data-mhub-permalilk="<?php echo esc_url( get_permalink( $po_meeting_id ) ); ?>" data-mhub-enable-user-zone="<?php echo esc_attr( $enable_user_zone ); ?>">
	<div class="<?php echo ! $hide_sidebar ? 'mhub-meeting-col' : 'mhub-col-12'; ?> ">
	<?php if ( ! $meeting_not_created ) { ?>
		<?php if ( ! $meeting_deleted ) { ?>
			<?php if ( 'end' !== $meeting_status ) { ?>
				<?php if ( ( $should_register && $attendee_login ) || current_user_can( 'manage_options' ) || ! $should_register || 1 === $meeting_type ) { ?>
					
						<?php
						if ( ! empty( $thumbnail_html ) ) {
							?>
								<div class="meeting-thumbnail">
									<?php echo wp_kses_post( $thumbnail_html ); ?>
								</div>
							<?php
						}
						?>

						<?php
						if ( ! empty( $meeting_description ) ) {
							?>
								<div class="meeting-details">
									<?php echo wp_kses_post( $meeting_description ); ?>
								</div>
							<?php
						}
						?>

					<div class="meetinghub-zoom-join-links">
						<a class="meetinghub-button meetinghub-zoom-join-web" target="_blank" href="<?php echo esc_attr( add_query_arg( array( 'display_meeting' => '1' ), get_permalink( get_the_ID() ) ) ); ?>" title="JOIN IN BROWSER"><?php esc_html_e( 'JOIN IN BROWSER', 'meetinghub' ); ?></a>
					
						<a target="_blank" href="<?php echo esc_url( $join_url ); ?>" rel="nofollow" class="meetinghub-button meetinghub-join-app"><?php esc_html_e( 'JOIN IN ZOOM APP', 'meetinghub' ); ?></a>
						
						<?php if ( current_user_can( 'manage_options' ) ) { ?>
						<a target="_blank" href="<?php echo esc_url( $start_url ); ?>" rel="nofollow" class="meetinghub-button meetinghub-join-app"><?php 2 === $meeting_type ? esc_html_e( 'START MEETING', 'meetinghub' ) : esc_html_e( 'START WEBINAR', 'meetinghub' ); ?></a>
						<?php } ?>
					</div>

					<?php
				} else {
					require_once MHUB_PRO_INCLUDES . '/Templates/mhub-register.php';
				}
				?>

			<?php } else { ?>
					<div class="meeting-not-started">
						<?php 2 === $meeting_type ? esc_html_e( 'The meeting has ended by the host.', 'meetinghub' ) : esc_html_e( 'The webinar has ended by the host.', 'meetinghub' ); ?>
					</div>
			<?php } ?>
		
		<?php } else { ?>
			<div class="meeting-not-started">
				<?php 2 === $meeting_type ? esc_html_e( 'Meeting does not exist.', 'meetinghub' ) : esc_html_e( 'Webinar does not exist.', 'meetinghub' ); ?>
			</div>
		<?php } ?>
	<?php } else { ?>
			<?php if ( current_user_can( 'manage_options' ) ) { ?>
				<div class="meeting-not-started">
					<?php 2 === $meeting_type ? esc_html_e( 'Meeting not created yet. Please setup api in settings first.', 'meetinghub' ) : esc_html_e( 'Webinar not created yet. Please setup api in settings first.', 'meetinghub' ); ?>
				</div>
			<?php } else { ?>
				<div class="meeting-not-started">
					<?php 2 === $meeting_type ? esc_html_e( 'Meeting does not exist.', 'meetinghub' ) : esc_html_e( 'Webinar does not exist.', 'meetinghub' ); ?>
				</div>
			<?php } ?>
		<?php } ?>
	</div>

	<?php
	if ( ! $hide_sidebar ) {
		require_once MHUB_INCLUDES . '/Templates/sidebars/mhub-zoom-sidebar.php';
	}
	?>
	 
</div>
