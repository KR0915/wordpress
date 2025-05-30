<?php
// PaidVideochat.com : HTML5 Videochat
// This file includes mainly functionality related to integrating the HTML5 Videochat application with WordPress platform (database, user system, shortcodes) in the PaidVideochat turnkey site solution.
// Handles AJAX calls from HTML5 Videochat application, receiving and returning chat updates, delivering configuration parameters and data.

namespace VideoWhisper\LiveWebcams;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


define( 'VW_H5V_DEVMODE', 0 );
define( 'VW_H5V_DEVMODE_COLLABORATION', 0 );
define( 'VW_H5V_DEVMODE_CLIENT', 0 );

trait H5Videochat {

	static function videowhisper_match( $atts ) {
		
		// Shortcode: Random Cam Videochat
		$options = self::getOptions();

		$current_user = wp_get_current_user();

		self::enqueueUI();


			$userID = $current_user->ID;
			if ($userID == 0 )
			{
				//use a cookie for visitor username persistence, if possible
				if ($_COOKIE['htmlchat_username'] ?? false) $userName = sanitize_file_name(wp_unslash($_COOKIE['htmlchat_username']));
				else
				{
					$userName =  'G_' . base_convert(time()%36 * wp_rand(0, 36*36), 10, 36);
					@setcookie('htmlchat_username', $userName);
				}
				
			}
			else 
			{
				$userName = $current_user->user_nicename;
				
				// performer to dashboard
				if ( self::any_in_array( self::getRolesPerformer(), $current_user->roles ) ) 
					{
					
						return '<div class="ui message">You are a performer. If you want to participate in random matches, go live from your performer dashboard to open a booth.<br><a class="ui button" href="' . get_permalink( $options['p_videowhisper_webcams_performer'] ). '">Performer Dashboard</a> </div>' ;
					}
			}
			
			
	
					
			//bill any sessions, check balance
					self::billSessions($userID);
					$balance = self::balance( $userID ); // use complete balance to avoid double amount checking
					$ppvMinInShow = self::balanceLimit( $options['ppvPPM'], 2, $options['ppvMinInShow'], $options );

						if ( $ppvMinInShow > $balance ) {							
							return  ( shortcode_exists( 'videowhisper_wallet' ) ? do_shortcode( '[videowhisper_wallet]' ) : __( 'Your current balance', 'ppv-live-webcams' ) . ': ' . $balance . ' ' . $options['currency'] ) . '<div class="ui message">' . __( 'Not enough funds to enter a matchmaking call. Minimum required: ', 'ppv-live-webcams' ) . $ppvMinInShow . '</div>';
						}
	
		global $wpdb;
		$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';
		$table_private = $wpdb->prefix . 'vw_vmls_private';

		//continue interrupted session?
		$sqlS = $wpdb->prepare("SELECT * FROM $table_sessions WHERE uid = %d AND username = %s AND status = 0 ORDER BY edate DESC LIMIT 1", $userID, $userName);
		$session = $wpdb->get_row($sqlS);
		
		if ($session)
		{
			//need to start streaming again
			self::resetWatch($session, false);
			
			//continue in match booth if still available
			$sqlS = $wpdb->prepare("SELECT * FROM `$table_private` WHERE `call`='2' AND status='0' AND `cid`=%d AND client=%s ORDER BY pedate DESC", $userID, $userName);
			$private = $wpdb->get_row( $sqlS );
			if ($private) 
			{
				$nextMatchID = $private->id;
				$htmlCode .= __( 'Found active booth. Resuming', 'ppv-live-webcams' ) . '...' ;
			}
			else $nextMatchID = 0;

		}else
		{		
			//create session
			$session   = self::sessionUpdate( $userName, 'Matching..', 0, 11, 0, 1, 1, $options, $userID, -1, self::get_ip_address(), [ 'videowhisper_match' => time() ] );
			if (!$session) return 'Could not create session!';
			
			$nextMatchID = 0;
		}
		
	
		$info = '';
		if (!$nextMatchID) $nextMatchID = self::nextMatch($session,  $options, $debugInfo);
		
		if ( $nextMatchID ) {
			return $htmlCode . do_shortcode( '[videowhisper_cam_app booth="' . $nextMatchID . '" session= "' . intval($session->id) . '" title="' . 'Random Call Room' . '"]' );
		} else {
			
			$reloadCode = '
			Retrying in 15 seconds. <a class="ui button" href="JavaScript:window.location.reload()">Retry Now</a>
<script>
  window.setTimeout( function() {
  window.location.reload();
}, 15000);
</script>';
			return __( 'No random call match found with current criteria!', 'ppv-live-webcams' ) . $reloadCode  .'<br><pre>'. $debugInfo . '</pre>' ;
		}
	} 

