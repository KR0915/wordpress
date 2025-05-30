import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Spinner from '../common/Spinner';
import JitsiEditForm from './form/jitsi/JitsiEditForm';
import ZoomEditForm from './form/zoom/ZoomEditForm';
import WebexEditForm from './form/webex/WebexEditForm';
import GoogleMeeEditForm from './form/googlemeet/GoogleMeeEditForm';
const { __ } = wp.i18n;
import { langString } from '../../Helpers';

const EditMeeting = () => {
	const { id, selectedPlatform } = useParams();
	const navigate = useNavigate();
	const [meetingDetails, setMeetingDetails] = useState(null);
	const [loading, setLoading] = useState(true);

	useEffect(
		() => {
			const fetchMeetingDetails = async () => {
				try {
					const response = await wp.apiFetch(
						{
							path: `mhub/v1/meetings/${id}`,
							method: 'GET',
						}
					);

					if (response) {
						setMeetingDetails(response);
					}
				} catch (error) {
					console.error( langString('api_error') , error);
				} finally {
					setLoading(false);
				}
			};
			fetchMeetingDetails();
		}, [id]
	);

	const handleBack = () => {
		navigate('/');
	};

	if (loading) {
		return <Spinner />;
	}

	return (
		<div className="meeting-wrapper">
			<button className="back-btn" onClick={handleBack}>
				<span className="dashicons dashicons-arrow-left-alt"></span>{ langString('back') }
			</button>

			<h2 className="title">{ langString('edit_meeting') }</h2>

			{meetingDetails && (
				<>
					{selectedPlatform === 'jitsi_meet' && (
						<JitsiEditForm meetingId={id} meetingDetails={meetingDetails} />
					)}
					{selectedPlatform === 'zoom' && (
						<ZoomEditForm meetingId={id} meetingDetails={meetingDetails} />
					)}
					{selectedPlatform === 'webex' && (
						<WebexEditForm meetingId={id} meetingDetails={meetingDetails} />
					)}

					{selectedPlatform === 'google_meet' && (
						<GoogleMeeEditForm meetingId={id} meetingDetails={meetingDetails} />
					)}
				</>
			)}
		</div>
	);
};

export default EditMeeting;
