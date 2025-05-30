import React from "react";
import InputField from "./InputField";
import TextAreaField from "./TextAreaField";
import { isProActive } from "../../../Helpers";
import { Tooltip } from 'react-tooltip'
import 'react-tooltip/dist/react-tooltip.css'
const { __ } = wp.i18n;
import PassInputField from "../common-fields/PassInputField";
import { langString } from "../../../Helpers";

const ApiSettingsTab = ({ formData, setFormData }) => {
  const handleDomainChange = (event) => {
    setFormData({
      ...formData,
      domain_type: event.target.value
    });
  };

  const handleInputChange = (field, value) => {
    setFormData(prevState => ({
      ...prevState,
      [field]: value
    }));
  };

  const { domain_type, custom_domain, app_id, api_key, private_key } = formData;

  return (
    <div className="api-settings-tab">
      <div className="jitsi-section-pannel">
        <div className="select-domain-title">
          <h3>{langString('select_hosted_domain') }</h3>
        </div>
        <div className="select-domain-description">
          <p>{langString('select_hosted_domain_hint')}</p>
        </div>

        <div className="radio-field">
          <input
            type="radio"
            id="jitsi_random_public"
            value="jitsi_random_public"
            checked={domain_type === "jitsi_random_public"}
            onChange={handleDomainChange}
          />
          <label htmlFor="jitsi_random_public">
            {langString('random_public_domain') }
            <a className="mhub-info" data-tooltip-id="jitsi_random_public" data-tooltip-content={langString('random_public_domain_hint') }>𝒊</a>
          </label>

          <Tooltip id="jitsi_random_public" place="right" type="info" effect="float" style={{ fontSize: '14px', width: '400px' }} />
        </div>

        <div className={`radio-field`}>
          <input
            type="radio"
            id="jitsi_jass_premium"
            value="jitsi_jass_premium"
            checked={domain_type === "jitsi_jass_premium"}
            onChange={handleDomainChange}
          />
         <label htmlFor="jitsi_jass_premium">
            {langString('jaas_8x8') }
            <a className="mhub-info" data-tooltip-id="jitsi_jass_premium" data-tooltip-content={langString('jaas_8x8_hint') }>𝒊</a>
          </label>

          <Tooltip id="jitsi_jass_premium" place="right" type="info" effect="float" style={{ fontSize: '14px', width: '400px' }} />
        </div>

        <div className={`radio-field`}>
          <input
            type="radio"
            id="jitsi_self_hosted"
            value="jitsi_self_hosted"
            checked={domain_type === "jitsi_self_hosted"}
            onChange={handleDomainChange}
          />
         <label htmlFor="jitsi_self_hosted">
            {langString('use_own_domain') }
            <a className="mhub-info" data-tooltip-id="jitsi_self_hosted" data-tooltip-content={langString('custom_domain_info') }>𝒊</a>
          </label>

          <Tooltip id="jitsi_self_hosted" place="right" type="info" effect="float" style={{ fontSize: '14px', width: '400px' }} />

        </div>

        {domain_type === "jitsi_self_hosted" && (
          <div className={`sefl-hosted-wrapper`}>
             <p className="description">
                {langString('any_valid_domain_note') }{' '}
                <a
                  href="https://jitsi.github.io/handbook/docs/devops-guide/"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  {langString('selfhosted_server') }
                </a>{' '}
                {langString('follow_guidance') }
              </p>

            <div className={`field-wrapper`}>
              <InputField
                label={langString('hosted_domain') }
                type="text"
                id="meetinghub_hosted_domain"
                value={custom_domain}
                onChange={(value) => handleInputChange("custom_domain", value)}
                placeholder={langString('example_8x8') }
                tooltip={langString('selfhosted_or_other_domain') }
              />
            </div>
          </div>
        )}

        {domain_type === "jitsi_jass_premium" && (
        <div className={`jass-wrapper`}>
          <p className="description">
              {langString('jaas_setup_note') }{' '}
              <a
                href="https://jaas.8x8.vc/#/"
                target="_blank"
                rel="noreferrer noopener"
              >
                {langString('login_jaas_account') }
              </a>
              . {langString('watch_our') }{' '}
              <a
                href="https://youtu.be/YqQ7Kcap5vo"
                target="_blank"
                rel="noreferrer noopener"
              >
                {langString('tutorial_video') }
              </a>{' '}
              {langString('for_guidance') }
            </p>

          <div className={`field-wrapper`}>
              <PassInputField
                label={langString('app_id') }
                type="password"
                id="jass_app_id"
                name="app_id"
                value={app_id}
                onChange={(value) => handleInputChange("app_id", value)}
                tooltip={langString('retrieve_the_App') }
              />

              <PassInputField
                label={langString('api_key') }
                type="password"
                id="jass_api_key"
                name="api_key"
                value={api_key}
                onChange={(value) => handleInputChange("api_key", value)}
                tooltip={langString('obtain_api_key') }
              />

              <TextAreaField
                label={langString('private_key') }
                id="jass_private_key"
                value={private_key}
                onChange={(value) => handleInputChange("private_key", value)}
                rows='6'
                tooltip={langString('generate_private_key') }
              />

          </div>
        </div>
        )}

      </div>
    </div>
  );
};

export default ApiSettingsTab;