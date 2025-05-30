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


if ( ! class_exists( 'Mhub_Jitsi_Elementor' ) ) {
	/**
	 * MeetingHub Elementor Widget
	 *
	 * @since 1.0.0
	 */
	class Mhub_Jitsi_Elementor extends Widget_Base {
		/**
		 * Get widget name
		 *
		 * @return string
		 */
		public function get_name() {
			return 'mhub_jitsi_elementor';
		}

		/**
		 * Get widget title
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'Jitsi Meet (MeetingHub)', 'meetinghub' );
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

			$mhub_jitsi_settings = get_option( 'mhub_jitsi_settings' );

			$jitsi_domain = '';

			if ( ! empty( $mhub_jitsi_settings ) ) {
				$domain_type = $mhub_jitsi_settings['domain_type'];

				if ( 'jitsi_random_public' === $domain_type ) {
					$jitsi_domain = mhub_random_domain();
				} elseif ( 'jitsi_jass_premium' === $domain_type ) {
					$jitsi_domain = '8x8.vc';
				} elseif ( 'jitsi_self_hosted' === $domain_type ) {
					$jitsi_domain = $mhub_jitsi_settings['custom_domain'];
				}
			}

			$this->start_controls_section(
				'configuration_section',
				array(
					'label' => esc_html__( 'Configuration', 'meetinghub' ),
				)
			);

			$this->add_control(
				'name',
				array(
					'label'       => esc_html__( 'Room Name', 'meetinghub' ),
					'type'        => Controls_Manager::TEXT,
					'default'     => mhub_generate_random_room( 8 ),
					'placeholder' => esc_html__( 'Meeting name here', 'meetinghub' ),
				)
			);

			$this->add_control(
				'domain',
				array(
					'label'       => esc_html__( 'Domain', 'meetinghub' ),
					'type'        => Controls_Manager::TEXT,
					'default'     => $jitsi_domain,
					'placeholder' => esc_html__( 'Domain name here', 'meetinghub' ),
				)
			);

			$this->add_control(
				'width',
				array(
					'label'   => esc_html__( 'Width', 'meetinghub' ),
					'type'    => Controls_Manager::NUMBER,
					'min'     => 100,
					'max'     => 2000,
					'step'    => 5,
					'default' => 1080,
				)
			);

			$this->add_control(
				'height',
				array(
					'label'   => esc_html__( 'Height', 'meetinghub' ),
					'type'    => Controls_Manager::NUMBER,
					'min'     => 100,
					'max'     => 2000,
					'step'    => 5,
					'default' => 720,
				)
			);

			$this->add_control(
				'start_with_audio_muted',
				array(
					'label'        => esc_html__( 'Start with audio muted', 'meetinghub' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'ON', 'meetinghub' ),
					'label_off'    => esc_html__( 'OFF', 'meetinghub' ),
					'return_value' => 'yes',
					'default'      => 'no',
				)
			);

			$this->add_control(
				'start_with_video_muted',
				array(
					'label'        => esc_html__( 'Start with video muted', 'meetinghub' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'ON', 'meetinghub' ),
					'label_off'    => esc_html__( 'OFF', 'meetinghub' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'enable_screen_sharing',
				array(
					'label'        => esc_html__( 'Enable screen sharing', 'meetinghub' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'ON', 'meetinghub' ),
					'label_off'    => esc_html__( 'OFF', 'meetinghub' ),
					'return_value' => 'yes',
					'default'      => 'no',
				)
			);

			$this->add_control(
				'enable_inviting',
				array(
					'label'        => esc_html__( 'Enable Inviting', 'meetinghub' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'ON', 'meetinghub' ),
					'label_off'    => esc_html__( 'OFF', 'meetinghub' ),
					'return_value' => 'yes',
					'default'      => 'yes',
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
			$settings = $this->get_settings();
			?>
		
		<div
			id="meetinghub_meeting"
			data-random-room-name="<?php echo esc_attr( $settings['name'] ); ?>"
			data-random-domain="<?php echo esc_attr( $settings['domain'] ); ?>"
			data-width="<?php echo esc_attr( $settings['width'] ); ?>"
			data-height="<?php echo esc_attr( $settings['height'] ); ?>"
			data-audio-muted="<?php echo esc_attr( $settings['start_with_audio_muted'] ); ?>"
			data-video-muted="<?php echo esc_attr( $settings['start_with_video_muted'] ); ?>"
			data-screen-sharing="<?php echo esc_attr( $settings['enable_screen_sharing'] ); ?>"
			data-enable-inviting="<?php echo esc_attr( $settings['enable_inviting'] ); ?>"
			data-elementor="mhub_jitsi_elementor"
		></div>
			<?php
		}
	}
}
