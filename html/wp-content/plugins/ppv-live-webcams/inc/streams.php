<?php
namespace VideoWhisper\LiveWebcams;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait Streams {
	// streams, FFmpeg

	

	static function rtmpServer( $postID, $options = '' ) {
		$rtmp_server = '';

		if (!$options) $options = self::getOptions();
		if ($options['rtmpServer'] == 'videowhisper') return $options['videowhisperRTMP']; //videowhisper

		if ( $postID ) {
			$rtmp_server = get_post_meta( $postID, 'rtmp_server', true );
			if ( $rtmp_server ) return $rtmp_server;
		}
		return $options['rtmp_server']; //wowza se default

	}


	static function rtmp_address( $userID, $postID, $broadcaster, $username, $room, $options ) {

		$roomRTMPserver = self::rtmpServer( $postID, $options );

		if ($options['rtmpServer'] == 'videowhisper') {
			return $roomRTMPserver . '//' . trim($options['vwsAccount']) . '/' . trim($username) . '?pin=' . self::getPin($userID, 'broadcast', $options); 
		}

		//wowza 
		if ( $broadcaster ) {
			$key = md5( 'vw' . $options['webKey'] . $userID . $postID );
			return $roomRTMPserver . '?' . urlencode( $username ) . '&' . urlencode( $room ) . '&' . $key . '&1&' . $userID . '&videowhisper';
		} else {
			$keyView = md5( 'vw' . $options['webKey'] . $postID );
			return $roomRTMPserver . '?' . urlencode( $username ) . '&' . urlencode( $room ) . '&' . $keyView . '&0' . '&videowhisper';
		}

		return $roomRTMPserver;
	}

	static function generatePin()
	{
		return rand(10000, 99999);
	}

	static function vmls_notify()
	{
	
		//ini_set('display_errors', '1');
		//error_reporting(E_ALL & ~E_NOTICE);

	//called by videowhisper streaming server to notify current streaming status (i.e to display rooms as live)

	$options = self::getOptions();

	// output clean
	if (ob_get_length()) {
		ob_clean();
	}

	$token = sanitize_text_field($_POST['token'] ?? '');

	if (!$token || $token != $options['vwsToken']) {
		echo json_encode( ['deny' => 1, 'info' => 'Invalid account token: ' . $token, 'POST' => $_POST] );
		exit();
	}

	self::requirementMet( 'rtmp_status' );

					// start logging
					$dir       = $options['uploadsPath'];
					$filename1 = $dir . '/_rtmpStreams.txt';
					$dfile     = fopen( $filename1, 'w' );

					fputs( $dfile, 'VideoWhisper Log for RTMP Streams' . "\r\n" );
					fputs( $dfile, 'Server Date: ' . "\r\n" . date( 'D M j G:i:s T Y' ) . "\r\n" );
					fputs( $dfile, '$_POST:' . "\r\n" . json_encode( $_POST ) );

					
					
		$streams= json_decode( stripslashes( $_POST['streams'] ?? ''), true ) ;
		if (json_last_error() !== JSON_ERROR_NONE) 
		{
					// Handle the error appropriately
					fputs( $dfile,  "\r\n" . 'JSON Error:' .  json_last_error_msg() );
		}else  fputs( $dfile,  "\r\n" . 'Streams:' . json_encode( $streams ) . ' A:'. is_array( $streams ) );

	$rtmp_test = 0;
	$ztime = time();

	$resultStreams = [];
	
	global $wpdb;

	if ( is_array( $streams ) ) 
	foreach ($streams as $stream => $params) 
		{

		$resultStreams[$stream] = [ 'name' => $stream];

			// an user is connected on rtmp: works
			if ( ! $rtmp_test ) {
				self::requirementMet( 'rtmp_test' );
				$rtmp_test = 1;
			}

		$user = get_user_by('slug', $stream);
		if ($user)
		{
			$postID = get_user_meta( $user->ID, 'currentWebcam', true );
			$room = $stream;
			$disconnect = '';
			$resultStreams[$stream] = [ 'userID' => $user->ID, 'postID' => $postID ];
			
			if ( $postID ) {
				
				$post = get_post($postID);
				if ($post) $room = $post->post_title;
				$r = $room;
					

					$resultStreams[$stream]['room'] = $room;
					
					// sessionUpdate($username='', $room='', $broadcaster=0, $type=1, $strict=1, $updated=1);
					$session = self::sessionUpdate( $stream, $room, 1, 9, 0, 0, 0, $options, -1 ); // not strict 

						// update session meta
						$table_sessions = $wpdb->prefix . 'vw_vmls_sessions';

						if ( $session->meta ) {
							$userMeta = unserialize( $session->meta );
						}
						if ( ! is_array( $userMeta ) ) {
							$userMeta = array();
						}

						$userMeta['external']       = true;
						$userMeta['externalUpdate'] = $ztime;
						$userMetaS                  = serialize( $userMeta );
						$sql = $wpdb->prepare("UPDATE `$table_sessions` SET meta=%s WHERE id=%d", $userMetaS, $session->id);
						$wpdb->query($sql);
				
						//$resultStreams[$stream]['userMeta'] = $userMeta;

						if ( self::roomSuspended( $postID, $options ) ) $disconnect = __( 'This room was suspended.', 'ppv-live-webcams' );

						if ( $options['bannedNames'] ) {
							if ( $ban = self::containsAny( $r, $options['bannedNames'] ) ) {
								$disconnect = "Room name banned ($ban)!";
							}
						}

						$resultStreams[$stream]['disconnect'] = $disconnect;

							// generate external snapshot for external broadcaster
						if ( $session->broadcaster ) {
							self::streamSnapshot( $session->session, true, $postID );
						}

		
							$streamType = get_post_meta( $postID, 'stream-type', true );

							update_post_meta( $postID, 'edate', $ztime );

							update_post_meta( $postID, 'stream-protocol', 'rtmp' );
							update_post_meta( $postID, 'stream-type', 'external' );
							update_post_meta( $postID, 'stream-updated', $ztime );

							// type changed
							if ( $streamType != 'external' ) {
								update_post_meta( $postID, 'updated_stream', $ztime );
							}

							self::updateViewers( $postID, $r, $options );

							

			} //end postID

		} //end user
	} //end foreach streams

	$result = ['time' => time(), 'streams' => $resultStreams, 'received' => count($streams) ];
	
	fputs( $dfile,  "\r\n" . 'Result:' . json_encode( $result )  );

	echo json_encode($result);
	exit();

	}

	static function vmls_stream()
	{
		//called by videowhisper streaming server to get stream broadcast/playback pins for stream validation

		$options = self::getOptions();

		// output clean
		if (ob_get_length()) {
			ob_clean();
		}

		$token = sanitize_text_field($_POST['token'] ?? '');
		$stream = sanitize_text_field($_POST['stream'] ?? '');

		if (!$token || $token != $options['vwsToken']) {
			echo json_encode(['deny' => 1, 'info' => 'Invalid account token']);
			exit();
		}

		if (!$stream) {
			echo json_encode(['deny' => 1, 'info' => 'Missing stream name']);
			exit();
		}

		//find user with $user->user_nicename == $stream
		$user = get_user_by('slug', $stream);
		if (!$user) {
			echo json_encode(['deny' => 1, 'info' => 'User not found for this stream: ' . $stream]);
			exit();
		}

		$result= [];
		$result['broadcastPin'] = self::getPin($user->ID, 'broadcast', $options);
		$result['playbackPin'] = self::getPin($user->ID, 'playback', $options);
		$result['uid'] = $user->ID;

	 	echo json_encode($result);
		exit();
	}

	static function getPin($userID, $type = 'broadcast', $options = null)
	{
		if (!$options) $options = self::getOptions();

		if ($options['videowhisperStream'])
		{
			$user = get_user_by('id', $userID);
			if (!$user) return '';

			if ($type == 'broadcast')
			{
				$broadcastPin = get_user_meta($user->ID, 'broadcastPin', true);
				if (!$broadcastPin)
				{
					$broadcastPin = self::generatePin();
					update_user_meta($user->ID, 'broadcastPin', $broadcastPin);
				}
				return $broadcastPin;
			}
			else
			{
				$playbackPin = get_user_meta($user->ID, 'playbackPin', true);
				if (!$playbackPin)
				{
					$playbackPin = self::generatePin();
					update_user_meta($user->ID, 'playbackPin', $playbackPin);
				}
				return $playbackPin;
			}
		}

		if ($type == 'broadcast') return trim($options['broadcastPin']);
		return trim($options['playbackPin']);
	}

