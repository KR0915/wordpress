
const MhSwitcher = ({ label, description, checked, onChange, name }) => {
	return (
		<div className="mhub-form-group">
			<label>
				{label}
				{description && <small className="description">{description}</small>}
			</label>
			<div className="input-wrapper">
				<div className=".mhub-switch-field">
					<input type="checkbox" id={name} checked={checked} onChange={() => onChange(name, !checked)} />
					<label htmlFor={name}></label>
				</div>
			</div>
		</div>
	);
};

export default MhSwitcher;
