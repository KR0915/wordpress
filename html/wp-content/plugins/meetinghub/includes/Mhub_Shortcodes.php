<?php // phpcs:ignore
/**
 * Mhub_Shortcodes Class
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

if ( ! class_exists( 'Mhub_Shortcodes' ) ) {
	/**
	 * Mhub_Shortcodes Class
	 *
	 * Handles frontend-related tasks and functionality for the Meeting Hub plugin.
	 *
	 * @since 1.0.0
	 */
	class Mhub_Shortcodes {
		/**
		 * Webex Api class.
		 *
		 * @var Mhub_Webex_Api
		 */
		private $webex_api;

		/**
		 * Mhub_Shortcodes constructor.
		 *
		 * Initializes the frontend class.
		 */
		public function __construct() {
			$this->webex_api = \SOVLIX\MHUB\API\Webex_Api::get_instance();
			add_shortcode( 'mhub-jitsi-meeting', array( $this, 'mhub_jitsi_shortcode_render' ) );
			add_shortcode( 'mhub-zoom-meeting', array( $this, 'mhub_zoom_shortcode_render' ) );
			add_shortcode( 'mhub-zoom-webinar', array( $this, 'mhub_zoom_webinar_shortcode_render' ) );
			add_shortcode( 'mhub-webex-meeting', array( $this, 'mhub_webex_shortcode_render' ) );
			add_shortcode( 'mhub-webex-meeting-list', array( $this, 'mhub_webex_meeting_list_shortcode_render' ) );
			add_shortcode( 'mhub-jitsi-meeting-list', array( $this, 'mhub_jitsi_meeting_list_shortcode_render' ) );
			add_shortcode( 'mhub-zoom-meeting-list', array( $this, 'mhub_zoom_meeting_list_shortcode_render' ) );
			add_shortcode( 'mhub-zoom-webinar-list', array( $this, 'mhub_zoom_webinar_list_shortcode_render' ) );
			add_shortcode( 'mhub-google-meeting', array( $this, 'mhub_google_meet_shortcode_render' ) );
			add_shortcode( 'mhub-google-meeting-list', array( $this, 'mhub_google_meeting_list_shortcode_render' ) );
		}

		/**
		 * Render Jitsi Meeting Shortcode Content.
		 *
		 * This function is responsible for rendering the content for the [mhub-jitsi-meeting] shortcode.
		 * It retrieves meeting settings from the specified or default post ID and outputs Jitsi meeting content.
		 *
		 * @param array $atts Shortcode attributes.
		 *                    'id' (int) - The post ID for which to display the Jitsi meeting content. Defaults to the current post ID.
		 *
		 * @return string Rendered HTML content for the Jitsi meeting shortcode.
		 */
		public function mhub_jitsi_shortcode_render( $atts ) {

			if ( ! empty( $atts ) ) {
				// Extract shortcode attributes, including the 'id' attribute if provided.
				$atts = shortcode_atts(
					array(
						'id'            => $atts['id'],
						'hide_details'  => $atts['hide_details'],
						'hide_timer'    => $atts['hide_timer'],
						'hide_calendar' => mhub_fs()->can_use_premium_code__premium_only() ? $atts['hide_calendar'] : '',
					),
					$atts,
					'mhub-jitsi-meeting'
				);

				// Check if the post exists.
				if ( ! get_post_status( $atts['id'] ) ) {
					return '<h1>' . esc_html__( 'The meeting has ended or does not exist.', 'meetinghub' ) . '</h1>';
				}

				ob_start();

				// Use the specified or default post ID to get the post meta.
				$serialize_options = get_post_meta( $atts['id'], 'mhub__meeting_settings', true );
				$options           = maybe_unserialize( $serialize_options );
				$po_meeting_id     = $atts['id'];
				$hide_details      = $atts['hide_details'];
				$hide_timer        = $atts['hide_timer'];
				$hide_calendar     = $atts['hide_calendar'];

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
				$enable_user_zone          = isset( $options['display_time_user_zone'] ) ? $options['display_time_user_zone'] : '';

				// User name.
				$user_id           = get_current_user_id();
				$current_user_data = get_userdata( $user_id );
				$current_user_name = $current_user_data ? $current_user_data->display_name : '';

				$meeting_status       = get_post_meta( $atts['id'], 'mhub_meeting_status', true );
				$meeting_start_status = get_post_meta( $atts['id'], 'mhub_meeting_start_status', true );
				$meeting_description  = get_post_meta( $atts['id'], 'meeting_description', true );
				$thumbnail_html       = get_the_post_thumbnail( $atts['id'] );
				$meeting_title        = get_the_title( $atts['id'] );
				$hide_sidebar         = $options['hide_sidebar'];
				$time_zone            = $options['meeting_timezone'];

				$gmt_array       = mhub_get_gmt_offset( $options['meeting_timezone'] );
				$gmt_offset_val  = $gmt_array['gmt_offset_val'];
				$gmt_offset      = $gmt_array['gmt_offset'];
				$should_register = false;
				$attendee_login  = false;
				$login_status;
				$meeting_as_product;
				$product_id;

				if ( mhub_fs()->can_use_premium_code__premium_only() ) {
					$cookie_key         = "mhub_login_status_{$po_meeting_id}";
					$login_status       = isset( $_COOKIE[ $cookie_key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) ) : '';
					$meeting_as_product = get_post_meta( $atts['id'], 'mhub_connect_as_product', true );
					$product_id         = get_post_meta( $atts['id'], 'mhub_connect_product_id', true );
					$should_register    = isset( $options['enable_should_register'] ) ? $options['enable_should_register'] : false;
				}

				// Get the current gmdate and time.
					$load_meeting      = false;
					$is_schedule       = false;
					$calculated_time   = array();
					$next_meeting_time = '';
					$start_time        = '';

				if ( mhub_fs()->can_use_premium_code__premium_only() ) {
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

				if ( 'jitsi_meet' === $options['selected_platform'] ) {
					?>
						<div class="meetinghub-wrapper" data-mhub-starttime="<?php echo esc_attr( $next_meeting_time ); ?>" data-mhub-timezone="<?php echo esc_attr( $options['meeting_timezone'] ); ?>" data-mhub-title="<?php echo esc_attr( $meeting_title ); ?>" data-mhub-permalilk="<?php echo esc_url( get_permalink( $po_meeting_id ) ); ?>" data-mhub-enable-user-zone="<?php echo esc_attr( $enable_user_zone ); ?>">
							<div class="mhub-col-12" style="float: none;">
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
											<?php } ?>
												
										<?php } ?>
									<?php } ?>
								<?php } ?>
									
								<?php
								if ( mhub_fs()->can_use_premium_code__premium_only() ) {
									if ( $should_register && ! current_user_can( 'manage_options' ) && 'end' !== $meeting_status && ! $attendee_login ) {
										require_once MHUB_PRO_INCLUDES . '/Templates/mhub-register.php';
									}
								}
								?>

							</div>

							<?php if ( 'yes' !== $hide_timer ) { ?>
						
							<div class="mhub-col-12" style="float: none; margin-bottom:40px;">
								<div class="mhub-sidebar">
									<div class="mhub-single-widget mhub-single-widget-countdown">
										<h4 class="mhub-widget-title"><?php esc_html_e( 'Time to go', 'meetinghub' ); ?></h4>
										<div class="mhub-widget-inner">
										<?php if ( 'end' !== $meeting_status ) { ?>
											<?php if ( $load_meeting && 'start' !== $meeting_start_status ) { ?>
											<span class="mhub-countdown-wrapper">
												<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting is running', 'meetinghub' ); ?></span>
												<span class="mhub-countdown-label"><?php esc_html_e( 'The meeting is started and running', 'meetinghub' ); ?></span>
											</span>
											<?php } elseif ( $load_meeting && 'start' === $meeting_start_status ) { ?>
												<span class="mhub-countdown-wrapper">
													<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting is Started', 'meetinghub' ); ?></span>
													<span class="mhub-countdown-label"><?php esc_html_e( 'The meeting is started by the host.', 'meetinghub' ); ?></span>
												</span>
											<?php } else { ?>
												<?php if ( $is_schedule ) { ?>
													<div class="meetinghub_start_time" data-meeting-start-time="<?php echo esc_attr( $next_meeting_time ); ?>"> </div>
												<?php } ?>

											<?php } ?>

										<?php } else { ?>
											<span class="mhub-countdown-wrapper">
												<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting is finished', 'meetinghub' ); ?></span>
												<span class="mhub-countdown-label"><?php esc_html_e( 'This meeting has been ended by the host.', 'meetinghub' ); ?></span>
											</span>
										<?php } ?>
										</div>
									</div>
								</div>
							</div>

							<?php } ?>

							<div class="mhub-col-12" style="float: none;  margin-bottom:40px;">
								<?php if ( $is_schedule && 'end' !== $meeting_status ) { ?>
									<?php if ( current_user_can( 'manage_options' ) ) { ?>
									<div class="mhub-single-widget mhub-single-widget-host-actions">
										<div class="mhub-widget-inner">
										<?php if ( 'start' !== $meeting_start_status ) { ?>
											<button  class="mhub-meeting-status" data-meeting-status="start" data-post-id="<?php echo esc_attr( $atts['id'] ); ?>" data-meeting-id="<?php echo esc_attr( $atts['id'] ); ?>"><?php esc_html_e( 'Start Meeting ?', 'meetinghub' ); ?>
											</button>
										<?php } else { ?>
											<button  class="mhub-meeting-status" data-meeting-status="stop" data-post-id="<?php echo esc_attr( $atts['id'] ); ?>" data-meeting-id="<?php echo esc_attr( $atts['id'] ); ?>"><?php esc_html_e( 'Stop meeting ?', 'meetinghub' ); ?>
											</button>
											<?php } ?>
											<p><?php esc_html_e( 'You are seeing this because you are the author of this meeting', 'meetinghub' ); ?></p>
										</div>
									</div>
									<?php } ?>
								<?php } ?>

								<?php if ( current_user_can( 'manage_options' ) ) { ?>
									<div class="mhub-single-widget mhub-single-widget-host-actions" style="margin-top:40px;">
										<div class="mhub-widget-inner">
										<?php if ( 'end' !== $meeting_status ) { ?>
											<button  class="mhub-meeting-status" data-meeting-status="end" data-post-id="<?php echo esc_attr( $atts['id'] ); ?>" data-meeting-id="<?php echo esc_attr( $atts['id'] ); ?>"><?php esc_html_e( 'End Meeting ?', 'meetinghub' ); ?>
											</button>
										<?php } else { ?>
											<button  class="mhub-meeting-status" data-meeting-status="resume" data-post-id="<?php echo esc_attr( $atts['id'] ); ?>" data-meeting-id="<?php echo esc_attr( $atts['id'] ); ?>"><?php esc_html_e( 'Enable Meeting Join ?', 'meetinghub' ); ?>
											</button>
											<?php } ?>
											<p><?php esc_html_e( 'You are seeing this because you are the author of this meeting', 'meetinghub' ); ?></p>
										</div>
									</div>
								<?php } ?>
							</div>

							<?php if ( 'yes' !== $hide_details ) { ?>

							<div class="mhub-col-12" style="float: none;  margin-bottom:40px;">
								<div class="mhub-single-widget mhub-single-widget-detail">
									<h4 class="mhub-widget-title"><?php esc_html_e( 'Details', 'meetinghub' ); ?></h4>
									<div class="mhub-widget-inner">
										<dl>
											<dt><?php esc_html_e( 'Topic:', 'meetinghub' ); ?></dt>
											<dd><?php echo esc_html( the_title() ); ?></dd>
											<dt><?php esc_html_e( 'Hosted By:', 'meetinghub' ); ?></dt>
											<dd>
											<?php
												echo esc_html( $current_user_name );
											?>
											</dd>

											<dt><?php esc_html_e( 'Start Time:', 'meetinghub' ); ?></dt>
											<dd class="mhbu-tm">
											<?php
												echo esc_html( gmdate( 'l, F j, Y g:i A', strtotime( $start_time ) ) );
											?>
											</dd>

											<dt><?php esc_html_e( 'Current Timezone:', 'meetinghub' ); ?></dt>
											<dd class="mhub-tz">
											<?php
												echo esc_html( $time_zone );
											?>
											</dd>
											
										</dl>
									</div>
								</div>

								<div class="mhub-buy-btn-wpapper">
									<?php
									if ( ! empty( $product_id ) && 'yes' === $meeting_as_product && ! current_user_can( 'manage_options' ) ) {
										$product_link = get_permalink( $product_id );
										?>
										<a href="<?php echo esc_url( $product_link ); ?>" class="mhub-buy-button" target="__blank"> <?php esc_attr_e( 'Buy Now !', 'meetinghub' ); ?></a>
									
									<?php } ?>
								</div>
							</div>

							<?php } ?>

							<?php
							if ( 'yes' !== $hide_calendar ) {
								if ( mhub_fs()->can_use_premium_code__premium_only() ) {
									?>
									<div class="mhub-col-12" style="float: none; margin-top:40px;">
										<div class="mhub-sidebar">
									<?php
										require_once MHUB_PRO_INCLUDES . '/Templates/mhub-calander.php';
									?>
										</div>
									</div>
									<?php
								}
							}
							?>
						</div>
					<?php
				}
			} else {
				return '<h1> Please use correct shortcode</h1>';
			}
			return ob_get_clean();
		}

		/**
		 * Renders the MeetingHub Zoom shortcode.
		 *
		 * This function handles the rendering of the MeetingHub Zoom shortcode, which is used to display
		 * Zoom meeting information on a WordPress site. It processes the shortcode attributes, retrieves
		 * meeting details from the post meta, and generates the necessary HTML and CSS to display the
		 * meeting information.
		 *
		 * @param array $atts The shortcode attributes. Includes:
		 *   - 'id' (int): The ID of the post containing the Zoom meeting details.
		 *
		 * @return string The rendered HTML content for the shortcode.
		 */
		public function mhub_zoom_shortcode_render( $atts ) {
			if ( ! empty( $atts ) ) {
				// Extract shortcode attributes, including the 'id' attribute if provided.
				$atts = shortcode_atts(
					array(
						'id'            => $atts['id'],
						'hide_details'  => $atts['hide_details'],
						'hide_timer'    => $atts['hide_timer'],
						'hide_calendar' => mhub_fs()->can_use_premium_code__premium_only() ? $atts['hide_calendar'] : '',
					),
					$atts,
					'mhub-zoom-meeting'
				);

				// Check if the post exists.
				if ( ! get_post_status( $atts['id'] ) ) {
					return '<h1>' . esc_html__( 'The meeting has ended or does not exist.', 'meetinghub' ) . '</h1>';
				}

				ob_start();

				$serialize_options = get_post_meta( $atts['id'], 'mhub__meeting_settings', true );
				$options           = maybe_unserialize( $serialize_options );

				$user_id           = get_current_user_id();
				$current_user_data = get_userdata( $user_id );
				$current_user_name = $current_user_data ? $current_user_data->display_name : '';

				$meeting_status       = get_post_meta( $atts['id'], 'mhub_meeting_status', true );
				$meeting_start_status = get_post_meta( $atts['id'], 'mhub_meeting_start_status', true );
				$hide_sidebar         = $options['hide_sidebar'];
				$time_zone            = $options['meeting_timezone'];

				$gmt_array       = mhub_get_gmt_offset( $options['meeting_timezone'] );
				$gmt_offset_val  = $gmt_array['gmt_offset_val'];
				$gmt_offset      = $gmt_array['gmt_offset'];
				$should_register = false;
				$attendee_login  = false;
				$login_status;
				$meeting_as_product;
				$product_id;
				$po_meeting_id = $atts['id'];
				$hide_details  = $atts['hide_details'];
				$hide_timer    = $atts['hide_timer'];
				$hide_calendar = $atts['hide_calendar'];

				if ( mhub_fs()->can_use_premium_code__premium_only() ) {
					$cookie_key         = "mhub_login_status_{$po_meeting_id}";
					$login_status       = isset( $_COOKIE[ $cookie_key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) ) : '';
					$meeting_as_product = get_post_meta( $atts['id'], 'mhub_connect_as_product', true );
					$product_id         = get_post_meta( $atts['id'], 'mhub_connect_product_id', true );
					$should_register    = isset( $options['enable_should_register'] ) ? $options['enable_should_register'] : false;
				}

				// Use the specified or default post ID to get the post meta.
				$serialize_options = get_post_meta( $atts['id'], 'mhub__meeting_settings', true );
				$options           = maybe_unserialize( $serialize_options );

				$start_url           = get_post_meta( $atts['id'], 'meetinghub_zoom_start_url', true );
				$meeting_id          = get_post_meta( $atts['id'], 'meetinghub_zoom_meeting_id', true );
				$join_url            = get_post_meta( $atts['id'], 'meetinghub_zoom_join_url', true );
				$meeting_duration    = get_post_meta( $atts['id'], 'mhub_zoom_meeting_duration', true );
				$meeting_description = get_post_meta( $atts['id'], 'meeting_description', true );
				$thumbnail_html      = get_the_post_thumbnail( $atts['id'] );
				$meeting_title       = get_the_title( $atts['id'] );
				$meeting_type        = intval( $options['meeting_type'] );
				$enable_user_zone    = isset( $options['display_time_user_zone'] ) ? $options['display_time_user_zone'] : '';

				$zoom_api      = \SOVLIX\MHUB\Zoom\Mhub_Zoom_Api::instance();
				$zoom_response = json_decode( $zoom_api->get_meeting_info( $meeting_id ) );
				$start_time    = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $options['startDateTime'] ) + $gmt_offset_val );

				$meeting_id;
				$password = '';

				$next_meeting_time   = '';
				$meeting_deleted     = false;
				$meeting_not_created = false;
				$is_schedule         = false;

				if ( isset( $zoom_response->code ) ) {
					if ( 3001 === $zoom_response->code ) {
						$meeting_deleted = true;
					}

					if ( 2300 === $zoom_response->code ) {
						$meeting_not_created = true;
					}
				}

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

				if ( ! $options['enable_recurring_meeting'] ) {

					if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) <= 0 ) {
						$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) - $gmt_offset_val );
						$is_schedule       = true;
					}

					if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) >= 0 ) {
						$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) );
					}
				}

				if ( mhub_fs()->can_use_premium_code__premium_only() ) {
					$attendee_login = 'login_successful' === $login_status ? true : false;
				}

				if ( 'zoom' === $options['selected_platform'] ) {
					?>
					<div class="meetinghub-wrapper" data-mhub-starttime="<?php echo esc_attr( $next_meeting_time ); ?>" data-mhub-timezone="<?php echo esc_attr( $options['meeting_timezone'] ); ?>" data-mhub-title="<?php echo esc_attr( $meeting_title ); ?>" data-mhub-permalilk="<?php echo esc_url( get_permalink( $po_meeting_id ) ); ?>" data-mhub-enable-user-zone="<?php echo esc_attr( $enable_user_zone ); ?>">
						<div class="mhub-col-12" style="float: none;">
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
						</div>

						<?php if ( 'yes' !== $hide_timer ) { ?>
						<div class="mhub-col-12" style="float: none;">
							<div class="mhub-sidebar">
								<div class="mhub-single-widget mhub-single-widget-countdown mhub-shortcode-widget">
									<h4 class="mhub-widget-title" style="margin:0; color:#fff;"><?php esc_html_e( 'Time to go', 'meetinghub' ); ?></h4>
									<div class="mhub-widget-inner">
									<?php if ( ! $meeting_not_created ) { ?>
										<?php if ( ! $meeting_deleted ) { ?>
											<?php if ( 'end' !== $meeting_status ) { ?>
												<?php if ( $is_schedule ) { ?>
													<div class="meetinghub_start_time" data-meeting-start-time="<?php echo esc_attr( $next_meeting_time ); ?>" data-meeting-time-offset="<?php echo esc_attr( $gmt_offset_val ); ?>" > </div>
													<?php } else { ?>
														<span class="mhub-countdown-wrapper">
															<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting is running', 'meetinghub' ); ?></span>
															<span class="mhub-countdown-label"><?php esc_html_e( 'The meeting is started and running', 'meetinghub' ); ?></span>
														</span>
													<?php } ?>
											<?php } else { ?>
												<span class="mhub-countdown-wrapper">
													<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting is finished', 'meetinghub' ); ?></span>
													<span class="mhub-countdown-label"><?php esc_html_e( 'This meeting has been ended by the host.', 'meetinghub' ); ?></span>
												</span>
											<?php } ?>

										<?php } else { ?>
											<span class="mhub-countdown-wrapper">
												<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting does not exist', 'meetinghub' ); ?></span>
												<span class="mhub-countdown-label"><?php esc_html_e( 'The meeting is not exists in zoom account.', 'meetinghub' ); ?></span>
											</span>
										<?php } ?>
									<?php } else { ?>
										<?php if ( current_user_can( 'manage_options' ) ) { ?>
											<span class="mhub-countdown-wrapper">
												<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting not created', 'meetinghub' ); ?></span>
												<span class="mhub-countdown-label"><?php esc_html_e( 'You have to setup zoom credentils first from settings.', 'meetinghub' ); ?></span>
											</span>
										<?php } else { ?>
											<span class="mhub-countdown-wrapper">
												<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting does not exist', 'meetinghub' ); ?></span>
												<span class="mhub-countdown-label"><?php esc_html_e( 'The meeting is not exists in zoom account.', 'meetinghub' ); ?></span>
											</span>
										<?php } ?>
									<?php } ?>
									</div>
								</div>
							</div>
						</div>
						<?php } ?>

						<div class="mhub-col-12" style="float: none;">
							<?php if ( ! $meeting_not_created ) { ?>
								<?php if ( ! $meeting_deleted ) { ?>
									<?php if ( 'end' !== $meeting_status ) { ?>
										<?php if ( ( $should_register && $attendee_login ) || current_user_can( 'manage_options' ) || ! $should_register ) { ?>
											<div class="mhub-shortcode-zoom-join-links">
												<a target="_blank" href="<?php echo esc_url( $join_url ); ?>" rel="nofollow" class="meetinghub-button meetinghub-join-app"><?php esc_html_e( 'JOIN IN ZOOM APP', 'meetinghub' ); ?></a>

												<a class="meetinghub-button meetinghub-zoom-join-web" target="_blank" href="<?php echo esc_attr( add_query_arg( array( 'display_meeting' => '1' ), get_permalink( $atts['id'] ) ) ); ?>" title="JOIN IN BROWSER"><?php esc_html_e( 'JOIN IN BROWSER', 'meetinghub' ); ?></a>

												<?php if ( current_user_can( 'manage_options' ) ) { ?>
												<a target="_blank" href="<?php echo esc_url( $start_url ); ?>" rel="nofollow" class="meetinghub-button meetinghub-join-app"><?php esc_html_e( 'START MEETING', 'meetinghub' ); ?></a>
												<?php } ?>
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
								
								<?php } else { ?>
									<div class="meeting-not-started">
										<?php esc_html_e( 'Meeting does not exist.', 'meetinghub' ); ?>
									</div>
								<?php } ?>
							<?php } else { ?>
								<?php if ( current_user_can( 'manage_options' ) ) { ?>
									<div class="meeting-not-started">
										<?php esc_html_e( 'Meeting not created yet. Please setup api in settings first.', 'meetinghub' ); ?>
									</div>
								<?php } else { ?>
									<div class="meeting-not-started">
										<?php esc_html_e( 'Meeting does not exist.', 'meetinghub' ); ?>
									</div>
								<?php } ?>
							<?php } ?>
						</div>
									
						<?php if ( 'yes' !== $hide_details ) { ?>
						<div class="mhub-col-12" style="float: none;">
							<div class="mhub-sidebar">
								<div class="mhub-single-widget mhub-single-widget-detail">
										<h4 class="mhub-widget-title" style="margin:0; color:#fff;"><?php esc_html_e( 'Details', 'meetinghub' ); ?></h4>
										<div class="mhub-widget-inner">
											<dl>
												<dt><?php esc_html_e( 'Topic:', 'meetinghub' ); ?></dt>
												<dd><?php echo esc_html( the_title() ); ?></dd>
												<dt><?php esc_html_e( 'Hosted By:', 'meetinghub' ); ?></dt>
												<dd>
												<?php
													echo esc_html( $current_user_name );
												?>
												</dd>

												<?php if ( ! $options['enable_recurring_meeting'] ) { ?>
													<dt><?php esc_html_e( 'Start Time:', 'meetinghub' ); ?></dt>
													<dd class="mhbu-tm">
													<?php

													echo esc_html( gmdate( 'l, F j, Y g:i A', strtotime( $start_time ) ) );

													?>
													</dd>

												<?php } ?>

												<?php if ( $options['enable_recurring_meeting'] && ! empty( $zoom_response->occurrences ) ) { ?>
													<dt><?php 2 === $meeting_type ? esc_html_e( 'Recurring Meeting schedule:', 'meetinghub' ) : esc_html_e( 'Recurring Webinar schedule:', 'meetinghub' ); ?></dt>
													<dd style="margin-bottom:0;"> 
														<?php esc_html_e( 'From:', 'meetinghub' ); ?> 
														<span class="muhb-st"> <?php echo esc_html( gmdate( 'd M Y', strtotime( $next_meeting_time ) ) ); ?></span>
													</dd>
													<dd> 
														<?php esc_html_e( 'To:', 'meetinghub' ); ?> 
														<span class="muhb-et"> <?php echo esc_html( $recurring_end_date ); ?></span>
													</dd>

													<dt><?php 2 === $meeting_type ? esc_html_e( 'Meeting recurrence:', 'meetinghub' ) : esc_html_e( 'Webinar recurrence:', 'meetinghub' ); ?></dt>
													<dd> <?php esc_html_e( 'Every', 'meetinghub' ); ?> <?php echo esc_html( $recurring_repeat_interval ); ?> <span class="mhub_reday"> <?php echo esc_html( $recurring_repeat_name ); ?> </span> <?php esc_html_e( 'at', 'meetinghub' ); ?> <span class="mhub_retime"> <?php echo esc_html( gmdate( 'H:i', strtotime( $next_meeting_time ) ) ); ?>  </span></dd>

													<dt><?php esc_html_e( 'Next Start Time:', 'meetinghub' ); ?></dt>
													<dd class="mhbu-tm">
														<?php

														echo esc_html( gmdate( 'l, F j, Y g:i A', strtotime( $next_meeting_time ) ) );

														?>
													</dd>


													<dt><?php esc_html_e( 'Duration:', 'meetinghub' ); ?></dt>
													<dd class="mhub-duration">
													<?php
													if ( 60 <= $recurring_duration ) {
														$hours   = floor( $recurring_duration / 60 );
														$minutes = $recurring_duration % 60;

														// Get the correct translation for "hour" or "hours".
														$hour_text = ( 1 == $hours ) ? __( 'hour', 'meetinghub' ) : __( 'hours', 'meetinghub' );
														$minute_text = ( 1 == $minutes ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );

														// Print hours.
														echo esc_html( number_format_i18n( $hours ) ) . ' ' . esc_html( $hour_text );

														// Print minutes if greater than 0.
														if ( $minutes > 0 ) {
															echo ' ' . esc_html( number_format_i18n( $minutes ) ) . ' ' . esc_html( $minute_text );
														}
													} else {
														// Get the correct translation for "minute" or "minutes".
														$minute_text = ( 1 == $recurring_duration ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );

														// Print minutes only.
														echo esc_html( number_format_i18n( $recurring_duration ) ) . ' ' . esc_html( $minute_text );
													}
													?>

													</dd>

												<?php } ?>

												<?php if ( ! $options['enable_recurring_meeting'] ) { ?>
													<dt><?php esc_html_e( 'Duration:', 'meetinghub' ); ?></dt>
													<dd class="mhub-duration">
													<?php
													if ( 60 <= $meeting_duration ) {
														$hours   = floor( $meeting_duration / 60 );
														$minutes = $meeting_duration % 60;

														// Get the correct translation for "hour" or "hours".
														$hour_text   = ( 1 == $hours ) ? __( 'hour', 'meetinghub' ) : __( 'hours', 'meetinghub' );
														$minute_text = ( 1 == $minutes ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );

														// Print hours.
														echo esc_html( number_format_i18n( $hours ) ) . ' ' . esc_html( $hour_text );

														// Print minutes if greater than 0.
														if ( $minutes > 0 ) {
															echo ' ' . esc_html( number_format_i18n( $minutes ) ) . ' ' . esc_html( $minute_text );
														}
													} else {
														// Get the correct translation for "minute" or "minutes".
														$minute_text = ( 1 == $meeting_duration ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );

														// Print minutes only.
														echo esc_html( number_format_i18n( $meeting_duration ) ) . ' ' . esc_html( $minute_text );
													}
													?>
													</dd>
												<?php } ?>

												<dt><?php esc_html_e( 'Current Timezone:', 'meetinghub' ); ?></dt>
												<dd class="mhub-tz">
												<?php
													echo esc_html( $time_zone );
												?>
												</dd>
												
											</dl>
										</div>
									</div>

									<div class="mhub-buy-btn-wpapper">
										<?php
										if ( ! empty( $product_id ) && 'yes' === $meeting_as_product && ! current_user_can( 'manage_options' ) ) {
											$product_link = get_permalink( $product_id );
											?>
											<a href="<?php echo esc_url( $product_link ); ?>" class="mhub-buy-button" target="__blank"> <?php esc_attr_e( 'Buy Now !', 'meetinghub' ); ?></a>
										
										<?php } ?>
									</div>
								</div>
						</div>
						<?php } ?>

						<?php
						if ( 'yes' !== $hide_calendar ) {
							if ( mhub_fs()->can_use_premium_code__premium_only() ) {
								?>
								<div class="mhub-col-12" style="float: none; margin-top:40px;">
									<div class="mhub-sidebar">
								<?php
									require_once MHUB_PRO_INCLUDES . '/Templates/mhub-calander.php';
								?>
									</div>
								</div>
								<?php
							}
						}
						?>
					</div>
					<?php
				}
			} else {
				?>
				<h1> <?php echo esc_html__( 'Please use correct shortcode', 'meetinghub' ); ?></h1>
				<?php
			}

			return ob_get_clean();
		}

		/**
		 * Renders the MeetingHub Zoom Webinar shortcode.
		 *
		 * This function handles the rendering of the MeetingHub Zoom shortcode, which is used to display
		 * Zoom meeting information on a WordPress site. It processes the shortcode attributes, retrieves
		 * meeting details from the post meta, and generates the necessary HTML and CSS to display the
		 * meeting information.
		 *
		 * @param array $atts The shortcode attributes. Includes:
		 *   - 'id' (int): The ID of the post containing the Zoom meeting details.
		 *
		 * @return string The rendered HTML content for the shortcode.
		 */
		public function mhub_zoom_webinar_shortcode_render( $atts ) {
			if ( ! empty( $atts ) ) {
				// Extract shortcode attributes, including the 'id' attribute if provided.
				$atts = shortcode_atts(
					array(
						'id'            => $atts['id'],
						'hide_details'  => $atts['hide_details'],
						'hide_timer'    => $atts['hide_timer'],
						'hide_calendar' => mhub_fs()->can_use_premium_code__premium_only() ? $atts['hide_calendar'] : '',
					),
					$atts,
					'mhub-zoom-webinar'
				);

				// Check if the post exists.
				if ( ! get_post_status( $atts['id'] ) ) {
					return '<h1>' . esc_html__( 'The webinar has ended or does not exist.', 'meetinghub' ) . '</h1>';
				}

				ob_start();

				$serialize_options = get_post_meta( $atts['id'], 'mhub__meeting_settings', true );
				$options           = maybe_unserialize( $serialize_options );

				$user_id           = get_current_user_id();
				$current_user_data = get_userdata( $user_id );
				$current_user_name = $current_user_data ? $current_user_data->display_name : '';

				$meeting_status       = get_post_meta( $atts['id'], 'mhub_meeting_status', true );
				$meeting_start_status = get_post_meta( $atts['id'], 'mhub_meeting_start_status', true );
				$hide_sidebar         = $options['hide_sidebar'];
				$time_zone            = $options['meeting_timezone'];
				$enable_user_zone     = isset( $options['display_time_user_zone'] ) ? $options['display_time_user_zone'] : '';

				$gmt_array       = mhub_get_gmt_offset( $options['meeting_timezone'] );
				$gmt_offset_val  = $gmt_array['gmt_offset_val'];
				$gmt_offset      = $gmt_array['gmt_offset'];
				$should_register = false;
				$attendee_login  = false;
				$login_status;
				$meeting_as_product;
				$product_id;
				$po_meeting_id = $atts['id'];
				$hide_details  = $atts['hide_details'];
				$hide_timer    = $atts['hide_timer'];
				$hide_calendar = $atts['hide_calendar'];

				if ( mhub_fs()->can_use_premium_code__premium_only() ) {
					$cookie_key         = "mhub_login_status_{$po_meeting_id}";
					$login_status       = isset( $_COOKIE[ $cookie_key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) ) : '';
					$meeting_as_product = get_post_meta( $atts['id'], 'mhub_connect_as_product', true );
					$product_id         = get_post_meta( $atts['id'], 'mhub_connect_product_id', true );
					$should_register    = isset( $options['enable_should_register'] ) ? $options['enable_should_register'] : false;
				}

				// Use the specified or default post ID to get the post meta.

				$start_url           = get_post_meta( $atts['id'], 'meetinghub_zoom_start_url', true );
				$meeting_id          = get_post_meta( $atts['id'], 'meetinghub_zoom_webinar_id', true );
				$join_url            = get_post_meta( $atts['id'], 'meetinghub_zoom_join_url', true );
				$meeting_duration    = get_post_meta( $atts['id'], 'mhub_zoom_meeting_duration', true );
				$meeting_description = get_post_meta( $atts['id'], 'meeting_description', true );
				$thumbnail_html      = get_the_post_thumbnail( $atts['id'] );
				$meeting_title       = get_the_title( $atts['id'] );
				$meeting_type        = intval( $options['meeting_type'] );

				$zoom_api      = \SOVLIX\MHUB\Zoom\Mhub_Zoom_Api::instance();
				$zoom_response = json_decode( $zoom_api->get_webinar_info( $meeting_id ) );
				$start_time    = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $options['startDateTime'] ) + $gmt_offset_val );

				$meeting_id;
				$password = '';

				$next_meeting_time   = '';
				$meeting_deleted     = false;
				$meeting_not_created = false;
				$is_schedule         = false;

				if ( isset( $zoom_response->code ) ) {
					if ( 3001 === $zoom_response->code ) {
						$meeting_deleted = true;
					}

					if ( 2300 === $zoom_response->code ) {
						$meeting_not_created = true;
					}
				}

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

				if ( ! $options['enable_recurring_meeting'] ) {

					if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) <= 0 ) {
						$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) - $gmt_offset_val );
						$is_schedule       = true;
					}

					if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) >= 0 ) {
						$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) );
					}
				}

				if ( mhub_fs()->can_use_premium_code__premium_only() ) {
					$attendee_login = 'login_successful' === $login_status ? true : false;
				}

				if ( 'zoom' === $options['selected_platform'] ) {
					?>
					<div class="meetinghub-wrapper" data-mhub-starttime="<?php echo esc_attr( $next_meeting_time ); ?>" data-mhub-timezone="<?php echo esc_attr( $options['meeting_timezone'] ); ?>" data-mhub-title="<?php echo esc_attr( $meeting_title ); ?>" data-mhub-permalilk="<?php echo esc_url( get_permalink( $po_meeting_id ) ); ?>" data-mhub-enable-user-zone="<?php echo esc_attr( $enable_user_zone ); ?>">
						<div class="mhub-col-12" style="float: none;">
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
						</div>

						<?php if ( 'yes' !== $hide_timer ) { ?>
						<div class="mhub-col-12" style="float: none;">
							<div class="mhub-sidebar">
								<div class="mhub-single-widget mhub-single-widget-countdown mhub-shortcode-widget">
									<h4 class="mhub-widget-title" style="margin:0; color:#fff;"><?php esc_html_e( 'Time to go', 'meetinghub' ); ?></h4>
									<div class="mhub-widget-inner">
									<?php if ( ! $meeting_not_created ) { ?>
										<?php if ( ! $meeting_deleted ) { ?>
											<?php if ( 'end' !== $meeting_status ) { ?>
												<?php if ( $is_schedule ) { ?>
													<div class="meetinghub_start_time" data-meeting-start-time="<?php echo esc_attr( $next_meeting_time ); ?>" data-meeting-time-offset="<?php echo esc_attr( $gmt_offset_val ); ?>" > </div>
													<?php } else { ?>
														<span class="mhub-countdown-wrapper">
															<span class="mhub-countdown-value"><?php esc_html_e( 'Webinar is running', 'meetinghub' ); ?></span>
															<span class="mhub-countdown-label"><?php esc_html_e( 'The webinar is started and running', 'meetinghub' ); ?></span>
														</span>
													<?php } ?>
											<?php } else { ?>
												<span class="mhub-countdown-wrapper">
													<span class="mhub-countdown-value"><?php esc_html_e( 'Webinar is finished', 'meetinghub' ); ?></span>
													<span class="mhub-countdown-label"><?php esc_html_e( 'This webinar has been ended by the host.', 'meetinghub' ); ?></span>
												</span>
											<?php } ?>

										<?php } else { ?>
											<span class="mhub-countdown-wrapper">
												<span class="mhub-countdown-value"><?php esc_html_e( 'Webinar does not exist', 'meetinghub' ); ?></span>
												<span class="mhub-countdown-label"><?php esc_html_e( 'The webinar is not exists in zoom account.', 'meetinghub' ); ?></span>
											</span>
										<?php } ?>
									<?php } else { ?>
										<?php if ( current_user_can( 'manage_options' ) ) { ?>
											<span class="mhub-countdown-wrapper">
												<span class="mhub-countdown-value"><?php esc_html_e( 'Webinar not created', 'meetinghub' ); ?></span>
												<span class="mhub-countdown-label"><?php esc_html_e( 'You have to setup zoom credentils first from settings.', 'meetinghub' ); ?></span>
											</span>
										<?php } else { ?>
											<span class="mhub-countdown-wrapper">
												<span class="mhub-countdown-value"><?php esc_html_e( 'Webinar does not exist', 'meetinghub' ); ?></span>
												<span class="mhub-countdown-label"><?php esc_html_e( 'The webinar is not exists in zoom account.', 'meetinghub' ); ?></span>
											</span>
										<?php } ?>
									<?php } ?>
									</div>
								</div>
							</div>
						</div>
						<?php } ?>

						<div class="mhub-col-12" style="float: none;">
							<?php if ( ! $meeting_not_created ) { ?>
								<?php if ( ! $meeting_deleted ) { ?>
									<?php if ( 'end' !== $meeting_status ) { ?>
										<?php if ( ( $should_register && $attendee_login ) || current_user_can( 'manage_options' ) || ! $should_register ) { ?>
											<div class="mhub-shortcode-zoom-join-links">
												<a target="_blank" href="<?php echo esc_url( $join_url ); ?>" rel="nofollow" class="meetinghub-button meetinghub-join-app"><?php esc_html_e( 'JOIN IN ZOOM APP', 'meetinghub' ); ?></a>

												<a class="meetinghub-button meetinghub-zoom-join-web" target="_blank" href="<?php echo esc_attr( add_query_arg( array( 'display_meeting' => '1' ), get_permalink( $atts['id'] ) ) ); ?>" title="JOIN IN BROWSER"><?php esc_html_e( 'JOIN IN BROWSER', 'meetinghub' ); ?></a>

												<?php if ( current_user_can( 'manage_options' ) ) { ?>
												<a target="_blank" href="<?php echo esc_url( $start_url ); ?>" rel="nofollow" class="meetinghub-button meetinghub-join-app"><?php esc_html_e( 'START WEBINAR', 'meetinghub' ); ?></a>
												<?php } ?>
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
								
								<?php } else { ?>
									<div class="meeting-not-started">
										<?php esc_html_e( 'Meeting does not exist.', 'meetinghub' ); ?>
									</div>
								<?php } ?>
							<?php } else { ?>
								<?php if ( current_user_can( 'manage_options' ) ) { ?>
									<div class="meeting-not-started">
										<?php esc_html_e( 'Meeting not created yet. Please setup api in settings first.', 'meetinghub' ); ?>
									</div>
								<?php } else { ?>
									<div class="meeting-not-started">
										<?php esc_html_e( 'Meeting does not exist.', 'meetinghub' ); ?>
									</div>
								<?php } ?>
							<?php } ?>
						</div>
									
						<?php if ( 'yes' !== $hide_details ) { ?>
						<div class="mhub-col-12" style="float: none;">
							<div class="mhub-sidebar">
								<div class="mhub-single-widget mhub-single-widget-detail">
										<h4 class="mhub-widget-title" style="margin:0; color:#fff;"><?php esc_html_e( 'Details', 'meetinghub' ); ?></h4>
										<div class="mhub-widget-inner">
											<dl>
												<dt><?php esc_html_e( 'Topic:', 'meetinghub' ); ?></dt>
												<dd><?php echo esc_html( the_title() ); ?></dd>
												<dt><?php esc_html_e( 'Hosted By:', 'meetinghub' ); ?></dt>
												<dd>
												<?php
													echo esc_html( $current_user_name );
												?>
												</dd>

												<?php if ( ! $options['enable_recurring_meeting'] ) { ?>
													<dt><?php esc_html_e( 'Start Time:', 'meetinghub' ); ?></dt>
													<dd class="mhbu-tm">
													<?php

													echo esc_html( gmdate( 'l, F j, Y g:i A', strtotime( $start_time ) ) );

													?>
													</dd>

												<?php } ?>

												<?php if ( $options['enable_recurring_meeting'] && ! empty( $zoom_response->occurrences ) ) { ?>
													<dt><?php 2 === $meeting_type ? esc_html_e( 'Recurring Meeting schedule:', 'meetinghub' ) : esc_html_e( 'Recurring Webinar schedule:', 'meetinghub' ); ?></dt>
													<dd style="margin-bottom:0;"> 
														<?php esc_html_e( 'From:', 'meetinghub' ); ?> 
														<span class="muhb-st"> <?php echo esc_html( gmdate( 'd M Y', strtotime( $next_meeting_time ) ) ); ?></span>
													</dd>
													<dd> 
														<?php esc_html_e( 'To:', 'meetinghub' ); ?> 
														<span class="muhb-et"> <?php echo esc_html( $recurring_end_date ); ?></span>
													</dd>

													<dt><?php 2 === $meeting_type ? esc_html_e( 'Meeting recurrence:', 'meetinghub' ) : esc_html_e( 'Webinar recurrence:', 'meetinghub' ); ?></dt>
													<dd> <?php esc_html_e( 'Every', 'meetinghub' ); ?> <?php echo esc_html( $recurring_repeat_interval ); ?> <span class="mhub_reday"> <?php echo esc_html( $recurring_repeat_name ); ?> </span> <?php esc_html_e( 'at', 'meetinghub' ); ?> <span class="mhub_retime"> <?php echo esc_html( gmdate( 'H:i', strtotime( $next_meeting_time ) ) ); ?>  </span></dd>

													<dt><?php esc_html_e( 'Next Start Time:', 'meetinghub' ); ?></dt>
													<dd class="mhbu-tm">
														<?php

														echo esc_html( gmdate( 'l, F j, Y g:i A', strtotime( $next_meeting_time ) ) );

														?>
													</dd>


													<dt><?php esc_html_e( 'Duration:', 'meetinghub' ); ?></dt>
													<dd class="mhub-duration">
													<?php
													if ( 60 <= $recurring_duration ) {
														$hours   = floor( $recurring_duration / 60 );
														$minutes = $recurring_duration % 60;

														// Get the correct translation for "hour" or "hours".
														$hour_text = ( 1 == $hours ) ? __( 'hour', 'meetinghub' ) : __( 'hours', 'meetinghub' );
														$minute_text = ( 1 == $minutes ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );

														// Print hours.
														echo esc_html( number_format_i18n( $hours ) ) . ' ' . esc_html( $hour_text );

														// Print minutes if greater than 0.
														if ( $minutes > 0 ) {
															echo ' ' . esc_html( number_format_i18n( $minutes ) ) . ' ' . esc_html( $minute_text );
														}
													} else {
														// Get the correct translation for "minute" or "minutes".
														$minute_text = ( 1 == $recurring_duration ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );

														// Print minutes only.
														echo esc_html( number_format_i18n( $recurring_duration ) ) . ' ' . esc_html( $minute_text );
													}
													?>

													</dd>

												<?php } ?>

												<?php if ( ! $options['enable_recurring_meeting'] ) { ?>
													<dt><?php esc_html_e( 'Duration:', 'meetinghub' ); ?></dt>
													<dd class="mhub-duration">
													<?php
													if ( 60 <= $meeting_duration ) {
														$hours   = floor( $meeting_duration / 60 );
														$minutes = $meeting_duration % 60;

														// Get the correct translation for "hour" or "hours".
														$hour_text   = ( 1 == $hours ) ? __( 'hour', 'meetinghub' ) : __( 'hours', 'meetinghub' );
														$minute_text = ( 1 == $minutes ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );

														// Print hours.
														echo esc_html( number_format_i18n( $hours ) ) . ' ' . esc_html( $hour_text );

														// Print minutes if greater than 0.
														if ( $minutes > 0 ) {
															echo ' ' . esc_html( number_format_i18n( $minutes ) ) . ' ' . esc_html( $minute_text );
														}
													} else {
														// Get the correct translation for "minute" or "minutes".
														$minute_text = ( 1 == $meeting_duration ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );

														// Print minutes only.
														echo esc_html( number_format_i18n( $meeting_duration ) ) . ' ' . esc_html( $minute_text );
													}
													?>
													</dd>
												<?php } ?>

												<dt><?php esc_html_e( 'Current Timezone:', 'meetinghub' ); ?></dt>
												<dd class="mhub-tz">
												<?php
													echo esc_html( $time_zone );
												?>
												</dd>
												
											</dl>
										</div>
									</div>

									<div class="mhub-buy-btn-wpapper">
										<?php
										if ( ! empty( $product_id ) && 'yes' === $meeting_as_product && ! current_user_can( 'manage_options' ) ) {
											$product_link = get_permalink( $product_id );
											?>
											<a href="<?php echo esc_url( $product_link ); ?>" class="mhub-buy-button" target="__blank"> <?php esc_attr_e( 'Buy Now !', 'meetinghub' ); ?></a>
										
										<?php } ?>
									</div>
								</div>
						</div>
						<?php } ?>

						<?php
						if ( 'yes' !== $hide_calendar ) {
							if ( mhub_fs()->can_use_premium_code__premium_only() ) {
								?>
								<div class="mhub-col-12" style="float: none; margin-top:40px;">
									<div class="mhub-sidebar">
								<?php
									require_once MHUB_PRO_INCLUDES . '/Templates/mhub-calander.php';
								?>
									</div>
								</div>
								<?php
							}
						}
						?>
					</div>
					<?php
				}
			} else {
				?>
				<h1> <?php echo esc_html__( 'Please use correct shortcode', 'meetinghub' ); ?></h1>
				<?php
			}

			return ob_get_clean();
		}

		/**
		 * Renders the Webex meeting details using a shortcode.
		 *
		 * This function processes the shortcode attributes, retrieves relevant
		 * meeting information from the post meta, and generates HTML to display
		 * the meeting details and join link. It handles both the Pro and Free
		 * versions of the plugin, checks the meeting status, and manages user
		 * authentication and registration if required.
		 *
		 * @param array $atts {
		 *     Optional. An array of shortcode attributes.
		 *
		 *     @type int $id The ID of the post containing the meeting settings. Default is the current post ID.
		 * }
		 * @return string The HTML content for displaying the Webex meeting details.
		 */
		public function mhub_webex_shortcode_render( $atts ) {
			if ( ! empty( $atts ) ) {
				// Extract shortcode attributes, including the 'id' attribute if provided.
				$atts = shortcode_atts(
					array(
						'id'            => $atts['id'],
						'hide_details'  => $atts['hide_details'],
						'hide_timer'    => $atts['hide_timer'],
						'hide_calendar' => mhub_fs()->can_use_premium_code__premium_only() ? $atts['hide_calendar'] : '',
					),
					$atts,
					'mhub-webex-meeting'
				);

				// Check if the post exists.
				if ( ! get_post_status( $atts['id'] ) ) {
					return '<h1>' . esc_html__( 'The meeting has ended or does not exist.', 'meetinghub' ) . '</h1>';
				}

				ob_start();

				$serialize_options = get_post_meta( $atts['id'], 'mhub__meeting_settings', true );
				$options           = maybe_unserialize( $serialize_options );

				$user_id           = get_current_user_id();
				$current_user_data = get_userdata( $user_id );
				$current_user_name = $current_user_data ? $current_user_data->display_name : '';

				$meeting_status       = get_post_meta( $atts['id'], 'mhub_meeting_status', true );
				$meeting_start_status = get_post_meta( $atts['id'], 'mhub_meeting_start_status', true );
				$meeting_description  = get_post_meta( $atts['id'], 'meeting_description', true );
				$thumbnail_html       = get_the_post_thumbnail( $atts['id'] );
				$meeting_title        = get_the_title( $atts['id'] );
				$hide_sidebar         = $options['hide_sidebar'];
				$time_zone            = $options['meeting_timezone'];
				$enable_user_zone     = isset( $options['display_time_user_zone'] ) ? $options['display_time_user_zone'] : '';

				$gmt_array       = mhub_get_gmt_offset( $options['meeting_timezone'] );
				$gmt_offset_val  = $gmt_array['gmt_offset_val'];
				$gmt_offset      = $gmt_array['gmt_offset'];
				$should_register = false;
				$attendee_login  = false;
				$login_status;
				$meeting_as_product;
				$product_id;
				$po_meeting_id = $atts['id'];
				$hide_details  = $atts['hide_details'];
				$hide_timer    = $atts['hide_timer'];
				$hide_calendar = $atts['hide_calendar'];

				if ( mhub_fs()->can_use_premium_code__premium_only() ) {
					$cookie_key         = "mhub_login_status_{$po_meeting_id}";
					$login_status       = isset( $_COOKIE[ $cookie_key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) ) : '';
					$meeting_as_product = get_post_meta( $atts['id'], 'mhub_connect_as_product', true );
					$product_id         = get_post_meta( $atts['id'], 'mhub_connect_product_id', true );
					$should_register    = isset( $options['enable_should_register'] ) ? $options['enable_should_register'] : false;
				}

				// Use the specified or default post ID to get the post meta.

				$is_schedule       = false;
				$next_meeting_time = '';

				$start_time       = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $options['startDateTime'] ) + $gmt_offset_val );
				$meeting_duration = get_post_meta( $atts['id'], 'mhub_webex_meeting_duration', true );
				$join_link        = get_post_meta( $atts['id'], 'mhub_webex_join_link', true );
				$meeting_agenda   = $options['agenda'];
				$meeting_id       = get_post_meta( $atts['id'], 'mhub_webex_meeting_id', true );

				$webex_api        = \SOVLIX\MHUB\API\Webex_Api::get_instance();
				$webex_response   = $webex_api->get_meeting( $meeting_id );
				$response_message = '';

				if ( isset( $webex_response['errors'] ) ) {
					$response_message = $webex_response['errors'][0]['description'];
				}

				if ( ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + $gmt_offset_val ) - strtotime( $start_time ) <= 0 ) {
					$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) - $gmt_offset_val );
					$is_schedule       = true;
				} else {
					$next_meeting_time = gmdate( 'Y-m-d\TH:i:s.v\Z', strtotime( $start_time ) );
				}

				if ( mhub_fs()->can_use_premium_code__premium_only() ) {
					$attendee_login = 'login_successful' === $login_status ? true : false;
				}

				if ( 'webex' === $options['selected_platform'] ) {
					?>
						<div class="meetinghub-wrapper" data-mhub-starttime="<?php echo esc_attr( $next_meeting_time ); ?>" data-mhub-timezone="<?php echo esc_attr( $options['meeting_timezone'] ); ?>" data-mhub-title="<?php echo esc_attr( $meeting_title ); ?>" data-mhub-permalilk="<?php echo esc_url( get_permalink( $po_meeting_id ) ); ?>" data-mhub-enable-user-zone="<?php echo esc_attr( $enable_user_zone ); ?>">

							<div class="mhub-col-12" style="float: none;">
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
							</div>

							<?php if ( 'yes' !== $hide_timer ) { ?>
							<div class="mhub-col-12" style="float: none;">
								<div class="mhub-webex-shortcode-sidebar">
									<div class="mhub-single-widget mhub-single-widget-countdown mhub-shortcode-widget">
										<h4 class="mhub-widget-title" style="margin:0; color:#fff;"><?php esc_html_e( 'Time to go', 'meetinghub' ); ?></h4>
										<div class="mhub-widget-inner">
											<?php if ( ! isset( $webex_response['errors'] ) ) { ?>
												<?php if ( 'end' !== $meeting_status ) { ?>
													<?php if ( $is_schedule ) { ?>
														<div class="meetinghub_start_time" data-meeting-start-time="<?php echo esc_attr( $next_meeting_time ); ?>" data-meeting-time-offset="<?php echo esc_attr( $gmt_offset_val ); ?>" > </div>
														<?php } else { ?>
															<span class="mhub-countdown-wrapper">
																<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting is running', 'meetinghub' ); ?></span>
																<span class="mhub-countdown-label"><?php esc_html_e( 'The meeting is started and running', 'meetinghub' ); ?></span>
															</span>
														<?php } ?>
												<?php } else { ?>
													<span class="mhub-countdown-wrapper">
														<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting is finished', 'meetinghub' ); ?></span>
														<span class="mhub-countdown-label"><?php esc_html_e( 'This meeting has been ended by the host.', 'meetinghub' ); ?></span>
													</span>
												<?php } ?>
											<?php } else { ?>
												<span class="mhub-countdown-wrapper">
														<span class="mhub-countdown-label"><?php echo esc_html( $response_message ); ?></span>
												</span>

											<?php } ?>
										</div>
									</div>
								</div>
							</div>
							<?php } ?>

							<div class="mhub-col-12" style="float: none;">
								<?php if ( ! isset( $webex_response['errors'] ) ) { ?>
									<?php if ( 'end' !== $meeting_status ) { ?>
										<?php if ( ( $should_register && $attendee_login ) || current_user_can( 'manage_options' ) || ! $should_register ) { ?>
											<?php if ( ! empty( $meeting_agenda ) ) { ?>
											<h3 class="webex-agenda-title"><?php esc_html_e( 'Meeting agenda', 'meetinghub' ); ?> </h3>
											<p class="webex-agenda-desc"><?php echo esc_html( $meeting_agenda ); ?></p>
										<?php } ?>

										<div class="mhub-webex-st-join-links">
											<a class="meetinghub-webex-button mhub-webex-join" target="_blank" href="<?php echo esc_url( $join_link ); ?>" title="JOIN NOW"><?php esc_html_e( 'JOIN NOW', 'meetinghub' ); ?></a>
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
										<?php } else { ?>
											<div class="meeting-not-started">
												<?php echo esc_html( $response_message ); ?>
											</div>

									<?php } ?>
							</div>

							<?php if ( 'yes' !== $hide_details ) { ?>
							<div class="mhub-col-12" style="float: none;">
								<div class="mhub-sidebar">
									<div class="mhub-single-widget mhub-single-widget-detail">
										<h4 class="mhub-widget-title" style="margin:0; color:#fff;"><?php esc_html_e( 'Details', 'meetinghub' ); ?></h4>
										<div class="mhub-widget-inner">
											<dl>
												<dt><?php esc_html_e( 'Topic:', 'meetinghub' ); ?></dt>
												<dd><?php echo esc_html( the_title() ); ?></dd>
												<dt><?php esc_html_e( 'Hosted By:', 'meetinghub' ); ?></dt>
												<dd>
												<?php
													echo esc_html( $current_user_name );
												?>
												</dd>

												<dt><?php esc_html_e( 'Start Time:', 'meetinghub' ); ?></dt>
												<dd class="mhbu-tm">
												<?php

												echo esc_html( gmdate( 'l, F j, Y g:i A', strtotime( $start_time ) ) );

												?>
												</dd>

												<dt><?php esc_html_e( 'Duration:', 'meetinghub' ); ?></dt>
												<dd class="mhub-duration">
													<?php
													if ( 60 <= $meeting_duration ) {
														$hours   = floor( $meeting_duration / 60 );
														$minutes = $meeting_duration % 60;
							
														// Get the correct translation for "hour" or "hours".
														$hour_text   = ( 1 == $hours ) ? __( 'hour', 'meetinghub' ) : __( 'hours', 'meetinghub' );
														$minute_text = ( 1 == $minutes ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );
							
														// Print hours.
														echo esc_html( number_format_i18n( $hours ) ) . ' ' . esc_html( $hour_text );
							
														// Print minutes if greater than 0.
														if ( $minutes > 0 ) {
															echo ' ' . esc_html( number_format_i18n( $minutes ) ) . ' ' . esc_html( $minute_text );
														}
													} else {
														// Get the correct translation for "minute" or "minutes".
														$minute_text = ( 1 == $meeting_duration ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );

														// Print minutes only.
														echo esc_html( number_format_i18n( $meeting_duration ) ) . ' ' . esc_html( $minute_text );
													}
													?>
												</dd>

												<dt><?php esc_html_e( 'Current Timezone:', 'meetinghub' ); ?></dt>
												<dd class="mhub-tz">
												<?php
													echo esc_html( $time_zone );
												?>
												</dd>
												
											</dl>
										</div>
									</div>

									<div class="mhub-buy-btn-wpapper">
										<?php
										if ( ! empty( $product_id ) && 'yes' === $meeting_as_product && ! current_user_can( 'manage_options' ) ) {
											$product_link = get_permalink( $product_id );
											?>
											<a href="<?php echo esc_url( $product_link ); ?>" class="mhub-buy-button" target="__blank"> <?php esc_attr_e( 'Buy Now !', 'meetinghub' ); ?></a>
										
										<?php } ?>
									</div>
								</div>
							</div>
							<?php } ?>

							<?php
							if ( 'yes' !== $hide_calendar ) {
								if ( mhub_fs()->can_use_premium_code__premium_only() ) {
									?>
									<div class="mhub-col-12" style="float: none; margin-top:40px;">
										<div class="mhub-sidebar">
									<?php
										require_once MHUB_PRO_INCLUDES . '/Templates/mhub-calander.php';
									?>
										</div>
									</div>
									<?php
								}
							}
							?>
					<?php
				}
			} else {
				?>
				<h1> <?php echo esc_html__( 'Please use correct shortcode', 'meetinghub' ); ?></h1>
				<?php
			}

			return ob_get_clean();
		}

		/**
		 * Renders the Google meeting details using a shortcode.
		 *
		 * This function processes the shortcode attributes, retrieves relevant
		 * meeting information from the post meta, and generates HTML to display
		 * the meeting details and join link. It handles both the Pro and Free
		 * versions of the plugin, checks the meeting status, and manages user
		 * authentication and registration if required.
		 *
		 * @param array $atts {
		 *     Optional. An array of shortcode attributes.
		 *
		 *     @type int $id The ID of the post containing the meeting settings. Default is the current post ID.
		 * }
		 * @return string The HTML content for displaying the Webex meeting details.
		 */
		public function mhub_google_meet_shortcode_render( $atts ) {
			if ( ! empty( $atts ) ) {
				// Extract shortcode attributes, including the 'id' attribute if provided.
				$atts = shortcode_atts(
					array(
						'id'            => $atts['id'],
						'hide_details'  => $atts['hide_details'],
						'hide_timer'    => $atts['hide_timer'],
						'hide_calendar' => mhub_fs()->can_use_premium_code__premium_only() ? $atts['hide_calendar'] : '',
					),
					$atts,
					'mhub-google-meet'
				);

				ob_start();

				$serialize_options = get_post_meta( $atts['id'], 'mhub__meeting_settings', true );
				$options           = maybe_unserialize( $serialize_options );

				$user_id           = get_current_user_id();
				$current_user_data = get_userdata( $user_id );
				$current_user_name = $current_user_data ? $current_user_data->display_name : '';

				$meeting_status       = get_post_meta( $atts['id'], 'mhub_meeting_status', true );
				$meeting_start_status = get_post_meta( $atts['id'], 'mhub_meeting_start_status', true );
				$meeting_description  = get_post_meta( $atts['id'], 'meeting_description', true );
				$thumbnail_html       = get_the_post_thumbnail( $atts['id'] );
				$meeting_title        = get_the_title( $atts['id'] );
				$hide_sidebar         = $options['hide_sidebar'];
				$time_zone            = $options['meeting_timezone'];
				$enable_user_zone     = isset( $options['display_time_user_zone'] ) ? $options['display_time_user_zone'] : '';

				$gmt_array       = mhub_get_gmt_offset( $options['meeting_timezone'] );
				$gmt_offset_val  = $gmt_array['gmt_offset_val'];
				$gmt_offset      = $gmt_array['gmt_offset'];
				$should_register = false;
				$attendee_login  = false;
				$login_status;
				$meeting_as_product;
				$product_id;
				$po_meeting_id = $atts['id'];
				$hide_details  = $atts['hide_details'];
				$hide_timer    = $atts['hide_timer'];
				$hide_calendar = $atts['hide_calendar'];

				if ( mhub_fs()->can_use_premium_code__premium_only() ) {
					$cookie_key         = "mhub_login_status_{$po_meeting_id}";
					$login_status       = isset( $_COOKIE[ $cookie_key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) ) : '';
					$meeting_as_product = get_post_meta( $atts['id'], 'mhub_connect_as_product', true );
					$product_id         = get_post_meta( $atts['id'], 'mhub_connect_product_id', true );
					$should_register    = isset( $options['enable_should_register'] ) ? $options['enable_should_register'] : false;
				}

				// Use the specified or default post ID to get the post meta.

				$is_schedule       = false;
				$next_meeting_time = '';

				$start_time       = '';
				$meeting_duration = get_post_meta( $atts['id'], 'mhub_google_meeting_duration', true );
				$join_link        = get_post_meta( $atts['id'], 'mhub_google_join_link', true );
				$meeting_id       = get_post_meta( $atts['id'], 'mhub_google_meeting_id', true );
				$recurrence_info  = '';
				$recurrence_data  = null;

				if ( $options['enable_recurring_meeting'] && mhub_fs()->can_use_premium_code__premium_only() && function_exists( 'mhub_get_next_meeting_occurrence' ) ) {
					$recurrence_data = mhub_get_next_meeting_occurrence( $meeting_id, $atts['id'], $options );
				}

				if ( $options['enable_recurring_meeting'] && mhub_fs()->can_use_premium_code__premium_only() && function_exists( 'mhub_get_next_meeting_occurrence' ) ) {
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

				if ( mhub_fs()->can_use_premium_code__premium_only() ) {
					$attendee_login = 'login_successful' === $login_status ? true : false;
				}

				if ( 'google_meet' === $options['selected_platform'] ) {
					?>
						<div class="meetinghub-wrapper" data-mhub-starttime="<?php echo esc_attr( $next_meeting_time ); ?>" data-mhub-timezone="<?php echo esc_attr( $options['meeting_timezone'] ); ?>" data-mhub-title="<?php echo esc_attr( $meeting_title ); ?>" data-mhub-permalilk="<?php echo esc_url( get_permalink( $po_meeting_id ) ); ?>" data-mhub-enable-user-zone="<?php echo esc_attr( $enable_user_zone ); ?>">

							<div class="mhub-col-12" style="float: none;">
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
							</div>

							<?php if ( 'yes' !== $hide_timer ) { ?>
							<div class="mhub-col-12" style="float: none;">
								<div class="mhub-google-meet-shortcode-sidebar">
									<div class="mhub-single-widget mhub-single-widget-countdown mhub-shortcode-widget">
										<h4 class="mhub-widget-title" style="margin:0; color:#fff;"><?php esc_html_e( 'Time to go', 'meetinghub' ); ?></h4>
										<div class="mhub-widget-inner">
											<?php if ( 'end' !== $meeting_status ) { ?>
												<?php if ( $is_schedule ) { ?>
													<div class="meetinghub_start_time" data-meeting-start-time="<?php echo esc_attr( $next_meeting_time ); ?>" data-meeting-time-offset="<?php echo esc_attr( $gmt_offset_val ); ?>" > </div>
													<?php } else { ?>
														<span class="mhub-countdown-wrapper">
															<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting is running', 'meetinghub' ); ?></span>
															<span class="mhub-countdown-label"><?php esc_html_e( 'The meeting is started and running', 'meetinghub' ); ?></span>
														</span>
													<?php } ?>
											<?php } else { ?>
												<span class="mhub-countdown-wrapper">
													<span class="mhub-countdown-value"><?php esc_html_e( 'Meeting is finished', 'meetinghub' ); ?></span>
													<span class="mhub-countdown-label"><?php esc_html_e( 'This meeting has been ended by the host.', 'meetinghub' ); ?></span>
												</span>
											<?php } ?>
										</div>
									</div>
								</div>
							</div>
							<?php } ?>

							<div class="mhub-col-12" style="float: none;">
								<?php if ( 'end' !== $meeting_status ) { ?>
									<?php if ( ( $should_register && $attendee_login ) || current_user_can( 'manage_options' ) || ! $should_register ) { ?>
										<?php
										if ( ! empty( $meeting_description ) ) {
											?>
												<div class="meeting-details">
													<?php echo wp_kses_post( $meeting_description ); ?>
												</div>
											<?php
										}
										?>

									<div class="mhub-google-meet-st-join-links">
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

							<?php if ( 'yes' !== $hide_details ) { ?>
							<div class="mhub-col-12" style="float: none;">
								<div class="mhub-sidebar">
									<div class="mhub-single-widget mhub-single-widget-detail">
										<h4 class="mhub-widget-title" style="margin:0; color:#fff;"><?php esc_html_e( 'Details', 'meetinghub' ); ?></h4>
										<div class="mhub-widget-inner">
											<dl>
												<dt><?php esc_html_e( 'Topic:', 'meetinghub' ); ?></dt>
												<dd><?php echo esc_html( the_title() ); ?></dd>
												<dt><?php esc_html_e( 'Hosted By:', 'meetinghub' ); ?></dt>
												<dd>
												<?php
													echo esc_html( $current_user_name );
												?>
												</dd>

												<dt><?php esc_html_e( 'Start Time:', 'meetinghub' ); ?></dt>
												<dd class="mhbu-tm">
												<?php

												echo esc_html( gmdate( 'l, F j, Y g:i A', strtotime( $start_time ) ) );

												?>
												</dd>

												<?php if ( $options['enable_recurring_meeting'] && mhub_fs()->can_use_premium_code__premium_only() ) { ?>
												<dt><?php esc_html_e( 'Meeting recurrence:', 'meetinghub' ); ?></dt>
												<dd> <?php echo esc_html( $recurrence_info ); ?> </dd>
												<?php } ?>

												<dt><?php esc_html_e( 'Duration:', 'meetinghub' ); ?></dt>
												<dd class="mhub-duration">
													<?php
													if ( 60 <= $meeting_duration ) {
														$hours   = floor( $meeting_duration / 60 );
														$minutes = $meeting_duration % 60;

														// Get the correct translation for "hour" or "hours".
														$hour_text   = ( 1 == $hours ) ? __( 'hour', 'meetinghub' ) : __( 'hours', 'meetinghub' );
														$minute_text = ( 1 == $minutes ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );
														// Print hours.
														echo esc_html( number_format_i18n( $hours ) ) . ' ' . esc_html( $hour_text );

														// Print minutes if greater than 0.
														if ( $minutes > 0 ) {
															echo ' ' . esc_html( number_format_i18n( $minutes ) ) . ' ' . esc_html( $minute_text );
														}
													} else {
														// Get the correct translation for "minute" or "minutes".
														$minute_text = ( 1 == $meeting_duration ) ? __( 'minute', 'meetinghub' ) : __( 'minutes', 'meetinghub' );

														// Print minutes only.
														echo esc_html( number_format_i18n( $meeting_duration ) ) . ' ' . esc_html( $minute_text );
													}
													?>
												</dd>

												<dt><?php esc_html_e( 'Current Timezone:', 'meetinghub' ); ?></dt>
												<dd class="mhub-tz">
												<?php
													echo esc_html( $time_zone );
												?>
												</dd>
												
											</dl>
										</div>
									</div>

									<div class="mhub-buy-btn-wpapper">
										<?php
										if ( ! empty( $product_id ) && 'yes' === $meeting_as_product && ! current_user_can( 'manage_options' ) ) {
											$product_link = get_permalink( $product_id );
											?>
											<a href="<?php echo esc_url( $product_link ); ?>" class="mhub-buy-button" target="__blank"> <?php esc_attr_e( 'Buy Now !', 'meetinghub' ); ?></a>
										
										<?php } ?>
									</div>
								</div>
							</div>
							<?php } ?>

							<?php
							if ( 'yes' !== $hide_calendar ) {
								if ( mhub_fs()->can_use_premium_code__premium_only() ) {
									?>
									<div class="mhub-col-12" style="float: none; margin-top:40px;">
										<div class="mhub-sidebar">
									<?php
										require_once MHUB_PRO_INCLUDES . '/Templates/mhub-calander.php';
									?>
										</div>
									</div>
									<?php
								}
							}
							?>
					<?php
				}
			} else {
				?>
				<h1> <?php echo esc_html__( 'Please use correct shortcode', 'meetinghub' ); ?></h1>
				<?php
			}

			return ob_get_clean();
		}

		/**
		 * Renders the Webex meeting list shortcode.
		 *
		 * This function starts output buffering, prints the container for the Webex meeting list,
		 * and then returns the buffered content.
		 *
		 * @return string The buffered output of the Webex meeting list container.
		 */
		public function mhub_webex_meeting_list_shortcode_render() {
			ob_start();

			?>
			<div id="mhub_webex_meeting_list">
			</div>
			
			<?php

			return ob_get_clean();
		}

		/**
		 * Renders the Jitsi meeting list shortcode.
		 *
		 * This function starts output buffering, prints the container for the Jitsi meeting list,
		 * and then returns the buffered content.
		 *
		 * @return string The buffered output of the Jitsi meeting list container.
		 */
		public function mhub_jitsi_meeting_list_shortcode_render() {
			ob_start();

			?>
			<div id="mhub_jitsi_meeting_list">
			</div>
			
			<?php

			return ob_get_clean();
		}

		/**
		 * Renders the Zoom meeting list shortcode.
		 *
		 * This function starts output buffering, prints the container for the Zoom meeting list,
		 * and then returns the buffered content.
		 *
		 * @return string The buffered output of the Zoom meeting list container.
		 */
		public function mhub_zoom_meeting_list_shortcode_render() {
			ob_start();

			?>
			<div id="mhub_zoom_meeting_list">
			</div>
			
			<?php

			return ob_get_clean();
		}

		/**
		 * Renders the Zoom webinar list shortcode.
		 *
		 * This function starts output buffering, prints the container for the Zoom webinar list,
		 * and then returns the buffered content.
		 *
		 * @return string The buffered output of the Zoom webinar list container.
		 */
		public function mhub_zoom_webinar_list_shortcode_render() {
			ob_start();

			?>
			<div id="mhub_zoom_webinar_list">
			</div>
			
			<?php

			return ob_get_clean();
		}

		/**
		 * Renders the Google Meeting list shortcode.
		 *
		 * This function starts output buffering, prints the container for the Zoom webinar list,
		 * and then returns the buffered content.
		 *
		 * @return string The buffered output of the Zoom webinar list container.
		 */
		public function mhub_google_meeting_list_shortcode_render() {
			ob_start();

			?>
			<div id="mhub_google_meet_meeting_list">
			</div>
			
			<?php

			return ob_get_clean();
		}
	}
}
