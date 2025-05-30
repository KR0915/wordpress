import React from 'react';
import Header from './Header';
import MeetingList from './MeetingList';

function MeetingDashboard() {
	return (
		<div className="meeting-dashboard">
			<Header />
			<MeetingList />
		</div>
	);
}

export default MeetingDashboard;
