<?php
/**
 * MeetingHub Google meet single Template
 *
 * Description: This template displays google meeting join links for a specific post.
 *
 * @package MeetingHub
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the current gmdate and time.
$is_schedule       = false;
$next_meeting_time = '';

$start_time       = '';
$meeting_duration = get_post_meta( get_the_ID(), 'mhub_google_meeting_duration', true );
$join_link        = get_post_meta( get_the_ID(), 'mhub_google_join_link', true );
$meeting_id       = get_post_meta( get_the_ID(), 'mhub_google_meeting_id', true );
$recurrence_info  = '';
$recurrence_data  = null;

if ( $options['enable_recurring_meeting'] && $mhub_is_pro_active && function_exists( 'mhub_get_next_meeting_occurrence' ) ) {
	$recurrence_data = mhub_get_next_meeting_occurrence( $meeting_id, get_the_ID(), $options );
}


if ( $options['enable_recurring_meeting'] && $mhub_is_pro_active && function_exists( 'mhub_get_next_meeting_occurrence' ) ) {
	$recurrence_info = mhub_pro_get_recurrence_description( $options );

	if ( ! empty( $recurrence_data ) && ! empty( $recurrence_data['next_occurrence'] ) ) {
		$datetime = new \DateTime( $recurrence_data['next_occurrence'] );
		// Get the timestamp with correct timezone handling.
		$start_time        = $datetime->format( 'Y-m-d\TH:i:s.v\Z' );
		$next_meeting_time = $recurrence_data['next_occurrence'];
		$gmt_offset_val    = $recurrence_data['recurring_gmt_offset_val'];
	} else {
		$start_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $options['startDateTime'] ) + $gmt_offset_val );
		if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) <= 0 ) {
			$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) - $gmt_offset_val );
			$is_schedule       = true;
		} else {
			$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) );
		}
	}
} else {
	$start_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $options['startDateTime'] ) + $gmt_offset_val );

	if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) <= 0 ) {
		$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) - $gmt_offset_val );
		$is_schedule       = true;
	} else {
		$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) );
	}
}


if ( $mhub_is_pro_active ) {
	$attendee_login = 'login_successful' === $login_status ? true : false;
}

$enable_user_zone = isset( $options['display_time_user_zone'] ) ? $options['display_time_user_zone'] : '';

?>
<div class="meetinghub-wrapper meetinghub-wrapper-flex" data-mhub-starttime="<?php echo esc_attr( $next_meeting_time ); ?>" data-mhub-timezone="<?php echo esc_attr( $options['meeting_timezone'] ); ?>" data-mhub-title="<?php echo esc_attr( $meeting_title ); ?>" data-mhub-permalilk="<?php echo esc_url( get_permalink( $po_meeting_id ) ); ?>" data-mhub-enable-user-zone="<?php echo esc_attr( $enable_user_zone ); ?>" >
	<div class="<?php echo ! $hide_sidebar ? 'mhub-meeting-col' : 'mhub-col-12'; ?> ">

	<?php if ( 'end' !== $meeting_status ) { ?>
		<?php if ( ( $should_register && $attendee_login ) || current_user_can( 'manage_options' ) || ! $should_register ) { ?>

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

		<div class="mhub-google-meet-join-links">
			<a class="meetinghub-google-meet-button mhub-google-meet-join" target="_blank" href="<?php echo esc_url( $join_link ); ?>" title="JOIN NOW"><?php esc_html_e( 'JOIN NOW', 'meetinghub' ); ?></a>
		</div>

			<?php
		} else {
			require_once MHUB_PRO_INCLUDES . '/Templates/mhub-register.php';
		}
		?>
	<?php } else { ?>
				<div class="meeting-not-started">
					<?php esc_html_e( 'The meeting has ended by the host.', 'meetinghub' ); ?>
				</div>
			<?php } ?>
	</div>
	
	<?php
	if ( ! $hide_sidebar ) {
		require_once MHUB_INCLUDES . '/Templates/sidebars/mhub-google-meet-sidebar.php';
	}
	?>
	 
</div>
