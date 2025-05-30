import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import "../../scss/settings/tab.scss";
import Spinner from "../common/Spinner";
import SettingIcons from "./SettingIcons";
import ApiSettingsTab from "./jitsi/ApiSettingsTab";
import AudioSettingsTab from "./jitsi/AudioSettingsTab";
import ConfigTab from "./jitsi/ConfigTab";
import VideoSettingsTab from "./jitsi/VideoSettingsTab";
import { toast } from 'react-toastify';
const { __ } = wp.i18n;
import ShortcodesTab from "./jitsi/ShortcodesTab";
import { langString } from "../../Helpers";

function JitsiSettings() {
  const storedTab = localStorage.getItem("mhub_jitsi_settings_active_tab");
  const [activeTab, setActiveTab] = useState(
    storedTab ? storedTab : "ApiSettings"
  );

  const [formData, setFormData] = useState({
    domain_type: "jitsi_random_public",
    custom_domain: "",
    app_id: "",
    api_key: "",
    private_key: "",
    yourself_muted: false,
    audio_muted: false,
    audio_only: false,
    start_silent: false,
    start_with_video_muted: false,
    start_with_screen_sharing: false,
    video_resolution: "",
    max_full_resolution: "",
    video_muted_after: "",
    height: "720",
    width: "1080",
    enable_inviting: false,
    hide_inviting: false,
    enable_recording: false,
    enable_simulcast: false,
    enable_livestreaming: false,
    enable_welcome_page: false,
    enable_transcription: false,
    enable_outbound: false,
    hide_sidebar: false,
    enable_should_register: false,
    enable_recurring_meeting: false,
    meeting_timezone: mhubMeetingsData.mhub_timezone,
    hide_header_footer: false,
  });

  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [saveButtonText, setSaveButtonText] = useState( langString('save_changes') );
  const [saveButtonClass, setSaveButtonClass] = useState("");

  useEffect(() => {
    // Retrieve active tab from localStorage on component mount
    if (storedTab) {
      setActiveTab(storedTab);
    }
  }, []);

  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        const response = await wp.apiFetch({
          path: "meetinghub/v2/settings/jitsi",
          method: "GET",
        });

        if (response) {
          // Update state with fetched data
          setFormData({
            domain_type: response.domain_type || formData.domain_type,
            custom_domain: response.custom_domain || formData.custom_domain,
            app_id: response.app_id || formData.app_id,
            api_key: response.api_key || formData.api_key,
            private_key: response.private_key || formData.private_key,
            yourself_muted: response.yourself_muted || formData.yourself_muted,
            audio_muted: response.audio_muted || formData.audio_muted,
            audio_only: response.audio_only || formData.audio_only,
            start_silent: response.start_silent || formData.start_silent,
            start_with_video_muted: response.start_with_video_muted || formData.start_with_video_muted,
            start_with_screen_sharing:
              response.start_with_screen_sharing || formData.start_with_screen_sharing,
            video_resolution: response.video_resolution || formData.video_resolution,
            max_full_resolution: response.max_full_resolution || formData.max_full_resolution,
            video_muted_after: response.video_muted_after || formData.video_muted_after,
            height: response.height || formData.height,
            width: response.width || formData.width,
            enable_inviting: response.enable_inviting || formData.enable_inviting,
            hide_inviting: response.hide_inviting || formData.hide_inviting,
            enable_recording: response.enable_recording || formData.enable_recording,
            enable_simulcast: response.enable_simulcast || formData.enable_simulcast,
            enable_livestreaming: response.enable_livestreaming || formData.enable_livestreaming,
            enable_welcome_page: response.enable_welcome_page || formData.enable_welcome_page,
            enable_transcription: response.enable_transcription || formData.enable_transcription,
            enable_outbound: response.enable_outbound || formData.enable_outbound,
            hide_sidebar: response.hide_sidebar || formData.hide_sidebar,
            enable_should_register: response.enable_should_register || formData.enable_should_register,
            enable_recurring_meeting: response.enable_recurring_meeting || formData.enable_recurring_meeting,
            meeting_timezone: response.meeting_timezone || formData.meeting_timezone,
            hide_header_footer: response.hide_header_footer || formData.hide_header_footer,
          });

          setIsLoading(false);
        }
      } catch (error) {
        console.error( langString('api_error') , error);
        setIsLoading(false);
      }
    };

    fetchData();
  }, []);

  const handleTabClick = (tabName) => {
    setActiveTab(tabName);
    // Store active tab in localStorage
    localStorage.setItem("mhub_jitsi_settings_active_tab", tabName);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setIsSaving(true);
    try {
      const response = await wp.apiFetch({
        path: "meetinghub/v2/settings/jitsi",
        method: "POST",
        data: {
          domain_type: formData.domain_type,
          custom_domain: formData.custom_domain,
          app_id: formData.app_id,
          api_key: formData.api_key,
          private_key: formData.private_key,
          yourself_muted: formData.yourself_muted,
          audio_muted: formData.audio_muted,
          audio_only: formData.audio_only,
          start_silent: formData.start_silent,
          start_with_video_muted: formData.start_with_video_muted,
          start_with_screen_sharing: formData.start_with_screen_sharing,
          video_resolution: formData.video_resolution,
          max_full_resolution: formData.max_full_resolution,
          video_muted_after: formData.video_muted_after,
          height: formData.height,
          width: formData.width,
          enable_inviting: formData.enable_inviting,
          hide_inviting: formData.hide_inviting,
          enable_recording: formData.enable_recording,
          enable_simulcast: formData.enable_simulcast,
          enable_livestreaming: formData.enable_livestreaming,
          enable_welcome_page: formData.enable_welcome_page,
          enable_transcription: formData.enable_transcription,
          enable_outbound: formData.enable_outbound,
          hide_sidebar: formData.hide_sidebar,
          enable_should_register: formData.enable_should_register,
          enable_recurring_meeting: formData.enable_recurring_meeting,
          meeting_timezone: formData.meeting_timezone,
          hide_header_footer: formData.hide_header_footer,
        },
      });

      if (response && response.jitsi_settings_saved) {
        toast.success( langString('settings_saved') );
        setSaveButtonText( langString('saved') );
        setSaveButtonClass("saved");
        setTimeout(() => {
          setSaveButtonText( langString('save_changes') );
          setSaveButtonClass("");
        }, 2000);
      } else {
        toast.error( langString('failed_update_settings') );
      }
    } catch (error) {
      console.error( langString('api_error') , error);
    } finally {
      setIsSaving(false);
    }
  };

  const renderTabContent = () => {
    // Map tab names to corresponding components
    const tabComponents = {
      ApiSettings: (
        <ApiSettingsTab formData={formData} setFormData={setFormData} />
      ),
      VideoSettings: (
        <VideoSettingsTab formData={formData} setFormData={setFormData} />
      ),
      Configurations: (
        <ConfigTab formData={formData} setFormData={setFormData} />
      ),
      AudioSettings: (
        <AudioSettingsTab formData={formData} setFormData={setFormData} />
      ),
    };

    // Render the component for the active tab
    return tabComponents[activeTab] || null;
  };

  console.log(activeTab);

  return (
    <div className="jitsi-settings-container">
      <Link to="/" className="back-button">
        &lt; { langString('back_to_settings') }
      </Link>

      <div className="main-wrapper">
        <div className="header">
          {SettingIcons.jitsi}
          <div className="title">{ langString('jitsi_meet') }</div>
        </div>

        <div className="tab-wrapper">
          <div className="tab">
            <div
              className={`tab-item ${activeTab === "ApiSettings" ? "active" : ""
                }`}
              onClick={() => handleTabClick("ApiSettings")}
            >
             { langString('api_settings') }
            </div>

            <div
              className={`tab-item ${activeTab === "Configurations" ? "active" : ""
                }`}
              onClick={() => handleTabClick("Configurations")}
            >
              { langString('configurations') }
            </div>

            <div
              className={`tab-item ${activeTab === "VideoSettings" ? "active" : ""
                }`}
              onClick={() => handleTabClick("VideoSettings")}
            >
             { langString('video_settings') }
            </div>

            <div
              className={`tab-item ${activeTab === "AudioSettings" ? "active" : ""
                }`}
              onClick={() => handleTabClick("AudioSettings")}
            >
              { langString('audio_settings') }
            </div>
            <div
              className={`tab-item ${activeTab === "Shortcodes" ? "active" : ""
                }`}
              onClick={() => handleTabClick("Shortcodes")}
            >
              { langString('shortcodes') }
            </div>
          </div>
          <a
            className="create-meeting-btn"
            href={mhubMeetingsData.createMeetingUrl}
          >
            <span className="dashicons dashicons-plus-alt2"></span>
            { langString('create_meeting') }
          </a>
        </div>

        {isLoading ? (
          <Spinner />
        ) : (
          <div
            className={` ${"ApiSettings" === activeTab
                ? "jitsi-api-settings-container"
                : "mhub-jitsi-meeting-form jitsi-settings-api"
              }`}
          >
            <div className="form-wrapper">
              {activeTab !== "Shortcodes" && (
                <form onSubmit={handleSubmit}>
                  {renderTabContent()}
                  <div className="mhub-save-actions">
                    <button
                      type="submit"
                      className={`setting-save-button ${saveButtonClass}`}
                      disabled={isSaving}
                    >
                      {isSaving ? langString('saving')  : saveButtonText}
                    </button>
                  </div>
                </form>
              )}
              {activeTab === "Shortcodes" && (
                <ShortcodesTab />
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default JitsiSettings;
