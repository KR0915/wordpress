<?php
/**
 * MeetingHub Elementor Integrator
 * Integrates MeetingHub widgets with Elementor.
 *
 * @package MeetingHub
 */

namespace SOVLIX\MHUB\Elementor;

use Elementor\Elements_Manager;

if ( ! class_exists( 'Mhub_Elementor_Integrator' ) ) {
	/**
	 * Class Mhub_Elementor_Integrator
	 * Integrates MeetingHub widgets with Elementor.
	 */
	class Mhub_Elementor_Integrator {
		/**
		 * Class constructor.
		 *
		 * Hooks into the 'plugins_loaded' action to initiate the plugin.
		 */
		public function __construct() {
			add_action( 'elementor/elements/categories_registered', array( $this, 'add_elementor_widget_categories' ), 10 );
			add_action( 'elementor/widgets/register', array( $this, 'register_oembed_widget' ) );
		}

		/**
		 * Add custom category for MeetingHub widgets.
		 *
		 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
		 * @return void
		 */
		public function add_elementor_widget_categories( $elements_manager ) {
			$elements_manager->add_category(
				'meetinghub-category',
				array(
					'title' => __( 'MeetingHub Widgets', 'meetinghub' ),
					'icon'  => 'fa fa-plug',
				)
			);
		}

		/**
		 * Register oEmbed Widget.
		 *
		 * Include widget file and register widget class.
		 *
		 * @since 1.0.0
		 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
		 * @return void
		 */
		public function register_oembed_widget( $widgets_manager ) {
			$widgets_manager->register( new Widgets\Mhub_Jitsi_Elementor() );
			$widgets_manager->register( new Widgets\Mhub_Webex_Elementor() );
			$widgets_manager->register( new Widgets\Mhub_Zoom_Direct_MW() );
			$widgets_manager->register( new Widgets\Mhub_Google_Meet_Elementor() );
		}
	}
}
