<?php
namespace VideoWhisper\LiveWebcams;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait Requirements {
	// define and check requirements

	static function requirementsDefinitions() {
		 $adminSettings = 'admin.php?page=live-webcams&tab=';

		$options = self::getOptions();

		$broadcastPageID = $options['p_videowhisper_webcams_performer'] ?? false;
		if ( $broadcastPageID ) {
			$broadcastPage = get_permalink( $broadcastPageID );
		} else {
			$broadcastPage = $adminSettings . 'pages';
		}

		// ordered
		return array(
			'setup'                 => array(
				'title'   => 'Start Setup',
				'warning' => 'Plugin requires setup to configure and activate features.',
				'info'    => 'Setting up features. Setup involves multiple steps for configuring and activating videochat features.',
				'fix'     => 'Start from Setup Overview page: see backend documentation, setup tutorial',
				'url'     => $adminSettings . 'setup',
			),

			'setup_pages'           => array(
				'title'   => 'Setup Pages',
				'warning' => 'Pages to access functionality are not setup, yet.',
				'info'    => 'Accessing main features: broadcast live channels, list channels.',
				'fix'     => 'Setup feature pages and menu from Pages tab in settings.',
				'url'     => $adminSettings . 'pages',
				'type'    => 'option_configured',
				'option'  => 'p_videowhisper_webcams_performer',
			),

			/*
			'vwsSocket' => array(
				'type'    => 'option_configured',
				'option'  => 'vwsSocket',
				'title'   => 'Configure P2P WebRTC using VideoWhisper WebRTC',
				'warning' => 'P2P was not configured, yet. ',
				'info'    => 'P2P in HTML5 Videochat. When using Wowza SE, P2P is not required but can provide improved latency on private calls with Auto setting.',
				'fix'     => 'Get a FREE or paid account from <a href="https://webrtchost.com/hosting-plans/#WebRTC-Only" target="_blank">WebRTC Host: P2P</a> and configure VideoWhisper WebRTC Adress & Token in settings, or install your own <a href="https://github.com/videowhisper/videowhisper-webrtc">VideoWhisper WebRTC</a> and own STUN/TURN servers. Or skip if you use Wowza SE and do not want to enable Auto with P2P for private calls, yet.',
				'url'     => $adminSettings . 'webrtc',

			),

			'wsURLWebRTC_configure' => array(
				'type'    => 'option_configured',
				'option'  => 'wsURLWebRTC',
				'title'   => 'Configure WebRTC relay for HTML5 WebRTC',
				'warning' => 'A WebRTC relay address was not configured, yet.',
				'info'    => 'HTML5 Videochat',
				'fix'     => 'Deploy solution on <a href="https://webrtchost.com/hosting-plans/#Complete-Hosting" target="_blank">Complete Turnkey Streaming Hosting from WebRTChost.com</A> (recommended) for full capabilities or add only WebRTC relay streaming service using <A href="https://webrtchost.com/hosting-plans/#Streaming-Only" target="_blank">remote streaming service with RTMP/WebRTC/HLS/DASH</A> (FFmpeg required on web host). Skip if you just want to test HTML5 Videochat using P2P with VideoWhisper WebRTC.',
				'url'     => $adminSettings . 'webrtc',

			),
			*/

			'rtmp_status'       => array(
				'title'   => 'Setup Stream Notifications / Session Control',
				'warning' => 'Stream Notifications/ Session Control was not detected, yet.',
				'info'    => 'Advanced support with external encoders like OBS, generates snapshots,recording, push streams), protection of streaming address from unauthorized usage (broadcast and playback require the secret pins or keys associated with active site channels). This checkpoint is triggered when there are active RTMP broadcasting sessions.',
				'fix'     => 'Get a <a href="https://site2stream.com/html5/">Turnkey Streaming Site</a> to a get turnkey site setup with full mode support and all hosting requirements, or <a href="https://webrtchost.com/hosting-plans/#Complete-Hosting" target="_blank">Complete Hosting w. Streaming</A> (recommended) for full capabilities or just Streaming Only services using <A href="https://webrtchost.com/hosting-plans/#Streaming-Only" target="_blank">remote streaming service for WebRTC + RTMP/HLS</A> (FFmpeg required on external web host). <a href="https://consult.videowhisper.com">Ask for installation assistance</a>, Proceed to see configuration instructions or Skip if you use only web based (WebRTC) broadcasting or plan to configure later.',
				'url'     => $adminSettings . 'server',

			),
/*
			'webrtc_test'           => array(
				'title'   => 'Test WebRTC Broadcast',
				'warning' => 'No WebRTC broadcast was detected, yet.',
				'info'    => 'Making sure WebRTC broadcasting works.',
				'fix'     => 'Go to Performer Dashboard and Go Live using HTML5 or HTML5 app interface. Requires session control to detect the HTML5 stream. Skip if you don not plan to use WebRTC with Wowza SE.',
				'url'     => $broadcastPage,
			),
	
			
			'rtmp_server_configure' => array(
				'type'    => 'option_configured',
				'option'  => 'rtmp_server',
				'title'   => 'Configure a functional RTMP address',
				'warning' => 'A valid RTMP address was not configured, yet.',
				'info'    => 'Broadcasting over RTMP using OBS, Larix Broadcaster mobile or other encoders.',
				'fix'     => 'Deploy solution on <a href="https://webrtchost.com/hosting-plans/#Complete-Hosting" target="_blank">Complete Turnkey Streaming Host</A> (recommended) for full capabilities including HTML5 or add <A href="https://webrtchost.com/hosting-plans/#Streaming-Only" target="_blank">remote streaming services with RTMP/WebRTC/HLS/DASH</A> to existing setup. For more details see <a href="https://videowhisper.com/?p=Requirements" target="_vwrequirements">requirements</a>. You can skip if you just want to test HTML5 Videochat using P2P with VideoWhisper WebRTC.',
				'url'     => $adminSettings . 'server',
			),
			
				'rtmp_test' => array
				(
					'title' => 'Test RTMP Broadcast',
					'warning' => 'No RTMP broadcast was detected, yet.',
					'info' => 'Making sure RTMP broadcasting and streaming server works.',
					'fix' => 'Use OBS or mobile Larix Broacaster app after setting up Stream Session Control, to test RTMP streaming. You can skip if you do not plan to use RTMP broadcasting.',
					'url' => $broadcastPage,
				),
*/
			'ffmpeg'                => array(
				'title'   => 'FFMPEG on Web Host',
				'warning' => 'FFMPEG was not detected, yet.',
				'info'    => 'Stream snapshots, stream analysis, on demand dynamic transcoding between different encodings specific to WebRTC/RTMP/RTSP/HLS/MPEG, needed for HTML5 playback.',
				'fix'     => 'For full capabilities deploy solution on <a href="https://webrtchost.com/hosting-plans/#Complete-Hosting" target="_blank">Complete Turnkey Streaming Hosting</A> (recommended).</A>.',
				'url'     => $adminSettings . 'hls',
			),

			'apf_optimizer' => array
			(
				'title' => 'APF Optimizer',
				'type' => 'file_exists',
				'file' => WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php',
				'warning' => 'APF Optimizer was not detected, yet.',
				'info' => 'APF - Allowed Plugins Filter is a Must Use Plugin that allows website admin to control what plugins are active for specific requests, to reduce server load, improve security, increase setup scalability. It is great for AJAX requests that usually do not required other plugins. Optional: If you do not want to use it, you can skip this requirement.',
				'fix' => 'Install APF Optimizer.',
				'url' => 'admin.php?page=live-webcams-apf',
			),

			'resources'             => array(
				'title'   => 'Review Suggested Plugins',
				'warning' => 'You did not check suggested plugins and support resources, yet.',
				'info'    => 'Extend solution functionality and optimize security, reliability.',
				'fix'     => 'Review suggested plugins and support options on Support Resources section.',
				'url'     => $adminSettings . 'support#plugins',
			),

			'appearance'            => array(
				'title'   => 'Review Appearance',
				'warning' => 'You did not review appearance settings, yet.',
				'info'    => 'Customizing logos, interface dark mode, styles.',
				'fix'     => 'Review appearance settings.',
				'url'     => $adminSettings . 'appearance',
			),

			'review'                => array(
				'title'   => 'Support Developers with a Review',
				'warning' => 'You did not review plugin, yet to claim a gift.',
				'info'    => 'If you have nice ideas, suggestions for further development or just want to share your experience or tips for other website owners, leave a review on WP repository. After review, submit a ticket with review link to VideoWhisper support, to claim a gift: 15% discount coupon for new orders or 2 months hosting upgrade. Skip this if you do not want to support the developers or already did.',
				'fix'     => 'Leave a good review on WP repository to support plugin developers and claim a gift.',
				'url'     => 'https://wordpress.org/support/plugin/ppv-live-webcams/reviews/#new-post',
				'manual'  => 1,
			),

		);
	}

