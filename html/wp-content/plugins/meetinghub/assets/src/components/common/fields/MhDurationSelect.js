import React from 'react';
const { __ } = wp.i18n;
import { langString } from '../../../Helpers';

const MhDurationSelect = ({ label, description, hours, minutes, onChangeHours, onChangeMinutes }) => {
    // Determine the label based on the selected value
    const hourLabel = hours >= 0 && hours <= 1 ? langString('hour')  :  langString('hours');
    const minuteLabel = minutes >= 0 && minutes <= 1 ? langString('minute') : langString('minutes');

    return (
        <div className="mhub-form-group">
            <label>
                {label}
                {description && <small className="description">{description}</small>}
            </label>
            <div className="input-wrapper duration-select">
                <select value={hours} onChange={(e) => onChangeHours(e.target.value)}>
                    {/* Options for hours */}
                    {[...Array(25).keys()].map(hour => (
                        <option key={hour} value={hour}>{hour}</option>
                    ))}
                </select>
                <span className="select-right-label right-space">{hourLabel}</span>
                <select value={minutes} onChange={(e) => onChangeMinutes(e.target.value)}>
                    {/* Options for minutes */}
                    {[0, 15, 30, 40].map(minute => (
                        <option key={minute} value={minute}>{minute}</option>
                    ))}
                </select>
                <span className="select-right-label">{minuteLabel}</span>
            </div>
        </div>
    );
};

export default MhDurationSelect;
