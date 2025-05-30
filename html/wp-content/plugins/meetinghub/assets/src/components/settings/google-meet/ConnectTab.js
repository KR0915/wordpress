import React, { useState, useEffect } from "react";
import axios from "axios";
import "../../../scss/settings/connect-tab.scss";
import Spinner from "../../common/Spinner";
const { __ } = wp.i18n;
import { langString } from "../../../Helpers";

const ConnectTab = ({fileName,isUploading,isCredentialLoaded,isPermitted,error,link,redirectUrl,onFileUpload,onResetCredential, isGoogleLoading}) => {
  const [buttonText, setButtonText] = useState("Copy");

  const handleCopy = () => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(redirectUrl).then(() => {
        setButtonText( langString('copied') );
        setTimeout(() => setButtonText(langString('copy')), 2000);
      }).catch((err) => console.error(langString('failed_to_copy') , err));
    } else {
      const textArea = document.createElement("textarea");
      textArea.value = redirectUrl;
      document.body.appendChild(textArea);
      textArea.select();
      try {
        document.execCommand("copy");
        setButtonText(langString('copied'));
        setTimeout(() => setButtonText(langString('copy') ), 2000);
      } catch (err) {
        console.error(langString('fallback_failed_to_copy') , err);
      }
      document.body.removeChild(textArea);
    }
  };
  

  const handleDrop = (e) => {
    e.preventDefault();
    onFileUpload(e.dataTransfer.files[0]);
  };

  const handleFileInputChange = (e) => onFileUpload(e.target.files[0]);


  if( isGoogleLoading ){
    return <Spinner/>
  }

  return (
    <div className="mhub-google-meet-connect-tab">

  { ((!isCredentialLoaded && !isPermitted) || (isCredentialLoaded && isPermitted)) && (
        <div className="mhub-section-pannel additional-info-section">
         <div className="google-meet-integration">
            <div className="integration-content">
              <h2 className="title">
                {langString('setup_your')} <span>{langString('google_meet')}</span> {langString('integration')}
              </h2>
              <p>
                {langString('integration_instruction')}{' '}
                <a
                  href="https://console.cloud.google.com/apis/dashboard"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {langString('link')}
                </a>
                {' '}
                {langString('oauth_instruction')}{' '}
                <a
                  href="https://youtu.be/mvC9Wqi_FKo"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {langString('tutorial')}
                </a>
                {'.'}
              </p>
              <div className="url-copy">
                <input type="text" value={redirectUrl} readOnly />
                <button onClick={handleCopy}>{buttonText}</button>
              </div>
            </div>
          </div>

        </div>
    )}
      
      <div>
        { 
        ! isPermitted && (
          isCredentialLoaded ? (
            <div className="mhub-section-pannel google-meet-credentainal-pannel">
              <div className="credential-status">
                <div className="top-content"> 
                  <h3>{langString('app_not_permitted') }</h3>
                  <p>{langString('grant_access_instruction')  }</p>
                </div>
                <div className="middle-content"> 
                  <div className="img-wrapper"> 
                    <img src={` ${mhubMeetingsData.assets_path}/img/google-calender-icon.svg`}></img>
                  </div>
                  <h3>{langString('google_calendar')}</h3>
                </div>
                <div className="bottom-content"> 
                  <a 
                      className="mhub-consent-screen-btn"
                      role="button" 
                      tabIndex="0" 
                      href={link}       
                  >
                      {langString('go_to_consent_screen') }
                  </a>
              
                  <a 
                      className="mhub-reset-credential-btn"
                      onClick={onResetCredential} 
                      role="button" 
                      style={{ cursor: 'pointer' }} 
                      tabIndex="0" 
                      onKeyPress={(e) => e.key === 'Enter' && onResetCredential()}
                  >
                      {langString('reset_credential') }
                  </a>
                </div>
              </div>
            </div>
          ) : (
            <div className="mhub-section-pannel">
              <div className="upload-section">
                <div 
                  className="drop-zone" 
                  onDrop={handleDrop} 
                  onDragOver={(e) => e.preventDefault()}
                  aria-label={langString('drag_drop_json') }
                >

                  <div className="mhub-round-box"> 
                    <svg xmlns="http://www.w3.org/2000/svg" width="50px" version="1.2" viewBox="0 0 512 512" id="json-file">
                      <path fill="#FFF" d="M422.3 477.9c0 7.6-6.2 13.8-13.8 13.8h-305c-7.6 0-13.8-6.2-13.8-13.8V34.1c0-7.6 6.2-13.8 13.8-13.8h230.1V109h88.7v368.9z"></path>
                      <path fill="#2B669F" d="M333.6 6H103.5C88 6 75.4 18.6 75.4 34.1v443.8c0 15.5 12.6 28.1 28.1 28.1h305c15.5 0 28.1-12.6 28.1-28.1V109.1L333.6 6zm88.7 471.9c0 7.6-6.2 13.8-13.8 13.8h-305c-7.6 0-13.8-6.2-13.8-13.8V34.1c0-7.6 6.2-13.8 13.8-13.8h230.1V109h88.7v368.9z"></path>
                      <path fill="#084272" d="M333.6 6v103.1h103zM465.9 450.8H46.1V308c0-9.8 7.9-17.7 17.7-17.7h384.3c9.8 0 17.7 7.9 17.7 17.7v142.8z"></path>
                      <path fill="#1A252D" d="M436.6 450.8v19.5l29.3-19.5zM75.4 450.8v19.5l-29.3-19.5z"></path>
                      <path fill="#2B669F" d="M64.1 308.4h383.7v124.5H64.1z"></path>
                      <g fill="#2B669F">
                        <path d="M298.3 78.6h-177a6.7 6.7 0 0 1 0-13.4h177a6.7 6.7 0 0 1 0 13.4zM298.3 110.6h-177a6.7 6.7 0 0 1 0-13.4h177a6.7 6.7 0 0 1 0 13.4zM391.8 142.5H121.3a6.7 6.7 0 0 1 0-13.4h270.5a6.7 6.7 0 0 1 0 13.4zM391.8 174.5H121.3a6.7 6.7 0 0 1 0-13.4h270.5a6.7 6.7 0 0 1 0 13.4zM391.8 206.5H121.3a6.7 6.7 0 0 1 0-13.4h270.5a6.7 6.7 0 0 1 0 13.4zM391.8 238.4H121.3a6.7 6.7 0 0 1 0-13.4h270.5a6.7 6.7 0 0 1 0 13.4zM391.8 270.4H121.3a6.7 6.7 0 0 1 0-13.4h270.5a6.7 6.7 0 0 1 0 13.4z"></path>
                      </g>
                      <g fill="#FFF">
                        <path d="M191.6 349.7v43.9c0 5.4-1.4 9.6-4.3 12.5-2.9 2.9-6.9 4.4-12.1 4.4-1.2 0-2.2-.1-3.2-.2s-2-.3-3.1-.5l.6-10.2c.8.2 1.4.3 2 .4s1.2.1 2.1.1c1.5 0 2.6-.5 3.4-1.6.8-1.1 1.2-2.7 1.2-4.8v-43.9h13.4zm-.3-10h-13.6v-9.1h13.6v9.1zM227.5 380.9c0-1.1-.6-2-1.8-2.9-1.2-.8-3.4-1.6-6.6-2.3-5-1-8.9-2.5-11.4-4.6-2.6-2.1-3.8-4.9-3.8-8.6 0-3.8 1.6-7.1 4.8-9.7 3.2-2.7 7.5-4 13-4 5.8 0 10.4 1.3 13.8 3.9 3.4 2.6 5 5.9 4.9 10l-.1.2h-13.1c0-1.7-.4-3-1.3-3.9-.9-.9-2.3-1.3-4.2-1.3-1.4 0-2.6.4-3.6 1.2-1 .8-1.4 1.8-1.4 3 0 1.1.6 2.1 1.7 2.9 1.1.8 3.3 1.5 6.7 2.2 5.3 1 9.2 2.6 11.8 4.7 2.5 2.1 3.8 5 3.8 8.8 0 3.9-1.7 7.1-5.2 9.6s-8 3.8-13.7 3.8c-5.9 0-10.6-1.5-14-4.5-3.4-3-5.1-6.3-4.9-10l.1-.2h12.3c.1 2.1.7 3.5 1.9 4.4 1.2.9 2.9 1.4 5.1 1.4 1.8 0 3.2-.4 4.1-1.1.6-.7 1.1-1.7 1.1-3zM250.8 371c0-6.5 1.8-11.8 5.5-15.9s8.8-6.1 15.4-6.1 11.8 2 15.5 6.1c3.7 4.1 5.5 9.4 5.5 15.9v.8c0 6.5-1.8 11.9-5.5 15.9-3.7 4.1-8.8 6.1-15.4 6.1s-11.8-2-15.5-6.1c-3.7-4.1-5.5-9.4-5.5-15.9v-.8zm13.6.9c0 3.6.6 6.5 1.7 8.6 1.1 2.1 3.1 3.1 5.8 3.1 2.6 0 4.5-1 5.7-3.1 1.1-2.1 1.7-4.9 1.7-8.5v-1c0-3.5-.6-6.3-1.7-8.5-1.1-2.1-3.1-3.2-5.8-3.2-2.7 0-4.6 1.1-5.7 3.2-1.1 2.1-1.7 5-1.7 8.5v.9zM317 349.7l.5 6.1c1.4-2.2 3.2-3.9 5.2-5.1 2.1-1.2 4.4-1.8 7-1.8 4.2 0 7.6 1.4 10 4.3 2.4 2.9 3.6 7.5 3.6 13.7V393h-13.5v-26c0-2.8-.4-4.7-1.3-5.9-.9-1.2-2.2-1.7-3.8-1.7-1.4 0-2.7.3-3.9.8-1.1.5-2.1 1.2-2.9 2.2v30.8h-13.5v-43.3H317z"></path>
                      </g>
                    </svg>
                  </div>
                  <div className="upload-instruction">
                    {langString('drag_drop_json_short') }
                  </div>
                  <label className="file-upload-label">
                    <input type="file" accept=".json" onChange={handleFileInputChange} style={{ display: "none" }} />
                    <span className="button-style">{langString('choose_file') }</span>
                  </label>
                  {fileName && <div className="file-info">{langString('file_attached') } {fileName}</div>}
                  {isUploading && <Spinner />}
                  {error && <div className="error-message">{error}</div>}
                </div>
              </div>
            </div>
          )
        )
      }
      </div>

      {
        isPermitted && (
          <div className="mhub-section-pannel">
            <div className="mhub-meet-connected-wrapper">
              <div className="connected-left">
                <h3>{langString('meet_account_activated')}</h3>
                <p>{langString('meet_connected')}</p>

              </div>
              <div className="connected-right">
                
                <a 
                    className="mhub-reset-credential-btn"
                    onClick={onResetCredential} 
                    role="button" 
                    style={{ cursor: 'pointer' }} 
                    tabIndex="0" 
                    onKeyPress={(e) => e.key === 'Enter' && onResetCredential()}
                >
                    {langString('reset_credential') }
                </a>

                <a 
                    className="mhub-change-account-btn"
                    role="button" 
                    tabIndex="0" 
                    href={link}       
                >
                    {langString('change_account') }
                </a>
              </div>
            </div>
          </div>
        )
      }
  </div>
  );
};

export default ConnectTab;
