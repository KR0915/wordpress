import React from "react";
import MhSwitcher from "../../common/fields/MhSwitcher";
const { __ } = wp.i18n;
import { langString } from "../../../Helpers";

const VideoSettingsTab = ({ formData, setFormData }) => {

  const handleChange = (name, value) => {
    setFormData({ ...formData, [name]: value });
  };

  const { option_host_video, option_participants_video, panelists_video, hd_video } = formData;

  return (
    <div>
		<div className="mhub-col-lg-12">
			<MhSwitcher
				label={ langString('start_when_host_joins') }
				description={langString('start_when_host_joins_hint') }		 
				checked={option_host_video}
				onChange={(name, value) => handleChange(name, value)}
				name="option_host_video"
			/>
		</div>

		<div className="mhub-col-lg-12">
			<MhSwitcher
				label={langString('participants_video') }
				description={langString('participants_video_hint') }					
				checked={option_participants_video}
				onChange={(name, value) => handleChange(name, value)}
				name="option_participants_video"
			/>
		</div>

		<div className="mhub-col-lg-12">
			<MhSwitcher
				label={langString('panelists_join') }
				description={langString('panelists_video_hint') }
				checked={panelists_video}
				onChange={(name, value) => handleChange(name, value)}
				name="panelists_video"
			/>
		</div>

		<div className="mhub-col-lg-12">
			<MhSwitcher
				label={langString('hd_video') }
				description={langString('hd_video_hint') }				
				checked={hd_video}
				onChange={(name, value) => handleChange(name, value)}
				name="hd_video"
			/>
		</div>
    </div>
  );
};

export default VideoSettingsTab;
