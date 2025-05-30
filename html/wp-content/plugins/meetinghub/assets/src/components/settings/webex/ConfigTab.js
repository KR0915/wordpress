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

  const { meeting_timezone, hide_sidebar, hide_header_footer, auto_record, breakout_sessions, automatic_lock, lock_minutes, enable_should_register, enable_recurring_meeting, join_before_host } = formData;

  	//Lock data
	  const lockOptions = [
		{ value: '0', label: __('0', 'meetinghub') },
		{ value: '5', label: __('5', 'meetinghub') },
		{ value: '10', label: __('10', 'meetinghub') },
		{ value: '15', label: __('15', 'meetinghub') },
		{ value: '20', label: __('20', 'meetinghub') },
	];
	

  return (
    <div>
	
		<div className="mhub-col-lg-12">
			<MhSelect
				label={ langString('timezone') }
				description={ langString('meeting_timezone') }				
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
				disabled={true}
				isUpcomming={true}
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
				label={langString('auto_record') }
				description={langString('auto_record_hint') }				
				checked={auto_record}
				onChange={(name, value) => handleChange(name, value)}
				name="auto_record"
			/>
		</div>

		<div className="mhub-col-lg-12">
			<MhSwitcher
				label={langString('enable_breakout') }
				description={langString('enable_breakout_hint') }				
				checked={breakout_sessions}
				onChange={(name, value) => handleChange(name, value)}
				name="breakout_sessions"
			/>
		</div>

		<div className="mhub-col-lg-12">
			<MhSwitcher
				label={langString('enable_auto_lock') }
				description={langString('enable_auto_lock_hint') }				
				checked={automatic_lock}
				onChange={(name, value) => handleChange(name, value)}
				name="automatic_lock"
			/>
		</div>

		<div className="mhub-col-lg-12">
			<MhSelect
				label={langString('lock_after') }
				description={langString('lock_after_hint') }					
				options={lockOptions}
				value={lock_minutes}
				onChange={(name, value) => handleChange(name, value)}
				name="lock_minutes"
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
