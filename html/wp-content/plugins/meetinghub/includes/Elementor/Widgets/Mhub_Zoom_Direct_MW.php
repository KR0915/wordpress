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

if ( ! class_exists( 'Mhub_Zoom_Direct_MW' ) ) {
	/**
	 * MeetingHub Elementor Widget
	 *
	 * @since 1.0.0
	 */
	class Mhub_Zoom_Direct_MW extends Widget_Base {
		/**
		 * Zoom Api class.
		 *
		 * @var Mhub_Zoom_Api
		 */
		private $zoom_api;

		/**
		 * Constructor method.
		 *
		 * @param array $data Widget data.
		 * @param array $args Widget args.
		 */
		public function __construct( $data = array(), $args = null ) {
			parent::__construct( $data, $args );

			// Initialize the Zoom API instance.
			$this->zoom_api = \SOVLIX\MHUB\Zoom\Mhub_Zoom_Api::instance();
		}

		/**
		 * Get widget name
		 *
		 * @return string
		 */
		public function get_name() {
			return 'mhub_zoom_direct_mw';
		}

		/**
		 * Get widget title
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'Zoom Direct Meetings & Webinars (MeetingHub)', 'meetinghub' );
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
				'meeting_type',
				array(
					'label'   => esc_html__( 'Meeting Type', 'meetinghub' ),
					'type'    => Controls_Manager::SELECT,
					'default' => 'meeting',
					'options' => array(
						'meeting' => esc_html__( 'Meeting', 'meetinghub' ),
						'webinar' => esc_html__( 'Webinar', 'meetinghub' ),
					),
				)
			);

			$zoom_users   = get_option( 'mhub_zoom_users' );
			$host_options = array();

			// Check if 'items' is set and is not empty.
			if ( ! empty( $zoom_users ) ) {
				// Loop through the meetings data and extract title and id for each meeting.
				foreach ( $zoom_users as $user ) {
					$host_options[ $user['id'] ] = esc_html( $user['email'] );
				}
			} else {
				$host_options[''] = esc_html__( 'No hosts found', 'meetinghub' );
			}

			$this->add_control(
				'host_id',
				array(
					'label'   => esc_html__( 'Select A Host', 'meetinghub' ),
					'type'    => Controls_Manager::SELECT,
					'options' => $host_options,
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
			$settings     = $this->get_settings_for_display();
			$host_id      = $settings['host_id'];
			$meeting_type = $settings['meeting_type'];

			if ( 'meeting' === $meeting_type ) {
				$zoom_response = json_decode( $this->zoom_api->list_meetings( $host_id ), true );
			}

			if ( 'webinar' === $meeting_type ) {
				$zoom_response = json_decode( $this->zoom_api->list_webinar( $host_id ), true );
			}

			if ( 'meeting' === $meeting_type ) {
				// Check if there's any response.
				if ( isset( $zoom_response['meetings'] ) && is_array( $zoom_response['meetings'] ) && ! empty( $zoom_response['meetings'] ) ) {
					?>
					<div>
						<table class="mhub-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Topic', 'meetinghub' ); ?></th>
									<th><?php esc_html_e( 'Start Time', 'meetinghub' ); ?></th>
									<th><?php esc_html_e( 'Timezone', 'meetinghub' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'meetinghub' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $zoom_response['meetings'] as $meeting ) {
									?>
									<tr>
										<td><?php echo esc_html( $meeting['topic'] ); ?></td>
										<td><?php echo esc_html( gmdate( 'M j, Y, g:i:s A', strtotime( $meeting['start_time'] ) ) ); ?></td>
										<td><?php echo esc_html( $meeting['timezone'] ); ?></td>
										<td><a href="<?php echo esc_url( $meeting['join_url'] ); ?>" rel="permalink" target="_blank"> <?php esc_html_e( 'Join via App', 'meetinghub' ); ?></a></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
					</div>
					<?php
				} else {
					// If no meetings found.
					echo '<p>' . esc_html__( 'No meetings found.', 'meetinghub' ) . '</p>';
				}
			}

			if ( 'webinar' === $meeting_type ) {
				if ( isset( $zoom_response['webinars'] ) && is_array( $zoom_response['webinars'] ) && ! empty( $zoom_response['webinars'] ) ) {
					?>
					<div>
						<table class="mhub-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Topic', 'meetinghub' ); ?></th>
									<th><?php esc_html_e( 'Start Time', 'meetinghub' ); ?></th>
									<th><?php esc_html_e( 'Timezone', 'meetinghub' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'meetinghub' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $zoom_response['webinars'] as $meeting ) {
									?>
									<tr>
										<td><?php echo esc_html( $meeting['topic'] ); ?></td>
										<td><?php echo esc_html( gmdate( 'M j, Y, g:i:s A', strtotime( $meeting['start_time'] ) ) ); ?></td>
										<td><?php echo esc_html( $meeting['timezone'] ); ?></td>
										<td><a href="<?php echo esc_url( $meeting['join_url'] ); ?>" rel="permalink" target="_blank"> <?php esc_html_e( 'Join via App', 'meetinghub' ); ?> </a></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
					</div>
					<?php
				} else {
					// If no meetings found.
					echo '<p>' . esc_html__( 'No Webinar found.', 'meetinghub' ) . '</p>';
				}
			}
		}
	}
}
?>
