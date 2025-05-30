<?php
/**
 * MeetingHub Elementor Element
 * Elementor widget for integrating MeetingHub.
 *
 * @package MeetingHub
 */

namespace SOVLIX\MHUB\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mhub_Google_Meet_Elementor' ) ) {
	/**
	 * MeetingHub Elementor Widget
	 *
	 * @since 1.0.0
	 */
	class Mhub_Google_Meet_Elementor extends Widget_Base {
		/**
		 * Webex Api class.
		 *
		 * @var Mhub_Webex_Api
		 */
		private $google_api;

		/**
		 * Constructor method.
		 *
		 * @param array $data Widget data.
		 * @param array $args Widget args.
		 */
		public function __construct( $data = array(), $args = null ) {
			parent::__construct( $data, $args );

			// Initialize the Webex API instance.
			$this->google_api = new \SOVLIX\MHUB\Googlemeet\Mhub_Events();
		}

		/**
		 * Get widget name
		 *
		 * @return string
		 */
		public function get_name() {
			return 'mhub_google_meet_elementor';
		}

		/**
		 * Get widget title
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'Google Meet (MeetingHub)', 'meetinghub' );
		}

		/**
		 * Get widget icon
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-video-camera';
		}

		/**
		 * Get widget categories
		 *
		 * @return array
		 */
		public function get_categories() {
			return array( 'meetinghub-category' );
		}

		/**
		 * Register widget controls
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'configuration_section',
				array(
					'label' => esc_html__( 'Configuration', 'meetinghub' ),
				)
			);

			$this->add_control(
				'link_only',
				array(
					'label'        => esc_html__( 'Link Only ?', 'meetinghub' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'ON', 'meetinghub' ),
					'label_off'    => esc_html__( 'OFF', 'meetinghub' ),
					'return_value' => 'yes',
					'default'      => 'no',
				)
			);

			$meetings_options     = array();
			$google_meet_response = $this->google_api->get_all_meetings();

			// Check if 'items' is set and is not empty.
			if ( ! empty( $google_meet_response ) && is_array( $google_meet_response ) ) {
				// Loop through the meetings data and extract title and id for each meeting.
				foreach ( $google_meet_response as $meeting ) {
					$meetings_options[ $meeting->id ] = esc_html( $meeting->getSummary() );
				}
			} else {
				$meetings_options[''] = esc_html__( 'No meetings found', 'meetinghub' );
			}

			$this->add_control(
				'meeting_id',
				array(
					'label'   => esc_html__( 'Select Meeting', 'meetinghub' ),
					'type'    => Controls_Manager::SELECT,
					'options' => $meetings_options,
				)
			);

			$this->end_controls_section();
		}

		/**
		 * Render widget output on the frontend
		 *
		 * @return void
		 */
		protected function render() {
			$settings   = $this->get_settings();
			$meeting_id = isset( $settings['meeting_id'] ) ? $settings['meeting_id'] : '';
			$link_only  = isset( $settings['link_only'] ) ? $settings['link_only'] : '';

			// Fetch meeting details based on the selected meeting ID.
			$meeting_details = array();

			if ( ! empty( $meeting_id ) ) {
				// Use the Webex API to fetch meeting details based on the meeting ID.
				$meeting_details = $this->google_api->mhub_get_meeting( $meeting_id );
			}

			?>
			<div>
				<?php
				if ( ! empty( $meeting_details ) ) {
					if ( 'yes' === $link_only ) {
						?>
						<a class="mhub_join_btn" href="<?php echo esc_url( $meeting_details->hangoutLink ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Join meeting', 'meetinghub' ); ?>
						</a>
						<?php
					} else {
						?>
						<table class="mhub-table" data-role="meeting-details">
							<tbody>
								<tr>
									<td ><?php esc_attr_e( 'Meeting ID', 'meetinghub' ); ?></td>
									<td><?php echo ! empty( $meeting_details['id'] ) ? esc_html( $meeting_details->id ) : ''; ?></td>
								</tr>
								<tr>
									<td><?php esc_attr_e( 'Topic', 'meetinghub' ); ?></td>
									<td><?php echo ! empty( $meeting_details->getSummary() ) ? esc_html( $meeting_details->getSummary() ) : ''; ?></td>
								</tr>
								<tr>
									<td><?php esc_attr_e( 'Start Time', 'meetinghub' ); ?></td>
									<td><?php echo ! empty( $meeting_details->start->dateTime ) ? esc_html( gmdate( 'M j, Y, g:i:s A', strtotime( $meeting_details->start->dateTime ) ) ) : ''; ?></td>
								</tr>
								<tr>
									<td><?php esc_attr_e( 'Timezone', 'meetinghub' ); ?></td>
									<td><?php echo ! empty( $meeting_details->start->timeZone ) ? esc_html( $meeting_details->start->timeZone ) : ''; ?></td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Join Link', 'meetinghub' ); ?></td>
									<td><a href="<?php echo esc_url( $meeting_details->hangoutLink ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Join', 'meetinghub' ); ?></a></td>
								</tr>
							</tbody>
						</table>
						<?php
					}
				} else {
					?>
						<h3> <?php esc_html_e( 'No meetings found', 'meetinghub' ); ?></h3>
					<?php
				}
				?>
			</div>
			<?php
		}
	}
}
