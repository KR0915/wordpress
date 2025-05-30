import React, { useState, useEffect } from 'react';
import '../scss/dashboard/_addon.scss';
const { __ } = wp.i18n;
import SettingIcons from '../components/settings/SettingIcons';
import { ToastContainer, toast } from 'react-toastify';
import Spinner from "../components/common/Spinner";
import { isProActive } from '../Helpers';
import { useMhubAdmin } from './MhubAdminContext';
import { langString } from '../Helpers';

const FEATURES_DATA = [
  {
    key: "frontend_addon",
    icon: "",
    iconBg: "#f0f2f5",
    title:  langString('frontend_meeting'),
    description: langString('frontend_manage_msg'),
    active: false,
    pro: true,
  },
  {
    key: "multivendor_addon",
    icon: "",
    iconBg: "#f0f2f5",
    title: langString('multivendor_addon'),
    description: langString('multivendor_desc'),
    active: false,
    pro: true,
},

];


const AddonApp = () => {
  const [features, setFeatures] = useState(FEATURES_DATA);
  const [copyStatus, setCopyStatus] = useState(null);
  const [loadingKeys, setLoadingKeys] = useState([]);
  const [loading, setLoading] = useState(true);
  const { openProModal } = useMhubAdmin();

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await wp.apiFetch({
          path: '/meetinghub/v2/settings/addon',
          method: 'GET',
        });
  
        if (response) {
          const updatedFeatures = features.map(feature => ({
            ...feature,
            active: feature.pro && !isProActive() ? false : (response[feature.key] ?? feature.active),
          }));
          setFeatures(updatedFeatures);
          setLoading(false);
        }
      } catch (error) {
        console.error( langString('api_error'), error);
      }
    };
  
    fetchSettings();
  }, []);
  

  const handleToggle = async (key,pro) => {
    if (loadingKeys.includes(key)) return;

    if (!isProActive() && pro) {
        openProModal();
        return; // Prevent the toggle from proceeding
      }

    const updatedFeatures = features.map(feature =>
      feature.key === key ? { ...feature, active: !feature.active } : feature
    );

    setFeatures(updatedFeatures);
    setLoadingKeys(prev => [...prev, key]);

    const settings = {};
    updatedFeatures.forEach(feature => {
      settings[feature.key] = feature.active;
    });

    try {
      const response = await wp.apiFetch({
        path: '/meetinghub/v2/settings/addon',
        method: 'POST',
        data: { mhub_addons_settings: settings },
      });

      if (response && response.addon_settings_saved) {
        toast.success(langString('settings_updated'));
      } else {
        throw new Error( langString('settings_failed_save'));
      }
    } catch (error) {
      console.error( langString('api_error'), error);
      toast.error( langString('settings_failed_update'));

      const revertedFeatures = features.map(feature =>
        feature.key === key ? { ...feature, active: !feature.active } : feature
      );
      setFeatures(revertedFeatures);
    } finally {
      setLoadingKeys(prev => prev.filter(loadingKey => loadingKey !== key));
    }
  };

  const handleCopyShortcode = (shortcode) => {
    const tempTextArea = document.createElement('textarea');
    tempTextArea.value = shortcode;
    document.body.appendChild(tempTextArea);
    tempTextArea.select();
    document.execCommand('copy');
    document.body.removeChild(tempTextArea);

    setCopyStatus( langString('copied') );
    setTimeout(() => setCopyStatus(null), 1500);
  };

 if (loading) {
    return <Spinner />;
 }

  return (
    <div className="mhub-grid-container">
      {features.map(feature => (
        <div key={feature.key} className="mhub-card">
           <div className='mhub-ct-wrapper'>
              <div className="mhub-icon" style={{ background: feature.iconBg }}>
                {(() => {
                    switch (feature.key) {
                      case "frontend_addon":
                        return SettingIcons.frontend_meeting;
                      case "multivendor_addon":
                        return SettingIcons.multivendor;
                      default:
                        return null;
                    }
                  })()}
              </div>

              <div className="mhub-title">{feature.title}</div>
          </div>

          <div className="mhub-description">
            {feature.description}

            {feature.key === "frontend_addon" && feature.active && isProActive() && (
              <div className="mhub-shortcode-wrapper">
                <p> { langString('shortcode_instruction') }</p>
                <span className="shortcode">[mhub-frontend-meeting /]</span>
                <span
                  className="shortcode-copy"
                  onClick={() => handleCopyShortcode('[mhub-frontend-meeting /]')}
                  title="Copy Shortcode"
                >
                  <i className="dashicons dashicons-admin-page"></i>
                </span>
                {copyStatus && <span className="copy-status">{copyStatus}</span>}
              </div>
            )}
          </div>

          <div className="mhub-toggle-container">
            <span className="mhub-toggle-text"> {langString('toggle_activate_deactivate')} { feature.pro  && !isProActive() ? (<span className="mhub-pro-tag mhub-tag-addon" onClick={openProModal}>{langString('pro')}</span>) : ''}</span>
            <div
              className={`mhub-toggle ${feature.active ? 'active' : ''} ${loadingKeys.includes(feature.key) ? 'loading' : ''}`}
              onClick={() => handleToggle(feature.key,feature.pro)}
            />
          </div>
        </div>
      ))}

        <ToastContainer
            position="top-right"
            autoClose={3000}
            hideProgressBar={false}
            newestOnTop={false}
            closeOnClick
            rtl={false}
            pauseOnFocusLoss
            draggable
            pauseOnHover
            theme="light"
            style={{ marginTop: '30px' }}
        />	
    </div>
  );
};

export default AddonApp;
