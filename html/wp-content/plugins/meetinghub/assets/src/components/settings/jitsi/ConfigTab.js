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

  const { height, width, enable_inviting, enable_recording, enable_simulcast, enable_livestreaming, enable_welcome_page, enable_transcription, enable_outbound, hide_sidebar, enable_should_register, meeting_timezone, enable_recurring_meeting, hide_header_footer, hide_inviting } = formData;

  return (
    <div>
      <div className="mhub-col-lg-12">
        <MhInput
          label={langString('meeting_height2') }
          description={langString('meeting_height_hint')}
          type="number"
          name="height"
          required="no"
          value={height}
          onChange={(name, value) => handleChange(name, value)}
        />
      </div>

      <div className="mhub-col-lg-12">
        <MhInput
          label={langString('meeting_width2') }
          description={langString('meeting_width_hint') }
          type="number"
          name="width"
          required="no"
          value={width}
          onChange={(name, value) => handleChange(name, value)}
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
          label={langString('enable_inviting') }
          description={langString('attendee_can_invite') }         
          name="enable_inviting"
          checked={enable_inviting}
          onChange={(name, value) => handleChange(name, value)}
        />
      </div>

      <div className="mhub-col-lg-12">
        <MhSwitcher
          label={langString('hide_inviting_option') }
          description={langString('hide_inviting_option_des') }         
          name="hide_inviting"
          checked={hide_inviting}
          onChange={(name, value) => handleChange(name, value)}
        />
      </div>

      <div className="mhub-col-lg-12">
        <MhSwitcher
          label={langString('enable_recording') }
          description={langString('enable_recording_hint') }         
          name="enable_recording"
          checked={enable_recording}
          onChange={(name, value) => handleChange(name, value)}
          disabled={!isProActive()} 
          isLocked={ !isProActive()}
        />
      </div>

      <div className="mhub-col-lg-12">
        <MhSwitcher
          label={langString('enable_simulcast') }
          description={langString('simulcast_hint') }          
          name="enable_simulcast"
          checked={enable_simulcast}
          onChange={(name, value) => handleChange(name, value)}
          disabled={!isProActive()} 
          isLocked={ !isProActive()}
        />
      </div>

      <div className="mhub-col-lg-12">
        <MhSwitcher
          label={langString('enable_livestream') }
          description={langString('livestream_hint') }         
          name="enable_livestreaming"
          checked={enable_livestreaming}
          onChange={(name, value) => handleChange(name, value)}
          disabled={!isProActive()} 
          isLocked={ !isProActive()}
        />
      </div>

      <div className="mhub-col-lg-12">
        <MhSwitcher
          label={langString('enable_welcome') }
          description={langString('welcome_hint') }         
          name="enable_welcome_page"
          checked={enable_welcome_page}
          onChange={(name, value) => handleChange(name, value)}
          disabled={!isProActive()} 
          isLocked={ !isProActive()}
        />
      </div>

      <div className="mhub-col-lg-12">
        <MhSwitcher
          label={langString('enable_transcription') }
          description={langString('transcription_hint') }
          name="enable_transcription"
          checked={enable_transcription}
          onChange={(name, value) => handleChange(name, value)}
          disabled={!isProActive()} 
          isLocked={ !isProActive()}
        />
      </div>

      <div className="mhub-col-lg-12">
        <MhSwitcher
          label={langString('enable_outbound') }
          description={langString('outbound_hint') }         
          name="enable_outbound"
          checked={enable_outbound}
          onChange={(name, value) => handleChange(name, value)}
          disabled={!isProActive()} 
          isLocked={ !isProActive()}
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
