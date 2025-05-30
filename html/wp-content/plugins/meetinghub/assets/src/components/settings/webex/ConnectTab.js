import React, { useEffect, useState } from "react";
import '../../../scss/settings/connect-tab.scss';
import Spinner from '../../common/Spinner';
import PassInputField from "../common-fields/PassInputField";
const { __ } = wp.i18n;
import { langString } from "../../../Helpers";

const ConnectTab = ({ formData, setFormData }) => {
  const [authUrl, setAuthUrl] = useState('');
  const [loading, setLoading] = useState(false);
  const [accessToken, setAccessToken] = useState('');

  const handleInputChange = (field, value) => {
    setFormData(prevState => ({
      ...prevState,
      [field]: value
    }));
  };

  useEffect(() => {
    fetchAccessToken();
  }, []);

  const fetchAccessToken = () => {
    setLoading(true);
    fetch('/wp-json/meetinghub/v1/webex/fetch-token')
      .then(response => {
        if (!response.ok) {
          throw new Error( langString('failed_fetch_data') );
        }
        return response.json();
      })
      .then(data => {
        setAccessToken(data.access_token);
        setLoading(false);
      })
      .catch(error => {
        console.error( langString('error_fetch_token'), error);
        setLoading(false);
      });
  };

  const fetchAuthUrl = () => {
    setLoading(true);
    fetch('/wp-json/meetinghub/v1/webex/auth-url')
      .then(response => response.json())
      .then(data => {
        setAuthUrl(data.auth_url);
        setLoading(false);
        // Redirect to the fetched URL in the current tab
        window.location.href = data.auth_url;
      })
      .catch(error => {
        console.error( langString('error_fetch_auth_url') , error);
        setLoading(false);
      });
  };

  const disconnectWebex = () => {
    setLoading(true);
    fetch('/wp-json/meetinghub/v1/webex/revoke-access-token')
      .then(response => response.json())
      .then(data => {
        setLoading(false);
        //Redirect to the fetched URL in the current tab
        window.location.href = data.disconnect_url + '&revoke=true';
      })
      .catch(error => {
        console.error( langString('error_fetch_auth_url') , error);
        setLoading(false);
      });
  };


  const { client_id, client_secret } = formData;

  return (
    <div className="meetinghub-connect-tab-content">
      <div className="accordion" id="meetinghub-webex-credentials">
        <div className="webex-credentials-info">
          <h3 className="title">{langString('webex_oauth_credentials') }</h3>
            <p className="description">
              {langString('webex_oauth_setup_hint') }{' '}
              <a
                href="https://developer.webex.com/"
                target="_blank"
                rel="noreferrer noopener"
              >
                {langString('webex_developer_portal') }
              </a>
              . {langString('watch_our') }{' '}
              <a
                href="https://youtu.be/vV3EwUNJusk"
                target="_blank"
                rel="noreferrer noopener"
              >
                {langString('tutorial_video') }
              </a>{' '}
              {langString('for_guidance') }
            </p>
        </div>

        {client_id && client_secret && (
          <div className="webx-btn-wrapper">
            {accessToken.hasOwnProperty("access_token") &&
            accessToken.access_token.trim() !== "" ? (
              <a className="mhub-connect-webex" onClick={disconnectWebex}>
               {langString('disconnect_webex') }
              </a>
            ) : (
              <a className="mhub-connect-webex" onClick={fetchAuthUrl}>
               {langString('connect_webex')}
              </a>
            )}
          </div>
        )}

        <div className="mhub-section-pannel">
          {/* Client ID */}
          <PassInputField
            label={langString('client_id') }
            type="password"
            id="mhub_webex_client_id"
            name="mhub_webex_client_id"
            value={client_id}
            onChange={(value) =>
              handleInputChange('client_id', value)
            }
          />

          {/* Client Secret */}
          <PassInputField
            label={langString('client_secret') }
            type="password"
            id="mhub_webex_client_secret"
            name="mhub_webex_client_secret"
            value={client_secret}
            onChange={(value) =>
              handleInputChange('client_secret', value)
            }
          />
        </div>
  
      </div>
    </div>
  );
};

export default ConnectTab;
