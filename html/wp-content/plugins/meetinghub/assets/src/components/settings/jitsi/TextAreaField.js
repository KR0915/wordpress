import React from "react";
import { Tooltip } from 'react-tooltip';
import 'react-tooltip/dist/react-tooltip.css';

const TextAreaField = ({ label, id, value, onChange, rows, tooltip }) => {
  return (
    <div className="textarea-field">
      <label htmlFor={id}>{label}
        <a className="mhub-info" data-tooltip-id={id} data-tooltip-content={tooltip}>𝒊</a>
      </label>
      <Tooltip id={id} place="right" type="info" effect="float" style={{ fontSize: '14px', width: '400px' }} />
      <textarea
        id={id}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        rows={rows}
      />
    </div>
  );
};

export default TextAreaField;
