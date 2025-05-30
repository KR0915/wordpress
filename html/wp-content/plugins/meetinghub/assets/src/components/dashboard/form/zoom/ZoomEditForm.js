import React, { useEffect, useState, useRef } from 'react';
import Datetime from 'react-datetime';
import 'react-datetime/css/react-datetime.css';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import { useNavigate } from 'react-router-dom';
import Spinner from '../../../common/Spinner';
import MhInput from '../../../common/fields/MhInput';
import MhSelect from '../../../common/fields/MhSelect';
import MhSwitcher from '../../../common/fields/MhSwitcher';
import MhTextArea from '../../../common/fields/MhTextArea';
import TimezoneList from '../../../common/fields/TimezoneList';
import MhCheckbox from '../../../common/fields/MhCheckbox';
import MhDurationSelect from '../../../common/fields/MhDurationSelect';
import { isProActive } from '../../../../Helpers';
import { zoomUsers } from '../../../../Helpers';
import Select from 'react-select';
import Editor from 'react-simple-wysiwyg';
import { select2Styles } from '../../../../Helpers';
import { toast } from 'react-toastify';
const { __ } = wp.i18n;
import ImageUploader from '../../../common/ImageUploader';
import moment from 'moment-timezone';
import { langString } from '../../../../Helpers';

