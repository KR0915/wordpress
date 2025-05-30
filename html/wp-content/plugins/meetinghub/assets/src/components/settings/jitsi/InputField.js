import React from 'react';
import { Tooltip } from 'react-tooltip'
import 'react-tooltip/dist/react-tooltip.css'

const InputField = ({ label, type, id, value, onChange, disabled, placeholder, tooltip }) => {
  return (
    <div className="input-container">
      <div className="input-field">
        <label htmlFor={id}>{label}  
          <a className="mhub-info" data-tooltip-id={id} data-tooltip-content={tooltip}>𝒊</a>
        </label>
        <Tooltip id={id} place="right" type="info" effect="float" style={{ fontSize: '14px', width: '400px' }} />
        <input
          type={type}
          name={id}
          id={id}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          disabled={disabled}
          placeholder={placeholder}
          className={`input-field ${disabled ? 'disabled' : ''}`}
        />
      </div>
    </div>
  );
};

export default InputField;