	static function nextMatch( $session, $options = null, &$debugInfo = '' ) {
		// matches client to a new performer

		if ( ! $options ) {
			$options = self::getOptions();
		}
		
		//$debugInfo .= 'Session=' . print_r($session, true) ;
		
		$userID = $session->uid;
		$userName = $session->username;
		$sessionID = $session->id;
				
		global $wpdb;
		$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

		$args = array(
			'post_type'      => $options['custom_post'],
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'offset'         => 0,
			'order'          => 'DESC',
		);

		if ( $userID ) {
			$args['author__not_in'] = array( $userID );
		}

		// order
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = 'edate';



$debugInfo = 'debugMode enabled: ' ;
$debugInfo .= 'matchAdvanced='. $options['matchAdvanced'] . ' ';



		
	foreach ( $options['profileFields'] as $field => $parameters ) 
	if ( isset($parameters['match']) )
	{	
		$fieldName  = sanitize_title( trim( $field ) );
		$clientValue = get_user_meta( $userID, 'vwf_' . $fieldName, true );
		$matchValue = '';

			if ($parameters['match'] == 'mirror')
			{
				
				$fieldOptions = explode( '/', $parameters['options'] );	
				
				if ( count($fieldOptions) < 2 ) break; //skip (not enough options)

				$key = array_search($clientValue, $fieldOptions);
				if ($key === false) break; //skip (not found)
				
				if ($key == 0) $otherKey = 1; else $otherKey = 1;
				
				$matchValue = $fieldOptions[$otherKey]; //mirror option of same criteria			
				
			}
			elseif ($options['matchAdvanced'])
			{	
				//2 way matchmaking, client defined specific criteria for performer
				$matchValue = get_user_meta( $userID, 'vwf_m_' . $fieldName, true );
				
				//performer also looking for specific client type
				
				if ($clientValue) $args['meta_query']['m_' . $fieldName] = array(
					'key'   => 'vwf_m_' . $fieldName,
					'value' => [ $clientValue, '' ], //client value or blank (performer set any)
					'compare' => 'IN',
				); 
				else 
				$args['meta_query'][ 'm_' . $fieldName ] = array(
				'key'   => 'vwf_m_' . $fieldName ,
				'value' => '',
				);		
			
				//undefinend so only rooms that set Any

			}
			
			//client looking for this type of performer
			if ($matchValue) $args['meta_query'][$fieldName] = array(
				'key'   => 'vwf_' . $fieldName,
				'value' => $matchValue,
			);
	

	}
	
		// only if in random mode
		$args['meta_query']['room_random'] = array(
			'key'   => 'room_random',
			'value' => '1',
		);
		
		$args['meta_query']['match_available'] = array(
			'key'   => 'match_available',
			'value' => '1',
		);
		
		
		$args['meta_query']['edate'] = array(
				'key'     => 'edate',
				'compare' => 'EXISTS',
			);
		
		
		$args['meta_query']['online']   = array(
						'key'     => 'edate',
						'value'   => time() - 40,
						'compare' => '>',
					);
	
	
		if ( $options['debugMode'] && isset($_GET['debug']) && $_GET['debug'] == 'onlyshow' )
		{
			$debugInfo .= 'Only show criteria: ' . esc_html( print_r( $args, true ) );
			return 0;
		}
	
		$nextRoomID = 0; // no room found
		$postslist  = get_posts( $args );
		
		
		$sql        = '';

		if ( count( $postslist ) > 0 ) {

			$roomAccessed = array();

			foreach ( $postslist as $item ) {
				$rid =  $item->ID;
				$roomAccessed[ $rid  ] = intval( $wpdb->get_var( "SELECT MAX(edate) from $table_sessions WHERE uid = '$userID' AND rid='$rid'" ) );
			}

	
			asort( $roomAccessed );

			// access next room as client
			// skip room if performer there
			foreach ( $roomAccessed as $iRoomID => $accessTime ) {
				$isPerformer = self::isPerformer( $userID, $iRoomID );

				if ( ! $isPerformer ) {
					$nextRoomID = intval( $iRoomID );
				}
				if ( ! $isPerformer ) {
					break;
				}
			}
		}

		if ( ! $nextRoomID && $options['debugMode'] ) {
			$debugInfo .= 'No available matching rooms found with current criteria: ' . print_r( $args, true );
		
			unset($args['meta_query']['online']);
			unset($args['meta_query']['match_available']);
			unset($args['meta_query']['room_random']);

			$postslist  = get_posts( $args );
			$debugInfo .= ' Unavailable matching rooms:' . count($postslist);
			
			foreach ($postslist as $item) $debugInfo .= ' ' . $item->post_title;
		}
		
		$privateID = 0;
		
		if ($nextRoomID)
		{
		//create session
		$performer   = get_post_meta( $nextRoomID, 'performer', true );
		$performerID = intval( get_post_meta( $nextRoomID, 'performerUserID', true ) );
				
			$callType  = 'video';
			$clientCPM = self::clientCPM( $roomName, $options, $roomID, $callType );

			// create private session
			$meta = array(
					'time'      => time(),
					'username'  => $userName,
					'callMode'  => 'random',
					'callType'  => $callType,
					'clientCPM' => strval( $clientCPM ),
			);

			$metaS     = serialize( $meta );
				
			$post = get_post($nextRoomID);
			$room_name = $post->post_name;
					
			$ztime = time();
			
			$table_private = $wpdb->prefix . 'vw_vmls_private';

			//see if performer is available - should be if marked correctly
			$sqlS = $wpdb->prepare("SELECT * FROM `$table_private` WHERE `call`='2' AND status='0' AND `pid`=%d AND ( (cid='0' AND client='') OR (cid=%d AND client=%s AND cs=%d)) ORDER BY pedate DESC", $performerID, $userID, $userName, $sessionID);
			$match = $wpdb->get_row( $sqlS );
			
			if (!$match)
			{
				if ($options['debugMode']) {
					$lastSession = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_private` WHERE `call`='2' AND status='0' AND `pid`=%d ORDER BY pedate DESC LIMIT 1", $performerID));
					$debugInfo .= "Performer not available for Room #$nextRoomID/$room_name [$sqlS] Rooms:" . print_r($roomAccessed, true) . ' Last: ' . print_r($lastSession, true);
				}
				return 0;
			}

			//get performer session
			$sqlS = $wpdb->prepare("SELECT * FROM $table_sessions WHERE uid = %d AND rid = %d AND status = 0 ORDER BY edate DESC LIMIT 1", $performerID, $nextRoomID);
			$performerSession = $wpdb->get_row( $sqlS );

			if (!$performerSession)
			{
				if ($options['debugMode']) $debugInfo .= "Performer session not available for #$nextRoomID [$sqlS]";
				return 0;
			}

			//update client session			
			$userMeta = unserialize($session->meta);
			if (!is_array($userMeta)) $userMeta = [];
			$userMeta['watch'] = $performerSession->id;
			$userMeta['watchConfirm'] = 0;
			$userMeta['watchNexted'] = 0;
			$userMeta['watchNextedConfirm'] = 0;
			$userMeta['watchLeft'] = 0;
			$userMetaS = serialize($userMeta);

			$sqlU = $wpdb->prepare("UPDATE `$table_sessions` SET rid=%d, room=%s, meta=%s WHERE id=%d", $nextRoomID, $room_name, $userMetaS, $session->id);
			$wpdb->query( $sqlU );
						
			//update performer session			
			$userMeta = unserialize($performerSession->meta);
			if (!is_array($userMeta)) $userMeta = [];
			$userMeta['watch'] = $session->id;
			$userMeta['watchConfirm'] = 0;
			$userMeta['watchNexted'] = 0;
			$userMeta['watchNextedConfirm'] = 0;
			$userMeta['watchLeft'] = 0;
			$userMetaS = serialize($userMeta);

			$sqlU = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $performerSession->id);
			$wpdb->query( $sqlU );

			update_post_meta( $nextRoomID, 'match_available', '0' ); // mark as busy for matching

			$sqlS = $wpdb->prepare("SELECT * FROM `$table_private` WHERE `call`='2' AND status=0 AND pid=%d AND cid=0 AND client='' ORDER BY pedate DESC LIMIT 1", $performerID);
			$privateSession = $wpdb->get_row( $sqlS );
			$privateID  = intval($privateSession->id);

			if (!$privateID)
			{
				if ($options['debugMode']) $debugInfo .=  "Match session not available [$sqlS]";
				return 0;
			}
			else
			{
			$meta = unserialize($privateSession->meta);
			if (!is_array($meta)) $meta = [];
			$meta['updatedBy'] = 'nextMatch';
			$metaS = serialize($meta);
	
			//update user in private call (match)
			//$sqlU = "UPDATE `$table_private` SET cid='$userID', client='$userName', cs='$sessionID', cedate='$ztime', meta='$metaS' WHERE id='$privateID'";
			$sqlU = $wpdb->prepare("UPDATE `$table_private` SET cid=%d, client=%s, cs=%d, cedate=%d, meta=%s WHERE id=%d", $userID, $userName, $sessionID, $ztime, $metaS, $privateID);
			$wpdb->query( $sqlU );
			return $privateID;
			}
						
			
			return -1;
			
		}

		return $privateID;
	}
	
	
		static function appRandomRoom( $post, $session, $options, $message = '', $new = false, $resetWatch = false)
	{
		
		$room = array();

		if ($options['webrtcServer'] == 'auto') $room['serverType'] = 'videowhisper'; //p2p in private

		//room changes on next based on session updates
		if ($session->rid && !$session->broadcaster) $post = get_post($session->rid);
		
		$room['ID'] = $post->ID;
		$postID = $post->ID;
		$ztime = time();

		$room['welcome']  = '';

		global $wpdb;
		$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';
		$table_private = $wpdb->prefix . 'vw_vmls_private';

		
		//reload session
		$session = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_sessions WHERE id = %d", $session->id) );		


		if ( $session->broadcaster ) {
			
		$closeTime = time() - max( intval( $options['ppvCloseAfter'] ), 70 );

		// close matches expired
		$sql = $wpdb->prepare("UPDATE `$table_private` SET status=%d WHERE `call`=%d AND psdate > %d AND status=%d AND ( pedate < %d OR ( client <>'' AND cedate < %d) )", 1, 2, 0, 0, $closeTime, $closeTime);
		$wpdb->query( $sql );

			
		//in private session?
		$sqlS = $wpdb->prepare("SELECT * FROM $table_private WHERE rid = %d AND pid = %d AND status = 0 ORDER BY id DESC LIMIT 1", $session->rid, $session->uid);
		$pSession = $wpdb->get_row($sqlS);
		
		$match_available = get_post_meta( $postID, 'match_available', true );
		$client_available = boolval( $pSession->client ?? false );
		
		if ($options['debugMode']) if ($pSession) $room['welcome'] .= $client_available? 'Found booth P#' .$pSession->id . ' with'. ' ' . $pSession->client . ' U#' . $pSession->cid . ".\n" : 'Found empty booth.' . ".\n";
		//if ($options['debugMode']) $room['welcome'] .= 'Available for matching:' . $match_available . "\n";
		
		if (!$client_available && !$match_available && !$new) 
		{
			$new = true;
			$resetWatch = true;
			if ($options['debugMode']) $room['welcome'] .= 'Client not available and performer not available. Awkward. Creating new booth.. ' . print_r($pSession, true);
		}

		if ($resetWatch) 
		{
			self::resetWatch($session);
			$session = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_sessions WHERE id = %d", $session->id ) );		
		}
	
		$room['privateUID'] = $pSession->cid ?? 0;

		//booth created by performer
		if (!$pSession || $new)
		{
			$meta              = array();
			$meta['createdBy']     = 'appRandomRoom';
			$metaS             = serialize( $meta );
					
			$sqlI = $wpdb->prepare(
				"INSERT INTO `$table_private` ( `call`, `performer`, `pid`, `ps`, `rid`, `room`, `status`, `meta`, `psdate`, `pedate` ) VALUES ( %d, %s, %d, %d, %d, %s, %d, %s, %d, %d )",
				2, $session->username, $session->uid, $session->id, $postID, $post->post_title, 0, $metaS, $ztime, $ztime
			);
			$wpdb->query( $sqlI );
			$privateID = $wpdb->insert_id;

			$pSession = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_private WHERE id = %d", $privateID ) );		

			update_post_meta( $postID, 'match_available', '1' ); // mark as available for matching
			$room['privateUID'] = 0;
			
			$room['welcome']  .= 'Created booth.' . ' ';
		}
		
		//end broadcaster	
		}
		else
		{
			//client
					$sqlS = $wpdb->prepare("SELECT * FROM $table_private WHERE rid = %d AND cid = %d AND client = %s AND status = 0 ORDER BY id DESC LIMIT 1", $session->rid, $session->uid, $session->username);
					$pSession = $wpdb->get_row($sqlS);
					
					if (!$pSession)
					{
						$room['welcome']  .= 'Booth no longer available. Awkward. Use Next button! ' . ( $options['debugMode'] ? $sqlS : '' );

					}
					
					$room['privateUID'] = $pSession->pid;

					if ($options['debugMode']) $room['welcome'] .= 'You are client.'. "\n";
		}
			
		$room['name'] = sanitize_file_name( $post->post_title );

		// parent room mode
		$room['audioOnly'] = self::is_true( get_post_meta( $post->ID, 'room_audio', true ) );
		$room['textOnly']  = self::is_true( get_post_meta( $post->ID, 'room_text', true ) );



// parent room mode
		$room['audioOnly'] = self::is_true( get_post_meta( $post->ID, 'room_audio', true ) );
		$room['textOnly']  = self::is_true( get_post_meta( $post->ID, 'room_text', true ) );

		// get private session info
		$clientCPM = 0;


		if ( $pSession ) {
			// get session meta for private room
			if ( $pSession->meta ) {
				$pSessionMeta = unserialize( $pSession->meta );
			}
			if ( ! is_array( $pSessionMeta ) ) {
				$pSessionMeta = array();
			}

			// private mode defined?
			if ( array_key_exists( 'callType', $pSessionMeta ) ) {
				if ( $pSessionMeta['callType'] == 'audio' ) {
					$room['audioOnly'] = true;
				}
				if ( $pSessionMeta['callType'] == 'text' ) {
					$room['textOnly'] = true;
				}
			}

			if ( array_key_exists( 'clientCPM', $pSessionMeta ) ) {
				$clientCPM = $pSessionMeta['clientCPM'];
			}
		}

		// $appComplexity = ($options['appComplexity'] == '1' || ($session->broadcaster && $options['appComplexity'] == '2'));

		$room['screen'] = 'Way2Screen'; // private 2 way video screen
		if ( $room['audioOnly'] ) {
			$room['screen'] = 'Way2AudioScreen'; // 2 way audio only mode
		}
		if ( $room['textOnly'] ) {
			$room['screen'] = 'TextScreen'; // text only mode
		}

		if (!$session->broadcaster)  //client 2 way
		{
			if (!$options['private2Way']) $room['screen'] = 'PlaybackScreen'; //  1 way video 
			if ($options['private2Way'] == 'audio') if ($room['screen'] != 'TextScreen') $room['screen'] = 'AudioScreen'; //  2 way audio
		}

		$room['welcome']      .= sprintf( 'Welcome to match booth #%d of "%s", %s S#%d!', $pSession->id, sanitize_file_name( $post->post_title ), $session->username, $session->id );
		$room['welcomeImage'] = dirname( plugin_dir_url( __FILE__ ) ) . '/images/users2.png';

		if ( $session->broadcaster ) {
			$room['welcome'] .= "\n" . __( 'You are room provider.', 'ppv-live-webcams' );
		}

		if ( $room['audioOnly'] ) {
			$room['welcome'] .= "\n" . __( 'Chat Type', 'ppv-live-webcams' ) . ': ' . __( 'Audio chat', 'ppv-live-webcams' );
		}
		if ( $room['textOnly'] ) {
			$room['welcome'] .= "\n" . __( 'Chat Type', 'ppv-live-webcams' ) . ': ' . __( 'Text chat', 'ppv-live-webcams' );
		}

		// $room['welcome'] .= "\n Meta: " . $pSession->meta;

		if ( $clientCPM ) {
			$room['welcome'] .= "\n" . __( 'This is a paid private session:', 'ppv-live-webcams' );
			$room['welcome'] .= "\n - " . __( 'Cost', 'ppv-live-webcams' ) . ': ' . ' ' . $clientCPM . ' ' . htmlspecialchars( $options['currencypm'] );
			
			if ( $session->broadcaster ) 
			{
				$performerRatio = self::performerRatio( $post->post_title, $options, $post->ID );
				$room['welcome'] .= "\n - " . __( 'You get', 'ppv-live-webcams' ) . ': ' . ' ' . round( $clientCPM * $performerRatio , 2 ) . ' ' . htmlspecialchars( $options['currencypm'] ) . ' (' . ($performerRatio*100) . '%)';
			}

			if ( $options['ppvGraceTime'] ) {
				$room['welcome'] .= "\n - " . __( 'Charging starts after a grace time:', 'ppv-live-webcams' ) . ' ' . $options['ppvGraceTime'] . 's.';
			}
			
			$room['welcomeImage'] = dirname( plugin_dir_url( __FILE__ ) ) . '/images/cash.png';
		}

		if (!$pSession->cid && $session->broadcaster) $room['welcome'] .= "\n" . __( 'Waiting for a client to match.', 'ppv-live-webcams' );

		//session meta
		
		if ( $session->meta ) {
				$sessionMeta = unserialize( $session->meta );
			}
			if ( ! is_array( $sessionMeta ) ) {
				$sessionMeta = array();
			}
			
				
		// special private streams based on session ids (also works for visitors)
		$streamBroadcast = 'm' . $pSession->id . '_' . $session->id;
		$streamPlayback = '';

		// in 2w always receive broadcast keys
		$room['streamBroadcast'] = self::webrtcStreamQuery( $session->uid, $post->ID, 1, $streamBroadcast, $options, 0, $post->post_title, $privateUID ?? 0 );

		//matched
		if ($pSession && array_key_exists('watch', $sessionMeta)) 
		{
			if ($sessionMeta['watchNexted'] || $sessionMeta['watchLeft'])
			{
				 $room['welcome'] .= "\n " . 'Other stream is no longer available.';
			}
			else
			{
				$streamPlayback  = 'm' . $pSession->id . '_' . $sessionMeta['watch'];

				$room['streamPlayback']  = self::webrtcStreamQuery( $session->uid, $post->ID, 0, $streamPlayback, $options, 0, $post->post_title, $privateUID );
				$room['streamUID']       = intval(  $room['privateUID']  );
			}
		}
	
		$room['streamNameBroadcast'] = $streamBroadcast;
		$room['streamNamePlayback'] = $streamPlayback;
		
	
		if ( $options['debugMode'] ) $room['welcome'] .= "\n " . "Playback: $streamPlayback Broadcast: $streamBroadcast Session: " . $session->id . " privateUID: " . $room['privateUID']  ;
	
		$room['actionPrivate']      = false;
		$room['actionPrivateClose'] = true;

		$room['actionID'] = $actionID ?? 0;

		// configure tipping options for clients
		$room['tips'] = false;
		if ( $options['tips'] ) {
			if ( ! $session->broadcaster ) {

				$tipOptions = self::appTipOptions( $options );
				if ( count( $tipOptions ) ) {
					$room['tipOptions'] = $tipOptions;
					$room['tips']       = true;
					$room['tipsURL']    = dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/templates/messenger/tips/';
				}
			}
		}
		

		
		$room['mode'] = get_post_meta( $post->ID, 'groupMode', true );

		//room group mode for performer
		if ( $session->broadcaster && is_array( $options['groupModes'] ) )
		{
				
				$modes = [];
				
				foreach ( $options['groupModes'] as $groupMode => $modeParameters ) 
				{
				
					$groupMode = sanitize_text_field( $groupMode );
					$icon = 'user';
					
					if ( $modeParameters['cpm'] ?? false )	$icon = 'money';
					
					if ( $modeParameters['room_audio'] ?? false )	$icon = 'volume up';
					if ( $modeParameters['room_text'] ?? false )	$icon = 'comment alternate';
					if ( $modeParameters['group_disabled'] ?? false )	$icon = 'user md';		
					if ( $modeParameters['room_private'] ?? false )	$icon = 'user secret';
					if ( $modeParameters['room_random'] ?? false )	$icon = 'sync';
					if ( $modeParameters['room_conference'] ?? false )	$icon = 'users';
					$modes[ $groupMode ] = [ 'key' => sanitize_file_name($groupMode), 'name' => $groupMode, 'icon' => $icon ];
					
				}
				
				if ( count($modes) ) $room['modes'] = $modes;

		}
		

		if ($message) $room['welcome'] .= $message;

		$room['next'] = true;
		$room['nextTooltip'] = __( 'Next Match', 'ppv-live-webcams' );;

		$room['actionPrivateClose'] = false;

		//disable snapshots in private
		if (!$options['privateSnapshots']) $room['snapshotDisable'] = true;

		//file upload button
		 $room['filesUpload'] = self::appRole( $session->uid, 'filesUpload', boolval( $session->broadcaster ), $options );

		// custom buttons
		$actionButtons = array();

		return $room;
		
		///appRandomRoom

	}
	

	static function videowhisper_cam_instant( $atts ) {
		// Shortcode: Instant Cam Setup & Access

		if ( ! is_user_logged_in() ) {
			return '<br><div id="performerDashboardMessage" class="ui orange segment">' . __( 'Login is required to access your own videochat room!', 'ppv-live-webcams' ) . '<br><a class="ui button inverted primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'ppv-live-webcams' ) . '</a>  <a class="ui button inverted secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'ppv-live-webcams' ) . '</a></div>';
		}

		$options      = self::getOptions();
		$current_user = wp_get_current_user();

		// approval required?
		if ( ! self::userEnabled( $current_user, $options, 'Performer' ) ) {
			return $htmlCode . '<div id="performerDashboardMessage" class="ui yellow segment">' . __( 'Your account is not currently enabled. Update your account records and wait for site admins to approve your account.', 'ppv-live-webcams' ) . '</div>' . do_shortcode( '[videowhisper_account_records]' );
		}

		// disabled by studio: no access
		$disabled = get_user_meta( $current_user->ID, 'studioDisabled', true );
		if ( $disabled ) {
			return $htmlCode . '<div id="performerDashboardMessage" class="ui red segment">' . __( 'Studio disabled your account: dashboard access is forbidden. Contact studio or site administrator!', 'ppv-live-webcams' ) . '</div>';
		}

		// selected webcam?
		$postID = get_user_meta( $current_user->ID, 'currentWebcam', true );
		if ( $postID ) {
			$post = get_post( $postID );
		} if ( ! $post ) {
			$postID = 0;
		}

		// any owned webcam?
		if ( ! $postID ) {
			global $wpdb;
			$postID = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_author = %d and post_type = %s LIMIT 0,1', $current_user->ID, $options['custom_post'] ) );}
		if ( $postID ) {
			$post = get_post( $postID );
		} if ( ! $post ) {
			$postID = 0;
		}

		// create a room
		if ( ! $postID ) {
			$postID = self::webcamPost(); // default cam
		}
		if ( $postID ) {
			$post = get_post( $postID );
		} if ( ! $post ) {
			$postID = 0;
		}
		if ( ! $postID ) {
			return 'Error: Could not setup a webcam post!';
		}

		return do_shortcode( "[videowhisper_videochat webcam_id=\"$postID\"]" );

	}


	static function videowhisper_cam_random( $atts ) {
		
		// Shortcode: Random Cam Videochat
		$options = self::getOptions();

		$userID     = get_current_user_id(); // 0 if no user logged in
		$nextRoomID = self::nextRoomID( $userID, $options );

		if ( $nextRoomID ) {
			return do_shortcode( '[videowhisper_videochat webcam_id="' . $nextRoomID . '" title="' . 'Random Performer Room' . '"]' );
		} else {
			return __( 'No random cam room found with current criteria!', 'ppv-live-webcams' );
		}
	}


	static function videowhisper_cam_app( $atts ) {
		// Shortcode: HTML5 Videochat

		$stream  = '';
		$postID  = 0;
		$options = self::getOptions();
		$room = ''; 

		if ( is_single() ) {
			$postID = get_the_ID();
			if ( get_post_type( $postID ) == $options['custom_post'] ) {
				$room = get_the_title( $postID );
			} else {
				$postID = 0;
			}
		}

		if ( ! $room ) {
			$room = sanitize_text_field( $_GET['room'] ?? '' );
		}

		$atts = shortcode_atts(
			array(
				'room'      => $room,
				'webcam_id' => $postID,
				'silent'    => 0,
				'private'   => 0,
				'booth'     => 0,
				'session' 	=> 0,
				'call'      => '',
				'type'      => '', // /audio/text
				'title'     => '',
				'width'		=>'',
				'height'	=>'',

			),
			$atts,
			'videowhisper_cam_app'
		);

		if ( $atts['room'] ) {
			$room = $atts['room']; // parameter channel="name"
		}
		if ( $atts['webcam_id'] ) {
			$postID = $atts['webcam_id'];
		}

		$width = $atts['width'];
		if ( ! $width ) {
			$width = '100%';
		}
		$height = $atts['height'];
		if ( ! $height ) {
			$height = '360px';
		}

		$room = sanitize_file_name( $room );

		global $wpdb;


		//booth
		if ($atts['booth']) 
		{
			$booth = intval($atts['booth']);
			
			$table_private = $wpdb->prefix . 'vw_vmls_private';
			$sql           = $wpdb->prepare("SELECT * FROM `$table_private` WHERE id = %d", $booth);
			$private       = $wpdb->get_row( $sql );
			$postID = intval($private->rid);
		}



		// only room provided
		if ( ! $postID && $room ) {
			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type = %s LIMIT 0,1", sanitize_file_name( $room ), $options['custom_post'] ) );
		}

		$post = get_post( $postID );

		// only wecam_id provided
		if ( ! $room ) {
			if ( ! $post ) {
				return "VideoWhisper HTML5 App Error: Room not found! (#$postID)";
			}
			$room = sanitize_file_name( $post->post_title );
		}

		$htmlCode = "<!--VideoWhisper.com/PaidVideochat.com/videowhisper_cam_app/$room#$postID-->";

		$roomID   = $postID;
		$roomName = sanitize_file_name( $room );

		$userID = get_current_user_id(); // 0 if no user logged in

		$isPerformer = 0;
		$isModerator = 0;
		
		if ( $userID ) {
			$isPerformer = self::isPerformer( $userID, $roomID );

			$user = get_userdata( $userID );
			if ( $isPerformer ) {
				$userName = self::performerName( $user, $options );
			} else {
				$userName = self::clientName( $user, $options );
			}

			$isModerator = self::isModerator( $userID, $options, $user);

			// access keys
			if ( $user ) {
				$userkeys   = $user->roles;
				$userkeys[] = $user->user_login;
				$userkeys[] = $user->user_nicename;
				$userkeys[] = $user->ID;
				$userkeys[] = $user->user_email;
				$userkeys[] = $user->display_name;
			} else {
				$userkeys = array( 'Member' );
			}

			if ( $isPerformer ) {
				// performer publishing with HTML5 app - save info for responsive playback
				update_post_meta( $postID, 'performer', $userName );
				update_post_meta( $postID, 'performerUserID', $userID );

				update_post_meta( $postID, 'stream-protocol', 'rtsp' );
				update_post_meta( $postID, 'stream-type', 'webrtc' );
				update_post_meta( $postID, 'stream-mode', 'direct' );
				update_post_meta( $postID, 'roomInterface', 'html5app' );
			}
		} else {

			// use a cookie for visitor username persistence
			if ( $_COOKIE['htmlchat_username'] ?? false ) {
				$userName = sanitize_file_name( $_COOKIE['htmlchat_username'] );
			} else {
				$userName = 'G_' . base_convert( time() % 36 * wp_rand( 0, 36 * 36 ), 10, 36 );
				// setcookie('htmlchat_username', $userName); // set in init()
			}
			$isVisitor = 1;

			$userkeys = array( 'Guest' );
		}
		
		// private call
		$isCall = 0;
		if ( $_GET['call'] ?? false ) {
			$call = sanitize_text_field( $_GET['call'] );
			if ( $atts['call'] ) {
				$call = $atts['call'];
			}

			if ( ! $userID ) {
				return '<div class="ui segment red">' . __( 'Login is required to access private calls.', 'ppv-live-webcams' ) . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'ppv-live-webcams' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'ppv-live-webcams' ) . '</a>' . '</div>';
			}

			$privateID = self::to10( $call );

			$table_private = $wpdb->prefix . 'vw_vmls_private';
			$sql           = $wpdb->prepare("SELECT * FROM `$table_private` WHERE id = %d", $privateID);
			$private       = $wpdb->get_row( $sql );

			if ( $private ) {
				if ( $private->status > 0 ) {
					return '<div class="ui segment red">' . __( 'This private call was closed.', 'ppv-live-webcams' ) . '</div>';
				}
				if ( $private->pid != $userID && $private->cid != $userID ) {
					return '<div class="ui segment red">' . __( 'Private call is only available to performer and client, as setup.', 'ppv-live-webcams' ) . '</div>';
				}

				$isCall = 1;
				if ( $private->pid == $userID ) {
					$pJS = ', requestUID: ' . $private->cid . ", requestUsername: '" . $private->client . "'";
				} else {
					$pJS = ', requestUID: ' . $private->pid . ", requestUsername: '" . $private->performer . "'";
				}
			} else {
				return '<div class="ui segment red">' . __( 'This private call does not exist.', 'ppv-live-webcams' ) . '</div>';
			}
		}

		$callsOnly = self::is_true( get_post_meta( $postID, 'calls_only', true ) );
		if ( $callsOnly && ! $isPerformer && ! $isCall ) {
			return '<div class="ui segment red">' . __( 'This room can only be used for locked calls, with call link.', 'ppv-live-webcams' ) . '</div>';
		}

		// access control
		// check if banned
		$bans = get_post_meta( $postID, 'bans', true );
		if ( $bans ) {

			// clean expired bans
			foreach ( $bans as $key => $ban ) {
				if ( $ban['expires'] < time() ) {
					unset( $bans[ $key ] );
					$bansUpdate = 1;
				}
			}
			if ( $bansUpdate ?? false) {
				update_post_meta( $postID, 'bans', $bans );
			}

			$clientIP = self::get_ip_address();

			foreach ( $bans as $ban ) {
				if ( $clientIP == $ban['ip'] || ( $uid > 0 && $uid == $ban['uid'] ) ) {

					return '<div class="ui segment red">' . $response['error'] = __( 'You are banned from accessing this room!', 'ppv-live-webcams' ) . ' ' . $ban['by'] . ' : ' . date( DATE_RFC2822, $ban['expires'] ) . '</div>';

				}
			}
		}
		
		if ( self::roomSuspended( $postID, $options ) ) {
			return '<div class="ui segment red ' . $options['interfaceClass'] . '">' . __( 'This room is currently suspended.', 'ppv-live-webcams' ) . '</div>';
		}

		//restricted roles
		if ( !$isPerformer && isset( $user ) )
		{
			$roleRestricted = explode( ',', $options['roleRestricted'] );
			foreach ( $roleRestricted as $key => $value ) $roleRestricted[ $key ] = trim( $value );
			
			 if ( self::any_in_array( $roleRestricted, $user->roles ) ) return '<div class="ui segment red ' . $options['interfaceClass'] . '">' . __( 'Your role can not access other rooms.', 'ppv-live-webcams' )  . ' ('. implode(', ', $user->roles) .  ')</div>';
		}

		$canWatch  = $options['canWatch'];
		$watchList = $options['watchList'];

		if ( !$isPerformer && !$isCall && !$isModerator ) {

			switch ( $canWatch ) {
				case 'all':
					break;

				case 'members':
					if ( ! $userID ) {
						return '<div class="ui segment red ' . $options['interfaceClass'] . '">' . __( 'Login is required to access.', 'ppv-live-webcams' ) . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'ppv-live-webcams' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'ppv-live-webcams' ) . '</a>' . '</div>';
					}
					break;

				case 'list';

					if ( ! $userID ) {
						return '<div class="ui segment red">' . __( 'Login is required to access.', 'ppv-live-webcams' ) . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'ppv-live-webcams' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'ppv-live-webcams' ) . '</a>' . '</div>';
					}
					if ( ! self::inList( $userkeys, $watchList ) ) {
						return '<div class="ui segment red">' . __( 'You are not in the allowed client list configured from backend settings.', 'ppv-live-webcams' ) . '</div>';
					}
				break;
			}

			$accessList = get_post_meta( $postID, 'vw_accessList', true );
			if ( $accessList ) {
				if ( ! self::inList( $userkeys, $accessList ) ) {
					return '<div class="ui segment red">' . __( 'This room is restricted by access list. You are not in room access list.', 'ppv-live-webcams' ) . '</div>';
				}
			}
		}
		
		
		// create a session
		 if ($atts['session']) {
			 $sessionID = intval($atts['session']);
			 
				global $wpdb;
				$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

				$sqlS    = $wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d AND status=0 LIMIT 1", $sessionID);
				$session = $wpdb->get_row( $sqlS );
				
				if (!$session) return '<div class="ui segment red">Specified session is no longer valid. Disable cache or troubleshoot database. (' . $sessionID . ')</div>';
			 
		 }
		 else $session   = self::sessionUpdate( $userName, $roomName, $isPerformer, 11, 0, 1, 1, $options, $userID, $roomID, self::get_ip_address(), [ 'created_by' => 'videowhisper_cam_app' ] );
		 
	//	 echo "[ $userName, $roomName, $isPerformer, 11, 0, 1, 1, $options, $userID, $roomID ]";

		if ( is_object($session) )
				$sessionID = $session->id;
		else {
			$sessionID = 0;
			$htmlCode .= '<div class="ui segment red">Error: Session could not be created: ' . "sessionUpdate($userName, $roomName, $isPerformer, 11, 0, 1, 1, $options, $userID, $roomID, ". self::get_ip_address() . ")<br> $session</div>";
		}

		// var_dump($session);
		// echo("($userName, $roomName, $isPerformer, 11, 1, 1, 1, $options, $userID, $roomID)");

		$wlJS = '';
		if ( $options['whitelabel'] ) {
			$wlJS = ', checkWait: true, whitelabel: ' . $options['whitelabel'];
		}

		// instant private show / call request
		$isCallInstant = 0;
		if ( ! $isCall ) {
			$pJS = '';
			if ( $atts['private'] ) {

				$performer   = get_post_meta( $postID, 'performer', true );
				$performerID = intval( get_post_meta( $postID, 'performerUserID', true ) );

				$pJS           = ', requestUID: ' . $performerID . ", requestUsername: '" . $performer . "'";
				$isCallInstant = 1;

				$callType  = $atts['type'];
				$clientCPM = self::clientCPM( $roomName, $options, $roomID, $callType );

				// create private session
				$meta = array(
					'time'      => time(),
					'username'  => $userName,
					'callMode'  => 'demand',
					'callType'  => $callType,
					'clientCPM' => strval( $clientCPM ),
				);

				$metaS     = serialize( $meta );
				$room_name = $post->post_name;

				$ztime = time();

				$table_private = $wpdb->prefix . 'vw_vmls_private';
				$sql = $wpdb->prepare(
					"INSERT INTO `$table_private` ( `call`, `performer`, `pid`, `client`, `cid`, `rid`, `room`, `status`, `meta`, `psdate`, `pedate`, `csdate`, `cedate` ) VALUES ( %d, %s, %d, %s, %d, %d, %s, %d, %s, %d, %d, %d, %d )",
					0, $performer, $performerID, $userName, $userID, $roomID, $roomName, 0, $metaS, 0, 0, $ztime, $ztime
				);
				$wpdb->query( $sql );
				$privateID = $wpdb->insert_id;

				$htmlCode .= "<!--VideoWhisper HTML5 Videochat - Private #$privateID : $callType / $clientCPM ( $sql )" . $wpdb->last_error . '-->';
			}
		}

		$group_disabled = self::is_true( get_post_meta( $postID, 'group_disabled', true ) );
		if ( $callsOnly && ! $isPerformer && ! $isCall && ! $isCallInstant ) {
			return '<div class="ui segment red">' . __( 'Group chat is disabled. This room can only be used for instant or locked calls (with call link).', 'ppv-live-webcams' ) . '</div>';
		}

		$ajaxurl   = admin_url() . 'admin-ajax.php?action=vmls_app'; //wp ajax server
		$serverURL2 = plugins_url('ppv-live-webcams/server/') ; //fast server
		
		$sessionKey = 'VideoWhisper';
		
		$pDev = (VW_H5V_DEVMODE ? ', devMode: true' : '');

		$modeVersion = trim($options['modeVersion'] ?? '');
		
		$dataCode = "window.VideoWhisper = {userID: $userID, sessionID: $sessionID, sessionKey: '$sessionKey', roomID: $roomID, performer: $isPerformer, userName: '$userName', roomName: '$roomName', serverURL: '$ajaxurl', serverURL2: '$serverURL2', modeVersion: '$modeVersion' $wlJS $pJS $pDev}";

		//
		// wp_enqueue_style( 'fomantic-ui', dirname(plugin_dir_url(  __FILE__ )) . '/scripts/semantic/semantic.min.css');
		wp_enqueue_script( 'fomantic-ui', dirname( plugin_dir_url( __FILE__ ) ) . '/scripts/semantic/semantic.min.js', array( 'jquery' ) );

		$k = 0;
		$CSSfiles = scandir( dirname( dirname( __FILE__ ) ) . '/static/css/' );
		foreach ( $CSSfiles as $filename ) {
			if ( strpos( $filename, '.css' ) && ! strpos( $filename, '.css.map' ) ) {
				wp_enqueue_style( 'vw-h5vcams-app' . ++$k, dirname( plugin_dir_url( __FILE__ ) ) . '/static/css/' . $filename );
			}
		}

		$countMain = 0;
		$countRuntime  = 0;
		$JSfiles       = scandir( dirname( dirname( __FILE__ ) ) . '/static/js/' );
		foreach ( $JSfiles as $filename ) {
			if ( strpos( $filename, '.js' ) && ! strpos( $filename, '.js.map' ) && ! strpos( $filename, '.txt' ) ) {
				wp_enqueue_script( 'vw-h5vcams-app' . ++$k, dirname( plugin_dir_url( __FILE__ ) ) . '/static/js/' . $filename, array(), '', true );

				if ( ! strstr( $filename, 'LICENSE.txt' ) ) {
					if ( substr( $filename, 0, 5 ) == 'main.' ) {
						$countMain++;
					}
				}
				if ( ! strstr( $filename, 'LICENSE.txt' ) ) {
					if ( substr( $filename, 0, 7 ) == 'runtime' ) {
						$countRuntime++;
					}
				}
			}
		}

		if ( $countMain > 1 || $countRuntime > 1 ) {
			$htmlCode .= '<div class="ui segment red">Warning: Possible duplicate JS files in application folder! Only latest versions should be deployed.</div>';
		}

		$cssCode = html_entity_decode( stripslashes( $options['appCSS'] ?? '' ) );

		if ($options['debugMode']) 
		{
			$htmlCode .= '<!--VideoWhisper.com debug info: videowhisper_cam_app shortcode atts=' . serialize($atts) .  '-->';
		}

		$htmlCode .= <<<HTMLCODE
<!--VideoWhisper.com - HTML5 Videochat web app - p:$isPerformer uid:$userID postID:$postID r:$room s:$sessionID-->
<noscript>You need to enable JavaScript to run this app. For more details see <a href="https://paidvideochat.com/html5-videochat/">HTML5 Videochat</a> or <a href="https://videowhisper.com/">contact HTML5 Videochat developers</a>.</noscript>
<div id="videowhisperAppContainer"><div id="videowhisperVideochat"></div></div>
<script>$dataCode;
document.cookie = "html5videochat=DoNotCache";
</script>
<style>

#videowhisperAppContainer
{
display: block;
min-height: 725px;
height: inherit;
background-color: #eee;
position: relative;
z-index: 102 !important;
}

#videowhisperVideochat
{
display: block;
width: 100%;
height: 100%;
position: absolute;
z-index: 102 !important;
}

$cssCode
</style>
HTMLCODE;

		// location.hash = "#vws-room";

		if ($isPerformer) if ($options['lovense'])
		{
			//lovense broadcaster https://developer.lovense.com/docs/cam-solutions/cam-extension-for-chrome.html#step-2-integration 
			wp_enqueue_script( 'lovense-api-broadcast', 'https://api.lovense-api.com/cam-extension/static/js-sdk/broadcast.js', array(), '', false ); //lovense api integration, per integration specs

//documentation and feedback not matching: 
$receiveTipCall = 'camExtension.receiveTip(amount, "' . $userName . '", clientName, "VideoWhisper");';
if ($options['lovenseTipParams'] == 3) $receiveTipCall = 'camExtension.receiveTip(amount, clientName, "VideoWhisper");';
if ($options['lovenseTipParams'] == 2) $receiveTipCall = 'camExtension.receiveTip(amount, clientName);';

//chatNotification(message)
//chatServer(action, data): serverUpdate{ 'task': 'externaljs', 'action': action, 'data': data }

//lovense reset when entering room
//if ($options['lovense'] && $options['lovenseToy'] =='auto' ) update_post_meta( $postID, 'lovenseToy', 0 );

			$htmlCode .= '
<SCRIPT>
			jQuery(document).ready(function(){
				
			  const camExtension = new CamExtension(\'' . $options['lovensePlatform'] . '\', \'' . $userName . '\')
			  
				camExtension.on(\'ready\',async function(ce) {
					const version = await ce.getCamVersion();
					console.log("Lovense Broadcast", version);
					
					if (typeof window.VideoWhisper.chatNotification != "undefined" ) window.VideoWhisper.chatNotification("Lovense " + version);
				})
  
  				 camExtension.on("postMessage", (data) => {
				  // Process the data which to be sent
				  // Send the data to chat room
				 	console.log("Lovense Broadcast postMessage", data);
				 	
				 	if (typeof window.VideoWhisper.chatNotification != "undefined" ) window.VideoWhisper.chatNotification("Lovense postMessage: " + data);
				})
  
			    camExtension.on("toyStatusChange", (data) => {
					console.log("Lovense Broadcast toyStatusChange", data);
					if (typeof window.VideoWhisper.chatNotification != "undefined" ) window.VideoWhisper.chatNotification("Lovense toyStatusChange: " + data.status + " " + data.name + " " + data.type);

					if (typeof window.VideoWhisper.chatServer != "undefined" ) window.VideoWhisper.chatServer("toyStatusChange", data);

			      // Handle toy information data
			      // data = [{
			      //      id:"d6c35fe83348",
			      //      name:"toy name",
			      //      type:"lush",
			      //      status:"on",
			      //      version:"",
			      //      battery:80
			      // }]			      
			      
			  })
 
				 camExtension.on("tipQueueChange", (data) => {
				      //  handle queue information data
				      //  data = {
				      //      running: [ ],
				      //      queue: [ ],
				      //      waiting: [ ]
				      //  }
				   	 
				   	 console.log("Lovense Broadcast tipQueueChange", data);
   
				  })
				  
				 camExtension.on("settingsChange", (data) => {
				      //  handle configuration information data
				      //  data = {
				      //      levels:{},
				      //      special:{},
				      //  }
				   	  
				   	  console.log("Lovense Broadcast settingsChange", data);				      
				  })  
				 
				 
				window.VideoWhisper.performerTip = function(amount, clientName)
				{
					//performer received a tip  
					console.log("window.performerTip", amount, clientName);
					' . $receiveTipCall . '
			        if (typeof window.VideoWhisper.chatNotification != "undefined" ) window.VideoWhisper.chatNotification("Lovense " + clientName + ": " + amount);				
				}
				     
			  })  
</SCRIPT>
			';
		}


		$vwtemplate = '';
		if ( array_key_exists( 'vwtemplate', $_GET ) ) {
			$vwtemplate = sanitize_text_field( $_GET['vwtemplate'] );
		}
		
		if ( $vwtemplate != 'app' && $options['postTemplate'] != '+app' ) {
			$htmlCode .= '<div class="ui form"><a class="ui button secondary fluid" href="' . add_query_arg( array( 'vwtemplate' => 'app' ), get_permalink( $postID ) ) . '"><i class="window maximize icon"></i> ' . __( 'Open in Full Page', 'ppv-live-webcams' ) . '</a></div>';
		}

		$state = 'block';
		if ( ! $options['videowhisper'] ) {
			$state = 'none';
		}
		$htmlCode .= '<div id="VideoWhisper" style="display: ' . $state . ';"><p>Powered by <a href="https://videowhisper.com">VideoWhisper Live Video Site Builder</a> / <a href="https://paidvideochat.com/html5-videochat/">HTML5 Videochat</a>.</p></div>';

		return $htmlCode;
	}

	static function nextRoomID( $userID, $options = null, $slots_required = 0 ) {
		// random videochat, returns next room or 0 if not found with configured criteria

		if ( ! $options ) {
			$options = self::getOptions();
		}

		global $wpdb;
		$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';
		
		self::billSessions($userID);

		$args = array(
			'post_type'      => $options['custom_post'],
			'post_status'    => 'publish',
			'posts_per_page' => 32,
			'offset'         => 0,
			'order'          => 'DESC',
		);

		if ( $userID ) {
			$args['author__not_in'] = array( $userID );
		}

		// order
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = 'edate';

		// not if private shows
		$args['meta_query']['public'] = array(
			'key'   => 'privateShow',
			'value' => '0',
		);

		// hide private rooms
		$args['meta_query']['room_private'] = array(
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
		
		// free rooms (always for visitors)
		if ( ! $options['videochatNextPaid'] || ! $userID ) {
			$args['meta_query']['groupCPM'] = array(
				'relation' => 'OR',
				array(
					'key'   => 'groupCPM',
					'value' => '0',
				),
				array(
					'key'     => 'groupCPM',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( $options['videochatNextPaid'] == '2' && $userID ) {
			$args['meta_query']['groupCPM'] = array(
				'key'     => 'groupCPM',
				'value'   => '0',
				'compare' => '>',
			);
		}

		if ( $options['videochatNextOnline'] ) {
			$args['meta_query']['online'] = array(
				'key'     => 'edate',
				'value'   => time() - 40,
				'compare' => '>',
			);
		}

		$nextRoomID = 0; // no room found
		$postslist  = get_posts( $args );
		$sql        = '';

		if ( count( $postslist ) > 0 ) {

			$roomAccessed = array();

			foreach ( $postslist as $item ) {
				$roomAccessed[ $item->ID ] = 0;
			}

			$rIDs = implode( ', ', array_keys( $roomAccessed ) );

			$sql = $wpdb->prepare("SELECT DISTINCT(rid), edate FROM $table_sessions WHERE uid = %d AND rid IN (%s) AND status = 0 ORDER BY edate DESC", $userID, $rIDs);
			$accesses = $wpdb->get_results( $sql );

			if ( $wpdb->num_rows > 0 ) {
				foreach ( $accesses as $access ) {
					$roomAccessed[ $access->rid ] = $access->edate;
				}
			}

				asort( $roomAccessed );
			// $response['nextRoomAccessed'] = $roomAccessed;

			// access next room as client
			// skip room if performer there
			foreach ( $roomAccessed as $iRoomID => $accessTime ) {
				$isPerformer = self::isPerformer( $userID, $iRoomID );

				if ( ! $isPerformer ) {
					$nextRoomID = intval( $iRoomID );
				}
				if ( ! $isPerformer ) {
					break;
				}
			}
		}

		if ( ! $nextRoomID && $options['debugMode'] ) {
			echo 'debugMode enabled: no valid random rooms found / ' . esc_html( $sql ) . '<br>';
			var_dump( $args );
			var_dump( $postslist );
		}

		return $nextRoomID;
	}


	static function humanDuration( $seconds = 0 ) {
		
		if (!$seconds) return '0s';
		
		$datetime1 = date_create( '@0' );
		$datetime2 = date_create( "@$seconds" );
		$interval  = date_diff( $datetime1, $datetime2 );

		if ( $interval->y >= 1 ) {
			$thetime[] = $interval->y . 'yr';
		}
		if ( $interval->m >= 1 ) {
			$thetime[] = $interval->m . 'mo';
		}
		if ( $interval->d >= 1 ) {
			$thetime[] = $interval->d . 'd';
		}
		if ( $interval->h >= 1 ) {
			$thetime[] = $interval->h . 'h';
		}
		if ( $interval->i >= 1 ) {
			$thetime[] = $interval->i . 'm';
		}
		if ( $interval->s >= 1 ) {
			$thetime[] = $interval->s . 's';
		}

		return isset( $thetime ) ? implode( ' ', $thetime ) . ( $interval->invert ? ' ' . __( 'ago', 'ppv-live-webcams' ) : '' ) : null;
	}


	// ! App
	// app

	static function appSfx() {
		// sound effects sources

		$base = dirname( plugin_dir_url( __FILE__ ) ) . '/sounds/';

		return array(
			'message' => $base . 'message.mp3',
			'hello'   => $base . 'hello.mp3',
			'leave'   => $base . 'leave.mp3',
			'call'    => $base . 'call.mp3',
			'warning' => $base . 'warning.mp3',
			'error'   => $base . 'error.mp3',
			'buzz'    => $base . 'buzz.mp3',
		);
	}


	static function appText() {
		 // implement translations

		// returns texts
		return array(
			'Send'                                   => __( 'Send', 'ppv-live-webcams' ),
			'Type your message'                      => __( 'Type your message', 'ppv-live-webcams' ),

			'Wallet'                                 => __( 'Wallet', 'ppv-live-webcams' ),
			'Balance'                                => __( 'Balance', 'ppv-live-webcams' ),
			'Pending Balance'                        => __( 'Pending Balance', 'ppv-live-webcams' ),
			'Session Time'                           => __( 'Session Time', 'ppv-live-webcams' ),
			'Session Cost'                           => __( 'Session Cost', 'ppv-live-webcams' ),

			'Record'                                 => __( 'Record', 'ppv-live-webcams' ),
			'Start'                                  => __( 'Start', 'ppv-live-webcams' ),
			'Stop'                                   => __( 'Stop', 'ppv-live-webcams' ),
			'Discard'                                => __( 'Discard', 'ppv-live-webcams' ),
			'Download'                               => __( 'Download', 'ppv-live-webcams' ),
			'Uploading. Please wait...'              => __( 'Uploading. Please wait...', 'ppv-live-webcams' ),

			'Chat'                                   => __( 'Chat', 'ppv-live-webcams' ),
			'Camera'                                 => __( 'Camera', 'ppv-live-webcams' ),
			'Users'                                  => __( 'Users', 'ppv-live-webcams' ),
			'Options'                                => __( 'Options', 'ppv-live-webcams' ),
			'Files'                                  => __( 'Files', 'ppv-live-webcams' ),
			'Presentation'                           => __( 'Presentation', 'ppv-live-webcams' ),

			'Tap for Sound'                          => __( 'Tap for Sound', 'ppv-live-webcams' ),
			'Enable Audio'                           => __( 'Enable Audio', 'ppv-live-webcams' ),
			'Mute'                                   => __( 'Mute', 'ppv-live-webcams' ),
			'Reload'                                 => __( 'Reload', 'ppv-live-webcams' ),
			'Ignore'                                 => __( 'Ignore', 'ppv-live-webcams' ),

			'Packet Loss: Download Connection Issue' => __( 'Packet Loss: Download Connection Issue', 'ppv-live-webcams' ),
			'Packet Loss: Upload Connection Issue'   => __( 'Packet Loss: Upload Connection Issue', 'ppv-live-webcams' ),

			'Broadcast'                              => __( 'Broadcast', 'ppv-live-webcams' ),
			'Stop Broadcast'                         => __( 'Stop Broadcast', 'ppv-live-webcams' ),
			'Make a selection to start!'             => __( 'Make a selection to start!', 'ppv-live-webcams' ),

			'Gift'                                   => __( 'Gift', 'ppv-live-webcams' ),
			'Gifts'                                  => __( 'Gifts', 'ppv-live-webcams' ),

			'Lights On'                              => __( 'Lights On', 'ppv-live-webcams' ),
			'Dark Mode'                              => __( 'Dark Mode', 'ppv-live-webcams' ),
			'Picture in Picture'                     => __( 'Picture in Picture', 'ppv-live-webcams' ),

			'Enter Fullscreen'                       => __( 'Enter Fullscreen', 'ppv-live-webcams' ),
			'Exit Fullscreen'                        => __( 'Exit Fullscreen', 'ppv-live-webcams' ),

			'Site Menu'                              => __( 'Site Menu', 'ppv-live-webcams' ),

			'Request Private'                        => __( 'Request Private', 'ppv-live-webcams' ),
			'Private'                                => __( 'Private', 'ppv-live-webcams' ),
			'Request Private Session'                => __( 'Request Private Session', 'ppv-live-webcams' ),
			'Performer Disabled Private Requests'    => __( 'Performer Disabled Private Requests', 'ppv-live-webcams' ),
			'Performer is Busy in Private'           => __( 'Performer is Busy in Private', 'ppv-live-webcams' ),
			'Performer is Not Online'                => __( 'Performer is Not Online', 'ppv-live-webcams' ),
			'Nevermind'                              => __( 'Nevermind', 'ppv-live-webcams' ),
			'Accept'                                 => __( 'Accept', 'ppv-live-webcams' ),
			'Decline'                                => __( 'Decline', 'ppv-live-webcams' ),
			'Close Private'                          => __( 'Close Private', 'ppv-live-webcams' ),

			'Next'                                   => __( 'Next', 'ppv-live-webcams' ),
			'Next: Random Videochat Room'            => __( 'Next: Random Videochat Room', 'ppv-live-webcams' ),

			'Name'                                   => __( 'Name', 'ppv-live-webcams' ),
			'Size'                                   => __( 'Size', 'ppv-live-webcams' ),
			'Age'                                    => __( 'Age', 'ppv-live-webcams' ),
			'Upload: Drag and drop files here, or click to select files' => __( 'Upload: Drag and drop files here, or click to select files', 'ppv-live-webcams' ),
			'Uploading. Please wait...'              => __( 'Uploading. Please wait...', 'ppv-live-webcams' ),
			'Open'                                   => __( 'Open', 'ppv-live-webcams' ),
			'Delete'                                 => __( 'Delete', 'ppv-live-webcams' ),

			'Media Displayed'                        => __( 'Media Displayed', 'ppv-live-webcams' ),
			'Remove'                                 => __( 'Remove', 'ppv-live-webcams' ),
			'Default'                                => __( 'Default', 'ppv-live-webcams' ),
			'Empty'                                  => __( 'Empty', 'ppv-live-webcams' ),

			'Profile'                                => __( 'Profile', 'ppv-live-webcams' ),
			'Show'                                   => __( 'Show', 'ppv-live-webcams' ),

			'Private Call'                           => __( 'Private Call', 'ppv-live-webcams' ),
			'Exit'                                   => __( 'Exit', 'ppv-live-webcams' ),

			'External Broadcast'                     => __( 'External Broadcast', 'ppv-live-webcams' ),
			'Not Available'                          => __( 'Not Available', 'ppv-live-webcams' ),
			'Streaming'                              => __( 'Streaming', 'ppv-live-webcams' ),
			'Closed'                                 => __( 'Closed', 'ppv-live-webcams' ),
			'Use after ending external broadcast, to faster restore web based webcam interface.' => __( 'Use after ending external broadcast, to faster restore web based webcam interface.', 'ppv-live-webcams' ),

			'Add'                                    => __( 'Add', 'ppv-live-webcams' ),
			'Complete'                               => __( 'Complete', 'ppv-live-webcams' ),
		);
	}


	static function appTipOptions( $options = null ) {

		$tipOptions = stripslashes( $options['tipOptions'] );
		if ( $tipOptions ) {
			$p = xml_parser_create();
			xml_parse_into_struct( $p, trim( $tipOptions ), $vals, $index );
			$error = xml_get_error_code( $p );
			xml_parser_free( $p );

			if ( is_array( $vals ) ) {
				return $vals;
			}
		}

		return array();

	}


	static function time2age( $time ) {
		$ret = '';

		$seconds = time() - $time;

		$days = intval( intval( $seconds ) / ( 3600 * 24 ) );
		if ( $days ) {
			$ret .= $days . 'd ';
		}
		if ( $days > 0 ) {
			return $ret;
		}

		$hours = intval( intval( $seconds ) / 3600 ) % 24;
		if ( $days || $hours ) {
			$ret .= $hours . 'h ';
		}

		if ( $hours > 0 ) {
			return $ret;
		}

		$minutes = intval( intval( $seconds ) / 60 ) % 60;
		if ( $minutes > 3 ) {
			$ret .= $minutes . 'm';
		} else {
			$ret .= __( 'New', 'ppv-live-webcams' );
		}

		return $ret;
	}

	static function appRoomFiles( $room, $options ) {

		$files = array();
		if ( ! $room ) {
			return $files;
		}

		$dir = $options['uploadsPath'];
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}
		$dir .= "/$room";
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}

		$handle = opendir( $dir );
		while ( ( $file = readdir( $handle ) ) !== false ) {
			if ( ( $file != '.' ) && ( $file != '..' ) && ( ! is_dir( "$dir/" . $file ) ) ) {
				$files[] = array(
					'name' => $file,
					'size' => intval( filesize( "$dir/$file" ) ),
					'age'  => self::time2age( $ftime = filemtime( "$dir/$file" ) ),
					'time' => intval( $ftime ),
					'url'  => self::path2url( "$dir/$file" ),
				);
			}
		}
		closedir( $handle );

		return $files;
	}


	static function is_true( $val, $return_null = false ) {
		$boolval = ( is_string( $val ) ? filter_var( $val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : (bool) $val );
		return $boolval === null && ! $return_null ? false : $boolval;
	}

	static function language2flag($lang)
			 {
				 $lang = strtolower($lang);

				 $flags = [ 'en-us'=>'us', 'en-gb'=>'gb', 'pt-br'=> 'br', 'pt-pt' => 'pt','zh'=>'cn', 'ja' => 'jp', 'el' => 'gr', 'da' => 'dk', 'en' => 'us', 'nb'=>'no', 'ko' => 'kr' ];
				 if ( array_key_exists($lang, $flags ) ) return $flags[ $lang ];

				 //use second part to select proper flag where it contains -
				 $parts = explode('-', $lang);
				 if (count($parts) > 1) $lang = $parts[1];

				 return $lang;
			 }
	
	static function languageField($userID, $options)
	{
		
		$langs = [];

		$languages = get_option( 'VWdeepLlangs' );
		if ($languages)
		{
			
			 foreach ($languages as $lang => $label) $langs []= ['value' => $lang, 'flag' => self::language2flag( $lang ), 'key' => $lang, 'text' => $label];
		}
		else $langs []= ['value' => 'en-us', 'flag' => 'us', 'key' => 'en-us', 'text' => 'English US'];
	 
			 	$h5v_language = get_user_meta( $userID, 'h5v_language', true );
				if (!$h5v_language) $h5v_language = $options['languageDefault'];
			 	if (!$h5v_language) $h5v_language = 'en-us';

	 			return [
	 			'name'        => 'h5v_language',
				'description' => __( 'Chat Language', 'ppv-live-webcams' ),
				'details'     => __( 'Language you will be writing in chat and would prefer to read.', 'ppv-live-webcams' ),
				'type'        => 'dropdown',
				'value'       => $h5v_language,
				'flag'		  => self::language2flag($h5v_language),
				'options'     => $langs,	 			
	 			];


	}
			
	static function appUserOptions( $session, $options ) {
		
		$h5v_language = get_user_meta( $session->uid, 'h5v_language', true );
		if (!$h5v_language) $h5v_language = $options['languageDefault'];
		if (!$h5v_language) $h5v_language = 'en-us';

		//detect mobile
		$mobile = false;
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$mobile = preg_match('/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i', $_SERVER['HTTP_USER_AGENT']);
		}
		
		return array(
			'h5v_language'      => $h5v_language,
			'h5v_flag'      	=> self::language2flag( $h5v_language ),	
			'h5v_sfx'           => $session->uid ? self::is_true( get_user_meta( $session->uid, 'h5v_sfx', true ) ) : true,
			'h5v_audio'         => $session->uid ? self::is_true( get_user_meta( $session->uid, 'h5v_audio', true ) ) : false,
			'h5v_dark'          => $session->uid ? self::is_true( get_user_meta( $session->uid, 'h5v_dark', true ) ) : false,
			'h5v_pip'           => $session->uid ? self::is_true( get_user_meta( $session->uid, 'h5v_pip', true ) ) : false,
			'h5v_min'           => $mobile && $options['appMobileMinimalist'] ? true : self::is_true( get_user_meta( $session->uid, 'h5v_min', true ) ),
			'h5v_reveal'        => $session->uid ? self::is_true( get_user_meta( $session->uid, 'h5v_reveal', true ) ) : false,
			'h5v_reveal_warmup' => intval( get_user_meta( $session->uid, 'h5v_reveal_warmup', true ) ),
		);
	}


	static function appRoomOptions( $post, $session, $options ) {
		$configuration = array();

		if ( ! $options['appOptions'] ) {
			return $configuration;
		}

		if ( $session->broadcaster ) {

			$fields = array(
				'requests_disable' => array(
					'name'        => 'requests_disable',
					'description' => __( 'Disable Call Requests', 'ppv-live-webcams' ),
					'details'     => __( 'Disable users from sending private call requests to room owner.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => self::is_true( get_post_meta( $post->ID, 'requests_disable', true ) ),
				),
				'room_private'     => array(
					'name'        => 'room_private',
					'description' => __( 'Not Public', 'ppv-live-webcams' ),
					'details'     => __( 'Hide room from public listings. Can be accessed by room link.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => self::is_true( get_post_meta( $post->ID, 'room_private', true ) ),
				),

				'calls_only'       => array(
					'name'        => 'calls_only',
					'description' => __( 'Calls Only', 'ppv-live-webcams' ),
					'details'     => __( 'Call Only mode: can only be used for calls.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => self::is_true( get_post_meta( $post->ID, 'calls_only', true ) ),
				),
				
					'room_random'     => array(
					'name'        => 'room_random',
					'description' => __( 'Random Calls', 'ppv-live-webcams' ),
					'details'     => __( 'Random 2 way calls mode.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => self::is_true( get_post_meta( $post->ID, 'room_random', true ) ),
				),
				
				'group_disabled'   => array(
					'name'        => 'group_disabled',
					'description' => __( 'Group Chat Disabled', 'ppv-live-webcams' ),
					'details'     => __( 'Use room only for instant or locked calls.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => self::is_true( get_post_meta( $post->ID, 'group_disabled', true ) ),
				),

				'room_audio'       => array(
					'name'        => 'room_audio',
					'description' => __( 'Audio Only', 'ppv-live-webcams' ),
					'details'     => __( 'Audio only room mode: Only microphone, no webcam video, for all participants. Applies both to group and private calls. Disables video calls.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => $audio = self::is_true( get_post_meta( $post->ID, 'room_audio', true ) ),
				),

				'room_text'        => array(
					'name'        => 'room_text',
					'description' => __( 'Text Only', 'ppv-live-webcams' ),
					'details'     => __( 'Text only room mode: Only text, for all participants. Applies both to group and private calls. Disables video and audio calls.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => $audio = self::is_true( get_post_meta( $post->ID, 'room_text', true ) ),
				),

				'room_conference'  => array(
					'name'        => 'room_conference',
					'description' => __( 'Conference Mode', 'ppv-live-webcams' ),
					'details'     => __( 'Enable owner to show multiple users streams at same time in split view. All users can publish webcam.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => $conference = self::is_true( get_post_meta( $post->ID, 'room_conference', true ) ),
				),

				'conference_auto'  => array(
					'name'        => 'conference_auto',
					'description' => __( 'Conference Auto Display', 'ppv-live-webcams' ),
					'details'     => __( 'Display users automatically in available conference slots when they start their camera, in conference mode.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => $conference_auto = self::is_true( get_post_meta( $post->ID, 'conference_auto', true ) ),
				),
				

			);

			$collaboration = self::collaborationMode( $post->ID, $options );
			$room_slots    = get_post_meta( $post->ID, 'room_slots', true );
			if ( ! $room_slots ) {
				$room_slots = 1;
			}
			if ( $collaboration || $conference ) {
				$fields['room_slots'] = array(
					'name'        => 'room_slots',
					'description' => __( 'Display Slots', 'ppv-live-webcams' ),
					'details'     => __( 'Split display to show multiple media items, in conference mode.', 'ppv-live-webcams' ),
					'type'        => 'dropdown',
					'value'       => $room_slots,
					'options'     => array(
						array(
							'value' => '1',
							'text'  => '1',
						),
						array(
							'value' => '2',
							'text'  => '2',
						),
						array(
							'value' => '4',
							'text'  => '4',
						),
						array(
							'value' => '6',
							'text'  => '6',
						),
					),
				);
			}

			// allow collaboration toggle based on room features  $options['presentationMode']
			
				$current_user = get_userdata( $session->uid );
				if ( $current_user ) {
					$userkeys   = $current_user->roles;
					$userkeys[] = $current_user->user_login;
					$userkeys[] = $current_user->user_nicename;
					$userkeys[] = $current_user->ID;
					$userkeys[] = $current_user->user_email;
				}

				if ( self::inList( $userkeys, $options['presentationMode'] ) ) {
					$fields['vw_presentationMode'] = array(
						'name'        => 'vw_presentationMode',
						'description' => __( 'Collaboration Mode', 'ppv-live-webcams' ),
						'details'     => __( 'Enable collaboration mode with multiple media, files, presentation.', 'ppv-live-webcams' ),
						'type'        => 'toggle',
						'value'       => self::is_true( get_post_meta( $post->ID, 'vw_presentationMode', true ) ),
					);
				}

			// party options
			$fields['party'] = array(
				'name'        => 'party',
				'description' => __( 'Party Mode in Random Chat', 'ppv-live-webcams' ),
				'details'     => __( 'In party mode, all users that join your room are added to party squad. Entire party moves to different room with Next button, in random chat. ', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $party = self::is_true( get_post_meta( $post->ID, 'party', true ) ),
			);

			if ( $party ) {
				$fields['party_reserved'] = array(
					'name'        => 'party_reserved',
					'description' => __( 'Party Reserved', 'ppv-live-webcams' ),
					'details'     => __( 'Number of reserved party video slots, needs to be available in other room, to travel.', 'ppv-live-webcams' ),
					'type'        => 'dropdown',
					'value'       => $party_reserved,
					'options'     => array(
						array(
							'value' => '0',
							'text'  => 'None',
						),
						array(
							'value' => '1',
							'text'  => '1',
						),
						array(
							'value' => '2',
							'text'  => '2',
						),
						array(
							'value' => '4',
							'text'  => '4',
						),
						array(
							'value' => '6',
							'text'  => '6',
						),
					),
				);
			}

			if (!$options['webrtcOnly']) 
			$fields['external_rtmp'] = array(
				'name'        => 'external_rtmp',
				'description' => __( 'External Broadcast', 'ppv-live-webcams' ),
				'details'     => __( 'Broadcast with external RTMP encoder: Show a broadcast tab with settings to configure an external RTMP encoder.', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $external_rtmp = self::is_true( get_post_meta( $post->ID, 'external_rtmp', true ) ),
			);

			// record
			
			if ( $options['recording'] ) 
			{
			$fields['stream_record']         = array(
				'name'        => 'stream_record',
				'description' => __( 'Record Perfomer', 'ppv-live-webcams' ),
				'details'     => __( 'Record performer stream.', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $stream_record = self::is_true( get_post_meta( $post->ID, 'stream_record', true ) ),
			);
			
			$fields['stream_record_private'] = array(
				'name'        => 'stream_record_private',
				'description' => __( 'Record Private', 'ppv-live-webcams' ),
				'details'     => __( 'Record in private calls.', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $stream_record_private = self::is_true( get_post_meta( $post->ID, 'stream_record_private', true ) ),
			);
			
			$fields['stream_record_all']     = array(
				'name'        => 'stream_record_all',
				'description' => __( 'Record All', 'ppv-live-webcams' ),
				'details'     => __( 'Record streams from all users.', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $stream_record_all = self::is_true( get_post_meta( $post->ID, 'stream_record_all', true ) ),
			);
			
			}

			if ( $options['tips'] ) {
				$fields['gifts'] = array(
					'name'        => 'gifts',
					'description' => __( 'Gifts', 'ppv-live-webcams' ),
					'details'     => __( 'Enable Gifts button in Actions bar. Gifts apply to current room goal when enabled. Disable to hide current room goal from text chat.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => $gifts = self::is_true( get_post_meta( $post->ID, 'gifts', true ) ),
				);
			}
			if ( $options['goals'] ) {
				$fields['goals_panel'] = array(
					'name'        => 'goals_panel',
					'description' => __( 'Goals Panel', 'ppv-live-webcams' ),
					'details'     => __( 'Show goals panel for participants to see all goals. Users can donate to any Independent goal.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => $goals_panel = self::is_true( get_post_meta( $post->ID, 'goals_panel', true ) ),
				);
			}
			if ( $options['goals'] ) {
				$fields['goals_sort'] = array(
					'name'        => 'goals_sort',
					'description' => __( 'Goals Sort', 'ppv-live-webcams' ),
					'details'     => __( 'Sort goals by current donations, descending.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => $goals_sort = self::is_true( get_post_meta( $post->ID, 'goals_sort', true ) ),
				);
			}
			
			if ( $options['messages'] ) {
				$fields['question_closed'] = array(
					'name'        => 'question_closed',
					'description' => __( 'Messages Closed', 'ppv-live-webcams' ),
					'details'     => __( 'Close paid questions/messages.', 'ppv-live-webcams' ),
					'type'        => 'toggle',
					'value'       => self::is_true( get_post_meta( $post->ID, 'question_closed', true ) ),
				);
			}

			$configuration['room'] = array(
				'name'   => __( 'Room Preferences', 'ppv-live-webcams' ) . ': ' . sanitize_file_name( $post->post_title ),
				'fields' => $fields,
			);
		}

		// user options
		$fieldsUser = [];
	
		
		$fieldsUser = [
			
			'h5v_sfx'    => array(
				'name'        => 'h5v_sfx',
				'description' => __( 'Sound Effects', 'ppv-live-webcams' ),
				'details'     => __( 'Sound effects (on actions).', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $sfx = self::is_true( get_user_meta( $session->uid, 'h5v_sfx', true ) ),
			),
			'h5v_dark'   => array(
				'name'        => 'h5v_dark',
				'description' => __( 'Dark Mode', 'ppv-live-webcams' ),
				'details'     => __( 'Dark interface mode.', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $darkMode = self::is_true( get_user_meta( $session->uid, 'h5v_dark', true ) ),
			),
			'h5v_pip'    => array(
				'name'        => 'h5v_pip',
				'description' => __( 'Picture in Picture', 'ppv-live-webcams' ),
				'details'     => __( 'Picture in picture mode with camera over video.', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $pipMode = self::is_true( get_user_meta( $session->uid, 'h5v_pip', true ) ),
			),
			'h5v_min'    => array(
				'name'        => 'h5v_min',
				'description' => __( 'Minimalist', 'ppv-live-webcams' ),
				'details'     => __( 'Show less buttons, features and interface elements.', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $minMode = self::is_true( get_user_meta( $session->uid, 'h5v_min', true ) ),
			),

			'h5v_audio'  => array(
				'name'        => 'h5v_audio',
				'description' => __( 'Audio Only', 'ppv-live-webcams' ),
				'details'     => __( 'Audio only user mode: Publish only microphone, no webcam video. Applies both to group and private calls.', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $userAudio = self::is_true( get_user_meta( $session->uid, 'h5v_audio', true ) ),
			),
			'h5v_reveal' => array(
				'name'        => 'h5v_reveal',
				'description' => __( 'Reveal Mode', 'ppv-live-webcams' ),
				'details'     => __( 'Webcam reveal mode: hide webcam in private calls and use a Reveal button to enable on request.', 'ppv-live-webcams' ),
				'type'        => 'toggle',
				'value'       => $revealMode = self::is_true( get_user_meta( $session->uid, 'h5v_reveal', true ) ),
			)
		];

		if ( $options['multilanguage'] ?? false ) $fieldsUser['h5v_language'] = self::languageField($session->uid, $options);
			
		if ( $revealMode ) {
			$h5v_reveal_warmup = get_user_meta( $session->uid, 'h5v_reveal_warmup', true );

			$fieldsUser['h5v_reveal_warmup'] = array(
				'name'        => 'h5v_reveal_warmup',
				'description' => __( 'Reveal Warmup', 'ppv-live-webcams' ),
				'details'     => __( 'Time in seconds required before revealing webcam is possible.', 'ppv-live-webcams' ),
				'type'        => 'dropdown',
				'value'       => $h5v_reveal_warmup,
				'options'     => array(
					array(
						'value' => '3',
						'text'  => '3 ' . 'seconds',
					),
					array(
						'value' => '30',
						'text'  => '30 ' . 'seconds',
					),
					array(
						'value' => '60',
						'text'  => '1 ' . 'minute',
					),
					array(
						'value' => '180',
						'text'  => '3 ' . 'minutes',
					),
					array(
						'value' => '300',
						'text'  => '5 ' . 'minutes',
					),
					array(
						'value' => '600',
						'text'  => '10 ' . 'minutes',
					),
					array(
						'value' => '900',
						'text'  => '15 ' . 'minutes',
					),
				),
			);
		}

		$configuration['user'] = array(
			'name'   => __( 'User Preferences', 'ppv-live-webcams' ) . ': ' . $session->username,
			'fields' => $fieldsUser,
		);

		$configuration['meta'] = array( 'time' => time() );

		return $configuration;

	}


	static function notificationMessage( $message, $session, $privateUID = 0, $meta = null ) {
		// adds a notification from server, only visible to user

		$ztime = time();

		global $wpdb;
		$table_chatlog = $wpdb->prefix . 'vw_vmls_chatlog';

		if ( ! $meta ) {
			$meta = array();
		}
		
		$meta['notification'] = true;
		$metaS = serialize( $meta );

		$sql = $wpdb->prepare(
			"INSERT INTO `$table_chatlog` ( `username`, `room`, `room_id`, `message`, `mdate`, `type`, `user_id`, `meta`, `private_uid`) VALUES ( %s, %s, %d, %s, %d, %d, %d, %s, %d )",
			$session->username, $session->room, $session->rid, $message, $ztime, 3, $session->uid, $metaS, $privateUID
		);
		$wpdb->query( $sql );

		// todo maybe: also update chat log file

		return $sql;
	}


	static function autoMessage( $message, $session, $privateUID = 0, $meta = null ) {
		// adds automated user message from server, automatically generated by user action

		$ztime = time();

		global $wpdb;
		$table_chatlog = $wpdb->prefix . 'vw_vmls_chatlog';

		if ( ! $meta ) {
			$meta = array();
		}
		$meta['automated'] = true;
		$metaS             = serialize( $meta );

		$sql = $wpdb->prepare(
			"INSERT INTO `$table_chatlog` ( `username`, `room`, `room_id`, `message`, `mdate`, `type`, `user_id`, `meta`, `private_uid`) VALUES ( %s, %s, %d, %s, %d, %d, %d, %s, %d )",
			$session->username, $session->room, $session->rid, $message, $ztime, 2, $session->uid, $metaS, $privateUID
		);
		$wpdb->query( $sql );

		return $sql;
	}



	static function appPrivateRoom( $post, $session, $privateUID, $actionID, $options ) {
		// private call room parameters, specific for this user


		$room_random = self::is_true( get_post_meta( $post->ID, 'room_random', true ) );
		if ($room_random) return self::appRandomRoom( $post, $session, $options, 'appPrivateRoom #' . $privateUID); 
	
		$room = array();

		$postID = $post->ID;
		$sessionID = $session->id;

		$room['_dev_by'] = "appPrivateRoom($postID, $sessionID, $privateUID, $actionID)";

		if ($options['webrtcServer'] == 'auto') $room['serverType'] = 'videowhisper'; //p2p in private

		$room['ID'] = $post->ID;

		if ( $session->broadcaster ) {
			$privateExt = $session->uid . '-' . $privateUID;
		} else {
			$privateExt = $privateUID . '-' . $session->uid; // the other is performer
		}
		$room['name'] = sanitize_file_name( $post->post_title . '_pr_' . $privateExt );

		if ( $session->broadcaster ) {
			$pid = $session->uid;
			$cid = $privateUID;
		} else {
			$cid = $session->uid;
			$pid = $privateUID;
		}

		// parent room mode
		$room['audioOnly'] = self::is_true( get_post_meta( $post->ID, 'room_audio', true ) );
		$room['textOnly']  = self::is_true( get_post_meta( $post->ID, 'room_text', true ) );

		// get private session info
		$clientCPM = 0;

		global $wpdb;
		$table_private = $wpdb->prefix . 'vw_vmls_private';

		$sqlS     = $wpdb->prepare("SELECT * FROM $table_private WHERE rid = %d AND pid = %d AND cid = %d AND status = 0 ORDER BY status ASC, id DESC", $post->ID, $pid, $cid);
		$pSession = $wpdb->get_row( $sqlS );

		if ( $pSession ) {
			// get session meta for private room
			if ( $pSession->meta ) {
				$pSessionMeta = unserialize( $pSession->meta );
			}
			if ( ! is_array( $pSessionMeta ) ) {
				$pSessionMeta = array();
			}

			// private mode defined?
			if ( array_key_exists( 'callType', $pSessionMeta ) ) {
				if ( $pSessionMeta['callType'] == 'audio' ) {
					$room['audioOnly'] = true;
				}
				if ( $pSessionMeta['callType'] == 'text' ) {
					$room['textOnly'] = true;
				}
			}

			if ( array_key_exists( 'clientCPM', $pSessionMeta ) ) {
				$clientCPM = $pSessionMeta['clientCPM'];
			}
		}

		$room['_dev_pSession'] = $pSession->id ?? 'Error: No private session found!';

		$room['screen'] = 'Way2Screen'; // private 2 way video screen		

		if ( $room['audioOnly'] ) {
			$room['screen'] = 'Way2AudioScreen'; // 2 way audio only mode
		}

		if ( $room['textOnly'] ) {
			$room['screen'] = 'TextScreen'; // text only mode
		}

		if (!$session->broadcaster)  //client 2 way
		{
			if (!$options['private2Way']) 
			{
				if ($room['screen'] == 'Way2Screen') $room['screen'] = 'PlaybackScreen'; 
				if ($room['screen'] == 'Way2AudioScreen') $room['screen'] = 'PlaybackAudioScreen';
			}

		} else //broadcaster 2 way
		{
			if (!$options['private2Way']) 
			{
				if ($room['screen'] == 'Way2Screen') $room['screen'] = 'BroadcastScreen';
				if ($room['screen'] == 'Way2AudioScreen') $room['screen'] = 'BroadcastAudioScreen';
			}
		}

		$room['privateUID'] = $privateUID;

		$room['welcome']      = sprintf( 'Welcome to a private booth #%d of "%s", %s!', $pSession->id ?? 0, sanitize_file_name( $post->post_title ), $session->username );
		$room['welcomeImage'] = dirname( plugin_dir_url( __FILE__ ) ) . '/images/users2.png';

		if ( $session->broadcaster ) {
			$room['welcome'] .= "\n" . __( 'You are room provider.', 'ppv-live-webcams' );
		}

		if ( $room['audioOnly'] ) {
			$room['welcome'] .= "\n" . __( 'Chat Type', 'ppv-live-webcams' ) . ': ' . __( 'Audio chat', 'ppv-live-webcams' );
		}
		if ( $room['textOnly'] ) {
			$room['welcome'] .= "\n" . __( 'Chat Type', 'ppv-live-webcams' ) . ': ' . __( 'Text chat', 'ppv-live-webcams' );
		}

		// $room['welcome'] .= "\n Meta: " . $pSession->meta;

		if ( $clientCPM ) {
			$room['welcome'] .= "\n" . __( 'This is a paid private session:', 'ppv-live-webcams' );
			$room['welcome'] .= "\n - " . __( 'Cost', 'ppv-live-webcams' ) . ': ' . ' ' . $clientCPM . ' ' . htmlspecialchars( $options['currencypm'] );
			
			if ( $session->broadcaster ) 
			{
				$performerRatio = self::performerRatio( $post->post_title, $options, $post->ID );
				$room['welcome'] .= "\n - " . __( 'You get', 'ppv-live-webcams' ) . ': ' . ' ' . round( $clientCPM * $performerRatio , 2 ) . ' ' . htmlspecialchars( $options['currencypm'] ) . ' (' . ($performerRatio*100) . '%)';
			}

			if ( $options['ppvGraceTime'] ) {
				$room['welcome'] .= "\n - " . __( 'Charging starts after a grace time:', 'ppv-live-webcams' ) . ' ' . $options['ppvGraceTime'] . 's.';
			}
			

			$room['welcomeImage'] = dirname( plugin_dir_url( __FILE__ ) ) . '/images/cash.png';
		}

		// special private streams based on user id
		$streamBroadcast = 'ps_' . $session->uid . '-' . $privateUID;
		$streamPlayback  = 'ps_' . $privateUID . '-' . $session->uid;
		
		$room['streamNameBroadcast'] = $streamBroadcast;
		$room['streamNamePlayback'] = $streamPlayback;

		// in 2w always receive broadcast keys
		$room['streamBroadcast'] = self::webrtcStreamQuery( $session->uid, $post->ID, 1, $streamBroadcast, $options, 0, $post->post_title, $privateUID );

		//playback only for client or in 2way mode
		if (!$session->broadcaster || $options['private2Way']) $room['streamPlayback']  = self::webrtcStreamQuery( $session->uid, $post->ID, 0, $streamPlayback, $options, 0, $post->post_title, $privateUID );
		else 
		{
			//otherwise disable playback
			$room['streamPlayback'] = '';
			$room['streamNamePlayback'] = '';
	    }

		$room['streamUID']       = intval( $privateUID );

		$room['actionPrivate']      = false;
		$room['actionPrivateClose'] = true;

		$room['actionID'] = $actionID;

		// panel reset
		/*
		$room['panelCamera'] = false;
		$room['panelUsers'] = false;
		$room['panelOptions'] = false;
		$room['panelFiles'] = false;
		$room['panelPresentation'] = false;
		*/

		// $other = get_userdata($privateUID);

		// configure tipping options for clients
		$room['tips'] = false;
		if ( $options['tips'] ) {
			if ( ! $session->broadcaster ) {

				$tipOptions = self::appTipOptions( $options );
				if ( count( $tipOptions ) ) {
					$room['tipOptions'] = $tipOptions;
					$room['tips']       = true;
					$room['tipsURL']    = dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/templates/messenger/tips/';
				}
			}
		}

		$room['voteOptions'] = self::appVoteOptions( $postID, $options );

		// offline snapshot (poster)
		
		$age = self::format_age( time() - $session->redate );
		
		$room['snapshot'] = self::webcamThumbSrc( $post->ID, sanitize_file_name( $post->post_title ), $options, $age, false ) ;

		$showImage = get_post_meta( $post->ID, 'showImage', true );
		if (!$showImage) $showImage = 'auto';

		$isLive = 0;
 	 	if ( $age == __( 'LIVE', 'ppv-live-webcams' ) ) $isLive = 1;

		// offline teaser video
		if ( !$isLive ) 
		{

			if ( $options['teaserOffline'] ) 
			{
				$video_teaser = get_post_meta( $post->ID, 'video_teaser', true );
				if ( $video_teaser ) {
					$room['videoOffline'] = self::vsvVideoURL( $video_teaser, $options );
				} else {
					$room['videoOffline'] = '';
				}
			}
		}

		//disable snapshots in private
		if (!$options['privateSnapshots']) $room['snapshotDisable'] = true;

		//file upload button
		 $room['filesUpload'] = self::appRole( $session->uid, 'filesUpload', boolval( $session->broadcaster ), $options );

		// custom buttons
		$actionButtons = array();

		/*
		//exit
		if ($session->broadcaster) $pid = $options['p_videowhisper_webcams_performer'];
		else $pid = $options['p_videowhisper_webcams_client'];
		$url = get_permalink( $pid );
		//_ will be added to target
		$actionButtons['exitDashboard'] = array('name'=> 'exitDashboard', 'icon'=>'close', 'color'=> 'red', 'floated'=>'right', 'target' => 'top', 'url'=> $url,'text'=>'', 'tooltip'=> __('Exit', 'ppv-live-webcams'));
		$room['actionButtons'] = $actionButtons;
		*/

		return $room;

	}


	static function collaborationMode( $postID, $options ) {
		$presentationMode = get_post_meta( $postID, 'vw_presentationMode', true );
		return self::is_true( $presentationMode );

		//default
		/*
		if ( $presentationMode == '' || empty( $presentationMode ) ) {
			return self::is_true( $options['presentation'] );
		} else {
			return self::is_true( $presentationMode );
		}
		*/
	}


	static function appStreamBroadcast( $userID, $post, $options ) {
		// broadcasting stream

		$user = get_userdata( $userID );
		// $broadcaster = self::isPerformer($userID, $post->ID)
		$streamName = $user->user_nicename;

		return self::webrtcStreamQuery( $userID, $post->ID, 1, $streamName, $options, 0, $post->post_title, 0 );
	}


	static function appStreamPlayback( $userID, $performerID, $post, $options ) {
		$user = get_userdata( $performerID );
		// $broadcaster = self::isPerformer($userID, $post->ID);
		$streamName = $user->user_nicename;

		return self::webrtcStreamQuery( $userID, $post->ID, 0, $streamName, $options, 0, $post->post_title, 0 );
	}

	static function appRole( $userID, $parameter, $default, $options ) {
		// returns parameter depending on user role
		if ( ! array_key_exists( 'appRoles', $options ) ) {
			return $default;
		}
		if ( ! array_key_exists( $parameter, $options['appRoles'] ) ) {
			return $default;
		}
		if ( ! array_key_exists( 'roles', $options['appRoles'][ $parameter ] ) ) {
			return $default;
		}

		$value = $options['appRoles'][ $parameter ]['value'];
		$other = $options['appRoles'][ $parameter ]['other'];

		$rolesS = trim( $options['appRoles'][ $parameter ]['roles'] );
		if ( $rolesS == '' || $rolesS == 'NONE' ) {
			return $other;
		}

		// special handling
		if ( $rolesS == 'ALL' ) {
			return $value;
		}
		if ( $userID && $rolesS == 'MEMBERS' ) {
			return $value;
		}

		$roles = explode( ',', $rolesS );
		foreach ( $roles as $key => $role ) {
			$roles[ $key ] = trim( $role ); // remove spaces
		}

		$user = get_userdata( $userID );
		if ( ! $user ) {
			return $other;
		}
		foreach ( $roles as $role ) {
			if ( in_array( $role, $user->roles ) ) {
				return $value;
			}
		}

		return $other;
	}



	static function appVoteOptions($postID, $options)
	{
		if ( !($options['categoriesContest'] ?? false) ) return false;

		$contestIDs = self::categoryIDs($options['categoriesContest']);
		$postCategories = wp_get_post_categories($postID);

		$voteOptions = [];

		$votes = get_post_meta( $postID, 'rateStarReview_votes', true);
		if (!is_array($votes)) $votes = [];

		$voted = 0;
		if (is_user_logged_in()) $user = wp_get_current_user();
		else $user = false;
		if ($user) if ( in_array($user->user_login, $votes) ) $voted = 1;
		
		$voteOptions[] =
		[
			'id' => 0,
			'text' => __('All', 'ppv-live-webcams'),
			'cost' => ($options['voteCost'] ?? 0 ) .  $options['currency'],
			'voted' => $voted,
			'votes' => count($votes),
		];

		if ($contestIDs) foreach ($contestIDs as $contestID)
		{
			if (in_array($contestID, $postCategories)) 
			{
				$votedCategory = 0;
				$votesCategory = get_post_meta( $postID, 'rateStarReview_votes' . $contestID, true);
				if (!is_array($votesCategory)) $votesCategory = [];
				if ($user) if ( in_array($user->user_login, $votesCategory) ) $votedCategory = 1;

				$voteOptions[] = ['id' => $contestID, 'text' => get_cat_name($contestID), 'cost' => $options['voteCost'] ?? 0, 'voted' => $votedCategory, 'votes' => count($votesCategory)];
			}
		}

		return $voteOptions;
	}

	static function appPublicRoom( $post, $session, $options, $welcome = '', &$room = null, $requestUID = 0 ) {
		// public room parameters, specific for this user



		$room_random = self::is_true( get_post_meta( $post->ID, 'room_random', true ) );
		if ($room_random) return self::appRandomRoom( $post, $session, $options, 'appPublicRoom U#' . $requestUID ); 
			
			
		if ( ! $room ) {
			$room = array();
		}

		$room['_dev_by'] = "appPublicRoom";

		$room['ID']   = $post->ID;
		$room['name'] = sanitize_file_name( $post->post_title );

		$room['performer']   = sanitize_file_name( get_post_meta( $post->ID, 'performer', true ) );
		$room['performerID'] = intval( get_post_meta( $post->ID, 'performerUserID', true ) );
		if ( ! $room['performerID'] ) {
			$room['performerID'] = intval( $post->post_author );
		}

		$collaboration = self::collaborationMode( $post->ID, $options );
		if ( VW_H5V_DEVMODE && VW_H5V_DEVMODE_COLLABORATION ) {
			$collaboration = true;
		}

		$conference = self::is_true( get_post_meta( $post->ID, 'room_conference', true ) );

		$room['audioOnly'] = self::is_true( get_post_meta( $post->ID, 'room_audio', true ) );
		$room['textOnly']  = self::is_true( get_post_meta( $post->ID, 'room_text', true ) );

		$appComplexity = ( $options['appComplexity'] == '1' || ( $session->broadcaster && $options['appComplexity'] == '2' ) );

		// screen
		if ( $session->broadcaster ) {
			$roomScreen = 'BroadcastScreen';
		} else {
			$roomScreen = 'PlaybackScreen';
		}

		if ( $room['audioOnly'] ) { // audion only layouts
			if ( $session->broadcaster ) {
				$roomScreen = 'BroadcastAudioScreen';
			} else {
				$roomScreen = 'PlaybackAudioScreen';
			}
		}

		if ( $room['textOnly'] ) {
			$roomScreen = 'TextScreen';
		}

		if ( $conference || $collaboration || $appComplexity ) {
			if ( $room['textOnly'] ) {
				$roomScreen = 'CollaborationTextScreen';
			} else {
				$roomScreen = 'CollaborationScreen';
			}
		}

				$room['screen'] = $roomScreen;

			$streamName = $room['performer'];

		// only performer receives broadcast keys in public room
		if ( $session->broadcaster ) {
			$room['streamBroadcast'] = self::appStreamBroadcast( $session->uid, $post, $options );
			$room['streamNameBroadcast'] = $session->username;
		} else {
			$room['streamBroadcast'] = '';
			$room['streamNameBroadcast'] = '';
		}
		
		
		 $isModerator = self::isModerator($session->uid, $options);


		$room['streamUID']      = intval( $room['performerID'] );
		$room['streamPlayback'] = self::appStreamPlayback( $session->uid, $room['streamUID'], $post, $options );

		// Split the string and store in a variable first
		$streamPlayback = explode('?', $room['streamPlayback']);
		// Now use array_shift on the variable
		$room['streamNamePlayback'] = array_shift($streamPlayback);

		$room['actionPrivate']        = !$session->broadcaster && !$requestUID && !$isModerator;
		$room['actionPrivateClose']   = false;
		$room['privateUID']           = 0;
		$room['actionPrivateDisable'] = self::is_true( get_post_meta( $post->ID, 'requests_disable', true ) );

		if ( ! $session->broadcaster ) {
			self::updatePrivateShow( $post->ID, $room['performerID'] ); // update performer private status
			$room['actionPrivateBusy'] = self::is_true( get_post_meta( $post->ID, 'privateShow', true ) );
		}

		$room['actionID'] = 0;


		$room['welcome']      = ' 💬 ' . sprintf( 'Welcome to public room #%d "%s", %s!', $post->ID, sanitize_file_name( $post->post_title ), $session->username );

		$room['welcomeImage'] = dirname( plugin_dir_url( __FILE__ ) ) . '/images/chat.png';

		if (VW_H5V_DEVMODE) $room['welcome'] .= "\nDEVMODE";

		if ( $session->broadcaster ) {
			$room['welcome'] .= "\n" . __( 'You are room performer.', 'ppv-live-webcams' );
		}

		$private = self::is_true( get_post_meta( $post->ID, 'room_private', true ) );
		if ( $private ) {
			$room['welcome'] .= "\n" . __( 'This is a private room (not listed).', 'ppv-live-webcams' );
		}

		$calls_only = self::is_true( get_post_meta( $post->ID, 'calls_only', true ) );
		if ( $calls_only ) {
			$room['welcome'] .= "\n" . __( 'This room can only be used for predefined locked calls. Participants need a private call link.', 'ppv-live-webcams' );
		}
		$group_disabled = self::is_true( get_post_meta( $post->ID, 'group_disabled', true ) );

		if ( $group_disabled ) {
			$room['welcome'] .= "\n" . __( 'Group chat is disabled. Participants can only access this room for instant or locked calls.', 'ppv-live-webcams' );
		}


		//streams
		if ( $options['reStreams'] )
		{
			
			//room stream mode?
			$streamType = get_post_meta(  $post->ID, 'stream-mode', true );
			if ($streamType == 'stream')
			{
				$streamName = get_post_meta( $post->ID, 'stream-name', true ) ;
				$room['streamName'] = $streamName ;
				$room['streamAddress'] = ($options['httprestreamer'] ? $options['httprestreamer']  : $options['httpstreamer']) . $streamName . '/playlist.m3u8' ;
				
				if ($options['debugMode']) $room['welcome'] .= "\nStream HLS: " . $room['streamHLS'];
			}
			else 
			{
				$room['streamName'] = '';
				$room['streamAddress'] = '';		
			}
			
			//$streamsViews = self::appRole( $session->uid, 'streamsViews', boolval( $session->uid ), $options );


			//stream list
			$streams = [];
			
			$reStreams = get_post_meta(  $post->ID, 'reStreams', true );
			if ( !$reStreams ) $reStreams = [];
			if ( !is_array($reStreams) ) $reStreams = [];
				
				//add streams
				foreach ($reStreams as $streamName => $address) 
				{
					$streamLabel = str_replace('.stream', '', $streamName );
					$streams[ $streamName ] = [ 'name' => $streamLabel, 'key' => $streamLabel, 'stream' => $streamName, 'address' => ($options['httprestreamer'] ? $options['httprestreamer']  : $options['httpstreamer']) . $streamName . '/playlist.m3u8' ];	
				}
				
		    if ( count($streams) ) $room['streams'] = $streams;
		    
		    if ( $options['debugMode'] ) $room['welcome'] .= "\nRoom Streams: " . count($streams);
		    
		    $room['streamsAdmin'] = $session->broadcaster ? true : false ; //admin for room
		}
		
		$clientCPM      = self::clientCPM( $post->post_title, $options, $post->ID );
		$performerRatio = self::performerRatio( $post->post_title, $options, $post->ID );

		$groupCPM = get_post_meta( $post->ID, 'groupCPM', true );

		if ( $groupCPM ) {
			$groupMode = get_post_meta( $post->ID, 'groupMode', true );

			$room['welcome'] .= "\n" . __( 'This is a paid room:', 'ppv-live-webcams' );
			$room['welcome'] .= "\n - " . __( 'Group Mode', 'ppv-live-webcams' ) . ': ' . $groupMode;
			$room['welcome'] .= "\n - " . __( 'Group session cost', 'ppv-live-webcams' ) . ': ' . ' ' . $groupCPM . ' ' . htmlspecialchars( $options['currencypm'] );
			
			if ( $session->broadcaster ) $room['welcome'] .= "\n - " . __( 'You get', 'ppv-live-webcams' ) . ': ' . ' ' . round( $groupCPM * $performerRatio , 2 ) . ' ' . htmlspecialchars( $options['currencypm'] ) . ' (' . ($performerRatio*100) . '%)';
			
			if ( $options['ppvGraceTime'] ) {
				$room['welcome'] .= "\n - " . __( 'Charging starts after a grace time:', 'ppv-live-webcams' ) . ' ' . $options['ppvGraceTime'] . 's, if performer is online. ';
			}
			$room['welcome'] .= "\n - " . __( 'All participants pay during this session: Private calls are not available.', 'ppv-live-webcams' );

			$room['welcomeImage'] = dirname( plugin_dir_url( __FILE__ ) ) . '/images/cash.png';

			$room['actionPrivate'] = false;
		}
		
		
			$room['mode'] = get_post_meta( $post->ID, 'groupMode', true );

		//room group mode for performer
		if ( $session->broadcaster && is_array( $options['groupModes'] ) )
		{
				
				$modes = [];
				
				foreach ( $options['groupModes'] as $groupMode => $modeParameters ) 
				{
				
					$groupMode = sanitize_text_field( $groupMode );
					$icon = 'user';
					
					if ( $modeParameters['cpm'] ?? false )	$icon = 'money';
					
					if ( $modeParameters['room_audio'] ?? false )	$icon = 'volume up';
					if ( $modeParameters['room_text'] ?? false )	$icon = 'comment alternate';
					if ( $modeParameters['group_disabled'] ?? false )	$icon = 'user md';		
					if ( $modeParameters['room_private'] ?? false )	$icon = 'user secret';
					if ( $modeParameters['room_random'] ?? false )	$icon = 'sync';
					if ( $modeParameters['room_conference'] ?? false )	$icon = 'users';
					$modes[ $groupMode ] = [ 'key' => sanitize_file_name($groupMode), 'name' => $groupMode, 'icon' => $icon ];
					
				}
				
				if ( count($modes) ) $room['modes'] = $modes;

		}
		
		// room  mode info
		if ( ! is_array( $roomMeta = unserialize( $session->roptions ) ) ) {
			$roomMeta = array();
		}
		if ( array_key_exists( 'userMode', $roomMeta ) ) {
			if ( $roomMeta['userMode'] == 'voyeur' ) {
				$room['welcome'] .= "\n" . __( 'You are in Voyeur mode: hidden from user list. Participants will not be aware of your presence unless you write in text chat.', 'ppv-live-webcams' );
				if ( $groupParameters['cpmv'] ) {
					$room['welcome'] .= "\n - " . __( 'Voyeur cost', 'ppv-live-webcams' ) . ': ' . $groupParameters['cpmv'] . $options['currencypm'];
				}
			}
		}
		
		 if ( $isModerator && !$session->broadcaster ) $room['welcome'] .= "\n 🕵️" .  __( 'You are a moderator. You will not show in user list or generate client charges. You can NOT request private calls.', 'ppv-live-webcams' );

		if ( ! $session->uid ) {
			$room['actionPrivate'] = false;
			$room['welcome']      .= "\n" . 'Your are not logged in: Please register and login to access more advanced features!';
		} elseif ( $session->broadcaster ) {

			if ( ! $groupCPM && !$room['actionPrivateDisable'] ) {
				$room['welcome'] .= "\n 👥 " . __( 'Registered users can request private calls during free presale chat and you can accept:', 'ppv-live-webcams' );
				if ( $clientCPM ) {
					$room['welcome'] .= "\n - " . __( 'Private show cost per minute, for client:', 'ppv-live-webcams' ) . ' ' . $clientCPM . ' ' . htmlspecialchars( $options['currencypm'] );
				}
				if ( $options['ppvGraceTime'] ) {
					$room['welcome'] .= "\n - " . __( 'Charging starts after a grace time:', 'ppv-live-webcams' ) . ' ' . $options['ppvGraceTime'] . 's';
				}
				if ( $clientCPM && $performerRatio ) {
					$room['welcome'] .= "\n - " . __( 'Private call earning per minute, for performer:', 'ppv-live-webcams' ) . ' ' . number_format( $clientCPM * $performerRatio, 2, '.', '' ) . ' ' . htmlspecialchars( $options['currencypm'] ) . ' (' . ($performerRatio * 100) . '%)';
				}
			
			}
		} else // member: client
			{
			if ( $post->post_author == $session->uid ) {
				$selectWebcam = get_user_meta( $session->uid, 'currentWebcam', true );
				if ( $selectWebcam ) {
					$room['welcome'] .= "\n Warning: This is your room but you are not accessing as performer. Your currently selected webcam: " . get_the_title( $selectWebcam );
				}
			}

			if ( $room['actionPrivate'] ) {
				// can client request private show?
				$balancePending = self::balance( $session->uid, true );
				if ( $clientCPM ) {
					$ppvMinimum = self::balanceLimit( $clientCPM, 5, $options['ppvMinimum'], $options );
				}
				if ( $ppvMinimum && $clientCPM ) {
					if ( $balancePending < $ppvMinimum ) {
						$room['actionPrivate'] = false;
						$room['welcome']      .= "\n" . __( 'You do not have enough credits to request a private show.', 'ppv-live-webcams' ) . " ($ppvMinimum)";
					} else {

						$room['welcome'] .= "\n 👥 " . __( 'You can request private session from room performer when available:', 'ppv-live-webcams' );

						if ( $clientCPM ) {
							$room['welcome'] .= "\n - " . __( 'Private show cost per minute:', 'ppv-live-webcams' ) . ' ' . $clientCPM . ' ' . htmlspecialchars( $options['currencypm'] );
						}
						if ( $options['ppvGraceTime'] ) {
							$room['welcome'] .= "\n - " . __( 'Charging starts after a grace time:', 'ppv-live-webcams' ) . ' ' . $options['ppvGraceTime'] . 's, if performer is online.';
						}
					}
				}
			}
		}

		// next
		if ( $options['videochatNext'] ) {
			if ( ! $session->broadcaster && ! $requestUID ) {
				$room['next'] = true;
			}

			// party
			$party = self::is_true( get_post_meta( $post->ID, 'party', true ) );
			if ( $party ) {
				$room['welcome'] .= "\n " . __( 'This room is in party mode and all users join the party squad. Entire party can be moved to other rooms by host, using Next button.', 'ppv-live-webcams' );
			}

			if ( $session->meta ) {
				$userMeta = unserialize( $session->meta );
			}
			if ( ! is_array( $userMeta ) ) {
				$userMeta = array();
			}

			if ( array_key_exists( 'party', $userMeta ) ) {
				if ( $userMeta['party'] ) {
					$room['welcome'] .= "\n " . __( 'You are in a party', 'ppv-live-webcams' ) . ': ' . $userMeta['partyName'];
					if ( $userMeta['partyHost'] ) {
						$room['welcome'] .= "\n - " . __( 'You are a party host. You can move party with Next and manage party users.', 'ppv-live-webcams' );
						$room['next']     = true;
					} else {
						$room['next'] = false; // only host can move party
					}

					$room['party']     = true;
					$room['partyHost'] = self::is_true( $userMeta['partyHost'] );
				}
			}
		}

		if ( $welcome ) {
			$room['welcome'] .= "\n" . $welcome;
		}

		
		//room snapshot
		$age = self::format_age( time() - $session->redate );
		
		$room['snapshot'] = self::webcamThumbSrc( $post->ID, sanitize_file_name( $post->post_title ), $options, $age, false ) ;


		$showImage = get_post_meta( $post->ID, 'showImage', true );
		if (!$showImage) $showImage = 'auto';


			$isLive = 0;
 	 	   if ( $age == __( 'LIVE', 'ppv-live-webcams' ) ) $isLive = 1;


		// offline teaser video
		
		
		if ( $showImage != 'auto' || !$isLive ) 
		{

		if ( $options['teaserOffline'] ) {
			$video_teaser = get_post_meta( $post->ID, 'video_teaser', true );
			if ( $video_teaser ) {
				$room['videoOffline'] = self::vsvVideoURL( $video_teaser, $options );
			} else {
				$room['videoOffline'] = '';
			}
			}
		}
		
		// panel reset
		$room['panelCamera']       = false;
		$room['panelUsers']        = false;
		$room['panelOptions']      = false;
		$room['panelFiles']        = false;
		$room['panelPresentation'] = false;

		// collaboration
		if ( $collaboration ) {
			$room['welcome'] .= "\n " . __( 'Room is in collaboration mode, with a Files panel.', 'ppv-live-webcams' );
			$room['files']    = self::appRoomFiles( $room['name'], $options );

			$room['panelFiles'] = true;

			$room['filesUpload'] = VW_H5V_DEVMODE || is_user_logged_in();
			
			$room['filesDelete'] = boolval( $session->broadcaster );

			$room['filesPresentation'] = boolval( $session->broadcaster );
			$room['panelPresentation'] = boolval( $session->broadcaster );
		}

		 $room['filesUpload'] = self::appRole( $session->uid, 'filesUpload', boolval( $session->broadcaster  || ( $collaboration && is_user_logged_in() ) ), $options );


		// room media (split view), including when disabled to reset to 1 slot
		$room['media'] = self::appRoomMedia( $post, $session, $options );

		if ( $conference || $collaboration ) {
			// all users can broadcast

			$panelCamera = self::appRole( $session->uid, 'conferenceParticipantCamera', boolval( $session->uid > 0 ), $options );

			if ( VW_H5V_DEVMODE || $panelCamera ) {
				$room['panelCamera']     = true;
				$room['streamBroadcast'] = self::appStreamBroadcast( $session->uid, $post, $options );
			}

			// assign user to media slots
			$room['usersPresentation'] = boolval( $session->broadcaster );
		} elseif ( $appComplexity ) {

			if ( $session->broadcaster ) {
				$room['panelCamera']     = true;
				$room['streamBroadcast'] = self::appStreamBroadcast( $session->uid, $post, $options );
			}
		}

		// advanced interface: always for conference, collaboration
		if ( $conference || $collaboration || $appComplexity ) {
			// users list
			$room['panelUsers']     = true;
			
			$room['usersModerator'] = ( boolval( $session->broadcaster ) || $isModerator ) && $options['bans']; // kick/ban
			
			if ( $options['bans'] && !$room['usersModerator'] ) {
				$room['usersModerator'] = self::appRole( $session->uid, 'banUsers', boolval( $session->broadcaster ) || $isModerator , $options ); // other users
			}

			// options for performer only
			if ( $options['appOptions'] ) {
				if ( $session->broadcaster ) {
					$room['panelOptions'] = boolval( $session->broadcaster );
					$room['options']      = self::appRoomOptions( $post, $session, $options );
				}
			}
		}

		// also needed to check when user comes online in calls
		if ( $requestUID || $conference || $collaboration || $appComplexity ) {
			$room['users'] = self::appRoomUsers( $post, $options );
		}

		// external broadcast panel: only for performer
		$external_rtmp = self::is_true( get_post_meta( $post->ID, 'external_rtmp', true ) );
		if ($options['webrtcOnly']) $external_rtmp = false;

		$room['panelBroadcast'] = $external_rtmp && $room['panelCamera'] && $session->broadcaster;

		if ( $room['panelBroadcast'] ) {
			$room['broadcastSettings'] = self::appBroadcastSettings( $session, $post, $options );
			$room['welcome']          .= "\n 📡 " . __(
				'WebRTC broadcasting may not be supported or provide good quality on certain devices, browser versions, connections or network conditions. 
 - Use best network available if you have the option: 5GHz on WiFi instead of 2.4 GHz, LTE/4G on mobile instead of 3G, wired instead of wireless.
 - For increased streaming quality and reliability, you can broadcast directly to streaming server with an application like OBS for desktop or GoCoder for mobile. Advanced desktop encoders also enable advanced compositions, screen sharing, effects and transitions. See Broadcast tab next to Cam tab.',
				'ppv-live-webcams'
			);

		}

$browser  = '';
if (isset($_SERVER['HTTP_USER_AGENT'])) 
if (stripos( $_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false) $browser='Chrome';
elseif (stripos( $_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) $browser = 'Safari';

/*
if ($browser == 'Safari' )
{
$room['welcome'] .=  "\n ⚠️ " . 'In latest Safari you need to disable NSURLSession WebSocket for WebRTC streaming to work:';

if ( strstr( $_SERVER['HTTP_USER_AGENT'], " Mobile/") ) $room['welcome'] .=  "\n " . 'On iOS Mobile, open Settings application. Tap Safari, then Advanced, and then Experimental Features, disable NSURLSession WebSocket.';
else $room['welcome'] .=  "\n " . 'On PC: From Safari menu > Preferences ... > Advanced tab, enable Show Develop menu. Then from Develop menu > Experimental features disable NSURLSession WebSocket.';
}
*/
		if ($options['lovense'])
		{
		 $room['welcome'] .=  "\n 🕹 " . 'Lovense integration is enabled. Use Lovense Browser or Extension to use features.';
		 		 
		}	
			
		//paid questions and messages
		if ($options['messages'])
		{
			$question_closed = self::is_true( get_post_meta( $post->ID, 'question_closed', true ) );
			if (!$question_closed) 
			{   
				//
				$room['questions'] = true;
				$room['questionsMessages'] = self::appRoomMessages( $post->ID, $options, ( $session->broadcaster ? 0 : $session->uid), 0, 1, 1,  $session->broadcaster);				
				
				if (! $session->broadcaster )
				{
						$room['questionsQuestion'] =  ! self::is_true( $session->broadcaster );

						$price = floatval( get_post_meta( $post->ID, 'question_price', true ) );
						
						if ( ! $price ) {
							$price = $options['messagesCost'];
						}
						
						$room['questionsPrice'] =  $price;
						
						$room['questionsInfo'] =  __('Room owner accepts questions.', 'ppv-live-webcams');
						if ($price) $room['questionsInfo'] .= ' ' .  __('When you send a question, you pay this amount:', 'ppv-live-webcams') . ' ' .  $price . $options['currency'];
 				}else
 				{
	 					$room['questionsAnswer'] =  self::is_true( $session->broadcaster );
	 					$room['questionsInfo'] =  __('Room owner can click to select a paid question and write replies.', 'ppv-live-webcams');
 				}
			}
			
		}
		
		// goals panel
		$goals_panel =  self::is_true( $options['goals'] && get_post_meta( $post->ID, 'goals_panel', true ) );

		if ( $goals_panel ) {
			$room['panelGoals'] = true;
			$goals_sort         = self::is_true( get_post_meta( $post->ID, 'goals_sort', true ) );
			$room['goals']      = self::appRoomGoals( $post->ID, $options, $goals_sort );
		} else {
			$room['panelGoals'] = false;
		}

		$room['goal'] = $goal = self::goal( $post->ID, $add = 0, $options ); // current goal

		// allow to complete
		if ( $session->broadcaster ) {
			$room['goalsComplete'] = true;
			$room['goalsManage']   = true;
		}

		// configure tipping options for clients: room & role
		$gifts = ( self::is_true( get_post_meta( $post->ID, 'gifts', true ) ) && self::rolesUser( $options['rolesDonate'], get_userdata(  $session->uid ) ) ) ;

			$room['tips'] = false;
			
		if ( $options['tips'] ) {
			if ( $gifts ) {
				$room['tips'] = true;
			}
		}

		if ($isModerator) $room['tips'] = false; //disable for moderators

		if ( $room['tips'] || $room['panelGoals'] ) {
			$tipOptions = self::appTipOptions( $options );

			if ( count( $tipOptions ) ) {
				$room['tipOptions'] = $tipOptions;
				$room['tipsURL']    = dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/templates/messenger/tips/';
			}
		}

			$room['voteOptions'] = self::appVoteOptions( $post->ID, $options );

			// custom buttons
			$actionButtons = array();

			// exit
		if ( $session->broadcaster ) {
			$pid = $options['p_videowhisper_webcams_performer'];
		} else {
			$pid = $options['p_videowhisper_webcams_client'];
		}
			$url                            = get_permalink( $pid );
			$actionButtons['exitDashboard'] = array(
				'name'    => 'exitDashboard',
				'icon'    => 'close',
				'color'   => 'red',
				'floated' => 'right',
				'target'  => 'top',
				'url'     => $url,
				'text'    => '',
				'tooltip' => __( 'Exit', 'ppv-live-webcams' ),
			);
			$room['actionButtons']          = $actionButtons;

			// current room goal if gifts button is enabled
			if ( $options['tips'] ) {
				if ( $options['goals'] ) {
					if ( $gifts ) {
										// $goal = self::goal($post->ID, 0, $options);
						if ( $goal ) {
							// $room['welcome'] .= "\n" . serialize($goal);

							$room['welcome'] .= "\n 🎁 " . __( 'Current gifts goal', 'ppv-live-webcams' ) . ': ' . $goal['name'];
							$room['welcome'] .= "\n - " . __( 'Goal description', 'ppv-live-webcams' ) . ': ' . $goal['description'];
							$room['welcome'] .= "\n - " . __( 'Goal started', 'ppv-live-webcams' ) . ': ' . ( isset($goal['started']) ? self::humanDuration( $goal['started'] - time() ) : ' - ' );
							$room['welcome'] .= "\n - " . __( 'Cumulated gifts (including previous goals)', 'ppv-live-webcams' ) . ': ' . $goal['cumulated'];

							$room['welcomeProgressValue']   = $goal['current'];
							$room['welcomeProgressTotal']   = $goal['amount'];
							$room['welcomeProgressDetails'] = $goal['name'];
						}
					}
				}
			}

			return $room;
	}

	static function goalCmp( $a, $b ) {
			   return $b['current'] - $a['current'];
	}


	static function appRoomGoals( $postID, $options, $goals_sort = false ) {

		if ( ! $options ) {
			$options = get_option( 'VWliveWebcamsOptions' );
		}

		if ( ! $postID ) {
			return 0; // goal is per post
		}

		$goals = get_post_meta( $postID, 'goals', true );

		$saveGoal = 0;

		if ( ! $goals ) {
			$goals = $options['goalsDefault'];
		}

		if ( ! is_array( $goals ) ) {
			$goals = array(
				0 => array(
					'name'        => 'Goal',
					'description' => 'Default. No custom goals setup.',
					'amount'      => 100,
					'current'     => 0,
					'cumulated'   => 0,
				),
			);
		}

		foreach ( $goals as $key => $value ) {
			$goals[ $key ]['description'] = stripslashes( $goals[ $key ]['description'] );
			$goals[ $key ]['current']     = floatval( $goals[ $key ]['current'] ?? 0 );
			$goals[ $key ]['ix']          = intval( $key );
		}

		if ( $goals_sort ) {
			usort( $goals, 'self::goalCmp' );
		}

		return $goals;
	}

	static function appBroadcastSettings( $session, $post, $options ) {
		$configuration = array();

		if ($options['rtmpServer'] == 'videowhisper') {
			$rtmpAddress =  trim( $options['videowhisperRTMP'] );
			$stream = trim($options['vwsAccount']) . '/' . trim($session->username) . '?pin=' . self::getPin($session->uid, 'broadcast', $options); 
			$rtmpURL = $rtmpAddress . '//' . $stream;
		}
		else 
		{
		$rtmpAddress = self::rtmp_address( $session->uid, $post->ID, true, $session->username, $post->post_title, $options );

		$application = substr( strrchr( $rtmpAddress, '/' ), 1 );
		$stream      = $session->username;

		$adrp1 = explode( '://', $rtmpAddress );
		$adrp2 = explode( '/', $adrp1[1] );
		$adrp3 = explode( ':', $adrp2[0] );

		$server = $adrp3[0];
		$port   = $adrp3[1] ?? '';
		if ( ! $port ) {
			$port = 1935;
		}
		
		$rtmpURL = $rtmpAddress . '/' . $stream;
		}

		$fields = array(
			'downloadOBS' => array(
				'name'        => 'downloadOBS',
				'description' => __( 'Download OBS', 'ppv-live-webcams' ),
				'details'     => __( 'OBS Studio is free desktop application for live streaming from Linux, Mac and Windows. Includes advanced composition features, screen sharing, scenes, transitions, filters, media input options.', 'ppv-live-webcams' ),
				'type'        => 'link',
				'url'         => 'https://obsproject.com',
				'icon'        => 'cloud upload',
				'color'       => 'blue',
			),
			'streamURL'   => array(
				'name'        => 'streamURL',
				'description' => __( 'Stream URL', 'ppv-live-webcams' ),
				'details'     => __( 'RTMP Address / OBS Stream URL: full streaming address. Contains: server, port if different than default 1935, application and control parameters, key. For OBS Settings: Stream > Server.', 'ppv-live-webcams' ),
				'type'        => 'text',
				'value'       => $rtmpAddress,
			),
			'streamKey'   => array(
				'name'        => 'streamKey',
				'description' => __( 'Stream Key', 'ppv-live-webcams' ),
				'details'     => __( 'Stream Name / OBS Stream Key: name of stream. For OBS Settings: Stream > Stream Key.', 'ppv-live-webcams' ),
				'type'        => 'text',
				'value'       => $stream,
			),
			
		);

		$videoBitrate = 0;
		$audioBitrate = 0;

		// $videoBitrate from rtmp session control
		$sessionsVars = self::varLoad( $options['uploadsPath'] . '/sessionsApp' );
		if ( is_array( $sessionsVars ) ) {
			if ( array_key_exists( 'limitClientRateIn', $sessionsVars ) ) {
				$limitClientRateIn = intval( $sessionsVars['limitClientRateIn'] ) * 8 / 1000;

				if ( $limitClientRateIn ) 
				{
					$videoBitrate  = $limitClientRateIn - 100;
					$audioBitrate  = 96;
				}

				if ( $limitClientRateIn ) $videoBitrate           = $limitClientRateIn - 100;
				
				//also limit to values set by admin if lower
				if ($options['webrtcVideoBitrate']) if ($videoBitrate > $options['webrtcVideoBitrate']) $videoBitrate = $options['webrtcVideoBitrate'];
				if ($options['webrtcAudioBitrate']) if ($audioBitrate > $options['webrtcAudioBitrate']) $audioBitrate = $options['webrtcAudioBitrate'];

				if ( $videoBitrate ) 
					$fields['videoBitrate'] = array(
						'name'        => 'videoBitrate',
						'description' => __( 'Maximum Video Bitrate', 'ppv-live-webcams' ),
						'details'     => __( 'Use this value or lower for video bitrate, depending on resolution. A static background and less motion requires less bitrate than movies, sports, games. For OBS Settings: Output > Streaming > Video Bitrate. Warning: Trying to broadcast higher bitrate than allowed by streaming server will result in disconnects/failures.', 'ppv-live-webcams' ),
						'type'        => 'text',
						'value'       => $videoBitrate,
					);

				if ( $audioBitrate )
					$fields['audioBitrate'] = array(
						'name'        => 'audioBitrate',
						'description' => __( 'Maximum Audio Bitrate', 'ppv-live-webcams' ),
						'details'     => __( 'Use this value or lower for audio bitrate. If you want to use higher Audio Bitrate, lower Video Bitrate to compensate for higher audio. For OBS Settings: Output > Streaming > Audio Bitrate. Warning: Trying to broadcast higher bitrate than allowed by streaming server will result in disconnects/failures.', 'ppv-live-webcams' ),
						'type'        => 'text',
						'value'       => $audioBitrate,
					);

			
			}
		}

		//vws limits
		if ($options['maxVideoBitrate'] ?? false) 
		$fields['videoBitrate'] = array(
			'name'        => 'videoBitrate',
			'description' => __( 'Maximum Video Bitrate', 'ppv-live-webcams' ),
			'details'     => __( 'Use this value or lower for video bitrate, depending on resolution. A static background and less motion requires less bitrate than movies, sports, games. For OBS Settings: Output > Streaming > Video Bitrate. Warning: Trying to broadcast higher bitrate than allowed by streaming server will result in disconnects/failures.', 'ppv-live-webcams'),
			'type'        => 'text',
			'value'       => $options['maxVideoBitrate'],
		);

		if ($options['maxAudioBitrate'] ?? false) 
		$fields['audioBitrate'] = array(
			'name'        => 'audioBitrate',
			'description' => __( 'Maximum Audio Bitrate', 'ppv-live-webcams' ),
			'details'     => __( 'Use this value or lower for audio bitrate. If you want to use higher Audio Bitrate, lower Video Bitrate to compensate for higher audio. For OBS Settings: Output > Streaming > Audio Bitrate. Warning: Trying to broadcast higher bitrate than allowed by streaming server will result in disconnects/failures.', 'ppv-live-webcams' ),
			'type'        => 'text',
			'value'       => $options['maxAudioBitrate'],
		);

		
		$fields['downloadLarixIOS'] = array(
			'name'        => 'downloadLarixIOS',
			'description' => __( 'Larix Broadcaster for iOS', 'ppv-live-webcams' ),
			'details'     => __( 'Larix Broadcaster free iOS app uses full power of mobile devices cameras to stream live content..', 'ppv-live-webcams' ),
			'type'        => 'link',
			'url'         => 'https://apps.apple.com/app/larix-broadcaster/id1042474385',
			'icon'        => 'apple',
			'color'       => 'grey',
		);
		
		$fields['downloadLarixAndroid'] = array(
			'name'        => 'downloadLarixAndroid',
			'description' => __( 'Larix Broadcaster for Android', 'ppv-live-webcams' ),
			'details'     => __( 'Larix Broadcaster free iOS app uses full power of mobile devices cameras to stream live content.', 'ppv-live-webcams' ),
			'type'        => 'link',
			'url'         => 'https://play.google.com/store/apps/details?id=com.wmspanel.larix_broadcaster',
			'icon'        => 'android',
			'color'       => 'green',
		);
		
		$fields['rtmpURL'] = array(
			'name'        => 'rtmpURL',
			'description' => __( 'RTMP URL', 'ppv-live-webcams' ),
			'details'     => __( 'Full RTMP URL / Larix URL: full streaming address. Contains: server, port if different than default 1935, application and control parameters, key, stream name. For Larix Broadcaster: Connections > URL.', 'ppv-live-webcams' ),
			'type'        => 'text',
			'value'       => $rtmpURL,
		);
		


/*
		$fields['downloadGoCoderIOS'] = array(
			'name'        => 'downloadGoCoderIOS',
			'description' => __( 'Download GoCoder for iOS', 'ppv-live-webcams' ),
			'details'     => __( 'The Wowza GoCoder™ app from Wowza Media Systems™ is a live audio and video capture and encoding application for iOS 8 and newer. Use the Wowza GoCoder app to broadcast HD-quality live events on the go from any location to any screen using H.264 adaptive bitrate streaming.', 'ppv-live-webcams' ),
			'type'        => 'link',
			'url'         => 'https://apps.apple.com/us/app/wowza-gocoder/id640338185',
			'icon'        => 'apple',
			'color'       => 'grey',
		);

		$fields['downloadGoCoderAndroid'] = array(
			'name'        => 'downloadGoCoderAndroid',
			'description' => __( 'Download GoCoder for Android', 'ppv-live-webcams' ),
			'details'     => __( 'The Wowza GoCoder™ app from Wowza Media Systems™ is a live audio and video capture and encoding application for Android 4.4 and later. Use the Wowza GoCoder app to broadcast HD-quality live events on the go from any location to any screen using H.264 adaptive bitrate streaming.', 'ppv-live-webcams' ),
			'type'        => 'link',
			'url'         => 'https://play.google.com/store/apps/details?id=com.wowza.gocoder&hl=en',
			'icon'        => 'android',
			'color'       => 'green',
		);
	*/	
		if ($options['lovense']) $fields['downloadLovense'] = array(
			'name'        => 'downloadLovense',
			'description' => __( 'Download Lovense', 'ppv-live-webcams' ),
			'details'     => __( 'If you have a Lovense toy, download the Lovense browser or extension to integrate with site.', 'ppv-live-webcams' ),
			'type'        => 'link',
			'url'         => 'https://www.lovense.com/r/sytsk',
			'icon'        => 'heart',
			'color'       => 'pink',
		);

		$configuration['rtmp_obs'] = array(
			'name'    => __( 'RTMP Encoder: OBS / Larix Broadcaster Settings', 'ppv-live-webcams' ),
			'details' => __( 'Use these settings to broadcast with a RTMP encoder app like OBS, iOS/Android Larix Broadcaster, xSplit, Zoom Meetings Webinars.', 'ppv-live-webcams' ),
			'fields'  => $fields,
		);

		$configuration['meta'] = array(
			'time'        => time(),
			'description' => __( 'Broadcast with external apps for advanced compositions, scenes, effects, higher streaming quality and reliability compared to browser based interface and protocols. External broadcasts have higher latency and improved capacity, reliability specific to HLS delivery method. New broadcasts show in about 10 seconds and unavailability updates after 1 minute.', 'ppv-live-webcams' ),
		);

		return $configuration;

	}


	static function appRoomMedia( $post, $session, $options ) {
		
		$media = get_post_meta( $post->ID, 'presentationMedia', true );

		// always Main to show default room stream
		if ( ! is_array( $media ) ) {
			$media = array( 'Main' => array( 'name' => 'Main' ) );
		}
		if ( ! count( $media ) ) {
			$media['Main'] = array( 'name' => 'Main' );
		}
		
		//stream hls (i.e. restream ip cameras)
		$streamMode = get_post_meta(  $post->ID, 'stream-mode', true);
		if ($streamMode == 'stream')
		{
			$streamName = get_post_meta( $post->ID, 'stream-name', true);		
			$media['Main'] = [ 'name' => $streamName, 'type' => 'hls', 'url' => ($options['httprestreamer'] ? $options['httprestreamer']  : $options['httpstreamer']) . $streamName . '/playlist.m3u8'  ];
		}

		// room slots
		$collaboration = self::collaborationMode( $post->ID, $options );
		$conference    = self::is_true( get_post_meta( $post->ID, 'room_conference', true ) );

		if ( $collaboration || $conference ) {
			$room_slots = intval( get_post_meta( $post->ID, 'room_slots', true ) );
		}
		if ( ! isset($room_slots) ) {
			$room_slots = 1;
		}

		$edited = 0;

		// always 1 if disabled
		if ( ! $collaboration && ! $conference ) {
			if ( $room_slots > 1 ) {
				$room_slots = 1;
				$edited     = 1;
			}
		}

		$items = 0;

		foreach ( $media as $placement => $content ) {
			if ( ++$items > $room_slots ) {
				unset( $media[ $placement ] ); // remove if too many
				$edited = 1;
			}
		}

		while ( count( $media ) < $room_slots ) {
			$media[ 'Slot' . ++$items ] = array(
				'name' => 'Slot' . $items,
				'type' => 'empty',
			); // add if missing
			$edited                     = 1;
		}

		// remove missing auto sessions

		global $wpdb;
		$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

		$items = 0;
		foreach ( $media as $placement => $content ) {
			++$items;

			if ( array_key_exists( 'auto', $content ) || ( isset($content['type']) && $content['type'] == 'user' )) {
				$sql = $wpdb->prepare("SELECT COUNT(*) as n FROM `$table_sessions` WHERE uid=%d AND username=%s AND status=0", $content['userID'], $content['userName']);

				if ( ! $wpdb->get_var( $sql ) ) {
					$media[ $placement ] = array(
						'name'    => 'Slot' . $items,
						'type'    => 'empty',
						'by'      => 'appRoomMedia()',
						'comment' => $content['userName'] . '/' . $content['userID'] . ' offline',
					); // empty slot
					$edited              = 1;
				}
			}
		}

		if ( $edited ) {
			update_post_meta( $post->ID, 'presentationMedia', $media );
			update_post_meta( $post->ID, 'updated_media', time() );
		}

		return $media;
	}


	static function appFail( $message = 'Request Failed', $response = null, $errorMore = '', $errorURL = '' ) {
		// bad request: fail

		if ( ! $response ) {
			$response = array();
		}

		$response['error'] = $message;

		$response['VideoWhisper'] = 'https://videowhisper.com';

		if ( $errorMore ) {
			$response['errorMore'] = $errorMore;
			$response['errorURL']  = $errorURL;
		}

		echo json_encode( $response );

		die();
	}


	static function appUserHLS( $username, $options ) {

		if ( $options['rtmpServer'] == 'videowhisper' )
		{
			//pin protected playback
			$playbackPin = trim($options['playbackPin']);

			if ($options['videowhisperStream'] && $options['videowhisperStream'] != 'broadcast') //user the broadcaster's playback pin
			{
				//get user id by slug
				$user = get_user_by( 'slug', $username );

				$playbackPin = get_user_meta($user->ID, 'playbackPin', true);
				if (!$playbackPin)
				{
					$playbackPin = self::generatePin();
					update_user_meta($user->ID, 'playbackPin', $playbackPin);
				}
			}

			return trim($options['videowhisperHLS']) .'/' . trim($options['vwsAccount']). '/'. $username . '/index.m3u8?pin=' . $playbackPin;
		} 

		//default wowza se stream
		return $options['httpstreamer'] . $username . '/playlist.m3u8';

	}
	
	static function appRoomMessages( $postID, $options, $userID = 0, $topicID = 0, $trim = 1, $url = 1, $performer = 0)
	{
		//paid questions and messages
		global $wpdb;
		$table_messages = $wpdb->prefix . 'vw_vmls_messages';
		
		$sql = "SELECT * FROM $table_messages WHERE webcam_id = %d AND reply_id = %d";
		$params = [$postID, $topicID];
		
		if ($userID) {
			$sql .= " AND sender_id = %d";
			$params[] = $userID;
		}
		
		$sql .= " ORDER BY sdate DESC, id DESC LIMIT 0, 100";
		
		$prepared_sql = $wpdb->prepare($sql, ...$params);
		$results = $wpdb->get_results($prepared_sql);
		
		
		$messages = [];
		
		if ( $wpdb->num_rows > 0 ) foreach ( $results as $message ) 
		{
				$mgs = [];
			
				$msg['ID'] = $message->id;
				$msg['userID'] = $message->sender_id;
				$msg['time'] = intval( $message->sdate * 1000 );
				
				$sender     = get_userdata( $message->sender_id );

				$msg['userName'] = $sender ? $sender->user_nicename : '_Unknown' . $message->sender_id  ;
				
				$msg['text'] = html_entity_decode(wp_strip_all_tags( $trim ? wp_trim_excerpt( $message->message ) : $message->message ));
				
				// replies
				$sqlC         = $wpdb->prepare("SELECT COUNT(id) FROM `$table_messages` WHERE reply_id=%d", $message->id);
				$msg['count'] = intval( $wpdb->get_var( $sqlC ) );

				//url depending on user type
				if ($performer) $pid = $options['p_videowhisper_webcams_performer'];
				else $pid = $options['p_videowhisper_webcams_client'];
				
				if ($url) $msg['url'] = add_query_arg(
					array(
						'view'    => 'messages',
						'messages'   => 'view',
						'message' => self::to62( $message->id ),
					),
					get_permalink( $pid )
				);
				
				//all replies
				$msg['replies'] = self::appRoomMessages( $postID, $options, 0, $message->id, 0, 0, $performer);

				//
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
				
				$msg['attachmentsCount'] = $attachmentsCount;
				$msg['recordingsCount'] = $recordingsCount;

				//add to list
				$messages[] = $msg;
 		}
		
		return $messages;
	}


	static function appRoomUsers( $post, $options ) {

		$webStatusInterval = $options['webStatusInterval'];
		if ( $webStatusInterval < 10 ) {
			$webStatusInterval = 60;
		}

		global $wpdb;
		$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

		$ztime = time();


		$rolesModerator = explode( ',', $options['roleModerators'] );
		foreach ( $rolesModerator as $key => $value ) $rolesModerator[ $key ] = trim( $value );

		// update room user list
		$items = array();

		$sql     = $wpdb->prepare("SELECT * FROM `$table_sessions` WHERE rid=%d AND status = 0 ORDER BY broadcaster DESC, username ASC", $post->ID);
		$sqlRows = $wpdb->get_results( $sql );

		$no = 0;
		if ( $wpdb->num_rows > 0 ) {
			foreach ( $sqlRows as $sqlRow ) {
				if ( $sqlRow->meta ) {
					$userMeta = unserialize( $sqlRow->meta );
				}
				if ( ! is_array( $userMeta ) ) {
					$userMeta = array();
				}

				$roomMeta = unserialize( $sqlRow->roptions );
				if ( ! is_array( $roomMeta ) ) {
					$roomMeta = array();
				}

				$item             = array();
				$item['userID']   = intval( $sqlRow->uid );
				$item['userName'] = sanitize_file_name( $sqlRow->username );
				if ( ! $item['userName'] ) {
					$item['userName'] = '#' . $sqlRow->uid;
				}

				$item['sdate']   = intval( $sqlRow->sdate );
				$item['updated'] = intval( $sqlRow->edate );
				$item['avatar']  = get_avatar_url( $sqlRow->uid, array( 'default' => dirname( plugin_dir_url( __FILE__ ) ) . '/images/avatar.png' ) );

				// buddyPress profile url
				$bp_url = '';
				if ( function_exists( 'bp_members_get_user_url' ) ) {
					$bp_url = bp_members_get_user_url( $sqlRow->uid );
				}

				if ( $sqlRow->broadcaster ) {
					$url = $options['performerProfile'] ? $options['performerProfile'] . urlencode( $sqlRow->username ) : ( $bp_url ? $bp_url : get_author_posts_url( $sqlRow->uid ) );
				} else {
					$url = $options['clientProfile'] ? $options['clientProfile'] . urlencode( $sqlRow->username ) : $bp_url;
				}

				$item['url'] = $url;

				if ( array_key_exists( 'privateUpdate', $userMeta ) ) {
					if ( $ztime - intval( $userMeta['privateUpdate'] ) < $options['onlineTimeout'] ) {
						$item['hide'] = true; // in private
					}
				}
				
				//hide moderators
				$isModerator = self::isModerator($sqlRow->uid, $options, null, $rolesModerator);
				if ( $isModerator ) $item['hide'] = true;
				 
				// if ($ztime - intval($sqlRow->edate) < $options['onlineTimeout']) $item['hide'] = true; //offline

				if ( array_key_exists( 'userMode', $roomMeta ) ) {
					if ( $roomMeta['userMode'] == 'voyeur' ) {
						$item['hide'] = true; // voyeur
					}
				}

				if ( $sqlRow->broadcaster ) {

					// updated external broadcast info
					if ( array_key_exists( 'externalUpdate', $userMeta ) ) {
						if ( $userMeta['externalUpdate'] < time() - $webStatusInterval ) {
							$userMeta['external']        = false; // went offline?
							$userMeta['externalTimeout'] = true; // went offline?
						}
					}

					if ( array_key_exists( 'external', $userMeta ) ) {
						if ( $userMeta['external'] ) {
							$item['hls'] = self::appUserHLS( $sqlRow->username, $options );
						}
					}
				}
				
				//language
				$language = $options['languageDefault'];
				if (!$language) $language = 'en-us'; //default

				if ($sqlRow->uid) $language = get_user_meta( $sqlRow->uid, 'h5v_language', true );
				$item['language'] = $language;
				$item['flag'] = self::language2flag( $language );	

				// include updated user meta
				$item['meta'] = $userMeta;

				$item['order'] = ++$no;

				$item['session_id'] = $sqlRow->id;

				$ix = $sqlRow->uid;
				if ( ! $ix ) {
					$ix = $sqlRow->id + 100000000;
				}

				$items[ $ix ] = $item;
			}
		} else {
			$item['userID']          = 0;
			$item['userName']        = 'ERROR_empty';
			$item['sql']             = $sql;
			$item['wpdb-last_error'] = $wpdb->last_error;
			$item['sdate']           = 0;
			$item['updated']         = 0;
			$item['meta']            = array();
			$items[0]                = $item;
		}

			return $items;
	}

	static function closeBooth($session, $otherSession)
	{
		if (!$session) return;
		if (!$otherSession) return;
		
		global $wpdb;
		$table_private  = $wpdb->prefix . 'vw_vmls_private';
		
		$ztime = time();
		if ($session->broadcaster) {
			$sqlU = $wpdb->prepare("UPDATE `$table_private` SET status='1', pedate=%d WHERE pid=%d AND status='0' AND cid=%d", $ztime, $session->uid, $otherSession->uid);
		} else {
			$sqlU = $wpdb->prepare("UPDATE `$table_private` SET status='1', cedate=%d WHERE pid=%d AND status='0' AND cid=%d", $ztime, $otherSession->uid, $session->uid);
		}
		
		$wpdb->query($sqlU);
	}

	static function resetWatch($session, $resetID = true)
	{
			if (!$session) return;
			
			
			global $wpdb;
			$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';
	
			//update client session			
			$userMeta = unserialize($session->meta);
			if (!is_array($userMeta)) $userMeta = [];
			if ($resetID) $userMeta['watch'] = 0;
			$userMeta['watchConfirm'] = 0;
			$userMeta['watchNexted'] = 0;
			$userMeta['watchNextedConfirm'] = 0;
			$userMeta['watchLeft'] = 0;
			$userMetaS = serialize($userMeta);

		$sqlU = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $session->id);
		$wpdb->query($sqlU);

	}
	
	// !App Ajax handlers
	static function vmls_app() {

		$options = self::getOptions();

		if (VW_H5V_DEVMODE || $options['debugMode']) ini_set('display_errors', 1);
		
		
		// Clean the output buffer
		if (ob_get_length()) ob_clean();


		// D: login, public room (1 w broadcaster/viewer), 2w private vc, status
		// TD: tips

		global $wpdb;
		$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';
		$table_chatlog  = $wpdb->prefix . 'vw_vmls_chatlog';
		$table_actions  = $wpdb->prefix . 'vw_vmls_actions';
		$table_messages = $wpdb->prefix . 'vw_vmls_messages';
		$table_private  = $wpdb->prefix . 'vw_vmls_private';

		// all strings - comment echo in prod:
		if ( VW_H5V_DEVMODE ) {
			$response['post'] = serialize( $_POST );
		}
		if ( VW_H5V_DEVMODE ) {
			$response['get'] = serialize( $_GET );
		}

		$http_origin              = get_http_origin();
		$response['http_origin']  = $http_origin;
		$response['VideoWhisper'] = 'https://videowhisper.com';

		$task    = sanitize_file_name( $_POST['task'] ?? 'NoTask');
		$devMode = self::is_true( $_POST['devMode'] ?? false ); // app in devMode
		$roomID = 0;
		
		$requestUID = intval( $_POST['requestUID'] ?? 0 ); // directly requested private call

		// originally passed trough window after creating session
		// urlvar user_id > php var $userID

		// session info received trough VideoWhisper POST var
		$VideoWhisper = isset( $_POST['VideoWhisper'] ) ? (array) $_POST['VideoWhisper'] : ''; //sanitized per item
		if ( $VideoWhisper ) {
			$userID     = intval( $VideoWhisper['userID'] );
			$sessionID  = intval( $VideoWhisper['sessionID'] );
			$roomID     = intval( $VideoWhisper['roomID'] );
			$sessionKey = intval( $VideoWhisper['sessionKey'] );

			$privateUID   = intval( $VideoWhisper['privateUID'] ?? 0 ); // in private call
			$roomActionID = intval( $VideoWhisper['roomActionID'] ?? 0 );
		} else
		{
			$userID = 0;
		}

		if ( VW_H5V_DEVMODE ) {
			ini_set( 'display_errors', 1 );
			error_reporting( E_ALL & ~E_NOTICE & ~E_STRICT );
		}

		// devMode: assign a default user/room if not provided, only when app is in devMode		
		if ( VW_H5V_DEVMODE ) {
			if ( $devMode ) {
				// setup specific
				if ( ! $userID ) {
					$userID = 1; // 2136 on demo.paidvideochat.com
				}
				if ( ! $roomID ) {
					$roomID = 130; //5540 on demo.paidvideochat.com , 130 on videolivesupport.com
				}

				if ( VW_H5V_DEVMODE_CLIENT ) {
					if ( $userID == 2136 ) {
						$roomID = 13; // different room => client, only in devMode
					}
				}
			}
		}

		// room is post
		$postID      = $roomID;
		$public_room = array();

		$post = get_post( $roomID );
		if ( ! $post ) {
			self::appFail( 'Room post not found: ' . $roomID . ' Server DEVMODE? VideoWhisper:' .  json_encode($VideoWhisper) );
		}
		$roomName    = $post->post_title;
		$changedRoom = 0;

		// Handling the supported tasks:

		$response['task'] = $task;

		// handle auth / session

		if ( $task != 'login' ) {
			// check session
			if ( ! $session = self::sessionValid( $sessionID, $userID ) ) {
				
				$debugInfo = ''; 
							
				if ($options['debugMode']) 
				{
				
					$debugInfo = 'Debug Info: '; 
					$debugInfo .= 'App Session #' . $sessionID . ' User #' . $userID . ' Room #' . $roomID . ' ' . $task;

					$sqlS    = $wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d AND uid=%d LIMIT 1", $sessionID, $userID);
					$session = $wpdb->get_row( $sqlS );

					if ($session) $debugInfo .= 'Session is no longer open. Status #' . $session->status . ' Last updated: ' . ( $session->edate ? date( 'F j, Y, g:i a', $session->edate) : '-' ) . ' Session Data: ' . serialize( $session );
					else $debugInfo .= 'Session with that ID was not found.'; 
				}
				
				self::appFail( __( 'Invalid Session. Occurs if browser tab gets paused in background or room type changes and new terms apply. Reload to start a new session!', 'ppv-live-webcams' ) . $debugInfo );

			}

			// update online for viewer
			if ( ! $session->broadcaster ) {
				if ( $disconnect = self::updateOnlineViewer( sanitize_file_name( $session->username ), $roomName, $postID, 11, '', $options, $userID ) ) {
					switch ( $disconnect ) {
						case __( 'Free chat daily visitor time limit reached: Register and login for more chat time today!', 'ppv-live-webcams' ):
						case __( 'Only registered and logged in users can access paid sessions.', 'ppv-live-webcams' ):
							$errorMore = __( 'Register', 'ppv-live-webcams' );
							$errorURL  = get_permalink( $options['p_videowhisper_register'] );
							break;

						case __( 'Not enough funds left for client to continue group chat session.', 'ppv-live-webcams' ):
							$errorMore = __( 'Wallet', 'ppv-live-webcams' );
							$errorURL  = get_permalink( $options['balancePage'] );
							break;

						case __( 'You are banned from accessing this room!', 'ppv-live-webcams' ):
						case __( 'Free chat daily time limit reached: You can only access paid group rooms today!', 'ppv-live-webcams' ):
						default:
							$errorMore = __( 'Webcams', 'ppv-live-webcams' );
							$errorURL  = get_permalink( $options['p_videowhisper_webcams'] );
					}

					self::appFail( 'Viewer disconnected: ' . urldecode( $disconnect ), null, $errorMore, $errorURL );
				}
			}

			if ( $session->broadcaster ) {
				self::updateOnlineBroadcaster( $sessionID );
			}

			// retreive user meta from session
			if ( $session->meta ) {
				$userMeta = unserialize( $session->meta );
			}
			
			if ( ! is_array( $userMeta ) ) {
				$userMeta = array();
			}

				// set session username
				$userName = sanitize_file_name( $session->username );

			if ( VW_H5V_DEVMODE || $options['debugMode']) {
				// retrieve room info
				$groupCPM     = get_post_meta( $postID, 'groupCPM', true );
				$performer    = get_post_meta( $postID, 'performer', true );
				$sessionStart = get_post_meta( $postID, 'sessionStart', true );
				$checkin      = get_post_meta( $postID, 'checkin', true );
				$privateShow  = get_post_meta( $postID, 'privateShow', true );

					$response['_dev']['clientGroupCost'] = self::clientGroupCost( $session, $groupCPM, $sessionStart );
					$response['_dev']['groupCPM']        = $groupCPM;
					$response['_dev']['sessionStart']    = $sessionStart;
					$response['_dev']['session']         = $session;
					$response['_dev']['userMeta'] 		 = $userMeta;

			}

			$isPerformer = self::isPerformer( $userID, $roomID );
			
					
			//in random match, not login
			$room_random = self::is_true( get_post_meta( $post->ID, 'room_random', true ) );

			if ($room_random)
			{
				

							
			//check if booth is still open 
			if ($session->broadcaster) {
				$sqlS = $wpdb->prepare("SELECT * FROM `$table_private` WHERE `call`='2' AND status='0' AND `pid`=%d AND performer=%s AND rid=%d ORDER BY pedate DESC LIMIT 1", $userID, $userName, $roomID);
			} else {
				$sqlS = $wpdb->prepare("SELECT * FROM `$table_private` WHERE `call`='2' AND status='0' AND `cid`=%d AND client=%s AND rid=%d ORDER BY cedate DESC LIMIT 1", $userID, $userName, $roomID);
			}
			$private = $wpdb->get_row($sqlS);

			//for client should be available, for broadcaster reacted on random
			if (!$private) if ($session->broadcaster) $response['room'] = self::appRandomRoom( $post, $session, $options, "\n No other open booth was available.", true, true);
							else 
							{
	
						//booth closed: auto client next
		
						//close client session
						$sqlU2 = $wpdb->prepare("UPDATE `$table_sessions` SET status=%d, edate=%d WHERE id=%d", 1, $ztime, $sessionID);
						$wpdb->query($sqlU2);
									
						//create new session for client
						$nextSession   = self::sessionUpdate( $userName, 'Matching..', 0, 11, 0, 1, 1, $options, $userID, -1, self::get_ip_address(), $userMeta );		
												
							if (!$nextSession) 
							{
								 $response['error'] = 'Booth was closed. Could not create new client session. Try reloading!';
							}
							else
							{
								
								$nextMatchID = self::nextMatch($nextSession, $options, $debugInfo);
								$nextSessionID = $nextSession->id;

								if ($nextMatchID) 
								{
									
									//update session and room
									$nextSession = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_sessions` WHERE id=%d", $nextSessionID));
									if ($nextSession->rid) $post = get_post($nextSession->rid);
									$postID = $post->ID;
									
									$response['room'] = self::appRandomRoom( $post, $nextSession, $options, "\n " . __( 'Booth was closed: matched with a new performer.', 'ppv-live-webcams' ) . ' S#' . $nextSession->id . ' P#' . $nextMatchID);
								}
								else 
								{
									
								$response['error'] = 'Booth was closed and no available match was found, yet. No booth to display. Reload to try finding a new match.' . ( $options['debugMode'] ? "S#$sessionID R#$roomID SQL: $sqlS" : '' );
								$response['errorTask'] = 'next';
									
								}
							}

						
						// update user session
							$response['user'] = array(
								'from'		=> 'next_closed',
								'ID'        => intval( $userID ),
								'name'      => $userName,
								'sessionID' => intval( $nextSession->id ),
								'boothID' 	=> intval( $nextMatchID ),
								'loggedIn'  => true,
								'balance'   => number_format( self::balance( $userID, true, $options ), 2, '.', ''  ),
								'cost'      => ( array_key_exists( 'cost', $userMeta ) ? $userMeta['cost'] : 0 ),
								'avatar'    => get_avatar_url( $userID, array( 'default' => dirname( plugin_dir_url( __FILE__ ) ) . '/images/avatar.png' ) ),
							);


								$session     = $nextSession;
								$sessionID = $nextSession->id;

								
									echo json_encode( $response );
									die();
		
								////
							}
			
			
			//if ($private) if ($private->cid && !$userMeta['watch'] && $session->broadcaster ) $response['room'] = self::appRandomRoom( $post, $session, $options, "\n Match no longer available. cid:" . $private->cid, true, true);
			
			if ( $options['debugMode']) $response['_dev']['booth'] = $private;
		
			
		
			//update match 
			if ($private) 
			{
				$disconnectPrivate = self::privateSessionUpdate( $session, $post, $isPerformer, $privateUID, $options, 0, $privateSession );
				if ($disconnectPrivate ?? false) $response['error'] = $disconnectPrivate;
			}
	
			//watch available and not applied: update room with streaming settings
			if (! isset($response['room']) ) if (! ($userMeta['watchConfirm'] ?? false) && ($userMeta['watch'] ?? false) )
			{
				$userMeta['watchConfirm'] = 1;
				$userMetaS = serialize($userMeta);

				//update session
				$wpdb->query($wpdb->prepare("UPDATE `$table_sessions` set meta=%s WHERE id=%d", $userMetaS, $sessionID));
				$session = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d", $sessionID));
				$otherSession = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d", intval($userMeta['watch'])));

				$response['room'] = self::appRandomRoom( $post, $session, $options, "\n 👤 User Matched: " . $otherSession->username . ' S#' . $otherSession->id );
				
				//make sure room is busy after match
				//update_post_meta($post->ID, 'match_available', 0);
			}
			
			//check if other nexted
			if ( !isset($response['room']) ) if ( ($userMeta['watchNexted'] ?? false) && !( $userMeta['watchNextedConfirm'] ?? false))
			{
				$userMeta['watchNextedConfirm'] = 1;
				$userMetaS = serialize($userMeta);

				//update session
				$wpdb->query($wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $sessionID));
				$session = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d", $sessionID));
				$otherSession = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d", intval($userMeta['watch'])));
				
				if ($session->broadcaster) $response['room'] = self::appRandomRoom( $post, $session, $options, "\n 👤 User Nexted: " . $otherSession->username  . ' Moved to new booth.', true, true );		
				else $response['room'] = self::appRandomRoom( $post, $session, $options, "\n 👤 User Nexted: " . $otherSession->username  . ' Use Next button.' );
				
			}
			
