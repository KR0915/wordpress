// SettingsTab.js
import React from 'react';
import { langString } from '../../../Helpers';

const SettingsTab = () => {
  return (
    <div className="settings-tab-container">
      <h2> { langString('settings_tab_coming')}</h2>
      <p> {langString('feature_under_development')}</p>
    </div>
  );
};

export default SettingsTab;