	static function restreamUpdate($postID, $options = null, $post = null )
	{
		//build streams

		if ( !$options ) $options = self::getOptions();

		if ( !$options['reStreams'] ) return 'ReStreaming disabled from settings.';

		if (!$post) $post = get_post($postID);

		if ( !file_exists( $options['streamsPath'] ) ) return 'Configured streams path does not exist!';

		$reStreams = get_post_meta( $postID, 'reStreams', true );

			if ( ! $reStreams ) {
				$reStreams = array();
				}

				if ( ! is_array( $reStreams ) ) {
					$reStreams = array( $reStreams );
				}

				//add stream file if not exists
				foreach ($reStreams as $stream => $address)
				{
					$streamPath = $options['streamsPath'] . '/' . $stream;
					if ( !file_exists( $streamPath ) )
					{
						$streamFile = fopen( $streamPath, 'w' );
						if ( $streamFile ) {
							fwrite( $streamFile, $address );
							fclose( $streamFile );
						}
					}

				}

				update_post_meta( $postID, 'updated_room', time() ); //stream list in room
	}


static function restreamPause( $postID, $options ) {

		$timeTo = self::timeTo( $stream . '/restreamPause' . $postID, 3, $options );

		if ( ! $timeTo ) {
			return "<!--VideoWhisper-restreamPause:$stream#$postID:not_timeTo=$timeTo-->"; // already checked recently (prevent several calls on same request)
		}

		if ( isset($options['restreamPause']) && $options['restreamPause'] ) {
			$paused = 1;
		} else {
			$paused = 0;
		}

		// updates restream Status
		if (isset($options['restreamTimeout']) && $options['restreamTimeout'] ) $activeTime = time() - $options['restreamTimeout'] - 1;
		else $activeTime = time() - 31;

		if ( $paused && $options['restreamAccessedUser'] ) {
			// access time
			$accessedUser = get_post_meta( $postID, 'accessedUser', true );
			if ( $accessedUser > $activeTime ) {
				$paused = 0;
			}
		}

		if ( $paused && $options['restreamAccessed'] ) {

			$accessed = get_post_meta( $postID, 'accessed', true );
			if ( $accesse > $activeTime ) {
				$paused = 0;
			}
		}

		if ( $paused && $options['restreamActiveOwner'] ) {
			// author site access time
			$userID     = get_post_field( 'post_author', $postID );
			$accessTime = get_user_meta( $userID, 'accessTime', true );

			if ( $accessTime > $activeTime ) {
				$paused = 0;
			}
		}

		if ( $paused && $options['restreamActiveUser'] ) {
			$userAccessTime = intval( get_option( 'userAccessTime', 0 ) );
			if ( $userAccessTime > $activeTime ) {
				$paused = 0;
			}
		}



			$streamsNew = get_post_meta( $postID, 'reStreams', true );
			if ( !$streamsNew ) $streamsNew = [];
			if ( !is_array($streamsNew) ) $streamsNew = [];

			if ( isset($options['streamsPath']) && $options['streamsPath'])
			foreach ($streamsNew as $stream => $address)
				{
					$streamFile = $options['streamsPath'] . '/' . $stream;

					if ( $paused ) {
						// disable
						if ( file_exists( $streamFile ) ) {
							unlink( $streamFile );
						}
					} else {
						// enable
						if ( ! file_exists( $streamFile ) ) {
							$myfile = fopen( $streamFile, 'w' );
							if ( $myfile ) {
								fwrite( $myfile, $address );
								fclose( $myfile );
							}
						}
					}

				}

		update_post_meta( $postID, 'restreamPaused', $paused );

		return  "<!--VideoWhisper-restreamPause:$stream#$postID:Paused=$paused-->";

	}

static function restreamTest($address, $options = null)
{

	if (!$address) return 'Missing address!';
	if (!$options) $options = self::getOptions();

			list($addressProtocol) = explode( ':', strtolower( $address ) );


			// try to retrieve a snapshot
			$dir = $options['uploadsPath'];
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			$dir .= '/_streams';
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			if ( ! file_exists( $dir ) ) {
				$error = error_get_last();
				return 'Error - Folder does not exist and could not be created: ' . $dir . ' - ' . $error['message'];
			}

			$stream = 'stream' . time();

			$filename     = "$dir/$stream.jpg";
			$log_file     = $filename . '.txt';
			$log_file_cmd = $filename . '-cmd.txt';

			$cmdP = '';
			$cmdT = '';

			// movie streams start with blank screens
			if ( strstr( $address, '.mp4' ) || strstr( $address, '.mov' ) || strstr( $address, 'mp4:' ) ) {
				$cmdT = '-ss 00:00:02';
			}

			if ( $addressProtocol == 'rtsp' ) {
				$cmdP = '-rtsp_transport tcp'; // use tcp for rtsp
			}

			$cmd = $options['ffmpegSnapshotTimeout'] . ' ' . $options['ffmpegPath'] . " -y -frames 1 \"$filename\" $cmdP $cmdT -i \"" . $address . "\" >&$log_file  ";

			// echo $cmd;
			if ( $options['enable_exec'] ) exec( $cmd, $output, $returnvalue );
			if ( $options['enable_exec'] ) exec( "echo '$cmd' >> $log_file_cmd", $output, $returnvalue );

			$lastLog = $options['uploadsPath'] . '/lastLog-streamTryTCP.txt';
			self::varSave(
				$lastLog,
				array(
					'file'    => $log_file,
					'cmd'     => $cmd,
					'return'  => $returnvalue,
					'output0' => $output[0],
					'time'    => time(),
				)
			);

			$devInfo = '';
			$devInfo = "[RTSP-TCP:$cmd]";

			// try also try over udp without $cmdP
			if ( ! file_exists( $filename ) ) {
				$cmd = $options['ffmpegSnapshotTimeout'] . ' ' . $options['ffmpegPath'] . " -y -frames 1 \"$filename\" $cmdT -i \"" . $address . "\" >&$log_file  ";

				// echo $cmd;
				if ( $options['enable_exec'] ) exec( $cmd, $output, $returnvalue );
				if ( $options['enable_exec'] ) exec( "echo '$cmd' >> $log_file_cmd", $output, $returnvalue );

				$lastLog = $options['uploadsPath'] . '/lastLog-streamTryUDP.txt';
				self::varSave(
					$lastLog,
					array(
						'file'    => $log_file,
						'cmd'     => $cmd,
						'return'  => $returnvalue,
						'output0' => $output[0],
						'time'    => time(),
					)
				);

					$devInfo .= " [RTSP-UDP:$cmd]";
			}

			// failed
			if ( ! file_exists( $filename ) ) {

				$htmlCode .= 'Snapshot could not be retrieved from ' . $addressProtocol . ': ' . $address . $devInfo  ;
			}
			else
			{
			$previewSrc = self::path2url( $filename );
			$htmlCode .= '<br>A snapshot was retrieved: <br> <IMG class="ui rounded image big" SRC="' . $previewSrc . '"><br>' . $devInfo;


			//retrieve info
			$command = $options['ffmpegPath'] . ' -probesize 5M -analyzeduration 5M -t 5 -i "'. $address . '" -f null -hide_banner -y /dev/null 2>&1';
			$output = shell_exec($command);

			$htmlCode .= '<br>Command: ' . $command;
			$htmlCode .= '<br><pre>' . $output . '</pre>';


			$videoCodecPattern = '/Stream #[0-9]+:[0-9]+: Video: ([^,]+),/';
			$audioCodecPattern = '/Stream #[0-9]+:[0-9]+: Audio: ([^,]+),/';
			$bitratePattern = '/bitrate: ([0-9]+) kb\/s/';
			$dataProcessedPattern = '/video:([0-9]+)kB/';

			$videoCodec = null;
			$audioCodec = null;
			$totalBitrate = null;

			if (preg_match($videoCodecPattern, $output, $matches)) {
				$videoCodec = $matches[1];
			}

			if (preg_match($audioCodecPattern, $output, $matches)) {
				$audioCodec = $matches[1];
			}

			if (preg_match($bitratePattern, $output, $matches)) {
				$totalBitrate = $matches[1];
			} elseif (preg_match($dataProcessedPattern, $output, $matches)) {
				$dataInKB = $matches[1];
				$totalBitrate = ($dataInKB * 8 * 1024) / 5; // Given the 5-second duration
				$totalBitrate = round($totalBitrate / 1024); // Convert to kbps
			}

			$htmlCode .= "<br>Video Codec: " . ($videoCodec ?: 'n/a') . "\n";

			$htmlCode .= "<br>Audio Codec: " . ($audioCodec ?: 'n/a') . "\n";

			$htmlCode .= "<br>Total Bitrate: " . ($totalBitrate ?: 'n/a') . " kb/s\n";

			}



			return $htmlCode ;

			}
//