			//check if other still online
			if (!isset($response['room']) ) if ( $userMeta['watch'] ?? false ) //watching
			{
				$otherSession = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d", intval($userMeta['watch'])));
				
				if ( !$otherSession || ( $otherSession->edate && $otherSession->edate < time() -  max($options['onlineTimeout'], 10 ) ) ||  $otherSession->status ) 
				{
					$debugInfo = '';
					if ($options['debugMode']) {
						$debugInfo .= ' Last S#' . print_r($wpdb->get_var($wpdb->prepare("SELECT id FROM $table_sessions WHERE uid = %d ORDER BY edate DESC LIMIT 1", $otherSession->uid)), true);
					}
				
					//self::autoMessage( 'Match session no longer active: '. $otherSession->username . ' #' . $otherSession->id. ' Use Next button.' . $debugInfo, $session, $privateUID );
					
					$userMeta['watchLeft'] = 1;
					$userMetaS = serialize($userMeta);
					
					//update session
					$wpdb->query($wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $sessionID));
					$session = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d", $sessionID));
					self::closeBooth($session, $otherSession);
				
					if ($session->broadcaster) $response['room'] = self::appRandomRoom( $post, $session, $options, "\n 👤 User Left: " . $otherSession->username  . ' Moved to new booth.', true, true );
					else  $response['room'] = self::appRandomRoom( $post, $session, $options, "\n 👤 User Left: " . $otherSession->username  . ' Use Next button.' );
					
				}		
	
			}


			//random room
			}
			
		//if !login
		}

		if ( $task == 'login' ) {
			// retrieve wp info
			$user = get_userdata( $userID );
			if ( ! $user ) {
								$isVisitor = 1;
				// self::appFail('User not found: ' . $userID);

				if ( $_COOKIE['htmlchat_username'] ?? false ) {
					$userName = sanitize_file_name( $_COOKIE['htmlchat_username'] );
				} else {
					$userName = 'G_' . base_convert( time() % 36 * rand( 0, 36 * 36 ), 10, 36 );
					// setcookie('htmlchat_username', $userName); // set in init()
				}

				$isPerformer = 0;

			} else {
				$isPerformer = self::isPerformer( $userID, $roomID );
				if ( $isPerformer ) {
					$userName = self::performerName( $user, $options );
				} else {
					$userName = self::clientName( $user, $options );
				}
			}

			// set/get room performer details
			if ( $isPerformer ) {
				update_post_meta( $postID, 'performer', $userName );
				update_post_meta( $postID, 'performerUserID', $userID );
			}

			// dev auto create session (at web login on production)
			if ( VW_H5V_DEVMODE ) {
				if ( ! isset($sessionID) ) {
					$session   = self::sessionUpdate( $userName, $roomName, $isPerformer, 11, 0, 1, 1, $options, $userID, $roomID, self::get_ip_address(), $userMeta  ?? [] );
					$sessionID = $session->id;

					$response['_dev']['isPerformer'] = $isPerformer;
					$response['_dev']['session']     = $session;
				}
			}

			if ( ! $session = self::sessionValid( $sessionID, $userID ) ) {
				self::appFail( 'Login session failed: s#' . $sessionID . ' u#' . $userID . ' Cache plugin may prevent access to this dynamic content.' );
			}

			// session valid, login

			// retreive user meta from session
			if ( $session->meta ) {
				$userMeta = unserialize( $session->meta );
			}
			if ( ! is_array( $userMeta ) ) {
				$userMeta = array();
			}

			// reset user preferences
			if ( $userID ) {
				if ( is_array( $options['appSetup'] ) ) {
					if ( array_key_exists( 'User', $options['appSetup'] ) ) {
						if ( is_array( $options['appSetup']['User'] ) ) {
							foreach ( $options['appSetup']['User'] as $key => $value ) {
								$optionCurrent = get_user_meta( $userID, $key, true );

								if ( empty( $optionCurrent ) || $options['appOptionsReset'] ) {
									update_user_meta( $userID, $key, $value );
								}
							}
						}
					}
				}
			}

					$balance        = floatval( self::balance( $userID, false, $options ) ); // final only, not temp
					$balancePending = floatval( self::balance( $userID, true, $options ) ); // temp

					// user session parameters and info, updates
					$response['user'] = array(
						'from' => 'login',
						'ID'             => intval( $userID ),
						'name'           => $userName,
						'sessionID'      => intval( $sessionID ),
						'loggedIn'       => true,
						'balance'        => number_format( $balance, 2, '.', ''  ),
						'balancePending' => number_format( $balancePending, 2, '.', ''  ),
						'time'           => ( $session->edate - $session->sdate ),
						'cost'           => ( array_key_exists( 'cost', $userMeta ) ? $userMeta['cost'] : 0 ),
						'avatar'         => get_avatar_url( $userID, array( 'default' => dirname( plugin_dir_url( __FILE__ ) ) . '/images/avatar.png' ) ),
					);

					if ( $balance < 0 ) {
						$response['error'] = 'Error: Negative balance (' . $balance . '). Can be a result of enabling cache for user requests. Contact site administrator to review activity and adjust balance.';
					}

					$response['user']['options'] = self::appUserOptions( $session, $options );

					// on login check if any private request was active to restore

					$sql     = $wpdb->prepare("SELECT * FROM `$table_actions` WHERE room_id=%d AND action = %s AND status > %d AND status < %d AND (user_id=%d OR target_id=%d) ORDER BY mdate DESC LIMIT 0, 1", $roomID, 'privateRequest', 4, 7, $session->uid, $session->uid);
					$pAction = $wpdb->get_row( $sql );

					// $response['sqlActions'] = $sql;
					$response['pAction'] = $pAction;

					if ( $pAction ) {
						$actionID   = $pAction->id;
						$privateUID = 0;
						if ( $pAction->user_id == $userID ) {
							$privateUID = $pAction->target_id;
						}
						if ( $pAction->target_id == $userID ) {
							$privateUID = $pAction->user_id;
						}

						// disable other similar actions to prevent confusion and duplicate requests
						$sqlU = $wpdb->prepare("UPDATE `$table_actions` SET status = %d WHERE room_id = %d AND action = %s AND status < %d AND id <> %d", 11, $roomID, 'privateRequest', 7, $pAction->id);
						$wpdb->query($sqlU);
					}
					
					
					
					//on login
					$room_random = self::is_true( get_post_meta( $post->ID, 'room_random', true ) );
					if ($room_random)
					{
						
						if ($session->broadcaster) {
							$sqlS = $wpdb->prepare("SELECT * FROM `$table_private` WHERE `call`='2' AND status='0' AND `pid`=%d AND performer=%s AND rid=%d ORDER BY pedate DESC LIMIT 1", $userID, $userName, $roomID);
						} else {
							$sqlS = $wpdb->prepare("SELECT * FROM `$table_private` WHERE `call`='2' AND status='0' AND `cid`=%d AND client=%s AND rid=%d ORDER BY cedate DESC LIMIT 1", $userID, $userName, $roomID);
						}				
						$pSession = $wpdb->get_row( $sqlS );
						
						//for client should be available, for broadcaster reacted on random
						if ($pSession) 
						{
							//random match call on login
							$response['room'] = self::appRandomRoom( $post, $session, $options, "\n Logged into booth P#" . $pSession->id);	
			
						}
						elseif ($session->broadcaster) $response['room'] = self::appRandomRoom( $post, $session, $options, "\n Logged into new booth.", true, true );
							else 
							{
								$response['error'] = 'Login into booth not possible (closed). Reload to find a new match.' . ( $options['debugMode'] ? "S#$sessionID R#$roomID SQL: $sqlS" : '' );
								$response['errorTask'] = 'next';
							}
			
			
						if ($session->broadcaster) $privateUID =  $pSession->cid;
						else $privateUID =  $pSession->pid;
											
						$room['privateUID']	= $privateUID;				
					
					//if $room_random
					} elseif ( $privateUID ?? false ) {
						//private calln
						$response['room'] = self::appPrivateRoom( $post, $session, $privateUID, $actionID, $options ); // private room restore
					} else {
						//public room
						$response['room'] = self::appPublicRoom( $post, $session, $options, '', $public_room, $requestUID ); // public room or lobby
					}

					// config params, const
					$response['config'] = array(
						'serverType' => $options['webrtcServer'], //wowza/videowhisper/auto (auto will use videowhisper in privates, wowza in group streams)
						'vwsSocket' => $options['vwsSocket'],
						'vwsToken' => $options['vwsToken'],
						'wss'              => $options['wsURLWebRTC'],
						'application'      => $options['applicationWebRTC'],
						'videoCodec'       => $options['webrtcVideoCodec'],
						'videoBitrate'     => $options['webrtcVideoBitrate'], //host
						'maxBitrate'       => $options['webrtcVideoBitrate'], //host
						'audioBitrate'     => $options['webrtcAudioBitrate'], //host
						'audioCodec'       => $options['webrtcAudioCodec'],
						'autoBroadcast'    => false,
						'actionFullscreen' => true,
						'actionFullpage'   => false,
						'serverURL'        => $ajaxurl = admin_url() . 'admin-ajax.php?action=vmls_app',
						'serverURL2'	   => plugins_url('ppv-live-webcams/server/'), //fast server
						'multilanguage'    =>  $options['multilanguage'] ? true : false,
						'translations' 	   => ( $options['translations'] == 'all' ? true : ( $options['translations'] == 'registered' ? is_user_logged_in() : false )  ),
						'languages'    	   => self::languageField($userID, $options),
						'logo'		   	 => trim($options['appLogo'] ?? ''),
						'modeVersion'	=> trim($options['modeVersion'] ?? ''),
					);

					// appMenu
					if ( $options['appSiteMenu'] > 0 ) {
						$menus = wp_get_nav_menu_items( $options['appSiteMenu'] );
						// https://developer.wordpress.org/reference/functions/wp_get_nav_menu_items/

						$appMenu = array();
						if ( is_array( $menus ) ) {
							if ( count( $menus ) ) {
								$k = 0;
								foreach ( (array) $menus as $key => $menu_item ) {
									if ( $menu_item->ID ) {
																		$k++;
																		$appMenuItem             = array();
																		$appMenuItem['title']    = $menu_item->title;
																		$appMenuItem['url']      = $menu_item->url;
																		$appMenuItem['ID']       = intval( $menu_item->ID );
																		$appMenuItem['parentID'] = intval( $menu_item->menu_item_parent );
																		$appMenu[]               = $appMenuItem;
									}
								}

								$appMenu[] = array(
									'title'    => 'END',
									'ID'       => 0,
									'parentID' => 0,
								); // menu end (last item ignored by app)

								if ( $k ) {
									$response['config']['siteMenu'] = $appMenu;
								}
							}
						}
					}

					// set default config options, in case not configured
					$optionsDefault = self::adminOptionsDefault();
					if ( is_array( $optionsDefault['appSetup'] ) ) {
						if ( array_key_exists( 'Config', $optionsDefault['appSetup'] ) ) {
							if ( is_array( $optionsDefault['appSetup']['Config'] ) ) {
								foreach ( $optionsDefault['appSetup']['Config'] as $key => $value ) {
									$response['config'][ $key ] = $value;
								}
							}
						}
					}

					// pass app setup config parameters, overwrites defaults
					if ( is_array( $options['appSetup'] ) ) {
						if ( array_key_exists( 'Config', $options['appSetup'] ) ) {
							if ( is_array( $options['appSetup']['Config'] ) ) {
								foreach ( $options['appSetup']['Config'] as $key => $value ) {
									$response['config'][ $key ] = $value;
								}
							}
						}
					}
					
					// translations
					$response['config']['text'] = self::appText();

					$response['config']['sfx'] = self::appSfx();

					$response['config']['exitURL'] = ( $url = get_permalink( $options['p_videowhisper_webcams'] ) ) ? $url : get_site_url();

					$response['config']['balanceURL'] = ( $url = get_permalink( $options['balancePage'] ) ) ? $url : get_site_url();
					if ( $options['balancePage'] == -1 ) {
						$response['config']['balanceURL'] = '';
					}


					//enforce wowza host limits to prevent stream rejection
					if ($response['config']['videoBitrate'] > $options['webrtcVideoBitrate']) $response['config']['videoBitrate'] = $options['webrtcVideoBitrate'];
					if ($response['config']['maxBitrate'] > $options['webrtcVideoBitrate']) $response['config']['maxBitrate'] = $options['webrtcVideoBitrate'];
					if ($response['config']['audioBitrate'] > $options['webrtcAudioBitrate']) $response['config']['audioBitrate'] = $options['webrtcAudioBitrate'];

					//enforce vws host limits to prevent stream rejection
					if ($options['maxVideoBitrate'] ?? false) if ($response['config']['videoBitrate'] > $options['maxVideoBitrate']) $response['config']['videoBitrate'] = $options['maxVideoBitrate'];
					if ($options['maxVideoBitrate'] ?? false) if ($response['config']['maxBitrate'] > $options['maxVideoBitrate']) $response['config']['maxBitrate'] = $options['maxVideoBitrate'];
					if ($options['maxAudioBitrate'] ?? false) if ($response['config']['audioBitrate'] > $options['maxAudioBitrate']) $response['config']['audioBitrate'] = $options['maxAudioBitrate'];

					if ($options['maxHeigh'] ?? false) 
					{
						if ($response['config']['resolutionHeight'] > $options['maxHeigh']) $response['config']['resolutionHeight'] = $options['maxHeigh'];
						if ($response['config']['maxResolutionHeight'] > $options['maxHeigh']) $response['config']['maxResolutionHeight'] = $options['maxHeigh'];
					}

					if ($options['maxFramerate'] ?? false)  if ($response['config']['frameRate'] > $options['maxFramerate']) $response['config']['frameRate'] = $options['maxFramerate'];

					if (!isset($response['config']['snapshotInterval']) || $response['config']['snapshotInterval']< 10 ) $response['config']['snapshotInterval'] = 180;

					if (!is_user_logged_in()) if (!VW_H5V_DEVMODE) 
					{
						if ($options['timeIntervalVisitor']) $response['config'][ 'timeInterval' ] = intval($options['timeIntervalVisitor']);
						$response['config'][ 'recorderDisable' ] = true;
					}
						// devmode: do not auto broadcast
					if ( VW_H5V_DEVMODE ) {
						$response['config']['cameraAutoBroadcast'] = '0';
						// $response['config']['videoAutoPlay '] = '0';

					}

					if ( ! $isPerformer ) {
						if ( array_key_exists( 'cameraAutoBroadcastAll', $response['config'] ) ) {
							$response['config']['cameraAutoBroadcast'] = $response['config']['cameraAutoBroadcastAll'];
						} else {
							$response['config']['cameraAutoBroadcast'] = '0';
						}
					}

					$mobile = false;
					if (isset($_SERVER['HTTP_USER_AGENT'])) {
						$mobile = preg_match('/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i', $_SERVER['HTTP_USER_AGENT']);
					}
					if ($mobile && $options['appMobileMinimalist']) $response['config']['minMode'] = true;

						$response['config']['loaded'] = true;
		}

		// all, including login

		// check if banned
		$bans = get_post_meta( $postID, 'bans', true );
		if ( $bans ) {

			// clean expired bans
			foreach ( $bans as $key => $ban ) {
				if ( $ban['expires'] < time() ) {
					unset( $bans[ $key ] );
					$bansUpdate = 1;
				}
			}
			if ( $bansUpdate ) {
				update_post_meta( $postID, 'bans', $bans );
			}

			$clientIP = self::get_ip_address();

			foreach ( $bans as $ban ) {
				if ( $clientIP == $ban['ip'] || ( $uid > 0 && $uid == $ban['uid'] ) ) {
					$response['error'] = __( 'You are banned from accessing this room!', 'ppv-live-webcams' ) . ' ' . $ban['by'] . ' : ' . date( DATE_RFC2822, $ban['expires'] );
				}
			}
		}


		$privateSession = null;
		$messageMeta = [];
						
		// update private session if in private mode
		if ( $privateUID ?? false ) {
			$privateSession    = array();
			$disconnectPrivate = self::privateSessionUpdate( $session, $post, $isPerformer, $privateUID, $options, 0, $privateSession );
			// $response['privateSession'] = $privateSession;

			if ($privateSession)
			{
			$end           = min( $privateSession['pedate'], $privateSession['cedate'] );
			$start         = max( $privateSession['psdate'], $privateSession['csdate'] );
			$totalDuration = $end - $start;
			$duration      = $totalDuration - $options['ppvGraceTime'];

			// $response['privateTime'] = $totalDuration;

			// stats based on private session
			if ( ! array_key_exists( 'user', $response ) ) {
				$response['user'] =
				array(
					'from' => 'privateSessionUpdate',
					'loggedIn'       => true,
					'balance'        => floatval( $privateSession['balance'] ),
					'balancePending' => floatval( $privateSession['balancePending'] ),
					'cost'           => floatval( $privateSession['cost'] ),
					'time'           => intval( $privateSession['time'] ),
					'earn'           => floatval( $privateSession['earn'] ),
					'other' 		 => $privateSession['other'],
				);
			} else { // update
				$response['user']['from']           = 'privateSessionUpdate';
				$response['user']['balance']        = floatval( $privateSession['balance'] );
				$response['user']['balancePending'] = floatval( $privateSession['balancePending'] );
				$response['user']['cost']           = floatval( $privateSession['cost'] );
				$response['user']['time']           = intval( $privateSession['time'] );
				$response['user']['earn']           = floatval( $privateSession['earn'] );
				$response['user']['other']          =  $privateSession['other'];
			}
				
				if ( isset($privateSession['id']) ) $messageMeta['privateSession'] = $privateSession['id'];

			}

			// update room private if not setup
			$inPrivate = self::is_true( get_post_meta( $postID, 'privateShow', true ) );
			if ( ! $inPrivate ) {
				$performerID = intval( get_post_meta( $postID, 'performerUserID', true ) );
				self::updatePrivateShow( $postID, $performerID );
			}
		}

		if ( $disconnectPrivate ?? false ) {
			self::autoMessage( __( 'Disconnected from private:', 'ppv-live-webcams' ) . ' ' . $disconnectPrivate, $session, $privateUID );

			$response['disconnectPrivate'] = $disconnectPrivate;
			$response['warning']           = $disconnectPrivate;

			// return user to public room
			$response['room'] = self::appPublicRoom( $post, $session, $options, $disconnectPrivate, $public_room, $requestUID );
		}


		$ztime = time();

		// td: remember in private, first message dup key bug (loaded twice when lastMsg 0)

		$needUpdate = array();	
		foreach (['room', 'user', 'options', 'files', 'media', 'questions'] as $key ) $needUpdate[$key] = 0;

		// process app task (other than login)
		switch ( $task ) {

			case 'login':
			case 'tick':
				break;
				
			case 'streamPresentation':
			
			if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer can moderate presentation.', 'ppv-live-webcams' );
					break;
				}
				
		
			//set HLS stream to watch in presentation
			$streamName  = sanitize_file_name( $_POST['streamName'] ?? '' );
			$streamAddress = sanitize_text_field( $_POST['streamAddress'] ?? '' );
			$placement = sanitize_file_name( $_POST['placement'] ?? '' );

				if ( ! $placement ) {
					$placement = 'Main';
				}

			
					$presentationMedia = self::appRoomMedia( $post, $session, $options );

					$content                         = array(
						'type'     => 'hls',
						'name'     => $streamName,
						'url'     => $streamAddress,
						'userID'   => $userID,
						'userName' => $userName,
					);
					$presentationMedia[ $placement ] = $content;

					update_post_meta( $postID, 'presentationMedia', $presentationMedia );

				// update everybody and self
				update_post_meta( $postID, 'updated_media', time() );
				$needUpdate['media'] = 1;
				
			break;
			
			
			case 'modesSet':
			//change group room mode by performer
			
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				if ( ! $session->broadcaster ) {
					$response['warning'] = 'Only performer can set group mode for room.';
					break;
				}
				
				if ( ! $roomName ) {
					self::appFail( 'No room.' );
				}
				
				$mode = sanitize_text_field( $_POST['mode'] ?? '' );
				
				$oldMode = get_post_meta( $postID, 'groupCPM', true );
				
				if ($mode == $oldMode)
				{
					$response['warning'] = __( 'Same mode.', 'ppv-live-webcams' );
					break;
				}
				

				update_post_meta( $postID, 'sessionStart', time() ); //new room session in new mode

	
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
						update_post_meta( $postID, 'groupCPM', floatval( $CPMg ) );
						update_post_meta( $postID, 'groupMode', $groupMode );
						update_post_meta( $postID, 'groupParameters', $modeParameters );

						//mode parameters
						foreach ( array( 'room_random', 'requests_disable', 'room_private', 'calls_only', 'group_disabled', 'room_slots', 'room_conference', 'conference_auto', 'room_audio', 'room_text', 'vw_presentationMode', 'h5v_audio', 'party', 'party_reserved', 'stream_record', 'stream_record_all', 'stream_record_private' ) as $meta ) {
							if ( array_key_exists( $meta, $modeParameters ) ) {
								update_post_meta( $postID, $meta, $modeParameters[ $meta ] );
							}
						}

						update_post_meta( $postID, 'roomInterface', 'html5app' );

			// go-live room option defaults
			if ( is_array( $options['appSetup'] ) ) {
				if ( array_key_exists( 'Room', $options['appSetup'] ) ) {
					if ( is_array( $options['appSetup']['Room'] ) ) {
						foreach ( $options['appSetup']['Room'] as $key => $value ) {
							$optionCurrent = get_post_meta( $postID, $key, true );

							if ( empty( $optionCurrent ) || $options['appOptionsReset'] ) {
								update_post_meta( $postID, $key, $value );


							}
						}
					}
				}
			}
			
			//room update for users to reload
			update_post_meta( $postID, 'updated_room', time() );

			//create new session for performer, in new mode
			$nextSession   = self::sessionUpdate( $userName, $post->post_title, 1, 11, 0, 1, 1, $options, $userID, $postID, self::get_ip_address(), $userMeta );
						
			$response['room'] = self::appPublicRoom( $post, $nextSession, $options, __( 'Room mode changed:', 'ppv-live-webcams' ) . ' ' . $mode );

			
					// update user session
							$response['user'] = array(
								'from'		=> 'modesSet',
								'ID'        => intval( $userID ),
								'name'      => $userName,
								'sessionID' => intval( $nextSession->id ),
								'loggedIn'  => true,
								'balance'   => number_format( self::balance( $userID, true, $options ), 2, '.', ''  ),
								'cost'      => ( array_key_exists( 'cost', $userMeta ) ? $userMeta['cost'] : 0 ),
								'avatar'    => get_avatar_url( $userID, array( 'default' => dirname( plugin_dir_url( __FILE__ ) ) . '/images/avatar.png' ) ),
							);

					echo json_encode( $response );
					die(); //mode found
					break; 			
					}
				}
			} 
	
			break;
				
			case 'streamsSet':
			
			//set main stream by performer
			
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				if ( ! $session->broadcaster ) {
					$response['warning'] = 'Only performer can set default room stream.';
					break;
				}
				
				if ( ! $roomName ) {
					self::appFail( 'No room.' );
				}
				
			//set HLS stream to watch
			$streamName  = sanitize_file_name( $_POST['streamName'] ?? '' );
			$streamAddress = sanitize_text_field( $_POST['streamAddress'] ?? '' );
			
			if ($streamName && $streamAddress)
			{
			update_post_meta( $postID, 'stream-name', $streamName );
			update_post_meta( $postID, 'stream-hls', $streamAddress );	
			update_post_meta( $postID, 'stream-mode', 'stream' );
			}
			else //reset to webcam
			{
				update_post_meta( $postID, 'stream-name', '' ); //remove if set as performer
				update_post_meta( $postID, 'stream-address', '' );
				update_post_meta( $postID, 'stream-hls', '' );	
				update_post_meta( $postID, 'stream-protocol', 'rtsp' );
				update_post_meta( $postID, 'stream-type', 'webrtc' );
				update_post_meta( $postID, 'stream-mode', 'direct' );
			}

			update_post_meta( $postID, 'stream-updated', time() );
					
			update_post_meta( $postID, 'updated_room', time() );
			$needUpdate['room'] = 1;
			
			break;

			case 'recorder_upload':
				if ( ! $roomName ) {
					self::appFail( 'No room for recording.' );
				}

				$mode     = sanitize_text_field( $_POST['mode'] ?? '' );
				$scenario = sanitize_text_field( $_POST['scenario'] ?? '' );
				if ( ! $privateUID ) {
					$privateUID = 0; // public room
				}

				// generate same private room folder for both users
				if ( $privateUID ) {
					if ( $isPerformer ) {
						$proom = $userID . '_' . $privateUID; // performer id first
					} else {
						$proom = $privateUID . '_' . $userID;
					}
				}

				$destination = $options['uploadsPath'];
				if ( ! file_exists( $destination ) ) {
					mkdir( $destination );
				}

				$destination .= "/$roomName";
				if ( ! file_exists( $destination ) ) {
					mkdir( $destination );
				}

				if ( $proom ) {
					$destination .= "/$proom";
					if ( ! file_exists( $destination ) ) {
						mkdir( $destination );
					}
				}

				//$response['_FILES'] = $_FILES;

				$allowed = array( 'mp3', 'ogg', 'opus', 'mp4', 'webm', 'mkv' );

				$uploads  = 0;
				$filename = '';

				if ( $_FILES ) {
					if ( is_array( $_FILES ) ) {
						foreach ( $_FILES as $ix => $file ) {
							$filename = sanitize_file_name( $file['name'] );

							if ( strstr( $filename, '.php' ) ) {
								self::appFail( 'Bad uploader!' );
							}

							$ext                          = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
							$response['uploadRecLastExt'] = $ext;
							$response['uploadRecLastF']   = $filename;

							$filepath = $destination . '/' . $filename;

							if ( in_array( $ext, $allowed ) ) {
								if ( file_exists( $file['tmp_name'] ) ) {
									$errorUp = self::handle_upload( $file, $filepath ); // handle trough wp_handle_upload()
									if ( $errorUp ) {
										$response['warning'] = ( $response['warning'] ? $response['warning'] . '; ' : '' ) . 'Error uploading ' . esc_html( $filename . ':' . $errorUp );
									}

									$response['uploadRecLast'] = $destination . $filename;
									$uploads++;
								}
							}
						}
					}
				}

				$response['uploadCount'] = $uploads;

				// 1 file
				if ( ! file_exists( $filepath ) ) {
					$response['warning'] = 'Recording upload failed!';
				}

				if ( ! $response['warning'] && $scenario == 'chat' ) {
					$url = self::path2url( $filepath );

					$response['recordingUploadSize'] = filesize( $filepath );
					$response['recordingUploadURL']  = $url;

					$messageText       = '';
					$messageUser       = $userName;
					$userAvatar        = get_avatar_url( $userID, array( 'default' => dirname( plugin_dir_url( __FILE__ ) ) . '/images/avatar.png' ) );
					$messageUserAvatar = esc_url_raw( $userAvatar );

					$meta = array(
						'userAvatar' => $messageUserAvatar,
					);

					if ( $mode == 'video' ) {
						$meta['video'] = $url;
					} else {
						$meta['audio'] = $url;
					}

					$metaS = serialize( $meta );

					// msg type: 2 web, 1 flash, 3 own notification
					$sql = $wpdb->prepare(
						"INSERT INTO `$table_chatlog` ( `username`, `room`, `room_id`, `message`, `mdate`, `type`, `user_id`, `meta`, `private_uid`) VALUES (%s, %s, %d, %s, %d, %s, %d, %s, %d)",
						$messageUser, $roomName, $roomID, $messageText, $ztime, '2', $userID, $metaS, $privateUID
					);
					$wpdb->query( $sql );

					$response['sql'] = $sql;

					$response['insertID'] = $wpdb->insert_id;

					// also update chat log file
					if ( $roomName ) {
						if ( $messageText ) {

												$messageText = strip_tags( $messageText, '<p><a><img><font><b><i><u>' );

												$messageText = date( 'F j, Y, g:i a', $ztime ) . " <b>$userName</b>: $messageText <audio controls src='$url'></audio>";

												$day = date( 'y-M-j', time() );

												$dfile = fopen( $destination . "/Log$day.html", 'a' );
												fputs( $dfile, $messageText . '<BR>' );
												fclose( $dfile );
						}
					}
				}

				break;

			case 'options':
				$name  = sanitize_file_name( $_POST['name'] ?? '' );
				$value = sanitize_file_name( $_POST['value'] ?? '' );

				if ( ! in_array( $name, array( 'requests_disable', 'room_private', 'room_random', 'calls_only', 'group_disabled', 'room_slots', 'room_conference', 'conference_auto', 'room_audio', 'room_text', 'vw_presentationMode', 'h5v_language', 'h5v_audio', 'h5v_sfx', 'h5v_dark', 'h5v_pip', 'h5v_min', 'h5v_reveal', 'h5v_reveal_warmup', 'party', 'party_reserved', 'stream_record', 'stream_record_all', 'stream_record_private', 'external_rtmp', 'goals_panel', 'goals_sort', 'gifts', 'question_closed' ) ) ) {
					self::appFail( 'Preference not supported!' );
				}

				if ( substr( $name, 0, 3 ) == 'h5v' ) {
					$userOption = 1;
				} else {
					$userOption = 0;
				}

				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					break; // visitors don't edit any preferences
				}

				if ( ! $session->broadcaster && ! $userOption ) {
					$response['warning'] = __( 'Only room owner can edit room options.', 'ppv-live-webcams' );
					break;
				}

				if ( $userOption ) {
					update_user_meta( $userID, $name, $value );
					update_user_meta( $userID, 'updated_options', time() );
					$needUpdate['user'] = 1;
				} else // room meta option
				{
					update_post_meta( $postID, $name, $value );
					update_post_meta( $postID, 'updated_options', time() );
				}

				$needUpdate['options'] = 1;

				if ( in_array( $name, array( 'room_slots', 'room_conference', 'vw_presentationMode' ) ) ) {
					update_post_meta( $postID, 'updated_media', time() );
					$needUpdate['media'] = 1;
				}

				if ( in_array( $name, array( 'room_random', 'room_conference', 'room_audio', 'room_text', 'vw_presentationMode', 'requests_disable', 'external_rtmp', 'goals_panel', 'goals_sort', 'gifts', 'question_closed' ) ) ) {
					update_post_meta( $postID, 'updated_room', time() );
					$needUpdate['room'] = 1;
				}

				if ( in_array( $name, array( 'stream_record', 'stream_record_private', 'stream_record_all' ) ) ) {
					// update recording process faster?
				}

				break;

			case 'update':
				// something changed - let everybody know (later implementation - selective updates, triggers)
				$update = sanitize_file_name( $_POST['update'] ?? '' );
				update_post_meta( $postID, 'updated_' . $update, time() );
				$needUpdate[ $update ] = 1;

				break;

			// collaboration

			case 'user_kick':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer can moderate.', 'ppv-live-webcams' );
					break;
				}

				$TuserID   = intval( $_POST['userID'] ?? 0 );
				$TuserName = sanitize_file_name( $_POST['userName'] ?? '' );

				$sqlS     = $wpdb->prepare("SELECT * FROM `$table_sessions` WHERE uid=%d AND session=%s AND status=0 AND rid=%d LIMIT 1", $TuserID, $TuserName, $postID);
				$Tsession = $wpdb->get_row( $sqlS );

				if ( ! $Tsession ) {
					$response['warning'] = "Participant not found to kick: #$TuserID $TuserName";
					if ( VW_H5V_DEVMODE ) {
						$response['warning'] .= " Dev: $sqlS";
					}
					break;
				}

				// prevent self block
				$clientIP = self::get_ip_address();
				if ( $clientIP == $Tsession->ip ) {
					$response['warning'] = "Can not block own IP $clientIP for #$TuserID ($TuserName)!";
					break;
				}
				if ( $userID == $TuserID ) {
					$response['warning'] = "Can not block own ID $userID for #$TuserID ($TuserName)!";
					break;
				}

				// block
				$duration = 900; // 15 min

				$bans = get_post_meta( $postID, 'bans', true );
				if ( ! is_array( $bans ) ) {
					$bans = array();
				}

				$ban    = array(
					'user'    => $username,
					'uid'     => $Tsession->uid,
					'ip'      => $Tsession->ip,
					'expires' => time() + $duration,
					'by'      => $userName,
				);
				$bans[] = $ban;

				update_post_meta( $postID, 'bans', $bans );

				self::autoMessage( 'Kicked for 15 minutes: ' . "#$TuserID ($TuserName)", $session );

				break;

			case 'user_ban':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer can moderate.', 'ppv-live-webcams' );
					break;
				}

				$TuserID   = intval( $_POST['userID'] ?? 0);
				$TuserName = sanitize_file_name( $_POST['userName'] ?? '');

				$sqlS     = $wpdb->prepare("SELECT * FROM `$table_sessions` WHERE uid=%d AND session=%s AND status=0 AND rid=%d LIMIT 1", $TuserID, $TuserName, $postID);
				$Tsession = $wpdb->get_row( $sqlS );

				if ( ! $Tsession ) {
					$response['warning'] = "Participant not found to ban: #$TuserID ($TuserName)";
					if ( VW_H5V_DEVMODE ) {
						$response['warning'] .= " Dev: $sqlS";
					}
					break;
				}

				// prevent self block
				$clientIP = self::get_ip_address();
				if ( $clientIP == $Tsession->ip ) {
					$response['warning'] = "Can not block own IP $clientIP for #$TuserID ($TuserName)";
					break;
				}
				if ( $userID == $TuserID ) {
					$response['warning'] = "Can not block own ID $userID for #$TuserID ($TuserName)";
					break;
				}

				// block
				$duration = 604800; // 7 days

				$bans = get_post_meta( $postID, 'bans', true );
				if ( ! is_array( $bans ) ) {
					$bans = array();
				}

				$ban    = array(
					'user'    => $username,
					'uid'     => $Tsession->uid,
					'ip'      => $Tsession->ip,
					'expires' => time() + $duration,
					'by'      => $userName,
				);
				$bans[] = $ban;

				update_post_meta( $postID, 'bans', $bans );

				self::autoMessage( 'Banned for 7 days: ' . "#$TuserID ($TuserName)", $session );

				break;

			case 'user_presentation':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer can moderate presentation.', 'ppv-live-webcams' );
					break;
				}

				$TuserID   = intval( $_POST['userID'] );
				$TuserName = sanitize_file_name( $_POST['userName'] );

				$placement = sanitize_file_name( $_POST['placement'] );
				if ( ! $placement ) {
					$placement = 'Main';
				}

				if ( ! $roomName ) {
					self::appFail( 'No room.' );
				}

				$user = get_userdata( $TuserID );

				if ( $user ) {
					$presentationMedia = self::appRoomMedia( $post, $session, $options );

					$content                         = array(
						'type'     => 'user',
						'stream'   => self::appStreamPlayback( $TuserID, $TuserID, $post, $options ),
						'name'     => sanitize_file_name( $user->user_nicename ),
						'userID'   => $TuserID,
						'userName' => sanitize_file_name( $user->user_nicename ),
					);
					$presentationMedia[ $placement ] = $content;

					update_post_meta( $postID, 'presentationMedia', $presentationMedia );

				} else {
					$response['warning'] = __( 'User not found to display:', 'ppv-live-webcams' ) . $TuserID;
				}

				if ( ! $TuserID ) {
					$response['warning'] = __( 'User is visitor. User needs to login to broadcast camera.', 'ppv-live-webcams' );
				}

				// update everybody and self
				update_post_meta( $postID, 'updated_media', time() );
				$needUpdate['media'] = 1;

				break;

			case 'presentation_remove':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer can moderate presentation.', 'ppv-live-webcams' );
					break;
				}

				$placement = sanitize_file_name( $_POST['placement'] ?? '' );

				$presentationMedia = self::appRoomMedia( $post, $session, $options );

				if ( array_key_exists( $placement, $presentationMedia ) ) {
					$presentationMedia[ $placement ] = array(
						'name' => $placement,
						'type' => 'empty',
					);
				}
				update_post_meta( $postID, 'presentationMedia', $presentationMedia );

				// update everybody and self
				update_post_meta( $postID, 'updated_media', time() );
				$needUpdate['media'] = 1;

				break;

			case 'file_presentation':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer can moderate presentation.', 'ppv-live-webcams' );
					break;
				}

				$filename  = sanitize_file_name( $_POST['file_name'] ?? '' );
				$placement = sanitize_file_name( $_POST['placement'] ?? '' );
				if ( ! $placement ) {
					$placement = 'Main';
				}

				if ( ! $roomName ) {
					self::appFail( 'No room.' );
				}
				if ( strstr( $filename, '.php' ) ) {
					self::appFail( 'Bad.' );
				}

				$destination = $options['uploadsPath'] . "/$roomName/";
				$file_path   = $destination . $filename;

				if ( file_exists( $file_path ) ) {
					$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

					$presentationMedia = self::appRoomMedia( $post, $session, $options );

					$content                         = array(
						'type'     => 'file',
						'url'      => self::path2url( $file_path ),
						'filename' => $filename,
						'name'     => $filename,
						'ext'      => $ext,
						'userID'   => $userID,
						'userName' => $userName,
					);
					$presentationMedia[ $placement ] = $content;

					update_post_meta( $postID, 'presentationMedia', $presentationMedia );

				} else {
					$response['warning'] = __( 'File not found to display:', 'ppv-live-webcams' ) . ' ' . $filename;
				}

				// update everybody and self
				update_post_meta( $postID, 'updated_media', time() );
				$needUpdate['media'] = 1;

				break;

			case 'file_delete':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer can delete files.', 'ppv-live-webcams' );
					break;
				}

				$filename = sanitize_file_name( $_POST['file_name']?? '' );

				if ( ! $roomName ) {
					self::appFail( 'No room.' );
				}
				if ( strstr( $filename, '.php' ) ) {
					self::appFail( 'Bad.' );
				}

				$destination = $options['uploadsPath'] . "/$roomName/";
				$file_path   = $destination . $filename;

				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				} else {
					$response['warning'] = __( 'File not found:', 'ppv-live-webcams' ) . ' ' . $filename;
				}

				// update list
				update_post_meta( $postID, 'updated_files', time() );
				$needUpdate['files'] = 1;

				break;

			case 'file_upload':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				/*
				if (!$session->broadcaster)
				{
				$response['warning'] = __('Only performer can manage files.', 'ppv-live-webcams') ;
				break;
				}
				*/

				$room = $roomName;
				if ( ! $room ) {
					self::appFail( 'No room.' );
				}

				$response['_FILES'] = $_FILES;
				// $response['files'] = $_POST['files'];

				$destination = sanitize_text_field( $options['uploadsPath'] ) . "/$room/";
				if ( ! file_exists( $destination ) ) {
					mkdir( $destination );
				}

				$allowed = array( 'swf', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'doc', 'docx', 'pdf', 'mp4', 'mp3', 'flv', 'avi', 'mpg', 'mpeg', 'webm', 'ppt', 'pptx', 'pps', 'ppsx', 'doc', 'docx', 'odt', 'odf', 'rtf', 'xls', 'xlsx' );

				$uploads = 0;

				if ( $_FILES ) {
					if ( is_array( $_FILES ) ) {
						foreach ( $_FILES as $ix => $file ) {
							$filename = sanitize_file_name( $file['name'] );

							if ( strstr( $filename, '.php' ) ) {
								self::appFail( 'Bad.' );
							}

							$filepath = $destination . $filename;

							$ext                       = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
							$response['uploadLastExt'] = $ext;
							$response['uploadLastF']   = $filename;

							if ( in_array( $ext, $allowed ) ) {
								if ( file_exists( $file['tmp_name'] ) ) {
									$errorUp = self::handle_upload( $file, $filepath ); // handle trough wp_handle_upload()
									
									if ( $errorUp ) {
										$response['warning'] = ( $response['warning'] ? $response['warning'] . '; ' : '' ) . 'Error uploading ' . esc_html( $filename . ':' . $errorUp );
									} else {
														//add file in chat
														$message =  __('New file upload.', 'ppv-live-webcams');
														$meta = [ 'file_name' => $filename , 'file_url' => self::path2url( $filepath ), 'file_size' => self::humanSize( filesize( $filepath ) ) ];
														
														if ($privateSession) if ( isset($privateSession['id']) ) $meta['privateSession'] = $privateSession['id'];

														if ( in_array($ext, ['jpg', 'jpeg', 'png', 'gif'] ) ) $meta['picture'] = $meta['file_url'];
														if ( in_array($ext, ['mp4', 'webm'] ) ) $meta['video'] = $meta['file_url'];																					
														if ( in_array($ext, ['mp3'] ) ) $meta['audio'] = $meta['file_url'];	
																				
														self::autoMessage( $message, $session, $privateUID, $meta );

									}

									$response['uploadLast'] = $filepath;

									$uploads++;
								}
							}
						}
					}
				}

				$response['uploadCount'] = $uploads;

				break;

			case 'party_return':
				if ( $session->meta ) {
					$userMeta = unserialize( $session->meta );
				}
				if ( ! is_array( $userMeta ) ) {
					$userMeta = array();
				}

				if ( array_key_exists( 'party', $userMeta ) ) {
					$nextRoomID = $partyID = $userMeta['party'];

					update_post_meta( $partyID, 'partyRoomID', $nextRoomID );

					$nextPost = get_post( $partyID );

					// create a new session
					$nextSession   = self::sessionUpdate( $userName, $nextPost->post_title, 0, 11, 0, 1, 1, $options, $userID, $nextRoomID, self::get_ip_address(), $userMeta );
					$nextSessionID = $nextSession->id;

					$userMetaS = serialize( $userMeta );
					$sql       = $wpdb->prepare("UPDATE `$table_sessions` set meta=%s WHERE id=%d", $userMetaS, $nextSession->id);
					$wpdb->query( $sql );

					// update user
					$response['user'] = array(
						'from' => 'party_return',
						'ID'        => intval( $userID ),
						'name'      => $userName,
						'sessionID' => intval( $nextSessionID ),
						'loggedIn'  => true,
						'balance'   => number_format( self::balance( $userID, true, $options ), 2, '.', ''  ),
						'cost'      => ( array_key_exists( 'cost', $userMeta ) ? $userMeta['cost'] : 0 ),
						'avatar'    => get_avatar_url( $userID, array( 'default' => dirname( plugin_dir_url( __FILE__ ) ) . '/images/avatar.png' ) ),
					);

					// move to next room
					$response['room'] = self::appPublicRoom( $nextPost, $nextSession, $options, __( 'Returned to party start room.', 'ppv-live-webcams' ) . $partyMessage );

					$session     = $nextSession;
					$post        = $nextPost;
					$changedRoom = 1;
				}

				break;

			case 'party_leave':
				if ( $session->meta ) {
					$userMeta = unserialize( $session->meta );
				}
				if ( ! is_array( $userMeta ) ) {
					$userMeta = array();
				}

				unset( $userMeta['party'] );
				unset( $userMeta['partyName'] );
				unset( $userMeta['partyHost'] );

				$session->meta = $userMeta;

				$userMetaS = serialize( $userMeta );
				$sql       = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $session->id);
				$wpdb->query( $sql );

				// then next individually

			case 'next':
			
			
				if ($room_random)
				{
					if ($isPerformer) 
					{
						//performer next
						self::autoMessage( 'Performer nexted: ' . $userName, $session, $privateUID, $messageMeta );
						
						//close private with client
						$sqlU = $wpdb->prepare("UPDATE `$table_private` SET status=%d, pedate=%d WHERE pid=%d AND status=%d AND cid=%d", 1, $ztime, $userID, 0, $privateUID);
						$wpdb->query($sqlU);
						
						//close session
						$sqlU2 = $wpdb->prepare("UPDATE `$table_sessions` SET status=%d, edate=%d WHERE id=%d", 1, $ztime, $sessionID);
						$wpdb->query($sqlU2);
						
						//create new session for performer
						$nextSession   = self::sessionUpdate( $userName, $post->post_title, 1, 11, 0, 1, 1, $options, $userID, $postID, self::get_ip_address(), $userMeta );
						
						$response['room'] = self::appRandomRoom( $post, $nextSession, $options, "\n " . __( 'Moved to a new booth, to match with a new client.', 'ppv-live-webcams' ), true, true  ); //available in new booth
						
						
					}
					else
					{
						//client next
						self::autoMessage( 'Client nexted: ' . $userName , $session, $privateUID, $messageMeta );

						//close private with performer
						$sqlU = $wpdb->prepare("UPDATE `$table_private` SET status=%d, cedate=%d WHERE cid=%d AND client=%s AND status=%d AND pid=%d", 1, $ztime, $userID, $userName, 0, $privateUID);
						$wpdb->query($sqlU);

						//close session
						$sqlU2 = $wpdb->prepare("UPDATE `$table_sessions` SET status=%d, edate=%d WHERE id=%d", 1, $ztime, $sessionID);
						$wpdb->query($sqlU2);

						//notify watch nexted
						if ( $session->meta ) $userMeta = unserialize( $session->meta );
						if ( ! is_array( $userMeta ) ) $userMeta = array();
						
						if (isset($userMeta['watch'])) {
							$otherSession = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id=%d", intval($userMeta['watch'])));
						}
						if ($otherSession) {
							if ($otherSession->meta) $otherUserMeta = unserialize($otherSession->meta);
							if (!is_array($otherUserMeta)) $otherUserMeta = array();
							$otherUserMeta['watchNexted'] = 1;

							$otherUserMetaS = serialize($otherUserMeta);
							$sqlU = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $otherUserMetaS, $otherSession->id);
							$wpdb->query($sqlU);
						}

						
						//create new sessio for client
						$nextSession   = self::sessionUpdate( $userName, 'Matching..', 0, 11, 0, 1, 1, $options, $userID, -1, self::get_ip_address(), $userMeta );		
												
							if (!$nextSession) 
							{
								 $response['error'] = 'Could not create new client session!';
								 break;
							}
							else
							{
								
								$nextMatchID = self::nextMatch($nextSession, $options, $debugInfo);
								$nextSessionID = $nextSession->id;

								if ($nextMatchID) 
								{
									
									//update session and room
									$nextSession = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_sessions` WHERE id=%d", $nextSessionID));
									if ($nextSession->rid) $post = get_post($nextSession->rid);
									$postID = $post->ID;
									
									$response['room'] = self::appRandomRoom( $post, $nextSession, $options, "\n " . __( 'Moved to a new booth, to match with a new performer.', 'ppv-live-webcams' ) . ' S#' . $nextSession->id . ' P#' . $nextMatchID);
								}
								else 
								{
									 $response['error'] = 'No performer match is available. Retry after some time. ' . $debugInfo;
									 $response['errorTask'] = 'next';
									 break;
									
								}
							}

					}
					
						// update user session
							$response['user'] = array(
								'from'		=> 'next_match',
								'ID'        => intval( $userID ),
								'name'      => $userName,
								'sessionID' => intval( $nextSession->id ),
								'boothID' 	=> intval( $nextMatchID ),
								'loggedIn'  => true,
								'balance'   => number_format( self::balance( $userID, true, $options ), 2, '.', ''  ),
								'cost'      => ( array_key_exists( 'cost', $userMeta ) ? $userMeta['cost'] : 0 ),
								'avatar'    => get_avatar_url( $userID, array( 'default' => dirname( plugin_dir_url( __FILE__ ) ) . '/images/avatar.png' ) ),
							);


					$session     = $nextSession;
					$sessionID = $nextSession->id;

					echo json_encode( $response );
					die();
					
					break ;
				}
				
				// party host?
				if ( $session->meta ) {
					$userMeta = unserialize( $session->meta );
				}
				if ( ! is_array( $userMeta ) ) {
					$userMeta = array();
				}

				$partyID        = 0;
				$slots_required = 0;

				if ( array_key_exists( 'party', $userMeta ) ) {
					$partyID        = $userMeta['party'];
					$slots_required = get_post_meta( $partyID, 'party_reserved', true );
				}

				$nextRoomID = self::nextRoomID( $userID, $options, $slots_required );

				if ( $nextRoomID ) {

					// next room
					$response['nextRoomID'] = $nextRoomID;
					$nextPost               = get_post( $nextRoomID );

					// create a new session
					$nextSession   = self::sessionUpdate( $userName, $nextPost->post_title, 0, 11, 0, 1, 1, $options, $userID, $nextRoomID, self::get_ip_address(), $userMeta );
					$nextSessionID = $nextSession->id;

					// update party
					$partyMessage = '';
					if ( $partyID ) {
						update_post_meta( $partyID, 'partyRoomID', $nextRoomID );

						// meta inc party info
						$userMetaS = serialize( $userMeta );
						$sql       = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $nextSession->id);
						$wpdb->query( $sql );

						$partyMessage = ' ' . __( 'Your party moves with you.', 'ppv-live-webcams' );
					}

					// update user
					$response['user'] = array(
						'from' => 'next_room',
						'ID'        => intval( $userID ),
						'name'      => $userName,
						'sessionID' => intval( $nextSessionID ),
						'loggedIn'  => true,
						'balance'   => number_format( self::balance( $userID, true, $options ), 2, '.', ''  ),
						'cost'      => ( array_key_exists( 'cost', $userMeta ) ? $userMeta['cost'] : 0 ),
						'avatar'    => get_avatar_url( $userID, array( 'default' => dirname( plugin_dir_url( __FILE__ ) ) . '/images/avatar.png' ) ),
					);

					// move to next room
					$response['room'] = self::appPublicRoom( $nextPost, $nextSession, $options, __( 'You can move a different room using Next button again.', 'ppv-live-webcams' ) . $partyMessage );

					$session     = $nextSession;
					$post        = $nextPost;
					$changedRoom = 1;
				} else {
					$response['warning'] = __( 'No next room found with current criteria!', 'ppv-live-webcams' );
				}

				break;

			case 'external':
				$external = ( $_POST['external'] == 'true' ? true : false );

				if ( $session->meta ) {
					$userMeta = unserialize( $session->meta );
				}
				if ( ! is_array( $userMeta ) ) {
					$userMeta = array();
				}

				$userMeta['externalUpdate'] = time();
				$userMeta['external']       = $external;

				$userMetaS = serialize( $userMeta );
				$sql       = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $session->id);
				$wpdb->query( $sql );

				update_post_meta( $post->ID, 'updated_media', time() );
				$needUpdate['media'] = 1;
				break;

			case 'snapshot':

				$dir = $options['uploadsPath'];
				if ( ! file_exists( $dir ) ) {
					mkdir( $dir );
				}

				$dir .= "/$roomName";
				if ( ! file_exists( $dir ) ) {
					mkdir( $dir );
				}

				$proom = '';

				// generate same private room folder for both users
				if ( $privateUID ) {
					if ( $isPerformer ) {
						$proom = $userID . '_' . $privateUID; // performer id first
					} else {
						$proom = $privateUID . '_' . $userID;
					}
				}

				if ( $proom ) {

					if (!$options['privateSnapshots']) break;

					$dir .= "/$proom";
					if ( ! file_exists( $dir ) ) {
						mkdir( $dir );
					}

				}

				$dir .= "/_snapshots";
				if ( ! file_exists( $dir ) ) {
					mkdir( $dir );
				}

				// get snapshot data from H5V and save into a png file

				//snapshot data from H5V
				$data = $_POST['data'];

				// Remove the metadata from the beginning of the data URI
				$filteredData = substr($data, strpos($data, ",") + 1);

				// Decode the Base64 encoded data
				$decodedData = base64_decode($filteredData);

				// Save the image to a file
				if ($options['saveSnapshots']) $filename =  $dir . '/' . $userID . '_' . time() . ".png";
				else $filename = $dir . '/' . $userID . '_' . $userName . ".jpg";
				
				file_put_contents($filename, $decodedData);

				$response['snapPath'] = $filename;

				// generate thumb, in room (not private)
				if ( !$proom && file_exists( $filename ) && filesize( $filename ) > 0 )
				{

				// if snapshot successful, update time
				update_post_meta( $postID, 'edate', time() ); // always update (snapshot retrieved = stream is live)
				update_post_meta( $postID, 'snapshotDate', time() );
				update_post_meta( $postID, 'vw_lastSnapshot', $filename );
				update_post_meta( $postID, 'snapshot', $filename );
				self::moderateSnapshot($filename, $postID, $options);

				// generate a thumb with proper size
				$thumbWidth  = $options['thumbWidth'];
				$thumbHeight = $options['thumbHeight'];

				$src                  = imagecreatefrompng( $filename );
				list($width, $height) = getimagesize( $filename );
				$tmp                  = imagecreatetruecolor( $thumbWidth, $thumbHeight );

				$dir = $options['uploadsPath'] . '/_thumbs';
				if ( ! file_exists( $dir ) ) {
					mkdir( $dir );
				}

				$thumbFilename = "$dir/$roomName.jpg";
				imagecopyresampled( $tmp, $src, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height );
				imagejpeg( $tmp, $thumbFilename, 95 );

				// detect tiny images without info
				if ( filesize( $thumbFilename ) > 1000 ) {
					$picType = 1;
				} else {
					$picType = 2;
				}

				update_post_meta( $postID, 'hasPicture', $picType );
				update_post_meta( $postID, 'hasSnapshot', 1 );
				update_post_meta( $postID, 'thumbSnapshot', $thumbFilename );
				update_post_meta( $postID, 'hasThumb', 1 );

				//also update max viewers when broadcaster is active
				self::updateViewers( $postID, $roomName, $options );
				}

				break;

			case 'externaljs':
				$action = sanitize_text_field( $_POST['action']);

				$data = isset( $_POST['data'] ) ? (array) $_POST['data'] : array(); // array elements sanitized individually

				switch ($action)
				{
					case 'toyStatusChange':
						if (!$options['lovense'] || $options['lovenseToy'] !='auto' ) break;

						if (!isset($data['status'])) $data['status'] = 'off';
						$toyStatus = $data['status'] == 'on' ? 1 : 0;
						update_post_meta( $postID, 'lovenseToy', $toyStatus );
					break;
				}

				break;

			case 'media':
				// notify user media (streaming) updates

				$connected = ( $_POST['connected'] == 'true' ? true : false );

				if ( $session->meta ) {
					$userMeta = unserialize( $session->meta );
				}
				if ( ! is_array( $userMeta ) ) {
					$userMeta = array();
				}

				// if ($options['debugMode']) $userMeta['updateMediaMeta'] = $session->meta;

				$userMeta['connected']       = $connected;
				$userMeta['connectedUpdate'] = time();

				// also update external broadcast info on web media publishing
				$webStatusInterval = $options['webStatusInterval'];
				if ( $webStatusInterval < 10 ) {
					$webStatusInterval = 60;
				}

				if ( array_key_exists( 'externalUpdate', $userMeta ) ) {
					if ( $userMeta['externalUpdate'] < time() - $webStatusInterval ) {
						$userMeta['external'] = false;
					}
				}

				$userMetaS = serialize( $userMeta );

				$sql = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $session->id);
				$wpdb->query( $sql );

				$response['taskSQL'] = $sql;

				// auto  assign enabled
				$conference      = self::is_true( get_post_meta( $post->ID, 'room_conference', true ) );
				$conference_auto = self::is_true( get_post_meta( $post->ID, 'conference_auto', true ) ) && !self::isModerator($userID, $options);
				
				if ( $conference && $conference_auto ) {

					$presentationMedia = self::appRoomMedia( $post, $session, $options );

					$items = 0;

					// remove on disconnect
					if ( ! $connected ) {
						foreach ( $presentationMedia as $placement => $content ) {
							++$items;
							if ( $content['userName'] == $userName && $content['userID'] == $userID && $content['type'] == 'user' ) {

								$content                         = array(
									'name' => 'Slot' . $items,
									'type' => 'empty',
									'by'   => $userName,
								);
								$presentationMedia[ $placement ] = $content;
								update_post_meta( $post->ID, 'presentationMedia', $presentationMedia );
								update_post_meta( $post->ID, 'updated_media', time() );
								$needUpdate['media'] = 1;
							}
						}
					}

					if ( ! $connected ) {
						break; // top switch
					}

					$performer = get_post_meta( $post->ID, 'performer', true );
					if ($userName == $performer) if ( !array_key_exists('type', $presentationMedia['Main']) ) break; //no need to add performer to extra slots, unless main defined otherwise

					// connected and already present: break
					foreach ( $presentationMedia as $placement => $content ) {
						if ( $content['userName'] == $userName && $content['type'] == 'user' ) {
							break 2; // both this foreach and top switch
						}
					}

					// add on connect
					foreach ( $presentationMedia as $placement => $content ) {
						if ( $content['type'] == 'empty' ) {

							$content = array(
								'type'     => 'user',
								'stream'   => self::appStreamPlayback( $userID, $userID, $post, $options ),
								'name'     => $userName,
								'userID'   => $userID,
								'userName' => $userName,
								'by'       => $userName,
								'auto'     => 1,
							);

							$presentationMedia[ $placement ] = $content;
							update_post_meta( $post->ID, 'presentationMedia', $presentationMedia );
							update_post_meta( $post->ID, 'updated_media', time() );
							$needUpdate['media'] = 1;
							break 2; // both this foreach and top switch
						}
						// not found

					}
				}

				/*
				$usersMeta = get_post_meta( $postID, 'vws_usersMeta', true);
					if (!is_array($users)) $usersMeta = array();
					if (!array_key_exists($userID, $users)) $usersMeta[$userID] = array();


					$usersMeta[$userID]['connected'] = $connected;
					$usersMeta[$userID]['username'] = $session->username;
					$usersMeta[$userID]['updated'] = $ztime;

					update_post_meta( $postID, 'vws_usersMeta', $usersMeta);
					*/

				// if ($userID) update_user_meta($userID, 'html5_media', $ztime);
				break;

			case 'goal_add':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer', 'ppv-live-webcams' );
					break;
				}

				// load goals
				$goals = self::appRoomGoals( $post->ID, $options );

				// name: this.state.name, description: this.state.description, amount: this.state.amount, independent: this.state.independent, order: this.state.order

				$newIndex = intval( $_POST['order'] );

				if ( array_key_exists( $newIndex, $goals ) ) {
					// increase from there
					foreach ( $goals as $ix => $goal ) {
						if ( $ix >= $newIndex ) {
							$goals[ $ix ]['ix'] = $ix + 1;
						}
					}
				}

				$newGoals = array();
				foreach ( $goals as $ix => $goal ) {
					$newGoals[ $goal['ix'] ] = $goal;
				}

				$newGoal               = array(
					'ix'          => $newIndex,
					'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
					'description' => sanitize_text_field( $_POST['description'] ?? '' ),
					'amount'      => intval( $_POST['amount'] ?? 0 ),
					'independent' => boolval( $_POST['independent'] ?? false ),
				);
				$newGoals[ $newIndex ] = $newGoal;

				// new goals with maching order keys
				ksort( $newGoals );

				update_post_meta( $post->ID, 'goals', $newGoals );
				$needUpdate['room'] = 1;

				$response['newGoalsCount'] = count( $newGoals );
				$response['newGoals']      = serialize( $newGoals );

				break;

			case 'goals_reset':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}

				// moderator
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer', 'ppv-live-webcams' );
					break;
				}

				$goals = self::appRoomGoals( $post->ID, $options );

				foreach ( $goals as $ix => $value ) {
					$goals[ $ix ]['current']   = 0;
					$goals[ $ix ]['cumulated'] = 0;
					$goals[ $ix ]['ix']        = $ix;
				}

				$goal = array_values( $goals )[0]; // first goal

				update_post_meta( $post->ID, 'goal', $goal );
				update_post_meta( $post->ID, 'goals', $goals );
				$needUpdate['room'] = 1;

				break;

			case 'goal_complete':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer', 'ppv-live-webcams' );
					break;
				}

				$ix = intval( $_POST['index'] ?? 0 );

				$goals = self::appRoomGoals( $post->ID, $options );

				if ( array_key_exists( $ix, $goals ) ) {

					$goal = $goals[ $ix ];

					$meta['progressValue']   = $goal['current'];
					$meta['progressTotal']   = $goal['amount'];
					$meta['progressDetails'] = $goal['name'];
					$message                .= "\n" . __( 'Performer marked goal as complete', 'ppv-live-webcams' ) . ': ' . $goal['name'] . "\n" . $goal['completedDescription'] . "\n";

					$goals[ $ix ]['current'] = 0;
					update_post_meta( $post->ID, 'goals', $goals );
					
					if ($privateSession) if ( isset($privateSession['id']) ) $meta['privateSession'] = $privateSession['id'];

					self::autoMessage( $message, $session, $privateUID, $meta );

					$needUpdate['room'] = 1;
				} else {
					$response['warning'] = 'Goal index not found: ' . $ix;
				}

				break;

			case 'goal_delete':
				if ( ! VW_H5V_DEVMODE && ! is_user_logged_in() ) {
					self::appFail( 'Denied visitor.' . VW_H5V_DEVMODE );
				}
				if ( ! $session->broadcaster ) {
					$response['warning'] = __( 'Only performer', 'ppv-live-webcams' );
					break;
				}

				$ix = intval( $_POST['index'] );

				$goals = self::appRoomGoals( $post->ID, $options );

				if ( array_key_exists( '', $goals ) ) {
					unset( $goals[''] );
					update_post_meta( $post->ID, 'goals', $goals );
					$needUpdate['room'] = 1;
				}

				if ( array_key_exists( $ix, $goals ) ) {
					unset( $goals[ $ix ] );
					update_post_meta( $post->ID, 'goals', $goals );
					$needUpdate['room'] = 1;
				} else {
					$response['warning'] = 'Goal index not found: ' . $ix;
				}

				break;
				
			case 'tipProcessed':
			
							$key = sanitize_text_field( $_POST['key'] ?? '' );	

					 		$tipsRecent = get_user_meta( $userID, 'tipsRecent', true );
							if (!is_array($tipsRecent)) $tipsRecent = [];
	
							if (array_key_exists($key, $tipsRecent)) unset($tipsRecent[$key]);
							
							foreach ($tipsRecent as $key => $tip) if (time() - $tip['time'] > 600) unset($tipsRecent[$key]); //erase older than 10min
							update_user_meta( $userID, 'tipsRecent', $tipsRecent );
							$response['tipsRecent'] = $tipsRecent;
							
							$response['user']['tipsRecent'] = $tipsRecent;
			break;

			case 'vote':
				$error = '';
				if ( ! $userID  || !$userName) {
					$error = __( 'Only users can vote!', 'ppv-live-webcams' );
				}
				if ( self::isModerator($userID, $options) ) $error = __( 'Moderators can not vote!', 'ppv-live-webcams' );


				$votes = get_post_meta( $postID, 'rateStarReview_votes', true);
				if (!is_array($votes)) $votes = [];

				$voted = 0;
				if ( in_array($userName, $votes) ) $voted = 1;
				
				if ($voted) $error = __('You already voted!', 'ppv-live-webcams' );

				$category  = intval( $_POST['category'] ?? 0 );

				$response['warning'] = $error;
				if ( $error ) {
					break;
				}

				
				//add vote
				if ( !in_array($userName, $votes) ) $votes[] = $userName;
				update_post_meta( $postID, 'rateStarReview_votes', $votes);
				update_post_meta( $postID, 'rateStarReview_voteCount', count($votes) );

				//category vote
				if ($category)
				{
					$votesCategory = get_post_meta( $postID, 'rateStarReview_votes' . $category, true);
					if ( !in_array($userName, $votesCategory) ) $votesCategory[] = $userName;
					update_post_meta( $postID, 'rateStarReview_votes' . $category, $votesCategory);
					update_post_meta( $postID, 'rateStarReview_voteCount' . $category, count($votesCategory) );
				}


				$response['votesCount'] = count($votes);

							// client cost
							$amount = floatval( $options['voteCost'] ?? 0);
							if ($amount)
							{
							$paid = number_format( $amount, 2, '.', '' );
							self::transaction( 'ppv_vote', $user->ID, - $paid, __( 'Vote for', 'ppv-live-webcams' ) . ' <a href="' . self::roomURL( $post->post_title ) . '">' . esc_html($post->post_title). '</a>. (' . $label . ')', $ztime );
							$response['votePaid'] = $paid;
							
							$earningRatio = floatval( $options['voteRatio']);
							if ($earningRatio)
							{
								$received = number_format( $amount * $earningRatio, 2, '.', '' );
								self::transaction( 'ppv_vote_earn', $post->post_author, $received, __( 'Vote from', 'ppv-live-webcams' ) . ' ' . $userName .  ' @<a href="' . self::roomURL( $post->post_title ) . '">' . esc_html($post->post_title). '</a>', $ztime );							
							}

							}


				break;

			case 'tip':
				$error = '';
				if ( ! $userID ) {
					$error = __( 'Only users can tip!', 'ppv-live-webcams' );
				}
				
				if ( self::isModerator($userID, $options) ) $error = __( 'Moderators can not tip!', 'ppv-live-webcams' );

				if ( !self::rolesUser( $options['rolesDonate'], get_userdata(  $userID ) ) ) $error = __( 'Your role is not allowed to donate!', 'ppv-live-webcams' );

				$response['warning'] = $error;
				if ( $error ) {
					break;
				}

				if ( $options['tipCooldown'] ) {
					$lastTip = intval( get_user_meta( $userID, 'vwTipLast', true ) );
					if ( $lastTip + $options['tipCooldown'] > time() ) {
						$error = __( 'Cooldown Required: Already sent tip recently. Try again in few seconds!', 'ppv-live-webcams' );
					}
				}
				$response['warning'] = $error;
				if ( $error ) {
					break;
				}

				$tip = isset( $_POST['tip'] ) ? (array) $_POST['tip'] : array(); // array elements sanitized individually

				$tipsURL  = sanitize_text_field( $_POST['tipsURL'] ?? '' );
				$targetID = intval( $_POST['targetID'] ?? 0 ); // tip recipient

				$label  = wp_encode_emoji( sanitize_text_field( $tip['attributes']['LABEL'] ?? '' ) );
				$amount = intval( $tip['attributes']['AMOUNT'] ?? 0 );
				$note   = wp_encode_emoji( sanitize_text_field( $tip['attributes']['NOTE'] ?? '' ) );
				$sound  = sanitize_text_field( $tip['attributes']['SOUND'] ?? '' );
				$image  = sanitize_text_field( $tip['attributes']['IMAGE'] ?? '' );
				$color  = sanitize_text_field( $tip['attributes']['COLOR'] ?? '' );

				$meta          = array();
				$meta['sound'] = $tipsURL . $sound;
				$meta['image'] = $tipsURL . $image;
				$meta['tip']   = true;

				if ($privateSession) if ( isset($privateSession['id']) ) $meta['privateSession'] = $privateSession['id'];


				if ( ! $label ) {
					$error = 'No tip message!';
				}
				$response['warning'] = $error;
				

				if ( ! $error ) {
					$message = $label . ': ' . $note;

					$message = preg_replace( '/([^\s]{48})(?=[^\s])/', '$1' . '<wbr>', $message ); // break long words <wbr>:Word Break Opportunity

					$private = 0;

					// tip
					$balance                        = self::balance( $userID, true, $options );
					$response['tipSuccess']         = 1;
					$response['tipBalancePrevious'] = $balance;
					$response['tipAmount']          = $amount;

					if ( $amount > $balance ) {
						$response['tipSuccess'] = 0;
						$response['warning']    = "Tip amount ($amount) greater than available balance ($balance)! Not processed.";
					} else {

						$ztime = time();

						// client cost
						$paid = number_format( $amount, 2, '.', '' );
						self::transaction( 'ppv_tip', $userID, - $paid, __( 'Tip in', 'ppv-live-webcams' ) . ' <a href="' . self::roomURL( $post->post_title ) . '">' . esc_html($post->post_title). '</a>. (' . $label . ')', $ztime );
						$response['tipPaid'] = $paid;

						// checking
						$roomOptions = unserialize( $session->roptions );
						$checkin     = $roomOptions['checkin'];
						
						if ( $checkin ) {

							if ( ! is_array( $checkin ) ) {
								$checkin = array( $checkin );
							}

							$divider = count( $checkin );
							if ( ! $divider ) {
								return;
							}

							$checkinComment = '';
							if ( $divider > 1 ) {
								$checkinComment = ' ' . __( 'checked in', 'ppv-live-webcams' ) . ' x' . $divider;
							}

							$received = number_format( $amount * $options['tipRatio'], 2, '.', '' );

							$share = number_format( $received / $divider, 2, '.', '' );

							foreach ( $checkin as $performerID ) 
							{
							self::transaction( 'ppv_tip_share', $performerID, $share, __( 'Tip from', 'ppv-live-webcams' ) . ' ' . $userName . ' (' . $label . ')' . ' ' . $timeStamp . $checkinComment .  ' @<a href="' . self::roomURL( $post->post_title ) . '">' . esc_html($post->post_title). '</a>', $session->id );
								
							//update tipsReceived
							$tip = [ 'amount' => $amount, 'clientID' => $userID, 'client' => $userName, 'received' => $share, 'time' => time() ];				
							$tipsRecent = get_user_meta( $performerID, 'tipsRecent', true );
							if (!is_array($tipsRecent)) $tipsRecent = [];
							$tipsRecent[$userID.'_'.time()] = $tip;
							update_user_meta( $performerID, 'tipsRecent', $tipsRecent );
																					
							}
						} else {
							// single performer earning
							$received = number_format( $amount * $options['tipRatio'], 2, '.', '' );
							self::transaction( 'ppv_tip_earn', $targetID, $received, __( 'Tip from', 'ppv-live-webcams' ) . ' ' . $userName . ' (' . $label . ')' .  ' @<a href="' . self::roomURL( $post->post_title ) . '">' . esc_html($post->post_title). '</a>', $ztime );							
							
							//update tipsRecent
							$tip = [ 'amount' => $amount, 'clientID' => $userID, 'client' => $userName,'received' => $received, 'time' => time() ];				
							$tipsRecent = get_user_meta( $targetID, 'tipsRecent', true );
							if (!is_array($tipsRecent)) $tipsRecent = [];
							$tipsRecent[$userID.'_'.time()] = $tip;
							update_user_meta( $targetID, 'tipsRecent', $tipsRecent );
						}

						// save last tip time
						update_user_meta( $userID, 'vwTipLast', time() );

						$response['tipTargetID'] = $targetID;
						$response['tipReceived'] = $received;

						// gifts button in actions bar
						$gifts = self::is_true( get_post_meta( $postID, 'gifts', true ) );

						// goals
						if ( $options['goals'] ) {

							if ( $independent = sanitize_text_field( $_POST['independent'] ) ) {
								$goal = self::goalIndependent( $postID, $independent, $paid, $options ); // to independent goal
							} else {
								$goal = self::goal( $postID, $paid, $options ); // to current goal
							}

							if ( $goal ) {
								$meta['progressValue']   = $goal['current'];
								$meta['progressTotal']   = $goal['amount'];
								$meta['progressDetails'] = $goal['name'];

								if ( $goal['completed'] ) {
									$message .= "\n" . __( 'Completed goal', 'ppv-live-webcams' ) . ': ' . $goal['completed'] . "\n" . $goal['completedDescription'] . "\n" . __( 'Starting new goal', 'ppv-live-webcams' ) . ':';
								}
								$needUpdate['room'] = 1;
							}
						}

						if ($privateSession) if ( isset($privateSession['id']) ) $meta['privateSession'] = $privateSession['id'];

						$response['tipSQLmsg'] = self::autoMessage( $message, $session, $privateUID, $meta );
						$response['tipMessage']                        = $message;

					}
				}

				break;

			case 'interaction-close':
				// any can close
				$action = isset( $_POST['interaction'] ) ? (array) $_POST['interaction'] : ''; // array elements in use sanitized below

				$action_ID         = intval( $action['ID'] );
				$action_status     = intval( $action['status'] );
				$action_privateUID = intval( $action['privateUID'] );

				if ( $action_status < 7 ) {
					$action_status = 7;
				}

				$sql = $wpdb->prepare("UPDATE `$table_actions` SET status = %d, mdate = %d WHERE id = %d", $action_status, $ztime, $action_ID);
				$wpdb->query( $sql );

				// close private session
				if ( $isPerformer ) {
					$pid = $userID;
					$cid = $action_privateUID;
				} else {
					$cid = $userID;
					$pid = $action_privateUID;
				}

				$wpdb->query( $wpdb->prepare("UPDATE `$table_private` SET status = %d WHERE status = %d AND rid = %d AND pid = %d AND cid = %d", 1, 0, $postID, $pid, $cid) );

				self::autoMessage( 'Closed private session.', $session, $action_privateUID, $messageMeta );

				// return to public room
				$response['room'] = self::appPublicRoom( $post, $session, $options, __( 'You closed private call.', 'ppv-live-webcams' ), $public_room, $requestUID );
				break;

			case 'interaction-confirm':
				$action = isset( $_POST['interaction'] ) ? (array) $_POST['interaction'] : ''; // array elements in use sanitized below
				if ( ! $action ) {
					break;
				}

				$action_ID     = intval( $action['ID'] );
				$action_status = intval( $action['status'] );
				if ( $action_status < 5 ) {
					$action_status = 5;
				}

				$sql = $wpdb->prepare("UPDATE `$table_actions` SET status = %d, mdate = %d WHERE id = %d", $action_status, $ztime, $action_ID);
				$wpdb->query( $sql );
				break;

			case 'interaction-answer':
				// recipient answers (& executes)

				$action = isset( $_POST['interaction'] ) ? (array) $_POST['interaction'] : ''; // array elements in use sanitized below
				
				if ( ! $action ) {
					$response['interactionAnswerInvalid'] = 'Invalid interaction-answer: _POST[interaction]';
					break;
				}

				$action_ID     = intval( $action['ID'] ?? 0);
				$action_answer = sanitize_text_field( $action['answer'] ?? '');
				$action_status = intval( $action['status'] ?? 0);

				if (!$action_ID) 
				{
					$response['interactionAnswerInvalid'] = 'Invalid interaction-answer: ' . $action_ID  . '/' . $action_answer  . '/' .  $action_status;
					break;
				}

				// select action to answer from db
				$sqlS             = $wpdb->prepare("SELECT * from `$table_actions` WHERE `id` = %d", $action_ID);
				$actionS          = $wpdb->get_row( $sqlS );
				$response['sqlS'] = $sqlS;

				$response['actionS'] = $actionS;

				if ( ! $actionS ) {
					$response['warning'] = 'Action not found, to answer: ' . $action_ID  . '/' . $action_answer ;
					break;
				}

				// sender requests 0, recipient received 1, recipient answered 2, recipient executed 3, sender received execution 4, sender executed 5,  closed 7

				if ( ! $action_status ) {
					$action_status = 1;
				}

				$sAnswer = '';
				if ( array_key_exists( 'answer', $action ) ) {
					$sAnswer = " answer = '" . $action_answer . "',";
				}

				$sql = $wpdb->prepare("UPDATE `$table_actions` SET answer = %s, status = %d, mdate = %d WHERE id = %d", $action_answer, $action_status, $ztime, $action_ID);
				$wpdb->query( $sql );
				$response['sql'] = $sql;

				if ( $actionS->action == 'privateRequest' && $action_status == 3 ) {
					// recipient (target_id) execute (3): move to private room //$response['room']
					// static function appPrivateRoom($post, $session, $privateUID, $actionID, $options)

					$response['room'] = self::appPrivateRoom( $post, $session, $actionS->user_id, $action_ID, $options );

					if ( ! $requestUID ) {
						self::autoMessage( 'Accepted private session.', $session );
					}
					
					self::autoMessage( 'Started private session.', $session, $actionS->user_id, $messageMeta );
				}

				$response['updateID'] = $wpdb->update_id ?? 0;

				if (!isset($wpdb->update_id)) $response['_dev_sql'] = 'No update ID:' . $wpdb->last_error . ' ' . $wpdb->last_query;

				break;

			case 'interaction':
				// sender sends new interaction (0)

				$action = isset( $_POST['interaction'] ) ? (array) $_POST['interaction'] : ''; // array elements in use sanitized below
				if ( ! $action ) {
					break;
				}

				$action_userID   = intval( $action['userID'] );
				$action_roomID   = intval( $action['roomID'] );
				$action_action   = sanitize_text_field( $action['action'] );
				$action_targetID = intval( $action['targetID'] );

				// terminate other similar actions from user to prevent confusion
				$sqlU = $wpdb->prepare("UPDATE `$table_actions` SET status = %d WHERE user_id = %d AND room_id = %d AND action = %s AND status = %d", 12, $action_userID, $action_roomID, $action_action, 0);
				$wpdb->query($sqlU);

				$actionMeta = serialize(is_array($action['meta']) ? array_map('sanitize_text_field', $action['meta']) : array());

				$sql = $wpdb->prepare("INSERT INTO `$table_actions` (user_id, room_id, target_id, action, meta, mdate, status, answer) VALUES (%d, %d, %d, %s, %s, %d, %d, %d)", $action_userID, $action_roomID, $action_targetID, $action_action, $actionMeta, $ztime, 0, 0);
				$wpdb->query($sql);

				$response['sql'] = $sql;

				$response['insertID'] = $wpdb->insert_id;

				if ( ! $requestUID ) {
					self::autoMessage( 'Request private session.', $session );
				}

				break;
				
				case 'questions_question': //paid question message
				
					$message = isset( $_POST['message'] ) ? (array) $_POST['message'] : ''; // array elements sanitized individually
					if ( ! $message )					break;

					$balance   = self::balance( $userID );

					$messagesCost = floatval( get_post_meta( $postID, 'question_price', true ) );
					if ( ! $messagesCost ) {
						$messagesCost = floatval( $options['messagesCost'] );
					}
		
				//balance check
				if ( $balance < $messagesCost ) {
				$response['warning'] =  __( 'You need cost of message in balance to send it.', 'ppv-live-webcams' ) . ' ' . $messagesCost . '/' . $balance . ' ' . $options['currency'] ;
				break;		
				}
				
				//cooldown
				$ztime       = time();
				$sdate = intval( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(sdate) FROM `$table_messages` WHERE sender_id=%d", $userID ) ) );
				if ( $sdate > $ztime - 10 ) {
					$response['warning'] =  __('You just sent a paid message. Try again after 10s.', 'ppv-live-webcams') ; // 10s
					break;
				}

				$messageText       = wp_encode_emoji( sanitize_textarea_field( $message['text'] ) );
				$messageUser       = sanitize_text_field( $message['userName'] );
				$messageUserAvatar = esc_url_raw( $message['userAvatar'] );

				$meta  = array(
					'userAvatar'     => $messageUserAvatar,
					'cost' => $messagesCost,
					'balance' => $balance,
				);
				
				if ( isset( $message[ 'language' ] ) ) 
				{
					$meta[ 'language' ] = sanitize_text_field( $message[ 'language' ] );
					$meta[ 'flag' ] = sanitize_text_field( $message[ 'flag' ] );
				}
				
				$metaS = serialize( $meta );
				$sqlIM = $wpdb->prepare("INSERT INTO `$table_messages` ( `sender_id`, `webcam_id`, `reply_id`, `sdate`, `message`, `meta`) VALUES ( %d, %d, %d, %d, %s, %s)", $userID, $postID, 0, $ztime, $messageText, $metaS);
				$wpdb->query($sqlIM);
				$response['insertID'] = $wpdb->insert_id;


				// pay for message
				if ( $messagesCost ) {
					self::transaction( $ref = 'paid_message', $userID, - $messagesCost, __( 'Paid Message to', 'ppv-live-webcams' ) . ' <a href="' . self::roomURL( $post->post_name ) . '">' . $post->post_title . '</a>', $response['insertID'], $message, $options );
				}

				$needUpdate['questions'] = true;


				break;
				
				
			case 'questions_message': //paid question replies
			
				$message = isset( $_POST['message'] ) ? (array) $_POST['message'] : ''; // array elements sanitized individually
				$messageSelected = intval($_POST['messageSelected']);
				
				
				$message = isset( $_POST['message'] ) ? (array) $_POST['message'] : ''; // array elements sanitized individually
				
				if ( ! $message )					break;
				if ( ! $messageSelected )					break;

				
				$messageText       = wp_encode_emoji( sanitize_textarea_field( $message['text'] ?? '' ) );
				$messageUser       = sanitize_text_field( $message['userName'] ?? '' );
				$messageUserAvatar = esc_url_raw( $message['userAvatar'] ?? '' );

				$meta  = array(
					'userAvatar'     => $messageUserAvatar,
				);
		
				if ( isset( $message[ 'language' ] ) ) 
				{
					$meta[ 'language' ] = sanitize_text_field( $message[ 'language' ] );
					$meta[ 'flag' ] = sanitize_text_field( $message[ 'flag' ] );
				}
				
						// get paid
							$messagesCost = floatval( get_post_meta( $postID, 'question_price', true ) );
							if ( ! $messagesCost ) {
								$messagesCost = floatval( $options['messagesCost'] );
							}
			
							$performerRatio = self::performerRatio( $post->post_name, $options, $postID );

				
				$meta['cost'] = $messagesCost;
				$meta['ratio'] = $performerRatio;

		
				$metaS = serialize( $meta );

				$sqlIM = $wpdb->prepare("INSERT INTO `$table_messages` ( `sender_id`, `webcam_id`, `reply_id`, `sdate`, `message`, `meta`) VALUES ( %d, %d, %d, %d, %s, %s)", $userID, $postID, $messageSelected, $ztime, $messageText, $metaS);
				$wpdb->query( $sqlIM );
				$response['insertID'] = $wpdb->insert_id;
				
				
				// update last message time
				$sqlU = $wpdb->prepare("UPDATE `$table_messages` SET `ldate` = %d WHERE id = %d", $ztime, $messageSelected);
				$wpdb->query( $sqlU );

				// get paid for first reply
				$sqlC         = $wpdb->prepare("SELECT COUNT(id) FROM `$table_messages` WHERE reply_id = %d", $messageSelected);
				$repliesCount = $wpdb->get_var( $sqlC );

				// only get paid for first reply and  if cost enabled
				if ( $repliesCount == 1 && $messagesCost ) {
					
										
					$messageCode = self::to62( $messageSelected );

					$messageURL = add_query_arg(
						array(
							'messages' => 'view',
							'message'  => $messageCode,
						),
						$this_page
					);

					self::transaction( $ref = 'paid_message_earn', $sender_id, $messagesCost * $performerRatio, __( 'Paid Message Earning from ', 'ppv-live-webcams' ) . ' <a href="' . $messageURL . '">' . $messageCode . '</a>', $response['insertID'], $message, $options );
					}
					
				$needUpdate['questions'] = true;
					
				break;

			case 'message':
				$message = isset( $_POST['message'] ) ? (array) $_POST['message'] : ''; // array elements sanitized individually
				if ( ! $message ) {
					break;
				}

				$messageText       =  wp_encode_emoji( sanitize_textarea_field( $message['text'] ) );
				$messageUser       = sanitize_text_field( $message['userName'] );
				$messageUserAvatar = esc_url_raw( $message['userAvatar'] );

				$meta  = array(
					'notification'   => $message['notification'] ?? '',
					'userAvatar'     => $messageUserAvatar,
					'mentionMessage' => intval( $message['mentionMessage'] ?? 0 ),
					'mentionUser'    => sanitize_text_field( $message['mentionUser'] ?? '' ),
				);

				if ( isset( $message[ 'language' ] ) ) 
				{
					$meta[ 'language' ] = sanitize_text_field( $message[ 'language' ] );
					$meta[ 'flag' ] = sanitize_text_field( $message[ 'flag' ] );
				} else if ( $options['multilanguage'] && $options['languageDefault'] )
				{
					$meta[ 'language' ] = $options['languageDefault'];
					$meta[ 'flag' ] = self::language2flag($meta[ 'language' ]);
				}
				
				if ($privateSession) if ( isset($privateSession['id']) ) $meta['privateSession'] = $privateSession['id'];
				
				$metaS = serialize( $meta );

				if ( ! $privateUID ) {
					$privateUID = 0; // public room
				}

				// msg type: 2 web, 1 flash, 3 own notification
				$sql = $wpdb->prepare(
					"INSERT INTO `$table_chatlog` ( `username`, `room`, `room_id`, `message`, `mdate`, `type`, `user_id`, `meta`, `private_uid`) VALUES (%s, %s, %d, %s, %d, %s, %d, %s, %d)",
					$messageUser, $roomName, $roomID, $messageText, $ztime, '2', $userID, $metaS, $privateUID
				);
				$wpdb->query( $sql );

				$response['sql'] = $sql;

				$response['insertID'] = $wpdb->insert_id;

				// also update chat log file
				if ( $roomName ) {
					if ( $messageText ) {

						$messageText = strip_tags( $messageText, '<p><a><img><font><b><i><u>' );

						$messageText = date( 'F j, Y, g:i a', $ztime ) . " <b>$userName</b>: $messageText";

						$proom = '';
						
						// generate same private room folder for both users
						if ( $privateUID ) {
							if ( $isPerformer ) {
								$proom = $userID . '_' . $privateUID; // performer id first
							} else {
								$proom = $privateUID . '_' . $userID;
							}
						}

						$dir = $options['uploadsPath'];
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}

						$dir .= "/$roomName";
						if ( ! file_exists( $dir ) ) {
							mkdir( $dir );
						}

						if ( $proom ?? false) {
							$dir .= "/$proom";
							if ( ! file_exists( $dir ) ) {
								mkdir( $dir );
							}
						}

						$day = date( 'y-M-j', time() );

						$dfile = fopen( $dir . "/Log$day.html", 'a' );
						fputs( $dfile, $messageText . '<BR>' );
						fclose( $dfile );
					}
				}

				break;
		}

		// update time
		$lastMessage   = intval( $_POST['lastMessage'] ?? 0 );
		$lastMessageID = intval( $_POST['lastMessageID'] ?? 0 );

		// retrieve only messages since user came online / updated
		$sdate = 0;
		if ( $session ) {
			$sdate = $session->sdate;
		}
		$startTime = max( $sdate, $lastMessage );

		if ($options['chatlogPrevious'] ?? false) $startTime = 0; // get all messages in history

		$response['startTime'] = $startTime;

		// !messages
		// SELECT * FROM `ksk_vw_vmls_chatlog` WHERE room='Video.Whispers' AND (type < 3 OR (type=3 AND user_id='2136' AND username='Video.Whispers')) AND private_uid ='1' AND (user_id = '2136' OR user_id = '1')

		// clean old chat logs
		$chatlog = intval($options['chatlog'] ?? 900);
		if (!$chatlog) $chatlog = 900;

		$closeTime = time() - $chatlog; // only keep for 15min
		$sql       = $wpdb->prepare("DELETE FROM `$table_chatlog` WHERE mdate < %d", $closeTime);
		$wpdb->query( $sql );

		$items = array();

		$cndNotification = $wpdb->prepare("AND (type < %d OR (type=%d AND user_id=%d AND username=%s))", 3, 3, $userID, $userName); // chat message or own notification (type 3)

		$cndPrivate = "AND private_uid = '0'";
		if ( $privateUID ?? false ) {
			$cndPrivate = $wpdb->prepare("AND ( (private_uid = %d AND user_id = %d) OR (private_uid = %d AND user_id = %d) )", $privateUID, $userID, $userID, $privateUID); // messages in private from each to other
		}

		$cndTime = $wpdb->prepare("AND mdate >= %d AND mdate <= %d AND id > %d", $startTime, $ztime, $lastMessageID);

		$sql = $wpdb->prepare("SELECT * FROM `$table_chatlog` WHERE room_id=%d $cndNotification $cndPrivate $cndTime ORDER BY mdate DESC LIMIT 0,100", $postID); // limit to last 100 messages, until processed date
		$sql = "SELECT * FROM ($sql) items ORDER BY mdate ASC"; // but order ascendent

		$response['sqlMessages'] = $sql;

		$sqlRows = $wpdb->get_results( $sql );

		$idMax = 0;
		if ( $wpdb->num_rows > 0 ) {
			foreach ( $sqlRows as $sqlRow ) {
				$item = array();

				$item['ID'] = intval( $sqlRow->id );

				if ( $item['ID'] > $idMax ) {
					$idMax = $item['ID'];
				}

				$item['userName'] = $sqlRow->username;
				$item['userID']   = intval( $sqlRow->user_id );

				$item['text'] = html_entity_decode( stripslashes( $sqlRow->message ) );
				$item['time'] = intval( $sqlRow->mdate * 1000 ); // time in ms for js

				// avatar
				$uid = $sqlRow->user_id;
				if ( ! $uid ) {
					$wpUser = get_user_by( $userName, $sqlRow->username );
					if ( ! $wpUser ) {
						$wpUser = get_user_by( 'login', $sqlRow->username );
					}

					if ( $wpUser ) {
						$uid = $wpUser->ID;
					} else {
						$uid = 0;
					}
				}

				$item['userAvatar'] = get_avatar_url( $uid );

				// meta
				if ( $sqlRow->meta ) {
					$meta = unserialize( $sqlRow->meta );
					foreach ( $meta as $key => $value ) {
						$item[ $key ] = $value;
					}

					$item['notification'] = ( isset($meta['notification']) && $meta['notification'] == 'true' ? true : false );
				}

				if ( $sqlRow->type == 3 ) {
					$item['notification'] = true;
				}
				
				$skipItem = 0;
				if ($room_random) if ($privateSession) if ( isset($privateSession['id']) ) 
				{
					$skipItem = 1;
					if ( isset( $item['privateSession'] ) && $privateSession['id'] == isset( $item['privateSession'] ) ) $skipItem = 0;
				}
				
				if (!$skipItem) $items[] = $item;
			}
		}

		$response['messages'] = $items; // messages list

		$response['timestamp'] = $ztime; // update time

		$response['lastMessageID'] = $idMax;

		// update message

		// !actions
		// clean old actions
		$closeTime = time() - 900; // only keep for 15min
		$sql       = $wpdb->prepare("DELETE FROM `$table_actions` WHERE mdate < %d", $closeTime);
		$wpdb->query( $sql );

		$items = array();

		// action request
		// status:
		// sender requests 0, recipient received 1, recipient answered 2, recipient executed 3, sender received execution 4, sender executed and closed 5
		// 7,8 closed, 10 both closed

		// maybe status 1 (may not receive it - would need receipt confirmation from client to mark 1)

		$sql = $wpdb->prepare("SELECT * FROM `$table_actions` WHERE room_id = %d AND (target_id = %d OR target_id = 0 AND status < 3) OR (user_id = %d AND status < 5) AND mdate <= %d ORDER BY mdate DESC LIMIT 0, 100", $roomID, $userID, $userID, $ztime);
		$sqlRows = $wpdb->get_results($sql);

		if ( $wpdb->num_rows > 0 ) {
			foreach ( $sqlRows as $sqlRow ) {

				$item = array();

				$item['ID'] = intval( $sqlRow->id );

				$item['userID']   = intval( $sqlRow->user_id );
				$item['targetID'] = intval( $sqlRow->target_id );

				$item['roomID'] = intval( $sqlRow->room_id );

				$item['action'] = $sqlRow->action;
				$item['time']   = intval( $sqlRow->mdate * 1000 ); // time in ms for js

				$item['status'] = intval( $sqlRow->status );
				$item['answer'] = intval( $sqlRow->answer );

				// meta
				if ( $sqlRow->meta ) {
					$item['meta'] = unserialize( $sqlRow->meta );
				}

				// replying to sender and also moving sender to private room
				if ( $sqlRow->user_id == $userID && $sqlRow->action == 'privateRequest' && $item['status'] == 3 ) {

					// sender (user_id) execute : move sender to room
					$response['room'] = self::appPrivateRoom( $post, $session, $sqlRow->target_id, $sqlRow->id, $options );

					// sender executed (5)
					$sql = $wpdb->prepare("UPDATE `$table_actions` SET status = %d, mdate = %d WHERE `id` = %d", 5, $ztime, $sqlRow->id);
					$wpdb->query( $sql );
				}

				if ( $item['status'] < 7 ) {
					$items[] = $item; // process action client side
				}
			}
		}

		$response['actions'] = $items; // messages list

		// actions

		// collaboration

		// check if private was closed and go back to main room, or update time if active
		if ( ( $privateUID ?? false ) && $roomActionID ) {
			// select action to answer from db
			$sqlS    = $wpdb->prepare("SELECT * from `$table_actions` WHERE `id` = %d AND status < %d", $roomActionID, 7);
			$actionS = $wpdb->get_row( $sqlS );

			$goPublic = 0;
			if ( ! $actionS ) {
				$goPublic = 1;
			} else {
				$wpdb->query( $wpdb->prepare("UPDATE `$table_actions` SET mdate = %d WHERE `id` = %d", $ztime, $roomActionID) );
			}

			if ( $goPublic ) {
				// close private session
				if ( $isPerformer ) {
					$pid = $userID;
					$cid = $privateUID;
				} else {
					$cid = $userID;
					$pid = $privateUID;
				}

				$table_private = $wpdb->prefix . 'vw_vmls_private';
				$wpdb->query( $wpdb->prepare("UPDATE `$table_private` SET status = %d WHERE `status` = %d AND rid = %d AND pid = %d AND cid = %d", 1, 0, $postID, $pid, $cid) );

				self::notificationMessage( __( 'Private call was closed.', 'ppv-live-webcams' ), $session, 0 );
				$response['room'] = self::appPublicRoom( $post, $session, $options, __( 'Private call was closed.', 'ppv-live-webcams' ), $public_room, $requestUID );
			}
		}

		// Party

		$party = self::is_true( get_post_meta( $postID, 'party', true ) );

		if ( $options['videochatNext'] && $party ) {

			if ( $session->meta ) {
				$userMeta = unserialize( $session->meta );
			}
			if ( ! is_array( $userMeta ) ) {
				$userMeta = array();
			}

			$updateMeta = 0;

			if ( array_key_exists( 'party', $userMeta ) ) {
				$partyID = $userMeta['party'];

				$partyRoomID = get_post_meta( $partyID, 'partyRoomID', true ); // check if party moved to follow
				if ( ! $partyRoomID ) {
					$partyRoomID = $partyID; // party mode
				}

				if ( $postID != $partyRoomID ) {
					self::notificationMessage( __( 'Following party to different room.', 'ppv-live-webcams' ) . " #$partyID: $postID > $partyRoomID", $session, 0 );

					$nextPost = get_post( $partyRoomID );

					// create new session for new room
					$nextSession   = self::sessionUpdate( $userName, $nextPost->post_title, 0, 11, 0, 1, 1, $options, $userID, $partyRoomID, self::get_ip_address(), $userMeta );
					$nextSessionID = $nextSession->id;

					// meta inc party info
					$userMetaS = serialize( $userMeta );
					$sql       = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $nextSession->id);
					$wpdb->query( $sql );

					$response['user'] = array(
						'from' => 'party',
						'ID'        => intval( $userID ),
						'name'      => $userName,
						'sessionID' => intval( $nextSessionID ),
						'loggedIn'  => true,
						'balance'   => number_format( self::balance( $userID, true, $options ), 2, '.', ''  ),
						'cost'      => ( array_key_exists( 'cost', $userMeta ) ? $userMeta['cost'] : 0 ),
						'avatar'    => get_avatar_url( $userID, array( 'default' => dirname( plugin_dir_url( __FILE__ ) ) . '/images/avatar.png' ) ),
					);

					// move to next room
					$response['room'] = self::appPublicRoom( $nextPost, $nextSession, $options, __( 'Moved to different room with party.', 'ppv-live-webcams' ) );

					$session     = $nextSession;
					$post        = $nextPost;
					$changedRoom = 1;
				}
			} else // not in any party
				{

				$party = self::is_true( get_post_meta( $postID, 'party', true ) );
				if ( $party ) {

					$userMeta['party']     = $postID;
					$userMeta['partyName'] = $post->post_title;
					$userMeta['partyHost'] = boolval( $session->broadcaster );

					$userMetaS = serialize( $userMeta );
					$sql       = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $session->id);
					$wpdb->query( $sql );
				}
			}
		}

		// balance and room updates, except on login
		if ( $task != 'login' ) {
	
			
			$lastRoomUpdate = intval( $_POST['lastRoomUpdate'] ?? 0 );

			$balance        = floatval( self::balance( $userID, false, $options ) );
			$balancePending = floatval( self::balance( $userID, true, $options ) );

			// update user only if does not exist - if (!$privateUID)
			if ( ! array_key_exists( 'user', $response ) ) 
			{
				$response['user'] =
					array(
						'from' => 'balanceUpdate',
						'loggedIn'       => true,
						'balance'        => number_format( $balance, 2, '.', ''  ),
						'balancePending' => number_format( $balancePending, 2, '.', ''  ),
						// 'balancePending' => 2.35,
						'cost'           => ( array_key_exists( 'cost', $userMeta ) ? $userMeta['cost'] : 0 ),
						// 'cost' => 5.2,
						'time'           => ( $session->edate - $session->sdate ),
					);
					
						 if ($options['lovense'] && $isPerformer) 
						 {	
							 //recent tips to process
			 				$tipsRecent = get_user_meta( $userID, 'tipsRecent', true );
							if (!is_array($tipsRecent)) $tipsRecent = [];
							foreach ($tipsRecent as $key => $tip) if (time() - $tip['time'] > 600) unset($tipsRecent[$key]); //erase older than 10min
							update_user_meta( $userID, 'tipsRecent', $tipsRecent );
							$response['user']['tipsRecent'] = $tipsRecent;
						}

			}

			if ( $balance < 0 ) {
				$response['error'] = 'Error: Negative balance. Can be a result of enabling cache for user requests. Contact site administrator to review and fix balance.';
			}

			// low balance notification for users only

			if ( $userID ) {
				$warnTime    = 0;
				$lastWarning = intval( get_user_meta( $userID, 'balance_warning' . $privateUID, true ) );
				if ( time() - $lastWarning > 300 ) {
					$warnTime = 1; // warn coolddown 5 min
				}

				// notify low balance, warn 1 amount > warn 2 amount
				if ( ! $isPerformer ) {
					if ( $balance > 0 && $warnTime && ( $balance < $options['balanceWarn1Amount'] || $balance < $options['balanceWarn2Amount'] ) ) {
						$notify = '';

						// low balance
						if ( $balance < $options['balanceWarn1Amount'] ) {
							$notify        = 'BalanceLow';
							$notifyMessage = $options['balanceWarn1Message'];
							$notifySound   = $options['balanceWarn1Sound'];
						}

						// critical balance
						if ( $balance < $options['balanceWarn2Amount'] ) {
							$notify        = 'BalanceCritical';
							$notifyMessage = $options['balanceWarn2Message'];
							$notifySound   = $options['balanceWarn2Sound'];
						}

						if ( $notify ) {
							self::notificationMessage(
								$notifyMessage,
								$session,
								$privateUID,
								array(
									'type'  => $notify,
									'sound' => $notifySound,
								)
							);
							update_user_meta( $userID, 'balance_warning' . $privateUID , time() );
						}
					}
				}
			}

			// balance

			$updateTime = get_user_meta( $userID, 'updated_options', true );
			if ( $updateTime ) {
				if ( $updateTime > $lastRoomUpdate ) {
					$needUpdate['user'] = 1;
				}
			}
			if ( $needUpdate['user'] ) {
				$response['user']['options']            = self::appUserOptions( $session, $options );
				$response['user']['options']['updated'] = true;
			}

			// update room
			if ( ! $changedRoom ) {
				// items that need update: for everybody
				foreach ( array( 'files', 'media', 'options', 'room' ) as $update ) {
					if ( ! $needUpdate[$update] ) {
						$updateTime = get_post_meta( $postID, 'updated_' . $update, true );
						if ( $updateTime ) {
							if ( $updateTime > $lastRoomUpdate ) {
								$needUpdate[ $update ] = 1; // change after last msg: need update		
							} 
						}
					}
				}

				//check if 
				

				// $needUpdate[] - send items marked for update
				if ( $needUpdate['room'] && ! $privateUID ) {
					$response['roomUpdate'] = self::appPublicRoom( $post, $session, $options, '', $response['roomUpdate'], $requestUID ); // no room update during private
				} else // update room in full or just sections
					{
					
					if ( $needUpdate['files'] ) {
						$response['roomUpdate']['files'] = self::appRoomFiles( $roomName, $options );
					}
					
					if ( $needUpdate['media'] ) {
						$response['roomUpdate']['media'] = self::appRoomMedia( $post, $session, $options );
					}
					
					if ( $needUpdate['options'] ) {
						$response['roomUpdate']['options'] = self::appRoomOptions( $post, $session, $options );
					}
					
					if ( $needUpdate['questions'] )
					$response['roomUpdate']['questionsMessages'] = self::appRoomMessages( $post->ID, $options, ( $session->broadcaster ? 0 : $session->uid), 0, 1, 1,  $session->broadcaster);				

				}

				$response['roomUpdate']['users']   = self::appRoomUsers( $post, $options ); // always update online users list
				$response['roomUpdate']['updated'] = $ztime;
			}
		}

		echo json_encode( $response );
		die();

	}

}
