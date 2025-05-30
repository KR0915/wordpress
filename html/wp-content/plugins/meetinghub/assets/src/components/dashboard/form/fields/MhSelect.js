
const MhSelect = ({ label, description, options, value, onChange, name }) => {
	return (
		<div className="mhub-form-group">
			<label>
				{label}
				{description && <small className="description">{description}</small>}
			</label>
			<div className="input-wrapper">
				<select value={value} onChange={(e) => onChange(name, e.target.value)}>
					{options.map(
						(option) => (
							<option key={option.value} value={option.value}>
								{option.label}
							</option>
						)
					)}
				</select>
			</div>
		</div>
	);
};

export default MhSelect;
