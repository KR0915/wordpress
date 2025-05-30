import React, { useEffect, useState } from "react";
import { useNavigate } from 'react-router-dom';
import '../../scss/dashboard/_common_form_style.scss';
import JitsiForm from './form/jitsi/JitsiForm';
import ZoomForm from './form/zoom/ZoomForm';
import WebexForm from "./form/webex/WebexForm";
import GoogleMeetForm from "./form/googlemeet/GoogleMeetForm";
const { __ } = wp.i18n;
import { langString } from "../../Helpers";

export default function CreateMeeting() {
	const navigate = useNavigate();
	const [selectedPlatform, setSelectedPlatform] = useState('');

	const handleBack = () => {
		navigate('/');
	};

	const handlePlatformChange = (e) => {
		setSelectedPlatform(e.target.value);
	};


	return (
		<div className="meeting-wrapper">
			<button className='back-btn' onClick={handleBack}><span className="dashicons dashicons-arrow-left-alt"></span>{   langString('back') }</button>
			
			<h2 className='title'>{ langString('add_new_meeting') }</h2>

			<div className='meeting-platform-wrapper'>
				<label>{ langString('select_meeting_app') }</label>
				<select className='choice-meeting-platform' value={selectedPlatform} onChange={handlePlatformChange}>
					<option value="">--{ langString('select') }--</option>
					<option value="jitsi_meet">{ langString('jitsi_meet') }</option>
					{(mhubMeetingsData.is_admin || (!mhubMeetingsData.is_admin && mhubMeetingsData.oauthData === 'connected')) && (
						<option value="zoom">{ langString('zoom') }</option>
					)}

					{(mhubMeetingsData.is_admin || (!mhubMeetingsData.is_admin && mhubMeetingsData.google_account === 'connected')) && (
						<option value="google_meet">{ langString('google_meet') }</option>
					)}

					{(mhubMeetingsData.is_admin || (!mhubMeetingsData.is_admin && mhubMeetingsData.webex_auth === 'connected')) && (
						<option value="webex">{ langString('webex') }</option>
					)}
				</select>
			</div>


			{selectedPlatform === 'jitsi_meet' && <JitsiForm selectedPlatform={selectedPlatform} />}
			{selectedPlatform === 'zoom' && <ZoomForm selectedPlatform={selectedPlatform} />}
			{selectedPlatform === 'webex' && <WebexForm selectedPlatform={selectedPlatform} />}
			{selectedPlatform === 'google_meet' && <GoogleMeetForm selectedPlatform={selectedPlatform} />}
		</div>
	);
}
