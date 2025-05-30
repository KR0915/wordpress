var mhub_jitsi_settings =  mhub_frontend.mhub_jitsi_settings;

const initFreeAPI = () => {
  let api;
  const meetingContainer = jQuery('#meetinghub_meeting');

  if (meetingContainer.length) {
    var domain                   = meetingContainer.data('random-domain');
    const roomName               = meetingContainer.data('random-room-name');
    const currentUserName        = meetingContainer.data('user-name');
    const width                  = meetingContainer.data('width');
    const height                 = meetingContainer.data('height');
    const start_with_audio_muted = meetingContainer.data('start-with-audio-muted');
    const start_with_video_muted = meetingContainer.data('start-with-video-muted');
    const screen_sharing         = meetingContainer.data('screen-sharing');
    const enable_inviting        = meetingContainer.data('enable-inviting');
    const mhub_jitsi_elementor   = meetingContainer.data('elementor');

    // Define toolbar buttons
    let toolbarButtons = [
      'camera',
      'chat',
      'closedcaptions',
      'download',
      'embedmeeting',
      'etherpad',
      'feedback',
      'filmstrip',
      'fullscreen',
      'hangup',
      'help',
      'livestreaming',
      'microphone',
      'mute-everyone',
      'mute-video-everyone',
      'participants-pane',
      'profile',
      'raisehand',
      'recording',
      'security',
      'select-background',
      'settings',
      'shareaudio',
      'sharedvideo',
      'shortcuts',
      'stats',
      'tileview',
      'toggle-camera',
      'videoquality',
      '__end',
    ];

    if (enable_inviting) {
      toolbarButtons.push('invite');
    }

    if (screen_sharing) {
      toolbarButtons.push('desktop');
    }

    const options = {
      roomName: roomName,
      width: width ? width : 1080,
      height: height ? height : '700',
      parentNode: meetingContainer[0],
      userInfo: {
        displayName: currentUserName ? currentUserName : '',
      },
      configOverwrite: {
        defaultLanguage: 'en',
        startWithAudioMuted: start_with_audio_muted ? true : false,
        startWithVideoMuted: start_with_video_muted ? true : false,
        toolbarButtons: toolbarButtons,
      },
      interfaceConfigOverwrite: {
        SHOW_CHROME_EXTENSION_BANNER: false,
        SHOW_PROMOTIONAL_CLOSE_PAGE: false,
      },
    };

    // Create JitsiMeetExternalAPI only if the meeting container is found
    api = new JitsiMeetExternalAPI(domain, options);
  } else {
    console.warn('Meeting container not found. API initialization skipped.');
  }
};

const initJassAPI = () => {
  let api;
  const meetingContainer = jQuery('#meetinghub_meeting');

  if (meetingContainer.length) {
    const domain                 = '8x8.vc';
    const roomName               = meetingContainer.data('random-room-name');
    const currentUserName        = meetingContainer.data('user-name');
    const width                  = meetingContainer.data('width');
    const height                 = meetingContainer.data('height');
    const start_with_audio_muted = meetingContainer.data('start-with-audio-muted');
    const start_with_video_muted = meetingContainer.data('start-with-video-muted');
    const screen_sharing         = meetingContainer.data('screen-sharing');
    const enable_inviting        = meetingContainer.data('enable-inviting');
    
    // Define toolbar buttons
    let toolbarButtons = [
      'camera',
      'chat',
      'closedcaptions',
      'download',
      'embedmeeting',
      'etherpad',
      'feedback',
      'filmstrip',
      'fullscreen',
      'hangup',
      'help',
      'livestreaming',
      'microphone',
      'mute-everyone',
      'mute-video-everyone',
      'participants-pane',
      'profile',
      'raisehand',
      'recording',
      'security',
      'select-background',
      'settings',
      'shareaudio',
      'sharedvideo',
      'shortcuts',
      'stats',
      'tileview',
      'toggle-camera',
      'videoquality',
      '__end',
    ];

    if (enable_inviting) {
      toolbarButtons.push('invite');
    }

    if (screen_sharing) {
      toolbarButtons.push('desktop');
    }

    const options = {
      roomName: mhub_jitsi_settings.app_id + '/' + roomName,
      width: width ? width : 1080,
      height: height ? height : '700',
      parentNode: meetingContainer[0],
      jwt: mhub_frontend.jwt,
      userInfo: {
        displayName: currentUserName ? currentUserName : '',
      },
      configOverwrite: {
        defaultLanguage: 'en',
        startWithAudioMuted: start_with_audio_muted ? true : false,
        startWithVideoMuted: start_with_video_muted ? true : false,
        toolbarButtons: toolbarButtons,
      },
      interfaceConfigOverwrite: {
        SHOW_CHROME_EXTENSION_BANNER: false,
        SHOW_PROMOTIONAL_CLOSE_PAGE: false,
      },
      devices: {
        audioOutput: 'default', // Change this to the desired output device label
      },
    };

    // Create JitsiMeetExternalAPI only if the meeting container is found
    api = new JitsiMeetExternalAPI(domain, options);
  } else {
    console.warn('Meeting container not found. API initialization skipped.');
  }
};

