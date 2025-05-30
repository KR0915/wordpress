import React, { useState, useRef } from 'react';
import { useNavigate } from "react-router-dom";
import { toast } from 'react-toastify';
import MhInput from '../../common/fields/MhInput';
import MhSelect from '../../common/fields/MhSelect';
const { __ } = wp.i18n;
import { langString } from '../../../Helpers';

const zoomUserType = [
	{ value: 1, label: langString('basic_user') },
	{ value: 2, label: langString('pro_user') },
  ];
  

const zoomActionType = [
{ value: 'create', label: langString('create') },
{ value: 'autoCreate', label: langString('auto_create') },
{ value: 'custCreate', label: langString('cust_create')},
{ value: 'ssoCreate', label: langString('sso_create') },
];
  
const ZuUserForm = () => {
	const [isSaving, setIsSaving] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const navigate = useNavigate();

	const handleBack = () => {
		navigate('/');
	};

	const [formData, setFormData] = useState({
		email: '',
		first_name: '',
		last_name: '',
		type: 1,
		zoom_user_action: 'create',
	});


	const handleChange = (name, value) => {
		setFormData({ ...formData, [name]: value });
	};

	const handleSubmit = async (e) => {
		e.preventDefault();

		// Disable the button
		setIsSaving(true);

		try {
			//Make an API request using wp.apiFetch
			const response = await wp.apiFetch({
				path: 'meetinghub/v2/zoom/users',
				method: 'POST',
				data: {
					email: formData.email,
					first_name: formData.first_name,
					last_name: formData.last_name,
					type: formData.type,
					zoom_user_action: formData.zoom_user_action,
				},
			});


			if (response && (response.code || response.message)) {
				toast.error(langString('failed_create_user'));
				if (response.code === 201) {
					// Reset error message
					setErrorMessage('');
					navigate('/');
				} else if (response.message && response.message !== 'No privilege.') {
					// Error message from response
					setErrorMessage(response.message);
				} else {
					// Other error
					setErrorMessage(langString('error'));
				}

				if (response.message === 'No privilege.') {
					// No privilege error
					setErrorMessage(langString('no_permission_user') );
				}
			} else {
				toast.success(langString('user_created') );
				navigate('/');
			}

		} catch (error) {
			// Handle errors
			console.error(langString('api_error') , error);
		} finally {
			// Enable the button after API request is complete (success or error)
			setIsSaving(false);
		}
	};

	const handleCloseError = () => {
        setErrorMessage('');
    };

	return (
		<div className="meeting-wrapper">
			<button className='back-btn' onClick={handleBack}><span className="dashicons dashicons-arrow-left-alt"></span>{
			langString('back') }</button>
			<h2 className='title'>{langString('add_user_short')}</h2>
			<p className='mhub-zoom-user-dec'>
			{langString('what_does_this')} {' '}
			<a href="https://support.zoom.us/hc/en-us/articles/201363183-Managing-users" target="_blank" rel="noreferrer noopener">
				{langString('zoom_website')}
			</a>. {' '}
			{langString('pro_account_note') }
			</p>


			{errorMessage && (
				<div className="mhub_zoom_error error">
					<h3>{errorMessage}</h3>
					<span className="close-icon" onClick={handleCloseError}>✕</span>
				</div>
            )}

			<div className="zoom-user-form">
				<div className="form-wrapper">
					<form className="form" onSubmit={handleSubmit} >

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('action') }
								description={langString('type_action') }
								options={zoomActionType}
								value={formData.zoom_user_action}
								onChange={(name, value) => handleChange(name, value)}
								name="zoom_user_action"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhInput
								label={langString('email_address') }
								description={langString('email_note') }
								type="email"
								value={formData.email}
								onChange={(name, value) => handleChange(name, value)}
								name="email"
								required="yes"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhInput
								label={langString('first_name') }
								description={langString('first_name_note')}
								type="text"
								value={formData.first_name}
								onChange={(name, value) => handleChange(name, value)}
								name="first_name"
								required="yes"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhInput
								label={langString('last_name') }
								description={langString('last_name_note') }
								type="text"
								value={formData.last_name}
								onChange={(name, value) => handleChange(name, value)}
								name="last_name"
								required="yes"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('user_type')}
								description={ langString('user_type_note') }
								options={zoomUserType}
								value={formData.type}
								onChange={(name, value) => handleChange(name, value)}
								name="type"
							/>
						</div>

						<div className="mhub-form-actions">
							<button type="submit" className="save-meeting" disabled={isSaving}>
								{langString('create_user')}
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	);
};

export default ZuUserForm;