	static function notifyLive( $user, $postID, $options = null ) {

		// BuddyPress Activity
		if ( function_exists( 'bp_activity_add' ) ) {

			if ( ! $options ) {
				$options = get_option( 'VWliveWebcamsOptions' );
			}

			if ( ! $options['activityCooldown'] ) {
				$options['activityCooldown'] = 300;
			}

			$lastUpdate = intval( get_user_meta( $user->ID, 'vwActivityPost', true ) );

			if ( $lastUpdate < time() - intval( $options['activityCooldown'] ) )
			if ( get_post_meta( $postID, 'hasThumb', true ) || has_post_thumbnail( $postID ) ) //only if has thumbnail
			{

				$post = get_post( $postID );
				$mode = get_post_meta( $postID, 'groupMode', true );

				$postCode = ' <a href="' . get_permalink( $postID ) . '">' . $post->post_title . ' / ' . $mode . '</a>';

				$args        = array(
					'action'       => '<a href="' . bp_core_get_user_domain( $user->ID ) . 'activity">' . $user->display_name . '</a> ' . __( 'is live', 'ppv-live-webcams' ) . ': ' . $postCode,
					'component'    => 'livewebcams',
					'type'         => 'online',
					'primary_link' => get_permalink( $postID ),
					'user_id'      => $user->ID,
					'item_id'      => $postID,
					'content'      => '<a href="' . get_permalink( $postID ) . '">' . get_the_post_thumbnail( $postID, array( 150, 150 ), array( 'class' => 'ui small rounded middle aligned spaced image' ) ) . '</a> ' . $post->post_excerpt,
				);
				$activity_id = bp_activity_add( $args );

				update_user_meta( $user->ID, 'vwActivityPost', time() );
			}
		}
	}


