// ConnectTab.js
import React, { useState } from 'react';
import '../../../scss/settings/connect-tab.scss';
import Spinner from '../../common/Spinner';
import PassInputField from '../common-fields/PassInputField';
const { __ } = wp.i18n;
import { toast } from 'react-toastify';
import { langString } from '../../../Helpers';

const ConnectTab = ({ formData, setFormData }) => {

  const { oauth_account_id, oauth_client_id, oauth_client_secret, sdk_client_id, sdk_client_secret } = formData;
  const [renewText, setRenewText] = useState( langString('renew_oauth_token') );

  const handleInputChange = (field, value) => {
    setFormData(prevState => ({
      ...prevState,
      [field]: value
    }));
  };

  const handleRenewOAuth = async () => {
    setRenewText( langString('renewing') );
  
    try {
      const response = await fetch(mhubMeetingsData.ajax_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'mhub_renew_zoom_oauth',
          security: mhubMeetingsData.nonce,
        }),
      });
  
      const result = await response.json();
      
      if (result.success) {
        toast.success(langString('oauth_token_renewed') );
      } else {
        toast.error(result.message || langString('renew_failed_check_credentials') );
      }
    } catch (error) {
      toast.error(langString('something_went_wrong') );
    }
  
    setRenewText(langString('renew_oauth_token'));
  };
  
  return (
    <div className="meetinghub-connect-tab-content">
        {/* Server to Server Oauth Credentials */}
        <div className="accordion" id="meetinghub_s2sOauth-credentials">
          <div className="server-auth-credentials-info">
              <h3 className="title">
                {__('Server to Server OAuth Credentials', 'meetinghub')}
              </h3>
              <p className="description">
                {langString('server_oauth_setup_hint')}{' '}
                <a
                  href="https://marketplace.zoom.us/"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  {langString('zoom_developer_portal')}
                </a>
                {'. '}
                {langString('watch_our')}{' '}
                <a
                  href="https://youtu.be/ApSm4QJXLGc"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  {langString('tutorial_video')}
                </a>
                {' '}
                {langString('for_guidance')}
              </p>
          </div>

          <div className="mhub-section-pannel mhub-zoon-connect-pannel">
            {/* Oauth Account ID */}
            <PassInputField
              label={langString('oauth_account_id')}
              type="password"
              id="meetinghub_oauth_account_id"
              name="oauth_account_id"
              value={oauth_account_id}
              onChange={(value) =>
                handleInputChange('oauth_account_id', value)
              }
            />

            {/* Oauth Client ID */}
            <PassInputField
              label={langString('oauth_client_id') }
              type="password"
              id="meetinghub_oauth_client_id"
              name="oauth_client_id"
              value={oauth_client_id}
              onChange={(value) =>
                 handleInputChange('oauth_client_id', value)
              }
            />

            {/* Oauth Client Secret */}
            <PassInputField
              label={langString('oauth_client_secret') }
              type="password"
              id="meetinghub_oauth_client_secret"
              name="oauth_client_secret"
              value={oauth_client_secret}
              onChange={(value) =>
               handleInputChange('oauth_client_secret', value)
              }
            />
            
            <span className="mhub-renew-oauth-button" onClick={handleRenewOAuth}> {renewText} </span>
          </div>         
        </div>

        {/* Meeting SDK App Credentials */}
        <div
          className="accordion"
          id="meetinghub_s2sOauth-app-sdk-credentials"
        >
         <div className="server-auth-credentials-info">
            <h3 className="title">{langString('app_credentials')}</h3>
            <p className="description">
              {langString('sdk_credentials_instruction')}{' '}
              <a
                href="https://marketplace.zoom.us/"
                target="_blank"
                rel="noreferrer noopener"
              >
                {langString('zoom_developer_portal')}
              </a>
              {'. '}
              {langString('view_our')}{' '}
              <a
                href="https://youtu.be/Q0Zt80PjvTE"
                target="_blank"
                rel="noreferrer noopener"
              >
                {langString('video_tutorial')}
              </a>
              {' '}
              {langString('for_assistance')}
            </p>
         </div>

          <div className="mhub-section-pannel">
            {/* SDK Client ID */}
            <PassInputField
              label={langString('client_id') }
              type="password"
              id="meetinghub_sdk_key"
              name="sdk_client_id"
              value={sdk_client_id}
              onChange={(value) =>
                handleInputChange('sdk_client_id', value)
              }
            />

            {/* SDK Client Secret */}
            <PassInputField
              label={langString('client_secret') }
              type="password"
              id="meetinghub_sdk_secret_key"
              name="sdk_client_secret"
              value={sdk_client_secret}
              onChange={(value) =>
                handleInputChange('sdk_client_secret', value)
              }
            />
          </div>
        </div>

    </div>
  );
};

export default ConnectTab;
