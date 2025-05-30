import React, { useEffect, useState, useRef } from 'react';
import Datetime from 'react-datetime';
import 'react-datetime/css/react-datetime.css';
import { useNavigate } from 'react-router-dom';
import Spinner from '../../../common/Spinner';
import MhInput from '../../../common/fields/MhInput';
import MhSelect from '../../../common/fields/MhSelect';
import MhSwitcher from '../../../common/fields/MhSwitcher';
import TimezoneList from '../../../common/fields/TimezoneList';
import MhDurationSelect from '../../../common/fields/MhDurationSelect';
import MhTextArea from '../../../common/fields/MhTextArea';
import Editor from 'react-simple-wysiwyg';
import { isProActive, langString } from '../../../../Helpers';
import { toast } from 'react-toastify';
const { __ } = wp.i18n;
import ImageUploader from '../../../common/ImageUploader';
import moment from 'moment-timezone';

const WebexEditForm = ({ meetingId, meetingDetails }) => {
	const navigate = useNavigate();
	const [formData, setFormData] = useState({});
	const [errorMessage, setErrorMessage] = useState('');
	const [loading, setLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const hiddenSubmitRef = useRef(null);
	const [imageUrl, setImageUrl] = useState('');
	const [imageId, setImageID] = useState('');

	const [meetingDescription, setMeetingDescription] = useState('');

	function handleMeetingDescription(e) {
		setMeetingDescription(e.target.value);
	}

	//Lock data
	const lockOptions = [
		{ value: '0', label: __('0', 'meetinghub') },
		{ value: '5', label: __('5', 'meetinghub') },
		{ value: '10', label: __('10', 'meetinghub') },
		{ value: '15', label: __('15', 'meetinghub') },
		{ value: '20', label: __('20', 'meetinghub') },
	];

	const formatDate = (dateString, timezone) => {
		return dateString ? moment.tz(dateString, timezone).format() : null;
	};


	useEffect(() => {
		if (meetingDetails) {
			setFormData({
				title: meetingDetails.title,
				selected_platform: meetingDetails.settings.selected_platform,
				startDateTime: formatDate(meetingDetails.settings.startDateTime, meetingDetails.settings.meeting_timezone),
				join_before_host: meetingDetails.settings.join_before_host,
				meeting_timezone: meetingDetails.settings.meeting_timezone,
				password: meetingDetails.settings.password,
				duration_hours: meetingDetails.settings.duration_hours,
				duration_minutes: meetingDetails.settings.duration_minutes,
				hide_sidebar: meetingDetails.settings.hide_sidebar,
				hide_header_footer: meetingDetails.settings.hide_header_footer,
				agenda: meetingDetails.settings.agenda,
				auto_record: meetingDetails.settings.auto_record,
				breakout_sessions: meetingDetails.settings.breakout_sessions,
				automatic_lock: meetingDetails.settings.automatic_lock,
				lock_minutes: meetingDetails.settings.lock_minutes,
				enable_should_register: meetingDetails.settings.enable_should_register,
				enable_recurring_meeting: meetingDetails.settings.enable_recurring_meeting,
				display_time_user_zone: meetingDetails.settings?.display_time_user_zone || 0,

			});

			setMeetingDescription(meetingDetails.meeting_description);
			setImageUrl(meetingDetails.settings.image_url);
			setImageID(meetingDetails.settings.image_id);
			setLoading(false);
		}
	}, [meetingDetails]);

	useEffect(() => {
		if (formData.meeting_timezone) {
			setFormData((prev) => ({
				...prev,
				startDateTime: moment.tz(prev.startDateTime, prev.meeting_timezone).tz(formData.meeting_timezone).format(),
			}));
		}
	}, [formData.meeting_timezone]);

	const handleChange = (name, value) => {
		let updatedValue = value;
		
		// Convert only `startDateTime` to the specified timezone
		if (name === 'startDateTime') {
			// Keep the same local time but assign the timezone
			updatedValue = moment.tz(value, formData.meeting_timezone).format();
		}

		setFormData({ ...formData, [name]: updatedValue });
	};

	const handleSubmit = async (e) => {
		e.preventDefault();

		// Disable the button
		setIsSaving(true);

		try {
			// Make an API request using wp.apiFetch
			const response = await wp.apiFetch({
				path: `mhub/v1/meetings/${meetingId}`,
				method: 'PUT',
				data: {
					id: meetingId,
					title: formData.title,
					selected_platform: formData.selected_platform,
					startDateTime: formData.startDateTime,
					join_before_host: formData.join_before_host,
					meeting_timezone: formData.meeting_timezone,
					duration_hours: formData.duration_hours,
					duration_minutes: formData.duration_minutes,
					hide_sidebar: formData.hide_sidebar,
					hide_header_footer: formData.hide_header_footer,
					password: formData.password,
					agenda: formData.agenda,
					auto_record: formData.auto_record,
					breakout_sessions: formData.breakout_sessions,
					automatic_lock: formData.automatic_lock,
					lock_minutes: formData.lock_minutes,
					enable_should_register: formData.enable_should_register,
					enable_recurring_meeting: formData.enable_recurring_meeting,
					meeting_description: JSON.stringify({ content: meetingDescription }),
					image_url: imageUrl,
					image_id: imageId,
					display_time_user_zone: formData.display_time_user_zone,
				},
			});

			if (response.hasOwnProperty("id")) {
				toast.success( langString('meeting_updated') );
				setErrorMessage('');
				navigate('/');
			}

			if (response && (response.errors || response.message)) {
				toast.error( langString('failed_to_update_meeting'));
				setErrorMessage(response.errors[0].description);
			}

			if (!response) {
				toast.error(langString('failed_to_update_meeting') );
				setErrorMessage(langString('check_webex_oauth') );
			}

		} catch (error) {
			// Handle errors
			console.error('API Error:', error);
		} finally {
			// Enable the button after API request is complete (success or error)
			setIsSaving(false);
		}
	};

	const Timezones = TimezoneList();

	if (loading) {
		return <Spinner />;
	}

	const handleStickySaveClick = () => {
		// Trigger form submission by calling submit() method on the form
		hiddenSubmitRef.current.click();
	};

	const handleCloseError = () => {
		setErrorMessage('');
	};


	return (
		<div>
			{errorMessage && (
				<div className="mhub_zoom_error error">
					<h3>{errorMessage}</h3>
					<span className="close-icon" onClick={handleCloseError}>✕</span>
				</div>
			)}
			<div className="mhub-zoom-meeting-form">
				{ ! mhubMeetingsData.hide_floating_update_btn  && (
					<div className='mhub-col-lg-12'>
						<div className="mhub-form-actions sticky-save-btn">
							<button type="button" className="save-meeting" disabled={isSaving} onClick={handleStickySaveClick}>
								{isSaving ? langString('updating')  : langString('update_meeting') }
							</button>
						</div>
					</div>
				)}

				<div className="form-wrapper">
					<form className="form" onSubmit={handleSubmit}>

						<div className="mhub-col-lg-12">
							<MhInput
								label={ langString('meeting_name') }
								description={langString('enter_meeting_name') }
								type="text"
								value={formData.title}
								onChange={(name, value) => handleChange(name, value)}
								name="title"
								required="yes"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<div className="mhub-form-group">
								<label>{langString('meeting_description')}<small className="description">{langString('meeting_description_help')}</small></label>
								<div className="editor-wrapper">
									<Editor
										value={meetingDescription}
										onChange={handleMeetingDescription}
										containerProps={{ style: { resize: 'both' } }}
									/>
								</div>
							</div>
						</div>

						<div className="mhub-col-lg-12">
							<ImageUploader
								imageUrl={imageUrl}
								setImageUrl={setImageUrl}
								setImageID={setImageID}
								label={ langString('meeting_thumbnail') }
								description={langString('meeting_thumbnail_upload') }
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhTextArea
								label={langString('meeting_agenda') }
								description={langString('agenda_hint')}
								value={formData.agenda}
								onChange={(name, value) => handleChange(name, value)}
								name="agenda"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<div className="mhub-form-group">
								<label>
									{langString('start_datetime') }
									<small className="description">{langString('start_datetime_help') }</small>
								</label>

								<div className="input-wrapper">
									<Datetime
										value={moment.tz(formData.startDateTime, formData.meeting_timezone)}
										onChange={(date) => handleChange('startDateTime', date)}
										isValidDate={(current) => {
											return current.isAfter(moment().subtract(1, 'day'), 'day');
										}}
									/>
								</div>
							</div>
						</div>

						<div className="mhub-col-lg-12">
							<MhDurationSelect
								label={langString('duration') }
								description={langString('select_duration') }
								hours={formData.duration_hours}
								minutes={formData.duration_minutes}
								onChangeHours={(value) => handleChange('duration_hours', value)}
								onChangeMinutes={(value) => handleChange('duration_minutes', value)}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('recurring_meeting') }
								description={langString('enable_recurring') }
								checked={formData.enable_recurring_meeting}
								onChange={(name, value) => handleChange(name, value)}
								name="enable_recurring_meeting"
								disabled={true}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('timezone') }
								description={langString('meeting_timezone') }
								options={Timezones}
								value={formData.meeting_timezone}
								onChange={(name, value) => handleChange(name, value)}
								name="meeting_timezone"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('show_user_timezone')}
								description={langString('show_user_timezone_help') }
								checked={formData.display_time_user_zone}
								onChange={(name, value) => handleChange(name, value)}
								name="display_time_user_zone"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('should_register') }
								description={langString('should_register_help')}
								checked={formData.enable_should_register}
								onChange={(name, value) => handleChange(name, value)}
								name="enable_should_register"
								disabled={!isProActive()}
								isLocked={!isProActive()}
								isProActive={isProActive()}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhInput
								label={langString('password') }
								description={langString('password_help') }
								type="text"
								value={formData.password}
								onChange={(name, value) => handleChange(name, value)}
								name="password"
								required="no"
								maxLength={10}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('join_before_host') }
								description={langString('join_before_host_hint') }
								checked={formData.join_before_host}
								onChange={(name, value) => handleChange(name, value)}
								name="join_before_host"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('auto_record') }
								description={langString('auto_record_hint') }
								checked={formData.auto_record}
								onChange={(name, value) => handleChange(name, value)}
								name="auto_record"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('enable_breakout') }
								description={langString('enable_breakout_hint') }
								checked={formData.breakout_sessions}
								onChange={(name, value) => handleChange(name, value)}
								name="breakout_sessions"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={_langString('enable_auto_lock') }
								description={langString('enable_breakout_hint') }
								checked={formData.automatic_lock}
								onChange={(name, value) => handleChange(name, value)}
								name="automatic_lock"
							/>
						</div>

						{formData.automatic_lock && (
							<div className="mhub-col-lg-12">
								<MhSelect
									label={langString('lock_after')}
									description={langString('lock_after_hint') }
									options={lockOptions}
									value={formData.lock_minutes}
									onChange={(name, value) => handleChange(name, value)}
									name="lock_minutes"
								/>
							</div>
						)}

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('hide_sidebar')}
								description={langString('hide_sidebar_help') }
								name="hide_sidebar"
								checked={formData.hide_sidebar}
								onChange={(name, value) => handleChange(name, value)}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('hide_header_footer') }
								description={langString('hide_header_footer_help') }
								name="hide_header_footer"
								checked={formData.hide_header_footer}
								onChange={(name, value) => handleChange(name, value)}
							/>
						</div>

						<button type="submit" style={{ display: 'none' }} ref={hiddenSubmitRef} />

						<div className="mhub-form-actions">
							<button type="submit" className="save-meeting" disabled={isSaving}>
								{isSaving ? langString('updating') : langString('update_meeting') }
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	);
};

export default WebexEditForm;
