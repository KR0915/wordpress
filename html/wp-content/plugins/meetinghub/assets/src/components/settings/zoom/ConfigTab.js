import React from "react";
import MhInput from "../../common/fields/MhInput";
import MhSwitcher from "../../common/fields/MhSwitcher";
import TimezoneList from "../../common/fields/TimezoneList";
import MhSelect from "../../common/fields/MhSelect";
import { isProActive } from "../../../Helpers";
const { __ } = wp.i18n;
import { langString } from "../../../Helpers";

const ConfigTab = ({ formData, setFormData }) => {

	const handleChange = (name, value) => {
		setFormData({ ...formData, [name]: value });
	};

	//timezones
	const Timezones = TimezoneList();

	//Auto recording
	const autoRecording = [
		{ value: 'none', label: langString('no_recordings')  },
		{ value: 'local', label: langString('local')  },
		{ value: 'cloud', label: langString('cloud')  },
	];
	
	const { enable_recurring_meeting, meeting_timezone, enable_should_register, disable_waiting_room, meeting_authentication, join_before_host,  option_mute_participants, practice_session, allow_multiple_devices, auto_recording, hide_sidebar, hide_header_footer } = formData;

	return (
		<div>
			<div className="mhub-col-lg-12">
				<MhSwitcher
					label={langString('recurring_meeting') }
					description={langString('enable_recurring') }
					checked={enable_recurring_meeting}
					onChange={(name, value) => handleChange(name, value)}
					name="enable_recurring_meeting"
					disabled={!isProActive()} 
					isLocked={ !isProActive()}
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSelect
					label={langString('timezone')}
					description={langString('meeting_timezone') }
					options={Timezones}
					value={meeting_timezone}
					onChange={(name, value) => handleChange(name, value)}
					name="meeting_timezone"
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSwitcher
					label={langString('should_register') }
					description={langString('should_register_help')}
					checked={enable_should_register}
					onChange={(name, value) => handleChange(name, value)}
					name="enable_should_register"
					disabled={!isProActive()} 
					isLocked={ !isProActive()}
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSwitcher
					label={langString('disable_waiting_room') }
					description={langString('disable_waiting_room_hint') }
					checked={disable_waiting_room}
					onChange={(name, value) => handleChange(name, value)}
					name="disable_waiting_room"
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSwitcher
					label={langString('meeting_authentication')}
					description={langString('meeting_authentication_hint') }
					checked={meeting_authentication}
					onChange={(name, value) => handleChange(name, value)}
					name="meeting_authentication"
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSwitcher
					label={langString('join_before_host') }
					description={langString('join_before_host_hint') }
					checked={join_before_host}
					onChange={(name, value) => handleChange(name, value)}
					name="join_before_host"
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSwitcher
					label={langString('mute_on_entry') }
					description={langString('mute_on_entry_hint') }
					checked={option_mute_participants}
					onChange={(name, value) => handleChange(name, value)}
					name="option_mute_participants"
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSwitcher
					label={langString('practise_session') }
					description={langString('practise_session_hint') }
					checked={practice_session}
					onChange={(name, value) => handleChange(name, value)}
					name="practice_session"
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSwitcher
					label={langString('multiple_devices') }
					description={langString('multiple_devices_hint') }
					checked={allow_multiple_devices}
					onChange={(name, value) => handleChange(name, value)}
					name="allow_multiple_devices"
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSelect
					label={langString('auto_recording') }
					description={langString('auto_recording_hint')}
					options={autoRecording}
					value={auto_recording}
					onChange={(name, value) => handleChange(name, value)}
					name="auto_recording"
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSwitcher
					label={langString('hide_sidebar') }
					description={langString('hide_sidebar_help') }
					name="hide_sidebar"
					checked={hide_sidebar}
					onChange={(name, value) => handleChange(name, value)}
				/>
			</div>

			<div className="mhub-col-lg-12">
				<MhSwitcher
					label={langString('hide_header_footer') }
					description={langString('hide_header_footer_help')}
					name="hide_header_footer"
					checked={hide_header_footer}
					onChange={(name, value) => handleChange(name, value)}
				/>
			</div>
		</div>
	);
};

export default ConfigTab;
