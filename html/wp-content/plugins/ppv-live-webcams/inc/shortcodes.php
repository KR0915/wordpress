<?php
namespace VideoWhisper\LiveWebcams;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait Shortcodes {

	// ! Shortcode Implementation


	static function enqueueUI() {
		wp_enqueue_script( 'jquery' );

		wp_enqueue_style( 'fomantic-ui', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/semantic/semantic.min.css' );
		wp_enqueue_script( 'fomantic-ui', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/semantic/semantic.min.js', array( 'jquery' ) );
	}


	static function videowhisper_match_form($atts)
	{

	$options = self::getOptions();

	// shortocode attributes
	$atts = shortcode_atts(
		array(
			'postid' => 0,
			'registration' => 0,
		),
		$atts,
		'videowhisper_match_form'
	);
	$atts = array_map('esc_attr', $atts);


	if (!is_user_logged_in() && !$atts['registration']) return 'Login required to edit and save match criteria!';

	$current_user = wp_get_current_user();


	$userID =  $current_user->ID ;

	$postID = 0;
	if ($atts['postid']) $postID = intval($atts['postid']);

	self::enqueueUI();

	$contentCode = '';

	if ( !$atts['registration'] ) $contentCode .= '<form class="ui ' . esc_attr($options['interfaceClass']) . ' form inline segment" method="post" enctype="multipart/form-data" action="' . esc_url(self::getCurrentURL()) . '" id="addForm" name="addForm">' . wp_nonce_field('videowhisper_match_form', 'match_form_nonce', true, false);

	$sideCode = [];

			foreach ( ['me', 'you'] as $side)
			{
			$sideCode[$side] = '';

			$fieldex = '';
			if ($side == 'you') $fieldex = 'm_'; //match

			if ($options['profileFields'] ?? false) if ( is_array($options['profileFields']) || $options['profileFields'] instanceof Countable ) if ( count($options['profileFields']) ) foreach ( $options['profileFields'] as $field => $parameters ) if ( isset( $parameters['match'] ) ) if ( $parameters['match'] != 'mirror' || $side == 'me' )
			{

					$fieldName = $fieldex . sanitize_title( trim( $field ) );

					if ( isset($parameters['instructions']) ) {
						$htmlInstructions = ' data-tooltip="' . htmlspecialchars( stripslashes( $parameters['instructions'] ) ) . '"';
					} else {
						$htmlInstructions = '';
					}

					$sideCode[$side] .= '<div class="field"' . $htmlInstructions . '>';

					$iconCode = '';
					$icon = isset($parameters['icon']) ? stripslashes( $parameters['icon'] ) : ( $options['profileFieldIcon'] ?? false );
					if ($icon) $iconCode .= '<i class="' . $icon . ' icon"></i> ';

					if (!isset($parameters['hideLabel'])) $sideCode[$side] .= '<label for="' . $fieldName . '">' . $iconCode . $field . '</label>';

					//default
					$fieldValue = '';
					if ( isset($parameters['default']) ) $fieldValue = sanitize_text_field( $parameters['default']);

					// save data
					if ( !$atts['registration'] )
					{
						if ( isset( $_POST[ $fieldName ] ) ) {
							if ( $_POST['matchFor'] ?? false ) {
								if ( in_array( $parameters['type'], array('checkboxes', 'multiselect', 'language') ) ) 
								{
									$tags = (array) $_POST[ $fieldName ];
									if ( is_array( $tags ) ) {
										foreach ( $tags as &$tag ) {
											$tag = sanitize_text_field( $tag );
										}
										unset( $tag );
									} else {
										$tags = sanitize_text_field( $tags );
									}
									// Verify nonce before updating data
									if (!isset($_POST['match_form_nonce']) || !wp_verify_nonce($_POST['match_form_nonce'], 'videowhisper_match_form')) {
										// Nonce verification failed
										continue;
									}
									
									if ($postID) update_post_meta( $postID, 'vwf_' . $fieldName, $tags );
									else update_user_meta( $userID, 'vwf_'  . $fieldName, $tags );

								} else {
									if ($postID)  update_post_meta( $postID, 'vwf_' . $fieldName, sanitize_text_field( $_POST[ $fieldName ] ) );
									else update_user_meta( $userID, 'vwf_' . $fieldName, sanitize_text_field( $_POST[ $fieldName ] )  );
								}
							}
						}

							// get data
						if ( in_array( $parameters['type'], array('checkboxes', 'multiselect', 'language') ) ) 
						{
							if ($postID) $fieldValue = get_post_meta( $postID, 'vwf_' . $fieldName, true );
							else $fieldValue = get_user_meta( $userID, 'vwf_' . $fieldName, true );

							if ( ! $fieldValue ) {
								$fieldValue = array();
							}
							if ( ! is_array( $fieldValue ) ) {
								$fieldValue = array( $fieldValue );
							}
						} else {
							if ($postID) $fieldValue = get_post_meta( $postID, 'vwf_' . $fieldName, true );
							else $fieldValue = get_user_meta( $userID, 'vwf_' . $fieldName, true );
						}

					}

					$fieldOptions = [];
					if ( isset($parameters['options']) )
					{
					$parameters['options'] = stripslashes( $parameters['options'] ?? '' );
					//if $parameters['options'] contains | get 	$fieldOptions by split by | otherwise by /
					$fieldOptions = strpos($parameters['options'], '|') ? explode('|', $parameters['options']) : explode('/', $parameters['options']);
					}


					// form
					switch ( $parameters['type'] ) {

						case 'select';
							$sideCode[$side] .= '<SELECT class="ui dropdown search clearable v-select" name="' . $fieldName . '" id="' . $fieldName . '">';
							$sideCode[$side] .= '<OPTION value="" ' . ( ! $fieldValue ? 'selected' : '' ) . '> ' . ($side == 'me' ? '-' : 'Any') . ' </OPTION>';

							foreach ( $fieldOptions as $fieldOption ) {
								$sideCode[$side] .= '<OPTION value="' . htmlspecialchars( stripslashes( $fieldOption ) ) . '" ' . ( $fieldOption == $fieldValue ? 'selected' : '' ) . '>' . htmlspecialchars( $fieldOption ) . '</OPTION>';
							}

							$sideCode[$side] .= '</SELECT>';
						break;

						case 'multiselect':
							$sideCode[$side] .= '<SELECT MULTI class="ui dropdown search clearable v-select" name="' . $fieldName . '[]" id="' . $fieldName . '" multiple>';

							if (is_array($fieldOptions))
							foreach ( $fieldOptions as $fieldOption ) {
								$sideCode[$side] .= '<OPTION value="' . htmlspecialchars( stripslashes( $fieldOption ) ) . '" ' . ( is_array($fieldValue) && in_array( $fieldOption, $fieldValue ) ? 'selected' : '' ) . '>' . htmlspecialchars( $fieldOption ) . '</OPTION>';
							};
							$sideCode[$side] .= '</SELECT>';

						case 'checkboxes';
							if (is_array($fieldOptions)) foreach ( $fieldOptions as $fieldOption ) {
								$sideCode[$side] .= '<div class="field"><div class="ui toggle checkbox">
  <input type="checkbox" name="' . $fieldName . '[]" value="' . htmlspecialchars( stripslashes( $fieldOption ) ) . '" ' . ( ( is_array($fieldValue) && in_array( $fieldOption, $fieldValue ) ) ? 'checked' : '' ) . '>
  <label>' . htmlspecialchars( $fieldOption ) . '</label></div></div>';
							} else $sideCode[$side] .= '<div>Empty or incorrect field options: ' . html_esc($parameters['options']). '</div>';

						break;

					}
					$sideCode[$side]  .= '</div>'; //field


					// if ($parameters['instructions']) $contentCode .= '<BR>' . htmlspecialchars($parameters['instructions']);
				}
			}

				$contentCode .= '<h4 class="ui dividing header">I Am</h4>' . $sideCode['me'] ;

				if ($sideCode['you']) $contentCode .= '<h4 class="ui dividing header">Looking For</h4>' . $sideCode['you'] ;

				$contentCode .='<input type="hidden" name="matchFor" id="matchFor" value="' . ( $postID ? $postID : ( $userID ? $userID : -1 ) ) . '">';

				if ( !$atts['registration'] ) $contentCode .= '<br><input class="ui button primary" type="submit" name="save" id="save" value="' . __( 'Save', 'ppv-live-webcams' ) . '" />
	</form>';

	$contentCode .='<script>jQuery(document).ready(function(){  jQuery(".ui.dropdown.clearable:not(.multi,.fpsDropdown)").dropdown({"clearable": true}); });</script>';
		return $contentCode;
	}

	static function videowhisper_sms_number($atts)
	{
	//fill number for SMS notifications

	$options = self::getOptions();

	if (!is_user_logged_in()) return 'Login required to fill SMS number!';

	$current_user = wp_get_current_user();

	// shortocode attributes
	$atts = shortcode_atts(
		array(
		),
		$atts,
		'videowhisper_sms_number'
	);
	$atts = array_map('esc_attr', $atts);

	$htmlCode = '';

	if ($_POST['sms_update'] ?? false)
	{
		$nonce = $_POST['_wpnonce'] ?? '';
		if ( ! wp_verify_nonce( $nonce, 'sms_number_update' ) )
		{
			return '<div class="ui error message">Invalid security token!</div>';
		}

		if ( isset($_POST['sms_number']) ) update_user_meta( $current_user->ID, 'sms_number', sanitize_text_field( $_POST['sms_number'] ) );
	}

		$current_url = self::getCurrentURL();
		
		$htmlCode .= '<form class="ui ' . esc_attr($options['interfaceClass']) . ' form inline segment" method="post" enctype="multipart/form-data" action="" id="addForm" name="addForm">' . wp_nonce_field('sms_number_update', '_wpnonce', true, false) . '<div class="two fields">';

		$htmlCode .= '<div class="field">
		  <label>' . esc_html__('SMS Mobile Number', 'ppv-live-webcams') . '</label>
		  <input type="text" name="sms_number" id="sms_number" value="' . esc_attr( get_user_meta( $current_user->ID, 'sms_number', true ) ) . '" />
		  <input type="hidden" name="sms_update" id="sms_update" value="1" />
		  </div>';
		$htmlCode .= '<div class="field"><label>' . esc_html__('Update', 'ppv-live-webcams') . '</label><input type="submit" id="submitButton" name="submitButton" class="ui submit button" value="' . esc_attr__('Save', 'ppv-live-webcams') . '" ></div>';
		$htmlCode .=  '</div><div class="field">' . esc_html( $options['sms_instructions'] ) . '</div>';
		$htmlCode .= '</form>';

		return $htmlCode;
	}

	static function videowhisper_room_rate($atts)
	{
		//display room ratio & bonus status if enabled

		// shortocode attributes
		$atts = shortcode_atts(
			array(
				'roomid' => '',
			),
			$atts,
			'videowhisper_room_rate'
		);
		$atts = array_map('esc_attr', $atts);

		$postID = intval( $atts['roomid'] );
		if (!$postID) return 'videowhisper_room_ratio: missing roomid';


		$options = self::getOptions();

		self::roomStatsUpdate($postID);
		$ratio = self::performerRatio( '', $options, $postID );

		self::enqueueUI();

		$htmlCode = '<div class="ui ' . esc_attr($options['interfaceClass']) . ' segment">';

		$htmlCode .= __('Current Earning Rate', 'ppv-live-webcams') . ': ' . esc_html($ratio);

		if ($options['statsBonusRate'] && $options['statsBonusOnline'])
		{
			$timeOnline = intval(get_post_meta( $postID, 'statsTimeOnline', true ));

			$htmlCode .= '<div class="ui ' . esc_attr($options['interfaceClass']) . ' indicating progress" data-value="' . esc_attr($timeOnline) . '" data-total="' . intval($options['statsBonusOnline']) . '" id="bonusRatio">
  <div class="bar">
    <div class="progress"></div>
  </div>
  <div class="label">' . esc_html__('Bonus Earning Rate', 'ppv-live-webcams') . ': <i class="gift icon"></i>' . esc_html($options['statsBonusRate']) . ' <i class="user clock icon"></i>' . esc_html(self::humanDuration(intval($options['statsBonusOnline']))) . '</div>
</div>';

		$htmlCode .='<pre style="display:none">
<script>
jQuery(document).ready(function(){
jQuery(".ui.progress").progress();
})
</script>
</pre>
		';

		}

		$htmlCode .= '</div>';

		return $htmlCode;
	}


	static function roomStatsUpdate($postID)
	{

	if (!$postID) return;

	$options = self::getOptions();
	if ( ! self::timeTo( $postID . '-roomStats', 60, $options ) ) return;

	$duration = 86400 * $options['statsDuration'];
	if (!$duration) $duration = 2592000; //30 days default
	$lastTime = time() - $duration;

	global $wpdb;
	$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';
	$table_calls = $wpdb->prefix . 'vw_vmls_private';

	$cnd = '';
	if ($postID) $cnd = "rid='$postID' AND";


		$timeOnline = 0;

		//group chats
		$sql      = $wpdb->prepare("SELECT * FROM $table_sessions WHERE $cnd broadcaster=%d AND sdate > %d", 1, $lastTime); //assign by session start time
		$sessions = $wpdb->get_results( $sql );

		if ( $wpdb->num_rows > 0 ) foreach ( $sessions as $session )
		{

				$timeOnline += $session->edate > 0 ? intval( $session->edate - $session->sdate ) : 0 ;

		}

		//calls
		$sql      = $wpdb->prepare("SELECT * FROM $table_calls WHERE $cnd csdate > %d", $lastTime);
		$sessionsC = $wpdb->get_results( $sql );

		//sessions
		if ( $wpdb->num_rows > 0 ) foreach ( $sessionsC as $session )
		{
			$timeOnline +=  $session->psdate > 0 ? intval( $session->pedate - $session->psdate ) : 0 ;
		}

		update_post_meta( $postID, 'statsTimeOnline', $timeOnline );

		if ($options['statsBonusOnline'] && $options['statsBonusRate'])
		if ( $timeOnline > $options['statsBonusOnline'] )
			update_post_meta( $postID, 'vw_bonusRatio', $options['statsBonusRate'] );
		else delete_post_meta( $postID, 'vw_bonusRatio' ); //remove bonus ratio

	}


	static function videowhisper_reports($atts)
	{
		// Check user permissions first
		if (!is_user_logged_in()) {
			return '<div class="ui error message">You must be logged in to view reports.</div>';
		}
		
		$current_user = wp_get_current_user();
		$options = self::getOptions();
		
		// shortocode attributes
		$atts = shortcode_atts(
			array(
				'roomid' => '',
			),
			$atts,
			'videowhisper_reports'
		);
		$atts = array_map('esc_attr', $atts);

		$postID = intval( $atts['roomid'] );
		
		// Security check: verify user has access to this room's reports
		if ($postID) {
			// Only allow room owner, moderators or administrators to view reports
			if (!self::isPerformer($current_user->ID, $postID) && 
				!self::isAuthor($postID) && 
				!self::isModerator($current_user->ID, $options) && 
				!current_user_can('manage_options')) {
				return '<div class="ui error message">You do not have permission to view these reports.</div>';
			}
		}

		self::enqueueUI();
		$htmlCode = '';

		wp_enqueue_script( 'chart-js', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/chartjs/chart.min.js', ['jquery'] );

		// Sanitize and validate date inputs
		$startDate = isset($_POST['startDate']) ? sanitize_text_field($_POST['startDate']) : '';
		$endDate = isset($_POST['endDate']) ? sanitize_text_field($_POST['endDate']) : '';

		// Convert to timestamps and validate
		$tS = strtotime($startDate);
		$tE = strtotime($endDate);
		
		// Validate timestamps
		if (!$tS || $tS === false) {
			$startDate = date("M d, Y", strtotime("-12 month"));
			$tS = strtotime($startDate);
		}
		
		if (!$tE || $tE === false) {
			$endDate = date("M d, Y", time());
			$tE = strtotime($endDate);
		}

		// Validate deltaDate input (group by parameter)
		$validDeltas = array('month', 'week', 'day');
		$deltaDate = isset($_POST['deltaDate']) ? sanitize_text_field($_POST['deltaDate']) : 'month';
		if (!in_array($deltaDate, $validDeltas)) {
			$deltaDate = 'month';
		}

		// Validate report type
		$validTypes = array('time', 'value');
		$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'time';
		if (!in_array($type, $validTypes)) {
			$type = 'time';
		}

		$htmlCode .= '
		<form class="ui ' . esc_attr($options['interfaceClass']) . ' form inline" method="post" enctype="multipart/form-data" action="' . esc_url(add_query_arg( array(), $wp->request ?? '' )) . '" id="addForm" name="addForm">
  <div class="five fields">

    <div class="field">
      <label>' . __('Start Date', 'ppv-live-webcams') . '</label>
      <div class="ui calendar" id="rangestart">
        <div class="ui input left icon">
          <i class="calendar icon"></i>
          <input type="text" name="startDate" placeholder="Start" value="'. $startDate .'">
        </div>
      </div>
    </div>

	<div class="field">
	  <label>' . __('End Date', 'ppv-live-webcams') . '</label>
	  <div class="ui calendar" id="rangeend">
	    <div class="ui input left icon">
	      <i class="calendar icon"></i>
	      <input type="text" name="endDate" placeholder="End" value="'. $endDate .'">
	    </div>
	  </div>
	</div>

 <div class="field">
  <label>' . __('Group', 'ppv-live-webcams') . '</label>
  <select name="deltaDate" id="deltaDate" class="ui fluid dropdown v-select">
  <OPTION value="month" ' . ( $deltaDate == 'month' ? 'selected' : '' ) . '>' . __('Monthly', 'ppv-live-webcams') . '</OPTION>
  <OPTION value="week" ' . ( $deltaDate == 'week' ? 'selected' : '' ) . '>' . __('Weekly', 'ppv-live-webcams') . '</OPTION>
  <OPTION value="day" ' . ( $deltaDate == 'day' ? 'selected' : '' ) . '>' . __('Daily', 'ppv-live-webcams') . '</OPTION>
  </select>
 </div>

  <div class="field">
  <label>' . __('Type', 'ppv-live-webcams') . '</label>
  <select name="type" id="type" class="ui fluid dropdown v-select">
  <OPTION value="time" ' . ( $type == 'time' ? 'selected' : '' ) . '>' . __('Time', 'ppv-live-webcams') . '</OPTION>
  <OPTION value="value" ' . ( $type == 'value' ? 'selected' : '' ) . '>' . __('Value', 'ppv-live-webcams') . '</OPTION>
  </select>
 </div>

 <div class="field">
  <label>' . __('Update', 'ppv-live-webcams') . '</label>
  <input type="submit" id="submitButton" name="submitButton" class="ui submit button" value="' . __('Update', 'ppv-live-webcams') . '" >
 </div>

</div>
</form>';


		$htmlCode .= '<div>
  <canvas id="videowhisperChart"></canvas>
  </div>';

	global $wpdb;
	$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';
	$table_calls = $wpdb->prefix . 'vw_vmls_private';


// Set up time range for reports with secure validation
$t1 = $tS; // Already validated
$t2 = strtotime($startDate . ' +1 ' . $deltaDate);
$endTime = $tE; // Already validated

// Validate t2 is sane value 
if (!$t2 || $t2 < $t1 || $t2 > time() + 86400) {
    $t2 = strtotime($startDate . ' +1 month');
}

// Maximum time range - for security and performance
$maxTimeRange = 86400 * 365 * 2; // 2 years
if ($endTime - $t1 > $maxTimeRange) {
    $endTime = $t1 + $maxTimeRange;
    $endDate = date("M d, Y", $endTime);
}

$tableCode = '<TABLE class="ui table small celled striped">';

$data = [];

if ($type == 'value')
{
	$dataSets = [
	'clientCost' => [ 'label' => 'Client Cost', 'color' => 'rgba(250, 100, 150, 0.8)', 'data' => '' ],
	'performerShare' => [ 'label' => 'Performer Earnings', 'color' => 'rgba(120, 150, 120, 0.8)', 'data' => '' ],
	'siteShare' => [ 'label' => 'Site Earnings', 'color' => 'rgba(160, 255, 120, 0.8)', 'data' => '' ],

	'ClientCallCost' => [ 'label' => 'Client Call Costs', 'color' => 'rgba(250, 150, 150, 0.8)', 'data' => '' ],
	'PerformerCallEarning' => [ 'label' => 'Performer Call Earnings', 'color' => 'rgba(120, 200, 120, 0.8)', 'data' => '' ],
	'SiteCallShare' => [ 'label' => 'Site Call Earnings', 'color' => 'rgba(160, 255, 170, 0.8)', 'data' => '' ],

	];

}
else
{
	$dataSets = [
	'PerformerTime' => [ 'label' => 'Performer Time', 'color' => 'rgba(100, 99, 132, 1)', 'data' => '' ],
	'ClientTimeFree' => [ 'label' => 'Client Time Free', 'color' => 'rgba(54, 100, 235, 1)', 'data' => '' ],
	'ClientTimePaid' => [ 'label' => 'Client Time Paid', 'color' => 'rgba(54, 200, 235, 1)', 'data' => '' ],
	'PerformerCall' => [ 'label' => 'Performer Call Time', 'color' => 'rgba(200, 100, 150, 1)', 'data' => '' ],
	'ClientCall' => [ 'label' => 'Client Call Time', 'color' => 'rgba(150, 100, 150, 1)', 'data' => '' ],

	'PerformerAll' => [ 'label' => 'Performer All Time', 'color' => 'rgba(150, 100, 100, 1)', 'data' => '' ],
	];
}

$labels = '';
$cnd = '';
$title = '';

// Securely build SQL condition for room ID
if ($postID) {
    $cnd = $wpdb->prepare("rid=%d AND", $postID);
    $post = get_post($postID);

    if ($post) $title = esc_html($post->post_title) . ' : ';
}

while ($t1 < $endTime && $t2 <= $endTime)
{
		//data segment
		$dS = [];
		foreach ($dataSets as $key => $dataSet) $dS[$key] = 0;

		// Use wpdb->prepare to safely construct and execute SQL queries
		$sql = $wpdb->prepare(
			"SELECT * FROM $table_sessions WHERE $cnd sdate > %d AND sdate < %d", 
			$t1, 
			$t2
		);
		
		$sessions = $wpdb->get_results($sql);

		//sessions
		if ($wpdb->num_rows > 0) foreach ($sessions as $session)
		{
			if ($type == 'value')
			{
				if (!$session->broadcaster)
				{
					$meta = maybe_unserialize($session->meta);
					if (!is_array($meta)) $meta = [];

					if (array_key_exists('cost', $meta)) $dS['clientCost'] += floatval($meta['cost']);

					if (array_key_exists('b_performerEarn', $meta)) $dS['performerShare'] += floatval($meta['b_performerEarn']);
					else if (array_key_exists('b_performerShare', $meta) && array_key_exists('b_performerRatio', $meta)) 
						$dS['performerShare'] += floatval($meta['b_performerShare']) * floatval($meta['b_performerRatio']);

					$dS['siteShare'] = $dS['clientCost'] - $dS['performerShare'];
				}
			}
			else //time
			{
				if ($session->broadcaster)
				{
					$dS['PerformerTime'] += ($session->edate > 0 ? intval($session->edate - $session->sdate) : 0);
					$dS['PerformerAll'] += ($session->edate > 0 ? intval($session->edate - $session->sdate) : 0);
				}
				elseif ($session->rmode == 0) $dS['ClientTimeFree'] += ($session->rsdate ? intval($session->redate - $session->rsdate) : 0);
				else $dS['ClientTimePaid'] += intval($session->redate - $session->rsdate);
			}
		}

		//calls - use prepared statement
		$sql = $wpdb->prepare(
			"SELECT * FROM $table_calls WHERE $cnd csdate > %d AND csdate < %d",
			$t1,
			$t2
		);
		
		$sessionsC = $wpdb->get_results($sql);

		//sessions
		if ( $wpdb->num_rows > 0 ) foreach ( $sessionsC as $session )
		{
			if ($type == 'time')
			{
				$dS['PerformerCall'] += ( $session->psdate > 0 ? intval( $session->pedate - $session->psdate ) : 0 );
				$dS['PerformerAll'] += ( $session->psdate > 0 ? intval( $session->pedate - $session->psdate ) : 0 );

				$dS['ClientCall'] += ( $session->csdate > 0 ? intval( $session->cedate - $session->csdate ) : 0 );
			}

			if ($type == 'value')
			{
				$meta = unserialize( $session->meta );
				if (!is_array($meta)) $meta = [];

				if (array_key_exists('b_clientCost', $meta)) $dS['ClientCallCost'] += $meta['b_clientCost'];
				if (array_key_exists('b_performerEarning', $meta)) $dS['PerformerCallEarning'] += $meta['b_performerEarning'];

				$dS['SiteCallShare'] = $dS['ClientCallCost'] - $dS['PerformerCallEarning'];
			}
		}


	//data y
	foreach ($dS as $key => $value)
	{
		$dataSets[$key]['data'] .= ( $dataSets[$key]['data'] !='' ? ', ' : '' ) . $value;
	}

	//label x
	$labels .= ($labels ? ', ' : '') .  '\'' . date( "M d, Y", $t1) .  '\'';

	//list
	$tableCode .= '<tr><td>' . date("M d, Y", $t1) . ' - ' . date("M d, Y", $t2) . '</td><td>';

	foreach ($dataSets as $key => $dataSet) $tableCode .= $dataSet['label'] . ': ' .  ( $type == 'time' ? self::humanDuration($dS[$key]) : $dS[$key] . ' ' . $options['currency'])  . '<br>';

	$tableCode .= '</td></tr>';

	//next
	$t1 = strtotime( date( "Y-m-d", $t1) . ' +1 ' . $deltaDate);
	$t2 = min( strtotime( date( "Y-m-d", $t2) . ' +1 ' . $deltaDate), $endTime);
}

$tableCode .= '</TABLE>';

//data

$dataCode = 'const data = {
  labels: [' . $labels . '],
  datasets: [
';
	foreach ($dataSets as $key => $dataSet)
	$dataCode .= '{
      label: \'' . $dataSet['label'] .  '\',
      data: [' . $dataSet['data'] .  '],
      backgroundColor: \'' . $dataSet['color'] . '\',
    },';

$dataCode .= ']
};';

  	$htmlCode .= '<pre style="display:none">
  	<script>
jQuery(document).ready(function(){

jQuery(\'#rangestart\').calendar({
  type: \'date\',
  endCalendar: jQuery(\'#rangeend\')
});

jQuery(\'#rangeend\').calendar({
  type: \'date\',
  startCalendar: jQuery(\'#rangestart\')
});

' . $dataCode . '

const config = {
  type: \'bar\',
  data: data,
  options: {
    plugins: {
      title: {
        display: true,
        text: \'' . $title . ($type == 'time' ?  __('Time', 'ppv-live-webcams')  :  __('Value', 'ppv-live-webcams') ) . '\'
      },
    },
    responsive: true,
    scales: {
      x: {
        stacked: true,
      },
      y: {
	    beginAtZero: true,
        stacked: true
      }
    }
  }
};

 const vwChart = new Chart( document.getElementById(\'videowhisperChart\').getContext(\'2d\'), config );


 //

})



</script>
</pre>
  	';

  		$htmlCode .= $tableCode;

		return $htmlCode;

	}



// tools

	static function to62( $num, $b = 62 ) {
		$base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$r    = $num % $b;
		$res  = $base[ $r ];
		$q    = floor( $num / $b );
		while ( $q ) {
			$r   = $q % $b;
			$q   = floor( $q / $b );
			$res = $base[ $r ] . $res;
		}
		return $res;
	}


	static function to10( $num, $b = 62 ) {

		$base  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		$limit = strlen( $num );
		if (!$limit) return '';

		$res   = strpos( $base, $num[0] );
		for ( $i = 1;$i < $limit;$i++ ) {
			$res = $b * $res + strpos( $base, $num[ $i ] );
		}

		return $res;
	}


	static function sectionsLayout( $header, $content, $active = '', $layout = 'tabs', $intro = '' ) {
		// renders section based on arrays of $header (titles) and $content with matching keys

		if ( ! $layout ) {
			$layout = 'auto';
		}
		$headerCode  = '';
		$contentCode = '';

		$isMobile = (bool) preg_match( '#\b(ip(hone|od|ad)|android|opera m(ob|in)i|windows (phone|ce)|blackberry|tablet|s(ymbian|eries60|amsung)|p(laybook|alm|rofile/midp|laystation portable)|nokia|fennec|htc[\-_]|mobile|up\.browser|[1-4][0-9]{2}x[1-4][0-9]{2})\b#i', $_SERVER['HTTP_USER_AGENT'] );

		$options = get_option( 'VWliveWebcamsOptions' );

		if ( $layout == 'auto' ) {
			if ( $isMobile ) {
				$layout = 'accordion';
			} else {
				$layout = 'tabs';
			}
		}

			// start
		switch ( $layout ) {
			case 'tabs':
				if ( $intro ) {
					$headerCode .= '<div class="ui ' . $options['interfaceClass'] . ' horizontal fluid label">' . $intro . '</div>';
				}
				$headerCode .= '<div class="ui ' . $options['interfaceClass'] . ' pointing menu wrappedtabs">';

				$contentCode = '';
				break;

			case 'accordion':
				$headerCode  = '<div class="ui ' . esc_attr($options['interfaceClass']) . ' segment">';
				$contentCode = '<div class="ui ' . esc_attr($options['interfaceClass']) . ' fluid accordion vwSections">';
				if ( $intro ) {
					$contentCode .= '<div class="ui ' . esc_attr($options['interfaceClass']) . ' fluid label">' . esc_html($intro) . '</div>';
				}

				break;

			case 'chapters':
				$headerCode = '<div class="ui ' . esc_attr($options['interfaceClass']) . ' menu vertical fluid">';
				if ( $intro ) {
					$headerCode .= '<div class="ui ' . esc_attr($options['interfaceClass']) . ' fluid label">' . esc_html($intro) . '</div>';
				}

				$contentCode = '';
				break;
		}

		// items
		foreach ( $header as $section => $title ) {
			switch ( $layout ) {
				case 'tabs':
					$headerCode .= '<a class="item ' . ( $active == $section ? 'active' : '' ) . '" data-tab="' . $section . '">' . $title . '</a>';

					$contentCode .= '<div class="ui ' . esc_attr($options['interfaceClass']) . ' tab segment ' . ( $active == $section ? 'active' : '' ) . '" data-tab="' . esc_attr($section) . '">';
					$contentCode .= $content[ $section ];
					$contentCode .= '<br style="clear:both"></div>';

					break;

				case 'accordion':
					$contentCode .= '<div class="ui title ' . ( $active == $section ? 'active' : '' ) . '">
      <i class="dropdown icon"></i>
      ' . $title . '
    </div>
    <div class="content ' . ( $active == $section ? 'active' : '' ) . '">
      ' . $content[ $section ] . '
    </div>';

					break;

				case 'chapters':
					$headerCode .= '<a class="ui item ' . ( $active == $section ? 'active' : '' ) . '" href="#' . $section . '">' . $title . '</a>';

					$contentCode .= '<a name="' . $section . '"></a>';
					$contentCode .= '<div class="ui header">' . $title . '</a></div>';
					$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment ' . ( $active == $section ? 'active' : '' ) . '">';
					$contentCode .= $content[ $section ];
					$contentCode .= '<br style="clear:both">';
					$contentCode .= '</div>';

					break;
			}
		}

		// end
		switch ( $layout ) {
			case 'tabs':
				$headerCode .= '</div>';

				$contentCode .= '
			<style>
.wrappedtabs{
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
}
			</style>
	<script>
jQuery(document).ready(function(){

	var vwTabs = jQuery(".wrappedtabs.menu .item");
	try{ vwTabs.tab()} catch(error)
	{
	console.log("Interface error Tabs", error, vwTabs);
	}
});
</script>';

				break;

			case 'accordion':
				$contentCode .= '</div></div>
			<script>
jQuery(document).ready(function(){

	jQuery(".ui.accordion.vwSections").accordion({animateChildren: false});

});
</script>
';
				break;

			case 'chapters':
				$headerCode .= '</div>';
				break;
		}

		$contentCode .= '
<style>
.ui.title, .ui.label, .ui.compact.button{
  height: auto !important;
}
</style>';

		return $headerCode . $contentCode;
	}


	static function videowhisper_callnow( $atts ) {
		$options = get_option( 'VWliveWebcamsOptions' );

		// shortocode attributes
		$atts = shortcode_atts(
			array(
				'roomid' => '',
			),
			$atts,
			'videowhisper_callnow'
		);
		$atts = array_map('esc_attr', $atts);

		$postID = intval( $atts['roomid'] );

		if ( ! $postID ) {
			return 'videowhisper_callnow: missing roomid';
		}

		$post = get_post( $postID );
		if ( ! $post ) {
			return 'videowhisper_callnow: invalid roomid';
		}

		if ( ! is_user_logged_in() ) {
			return '<a class="ui ' . esc_attr($options['interfaceClass']) . ' button" href="' . esc_url(get_permalink( $options['p_videowhisper_login'] )) . '"> <i class="sign in icon"></i> ' . esc_html__( 'Login to Call', 'ppv-live-webcams' ) . '</a>';
		}

		$name = sanitize_file_name( $post->post_title );
		$url  = self::roomURL( $name );

		self::enqueueUI();
		$enterCode = "<!--VideoWhisper.com/PaidVideochat.com/videowhisper_callnow/#$postID-->";

		$urlCall = add_query_arg( 'vwsm', 'private', $url );

		$requests_disable = self::is_true( get_post_meta( $post->ID, 'requests_disable', true ) );
		$clientCPM        = self::clientCPM( $name, $options, $postID );

		if ( ! $requests_disable ) {
			$enterCode .= '<a class="ui ' . esc_attr($options['interfaceClass']) . ' button" href="' . esc_url($urlCall) . '">' . esc_html__( 'Call Now', 'ppv-live-webcams' ) . ' ' . ( $clientCPM ? esc_html($clientCPM . $options['currencypm']) : '' ) . '</a>';
		} else {
			$enterCode .= '<a class="ui ' . $options['interfaceClass'] . ' button" href="' . $url . '">' . __( 'Join', 'ppv-live-webcams' ) . '</a>';
		}

		return $enterCode;
	}


	static function videowhisper_streams( $atts ) {

		$options = self::getOptions();

		// shortocode attributes
		$atts = shortcode_atts(
			array(
				'roomid' => '',
			),
			$atts,
			'videowhisper_streams'
		);
		$atts = array_map('esc_attr', $atts);

		$postID = intval( $atts['roomid'] );

		if ( ! $postID ) {
			return 'videowhisper_streams: missing roomid';
		}

		$post = get_post( $postID );
		if ( ! $post ) {
			return 'videowhisper_streams: invalid roomid';
		}

		$current_user = wp_get_current_user();
		$username     = self::performerName( $current_user, $options );

		self::enqueueUI();
		$this_page = self::getCurrentURL();
		$htmlCode  = '';
		$status = '';


		//if current user is not room performer or moderator or admin return error
		if ( ! self::isPerformer( $current_user->ID, $postID ) && !self::isAuthor( $postID ) && !self::isModerator( $current_user->ID, $options ) && ! current_user_can( 'manage_options' ) ) {
			return 'videowhisper_streams: not allowed';
		}



		$htmlCode .= '<div class="ui accordion fluid vwStreams ' . $options['interfaceClass'] . '">';


			 if ( $options['reStreams'] )
			 {


				 $active = '';
				 if ( isset( $_POST['reStream'] ) || isset( $_GET['delete_stream'] ) || isset( $_GET['set_performer'] ) || isset( $_GET['update_streams'] ) || isset( $_GET['streams'] ) ) $active = 'active';

 				$htmlCode .= '<div class="title ' . $active . '"> <i class="camera video icon"></i> ' . __( 'ReStream RTSP/RTMP', 'ppv-live-webcams' ) . '</div>';

				$htmlCode .= '<div class="content ' . $active . '">';
				$htmlCode .= __( 'Add external streams to room (re-stream), from RTSP IP cameras or similar sources.', 'ppv-live-webcams' );

				//retrieve
				$reStreams = get_post_meta( $postID, 'reStreams', true );
				$currentStream = get_post_meta( $postID, 'stream-name', true );

				if ( ! $reStreams || ! is_array( $reStreams ) ) {
					$reStreams = array();
				}

				//add
				if ( isset( $_GET['streams'] ) && isset( $_POST['reStream'] ) ) {
					$reStream = sanitize_text_field( $_POST['reStream'] );
					$reStreamName = sanitize_file_name( sanitize_text_field( $_POST['reStreamName'] ) );


					if ( !strstr( $reStreamName, '.stream' ) ) $reStreamName .= '.stream';

					if (! in_array( $reStream, $reStreams ) )
					{
						$reStreams[ $reStreamName ] = $reStream;
						update_post_meta( $postID, 'reStreams', $reStreams );
						$reStreams = get_post_meta( $postID, 'reStreams', true );

					}
					else $htmlCode .= '<br>Source already added: ' . esc_html( $reStream ) ;

					$htmlCode .= self::restreamUpdate($postID, $options, $post);
				}

				//performer
				if ( isset( $_GET['set_performer'] ) ) {
					$set_performer = sanitize_text_field( $_GET['set_performer'] );

					$streamHLS =($options['httprestreamer'] ? $options['httprestreamer']  : $options['httpstreamer']) . $set_performer . '/playlist.m3u8';

					//playback as HLS with Wowza SE
					update_post_meta( $postID, 'stream-name', $set_performer );
					update_post_meta( $postID, 'stream-hls', $streamHLS );
					update_post_meta( $postID, 'stream-type', 'restream' );
					update_post_meta( $postID, 'stream-mode', 'stream' );
					update_post_meta( $postID, 'stream-updated', time() );

					$htmlCode .= '<br>Set Performing Stream: ' . esc_html( $set_performer ) ;
				}

				$roomStream   = get_post_meta( $postID, 'stream-name', true );

				//remove
				if ( isset( $_GET['delete_stream'] ) ) {

					$reStreamName = sanitize_text_field( $_GET['delete_stream'] );

					if ( isset( $reStreams[ $reStreamName ] ) ) unset( $reStreams[ $reStreamName ] );
					update_post_meta( $postID, 'reStreams', $reStreams );

					//delete file
					$streamPath = $options['streamsPath'] . '/' . $reStreamName;
					if ( file_exists( $streamPath ) ) {
						unlink( $streamPath );
					}

					if ( $roomStream == $reStreamName )
					{
						//remove
						update_post_meta( $postID, 'stream-name', '' ); //remove if set as performer
						update_post_meta( $postID, 'stream-address', '' );
						update_post_meta( $postID, 'stream-protocol', 'rtsp' );
						update_post_meta( $postID, 'stream-type', 'webrtc' );
						update_post_meta( $postID, 'stream-mode', 'direct' );
						update_post_meta( $postID, 'stream-updated', time() );
					}
					$htmlCode .= '<br>Removed stream: ' . esc_html( $reStreamName ) ;

					$reStreams = get_post_meta( $postID, 'reStreams', true );
					$htmlCode .= self::restreamUpdate($postID, $options, $post);
				}


				//update
				if ( isset( $_GET['update_streams'] ) )
				{
					$htmlCode .= '<br>ReStreams update.';
					$htmlCode .= self::restreamUpdate($postID, $options, $post);
				}

				if ( ! $reStreams ) {
				$reStreams = array();
				}

				if ( ! is_array( $reStreams ) ) {
					$reStreams = array( $reStreams );
				}


				foreach ( $reStreams as $streamName => $source ) {

				$streamPath = $options['streamsPath'] . '/' . $streamName;
				$streamLabel = str_replace('.stream', '', $streamName);


				$htmlCode .= '<br><div class="ui label ' . ( $streamName != $currentStream ? 'basic' : '' ) . '"> <i class="' . ( file_exists($streamPath) ? 'play' : 'pause' ) . ' icon"></i> ' . $streamLabel . '</div> <div class="ui label">' . esc_html( $source ) . '</div>';

				if ( $streamName != $performer ) $htmlCode .= '<a class="ui tiny icon button basic" data-content="Set as Performing Stream" href="' . add_query_arg(
					array(
						'streams'            => 1,
						'set_performer' => $streamName,
					),
					$this_page
				) . '"><i class="play circle icon"></i></a>';

				$htmlCode .= '<a class="ui tiny icon button basic" data-content="Remove" href="' . add_query_arg(
					array(
						'streams'            => 1,
						'delete_stream' => $streamName,
					),
					$this_page
				) . '"><i class="x icon"></i></a>';

			} //foreach

			$htmlCode .= '<br><a class="ui small icon button" href="' . add_query_arg(
					array(
						'streams'            => 1,
						'update_streams' => 1,
					),
					$this_page
				) . '"><i class="save icon"></i> Force Update</a>';


			$htmlCode .= '<form class="ui form ' . $status . '" method="post" enctype="multipart/form-data" action="' . add_query_arg( 'streams', 1, $this_page ) . '" id="addForm" name="addForm">';
			$htmlCode .= '<br/><h5 class="ui dividing header"> <i class="plus icon"></i> ' . __( 'Add Source', 'ppv-live-webcams' ) . '</h5>';

			$htmlCode .= '<div class="field">
    <label>' . __( 'Stream Name', 'ppv-live-webcams' ) . '</label>
    <input type="text" id="reStreamName" name="reStreamName" value="' .  $post->post_title . '_' . ( count($reStreams) + 1 )  . '"/>
  </div>';

			$htmlCode .= '<div class="field">
    <label>' . __( 'Source Address', 'ppv-live-webcams' ) . '</label>
    <input type="text" id="reStream" name="reStream" value="" placeholder="' . __( 'rtsp://{server}[:port]/{path}', 'ppv-live-webcams' ) . '"/>
  </div>
 ';

			$htmlCode .= '
  <div class="field">

  <button type="submit" id="submitButton" name="submitButton" class="ui submit button">
  <i class="plus icon"></i>
  ' . __( 'Add', 'ppv-live-webcams' ) . '
  </button>

   </div>
  ';
			$htmlCode .= '</form></div>';

			}

		if ( $options['pushStreams'] ) {

			$active = '';
			if ( isset($_POST['address']) || isset($_GET['delete_destination']) ) $active = 'active';


			$htmlCode .= '<div class="title ' . $active . '"> <i class="rss icon"></i> ' . __( 'Push RTMP Stream', 'ppv-live-webcams' ) . ': ' . $post->post_title . '</div>';


			$htmlCode .= '<div class="content ' . $active . '">';
			$htmlCode .= __( 'Push performer stream to external RTMP server or platform.', 'ppv-live-webcams' );

			$pushDestinations = get_post_meta( $postID, 'pushStreams', true );
			if ( ! $pushDestinations ) {
				$pushDestinations = array();
			}
			if ( ! is_array( $pushDestinations ) ) {
				$pushDestinations = array();
			}

			if ( isset($_GET['streams']) && isset($_POST['address']) ) {
				$pushDestinations[] = array( 'address' => sanitize_text_field( $_POST['address'] ) );
				update_post_meta( $postID, 'pushStreams', $pushDestinations );
			}

			foreach ( $pushDestinations as $key => $destination ) {
				$address   = $destination['address'];
				$htmlCode .= '<br> #' . $key . ' <div class="ui label">' . $address . '</div> <a class="ui tiny icon button" href="' . add_query_arg(
					array(
						'streams'            => 1,
						'delete_destination' => $key,
					),
					$this_page
				) . '"><i class="x icon"></i></a>';

				if ( $_GET['delete_destination'] != '' ) {
					if ( intval( $_GET['delete_destination'] ) == $key ) {
						unset( $pushDestinations[ $key ] );
						update_post_meta( $postID, 'pushStreams', $pushDestinations );
						$htmlCode .= ' - removed';
					}
				}
			}

			$htmlCode .= '<form class="ui form ' . $status . '" method="post" enctype="multipart/form-data" action="' . add_query_arg( 'streams', 1, $this_page ) . '" id="addForm" name="addForm">';
			$htmlCode .= '<br/><h5 class="ui dividing header"> <i class="plus icon"></i> ' . __( 'Add Destination', 'ppv-live-webcams' ) . '</h5>';
			$htmlCode .= '<div class="field">
    <label>' . __( 'Destination Address', 'ppv-live-webcams' ) . '</label>
    <input type="text" id="address" name="address" value="" placeholder="' . __( 'rtmp[s]://{server}[:port]/{app}/{stream_key}', 'ppv-live-webcams' ) . '">
  </div>
 ';

			$htmlCode .= '
  <div class="field">

  <button type="submit" id="submitButton" name="submitButton" class="ui submit button">
  <i class="plus icon"></i>
  ' . __( 'Add', 'ppv-live-webcams' ) . '
  </button>

   </div>
  ';
			$htmlCode .= '</form></div>';
		}

//external encolder

	$external_rtmp = self::is_true( get_post_meta( $post->ID, 'external_rtmp', true ) );

		if ( $external_rtmp ) {
			$htmlCode .= '<div class="title"> <i class="window maximize icon"></i> ' . __( 'External Broadcast', 'ppv-live-webcams' ) . ': ' . esc_html($post->post_title) . ' (OBS, Larix)</div>';

			$htmlCode .= '<div class="content"><form class="ui form">';

			$htmlCode .= '<div class="ui field">' . '<a href="https://obsproject.com/download" class="ui button"> <i class="ui cloud upload icon"></I>' . __( 'Download OBS', 'ppv-live-webcams' ) . '</a>' . __( 'OBS Studio is free desktop application for live streaming from Linux, Mac and Windows. Includes advanced composition features, screen sharing, scenes, transitions, filters, media input options.', 'ppv-live-webcams' ) . '</div>';


			if ($options['rtmpServer'] == 'videowhisper') {
				$rtmpAddress =  trim( $options['videowhisperRTMP'] );
				$stream = trim($options['vwsAccount']) . '/' . trim($username) . '?pin=' . self::getPin($current_user->ID, 'broadcast', $options); 
				$rtmpURL = $rtmpAddress . '//' . $stream;
			}
			else {
				$rtmpAddress = self::rtmp_address( $current_user->ID, $post->ID, true, $username, $post->post_title, $options );
			//wowza
			
			$application = substr( strrchr( $rtmpAddress, '/' ), 1 );
			$stream      = $username;

			$adrp1 = explode( '://', $rtmpAddress );
			$adrp2 = explode( '/', $adrp1[1] );
			$adrp3 = explode( ':', $adrp2[0] );

			$application = $adrp2[1];

			$server = $adrp3[0];
			$port   = $adrp3[1] ?? '';
			if ( ! $port ) {
				$port = 1935;
			}

			$rtmpURL = $rtmpAddress . '/' . $stream;
			}

			$htmlCode .= '<div class="ui field"><label>' . __( 'Server', 'ppv-live-webcams' ) . '</label> <INPUT class="ui fluid input" readonly value="' . esc_attr($rtmpAddress) . '" /><br>' . __( 'RTMP Address / OBS Stream URL: full streaming address. Contains: server, port if different than default 1935, application and control parameters, key. For OBS Settings: Stream > Server.', 'ppv-live-webcams' ) . '</div>';

			$htmlCode .= '<div class="ui field"><label>' . __( 'Stream Key', 'ppv-live-webcams' ) . '</label> <INPUT class="ui fluid input" readonly value="' . esc_attr($stream) . '" /><br>' . __( 'Stream Name / OBS Stream Key: name of stream. For OBS Settings: Stream > Stream Key. For GoCoder top right W Settings: Wowza Streaming Engine > Application > Stream Name', 'ppv-live-webcams' ) . '</div>';

			// $videoBitrate
			$sessionsVars = self::varLoad( $options['uploadsPath'] . '/sessionsApp' );
			if ( is_array( $sessionsVars ) ) {
				if ( array_key_exists( 'limitClientRateIn', $sessionsVars ) ) {
					$limitClientRateIn = intval( $sessionsVars['limitClientRateIn'] ) * 8 / 1000;

					if ( $limitClientRateIn ) {
						$videoBitrate = $limitClientRateIn - 100;

						$htmlCode .= '<div class="ui field"><label>' . __( 'Maximum Video Bitrate', 'ppv-live-webcams' ) . '</label> <INPUT class="ui fluid input" readonly value="' . $videoBitrate . '" /><br>' . __( 'Use this value or lower for video bitrate, depending on resolution and audio bitrate. A static background and less motion requires less bitrate than movies, sports, games. For OBS Settings: Output > Streaming > Video Bitrate. For GoCoder bottom left bitrate. Warning: Trying to broadcast higher bitrate than allowed by streaming server will result in disconnects/failures.', 'ppv-live-webcams' ) . '</div>';

						$htmlCode .= '<div class="ui field"><label>' . __( 'Maximum Audio Bitrate', 'ppv-live-webcams' ) . '</label> <INPUT class="ui fluid input" readonly value="96" /><br>' . __( 'Use this value or lower for audio bitrate. If you want to use higher Audio Bitrate, lower Video Bitrate to compensate for higher audio. For OBS Settings: Output > Streaming > Audio Bitrate. Warning: Trying to broadcast higher bitrate than allowed by streaming server will result in disconnects/failures.', 'ppv-live-webcams' ) . '</div>';

					}
				}
			}

			$htmlCode .= '<div class="ui field">' . '<a href="https://apps.apple.com/app/larix-broadcaster/id1042474385" class="ui button"> <i class="ui cloud upload icon"></I>' . __( 'Larix Broadcaster for iOS', 'ppv-live-webcams' ) . '</a>' . __( 'Larix Broadcaster free iOS app uses full power of mobile devices cameras to stream live content.', 'ppv-live-webcams' ) . '</div>';

			$htmlCode .= '<div class="ui field">' . '<a href="https://play.google.com/store/apps/details?id=com.wmspanel.larix_broadcaster" class="ui button"> <i class="ui cloud upload icon"></I>' . __('Larix Broadcaster for Android', 'ppv-live-webcams' ) . '</a>' . __( 'Larix Broadcaster free Android app uses full power of mobile devices cameras to stream live content.', 'ppv-live-webcams' ) . '</div>';

			$htmlCode .= '<div class="ui field"><label>' . __( 'URL', 'ppv-live-webcams' ) . '</label> <INPUT class="ui fluid input" readonly value="' . $rtmpURL . '" /><br>' . __( 'RTMP URL / Larix URL: full streaming address with stream name. Contains: server, port if different than default 1935, application and control parameters, key, stream name. For Larix Connections > URL.', 'ppv-live-webcams' ) . '</div>';

			/*
			$htmlCode .= '<div class="ui field">' . '<a href="https://apps.apple.com/us/app/wowza-gocoder/id640338185" class="ui button"> <i class="ui cloud upload icon"></I>' . __( 'Download GoCoder for iOS', 'ppv-live-webcams' ) . '</a>' . __( 'The Wowza GoCoder™ app from Wowza Media Systems™ is a live audio and video capture and encoding application for iOS 8 and newer. Use the Wowza GoCoder app to broadcast HD-quality live events on the go from any location to any screen using H.264 adaptive bitrate streaming.', 'ppv-live-webcams' ) . '</div>';

			$htmlCode .= '<div class="ui field">' . '<a href="https://play.google.com/store/apps/details?id=com.wowza.gocoder&hl=en" class="ui button"> <i class="ui cloud upload icon"></I>' . __( 'Download GoCoder for Android', 'ppv-live-webcams' ) . '</a>' . __( 'The Wowza GoCoder™ app from Wowza Media Systems™ is a live audio and video capture and encoding application for Android 4.4 and later. Use the Wowza GoCoder app to broadcast HD-quality live events on the go from any location to any screen using H.264 adaptive bitrate streaming.', 'ppv-live-webcams' ) . '</div>';


			$htmlCode .= '<div class="ui field"><label>' . __( 'Server', 'ppv-live-webcams' ) . '</label> <INPUT class="ui fluid input" readonly value="' . $server . '" /><br>' . __( 'Server IP or domain. For GoCoder top right W Settings: Wowza Streaming Engine > Host > Server.', 'ppv-live-webcams' ) . '</div>';

			$htmlCode .= '<div class="ui field"><label>' . __( 'Port', 'ppv-live-webcams' ) . '</label> <INPUT class="ui fluid input" readonly value="' . $port . '" /><br>' . __( 'Server port. For GoCoder top right W Settings: Wowza Streaming Engine > Host > Port.', 'ppv-live-webcams' ) . '</div>';

			$htmlCode .= '<div class="ui field"><label>' . __( 'Application', 'ppv-live-webcams' ) . '</label> <INPUT class="ui fluid input" readonly value="' . $adrp2[1] . '" /><br>' . __( 'Application with parameters. For GoCoder top right W Settings: Wowza Streaming Engine > Host > Application.', 'ppv-live-webcams' ) . '</div>';
			*/

			$htmlCode .= '</form> </div>';
		}

	$htmlCode .= '</div>'; //accordion end

	$htmlCode .= '<script>
jQuery(document).ready(function(){

	jQuery(".ui.accordion.vwStreams").accordion({animateChildren: false, exclusive: false});

});
</script>
';


		return $htmlCode;
	}


	static function redirectURL( $user ) {

		$redirect_to = get_site_url();

		if ( isset( $user->roles ) && is_array( $user->roles ) ) {

			// check for admins
			if ( in_array( 'administrator', $user->roles ) ) {
				return get_admin_url();

			} else {

				$options = get_option( 'VWliveWebcamsOptions' );

				// performer to dashboard
				if ( in_array( $options['rolePerformer'], $user->roles ) ) {
					$pid = $options['p_videowhisper_webcams_performer'];
					if ( $pid ) {
						return get_permalink( $pid );
					} else {
						return $redirect_to;
					}
				}

				// studio to studio dashboard
				if ( in_array( $options['roleStudio'], $user->roles ) ) {
					$pid = $options['p_videowhisper_webcams_studio'];
					if ( $pid ) {
						return get_permalink( $pid );
					} else {
						return $redirect_to;
					}
				}

				// client to client dashboard
				if ( in_array( $options['roleClient'], $user->roles ) ) {
					$pid = $options['p_videowhisper_webcams_client'];
					if ( $pid ) {
						return get_permalink( $pid );
					} else {
						return $redirect_to;
					}
				}
			}
		}
		return $redirect_to;

	}


	// login/logout before output
	static function after_setup_theme() {

		if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' )
		if ( isset($_GET['logout']) && isset($_GET['verify']) && !isset($_GET['error']) )
		{
				$error  = '';
				$status = '';
				$url = '';

			$options = self::getOptions();
			if ($options['p_videowhisper_login']) $url = get_permalink( $options['p_videowhisper_login'] );

			if ( wp_verify_nonce( $_GET['verify'], 'logout' . $_GET['logout'] ) )
			{
			wp_logout();
			}
			else
			{
				$error .=  '<li>' . __( 'Nonce incorrect for logout. Could be cache related: Try again!', 'ppv-live-webcams' ) . '</li>';

			}

				if ( $error ) {
					$status = 'warning';

					if (!$url) $url = wp_login_url();
					wp_redirect( add_query_arg( 'login_error', urlencode( $error ),  $url ) );
				}
				else
				{
					if (!$url) $url = get_site_url();
					wp_redirect( $url );
				}

			exit();
		}

		if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if ( $_POST['videowhisper_login'] ?? false) {
				$error  = '';
				$status = '';

				$options = self::getOptions();
				
				// Check for login rate limiting
				$ip_address = self::get_ip_address();
				$ip_hash = md5($ip_address); // Hash the IP for security
				$transient_name = 'vw_failed_login_' . $ip_hash;
				$failed_attempts = get_transient($transient_name);
				
				// Set max attempts - default to 5 if not specified in options
				$max_attempts = isset($options['maxLoginAttempts']) ? intval($options['maxLoginAttempts']) : 5;
				$lockout_time = isset($options['loginLockoutTime']) ? intval($options['loginLockoutTime']) : 3600; // Default 1 hour
				
				// Check if user has too many failed attempts
				if ($failed_attempts && $failed_attempts >= $max_attempts) {
					$error .= '<li>' . sprintf(__('Too many failed login attempts. Please try again after %d minute(s).', 'ppv-live-webcams'), ceil($lockout_time/60)) . '</li>';
					wp_redirect(add_query_arg('login_error', urlencode($error), get_permalink($options['p_videowhisper_login'])));
					exit();
				}

				$login    = sanitize_text_field( $_POST['login'] );
				$password = sanitize_text_field( $_POST['password'] );

				if ( ! $password ) {
					$error .= '<li>' . __( 'A password is required.', 'ppv-live-webcams' ) . '</li>';
				}
				if ( ! $login ) {
					$error .= '<li>' . __( 'A login (username) is required.', 'ppv-live-webcams' ) . '</li>';
				}

				if ( ! wp_verify_nonce( $_GET['videowhisper'], 'vw_login' ) ) {
					$error .=  '<li>' . __( 'Nonce incorrect for login. Could be cache related: Try again!' , 'ppv-live-webcams' ) . '</li>';
				}

				if ( $options['recaptchaSite'] ) {

					if ( isset( $_POST['recaptcha_response'] ) ) {
						if ( $_POST['recaptcha_response'] ) {
							// Build POST request:
							$recaptcha_response = sanitize_text_field( $_POST['recaptcha_response'] );

							// Make and decode POST request:
							// $recaptcha = file_get_contents('https://www.google.com/recaptcha/api/siteverify' . '?secret=' . $options['recaptchaSecret'] . '&response=' . $recaptcha_response);
							// $recaptchaD = json_decode($recaptcha);
							$response   = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify' . '?secret=' . $options['recaptchaSecret'] . '&response=' . $recaptcha_response );
							$body       = wp_remote_retrieve_body( $response );
							$recaptchaD = json_decode( $body );

							// Take action based on the score returned:
							if ( $recaptchaD->score >= 0.3 ) {
								// Verified
									//	$htmlCode = '<!-- VideoWhisper reCAPTCHA v3 score: ' . $recaptchaD->score . '-->';

							} else {
								// Not verified - show form error
								$error .= '<li>' . 'Google reCAPTCHA v3 Failed. Score: ' . $recaptchaD->score . ' . Privacy features may prevent Google from detecting user activity. For Brave browser turn off Brave Shields. Try again with privacy options disabled for this site or use a different browser!' . '</li>';
							}
						} else {
							$error .= '<li>' . 'Google reCAPTCHA v3 empty. Make sure you have JavaScript enabled or try a different browser!' . '</li>';
						}
					} else {
						$error .= '<li>' . 'Google reCAPTCHA v3 missing. Make sure you have JavaScript enabled or try a different browser!' . '</li>';
					}
				}

				if ( $error ) {
					$status = 'warning';
					wp_redirect( add_query_arg( 'login_error', urlencode( $error ), get_permalink( $options['p_videowhisper_login'] ) ) );
				} else {

					$login_user = wp_signon(
						array(
							'user_login'    => $login,
							'user_password' => $password,
							'remember'      => true,
						),
						is_ssl()
					);

					if ( $login_user && ! is_wp_error( $login_user ) ) {

						update_user_meta( $login_user->ID, 'videowhisper_ip_login', self::get_ip_address() ); // save ip

						// redirect
						wp_redirect( self::redirectURL( $login_user ) );
						exit(); // you need to exit to get wp_redirect to work
					}

					if ( is_wp_error( $login_user ) ) {
						$status = 'warning';
						$error .= $login_user->get_error_message() . "\n";
						
						// Track failed login attempt
						$ip_address = self::get_ip_address();
						$ip_hash = md5($ip_address);
						$transient_name = 'vw_failed_login_' . $ip_hash;
						$failed_attempts = get_transient($transient_name);
						
						// Increment failed attempts counter
						if ($failed_attempts) {
							$failed_attempts++;
						} else {
							$failed_attempts = 1;
						}
						
						// Store in transient with expiration time (default 1 hour)
						$lockout_time = isset($options['loginLockoutTime']) ? intval($options['loginLockoutTime']) : 3600;
						set_transient($transient_name, $failed_attempts, $lockout_time);
						
						// Add information about remaining attempts
						$max_attempts = isset($options['maxLoginAttempts']) ? intval($options['maxLoginAttempts']) : 5;
						$remaining = $max_attempts - $failed_attempts;
						if ($remaining > 0) {
							$error .= '<li>' . sprintf(__('You have %d login attempts remaining before your IP is temporarily blocked.', 'ppv-live-webcams'), $remaining) . '</li>';
						}

						wp_redirect( add_query_arg( 'login_error', urlencode( $error ), get_permalink( $options['p_videowhisper_login'] ) ) );
						exit();
					}
				}

				// end process login
			}
		}
	}


	static function sendNotification($userID, $messageSubject, $messageText, $url, $options = null)
	{
		//notify user $userID by email & SMS
		if (!$options) $options = self::getOptions();

		$user = get_userdata($userID);
		$email = $user->user_email;

		//email notification
		$email_last =  get_user_meta( $userID, 'email_last', true );
		if (!$email_last || $email_last < ( time() - $options['email_cooldown']) )
		{
		wp_mail( $email, $messageSubject, $messageText . "\r\n" . $url );
		update_user_meta( $userID, 'email_last', time() );
		}

		//sms notification
		if (!$options['sms_number']) return;

		$sms_message = $messageSubject . ' ' . $url;

			$sms_number = get_user_meta( $userID, 'sms_number', true );
			if ($sms_number)
			{
				//cooldown
				$sms_last =  get_user_meta( $userID, 'sms_last', true );
				if (!$sms_last || $sms_last < ( time() - $options['sms_cooldown']) )
				{

				if ($options['wp_sms']) if ( function_exists('wp_sms_send') ) wp_sms_send($sms_number,  $sms_message);

				if ($options['wp_sms_twilio']) if ( function_exists('twl_send_sms') )
				{
					$response = twl_send_sms( array( 'number_to' => $sms_number, 'message' => $sms_message ) );
					if( is_wp_error( $response ) ) return '<div class="ui message">SMS Error: ' . esc_html( $response->get_error_message() ) . '</div>';
				}

				update_user_meta( $userID, 'sms_last', time() );
				}
			}

		return;
	}

	static function videowhisper_password_form( $atts ) {

		$options = get_option( 'VWliveWebcamsOptions' );

		$atts = shortcode_atts(
			array(
				'role'     => '',
				'register' => 1,
				'terms'    => get_permalink( $options['p_videowhisper_terms'] ),
				'privacy'  => get_permalink( $options['p_videowhisper_privacy'] ),
			),
			$atts,
			'videowhisper_password_form'
		);
		$atts = array_map('esc_attr', $atts);
		$atts['terms'] = esc_url($atts['terms']);
		$atts['privacy'] = esc_url($atts['privacy']);

		self::enqueueUI();
		$this_page = self::getCurrentURL();
		$htmlCode  = '';
		$error     = '';

		if ( ! $options['loginFrontend'] ) {
			$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">';
			$htmlCode .= __( 'Frontend login is disabled from settings.', 'ppv-live-webcams' );
			$htmlCode .= '<br /> <a class="ui button qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'ppv-live-webcams' ) . '</a>';
			$htmlCode .= '</div>';
			return $htmlCode;
		}

		// email link
		if ( isset($_GET['key']) && isset($_GET['user']) ) {

			$key = filter_input( INPUT_GET, 'key' );
			if ( ! $key ) {
				return '<div class="ui ' . $options['interfaceClass'] . ' segment">The key parameter is invalid.</div>';
			}
			$user_id = filter_input( INPUT_GET, 'user', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
			if ( ! $user_id ) {
				return '<div class="ui ' . $options['interfaceClass'] . ' segment">The user_id parameter is invalid.</div>';
			}

			// password form
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				return '<div class="ui ' . $options['interfaceClass'] . ' segment">User does not exist.</div>';
			}

			$code = get_user_meta( $user_id, 'videowhisper_newpassword', true );
			if ( ! $code ) {
				$error .= '<li>' . __( 'Password change request code missing. Already changed?', 'ppv-live-webcams' ) . '</li>';
			}
			
			// Check token expiration
			$expiry = get_user_meta( $user_id, 'videowhisper_newpassword_expiry', true );
			if ($expiry && time() > intval($expiry)) {
				$error .= '<li>' . __( 'Password reset link has expired. Please request a new one.', 'ppv-live-webcams' ) . '</li>';
				delete_user_meta( $user_id, 'videowhisper_newpassword' );
				delete_user_meta( $user_id, 'videowhisper_newpassword_expiry' );
			}
			
			// Verify CSRF nonce
			if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'reset-password-' . $user_id)) {
				$error .= '<li>' . __( 'Invalid security token. Please request a new password reset link.', 'ppv-live-webcams' ) . '</li>';
			}
			
			if ( ! $error ) {
				if ( $code != $key ) {
					$error .= '<li>' . __( 'Code does not match. Make sure you use link from last email if you tried multiple times.', 'ppv-live-webcams' ) . '</li>';
					
					// Track failed verification attempts
					$verify_attempts = get_user_meta( $user_id, 'password_reset_verify_attempts', true );
					$verify_attempts = $verify_attempts ? $verify_attempts + 1 : 1;
					update_user_meta( $user_id, 'password_reset_verify_attempts', $verify_attempts );
					
					// If too many failed attempts, invalidate the token
					if ($verify_attempts > 5) {
						delete_user_meta( $user_id, 'videowhisper_newpassword' );
						delete_user_meta( $user_id, 'videowhisper_newpassword_expiry' );
						$error .= '<li>' . __( 'Too many failed attempts. Please request a new password reset.', 'ppv-live-webcams' ) . '</li>';
					}
				}
			}

			if ( $error ) {
				$htmlCode .= '<div class="ui warning message">
    <div class="header">' . __( 'Error', 'ppv-live-webcams' ) . ':</div>
    <ul class="list">
      ' . $error . '
    </ul>
  </div>';
			}

				// show form to provide new password
			if ( ! $error ) {
				$htmlCode .= '<form class="ui ' . $options['interfaceClass'] . ' form ' . $status . '" method="post" enctype="multipart/form-data" action="' . wp_nonce_url( $this_page, 'vw_password', 'videowhisper' ) . '" id="passwordForm" name="passwordForm">';
				$htmlCode .= '<h4 class="ui dividing header"> <i class="lock icon"></i> ' . __( 'Change Password', 'ppv-live-webcams' ) . ': ' . $user->user_login . '</h4>';

				if ( $options['recaptchaSite'] ) {
					wp_enqueue_script( 'google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . $options['recaptchaSite'], array() );

					$htmlCode .= '<input type="hidden" name="recaptcha_response" id="recaptchaResponseLogin">';

					$htmlCode .= '<script>
function onFormSubmitClick() {
        grecaptcha.ready(function() {
          grecaptcha.execute("' . $options['recaptchaSite'] . '", {action: "password"}).then(function(token) {
		  var recaptchaResponse = document.getElementById("recaptchaResponseLogin");
		  recaptchaResponse.value = token;
		  console.log("VideoWhisper Login: Google reCaptcha v3 updated", token, recaptchaResponse);
		  var ticketForm = document.getElementById("passwordForm");
		  ticketForm.submit();
          });
        });
      }
</script>
<noscript>JavaScript is required to use <a href="https://paidvideochat.com/">PaidVideochat turnkey live cam site solution</a>. Contact <a href="https://videowhisper.com/">VideoWhisper Online Platforms</a> for clarifications.</noscript>';
				} else {
					// recaptcha disabled
					$htmlCode .= '<script>
function onFormSubmitClick(e) {
  	  console.log("VideoWhisper Login: Google reCaptcha v3 disabled");
		  var ticketForm = document.getElementById("passwordForm");
		  ticketForm.submit();
      }
</script>
<noscript>JavaScript is required to use <a href="https://paidvideochat.com/">PaidVideochat turnkey live cam site solution</a>. Contact <a href="https://videowhisper.com/">VideoWhisper Online Platforms</a> for clarifications.</noscript>
';
				}

				$htmlCode .= '
	<div class="field">
    <label>' . __( 'Password', 'ppv-live-webcams' ) . '</label>
    <input type="password" name="password" value="' . '" placeholder="' . __( 'Password', 'ppv-live-webcams' ) . '">
  </div>

  <div class="field">
    <label>' . __( 'Password Verification', 'ppv-live-webcams' ) . '</label>
    <input type="password" name="password2" value="' . '" placeholder="' . __( 'Password Verification', 'ppv-live-webcams' ) . '">
  </div>
  ';

				$htmlCode .= '
  <div class="field">
  <input type="hidden" name="videowhisper_password" value="password">
  <input type="hidden" name="user_id" value="' . $user_id . '">
  <input type="hidden" name="key" value="' . $key . '">

  <button type="button" id="submitButton" name="submitButton" class="ui submit button" onclick="onFormSubmitClick()">
  <i class="lock icon"></i>
  ' . __( 'Update', 'ppv-live-webcams' ) . '
</button>
   </div>
  ';

				$htmlCode .= '
				<div class="field">';
				$htmlCode .= ' <a class="ui tiny button basic" href="' . $atts['terms'] . '"> <i class="clipboard list icon"></i> ' . __( 'Terms of Use', 'ppv-live-webcams' ) . '</a> ';
				$htmlCode .= ' <a class="ui tiny button basic" href="' . $atts['privacy'] . '"> <i class="clipboard list icon"></i> ' . __( 'Privacy Policy', 'ppv-live-webcams' ) . '</a> ';
				$htmlCode .= '<br/></div>';

				$htmlCode .= '<br/></form>';

			}
		}

		if ( isset($_POST['videowhisper_password']) && $_POST['videowhisper_password'] == 'email' && isset($_POST['email']) && $_POST['email'] ) {

			// bot checks
			if ( ! wp_verify_nonce( $_GET['videowhisper'], 'vw_password' ) ) {
				$error .= '<li>' . __( 'Nonce incorrect for password reset.', 'ppv-live-webcams' ) . '</li>';
			}

			if ( $options['recaptchaSite'] ) {

				if ( isset( $_POST['recaptcha_response'] ) ) {
					if ( $_POST['recaptcha_response'] ) {
						// Build POST request:
						$recaptcha_response = sanitize_text_field( $_POST['recaptcha_response'] );

						// Make and decode POST request:
						// $recaptcha = file_get_contents('https://www.google.com/recaptcha/api/siteverify' . '?secret=' . $options['recaptchaSecret'] . '&response=' . $recaptcha_response);
						// $recaptchaD = json_decode($recaptcha);
						$response   = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify' . '?secret=' . $options['recaptchaSecret'] . '&response=' . $recaptcha_response );
						$body       = wp_remote_retrieve_body( $response );
						$recaptchaD = json_decode( $body );

						// Take action based on the score returned:
						if ( !$recaptchaD->score )
						{
							$error .= '<li>Google reCAPTCHA v3 Failed. No score received. Try again, disable ad blockers or use a different browser!</li>';

						}
						elseif ( $recaptchaD->score >= 0.3 ) {
							// Verified
							$htmlCode .= '<!-- VideoWhisper reCAPTCHA v3 score: ' . $recaptchaD->score . '-->';

						} else {
							// Not verified - show form error
							$error .= '<li>Google reCAPTCHA v3 Failed. Score: ' . $recaptchaD->score . ' . Try again, disable ad blockers or use a different browser!</li>';
						}
					} else {
						$error .= '<li>Google reCAPTCHA v3 empty. Make sure you have JavaScript enabled or try a different browser!</li>';
					}
				} else {
					$error .= '<li>Google reCAPTCHA v3 missing. Make sure you have JavaScript enabled or try a different browser!</li>';
				}
			}

			$email = sanitize_email( $_POST['email'] );
			if ( ! $email ) {
				$error .= '<li>' . __( 'A valid email address is required.', 'ppv-live-webcams' ) . '</li>';
			}

			if ( ! $error ) {
				$user = get_user_by( 'email', $email );
				if ( ! $user ) {
					$error .= '<li>' . __( 'No user found for that email. Try a different email or register a new account!', 'ppv-live-webcams' ) . '</li>';
				} else {
					$user_id         = $user->ID;
					// Generate a cryptographically secure random token instead of predictable token
					$code            = bin2hex(random_bytes(32)); // Generate a 64-character random hex string
					$expiry          = time() + 3600; // Token expires in 1 hour
					$activation_link = add_query_arg(
						array(
							'key'  => $code,
							'user' => $user_id,
							'nonce' => wp_create_nonce('reset-password-' . $user_id)
						),
						get_permalink( $options['p_videowhisper_password_form'] )
					);
					update_user_meta( $user_id, 'videowhisper_newpassword', $code ); // password reset code
					update_user_meta( $user_id, 'videowhisper_newpassword_expiry', $expiry ); // set expiration
					
					// Rate limiting for reset requests
					$reset_attempts = get_user_meta( $user_id, 'password_reset_attempts', true );
					$reset_attempts_time = get_user_meta( $user_id, 'password_reset_attempts_time', true );
					
					// Reset counter if it's been more than an hour since last attempt
					if ($reset_attempts_time && time() - $reset_attempts_time > 3600) {
						$reset_attempts = 0;
					}
					
					// Increment attempt counter
					$reset_attempts = $reset_attempts ? $reset_attempts + 1 : 1;
					update_user_meta( $user_id, 'password_reset_attempts', $reset_attempts );
					update_user_meta( $user_id, 'password_reset_attempts_time', time() );

					wp_mail( $email, $options['passwordSubject'], $options['passwordText'] . "\r\n" . $activation_link );

					//notify by SMS
					if ($options['sms_number'])
					{
						$sms_number = get_user_meta( $user->ID, 'sms_number', true );
						// Verify that the SMS number is properly formatted before sending
						$sms_number = preg_replace('/[^0-9+]/', '', $sms_number);

						if ($sms_number)
						{
							$sms_sent = false;

							//cooldown
							$sms_last =  get_user_meta( $user->ID, 'sms_last', true );

							if (!$sms_last || $sms_last < ( time() - $options['sms_cooldown']) )
							{

							// Sanitize the SMS message content
							$sms_message = sanitize_text_field($options['passwordSubject']) . ' ' . esc_url($activation_link);

							if ($sms_number && $options['wp_sms'] && function_exists('wp_sms_send')) {
								wp_sms_send($sms_number, $sms_message);
								$sms_sent = true;
							}

						
							if ($options['wp_sms_twilio']) if ( function_exists('twl_send_sms') )
							{
								$response = twl_send_sms( array( 'number_to' => $sms_number, 'message' => $sms_message ) );
								if( is_wp_error( $response ) ) $htmlCode .= '<div class="ui message">SMS Error: ' . esc_html( $response->get_error_message() ) . '</div>';
								else 
								{ //sms sent
									$sms_sent = true;
								}
							}

							if ($sms_sent)
							{
								// update sms 
								update_user_meta( $user->ID, 'sms_last', time() );
							}


							}
						}
					}

					$htmlCode .= '<h4 class="ui dividing header"> <i class="lock icon"></i> ' . __( 'Change Password', 'ppv-live-webcams' ) . '</h4>';
					$htmlCode .= '<div class="ui message">' . __( 'Email was sent. Use link from email to change password.', 'ppv-live-webcams' ) . '</div>';

				}
			}

			if ( $error ) {
				$htmlCode .= '<div class="ui warning message">
    <div class="header">' . __( 'Error', 'ppv-live-webcams' ) . ':</div>
    <ul class="list">
      ' . $error . '
    </ul>
  </div>';
			}
		}

		if ( isset($_POST['videowhisper_password']) && $_POST['videowhisper_password'] == 'password' && isset($_POST['password']) ) {
			// change password

			$password  = sanitize_text_field( $_POST['password'] );
			$password2 = sanitize_text_field( $_POST['password2'] ?? '' );
			$user_id   = intval( $_POST['user_id'] ?? 0 );
			$key       = sanitize_text_field( $_POST['key'] ?? '' );

			// security errors: exit
			if ( ! $user_id || ! $key ) {
				return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment">Key parameters missing.</div>';
			}
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment">User does not exist.</div>';
			}
			$code = get_user_meta( $user_id, 'videowhisper_newpassword', true );
			if ( $code != $key ) {
				return __( 'Code does not match. Make sure you use link from last email if you tried multiple times.', 'ppv-live-webcams' );
			}

			// user errors
			if ( ! $password ) {
				$error .= '<li>' . __( 'A password is required.', 'ppv-live-webcams' ) . '</li>';
			}
			if ( $password != $password2 ) {
				$error .= '<li>' . __( 'Password verification does not match.', 'ppv-live-webcams' ) . '</li>';
			}

			// bot checks
			if ( ! wp_verify_nonce( $_GET['videowhisper'], 'vw_password' ) ) {
				$error .= '<li>' . __( 'Nonce incorrect for password reset.', 'ppv-live-webcams' ) . '</li>';
			}
			if ( $options['recaptchaSite'] ) {

				if ( isset( $_POST['recaptcha_response'] ) ) {
					if ( $_POST['recaptcha_response'] ) {
						// Build POST request:
						$recaptcha_response = sanitize_text_field( $_POST['recaptcha_response'] );

						// Make and decode POST request:
						// $recaptcha = file_get_contents('https://www.google.com/recaptcha/api/siteverify' . '?secret=' . $options['recaptchaSecret'] . '&response=' . $recaptcha_response);
						// $recaptchaD = json_decode($recaptcha);
						$response   = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify' . '?secret=' . esc_attr( $options['recaptchaSecret'] )  . '&response=' . esc_attr( $recaptcha_response)  );
						$body       = wp_remote_retrieve_body( $response );
						$recaptchaD = json_decode( $body );

						// Take action based on the score returned:
						if ( $recaptchaD->score >= 0.3 ) {
							// Verified
							$htmlCode .= '<!-- VideoWhisper reCAPTCHA v3 score: ' . esc_html( $recaptchaD->score ) . '-->';

						} else {
							// Not verified - show form error
							$error .= '<li>Google reCAPTCHA v3 Failed. Score: ' . esc_html( $recaptchaD->score ) . ' . Try again or using a different browser!</li>';
						}
					} else {
						$error .= '<li>Google reCAPTCHA v3 empty. Make sure you have JavaScript enabled or try a different browser!</li>';
					}
				} else {
					$error .= '<li>Google reCAPTCHA v3 missing. Make sure you have JavaScript enabled or try a different browser!</li>';
				}
			}

			if ( $error ) {
				$htmlCode .= '<div class="ui warning message">
    <div class="header">' . __( 'Error', 'ppv-live-webcams' ) . ':</div>
    <ul class="list">
      ' . $error . '
    </ul>
  </div>';
			} else {
				wp_set_password( $password, $user_id );
				delete_user_meta( $user_id, 'videowhisper_newpassword' ); // password changed
				update_user_meta( $user_id, 'videowhisper_ip_newpassword', self::get_ip_address() ); // save ip used to change password

				$htmlCode .= '<h4 class="ui dividing header"> <i class="lock icon"></i> ' . __( 'Change Password', 'ppv-live-webcams' ) . ': ' . esc_html( $user->user_login ) . '</h4>';
				$htmlCode .= '<div class="ui message">' . __( 'Password was changed.', 'ppv-live-webcams' ) . '</div>';
				if ( ! is_user_logged_in() ) {
					$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' header"> <i class="sign in icon"></i> ' . __( 'Login', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form]' );
				}
			}
		}

		// email form
		if ( !isset($_POST['videowhisper_password']) && !isset($_GET['key']) ) {
			// provide email form
			$status = '';
			$email = '';

			$htmlCode .= '<form class="ui ' . esc_attr( $options['interfaceClass'] ) . ' form ' . $status . '" method="post" enctype="multipart/form-data" action="' . wp_nonce_url( $this_page, 'vw_password', 'videowhisper' ) . '" id="passwordForm" name="passwordForm">';

			$htmlCode .= '<h4 class="ui dividing header"> <i class="lock icon"></i> ' . __( 'Change Password', 'ppv-live-webcams' ) . '</h4>';

			if ( $options['recaptchaSite'] ) {
				wp_enqueue_script( 'google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . $options['recaptchaSite'], array() );

				$htmlCode .= '<input type="hidden" name="recaptcha_response" id="recaptchaResponseLogin">';

				$htmlCode .= '<script>
function onFormSubmitClick() {
        grecaptcha.ready(function() {
          grecaptcha.execute("' . esc_attr( $options['recaptchaSite'] ). '", {action: "password"}).then(function(token) {
		  var recaptchaResponse = document.getElementById("recaptchaResponseLogin");
		  recaptchaResponse.value = token;
		  console.log("VideoWhisper Login: Google reCaptcha v3 updated", token, recaptchaResponse);
		  var ticketForm = document.getElementById("passwordForm");
		  ticketForm.submit();
          });
        });
      }
</script>
<noscript>JavaScript is required to use <a href="https://paidvideochat.com/">PaidVideochat turnkey live cam site solution</a>. Contact <a href="https://videowhisper.com/">VideoWhisper Online Platforms</a> for clarifications.</noscript>';
			} else {
				// recaptcha disabled
				$htmlCode .= '<script>
function onFormSubmitClick(e) {
  	  console.log("VideoWhisper Login: Google reCaptcha v3 disabled");
		  var ticketForm = document.getElementById("passwordForm");
		  ticketForm.submit();
      }
</script>
<noscript>JavaScript is required to use <a href="https://paidvideochat.com/">PaidVideochat turnkey live cam site solution</a>. Contact <a href="https://videowhisper.com/">VideoWhisper Online Platforms</a> for clarifications.</noscript>
';
			}

			if ( $error ) {
				$htmlCode .= '<div class="ui warning message">
    <div class="header">' . __( 'Error', 'ppv-live-webcams' ) . ':</div>
    <ul class="list">
      ' . $error . '
    </ul>
  </div>';
			}

			$htmlCode .= '
  <div class="field">
    <label>' . __( 'Email', 'ppv-live-webcams' ) . '</label>
    <input type="text" name="email" value="' . esc_attr($email) . '" placeholder="' . __( 'Email', 'ppv-live-webcams' ) . '">
    ' . __( 'Provide account email to request password change.', 'ppv-live-webcams' ) . '
  </div> ';

			$htmlCode .= '
  <div class="field">
  <input type="hidden" name="videowhisper_password" value="email">
  <button type="button" id="submitButton" name="submitButton" class="ui submit button" onclick="onFormSubmitClick()">
  <i class="mail icon"></i>
  ' . __( 'Send', 'ppv-live-webcams' ) . '
</button>
   </div>
  ';

			$htmlCode .= '
				<div class="field">';
			$htmlCode .= ' <a class="ui tiny button basic" href="' . esc_url( $atts['terms'] ) . '"> <i class="clipboard list icon"></i> ' . __( 'Terms of Use', 'ppv-live-webcams' ) . '</a> ';
			$htmlCode .= ' <a class="ui tiny button basic" href="' . esc_url( $atts['privacy'] ) . '"> <i class="clipboard list icon"></i> ' . __( 'Privacy Policy', 'ppv-live-webcams' ) . '</a> ';
			$htmlCode .= '<br/></div>';

			$htmlCode .= '<br/></form>';

			// end email form
		}

		return $htmlCode;

	}


	static function videowhisper_login_form( $atts ) {

		$options = get_option( 'VWliveWebcamsOptions' );

		// shortocode attributes
		$atts = shortcode_atts(
			array(
				'role'     => '',
				'register' => 0,
				'terms'    => get_permalink( $options['p_videowhisper_terms'] ),
				'privacy'  => get_permalink( $options['p_videowhisper_privacy'] ),
			),
			$atts,
			'videowhisper_login_form'
		);
		$atts = array_map('esc_attr', $atts);
		$atts['terms'] = esc_url($atts['terms']);
		$atts['privacy'] = esc_url($atts['privacy']);

		self::enqueueUI();
		$this_page = self::getCurrentURL();
		$htmlCode  = '';

		if ( ! $options['loginFrontend'] ) {
			$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">';
			$htmlCode .= __( 'Frontend login is disabled from settings.', 'ppv-live-webcams' );
			$htmlCode .= '<br /> <a class="ui button qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'ppv-live-webcams' ) . '</a>';
			$htmlCode .= '</div>';
			return $htmlCode;
		}

		if ( $_GET['logout'] ?? false ) {
			if ( wp_verify_nonce( $_GET['verify'], 'logout' . $_GET['logout'] ) ) {
				$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'You logged out.', 'ppv-live-webcams' ) . '</div>';
			} else {
				return 'Logout nonce error.';
			}
		}

		// already logged in?
		$current_user = wp_get_current_user();
		if ( $current_user->ID ) {
			$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment"> <i class="user icon"></i>';

			$htmlCode .= __( 'You are currently logged in as', 'ppv-live-webcams' ) . ': ' . $current_user->user_login;
			$htmlCode .= '<BR /><BR /> <a class="ui button" href="' . wp_nonce_url( add_query_arg( 'logout', $current_user->ID, $this_page ), 'logout' . $current_user->ID, 'verify' ) . '"> <i class="sign out icon"></i>' . __( 'Log Out', 'ppv-live-webcams' ) . '</a> ';

			$htmlCode .= '</div>';

			return $htmlCode;
		}

		// process login form

		$error  = '';
		$status = '';

		if (isset($_GET['login_error'])) $error = wp_kses(
			$_GET['login_error'],
			array(
				'a'      => array(
					'href'  => array(),
					'title' => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'li'     => array(),
			)
		);

		if ( $error ) {
			$status = 'warning';
		}

		// show form
		if ( $_SERVER['REQUEST_METHOD'] != 'POST' || $error ) {

			$htmlCode .= '<form defaultbutton="submitButton" class="ui ' . $options['interfaceClass'] . ' form ' . $status . '" method="post" enctype="multipart/form-data" action="' . wp_nonce_url( $this_page, 'vw_login', 'videowhisper' ) . '" id="loginForm" name="loginForm">';

			if ( $options['recaptchaSite'] ) {
				wp_enqueue_script( 'google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . $options['recaptchaSite'], array() );

				$htmlCode .= '<input type="hidden" name="recaptcha_response" id="recaptchaResponseLogin">';

				$htmlCode .= '<script>
function onLoginSubmitClick(e) {
		e.preventDefault();
        grecaptcha.ready(function() {
          grecaptcha.execute("' . $options['recaptchaSite'] . '", {action: "login"}).then(function(token) {
		  var recaptchaResponse = document.getElementById("recaptchaResponseLogin");
		  recaptchaResponse.value = token;
		  console.log("VideoWhisper Login: Google reCaptcha v3 updated", token, recaptchaResponse);
		  var ticketForm = document.getElementById("loginForm");
		  ticketForm.submit();
          });
        });
      }
</script>
<noscript>JavaScript is required to use <a href="https://paidvideochat.com/">PaidVideochat turnkey live cam site solution</a>. Contact <a href="https://videowhisper.com/">VideoWhisper Online Platforms</a> for clarifications.</noscript>';
			} else {
				// recaptcha disabled
				$htmlCode .= '<script>
function onLoginSubmitClick(e) {
	  e.preventDefault();
  	  console.log("VideoWhisper Login: Google reCaptcha v3 disabled");
	 	  var ticketForm = document.getElementById("loginForm");
		  ticketForm.submit();
      }
</script>
<noscript>JavaScript is required to use <a href="https://paidvideochat.com/">PaidVideochat turnkey site solution</a>. Contact <a href="https://videowhisper.com/">VideoWhisper</a> for clarifications.</noscript>
';
			}

			if ( $error ) {
				$htmlCode .= '<div class="ui warning message">
    <div class="header">' . __( 'Login Failed', 'ppv-live-webcams' ) . ':</div>
    <ul>
      ' . $error . '
     </ul>
  </div>';
			}

//preserve inputs
$login = sanitize_text_field( $_POST['login'] ?? '' );
$password = sanitize_text_field( $_POST['password'] ?? '' );

			$htmlCode .= '<div class="field">
  <div class="field">
    <label>' . __( 'Username', 'ppv-live-webcams' ) . '</label>
    <input type="text" name="login" value="' . esc_attr($login) . '" placeholder="">
  </div>

   <div class="field">
    <label>' . __( 'Password', 'ppv-live-webcams' ) . '</label>
    <input type="password" name="password" value="" placeholder="">
  </div>
 ';

			$htmlCode .= '
  <div class="field">
  <input type="hidden" name="videowhisper_login" value="1">
  <button id="submitButton" name="submitButton" class="ui submit button" onclick="onLoginSubmitClick(event)">
  <i class="sign in icon"></i>
  ' . __( 'Login', 'ppv-live-webcams' ) . '
</button>
   </div>
  ';

			// <input type="button" id="submitButton" name="submitButton" onclick="onLoginSubmitClick()" class="ui submit button" value="" />

			if ( $atts['register'] ) {
				$htmlCode .= '
  <div class="field">';

				if ( $atts['role'] == $options['roleClient'] || ! $atts['role'] ) {
					$htmlCode .= ' <a class="ui tiny button basic" href="' . get_permalink( $options['p_videowhisper_register_client'] ) . '"> <i class="user plus icon"></i> ' . __( 'Register', 'ppv-live-webcams' ) . ' ' . ucwords( $options['roleClient'] ) . '</a> ';
				}

				if ( $atts['role'] == $options['rolePerformer'] || ! $atts['role'] ) {
					$htmlCode .= ' <a class="ui tiny button basic" href="' . get_permalink( $options['p_videowhisper_register_performer'] ) . '"> <i class="user plus icon"></i> ' . __( 'Register', 'ppv-live-webcams' ) . ' ' . ucwords( $options['rolePerformer'] ) . '</a> ';
				}

				if ( $options['studios'] ) {
					if ( $atts['role'] == $options['roleStudio'] || ! $atts['role'] ) {
						$htmlCode .= ' <a class="ui tiny button basic" href="' . get_permalink( $options['p_videowhisper_register_studio'] ) . '"> <i class="user plus icon"></i> ' . __( 'Register', 'ppv-live-webcams' ) . ' ' . ucwords( $options['roleStudio'] ) . '</a> ';
					}
				}

				if ( $options['p_videowhisper_password_form'] ) {
					$htmlCode .= ' <a class="ui tiny button basic" href="' . get_permalink( $options['p_videowhisper_password_form'] ) . '"> <i class="lock icon"></i> ' . __( 'Lost Password', 'ppv-live-webcams' ) . '</a> ';
				}

					$htmlCode .= ' <a class="ui tiny button basic" href="' . esc_url( $atts['terms'] ) . '"> <i class="clipboard list icon"></i> ' . __( 'Terms of Use', 'ppv-live-webcams' ) . '</a> ';
				$htmlCode     .= ' <a class="ui tiny button basic" href="' . esc_url( $atts['privacy'] ) . '"> <i class="clipboard list icon"></i> ' . __( 'Privacy Policy', 'ppv-live-webcams' ) . '</a> ';

				$htmlCode .= '<br/></div>';
			}

			$htmlCode .= '<br/></form>';
			// end show login form
		}

		return $htmlCode;

	}


	static function videowhisper_register_form( $atts ) {
		//registration form
		$options = get_option( 'VWliveWebcamsOptions' );

		// shortocode attributes
		$atts = shortcode_atts(
			array(
				'role'    => $options['roleClient'],
				'terms'   => get_permalink( $options['p_videowhisper_terms'] ),
				'privacy' => get_permalink( $options['p_videowhisper_privacy'] ),
			),
			$atts,
			'videowhisper_register_form'
		);
		$atts = array_map('esc_attr', $atts);
		$atts['terms'] = esc_url($atts['terms']);
		$atts['privacy'] = esc_url($atts['privacy']);

		self::enqueueUI();

		if ( ! $options['registerFrontend'] ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'Frontend registration is disabled!', 'ppv-live-webcams' ) . '</div>';
		}
		if ( $atts['role'] == $options['roleStudio'] && ! $options['studios'] ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'Studio registration is disabled!', 'ppv-live-webcams' ) . '</div>';
		}

		$error    = '';
		$status   = '';
		$htmlCode = '';

		if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {

			if ( ! wp_verify_nonce( $_GET['videowhisper'], 'vw_register' ) ) {
				$error .= '<li>' . __( 'Nonce incorrect for registration.', 'ppv-live-webcams' ) . '</li>';
			}

			if (is_user_logged_in()) $error .= '<li>' . __( 'You are already have an account and are logged in.', 'ppv-live-webcams' ) . '</li>';

			$login     = sanitize_text_field( $_POST['login'] ?? '');
			$firstname = sanitize_text_field( $_POST['firstname'] ?? '' );
			$lastname  = sanitize_text_field( $_POST['lastname'] ?? '' );
			$password  = sanitize_text_field( $_POST['password'] ?? '');
			$password2 = sanitize_text_field( $_POST['password2'] ?? '');

			if (!$firstname) $firstname = $login;

			$email = sanitize_email( $_POST['email'] ?? '') ;
			$terms = sanitize_text_field( $_POST['terms'] ?? '');

			if ( ! $login ) {
				$error .= '<li>' . __( 'A login (username) is required.', 'ppv-live-webcams' ) . '</li>';
			}
			if ( ! $terms ) {
				$error .= '<li>' . __( 'Accepting Terms of Use is required.', 'ppv-live-webcams' ) . '</li>';
			}

			if ( ! $password ) {
				$error .= '<li>' . __( 'A password is required.', 'ppv-live-webcams' ) . '</li>';
			}

			if ( $password != $password2 ) {
				$error .= '<li>' . __( 'Password verification does not match.', 'ppv-live-webcams' ) . '</li>';
			}

			if ( ! $email ) {
				$error .= '<li>' . __( 'A valid email address is required.', 'ppv-live-webcams' ) . '</li>';
			}



			if ( $options['registerIPlimit'] ) {
				$users = get_users(
					array(
						'meta_key'     => 'videowhisper_ip_register',
						'meta_value'   => self::get_ip_address(),
						'meta_compare' => '=',
					)
				);
				if ( count( $users ) >= intval( $options['registerIPlimit'] ) ) {
					$error .= '<li>' . __( 'Registration per IP limit reached.', 'ppv-live-webcams' ) . ' #' . count( $users ) . '</li>';
				}
			}

			if ( $options['recaptchaSite'] ) {

				if ( isset( $_POST['recaptcha_response'] ) ) {
					if ( $_POST['recaptcha_response'] ) {
						// Build POST request:
						$recaptcha_response = sanitize_text_field( $_POST['recaptcha_response'] );

						// Make and decode POST request:
						// $recaptcha = file_get_contents('https://www.google.com/recaptcha/api/siteverify' . '?secret=' . $options['recaptchaSecret'] . '&response=' . $recaptcha_response);
						// $recaptchaD = json_decode($recaptcha);
						$response   = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify' . '?secret=' . $options['recaptchaSecret'] . '&response=' . $recaptcha_response );
						$body       = wp_remote_retrieve_body( $response );
						$recaptchaD = json_decode( $body );

						// Take action based on the score returned:
						if ( isset($recaptchaD->score) && $recaptchaD->score >= 0.3 ) {
							// Verified
							$htmlCode .= '<!-- VideoWhisper reCAPTCHA v3 score: ' . $recaptchaD->score . '-->';

						} else {
							// Not verified - show form error
							$error .= '<li>Google reCAPTCHA v3 Failed. Score: ' . ( $recaptchaD->score ?? 'n/a') . ' . Try again or using a different browser!</li>';
						}
					} else {
						$error .= '<li>Google reCAPTCHA v3 empty. Make sure you have JavaScript enabled or try a different browser!</li>';
					}
				} else {
					$error .= '<li>Google reCAPTCHA v3 missing. Make sure you have JavaScript enabled or try a different browser!</li>';
				}
			}

			$persons = intval($_POST['persons'] ?? 0);

			//pre-check if required records were filled
			$requiredPresent = true;
			if ( $atts['role'] == $options['rolePerformer'] || $atts['role'] == $options['roleStudio'] )
				if ( is_array( $options['recordFields'] ) )
						foreach ( $options['recordFields'] as $field => $parameters ) if ( $parameters['required'] ?? false ) {

							$fieldName = sanitize_title( trim( $field ) );

							if ($options['multiPerson'] && isset($parameters['personal']) && $parameters['personal'] )
							{
								for ($pi=1; $pi <= $persons; $pi++)
								{
									$fieldName = sanitize_title( trim( $field ) ) . '_' . $pi ;

									if ( $parameters['type'] =='file')
									if  (!isset($_FILES[ $fieldName ]['tmp_name']) || !$_FILES[ $fieldName ]['tmp_name'] ) {

										$error .= '<li>' . $field . ' : ' . __( 'Required file is missing!', 'ppv-live-webcams' ) . ' '. $pi . ' / '. $persons . '</li>';
									}
									else
									{
										//pre-check extension and size
										$ext     = strtolower( pathinfo( sanitize_file_name( $_FILES[ $fieldName ]['name'] ), PATHINFO_EXTENSION ) );
										$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'pdf' );

										if ( ! in_array( $ext, $allowed ) ) {
											$error .= '<li>' . esc_html( $field ) . ' / ' . esc_html( $_FILES[ $fieldName ]['name'] ) . ': Unsupported file extension! Please use one of these formats: ' . implode( ', ', $allowed ) . ' '. $pi . ' / '. $persons . '</li>';
										}

										$maxUpload = intval ($options['maxUpload'] ?? 6000) * 1000;
										if ( $_FILES[ $fieldName ]['size'] > $maxUpload ) {
											$error .= '<li>' . esc_html( $field ) . ' / ' . esc_html( $_FILES[ $fieldName ]['name'] ) . ': File is too big! Please use a file under ' . $maxUpload . 'kb.' . ' '. $pi . ' / '. $persons . '</li>';
										}
									}

									if ( $parameters['type'] !='file' && !$_POST[ $fieldName ] ) {
										$error .= '<li>' . $field . ' : ' . __( 'Required field is missing!', 'ppv-live-webcams' ) . ' '. $pi . ' / '. $persons . '</li>';
									}

								}

							}
							else
							{

							//account records (not personal)
							if ( $parameters['type'] =='file')
							if  (!$_FILES[ $fieldName ]['tmp_name'] ) {

								$error .= '<li>' . $field . ' : ' . __( 'Required file is missing!', 'ppv-live-webcams' ) . '</li>';
							}
							else
							{
								//pre-check extension and size
								$ext     = strtolower( pathinfo( sanitize_file_name( $_FILES[ $fieldName ]['name'] ), PATHINFO_EXTENSION ) );
								$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'pdf' );

								if ( ! in_array( $ext, $allowed ) ) {
									$error .= '<li>' . esc_html( $field ) . ' / ' . esc_html( $_FILES[ $fieldName ]['name'] ) . ': Unsupported file extension! Please use one of these formats: ' . implode( ', ', $allowed ). '</li>';
								}

								$maxUpload = intval ($options['maxUpload'] ?? 6000) * 1000;
								if ( $_FILES[ $fieldName ]['size'] > $maxUpload ) {
									$error .= '<li>' . esc_html( $field ) . ' / ' . esc_html( $_FILES[ $fieldName ]['name'] ) . ': File is too big! Please use a file under ' . $maxUpload . 'kb.'. '</li>';
								}
							}

							if ( $parameters['type'] !='file' && !$_POST[ $fieldName ] ) {
								$error .= '<li>' . $field . ' : ' . __( 'Required field is missing!', 'ppv-live-webcams' ) . '</li>';
							}

						}

						}

			if ( $error ) {
				$status = 'warning';
			} else {

				// create role if missing
				if ( ! get_role( $atts['role'] ) ) {
					add_role( $atts['role'], ucwords( $atts['role'] ), array( 'read' => true ) );
				}

				// register user
				$newuser = array(
					'user_pass'  => $password,
					'user_login' => $login,
					'user_email' => $email,
					'first_name' => $firstname,
					'last_name'  => $lastname,
					'role'       => $atts['role'],
				);

				$user_id = wp_insert_user( $newuser );

				if ( $user_id && ! is_wp_error( $user_id ) )
				if ( !$options['registrationNoActivation'] || ( $options['registrationNoActivation'] == 'client' &&  $atts['role'] != $options['roleClient'] ) )
				{
					$code = sha1( $user_id . time() );

					add_user_meta( $user_id, 'videowhisper_activate', $code, true ); // mark activation required
					update_user_meta( $user_id, 'videowhisper_ip_register', self::get_ip_address() ); // save ip
					update_user_meta( $user_id, 'created_by', 'ppv-live-webcams/register_form' ); // for troubleshooting registration origin
					update_user_meta( $user_id, 'videowhisper_role', $atts['role'] ); // videowhisper registration role

					$activation_link = add_query_arg(
						array(
							'key'  => $code,
							'user' => $user_id,
						),
						get_permalink($options['p_videowhisper_register_activate'])
					);
					
					$headers   = array();
					$headers[] = 'Content-Type: text/plain; charset=utf-8';
					$headers[] = 'Content-Transfer-Encoding: bit-8';

					$message = $options['activateText'] . "\r\n" . $activation_link;
					
					wp_mail($email, $options['activateSubject'], $message, $headers);
					
					$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">' . esc_html($options['activateMessage']) . '</div>';
				}
				else $htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'Your account was registered.', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="0"]' );


				if ( is_wp_error( $user_id ) ) {
					$status = 'warning';
					$error .= '<li>' . $user_id->get_error_message() . '</li>';
				} else {

					//user created
					$user   = get_userdata( $user_id );

				// process administrative records
					if ( $atts['role'] == $options['rolePerformer'] || $atts['role'] == $options['roleStudio'] ) {
						if ( is_array( $options['recordFields'] ) ) {

							$error2 = '';

							$persons = 1;
							$personal = false;
							if ($options['multiPerson'])
							{
								$persons = intval($_POST['persons'] ?? 1);
								update_user_meta( $user->ID, 'persons', $persons );

							//set types for persons
							for ($pi = 1; $pi <= $persons; $pi++) if (isset($_POST['person_' . $pi])) update_user_meta( $user->ID, 'person_' . $pi, sanitize_text_field( $_POST['person_' . $pi] ) );

							}

							if ( $user ) {
								foreach ( $options['recordFields'] as $field => $parameters ) {

									$fieldName = sanitize_title( trim( $field ) ); //base fieldname

									$maxperson = $persons;
									if ( $options['multiPerson'] && isset($parameters['personal']) && $parameters['personal'] ) $personal = true; else $personal = false;
									if (!$personal) $maxperson = 1; // only 1 iteration

									for ($pi = 1; $pi <= $maxperson; $pi++)
									{

									//start person
									if ($options['multiPerson'] && $personal) $fieldName = sanitize_title( trim( $field ) ) . '_' . $pi; //multiple person: add person index to field name

									switch ( $parameters['type'] ) {
										case 'file':
											$uploadSuccess = false;
											if ( $_FILES[ $fieldName ]['tmp_name'] ?? false && $filename =  $_FILES[ $fieldName ]['tmp_name'] ) {
												$deny = false;

												$ext     = strtolower( pathinfo( $_FILES[ $fieldName ]['name'] , PATHINFO_EXTENSION  ) );
												$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'pdf' );

												if ( ! in_array( $ext, $allowed ) ) {
													$error2 .= '<br>' . $fieldName . ' - ' . $filename . ': Unsupported file extension! Please use one of these formats: ' . implode( ', ', $allowed );
													$deny    = true;
												}

												$maxUpload = intval ($options['maxUpload'] ?? 6000) * 1000;
												if ( $_FILES[ $fieldName ]['size'] > $maxUpload ) {
													$error2 .= '<br>' . esc_html( $fieldName ) . ': File is too big! Please use a file under ' . $maxUpload . 'kb.';
													$deny      = true;
												}

												if ( ! $deny ) {
													$destination = $options['uploadsPath'] . '/user_' .  $user->user_nicename . '_' .  $user->ID  . '/';
													if ( ! file_exists( $destination ) ) {
														mkdir( $destination );
													}

													$newfilename = $fieldName . '_' . time() . '.' . $ext ;
													$newpath     = $destination . $newfilename;

													$errorUp = self::handle_upload( $_FILES[ $fieldName ], $newpath ); // handle trough wp_handle_upload()
													if ( $errorUp ) {
														$error2 .= '<br>' . 'Error uploading ' . esc_html( $filename . ':' . $errorUp );
													}

													if ( ! file_exists( $newpath ) ) {
														$error2 .= '<br>' . esc_html( $fieldName ) . ': Error uploading ' . esc_html( $newpath );
													} else {
														$oldpath = get_user_meta( $user->ID, 'vwf_' . $fieldName, true );
														if ( $options['recordClean'] ) {
															if ( file_exists( $oldpath ) ) {
																unlink( $oldpath );
															}
														}

														update_user_meta( $user->ID, 'vwf_' . $fieldName, $newpath );
														$uploadSuccess = true;
													}
												}
											}

											if ($parameters['required'] ?? false) if (!$uploadSuccess) $error2 .= '<br>' . esc_html( $fieldName ) . ': ' . 'Required file not uploaded!';
											break;

										default:
											if ( isset( $_POST[ $fieldName ] ) ) 
											if ( is_string($_POST[ $fieldName ]) ) {
												update_user_meta( $user->ID, 'vwf_' . $fieldName, sanitize_textarea_field( $_POST[ $fieldName ] ) );
											} else $error2 .= '<br>' . esc_html( $fieldName ) . ': ' . ' Invalid value for field.';
									}
									//end person
								 }

									// end field
								}
							}

							update_user_meta( $user->ID, 'vwUpdated', time() );
							self::usersPendingApproval();

							if ( $error2 ) {
								$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'Some error occurred related to uploads. After email confirmation and login you can retry from Account Records section in Dashboard.', 'ppv-live-webcams' ) . $error2 . '</div>';
							}

							// end records

						}
					} //end performer administrative records

					$userID = $user_id;

					//create first room for performer
					if ( $atts['role'] == $options['rolePerformer'] )
					{
						$performerName = self::performerName( $user, $options );
						$postID = self::webcamPost( $performerName, $performerName, $userID );
					}


			//save Mathmaking options
			if ( $options['match'] )
			{

				foreach ( ['me', 'you'] as $side)
						{

						$fieldex = '';
						if ($side == 'you') $fieldex = 'm_'; //match

						foreach ( $options['profileFields'] as $field => $parameters ) if ( isset( $parameters['match'] ) ) if ( $parameters['match'] != 'mirror' || $side == 'me' )
							{

									$fieldName = $fieldex . sanitize_title( trim( $field ) );

										if ( isset( $_POST[ $fieldName ] ) ) {
												if ( $_POST['matchFor'] ) {
													if ( in_array( $parameters['type'], array('checkboxes', 'multiselect') ) ) 
													{
														$tags = (array) $_POST[ $fieldName ];
														if ( is_array( $tags ) ) {
															foreach ( $tags as &$tag ) {
																$tag = sanitize_text_field( $tag );
															}
															unset( $tag );
														} else {
															$tags = sanitize_text_field( $tags );
														}
														if ($postID ?? false) update_post_meta( $postID, 'vwf_' . $fieldName, $tags );
														else update_user_meta( $userID, 'vwf_'  . $fieldName, $tags );

													} else {
														if ($postID ?? false)  update_post_meta( $postID, 'vwf_' . $fieldName, sanitize_text_field( $_POST[ $fieldName ] ) );
														else update_user_meta( $userID, 'vwf_' . $fieldName, sanitize_text_field( $_POST[ $fieldName ] )  );
													}
												}
											}

							}
						}

					}

				}
			}
		}

		if ( $_SERVER['REQUEST_METHOD'] != 'POST' || $error != '' ) {

			$this_page = self::getCurrentURL();

			$htmlCode .= '<form class="ui ' . $options['interfaceClass'] . ' form ' . $status . '" method="post" enctype="multipart/form-data" action="' . wp_nonce_url( $this_page, 'vw_register', 'videowhisper' ) . '" id="registrationForm" name="registrationForm">';
			$htmlCode .= '<h4 class="ui dividing header"> <i class="user plus icon"></i> ' . __( 'Registration', 'ppv-live-webcams' ) . ': ' . ucwords( $atts['role'] ) . '</h4>';

			if ( $options['recaptchaSite'] ) {
				wp_enqueue_script( 'google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . $options['recaptchaSite'], array() );

				$htmlCode .= '<input type="hidden" name="recaptcha_response" id="recaptchaResponse">';

				$htmlCode .= '<script>
function onSubmitClick(e) {
        grecaptcha.ready(function() {
          grecaptcha.execute("' . $options['recaptchaSite'] . '", {action: "register"}).then(function(token) {
		  var recaptchaResponse = document.getElementById("recaptchaResponse");
		  recaptchaResponse.value = token;
		  console.log("VideoWhisper Registration: Google reCaptcha v3 updated", token);
		  var ticketForm = document.getElementById("registrationForm");
		  ticketForm.submit();
          });
        });
      }
</script>
<noscript>JavaScript is required to use <a href="https://paidvideochat.com/">PaidVideochat turnkey cam site solution</a>. Contact <a href="https://videowhisper.com/">VideoWhisper live streaming platforms</a> for clarifications.</noscript>
';
			} else {
				// recaptcha disabled
				$htmlCode .= '<script>
function onSubmitClick(e) {
  	  console.log("VideoWhisper Registration: Google reCaptcha v3 disabled");
		  var ticketForm = document.getElementById("registrationForm");
		  ticketForm.submit();

      }
</script>
<noscript>JavaScript is required to use <a href="https://paidvideochat.com/">PaidVideochat turnkey cam site solution</a>. Contact <a href="https://videowhisper.com/">VideoWhisper live streaming platforms</a> for clarifications.</noscript>
';
			}

			if ( $error ) {
				$htmlCode .= '<div class="ui warning message">
    <div class="header">' . __( 'Could not submit registration', 'ppv-live-webcams' ) . ':</div>
    <ul class="list">
      ' . $error . '
    </ul>
  </div>';
			}

			if ( !$options['registrationAanonymous'] || ( $options['registrationAanonymous'] == 'client' &&  $atts['role'] != $options['roleClient'] ) )
			$htmlCode .= '<div class="field">
    <label>' . __( 'First Name', 'ppv-live-webcams' ) . '</label>
    <input type="text" name="firstname" value="' . ( $firstname ?? '' ) . '" placeholder="' . __( 'First Name', 'ppv-live-webcams' ) . '">
  </div>
  <div class="field">
    <label>' . __( 'Last Name', 'ppv-live-webcams' ) . '</label>
    <input type="text" name="lastname" value="' . ( $lastname ?? '' ). '" placeholder="' . __( 'Last Name', 'ppv-live-webcams' ) . '">
  </div>';

 $htmlCode .='<div class="field">
    <label for="login">' . __( 'Username', 'ppv-live-webcams' ) . ' <div class="ui button tiny basic compact icon" data-tooltip="' . __( 'Required', 'ppv-live-webcams' ) . '"><i class="warning circle icon"></i></div>
	</label>
    <input type="text" name="login" value="' . ( $login ?? '' ). '" placeholder="' . __( 'Username', 'ppv-live-webcams' ) . '">
  </div>

   <div class="field">
    <label for="password">' . __( 'Password', 'ppv-live-webcams' ) . ' <div class="ui button tiny basic compact icon" data-tooltip="' . __( 'Required', 'ppv-live-webcams' ) . '"><i class="warning circle icon"></i></div>
	</label>
    <input type="password" name="password" value="' . ( $password ?? '' ). '" placeholder="' . __( 'Password', 'ppv-live-webcams' ) . '">
  </div>

  <div class="field">
    <label for="password2">' . __( 'Password Verification', 'ppv-live-webcams' ) . ' <div class="ui button tiny basic compact icon" data-tooltip="' . __( 'Required', 'ppv-live-webcams' ) . '"><i class="warning circle icon"></i></div>
	</label>
    <input type="password" name="password2" value="' . ( $password2 ?? '' ) . '" placeholder="' . __( 'Password Verification', 'ppv-live-webcams' ) . '">
  </div>

  <div class="field">
    <label for="email">' . __( 'Email', 'ppv-live-webcams' ) . ' <div class="ui button tiny basic compact icon" data-tooltip="' . __( 'Required', 'ppv-live-webcams' ) . '"><i class="warning circle icon"></i></div>
	</label>
    <input type="text" name="email" value="' . ( $email ?? '' ) . '" placeholder="' . __( 'Email', 'ppv-live-webcams' ) . '">
  </div> ';

			if ( $atts['role'] == $options['rolePerformer'] || $atts['role'] == $options['roleStudio'] ) {
				if ( is_array( $options['recordFields'] ) ) {

					$htmlCode .= '<h4 class="ui dividing header"> <i class="address book icon"></i> ' . __( 'Administrative Records', 'ppv-live-webcams' ) . '</h4>';

					$htmlCode .= '<div class="field"><label>' . __( 'Administrative records are only accessible to site administrators for approval and administrative use.', 'ppv-live-webcams' ) . ' <a target="terms" href="' . $atts['privacy'] . '"> <i class="clipboard list icon"></i> ' . __( 'Privacy Policy', 'ppv-live-webcams' ) . '</a></label></div>';

					if (!$options['multiPerson'])
					foreach ( $options['recordFields'] as $field => $parameters ) {

						$fieldName = sanitize_title( trim( $field ) );

						// if ($parameters['instructions']) $htmlInstructions = ' data-tooltip="' . htmlspecialchars(stripslashes($parameters['instructions'])). '"'; else
						$htmlInstructions = '';

						$htmlIcons = '';
						if ($parameters['required'] ?? false) $htmlIcons .= ' <div class="ui button tiny basic compact icon" data-tooltip="' . __( 'Required', 'ppv-live-webcams' ) . '"><i class="warning circle icon"></i></div>';

						$htmlCode .= '<div class="field"' . $htmlInstructions . '><label for="' . $fieldName . '">' . $field . $htmlIcons . '</label>' ;

						$fieldValue = sanitize_textarea_field( $_POST[ $fieldName ] ?? '');

						switch ( $parameters['type'] ) {
							case 'file':
								$htmlCode .= '<INPUT class="ui button" type="file" name="' . $fieldName . '" id="' . $fieldName . '">';
								break;

							case 'text':
								$htmlCode .= '<INPUT type="text" size="72" name="' . $fieldName . '" id="' . $fieldName . '" value="' . $fieldValue . '">';
								break;

							case 'textarea':
								$htmlCode .= '<TEXTAREA type="text" rows="3" cols="70" name="' . $fieldName . '" id="' . $fieldName . '">' . $fieldValue . '</TEXTAREA>';
								break;

							case 'select':
								$htmlCode    .= '<SELECT name="' . $fieldName . '" id="' . $fieldName . '" class="ui dropdown v-select">';
								$fieldOptions = explode( '/', stripslashes( $parameters['options'] ) );

								$htmlCode .= '<OPTION value="" ' . ( ! $fieldValue ? 'selected' : '' ) . '> - </OPTION>';

								foreach ( $fieldOptions as $fieldOption ) {
									$htmlCode .= '<OPTION value="' . htmlspecialchars( $fieldOption ) . '" ' . ( $fieldOption == $fieldValue ? 'selected' : '' ) . '>' . htmlspecialchars( $fieldOption ) . '</OPTION>';
								}

								$htmlCode .= '</SELECT>';
								break;
						}
						if ( $parameters['instructions'] ?? false ) {
							$htmlCode .= '<div class="ui basic small label pointing">' . htmlspecialchars( stripslashes( $parameters['instructions'] ) ) . '</div>';
						}

						$htmlCode .= '</div>';

					}
					else
					{
						//multi person account records
						$htmlCode .= '<div id="typesSelect">';

						$htmlCode .= '<h5 class="ui dividing header"> <i class="group icon"></i>' . __( 'Account Persons', 'ppv-live-webcams' ) .  '</h4>';

						$htmlCode .= '<table class="">';

						$ajaxurl = '"' . admin_url( 'admin-ajax.php?action=vmls_recordsform&uid=0' ) . '"';
						$ajaxurl0 = '';

						$personsCode = '';

						$personTypes = explode (',', $options['personTypes']);
						foreach ($personTypes as $personType)
						{
							$type = trim($personType);
							$typeVar = sanitize_title_with_dashes($type);
							$htmlCode .= '
							 <tr><th class="center aligned">' . $type . '</th>
							 <td>
							 <div class="inline field increment">
								<input type="button" class="ui button decrease" value="-">
								<input id=' . $typeVar . ' class="ui number compact" type="number" value="0" min="0">
								<input type="button" class="ui button increase" value="+">
							   </div>
							</td>
							</tr>
							';

							$ajaxurl .= '+ "&' . $typeVar . '=" + jQuery("#' . $typeVar . '").val()';
							$personsCode .=   ' + Number(jQuery("#' . $typeVar . '").val())';

							if ($_POST['persons_' . $typeVar] ?? false) $ajaxurl0 .= '&' . $typeVar . '=' . intval($_POST['persons_' . $typeVar]);
						}

						$htmlCode .= '</table>';

						$htmlCode .= '<p>'. __( 'Specify persons that will be using this account and then use button below to load personal forms.', 'ppv-live-webcams' ) . '</p>';

						$htmlCode .= '<input id="fillButton" type="button" class="ui button" value="' . __( 'Fill Personal Records', 'ppv-live-webcams' ) . '">';

						$htmlCode .= '</div>';

						$htmlCode .= '<div id="videowhisperRecordsForm"></div>';


						$htmlCode .= '<script>
						jQuery(".increment .decrease").on("click", function(e) {
							e.preventDefault();
							var num = jQuery(this).siblings(".number");
							num.val(function(i, oldval) {
							  return Math.max(0,--oldval);
							});
						  });

							jQuery(".increment .increase").on("click", function(e) {
							  e.preventDefault();
							var num = jQuery(this).siblings(".number");
							num.val(function(i, oldval) {
							  return Math.max(0,++oldval);
							});
						  });

						  jQuery("#fillButton").on("click", function(e) {
							e.preventDefault();
							totalPersons = 0 ' . $personsCode . ';
							if (totalPersons<1)
							{
								alert("Please add at least one person type to the account!");
								return;
							}
							aurl = ' . $ajaxurl . ' + "&persons=" + totalPersons;
							loadRecordsForm("Loading Records Form", aurl);
							jQuery("#fillButton").hide();
							jQuery("#typesSelect").hide();
						});

	var loaderRF;

	function loadRecordsForm(message, aurl){
	if (message)
	if (message.length > 0)
	{
	  jQuery("#videowhisperRecordsForm").html(message);
	}

		if (loaderRF) loaderRF.abort();

		loaderRF = jQuery.ajax({
			url: aurl,
			data: "interface=recordsForm",
			success: function(data) {
				jQuery("#videowhisperRecordsForm").html(data);
			}
		});
	}

						  </script>';

						  //does not refill post data (ajax)
						  /*
						  if ($ajaxurl0)
						  {
							$ajaxurl0 = admin_url( 'admin-ajax.php?action=vmls_recordsform&uid=0' ) . $ajaxurl0;
							$htmlCode .= '<script>
							jQuery(document).ready(function(){
								loadRecordsForm("Restoring Submitted Records Form", "' . $ajaxurl0 . '");
							});
							jQuery("#fillButton").hide();
							jQuery("#typesSelect").hide();
							</script>';
						  }
					*/

		//the common account records part, in multi person registration
		$htmlCode .= '<h5 class="ui dividing header"> <i class="group icon"></i> ' . __( 'Common Account Records', 'ppv-live-webcams' ) . '</h4>';

		foreach ( $options['recordFields'] as $field => $parameters ) if (!$options['multiPerson'] || !isset($parameters['personal']) || !$parameters['personal']) {

			$fieldName = sanitize_title( trim( $field ) );

			// if ($parameters['instructions']) $htmlInstructions = ' data-tooltip="' . htmlspecialchars(stripslashes($parameters['instructions'])). '"'; else
			$htmlInstructions = '';

			$htmlIcons = '';
			if ($parameters['required'] ?? false) $htmlIcons .= ' <div class="ui button tiny basic compact icon" data-tooltip="' . __( 'Required', 'ppv-live-webcams' ) . '"><i class="warning circle icon"></i></div>';

			$htmlCode .= '<div class="field"' . $htmlInstructions . '><label for="' . $fieldName . '">' . $field . $htmlIcons . '</label>' ;

			$fieldValue = sanitize_textarea_field( $_POST[ $fieldName ] ?? '');

			switch ( $parameters['type'] ) {
				case 'file':
					$htmlCode .= '<INPUT class="ui button" type="file" name="' . $fieldName . '" id="' . $fieldName . '">';
					break;

				case 'text':
					$htmlCode .= '<INPUT type="text" size="72" name="' . $fieldName . '" id="' . $fieldName . '" value="' . $fieldValue . '">';
					break;

				case 'textarea':
					$htmlCode .= '<TEXTAREA type="text" rows="3" cols="70" name="' . $fieldName . '" id="' . $fieldName . '">' . $fieldValue . '</TEXTAREA>';
					break;

				case 'select':
					$htmlCode    .= '<SELECT name="' . $fieldName . '" id="' . $fieldName . '" class="ui dropdown v-select">';
					$fieldOptions = explode( '/', stripslashes( $parameters['options'] ) );

					$htmlCode .= '<OPTION value="" ' . ( ! $fieldValue ? 'selected' : '' ) . '> - </OPTION>';

					foreach ( $fieldOptions as $fieldOption ) {
						$htmlCode .= '<OPTION value="' . htmlspecialchars( $fieldOption ) . '" ' . ( $fieldOption == $fieldValue ? 'selected' : '' ) . '>' . htmlspecialchars( $fieldOption ) . '</OPTION>';
					}

					$htmlCode .= '</SELECT>';
					break;
			}
			if ( $parameters['instructions'] ?? false ) {
				$htmlCode .= '<div class="ui basic small label pointing">' . htmlspecialchars( stripslashes( $parameters['instructions'] ) ) . '</div>';
			}

			$htmlCode .= '</div>';
		}

						  //end multi person account records
					}
				}
			}

			if ( $options['match'] )
			{
				$htmlCode .= do_shortcode( '[videowhisper_match_form registration="1"]' );
			}



			$htmlCode .= '<div class="ui segment">
    <div class="field">
      <div class="ui toggle checkbox">
        <input type="checkbox" name="terms" ' . ( isset( $terms ) && $terms ? 'checked' : '' ) . ' tabindex="0" class="hidden">
        <label>' . __( 'I accept the Terms of Use', 'ppv-live-webcams' ) . ' </label>
      </div>
	  <div class="ui button tiny basic compact icon" data-tooltip="' . __( 'Required', 'ppv-live-webcams' ) . '"><i class="warning circle icon"></i></div>
      <a class="ui tiny button" target="terms" href="' . $atts['terms'] . '"> <i class="clipboard check icon"></i> ' . __( 'Review', 'ppv-live-webcams' ) . '</a>
    </div>
  </div>
  <div class="field">
  <input type="button" id="submitButton" name="submitButton" onclick="onSubmitClick()" class="ui submit button" value="' . __( 'Register', 'ppv-live-webcams' ) . '" />
   </div>
  ';

  		if ( !$options['registrationNoActivation'] || ( $options['registrationNoActivation'] == 'client' &&  $atts['role'] != $options['roleClient'] ) ) $htmlCode .='<div class="field">
   <label><i class="mail icon"></i> ' . __( 'After registration, you will receive a confirmation email with activation link.', 'ppv-live-webcams' ) . ' '.  __( 'Look for the email and check spam folder if necessary, because you will not be able to use account without activation. If you do not receive the email at current provider, register with a different email address.', 'ppv-live-webcams' ) . '</label>
   </div>';

			$htmlCode .= '</form> <script>
			jQuery(document).ready(function(){
jQuery(".ui.checkbox").checkbox();
});
			</script>';
		}
		return $htmlCode;
	}

	static function vmls_recordsform()
{
    $options = self::getOptions();

    ob_start(); // Start output buffering

    ?>
    <!-- Hidden Inputs for Person Types -->
    <?php
    $persons = [];
    $personTypes = explode(',', $options['personTypes']);
    foreach ($personTypes as $personType) {
        $type = trim($personType);
        $typeVar = sanitize_title_with_dashes($type);
        $persons[$type] = intval($_GET[$typeVar] ?? 0);
        ?>
        <input type="hidden" name="persons_<?php echo esc_attr($typeVar); ?>" id="persons_<?php echo esc_attr($typeVar); ?>" value="<?php echo esc_attr($persons[$type]); ?>">
        <?php
    }
    
    $pi = 1;
    $useColumns = $options['registrationColumns'] ?? false;
    if ($useColumns) {
        echo '<div class="ui two column grid">';
    }

    // Loop through person types
    foreach ($persons as $type => $number) {
        if ($number) {
            for ($pn = 1; $pn <= $number; $pn++) {
                if ($useColumns) {
                    echo '<div class="column">';
                }
                
                ?>
                <h5 class="ui dividing header">
                    <i class="user icon"></i> <?php echo esc_html($pi . '. ' . $type . ' ' . $pn . ': ' . __('Account Records', 'ppv-live-webcams')); ?>
                </h5>
                <input type="hidden" name="person_<?php echo esc_attr($pi); ?>" id="person_<?php echo esc_attr($pi); ?>" value="<?php echo esc_attr($type); ?>">

                <?php
                foreach ($options['recordFields'] as $field => $parameters) {
                    if (!isset($parameters['personal']) || !$parameters['personal']) {
                        continue;
                    }

                    $fieldName = sanitize_title(trim($field)) . '_' . $pi;
                    $fieldValue = sanitize_textarea_field($_POST[$fieldName] ?? '');
                    ?>

                    <div class="field"<?php echo !empty($parameters['instructions']) ? ' data-tooltip="' . esc_attr(stripslashes($parameters['instructions'])) . '"' : ''; ?>>
                        <label for="<?php echo esc_attr($fieldName); ?>">
                            <?php echo esc_html($field); ?> <?php echo ($parameters['required'] ?? false) ? '<div class="ui button tiny basic compact icon" data-tooltip="' . esc_attr(__('Required', 'ppv-live-webcams')) . '"><i class="warning circle icon"></i></div>' : ''; ?>
                        </label>

                        <?php
                        switch ($parameters['type']) {
                            case 'file':
                                ?>
                                <input class="ui button" type="file" name="<?php echo esc_attr($fieldName); ?>" id="<?php echo esc_attr($fieldName); ?>">
                                <?php
                                break;

                            case 'text':
                                ?>
                                <input type="text" size="72" name="<?php echo esc_attr($fieldName); ?>" id="<?php echo esc_attr($fieldName); ?>" value="<?php echo esc_attr($fieldValue); ?>">
                                <?php
                                break;

                            case 'textarea':
                                ?>
                                <textarea type="text" rows="3" cols="70" name="<?php echo esc_attr($fieldName); ?>" id="<?php echo esc_attr($fieldName); ?>"><?php echo esc_textarea($fieldValue); ?></textarea>
                                <?php
                                break;

                            case 'select':
                                ?>
                                <select name="<?php echo esc_attr($fieldName); ?>" id="<?php echo esc_attr($fieldName); ?>" class="ui dropdown v-select">
                                    <option value="" <?php selected(!$fieldValue); ?>> - </option>
                                    <?php
                                    $fieldOptions = explode('/', stripslashes($parameters['options']));
                                    foreach ($fieldOptions as $fieldOption) {
                                        ?>
                                        <option value="<?php echo esc_attr($fieldOption); ?>" <?php selected($fieldOption, $fieldValue); ?>><?php echo esc_html($fieldOption); ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <?php
                                break;
                        }

                        if (!empty($parameters['instructions'])) {
                            ?>
                            <div class="ui basic small label pointing"><?php echo esc_html(stripslashes($parameters['instructions'])); ?></div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }

                if ($useColumns) {
                    echo '</div>'; // Close column div
                }
                $pi++;
            }
        }
    }

    if ($useColumns) {
        echo '</div>'; // Close grid div
    }

    ?>
    <input type="hidden" name="persons" id="persons" value="<?php echo esc_attr($pi - 1); ?>">
    <?php

    exit();
}


	static function videowhisper_register_activate( $atts ) {

		$options = get_option( 'VWliveWebcamsOptions' );

		// shortocode attributes
		$atts = shortcode_atts(
			array(),
			$atts,
			'videowhisper_register_activate'
		);
		$atts = array_map('esc_attr', $atts);

		self::enqueueUI();

		if ( ! filter_input( INPUT_GET, 'key' ) ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' segment">The key parameter is missing. Make sure your activation link contains a part like key=[code].</div>';
		}

		$user_id = filter_input( INPUT_GET, 'user', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );

		$htmlCode = '';

		if ( $user_id ) {

			$user = get_userdata( $user_id );
			if (!$user) return '<div class="ui ' . $options['interfaceClass'] . ' segment">User not found.</div>';

			// get user meta activation hash field
			$code = get_user_meta( $user_id, 'videowhisper_activate', true );
			if ( $code == filter_input( INPUT_GET, 'key' ) ) {
				delete_user_meta( $user_id, 'videowhisper_activate' ); // no activation required
				$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'Your account was activated.', 'ppv-live-webcams' ) . '</div>';

				$htmlCode .= do_shortcode( '[videowhisper_login_form register="0"]' );

				//pending approval message
				if ( in_array( $options['rolePerformer'], $user->roles )  || in_array( $options['roleStudio'], $user->roles )  ) {
					if ($options['pendingMessage'] ?? false) $htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">' . wp_kses_post( $options['pendingMessage'] ) . '</div>';

					$email = $user->user_email ?? '';
					$email = filter_var( $email, FILTER_VALIDATE_EMAIL );
					if ($options['pendingSubject'] ?? false) if ($email) wp_mail( $email, $options['pendingSubject'], $options['pendingText'] . "\r\n" . wp_login_url() );
				}

			} elseif ( ! $code ) {
				$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">No activation code is required. This account is already activated.</div>';
			} else {
				$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">Wrong activation code. Make sure you copy and paste link in full without extra spaces or encoding!</div>';
			}
		} else {
			$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">User parameter missing. Make sure your activation link contains a part like &user=[number].</div>';
		}

		return $htmlCode;
	}



	static function wp_authenticate_user( $user, $password ) {
		$requires_activation = get_user_meta( $user->ID, 'videowhisper_activate' );
		if ( $requires_activation ) {
			return new \WP_Error( 'activation_failed', __( '<strong>ERROR</strong>: User is not activated. Use activation link from registration email!', 'ppv-live-webcams' ) );
		}
		return $user;
	}


	// DASHBOARD

	// ! Account Status and Records
	static function videowhisper_account_records( $atts ) {

		$options = self::getOptions();

		if ( ! is_user_logged_in() ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' header"> <i class="sign in icon"></i> ' . __( 'Login Required', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="1"]' );
		}

		$current_user = wp_get_current_user();
		$this_page    = self::getCurrentURL();

		// ! update account records

		$updateRecords = sanitize_text_field( $_GET['updateRecords'] ?? '' );

		$verified       = get_user_meta( $current_user->ID, 'vwVerified', true );
		$adminSuspended = get_user_meta( $current_user->ID, 'vwSuspended', true );

		$htmlCode = '';

		$htmlCode .= '<div id="performerStatus" class="ui ' . ( $options['interfaceClass'] ?? '' ) . ' segment"><h4 class="ui header"> <i class="user icon"></i> ' . $current_user->user_login . ' (#' . $current_user->ID . ')</h4>';

		if ( $options['performerStatus'] ) {
			$htmlCode .= ' <i class="address book icon"></i> ' . __( 'Account Status', 'ppv-live-webcams' ) . ': ' . ( $verified == '1' ? ( '<i class="check icon"></i>' . __( 'Verified', 'ppv-live-webcams' ) ) : ($verified == '2' ? ( '<i class="x icon"></i>' . __( 'Rejected', 'ppv-live-webcams' ) ) :  ( '<i class="x icon"></i>' . __( 'Not Verified', 'ppv-live-webcams' ) ) ) ) . ( $adminSuspended ? ' ' . __( 'Suspended by Admin', 'ppv-live-webcams' ) : '' );

			if ($verified == '2') $htmlCode .= '<div class="ui message red">' . wpautop( get_user_meta( $current_user->ID, 'vwVerifiedReason', true ) ) . '</div>';

			if ( $updateRecords != 'form' ) {
				$htmlCode .= ' <small><a class="ui compact tiny button" href=' . add_query_arg( array( 'updateRecords' => 'form' ), $this_page ) . '> <i class="edit icon"></i>' . __( 'Update Account Records', 'ppv-live-webcams' ) . '</a></small>';
			}
		};

		if ( $options['performerWallet'] ) {
			$balance = self::balance( $current_user->ID );
			if ( $balance ) {
				$htmlCode .= '<p> <i class="money bill icon"></i> ' . __( 'Your active balance', 'ppv-live-webcams' ) . ': ' . $balance . self::balances( $current_user->ID ) . '</p>';
			}
		}

		if ( $options['loginFrontend'] ) {
			$htmlCode .= '<a class="ui tiny button" href="' . wp_nonce_url( add_query_arg( 'logout', $current_user->ID, get_permalink( $options['p_videowhisper_login'] ) ), 'logout' . $current_user->ID, 'verify' ) . '"> <i class="sign out icon"></i>' . __( 'Log Out', 'ppv-live-webcams' ) . '</a> ';
		}

		$htmlCode .= '</div>';

		$error = '';

		switch ( $updateRecords ) {

			case 'form':
				$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' form segment"><form method="post" enctype="multipart/form-data" action="' . add_query_arg( array( 'updateRecords' => 'update' ), $this_page ) . '" name="accountForm" class="w-actionbox">';

				$htmlCode .= '<H4 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'Account Administrative Records', 'ppv-live-webcams' ) . ': ' . $current_user->user_login . '</H4> ' . __( 'These details are required by administrators for account approval and administrative operations.', 'ppv-live-webcams' );

				if ( ! is_array( $options['recordFields'] ) ) {
					$htmlCode .= '<p>' . __( 'No record fields defined by administrator!', 'ppv-live-webcams' ) . '</p>';
				} else {

					$persons = 1;
					if ($options['multiPerson'])
					{
						$persons = get_user_meta( $current_user->ID, 'persons', $persons );
						if (!$persons) $persons = 1;

						//also check wp nonce from url to add new person
						if ( isset($_GET['person_add']) && $_GET['person_add']=='1' )
						if (wp_verify_nonce( $_GET['verify'] ?? '', 'person' . $current_user->ID ))
						{
							$persons++;
							update_user_meta( $current_user->ID, 'persons', $persons );
						}else $htmlCode .= 'Add person nonce error!';

						if ( isset($_GET['person_remove']) && $_GET['person_remove']=='last' )
						if (wp_verify_nonce( $_GET['verify'] ?? '', 'person' . $current_user->ID ))
						{
							$persons--;
							update_user_meta( $current_user->ID, 'persons', $persons );
						}else $htmlCode .= 'Add person nonce error!';

						//insert link to add/remove person
						$htmlCode .= '<div class="ui message">';
						$htmlCode .=  __( 'Persons', 'ppv-live-webcams' ) . ': <div class="ui label">' . $persons . '</div>';
						$htmlCode .= '<a class="ui button tiny" href="' .  wp_nonce_url( add_query_arg( array( 'updateRecords' => 'form', 'person_add' => '1' ), $this_page ) , 'person' . $current_user->ID, 'verify' ). '"><i class="plus icon"></i>' . __( 'Add New Person', 'ppv-live-webcams' ) . '</a>';
						$htmlCode .= '</div>';

						for ($i = 1; $i <= $persons; $i++)
						{
							$htmlCode .=  '<h4 class="ui dividing header">' . __( 'Personal Records', 'ppv-live-webcams' ) . ' ' . $i . '</h4>';

							$type = get_user_meta( $current_user->ID, 'person_' . $i, true );

							//display a select to change type
							$htmlCode .= '<div class="field"><label>' . __( 'Type', 'ppv-live-webcams' ) . '</label>';
							$htmlCode .= '<SELECT name="' . 'person_' . $i . '" id="' . 'person_' . $i . '" class="ui dropdown v-select">';
							$personTypes = explode( ',', $options['personTypes'] );
							foreach ($personTypes as $personType)
								$htmlCode .= '<OPTION value="' . esc_attr( trim($personType) ) . '" ' . ( $type ==  trim($personType) ? 'selected' : '' ) . '>' . htmlspecialchars( trim($personType) ) . '</OPTION>';
							$htmlCode .= '</SELECT></div>';

							foreach ( $options['recordFields'] as $field => $parameters )
							if ( isset($parameters['personal']) && $parameters['personal'] )
							{

								$fieldName = sanitize_title( trim( $field ) ) . '_' . $i; //per person

								// if ($parameters['instructions']) $htmlInstructions = ' data-tooltip="' . htmlspecialchars(stripslashes($parameters['instructions'])). '"'; else
								$htmlInstructions = '';

								$htmlIcons = '';
								if ($parameters['required'] ?? false) $htmlIcons .= ' <div class="ui button tiny basic compact icon" data-tooltip="' . __( 'Required', 'ppv-live-webcams' ) . '"><i class="warning circle icon"></i></div>';

								$htmlCode .= '<div class="field"' . $htmlInstructions . '><label for="' . $fieldName . '">' . $field . $htmlIcons . '</label>';

								if ( isset( $_POST[ $fieldName ] ) ) {
									if ( $current_user->ID == $_POST['profileFor'] ) {
										$fieldValue = sanitize_text_field( $_POST[ $fieldName ] );
										update_user_meta( $current_user->ID, 'vwf_' . $fieldName, $fieldValue );
									}
								}

								$fieldValue = htmlspecialchars( get_user_meta( $current_user->ID, 'vwf_' . $fieldName, true ) );
								if ($parameters['required'] ?? false) if (!$fieldValue) $error .=  esc_html( $field ) . ': ' . __( 'Required field is empty!', 'ppv-live-webcams' ) . ' ' . $i . '/' . $persons . '<br>';

								switch ( $parameters['type'] ) {
									case 'file':
										$htmlCode .= '<INPUT class="ui button" type="file" name="' . $fieldName . '" id="' . $fieldName . '">';
										$filePath  = get_user_meta( $current_user->ID, 'vwf_' . $fieldName, true );
										if ( file_exists( $filePath ) ) {
											$htmlCode .= '<a target="_download" href="' . self::path2url( $filePath ) . '">' . __( 'Available', 'ppv-live-webcams' ) . '</a> ' . self::humanSize( filesize( $filePath ) ) . ' ' . pathinfo( $filePath, PATHINFO_EXTENSION ) . '<br>';
										}
										break;

									case 'text':
										$htmlCode .= '<INPUT type="text" size="72" name="' . $fieldName . '" id="' . $fieldName . '" value="' . $fieldValue . '">';
										break;

									case 'textarea':
										$htmlCode .= '<TEXTAREA type="text" rows="3" cols="70" name="' . $fieldName . '" id="' . $fieldName . '">' . $fieldValue . '</TEXTAREA>';
										break;

									case 'select':
										$htmlCode    .= '<SELECT name="' . $fieldName . '" id="' . $fieldName . '" class="ui dropdown v-select">';
										$fieldOptions = explode( '/', stripslashes( $parameters['options'] ) );

										$htmlCode .= '<OPTION value="" ' . ( ! $fieldValue ? 'selected' : '' ) . '> - </OPTION>';

										foreach ( $fieldOptions as $fieldOption ) {
											$htmlCode .= '<OPTION value="' . htmlspecialchars( $fieldOption ) . '" ' . ( $fieldOption == $fieldValue ? 'selected' : '' ) . '>' . htmlspecialchars( $fieldOption ) . '</OPTION>';
										}

										$htmlCode .= '</SELECT>';
										break;
								}
								$htmlCode .= '<small>' . htmlspecialchars( stripslashes( $parameters['instructions'] ?? '' ) ) . '</small>';

								$htmlCode .= '</div>';

							}

						}

						$htmlCode .= '<br><a class="ui button tiny" href="' .  wp_nonce_url( add_query_arg( array( 'updateRecords' => 'form', 'person_remove' => 'last' ), $this_page ) , 'person' . $current_user->ID, 'verify' ). '"><i class="minus icon"></i>' . __( 'Remove Last Person', 'ppv-live-webcams' ) . '</a>';
					}

					$htmlCode .=  '<h4 class="ui header dividing">' . __( 'Account Records', 'ppv-live-webcams' ) . '</h4>';

					foreach ( $options['recordFields'] as $field => $parameters )
					if ( !$options['multiPerson'] || !isset($parameters['personal']) || !$parameters['personal'] )
					{

						$fieldName = sanitize_title( trim( $field ) );

						// if ($parameters['instructions']) $htmlInstructions = ' data-tooltip="' . htmlspecialchars(stripslashes($parameters['instructions'])). '"'; else
						$htmlInstructions = '';

						$htmlIcons = '';
						if ($parameters['required'] ?? false) $htmlIcons .= ' <div class="ui button tiny basic compact icon" data-tooltip="' . __( 'Required', 'ppv-live-webcams' ) . '"><i class="warning circle icon"></i></div>';

						$htmlCode .= '<div class="field"' . $htmlInstructions . '><label for="' . $fieldName . '">' . $field . $htmlIcons . '</label>';

						if ( isset( $_POST[ $fieldName ] ) ) {
							if ( $current_user->ID == $_POST['profileFor'] ) {
								$fieldValue = sanitize_text_field( $_POST[ $fieldName ] );
								update_user_meta( $current_user->ID, 'vwf_' . $fieldName, $fieldValue );
							}
						}

						$fieldMeta = get_user_meta( $current_user->ID, 'vwf_' . $fieldName, true );
						if (is_array($fieldMeta)) 
						{
							$error .= esc_html( $field ) . ': ' . __( 'Array field is not supported!', 'ppv-live-webcams' ) . '<br>';
							$fieldMeta = implode('|', $fieldMeta);
						}

						$fieldValue = htmlspecialchars( $fieldMeta );
						if ($parameters['required'] ?? false) if (!$fieldValue) $error .=  esc_html( $field ) . ': ' . __( 'Required field is empty!', 'ppv-live-webcams' ) . '<br>';

						switch ( $parameters['type'] ) {
							case 'file':
								$htmlCode .= '<INPUT class="ui button" type="file" name="' . $fieldName . '" id="' . $fieldName . '">';
								$filePath  = get_user_meta( $current_user->ID, 'vwf_' . $fieldName, true );
								if ( file_exists( $filePath ) ) {
									$htmlCode .= '<a target="_download" href="' . self::path2url( $filePath ) . '">' . __( 'Available', 'ppv-live-webcams' ) . '</a> ' . self::humanSize( filesize( $filePath ) ) . ' ' . pathinfo( $filePath, PATHINFO_EXTENSION ) . '<br>';
								}
								break;

							case 'text':
								$htmlCode .= '<INPUT type="text" size="72" name="' . $fieldName . '" id="' . $fieldName . '" value="' . $fieldValue . '">';
								break;

							case 'textarea':
								$htmlCode .= '<TEXTAREA type="text" rows="3" cols="70" name="' . $fieldName . '" id="' . $fieldName . '">' . $fieldValue . '</TEXTAREA>';
								break;

							case 'select':
								$htmlCode    .= '<SELECT name="' . $fieldName . '" id="' . $fieldName . '" class="ui dropdown v-select">';
								$fieldOptions = explode( '/', stripslashes( $parameters['options'] ) );

								$htmlCode .= '<OPTION value="" ' . ( ! $fieldValue ? 'selected' : '' ) . '> - </OPTION>';

								foreach ( $fieldOptions as $fieldOption ) {
									$htmlCode .= '<OPTION value="' . htmlspecialchars( $fieldOption ) . '" ' . ( $fieldOption == $fieldValue ? 'selected' : '' ) . '>' . htmlspecialchars( $fieldOption ) . '</OPTION>';
								}

								$htmlCode .= '</SELECT>';
								break;
						}
						$htmlCode .= '<small>' . htmlspecialchars( stripslashes( $parameters['instructions'] ?? '' ) ) . '</small>';

						$htmlCode .= '</div>';

					}
				}
				$htmlCode .= '<input type="hidden" name="profileFor" id="profileFor" value="' . $current_user->ID . '">
	<BR><input class="ui primary button" type="submit" name="updateRecords" id="updateRecords" value="' . __( 'Save', 'ppv-live-webcams' ) . '" />
	</form></div>';

				break;

			case 'update':
				// var_dump($_POST);

				$persons = 1;
				if ($options['multiPerson'])
				{
					$persons = get_user_meta( $current_user->ID, 'persons', $persons );
					if (!$persons) $persons = 1;

					//update person type
					for ($i = 1; $i <= $persons; $i++)
					{
						$type = sanitize_text_field( $_POST[ 'person_' . $i ] ?? '' );
						update_user_meta( $current_user->ID, 'person_' . $i, $type );

					}

				}

				if ( is_array( $options['recordFields'] ) ) {
					if ( $current_user->ID == $_POST['profileFor'] ) {
						$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' form segment">';

						// var_dump($options['recordFields']);
						// var_dump($_FILES);

						foreach ( $options['recordFields'] as $field => $parameters )
						{

							if ($options['multiPerson']) $piterations = $persons;
							else $piterations = 1;

							for ($pi = 1; $pi <= $piterations; $pi++)
							{

								if ($options['multiPerson'] && isset($parameters['personal']) && $parameters['personal']) $fieldName = sanitize_title( trim( $field ) ) . '_' . $pi;
								else $fieldName = sanitize_title( trim( $field ) );

							switch ( $parameters['type'] ) {

								case 'file':
									$uploadComplete = false;
									$uploadExists = false;

									if ( $filename = $_FILES[ $fieldName ]['tmp_name'] ) {
										$deny = false;

										$ext     = strtolower( pathinfo( $_FILES[ $fieldName ]['name'], PATHINFO_EXTENSION ) );
										$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'pdf' );

										if ( ! in_array( $ext, $allowed ) ) {
											$htmlCode .= '<br>' . esc_html( $field ) . ' - ' . esc_html( $filename ) . ': Unsupported file extension! Please use one of these formats: ' . implode( ', ', $allowed );
											$deny      = true;
										}

										$maxUpload = intval ($options['maxUpload'] ?? 6000) * 1000;
										if ( $_FILES[ $fieldName ]['size'] > $maxUpload ) {
											$htmlCode .= '<br>' . esc_html( $field ) . ': File is too big! Please use a file under ' . $maxUpload . 'kb.';
											$deny      = true;
										}

										if ( ! $deny ) {
											$destination = $options['uploadsPath'] . '/user_' .  $current_user->user_nicename . '_' .  $current_user->ID  . '/';
											if ( ! file_exists( $destination ) ) {
												mkdir( $destination );
											}

											$newfilename =  $fieldName . '_' . time() . '.' . $ext ;
											$newpath     = $destination . $newfilename;

											$errorUp = self::handle_upload( $_FILES[ $fieldName ], $newpath ); // handle trough wp_handle_upload()
											if ( $errorUp ) {
												$htmlCode .= '<br>' . 'Error uploading ' . esc_html( $filename . ':' . $errorUp );
											}

											if ( ! file_exists( $newpath ) ) {
												$htmlCode .= '<br>' . esc_html( $field ) . ': Error uploading ' . esc_html( $newpath );
											} else {
												$oldpath = get_user_meta( $current_user->ID, 'vwf_' . $fieldName, true );
												if ( $options['recordClean'] ) {
													if ( file_exists( $oldpath ) ) {
														unlink( $oldpath );
													}
												}

												update_user_meta( $current_user->ID, 'vwf_' . $fieldName, $newpath );
												$uploadComplete = true;
											}

										}
									}

									if (!$uploadComplete) if (get_user_meta($current_user->ID, 'vwf_' . $fieldName, true)) $uploadExists = true;

									if ($parameters['required'] ?? false) if (!$uploadComplete && !$uploadExists) $error .= esc_html( $field ) . ': ' . __( 'Required file missing!', 'ppv-live-webcams' ). ' ' . $pi . '/' . $persons . '<br>' ;
									break;

								default:
									if ( isset( $_POST[ $fieldName ] ) ) {
										$fieldValue =  sanitize_textarea_field( $_POST[ $fieldName ] );
										update_user_meta( $current_user->ID, 'vwf_' . $fieldName, $fieldValue );
										// $htmlCode .= '<br>' . $field . ': '.  sanitize_text_field($_POST[$fieldName]);

										if ($parameters['required'] ?? false) if (!$fieldValue) $error .=  esc_html( $field ) . ': ' . __( 'Required field missing!', 'ppv-live-webcams' ) . ' ' . $pi . '/' . $persons . '<br>' ;
									}
							} //end type

						 } //end iterations

						} // end field

						update_user_meta( $current_user->ID, 'vwUpdated', time() );
						self::usersPendingApproval();

						$htmlCode .= '<br>' . __( 'Updated administrative account records.', 'ppv-live-webcams' ) . '</div>';

						$verified       = get_user_meta( $current_user->ID, 'vwVerified', true );

						if (!$error) if ($options['pendingMessage'] ?? false) $htmlCode .= '<p>' . wp_kses_post( $options['pendingMessage'] ) . '</p>';

						// end records

					} else {
						$htmlCode .= 'Incorrect profileFor.';
					}
				}
				break;
		}

		if ($error)
		{
		  $htmlCode .= '<div class="ui warning message">
	<div class="header">' . __( 'Administrative Records Incomplete', 'ppv-live-webcams' ) . ':</div>
	<ul class="list">
	  ' . $error . '
	</ul><p>' . __( 'Account can not be verified without required records.', 'ppv-live-webcams' ) . '</p></div>';

		 //unverify user if required info is missing
		 update_user_meta( $current_user->ID, 'vwVerified', 0 );
		}




		return $htmlCode;
	}


	// start studio dashboard

	// ! Studio Dashboard
	static function videowhisper_webcams_studio( $atts ) {
		$options = get_option( 'VWliveWebcamsOptions' );

		self::enqueueUI();

		if ( ! is_user_logged_in() ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' header"> <i class="sign in icon"></i> ' . __( 'Login to manage studio!', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="1" role="' . $options['roleStudio'] . '"]' );
		}

		// if (!is_user_logged_in()) return __('Login to manage studio!','ppv-live-webcams') . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __('Login', 'ppv-live-webcams') . '</a>  <a class="ui button secondary qbutton" href="' . ( ($options['registerFrontend'] && $options['p_videowhisper_register_studio']) ? get_permalink($options['p_videowhisper_register_studio']) : wp_registration_url() ) . '">' . __('Register', 'ppv-live-webcams') . '</a>';

		// shortocode attributes
		$atts = shortcode_atts(
			array(
				'include_css' => '1',
			),
			$atts,
			'videowhisper_webcams_studio'
		);
		$atts = array_map('esc_attr', $atts);

		$current_user = wp_get_current_user();
		$htmlCode = '';
		$performersList = 0;

		if ( ! $current_user->ID ) {
			return __( 'Login is required to access this section!', 'ppv-live-webcams' );
		}

		// access keys
		$userkeys   = $current_user->roles;
		$userkeys[] = $current_user->user_login;
		$userkeys[] = $current_user->user_nicename;
		$userkeys[] = $current_user->ID;
		$userkeys[] = $current_user->user_email;

		$roleS = implode( ',', $current_user->roles );
		if ( ! self::any_in_array( array( $options['roleStudio'], 'administrator', 'super admin' ), $current_user->roles ) ) {
			return __( 'User role does not allow managing studio!', 'ppv-live-webcams' ) . ' (' . $roleS . ')';
		}

		$this_page = self::getCurrentURL();
		$tab       = sanitize_text_field( $_GET['tab'] ?? '' );

		if ( ! $tab ) {
			$htmlCode .= html_entity_decode( stripslashes( $options['dashboardMessageStudio'] ) );
		}

		$htmlCode .= do_shortcode( '[videowhisper_account_records]' );
		if ( ! self::userEnabled( $current_user, $options, 'Studio' ) ) {
			return $htmlCode . __( 'Your account is not currently enabled. Update your account records and wait for site admins to approve your account.', 'ppv-live-webcams' );
		}

		// updating records
		if ( $_GET['updateRecords'] ?? false ) {
			if ( $_GET['updateRecords'] != 'update' ) {
				return $htmlCode;
			}
		}

			// ! Dashboard Tabs
			/*
			  $htmlCode .= '<div class="vwtabs">';

			//! performers tab
			$checked = '';
			if ( $tab == 'performers' || ! $tab) $checked = 'checked';
			if ($checked) $checked1 = true;

			$htmlCode .= '<div class="vwtab">
		<input type="radio" id="tab-performers" name="tab-group-1" '.$checked.'>
		<label class="vwlabel" for="tab-performers">' . __('Performers', 'ppv-live-webcams') . '</label>

		<div class="vwpanel">
		<div class="vwcontent">';
			$htmlCode .= '<h3 class="ui header">' . __('Performers', 'ppv-live-webcams') . '</h3> ' . __('Manage studio performer accounts.', 'ppv-live-webcams') ;
		*/
			$active = '';
		$header     = array();
		$content    = array();

		if ( $tab == 'performers' || ! $tab ) {
			$active = 'performers';
		}

		$header['performers'] = __( 'Performers', 'ppv-live-webcams' );

		$contents = '<h3 class="ui header">' . __( 'Performers', 'ppv-live-webcams' ) . '</h3> ' . __( 'Studio manages multiple performer accounts.', 'ppv-live-webcams' );

		if ( $tab == 'performers' ) {

			if ( $collectID = intval( $_GET['collect'] ?? 0 ) ) {
				$studioID = get_user_meta( $collectID, 'studioID', true );

				if ( $studioID == $current_user->ID || $collectID == $current_user->ID ) {
					$balance = self::balance( $collectID, false, $options );

					if ( $balance > 0 ) {

						$performer = get_userdata( $collectID );
						$studio    = get_userdata( $studioID );

						$collectLast  = floatval( get_user_meta( $collectID, 'studioCollectLast', true ) );
						$collectTotal = floatval( get_user_meta( $collectID, 'studioCollectTotal', true ) );

						self::transaction( 'collect_performer', $collectID, -$balance, 'Studio ' . $studio->user_login . 'collected balance.', null, null, $options );
						self::transaction( 'collect_studio', $studioID, $balance, 'Collected balance from ' . $performer->user_login . '.', null, null, $options );

						update_user_meta( $collectID, 'studioCollectLast', $balance );
						update_user_meta( $collectID, 'studioCollectTotal', $balance + $collectTotal );

						$contents .= '<div class="ui segment">' . __( 'Collected user ballance.', 'ppv-live-webcams' ) . '<br>' . $balance . ' : ' . $performer->user_login . ' - ' . $studio->user_login . '<div>';
					} else {
						$contents .= '<BR>' . __( 'No balance to collect!', 'ppv-live-webcams' );
					}
				} else {
					$contents .= '<BR>' . __( 'You are not allowed to collect for this user!', 'ppv-live-webcams' );
				}
			}

			// Transactions Log
			if ( $transactionsID = intval( $_GET['transactions'] ?? 0 ) ) {

				$studioID = get_user_meta( $transactionsID, 'studioID', true );

				if ( $studioID == $current_user->ID || $transactionsID == $current_user->ID ) {

					$user_info = get_userdata( $transactionsID );

					if ( $options['wallet'] == 'MyCred' ) {
						$contents .= '<h4>' . __( 'Performer Transactions', 'ppv-live-webcams' ) . ': ' . $user_info->user_login . '</h4>';
						if ( shortcode_exists( 'mycred_history' ) ) {
							$contents .= do_shortcode( '[mycred_history user_id=' . $transactionsID . ']' );
						}

						$contents .= '<style type="text/css">
.pagination, .pagination > LI {
display: inline !important;
padding: 5px;
margin: 2px;
background-color: #EEE;
}
</style>';
					}

					if ( $options['wallet'] == 'WooWallet' ) {
						$contents .= '<h4>' . __( 'Transactions', 'ppv-live-webcams' ) . '</h4>';
						if ( shortcode_exists( 'woo-wallet' ) ) {
							$contents .= do_shortcode( '[woo-wallet user_id=' . $transactionsID . ']' );
						}
					}
				} else {
					$contents .= '<BR>' . __( 'You are not allowed to view transactions for this user!', 'ppv-live-webcams' );
				}
			}

			switch ( $_GET['view'] ) {
				case 'form':
					$contents .= '<h4>' . __( 'Create Performer Account', 'ppv-live-webcams' ) . '</h4>';

					$action = add_query_arg(
						array(
							'tab'  => 'performers',
							'view' => 'insert',
						),
						$this_page
					);

					$clarificationsMsg = __( 'After setting up performer account you will also receive password to communicate to performer for quick access. Account password should be changed by performer. Warning: Make sure you fill details correctly. Only administrator can delete incorrect accounts.', 'ppv-live-webcams' );
					$saveMsg           = __( 'Save', 'ppv-live-webcams' );

					$contents .= <<<HTMLCODE
<form class="ui form" method="post" enctype="multipart/form-data" action="$action" name="adminForm" class="w-actionbox">
<table class="ui table striped" width="500px">
<tr><td>Username</td><td><input size="32" type="text" name="performer_username" id="performer_username" value=""></td></tr>
<tr><td>Email</td><td><input size="32" type="text" name="performer_email" id="performer_email" value=""></td></tr>
<tr><td></td><td><input class="ui button" type="submit" name="save" id="save" value="$saveMsg" /></td></tr>
</table>
$clarificationsMsg
</form>
HTMLCODE;
					break;

				case 'insert':
					$contents .= '<h4>' . __( 'Adding Performer Account', 'ppv-live-webcams' ) . '</h4>';

					$error = '';

					$performer_username = sanitize_text_field( $_POST['performer_username'] );
					$performer_email    = sanitize_text_field( $_POST['performer_email'] );

					if ( username_exists( $performer_username ) ) {
						$error = __( 'Username already in use!', 'ppv-live-webcams' );
					}
					if ( email_exists( $performer_email ) ) {
						$error = __( 'Email already in use!', 'ppv-live-webcams' );
					}

					$args            = array(
						'blog_id'      => $GLOBALS['blog_id'],
						'meta_key'     => 'studioID',
						'meta_value'   => $current_user->ID,
						'meta_compare' => '=',
					);
					$performers      = get_users( $args );
					$performersCount = count( $performers );
					if ( $options['studioPerformers'] && $performersCount >= $options['studioPerformers'] ) {
						$error = __( 'Performers limit reached!', 'ppv-live-webcams' );
					}

					if ( $error ) {
						$contents .= __( 'Could not create performer account: ', 'ppv-live-webcams' ) . $error;
					} else {
						$random_password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
						$user_id         = wp_create_user( $performer_username, $random_password, $performer_email );
						if ( $user_id > 0 ) {
							$contents .= __( 'Performer account was created', 'ppv-live-webcams' ) . ': <BR>Username: ' . $performer_username . '<BR>' . __( 'Password', 'ppv-live-webcams' ) . ': ' . $random_password . '<BR>' . __( 'Email', 'ppv-live-webcams' ) . ': ' . $performer_email;

							// assign to studio
							update_user_meta( $user_id, 'studioID', $current_user->ID );
							update_user_meta( $user_id, 'studioLogin', $current_user->user_login );
							update_user_meta( $user_id, 'studioPassword', $random_password );
							update_user_meta( $user_id, 'studioDisabled', 0 );

							// create performer role if missing
							$role = $options['rolePerformer'];
							$oRole = get_role( $role );
							if ( ! $oRole ) {
								add_role( $role, ucwords( $role ), array( 'read' => true ) );
								$oRole = get_role( $role );
							}

							// set as performer
							wp_update_user(
								array(
									'ID'   => $user_id,
									'role' => $options['rolePerformer'],
								)
							);

							update_user_meta( $user_id, 'videowhisper_ip_register', self::get_ip_address() ); // registration ip
							update_user_meta( $user_id, 'created_by', 'ppv-live-webcams/studio' ); // for troubleshooting registration origin
							update_user_meta( $user_id, 'videowhisper_role', $options['rolePerformer'] ); // registration role

							// also create a webcam listing
							$newPerformer = get_userdata( $user_id );
							$name         = self::performerName( $newPerformer, $options );

							$webcamID = self::webcamPost( $name, $name, $user_id, $current_user->ID );
							update_user_meta( $user_id, 'currentWebcam', $webcamID );

						}
					}

					break;

				default:
					$performersList = 1;
			}

			//disable/enable
			if ( $user_id = intval( $_GET['performer_disable'] ?? 0 ) ) {
				$studioID = get_user_meta( $user_id, 'studioID', true );

				if ( $studioID == $current_user->ID ) {
					update_user_meta( $user_id, 'studioDisabled', 1 );
				} else {
					$contents .= __( 'That performer is not assigned to this studio!', 'ppv-live-webcams' );
				}
			}

				if ( $user_id = intval( $_GET['performer_enable'] ?? 0 ) ) {
				$studioID = get_user_meta( $user_id, 'studioID', true );

				if ( $studioID == $current_user->ID ) {
					delete_user_meta( $user_id, 'studioDisabled' );
				} else {
					$contents .= __( 'That performer is not assigned to this studio!', 'ppv-live-webcams' );
				}
			}



		} else {
			$performersList = 1; // no special action
		}

		if ( $performersList ?? false ) {

			$args = array(
				'blog_id'      => $GLOBALS['blog_id'],
				'meta_key'     => 'studioID',
				'meta_value'   => $current_user->ID,
				'meta_compare' => '=',
				'orderby'      => 'registered',
				'order'        => 'DESC',
				'fields'       => 'all',
			);

			$performers = get_users( $args );

			$performersCount = count( $performers );

			$contents .= '<h4>' . __( 'Performers List', 'ppv-live-webcams' ) . ' (' . $performersCount . '/' . ( $options['studioPerformers'] ? $options['studioPerformers'] : '&infin;' ) . ')</h4>';

			if ( $performersCount ) {
				$contents .= '<table class="ui table celled striped sortable"><thead><tr><th class="sorted ascending">' . __( 'Login', 'ppv-live-webcams' ) . '</th><th>' . __( 'Email', 'ppv-live-webcams' ) . '</th><th>' . __( 'Balance', 'ppv-live-webcams' ) . ' (' . $options['currency'] . ')</th><th>' . __( 'Collected', 'ppv-live-webcams' ) . '<br>' . __( 'Last', 'ppv-live-webcams' ) . ' / ' . __( 'Total', 'ppv-live-webcams' ) . '</th><th>' . __( 'Status', 'ppv-live-webcams' ) . '</th><th>Info</th><th>' . __( 'Current Room', 'ppv-live-webcams' ) . '</th></tr></thead>';

				foreach ( $performers as $performer ) {

				$userLabel = '<i class="icon user"></i>' . $performer->user_login;

				if ( function_exists( 'bp_members_get_user_url' ) ) $userLabel = '<a href="' . bp_members_get_user_url( $performer->id ). '"><i class="icon user"></i>' . $performer->user_login . '</a>';

					$contents .= '<tr><td><b>' . $userLabel . '</b> </td><td><small>' . $performer->user_email .'</small>';

					$contents .= '</td><td>';
					$contents .= '<a class="ui compact label" href="' . add_query_arg(
						array(
							'tab'          => 'performers',
							'transactions' => $performer->ID,
						),
						$this_page
					) . '">' . self::balance( $performer->ID ) . '</a> ';
					$contents .= '<a class="ui compact tiny button" href="' . add_query_arg(
						array(
							'tab'     => 'performers',
							'collect' => $performer->ID,
						),
						$this_page
					) . '">' . __( 'Collect', 'ppv-live-webcams' ) . '</a>';
					$contents .= '</td><td>';

					$collectLast  = floatval( get_user_meta( $performer->ID, 'studioCollectLast', true ) );
					$collectTotal = floatval( get_user_meta( $performer->ID, 'studioCollectTotal', true ) );

					$contents .= $collectLast . '/' . $collectTotal;
					$contents .= '</td><td>';

					$disabled = get_user_meta( $performer->ID, 'studioDisabled', true );

					if ( ! $disabled ) {
						$contents .= '<a class="ui compact tiny button" href="' . add_query_arg(
							array(
								'tab'      => 'performers',
								'performer_disable'  => $performer->ID,
							),
							$this_page
						) . '">' . __( 'Enabled', 'ppv-live-webcams' ) . '</a>';
					} else {
						$contents .= '<a class="ui compact tiny button grey" href="' . add_query_arg(
							array(
								'tab'      => 'performers',
								'performer_enable'  => $performer->ID,
							),
							$this_page
						) . '">' . __( 'Disabled', 'ppv-live-webcams' ) . '</a>';
					}

					$contents .= '</td><td> <div class="ui label mini basic">';

					$password = get_user_meta( $performer->ID, 'studioPassword', true );
					if ( $password ) {
						$contents .= __( 'Original Password', 'ppv-live-webcams' ) . ': ' . $password . ' ';
					}

					$contents .= '</div> </td><td>';

					$selectWebcam = get_user_meta( $performer->ID, 'currentWebcam', true );
					if ( $selectWebcam ) {
						$contents .= '<a href="' . get_permalink( $selectWebcam ) . '"><i class="icon users"></i>' . self::post_title( $selectWebcam ) . '</a> ';
						$contents .= get_post_meta( $selectWebcam, 'groupMode', true );

					}
					$contents .= '</td></tr>';
				}

				$contents .= '</table>';

			wp_enqueue_script( 'semantic-tablesort', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/semantic/tablesort.js', array( 'fomantic-ui' ) );

				$contents .= '<PRE style="display: none"><SCRIPT>
	jQuery(document).ready( function(){
		jQuery("table.ui").tablesort();
    });</SCRIPT></PRE>';


			} else {
				$contents .= __( 'Studio has no performer accounts, yet.', 'ppv-live-webcams' );
			}

			if ( ! $options['studioPerformers'] || $performersCount < $options['studioPerformers'] ) {
				$contents .= '<p><a href="' . add_query_arg(
					array(
						'tab'  => 'performers',
						'view' => 'form',
					),
					$this_page
				) . '" class="ui button">' . __( 'Add Performer', 'ppv-live-webcams' ) . '</a> ' . __( 'Create a new performer account.', 'ppv-live-webcams' ) . '</p>';
			}
		} else {
			$contents .= '<p><a class="ui button" href="' . add_query_arg( array( 'tab' => 'performers' ), $this_page ) . '">' . __( 'View  Performers List', 'ppv-live-webcams' ) . '</a></p>';
		}

		$content['performers'] = $contents;

		/*
			$htmlCode .= '
			</div>
		</div>
		</div>';

		*/
		// ! webcams tab

		/*
			$checked = '';
			if ( $tab == 'webcams') $checked = 'checked';
			if ($checked) $checked1 = true;

			$htmlCode .= '<div class="vwtab">
		<input type="radio" id="tab-webcams" name="tab-group-1" '.$checked.'>
		<label class="vwlabel" for="tab-webcams">Webcams</label>

		<div class="vwpanel">
		<div class="vwcontent">';
			$htmlCode .= '<h3 class="ui header">' . __('Webcams', 'ppv-live-webcams') . '</h3> ' . __('Manage webcam listings for studio performers.', 'ppv-live-webcams')  ;
		*/

		$header['webcams'] = __( 'Rooms', 'ppv-live-webcams' );

		$contents = '<h3 class="ui header">' . __( 'Rooms', 'ppv-live-webcams' ) . '</h3> ' . __( 'Manage room listings for studio performers.', 'ppv-live-webcams' );

		if ( $tab == 'webcams' ) {

			$active = 'webcams';

			switch ( $_GET['view'] ) {
				case 'add':
					$contents .= '<h4>' . __( 'Create Webcam Room Listing', 'ppv-live-webcams' ) . '</h4>';

					// performers list
					if ( ! $performers ) {
						$args = array(
							'blog_id'      => $GLOBALS['blog_id'],
							'meta_key'     => 'studioID',
							'meta_value'   => $current_user->ID,
							'meta_compare' => '=',
							'orderby'      => 'registered',
							'order'        => 'DESC',
							'fields'       => 'all',
						);

						$performers = get_users( $args );
					}

					if ( count( $performers ) ) {
						foreach ( $performers as $performer ) {
							$performersCode .= '<input type="checkbox" name="selectedPerformers[]" value="' . $performer->ID . '">' . $performer->user_login . '<br>';
						}
					} else {
						$performersCode .= 'Studio has no performer accounts to select from.';
					}

					$action = add_query_arg(
						array(
							'tab'  => 'webcams',
							'view' => 'insert',
						),
						$this_page
					);

					$infoMsg = __( 'Warning: Make sure webcam listing name is correct. Only administrator can delete webcams.', 'ppv-live-webcams' );
					$saveMsg = __( 'Save', 'ppv-live-webcams' );

					$contents .= <<<HTMLCODE
<form class="ui form" method="post" enctype="multipart/form-data" action="$action" name="adminForm" class="w-actionbox">
<table class="ui table celled">
<tr><td>Webcam Listing Name</td><td><input size="32" type="text" name="webcam_name" id="webcam_name" value=""></td></tr>
<tr><td>Performers</td><td align="left">$performersCode</td></tr>
<tr><td></td><td><input class="ui button" type="submit" name="save" id="save" value="$saveMsg" /></td></tr>
</table>
$infoMsg
</form>
HTMLCODE;
					break;

				case 'insert':
					$contents .= '<h4>Adding Webcam Listing</h4>';

					$error = '';

					$webcam_name        = sanitize_text_field( $_POST['webcam_name'] );
					$selectedPerformers = isset( $_POST['selectedPerformers'] ) ? (array) $_POST['selectedPerformers'] : array(); // array elements sanitized on use

					global $wpdb;
					$pid = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s", $webcam_name, sanitize_text_field( $options['custom_post'] ) ) );

					if ( ! $pid ) {
						$post = array(
							'post_name'   => sanitize_title_with_dashes( $webcam_name ),
							'post_title'  => $webcam_name,
							'post_author' => $current_user->ID,
							'post_type'   => sanitize_text_field( $options['custom_post'] ),
							'post_status' => 'publish',
						);

						$pid = wp_insert_post( $post );

						// assign to studio
						update_post_meta( $pid, 'studioID', $current_user->ID );

						// assign to performers
						if ( count( $selectedPerformers ) ) {
							foreach ( $selectedPerformers as $performer ) {
								add_post_meta( $pid, 'performerID', intval( $performer ), false );
							}
						}
					} else {
						$error = __( 'Room listing with that name already exists.', 'ppv-live-webcams' );
					}

					if ( $error ) {
						$contents .= 'Could not create webcam listing: ' . $error;
					} else {
						$contents .= __( 'Room listing was successfully created!', 'ppv-live-webcams' );
					}

					break;

				default:
					$webcamsList = 1;
			}

			// webcam actions in list view

		} else {
			$webcamsList = 1; // no special action
		}

		if ( $webcamsList ) {

			$args = array(
				'post_type'    => sanitize_text_field( $options['custom_post'] ),
				'meta_key'     => 'studioID',
				'meta_value'   => $current_user->ID,
				'meta_compare' => '=',
				'orderby'      => 'date',
				'order'        => 'DESC',
			);

			$webcams = get_posts( $args );

			$webcamsCount = count( $webcams );

			$contents .= '<h4>Webcams List (' . $webcamsCount . '/' . ( esc_html( $options['studioWebcams'] ) ? esc_html( $options['studioWebcams'] ) : '&infin;' ) . ')</h4>';

			if ( $webcamsCount ) {
				$contents .= '<table class="ui table celled striped"><thead><tr><th>Webcam</th><th>Status</th><th>Performer(s)</th></tr></thead>';

				foreach ( $webcams as $webcam ) {

					$contents .= '<tr><td><b>' . esc_html( $webcam->post_title ) . '</b><td>' . esc_html( $webcam->post_status ) . '</td>';

					$contents    .= ' </td><td>';
					$performerIDs = get_post_meta( $webcam->ID, 'performerID', false );
					if ( $performerIDs ) {
						if ( count( $performerIDs ) ) {
							foreach ( $performerIDs as $performerID ) {
								$performer = get_userdata( intval( $performerID ) );
								$contents .= esc_html( $performer->user_login ) . ' ';
							}
						}
					}

						$contents .= '</td>';
					$contents     .= '</tr>';
				}

				$contents .= '</table>';
			} else {
				$contents .= 'Studio has no webcam listings, yet.';
			}

			if ( ! $options['studioWebcams'] || $webcamsCount < $options['studioWebcams'] ) {
				$contents .= '<p><a href="' . add_query_arg(
					array(
						'tab'  => 'webcams',
						'view' => 'add',
					),
					$this_page
				) . '" 	class="ui button">Add Room</a> Create a new webcam room listing.</p>';
			}
		} else {
			$contents .= '<p><a href="' . add_query_arg( array( 'tab' => 'webcams' ), $this_page ) . '" class="ui button secondary">View Your Room List</a></p>';
		}

		/*
			$htmlCode .= '
			</div>
		</div>
		</div>';

			$htmlCode .= '
		</div>';
		*/
		$content['webcams'] = $contents;

		// wallets in Studio Dashboard



		// MicroPayments Tab
		if ( $options['performerWallet'] ) {
			if ( $options['wallet'] == 'MicroPayments' || $options['walletMulti'] >= 1 ) {
				if ( shortcode_exists( 'videowhisper_transactions' ) ) {

					if ( isset($_GET['orderby']) || isset($_GET['paged']) ) $active = 'micropayments';

					$header['micropayments'] = __( 'MicroPayments Wallet', 'ppv-live-webcams' );

					$content['micropayments'] = '<h3 class="ui header">' . __( 'MicroPayments Wallet', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[videowhisper_transactions user_id="' . $current_user->ID . '"]' );
				}
			}
		}


		// WooWallet Tab
		if ( $options['performerWallet'] ) {
			if ( $options['wallet'] == 'WooWallet' || $options['walletMulti'] >= 1 ) {
				if ( shortcode_exists( 'woo-wallet' ) ) {

					if ( ($_GET['wallet_action'] ?? false ) || get_query_var( 'wallet_action' ) ) {
						$active = 'woowallet';
					}
					$header['woowallet'] = __( 'WooCommerce Wallet', 'ppv-live-webcams' );

					$content['woowallet'] = '<h3 class="ui header">' . __( 'TeraWallet WooCommerce', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[woo-wallet]' );
				}
			}
		}


			// MyCred Transactions
		if ( $options['performerWallet'] ) {
			if ( $options['wallet'] == 'MyCred' || $options['walletMulti'] >= 1 ) {
				if ( shortcode_exists( 'mycred_history' ) ) {

					if ( isset($_GET['pagenum']) || get_query_var( 'pagenum' ) ) {
						$active = 'mycred';
					}
					$header['mycred'] = __( 'MyCred', 'ppv-live-webcams' );

					$content['mycred']  = __( 'MyCred Wallet', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[mycred_history user_id="current"]' );
					$content['mycred'] .= '<style type="text/css">
.pagination, .pagination > LI {
display: inline !important;
padding: 5px;
margin: 2px;
background-color: #EEE;
}
</style>';
				}
			}
		}

				$htmlCode .= self::sectionsLayout( $header, $content, $active, $options['performerLayout'], '' );

		if ( $atts['include_css'] ) {
			$htmlCode .= '<STYLE>' . html_entity_decode( stripslashes( $options['dashboardCSS'] ) ) . '</STYLE>';
		}

			return $htmlCode;
	}


//ajax function to retrieve location options
	static function vmls_location()
	{
		
		$for = sanitize_text_field( $_POST['for'] );
		
		$fieldName = sanitize_text_field( $_POST['field_name'] );

		$continent0 = sanitize_text_field( $_POST['continent0'] );
		$country0 = sanitize_text_field( $_POST['country0'] );
		$region0 = sanitize_text_field( $_POST['region0'] );

		$continent1 = sanitize_text_field( $_POST['continent'] );
		$country1   = sanitize_text_field( $_POST['country'] );
		$region1    = sanitize_text_field( $_POST['region'] );

		if (!$continent1) $continent1 = $continent0;
		if (!$country1) $country1 = $country0;
		if (!$region1) $region1 = $region0;
		
			// output clean
			if (ob_get_length()) {
				ob_clean();
			}

			$options = self::getOptions();


		$response = array();

				//load location info
				$locationsPath = $options['uploadsPath'] . '/_locations/';
				$locationsPathDefaut = dirname(plugin_dir_path( __FILE__ )). '/data/';
	
				$fileLocations = $locationsPath . 'locations.json';

				if ( ! file_exists( $fileLocations ) )$fileLocations = $locationsPathDefaut . 'locations.json';

				if ( file_exists( $fileLocations ) ) $locations = json_decode( file_get_contents( $fileLocations ), true );
				if ( ! is_array( $locations ) ) $locations = array();

				$continents = array();
				$countries = array();
				$regions = array();


				foreach ( $locations as $continent_code => $continent )
				{
					$continents[ $continent_code ] = $continent['continent_name'];
					
					if ($continent1) if ($continent['continent_name'] == $continent1) foreach ( $continent['countries'] as $country_code => $country )
					{
						$countries[ $country_code ] = $country['country_name'];

						if ( isset( $country['regions'] ) ) {
							if ($country1) if ($country['country_name'] == $country1) foreach ( $country['regions'] as $region_code => $region ) {
								$regions[ $region_code ] = $region['region_name'];
							}
						}
					}
				}
				
				$contentCode = '';

				if (count($countries)) 
				{
					echo '<div class="ui fluid search selection dropdown v-select vwAjaxDropdown">
					<input type="hidden" name="' . esc_attr($fieldName) . '_country" id="' . esc_attr($fieldName) . '_country" value="' . esc_attr($country1). '" onchange="updateLocationField(\'' . esc_attr($fieldName) . '\', \'continent\',\'' . esc_attr($continent1) . '\',\'' . esc_attr($country1) . '\',\'' . esc_attr($region1) . '\' )" >
					<i class="dropdown icon"></i>
					<div class="default text">' .__( 'Country', 'ppv-live-webcams' ) . '</div>
					<div class="menu">';

					foreach ( $countries as $key => $country ) {
						echo '<div class="item" data-value="' . esc_attr( htmlspecialchars( stripslashes( $country ) ) ) . '"><i class="' . esc_attr( self::language2flag($key)) . ' flag"></i> ' . esc_html( htmlspecialchars( stripslashes( $country ) ) ) . '</div>';
					}

					echo '</div></div>';
				}

				if (count($regions))
				{
					echo '<div class="ui fluid search selection dropdown v-select vwAjaxDropdown">
					<input type="hidden" name="' . esc_attr($fieldName) . '_region" id="' . esc_attr($fieldName) . '_region" value="' . esc_attr($region1). '"  >
					<i class="dropdown icon"></i>
					<div class="default text">' .__( 'Region', 'ppv-live-webcams' ) . '</div>
					<div class="menu">';

					foreach ( $regions as $key => $region ) {
						echo '<div class="item" data-value="' . esc_attr( htmlspecialchars( stripslashes( $region ) ) ) . '">' . esc_html( htmlspecialchars( stripslashes( $region ) ) ) . '</div>';
					}

					echo '</div></div>';
				}

				echo '<script>
				jQuery(document).ready(function(){
					jQuery(".vwAjaxDropdown").dropdown();
				});
				</script>';
			
				exit;
	}

	// end studio dashboard
	// start performer dashboard

	static function videowhisper_webcams_performer( $atts ) {

		$options = get_option( 'VWliveWebcamsOptions' );

		if ( ! is_user_logged_in() ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' header"> <i class="sign in icon"></i> ' . __( 'Login to access dashboard!', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="1" role="' . $options['rolePerformer'] . '"]' );
		}

		// '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __('Login', 'ppv-live-webcams') . '</a>  <a class="ui button secondary qbutton" href="' . ( ($options['registerFrontend'] && $options['p_videowhisper_register_performer']) ? get_permalink($options['p_videowhisper_register_performer']) : wp_registration_url() ) . '">' . __('Register', 'ppv-live-webcams') . '</a>';

		// shortocode attributes
		$atts = shortcode_atts(
			array(
				'include_css' => '1',
			),
			$atts,
			'videowhisper_webcams_performer'
		);
		$atts = array_map('esc_attr', $atts);

		$current_user = wp_get_current_user();

		if ( ! $current_user->ID ) {
			return __( 'Login is required to access this section!', 'ppv-live-webcams' );
		}

		$uid = $current_user->ID;

		// access keys
		$userkeys   = $current_user->roles;
		$userkeys[] = $current_user->user_login;
		$userkeys[] = $current_user->user_nicename;
		$userkeys[] = $current_user->ID;
		$userkeys[] = $current_user->user_email;

		$roleS = implode( ',', $current_user->roles );
		if ( ! self::any_in_array( self::getRolesPerformer( $options ), $current_user->roles ) ) {
			return __( 'User role does not allow publishing webcams!', 'ppv-live-webcams' ) . ' (' . $roleS . ')';
		}

		// process user's sessions to show updated balance
		self::billSessions( $current_user->ID );

		// semantic ui : performer dashboard
		self::enqueueUI();
		$htmlCode = '';

		if ( $options['dashboardMessage'] ) {
			$htmlCode .= '<div id="performerDashboardMessage" class="ui ' . $options['interfaceClass'] . ' segment">' . html_entity_decode( stripslashes( $options['dashboardMessage'] ) ) . '</div>';
		}

		$this_page = self::getCurrentURL();

		$htmlCode .= do_shortcode( '[videowhisper_account_records]' );

		if ( ! self::userEnabled( $current_user, $options, 'Performer' ) ) {
			return $htmlCode . '<div id="performerDashboardMessage" class="ui yellow segment">' . __( 'Your account is not currently enabled. Update your account records and wait for site admins to approve your account.', 'ppv-live-webcams' ) . '</div>';
		}

		// disabled by studio: no access
		$disabled = get_user_meta( $current_user->ID, 'studioDisabled', true );
		if ( $disabled ) {
			return $htmlCode . '<div id="performerDashboardMessage" class="ui red segment">' . __( 'Studio disabled your account: dashboard access is forbidden. Contact studio or site administrator!', 'ppv-live-webcams' ) . '</div>';
		}

		// updating records
		if ( $_GET['updateRecords'] ?? false) {
			if ( $_GET['updateRecords'] != 'update' ) {
				return $htmlCode;
			}
		}

			// display dashboard info

			// ! webcams manage: shows when managing

			// ! select webcam listing

		$postID = 0;

		if ( $selectWebcam = intval( $_GET['selectWebcam'] ?? 0 ) ) {

			// verify if valid (owner or assigned)
			$webcamSelected = get_post( $selectWebcam );
			$warnCode       = '';

			if ( ! $webcamSelected ) {
				$selectWebcam = '';
				$warnCode     .= 'This room post does not exist: #' . $selectWebcam;
			} else {
				$performerIDs = get_post_meta( $webcamSelected->ID, 'performerID', false );
				if ( ! $performerIDs ) {
					$performerIDs = array();
				}

				if ( $selectWebcam && $webcamSelected->post_author != $current_user->ID && ! in_array( $current_user->ID, $performerIDs ) ) {
					$selectWebcam = '';
					$warnCode     .= 'You are not performer in room: #' . $selectWebcam; }

				if ( $selectWebcam && $webcamSelected->post_type != $options['custom_post'] ) {
					$selectWebcam = '';
					$warnCode     .= 'This post is not a valid room type: #' . $selectWebcam . ': ' . $webcamSelected->post_type . '/' . $options['custom_post'] ; }
			}

			if ( $selectWebcam ) {
				update_user_meta( $current_user->ID, 'currentWebcam', $selectWebcam );
				$postID = $selectWebcam;
			} else {
				$htmlCode .= '<BR>' . __( 'Selected webcam listing is invalid.', 'ppv-live-webcams' ) . ' ' . $warnCode;
			}
		}

		$selectWebcamList = 0;
		if ( ! $postID ) {
			// get or setup webcam post for this user
			if ( ! $options['performerWebcams'] ) {
				$postID = self::webcamPost(); // default cam
			} else {
				$postID = get_user_meta( $current_user->ID, 'currentWebcam', true );
				if ( ! $postID ) {
					$selectWebcamList = 1;
				}
			}
		}

		// ! manage webcams
		if ( $selectWebcamList || ( $_GET['changeWebcam'] ?? '' ) ) {
			$htmlCode .= '<div id="performerWebcamsManage" class="ui ' . $options['interfaceClass'] . ' segment">';

			$postID = ''; // select or manage

			switch ( $_GET['view'] ?? '' ) {
				case 'add':
					$htmlCode .= '<h4>' . __( 'Create Webcam Listing', 'ppv-live-webcams' ) . '</h4>';

					$action = add_query_arg(
						array(
							'changeWebcam' => 'manage',
							'view'         => 'insert',
						),
						$this_page
					);

					$performersCode = '<input size="48" type="text" name="performersCSV" id="performersCSV" value=""><br>' . __( 'Partner performers that can also go live in this room: Comma separated list of performer usernames or emails.', 'ppv-live-webcams' );

					$msg1 = __( 'Room Listing Name', 'ppv-live-webcams' );
					$msg2 = __( 'Performers', 'ppv-live-webcams' );
					$msg3 = __( 'Save', 'ppv-live-webcams' );
					$msg4 = __( 'Warning: Make sure webcam listing name is correct. Only administrator can delete webcams.', 'ppv-live-webcams' );

					$htmlCode .= <<<HTMLCODE
<form class="ui form" method="post" enctype="multipart/form-data" action="$action" name="adminForm">
<table class="g-input" width="500px">
<tr><td>$msg1</td><td><input size="32" type="text" name="webcam_name" id="webcam_name" value=""></td></tr>
<tr><td>$msg2</td><td align="left">$performersCode</td></tr>
<tr><td></td><td><input class="ui button videowhisperButton" type="submit" name="save" id="save" value="$msg3" /></td></tr>
</table>
$msg4
</form>
HTMLCODE;
					break;

				case 'insert':
					$htmlCode .= '<h4>' . __( 'Adding Webcam Listing', 'ppv-live-webcams' ) . '</h4>';

					$error = '';

					$webcam_name = sanitize_text_field( $_POST['webcam_name'] );

					global $wpdb;
					$pid = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = '%s' AND post_type='" . $options['custom_post'] . "'", $webcam_name ) );

					if ( ! $pid ) {
						$post = array(
							'post_name'   => sanitize_title_with_dashes( $webcam_name ),
							'post_title'  => $webcam_name,
							'post_author' => $current_user->ID,
							'post_type'   => $options['custom_post'],
							'post_status' => 'publish',
						);

						$pid = wp_insert_post( $post );

						// assign to studio
						update_post_meta( $pid, 'studioID', $current_user->ID );

						// assign to performers
						if ( $performersCSV = sanitize_text_field( $_POST['performersCSV'] ) ) {
							$selectedPerformers = array();

							$performers = explode( ',', $performersCSV );
							if ( count( $performers ) ) {
								foreach ( $performers as $performerV ) {
									$performerID = '';
									$value       = trim( $performerV );
									if ( is_email( $value ) ) {
										if ( $performerID = get_user_by( 'email', $value ) ) {
											$selectedPerformers[] = $performerID;
										}
									} elseif ( $performerID = get_user_by( 'login', $value ) ) {
										$selectedPerformers[] = $performerID;
									}

									if ( $value && ! $performerID ) {
										$htmlCode .= '"' . $value . '" ' . __( 'Warning: Performer not found: use a valid username (login) or email!', 'ppv-live-webcams' ) . '<BR>';
									}
								}
							}

							if ( count( $selectedPerformers ) ) {
								foreach ( $selectedPerformers as $performerID ) {
									add_post_meta( $pid, 'performerID', (int) $performerID, false );
								}
							}
						}
					} else {
						$error = __( 'Room listing with that name already exists.', 'ppv-live-webcams' );
					}

					if ( $error ) {
						$htmlCode .= __( 'Could not create webcam listing:', 'ppv-live-webcams' ) . $error;
					} else {
						$htmlCode .= __( 'Room listing was successfully created!', 'ppv-live-webcams' );
					}

					break;

				default:
					$webcamsList = 1;
			}

			if ( $webcamsList ?? false ) {

				$args = array(
					'post_type'      => $options['custom_post'],
					'author'         => $current_user->ID,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'posts_per_page' => -1,
				);

				$webcamsOwned = get_posts( $args );

				$args = array(
					'post_type'      => $options['custom_post'],
					'meta_key'       => 'performerID',
					'meta_value'     => $current_user->ID,
					'meta_compare'   => '=',
					'orderby'        => 'date',
					'order'          => 'DESC',
					'posts_per_page' => -1,

				);

				$webcamsAssigned = get_posts( $args );

				$webcams = array_merge( $webcamsOwned, $webcamsAssigned );
				$webcams = array_unique( $webcams, SORT_REGULAR );

				$webcamsCount = count( $webcams );

				$htmlCode .= '<h4>' . __( 'Select Room Listing', 'ppv-live-webcams' ) . ' (' . $webcamsCount . '/' . ( $options['performerWebcams'] ? $options['performerWebcams'] : '&infin;' ) . ')</h4>';
				$htmlCode .= __( 'Select a room to enter, manage, operate.', 'ppv-live-webcams' );

				$webcamCreated = 0;
				if ( $webcamsCount ) {
					$htmlCode     .= '<br>' . __( 'Rooms (webcam venue listings) you created or were granted access to broadcast:', 'ppv-live-webcams' );
					$currentWebcam = get_user_meta( $current_user->ID, 'currentWebcam', true );

					foreach ( $webcams as $webcam ) {
						$htmlCode .= '<br><a class="ui button secondary" href="' . add_query_arg( array( 'selectWebcam' => $webcam->ID ), $this_page ) . '"><i class="ui icon users"></i><b>' . $webcam->post_title . '</b></a> ';

						$htmlCode .=  '<label class="ui label basic">' .  get_post_meta( $webcam->ID, 'groupMode', true ) . '</label>';

						$performerIDs = get_post_meta( $webcam->ID, 'performerID', false );
						if ( $performerIDs ) {
							if ( count( $performerIDs ) ) {
								foreach ( $performerIDs as $performerID ) {
									$performer = get_userdata( $performerID );
									$htmlCode .= ' <label class="ui label"><i class="ui icon users"></i>' . $performer->user_login . '</label>';
								}
							}
						}

						if ( $currentWebcam == $webcam->ID ) {
							echo ' - ' . __( 'Selected', 'ppv-live-webcams' );
						}
					}
				} else {
					$htmlCode .= '<br>' . __( 'You do not have any active webcam room: one will be setup for you and selected.', 'ppv-live-webcams' ) . '<br>';
					$postID    = self::webcamPost();

					if ( $postID ) {
						$post = get_post( $postID );
					}
					if ( ! $post ) {
						$postID = 0;
					}

					if ( ! $postID ) {
						$htmlCode .= 'Error: Could not setup a webcam post!';
					} else {
						$htmlCode     .= '<b>' . $post->post_title . '</b> #' . $postID;
						$webcamCreated = 1;
					}
				}

				if ( ! $options['performerWebcams'] || $webcamsCount < $options['performerWebcams'] ) {
					if ( ! $webcamCreated ) {
						$htmlCode .= '<p><a href="' . add_query_arg(
							array(
								'changeWebcam' => 'manage',
								'view'         => 'add',
							),
							$this_page
						) . '" 	class="ui button">' . __( 'Add Room', 'ppv-live-webcams' ) . '</a> ' . __( 'Create a new webcam room listing.', 'ppv-live-webcams' ) . '</p>';
					}
				}
			} else {
				$htmlCode .= '<p><a class="ui button" href="' . add_query_arg( array( 'changeWebcam' => 'select' ), $this_page ) . '">' . __( 'View Your Room List', 'ppv-live-webcams' ) . '</a></p>';
			}

			$htmlCode .= '</div>';
		}
		// ! end webcams manage

		if ( ! $postID ) {
			if ( $atts['include_css'] ) {
				$htmlCode .= '<STYLE>' . html_entity_decode( stripslashes( $options['dashboardCSS'] ) ) . '</STYLE>';
			}
			return $htmlCode;
		}

		if ( self::roomSuspended($postID, $options) )
		{
			$htmlCode .= '<div class="ui red segment"><h4>' . __('Room Suspended', 'ppv-live-webcams') . '</h4>' . wp_kses_post( $options['suspendMessage'] ) . '</div>';
			return $htmlCode;
		}
		// !room updates

		// FEATURES
		// ! edit room
		if ( $_POST['setup'] ?? false ) {

			if ( isset($webcamPost) ) {
				$webcamPost = get_post( $postID );
			}

				//set room subscription tier
				if ( isset( $_POST['subscriptionTier'] ) )
				{
					$subscriptionTier = intval( $_POST['subscriptionTier'] );
					update_post_meta( $postID, 'vw_subscription_tier', $subscriptionTier );
				}

			// costPerMinute
			if ( self::inList( $userkeys, $options['costPerMinute'] ) ) {
				$costPerMinute = round( $_POST['costPerMinute'], 2 );

				// check range
				if ( $costPerMinute > 0 ) {
					if ( $options['ppvPPMmin'] > $costPerMinute || $options['ppvPPMmax'] < $costPerMinute ) {
						$costPerMinute = 0;
						$htmlCode     .= '<div class="warning">' . __( 'Custom CPM out of range: removed.', 'ppv-live-webcams' ) . ' (' . $options['ppvPPMmin'] . '- ' . $options['ppvPPMmax'] . ')</div>';
					}
				}

				if ( $costPerMinute ) {
					update_post_meta( $postID, 'vw_costPerMinute', $costPerMinute );
				} else {
					delete_post_meta( $postID, 'vw_costPerMinute' );
				}

				// audio
				$costPerMinuteAudio = round( $_POST['costPerMinuteAudio'], 2 );

				// check range
				if ( $costPerMinuteAudio > 0 ) {
					if ( $options['ppvPPMmin'] > $costPerMinuteAudio || $options['ppvPPMmax'] < $costPerMinuteAudio ) {
						$costPerMinuteAudio = 0;
						$htmlCode          .= '<div class="warning">' . __( 'Custom CPM out of range: removed.', 'ppv-live-webcams' ) . ' (' . $options['ppvPPMmin'] . '- ' . $options['ppvPPMmax'] . ')</div>';
					}
				}

				if ( $costPerMinuteAudio ) {
					update_post_meta( $postID, 'vw_costPerMinuteaudio', $costPerMinuteAudio );
				} else {
					delete_post_meta( $postID, 'vw_costPerMinuteaudio' );
				}

				// text
				$costPerMinuteText = round( $_POST['costPerMinuteText'], 2 );

				// check range
				if ( $costPerMinuteText > 0 ) {
					if ( $options['ppvPPMmin'] > $costPerMinuteText || $options['ppvPPMmax'] < $costPerMinuteText ) {
						$costPerMinuteText = 0;
						$htmlCode         .= '<div class="warning">' . __( 'Custom CPM out of range: removed.', 'ppv-live-webcams' ) . ' (' . $options['ppvPPMmin'] . '- ' . $options['ppvPPMmax'] . ')</div>';
					}
				}

				if ( $costPerMinuteText ) {
					update_post_meta( $postID, 'vw_costPerMinutetext', $costPerMinuteText );
				} else {
					delete_post_meta( $postID, 'vw_costPerMinutetext' );
				}
			}

			// costPerMinute
			if ( self::inList( $userkeys, $options['costPerMinuteGroup'] ) ) {
				$costPerMinute = round( $_POST['costPerMinuteGroup'], 2 );

				// check range
				if ( $costPerMinute > 0 ) {
					if ( $options['ppvPPMmin'] > $costPerMinute || $options['ppvPPMmax'] < $costPerMinute ) {
						$costPerMinute = 0;
						$htmlCode     .= '<div class="warning">' . __( 'Custom group CPM out of range: removed.', 'ppv-live-webcams' ) . '</div>';
					}
				}

				if ( $costPerMinute ) {
					update_post_meta( $postID, 'vw_costPerMinuteGroup', $costPerMinute );
				} else {
					delete_post_meta( $postID, 'vw_costPerMinuteGroup' );
				}
			}

			// slots 2way
			if ( isset($options['slots2way']) && self::inList( $userkeys, $options['slots2way'] ) ) {
				$slots2way = round( $_POST['slots2way'] );

				if ( $slots2way ) {
					update_post_meta( $postID, 'vw_slots2way', $slots2way );
				} else {
					delete_post_meta( $postID, 'vw_slots2way' );
				}
			}

			// $htmlCode .= 'Upload?'. $_FILES['uploadPicture']['tmp_name'];

			// uploadPicture
			if ( self::inList( $userkeys, $options['uploadPicture'] ) ) {
				if ( $filename = $_FILES['uploadPicture1']['tmp_name'] ) {
					$htmlCode .= 'Processing picture upload... ';

					$ext     = strtolower( pathinfo( $_FILES['uploadPicture1']['name'], PATHINFO_EXTENSION ) );
					$allowed = array( 'jpg', 'jpeg', 'png', 'gif' );
					if ( ! in_array( $ext, $allowed ) ) {
						return 'Unsupported file extension!';
					}

					list($width, $height) = getimagesize( $filename );

					if ( $width && $height ) {

						// delete previous image(s)
						$htmlCode .= self::delete_associated_media( $postID ); // deletes files, call before copy

						if (!isset($webcamPost)) $webcamPost = get_post( $postID );
						$room_name = sanitize_text_field( $webcamPost->post_title );


						$dir = sanitize_text_field( $options['uploadsPath'] );
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}
						$dir .= '/_pictures';
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}
						// save file
						$destination = "$dir/$room_name.$ext";
						// copy source

						$errorUp = self::handle_upload( $_FILES['uploadPicture1'], $destination ); // handle trough wp_handle_upload()
						if ( $errorUp ) {
							$htmlCode .= '<br>' . 'Error uploading ' . esc_html( $filename . ':' . $errorUp );
						}

						if ( file_exists( $destination ) ) {

							if ( $postID ) {
								update_post_meta( $postID, 'picture', $destination );
								update_post_meta( $postID, 'hasPicture', 1 );
							}

							// update post image

							if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
								require ABSPATH . 'wp-admin/includes/image.php';
							}

							$wp_filetype = wp_check_filetype( basename( $destination ), null );

							$attachment = array(
								'guid'           => $destination,
								'post_mime_type' => $wp_filetype['type'],
								'post_title'     => $room_name,
								'post_content'   => '',
								'post_status'    => 'inherit',
							);

							$attach_id = wp_insert_attachment( $attachment, $destination, $postID );
							set_post_thumbnail( $postID, $attach_id );

							// update post imaga data
							$attach_data = wp_generate_attachment_metadata( $attach_id, $destination );
							wp_update_attachment_metadata( $attach_id, $attach_data );

							if ( ! file_exists( $destination ) ) {
								$htmlCode .= __( 'ERROR: Missing ', 'ppv-live-webcams' ) . $destination;
							}

							// create thumb
							$imageData = file_get_contents( $destination ); // local image file

							if ( $imageData ) {
								// $htmlCode .= 'Generating thumb... ';
								$thumbWidth  = $options['thumbWidth'];
								$thumbHeight = $options['thumbHeight'];

								$src = imagecreatefromstring( $imageData );

								unset( $imageData );// prevent memory leaks

								$tmp = imagecreatetruecolor( $thumbWidth, $thumbHeight );
								if ( $tmp ) {
									$dir = $options['uploadsPath'];
									if ( ! file_exists( $dir ) ) {
										mkdir( $dir );
									}

									$dir .= '/_thumbs';
									if ( ! file_exists( $dir ) ) {
										mkdir( $dir );
									}

									$thumbFilename = "$dir/$room_name-picture.jpg";
									imagecopyresampled( $tmp, $src, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height );

									imagejpeg( $tmp, $thumbFilename, 95 );

									// prevent memory leaks in case of loops (due to filters on other plugins)
									if ( $src ) {
										imagedestroy( $src );
									}
									if ( $tmp ) {
										imagedestroy( $tmp );
									}

									if ( file_exists( $thumbFilename ) ) {
										// detect tiny images without info
										// if (filesize($thumbFilename)>5000) $picType = 1;
										// else $picType = 2;

										// update post meta
										if ( $postID ) {
											update_post_meta( $postID, 'thumbPicture', $thumbFilename );
											update_post_meta( $postID, 'hasThumb', 1 );
										}

										// $htmlCode .= ' Updating picture... ' . $thumbFilename;

										$htmlCode .= __( 'Picture Updated.', 'ppv-live-webcams' );
									} else {
										$htmlCode .= __( 'ERROR: Cound not create JPG thumb file ', 'ppv-live-webcams' ) . $thumbFilename;
									}
								} else {
									$htmlCode .= __( 'ERROR: Cound not create temporary image!', 'ppv-live-webcams' );
								}
							} else {
								$htmlCode .= __( 'ERROR: Failed loading image data ', 'ppv-live-webcams' ) . $destination;
							}
						} else {
							$htmlCode .= __( 'ERROR: Upload copy failed. File does not exist ', 'ppv-live-webcams' ) . $destination;
						}
					} else {
						$htmlCode .= __( 'ERROR: Could not retrieve image size for ', 'ppv-live-webcams' ) . $filename;
					}
				}

				$showImage = sanitize_text_field( $_POST['showImage'] );
				update_post_meta( $postID, 'showImage', $showImage );

			}

			if ( $options['lovense'] && $options['lovenseToy'] == 'manual' )
			{
				$lovenseToy = intval( $_POST['lovenseToy'] );
				update_post_meta( $postID, 'lovenseToy', $lovenseToy );
			}


			// category
			if ( self::inList( $userkeys, $options['roomCategory'] ) ) {
				$category = (int) $_POST['newcategory'];
				wp_set_post_categories( $postID, array( $category ) );
			}

			// roomDescription
			if ( self::inList( $userkeys, $options['roomDescription'] ) ) {

				$roomDescription = sanitize_text_field( $_POST['roomDescription'] );
				wp_update_post(
					array(
						'ID'           => $postID,
						'post_content' => $roomDescription,
					)
				);
			}

			// roomLabel
			if ( self::inList( $userkeys, $options['roomLabel'] ) ) {
				$roomLabel = sanitize_text_field( $_POST['roomLabel'] );
				update_post_meta( $postID, 'vw_roomLabel', $roomLabel );
			}

			// roomBrief
			if ( self::inList( $userkeys, $options['roomBrief'] ) ) {
				$roomBrief = sanitize_text_field( $_POST['roomBrief'] );
				update_post_meta( $postID, 'vw_roomBrief', $roomBrief );
			}

			// roomTags
			if ( self::inList( $userkeys, $options['roomTags'] ) ) {
				$roomTags = sanitize_text_field( $_POST['roomTags'] );
				wp_set_post_tags( $postID, $roomTags, false );
			}

			// banCountries
			if ( self::inList( $userkeys, $options['banCountries'] ) ) {
				$banCountries = sanitize_text_field( $_POST['banCountries'] );

				$banLocations = str_getcsv( $banCountries, ',' );

				delete_post_meta( $postID, 'vw_banCountries' );

				if ( is_array( $banLocations ) ) {
					if ( ! empty( $banLocations ) ) {
						foreach ( $banLocations as $value ) {
							if ( trim( $value ) ) {
											add_post_meta( $postID, 'vw_banCountries', trim( $value ) );
							}
						}
					}
				}
			}
/*
			// private2way
			if ( self::inList( $userkeys, $options['private2way'] ) ) {
				$private2way = sanitize_text_field( $_POST['private2way'] );
				update_post_meta( $postID, 'vw_private2way', $private2way );
			}
*/
			// accessList
			if ( self::inList( $userkeys, $options['accessList'] ) ) {
				$accessList = sanitize_text_field( $_POST['accessList'] );
				update_post_meta( $postID, 'vw_accessList', $accessList );
			}

			// accessPrice
			if ( isset($options['accessPrice']) ) if ( self::inList( $userkeys, $options['accessPrice'] ) ) {

				$accessPrice = round( $_POST['accessPrice'] ?? 0, 2 );
				update_post_meta( $postID, 'vw_accessPrice', $accessPrice );

				if ( class_exists( 'VWpaidMembership' ) ) {
					// var_dump($webcamPost);

					if (!isset($webcamPost)) $webcamPost = get_post( $postID );
					\VWpaidMembership::contentEdit( $webcamPost, array( 'price' => $accessPrice ) );

				} else {
					// mycred
					$mCa = array(
						'status'       => 'enabled',
						'price'        => $accessPrice,
						'button_label' => 'Buy Access Now', // default button label
						'expire'       => 0, // default no expire
					);

					if ( $accessPrice ) {
						update_post_meta( $postID, 'myCRED_sell_content', $mCa );
					} else {
						delete_post_meta( $postID, 'myCRED_sell_content' );
					}
				}
			}

			// accessPassword
			$accessPassword = ''; // remove if not enabled
			if ( self::inList( $userkeys, $options['accessPassword'] ) ) {
				$accessPassword = sanitize_text_field( $_POST['accessPassword'] );
			}

			// if ($accessPassword) $htmlCode .= 'Webcam was password protected.';

			wp_update_post(
				array(
					'ID'            => $postID,
					'post_password' => $accessPassword,
				)
			);
/*
			// logoCustom
			if ( self::inList( $userkeys, $options['logoCustom'] ) ) {
				$logoImage = sanitize_text_field( $_POST['logoImage'] );
				update_post_meta( $postID, 'vw_logoImage', $logoImage );

				$logoLink = sanitize_text_field( $_POST['logoLink'] );
				update_post_meta( $postID, 'vw_logoLink', $logoLink );

				update_post_meta( $postID, 'vw_logo', 'custom' );
			}
*/
			// presentationMode
			if ( isset($options['presentationMode']) && isset($_POST['presentationMode']) ) if ( self::inList( $userkeys, $options['presentationMode'] ) ) {
				$roomLabel = sanitize_text_field( $_POST['presentationMode'] ?? 0 );
				update_post_meta( $postID, 'vw_presentationMode', $roomLabel );
			} else {
				update_post_meta( $postID, 'vw_presentationMode', '' );
			}
		}

		// feature updates

/*
		// transcode
		if ( self::inList( $userkeys, $options['multicam'] ) ) {
			update_post_meta( $postID, 'vw_multicam', $options['multicamMax'] );
		} else {
			update_post_meta( $postID, 'vw_multicam', '0' );
		}

		// transcode
		if ( self::inList( $userkeys, $options['transcode'] ) ) {
			update_post_meta( $postID, 'vw_transcode', '1' );
		} else {
			update_post_meta( $postID, 'vw_transcode', '0' );
		}

		// logoHide
		if ( self::inList( $userkeys, $options['logoHide'] ) ) {
			update_post_meta( $postID, 'vw_logo', 'hide' );
		} else {
			update_post_meta( $postID, 'vw_logo', 'global' );
		}
*/
		if ( $options['videosharevod'] ) {
			if ( self::inList( $userkeys, $options['videos'] ) ) {
				update_post_meta( $postID, 'vw_videos', '1' );
			} else {
				update_post_meta( $postID, 'vw_videos', '0' );
			}
		}

		if ( $options['picturegallery'] ) {
			if ( self::inList( $userkeys, $options['pictures'] ) ) {
				update_post_meta( $postID, 'vw_pictures', '1' );
			} else {
				update_post_meta( $postID, 'vw_pictures', '0' );
			}
		}

				// schedulePlaylists
		if ( ! $options['playlists'] || ! self::inList( $userkeys, $options['schedulePlaylists'] ) ) {
			update_post_meta( $postID, 'vw_playlistActive', '' );
		}

				// end room update

				// ! Go Live
		if ( isset( $_POST['go-live'] ) || isset( $_GET['livemode'] ) ) {
			// name fix
			$webcamPost = get_post( $postID );
			if ( $webcamPost->post_title != sanitize_text_field( $webcamPost->post_title ) ) {
				wp_update_post(
					array(
						'ID'         => $postID,
						'post_title' => sanitize_text_field( $webcamPost->post_title ),
					)
				);
			}

			// set current as room performer
			$performerName = self::performerName( $current_user, $options );

			update_post_meta( $postID, 'performer', $performerName );
			update_post_meta( $postID, 'performerUserID', $current_user->ID );

			update_post_meta( $postID, 'sessionStart', time() );

			self::roomStatsUpdate($postID);

			$link = get_permalink( $postID );

			$archive = 0;

			if ($options['debugMode']) $htmlCode .= '<BR>' . __( 'Room Name', 'ppv-live-webcams' ) . ': ' . $webcamPost->post_title;
			if ($options['debugMode']) $htmlCode .= '<BR>' . __( 'Room ID', 'ppv-live-webcams' ) . ': ' . $postID;

			if ( $_GET['livemode'] ?? false ) {
				$mode = sanitize_text_field( $_GET['livemode'] );
			} else {
				$mode = sanitize_text_field( $_POST['groupMode'] ?? '' );
			}

			// mode & parameters
			if ( is_array( $options['groupModes'] ) && $mode ) {
				foreach ( $options['groupModes'] as $groupMode => $modeParameters ) {


					if ( $mode == $groupMode ) {
						// 0 if not set
					$CPMg = 0;

						if ( $modeParameters['cpm'] ) {
							$CPMg = get_post_meta( $postID, 'vw_costPerMinuteGroup', true );
							if ( $CPMg ) {
								$modeParameters['cpm'] = $CPMg;
							} else {
								$CPMg = $modeParameters['cpm'];
							}
						}

						// custom 2 way slots if configured
						$slots2way = get_post_meta( $postID, 'vw_slots2way', true );
						if ( $slots2way ) {
							$modeParameters['2way'] = $slots2way;
						}

						if ($options['debugMode']) $htmlCode .= '<pre>' . print_r($modeParameters, true) . '</pre>';

						if ($options['debugMode']) $htmlCode .= '<BR>' . __( 'Setting webcam group mode to', 'ppv-live-webcams' ) . ': ' . $groupMode;
						update_post_meta( $postID, 'groupCPM', floatval( $CPMg ) );
						update_post_meta( $postID, 'groupMode', $groupMode );
						update_post_meta( $postID, 'groupParameters', $modeParameters );

						//mode parameters on login
						foreach ( array( 'room_random', 'requests_disable', 'room_private', 'calls_only', 'group_disabled', 'room_slots', 'room_conference', 'conference_auto', 'room_audio', 'room_text', 'vw_presentationMode', 'h5v_audio', 'party', 'party_reserved', 'stream_record', 'stream_record_all', 'stream_record_private' ) as $meta ) {
							if ( array_key_exists( $meta, $modeParameters ) ) {
								update_post_meta( $postID, $meta, $modeParameters[ $meta ] );
								if ($options['debugMode']) $htmlCode .= '<BR> - ' . $meta . ': ' . $modeParameters[ $meta ];
							}
						}

						if ( $modeParameters['archive'] ?? false ) {
							$archive = 1;
						}

						if ( $modeParameters['archiveImport'] ?? false ) {
							$archiveImport = 1;
						} else {
							$archiveImport = 0;
						}
					}
				}
			} else {
				update_post_meta( $postID, 'groupCPM', 0 );
				update_post_meta( $postID, 'groupMode', $mode ? $mode : __( 'Free', 'ppv-live-webcams' ) );
			}

			// room interface: defaults
			$roomInterface = 'html5app';

			if ( $options['webrtc'] ) {
				$agent   = $_SERVER['HTTP_USER_AGENT'];
				$Android = stripos( $agent, 'Android' );
				$iOS     = ( strstr( $agent, 'iPhone' ) || strstr( $agent, 'iPod' ) || strstr( $agent, 'iPad' ) );
				if ( $iOS || $Android ) {
					$roomInterface = 'html5app'; // on mobiles only html5/html5app available
				}
			}

			// or selected
			if ( isset( $_POST['roomInterface'] ) ) {
				$roomInterface = sanitize_text_field( $_POST['roomInterface'] );
			}

			// set
			update_post_meta( $postID, 'roomInterface', $roomInterface );

			// go-live room option defaults

			if ( is_array( $options['appSetup'] ) ) {
				if ( array_key_exists( 'Room', $options['appSetup'] ) ) {
					if ( is_array( $options['appSetup']['Room'] ) ) {
						foreach ( $options['appSetup']['Room'] as $key => $value ) {
							$optionCurrent = get_post_meta( $postID, $key, true );

							if ( empty( $optionCurrent ) || $options['appOptionsReset'] ) {
								update_post_meta( $postID, $key, $value );

								if ($options['debugMode']) $htmlCode .= '<BR>' . $key . ': ' . $value;

							}
						}
					}
				}
			}

					// import previous archives to prevent confusion
					self::importArchives( $postID );

				/*

			// archiving on streaming server discontinued
			if ( $archive ) {
						update_post_meta( $postID, 'rtmp_server', $options['rtmp_server_archive'] );

				if ( $archiveImport ) {
					// update info about archived sessions for automated import
					$archivedSessions = get_post_meta( $postID, 'archivedSessions', true );
					if ( ! $archivedSessions ) {
						$archivedSessions = array();
					}
					$archivedSession    = array(
						'performer'    => $performerName,
						'sessionStart' => time(),
						'groupMode'    => $mode,
					);
					$archivedSessions[] = $archivedSession;
					update_post_meta( $postID, 'archivedSessions', $archivedSessions );
				}
			} else {
				update_post_meta( $postID, 'rtmp_server', $options['rtmp_server'] );
			}*/

			// remove previous archive server
			if ( get_post_meta($postID, 'rtmp_server', true) == $options['rtmp_server'] ) delete_post_meta( $postID, 'rtmp_server' );

			// Checkins
			if ( $options['checkins'] ) {

				$selectedPerformers = isset( $_POST['selectedPerformers'] ) ? (array) $_POST['selectedPerformers'] : [];
				if ( count( $selectedPerformers ) ) {
					foreach ( $selectedPerformers as $key => $performerID ) {
						$selectedPerformers[ sanitize_text_field( $key ) ] = intval( $performerID );
					}
				} else $selectedPerformers = []; //for current performer

				if ( $performersCSV = sanitize_text_field( $_POST['performersCSV'] ) ) {

					$performers = explode( ',', $performersCSV );
					if ( count( $performers ) ) {
						foreach ( $performers as $performerV ) {
											$performerID = '';
											$value       = trim( $performerV );

							if ( is_email( $value ) ) {
								$performerID = get_user_by( 'email', $value );
							} else {
								$performerID = get_user_by( 'login', $value );
							}

							if ( $performerID ) {
								if ( ! in_array( $performerID, $selectedPerformers ) ) {
									$selectedPerformers[] = $performerID;
								}
							}

							if ( $value && ! $performerID ) {
								$htmlCode .= '<BR>' . __( 'Warning: Performer', 'ppv-live-webcams' ) . ' "' . $value . '" ' . __( 'not found: use a valid username (login) or email!', 'ppv-live-webcams' ) . '<BR>';
							}
						}
					}
				}
			} else $selectedPerformers = [];
			// always checkin current performer
			if ( ! in_array( $current_user->ID, $selectedPerformers ) ) {
				$selectedPerformers[] = $current_user->ID;
			}
			update_post_meta( $postID, 'checkin', $selectedPerformers );

			//room update when performer enters again, for users to reload
			update_post_meta( $postID, 'updated_room', time() );

			if (!isset($data['status'])) $data['status'] = 'off';
			$toyStatus = $data['status'] == 'on' ? 1 : 0;
			update_post_meta( $postID, 'lovenseToy', $toyStatus );


			// if ($roomInterface != 'html5app' )
			if ($options['debugMode']) $htmlCode .= '<BR>' . __( 'Room Interface', 'ppv-live-webcams' ) . ': ' . $roomInterface;

			if ($options['debugMode'] &&  $options['checkins']) {
				$htmlCode .= '<BR>' . __( 'Checked in performers', 'ppv-live-webcams' ) . ': ' . count( $selectedPerformers );
			}

							if ($options['debugMode']) $htmlCode .= '<BR>' . __( 'Assigning you', 'ppv-live-webcams' ) . ' (' . $performerName . ') ' . __( 'as room performer (streamer) and redirecting to webcam room page. Please wait...', 'ppv-live-webcams' ) . '';

							// notify performer is live
							self::notifyLive( $current_user, $postID, $options );

							$htmlCode .= '<PRE style="display: none">
							<SCRIPT>window.location=\'' . $link . '\';</SCRIPT>
							</PRE>
				<p><a class="ui button" href="' . $link . '"><b>' . __( 'Click here', 'ppv-live-webcams' ) . '</b> ' . __( 'to access your webcam room if you do not get automatically redirected', 'ppv-live-webcams' ) . '</a></p>';

							return $htmlCode;
		}

		// ! Current Webcam
		$webcamPost   = get_post( $postID );
		$hideRoomFeatures = false;

		if ( !$webcamPost ) {
			$htmlCode .= '<div class="ui inverted red segment">ERROR: "Current ' . $options['custom_post'] . '" post not found: #' . $postID . '.';

			$args = array(
				'post_type'      => sanitize_text_field( $options['custom_post'] ),
				'post_status'    => 'publish',
				'post_author'    => $current_user->ID,
				'posts_per_page' => 1,
			);
			$rooms = get_posts( $args );
			if ( count( $rooms ) ) {
				$webcamPost = $rooms[0];
				update_user_meta( $current_user->ID, 'currentWebcam', $webcamPost->ID );
				$htmlCode .= '</br>A room was found and set as current: #' . $webcamPost->ID;
			}

			if ( $options['performerWebcams'] )
			$htmlCode .= '<br><a class="ui icon right labeled button" href="' . add_query_arg( array( 'changeWebcam' => 1 ), $this_page ) . '"> <i class="sync icon"></i> ' . __( 'My Rooms', 'ppv-live-webcams' ) . '</a> ';

			$htmlCode .= '</br>If issue persists, contact administrator to troubleshoot!</div>';

			$hideRoomFeatures = true;
			return $htmlCode;
		}

		$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment"><h4 class="ui ' . $options['interfaceClass'] . ' header"> <i class="user circle icon"></i>' . ucwords($options['custom_post']). ' '. __( 'Room', 'ppv-live-webcams' ) . ': ' . $webcamPost->post_title . '</h4>';

		if ( $webcamPost->post_status != 'publish' ) {
			$htmlCode .= '<div class="ui inverted red segment">ERROR: Webcam room post is not accessible. Status is "' . $webcamPost->post_status . '" and should be "publish". Contact administrator to publish room or select a different room!</div>';
		}

		if ( $webcamPost->post_type != $options['custom_post'] ) {

			$warnCode = '';
			if ( $options['performerWebcams'] ) {
				$warnCode .= '<br><a class="ui icon right labeled button" href="' . add_query_arg( array( 'changeWebcam' => 1 ), $this_page ) . '"> <i class="sync icon"></i> ' . __( 'My Rooms', 'ppv-live-webcams' ) . '</a> ';
			}

			$htmlCode .= '<div class="ui inverted red segment">ERROR: Currently selected webcam room post is not of correct type. Type is "' . $webcamPost->post_type . '" and should be "' . $options['custom_post'] . '". As administrator changed room types, select a different room! ' . $warnCode . '</div>';

			$hideRoomFeatures = true;
		}

		// ! form: group mode
		$CPM     = get_post_meta( $postID, 'vw_costPerMinute', true );
		$CPMg    = get_post_meta( $postID, 'vw_costPerMinuteGroup', true );
		$cpmCode = '';
		// if ($CPM) $cpmCode.=  __('Custom cost per minute in private show:', 'ppv-live-webcams') . ' '. $CPM . ' ';
		if ( $CPMg ) {
			$cpmCode .= '<br>* ' . __( 'Custom cost per minute in paid group chat:', 'ppv-live-webcams' ) . ' ' . $CPMg . ' ' . htmlspecialchars( $options['currency'] );
		}

		if ( $options['performerGolive'] == 'buttons' ) {

			if ( is_array( $options['groupModes'] ) ) {
				$groupModeCrt = get_post_meta( $postID, 'groupMode', true );

				$htmlCode .= '<table class="ui very basic table"><tbody>';

				foreach ( $options['groupModes'] as $groupMode => $modeParameters ) {
					$htmlCode .= '<tr><td>';
					$htmlCode .= '<a href="' . add_query_arg( 'livemode', $groupMode, $this_page ) . '" class="ui icon right labeled button fluid" > ' . $groupMode . ' <i class="play icon"></i></a> </td><td> <small>' . stripslashes( $modeParameters['description'] ) . '</small> ';
					if ( $modeParameters['cpm'] ) {
						$htmlCode .= '<small> ' . __( 'Paid Mode', 'ppv-live-webcams' ) . ': ' . $modeParameters['cpm'] . htmlspecialchars( $options['currencypm'] ) . '</small>';
					}
					$htmlCode .= '</td></tr>';
				}

				$htmlCode .= '</tbody></table>';
			}

			if ( self::is_true( get_post_meta( $postID, 'room_private', true ) ) ) {
				$htmlCode .= '<div>* ' . __( 'This room is currently private, hidden from room list.', 'ppv-live-webcams' ) . '</div>';
			}

			if ( $options['performerWebcams'] ) {
				$htmlCode .= '<hr class="ui divider"/> <a class="ui icon right labeled button" href="' . add_query_arg( array( 'changeWebcam' => 1 ), $this_page ) . '"> <i class="sync icon"></i> ' . __( 'My Rooms', 'ppv-live-webcams' ) . '</a> ' . __( 'Switch to different room / listing. Your account can setup and manage multiple room profiles.', 'ppv-live-webcams' );
			}
		} else // chat modes in advanced form
			{

			$webcamCode = '';
			if ( $options['performerWebcams'] ) {
				$webcamCode .= '<hr class="ui divider"/> <a class="ui icon right labeled button" href="' . add_query_arg( array( 'changeWebcam' => 1 ), $this_page ) . '"> <i class="sync icon"></i> ' . __( 'My Rooms', 'ppv-live-webcams' ) . '</a> ' . __( 'Switch to different room / listing. Your account can setup and manage multiple room profiles.', 'ppv-live-webcams' );
			}

			// groupModes
			$modeCode = '';
			if ( is_array( $options['groupModes'] ) ) {
				$groupModeCrt = get_post_meta( $postID, 'groupMode', true );

				$modeCode   .= '<div id="groupModeSelect"><SELECT class="ui dropdown v-select" name="groupMode" id="groupMode">';
				$modeDetails = '';

				foreach ( $options['groupModes'] as $groupMode => $modeParameters ) {
					/*
					if ($modeParameters['cpm'])
						if ($CPMg) $costCode = ' *' . $CPMg . htmlspecialchars($options['currencypm']) ;
						else $costCode = ' ' . $modeParameters['cpm'] . htmlspecialchars($options['currencypm']);
						else $costCode = '';
						*/
					$modeCode .= '<OPTION value="' . $groupMode . '" ' . ( $groupModeCrt == $groupMode ? 'selected' : '' ) . '>' . $groupMode . '</OPTION>';

					if ( $modeParameters['description'] || $modeParameters['cpm'] ) {
						$modeDetails .= $modeDetails ? '<br>' : '';

						$modeDetails .= '<b>' . $groupMode . '</b>' . ': <small>' . stripslashes( $modeParameters['description'] ) . '</small>';
						if ( $modeParameters['cpm'] ?? false ) {
							$modeDetails .= '<small> ' . __( 'Paid Mode', 'ppv-live-webcams' ) . ': ' . $modeParameters['cpm'] . htmlspecialchars( $options['currencypm'] ) . '</small>';
						}
					}
				}
				$modeCode .= '</SELECT> ' . __( 'Room Mode', 'ppv-live-webcams' ) . ': ' . __( 'Select group mode presets for your new session in this room, before going live.', 'ppv-live-webcams' ) . '</div>';
			} else {
				$htmlCode .= 'No chat modes defined.';
			}

			if ( ! $options['presentation'] && self::collaborationMode( $postID, $options ) ) {
				$modeDetails .= '<br>* ' . __( 'This room is currently in presentation/collaboration mode.', 'ppv-live-webcams' );
			}

			if ( self::is_true( get_post_meta( $postID, 'room_private', true ) ) ) {
				$modeDetails .= '<br>* ' . __( 'This room is currently private, hidden from room list.', 'ppv-live-webcams' );
			}

			// style
			$modeDetails = $modeDetails ? '<div class="ui small basic ' . $options['interfaceClass'] . ' segment">' . $modeDetails . '</div>' : '';

			// ! interface select
			$interfaceCode = '';
			$interfaceInfo = '';

			$roomInterface = get_post_meta( $postID, 'roomInterface', true );
			if ( $options['webrtc'] ) {
				if ( ! $roomInterface ) {
					$roomInterface = 'html5app';
				}

				$agent   = $_SERVER['HTTP_USER_AGENT'];
				$Android = stripos( $agent, 'Android' );
				$iOS     = ( strstr( $agent, 'iPhone' ) || strstr( $agent, 'iPod' ) || strstr( $agent, 'iPad' ) );

				$interfaceCode .= '<div id="roomInterfaceSelect"><SELECT class="ui dropdown v-select" name="roomInterface" id="roomInterface">';

				// html5 videochat app
				$interfaceCode .= '<OPTION value="html5app" ' . ( $roomInterface == 'html5app' ? 'selected' : '' ) . '>' . __( 'HTML5', 'ppv-live-webcams' ) . ' - ' . __( 'Recommended', 'ppv-live-webcams' ) . '</OPTION>';
				$interfaceInfo .= ' * ' . __( 'HTML5 Videochat works in mobile and PC browsers, providing streaming with group chat, private 2 way video calls, tips, conferencing, collaboration, external broadcasts. Recommended.', 'ppv-live-webcams' );

				// html5 live streaming
				/*
				if ($options['webrtc'] < 6)
				{
				$interfaceCode .= '<OPTION value="html5" ' . ($roomInterface=='html5'?'selected':'') . '>' . __('HTML5 Streaming', 'ppv-live-webcams') . '</OPTION>';
				$interfaceInfo .= '<br>HTML5 Streaming interface provides simple 1 way video streaming with group chat and tips.';
				}
				*/
			}

			// Flash
			if ( $options['webrtc'] < 5 ) {
				if ( ! $iOS && ! $Android ) {
					if ( ! $roomInterface ) {
						$roomInterface = 'flash';
					}

					$interfaceCode .= '<OPTION value="flash" ' . ( $roomInterface == 'flash' ? 'selected' : '' ) . '>' . __( 'Flash', 'ppv-live-webcams' ) . '</OPTION>';
					$interfaceInfo .= '<br> * ' . __( 'Flash PC interface is and older legacy app that supports presentation mode, streaming with group chat, 2 way videochat, tips. Mobile users will get the HTML5 Streaming interface, with extra latency, if transcoding is available. Does not support latest chat modes and options available in HTML5 app.', 'ppv-live-webcams' );
				} else {
					$interfaceInfo .= '<br>Advanced PC interface is Flash based and not supported in mobile brosers.';
				}
			}

			$interfaceCode .= '</SELECT> Room Interface: ' . __( 'Select room interface before going live. Also applies to clients.', 'ppv-live-webcams' );
			$interfaceCode .= '<div class="ui small basic ' . $options['interfaceClass'] . ' segment">' . $interfaceInfo . '</div>';
			$interfaceCode .= '</div>';

			// hide if html5app only

			if ( $options['webrtc'] >= 5 ) {
				$interfaceCode = '<input type="hidden" name="roomInterface" id="roomInterface" value="html5app">';
				$interfaceInfo = '';
			}

			// ! form: checkin
			$checkinCode = '';
			if ( $options['checkins'] ) {
				$checkinCode .= '<A class="ui icon right labeled button" id="hideshow" >' . __( 'CheckIn Options', 'ppv-live-webcams' ) . ' <i class="down arrow icon"></i> </a> ' . __( 'Tag other performers present in session.', 'ppv-live-webcams' );

				$checkinCode .= '<div id="checkinPerformers"><div class="ui ' . $options['interfaceClass'] . ' segment">
<H4 class="ui header">' . __( 'Checkin Performers', 'ppv-live-webcams' ) . '</H4>' . __( 'You can select partner performers or specify other performers that are present in session.', 'ppv-live-webcams' ) . '
<BR>' . __( 'If paid group chat is enabled, earnings will be shared equally between checked in performers.', 'ppv-live-webcams' );

				// partner performers
				$performerIDs = get_post_meta( $postID, 'performerID', false );
				if ( $performerIDs ) {
					if ( count( $performerIDs ) ) {
						$checkinCode .= '<BR> <label>' . __( 'Partner Performers', 'ppv-live-webcams' ) . '</label>';
						foreach ( $performerIDs as $performerID ) {
							$performer = get_user_by( 'id', $performerID );
							if ( $performer ) {
								$checkinCode .= '<input type="checkbox" name="selectedPerformers[]" value="' . $performer->ID . '">' . $performer->user_login . '<br>';
							}
						}
					}
				}

				$checkinCode .= '<BR> <label>' . __( 'Checkin Performers', 'ppv-live-webcams' ) . '</label> <input size="48" type="text" name="performersCSV" id="performersCSV" value=""><BR>' . __( 'Comma separated list of performer usernames or emails.', 'ppv-live-webcams' ) . '';

				$checkinCode .= '</div></div>';

				$checkinCode .= "<PRE style='display: none'><SCRIPT>jQuery(document).ready(function(){

		jQuery('#checkinPerformers').toggle('fast');

        jQuery('#hideshow').on('click', function(event) {
             jQuery('#checkinPerformers').toggle('slow');
			 jQuery(\".ui.dropdown:not(.multi,.fpsDropdown)\").dropdown();

        });
    });</SCRIPT></PRE>";
			}

			$htmlCode .= '
<form method="post" enctype="multipart/form-data" action="' . $this_page . '" name="goLiveForm" class="">
<div class="ui form">
<div class="ui field"><button class="ui icon right labeled button big green" type="submit" name="go-live" id="go-live" value="go-live" > ' . __( 'Go Live', 'ppv-live-webcams' ) . ' <i class="play icon"></i> </button> </div>
<div class="ui field">' . $modeCode . $modeDetails . '</div>
<div class="ui field">' . $interfaceCode . '</div>
<div class="ui field">' . $checkinCode . '</div>
<div class="ui field">' . $webcamCode . '</div>
</div>
</form>
<br style="clear:both">
';

		}
		if ( ! get_post_meta( $postID, 'hasSnapshot', true ) && ! get_post_meta( $postID, 'hasPicture', true ) ) {
			$htmlCode .= '<div class="ui segment secondary">' . __( 'This room can not currently show in room list as it does not have a snapshot or picture. Rooms show in list after first broadcast only with a listing image. Broadcast camera for about a minute to generate a snapshot or upload a room picture!', 'ppv-live-webcams' ) . '</div>';
		}

		$htmlCode .= '</div>
			';

		if ( $hideRoomFeatures ) {
			return $htmlCode;
		}

		// ! Dashboard Tabs
		// $htmlCode .= '<div class="vwtabs">';

		// tab header

		/*
			$headerCode .= '<div class="ui ' . $options['interfaceClass'] . ' top attached tabular menu">';


			$htmlCode .= '
		<script>
		jQuery(document).ready(function(){

		var vwTabs = jQuery(".tabular.menu .item");
		try{ vwTabs.tab()} catch(error)
		{
		console.log("Interface error Tabs", error, vwTabs);
		}
		});
		</script>';

		*/
		$active  = '';
		$header  = array();
		$content = array();

		if ( $options['calls'] ) {
			/*
			//! calls tab
			$checked = '';
			if ($_GET['calls']) $checked = 'active';
			if ($checked) $checked1 = true;

			$headerCode .= '<a class="item ' . $checked .'" data-tab="calls">' . __('Calls', 'ppv-live-webcams') . '</a>';

			$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="calls">';

			$contentCode .=  '<h3 class="ui header">' . __('Locked Private Calls', 'ppv-live-webcams') . '</H3>';

			$contentCode .= do_shortcode('[videowhisper_cam_calls post_id="' . $postID . '"]');

			$contentCode .= '<br style="clear:both"></div>';
			*/

			if ( isset( $_GET['calls'] ) || isset( $_GET['deletecall'] ) ) {
				$active = 'calls';
			}

			$header['calls'] = __( 'Calls', 'ppv-live-webcams' );

			$content['calls']  = '<h3 class="ui header ' . $options['interfaceClass'] .'">' . __( 'Locked Private Calls', 'ppv-live-webcams' ) . '</H3>';
			$content['calls'] .= do_shortcode( '[videowhisper_cam_calls post_id="' . $postID . '"]' );
		}

		// ! messages tab

		if ( $options['messages'] ?? false ) {
			$checked = '';
			if ( $_GET['messages'] ?? false ) {
				$checked = 'active';
			}
			if ( $checked ) {
				$checked1 = true;
			}

			global $wpdb;
			$table_messages = $wpdb->prefix . 'vw_vmls_messages';
			$sqlC           = $wpdb->prepare("SELECT COUNT(id) FROM `$table_messages` WHERE webcam_id=%d AND reply_id=%d AND ldate=%d", $postID, 0, 0); // for webcam, only questions (no replies), not replied (ldate)
			$unrepliedCount = $wpdb->get_var($sqlC);

			/*
				$headerCode .= '<a class="item ' . $checked .'" data-tab="messages">' . __('Messages', 'ppv-live-webcams') . ($unrepliedCount?'<div class="ui green left pointing label">'.$unrepliedCount.'</div>':'') .'</a>';

				$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="messages">';

				$contentCode .= '<h3 class="ui header">' . __('Paid Questions and Messages', 'ppv-live-webcams') . '</H3>';

				$contentCode .= do_shortcode('[videowhisper_cam_messages_performer post_id="' . $postID . '"]');

				$contentCode .= '<br style="clear:both"></div>';
				*/

			if ( $_GET['messages'] ?? false ) {
				$active = 'messages';
			}

			$header['messages'] = __( 'Messages', 'ppv-live-webcams' ) . ( $unrepliedCount ? '<div class="ui green left pointing label">' . $unrepliedCount . '</div>' : '' );

			$content['messages']  = '<h3 class="ui header ' . $options['interfaceClass'] . ' ">' . __( 'Paid Questions and Messages', 'ppv-live-webcams' ) . '</H3>';
			$content['messages'] .= do_shortcode( '[videowhisper_cam_messages_performer post_id="' . $postID . '"]' );

			if ($options['sms_number']) $content['messages']  .= do_shortcode( '[videowhisper_sms_number]' );

			// $content['messages'] .= '<p>' . __('Clients can send paid questions.', 'ppv-live-webcams') . '</p>';
		}

		/*
			$htmlCode .= '<div class="vwtab">
		<input type="radio" id="tab-2" name="tab-group-1" '.$checked.'>
		<label class="vwlabel" for="tab-2">' . __('Setup', 'ppv-live-webcams') . '</label>

		<div class="vwpanel">
		<div class="vwcontent">';
		*/

		// ! setup tab

		/*
			$checked = '';
			if ($_POST['setup']) $checked = 'active';
			if ($checked) $checked1 = true;

			$headerCode .= '<a class="item ' . $checked .'" data-tab="setup">' . __('Setup', 'ppv-live-webcams') . '</a>';

			$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="setup">';
		*/

		if ( $options['performerSetup'] ?? false ) {

			if ( $_POST['setup'] ?? false ) {
				$active = 'setup';
			}

			$header['setup'] = __( 'Setup', 'ppv-live-webcams' );

			$featuresCode = '';


						$featuresCode .= '<tr><th colspan="2">' . __( 'Room Access & Monetization', 'ppv-live-webcams' ) . '</td></tr>';


			// costPerMinute
			if ( self::inList( $userkeys, $options['costPerMinute'] ) )
			{

				if ( $postID ) $value = get_post_meta( $postID, 'vw_costPerMinute', true );

				if ( $value == '' ) {
					$value = $options['ppvPPM'];
				}

				if ( $value < $options['ppvPPMmin'] ) {
					$value = $options['ppvPPMmin'];
				}
				if ( $options['ppvPPMmax'] ) {
					if ( $value > $options['ppvPPMmax'] ) {
						$value = $options['ppvPPMmax'];
					}
				}

					$cpmRange = '' . __( 'Default', 'ppv-live-webcams' ) . ': ' . $options['ppvPPM'] . ' Min: ' . $options['ppvPPMmin'] . ' Max: ' . $options['ppvPPMmax'];

				$featuresCode .= '<tr><td>' . __( 'Cost Per Minute', 'ppv-live-webcams' ) . '</td><td><div class="ui right labeled input"><input size=5 name="costPerMinute" id="costPerMinute" value="' . $value . '"><div class="ui  label">' . esc_html( $options['currency'] ) . ' </div></div><BR>' . __( 'Cost per minute for private shows (set 0 to use default).', 'ppv-live-webcams' ) . ' ' . $cpmRange . '</td></tr>';


				if ( $options['modeAudio'] )
				{

				if ( $postID ) $value = get_post_meta( $postID, 'vw_costPerMinuteaudio', true );

				if ( $value == '' ) {
					$value = $options['ppvPPMaudio'];
				}

				if ( $value < $options['ppvPPMmin'] ) {
					$value = $options['ppvPPMmin'];
				}
				if ( $options['ppvPPMmax'] ) {
					if ( $value > $options['ppvPPMmax'] ) {
						$value = $options['ppvPPMmax'];
					}
				}

					$featuresCode .= '<tr><td>' . __( 'Cost Per Minute', 'ppv-live-webcams' ) . ': ' . __( 'Audio Only', 'ppv-live-webcams' ) . '</td><td><div class="ui right labeled input"><input size=5 name="costPerMinuteAudio" id="costPerMinuteAudio" value="' . $value . '"><div class="ui  label">' . esc_html( $options['currency'] ) . ' </div></div><BR>' . ' ' . $cpmRange . '</td></tr>';

				}

				if ( $options['modeText'] )
				{

				if ( $postID ) $value = get_post_meta( $postID, 'vw_costPerMinutetext', true );

				if ( $value == '' ) {
					$value = $options['ppvPPMtext'];
				}

				if ( $value < $options['ppvPPMmin'] ) {
					$value = $options['ppvPPMmin'];
				}
				if ( $options['ppvPPMmax'] ) {
					if ( $value > $options['ppvPPMmax'] ) {
						$value = $options['ppvPPMmax'];
					}
				}

					$featuresCode .= '<tr><td>' . __( 'Cost Per Minute', 'ppv-live-webcams' ) . ': ' . __( 'Text Only', 'ppv-live-webcams' ) . '</td><td><div class="ui right labeled input"><input size=5 name="costPerMinuteText" id="costPerMinuteText" value="' . $value . '"><div class="ui  label">' . esc_html( $options['currency'] ) . ' </div></div><BR>' . ' ' . $cpmRange . '</td></tr>';
					}

			}

			// costPerMinuteGroup
			if ( self::inList( $userkeys, $options['costPerMinuteGroup'] ) ) {
				if ( $postID ) {
					$value = get_post_meta( $postID, 'vw_costPerMinuteGroup', true );
				}
				if ( $value == '' ) {
					$value = $options['ppvPPM'];
				}

				if ( $value < $options['ppvPPMmin'] ) {
					$value = $options['ppvPPMmin'];
				}
				if ( $options['ppvPPMmax'] ) {
					if ( $value > $options['ppvPPMmax'] ) {
						$value = $options['ppvPPMmax'];
					}
				}

					$cpmRange = '' . __( 'Default', 'ppv-live-webcams' ) . ': ' . $options['ppvPPM'] . ' Min: ' . $options['ppvPPMmin'] . ' Max: ' . $options['ppvPPMmax'];

				$featuresCode .= '<tr><td>' . __( 'Group Show Cost Per Minute', 'ppv-live-webcams' ) . '</td><td><div class="ui right labeled input"><input size=5 name="costPerMinuteGroup" id="costPerMinuteGroup" value="' . $value . '"><div class="ui  label">' . esc_html( $options['currency'] ) . ' </div></div><BR>' . __( 'Cost per minute for group shows. Replaces paid group CPM (set 0 to use default).', 'ppv-live-webcams' ) . ' ' . $cpmRange . '</td></tr>';
			}



			//MicroPayment Subscriptions
			if ( class_exists( 'VWpaidMembership' ) )
			{
				$subscriptions = get_user_meta( $current_user->ID, 'vw_provider_subscriptions', true );
				$content_tier = get_post_meta( $postID, 'vw_subscription_tier', true );

			   //$client_tier         = get_user_meta( $userID, 'vw_client_subscription_' . $author->ID, true );

			   	$optionsMP = get_option( 'VWpaidMembershipOptions' );
			    $setupLink = ( $optionsMP['p_videowhisper_provider_subscriptions'] ? ' <a href="' . get_permalink( $optionsMP['p_videowhisper_provider_subscriptions'] ) . '"><i class="lock icon"></i>' . __( 'Setup Subscriptions', 'ppv-live-webcams' ) . '</a>' : '' );

				if ($subscriptions && is_array($subscriptions))
				{

					$featuresCode .= '<tr><td>' . __( 'Subscription Tier', 'ppv-live-webcams' ) . '</td><td><select name="subscriptionTier" id="subscriptionTier" class="ui dropdown v-select">';

					$featuresCode     .= '<option value="0" ' . ( $content_tier == 0 ? 'selected' : '' ) . '>' . __('None', 'ppv-live-webcams') . '</option>';

					foreach ($subscriptions as $tier => $subscription)
					$featuresCode     .= '<option value="' . $tier . '" ' . ( $content_tier == $tier ? 'selected' : '' ) . '>' . $tier . '. ' . $subscription['name'] . '</option>';

					$featuresCode     .= '</select>' .  '<br>'. __('Users that buy configured subscription tier or higher can participate in paid group shows for free.', 'ppv-live-webcams') . $setupLink . '</td></tr>';

				}else $featuresCode .= '<tr><td>' . __( 'Subscription Tier', 'ppv-live-webcams' ) . '</td><td>'. __( 'You do not have any subscriptions setup, yet.', 'ppv-live-webcams' )  . $setupLink .   '<br>'. __('Users that buy configured subscription tier or higher can participate in group sessions for free.', 'ppv-live-webcams') . '</td></tr>';

			}

			// accessPrice
			if ( self::inList( $userkeys, $options['accessPrice'] ) ) {
				if ( $postID ) {

				//MicroPayments
				if ( class_exists( 'VWpaidMembership' ) ) {

					$data = \VWpaidMembership::contentData( $postID);
					$value = floatval( $data['price'] );

				} else {

						$value = get_post_meta( $postID, 'vw_accessPrice', true );
					}

				} else {
					$value = '';
				}

if ( class_exists( 'VWpaidMembership' ) )
{
				$optionsMP = get_option( 'VWpaidMembershipOptions' );
			    $setupLink = '<a href="' . add_query_arg( 'editID', intval( $postID ), get_permalink( $optionsMP['p_videowhisper_content_edit'] ) ) . '"><i class="edit icon"></i> ' . __( 'Setup Monetization', 'ppv-live-webcams' ) . '</a>';

			    $featuresCode .= '<tr><td' . __('>Access Price', 'ppv-live-webcams') . '</td><td>' . esc_html($value) . esc_html($optionsMP['currency']) .' ' . $setupLink . '</td></tr>';

}
else $featuresCode .= '<tr><td' . __('>Access Price', 'ppv-live-webcams') . '</td><td><input size=5 name="accessPrice" id="accessPrice" value="' . $value . '"><BR>' . __( 'Room access price. Leave 0 for free access.', 'ppv-live-webcams' ) . '</td></tr>';


			}

					// accessList
			if ( self::inList( $userkeys, $options['accessList'] ) ) {
				if ( $postID ) {
					$value = get_post_meta( $postID, 'vw_accessList', true );
				} else {
					$value = '';
				}

				$featuresCode .= '<tr><td>' . __( 'Access List', 'ppv-live-webcams' ) . '</td><td><textarea rows=2 cols="80" name="accessList" id="accessList">' . $value . '</textarea><BR>' . __( 'User roles, logins, emails separated by comma. Leave empty to allow everybody to access.', 'ppv-live-webcams' ) . '</td></tr>';
			}



			// accessPassword
			if ( self::inList( $userkeys, $options['accessPassword'] ) ) {
				$value = $webcamPost->post_password;

				$featuresCode .= '<tr><td>Access Password</td><td><input size=16 name="accessPassword" id="accessPassword" value="' . $value . '"><BR>Password to protect room page access. Leave blank to not require password.</td></tr>';
			}

						// banCountries
			if ( self::inList( $userkeys, $options['banCountries'] ) ) {
				if ( $postID ) {
					$banLocations = get_post_meta( $postID, 'vw_banCountries', false );
				} else {
					$banLocations = '';
				}

				$value = implode( ', ', $banLocations );

				$info = '';

				// $banLocations = str_getcsv($value, ',');
				$info .= ' (' . count( $banLocations ) . ') ';

				$clientIP = self::get_ip_address();
				$info    .= '<br>' . __( 'Detected IP', 'ppv-live-webcams' ) . ': ' . $clientIP;

				$detectedLocation = self::detectLocation( 'all', $clientIP );

				$info .= '<br>' . __( 'Detected Location', 'ppv-live-webcams' ) . ': ' . ( $detectedLocation ? implode( ", ", $detectedLocation ) : __( 'Not detected.', 'ppv-live-webcams' ) );

				if ( self::detectLocation() === false ) {
					$info .= '<BR>' . __( 'ERROR: GeoIP extension is required on web host for this functionality.', 'ppv-live-webcams' );
				}

				$featuresCode .= '<tr><td>' . __( 'Ban Countries', 'ppv-live-webcams' ) . '</td><td><textarea rows="2" cols="80" name="banCountries" id="banCountries">' . htmlspecialchars( $value ) . '</textarea><BR>' . __( 'You may want to ban your location for privacy reasons. Or restrict countries and regions where your content is not legal or well received. Room will not be listed and room page will not be accessible from banned location. This functionality is not 100% accurate and may be bypassed with VPNs. Specify list of locations (country, region, city or continent code, separated by comma) that can not access webcam room. ', 'ppv-live-webcams' ) . $info . '</td></tr>';

			}


			$featuresCode .= '<tr><th colspan="2">' . __( 'Room Details', 'ppv-live-webcams' ) . '</td></tr>';


			// uploadPicture
			if ( self::inList( $userkeys, $options['uploadPicture'] ) ) {

				$picCode      = '';
				$picture      = get_post_meta( $postID, 'picture', true );
				$thumbPicture = get_post_meta( $postID, 'thumbPicture', true );

				if ( $picture ) {
					if ( file_exists( $picture ) ) {
						$picCode .= '<br>Picture: <a href="' . self::path2url( $picture ) . '?nocache=1" target="_picture">' . basename( $picture ) . '</a> ' . self::humanSize( filesize( $picture ) );
					}
				}
				if ( $thumbPicture ) {
					if ( file_exists( $thumbPicture ) ) {
						$picCode .= '<br>Picture Thumb: <a href="' . self::path2url( $thumbPicture ) . '?nocache=1" target="_picture">' . basename( $thumbPicture ) . '</a> ' . self::humanSize( filesize( $thumbPicture ) );
					}
				}

						$featuresCode .= '<tr><td>' . __( 'Picture', 'ppv-live-webcams' ) . '</td><td><input type="file" accept="image/*;capture=camera" name="uploadPicture1" id="uploadPicture1" class="ui button">' . '<BR>' . __( 'Update room picture to use instead of live snapshot.', 'ppv-live-webcams' ) . $picCode . '</td></tr>';

					$value   = get_post_meta( $postID, 'showImage', true );
					$picCode = '';

					$snapshot      = get_post_meta( $postID, 'snapshot', true );
					$thumbSnapshot = get_post_meta( $postID, 'thumbSnapshot', true );

					$performerID = get_post_meta( $postID, 'performerUserID', true );
					$performer   = get_post_meta( $postID, 'performer', true );
					$avatar      = get_avatar_url( $performerID, array( 'size' => max( $options['thumbWidth'], $options['thumbHeight'] ) ) );

				if ( $snapshot ) {
					if ( file_exists( $snapshot ) ) {
						$picCode .= '<br>Snapshot: <a href="' . self::path2url( $snapshot ) . '?nocache=1" target="_picture">' . basename( $snapshot ) . '</a> ' . self::humanSize( filesize( $snapshot ) );
					}
				}
				if ( $thumbSnapshot ) {
					if ( file_exists( $thumbSnapshot ) ) {
						$picCode .= '<br>Snapshot Thumb: <a href="' . self::path2url( $thumbSnapshot ) . '?nocache=1" target="_picture"> ' . basename( $thumbSnapshot ) . '</a> ' . self::humanSize( filesize( $thumbSnapshot ) );
					}
				}

				if ( $avatar ) {
					$picCode .= '<br>Performer Avatar: <a href="' . $avatar . '" target="_picture"> ' . $performer . ' #' . $performerID . '</a> ';
				}

					$featuresCode .= '<tr><td>' . __( 'Show', 'ppv-live-webcams' ) . '</td><td><select name="showImage" id="showImage" class="ui dropdown v-select">';
					$featuresCode     .= '<option value="auto" ' . ( $value == 'auto' ? 'selected' : '' ) . '>' . __( 'Auto', 'ppv-live-webcams' ) . '</option>';
					$featuresCode     .= '<option value="teaser" ' . ( $value == 'teaser' ? 'selected' : '' ) . '>' . __( 'Teaser', 'ppv-live-webcams' ) . '</option>';
					$featuresCode     .= '<option value="picture" ' . ( $value == 'picture' ? 'selected' : '' ) . '>' . __( 'Picture', 'ppv-live-webcams' ) . '</option>';
					$featuresCode     .= '<option value="snapshot" ' . ( $value == 'snapshot' ? 'selected' : '' ) . '>' . __( 'Live Snapshot', 'ppv-live-webcams' ) . '</option>';
					$featuresCode     .= '<option value="avatar" ' . ( $value == 'avatar' ? 'selected' : '' ) . '>' . __( 'Performer Avatar', 'ppv-live-webcams' ) . '</option>';
					$featuresCode     .= '</select>' . '<BR>' . __( 'Select preview to show in webcam listings and HTML5 Videochat room while not streaming. Auto shows picture or teaser when offline and snapshot when online.', 'ppv-live-webcams' ) . $picCode . '</td></tr>';

			}

			if ( $options['lovense'] && $options['lovenseToy'] == 'manual' )
			{
				$value   = get_post_meta( $postID, 'lovenseToy', true );

				$featuresCode .= '<tr><td>' . __( 'Lovense Toy', 'ppv-live-webcams' ) . '</td><td><select name="lovenseToy" id="lovenseToy" class="ui dropdown v-select">';
				$featuresCode     .= '<option value="0" ' . ( !$value ? 'selected' : '' ) . '>' . __( 'Disabled', 'ppv-live-webcams' ) . '</option>';
				$featuresCode     .= '<option value="1" ' . ( $value ? 'selected' : '' ) . '>' . __( 'Enabled', 'ppv-live-webcams' ) . '</option>';
				$featuresCode     .= '</select>' . '<BR>' . __( 'Show in listings that Lovense toy is active for this room.', 'ppv-live-webcams' ). '</td></tr>';

			}

			// roomLabel
			$vw_roomLabel = '';
			if ( self::inList( $userkeys, $options['roomLabel'] ) ) {
				if ( $postID ) {
					$vw_roomLabel = get_post_meta( $postID, 'vw_roomLabel', true );
				};

				$featuresCode .= '<tr><td>' . __( 'Label', 'ppv-live-webcams' ) . '</td><td><input size=24 name="roomLabel" id="roomLabel" value="' . $vw_roomLabel . '"><BR>' . __( 'Shows in room listings instead of name.', 'ppv-live-webcams' ) . '</td></tr>';
			}


			// roomCategory
			$newCat = '';
			if ( self::inList( $userkeys, $options['roomCategory'] ) ) {
				$cats = wp_get_post_categories( $postID );
				if ( count( $cats ) ) {
					$newCat = array_pop( $cats );
				}

				$featuresCode .= '<tr><td>' . __( 'Category', 'ppv-live-webcams' ) . '</td><td>' . wp_dropdown_categories( 'show_count=0&echo=0&name=newcategory&hide_empty=0&class=ui+dropdown&selected=' . $newCat ) . '<BR>' . __( 'Category.', 'ppv-live-webcams' ) . '</td></tr>';
			}

			// roomDescription
			if ( self::inList( $userkeys, $options['roomDescription'] ) ) {
				$featuresCode .= '<tr><td>' . __( 'Description', 'ppv-live-webcams' ) . '</td><td><textarea rows="4" cols="80" name="roomDescription" id="roomDescription">' . htmlspecialchars( $webcamPost->post_content ) . '</textarea><BR>' . __( 'Room description: profile, schedule. Shows on room page and full row list layout.', 'ppv-live-webcams' ) . '</td></tr>';
			}

			// roomBrief
			if ( self::inList( $userkeys, $options['roomBrief'] ) ) {
				if ( $postID ) {
					$value = get_post_meta( $postID, 'vw_roomBrief', true );
				} else {
					$value = '';
				}

				$featuresCode .= '<tr><td>' . __( 'Brief', 'ppv-live-webcams' ) . '</td><td><textarea rows="2" cols="80" name="roomBrief" id="roomBrief">' . htmlspecialchars( $value ) . '</textarea><BR>' . __( 'Room brief info: profile, schedule. Shows in room listings.', 'ppv-live-webcams' ) . '</td></tr>';
			}
/*
			// private2way
			if ( self::inList( $userkeys, $options['private2way'] ) ) {
				if ( $postID ) {
					$value = get_post_meta( $postID, 'vw_private2way', true );
				} else {
					$value = '';
				}

				$featuresCode .= '<tr><td>' . __( 'Private Videochat Mode', 'ppv-live-webcams' ) . '</td><td><select name="private2way" id="private2way" class="ui dropdown v-select">';
				$featuresCode .= '<option value="" ' . ( $value == '' ? 'selected' : '' ) . '>' . __( 'Default', 'ppv-live-webcams' ) . '</option>';
				$featuresCode .= '<option value="1way" ' . ( $value == '1way' ? 'selected' : '' ) . '>' . __( '1 Way', 'ppv-live-webcams' ) . '</option>';
				$featuresCode .= '<option value="2way" ' . ( $value == '2way' ? 'selected' : '' ) . '>' . __( '2 Way', 'ppv-live-webcams' ) . '</option>';
				// $featuresCode .= '<option value="both" '.($value=='both'?'selected':'').'>Both Modes</option>';
				$featuresCode .= '</select><BR>' . __( 'Allow clients to start their cams for 2 way videochat.', 'ppv-live-webcams' ) . '</td></tr>';
			}
*/
			// roomTags
			if ( self::inList( $userkeys, $options['roomTags'] ) ) {
				$tags = wp_get_post_tags( $postID, array( 'fields' => 'names' ) );
				// var_dump($tags);
				$value = '';

				if ( ! empty( $tags ) ) {
					if ( ! is_wp_error( $tags ) ) {
						foreach ( $tags as $tag ) {
							$value .= ( $value ? ', ' : '' ) . $tag;
						}
					}
				}

						$featuresCode .= '<tr><td>' . __( 'Tags', 'ppv-live-webcams' ) . '</td><td><textarea rows=2 cols="80" name="roomTags" id="roomTags">' . $value . '</textarea><BR>' . __( 'Tags separated by comma. Show in room listings.', 'ppv-live-webcams' ) . '</td></tr>';
			}

/*
			// presentationMode
			if ( self::inList( $userkeys, $options['presentationMode'] ) ) {
				if ( $postID ) {
					$value = get_post_meta( $postID, 'vw_presentationMode', true );
				} else {
					$value = '';
				}

				$featuresCode .= '<tr><td>' . __( 'Collaboration Mode', 'ppv-live-webcams' ) . '</td><td><select name="presentationMode" id="presentationMode" class="ui dropdown v-select">';
				$featuresCode .= '<option value="1" ' . ( $value == '1' ? 'selected' : '' ) . '>' . __( 'Enabled', 'ppv-live-webcams' ) . '</option>';
				$featuresCode .= '<option value="0" ' . ( $value == '0' ? 'selected' : '' ) . '>' . __( 'Disabled', 'ppv-live-webcams' ) . '</option>';
				// $featuresCode .= '<option value="both" '.($value=='both'?'selected':'').'>Both Modes</option>';
				$featuresCode .= '</select><BR>' . __( 'Enables collaboration mode with file sharing.', 'ppv-live-webcams' ) . '</td></tr>';
			}
/*
			// logoCustom
			if ( self::inList( $userkeys, $options['logoCustom'] ) ) {
				$value         = get_post_meta( $editPost, 'vw_logoImage', true );
				$featuresCode .= '<tr><td>' . __( 'Logo Image', 'ppv-live-webcams' ) . '</td><td><input size=64 name="logoImage" id="logoImage" value="' . $value . '"><BR>' . __( 'Floating logo URL (preferably a transparent PNG image). Leave blank to hide.', 'ppv-live-webcams' ) . '</td></tr>';
				$value         = get_post_meta( $editPost, 'vw_logoLink', true );
				$featuresCode .= '<tr><td>' . __( 'Logo Link', 'ppv-live-webcams' ) . '</td><td><input size=64 name="logoLink" id="logoImage" value="' . $value . '"><BR>' . __( 'URL to open on logo click.', 'ppv-live-webcams' ) . '</td></tr>';
			}
*/


			$this_page = self::getCurrentURL();

			$msg1 = __( 'Room Setup', 'ppv-live-webcams' );
			$msg2 = __( 'Setup', 'ppv-live-webcams' );

			$interfaceClass = $options['interfaceClass'];

			if ( $featuresCode ) {
				$content['setup'] = <<<HTMLCODE
<form method="post" enctype="multipart/form-data" action="$this_page" name="adminForm" class="ui $interfaceClass form w-actionbox">
<h3 class="ui $interfaceClass header">$msg1</h3>
<table class="ui $interfaceClass selectable striped table form">
$featuresCode
<tr><td></td><td><input class="ui button primary" type="submit" name="setup" id="setup" value="$msg2" /></td></tr>
</table>
</form>

HTMLCODE;
			}

			/*
			$contentCode .= '
			<br style="clear:both">
			</div>';
			*/
		}

		// !matching tab

		if ($options['match'] ?? false )
		{
				if ( $_POST['matchFor'] ?? false) {
					$active = 'match';
				}

			$header['match'] = __( 'Match', 'ppv-live-webcams' );
			$content['match'] = __('Define criteria for random 2 way video call matches.','ppv-live-webcams') . do_shortcode( "[videowhisper_match_form postid=\"$postID\"]" );

		}


		// ! profile tab

		if ( $options['profiles'] ) {
			if ( is_array( $options['profileFields'] ) ) {

				/*
				$checked = '';
				if ($_POST['save']) $checked = 'active';
				if ($checked) $checked1 = true;

				$headerCode .= '<a class="item ' . $checked .'" data-tab="profile">' . __('Profile', 'ppv-live-webcams') . '</a>';

				$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="profile">';

						*/
				if ( $_POST['profileFor'] ?? false ) {
					$active = 'profile';
				}

				$header['profile'] = __( 'Profile', 'ppv-live-webcams' );

				$contentCode = '';
				/*
				$htmlCode .= '<div class="vwtab">
						<input type="radio" id="tab-profile" name="tab-group-1" '.$checked.'>
						<label class="vwlabel" for="tab-profile">' . __('Profile', 'ppv-live-webcams') . '</label>

						<div class="vwpanel">
						<div class="vwcontent">';
						*/

				$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' form"><form method="post" enctype="multipart/form-data" action="' . $this_page . '" name="profileForm" class="w-actionbox">';

				$contentCode .= '<h3 class="ui ' . $options['interfaceClass'] . ' header">' . ucwords($options['custom_post']). ' '.  __( 'Room Listing Profile', 'ppv-live-webcams' ) . '</H3> ' . __( 'These details will show on webcam room page (under videochat interface).', 'ppv-live-webcams' );

				$contentCode .= '<div class="ui divider" /></div>';

				// allowed tags
				$allowedtags = array(
					'a'          => array(
						'href'  => true,
						'title' => true,
					),
					'abbr'       => array(
						'title' => true,
					),
					'acronym'    => array(
						'title' => true,
					),
					'b'          => array(),
					'blockquote' => array(
						'cite' => true,
					),
					'cite'       => array(),
					'code'       => array(),
					'del'        => array(
						'datetime' => true,
					),
					'em'         => array(),
					'i'          => array(),
					'q'          => array(
						'cite' => true,
					),
					'strike'     => array(),
					'strong'     => array(),

					'ul'         => array(),
					'ol'         => array(),
					'li'         => array(),

					'span'       => array(
						'style' => array(),
					),

					'p'          => array(
						'style' => array(),
					),
				);

				$tinymce_options = array(
					'plugins'          => 'lists,link,textcolor,hr',
					'toolbar1'         => 'cut,copy,paste,|,undo,redo,|,fontsizeselect,forecolor,backcolor,bold,italic,underline,strikethrough',
					'toolbar2'         => 'alignleft,aligncenter,alignright,alignjustify,blockquote,hr,bullist,numlist,link,unlink',
					'fontsize_formats' => '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt',
				);

				//load location info in profile editor
				$locationsPath = $options['uploadsPath'] . '/_locations/';
				$locationsPathDefaut = dirname(plugin_dir_path( __FILE__ )) . '/data/';
	
				$fileLocations = $locationsPath . 'locations.json';
				$fileLanguages = $locationsPath . 'languages.json';

				if ( ! file_exists( $fileLocations ) )$fileLocations = $locationsPathDefaut . 'locations.json';
				if ( ! file_exists( $fileLanguages ) )$fileLanguages = $locationsPathDefaut . 'languages.json';

				if ( file_exists( $fileLocations ) ) $locations = json_decode( file_get_contents( $fileLocations ), true );
				if ( ! is_array( $locations ) ) $locations = array();

				if ( file_exists( $fileLanguages ) ) $languages = json_decode( file_get_contents( $fileLanguages ), true );
				if ( ! is_array( $languages ) ) $languages = array('en'=> 'English');

				$continents = array();
				$countries = array();
				$regions = array();

				foreach ( $locations as $continent_code => $continent )
				{
					$continents[ $continent_code ] = $continent['continent_name'];
					foreach ( $continent['countries'] as $country_code => $country )
					{
						$countries[ $country_code ] = $country['country_name'];

						if ( isset( $country['regions'] ) ) {
							foreach ( $country['regions'] as $region_code => $region ) {
								$regions[ $country_code ][ $region_code ] = $region['region_name'];
							}
						}
					}
				}

				$ajaxurlLocation = admin_url( 'admin-ajax.php' ) . '?action=vmls_location';

				$contentCode .= '<script>
				function updateLocationField(fieldName, level, continent, country, region) {
					var data = {
						for: "select",
						field_name: fieldName,
						level: level,
						continent0: continent,
						country0: country,
						region0: region,
						continent: jQuery("#" + fieldName + "_continent").val(),
						country: jQuery("#" + fieldName + "_country").val(),
						region: jQuery("#" + fieldName + "_region").val(),
					};

					if (level == "continent" || level == "country") 
					jQuery.post("' . $ajaxurlLocation . '", data, function(response) {
						if (response) jQuery("#" + fieldName + "_container").html(response);
						console.log(response, response.countries);
					});

					//update hidden field
					var value = jQuery("#" + fieldName + "_region").val() + ", " + jQuery("#" + fieldName + "_country").val() + ", " + jQuery("#" + fieldName + "_continent").val();
					jQuery("#" + fieldName).val(value);
				}
				</script>';
		

				foreach ( $options['profileFields'] as $field => $parameters ) {

					$fieldName = sanitize_title( trim( $field ) );

					if ( $parameters['instructions'] ?? false ) {
						$htmlInstructions = ' data-tooltip="' . htmlspecialchars( stripslashes( $parameters['instructions'] ) ) . '"';
					} else {
						$htmlInstructions = '';
					}

					$iconCode = '';
					$icon = isset($parameters['icon']) ? stripslashes( $parameters['icon'] ) : ( $options['profileFieldIcon'] ?? false );
					if ($icon) $iconCode .= '<i class="' . $icon . ' icon"></i> ';

					$contentCode .= '<div class="field"' . $htmlInstructions . '><label for="' . $fieldName . '">' . $iconCode .  $field . '</label>';
					// $contentCode .= '<span>' . htmlspecialchars(stripslashes($parameters['instructions'])) . '</span>';


					// save data
					if ( isset( $_POST[ $fieldName ] ) ) {
						if ( $postID == sanitize_text_field( $_POST['profileFor'] ?? 0 ) ) {
							if ( in_array( $parameters['type'], array('checkboxes', 'multiselect', 'language') ) ) 
							{
								$tags = $_POST[ $fieldName ] ?? array();

								if ( !is_array( $tags ) ) {
									$tags = explode( ',', $tags );
								}

								foreach ( $tags as &$tag ) {
									$tag = sanitize_text_field( trim($tag) );
								}
								unset( $tag );

								//$contentCode .= "--Saving vwf_$fieldName: " . count( $tags ) . ' items = ' . json_encode( $tags );

								update_post_meta( $postID, 'vwf_' . $fieldName, $tags );
							} else if ( $parameters['type'] == 'location' )
							{
								//obtain field value from region, country, continent separated by , 
								$fieldValue = sanitize_text_field( $_POST[ $fieldName . '_region' ] ) . ', ' . sanitize_text_field( $_POST[ $fieldName . '_country' ] ) . ', ' . sanitize_text_field( $_POST[ $fieldName . '_continent' ] );
								update_post_meta( $postID, 'vwf_' . $fieldName, $fieldValue );

								update_post_meta( $postID, 'vwf_' . $fieldName . '_continent', sanitize_text_field( $_POST[ $fieldName . '_continent' ] ) );
								update_post_meta( $postID, 'vwf_' . $fieldName . '_country', sanitize_text_field( $_POST[ $fieldName . '_country' ] ) );
								update_post_meta( $postID, 'vwf_' . $fieldName . '_region', sanitize_text_field( $_POST[ $fieldName . '_region' ] ) );
							}
							else {
								if (is_string($_POST[ $fieldName ]))  update_post_meta( $postID, 'vwf_' . $fieldName, wp_kses( $_POST[ $fieldName ], $allowedtags ) );
								else $contentCode .= '* Warning: Invalid content for field ' . $fieldName . ' of type ' . $parameters['type'];
							}
						}
					}

						// get data for field depending on type
						if ( in_array( $parameters['type'], array('checkboxes', 'multiselect', 'language') ) )
					 {
						$fieldValue = get_post_meta( $postID, 'vwf_' . $fieldName, true );
						if ( ! $fieldValue ) {
							$fieldValue = array();
						}
						if ( ! is_array( $fieldValue ) ) {
							$fieldValue = explode( ',', $fieldValue );
						}
					} else {
						$fieldValue = get_post_meta( $postID, 'vwf_' . $fieldName, true );
					}
					// $fieldValue = htmlspecialchars(stripslashes(get_post_meta( $postID, 'vwf_' . $fieldName, true )));

					$fieldOptions  = array();
					if (isset($parameters['options'])) {
						$parameters['options'] = stripslashes($parameters['options']);
						//if contains | explode by | else by /
						if (strpos($parameters['options'], '|') !== false) {
							$fieldOptions  = explode('|', $parameters['options']);
						} else {
							$fieldOptions  = explode('/', $parameters['options']);
						}
					}

					// form
					switch ( $parameters['type'] ) {

						case 'location';
						$continentValue = get_post_meta( $postID, 'vwf_' . $fieldName . '_continent', true );
						$countryValue   = get_post_meta( $postID, 'vwf_' . $fieldName . '_country', true );
						$regionValue    = get_post_meta( $postID, 'vwf_' . $fieldName . '_region', true );

						$contentCode .= '
								<input type="hidden" name="' . $fieldName . '" id="' . $fieldName . '" value="' . $fieldValue . '">
								<select class="ui dropdown" name="' . $fieldName . '_continent" id="' . $fieldName . '_continent" onchange="updateLocationField(\'' . $fieldName . '\', \'continent\',\'' . $continentValue . '\',\'' . $countryValue . '\',\'' . $regionValue . '\' )">
									<option value="">' . __('Select Continent', 'ppv-live-webcams') . '</option>';
									
						foreach ($continents as $key => $continent) {
							$contentCode .= '<option value="' . $continent . '" ' . ($continent == $continentValue ? 'selected' : '') . '>' . htmlspecialchars($continent) . '</option>';
						}

						$contentCode .= '</select>';

						$contentCode .= '<div id="' . $fieldName . '_container"></div>';

						//if $continent is set, add JS code to load by ajax countries from that continent updateLocationField

						if ($continentValue) $contentCode .= '<script type="text/javascript">
						jQuery(document).ready(function() {
							updateLocationField("' . $fieldName . '", "continent", "' . $continentValue . '", "' . $countryValue . '", "' . $regionValue . '");
						});
						</script>';		

						break;

						case 'continent';
						$contentCode .= '<div class="ui fluid search selection dropdown v-select">
						<input type="hidden" name="' . $fieldName . '" id="' . $fieldName . '" value="' . esc_attr($fieldValue). '"  >
						<i class="dropdown icon"></i>
						<div class="default text">' .__( 'Continent', 'ppv-live-webcams' ) . '</div>
						<div class="menu">';
						foreach ( $continents as $key => $continent ) {
							$contentCode .= '<div class="item" data-value="' . htmlspecialchars( stripslashes( $continent ) ) . '">' . htmlspecialchars( stripslashes( $continent ) ) . '</div>';
						}	
						$contentCode .= '</div></div>';
						break;

						case 'country';
						$contentCode .= '<div class="ui fluid search selection dropdown v-select">
						<input type="hidden" name="' . $fieldName . '" id="' . $fieldName . '" value="' . esc_attr($fieldValue). '"  >
						<i class="dropdown icon"></i>
						<div class="default text">' .__( 'Country', 'ppv-live-webcams' ) . '</div>
						<div class="menu">';

						foreach ( $countries as $key => $country ) {
							$contentCode .= '<div class="item" data-value="' . htmlspecialchars( stripslashes( $country ) ) . '"><i class="' . self::language2flag($key) . ' flag"></i> ' . htmlspecialchars( stripslashes( $country ) ) . '</div>';
						}
						$contentCode .= '</div></div>';
						break;

						case 'region';
						$contentCode .= '<div class="ui fluid search selection dropdown v-select">
						<input type="hidden" name="' . $fieldName . '" id="' . $fieldName . '" value="' . esc_attr($fieldValue). '"  >
						<i class="dropdown icon"></i>
						<div class="default text">' .__( 'Region', 'ppv-live-webcams' ) . '</div>
						<div class="menu">';
						foreach ( $regions as $country_code => $country ) {
							foreach ( $country as $region_code => $region ) {
								$contentCode .= '<div class="item" data-value="' . htmlspecialchars( stripslashes( $region ) ) . '">' . htmlspecialchars( stripslashes( $region  . ' (' . $country_code .')' ) ) . '</div>';
							}
						}
						$contentCode .= '</div></div>';
						break;

						case 'language';
						$contentCode .= '<div class="ui fluid search multiple selection dropdown v-select">
  <input type="hidden" name="' . $fieldName . '" id="' . $fieldName . '" value="' . esc_attr( implode(',', $fieldValue) ). '"  >
  <i class="dropdown icon"></i>
  <div class="default text">' .__( 'Language', 'ppv-live-webcams' ) . '</div>
  <div class="menu">';
						foreach ( $languages as $key => $language ) {
							$contentCode .= '<div class="item" data-value="' . htmlspecialchars( stripslashes( $language ) ) . '">' . htmlspecialchars( stripslashes( $language ) ) . '</div>'; //languages are not countries <i class="' . self::language2flag($key) . ' flag"></i>
						}
						$contentCode .= '</div></div>';
						break;

						case 'text';
							$contentCode .= '<INPUT type="text" size="72" name="' . $fieldName . '" id="' . $fieldName . '" value="' . $fieldValue . '">';
						break;

						case 'textarea';
							// $contentCode .= '<TEXTAREA type="text" rows="3" cols="70" name="'. $fieldName . '" id="'. $fieldName . '">' .$fieldValue. '</TEXTAREA>';

							ob_start();
							wp_editor(
								$fieldValue,
								$fieldName,
								$settings = array(
									'textarea_rows' => 3,
									'media_buttons' => false,
									'teeny'         => true,
									'wpautop'       => false,
									'tinymce'       => $tinymce_options,
								)
							);
							$contentCode .= ob_get_clean();

						break;

						case 'select';
							$contentCode .= '<SELECT class="ui dropdown search clearable v-select" name="' . $fieldName . '" id="' . $fieldName . '">';
							$contentCode .= '<OPTION value="" ' . ( ! $fieldValue ? 'selected' : '' ) . '> - </OPTION>';

							foreach ( $fieldOptions as $fieldOption ) {
								$contentCode .= '<OPTION value="' . htmlspecialchars( stripslashes( $fieldOption ) ) . '" ' . ( $fieldOption == $fieldValue ? 'selected' : '' ) . '>' . htmlspecialchars( $fieldOption ) . '</OPTION>';
							}

							$contentCode .= '</SELECT>';
						break;

						case 'multiselect';
							$contentCode .= '<SELECT MULTIPLE class="ui dropdown multiple search v-select" name="' . $fieldName . '[]" id="' . $fieldName . '" >';
							foreach ( $fieldOptions as $fieldOption ) {
								$contentCode .= '<OPTION value="' . htmlspecialchars( stripslashes( $fieldOption ) ) . '" ' . ( in_array( $fieldOption, $fieldValue ) ? 'selected' : '' ) . '>' . htmlspecialchars( $fieldOption ) . '</OPTION>';
							}

							$contentCode .= '</SELECT>';
							//$contentCode .= implode ( ', ', $fieldValue );
						break;

						case 'checkboxes';
							foreach ( $fieldOptions as $fieldOption ) {
								$contentCode .= '<div class="field"><div class="ui toggle checkbox">
  <input type="checkbox" name="' . $fieldName . '[]" value="' . htmlspecialchars( stripslashes( $fieldOption ) ) . '" ' . ( in_array( $fieldOption, $fieldValue ) ? 'checked' : '' ) . '>
  <label>' . htmlspecialchars( $fieldOption ) . '</label></div></div>';
							}
						break;

					}

					// if ($parameters['instructions']) $contentCode .= '<BR>' . htmlspecialchars($parameters['instructions']);

					$contentCode .= '<div class="ui divider" /></div> </div>';
				}

				$contentCode .= '<input type="hidden" name="profileFor" id="profileFor" value="' . $postID . '">
	<BR><input class="ui button primary" type="submit" name="save" id="save" value="' . __( 'Save', 'ppv-live-webcams' ) . '" />
	</form></div>';

				$content['profile'] = $contentCode;

				/*
				$contentCode .= '
				<br style="clear:both">
				</div>
				';*/
			}
		}


		if ($options['categoriesContest'] ?? false)
		{
			if ( ($_GET['categoryAdd'] ?? false) || ( $_GET['categoryRemove'] ?? false ) ) {
				$active = 'categories';
			}

			$header['categories'] = __( 'Contests', 'ppv-live-webcams' );
			$content['categories'] = wp_kses_post( $options['categoriesMessage'] ) . '<br>';

			$contestIDs = self::categoryIDs($options['categoriesContest']);
			$categories = wp_get_post_categories( $postID );

			if ($_GET['categoryAdd'] ?? false) {
				$contestID = intval($_GET['categoryAdd']);
				if ($contestID && !in_array($contestID, $categories)) {
					wp_set_post_categories( $postID, array_merge($categories, array($contestID)) );
					$categories = wp_get_post_categories( $postID );
				}
			}

			if  ( $_GET['categoryRemove'] ?? false )
			{
				$contestID = intval($_GET['categoryRemove']);
				if ($contestID && in_array($contestID, $categories)) {
					wp_set_post_categories( $postID, array_diff($categories, array($contestID)) );
					$categories = wp_get_post_categories( $postID );
				}
			}

			foreach ($contestIDs as $contestID)
			{
				$contestCategory = get_term($contestID);
				//check if category is assigned to post $postID
				
				$content['categories'] .= '<br><div class="ui label large">' . $contestCategory->name . '</div>' . ( in_array($contestID, $categories) ? ' <i class="icon check"></i>' : '' ) ;
				$currentURL = self::getCurrentURL();

				if (in_array($contestID, $categories))
				{
					//include wp_nonce_url to remove category
					$content['categories'] .= '<a class="ui button negative tiny" href="' . wp_nonce_url( add_query_arg( 'categoryRemove', $contestID, $currentURL), 'category' . $postID, 'verify' ). '">' . __('Remove', 'ppv-live-webcams') . '</a>';

				}
				else
				{
					//include wp_nonce_url to add category
					$content['categories'] .= '<a class="ui button positive tiny" href="' . wp_nonce_url( add_query_arg( 'categoryAdd', $contestID, $currentURL), 'category' . $postID, 'verify' ). '">' . __('Add', 'ppv-live-webcams') . '</a>';
				}
				
			}
		}

		// ! Restreaming tab

		if (!$options['webrtcOnly'])
		if ($options['reStreams'] || $options['pushStreams'] || self::is_true( get_post_meta( $postID, 'external_rtmp', true ) ) ) {
			if ( $_GET['streams'] ?? false ) {
				$active = 'streams';
			}

			$header['streams'] = __( 'Streams', 'ppv-live-webcams' );

			$contentCode = '';

			$contentCode .= do_shortcode( "[videowhisper_streams roomid=\"$postID\"]" );

			$content['streams'] = $contentCode;
		}

		// ! videos tab
		if ( $options['videosharevod'] ) {
			if ( shortcode_exists( 'videowhisper_postvideos' ) ) {
				if ( self::inList( $userkeys, $options['videos'] ) ) {

					// import saved archives
					self::importArchives( $postID );

					/*
					$checked = '';
					if ($_GET['playlist_upload'] || $_GET['playlist_import'] || $_GET['import_preview']) $checked = 'active';
					if ($checked) $checked1 = true;

					$headerCode .= '<a class="item ' . $checked .'" data-tab="videos">' . __('Videos', 'ppv-live-webcams') . '</a>';

					$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="videos">';
					*/
					if ( isset( $_GET['playlist_upload'] ) || isset( $_GET['playlist_import'] ) || isset( $_GET['import_preview'] ) ) {
						$active = 'videos';
					}

					$header['videos'] = __( 'Videos', 'ppv-live-webcams' );

					$contentCode = '';
					/*
					$htmlCode .= '<div class="vwtab">
					<input type="radio" id="tab-vid" name="tab-group-1" '.$checked.'>
					<label class="vwlabel" for="tab-vid">' . __('Videos', 'ppv-live-webcams') . '</label>

					<div class="vwpanel">
					<div class="vwcontent">';
					*/

					// recordings path
					$dir = $options['uploadsPath'];
					if ( ! file_exists( $dir ) ) {
						mkdir( $dir );
					}
					$dir .= '/' . sanitize_file_name( $webcamPost->post_title );
					if ( ! file_exists( $dir ) ) {
						mkdir( $dir );
					}
					$dir .= '/_recordings';
					if ( ! file_exists( $dir ) ) {
						mkdir( $dir );
					}

					$contentCode .= '<p>' . __( 'Multiple profile videos can show on webcam listing page. Each listing has own videos.', 'ppv-live-webcams' ) . '</p>';

					$contentCode .= do_shortcode( "[videowhisper_postvideos_process path=\"$dir\" prefix=\"\" post=\"$postID\"]" );

					$contentCode .= do_shortcode( "[videowhisper_postvideos path=\"$dir\" prefix=\"\" post=\"$postID\"]" );

					$contentCode .= '<p>' . __( 'Recorded live streams can be published using Import.', 'ppv-live-webcams' ) . '</p>';

					// record
					$contentCode .= '<p><b>' . __( 'Record Perfomer', 'ppv-live-webcams' ) . ': ' . ( self::is_true( get_post_meta( $postID, 'stream_record', true ) ) ? __( 'Enabled', 'ppv-live-webcams' ) : __( 'Disabled', 'ppv-live-webcams' ) ) . '</b><br>' . __( 'Record performer stream.', 'ppv-live-webcams' ) . '</p>';
					$contentCode .= '<p><b>' . __( 'Record Private', 'ppv-live-webcams' ) . ': ' . ( self::is_true( get_post_meta( $postID, 'stream_record_private', true ) ) ? __( 'Enabled', 'ppv-live-webcams' ) : __( 'Disabled', 'ppv-live-webcams' ) ) . '</b><br>' . __( 'Record in private calls.', 'ppv-live-webcams' ) . '</p>';

					$contentCode .= '<p><b>' . __( 'Record All', 'ppv-live-webcams' ) . ': ' . ( self::is_true( get_post_meta( $postID, 'stream_record_all', true ) ) ? __( 'Enabled', 'ppv-live-webcams' ) : __( 'Disabled', 'ppv-live-webcams' ) ) . '</b><br>' . __( 'Record streams from all users.', 'ppv-live-webcams' ) . '</p>';

					$content['videos'] = $contentCode;

					/*
					$contentCode .= '
					<br style="clear: both">
					</div>
					';
					*/
				}
			}
		}

			// !  teaser video tab
		if ( $options['videosharevod'] ) {
			if ( $options['teaserOffline'] ) {
				if ( shortcode_exists( 'videowhisper_postvideo_assign' ) ) {
					if ( self::inList( $userkeys, $options['videos'] ) ) {
						/*
						$checked = '';
						if ($_GET['assignVideo']) $checked = 'active';
						if ($checked) $checked1 = true;

						$headerCode .= '<a class="item ' . $checked .'" data-tab="teaser">' . __('Teaser', 'ppv-live-webcams') . '</a>';
						$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="teaser">';

						*/
						if ( $_GET['assignVideo'] ?? false ) {
							$active = 'teaser';
						}
						$header['teaser'] = __( 'Teaser', 'ppv-live-webcams' );

						/*
						$htmlCode .= '<div class="vwtab">
						<input type="radio" id="tab-teaser" name="tab-group-1" '.$checked.'>
						<label class="vwlabel" for="tab-teaser">' . __('Teaser', 'ppv-live-webcams') . '</label>

						<div class="vwpanel">
						<div class="vwcontent">
						*/
						$contentCode = do_shortcode( "[videowhisper_postvideo_assign post_id=\"$postID\" meta=\"video_teaser\"]" );

						$contentCode .= '<p>' . __( 'Teaser video (featured video) plays shortly as preview in listings, in room when performer is offline in HTML5 Videochat interface and shows on profile page. Can be selected from all videos of user.', 'ppv-live-webcams' ) . '</p>';

						$content['teaser'] = $contentCode;

						/*
						$contentCode .= '
						<br style="clear: both">
						</div>
						';
						*/
					}
				}
			}
		}

/*
					// ! playlists scheduler tab
		if ( $options['playlists'] ) {
			if ( self::inList( $userkeys, $options['schedulePlaylists'] ) ) {

				// activity
				$editPlaylist = $postID;

				$playlistActive = (int) $_POST['playlistActive'];

				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-widget' );
				wp_enqueue_script( 'jquery-ui-dialog' );

				// css
				wp_enqueue_style( 'jtable-green', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/jtable/themes/lightcolor/green/jtable.min.css' );

				wp_enqueue_style( 'jtable-flick', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/jtable/themes/flick/jquery-ui.min.css' );

				// js
				wp_enqueue_script( 'jquery-ui-jtable', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/jtable/jquery.jtable.min.js', array( 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-dialog' ) );

				$ajaxurl = admin_url() . 'admin-ajax.php?action=vmls_playlist&webcam=' . $postID;



				if ( $_POST['$editPlaylist'] || $_POST['playlistActive'] ) {
					$active = 'playlist';
				}
					$header['playlist'] = __( 'Playlist', 'ppv-live-webcams' );
					$contentCode        = '';
					// activity on this tab



					// OS timezone : Wowza time
					$timezone = 'UTC';
				if ( is_link( '/etc/localtime' ) ) {
					// Mac OS X (and older Linuxes)
					// /etc/localtime is a symlink to the
					// timezone in /usr/share/zoneinfo.
					$filename = readlink( '/etc/localtime' );
					if ( strpos( $filename, '/usr/share/zoneinfo/' ) === 0 ) {
						$timezone = substr( $filename, 20 );
					}
				} elseif ( file_exists( '/etc/timezone' ) ) {
					// Ubuntu / Debian.
					$data = file_get_contents( '/etc/timezone' );
					if ( $data ) {
						$timezone = $data;
					}
				} elseif ( file_exists( '/etc/sysconfig/clock' ) ) {
					// RHEL / CentOS
					$data = parse_ini_file( '/etc/sysconfig/clock' );
					if ( ! empty( $data['ZONE'] ) ) {
						$timezone = $data['ZONE'];
					}
				}

					$defaultTimezone = date_default_timezone_get();
				if ( $timezone ) {
					date_default_timezone_set( $timezone );
				}

					// $webcamPost;

					$stream = sanitize_file_name( $webcamPost->post_title );

					// ! quick loop setup - save loop

				if ( $loop = sanitize_text_field( $_POST['loop'] ) ) {

					$playlist = array();

					$item              = array();
					$item['Id']        = 1;
					$item['Video']     = $loop;
					$item['Repeat']    = 1;
					$item['Scheduled'] = date( 'Y-m-j h:i:s' );
					$item['Order']     = 1;

					$playlist[1] = $item;

					$playlist[1]['Videos'] = array();

					$item           = array();
					$item['Video']  = $loop;
					$item['Start']  = 0;
					$item['Length'] = -1;
					$item['Order']  = 1;
					$item['Id']     = 1;

					$playlist[1]['Videos'][] = $item;

					// $playlistPath
					$uploadsPath = $options['uploadsPath'];
					if ( ! file_exists( $uploadsPath ) ) {
						mkdir( $uploadsPath );
					}
					$upath = $uploadsPath . "/$stream/";
					if ( ! file_exists( $upath ) ) {
						mkdir( $upath );
					}
					$playlistPath = $upath . 'playlist.txt';

					self::varSave( $playlistPath, $playlist );

					// $contentCode .= '<p>' . __('Video Loop', 'ppv-live-webcams') . ': '.$loop.'</p>';
					update_post_meta( $postID, 'videoLoop', $loop );

				}

					$currentDate = date( 'Y-m-j h:i:s' );

					// activate playslit
				if ( $_POST['updatePlaylist'] ) {
					update_post_meta( $postID, 'vw_playlistActive', $playlistActive );
					self::updatePlaylist( $stream, $playlistActive );
					update_post_meta( $postID, 'vw_playlistUpdated', time() );

					if ( $playlistActive ) {
						$contentCode .= '<p>' . __( 'Playlist is enabled and room stream updated to', 'ppv-live-webcams' ) . ' ' . $stream . '.</p>';
						update_post_meta( $postID, 'performer', $stream );
						update_post_meta( $postID, 'performerUserID', $current_user->ID );

						update_post_meta( $postID, 'edate', time() );

						// stream is from rtmp server
						update_post_meta( $postID, 'stream-protocol', 'rtmp' );
						update_post_meta( $postID, 'stream-type', 'playlist' );

					}
				}

					// retrieve video list
					$optionsVSV        = get_option( 'VWvideoShareOptions' );
					$custom_post_video = $optionsVSV['custom_post'];
				if ( ! $custom_post_video ) {
					$custom_post_video = 'video';
				}

					// query
					$args = array(
						'post_type' => $custom_post_video,
						'author'    => $current_user->ID,
						'orderby'   => 'post_date',
						'order'     => 'DESC',
					);

					$postslist = get_posts( $args );
					if ( count( $postslist ) > 0 ) {
						$videoLoop = get_post_meta( $postID, 'videoLoop', true ); // previous video loop

						$quickCode .= '<SELECT id="loop" name="loop" class="ui dropdown v-select">';
						foreach ( $postslist as $item ) {
							$video_id = $item->ID;

							// retrieve video stream
							$streamPath = '';
							$videoPath  = get_post_meta( $video_id, 'video-source-file', true );
							$ext        = pathinfo( $videoPath, PATHINFO_EXTENSION );

							// use conversion if available
							$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
							if ( $videoAdaptive ) {
								$videoAlts = $videoAdaptive;
							} else {
								$videoAlts = array();
							}

							foreach ( array( 'high', 'mobile' ) as $frm ) {
								if ( array_key_exists( $frm, $videoAlts ) ) {
									if ( $alt = $videoAlts[ $frm ] ) {
										if ( file_exists( $alt['file'] ) ) {
											$ext        = pathinfo( $alt['file'], PATHINFO_EXTENSION );
											$streamPath = self::path2stream( $alt['file'] );
											break;
										}
									}
								}
							};

							// user original
							if ( ! $streamPath ) {
								if ( in_array( $ext, array( 'flv', 'mp4', 'm4v' ) ) ) {
											// use source if compatible
											$streamPath = self::path2stream( $videoPath );
								}
							}

													$quickCode .= '<option value="' . htmlspecialchars( $streamPath ) . '" ' . ( $videoLoop == $streamPath ? 'selected' : '' ) . '>' . $video_id . ' ' . $item->post_title . '</option>';

						}
						$quickCode .= '</SELECT>';
					} else {
						$quickCode = __( 'No videos found! Please add some videos first.', 'ppv-live-webcams' );
					}

					$playlistPage = add_query_arg( array( 'editPlaylist' => $editPlaylist ), $this_page );

					$msg1 = __( 'Quick Loop Setup', 'ppv-live-webcams' );
					$msg2 = __( 'Video will play in loop while playlist is active. Warning: Going live (with real webcam) will stop video loop (needs to be setup again). Can also be disabled by setting Playlist Status as Inactive.', 'ppv-live-webcams' );
					$msg3 = __( 'Set Video Loop', 'ppv-live-webcams' );

					$contentCode .= <<<HTMLCODE
					<h3 class="ui header">$msg1</H3>
<form method="post" action="$playlistPage" name="adminForm" class="w-actionbox">
<label>Select Video</label> $quickCode <input class="ui button" type="submit" name="button" id="button" value="$msg3" />
<input type="hidden" name="playlistActive" id="playlistActive" value="1" />
<input type="hidden" name="updatePlaylist" id="updatePlaylist" value="$editPlaylist" />
<BR>$msg2
<br style="clear:both">
</form>
HTMLCODE;

					// playlistActive
					$value = get_post_meta( $editPlaylist, 'vw_playlistActive', true );

					$activeCode .= '<select id="playlistActive" name="playlistActive" class="ui dropdown v-select">';
					$activeCode .= '<option value="0" ' . ( ! $value ? 'selected' : '' ) . '>' . __( 'Inactive', 'ppv-live-webcams' ) . '</option>';
					$activeCode .= '<option value="1" ' . ( $value ? 'selected' : '' ) . '>' . __( 'Active', 'ppv-live-webcams' ) . '</option>';
					$activeCode .= '</select>';

					$value           = get_post_meta( $editPlaylist, 'vw_playlistUpdated', true );
					$playlistUpdated = date( 'Y-m-j h:i:s', (int) $value );

					$value          = get_post_meta( $editPlaylist, 'vw_playlistLoaded', true );
					$playlistLoaded = date( 'Y-m-j h:i:s', (int) $value );

					$videosImg = dirname( plugin_dir_url( __FILE__ ) ) . 'scripts/jtable/themes/lightcolor/edit.png';

					$channelURL = get_permalink( $postID );

					$msg1 = __( 'Advanced Playlist Editor', 'ppv-live-webcams' );
					$msg2 = __( 'After editing playlist contents below, update it to apply changes. Last Updated:', 'ppv-live-webcams' );
					$msg3 = __( 'Playlist is loaded with web application (on access) and reloaded if necessary when users access', 'ppv-live-webcams' );
					$msg4 = __( 'First create a Schedule (Add new record), then Edit Videos (Add new record under Videos):', 'ppv-live-webcams' );
					$msg5 = __( 'videochat interface', 'ppv-live-webcams' );
					$msg6 = __( 'last time reloaded', 'ppv-live-webcams' );

					// ! jTable
					$contentCode .= <<<HTMLCODE
					<h3 class="ui header">$msg1</H3>
<form method="post" action="$playlistPage" name="adminForm" class="w-actionbox">
<label>Playlist Status</label> $activeCode <input class="ui button" type="submit" name="button" id="button" value="Update" />
<input type="hidden" name="updatePlaylist" id="updatePlaylist" value="$editPlaylist" />
<BR>$msg2 $playlistUpdated $timezone
<BR>$msg3 <a href='$channelURL'>$msg5</a> ($msg6:  $playlistLoaded $timezone).
</form>
<BR>
$msg4
	<div id="PlaylistTableContainer" style="width: 600px;"></div>
	<script type="text/javascript">

		jQuery(document).ready(function () {

		    //Prepare jTable
			jQuery('#PlaylistTableContainer').jtable({
				title: 'Playlist Contents',
				defaultSorting: 'Order ASC',
				toolbar: {hoverAnimation: false},
				actions: {
					listAction: '$ajaxurl&task=list',
					createAction: '$ajaxurl&task=create',
					updateAction: '$ajaxurl&task=update',
					deleteAction: '$ajaxurl&task=delete'
				},
				fields: {
					Id: {
						key: true,
						create: false,
						edit: false,
						list: false,
					},
					//CHILD TABLE DEFINITION
					Videos: {
                    title: 'Videos',
                    sorting: false,
                    edit: false,
                    create: false,
                    display: function (playlist) {
                        //Create an image that will be used to open child table
                        var vButton = jQuery('<IMG src="$videosImg" /><I>Edit Videos</I>');
                        //Open child table when user clicks the image
                        vButton.click(function () {
                            jQuery('#PlaylistTableContainer').jtable('openChildTable',
                                    vButton.closest('tr'),
                                    {
                                        title: 'Videos for Schedule ' + playlist.record.Scheduled,
                                        actions: {
                                            listAction: '$ajaxurl&task=videolist&item=' + playlist.record.Id,
                                            deleteAction: '$ajaxurl&task=videoremove&item=' + playlist.record.Id,
                                            updateAction: '$ajaxurl&task=videoupdate',
                                            createAction: '$ajaxurl&task=videoadd'
                                        },
                                        fields: {
                                            ItemId: {
                                                type: 'hidden',
                                                defaultValue: playlist.record.Id
                                            },
                                            Id: {
                                                key: true,
                                                create: false,
                                                edit: false,
                                                list: false
                                            },
											Video: {
												title: 'Video',
												options: '$ajaxurl&task=source',
												sorting: false
											},
											Start: {
												title: 'Start',
												defaultValue: '0',
											},
											Length: {
												title: 'Length',
												defaultValue: '-1',
											},
											Order: {
												title: 'Order',
												defaultValue: '0',
											},
	                                    }
                                    }, function (data) { //opened handler
                                        data.childTable.jtable('load');
                                    });
                        });
                        //Return image to show on the person row
                        return vButton;
                    }

                    },
					Scheduled: {
						title: 'Scheduled',
						defaultValue: '$currentDate',
						sorting: false
					},
					Repeat: {
						title: 'Repeat',
						type: 'checkbox',
						defaultValue: '0',
						values: { '0' : 'Disabled', '1' : 'Enabled' },
						sorting: false
					},
					Order: {
						title: 'Order',
						defaultValue: '0',
					}
				}
			});

			//Load item list from server
			jQuery('#PlaylistTableContainer').jtable('load');
		});
	</script>
	<STYLE>
	.ui-widget
	{
		z-index: 1000 !important;
	}
	</STYLE>

HTMLCODE;

					$contentCode .= '<BR>' . __( 'Schedule playlist items as: Year-Month-Day Hours:Minutes:Seconds in scheduling server timezone. In example, current server OS time', 'ppv-live-webcams' ) . ': ' . date( 'y-m-j h:i:s' ) . ' ' . __( 'in timezone', 'ppv-live-webcams' ) . ' ' . date_default_timezone_get() . ' (' . __( 'default timezone was', 'ppv-live-webcams' ) . ' ' . $defaultTimezone . ').';

					if ( date_default_timezone_get() ) {
						$contentCode .= '<BR>' . __( 'If the schedule time is in the past, each video is loaded in order and immediately replaces the previous video for the stream. Repeat will cause that videos to repeat in loop.', 'ppv-live-webcams' );
					}

					// restore default timezone
					if ( $defaultTimezone ) {
						date_default_timezone_set( $defaultTimezone );
					}

					$contentCode .= '<p>' . __( 'Scheduled videos play instead of performer live stream in videochat interface. Can be selected from all videos of user.', 'ppv-live-webcams' ) . '</p>';

					$content['playlist'] = $contentCode;

			}
		}
*/
					// ! content assets tab in dashboard
		if ( $options['micropaymentsAssets'] ?? false ) {
			if ( shortcode_exists( 'videowhisper_content_list' ) ) {
				if ( $_GET['assets'] ?? false ) {
					$active = 'assets';
				}
						$header['assets'] = __( 'Assets', 'ppv-live-webcams' );

						$content['assets'] = __( 'Select digital assets to show on room profile page.', 'ppv-live-webcams' );

						$this_page = self::getCurrentURL();

				if ( $_POST['save'] ?? false ) {
					if ( is_array( $_POST['selectedAssets'] ?? '' ) ) {
								$contentIDs = get_post_meta( $postID, 'contentIDs', true );
						if ( ! is_array( $contentIDs ) ) {
							$contentIDs = array();
						}

						foreach ( $_POST['selectedAssets'] as $selected ) {
							$selectedInt = intval( $selected );

							$sPost = get_post( abs( $selectedInt ) );
							if ( ! $sPost ) {
								return 'Post not found!';
							}
							if ( $sPost->post_author != $current_user->ID ) {
								return 'Not your post!';
							}

							if ( $selectedInt > 0 ) {
								if ( ! in_array( $selectedInt, $contentIDs ) ) {
									$contentIDs[] = $selectedInt; // add selected ID to show
								}
							}

							if ( $selectedInt < 0 ) {
								if ( in_array( -$selectedInt, $contentIDs ) ) {
									if ( ( $key = array_search( -$selectedInt, $contentIDs ) ) !== false ) {
										unset( $contentIDs[ $key ] ); // find and remove ID to hide
									}
								}
							}
						}

								update_post_meta( $postID, 'contentIDs', $contentIDs );

								$active = 'assets';

					}
				}

						// current
						$contentIDs = get_post_meta( $postID, 'contentIDs', true );
				if ( ! is_array( $contentIDs ) ) {
					$contentIDs = array();
				}

						$asset_page = intval( $_GET['asset_page'] ?? 0 );
				if ( $asset_page ) {
					$active = 'assets';
				}

				if ( $asset_page <= 0 ) {
					$asset_page = 1;
				}

						$post_type = array();
						$postTypes = explode( ',', $options['postTypesAssets'] );
				foreach ( $postTypes as $postType ) {
					$post_type[] = trim( $postType );
				}

						$args = array(
							'author'         => $current_user->ID,
							'post_type'      => $post_type,
							'orderby'        => 'modified',
							'order'          => 'DESC',
							'posts_per_page' => 8,
							'paged'          => $asset_page ? $asset_page : 1,
						);

						$postslist = new \WP_Query( $args ); // WP root namespace

						if ( $postslist->have_posts() ) :

							$content['assets'] .= '<form class="ui form" method="post" enctype="multipart/form-data" action="' . $this_page . '"  name="adminAssets">';

							// console.log(\'selectAll\');

							$content['assets'] .= '<table class="ui celled striped table ' . $options['interfaceClass'] . '">
<thead>
    <tr>
    <th> Select </th>
    <th>Asset</th>
    </tr>
</thead>
<tbody>';

							while ( $postslist->have_posts() ) :
								$postslist->the_post();

								$content['assets'] .= '<tr>';

								$content['assets'] .= '<td>
					<select class="ui ' . $options['interfaceClass'] . ' dropdown" id="selectedAssets" name="selectedAssets[]">
  <option value="' . get_the_id() . '" ' . ( in_array( get_the_id(), $contentIDs ) ? 'selected' : '' ) . '>Show</option>
  <option value="-' . get_the_id() . '" ' . ( ! in_array( get_the_id(), $contentIDs ) ? 'selected' : '' ) . '>Hide</option>
</select>
		</td>';

								$content['assets'] .= '<td> <a href="' . get_permalink() . '"><i class="box icon"></i>' . get_the_title() . get_the_post_thumbnail( get_the_id(), array( 150, 150 ), array( 'class' => 'alignright' ) ) . '</a> ' . ucwords( get_post_type() ) . ' </td>';

								$content['assets'] .= '</tr>';
							endwhile;
							$content['assets'] .= '</tbody>
  <tfoot>
  <tr><th colspan="5">';

							$content['assets'] .= '<div class="field inline">  <button class="ui ' . $options['interfaceClass'] . ' button" name="save" id="save" value="save" type="submit"><i class="cart icon"></i>' . __( 'Update Selection', 'ppv-live-webcams' ) . '</button>  </div>';

							if ( $asset_page > 1 ) {
								$content['assets'] .= '<a class="ui ' . $options['interfaceClass'] . ' button" href="' . add_query_arg( 'asset_page', $asset_page - 1, $this_page ) . '"> <i class="arrow left icon"></i>' . __( 'Previous Page', 'ppv-live-webcams' ) . '</a>';
							}
							if ( $asset_page < $postslist->max_num_pages ) {
								$content['assets'] .= '<a class="ui ' . $options['interfaceClass'] . ' button" href="' . add_query_arg( 'asset_page', $asset_page + 1, $this_page ) . '">' . __( 'Next Page', 'ppv-live-webcams' ) . ' <i class="arrow right icon"></i> </a>';
							}

							$content['assets'] .= '</th>
  </tr></tfoot></table>';

							$content['assets'] .= '</form>';

							wp_reset_postdata();
										else :
											$content['assets'] .= 'You have no digital assets to list for this room.';
											endif;

										// end products list

			}
		}

					// ! products tab in dashboard

		if ( $options['woocommerce'] ?? false ) {
			if ( shortcode_exists( 'products' ) ) {
				if ( $_GET['products'] ?? false ) {
					$active = 'products';
				}
						$header['products'] = __( 'Products', 'ppv-live-webcams' );

						$content['products'] = __( 'Select products to show on room profile page.', 'ppv-live-webcams' );

						$this_page = self::getCurrentURL();

				if ( $_POST['save'] ?? false ) {
					if ( is_array( $_POST['selected'] ?? false ) ) {
								$productIDs = get_post_meta( $postID, 'productIDs', true );
						if ( ! is_array( $productIDs ) ) {
							$productIDs = array();
						}

						foreach ( $_POST['selected'] as $selected ) {
							$selectedInt = intval( $selected );

							$sPost = get_post( abs( $selectedInt ) );
							if ( ! $sPost ) {
								return 'Post not found!';
							}
							if ( $sPost->post_author != $current_user->ID ) {
								return 'Not your post!';
							}

							if ( $selectedInt > 0 ) {
								if ( ! in_array( $selectedInt, $productIDs ) ) {
									$productIDs[] = $selectedInt; // add selected ID to show
								}
							}

							if ( $selectedInt < 0 ) {
								if ( in_array( -$selectedInt, $productIDs ) ) {
									if ( ( $key = array_search( -$selectedInt, $productIDs ) ) !== false ) {
										unset( $productIDs[ $key ] ); // find and remove ID to hide
									}
								}
							}
						}

								update_post_meta( $postID, 'productIDs', $productIDs );

								$active = 'products';

								// $content['products'] .= '--' . serialize($_POST['selected']) . '--';
					}
				}

						// current
						$productIDs = get_post_meta( $postID, 'productIDs', true );
				if ( ! is_array( $productIDs ) ) {
					$productIDs = array();
				}
						// $content['products'] .= '--' . serialize($productIDs) . '--';

						$product_page = intval( $_GET['product_page'] ?? 0 );
				if ( $product_page ) {
					$active = 'products';
				}

				if ( $product_page <= 0 ) {
					$product_page = 1;
				}

						$args = array(
							'author'         => $current_user->ID,
							'post_type'      => 'product',
							'orderby'        => 'modified',
							'order'          => 'DESC',
							'posts_per_page' => 8,
							'paged'          => $product_page ? $product_page : 1,
						);

						$postslist = new \WP_Query( $args ); // WP root namespace

						if ( $postslist->have_posts() ) :

							$content['products'] .= '<form class="ui form" method="post" enctype="multipart/form-data" action="' . $this_page . '"  name="adminForm">';

							// console.log(\'selectAll\');

							$content['products'] .= '<table class="ui celled striped table ' . $options['interfaceClass'] . '">
<thead>
    <tr>
    <th> Select </th>
    <th>Asset</th>
    </tr>
</thead>
<tbody>';

							while ( $postslist->have_posts() ) :
								$postslist->the_post();

								$content['products'] .= '<tr>';

								$content['products'] .= '<td>
					<select class="ui ' . $options['interfaceClass'] . ' dropdown" id="selected" name="selected[]">
  <option value="' . get_the_id() . '" ' . ( in_array( get_the_id(), $productIDs ) ? 'selected' : '' ) . '>Show</option>
  <option value="-' . get_the_id() . '" ' . ( ! in_array( get_the_id(), $productIDs ) ? 'selected' : '' ) . '>Hide</option>
</select>
		</td>';

								$content['products'] .= '<td> <a href="' . get_permalink() . '"><i class="box icon"></i>' . get_the_title() . get_the_post_thumbnail( get_the_id(), array( 150, 150 ), array( 'class' => 'alignright' ) ) . '</a> </td>';

								$content['products'] .= '</tr>';
							endwhile;
							$content['products'] .= '</tbody>
  <tfoot>
  <tr><th colspan="5">';

							$content['products'] .= '<div class="field inline">  <button class="ui ' . $options['interfaceClass'] . ' button" name="save" id="save" value="save" type="submit"><i class="cart icon"></i>' . __( 'Update Selection', 'ppv-live-webcams' ) . '</button>  </div>';

							if ( $product_page > 1 ) {
								$content['products'] .= '<a class="ui ' . $options['interfaceClass'] . ' button" href="' . add_query_arg( 'product_page', $product_page - 1, $this_page ) . '"> <i class="arrow left icon"></i>' . __( 'Previous Page', 'ppv-live-webcams' ) . '</a>';
							}
							if ( $product_page < $postslist->max_num_pages ) {
								$content['products'] .= '<a class="ui ' . $options['interfaceClass'] . ' button" href="' . add_query_arg( 'product_page', $product_page + 1, $this_page ) . '">' . __( 'Next Page', 'ppv-live-webcams' ) . ' <i class="arrow right icon"></i> </a>';
							}

							$content['products'] .= '</th>
  </tr></tfoot></table>';

							$content['products'] .= '</form>';

							wp_reset_postdata();
										else :
											$content['products'] .= 'You have no products to list for this room.';
											endif;

										// end products list

			}
		}

					// ! pictures tab
		if ( $options['picturegallery'] ) {
			if ( shortcode_exists( 'videowhisper_postpictures' ) ) {
				if ( self::inList( $userkeys, $options['pictures'] ) ) {

					/*
					$checked = '';
					if ($_GET['gallery_upload'] || $_GET['gallery_import']) $checked = 'active';
					if ($checked) $checked1 = true;

					$headerCode .= '<a class="item ' . $checked .'" data-tab="pictures">' . __('Pictures', 'ppv-live-webcams') . '</a>';
					$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="pictures">';
					*/

					if ( isset($_GET['gallery_upload']) || isset($_GET['gallery_import']) ) {
						$active = 'pictures';
					}
					$header['pictures'] = __( 'Pictures', 'ppv-live-webcams' );

					/*
														  $htmlCode .= '<div class="vwtab">
					<input type="radio" id="tab-pic" name="tab-group-1" '.$checked.'>
					<label class="vwlabel" for="tab-pic">Pictures</label>

					<div class="vwpanel">
					<div class="vwcontent">';
					*/
					$content['pictures']  = do_shortcode( "[videowhisper_postpictures_process post=\"$postID\"]" );
					$content['pictures'] .= do_shortcode( "[videowhisper_postpictures perpage=\"8\" post=\"$postID\"]" );

					$content['pictures'] .= '<p>' . __( 'Multiple profile pictures can show on webcam listing page. Each listing has own pictures.', 'ppv-live-webcams' ) . '</p>';

					/*
					$contentCode .= '
					<br style="clear:both">
					</div>
					';
					*/
				}
			}
		}

						// move this at bottom??
						$htmlCode .= apply_filters( 'vw_plw_dashboard', '', $postID );

					$htmlCode .= apply_filters( 'vw_plw_dashboard', '', $postID );

		if ( $atts['include_css'] ) {
			$htmlCode .= '<STYLE>' . html_entity_decode( stripslashes( $options['dashboardCSS'] ) ) . '</STYLE>';
		}

					$htmlCode .= '<style type="text/css">
.pagination, .pagination > LI {
display: inline !important;
padding: 5px;
}
</style>';

//! chat logs tab
$header['files'] = __( 'Logs', 'ppv-live-webcams' );

if ( $fileRemove = sanitize_textarea_field( $_GET['fileRemove'] ?? '' ) ) {
	$active = 'files';
}

$contentCode    = '';

$dir = $options['uploadsPath'];
if ( ! file_exists( $dir ) ) {
	mkdir( $dir );
}

$dir .= '/' . sanitize_file_name( $webcamPost->post_title );
if ( ! file_exists( $dir ) ) {
	mkdir( $dir );
}

$nonce = sanitize_text_field( $_GET['nonce'] ?? '' );
if ( $fileRemove &&  wp_verify_nonce( $nonce, 'fileRemove' . $webcamPost->ID ) ) 
{
	$fileName = base64_decode($fileRemove);

	//make sure it does not contain path traversal
	if (strpos($fileName, '/') === false && strpos($fileName, '\\') === false) {
		$filePath = $dir . '/' . $fileName;
		if (file_exists($filePath)) {
			unlink($filePath);
			$contentCode .= '<p>' . __('Deleted', 'ppv-live-webcams') . ' ' . $fileName . '</p>';
		}
	}
	
} else {
	if ($fileRemove) $contentCode .= 'Invalid request!';
}

//find chat log files of format  $dir . "/Log$day.html" and list with most recent first
$files = glob( $dir . '/Log*.html' );
if ( $files ) {
	rsort( $files );
}

if ($options['chatlogCleanup'] ?? false) {
	//delete files older that cleanup period (days)
	$cleanup = $options['chatlogCleanup'];
	$now = time();
	foreach ($files as $file) {
		$age = $now - filemtime($file);
		if ($age > $cleanup * 24 * 60 * 60) {
			unlink($file);
			//remove from list
			$key = array_search($file, $files);
			if ($key !== false) {
				unset($files[$key]);
			}
		}
	}
}

//list files with links to view (in new tab) and delete with wp_nonce
if (count($files)) {
	$contentCode .= '<h3 class="ui header">' . __('Room Chat Logs', 'ppv-live-webcams') . '</h3>';
	foreach ($files as $file) {
		$filename = basename($file);
		$age = time() - filemtime($file);
		$size = filesize($file) / 1000;
		//include wp_nonce for delete
		$encodedFilename = base64_encode($filename);
		$nonce = wp_create_nonce('fileRemove' . $webcamPost->ID);
		$contentCode .= '<a href="' . self::path2url($file). '" target="_blank">' . $filename . '</a> | ' . self::format_age($age) . ' | ' . $size . 'kb | <a class="ui small button" href="' . add_query_arg(array('fileRemove' => $encodedFilename , 'nonce' => $nonce), $this_page) . '">delete</a><br>';
	}
} else {
	$contentCode .= __('No chat log files found in this room.', 'ppv-live-webcams') ;  
}

if ($options['chatlogCleanup'] ?? false) $contentCode .= '<p><small>*' . __('Older log files are automatically deleted after a number of days:', 'ppv-live-webcams') . ' ' . $options['chatlogCleanup'] . '</small></p>';


//also find files other that chat logs $dir . '/Log*.html' and list with most recent first
$files = glob( $dir . '/*' );
if ( $files ) {
	rsort( $files );
}

//remove log files Log*.html (already listed)
foreach ($files as $key => $file) {
	if (strpos($file, 'Log') !== false) {
		unset($files[$key]);
	}
	//also remove folders
	if (is_dir($file)) {
		unset($files[$key]);
	}
}

if (count($files)) {
	$contentCode .= '<h3 class="ui header">' . __('Room Uploads', 'ppv-live-webcams') . '</h3>';
	foreach ($files as $file) {
		$filename = basename($file);
		$age = time() - filemtime($file);
		$size = filesize($file) / 1000;
		//include wp_nonce for delete
		$encodedFilename = base64_encode($filename);
		$nonce = wp_create_nonce('fileRemove' . $webcamPost->ID);
		$contentCode .= '<a href="' . self::path2url($file). '" target="_blank">' . $filename . '</a> | ' . self::format_age($age) . ' | ' . $size . 'kb | <a href="' . add_query_arg(array('fileRemove' => $encodedFilename , 'nonce' => $nonce), $this_page) . '">delete</a><br>';
	}
} else {
	$contentCode .= __('No uploads found in this room.', 'ppv-live-webcams') ;  
}

//count total file size in $dir including subfolders
$size = 0;
foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file) {
	$size += $file->getSize();
}
$contentCode .= '<br><p>' . __('Total room folder size:', 'ppv-live-webcams') . ' ' . $size / 1000 . 'kb</p>';


$content['files'] = $contentCode;

					// ! bans tab

					/*
					$checked = '';
					if ($banRemove = sanitize_textarea_field($_GET['banRemove'])) $checked = 'active';
					if ($checked) $checked1 = true;

					$headerCode .= '<a class="item ' . $checked .'" data-tab="bans">' . __('Bans', 'ppv-live-webcams') . '</a>';
					$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="bans">';
					*/

		if ( $options['bans'] ?? false ) {
			if ( $banRemove = sanitize_textarea_field( $_GET['banRemove'] ?? '' ) ) {
				$active = 'bans';
			}
			$header['bans'] = __( 'Bans', 'ppv-live-webcams' );
			$contentCode    = '';

			$bans = get_post_meta( $postID, 'bans', true );

			$bansUpdate = false;
			$banInfo = '';

			if ( $banRemove && $bans ) {
				if ( is_array( $bans ) ) {
					foreach ( $bans as $key => $ban ) {
						if ( $banRemove == base64_encode( $ban['user'] . $ban['ip'] . $ban['expires'] ) ) {
								unset( $bans[ $key ] );
								$bansUpdate = 1;
								$banInfo   .= '<br>' . __( 'Cleared ban for', 'ppv-live-webcams' ) . ': ' . $ban['user'];
						}
					}
				}

				if ( ! $bansUpdate ) {
					$banInfo .= __( 'Not found!', 'ppv-live-webcams' );
				}
			}

			// clean bans
			if ( $bans ) {
				foreach ( $bans as $key => $ban ) {
					if ( $ban['expires'] < time() ) {
							unset( $bans[ $key ] );
							$bansUpdate = 1;
					}
				}
			}
			if ( $bansUpdate ) {
				update_post_meta( $postID, 'bans', $bans );
			}
				/*
						$htmlCode .= '<div class="vwtab">
						<input type="radio" id="tab-bans" name="tab-group-1" '.$checked.'>
						<label class="vwlabel" for="tab-bans">' . __('Bans', 'ppv-live-webcams') . '</label>

						<div class="vwpanel">
						<div class="vwcontent">';
						*/
				$contentCode .= '<h3 class="ui header">' . __( 'Bans', 'ppv-live-webcams' ) . '</h3>' . $banInfo;
			if ( $bans ) {
				foreach ( $bans as $ban ) {
					$contentCode .= '- ' . $ban['user'] . ' ' . $ban['ip'] . ' Expires: ' . self::format_age( $ban['expires'] - time() ) . ' <a href="' . add_query_arg( array( 'banRemove' => urlencode( base64_encode( $ban['user'] . $ban['ip'] . $ban['expires'] ) ) ), $this_page ) . '">remove</a><br>';
				}
			} else {
				$contentCode .= __( 'There are no bans for this room.', 'ppv-live-webcams' );
			}

						$contentCode .= '<p>' . __( 'Bans occur when performer kicks or bans users from videochat interface. Kicks result in short bans (15 minutes) and full bans have longer cooldown (1 week). Bans prevent viewers from accessing or remaining in chat room.', 'ppv-live-webcams' ) . '</p>';

						$content['bans'] = $contentCode;
						/*
						$contentCode .= '
						<br style="clear:both">
						</div>';
						*/
		}

					// ! credits tabs

					// update clients count
					$uid = get_current_user_id();
					self::billCount( $uid, $options );

					self::billCountRoom( $postID, $options );
					self::billSessions( 0, $postID );

					// MicroPayments Tab
		if ( $options['performerWallet'] ?? false ) {
			if ( $options['wallet'] == 'MicroPayments' || $options['walletMulti'] >= 1 ) {
				if ( shortcode_exists( 'videowhisper_transactions' ) ) {

		if ( isset( $_GET['orderby'] ) || get_query_var( 'paged' ) ) {
						$active = 'MicroPayments';
					}

					$header['MicroPayments'] = __( 'MicroPayments', 'ppv-live-webcams' );

					$content['MicroPayments'] = '<h3 class="ui header">' . __( 'MicroPayments Transactions', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[videowhisper_transactions user_id="' . $uid . '"]' );

					$clientsP              = get_user_meta( $uid, 'paidSessionsPrivate', true );
					$clientsG              = get_user_meta( $uid, 'paidSessionsGroup', true );
					$content['MicroPayments'] .= '<br>' . __( 'Logged days', 'ppv-live-webcams' ) . ': ' . round( $options['ppvKeepLogs'] / 3600 / 24, 2 );
					$content['MicroPayments'] .= '<br>' . __( 'Logged private sessions billed', 'ppv-live-webcams' ) . ': ' . $clientsP;
					$content['MicroPayments'] .= '<br>' . __( 'Logged group sessions billed', 'ppv-live-webcams' ) . ': ' . $clientsG;
					$content['MicroPayments'] .= '<br>* ' . __( 'Session logs are kept for limited time and transaction logs forever.', 'ppv-live-webcams' );

				}
			}
		}


		// WooWallet Tab
		if ( $options['performerWallet'] ) {
			if ( $options['wallet'] == 'WooWallet' || $options['walletMulti'] >= 1 ) {
				if ( shortcode_exists( 'woo-wallet' ) ) {
					/*
					$checked = '';
					if ($_GET['wallet_action'] || get_query_var('wallet_action')) $checked = 'active';
					if ($checked) $checked1 = true;

					$headerCode .= '<a class="item ' . $checked .'" data-tab="woowallet">' . __('TeraWallet', 'ppv-live-webcams') . '</a>';
					$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="woowallet">';

					$contentCode .= '<h3 class="ui header">' . __('TeraWallet (WooCommerce)', 'ppv-live-webcams') . '</h3>' . do_shortcode('[woo-wallet]') ;
					*/

					if ( isset( $_GET['wallet_action'] ) || get_query_var( 'wallet_action' ) ) {
						$active = 'woowallet';
					}
					$header['woowallet'] = __( 'TeraWallet', 'ppv-live-webcams' );

					$content['woowallet'] = '<h3 class="ui header">' . __( 'TeraWallet (WooCommerce)', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[woo-wallet]' );

					$clientsP              = get_user_meta( $uid, 'paidSessionsPrivate', true );
					$clientsG              = get_user_meta( $uid, 'paidSessionsGroup', true );
					$content['woowallet'] .= '<br>' . __( 'Logged days', 'ppv-live-webcams' ) . ': ' . round( $options['ppvKeepLogs'] / 3600 / 24, 2 );
					$content['woowallet'] .= '<br>' . __( 'Logged private sessions billed', 'ppv-live-webcams' ) . ': ' . $clientsP;
					$content['woowallet'] .= '<br>' . __( 'Logged group sessions billed', 'ppv-live-webcams' ) . ': ' . $clientsG;
					$content['woowallet'] .= '<br>* ' . __( 'Session logs are kept for limited time and transaction logs forever.', 'ppv-live-webcams' );

					/*
					$contentCode .= '
					<br style="clear:both">
					</div>';
					*/
				}
			}
		}

					// MyCred Transactions
		if ( $options['performerWallet'] ) {
			if ( $options['wallet'] == 'MyCred' || $options['walletMulti'] >= 1 ) {
				if ( shortcode_exists( 'mycred_history' ) ) {
					/*
					$checked = '';
					if ($_GET['page'] || get_query_var('page')) $checked = 'active';
					if ($checked) $checked1 = true;

					$headerCode .= '<a class="item ' . $checked .'" data-tab="transactions">' . __('My Credits', 'ppv-live-webcams') . '</a>';
					$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="transactions">';

					$contentCode .= '<h3 class="ui header">' . __('My Credits', 'ppv-live-webcams') . '</h3>' . do_shortcode('[mycred_history user_id="current"]') ;
					*/
					if ( isset($_GET['page']) || get_query_var( 'page' ) ) {
						$active = 'mycred';
					}
					$header['mycred'] = __( 'MyCred', 'ppv-live-webcams' );

					$content['mycred'] = '<h3 class="ui header">' . __( 'My Credits', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[mycred_history user_id="current"]' );

					$clientsP = get_user_meta( $uid, 'paidSessionsPrivate', true );
					$clientsG = get_user_meta( $uid, 'paidSessionsGroup', true );

					$content['mycred'] .= '<br>' . __( 'Logged days', 'ppv-live-webcams' ) . ': ' . round( $options['ppvKeepLogs'] / 3600 / 24, 2 );
					$content['mycred'] .= '<br>' . __( 'Logged private sessions billed', 'ppv-live-webcams' ) . ': ' . $clientsP;
					$content['mycred'] .= '<br>' . __( 'Logged group sessions billed', 'ppv-live-webcams' ) . ': ' . $clientsG;
					$content['mycred'] .= '<br>* ' . __( 'Session logs are kept for limited time and transaction logs forever.', 'ppv-live-webcams' );

					/*
					$contentCode .= '
					<br style="clear:both">
					</div>';
					*/
				}
			}
		}



		//reports

		if ($options['performerReports'] ?? false )
		{
		if ( isset( $_POST['deltaDate'] ) || get_query_var( 'deltaDate' ) ) $active = 'reports';

		if ( ! $active ) $active = 'reports';

		$header['reports'] =  __( 'Reports', 'ppv-live-webcams' ) ;
		$content['reports'] = '<h3 class="ui header">' . __( 'Reports', 'ppv-live-webcams' ) . '</h3>';

		if ($options['statsBonusRate'] && $options['statsBonusOnline']) $content['reports'] .= do_shortcode( '[videowhisper_room_rate roomid="'. $postID . '"]' );

		$content['reports'] .= do_shortcode( '[videowhisper_reports roomid="'. $postID . '"]' );


					// ! overview tab
					/*
					$checked = '';
					if (!$checked1) $checked = 'active';


					$headerCode .= '<a class="item ' . $checked .'" data-tab="overview">' . __('Overview', 'ppv-live-webcams') . '</a>';
					$contentCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment ' . $checked .'" data-tab="overview">';

					*/
		}

		if ( $options['performerOverview'] ) {

			$header['overview'] = __( 'Overview', 'ppv-live-webcams' );
			if ( ! $active ) $active = 'overview';

			$contentCode = '';
			/*
			$htmlCode .= '<div class="vwtab">
			<input type="radio" id="tab-1" name="tab-group-1" '.$checked.'>
			<label class="vwlabel" for="tab-1">' . __('Overview', 'ppv-live-webcams') . '</label>

			<div class="vwpanel">
			<div class="vwcontent">
			';
			*/
			$thumbFilename = '';

			$attach_id = get_post_thumbnail_id( $postID );
			if ( $attach_id ) {
				$thumbFilename = get_attached_file( $attach_id );
			}
			$url = get_permalink( $postID );

			$noCache = '?' . ( floor( time() / 10 ) % 100 );

			$contentCode .= '<h3 class="ui header">' . ucwords($options['custom_post']) . ' '. __( 'Room Listing Overview', 'ppv-live-webcams' ) . '</h3>';

			// $contentCode .= $webcamPost->post_content;

			// get/set performer stream name
			$stream        = get_post_meta( $postID, 'performer', true );
			$performerName = self::performerName( $current_user, $options );

			if ( ! $stream ) {
				update_post_meta( $postID, 'performer', $performerName );
				update_post_meta( $postID, 'performerUserID', $current_user->ID );

			}

			$imageCode = '';

			if ($thumbFilename) if ( file_exists( $thumbFilename ) ) {
				$imageCode .= '<a href="' . $url . '"><IMG class="ui small rounded bordered image right floated" src="' . self::path2url( $thumbFilename ) . $noCache . '"></a>';
			} else {
				$imageCode .= '<a href="' . $url . '"><IMG class="ui small rounded bordered image right floated" SRC="' . dirname( plugin_dir_url( __FILE__ ) ) . '/no-picture.png"></a>';
			}

			if (!self::isPerformer(get_current_user_id(), $webcamPost->ID)) $contentCode .= 'Warning: You are not detected as performer!';

			$contentCode .= '<table class="ui ' . $options['interfaceClass'] . ' selectable striped table two">';

			$contentCode .= '<tr><td>' . __( 'Listing (Room) Name', 'ppv-live-webcams' ) . ': <b>' . $webcamPost->post_title . '</b>' . $imageCode . '</td></tr>';
			$contentCode .= '<tr><td>' . __( 'Current Room Performer', 'ppv-live-webcams' ) . ': ' . $stream . '</td></tr>';
			$contentCode .= '<tr><td>' . __( 'Current Room Performer ID', 'ppv-live-webcams' ) . ': ' . get_post_meta( $postID, 'performerUserID', true ) . '</td></tr>';
			$contentCode .= '<tr><td>' . __( 'Your Performer Name', 'ppv-live-webcams' ) . ': ' . $performerName . '</td></tr>';
			$contentCode .= '<tr><td>' . __( 'Room Label', 'ppv-live-webcams' ) . ': ' . ($vw_roomLabel ?? '') . '</td></tr>';
			$contentCode .= '<tr><td>' . __( 'Room Status', 'ppv-live-webcams' ) . ': ' . $webcamPost->post_status . '</td></tr>';
			$contentCode .= '<tr><td>' . __( 'Room URL', 'ppv-live-webcams' ) . ': ' . $url . '</td></tr>';
			$contentCode .= '<tr><td>' . __( 'Room Slug', 'ppv-live-webcams' ) . ': ' . $webcamPost->post_name . '</td></tr>';
			$contentCode .= '<tr><td>' . __( 'Room ID', 'ppv-live-webcams' ) . ': ' . $webcamPost->ID . '</td></tr>';
			$contentCode .= '<tr><td>' . __( 'Room Owner ID', 'ppv-live-webcams' ) . ': ' . $webcamPost->post_author . '</td></tr>';

			$featured = get_post_meta( $postID, 'vw_featured', true );
			if ( empty( $featured ) ) {
				update_post_meta( $postID, 'vw_featured', 0 );
			}
			$contentCode .= '<tr><td>' . __( 'Featured', 'ppv-live-webcams' ) . ': ' . ( $featured ? __( 'Yes', 'ppv-live-webcams' ) : __( 'No', 'ppv-live-webcams' ) ) . '</td></tr>';

			if ( $CPM = self::clientCPM( $webcamPost->post_title, $options, $postID ) ) {
				$contentCode .= '<tr><td>' . __( 'Client CPM', 'ppv-live-webcams' ) . ': ' . $CPM . '</td></tr>';
				$contentCode .= '<tr><td>' . __( 'Earning Ratio', 'ppv-live-webcams' ) . ': ' . self::performerRatio( $webcamPost->post_title, $options, $postID ) * 100 . '%' . '</td></tr>';
			}

			if ( $options['videosharevod'] ) {
				if ( shortcode_exists( 'videowhisper_videos' ) ) {
							$vw_videos    = get_post_meta( $postID, 'vw_videos', true );
							$contentCode .= '<tr><td>' . __( 'Videos', 'ppv-live-webcams' ) . ': ' . ( $vw_videos ? __( 'Yes', 'ppv-live-webcams' ) : __( 'No', 'ppv-live-webcams' ) ) . '</td></tr>';
				}
			}

			if ( $options['picturegallery'] ) {
				if ( shortcode_exists( 'videowhisper_pictures' ) ) {
							$vw_pictures  = get_post_meta( $postID, 'vw_pictures', true );
							$contentCode .= '<tr><td>' . __( 'Pictures', 'ppv-live-webcams' ) . ': ' . ( $vw_pictures ? __( 'Yes', 'ppv-live-webcams' ) : __( 'No', 'ppv-live-webcams' ) ) . '</td></tr>';
				}
			}
			/*
			if ($options['transcoding'])
			{
			$vw_transcode = get_post_meta($postID, 'vw_transcode',true);
			$contentCode .= '<tr><td>' . __('HTML5 Transcoding', 'ppv-live-webcams') . ': ' . ($vw_transcode?__('Yes', 'ppv-live-webcams'):__('No', 'ppv-live-webcams')) . '</td></tr>';
			}


			if ($options['multicamMax'])
			{
			$vw_multicam = get_post_meta($postID, 'vw_multicam', true);
			$contentCode .= '<tr><td>' . __('Multiple Cameras', 'ppv-live-webcams') . ': ' . ($vw_multicam?"Yes ($vw_multicam extra)":'No') . '</td></tr>';
			}
			*/

			if ( $options['playlists'] ) {
				if ( self::inList( $userkeys, $options['schedulePlaylists'] ) ) {
							$vw_playlistActive = get_post_meta( $postID, 'vw_playlistActive', true );
							$contentCode      .= '<tr><td>' . __( 'Scheduled Playlist', 'ppv-live-webcams' ) . ': ' . ( $vw_playlistActive ? __( 'Yes', 'ppv-live-webcams' ) : __( 'No', 'ppv-live-webcams' ) ) . '</td></tr>';
				}
			}

			$contentCode .= '<tr><td>' . __( 'Description', 'ppv-live-webcams' ) . ': ' . $webcamPost->post_content . '</td></tr>';

			$contentCode .= '</table>';

			$content['overview'] = $contentCode;
		}
					/*
					$contentCode .= '
					<br style="clear:both">
					</div>
					';
					*/

					/*
					//build tabs
					$headerCode .= '</div>';
					$htmlCode .= $headerCode . $contentCode ;
					*/

			if ( ! $active ) if ( $options['performerSetup'] ) $active = 'setup';

			$htmlCode .= self::sectionsLayout( $header, $content, $active, $options['performerLayout'], $webcamPost->post_title );

		if ( $options['dashboardMessageBottom'] ) {
			$htmlCode .= '<div id="performerDashboardMessageBottom" class="ui ' . $options['interfaceClass'] . ' segment">' . html_entity_decode( stripslashes( $options['dashboardMessageBottom'] ) ) . '</div>';
		}

					$htmlCode .= '<br style="clear:both">';

					return $htmlCode;
	}


	// end performer dashboard

	static function generateName( $fn ) {
		$ext = strtolower( pathinfo( $fn, PATHINFO_EXTENSION ) );

		// unpredictable name
		return md5( uniqid( $fn, true ) ) . '.' . $ext;
	}


	static function videowhisper_cam_message( $atts ) {
		// send a (paid) question or message to webcam profile

		self::enqueueUI();
		$htmlCode = '';

		$options = self::getOptions();

		if ( ! is_user_logged_in() ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' header"> <i class="sign in icon"></i> ' . __( 'Login to send questions or messages!', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="1" role="' . $options['roleClient'] . '"]' );
		}
		// return __('Login to send questions or messages!','ppv-live-webcams') . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __('Login', 'ppv-live-webcams') . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __('Register', 'ppv-live-webcams') . '</a>';

		$atts = shortcode_atts(
			array(
				'post_id' => 0, // for setup
			),
			$atts,
			'videowhisper_cam_message'
		);
		$atts = array_map('esc_attr', $atts);

		// room id
		$postID = intval( $atts['post_id'] );
		if ( ! $postID ) {
			$postID = intval( $_GET['webcam_id'] ); // pass by URL
		}

		if ( $postID ) {
			$post = get_post( $postID );
		}
		if ( ! $post ) {
			$postID = 0;
		}

		if ( ! $postID ) {
			return 'Error: Could not find this webcam room! #' . $postID;
		}

		$room = sanitize_file_name( $post->post_title );

		$sender_id = get_current_user_id();
		$balance   = self::balance( $sender_id );

		$messagesCost = floatval( get_post_meta( $postID, 'question_price', true ) );
		if ( ! $messagesCost ) {
			$messagesCost = floatval( $options['messagesCost'] );
		}

		$showNew = 1;

		$closed = intval( get_post_meta( $postID, 'question_closed', true ) );

		if ( $closed ) {
			return '<div id="warningMessage" class="ui ' . $options['interfaceClass'] . ' message warning">' . __( 'Messages are currently closed.', 'ppv-live-webcams' ) . '</div>';
		}

		if ( $_POST['newpaidmessage'] ?? false ) {
			$showNew = 0;

			if ( $balance < $messagesCost ) {
				$htmlCode .= '<div id="warnMessages" class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'You need cost of message in balance to send it.', 'ppv-live-webcams' ) . '<br>' . $messagesCost . '/' . $balance . '<br><a class="ui button primary qbutton" href="' . get_permalink( $options['balancePage'] ) . '">' . __( 'Wallet', 'ppv-live-webcams' ) . '</a>' . '</div>';
			} else {
				global $wpdb;
				$table_messages = $wpdb->prefix . 'vw_vmls_messages';

				$ztime       = time();
				$paidmessage = wp_encode_emoji( stripslashes( sanitize_textarea_field( $_POST['paidmessage'] ) ) );

				$sdate = intval( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(sdate) FROM `$table_messages` WHERE sender_id=%d", $sender_id ) ) );
				if ( $sdate > $ztime - 10 ) {
					return 'Error: You just sent a paid message or shortcode was already executed. Try again after 10s.'; // 10s
				}

				$meta                = array();
				$meta['attachments'] = array();

				// Attachments
				$countfiles = 0;
				if ( ! empty( $_FILES ) ) {
					if ( $_FILES['attachments'] ) {
						$countfiles = count( $_FILES['attachments'] );
					}
				}

				if ( $options['attachment_extensions'] ) {
					if ( $countfiles ) {
						$dir = $options['uploadsPath'];
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}

						$dir .= "/$room";
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}

						$dir .= '/_attachments';
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}

						$dir .= '/';

						// Looping all files
						for ( $i = 0;$i < $countfiles;$i++ ) if ( isset($_FILES['attachments']['name'][ $i ]) ) {

							$filename = $_FILES['attachments']['name'][ $i ];
							if ( ! $filename ) {
								break;
							}

							$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

							if ( ! in_array( $ext, self::extensions_attachment() ) ) {
								return '<div class="ui message error">' . __( 'This attachment extension is not allowed:', 'ppv-live-webcams' ) . ' ' . $ext . '<br>' . __( 'Allowed attachment extensions:', 'ppv-live-webcams' ) . ' ' . $options['attachment_extensions'] . '<br>' . __( 'Try again:', 'ppv-live-webcams' ) . ' ' . $paidmessage . '</div>';
							}

							if ( ! file_exists( $_FILES['attachments']['tmp_name'][ $i ] ) ) {
								return 'Attachment upload failed. Report to webmaster and include link in message, until sorted out.';
							}

							if ( $options['attachmentSize'] ) {
								if ( filesize( $_FILES['attachments']['tmp_name'][ $i ] ) > $options['attachmentSize'] ) {
														return '<div class="ui message error">' . __( 'This attachment is too big:', 'ppv-live-webcams' ) . ' ' . $filename . '<br>' . __( 'Allowed attachment size:', 'ppv-live-webcams' ) . ' ' . $options['attachmentSize'] . '<br>' . __( 'Try again:', 'ppv-live-webcams' ) . ' ' . $paidmessage . '</div>';
								}
							}

							// Upload file
							$path = $dir . self::generateName( $filename );

							$meta['attachments'][ $i ] = array(
								'name' => sanitize_text_field( $filename ),
								'file' => $path,
								'size' => filesize( $_FILES['attachments']['tmp_name'][ $i ] ),
							);

							//per file
							$file = array(
							            'name' => $_FILES['attachments']['name'][ $i ],
							            'type' => $_FILES['attachments']['type'][ $i ],
							            'tmp_name' => $_FILES['attachments']['tmp_name'][ $i ],
							            'error' => $_FILES['attachments']['error'][ $i ] ,
							            'size' => $_FILES['attachments']['size'][ $i ]
							        );

							$errorUp = self::handle_upload( $file, $path ); // handle trough wp_handle_upload()
							if ( $errorUp ) {
								$htmlCode .= '<br>' . 'Error uploading ' . esc_html( $filename . ':' . $errorUp );
							}
						}
					}
				}

				// message sender
				$sender = wp_get_current_user();

				// recordings
				$meta['recordings'] = array();

				if ( $options['attachmentRecord'] ) {
					$recordings = sanitize_text_field( $_POST['recordings'] ?? false );
					if ( $recordings ) {

						$optionsRecorder = get_option( 'VWinlineRecorderOptions' );
						$recordingFiles  = explode( ',', $recordings );

						foreach ( $recordingFiles as $recordingFile ) {
							if ( $recordingFile ) {
								if ( file_exists( $filePath = $optionsRecorder['uploadsPath'] . '/' . $sender->user_login . '/' . $recordingFile ) ) {
									$meta['recordings'][] = array(
										'name' => sanitize_text_field( $recordingFile ),
										'file' => $filePath,
										'size' => filesize( $filePath ),
									);
								} else {
									$htmlCode .= ' Recording file not found: ' . $filePath;
								}
							}
						}
					}
				}

				$meta['cost'] = $messagesCost;

				$metaS = serialize( $meta );
				$sqlI = $wpdb->prepare(
					"INSERT INTO `$table_messages` ( `sender_id`, `webcam_id`, `reply_id`, `sdate`, `message`, `meta`) VALUES ( %d, %d, %d, %d, %s, %s)",
					$sender_id, $postID, 0, $ztime, $paidmessage, $metaS
				);

				$wpdb->query( $sqlI );
				$messageID = $wpdb->insert_id;

				if ( ! $messageID ) {
					return 'Error: Sending message failed! ' . $sqlI;
				}

					// notification to room author
					$author = get_userdata( $post->post_author );
					$link   = get_permalink( $options['p_videowhisper_webcams_perfomer'] ?? 0 );
				if ( $author ) {
					$mailed = wp_mail( $author->user_email, $options['messagesSubject'] . ' ' . ( $sender->display_name ? $sender->display_name : $sender->user_login ), $options['messagesText'] . "\r\n" . $link);

					//SMS notification
					if ($options['sms_number'])
					{
							$sms_number = get_user_meta( $author->ID, 'sms_number', true );
							if ($sms_number)
							{
								//cooldown
								$sms_last =  get_user_meta( $author->ID, 'sms_last', true );
								if (!$sms_last || $sms_last < ( time() - $options['sms_cooldown']) )
								{

								$sms_message = $options['messagesSubject'] . ' ' . ( $sender->display_name ? $sender->display_name : $sender->user_login ) . ' ' . $link ;

								if ($options['wp_sms']) if ( function_exists('wp_sms_send') ) wp_sms_send($sms_number,  $sms_message);

								if ($options['wp_sms_twilio']) if ( function_exists('twl_send_sms') )
								{
									$response = twl_send_sms( array( 'number_to' => $sms_number, 'message' => $sms_message ) );
									if( is_wp_error( $response ) ) $htmlCode .= '<div class="ui message"> SMS Error: ' . esc_html( $response->get_error_message() ) . '</div>';
								}

								update_user_meta( $author->ID, 'sms_last', time() );
								}
							}
					}

				} else {
					$htmlCode .= 'Error: User not found to notify!';
				}

				if ( ! $mailed ) {
					$htmlCode .= 'Warning: Notification email was not sent. Check WordPress mailing settings!';
				}

				// pay for message
				if ( $messagesCost ) {
					self::transaction( $ref = 'paid_message', $sender_id, - $messagesCost, __( 'Paid Message to', 'ppv-live-webcams' ) . ' <a href="' . self::roomURL( $post->post_name ) . '">' . $post->post_title . '</a>', $messageID, $paidmessage, $options );
				}

				$messageURL = add_query_arg(
					array(
						'view'    => 'messages',
						'msg' => self::to62( $messageID ),
					),
					get_permalink( $postID )
				);

				$htmlCode .= '<div id="successMessage" class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'Your message was sent.', 'ppv-live-webcams' ) .
					'<br><a class="ui button primary qbutton" href="' . $messageURL . '">' . __( 'View Message', 'ppv-live-webcams' ) . ' ' . '</a> ' . '</div>';

			}
		}

		if ( $showNew ) {
			$this_page = self::getCurrentURL();
			$action    = add_query_arg(
				array(
					'view'     => 'messages',
					'messages' => 'add',
				),
				$this_page
			);

			$htmlCode .= '<form method="post" enctype="multipart/form-data" action="' . $action . '" name="messageForm" class="ui form ' . $options['interfaceClass'] . ' segment">
	<div class="field"><textarea class="ui field fluid" name="paidmessage" id="paidmessage" placeholder="' . __( 'Your Question or Message', 'ppv-live-webcams' ) . '"></textarea> </div>';

			$htmlCode .= ( $options['attachment_extensions'] ? '
	<div class="field"><label><i class="attach icon"></i> ' . __( 'Attachments (Optional)', 'ppv-live-webcams' ) . '</label>
	<input type="file" name="attachments[]" id="attachments" multiple placeholder="Attachments"><br><small>' . __( 'Supports', 'ppv-live-webcams' ) . ' : ' . $options['attachment_extensions'] . '</small>
	</div>
	' : '' );

			if ( $options['attachmentRecord'] ) {
				if ( shortcode_exists( 'videowhisper_recorder_inline' ) ) {
					if ( isset($_GET['view']) && $_GET['view'] == 'messages' ) {
										$htmlCode .= do_shortcode( '[videowhisper_recorder_inline label="Attach Recordings (Optional)"]' );
					} else {
						$htmlCode .= '<div class="field">' . __( 'Open in full view to add video recordings:', 'ppv-live-webcams' ) . '  <a class="ui button tiny compact" href="' . add_query_arg( 'view', 'messages', get_permalink( $postID ) ) . '">' . __( 'Messages', 'ppv-live-webcams' ) . '</a> </div>';
					}
				}
			}

			$htmlCode .= '<div class="field"><input class="ui button videowhisperButton" type="submit" name="newpaidmessage" id="newpaidmessage" value="' . __( 'Send Message', 'ppv-live-webcams' ) . '" /> </div>

	<input name="post_id" id="post_id" type="hidden" value="' . $postID . '">
	<div class="ui message small">'
				. ( shortcode_exists( 'videowhisper_wallet' ) ? do_shortcode( '[videowhisper_wallet]' ) : __( 'Your current balance', 'ppv-live-webcams' ) . ': ' . $balance . ' ' . $options['currency'] )
				. __( 'Cost per question or message', 'ppv-live-webcams' ) . ': ' . $messagesCost . ' ' . $options['currency'] . '
	</div>
	</form>';
		}

		return $htmlCode;

	}



	static function messageReplies( $messageCode ) {

		$id = self::to10( $messageCode );

		if ( ! $id ) {
			return 'Error: Incorrect message code! #' . $messageCode;
		}

		global $wpdb;
		$table_messages = $wpdb->prefix . 'vw_vmls_messages';

		$sqlr     = $wpdb->prepare("SELECT * FROM $table_messages WHERE id=%d ORDER BY sdate ASC, id DESC LIMIT 0, 100", $id);
		$question = $wpdb->get_row( $sqlr );
		if ( ! $question ) {
			return 'Error: Message not found! #' . $id;
		}

		$post = get_post( $question->webcam_id );
		if ( ! $question ) {
			return 'Error: Room listing not found! #' . $questio->webcam_id;
		}

		$options = self::getOptions();

		$htmlCode = '<div class="ui segment ' . $options['interfaceClass'] . '">';
		$htmlCode .= '<h4>' . ' <i class="users icon big"></i> ' . $post->post_title . ' / ' . ' #' . $messageCode . '</h4>';
		// list messages

		$sqlRs = $wpdb->prepare("SELECT * FROM $table_messages WHERE id=%d OR reply_id=%d ORDER BY sdate ASC, id DESC LIMIT 0, 100", $id, $id);

		$results = $wpdb->get_results( $sqlRs );
		if ( $wpdb->num_rows > 0 ) {
			$this_page = self::getCurrentURL();

			$htmlCode .= '<div class="ui list">';

			foreach ( $results as $message ) {
				$sender = get_userdata( $message->sender_id );

				$htmlCode .= '<div class="item"> <i class="mail icon big"></i>';
				$htmlCode .= '<div class="content">';
				$htmlCode .= '<div class="header">' . ' <i class="user icon"></i>' . $sender->user_nicename . ' <i class="clock icon"></i>' . date( DATE_RFC2822, $message->sdate ) . '</div>';
				$htmlCode .= '<div class="ui segment ' . $options['interfaceClass'] . '">' . stripslashes( $message->message );

				$attachmentsCount = 0;
				$recordingsCount  = 0;
				if ( $message->meta ) {
					$meta = unserialize( $message->meta );
					if ( is_array( $meta ) ) {
						if ( array_key_exists( 'attachments', $meta ) ) {
							$attachmentsCount = count( $meta['attachments'] );
						}
						if ( array_key_exists( 'recordings', $meta ) ) {
							$recordingsCount = count( $meta['recordings'] );
						}
					}
				}

				if ( $recordingsCount ) {
					$htmlCode .= '<br>';
					foreach ( $meta['recordings'] as $attachment )
						if ( file_exists( $attachment['file'] ) )
						{
						$htmlCode .= '<br><a class="ui label small" href="' . self::path2url( $attachment['file'] ) . '"> <i class="file video icon"></i>' . $attachment['name'] . ' ' . self::humanSize( filesize( $attachment['file'] ) ) . ' </a> ';

						}
						else $htmlCode .=  $attachment['name'] . ' missing ';
				}

				if ( $attachmentsCount ) {
					$htmlCode .= '<br>';
					foreach ( $meta['attachments'] as $attachment )
						if ( file_exists( $attachment['file'] ) )
						{
						$url = self::path2url( $attachment['file'] );

						$htmlCode .= '<br><a class="ui label small" href="' . $url  . '"> <i class="file icon"></i>' . $attachment['name'] . ' ' . self::humanSize( filesize( $attachment['file'] ) ) . ' </a> ';

						$ext = strtolower( pathinfo( $attachment['file'], PATHINFO_EXTENSION ) );
						if ( in_array($ext, [ 'jpg', 'jpeg', 'gif' ]) ) $htmlCode .= '<br><a href="' . $url . '" class="ui medium image"> <img src="' . $url . '"> </a>';

						}
						else $htmlCode .=  '<span class="ui label small">' . $attachment['name'] . ' missing ' . '</span>';

				}

				$htmlCode .= '</div></div></div>';
			}

			$htmlCode .= '</div>';

		} else {
			$htmlCode .= __( 'No Messages', 'ppv-live-webcams' );
		}

		$htmlCode .= '</div>';

		return $htmlCode;
	}


	static function videowhisper_cam_messages_performer( $atts ) {
		// show messages/questions to performer for answering

		// if (!is_user_logged_in()) return __('Login to manage messages and replies!','ppv-live-webcams') . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __('Login', 'ppv-live-webcams') . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __('Register', 'ppv-live-webcams') . '</a>';

		if ( ! is_user_logged_in() ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' header"> <i class="sign in icon"></i> ' . __( 'Login to manage messages and replies!', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="1" role="' . $options['rolePerformer'] . '"]' );
		}

		$atts = shortcode_atts(
			array(
				'post_id' => 0, // for setup
			),
			$atts,
			'videowhisper_cam_messages_performer'
		);
		$atts = array_map('esc_attr', $atts);

		// room id
		$postID = intval( $atts['post_id'] );
		if ( ! $postID ) {
			$postID = intval( $_GET['webcam_id'] ); // pass by URL
		}

		if ( $postID ) {
			$post = get_post( $postID );
		}
		if ( ! $post ) {
			$postID = 0;
		}

		if ( ! $postID ) {
			return 'Error: Could not find this webcam room! #' . $postID;
		}

		$room = sanitize_file_name( $post->post_title );

		if ( ! self::isAuthor( $postID ) ) {
			return 'Access to messages not permitted (different room owner)! #' . $postID;
			die;
		}

		$options = self::getOptions();
		$htmlCode = '';

		$this_page = self::getCurrentURL();

		if (isset( $_GET['messages'] ) ) if ( $_GET['messages'] == 'setup' && $_POST['question_setup'] ) {

			$price = floatval( $_POST['question_price'] );
			if ( ! $price ) {
				$price = $options['messagesCost'];
			}
			update_post_meta( $postID, 'question_price', $price );

			update_post_meta( $postID, 'question_closed', intval( $_POST['question_closed'] ) );
		}

		$messageCode = '';
		if (isset( $_GET['messages'] ) ) if ( in_array( $_GET['messages'], array( 'view', 'reply' ) ) ) {
			$messageCode = sanitize_text_field( $_GET['msg'] );

			// performer message reply
			if ( $_POST['newmessage'] ) {

				global $wpdb;
				$table_messages = $wpdb->prefix . 'vw_vmls_messages';

				$ztime = time();

				$sender_id = get_current_user_id();

				$id = self::to10( $messageCode );
				if ( ! $id ) {
					return 'Error: Incorrect message code! #' . $messageCode;
				}

				$sqlS        = $wpdb->prepare("SELECT * FROM $table_messages WHERE id=%d", $id);
				$mainmessage = $wpdb->get_row( $sqlS );
				if ( ! $mainmessage ) {
					return 'Error: Message not found! #' . $id;
				}

				$ldate = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(ldate) FROM `$table_messages` WHERE id=%d", $id ) );if ( $ldate == $ztime ) {
					return 'Error: You just sent reply or shortcode was already executed.';
				}

				// allowed tags
				$allowedtags = array(
					'a'          => array(
						'href'  => true,
						'title' => true,
					),
					'abbr'       => array(
						'title' => true,
					),
					'acronym'    => array(
						'title' => true,
					),

					'b'          => array(),
					'blockquote' => array(
						'cite' => true,
					),
					'cite'       => array(),
					'code'       => array(),
					'del'        => array(
						'datetime' => true,
					),
					'em'         => array(),
					'i'          => array(),
					'q'          => array(
						'cite' => true,
					),
					'strike'     => array(),
					'strong'     => array(),

					'ul'         => array(),
					'ol'         => array(),
					'li'         => array(),

					'span'       => array(
						'style' => array(),
					),

					'p'          => array(
						'style' => array(),
					),
				);

				$message = wp_kses( stripslashes($_POST['msgContent']) , $allowedtags );

								$meta = array();
				$meta['attachments']  = array();

				// Attachments
				$countfiles = 0;
				if ( ! empty( $_FILES ) ) {
					if ( $_FILES['attachments'] ) {
						$countfiles = count( $_FILES['attachments'] );
					}
				}

				if ( $options['attachment_extensions'] ) {
					if ( $countfiles ) {

						$dir = sanitize_text_field( $options['uploadsPath'] );
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}

						$dir .= "/$room";
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}

						$dir .= '/_attachments';
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}

						$dir .= '/';

						// Looping all files
						for ( $i = 0;$i < $countfiles;$i++ ) {

							$filename = isset($_FILES['attachments']['name'][ $i ]) ? $_FILES['attachments']['name'][ $i ] : '' ;

							if ( ! $filename ) {
								break;
							}

							$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

							if ( ! in_array( $ext, self::extensions_attachment() ) ) {
								return '<div class="ui message error">' . $filename . ') ' . __( 'This attachment extension is not allowed:', 'ppv-live-webcams' ) . ' ' . $ext . '<br>' . __( 'Allowed attachment extensions:', 'ppv-live-webcams' ) . ' ' . $options['attachment_extensions'] . '<br>' . __( 'Try again:', 'ppv-live-webcams' ) . ' ' . $message . '</div>';

								exit;
							}

							if ( ! file_exists( $_FILES['attachments']['tmp_name'][ $i ] ) ) {
								return 'Attachment upload failed. Report to webmaster and include link in message, until sorted out.';
							}

							if ( $options['attachmentSize'] ) {
								if ( filesize( $_FILES['attachments']['tmp_name'][ $i ] ) > $options['attachmentSize'] ) {
																return '<div class="ui message error">' . __( 'This attachment is too big:', 'ppv-live-webcams' ) . ' ' . $filename . '<br>' . __( 'Allowed attachment size:', 'ppv-live-webcams' ) . ' ' . $options['attachmentSize'] . '<br>' . __( 'Try again:', 'ppv-live-webcams' ) . ' ' . $message . '</div>';
								}
							}

							// Upload file
							$path = $dir . self::generateName( $filename );

							$meta['attachments'][ $i ] = array(
								'name' => sanitize_text_field( $filename ),
								'file' => $path,
								'size' => filesize( $_FILES['attachments']['tmp_name'][ $i ] ),
							);

							//per file
							$file = array(
							            'name' => $_FILES['attachments']['name'][ $i ],
							            'type' => $_FILES['attachments']['type'][ $i ],
							            'tmp_name' => $_FILES['attachments']['tmp_name'][ $i ],
							            'error' => $_FILES['attachments']['error'][ $i ] ,
							            'size' => $_FILES['attachments']['size'][ $i ]
							        );

							// ( $_FILES['attachments']['tmp_name'][$i], $path);
							$errorUp = self::handle_upload( $file, $path ); // handle trough wp_handle_upload()

							if ( $errorUp ) {
								$htmlCode .= '<br>' . 'Error uploading ' . esc_html( $filename . ':' . $errorUp  )  ;
							}

							if (!file_exists($path)) $htmlCode .= '<br> Upload failed, file missing: ' . $path;

						}
					}
				}

				// message sender
				$sender = wp_get_current_user();

				// recordings
				$meta['recordings'] = array();
				$recordings         = sanitize_text_field( $_POST['recordings'] );
				if ( $recordings ) {

					$optionsRecorder = get_option( 'VWinlineRecorderOptions' );
					$recordingFiles  = explode( ',', $recordings );

					foreach ( $recordingFiles as $recordingFile ) {
						if ( $recordingFile ) {
							if ( file_exists( $filePath = $optionsRecorder['uploadsPath'] . '/' . $sender->user_login . '/' . $recordingFile ) ) {
								$meta['recordings'][] = array(
									'name' => sanitize_text_field( $recordingFile ),
									'file' => $filePath,
									'size' => filesize( $filePath ),
								);
							} else {
								$htmlCode .= ' Recording file not found: ' . $filePath;
							}
						}
					}
				}

				$metaS = serialize( $meta );

				$sqlIM = $wpdb->prepare(
					"INSERT INTO `$table_messages` ( `sender_id`, `webcam_id`, `reply_id`, `sdate`, `message`, `meta`) VALUES ( %d, %d, %d, %d, %s, %s)",
					$sender_id, $postID, $id, $ztime, $message, $metaS
				);
				$wpdb->query( $sqlIM );
				$messageID = $wpdb->insert_id;
				if ( ! $messageID ) {
					return 'Error: Sending message failed! ' . $sqlIM;
				}

					// notification to client
					$client = get_userdata( $mainmessage->sender_id );
					$link   = get_permalink( $options['p_videowhisper_webcams_client'] );
				if ( $client ) {
					$mailed = wp_mail( $client->user_email, $options['messagesSubject'] . ' ' . ( $sender->display_name ? $sender->display_name : $sender->user_login ), $options['messagesText'] . "\r\n" . $link );

					//notify by SMS
					if ($options['sms_number'])
					{
						$sms_number = get_user_meta( $client->ID, 'sms_number', true );
						if ($sms_number)
						{
							$sms_last =  get_user_meta( $client->ID, 'sms_last', true );
							if (!$sms_last || $sms_last < ( time() - $options['sms_cooldown']) )
							{

							$sms_message = $options['messagesSubject'] . ' ' . ( $sender->display_name ? $sender->display_name : $sender->user_login ) . ' ' . $link;

							if ($options['wp_sms']) if ( function_exists('wp_sms_send') ) wp_sms_send($sms_number,  $sms_message);

							if ($options['wp_sms_twilio']) if ( function_exists('twl_send_sms') )
							{
								$response = twl_send_sms( array( 'number_to' => $sms_number, 'message' => $sms_message ) );
								if( is_wp_error( $response ) ) $htmlCode .= '<div class="ui message">SMS Error: ' . esc_html( $response->get_error_message() ) . '</div>';
							}

							update_user_meta( $author->ID, 'sms_last', time() );
							}
						}

					}

				} else {
					$htmlCode .= 'Error: User not found to notify!';
				}

				if ( ! $mailed ) {
					$htmlCode .= 'Warning: Notification email was not sent by WP.';
				}

				// update last message time
				$sqlU = $wpdb->prepare("UPDATE `$table_messages` SET `ldate` = %d WHERE id = %d", $ztime, $id);
				$wpdb->query( $sqlU );

				// get paid for first reply
				$sqlC = $wpdb->prepare("SELECT COUNT(id) FROM `$table_messages` WHERE reply_id = %d", $id);
				$repliesCount = $wpdb->get_var( $sqlC );
				if ( ! $repliesCount ) {
					return 'Error: No replies! ' . $sqlC;
				}

				// get paid
				$messagesCost = floatval( get_post_meta( $postID, 'question_price', true ) );
				if ( ! $messagesCost ) {
					$messagesCost = floatval( $options['messagesCost'] );
				}

				$performerRatio = self::performerRatio( $post->post_name, $options, $postID );

				// only get paid for first reply and  if cost enabled
				if ( $repliesCount == 1 && $messagesCost ) {
					$messageURL = add_query_arg(
						array(
							'messages' => 'view',
							'msg'  => $messageCode,
						),
						$this_page
					);

					self::transaction( $ref = 'paid_message_earn', $sender_id, $messagesCost * $performerRatio, __( 'Paid Message Earning from ', 'ppv-live-webcams' ) . ' <a href="' . $messageURL . '">' . $messageCode . '</a>', $messageID, $message, $options );
					$htmlCode .= '<div id="successMessage" class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'Your reply was sent and you got paid.', 'ppv-live-webcams' ) . '</div>';

				} else {
					$htmlCode .= '<div id="successMessage" class="ui ' . $options['interfaceClass'] . ' segment">' . __( 'Your reply was sent.', 'ppv-live-webcams' ) . '</div>';
				}
			}

			// show msg & replies
			$htmlCode .= self::messageReplies( $messageCode );

			// reply form
			$tinymce_options = array(
				'plugins'          => 'lists,link,textcolor,hr',
				'toolbar1'         => 'cut,copy,paste,|,undo,redo,|,fontsizeselect,forecolor,backcolor,bold,italic,underline,strikethrough',
				'toolbar2'         => 'alignleft,aligncenter,alignright,alignjustify,blockquote,hr,bullist,numlist,link,unlink',
				'fontsize_formats' => '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt',
			);

			$action    = add_query_arg(
				array(
					'messages' => 'reply',
					'msg'  => $messageCode,
				),
				$this_page
			);
			$htmlCode .= '<form method="post" enctype="multipart/form-data" action="' . $action . '" name="messageForm" class="ui form segment">';

			$htmlCode .= '<div class="field"><label><i class="newspaper icon"></i> ' . __( 'Send Reply', 'ppv-live-webcams' ) . '</label>';
			ob_start();
			wp_editor(
				'',
				'msgContent',
				$settings = array(
					'textarea_rows' => 3,
					'media_buttons' => false,
					'teeny'         => true,
					'wpautop'       => false,
					'tinymce'       => $tinymce_options,
				)
			);
			$htmlCode    .= ob_get_clean();
			$htmlCode    .= '</div>';

			$htmlCode .= ( $options['attachment_extensions'] ? '
	<div class="field"><label><i class="attach icon"></i> ' . __( 'Attachments (Optional)', 'ppv-live-webcams' ) . '</label>
	<input type="file" name="attachments[]" id="attachments" multiple placeholder="Attachments"><br><small>' . __( 'Supports', 'ppv-live-webcams' ) . ' : ' . $options['attachment_extensions'] . '</small>
	</div>
	' : '' );

			if ( shortcode_exists( 'videowhisper_recorder_inline' ) ) {
				$htmlCode .= do_shortcode( '[videowhisper_recorder_inline label="Attach Recordings (Optional)"]' );
			}

			$htmlCode .= '<div class="field"> <input class="ui button videowhisperButton" type="submit" name="newmessage" id="newmessage" value="' . __( 'Send Reply', 'ppv-live-webcams' ) . '" /> </div>
	<input name="post_id" id="post_id" type="hidden" value="' . $postID . '">
	</form>';

		}

		// list messages

		global $wpdb;
		$table_messages = $wpdb->prefix . 'vw_vmls_messages';

		$htmlCode .= '<div class="ui segment ' . $options['interfaceClass'] . '">';

		$sql = $wpdb->prepare("SELECT * FROM $table_messages WHERE webcam_id = %d AND reply_id = 0 ORDER BY sdate DESC, id DESC LIMIT 0, 100", $postID);
		$results = $wpdb->get_results( $sql );

		if ( $wpdb->num_rows > 0 ) {
			$htmlCode .= '<div class="ui list celled ' . $options['interfaceClass'] . '">';

			foreach ( $results as $message ) {
				$messageCode1 = self::to62( $message->id );

				$messageURL = add_query_arg(
					array(
						'messages' => 'view',
						'msg'  => $messageCode1,
					),
					$this_page
				);
				$sender     = get_userdata( $message->sender_id );

				// replies
				$sqlC         = $wpdb->prepare("SELECT COUNT(id) FROM `$table_messages` WHERE reply_id=%d", $message->id);
				$repliesCount = $wpdb->get_var( $sqlC );

				$attachmentsCount = 0;
				if ( $message->meta ) {
					$meta = unserialize( $message->meta );
					if ( is_array( $meta ) ) {
						if ( array_key_exists( 'attachments', $meta ) ) {
							$attachmentsCount = count( $meta['attachments'] );
						}
					}
				}

				$htmlCode .= '<div class="item ' . $options['interfaceClass'] . '" href="' . $messageURL . '"> <i class="' . ( $messageCode == $messageCode1 ? 'envelope outline' : 'mail' ) . ' icon big"></i>';

				$htmlCode .= '<div class="content">';
				$htmlCode .= '<div class="header">' . ' <i class="user icon"></i>' . ( $sender ? ($sender->display_name ? $sender->display_name : $sender->user_login) : '#' . $message->sender_id . ' - User Not Found'). ' <i class="clock icon"></i>' . date( DATE_RFC2822, $message->sdate ) . ' <i class="arrow right icon"></i> #' . $messageCode1 . ( $repliesCount ? ' <i class="check icon"></i> ' . __( 'Replied', 'ppv-live-webcams' ) . '' : ' <i class="spinner loading icon"></i> <i class="green bell icon"></i>' ) . '</div>';

				$htmlCode .= '<div class="description">' . ( $attachmentsCount ? '<i class="ui attach icon"></i>' . $attachmentsCount . ' ' : '' ) . ' <i class="ui newspaper icon"></i> ' . wp_trim_excerpt( $message->message ) . '</div>';
				$htmlCode .= '<div class="description"><a href="' . $messageURL . '" class="ui button compact"> <i class="zoom-in icon"></i> ' . __( 'View Message', 'ppv-live-webcams' ) . '</a></div>';

				$htmlCode .= '</div></div>';
			}

			$htmlCode .= __( 'You receive payment for paid messages on reply. Clients can access their messages and replies from client dashboard and in messages section from webcam profile.', 'ppv-live-webcams' );

			$htmlCode .= '</div>';

		} else {
			$htmlCode .= __( 'No Messages', 'ppv-live-webcams' );
		}
		$htmlCode .= '</div>';

		// messaging settings

		$price = floatval( get_post_meta( $postID, 'question_price', true ) );
		if ( ! $price ) {
			$price = $options['messagesCost'];
		}

		$closed = intval( get_post_meta( $postID, 'question_closed', true ) );

		$htmlCode .= '<form class="ui form ' . $options['interfaceClass'] . '" method="post" enctype="multipart/form-data" action="' . add_query_arg( 'messages', 'setup', $this_page ) . '" name="questionSetup">';

		$htmlCode .= '<div class="two fields">';

		$htmlCode .= '<div class="field"> <label>' . __( 'Questions', 'ppv-live-webcams' ) . ' </label> <select class="ui fluid dropdown" name="question_closed" id="question_closed">
		<option value="1" ' . ( $closed ? 'selected' : '' ) . '>' . __( 'Closed', 'ppv-live-webcams' ) . '</option>
		<option value="0" ' . ( ! $closed ? 'selected' : '' ) . '>' . __( 'Open', 'ppv-live-webcams' ) . '</option>
		</select>
		</div>';

		$htmlCode .= '<div class="field"> <label>' . __( 'Price per Question', 'ppv-live-webcams' ) . ' </label>
		<div class="ui right labeled input">
		<input size="8" maxlength="12" type="text" name="question_price" id="question_price" value="' . $price . '"/>
		<div class="ui basic label">
		' . $options['currency'] . '
		</div>
		</div>
		</div>';

		$htmlCode .= '<div class="field"> <label>' . __( 'Update', 'ppv-live-webcams' ) . '</label> <input class="ui button videowhisperButton" type="submit" name="question_setup" id="question_setup" value="' . __( 'Save', 'ppv-live-webcams' ) . '" /></div>';

		$htmlCode .= '</div>';

		$htmlCode .= '<input name="post_id" id="post_id" type="hidden" value="' . $postID . '">
		</form>';

		return $htmlCode;
	}


	static function videowhisper_webcams_client( $atts ) {

		self::enqueueUI();
		$htmlCode = '';

		$options = get_option( 'VWliveWebcamsOptions' );

		if ( ! is_user_logged_in() ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' header"> <i class="sign in icon"></i> ' . __( 'Login to access client features!', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="1" role="' . $options['roleClient'] . '"]' );
		}

		// if (!is_user_logged_in()) return __('Login to access as client!','ppv-live-webcams') . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __('Login', 'ppv-live-webcams') . '</a>  <a class="ui button secondary qbutton" href="' . ( ($options['registerFrontend'] && $options['p_videowhisper_register_client']) ? get_permalink($options['p_videowhisper_register_client']) : wp_registration_url() )  . '">' . __('Register', 'ppv-live-webcams') . '</a>';

		$htmlCode = '';

		$user_ID = get_current_user_id();

		$current_user = wp_get_current_user();
		$this_page    = self::getCurrentURL();

		$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' header">' . $current_user->user_login . ( $options['loginFrontend'] ? ' <a class="ui tiny button" href="' . wp_nonce_url( add_query_arg( 'logout', $current_user->ID, get_permalink( $options['p_videowhisper_login'] ) ), 'logout' . $current_user->ID, 'verify' ) . '"> <i class="sign out icon"></i>' . __( 'Log Out', 'ppv-live-webcams' ) . '</a>' : '' ) . '</div>';

		if ( $options['clientWallet'] ?? false) {
			$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' green segment form">';
			$htmlCode .= '<h4> <i class="ui icon money"></i>' . __( 'Active balance', 'ppv-live-webcams' ) . ': ' . self::balance( $user_ID ) . '</h4>';
			$htmlCode .= __( 'All Balances', 'ppv-live-webcams' ) . ': ' . self::balances( $user_ID );
			$htmlCode .= '<br><A class="ui button primary" HREF="' . get_permalink( $options['balancePage'] ) . '"><i class="ui icon wallet"></i>' . __( 'Manage Wallet', 'ppv-live-webcams' ) . '</A>';
			$htmlCode .= '</div>';
		}

		if ($options['match'] ?? false)
		if ( ! self::any_in_array( self::getRolesPerformer(), $current_user->roles ) ) //performers need to go live from own dashboard
		{
			$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">';
			$htmlCode .= '<h4>' . __( 'Random Mathmaking', 'ppv-live-webcams' ) . '</h4>';
			$htmlCode .= '<A class="ui button" href="' . get_permalink( $options['p_videowhisper_match_form'] ) . '">' . __( 'Match Criteria', 'ppv-live-webcams' ) . '</A>';
			$htmlCode .= '<A class="ui button" href="' . get_permalink( $options['p_videowhisper_match'] ) . '">' . __( 'Random Match', 'ppv-live-webcams' ) . '</A>';
			$htmlCode .= '</div>';
		}

		if ( $options['messages'] ?? false ) {
			$htmlCode .= '<h3 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'My Questions and Messages', 'ppv-live-webcams' ) . '</h3>';
			$htmlCode .= do_shortcode( '[videowhisper_cam_messages]' );
		}

		if ( $options['calls'] ?? false ) {
			$htmlCode .= '<h3 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'My Calls', 'ppv-live-webcams' ) . '</h3>';
			$htmlCode .= do_shortcode( '[videowhisper_cam_calls]' );
		}

		if ( $options['addRoles'] ?? false ) {
			$htmlCode .= '<h3 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'Add Role', 'ppv-live-webcams' ) . '</h3>';
			$htmlCode .= do_shortcode( '[videowhisper_role_change]' );
		}

		if ( $options['sms_number'] ?? false )
		{
			//$htmlCode .= '<h3 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'SMS Mobile Number', 'ppv-live-webcams' ) . '</h3>';

			$htmlCode .= do_shortcode( '[videowhisper_sms_number]' );
		}

		if ( $options['clientSubscriptions'] ?? false ) if (shortcode_exists( 'videowhisper_client_subscriptions' )) {
			$htmlCode .= '<h3 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'My Subscriptions', 'ppv-live-webcams' ) . '</h3>';
			$htmlCode .= do_shortcode( '[videowhisper_client_subscriptions]' );
		}

		if ( $options['clientContent'] ?? false ) if (shortcode_exists( 'videowhisper_content' )) {
			$htmlCode .= do_shortcode( '[videowhisper_content]' );
		}

		if ( ! $htmlCode ) {
			return 'No client dashboard features enabled.';
		}

		return $htmlCode;
	}


	static function videowhisper_role_change( $atts ) {

		self::enqueueUI();
		$htmlCode = '';

		if ( ! is_user_logged_in() ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' header"> <i class="sign in icon"></i> ' . __( 'Login to manage role!', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="1"]' );
		}

		$options = get_option( 'VWliveWebcamsOptions' );
		if ( ! $options['registrationFormRole'] ) {
			return 'Registration roles disabled!';
		}

		$roles = array( $options['roleClient'], $options['rolePerformer'] );
		if ( $options['studios'] ) {
			$roles[] = $options['roleStudio']; // add studio if enabled
		}

		$changerole = sanitize_text_field( $_POST['changerole'] );

		$current_user = wp_get_current_user();

		if ( $changerole ) {
			if ( ! in_array( $changerole, $roles ) ) {
				return 'Unavailable role!';
			}
			if ( in_array( 'administrator', $current_user->roles ) ) {
				return 'Administrator can access all features without other roles!';
			}
			$current_user->add_role( $changerole );
		}

		$htmlCode = '';

		$roleCode = '';
		foreach ( $current_user->roles as $role ) {
			$roleCode .= ( $roleCode ? ', ' : '' ) . $role;
		}

		$htmlCode .= __( 'Your role(s)', 'ppv-live-webcams' ) . ' : ' . $roleCode;

		$htmlCode .= '<form method="post" enctype="multipart/form-data" action="' . self::getCurrentURL() . '" name="callForm" class="ui form ' . $options['interfaceClass'] . ' segment">';

		$htmlCode .= '<label for="role"> ' . __( 'Role', 'ppv-live-webcams' ) . '</label> <select id="changerole" name="changerole" class="ui dropdown v-select">';

		foreach ( $roles as $role ) {
			// create role if missing
			if ( ! $oRole = get_role( $role ) ) {
				add_role( $role, ucwords( $role ), array( 'read' => true ) );
				$oRole = get_role( $role );
			}

			$htmlCode .= '<option value="' . $role . '" ' . ( in_array( $role, $current_user->roles ) ? 'selected' : '' ) . '>' . ucwords( $oRole->name ) . '</option>';

		}

		$htmlCode .= '</select>';

		$htmlCode .= '<input class="ui button" type="submit" name="update" id="newcall" value="' . __( 'Add Role', 'ppv-live-webcams' ) . '" /></form>';

		return $htmlCode;

	}


	static function videowhisper_cam_messages( $atts ) {

		// shows client messages, to check replies

		// if (!is_user_logged_in()) return __('Login to manage messages and replies!','ppv-live-webcams') . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __('Login', 'ppv-live-webcams') . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __('Register', 'ppv-live-webcams') . '</a>';

		$options = self::getOptions();
		if ( ! is_user_logged_in() ) {
			return '<div class="ui ' . $options['interfaceClass'] . ' header"> <i class="sign in icon"></i> ' . __( 'Login to manage messages and replies!', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="1" role="' . $options['roleClient'] . '"]' );
		}

		$atts = shortcode_atts(
			array(
				'post_id' => 0, // 0 shows all
			),
			$atts,
			'videowhisper_cam_messages_performer'
		);
		$atts = array_map('esc_attr', $atts);

		// room id
		$postID = intval( $atts['post_id'] );
		if ( ! $postID ) {
			$postID = intval( $_GET['webcam_id'] ?? 0 ); // pass by URL
		}

		$post = false;
		if ( $postID ) {
			$post = get_post( $postID );
		}

		if ( ! $post ) {
			$postID = 0;
		}

		// if (!$postID) return 'Error: Could not find this webcam room! #' .  $postID;

		$htmlCode = '';

		$this_page = self::getCurrentURL();

		$sender_id = get_current_user_id();

		$messageCode = '';
		if ( isset( $_GET['msg'] ) && $_GET['view'] == 'messages' ) {
			$messageCode = sanitize_text_field( $_GET['msg'] );

			// show msg & replies
			$htmlCode .= self::messageReplies( $messageCode );
		}

		global $wpdb;
		$table_messages = $wpdb->prefix . 'vw_vmls_messages';

		// list messages
		$htmlCode .= '<div class="ui segment ' . $options['interfaceClass'] . '">';

		$cnd = '';
		if ( $postID ) {
			$cnd = "AND webcam_id='$postID'";
		}
		$sql = $wpdb->prepare("SELECT * FROM $table_messages WHERE sender_id = %d $cnd AND reply_id = 0 ORDER BY sdate DESC, id DESC LIMIT 0, 100", $sender_id);
		$results = $wpdb->get_results($sql);

		if ( $wpdb->num_rows > 0 ) {
			$htmlCode .= '<div class="ui list celled ' . $options['interfaceClass'] . '">';

			foreach ( $results as $message ) {

				// replies
				$sqlC         = $wpdb->prepare("SELECT COUNT(id) FROM `$table_messages` WHERE reply_id=%d", $message->id);
				$repliesCount = $wpdb->get_var( $sqlC );

				$messageCode1 = self::to62( $message->id );

				$messageURL = add_query_arg(
					array(
						'view'    => 'messages',
						'msg' => $messageCode1,
					),
					$this_page
				);
				$sender     = get_userdata( $message->sender_id );

				$htmlCode .= '<div class="item ' . $options['interfaceClass'] . '"> <i class="' . ( $messageCode == $messageCode1 ? 'envelope outline' : 'mail' ) . ' icon big"></i>';
				$htmlCode .= '<div class="content">';
				$htmlCode .= '<div class="header">' . ' <i class="user icon"></i>' . $sender->user_nicename . ' <i class="clock icon"></i>' . date( DATE_RFC2822, $message->sdate ) . ( $repliesCount ? ' <i class="check icon"></i> ' . __( 'Replied', 'ppv-live-webcams' ) . '' : ' <i class="spinner loading icon"></i>' ) . '</div>';

				// var_dump($message);

				$attachmentsCount = 0;
				if ( $message->meta ) {
					$meta = unserialize( $message->meta );
					if ( is_array( $meta ) ) {
						if ( array_key_exists( 'attachments', $meta ) ) {
							$attachmentsCount = count( $meta['attachments'] );
						}
					}
				}

				$htmlCode .= '<div class="description">' . ( $attachmentsCount ? '<i class="ui attach icon"></i>' . $attachmentsCount . ' ' : '' ) . ' <i class="ui newspaper icon"></i> ' . wp_trim_excerpt( $message->message ) . '</div>';

				$htmlCode .= '<div class="description"> <a href="' . $messageURL . '" class="ui button compact"> <i class="zoom-in icon"></i> ' . __( 'View Message', 'ppv-live-webcams' ) . '</a></div>';

				$htmlCode .= '</div></div>';
			}

			$htmlCode .= '</div>';

		} else {
			$htmlCode .= __( 'No Messages', 'ppv-live-webcams' );
		}
		$htmlCode .= '</div>';

		return $htmlCode;
	}



	static function videowhisper_cam_calls( $atts ) {
		// setup and list video calls

		if ( ! is_user_logged_in() ) {
			return __( 'Login to manage calls!', 'ppv-live-webcams' ) . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'ppv-live-webcams' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'ppv-live-webcams' ) . '</a>';
		}

		$atts = shortcode_atts(
			array(
				'post_id' => 0, // for setup
			),
			$atts,
			'videowhisper_cam_calls'
		);
		$atts = array_map('esc_attr', $atts);

		// room id
		$post = false;
		$postID = intval( $atts['post_id'] );
		if ( $postID ) {
			$post = get_post( $postID );
		}
		if ( ! $post ) {
			$postID = 0;
		}

		$options = get_option( 'VWliveWebcamsOptions' );

		$htmlCode = '';

		// user info
		// performer (role)
		$current_user = wp_get_current_user();
		// access keys
		$userkeys   = $current_user->roles;
		$userkeys[] = $current_user->user_login;
		$userkeys[] = $current_user->user_nicename;
		$userkeys[] = $current_user->ID;
		$userkeys[] = $current_user->user_email;

		global $wpdb;
		$table_private = $wpdb->prefix . 'vw_vmls_private';

		$roleS = implode( ',', $current_user->roles );
		if ( $postID ) { // only for a room
			if ( self::any_in_array( self::getRolesPerformer( $options ), $current_user->roles ) ) {
				// main webcam room/post

				/*
				//any owned webcam?
				if (!$postID)
				{
				$postID = $wpdb->get_var( $sql = 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_author = \'' . $current_user->ID . '\' and post_type=\''.$options['custom_post'] . '\' LIMIT 0,1' );
				}
				//create a room
				if (!$postID) $postID = self::webcamPost(); //default cam
				if ($postID && !$post) $post = get_post($postID);
				if (!$post) $postID = 0;
				if (!$postID) return 'Error: Could not find or setup a webcam room for this user!';
				*/

				$room_audio = self::is_true( get_post_meta( $postID, 'room_audio', true ) );
				$room_text = self::is_true( get_post_meta( $postID, 'room_text', true ) );

				$showNew = 1;

				if ( isset( $_GET['calls'] ) && $_GET['calls'] == 'new' )
				{
					$htmlCode .= '<div class="ui segment">';

					$client = trim( sanitize_text_field( $_POST['client'] ) );
					if ( $client ) {
						if ( filter_var( $client, FILTER_VALIDATE_EMAIL ) ) {
							$user  = get_user_by( 'email', $client );
							$login = $user->user_login;
							$email = $client;
						} else {
							$user  = get_user_by( 'login', $client );
							$login = $client;
							$email = $user->user_email;
						}
					}

					if ( $user ) {
						$callMode = sanitize_text_field( $_POST['callMode'] );

						$callType = '';
						if ( $callMode == 'paid-audio' || $callMode == 'free-audio' ) {
							$callType = 'audio';
						}
						if ( $callMode == 'paid-text' || $callMode == 'free-text' ) {
							$callType = 'text';
						}

						if ( $callMode == 'free' || $callMode == 'free-audio' || $callMode == 'free-text' ) {
							$clientCPM = 0;
						} else {
							$clientCPM = self::clientCPM( $post->post_title, $options, $postID, $callType );
						}

						//downgrade call if room in lesser mode
						if ($room_audio) if ($calltype != 'text') $calltype = 'audio';
						if ($room_text) $calltype = 'text';

						if ( ! $clientCPM && ! $callMode ) {
							$callMode = 'free';
						}

						$performer = self::performerName( $current_user, $options );

						$meta = array(
							'time'      => time(),
							'client'    => $client,
							'email'     => $email,
							'username'  => $login,
							'callMode'  => $callMode,
							'callType'  => $callType,
							'clientCPM' => $clientCPM,
						);

						$metaS     = serialize( $meta );
						$room_name = $post->post_name;

						$sql = $wpdb->prepare(
							"INSERT INTO `$table_private` ( `call`, `performer`, `pid`, `client`, `cid`, `rid`, `room`, `status`, `meta` ) VALUES ( %d, %s, %d, %s, %d, %d, %s, %d, %s )",
							1, $performer, $current_user->ID, $user->user_login, $user->ID, $postID, $room_name, 0, $metaS
						);
						$wpdb->query( $sql );
						$privateID = $wpdb->insert_id;

						$callURL   = add_query_arg( array( 'call' => self::to62( $privateID ) ), get_permalink( $postID ) );
						$htmlCode .= __( 'New call was setup', 'ppv-live-webcams' ) . ': <br>' . $callURL;

						$showNew = 0;
					} else {
						$htmlCode .= __( 'Error: Client was not found by email or username! User needs to register to be able to login and access the private call.', 'ppv-live-webcams' );
						$htmlCode .= '<br>"' . $client . '"';
					}

					$htmlCode .= '</div>';
				}

				$modeCode = '';

				if ( $showNew && $postID ) {

					$modeCode .= '<select style="opacity: 1;" name="callMode" id="callMode" class="ui dropdown v-select">';

					// paid
					$clientCPM = self::clientCPM( $post->post_title, $options, $postID );
					if ( $clientCPM > 0 ) {
						if ( $options['modeVideo'] != '0' ) if (!$room_audio) if (!$room_text) $modeCode .= '<option value="paid" selected>' . __( 'Paid', 'ppv-live-webcams' ) . ': ' . __( 'Videochat', 'ppv-live-webcams' ) . ' ' . $clientCPM . htmlspecialchars( $options['currencypm'] ) . '</option>';
					}

					$clientCPM = self::clientCPM( $post->post_title, $options, $postID, 'audio' );
					if ( $options['modeAudio'] ) if (!$room_text)  {
						if ( $clientCPM > 0 ) {
							$modeCode .= '<option value="paid-audio">' . __( 'Paid', 'ppv-live-webcams' ) . ': ' . __( 'Audio', 'ppv-live-webcams' ) . ' ' . $clientCPM . htmlspecialchars( $options['currencypm'] ) . '</option>';
						}
					}

					$clientCPM = self::clientCPM( $post->post_title, $options, $postID, 'text' );
					if ( $options['modeText'] ) {
						if ( $clientCPM > 0 ) {
							$modeCode .= '<option value="paid-text">' . __( 'Paid', 'ppv-live-webcams' ) . ': ' . __( 'Text', 'ppv-live-webcams' ) . ' ' . $clientCPM . htmlspecialchars( $options['currencypm'] ) . '</option>';
						}
					}

					// free
					if ( $options['modeVideo'] != '0' ) if (!$room_audio) if (!$room_text) $modeCode .= '<option value="free">' . __( 'Free', 'ppv-live-webcams' ) . ': ' . __( 'Videochat', 'ppv-live-webcams' ) . '</option>';

					if ( $options['modeAudio'] ) if (!$room_text) {
						$modeCode .= '<option value="free-audio">' . __( 'Free', 'ppv-live-webcams' ) . ': ' . __( 'Audio', 'ppv-live-webcams' ) . '</option>';
					}

					if ( $options['modeText'] ) {
						$modeCode .= '<option value="free-text">' . __( 'Free', 'ppv-live-webcams' ) . ': ' . __( 'Text', 'ppv-live-webcams' ) . '</option>';
					}

					$modeCode .= '</select>';

					$this_page = remove_query_arg( 'deletecall', self::getCurrentURL() );
					$action    = add_query_arg( array( 'calls' => 'new' ), $this_page );
					$htmlCode .= '<form method="post" enctype="multipart/form-data" action="' . $action . '" name="callForm" class="ui form ' . $options['interfaceClass'] . ' segment">
	<div class="field"><label>' . __( 'Private Call Chat Mode', 'ppv-live-webcams' ) . '</label>' . $modeCode . '</div>
	<div class="field"><label>' . __( 'Client', 'ppv-live-webcams' ) . '</label> <input class="ui field" name="client" id="client" value="" placeholder="' . __( 'Username or Email', 'ppv-live-webcams' ) . '"></div>
	<input class="ui button videowhisperButton" type="submit" name="newcall" id="newcall" value="' . __( 'Setup Call', 'ppv-live-webcams' ) . '" />
	<input name="post_id" id="post_id" type="hidden" value="' . $postID . '">
';
					if ($room_audio) $htmlCode .= '<p> * ' . __( 'Warning: Room in audio only mode restricts call options.', 'ppv-live-webcams' ) . '</p>';
					if ($room_text) $htmlCode .= '<p> * ' . __( 'Warning: Room in text only mode restricts call options.', 'ppv-live-webcams' ) . '</p>';

					$htmlCode .= '<p>' . __( 'Setup locked private calls that can only be accessed by client using dedicated call link. Clients can access their persistent calls list in client dashboard.  ', 'ppv-live-webcams' ) . '</p>
			</form>';

				}
			}
		}

		// list calls

		$htmlCode .= '<div class="ui segment">';

		$uid = $current_user->ID;

		if ( $delete = intval( $_GET['deletecall'] ?? 0 ) ) {
			$sqlD = $wpdb->prepare("DELETE FROM $table_private WHERE `call` > 0 AND id = %d AND pid = %d", $delete, $uid);
			$wpdb->query( $sqlD );

			$htmlCode .= '<div class="ui message">' . __( 'Call deleted.', 'ppv-live-webcams' ) . ' #' . $delete  . '</div>';
		}

		if ( $postID ) {
			$sql = $wpdb->prepare("SELECT * FROM $table_private WHERE rid = %d AND `call` > 0 ORDER BY status ASC, id DESC LIMIT 0, 100", $postID);
		} else {
			$sql = $wpdb->prepare("SELECT * FROM $table_private WHERE (cid = %d OR pid = %d) AND `call` > 0 ORDER BY status ASC, id DESC LIMIT 0, 100", $uid, $uid);
		}

		$results = $wpdb->get_results( $sql );
		if ( $wpdb->num_rows > 0 ) {
			$htmlCode .= '<div class="ui list">';

			foreach ( $results as $private ) {
				$callCode = self::to62( $private->id );
				$callURL  = add_query_arg( array( 'call' => $callCode ), get_permalink( $private->rid ) );

				$clientCPM = 0;
				if ( $private->meta ) {
					$meta = unserialize( $private->meta );
					if ( array_key_exists( 'email', $meta ) ) {
						$metaInfo = $meta['email'];
					}
					if ( array_key_exists( 'clientCPM', $meta ) ) {
						$clientCPM = $meta['clientCPM'];
					}
				}

				$htmlCode .= '<div class="item"> <i class="phone icon big"></i>';
				$htmlCode .= '<div class="content">';
				$htmlCode .= '<div class="header">' . $private->room . ' #' . $callCode . ' &nbsp; <i class="user icon"></i>' . ( $current_user->ID == $private->pid ? $private->client : $private->performer ) . ' &nbsp; ' . ( $clientCPM ? '<i class="money bill alternate outline icon"></i>' . $clientCPM . ' ' . htmlspecialchars( $options['currencypm'] ) : '' ) . '</div>';
				$htmlCode .= '<div class="description">' . $callURL . '</div>';

				$delCode = '<a href="' . add_query_arg( array( 'deletecall' => $private->id ), self::getCurrentURL() ) . '" class="ui button compact red"> <i class="close icon"></i> ' . __( 'Delete', 'ppv-live-webcams' ) . '</a>';

				if ( $private->status == 0 ) {
					$htmlCode .= '<div class="description"><a href="' . $callURL . '" class="ui button compact green"> <i class="phone volume icon"></i> ' . __( 'Access', 'ppv-live-webcams' ) . $delCode . '</a></div>';
				} else {
					$htmlCode .= '<div class="description"> <div class="ui label"> <i class="close icon"></i> ' . __( 'Closed', 'ppv-live-webcams' ) . '</div>' . $delCode . '</div>';
				}
				$htmlCode .= '</div></div>';
			}

			$htmlCode .= '</div>';

		} else {
			$htmlCode .= __( 'No Calls', 'ppv-live-webcams' );
		}

		$htmlCode .= '</div>';

		return $htmlCode;
	}


	static function videowhisper_camvideo( $atts ) {

		$atts = shortcode_atts(
			array(
				'cam'     => '',
				'width'   => '640px',
				'height'  => '480px',
				'html5'   => 'auto',
				'post_id' => 0,
			),
			$atts,
			'videowhisper_camvideo'
		);
		$atts = array_map('esc_attr', $atts);

		$options = get_option( 'VWliveWebcamsOptions' );

		$webcamName = sanitize_text_field( $atts['cam'] );
		if ( ! $webcamName ) {
			$webcamName = sanitize_file_name( $_GET['cam'] );
		}

		if ( $atts['post_id'] ) {
			$postID = intval( $atts['post_id'] );
		}

		if ( ! $postID ) {
			global $wpdb;
			$postID = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s and post_type=%s LIMIT 0,1', $webcamName, $options['custom_post'] ) );

		}

		$roomInterface = get_post_meta( $postID, 'roomInterface', true ); // html5/flash
		$streamType    = get_post_meta( $postID, 'stream-type', true ); // webrtc/flash/external/playlist

		$stream = self::webcamStreamName( $webcamName, $postID );

		$width = $atts['width'];
		if ( ! $width ) {
			$width = '480px';
		}
		$height = $atts['height'];
		if ( ! $height ) {
			$height = '360px';
		}

		if ( ! $stream ) {
			return 'Watch Video Error: Missing webcam stream name!';
		}

		// HLS if iOS/Android detected
		$agent   = $_SERVER['HTTP_USER_AGENT'];
		$Android = stripos( $agent, 'Android' );
		$iOS     = ( strstr( $agent, 'iPhone' ) || strstr( $agent, 'iPod' ) || strstr( $agent, 'iPad' ) );
		$Safari  = ( strstr( $agent, 'Safari' ) && ! strstr( $agent, 'Chrome' ) );

		$htmlCode .= "<!--CamVideo:$webcamName|$postID|$roomInterface|$stream|$Android|$iOS|$Safari dH:" . $options['detect_hls'] . '-->';

		$showTeaser = 0;
		if ( $options['teaserOffline'] && ! self::webcamOnline( $postID ) ) {
			$showTeaser = 1; // show offline teaser to client
		}

		// webrtc
		if ( $streamType == 'webrtc' && ! $showTeaser ) {
			return $htmlCode . '<!--H5-WebRTC-->' . do_shortcode( "[videowhisper_cam_webrtc_playback room=\"$webcamName\" webcam_id=\"$postID\" width=\"$width\" height=\"$height\" webstatus=\"1\"]" );
		}

		// always
		if ( $atts['html5'] == 'always' ) {
			if ( $iOS || $Safari ) {
				return $htmlCode . '<!--H5-HLS-->' . do_shortcode( "[videowhisper_camhls webcam=\"$webcamName\" width=\"$width\" height=\"$height\" webstatus=\"1\"]" );
			} else {
				return $htmlCode . '<!--H5-MPEG-->' . do_shortcode( "[videowhisper_cammpeg webcam=\"$webcamName\" width=\"$width\" height=\"$height\" webstatus=\"1\"]" );
			}
		}

		// detect for video interface
		if ( ( $Android && in_array( $options['detect_mpeg'], array( 'android', 'all' ) ) ) || ( ! $iOS && in_array( $options['detect_mpeg'], array( 'all' ) ) ) || ( ! $iOS && ! $Safari && in_array( $options['detect_mpeg'], array( 'nonsafari' ) ) ) ) {
			return $htmlCode . '<!--MPEG-->' . do_shortcode( "[videowhisper_cammpeg webcam=\"$webcamName\" width=\"$width\" height=\"$height\" webstatus=\"1\"]" );
		}

		if ( ( ( $Android || $iOS ) && in_array( $options['detect_hls'], array( 'mobile', 'safari', 'all' ) ) ) || ( $iOS && $options['detect_hls'] == 'ios' ) || ( $Safari && in_array( $options['detect_hls'], array( 'safari', 'all' ) ) ) ) {
			return $htmlCode . '<!--HLS-->' . do_shortcode( "[videowhisper_camhls webcam=\"$webcamName\" width=\"$width\" height=\"$height\" webstatus=\"1\"]" );
		}

		$afterCode = <<<HTMLCODE
<style type="text/css">
<!--

#videowhisper_container_$stream
{
position: relative;
width: $width;
height: $height;
border: solid 1px #999;
}

-->
</style>
HTMLCODE;

		return $htmlCode . self::app_video( $stream, $webcamName, $width, $height ) . $afterCode;

	}


	static function app_video( $stream, $room, $width = '100%', $height = '360px' ) {

		$stream = sanitize_file_name( $stream );

		$swfurl  = dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/live_video.swf?ssl=1&s=' . urlencode( $stream ) . '&n=' . urlencode( $room );
		$swfurl .= '&prefix=' . urlencode( admin_url() . 'admin-ajax.php?action=vmls&task=' );
		$swfurl .= '&extension=' . urlencode( '_none_' );
		$swfurl .= '&ws_res=' . urlencode( dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/' );

		$bgcolor = '#333333';

		$htmlCode = <<<HTMLCODE
<div id="videowhisper_container_$stream">
<object id="videowhisper_video_$stream" width="100%" height="100%" type="application/x-shockwave-flash" data="$swfurl">
<param name="movie" value="$swfurl"></param><param bgcolor="$bgcolor"><param name="scale" value="noscale" /> </param><param name="salign" value="lt"></param><param name="allowFullScreen"
value="true"></param><param name="allowscriptaccess" value="always"></param>
</object>
</div>
HTMLCODE;

		$htmlCode .= self::flash_warn();

		return $htmlCode;

	}


	static function videowhisper_cammpeg( $atts ) {
		// [videowhisper_cammpeg webcam="webcam name" width="480px" height="360px"]

		$stream = '';

		$options = get_option( 'VWliveWebcamsOptions' );

		if ( is_single() ) {
			if ( get_post_type( $postID = get_the_ID() ) == $options['custom_post'] ) {
				$webcamName = self::post_title( get_the_ID() );
			}
		}

			$atts = shortcode_atts(
				array(
					'webcam' => $webcamName,
					'width'  => '480px',
					'height' => '360px',
				),
				$atts,
				'videowhisper_cammpeg'
			);
			$atts = array_map('esc_attr', $atts);

		if ( ! $webcamName ) {
			$webcamName = sanitize_text_field( $atts['webcam'] ); // parameter webcam="name"
		}
		if ( ! $webcamName ) {
			$webcamName = sanitize_text_field( $_GET['n'] );
		}

		if ( ! $postID ) {
			global $wpdb;
			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type=%s LIMIT 0,1", sanitize_file_name( $webcamName ), $options['custom_post'] ) );
		}

		$stream = self::webcamStreamName( $webcamName, $postID, $options );

		$width = $atts['width'];
		if ( ! $width ) {
			$width = '480px';
		}
		$height = $atts['height'];
		if ( ! $height ) {
			$height = '360px';
		}

		if ( ! $stream ) {
			return 'Watch MPEG Dash Error: Missing channel name!';
		}

		$webcamOnline = self::webcamOnline( $postID );
		if ( $options['teaserOffline'] && ! $webcamOnline ) {
			$streamName = '';
			$streamPath = '';

			$video_teaser = get_post_meta( $postID, 'video_teaser', true );
			$streamURL    = self::vsvVideoURL( $video_teaser, $options );
		}

		if ( $options['transcodingAuto'] && $webcamOnline ) {
			$streamName = self::transcodeStream( $stream, 1, $webcamName, 2, 1, $options, $postID ); // require transcoding name
		}

		if ( $streamName ) {
			$streamURL = $options['httpstreamer'] . $streamName . '/manifest.mpd';
		}

		if ( $streamURL ) {
			// not used
			// if (strstr($streamURL,'http://')) wp_enqueue_script('dashjs', 'http://cdn.dashjs.org/latest/dash.all.min.js');
			// else wp_enqueue_script('dashjs', 'https://cdn.dashjs.org/latest/dash.all.min.js');

			// poster
			$thumbUrl = self::webcamThumbSrc( $postID, $stream, $options );

			$htmlCode = <<<HTMLCODE
<!--MPEG Pi:$postID Wo:$webcamOnline Wn:$webcamName S:$stream Sn:$streamName T:$video_teaser Su:$streamURL-->
<video id="videowhisper_mpeg_$stream" class="videowhisper_htmlvideo" width="$width" height="$height" data-dashjs-player autobuffer autoplay loop playsinline muted="muted" controls="true" poster="$thumbUrl" src="$streamURL">
    <div class="fallback" style="display:none">
    <IMG SRC="$thumbUrl">
	    <p>HTML5 MPEG Dash capable browser (i.e. Chrome) is required to open this live stream: $streamURL</p>
	</div>
</video>
<br>MPEG-DASH: Enable sound from browser controls. Transcoding and HTTP based delivery technology may involve high latency.
HTMLCODE;
		} else {
			$htmlCode = '<div class="warning">MPEG Dash format is not currently available for this stream: ' . $stream . '</div>';
		}

		return $htmlCode;
	}


	static function videowhisper_camhls( $atts ) {

		// [videowhisper_camhls webcam="webcam name" width="480px" height="360px"]

		$stream = '';

		$options = get_option( 'VWliveWebcamsOptions' );

		if ( is_single() ) {
			if ( get_post_type( $postID = get_the_ID() ) == $options['custom_post'] ) {
				$webcamName = self::post_title( get_the_ID() );
			}
		}

			$atts = shortcode_atts(
				array(
					'webcam' => $webcamName,
					'width'  => '480px',
					'height' => '360px',
				),
				$atts,
				'videowhisper_camhls'
			);
			$atts = array_map('esc_attr', $atts);

		if ( ! $webcamName ) {
			$webcamName = sanitize_text_field( $atts['webcam'] ); // parameter webcam="name"
		}
		if ( ! $webcamName ) {
			$webcamName = sanitize_text_field( $_GET['n'] );
		}

		if ( ! $postID ) {
			global $wpdb;
			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type=%s LIMIT 0,1", sanitize_file_name( $webcamName ), $options['custom_post'] ) );
		}

		$stream = self::webcamStreamName( $webcamName, $postID, $options );

		$width = $atts['width'];
		if ( ! $width ) {
			$width = '480px';
		}
		$height = $atts['height'];
		if ( ! $height ) {
			$height = '360px';
		}

		if ( ! $stream ) {
			return 'Watch HLS Error: Missing channel name!';
		}

		$webcamOnline = self::webcamOnline( $postID );
		if ( $options['teaserOffline'] && ! $webcamOnline ) {
			$streamName = '';
			$streamPath = '';

			$video_teaser = get_post_meta( $postID, 'video_teaser', true );
			$streamURL    = self::vsvVideoURL( $video_teaser, $options );
		}

		// auto transcoding
		if ( $options['transcodingAuto'] && $webcamOnline ) {
			$streamName = self::transcodeStream( $stream, 1, $webcamName, 2, 1, $options, $postID ); // require transcoding name
		}

		if ( $streamName ) {
			$streamURL = $options['httpstreamer'] . $streamName . '/playlist.m3u8';
		}

		if ( $streamURL ) {
			// poster
			$thumbUrl = self::webcamThumbSrc( $postID, $stream, $options );

			$htmlCode = <<<HTMLCODE
<!--HLS:$postID:$webcamName:$stream-->
<video id="videowhisper_hls_$stream" class="videowhisper_htmlvideo" width="$width" height="$height" autobuffer autoplay loop controls="true" poster="$thumbUrl">
 <source src="$streamURL" type='video/mp4'>
    <div class="fallback" style="display:none">
    <IMG SRC="$thumbUrl">
	    <p>HTML5 HLS capable browser (i.e. Safari) is required to open this live stream: $streamURL</p>
	</div>
</video>
<br>HLS: Enable sound from your browser controls. Transcoding and HTTP based delivery technology may involve higher latency.

<br>
HTMLCODE;
		} else {
			$htmlCode = '<div class="warning">HLS format is not currently available for this stream: ' . $stream . '</div>';
		}

		return $htmlCode;
	}


	static function videowhisper_campreview( $atts ) {

		$options = get_option( 'VWliveWebcamsOptions' );

		$atts = shortcode_atts(
			array(
				'status'   => 'online',
				'order_by' => 'rand',
				'category' => '',
				'perpage'  => 1,
				'perrow'   => 2,
				'width'    => '480px',
				'height'   => '360px',
			),
			$atts,
			'videowhisper_campreview'
		);
		$atts = array_map('esc_attr', $atts);

		$perPage = intval( $atts['perpage'] );
		$perRow  = intval( $atts['perrow'] );

		$pstatus  = sanitize_text_field( $atts['status'] );
		$order_by = sanitize_text_field( $atts['order_by'] );
		$category = sanitize_text_field( $atts['category'] );

		$width = sanitize_text_field( $atts['width'] );
		if ( ! $width ) {
			$width = '480px';
		}
		$height = sanitize_text_field( $atts['height'] );
		if ( ! $height ) {
			$height = '360px';
		}

		$args = array(
			'post_type'      => sanitize_text_field( $options['custom_post'] ),
			'post_status'    => 'publish',
			'posts_per_page' => $perPage,
			'offset'         => 0,
			'order'          => 'DESC',
			'meta_query'     => array(
				'relation'    => 'AND',
				'snapshot'    => array(
					'key'   => 'hasSnapshot',
					'value' => '1',
				),
				'vw_featured' => array(
					'key'     => 'vw_featured',
					'compare' => 'EXISTS',
				),
				'edate'       => array(
					'key'     => 'edate',
					'compare' => 'EXISTS',
				),
			),
		);

		if ($pstatus) switch ( $pstatus ) {

			case 'free':
				$args['meta_query']['public']   = array(
					'key'   => 'privateShow',
					'value' => '0',
				);
				$args['meta_query']['groupCPM'] = array(
					'key'   => 'groupCPM',
					'value' => '0',
				);
				break;
			case 'paid':
				$args['meta_query']['public']   = array(
					'key'   => 'privateShow',
					'value' => '0',
				);
				$args['meta_query']['groupCPM'] = array(
					'key'     => 'groupCPM',
					'value'   => '0',
					'compare' => '>',
				);
				break;
			case 'available_free':
				$args['meta_query']['public']   = array(
					'key'   => 'privateShow',
					'value' => '0',
				);
				$args['meta_query']['online']   = array(
					'key'     => 'edate',
					'value'   => time() - 40,
					'compare' => '>',
				);
				$args['meta_query']['groupCPM'] = array(
					'key'   => 'groupCPM',
					'value' => '0',
				);
				break;
			case 'available_paid':
				$args['meta_query']['public']   = array(
					'key'   => 'privateShow',
					'value' => '0',
				);
				$args['meta_query']['online']   = array(
					'key'     => 'edate',
					'value'   => time() - 40,
					'compare' => '>',
				);
				$args['meta_query']['groupCPM'] = array(
					'key'     => 'groupCPM',
					'value'   => '0',
					'compare' => '>',
				);
				break;

			case 'private':
				$args['meta_query']['private'] = array(
					'key'   => 'privateShow',
					'value' => '1',
				);
				$args['meta_query']['online']  = array(
					'key'     => 'edate',
					'value'   => time() - 40,
					'compare' => '>',
				);
				break;

			case 'online':
				$args['meta_query']['online'] = array(
					'key'     => 'edate',
					'value'   => time() - 40,
					'compare' => '>',
				);
				break;

			case 'public':
				$args['meta_query']['public'] = array(
					'key'   => 'privateShow',
					'value' => '0',
				);
				$args['meta_query']['online'] = array(
					'key'     => 'edate',
					'value'   => time() - 40,
					'compare' => '>',
				);
				break;

			case 'offline':
				$args['meta_query']['offline'] = array(
					'key'     => 'edate',
					'value'   => time() - 40,
					'compare' => '<',
				);
				break;
		}

		switch ( $order_by ) {

			case 'default':
				$args['orderby'] = array(
					'vw_featured' => 'DESC',
					'edate'       => 'DESC',
				);

				break;

			case 'post_date':
				$args['orderby'] = 'post_date';
				break;

			case 'rand':
				$args['orderby'] = 'rand';
				break;

			default:
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = $order_by;
				break;
		}

		if ( $category ) {
			$args['category'] = $category;
		}

		$postslist = get_posts( $args );

		// list cam previews

		$code = '';

		if ( count( $postslist ) > 0 ) {
			$k = 0;
			foreach ( $postslist as $item ) {
				if ( $perRow ) {
					if ( $k ) {
						if ( $k % $perRow == 0 ) {
												$code .= '<br>';
						}
					}
				}

						$webcamName = sanitize_file_name( $item->post_title );

					$code .= do_shortcode( "[videowhisper_camvideo cam=\"$webcamName\" width=\"$width\" height=\"$height\"]" );
			}
		} else {
			$code .= 'No cams currently match preview criteria.';
			// $code .= var_export($args);
		}

		return $code;
	}


	static function videowhisper_camprofile( $atts ) {
		$atts = shortcode_atts(
			array(
				'cam'     => $stream,
				'post_id' => 0,
			),
			$atts,
			'videowhisper_camprofile'
		);
		$atts = array_map('esc_attr', $atts);

		$stream = sanitize_text_field( $atts['cam'] );
		if ( ! $stream ) {
			$stream = sanitize_text_field( $_GET['cam'] );
		}
		$stream = sanitize_file_name( $stream );

		$options = get_option( 'VWliveWebcamsOptions' );

		$post_id = intval( $atts['post_id'] );

		if ( $post_id ) {
			$postID = $post_id;
		} elseif ( $stream ) {
			global $wpdb;
			$postID = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s and post_type=%s LIMIT 0,1', $stream, $options['custom_post'] ) );
		}

		if ( ! $postID ) {
			return "videowhisper_camprofile error: Webcam not found ($stream/$post_id)!";
		}

		return self::webcamProfile( $postID, $options );
	}


	static function videowhisper_camcontent( $atts ) {
		$atts = shortcode_atts(
			array(
				'cam'     => $stream,
				'post_id' => 0,
			),
			$atts,
			'videowhisper_camcontent'
		);
		$atts = array_map('esc_attr', $atts);

		$stream = sanitize_text_field( $atts['cam'] );
		if ( ! $stream ) {
			$stream = sanitize_text_field( $_GET['cam'] );
		}
		$stream = sanitize_file_name( $stream );

		$options = get_option( 'VWliveWebcamsOptions' );

		$post_id = intval( $atts['post_id'] );

		if ( $post_id ) {
			$postID = $post_id;
		} elseif ( $stream ) {
			global $wpdb;
			$postID = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s and post_type=%s LIMIT 0,1', $stream, $options['custom_post'] ) );
		}

		if ( $postID && ! $stream ) {
			global $wpdb;
			$stream = $wpdb->get_var( $wpdb->prepare( 'SELECT post_title FROM ' . $wpdb->posts . ' WHERE ID = %d and post_type=%s LIMIT 0,1', $postID, $options['custom_post'] ) );
		}

		if ( ! $postID || ! $stream ) {
			return "videowhisper_camcontent error: Webcam not found ($stream/$post_id)!";
		}

		return self::webcamContent( $postID, $stream, $options, '' );
	}


	static function videowhisper_caminfo( $atts ) {
		$atts = shortcode_atts(
			array(
				'cam'    => '',
				'info'   => 'cpm',
				'format' => 'csv',
			),
			$atts,
			'videowhisper_caminfo'
		);
		$atts = array_map('esc_attr', $atts);

		$stream = sanitize_text_field( $atts['cam'] );
		if ( ! $stream ) {
			$stream = sanitize_text_field( $_GET['cam'] );
		}
		$stream = sanitize_file_name( $stream );

		if ( ! $stream ) {
			return 'No cam name!';
		}

		$options = get_option( 'VWliveWebcamsOptions' );

		// custom room cost per minute
		global $wpdb;
		$postID = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s and post_type=%s LIMIT 0,1', $stream, $options['custom_post'] ) );

		if ( ! $postID ) {
			return "Cam '$stream' not found!";
		}

		switch ( $atts['info'] ) {
			case 'cpm':
				$result = self::clientCPM( $stream, $options, $postID );
				break;

			case 'online':
				$edate  = get_post_meta( $postID, 'edate', true );
				$result = self::format_age( time() - $edate );
				break;

			case 'label':
				$result = get_post_meta( $postID, 'vw_roomLabel', true );
				break;

			case 'brief':
				$result = get_post_meta( $postID, 'vw_roomBrief', true );
				break;

			case 'tags':
				$tags     = wp_get_post_tags( $postID, array( 'fields' => 'names' ) );
				$roomTags = '';
				if ( ! empty( $tags ) ) {
					if ( ! is_wp_error( $tags ) ) {
						$result = $tags;
					}
				}
				break;

			case 'performers':
				$checkin = get_post_meta( $postID, 'checkin', true );
				if ( $checkin ) {
					foreach ( $checkin as $performerID ) {
						$result[] = self::performerLink( $performerID, $options );
					}
				}

				break;

			case 'groupMode':
				$result = get_post_meta( $postID, 'groupMode', true );
				break;

			case 'groupCPM':
				$result = get_post_meta( $postID, 'groupCPM', true );
				break;

			default:
				$result = 'Info type incorrect!';
		}

		switch ( $atts['format'] ) {
			case 'csv':
				if ( is_array( $result ) ) {
					foreach ( $result as $tag ) {
						$roomTags .= ( $roomTags ? ', ' : '' ) . $tag;
					}
					return $roomTags;
				} else {
					return $result;
				}

				break;

			case 'serialize':
				return serialize( $result );
			break;

			default:
				return $result;

			break;
		}

	}


	static function videowhisper_webcams( $atts ) {
		$options = get_option( 'VWliveWebcamsOptions' );

		// shortocode attributes
		$atts = shortcode_atts(
			array(
				'layout' 		  => sanitize_text_field( $options['layoutDefault'] ?? 'grid' ),
				'menu'            => sanitize_text_field( $options['listingsMenu']  ?? 'auto' ),
				'perpage'         => intval( $options['perPage'] ?? 12 ),
				'ban'             => '0',
				'perrow'          => '',
				'order_by'        => 'default',
				'category_id'     => isset( $_GET['category_id'] ) ? intval( $_GET['category_id'] ) : '',
				'pstatus'         => isset( $_GET['pstatus'] ) ? sanitize_text_field( $_GET['pstatus'] ) : '',
				'lovense'         => isset( $_GET['lovense'] ) ? sanitize_text_field( $_GET['lovense'] ) : '',
				'select_category' => '1',
				'select_filters'  => '1',
				'select_tags'     => '1',
				'select_name'     => '1',
				'select_order'    => '1',
				'select_status'   => '1',
				'select_page'     => '1',
				'select_layout'   => '1',
				'include_css'     => '1',
		//		'url_vars'        => '1',
		//		'url_vars_fixed'  => '1',
				'persistent_off' => '0',
				'studio_id'       => '',
				'author_id'       => '',
				'tags'            => isset( $_GET['tags'] ) ? sanitize_text_field( $_GET['tags'] ) : '',
				'name'            => isset( $_GET['name'] ) ? sanitize_text_field( $_GET['name'] ) : '',
				'id'              => '',
			),
			$atts,
			'videowhisper_webcams'
		);
		$atts = array_map('esc_attr', $atts);

		$id = sanitize_text_field( $atts['id'] );
		if ( ! $id ) {
			$id = 1; // uniqid();
		}

		// get vars from url
		/*
		if ( $atts['url_vars'] ) {
			$cid = intval( $_GET['cid'] ?? 0 );
			if ( $cid ) {
				$atts['category_id'] = $cid;
				if ( $atts['url_vars_fixed'] ) {
					$atts['select_category'] = '0';
				}
			}
		}
		*/

		$htmlCode = '';

		// semantic ui : listings
		self::enqueueUI();

		// ajax url
		$ajaxurl = admin_url() . 'admin-ajax.php?action=vmls_cams&menu=' . $atts['menu'] . '&layout=' . $atts['layout'] . '&&pp=' . $atts['perpage'] . '&pr=' . $atts['perrow'] . '&ob=' . $atts['order_by'] . '&cat=' . $atts['category_id'] . '&st=' . $atts['pstatus'] . '&lt=' . $atts['lovense'] . '&sc=' . $atts['select_category'] . '&sf=' . $atts['select_filters']  . '&so=' . $atts['select_order'] . '&ss=' . $atts['select_status'] . '&sp=' . $atts['select_page'] . '&sl=' . $atts['select_layout'] . '&sn=' . $atts['select_name'] . '&sg=' . $atts['select_tags'] . '&id=' . esc_attr( $id ) . '&tags=' . urlencode( $atts['tags'] ) . '&name=' . urlencode( $atts['name'] );

		if ( $atts['studio_id'] ) {
			$ajaxurl .= '&studio_id=' . intval( $atts['studio_id'] );
		}

		if ( $atts['author_id'] ) {
			$ajaxurl .= '&author_id=' . intval( $atts['author_id'] );
		}

		if ( $atts['ban'] ) {
			$ajaxurl .= '&ban=' . $atts['ban'];
		}

				// custom profile filters
				if (is_array($options['profileFields']))
				foreach ( $options['profileFields'] as $field => $parameters ) {
					if ( isset($parameters['filter']) && isset($parameters['options']) ) {
						$fieldName  = sanitize_title( trim( $field ) );
						$fieldMeta  = 'vwf_' . $fieldName;
						$fieldValue = sanitize_text_field( $_GET[ $fieldMeta ] ?? '' );
						if ( $fieldValue ) {
							$ajaxurl .= '&' . $fieldMeta . '=' . urlencode( $fieldValue );
						}
					}
				}

		$loadingMessage = '<div class="ui active inline text large loader">' . __( 'Loading Live Webcams', 'ppv-live-webcams' ) . '...</div>';

		if ( ($options['filtersSave'] ?? false) && !$atts['persistent_off'])
		if (is_user_logged_in())
		{
			$ajaxURLall = get_user_meta( get_current_user_id(), 'vwf_filters_url', true );
			if ($ajaxURLall ) 
			{
				$ajaxurl = $ajaxURLall;
				$loadingMessage = '<div class="ui active inline text large loader">' . __( 'Loading your last search', 'ppv-live-webcams' ) . '...</div>';
			}
		}


		$codeReload = '';

		if (!$options['debugMode']) $codeReload = 'setInterval("loadWebcams'. $id . '()", 15000);';

		$htmlCode .= <<<HTMLCODE
<PRE style="display: none">
<SCRIPT>
var aurl$id = '$ajaxurl';
var \$j = jQuery.noConflict();
var loader$id;

	function loadWebcams$id(message){

	if (message)
	if (message.length > 0)
	{
	  \$j("#videowhisperWebcams$id").html(message);
	}

		if (loader$id) loader$id.abort();

		loader$id = \$j.ajax({
			url: aurl$id,
			data: "interfaceid=$id",
			success: function(data) {
				\$j("#videowhisperWebcams$id").html(data);
				jQuery(".ui.dropdown:not(.multi,.fpsDropdown)").dropdown();
				jQuery(".ui.rating.readonly").rating("disable");
			}
		});
	}

	jQuery(document).ready(function(){
		loadWebcams$id();
		$codeReload
	});

</SCRIPT>
</PRE>
<div id="videowhisperWebcams$id" class="videowhisperWebcams">
    $loadingMessage
</div>
<div style="clear:both"><div/>
HTMLCODE;

		if ( $atts['include_css'] ) {
			$htmlCode .= '<STYLE>' . html_entity_decode( stripslashes( $options['customCSS'] ) ) . '</STYLE>';
		}

		return $htmlCode;
	}


	// ! AJAX Webcams List
	static function vmls_cams_callback() {
		 // ajax called

		// cam meta:
		// edate s
		// viewers n
		// maxViewers n
		// maxDate s
		// hasSnapshot 1
		// privateShow 0

		$options = get_option( 'VWliveWebcamsOptions' );

		$isMobile = (bool) preg_match( '#\b(ip(hone|od|ad)|android|opera m(ob|in)i|windows (phone|ce)|blackberry|tablet|s(ymbian|eries60|amsung)|p(laybook|alm|rofile/midp|laystation portable)|nokia|fennec|htc[\-_]|mobile|up\.browser|[1-4][0-9]{2}x[1-4][0-9]{2})\b#i', $_SERVER['HTTP_USER_AGENT'] );

		// output clean (clear 0)
		if (ob_get_length()) {
			ob_clean();
		}

		// cache : do not generate more often than 14s (client refresh each 15s)
		// client still updates but less load on high volume (many users online)

		/*
		$cacheQuery = $_SERVER['QUERY_STRING'] . '&mob='.$isMobile . '&login=' . is_user_logged_in();
		$cacheKey = sha1($cacheQuery);

		//$cachePath
		$cachePath = $options['uploadsPath'];
		if (!file_exists($cachePath)) mkdir($cachePath);
		$cachePath .= "/_cache/";
		if (!file_exists($cachePath)) mkdir($cachePath);
		$cachePath .= $cacheKey;

		if (!self::timeTo('cl_' . $cacheKey, 9, $options))
			if (file_exists($cachePath))
			{
				echo self::stringLoad($cachePath);
				exit;
			}
		*/

		if ( $options['videosharevod'] ) {
			$optionsVSV = get_option( 'VWvideoShareOptions' );
		} else $optionsVSV = null;

		$debugCode = '';
		// safari requires muted for autoplay
		$isSafari = (bool) ( strpos( $_SERVER['HTTP_USER_AGENT'], 'AppleWebKit' ) && strpos( $_SERVER['HTTP_USER_AGENT'], 'Safari' ) );
		if ( $isSafari ) {
			$previewMuted = 'muted';
		} else {
			$previewMuted = '';
		}

		$isLoggedin = is_user_logged_in();
		$isModerator = 0;
		if  ( $isLoggedin ) $isModerator = self::isModerator( get_current_user_id(), $options);


		$debugMode = $options['debugMode'];

		// widget id, to avoid duplicate function names if using multiple on same page
		$id = sanitize_file_name( $_GET['id'] ?? '' );

		// pagination
		$perPage = intval( $_GET['pp'] ?? 0 );
		if ( ! $perPage ) {
			$perPage = $options['perPage'];
		}

		$page   = intval( $_GET['p'] ?? 0 );
		$offset = $page * $perPage;

		$perRow = intval( $_GET['pr'] ?? 0 );

		$json = boolval($_GET['json'] ?? false);
		$result = [ 'rooms' => [] ];

		// admin side
		$ban = intval( $_GET['ban'] ?? 0 );

		$category = intval( $_GET['cat'] ?? 0 );
		$pstatus          = sanitize_text_field( $_GET['st'] ?? '');
		$ptoy = sanitize_text_field( $_GET['lt'] ?? '');

		$menu             = sanitize_text_field( $_GET['menu'] ?? '');
		if ( $menu == 'auto' && $isMobile )  $menu = false;		//auto disables on mobile

		// order
		$order_by = sanitize_text_field( $_GET['ob'] );
		if ( ! $order_by ) {
			$order_by = 'default';
		}

		// options
		$selectCategory = intval( $_GET['sc'] ?? 0 );
		$selectOrder    = intval( $_GET['so'] ?? 0 );
		$selectPage     = intval( $_GET['sp'] ?? 0 );
		$selectLayout   = intval( $_GET['sl'] ?? 0 );

		$selectStatus = intval( $_GET['ss'] ?? 0 );

		$selectName = intval( $_GET['sn'] ?? 0 );
		$selectTags = intval( $_GET['sg'] ?? 0 );

		$selectFilters = intval( $_GET['sf'] ?? 0 );

		// studio/user
		$studio_id = intval( $_GET['studio_id'] ?? 0 );
		if ($studio_id == -1) $studio_id = get_current_user_id();

		$author_id = intval( $_GET['author_id'] ?? 0 );
		if ($author_id == -1) $author_id = get_current_user_id();

		// tags,name search
		$tags = sanitize_text_field( $_GET['tags'] ?? '' );
		$name = sanitize_text_field( $_GET['name'] ?? '');
		if ( $name == 'undefined' ) {
			$name = '';
		}
		if ( $tags == 'undefined' ) {
			$tags = '';
		}

		// layout: grid/list/horizontal
		$layout = sanitize_text_field( $_GET['layout'] ?? '' );
		if ( ! $layout ) {
			$layout = $options['layoutDefault'];
		}
		if ( ! $layout ) {
			$layout = 'grid';
		}

		// thumbs dir
		$dir = $options['uploadsPath'] . '/_thumbs';

		$ajaxurl = admin_url() . 'admin-ajax.php?action=vmls_cams&menu=' . $menu . '&pp=' . $perPage . '&pr=' . $perRow . '&ss=' . $selectStatus . '&sc=' . $selectCategory . '&so=' . $selectOrder . '&sn=' . $selectName . '&sg=' . $selectTags . '&sp=' . $selectPage . '&sl=' . $selectLayout . '&id=' . esc_attr( $id ) . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ) . '&sf=' . $selectFilters;

		if ( $studio_id ) {
			$ajaxurl .= '&studio_id=' . $studio_id;
		}

		if ( $author_id ) {
			$ajaxurl .= '&author_id=' . $author_id;
		}

		if ( $ban ) {
			$ajaxurl .= '&ban=' . $ban; // admin side
		}


if ($json)
{
	//setup extra fields
	$json_fields = [];
	foreach ( $options['profileFields'] as $field => $parameters )
	if ( ( isset($parameters['filter']) && $parameters['filter'] ) || ( isset ( $parameters['json']) && $parameters['json'] ) )
	{	$fieldName  = sanitize_title( trim( $field ) );
		$json_fields[] = 'vwf_' . $fieldName ;
	}
}
		//$ajaxURLpag   = $ajaxurl . '&p=' . $page . '&layout=' . $layout. '&st=' . $pstatus;
		$ajaxURLord   = $ajaxurl . '&cat=' . $category . '&layout=' . $layout . '&st=' . $pstatus . '&lt=' . $ptoy; //order by
		$ajaxURLcat   = $ajaxurl . '&ob=' . $order_by . '&layout=' . $layout . '&st=' . $pstatus. '&lt=' . $ptoy; // category select
		$ajaxURLstat = $ajaxurl . '&cat=' . $category . '&ob=' . $order_by . '&layout=' . $layout. '&lt=' . $ptoy; // status select
		$ajaxURLtoy = $ajaxurl . '&cat=' . $category . '&ob=' . $order_by . '&layout=' . $layout . '&st=' . $pstatus; // status toy

		$ajaxURLlay = $ajaxurl . '&cat=' . $category . '&ob=' . $order_by . '&p=' . $page. '&st=' . $pstatus . '&lt=' . $ptoy; // layout select



		$ajaxURLall = $ajaxurl . '&cat=' . $category . '&ob=' . $order_by . '&layout=' . $layout . '&st=' . $pstatus. '&lt=' . $ptoy; // all persist: reload, page, search

		// $htmlCode .= '<div class="videowhisperListOptions">';

// menu start
		if ( $menu && !$json ) {
			echo '
<style>
	.vwRoomsSidebar {
    grid-area: sidebar;
  }

  .vwRoomsContent {
    grid-area: content;
    position: relative;
    display: grid;
  }

.vwRoomsWrapper {
	position: relative;
    display: grid;
    grid-gap: 4px;
    grid-template-columns: 120px  auto;
    grid-template-areas: "sidebar content";
    color: #444;
  }

  .ui .title { height: auto !important; background-color: inherit !important}
  .ui .content {margin: 0 !important; }
  .vwRoomsSidebar .menu { max-width: 120px !important;}

 </style>
 <div class="vwRoomsWrapper">
 <div class="vwRoomsSidebar">';

			if ( $selectCategory ) {
				echo '
<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' accordion small vwFields">

  <div class="title">
    <i class="list icon"></i>
    ' . __( 'Category', 'ppv-live-webcams' ) . ' ' . ( esc_html( $category ) ? '<i class="check icon small"></i>' : '' ) . '
  </div>
  <div class="' . ( $category ? 'active' : '' ) . ' content">
  <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' vertical menu small">
  ';
				echo '  <a class="' . ( $category == 0 ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLcat ) . '&cat=0\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading category', 'ppv-live-webcams' ) . '...</div>\')">' . __( 'All Categories', 'ppv-live-webcams' ) . '</a> ';

				$categories = get_categories( array( 'taxonomy' => 'category' ) );
				foreach ( $categories as $cat ) {
					echo '  <a class="' . ( $category == $cat->term_id ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_html( $ajaxURLcat ) . '&cat=' . esc_attr( $cat->term_id ) . '\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading category', 'ppv-live-webcams' ) . '...</div>\')">' . esc_html( $cat->name ) . '</a> ';
				}

				echo '</div>

  </div>
</div>';

			}

			if ( $selectStatus ) {
				echo '
<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' accordion small vwFields">

  <div class="title">
    <i class="power off icon"></i>
    ' . __( 'Status', 'ppv-live-webcams' ) . ' ' . ( $pstatus ? '<i class="check icon small"></i>' : '' ) . '
  </div>

  <div class="' . ( $pstatus != '' ? 'active' : '' ) . ' content">
  <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' vertical menu small">

  <a class="' . ( $pstatus == '' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&st=\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . esc_attr(self::label( 'All', __( 'All', 'ppv-live-webcams' ), $options )) . '</a>

  <a class="' . ( $pstatus == 'online' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&st=online\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . esc_attr(self::label( 'Online', __( 'Online', 'ppv-live-webcams' ), $options )) . '</a>
  <a class="' . ( $pstatus == 'public' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&st=public\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . esc_attr(self::label( 'Available', __( 'Online, Available', 'ppv-live-webcams' ), $options )) . '</a>

  <a class="' . ( $pstatus == 'paid' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&st=paid\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . esc_attr(self::label( 'Paid', __( 'All, Paid', 'ppv-live-webcams' ), $options )) . '</a>
  <a class="' . ( $pstatus == 'free' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&st=free\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . esc_attr(self::label( 'Free', __( 'All, Free', 'ppv-live-webcams' ), $options )) . '</a>

  <a class="' . ( $pstatus == 'available_paid' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&st=available_paid\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . esc_attr(self::label( 'Available Paid', __( 'Available, Paid', 'ppv-live-webcams' ), $options )) . '</a>
  <a class="' . ( $pstatus == 'available_free' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&st=available_free\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . esc_attr(self::label( 'Available Free', __( 'Available, Free', 'ppv-live-webcams' ), $options )) . '</a>

  <a class="' . ( $pstatus == 'private' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&st=private\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . esc_attr(self::label( 'In Private', __( 'Online, In Private', 'ppv-live-webcams' ), $options )) . '</a>
  <a class="' . ( $pstatus == 'offline' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&st=offline\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . esc_attr(self::label( 'Offline', __( 'Offline', 'ppv-live-webcams' ), $options )) . '</a>

</div>

  </div>
</div>';
			}
			if ( $selectStatus && $options['lovense'] && $options['lovenseToy'] ) {
				echo '
<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' accordion small vwFields">

  <div class="title">
    <i class="game icon"></i>
    ' . __( 'Lovense', 'ppv-live-webcams' ) . ' ' . ( $ptoy != '' ? '<i class="check icon small"></i>' : '' ) . '
  </div>

  <div class="' . ( $ptoy != '' ? 'active' : '' ) . ' content">
  <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' vertical menu small">

  <a class="' . ( $ptoy == '' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLtoy) . '&lt=\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . __( 'Any', 'ppv-live-webcams' ) . '</a>

  <a class="' . ( $ptoy == '1' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLtoy) . '&lt=1\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . __( 'Available', 'ppv-live-webcams' ) . '</a>

  <a class="' . ( $ptoy == '0' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLtoy) . '&lt=0\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Updating listings, please wait', 'ppv-live-webcams' ) . '...</div>\')">' . __( 'Not Available', 'ppv-live-webcams' ) . '</a>
  </div>

  </div>
</div>';
			} 

			if ( $selectOrder ) {

				$optionsOrders = array(
					'default'    => __( 'Default Order', 'ppv-live-webcams' ),
					'viewers'    => __( 'Current Viewers', 'ppv-live-webcams' ),
					'edate'      => __( 'Broadcast Recently', 'ppv-live-webcams' ),
					'post_date'  => __( 'Created Recently', 'ppv-live-webcams' ),
					'maxViewers' => __( 'Maximum Viewers', 'ppv-live-webcams' ),
					'rand'       => __( 'Random', 'ppv-live-webcams' ),
				);

				if ( $options['rateStarReview'] ) {
					$optionsOrders['rateStarReview_voteCount'] = __( 'Votes', 'ppv-live-webcams' );
					$optionsOrders['rateStarReview_rating']       = __( 'Rating', 'ppv-live-webcams' );
					$optionsOrders['rateStarReview_ratingNumber'] = __( 'Ratings Number', 'ppv-live-webcams' );
					$optionsOrders['rateStarReview_ratingPoints'] = __( 'Rate Popularity', 'ppv-live-webcams' );
					if ( $category ) {
						$optionsOrders[ 'rateStarReview_rating_category' . $category ]       = __( 'Rating', 'ppv-live-webcams' ) . ' ' . __( 'in Category', 'ppv-live-webcams' );
						$optionsOrders[ 'rateStarReview_ratingNumber_category' . $category ] = __( 'Ratings Number', 'ppv-live-webcams' ) . ' ' . __( 'in Category', 'ppv-live-webcams' );
						$optionsOrders[ 'rateStarReview_ratingPoints_category' . $category ] = __( 'Rate Popularity', 'ppv-live-webcams' ) . ' ' . __( 'in Category', 'ppv-live-webcams' );
					}
				}

				echo '
<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' accordion small vwFields">

  <div class="title">
    <i class="sort icon"></i>
    ' . __( 'Order By', 'ppv-live-webcams' ) . ' ' . ( $order_by != 'default' ? '<i class="check icon small"></i>' : '' ) . '
  </div>
  <div class="' . ( $order_by != 'default' ? 'active' : '' ) . ' content">
  <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' vertical menu small">
  ';

				foreach ( $optionsOrders as $key => $value ) {
					echo '  <a class="' . ( $order_by == $key ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLord ) . '&ob=' . esc_attr( $key ) . '\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Ordering Rooms', 'ppv-live-webcams' ) . '...</div>\')">' . esc_html( $value ) . '</a> ';
				}

				echo '</div>

  </div>
</div>';

			}

			$customFilters = array();

			if ($selectFilters)
			{
			if (is_array($options['profileFields']))
			foreach ( $options['profileFields'] as $field => $parameters ) {
				if ( isset($parameters['filter']) && ( isset($parameters['options']) || in_array($parameters['type'], ['location', 'language']) )  ) {

					$fieldName  = sanitize_title( trim( $field ) );
					$fieldMeta  = 'vwf_' . $fieldName;

					if ($parameters['type'] == 'location')
					{
						//handle location fields
						foreach ( array('continent', 'country', 'region') as $locationField )
						{
							$fieldMetaL = 'vwf_' . $fieldName . '_'. $locationField;
							if (isset( $_GET[ $fieldMetaL ])) $fieldValue = $_GET[ $fieldMetaL ] ?? array();
							else $fieldValue = '';

							if (is_array($fieldValue)) foreach ($fieldValue as $key => $value) $fieldValue[$key] = sanitize_text_field( $value );
							else $fieldValue = false;

							if ( $fieldValue ) {
								$customFilters[ $fieldMetaL ] = $fieldValue;
							}
						}
						continue;
					}

					if ( !isset($_GET[ $fieldMeta ]) || is_array($_GET[ $fieldMeta ] ?? ''))
					{
						$fieldValue = $_GET[ $fieldMeta ] ?? array();
						foreach ($fieldValue as $key => $value) $fieldValue[$key] = sanitize_text_field( $value );

						//remove duplicates
						$fieldValue = array_unique($fieldValue);
					}else $fieldValue = sanitize_text_field( $_GET[ $fieldMeta ] ?? '' );
					 

					if ( $fieldValue ) {
						$customFilters[ $fieldMeta ] = $fieldValue;
					}
				}
			}

			if ( count( $customFilters ) ) {
				$ajaxURLall = add_query_arg( $customFilters, $ajaxURLall );// update url query
			}

			if (is_array($options['profileFields']))
			foreach ( $options['profileFields'] as $field => $parameters ) {
				if ( isset($parameters['filter']) && isset($parameters['options']) ) {

					$fieldName = sanitize_title( trim( $field ) );
					$fieldMeta = 'vwf_' . $fieldName;

					//if (in_array($parameters['type'], array('checkboxes', 'multiselect'))) 
					if ( !isset($_GET[ $fieldMeta ]) || is_array($_GET[ $fieldMeta ] ?? '') )
					{
						//get values from field passed as array in GET
						$fieldValue = $_GET[ $fieldMeta ] ?? array();
						//if (!is_array($fieldValue)) $fieldValue = array($_GET[ $fieldMeta ]);
						foreach ($fieldValue as $key => $value) $fieldValue[$key] = sanitize_text_field( $value );
						$emptyValue = array();
					}else 
					{
						$fieldValue = sanitize_text_field( $_GET[ $fieldMeta ] ?? '' );
						$emptyValue = '';
					}
		
					$parameters['options'] = stripslashes( $parameters['options'] ?? '' );

					//if $parameters['options'] contains | get 	$fieldOptions by split by | otherwise by /
					$fieldOptions = strpos($parameters['options'], '|') ? explode('|', $parameters['options']) : explode('/', $parameters['options']);
				

					$icon = isset($parameters['icon']) ? stripslashes( $parameters['icon'] ) : ( $options['profileFieldIcon'] ?? false );

					//if (in_array($parameters['type'], array('checkboxes', 'multiselect'))) 
					if (is_array($fieldValue))
						$active = !empty($fieldValue) ? 'active' : '';
					else $active = ( $fieldValue ? 'active' : '');

					echo '
<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' accordion small vwFields">

  <div class="title">
    <i class="' . ( $icon ? esc_attr( $icon ) : 'dropdown icon' ) .'"></i>
    ' . esc_html( $field ) . ' ' . ( $active ? '<i class="check icon tiny"></i>' : '' ) . '
  </div>
  <div class="' . esc_attr( $active ) . ' content">
  <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' vertical menu small">
  ';

					echo '  <a class="' . ( $active ? '' : 'active') . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . add_query_arg( $fieldMeta, $emptyValue, $ajaxURLall ) . '\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Filtering Rooms', 'ppv-live-webcams' ) . '...</div>\')">' . __( 'Any', 'ppv-live-webcams' ) .' <i class="window close outline icon small"></i></a> ';

					//echo json_encode($fieldValue);

					foreach ( $fieldOptions as $fieldOption ) {

						$setValue = $fieldValue; //copy

						//if (in_array($parameters['type'], array('checkboxes', 'multiselect'))) 
						if ( is_array($fieldValue) )
						{
							if (in_array($fieldOption, $setValue))
							{
								//remove from array
								$setValue = array_diff($setValue, array($fieldOption));
							}else
							{
								$setValue[] = sanitize_text_field( $fieldOption );
							}
							$urlQuery = add_query_arg( $fieldMeta,  $setValue, $ajaxURLall );

							$active = in_array($fieldOption, $fieldValue) ? 'active' : '';
						}else 
						{
							$urlQuery = add_query_arg( $fieldMeta,  sanitize_text_field( $fieldOption ), $ajaxURLall );
							$active = $fieldValue == $fieldOption ? 'active' : '';
						}

						echo '  <a class="' . esc_attr($active) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $urlQuery ) . '\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Filtering Rooms', 'ppv-live-webcams' ) . '...</div>\')">' . esc_html( $fieldOption ) . ' '. ( $active ? '<i class="check icon tiny"></i>' : '' ). '</a> ';
					}

					echo '</div>

  </div>
</div>';

				}
			}
		//end $selectFilters
		}

			echo '
<PRE style="display: none"><SCRIPT language="JavaScript">
jQuery(document).ready(function()
{
jQuery(".ui.accordion.vwFields").accordion({exclusive:false});
});
</SCRIPT></PRE>
';
			echo '</div><div class="vwRoomsContent">';
		}

		//end menu

		// ! header option controls
if (!$json)
{
		echo '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' tiny equal width form"><div class="inline fields">';

		if ( $selectCategory && ! $menu ) {
			echo '<div class="field">' . wp_dropdown_categories( 'echo=0&name=category' . esc_attr( $id ) . '&hide_empty=1&class=ui+dropdown+fluid+v-select&show_option_all=' . esc_attr(self::label( 'All Categories', __( 'All Categories', 'ppv-live-webcams' ), $options )) . '&selected=' . $category ) . '</div>';
			echo '<script>var category' . esc_attr( $id ) . ' = document.getElementById("category' . esc_attr( $id ) . '"); category' . esc_attr( $id ) . '.onchange = function(){aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLcat ) . '&cat=\'+ this.value; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading category...</div>\')}
			</script>';
		}

		if ( $selectStatus && ! $menu ) {
			echo '<div class="field"><select class="ui dropdown v-select fluid" id="pstatus' . esc_attr( $id ) . '" name="pstatus' . esc_attr( $id ) . '" onchange="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&st=\'+ this.value; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __('Updating listings, please wait', 'ppv-live-webcams') . '...</div>\')">';

			echo '<option value="all"' . ( $pstatus == '' ? ' selected' : '' ) . '>' . esc_attr(self::label( 'All', __( 'All', 'ppv-live-webcams' ), $options )) . '</option>';

			echo '<option value="online"' . ( $pstatus == 'online' ? ' selected' : '' ) . '>' . esc_attr(self::label( 'Online', __( 'Online', 'ppv-live-webcams' ), $options )) . '</option>';

			echo '<option value="public"' . ( $pstatus == 'public' ? ' selected' : '' ) . '>' . esc_attr(self::label( 'Available', __( 'Online, Available', 'ppv-live-webcams' ), $options )) . '</option>';

			echo '<option value="paid"' . ( $pstatus == 'paid' ? ' selected' : '' ) . '>' . esc_attr(self::label( 'Paid', __( 'All, Paid', 'ppv-live-webcams' ), $options )) . '</option>';

			echo '<option value="free"' . ( $pstatus == 'free' ? ' selected' : '' ) . '>' . esc_attr(self::label( 'Free', __( 'All, Free', 'ppv-live-webcams' ), $options )) . '</option>';

			echo '<option value="available_paid"' . ( $pstatus == 'available_paid' ? ' selected' : '' ) . '>' . esc_attr(self::label( 'Available Paid', __( 'Available, Paid', 'ppv-live-webcams' ), $options )) . '</option>';

			echo '<option value="available_free"' . ( $pstatus == 'available_free' ? ' selected' : '' ) . '>' . esc_attr(self::label( 'Available Free', __( 'Available, Free', 'ppv-live-webcams' ), $options )) . '</option>';

			echo '<option value="private"' . ( $pstatus == 'private' ? ' selected' : '' ) . '>' . esc_attr(self::label( 'In Private', __( 'Online, In Private', 'ppv-live-webcams' ), $options )) . '</option>';

			echo '<option value="offline"' . ( $pstatus == 'offline' ? ' selected' : '' ) . '>' . esc_attr(self::label( 'Offline', __( 'Offline', 'ppv-live-webcams' ), $options )) . '</option>';

			echo '</select></div>';

		}

		if ( $selectOrder && ! $menu ) {

			echo '<div class="field"><select class="ui dropdown v-select fluid" id="order_by' . esc_attr( $id ) . '" name="order_by' . esc_attr( $id ) . '" onchange="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLord ) . '&ob=\'+ this.value; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Ordering webcams...</div>\')">';
			echo '<option value="">' . __( 'Order By', 'ppv-live-webcams' ) . ':</option>';

			echo '<option value="default"' . ( $order_by == 'default' ? ' selected' : '' ) . '>' . __( 'Default Order', 'ppv-live-webcams' ) . '</option>';

			echo '<option value="viewers"' . ( $order_by == 'viewers' ? ' selected' : '' ) . '>' . __( 'Current Viewers', 'ppv-live-webcams' ) . '</option>';

			echo '<option value="edate"' . ( $order_by == 'edate' ? ' selected' : '' ) . '>' . __( 'Broadcast Recently', 'ppv-live-webcams' ) . '</option>';
			echo '<option value="post_date"' . ( $order_by == 'post_date' ? ' selected' : '' ) . '>' . __( 'Created Recently', 'ppv-live-webcams' ) . '</option>';

			echo '<option value="maxViewers"' . ( $order_by == 'maxViewers' ? ' selected' : '' ) . '>' . __( 'Maximum Viewers', 'ppv-live-webcams' ) . '</option>';

			echo '<option value="rand"' . ( $order_by == 'rand' ? ' selected' : '' ) . '>' . __( 'Random', 'ppv-live-webcams' ) . '</option>';

			if ( $options['rateStarReview'] ) {

				echo '<option value="rateStarReview_voteCount"' . ( $order_by == 'rateStarReview_voteCount' ? ' selected' : '' ) . '>' . __( 'Votes', 'ppv-live-webcams' ) . '</option>';

				echo '<option value="rateStarReview_rating"' . ( $order_by == 'rateStarReview_rating' ? ' selected' : '' ) . '>' . __( 'Rating', 'ppv-live-webcams' ) . '</option>';
				echo '<option value="rateStarReview_ratingNumber"' . ( $order_by == 'rateStarReview_ratingNumber' ? ' selected' : '' ) . '>' . __( 'Ratings Number', 'ppv-live-webcams' ) . '</option>';
				echo '<option value="rateStarReview_ratingPoints"' . ( $order_by == 'rateStarReview_ratingPoints' ? ' selected' : '' ) . '>' . __( 'Rate Popularity', 'ppv-live-webcams' ) . '</option>';

				if ( $category ) {
					echo '<option value="rateStarReview_rating_category' . esc_attr( $category ) . '"' . ( $order_by == 'rateStarReview_rating_category' . $category ? ' selected' : '' ) . '>' . __( 'Rating', 'ppv-live-webcams' ) . ' ' . __( 'in Category', 'ppv-live-webcams' ) . '</option>';
					echo '<option value="rateStarReview_ratingNumber_category' . esc_attr( $category ) . '"' . ( $order_by == 'rateStarReview_ratingNumber_category' . $category ? ' selected' : '' ) . '>' . __( 'Ratings Number', 'ppv-live-webcams' ) . ' ' . __( 'in Category', 'ppv-live-webcams' ) . '</option>';
					echo '<option value="rateStarReview_ratingPoints_category' . esc_attr( $category ) . '"' . ( $order_by == 'rateStarReview_ratingPoints_category' . $category ? ' selected' : '' ) . '>' . __( 'Rate Popularity', 'ppv-live-webcams' ) . ' ' . __( 'in Category', 'ppv-live-webcams' ) . '</option>';
				}
			}

			echo '</select></div>';

		}


		if ($selectFilters) //special filter types with many options always show as dropdowns
		{

			//todo: optimize by loading only continents

			//load location info
			$locationsPath = $options['uploadsPath'] . '/_locations/';
			$locationsPathDefaut = plugin_dir_path( __FILE__ ) . 'data/';

			$fileLocations = $locationsPath . 'locations.json';
			$fileLanguages = $locationsPath . 'languages.json';

			if ( ! file_exists( $fileLocations ) )$fileLocations = $locationsPathDefaut . 'locations.json';
			if ( ! file_exists( $fileLanguages ) )$fileLanguages = $locationsPathDefaut . 'languages.json';

			$languages = array('en'=> 'English');
			if ($options['languageFilters'] ?? false)
			{
			if ( file_exists( $fileLanguages ) ) $languages = json_decode( file_get_contents( $fileLanguages ), true );
			if ( ! is_array( $languages ) ) $languages = array('en'=> 'English');
			}

			$locations = array();
			$continents = array();
			$countries = array();
			$regions = array();

			if ($options['locationFilters'] ?? false)
			{
			if ( file_exists( $fileLocations ) ) $locations = json_decode( file_get_contents( $fileLocations ), true );
			if ( ! is_array( $locations ) ) $locations = array();

			foreach ( $locations as $continent_code => $continent )
			{
				$continents[ $continent_code ] = $continent['continent_name'];
				foreach ( $continent['countries'] as $country_code => $country )
				{
					$countries[ $country_code ] = $country['country_name'];

					if ($options['locationFilters'] != 2) 
					if ( isset( $country['regions'] ) ) {
						foreach ( $country['regions'] as $region_code => $region ) {
							$regions[ $country_code ][ $region_code ] = $region['region_name'];
						}
					}
				}
			}

			}

				if (is_array($options['profileFields']))
				foreach ( $options['profileFields'] as $field => $parameters ) {
					if ( isset($parameters['filter']) && (in_array($parameters['type'], ['location', 'language']) ) )
					{
						$fieldName  = sanitize_title( trim( $field ) );
						$fieldMeta  = 'vwf_' . $fieldName;
	
						if ( is_array($_GET[ $fieldMeta ] ?? '' ) )
						{
							//get values from field passed as array in GET
							$fieldValue = $_GET[ $fieldMeta ] ?? array();
							foreach ($fieldValue as $key => $value) $fieldValue[$key] = sanitize_text_field( $value );
		
							//remove duplicates
							$fieldValue = array_unique($fieldValue);
		
						}else 
						{
							$fieldValue = array( sanitize_text_field( $_GET[ $fieldMeta ] ?? '' ) );
						}
		
						if (in_array('-', $fieldValue)) $fieldValue = array('');

						$icon = isset($parameters['icon']) ? stripslashes( $parameters['icon'] ) : ( $options['profileFieldIcon'] ?? false );

						switch ($parameters['type'])
						{
							case 'language':

								if ($options['languageFilters'] ?? false)
								{
								echo '<div class="field"><select multiple class="ui dropdown multiple search v-select fluid" id="' . esc_attr($fieldMeta) . esc_attr($id) . '" name="' . esc_attr($fieldMeta) . esc_attr($id) . '[]" onchange="aurl' . esc_attr($id) . '=\'' . esc_url( remove_query_arg($fieldMeta, $ajaxURLall) ) . '&\' + jQuery.param({\'' . esc_js($fieldMeta . '[]') . '\': jQuery(\'#' . esc_js($fieldMeta) . esc_attr($id) . '\').val() }); loadWebcams' . esc_attr($id) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __('Updating listings, please wait', 'ppv-live-webcams') . '...</div>\')">';

								echo '<option value="">' . esc_html($field) . '</div>';
								echo '<option value="-">' . esc_html(self::label( 'Any', __( 'Any', 'ppv-live-webcams' ), $options )) . '</option>';

													  foreach ( $languages as $key => $language ) {
														$selected = in_array($language , $fieldValue) ? ' selected' : '';

														  echo '<option value="' . esc_attr(htmlspecialchars( stripslashes( $language ) )) . '" ' . esc_attr($selected) . '>' . esc_html(htmlspecialchars( stripslashes( $language ) )) . '</option>'; 
													  }

								echo '</select></div>';			
								} else echo '<!-- ' . esc_attr($fieldMeta) . esc_attr($id) . ':  Language filters disabled -->';

								break;

								case 'location':

									if ($options['locationFilters'] ?? false)
									{

									$fieldValue = array();
									foreach ([ 'continent', 'country', 'region' ] as $locationField)
									{
										$fieldMetaL = 'vwf_' . $fieldName . '_'. $locationField;
										if (isset( $_GET[ $fieldMetaL ])) $fieldValue[$locationField] = $_GET[ $fieldMetaL ] ?? array();
										else $fieldValue[$locationField] = [];
									}

										// Continent Dropdown
										echo '<div class="field"><select multiple class="ui dropdown multiple search v-select fluid" id="' . esc_attr($fieldMeta) . esc_attr($id) . '_continent" name="' . esc_attr($fieldMeta) . esc_attr($id) . '_continent[]" onchange="aurl' . esc_attr($id) . '=\'' . esc_url( remove_query_arg($fieldMeta . '_continent', $ajaxURLall) ) . '&\' + jQuery.param({\'' . esc_js($fieldMeta . '_continent[]') . '\': jQuery(\'#' . esc_js($fieldMeta) . esc_attr($id) . '_continent\').val() }); loadWebcams' . esc_attr($id) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __('Updating listings, please wait', 'ppv-live-webcams') . '...</div>\')">';

										echo '<option value="">Continent ' . esc_html($field) . '</option>';
										echo '<option value="-">' . esc_html(self::label( 'Any', __( 'Any', 'ppv-live-webcams' ), $options )) . '</option>';

										foreach ( $continents as $key => $continent ) {
											$selected = in_array($continent , $fieldValue['continent']) ? ' selected' : '';
											echo '<option value="' . esc_attr(htmlspecialchars( stripslashes( $continent ) )) . '" ' . esc_attr( $selected ). '>' . esc_html(htmlspecialchars( stripslashes( $continent ) )) . '</option>'; 
										}

										echo '</select></div>';

										// Country Dropdown
										echo '<div class="field"><select multiple class="ui dropdown multiple search v-select fluid" id="' . esc_attr($fieldMeta) . esc_attr($id) . '_country" name="' . esc_attr($fieldMeta) . esc_attr($id) . '_country[]" onchange="aurl' . esc_attr($id) . '=\'' . esc_url( remove_query_arg($fieldMeta . '_country', $ajaxURLall) ) . '&\' + jQuery.param({\'' . esc_js($fieldMeta . '_country[]') . '\': jQuery(\'#' . esc_js($fieldMeta) . esc_attr($id) . '_country\').val() }); loadWebcams' . esc_attr($id) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __('Updating listings, please wait', 'ppv-live-webcams') . '...</div>\')">';

										echo '<option value="">Country ' . esc_html($field) . '</option>';
										echo '<option value="-">' . esc_html(self::label( 'Any', __( 'Any', 'ppv-live-webcams' ), $options )) . '</option>';

										foreach ( $countries as $key => $country ) {
											$selected = in_array($country , $fieldValue['country']) ? ' selected' : '';
											echo '<option value="' . esc_attr(htmlspecialchars( stripslashes( $country ) )) . '" ' . esc_attr( $selected ) . '>' . esc_html(htmlspecialchars( stripslashes( $country ) )) . '</option>'; 
										}

										echo '</select></div>';

										// Region Dropdown

										if ($options['locationFilters'] != 2)
										{
										echo '<div class="field"><select multiple class="ui dropdown multiple search v-select fluid" id="' . esc_attr($fieldMeta) . esc_attr($id) . '_region" name="' . esc_attr($fieldMeta) . esc_attr($id) . '_region[]" onchange="aurl' . esc_attr($id) . '=\'' . esc_url( remove_query_arg($fieldMeta . '_region', $ajaxURLall) ) . '&\' + jQuery.param({\'' . esc_js($fieldMeta . '_region[]') . '\': jQuery(\'#' . esc_js($fieldMeta) . esc_attr($id) . '_region\').val() }); loadWebcams' . esc_attr($id) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __('Updating listings, please wait', 'ppv-live-webcams') . '</div>\')">';

										echo '<option value="">Region ' . esc_html($field) . '</option>';
										echo '<option value="-">' . esc_html(self::label( 'Any', __( 'Any', 'ppv-live-webcams' ), $options )) . '</option>';

										foreach ( $regions as $country_code => $country_regions ) {
											foreach ( $country_regions as $region_code => $region ) {
												$selected = in_array($region , $fieldValue['region']) ? ' selected' : '';
												echo '<option value="' . esc_attr(htmlspecialchars( stripslashes( $region ) )) . '" ' . esc_attr($selected) . '>' . esc_html(htmlspecialchars( stripslashes( $region . ' (' . $country_code . ')') )) . '</option>'; 
											}
										}
										echo '</select></div>';
										}



									}
									else echo '<!-- ' . esc_attr($fieldMeta) . esc_attr($id) . ':  Location filters disabled -->';


								break;
						}
								
					}
				}
			
		}


		if ($selectFilters && ! $menu ) //filters with options
		{

			/*
		// custom profile filters
		if (is_array($options['profileFields']))
		foreach ( $options['profileFields'] as $field => $parameters ) {
			if ( isset($parameters['filter']) && isset($parameters['options']) ) {

				$fieldName  = sanitize_title( trim( $field ) );
				$fieldMeta  = 'vwf_' . $fieldName;

				if (is_array($_GET[ $fieldMeta ] ?? ''))
				{
					$fieldValue = $_GET[ $fieldMeta ] ?? array();
					foreach ($fieldValue as $key => $value) $fieldValue[$key] = sanitize_text_field( $value );
				}else $fieldValue = sanitize_text_field( $_GET[ $fieldMeta ] ?? '' );
				 
				if ( $fieldValue ) {
					$customFilters[ $fieldMeta ] = $fieldValue;
				}
			}
		}
					if ( count( $customFilters ) ) {
			$ajaxURLall = add_query_arg( $customFilters, $ajaxURLall );// update url query
		}
		*/



		if (is_array($options['profileFields']))
		foreach ( $options['profileFields'] as $field => $parameters ) {
			if ( isset($parameters['filter']) && isset($parameters['options']) ) {

				$fieldName = sanitize_title( trim( $field ) );
				$fieldMeta = 'vwf_' . $fieldName;

				if ( is_array($_GET[ $fieldMeta ] ?? '' ) )
				{
					//get values from field passed as array in GET
					$fieldValue = $_GET[ $fieldMeta ] ?? array();
					foreach ($fieldValue as $key => $value) $fieldValue[$key] = sanitize_text_field( $value );

					//remove duplicates
					$fieldValue = array_unique($fieldValue);

				}else 
				{
					$fieldValue = array( sanitize_text_field( $_GET[ $fieldMeta ] ?? '' ) );
				}

				if (in_array('-', $fieldValue)) $fieldValue = array('');
	
				$parameters['options'] = stripslashes( $parameters['options'] ?? '' );

				//if $parameters['options'] contains | get 	$fieldOptions by split by | otherwise by /
				$fieldOptions = strpos($parameters['options'], '|') ? explode('|', $parameters['options']) : explode('/', $parameters['options']);

				$icon = isset($parameters['icon']) ? stripslashes( $parameters['icon'] ) : ( $options['profileFieldIcon'] ?? false );

				//if (in_array($parameters['type'], array('checkboxes', 'multiselect'))) 
				if (is_array($fieldValue))
					$active = !empty($fieldValue) ? 'active' : '';
				else $active = ( $fieldValue ? 'active' : '');

//echo json_encode($fieldValue);

echo '<div class="field"><select multiple class="ui dropdown multiple search v-select fluid" id="' . esc_attr($fieldMeta) . esc_attr($id) . '" name="' . esc_attr($fieldMeta) . esc_attr($id) . '[]" onchange="aurl' . esc_attr($id) . '=\'' . esc_url( remove_query_arg($fieldMeta, $ajaxURLall) ) . '&\' + jQuery.param({\'' . esc_js($fieldMeta . '[]') . '\': jQuery(\'#' . esc_js($fieldMeta) . esc_attr($id) . '\').val() }); loadWebcams' . esc_attr($id) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __('Updating listings, please wait', 'ppv-live-webcams') . '...</div>\')">';

echo '<option value="">' . esc_attr($field). '</option>';

echo '<option value="-">' . esc_html(self::label( 'Any', __( 'Any', 'ppv-live-webcams' ), $options )) . '</option>';


				foreach ( $fieldOptions as $fieldOption ) {

					$selected = in_array($fieldOption, $fieldValue) ? ' selected' : '';
					echo '<option value="' . esc_attr($fieldOption) . '"' . esc_attr($selected) . '>' . esc_html( $fieldOption ) . '</option>';
				}

				echo '</select></div>';

			}
		}
	//end $selectFilters
	}

	//persistent filters
	if ($options['filtersSave'] ?? false)
	if (is_user_logged_in())
	{
		update_user_meta( get_current_user_id(), 'vwf_filters_url', $ajaxURLall  );
	}

		if ( $selectTags || $selectName ) {
			echo '<div class="field"></div>'; // separator

			if ( $selectTags ) {
				echo '<div class="field" data-tooltip="Tags, Comma Separated"><div class="ui left icon input fluid"><i class="tags icon"></i><INPUT class="videowhisperInput" type="text" size="12" name="tags" id="tags" placeholder="' . __( 'Tags', 'ppv-live-webcams' ) . '" value="' . esc_attr( htmlspecialchars( $tags ) ) . '">
					</div></div>';
			}

			if ( $selectName ) {
				echo '<div class="field"><div class="ui left corner labeled input fluid"><INPUT class="videowhisperInput" type="text" size="12" name="name" id="name" placeholder="' . __( 'Name', 'ppv-live-webcams' ) . '" value="' . esc_attr( htmlspecialchars( $name ) ) . '">
  <div class="ui left corner label">
    <i class="asterisk icon"></i>
  </div>
					</div></div>';
			}

			// search button
			echo '<div class="field"><button class="ui icon button" data-tooltip="Search" type="button" name="button" id="submit" value="' . __( 'Search', 'ppv-live-webcams' ) . '" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLstat) . '&tags=\' + document.getElementById(\'tags\').value +\'&name=\' + document.getElementById(\'name\').value; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Searching webcams...</div>\')"><i class="search icon"></i></button>
			 <button style="float:right" data-tooltip="Reload" class="ui icon button" type="button" name="reload" id="reload" value="' . __( 'Reload', 'ppv-live-webcams' ) . '" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLall ) . '\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Reloading Rooms List...</div>\')"><i class="sync icon"></i></button>
			 </div>';
		}

		// reload button
		// if ($selectCategory || $selectOrder || $selectTags || $selectName) echo '<div class="field" ></div>';

		echo '</div></div>';

//!json
}

		$year = time() - 31536000;

		// ! meta query
		$meta_query = array(
			'relation'    => 'AND',
			'vw_featured' => array(
				'key'     => 'vw_featured',
				'compare' => 'EXISTS',
			),
			'edate'       => array(
				'key'     => 'edate',
				'compare' => 'EXISTS',
			),
		);

		// has thumb
		if ($options['listingsThumbsOnly'])
		$meta_query['hasThumb'] = array(
			'relation' => 'OR',
			array(
				'key'   => 'hasSnapshot',
				'value' => '1',
			),
			/*
			array(
				'key'   => 'hasPicture',
				'value' => '1',
			),*/
		);

		if ($options['sightengine'] ?? false) $meta_query['suspended'] = array(
			'key'     => 'vwSuspended',
			'compare' => 'NOT EXISTS',
		);

		// hide private rooms
		$meta_query['room_private'] = array(
			'relation' => 'OR',
			array(
				'key'   => 'room_private',
				'value' => 'false',
			),
			array(
				'key'   => 'room_private',
				'value' => '',
			),
			array(
				'key'     => 'room_private',
				'compare' => 'NOT EXISTS',
			),
		);


		// location filter: may impact query performance even timeout on databases with many metas

		if ( !$options['listingsDisableLocation'] && !$isModerator)
		{
		$clientLocation = self::detectLocation( 'all' ); // array

		if ( $clientLocation ) {
			if ( is_array( $clientLocation ) ) {
				if ( ! empty( $clientLocation ) ) {
					$meta_query['location'] = array(
						'relation' => 'OR',
						array(
							'key'     => 'vw_banCountries',
							'value'   => $clientLocation,
							'compare' => 'NOT IN',
						),
						array(
							'key'     => 'vw_banCountries',
							'compare' => 'NOT EXISTS',
						),
					);

				}
			}
		}

		}

			// ! query args (starter pack)
			$args = array(
				'post_type'      => $options['custom_post'],
				'post_status'    => 'publish',
				'posts_per_page' => $perPage,
				'offset'         => $offset,
				'order'          => 'DESC',
				'meta_query'     => $meta_query,
				'cache_results'  => false, //own implementation with listingsCache
			);

			if ( ! $pstatus ) {
				$pstatus = '';
			}

			if ( $studio_id ) {
				$args['meta_query'][] = array(
					'key'   => 'studioID',
					'value' => $studio_id,
				);
			}

			if ( $author_id ) {
				$args['author'] = $author_id;
			}

			if ($ptoy != '') 	$args['meta_query']['lovenseToy']   = array(
				'key'   => 'lovenseToy',
				'value' => $ptoy,
			);

			if ($pstatus) switch ( $pstatus ) {
				case 'free':
					$args['meta_query']['public']   = array(
						'key'   => 'privateShow',
						'value' => '0',
					);
					$args['meta_query']['groupCPM'] = array(
						'key'   => 'groupCPM',
						'value' => '0',
					);
					break;
				case 'paid':
					$args['meta_query']['public']   = array(
						'key'   => 'privateShow',
						'value' => '0',
					);
					$args['meta_query']['groupCPM'] = array(
						'key'     => 'groupCPM',
						'value'   => '0',
						'compare' => '>',
					);
					break;
				case 'available_free':
					$args['meta_query']['public']   = array(
						'key'   => 'privateShow',
						'value' => '0',
					);
					$args['meta_query']['online']   = array(
						'key'     => 'edate',
						'value'   => time() - 40,
						'compare' => '>',
					);
					$args['meta_query']['groupCPM'] = array(
						'key'   => 'groupCPM',
						'value' => '0',
					);
					break;
				case 'available_paid':
					$args['meta_query']['public']   = array(
						'key'   => 'privateShow',
						'value' => '0',
					);
					$args['meta_query']['online']   = array(
						'key'     => 'edate',
						'value'   => time() - 40,
						'compare' => '>',
					);
					$args['meta_query']['groupCPM'] = array(
						'key'     => 'groupCPM',
						'value'   => '0',
						'compare' => '>',
					);
					break;

				case 'private':
					$args['meta_query']['private'] = array(
						'key'   => 'privateShow',
						'value' => '1',
					);
					$args['meta_query']['online']  = array(
						'key'     => 'edate',
						'value'   => time() - 40,
						'compare' => '>',
					);
					break;

				case 'online':
					$args['meta_query']['online'] = array(
						'key'     => 'edate',
						'value'   => time() - 40,
						'compare' => '>',
					);
					break;

				case 'public':
					$args['meta_query']['public'] = array(
						'key'   => 'privateShow',
						'value' => '0',
					);
					$args['meta_query']['online'] = array(
						'key'     => 'edate',
						'value'   => time() - 40,
						'compare' => '>',
					);
					break;

				case 'offline':
					$args['meta_query']['offline'] = array(
						'key'     => 'edate',
						'value'   => time() - 40,
						'compare' => '<',
					);
					break;
			}

			if ($order_by) switch ( $order_by ) {
				case 'default':
					$args['orderby'] = array(
						'vw_featured' => 'DESC',
						'edate'       => 'DESC',
					);
					break;

				case 'post_date':
					$args['orderby'] = 'post_date';
					break;

				case 'rand':
					$args['orderby'] = 'rand';
					break;

				default:
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = $order_by;
					break;
			}

			if ( $category ) {
				$args['category'] = $category;
			}

			if ( $tags ) {
				$tagList = explode( ',', $tags );
				if ( is_array($tagList) ) foreach ( $tagList as $key => $value )
				{
					$tagList[ $key ] = trim( $tagList[ $key ] );
				}

				$args['tax_query'] = array(
					array(
						'taxonomy' => 'post_tag',
						'field'    => 'slug',
						'operator' => 'AND',
						'terms'    => $tagList,
					),
				);
			}

			if ( $name ) {
				$args['s'] = $name;
			}

			// custom profile filters - get postz
			if (is_array($options['profileFields']))
			foreach ( $options['profileFields'] as $field => $parameters ) {
				if ( isset($parameters['filter']) ) {

					$fieldName  = sanitize_title( trim( $field ) );
					$fieldMeta  = 'vwf_' . $fieldName;

					//skip if not set or location 
					if (!isset($_GET[ $fieldMeta ]) && $parameters['type'] != 'location') continue;

					if ( $parameters['type'] == 'location' )
					{

						$locations = 0;
						$argsL = array();
						foreach (['continent', 'country', 'region'] as $location) 
						{
							$fieldMetaL = 'vwf_' . $fieldName . '_' . $location;
							if (isset($_GET[ $fieldMetaL ])) $fieldValue = $_GET[ $fieldMetaL ] ?? '';
							else $fieldValue = '';

							if ($fieldValue) 
							{
								$argsL[$location] = array(
									'key'   => $fieldMetaL,
									'value' => $fieldValue,
								);
							}
							$locations++;
						}

						if ($locations)
						{
							$args['meta_query'][ $fieldMeta ] = $argsL;
							$args['meta_query'][ $fieldMeta ][ 'relation' ] = 'OR';
						}
						continue;
					}

					//regular fields
					if ( is_array( $_GET[ $fieldMeta ] ?? '') )
					{
						$fieldValue = $_GET[ $fieldMeta ] ?? array();
						foreach ($fieldValue as $key => $value) $fieldValue[$key] = sanitize_text_field( $value );
					}else 
					{
						$fieldValue = sanitize_text_field( $_GET[ $fieldMeta ] ?? '' );
					}
			
					if ( $fieldValue ) {
						if ( is_array($fieldValue) )
						{
							if (in_array('-', $fieldValue)) $fieldValue = array();

							//add a wp meta query to match any of the values considering both $fieldValue and meta are arrays
							if (!empty($fieldValue)) 
							{
								$args['meta_query'][ $fieldMeta ] = ['relation' => 'OR' ];
								$fieldValue = array_unique($fieldValue);
								
								if (in_array($parameters['type'], array('checkboxes', 'multiselect', 'language')))
								foreach ($fieldValue as $key => $value) $args['meta_query'][ $fieldMeta ][] =
								[
									'key'     => $fieldMeta,
									// field is stored as a serialized array (e.g., a:4:{i:0;s:7:"English";i:1;s:7:"Spanish";i:2;s:7:"Italian";i:3;s:6:"French";}) in the WP database
									'value'   => '"' . $value . '"',
									'compare' => 'LIKE',
								];
								else
								foreach ($fieldValue as $key => $value) $args['meta_query'][ $fieldMeta ][] =
								[
									'key'   => $fieldMeta,
									'value' => $value,
								];

							}

						} else {
							$args['meta_query'][ $fieldMeta ] = array(
								'key'   => $fieldMeta,
								'value' => $fieldValue,
							);
						}
					}
				}
			}


			//db query with cache
			if ($options['listingsCache'] ?? false)
			{
				//https://developer.wordpress.org/reference/functions/wp_cache_set/
				$listingsCache = intval( $options['listingsCache'] );
				if ($listingsCache < 5) $listingsCache = 5;

				//force check in case cache is not properly purged
				$listingsUpdate = false;
				if ( self::timeTo( 'listingsCache', $listingsCache, $options ) ) $listingsUpdate = true;

				$cacheKey = json_encode($args);
				if ($listingsUpdate || !$postslist = wp_cache_get($cacheKey, 'VideoWhisper') ) //generate new
				{
				$postslist = get_posts( $args );
				wp_cache_set($cacheKey, $postslist, 'VideoWhisper', $listingsCache); //short to list live rooms
				echo '<!--VideoWhisper Room List: Saved Cache '. esc_html($listingsCache) .'s -->';
				} else echo '<!--VideoWhisper Room List: Cache Result '. esc_html($listingsCache) .'s -->';

			} else
			{
				echo '<!--VideoWhisper Room List: Cache Disabled-->';
				$postslist = get_posts( $args );
			}

			if ($options['debugMode']) echo '<!--VideoWhisper Room List Query: '. json_encode($args) .' -->';

			if (!$json) echo '<!--VideoWhisper Room List Start--><div id="videowhisperListings'. esc_attr($id) .'" class="videowhisper' . ucwords($layout) . '">';

			// ! list cams
			$listingsCount = 0;
			if (is_array($postslist)) $listingsCount = count( $postslist );

			if ( $listingsCount > 0 ) {

			if (!$json)
			{
				if ( $layout == 'horizontal' ) {
					$listingTemplate = $listingTemplate2 = $listingTemplate1 = html_entity_decode( stripslashes( $options['listingTemplateHorizontal'] ) );
				}elseif ( $layout == 'list' ) {
					$listingTemplate = $listingTemplate2 = $listingTemplate1 = html_entity_decode( stripslashes( $options['listingTemplateList'] ) );
				} else {
					$listingTemplate1 = html_entity_decode( stripslashes( $options['listingTemplate'] ) );
					$listingTemplate2 = html_entity_decode( stripslashes( $options['listingTemplate2'] ) );
					$listingTemplate  = $listingTemplate2;
				}

				$templateSearch = array( '#name#', '#age#', '#clientCPM#', '#roomBrief#', '#roomTags#', '#url#', '#snapshot#', '#thumbWidth#', '#thumbHeight#', '#banLink#', '#groupMode#', '#groupCPM#', '#performers#', '#currency#', '#preview#', '#enter#', '#paidSessionsPrivate#', '#paidSessionsGroup#', '#rating#', '#performerStatus#', '#roomCategory#', '#featuredReview#', '#roomDescription#', '#featured#', '#buttonChat#', '#buttonCall#', '#buttonMessage#', '#vote#', '#icons#' );
				// $templateReplace  = array($name, $age, $clientCPM, $roomBrief, $roomTags, $url, $snapshot, $thumbWidth, $thumbHeight, $banLink);
			}

			$showBig = 0;

				$k = 0;
				foreach ( $postslist as $item ) {
					//per webcam post item

					$ritem = [];

					if (!$json)
					{
					 if ( $layout == 'grid' ) {
						if ( $perRow ) {
							if ( $k ) {
								if ( $k % $perRow == 0 ) {
									echo '<br>';
								}
							}
						}
					}

							$k++;

						$listingTemplate = $listingTemplate1;

					$showBig = 0;

					if ( $options['listingBig'] ) {
						if ( $options['listingBig'] == 1 && $k == 1 ) {
							$listingTemplate = $showBig = 1;
						}
						if ( $options['listingBig'] > 1 ) {
							if ( $k % $options['listingBig'] == 1 ) {
								$showBig = 1;
							}
						}

						if ( $showBig ) {
							$listingTemplate = $listingTemplate2;
						}
					}

				 }
					// else $listingTemplate = $listingTemplate1;

					$k++;

					$name = sanitize_file_name( $item->post_title );

					$banLink = '';

					if ( $ban && !$json) {
						$banLink = '<a class = "button" href="admin.php?page=live-webcams&ban=' . urlencode( $name ) . '">' . __( 'Ban This Webcam', 'ppv-live-webcams' ) . '</a><br>';
					}

					$edate = intval( get_post_meta( $item->ID, 'edate', true ) );
					$age   = self::format_age( time() - $edate );

					$privateShow = get_post_meta( $item->ID, 'privateShow', true );

					// performer status
					if ( time() - $edate > 40 ) {
						$performerStatus = 'offline';
					} elseif ( $privateShow ) {
						$performerStatus = 'private';
					} else {
						$performerStatus = 'public';
					}



					$clientCPM = self::clientCPM( $name, $options, $item->ID );

					// ip camera or playlist : update snapshot when listing
					if ( get_post_meta( $item->ID, 'vw_ipCamera', true ) || ( get_post_meta( $item->ID, 'vw_playlistActive', true ) && $options['playlists'] ) ) {
						self::streamSnapshot( $name, true, $item->ID );
						// echo 'Updating IP Cam Snapshot: ' . $stream;
					}

					switch ( $options['webcamLink'] ) {
						case '':
						case 'room':
							$url = self::roomURL( $name );
							break;

						case 'custom':
							$url = get_post_meta( $item->ID, 'customRoomLink', true );
							if ( ! $url ) {
								$url = $options['webcamLinkDefault'] . $name;
								update_post_meta( $item->ID, 'customRoomLink', $url );
							}
							break;

						case 'auto':
							$url = get_post_meta( $item->ID, 'customRoomLink', true );
							if ( ! $url ) {
								$url = self::roomURL( $name );
							}
							break;

					}



					$thumbWidth  = $options['thumbWidth'];
					$thumbHeight = $options['thumbHeight'];

					$snapshot = self::webcamThumbSrc( $item->ID, $name, $options, $age, true );

					$previewCode = self::webcamThumbCode( $item->ID, $name, $options, $snapshot, $optionsVSV, $isMobile, $showBig, $previewMuted, $age );

					$roomBrief = get_post_meta( $item->ID, 'vw_roomBrief', true );

					$tags     = wp_get_post_tags( $item->ID, array( 'fields' => 'names' ) );
					$roomTags = '';
					if ( ! empty( $tags ) ) {
						if ( ! is_wp_error( $tags ) ) {
							foreach ( $tags as $tag ) {
								$roomTags .= ( $roomTags ? ', ' : '' ) . $tag;
							}
						}
					}

						$roomLabel = get_post_meta( $item->ID, 'vw_roomLabel', true );
					if ( ! $roomLabel ) {
						$roomLabel = $name;
					}

					$roomCategory = '';
					$cats         = wp_get_post_categories( $item->ID, array( 'fields' => 'names' ) );
					if ( ! empty( $cats ) ) {
						foreach ( $cats as $category ) {
							$roomCategory .= ( $roomCategory ? ', ' : '' ) . $category;
						}
					}

					$roomDescription = $item->post_content;

					$groupMode = get_post_meta( $item->ID, 'groupMode', true );
					if ( ! $groupMode ) {
						$groupMode = 'Free';
					}

					$groupCPM = get_post_meta( $item->ID, 'groupCPM', true );
					if ( ! $groupCPM ) {
						$groupCPM = 0;
					}

					// extras
					$groupParameters = get_post_meta( $item->ID, 'groupParameters', true );

					// sales counters
					$paidSessionsPrivate = get_post_meta( $item->ID, 'paidSessionsPrivate', true );
					$paidSessionsGroup   = get_post_meta( $item->ID, 'paidSessionsGroup', true );


					$vw_featured  = get_post_meta( $item->ID, 'vw_featured', true );

			if ($json)
					{
						$ritem['name'] = $name;
						$ritem['performerStatus'] = $performerStatus;
						$ritem['lastOnline'] = $age;
						$ritem['url'] = $url;
						$ritem['snapshot'] = $snapshot;
						$ritem['label'] = $roomLabel;
						$ritem['category'] = $roomCategory;
						$ritem['tags'] = $roomTags;
						$ritem['brief'] = $roomBrief;
						$ritem['description'] = $roomDescription;
						$ritem['clientCPM'] = $clientCPM;
						$ritem['groupMode'] = $groupMode;
						$ritem['groupCPM'] = $groupCPM;
						$ritem['featured'] = $vw_featured;
						$ritem['maxViewers'] = intval( get_post_meta( $item->ID, 'maxViewers', true ) );
						$ritem['viewers'] = intval( get_post_meta( $item->ID, 'viewers', true ) );
					}

					//icons: lovense toy
					$iconsCode = '';
					if ($options['lovense'] && $options['lovenseToy'])
					{
						$lovenseToy = get_post_meta( $item->ID, 'lovenseToy', true );
						if ($lovenseToy) $iconsCode .= '<i class="ui icon game"></i>';
					}


						$ratingCode = '';
						$voteCode = '';
					if ( $options['rateStarReview'] ) {

					//votes
					if (!$json) $voteCode = do_shortcode( '[videowhisper_vote post_id="' . $item->ID . '"]' );

					//rating
						$rating = floatval( get_post_meta( $item->ID, 'rateStarReview_rating', true ) );
						if ($json) $ritem['rating'] = number_format($rating, 2);
						$max    = 5;
						if ( $rating > 0  && !$json) {
							$ratingCode = '<div class="ui yellow star rating readonly" data-rating="' . round( $rating * $max ) . '" data-max-rating="' . $max . '"></div>'; // . number_format($rating * $max,1)  . ' / ' . $max
						}

					$featuredReview = '<div class="ui stackable cards">' . do_shortcode( '[videowhisper_review_featured post_id="' . $item->ID . '"]' ) . '</div>';
					}

					// featured webcam
					$featuredCode = '';
					if ( $vw_featured ) {
						$featuredCode = '<div class="videowhisperWrap"><span class="videowhisperFeatured">' . __( 'Featured', 'ppv-live-webcams' ) . ( $vw_featured > 1 ? ' x' . $vw_featured : '' ) . '</span></div>';
						}

					// #performers#
					$checkin = get_post_meta( $item->ID, 'checkin', true );
					if ( $checkin ) {
						if ( ! is_array( $checkin ) ) {
							$checkin = array( $checkin );
						}
					}

					$performersCode = '';

					if ( $checkin ) {
						foreach ( $checkin as $performerID ) {
							$performersCode .= ( $performersCode ? ',' : '' ) . self::performerLink( $performerID, $options );
						}
					}

					if ($json)
					{
						$ritem['performers'] = strip_tags($performersCode);
					}

					if ( strip_tags($performersCode) == $roomLabel ) $performersCode ='';

					$urlCall = add_query_arg( 'vwsm', 'private', $url );

					$requests_disable = self::is_true( get_post_meta( $item->ID, 'requests_disable', true ) );

					$enterLabel = __( 'Enter', 'ppv-live-webcams' );

					$group_disabled = self::is_true( get_post_meta( $item->ID, 'group_disabled', true ) );
					if ( $group_disabled ) {
						$url        = $urlCall;
						$enterLabel = __( 'Call', 'ppv-live-webcams' );

						if ( ! $isLoggedin ) {
							$url        = ( $options['p_videowhisper_login'] > 0 ? get_permalink( $options['p_videowhisper_login'] ) : wp_login_url() );
							$enterLabel = __( 'Login', 'ppv-live-webcams' );
						}
					}

					// #enter#
					$enterCode = '<div class="videowhisperEnterDropdown"><a href="' . $url . '"><button class="videowhisperEnterButton">' . $enterLabel . '</button></a><div class="videowhisperEnterDropdown-content">';

					$buttonChat    = '<a class="videowhisperButtonChat" href="' . $url . '">' . $enterLabel . '</a>';
					$buttonMessage = '';
					$buttonCall    = '';

					if ($json)
					{
						$ritem['enterUrl'] = $url;
						$ritem['enterLabel'] = $enterLabel;
					}

					if (!$json)
					{
					//enter menu
					if ( ! $isLoggedin ) {

						if ( ! $groupCPM && ! $group_disabled ) {
							$enterCode .= '<a href="' . $url . '">' . $groupMode . '</a>';
						}
						$enterCode .= '<a href="' . ( $options['p_videowhisper_login'] > 0 ? get_permalink( $options['p_videowhisper_login'] ) : wp_login_url() ) . '">' . __( 'Login for More', 'ppv-live-webcams' ) . ' ...' . '</a>';
					} else {
						if ( ! $group_disabled ) {
							$enterCode .= '<a href="' . $url . '">' . $groupMode . ( $groupCPM ? ' ' . $groupCPM . $options['currencypm'] : '' ) . '</a>';
						}

						// special modes
						$room_audio = self::is_true( get_post_meta( $item->ID, 'room_audio', true ) );
						$room_text = self::is_true( get_post_meta( $item->ID, 'room_text', true ) );

						if ( ! $requests_disable ) if (!$room_audio) if (!$room_text) if ( $options['modeVideo'] != '0' )
						{
							$enterCode .= '<a href="' . $urlCall . '">' . __( 'Video Call', 'ppv-live-webcams' ) . ' ' . ( $clientCPM ? $clientCPM . $options['currencypm'] : '' ) . '</a>';
							$buttonCall = '<a class="videowhisperButtonCall" href="' . $urlCall . '">' . __( 'Video Call', 'ppv-live-webcams' ) . ' ' . ( $clientCPM ? $clientCPM . $options['currencypm'] : '' ) . '</a>';
						}

						if ( $options['modeAudio'] ) if (!$room_text) {
							$clientCPM = self::clientCPM( $name, $options, $item->ID, 'audio' );
							if ( ! $requests_disable ) {
								$enterCode .= '<a href="' . $urlCall . '&calltype=audio">' . __( 'Audio Call', 'ppv-live-webcams' ) . ' ' . ( $clientCPM ? $clientCPM . $options['currencypm'] : '' ) . '</a>';

								$buttonCall = '<a class="videowhisperButtonCall" href="' . $urlCall . '">' . __( 'Audio Call', 'ppv-live-webcams' ) . ' ' . ( $clientCPM ? $clientCPM . $options['currencypm'] : '' ) . '</a>';

							}
						}

						if ( $options['modeText'] ) {
							$clientCPM = self::clientCPM( $name, $options, $item->ID, 'text' );
							if ( ! $requests_disable ) {
								$enterCode .= '<a href="' . $urlCall . '&calltype=text">' . __( 'Text Chat', 'ppv-live-webcams' ) . ' ' . ( $clientCPM ? $clientCPM . $options['currencypm'] : '' ) . '</a>';

								$buttonCall = '<a class="videowhisperButtonCall" href="' . $urlCall . '">' . __( 'Text Chat', 'ppv-live-webcams' ) . ' ' . ( $clientCPM ? $clientCPM . $options['currencypm'] : '' ) . '</a>';
							}
						}

						if ( $options['messages'] ) {

							$messagesCost = floatval( get_post_meta( $item->ID, 'question_price', true ) );
							if ( ! $messagesCost ) {
								$messagesCost = floatval( $options['messagesCost'] );
							}
							$closed = intval( get_post_meta( $item->ID, 'question_closed', true ) );

							if (!$closed) $enterCode   .= '<a href="' . add_query_arg( 'view', 'messages', $url ) . '">' . __( 'Question', 'ppv-live-webcams' ) . ' ' . ( $messagesCost ? $messagesCost . $options['currency'] : '' ) . '</a>';

							$buttonMessage = '<a class="videowhisperButtonMessage" href="' . add_query_arg( 'view', 'messages', $url ) . '">' . __( 'Question', 'ppv-live-webcams' ) . ' ' . ( $messagesCost ? $messagesCost . $options['currency'] : '' ) . '</a>';

						}

						if ( is_array( $groupParameters ) ) {
							if ( isset($groupParameters['2way']) && $groupParameters['2way'] > 0 ) {
								$c2way = 0;

								$mode2way = get_post_meta( $item->ID, 'mode2way', true );
								if ( is_array( $mode2way ) ) {
									$c2way = count( $mode2way );
								}

								$roomInterface = get_post_meta( $item->ID, 'roomInterface', true );

								// only in Flash interface, h5V has special conference mode
								if ( $roomInterface == 'flash' ) {
									$enterCode .= '<a href="' . add_query_arg( 'vwsm', '2way', $url ) . '">' . __( '2 Way', 'ppv-live-webcams' ) . ( $groupParameters['cpm2'] ? ' ' . $groupParameters['cpm2'] . $options['currencypm'] : '' ) . ' (' . $c2way . '/' . $groupParameters['2way'] . ')</a>';
								}
							}
						}

						if ( $options['voyeurAvailable'] == 'always' || ( $options['voyeurAvailable'] == 'private' && $privateShow ) || ( $options['voyeurAvailable'] == 'public' && ! $privateShow ) ) {
							if ( is_array( $groupParameters ) ) {
								if ( array_key_exists( 'voyeur', $groupParameters ) ) {
									if ( $groupParameters['voyeur'] ) {
											$enterCode .= '<a href="' . add_query_arg( 'vwsm', 'voyeur', $url ) . '">' . __( 'Voyeur', 'ppv-live-webcams' ) . ( $groupParameters['cpmv'] ? ' ' . $groupParameters['cpmv'] . $options['currencypm'] : '' ) . '</a>';

									}
								}
							}
						}
					}
				//
				}



					if ($json) $ritem['profileUrl'] = add_query_arg( 'view', 'content', $url );

					if (!$json)
					{
					$enterCode .= '<a href="' . add_query_arg( 'view', 'content', $url ) . '">' . __( 'Profile', 'ppv-live-webcams' ) . '</a>';
					$enterCode .= '</div></div>';

					// replace
					$templateReplace = array( $roomLabel, $age, $clientCPM, $roomBrief, $roomTags, $url, $snapshot, $thumbWidth, $thumbHeight, $banLink, $groupMode, $groupCPM, $performersCode, $options['currency'], $previewCode, $enterCode, $paidSessionsPrivate, $paidSessionsGroup, $ratingCode, $performerStatus, $roomCategory, $featuredReview ?? '', $roomDescription, $featuredCode, $buttonChat, $buttonCall, $buttonMessage, $voteCode, $iconsCode );

					echo str_replace( $templateSearch, $templateReplace, $listingTemplate )  . esc_html( $debugCode );
					}

					if ($json)
					{
						//special profile fields
						if ( $json_fields && count($json_fields) ) foreach ($json_fields as $field)
						{
							$value = get_post_meta( $item->ID, $field, true );
							if ($value) $ritem[$field] = $value;
						}

						$ritem['ban'] = get_post_meta( $item->ID, 'vw_banCountries', true );

						$result['rooms'] []= $ritem;
					}

					//per item
				}
				//not empty
			} else {

				if ($json)
				{
					$result['warning'] = __( 'No rooms match current criteria. A room requires a picture or snapshot from live broadcast to get listed.', 'ppv-live-webcams' );
				}
				else
				{
				echo __( 'No rooms match current criteria. A room requires a picture or snapshot from live broadcast to get listed.', 'ppv-live-webcams' );
				if ( $debugMode ) {
					echo 'Debug (no webcams found):<br><textarea readonly cols="120" rows="4">';
					var_dump( $args );
					echo '</textarea>';
				}

				}
			}

			if (!$json) echo '</div><!--VideoWhisper Room List End-->'; // end room list

		//horizontal slider buttons in container
			if ($listingsCount == $perPage || $page) //only if listings
			if (!$json && $layout == 'horizontal')
			{

				if ($listingsCount == $perPage || $page) echo '<a class="horizontalPrevious ui icon button tiny compact" href="javascript:void(0);" onClick="' . ($page > 0  ? 'if (document.getElementById(\'videowhisperListings' . esc_attr( $id ) . '\').scrollLeft == 0) { aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLall) . '&p=' . intval( $page - 1 ) . '\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading previous page...</div>\') };' : '' )  . '; document.getElementById(\'videowhisperListings' . esc_attr( $id ) . '\').scrollLeft -= ' . intval( $options['thumbWidth'] + 4 ) . '"><i class="left arrow icon"></i></a> ';

				if ($listingsCount == $perPage) echo '<a class="horizontalNext ui icon button tiny compact" href="javascript:void(0);" onClick="var vwElement = document.getElementById(\'videowhisperListings' . esc_attr( $id ) . '\'); ' . ( count( $postslist ) == $perPage ? 'var maxScrollLeft = vwElement.scrollWidth - vwElement.clientWidth; if (vwElement.scrollLeft >=  maxScrollLeft) { aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLall) . '&p=' . intval( $page + 1 ) . '\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading next page...</div>\') }' : '' )  . '; vwElement.scrollLeft += ' . intval( $options['thumbWidth'] + 4 ) . ';"><i class="right arrow icon"></i></a> ';

			}

			if ($json)
			{
				echo json_encode($result);
				die();
			}

			// footer start
			if (!$json)
			{

			echo '<div class="ui clearing divider hidden tiny"></div>';
			
			echo '<div class="ui' . esc_attr( $options['interfaceClass'] ) . ' equal width grid">';

			// pagination
			if ( $selectPage ) {
				//mage sure pagination is displayed under current content
				echo '<div class="column"><div class="ui form"><div class="inline fields">';

				if ( $page > 0 && $layout != 'horizontal' ) {
					echo ' <a class="ui labeled icon button" href="javascript:void(0);" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLall) . '&p=' . intval( $page - 1 ) . '\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading previous page...</div>\');"><i class="left arrow icon"></i> ' . __( 'Previous', 'ppv-live-webcams' ) . '</a> ';
				}

				echo '<a class="ui secondary button" href="#"> ' . __( 'Page', 'ppv-live-webcams' ) . ' ' . intval( $page + 1 ) . ' </a>';

				if (is_array( $postslist )) if ( count( $postslist ) == $perPage && $layout != 'horizontal' ) {
					echo ' <a class="ui right labeled icon button" href="javascript:void(0);" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxURLall) . '&p=' . intval( $page + 1 ) . '\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading next page...</div>\');">' . __( 'Next', 'ppv-live-webcams' ) . ' <i class="right arrow icon"></i></a> ';
				}

				echo '</div></div></div>';
			}

			if ( $selectLayout ) {
				echo '
<div class="right floated column">
<div class="ui icon buttons right floated">
  <a class="ui button ' . ( $layout == 'grid' ? 'active' : '' ) . '" data-tooltip="' . __( 'Grid', 'ppv-live-webcams' ) . '"  href="JavaScript: void()" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_attr( $ajaxURLlay ) . '&layout=grid\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading grid layout...</div>\');">
    <i class="th icon"></i>
  </a>
  <a class="ui button ' . ( $layout == 'list' ? 'active' : '' ) . '" data-tooltip="' . __( 'Full Row List', 'ppv-live-webcams' ) . '" href="JavaScript: void()" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_attr( $ajaxURLlay ) . '&layout=list\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading list layout...</div>\');">
    <i class="th list icon"></i>
  </a>
 <a class="ui button ' . ( $layout == 'horizontal' ? 'active' : '' ) . '" data-tooltip="' . __( 'Horizontal Slider', 'ppv-live-webcams' ) . '" href="JavaScript: void()" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_attr( $ajaxURLlay ) . '&layout=horizontal\'; loadWebcams' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading horizontal slider layout...</div>\');">
    <i class="alternate arrows horizontal icon"></i>
  </a>
</div>
</div>';
			}

			echo '</div>';
			}
			// footer end

			if ( ! $isMobile && $options['videosharevod'] && !$json ) {

				echo '
<PRE style="display: none"><SCRIPT language="JavaScript">
jQuery(document).ready(function()
{

var hHandlers = jQuery(".videowhisperWebcam")
.on("mouseenter", hoverVideoWhisper)
.on("click", hoverVideoWhisper)
.on("mouseleave", outVideoWhisper);

var hHandlers2 = jQuery(".videowhisperWebcam2").hover( hoverVideoWhisper, outVideoWhisper );

function hoverVideoWhisper(e) {
	var vid = jQuery("video", this).get(0);
	if (vid && vid.paused) {
		vid.play().catch(function(error) {
			if (error.name === "NotAllowedError") {
				// Handle the NotAllowedError, for example, by showing a message to the user
			}
			console.warn("Autoplay video on hover error:", error);
		});
	}
}

function outVideoWhisper(e) {
     var vid = jQuery(\'video\', this).get(0);
     if (vid) vid.pause();
}
});
</SCRIPT></PRE>
';

			}

		//close layout with menu
		if ( $menu && !$json) echo '</div></div>';


					if ( $debugMode ) {
						global $wpdb;
						echo '<!-- Debug: Query: '. wp_kses_post( $cacheKey ) . '-->';
					}

			// echo '<!-- Generated '. date(DATE_RFC2822) . ' ' . $cacheQuery . ' ' . $cacheKey . ' -->';

			// self::stringSave($cachePath, $htmlCode);

			self::scriptThemeMode($options);

			die();
	}

	// end room list

	static function scriptThemeMode($options)
{
	$theme_mode = '';

	//check if using the FansPaysite theme and apply the dynamic theme mode
	if (function_exists('fanspaysite_get_current_theme_mode')) $theme_mode = fanspaysite_get_current_theme_mode();

	if (!$theme_mode) $theme_mode = $options['themeMode'] ?? '';

	if (!$theme_mode) return '<!-- No theme mode -->';

	// JavaScript function to apply the theme mode
	echo '<script>
	if (typeof setConfiguredTheme !== "function")  // Check if the function is already defined
	{ 

		function setConfiguredTheme(theme) {
			if (theme === "auto") {
				if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
					document.body.dataset.theme = "dark";
				} else {
					document.body.dataset.theme = "";
				}
			} else {
				document.body.dataset.theme = theme;
			}

			if (document.body.dataset.theme == "dark")
			{
			jQuery("body").find(".ui").addClass("inverted");
			jQuery("body").addClass("inverted");
			}else
			{
				jQuery("body").find(".ui").removeClass("inverted");
				jQuery("body").removeClass("inverted");
			}

			console.log("PaidVideochat/setConfiguredTheme:", theme);
		}
	}	

	setConfiguredTheme("' . esc_js($theme_mode) . '");

	</script>';
}


	static function videowhisper_cam_webrtc_playback( $atts ) {
		$stream  = '';
		$postID  = 0;
		$options = get_option( 'VWliveWebcamsOptions' );

		if ( is_single() ) {
			$postID = get_the_ID();
			if ( get_post_type( $postID ) == $options['custom_post'] ) {
				$room = self::post_title( $postID );
			} else {
				$postID = 0;
			}
		}

		if ( ! $room ) {
			$room = sanitize_text_field( $_GET['room'] );
		}

		$atts = shortcode_atts(
			array(
				'room'      => $room,
				'width'     => '480px',
				'height'    => '360px',
				'webcam_id' => $postID,
				'silent'    => 0,
			),
			$atts,
			'videowhisper_cam_webrtc_playback'
		);
		$atts = array_map('esc_attr', $atts);

		if ( $atts['room'] ) {
			$room = sanitize_text_field( $atts['room'] ); // parameter channel="name"
		}
		if ( $atts['webcam_id'] ) {
			$postID = sanitize_text_field( $atts['webcam_id'] );
		}
		$width = sanitize_text_field( $atts['width'] );
		if ( ! $width ) {
			$width = '100%';
		}
		$height = sanitize_text_field( $atts['height'] );
		if ( ! $height ) {
			$height = '360px';
		}

		$room = sanitize_file_name( $room );

		if ( ! $postID && $room ) {
			global $wpdb;
			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type=%s LIMIT 0,1", sanitize_file_name( $room ), $options['custom_post'] ) );
		}

		if ( ! $room ) {
			return 'WebRTC Playback Error: Missing webcam room name!';
		}

		$stream = self::webcamStreamName( $room, $postID );

		$userID = 0;
		if ( is_user_logged_in() ) {
			$userName = $options['userName'];
			if ( ! $userName ) {
				$userName = 'user_login';
			}
			$current_user = wp_get_current_user();
			if ( $current_user->$userName ) {
				$username = sanitize_file_name( $current_user->$userName );
			}
			$userID = $current_user->ID;
		}

		// detect browser
		$agent   = $_SERVER['HTTP_USER_AGENT'];
		$Android = stripos( $agent, 'Android' );
		$iOS     = ( strstr( $agent, 'iPhone' ) || strstr( $agent, 'iPod' ) || strstr( $agent, 'iPad' ) );
		$Safari  = ( strstr( $agent, 'Safari' ) && ! strstr( $agent, 'Chrome' ) );
		$Firefox = stripos( $agent, 'Firefox' );

		$codeMuted = '';
		if ( ! $Firefox ) {
			$codeMuted = 'muted';
		}

		// WebRTC playback: detect source type and transcode if necessary
		$streamProtocol = get_post_meta( $postID, 'stream-protocol', true ); // rtmp/webrtc
		$streamMode     = get_post_meta( $postID, 'stream-mode', true ); // direct/safari_pc

		if ( $streamProtocol == 'rtsp' && $streamMode == 'direct' ) {
			$stream_webrtc = $stream;
		} else {
			$stream_webrtc = self::transcodeStreamWebRTC( $stream, $postID, $options );
		}

		if ( ! $stream_webrtc ) {
			$htmlCode .= 'Error: WebRTC stream name not available!';
		}

		$streamQuery = self::webrtcStreamQuery( $userID, $postID, 0, $stream_webrtc, $options, 0, $room );

		self::enqueueUI();

		wp_enqueue_script( 'webrtc-adapter', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/adapter.js', array( 'jquery' ) );
		wp_enqueue_script( 'videowhisper-webrtc-playback', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/vwrtc-playback.js', array( 'jquery', 'webrtc-adapter' ) );

		$wsURLWebRTC       = $options['wsURLWebRTC'];
		$applicationWebRTC = $options['applicationWebRTC'];

		$htmlCode .= <<<HTMLCODE
		<div class="videowhisper-webrtc-video">
		<video id="remoteVideo" class="videowhisper_htmlvideo" autoplay playsinline controls $codeMuted style="width:$width; height:$height"></video>
		<!--$streamProtocol|$stream|$stream_webrtc-->
		</div>

		<div>
			<span id="sdpDataTag"></span>
		</div>

		<script type="text/javascript">

			var videoBitrate = 360;
			var audioBitrate = 64;
			var videoFrameRate = "29.97";
			var videoChoice = "$videoCodec";
			var audioChoice = "$audioCodec";

			var userAgent = navigator.userAgent;
		    var wsURL = "$wsURLWebRTC";
			var streamInfo = {applicationName:"$applicationWebRTC", streamName:"$streamQuery", sessionId:"[empty]"};
			var userData = {param1:"value1","videowhisper":"webrtc-playback"};

		jQuery( document ).ready(function() {
 		browserReady();
});

		</script>
HTMLCODE;

		if ( ! $atts['silent'] ) {
			if ( $codeMuted ) {
				$htmlCode .= 'Playback is muted to allow auto play: enable audio from player controls.';
			}
		}
		return $htmlCode;
	}


	static function videowhisper_cam_webrtc_broadcast( $atts ) {
		$stream = '';

		if ( ! is_user_logged_in() ) {
			return "<div class='error'>" . __( 'Broadcasting not allowed: Only logged in users can broadcast!', 'ppv-live-webcams' ) . '</div>';
		}

		$options = get_option( 'VWliveWebcamsOptions' );

		// 1. webcam post
		$postID = get_the_ID();
		if ( is_single() ) {
			if ( get_post_type( $postID ) == $options['custom_post'] ) {
				$room = self::post_title( $postID );
			}
		}

		// 2. shortcode param
		// $stream = get_post_meta($postID, 'performer', true);

		$atts = shortcode_atts(
			array(
				'room'      => $room,
				'webcam_id' => $postID,
			),
			$atts,
			'videowhisper_cam_webrtc_broadcast'
		);
		$atts = array_map('esc_attr', $atts);

		if ( $atts['room'] ) {
			$room = $atts['room'];
		}

		$room = sanitize_file_name( $room );

		if ( ! $room ) {
			return "<div class='error'>Can't load application: Missing room name!</div>";
		}

		$postID = $atts['webcam_id'];

		if ( $room && ! $postID ) {
			global $wpdb;
			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type=%s LIMIT 0,1", sanitize_file_name( $room ), $options['custom_post'] ) );
		}

		$post = get_post( $postID );
		if ( ! $post ) {
			return "<div class='error'>Webcam post not found!</div>";
		}

		// performer check
		$current_user = wp_get_current_user();

		$loggedin  = 0;
		$msg       = '';
		$performer = 0;
		$balance   = 0;
		$uid       = 0;

		// user info
		if ( $current_user ) {
			if ( $current_user->ID > 0 ) {

				// identify if user can be room performer
				if ( self::isRoomPerformer( $post, $current_user ) ) {
					$performer = 1;
				} else {
					$performer = 0;
				}
				if ( ! $performer ) {
					return "<div class='error'>Only room performers are allowed to broadcast!</div>";
				}

				$performerName = self::performerName( $current_user, $options );
				$stream        = $performerName;

				// $debug .= "p_a:".$post->post_author .".uID:".$current_user->ID.".pName:$performerName.s:$stream";
				$uid = $current_user->ID;

				if ( $uid ) {
					self::billSessions( $uid );
					$balance        = self::balance( $uid );
					$balancePending = $balance;
				}
			}
		}

		$streamQuery = self::webrtcStreamQuery( $current_user->ID, $postID, 1, $stream, $options, 0, $room );

		// detect browser
		$agent   = $_SERVER['HTTP_USER_AGENT'];
		$Android = stripos( $agent, 'Android' );
		$iOS     = ( strstr( $agent, 'iPhone' ) || strstr( $agent, 'iPod' ) || strstr( $agent, 'iPad' ) );
		$Safari  = ( strstr( $agent, 'Safari' ) && ! strstr( $agent, 'Chrome' ) );
		$Firefox = stripos( $agent, 'Firefox' );

		// publishing as WebRTC - save info for responsive playback
		update_post_meta( $postID, 'performer', $stream );
		update_post_meta( $postID, 'performerUserID', $current_user->ID );

		update_post_meta( $postID, 'stream-protocol', 'rtsp' );
		update_post_meta( $postID, 'stream-type', 'webrtc' );
		update_post_meta( $postID, 'roomInterface', 'html5' );

		if ( ! $iOS && $Safari ) {
			update_post_meta( $postID, 'stream-mode', 'safari_pc' ); // safari on pc encoding profile issues
		} else {
			update_post_meta( $postID, 'stream-mode', 'direct' );
		}

		self::enqueueUI();

		wp_enqueue_script( 'webrtc-adapter', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/adapter.js', array( 'jquery' ) );
		wp_enqueue_script( 'videowhisper-webrtc-broadcast', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/vwrtc-publish.js', array( 'jquery', 'webrtc-adapter' ) );

		$wsURLWebRTC       = $options['wsURLWebRTC'];
		$applicationWebRTC = $options['applicationWebRTC'];

		$videoCodec = $options['webrtcVideoCodec']; // 42e01f
		$audioCodec = $options['webrtcAudioCodec']; // opus

		$videoBitrate = (int) $options['webrtcVideoBitrate'];
		if ( ! $videoBitrate ) {
			$videoBitrate = 400; // 400 max for tcp with Wowza
		}

		$htmlCode .= "<!--WebRTC_Broadcast|r:$room|p:$postID|$agent|i:$iOS|a:$Android|Sa:$Safari|Ff:$Firefox-->";

		$broadcastCode = <<<HTMLCODE
		<div class="videowhisper-webrtc-camera">
		<video id="localVideo" class="videowhisper_htmlvideo" autoplay playsinline muted style="width:640px;height:480px;"></video>
		</div>

		<div class="ui segment form">
			<span id="sdpDataTag">Connecting...</span>

<hr class="divider" />
    <div class="field inline">
        <label for="videoSource">Video Source </label><select class="ui dropdown v-select" id="videoSource"></select>
    </div>

    <div class="field inline">
        <label for="videoResolution">Video Resolution </label><select class="ui dropdown v-select" id="videoResolution"></select>
    </div>

	 <div class="field inline">
        <label for="audioSource">Audio Source </label><select class="ui dropdown v-select" id="audioSource"></select>
    </div>

    		</div>


		<script type="text/javascript">

			var userAgent = navigator.userAgent;
		    var wsURL = "$wsURLWebRTC";
			var streamInfo = {applicationName:"$applicationWebRTC", streamName:"$streamQuery", sessionId:"[empty]"};
			var userData = {param1:"value1","videowhisper":"webrtc-broadcast"};
			var videoBitrate = $videoBitrate;
			var audioBitrate = 64;
			var videoFrameRate = "29.97";
			var videoChoice = "$videoCodec";
			var audioChoice = "$audioCodec";

		jQuery( document ).ready(function() {
 		browserReady();
 		jQuery(".ui.dropdown:not(.multi,.fpsDropdown)").dropdown();

});
		</script>
HTMLCODE;

		// AJAX Chat for WebRTC broadcasting

		// htmlchat ui
		// css
		wp_enqueue_style( 'jScrollPane', dirname( plugin_dir_url( __FILE__ ) ) . '/htmlchat/js/jScrollPane/jScrollPane.css' );
		wp_enqueue_style( 'htmlchat', dirname( plugin_dir_url( __FILE__ ) ) . '/htmlchat/css/chat-broadcast.css' );

		// js
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jScrollPane-mousewheel', dirname( plugin_dir_url( __FILE__ ) ) . '/htmlchat/js/jScrollPane/jquery.mousewheel.js' );
		wp_enqueue_script( 'jScrollPane', dirname( plugin_dir_url( __FILE__ ) ) . '/htmlchat/js/jScrollPane/jScrollPane.min.js' );
		wp_enqueue_script( 'htmlchat', dirname( plugin_dir_url( __FILE__ ) ) . '/htmlchat/js/script.js', array( 'jquery', 'jScrollPane' ) );

		$ajaxurl = admin_url() . 'admin-ajax.php?action=vmls_htmlchat&room=' . urlencode( sanitize_file_name( $stream ) );

		$loginCode = '<a href="' . wp_login_url() . '">Login is required to chat!</a>';
		$buttonSFx = dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/templates/messenger/message.mp3';
		$tipsSFx   = dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/templates/messenger/tips/';

		if ( $options['tips'] ) {

			// broacaster: only balance

			$tipbuttonCodes = '<p>Viewers can send you tips. Balance will update shortly after receiving a tip.</p>';

			$tipsCode = <<<TIPSCODE
<div id="tips" class="ui segment form">
<div class="inline fields">

<div class="ui label olive large">
  <i class="money bill alternate icon large"></i>Balance: <span id="balanceAmount" class="inline"> - </span>
</div>

$tipbuttonCodes
</div>
</div>
TIPSCODE;
		}

		$htmlCode .= <<<HTMLCODE
<div id="videochatContainer">
<!--Room:$stream-->
<div id="streamContainer">
$broadcastCode
</div>

<div id="chatContainer">

    <div id="chatUsers" class="ui segment"></div>

    <div id="chatLineHolder"></div>

    <div id="chatBottomBar" class="ui segment">
    	<div class="tip"></div>

        <form id="loginForm" method="post" action="" class="ui form">
Login is required to chat!
		</form>

        <form id="submitForm" method="post" action="" class="ui form">
            <input id="chatText" name="chatText" class="rounded" maxlength="255" />
            <input type="submit" class="ui button" value="Submit" />
        </form>

    </div>
</div>
</div>
$tipsCode

<script>
var vwChatAjax= '$ajaxurl';
var vwChatButtonSFx =  '$buttonSFx';
var vwChatTipsSFx =  '$tipsSFx';
</script>


HTMLCODE;

		if ( $options['transcodingWarning'] >= 1 ) {
			$htmlCode .= '<p class="info"><small>Warning: WebRTC will play directly where possible, depending on settings and viewer device. If transcoding is needed for playback, it may take up to a couple of minutes for transcoder to start and WebRTC published stream to become available for RTMP and HLS/MPEG DASH playback.
<BR>For advanced features use advanced web broadcasting interface available in PC browser with Flash plugin.</small></p>' .
				'<p><a class="ui button secondary" href="' . add_query_arg( array( 'flash-broadcast' => '' ), get_permalink( $postID ) ) . '">Try Advanced Flash Broadcast (PC)</a></p>';
		}

		return $htmlCode;

	}


	static function flash_warn() {
		$agent   = $_SERVER['HTTP_USER_AGENT'];
		$Android = stripos( $agent, 'Android' );
		$iOS     = ( strstr( $agent, 'iPhone' ) || strstr( $agent, 'iPod' ) || strstr( $agent, 'iPad' ) );
		$Safari  = ( strstr( $agent, 'Safari' ) && ! strstr( $agent, 'Chrome' ) );

		$extraInfo = '';
		if ( $Safari && ! $iOS && ! $Android ) {
			$extraInfo .= '<u><A href="https://helpx.adobe.com/flash-player/kb/enabling-flash-player-safari.html" rel="nofollow" target="_flash">Follow these instructions to enable Flash plugin in PC Safari!</A></u>';
		}

		$htmlCode = <<<HTMLCODE
<div id="flashWarning"></div>

<script>
	function detectflash(){
    if (navigator.plugins != null && navigator.plugins.length > 0){
        return navigator.plugins["Shockwave Flash"] && true;
    }
    if(~navigator.userAgent.toLowerCase().indexOf("webtv")){
        return true;
    }
    if(~navigator.appVersion.indexOf("MSIE") && !~navigator.userAgent.indexOf("Opera")){
        try{
            return new ActiveXObject("ShockwaveFlash.ShockwaveFlash") && true;
        } catch(e){}
    }
    return false;
}

//var hasFlash = ((typeof navigator.plugins != "undefined" && typeof navigator.plugins["Shockwave Flash"] == "object") || (window.ActiveXObject && (new ActiveXObject("ShockwaveFlash.ShockwaveFlash")) != false));
var hasFlash = detectflash();

var flashWarn = '<small>Using the Flash web based interface requires <a rel="nofollow" target="_flash" href="https://get.adobe.com/flashplayer/">latest Flash plugin</a> and <a rel="nofollow" target="_flash" href="https://helpx.adobe.com/flash-player.html">activating plugin in your browser</a>. Flash apps are recommended on PC for best latency and most advanced features. $extraInfo</small>'

if (!hasFlash) document.getElementById("flashWarning").innerHTML = flashWarn;

</script>
HTMLCODE;

		return $htmlCode;
	}


	static function webcamLinks( $postID, $options ) {

		if ( ! $options['profiles'] && ! $options['messages'] && ! $options['videosharevod'] && ! $options['picturegallery'] && ! $options['rateStarReview'] ) {
			return '';
		}

		$addCode = '';

		$addCode .= '<a class="ui button tiny compact" href="' . get_permalink( $postID ) . '">' . __( 'Room Page', 'ppv-live-webcams' ) . '</a> ';
		$addCode .= '<a class="ui button tiny compact" href="' . add_query_arg( 'view', 'content', get_permalink( $postID ) ) . '">' . __( 'Room Profile Content', 'ppv-live-webcams' ) . '</a> ';
		if ( $options['profiles'] ) {
			$addCode .= '<a class="ui button tiny compact" href="' . add_query_arg( 'view', 'profile', get_permalink( $postID ) ) . '">' . __( 'Room Profile Info', 'ppv-live-webcams' ) . '</a> ';
		}

		if ( $options['messages'] ) {
			$addCode .= '<a class="ui button tiny compact" href="' . add_query_arg( 'view', 'messages', get_permalink( $postID ) ) . '">' . __( 'Messages', 'ppv-live-webcams' ) . '</a> ';
		}

		return $addCode;
	}


	static function webcamContent( $postID, $room, $options, $content = '' ) {

		$header   = array();
		$contents = array();
		$active  = '';
		
		$addCode = '';

		/*
			$addCode .= '
		<script>
		jQuery(document).ready(function(){

		var vwTabs = jQuery(".tabular.menu .item");
		if (vwTabs) vwTabs.tab();
		else console.log("Interface warning: No tabs found in webcamContent");

		});
		</script>';

			//tab header
			$addCode .= '<a name="profile"></a> <div class="ui ' . $options['interfaceClass'] . ' top attached tabular menu">';

			$addCode .= '<a class="item active" data-tab="profile">' . __('Profile', 'ppv-live-webcams') . '</a>';

			if ($options['videosharevod']) $addCode .= '<a class="item" data-tab="videos">' . __('Videos', 'ppv-live-webcams') . '</a>';
			if ($options['picturegallery']) $addCode .= '<a class="item" data-tab="pictures">' . __('Pictures', 'ppv-live-webcams') . '</a>';
			if ($options['rateStarReview']) $addCode .= '<a class="item" data-tab="reviews">' . __('Reviews', 'ppv-live-webcams') . '</a>';
			if ($options['messages']) $addCode .= '<a class="item" data-tab="messages">' . __('Messages', 'ppv-live-webcams') . '</a>';

			$addCode .= '</div>';
		*/
		if ( $options['profiles'] ) {
			$header['profile'] = __( 'Profile', 'ppv-live-webcams' );
		}
		if ( $options['videosharevod'] ) {
			$header['videos'] = __( 'Videos', 'ppv-live-webcams' );
		}
		if ( $options['picturegallery'] ) {
			$header['pictures'] = __( 'Pictures', 'ppv-live-webcams' );
		}
		if ( $options['rateStarReview'] ) {
			$header['reviews'] = __( 'Reviews', 'ppv-live-webcams' );
		}

		// digital content / assets from micropayments
		$contentIDs = get_post_meta( $postID, 'contentIDs', true );
		if ( ! is_array( $contentIDs ) ) {
			$contentIDs = array();
		}
		if ( $options['micropaymentsAssets'] ) {
			if ( shortcode_exists( 'videowhisper_content_list' ) ) {
				if ( count( $contentIDs ) ) {
					$header['content'] = __( 'Content', 'ppv-live-webcams' );
				}
			}
		}

		// products
		$productIDs = get_post_meta( $postID, 'productIDs', true );
		if ( ! is_array( $productIDs ) ) {
			$productIDs = array();
		}
		if ( $options['woocommerce'] ) {
			if ( shortcode_exists( 'products' ) ) {
				if ( count( $productIDs ) ) {
					$header['products'] = __( 'Products', 'ppv-live-webcams' );
				}
			}
		}

		if ( $options['messages'] ) {
			$header['messages'] = __( 'Messages', 'ppv-live-webcams' );
		}

		if ( ! count( $header ) ) {
			return '';
		}

			// tab : profile
			// $addCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment active" data-tab="profile">';

			$addCode = ' <div class="ui grid">';

		if ( $options['profiles'] ) {

			$active = 'profile';

			if ( $options['videosharevod'] ) {
				$video_teaser = get_post_meta( $postID, 'video_teaser', true );

				if ( $video_teaser ) {
					$addCode .= '<div class="item"><h3 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'Teaser', 'ppv-live-webcams' ) . '</h3> <div class="ui ' . $options['interfaceClass'] . ' segment" style="min-width:320px">' . do_shortcode( '[videowhisper_player video="' . $video_teaser . '"]' . '</div></div>' );
				}
			}

			if ( $options['profileCall'] ?? true ) $addCode .= '<div class="item"><h3 class="ui ' . $options['interfaceClass'] . ' header"></h3>' . do_shortcode( '[videowhisper_callnow roomid="' . $postID . '"]' ) . '</div>';

			$addCode .= '<div class="item">';
			// ! show viewers

			if ( $options['viewersCount'] || $options['salesCount'] ) {
				$addCode .= '<h3 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'Meta', 'ppv-live-webcams' ) . '</h3><div class="ui ' . $options['interfaceClass'] . ' segment">';

				if ( $options['viewersCount'] ) {

					$maxViewers = intval( get_post_meta( $postID, 'maxViewers', true ) );

						if ( $maxViewers > 0 ) {
							$maxDate  = intval( get_post_meta( $postID, 'maxDate', true ) );
							$addCode .= __( 'Maximum viewers', 'ppv-live-webcams' ) . ': ' . $maxViewers;
							if ( $maxDate ) {
								$addCode .= ' on ' . date( 'F j, Y, g:i a', $maxDate );
							}
						}
					
				}

				// ! show clients
				if ( $options['salesCount'] ) {
					$clientsP = get_post_meta( $postID, 'paidSessionsPrivate', true );
					$clientsG = get_post_meta( $postID, 'paidSessionsGroup', true );

					$addCode .= '<div id="vwPaidSessions">' . __( 'Paid sessions', 'ppv-live-webcams' ) . ': ' . $clientsP . ' ' . __( 'private', 'ppv-live-webcams' ) . ', ' . $clientsG . ' ' . __( 'group', 'ppv-live-webcams' ) . '. ' . __( 'Logged days', 'ppv-live-webcams' ) . ': ' . round( $options['ppvKeepLogs'] / 3600 / 24, 2 ) . ' </div>';
				}

				$addCode .= '</div>';
			}

			// ! show profile
			$profileCode = self::webcamProfile( $postID, $options );
			if ( $profileCode ) {
				$addCode .= '<h3 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'Profile Info', 'ppv-live-webcams' ) . '</h3>' . $profileCode;
			}

			//description
			if ( $content ) {
				$addCode .= '<h3 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'Description', 'ppv-live-webcams' ) . '</h3><div class="ui ' . $options['interfaceClass'] . ' segment">' . $content . '</div>';
			}

			$addCode .= '</div></div>';

			$contents['profile'] = $addCode;
		}

		// ! show videos
		if ( $options['videosharevod'] ) {

			if ( ! $active ) {
				$active = 'videos';
			}

			$addCode = '';
			// tab : videos
			// $addCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment" data-tab="videos">';

			$vw_videos = get_post_meta( $postID, 'vw_videos', true );

			if ( $vw_videos ) {
				if ( shortcode_exists( 'videowhisper_videos' ) ) {
					$addCode .= '<h3 class="header">' . __( 'Videos', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[videowhisper_videos playlist="' . $room . '" include_css="1"]' );
				} else {
					$addCode .= 'Warning: shortcodes missing. Plugin <a href="https://videosharevod.com">Video Share VOD</A> should be installed and enabled or feature disabled.';
				}
			}

				// $addCode .= '</div>';

				$contents['videos'] = $addCode;
		}

		// ! show pictures
		if ( $options['picturegallery'] ) {
			$addCode = '';
			// tab : pictures
			// $addCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment" data-tab="pictures">';

			$vw_pictures = get_post_meta( $postID, 'vw_pictures', true );
			if ( $vw_pictures ) {
				if ( shortcode_exists( 'videowhisper_pictures' ) ) {
					$addCode .= '<h3 class="ui header">' . __( 'Pictures', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[videowhisper_pictures perpage="12" gallery="' . $room . '" include_css="1"]' );
				} else {
					$addCode .= 'Warning: shortcodes missing. Plugin <a href="https://wordpress.org/plugins/picture-gallery/">Picture Gallery</A> should be installed and enabled or feature disabled.';
				}
			}

				// $addCode .= '</div>';
				$contents['pictures'] = $addCode;

		}

		// ! show reviews
		if ( $options['rateStarReview'] ) {
			$addCode = '';

			// tab : reviews
			// $addCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment" data-tab="reviews">';

			if ( shortcode_exists( 'videowhisper_review' ) ) {
				$addCode .= '<h3 class="ui header">' . __( 'My Review', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[videowhisper_review content_type="webcam" post_id="' . $postID . '" content_id="' . $postID . '"]' );
			} else {
				$addCode .= 'Warning: shortcodes missing. Plugin <a target="_plugin" href="https://wordpress.org/plugins/rate-star-review/">Rate Star Review</a> should be installed and enabled or feature disabled.';
			}

			if ( shortcode_exists( 'videowhisper_reviews' ) ) {
				$addCode .= '<h3 class="ui header">' . __( 'Reviews', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[videowhisper_reviews post_id="' . $postID . '"]' );
			}

			// $addCode .= '</div>';
			$contents['reviews'] = $addCode;

		}

		// content

		if ( $options['micropaymentsAssets'] ) {
			if ( shortcode_exists( 'videowhisper_content_list' ) ) {
				if ( count( $contentIDs ) ) {

					$addCode = '';

					$addCode .= '<h3 class="ui header">' . __( 'Room Content', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[videowhisper_content_list ids="-1,' . implode( ',', $contentIDs ) . '"]' );

					// $addCode .= '</div>';
					$contents['content'] = $addCode;

				}
			}
		}

		// products

		if ( $options['woocommerce'] ) {
			if ( shortcode_exists( 'products' ) ) {
				if ( count( $productIDs ) ) {

					$addCode = '';

					$addCode .= '<h3 class="ui header">' . __( 'Room Products', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[products ids="' . implode( ',', $productIDs ) . '"]' );

					// $addCode .= '</div>';
					$contents['products'] = $addCode;
				}
			}
		}

				// ! show messages

		if ( $options['messages'] ) {

			if ( ! $active ) {
				$active = 'messages';
			}

			$addCode = '';

			// tab : messages
			// $addCode .= '<div class="ui ' . $options['interfaceClass'] . ' bottom attached tab segment" data-tab="messages">';

			$addCode .= self::webcamMessages( $postID, $options, $room );

			// $addCode .= '</div>';
			$contents['messages'] = $addCode;

		}

			return self::sectionsLayout( $header, $contents, $active, $options['profileLayout'], $room );

		// return $addCode;
	}


	static function webcamMessages( $postID, $options = null, $name = '' ) {
		if ( ! $options ) {
			$options = get_option( 'VWliveWebcamsOptions' );
		}
		$addCode = '';

		$addCode .= '<h3 class="ui header">' . ( $name ? $name . ': ' : '' ) . __( 'Send Question or Message', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[videowhisper_cam_message post_id="' . $postID . '"]' );

		$addCode .= '<h3 class="ui header">' . ( $name ? $name . ': ' : '' ) . __( 'My Previous Questions and Messages', 'ppv-live-webcams' ) . '</h3>' . do_shortcode( '[videowhisper_cam_messages post_id="' . $postID . '"]' );
		return $addCode;
	}


	static function webcamProfile( $postID, $options ) {
		if ( ! $options ) {
			$options = self::getOptions();
		}

		// allowed tags
		$allowedtags = array(
			'a'          => array(
				'href'  => true,
				'title' => true,
			),
			'abbr'       => array(
				'title' => true,
			),
			'acronym'    => array(
				'title' => true,
			),
			'b'          => array(),
			'blockquote' => array(
				'cite' => true,
			),
			'cite'       => array(),
			'code'       => array(),
			'del'        => array(
				'datetime' => true,
			),
			'em'         => array(),
			'i'          => array(),
			'q'          => array(
				'cite' => true,
			),
			'strike'     => array(),
			'strong'     => array(),

			'ul'         => array(),
			'ol'         => array(),
			'li'         => array(),

			'span'       => array(
				'style' => array(),
			),

			'p'          => array(
				'style' => array(),
			),
		);

		$profileCode = '<div>';

		if ( is_array( $options['profileFields'] ) ) {
			foreach ( $options['profileFields'] as $field => $parameters ) {
				$fieldName = sanitize_title( trim( $field ) );


				// retrieve value
				$fieldValue = get_post_meta( $postID, 'vwf_' . $fieldName, true );
				
				//$profileCode .= "* $fieldName=$fieldValue | ";

				if ( in_array( $parameters['type'], array('checkboxes', 'multiselect', 'language') ) ) 
				 {
					if ( ! $fieldValue ) {
						$fieldValue = array();
					}
					if ( ! is_array( $fieldValue ) ) {
						$fieldValue = array( $fieldValue );
					}
				} else {
					if (is_string($fieldValue)) $fieldValue = wp_kses( $fieldValue, $allowedtags );
					else $profileCode .= '* Warning: Invalid field value for ' . $field . '';
				}

				if ( $fieldValue ) {
					if ( ! is_array( $fieldValue ) ) {
									$profileCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">
						<h4 class="ui ' . $options['interfaceClass'] . ' header">' . $field . '</h4>' . $fieldValue . '</div>';
					} else {


						$fieldValues = '';
					//	$fieldValues .= 'vwf_' . $fieldName . '=' . json_encode( $fieldValue );

						foreach ( $fieldValue as $fieldItem ) {
							$fieldValues .= ( $fieldValues ? ', ' : '' ) . $fieldItem;
						}

						$profileCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">
						<h4 class="ui ' . $options['interfaceClass'] . ' header">' . $field . '</h4>' . $fieldValues . '</div>';
					}
				}
			}
		}
		$profileCode .= '</div>';
		return $profileCode;

	}


	static function videowhisper_videochat( $atts ) {
		// Shortcode: shows videochat interface depending on mode, device

		$stream = ''; // room name

		$options = self::getOptions();
		// username used with application
		/*
			$userName =  $options['userName'];
			if (!$userName) $userName='user_nicename';
			global $current_user;
			get_currentuserinfo();
			if ($current_user->$userName) $username=sanitize_file_name($current_user->$userName);
			$postID = 0;
			*/

		// 1. webcam post
		$postID = 0;
		$room = '';

		if ( is_single() ) {
			if ( get_post_type( $postID = get_the_ID() ) == $options['custom_post'] ) {
				$room   = self::post_title( $postID );
				$stream = $room;
			} else {
				$postID = 0; // post or page?
			}
		}

		$atts = shortcode_atts(
			array(
				'room'      => $stream,
				'flash'     => '0',
				'html5'     => '0',
				'webcam_id' => $postID,
			),
			$atts,
			'videowhisper_videochat'
		);
		$atts = array_map('esc_attr', $atts);

		// 2. shortcode param
		// $stream = get_post_meta($postID, 'performer', true);
		if ( !$room ) {
			$room = $atts['room'];
		}

		$room   = sanitize_file_name( $room );
		if (!$postID) $postID = $atts['webcam_id'];

		global $wpdb;
		if ( ! $room && $postID ) {
			$room = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE ID = %d and post_type=%s LIMIT 0,1", $postID, $options['custom_post'] ) );
		}

		if ( ! $room ) {
			return "<div class='ui segment red'>Can't load application: Missing room name!</div>";
		}

		if ( $room && ! $postID ) {
			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type=%s LIMIT 0,1", sanitize_file_name( $room ), $options['custom_post'] ) );
		}

		$ztime = time();

		// update access time, if anybody accessed including visitors, 20s precision
		$accessed = intval( get_post_meta( $postID, 'accessed', true ) );
		if ( $ztime - $accessed > 20 )update_post_meta( $postID, 'accessed', $ztime );

		// login
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$uid          = $current_user->ID;
			$balance = self::balance( $current_user->ID );

			$post      = get_post( $postID );
			$performer = 0;
			if ( self::isRoomPerformer( $post, $current_user ) ) {
				$performer = 1;
			}

			$accessedUser = intval( get_post_meta( $postID, 'accessedUser', true ) );
			if ( $ztime - $accessedUser > 10 ) update_post_meta( $postID, 'accessedUser', $ztime );


		} else {
			$uid       = 0;
			$performer = 0;
			$balance = 0;

			// paid room requires login
			$groupCPM = get_post_meta( $postID, 'groupCPM', true );
			if ( $groupCPM ) {
				return '<div class="ui segment red"><div class="ui header"><i class="ticket icon"></i>' .$room. '</div>Only registered and logged in users can access rooms in paid mode. Requires: ' . $groupCPM .' ' . $options['currencypm'].  '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'ppv-live-webcams' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'ppv-live-webcams' ) . '</a></div>';
			}
		}

		$attsPrivate = '';
		if ( ! $performer ) {
			// free mode limits: check before rendering videochat room
			global $wpdb;
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

			if ( ! isset( $clientIP ) ) {
				$clientIP = self::get_ip_address();
			}

			if ( $uid ) {
				$cnd = "uid='$uid'";
			} else {
				$cnd = "ip='$clientIP' AND uid='0'";
			}

			$h24      = time() - 86400;
			$sqlC     = $wpdb->prepare("SELECT SUM(edate-sdate) FROM `$table_sessions` WHERE $cnd AND broadcaster='0' AND sdate > %d AND edate > %d", $h24, $h24);
			$freeTime = $wpdb->get_var( $sqlC );

			if ( $uid ) {
				if ( $freeTime > $options['freeTimeLimit'] && $options['freeTimeLimit'] > 0 ) {

					if ( !$options['freeTimeBalance'] ) $disconnect = __( 'Free chat daily time limit reached: You can only access paid group rooms today.', 'ppv-live-webcams' ) . ' (' . $freeTime . 's > ' . $options['freeTimeLimit'] . 's ' . $cnd . ')';
					elseif ( $balance < $options['freeTimeBalance'] ) $disconnect = __( 'Free chat daily time limit reached. You get unlimited free chat time if you add a minimum balance.', 'ppv-live-webcams' ) . ' (' . $options['freeTimeBalance'] . $options['currency'] . ')';

				}
			}
			if ( ! $uid ) {
				if ( $freeTime > $options['freeTimeLimitVisitor'] && $options['freeTimeLimitVisitor'] > 0 ) {
					$disconnect = __( 'Free chat daily visitor time limit reached: Register and login for more chat time today!', 'ppv-live-webcams' ) . ' (' . $freeTime . 's > ' . $options['freeTimeLimitVisitor'] . 's ' . $cnd . ')';
				}
			}

			if ( isset( $disconnect ) ) {
				return "<div class='ui segment red'>$disconnect</div>";
			}

					// buddypress groups
					$buddypressGroup = get_post_meta( $postID, 'buddypressGroup', true );
			if ( $buddypressGroup ) {
				if ( function_exists( 'groups_is_user_member' ) ) {
					if ( ! groups_is_user_member( $uid, $buddypressGroup ) ) {
						return '<div class="ui segment red">' . __( 'This is a BuddyPress group room and you are not a member!', 'ppv-live-webcams' ) . '</div>';
					}
				}
			}
		}

		$htmlCode = '';

		// flash/webrtc room interface
		$roomInterface  = get_post_meta( $postID, 'roomInterface', true ); // flash/html5/html5app
		$playlistActive = get_post_meta( $postID, 'vw_playlistActive', true );

		// special modes (for clients)
		$isVoyeur  = 0;
		$is2way    = 0;
		$isPrivate = 0;

		$specialMode = sanitize_file_name( $_GET['vwsm'] ?? '' );

		if ( $specialMode ) {
			if ( is_user_logged_in() ) {

				$groupParameters = get_post_meta( $postID, 'groupParameters', true );
				// var_dump($groupParameters);

				// enable mode list for room and capabilities for user:

				switch ( $specialMode ) {

					case 'private':
						$isPrivate = 1;
						$calltype  = sanitize_file_name( $_GET['calltype'] ?? '' );

						//downgrade if room in lesser mode
						$room_audio = self::is_true( get_post_meta( $postID, 'room_audio', true ) );
						$room_text = self::is_true( get_post_meta( $postID, 'room_text', true ) );
						if ($room_audio) if ($calltype != 'text') $calltype = 'audio';
						if ($room_text) $calltype = 'text';

						if ( ! $attsPrivate ) {
							$attsPrivate = 'private="1" type="' . $calltype . '"';
						}

						//notification to performer
						if ($uid && !$performer)
						{
							$performerID = intval( get_post_meta( $postID, 'performerUserID', true ) );
							if ($options['privateNotification']) if ($performerID) self::sendNotification($performerID, $options['privateSubject'] . ' ' . $current_user->display_name , $options['privateText'], get_permalink( $postID ), $options);
						}

						break;

					case '2way':
						$mode2way = get_post_meta( $postID, 'mode2way', true );

						if ( ! is_array( $mode2way ) ) {
							$mode2way = array();
						}

						// add user if slots available
						if ( count( $mode2way ) < $groupParameters['2way'] ) {
							$mode2way[ $uid ] = time();
							update_post_meta( $postID, 'mode2way', $mode2way );
							$is2way = 1;
						}
						break;

					case 'voyeur':
						$modeVoyeur = get_post_meta( $postID, 'modeVoyeur', true );
						if ( ! is_array( $modeVoyeur ) ) {
							$modeVoyeur = array();
						}

						if ( $groupParameters['voyeur'] ) {
							$modeVoyeur[ $uid ] = time();
							update_post_meta( $postID, 'modeVoyeur', $modeVoyeur );
							$isVoyeur = 1;
						}
						break;
				}
			} else {
				return '<div class="ui ' . $options['interfaceClass'] . ' header"> <i class="sign in icon"></i> ' . __( 'Login to access special chat mode!', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_login_form register="1" role="' . $options['roleClient'] . '"]' );
			}
		}

		$isCall = 0;
		if ( $_GET['call'] ?? false ) {
			$isCall = 1;
		}

		if ( ! $performer ) {
			$group_disabled = self::is_true( get_post_meta( $postID, 'group_disabled', true ) );
			if ( $group_disabled ) {
				if ( ! $isPrivate && ! $isVoyeur && ! $isCall ) {
					return '<div class="ui segment red">' . __( 'Group chat is disabled. This room can only be used for instant or locked calls.', 'ppv-live-webcams' ) . '</div>';
				}
			}
		}


		// HLS if iOS/Android detected
		$agent   = $_SERVER['HTTP_USER_AGENT'];
		$Android = strstr( $agent, 'Android' );
		$iOS     = ( strstr( $agent, 'iPhone' ) || strstr( $agent, 'iPod' ) || strstr( $agent, 'iPad' ) );
		$Safari  = ( strstr( $agent, 'Safari' ) && ! strstr( $agent, 'Chrome' ) );

		$htmlCode .= "<!--VideoChat:$roomInterface room:$room postID:$postID Performer:$performer SpecialMode:$specialMode 2Way:$is2way Voyeur:$isVoyeur Device:$Android|$iOS|$Safari($agent) HTML5:-->";

		// handle paused reStreams
			$restreamPaused = false;
			if ( $options['reStreams'] )
			{
				$reStreams = get_post_meta( $postID, 'reStreams', true );
				if ( $reStreams ) {
					$restreamPaused = get_post_meta( $postID, 'restreamPaused', true );
					$htmlCode .= self::restreamPause( $postID, $stream, $options );
				}
			}

		$fallback = 0;

		if ( ! $atts['flash'] && ( $roomInterface != 'flash' || ! $performer ) ) {

			if ( $options['webrtc'] > 2 ) {
				if ( isset($_GET['html5app']) || $roomInterface == 'html5app' || ! self::webcamOnline( $postID ) ) {
					$htmlCode .= do_shortcode( "[videowhisper_cam_app room=\"$room\" webcam_id=\"$postID\" $attsPrivate]" );
					$htmlCode .= apply_filters( 'vw_plw_videochat', '', $postID );
					return $htmlCode;
				}
			}

			// performer
			if ( $performer ) {
				if ( $roomInterface == 'html5' || $Android || $iOS || ( $_GET['html5'] && $options['htmlchatTest'] ) || $atts['html5'] ) {
					$htmlCode .= do_shortcode( "[videowhisper_cam_webrtc_broadcast room=\"$room\" webcam_id=\"$postID\"]" );
					$htmlCode .= apply_filters( 'vw_plw_videochat', '', $postID );
					return $htmlCode;
				} else {
					$showHTML5 = 0; // performer in flash mode
					$fallback  = 0;
				}
			} else // client
				{

				if ( $options['teaserOffline'] && ! self::webcamOnline( $postID ) ) {
					$showHTML5  = 1;
					$comment   .= ' Teaser';
					$fallback   = 1;
					$showTeaser = 1;
				}

				// client in webrtc room
				if ( $roomInterface == 'html5' && ! $showTeaser ) {
					if ( ! $isVoyeur ) {
						$htmlCode .= do_shortcode( "[videowhisper_htmlchat room=\"$room\" post_id=\"$postID\" width=\"$width\" height=\"$height\"]" );
					} else {
						$htmlCode .= do_shortcode( "[videowhisper_cam_webrtc_playback room=\"$room\" webcam_id=\"$postID\" width=\"$width\" height=\"$height\"]" );
					}

					$htmlCode .= apply_filters( 'vw_plw_videochat', '', $postID );
					return $htmlCode;
				}

				if ( $options['transcoding'] >= 3 ) {
					if ( $playlistActive && ! $performer ) {
						$showHTML5 = 1;
						$comment  .= ' Playlist';
						$fallback  = 1;
					}
				}

				// detection for client videochat interface
				if ( ( $Android && in_array( $options['detect_mpeg'], array( 'android', 'all' ) ) ) || ( ! $iOS && in_array( $options['detect_mpeg'], array( 'all' ) ) ) || ( ! $iOS && ! $Safari && in_array( $options['detect_mpeg'], array( 'nonsafari' ) ) ) ) {
					$showHTML5 = 1;
					$fallback  = 1;
					$comment  .= ' detectMPEG';
				}

				/*
						if ($options['htmlchat'] && !$isVoyeur)
						{
							$htmlCode .= do_shortcode("[videowhisper_htmlchat room=\"$room\" post_id=\"$postID\" width=\"$width\" height=\"$height\"]");
							$fallback = 1;
						}
					else
					{
						$htmlCode .= do_shortcode("[videowhisper_cammpeg webcam=\"$room\" width=\"$width\" height=\"$height\" webstatus=\"1\"]");
						$fallback = 2;
					}
				*/

				if ( ( ( $Android || $iOS ) && in_array( $options['detect_hls'], array( 'mobile', 'safari', 'all' ) ) ) || ( $iOS && $options['detect_hls'] == 'ios' ) || ( $Safari && in_array( $options['detect_hls'], array( 'safari', 'all' ) ) ) ) {
					$showHTML5 = 1;
					$fallback  = 1;
					$comment  .= ' detectHLS';
				}
				/*
						if ($options['htmlchat'] && !$isVoyeur)
						{
							$htmlCode .=  do_shortcode("[videowhisper_htmlchat room=\"$room\" post_id=\"$postID\" width=\"$width\" height=\"$height\" ]");
							$fallback = 1;
						}
					else
					{
						$htmlCode .=  do_shortcode("[videowhisper_camhls webcam=\"$room\" width=\"$width\" height=\"$height\" webstatus=\"1\"]");
						$fallback = 3;
					}
					*/

				if ( $showHTML5 ) {
					$htmlCode .= '<!--$showHTML5: ' . $comment . ' -->';
					if ( ! $isVoyeur ) {
						$htmlCode .= do_shortcode( "[videowhisper_htmlchat room=\"$room\" post_id=\"$postID\" width=\"$width\" height=\"$height\"]" );
					} else {
						$htmlCode .= '<H4>Voyeur Mode</H4>' . do_shortcode( '[videowhisper_camvideo post_id="' . $postID . '" cam="' . $room . '" html5="always"]' );
					}
				}
			}

			// forced testing, even when  should show flash
			if ( $options['htmlchatTest'] && ( $_GET['htmlchat'] || $_GET['html5'] ) ) {
				$htmlCode .= '<!--Test Chat-->' . do_shortcode( "[videowhisper_htmlchat post_id=\"$postID\" room=\"$room\"]" );
				$fallback  = 1;
			}

			if ( $options['htmlchatTest'] && $_GET['hls'] ) {
				$htmlCode .= '<!--Test HLS-->' . do_shortcode( "[videowhisper_camhls webcam=\"$room\" width=\"$width\" height=\"$height\"]" );
				$fallback  = 3;
			}

			if ( $options['htmlchatTest'] && $_GET['mpeg'] ) {
				$htmlCode .= '<!--Test MPEG-->' . do_shortcode( "[videowhisper_cammpeg webcam=\"$room\" width=\"$width\" height=\"$height\"]" );
				$fallback  = 2;
			}

			if ( $fallback ) {
				$htmlCode .= '<!--HTML5 Videochat End-->';
			}
		}

		// Flash app
		if ( ! $fallback ) {
			if ( $isVoyeur ) {
				$htmlCode .= '<H4>Voyeur Mode</H4>' . do_shortcode( '[videowhisper_camvideo cam="' . $room . '"]' );
			} else {

				$swfurl  = dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/videomessenger.swf?ssl=1&room=' . urlencode( $room );
				$swfurl .= '&prefix=' . urlencode( admin_url() . 'admin-ajax.php?action=vmls&task=' );
				$swfurl .= '&extension=' . urlencode( '_none_' );
				$swfurl .= '&ws_res=' . urlencode( dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/' );

				$bgcolor = '#333333';

				$htmlCode .= <<<HTMLCODE
<div id="videowhisper_container">
<object id="videowhisper_flash" width="100%" height="100%" type="application/x-shockwave-flash" data="$swfurl">
<param name="movie" value="$swfurl"></param><param bgcolor="$bgcolor"><param name="scale" value="noscale" /> </param><param name="salign" value="lt"></param><param name="allowFullScreen"
value="true"></param><param name="allowscriptaccess" value="always"></param>
</object>
<!-- $stream : $agent -->
</div>

<br style="clear:both" />

<style type="text/css">
<!--

#videowhisper_flash
{
width: 100%;
height: 700px;
max-height: 700px;
border: solid 3px #999;
display: block;
}

-->
</style>

HTMLCODE;

				$htmlCode .= self::flash_warn();

				$htmlCode .= '<p>';
				if ( $performer && $options['webrtc'] >= 2 ) {
					$htmlCode .= '<a class="ui button secondary" href="' . add_query_arg( array( 'html5app' => '1' ), get_permalink( $postID ) ) . '">' . __( 'Try HTML5 Videochat', 'ppv-live-webcams' ) . '</a>';
				}

				/*
				 //remove optional html5 streaming
				if ($options['transcoding'] >= 2 ||  ($performer && $options['webrtc'] >= 2))
				{
					if ($options['htmlchatTest'])
						$htmlCode .= '<a class="ui button secondary" href="' . add_query_arg(array('html5'=>'1'), get_permalink($postID)) . '">Try HTML5 Streaming</a>';
				}
				*/

				$htmlCode .= '</p>';

			}
		} else {
			$htmlCode .= 'Error: Fallback disabled!';
		}

		$htmlCode .= apply_filters( 'vw_plw_videochat', '', $postID );

		return $htmlCode;
	}


	static function videowhisper_htmlchat( $atts ) {
		// [videowhisper_htmlchat room="webcam name" width="480px" height="360px"]

		$atts = shortcode_atts(
			array(
				'room'        => '',
				'post_id'     => '0',
				'videowidth'  => '480px',
				'videoheight' => '360px',
			),
			$atts,
			'videowhisper_htmlchat'
		);
		$atts = array_map('esc_attr', $atts);

		$room = sanitize_text_field( $atts['room'] );
		if ( ! $room ) {
			$room = sanitize_text_field( $_GET['room'] );
		}

		$room = sanitize_file_name( $room );

		$postID = intval( $atts['post_id'] );

		$options = get_option( 'VWliveWebcamsOptions' );

		if ( ! $postID ) {
			global $wpdb;

			$postID = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s and post_type=%s LIMIT 0,1', $room, $options['custom_post'] ) );

		}

		$videowidth = $atts['videowidth'];
		if ( ! $videowidth ) {
			$videowidth = '480px';
		}
		$videoheight = $atts['videoheight'];
		if ( ! $videoheight ) {
			$videoheight = '360px';
		}

		if ( ! $room ) {
			return 'HTML AJAX Chat Error: Missing room name!';
		}

		$isPerformer = self::isAuthor( $postID ); // is current user performer?

		self::enqueueUI();

		// htmlchat ui
		// css
		wp_enqueue_style( 'jScrollPane', dirname( plugin_dir_url( __FILE__ ) ) . '/htmlchat/js/jScrollPane/jScrollPane.css' );
		wp_enqueue_style( 'htmlchat', dirname( plugin_dir_url( __FILE__ ) ) . '/htmlchat/css/chat-watch.css' );

		// js
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jScrollPane-mousewheel', dirname( plugin_dir_url( __FILE__ ) ) . '/htmlchat/js/jScrollPane/jquery.mousewheel.js' );
		wp_enqueue_script( 'jScrollPane', dirname( plugin_dir_url( __FILE__ ) ) . '/htmlchat/js/jScrollPane/jScrollPane.min.js' );
		wp_enqueue_script( 'htmlchat', dirname( plugin_dir_url( __FILE__ ) ) . '/htmlchat/js/script.js', array( 'jquery', 'jScrollPane' ) );

		$ajaxurl = admin_url() . 'admin-ajax.php?action=vmls_htmlchat&room=' . urlencode( $room );

		$videoCode = do_shortcode( '[videowhisper_camvideo cam="' . $room . '" post_id="' . $postID . '" html5="always"]' );

		$loginCode = '<a class="ui button" href="' . wp_login_url() . '">Login is required to chat!</a>';

		$buttonSFx = dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/templates/messenger/message.mp3';
		$tipsSFx   = dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/templates/messenger/tips/';

		if ( $options['tips'] ) {

			// tip options
			$tipOptions = stripslashes( $options['tipOptions'] );
			if ( $tipOptions ) {
				$p = xml_parser_create();
				xml_parse_into_struct( $p, trim( $tipOptions ), $vals, $index );
				$error = xml_get_error_code( $p );
				xml_parser_free( $p );

				if ( is_array( $vals ) ) {
					foreach ( $vals as $tKey => $tip ) {
						if ( $tip['tag'] == 'TIP' ) {
							if ( $tip['attributes']['AMOUNT'] != 'custom' ) {
								// var_dump($tip['attributes']);
								$amount = intval( $tip['attributes']['AMOUNT'] );
								if ( ! $amount ) {
									$amount = 1;
								}
								$label = $tip['attributes']['LABEL'];
								if ( ! $label ) {
									$label = '$1 Tip';
								}
								$note = $tip['attributes']['NOTE'];
								if ( ! $note ) {
									$label = 'Tip';
								}
								$sound = $tip['attributes']['SOUND'];
								if ( ! $sound ) {
									$sound = 'coins1.mp3';
								}
								$image = $tip['attributes']['IMAGE'];
								if ( ! $image ) {
									$image = 'gift1.png';
								}

								$imageURL = $tipsSFx . $image;

								$tipbuttonCodes .= <<<TBCODE
	<div class="tipButton ui labeled button small" tabindex="0" amount="$amount" label="$label" note="$note" sound="$sound" image="$image">
  <div class="ui button">
    <img class="mini image avatar" src="$imageURL"> $label
  </div>
  <a class="ui basic label small">
    $amount
  </a>
</div>
TBCODE;
							}
						}
					}
				}
			}

						$balanceURL = '#';
			if ( $options['balancePage'] ) {
				$balanceURL = get_permalink( $options['balancePage'] );
			}

			$tipsCode = <<<TIPSCODE
<div id="tips" class="ui segment form">
<div class="inline fields">

<a href="$balanceURL" target="_balance" class="ui label olive large">
  <i class="money bill alternate icon large"></i>Balance: <span id="balanceAmount" class="inline"> - </span>
</a>

$tipbuttonCodes
</div>
</div>
TIPSCODE;
		}

		$htmlCode = <<<HTMLCODE
<div id="videochatContainer">
<!--$room-->
<div id="streamContainer">
$videoCode
</div>

<div id="chatContainer">
    <div id="chatUsers" class="ui segment"></div>

    <div id="chatLineHolder"></div>

    <div id="chatBottomBar" class="ui segment">

    	<div class="tip"></div>

        <form id="loginForm" method="post" action="" class="ui form">
$loginCode
		</form>

        <form id="submitForm" method="post" action="" class="ui form">
            <input id="chatText" name="chatText" class="rounded" maxlength="255" />
            <input id="submit" type="submit" class="ui button" value="Submit" />
        </form>

    </div>

</div>
</div>
$tipsCode

<script>
var vwChatAjax= '$ajaxurl';
var vwChatButtonSFx =  '$buttonSFx';
var vwChatTipsSFx =  '$tipsSFx';

var \$jQ = jQuery.noConflict();
\$jQ(document).ready(function(){
\$jQ('.tipButton').popup();
});
</script>

HTMLCODE;

		return $htmlCode;

	}



}
