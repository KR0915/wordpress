import React, { useState } from "react";
const { __ } = wp.i18n;
import '../../../scss/settings/shortcode-tab.scss';
import { langString } from "../../../Helpers";

const shortcodes = [
  { title: langString('webex_meeting_list') , description: langString('embed_webex_meetings') , code: "[mhub-webex-meeting-list]" },
];

const ShortcodesTab = () => {
  const [copiedIndex, setCopiedIndex] = useState(null);

  const handleCopyShortcode = (shortcode, index) => {
    const tempTextArea = document.createElement('textarea');
    tempTextArea.value = shortcode;
    document.body.appendChild(tempTextArea);

    tempTextArea.select();
    document.execCommand('copy');

    document.body.removeChild(tempTextArea);

    setCopiedIndex(index);

    // Reset copy status after a short delay
    setTimeout(() => setCopiedIndex(null), 1500);
  };

  return (
    <div className="shortcodes-tab">
      <h2>{langString('shortcodes') }</h2>
      <p>{langString('shortcodes_instruction') }</p>
      <div className="shortcodes-list">
        {shortcodes.map((shortcode, index) => (
          <div key={index} className="shortcode-item">
            <div className="left-content">
              <h3>{shortcode.title}</h3>
              <p>{shortcode.description}</p>
            </div>
            <div className="right-content">
              <button 
                onClick={() => handleCopyShortcode(shortcode.code, index)} 
                className="copy-shortcode-btn"
              >
                
                {copiedIndex === index ? (<span> {langString('copied_success')} </span>) : (<><i className='dashicons dashicons-admin-page'></i> <span> {langString('copy_shortcode')}</span> </>)}
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default ShortcodesTab;