const ZoomEditForm = ({ meetingId, meetingDetails }) => {
	const navigate = useNavigate();
	const [formData, setFormData] = useState({});
	const [errorMessage, setErrorMessage] = useState('');
	const [loading, setLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const formRef = useRef(null);
	const hiddenSubmitRef = useRef(null);

	const [meetingDescription, setMeetingDescription] = useState('');
	const [imageUrl, setImageUrl] = useState('');
	const [imageId, setImageID] = useState('');

	function handleMeetingDescription(e) {
		setMeetingDescription(e.target.value);
	}

	// Define the weekdays options
	const weekdays = [
		{ label: langString('sunday'), value: 1, checked: false },
		{ label: langString('monday'), value: 2, checked: false },
		{ label: langString('tuesday'), value: 3, checked: false },
		{ label: langString('wednesday'), value: 4, checked: false },
		{ label: langString('thursday'), value: 5, checked: false },
		{ label: langString('friday'), value: 6, checked: false },
		{ label: langString('saturday'), value: 7, checked: false },
	];


	const formatDate = (dateString, timezone) => {
		return dateString ? moment.tz(dateString, timezone).format() : null;
	};


	useEffect(() => {
		if (meetingDetails) {

			const updatedWeekdays = weekdays.map(day => ({
				...day,
				checked: meetingDetails.settings.weekdays.includes(day.value)
			}));

			setFormData({
				title: meetingDetails.title,
				selected_platform: meetingDetails.settings.selected_platform,
				startDateTime: formatDate(meetingDetails.settings.startDateTime, meetingDetails.settings.meeting_timezone),
				meeting_type: meetingDetails.settings.meeting_type,
				disable_waiting_room: meetingDetails.settings.disable_waiting_room,
				meeting_authentication: meetingDetails.settings.meeting_authentication,
				join_before_host: meetingDetails.settings.join_before_host,
				option_host_video: meetingDetails.settings.option_host_video,
				option_participants_video: meetingDetails.settings.option_participants_video,
				option_mute_participants: meetingDetails.settings.option_mute_participants,
				meeting_timezone: meetingDetails.settings.meeting_timezone,
				agenda: meetingDetails.settings.agenda,
				auto_recording: meetingDetails.settings.auto_recording,
				password: meetingDetails.settings.password,
				enable_recurring_meeting: meetingDetails.settings.enable_recurring_meeting,
				recurrence_option: meetingDetails.settings.recurrence_option,
				repeat_day: meetingDetails.settings.repeat_day,
				end_type: meetingDetails.settings.end_type,
				end_date_time: formatDate(meetingDetails.settings.end_date_time, meetingDetails.settings.meeting_timezone),
				set_number_of_occurrences: meetingDetails.settings.set_number_of_occurrences,
				repeat_weekly: meetingDetails.settings.repeat_weekly,
				weekdays: updatedWeekdays,
				repeat_monthly: meetingDetails.settings.repeat_monthly,
				occurs_on_monthly: meetingDetails.settings.occurs_on_monthly,
				day_of_the_month: meetingDetails.settings.day_of_the_month,
				set_month_order: meetingDetails.settings.set_month_order,
				set_monthly_weekday: meetingDetails.settings.set_monthly_weekday,
				duration_hours: meetingDetails.settings.duration_hours,
				duration_minutes: meetingDetails.settings.duration_minutes,
				enable_should_register: meetingDetails.settings.enable_should_register,
				hide_sidebar: meetingDetails.settings.hide_sidebar,
				hide_header_footer: meetingDetails.settings.hide_header_footer,
				host_id: meetingDetails.settings.host_id,
				alternative_host: meetingDetails.settings.alternative_host,
				registration_type: meetingDetails.settings?.registration_type || 2, // safely check and default
				registrants_email_notification: meetingDetails.settings?.registrants_email_notification || false, // safely check and default
				approval_type: meetingDetails.settings?.approval_type || 0, // safely check and default
				display_time_user_zone: meetingDetails.settings?.display_time_user_zone || 0, // safely check and default

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
				end_date_time: moment.tz(prev.end_date_time, prev.meeting_timezone).tz(formData.meeting_timezone).format(),
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

		if (name === 'end_date_time') {
			// Keep the same local time but assign the timezone
			updatedValue = moment.tz(value, formData.meeting_timezone).format();
		}
	
		setFormData({ ...formData, [name]: updatedValue  });
	};

	const handleAlternativeHostChange = (selectedOption) => {
		const selectedValue = selectedOption.map(option => option.value);
		setFormData({ ...formData, alternative_host: selectedValue });
	};


	const handleWeekdays = (updatedOptions) => {
		// Extract the names of checked weekdays
		const checkedWeekdays = updatedOptions
			.filter(option => option.checked)
			.map(option => option.label.toLowerCase());

		// Update the formData state with the checked weekdays
		setFormData(prevState => ({
			...prevState,
			weekdays: updatedOptions
		}));
	};

	const handleSubmit = async (e) => {
		e.preventDefault();

		const checkedWeekdays = formData.weekdays
			.filter(day => day.checked)
			.map(day => day.value);

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
					startDateTime: formData.startDateTime,
					meeting_type: formData.meeting_type,
					disable_waiting_room: formData.disable_waiting_room,
					meeting_authentication: formData.meeting_authentication,
					join_before_host: formData.join_before_host,
					option_host_video: formData.option_host_video,
					option_participants_video: formData.option_participants_video,
					option_mute_participants: formData.option_mute_participants,
					meeting_timezone: formData.meeting_timezone,
					auto_recording: formData.auto_recording,
					password: formData.password,
					agenda: formData.agenda,
					selected_platform: formData.selected_platform,
					enable_recurring_meeting: formData.enable_recurring_meeting,
					recurrence_option: formData.recurrence_option,
					repeat_day: formData.repeat_day,
					end_type: formData.end_type,
					end_date_time: formData.end_date_time,
					set_number_of_occurrences: formData.set_number_of_occurrences,
					repeat_weekly: formData.repeat_weekly,
					weekdays: checkedWeekdays,
					repeat_monthly: formData.repeat_monthly,
					occurs_on_monthly: formData.occurs_on_monthly,
					day_of_the_month: formData.day_of_the_month,
					set_month_order: formData.set_month_order,
					set_monthly_weekday: formData.set_monthly_weekday,
					duration_hours: formData.duration_hours,
					duration_minutes: formData.duration_minutes,
					enable_should_register: formData.enable_should_register,
					hide_sidebar: formData.hide_sidebar,
					hide_header_footer: formData.hide_header_footer,
					host_id: formData.host_id,
					alternative_host: formData.alternative_host,
					meeting_description: JSON.stringify({ content: meetingDescription }),
					image_url: imageUrl,
					image_id: imageId,
					registration_type: formData.registration_type,
					registrants_email_notification: formData.registrants_email_notification,
					approval_type: formData.approval_type,
					display_time_user_zone: formData.display_time_user_zone,
				},
			});

			if (response && (response.code || response.message)) {
				if (2 == formData.meeting_type) {
					toast.error(__('Failed to Update Meeting !', 'meetinghub'));
				}

				if (1 == formData.meeting_type) {
					toast.error(__('Failed to update webinar !', 'meetinghub'));
				}

				if (response.message && response.message !== 'No privilege.') {
					// Error message from response
					setErrorMessage(response.message);
				} else {
					// Other error
					setErrorMessage(__('Error', 'meetinghub'));
				}

				if (response.message === 'No privilege.') {
					// No privilege error
					setErrorMessage(__("You don't have permission to add a new user", 'meetinghub'));
				}
			}

			// Redirect to the home URL after successful submission
			if (response && response.meeting_updated) {
				if (2 == formData.meeting_type) {
					toast.success(__('Meeting Updated Successfully.', 'meetinghub'));
				}

				if (1 == formData.meeting_type) {
					toast.success(__('Webinar updated successfully.', 'meetinghub'));
				}

				navigate('/');
			}
		} catch (error) {
			// Handle errors
			console.error('API Error:', error);
		} finally {
			// Enable the button after API request is complete (success or error)
			setIsSaving(false);
		}
	};

	// Define different options for each MhSelect
	const meetingTypeOptions = [
		{ value: '2', label: langString('meeting') },
		{ value: '1', label: langString('webinar')  },
	];


	// Auto recording
	const autoRecording = [
		{ value: 'none', label: langString('no_recordings') },
		{ value: 'local', label: langString('local') },
		{ value: 'cloud', label: langString('cloud') },
	];

	// Recurrence Options
	const recurrenceOptions = [
		{ value: '1', label: langString('daily') },
		{ value: '2', label: langString('weekly') },
		{ value: '3', label: langString('monthly') },
		{ value: '4', label: langString('no_fixed_time') },
	];

	// set_month_order
	const monthOrder = [
		{ value: 1, label: langString('first_week_of_the_month') },
		{ value: 2, label: langString('second_week_of_the_month') },
		{ value: 3, label: langString('third_week_of_the_month') },
		{ value: 4, label: langString('fourth_week_of_the_month') },
		{ value: -1, label: langString('last_week_of_the_month') },
	];

	// set_monthly_weekday
	const monthlyWeekdays = [
		{ value: 1, label: langString('sunday') },
		{ value: 2, label: langString('monday') },
		{ value: 3, label: langString('tuesday')  },
		{ value: 4, label: langString('wednesday')  },
		{ value: 5, label: langString('thursday') },
		{ value: 6, label: langString('friday') },
		{ value: 7, label: langString('saturday') },
	];

	// Repeat monthly
	const repeatMonthly = [
		{ value: '1', label: __('1', 'meetinghub') },
		{ value: '2', label: __('2', 'meetinghub') },
		{ value: '3', label: __('3', 'meetinghub') },
	];


	// Repeat Day
	const repeatDay = [];
	for (let i = 1; i <= 31; i++) {
		repeatDay.push({ value: String(i), label: String(i) });
	}

	// Number of Occurrences
	const numberOfOccurrences = [];
	for (let i = 1; i <= 20; i++) {
		numberOfOccurrences.push({ value: String(i), label: String(i) });
	}

	// End Type
	const endType = [
		{ value: 'date', label: langString('by_date') },
		{ value: 'occurrences', label: langString('by_occurrences') },
	];

	// Occurs Monthly
	const occursMonthly = [
		{ value: 'day', label: langString('day') },
		{ value: 'weekdays', label: langString('weekdays') },
	];

	// Repeat Weekly 
	const repeatWeekly = [];
	for (let i = 1; i <= 12; i++) {
		repeatWeekly.push({ value: String(i), label: String(i) });
	}

	const approvalTypeOptions = [
		{ value: '0', label: langString('automatic_approval') },
		{ value: '1', label: langString('manual_approval') },
		{ value: '2', label: langString('sano_registrationturday') },
	];

	const registrationTypeOptions = [
		{ value: '1', label: langString('register_once_for_all_sessions') },
		{ value: '2', label: langString('register_for_each_session_separately')  },
		{ value: '3', label: langString('register_once_and_choose_sessions') }
	];


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
								{isSaving ? langString('updating') : langString('update_meeting')}
							</button>
						</div>
					</div>
				)}

				<div className="form-wrapper">
					<form className="form" onSubmit={handleSubmit}>
						<div className="mhub-col-lg-12">
							<MhInput
								label={langString('meeting_name') }
								description={langString('please_enter_the_meeting_name') }
								type="text"
								value={formData.title}
								onChange={(name, value) => handleChange(name, value)}
								name="title"
								required="yes"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<div className="mhub-form-group">
								<label>{langString('meeting_description')}<small className="description">{ langString('meeting_description_help')} </small></label>
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
								label={langString('meeting_thumbnail') }
								description={langString('meeting_thumbnail_upload') }
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhTextArea
								label={langString('meeting_agenda') }
								description={langString('write_agenda_for_your_meeting') }
								value={formData.agenda}
								onChange={(name, value) => handleChange(name, value)}
								name="agenda"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('meeting_host') }
								description={langString('meeting_host_hint') }
								options={zoomUsers()}
								value={formData.host_id}
								onChange={(name, value) => handleChange(name, value)}
								name="host_id"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('meeting_type') }
								description={langString('meeting_type_hint') }
								options={meetingTypeOptions}
								value={formData.meeting_type}
								onChange={(name, value) => handleChange(name, value)}
								name="meeting_type"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<div className="mhub-form-group">
								<label>
									{langString('start_datetime') }
									<small className="description">{langString('start_datetime_help')}</small>
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
								disabled={!isProActive()}
								isLocked={!isProActive()}
								isProActive={isProActive()}
							/>
						</div>

						{
							formData.enable_recurring_meeting && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('recurrence')}
										description={langString('select_recurrence') }
										options={recurrenceOptions}
										value={formData.recurrence_option}
										onChange={(name, value) => handleChange(name, value)}
										name="recurrence_option"
									/>
								</div>
							)
						}


						{
							formData.enable_recurring_meeting && 1 == formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('repeat_every') }
									    description={langString('repeat_every_help')}
										options={repeatDay}
										value={formData.repeat_day}
										onChange={(name, value) => handleChange(name, value)}
										name="repeat_day"
										SelectClass="zoom-recurrence-repeat"
										rightLabel="Day"
									/>
								</div>

							)
						}

						{
							formData.enable_recurring_meeting && 2 == formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('repeat_every') }
										description={langString('repeat_every_help') }
										options={repeatWeekly}
										value={formData.repeat_weekly}
										onChange={(name, value) => handleChange(name, value)}
										name="repeat_weekly"
										SelectClass="zoom-recurrence-repeat"
										rightLabel="Week"
									/>
								</div>

							)
						}

						{
							formData.enable_recurring_meeting && 3 == formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('repeat_every') }
										description={langString('repeat_every_help') }
										options={repeatMonthly}
										value={formData.repeat_monthly}
										onChange={(name, value) => handleChange(name, value)}
										name="repeat_monthly"
										SelectClass="zoom-recurrence-repeat"
										rightLabel="Month"
									/>
								</div>

							)
						}

						{
							formData.enable_recurring_meeting && 3 == formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('occurs_on') }
										description={langString('select_occurs_type') }
										options={occursMonthly}
										value={formData.occurs_on_monthly}
										onChange={(name, value) => handleChange(name, value)}
										name="occurs_on_monthly"
									/>
								</div>
							)
						}

						{
							formData.enable_recurring_meeting && 3 == formData.recurrence_option && 'day' == formData.occurs_on_monthly && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('day_of_month') }
										description={langString('repeat_every_help') }
										options={repeatDay}
										value={formData.day_of_the_month}
										onChange={(name, value) => handleChange(name, value)}
										name="day_of_the_month"
									/>
								</div>
							)
						}

						{
							formData.enable_recurring_meeting && 3 == formData.recurrence_option && 'weekdays' == formData.occurs_on_monthly && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('set_order') }
										description={langString('set_order_help') }
										options={monthOrder}
										value={formData.set_month_order}
										onChange={(name, value) => handleChange(name, value)}
										name="set_month_order"
									/>
								</div>
							)
						}

						{
							formData.enable_recurring_meeting && 3 == formData.recurrence_option && 'weekdays' == formData.occurs_on_monthly && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('set_weekday') }
										description={langString('select_weekday') }
										options={monthlyWeekdays}
										value={formData.set_monthly_weekday}
										onChange={(name, value) => handleChange(name, value)}
										name="set_monthly_weekday"
									/>
								</div>
							)
						}

						{
							formData.enable_recurring_meeting && 2 == formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhCheckbox
										label={langString('occurs_on') }
										description={langString('select_weekdays') }
										options={formData.weekdays}
										onChange={handleWeekdays}
									/>
								</div>

							)
						}


						{
							formData.enable_recurring_meeting && 4 != formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('end_date') }
										description={langString('select_end_type') }
										options={endType}
										value={formData.end_type}
										onChange={(name, value) => handleChange(name, value)}
										name="end_type"
									/>
								</div>

							)
						}

						{
							formData.enable_recurring_meeting && 'occurrences' == formData.end_type && 4 != formData.recurrence_option && isProActive() && (

								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('set_num_occurrences') }
										description={langString('set_num_occurrences_help') }
										options={numberOfOccurrences}
										value={formData.set_number_of_occurrences}
										onChange={(name, value) => handleChange(name, value)}
										name="set_number_of_occurrences"
									/>
								</div>

							)
						}

						{
							formData.enable_recurring_meeting && 'date' == formData.end_type && 4 != formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<div className="mhub-form-group">
										<label>
											{langString('set_end_date') }
											<small className="description">{langString('set_end_date_help') }</small>
										</label>

										<div className="input-wrapper">
											<Datetime
												value={moment.tz(formData.end_date_time, formData.meeting_timezone)}
												onChange={(date) => handleChange('end_date_time', date)}
												isValidDate={(current) => {
													return current.isAfter(moment().subtract(1, 'day'), 'day');
												}}
											/>
										</div>
									</div>
								</div>

							)
						}

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('timezone') }
								description={langString('meeting_timezone')}
								options={Timezones}
								value={formData.meeting_timezone}
								onChange={(name, value) => handleChange(name, value)}
								name="meeting_timezone"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('show_user_timezone') }
								description={langString('show_user_timezone_help') }
								checked={formData.display_time_user_zone}
								onChange={(name, value) => handleChange(name, value)}
								name="display_time_user_zone"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('should_register') }
								description={ 2 == formData.meeting_type ?  langString('should_register_help')  : langString('register_for_webinar') }
								checked={formData.enable_should_register}
								onChange={(name, value) => handleChange(name, value)}
								name="enable_should_register"
								disabled={!isProActive()}
								isLocked={!isProActive()}
								isProActive={isProActive()}
							/>
						</div>

						{
							formData.enable_should_register && 1 == formData.meeting_type && (
								<>

								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('registration_approval') }
										description={langString('registration_approval_hint') }
										options={approvalTypeOptions}
										value={formData.approval_type}
										onChange={(name, value) => handleChange(name, value)}
										name="approval_type"
									/>
								</div>

								<div className="mhub-col-lg-12">
									<MhSelect
											label={langString('recurring_registration_type') }
											description={langString('recurring_registration_hint') }
											options={registrationTypeOptions}
											value={formData.registration_type}
											onChange={(name, value) => handleChange(name, value)}
											name="registration_type"
										/>

								</div>

								<div className="mhub-col-lg-12">
									<MhSwitcher
										label={langString('email_notifications') }
										description={langString('email_notifications_hint') }
										checked={formData.registrants_email_notification}
										onChange={(name, value) => handleChange(name, value)}
										name="registrants_email_notification"
									/>
								</div>

								</>

							)
						}


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
								label={langString('disable_waiting_room') }
								description={langString('disable_waiting_room_hint') }
								checked={formData.disable_waiting_room}
								onChange={(name, value) => handleChange(name, value)}
								name="disable_waiting_room"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('meeting_authentication') }
								description={langString('meeting_authentication_hint') }
								checked={formData.meeting_authentication}
								onChange={(name, value) => handleChange(name, value)}
								name="meeting_authentication"
							/>
						</div>

						{
							2 == formData.meeting_type && (

								<div className="mhub-col-lg-12">
									<MhSwitcher
										label={langString('join_before_host') }
										description={langString('join_before_host_hint') }
										checked={formData.join_before_host}
										onChange={(name, value) => handleChange(name, value)}
										name="join_before_host"
									/>
								</div>

							)
						}


						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('start_when_host_joins') }
								description={langString('start_when_host_joins_hint') }
								checked={formData.option_host_video}
								onChange={(name, value) => handleChange(name, value)}
								name="option_host_video"
							/>
						</div>

						{
							2 == formData.meeting_type && (
								<div className="mhub-col-lg-12">
									<MhSwitcher
										label={langString('participants_video') }
										description={langString('participants_video_hint') }
										checked={formData.option_participants_video}
										onChange={(name, value) => handleChange(name, value)}
										name="option_participants_video"
									/>
								</div>
							)
						}

						{
							2 == formData.meeting_type && (
								<div className="mhub-col-lg-12">
									<MhSwitcher
										label={langString('mute_on_entry') }
										description={langString('mute_on_entry_hint') }
										checked={formData.option_mute_participants}
										onChange={(name, value) => handleChange(name, value)}
										name="option_mute_participants"
									/>
								</div>
							)
						}

						{
							1 == formData.meeting_type && (
								<div className="mhub-col-lg-12">
									<MhSwitcher
										label={langString('panelists_join') }
										description={langString('panelists_video_hint') }
										checked={formData.panelists_video}
										onChange={(name, value) => handleChange(name, value)}
										name="panelists_video"
									/>
								</div>
							)
						}

						{
							1 == formData.meeting_type && (
								<div className="mhub-col-lg-12">
									<MhSwitcher
										label={langString('practise_session') }
										description={langString('practise_session_hint') }
										checked={formData.practice_session}
										onChange={(name, value) => handleChange(name, value)}
										name="practice_session"
									/>
								</div>
							)
						}

						{
							1 == formData.meeting_type && (
								<div className="mhub-col-lg-12">
									<MhSwitcher
										label={langString('hd_video') }
										description={langString('hd_video_hint') }
										checked={formData.hd_video}
										onChange={(name, value) => handleChange(name, value)}
										name="hd_video"
									/>
								</div>
							)
						}

						{
							1 == formData.meeting_type && (
								<div className="mhub-col-lg-12">
									<MhSwitcher
										label={langString('multiple_devices') }
										description={langString('multiple_devices_hint') }
										checked={formData.allow_multiple_devices}
										onChange={(name, value) => handleChange(name, value)}
										name="allow_multiple_devices"
									/>
								</div>
							)
						}

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('auto_recording') }
								description={langString('auto_recording_hint') }
								options={autoRecording}
								value={formData.auto_recording}
								onChange={(name, value) => handleChange(name, value)}
								name="auto_recording"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<div className="mhub-form-group">
								<label>
									{langString('alternative_host') }
									<small className="description">{langString('alternative_host_hint') }</small>
								</label>

								<div className="input-wrapper">
									<Select
										options={zoomUsers()}
										onChange={(selectedOption) => handleChange('alternative_host', selectedOption)}
										isMulti
										className="mhub-select2"
										placeholder={langString('select_alternative_hosts') }
										styles={select2Styles()}
									/>
								</div>
							</div>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={__('Hide Sidebar', 'meetinghub')}
								description={__('Hide meeting page right sidebar', 'meetinghub')}
								name="hide_sidebar"
								checked={formData.hide_sidebar}
								onChange={(name, value) => handleChange(name, value)}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('hide_sidebar') }
								description={langString('hide_sidebar_help') }
								name="hide_header_footer"
								checked={formData.hide_header_footer}
								onChange={(name, value) => handleChange(name, value)}
							/>
						</div>

						<button type="submit" style={{ display: 'none' }} ref={hiddenSubmitRef} />

						<div className="mhub-form-actions">
							<button type="submit" className="save-meeting" disabled={isSaving}>
								{isSaving ? langString('updating')  : langString('update_meeting') }
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	);
};

export default ZoomEditForm;
