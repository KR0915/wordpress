import React from "react";
import MhSwitcher from "../../common/fields/MhSwitcher";
import MhInput from "../../common/fields/MhInput";
import { isProActive } from "../../../Helpers";
const { __ } = wp.i18n;
import { langString } from "../../../Helpers";

const AudioSettingsTab = ({ formData, setFormData }) => {

  const handleChange = (name, value) => {
    setFormData({ ...formData, [name]: value });
  };

  const { yourself_muted, audio_muted, audio_only, start_silent } = formData;

  return (
    <div>
      <div className="mhub-col-lg-12">
        <MhSwitcher
          label={langString('yourself_muted') }
          description={langString('start_with_yourself_muted') }
          name="yourself_muted"
          checked={yourself_muted}
          onChange={(name, value) => handleChange(name, value)}
        />
      </div>
      <div className="mhub-col-lg-12">
        <MhInput
          label={langString('audio_muted_after') }
          description={langString('audio_muted_nth') }
          type="number"
          value={audio_muted}
          onChange={(name, value) => handleChange(name, value)}
          name="audio_muted"
          required="no"
        />
      </div> 
      <div className="mhub-col-lg-12">
        <MhSwitcher
          label={langString('audio_only') }
          description={langString('start_audio_only') }
          name="audio_only"
          checked={audio_only}
          onChange={(name, value) => handleChange(name, value)}
          disabled={!isProActive()} 
          isLocked={ !isProActive()}
        />
      </div>
      <div className="mhub-col-lg-12">
        <MhSwitcher
          label={langString('start_silent') }
          description={langString('disable_local_audio') }          
          name="start_silent"
          checked={start_silent}
          onChange={(name, value) => handleChange(name, value)}
          disabled={!isProActive()} 
          isLocked={ !isProActive()}
        />
      </div>
    </div>
  );
};

export default AudioSettingsTab;
