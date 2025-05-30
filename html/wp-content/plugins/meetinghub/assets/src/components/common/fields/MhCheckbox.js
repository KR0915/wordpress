// MhCheckbox.js
import React from 'react';

const MhCheckbox = ({ label, description, options, onChange }) => {
    const handleCheckboxChange = (optionIndex) => {
        const updatedOptions = options.map((option, index) => {
            if (index === optionIndex) {
                // Toggle the checked state of the clicked checkbox
                return { ...option, checked: !option.checked };
            }
            return option;
        });
        onChange(updatedOptions); // Pass all options to the onChange function
    };

    return (
        <div className="mhub-form-group">
            <label>
                {label}
                <small className="description">
                    {description}
                </small>
            </label>
            <div className="multi-checkbox-wrapper">
                {options.map((option, index) => (
                    <div key={index} className='single-checkbox'>
                        <input
                            id={`checkbox-${index}`}
                            type="checkbox"
                            checked={option.checked}
                            onChange={() => handleCheckboxChange(index)}
                        />
                        <label htmlFor={`checkbox-${index}`}>{option.label}</label>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default MhCheckbox;
