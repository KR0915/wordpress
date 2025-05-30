import { createRoot } from 'react-dom/client';
import { HashRouter } from 'react-router-dom';

import MeetingListApp from './App/MeetingListApp';

document.addEventListener(
	'DOMContentLoaded', function () {
		const rootWebexMeetingList = document.getElementById('mhub_webex_meeting_list');
		const rootJitsiMeetingList = document.getElementById('mhub_jitsi_meeting_list');
		const rootZoomMeetingList = document.getElementById('mhub_zoom_meeting_list');
		const rootZoomWebinarList = document.getElementById('mhub_zoom_webinar_list');
		const rootGoogleMeetingList = document.getElementById('mhub_google_meet_meeting_list');
		
		if (rootWebexMeetingList) {
			const webexMeetingListRoot = createRoot(rootWebexMeetingList);
			webexMeetingListRoot.render(<HashRouter><MeetingListApp platform="webex" /></HashRouter>);
		}

		if (rootGoogleMeetingList) {
			const googleMeetingListRoot = createRoot(rootGoogleMeetingList);
			googleMeetingListRoot.render(<HashRouter><MeetingListApp platform="google_meet" /></HashRouter>);
		}

		if (rootJitsiMeetingList) {
			const jitsiMeetingListRoot = createRoot(rootJitsiMeetingList);
			jitsiMeetingListRoot.render(<HashRouter><MeetingListApp platform="jitsi_meet" /></HashRouter>);
		}

		if (rootZoomMeetingList) {
			const zoomMeetingListRoot = createRoot(rootZoomMeetingList);
			zoomMeetingListRoot.render(<HashRouter><MeetingListApp platform="zoom" meeting_type="2" /></HashRouter>);
		}

		if (rootZoomWebinarList) {
			const zoomWebinarListRoot = createRoot(rootZoomWebinarList);
			zoomWebinarListRoot.render(<HashRouter><MeetingListApp platform="zoom" meeting_type="1" /></HashRouter>);
		}
	} 
);

