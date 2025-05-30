import { createRoot } from 'react-dom/client';
import { HashRouter } from 'react-router-dom';
import DashboardApp from './App/DashboardApp';
import SettingsApp from './App/SettingsApp';
import ZoomUsersApp from './App/ZoomUsersApp';
import ZoomReportsApp from './App/ZoomReportsApp';
import ZoomRecordingsApp from './App/ZoomRecordingsApp';
import MeetingListApp from './App/MeetingListApp';
import AddonApp from './App/AddonApp';
import IntegrationApp from './App/IntegrationApp';
import { MhubAdminProvider } from './App/MhubAdminContext';
import GetProModal from './components/common/GetProModal';

document.addEventListener(
	'DOMContentLoaded', function () {
		var rootElementDashboard        = document.getElementById('meeting_hub_admin_dasboard');
		var rootElementLmsDashboard     = document.getElementById('meetingHub_lms_dashboard');
		var rootElementDokanDashboard   = document.getElementById('mhub_dokan_dasboard');
		var rootElementWcfmDashboard    = document.getElementById('mhub_wcfm_dasboard');
		var rootMultivendorXDashboard   = document.getElementById('mhub_multivendorX_dasboard');
		const rootElementSettings       = document.getElementById('meeting_hub_admin_settings');
		const rootElementZoomUsers      = document.getElementById('meetinghub_zoom_users');
		const rootElementZoomReports    = document.getElementById('meetinghub_zoom_reports');
		const rootElementZoomRecordings = document.getElementById('meetinghub_zoom_recordings');
		const rootElementAddons         = document.getElementById('meetingHub_addons');
		const rootElementIntegrations   = document.getElementById('meetingHub_integrations');
		const rootFrontendMeeting       = document.getElementById('mhub_frontend_meeting');

		if(rootElementDashboard ) {
			const dashboardRoot = createRoot(rootElementDashboard);
			dashboardRoot.render(<HashRouter> <DashboardApp /></HashRouter>);
		}
		
		if(rootElementDokanDashboard  ) {
			const DokanDashboardRoot = createRoot(rootElementDokanDashboard );
			if( mhubMeetingsData.active ){
				DokanDashboardRoot.render(<HashRouter> <DashboardApp /></HashRouter>);
			}
		}

		if(rootElementWcfmDashboard  ) {
			const WcfmDashboardRoot = createRoot(rootElementWcfmDashboard );
			if( mhubMeetingsData.active ){
				WcfmDashboardRoot.render(<HashRouter> <DashboardApp /></HashRouter>);
			}
		}

		if(rootMultivendorXDashboard  ) {
			const MultivendorXDashboard = createRoot(rootMultivendorXDashboard );
			if( mhubMeetingsData.active ){
				MultivendorXDashboard.render(<HashRouter> <DashboardApp /></HashRouter>);
			}
		}

		if (rootFrontendMeeting) {
			const frontendMeetinRoot = createRoot(rootFrontendMeeting);
			frontendMeetinRoot.render(<HashRouter> <DashboardApp /></HashRouter>);
		}

		if(rootElementLmsDashboard ) {
			const lmsDashboardRoot = createRoot(rootElementLmsDashboard);
			if( mhubMeetingsData.active ){
				lmsDashboardRoot.render(<HashRouter> <DashboardApp /></HashRouter>);
			}
		}

		if (rootElementSettings) {
			const settingsRoot = createRoot(rootElementSettings);
			settingsRoot.render(<HashRouter><SettingsApp /></HashRouter>);
		}

		if (rootElementZoomUsers) {
			const usersRoot = createRoot(rootElementZoomUsers);
			usersRoot.render(<HashRouter><ZoomUsersApp /></HashRouter>);
		}

		if (rootElementZoomReports) {
			const reportsRoot = createRoot(rootElementZoomReports);
			reportsRoot.render(<HashRouter><ZoomReportsApp /></HashRouter>);
		}

		if (rootElementZoomRecordings) {
			const reportsRoot = createRoot(rootElementZoomRecordings);
			reportsRoot.render(<HashRouter><ZoomRecordingsApp /></HashRouter>);
		}

		if (rootElementAddons) {
			const addonsRoot = createRoot(rootElementAddons);
			addonsRoot.render(
				<HashRouter>
					<MhubAdminProvider>
						<AddonApp />
						<GetProModal />
					</MhubAdminProvider>
				</HashRouter>
			);
		}

		if (rootElementIntegrations) {
			const integrationsRoot = createRoot(rootElementIntegrations);
			integrationsRoot.render(
				<HashRouter>
					<MhubAdminProvider>
						<IntegrationApp />
						<GetProModal />
					</MhubAdminProvider>
				</HashRouter>
			);
		}
	
	} 
);