	static function streamPush( $session, $stream = '', $type = 'rtmp', $postID = 0, $options = null ) {
		if ( ! $options['enable_exec'] ) {
			return;
		}

		if ( ! $options['pushStreams'] ) {
			return;
		}

		if ( ! $postID ) {
			$postID = $session->rid;
		}
		if ( ! $postID ) {
			return; // no room, no record
		}
		if ( ! $stream ) {
			$stream = $session->username;
		}
		$room = $session->room ? $session->room : $postID;

		$isPerformer = self::isPerformer( $session->uid, $postID );
		if ( ! $isPerformer ) {
			return; // pushing only performer stream
		}

		if ( ! self::timeTo( $room . '/push-' . $stream, 29, $options ) ) {
			return;
		}

		$pushDestinations = get_post_meta( $postID, 'pushStreams', true );
		if ( ! $pushDestinations ) {
			return;
		}
		if ( ! is_array( $pushDestinations ) ) {
			return;
		}

		// log path
		$dir = $options['uploadsPath'];
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}
		$dir .= '/' . $room;
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}

		$filename  = $stream . '_push' . time();
		$filepath  = $dir . '/' . $filename;
		$log_file .= $filepath . '.log';

		$pushingOn = 1;

		foreach ( $pushDestinations as $key => $destination ) {

			$address = $destination['address'];

			// detect push process - cancel if already started and disabled
			$cmd = "ps auxww | grep '$address'";
			if ( $options['enable_exec'] ) {
				exec( escapeshellcmd( $cmd ), $output, $returnvalue );
			}

			$pushing = 0;
			foreach ( $output as $line ) {
				if ( strstr( $line, 'ffmpeg' ) ) {
					$pushing = 1;

					// kill
					if ( ! $pushingOn ) {
						$columns = preg_split( '/\s+/', $line );
						$kcmd    = 'kill -KILL ' . $columns[1];
						if ( $options['enable_exec'] ) {
							exec( escapeshellcmd( $kcmd ), $koutput, $kreturnvalue );
						}
					}
				}
			}

			if ( $pushing ) {
				break; // already pushing
			}
			if ( ! $pushingOn ) {
				break; // disabled
			}

			// souce & command
			if ( $type == 'rtsp' ) {
				$userID      = 0;
				$streamQuery = self::webrtcStreamQuery( $session->uid, $postID, 0, $stream, $options, 1, $session->room );

				// usually webrtc
				$cmd = $options['ffmpegPath'] . ' -y -i "' . $options['rtsp_server'] . '/' . $streamQuery . '" ' . $options['pushTranscode'] . " -f flv \"$address\" >&$log_file & ";
			} else // type == 'rtmp'
				{

				$roomRTMPserver = self::rtmpServer( $postID, $options );

				if ( $options['externalKeysTranscoder'] ) {
					$keyView         = md5( 'vw' . $options['webKey'] . $postID );
					$rtmpAddressView = $roomRTMPserver . '?' . urlencode( 'ffmpegPush_' . $stream ) . '&' . urlencode( $stream ) . '&' . $keyView . '&0&videowhisper';
				} else {
					$rtmpAddressView = $roomRTMPserver;
				}

				$cmd = $options['ffmpegPath'] . ' -y -i "' . $rtmpAddressView . '/' . $stream . "\" -c:v copy -c:a copy -f flv \"$address\" >&$log_file & ";
			}

			$cmd = 'nice ' . $cmd;

			// start and log push process
			self::startProcess( $cmd, $log_file, $postID, $stream, 'push', $options );

			// end destinaton
		}
	}


	static function streamRecord( $session, $stream = '', $type = 'rtsp', $postID = 0, $options = null ) {

		if ( ! $options['enable_exec'] ) {
			return;
		}

		if ( $options['pushStreams'] ) {
			self::streamPush( $session, $stream, $type, $postID, $options );
		}

		if ( !$options['recording'] ) return; //disabled

		if ( ! $postID ) {
			$postID = $session->rid;
		}
		if ( ! $postID ) {
			return; // no room, no record
		}
		if ( ! $stream ) {
			$stream = $session->username;
		}
		$room = $session->room ? $session->room : $postID;

		if ( ! self::timeTo( $room . '/record-' . $stream, 29, $options ) ) {
			return;
		}

		// recording enabled?
		$streamRecord        = self::is_true( get_post_meta( $postID, 'stream_record', true ) ); // record performer
		$streamRecordAll     = self::is_true( get_post_meta( $postID, 'stream_record_all', true ) ); // record all user streams
		$streamRecordPrivate = self::is_true( get_post_meta( $postID, 'stream_record_private', true ) ); // record private streams

		$isPerformer      = self::isPerformer( $session->uid, $postID );
		$performerPrivate = self::is_true( get_post_meta( $postID, 'privateShow', true ) );

		$recordingOn = false;

		// public
		if ( ! $performerPrivate ) {
			$recordingOn = ( $streamRecord && $isPerformer ) || $streamRecordAll;
		}

		// private
		if ( $performerPrivate && $streamRecordPrivate ) {
			if ( $isPerformer ) {
				$recordingOn = true;
			} elseif ( $streamRecordAll ) {
				// only if this specific user is in private
				if ( $session->meta ) {
					$userMeta = unserialize( $session->meta );
				}
				if ( ! is_array( $userMeta ) ) {
					$userMeta = array();
				}
				if ( array_key_exists( 'privateUpdate', $userMeta ) ) {
					if ( $userMeta['privateUpdate'] >= time() - $options['onlineTimeout'] - 10 ) {
						$recordingOn = true;  // live in private
					}
				}
			}
		}

		// recordings path
		$dir = $options['uploadsPath'];
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}
		$dir .= '/' . $room;
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}
		$dir .= '/_recordings';
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}

		$filename  = $stream . '_' . time();
		$filepath  = $dir . '/' . $filename;
		$log_file .= $filepath . '.log';

		// detect recording process - cancel if already started and disabled
		$cmd = "ps auxww | grep '_recordings/$stream'";
		if ( $options['enable_exec'] ) {
			exec( escapeshellcmd( $cmd ), $output, $returnvalue );
		}

		$recording = 0;
		foreach ( $output as $line ) {
			if ( strstr( $line, 'ffmpeg' ) ) {
				$recording = 1;

				// kill
				if ( ! $recordingOn ) {
					$columns = preg_split( '/\s+/', $line );
					$kcmd    = 'kill -KILL ' . $columns[1];
					if ( $options['enable_exec'] ) {
						exec( escapeshellcmd( $kcmd ), $koutput, $kreturnvalue );
					}
				}
			}
		}

		if ( ! $recordingOn ) {
			return;
		}

		// souce & command
		if ( $type == 'rtsp' ) {
			$userID      = 0;
			$streamQuery = self::webrtcStreamQuery( $session->uid, $postID, 0, $stream, $options, 1, $session->room );

			$input = $options['rtsp_server'] . '/' . $streamQuery;
			// usually webrtc
			$cmd = $options['ffmpegPath'] . ' -y -i "' . $input . "\" -c:v copy -c:a copy \"$filepath.webm\" >&$log_file & ";
		} else // type == 'rtmp'
			{

			$roomRTMPserver = self::rtmpServer( $postID, $options );

			if ( $options['externalKeysTranscoder'] ) {
				$keyView         = md5( 'vw' . $options['webKey'] . $postID );
				$rtmpAddressView = $roomRTMPserver . '?' . urlencode( 'ffmpegSave_' . $stream ) . '&' . urlencode( $stream ) . '&' . $keyView . '&0&videowhisper';
			} else {
				$rtmpAddressView = $roomRTMPserver;
			}

			$input = $rtmpAddressView . '/' . $stream;

			$cmd = $options['ffmpegPath'] . ' -y -i "' . $input . "\" -c:v copy -c:a copy \"$filepath.mp4\" >&$log_file & ";
		}

		$cmd = 'nice ' . $cmd;

		// start and log recording process, if not already recording
		if ( ! $recording ) {
			if ( ! $options['recordingStack'] || $options['recordingStack'] == 'both' ) {
				self::startProcess( $cmd, $log_file, $postID, $stream, 'record', $options );
			}
		}

		if ( $options['recordingStack'] ) {
			self::streamRecordStack( $room, $postID, $stream, $input, $type, $options );
		}
	}

	static function streamRecordStack( $room, $postID, $stream, $input, $type, $options ) {
		if ( ! $options['enable_exec'] ) {
			return;
		}

		// recordings path
		$dir = $options['uploadsPath'];
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}
		$dir .= '/' . $room;
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}
		$dir .= '/_recordings';
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}

		$streamStack = '_stacked_' . time();
		$filepath    = $dir . '/' . $streamStack;
		$log_file   .= $filepath . '.log';

		$recStackPath = $dir . '/recordingStack.txt';
		$recStack     = self::varLoad( $recStackPath );

		if ( ! $recStack ) {
			$recStack = array();
		}
		$originalKeys = serialize( array_keys( $recStack ) );

		// update or add current stream
		$recStack[ $stream ] = array(
			'input'   => $input,
			'type'    => $type,
			'updated' => time(),
		);

		// remove timeout streams
		foreach ( $recStack as $stream => $params ) {
			$recStack[ $stream ]['expired'] = boolval( time() - $params['updated'] > $options['onlineTimeout'] );
		}
		foreach ( $recStack as $stream => $params ) {
			if ( $recStack[ $stream ]['expired'] ) {
				unset( $recStack[ $stream ] );
			}
		}
		// if (time() - $params['updated'] > intval($options['onlineTimeout'])) unset($recStack[$stream]);

		ksort( $recStack );

		self::varSave( $recStackPath, $recStack );

		$currentKeys = serialize( array_keys( $recStack ) );

		$update = 0;
		if ( $originalKeys != $currentKeys ) {
			$update = 1; // streams changed: update required
		}

		// detect recording process - cancel if already started and disabled
		$cmd = "ps auxww | grep '$room/_recordings/_stacked'";
		if ( $options['enable_exec'] ) {
			exec( escapeshellcmd( $cmd ), $output, $returnvalue );
		}

		$recording = 0;
		foreach ( $output as $line ) {
			if ( strstr( $line, 'ffmpeg' ) ) {
				$recording = 1;

				// kill if update needed
				if ( $update ) {
					$columns = preg_split( '/\s+/', $line );
					$kcmd    = 'kill -KILL ' . $columns[1];
					if ( $options['enable_exec'] ) {
						exec( escapeshellcmd( $kcmd ), $koutput, $kreturnvalue );
					}
				}
			}
		}

		if ( ! count( $recStack ) ) {
			return;
		}

		$type   = '';
		$inputs = '';
		$count  = 0;

		foreach ( $recStack as $stream => $params ) {
			$inputs .= ' -i "' . $params['input'] . '" ';
			if ( ! $type ) {
				$type = $params['type'];
			}
			if ( $type != $params['type'] ) {
				$type = 'mixed';
			}
			$count++;
		}

		if ( $count < 2 ) {
			return; // at least 2 required to stack
		}

		// $outputs = '-c:v copy -c:a copy';
		if ( $type == 'mixed' ) {
			$outputs = '-c:v libx264 -c:a libfdk_aac';
		}

		if ( $type == 'rtsp' ) {
			$outputs .= ' "' . $filepath . '.webm"';
		} else {
			$outputs .= ' "' . $filepath . '.mp4"';
		}

		// https://ottverse.com/stack-videos-horizontally-vertically-grid-with-ffmpeg/

		$cmd = $options['ffmpegPath'] . ' -y ' . $inputs . ' -filter_complex hstack=inputs=' . $count . ' ' . $outputs . ' >&' . $log_file . ' & ';

		$cmd = 'nice ' . $cmd;

		if ( ! $recording || $update ) {
			self::startProcess( $cmd, $log_file, $postID, $streamStack, 'record-stack', $options );
		}
	}

	static function webcamStreamName( $webcamName = '', $postID = 0, $options = '' ) {
		// current stream name (performer) for webcam

		$webcam = sanitize_file_name( $webcamName );

		if ( ! $options ) {
			$options = get_option( 'VWliveWebcamsOptions' );
		}

		if ( ! $postID ) {
			global $wpdb;
			$postID = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s and post_type = %s LIMIT 0,1', $webcam, $options['custom_post'] ) );

		}

		$stream = get_post_meta( $postID, 'performer', true );
		if ( ! $stream ) {
			$stream = $webcamName;
		}

		return sanitize_file_name( $stream );
	}


	// ! WebRTC
	static function webrtcStreamQuery( $userID, $postID, $broadcaster, $stream_webrtc, $options = null, $transcoding = 0, $room = '', $privateUID = 0 ) {

		if ( ! $options ) {
			$options = get_option( 'VWliveWebcamsOptions' );
		}
		$clientIP = self::get_ip_address();

		if ( ! $room ) {
			if ( $postID ) {
				global $wpdb;
				$room = sanitize_file_name( $wpdb->get_var( $wpdb->prepare( 'SELECT `post_title` FROM ' . $wpdb->posts . ' WHERE `ID` = %d LIMIT 0,1', $postID ) ) );
			}
		}

		if ( ! $room ) {
			$room = $stream_webrtc; // same as stream name
		}

		if ( $broadcaster ) {
			$key = md5( 'vw' . $options['webKey'] . $userID . $postID );

		} else {
			$key = md5( 'vw' . $options['webKey'] . $postID );
		}

		$streamQuery = sanitize_file_name( $stream_webrtc ) . '?channel_id=' . intval( $postID ) . '&userID=' . urlencode( intval( $userID ) ) . '&key=' . urlencode( $key ) . '&ip=' . urlencode( $clientIP ) . '&transcoding=' . $transcoding . '&room=' . urlencode( sanitize_file_name( $room ) ) . '&privateUID=' . intval( $privateUID );
		return $streamQuery;

	}

	static function moderateSnapshot($image, $postID, $options = null, $verbose = 0)
	{	

		//$verbose only in backend (displays api secret)

		if (!$image || !$postID) return 'Missing image or postID!';
		if (!$options) $options = self::getOptions();
		if (!$options['sightengine']) return 'Sightengine moderation not enabled!';  

		//sightengine integration
		if (!$options['sightengineUser'] || !$options['sightengineSecret']) return 'No Sightengine API credentials!';
		if (!function_exists('curl_init')) return 'No cURL support!' ;

		$htmlCode = '';

		$lastCheck = intval(get_post_meta($postID, 'sighengineLast', true));
		if (time() - $lastCheck < intval($options['sightengineInterval'])) return 'Recent check: ' . $lastCheck; 

		$params = array(
			'url' => self::path2url($image) . '?time=' . time(),
			'models' => trim($options['sightengineModels']),
			'api_user' => trim($options['sightengineUser']),
			'api_secret' => trim($options['sightengineSecret']),
		  );
		  
		  if ($verbose) $htmlCode .= '<br>Params: ' . print_r($params, true);

		  // this example uses cURL
		  $ch = curl_init('https://api.sightengine.com/1.0/check.json?'.http_build_query($params));
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  $response = curl_exec($ch);
		  curl_close($ch);

		  if ($verbose) $htmlCode .= '<br>Response: ' . $response;
		  $output = json_decode($response, true);

		  if ($output['status'] == 'success') {

			$detected = false;
			$detectedInfo = '';

			$detectModels = explode(',', $options['sightengineDetect']);
			$detectClasses = explode(',', $options['sightengineClasses']);

			if ($verbose) $htmlCode .= '<br>Detecting: ';
			
			if (is_array($detectModels)) foreach ($detectModels as $dModel) 
			{
				$model = trim($dModel);
				if (isset($output[$model])) 
				{
					if (is_array($output[$model])) 
					{
						foreach ($detectClasses as $dClass) 
						{
							$class = trim($dClass);
							if (isset($output[$model][$class])) 
							{
								if (floatval($output[$model][$class]) > floatval($options['sightengineThreshold']))
								{
									$detected = true;
									$detectedInfo .= $model . '/' . $class . '=' . $output[$model][$class] . ' ';
								}

								if ($verbose) $htmlCode .=  $model . '/' . $class . '=' . $output[$model][$class] . ' ';
							}
						}

					}
					else 
					{
						if (floatval($output[$model]) > floatval($options['sightengineThreshold']))
						{
						$detected = true;
						$detectedInfo .= $model . '=' . $output[$model] . ' ';
						}

						if ($verbose) $htmlCode .=  $model . ' = ' . $output[$model] . ' ';

					}
				}
			}else $htmlCode .= '<br>Error: No models to detect: ' . $options['sightengineDetect'];

			if ($detected)
			{
				 $htmlCode .= '<br>Detected: ' . $detectedInfo;

				//moderate

				 update_post_meta($postID, 'vwSuspended', time());
				 update_post_meta($postID, 'vwSuspendedInfo', 'SightEngine API: '. $detectedInfo );

				if ($options['sightengine'] == 'account')
				{
					$authorID = get_post_field( 'post_author', $postID );
					update_user_meta( $authorID, 'vwSuspended', time() );
					update_user_meta( $authorID, 'vwSuspendedInfo', 'SightEngine API: '. $detectedInfo );
				}

			}

		  } else $htmlCode .= '<br>Error: ' . $output['error']['message'];

		  return $htmlCode;
 
	}

	static function streamSnapshot( $stream, $standalone = false, $postID = '' ) {

		// updates snapshot and thumbs for room $postID from $stream

		// $standalone = independent streams/scheduler - not used
		// handles rtmp/rtsp snapshots depending on source type

		$stream = sanitize_file_name( $stream );

		if ( strstr( $stream, '.php' ) ) {
			return;
		}
		if ( ! $stream ) {
			return;
		}

		$options = self::getOptions();

		if ( ! $options['enable_exec'] ) {
			return;
		}

		global $wpdb;

		if ( ! $postID ) {
			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type = %s LIMIT 0,1", $stream, $options['custom_post'] ) );
		}

		if ( ! $postID ) {
			return; // did not identify
		}

		if ( !isset($room) ) {
			$room = sanitize_file_name( $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE ID = %d", $postID ) ) );
		}

		// rest time
		$rtmpSnapshotRest = $options['rtmpSnapshotRest'];
		if ( $rtmpSnapshotRest < 10 ) {
			$rtmpSnapshotRest = 30;
		}

		$snapshotDate = intval( get_post_meta( $postID, 'snapshotDate', true ) );
		if ( time() - $snapshotDate < $rtmpSnapshotRest ) {
			return; // fresh
		}

		$dir = $options['uploadsPath'];
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}
		$dir .= '/_snapshots';
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}

		if ( ! file_exists( $dir ) ) {
			$error = error_get_last();
			// echo 'Error - Folder does not exist and could not be created: ' . $dir . ' - '.  $error['message'];

		}

		$filename = "$dir/$room.jpg";
		if ( file_exists( $filename ) ) {
			if ( time() - filemtime( $filename ) < $rtmpSnapshotRest ) {
				if ( $snapshotDate ) {
								return; // do not update if file is fresh (15s), unless no snapshot
				}
			}
		}

				$log_file = $filename . '.txt';

		// get primary stream source (rtmp/rtsp)
		$streamProtocol = get_post_meta( $postID, 'stream-protocol', true );
		$streamType         = get_post_meta( $postID, 'stream-type', true );
		$streamAddress      = get_post_meta( $postID, 'stream-address', true );

		$roomRTMPserver = self::rtmpServer( $postID, $options );

		if ( $streamType == 'restream' && $streamAddress ) {
			// retrieve from main source
			$cmdP = '';
			if ( $streamProtocol == 'rtsp' ) {
				$cmdP = '-rtsp_transport tcp'; // use tcp for rtsp
			}
			$cmd = 'timeout -s KILL 10 ' . $options['ffmpegPath'] . " -y -frames 1 \"$filename\" $cmdP -i \"" . $streamAddress . "\" >&$log_file  ";
		} elseif ( $streamProtocol == 'rtsp' ) {
			$userID      = 0;
			$streamQuery = self::webrtcStreamQuery( $userID, $postID, 0, $stream, $options, 1, $room );

			// usually webrtc, UDP times out
			$cmd = 'timeout -s KILL 10 ' . $options['ffmpegPath'] . " -f image2 -vframes 1 \"$filename\" -y -i \"" . $options['rtsp_server'] . '/' . $streamQuery . "\" >&$log_file & ";
		} else {
			if ($options['rtmpServer'] == 'videowhisper')
			{
				//wp get user id by slug = $stream
				$user = get_user_by('slug', $stream);
				if ($user) $userID = $user->ID; else $userID = 0;
				$rtmpAddressView = trim($options['videowhisperHLS']) .'/' . trim($options['vwsAccount']). '/'.  $stream. '/index.m3u8?pin=' . self::getPin($userID, 'playback', $options) . '&token=' . $options['vwsToken'];  
			}
			elseif ( $options['externalKeysTranscoder'] ) {
				$keyView         = md5( 'vw' . $options['webKey'] . $postID );
				$rtmpAddressView = $roomRTMPserver . '?' . urlencode( 'ffmpegSnap_' . $stream ) . '&' . urlencode( $stream ) . '&' . $keyView . '&0&videowhisper';
			} else {
				$rtmpAddressView = $roomRTMPserver;
			}

			$cmd = 'timeout -s KILL 5 ' . $options['ffmpegPath'] . " -i \"" . $rtmpAddressView . '/' . $stream . "\" -frames:v 1 -y -f image2 -update 1 \"$filename\" >&$log_file & ";
		}

		// start and log snapshot process
		self::startProcess( $cmd, $log_file, $postID, $stream, 'snapshot', $options );

		// failed
		if ( ! file_exists( $filename ) ) {
			return;
		}

		// if snapshot successful update time
		update_post_meta( $postID, 'edate', time() ); // always update (snapshot retrieved = stream is live)
		update_post_meta( $postID, 'snapshotDate', time() );
		update_post_meta( $postID, 'vw_lastSnapshot', $filename );
		update_post_meta( $postID, 'snapshot', $filename );
		self::moderateSnapshot($filename, $postID, $options);

		// generate thumb
		$thumbWidth  = $options['thumbWidth'];
		$thumbHeight = $options['thumbHeight'];

		$src                  = imagecreatefromjpeg( $filename );
		list($width, $height) = getimagesize( $filename );
		$tmp                  = imagecreatetruecolor( $thumbWidth, $thumbHeight );

		$dir = $options['uploadsPath'] . '/_thumbs';
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}

		$thumbFilename = "$dir/$room.jpg";
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

	}


	static function webcamThumbSrc( $postID, $name, $options, $age = '', $thumb = true ) {
		// returns webcam thumbnail url: from snapshot or picture as configured

		$snapshot = '';

		$showImage = get_post_meta( $postID, 'showImage', true );
		if (!$showImage) $showImage = 'auto';


		$isLive = 0;
		if ( $age == __( 'LIVE', 'ppv-live-webcams' ) ) $isLive = 1;


		if ( $showImage == 'avatar' ) {
			$performerID = get_post_meta( $postID, 'performerUserID', true );
			if ( $thumb ) {
				return get_avatar_url( $performerID, array( 'size' => max( $options['thumbWidth'], $options['thumbHeight'] ) ) );
			} else {
				return get_avatar_url( $performerID, array( 'size' => 512 ) );
			}
		}



		if ( ! $thumb ) {

			if ( $showImage == 'picture' || ( $showImage == 'auto' && !$isLive ) ) {
				$picture = get_post_meta( $postID, 'picture', true );
				if ( file_exists( $picture ) ) {
					return self::path2url( $picture );
				}
			}

			// else snapshot
			if ( $showImage == 'snapshot' || $isLive )
			{
				$picture = get_post_meta( $postID, 'snapshot', true );
				if ( ! $picture ) {
					$picture = $options['uploadsPath'] . '/_snapshots/' . $name . '.jpg';
				}
				if ( file_exists( $picture ) ) {
					return self::path2url( $picture );
				}
			}

			// else show thumb
		}

		if ( $showImage == 'picture' || ( $showImage == 'auto' && !$isLive ) ) {
			$thumbFilename = get_post_meta( $postID, 'thumbPicture', true );

			if ( ! $thumbFilename ) {
				$attach_id = get_post_thumbnail_id( $postID );
				if ( $attach_id ) {
					$thumbFilename = get_attached_file( $attach_id );
				}
			}
		}

		// no thumb? get live snapshot thumb
		if ( !isset($thumbFilename) || !$thumbFilename || !file_exists($thumbFilename) || $showImage == 'snapshot' || $isLive ) {

			// $debug .= 'PostThumbNotFoundId:' . $attach_id .'-'. $thumbFilename;
			$thumbFilename = get_post_meta( $postID, 'thumbSnapshot', true );

			if ( ! $thumbFilename ) {
				$dir           = $options['uploadsPath'] . '/_thumbs';
				$thumbFilename = "$dir/" . $name . '.jpg';
			}
		}

		$noCache = '';
		if ( $age == __( 'LIVE', 'ppv-live-webcams' ) && $showImage != 'picture' ) {
			$noCache = '?' . ( floor( time() / 10 ) % 100 );
		}

		if ( file_exists( $thumbFilename ) ) {
			$snapshot = self::path2url( $thumbFilename ) . $noCache;
		} else {
			$snapshot = dirname( plugin_dir_url( __FILE__ ) . 'no-picture.png' );
			// $debug .= 'ThumbNotFoundPath:' . $thumbFilename;
		}

		return $snapshot;
	}

