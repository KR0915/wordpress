const Input = ({ label, description, type, value, onChange, name, required, maxLength }) => {
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
          className="form-control"
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
        />
      </div>
    </div>
  );
};

export default Input;