	static function requirements_plugins_loaded() {
		 $remind = get_option( __CLASS__ . '_requirementsRemind' );

		if ( $remind < time() ) {
			if ( ! array_key_exists( 'tab', $_GET ) || sanitize_text_field( $_GET['tab'] ) != 'setup' ) {
				add_action( 'admin_notices', array( __CLASS__, 'requirements_admin_notices' ) );
				add_action( 'wp_ajax_vws_notice', array( __CLASS__, 'vws_notice' ) );
			}
		}
	}

	static function requirementsStatus() {
		return get_option( __CLASS__ . '_requirements' );
	}

	static function requirementsGet() {
		 $defs  = self::requirementsDefinitions();
		$status = self::requirementsStatus();

		if ( ! $status ) {
			return $defs;
		}
		if ( ! is_array( $status ) ) {
			return $defs;
		}

		$merged = array();
		foreach ( $defs as $key => $value ) {
			if ( array_key_exists( $key, $status ) ) {
				$r_merged       = array_merge( (array) $value, (array) $status[ $key ] );
				$merged[ $key ] = $r_merged;
			} else {
				$merged[ $key ] = $value;
			}

			$merged[ $key ]['label'] = $key;

		}

		return $merged;
	}

	static function requirements_admin_notices() {
		$adminPage = 'admin.php?page=live-webcams&tab=';

				$requirement = self::nextRequirement();

		if ( ! $requirement ) {
			return; // nothing to show
		}

		$htmlCode = self::requirementRender( $requirement['label'], 'overview', $requirement );

		$ajaxurl = get_admin_url() . 'admin-ajax.php';
		// onclick="noticeAction('skip',
		?>
	<div id="vwNotice" class="notice notice-success is-dismissible">
		<h4>Paid Videochat - PPV Live Webcams: What to do next?</h4>Turnkey Site Setup Wizard with Requirement Checkpoints and Suggestions

		<?php echo wp_kses_post( $htmlCode ); ?>
		<a href="<?php echo esc_url( $adminPage ); ?>setup" >Setup Overview</a>
		| <a href="<?php echo esc_url( $adminPage ); ?>setup&skip=<?php echo esc_attr( $requirement['label'] ); ?>">Skip "<?php echo esc_html( $requirement['title'] ); ?>"</a>

		| <a href="<?php echo esc_url( $adminPage ); ?>support" >Support Resources</a>
		| <a target="_videowhisper" href="https://videowhisper.com/tickets_submit.php" >Contact Developers</a>
		| <a  href="#" onclick="noticeAction('remind', '<?php echo esc_attr( $requirement['label'] ); ?>')" >Remind me Tomorrow</a>

	</div>

<style>
.vwInfo
{
background-color: #fffffa;
padding: 8px;
margin: 8px;
border-radius: 4px;
display:block;
border: #999 1px solid;
box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}
</style>

	<script>

		function noticeAction(task, label)
		{
					var data = {
					'action': 'vws_notice',
					'task': task,
					'label': label,
					};

		  jQuery.post('<?php echo esc_url( $ajaxurl ); ?>', data, function() {});

		  vwNotice = document.getElementById("vwNotice");
		  if (vwNotice) vwNotice.style.display = "none";
		}
	</script>
		<?php
	}

