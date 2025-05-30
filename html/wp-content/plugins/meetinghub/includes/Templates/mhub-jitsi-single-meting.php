<?php
/**
 * MeetingHub single Template File
 *
 * Description: This template is used to display a meeting or information about a meeting based on the start gmdate and time.
 *
 * @package MeetingHub
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$meeting_domain            = $options['domain'];
$room_name                 = $options['room_name'];
$height                    = $options['height'];
$width                     = $options['width'];
$start_with_audio_muted    = $options['start_with_audio_muted'];
$start_with_video_muted    = $options['start_with_video_muted'];
$start_with_screen_sharing = $options['start_with_screen_sharing'];
$enable_inviting           = $options['enable_inviting'];
$audio_muted               = $options['audio_muted'];
$audio_only                = $options['audio_only'];
$start_silent              = $options['start_silent'];
$video_resolution          = $options['video_resolution'];
$max_full_resolution       = $options['max_full_resolution'];
$video_muted_after         = $options['video_muted_after'];
$enable_recording          = $options['enable_recording'];
$enable_simulcast          = $options['enable_simulcast'];
$enable_livestreaming      = $options['enable_livestreaming'];
$enable_welcome_page       = $options['enable_welcome_page'];
$enable_transcription      = $options['enable_transcription'];
$enable_outbound           = $options['enable_outbound'];
$enable_outbound           = $options['enable_outbound'];
$enable_recurring          = $options['enable_recurring_meeting'];
$saved_time                = $options['startDateTime'];
$hide_sidebar              = $options['hide_sidebar'];

// Get the current gmdate and time.
$load_meeting      = false;
$is_schedule       = false;
$calculated_time   = array();
$next_meeting_time = '';
$start_time        = '';


if ( $mhub_is_pro_active ) {
	$calculated_time = mhub_pro_calculate_next_meeting_time( $options, $gmt_offset, $gmt_offset_val );
	$start_time      = $calculated_time['start_time'];
	$attendee_login  = 'login_successful' === $login_status ? true : false;
} else {
	$start_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $options['startDateTime'] ) + $gmt_offset_val );
}

if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) >= 0 ) {
	$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) );
	$load_meeting      = true;
}

if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) <= 0 ) {
	$is_schedule       = true;
	$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) - $gmt_offset_val );
	if ( 'start' === $meeting_start_status ) {
		$load_meeting = true;
	}
}


$enable_user_zone = isset( $options['display_time_user_zone'] ) ? $options['display_time_user_zone'] : '';


?>

<div class="meetinghub-wrapper meetinghub-wrapper-flex" data-mhub-starttime="<?php echo esc_attr( $next_meeting_time ); ?>" data-mhub-timezone="<?php echo esc_attr( $options['meeting_timezone'] ); ?>" data-mhub-title="<?php echo esc_attr( $meeting_title ); ?>" data-mhub-permalilk="<?php echo esc_url( get_permalink( $po_meeting_id ) ); ?>" data-mhub-enable-user-zone="<?php echo esc_attr( $enable_user_zone ); ?>">
	<div class="<?php echo ! $hide_sidebar ? 'mhub-meeting-col' : 'mhub-col-12'; ?> ">
		<?php if ( 'end' !== $meeting_status ) { ?>
			<?php if ( ( $should_register && $attendee_login ) || current_user_can( 'manage_options' ) || ! $should_register ) { ?>
				<?php if ( $load_meeting ) { ?> 
					<div id="meetinghub_meeting"
						data-random-domain="<?php echo esc_attr( $meeting_domain ); ?>"
						data-random-room-name="<?php echo esc_attr( $room_name ); ?>"
						data-height="<?php echo esc_attr( $height ); ?>"
						data-width="<?php echo esc_attr( $width ); ?>"
						data-start-with-audio-muted="<?php echo esc_attr( $start_with_audio_muted ); ?>"
						data-start-with-video-muted="<?php echo esc_attr( $start_with_video_muted ); ?>"
						data-screen-sharing="<?php echo esc_attr( $start_with_screen_sharing ); ?>"
						data-enable-inviting="<?php echo esc_attr( $enable_inviting ); ?>"
						data-audio-muted="<?php echo esc_attr( $audio_muted ); ?>"
						data-audio-only="<?php echo esc_attr( $audio_only ); ?>"
						data-start-silent="<?php echo esc_attr( $start_silent ); ?>"
						data-video-resolution="<?php echo esc_attr( $video_resolution ); ?>"
						data-max-full-resolution="<?php echo esc_attr( $max_full_resolution ); ?>"
						data-video-muted-after="<?php echo esc_attr( $video_muted_after ); ?>"
						data-enable-recording="<?php echo esc_attr( $enable_recording ); ?>"
						data-enable-simulcast="<?php echo esc_attr( $enable_simulcast ); ?>"
						data-enable-livestreaming="<?php echo esc_attr( $enable_livestreaming ); ?>"
						data-enable-welcome-page="<?php echo esc_attr( $enable_welcome_page ); ?>"
						data-enable-transcription="<?php echo esc_attr( $enable_transcription ); ?>"
						data-enable-outbound="<?php echo esc_attr( $enable_outbound ); ?>"
						>
					</div>	
				<?php } else { ?>
						<?php if ( $is_schedule ) { ?>

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

							<?php if ( $hide_sidebar ) { ?>
								<div class="meeting-not-started">
									<h1><?php esc_html_e( 'Meeting has not started yet.', 'meetinghub' ); ?></h1>
									<h4 class="mhub_time_to_start"><?php esc_html_e( 'Time to start:', 'meetinghub' ); ?> </h4>
									<div class="meetinghub_start_time" data-meeting-start-time="<?php echo esc_attr( $next_meeting_time ); ?>"> </div>
								</div>
							<?php } ?>
						<?php } else { ?>
							<div class="meeting-not-started">
									<?php esc_html_e( 'The meeting has ended.', 'meetinghub' ); ?>
							</div>
						<?php } ?>
				<?php } ?>
			<?php } ?>
		<?php } else { ?>
			<div class="meeting-not-started">
				<?php esc_html_e( 'The meeting has ended by the host.', 'meetinghub' ); ?>
			</div>
		<?php } ?>

		<?php
		if ( $mhub_is_pro_active ) {
			if ( $should_register && ! current_user_can( 'manage_options' ) && 'end' !== $meeting_status && ! $attendee_login ) {
				require_once MHUB_PRO_INCLUDES . '/Templates/mhub-register.php';
			}
		}
		?>

	</div>

	<?php
	if ( ! $hide_sidebar ) {
		require_once MHUB_INCLUDES . '/Templates/sidebars/mhub-jitsi-sidebar.php';
	}
	?>

</div>
