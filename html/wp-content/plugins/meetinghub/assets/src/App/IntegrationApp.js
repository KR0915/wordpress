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
    key: "woocommerce",
    icon: "",
    iconBg: "#f0f2f5",
    title: langString('woocommerce'),
    description: langString('woocommerce_sell_msg'),
    active: false,
    pro: true,
  },
  {
    key: "woocommerce_booking",
    icon: "",
    iconBg: "#f0f2f5",
    title: langString('woocommerce_booking'),
    description: langString('booking_auto_meeting'),
    active: false,
    pro: true,
  },
  {
    key: "tutor_lms",
    icon: "",
    iconBg: "#f0f2f5",
    title: langString('tutor_lms'),
    description: langString('tutor_lms_msg'),
    active: false,
    pro: true,
  },
  {
    key: "academy_lms",
    icon: "",
    iconBg: "#f0f2f5",
    title: langString('academy_lms'),
    description: langString('academy_lms_msg'),
    active: false,
    pro: true,
  },
  {
    key: "learnpress_lms",
    icon: "",
    iconBg: "#f0f2f5",
    title: langString('learnpress'),
    description: langString('learnpress_msg'),
    active: false,
    pro: true,
  },
  {
    key: "google_calendar",
    icon: "",
    iconBg: "#f0f2f5",
    title: langString('google_calendar'),
    description: langString('google_calendar_msg'),
    active: false,
    pro: true,
  },
];

const IntegrationApp = () => {
  const [features, setFeatures] = useState(FEATURES_DATA);
  const [copyStatus, setCopyStatus] = useState(null);
  const [loadingKeys, setLoadingKeys] = useState([]);
  const [loading, setLoading] = useState(true);
  const { openProModal } = useMhubAdmin();

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await wp.apiFetch({
          path: '/meetinghub/v2/settings/integration',
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
        path: '/meetinghub/v2/settings/integration',
        method: 'POST',
        data: { mhub_integration_settings: settings },
      });

      if (response && response.integration_settings_saved) {
        toast.success(langString('settings_updated'));
      } else {
        throw new Error(langString('settings_failed_save'));
      }
    } catch (error) {
      console.error(langString('api_error'), error);
      toast.error(langString('settings_failed_update'));

      const revertedFeatures = features.map(feature =>
        feature.key === key ? { ...feature, active: !feature.active } : feature
      );
      setFeatures(revertedFeatures);
    } finally {
      setLoadingKeys(prev => prev.filter(loadingKey => loadingKey !== key));
    }
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
                  case "woocommerce":
                    return SettingIcons.woocommerce;
                  case "google_calendar":
                    return SettingIcons.google_clender;
                  case "tutor_lms":
                    return SettingIcons.tutor_lms;
                  case "woocommerce_booking":
                    return SettingIcons.woo_booking;
                  case "academy_lms":
                    return SettingIcons.academy;
                  case "learnpress_lms":
                    return <i className="dashicons-before dashicons-welcome-learn-more"></i>;
                  default:
                    return null;
                }
              })()}
            </div>

            <div className="mhub-title">{feature.title}</div>
          </div>

          <div className="mhub-description">
            {feature.description}
          </div>

          <div className="mhub-toggle-container">
            <span className="mhub-toggle-text">{langString('toggle_enable_disable')} { feature.pro  && !isProActive() ? (<span className="mhub-pro-tag mhub-tag-addon" onClick={openProModal}>{langString('pro')}</span>) : ''}</span>
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

export default IntegrationApp;
