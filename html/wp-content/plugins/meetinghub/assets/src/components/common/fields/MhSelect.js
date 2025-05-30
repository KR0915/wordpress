// MhSelect.js
import React from 'react';
const { __ } = wp.i18n;
import { useMhubAdmin } from "../../../App/MhubAdminContext";
import { langString } from '../../../Helpers';


const MhSelect = ({ label, description, options, value, onChange, name, disabled, isLocked }) => {
  const { openProModal } = useMhubAdmin();

  return (
    <div className="mhub-form-group">
      <label>
        {label}
        {description && <small className="description">{description}</small>}
      </label>
      <div className="input-wrapper">
        <select value={value} onChange={(e) => onChange(name, e.target.value)} disabled={disabled} className={`${isLocked ? 'mhub-locked' : ''}`}> 
          {options.map(
            (option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            )
          )}
        </select>
      
        { disabled && ( <div className="mhub_disabled" onClick={openProModal}></div>)}

        {name === 'host_id' && (
            <p className="mhub-field_right_dec">{langString('note_no_hosts')}</p>
        )}

        {name === 'meeting_type' && (
            <p className="mhub-field_right_dec">{langString('note_webinar_plan')}</p>
        )}

        {name === 'zoom_user_action' && (
           <div className="mhub-hint-wrapper">
            <ol>
                <li className="hint">
                    <strong>{ langString('create') }</strong> - {langString('zoom_email_info') }
                </li>

                <li className="hint">
                    <strong>{langString('automated_create') }</strong> - {langString('automated_create_note')}
                </li>

                <li className="hint">
                    <strong>{langString('custom_create')}</strong> - {langString('custom_create_note') }
                </li>

                <li className="hint">
                    <strong>{langString('sso_create') }</strong> - {langString('sso_create_note') }
                </li>
            </ol>
          </div>
        )}
        
        {isLocked ? (<span className="mhub-pro-tag select-pro" onClick={openProModal}>{langString('pro') }</span>) : ''}
      </div>
    </div>
  );
};

export default MhSelect;
