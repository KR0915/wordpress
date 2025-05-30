// MhInput.js
import React from 'react';
const { __ } = wp.i18n;
import { useMhubAdmin } from "../../../App/MhubAdminContext";
import { langString } from '../../../Helpers';

const MhInput = ({ label, description, type, value, onChange, name, required, maxLength, disabled, isLocked }) => {
  const { openProModal } = useMhubAdmin();
  return (
    <div className="mhub-form-group">
      <label>
        {label}
        <small className="description">
          {description}
        </small>
      </label>
      <div className="input-wrapper">
        <input
          type={type}
          className={`form-control ${isLocked ? 'mhub-locked' : ''}`}
          name={name}
          value={value}
          {...(required === "yes" && { required: true })}
          {...(maxLength && { maxLength: maxLength })}
          onChange={(e) => {
            if (maxLength && e.target.value.length > maxLength) {
              return;  // Prevent input if maxLength is exceeded
            }
            onChange(name, e.target.value);
          }}
         disabled={disabled} // Apply the disabled prop here
        />
  
        { disabled && ( <div className="mhub_disabled" onClick={openProModal}></div>)}
       
        {isLocked ? (<span className="mhub-pro-tag input-pro" onClick={openProModal}>{langString('pro')}</span>) : ''}
    
      </div>
    </div>
  );
};

export default MhInput;
