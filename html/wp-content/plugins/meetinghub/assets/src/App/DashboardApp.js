import React from "react";
import { Route, Routes, useNavigate } from "react-router-dom";
import CreateMeeting from '../components/dashboard/CreateMeeting';
import MeetingDashboard from '../components/dashboard/MeetingDashboard';
import EditMeeting from '../components/dashboard/EditMeeting';
import '../scss/dashboard/meeting.scss';
import 'react-toastify/dist/ReactToastify.css';
import { ToastContainer, toast } from 'react-toastify';
import GetProModal from "../components/common/GetProModal";
import { MhubAdminProvider } from "./MhubAdminContext";

function DashboardApp() {
	const navigate = useNavigate();
	
	return (
		<div id="meeting_hub">
			 <MhubAdminProvider>
				<Routes>
					<Route path='/' element={<MeetingDashboard />} />
					<Route path='/meeting/create' element={<CreateMeeting />} />
					<Route path='/meeting/edit/:selectedPlatform/:id' element={<EditMeeting />} />
				</Routes>
				<GetProModal />
			</MhubAdminProvider>

		

			<ToastContainer
				position="top-right"
				autoClose={3000}
				hideProgressBar={false}
				newestOnTop={false}
				closeOnClick
				rtl={false}
				pauseOnFocusLoss
				draggable
				pauseOnHover
				theme="light"
				style={{ marginTop: '30px' }}
			/>	
		</div>
	);
}

export default DashboardApp;