const initCustomAPI = () => {
  let api;
  const meetingContainer = jQuery('#meetinghub_meeting');

  if (meetingContainer.length) {
    const domain                 = mhub_jitsi_settings.custom_domain;
    const roomName               = meetingContainer.data('random-room-name');
    const currentUserName        = meetingContainer.data('user-name');
    const width                  = meetingContainer.data('width');
    const height                 = meetingContainer.data('height');
    const start_with_audio_muted = meetingContainer.data('start-with-audio-muted');
    const start_with_video_muted = meetingContainer.data('start-with-video-muted');
    const screen_sharing         = meetingContainer.data('screen-sharing');
    const enable_inviting        = meetingContainer.data('enable-inviting');

    // Define toolbar buttons
    let toolbarButtons = [
      'camera',
      'chat',
      'closedcaptions',
      'download',
      'embedmeeting',
      'etherpad',
      'feedback',
      'filmstrip',
      'fullscreen',
      'hangup',
      'help',
      'livestreaming',
      'microphone',
      'mute-everyone',
      'mute-video-everyone',
      'participants-pane',
      'profile',
      'raisehand',
      'recording',
      'security',
      'select-background',
      'settings',
      'shareaudio',
      'sharedvideo',
      'shortcuts',
      'stats',
      'tileview',
      'toggle-camera',
      'videoquality',
      '__end',
    ];

    if (enable_inviting) {
      toolbarButtons.push('invite');
    }

    if (screen_sharing) {
      toolbarButtons.push('desktop');
    }

    const options = {
      roomName: roomName,
      width: width ? width : 1080,
      height: height ? height : '700',
      parentNode: meetingContainer[0],
      userInfo: {
        displayName: currentUserName ? currentUserName : '',
      },
      configOverwrite: {
        defaultLanguage: 'en',
        startWithAudioMuted: start_with_audio_muted ? true : false,
        startWithVideoMuted: start_with_video_muted ? true : false,
        toolbarButtons: toolbarButtons,
      },
      interfaceConfigOverwrite: {
        SHOW_CHROME_EXTENSION_BANNER: false,
        SHOW_PROMOTIONAL_CLOSE_PAGE: false,
      },
    };

    // Create JitsiMeetExternalAPI only if the meeting container is found
    api = new JitsiMeetExternalAPI(domain, options);
  } else {
    console.warn('Meeting container not found. API initialization skipped.');
  }
};



// Check if the DOM has fully loaded
jQuery(document).ready(() => {
  if (jQuery('#meetinghub_meeting').length) {
     if( 'jitsi_random_public' === mhub_jitsi_settings.domain_type ) {
      initFreeAPI();
     } else if( 'jitsi_jass_premium' === mhub_jitsi_settings.domain_type ) {
        if (mhub_frontend.jwt) {
          initJassAPI();
        } else {
          initFreeAPI();
        }
     } else if ( 'jitsi_self_hosted' === mhub_jitsi_settings.domain_type ){
       initCustomAPI();
     }else{
      initFreeAPI();
     }
   
  }
});
