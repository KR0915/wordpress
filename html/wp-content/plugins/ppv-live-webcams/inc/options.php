<?php
namespace VideoWhisper\LiveWebcams;

if ( ! defined( 'ABSPATH' ) )
{
	exit; // Exit if accessed directly
}

trait Options {
	// define and edit settings


	static function isModerator($userID, $options = null, $user = null, $roles = null)
	{
		if ( !$userID ) return false;
		
		if ( !$options ) $options = self::getOptions();
		
		if ( !$user) $user = get_userdata( $userID );
		
		if ( !$roles )
		{
			$roles = explode( ',', $options['roleModerators'] );
			if ( !is_array($roles) ) $roles = array();
			foreach ( $roles as $key => $value )$roles[ $key ] = trim( $value );
		}

		if ($user && isset($user->roles)) if ( self::any_in_array( $roles, $user->roles ) ) return true;

		return false;
	}

	static function rolesUser( $csvRoles, $user)
	{
		// user has any of the listed roles
		// if (self::rolesUser( $option['rolesDonate'], wp_get_current_user() )

		if (!$csvRoles) return true; //all allowed if not defined

		$roles = explode(',', $csvRoles);
		if ( !is_array($roles) ) $roles = array();
		foreach ($roles as $key => $value) $roles[$key] = trim($value);
	
		if (!$user || !isset($user->roles) ) 
		if ( self::any_in_array( $roles, ['Guest','Visitor'] ) ) return true;
		else return false; //not logged in

		if ($user && isset($user->roles)) if ( self::any_in_array( $roles, $user->roles ) ) return true;

		return false;
	}


	static function getRolesPerformer( $options = null )
	{
		if ( !$options ) $options = self::getOptions();

		$roles = explode( ',', $options['rolePerformers'] );
		if ( !is_array($roles) ) $roles = array();
		
		if (count($roles)) foreach ( $roles as $key => $value )
		{
			$roles[ $key ] = trim( $value );
		}

		$roles[] = 'administrator';
		$roles[] = 'super-admin';
		$roles[] = trim( $options['rolePerformer'] );

		return $roles;
	}


	static function getOptions()
	{
		$options = get_option( 'VWliveWebcamsOptions' );

		if ( ! $options )
		{
			$options = self::adminOptionsDefault();
		}

		return $options;
	}


	static function safe_json_decode( $json, $assoc = false )
	{
		$data = json_decode( $json, $assoc );

		if ( json_last_error() !== JSON_ERROR_NONE )
		{
			//log error
			error_log( 'VideoWhisper\LiveWebcams safe_json_decode Error: ' . json_last_error_msg() );

			return array();
		}

		return $data;
	}

	// ! Options

	static function adminOptionsDefault()
	{
		$upload_dir = wp_upload_dir();
		$root_url    = plugins_url();
		$root_ajax   = admin_url( 'admin-ajax.php?action=vmls&task=' );

		return array(

			'maxLoginAttempts' => 5,
			'loginLockoutTime' => 3600, //1h

			'filtersSave' => 0,

			//locations
			'languageFilters' => 1,
			'locationFilters' => 0,

			'locationCountriesURL' => 'https://gist.githubusercontent.com/fogonwater/bc2b98baeb2aa16b5e6fbc1cf3d7d545/raw/6fd2951260d8f171181a45d2f09ee8b2c7767330/countries.json',
			'locationRegionsURL' => 'https://raw.githubusercontent.com/country-regions/country-region-data/refs/heads/master/data.json',
			'locationLanguagesURL' => 'https://gist.githubusercontent.com/josantonius/b455e315bc7f790d14b136d61d9ae469/raw/1a3babcb42fbfc364e648aeba6968ae20c381bf8/language-codes.json', //icon idea: https://github.com/AnandChowdhary/language-icons

			'chatlog' => 900, //chatlog time in seconds
			'chatlogPrevious' => 0, //show previous chatlog
			'chatlogCleanup' => 60, //cleanup chatlog

			'rtmpServer' => 'wowza', //videowhisper/wowza
			'videowhisperRTMP' =>'',
			'videowhisperHLS' =>'',
			'broadcastPin' => '',
			'playbackPin' => '',
			'videowhisperStream' => '0', //stream validation

			'categoriesContest' => '',
			'categoriesMessage' => 'Join categories to participate in contests and get more exposure.',
			'voteCost' => 1.0,
			'voteRatio' => 0.5,

			'webrtcOnly' => 0, //only webrtc (web features)

			'modeVersion' => '',
			'suspendTimeout' => 86400, //24h
			'suspendMessage' => 'This room was suspended for violating terms of service. Please contact support for details.',
			'sightengine' => 0, //enable sightengine for stream snapshot moderation
			'sightengineUser' => '',
			'sightengineSecret' => '',
			'sightengineModels' => 'nudity-2.0,wad,offensive,gore',
			'sightengineDetect' => 'nudity,weapons,alcohol,drugs,offensive,gore',
			'sightengineClasses' => 'sexual_activity,sexual_display,prob',
			'sightengineThreshold'	=> '0.50',
			'sightengineInterval' => '600',

			'registrationColumns' => 1,
			'multiPerson' => 0, //enable multi person accounts
			'personTypes' => 'Male,Female', //types of persons that can be added, csv

			'activateMessage' => 'An email was sent to provided email address. Please open the activation link from email to activate your account!',

			'pendingMessage'                => 'Thank you for submitting account account records for approval. This is a custom message that can include HTML code: <a href="https://videowhisper.com/tickets_submit.php">Contact VideoWhisper</a>.',


			'bannedNames' => '',

			'payoutMinimum' => 300,
			'payoutMaximum' => 5000,
			'payoutPerPage' => 10,
			'payoutExchange' => 1.00,
			'payoutCurrency' => 'USD',
			'payoutRoles' => 'administrator',
			'payoutBalanceMeta' => 'auto',
			'payoutMethodField' => 'Payout Method',
			'payoutMethods' => json_decode('{"Paypal":{"csv":"Paypal Email, #amount, #currency"},"Skrill":{"csv":"Skrill Email, #currency, #amount, #reference"}}'),
			'reportCSV' => 'Full Name,#email, #method, #amount, #currency, #reference',
			'payoutMethodsConfig' => '
			[Paypal]
			csv=Paypal Email, #amount, #currency
			
			[Skrill]
			csv=Skrill Email, #currency, #amount, #reference',

			'profileFieldIcon' => 'dropdown icon',
		
			'listingsCache' => 19,
			'maxUpload' => 6000,

			'privateSnapshots' => 1,
			'rtpSnapshots' => 0, //no longer needed as uploaded by h5v app
			'saveSnapshots' => 1,

			'appLogo'                        => dirname( plugin_dir_url( __FILE__ ) ) . '/images/logo.png',

			'private2Way' => 1,

			'multilanguage' => 1,
			'deepLkey' => '',
			'translations' => 'all',
			'languageDefault' => 'en-us',		
			
			'geoIP' => '2',
			'registrationAanonymous' => 'client',
			'registrationNoActivation' => '',
			
			'roleRestricted' => 'performer',
			'roleModerators' => 'editor, moderator',

			'match' => 1,
			'matchAdvanced' =>0,
			
			'timeIntervalVisitor' => 15000,
			'privateNotification' => 0,
			'privateSubject' => 'New Private Request From',
			'privateText' => 'You received a private request. Access your room quickly when user is online.',
			'email_cooldown' => 300, //5 min
			
			'sms_number' => 1,
			'sms_cooldown' => 600, //10 min
			'wp_sms_twilio' =>0,
			'wp_sms' => 0,
			'sms_instructions' => 'Country code and mobile number.',
			

			'statsBonusOnline' => '36000',
			'statsDuration' => '30',
			'statsBonusRate' => '0.9',
			
			'lovense' => 0,
			'lovensePlatform' => '',
			'lovenseTipParams' => 2,
			'lovenseToy' => 'auto',
			
			'listingsThumbsOnly' => 1,
			'listingsDisableLocation'     => 1,

			'rolesDonate'       => 'administrator, editor, author, contributor, subscriber, performer, creator, studio, client, fan',
			'enable_exec'                     => 0, // disabled by default for security confirmation

			'recording'                 	  => 0,
			'recordingStack'                  => 1,
			
			'micropaymentsAssets'             => 1,

			'postTypesAssets'                 => 'video, picture, download, post',
			'messagesSubject'                 => 'Your received a new message from',
			'messagesText'                    => 'Read message on site:',

			'attachmentRecord'                => 1,

			'attachment_extensions'           => 'pdf,doc,docx,odt,rtf,tex,txt,ppt,pptx,key,pages,numbers,odp,xls,xlsx,csv,sql,zip,tar,gz,rar,psd,ttf,otf,fon,fnt,mp3,jpg,jpeg,heif,png,gif,gdoc,gsheet,gslide',
			'attachmentSize'                  => '10485760',

			'listingsMenu'                    => 'auto',

			'modeAudio'                       => 1,
			'modeText'                        => 1,
			'modeVideo'                       => 1,

			'comments'                        => 0,

			'performerGolive'                 => 'form',
			'performerWallet'                 => 1,
			'performerStatus'                 => 1,
			'performerSetup'                  => 1,
			'performerOverview'               => 1,
			'performerReports'				  => 1,

			'profileCall'					  => 1,
			'calls'                           => 1,
			'bans'                            => 1,
			'profiles'                        => 1,
			'woocommerce'                     => 1,

			'clientWallet'                    => 1,

			'reStreams'                    	  => 0,
			'restreamPause'             => 1,
			'restreamTimeout'           => 900,
			'restreamAccessedUser'      => 1,
			'restreamAccessed'          => 1,
			'restreamActiveOwner'       => 1,
			'restreamActiveUser'        => 0,
			'httprestreamer'                   => '',		

			'pushStreams'                     => 0,
			
			'pushTranscode'                   => '-c:v libx264 -c:a libfdk_aac',

			'registerIPlimit'                 => 3,

			'frontendURLs'					  => 0,
			'loginFrontend'                   => 1,
			'redirectWpLogin'                 => 0,

			'recaptchaSite'                   => '',
			'recaptchaSecret'                 => '',

			'checkins'                        => 1,
			'goals'                           => 1,
			'goalsConfig'                     => ';Define default goals that can be achieved with donations/gifts/crowdfunding. Completing a goal moves room to next one if available or repeats.

[1]
name=Break the Ice
description="Break the ice with a small gift."
amount=5 ; required to complete goal
current=3 ; starting amount on this goal (fake gifts)
cumulated=3 ; total amount on all goals (fake gifts)
reset=1 ; days to reset

[2]
name=Getting Started
description=Get things started.
amount=10
reset=0 ; does not reset

[3]
name=Heat It Up
description=Heat things up.
amount=50

[4]
name=Independent
description=Independent goals can also receive donations anytime from Goals panel.
independent=true
current=5
amount=50

[5]
name=Bonus
description=Bonus. Final goal repeats if completed.
independent=true
amount=100
',
			'goalsDefault'                    => unserialize( 'a:5:{i:1;a:6:{s:4:"name";s:13:"Break the Ice";s:11:"description";s:34:"\Break the ice with a small gift.\";s:6:"amount";s:1:"5";s:7:"current";s:1:"3";s:9:"cumulated";s:1:"3";s:5:"reset";s:1:"1";}i:2;a:4:{s:4:"name";s:15:"Getting Started";s:11:"description";s:19:"Get things started.";s:6:"amount";s:2:"10";s:5:"reset";s:1:"0";}i:3;a:3:{s:4:"name";s:10:"Heat It Up";s:11:"description";s:15:"Heat things up.";s:6:"amount";s:2:"50";}i:4;a:5:{s:4:"name";s:11:"Independent";s:11:"description";s:70:"Independent goals can also receive donations anytime from Goals panel.";s:11:"independent";s:1:"1";s:7:"current";s:1:"5";s:6:"amount";s:2:"50";}i:5;a:4:{s:4:"name";s:5:"Bonus";s:11:"description";s:39:"Bonus. Final goal repeats if completed.";s:11:"independent";s:1:"1";s:6:"amount";s:3:"100";}}' ),
			'registerFrontend'                => 1,

			'pendingSubject' => 'Account Pending Approval',
			'pendingText'                    => 'Your account is pending approval. You can update administrative records from your dashboard, after login. ',

			'activateSubject'                 => 'Activate Your Account',
			'activateText'                    => 'Your account was created. Open this link to activate account: ',

			'passwordSubject'                 => 'Password Change Request',
			'passwordText'                    => 'Password change was requested from website. If this is correct, use this link to change password: ',

			'appCSS'                          => '

/* elementor-icons-ekiticons-css  conflict */
i.icon {
font-family: Icons  !important;
}

.ui.button
{
width: auto !important;
height: auto !important;
}

.ui .item
{
 margin-top: 0px !important;
}

.ui.modal>.content
{
margin: 0px !important;
}
.ui.header .content
{
background-color: inherit !important;
}

.site-inner
{
max-width: 100%;
}

.panel
{
padding: 0px !important;
margin: 0px !important;
}
			',

			'profileLayout'                   => 'auto',
			'performerLayout'                 => 'auto',

			'buddypressConnectMessages'		  => 1,
			'buddypressConnect' 			  => 1,
			'buddypressSupport' 			  => 1,
			'buddypressMessages' 			  => 1,
			'buddypressRooms' 			      => 1,

			'buddypressDirectory' 			  => 'webcam',
			'buddypressEmbed'				  => 1, //embed room in buddypress
			'buddypressGroupPerformer'        => 1, // restrict group creation to performers
			'activityCooldown'                => 3600, // posts to activity stream

			'messages'                        => 1,
			'messagesCost'                    => '5.00',

			'whitelabel'                      => 0,

			'appOptionsReset'                 => 0,
			'appOptions'                      => 1,

			'appMobileMinimalist'             => 1,
			'appComplexity'                   => 1,
			'appSiteMenu'                     => -1,
			'templateTypes'                   => 'page, post, channel, webcam, conference, presentation, videochat, video, picture, download',

			'geoBlocking'                     => '',
			'geoBlockingMessage'              => 'This type of content is forbidden in your location.',

			'videochatNext'                   => '1',
			'videochatNextPaid'               => '1',
			'videochatNextOnline'             => '0',
			'videochatNextPool'               => 32,

			'themeMode' 		  => '',
			'interfaceClass'                  => '',

			'layoutDefault'                   => 'grid',
			'corsACLO'                        => '',

			'debugMode'                       => '0',
			'teaserOffline'                   => '1',

			'balancePage'                     => '',

			'rtmp_restrict_ip'                => '',
			'webStatus'                       => 'auto',
			'webStatusInterval'               => '60', // seconds between status calls

			'balanceWarn1Amount'              => '9',
			'balanceWarn1Message'             => 'Warning: Your balance is low!',
			'balanceWarn1Sound'               => 'warning',
			'balanceWarn2Amount'              => '4',
			'balanceWarn2Message'             => 'Warning: Your balance is critical! ',
			'balanceWarn2Sound'               => 'critical',

			'privateSnapshotsInterval'        => '60',

			'performerProfile'                => '',
			'clientProfile'                   => '',

			'webcamLink'                      => 'room',
			'webcamLinkDefault'               => get_site_url() . '/webcam/',

			'freeTimeLimitVisitor'            => '3600',
			'freeTimeLimit'                   => '43200',
			'freeTimeBalance' 				  => '10',

			'webfilter'                       => '0',

			'videosharevod'                   => '1',
			'picturegallery'                  => '1',
			'rateStarReview'                  => '1',

			'filterRegex'                     => '(?i)(arsehole|fuck|cunt|shit)(?-i)',
			'filterReplace'                   => '*',

			'custom_post'                     => 'webcam',
			'postTemplate'                    => '+plugin',

			'disableSetupPages'               => '0',

			'addRoles'        => '0',

			'clientSubscriptions' => '1',
			'clientContent' => '1',

			'registrationFormRole'            => '1',

			'roleClient'                      => 'client',

			'rolePerformer'                   => 'performer',
			'rolePerformers'                  => 'editor, author, contributor',

			'roleStudio'                      => 'studio',

			'studios'                         => '0',
			'studioPerformers'                => '20',
			'studioWebcams'                   => '50',

			'performerWebcams'                => '3',

			'userName'                        => 'user_nicename',
			'webcamName'                      => 'user_nicename',

			'thumbWidth'                      => '240',
			'thumbHeight'                     => '180',
			'perPage'                         => '5',

			'wallet'                          => 'MicroPayments',
			'wallet2'			              => '',
			
			'walletMulti'                     => '2',

			'ppvGraceTime'                    => '30',
			'ppvPPM'                          => '0.50',
			'ppvPPMaudio'                     => '0.30',
			'ppvPPMtext'                      => '0.20',

			'ppvPPMmin'                       => '0.10',
			'ppvPPMmax'                       => '5.00',

			'autoBalanceLimits'               => 1,
			'ppvRatio'                        => '0.80',
			'ppvPerformerPPM'                 => '0.00',
			'ppvMinimum'                      => '1.50',
			'ppvMinInShow'                    => '0.30',

			'ppvCloseAfter'                   => '120',
			'ppvBillAfter'                    => '10',
			'ppvKeepLogs'                     => '31536000',
			'onlineTimeout'                   => '60',

			'rtmp_server'                     => 'rtmp://[your-rtmp-server-ip-or-domain]/videowhisper-chat',
			'rtmp_server_archive'             => 'rtmp://[your-rtmp-server-ip-or-domain]/videowhisper-archive',
			'rtmp_server_record'              => 'rtmp://[your-rtmp-server-ip-or-domain]/videowhisper-record',

			'rtmp_server_admin'               => 'rtmp://[your-rtmp-server-ip-or-domain]/videowhisper-chat',

			'presentation'                    => '0',

			'rtmp_amf'                        => 'AMF3',

			'canWatch'                        => 'all',
			'watchList'                       => 'Super Admin, Administrator, Editor, Author, Contributor, Subscriber, Client, Student, Member',

			'performerOffline'                => 'warn',
			'performerOfflineMessage'         => '<H4>Performer is currently offline.</h4> Access is enabled: you can enter public room and wait for performer.',
			'viewersCount'                    => '1',
			'salesCount'                      => '1',

			'loaderGIF'                       => dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/loading.gif',

			'overLogo'                        => dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/logo.png',
			'overLink'                        => 'https://videowhisper.com',

			'loginLogo'                       => dirname( plugin_dir_url( __FILE__ ) ) . '/videowhisper/pvc-logo-w.png',

			'tokenKey'                        => 'VideoWhisper',
			'webKey'                          => 'VideoWhisper',

			'multicamMax'                     => '2',
			'transcoding'                     => '0',
			'transcodingAuto'                 => '2',
			'htmlchat'                        => '1',
			'htmlchatTest'                    => '1',
			'externalKeys'                    => '1',
			'externalKeysTranscoder'          => '1',

			'rtmp_server_hls'                 => 'rtmp://[your-rtmp-server-ip-or-domain]/videowhisper-x',
			'httpstreamer'                    => 'http://[your-rtmp-server-ip-or-domain]:1935/videowhisper-x/',
			'hls_vod'                         => '',

			'ffmpegPath'                      => '/usr/local/bin/ffmpeg',
			'ffmpegConfiguration'             => '1',
			'ffmpegTranscode'                 => '-analyzeduration 0 -vcodec copy -acodec libfaac -ac 2 -ar 22050 -ab 96k',
			'transcodeRTC'                    => '0',
			'transcodeFromRTC'                => '0',
			'ffmpegTranscodeRTC'              => '-c:v copy -c:a libopus', // transcode for RTC like ffmpeg -re -i source -acodec opus -vcodec libx264 -vprofile baseline -f rtsp rtsp://<wowza-instance>/rtsp-to-webrtc/my-stream //-c:v libx264 -profile:v baseline -c:a libopus

			'rtsp_server'                     => 'rtsp://[your-server]/videowhisper-x', // access WebRTC stream with sound from here
			'rtsp_server_publish'             => 'rtsp://[user:password@][your-server]/videowhisper-x', // publish WebRTC stream here

			'webrtc'                          => '6', // enable webrtc
			'webrtcServer'                    => 'wowza', // wowza/videowhisper/auto
			'vwsSocket'                 	  => '', // videowhisper nodejs server
			'vwsToken'                		  => '', // videowhisper nodejs server token
			'vwsAccount' => '', // videowhisper nodejs server account	

			'wsURLWebRTC'                     => 'wss://[wowza-server-with-ssl]:[port]/webrtc-session.json', // Wowza WebRTC WebSocket URL (wss with SSL certificate)
			'applicationWebRTC'               => '[application-name]', // Wowza Application Name (configured or WebRTC usage)

			'webrtcVideoCodec'                => 'VP8',
			'webrtcAudioCodec'                => 'opus',

			'webrtcVideoBitrate'              => 750,
			'webrtcAudioBitrate'              => 32,

			'rtmp_restrict_ip'                => '',
			'webStatus'                       => 'auto',

			'unoconvPath'                     => '/usr/bin/unoconv',
			'convertPath'                     => '/usr/bin/convert',

			'detect_hls'                      => 'ios',
			'detect_mpeg'                     => 'android',

			'transcodeShows'                  => '0',
			'rtmpSnapshotRest'                => '30',

			'processTimeout'                  => '90',

			'streamsPath'                     => '/home/account/public_html/streams',
			'playlists'                       => '0',

			'serverRTMFP'                     => 'rtmfp://stratus.adobe.com/f1533cc06e4de4b56399b10d-1a624022ff71/',
			'p2pGroup'                        => 'VideoWhisper',
			'supportRTMP'                     => '1',
			'supportP2P'                      => '0',
			'alwaysRTMP'                      => '1',
			'alwaysP2P'                       => '0',

			'disableBandwidthDetection'       => '1',

			'videowhisper'                    => 0,

			'uploadsPath'                     => $upload_dir['basedir'] . '/ppv-live-webcams',
			'adServer'                        => $root_ajax . 'm_ads',

			'appRoles'                        => unserialize( 'a:3:{s:27:"conferenceParticipantCamera";a:3:{s:5:"roles";s:30:"administrator,performer,client";s:5:"value";s:1:"1";s:5:"other";s:0:"";}s:8:"banUsers";a:3:{s:5:"roles";s:13:"administrator";s:5:"value";s:1:"1";s:5:"other";s:0:"";}s:11:"filesUpload";a:3:{s:5:"roles";s:30:"administrator,performer,client";s:5:"value";s:1:"1";s:5:"other";s:0:"";}}' ),
			'appRolesConfig'                  => '
; This configures features per role
[conferenceParticipantCamera] ; enable camera panel for these conference participants in addition to performer
roles = administrator,performer,client	 ; default: administrators, all performers, clients - always enabled for room owner/performer
value = true						   ; value for matching roles
other = false						   ; value for other roles

[banUsers] ; enable other participants to kick or ban
roles = administrator
value = true
other = false

[filesUpload] ; enable other participants to upload files by role
roles = administrator,performer,client
value = true
other = false
			',

			'appSetup'                        => unserialize( 'a:3:{s:6:"Config";a:23:{s:8:"darkMode";s:0:"";s:7:"pipMode";s:0:"";s:7:"minMode";s:0:"";s:7:"tabMenu";s:4:"icon";s:19:"cameraAutoBroadcast";s:1:"1";s:22:"cameraAutoBroadcastAll";s:1:"1";s:14:"cameraControls";s:1:"1";s:16:"snapshotInterval";s:3:"240";s:15:"snapshotDisable";s:0:"";s:13:"videoAutoPlay";s:0:"";s:16:"resolutionHeight";s:3:"360";s:7:"bitrate";s:3:"500";s:9:"frameRate";s:2:"15";s:12:"audioBitrate";s:2:"32";s:19:"maxResolutionHeight";s:4:"1080";s:10:"maxBitrate";s:4:"3500";s:12:"timeInterval";s:4:"5000";s:15:"recorderMaxTime";s:3:"300";s:15:"recorderDisable";s:0:"";s:11:"goals_label";s:5:"Goals";s:10:"goals_icon";s:4:"gift";s:12:"recordAction";s:1:"1";s:14:"longTextLength";s:2:"20";}s:4:"Room";a:19:{s:16:"requests_disable";s:0:"";s:12:"room_private";s:0:"";s:13:"external_rtmp";s:1:"1";s:10:"room_audio";s:0:"";s:9:"room_text";s:0:"";s:15:"room_conference";s:0:"";s:15:"conference_auto";s:1:"1";s:10:"room_slots";s:1:"4";s:19:"vw_presentationMode";s:0:"";s:10:"calls_only";s:0:"";s:14:"group_disabled";s:0:"";s:5:"party";s:0:"";s:14:"party_reserved";s:1:"0";s:13:"stream_record";s:0:"";s:21:"stream_record_private";s:0:"";s:17:"stream_record_all";s:0:"";s:11:"goals_panel";s:1:"1";s:10:"goals_sort";s:0:"";s:5:"gifts";s:1:"1";}s:4:"User";a:9:{s:12:"h5v_language";s:5:"en-us";s:8:"h5v_flag";s:2:"us";s:7:"h5v_sfx";s:0:"";s:8:"h5v_dark";s:0:"";s:7:"h5v_pip";s:0:"";s:7:"h5v_min";s:0:"";s:9:"h5v_audio";s:0:"";s:10:"h5v_reveal";s:0:"";s:17:"h5v_reveal_warmup";s:2:"30";}}' ),

			'appSetupConfig'                  => '
; This configures HTML5 Videochat application and other apps that use same API.

[Config]						; Application settings
darkMode = false 			 	; true/false : start app in dark mode
pipMode = false 			 	; true/false : picture in picture with camera over video
minMode = false 			 	; true/false : minimalist mode with less buttons and interface elements
tabMenu = icon 				    ; icon/text/full : menu type for tabs (in advanced/collaboration mode), use icon to fit tabs for setups with many features
cameraAutoBroadcast = true		; true/false : start broadcast automatically for performer
cameraAutoBroadcastAll = true	; true/false : start broadcast automatically for all, use in combination with conference_auto in chat mode or room settings
cameraControls = true 			; true/false : broadcast control panel
snapshotInterval = 240			; camera snapshot interval in seconds, to upload camera snapshot, min 10s (lower defaults to 180)
snapshotDisable = false			; disable uploading camera snapshots by videochat applicaiton
videoAutoPlay = false 			; true/false : try to play video without broadcaster notification
resolutionHeight = 360			; streaming resolution, maximum 360 in free mode
bitrate = 500					; streaming bitrate in kbps, maximum 750kbps in free mode
frameRate = 15					; streaming frame rate in fps, maximum 15fps in free mode
audioBitrate = 32				; streaming audio bitrate in kbps, maximum 32kbps in free mode
maxResolutionHeight = 1080 		; maximum selectable resolution height, maximum 480p in free mode
maxBitrate = 3500				; maximum selectable streaming bitrate in kbps, maximum 750kbps in free mode, also limited by hosting
timeInterval = 5000				; chat and interaction update in milliseconds, if no action taken by user, min 2000ms
recorderMaxTime = 300			; maximum recording time in seconds, limited in free mode
recorderDisable= false			; disable inserting recordings in text chat
goals_label = Goals 			; Goals panel label
goals_icon = gift 				; https://semantic-ui.com/elements/icon.html
recordAction = true				; shows record button in actions bar for performer to quickly toggle recording
longTextLength = 20				; number of characters: show a big textarea dialog for long text

[Room]						; Defaults for room options, editable by room owner in Options tab
requests_disable = false	; true/false : Disable users from sending private call requests to room owner.
room_private = false		; true/false : Hide room from public listings. Can be accessed by room link.
external_rtmp = true		; Enabled Broadcast tab with settings to configure external RTMP encoder, for perfomers
room_audio = false      	; true/false : Audio only mode. Only microphone, no webcam video.
room_text = false      		; true/false : Text only mode. No microphone, no webcam.
room_conference = false		; true/false : Enable owner to show multiple users streams at same time in split view. All users can publish webcam.
conference_auto = true		; true/false : Automatically assign users to video conference slots when they publish webcam.
room_slots = 4				; 2/4/6 : Split display to show multiple media items, in conference mode.
vw_presentationMode = false ; true/false : Enable presentation and collaboration mode with multiple media, files, presentation.
calls_only = false			; true/false : Only accept locked calls, with call link
group_disabled = false		; true/false : Only accept private calls (instant or setup), no group chat
party = false				; true/false : This room is a party squad, traveling in group to other rooms
party_reserved = 0			; 0-6 : Number of reserved party cam slots, needs to be available in other room
stream_record = false			; Record performer stream. Requires FFmpeg with involved codecs.
stream_record_private = false	; Record in private calls. Requires FFmpeg with involved codecs.
stream_record_all = false		; Record streams from all users. Requires FFmpeg with involved codecs.
goals_panel = true			; Panel with all Goals. Users can donate to Independent goals anytime.
goals_sort = false			; Display goals in contribution order.
gifts = true				; Enable Gifts button in Actions bar. Applies to current room goal if enabled. Disable to hide current room goal from text chat.

[User]						; Defaults for user preferences, editable by user in Options tab
h5v_language = en-us		; default language (for DeepL API translation)
h5v_flag = us				; flag associated to language
h5v_sfx = false     	 	; true/false : User sound effects preference
h5v_dark = false     	 	; true/false : User dark mode preference
h5v_pip = false     	 	; true/false : User picture in picture preference
h5v_min = false     	 	; true/false : User minimalist mode preference
h5v_audio = false   	   	; true/false : User audio only mode (no webcam)
h5v_reveal = false      	; true/false : Reveal mode with microphone only until webcam is started with specific Reveal button, requires audio only disabled
h5v_reveal_warmup = 30      ; Number of seconds required before webcam can be revealed
				',

			'profileFields' => self::safe_json_decode('{"Languages":{"type":"language","filter":"enabled","icon":"flag icon"},"Location":{"type":"location","filter":"enabled","icon":"map marked icon"},"Quick Match":{"match":"mirror","type":"select","options":"Man looking for Woman|Woman looking for Man","default":"Man looking for Woman","hideLabel":true,"icon":"random icon"},"About Me":{"type":"textarea","instructions":"A few details about yourself.","icon":"file alternate icon"},"Private Chat":{"type":"textarea","instructions":"Describe what can you perform in private.","icon":"user secret icon"},"Schedule":{"type":"textarea","instructions":"Describe when you plan to go online.","icon":"calendar icon"},"Interests":{"type":"textarea","icon":"thumbs up icon"},"Wishlist":{"type":"textarea","icon":"gift icon"},"Gender":{"type":"select","options":"Male|Female|Trans|Tester","filter":"enabled","match":"enabled","icon":"user icon"},"Sports":{"type":"multiselect","options":"Gym|Running|Jogging|Hiking|Snowboarding|Swimming|Pilates|Yoga|Cycling|Bike|Crossfit|Football|Tennis","filter":"enabled","icon":"running icon"},"Hair":{"type":"checkboxes","options":"Long|Short|Blonde|Brunette|Readhead|Black hair","icon":"user circle icon"},"Age":{"type":"select","options":"18-22|22-30|30-40|40+","filter":"enabled","match":"enabled","icon":"hourglass icon"}}', true),
			'profileFieldsConfig'             => '
; This configures listing profile fields. Can contain comments that start with character ;

[Languages]
type = language ; multiple options can be selected at same time with checkboxes
filter = enabled
icon = "flag icon"

[Location]
type = location
filter = enabled
icon = "map marked icon"

[Quick Match]
match=mirror
type=select
options="Man looking for Woman|Woman looking for Man"
default=Man looking for Woman
hideLabel=true
icon="random icon"

[About Me] 
type = textarea
instructions = A few details about yourself. 
icon="file alternate icon"

[Private Chat] ; field name is defined in brackets
type = textarea ; type defines type of field: text/textarea/select/checkboxes
instructions = Describe what can you perform in private. ; instructions show user filling the form
icon="user secret icon"

[Schedule]
type = textarea
instructions = Describe when you plan to go online.
icon = "calendar icon"

[Interests]
type = textarea
icon = "thumbs up icon"

[Wishlist]
type = textarea
icon = "gift icon"

[Gender]
type = select ; only 1 option can be selected for select
options = "Male|Female|Trans|Tester"
filter = enabled
match = enabled
icon="user icon"

[Sports]
type = multiselect
options = "Gym|Running|Jogging|Hiking|Snowboarding|Swimming|Pilates|Yoga|Cycling|Bike|Crossfit|Football|Tennis"
filter = enabled
icon = "running icon"

[Hair]
type = checkboxes
options = "Long|Short|Blonde|Brunette|Readhead|Black hair"
icon = "user circle icon"

[Age]
type = select
options = "18-22|22-35|35-45|45-55|55+"
filter = enabled
match = enabled
icon = "hourglass icon"
',
			'labels'                          => '',
			'labelsConfig'                    => '
All=All
Online=Online
Available=Online, Available
In Private=Online, In Private
Offline=Offline
',
			'groupModes'                      => unserialize( 'a:9:{s:14:"Free Broadcast";a:19:{s:11:"description";s:126:"\Free presale chat. Performer broadcasts 1 way and clients can participate in text chat, send tips or request paid call/show.\";s:3:"cpm";s:1:"0";s:4:"2way";s:1:"0";s:4:"cpm2";s:3:"0.5";s:6:"voyeur";s:1:"1";s:4:"cpmv";s:4:"0.25";s:9:"snapshots";s:1:"0";s:7:"archive";s:1:"0";s:12:"room_private";s:0:"";s:10:"calls_only";s:0:"";s:16:"requests_disable";s:0:"";s:15:"room_conference";s:0:"";s:19:"vw_presentationMode";s:0:"";s:10:"room_audio";s:0:"";s:9:"room_text";s:0:"";s:14:"group_disabled";s:0:"";s:13:"stream_record";s:0:"";s:21:"stream_record_private";s:0:"";s:17:"stream_record_all";s:0:"";}s:16:"Video Conference";a:17:{s:11:"description";s:152:"\Group conference with 6 video slots where users are automatically displayed when they start their camera. First slot is always reserved for performer.\";s:15:"room_conference";s:1:"1";s:15:"conference_auto";s:1:"1";s:10:"room_slots";s:1:"6";s:19:"vw_presentationMode";s:0:"";s:3:"cpm";s:1:"0";s:9:"snapshots";s:1:"0";s:7:"archive";s:1:"0";s:12:"room_private";s:0:"";s:10:"calls_only";s:0:"";s:16:"requests_disable";s:0:"";s:10:"room_audio";s:0:"";s:9:"room_text";s:0:"";s:14:"group_disabled";s:0:"";s:13:"stream_record";s:0:"";s:21:"stream_record_private";s:0:"";s:17:"stream_record_all";s:0:"";}s:19:"Video Collaboration";a:17:{s:11:"description";s:137:"\Moderated presentation or conference with 4 slots where users, videos, pictures are displayed by moderator. Includes files sharing tab.\";s:15:"room_conference";s:1:"1";s:15:"conference_auto";s:0:"";s:10:"room_slots";s:1:"4";s:19:"vw_presentationMode";s:1:"1";s:3:"cpm";s:1:"0";s:9:"snapshots";s:1:"0";s:7:"archive";s:1:"0";s:12:"room_private";s:0:"";s:10:"calls_only";s:0:"";s:16:"requests_disable";s:0:"";s:10:"room_audio";s:0:"";s:9:"room_text";s:0:"";s:14:"group_disabled";s:0:"";s:13:"stream_record";s:0:"";s:21:"stream_record_private";s:0:"";s:17:"stream_record_all";s:0:"";}s:15:"Group Paid Show";a:20:{s:11:"description";s:52:"\All clients pay. Private 2 way calls are disabled.\";s:3:"cpm";s:4:"1.50";s:4:"2way";s:1:"2";s:4:"cpm2";s:3:"2.0";s:6:"voyeur";s:1:"1";s:4:"cpmv";s:4:"1.75";s:9:"snapshots";s:1:"0";s:7:"archive";s:1:"0";s:13:"archiveImport";s:1:"0";s:12:"room_private";s:0:"";s:10:"calls_only";s:0:"";s:16:"requests_disable";s:1:"1";s:15:"room_conference";s:0:"";s:19:"vw_presentationMode";s:0:"";s:10:"room_audio";s:0:"";s:9:"room_text";s:0:"";s:14:"group_disabled";s:0:"";s:13:"stream_record";s:1:"1";s:21:"stream_record_private";s:0:"";s:17:"stream_record_all";s:0:"";}s:10:"Audio Only";a:13:{s:11:"description";s:84:"\Webcam is disabled for group and private 2 way calls. Only microphone can be used.\";s:10:"room_audio";s:1:"1";s:9:"room_text";s:0:"";s:3:"cpm";s:1:"0";s:12:"room_private";s:0:"";s:10:"calls_only";s:0:"";s:16:"requests_disable";s:0:"";s:15:"room_conference";s:0:"";s:19:"vw_presentationMode";s:0:"";s:14:"group_disabled";s:0:"";s:13:"stream_record";s:0:"";s:21:"stream_record_private";s:0:"";s:17:"stream_record_all";s:0:"";}s:9:"Text Only";a:8:{s:11:"description";s:88:"\Text chat only with webcam and microphone disabled for group and private 2 way calls. \";s:9:"room_text";s:1:"1";s:3:"cpm";s:1:"0";s:12:"room_private";s:0:"";s:10:"calls_only";s:0:"";s:16:"requests_disable";s:0:"";s:15:"room_conference";s:0:"";s:19:"vw_presentationMode";s:0:"";}s:5:"Calls";a:11:{s:11:"description";s:98:"\Only take private 2 way calls (instant requests or preset locked calls). Group chat is disabled.\";s:12:"room_private";s:0:"";s:14:"group_disabled";s:1:"1";s:10:"calls_only";s:0:"";s:16:"requests_disable";s:0:"";s:3:"cpm";s:1:"0";s:10:"room_audio";s:0:"";s:9:"room_text";s:0:"";s:13:"stream_record";s:0:"";s:21:"stream_record_private";s:0:"";s:17:"stream_record_all";s:0:"";}s:12:"Locked Calls";a:11:{s:11:"description";s:129:"\Only take locked private calls, previously setup from dashboard. Group chat and instant calls are disabled, room is not listed.\";s:12:"room_private";s:1:"1";s:10:"calls_only";s:1:"1";s:14:"group_disabled";s:1:"1";s:16:"requests_disable";s:0:"";s:3:"cpm";s:1:"0";s:10:"room_audio";s:0:"";s:9:"room_text";s:0:"";s:13:"stream_record";s:0:"";s:21:"stream_record_private";s:0:"";s:17:"stream_record_all";s:0:"";}s:12:"Random Match";a:3:{s:11:"description";s:80:"\Random 2 way call matchmaking. Define match criteria from performer dashboard.\";s:11:"room_random";s:1:"1";s:12:"room_private";s:1:"1";}}' ),
			'groupModesConfig'                => '
[Free Broadcast]
description="Free presale chat. Performer broadcasts 1 way and clients can participate in text chat, send tips or request paid call/show."
cpm=0 ; cost per minute 0 for free
2way=0 ; for legacy Flash app
cpm2=0.5 ; for legacy Flash app
voyeur=1 ; allow user to join invisible
cpmv=0.25 ; cost per minute for voyeur
snapshots=0 ; do not save snapshots in pictures
archive=0 ; do not archive stream
room_private=false ; show in list
calls_only=false ; allow group chat and instant calls
requests_disable=false ; enable call requests
room_conference=false ; no conference slots
vw_presentationMode=false ; no collaboration tools
room_audio=false ; both video and audio enabled
room_text=false ; text chat only mode disabled
group_disabled=false ; allow group chat
stream_record = false			; Record performer stream.
stream_record_private = false	; Record in private calls.
stream_record_all = false		; Record streams from all users
room_random = 0 ; random 2 way match mode

[Video Conference]
description="Group conference with 6 video slots where users are automatically displayed when they start their camera. First slot is always reserved for performer."
room_conference=true ; enable multiple video slots
conference_auto=true ; enable adding users automatically to slots when they start broadcasting, can be used in combination with cameraAutoBroadcastAll in HTML5 Videochat settings
room_slots=6 ; slots for conference
vw_presentationMode=false ; no collaboration tools
cpm=0 ; free
snapshots=0 ; not saving performer snapshots
archive=0 ; not recording
room_private=false ; show room in list
calls_only=false
requests_disable=false
room_audio=false
room_text=false
group_disabled=false
stream_record = false			; Record performer stream.
stream_record_private = false	; Record in private calls.
stream_record_all = false		; Record streams from all users
room_random = 0 ; random 2 way match mode

[Video Collaboration]
description="Moderated presentation or conference with 4 slots where users, videos, pictures are displayed by moderator. Includes files sharing tab."
room_conference=true
conference_auto=false
room_slots=4
vw_presentationMode=true
cpm=0
snapshots=0
archive=0
room_private=false
calls_only=false
requests_disable=false
room_audio=false
room_text=false
group_disabled=false
stream_record = false			; Record performer stream.
stream_record_private = false	; Record in private calls.
stream_record_all = false		; Record streams from all users
room_random = 0 ; random 2 way match mode

[Group Paid Show]
description="All clients pay. Private 2 way calls are disabled."
cpm=1.50 ; default cost per minute editable from performer dashboard if enabled
2way=2 ; for older Flash app
cpm2=2.0
voyeur=1
cpmv=1.75
snapshots=0
archive=0
archiveImport=0
room_private=false
calls_only=false
requests_disable=true
room_conference=false
vw_presentationMode=false
room_audio=false
room_text=false
group_disabled=false
stream_record = true			; Record performer stream.
stream_record_private = false	; Record in private calls.
stream_record_all = false		; Record streams from all users
room_random = 0 ; random 2 way match mode

[Audio Only]
description="Webcam is disabled for group and private 2 way calls. Only microphone can be used."
room_audio=true ; only microphone enabled with no camera
room_text=false
cpm=0 ; free
room_private=false ; show in list
calls_only=false ; enable group chat and instant call requests
requests_disable=false ; enable call requests
room_conference=false
vw_presentationMode=false
group_disabled=false ; group chat
stream_record = false			; Record performer stream.
stream_record_private = false	; Record in private calls.
stream_record_all = false		; Record streams from all users
room_random = 0 ; random 2 way match mode

[Text Only]
description="Text chat only with webcam and microphone disabled for group and private 2 way calls. "
room_text=true ; text chat only
cpm=0 ; free
room_private=false ; show in list
calls_only=false ; enable group chat and instant call requests
requests_disable=false ; enable call requests
room_conference=false
vw_presentationMode=false
room_audio=false
stream_record = false			
stream_record_private = false
stream_record_all = false
room_random = 0 ; random 2 way match mode

[Calls]
description="Only take private 2 way calls (instant requests or preset locked calls). Group chat is disabled."
room_private=false ; show in room list available for calls
group_disabled=true ; no group chat, show connection lobby
calls_only=false ; allow both instant calls and locked calls
requests_disable=false ; allow call requests
cpm=0 ; no group cost per minute
room_audio=false
room_text=false
stream_record = false			; Record performer stream.
stream_record_private = false	; Record in private calls.
stream_record_all = false		; Record streams from all users
room_random = 0 ; random 2 way match mode

[Locked Calls]
description="Only take locked private calls, previously setup from dashboard. Group chat and instant calls are disabled, room is not listed."
room_private=true ; hide from room list
calls_only=true ; allow only locked calls
group_disabled=true ; no group chat, show connection lobby
requests_disable=false ; enable call requests
cpm=0 ; no group cost per minute
room_audio=false
room_text=false
stream_record = false			; Record performer stream.
stream_record_private = false	; Record in private calls.
stream_record_all = false		; Record streams from all users
room_random = 0 ; random 2 way match mode

[Random Match]
description="Random 2 way call matchmaking. Define match criteria from performer dashboard."
room_random = 1
room_private = 1
room_audio=false
room_text=false
cpm=0 
',
			'groupGraceTime'                  => '30',
			'voyeurAvailable'                 => 'never',

			'currency'                        => 'tk$',
			'currencypm'                      => 'tk$/m',
			'currencyLong'                    => 'tokens',

			'recordFields'                    => unserialize( 'a:11:{s:9:"Full Name";a:2:{s:4:"type";s:4:"text";s:12:"instructions";s:17:"\Real full name.\";}s:13:"Date of Birth";a:1:{s:4:"type";s:4:"text";}s:24:"Proof of Age ID Document";a:2:{s:4:"type";s:4:"file";s:12:"instructions";s:174:"\Upload a document scan or photo proving age. Can be identity document, driver license showing name and birth date. Supported formats: pdf, jpeg, jpg, png. Maximum size 3mb.\";}s:18:"ID Type and Number";a:2:{s:4:"type";s:4:"text";s:12:"instructions";s:58:"\The type and number for document providing proof of age.\";}s:24:"Proof of Identity Selfie";a:2:{s:4:"type";s:4:"file";s:12:"instructions";s:183:"\Upload a selfie holding the identity document uploaded previously. Your face and front of document must be clearly visible. Supported formats: pdf, jpeg, jpg, png. Maximum size 3mb.\";}s:7:"Address";a:2:{s:4:"type";s:8:"textarea";s:12:"instructions";s:31:"\Current address of residence.\";}s:25:"Proof Of Address Document";a:2:{s:4:"type";s:4:"file";s:12:"instructions";s:172:"\Upload a document scan or photo proving address. Can be utility bill or bank statement showing name and address. Supported formats: pdf, jpeg, jpg, png. Maximum size 3mb.\";}s:12:"Phone Number";a:2:{s:4:"type";s:4:"text";s:12:"instructions";s:140:"\Phone number where you can be reached if further details, verifications are required. Can be used to verify identity for account recovery.\";}s:25:"Tax Identification Number";a:2:{s:4:"type";s:4:"text";s:12:"instructions";s:112:"\Your Tax Identification Number (TIN), in some countries same as the Personal Identification Code from your ID.\";}s:13:"Payout Method";a:3:{s:4:"type";s:6:"select";s:7:"options";s:37:"Paypal/Bitcoin/Ethereum/Litecoin/Hold";s:12:"instructions";s:33:"\How would you like to get paid.\";}s:14:"Payout Details";a:2:{s:4:"type";s:8:"textarea";s:12:"instructions";s:65:"\Details where payment should be sent (email or wallet address).\";}}' ),
			'recordFieldsConfig'              => '
			[Full Name]
			type = text
			instructions = "Real full name."
			personal = true
			required = true

			[Date of Birth]
			type = text
			personal = true
			required = true
			
			[Proof of Age ID Document]
			type = file
			instructions = "Upload a document scan or photo proving age. Can be identity document, driver license showing name and birth date. Supported formats: pdf, jpeg, jpg, png. "
			personal = true
			required = true
			
			[ID Type and Number]
			type = text
			instructions = "The type and number for document providing proof of age."
			personal = true
			required = true
			
			[Proof of Identity Selfie]
			type = file
			instructions = "Upload a selfie holding the identity document uploaded previously. Your face and front of document must be clearly visible. Supported formats: pdf, jpeg, jpg, png."
			personal = true
			
			[Address]
			type = textarea
			instructions = "Current address of residence."
			personal = true
			
			[Proof Of Address Document]
			type = file
			instructions = "Upload a document scan or photo proving address. Can be utility bill or bank statement showing name and address. Supported formats: pdf, jpeg, jpg, png."
			personal = true
			
			[Phone Number]
			type = text
			instructions = "Phone number where you can be reached if further details, verifications are required. Can be used to verify identity for account recovery."
			
			[Tax Identification Number]
			type = text
			instructions = "Your Tax Identification Number (TIN), in some countries same as the Personal Identification Code from your ID."
			personal = true
			
			[Payout Method]
			type = select
			options = Paypal/Skrill/Bitcoin/Ethereum/Litecoin/Hold
			instructions = "How would you like to get paid."
			
			[Paypal Email]
			type = text
			instructions = "Your Paypal email, if you have an account."
			
			[Skrill Email]
			type = text
			instructions = "Your Skrill email, if you have an account."
			
			[Payout Details]
			type = textarea
			instructions = "Details where payment should be sent (wallet address)."',
			'recordClean'                     => 0, // clean old files on update
			'unverifiedPerformer'             => 1,
			'unverifiedStudio'                => 1,

			'tips'                            => 1,
			'tipRatio'                        => '0.90',
			'tipCooldown'                     => '15',
			'tipOptions'                      => '<tips>
<tip amount="1" label="1$ Tip" note="Like!" sound="coins1.mp3" image="gift1.png" color="#33FF33"/>
<tip amount="2" label="2$ Tip" note="Big Like!" sound="coins2.mp3" image="gift2.png" color="#33FF33"/>
<tip amount="5" label="5$ Gift" note="Great!" sound="coins2.mp3" image="gift3.png" color="#33FF33"/>
<tip amount="10" label="10$ Gift" note="Excellent!" sound="register.mp3" image="gift4.png" color="#33FF33"/>
<tip amount="20" label="20$ Gift" note="Ultimate!" sound="register.mp3" image="gift5.png"  color="#33FF33"/>
<tip amount="custom" label="Custom Tip!" note="Custom Tip" sound="coins1.mp3" image="gift1.png" color="#33FF33"/>
</tips>',

			'dashboardMessage'                => 'This is the performer dashboard area. This HTML content section can be edited from plugin settings and can include announcements, instructions, links to help. Can include HTML code: <a href="https://videowhisper.com/tickets_submit.php">Contact VideoWhisper</a>.',

			'dashboardMessageBottom'          => 'This is the performer dashboard area, bottom section. This HTML content section can be edited from plugin settings and can include announcements, instructions, links to help.',

			'dashboardMessageStudio'          => 'This is the studio dashboard area. Studio accounts manage multiple performer accounts. This HTML content section can be edited from plugin settings and can include announcements, instructions, links to help.',

			'welcomePerformer'                => 'Welcome to your performer room!',
			'welcomeClient'                   => 'Welcome!',

			'parametersPerformer'             => '&usersEnabled=1&chatEnabled=1&videoEnabled=1&webcamSupported=1&webcamEnabled=1&toolbarEnabled=1&removeChatOnPrivate=0&requestPrivate=0&assignedPrivate=0&directPrivate=0&canHide=1&canDenyAll=1&hideOnPrivate=1&soundNotifications=1&camWidth=640&camHeight=480&camFPS=30&camBandwidth=75000&videoCodec=H264&codecProfile=main&codecLevel=3.1&soundCodec=Nellymoser&micRate=22&soundQuality=9&bufferLive=0.2&bufferFull=0.2&bufferLivePlayback=0.2&bufferFullPlayback=0.2&showCamSettings=1&advancedCamSettings=1&camMaxBandwidth=250000&disableBandwidthDetection=0&disableUploadDetection=0&limitByBandwidth=1&configureSource=0&fillWindow=0&disableVideo=0&disableSound=0&floodProtection=2&writeText=1&statusInterval=30000&statusPrivateInterval=10000&externalInterval=9500&verboseLevel=2&loaderProgress=0&selectCam=1&selectMic=1&asYouType=1&disableEmoticons=0&presentation=0&restorePaused=1',

			'parametersPerformerPresentation' => '&usersEnabled=1&chatEnabled=1&videoEnabled=1&webcamSupported=1&webcamEnabled=1&toolbarEnabled=1&removeChatOnPrivate=0&requestPrivate=0&assignedPrivate=0&directPrivate=0&canHide=1&canDenyAll=1&hideOnPrivate=1&soundNotifications=1&camWidth=640&camHeight=480&camFPS=30&camBandwidth=75000&videoCodec=H264&codecProfile=main&codecLevel=3.1&soundCodec=Nellymoser&micRate=22&soundQuality=9&bufferLive=0.2&bufferFull=0.2&bufferLivePlayback=0.2&bufferFullPlayback=0.2&showCamSettings=1&advancedCamSettings=1&camMaxBandwidth=250000&disableBandwidthDetection=0&disableUploadDetection=0&limitByBandwidth=1&configureSource=0&fillWindow=0&disableVideo=0&disableSound=0&floodProtection=2&writeText=1&statusInterval=30000&statusPrivateInterval=10000&externalInterval=9500&verboseLevel=2&loaderProgress=0&selectCam=1&selectMic=1&asYouType=1&disableEmoticons=0&presentation=1&videoControl=1&videoRecorder=0&webcamSlides=0&slideComments=1&files_enabled=1&regularWatch=1&slideShow=1&publicVideosAdd=1&file_delete=1&file_upload=1&internalOpen=0&change_background=1&externalStream=1&writeAnnotations=1&editAnnotations=1&restorePaused=0',

			'parametersClient'                => '&usersEnabled=1&chatEnabled=1&videoEnabled=1&webcamSupported=1&webcamEnabled=0&toolbarEnabled=1&webcamOnPrivate=1&removeChatOnPrivate=1&removeVideoOnPrivate=1&removeUsersOnPrivate=1&maximizePrivate=1&assignedPrivate=1&requestPrivate=0&directPrivate=0&canHide=0&canDenyAll=0&hideOnPrivate=0&soundNotifications=0&camWidth=480&camHeight=360&camFPS=25&camBandwidth=50000&videoCodec=H264&codecProfile=main&codecLevel=3.1&soundCodec=Nellymoser&micRate=22&soundQuality=9&bufferLive=0.2&bufferFull=0.2&bufferLivePlayback=0.2&bufferFullPlayback=0.2&showCamSettings=1&advancedCamSettings=1&camMaxBandwidth=81920&disableBandwidthDetection=0&disableUploadDetection=0&limitByBandwidth=1&configureSource=0&fillWindow=0&disableVideo=0&disableSound=0&floodProtection=3&writeText=1&statusInterval=60000&statusPrivateInterval=10000&externalInterval=11500&verboseLevel=2&loaderProgress=0&asYouType=1&disableEmoticons=0&presentation=0&room_limit=1000&showMenu=1',

			'parametersClientPresentation'    => '&usersEnabled=1&chatEnabled=1&videoEnabled=1&webcamSupported=1&webcamEnabled=0&toolbarEnabled=1&webcamOnPrivate=1&removeChatOnPrivate=1&removeVideoOnPrivate=1&removeUsersOnPrivate=1&maximizePrivate=1&assignedPrivate=1&requestPrivate=0&directPrivate=0&canHide=0&canDenyAll=0&hideOnPrivate=0&soundNotifications=0&camWidth=480&camHeight=360&camFPS=25&camBandwidth=50000&videoCodec=H264&codecProfile=main&codecLevel=3.1&soundCodec=Nellymoser&micRate=22&soundQuality=9&bufferLive=0.2&bufferFull=0.2&bufferLivePlayback=0.2&bufferFullPlayback=0.2&showCamSettings=1&advancedCamSettings=1&camMaxBandwidth=81920&disableBandwidthDetection=0&disableUploadDetection=0&limitByBandwidth=1&configureSource=0&fillWindow=0&disableVideo=0&disableSound=0&floodProtection=3&writeText=1&statusInterval=60000&statusPrivateInterval=10000&externalInterval=11500&verboseLevel=2&loaderProgress=0&asYouType=1&disableEmoticons=0&presentation=1&room_limit=1000&videoControl=0&videoRecorder=0&webcamSlides=0&slideComments=1&files_enabled=1&regularWatch=0&slideShow=0&publicVideosAdd=0&file_delete=0&file_upload=0&internalOpen=0&change_background=0&externalStream=0&writeAnnotations=0&editAnnotations=0&showMenu=1',

			'parametersVideo'                 => '&bufferLive=0.2&bufferFull=0.2&showCredit=0&disconnectOnTimeout=1&offlineMessage=Webcam+Offline&statusInterval=30000&noSound=0',

			'layoutCodePerformer'             => 'id=0&label=Users&x=747&y=2&width=205&height=218&resize=true&move=true; id=1&label=Chat&x=747&y=225&width=451&height=432&resize=true&move=true; id=2&label=RichMedia&x=2&y=2&width=741&height=457&resize=true&move=true; id=3&label=Webcam&x=956&y=2&width=240&height=219&resize=true&move=true',

			'layoutCodePerformerPresentation' => 'id=0&label=Users&x=590&y=5&width=410&height=200&system=absolute&px=35&py=0.5&pwidth=24.5&pheight=28.5&resize=true&move=true&title=;
id=1&label=Chat&x=590&y=210&width=630&height=270&system=absolute&px=35&py=30&pwidth=37.5&pheight=38.5&resize=true&move=true&title=Chat;
id=2&label=RichMedia&x=5&y=5&width=580&height=474&system=absolute&px=0.5&py=0.5&pwidth=34.5&pheight=67.5&resize=true&move=true&title=;
id=3&label=Webcam&x=1005&y=5&width=215&height=200&system=absolute&px=60&py=0.5&pwidth=13&pheight=28.5&resize=true&move=true&title=;
id=4&label=Files&x=910&y=485&width=310&height=175&system=absolute&px=54&py=69.5&pwidth=18.5&pheight=25&resize=true&move=true&title=Files;
id=5&label=Form&x=100&y=100&width=410&height=150&system=absolute&px=6&py=14.5&pwidth=24.5&pheight=21.5&resize=true&move=true&title=External;
id=6&label=Video&x=470&y=460&width=240&height=219&system=absolute&px=28&py=65.5&pwidth=14.5&pheight=31.5&resize=true&move=true&title=;
id=7&label=Slides&x=590&y=485&width=315&height=175&system=absolute&px=35&py=69.5&pwidth=19&pheight=25&resize=true&move=true&title=SlideShow;
id=8&label=Comments&x=5&y=485&width=580&height=175&system=absolute&px=0.5&py=69.5&pwidth=34.5&pheight=25&resize=true&move=true&title=Annotations;',

			'layoutCodeClient'                => 'id=0&label=Users&x=820&y=5&width=375&height=200&resize=true&move=true; id=1&label=Chat&x=820&y=210&width=375&height=440&resize=true&move=true; id=2&label=RichMedia&x=5&y=5&width=810&height=646&resize=true&move=true; id=3&label=Webcam&x=840&y=20&width=240&height=219&resize=true&move=true',

			'layoutCodeClientPresentation'    => 'id=0&label=Users&x=1060&y=10&width=375&height=340&system=absolute&px=63&py=1.5&pwidth=22.5&pheight=48.5&resize=true&move=true&title=;
id=1&label=Chat&x=680&y=10&width=375&height=430&system=absolute&px=40.5&py=1.5&pwidth=22.5&pheight=61.5&resize=true&move=true&title=;
id=2&label=RichMedia&x=10&y=10&width=665&height=538&system=absolute&px=0.5&py=1.5&pwidth=39.5&pheight=77&resize=true&move=true&title=;
id=3&label=Webcam&x=840&y=20&width=240&height=219&system=absolute&px=50&py=3&pwidth=14.5&pheight=31.5&resize=true&move=true&title=;
id=4&label=Files&x=1060&y=360&width=380&height=285&system=absolute&px=63&py=51.5&pwidth=22.5&pheight=40.5&resize=true&move=true&title=Files;
id=5&label=Video&x=470&y=460&width=240&height=219&system=absolute&px=28&py=65.5&pwidth=14.5&pheight=31.5&resize=true&move=true&title=;
id=6&label=Comments&x=680&y=450&width=375&height=190&system=absolute&px=40.5&py=64.5&pwidth=22.5&pheight=27&resize=true&move=true&title=;',

			'layoutCodeClient2way'            => 'id=0&label=Users&x=820&y=5&width=375&height=200&resize=true&move=true; id=1&label=Chat&x=820&y=210&width=375&height=440&resize=true&move=true; id=2&label=RichMedia&x=5&y=5&width=810&height=646&resize=true&move=true; id=3&label=Webcam&x=840&y=20&width=240&height=219&resize=true&move=true',

			'layoutCodePrivatePerformer'      => 'id=0&label=Users&x=747&y=2&width=205&height=218&resize=true&move=true; id=1&label=Chat&x=747&y=225&width=451&height=432&resize=true&move=true; id=2&label=RichMedia&x=2&y=2&width=741&height=457&resize=true&move=true; id=3&label=Webcam&x=956&y=2&width=240&height=219&resize=true&move=true',

			'layoutCodePrivateClient'         => 'id=0&label=Users&x=820&y=5&width=375&height=200&resize=true&move=true; id=1&label=Chat&x=820&y=210&width=375&height=440&resize=true&move=true; id=2&label=RichMedia&x=5&y=5&width=810&height=646&resize=true&move=true; id=3&label=Webcam&x=840&y=20&width=240&height=219&resize=true&move=true',

			'layoutCodePrivateClient2way'     => 'id=0&label=Users&x=820&y=5&width=375&height=200&resize=true&move=true; id=1&label=Chat&x=820&y=210&width=375&height=440&resize=true&move=true; id=2&label=RichMedia&x=5&y=5&width=810&height=646&resize=true&move=true; id=3&label=Webcam&x=840&y=20&width=240&height=219&resize=true&move=true',

			'translationCode'                 => '<translations>
  <t text="Chat session was terminated for this user:" translation="Chat session was terminated for this user:"/>
  <t text="Chat panel was closed by:" translation="Chat panel was closed by:"/>
  <t text="Close this panel from top right corner to return." translation="Close this panel from top right corner to return."/>
  <t text="Hello, do you want to chat with me?" translation="Hello, do you want to chat with me?"/>
  <t text="Yes, Hello!" translation="Yes, Hello!"/>
  <t text="No, I am very busy." translation="No, I am very busy."/>
  <t text="Waiting" translation="Waiting"/>
  <t text="Close" translation="Close"/>
  <t text="Toggle Enter and Leave Alerts" translation="Toggle Enter and Leave Alerts"/>
  <t text="Sound Effects" translation="Sound Effects"/>
  <t text="Talk" translation="Talk"/>
  <t text="Toggle Microphone" translation="Toggle Microphone"/>
  <t text="Sound Enabled" translation="Sound Enabled"/>
  <t text="Underline" translation="Underline"/>
  <t text="Ban" translation="Ban"/>
  <t text="Toggle Webcam" translation="Toggle Webcam"/>
  <t text="Apply Settings" translation="Apply Settings"/>
  <t text="Italic" translation="Italic"/>
  <t text="Forced" translation="Forced"/>
  <t text="Select Microphone Device" translation="Select Microphone Device"/>
  <t text="Tune Streaming Bandwidth" translation="Tune Streaming Bandwidth"/>
  <t text="Assigned" translation="Assigned"/>
  <t text="Broadcasting to server" translation="Broadcasting to server"/>
  <t text="Sound Disabled" translation="Sound Disabled"/>
  <t text="HD" translation="HD"/>
  <t text="High" translation="High"/>
  <t text="Toggle Preview Compression" translation="Toggle Preview Compression"/>
  <t text="Full Screen" translation="Full Screen"/>
  <t text="Preview Shows as Captured" translation="Preview Shows as Captured"/>
  <t text="Bold" translation="Bold"/>
  <t text="Server subscribers" translation="Server subscribers"/>
  <t text="Broadcasting to P2P group" translation="Broadcasting to P2P group"/>
  <t text="Change Volume" translation="Change Volume"/>
  <t text="Room Video" translation="Room Video"/>
  <t text="iPhone 4" translation="iPhone 4"/>
  <t text="P2P group subscribers" translation="P2P group subscribers"/>
  <t text="DVD NTSC" translation="DVD NTSC"/>
  <t text="CD" translation="CD"/>
  <t text="Kick" translation="Kick"/>
  <t text="Low Cam" translation="Low Cam"/>
  <t text="Mobile 4:3" translation="Mobile 4:3"/>
  <t text="Radio" translation="Radio"/>
  <t text="Username" translation="Username"/>
  <t text="Very High" translation="Very High"/>
  <t text="Send" translation="Send"/>
  <t text="Open Here" translation="Open Here"/>
  <t text="Public" translation="Public"/>
  <t text="iPhone 1-3" translation="iPhone 1-3"/>
  <t text="SD" translation="SD"/>
  <t text="DVD PAL" translation="DVD PAL"/>
  <t text="Users Online" translation="Users Online"/>
  <t text="HDTV" translation="HDTV"/>
  <t text="Pause Broadcast" translation="Pause Broadcast"/>
  <t text="iPhone 5" translation="iPhone 5"/>
  <t text="Web 16:9" translation="Web 16:9"/>
  <t text="4K" translation="4K"/>
  <t text="Low" translation="Low"/>
  <t text="Select Webcam Device" translation="Select Webcam Device"/>
  <t text="Hide" translation="Hide"/>
  <t text="Emoticons" translation="Emoticons"/>
  <t text="Toggle External Encoder" translation="Toggle External Encoder"/>
  <t text="Main Webcam" translation="Main Webcam"/>
  <t text="Auto Deny Requests" translation="Auto Deny Requests"/>
  <t text="New Camera" translation="New Camera"/>
  <t text="Open In Browser" translation="Open In Browser"/>
  <t text="Video is Disabled" translation="Video is Disabled"/>
  <t text="Sound Fx" translation="Sound Fx"/>
  <t text="Rate" translation="Rate"/>
  <t text="FullHD" translation="FullHD"/>
  <t text="Webcam" translation="Webcam"/>
  <t text="Framerate" translation="Framerate"/>
  <t text="Sound is Disabled" translation="Sound is Disabled"/>
  <t text="Please wait. Connecting..." translation="Please wait. Connecting..."/>
  <t text="Drag to move" translation="Drag to move"/>
  <t text="HDCAM" translation="HDCAM"/>
  <t text="Drag to resize" translation="Drag to resize"/>
  <t text="Public Chat" translation="Public Chat"/>
  <t text="Available" translation="Available"/>
  <t text="Cinema" translation="Cinema"/>
  <t text="Away" translation="Away"/>
  <t text="no" translation="no"/>
  <t text="Busy" translation="Busy"/>
  <t text="Resolution" translation="Resolution"/>
  <t text="iPad" translation="iPad"/>
</translations>',
			'listingTemplate'                 => '
			<div class="videowhisperWebcam #performerStatus# layoutGrid">

			<div class="videowhisperTitle">#name#</div>
			<div class="videowhisperTime">#banLink# #age#</div>
			<div class="videowhisperIcons">#icons#</div>

			<div class="videowhisperCPM">Private: #clientCPM# #currency#/m</div>

			<a href="#url#">#preview#</a>
			<div class="videowhisperBrief">#roomBrief#</div>
			<div class="videowhisperTags">#roomTags#</div>
			<div class="videowhisperCategory">#roomCategory#</div>

			<div class="videowhisperGroupMode">Mode: #groupMode#</div>
			<div class="videowhisperGroupCPM">#groupCPM# #currency#/m</div>
			<div class="videowhisperPerformers">#performers#</div>

			<div class="videowhisperVote">#vote#</div>
			<div class="videowhisperRating">#rating#</div>
			#featured#
			#enter#
			</div>
			',
			'listingBig'                      => 0,
			'listingTemplate2'                => '
			<div class="videowhisperWebcam2 #performerStatus#">

			<div class="videowhisperTitle">#name#</div>
			<div class="videowhisperTime">#banLink# #age#</div>
			<div class="videowhisperIcons">#icons#</div>

			<div class="videowhisperCPM">Private: #clientCPM# #currency#/m</div>

			<a href="#url#">#preview#</a>
			<div class="videowhisperBrief">#roomBrief#</div>
			<div class="videowhisperTags">#roomTags#</div>
			<div class="videowhisperCategory">#roomCategory#</div>

			<div class="videowhisperGroupMode">Mode: #groupMode#</div>
			<div class="videowhisperGroupCPM">#groupCPM# #currency#/m</div>
			<div class="videowhisperPerformers">#performers#</div>

			<div class="videowhisperVote">#vote#</div>
			<div class="videowhisperRating">#rating#</div>
			#featured#
			#enter#
			</div>
			',	'listingTemplateHorizontal'             => '
			<div class="videowhisperWebcam #performerStatus# layoutHorizontal">

			<div class="videowhisperTitle">#name#</div>
			<div class="videowhisperTime">#banLink# #age#</div>
			<div class="videowhisperIcons">#icons#</div>

			<div class="videowhisperCPM">Private: #clientCPM# #currency#/m</div>

			<a href="#url#">#preview#</a>
			<div class="videowhisperBrief">#roomBrief#</div>
			<div class="videowhisperTags">#roomTags#</div>
			<div class="videowhisperCategory">#roomCategory#</div>

			<div class="videowhisperGroupMode">Mode: #groupMode#</div>
			<div class="videowhisperGroupCPM">#groupCPM# #currency#/m</div>
			<div class="videowhisperPerformers">#performers#</div>

			<div class="videowhisperVote">#vote#</div>
			<div class="videowhisperRating">#rating#</div>
			#featured#
			#enter#
			</div>
			',
			'listingTemplateList'             => '
			<div class="videowhisperWebcam #performerStatus# layoutList">

			<div class="videowhisperTitle">#name#</div>
			<div class="videowhisperTime">#banLink# #age#</div>
			<div class="videowhisperIcons">#icons#</div>

			<div class="videowhisperCPM">Private: #clientCPM# #currency#/m</div>

			<a href="#url#">#preview#</a>
			<div class="videowhisperBrief">#roomBrief#</div>
			<div class="videowhisperTags">#roomTags#</div>
			<div class="videowhisperCategory">#roomCategory#</div>

			<div class="videowhisperGroupMode">Mode: #groupMode#</div>
			<div class="videowhisperGroupCPM">#groupCPM# #currency#/m</div>
			<div class="videowhisperPerformers">#performers#</div>

			<div class="videowhisperVote">#vote#</div>
			<div class="videowhisperRating">#rating#</div>
			#featured#
			#enter#
			<div class="videowhisperDescription">#roomDescription#</div>

			<div class="videowhisperFeaturedReview">#featuredReview#</div>

			</div>
			',
			'dashboardCSS'                    => <<<HTMLCODE
			/* Make Common Themes Full Width */
			.site-inner, .page-one-column .panel-content .wrap, .entry-content, .entry-content > *:not(.alignwide):not(.alignfull):not(.alignleft):not(.alignright):not(.wp-block-separator):not(.woocommerce)
			{
			max-width: 100%;
			}
			
			/* elementor-icons-ekiticons-css  conflict */
			i.icon, i.icon::before {
			font-family: Icons  !important;
			}
HTMLCODE

			,
			'customCSS'                       => <<<HTMLCODE
/* Custom CSS for Listings */

/* Fixes */
/* elementor-icons-ekiticons-css  conflict */
i.icon {
font-family: Icons  !important;
}

.ui.button, .ui.buttons
{
width: auto !important;
height: auto !important;
}

/* Semantic UI */


.ui.input
{
height: auto !important;
}

/* Fix video preview position broken by Elementor  */
video.videowhisperPreview
{
width: 240px !important;
max-width: 240px !important;
margin: 0px !important;
}


/* Make Common Themes Full Width */
.site-inner, .page-one-column .panel-content .wrap, .entry-content, .entry-content > *:not(.alignwide):not(.alignfull):not(.alignleft):not(.alignright):not(.wp-block-separator):not(.woocommerce)
{
max-width: 100%;
}

/* Listing Elements */

.videowhisperWebcams {
position: relative;
}

.videowhisperHorizontal { 
display: flex;
  flex-wrap: nowrap;
  overflow-x: auto;
  overflow-y: hidden;
  height: 260px;
}

.layoutHorizontal {
flex: 0 0 auto;
white-space: nowrap;
}

.horizontalPrevious {
	 position: absolute;
	 top: 170px;
	 left: 5px;
	 z-index:20;
     min-width: 50px;

}

 .horizontalNext {
	 position: absolute;
	 top: 170px;
	 right: 0px;
     z-index:20;
     min-width: 50px;
}

.videowhisperWebcam
{
	position: relative;
	display:inline-block;
	float: left;

	border:1px solid #666;
	background-color:#666;
	padding: 0px;
	margin: 2px;

	width: 240px;
        height: 240px;

	overflow: hidden;
	z-index: 0;
}

 .videowhisperWebcam:hover {
	 transform: scale(1.1);
         z-index: 2;
	 box-shadow: 10px 10px 10px rgba(0,0,0,0.15);
}

.videowhisperButtonChat{  bottom: 102px;   right: 2px; }
.videowhisperButtonCall{ bottom: 128px;   right: 2px; }
.videowhisperButtonMessage{ bottom: 154px;   right: 2px; }
.layoutList .videowhisperButtonChat{  bottom: 2px;   left: 430px; right: auto; }
.layoutList .videowhisperButtonCall{ bottom: 28px;   left: 430px; right: auto; }
.layoutList .videowhisperButtonMessage{ bottom: 54px;   left: 430px; right: auto; }
.videowhisperButtonChat, .videowhisperButtonCall, .videowhisperButtonMessage
{
    position: absolute;
    z-index: 10;
    border-radius: 3px;
    padding: 2px;
    background-color: #8e3e41;
    color: white;
    font-size: 10px;
    border: none;
}


.videowhisperEnterButton {
    background-color: #8e3e41;
    border-radius: 3px;
    padding: 8px;

    color: white;
    font-size: 15px;

    border: none;
    cursor: pointer;
}
.videowhisperEnterDropdown:hover .videowhisperEnterButton {
    background-color: #AF4C50;
}


.videowhisperEnterDropdown {
   position: absolute;
    display: inline-block;
    bottom: 2px;
    right: 2px;
	z-index: 20;
}

.layoutList .videowhisperEnterDropdown
{
bottom: 2px;
left: 430px;
right: auto;
}

.videowhisperEnterDropdown-content {
    display: none;
    position: absolute;

    background-color: #f9f9f9;
    border-radius: 3px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);

    min-width: 150px;
    z-index: 15;
    bottom: 25px;
    right: 0px;
    text-align: right;

    font-size: 14px;
}

.videowhisperEnterDropdown-content a {
    color: black;
    padding: 6px 8px;
    text-decoration: none;
    display: block;
}

.videowhisperEnterDropdown-content a:hover
{
	    color: white;
		background-color: #8e3e41;
		border-radius: 3px;

}

.videowhisperEnterDropdown:hover .videowhisperEnterDropdown-content {
    display: block;
}

.videowhisperWebcam.layoutList
{
height: 180px;
width: 100%;
}

.videowhisperWebcam.offline{background-color:#866;}
.videowhisperWebcam.public{background-color:#686;}
.videowhisperWebcam.private{background-color:#668;}

.videowhisperWebcam:hover, .videowhisperWebcam2:hover {
	border:1px solid #333;
	background-color:#888;
}

.videowhisperWebcam2
{
position: relative;
display:inline-block;
float: left;

	border:1px solid #aaa;
	background-color:#666;
	padding: 0px;
	margin: 2px;

	width: 486px;
       height: 486px;

	overflow: hidden;
	z-index: 0;
}

.videowhisperSnap
{
       width: 240px;
       height: 180px;
}

.videowhisperSnap2
{
	width: 486px;
       height: 364px;
}


.videowhisperPreview
{
width: 240px;
height: 180px;
overflow: hidden;

padding: 0px;
margin: 0px;
border: 0px;
z-index: 1;
}

.videowhisperPreview2
{
width: 486px;
height: 364px;
overflow: hidden;

padding: 0px;
margin: 0px;
border: 0px;
z-index: 1;
}

.videowhisperDescription
{
position: absolute;
top:0px;
left: 490px;
right:240px;
font-size: 10px;
color: #eee;
text-shadow:1px 1px 1px #333;
z-index: 10;
height:240px;
overflow:auto;
}


.videowhisperFeaturedReview
{
position: absolute;
top:-10px;
right:2px;

width:240px;
z-index: 10;
}

.videowhisperTitle
{
position: absolute;
top:2px;
left:2px;
font-size: 14px;
color: #FFF;
text-shadow:1px 1px 1px #333;
z-index: 10;

border-radius: 3px;
padding: 3px;
background: #633;

white-space: nowrap;
overflow: hidden;
max-width:120px;
}

.layoutList .videowhisperTitle
{
left: 242px;
}

.videowhisperPerformers 
{
position: absolute;
top: 29px;
left: 2px;
font-size: 12px;
color: #fee;
text-shadow:1px 1px 1px #333;
z-index: 10;

white-space: nowrap;
overflow: hidden;
max-width:120px;

border-radius: 3px;
padding: 3px;
background: #844;
}

.videowhisperPerformers a
{
color: #eee;
text-decoration: none;
}

.layoutList .videowhisperPerformers
{
left: 242px;
right: auto;
top: 25px;
}

.videowhisperIcons
{
position: absolute;
top: 25px;
right: 2px;
z-index: 10;
color: #FFF;
text-shadow:1px 1px 1px #333;
}

.layoutList .videowhisperIcons
{
right: auto;
top: 50px;
left: 320px;
}

.videowhisperTime
{
position: absolute;
bottom: 60px;
left:2px;
font-size: 11px;
color: #FFF;
text-shadow:1px 1px 1px #333;
z-index: 10;

white-space: nowrap;
overflow: hidden;
max-width:120px;
}

.layoutList .videowhisperTime
{
top: 50px;
bottom: auto;
left:242px;
}

.videowhisperCategory
{
position: absolute;
bottom: 40px;
right: 2px;
font-size: 11px;
color: #fff;
text-shadow:1px 1px 1px #333;
z-index: 10;

white-space: nowrap;
overflow: hidden;
max-width:120px;
}
.layoutList .videowhisperCategory
{
top: 70px;
bottom:auto;
left: 242px;
}

.videowhisperTags
{
position: absolute;
bottom: 40px;
left: 2px;
font-size: 10px;
color: #fff;
text-shadow:1px 1px 1px #333;
z-index: 10;

white-space: nowrap;
overflow: hidden;
max-width:170px;
}
.layoutList .videowhisperTags
{
top: 90px;
bottom:auto;
left: 242px;
}

.videowhisperBrief
{
position: absolute;
bottom: 22px;
left: 2px;
font-size: 10px;
color: #eee;
text-shadow:1px 1px 1px #333;
z-index: 10;

white-space: nowrap;
overflow: hidden;
max-width:170px;
}
.layoutList .videowhisperBrief
{
top: 110px;
bottom:auto;
left: 242px;
}

.videowhisperGroupMode
{
position: absolute;
bottom: 2px;
left: 2px;
font-size: 12px;
color: #eef;
text-shadow:1px 1px 1px #333;
z-index: 10;
}
.layoutList .videowhisperGroupMode
{
top: 130px;
bottom: auto;
left: 242px;
}

.videowhisperGroupCPM
{
position: absolute;
bottom: 2px;
right: 60px;
font-size: 12px;
color: #efe;
text-shadow:1px 1px 1px #333;
z-index: 10;
}
.layoutList .videowhisperGroupCPM
{
top: 130px;
bottom: auto;
left: 350px;
right: auto;
}

.videowhisperCPM
{
position: absolute;
bottom: 62px;
right: 2px;
font-size: 12px;
color: #FFF;
text-shadow:1px 1px 1px #333;
z-index: 10;
}
.layoutList .videowhisperCPM
{
top: 150px;
bottom: auto;
left: 242px;
right: auto;
}

.videowhisperVote
{
position: absolute;
bottom: 50px;
left: 10px;
width: 80px;
z-index: 10;
alpha: 0.5;
}
.layoutList .videowhisperVote
{
top: 2px;
left: 400px;
right: auto;
bottom: auto;
}

.videowhisperRating
{
position: absolute;
bottom: 80px;
left:22px;
font-size: 15px;
color: #FFF;
text-shadow:1px 1px 1px #333;
z-index: 10;
}
.layoutList .videowhisperRating
{
top: 2px;
left: 400px;
right: auto;
bottom: auto;
}

.videowhisperFeatured {
  width: 140px;
  height: 21px;
  line-height: 21px;
  position: absolute;
  top: 21px;
  right: -35px;
  overflow: hidden;
  -webkit-transform: rotate(45deg);
  transform: rotate(45deg);
  box-shadow:0 0 0 3px #DD5743,  0px 21px 5px -18px rgba(0,0,0,0.6);
  background: #DD5743;
  color: #FFFFFF;
  text-align: center;
  opacity: 0.7;
  z-index: 11;
}


td {
    padding: 4px;
}
HTMLCODE,
		);

	}


	static function extensions_attachment()
	{
		// allowed file extensions
		$options = self::getOptions();

		if ( $options['attachment_extensions'] )
		{
			$extensions = explode( ',', $options['attachment_extensions'] );

			if ( is_array( $extensions ) )
			{
				foreach ( $extensions as $key => $value )
				{
					$extensions[ $key ] = trim( $value );
				}
				return $extensions;
			}
		}

		return array();
	}
 

	static function filtersFor( $hook = '' )
	{
		global $wp_filter;
		if ( empty( $hook ) || ! isset( $wp_filter[ $hook ] ) )
		{
			return;
		}

		print '<br><textarea readonly cols="100" rows="4">';
		// print '<pre>';
		print_r( $wp_filter[ $hook ] );
		// print '</pre>';
		print '</textarea>';
	}


	static function getAdminOptions()
	{

		$adminOptions = self::adminOptionsDefault();

		$features = self::roomFeatures();
		foreach ( $features as $key => $feature )
		{
			if ( $feature['installed'] )
			{
				$adminOptions[ $key ] = $feature['default'];
			}
		}

		$options = get_option( 'VWliveWebcamsOptions' );

		if ( ! empty( $options ) )
		{
			foreach ( $options as $key => $option )
			{
				$adminOptions[ $key ] = $option;
			}
		}

		update_option( 'VWliveWebcamsOptions', $adminOptions );

		return $adminOptions;
	}


	static function categoryIDs($csv)
	{
		$ids = array();
		if (!$csv) return $ids;

		$categories = explode(',', $csv);
		foreach ($categories as $category)
		{
			$cat = get_term_by('name', trim($category), 'category');
			if ($cat) $ids[] = $cat->term_id;
		}
		return $ids;
	}

	static function adminOptions()
	{
		$options = self::getAdminOptions();

		if ( isset( $_POST ) )
		{
			if ( ! empty( $_POST ) )
			{

				$nonce = $_REQUEST['_wpnonce'];
				if ( ! wp_verify_nonce( $nonce, 'vwsec' ) )
				{
					echo 'Invalid nonce!';
					exit;
				}

				foreach ( $options as $key => $value )
				{
					if ( isset( $_POST[ $key ] ) )
					{
						$options[ $key ] = trim( sanitize_textarea_field( $_POST[ $key ] ) );
					}
				}

				// sanitize html
				foreach (['suspendMessage', 'pendingMessage', 'dashboardMessage', 'dashboardMessageBottom', 'listingTemplate', 'listingTemplate2', 'listingTemplateList', 'listingTemplateHorizontal' ] as $optionName) if ( isset( $_POST[$optionName] ) )
						$options[$optionName] = wp_kses_post( $_POST[$optionName] );


				//sanitize as title
				foreach (['custom_post', 'rolePerformer', 'roleClient', 'roleStudio' ] as $optionName) if ( isset( $_POST[$optionName] ) ) 
				$options[$optionName] = sanitize_title_with_dashes(  $_POST[$optionName] );

					// config sanitizing & parsing
					
					
					if ( isset( $_POST['payoutMethodsConfig'] ) )
					{
						$options['payoutMethods'] = parse_ini_string( sanitize_textarea_field( $_POST['payoutMethodsConfig'] ), true );
					}

					if ( isset( $_POST['goalsConfig'] ) )
					{
						$options['goalsDefault'] = parse_ini_string( sanitize_textarea_field( $_POST['goalsConfig'] ), true );
					}

				if ( isset( $_POST['profileFieldsConfig'] ) )
				{
					$options['profileFields'] = parse_ini_string( sanitize_textarea_field( $_POST['profileFieldsConfig'] ), true );
				}

				if ( isset( $_POST['groupModesConfig'] ) )
				{
					$options['groupModes'] = parse_ini_string( sanitize_textarea_field( $_POST['groupModesConfig'] ), true );
				}

				if ( isset( $_POST['appSetupConfig'] ) )
				{
					$options['appSetup'] = parse_ini_string( sanitize_textarea_field( $_POST['appSetupConfig'] ), true );
				}

				if ( isset( $_POST['appRolesConfig'] ) )
				{
					$options['appRoles'] = parse_ini_string( sanitize_textarea_field( $_POST['appRolesConfig'] ), true );
				}

				if ( isset( $_POST['recordFieldsConfig'] ) )
				{
					$options['recordFields'] = parse_ini_string( sanitize_textarea_field( $_POST['recordFieldsConfig'] ), true );
				}

				if ( isset( $_POST['labelsConfig'] ) )
				{
					$options['labels'] = parse_ini_string( sanitize_textarea_field( $_POST['labelsConfig'] ), false );
				}

				//sanitize xml
				foreach ([ 'tipOptions' ] as $optionName) if ( isset( $_POST[$optionName] ) )
						$options[$optionName] =  sanitize_textarea_field( htmlspecialchars_decode( htmlspecialchars( $_POST[$optionName] ) ) ) ;


					// menus by role
					$nav_menus       = wp_get_nav_menus();
				$theme_locations = get_nav_menu_locations();

				foreach ( array( 'client', 'performer', 'studio' ) as $menu_role )
				{
					foreach ( $theme_locations as $location => $selected )
					{
						$var = 'menu_' . $menu_role . '_' . sanitize_text_field( $location );
						if ( isset( $_POST[ $var ] ) )
						{
							$options[ $var ] = sanitize_text_field( $_POST[ $var ] );
						}
					}
				}

				update_option( 'VWliveWebcamsOptions', $options );
			}
		}

		$optionsDefault = self::adminOptionsDefault();

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'setup';

		// <span class="nav-tab dashicons dashicons-admin-generic"></span>

?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>VideoWhisper PPV Live Webcams - Paid Videochat Site Solution</h2>
</div>

<nav class="nav-tab-wrapper wp-clearfix">
	<a href="admin.php?page=live-webcams&tab=webrtc" class="nav-tab <?php echo $active_tab == 'webrtc' ? 'nav-tab-active' : ''; ?>">WebRTC</a>
		<a href="admin.php?page=live-webcams&tab=app" class="nav-tab <?php echo $active_tab == 'app' ? 'nav-tab-active' : ''; ?>">HTML5 Videochat</a>

	<a href="admin.php?page=live-webcams&tab=server" class="nav-tab <?php echo $active_tab == 'server' ? 'nav-tab-active' : ''; ?>">RTMP / HLS</a>
	<a href="admin.php?page=live-webcams&tab=hls" class="nav-tab <?php echo $active_tab == 'hls' ? 'nav-tab-active' : ''; ?>">FFmpeg / Record / Push RTMP</a>

	<a href="admin.php?page=live-webcams&tab=restream" class="nav-tab <?php echo $active_tab == 'restream' ? 'nav-tab-active' : ''; ?>">ReStream RTSP</a>

	<a href="admin.php?page=live-webcams&tab=pages" class="nav-tab <?php echo $active_tab == 'pages' ? 'nav-tab-active' : ''; ?>">Pages & Menus</a>
	<a href="admin.php?page=live-webcams&tab=customize" class="nav-tab <?php echo $active_tab == 'customize' ? 'nav-tab-active' : ''; ?>">Customize</a>

	<a href="admin.php?page=live-webcams&tab=integration" class="nav-tab <?php echo $active_tab == 'integration' ? 'nav-tab-active' : ''; ?>">Integration</a>
	<a href="admin.php?page=live-webcams&tab=appearance" class="nav-tab <?php echo $active_tab == 'appearance' ? 'nav-tab-active' : ''; ?>">Appearance</a>
	<a href="admin.php?page=live-webcams&tab=listings" class="nav-tab <?php echo $active_tab == 'listings' ? 'nav-tab-active' : ''; ?>">Listings</a>


	<a href="admin.php?page=live-webcams&tab=group" class="nav-tab <?php echo $active_tab == 'group' ? 'nav-tab-active' : ''; ?>">Chat Modes</a>
	<a href="admin.php?page=live-webcams&tab=random" class="nav-tab <?php echo $active_tab == 'random' ? 'nav-tab-active' : ''; ?>">Random Chat</a>

	<a href="admin.php?page=live-webcams&tab=performer" class="nav-tab <?php echo $active_tab == 'performer' ? 'nav-tab-active' : ''; ?>">Performer</a>
	<a href="admin.php?page=live-webcams&tab=record" class="nav-tab <?php echo $active_tab == 'record' ? 'nav-tab-active' : ''; ?>">Account Records</a>
	<a href="admin.php?page=live-webcams&tab=features" class="nav-tab <?php echo $active_tab == 'features' ? 'nav-tab-active' : ''; ?>">Features</a>
	<a href="admin.php?page=live-webcams&tab=profile" class="nav-tab <?php echo $active_tab == 'profile' ? 'nav-tab-active' : ''; ?>">Room Profile</a>
	<a href="admin.php?page=live-webcams&tab=locations" class="nav-tab <?php echo $active_tab == 'locations' ? 'nav-tab-active' : ''; ?>">Locations</a>

	<a href="admin.php?page=live-webcams&tab=categories" class="nav-tab <?php echo $active_tab == 'categories' ? 'nav-tab-active' : ''; ?>">Contest Categories</a>

	<a href="admin.php?page=live-webcams&tab=studio" class="nav-tab <?php echo $active_tab == 'studio' ? 'nav-tab-active' : ''; ?>">Studio</a>

	<a href="admin.php?page=live-webcams&tab=client" class="nav-tab <?php echo $active_tab == 'client' ? 'nav-tab-active' : ''; ?>">Client</a>
	<a href="admin.php?page=live-webcams&tab=moderator" class="nav-tab <?php echo $active_tab == 'moderator' ? 'nav-tab-active' : ''; ?>">Moderator</a>

	<a href="admin.php?page=live-webcams&tab=geofencing" class="nav-tab <?php echo $active_tab == 'geofencing' ? 'nav-tab-active' : ''; ?>">GeoFencing</a>
	<a href="admin.php?page=live-webcams&tab=sightengine" class="nav-tab <?php echo $active_tab == 'sightengine' ? 'nav-tab-active' : ''; ?>">SightEngine</a>


	<a href="admin.php?page=live-webcams&tab=billing" class="nav-tab <?php echo $active_tab == 'billing' ? 'nav-tab-active' : ''; ?>">Billing Wallets</a>

	<a href="admin.php?page=live-webcams&tab=ppv" class="nav-tab <?php echo $active_tab == 'ppv' ? 'nav-tab-active' : ''; ?>">Pay Per Minute</a>
	<a href="admin.php?page=live-webcams&tab=messages" class="nav-tab <?php echo $active_tab == 'messages' ? 'nav-tab-active' : ''; ?>">Paid Messages</a>
	<a href="admin.php?page=live-webcams&tab=tips" class="nav-tab <?php echo $active_tab == 'tips' ? 'nav-tab-active' : ''; ?>">Gifts/Donations &amp; Goals</a>
	<a href="admin.php?page=live-webcams&tab=payouts" class="nav-tab <?php echo $active_tab == 'payouts' ? 'nav-tab-active' : ''; ?>">Payouts</a>

	<a href="admin.php?page=live-webcams&tab=sms" class="nav-tab <?php echo $active_tab == 'sms' ? 'nav-tab-active' : ''; ?>">SMS</a>
	<a href="admin.php?page=live-webcams&tab=lovense" class="nav-tab <?php echo $active_tab == 'lovense' ? 'nav-tab-active' : ''; ?>">Lovense</a>

	<a href="admin.php?page=live-webcams&tab=video" class="nav-tab <?php echo $active_tab == 'video' ? 'nav-tab-active' : ''; ?>">Videos Pictures Reviews</a>
	<a href="admin.php?page=live-webcams&tab=buddypress" class="nav-tab <?php echo $active_tab == 'buddypress' ? 'nav-tab-active' : ''; ?>">BuddyPress / BuddyBoss</a>

	<a href="admin.php?page=live-webcams&tab=translate" class="nav-tab <?php echo $active_tab == 'translate' ? 'nav-tab-active' : ''; ?>">DeepL / Translate</a>

	<a href="admin.php?page=live-webcams&tab=import" class="nav-tab <?php echo $active_tab == 'import' ? 'nav-tab-active' : ''; ?>">Import Settings</a>
	<a href="admin.php?page=live-webcams&tab=reset" class="nav-tab <?php echo $active_tab == 'reset' ? 'nav-tab-active' : ''; ?>">Reset</a>
	<a href="admin.php?page=live-webcams&tab=requirements" class="nav-tab <?php echo $active_tab == 'requirements' ? 'nav-tab-active' : ''; ?>">Requirements & Troubleshooting</a>

	<a href="admin.php?page=live-webcams&tab=support" class="nav-tab <?php echo $active_tab == 'support' ? 'nav-tab-active' : ''; ?>">Support</a>

	<a href="admin.php?page=live-webcams&tab=setup" class="nav-tab <?php echo $active_tab == 'setup' ? 'nav-tab-active' : ''; ?>">Setup</a>

</nav>

<form method="post" action="<?php echo wp_nonce_url( sanitize_text_field( $_SERVER['REQUEST_URI'] ) , 'vwsec' ); ?>">
		<?php

		switch ( $active_tab )
		{

		case 'locations':
			?>
			<h3>Locations / Langauges Database</h3>
			Locations and languages are retrieved from local files you can edit or regenerate from public data sources. This data is used for profiles, for users to pick from.
			<?php 

			$locationsPath = $options['uploadsPath'] . '/_locations/';
			if ( ! file_exists( $locationsPath ) )
			{
				mkdir( $locationsPath, 0777, true );
			}

			$fileLocations = $locationsPath . 'locations.json';
			$fileLanguages = $locationsPath . 'languages.json';

			//generate locations if called
			if ( isset( $_GET['generate_locations'] ) )
			{
				if ( !wp_verify_nonce( $_GET['_wpnonce'], 'vwsec' ) ) 
				{
					echo 'Invalid nonce!';
					exit;
				}

				echo '<br>Generating Location Database...';

				//retrieve languages from $options['locationLanguagesURL'] as json
				$languages = array();
				if ( $options['locationLanguagesURL'] )
				{    //language_code => language_name
					$languages = json_decode( file_get_contents( $options['locationLanguagesURL'] ), true );
					if (is_array($languages) && count($languages)) 
					{	
					//remove duplicates
					$languages = array_unique($languages, SORT_REGULAR);
					//sort in a way that shows main language like "en":"English" before dialects like en-us 
					uasort($languages, function($a, $b) {
						if (strpos($a, $b) !== false) return 1;
						if (strpos($b, $a) !== false) return -1;
						return strcmp($a, $b);
					});
					//save to file
					file_put_contents( $fileLanguages, json_encode( $languages ) );
					echo '<br>Languages Database Generated: ' . count( $languages ) . ' at ' . esc_html( $fileLanguages );
					} else echo '<br>Languages could not be retrieved from: ' . esc_html( $options['locationLanguagesURL'] );
				} else echo '<br>Languages URL not set.';


				//retrieve countries and regions
				$countries = array();
				if ( $options['locationCountriesURL'] )
				{
					$countries = json_decode( file_get_contents( $options['locationCountriesURL'] ), true );
					if (!is_array($countries) ||  !count($countries)) echo '<br>Countries could not be retrieved from: ' . esc_html( $options['locationCountriesURL'] );
					elseif ($options['locationRegionsURL']) 
					{
						$regions = json_decode( file_get_contents( $options['locationRegionsURL'] ), true );
						if (!is_array($regions) ||  !count($regions)) echo '<br>Regions could not be retrieved from: ' . esc_html( $options['locationRegionsURL'] );
						else
						{
							$locations = array();
							$countContinents = 0;
							$countCountries = 0;
							$countRegions = 0;
							
							foreach ($countries as $countryCode => $countryData) {
								$continentCode = $countryData['continent_code'];
								$continentName = $countryData['continent_name'];
								$countryName = $countryData['country_name'];
							
								// Initialize continent if not already set
								if (!isset($locations[$continentCode])) {
									$locations[$continentCode] = array(
										'continent_name' => $continentName,
										'countries' => array()
									);
								}
							
								// Initialize country if not already set
								if (!isset($locations[$continentCode]['countries'][$countryCode])) {
									$locations[$continentCode]['countries'][$countryCode] = array(
										'country_name' => $countryName,
										'regions' => array()
									);
								}
							
								// Add regions to the country
								foreach ($regions as $regionData) {
									if ($regionData['countryShortCode'] === $countryCode) {
										foreach ($regionData['regions'] as $region) if (isset($region['shortCode'])) {
											$locations[$continentCode]['countries'][$countryCode]['regions'][] = array(
												'region_name' => $region['name'],
												'region_code' => $region['shortCode']
											);
											$countRegions++;
										}
									}
								}
							}
							
							// Save the locations array to a JSON file
							file_put_contents($fileLocations, json_encode($locations));
							echo '<br>Locations Database Generated: ' . esc_html( $countRegions ) . ' regions at ' . esc_html($fileLocations) ;
						}

					}

				} else echo '<br>Countries URL not set.';
			}
					
					//default location is in data folder relative to parent folder of this script
					$locationsPathDefaut = dirname( plugin_dir_path( __FILE__ ) ) . '/data/';


						//if files do not exist, use from default path

						if ( ! file_exists( $fileLocations ) ) $fileLocations = $locationsPathDefaut . 'locations.json';
						if ( ! file_exists( $fileLanguages ) ) $fileLanguages = $locationsPathDefaut . 'languages.json';

						//if files exist report count of js entries

						echo '<br> - Locations File: ' . esc_html( $fileLocations );
						$locations = array();
						if ( file_exists( $fileLocations ) )
						{
							$locations = json_decode( file_get_contents( $fileLocations ), true );
							// display count for 3 levels array: continents, countries,  regions
							$countContinents = 0;
							$countCountries   = 0;
							$countRegions     = 0;

							foreach ( $locations as $continent )
							{
								$countContinents++;
								foreach ( $continent['countries'] as $country )
								{
									$countCountries++;
									$countRegions += count( $country['regions'] );
								}
							}

							echo '<br>Continents: ' . esc_html( $countContinents )  . ' Countries: ' . esc_html( $countCountries ) . ' Regions: ' . esc_html( $countRegions );
							echo ' <a href="' . esc_url( self::path2url( $fileLocations ) ) . '">View</a>';
						} else echo '<br>Locations File not found. Please generate.';

						//languages
						echo '<br> - Languages File: ' . esc_html( $fileLanguages );
						$languages = array();
						if ( file_exists( $fileLanguages ) )
						{
							$languages = json_decode( file_get_contents( $fileLanguages ), true );
							echo '<br>Languages: ' . count( $languages );
							echo ' <a href="' . self::path2url( $fileLanguages ) . '">View</a>';
						} else echo '<br>Languages File not found. Please generate.';

						//display Generate button using wp nonce for security
						echo '<br><a class="button" href="admin.php?page=live-webcams&tab=locations&generate_locations=1&_wpnonce=' . wp_create_nonce( 'vwsec' ) . '">Generate Locations</a>';
					?>

			<h4>Countries URL</h4>
			<input type="text" size="120%" name="locationCountriesURL" value="<?php echo esc_attr( $options['locationCountriesURL'] ); ?>" size="64" />
			<br>Default: <?php echo esc_html( $optionsDefault['locationCountriesURL'] ); ?>

			<h4>Regions URL</h4>
			<input type="text" size="120" name="locationRegionsURL" value="<?php echo esc_attr( $options['locationRegionsURL'] ); ?>" size="64" />
			<br>Default: <?php echo esc_html( $optionsDefault['locationRegionsURL'] ); ?>

			<h4>Languages URL</h4>
			<input type="text" size="120" name="locationLanguagesURL" value="<?php echo esc_attr( $options['locationLanguagesURL'] ); ?>" size="64" />
			<br>Default: <?php echo esc_html( $optionsDefault['locationLanguagesURL'] ); ?>

			<h4>Language Filters</h4>
			<select name="languageFilters">
				<option value="0" <?php echo $options['languageFilters'] == '0' ? 'selected' : ''; ?>>Disabled</option>
				<option value="1" <?php echo $options['languageFilters'] == '1' ? 'selected' : ''; ?>>Enabled</option>
			</select>
			<br>Loads languages on listings form and displays language filters. May slow down form loading and increase resource usage. Disable if not important for your project.

			<h4>Location Filters</h4>
			<select name="locationFilters">
				<option value="0" <?php echo $options['locationFilters'] == '0' ? 'selected' : ''; ?>>Disabled</option>
				<option value="1" <?php echo $options['locationFilters'] == '1' ? 'selected' : ''; ?>>Enabled</option>
				<option value="2" <?php echo $options['locationFilters'] == '2' ? 'selected' : ''; ?>>No Regions</option>
			</select>
			<br>Loads locations on listings form and displays location filters. May slow down form loading and increase resource usage. Has higher impact than languages due to bigger data size. Disable if not important for your project or use No Regions to filter only by Continent / Country (regions have thousands of options compared to couple of hundreds for languages and countries).
			
			<h4>Persistent Search</h4>
<select name="filtersSave" id="filtersSave">
  <option value="1" <?php echo $options['filtersSave'] == '1' ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['filtersSave'] == '0' ? 'selected' : ''; ?>>No</option>
</select>
<br>If enabled search filters will be saved for logged in users and used next time when they access listings. Persistent search may be useful on projects with complex filters/options like locations. Default is No (filters are reset on each page access so users get all listing with default parameters).

			<?php

			break;	
		case 'categories':
			?>
			<h3>Contest Categories</h3>
			Rooms can join special categories for contents, where users can vote with payment. Votes and rankings require Rate Star Reviews plugin. Voting and paid votes can also be configured from <a href="admin.php?page=rate-star-review">Rate Star Review settings</a> with same pricing, to display on item pages.


			<h4>Categories Message</h4>
			<?php
			$options['categoriesMessage'] = wp_kses_post( $options['categoriesMessage'] );
			wp_editor( $options['categoriesMessage'], 'categoriesMessage', $settings = array( 'textarea_rows' => 3 ) );
?>
<br>Shows in performer dashboard for Contests section. Could contain announcements, instructions, links to support.
Default:<br><textarea readonly cols="100" rows="2"><?php echo esc_textarea( $optionsDefault['categoriesMessage'] ); ?></textarea>

			<h4>Vote Cost</h4>
			<input type="text" name="voteCost" value="<?php echo esc_attr( $options['voteCost'] ); ?>" size="10" />
			<br>Cost paid by voter. If voting is also enabled from Rate Star Reviews plugin, should be same price.

			<h4>Vote Earning Ratio</h4>
			<input type="text" name="voteRatio" value="<?php echo esc_attr( $options['voteRatio'] ); ?>" size="10" />
			<br>Ratio paid to room owner (in example 0.5 means half). Default:  <?php echo esc_attr( $optionsDefault['voteRatio'] ); ?>

		
			<h4>Contest Categories</h4>
			<input type="text" name="categoriesContest" value="<?php echo esc_attr( $options['categoriesContest'] ); ?>" size="64" />
			<br>Comma separated list of categories for contests. Categories must be <a href="edit-tags.php?taxonomy=category&post_type=<?php echo esc_attr($options['custom_post']??'webcam')?>">predefined</a>. Leave blank to disable.
			<br>Example: Best Live Show, Best Dancer, Best Videos, Best Quality

			<?php
			if ($options['categoriesContest']) echo '<br>Category IDs for "' . esc_html($options['categoriesContest']). '": ' . json_encode( self::categoryIDs($options['categoriesContest']) );
			else echo '<br>No contest categories defined.';
			break;

		case 'sightengine':
			?>

			<h3>SightEngine: Live Stream Moderation</h3>
			Integrate <a href="https://sightengine.com">SighEngine</a> to detect and filter unwanted content in live streams, based on stream snapshot analysis. Depending on site terms, this can be used to detect and  nudity, violence, drugs, weapons and suspend rooms or optionally accounts. 
			<br>Requires SightEngine account (<a href="https://sightengine.com/pricing">free plan</a> available), cURL support on web host.
			<?php
			if ( ! function_exists( 'curl_init' ) )
			{
				?>
				<div class="error notice">
					<p><strong>Warning:</strong> cURL is not avaialble on this server. SightEngine integration requires cURL support.</p>
				</div>
				<?php
			} else echo '<br>cURL detected, version: ' . esc_html ( curl_version()['version'] );
			?>
			<h4>SightEngine Moderation</h4>
			<select name="sightengine">
				<option value="0" <?php echo $options['sightengine'] == '0' ? 'selected' : ''; ?>>Disabled</option>
				<option value="room" <?php echo $options['sightengine'] == 'room' ? 'selected' : ''; ?>>Room</option>
				<option value="account" <?php echo $options['sightengine'] == 'account' ? 'selected' : ''; ?>>Account</option>
			</select>
			<br>Room suspension can be temporary (with configurable timeout) or permanent (until unsuspended by admin). 
			<br>Account suspension is permanent (until unsuspended by admin) and applies in addition to room suspension.

			<h4>SightEngine API User</h4>
			<input type="text" name="sightengineUser" value="<?php echo esc_attr( $options['sightengineUser'] ); ?>" size="64" />
			<br>API Key from <a href="https://dashboard.sightengine.com/api-credentials">SightEngine Dashboard</a>. Blank will disable.

			<h4>SightEngine API Secret</h4>
			<input type="text" name="sightengineSecret" value="<?php echo esc_attr( $options['sightengineSecret'] ); ?>" size="64" />
			<br>Secret from <a href="https://dashboard.sightengine.com/api-credentials">SightEngine Dashboard</a>. Blank will disable.

			<h4>SightEngine API Models</h4>
			<input type="text" name="sightengineModels" value="<?php echo esc_attr( $options['sightengineModels'] ); ?>" size="64" />
			<br>Models to request for moderation. Supported: nudity-2.0,wad,offensive,gore . Default: <?php echo esc_attr( $optionsDefault['sightengineModels'] ); ?>
			<br>Multiple models involve multiple operations per request (with billing implications), in example 4 models involve 4 operations for each verification. Find out more about models per SightEngine <a href="https://sightengine.com/docs/models">model documentation</a>.

			<h4>SightEngine Detect Models</h4>
			<input type="text" name="sightengineDetect" value="<?php echo esc_attr( $options['sightengineDetect'] ); ?>" size="64" />
			<br>Models to use for detection, from SightEngine response. Some models involve direct detection and others have sub classes.
			<br>Example models: nudity,weapons,alcohol,drugs,offensive,gore . Default: <?php echo esc_attr( $optionsDefault['sightengineDetect'] ); ?>

			<h4>SightEngine Detect Classes</h4>
			<input type="text" name="sightengineClasses" value="<?php echo esc_attr( $options['sightengineClasses'] ); ?>" size="64" />
			<br>Classes to use for detection, from SightEngine response. Example: sexual_activity,sexual_display,prob . Default: <?php echo esc_attr( $optionsDefault['sightengineClasses'] ); ?>

			<h4>SightEngine Detection Threshold</h4>
			<input type="text" name="sightengineThreshold" value="<?php echo esc_attr( $options['sightengineThreshold'] ); ?>" size="10" />
			<br>Detection threshold for moderation, between 0.02 and 0.99 . Lower is more strict. Applies per model or class if model has more classes. Default: <?php echo esc_attr( $optionsDefault['sightengineThreshold'] ); ?>

			<h4>SightEngine Interval</h4>
			<input type="text" name="sightengineInterval" value="<?php echo esc_attr( $options['sightengineInterval'] ); ?>" size="10" />
			<br>Interval in seconds between SightEngine moderation checks, per room. Shorter interval provides faster detection but involves more requests (with billing implications). Default: <?php echo esc_attr( $optionsDefault['sightengineInterval'] ); ?>

			<h4>Room Suspension Timeout</h4>
			<input type="text" name="suspendTimeout" value="<?php echo esc_attr( $options['suspendTimeout'] ); ?>" size="10" />s
			<br>Timeout in seconds for room suspension, per room, in seconds. Set 0 to make suspensions permanent. Default: <?php echo esc_attr( $optionsDefault['suspendTimeout'] ); ?>

			<h4>Room Suspension Message</h4>
			<?php		
		$options['suspendMessage'] = wp_kses_post( stripslashes( $options['suspendMessage'] ) );
		wp_editor( $options['suspendMessage'], 'suspendMessage', $settings = array( 'textarea_rows' => 3, 'media_buttons' => false ) );
			?>
			<br>Message to display to performer when room is suspended. Default: <I><?php echo wp_kses_post( $optionsDefault['suspendMessage'] ); ?></I>

			<?php
			submit_button();

			echo '<h4>Test SightEngine Integration</h4>';
			$postID = get_user_meta( get_current_user_id(), 'currentWebcam', true );
			if (!$postID) echo '<br>Create a room to test!';
			else
			{
				$snapshot = get_post_meta( $postID, 'snapshot', true );
				if ($snapshot) 
				{
					echo 'Output for moderateSnapshot:<br>' . esc_html ( self::moderateSnapshot( $snapshot, $postID, $options, 1 ) );
					echo '<br><br><img src="' . esc_url( self::path2url($snapshot) ) . '?time=' . time() . '" />';
				}
				else echo '<br>No snapshot available for room ' . esc_attr( $postID );	
			}

			break;

		case 'payouts':
			?>
			<h3>Payouts</h3>
			Configure local <a href="admin.php?page=live-webcams-payouts">payout tools</a>. The payout tool can be used to assist with making payouts, including generating custom CSV payout lists for mass payouts, where possible (in example <a href="https://www.paypal.com/us/cshelp/article/how-do-i-send-a-payouts-mass-payment-help252">Paypal</a>, <a href="https://www.skrill.com/fileadmin/content/pdf/Skrill_Mass_Payments_Guide.pdf">Skrill</a>).

			<h4>Minimum Balance Amount</h4>
			<input type="text" name="payoutMinimum" value="<?php echo esc_attr( $options['payoutMinimum'] ); ?>" size="10" /><?php echo esc_html( $options['currency'] ); ?>
			<br>Minimum balance required to receive a payout. If not reached, balance will be carried over to next payout. Default: <?php echo esc_attr( $optionsDefault['payoutMinimum'] ); ?><?php echo esc_html( $options['currency'] ); ?>

			<h4>Maximum Payout Amount</h4>
			<input type="text" name="payoutMaximum" value="<?php echo esc_attr( $options['payoutMaximum'] ); ?>" size="10" /><?php echo esc_html( $options['currency'] ); ?>
			<br>Maximum payout payable from balance, per account. Set 0 to disable. You may consider sending big payouts in multile batches or using special methods. Default: <?php echo esc_attr( $optionsDefault['payoutMaximum'] ); ?><?php echo esc_html( $options['currency'] ); ?>

			<h4>Payouts per Page</h4>
			<input type="text" name="payoutPerPage" value="<?php echo esc_attr( $options['payoutPerPage'] ); ?>" size="10" />
			<br>Number of users to list per page when searching for users that need to get paid. Can be used to generate sequencial payout lists. Default: <?php echo esc_attr( $optionsDefault['payoutPerPage'] ); ?>

			<h4>Exchange Rate</h4>
			<input type="text" name="payoutExchange" value="<?php echo esc_attr( $options['payoutExchange'] ); ?>" size="10" />
			<br>Exchange rate for tokens to currency. Default: <?php echo esc_attr( $optionsDefault['payoutExchange'] ); ?>
			
			<h4>Payout Currency</h4>
			<input type="text" name="payoutCurrency" value="<?php echo esc_attr( $options['payoutCurrency'] ); ?>" size="10" />
			<br>Currency used for payouts. Default: <?php echo esc_attr( $optionsDefault['payoutCurrency'] ); ?>

			<h4>Extra Payout Roles</h4>
			<input type="text" name="payoutRoles" value="<?php echo esc_attr( $options['payoutRoles'] ); ?>" size="64" />
			<br>Add comma separated roles that should receive payouts. Default payout roles include studio role and all performer roles without the 'administrator', 'super-admin' roles. The 'administrator' role is added as extra for testing but should be removed for production. Default: <?php echo esc_attr( $optionsDefault['payoutRoles'] ); ?>

			<h4>Balance Meta</h4>
			<select name="payoutBalanceMeta">
				<option value="auto" <?php echo $options['payoutBalanceMeta'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
				<option value="vw_ppv_balance" <?php echo $options['payoutBalanceMeta'] == 'vw_ppv_balance' ? 'selected' : ''; ?>>PaidVideochat</option>
				<option value="micropayments_balance" <?php echo $options['payoutBalanceMeta'] == 'micropayments_balance' ? 'selected' : ''; ?>>MicroPayments</option>
			</select>
			<br>Meta when searching for users that need to get paid. PaidVideochat refers to local balance from main wallet configured, but may not be accurate if transactions occured in other plugins and was not updated after (by accessing PaidVideochat balance/transactions features, including in dashboards and backend user listings where balance queried). This applies to initial discovery query as local balance is updated when users are listed (so payouts are up to date).
			<br>When using MicroPayments as main wallet it's best to also use that as Balance Meta. And that's what Auto setting does.

			<h4>Payout Method Field</h4>
			<input type="text" name="payoutMethodField" value="<?php echo esc_attr( $options['payoutMethodField'] ); ?>" size="32" />
			<br>Field name for payout method in <a href="admin.php?page=live-webcams&tab=record">Administrative Records</a>. Default: <?php echo esc_attr( $optionsDefault['payoutMethodField'] ); ?>

			<h4>Payout Methods</h4>
			<textarea name="payoutMethodsConfig" id="payoutMethodsConfig" cols="120" rows="6"><?php echo esc_textarea( $options['payoutMethodsConfig'] ); ?></textarea>
			<br/>Define payout methods that can be used with the payout tool to generate CSV payout lists and quick processing.
			<br/>- title contains the payout method name (that user selects with Payout Method field in <a href="admin.php?page=live-webcams&tab=record">Administrative Records</a>).
			<br/>- csv parameter contains list of fields to be included in csv export. Can include field names from Administrative Records and these special fields: #amount (balance = amount to be paid out), #email (user account email, if you want to force users to have same email), #reference (Payout Note + username), #login (username)
			<br/>- to implement mass payouts please check documentation for each payout method that supports it (i.e. <a href="https://www.paypal.com/us/cshelp/article/how-do-i-send-a-payouts-mass-payment-help252">Paypal</a>, <a href="https://www.skrill.com/fileadmin/content/pdf/Skrill_Mass_Payments_Guide.pdf">Skrill</a>) and if necessary discuss with their support team for approval, final verifications for production.
			<br/>Default:
			<br/><textarea readonly cols="120" rows="4"><?php echo esc_textarea( $optionsDefault['payoutMethodsConfig'] ); ?></textarea>

<BR>Parsed configuration (should be an array or arrays) JSON:<BR>
				<?php
			echo esc_html( json_encode( $options['payoutMethods'] ) );
?>
			<h4>Report CSV</h4>
			<input type="text" name="reportCSV" value="<?php echo esc_attr( $options['reportCSV'] ); ?>" size="80" />
			<br>Payout report CSV with fields similar to CSV of payout methods. Additionally the #method field includes selected method. Default: <?php echo esc_attr( $optionsDefault['reportCSV'] ); ?>
	
			<?php submit_button(); ?>

			<h4>Generic Payout Information</h4>
Webmaster can see balance for all users (depending on billing wallet used) and adjust as necessary (in example when making payouts to performers manually with a method of choice). 
<br/>- Webmaster can define <a href="admin.php?page=live-webcams&tab=record">Administrative Records</a> to request details from performers including payout method. Performers fill administrative records on registration and anytime later from their Performer Dashboard. These administrative records show when approving users and reviewing records is accessible from <a href="users.php">users list</a>.
<br/>- The <a href="admin.php?page=live-webcams-payouts">Payout tool</a> can be used to assist with making payouts, including custom CSV lists for mass payouts where possible (in example <a href="https://www.paypal.com/us/cshelp/article/how-do-i-send-a-payouts-mass-payment-help252">Paypal</a>, <a href="https://www.skrill.com/fileadmin/content/pdf/Skrill_Mass_Payments_Guide.pdf">Skrill</a>).
<br/>- Payouts processed with the Payout tool are available in <a href="admin.php?page=live-webcams-payout-report">Payout Reports</a>.
<br/>– If you use TeraWallet, there is a premium addon <a href="https://standalonetech.com/product/wallet-withdrawal/">Withdrawal</a> (developed by TeraWallet providers) that can be used on some projects per their Withdrawal documentation to withdraw with Paypal, Stripe, Bank.
<br/>– For adult websites you can explore these manual payout options listed by major adult operators: ACH, SEPA, International Wire Transfer, Crypto, Directa24, PagoMundo, Paxum, CosmoPayment, ePayService, WebMoney, Skrill, Check by Mail.
<br/><a href="https://paidvideochat.com/features/billing-payment-gateways/#payout">Read about Payouts on PaidVideochat.com</a>...
			<?php
			break;

		case 'restream':	
		?>
		<h3>ReStreaming / RTSP IP Cameras</h3>		
This functionality requires web and streaming services on same server (host) when using stream configuration files for Wowza SE, as provided with <a href="https://webrtchost.com/hosting-plans/#Complete-Hosting">WebRTC Host - Complete plans</a>. 
		<?php
				if ( $testAddress = sanitize_text_field(  $_GET['testAddress'] ?? '' ) )
				{
					
					$testAddress = base64_decode( $testAddress );
					echo '<h4>Trying Address</h4>';
					echo esc_html( $testAddress );
					
					echo esc_html( self::restreamTest( $testAddress, $options ) );
				}
				
					if ( $removeStream = intval( $_GET['removeStream'] ) ?? 0 ) {
					echo '<h4>Removing Streams</h4>';

					$roomPost = get_post( $removeStream );
					if ( ! $roomPost ) {
						echo 'Room not found: #' . esc_html( $removeStream );
					} else {
						$stream = sanitize_file_name( $roomPost->post_title );
						echo 'Room Stream: ' . esc_html( $stream );

							$reStreams    = get_post_meta( $post->ID, 'reStreams', true );
							if (is_array($reStreams))
							foreach ($reStreams as $stream => $address) 
							{
							$streamFile = $options['streamsPath'] . '/' . $stream;

							if ( file_exists( $streamFile ) ) {
							$ftime = filemtime( $streamFile );
							echo '<br>Found file date: ' . esc_html ( date( DATE_RFC2822, $ftime ) );
							unlink( $streamFile );
							echo '<br>Removed: ' . esc_html( $streamFile );
						} else {
							echo '<br>Stream file not found: ' . esc_html( $streamFile );
						}
						
							}


						

						update_post_meta( $roomPost->ID, 'reStreams', '' );
						echo '<br>Removed room re-streaming configuration.';

					}
				}
				
				
?>
<h4>ReStreaming Rooms</h4>
				<?php

				$addresses = array();

				$ztime = time();

				// query
				$meta_query = array(
					'relation' => 'AND', // Optional, defaults to "AND"
					array(
						'key'     => 'reStreams',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => 'reStreams',
						'compare' => 'EXISTS',
					),
				);

				$args = array(
					'post_type'   => $options['custom_post'],
					'numberposts' => -1,
					'orderby'     => 'post_date',
					'order'       => 'DESC',
					'meta_query'  => $meta_query,

				);

				$posts = get_posts( $args );

				echo '<table><tr><th>Room</th><th>Owner</th><th>Remove</th><th>Streams</th><th>Paused</th><th>Accessed</th><th>Accessed by an user</th><th>Owner active</th><th>Broadcast</th><th>Thumb</th></tr>';

				if ( is_array( $posts ) && count( $posts ) ) {
						foreach ( $posts as $post ) {
							echo '<tr ' . esc_attr( ++$k % 2 ? 'class="alternate"' : '' ) . '>';
						
							// update status
							self::restreamPause( $post->ID, $options );

							$edate     = intval( get_post_meta( $post->ID, 'edate', true ) );
							$thumbTime = intval( get_post_meta( $post->ID, 'snapshotDate', true ) );

							$reStreams    = get_post_meta( $post->ID, 'reStreams', true );
							$restreamPaused = get_post_meta( $post->ID, 'restreamPaused', true );

							// access time
							$accessedUser = intval( get_post_meta( $post->ID, 'accessedUser', true ) );
							$accessed     = intval( get_post_meta( $post->ID, 'accessed', true ) );

							// author site access time
							$userID = get_post_field( 'post_author', $post->ID );
							$user   = get_userdata( $userID );

							$accessTime = intval( get_user_meta( $userID, 'accessTime', true ) );

							echo '<TH><a href="' . get_permalink( $post->ID ) . '" target="_room">' . esc_html( $post->post_title ) . '</a></TH>';
							echo '<td>' . esc_html( $user->user_login ) . '</td>';
							
							echo '<TD>';	
							if (is_array($reStreams))
							foreach ($reStreams as $stream => $address) 
							{
								$addresses[ $stream ] = $address;
								echo '<div><a class="secondary button" target="_vwtry" href="admin.php?page=live-webcams&tab=restream&testAddress=' . esc_attr ( urlencode( base64_encode($address) ) ) . '">Try</a> <a class="secondary button" href="admin.php?page=live-webcams&tab=restream&removeStream=' . esc_url($stream) . '">Remove</a></TD><TD><small>' . esc_html( htmlspecialchars( $address ) ) . '</small></div>';
							}
							echo '</TD>';
							
							echo '<td>' . ( $restreamPaused ? 'Yes' : 'No' ) . '</td>';
							echo '<td>' . esc_html( self::format_age( $ztime - $accessed ) ) . '</td>';
							echo '<td>' . esc_html( self::format_age( $ztime - $accessedUser ) ) . '</td>';
							echo '<td>' . esc_html( self::format_age( $ztime - $accessTime ) ) . '</td>';

							echo '<td>' . esc_html( self::format_age( $ztime - $edate ) ) . '</td>';
							echo '<td>' . esc_html( self::format_age( $ztime - $thumbTime ) ) . '</td>';

							echo '</tr>';


						}
					} else {
						echo '<tr><td colspan=6>No rooms with external stream sources.<td></tr>';
					}
				
				echo '</table>';
?>

<h4>Stream Files (Active Configurations)</h4>
Stream files in configured streams folder:
				<?php
					echo esc_html( $options['streamsPath'] );

				$removeFile =  base64_decode( sanitize_text_field( $_GET['removeFile'] ?? '' ) );
				if ( $removeFile ) {
					echo '<br>Remove: ' . esc_html( $removeFile );

					if ( substr( $removeFile, 0, strlen( $options['streamsPath'] ) ) == $options['streamsPath'] ) {
						if ( file_exists( $removeFile ) ) {
							unlink( $removeFile );
						} else {
							echo ' NOT FOUND!';
						}
					} else {
						echo ' BAD PATH!';
					}
				}
				
				
				//list .stream files
				$files = array();
				foreach ( glob( $options['streamsPath'] . '/*.stream' ) as $file ) {
					$files[] = $file;

				}

				if ( count( $files ) ) {
					foreach ( $files as $file ) {
						$address = file_get_contents( $file );
						echo '<BR>' . esc_html( $file . ' : ' . htmlspecialchars( $address ) );
						echo ' <a class="secondary button" href="admin.php?page=live-webcams&tab=restream&removeFile=' .  urlencode( base64_encode( $file ) ) . '">Remove</a> | ';

						$found = array_search($address, $addresses);
						
						if ( ! $found ) {
							echo ' * NOT assigned to any room!';
								}else {
							echo esc_html( $found );
							};
					}
				} else {
					echo '<br>No stream files detected in configured folder.';
				}
?>
		
<h4>Re-Streams / RTSP IP Cameras</h4>
<select name="reStreams" id="reStreams">
  <option value="0" <?php echo $options['reStreams'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['reStreams'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<BR>Allow room owners to add external stream sources to restream in own room, including RTSP IP cameras. This is bitrate intensive for streaming server as it needs to retrieve the streams.
<br>Stream addresses can be added from Performer Dashboard > Streams tab. 

<h4>Streams Path</h4>
				<?php
			if ( $options['streamsPath'] == $optionsDefault['streamsPath'] )
			{
				if ( file_exists( ABSPATH . 'streams' ) )
				{
					$options['streamsPath'] = ABSPATH . 'streams';
					echo 'Save to apply! Detected: ' . esc_html( $options['streamsPath'] ) . '<br>';
				}
			}
?>
<input name="streamsPath" type="text" id="streamsPath" size="100" maxlength="256" value="<?php echo esc_attr( $options['streamsPath'] ); ?>"/>
<BR>Wowza SE path for stream files.
<br>Server administrator must add stream monitoring for your app in startupStreamsMonitorApplicationList from Server.xml for Wowza SE. App must match HTTP Streaming Base configuration.

<h4>HTTP Streaming Base URL</h4>
<input name="httpstreamer" type="text" id="httpstreamer" size="100" maxlength="256" value="<?php echo esc_attr( $options['httpstreamer'] ); ?>"/>
<br>HTTPS base for HLS (usually based on Wowza SE StreamLock URL). SSL certificate is required in recent browsers for live streaming on HTTPS sites.

<h4>HTTP Streaming Base URL for ReStreaming (Optional)</h4>
<input name="httprestreamer" type="text" id="httprestreamer" size="100" maxlength="256" value="<?php echo esc_attr( $options['httprestreamer'] ); ?>"/>
<br>Optionally, a special application can be configured for RTSP re-streaming, with a different configuration that for RTMP broadcasts. If not defined, the deafault HTTP Streaming Base URL is used.


<h4>Auto Pause</h4>
Pause re-streaming while not needed, to reduce bandwidth usage / server load.
<br>
<select name="restreamPause" id="restreamPause">
  <option value="0" <?php echo $options['restreamPause'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['restreamPause'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select>
<h4>Resume</h4>
Restreaming updates done by WP cron that also updates snapshots.
				<?php
					echo '<BR>Next automated check (WP Cron, 10 min or more depending on site activity): in ' . esc_html ( wp_next_scheduled( 'cron_10min_event' ) - time() ) . 's';
				?>

<h5>Activity Timeout</h5>
<input name="restreamTimeout" type="text" id="restreamTimeout" size="16" maxlength="32" value="<?php echo esc_attr( $options['restreamTimeout'] ); ?>"/>s

<br>Resume if any of these occurred during timeout period:

<h5>Resume On Channel Access</h5>
<select name="restreamAccessed" id="restreamAccessed">
  <option value="0" <?php echo $options['restreamAccessed'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['restreamAccessed'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select> Any access (visitor or registered user) will resume stream. When streams should be accessible by anybody. Warning: This can be triggered often by crawlers, bots.

<h5>Resume On Channel Access by Registered User</h5>
<select name="restreamAccessedUser" id="restreamAccessedUser">
  <option value="0" <?php echo $options['restreamAccessedUser'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['restreamAccessedUser'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select> Registered user access will resume stream. When service is used by site members (IPTV site).

<h5>Resume On Owner Active</h5>
<select name="restreamActiveOwner" id="restreamActiveOwner">
  <option value="0" <?php echo $options['restreamActiveOwner'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['restreamActiveOwner'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select> Channel owner active on site will resume streams. When service is used by owner (IP camera monitoring site). Not recommended when many streams are setup under same account, as that can deplete server / account streaming capacity for running all streams at same time.

<h5>Resume On Any User Active</h5>
<select name="restreamActiveUser" id="restreamActiveUser">
  <option value="0" <?php echo $options['restreamActiveUser'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['restreamActiveUser'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select>ANY registered user active on site will resume ALL streams. When there are few streams and site is used by few users that can check all streams (in example paid membership only sites).

<?php
			break;
		

		case 'moderator';

?>
<h3>Moderators</h3>
Moderators can access group chats (including paid) silently and kick users. Moderators don't generate client charges and can't trigger paid features like private requests, tips. Restrictions like geofencing, location bans, access lists do not apply to moderator roles.

<h4>Moderator Roles</h4>
<input name="roleModerators" type="text" id="roleModerators" size="40" maxlength="64" value="<?php echo esc_attr( $options['roleModerators'] ); ?>"/>
<br>Comma separated roles.
 
<?php
			break;

		case 'lovense';

?>
<h3>Lovense Integration</h3>
HTML5 Videochat can notify Lovense API of tips, for toy reactions. When performer receives a tip, videochat will notify Lovense browser/extension and show an extra notification in chat. Integration currently provides 2 features: notify Lovense API of tips so performer toy can produce a reaction and list/filter performers that have Lovense toys active. 

<br>After <a href="https://www.lovense.com/signup">registering with Lovense</a>, configure your site from <a href="https://www.lovense.com/user/developer/info">Lovense developer dashboard</a>.
<h4>Lovense Integration</h4>
<select name="lovense" id="lovense">
  <option value="0" <?php echo $options['lovense'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['lovense'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<br>Load Lovense broadcaster API for performer and videochat app notifies on tips. Performer needs to access the HTML5 Videochat page with the <a href="https://www.lovense.com/cam-model/guides/Written-Guides/Installation-Guides/Extension-–-Chrome">Chrome + Lovense Browser Extension</a>, <a href="https://www.lovense.com/cam-model/guides/mac">Browser on Mac</a>, <a href="https://www.lovense.com/cam-model/guides/pc-phone">Browser on Pc</a> to integrate with own toy. Lovense extension must be enabled from chrome://extensions/ and login is required, as described in the tutorials. If performer uses Lovense browser or extension, after login in extension, performer should see version in text chat (ex: Lovense 30.8.5) which means the API detected the extension.
	
<h4>Website (Platform) Name</h4>
<input name="lovensePlatform" type="text" id="lovensePlatform" size="32" maxlength="64" value="<?php echo esc_attr( $options['lovensePlatform'] ); ?>"/>
<br>As configured in <a href="https://www.lovense.com/user/developer/info">Lovense Dashboard</a> per <a href="https://www.lovense.com/sextoys/developer/doc#step-1-configure-your-dashboard">integration instructions</a>.
<br>Website URL: <?php echo get_site_url() ?>
<br>Model Broadcasting Page: <?php echo get_site_url( null, $options['custom_post']) . '/*' ?>
<br>You need your plaform active because the toy response needs to be configured in the Lovense Chrome Extension by performer, specially for this platform (your website). That involves configuring certain reactions depending on tip amount as in the <a href=https://developer.lovense.com/docs/cam-solutions/cam-extension-for-chrome.html">documentation screenshot</a>.
<p>After configuring website you will need to contact Lovense as described in <a href="https://www.lovense.com/user/developer/info">Lovense Developers dashboard</a> to get your setup tested and approved: "Status：pending - When your integration is complete contact us to start testing."

<h4>Lovense Tip Parameters</h4>
<select name="lovenseTipParams" id="lovenseTipParams">
  <option value="4" <?php echo $options['lovenseTipParams'] == 4 ? 'selected' : ''; ?>>4: camExtension.receiveTip(amount, modelName, tipperName, cParameter) </option>
  <option value="3" <?php echo $options['lovenseTipParams'] == 3 ? 'selected' : ''; ?>>3: camExtension.receiveTip(amount, tipperName, cParameter) </option>
  <option value="2" <?php echo $options['lovenseTipParams'] == 2 ? 'selected' : ''; ?>>2: camExtension.receiveTip(amount, tipperName) </option>
</select>
<br>When the model receives a tip, integration will call receiveTip to tell the Cam Extension. The Cam Extension will trigger a response in the toy.
<br><a href="https://developer.lovense.com/docs/cam-solutions/cam-extension-for-chrome.html#step-3-methods-events">Lovense documentation</a> currently mentions 2 parameters camExtension.receiveTip(amount, tipperName) , was previously mentioning 4 and integration feedback suggests 3. Use what works - you can contact Lovense support to work with their recent API.

<br>If there's any changes required for using the latest API (like different parameters) provide feedback to VideoWhisper to apply such changes to the function calls.</p>

<h4>Active Toy Integration</h4>
<select name="lovenseToy" id="lovenseToy">
	  <option value="0" <?php echo $options['lovenseToy'] ? '' : 'selected'; ?>>Disabled</option>
  	  <option value="auto" <?php echo $options['lovenseToy'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
  	  <option value="manual" <?php echo $options['lovenseToy'] == 'manual' ? 'selected' : ''; ?>>Manual</option>
</select>
<br>Show performers that have toys active, in listings. 
<br>On Auto, active toy is detected from Lovense API when performer accesses HTML5 Videochat, based on camExtension.on("toyStatusChange" . This both enables and disables that toy status in listings. This integration notifies web server when performer toy status changes: camExtension.on("toyStatusChange", (data) => { .. window.VideoWhisper.chatServer("toyStatusChange", data); - which notifies web server to update room status: serverUpdate{ 'task': 'externaljs', 'action': 'toyStatusChange', 'data': data }.
<br>On Manual, the performer can manually set it from room setup. Can be more reliable.
<br>Disable Toy integration or entire Lovense integration to hide filters & icon in listings, controls.
<?php
			break;

		case 'buddypress';

?>
<h3>BuddyPress/BuddyBoss Integration</h3>
<a href="https://buddypress.org">BuddyPress</a> helps you build any kind of community website using WordPress, with member profiles, activity streams, user groups, messaging, and more.
<br><a href="https://www.buddyboss.com/platform/">BuddyBoss Platform</a> is a fork (tweaked version) of BuddyPress. A Pro version is availble with BuddyBoss Theme. Before installing BuddyBoss Platform, BuddyPress plugin needs to be removed if already installed.

<h4>BuddyPress/BuddyBoss Groups</h4>
				<?php
			if ( class_exists( 'BP_Group_Extension' ) )
			{
				echo 'Detected.';
			} else
			{
				echo 'Not detected. To use this integration, activate <a href="https://wordpress.org/plugins/buddypress/">BuddyPress</a> and <a href="options-general.php?page=bp-components">Groups component</a>.';
			}
?>
<br>Group admins can setup the group room from Manage > Webcams tab.
<br>Group members can access it from Webcams tab and at room URL. Only group members can participate in group room.



<h4>Activity Stream Cooldown</h4>
<input name="activityCooldown" type="text" id="activityCooldown" size="10" maxlength="30" value="<?php echo esc_attr( $options['activityCooldown'] ); ?>"/>
<br>Cooldown before new activity stream post for automated posts like going live.

<h4>BuddyPress/BuddyBoss Members Directory</h4>
<select name="buddypressDirectory" id="buddypressDirectory">
  <option value="" <?php echo $options['buddypressDirectory'] ? '' : 'selected'; ?>>All</option>
  <option value="webcam" <?php echo $options['buddypressDirectory']=='webcam' ? 'selected' : ''; ?>>Has Room</option>
  <option value="verified" <?php echo $options['buddypressDirectory']=='verified' ? 'selected' : ''; ?>>Verified</option>
</select>
<br>Filter users showing in BP/BB members directory, based on user meta (has current room setup or verified as performer). Enables listing only performers/creators instead of all users.

<h4>Profile Connect Tab</h4>
<select name="buddypressConnect" id="buddypressConnect">
  <option value="0" <?php echo $options['buddypressConnect'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['buddypressConnect'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Shows options to quickly connect with user like current chat room and conversations options.

<h4>Integrate Conversations in Profile Connect Tab</h4>
<select name="buddypressSupport" id="buddypressSupport">
  <option value="0" <?php echo $options['buddypressSupport'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['buddypressSupport'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Requires <a href="https://wordpress.org/plugins/live-support-tickets/">VideoWhisper - Live Support Tickets Plugin</a>.


<h4>Questions for Current Room in Profile Connect Tab</h4>
<select name="buddypressConnectMessages" id="buddypressConnectMessages">
  <option value="0" <?php echo $options['buddypressConnectMessages'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['buddypressConnectMessages'] ? 'selected' : ''; ?>>Yes</option>
</select>


<h4>Embed Current Room in Profile Connect Tab</h4>
<select name="buddypressEmbed" id="buddypressEmbed">
  <option value="0" <?php echo $options['buddypressEmbed'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['buddypressEmbed'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Warning: Disable when controling access to room page with MicroPayments or other plugins as this provides access to videochat room without access to page. When disabled shows button linking to room page (subject to access control).
<br>Also useful when BuddyPress template alters the styling of interface elements defined by Semantic UI in a way that breaks the design.

<h4>Restrict Group Creation to Performers</h4>
<select name="buddypressGroupPerformer" id="buddypressGroupPerformer">
  <option value="0" <?php echo $options['buddypressGroupPerformer'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['buddypressGroupPerformer'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enable only performers to create groups. Performer able roles: <?php echo esc_html( implode( ', ', self::getRolesPerformer( $options ) ) ); ?>
<br>Suggestion: Use the <a href="https://wordpress.org/plugins/limit-groups-per-user/">Limit BuddyPress Groups per User</a> plugin for more restrictions, to prevent group flood.

<h4>Profile Rooms Tab</h4>
<select name="buddypressRooms" id="buddypressRooms">
  <option value="0" <?php echo $options['buddypressRooms'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['buddypressRooms'] ? 'selected' : ''; ?>>Yes</option>
</select>


<h4>Profile Video Messages Tab</h4>
<select name="buddypressMessages" id="buddypressMessages">
  <option value="0" <?php echo $options['buddypressMessages'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['buddypressMessages'] ? 'selected' : ''; ?>>Yes</option>
</select>




<h4>WP Registration Form Roles</h4>
<select name="registrationFormRole" id="registrationFormRole">
  <option value="1" <?php echo $options['registrationFormRole'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['registrationFormRole'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Add roles to default WordPress registration form so users can register as client, performer or studio (if enabled). Disable only if you use other roles and assignation system (ie. with a membership plugin).
<br>BuddyPress: This option disabled redirect to BP registration form because that does not include the roles.

				<?php

			break;

		case 'customize';

			$options['custom_post']   = sanitize_title_with_dashes( $options['custom_post'] );
			$options['rolePerformer'] = sanitize_title_with_dashes( trim( $options['rolePerformer'] ) );
			$options['roleClient']    = sanitize_title_with_dashes( trim( $options['roleClient'] ) );

?>
<h3><?php _e( 'Customize Features/Labels by Site Type', 'ppv-live-webcams' ); ?></h3>
Customize content, roles depending on site type and theme. This is a wizard to configure/access most important content type customization sections.

<h4>Rooms: Room Post Name / Webcam Listing</h4>
<input name="custom_post" type="text" id="custom_post" size="12" maxlength="32" value="<?php echo esc_html( strtolower( $options['custom_post'] ) ); ?>"/>
<br>Custom post name for webcams (only alphanumeric, lower case). Will be used for webcams urls.
<br>Ex: webcam, room, office, venue, meeting, conference, business, company, shop, agency
<br>Save <a href="options-permalink.php">Settings > Permalinks</a> to apply new URL structure and make new post types accessible.
<br>In many use cases, each performer needs 1 room and that is created automatically on first access from dashboard.
<br>Custom post name also needs to be updated in other plugins that may provide features for it, like for <a href="admin.php?page=paid-membership&tab=content">MicroPayments - Paid Content</a>.
<br>Warning: Changing this at runtime breaks all previously created rooms. New settings only applies for new posts. Previous posts (with previous custom type) will no longer be accessible and performers will need to configure new listings. Should be configured before going live. If room type changed at runtime, existing performers need to select the new room (from Select/Manage Rooms) before going live, as room post of previous type remains selected. Restoring a previous type, will also restore that.

<h4>Rooms Settings & Management</h4>
Each room listing has a profile, own settings, assigned categories, tags, videos, pictures.
<UL>
<LI> + <a href="admin.php?page=live-webcams&tab=profile">Room Listing Profile Fields / Questions</a>: Webcam Room - Profile Setup</LI>
<LI> + <a href="edit-tags.php?taxonomy=category&post_type=<?php echo esc_attr( $options['custom_post'] ); ?>">Categories</a>: Content Categories also Apply to Rooms</LI>
<LI> + <a href="edit-tags.php?taxonomy=post_tag&post_type=<?php echo esc_attr( $options['custom_post'] ); ?>">Tags</a>: Content Tags also Apply to Rooms</LI>
<LI> + <a href="admin.php?page=live-webcams&tab=listings">Room List Style</a>: Template & CSS for AJAX Room List</LI>
<LI> + <a href="edit.php?post_type=<?php echo esc_attr( $options['custom_post'] ); ?>">Browse <?php echo ucwords( esc_html( $options['custom_post'] ) ); ?> Posts (Rooms)</a></LI>
</UL>
</p>

<h4>Role Name for Performer / Room Owner</h4>
<input name="rolePerformer" type="text" id="rolePerformer" size="20" maxlength="64" value="<?php echo esc_attr( $options['rolePerformer'] ); ?>"/>
<br>This is used as registration role option and access to performer (room owner) dashboard page (redirection on login). Administrators can also manually access dashboard page and setup/access webcam page for testing. Should be 1 role name slug, without special characters.
<br>Sample possible values: performer, expert, host, professional, teacher, trainer, tutor, provider, model, author, expert, artist, medium, moderator, owner . Default: <?php echo esc_html( $optionsDefault['rolePerformer'] ); ?>
<br>Use role name (slug) like "user_role", not name like "User Role".
<br>Performer role should be configured with other integrated plugins for necessary capabilities (like sharing videos, pictures).
<br> - Your roles (for troubleshooting):
				<?php
			global $current_user;
			foreach ( $current_user->roles as $role )
			{
				echo esc_html( $role ) . ' ';
			}
?>
			<br> - Current WordPress roles:
				<?php
			global $wp_roles;
			foreach ( $wp_roles->roles as $role_slug => $role )
			{
				echo esc_html( $role_slug ) . '= "' . esc_html( $role['name'] ) . '" ';
			}
?>
<br>Depending of project, performer role should also be configured/updated in settings for other plugins that configure permissions by role (<a href="admin.php?page=video-share&tab=share">Video Share VOD</a>, <a href="admin.php?page=picture-gallery&tab=share">Picture Gallery</a>, <a href="admin.php?page=paid-membership&tab=share">MicroPayments - Downloads</a>).
<br>Warning: Changing role name will allow only users with new role to access performer dashboard. New role is assigned to <a href="admin.php?page=live-webcams&tab=integration">new registrations if enabled</a>. <a href="users.php">Previously registered users</a> need to be assigned to new role manually. Additionally, shortcode role parameter need to be updated on the dedicated <a href="admin.php?page=live-webcams&tab=pages">registration pages</a>.

<h4>Extra Roles for Performers / Room Owners</h4>
<input name="rolePerformers" type="text" id="rolePerformers" size="40" maxlength="64" value="<?php echo esc_attr( $options['rolePerformers'] ); ?>"/>
<br>Other roles that can access performer dashboard to setup and manage rooms. Role names (slugs) as comma separated values.
<br>Roles administrator, super-admin are already included for testing purposes (don't need to be added to this list).  Default: <?php echo esc_html( $optionsDefault['rolePerformers'] ); ?>
<br>Roles that can manage rooms: <?php echo implode( ', ', self::getRolesPerformer( $options ) ); ?>

<h4>Performer Profile Link Prefix</h4>
<input name="performerProfile" type="text" id="performerProfile" size="80" maxlength="128" value="<?php echo esc_attr( $options['performerProfile'] ); ?>"/>
<br>Checked in performer links are available in webcam listings. Set blank to use BuddyPress profile or get_author_posts_url() without BuddyPress. Customize if there is profile page generated by a special plugin or theme feature.
<br>Ex: https://yoursite.com/author/ (if profile is https://yoursite.com/author/[user_nicename]
<BR>Your user_nicename based link (for troubleshooting):
				<?php
			echo esc_html( $options['performerProfile'] . esc_html( $current_user->user_nicename ) );
?>


<h4>Performer / Room Owner Settings & Management</h4>
<UL>
<LI> + <a href="admin.php?page=live-webcams&tab=performer">Performer</a>: Configure performer settings
<LI> + <a href="admin.php?page=live-webcams&tab=record">Account Administrative Fields / Questions</a>: Configure Account Administrative Records and Verification/Approval
<LI> + <a href="admin.php?page=live-webcams&tab=group">Chat Modes</a>: Performers can select room mode for webcam room when going live (in example Free Chat, Broadcast, Paid Group Show, Calls Only, Video Conference)
<LI> + <a href="admin.php?page=live-webcams&tab=studio">Studios</a>: If enabled, studios can manage multiple performer accounts and rooms
<LI> + <a href="admin.php?page=live-webcams&tab=features">Role Features</a>: Some features can be toggle by role (like enable custom cost per minute)
<LI> + <a href="users.php?role=<?php echo esc_attr( $options['rolePerformer'] ); ?>">View <?php echo ucwords( esc_html( $options['rolePerformer'] ) ); ?> User List</a>
<LI> + <a href="admin.php?page=live-webcams-records"><?php echo ucwords( esc_html( $options['rolePerformer'] ) ); ?> Approval</a>
</LI>
</UL>


<h4>Clients: Client Role Name</h4>
<input name="roleClient" type="text" id="roleClient" size="20" maxlength="64" value="<?php echo esc_attr( $options['roleClient'] ); ?>"/>
<br>This is used as registration role option.  Should be 1 role name slug, without special characters.
<br>Sample values: client, customer, student, member, subscriber, participant, user  Default: <?php echo esc_html( $optionsDefault['roleClient'] ); ?>
<br>Warning: New role is only assigned to <a href="admin.php?page=live-webcams&tab=integration">new registrations if enabled</a>. <a href="users.php">Previously registered users</a> need to be assigned to new role manually.

<h4>Client Settings & Management</h4>
<UL>
<LI> + <a href="admin.php?page=live-webcams&tab=client">Client</a>: Configure client settings and limits
<LI> + <a href="admin.php?page=live-webcams&tab=billing">Billing Options</a>: How Clients Pay for Site Services and Items</LI>
<LI> + <a href="admin.php?page=live-webcams&tab=tips">Tips / Gifts / Donations</a>: Configure tips/gifts/donations</LI>
<LI> + <a href="admin.php?page=live-webcams&tab=ppv">Pay Per Minute Settings</a>: Configure pay per minute, private calls</LI>
<LI> + <a href="users.php?role=<?php echo esc_attr( $options['roleClient'] ); ?>">View <?php echo ucwords( esc_html( $options['roleClient'] ) ); ?> User List</a>
</UL>

<h4>Client Profile Link Prefix</h4>
<input name="clientProfile" type="text" id="clientProfile" size="80" maxlength="128" value="<?php echo esc_attr( $options['clientProfile'] ); ?>"/>
<br>Leave blank to use BuddyPress profile or disable without BuddyPress. Customize if there is profile page generated by a special plugin or theme feature.
<br>Ex: https://yoursite.com/profile/ (if profile is https://yoursite.com/profile/[user_nicename]
<BR>Your user_nicename based link (for troubleshooting):
				<?php
			echo esc_html( $options['clientProfile'] . $current_user->user_nicename );
?>


<h4>Customize More</h4>
<UL>
<LI><a class="button secondary" href="https://paidvideochat.com/customize">Customization Options from Setup Tutorial</a></LI>
</UL>
				<?php

			break;
		case 'messages';
?>
<h3><?php _e( 'Paid Questions and Messages', 'ppv-live-webcams' ); ?></h3>
Client is charged when sending question and performer gets paid on reply. Performer earning ratio is applied on earnings, similar to pay per minute.

<h4>Enable Questions / Messages</h4>
<select name="messages" id="messages">
  <option value="0" <?php echo $options['messages'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['messages'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Clients can send questions/messages to rooms and performers can answer.
<br>Performer can toggle questions to Closed, per room.

<h4>Default Cost per Question</h4>

<input name="messagesCost" type="text" id="messagesCost" size="10" maxlength="16" value="<?php echo esc_attr( $options['messagesCost'] ); ?>"/>
<br>Can be 0 to allow free messages (not recommended as that can result in SPAM). Default: <?php echo esc_html( $optionsDefault['messagesCost'] ); ?>
<br>Peformer can set own cost, per room.

<h4>Enable Clients to Add Recordings</h4>
<select name="attachmentRecord" id="attachmentRecord">
  <option value="0" <?php echo $options['attachmentRecord'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['attachmentRecord'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Requires HTML5 Webcam Microphone Recorder Forms plugin.

<h4>Attachment Extensions Allowed</h4>
<textarea name="attachment_extensions" id="attachment_extensions" cols="100" rows="3"><?php echo esc_textarea( $options['attachment_extensions'] ); ?></textarea>
<br>Depending on server configuration, allowing frontend users to upload files can result in security risks. Do not allow script extensions like php, executables or other dangerous content types.
<br>Remove all to disable attachments.
<br>Default: <?php echo esc_html( $optionsDefault['attachment_extensions'] ); ?>

<h4>Attachment Size Limit</h4>
<input name="attachmentSize" type="text" id="attachmentSize" size="10" maxlength="16" value="<?php echo esc_attr( $options['attachmentSize'] ); ?>"/> bytes
<br>Default: <?php echo esc_html( $optionsDefault['attachmentSize'] ); ?>

<h4>Message Notification Subject</h4>
<input name="messagesSubject" type="text" id="messagesSubject" size="100" maxlength="256" value="<?php echo esc_attr( $options['messagesSubject'] ); ?>"/>[user]
<br>An notification email is sent when message is sent. Sender name is added at end. Default: <?php echo esc_html( $optionsDefault['messagesSubject'] ); ?>
<br>For SMS notifications link is added at end (without extra text).
<br>Notification is sent both to performer (on new question) and client (on reply).

<h4>Message Notification Text</h4>
<textarea name="messagesText" id="messagesText" cols="100" rows="3"><?php echo esc_textarea( $options['messagesText'] ); ?></textarea>[link]
<br>Link to site is added at end. Not included in SMS (only subject and link is sent).

<h4>Password Change Email Subject</h4>
<input name="passwordSubject" type="text" id="passwordSubject" size="100" maxlength="256" value="<?php echo esc_attr( $options['passwordSubject'] ); ?>"/>
<br>An activation email is sent when using solution registration method where user also chooses password on registration. Default: <?php echo esc_html( $optionsDefault['passwordSubject'] ); ?>

<h4>Password Change Email Text</h4>
<textarea name="passwordText" id="passwordText" cols="100" rows="3"><?php echo esc_textarea( $options['passwordText'] ); ?></textarea>

				<?php

			break;
		case 'sms';
?>
<h3><?php _e( 'SMS Notifications', 'ppv-live-webcams' ); ?></h3>
Integrate SMS notification. These are sent in addition to email notifications for some features implemented by PaidVideochat solution: questions/messages, frontend forgot password. Notifications need be configured and enabled for 1 SMS plugin (and gateway).

<h4>Collect SMS Mobile Number</h4>
<select name="sms_number" id="sms_number">
  <option value="0" <?php echo $options['sms_number'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['sms_number'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enable users to fill their SMS mobile number (required for SMS notifications). Users can update number in Client Dashboard, Performer Dashboard > Messages tab, backend Profile page.
<br>This field is used to send notifications for PaidVideochat features. Enabling other fields or notifications is not required in the SMS plugins - configure only settings for the SMS gateway.

<h4>Instructions</h4>
<input name="sms_instructions" type="text" id="sms_instructions" size="32" maxlength="128" value="<?php echo esc_attr( $options['sms_instructions'] ); ?>"/>
<br>Brief instructions for filling number.

<h4>SMS Cooldown</h4>
<input name="sms_cooldown" type="text" id="sms_cooldown" size="10" maxlength="16" value="<?php echo esc_attr( $options['sms_cooldown'] ); ?>"/> seconds
<br>Set a minimal cooldown for sending SMS to same user. Prevents generating high volume of SMS to same user, which can result in blacklisting and high costs. Default: <?php echo esc_html( $optionsDefault['sms_cooldown'] ); ?>

<h3>WP SMS Plugin for Wordpress (WP Twilio Core)</h3>
<a href="https://wordpress.org/plugins/wp-twilio-core/">WP Twilio Core</a> integrates Twilio API for sending SMS.
<br>twl_send_sms():
<?php
if (function_exists('twl_send_sms'))echo 'Detected. <a href="admin.php?page=twilio-options">Configure WP Twilio Core</a>';
else echo 'Not detected. <a href="plugin-install.php?s=wp%20twilio%20core&tab=search&type=term">Install</a> and activate WP SMS Plugin for Wordpress if you want to use it.'	
?>
<h4>Enable WP Twilio Core Notifications</h4>
<select name="wp_sms_twilio" id="wp_sms_twilio">
  <option value="0" <?php echo $options['wp_sms_twilio'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['wp_sms_twilio'] ? 'selected' : ''; ?>>Yes</option>
</select>

<h3>WP SMS</h3>
<a href="https://wordpress.org/plugins/wp-sms/">WP SMS  plugin</a> adds the ability to send SMS to your WordPress through more than <a href="https://wp-sms-pro.com/gateways/">200 SMS gateways</a>, such as Twilio, Plivo, Clickatell, BulkSMS, Infobip, Vonage (Nexmo), Clockworksms, Messagebird, Click send.
<br>wp_sms_send(): <?php
if (function_exists('wp_sms_send'))	echo 'Detected. <a href="admin.php?page=wp-sms-settings">Configure WP SMS</a>';
else echo 'Not detected. <a href="plugin-install.php?s=wp%20sms&tab=search&type=term">Install</a> and activate WP SMS plugin if you want to use it.'	
?>
<h4>Enable WP SMS Notifications</h4>
<select name="wp_sms" id="wp_sms">
  <option value="0" <?php echo $options['wp_sms'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['wp_sms'] ? 'selected' : ''; ?>>Yes</option>
</select>

<h3>Configure Notifications</h3>

<h4>Performer Notification on Private Requests</h4>
<select name="privateNotification" id="privateNotification">
  <option value="0" <?php echo $options['privateNotification'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['privateNotification'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Notify performer when user requests private from room listing menu (not when online in group chat as notification is shown directly in web app).

<h4>Private Notification Subject</h4>
<input name="privateSubject" type="text" id="privateSubject" size="100" maxlength="256" value="<?php echo esc_attr( $options['privateSubject'] ); ?>"/> [user]
<br>An notification is sent when a client requests a private from room list. Sender name is added at end. Default: <?php echo esc_html( $optionsDefault['privateSubject'] ); ?>
<br>For SMS notifications link is added at end (without extra text).

<h4>Private Notification Text</h4>
<textarea name="privateText" id="privateText" cols="100" rows="3"><?php echo esc_textarea( $options['privateText'] ); ?></textarea> [link]
<br>Link to site is added at end. Not included in SMS (only subject and link is sent).

<h4>Email Cooldown</h4>
<input name="email_cooldown" type="text" id="email_cooldown" size="10" maxlength="16" value="<?php echo esc_attr( $options['email_cooldown'] ); ?>"/> seconds
<br>Set a minimal cooldown for sending email to same user. Prevents generating high volume of emails and blacklisting due to rate limits. Default: <?php echo esc_html( $optionsDefault['email_cooldown'] ); ?>

<h4>Paid Question/Message Notification Subject</h4>
<input name="messagesSubject" type="text" id="messagesSubject" size="100" maxlength="256" value="<?php echo esc_attr( $options['messagesSubject'] ); ?>"/>[user]
<br>An notification email is sent when message is sent. Sender name is added at end. Default: <?php echo esc_html( $optionsDefault['messagesSubject'] ); ?>
<br>For SMS notifications link is added at end (without extra text).
<br>Notification is sent both to performer (on new question) and client (on reply).

<h4>Message Notification Text</h4>
<textarea name="messagesText" id="messagesText" cols="100" rows="3"><?php echo esc_textarea( $options['messagesText'] ); ?></textarea>[link]
<br>Link to site is added at end. Not included in SMS (only subject and link is sent).

<?php
		
		break;		
		case 'random';
?>


<h3><?php _e( 'Random Video Chat', 'ppv-live-webcams' ); ?></h3>
<a href="https://paidvideochat.com/random-videochat-match/">Radom Videochat</a> is available in 2 variants for clients: surfing group chat rooms and matchmaking with performers directly in private shows.


<h4>Next Button</h4>
<select name="videochatNext" id="videochatNext">
  <option value="0" <?php echo $options['videochatNext'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['videochatNext'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Shows in group chat and enables clients to quickly move to a different performer room (without leaving chat interface).
<BR>Next room is selected from rooms recently active online, by picking a room user did not enter or entered longest time ago (for rotation). Will not select rooms where performer is in private show.
<br>A random videochat room can also be rendered with [videowhisper_cam_random] shortcode, that displays a random room based on these settings.

<h4>Paid Rooms on Next</h4>
<select name="videochatNextPaid" id="videochatNextPaid">
  <option value="0" <?php echo $options['videochatNextPaid'] ? '' : 'selected'; ?>>Free</option>
  <option value="1" <?php echo $options['videochatNextPaid'] ? 'selected' : ''; ?>>Any</option>
  <option value="2" <?php echo $options['videochatNextPaid'] == '2' ? 'selected' : ''; ?>>Only Paid</option>
</select>
<br>Visitors always get free rooms. When users enter paid rooms, welcome message will contain details including group cost per minute and grace time. User is not charged if moving to next room or closing before grace time ends. Also paid room welcome message has a special icon showing payment. Default: Any
<br><IMG WIDTH="48px" SRC="<?php echo esc_attr( dirname( plugin_dir_url( __FILE__ ) ) ) . '/images/cash.png'; ?>">

<h4>Online Rooms on Next</h4>
<select name="videochatNextOnline" id="videochatNextOnline">
  <option value="0" <?php echo $options['videochatNextOnline'] ? '' : 'selected'; ?>>Any</option>
  <option value="1" <?php echo $options['videochatNextOnline'] ? 'selected' : ''; ?>>Only Online</option>
</select>
<BR>On new sites with few online performers this should not be enabled as users will not find many online rooms. Default: Any

<h3>Random Matchmaking</h3>
Performers can go live in 2 way video call mode for random matchmaking with clients. Matching criteria can be defined both for rooms and clients.

<h4>Matchmaking</h4>
<select name="match" id="match">
  <option value="0" <?php echo $options['match'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['match'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<br>Enables users and performers to setup matchmaking criteria. Matching fields are configured from room profile fields by marking with a 'match' parameter, for selects and checkboxes. Matchmaking criteria applies per user for clients (from Match Criteria page) and per room for performers (from Performer Dashboard).

<h4>Advanced Matchmaking</h4>
<select name="matchAdvanced" id="matchAdvanced">
  <option value="0" <?php echo $options['matchAdvanced'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['matchAdvanced'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<br>When disabled (recommended), simple matchmaking will only use mirror fields (with mirror options like Man Looking for Woman|Woman Looking for Man).
<br>Complex matchmaking criteria (with 2 way matchmaking and multiple fields) can result in very slow matchmaking queries, slow response and even errors (when queries and requests timeout).
			<?php
			submit_button( __( 'Save Settings', 'ppv-live-webcams' ) );
?>
<h4>How to Setup & Use Matchmaking</h4>
-  If not available, setup matchmaking criteria fields from <a href="admin.php?page=live-webcams&tab=profile">Room Profile</a> settings, preferably 1 match=mirror field. Ex:
<small><pre>
[Quick Match]
match=mirror
type=select
options=Man looking for Woman/Woman looking for Man
default=Man looking for Woman
hideLabel=true
</pre></small>

- Setup <a href="admin.php?page=live-webcams&tab=pages">Frontend Pages</a> to include Match Criteria and Random Match pages, needed by clients to update matchmaking critera and access performers available for matchmaking. These will be accessible from Client Dashboard for clients. Can be rendered with these shortcodes: [videowhisper_match_form] [videowhisper_match] .
<br>

<br>- Performers fill criteria per room from Performer Dashboard > Match tab and Go Live for matchmaking using a special Random Matchmaking mode to become available for matchmaking in private 2 way videocall booths instead of their regular group chat room.
If not available, setup a Random Matchmaking mode with room_random=true for performers from group <a href="admin.php?page=live-webcams&tab=group">Chat Modes</a> settings. Ex:
<small><pre>
[Random Matchmaking]
description="Random 2 way call matchmaking. Define match criteria from performer dashboard."
room_random=true
room_private=true
room_audio=false
room_text=false
</pre></small>
 
<?php

			break;

		case 'pages';

			/*
				'videowhisper_webcams' => __('Webcams', 'ppv-live-webcams'),
				'videowhisper_webcams_performer' => __('Performer Dashboard', 'ppv-live-webcams'),
				'videowhisper_webcams_studio' => __('Studio Dashboard', 'ppv-live-webcams'),
				'videowhisper_webcams_logout' => __('Chat Logout', 'ppv-live-webcams'),
				'videowhisper_cam_random' =>  __('Random Cam', 'ppv-live-webcams'),
				'videowhisper_webcams_client' => __('Client Dashboard', 'ppv-live-webcams'),
				*/
?>
<h3><?php _e( 'Setup Pages', 'ppv-live-webcams' ); ?></h3>

				<?php
			if ( $_POST['submit'] ?? false)
			{
				echo '<p>Saving pages setup.</p>';
				self::setupPages();
				$options = self::getAdminOptions();
			}

			submit_button( __( 'Update Pages', 'ppv-live-webcams' ) );
?>
Use this to setup pages on your site, customize menus for different role types. Pages with main feature shortcodes are required to access main functionality. After setting up these pages you should add the feature pages to site menus for users to access.
A sample VideoWhisper menu will also be added when adding pages: can be configured to show in a menu section depending on theme.
<br>You can manage these anytime from backend: <a href="edit.php?post_type=page">pages</a> and <a href="nav-menus.php">menus</a>.
<BR><?php echo wp_kses_post( self::requirementRender( 'setup_pages' ) ); ?>

<h4>Setup Pages</h4>
<select name="disableSetupPages" id="disableSetupPages">
  <option value="0" <?php echo $options['disableSetupPages'] ? '' : 'selected'; ?>>Yes</option>
  <option value="1" <?php echo $options['disableSetupPages'] ? 'selected' : ''; ?>>No</option>
</select>
<br>Create pages for main functionality. Also creates a menu with these pages (VideoWhisper) that can be added to themes.
<br>After login performers are redirected to the dashboard page and clients to webcams page.

<h4>Manage Balance Page</h4>
<select name="balancePage" id="balancePage">
<option value='0'>Undefined: Reset</option>
<option value='-1'
				<?php
			if ( $options['balancePage'] == -1 )
			{
				echo 'selected';}
?>
	>None</option>
				<?php

			$args   = array(
				'sort_order'   => 'asc',
				'sort_column'  => 'post_title',
				'hierarchical' => 1,
				'post_type'    => 'page',
			);
			$sPages = get_pages( $args );

			foreach ( $sPages as $sPage )
			{
				echo '<option value="' . esc_attr( $sPage->ID ) . '" ' . ( $options['balancePage'] == ( $sPage->ID ) || ( $options['balancePage'] == 0 && ( $sPage->post_title == 'My Wallet' || $sPage->post_title == 'Client Dashboard' ) ) ? 'selected' : '' ) . '>' . esc_html( $sPage->post_title ) . '</option>' . "\r\n";
			}
?>
</select>
<br>Page linked from balance section, usually a page where registered users can buy credits. Recommended: My Wallet (created by <a href="https://wordpress.org/plugins/paid-membership/">MicroPayments</a> VideoWhisper Plugin that manages wallets and paid content as WooCommerce products)


<h3>Feature Pages</h3>
These pages are required for specific turnkey site solution functionality. If you edit pages with shortcodes to add extra content, make sure shortcodes remain present.
				<?php

			$pages   = self::setupPagesList();
			$content = self::setupPagesContent();

			foreach ( $pages as $shortcode => $title )
			{
				$pid = sanitize_text_field( $options[ 'p_' . $shortcode ] ?? 0 );
				if ( $pid != '' )
				{

					echo '<h4>' . esc_html( $title ) . '</h4>';
					echo '<select name="p_' . esc_attr( $shortcode ) . '" id="p_' . esc_attr( $shortcode ) . '">';
					echo '<option value="0">Undefined: Reset</option>';
					foreach ( $sPages as $sPage )
					{
						echo '<option value="' . esc_attr( $sPage->ID ) . '" ' . ( ( $pid == $sPage->ID ) ? 'selected' : '' ) . '>' . esc_html( $sPage->ID ) . '. ' . esc_html( $sPage->post_title ) . ' - ' . esc_html( $sPage->post_status ) . '</option>' . "\r\n";
					}
					echo '</select><br>';
					if ( $pid )
					{
						echo '<a href="' . get_permalink( $pid ) . '">view</a> | ';
					}
					if ( $pid )
					{
						echo '<a href="post.php?post=' . esc_attr( $pid ) . '&action=edit">edit</a> | ';
					}
					echo 'Default content: ' . ( array_key_exists( $shortcode, $content ) ? esc_html( $content[ $shortcode ] ) : esc_html( "[$shortcode]" ) ) . '';

				}
			}

			echo '<h3>PaidVideochat Frontend Feature Pages</h3>';

			$noMenu = array( 'videowhisper_webcams_logout', 'mycred_buy_form', 'videowhisper_register_activate', 'videowhisper_password_form' );

			foreach ( $pages as $shortcode => $title )
			{
				if ( ! in_array( $shortcode, $noMenu ) )
				{
					$pid = sanitize_text_field( $options[ 'p_' . $shortcode ] ?? 0 );
					if ( $pid )
					{
						$url = get_permalink( $pid );
						echo '<p> - ' . esc_html( $title ) . ':<br>';
						echo '<a href="' . esc_attr( $url ) . '">' . esc_html( $url ) . '</a></p>';

					}
				}
			}

?>
<h3>Menus</h3>
Select special menus to show in theme for main roles (instead of default menus shown to visitors and other roles).
<BR>Menus can be selected from <a href="nav-menus.php">available menus</a>.
<BR>Available menu locations depend on theme (each theme implements own menu locations). Current theme provides these menu locations:
				<?php
			$nav_menus       = wp_get_nav_menus();
			$theme_locations = get_nav_menu_locations();
			// var_dump($nav_menus);

if (is_array($theme_locations) && count($theme_locations))
			foreach ( $theme_locations as $location => $selected ) echo esc_html( $location ) . ' ';
			
			foreach ( array( 'client', 'performer', 'studio', 'member' ) as $menu_role )
			{
				echo '<h4>Menus for ' . esc_html( ucwords( $menu_role ) ) . '</h4>';
				switch ( $menu_role )
				{
				case 'performer':
					echo 'Performer roles (<a href="admin.php?page=live-webcams&tab=performer">configured</a>): ' . esc_html ( implode( ', ', self::getRolesPerformer( $options ) ) );
					break;

				case 'client':
					echo 'Client role (<a href="admin.php?page=live-webcams&tab=client">configured</a>): ' . esc_html( $options['roleClient'] );
					break;

				case 'studio':
					echo 'Studio role (<a href="admin.php?page=live-webcams&tab=studio">configured</a>): ' . esc_html( $options['roleStudio'] );
					break;

				case 'member':
					echo 'Other logged in member roles.';
					break;
				}

				if (is_array($theme_locations) && count($theme_locations))
					foreach ( $theme_locations as $location => $selected )
					{
						echo '<h4>' . esc_html( $location ) . '</h4>';
						echo 'Default Menu: ' . ($selected && array_key_exists($selected, $nav_menus) ? esc_html( $nav_menus[$selected]->name ) : 'none') . '<br>';
						$var = 'menu_' . esc_html( $menu_role ) . '_' . sanitize_file_name( $location );

						$value = '';
						if ( array_key_exists( $var, $options ) )
						{
							$value = $options[ $var ];
						}

						echo '<select name="' . esc_attr( $var ) . '" id="' . esc_attr( $var ) . '">';
						echo '<option value="">Default</option>';
						foreach ( $nav_menus as $menu )
						{
							echo '<option value="' . esc_attr( $menu->term_id ) . '" ' . ( $value == $menu->term_id ? 'selected' : '' ) . '>' . esc_html( $menu->name ) . '</option>';
						}
						echo '</select>';
					}
				else echo 'Your theme does not define any menu locations to assign menus. To use this functionality, use a theme that implements <a href="https://developer.wordpress.org/themes/functionality/navigation-menus/">menu locations</a>.' ;
			}

			break;

		case 'setup':
?>
<h3><?php _e( 'Setup Overview', 'ppv-live-webcams' ); ?></h3>


 1. Requirements: Before setting up, make sure you have necessary hosting requirements, for HTML5 live video streaming. This plugin has <a href="https://videowhisper.com/?p=Requirements" title="Live Streaming Requirements" target="_requirements">requirements</a> beyond regular WordPress hosting specifications and needs specific HTML5 live streaming services and video tools. Skip requirements review if you have <a href="https://webrtchost.com/hosting-plans/">a turnkey live streaming hosting plan</a> from VideoWhisper as it provides all features.
<br> 2. Existing active site? This plugin is designed to setup a turnkey live streaming site, changing major WP blog features. Set it up on a development environment as it can alter functionality of existing sites. To be able to revert changes, before setting up, make a recovery backup using hosting control panel or other backup tool/plugin. You can skip backups if this is a new site.
<br> 3. Setup: To setup this plugin start from <a href="admin.php?page=live-webcams-doc">Backend Documentation</a>, check project page <a href="https://paidvideochat.com/features/quick-setup-tutorial/" target="_documentation">PaidVideoChat Setup Tutorial</a> for more details and then review requirements checkpoints list on this page.
<br>If not sure about how to proceed or need clarifications, <a href="https://consult.videowhisper.com?topic=Install+PaidVideochat+Plugin">contact plugin developers</a>.

<p><a class="button secondary" href="admin.php?page=live-webcams-doc">Backend Setup Tutorial</a></p>

<h3><?php _e( 'Setup Checkpoints', 'ppv-live-webcams' ); ?></h3>

This section lists main requirements and checkpoints for setting up and using this solution.
				<?php

			// handle item skips
			$unskip = sanitize_file_name( $_GET['unskip'] ?? '' );
			if ( $unskip )
			{
				self::requirementUpdate( $unskip, 0, 'skip' );
			}

			$skip = sanitize_file_name( $_GET['skip'] ?? '' );
			if ( $skip )
			{
				self::requirementUpdate( $skip, 1, 'skip' ?? '');
			}

			$check = sanitize_file_name( $_GET['check'] ?? '');
			if ( $check )
			{
				self::requirementUpdate( $check, 0 );
			}

			$done = sanitize_file_name( $_GET['done'] ?? '');
			if ( $done )
			{
				self::requirementUpdate( $done, 1 );
			}

			// accessed setup page: easy
			self::requirementMet( 'setup' );

			// list requirements
			$requirements = self::requirementsGet();

			$rDone = 0;

			$htmlDone = '';
			$htmlPending = '';
			$htmlSkip = '';
			
			
			foreach ( $requirements as $label => $requirement )
			{
				$html = self::requirementRender( $label, 'overview', $requirement );

				$status = self::requirementStatus( $requirement );
				$skip   = self::requirementStatus( $requirement, 'skip' );

				if ( $status )
				{
					$htmlDone .= $html;
					$rDone++;
				} elseif ( $skip )
				{
					$htmlSkip .= $html;
				} else
				{
					$htmlPending .= $html;
				}
			}

			if ( $htmlPending )
			{
				echo '<h4>To Do:</h4>' . wp_kses_post( $htmlPending );
			}
			if ( $htmlSkip )
			{
				echo '<h4>Skipped:</h4>' . wp_kses_post( $htmlSkip );
			}
			if ( $htmlDone )
			{
				echo '<h4>Done (' . esc_html( $rDone ) . '):</h4>' . wp_kses_post( $htmlDone );
			}
?>
* These requirements are updated with checks and checkpoints from certain pages, sections, scripts. Certain requirements may take longer to update (in example session control updates when there are live streams and streaming server calls the web server to notify). When plugin upgrades include more checks to assist in reviewing setup, these will initially show as required until checkpoint.
				<?php
			// var_dump($requirements);
			break;

		case 'app':
			$options['appSetupConfig'] = stripslashes( $options['appSetupConfig'] );
			$options['appCSS']         = stripslashes( $options['appCSS'] );
			$options['appRolesConfig'] = stripslashes( $options['appRolesConfig'] ) ;

?>
<h3>Apps</h3>
This section configures HTML5 Videochat app and external access (by external apps) using same API. Required when building external apps to work with solution.
<br>For live streaming features, HTML5 Videochat app requires Wowza SE as relay or P2P using VideoWhisper WebRTC signaling configured for secure WebRTC live streaming:  <a href="admin.php?page=live-webcams&tab=webrtc">Configure HTML5 WebRTC</A>.


<h4>Logo URL</h4>
<input type="text" name="appLogo" id="appLogo" value="<?php echo esc_attr( trim( $options['appLogo'] ) ); ?>" size="120" />
<BR>URL to logo image to be displayed in app, floating over videos. Set blank to remove. It's a HTML element that can be styled with CSS for class videowhisperAppLogo.
<?php
if ( $options['appLogo'] )
{	
?>
<BR><img src="<?php echo esc_attr( $options['appLogo'] ); ?>" style="max-width:100px;" />
<?php
}
?>

<h4>App Configuration</h4>
<textarea name="appSetupConfig" id="appSetupConfig" cols="120" rows="12"><?php echo esc_textarea( $options['appSetupConfig'] ); ?></textarea>
<BR>Application setup parameters are delivered to app when connecting to server. Config section refers to application parameters. Room section refers to default room options (configurable from app at runtime). User section refers to default room options configurable from app at runtime and setup on access.
<br>Bitrate limitations also affect maximum resolution: app will hide resolutions if necessary bitrate is not available. In addition to limitation set in app, the <a href="admin.php?page=live-webcams&tab=webrtc">general host bitrate limitations</a> also apply.

Default:<br><textarea readonly cols="120" rows="6"><?php echo esc_textarea( $optionsDefault['appSetupConfig'] ); ?></textarea>

<BR>Parsed configuration (should be an array or arrays) JSON:<BR>
				<?php

			echo esc_html( json_encode( $options['appSetup'] ) );
?>
<BR>Serialized:<BR>
				<?php

			echo esc_html( serialize( $options['appSetup'] ) );
?>

<h4>Reset Room & User Options</h4>
<select name="appOptionsReset" id="appOptionsReset">
	<option value="0" <?php echo ! $options['appOptionsReset'] ? 'selected' : ''; ?>>No</option>
	<option value="1" <?php echo $options['appOptionsReset'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Resets room options on each performer session start and user options when entering application, forcing defaults.
Disable to allow options configured at runtime to persist.


<h4>Show Options</h4>
<select name="appOptions" id="appOptions">
	<option value="0" <?php echo ! $options['appOptions'] ? 'selected' : ''; ?>>No</option>
	<option value="1" <?php echo $options['appOptions'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Show Options tab in Advanced interface, for users to edit owned room options and user preferences live.


<h4>Private Chat 2 Way</h4>
<select name="private2Way" id="private2Way">
	<option value="0" <?php echo ! $options['private2Way'] ? 'selected' : ''; ?>>Disabled</option>
	<option value="1" <?php echo $options['private2Way'] == '1' ? 'selected' : ''; ?>>Enabled</option>
</select>
<br>2 Way means both participants can see each other. Disable if you want broadcasting interface removed for client. Supported for video and audio modes.

<h4>Minimalist on Mobile</h4>
<select name="appMobileMinimalist" id="appMobileMinimalist">
	<option value="0" <?php echo ! $options['appMobileMinimalist'] ? 'selected' : ''; ?>>No</option>
	<option value="1" <?php echo $options['appMobileMinimalist'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Force minimalist mode on mobile, for simplified interface.

<h4>App Interface Complexity</h4>
<select name="appComplexity" id="appComplexity">
	<option value="0" <?php echo ! $options['appComplexity'] ? 'selected' : ''; ?>>Simple</option>
	<option value="1" <?php echo $options['appComplexity'] == '1' ? 'selected' : ''; ?>>Advanced</option>
	<option value="2" <?php echo $options['appComplexity'] == '2' ? 'selected' : ''; ?>>Advanced for Room Owner</option>
</select>
<br>Simple interface shows minimal panels (webrtc video, text chat, actions).
<br>-Audio Only mode involves special layouts for Broadcast, Playback, 2 Way interfaces: Chat uses most space and audio controls for broadcast/playback minimized in a bar.
<br>-Audio Only mode is only available in simple interfaces.
<br>Advanced shows tabs with users list, options, files, presentation, HLS.
<br>-Broadcaster has both camera tab and playback (preview) from server in advanced mode, unless in Text only mode.
<br>-Collaboration & Conference modes are always in advanced interface.
<br>-Text Only mode is available both for Simple and Advanced interface.
<br>-Advanced for Room Owner will give owner ability to switch room live to Conference / Collaboration mode (and get everybody from Simple to Advanced interface).
<br>Advanced features like external OBS broadcast, ReStreams require advanced interface to switch between Webcam other source types tabs played as HLS.

<h4>App Roles Configuration</h4>
<textarea name="appRolesConfig" id="appRolesConfig" cols="120" rows="5"><?php echo esc_textarea( $options['appRolesConfig'] ); ?></textarea>
<BR>Certain parameters can be configured per role, depending on usage scenario. Special values for "roles": ALL, MEMBERS, NONE.
<BR>Current WordPress roles:
				<?php
			global $wp_roles;
			foreach ( $wp_roles->roles as $role_slug => $role )
			{
				echo esc_html( $role_slug ) . '= "' . esc_html( $role['name'] ) . '" ';
			}
?>
Current user roles:
				<?php
			$current_user = wp_get_current_user();
			foreach ( $current_user->roles as $role )
			{
				echo esc_html( $role ) . ' ';
			}
?>
Default configuration:<br><textarea readonly cols="120" rows="3"><?php echo esc_textarea( $optionsDefault['appRolesConfig'] ); ?></textarea>

<BR>Parsed configuration (should be an array or arrays) JSON:<BR>
				<?php

			esc_html( json_encode( $options['appRoles'] ) );
?>
<BR>Serialized:<BR>
				<?php

			echo esc_html( serialize( $options['appRoles'] ) );
?>
<BR>Test for current user:<BR>
				<?php

			$userID = get_current_user_id();
			echo esc_html( '#' . $userID . ' ' );
			if ( array_key_exists( 'appRoles', $options ) )
			{
				foreach ( $options['appRoles'] as $parameter => $values )
				{
					echo esc_html( $parameter ) . ': ' . esc_html( self::appRole( $userID, $parameter, '-', $options ) ) . ', ';
				}
			}
?>

<h4>Textchat History</h4>
<input name="chatlog" type="text" id="chatlog" size="10" maxlength="256" value="<?php echo esc_attr( $options['chatlog'] ); ?>"/>s
<br>Time to keep chatlog messages in database, in seconds (i.e. 900s for 15 minutes). Messages are also saved in chat log files. Default: <?php echo esc_attr( $optionsDefault['chatlog'] ); ?>

<h4>Previous Textchat</h4>
<select name="chatlogPrevious" id="chatlogPrevious">
	<option value="0" <?php echo ! $options['chatlogPrevious'] ? 'selected' : ''; ?>>No</option>
	<option value="1" <?php echo $options['chatlogPrevious'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select>
<br>When enabled, users also see text chat messages previous to their entry in chat room, as available in database. Default: No

<h4>Chatlog File Cleanup</h4>
<input name="chatlogCleanup" type="text" id="chatlogCleanup" size="10" maxlength="10" value="<?php echo esc_attr( $options['chatlogCleanup'] ); ?>"/>days
<br>Time to keep chatlog files in chatlogs folder, in days. Default: <?php echo esc_attr( $optionsDefault['chatlogCleanup'] ); ?>

<h4>Visitor Update Interval</h4>

<input name="timeIntervalVisitor" type="text" id="timeIntervalVisitor" size="10" maxlength="256" value="<?php echo esc_attr( $options['timeIntervalVisitor'] ); ?>"/>ms
<br>Time between update web requests for visitors, in milliseconds. That's the time between updates (like chat), unless user does something (when user send a message an update also occurs). To reduce load on web server from visitors, increase interval between update web requests. Ex: <?php echo esc_attr( $optionsDefault['timeIntervalVisitor'] ); ?>

<h4>Save All Snapshots</h4>
<select name="saveSnapshots" id="saveSnapshots">
	<option value="0" <?php echo ! $options['saveSnapshots'] ? 'selected' : ''; ?>>No</option>
	<option value="1" <?php echo $options['saveSnapshots'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select>
<br>When enabled each camera snapshot will be saved with own timestamp, otherwise it will be overwritten. This can be used to save space or keep all snapshots for logging purposes.

<h4>Private Call Snapshots</h4>
<select name="privateSnapshots" id="privateSnapshots">
	<option value="0" <?php echo ! $options['privateSnapshots'] ? 'selected' : ''; ?>>No</option>
	<option value="1" <?php echo $options['privateSnapshots'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Private call snapshots can be generated for logging purposes, not used for listings.

<h4>Wallet Page</h4>
<select name="balancePage" id="balancePage">
<option value='-1'
				<?php
			if ( $options['balancePage'] == -1 )
			{
				echo 'selected';}
?>
	>None</option>
				<?php

			$args   = array(
				'sort_order'   => 'asc',
				'sort_column'  => 'post_title',
				'hierarchical' => 1,
				'post_type'    => 'page',
				'post_status'  => 'publish',
			);
			$sPages = get_pages( $args );
			foreach ( $sPages as $sPage )
			{
				echo '<option value="' . esc_attr( $sPage->ID ) . '" ' . ( $options['balancePage'] == ( $sPage->ID ) || ( $options['balancePage'] == 0 && $sPage->post_title == 'My Wallet' ) ? 'selected' : '' ) . '>' . esc_html( $sPage->post_title ) . '</option>' . "\r\n";
			}
?>
</select>
<br>Page linked from balance section, usually a page where registered users can buy credits. Recommended: My Wallet (setup with <a href="https://wordpress.org/plugins/paid-membership/">Paid Membership & Content</a> plugin).


<h4>Content Types for Fullpage Videochat Template</h4>
<input name="templateTypes" type="text" id="templateTypes" size="100" maxlength="250" value="<?php echo esc_attr( $options['templateTypes'] ); ?>"/>
<BR>Comma separated content/post types, to show videochat template option. Ex: page, post, video, picture, download, webcam
<BR>A special metabox will show up when editing these content types from backend, to enable the videochat template.
<BR>Use this to load videochat in full page template on that page. Page contents must include the [videowhisper_videochat] shortcode (see documentation).

<h4>Site Menu in App</h4>
<select name="appSiteMenu" id="appSiteMenu">
	<option value="0" <?php echo ! $options['appSiteMenu'] ? 'selected' : ''; ?>>None</option>
				<?php
			$menus = get_terms( 'nav_menu', array( 'hide_empty' => true ) );

			foreach ( $menus as $menu )
			{
				echo '<option value="' . esc_attr( $menu->term_id ) . '" ' . ( $options['appSiteMenu'] == ( $menu->term_id ) || ( $options['appSiteMenu'] == -1 && $menu->name == 'VideoWhisper' ) ? 'selected' : '' ) . '>' . esc_html( $menu->name ) . '</option>' . "\r\n";
			}

?>
</select>
<br>A site menu is useful for chat users to access site features, especially when running app in full page. Warning: Broken menu data can cause errors in videochat application.


<h4>App CSS</h4>
<textarea name="appCSS" id="appCSS" cols="100" rows="6"><?php echo esc_textarea( $options['appCSS'] ); ?></textarea>
<br>
CSS code to adjust or fix application styling if altered by site theme. Multiple interface elements are implemented by <a href="https://fomantic-ui.com">Fomantic UI</a> (a fork of <a href="https://semantic-ui.com">Semantic UI</a>). Editing interface and layout usually involves advanced CSS skills. For reference also see <a href="https://paidvideochat.com/html5-videochat/css/">Layout CSS</a>. Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['appCSS'] ); ?></textarea>


<h4>Mode</h4>
<input name="modeVersion" type="text" id="modeVersion" size="80" maxlength="25" value="<?php echo esc_attr( $options['modeVersion'] ); ?>"/>
<br>Unless configured otherwise, application runs in demo mode (with some limitations and notices), for testing by site visitors without consuming lots of server resources. If you want to disable demo mode, confirm by filling application version. <a href="https://consult.videowhisper.com">Contact</a> if you need assistance. 

<h4>Remove Author Attribution Notices (Ask for Permission)</h4>
<select name="whitelabel" id="whitelabel">
	<option value="0" <?php echo ! $options['whitelabel'] ? 'selected' : ''; ?>>Disabled</option>
	<option value="1" <?php echo $options['whitelabel'] == '1' ? 'selected' : ''; ?>>Enabled</option>
</select>
<br>Embedded HTML5 Videochat application is branded with subtle attribution references to authors, similar to most software solutions in the world. Removing the default author attributions can be permitted, on request. <a href="https://consult.videowhisper.com">Contact</a> if you need assistance. 

<h4>CORS Access-Control-Allow-Origin</h4>

<input name="corsACLO" type="text" id="corsACLO" size="80" maxlength="256" value="<?php echo esc_attr( $options['corsACLO'] ); ?>"/>
<br>Enable external web access from these domains (CSV). Ex: http://localhost:3000


<h4>More Documentation</h4>
 - <a href="https://videochat-scripts.com/troubleshoot-html5-and-webrtc-streaming-in-videowhisper/">Troubleshoot HTML5 Streaming</a>: Tutorials, suggestions for troubleshooting streaming reliability and quality
<br> - <a href="https://paidvideochat.com/html5-videochat/">HTML5 Videochat Page</a>: Application features and product page
<br> - <a href="https://paidvideochat.com/html5-videochat/css/">HTML5 Videochat Layout CSS</a>
<br> - <a href="https://fomantic-ui.com">Fomantic UI</a>: Review interface element names for applying CSS
<br> - <a href="https://react.semantic-ui.com">Semantic UI React</a>: Review interface element names for applying CSS


				<?php
			break;

		case 'geofencing':
?>
<h4>GeoFencing</h4>
Block access to site content depending on location: globally for entire site (by admin) or per webcam listing (by performer).

<h4>GeoIP</h4>
GeoIP is required for admins and performers to ban certain countries, regions from accessing site or certain listings. This uses IP databases for location mapping without need for user to allow location detection in browser.
<BR><BR>

				<?php
			$clientIP = self::get_ip_address();
			echo __( 'Client IP', 'ppv-live-webcams' ) . ': ' . esc_html( $clientIP );

			if ( self::detectLocation() === false )
			{
				echo '<br>' . __( 'ERROR: Can not detect location. GeoIP extension is required on host for this functionality.', 'ppv-live-webcams' );
			} else
			{
				echo '<br><u>Detected:</u> GeoIP location seems to work. Detected location (precision depends on IP):';
				if ( $options['geoIP'] >= 1) echo '<br>' . __( 'Continent', 'ppv-live-webcams' ) . ': ' . esc_html( self::detectLocation( 'continent', $clientIP ) );
				if ( $options['geoIP'] >= 2) echo '<br>' . __( 'Country', 'ppv-live-webcams' ) . ': ' . esc_html( self::detectLocation( 'country', $clientIP ) );
				if ( $options['geoIP'] >= 3) echo '<br>' . __( 'Region', 'ppv-live-webcams' ) . ': ' . esc_html( self::detectLocation( 'region', $clientIP ) );
				if ( $options['geoIP'] >= 4) echo '<br>' . __( 'City', 'ppv-live-webcams' ) . ': ' . esc_html( self::detectLocation( 'city', $clientIP ) );

			}
?>

<h4>GeoIP Maximum Resolution</h4>
<select name="geoIP" id="geoIP">
	<option value="1" <?php echo $options['geoIP'] == '1' ? 'selected' : ''; ?>>Continent</option>
	<option value="2" <?php echo $options['geoIP'] == '2' ? 'selected' : ''; ?>>Country</option>
	<option value="3" <?php echo $options['geoIP'] == '3' ? 'selected' : ''; ?>>Region</option>
	<option value="4" <?php echo $options['geoIP'] == '4' ? 'selected' : ''; ?>>City</option>
	<option value="0" <?php echo $options['geoIP'] == '0' ? 'selected' : ''; ?>>GeoIP Disabled</option>	
</select>
<br>Recommended: Country. High resolution (City) is imprecise and may also cause performance issues if listing filtering is enabled.

<h4>Global Geo Blocking</h4>
<textarea name="geoBlocking" id="geoBlocking" cols="100" rows="3"><?php echo esc_textarea( $options['geoBlocking'] ); ?></textarea>
<br>Comma separated continents, countries, regions, cities. Sample Geo Blocking:<br>
<textarea readonly cols="100" rows="2">Guyana, Bangladesh, India, South Korea, Saudi Arabia, Botswana, Nigeria, Sudan, Egypt, Afghanistan, Pakistan, Turkmenistan, Burma, Iran, Iraq, Jordan, Syria, Kuwait, Yemen, Bahrain, Oman, Qatar, Saudi Arabia</textarea>

<h4>Global Geo Blocking Message</h4>
				<?php
			$options['geoBlockingMessage'] = stripslashes( $options['geoBlockingMessage'] );
			wp_editor( $options['geoBlockingMessage'], 'geoBlockingMessage', $settings = array( 'textarea_rows' => 3, 'media_buttons' => false ) );
?>

<p>Warning: GeoIP does not provide 100% accuracy. Also, certain users may use VPN or proxy services to access trough other locations (requires some technical skills and resources). Interdictions should also be enforced by site terms, additional verifications and processing of reports. </p>



<h4>Disable Location Bans in Listing Results</h4>
<select name="listingsDisableLocation" id="listingsDisableLocation">
  <option value="1" <?php echo $options['listingsDisableLocation'] == '1' ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['listingsDisableLocation'] == '0' ? 'selected' : ''; ?>>No</option>
</select>
<br>If disabled, listings will show to users from locations banned by performer. If enabled, rooms are hidden if performers banned access.
<br>Disabling location filtering improves query performance on databases with many metas. On databases with many metas this type of query can be very slow or even timeout.
<br>Recommended: Yes. Although listing will show to avoid performance issues, banned locations will not be able to access room page.

				<?php
			break;

		case 'requirements':
?>
<h3>Requirements and Troubleshooting</h3>
To be able to run this solution, make sure your have all  requirements for features you need. 

<BR>Recommended hosting that meets all requirements:
<br><a class="button" href="https://webrtchost.com/hosting-plans/" target="_vwhost">Compatible Hosting Plans</a>
				<?php

			echo '<h4>Web Host</h4>';
			echo 'Web Name: ' . esc_html( $_SERVER['SERVER_NAME'] ?? '-' );
			echo '<br>Web IP: ' . esc_html( $_SERVER['SERVER_ADDR'] ?? '-' );
			echo '<br>Web Port: ' . esc_html( $_SERVER['SERVER_PORT'] ?? '-');
			echo '<br>Web Protocol: ' . esc_html( $_SERVER['SERVER_PROTOCOL'] ?? '-');
			echo '<br>Site Path: ' . esc_html( $_SERVER['DOCUMENT_ROOT'] ?? '-');
			echo '<br>Server Hostname: ' . esc_html( gethostname() );
			if (function_exists('php_uname')) {
				echo '<br>Server OS: ' . esc_html(php_uname());
			} else {
				echo '<br>Server OS: ' . esc_html(PHP_OS ?? '-') . ' (Function php_uname is not available.)';
			}
			echo '<br>Web Server: ' . esc_html( $_SERVER['SERVER_SOFTWARE'] ?? '-');
			echo '<br>Connection: ' . esc_html( $_SERVER['HTTP_CONNECTION'] ?? '-');
			echo '<br>Client IP: ' . esc_html( $_SERVER['REMOTE_ADDR'] ?? '-');
			echo '<br>Client Browser: ' . esc_html( $_SERVER['HTTP_USER_AGENT'] ?? '-');
			echo '<br>Current URL: ' . self::getCurrentURL();

			echo '<br>Last Plugin DB Update: ' . esc_html( get_option( 'vmls_db_version' ) );

?>


<h4>FFmpeg & Codecs</h4>
FFmpeg and specific codecs are required for transcoding live streams for Wowza mobile delivery, converting videos, extracting snapshots.
<BR><BR>
				<?php

			if ( ! $options['enable_exec'] )
			{
				echo 'Currently executing server commands is disabled and tools like FFmpeg are not accessible.<br>If web host provides secure access to server commands like FFmpeg, enable functionality from <a href="admin.php?page=live-webcams&tab=hls">FFmpeg Settings</a>';
			}

			if ( $options['enable_exec'] )
			{
				$fexec = 0;
				echo 'exec: ';
				if ( function_exists( 'exec' ) )
				{
					echo 'function is enabled';

					if ( exec( 'echo EXEC' ) == 'EXEC' )
					{
						echo ' and works';
						$fexec = 1;
					} else
					{
						echo ' <b>but does not work</b>';
					}
				} else
				{
					echo '<b>function is not enabled</b><BR>PHP function "exec" is required to run FFMPEG. Current hosting settings are not compatible with this functionality.';
				}

				echo '<br>PHP script owner: ' . esc_html( get_current_user() );
				if (function_exists('getmyuid')) {
					echo ' #' . esc_html( getmyuid() );
				} else {
					echo ' (getmyuid() not available)';
				}
				if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
					echo '<br>Process effective owner: ' . esc_html( posix_getpwuid( posix_geteuid() )['name'] ) . ' #' . esc_html( posix_geteuid() );
				} else {
					echo '<br>Process effective owner: (posix_getpwuid() or posix_geteuid() not available)';
				}

				if ( $fexec )
				{
					echo '<br>exec("whoami"): ';
					$cmd    = 'whoami';
					$output = '';
					if ( $options['enable_exec'] )
					{
						exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					}
					foreach ( $output as $outp )
					{
						echo esc_html( $outp );
					}

					echo '<br>exec("which ffmpeg"): ';
					$cmd    = 'which ffmpeg';
					$output = '';
					if ( $options['enable_exec'] )
					{
						exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					}
					foreach ( $output as $outp )
					{
						echo esc_html( $outp );
					}

					echo '<br>Path from settings: ' . esc_html( $options['ffmpegPath'] ) . '<br>';

					$cmd    = sanitize_text_field( $options['ffmpegPath'] ) . ' -version';
					$output = '';
					if ( $options['enable_exec'] )
					{
						exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					}
					if ( $returnvalue == 127 )
					{
						echo '<b>Warning: not detected: ' . esc_html( $cmd ) . '</b>'; } else
					{
						echo 'FFMPEG Detected (127): ';

						echo esc_html( $cmd ) . ' / Output:<br><textarea readonly cols="120" rows="4">';
						echo esc_textarea( join( "\n", $output ) );
						echo '</textarea>';
					}

					$cmd    = sanitize_text_field( $options['ffmpegPath'] ) . ' -codecs';
					$output = '';
					if ( $options['enable_exec'] )
					{
						exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					}

					// detect codecs
					$hlsAudioCodec = ''; // hlsAudioCodec
					if ( $output )
					{
						if ( count( $output ) )
						{
							echo '<br>Codec libraries: ';
							echo esc_html( $cmd ) . ' / Output:<br><textarea readonly cols="120" rows="4">';
							echo esc_textarea( join( "\n", $output ) );
							echo '</textarea>';
							foreach ( array( 'h264', 'vp6', 'speex', 'nellymoser', 'aacplus', 'vo_aacenc', 'faac', 'fdk_aac', 'vp8', 'vp9', 'opus' ) as $cod )
							{
								$det  = 0;
								$outd = '';
								echo '<BR>' . esc_html( "$cod : " );
								foreach ( $output as $outp )
								{
									if ( strstr( $outp, $cod ) )
									{
										$det  = 1;
										$outd = $outp;
									}
								};

								if ( $det )
								{
									echo esc_html( "detected ($outd)" );
								} elseif ( in_array( $cod, array( 'aacplus', 'vo_aacenc', 'faac', 'fdk_aac' ) ) )
								{
									echo esc_html( "lib$cod is missing but other aac codec may be available" );
								} else
								{
									echo esc_html( "missing: configure and install FFMPEG with lib$cod if you don't have another library for that codec" );
								}

								if ( $det && in_array( $cod, array( 'aacplus', 'vo_aacenc', 'faac', 'fdk_aac' ) ) )
								{
									$hlsAudioCodec = 'lib' . $cod;
								}
							}
						}
					}

					echo '<BR>Auto config AAC Codec: ' . esc_html( $hlsAudioCodec );
				}

?>
<BR><BR>You need only 1 AAC codec. Latest FFmpeg supports aac and libfdk_aac. Depending on <a href="https://trac.ffmpeg.org/wiki/Encode/AAC">AAC library available on your system</a> you may need to update transcoding parameters.

<h4>Unoconv</h4>
Unoconv is required for converting documents to accessible formats.
<BR><BR>
					<?php
				echo 'Path from settings: ' . esc_html( $options['unoconvPath'] ) . '<br>';

				$cmd    = $options['unoconvPath'] . ' --version';
				$output = '';
				if ( $options['enable_exec'] )
				{
					if (function_exists('exec')) {
						exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					} else {
						echo '<b>Warning: exec function is not available on this webhost.</b>';
						$returnvalue = 127; // Indicate that the command could not be executed
					}
				}
				if ( $returnvalue == 127 )
				{
					echo '<b>Warning: not detected: ' . esc_html( $cmd ) . '</b>'; } else
				{
					echo '<u>Detected:</u>';
					echo esc_html( $cmd ) . ' / Output:<br><textarea readonly cols="120" rows="4">';
					echo esc_textarea( join( "\n", $output ) );
					echo '</textarea>';
				}
?>
<h4>ImageMagick Convert</h4>
ImageMagick Convert is required for converting documents to slides.
<BR><BR>
					<?php
				echo 'Path from settings: ' . esc_html( $options['convertPath'] ) . '<br>';

				$cmd    = sanitize_text_field( $options['convertPath'] );
				$output = '';
				if ( $options['enable_exec'] )
				{
					if (function_exists('exec')) {
						exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					} else {
						$output = ['exec function is not available on this webhost.'];
						$returnvalue = 127;
					}
				}
				if ( $returnvalue == 127 )
				{
					echo '<b>Warning: not detected: ' . esc_html( $cmd ) . '</b>'; } else
				{
					echo '<u>Detected:</u>';
					echo esc_html( $cmd ) . ' / Output:<br><textarea readonly cols="120" rows="4">';
					echo esc_textarea( join( "\n", $output ) );
					echo '</textarea>';
				}
			} //enable_exec

?>
<h4>Session Control</h4>
				<?php

			if ( in_array( $options['webStatus'], array( 'enabled', 'strict', 'auto' ) ) )
			{
				if ( file_exists( $path = $options['uploadsPath'] . '/_rtmpStatus.txt' ) )
				{
					$url = self::path2url( $path );
					echo 'Found: <a target=_blank href="' . esc_url( $url ) . '">last status request</a> ' . date( 'D M j G:i:s T Y', filemtime( $path ) );
					echo '<br><textarea readonly cols="120" rows="4">' . esc_textarea( file_get_contents( $path ) ) . '</textarea>';
				} else
				{
					echo 'Warning: Status log file not found!';
				}
			} else
			{
				echo 'Warning: webStatus not enabled/strict/auto!';
			}
?>

<h4>GeoIP</h4>
GeoIP is required for performers to ban certain countries, regions from accessing their listings.
<BR><BR>

				<?php
			$clientIP = self::get_ip_address();
			echo __( 'Client IP', 'ppv-live-webcams' ) . ': ' . esc_html( $clientIP );

			if ( self::detectLocation() === false )
			{
				echo '<br>' . __( 'ERROR: Can not detect location. GeoIP extension is required on host for this functionality.', 'ppv-live-webcams' );
			} else
			{
				echo '<br><u>Detected:</u> GeoIP location seems to work. Detected location (precision depends on IP):';
				echo '<br>' . __( 'Continent', 'ppv-live-webcams' ) . ': ' . esc_html( self::detectLocation( 'continent', $clientIP ) );
				echo '<br>' . __( 'Country', 'ppv-live-webcams' ) . ': ' . esc_html( self::detectLocation( 'country', $clientIP ) );
				echo '<br>' . __( 'Region', 'ppv-live-webcams' ) . ': ' . esc_html( self::detectLocation( 'region', $clientIP ) );
				echo '<br>' . __( 'City', 'ppv-live-webcams' ) . ': ' . esc_html( self::detectLocation( 'city', $clientIP ) );

			}

?>
<h4>Last Process Logs</h4>
				<?php
			foreach ( array( 'snapshot', 'record', 'transcode', 'push' ) as $type )
			{
				$lastLog = $options['uploadsPath'] . '/lastLog-' . $type . '.txt';

				echo '<h5>' . esc_html( $type ) . '</h5>  ' . esc_html( $lastLog ) . ' : ';
				if ( ! file_exists( $lastLog ) )
				{
					echo 'Not found, yet!';
				} else
				{
					$log = self::varLoad( $lastLog );
					echo '<br>PostID: ' . esc_html( $log['postID'] );
					echo '<br>Time: ' . date( DATE_RFC2822, $log['time'] );
					echo '<br>Command: ' . esc_html( $log['cmd'] );
					echo '<br>Return: ' . esc_html( $log['return'] );
					echo '<br>Output: ' . esc_html( implode( ', ', $log['output'] ) );
					echo '<br>File: ' . esc_html( $log['file'] );
					if ( ! file_exists( $log['file'] ) )
					{
						echo ' Log file not found!';
					} else
					{
						echo '<br><textarea readonly cols="100" rows="4">' . esc_textarea( file_get_contents( $log['file'] ) ) . '</textarea>';
					}
				}
			}
?>

	<h4>Filters</h4>
				<?php

			echo 'the_content: ';
			self::filtersFor( 'the_content' );

			echo '<br>current_filter(): ';
			echo esc_html( json_encode( current_filter() ) );

			break;

		case 'import':
?>
<h3><?php _e( 'Import Options', 'ppv-live-webcams' ); ?></h3>
Import/Export plugin settings and options.
				<?php

				$importURL = sanitize_text_field( $_POST['importURL'] ?? '' );
				if ($importURL) 
				{
					echo '<br>Importing settings from URL: ' . esc_html( $importURL );
					$optionsImport = parse_ini_string( file_get_contents( $importURL ), false );

					//display parse error if any
					if ( $optionsImport === false )
					{
						echo '<br>Parse Error: ' . esc_html( error_get_last()['message'] );
					}

					if ($optionsImport ) foreach ( $optionsImport as $key => $value )
					{
						echo '<br>' . esc_html( " - $key = $value" );
						$options[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
					}
					update_option( 'VWliveWebcamsOptions', $options );
				}

			if ( $importConfig = sanitize_textarea_field( $_POST['importConfig'] ?? '' ) )
			{
				echo '<br>Importing: ';
				$optionsImport = parse_ini_string( stripslashes( $importConfig ), false );

				//display parse error if any
				if ( $optionsImport === false )
				{
						echo '<br>Parse Error: ' . esc_html( error_get_last()['message'] );
				}
						
				if ($optionsImport ) foreach ( $optionsImport as $key => $value )
				{
					echo '<br>' . esc_html( " - $key = $value" );
					$options[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
				}
				update_option( 'VWliveWebcamsOptions', $options );
			}
?>
<h4>Settings Import URL</h4>
<input name="importURL" type="text" id="importURL" size="120" maxlength="256" value=""/>
<br/>If you have an account with VideoWhisper go to <a href="https://consult.videowhisper.com/my-accounts/">My Accounts</a> and use Configure Apps button for the account you want to use. Copy and paste the Settings Import URL here. 
<br/>If you don't have a streaming plan, yet, get one from <a href="https://webrtchost.com/hosting-plans/#Streaming-Only">WebRTC Host</a>.
<br/>If you change your plan, import settings again as this also includes streaming plan limitations to avoid streams from being rejected.
<?php 
submit_button( "Import");
?>

<h4>Import Plugin Settings</h4>
<textarea name="importConfig" id="importConfig" cols="120" rows="12"></textarea>
<br>Quick fill settings as option = "value".
<?php 
submit_button( "Import");
?>


<h4>Export Current Plugin Settings</h4>
<textarea readonly cols="120" rows="12">[Plugin Settings]
				<?php
			foreach ( $options as $key => $value )
			{
				if ( is_object($value) ) echo esc_textarea( "\n" . "$key = " . '"' . addslashes( json_encode($value) ) . '"' );
				else {
					if (is_array($value)) {
						echo esc_textarea( "\n" . "$key = " . '"' . esc_html( json_encode($value) ) . '"' );
					} else {
						echo esc_textarea( "\n" . "$key = " . '"' . esc_html( stripslashes( strval($value) ) ) . '"' );
					}
				}
			}
?>
			</textarea>

<h4>Export Default Plugin Settings</h4>
<textarea readonly cols="120" rows="10">[Plugin Settings]
				<?php
			if ($optionsDefault) foreach ( $optionsDefault as $key => $value )
			{
				if ( is_object($value) ) echo esc_textarea( "\n" . "$key = " . '"' . addslashes( json_encode($value) ) . '"' );
				else echo esc_textarea( "\n" . "$key = " . '"' . esc_html( stripslashes( strval($value) ) ) . '"' );
			}
?>
			</textarea>

<h5>Warning: Saving will set settings provided in Import Plugin Settings box.</h5>
				<?php

			break;

		case 'reset':
?>
<h3><?php _e( 'Reset Options', 'ppv-live-webcams' ); ?></h3>
This resets some options to defaults. Useful when upgrading plugin and new defaults are available for new features and for fixing broken installations.
				<?php

			$confirm = ( isset( $_GET['confirm'] ) && '1' === $_GET['confirm'] ) ? true : false;

			if ( $confirm )
			{
				echo '<h4>Resetting...</h4>';
			} else
			{
				echo '<p><A class="button" href="admin.php?page=live-webcams&tab=reset&confirm=1">Yes, Reset These Settings!</A></p>';
			}

			$resetOptions = array( 'customCSS', 'dashboardCSS', 'listingTemplate', 'listingTemplate2', 'listingTemplateList', 'listingTemplateHorizontal', 'listingBig', 'supportRTMP', 'alwaysRTMP', 'supportP2P', 'alwaysP2P', 'parametersPerformer', 'parametersPerformerPresentation', 'parametersClient', 'parametersClientPresentation', 'ppvCloseAfter', 'ppvBillAfter', 'custom_post', 'detect_hls', 'detect_mpeg', 'tipOptions' );

			foreach ( $resetOptions as $opt )
			{
				echo '<BR> - ' . esc_html( $opt );
				if ( $confirm )
				{
					$options[ $opt ] =  $optionsDefault[ $opt ];
				}
			}

			if ( $confirm )
			{
				update_option( 'VWliveWebcamsOptions', $options );
			}

			break;

		case 'group':
			$options['groupModesConfig'] =  stripslashes( $options['groupModesConfig'] ) ;

?>
			<h3>Room Chat Modes Setup</h3>
Configure group chat room mode presets. Performers can select room mode for webcam room when going live (in example Free Chat, Broadcast, Paid Group Show, Calls Only, Video Conference).
In HTML5 Videochat with Advanced interface, performer can also customize the chatroom at runtime, from Options tab.

<h4>Group Modes</h4>
<textarea name="groupModesConfig" id="groupModesConfig" cols="100" rows="12"><?php echo esc_textarea( $options['groupModesConfig'] ); ?></textarea>
<BR>Configure modes as sections. Set cost per minute as "cpm", 2 way cost as "cpm2". Many HTML5 Videochat - Room settings can be configured per chat mode. Some default settings are for older legacy interfaces, with no effect in HTML5 Videochat.
Default:<br><textarea readonly cols="100" rows="6"><?php echo esc_textarea( $optionsDefault['groupModesConfig'] ); ?></textarea>

<BR>Parsed configuration (should be an array or arrays) JSON:<BR>
				<?php

			echo esc_html( json_encode( $options['groupModes'] ) );
?>
<BR>Serialized:<BR>
				<?php

			echo esc_html( serialize( $options['groupModes'] ) );
?>

<h4>MicroPayments Author Subscriptions</h4>
Paid group chat modes can be accessed by subscription (instead of pay per minute) with MicroPayments plugin. Performer configures a subscription tier from Performer Dashboard > Setup tab and during paid group shows subscribers access for free and other users have to pay per minute. To only allow subscribers to access, remove webcam post type from VideoWhisper &gt; MicroPayments plugin settings > Author Subscriptions > Externally Managed Post Types so MicroPayments disables access to chat interface for rest of users.


<h4>Performer Go Live : Chat Modes List</h4>
<select name="performerGolive" id="performerGolive">
  <option value="form" <?php echo $options['performerGolive'] == 'form' ? 'selected' : ''; ?>>Form</option>
  <option value="buttons" <?php echo $options['performerGolive'] == 'buttons' ? 'selected' : ''; ?>>Buttons</option>
</select>
<br>Form may include advanced settings like Checkin options and Buttons shows simple access buttons.


<h4>Group Chat Grace Time</h4>
<input name="groupGraceTime" id="groupGraceTime" type="text" size="5" maxlength="10" value="<?php echo esc_attr( $options['groupGraceTime'] ); ?>"/>s
<br>On paid sessions users only get billed if they stay longer than this time (free stay for evaluation).

<h4>Audio Only Chat Mode</h4>
<select name="modeAudio" id="modeAudio">
  <option value="0" <?php echo $options['modeAudio'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['modeAudio'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<br>Toggle global availability of audio only, in listings menu and locked calls.

<h4>Text Only Chat Mode</h4>
<select name="modeText" id="modeText">
  <option value="0" <?php echo $options['modeText'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['modeText'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<br>Toggle global availability of text only mode, in listings menu and locked calls.

<h4>Video Chat Mode</h4>
<select name="modeVideo" id="modeVideo">
  <option value="0" <?php echo $options['modeVideo'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['modeVideo'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<br>Toggle global availability of videochat mode (video including picture and sound), in listings menu and locked calls. This is the default mode and should be active in most setups.



<h4>Voyeur Mode</h4>
<select name="voyeurAvailable" id="voyeurAvailable">
  <option value="never" <?php echo $options['voyeurAvailable'] == 'never' ? 'selected' : ''; ?>>Never</option>
  <option value="always" <?php echo $options['voyeurAvailable'] == 'always' ? 'selected' : ''; ?>>Always</option>
  <option value="public" <?php echo $options['voyeurAvailable'] == 'public' ? 'selected' : ''; ?>>In Public</option>
</select>
<BR>When is voyeur mode available: always or only when performer is during private or public chat. In voyeur mode clients can join group chat hidden in user list, secretly if they don't send messages.

				<?php
					
					/*  <option value="private" <?php echo $options['voyeurAvailable'] == 'private' ? 'selected' : ''; ?>>In Private</option> */

			break;

		case 'record':
			$options['pendingMessage'] = stripslashes( $options['pendingMessage'] );
			$options['recordFieldsConfig'] = stripslashes( $options['recordFieldsConfig'] );
?>
			<h3>Account Administrative Records and Verification/Approval</h3>
Configure fields, questions for performing users, as necessary: details required for approval, verification, payouts. These details are included on Performer Registration page and can be edited later from Performer Dashboard. 
<BR><a href="admin.php?page=live-webcams-records">Review Records and Approve Accounts</a>

<h4>Fields / Questions</h4>

<textarea name="recordFieldsConfig" id="recordFieldsConfig" cols="100" rows="12"><?php echo esc_textarea( $options['recordFieldsConfig'] ); ?></textarea>
				<?php
			if ( $options['recordFieldsConfig'] && ! $options['recordFields'] )
			{
				echo '<br><b>Warning: Configuration syntax error! Please review & correct.</b>';
			}
?>
<BR>Save to setup.
<br>Configure fields as sections with these parameters: 
<br>type = text/textarea/select/checkboxes/file
<br>instructions = instructions to end user for filling field
<br>options = values (separated by /), for type = select/checkboxes
<br>required = true for fields that need to be filled for registration/approval
<br>personal = true for fields requird per person
<br>*If a value contains special characters it needs to be enclosed in double-quotes ("").
<br>Default:<br><textarea readonly cols="100" rows="6"><?php echo esc_textarea( $optionsDefault['recordFieldsConfig'] ); ?></textarea>

<small>
<BR>Parsed records configuration (should be an array or arrays) JSON:<BR>
				<?php

			echo esc_html( json_encode( $options['recordFields'] ) );

?>
<BR>Serialized:<BR>
				<?php

			echo esc_html( serialize( $options['recordFields'] ) );

?>
</small>

<h4>Multi Person Accounts</h4>
<select name="multiPerson" id="multiPerson">
  <option value="0" <?php echo $options['multiPerson'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['multiPerson'] ? 'selected' : ''; ?>>Enabled</option>	
</select>
<br>Allow multiple physical persons to register and use same account and collect information (in example ID for age verification) for each one separately. This is useful for site that need to support accounts with multiple individuals, like partners, couples, groups, teams, companies. You will need to configure "personal = true" for fields that need to be collected per person. 
<br>If you don't need this type of features it's recommended to keep feature disabled, for simplicity and clarity.

<h4>Person Types</h4>
<input name="personTypes" id="personTypes" type="text" size="80" maxlength="256" value="<?php echo esc_attr( $options['personTypes'] ); ?>"/>
<br>Comma separated physical person types, when using multi person accounts. Physical person types can be genders or other roles (in example on a professional platform an account can represent a team or company with persons in Frontend/Backend/Design/Sales specific to platform).  Default: <?php echo esc_textarea( $optionsDefault['personTypes'] ); ?>

<h4>Registration Columns</h4>
<select name="registrationColumns" id="registrationColumns">
  <option value="0" <?php echo $options['registrationColumns'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['registrationColumns'] ? 'selected' : ''; ?>>Enabled</option>	
</select>
<br>Show registration field in 2 columns, for multi person fields.

<h4>Maximum Upload Size</h4>
<input name="maxUpload" id="maxUpload" type="text" size="5" maxlength="10" value="<?php echo esc_attr( $options['maxUpload'] ); ?>"/>kb
<br/>Maximum size for uploaded files (documents, photos, selfies). Suggested: 6000 (6MB).

<h4>Enable Performers Without Verification</h4>
<select name="unverifiedPerformer" id="unverifiedPerformer">
  <option value="0" <?php echo $options['unverifiedPerformer'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['unverifiedPerformer'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Requiring verification disables performer features (going live, setting up room) until approval, if required by website type (in example adult sites). 

<h4>Enable Studios Without Verification</h4>
<select name="unverifiedStudio" id="unverifiedStudio">
  <option value="0" <?php echo $options['unverifiedStudio'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['unverifiedStudio'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Requiring verification disables studio features until approval if required by website type (in example adult sites). 

<h4>Pending Approval Message</h4>
				<?php
			$options['pendingMessage'] = wp_kses_post( $options['pendingMessage'] );
			wp_editor( $options['pendingMessage'], 'pendingMessage', $settings = array( 'textarea_rows' => 3, 'media_buttons' => false  ) );
?>
<br>Shows to performers/studios after registrations and after updating account records. Use blank to disable.
Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['pendingMessage'] ); ?></textarea>

<?php
			$options['pendingSubject'] = stripslashes( $options['pendingSubject'] );
			$options['pendingText']    = stripslashes( $options['pendingText'] ) ;
?>

<h4>Pending Email Subject</h4>
<input name="pendingSubject" type="text" id="pendingSubject" size="100" maxlength="256" value="<?php echo esc_attr( $options['pendingSubject'] ); ?>"/>
<br>Pending email is sent to performer/studio after activation. Use blank to disable. If no activation is required this can be a welcome message. Default: <small><?php echo esc_html( $optionsDefault['pendingSubject'] ); ?></small>

<h4>Pending Email Text</h4>
<textarea name="pendingText" id="pendingText" cols="100" rows="3"><?php echo esc_textarea( $options['pendingText'] ); ?></textarea>
<br>Includes site login link. Default:<small><?php echo esc_html( $optionsDefault['pendingText'] ); ?></small>

<h4>Clean Old Uploads</h4>
<select name="recordClean" id="recordClean">
  <option value="0" <?php echo $options['recordClean'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['recordClean'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>If enabled deletes old uploaded document when user uploads an update. Recommended: No, to keep older documents if needed for later reference.
<br>User files are stored in <?php echo esc_html( $options['uploadsPath'] ); ?>/user_[user_nicename]_[ID] .
				<?php

			break;

		case 'profile':
			$options['profileFieldsConfig'] = stripslashes( $options['profileFieldsConfig'] );

?>
			<h3>Room Listing - Profile Setup</h3>
Configure fields, questions for room listings. These can be configured by performer and will show on room listing profile page.
<br>Warning: These profile fields are for rooms, not for user profiles. Only "match" fields apply both for room profiles and client profiles, for matchmaking purposes. A performer can setup and use multiple webcam rooms. Each webcam room has a listing (and profile with these fields).


<h4>Room Profiles</h4>
<select name="profiles" id="profiles">
  <option value="0" <?php echo $options['profiles'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['profiles'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enable rooms profiles and sections.

<h4>Fields / Questions</h4>
<textarea name="profileFieldsConfig" id="profileFieldsConfig" cols="100" rows="12"><?php echo esc_textarea( $options['profileFieldsConfig'] ); ?></textarea>
				<?php
			if ( $options['profileFieldsConfig'] && ! $options['profileFields'] )
			{
				echo '<br><b>Warning: Configuration syntax error! Please review & correct.</b>';
			}
?>
<BR>Save to setup. Configure fields as sections with parameters "type", "instructions", "options" (values separated by |), "filter", "icon" as necessary .
<br>Type:  text/textarea/select/multiselect/checkboxes/language/location/continent/country/region 
<br>If a value contains any non-alphanumeric characters it needs to be enclosed in double-quotes ("").
<br>Fields that contain options can also be used as filters for listings, by configuring "filter = enabled".
<br>Supported filter types: fields with options (select/multiselect/checkboxes) and language/location. Not implemented for continent/country/region .
<br>Fields for matchmaking are marked with parameter match=enabled and match=mirror for mirroring (define 2 options and setting one will match the other, ex. Man looking for Woman/Woman looking for Man). Matchmaking parameters apply both to room profiles and client user accounts (rooms match with clients). Hide labels with hideLabel=true . Matchmaking fields show on registration pages.
<br>The icon field represents a class name for an icon to show next to the field (in example "list icon"). Interface includes <a href="https://semantic-ui.com/elements/icon.html">Semantic UI icons</a>.
<br>Paramenter default can setup a default value. 
<br>Filters can be resource intensive in MySQL queries (making listings load slowly). As optimization you can add an index for metas: CREATE INDEX meta_value ON `wp_postmeta` (meta_value(10)) USING BTREE
<br>Default:<br><textarea readonly cols="100" rows="6"><?php echo esc_textarea( $optionsDefault['profileFieldsConfig'] ); ?></textarea>

<BR>Parsed fields configuration (should be an array or arrays) JSON:<BR>
<?php
echo  esc_html( wp_json_encode( $options['profileFields'], JSON_UNESCAPED_SLASHES ) );
echo '<br>Number of fields: ' . esc_html( is_array($options['profileFields']) ? 'Array - ' . count($options['profileFields']) : (is_object($options['profileFields']) ? 'Object - ' . count((array)$options['profileFields']) : 0) );
?>

<h4>Default Profile Field Icon</h4>
<input name="profileFieldIcon" id="profileFieldIcon" type="text" size="20" maxlength="128" value="<?php echo esc_attr( $options['profileFieldIcon'] ); ?>"/>
<br>Default icon for profile fields (class name, in example 'dropdown icon', 'arrow right icon'). Leave blank to disable in forms and use 'dropdown icon' for dropdowns.

<h3>Profile Content Integrations</h3>

<h4>MicroPayments <a target="_plugin" href="https://wordpress.org/plugins/paid-membership/">Plugin</a> - Enable Digital Assets</h4>
				<?php
			if ( is_plugin_active( 'paid-membership/paid-membership.php' ) )
			{
				echo 'Detected:  <a href="admin.php?page=paid-membership">Configure</a>';
			} else
			{
				echo 'Not detected. Please install and activate <a target="_micropayments" href="https://wordpress.org/plugins/paid-membership/">MicroPayments Plugin</a> from <a href="plugin-install.php?s=videowhisper&tab=search&type=term">Plugins > Add New</a>!';
			}
?>

<h4>Digital Assets</h4>
<select name="micropaymentsAssets" id="micropaymentsAssets">
  <option value="0" <?php echo $options['micropaymentsAssets'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['micropaymentsAssets'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Include Content by MicroPayments on room page.

<h4>Asset Post Types</h4>
<input name="postTypesAssets" type="text" id="postTypesAssets" size="100" maxlength="128" value="<?php echo esc_attr( $options['postTypesAssets'] ); ?>"/>
<br>List of post types that perfomers can select to show on room page.


<h4>Video Share VOD <a target="_plugin" href="https://videosharevod.com/">Plugin</a> - Enable Videos</h4>
				<?php
			if ( is_plugin_active( 'video-share-vod/video-share-vod.php' ) )
			{
				echo 'Detected:  <a href="admin.php?page=video-share">Configure</a> | <a href="https://videosharevod.com/features/quick-start-tutorial/">Tutorial</a>';
			} else
			{
				echo 'Not detected. Please install and activate <a target="_videosharevod" href="https://wordpress.org/plugins/video-share-vod/">VideoShareVOD Plugin</a> from <a href="plugin-install.php?s=videowhisper&tab=search&type=term">Plugins > Add New</a>!';
			}
?>
<BR><select name="videosharevod" id="videosharevod">
  <option value="0" <?php echo $options['videosharevod'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['videosharevod'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>This feature requires latest FFmpeg with HTML5 codecs.
<br>Enables VideoShareVOD integration that allows performers to add videos to their videochat page. Performer role should be allowed to share/publish videos from VSV settings.


<h4>Picture Gallery <a target="_plugin" href="https://wordpress.org/plugins/picture-gallery/">Plugin</a> - Enable Pictures</h4>
				<?php
			if ( is_plugin_active( 'picture-gallery/picture-gallery.php' ) )
			{
				echo 'Detected:  <a href="admin.php?page=picture-gallery">Configure</a>';
			} else
			{
				echo 'Not detected. Please install and activate Picture Gallery by VideoWhisper.com from <a href="plugin-install.php">Plugins > Add New</a>!';
			}
?>
<BR><select name="picturegallery" id="picturegallery">
  <option value="0" <?php echo $options['picturegallery'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['picturegallery'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables Picture Gallery integration that allows performers to add videos to their videochat page. Performer role should be allowed to share/publish pictures from plugin settings.


<h4>WooCommerce Plugin - Enable Products</h4>
				<?php
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) )
			{
				echo 'Detected';
			} else
			{
				echo 'Not detected. Please install and activate WooCommerce from <a href="plugin-install.php">Plugins > Add New</a>!';
			}
?>
<BR><select name="woocommerce" id="woocommerce">
  <option value="0" <?php echo $options['woocommerce'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['woocommerce'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables WooCommerce products in performer dashboard and and room profile. Great in combination with MicroPayments plugin that enable selling access to digital content as WooCommerce products.


				<?php
			break;

		case 'studio':
?>
			<h3>Studio Settings</h3>

<a href="https://paidvideochat.com/features/studios/">Studios</a> are managers (companies, agencies, agents, schools, departments) that manage multiple performers (artists, providers, creators, experts, teachers, instructors).
<br>A studio account can create and manage multiple performer accounts and webcam room listings for these performers. Can setup shared rooms that can be used in common by multiple performers, can see individual performer balance and collect performer payments (to consolidate payouts). How studios distribute profits after collection is beyond the scope of this solution. Studios do not have performer capabilities (to go live) and only manage the performers.
<br>Webmasters can <a href="admin.php?page=live-webcams-studio">assign existing users to existing studios, as performers</a>. Performers can be independent or under a studio.
<br>Studios can be required to fill <a href="admin.php?page=live-webcams&tab=record">Account Records for Approval</a>, to operate on site.
<br>Warning: Studios are not suitable for all sites as it adds an extra layer of complexity and may generate confusion for performers on registration. If not needed on your site, disable studio registrations and also remove the site menus and site pages that reference studios.

<h4>Enable Studio Registrations</h4>
<select name="studios" id="studios">
  <option value="0" <?php echo $options['studios'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['studios'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables studios registration role (so user can register as studios). If disabled you will have to promote users to studio role using a different path (manual, paid membership).

<h4>Studio Role Name</h4>
<p>This is used as registration role option and access to studio dashboard page (redirection on login). Administrators can also manually access dashboard page for testing.</p>
<input name="roleStudio" type="text" id="roleStudio" size="20" maxlength="64" value="<?php echo esc_attr( $options['roleStudio'] ); ?>"/>
<br>Sample possible value: studio, company, manager, agency, school, university, department, group, academy. Default: ><?php echo esc_html( $optionsDefault['roleStudio'] ); ?>
<br>Should be 1 single value (used as role for new users of this type), a role name (slug) like "user_role", not name like "User Role". 
<br> - Your roles (for troubleshooting):
				<?php
			global $current_user;
			foreach ( $current_user->roles as $role )
			{
				echo esc_html( $role ) . ' ';
			}
?>
			<br> - Current WordPress roles:
				<?php
			global $wp_roles;
			foreach ( $wp_roles->roles as $role_slug => $role )
			{
				echo esc_html( $role_slug ) . '= "' . esc_html( $role['name'] ) . '" ';
			}
?>
<br>Warning: Changing role name will allow only users with new role to access performer dashboard. New role is assigned to <a href="admin.php?page=live-webcams&tab=integration">new registrations if enabled</a>. <a href="users.php">Previously registered users</a> need to be assigned to new role manually. Additionally, shortcode role parameter need to be updated on the dedicated <a href="admin.php?page=live-webcams&tab=pages">registration pages</a>.

<h4>Studio Performers</h4>
<input name="studioPerformers" id="studioPerformers" type="text" size="5" maxlength="10" value="<?php echo esc_attr( $options['studioPerformers'] ); ?>"/>
<br>Specify maximum number of performers each studio can have. To prevent flood of items, name reservation.

<h4>Studio Webcams</h4>
<input name="studioWebcams" id="studioWebcams" type="text" size="5" maxlength="10" value="<?php echo esc_attr( $options['studioWebcams'] ); ?>"/>
<br>Specify maximum number of webcam listings each studio can have. When reached, studio can no longer create new ones, to prevent flood of items, name reservation.

<h4>Studio Dashboard Message (Brief Instructions, News)</h4>
<textarea name="dashboardMessageStudio" id="dashboardMessageStudio" cols="100" rows="4"><?php echo esc_textarea( $options['dashboardMessageStudio'] ); ?></textarea>
<br>Shows in studio dashboard. Could contain instructions, announcements, links to support.
Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['dashboardMessageStudio'] ); ?></textarea>

<h4>Enable Studios Without Verification</h4>
<select name="unverifiedStudio" id="unverifiedStudio">
  <option value="0" <?php echo $options['unverifiedStudio'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['unverifiedStudio'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Disable studio features until verification if required by website type (in example adult sites).


				<?php
			break;
		case 'video':
?>
<h3>Videos, Pictures, Reviews</h3>
Solution integrates advanced plugins to manage videos, pictures, reviews.

<h4><a target="_plugin" href="https://videosharevod.com/">Video Share VOD</a> - Enable Videos</h4>
				<?php
			if ( is_plugin_active( 'video-share-vod/video-share-vod.php' ) )
			{
				echo 'Detected:  <a href="admin.php?page=video-share">Configure</a> | <a href="https://videosharevod.com/features/quick-start-tutorial/">Tutorial</a>';
			} else
			{
				echo 'Not detected. Please install and activate <a target="_videosharevod" href="https://wordpress.org/plugins/video-share-vod/">VideoShareVOD Plugin</a> from <a href="plugin-install.php">Plugins > Add New</a>!';
			}
?>
<BR><select name="videosharevod" id="videosharevod">
  <option value="0" <?php echo $options['videosharevod'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['videosharevod'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>This feature requires latest FFmpeg with HTML5 codecs.
<br>Enables VideoShareVOD integration that allows performers to add videos to their videochat page. Performer role should be allowed to share/publish videos from VSV settings.
<br>HTML5 Videochat saves recordings (if configured) using FFmpeg in a _recordings folder in each room folder, as webm for WebRTC streams. VideoShareVOD enables Import (publishing) of these videos and conversion to HTML5 MP4 that works on all browsers.
<br>Performers can toggle stream recording from Settings panel in HTML5 Videochat advanced interface and defaults are configured from <a href="admin.php?page=live-webcams&tab=app">HTML5 Videochat Settings</a> .
<br>Streams are recorded in adaptive formats (webm/mp4) depending on broadcast (WebRTC/RTMP) and files are stored in room uploads folder. Recordings be published to site with VideoShareVOD plugin from Videos tab, Import in Performer Dashboard.
<br>Warning: Recording streams involves processing resources mainly for disk writing and consumes disk space fast.

<h4>Recordings Stack</h4>
<select name="recordingStack" id="recordingStack">
  <option value="0" <?php echo $options['recordingStack'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['recordingStack'] == '1' ? 'selected' : ''; ?>>Yes</option>
  <option value="both" <?php echo $options['recordingStack'] == 'both' ? 'selected' : ''; ?>>Both</option>
</select>
<br>Save stream recordings side by side in same file (for conferencing). Both saves both individually and in stack, for increased reliability, using more resources.

<h4>Teaser Offline</h4>
 <select name="teaserOffline" id="teaserOffline">
  <option value="0" <?php echo $options['teaserOffline'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['teaserOffline'] ? 'selected' : ''; ?>>Yes</option>
</select>
Play a teaser while offline. When selecting teaser also disables any scheduling if enabled.

<h4>VOD HTTP Streaming URL (HLS/MPEG-Dash)</h4>
<input name="hls_vod" type="text" id="hls_vod" size="100" maxlength="256" value="<?php echo esc_attr( $options['hls_vod'] ); ?>"/>
<br>This is used for live streaming video files trough streaming server (instead of web server). Available with <a href="https://webrtchost.com/hosting-plans/">Wowza SE Hosting</a> plans. Ex: https://[your-rtmp-server-ip-or-domain]:1935/videowhisper-vod/
<br>Leave blank to play directly trough web server (recommended).

<h4><a target="_plugin" href="https://wordpress.org/plugins/picture-gallery/">Picture Gallery</a> - Enable Pictures</h4>
				<?php
			if ( is_plugin_active( 'picture-gallery/picture-gallery.php' ) )
			{
				echo 'Detected:  <a href="admin.php?page=picture-gallery">Configure</a>';
			} else
			{
				echo 'Not detected. Please install and activate Picture Gallery by VideoWhisper.com from <a href="plugin-install.php">Plugins > Add New</a>!';
			}
?>
<BR><select name="picturegallery" id="picturegallery">
  <option value="0" <?php echo $options['picturegallery'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['picturegallery'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables Picture Gallery integration that allows performers to add videos to their videochat page. Performer role should be allowed to share/publish pictures from plugin settings.



<h4><a target="_plugin" href="https://wordpress.org/plugins/rate-star-review/">Rate Star Review</a> - Enable Reviews</h4>
				<?php
			if ( is_plugin_active( 'rate-star-review/rate-star-review.php' ) )
			{
				echo 'Detected:  <a href="admin.php?page=rate-star-review">Configure</a>';
			} else
			{
				echo 'Not detected. Please install and activate Rate Star Review by VideoWhisper.com from <a href="plugin-install.php">Plugins > Add New</a>!';
			}
?>
<BR><select name="rateStarReview" id="rateStarReview">
  <option value="0" <?php echo $options['rateStarReview'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['rateStarReview'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables Rate Star Review integration. Shows star ratings on listings and review form, reviews on item pages.


<h4>WooCommerce Plugin - Enable Products</h4>
				<?php
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) )
			{
				echo 'Detected';
			} else
			{
				echo 'Not detected. Please install and activate WooCommerce from <a href="plugin-install.php">Plugins > Add New</a>!';
			}
?>
<BR><select name="woocommerce" id="woocommerce">
  <option value="0" <?php echo $options['woocommerce'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['woocommerce'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables WooCommerce products in performer dashboard and and room profile. Great in combination with MicroPayments plugin that enable selling access to digital content as WooCommerce products.
				<?php
			break;
			
		case 'server':
?>
<h3>RTMP / HLS Server Settings</h3>
RTMP is required for supporting external encoders like OBS or Larix Broadcaster that broadcast RTMP. RTMP streams play as HLS in browsers. RTMP to HLS is the recommended method for reliablie and scalable 1 to many live streaming. 
<br>Settings can be quickly imported (as provided for VideoWhisper setups):
<br><a href="admin.php?page=live-webcams&tab=import" class="button">Import Settings</a>

<BR>For a quick, hassle free and cost effective setup, start with <a href="https://site2stream.com/html5/">turnkey live streaming site plan</a> instead of setting up own live streaming servers.
<BR>Recommended Hosting: <a href="https://webrtchost.com/hosting-plans/" target="_vwhost">Complete Hosting with HTML5 Live Streaming</a> - All hosting requirements including HTML5 live streaming server services, SSL for site and streaming, specific server tools and configurations for advanced features.

<h4>RTMP/HLS Server Type</h4>
<select name="rtmpServer" id="rtmpServer">
  <option value="videowhisper" <?php echo $options['rtmpServer'] == 'videowhisper' ? 'selected' : ''; ?>>VideoWhisper</option>
  <option value="wowza" <?php echo $options['rtmpServer'] == 'wowza' ? 'selected' : ''; ?>>Wowza Streaming Engine</option>
</select>
<br>Choose a supported RTMP/HLS server type. Special interactivity is required for detecting external broadcasts and listing rooms as live on site and this is available with specific configuration for the supported server types.

<?php
submit_button('Save & Show');
?>
Save server type change to display specific settings.

<?php if ( $options['rtmpServer'] == 'videowhisper' ) { ?>

<h4>Account Name</h4>
<input name="vwsAccount" type="text" id="vwsAccount" size="32" maxlength="64" value="<?php echo esc_attr( $options['vwsAccount'] ); ?>"/>
<br>Account name is used in stream names.

<h4>Token for Account</h4>
<input name="vwsToken" type="text" id="vwsToken" size="32" maxlength="64" value="<?php echo esc_attr( $options['vwsToken'] ); ?>"/>

<h4>RTMP Server Address</h4>
<input name="videowhisperRTMP" type="text" id="videowhisperRTMP" size="100" maxlength="256" value="<?php echo esc_attr( $options['videowhisperRTMP'] ); ?>"/>*

<h4>HLS Server Address</h4>
<input name="videowhisperHLS" type="text" id="videowhisperHLS" size="100" maxlength="256" value="<?php echo esc_attr( $options['videowhisperHLS'] ); ?>"/>

<h4>Master Broadcast PIN</h4>
<input name="broadcastPin" type="text" id="broadcastPin" size="32" maxlength="64" value="<?php echo esc_attr( $options['broadcastPin'] ); ?>"/>
<br>Can be reset from VideoWhisper account.

<h4>Master Playback PIN</h4>
<input name="playbackPin" type="text" id="playbackPin" size="32" maxlength="64" value="<?php echo esc_attr( $options['playbackPin'] ); ?>"/>
<br>Can be reset from VideoWhisper account.

<h4>Stream Validation</h4>
<select name="videowhisperStream" id="videowhisperStream">
  <option value="0" <?php echo $options['videowhisperStream'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="all" <?php echo $options['videowhisperStream'] == 'all' ? 'selected' : ''; ?>>All</option>
  <option value="broadcast" <?php echo $options['videowhisperStream'] == 'broadcast' ? 'selected' : ''; ?>>Only Broadcast</option>
</select>
<br>Pins can be generated per stream for validation instead of sharing, to prevent users from publishing/playing other streams using your account.
<br>Warning: This function may introduce latency, extra server load or even failed streaming because streaming server will make a web request to WP on each stream access. Also incorrect configuration or firewalls that block streaming server requests will stop streaming. Test streaming when enabling and after site changes. Recommmended: Only Broadcast .
<br>Stream Validation requires configuring this Stream URL for the <a href="https://consult.videowhisper.com/my-accounts/">VideoWhisper account</a>.<br>Stream URL:<br>
	<?php //display admin ajax url
			$admin_ajax = admin_url() . 'admin-ajax.php';
			$stream_url = htmlentities( $admin_ajax . '?action=vmls_stream' );
			echo '<input type="text" value="' . esc_attr ( $stream_url ) . '" readonly size="100">';
			?>
<h4>Stream Notifications (Session Control)</h4>
Stream notifications are required to show streams (rooms) live on website when published with external RTMP econder. Involves configuring this Notification URL for the <a href="https://consult.videowhisper.com/my-accounts/">VideoWhisper account</a>. <br>Notify URL:<br>
<?php
			$stream_url = htmlentities( $admin_ajax . '?action=vmls_notify' );
//display $stream_url in readyonly input field for easy copy
echo '<input type="text" value="' . esc_attr( $stream_url ) . '" readonly size="100">';
}//end videowhisper settings

if ( $options['rtmpServer'] == 'wowza' ) { ?>
<br>On VideoWhisper setups, server side Stream Session Control needs to be configured by streaming server administrator, <a href="https://videowhisper.com/tickets_submit.php?topic=Stream+Session+Control">on request</a>.

<h4>HTML5 WebRTC</h4>
Latest web applications use HTML5 WebRTC for publishing user webcam. HTML5 live streaming support is compulsory for web based live streaming as Flash plugins are no longer supported since 2020. 
<br><a href="admin.php?page=live-webcams&tab=webrtc" class="button">Configure HTML5 WebRTC</a>
<br>P2P WebRTC is now supported using VideoWhisper WebRTC, in addition to Wowza SE. For testing and development, get a <b>Free</b> Developers from <a href="https://webrtchost.com/hosting-plans/#WebRTC-Only">WebRTC Host: P2P</a>.

<h4>RTMP Address</h4>
<input name="rtmp_server" type="text" id="rtmp_server" size="100" maxlength="256" value="<?php echo esc_attr( $options['rtmp_server'] ); ?>"/>*
<BR>A public accessible RTMP publishing address is required by external encoders like OBS, iOS/Android Larix for external live streaming. External encoder integration involves a special Wowza SE module with Streaming Session Control (included with streaming hosting plans) and special configuration on streaming server. Format: rtmp[s]://[live-streaming-server]/[app-name]

<h4>HTTP Streaming URL (HLS/MPEG-Dash)</h4>
This is used for accessing transcoded streams on HLS playback. Usually available with <a href="https://webrtchost.com/hosting-plans/#Complete-Hosting">HTML5 Streaming Plans</a> .<br>
<input name="httpstreamer" type="text" id="httpstreamer" size="100" maxlength="256" value="<?php echo esc_attr( $options['httpstreamer'] ); ?>"/>
<br>Playback external RTMP streams as HTML5 HLS.
<br>HTTPS (SSL) is required by latest browsers.
<BR>External players and encoders (if enabled) are not monitored or controlled by this plugin, unless special rtmp side session control is available.
<BR>Application folder must match rtmp application. Ex. http://localhost:1935/videowhisper-x/ works when publishing to rtmp://localhost/videowhisper-x .


<BR><?php 
//echo self::requirementRender( 'rtmp_server_configure' );
 ?>
				<?php
			submit_button();
?>

<h4>Stream Web Key, Web Login/Status, Session Control</h4>
<input name="webKey" type="text" id="webKey" size="32" maxlength="64" value="<?php echo esc_attr( $options['webKey'] ); ?>"/>
<BR>A web key can be used for <a href="https://videochat-scripts.com/videowhisper-rtmp-web-authetication-check/">VideoWhisper RTMP Web Session Check</a>. Application.xml settings in &lt;Root&gt;&lt;Application&gt;&lt;Properties&gt; :<br>
<textarea readonly cols="100" rows="4">
				<?php
			$admin_ajax = admin_url() . 'admin-ajax.php';
			$webLogin   = htmlentities( $admin_ajax . '?action=vmls&task=rtmp_login&s=' );
			$webLogout  = htmlentities( $admin_ajax . '?action=vmls&task=rtmp_logout&s=' );
			$webStatus  = htmlentities( $admin_ajax . '?action=vmls&task=rtmp_status' );

			echo esc_xml( htmlspecialchars(
				"<!-- VideoWhisper.com: RTMP Session Control https://videowhisper.com/?p=rtmp-session-control -->
<Property>
<Name>acceptPlayers</Name>
<Value>true</Value>
</Property>
<Property>
<Name>webLogin</Name>
<Value>$webLogin</Value>
</Property>
<Property>
<Name>webKey</Name>
<Value>" . $options['webKey'] . "</Value>
</Property>
<Property>
<Name>webLogout</Name>
<Value>$webLogout</Value>
</Property>
<Property>
<Name>webStatus</Name>
<Value>$webStatus</Value>
</Property>
"
			) );
?>
</textarea>
<br>On VideoWhisper setups, server side Stream Session Control needs to be configured by streaming server administrator, <a href="https://videowhisper.com/tickets_submit.php?topic=Stream+Session+Control">on request</a>.
<BR>Or based on a frontend page rewrite (RewriteRule ^^ajax$ //wp-admin/admin-ajax.php [QSA,L]), when firewall or some other plugin blocks admin-ajax.php requests:
<br><textarea readonly cols="100" rows="4">
				<?php

			$admin_ajax = home_url() . '/ajax';
			$webLogin   = htmlentities( $admin_ajax . '?action=vmls&task=rtmp_login&s=' );
			$webLogout  = htmlentities( $admin_ajax . '?action=vmls&task=rtmp_logout&s=' );
			$webStatus  = htmlentities( $admin_ajax . '?action=vmls&task=rtmp_status' );

			echo esc_xml( htmlspecialchars(
				"<!-- VideoWhisper.com: RTMP Session Control https://videowhisper.com/?p=rtmp-session-control -->
<Property>
<Name>acceptPlayers</Name>
<Value>true</Value>
</Property>
<Property>
<Name>webLogin</Name>
<Value>$webLogin</Value>
</Property>
<Property>
<Name>webKey</Name>
<Value>" . esc_attr( $options['webKey'] ) . "</Value>
</Property>
<Property>
<Name>webLogout</Name>
<Value>$webLogout</Value>
</Property>
<Property>
<Name>webStatus</Name>
<Value>$webStatus</Value>
</Property>
"
			) ); 
?>
</textarea>


<BR>Session control configuration can be configured for multiple Wowza SE applications (chat, archive), that will find confirmation of authorized user on web server by webLogin and will update their sessions using webStatus.
<BR><?php echo self::requirementRender( 'rtmp_status' ); ?>


<h4>Web Status, Session Control</h4>
<select name="webStatus" id="webStatus">
  <option value="auto" <?php echo $options['webStatus'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
  <option value="enabled" <?php echo $options['webStatus'] == 'enabled' ? 'selected' : ''; ?>>Enabled</option>
  <option value="strict" <?php echo $options['webStatus'] == 'strict' ? 'selected' : ''; ?>>Strict</option>
  <option value="disabled" <?php echo $options['webStatus'] == 'disabled' ? 'selected' : ''; ?>>Disabled</option>
</select>
<BR>Auto will automatically enable first time webLogin successful authentication occurs for a broadcaster. Will also configure the server IP restriction.
In Strict mode additional IPs can't be added by webLogin authorisation (not recommended as streaming server may have multiple IPs).
<BR>Warning: webStatus will not work on 3rd party servers without Stream Session Control configured.
<BR>Benefits of using  Stream Session Control : advanced support for external encoders like OBS (shows channels as live on site, generates snapshots, usage stats, transcoding), protect streaming server from external usage (broadcast and playback require the secret keys associated with active site channels), faster availability and updates for transcoding/snapshots.
<BR>Broadcaster can't connect at same time from web broadcasting interface and external encoder with session control (as session name will be rejected as duplicate).
<BR>Certain services or firewalls like Cloudflare may reject access of streaming server. Make sure configured web requests can be called by streaming server.

<h4>Web Status Server IP Restriction</h4>
<input name="rtmp_restrict_ip" type="text" id="rtmp_restrict_ip" size="100" maxlength="512" value="<?php echo esc_attr( $options['rtmp_restrict_ip'] ); ?>"/>
<BR>Allow status updates only from configured IP(s). If not defined will configure automatically when first successful webLogin authorisation occurs for a broadcaster. Web status will not work if this is empty or not configured right.
<BR>Some streaming servers use different IPs. All must be added as comma separated values.
				<?php

			if ( in_array( $options['webStatus'], array( 'enabled', 'strict', 'auto' ) ) )
			{
				if ( file_exists( $path = $options['uploadsPath'] . '/_rtmpStatus.txt' ) )
				{
					$url = self::path2url( $path );
					echo 'Found: <a target=_blank href="' . esc_url( $url ) . '">last status request</a> ' . date( 'D M j G:i:s T Y', filemtime( $path ) );
				}
			}
?>

<h4>Streams Path</h4>
				<?php
			if ( $options['streamsPath'] == $optionsDefault['streamsPath'] )
			{
				if ( file_exists( ABSPATH . 'streams' ) )
				{
					$options['streamsPath'] = ABSPATH . 'streams';
					echo 'Save to apply! Detected: ' . esc_html( $options['streamsPath'] ) . '<br>';
				}
			}
?>
<input name="streamsPath" type="text" id="streamsPath" size="100" maxlength="256" value="<?php echo esc_attr( $options['streamsPath'] ); ?>"/>
<BR>Used for .smil playlists (should be same as streams path configured in VideoShareVOD for RTMP delivery).
<BR>Ex: /home/[account]/public_html/streams
			
			
<h4>Legacy - RTMP Address for Archiving</h4>
				<?php
			if ( $options['rtmp_server'] != $optionsDefault['rtmp_server'] )
				{ // propagate
				if ( $options['rtmp_server_archive'] == $optionsDefault['rtmp_server_archive'] )
				{
					$options['rtmp_server_archive'] = $options['rtmp_server'];
					echo 'Save to apply! Suggested: ' . esc_html( $options['rtmp_server_archive'] ) . '<br>';
				}
			}
?>
<input name="rtmp_server_archive" type="text" id="rtmp_server_archive" size="100" maxlength="256" value="<?php echo esc_attr( $options['rtmp_server_archive'] ); ?>"/>
<BR>An address that also archives broadcasts. Recording live stream requires space and causes additional load on server. Use same RTMP address if not needed or using session control.

<h4>Legacy - RTMP Address for Recording</h4>
				<?php
			if ( $options['rtmp_server'] != $optionsDefault['rtmp_server'] )
				{ // propagate
				if ( $options['rtmp_server_record'] == $optionsDefault['rtmp_server_record'] )
				{
					$options['rtmp_server_record'] = $options['rtmp_server'] . '-record';
					echo 'Save to apply! Suggested: ' . esc_html( $options['rtmp_server_record'] ) . '<br>';
				}
			}
?>
<input name="rtmp_server_record" type="text" id="rtmp_server_record" size="100" maxlength="256" value="<?php echo esc_attr( $options['rtmp_server_record'] ); ?>"/>
<BR>An address configured for recording. Used for recording in presentation mode, if enabled by parameters.
			
			<?php

} //end wowza settings

?>

<h4>Uploads Path</h4>
<p>Path where logs and snapshots will be uploaded. Make sure you use a location outside plugin folder to avoid losing logs on updates and plugin uninstallation.</p>
<input name="uploadsPath" type="text" id="uploadsPath" size="80" maxlength="256" value="<?php echo esc_attr( $options['uploadsPath'] ); ?>"/>
				<?php
			echo '<br>Default: ' . esc_html( $optionsDefault['uploadsPath'] );
			echo '<br>WordPress Path: ' . get_home_path();
			$upload_dir = wp_upload_dir();
			echo '<br>Uploads Path: ' . esc_html( $upload_dir['basedir'] );
			if ( ! strstr( $options['uploadsPath'], get_home_path() ) )
			{
				echo '<br><b>Warning: Uploaded files may not be accessible by web.</b>';
			}
			echo '<br>WordPress URL: ' . get_site_url();
?>
<br>Windows sample path: C:/Inetpub/vhosts/yoursite.com/httpdocs/wp-content/uploads/vw_webcams


<?php
			break;

		case 'webrtc':
	

?> 
<h3>WebRTC</h3>
WebRTC live streaming requires configuring a specific server for signaling and relaying video streaming depending on network conditions.
WebRTC can be used to broadcast and playback live video streaming in HTML5 browsers with low latency. Latency can be under 1s depending on network conditions, compared to HLS from RTMP which can be have up to 10s latency because of delivery tehnology. WebRTC is recommended for interactive scenarios and 2 way calls / conferencing but provides poor scaling capacity. 
<br/>If you have a <a href="https://site2stream.com/html5/">turnkey</a> or <a href="https://webrtchost.com/hosting-plans/">streaming</a> plan (free or commercial) from VideoWhisper, go to <a href="admin.php?page=live-webcams&tab=import">Import Settings</a> to automatically fill streaming settings.

<h4>WebRTC Streaming Server</h4>
<select name="webrtcServer" id="webrtcServer">
<option value="videowhisper" <?php echo ( $options['webrtcServer'] == 'videowhisper' ) ? 'selected' : ''; ?>>VideoWhisper WebRTC</option>
<option value="wowza" <?php echo ( $options['webrtcServer'] == 'wowza' ) ? 'selected' : ''; ?>>Wowza Streaming Engine</option>
<option value="auto" <?php echo ( $options['webrtcServer'] == 'auto' ) ? 'selected' : ''; ?>>Auto</option>
</select>
<br/>At least one of the specific servers is required to live stream with this solution: VideoWhisper P2P WebRTC (recommended) or Wowza SE WebRTC relay.
<br/>VideoWhisper WebRTC currently provides WebRTC signaling for P2P streaming and supports STUN/TURN for relaying.
<br><b>Auto</b> uses Wowza SE for group chat/streaming and VideoWhisper WebRTC signaling for P2P private calls (with 2 users only). Requires both types of live streaming server hosting configured.

<?php submit_button('Save & Show'); ?>
Save to show new settings after changing server type.

<h4>WebRTC Only</h4>
<select name="webrtcOnly" id="webrtcOnly">
  <option value="0" <?php echo $options['webrtcOnly'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['webrtcOnly'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enable WebRTC only mode. Disables RTMP streaming and related features, required when only the WebRTC signaling server is available.

<?php 
if (in_array($options['webrtcServer'], array('videowhisper', 'auto'))) {
	?>
<h3>VideoWhisper WebRTC Server</h3>
<a href="https://github.com/videowhisper/videowhisper-webrtc">VideoWhisper WebRTC server</a> is a NodeJS based server that provides WebRTC signaling and can be used in combination with TURN/STUN servers. It's a great option for low latency P2P live streaming between 2 or few users but not recommended for 1 to many scenarios. In P2P streaming, broadcaster streams to each viewer which is optimal for latency, but requires a high speed connection to handle all this streaming. 
It's a new server that is still in development and is not yet recommended for production. 

<p>Get <b>Free Developers</b> or paid account from <a href="https://webrtchost.com/hosting-plans/#WebRTC-Only">WebRTC Host: P2P</a>.</p>

<h4>Address / VideoWhisper WebRTC</h4>
<input name="vwsSocket" type="text" id="vwsSocket" size="100" maxlength="256" value="<?php echo esc_attr( $options['vwsSocket'] ); ?>"/>
<BR>VideoWhisper NodeJS server address. Formatted as wss://[socket-server]:[port] . Example: wss://videowhisper.yourwebsite.com:3000

<h4>Token / VideoWhisper WebRTC </h4>
<input name="vwsToken" type="text" id="vwsToken" size="100" maxlength="256" value="<?php echo esc_attr( $options['vwsToken'] ); ?>"/>
<BR>Token (account token) for VideoWhisper WebRTC server. 

<BR>
<?php

// echo self::requirementRender( 'vwsSocket' );

			submit_button();

		//	$wsURLWebRTC_configure = self::requirementDisabled( 'wsURLWebRTC_configure' );
			// if ($wsURLWebRTC_configure) $options['webrtc'] = 0;
}

if (in_array($options['webrtcServer'], array('wowza', 'auto'))) {
?>

<h3>Wowza Streaming Engine</h3>
Wowza Streaming Engine is an industry standard streaming server, that can be used for WebRTC, RTMP, HLS, MPEG-DASH and other streaming protocols. It's a great option for 1 to many scenarios and can be used with VideoWhisper solutions for advanced features like recording, transcoding and more.
Includes both a socket signaling server and live streaming relay server services, which makes it a great option for distributing live streams to multiple viewers.

<h4>Wowza SE WebRTC WebSocket URL</h4>
<input name="wsURLWebRTC" type="text" id="wsURLWebRTC" size="100" maxlength="256" value="<?php echo esc_attr( $options['wsURLWebRTC'] ); ?>"/>
<BR>Wowza SE WebRTC WebSocket URL (wss with SSL certificate). Formatted as wss://[wowza-server-with-ssl]:[port]/webrtc-session.json .
<BR><?php 
	//echo self::requirementRender( 'wsURLWebRTC_configure' ); 
	?>
<BR>Requires a relay WebRTC streaming server  with a SSL certificate. Such setup is available with the <a href="https://webrtchost.com/hosting-plans/#Complete-Hosting" target="_vwhost">Turnkey Complete Hosting plans</a>.

<h4>Wowza SE WebRTC Application Name</h4>
<input name="applicationWebRTC" type="text" id="applicationWebRTC" size="100" maxlength="256" value="<?php echo esc_attr( $options['applicationWebRTC'] ); ?>"/>
<BR>Wowza Application Name (configured or WebRTC usage). Ex: videowhisper-webrtc
<BR>Server and application must match RTMP server settings, for streams to be available across protocols. Streams published with WebRTC can be played using advanced Flash player watch interface or as plain live video in browsers that support that.

<h4>RTSP Playback Address</h4>
<input name="rtsp_server" type="text" id="rtsp_server" size="100" maxlength="256" value="<?php echo esc_attr( $options['rtsp_server'] ); ?>"/>
<BR>For retrieving WebRTC streams. Ex: rtsp://[your-server]:1935/videowhisper-x
<BR>Access WebRTC (RTSP) stream for snapshots, transcoding for RTMP/HLS/MPEGDASH playback. Use same port 1935 as RTMP for maximum compatibility.

<h4>RTSP Publish Address</h4>
<input name="rtsp_server_publish" type="text" id="rtsp_server_publish" size="100" maxlength="256" value="<?php echo esc_attr( $options['rtsp_server_publish'] ); ?>"/>
<BR>For publishing WebRTC streams. Usually requires publishing credentials (for Wowza configured in conf/publish.password). Use same port 1935 as RTMP for maximum compatibility. Ex: rtsp://[user:password@][your-server]:1935/videowhisper-x

<h4>Video Codec</h4>
<select name="webrtcVideoCodec" id="webrtcVideoCodec">
  <option value="42e01f" <?php echo $options['webrtcVideoCodec'] == '42e01f' ? 'selected' : ''; ?>>H.264 Profile 42e01f</option>
  <option value="VP8" <?php echo $options['webrtcVideoCodec'] == 'VP8' ? 'selected' : ''; ?>>VP8</option>
 <!--

	 <option value="VP8" <?php echo $options['webrtcVideoCodec'] == 'VP8' ? 'selected' : ''; ?>>VP8</option>
  <option value="VP9" <?php echo $options['webrtcVideoCodec'] == 'VP9' ? 'selected' : ''; ?>>VP9</option>

  <option value="420010" <?php echo $options['webrtcVideoCodec'] == '420010' ? 'selected' : ''; ?>>H.264 420010</option>
  <option value="420029" <?php echo $options['webrtcVideoCodec'] == '420029' ? 'selected' : ''; ?>>H.264 420029</option>

  -->
</select>
<br>Safari supports VP8 from version 12.1 for iOS & PC and H264 in older versions. Because Safari uses hardware encoding for H264, profile may not be suitable for playback without transcoding, depending on device: VP8 is recommended when broadcasting with latest Safari. H264 can also playback directly in HLS, MPEG, Flash without additional transcoding (only audio is transcoded). Using hardware encoding (when functional) involves lower device resource usage and longer battery life.

<h4>Audio Codec</h4>
<select name="webrtcAudioCodec" id="webrtcAudioCodec">
  <option value="opus" <?php echo $options['webrtcAudioCodec'] == 'opus' ? 'selected' : ''; ?>>Opus</option>
  <option value="vorbis" <?php echo $options['webrtcAudioCodec'] == 'vorbis' ? 'selected' : ''; ?>>Vorbis</option>
</select>
<BR>Recommended: Opus.

<h4>FFMPEG Transcoding Parameters for WebRTC Playback (H264 + Opus)</h4>
<input name="ffmpegTranscodeRTC" type="text" id="ffmpegTranscodeRTC" size="100" maxlength="256" value="<?php echo esc_attr( $options['ffmpegTranscodeRTC'] ); ?>"/>
<BR>This should convert RTMP stream to H264 baseline restricted video and Opus audio, compatible with most WebRTC supporting browsers.
<br>For most browsers including Chrome, Safari, Firefox: -c:v libx264 -profile:v baseline -level 3.0 -c:a libopus -tune zerolatency
<br>For some browsers like Chrome, Firefox, not Safari, when broadcasting H264 baseline from flash client video can play as is: -c:v copy -c:a libopus

<h4>Transcode streams to WebRTC</h4>
<select name="transcodeRTC" id="transcodeRTC">
  <option value="0" <?php echo $options['transcodeRTC'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['transcodeRTC'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Make streams from Safari PC and different sources available for WebRTC playback. Involves processing resources (high CPU & memory load).

<h4>Transcode streams From WebRTC</h4>
<select name="transcodeFromRTC" id="transcodeFromRTC">
  <option value="0" <?php echo $options['transcodeFromRTC'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['transcodeFromRTC'] == '1' ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Not currently in use. Make streams from WebRTC available for HLS/MPEG/RTMP playback. Involves processing resources (high CPU & memory load).

<?php

$sessionsVars = self::varLoad( $options['uploadsPath'] . '/sessionsApp' );
			if ( is_array( $sessionsVars ) )
			{
				if ( array_key_exists( 'limitClientRateIn', $sessionsVars ) )
				{
					$limitClientRateIn = intval( $sessionsVars['limitClientRateIn'] ) * 8 / 1000;

					echo 'Detected hosting client upload limit: ' . ( $limitClientRateIn ? esc_html( $limitClientRateIn ) . 'kbps' : 'unlimited' ) . '<br>';

					$maxVideoBitrate = $limitClientRateIn - 100;
					if ( $options['webrtcAudioBitrate'] > 90 )
					{
						$maxVideoBitrate = $limitClientRateIn - $options['webrtcAudioBitrate'] - 10;
					}

					if ( $limitClientRateIn )
					{
						if ( $options['webrtcVideoBitrate'] > $maxVideoBitrate )
						{
							echo '<b>Warning: Adjust bitrate to prevent disconnect / failure.<br>Video bitrate should be 100kbps lower than total upload so it fits with audio and data added. Save to apply!</b><br>';
							$options['webrtcVideoBitrate'] = $maxVideoBitrate;
						}
					}
				}
			}

//end Wowza settings
}
?>

<h4>Maximum Video Bitrate</h4>

<input name="webrtcVideoBitrate" type="text" id="webrtcVideoBitrate" size="10" maxlength="16" value="<?php echo esc_attr( $options['webrtcVideoBitrate'] ); ?>"/> kbps
<BR>Ex: 800. Max 400 for TCP. HTML5 Videochat app will adjust default bitrate and options depending on selected resolution. Very high bitrate setting may be discarded by browsers or result in failures or interruptions due to user connection limits. Application may have lower restrictions. Default: <?php echo esc_html( $optionsDefault['webrtcVideoBitrate'] ); ?>
<br>If streaming hosting upload is limited, video bitrate should be 100kbps lower than total upload so it fits with audio and data added. Trying to broadcast higher will result in disconnect/failure.


<h4>Maximum Audio Bitrate</h4>
<input name="webrtcAudioBitrate" type="text" id="webrtcAudioBitrate" size="10" maxlength="16" value="<?php echo esc_attr( $options['webrtcAudioBitrate'] ); ?>"/> kbps
<br>Ex: 64 or 72, 96 Default: <?php echo esc_html( $optionsDefault['webrtcAudioBitrate'] ); ?>
<br>If client upload bitrate is limited, using higher audio bitrate also involves reducing maximum video bitrate.

				<?php
			break;

		case 'hls':
			// ! HLS & Transcoding
?>
<h3>FFmpeg / HLS / Recording / Push RTMP</h3>
Enable: Stream Snapshots, Recording, Transcoding, HTML5 HLS / MPEG-Dash Delivery, HTML5 Videochat Recording
<br>FFmpeg is required to manage streams and videos:
<br>- extract snapshots from streams (to show in listings), from HTML5 interface and external broadcasters as OBS/GoCoder
<br>- record streams, on request, in adaptive video containers
<br>- push stream to a 3rd party RTMP live stream platform (like Twitch, YouTube, Facebook, Twitter)
<br>- stream codec analysis (to detect if transcoding is necessary)
<br>- transcode stream from legacy Flash interface for mobile playback as HTML5 HLS/MPEG-DASH

<h4>FFmpeg</h4>
Recommended Hosting: <a href="https://webrtchost.com/hosting-plans/#Complete-Hosting" target="_vwhost">Complete Turnkey Streaming Hosting</a> - turnkey streaming for all protocols, configuration for archiving, transcoding streams, delivery to mobiles as HLS, WebRTC.

<h4>Server Command Execution</h4>
<select name="enable_exec" id="enable_exec">
  <option value="0" <?php echo $options['enable_exec'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['enable_exec'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<BR>By default, all features that require executing server commands are disabled, for security reasons. Enable only after making sure your server is configured to safely execute server commands like FFmpeg. If you have own server, isolation is recommended with <a href="https://docs.cloudlinux.com/cloudlinux_os_components/#cagefs">CageFS</a> or similar tools.
<BR>
				<?php

			if ( $options['enable_exec'] )
			{

				$fexec = 0;
				echo '<br>- exec: ';
				if ( function_exists( 'exec' ) )
				{
					echo 'function is enabled';

					if ( exec( 'echo EXEC' ) == 'EXEC' )
					{
						echo ' and works';
						$fexec = 1;
					} else
					{
						echo ' <b>but does not work</b>';
					}
				} else
				{
					echo '<b>function is not enabled</b><BR>PHP function "exec" is required to run FFMPEG. Current hosting settings are not compatible with this functionality.';
					self::requirementUpdate( 'ffmpeg', 0 );

				}

				if ( $fexec )
				{
					$output = '';
					echo '<BR>- FFMPEG: ';
					$cmd = sanitize_text_field( $options['ffmpegPath'] ) . ' -version';
					if ( $options['enable_exec'] )
					{
						exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					}
					if ( $returnvalue == 127 )
					{
						echo '<b>Warning: not detected: ' . esc_html( $cmd ) . '</b>';
						self::requirementUpdate( 'ffmpeg', 0 );
					} else
					{
						echo 'found';

						if ( $returnvalue != 126 )
						{
							if ( ! $output )
							{
								echo '<b>Error: No output from FFMPEG, no codecs detected!</b>';
							} else
							{
								self::requirementUpdate( 'ffmpeg', 1 );
							}
						} else
						{
							echo ' but is NOT executable by current user: ' . esc_html( $processUser );
							self::requirementUpdate( 'ffmpeg', 0 );
						}
					}
				}
?>
<BR><?php echo self::requirementRender( 'ffmpeg' ); ?>

<h4>Live Transcoding</h4>
					<?php

				$processUser = get_current_user();
				echo "This section shows transcoding and snapshot retrieval processes currently run by account '". esc_html( $processUser ) . "'.<BR>";

				if ( $fexec )
				{
					$cmd = "ps aux | grep 'ffmpeg'";
					if ( $options['enable_exec'] )
					{
						$process = exec( escapeshellcmd( $cmd ), $output, $returnvalue );
					}

					// $processp = explode(' ', $process);
					// $processId = $processp[3];

					// var_dump($processId);

					$transcoders = 0;
					foreach ( $output as $line )
					{
						if ( strstr( $line, 'ffmpeg' ) )
						{
							$columns = preg_split( '/\s+/', $line );
							if ( $processUser == $columns[0] && ( ! in_array( $columns[10], array( 'sh', 'grep' ) ) ) )
							{

								echo 'Process #' . esc_html( $columns[1] ) . ' CPU: ' . esc_html( $columns[2] ) . ' Mem: ' . esc_html( $columns[3] ) . ' Start: ' . esc_html( $columns[8] ) . ' CPU Time: ' . esc_html( $columns[9] ) . ' Cmd: ';
								for ( $n = 10; $n < 24; $n++ )
								{
									echo esc_html( $columns[ $n ] ) . ' ';
								}

								if ( $_GET['kill'] == $columns[1] )
								{
									$kcmd = 'kill -KILL ' . escapeshellarg( sanitize_text_field( $columns[1] ) );
									if ( $options['enable_exec'] )
									{
										exec( $kcmd, $koutput, $kreturnvalue );
									}
									echo ' <B>Killing process...</B>';
								} else
								{
									echo ' <a href="admin.php?page=live-webcams&tab=hls&kill=' . esc_attr( $columns[1] ) . '">Kill</a>';
								}

								echo '<br>';
								$transcoders++;
							}
						}
					}

					if ( ! $transcoders )
					{
						echo 'No live transcoding/snapshot processes detected.';
					} else
					{
						echo '<BR>Total processes for transcoding/snapshot: ' . esc_html( $transcoders );
					}

					self::processTimeout( 'ffmpeg', true, true );
				}

				$ffmpegDisabled = self::requirementDisabled( 'ffmpeg' );
				
				if ( $ffmpegDisabled )
				{
					$options['transcoding'] = 0;
				}
?>

<h4>FFMPEG Path</h4>
<input name="ffmpegPath" type="text" id="ffmpegPath" size="100" maxlength="256" value="<?php echo esc_attr( $options['ffmpegPath'] ); ?>"/>
<BR> Path to latest FFMPEG. Required for transcoding of web based streams, generating snapshots for external broadcasting applications (requires <a href="https://videowhisper.com/?p=RTMP-Session-Control">rtmp session control</a> to notify plugin about these streams).
					<?php

				if ( $options['enable_exec'] )
				{
					if ( $fexec )
					{
						echo '<br>FFMPEG path detection (which ffmpeg): ';
						$cmd    = 'which ffmpeg';
						$output = '';

						if ( $options['enable_exec'] )
						{
							exec( escapeshellcmd( $cmd ), $output, $returnvalue );
						}

						foreach ( $output as $outp )
						{
							echo esc_html( $outp );
						}

						if ( is_array($output) && count($output) && $output[0] != $options['ffmpegPath'] )
						{
							echo 'which ffmpeg Warning - Different path:' . esc_html( $options['ffmpegPath'] );
							//var_dump( $output);
						}

						$cmd = sanitize_text_field( $options['ffmpegPath'] ) . ' -codecs';
						if ( $options['enable_exec'] )
						{
							exec( escapeshellcmd( $cmd ), $output, $returnvalue );
						}

						// detect codecs
						$hlsAudioCodec = ''; // hlsAudioCodec
						if ( $output )
						{
							if ( count( $output ) )
							{
								echo '<br>Codec libraries: ';
								foreach ( array( 'h264', 'vp6', 'speex', 'nellymoser', 'aacplus', 'vo_aacenc', 'faac', 'fdk_aac', 'vp8', 'vp9', 'opus' ) as $cod )
								{
									$det  = 0;
									$outd = '';
									echo '<BR>' . esc_html( "$cod : " );
									foreach ( $output as $outp )
									{
										if ( strstr( $outp, $cod ) )
										{
											$det  = 1;
											$outd = $outp;
										}
									};

									if ( $det )
									{
										echo esc_html( "detected ($outd)" );
									} elseif ( in_array( $cod, array( 'aacplus', 'vo_aacenc', 'faac', 'fdk_aac' ) ) )
									{
										echo esc_html( "lib$cod is missing but other aac codec may be available" );
									} else
									{
										echo esc_html( "missing: configure and install FFMPEG with lib$cod if you don't have another library for that codec" );
									}

									if ( $det && in_array( $cod, array( 'aacplus', 'vo_aacenc', 'faac', 'fdk_aac' ) ) )
									{
										$hlsAudioCodec = 'lib' . $cod;
									}
								}
							}
						}
					}
				}
?>
<BR>You need only 1 AAC codec. Depending on <a href="https://trac.ffmpeg.org/wiki/Encode/AAC#libfaac">AAC library available on your system</a> you may need to update transcoding parameters. Latest FFMPEG also includes a native encoder (aac).

<h4>FFMPEG Codec Configuration</h4>
<select name="ffmpegConfiguration" id="ffmpegConfiguration">
  <option value="0" <?php echo $options['ffmpegConfiguration'] ? '' : 'selected'; ?>>Manual</option>
  <option value="1" <?php echo $options['ffmpegConfiguration'] == 1 ? 'selected' : ''; ?>>Auto</option>
</select>
<BR>Auto will configure based on detected AAC codec libraries (recommended). Requires saving settings to apply.

					<?php
				$hlsAudioCodecReadOnly = '';

				if ( $options['ffmpegConfiguration'] )
				{
					if ( ! $hlsAudioCodec )
					{
						$hlsAudioCodec = 'aac';
					}
					$options['ffmpegTranscode'] = "-c:v copy -c:a $hlsAudioCodec -b:a 96k -tune zerolatency ";
					$hlsAudioCodecReadOnly      = 'readonly';

					$options['pushTranscode'] = "-c:v libx264 -c:a $hlsAudioCodec";

				}
?>

<h4>HLS FFMPEG Transcoding Parameters</h4>
<input name="ffmpegTranscode" type="text" id="ffmpegTranscode" size="100" maxlength="256" value="<?php echo esc_attr( $options['ffmpegTranscode'] ); ?>" <?php echo esc_attr( $hlsAudioCodecReadOnly ); ?>/>
<BR>For lower server load and higher performance, web clients should be configured to broadcast video already suitable for target device (H.264 Baseline 3.1 for most iOS devices) so only audio needs to be encoded.
<BR>Ex.(transcode audio for iOS using latest FFMPEG with libfdk_aac): -c:v copy -c:a libfdk_aac -b:a 96k
<BR>Ex.(transcode audio for iOS using latest FFMPEG with native aac): -c:v copy -c:a aac -b:a 96k
<BR>Ex.(transcode audio for iOS using older FFMPEG with libfaac): -vcodec copy -acodec libfaac -ac 2 -ar 22050 -ab 96k
<BR>Ex.(transcode video+audio): -vcodec libx264 -s 480x360 -r 15 -vb 512k -x264opts vbv-maxrate=364:qpmin=4:ref=4 -coder 0 -bf 0 -analyzeduration 0 -level 3.1 -g 30 -maxrate 768k -acodec libfaac -ac 2 -ar 22050 -ab 96k
<BR>For advanced settings see <a href="https://developer.apple.com/library/ios/technotes/tn2224/_index.html#//apple_ref/doc/uid/DTS40009745-CH1-SETTINGSFILES">iOS HLS Supported Codecs<a> and <a href="https://trac.ffmpeg.org/wiki/Encode/AAC">FFMPEG AAC Encoding Guide</a>.

<h4>Push RTSP to RTMP Transcoding Parameters</h4>
<input name="pushTranscode" type="text" id="pushTranscode" size="100" maxlength="256" value="<?php echo esc_attr( $options['pushTranscode'] ); ?>" <?php echo esc_attr( $hlsAudioCodecReadOnly ); ?>/>
<br>Pushing RTSP (WebRTC) streams to RTMP requires suitable codecs.

<h4>Push Streams to RTMP</h4>
<select name="pushStreams" id="pushStreams">
  <option value="0" <?php echo $options['pushStreams'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['pushStreams'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<BR>Allow room owners to add destinations to push performer stream while broadcasting. This is processing intensive for web hosting as FFmpeg transcoding is required to convert WebRTC stream codecs to RTMP.
<BR>To test this feature on <a href="https://webrtchost.com/hosting-plans/">WebRTC Host Complete plans</a>, you can push stream to the RTMP app configured for recording like rtmp://[account_ip]/[account_name]-record/test and you should obtain a test.flv file in streams folder.
<BR>Requirements: Stream Session Control need to be configured (to trigger push when stream is detected) and latest FFmpeg supporting HTML5 codecs.
<BR>Recommended: Disabled

<h4>RTP Snapshots</H4>
<select name="rtpSnapshots" id="rtpSnapshots" >
  <option value="0" <?php echo $options['rtpSnapshots'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['rtpSnapshots'] == 1 ? 'selected' : ''; ?>>Enabled</option>
</select>
<br/>Recommended: Disabled, as latest HTML5 Videochat app generates snapshots client side and and uploads to server. Extracting snapshots server side is only required for external streams, including from OBS, IP cameras, specific to Wowza SE.

<h4>FFmpeg Recording</h4>
<select name="recording" id="recording">
  <option value="0" <?php echo $options['recording'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['recording'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<br>Performers can toggle stream recording from Settings panel in HTML5 Videochat advanced interface and defaults are configured from <a href="admin.php?page=live-webcams&tab=app">HTML5 Videochat Settings</a> .
<br>Streams are recorded in adaptive formats (webm/mp4) depending on broadcast (WebRTC/RTMP) and files are stored in room uploads folder. Recordings be published to site with VideoShareVOD plugin from Videos tab, Import in Performer Dashboard.
<br>Warning: Recoding is resource intensive using lots of memory, disk writing and can also fill lots of space fast. Use this to disable background stream recording and leave more resources for handling requests (higher site capacity).
<br>Recommended: Disabled

<h4>Recordings Stack</h4>
<select name="recordingStack" id="recordingStack">
  <option value="0" <?php echo $options['recordingStack'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['recordingStack'] == '1' ? 'selected' : ''; ?>>Yes</option>
  <option value="both" <?php echo $options['recordingStack'] == 'both' ? 'selected' : ''; ?>>Both</option>
</select>
<br>Save stream recordings side by side in same file (for conferencing). Both saves both individually and in stack, for increased reliability, using more resources.

<h4>Enable HTML5 Transcoding</h4>
<select name="transcoding" id="transcoding" <?php echo esc_attr( $ffmpegDisabled ); ?>>
  <option value="0" <?php echo $options['transcoding'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="3" <?php echo $options['transcoding'] == 3 ? 'selected' : ''; ?>>Adaptive</option>
</select>
<BR>Recommended: Disabled. This enables account level transcoding based on FFmpeg but is no longer required for regular solution usage.

<h4>External Transcoder Keys</h4>
<select name="externalKeysTranscoder" id="externalKeysTranscoder">
  <option value="0" <?php echo $options['externalKeysTranscoder'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['externalKeysTranscoder'] ? 'selected' : ''; ?>>Yes</option>
</select>
<BR>Direct authentication parameters will be used for transcoder, external stream thumbnails in case webLogin is enabled.
<br>In HTML5 Videochat there is an external_rtmp setting to include a Broadcast tab with instructions to broadcast with external RMTP encoders.

<h4>Web Key</h4>
<input name="webKey" type="text" id="webKey" size="32" maxlength="64" value="<?php echo esc_attr( $options['webKey'] ); ?>"/>
<BR>A web key can be used for <a href="https://videochat-scripts.com/videowhisper-rtmp-web-authetication-check/">VideoWhisper RTMP Web Session Check</a> - not currently implemented.

<h4>RTMP Snapshot Rest</h4>
<input name="rtmpSnapshotRest" type="text" id="rtmpSnapshotRest" size="32" maxlength="64" value="<?php echo esc_attr( $options['rtmpSnapshotRest'] ); ?>"/>
<br>Minimum time to wait before refreshing a snapshot by RTMP.

					<?php

			}//enable_exec

			break;

		case 'support':
			// ! Support

			self::requirementMet( 'resources' );

?>

<h3>Support Resources</h3>
Solution resources: documentation, tutorials, support.
<p><a href="admin.php?page=live-webcams-doc" class="button primary" >Documentation</a> | <a href="https://videowhisper.com/tickets_submit.php?topic=PaidVideochat+Plugin" class="button primary" >Contact VideoWhisper</a></p>


<h3>Hosting Requirements</h3>
<UL>
<LI><a href="https://videowhisper.com/?p=Requirements">Hosting Requirements</a> This advanced software requires web hosting and HTML5 live streaming hosting.</LI>
<LI><a href="https://webrtchost.com/hosting-plans/">Recommended Hosting</a> Turnkey, convenient and cost effective plans (compared to setting up own live streaming servers).</LI></UL>

<h3>Solution Documentation</h3>
<UL>
<LI><a href="admin.php?page=live-webcams-doc">Backend Documentation</a> Includes tutorial with local links to configure main features, menus, pages.</LI>
<LI><a href="https://paidvideochat.com/features/quick-setup-tutorial/">PaidVideochat Tutorial</a> Setup a turnkey ppv live videochat site.</LI>
<LI><a href="https://videowhisper.com/?p=WordPress-PPV-Live-Webcams">VideoWhisper Plugin Homepage</a> Plugin and application documentation.</LI>
</UL>

<a name="plugins"></a>

<h3>Feature Integration Plugins (Recommended)</h3>

<UL>
<LI><a href="https://wordpress.org/plugins/video-share-vod/">Video Share VOD</a> Add webcam room videos, teaser video, videos for sale.</LI>
<li><a href="https://wordpress.org/plugins/rate-star-review/" title="Rate Star Review - AJAX Reviews for Content with Star Ratings">Rate Star Review – AJAX Reviews for Content with Star Ratings</a> plugin, integrated for webcam reviews and ratings.</li>
<LI><a href="https://wordpress.org/plugins/picture-gallery/">Picture Gallery</a> Add performer picture galleries, automated snapshots from shows.</LI>
<LI><a href="https://wordpress.org/plugins/paid-membership/">MicroPayments - Paid Content, Membership and Donations</a> Sell videos (per item) from frontend, sell membership subscriptions. Based on MyCred / TeraWallet WooCommerce tokens that can be purchased with real money gateways or earned on site.</LI>
<li><a href="https://wordpress.org/plugins/mycred/">myCRED</a> and/or <a href="https://wordpress.org/plugins/woo-wallet/">WooCommerce TeraWallet</a>, integrated for tips.  Configure as described in Tips settings tab.</li>
<LI><a href="https://wordpress.org/plugins/video-posts-webcam-recorder/">Webcam Video Recorder</a> Site users can record videos from webcam. Can also be used to setup reaction recording: record webcam while playing an Youtube video.</LI>
</UL>

<h3>Optimization Plugins (Recommended)</h3>
<UL>
<li><a href="https://wordpress.org/plugins/wp-super-cache/">WP Super Cache</a> configured ONLY for visitors, disabled for known users or requests with GET parameters, great for protecting against bot or crawlers eating up site resources). Do NOT enable compression on dynamic sites as provided by these solutions, as that causes unnecessary overhead, including performance degradation, high resource usage.</li>
<li><a href="https://wordpress.org/plugins/wordfence/">WordFence</a> plugin with firewall. Configure to protect by limiting failed login attempts, bot attacks / flood request, scan for malware or vulnerabilities. In WordFence, enable reCAPTCHA from <a href="<?php echo get_admin_url(); ?>wp-admin/admin.php?page=WFLS#top#settings">WordFence Login Settings</a> after getting a <a href="https://www.google.com/recaptcha/admin/create">free reCaptcha v3 key</a>.</li>
<li>HTTPS redirection plugin like <a href="https://wordpress.org/plugins/really-simple-ssl/">Really Simple SSL</a>&nbsp;, if you have a SSL certificate and HTTPS configured (as on VideoWhisper plans). HTTPS is required to broadcast webcam, in latest browsers like Chrome. If you also use HTTP urls (not recommended), disable “Auto replace mixed content” option to avoid breaking external HTTP urls (like HLS).</li>
<li>A <a href="https://wordpress.org/plugins/search/smtp/">SMTP mailing plugin</a> and setup a real email account from your hosting backend (setup an email from CPanel) or external (Gmail or other provider), to send emails using SSL and all verifications. This should reduce incidents where users don’t find registration emails due to spam filter triggering. Also instruct users to check their spam folders if they don’t find registration emails. 
	 <li>For basic search engine indexing, make sure your site does not discourage search engine bots from Settings &gt; Reading   (discourage search bots box should not be checked).
Then install a plugin like <a href="https://wordpress.org/plugins/google-sitemap-generator/">Google XML Sitemaps</a> for search engines to quickly find main site pages.</li>
	 <li>For sites with adult content, an <a href="https://wordpress.org/plugins/tags/age-verification/">age verification / confirmation plugin</a> should be deployed. Such sites should also include a page with details for 18 U.S.C. 2257 compliance. For other suggestions related to adult sites, see <a href="https://paidvideochat.com/adult-videochat-business-setup/">Adult Videochat Business Setup</a>.</li>
</UL>

<h3>Turnkey Features Plugins</h3>
<ul>
	 <li><a href="https://woocommerce.com/?aff=18336&amp;cid=2828082">WooCommerce</a> : <em>ecommerce</em> platform</li>
	 <li><a href="https://buddypress.org/">BuddyPress</a> : <em>community</em> (member profiles, activity streams, user groups, messaging)</li>
	 <li><a href="https://woocommerce.com/products/sensei/?aff=18336&amp;cid=2828082">Sensei LMS</a> : <em>learning</em> management system</li>
	 <li><a href="https://bbpress.org/">bbPress</a>: clean discussion <em>forums</em></li>
</ul>


<h3>Premium Plugins / Addons</h3>
<ul>
	<LI><a href="http://themeforest.net/popular_item/by_category?category=wordpress&ref=videowhisper">Premium Themes</a> Professional WordPress themes.</LI>
	<LI><a href="https://woocommerce.com/products/woocommerce-memberships/?aff=18336&amp;cid=2828082">WooCommerce Memberships</a> Setup paid membership as products. Leveraged with Subscriptions plugin allows membership subscriptions.</LI>

	<LI><a href="https://woocommerce.com/products/woocommerce-subscriptions/?aff=18336&amp;cid=2828082">WooCommerce Subscriptions</a> Setup subscription products, content. Leverages Membership plugin to setup membership subscriptions.</LI>

<li><a href="https://woocommerce.com/products/woocommerce-bookings/?aff=18336&amp;cid=2828082">WooCommerce Bookings</a> Setup booking products with calendar, <a href="https://woocommerce.com/products/bookings-availability/?aff=18336&amp;cid=2828082">availability</a>, <a href="https://woocommerce.com/products/woocommerce-deposits/?aff=18336&amp;cid=2828082">booking deposits</a>, confirmations for 1 on 1 or group bookings. Include performer room link.</li>

	<LI><a href="https://woocommerce.com/products/follow-up-emails/?aff=18336&amp;cid=2828082">WooCommerce Follow Up</a> Follow Up by emails and twitter automatically, drip campaigns.</LI>

		<LI><a href="https://woocommerce.com/products/product-vendors/?aff=18336&amp;cid=2828082">WooCommerce Product Vendors</a> Allow multiple vendors to sell via your site and in return take a commission on sales. Leverage with <a href="https://woocommerce.com/products/woocommerce-product-reviews-pro/?aff=18336&amp;cid=2828082">Product Reviews Pro</a>.</LI>

<li><a href="https://woocommerce.com/products/woocommerce-order-status-control/?aff=18336&amp;cid=2828082">Order Status Control</a> Control which Paid WooCommerce Orders are Automatically Completed so you don't have to manually Process payments. Order processing is required to get tokens allocated automatically when using TeraWallet and also to enable access for content purchased using the MicroPayments integration for selling content as WooCommerce products.</li>
</ul>

<h3>Contact and Feedback</h3>
<a href="https://videowhisper.com/tickets_submit.php?topic=PaidVideochat+Plugin">Sumit a Ticket</a> with your questions, inquiries and VideoWhisper support staff will try to address these as soon as possible.
<br>VideoWhisper  can clarify requirements, features, installation steps or suggest additional services like customisations, hosting you may need for your project.

<h3>Review and Discuss</h3>
You can publicly <a href="https://wordpress.org/support/view/plugin-reviews/ppv-live-webcams">review this WP plugin</a> on the official WordPress site (after <a href="https://wordpress.org/support/register.php">registering</a>). You can describe how you use it and mention your site for visibility. You can also post on the <a href="https://wordpress.org/support/plugin/ppv-live-webcams">WP support forums</a> - these are not monitored by support so use a <a href="https://videowhisper.com/tickets_submit.php?topic=PaidVideochat+Plugin">ticket</a> if you want to contact VideoWhisper.

<h3>News and Updates on Social Media</h3>
Follow updates using <a href="https://twitter.com/videowhisper"> Twitter </a>, <a href="https://www.facebook.com/VideoWhisper"> Facebook </a>.


				<?php
			break;

		case 'translate':
		
?>

<h3>MultiLanguage & Translations</h3>
HTML5 Videochat integrates <a href="https://www.deepl.com/en/whydeepl" target="_vw">DeepL</a> API for live chat text translations. Static texts can be translated as rest of WP plugins.

<h4>Multilanguage Chat</h4>
<select name="multilanguage" id="multilanguage">
  <option value="0" <?php echo $options['multilanguage'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['multilanguage'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<br>Enable users to specify language they are using in text chat.

<H4>DeepL API Key</H4>
<input name="deepLkey" type="text" id="deepLkey" size="100" maxlength="256" value="<?php echo esc_attr( $options['deepLkey'] ); ?>"/>
<br>Register a <a href="https://www.deepl.com/pro-checkout/account?productId=1200&yearly=false&trial=false">free DeepL developer account</a> to get a key. After login, you can retrieve your key from <a href="https://www.deepl.com/account/summary" target="_vw">DeepL account > Authentication Key for DeepL API</a>. For high activity sites, a paid account may be required depending on translation volume. Keep your key secret to prevent unauthorized usage.

<h4>Chat Translations</h4>
<select name="translations" id="translations">
  <option value="0" <?php echo $options['translations'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="registered" <?php echo $options['translations'] == 'registered' ? 'selected' : ''; ?>>Registered</option>
  <option value="all" <?php echo $options['translations'] == 'all' ? 'selected' : ''; ?>>All Users</option>
</select>
<br>Enable translations for everybody or just registered users.

<h4>Default Language</h4>
<select name="languageDefault" id="languageDefault">
<?php
$languages = get_option( 'VWdeepLlangs' );
//list languages as options	
if ( !$languages ) echo '<option value="en-us" selected>English (American)</option>';
else foreach ( $languages as $key => $value )
{
	echo '<option value="'. esc_attr($key) .'" ' . ( $options['languageDefault'] == $key ? 'selected' : '' ) . '>' . esc_html($value) . '</option>';
}
?>
</select>
<br>Default language of site users. This will be used for translations if user does not specify a language.

<?php submit_button(); ?>

<H4>Supported Languages</H4>
<?php 
if ( !$languages )
{
		echo 'First runs. Setting default languages. ';
	    update_option( 'VWdeepLlangs', unserialize( 'a:31:{s:2:"bg";s:9:"Bulgarian";s:2:"cs";s:5:"Czech";s:2:"da";s:6:"Danish";s:2:"de";s:6:"German";s:2:"el";s:5:"Greek";s:5:"en-gb";s:17:"English (British)";s:5:"en-us";s:18:"English (American)";s:2:"es";s:7:"Spanish";s:2:"et";s:8:"Estonian";s:2:"fi";s:7:"Finnish";s:2:"fr";s:6:"French";s:2:"hu";s:9:"Hungarian";s:2:"id";s:10:"Indonesian";s:2:"it";s:7:"Italian";s:2:"ja";s:8:"Japanese";s:2:"ko";s:6:"Korean";s:2:"lt";s:10:"Lithuanian";s:2:"lv";s:7:"Latvian";s:2:"nb";s:9:"Norwegian";s:2:"nl";s:5:"Dutch";s:2:"pl";s:6:"Polish";s:5:"pt-br";s:22:"Portuguese (Brazilian)";s:5:"pt-pt";s:21:"Portuguese (European)";s:2:"ro";s:8:"Romanian";s:2:"ru";s:7:"Russian";s:2:"sk";s:6:"Slovak";s:2:"sl";s:9:"Slovenian";s:2:"sv";s:7:"Swedish";s:2:"tr";s:7:"Turkish";s:2:"uk";s:9:"Ukrainian";s:2:"zh";s:20:"Chinese (simplified)";}' ) );
		
		$languages = get_option( 'VWdeepLlangs', true);
		
}
echo esc_html( wp_json_encode( $languages ) );
?>
<br><a class="button secondary" target="_vw" href="<?php echo plugins_url('ppv-live-webcams/server/translate.php?update_languages=videowhisper'); ?>">Update Supported Languages</a>
<br>This will retrieve latest list of supported languages from DeepL API, if a valid key is available.

<h4>Translation for Solution Features, Pages, HTML5 Videochat</h4>
Translate solution in different language. Software is composed of applications and integration code (plugin) that shows features on WP pages.

This plugin is translation ready and can be easily translated started from 'pot' file from languages folder. You can translate for own use and also <a href="https://translate.wordpress.org/projects/wp-plugins/ppv-live-webcams/">contributing translations</a>.

<br>Sample translations for plugin are available in "languages" plugin folder and you can edit/adjust or add new languages using a translation plugin like <a href="https://wordpress.org/plugins/loco-translate/">Loco Translate</a> : From Loco Translate > Plugins > Paid Videochat Turnkey Site - HTML5 PPV Live Webcams you can edit existing languages or add new languages.
<br>You can also start with an automated translator application like Poedit, translate more texts with Google Translate and at the end have a human translator make final adjustments. You can contact VideoWhisper support and provide links to new translation files if you want these included in future plugin updates.

<BR>Some customizable labels, custom content and features can be translated from plugin settings, including tabs like <a href="admin.php?page=live-webcams&tab=listings">Cam Listings</a>, <a href="admin.php?page=live-webcams&tab=profile">Profile Fields</a>, <a href="admin.php?page=live-webcams&tab=record">Account Records</a>, <a href="admin.php?page=live-webcams&tab=group">Group Modes</a>.

				<?php
			break;

		case 'integration':
			// ! Integration Settings
			// preview
			global $current_user;
			get_currentuserinfo();

			$options['custom_post'] = sanitize_title_with_dashes( $options['custom_post'] );

?>

<h3>General Integration Settings</h3>
Customize WordPress integration options for videochat application, including multiple hooks. Each room has an associated webcam listing (custom WP post type).

<h4>Frontend Registration</h4>
<select name="registerFrontend" id="registerFrontend">
  <option value="1" <?php echo $options['registerFrontend'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['registerFrontend'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Enable special registration pages for client/performer/studio in frontend. User fills password and activation is by secret link sent to registration email.
<br>Setup from <a href="admin.php?page=live-webcams&tab=pages">Pages</a>.
<br>Warning: Disable <a href="options-general.php">default WordPress registration</a> if you want to use only the frontend registration/activation pages.

<h4>Anonymous Frontend Registration</h4>
<select name="registrationAanonymous" id="registrationAanonymous">
  <option value="" <?php echo $options['registrationAanonymous'] == '' ? 'selected' : ''; ?>>No</option>
  <option value="client" <?php echo $options['registrationAanonymous'] == 'client' ? 'selected' : ''; ?>>Client</option>
  <option value="all" <?php echo $options['registrationAanonymous'] == 'all' ? 'selected' : ''; ?>>All</option>
</select>
<br>Hide first name and last name WP fields from registration form. WP first name will be filled with login (for display).

<?php
			$options['activateSubject'] = stripslashes( $options['activateSubject'] );
			$options['activateText']    = stripslashes( $options['activateText'] ) ;
?>

<h4>Google reCAPTCHA v3: Site Key</h4>
<input name="recaptchaSite" type="text" id="recaptchaSite" size="100" maxlength="256" value="<?php echo esc_attr( $options['recaptchaSite'] ); ?>"/>
<br>Register your site for free for using <a href="https://www.google.com/recaptcha/admin/create">Google reCAPTCHA v3</a> to protect your site from spam bot registrations and brute force login attacks.
<br>Warning: For effectiveness of this feature, any other registration/login methods or forms, including default WP registration, should be disabled.

<h4>Google reCAPTCHA v3: Secret Key</h4>
<input name="recaptchaSecret" type="text" id="recaptchaSite" size="100" maxlength="256" value="<?php echo esc_attr( $options['recaptchaSecret'] ); ?>"/>

<h4>Activation Email Subject</h4>
<input name="activateSubject" type="text" id="activateSubject" size="100" maxlength="256" value="<?php echo esc_attr( $options['activateSubject'] ); ?>"/>
<br>An activation email is sent when using solution registration method where user also chooses password on registration. Default: <?php echo esc_html( $optionsDefault['activateSubject'] ); ?>

<h4>Activation Email Text</h4>
<textarea name="activateText" id="activateText" cols="100" rows="3"><?php echo esc_textarea( $options['activateText'] ); ?></textarea>

<h4>Registration Message</h4>
<textarea name="activateMessage" id="activateMessage" cols="100" rows="3"><?php echo esc_textarea( $options['activateMessage'] ); ?></textarea>
<br>Message displayed after activation email is sent. Default: <?php echo esc_html( $optionsDefault['activateMessage'] ); ?>


<h4>Frontend Registration Without Email Activation</h4>
<select name="registrationNoActivation" id="registrationNoActivation">
  <option value="" <?php echo $options['registrationNoActivation'] == '' ? 'selected' : ''; ?>>No</option>
  <option value="client" <?php echo $options['registrationNoActivation'] == 'client' ? 'selected' : ''; ?>>Client</option>
  <option value="all" <?php echo $options['registrationNoActivation'] == 'all' ? 'selected' : ''; ?>>All</option>
</select>
<br>Do not require email activation for registration. Not recommended as it can result in bulk registrations of bots or scam accounts. Configure Google reCAPTCHA v3 before using this.


<h4>Password Change Email Subject</h4>
<input name="passwordSubject" type="text" id="passwordSubject" size="100" maxlength="256" value="<?php echo esc_attr( $options['passwordSubject'] ); ?>"/>
<br>An activation email is sent when using solution registration method where user also chooses password on registration. Default: <?php echo esc_html( $optionsDefault['passwordSubject'] ); ?>

<h4>Password Change Email Text</h4>
<textarea name="passwordText" id="passwordText" cols="100" rows="3"><?php echo esc_textarea( $options['passwordText'] ); ?></textarea>

<h4>Failed Login Attempts Protection</h4>
<select name="maxLoginAttempts" id="maxLoginAttempts">
  <option value="3" <?php echo $options['maxLoginAttempts'] == '3' ? 'selected' : ''; ?>>3 Attempts</option>
  <option value="5" <?php echo $options['maxLoginAttempts'] == '5' || empty($options['maxLoginAttempts']) ? 'selected' : ''; ?>>5 Attempts</option>
  <option value="10" <?php echo $options['maxLoginAttempts'] == '10' ? 'selected' : ''; ?>>10 Attempts</option>
  <option value="0" <?php echo $options['maxLoginAttempts'] === '0' ? 'selected' : ''; ?>>Disabled</option>
</select>
<br>Limit failed login attempts to prevent brute force attacks. Users will be blocked temporarily after exceeding this limit.

<h4>Login Lockout Duration</h4>
<select name="loginLockoutTime" id="loginLockoutTime">
  <option value="900" <?php echo $options['loginLockoutTime'] == '900' ? 'selected' : ''; ?>>15 Minutes</option>
  <option value="1800" <?php echo $options['loginLockoutTime'] == '1800' ? 'selected' : ''; ?>>30 Minutes</option>
  <option value="3600" <?php echo $options['loginLockoutTime'] == '3600' || empty($options['loginLockoutTime']) ? 'selected' : ''; ?>>1 Hour</option>
  <option value="7200" <?php echo $options['loginLockoutTime'] == '7200' ? 'selected' : ''; ?>>2 Hours</option>
  <option value="86400" <?php echo $options['loginLockoutTime'] == '86400' ? 'selected' : ''; ?>>24 Hours</option>
</select>
<br>Time period during which login attempts will be blocked after too many failed attempts.

<h4>Frontend Login</h4>
<select name="loginFrontend" id="loginFrontend">
  <option value="1" <?php echo $options['loginFrontend'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['loginFrontend'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Allows login from frontend with a special shortcode and dedicated page (if setup). Recommended for reCAPTCHA security and turnkey site clarity.
<br>Disable only if you use a different secured login form.

<h4>Frontend WP Login/Registration URLs</h4>
<select name="frontendURLs" id="frontendURLs">
  <option value="1" <?php echo $options['frontendURLs'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['frontendURLs'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Use the frontend login/registration where possible (apply filters to use the new URLs for other plugins that call wp_login_url(), wp_registration_url() ).
<br>Warning: Before enabling this, make sure frontend login/registration pages are setup and work in your setup.

<h4>Redirect Default WP Login</h4>
<select name="redirectWpLogin" id="redirectWpLogin">
  <option value="1" <?php echo $options['redirectWpLogin'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['redirectWpLogin'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Redirects default backend WP login page to the frontend login page implemented by this solution. Recommended for reCAPTCHA security and turnkey site clarity.
<br>Warning: Before enabling this, make sure frontend login page is setup and works in your setup so you don't get locked out.

<h4>Limit Registrations per IP</h4>
<input name="registerIPlimit" type="text" id="registerIPlimit" size="5" maxlength="32" value="<?php echo esc_attr( $options['registerIPlimit'] ); ?>"/>
<br>Prevent multiple users to register from same IP, using this plugin. Tracking applies since feature was implemented. Default: <?php echo esc_html( $optionsDefault['registerIPlimit'] ); ?>
<br>Warning: For effectiveness of this feature, any other registration methods or forms including default WP registration should be disabled.

<h4>WP Registration Form Roles</h4>
<select name="registrationFormRole" id="registrationFormRole">
  <option value="1" <?php echo $options['registrationFormRole'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['registrationFormRole'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Add roles to default WordPress registration form so users can register as client, performer or studio (if enabled). Disable only if you use other roles and assignation system (ie. with a membership plugin).
<br>BuddyPress: This option disabled redirect to BP registration form because that does not include the roles.

<h4>Room Post Name (Webcam Listing)</h4>
<input name="custom_post" type="text" id="custom_post" size="12" maxlength="32" value="<?php echo esc_attr( strtolower( $options['custom_post'] ) ); ?>"/>
<br>Custom post name for webcams (only alphanumeric, lower case). Will be used for webcams urls. Ex: webcam
<br>Save <a href="options-permalink.php">Settings > Permalinks</a> to apply new URL structure and make new post types accessible.
<br>Custom post name also needs to be updated in other plugins that may provide features for it, like for <a href="admin.php?page=paid-membership&tab=content">MicroPayments - Paid Content</a>.
<br>Warning: New settings only applies for new posts. Previous posts (with previous custom type) will no longer be accessible and performers will need to configure new listings. Should be configured before going live. Existing users need to select the new room before going live as room post of previous type remains selected for existing users. Restoring a previous type, will also restore that.

<h4>Room Post Comments</h4>
<select name="comments" id="comments">
  <option value="1" <?php echo $options['comments'] ? 'selected' : ''; ?>>Enabled</option>
  <option value="0" <?php echo $options['comments'] ? '' : 'selected'; ?>>Disabled</option>
</select>

<h4>Performer Profile Link Prefix</h4>
<input name="performerProfile" type="text" id="performerProfile" size="80" maxlength="128" value="<?php echo esc_attr( $options['performerProfile'] ); ?>"/>
<br>Checked in performer links are available in webcam listings. Set blank to use BuddyPress profile url if available or get_author_posts_url() if not. Customize if there is profile page generated by a special plugin or theme feature.
<br>Ex: https://yoursite.com/author/ (if profile is https://yoursite.com/author/[user_nicename]
<BR>Your user_nicename based link (for troubleshooting):
				<?php
			echo esc_html( $options['performerProfile'] ) . esc_html( $current_user->user_nicename );
?>

<h4>Webcam Post Template Filename</h4>
<input name="postTemplate" type="text" id="postTemplate" size="20" maxlength="64" value="<?php echo esc_attr( $options['postTemplate'] ); ?>"/>
<br>Template file located in current theme folder, that should be used to render webcam post page. Ex: page.php, single.php, full_width.php (templates available with current site theme).
<br>
				<?php
			if ( $options['postTemplate'] != '+plugin' )
			{
				$single_template = get_template_directory() . '/' . sanitize_file_name( $options['postTemplate'] );
				echo esc_html( $single_template ) . ' : ';
				if ( file_exists( $single_template ) )
				{
					echo 'Found.';
				} else
				{
					echo 'Not Found! Use another theme file!';
				}
			}

			echo '<br>Theme directory: ' . get_template_directory();
			$thelist = '';

			if ( $handle = opendir( get_template_directory() ) )
			{

				echo '<br>Possible template files in your current theme directory: ';
				while ( false !== ( $file = readdir( $handle ) ) )
				{
					if ( $file != '.' && $file != '..' && strtolower( substr( $file, strrpos( $file, '.' ) + 1 ) ) == 'php' && ! strstr( $file, 'footer' ) && ! strstr( $file, 'header' ) && $file != 'index.php' )
					{
						$thelist .= $file . ',';
					}
				}
				closedir( $handle );
				echo '<I>' . esc_html( $thelist ) . '</I>'; 
				echo '<br>Some themes store templates in subfolders, not listed above.';
			}

?>

<br>Set "+plugin" to use a minimal template with theme header and footer provided by this plugin, instead of theme templates.
<br>Set "+app" for a full page template for HTML5 Videochat app without theme header/footer.

<h4>Banned Words in Names</h4>
<textarea name="bannedNames" cols="64" rows="3" id="bannedNames"><?php echo esc_textarea( $options['bannedNames'] ); ?>
</textarea>
<br>Users trying to broadcast/access rooms using these words will be disconnected.

				<?php
			break;

		case 'appearance':
			self::requirementMet( 'appearance' );

?>
<h3>Appearance</h3>
Customize logos, general appearance, styling. For feature customizations and overview see <a href="admin.php?page=live-webcams&tab=customize">Customize</a> tab.

<h4>Theme Mode (Dark/Light/Auto)</h4> 
<select name="themeMode" id="themeMode">
  <option value="" <?php echo $options['themeMode'] ? '' : 'selected'; ?>>None</option>
  <option value="light" <?php echo $options['themeMode'] == 'light' ? 'selected' : ''; ?>>Light Mode</option>
  <option value="dark" <?php echo $options['themeMode'] == 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
  <option value="auto" <?php echo $options['themeMode'] == 'auto' ? 'selected' : ''; ?>>Auto Mode</option>
</select>
<br>This will use JS to apply ".inverted" class to Fomantic ".ui" elements mainly on AJAX listings. When using the <a href="https://fanspaysite.com/theme">FansPaysSite theme</a> this will be discarded and the dynamic theme mode will be used.

<h4>Interface Class(es)</h4>
<input name="interfaceClass" type="text" id="interfaceClass" size="30" maxlength="128" value="<?php echo esc_attr( $options['interfaceClass'] ); ?>"/>
<br>Extra static class to apply to interface (using Semantic UI). Use inverted when theme uses a dark mode (a dark background with white text) or for contrast. Ex: inverted
<br>Some common Semantic UI classes: inverted (dark mode or contrast), basic (minimal formatting), secondary/tertiary (greys), red/orange/yellow/olive/green/teal/blue/violet/purple/pink/brown/grey/black (colors). Multiple classes can be combined, divided by spaces. Ex: "inverted", "basic pink", "secondary green", "secondary basic", "inverted orange" 
<br>HTML5 interface elements can customized by extra CSS. A lot of core styling is done with Semantic UI and custom CSS can be used to alter elements.
In example <a href="https://semantic-ui.com/elements/button.html">Semantic UI Button</a> font can be edited with code like “.ui.button{font-family: verdana}”, added to custom CSS of specific sections (like performer dashboard or listings) or with theme to apply on all pages.

<h4>WP Registration and Login Logo</h4>
<input name="loginLogo" type="text" id="loginLogo" size="100" maxlength="200" value="<?php echo esc_url_raw( $options['loginLogo'] ); ?>"/>
<br>Logo image to show on registration & login form, replacing default WordPress logo for a turnkey site. Leave blank to disable. Recommended size: 240x80.
<br>Warning: If you upload own image, upload it to a different folder (i.e. with <a href="upload.php">Media Uploader</a>) as plugin folder resets on updates (deleting any extra files).
				<?php echo esc_url_raw( $options['loginLogo'] ) ? "<BR><img src='" . esc_url_raw( $options['loginLogo'] ) . "'>" : ''; ?>

<h4>HTML5 Videochat Logo URL</h4>
<input type="text" name="appLogo" id="appLogo" value="<?php echo esc_attr( trim( $options['appLogo'] ) ); ?>" size="120" />
<BR>URL to logo image to be displayed in app, floating over videos. Set blank to remove. It's a HTML element that can be styled with CSS for class videowhisperAppLogo.
<?php
if ( $options['appLogo'] )
{	
?>
<BR><img src="<?php echo esc_attr( $options['appLogo'] ); ?>" style="max-width:100px;" />
<?php
}
?>

<h4>Room Profile Sections Layout</h4>
<select name="profileLayout" id="profileLayout">
  <option value="tabs" <?php echo $options['profileLayout'] == 'tabs' ? 'selected' : ''; ?>>Tabs</option>
  <option value="accordion" <?php echo $options['profileLayout'] == 'accordion' ? 'selected' : ''; ?>>Accordion</option>
  <option value="chapters" <?php echo $options['profileLayout'] == 'chapters' ? 'selected' : ''; ?>>Table of Contents</option>
  <option value="auto" <?php echo $options['profileLayout'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
</select>
<br>How to list and navigate performer dashboard sections. Default: <?php echo esc_html( $optionsDefault['profileLayout'] ); ?>
<br>Auto will show Tabs on PC and Accordion on mobile. Table of Contents works without JavaScript.
<br> + <a href="admin.php?page=live-webcams&tab=listings">Customize Listings Template and CSS</a>
<br> + <a href="admin.php?page=live-webcams&tab=profile">Customize Room Profile Fields</a>


<h4>Dashboard Sections Layout</h4>
<select name="performerLayout" id="performerLayout">
  <option value="tabs" <?php echo $options['performerLayout'] == 'tabs' ? 'selected' : ''; ?>>Tabs</option>
  <option value="accordion" <?php echo $options['performerLayout'] == 'accordion' ? 'selected' : ''; ?>>Accordion</option>
  <option value="chapters" <?php echo $options['performerLayout'] == 'chapters' ? 'selected' : ''; ?>>Table of Contents</option>
  <option value="auto" <?php echo $options['performerLayout'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
</select>
<br>How to list and navigate performer dashboard sections. Default: <?php echo esc_html( $optionsDefault['performerLayout'] ); ?>
<br>Auto will show Tabs on PC and Accordion on mobile. Table of Contents works without JavaScript.
<br> + <a href="admin.php?page=live-webcams&tab=performer">Customize Performer Settings and Dashboard</a>



<h4>Webcam Post Template Filename</h4>
<input name="postTemplate" type="text" id="postTemplate" size="20" maxlength="64" value="<?php echo esc_attr( $options['postTemplate'] ); ?>"/>
<br>Template file located in current theme folder, that should be used to render webcam post page. Ex: page.php, single.php, full_width.php (templates available with current site theme).
<br>
				<?php
			if ( $options['postTemplate'] != '+plugin' )
			{
				$single_template = get_template_directory() . '/' . sanitize_file_name( $options['postTemplate'] );
				echo esc_html( $single_template ) . ' : ';
				if ( file_exists( $single_template ) )
				{
					echo 'Found.';
				} else
				{
					echo 'Not Found! Use another theme file!';
				}
			}

			echo '<br>Theme directory: ' . get_template_directory();

			if ( $handle = opendir( get_template_directory() ) )
			{

				echo '<br>Possible template files in your current theme directory: ';
				$thelist = '';
				while ( false !== ( $file = readdir( $handle ) ) )
				{
					if ( $file != '.' && $file != '..' && strtolower( substr( $file, strrpos( $file, '.' ) + 1 ) ) == 'php' && ! strstr( $file, 'footer' ) && ! strstr( $file, 'header' ) && $file != 'index.php' )
					{
						$thelist .= $file . ',';
					}
				}
				closedir( $handle );
				echo esc_html( $thelist );
				echo '<br>Some themes store templates in subfolders, not listed above.';
			}

?>

<br>Set "+plugin" to use a minimal template with theme header and footer provided by this plugin, instead of theme templates.
<br>Set "+app" for a full page template for HTML5 Videochat app without theme header/footer.




<h4>Show VideoWhisper Powered by</h4>
<select name="videowhisper" id="videowhisper">
  <option value="0" <?php echo $options['videowhisper'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['videowhisper'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Display references to plugin developers on main sections.

				<?php submit_button(); ?>

<p> + <strong>Theme</strong>: Get a <a href="http://themeforest.net/popular_item/by_category?category=wordpress&amp;ref=videowhisper">professional WordPress theme</a> to skin site, change design.<br>
A theme with wide content area (preferably full page width) should be used so videochat interface can use most of the space.<br>
Also plugin hooks into WP registration to implement a role selector: a theme that manages registration in a different custom page should be compatible with WP hooks to show the role option, unless you manage roles in a different way.<br>
Tutorial: <a href="https://en.support.wordpress.com/themes/uploading-setting-up-custom-themes/">Upload and Setup Custom WP Theme</a><br>
Sample themes: <a href="http://themeforest.net/item/jupiter-multipurpose-responsive-theme/5177775?ref=videowhisper">Jupiter</a>, <a href="http://themeforest.net/item/impreza-retina-responsive-wordpress-theme/6434280?ref=videowhisper">Impreza</a>, <a href="http://themeforest.net/item/elision-retina-multipurpose-wordpress-theme/6382990?ref=videowhisper">Elision</a>, <a href="http://themeforest.net/item/sweet-date-more-than-a-wordpress-dating-theme/4994573?ref=videowhisper">Sweet Date 4U</a>, <a href="https://themeforest.net/item/aeroland-responsive-app-landing-and-website-wordpress-theme/23314522?ref=videowhisper">AeroLand </a>. Most premium themes should work fine, these are just some we deployed in some projects.</p>

<p> + <strong>Logo</strong>: You can start from a <a href="http://graphicriver.net/search?utf8=%E2%9C%93&amp;order_by=sales&amp;term=video&amp;page=1&amp;category=logo-templates&amp;ref=videowhisper">professional logo template</a>. Logos can be configured from plugin settings, Integration tab and by default load from images in own installation.</p>

<p> + <strong>Design/Interface adjustments</strong>:
After selecting a theme to start from, that can be customized by a web designer experienced with WP themes. A WP designer can also create a custom theme (that meets WP coding requirements and standards).
Solution specific CSS (like for listings and user dashboards) can be edited in plugin backend.
Content on videochat page is generated by shortcodes from multiple plugins: videochat, profile fields, videos, pictures, ratings. There are multiple settings and CSS. Shortcodes are documented in plugin backend and can be added to pages, posts, templates.
Flash videochat skin graphics can be edited by replacing interface images in a templates folder as described in plugin backend. Videochat application layout and functional parameters can be edited in plugin settings.
HTML5 interface elements can customized by extra CSS. A lot of core styling is done with Semantic UI.
VideoWhisper developers can add additional options, settings to ease up customizations, for additional fees depending on exact customization requirements.
</p>




				<?php
			break;
		case 'listings':
			// ! Listings

			$options['labelsConfig'] = stripslashes( $options['labelsConfig'] );

?>
<h3>Webcam Room Listings</h3>
Customize listings (mainly for [videowhisper_webcams] shortcode). Shortcode can be customized with parameters by editing <a href="post.php?post=<?php echo esc_attr( $options['p_videowhisper_webcams'] ); ?>&action=edit">Webcams page</a>, as <a href="admin.php?page=live-webcams-doc#shortcodes">documented</a>. Room profile filters to show can be <a href="admin.php?page=live-webcams&tab=profile">customized</a>.
<BR><a href="edit.php?post_type=<?php echo esc_attr( $options['custom_post'] ); ?>">Webcam listings</a> are rooms created by performers: "Quick Edit" to set custom earning ratio, custom cost per minute for private show, featured listings.

<h4>Default Listing Layout</h4>
<select name="layoutDefault" id="layoutDefault">
  <option value="grid" <?php echo $options['layoutDefault'] == 'grid' ? 'selected' : ''; ?>>Preview Grid</option>
  <option value="list" <?php echo $options['layoutDefault'] == 'list' ? 'selected' : ''; ?>>Full Row List</option>
  <option value="horizontal" <?php echo $options['layoutDefault'] == 'horizontal' ? 'selected' : ''; ?>>Horizontal Slider</option>
</select>
<br>Preview Grid layout shows brief info over webcam preview (for video centered sites).
<br>Full Row List layout shows each room on a row, with more details like full room description, a featured (top available) review for (information/consultation centered sites).
<br>Number of rows depends on number of items per page, their sizes and content area width in Preview Grid.

<h4>Listings Menu</h4>
<select name="listingsMenu" id="listingsMenu">
<option value="auto" <?php echo $options['listingsMenu'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
  <option value="1" <?php echo $options['listingsMenu'] == '1' ? 'selected' : ''; ?>>Menus</option>
  <option value="0" <?php echo $options['listingsMenu'] == '0' ? 'selected' : ''; ?>>Dropdowns</option>
</select>
<br>Filters can show as left side menus or dropdowns. Auto will show dropdowns on mobile devices and menu on desktops where's there's more space for a side menu (recommended).

<h4>Webcam Thumb Width</h4>
<input name="thumbWidth" type="text" id="thumbWidth" size="4" maxlength="4" value="<?php echo esc_attr( $options['thumbWidth'] ); ?>"/>px
<br>Thumbnails for listings are generated at this size (in pixels). Should be at least display size configured in CSS. Applies when thumbs are generated/updated - older thumbs will have old size until updated (by performer uploading new room picture or generating live snapshots depending on settings).

<h4>Webcam Thumb Height</h4>
<input name="thumbHeight" type="text" id="thumbHeight" size="4" maxlength="4" value="<?php echo esc_attr( $options['thumbHeight'] ); ?>"/>px

<h4>Default Number of Rooms Per Page</h4>
<input name="perPage" type="text" id="perPage" size="3" maxlength="3" value="<?php echo esc_attr( $options['perPage'] ); ?>"/>
<br>Affects number of rows per page, depending on their sizes and content area width in Preview Grid.
<br>For Full Row List this is also the number of rows per page.


<h4>Credits/Tokens Currency Label</h4>
<input name="currency" type="text" id="currency" size="8" maxlength="12" value="<?php echo esc_attr( $options['currency'] ); ?>"/>

<h4>Credits/Tokens Currency Per Minute Label</h4>
<input name="currencypm" type="text" id="currencypm" size="8" maxlength="20" value="<?php echo esc_attr( $options['currencypm'] ); ?>"/>


<h4>Labels</h4>
<textarea name="labelsConfig" id="labelsConfig" cols="100" rows="5"><?php echo esc_textarea( $options['labelsConfig'] ); ?></textarea>
<BR>Configure custom labels.

Default:<br><textarea readonly cols="100" rows="5"><?php echo esc_textarea( $optionsDefault['labelsConfig'] ); ?></textarea>

<BR>Parsed configuration (should be an array) JSON:<BR>
				<?php

echo esc_html( wp_json_encode( $options['labels'] ) ) ; 
?>
<BR>Serialized:<BR>
				<?php

			echo esc_html( serialize( $options['labels'] ) );
?>

<h4>AJAX Listings CSS</h4>
				<?php
			$options['customCSS'] = stripslashes( $options['customCSS'] );

?>
<textarea name="customCSS" id="customCSS" cols="100" rows="8"><?php echo esc_textarea( $options['customCSS'] ); ?></textarea>
<br>
Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['customCSS'] ); ?></textarea>

<h4>Listing Template: Grid</h4>
				<?php
			$options['listingTemplate'] = stripslashes( $options['listingTemplate'] );

?>
<textarea name="listingTemplate" id="listingTemplate" cols="100" rows="8"><?php echo esc_textarea( $options['listingTemplate'] ); ?></textarea>
<br>These tags are supported in all listing templates: #name#', '#age#', '#clientCPM#', '#roomBrief#', '#roomTags#', '#url#', '#snapshot#','#thumbWidth#', '#thumbHeight#', '#banLink#', '#groupMode#', '#groupCPM#', '#performers#', '#currency#', '#preview#', '#enter#', '#paidSessionsPrivate#', '#paidSessionsGroup#', '#rating#', '#performerStatus#', '#roomCategory#', '#featuredReview#', '#roomDescription#', '#featured#', '#buttonChat#', '#buttonCall#', '#buttonMessage#', '#vote', '#icons#' .
Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['listingTemplate'] ); ?></textarea>
<br>#performerStatus# : offline/public/private (use for applying different css, not translated)
<br>#paidSessionsPrivate#, #paidSessionsGroup#' : number of paid sessions (depending on logging settings)
<br>#clientCPM#, #groupCPM# : cost per minute in private and group sessions
<br>#age# : last time online (LIVE for live performers)
<br>#enter#  : enter button

<h4>Emphasized Listings</h4>
<select name="listingBig" id="listingBig">
  <option value="0" <?php echo $options['listingBig'] == '0' ? 'selected' : ''; ?>>No</option>
  <option value="1" <?php echo $options['listingBig'] == '1' ? 'selected' : ''; ?>>First</option>
  <option value="5" <?php echo $options['listingBig'] == '5' ? 'selected' : ''; ?>>1 emphasized, 4 normal</option>
  <option value="9" <?php echo $options['listingBig'] == '9' ? 'selected' : ''; ?>>1 emphasized, 8 normal</option>
</select>
<br>Show some listings emphasized on page to bring more attention. These can have special CSS.


<h4>Listing Template: Grid, Emphasized</h4>
				<?php
			$options['listingTemplate2'] = stripslashes( $options['listingTemplate2'] );

?>
<textarea name="listingTemplate2" id="listingTemplate2" cols="100" rows="8"><?php echo esc_textarea( $options['listingTemplate2'] ); ?></textarea>
<br>Template for emphasized listing on each page. Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['listingTemplate2'] ); ?></textarea>


<h4>Listing Template: List</h4>
				<?php
			$options['listingTemplateList'] = stripslashes( $options['listingTemplateList'] );

?>
<textarea name="listingTemplateList" id="listingTemplateList" cols="100" rows="8"><?php echo esc_textarea( $options['listingTemplateList'] ); ?></textarea>
<br>Template for listing in List layout. Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['listingTemplateList'] ); ?></textarea>

<h4>Listing Template: Horizontal Slider</h4>
				<?php
			$options['listingTemplateHorizontal'] = stripslashes( $options['listingTemplateHorizontal'] );

?>
<textarea name="listingTemplateHorizontal" id="listingTemplateHorizontal" cols="100" rows="8"><?php echo esc_textarea( $options['listingTemplateHorizontal'] ); ?></textarea>
<br>Template for listing in Horizontal Slider layout. Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['listingTemplateHorizontal'] ); ?></textarea>


<h4>Room Profile Sections Layout</h4>
<select name="profileLayout" id="profileLayout">
  <option value="tabs" <?php echo $options['profileLayout'] == 'tabs' ? 'selected' : ''; ?>>Tabs</option>
  <option value="accordion" <?php echo $options['profileLayout'] == 'accordion' ? 'selected' : ''; ?>>Accordion</option>
  <option value="chapters" <?php echo $options['profileLayout'] == 'chapters' ? 'selected' : ''; ?>>Table of Contents</option>
  <option value="auto" <?php echo $options['profileLayout'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
</select>
<br>How to list and navigate performer dashboard sections. Default: <?php echo esc_html( $optionsDefault['profileLayout'] ); ?>
<br>Auto will show Tabs on PC and Accordion on mobile. Table of Contents works without JavaScript.


<h4>Max Viewers Count on Room Page</h4>
<select name="viewersCount" id="viewersCount">
  <option value="1" <?php echo $options['viewersCount'] == '1' ? 'selected' : ''; ?>>Show</option>
  <option value="0" <?php echo $options['viewersCount'] == '0' ? 'selected' : ''; ?>>Hide</option>
</select>
<br>Display maximum viewers ever count on webcam page.

<h4>Paid Session Sales Count on Room Page</h4>
<select name="salesCount" id="salesCount">
  <option value="1" <?php echo $options['salesCount'] == '1' ? 'selected' : ''; ?>>Show</option>
  <option value="0" <?php echo $options['salesCount'] == '0' ? 'selected' : ''; ?>>Hide</option>
</select>
<br>Display paid sessions (sales count) on webcam page. Based on session logs (configurable time to keep).

<h4>Webcam Link for Enter</h4>
<select name="webcamLink" id="webcamLink">
  <option value="room" <?php echo $options['webcamLink'] == 'room' ? 'selected' : ''; ?>>Room</option>
  <option value="custom" <?php echo $options['webcamLink'] == 'custom' ? 'selected' : ''; ?>>Custom</option>
  <option value="auto" <?php echo $options['webcamLink'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
</select>
<br>Enter room button can link to default room page or to a custom link. Auto will link to custom room link only if defined.

<h4>Default Custom Webcam Link Prefix</h4>
<input name="webcamLinkDefault" type="text" id="webcamLinkDefault" size="80" maxlength="128" value="<?php echo esc_attr( $options['webcamLinkDefault'] ); ?>"/>
<br>Custom link can be configured for webcam room Enter button. Can be set by admin with quick edit from <a href="edit.php?post_type=webcam">webcams listings</a> in backend.
<br>Ex: https://yoursite.com/webcam/ (if webcam is at https://yoursite.com/webcam/[webcam_name]

<h4>Listings Cache</h4>
<input name="listingsCache" type="text" id="listingsCache" size="3" maxlength="3" value="<?php echo esc_attr( $options['listingsCache'] ); ?>"/>seconds
Set 0 or blank to disable. Minimum 5s. 

<h4>Disable Location Bans in Listing Results</h4>
<select name="listingsDisableLocation" id="listingsDisableLocation">
  <option value="1" <?php echo $options['listingsDisableLocation'] == '1' ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['listingsDisableLocation'] == '0' ? 'selected' : ''; ?>>No</option>
</select>
<br>If disabled, listings will show to users from locations banned by performer. If enabled, rooms are hidden if performers banned access.
<br>Disabling location filtering improves query performance on databases with many metas. On databases with many metas this type of query can be very slow or even timeout.
<br>Recommended: Yes. Although listing will show to avoid performance issues, banned locations will not be able to access room page.


<h4>List Only Rooms with Thumb (Snapshot/Picture)</h4>
<select name="listingsThumbsOnly" id="listingsThumbsOnly">
  <option value="1" <?php echo $options['listingsThumbsOnly'] == '1' ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['listingsThumbsOnly'] == '0' ? 'selected' : ''; ?>>No</option>
</select>

<h4>Persistent Search</h4>
<select name="filtersSave" id="filtersSave">
  <option value="1" <?php echo $options['filtersSave'] == '1' ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['filtersSave'] == '0' ? 'selected' : ''; ?>>No</option>
</select>
<br>If enabled search filters will be saved for logged in users and used next time when they access listings. Default is No (filters are reset on each page access).

<h4>Debug Mode / Dev Mode</h4>
<select name="debugMode" id="debugMode">
  <option value="1" <?php echo $options['debugMode'] == '1' ? 'selected' : ''; ?>>On</option>
  <option value="0" <?php echo $options['debugMode'] == '0' ? 'selected' : ''; ?>>Off</option>
</select>
<BR>Outputs various debugging info, including query parameters when there are no listings to show, room settings when going live, various information in text chat, matchmaking criteria.

<h4>Troubleshooting</h4>
Options in themes, plugins that alter JS may break menu for listings or other functionality. That may include options to delay or minimize JS, specific to cache plugins. If you encounter issues, try disabling options that alter JS, as for WP Rocket plugin (that allows editing these options per page).

				<?php
			break;

		case 'performer':
			// ! Performer Settings
			$options['dashboardMessage'] = stripslashes( $options['dashboardMessage'] );
			$options['welcomePerformer']  =  stripslashes( $options['welcomePerformer'] );
			$options['dashboardCSS'] = stripslashes( $options['dashboardCSS'] );
			$options['rolePerformer'] = sanitize_title_with_dashes( $options['rolePerformer'] );

?>
<h3>Performer Settings</h3>

<h4>Role Name for Performer / Room Owner</h4>
<input name="rolePerformer" type="text" id="rolePerformer" size="20" maxlength="64" value="<?php echo esc_attr( $options['rolePerformer'] ); ?>"/>
<br>This is used as registration role option and access to performer (room owner) dashboard page (redirection on login). Administrators can also manually access dashboard page and setup/access webcam page for testing. Should be 1 role name slug, without special characters.
<br>Sample possible values: performer, expert, host, professional, teacher, trainer, tutor, provider, model, author, expert, artist, medium, moderator, owner . Default: <?php echo esc_attr( $optionsDefault['rolePerformer'] ); ?>
<br>Use role name (slug) like "user_role", not name like "User Role".
<br>Performer role should be configured with other integrated plugins for necessary capabilities (like sharing videos, pictures).
<br> - Your roles (for troubleshooting):
				<?php
			$current_user = wp_get_current_user();
			foreach ( $current_user->roles as $role )
			{
				echo esc_html( $role ) . ' ';
			}
?>
			<br> - Current WordPress roles:
				<?php
			global $wp_roles;
			foreach ( $wp_roles->roles as $role_slug => $role )
			{
				echo esc_html( $role_slug ) . '= "' . esc_html( $role['name'] ) . '" ';
			}
?>
<br>Depending of project, performer role should also be configured/updated in settings for other plugins that configure permissions by role (<a href="admin.php?page=video-share&tab=share">Video Share VOD</a>, <a href="admin.php?page=picture-gallery&tab=share">Picture Gallery</a>, <a href="admin.php?page=paid-membership&tab=share">MicroPayments - Downloads</a>).
<br>Warning: Changing role name will allow only users with new role to access performer dashboard. New role is assigned to <a href="admin.php?page=live-webcams&tab=integration">new registrations if enabled</a>. <a href="users.php">Previously registered users</a> need to be assigned to new role manually. Additionally, shortcode role parameter need to be updated on the dedicated <a href="admin.php?page=live-webcams&tab=pages">registration pages</a>.


<h4>Extra Roles for Performers / Room Owners</h4>
<input name="rolePerformers" type="text" id="rolePerformers" size="40" maxlength="64" value="<?php echo esc_attr( $options['rolePerformers'] ); ?>"/>
<br>Other roles that can access performer dashboard to setup and manage rooms. Role names (slugs) as comma separated values.
<br>Roles administrator, super-admin are already included for testing purposes (don't need to be added to this list).  Default: <?php echo esc_html( $optionsDefault['rolePerformers'] ); ?>
<br>Roles that can manage rooms: <?php echo esc_html ( implode( ', ', self::getRolesPerformer( $options ) ) ); ?>

<h4>Restricted Roles</h4>
<input name="roleRestricted" type="text" id="roleRestricted" size="40" maxlength="64" value="<?php echo esc_attr( $options['roleRestricted'] ); ?>"/>
<br>Comma separated roles that are not allowed to access rooms except their own. Performers can be prevented from accessing rooms of other performers.

<h4>Performer / Room Owner Settings & Management</h4>
<UL>
<LI> + <a href="admin.php?page=live-webcams&tab=performer">Performer</a>: Configure performer settings
<LI> + <a href="admin.php?page=live-webcams&tab=record">Account Administrative Fields / Questions</a>: Configure Account Administrative Records and Verification/Approval
<LI> + <a href="admin.php?page=live-webcams&tab=group">Chat Modes</a>: Performers can select room mode for webcam room when going live (in example Free Chat, Broadcast, Paid Group Show, Calls Only, Video Conference)
<LI> + <a href="admin.php?page=live-webcams&tab=studio">Studios</a>: If enabled, studios can manage multiple performer accounts and rooms
<LI> + <a href="admin.php?page=live-webcams&tab=features">Role Features</a>: Some features can be toggle by role (like enable custom cost per minute)
<LI> + <a href="users.php?role=<?php echo esc_attr( array( 'rolePerformer' ) ); ?>">View <?php echo ucwords( esc_html( $options['rolePerformer'] ) ); ?> User List</a>
<LI> + <a href="admin.php?page=live-webcams-records"><?php echo ucwords( esc_html( $options['rolePerformer'] ) ); ?> Approval</a>
</LI>
</UL>


<h4>Dashboard Welcome Message for Performers</h4>
				<?php
			$options['dashboardMessage'] = wp_kses_post( $options['dashboardMessage'] );
			wp_editor( $options['dashboardMessage'], 'dashboardMessage', $settings = array( 'textarea_rows' => 3 ) );
?>
<br>Shows in performer dashboard at top. Could contain announcements, instructions, links to support.
Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['dashboardMessage'] ); ?></textarea>

<h4>Dashboard Sections Layout</h4>
<select name="performerLayout" id="performerLayout">
  <option value="tabs" <?php echo $options['performerLayout'] == 'tabs' ? 'selected' : ''; ?>>Tabs</option>
  <option value="accordion" <?php echo $options['performerLayout'] == 'accordion' ? 'selected' : ''; ?>>Accordion</option>
  <option value="chapters" <?php echo $options['performerLayout'] == 'chapters' ? 'selected' : ''; ?>>Table of Contents</option>
  <option value="auto" <?php echo $options['performerLayout'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
</select>
<br>How to list and navigate performer dashboard sections. Default: <?php echo esc_html( $optionsDefault['performerLayout'] ); ?>
<br>Auto will show Tabs on PC and Accordion on mobile. Table of Contents works without JavaScript.

<h4>Dashboard Bottom Message for Performers</h4>
				<?php
			$options['dashboardMessageBottom'] = wp_kses_post( stripslashes( $options['dashboardMessageBottom'] ) );
			wp_editor( $options['dashboardMessageBottom'], 'dashboardMessageBottom', $settings = array( 'textarea_rows' => 4 ) );
?>
<br>Shows in performer dashboard at bottom. Could contain instructions, links to support.
Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['dashboardMessageBottom'] ); ?></textarea>



<h4>Webcam Rooms (Listings) per Performer</h4>
<input name="performerWebcams" id="performerWebcams" type="text" size="5" maxlength="10" value="<?php echo esc_attr( $options['performerWebcams'] ); ?>"/>
<br>Specify maximum number of webcam listings each performer can have. Limit to prevent flood of items, name reservation.
<br>Set 0 for single cam mode (1 webcam listing per performer automatically generated and selected) and 1 or more to allow multiple room select.


<h4>Welcome Message for Performer in Videochat Room</h4>
<textarea name="welcomePerformer" id="welcomePerformer" cols="100" rows="2"><?php echo esc_textarea( $options['welcomePerformer'] ); ?></textarea>
<br>Shows in chat area when entering own room.
Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['welcomePerformer'] ); ?></textarea>


<h4>Performer Profile Link Prefix</h4>
<input name="performerProfile" type="text" id="performerProfile" size="80" maxlength="128" value="<?php echo esc_attr( $options['performerProfile'] ); ?>"/>
<br>Checked in performer links are available in webcam listings. Set blank to use get_author_posts_url() .
<br>Ex: https://yoursite.com/author/ (if profile is https://yoursite.com/author/[user_nicename]
<BR>Your user_nicename based link (for troubleshooting):
				<?php
			echo esc_html( $options['performerProfile'] ) . esc_html( $current_user->user_nicename );
?>


<h4>Dashboard CSS</h4>
<textarea name="dashboardCSS" id="dashboardCSS" cols="100" rows="6"><?php echo esc_textarea( $options['dashboardCSS'] ); ?></textarea>
<br>
Custom CSS for performer dashboard page. Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['dashboardCSS'] ); ?></textarea>


<h4>Toggle Features from Performer Dashboard Sections</h4>
Use these settings to toggle features (and also dashboard sections).

<h4>Approval Status in Dashboard</h4>
<select name="performerStatus" id="performerStatus">
  <option value="1" <?php echo $options['performerStatus'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['performerStatus'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Show approval status, edit account records button.

<h4>Performer Go Live : Chat Modes List</h4>
<select name="performerGolive" id="performerGolive">
  <option value="form" <?php echo $options['performerGolive'] == 'form' ? 'selected' : ''; ?>>Form</option>
  <option value="buttons" <?php echo $options['performerGolive'] == 'buttons' ? 'selected' : ''; ?>>Buttons</option>
</select>
<br>Form may include advanced settings like Checkin options and Buttons shows simple access buttons.

<h4>Performer Wallet</h4>
<select name="performerWallet" id="performerWallet">
  <option value="1" <?php echo $options['performerWallet'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['performerWallet'] ? '' : 'selected'; ?>>No</option>
</select>

<h4>Performer Checkins</h4>
<select name="checkins" id="checkins">
  <option value="1" <?php echo $options['checkins'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['checkins'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Checkins enables a performer/provider to checkin other performers when going live for sharing profits from group paid chat and gifts/donations. In example when another performer joins the first in same physical location for a shared performance or multiple providers do a group webinar with video conferencing.


<h4>Locked Calls</h4>
<select name="calls" id="calls">
  <option value="1" <?php echo $options['calls'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['calls'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Enable locked (predefined) calls section in performer and client dashboards.

<h4>Enable Questions / Messages</h4>
<select name="messages" id="messages">
  <option value="0" <?php echo $options['messages'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['messages'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Clients can send questions/messages to rooms and performers can answer.


<h4>Overview in Performer Dashboard</h4>
<select name="performerOverview" id="performerOverview">
  <option value="1" <?php echo $options['performerOverview'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['performerOverview'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Show Overview tab in Performer Dashboard.

<h4>Reports in Performer Dashboard</h4>
<select name="performerReports" id="performerReports">
  <option value="1" <?php echo $options['performerReports'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['performerReports'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Show Reports tab in Performer Dashboard.

<h4>Setup in Performer Dashboard</h4>
<select name="performerSetup" id="performerSetup">
  <option value="1" <?php echo $options['performerSetup'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['performerSetup'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Show Setup tab in Performer Dashboard.

<h4>Bans</h4>
<select name="bans" id="bans">
  <option value="1" <?php echo $options['bans'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['bans'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Enable performer to kick/ban users in application and section to manage bans in performer dashboard.

<h4>Room Profiles</h4>
<select name="profiles" id="profiles">
  <option value="0" <?php echo $options['profiles'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['profiles'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enable rooms profiles and sections.


<h4>Call Now Button in Room Profile</h4>
<select name="profileCall" id="profileCall">
  <option value="0" <?php echo $options['profileCall'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['profileCall'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enable a Call Now button in room profile, to request private 2 way [video] call. Depending on chat mode and performer configured room settings, it will show a Join button if private requests are disabled. Implements the [videowhisper_callnow roomid=""] shortcode.


<h4>Video Share VOD <a target="_plugin" href="https://videosharevod.com/">Plugin</a> - Enable Videos</h4>
				<?php
			if ( is_plugin_active( 'video-share-vod/video-share-vod.php' ) )
			{
				echo 'Detected:  <a href="admin.php?page=video-share">Configure</a> | <a href="https://videosharevod.com/features/quick-start-tutorial/">Tutorial</a>';
			} else
			{
				echo 'Not detected. Please install and activate <a target="_videosharevod" href="https://wordpress.org/plugins/video-share-vod/">VideoShareVOD Plugin</a> from <a href="plugin-install.php">Plugins > Add New</a>!';
			}
?>
<BR><select name="videosharevod" id="videosharevod">
  <option value="0" <?php echo $options['videosharevod'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['videosharevod'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>This feature requires latest FFmpeg with HTML5 codecs.
<br>Enables VideoShareVOD integration that allows performers to add videos to their videochat page. Performer role should be allowed to share/publish videos from VSV settings.
<br>HTML5 Videochat saves recordings (if configured) using FFmpeg in a _recordings folder in each room folder, as webm for WebRTC streams. VideoShareVOD enables Import (publishing) of these videos and conversion to HTML5 MP4 that works on all browsers.
<br>Performers can toggle stream recording from Settings panel in HTML5 Videochat advanced interface and defaults are configured from <a href="admin.php?page=live-webcams&tab=app">HTML5 Videochat Settings</a> .
<br>Streams are recorded in adaptive formats (webm/mp4) depending on broadcast (WebRTC/RTMP) and files are stored in room uploads folder. Recordings be published to site with VideoShareVOD plugin from Videos tab, Import in Performer Dashboard.
<br>Warning: Recording streams involves processing resources mainly for disk writing and consumes disk space fast.

<h4>Recordings Stack</h4>
<select name="recordingStack" id="recordingStack">
  <option value="0" <?php echo $options['recordingStack'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['recordingStack'] == '1' ? 'selected' : ''; ?>>Yes</option>
  <option value="both" <?php echo $options['recordingStack'] == 'both' ? 'selected' : ''; ?>>Both</option>
</select>
<br>Save stream recordings side by side in same file (for conferencing). Both saves both individually and in stack, for increased reliability, using more resources.


<h4>Teaser Offline</h4>
 <select name="teaserOffline" id="teaserOffline">
  <option value="0" <?php echo $options['teaserOffline'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['teaserOffline'] ? 'selected' : ''; ?>>Yes</option>
</select>
Play a teaser while offline. When selecting teaser also disables any scheduling if enabled.

<h4>Picture Gallery <a target="_plugin" href="https://wordpress.org/plugins/picture-gallery/">Plugin</a> - Enable Pictures</h4>
				<?php
			if ( is_plugin_active( 'picture-gallery/picture-gallery.php' ) )
			{
				echo 'Detected:  <a href="admin.php?page=picture-gallery">Configure</a>';
			} else
			{
				echo 'Not detected. Please install and activate Picture Gallery by VideoWhisper.com from <a href="plugin-install.php">Plugins > Add New</a>!';
			}
?>
<BR><select name="picturegallery" id="picturegallery">
  <option value="0" <?php echo $options['picturegallery'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['picturegallery'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables Picture Gallery integration that allows performers to add videos to their videochat page. Performer role should be allowed to share/publish pictures from plugin settings.


<h4>WooCommerce Plugin - Enable Products</h4>
				<?php
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) )
			{
				echo 'Detected';
			} else
			{
				echo 'Not detected. Please install and activate WooCommerce from <a href="plugin-install.php">Plugins > Add New</a>!';
			}
?>
<BR><select name="woocommerce" id="woocommerce">
  <option value="0" <?php echo $options['woocommerce'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['woocommerce'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables WooCommerce products in performer dashboard and and room profile. Great in combination with MicroPayments plugin that enable selling access to digital content as WooCommerce products.

<h4>External RTMP Encoder (OBS/Larix)</h4>
Default setting is configured from HTML5 Videochat (external_rtmp) and can be toggled from room settings. Streams tab shows in performer dashboard if any of these 3 features is enabled: external encoder support, re-streams, push streams.

<h4>Re-Streams / RTSP IP Cameras</h4>
<select name="reStreams" id="reStreams">
  <option value="0" <?php echo $options['reStreams'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['reStreams'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<BR>Allow room owners to add external stream sources to restream in own room, including RTSP IP cameras. This is bitrate intensive for streaming server as it needs to retrieve the streams.

<h4>Push Streams</h4>
<select name="pushStreams" id="pushStreams">
  <option value="0" <?php echo $options['pushStreams'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['pushStreams'] ? 'selected' : ''; ?>>Yes</option>
</select>
<BR>Allow room owners to add destinations to push performer stream while broadcasting. This is processing intensive for web hosting as FFmpeg transcoding is required to convert WebRTC stream codecs to RTMP.
<BR>To test this feature on <a href="https://webrtchost.com/hosting-plans/">WebRTC Host Complete plans</a>, you can push stream to the RTMP app configured for recording like rtmp://[account_ip]/[account_name]-record/test and you should obtain a test.flv file in streams folder.
<BR>Recommended: No
	
	<h4>Performer Notification on Private Requests</h4>
	<select name="privateNotification" id="privateNotification">
	  <option value="0" <?php echo $options['privateNotification'] ? '' : 'selected'; ?>>No</option>
	  <option value="1" <?php echo $options['privateNotification'] ? 'selected' : ''; ?>>Yes</option>
	</select>
	<br>Notify performer when user requests private from room listing menu (not when online in group chat as notification is shown directly in web app).
	
	<h4>Private Notification Subject</h4>
	<input name="privateSubject" type="text" id="privateSubject" size="100" maxlength="256" value="<?php echo esc_attr( $options['privateSubject'] ); ?>"/> [user]
	<br>An notification is sent when a client requests a private from room list. Sender name is added at end. Default: <?php echo esc_html( $optionsDefault['privateSubject'] ); ?>
	<br>For SMS notifications link is added at end (without extra text).
	
	<h4>Private Notification Text</h4>
	<textarea name="privateText" id="privateText" cols="100" rows="3"><?php echo esc_textarea( $options['privateText'] ); ?></textarea> [link]
	<br>Link to site is added at end. Not included in SMS (only subject and link is sent).

	<h4>Email Cooldown</h4>
	<input name="email_cooldown" type="text" id="email_cooldown" size="10" maxlength="16" value="<?php echo esc_attr( $options['email_cooldown'] ); ?>"/> seconds
	<br>Set a minimal cooldown for sending email to same user. Prevents generating high volume of emails and blacklisting due to rate limits. Default: <?php echo esc_html( $optionsDefault['email_cooldown'] ); ?>


	<?php
			break;

		case 'client':
			// ! Client Settings

			$options['welcomeClient']    = stripslashes( $options['welcomeClient'] ) ;

			$options['roleClient'] = sanitize_title_with_dashes( $options['roleClient'] );

?>
<h3>Client / Viewers </h3>
Settings for client role and client interface in chat,  visitors and generally viewers (not performers/providers), client dashboard.

<h4>Client Role Name</h4>
<input name="roleClient" type="text" id="roleClient" size="20" maxlength="64" value="<?php echo esc_attr( $options['roleClient'] ); ?>"/>
<br>This is used as registration role option.  Should be 1 role name, without special characters.
<br>Sample values: client, customer, student, member, subscriber
<br>Warning: New role is only assigned to <a href="admin.php?page=live-webcams&tab=integration">new registrations if enabled</a>. <a href="users.php">Previously registered users</a> need to be assigned to new role manually.

<h4>Who can access videochat</h4>
<select name="canWatch" id="canWatch">
  <option value="all" <?php echo $options['canWatch'] == 'all' ? 'selected' : ''; ?>>Anybody</option>
  <option value="members" <?php echo $options['canWatch'] == 'members' ? 'selected' : ''; ?>>All Members</option>
  <option value="list" <?php echo $options['canWatch'] == 'list' ? 'selected' : ''; ?>>Members in List</option>
</select>
<br>Performers can access their own rooms even if they don't have permissions to access free chat.
<br>Restriction does not apply to private calls.

<h4>Members allowed to access videochat (comma separated usernames, roles, IDs)</h4>
<textarea name="watchList" cols="100" rows="2" id="watchList"><?php echo esc_textarea( $options['watchList'] ); ?>
</textarea>

<h4>Restricted Roles</h4>
<input name="roleRestricted" type="text" id="roleRestricted" size="40" maxlength="64" value="<?php echo esc_attr( $options['roleRestricted'] ); ?>"/>
<br>Comma separated roles that are not allowed to access rooms except their own. Performers can be prevented from accessing rooms of other performers.
 
<h4>Anonymous Frontend Registration</h4>
<select name="registrationAanonymous" id="registrationAanonymous">
  <option value="" <?php echo $options['registrationAanonymous'] == '' ? 'selected' : ''; ?>>No</option>
  <option value="client" <?php echo $options['registrationAanonymous'] == 'client' ? 'selected' : ''; ?>>Client</option>
  <option value="all" <?php echo $options['registrationAanonymous'] == 'all' ? 'selected' : ''; ?>>All</option>
</select>
<br>Hide first name and last name WP fields from registration form. 

<h4>Frontend Registration Without Email Activation</h4>
<select name="registrationNoActivation" id="registrationNoActivation">
  <option value="" <?php echo $options['registrationNoActivation'] == '' ? 'selected' : ''; ?>>No</option>
  <option value="client" <?php echo $options['registrationNoActivation'] == 'client' ? 'selected' : ''; ?>>Client</option>
  <option value="all" <?php echo $options['registrationNoActivation'] == 'all' ? 'selected' : ''; ?>>All</option>
</select>
<br>Do not require email activation for registration. Not recommended as it can result in bulk registrations of bots or scam accounts. Configure Google reCAPTCHA v3 before using this.
 
<h4>Client Profile Link Prefix</h4>
<input name="clientProfile" type="text" id="clientProfile" size="80" maxlength="128" value="<?php echo esc_attr( $options['clientProfile'] ); ?>"/>
<br>Leave blank to disable. Customize if there is profile page generated by a special plugin or theme feature.
<br>Ex: https://yoursite.com/profile/ (if profile is https://yoursite.com/profile/[user_nicename]
<BR>Your user_nicename based link (for troubleshooting):
				<?php
			$current_user = wp_get_current_user();
			if ( $current_user && $current_user->user_nicename ) {
				echo esc_html( $options['clientProfile'] ?? '' ) . esc_html( $current_user->user_nicename );
			}
?>

<h4>Client Wallet</h4>
<select name="clientWallet" id="clientWallet">
  <option value="1" <?php echo $options['clientWallet'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['clientWallet'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Show wallet, balance on <a href="<?php echo get_permalink( $options['p_videowhisper_webcams_client'] ); ?>">client dashboard page</a>.

<h4>Locked Calls</h4>
<select name="calls" id="calls">
  <option value="1" <?php echo $options['calls'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['calls'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Enable locked calls in performer and client dashboards.

<h4>Enable Questions / Messages</h4>
<select name="messages" id="messages">
  <option value="0" <?php echo $options['messages'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['messages'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Clients can send questions/messages to rooms and performers can answer.

<h4>Client Subcriptions</h4>
<select name="clientSubscriptions" id="clientSubscriptions">
  <option value="1" <?php echo $options['clientSubscriptions'] ? 'selected' : ''; ?>>Enabled</option>
  <option value="0" <?php echo $options['clientSubscriptions'] ? '' : 'selected'; ?>>Disabled</option>
</select>
<br>List purchased subscriptions in client dashboard page, if MicroPayments is enabled.

<h4>Client Content</h4>
<select name="clientContent" id="clientContent">
  <option value="1" <?php echo $options['clientContent'] ? 'selected' : ''; ?>>Enabled</option>
  <option value="0" <?php echo $options['clientContent'] ? '' : 'selected'; ?>>Disabled</option>
</select>
<br>List purchased content in client dashboard page, if MicroPayments is enabled.

<h4>WP Registration Form Roles</h4>
<select name="registrationFormRole" id="registrationFormRole">
  <option value="1" <?php echo $options['registrationFormRole'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['registrationFormRole'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Add roles to default WordPress registration form so users can register as client, performer or studio (if enabled). Disable only if you use other roles and assignation system (ie. with a membership plugin).
<br>User can also add roles after registration from client dashboard, if enabled.
<br>BuddyPress: This option disabled redirect to BP registration form because that does not include the roles.

<h4>Add Roles</h4>
<select name="addRoles" id="addRoles">
  <option value="1" <?php echo $options['addRoles'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['addRoles'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Allows user to add roles after registration from client dashboard. Enable to allow users to add multiple roles (client, performer, studio) to same account.

<h4>Free Chat Time Limit for Visitors - Daily</h4>
<input name="freeTimeLimitVisitor" type="text" id="freeTimeLimitVisitor" size="11" maxlength="64" value="<?php echo esc_attr( $options['freeTimeLimitVisitor'] ); ?>"/>s
<BR>Maximum time (seconds) per day a visitor can participate in free chat. Calculated based on chat session logs. Tracked by IP.
<br>Can be lower (ex. 20min) so users register to get more time. When reached, visitor can register or wait until next day. Default: <?php echo esc_html( $optionsDefault['freeTimeLimitVisitor'] ); ?>



<h4>Free Chat Time Limit for Users - Daily</h4>
<input name="freeTimeLimit" type="text" id="freeTimeLimit" size="11" maxlength="64" value="<?php echo esc_attr( $options['freeTimeLimit'] ); ?>"/>s
<BR>Maximum time (seconds) per day a user can participate in free chat. Calculated based on chat session logs.
<br>Should be big (ex. 6h) as it's required for users to access lobbies and private shows.
<br>Warning: Do not set very low. When reached, user can only visit paid group rooms or wait until next day. Can't enter free lobby to request paid private shows. Default: <?php echo esc_html( $optionsDefault['freeTimeLimit'] ); ?>

<h4>Unlimited Free Chat Time Balance</h4>
<input name="freeTimeBalance" type="text" id="freeTimeBalance" size="11" maxlength="64" value="<?php echo esc_attr( $options['freeTimeBalance'] ); ?>"/> <?php echo esc_html( $options['currency'] ) ?>
<br>Users that have at least this amount in their wallet get unlimited free time. When users consume all free chat time, they are notified about this, if enabled.
<br>Set 0 to disable.

<h4>Welcome Message for Client</h4>
<textarea name="welcomeClient" id="parametersPerformer" cols="100" rows="2"><?php echo esc_textarea( $options['welcomeClient'] ); ?></textarea>

<h4>When Performer Offline</h4>
<select name="performerOffline" id="performerOffline">
  <option value="show" <?php echo $options['performerOffline'] == 'show' ? 'selected' : ''; ?>>Show Chat</option>
  <option value="warn" <?php echo $options['performerOffline'] == 'warn' ? 'selected' : ''; ?>>Warn & Show</option>
  <option value="hide" <?php echo $options['performerOffline'] == 'hide' ? 'selected' : ''; ?>>Hide Chat</option>
</select>
<br>Controls if chat room interface is shown when performer (host) is offline.
<br>When performer is offline the adaptive interface is displayed (HTML5 Videochat if configured). When performer is online, the interface used by performer is displayed.


<h4>Performer Offline Warning</h4>
				<?php
			$options['performerOfflineMessage'] = stripslashes( $options['performerOfflineMessage'] );
			wp_editor( $options['performerOfflineMessage'], 'performerOfflineMessage', $settings = array( 'textarea_rows' => 3 ) );

?>
<br>Show this when performer is offline (if enabled).
<br>Does not show for performers rooms with <a href="admin.php?page=live-webcams&tab=video">scheduled videos</a> (fake live performers with video loops).

				<?php
			break;
		case 'features':
			// ! Webcam Room Features
?>
<h3>Webcam Room Features</h3>
Enable webcam room features, accessible by performer.
<br>Specify comma separated list of user roles, emails, logins able to setup these features for their rooms.
<br>Use All to enable for everybody and None or blank to disable.
				<?php

			$features = self::roomFeatures();

			foreach ( $features as $key => $feature )
			{
				if ( $feature['installed'] )
				{
					echo '<h3>' . esc_html( $feature['name'] ) . '</h3>';
					echo '<textarea name="' . esc_attr( $key ) . '" cols="64" rows="2" id="' . esc_attr( $key ) . '">' . esc_textarea( trim( $options[ $key ] ) ) . '</textarea>';
					echo '<br>' . esc_html( $feature['description'] );
				}
			}

			break;

		case 'tips':
?>
<h3>Gifts/Tips/Donations &amp; Goals</h3>
<a target="_read" href="https://paidvideochat.com/features/tips/">Read about performer tips ...</a>

<h4>Enable Gifts/Tips/Donations</h4>
<select name="tips" id="tips">
  <option value="1" <?php echo $options['tips'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['tips'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Enable clients to send gifts/donations to performers.
<br>In HTML5 Videochat, gifts/donations are shared if there's multiple checked in performers and are counted towards goals that display in chat.
				<?php
			$options['goalsConfig'] = stripslashes( $options['goalsConfig'] );
?>

<h4>Donate User Roles</h4>
<input name="rolesDonate" type="text" id="rolesDonate" size="100" maxlength="250" value="<?php echo esc_attr( $options['rolesDonate'] ); ?>"/>
<BR>Comma separated roles allowed to donate. Ex: administrator, editor, author, contributor, subscriber, performer, creator, studio, client, fan
<br>Leave empty to allow anybody or only an inexistent role (none) to disable for everybody.
<br> - Your roles (for troubleshooting):
				<?php
			global $current_user;
			foreach ( $current_user->roles as $role )
			{
				echo esc_html( $role ) . ' ';
			}
?>
			<br> - Current WordPress roles:
				<?php
			global $wp_roles;
			foreach ( $wp_roles->roles as $role_slug => $role )
			{
				echo esc_html( $role_slug ) . '= "' . esc_html( $role['name'] ) . '" ';
			}
?>


<h4>Enable Room Goals</h4>
<select name="goals" id="goals">
  <option value="1" <?php echo $options['goals'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['goals'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Goals are based on total gifts/donations from all users (crowdfunding).


<h4>Goals Configuration</h4>
<textarea name="goalsConfig" id="goalsConfig" cols="120" rows="12"><?php echo esc_textarea( $options['goalsConfig'] ); ?></textarea>
<BR>
Default:<br><textarea readonly cols="120" rows="6"><?php echo esc_textarea( $optionsDefault['goalsConfig'] ); ?></textarea>
<BR>Parsed configuration (should be an array or arrays) JSON:<BR>
				<?php

			echo esc_html( wp_json_encode( $options['goalsDefault'] ) );
?>
<BR>Serialized:<BR>
				<?php

			echo esc_html( serialize( $options['goalsDefault'] ) );
?>


<h4>Tip Options</h4>
				<?php
			$options['tipOptions'] = stripslashes( $options['tipOptions'] ) ;
?>
<textarea name="tipOptions" id="tipOptions" cols="100" rows="8"><?php echo esc_textarea( $options['tipOptions'] ); ?></textarea>
<br>List of tip options as XML. Sounds and images must be deployed in videowhisper/templates/messenger/tips folder.
Set amount="custom" to allow user to select amount. Custom tips only work in flash App.
Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['tipOptions'] ); ?></textarea>

<br>Tips data parsed:
				<?php

			if ( $options['tipOptions'] )
			{
				$p = xml_parser_create();
				xml_parse_into_struct( $p, trim( $options['tipOptions'] ), $vals, $index );
				$error = xml_get_error_code( $p );
				xml_parser_free( $p );

				if ( $error )
				{
					echo '<br>Error:' . esc_html( xml_error_string( $error ) );
				}

				if ( is_array( $vals ) )
				{
					foreach ( $vals as $tKey => $tip )
					{
						if ( $tip['tag'] == 'TIP' )
						{
							echo '<br>- ';
							echo esc_html( json_econde( ( $tip['attributes'] ) ) ) ;
						}
					}
				}
			}
?>

<h4>Performer Tip Earning Ratio</h4>
<input name="tipRatio" type="text" id="tipRatio" size="10" maxlength="16" value="<?php echo esc_attr( $options['tipRatio'] ); ?>"/>
<br>Performer receives this ratio from client tip.
<br>Ex: 0.9; Set 0 to disable (performer receives nothing). Set 1 for performer to get full amount paid by client.
<br>Site earns depending on performer earning ration (ex. 0.90 ratio for performer leaves 10% to site).
<br>Using  different rates for show minutes and tips could be an incentive for performers to encourage clients to contribute in a certain way.

<h4>Client Tip Cooldown</h4>
<input name="tipCooldown" type="text" id="tipCooldown" size="10" maxlength="16" value="<?php echo esc_attr( $options['tipCooldown'] ); ?>"/>s
<BR>A minimum time client has to wait before sending a new tip. This prevents accidental multi tipping and overspending. Set 0 to disable (not recommended).

<h4>Manage Balance Page</h4>
<select name="balancePage" id="balancePage">
<option value='-1'
				<?php
			if ( $options['balancePage'] == -1 )
			{
				echo 'selected';}
?>
>None</option>
				<?php

			$args   = array(
				'sort_order'   => 'asc',
				'sort_column'  => 'post_title',
				'hierarchical' => 1,
				'post_type'    => 'page',
				'post_status'  => 'publish',
			);
			$sPages = get_pages( $args );
			foreach ( $sPages as $sPage )
			{
				echo '<option value="' . esc_attr( $sPage->ID ) . '" ' . ( $options['balancePage'] == ( $sPage->ID ) || ( $options['balancePage'] == 0 && $sPage->post_title == 'My Wallet' ) ? 'selected' : '' ) . '>' . esc_html( $sPage->post_title ) . '</option>' . "\r\n";
			}
?>
</select>
<br>Page linked from balance section, usually a page where registered users can buy credits.

<h4>Client Wallet</h4>
<select name="clientWallet" id="clientWallet">
  <option value="1" <?php echo $options['clientWallet'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['clientWallet'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Show wallet, balance on <a href="<?php echo get_permalink( $options['p_videowhisper_webcams_client'] ); ?>">client dashboard page</a>.



<h4>Performer Checkins</h4>
<select name="checkins" id="checkins">
  <option value="1" <?php echo $options['checkins'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['checkins'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Checkins enables a performer/provider to checkin other performers when going live for sharing profits from group paid chat and gifts/donations. In example when another performer joins the first in same physical location for a shared performance or multiple providers do a group webinar with video conferencing.

				<?php submit_button(); ?>

<a name="brave"></a>

<h3>Brave Tips and Rewards in Cryptocurrencies</h3>
<a href="https://brave.com/pai553">Brave</a> is a special build of the popular Chrome browser, focused on privacy and speed (by not loading ads), already used by millions. Why use Brave? In addition to privacy and speed, users get grants, airdrops and rewards from ads they are willing to watch. Content creators (publishers) like site owners get tips and automated revenue from visitors. This is done in $BAT and can be converted to other cryptocurrencies like Bitcoin or withdrawn in USD, EUR.
<br>Additionally, with Brave you can easily test if certain site features are disabled by privacy features, cookie restrictions or common ad blocking rules.
	<p>How to receive contributions and tips for your site:
	<br>+ Get the <a href="https://brave.com/pai553">Brave Browser</a>. You will get a browser wallet, airdrops and get to see how tips and contributions work.
	<br>+ Join <a href="https://creators.brave.com/">Brave Creators Publisher Program</a> and add your site(s) as channels. If you have an established site, you may have automated contributions or tips already available from site users that accessed using Brave. Your site(s) will show with a Verified Publisher badge in Brave browser and users know they can send you tips directly.
	<br>+ You can setup and connect an Uphold wallet to receive your earnings and be able to withdraw to bank account or different wallet. You can select to receive your deposits in various currencies and cryptocurrencies (USD, EUR, BAT, BTC, ETH and many more).
</p>

<h3>Lovense Toy Integration</h3>
Videochat application can notify Lovense browser/extension when performer receives tips (to activate toy).
See <a href="admin.php?page=live-webcams&tab=lovense">Lovense integration settings</a> section for more details.

				<?php
			break;

		case 'ppv':
			// ! Pay Per View Settings
?>
<h3>Pay per View / Pay per Minute Settings</h3>
Configure pay per minute settings. Transactions are in tokens (credits), in site wallet. Requires configuring a <a href="admin.php?page=live-webcams&tab=billing">wallet</a> plugin.
<br> + Read about <a target="_read" href="https://paidvideochat.com/features/pay-per-view-ppv/">Pay per view (PPV) and pay per minute (PPM)</a>
<br> + Read about <a target="_read" href="https://paidvideochat.com/features/billing-payment-gateways/">Billing gateways and tokens</a>

<h4>Removing Paid Features</h4>
You can remove micropayment related features if you don't need this or plan to implement payment in a different way (like membership or other ecommerce options):
<br> - Remove <a href="admin.php?page=live-webcams&tab=group">paid chat options</a> like the [Group Paid Show] section. Leave only chat modes you need.
<br> - Set cost per minute to 0 in settings below. Disable balance page, checkins.
<br> - Set None to disable paid options from <a href="admin.php?page=live-webcams&tab=features">performer features</a> like Cost Per Minute.
<br> - Disable <a href="admin.php?page=live-webcams&tab=tips">tips and gift goals</a>.
<br> - Customize <a href="admin.php?page=live-webcams&tab=customize">role labels</a>, if needed.
<br> - <a href="plugins.php">Disable</a> integrated payment & <a href="admin.php?page=live-webcams&tab=billing">billing</a> plugins in reverse order of dependence: in example TeraWallet/CCBill/Autocomplete then WooCommerce, MyCred, MicroPayments. Warning: If not disabled in right order, some plugins may generate errors about dependencies and you can remove these with FTP.

<h4>MicroPayments Author Subscriptions</h4>
<a href="admin.php?page=live-webcams&tab=group">Paid group chat modes</a> can be accessed by subscription (instead of pay per minute) with MicroPayments plugin. Performer configures a subscription tier from Performer Dashboard > Setup tab and during paid group shows subscribers access for free and other users have to pay per minute. To only allow subscribers to access, remove webcam post type from VideoWhisper &gt; MicroPayments plugin settings > Author Subscriptions > Externally Managed Post Types so MicroPayments disables access to chat interface for rest of users.



<h4>Manage Balance Page</h4>
<select name="balancePage" id="balancePage">
<option value='-1'
				<?php
			if ( $options['balancePage'] == -1 )
			{
				echo 'selected';}
?>
>Disable</option>
				<?php

			$args   = array(
				'sort_order'   => 'asc',
				'sort_column'  => 'post_title',
				'hierarchical' => 1,
				'post_type'    => 'page',
				'post_status'  => 'publish',
			);
			$sPages = get_pages( $args );
			foreach ( $sPages as $sPage )
			{
				echo '<option value="' . esc_attr( $sPage->ID ) . '" ' . ( $options['balancePage'] == ( $sPage->ID ) || ( $options['balancePage'] == 0 && $sPage->post_title == 'My Wallet' ) ? 'selected' : '' ) . '>' . esc_html( $sPage->post_title ) . '</option>' . "\r\n";
			}
?>
</select>
<br>Page linked from balance section, usually a page where registered users can buy credits. Recommended: My Wallet (setup with <a href="https://wordpress.org/plugins/paid-membership/">MicroPayments - Paid Membership & Content</a> plugin).
<br> + See <a href="admin.php?page=live-webcams&tab=billing">Billing Wallets and Settings</a>

<h4>Performer Checkins</h4>
<select name="checkins" id="checkins">
  <option value="1" <?php echo $options['checkins'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['checkins'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Checkins enables a performer/provider to checkin other performers when going live for sharing profits from group paid chat and gifts/donations. In example when another performer joins the first in same physical location for a shared performance or multiple providers do a group webinar with video conferencing.

<h3>Private Show / 1 on 1 Calls</h3>
Configure pay per minute for private shows and calls. For configuring cost per minute in group chat see <a href="admin.php?page=live-webcams&tab=group">Group Chat Mode Settings</a>.

<h4>Grace Time</h4>
<p>Private video chat is charged per minute after this time.</p>
<input name="ppvGraceTime" type="text" id="ppvGraceTime" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvGraceTime'] ); ?>"/>s
<br>Ex: 30; Set 0 to disable.

<h4> Pay Per Minute Cost for Client in Private Video Shows/Calls</h4>
<p>Paid by client in private video chat, calls.</p>
<input name="ppvPPM" type="text" id="ppvPPM" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvPPM'] ); ?>"/>
<br>Ex: 0.5; Set 0 to disable.
<br>This is default value. If <a href="admin.php?page=live-webcams&tab=features">Cost Per Minute feature is enabled</a>, performers can also setup their own custom CPM.
<br>Admins can edit performer Custom CPM with Quick Edit from <a href="edit.php?post_type=<?php echo esc_attr( $options['custom_post'] ); ?>">Webcams</a> list.
<br>Locked private calls setup as Free have no cost.

<h4>Audio Only Pay Per Minute Cost for Client in Private Shows/Calls</h4>
<input name="ppvPPMaudio" type="text" id="ppvPPMaudio" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvPPMaudio'] ); ?>"/>
<br>Ex: 0.3; Set 0 to disable.

<h4>Text Only Pay Per Minute Cost for Client in Private Shows/Calls</h4>
<input name="ppvPPMtext" type="text" id="ppvPPMtext" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvPPMtext'] ); ?>"/>
<br>Ex: 0.2; Set 0 to disable.

<h4>Minimum Pay Per Minute Cost for Client</h4>
<p>Minimum cost per minute configurable by performer (if permitted). Limits both private/group CPM.</p>
<input name="ppvPPMmin" type="text" id="ppvPPMmin" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvPPMmin'] ); ?>"/>
<br>Ex: 0.1; Set 0 to disable.

<h4>Maximum Pay Per Minute Cost for Client</h4>
<p>Maximum cost per minute configurable by performer (if permitted). Limits both private/group CPM.</p>
<input name="ppvPPMmax" type="text" id="ppvPPMmax" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvPPMmax'] ); ?>"/>
<br>Ex: 5

<h4>Pay Per Minute Cost for Performer</h4>
<p>Performers can also be charged for the private video chat time.</p>
<input name="ppvPerformerPPM" type="text" id="ppvPerformerPPM" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvPerformerPPM'] ); ?>"/>s
<br>Ex: 0.10; Set 0 to disable.


<h3>Common PPM Settings</h3>
Apply for private and group pay per view videochat.

<h4>Credits/Tokens Currency Label</h4>
<input name="currency" type="text" id="currency" size="8" maxlength="12" value="<?php echo esc_attr( $options['currency'] ); ?>"/>

<h4>Credits/Tokens Currency Per Minute Label</h4>
<input name="currencypm" type="text" id="currencypm" size="8" maxlength="20" value="<?php echo esc_attr( $options['currencypm'] ); ?>"/>

<h4>Performer Earning Ratio</h4>
<p>Performer receives this ratio from client charge.</p>
<input name="ppvRatio" type="text" id="ppvRatio" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvRatio'] ); ?>"/>
<br>Ex: 0.8; Set 0 to disable. Set 1 for performer to get full amount paid by client.
<br>Admins can edit Custom Earning Ratio per performer, with Quick Edit from <a href="edit.php?post_type=<?php echo esc_html( array( 'custom_post' ) ); ?>">Webcams</a> list.
<br>Site earns depending on performer earning ration (ex. 0.80 ratio for performer leaves 20% to site).


<h4>Performer Bonus Ratio</h4>
<p>Performer receives better earning rate for staying online a certain amount of time on site. Shows in performer dasboard. </p>
<input name="statsBonusRate" type="text" id="statsBonusRate" size="10" maxlength="16" value="<?php echo esc_attr( $options['statsBonusRate'] ); ?>"/>
<br>Ex: 0.9; Set 0 to disable feature. Set 1 for performer to get full amount paid by client. 

<h4>Performer Bonus Online Time</h4>
<p>Online time required to activate bonus, in seconds. Based on Performer All Time from Reports, including all time online (group/calls,free/paid).</p>
<input name="statsBonusOnline" type="text" id="statsBonusOnline" size="10" maxlength="16" value="<?php echo esc_attr( $options['statsBonusOnline'] ); ?>"/>s
<br>Ex: 36000 (10h) ; Set 0 to disable feature. 

<h4>Performer Bonus Interval</h4>
<p>Interval for achieving online time, in days.</p>
<input name="statsDuration" type="text" id="statsDuration" size="10" maxlength="16" value="<?php echo esc_attr( $options['statsDuration'] ); ?>"/>days
<br>Ex: 30 (1 month)
			

<h4>Auto Balance Limitations</h4>
<p>Automatically requires some minimum balance based on the custom CPM settings, to avoid negative balances and big calculation errors related to limitations of how system monitors sessions and calculates billing. </p>
<select name="autoBalanceLimits" id="autoBalanceLimits">
  <option value="1" <?php echo $options['autoBalanceLimits'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['autoBalanceLimits'] ? '' : 'selected'; ?>>No</option>
</select>
<BR>Recommended: Yes. Will calculate Minimum Balance for Show at 5 minutes of CPM and Minimum Balance in Show at 2 minutes of CPM. Will use maximum between calculated and configured limit.

<h4>Minimum Balance for Show</h4>
<p>Only clients that have a minimum balance can request private shows.</p>
<input name="ppvMinimum" type="text" id="ppvMinimum" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvMinimum'] ); ?>"/>
<br>Recommended 3-10 minutes worth of credit. Ex: 1.5; Set 0 to disable.

<h4>Minimum Balance in Show / for Tips</h4>
<p>Only users that have this minimum balance can continue private show or send tips. This reduces negative balance situations (overspending) due to session check/processing delay. Applies both for performer / client when cost exists.</p>
<input name="ppvMinInShow" type="text" id="ppvMinInShow" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvMinInShow'] ); ?>"/>
<br>Recommended 30s-60s worth of credit. Ex: 0.25;

<h4>Balance Warning Level</h4>
<input name="balanceWarn1Amount" type="text" id="balanceWarn1Amount" size="10" maxlength="16" value="<?php echo esc_attr( $options['balanceWarn1Amount'] ); ?>"/>
<br>Notifies client when balance is low. Does not notify users with 0 balance. Recommended 10-20min worth of credit. Ex: 9.00;

<h4>Balance Warning Message</h4>
<input name="balanceWarn1Message" type="text" id="balanceWarn1Message" size="80" maxlength="250" value="<?php echo esc_attr( $options['balanceWarn1Message'] ); ?>"/>

<h4>Balance Critical Level</h4>
<input name="balanceWarn2Amount" type="text" id="balanceWarn2Amount" size="10" maxlength="16" value="<?php echo esc_attr( $options['balanceWarn2Amount'] ); ?>"/>
<br>Notifies client when balance is very low. Does not notify users with 0 balance. Recommended 5-10min worth of credit. Ex: 4.00;

<h4>Balance Critical Message</h4>
<input name="balanceWarn2Message" type="text" id="balanceWarn2Message" size="80" maxlength="250" value="<?php echo esc_attr( $options['balanceWarn2Message'] ); ?>"/>

<h4>Bill After</h4>
<p>Closed sessions are billed after a minimum time, required for both client computers to update usage time. There's one transaction for entire private session, not for each minute or second. Session durations are aproximated depending on web status calls.</p>
<input name="ppvBillAfter" type="text" id="ppvBillAfter" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvBillAfter'] ); ?>"/>s
<br>Ex. 10s

<h4>Close Sessions</h4>
<p>After some time, close sessions terminated abruptly and delete sessions where users did not enter both, due to client error. After closing, billing can occur for valid sessions. Also used for cleaning online viewers count.</p>
<input name="ppvCloseAfter" type="text" id="ppvCloseAfter" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvCloseAfter'] ); ?>"/>s
<br>Minimum: 70 (HTML5 web status call interval can go up to 60s + web delays; lower that statusInterval would cause errors on viewer count and calculations). Ex. 120s

<h4>Keep Logs</h4>
<input name="ppvKeepLogs" type="text" id="ppvKeepLogs" size="10" maxlength="16" value="<?php echo esc_attr( $options['ppvKeepLogs'] ); ?>"/>s
<br>Time to keep session logs. Ex: 31536000 (365 days) or 2592000 (30 days). Minimum: 180s.

<h4>Online Timeout</h4>
<input name="onlineTimeout" type="text" id="onlineTimeout" size="10" maxlength="16" value="<?php echo esc_attr( $options['onlineTimeout'] ); ?>"/>s
<BR>Should be greater that statusInterval for performers (default 30s). After timeout session can be closed and then can be billed (based on Bill After setting).
<BR>Web applications are monitored by web server with status calls (as configured from <a href="admin.php?page=live-webcams&tab=performer">performer parameters</a>). Sessions length approximation depends on the statusInterval parameter. Having calls too often can cause high load on web server and reduce performance / user capacity so statusInterval should be balanced.

				<?php
			break;

		case 'billing':
?>
<h3>Billing / Payment Site Settings</h3>
Clients can prepay credits (tokens) that show in a site wallet and can be used anytime later in chat to pay for private shows per minute, send tips to performers or access paid content. Tokens can be used for services from different performers and content from different owners, anytime after deposit.
Payments (real money) go into accounts configured by site owner, setup with billing gateways (like Paypal, CCBill, Zombaio, Stripe).
<br>Configure/toggle payment features: <a href="admin.php?page=live-webcams&tab=ppv">Pay Per Minute</a>, <a href="admin.php?page=live-webcams&tab=group">Chat Modes</a>, <a href="admin.php?page=live-webcams&tab=tips">Gifts/Donations &amp; Goals</a>, <a href="admin.php?page=live-webcams&tab=messages">Paid Messages</a>.
<BR>External Documentation:  <a target="_read" href="https://paidvideochat.com/features/billing-payment-gateways/">billing features and gateways</a>, <a target="_read" href="https://paidvideochat.com/features/quick-setup-tutorial/#ppv">billing setup</a>.

<br>Solution requires at least 1 wallet plugin to enable micropayments with tokens/credits.

<h4>Active Wallet</h4>
<select name="wallet" id="wallet">
  <option value="MicroPayments" <?php echo $options['wallet'] == 'MicroPayments' ? 'selected' : ''; ?>>MicroPayments</option>
  <option value="MyCred" <?php echo $options['wallet'] == 'MyCred' ? 'selected' : ''; ?>>MyCred</option>
  <option value="WooWallet" <?php echo $options['wallet'] == 'WooWallet' ? 'selected' : ''; ?>>TeraWallet (WooCommerce)</option>
</select>
<BR>Select wallet to use with videochat solution for paid chat and tips.
<BR>Add the <a href="https://wordpress.org/plugins/paid-membership/">MicroPayments - Wallet & Paid Author Subscriptions/Downloads/Content </a> plugin and <a href="admin.php?page=paid-membership&tab=setup">Setup Pages</a> to get a My Wallet pages that provides acess to all wallets and also usefull sell paid features.

<h4>Secondary Wallet</h4>
<select name="wallet2" id="wallet2">
  <option value="" <?php echo !$options['wallet2'] ? 'selected' : ''; ?>>None</option>
  <option value="MicroPayments" <?php echo $options['wallet2'] == 'MicroPayments' ? 'selected' : ''; ?>>MicroPayments (internal)</option>
  <option value="MyCred" <?php echo $options['wallet2'] == 'MyCred' ? 'selected' : ''; ?>>MyCred</option>
  <option value="WooWallet" <?php echo $options['wallet2'] == 'WooWallet' ? 'selected' : ''; ?>>TeraWallet (WooCommerce)</option>
</select>
<br>Some setups may require features from 2 different wallets.

<h4>Multi Wallet</h4>
<select name="walletMulti" id="walletMulti">
  <option value="0" <?php echo $options['walletMulti'] == '0' ? 'selected' : ''; ?>>Disabled</option>
  <option value="1" <?php echo $options['walletMulti'] == '1' ? 'selected' : ''; ?>>Show</option>
  <option value="2" <?php echo $options['walletMulti'] == '2' ? 'selected' : ''; ?>>Manual</option>
  <option value="3" <?php echo $options['walletMulti'] == '3' ? 'selected' : ''; ?>>Auto</option>
</select>
<BR>Show will display balances for available wallets, manual will allow transferring to active/secondary wallet (one way), auto will automatically transfer all to active wallet, unless in secondary wallet. Tokens are transferred at 1:1 rate.
<br>Multiple wallets can be used to quickly add extra integrations/features, like bonus for referral or product review available in TeraWallet.
<br>Multiple token types can be confusing to users unless each has specific usage. Suggested usage: MicroPayments (internal) for site transactions, with tokens at custom exchange ratios or offers with WooCommerce token packages.  Optionally use TeraWallet for tokens at 1:1 in WooCommerce currency you decide to use on site. 

<h4>Manage Balance Page</h4>
<select name="balancePage" id="balancePage">
				<?php

			$args   = array(
				'sort_order'   => 'asc',
				'sort_column'  => 'post_title',
				'hierarchical' => 1,
				'post_type'    => 'page',
				'post_status'  => 'publish',
			);
			$sPages = get_pages( $args );
			foreach ( $sPages as $sPage )
			{
				echo '<option value="' . esc_attr( $sPage->ID ) . '" ' . ( $options['balancePage'] == ( $sPage->ID ) || ( $options['balancePage'] == 0 && $sPage->post_title == 'My Wallet' ) ? 'selected' : '' ) . '>' . esc_html( $sPage->post_title ) . '</option>' . "\r\n";
			}
?>
</select>
<br>Page linked from balance section, usually a page where registered users can buy credits. Recommended: My Wallet (setup with <a href="https://wordpress.org/plugins/paid-membership/">Paid Membership & Content</a> plugin).

<h4>Credits/Tokens Currency Label</h4>
<input name="currency" type="text" id="currency" size="8" maxlength="12" value="<?php echo esc_attr( $options['currency'] ); ?>"/>

				<?php

			submit_button();
?>
<h3>MicroPayments - VideoWhisper (internal wallet)</h3>

			<?php
			if ( is_plugin_active( 'paid-membership/paid-membership.php' ) )
			{
				echo 'MicroPayments Plugin Detected';

				if ( class_exists( 'VWpaidMembership' ) )
				{
					echo '<br>Testing balance: You have:  ' . wp_kses_post( \VWpaidMembership::micropayments_balance() ) . wp_kses_post($options['currency']);
?>
	<ul>
		<li><a class="secondary button" href="admin.php?page=micropayments-transactions">All Transactions</a></li>
		<li><a class="secondary button" href="users.php?orderby=micropayments_balance&order=desc">User List with Balance</a></li>
		<li><a class="secondary button" href="admin.php?page=paid-membership&tab=packages">Token Packages</a></li>
	</ul>
						<?php

				} else
				{
					echo 'Error: VWpaidMembership not found!';
				}
			} else
			{
				echo 'Not detected. Please install and activate <a target="_plugin" href="https://wordpress.org/plugins/paid-membership/">MicroPayments - Paid Author Subscriptions, Content, Membership</a> from <a href="plugin-install.php">Plugins > Add New</a>!';
			}

?>
MicroPayments plugin (by Videowhisper) provides a tokens wallet, author subscription tiers (clients subscribe to creator), managing paid content/downloads in frontend, content sales (different methods supported), token packages that can be purchased as WooCommerce products, multiwallet support integrating 3rd party TeraWallet (WooCommerce) & MyCred wallets.


<h3>WooCommerce Wallet (TeraWallet / WooWallet)</h3>
				<?php
			if ( is_plugin_active( 'woo-wallet/woo-wallet.php' ) )
			{
				echo 'TeraWallet for WooCommerce Plugin Detected';

				if ( $GLOBALS['woo_wallet'] )
				{
					$wooWallet = $GLOBALS['woo_wallet'];

					echo '<br>Testing balance: You have: ' . wp_kses_post( $wooWallet->wallet->get_wallet_balance( get_current_user_id() ) );

?>
	<ul>
		<li><a class="secondary button" href="admin.php?page=woo-wallet">User Credits History & Adjust</a></li>
		<li><a class="secondary button" href="users.php">User List with Balance</a></li>
		<li><a class="secondary button" href="edit.php?post_type=shop_order">Orders / Payments</a></li>
	</ul>
						<?php

				} else
				{
					echo 'Error: woo_wallet not found!';
				}
			} else
			{
				echo 'Not detected. Please install and activate <a target="_plugin" href="https://wordpress.org/plugins/woo-wallet/">WooCommerce Wallet</a> from <a href="plugin-install.php">Plugins > Add New</a>!';
			}

?>
TeraWallet plugin is based on <a href="https://woocommerce.com/?aff=18336&amp;cid=2828082">WooCommerce</a> plugin and allows customers to store their money in a digital wallet. The customers can add money to their wallet using various payment methods set by the admin, available in WooCommerce. The customers can also use the wallet money for purchasing products from the WooCommerce store.
<br> * WooCommerce Gateways: PayPal Standard/Checkout, Stripe Card/SEPA/Bancontact/SOFORT/Giropay/EPS/iDeal/Przelewy24-P24/Alipay/Multibanco, CCBill (WP plugin) .
<br> * <a href="https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/">WooCommerce Free Gateway Extensions</a>: Square, Amazon Pay, PayFast, Venmo, eWay, Klarna, Sofort, Bambora, Bolt, Paypal Venmo/Checkout/Credit.
<br> * <a href="https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/">WooCommerce Premium Gateway Extensions</a>: Authorize.Net (adult), FirstData, SagePay, WorldPay, Intuit, Elavon, Moneris, USA ePay, Payson, GoCardless, Paytrace, Ogone, NAB Transact, Payment Express, Pin Payments, Alipay, SnapScan, Paytrail, Affirm, Cybersource, Chase Paymentech, RedSys, PayWay, iPay88, PaySafe, MyGate, PayPoint, PayU, PsiGate, TrustCommerce, Merchant Warrior, e-Path, ePay, CardStream, everiPay, PencePay.

<br> + Configure WooCommerce payment gateways from <a target="_gateways" href="admin.php?page=wc-settings&tab=checkout">WooCommerce > Settings, Payments tab</a>.
<br> - Enable the myCRED – Pay with myCRED and/or Wallet – Wallet payment options at bottom so users can pay with tokens for items in their shopping cart, to enable micropayments.
<br> - For adult related sites, try the <a href="https://wordpress.org/plugins/woocommerce-payment-gateway-ccbill/">CCBill - WooCommerce Payment Gateway Plugin</a>. After activating plugin and registering with CCBILL, configure it from <a href="admin.php?page=wc-settings&tab=checkout&section=ccbill">CCBill WooCommerce Settings</a>.
<br> + Enable payment gateways from <a target="_gateways" href="admin.php?page=woo-wallet-settings">Woo Wallet Settings</a>.
<br> + Setup a page for users to buy credits with shortcode [woo-wallet]. My Wallet section is also available in WooCommerce My Account page (/my-account).
<br> + As WooCommerce requires processing of orders (to get tokens allocated), use a plugin like <a href="https://woocommerce.com/products/woocommerce-order-status-control/?aff=18336&amp;cid=2828082">Order Status Control</a> or <a href="https://wordpress.org/plugins/autocomplete-woocommerce-orders/">Autocomplete WooCommerce Orders</a> (and <a href="admin.php?page=wc-settings&tab=silkwave_aco">enable it</a>) to automatically do Processing to Completed . Or manually process from <a href="edit.php?post_status=wc-processing&post_type=shop_order">Orders</a> by editing and changing Processing to Completed. Tokens are not allocated until order is completed.
<br>TeraWallet transactions are created by user that loaded the page. Detection and billing of session that ended can be triggered by a web request from performer, client, admin or any user when using WP Cron. 
<br>If selected, plugin will try to apply a filter to enable transactions when balance is lower as recent TeraWallet versions reject negative balances.


<h4>Premium WooCommerce Plugins</h4>
<ul>

<li><a href="https://woocommerce.com/products/woocommerce-order-status-control/?aff=18336&amp;cid=2828082">Order Status Control</a> Control which Paid WooCommerce Orders are Automatically Completed so you don't have to manually Process payments. Order processing is required to get tokens allocated automatically when using TeraWallet and also to enable access for content purchased using the MicroPayments integration for selling content as WooCommerce products.</li>

<li><a href="https://woocommerce.com/products/woocommerce-memberships/?aff=18336&amp;cid=2828082">WooCommerce Memberships</a> Setup paid membership as products. Leveraged with Subscriptions plugin allows membership subscriptions.</li>
<li><a href="https://woocommerce.com/products/woocommerce-subscriptions/?aff=18336&amp;cid=2828082">WooCommerce Subscriptions</a> Setup subscription products, content. Leverages Membership plugin to setup membership subscriptionsSetup at least 1 paid role that members get by purchasing membership.</li>


<li><a href="https://woocommerce.com/products/woocommerce-bookings/?aff=18336&amp;cid=2828082">WooCommerce Booking</a> Setup booking products with calendar, <a href="https://woocommerce.com/products/bookings-availability/?aff=18336&amp;cid=2828082">availability</a>, <a href="https://woocommerce.com/products/woocommerce-deposits/?aff=18336&amp;cid=2828082">booking deposits</a>, confirmations for 1 on 1 or group bookings.<br />
Include the room link or video call link in booking product description.</li>
<li><a href="https://woocommerce.com/products/product-vendors/?aff=18336&amp;cid=2828082">WooCommerce Product Vendors</a> Allow multiple vendors to sell via your site and in return take a commission on sales. Leverage with <a href="https://woocommerce.com/products/woocommerce-product-reviews-pro/?aff=18336&amp;cid=2828082">Product Reviews Pro</a>.</li>

	<LI><a href="https://woocommerce.com/products/follow-up-emails/?aff=18336&amp;cid=2828082">WooCommerce Follow Up</a> Follow Up by emails and twitter automatically, drip campaigns.</LI>

</ul>


<h3>myCRED Wallet (MyCred)</h3>

<h4>1) myCRED</h4>
				<?php
			if ( is_plugin_active( 'mycred/mycred.php' ) )
			{
				echo 'MyCred Plugin Detected';
			} else
			{
				echo 'Not detected. Please install and activate <a target="_mycred" href="https://wordpress.org/plugins/mycred/">myCRED</a> from <a href="plugin-install.php">Plugins > Add New</a>!';
			}

			if ( function_exists( 'mycred_get_users_balance' ) )
			{
				$balance = mycred_get_users_balance( get_current_user_id() );

				echo '<br>Testing balance: You have ' . esc_html( $balance ) . ' ' . esc_html( htmlspecialchars( $options['currencyLong'] ) ) . '. ';

				if ( ! strlen( $balance ) )
				{
					echo 'Warning: No balance detected! Unless this account is excluded, there should be a MyCred balance. MyCred plugin may not be configured/enabled correctly.';
				}
?>
	<ul>
		<li><a class="secondary button" href="admin.php?page=mycred">Transactions Log</a></li>
		<li><a class="secondary button" href="users.php">User Credits History & Adjust</a></li>
	</ul>
					<?php
			}
?>
<a target="_mycred" href="https://wordpress.org/plugins/mycred/">myCRED</a> is a stand alone adaptive points management system that lets you award / charge your users for interacting with your WordPress powered website. The Buy Content add-on allows you to sell any publicly available post types, including webcam posts created by this plugin. You can select to either charge users to view the content or pay the post's author either the whole sum or a percentage.
<br> * MyCRED Gateways: PayPal Standard, BitPay Bitcoins, Skrill Moneybookers, NETBilling, Zombaio.
<br> * <a href="https://mycred.me/product-category/buycred-gateways/">MyCRED Premium Gateways</a>: 2checkout, ComproPago, CoinBase, CoinPayments, PayFast, PaymentWall, PayZa, Robokassa, Stripe, WePay.

	<br> + After installing and enabling myCRED, activate these <a href="admin.php?page=mycred-addons">addons</a>: buyCRED, Sell Content are required and optionally Notifications, Statistics or other addons, as desired for project.

	<br> + Configure in <a href="admin.php?page=mycred-settings ">Core Setting > Format > Decimals</a> at least 2 decimals to record fractional token usage. With 0 decimals, any transactions under 1 token will not be recorded.




<h4>2) myCRED buyCRED Module</h4>
				<?php
			if ( class_exists( 'myCRED_buyCRED_Module' ) )
			{
				echo 'Detected';
?>
	<ul>
		<li><a class="secondary button" href="edit.php?post_type=buycred_payment">Pending Payments</a></li>
		<li><a class="secondary button" href="admin.php?page=mycred-purchases-mycred_default">Purchase Log</a> - If you enable BuyCred separate log for purchases.</li>
		<li><a class="secondary button" href="edit-comments.php">Troubleshooting Logs</a> - MyCred logs troubleshooting information as comments.</li>
	</ul>
					<?php
			} else
			{
				echo 'Not detected. Please install and activate myCRED with <a href="admin.php?page=mycred-addons">buyCRED addon</a>!';
			}
?>

<p> + myCRED <a href="admin.php?page=mycred-addons">buyCRED addon</a> should be enabled and at least 1 <a href="admin.php?page=mycred-gateways">payment gateway</a> configured, for users to be able to buy credits.
<br> - If you don't have a payment processor, for adult contents try <a href="https://ccbill.com/merchants">CCBill</a>, <a href="https://merchant.zombaio.com/signup-merchant.asp">Zombaio</a>, <a href="https://www.authorize.net/sign-up/pricing.html">Authorize</a> and for most other types of sites try <a href="https://www.paypal.com/webapps/mpp/account-selection">Paypal</a>. Then configure in MyCred as described for <a href="https://codex.mycred.me/chapter-iii/buycred/payment-gateways/zombaio/">Zombaio</a>, <a href="https://codex.mycred.me/chapter-iii/buycred/payment-gateways/paypal/">Paypal</a>. Note: Zombaio is available with <a href="https://wordpress.org/plugins/mycred/advanced/">MyCred 1.8.8 or older</a> (download from bottom) and can be enabled in latest version by tweaking its files, as developers <a href="https://plugins.trac.wordpress.org/changeset?old_path=%2Fmycred%2Ftags%2F1.8.8%2Faddons&old=&new_path=%2Fmycred%2Ftags%2F1.8.9%2Faddons">commented some code</a> to disable adult specific Zombaio gateway.
<br> + Setup a page for users to buy credits with shortcode <a target="mycred" href="http://codex.mycred.me/shortcodes/mycred_buy_form/">[mycred_buy_form]</a>.
<br> + Also "Thank You Page", "Cancellation Page" should be set to to My Wallet, from <a href="admin.php?page=mycred-settings">buyCred settings</a>.</p>
<p>Troubleshooting: If you experience issues with IPN tests, check recent access logs (recent Visitors from CPanel) to identify exact requests from billing site, right after doing a test.</p>

<h4>3) myCRED Sell Content Module</h4>
				<?php
			if ( class_exists( 'myCRED_Sell_Content_Module' ) )
			{
				echo 'Detected';
			} else
			{
				echo 'Not detected. Please install and activate myCRED with <a href="admin.php?page=mycred-addons">Sell Content addon</a>!';
			}
?>
<p>
myCRED <a href="admin.php?page=mycred-addons">Sell Content addon</a> should be enabled as it's required to enable certain stat shortcodes. Optionally select "<?php echo esc_html( ucwords( $options['custom_post'] ) ); ?>" - I Manually Select as Post Types you want to sell in <a href="admin.php?page=mycred-settings">Sell Content settings tab</a> so access to webcams can be sold from backend. You can also configure payout to content author from there (Profit Share) and expiration, if necessary.



<h3>MicroPayment Features / Pricing</h3>
Configure paid solution features / pricing:
<br> - <a href="admin.php?page=live-webcams&tab=ppv">Pay Per Minute</a>
<br> - <a href="admin.php?page=live-webcams&tab=group">Chat Modes</a>
<br> - <a href="admin.php?page=live-webcams&tab=tips">Gifts/Donations &amp; Goals</a>
<br> - <a href="admin.php?page=live-webcams&tab=messages">Paid Messages</a>
<br>Setting prices to 0 will make features free.


				<?php

			break;

		}
		
		
		if ( ! in_array( $active_tab, array( 'setup', 'support', 'reset', 'requirements', 'billing', 'tips', 'appearance', 'payouts', 'sightengine') ) )
		{
			submit_button();
		}

		echo '</form>';
		echo '<style>
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
</style>';

	}


	// ! Feature Pages and Menus

	static function setupPagesList( $options = null )
	{

		if ( ! $options )
		{
			$options = get_option( 'VWliveWebcamsOptions' );
		}

		// shortcode pages
		return array(
			'videowhisper_webcams'            => __( 'Webcams', 'ppv-live-webcams' ),
			'videowhisper_webcams_client'     => __( 'Client Dashboard', 'ppv-live-webcams' ),
			'videowhisper_webcams_performer'  => __( 'Performer Dashboard', 'ppv-live-webcams' ),
			'videowhisper_webcams_studio'     => __( 'Studio Dashboard', 'ppv-live-webcams' ),
			'videowhisper_webcams_logout'     => __( 'Chat Logout', 'ppv-live-webcams' ),
			'videowhisper_cam_random'         => __( 'Random Room', 'ppv-live-webcams' ),
			'videowhisper_terms'              => __( 'Terms of Use', 'ppv-live-webcams' ),
			'videowhisper_privacy'            => __( 'Privacy Policy', 'ppv-live-webcams' ),
			'videowhisper_login'              => __( 'Login', 'ppv-live-webcams' ),

			'videowhisper_register_client'    => __( 'Register', 'ppv-live-webcams' ) . ' ' . ucwords( $options['roleClient'] ),
			'videowhisper_register_performer' => __( 'Register', 'ppv-live-webcams' ) . ' ' . ucwords( $options['rolePerformer'] ),
			'videowhisper_register_studio'    => __( 'Register', 'ppv-live-webcams' ) . ' ' . ucwords( $options['roleStudio'] ),

			'videowhisper_register_activate'  => __( 'Register Activate', 'ppv-live-webcams' ),
			'videowhisper_password_form'      => __( 'Change Password', 'ppv-live-webcams' ),

			'videowhisper_match' => __( 'Random Match', 'ppv-live-webcams' ),
			'videowhisper_match_form' => __( 'Match Criteria', 'ppv-live-webcams' ),

		);

	}


	static function setupPagesContent( $options = null )
	{

		if ( ! $options )
		{
			$options = get_option( 'VWliveWebcamsOptions' );
		}

		return array(
			'videowhisper_terms'              => 'Terms of Use',
			'videowhisper_privacy'            => 'Privacy Policy',

			'videowhisper_register_client'    => '[videowhisper_register_form role="' . $options['roleClient'] . '"]',
			'videowhisper_register_performer' => '[videowhisper_register_form role="' . $options['rolePerformer'] . '"]',
			'videowhisper_register_studio'    => '[videowhisper_register_form role="' . $options['roleStudio'] . '"]',

			'videowhisper_login'              => '[videowhisper_login_form role="" register="1"]',
		);
	}


	static function setupPages()
	{

		$options = get_option( 'VWliveWebcamsOptions' );
		
	
		if ( $options['disableSetupPages'] )
		{
			return;
		}

		$pages = self::setupPagesList();

		$noMenu = array( 'videowhisper_webcams_logout', 'mycred_buy_form', 'videowhisper_register_activate', 'videowhisper_password_form' );

		$parents = array(
			'videowhisper_cam_random'         => array( 'Webcams' ),
			'videowhisper_match'    		  => array( 'Webcams'),

			'videowhisper_login'              => array( 'Webcams' ),
			'videowhisper_terms'              => array( 'Webcams' ),
			'videowhisper_privacy'            => array( 'Webcams' ),

			'videowhisper_webcams_performer'  => array( 'Performer', 'Performer Dashboard' ),
			'videowhisper_register_performer' => array( 'Performer', 'Performer Dashboard' ),

			'videowhisper_webcams_client'     => array( 'Client', 'Client Dashboard' ),
			'videowhisper_register_client'    => array( 'Client', 'Client Dashboard' ),
			'videowhisper_match_form'    => array( 'Client', 'Client Dashboard' ),

			'videowhisper_webcams_studio'     => array( 'Studio', 'Studio Dashboard' ),
			'videowhisper_register_studio'    => array( 'Studio', 'Studio Dashboard' ),
		);

		// custom content (not shortcode)
		$content = self::setupPagesContent();

		$duplicate = array( 'videowhisper_webcams', 'videowhisper_webcams_performer', 'videowhisper_webcams_client', 'videowhisper_webcams_studio' );

		// create a menu and add pages
		$menu_name   = 'VideoWhisper';
		$menu_exists = wp_get_nav_menu_object( $menu_name );

		if ( ! $menu_exists )
		{
			$menu_id = wp_create_nav_menu( $menu_name );
		} else
		{
			$menu_id = $menu_exists->term_id;
		}
		$menuItems = array();

		// create pages if not created or existant
		foreach ( $pages as $key => $value )
		{
			$pid  = $options[ 'p_' . $key ] ?? 0;
			$page = get_post( $pid );
			if ( ! $page )
			{
				$pid = 0;
			}

			if ( ! $pid )
			{
				$page                   = array();
				$page['post_type']      = 'page';
				$page['post_parent']    = 0;
				$page['post_status']    = 'publish';
				$page['post_title']     = $value;
				$page['comment_status'] = 'closed';

				if ( array_key_exists( $key, $content ) )
				{
					$page['post_content'] = $content[ $key ]; // custom content
				} else
				{
					$page['post_content'] = '[' . $key . ']';
				}

				$pid = wp_insert_post( $page );

				$options[ 'p_' . $key ] = $pid;
				$link                   = get_permalink( $pid );

				// get updated menu
				if ( $menu_id )
				{
					$menuItems = wp_get_nav_menu_items( $menu_id, array( 'output' => ARRAY_A ) );
				}

				// find if menu exists, to update
				$foundID = 0;
				foreach ( $menuItems as $menuitem )
				{
					if ( $menuitem->title == $value )
					{
						$foundID = $menuitem->ID;
						break;
					}
				}

				if ( ! in_array( $key, $noMenu ) )
				{
					if ( $menu_id )
					{
						// select menu parent
						$parentID = 0;
						if ( array_key_exists( $key, $parents ) )
						{
							foreach ( $parents[ $key ] as $parent )
							{
								foreach ( $menuItems as $menuitem )
								{
									if ( $menuitem->title == $parent )
									{
										$parentID = $menuitem->ID;
										break 2;
									}
								}
							}
						}

						// update menu for page
						$updateID = wp_update_nav_menu_item(
							$menu_id,
							$foundID,
							array(
								'menu-item-title'     => $value,
								'menu-item-url'       => $link,
								'menu-item-status'    => 'publish',
								'menu-item-object-id' => $pid,
								'menu-item-object'    => 'page',
								'menu-item-type'      => 'post_type',
								'menu-item-parent-id' => $parentID,
							)
						);

						// duplicate menu, only first time for main menu
						if ( ! $foundID )
						{
							if ( ! $parentID )
							{
								if ( intval( $updateID ) )
								{
									if ( in_array( $key, $duplicate ) )
									{
										wp_update_nav_menu_item(
											$menu_id,
											0,
											array(
												'menu-item-title'  => $value,
												'menu-item-url'    => $link,
												'menu-item-status' => 'publish',
												'menu-item-object-id' => $pid,
												'menu-item-object' => 'page',
												'menu-item-type'   => 'post_type',
												'menu-item-parent-id' => $updateID,
											)
										);
									}
								}
							}
						}
					}
				}
			}
		}

		update_option( 'VWliveWebcamsOptions', $options );
	}
//

static function adminAPF()
	{
		$thisAPFversion = '2023.08.27c'; //should match that in apf/allowed-plugins-filter.php
		$options = self::getOptions();
		
?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>APF Optimizer: Configure Allowed Plugins Filter</h2>
</div>
Allowed Plugins Filter Optimizer is a <a href="https://wordpress.org/documentation/article/must-use-plugins">must use plugin</a> that allows website admin to control what plugins are active for specific requests, to reduce server load, improve security, increase setup scalability.
<br>Warning: This implements advanced functionality that should be carefully configured and tested because it can remove or break features, depending on each website.
<?php
$installed = 0;
if (defined('VIDEOWHISPER_APF_VERSION'))
{
	echo '<br>Detected APF Version: ' . esc_html( VIDEOWHISPER_APF_VERSION );
	$installed = VIDEOWHISPER_APF_VERSION;
}
else echo '<br>APF not detected. Save Changes to install and activate!';
?>

<?php
$optionsAPF = get_option( 'videowhisper_apf_ajax' );

if ( isset( $_POST ) )
{
	if ( ! empty( $_POST ) )
	{

		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'vwsec' ) )
		{
			echo 'Invalid nonce!';
			exit;
		}

		if (isset($_POST['videowhisper_apf_install']))
		{
			$install = intval( $_POST['videowhisper_apf_install'] );

			if (!$install && $videowhisperAPFversion)
			{
				echo '<div>Removing APF...</div>';

				//remove from wp-content/mu-plugins 
				if (file_exists(WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php'))
				unlink ( WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php' );
				else echo '<div>APF file not found in mu-plugins folder: ' . esc_html( WP_CONTENT_DIR ) . '/mu-plugins/allowed-plugins-filter.php' . ' </div>';
			}

			if ($install && $thisAPFversion > $installed )
			{
				echo '<div>Installing latest APF. Reload to detect...</div>';

				//create mu-plugins folder if missing
				if (!file_exists(WP_CONTENT_DIR . '/mu-plugins')) mkdir ( WP_CONTENT_DIR . '/mu-plugins' );

				//copy from apf folder in plugin folder
				if (file_exists(WP_PLUGIN_DIR  . '/ppv-live-webcams/apf/allowed-plugins-filter.code'))
				copy ( WP_PLUGIN_DIR  . '/ppv-live-webcams/apf/allowed-plugins-filter.code', WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php' );
				else echo '<div>APF file not found in plugin folder: ' . esc_html( WP_PLUGIN_DIR ) . '/ppv-live-webcams/apf/allowed-plugins-filter.code' . ' </div>';

				if (file_exists(WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php')) 
				{
					$installed = $thisAPFversion;
					echo '<div>APF file detected at expected location: ' . esc_html( WP_CONTENT_DIR ) . '/mu-plugins/allowed-plugins-filter.php'  . ' </div>';
				}
			}
		}

		foreach (['vmls_app', 'vmls_cams'] as $action) 
		if ( isset($_POST['videowhisper_apf_ajax_' . $action]) && !empty($_POST['videowhisper_apf_ajax_' . $action]) ) 
		{
			$csv = sanitize_text_field( $_POST['videowhisper_apf_ajax_' . $action] );
			$items = explode( ',', $csv );

			foreach ( $items as $key => $value )
			{
				$items[ $key ] = trim( $value );
			}
			$optionsAPF[$action] = $items;

			//this plugin is always required
			$optionsAPF[$action] = $items;
			if (!in_array('ppv-live-webcams', $optionsAPF[$action])) $optionsAPF[$action][] = 'ppv-live-webcams';
		
		} else
		{
			$optionsAPF[$action] = ['ppv-live-webcams'];
		}

		update_option( 'videowhisper_apf_ajax', $optionsAPF );
	}
}
?>
<form method="post" action="<?php echo wp_nonce_url( esc_url( $_SERVER['REQUEST_URI'] ) , 'vwsec' ); ?>">

<h3>Use APF</h3>
<select name="videowhisper_apf_install">
	<option value="0">Do Not Install / Remove</option>
	<option value="1" <?php if ($installed) echo 'selected'; ?>>Install / Update / Keep</option>
</select>
<?php
echo '<br>Detected APF Version: ' . esc_html( $installed ) ;
echo '<br>Local APF Version: ' . esc_html( $thisAPFversion . ' ' . ( $thisAPFversion > $installed ? 'Save to Update!'  : '' ) ) ;
?>

<h3>AJAX Actions</h3>
Configure pluings allowed to load on specific AJAX actions. This can be used to reduce server load, improve security, increase setup scalability. Check for errors or missing features before using in a production environment. Active plugins should only include those that are required for this action to work, like billing wallets, integrated features, notifications. As a rule of thumb no new plugins should be added to the lists unless they are somehow required by integrated plugin addons.

<h4>HTML5 Videochat / AJAX action: vmls_app</h4>
This action is used for AJAX requests from HTML5 Videochat. Should include plugins powering integrated features like transaction wallet, video teaser, notifications (in example BuddyPress/BuddyBoss if there should be activity notifications when performer goes live).</br>
<?php
$pluginList = '';
if ($optionsAPF) if (isset($optionsAPF['vmls_app'])) foreach ($optionsAPF['vmls_app'] as $plugin) $pluginList .= ($pluginList ? ', ' : '' ) . $plugin;

if (!$pluginList) $pluginList = 'ppv-live-webcams, paid-membership, video-share-vod, woocommerce, woo-wallet, mycred, buddypress/bp-loader.php';
?>

			<textarea name="videowhisper_apf_ajax_vmls_app" cols="80" rows="3"><?php echo esc_attr( $pluginList ); ?></textarea>
			<br>Comma separated list of plugin names (folder name in wp-content/plugins if standard like plugin/plugin.php) or complete plugin filename. Only installed & enabled plugins that are also in this list should be loaded in this type of requests.
			<br>Suggested: ppv-live-webcams, paid-membership, video-share-vod, woocommerce, woo-wallet, mycred, buddypress/bp-loader.php

<h4>Room List / AJAX action: vmls_cams</h4>
This action is used for AJAX requests to list rooms. Should include plugins powering integrated features like video teaser, ratings. </br>
<?php
$pluginList = '';
if ($optionsAPF) if (isset($optionsAPF['vmls_cams'])) foreach ($optionsAPF['vmls_cams'] as $plugin) $pluginList .= ($pluginList ? ', ' : '' ) . $plugin;

if (!$pluginList) $pluginList = 'ppv-live-webcams, paid-membership, video-share-vod, rate-star-review';
?>

			<textarea name="videowhisper_apf_ajax_vmls_cams" cols="80" rows="3"><?php echo esc_attr( $pluginList ); ?></textarea>
			<br>Comma separated list of plugin names (folder name in wp-content/plugins if standard like plugin/plugin.php) or full path. Only installed & enabled plugins that are also in this list should be loaded in this type of requests.
			<br>Suggested: ppv-live-webcams, paid-membership, video-share-vod, rate-star-review


<?php
submit_button();
echo '</form><br>All APF AJAX options:<pre>';
echo esc_html( json_encode( $optionsAPF ) );
echo '</pre>';
}
	


	// Admin Documentation page

	
	static function adminDocs()
	{
		$options = self::getOptions();
?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>Documentation for VideoWhisper PPV Live Webcams - Paid Videochat</h2>
</div>

Find on this page: <a href="#setup">Setup Tutorial</a>, <a href="#urls">Setup Overview</a>, <a href="#shortcodes">Shortcodes</a>.
<a name="setup"></a> 
<h3>Backend Setup Tutorial</h3>
For turnkey VideoWhisper plans, most of these steps are configured by VideoWhisper, except sections specific to your business/website (like billing).
<br>After you installed and activated this Paid Videochat Turnkey Site - HTML5 PPV Live Webcams plugin by VideoWhisper:
<ol>
<li>This plugin can also use (integrate) other <a href="plugin-install.php?s=videowhisper&tab=search&type=term">plugins by Videowhisper</a>. Install and activate these (recommended): MicroPayments, VideoShareVOD, Picture Gallery, Rate Star Review.</li>
<li>Configure streaming service settings. If you have quick configuration settings (from <a href="https://webrtchost.com/hosting-plans/">VideoWhisper plans</a>), use <a href="admin.php?page=live-webcams&tab=import">Live Webcams > Settings : Import Settings</a>. Or manually configure from Streaming Server / WebRTC / FFmpeg & HLS tabs in plugin settings. Warning: It is compulsory to fill valid streaming server settings for videochat to work. If you don't have this yet, get <a href="https://webrtchost.com/hosting-plans/">HTML5 live streaming hosting</a>.</li>
<li>From <a href="options-permalink.php">Settings > Permalinks</a> enable a SEO friendly structure (ex. Post name)</li>
<li>Install and enable a <a href="admin.php?page=live-webcams&tab=billing">wallet plugin</a> (MyCRED/TeraWallet-WooCommerce).
<br>Make sure you setup a payment option (by configuring a supported billing site you register with) and a page for users to buy credits/tokens. If you don't have a payment processor, for adult contents sites you could register with CCBill, Authorize, Zombaio and for most other types of sites you could try <a href="https://www.paypal.com/webapps/mpp/account-selection">Paypal</a>.
<br>Use MicroPayments - Paid Membership/Content/Downloads plugin to setup My Wallet page and paid videos/pictures/downloads as WooCommerce products.</li>
<li><a href="admin.php?page=live-webcams&tab=pages">Setup main feature pages</a>. From <a href="nav-menus.php">Appearance > Menus</a> add Webcams and optionally the Performer Dashboard pages to main site menu. You can edit pages including Terms of Use and Privacy Policy as needed for your site/business.</li>
<li>Setup <a href="edit-tags.php?taxonomy=category&post_type=<?php echo esc_attr( $options['custom_post'] ); ?>">webcam categories</a>, common to site content.</li>
<li>Optional: From <a href="options-reading.php">Settings > Reading</a> setup Front page: Webcams if this is the main functionality you want to emphasize.</li>
<li>Registration/Login: Turnkey site solution implements frontend registration dedicated for main roles that you can disable from <a href="admin.php?page=live-webcams&tab=integration">integration settings</a>. If you prefer a common WP registration, from <a href="options-general.php">Settings > General</a> enable Membership: Anyone can register (not recommended as standard WP registration is often attacked by bots). Plugin will create and show roles on the default <a href="../wp-login.php?action=register">registration page</a>. You may need to add a menu/link/widget for users to easily find registration page.
<br>Warning: To prevent spam bot registrations, <a href="admin.php?page=live-webcams&tab=integration">configure reCAPTCHA</a>. Get a <a href="https://www.google.com/recaptcha/admin/create#list">reCaptcha key from Google</a>.</li>
<li>Multi-language chat & translations: To enable DeepL API integration for chat text translations you need to get a free or commercial DeepL API developer key and configure at <a href="<?php echo get_admin_url(); ?>/admin.php?page=live-webcams&tab=translate">Settings : DeepL / Translate</a>.</li>
<li>Recommended: Next step is to review <a href="<?php echo get_admin_url(); ?>admin.php?page=live-webcams&tab=support#plugins">suggested plugins</a>.
<br> - Install bot & hacker protection plugins. Solution was tested with <a href="admin.php?page=WordfenceWAF">WordFence Firewall</a> and its registration/login protection disabled (reCaptcha is integrated separately for frontend registration).
<br> - If you have a cache plugin like <a href="options-general.php?page=wpsupercache&tab=settings">WP Super Cache</a>, disable caching for visitors who have a cookie set in their browser, don’t cache pages with GET parameters and add and exception for "/<?php echo esc_attr( $options['custom_post'] ); ?>/" pages. Do NOT enable compression on dynamic sites as provided by these solutions, as that causes unnecessary overhead, including performance degradation, high resource usage.
<br> - Setup a reliable mailing account (from web hosting control panel i.e. cPanel) and configure a <a href="plugin-install.php?s=smtp&tab=search&type=term">WordPress SMTP plugin</a> to improve deliverability for WP emails, notifications.
<br> - Install and activate a <a href="themes.php">theme</a> with wide content area (preferably full page width) so videochat application interface fits.</li>
</ol>

<h4>Setup Tutorial</h4>
<a href="https://paidvideochat.com/features/quick-setup-tutorial/">Paid Video Chat : Quick Setup Tutorial</a>


<a name="urls"></a>

<h3>PaidVideochat Installation Overview</h3>
	- Registration - Users can register in frontend with performer and client roles from:
	<br><?php echo get_permalink( $options['p_videowhisper_register_performer'] ?? 0 ); ?>
	<br><?php echo get_permalink( $options['p_videowhisper_register_client'] ?? 0 ); ?>
	<br> + Configure reCAPTCHA v3 for registration/login: Get a key from https://www.google.com/recaptcha/admin/create and configure at <?php echo get_admin_url(); ?>admin.php?page=live-webcams&tab=integration . Do not enable default WP registration or other registration plugins, unless protected.
	<br>+ Edit your WP site admin email from <?php echo get_admin_url(); ?>options.php and keep default registration disabled if you use the frontend (protected) registration forms. Default registration and popular registration plugins are often targeted by spam bots, unless specially protected with reCaptcha or similar verification, which may require extra steps for registration/login.
	<br>If you decide to use different registration type and roles (like from a membership plugin), you will need to adjust (customize) performer/client roles to ones you plan to use for the features.

	<br><br> - Frontend Login:
	<br><?php echo get_permalink( $options['p_videowhisper_login'] ?? 0 ); ?>

	<br><br> - Performers and admins (for testing) can setup their webcam page and Go Live from:
	<br><?php echo get_permalink( $options['p_videowhisper_webcams_performer'] ?? 0 ); ?>

	<br><br> - After broadcasting, webcam shows in Webcams list:
	<br><?php echo get_permalink( $options['p_videowhisper_webcams'] ?? 0 ); ?>

	<br><br> - After login, clients can buy credits/tokens (to spend in private shows and for tips):
	<br><?php echo get_permalink( isset($options['balancePage']) ? $options['balancePage'] : 0 ); ?>

	<br><br> - Billing features, wallet plugin options and payment gateways are accessible from:
	<br><?php echo get_admin_url(); ?>admin.php?page=live-webcams&tab=billing
	<br>At least 1 tokens wallet plugin (TeraWallet for WooCommerce, MicroPayments, MyCRED) is required to enable paid solution features like pay per minute, gifts/donations, paid video questions.
	<br>Configure or disable pay per view features as described at <?php echo get_admin_url(); ?>admin.php?page=live-webcams&tab=ppv . More paid features like author subscriptions, paid content can be setup and configured from &gt; MicroPayments plugin.

	<br><br> - For paid features, a payment website is required to process cards, collect payments from clients and send to your account.
	<br> + For WooCommerce, after adding WooCommerce & TeraWallet plugins, configure payment gateways with your account details from <?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=checkout
	<br>For non adult related sites you could try Paypal https://www.paypal.com/webapps/mpp/account-selection .
	<br>For adult related sites, try the https://wordpress.org/plugins/woocommerce-payment-gateway-ccbill/ CCBill plugin for WooCommerce.  After activating plugin and registering with CCBILL, configure it from <?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=checkout&amp;section=ccbill . As WooCommerce requires processing of orders, payments need to be confirmed manually or automatically with an extra plugin (see <?php echo get_admin_url(); ?>admin.php?page=live-webcams&tab=billing ).
	<br> + For MyCred configure payment gateways with your account details from <?php echo get_admin_url(); ?>admin.php?page=mycred-gateways
	
	<br><br> - Multi-language chat & translations:
	<br>To enable DeepL API integration for chat text translations you need to get a free or commercial DeepL API developer key and configure at <?php echo get_admin_url(); ?>/admin.php?page=live-webcams&tab=translate .
	<br>WordPress, theme and site plugins can be translated with WP localization system (language files can be used, updated as needed).
	
	<br><br> - Customize setup:
	<br><?php echo get_admin_url(); ?>admin.php?page=live-webcams&tab=appearance / adjust interface for a dark theme
	<br><?php echo get_admin_url(); ?>admin.php?page=live-webcams&tab=customize / customize roles, rooms labels
	<br>Features powered by other plugins (MicroPayments, VideoShareVOD, Picture Gallery, HTML5 Webcam Recorder) can be configured from their plugins, including appearance and access roles.

	<br><br> - See suggested plugins to improve site reliability, security, performance and features:
	<br><?php echo get_admin_url(); ?>admin.php?page=live-webcams&tab=support#plugins
	<br>Recommended:
	<br> + Harden site security with WordFence plugin: configure site Firewall and automated scans. If backend registration is disabled, extra protection like WordFence > Login Security > Enable Recaptcha may require extra login steps like "Additional verification is required for login".
	<br> + A cache plugin reduces load from aggressive crawlers and bots but should be configured to allow dynamic/interactive content for logged in users. Solution was tested with WP Super Cache configured ONLY for visitors/crawlers, disabled for users with cookie or requests with GET parameters (so chat can be used by real people). Do NOT enable compression on dynamic sites as provided by these solutions, as that causes unnecessary overhead, including performance degradation, high resource usage.
	<br> + Improve deliverability of notification emails (required for registration/activation): Use a WP SMTP mailing plugin https://wordpress.org/plugins/search/smtp/ . Configure with a real email account from your hosting backend (setup an email from CPanel) or external (Gmail or other provider), to send emails using SSL and all verifications. This should reduce incidents where users don’t find registration emails due to spam filter triggering. Also instruct users to check their spam folders if they don’t find registration emails, because new domain / site IP may take some time to achieve good mailing reputation. An external SMTP mailing account can be used. After configuring SMTP plugin, test registration to make sure it emails are delivered and links are not broken (some plugins may require special settings to adjust mail formatting)	
	<br>! Warning: Install only plugins that are really necessary for project because adding many results in higher setup complexity (hassle with conflicting settings or hooks) and high resource usage per request (lower user capacity and speed for website). Often when adding plugins, less is more. If a new plugin breaks website you can remove it from wp-content/plugins/ folder with FTP or file manager in cPanel (if available). On VideoWhisper setups you can easily disable/remove/update plugins from cPanel > WordPress Management. 

	<br><br> - Setup can be customized (theme, pages, menus, features) as described at:
	<br>https://paidvideochat.com/customize
	<br>! Warning: When switching to a new theme make sure you don't enable importing demo content as that can erase/break existing pages for main solution features. You will also need to add exiting menu to new theme from Appearance > Menus.

	<br><br> - If you find this WP plugin useful or interesting, we would appreciate your review(s) in the WordPress repository. Better exposure on this platform will help us drive more resources into further development and improvements for WordPress integration plugins (provided as free php source code).
	<br>https://wordpress.org/support/plugin/ppv-live-webcams/reviews/#new-post

	<br><br> - Contact VideoWhisper technical support anytime for clarifications, troubleshooting, upgrades, improvements:
	<br>https://consult.videowhisper.com
	<br>https://videowhisper.com/tickets_submit.php



<a name="shortcodes"></a>

<h3>Shortcodes</h3>


<h4>[videowhisper_webcams menu="1" layout="grid" perpage="6" perrow="0" pstatus="" order_by= "default" category_id="" select_status="1" select_order="1" select_category="1" select_filters="1" select_page="1" select_layout="1" select_tags="1" select_name="1" include_css="1" url_vars="1" url_vars_fixed="1" studio_id="" author_id="" id="1" lovense="" tags="" name=""]
</h4>
Lists and updates webcams using AJAX. Automatically setup from Settings > Pages. Allows filtering and toggling filter controls.
<br>
/ order_by: edate = last time online
/ default = features then online
/ post_date = registration
/ viewers = currently in room
/ maxViewers = maximum viewers ever
/ rand = Random order
<br>pstatus: "" = all performers (default), can also be provided as GET parameter
/ online = online (in public or private chat)
/ public = in public chat (online and not in private)
/ private = in private shows
/ offline = currently offline
<br>category_id = filter by id number of category (see tag_ID in url when you <a href="edit-tags.php?taxonomy=category&post_type=webcam">edit WP categories</a>), can also be provided as GET parameter
<br>lovense = 0/1 (filter by lovense enabled), can also be provided as GET parameter
<br>tags = filter by tags (comma separated), can also be provided as GET parameter
<br>name = filter by name (search), can also be provided as GET parameter
<br>select_ .. = 0/1 (enables interface to select that control)
<br>perpage : number of listings to show per page (if select_page="0" that's maximum that will show)
<br>studio_id, author_id = filter based on studio or author account ID, -1 for current logged in user
<br>menu = 0/1/auto show filters as accordion menus instead of dropdowns, auto will disable menu on mobile
<br>layout = grid/list/horizontal; horizontal slider includes own buttons to scroll left/right that also load next page at end (can be used with select_page="0")
<br>id = when using shortcode multiple times on same page, different id is required to avoid element/function conflicts (experimental)
<br>custom profile fields defined as "filter" can be added as GET parameters, per this list:
<?php
	// custom profile filters
	if (is_array($options['profileFields']))
	foreach ( $options['profileFields'] as $field => $parameters ) {
		if ( isset($parameters['filter']) && isset($parameters['options']) ) {
			$fieldName  = sanitize_title( trim( $field ) );
			$fieldMeta  = 'vwf_' . $fieldName;
			echo esc_html( $fieldMeta ) . ', ';
		}
	}
?>

<h4>[videowhisper_register_form role="client" terms="" privacy=""]</h4>
Display a registration form for a specific role type, for setting up dedicated role registration pages in frontend. Automatically setup from Settings > Pages. Users submit account details including password and accounts are activated by link in email. Activation email is configurable from settings. Can show custom Terms and Privacy page links.

<h4>[videowhisper_login_form  register="0" role="" terms="" privacy=""]</h4>
Display login form. Can include registration links for all roles or a specific role. Can show custom Terms and Privacy page links.

<h4>[videowhisper_register_activate]</h4>
Required in a page for frontend registrations to confirm using link from email.  Automatically setup from Settings > Pages. Activation email is configurable from settings.

<h4>[videowhisper_callnow roomid="post id"]</h4>
Shows a Call Now button if calling is enabled or Join if not. 

<h4>[videowhisper_cam_instant]</h4>
Instantly setup and access own webcam room. Can be used for instant access to own room (and setup if missing), without going trough Performer Dashboard.

<h4>[videowhisper_match]</h4>
Random match mode for client: will try to find a performer room available in random match mode.

<h4>[videowhisper_match_form postid="0"]</h4>
For to setup match criteria. For performer rooms include post id. Define fields that can be used for matching with 'match' parameter


<h4>[videowhisper_cam_random]</h4>
Random videochat room as configured in backend. Automatically setup from Settings > Pages. Next button goes to next performer room, per settings.

<h4>[videowhisper_campreview status="online" order_by="rand" category="" perpage="1" perrow="2" width="480px" height="360px"]</h4>
Show webcam previews (video).

<h4>[videowhisper_videochat room="Room Name" webcam_id="post id"]</h4>
Shows videochat application. Automatically detects room if shown on webcam post. Room name is listed in Performer Dashboard Overview. Can also use post ID as webcam_id.


<h4>[videowhisper_webcams_performer include_css="1"]</h4>
Shows performer dashboard with balance, webcam listing management, tabs. Automatically setup from Settings > Pages.

<h4>[videowhisper_webcams_studio include_css="1"]</h4>
Shows studio dashboard. Automatically setup from Settings > Pages.

<h4>[videowhisper_account_records]</h4>
Shows account status and allows updating administrative records for current user. Administrative records refers to custom fields defined by administrators that users can fill. These are only accessible by adminstrators and can be used for identity verification, collecting payout info.

<h4>[videowhisper_camcontent cam="Room Name" post_id="0"]</h4>
Shows webcam content (tabs). Webcam listing name or post_id must be provided.

<h4>[videowhisper_camprofile cam="Room Name" post_id="0"]</h4>
Shows webcam listing profile (fields). Webcam listing name or post_id must be provided.

<h4>[videowhisper_caminfo cam="Room Name" info="cpm" format="csv"]</h4>
Shows info about a cam.
<br>info:
cpm = cost per minute for private show /
online = last time online /
brief = brief info /
tags = room tags /
performers = checked in performers (links) /
groupMode = group mode /
groupCPM = group CPM
<br>format:
csv = comma separated values /
serialized = php serialized string

<h4>[videowhisper_camvideo cam =  "Room (Webcam) Name" width="480px" height="360px" html5="auto" post_id=""]</h4>
Shows plain live video from a room, if available. A static implementation that does not update if broadcaster enters later or leaves and comes back (as for HTML5 Videochat app). Supports HTML5 HLS/MPEG-DASH/WebRTC streams (will select type on load, if available). Can provide either room name as webcam parameter or room post id as webcam_id.
<br> html5 = auto/always

<h4>[videowhisper_cam_webrtc_playback webcam="webcam name" width="480px" height="360px"  webcam_id="" silent="0"]</h4>
Shows live WebRTC stream for a webcam room, if available. Static implementation (plays only if available at moment of page load). Can provide either room name as webcam parameter or room post id as webcam_id.

<h4>[videowhisper_cammpeg webcam="webcam name" width="480px" height="360px"]</h4>
Plain video as HTML5 MPEG Dash (if supported & available).

<h4>[videowhisper_camhls webcam="webcam name" width="480px" height="360px"]</h4>
Plain video as HTML5 HLS (if supported & available).

<h4>[videowhisper_htmlchat room="webcam name" width="480px" height="360px"]</h4>
HTML ajax based simplified chat with HTML5 video playback (if supported & available).

<h4>[videowhisper_cam_app room="webcam name" webcam_id="post id" type=""]</h4>
HTML5 Videochat app interface for webcam room.
<br>type: blank = video / audio / text

<p>Warning: Some shortcodes may refer to obsolete features, integrations and may no longer be functional or suitable in latest versions.</p>

<h3>Filters</h3>
Filters allow adding content to plugin sections.

<h4>apply_filters("vw_plw_dashboard", '', $postID)</h4>
Under performer dashboard.

<h4>apply_filters("vw_plw_videochat", '', $postID)</h4>
Under videochat app.

<H3>Room List API</H3>
Get room list as JSON from this URL: <br>
<?php
		echo admin_url() . 'admin-ajax.php?action=vmls_cams&json=1&pp=10&p=0&tags=&name=&cat=&ob=default&st=all';
?>
<br>
<br> ob = order by: edate = last time online
/ default = featured then online
/ post_date = registration
/ viewers = currently in room
/ maxViewers = maximum viewers ever
/ rand = Random order
<br>st = performer status: "" = all performers (default)
/ online = online (in public or private chat)
/ public = in public chat (online and not in private)
/ private = in private shows
/ offline = currently offline
<br>pp = perPage : number of listings to show per page (if select_page="0" that's maximum that will show)
<br>p = page
<br>tags = tags (csv)
<br>name = name search
<br>The room list includes custom room profile meta fields marked with "filter" or "json" option from plugin settings, named with "vwf_" prefix.

<h3>Cookies</h3>
These cookies can be registered in a cookie manager / GDPR plugin:
<h4>htmlchat_username</h4> Required for persistence of visitor usernames in html chat and app.
		<?php
	}


	// ! user approval

	static function userEnabled( $user, $options, $role = 'Performer' )
	{
		if ( ! $user )
		{
			return;
		}
		if ( ! $user->ID )
		{
			return;
		}

		$verified       = get_user_meta( $user->ID, 'vwVerified', true ); //0 = unverified, 1 = approved, 2 = rejected
		$adminSuspended = get_user_meta( $user->ID, 'vwSuspended', true ); //0 = active, other = suspend time

		if ( $adminSuspended )
		{
			return 0;
		}
		if ( $verified == 2 ) return 0; //rejected

		if ( $verified == 1 || $options[ 'unverified' . $role ] )
		{
			return 1;
		}

		return 0;
	}

	static function roomSuspended($postID, $options = null)
	{
		$suspended = get_post_meta( $postID, 'vwSuspended', true ); //0 = active, other = suspend time
		if (!$suspended) return 0;

		if (!$options) $options = self::getOptions();
		
		//clear
		$suspendTimeout = intval($options['suspendTimeout']);
		if ($suspendTimeout) if ($suspended + $suspendTimeout < time()) 
		{
			delete_post_meta($postID, 'vwSuspended');
			return 0;
		}

		return 1;
	}


	//admin filter users by approval

	// !backend listings
	static function manage_users_columns( $columns )
	{
		$columns['vwUpdated'] = 'PaidVideochat';
		return $columns;
	}


	static function manage_users_sortable_columns( $columns )
	{
		$columns['vwUpdated'] = 'vwUpdated';
		return $columns;
	}


	static function pre_user_query( $user_search )
	{
		global $wpdb, $current_screen;

		if ( ! $current_screen )
		{
			return;
		}
		if ( 'users' != $current_screen->id )
		{
			return;
		}

		$vars = $user_search->query_vars;

		if ( 'vwUpdated' == $vars['orderby'] )
		{
			$user_search->query_from   .= " LEFT JOIN {$wpdb->usermeta} m1 ON {$wpdb->users}.ID=m1.user_id AND (m1.meta_key='vwUpdated')";
			$user_search->query_orderby = ' ORDER BY UPPER(m1.meta_value) ' . $vars['order'];
		}

	}


	static function manage_users_custom_column( $value, $column_name, $user_id )
	{
		if ( $column_name == 'vwUpdated' )
		{
			$verified  = get_user_meta( $user_id, 'vwVerified', true );
			$vwUpdated = get_user_meta( $user_id, 'vwUpdated', true );

			$studioID = get_user_meta( $user_id, 'studioID', true );

			$htmlCode  = $verified == '1' ? 'Verified' : ($verified == '2' ? 'Rejected': 'Not Verified' );
			$htmlCode .= '<small style="display:block;">Updated:<br>' . ( $vwUpdated ? date( 'F j, Y, g:i a', $vwUpdated ) : 'Never' ) . '</small>';
			if ( $studioID )
			{
				$htmlCode .= '<small style="display:block;">Studio ID: ' .intval( $studioID ) . '</small>';
			}

			$htmlCode .= '<small style="display:block;">Balance: ' . esc_html( self::balance($user_id) ). '</small>';
			$code = get_user_meta( $user_id, 'videowhisper_activate', true );

			if ($code)
			{
				$htmlCode .= '<small style="display:block;">Unconfirmed Mail</small>';

				$options = self::getOptions();
				$activation_link = add_query_arg(
					array(
						'key'  => $code,
						'user' => $user_id,
					),
					get_permalink( $options['p_videowhisper_register_activate'] )
				);

				$htmlCode .= '<div class="row-actions"><span><a href="' . esc_url( $activation_link ) . '">Force Activate</a></span></div>';
			}

			$htmlCode .= '<div class="row-actions"><span><a href="admin.php?page=live-webcams-records&user_id=' . $user_id . '">Review Records</a></span></div>';
			$htmlCode .= '<div class="row-actions"><span><a href="admin.php?page=live-webcams-studio&user_id=' . $user_id . '">Assign to Studio</a></span></div>';

			return $htmlCode;

		} else
		{
			return $value;
		}
	}

	 static function restrict_manage_users($which)
	{
		$filter = isset( $_GET['verified_' . $which] ) ? sanitize_text_field( $_GET['verified_' . $which] ) : '';

		// combine template and options
		echo sprintf('<select name="verified_%s" style="float:none;">
    <option value="" ' . ( $filter == '' ? 'selected' : '' ) . '>%s</option>%s</select>', esc_attr( $which) , __( 'Verification: All', 'ppv-live-webcams' ) , ' <option value="pending" ' . ( $filter == 'pending' ? 'selected' : '' ) . '>Pending</option>
	<option value="1" ' . ( $filter == '1' ? 'selected' : '' ) . '>Approved</option>
	<option value="2" ' . ( $filter == '2' ? 'selected' : '' ) . '>Rejected</option>
	<option value="0" ' . ( $filter == '0' ? 'selected' : '' ) . '>Not Verified</option>
	<option value="confirmed" ' . ( $filter == 'confirmed' ? 'selected' : '' ) . '>Confirmed</option>
	<option value="unconfirmed" ' . ( $filter == 'unconfirmed' ? 'selected' : '' ) . '>Unconfirmed</option>
	' );

		 submit_button(__( 'Filter', 'ppv-live-webcams'), 'primary', 'vfB_' . esc_attr( $which ) , false);
	}


	static function pre_get_users($query)
	{
		global $pagenow;
		if (is_admin() && 'users.php' == $pagenow)
		{

			// figure out which button was clicked. The $which in restrict_manage_users()
			$top = isset( $_GET['verified_top'] ) ? sanitize_text_field( $_GET['verified_top'] ) : null;
			$bottom = isset ( $_GET['verified_bottom'] ) ? sanitize_text_field( $_GET['verified_bottom'] ) : null;

			if (!empty($top) or !empty($bottom))
			{
				$filter = !empty($top) ? $top : $bottom;

				if ($filter == 'unconfirmed') $meta_query = array (
						'relation' => 'AND',
						array (
							'key' => 'videowhisper_activate',
							'compare' => 'EXISTS'
						));
				elseif ($filter == 'confirmed') $meta_query = array (
						'relation' => 'AND',
						array (
							'key' => 'videowhisper_activate',
							'compare' => 'NOT EXISTS'
						));
				elseif ($filter == 'pending') $meta_query = array(
						array(
							'relation' => 'AND',
							array(
								'key'     => 'vwUpdated',
								'value'   => '0',
								'compare' => '!=',
							),
							array(
								'key'     => 'vwVerified',
								'compare' => 'NOT EXISTS',
							),
						),
					);
				else $meta_query = array (array (
							'key' => 'vwVerified',
							'value' => $filter,
							'compare' => 'LIKE'
						));

				$query->set('meta_query', $meta_query);
			}

		}

	}


static function usersPendingApproval()
{
		$args = array(
				// 'role__in'     => array($options['rolePerformer'], $options['roleStudio'], 'administrator'),
				'meta_query' => array(
					array(
						'relation' => 'AND',
						array(
							'key'     => 'vwUpdated',
							'value'   => '0',
							'compare' => '!=',
						),
						array(
							'key'     => 'vwVerified',
							'compare' => 'NOT EXISTS',
						),
					),
				),
			);

			$users = get_users( $args );
			
			update_option('VWliveWebcams_Pending', count($users) );
			
			return $users;
}


static function adminPayoutReport()
{
	$options = self::getOptions();

	?>
	<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>Payout Reports / PaidVideochat / VideoWhisper</h2>
	</div>
	<?php

if ( isset( $_POST ) && ! empty($_POST))
{
	$nonce = $_REQUEST['_wpnonce'];
	if ( ! wp_verify_nonce( $nonce, 'vwsec' ) )
	{
		echo 'Invalid WP nonce!';
		exit;
	}
}

self::enqueueUI();

//display a form to filter payouts by payout method, payout start date, payout end date
$payoutMethod = sanitize_text_field($_POST['payoutMethod'] ?? '');
$payoutStartDate = sanitize_text_field($_POST['payoutStartDate'] ?? '');
$payoutEndDate = sanitize_text_field($_POST['payoutEndDate'] ?? '');
$perPage = intval($_POST['perPage'] ?? 50) ? intval($_POST['perPage'] ?? 50) : 50;

$save = 0;
if ($_POST['save'] ?? 0) $save = 1;

//set start date 1 month ago if not set and end date tomorrow if not set
if (!$payoutStartDate) $payoutStartDate = date('Y-m-d', strtotime('-1 month'));
if (!$payoutEndDate) $payoutEndDate = date('Y-m-d', strtotime('+1 day'));

$startTime = strtotime($payoutStartDate) ;
$endTime = strtotime($payoutEndDate);

?>
<form class="ui form" action="<?php echo wp_nonce_url( 'admin.php?page=live-webcams-payout-report', 'vwsec' ); ?>" method="post">
<div class="five fields">
<?php

$payoutMethodParams = array();
$csvDefinition = array();

$payoutMethods = $options['payoutMethods'] ?? array();
if (!is_array($payoutMethods)) $payoutMethods = array();
if (count($payoutMethods))
{
	//display a dropdown to select payout method
	echo  '<div class="field">
	<label>' . __('Payout Method', 'ppv-live-webcams') . '</label>
	<select name="payoutMethod">
	<option value="">Any</option>';	
	foreach ($payoutMethods as $method => $params)
	{
		echo '<option value="' . esc_attr($method) . '" '. ( $payoutMethod == $method ? 'selected' : '' ) . ' >' . esc_html($method) . '</option>';

		if ($method == $payoutMethod) $payoutMethodParams = $params;
	}
}
?>
</select>
</div>
<?php

echo '<div class="field">
      <label>' . __('Start Date', 'ppv-live-webcams') . '</label>
      <div class="ui calendar" id="rangestart">
        <div class="ui input left icon">
          <i class="calendar icon"></i>
          <input type="text" name="startDate" placeholder="Start" value="'. esc_attr( $payoutStartDate ) .'">
        </div>
      </div>    
    </div>
    
	<div class="field">
	  <label>' . __('End Date', 'ppv-live-webcams') . '</label>
	  <div class="ui calendar" id="rangeend">
	    <div class="ui input left icon">
	      <i class="calendar icon"></i>
	      <input type="text" name="endDate" placeholder="End" value="'. esc_attr( $payoutEndDate ) .'">
	    </div>
	  </div>      
	</div>
	
	<div class="field">
	<label>' . __('Per Page', 'ppv-live-webcams') . '</label>';
	
	//display a dropdown to select per page, where -1 is all
	$perPageOptions = array(10, 20, 50, 100, 200, 500);
	echo '<select name="perPage">';
	echo '<option value="-1" '. ( $perPage == -1 ? 'selected' : '' ) . ' >All</option>';
	//echo '<option value="-2" '. ( $perPage == -2 ? 'selected' : '' ) . ' >All &amp; Save</option>';
	foreach ($perPageOptions as $option)
	{
		echo '<option value="' . esc_attr( $option ) . '" '. ( $perPage == $option ? 'selected' : '' ) . ' >' . esc_html( $option ) . '</option>';
	}	

	echo '</select>
	</div>
	<div class="field">
	<label>Action</label>
	<input name="list" type="submit" class="ui button" value="List">
	<input name="save" type="submit" class="ui button" value="Save">
	</div>
	</div>';

	echo '<pre style="display:none">
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

});
</script>
	</pre>';

	//include page in form
	$page = intval($_POST['paged'] ?? 1) ? intval($_POST['paged'] ?? 1) : (intval($_GET['paged'] ?? 1) ? intval($_GET['paged'] ?? 1) : 1);
	echo '<input type="hidden" name="paged" value="' . intval( $page ) . '">';

	echo '</form>';

//display a payouts table with pagination, columns for username,  amount,  currency, method, date, csv fields, meta info
//get payouts for selected payout method, between selected payout start date and payout end date, sorted by date, $perPage per page, pagination
/*
CREATE TABLE `$table_payouts` (
`id` int(20) NOT NULL AUTO_INCREMENT,
`uid` int(20) NOT NULL,
`amount` decimal(8,2) NOT NULL,
`method` varchar(64) NOT NULL,
`csv` TEXT,
`meta` TEXT,
`ptime` int(11) NOT NULL,
PRIMARY KEY (`id`),
KEY `uid` (`uid`),
KEY `amount` (`amount`),
KEY `method` (`method`),
KEY `ptime` (`ptime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

global $wpdb;
$table_payouts = $wpdb->prefix . 'vw_vmls_payouts';


//do not filter by method if not defined

// Base query
$query = "SELECT * FROM $table_payouts WHERE ptime >= %d AND ptime <= %d";
$params = array($startTime, $endTime);

// Add condition for payoutMethod if not blank
if ($payoutMethod) {
    $query .= " AND method LIKE %s";
    $params[] = $payoutMethod;
}

// Add ordering
$query .= " ORDER BY ptime DESC";

// Add limit if perPage is set
if ($perPage>0) {
    $query .= " LIMIT %d, %d";
    $params[] = $perPage * ($page - 1);
    $params[] = $perPage;
}

$csvList = '';
$rows = $wpdb->get_results($wpdb->prepare($query, ...$params));

//display error if any
if ($wpdb->last_error) echo '<div class="ui error message">' . esc_html( $wpdb->last_error )  . '</div>';

//display a payouts table with pagination, columns for username,  amount,  currency, method, date, csv fields, meta info

?>
<table class="ui table small striped fluid">
<thead>
<tr>
<th><?php _e('Username', 'ppv-live-webcams'); ?></th>
<th><?php _e('Payout', 'ppv-live-webcams'); ?></th>
<th><?php _e('Method', 'ppv-live-webcams'); ?></th>
<th><?php _e('Date', 'ppv-live-webcams'); ?></th>
<th><?php _e('CSV Fields', 'ppv-live-webcams'); ?></th>
</tr>
</thead>

<tbody>
<?php
$payments = 0;
$total = 0;

foreach ($rows as $row)
{
	$user = get_user_by('id', $row->uid);
	?>
	<tr>
	<td><?php echo esc_html($user->user_login); ?></td>
	<td><?php echo esc_html($row->amount); ?></td>
	<td><?php echo esc_html($row->method); ?></td>
	<td><?php echo esc_html(date('F j, Y, g:i a', $row->ptime)); ?></td>
	<td><small><?php echo esc_html($row->csv); ?></small></td>
	</tr>
	<?php
	if ($save) $csvList .= $row->csv . "\n";
	$payments++;
	$total += $row->amount;
}

echo '<tr><td colspan="5">Total payout: ' . esc_html($total) . ' in ' . esc_html($payments) . ' payments, using ' . esc_html($payoutMethod ? $payoutMethod : 'all methods') . ', between ' . esc_html($payoutStartDate) . ' and ' . esc_html($payoutEndDate) . '.</td></tr>';

?>
</tbody>
</table>
<?php

if ($perPage >= 1) 
{
//display pagination
//get total payouts for selected payout method, between selected payout start date and payout end date
$total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_payouts WHERE method = %s AND ptime >= %d AND ptime <= %d", $payoutMethod, $startTime, $endTime ) );

//display pagination
$pages = ceil($total / $perPage);
	if ($pages > 1)
	{
		?>
		<div class="ui pagination menu">
		<?php
		for ($i = 1; $i <= $pages; $i++)
		{
			?>
			<a class="item <?php echo $i == $page ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'paged' => intval( $i ) ) ) ); ?>"><?php echo intval( $i ); ?></a>
			<?php
		}
		?>
		</div>
		<?php
	}
}

//report files
$dir = sanitize_text_field( $options['uploadsPath'] );
if ( ! file_exists( $dir ) ) {
	mkdir( $dir );
}

$dir .= '/_reports';
if ( ! file_exists( $dir ) ) {
	mkdir( $dir );
}

//save new report file
if ($save && $csvList)
	{
		$payoutFile = $dir . '/' . sanitize_file_name( $payoutStartDate . '_' . $payoutEndDate . '_' . ( $payoutMethod ? $payoutMethod : 'any' ) . '_' . ($perPage<0? 'all' : ($perPage .'_' . $page ) ) ) . '.csv';

		//save $csvList to $payoutFile
		file_put_contents($payoutFile, $csvList);

		echo '<h4>New Report File Saved</h4>';
		echo '<a target="_blank" href="' . esc_url( self::path2URL($payoutFile) ) . '">' . esc_html( self::path2URL($payoutFile) ) . '</a>';
	}

echo '<h4>Recent Report Files</h4><small>';

//list links to 10 most recent report files (.csv) from $dir
$files = scandir($dir, SCANDIR_SORT_NONE);
$files = array_diff($files, array('.', '..'));
if (count($files)) 
{
	$files = array_slice($files, -10);
	foreach ($files as $file)
	{
		//display file date
		echo ' - ' . date( 'F j, Y, g:i a', filemtime($dir . '/' . $file) ) . ': ';
		echo '<a target="_blank" href="' . esc_url( self::path2URL($dir . '/' . $file) ) . '">' . esc_html( self::path2URL($dir . '/' . $file) ) . '</a><br>';
	}
} else echo 'No report files found.';

echo '</small><br><p>Find all report files in <code>' . esc_html( $dir ) . '</code></p>';
?>

<h5>How to Generate Payout Report</h5>
1) Configure report CSV fields from <a href="admin.php?page=live-webcams&tab=payouts">Payout Settings</a><br>
2) Select payout method, start date, end date, per page and click List<br>
3) Click Save to save report as CSV file<br>

<h5>Clarifications</h5>
Files for reports and payouts are diferent: a payout file is created when Processing payouts and a report file is created with Save report. Process payouts from <a href="admin.php?page=live-webcams-payouts">Payouts Tool</a> and then use this tool to generate reports.
<?php
	
}

static function adminPayouts()
{
	$options = self::getOptions();
?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>Payouts / PaidVideochat / VideoWhisper</h2>
</div>
<?php

if ( isset( $_POST ) && ! empty($_POST))
{
	$nonce = $_REQUEST['_wpnonce'];
	if ( ! wp_verify_nonce( $nonce, 'vwsec' ) )
	{
		echo 'Invalid WP nonce!';
		exit;
	}
}

global $wpdb;
$table_payouts = $wpdb->prefix . 'vw_vmls_payouts';

// settings
$payoutBalanceMeta = $options['payoutBalanceMeta'] ?? 'auto';
if ($payoutBalanceMeta == 'auto') if ( $options['wallet'] == 'micropayments') $payoutBalanceMeta = 'micropayments_balance'; else $payoutBalanceMeta = 'vw_ppv_balance';

$payoutMethodField = trim($options['payoutMethodField'] ?? 'Payout Method');
$payoutMethodFieldName = 'vwf_' .  sanitize_title($payoutMethodField);

$payoutMinimum = floatval($options['payoutMinimum'] ?? 300) ? floatval($options['payoutMinimum'] ?? 300 ) : 0;

$perPage = intval($options['payoutPerPage'] ?? 10) ? intval($options['payoutPerPage'] ?? 10) : 10;
$page = intval($_GET['paged'] ?? 1) ? intval($_GET['paged'] ?? 1) : 1;

$payoutMethod = sanitize_text_field($_POST['payoutMethod'] ?? '');

$payoutExchange = floatval($options['payoutExchange'] ?? 1) ? floatval($options['payoutExchange'] ?? 1) : 1;
$payoutCurrency = $options['payoutCurrency'] ?? 'USD';
$payoutMaximum = floatval($options['payoutMaximum'] ?? 0) ? floatval($options['payoutMaximum'] ?? 0) : 0;

$task = 'list';
if ( $_POST['generate'] ?? false ) $task = 'generate';
if ( $_POST['process'] ?? false ) $task = 'process';
$payoutNote = sanitize_text_field($_POST['payoutNote'] ?? '');

//get all users with balance meta greater than $options['payoutMinimum'], sorted by balance, $options['payoutPerPage'] per page, pagination
$args = array(
	'meta_query' => array(
		'relation' => 'AND',
		'minimum'  => array(
			'key'     => $payoutBalanceMeta,
			'value'   => $payoutMinimum,
			'type'	  => 'NUMERIC',
			'compare' => '>=',
		),
	),
	'meta_key' => $payoutBalanceMeta,
	'orderby' => 'meta_value_num',
	'order' => 'DESC',
	'number' => $perPage,
	'paged' => $page,
);

// and also filter by $payoutMethod if defined
if ($payoutMethod) {
	$args['meta_query']['method'] = array(
		'key'     => $payoutMethodFieldName,
		'value'   => $payoutMethod,
		'compare' => '=',
	);
}

//payouts are for performers & studios
$payoutRoles =  self::getRolesPerformer();
$payoutRoles[] = $options['roleStudio']; 
//remove 'administrator', super-admin value from $payoutRoles
$payoutRoles = array_diff($payoutRoles, array('administrator', 'super-admin'));

if ($options['payoutRoles']) 
{
	$extraRoles = explode(',', $options['payoutRoles']);
	foreach ($extraRoles as $role) $payoutRoles[] = trim($role);
}
$args['role__in'] = $payoutRoles;

$users = get_users( $args );

//display users in a WP admin table with columns for select(checkbox), user login, balance, actions (view records), pagination
?>
<form action="<?php echo wp_nonce_url( 'admin.php?page=live-webcams-payouts', 'vwsec' ); ?>" method="post">
<?php

$payoutMethodParams = array();
$csvDefinition = array();

$payoutMethods = $options['payoutMethods'] ?? array();
if (!is_array($payoutMethods)) $payoutMethods = array();
if (count($payoutMethods))
{
	//display a dropdown to select payout method
	?>
	<select name="payoutMethod">
	<option value="">All</option>
	<?php
	foreach ($payoutMethods as $method => $params)
	{
		echo '<option value="' . esc_attr($method) . '" '. ( $payoutMethod == $method ? 'selected' : '' ) . ' >' . esc_html($method) . '</option>';

		if ($method == $payoutMethod) $payoutMethodParams = $params;
	}
}

$csvDefinition = '';
if (array_key_exists('csv', $payoutMethodParams))
{
	$csvDefinition = explode(',', $payoutMethodParams['csv']);
	foreach ($csvDefinition as $key => $value) $csvDefinition[$key] = trim($value);
}

$reportDefinition = explode(',', $options['reportCSV']);
foreach ($reportDefinition as $key => $value) $reportDefinition[$key] = trim($value);


?>
</select>
<input name="method" class="button" type="submit" value="Select Payout Method">
<?php
wp_nonce_field( 'vw_payouts', 'vw_payouts_nonce' );
?>
<table class="wp-list-table widefat fixed striped">
<thead>
<tr>
<th scope="col" id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></th>
<th scope="col" id="user_login" class="manage-column column-user_login column-primary">User</th>
<th scope="col" id="balance" class="manage-column column-balance">Balance</th>
<th scope="col" id="info" class="manage-column column-balance">Payout</th>

<th scope="col" id="actions" class="manage-column column-actions">Actions</th>
</tr>
</thead>

<tbody id="the-list">
<?php
$selectedAmount = 0;
$selectedCount = 0;
$csvList = '';

//get user ids from users[]
$userIDs = array();
if (is_array($_POST['users'] ?? false)) foreach ($_POST['users'] as $userID) $userIDs[] = intval($userID);
else $userIDs = false;

foreach ( $users as $user )
{
	$userWarnings = '';

	$balance = self::balance( $user->ID, false, $options );
	$amount = $balance; //try to pay all

	if ($amount < $payoutMinimum) 
	{
		$amount = 0;
		$userWarnings .= 'Balance below minimum. ';
	}

	if ($payoutMaximum) if ( $amount > $payoutMaximum ) 
	{
		$amount = $payoutMaximum;
		$userWarnings .= 'Maximum payout limit. ';
	}

	//exchange rate
	$payout = floor( $amount * $payoutExchange );
	$amount = ceil( $payout / $payoutExchange );

	$csvRow = '';
	$csvRowR = '';

	$emptyField = '';
	$emptyReportField = '';

	if ($userIDs && in_array($user->ID, $userIDs)) 
	{	
		if (in_array($task, ['generate', 'process']))
		{
			if (count($csvDefinition)) foreach ($csvDefinition as $field) 
			{		
				switch ($field)
				{
					case '#amount':
						$csvRow .= $payout;
						break;
					case '#currency':
						$csvRow .= $payoutCurrency;
						break;
					case '#login':
						$csvRow .= $user->user_login;
						break;
					case '#email':
						$csvRow .= $user->user_email;
						break;
					case '#reference':
						$csvRow .= ( $payoutNote ? $payoutNote . ' ' : '' ) . $user->user_login;
						break;
					default: 
						$fieldName = 'vwf_' .  sanitize_title($field);
						$fieldValue = get_user_meta( $user->ID, $fieldName, true );
						$csvRow .= $fieldValue;
						if (!$fieldValue) $emptyField .=  $field . ' ';
				}
				$csvRow .= ',';
			}
			//remove last , 
			$csvRow = substr($csvRow, 0, -1);

			if (count($reportDefinition)) foreach ($reportDefinition as $field) 
			{		
				switch ($field)
				{
					case '#method':
						$csvRowR .= $payoutMethod;
						break;
					case '#amount':
						$csvRowR .= $payout;
						break;
					case '#currency':
						$csvRowR .= $payoutCurrency;
						break;
					case '#login':
						$csvRowR .= $user->user_login;
						break;
					case '#email':
						$csvRowR .= $user->user_email;
						break;
					case '#reference':
						$csvRowR .= ( $payoutNote ? $payoutNote . ' ' : '' ) . $user->user_login;
						break;
					default: 
						$fieldName = 'vwf_' .  sanitize_title($field);
						$fieldValue = get_user_meta( $user->ID, $fieldName, true );
						$csvRowR .= $fieldValue;
						if (!$fieldValue) $emptyReportField .=  $field . ' ';
				}
				$csvRowR .= ',';
			}
			//remove last , 
			$csvRowR = substr($csvRowR, 0, -1);

			if (!$emptyField)
			{
				$selectedAmount += $payout;
				$selectedCount++;
				$csvList .= $csvRow . "\n";

				if ($task == 'process')
				{

				//process local transfer
				self::transaction( 'payout', $user->ID, -$amount, $payoutMethod . ' Payout initiated: ' . $payout . $payoutCurrency . ( $payoutNote ? ' ' . $payoutNote  : '' ) , null, $csvRow, $options );

				//save in $table_payouts : uid, amount, method, csv, meta, ptime
				$meta = array();
				$meta['login'] = $user->user_login;
				$meta['wallet'] = $options['wallet'];
				$meta['balance'] = $balance;
				$meta['amount'] = $amount;
				$meta['exchange'] = $payoutExchange;
				$meta['currency'] = $payoutCurrency;
				$meta['payoutCSV'] = $csvRow;
				$meta['payoutNote'] = $payoutNote;

				$current_user = wp_get_current_user();
				$meta['admin'] = $current_user->user_login;
				$meta['adminId'] = $current_user->ID;

				$metaJ = json_encode($meta);

				$wpdb->insert(
					$table_payouts,
					array(
						'uid' => $user->ID,
						'amount' => $payout,
						'method' => $payoutMethod,
						'csv' => $csvRowR,
						'meta' => $metaJ,
						'ptime' => time(),
					),
					array(
						'%d',
						'%d',
						'%s',
						'%s',
						'%s',
						'%d',
					)
				);

				//display error if any
				if ($wpdb->last_error) echo '<div class="error">' . esc_html( $wpdb->last_error ) . '</div>';				
				}
			}
		}
	}

	if ($emptyField) $userWarnings .= 'Empty fields: ' . $emptyField;
	if ($emptyReportField) $userWarnings .= 'Empty report fields: ' . $emptyReportField;

	?>
	<tr id="user-<?php echo esc_attr($user->ID); ?>" class="iedit author-self level-0 type-post status-publish format-standard hentry category-uncategorized">
	<th scope="row" class="check-column"><input id="cb-select-<?php echo esc_attr($user->ID); ?>" type="checkbox" name="users[]" value="<?php echo esc_attr($user->ID); ?>" <?php echo esc_attr($payoutMethod ? ($userIDs && in_array($user->ID, $userIDs) ? 'checked' : '') : 'checked'); ?>></th>
	<td class="title column-title has-row-actions column-primary page-title" data-colname="User"><strong><a class="row-title" href="user-edit.php?user_id=<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->user_login); ?></a></strong></td>
	<td class="title column-title has-row-actions column-primary page-title" data-colname="Balance"><strong><?php echo esc_html( $balance ); ?></strong></td>
	<td class="" data-colname="Info">
	<?php 
	$method = get_user_meta( $user->ID, $payoutMethodFieldName, true ); 
	echo esc_html($method) . ': ' . esc_html($payout) . ' ' . esc_html($payoutCurrency); 
	if ($csvRow) echo '<small><br>' . esc_html($csvRow) . '</small>';
	if ($userWarnings) echo '<br><small>Warnings: ' . esc_html($userWarnings) . '</small>';
	?>
	</td>
	<td class="title column-title has-row-actions column-primary page-title" data-colname="Actions"><a href="admin.php?page=live-webcams-records&user_id=<?php echo esc_attr($user->ID); ?>">Records</a>
	<?php
		switch ($options['wallet'])
		{
			case 'MicroPayments':
				echo ' | <a href="admin.php?page=micropayments-transactions&user_id=' . esc_attr($user->ID) . '">Transactions</a>';
			break;

			case 'WooWallet':
				echo ' | <a href="admin.php?page=woo-wallet-transactions&user_id=' . esc_attr($user->ID) . '">Transactions</a>';
			break;

			case 'MyCred':
				echo ' | <a href="admin.php?page=mycred&user=' . esc_attr($user->ID) . '">Transactions</a>';
			break;
		}
	?>
	</td>
	</tr>
	<?php
}
?>
</tbody>

<tfoot>
<tr>
<th scope="col" id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-2" type="checkbox"></th>
<th scope="col" id="user_login" class="manage-column column-user_login column-primary">User</th>
<th scope="col" id="user_login" class="manage-column column-user_login column-primary">Info</th>
<th scope="col" id="balance" class="manage-column column-balance">Balance</th>
<th scope="col" id="actions" class="manage-column column-actions">Actions</th>
</tr>
</tfoot>
</table>
<?php
if (!$payoutMethod) echo '<div class="notice">Select a payout method to generate payout list.</div>';
else 
{
	if ($task == 'list') echo '<div class="notice">Generate list (for preview) or Process (deduct amounts from balance and save CSV list) .</div>';

	if ($selectedAmount) echo '<div class="info">Selected payout amount: ' . esc_html($selectedAmount) . ' ' . esc_html($payoutCurrency) . ' for ' . esc_html($selectedCount) . ' accounts. </div>';
	?>
	<input name="generate" class="button" type="submit" value="Generate Payout List"> | 
	<input name="process" class="button" type="submit" value="Process Payouts"> <input name="note" class="regular-text" type="text" value="<?php echo esc_attr( $payoutNote ) ?>" placeholder="Payout Note">
	<?php
}
?>
</form>
<?php
//display pagination
echo paginate_links( array(
	'base' => add_query_arg( 'paged', '%#%' ),
	'format' => '',
	'prev_text' => __( '&laquo;', 'ppv-live-webcams' ),
	'next_text' => __( '&raquo;', 'ppv-live-webcams' ),
	'total' => ceil( count($users) / $perPage ),
	'current' => intval( $page ) ,
) );	

//payout files dir
$dir = sanitize_text_field( $options['uploadsPath'] );
if ( ! file_exists( $dir ) ) {
	mkdir( $dir );
}
$dir .= '/_payouts';
if ( ! file_exists( $dir ) ) {
	mkdir( $dir );
}

if (in_array($task, ['generate', 'process']))
{
	//display $csvList in textarea readonly
	if ($csvList)
	{

	?>
	<h4>CSV Payout List</h4>
	<textarea readonly cols="100" rows="4"><?php echo esc_html($csvList); ?></textarea>
	<?php

	if ($task == 'process')
	{
		$payoutFile = $dir . '/' . sanitize_file_name( $payoutMethod ) . date('Y-m-d_H-i-s') . '.csv';

		//save $csvList to $payoutFile
		file_put_contents($payoutFile, $csvList);

		echo '<h4>Payout File</h4>';
		echo '<a target="_blank" href="' . esc_url( self::path2URL($payoutFile) ) . '">' . esc_html( self::path2URL($payoutFile) ) . '</a>';
	}

	} else echo '<div class="error">No payout list generated. Select accounts that meet payout criteria and have all CSV fields filled.</div>';

}

echo '<h4>Recent Payout Files</h4><small>';

$files = scandir($dir, SCANDIR_SORT_NONE);
$files = array_diff($files, array('.', '..'));
if (count($files))
{
	$files = array_slice($files, -5);
	foreach ($files as $file)
	{
		//display file date
		echo ' - ' . date( 'F j, Y, g:i a', filemtime($dir . '/' . $file) ) . ': ';
		echo '<a target="_blank" href="' . esc_url( self::path2URL($dir . '/' . $file) ) . '">' . esc_html( self::path2URL($dir . '/' . $file) ) . '</a><br>';
	}
} else echo 'No payout files found.';

echo '</small><p>Find all payout files in <code>' . esc_html( $dir ) . '</code></p>';

echo '<h4> Task: '. esc_html($task) .'</h4><small>';
echo 'Payout roles: ' . esc_html(implode(', ', $payoutRoles)) . '<br>';
echo 'Balance Meta: ' . esc_html($payoutBalanceMeta) . '<br>';
echo 'Payout Method Field: ' . esc_html($payoutMethodField) . '<br>';
echo 'Minimum Balance Amount: ' . esc_html($payoutMinimum) . esc_html($options['currency']) . '<br>';
echo 'Payout Maximum Amount: ' . esc_html($payoutMaximum) . esc_html($options['currency']) . '<br>';
echo 'Exchange Rate: ' . esc_html($payoutExchange) . '<br>';
echo 'Payout Currency: ' . esc_html($payoutCurrency) . '<br>';
echo 'Payouts Per Page: ' . esc_html($perPage) . '<br>';
if ($payoutMethod) 
{
	echo 'Payout Method: ' . esc_html($payoutMethod) . '<br>';
	echo 'CSV Definition: ' . esc_html(json_encode($csvDefinition)) . '<br>';
}

echo 'Current user: ' . esc_html(wp_get_current_user()->user_login) . '<br>';
echo 'Current user balance: ' . esc_html(self::balance(wp_get_current_user()->ID)) . esc_html($options['currency']) . '<br>';
echo 'Configured balance wallet: ' . esc_html($options['wallet']) . '<br>';
echo '</small>';
?>
<h4> How to Use this Payout Tool</h4>
<small>
 1) Configure  <a href="admin.php?page=live-webcams&tab=payouts">Payouts Methods</a> and <a href="admin.php?page=live-webcams&tab=record">Administrative Records</a>
<br> 2) Select a [Payout Method]
<br> 3) Select/Deselect accounts to include in payout list
<br> 4) [Generate Payout List]: this is only a preview that displays list of accounts and CSV
<br> 5) [Process Payouts]: this deducts the token amount from local user balance and saves the CSV info in a file. You need to use the list with the processor to effectively do the payouts.
<br> 6) Review processed payouts anytime from <a href="admin.php?page=live-webcams-payout-report">Payout Reports</a>.

<p>Clarification: This tool only operates with tokens and local wallets and does not operate transactions on billing sites and real money. To pay users, payout lists may be imported in billing sites that include this functionality or transactions can be operated manually.
</small>
</p>

<?php
}

	static function adminRecords()
	{
?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>Records Review and User Approval</h2>
Review administrative records submitted by providers (performers/studios) and approve accounts.
</div>

			<?php
		$options = self::getOptions();

		if ( $user_id = intval( $_GET['user_id'] ?? 0 ) )
		{

			$user = get_userdata( $user_id );
			if ( ! $user )
			{
				echo __( 'User not found: ', 'ppv-live-webcams' ) . esc_html( $user_id );
			} else
			{

				echo '<div><hr />
					<H3>Reviewing User Account: <a href="user-edit.php?user_id=' . esc_attr( $user_id ) . '">' . esc_html( $user->user_login ) . '</a></H3>';

				if ( isset( $_POST['reject'] ) )
				{
					update_user_meta( $user_id, 'vwVerified', 2 ); //rejected
					update_user_meta( $user_id, 'vwVerifiedReason', wp_kses_post( $_POST['reason'] ) ); //rejected
					update_user_meta( $user_id, 'vwVerifiedTime', time() );

					$loginURL = ( $options['p_videowhisper_login'] > 0 ? get_permalink( $options['p_videowhisper_login'] ) : wp_login_url() );
					$mailed = wp_mail( $user->user_email, __('Account Pending', 'ppv-live-webcams') ,  __('Your account could not be approved. Login for details:', 'ppv-live-webcams') . "\r\n" . $loginURL );
					if ( ! $mailed ) echo 'Warning: Notification email was not sent. Check WordPress mailing settings!';

				}

				$verified     = get_user_meta( $user_id, 'vwVerified', true );
				$verifiedTime = get_user_meta( $user_id, 'vwVerifiedTime', true );

				$adminSuspended = get_user_meta( $user_id, 'vwSuspended', true );
				$vwUpdated      = get_user_meta( $user_id, 'vwUpdated', true );

				if ( isset($_GET['verify']) && $_GET['verify'] == '1' )
				{
					$verified = 1;

					update_user_meta( $user_id, 'vwVerified', 1 );
					update_user_meta( $user_id, 'vwVerifiedTime', time() );
					delete_user_meta( $user_id, 'vwVerifiedReason');

					$loginURL = ( $options['p_videowhisper_login'] > 0 ? get_permalink( $options['p_videowhisper_login'] ) : wp_login_url() );
					$mailed = wp_mail( $user->user_email,  __('Account Was Approved', 'ppv-live-webcams'), __('Login to access:', 'ppv-live-webcams') . "\r\n" . $loginURL );
					if ( ! $mailed ) echo 'Warning: Notification email was not sent. Check WordPress mailing settings!';

					self::usersPendingApproval(); //update count
				}

				if ( isset($_GET['suspend']) && $_GET['suspend'] == '1' )
				{
					$adminSuspended = ! $adminSuspended;
					update_user_meta( $user_id, 'adminSuspended', $adminSuspended );
				}

				echo '<H4>' . __( 'Verification Status', 'ppv-live-webcams' ) . '</H4>';
				echo '<B>' . ( $verified == '1' ? __( 'Approved', 'ppv-live-webcams' ) : ( $verified == '2' ? __( 'Rejected', 'ppv-live-webcams' ) :__( 'Not Verified', 'ppv-live-webcams' ) ) ) . '</B> ';
				echo '<br><a class="button secondary" href="admin.php?page=live-webcams-records&verify=1&user_id=' . esc_attr( $user_id ) . '">' . __( 'Approve', 'ppv-live-webcams' ) . '</a> <hr>';

				echo '<div class="message"><form action="admin.php?page=live-webcams-records&user_id=' . intval($user_id). '" method="post">';
				echo '<label>Reject Reason (only if rejecting):<label><BR>';

				wp_editor( get_user_meta( $user_id, 'vwVerifiedReason', true ) , 'reason', $settings = array( 'textarea_rows' => 3, 'media_buttons' => false ) );


				echo '<BR><INPUT class="button primary" TYPE="submit" name="reject" id="reject" value="Reject">';
				echo '</form></div><hr>';


				echo '<BR><B>' . __( 'Suspended', 'ppv-live-webcams' ) . ': ' . ( $adminSuspended ? 'Yes' : 'No' ) . '</B> ';
				echo '<br><a class="button secondary" href="admin.php?page=live-webcams-records&suspend=1&user_id=' . esc_attr( $user_id ) . '">' . __( 'Toggle Suspended', 'ppv-live-webcams' ) . '</a><HR>';



				echo '' . __( 'Records Updated', 'ppv-live-webcams' ) . ': ' . ( $vwUpdated ? date( 'F j, Y, g:i a', $vwUpdated ) : 'Never' ) . '';
				echo '<BR>' . __( 'Last Verified', 'ppv-live-webcams' ) . ': ' . ( $verifiedTime ? date( 'F j, Y, g:i a', $verifiedTime ) : 'Never' ) . '';

				echo '<BR>' . __( 'Registration IP', 'ppv-live-webcams' ) . ': ' . esc_html( get_user_meta( $user_id, 'videowhisper_ip_register', true ) ) . '';
				echo '<BR>' . 'Password Change IP' . ': ' . esc_html( get_user_meta( $user_id, 'videowhisper_ip_newpassword', true ) ) . '';


				echo '<BR><HR>';
				if ( ! is_array( $options['recordFields'] ) )
				{
					echo '<p>No record fields defined by administrator!</p>';
				} else
				{

					$persons = 1;
					if ($options['multiPerson']) 
					{
						$persons = get_user_meta( $user->ID, 'persons', $persons );
						if (!$persons) $persons = 1;


						echo '<h4>Personal Records</b><h4>';

						for ($i = 1; $i <= $persons; $i++) 
						{
							echo '<h4>Person ' . intval( $i ). '</h4>';

							foreach ( $options['recordFields'] as $field => $parameters ) 
							if ( isset($parameters['personal']) && $parameters['personal'] )
							{
								$fieldCode = '<p><h5>' . esc_html( $field ) . '</h5>';

								$fieldName = sanitize_title( trim( $field ) );
								$fieldValue = get_user_meta( $user->ID, 'vwf_' . $fieldName . '_' . $i, true );

								switch ( $parameters['type'] )
								{
								case 'file':
									$filePath = $fieldValue ;
									if ( file_exists( $filePath ) )
									{
										$fieldCode .= '<a target="_download" href="' . esc_url( self::path2url( $filePath ) ). '">' . __( 'Available', 'ppv-live-webcams' ) . '</a> ' . esc_html( self::humanSize( filesize( $filePath ) ) )  . ' ' . esc_html( pathinfo( $filePath, PATHINFO_EXTENSION ) ) . '<br>Path on server: ' . esc_html( $filePath );
									}
									break;

								default:
									$fieldValue = htmlspecialchars( $fieldValue  );
									$fieldCode .= esc_html( $fieldValue );

								}

								if ( $fieldValue || ( isset($parameters['required']) && $parameters['required'] ) )
								{
									echo wp_kses_post( $fieldCode );		
									if ( !$fieldValue ) echo '* Required and missing!';
									echo '</p>';
								}
							}


						}

					}
					
					echo '<h4>Account Records</b><h4>';

					//all common fields
					foreach ( $options['recordFields'] as $field => $parameters )
					{

						$fieldName = sanitize_title( trim( $field ) );
						$fieldValue = get_user_meta( $user->ID, 'vwf_' . $fieldName, true );

						$fieldCode = '<p><h5>' . esc_html( $field ) . '</h5>';

						if ($fieldValue) switch ( $parameters['type'] )
						{
						case 'file':
							$filePath = $fieldValue;
							if ( file_exists( $filePath ) )
							{
								$fieldCode .= '<a target="_download" href="' . esc_url( self::path2url( $filePath ) ) . '">' . __( 'Available', 'ppv-live-webcams' ) . '</a> ' . esc_html( self::humanSize( filesize( $filePath ) ) ) . ' ' . pathinfo( $filePath, PATHINFO_EXTENSION ) . '<br>Path on server: ' . esc_html( $filePath );
							}
							break;

						default:
							$fieldValue = htmlspecialchars( $fieldValue );
							$fieldCode .= esc_html( $fieldValue );
						}

						if ( $fieldValue || ( isset($parameters['required']) && $parameters['required'] ) )
						{
							echo wp_kses_post( $fieldCode );		
							if ( !$fieldValue ) echo ' <b>Required!</b>';
							echo '</p>';
						}
					}
					
					
					
				}

				echo '</div><p>Site admins can approve or reject performers. When rejecting performer, a reason can be provided and will be displayed to user in Performer Dashboard.</p>
					<p>Warning: As user may update (and overwrite) this data, you may want to save it for later reference.</p>
					<a class="button" href="user-edit.php?user_id=' . esc_attr( $user_id ) . '">Edit User</a>
					<hr />';
			}
		} else
		{

			echo '<h3>User Records Pending Review</h3>';

			$users = self::usersPendingApproval();
			
			if ( count( $users ) )
			{
				foreach ( $users as $user )
				{
					echo '<b>' . esc_html( $user->user_login ) . '</b> <a href="admin.php?page=live-webcams-records&user_id=' . esc_attr( $user->ID ) . '">Review</a> | <a href="user-edit.php?user_id=' . esc_attr( $user->ID ) . '">Edit</a><br>';
				}
			} else
			{
				echo 'No records pending review found. Pending list includes only users that updated their records but were never verified.';
			}
		}
?>
	<BR>For more options:
	<BR>+ <a class="button" href="users.php?orderby=vwUpdated&order=desc">Browse Users that Recently Updated Records</a> (All Users)
	<BR>+ <a class="button" href="admin.php?page=live-webcams-records">Browse Users Pending Review</a> (Never Verified Users)
	<BR>+ <a class="button" href="admin.php?page=live-webcams&tab=record">Configure Administrative Fields</a>

<p>Administrative records refers to custom fields defined by administrators that users can fill. These are only accessible by administrators and can be used for identity verification, collecting payout info.
</p>
			<?php
	}


	// admin menus

	static function admin_bar_menu( $wp_admin_bar )
	{
		if ( ! is_user_logged_in() )
		{
			return;
		}

		$options = get_option( 'VWliveWebcamsOptions' );

		if ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) )
		{

			// find VideoWhisper menu
			$nodes = $wp_admin_bar->get_nodes();
			if ( ! $nodes )
			{
				$nodes = array();
			}
			$found = 0;
			foreach ( $nodes as $node )
			{
				if ( $node->title == 'VideoWhisper' )
				{
					$found = 1;
				}
			}
			if ( ! $found )
			{
				$wp_admin_bar->add_node(
					array(
						'id'    => 'videowhisper',
						'title' => '👁 VideoWhisper',
						'href'  => admin_url( 'plugin-install.php?s=videowhisper&tab=search&type=term' ),
					)
				);

				// more VideoWhisper menus

				$wp_admin_bar->add_node(
					array(
						'parent' => 'videowhisper',
						'id'     => 'videowhisper-add',
						'title'  => __( 'Add Plugins', 'ppv-live-webcams' ),
						'href'   => admin_url( 'plugin-install.php?s=videowhisper&tab=search&type=term' ),
					)
				);

				$wp_admin_bar->add_node(
					array(
						'parent' => 'videowhisper',
						'id'     => 'videowhisper-consult',
						'title'  => __( 'Consult Developers', 'ppv-live-webcams' ),
						'href'   => 'https://consult.videowhisper.com/'),
					);

				$wp_admin_bar->add_node(
					array(
						'parent' => 'videowhisper',
						'id'     => 'videowhisper-contact',
						'title'  => __( 'Contact Support', 'ppv-live-webcams' ),
						'href'   => 'https://videowhisper.com/tickets_submit.php?topic=WordPress+Plugins+' . urlencode( $_SERVER['HTTP_HOST'] ),
					)
				);
			}

			$menu_id = 'videowhisper-ppvlivewebcams';

			$usersPending = intval( get_option('VWliveWebcams_Pending') );

			$wp_admin_bar->add_node(
				array(
					'parent' => 'videowhisper',
					'id'     => $menu_id,
					'title'  => $usersPending ? '👁️‍🗨️ PaidVideochat' . ' <span class="ab-label">+' . $usersPending . '</span>' : '👁️‍🗨️ PaidVideochat' ,
					'href'   => admin_url( 'admin.php?page=live-webcams' ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-records',
					'title'  => $usersPending ? __( 'Approve Performers', 'ppv-live-webcams' ) . ' <span class="ab-label">+' . $usersPending . '</span>' : __( 'Approve Users', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams-records' ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-payouts',
					'title'  => __( 'Payouts', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams-payouts' ),
				)
			);
			
			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-payout-report',
					'title'  => __( 'Payout Report', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams-payout-report' ),
				)
			);

			$wp_admin_bar->add_node(
					array(
						'parent' => 'my-account',
						'id'     => $menu_id . '-records-account',
						'title'  => $usersPending ? '✅ ' . __( 'Approve Performers', 'ppv-live-webcams' ) . ' <span class="count alert">' . $usersPending . '</span>' : '✅ ' . __( 'Approve Users', 'ppv-live-webcams' ),
						'href'   => admin_url( 'admin.php?page=live-webcams-records' ),
					)
				);

				$wp_admin_bar->add_node(
					array(
						'parent' => 'my-account',
						'id'     => $menu_id . '-records-payouts',
						'title'  => '💸 ' . __( 'Payouts', 'ppv-live-webcams' ),
						'href'   => admin_url( 'admin.php?page=live-webcams-payouts' ),
					)
				);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-reports',
					'title'  => __( 'Session Reports', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams-reports' ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-sessions',
					'title'  => __( 'Session Logs', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams-sessions' ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-streams',
					'title'  => __( 'Stream Sessions', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams-streams' ),
				)
			);
				$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-posts',
					'title'  => __( 'Webcam Posts', 'ppv-live-webcams' ),
					'href'   => admin_url( 'edit.php?post_type=' . ( $options['custom_post'] ?? 'webcam' ) ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-settings',
					'title'  => __( 'Settings Setup', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams' ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-pages',
					'title'  => __( 'Frontend Pages', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams&tab=pages' ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-bill',
					'title'  => __( 'Billing Setup', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams&tab=billing' ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-apf',
					'title'  => __( 'APF Optimizer', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams-apf' ),
				)
			);	

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-docs',
					'title'  => __( 'Documentation', 'ppv-live-webcams' ),
					'href'   => admin_url( 'admin.php?page=live-webcams-doc' ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-hosting',
					'title'  => __( 'Streaming Hosting', 'ppv-live-webcams' ),
					'href'   => 'https://webrtchost.com/hosting-plans/',
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-turnkey',
					'title'  => __( 'Turnkey Plans', 'ppv-live-webcams' ),
					'href'   => 'https://paidvideochat.com/order/',
				)
			);
			
						$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-wpdiscuss',
						'title'  => __( 'Discuss WP Plugin', 'ppv-live-webcams' ),
						'href'   => 'https://wordpress.org/support/plugin/ppv-live-webcams/',
					)
				);

				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-wpreview',
						'title'  => __( 'Review WP Plugin', 'ppv-live-webcams' ),
						'href'   => 'https://wordpress.org/support/plugin/ppv-live-webcams/reviews/#new-post',
					)
				);

		}

		$current_user = wp_get_current_user();

		if ( $options['p_videowhisper_webcams_performer'] ?? false )
		{
			if ( self::any_in_array( self::getRolesPerformer( $options ), $current_user->roles ) )
			{
				$wp_admin_bar->add_node(
					array(
						'parent' => 'my-account',
						'id'     => 'videowhisper_performer_dashboard',
						'title'  => '📹 ' . __( 'Performer Dashboard', 'ppv-live-webcams' ),
						'href'   => get_permalink( $options['p_videowhisper_webcams_performer'] ),
					)
				);
			}
		}

		if ( $options['p_videowhisper_webcams_client'] ?? false )
		{
			if ( self::any_in_array( array( $options['roleClient'], 'administrator', 'super-admin' ), $current_user->roles ) )
			{
				$wp_admin_bar->add_node(
					array(
						'parent' => 'my-account',
						'id'     => 'videowhisper_performer_client',
						'title'  => '📋 ' . __( 'Client Dashboard', 'ppv-live-webcams' ),
						'href'   => get_permalink( $options['p_videowhisper_webcams_client'] ),
					)
				);
			}
		}

		if ( $options['p_videowhisper_webcams_studio'] ?? false )
		{
			if ( self::any_in_array( array( $options['roleStudio'], 'administrator', 'super-admin' ), $current_user->roles ) )
			{
				$wp_admin_bar->add_node(
					array(
						'parent' => 'my-account',
						'id'     => 'videowhisper_studio_dashboard',
						'title'  => '📋 ' . __( 'Studio Dashboard', 'ppv-live-webcams' ),
						'href'   => get_permalink( $options['p_videowhisper_webcams_studio'] ),
					)
				);
			}
		}
	}


	static function admin_menu()
	{
		$usersPending = intval( get_option('VWliveWebcams_Pending') );

		add_menu_page( 'Live Webcams', $usersPending ? 'Videochat' . '<span class="awaiting-mod">' . $usersPending . '</span>' : 'PaidVideochat', 'manage_options', 'live-webcams', array( 'VWliveWebcams', 'adminOptions' ), 'dashicons-video-alt2', 83 );

		add_submenu_page( 'live-webcams', 'Settings for Live Webcams', 'Settings', 'manage_options', 'live-webcams', array( 'VWliveWebcams', 'adminOptions' ) );
		

		add_submenu_page( 'live-webcams', 'Approve Users', $usersPending ? 'Approve Users' . '<span class="awaiting-mod">' . $usersPending . '</span>' : 'Approve Users', 'promote_users', 'live-webcams-records', array( 'VWliveWebcams', 'adminRecords' ) );
		add_submenu_page( 'live-webcams', 'Payouts', 'Payouts', 'promote_users', 'live-webcams-payouts', array( 'VWliveWebcams', 'adminPayouts' ) );
		add_submenu_page( 'live-webcams', 'Payout Report', 'Payout Report', 'promote_users', 'live-webcams-payout-report', array( 'VWliveWebcams', 'adminPayoutReport' ) );

		add_submenu_page( 'live-webcams', 'Session Reports', 'Session Reports', 'promote_users', 'live-webcams-reports', array( 'VWliveWebcams', 'adminReports' ) );
		
		add_submenu_page( 'live-webcams', 'Session Logs', 'Session Logs', 'list_users', 'live-webcams-sessions', array( 'VWliveWebcams', 'adminSessions' ) );
		add_submenu_page( 'live-webcams', 'Stream Sessions', 'Stream Sessions', 'edit_users', 'live-webcams-streams', array( 'VWliveWebcams', 'adminStreams' ) );
		//add_submenu_page( 'live-webcams', 'RTMP Admin', 'RTMP Admin', 'edit_users', 'live-webcams-admin', array( 'VWliveWebcams', 'adminLive' ) );
		add_submenu_page( 'live-webcams', 'Assign Performers to Studios', 'Studio Assign', 'promote_users', 'live-webcams-studio', array( 'VWliveWebcams', 'adminStudio' ) );
		
		add_submenu_page( 'live-webcams', 'Room Setup', 'Room Setup', 'promote_users', 'live-webcams-setup', array( 'VWliveWebcams', 'adminRoom' ) );

		add_submenu_page( 'live-webcams', 'APF Optimizer', 'APF Optimizer', 'manage_options', 'live-webcams-apf', array( 'VWliveWebcams', 'adminAPF' ) );

		add_submenu_page( 'live-webcams', 'Documentation for Live Webcams', 'Documentation', 'manage_options', 'live-webcams-doc', array( 'VWliveWebcams', 'adminDocs' ) );

		// hide add submenu
		$options = get_option( 'VWliveWebcamsOptions' );
		global $submenu;
		unset( $submenu[ 'edit.php?post_type=' . ( $options['custom_post'] ?? 'webcam' ) ][10] );
	}


	static function wp_nav_menu_items( $itemsCode, $args )
	{
		//custom menus depending on user type
		
		if ( ! is_user_logged_in() )
		{
			return $itemsCode; // default for visitors
		}
		$menu_role = 'member';

		$options = self::getOptions();

		$current_user = wp_get_current_user();
		if ( self::any_in_array( array( $options['roleClient'] ), $current_user->roles ) )
		{
			$menu_role = 'client';
		}
		if ( self::any_in_array( self::getRolesPerformer( $options ), $current_user->roles ) )
		{
			$menu_role = 'performer';
		}
		if ( self::any_in_array( array( $options['roleStudio'] ), $current_user->roles ) )
		{
			$menu_role = 'studio';
		}

		$var = 'menu_' . $menu_role . '_' . sanitize_file_name( $args->theme_location );

		$value = '';
		if ( array_key_exists( $var, $options ) )
		{
			$value = $options[ $var ];
		}

		if ( ! $value )
		{
			return $itemsCode;
		} else
		{
			$menu_args                   = (array) $args;
			$menu_args['menu']           = intval( $value );
			$menu_args['walker']         = $args->walker;
			$menu_args['theme_location'] = '';
			$itemsCode                   = wp_nav_menu( $menu_args );
			// var_dump($menu_args);
		}

		return $itemsCode;
	}

	static function user_edit_form_tag(){
		//enable uploads in profile form
		echo 'enctype="multipart/form-data"';
	}

	static function user_profile_fields( $user ) { 
		
		$options = self::getOptions();

		if ( $options['sms_number'] ?? false )
		{
		?>
		<h3><?php _e("SMS Notifications", 'ppv-live-webcams'); ?> / PPV Live Webcams</h3>
	
		<table class="form-table">
		<tr>
			<th><label for="sms_number"><?php _e("SMS Mobile Number", 'ppv-live-webcams'); ?></label></th>
			<td>
				<input type="text" name="sms_number" id="sms_number" value="<?php echo esc_attr( get_user_meta( $user->ID, 'sms_number', true ) ); ?>" class="regular-text" /><br />
				<span class="description"><?php _e("Mobile number for SMS notifications.", 'ppv-live-webcams') ; echo ' ' . esc_html( $options['sms_instructions'] ?? '' ); ?> </span>
			</td>
		</tr>
		</table>
		<?php
			}

		?>
		<h3><?php _e("Administrative Records", 'ppv-live-webcams'); ?> / PPV Live Webcams</h3>

		<?php
		//administrative records
		$current_user = $user;
		$this_page    = 'user-edit.php?user_id=' . $current_user->ID;

		if ( ! is_array( $options['recordFields'] ) ) {
			echo '<p>' . __( 'No record fields defined by administrator!', 'ppv-live-webcams' ) . '</p>';
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
				}else echo 'Add person nonce error!';

				if ( isset($_GET['person_remove']) && $_GET['person_remove']=='last' ) 
				if (wp_verify_nonce( $_GET['verify'] ?? '', 'person' . $current_user->ID ))
				{		
					$persons--;
					update_user_meta( $current_user->ID, 'persons', $persons );
				}else echo 'Add person nonce error!';

				//insert link to add/remove person
				echo '<div class="ui message">';
				echo  __( 'Persons', 'ppv-live-webcams' ) . ': <span class="ui label"><b>' . esc_html($persons) . '</b></span>';
				echo '<br><a class="ui button tiny" href="' .  wp_nonce_url( add_query_arg( array( 'updateRecords' => 'form', 'person_add' => '1' ), $this_page ) , 'person' . $current_user->ID, 'verify' ). '"><i class="plus icon"></i>' . __( 'Add New Person', 'ppv-live-webcams' ) . '</a>';
				echo '</div>';

				for ($i = 1; $i <= $persons; $i++) 
				{
					echo  '<h4 class="ui dividing header">' . __( 'Personal Records', 'ppv-live-webcams' ) . ' ' . esc_html($i) . '</h4>';

					$type = get_user_meta( $current_user->ID, 'person_' . $i, true );
					
					//display a select to change type
					echo '<div class="field"><label>' . __( 'Type', 'ppv-live-webcams' ) . '</label>';
					echo '<SELECT name="' . 'person_' . esc_attr($i) . '" id="' . 'person_' . esc_attr($i) . '" class="ui dropdown v-select">';
					$personTypes = explode( ',', $options['personTypes'] );
					foreach ($personTypes as $personType)	
						echo '<OPTION value="' . esc_attr( trim($personType) ) . '" ' . ( $type ==  trim($personType) ? 'selected' : '' ) . '>' . esc_html( trim($personType) ) . '</OPTION>';
					echo '</SELECT></div>';

					foreach ( $options['recordFields'] as $field => $parameters ) 
					if ( isset($parameters['personal']) && $parameters['personal'] )
					{

						$fieldName = sanitize_title( trim( $field ) ) . '_' . $i; //per person
	
						$htmlInstructions = '';

						echo '<div class="field"><label for="' . esc_attr($fieldName) . '">' . esc_html($field) . '</label><br>';
		
						if ( isset( $_POST[ $fieldName ] ) ) {
							if ( $current_user->ID == $_POST['profileFor'] ) {
								update_user_meta( $current_user->ID, 'vwf_' . $fieldName, sanitize_text_field( $_POST[ $fieldName ] ) );
							}
						}
		
						$fieldValue = htmlspecialchars( get_user_meta( $current_user->ID, 'vwf_' . $fieldName, true ) );
		
						switch ( $parameters['type'] ) {
							case 'file':
								$filePath  = get_user_meta( $current_user->ID, 'vwf_' . $fieldName, true );
								echo '<INPUT class="ui button" size="72" type="file" name="' . esc_attr($fieldName) . '" id="' . esc_attr($fieldName) . '">';
								if ( file_exists( $filePath ) ) {
									echo '<br><a target="_download" href="' . esc_url(self::path2url( $filePath )) . '">' . __( 'Available', 'ppv-live-webcams' ) . '</a> ' . esc_html(self::humanSize( filesize( $filePath ) )) . ' ' . esc_html(pathinfo( $filePath, PATHINFO_EXTENSION )) . '<br>';
								}
					
								break;
		
							case 'text':
								echo '<INPUT type="text" size="72" name="' . esc_attr($fieldName) . '" id="' . esc_attr($fieldName) . '" value="' . esc_attr($fieldValue) . '">';
								break;
		
							case 'textarea':
								echo '<TEXTAREA type="text" rows="3" cols="70" name="' . esc_attr($fieldName) . '" id="' . esc_attr($fieldName) . '">' . esc_html($fieldValue) . '</TEXTAREA>';
								break;
		
							case 'select':
								echo '<SELECT name="' . esc_attr($fieldName) . '" id="' . esc_attr($fieldName) . '" class="ui dropdown v-select">';
								$fieldOptions = explode( '/', stripslashes( $parameters['options'] ) );
		
								echo '<OPTION value="" ' . ( ! $fieldValue ? 'selected' : '' ) . '> - </OPTION>';
		
								foreach ( $fieldOptions as $fieldOption ) {
									echo '<OPTION value="' . esc_attr($fieldOption) . '" ' . ( $fieldOption == $fieldValue ? 'selected' : '' ) . '>' . esc_html($fieldOption) . '</OPTION>';
								}
		
								echo '</SELECT>';
								break;
						}
		
						if (isset($parameters['instructions'])) echo '<br><small>' . esc_html(stripslashes( $parameters['instructions'] )) . '</small>';
		
						echo '</div>';

					}

				}
				
				echo '<br><a class="ui button tiny" href="' .  wp_nonce_url( add_query_arg( array( 'updateRecords' => 'form', 'person_remove' => 'last' ), $this_page ) , 'person' . $current_user->ID, 'verify' ). '"><i class="minus icon"></i>' . __( 'Remove Last Person', 'ppv-live-webcams' ) . '</a>';
			}

			echo  '<h4 class="ui header dividing">' . __( 'Account Records', 'ppv-live-webcams' ) . '</h4>';

			foreach ( $options['recordFields'] as $field => $parameters ) 
			if ( !$options['multiPerson'] || !isset($parameters['personal']) || !$parameters['personal'] )
			{

				$fieldName = sanitize_title( trim( $field ) );

				$htmlInstructions = '';

				echo '<div class="field"><label for="' . esc_attr($fieldName) . '">' . esc_html($field) . '</label><br>';

				if ( isset( $_POST[ $fieldName ] ) ) {
					if ( $current_user->ID == $_POST['profileFor'] ) {
						update_user_meta( $current_user->ID, 'vwf_' . $fieldName, sanitize_text_field( $_POST[ $fieldName ] ) );
					}
				}

				$fieldValue = htmlspecialchars( get_user_meta( $current_user->ID, 'vwf_' . $fieldName, true ) );

				switch ( $parameters['type'] ) {
					case 'file':
						$filePath  = get_user_meta( $current_user->ID, 'vwf_' . $fieldName, true );
						echo '<INPUT class="ui button" size="72" type="file" name="' . esc_attr($fieldName) . '" id="' . esc_attr($fieldName) . '">';
						if ( file_exists( $filePath ) ) {
							echo '<br><a target="_download" href="' . esc_url(self::path2url( $filePath )) . '">' . __( 'Available', 'ppv-live-webcams' ) . '</a> ' . esc_html(self::humanSize( filesize( $filePath ) )) . ' ' . esc_html(pathinfo( $filePath, PATHINFO_EXTENSION )) . '<br>';
						}


						break;

					case 'text':
						echo '<INPUT type="text" size="72" name="' . esc_attr($fieldName) . '" id="' . esc_attr($fieldName) . '" value="' . esc_attr($fieldValue) . '">';
						break;

					case 'textarea':
						echo '<TEXTAREA type="text" rows="3" cols="70" name="' . esc_attr($fieldName) . '" id="' . esc_attr($fieldName) . '">' . esc_html($fieldValue) . '</TEXTAREA>';
						break;

					case 'select':
						echo '<SELECT name="' . esc_attr($fieldName) . '" id="' . esc_attr($fieldName) . '" class="ui dropdown v-select">';
						$fieldOptions = explode( '/', stripslashes( $parameters['options'] ) );

						echo '<OPTION value="" ' . ( ! $fieldValue ? 'selected' : '' ) . '> - </OPTION>';

						foreach ( $fieldOptions as $fieldOption ) {
							echo '<OPTION value="' . esc_attr($fieldOption) . '" ' . ( $fieldOption == $fieldValue ? 'selected' : '' ) . '>' . esc_html($fieldOption) . '</OPTION>';
						}

						echo '</SELECT>';
						break;
				}

				if (isset($parameters['instructions'])) echo '<br><small>' . esc_html(stripslashes( $parameters['instructions'] )) . '</small>';

				echo '</div>';

			}
		}
		?>
	<?php 
	}

	static function save_user_profile_fields( $user_id ) {
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '' , 'update-user_' . $user_id ) ) {
			return;
		}
		
		if ( !current_user_can( 'edit_user', $user_id ) ) { 
			return false; 
		}
		
		$options = self::getOptions();
		$htmlCode = '';
		$current_user = get_userdata( $user_id);
		
		if ( $options['sms_number'] ?? false ) update_user_meta( $user_id, 'sms_number', sanitize_text_field( $_POST['sms_number'] ?? '') );

		//records update
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

		if ( is_array( $options['recordFields'] ) )
			foreach ( $options['recordFields'] as $field => $parameters ) 
		{

			if ($options['multiPerson']) $piterations = $persons; 
			else $piterations = 1;
			
			for ($pi = 1; $pi <= $piterations; $pi++)
			{

				if ($options['multiPerson'] && isset($parameters['personal']) && $parameters['personal']) $fieldName = sanitize_title( trim( $field ) ) . '_' . $pi; 
				else $fieldName = sanitize_title( trim( $field ) );

				switch ( $parameters['type'] ) 
				{

					case 'file':
						$uploadComplete = false;		

						if ( $filename = sanitize_file_name( $_FILES[ $fieldName ]['tmp_name'] ) ) {
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
								$destination = $options['uploadsPath'] . '/user_' . sanitize_file_name( $current_user->user_nicename ) . '_' . sanitize_file_name( $current_user->ID ) . '/';
								if ( ! file_exists( $destination ) ) {
									mkdir( $destination );
								}

								$newfilename = sanitize_file_name( $fieldName . '_' . time() . '.' . $ext );
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
						break;

					default:
						if ( isset( $_POST[ $fieldName ] ) ) {
							$fieldValue =  sanitize_textarea_field( $_POST[ $fieldName ] );
							update_user_meta( $current_user->ID, 'vwf_' . $fieldName, $fieldValue );
							// $htmlCode .= '<br>' . $field . ': '.  sanitize_text_field($_POST[$fieldName]);
						}
						break;

				}//end $type switch

			}//end iterations
 
		}//end field
	
	}
	
	static function adminRoom() 
	{
						
		$options = self::getOptions();
		$roomid = intval( $_GET['roomid'] ?? 0);
		
		if ($roomid)
		{
			$post = get_post( $roomid );
		} else $post = false;
		
	?>
	<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h3>Room Setup: <?php echo ($post ? ( esc_html( $post->post_title ) ?? 'N/A' ) : 'Select') ?></h3>
	</div>
	<?php
	
		if ($post ?? false)
		{

			//display room owner and link to profile
			$owner = get_userdata( $post->post_author );
			echo '<p>Owner: <a href="' . admin_url( 'user-edit.php?user_id=' . esc_attr( $owner->ID ) ) . '">' . esc_html( $owner->display_name ) . '</a></p>';
			//include link to edit room post
			echo '<a class="button" href="' . esc_url( get_edit_post_link( $roomid ) ) . '">Edit Room Post</a>';
			echo '<a class="button" href="' . get_permalink( $roomid ) . '">View Room Page</a>';

				if ( shortcode_exists( 'videowhisper_postvideo_assign' ) ) 
				{	echo '<h3>Set Teaser</h3>';
					echo do_shortcode( "[videowhisper_postvideo_assign post_id=\"$roomid\" meta=\"video_teaser\" user_id=\"" . intval( $post->post_author ) .  "\" show=\"1\"]" );
					echo '<br>* You can also set a video from own (admin) videos, uploaded with Video Share VOD. Use Select button to Set.<hr>';
				}
				
				
				echo '<h3>Room Settings</h3>';
				$metas = [
					'showImage' => 'Preview',
					'vw_earningRatio' => 'Custom Earning Ratio',
					'vw_featured' => 'Featured Level',
					'vw_costPerMinute' => 'Custom Cost Per Minute in Private',
					'vw_costPerMinuteGroup' => 'Custom Cost Per Minute in Group',
					'vw_subscription_tier' => 'MicroPayments Subscription Tier',
					'vw_roomLabel' => 'Room Label',
					'vw_roomBrief' => 'Room Brief Info',
					'vw_banCountries' => 'Banned Locations',
					'vw_accessList' => 'Access List',
					'question_closed' => 'Questions Closed',
					'question_price' => 'Question Price',
					'sms_number' => 'SMS Mobile Number',
					'vwSuspended' => 'Suspended Time',
				];
				
				
				$metaOptions = [
					'showImage' => [ '', 'auto', 'teaser', 'picture', 'snapshot', 'avatar' ],
					'question_closed' => [ '', 0, 1 ],

				]; 
				

				echo '<form action="admin.php?page=live-webcams-setup&roomid=' . esc_attr( $roomid ) . '" method="post">';
				echo 'Update settings and use Save Settings button below to save.';
				
				foreach ($metas as $meta=>$label)
				{
					if ( isset( $_POST[$meta] ) )
					{
						$value = sanitize_text_field( $_POST[$meta] );
						update_post_meta( $roomid, $meta, $value );
					}
					
					echo '<h4>' . esc_html( $label ) . '</h4>';
					$value = get_post_meta( $roomid, $meta, true );
					
					if ( array_key_exists($meta, $metaOptions) )
					{
						echo '<select name="' . esc_attr($meta) . '" id="' . esc_attr($meta) . '">';
						foreach ( $metaOptions[$meta] as $option) echo '<option value="' . esc_attr($option) . '" ' . ($value == $option ? 'selected' : '') . '>' . esc_attr(ucwords($option)) . '</option>';
						echo '</select>';
					}
					else
					echo '<input size="80" name="' . esc_attr($meta) . '" value="' . esc_attr($value) . '"/>';
				}

				echo '<p><input class="button" type="submit" name="submit" id="submit" value="Save Settings"/></p></form>';

		} else echo 'Select a room to setup settings.';
		
			
			echo '<hr><a class="button" href="edit.php?post_type=' . esc_attr($options['custom_post']) . '">  ⃪ Room List</a>';
	}
	

		static function adminReports() {
				
		$options = self::getOptions();
		$roomid = intval($_GET['roomid'] ?? 0);
		
		if ($roomid)
		{
			$post = get_post( $roomid );
		}
	?>
	<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>Reports : <?php echo ( isset($post) ? esc_html( $post->post_title ): 'All Rooms') ?></h2>
	</div>
	
	<?php
		
	
		echo do_shortcode( '[videowhisper_reports roomid="' . esc_attr($roomid) . '"]' );
	?>
	<p>Want to see reports for specific rooms? Use Reports button from <a href="<?php echo admin_url( 'edit.php?post_type=' . esc_attr($options['custom_post']) ) ?>">Room List</a>.</p>
	<?php
				}
	


}
