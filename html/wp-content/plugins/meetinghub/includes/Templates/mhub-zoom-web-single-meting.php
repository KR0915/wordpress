<?php
/**
 * MeetingHub Zoom WebSDK Template
 *
 * Description: This template is used to embed Zoom WebSDK for displaying Zoom meetings or webinars on a specific post or page.
 * It handles user registration and meeting configuration based on the Zoom settings.
 *
 * @package SOVLIX\MHUB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mhub_post_id   = get_the_ID();
$mhub_post_type = get_post_type( $mhub_post_id );
$zoom_settings  = get_option( 'mhub_zoom_settings', true );
$api_key        = $zoom_settings['sdk_client_id'];
$api_secret     = $zoom_settings['sdk_client_secret'];
$meeting_id     = get_post_meta( get_the_ID(), 'meetinghub_zoom_meeting_id', true );
$webinar_id     = get_post_meta( get_the_ID(), 'meetinghub_zoom_webinar_id', true );
$meeting_type   = intval( $options['meeting_type'] );

$meeting_password = get_post_meta( get_the_ID(), 'meetinghub_zoom_meeting_password', true );

$username = '';
$email    = '';

if ( is_user_logged_in() ) {
	$registration_form = false;
	$user              = wp_get_current_user();
	$username          = $user->user_login;
	$email             = $user->user_email;
}

?>

<!DOCTYPE html>
<head>
	<title><?php echo esc_html( $meeting_title ); ?></title>
	<meta charset="utf-8" />
	<meta name="format-detection" content="telephone=no">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<style>
		.mhub-zoom-web-meeting {
			margin: 0;
			padding: 0;
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			background-color: #f2f2f2;
		}

		.meeting-form-container {
			width: 500px;
			padding: 40px;
			box-shadow: 0 0 10px rgba(0,0,0,0.1);
			border-radius: 8px;
			background: white;
			margin: 20px;
		}

		.from-wrapper{
			margin-top: 30px;
		}

		.form-group {
			margin-bottom: 15px;
		}

		.form-group label {
			display: block;
			color: #333;
			font-weight: bold;
			margin-bottom: 10px;
			color: #4a4a4a;
		}

		.form-group input, 
		.form-group select {
			width: 100%;
			padding: 8px;
			border: 1px solid #ddd;
			border-radius: 4px;
			box-sizing: border-box;
		}

		.join-button {
			width: 100%;
			padding: 10px;
			background: #7856fb;
			color: white;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 15px;
		}

		.join-button:hover {
			background: #4f21ff;
		}

		.notice {
			color: #ff0000;
			margin-bottom: 15px;
		}

		#meeting-container {
			display: none;
		}

		.mhub-web-title {
			text-align: center;
			color: #4a4a4a;
			line-height: 1.5;
			font-size: 24px;
			font-weight: 600;
		}

		.mhub-web-des {
			color: #838383;
			font-weight: 300;
			text-align: center;
			font-size: 16px;
			margin-bottom: 10px;
		}

		header{
			display: none;
		}

		#boxed-layout-pro {
			display: none;
		}

	</style>
</head>

<body>
	<div class="mhub-zoom-web-meeting">
		<div id="form-container" class="meeting-form-container">
			<h2 class="mhub-web-title"><?php echo esc_html( $meeting_title ); ?></h2>
			<p class="mhub-web-des"><?php esc_html_e( 'Enter below details to join this Zoom Event', 'meetinghub' ); ?></p>
			<div class="from-wrapper">
				<div class="form-group">
					<label for="display_name"><?php esc_html_e( 'Name', 'meetinghub' ); ?></label>
					<input type="text" id="display_name" name="display_name" value="<?php echo $username ? esc_attr( $username ) : ''; ?>" required>
				</div>
				<div class="form-group">
					<label for="display_email"><?php esc_html_e( 'Email', 'meetinghub' ); ?></label>
					<input type="text" id="display_email" name="display_email" value="<?php echo $email ? esc_attr( $email ) : ''; ?>">
				</div>
				<div class="form-group">
					<label for="meeting_lang"><?php esc_html_e( 'Language', 'meetinghub' ); ?></label>
					<select id="meeting_lang" name="meeting_lang">
						<option value="en-US"><?php esc_html_e( 'English', 'meetinghub' ); ?></option>
						<option value="de-DE"><?php esc_html_e( 'German - Deutsch', 'meetinghub' ); ?></option>
						<option value="es-ES"><?php esc_html_e( 'Spanish - Español', 'meetinghub' ); ?></option>
						<option value="fr-FR"><?php esc_html_e( 'French - Français', 'meetinghub' ); ?></option>
						<option value="id-ID"><?php esc_html_e( 'Indonesian - Bahasa Indonesia', 'meetinghub' ); ?></option>
						<option value="jp-JP"><?php esc_html_e( 'Japanese - 日本語', 'meetinghub' ); ?></option>
						<option value="pt-PT"><?php esc_html_e( 'Portuguese - Português', 'meetinghub' ); ?></option>
						<option value="ru-RU"><?php esc_html_e( 'Russian - Русский', 'meetinghub' ); ?></option>
						<option value="zh-CN"><?php esc_html_e( 'Simplified Chinese - 简体中文', 'meetinghub' ); ?></option>
						<option value="zh-TW"><?php esc_html_e( 'Traditional Chinese - 繁体中文', 'meetinghub' ); ?></option>
						<option value="ko-KO"><?php esc_html_e( 'Korean - 한국어', 'meetinghub' ); ?></option>
						<option value="vi-VN"><?php esc_html_e( 'Vietnamese - Tiếng Việt', 'meetinghub' ); ?></option>
						<option value="it-IT"><?php esc_html_e( 'Italian - Italiano', 'meetinghub' ); ?></option>
						<option value="nl-NL"><?php esc_html_e( 'Dutch - Nederlands', 'meetinghub' ); ?></option>
						<option value="pl-PL"><?php esc_html_e( 'Polish - Polska', 'meetinghub' ); ?></option>
						<option value="sv-SE"><?php esc_html_e( 'Swedish - Svenska', 'meetinghub' ); ?></option>
						<option value="tr-TR"><?php esc_html_e( 'Turkish - Türkçe', 'meetinghub' ); ?></option>
					</select>

				</div>
			</div>
			
			<button class="join-button" onclick="startMeeting()"> <?php esc_html_e( 'Join Event via Browser', 'meetinghub' ); ?></button>
		</div>
	</div>

	<div id="meeting-container"></div>

	<script>
		var API_KEY = '<?php echo esc_js( $api_key ); ?>';
		var SECRET_KEY = '<?php echo esc_js( $api_secret ); ?>';
		var leaveUrl = '<?php echo esc_url( get_permalink() ); ?>';
		var meeting_id = '<?php echo esc_attr( 1 === $meeting_type ? $webinar_id : $meeting_id ); ?>';
		var meeting_password = '<?php echo esc_attr( $meeting_password ); ?>';

		function startMeeting() {
			var displayName = document.getElementById('display_name').value;
			var meetingLang = document.getElementById('meeting_lang').value;
			var displayEmail = document.getElementById('display_email').value;
			
			if (!displayName) {
				alert('Please enter your name');
				return;
			}

			// Hide form and show meeting container
			document.getElementById('form-container').style.display = 'none';
			document.getElementById('meeting-container').style.display = 'block';

			// Set global variables for meeting.js
			window.username = displayName;
			window.lang = meetingLang;
			window.email = displayEmail;
			window.role = 0;

			// Initialize meeting
			websdkready();
		}
	</script>

	<?php // phpcs:ignore ?>
	<script src="https://source.zoom.us/3.1.6/lib/vendor/react.min.js"></script>
	<?php // phpcs:ignore ?>
	<script src="https://source.zoom.us/3.1.6/lib/vendor/react-dom.min.js"></script>
	<?php // phpcs:ignore ?>
	<script src="https://source.zoom.us/3.1.6/lib/vendor/redux.min.js"></script>
	<?php // phpcs:ignore ?>
	<script src="https://source.zoom.us/3.1.6/lib/vendor/redux-thunk.min.js"></script>
	<?php // phpcs:ignore ?>
	<script src="https://source.zoom.us/3.1.6/lib/vendor/lodash.min.js"></script>
	<?php // phpcs:ignore ?>
	<script src="https://source.zoom.us/zoom-meeting-3.1.6.min.js"></script>
	<?php // phpcs:ignore ?>
	<script src="<?php echo esc_url( MHUB_ASSETS . '/js/zoom/vconsole.min.js' ); ?>"></script>
	<?php // phpcs:ignore ?>
	<script src="<?php echo esc_url( MHUB_ASSETS . '/js/zoom/tool.js' ); ?>"></script>
	<?php // phpcs:ignore ?>
	<script src="<?php echo esc_url( MHUB_ASSETS . '/js/zoom/meeting.js' ); ?>"></script>
</body>
</html>