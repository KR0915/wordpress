import React from 'react';
import SettingsCard from './SettingsCard';
const { __ } = wp.i18n;
import { langString } from '../../Helpers';

function SettingsContent() {
	return (
		<div className="settings-container">
			<h1 className="settings-title">{ langString('settings') }</h1>
			<div className="settings-cards">
				{/* Card 1 */}
				<SettingsCard
					title={ langString('jitsi_meet') }
					description={ langString('jitsi_meet_config_hint') }
					settingsType="jitsi"
				/>

				{/* Card 2 */}
				<SettingsCard
					title={ langString('zoom') }
					description={ langString('zoom_config_hint') }
					settingsType="zoom"
				/>


				{/* Card 3 */}
				<SettingsCard
					title={ langString('webex') }
					description={ langString('webex_config_hint') }
					settingsType="webex"
				/>

				{/* Card 3 */}
				<SettingsCard
					title={ langString('google_meet')}
					description={ langString('google_meet_config_hint') }
					settingsType="google_meet"
				/>

			</div>
		</div>
	);
}

export default SettingsContent;
