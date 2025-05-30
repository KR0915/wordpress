import React from "react";
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import SettingIcons from './SettingIcons';
const { __ } = wp.i18n;
import { langString } from "../../Helpers";

function SettingsCard({ title, description, isUpcoming, settingsType }) {
	const getIcon = () => {
		switch (settingsType) {
			case 'jitsi':
				return SettingIcons.jitsi;
			case 'zoom':
				return SettingIcons.zoom;
			case 'webex':
				return SettingIcons.webex;
			case 'google_meet':
				return SettingIcons.google_meet;
			default:
				break;
		}
	};

	return (
		<div className="settings-card">
			<div className="card-top">
				<div className="card-left">
					{getIcon()} 
					<h3 className="card-title">{title}</h3>
					<p className="card-description">{description}</p>
				</div>
				
				<div className="card-right">
					{settingsType === 'jitsi' && 'connected' == mhubMeetingsData.jitsi_active_status && <p className="account-status">{ langString('account_connected')  }</p>}
					{ settingsType === 'zoom'  && 'connected' == mhubMeetingsData.oauthData && <p className="account-status">{ langString('account_connected') }</p>}
					{ settingsType === 'webex'  && 'connected' == mhubMeetingsData.webex_auth && <p className="account-status">{ langString('account_connected') }</p>}

					{ settingsType === 'google_meet'  && 'connected' == mhubMeetingsData.google_account && <p className="account-status">{ langString('account_connected') }</p>}
				</div>
			</div>
			<div className="card-bottom">
				{isUpcoming ? (
					<span className="upcoming">{ langString('upcoming') }</span>
				) : (
					<Link to={`/${settingsType}`} className="settings-button">
						{ langString('configure') }
					</Link>
				)}
			</div>
		</div>
	);
}

SettingsCard.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string.isRequired,
	isUpcoming: PropTypes.bool,
	settingsType: PropTypes.string.isRequired,
};

export default SettingsCard;
