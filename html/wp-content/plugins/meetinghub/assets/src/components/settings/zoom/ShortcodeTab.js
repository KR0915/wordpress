// ShortcodeTab.js
import React from 'react';
import { langString } from '../../../Helpers';

const ShortcodeTab = () => {
  return (
    <div className="shortcode-tab-container">
      <h2> {langString('shortcode_tab_coming')}</h2>
      <p>{ langString('feature_under_development')}</p>
    </div>
  );
};

export default ShortcodeTab;