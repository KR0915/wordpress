import React from 'react';
import { Route, Routes, useNavigate } from "react-router-dom";
import ZoomUsersDashboard from '../components/zoom_users/ZoomUsersDashboard';
import ZuUserForm from '../components/zoom_users/form/ZuUserForm';
import 'react-toastify/dist/ReactToastify.css';
import { ToastContainer, toast } from 'react-toastify';
import { MhubAdminProvider } from './MhubAdminContext';
import GetProModal from "../components/common/GetProModal";

function ZoomUsersApp() {
	const navigate = useNavigate();
	return (
		<div id="meeting_hub">
			<MhubAdminProvider>
				<Routes>
					<Route path='/' element={<ZoomUsersDashboard />} />
					<Route path='/user/create' element={<ZuUserForm />} />
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

export default ZoomUsersApp;
