// InputField.js
import React, { useState } from 'react';
import { langString } from '../../../Helpers';

const InputField = ({ label, type, id, value, onChange }) => {
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
        <label htmlFor={id}>{label}</label>
        <input
          type={isPasswordVisible ? 'text' : 'password'}
          name={id}
          id={id}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          className="text-input"
        />
        <a href="#" className="toggle-trigger" onClick={handleToggleClick}>
          {isPasswordVisible ?  langString('hide') : langString('show') }
        </a>
      </div>
    </div>
  );
};

export default InputField;
