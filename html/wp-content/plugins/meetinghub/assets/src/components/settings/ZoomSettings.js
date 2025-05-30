// ZoomSettings.js
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import '../../scss/settings/tab.scss';
import SettingIcons from './SettingIcons';
import ConnectTab from './zoom/ConnectTab';
import Spinner from '../common/Spinner';
import ConfigTab from './zoom/ConfigTab';
import VideoSettingsTab from './zoom/VideoSettingsTab';
import { toast } from 'react-toastify';
const { __ } = wp.i18n;
import ShortcodesTab from './zoom/ShortcodesTab';
import { langString } from '../../Helpers';

function ZoomSettings() {
  const storedTab = localStorage.getItem('mhub_zoom_settings_active_tab');
  const [activeTab, setActiveTab] = useState(storedTab ? storedTab : 'Connect');

  const [saveButtonText, setSaveButtonText] = useState( langString('save_changes') );
  const [saveButtonClass, setSaveButtonClass] = useState('');
  const [isSaving, setIsSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  // State for form data
  const [formData, setFormData] = useState({
    oauth_account_id: '',
    oauth_client_id: '',
    oauth_client_secret: '',
    sdk_client_id: '',
    sdk_client_secret: '',
    enable_recurring_meeting: false,
    meeting_timezone: mhubMeetingsData.mhub_timezone,
    enable_should_register: false,
    disable_waiting_room: false,
    meeting_authentication: false,
    join_before_host: false,
    option_host_video: false,
    option_participants_video: false,
    option_mute_participants: false,
    panelists_video: false,
    practice_session: false,
    hd_video: false,
    allow_multiple_devices: false,
    auto_recording: '',
    hide_sidebar: false,
    hide_header_footer: false,

  });

  useEffect(() => {
    // Retrieve active tab from localStorage on component mount
    if (storedTab) {
      setActiveTab(storedTab);
    }
  }, []);

  // UseEffect to fetch Zoom settings when the component mounts
  useEffect(() => {
    const fetchZoomSettings = async () => {
      setIsLoading(true);
      try {
        const settingsResponse = await wp.apiFetch({
          path: 'meetinghub/v2/settings/zoom',
          method: 'GET',
        });

        setFormData({
          oauth_account_id: settingsResponse.oauth_account_id || formData.oauth_account_id,
          oauth_client_id: settingsResponse.oauth_client_id || formData.oauth_client_id,
          oauth_client_secret: settingsResponse.oauth_client_secret || formData.oauth_client_secret,
          sdk_client_id: settingsResponse.sdk_client_id || formData.sdk_client_id,
          sdk_client_secret: settingsResponse.sdk_client_secret || formData.sdk_client_secret,
          enable_recurring_meeting: settingsResponse.enable_recurring_meeting || formData.enable_recurring_meeting,
          meeting_timezone: settingsResponse.meeting_timezone || formData.meeting_timezone,
          enable_should_register: settingsResponse.enable_should_register || formData.enable_should_register,
          disable_waiting_room: settingsResponse.disable_waiting_room || formData.disable_waiting_room,
          meeting_authentication: settingsResponse.meeting_authentication || formData.meeting_authentication,
          join_before_host: settingsResponse.join_before_host || formData.join_before_host,
          option_host_video: settingsResponse.option_host_video || formData.option_host_video,
          option_participants_video: settingsResponse.option_participants_video || formData.option_participants_video,
          option_mute_participants: settingsResponse.option_mute_participants || formData.option_mute_participants,
          panelists_video: settingsResponse.panelists_video || formData.panelists_video,
          practice_session: settingsResponse.practice_session || formData.practice_session,
          hd_video: settingsResponse.hd_video || formData.hd_video,
          allow_multiple_devices: settingsResponse.allow_multiple_devices || formData.allow_multiple_devices,
          auto_recording: settingsResponse.auto_recording || formData.auto_recording,
          hide_sidebar: settingsResponse.hide_sidebar || formData.hide_sidebar,
          hide_header_footer: settingsResponse.hide_header_footer || formData.hide_header_footer,
        });
      } catch (error) {
        console.error( langString('api_error') , error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchZoomSettings();
  }, []);

  const handleTabClick = (tabName) => {
    setActiveTab(tabName);
    // Store active tab in localStorage
    localStorage.setItem('mhub_zoom_settings_active_tab', tabName);
  };


  // Function to handle form submission
  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSaving(true);

    try {
      const response = await wp.apiFetch({
        path: 'meetinghub/v2/settings/zoom',
        method: 'POST',
        data: formData,
      });

      if (response && response.zoom_settings_saved) {
        toast.success( langString('settings_saved') );
        setSaveButtonText( langString('saved') );
        setSaveButtonClass('saved');

        setTimeout(() => {
          setSaveButtonText( langString('save_changes') );
          setSaveButtonClass('');
        }, 3000);
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
      Connect: <ConnectTab formData={formData} setFormData={setFormData} />,
      Configurations: (
        <ConfigTab formData={formData} setFormData={setFormData} />
      ),
      VideoSettings: (
        <VideoSettingsTab formData={formData} setFormData={setFormData} />
      ),
    };

    // Render the component for the active tab
    return tabComponents[activeTab] || null;
  };

  return (
    <div className="zoom-settings-container">
      <Link to="/" className="back-button">
        &lt; { langString('back_to_settings') }
      </Link>

      <div className="main-wrapper">
        <div className="header">
          {SettingIcons.zoom}
          <div className="title">{ langString('zoom') }</div>
        </div>

        <div className="tab-wrapper">
          <div className="tab">
            <div
              className={`tab-item ${activeTab === 'Connect' ? 'active' : ''}`}
              onClick={() => handleTabClick('Connect')}
            >
             { langString('connect') }
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
            <span className="dashicons dashicons-plus-alt2"></span>{ langString('create_meeting') }
          </a>
        </div>

       {isLoading ? (
          <Spinner />
        ) : (
          <div className="webex-meeting-form ">
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
                   {isSaving ?  langString('saving')  : saveButtonText}
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

export default ZoomSettings;
