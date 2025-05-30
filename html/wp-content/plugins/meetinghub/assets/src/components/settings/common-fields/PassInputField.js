import React, { useState } from 'react';
import { Tooltip } from 'react-tooltip';
import 'react-tooltip/dist/react-tooltip.css';
import { langString } from '../../../Helpers';

const { __ } = wp.i18n;

const PassInputField = ({ label, type, id, value, onChange, name, tooltip }) => {
  const [isPasswordVisible, setIsPasswordVisible] = useState(false);

  const togglePasswordVisibility = () => {
    setIsPasswordVisible(!isPasswordVisible);
  };

  const handleToggleClick = (e) => {
    e.preventDefault();
    e.stopPropagation(); // Prevent click event from reaching parent
    togglePasswordVisibility();
  };

  return (
    <div className="input-container">
      <div className="input-field">
        <label htmlFor={id}>
          {label}
          {tooltip && (
            <a className="mhub-info" data-tooltip-id={id} data-tooltip-content={tooltip}>
              𝒊
            </a>
          )}
        </label>
        {tooltip && (
          <Tooltip id={id} place="right" type="info" effect="float" style={{ fontSize: '14px', width: '400px' }} />
        )}
        <input
          type={isPasswordVisible ? 'text' : 'password'}
          name={name}
          id={id}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          className="text-input"
        />
        <a href="#" className="toggle-trigger" onClick={handleToggleClick}>
          {isPasswordVisible ? langString('hide')  : langString('show') }
        </a>
      </div>
    </div>
  );
};

export default PassInputField;
