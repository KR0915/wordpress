import React from "react";
import MhInput from "../../common/fields/MhInput";
import MhSwitcher from "../../common/fields/MhSwitcher";
import TimezoneList from "../../common/fields/TimezoneList";
import MhSelect from "../../common/fields/MhSelect";
import { isProActive } from "../../../Helpers";
const { __ } = wp.i18n;
import Spinner from "../../common/Spinner";
import { langString } from "../../../Helpers";

const ConfigTab = ({ formData, setFormData, isLoading }) => {

  const handleChange = (name, value) => {
    setFormData({ ...formData, [name]: value });
  };

  //timezones
  const Timezones = TimezoneList();

  const { meeting_timezone, hide_sidebar, hide_header_footer, reminder_time, enable_should_register, enable_recurring_meeting, event_status,send_updates,transparency, event_visibility  } = formData;

  const defaultReminderOptions = [
	{ value: '1', label: langString('minutes_before_1') },
	{ value: '5', label: langString('minutes_before_5') },
	{ value: '15', label: langString('minutes_before_15')  },
	{ value: '30', label: langString('minutes_before_30') },
];

const defaultEventStatusOptions = [
	{ value: 'confirmed', label: langString('confirmed') },
	{ value: 'tentative', label: langString('tentative') },
];

const sendStatusOptions = [
	{ value: 'all', label: langString('all') },
	{ value: 'externalOnly', label: langString('external_only')  },
	{ value: 'none', label: langString('none') },
];

const transparencyOptions = [
	{ value: 'opaque', label: langString('opaque_block')  },
	{ value: 'transparent', label: langString('transparent_not_block') },
];

const eventVisibilityOptions = [
	{ value: 'default', label: langString('default')},
	{ value: 'public', label: langString('public')  },
	{ value: 'private', label: langString('private')  },
];

if( isLoading ){
    return <Spinner/>
  }
	

  return (
    <div>
		
		<div className="mhub-col-lg-12">
			<MhSelect
				label={langString('timezone') }
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
				description={langString('should_register_help') }				
				checked={enable_should_register}
				onChange={(name, value) => handleChange(name, value)}
				name="enable_should_register"
				disabled={!isProActive()} 
				isLocked={ !isProActive()}
			/>
		</div>

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
				label={langString('default_reminder_time') }
				description={langString('default_reminder_time_help')}
				options={defaultReminderOptions}
				value={reminder_time}
				onChange={(name, value) => handleChange(name, value)}
				name="reminder_time"
				disabled={!isProActive()} 
          		isLocked={ !isProActive()}
			/>
		</div>
		
		<div className="mhub-col-lg-12">
			<MhSelect
				label={langString('default_event_status')}
				description={langString('default_event_status_meet') }
				options={defaultEventStatusOptions}
				value={event_status}
				onChange={(name, value) => handleChange(name, value)}
				name="event_status"
				disabled={!isProActive()} 
          		isLocked={ !isProActive()}
			/>
		</div>

		<div className="mhub-col-lg-12">
			<MhSelect
				label={langString('send_updates') }
				description={langString('send_updates_help') }
				options={sendStatusOptions}
				value={send_updates}
				onChange={(name, value) => handleChange(name, value)}
				name="send_updates"
				disabled={!isProActive()} 
          		isLocked={ !isProActive()}
			/>
		</div>

		<div className="mhub-col-lg-12">
			<MhSelect
				label={langString('transparency') }
				description={langString('transparency_help') }
				options={transparencyOptions}
				value={transparency}
				onChange={(name, value) => handleChange(name, value)}
				name="transparency"
				disabled={!isProActive()} 
          		isLocked={ !isProActive()}
			/>
		</div>

		<div className="mhub-col-lg-12">
			<MhSelect
				label={langString('visibility_on_calendar') }
				description={langString('visibility_on_calendar_help') }
				options={eventVisibilityOptions}
				value={event_visibility}
				onChange={(name, value) => handleChange(name, value)}
				name="event_visibility"
				disabled={!isProActive()} 
          		isLocked={ !isProActive()}
			/>
		</div>


		<div className="mhub-col-lg-12">
			<MhSwitcher
				label={langString('hide_sidebar') }
				description={langString('hide_sidebar_help')}
				name="hide_sidebar"
				checked={hide_sidebar}
				onChange={(name, value) => handleChange(name, value)}
			/>
		</div>

		<div className="mhub-col-lg-12">
			<MhSwitcher
				label={langString('hide_header_footer') }
				description={langString('hide_header_footer_help') }				
				name="hide_header_footer"
				checked={hide_header_footer}
				onChange={(name, value) => handleChange(name, value)}
			/>
		</div>
    </div>
  );
};

export default ConfigTab;