static function webcamThumbCode( $postID, $name, $options, $snapshot, $optionsVSV, $isMobile, $showBig, $previewMuted, $age ='' ) {
			if ( $showBig ) {
				$ci = '2';
			} else {
				$ci = '';
			}

		$showImage = get_post_meta( $postID, 'showImage', true );
		if (!$showImage) $showImage = 'auto';


			$isLive = 0;
 	 	   if ( $age == __( 'LIVE', 'ppv-live-webcams' ) ) $isLive = 1;

			$previewCode = '<IMG src="' . $snapshot . '" class="videowhisperPreview' . $ci . '">';

			if ( ! $options['videosharevod'] ) {
				return $previewCode;
			}


			if ( $showImage == 'teaser' || ( $showImage == 'auto' && !$isLive ) ) //when live it shows the snapshot, not teaser
			{

			$video_teaser = get_post_meta( $postID, 'video_teaser', true );
			if ( ! $video_teaser ) return $previewCode;


			$previewVideo  = '';

			if (!$isMobile) {
				$videoAdaptive = get_post_meta($video_teaser, 'video-adaptive', true);

				if (is_array($videoAdaptive)) {
					if (array_key_exists('preview', $videoAdaptive)) {
						if ($videoAdaptive['preview']) {
							if ($videoAdaptive['preview']['file']) {
								if (file_exists($videoAdaptive['preview']['file'])) {
									$previewVideo = $videoAdaptive['preview']['file'];
								}
							}
						}
					}
				}
			}

			//match video thumb as video poster
			$thumbURL = $snapshot;
			$imagePath         = get_post_meta( $video_teaser, 'video-thumbnail', true );
			if ($imagePath) $thumbURL    = self::path2url( $imagePath );

			if ($isMobile) return '<IMG src="' . $thumbURL . '" class="videowhisperPreview' . $ci . '">'; //mobile: show thumb only (no hover preview)

			if ($previewVideo)	$previewCode = '<video class="videowhisperPreview' . $ci . '" ' . $previewMuted . ' poster="' . $thumbURL . '" preload="auto"><source src="' . self::path2url( $previewVideo ) . '" type="video/mp4">' . $previewCode . '</video>';
			}


						return $previewCode;
		}




	// !  FFMPEG Transcoding
	static function transcodeStreamWebRTC( $stream, $postID, $options = null, $detect = 2 ) {

		// not used

		// transcode for WebRTC usage: RTMP/RTSP as necessary
		if ( ! $stream ) {
			return;
		}
		if ( ! $options ) {
			$options = get_option( 'VWliveWebcamsOptions' );
		}

		if ( ! $options['enable_exec'] ) {
			return $stream;
		}

		if ( ! $options['webrtc'] ) {
			return $stream;
		}
		// check every 59s
		$tooSoon = 0;
		if ( ! self::timeTo( $stream . '/transcodeCheckWebRTC', 29, $options ) ) {
			$tooSoon = 1;
		}

		global $wpdb;

		if ( ! $postID ) {
			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type = %s LIMIT 0,1", sanitize_file_name( $stream ), $options['custom_post'] ) );
		}

		if ( ! $postID ) {
			return '';
		}

		if ( ! $room ) {
			$room = sanitize_file_name( $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE ID = %d", $postID ) ) );
		}

		$stream_webrtc = get_post_meta( $postID, 'stream-webrtc', true );
		if ( ! self::timeTo( $stream . '/transcodeCheckWebRTC-Flood', 3, $options ) ) {
			return $stream_webrtc; // prevent duplicate checks
		}

		// room metas
		$transcodeEnabled = get_post_meta( $postID, 'vw_transcode', true );
		$videoCodec       = get_post_meta( $postID, 'stream-codec-video', true );
		$privateShow      = get_post_meta( $postID, 'privateShow', true );
		$performer        = get_post_meta( $postID, 'performer', true );
		$performerUserID  = get_post_meta( $postID, 'performerUserID', true );

		if ( $performer ) {
			$stream = $performer; // always transcode room performer stream as source
		}

		$streamProtocol = get_post_meta( $postID, 'stream-protocol', true );
		$streamType     = get_post_meta( $postID, 'stream-type', true ); // restream/webrtc/external
		$streamMode     = get_post_meta( $postID, 'stream-mode', true ); // direct/safari_pc

		$roomRTMPserver = self::rtmpServer( $postID, $options );

		if ( ! $streamProtocol ) {
			$streamProtocol = 'rtmp'; // assuming plain wowza stream
		}

		// safari_pc
		if ( $streamProtocol == 'rtsp' && $streamMode == 'safari_pc' ) {

			if ( ! $options['transcodeRTC'] ) {
				return $stream;
			}
			// RTSP to RTSP (correct profile transcoding)

			// RTMP to RTSP (h264/opus)
			$stream_webrtc = $stream . '_webrtc';

			if ( $tooSoon ) {
				return $stream_webrtc;
			}

			$streamQuery = self::webrtcStreamQuery( $performerUserID, $postID, 1, $stream_webrtc, $options, 1, $room );

			// detect transcoding process - cancel if already started
			$cmd = "ps aux | grep '/$streamQuery '";
			if ( $options['enable_exec'] ) {
				exec( escapeshellcmd( $cmd ), $output, $returnvalue );
			}

			$transcoding = 0;
			foreach ( $output as $line ) {
				if ( strstr( $line, 'ffmpeg' ) ) {
					$transcoding = 1;
					break;
				}
			}

			// paths for logs
			$uploadsPath = $options['uploadsPath'];
			if ( ! file_exists( $uploadsPath ) ) {
				mkdir( $uploadsPath );
			}
			$upath = $uploadsPath . "/$stream/";
			if ( ! file_exists( $upath ) ) {
				mkdir( $upath );
			}

			if ( ! $transcoding ) {

				// detect
				if ( $detect == 2 || ( $detect == 1 && ! $videoCodec ) ) {

					// detect webrtc stream info
					$log_file = $upath . 'streaminfo-webrtc.log';

					$cmd = 'timeout -s KILL 3 ' . $options['ffmpegPath'] . ' -y -i "' . $options['rtsp_server'] . '/' . $stream . '" 2>&1 ';
					if ( $options['enable_exec'] ) {
						exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					}
					$info = implode( "\n", $output );

					// video
					if ( ! preg_match( '/Stream #(?:[0-9\.]+)(?:.*)\: Video: (?P<videocodec>.*)/', $info, $matches ) ) {
						preg_match( '/Could not find codec parameters \(Video: (?P<videocodec>.*)/', $info, $matches );
					}
					list($videoCodec) = explode( ' ', $matches[1] );
					if ( $videoCodec && $postID ) {
						update_post_meta( $postID, 'stream-codec-video', strtolower( $videoCodec ) );
					}

					// audio
					$matches = array();
					if ( ! preg_match( '/Stream #(?:[0-9\.]+)(?:.*)\: Audio: (?P<audiocodec>.*)/', $info, $matches ) ) {
						preg_match( '/Could not find codec parameters \(Audio: (?P<audiocodec>.*)/', $info, $matches );
					}

					list($audioCodec) = explode( ' ', $matches[1] );
					$audioCodec       = trim( $audioCodec, " ,.\t\n\r\0\x0B" );
					if ( $audioCodec && $postID ) {
						update_post_meta( $postID, 'stream-codec-audio', strtolower( $audioCodec ) );
					}
					if ( ( $videoCodec || $audioCodec ) && $postID ) {
						update_post_meta( $postID, 'stream-codec-detect', time() );
					}

					file_put_contents( $log_file, "$stream|$stream_hls|$stream_webrtc|$transcodeEnabled|$detect|$videoCodec|$audioCodec", FILE_APPEND );
					file_put_contents( $log_file, $info, FILE_APPEND );

				}

				// start transcoding process
				$log_file = $upath . 'transcode_webrtc-webrtc.log';

				if ( $videoCodec && $audioCodec ) {
					$cmd = $options['ffmpegPath'] . ' ' . $options['ffmpegTranscodeRTC'] .
						' -threads 1 -f rtsp "' . $options['rtsp_server_publish'] . '/' . $streamQuery .
						'" -i "' . $options['rtsp_server'] . '/' . $stream . "\" >&$log_file & ";

					// echo $cmd;
					file_put_contents( $log_file, "\n\nCMD: " . $cmd, FILE_APPEND );
					if ( $options['enable_exec'] ) {
						exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					}

					update_post_meta( $postID, 'stream-webrtc', $stream_webrtc );
				} else {
					file_put_contents( $log_file, 'RTSP stream incomplete: ' . escapeshellarg( $options['rtsp_server'] . '/' . $stream ) . ' Will check again later... ', FILE_APPEND );
				}
			} else {
				update_post_meta( $postID, 'transcoding-webrtc', time() ); // last time process detected
			}
		}

			// otherwise return existing stream (and update)
		if ( ! $stream_webrtc ) {
			$stream_webrtc = $stream;
		}
		update_post_meta( $postID, 'stream-webrtc', $stream_webrtc );
		return $stream_webrtc;
	}


	static function responsiveStream( $default, $postID, $player = 'flash' ) {
		if ( ! $postID ) {
			return $default;
		}

		$streamProtocol = get_post_meta( $postID, 'stream-protocol', true );

		if ( $player == 'flash' ) {
			if ( $streamProtocol == 'rtsp' ) {
				$transcode = 0;

				$videoCodec = get_post_meta( $postID, 'stream-codec-video', true );
				$audioCodec = get_post_meta( $postID, 'stream-codec-audio', true );

				if ( ! in_array( $videoCodec, array( 'h264' ) ) ) {
					$transcode = 1;
				}
				if ( ! in_array( $audioCodec, array( 'aac', 'speex' ) ) ) {
					$transcode = 1;
				}

				if ( ! $transcode ) {
					return $default;
				}

				$stream_hls = get_post_meta( $postID, 'stream-hls', true );
				if ( $stream_hls ) {
					return $stream_hls;
				}
			}
		}

		return $default;
	}



	function transcodeStream( $stream, $required = 0, $room = '', $detect = 2, $convert = 1, $options = null, $postID = 0 ) {

		// $detect: 0 = no, 1 = auto, 2 = always (update)
		// $convert: 0 = no, 1 = auto , 2 = always

		// VWliveWebcams

		if ( ! $stream ) {
			return;
		}
		if ( ! $room ) {
			$room = sanitize_file_name( $stream );
		}

		if ( ! $options ) {
			$options = get_option( 'VWliveWebcamsOptions' );
		}

		if ( ! $options['enable_exec'] ) {
			return;
		}

		if ( ! $options['transcoding'] ) {
			return $stream; // functionality is disabled
		}

		// is it a post channel?
		if ( ! $postID ) {
			global $wpdb;
			$postID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type = %s LIMIT 0,1", sanitize_file_name( $room ), $options['custom_post'] ) );
		}

		// echo "transcodeStream($stream, $required, $detect, $convert) $transcoding $postID ".$options['transcoding'];

		// is feature enabled?
		if ( $postID ) {
			$transcodeEnabled = get_post_meta( $postID, 'vw_transcode', true );
			$videoCodec       = get_post_meta( $postID, 'stream-codec-video', true );
			$privateShow      = get_post_meta( $postID, 'privateShow', true );
			$performer        = get_post_meta( $postID, 'performer', true );

			if ( $performer ) {
				$stream = $performer; // always transcode room performer stream as source
			}

			$sourceProtocol = get_post_meta( $postID, 'stream-protocol', true );
			$sourceType     = get_post_meta( $postID, 'stream-type', true ); // stream-type: flash/external/webrtc/restream/playlist
			$stream_hls     = get_post_meta( $postID, 'stream-hls', true );
			$reStream       = get_post_meta( $postID, 'vw_ipCamera', true );

		} else {
			return $stream;
		}

		// currently only for rtmp
		if ( $sourceProtocol != 'rtmp' ) {
			return $stream;
		}

		if ( in_array( $sourceProtocol, array( 'http', 'https' ) ) ) {
			$stream_hls = $stream; // as is for http streams
		}

		if ( ! $options['transcodingAuto'] && $convert != 2 ) {
			return $stream_hls; // disabled
		}

		// direct delivery for restream/external/playlist : do not transcode
		if ( ( $reStream && ! $options['transcodeReStreams'] ) || ( $sourceType == 'external' && ! $options['transcodeExternal'] ) || $sourceType == 'playlist' ) {
			update_post_meta( $postID, 'stream-hls', $stream );

			return $stream;
		}

		// do not check more often than 60s if not required
		if ( ! $required ) {
			if ( ! self::timeTo( 'transcoder-' . $stream, 60, $options ) ) {
				return '';
			}
		}

			// detect transcoding process
			$cmd = "ps aux | grep '/i_$stream -i rtmp'";
		if ( $options['enable_exec'] ) {
			exec( escapeshellcmd( $cmd ), $output, $returnvalue );
		}
		// var_dump($output);

		$transcoding = 0;
		foreach ( $output as $line ) {
			if ( strstr( $line, 'ffmpeg' ) ) {
				$transcoding = 1;
				break; // break foreach loop
			}
		}

		// stop transcoding if not permitted during show
		if ( $transcoding ) {
			if ( $privateShow ) {
				if ( ! $options['transcodeShows'] ) {
							// close transcoding
							$cmd = "ps aux | grep '/i_$stream -i rtmp'";
					if ( $options['enable_exec'] ) {
						exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					}
							// var_dump($output);

							$transcoderClosed = 0;
					foreach ( $output as $line ) {
						if ( strstr( $line, 'ffmpeg' ) ) {
									$columns = preg_split( '/\s+/', $line );
									$cmd     = 'kill -9 ' . $columns[1];
							if ( $options['enable_exec'] ) {
								exec( escapeshellcmd( $cmd ), $output, $returnvalue );
							}
									$transcoderClosed++;
						}
					}

							return '';
				}
			}
		}

			// no further action required if already transcoding
		if ( $transcoding ) {
			return 'i_' . $stream; // already transcoding - use that
		}

			// rtmp keys: required for connecting to stream
		if ( $options['externalKeysTranscoder'] ) {
			$current_user = wp_get_current_user();

			$key = md5( 'vw' . $options['webKey'] . $current_user->ID . $postID );

			$keyView = md5( 'vw' . $options['webKey'] . $postID );

			// ?session&room&key&broadcaster&broadcasterid
			$rtmpAddress      = $options['rtmp_server_hls'] . '?' . urlencode( 'i_' . $stream ) . '&' . urlencode( $stream ) . '&' . $key . '&1&' . $current_user->ID . '&videowhisper';
			$rtmpAddressView  = self::rtmpServer( $postID, $options ) . '?' . urlencode( 'ffmpegView_' . $stream ) . '&' . urlencode( $stream ) . '&' . $keyView . '&0&videowhisper';
			$rtmpAddressViewI = self::rtmpServer( $postID, $options ) . '?' . urlencode( 'ffmpegInfo_' . $stream ) . '&' . urlencode( $stream ) . '&' . $keyView . '&0&videowhisper';

			// self::webSessionSave("/i_". $stream, 1);
		} else {
			$rtmpAddress      = $options['rtmp_server_hls'];
			$rtmpAddressViewI = $rtmpAddressView = self::rtmpServer( $postID, $options );
		}

			// paths
			$uploadsPath = $options['uploadsPath'];
		if ( ! file_exists( $uploadsPath ) ) {
			mkdir( $uploadsPath );
		}

			$upath = $uploadsPath . "/$room/";
		if ( ! file_exists( $upath ) ) {
			mkdir( $upath );
		}

			// detect codecs - do transcoding only if necessary
		if ( $detect == 2 || ( $detect == 1 && ! $videoCodec ) ) {

			$log_file = $upath . $stream . '_streaminfo.log';

			// $swfurl = plugin_dir_url(__FILE__) . 'videowhisper/live_video.swf';

			// $cmd = $options['ffmpegPath'] .' -y -rtmp_pageurl "http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] . '" -rtmp_swfurl "' . $swfurl .'" -rtmp_swfverify "' . $swfurl .'" -i "' . $rtmpAddressViewI .'/'. $stream . '" 2>&1 ';
			$cmd = $options['ffmpegPath'] . ' -y -i ' . escapeshellarg( $rtmpAddressViewI . '/' . $stream ) . '2>&1 ';

			if ( $options['enable_exec'] ) {
				exec( escapeshellcmd( $cmd ), $output, $returnvalue );
			}
			$info = implode( "\n", $output );

			// video
			if ( ! preg_match( '/Stream #(?:[0-9\.]+)(?:.*)\: Video: (?P<videocodec>.*)/', $info, $matches ) ) {
				preg_match( '/Could not find codec parameters \(Video: (?P<videocodec>.*)/', $info, $matches );
			}
			list($videoCodec) = explode( ' ', $matches[1] );
			if ( $videoCodec && $postID ) {
				update_post_meta( $postID, 'stream-codec-video', strtolower( $videoCodec ) );
			}

			// audio
			$matches = array();
			if ( ! preg_match( '/Stream #(?:[0-9\.]+)(?:.*)\: Audio: (?P<audiocodec>.*)/', $info, $matches ) ) {
				preg_match( '/Could not find codec parameters \(Audio: (?P<audiocodec>.*)/', $info, $matches );
			}

			list($audioCodec) = explode( ' ', $matches[1] );
			if ( $audioCodec && $postID ) {
				update_post_meta( $postID, 'stream-codec-audio', strtolower( $audioCodec ) );
			}

			if ( ( $videoCodec || $audioCodec ) && $postID ) {
				update_post_meta( $postID, 'stream-codec-detect', time() );
			}

			file_put_contents( $log_file, ' CMD: ' . $cmd, FILE_APPEND );
			file_put_contents( $log_file, $info, FILE_APPEND );

		}

			// do any conversions after detection
		if ( $convert ) {
			if ( ! $videoCodec && $postID ) {
				$videoCodec = get_post_meta( $postID, 'stream-codec-video', true );
			}
			if ( ! $audioCodec && $postID ) {
				$audioCodec = get_post_meta( $postID, 'stream-codec-audio', true );
			}

			// valid mp4 for html5 playback?
			if ( ( $sourceExt == 'mp4' ) && ( $videoCodec == 'h264' ) && ( $audioCodec = 'aac' ) ) {
				$isMP4 = 1;
			} else {
				$isMP4 = 0;
			}

			if ( $isMP4 && $convert == 1 ) {
				return $stream; // present format is fine - no conversion required
			}

			if ( ! $transcodeEnabled ) {
				return ''; // transcoding disabled
			}

			// start transcoding process
			$log_file = $upath . $stream . '_transcode.log';

			// $swfurl = plugin_dir_url(__FILE__) . 'videowhisper/live_video.swf';

			// $cmd = $options['ffmpegPath'] .' ' .  $options['ffmpegTranscode'] . " -threads 1 -rtmp_pageurl \"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] . '" -rtmp_swfurl "' . $swfurl .'" -rtmp_swfverify "' . $swfurl ."\" -f flv \"" . $rtmpAddress . "/i_". $room . "\" -i \"" . $rtmpAddressView ."/". $stream . "\" >&$log_file & ";

			// input/output depends based on performer name, not room name
			$cmd = $options['ffmpegPath'] . ' ' . $options['ffmpegTranscode'] . ' -threads 1 -f flv ' . escapeshellarg( $rtmpAddress . '/i_' . $stream ) . ' -i ' . escapeshellarg( $rtmpAddressView . '/' . $stream ) . " >&$log_file 2>&1 & ";

			// start and log transcoding process
			self::startProcess( $cmd, $log_file, $postID, $stream, 'transcode', $options );

			return 'i_' . $room;
		}

	}


	static function startProcess( $cmd = '', $log_file = '', $postID = '', $stream = '', $type = '', $options = '' ) {

		// start and log a process
		// $cmd must end in &

		if ( ! $options ) {
			$options = get_option( 'VWliveWebcamsOptions' );
		}

		if ( ! $options['enable_exec'] ) {
			return;
		}

		// release timeout slots before starting new process
		// self::processTimeout();

		if ( $options['enable_exec'] ) {
			$processId = exec( $cmd  . ' echo $!;', $output, $returnvalue );
		}
		file_put_contents( $log_file, "\n\nCMD: " . $cmd . ' Return:' . $returnvalue . ' processID:' . $processId . ' postID:' . $postID . ' stream:' . $stream . "\n", FILE_APPEND );

		$uploadsPath = $options['uploadsPath'];

		$processPath = $uploadsPath . '/_process/';
		if ( ! file_exists( $processPath ) ) {
			mkdir( $processPath );
		}

		if ( $processId ) {

			$info = array(
				'postID' => $postID,
				'stream' => $stream,
				'type'   => $type,
				'time'   => time(),
			);

			self::varSave( $processPath . $processId, $info );

			$lastLog = $options['uploadsPath'] . '/lastLog-' . $type . '.txt';
			self::varSave(
				$lastLog,
				array(
					'type'   => $type,
					'postID' => $postID,
					'file'   => $log_file,
					'cmd'    => $cmd,
					'return' => $returnvalue,
					'output' => $output,
					'time'   => time(),
				)
			);

		}
	}


	static function processTimeout( $search = 'ffmpeg', $force = false, $verbose = false ) {

		// clear processes for listings that are not online
		$options = get_option( 'VWliveWebcamsOptions' );

		if ( ! $options['enable_exec'] ) {
			return;
		}

		if ( ! $force && ! self::timeTo( 'processTimeout', 300, $options ) ) {
			return;
		}

		if ( $verbose ) {
			echo '<BR>Checking timeout processes (associated with offline listings) ...';
		}

		$processTimeout = $options['processTimeout'];
		if ( $processTimeout < 10 ) {
			$processTimeout = 90;
		}

		$uploadsPath = $options['uploadsPath'];
		if ( ! file_exists( $uploadsPath ) ) {
			mkdir( $uploadsPath );
		}

		$processPath = $uploadsPath . '/_process/';
		if ( ! file_exists( $processPath ) ) {
			mkdir( $processPath );
		}

		$processUser = get_current_user();

		$cmd = "ps aux | grep '$search'";
		if ( $options['enable_exec'] ) {
			exec( escapeshellcmd( $cmd ), $output, $returnvalue );
		}
		// var_dump($output);

		$transcoders = 0;
		$kills       = 0;

		foreach ( $output as $line ) {
			if ( strstr( $line, $search ) ) {
				$columns = preg_split( '/\s+/', $line );
				if ( $processUser == $columns[0] && ( ! in_array( $columns[10], array( 'sh', 'grep' ) ) ) ) {
					$transcoders++;

					$killThis = false;

					$info = self::varLoad( $processPath . $columns[1] );

					if ( $info === false ) {
						// not found: kill it
						// $killThis = true;

						if ( $verbose ) {
							echo '<br>Warning: No info found for process #' . esc_html( $columns[1] );
						}
					} else {
						if ( $info['postID'] ) {
							$edate = (int) get_post_meta( $info['postID'], 'edate', true );
							if ( time() - $edate > $processTimeout ) {
								$killThis = true; // kill if not online last $processTimeout s
							}
						}
					}

					if ( $killThis ) {
						$cmd = 'kill -9 ' . $columns[1];
						if ( $options['enable_exec'] ) {
							exec( escapeshellcmd( $cmd ), $output, $returnvalue );
						}

						$kills++;
						if ( $verbose ) {
							echo '<br>processTimeout (item offline) Killed #' . esc_html( $columns[1] );
						}
					}
				}
			}
		}

		if ( $verbose ) {
			echo '<br>' . esc_html( $transcoders ) . ' processes found, ' . esc_html( $kills ) . ' cleared';
		}

	}


}