	function vws_notice() {
		// update_option( 'my_dismiss_notice', true );

		$task = sanitize_text_field( $_POST['task'] ?? '' );

		switch ( $task ) {
			case 'remind':
				update_option( __CLASS__ . '_requirementsRemind', time() + 86400 );
				break;

			case 'skip':
				$label = sanitize_file_name( $_POST['label'] ?? '');
				self::requirementUpdate( $label, 1, 'skip' );
				break;
		}

		ob_clean();

		exit;
	}


	// item handling

	static function requirementStatus( $requirement, $meta = 'status' ) {
		if ( ! $requirement ) {
			return 0;
		}
		if ( ! is_array( $requirement ) ) {
			return 0;
		}
		if ( ! array_key_exists( $meta, $requirement ) ) {
			return 0;
		}

		return $requirement[ $meta ];
	}


	static function requirementUpdate( $label, $value, $meta = 'status' ) {
		$status = self::requirementsStatus();
		if ( ! is_array( $status ) ) {
			$status = array();
		}

		if ( array_key_exists( $label, $status ) ) {
			$metas = $status[ $label ];
		} else {
			$metas = array();
		}

		if ( $meta == 'status' && isset($metas['status']) &&  $metas['status'] != $value ) {
			$metas['updated'] = time(); // mark as update only if changed
		}
		$metas[ $meta ] = $value;

		$status[ $label ] = $metas;
		update_option( __CLASS__ . '_requirements', $status );
	}

