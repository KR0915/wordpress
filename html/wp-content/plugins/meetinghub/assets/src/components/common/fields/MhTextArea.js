const { __ } = wp.i18n;

const MhTextArea = ({ label, description, value, onChange, name}) => {
	return (
		<div className="mhub-form-group">
			<label>
				{label}
				<small className="description">
					{description}
				</small>
			</label>
			<div className="input-wrapper">
				<textarea
					className="form-control"
					name={name}
					value={value}
					onChange={(e) => onChange(name, e.target.value)}
				/>
			</div>
		</div>
	);
};

export default MhTextArea;