	static function requirementMet( $label ) {
		if ( ! self::requirementStatus( $label ) ) {
			self::requirementUpdate( $label, 1 );
		}
	}


	static function nextRequirement() {
		$requirements = self::requirementsGet();

		foreach ( $requirements as $label => $requirement ) {
			if ( ! self::requirementStatus( $requirement ) && ! self::requirementStatus( $requirement, 'skip' ) ) {
				$requirement['label'] = $label;
				return $requirement;
			}
		}

	}


	static function requirementDisabled( $label ) {

		if ( self::requirementCheck( $label ) ) {
			return '';
		} else {
			return 'disabled';
		}
	}

	static function requirementCheck( $label, $force = false ) {
		$requirements = self::requirementsGet();

		if ( ! array_key_exists( $label, $requirements ) ) {
			return 0; // not defined
		}

		$requirement = $requirements[ $label ];

		// already checked and valid
		$canCheck = isset($requirement['type']) && in_array( $requirement['type'], array( 'option_configured' ) );
	 
		if ( ! $force || ! $canCheck ) { 
			// force only for possible checks
			if ( $requirement['updated'] ?? true ) {
				if ( isset($requirement['status']) && $requirement['status'] ) {
					return $requirement['status'];
				}
			}
		}

		// check now if possible
		switch ( $requirement['type'] ?? '' ) {
			case 'option_configured':
					// not configured
					$options        = self::getOptions();
					$optionsDefault = self::adminOptionsDefault();

					$requirementOption = $requirement['option'] ?? '';

					$status = ( ( $options[ $requirementOption ] ?? '' ) != ( $optionsDefault[ $requirementOption ] ?? '') );

					self::requirementUpdate( $label, $status );
				return $status;

			case 'option_defined':
				$option = get_option( $requirement['option'] );
				if ( $option ) {
					$status = 1;
				} else {
					$status = 0;
				}
				self::requirementUpdate( $label, $status );
				return $status;

					break;

			case 'file_exists':
				$file = $requirement['file'] ?? '';
				if ( file_exists( $file ) ) {
					$status = 1;
				} else {
					$status = 0;
				}
				self::requirementUpdate( $label, $status );
				return $status;

					break;		
		}

			// otherwise manual
			return 0;
	}

	static function requirementRender( $label, $view = 'check', $requirement = null ) {

		$isPresent = self::requirementCheck( $label, $view == 'check' ); // force when check

		$htmlCode = '';

		switch ( $view ) {
			case 'check':
				if ( ! $requirement ) {
					$requirements = self::requirementsDefinitions();
					$requirement  = $requirements[ $label ];
				}

				$htmlCode = 'Requirement check: ' . $requirement['title'];

				if ( $isPresent ) {
					$htmlCode .= ' = Checked.';
				} else {

					$htmlCode .=  '<div class="vwInfo"><b>' . $requirement['warning'] . '</b> Required for: ' . $requirement['info'] .
					'<br>Quick Fix: ' . $requirement['fix'] . '</div>';
				}
				break;

			case 'overview':
				$htmlButton = '<br><a class="button" href="' . $requirement['url'] . '">' . ( $isPresent ? 'Review' : 'Proceed' ) . '</a>';

				$adminPage = 'admin.php?page=live-webcams&tab=';

				if ( self::requirementStatus( $requirement, 'skip' ) ) {
					$htmlButton .= ' <a class="button" href="' . $adminPage . 'setup&unskip=' . $requirement['label'] . '">UnSkip</a>';
				}

				if ( ! $isPresent && ( $requirement['manual'] ?? false ) ) {
					$htmlButton .= ' <a class="button" href="' . $adminPage . 'setup&done=' . $requirement['label'] . '">Done</a>';
				}

				if ( $isPresent ) {
					$htmlButton .= ' <a class="button" href="' . $adminPage . 'setup&check=' . $requirement['label'] . '">Check Again</a>';
				}

				if ( $requirement['updated'] ?? false ) {
					$htmlButton .= ' <small style="float:right"> Status: ' . ( $requirement['status'] ? 'Done' : 'Required' ) . ' Updated: ' . date( 'F j, Y, g:i a', $requirement['updated'] ) . '</small>';
				}

				$htmlCode .= '<div class="vwInfo"><b>' . $requirement['title'] . '</b>: ' . ( $isPresent ? 'Checked. ' : '<b>' . $requirement['warning'] . '</b> ' ) . 'Required for: ' . $requirement['info'] .
				'<br>Quick Fix: ' . ( $requirement['fix'] ?? '-'  ) . $htmlButton . '</div>';

				break;
		}
		return $htmlCode;
	}

}
