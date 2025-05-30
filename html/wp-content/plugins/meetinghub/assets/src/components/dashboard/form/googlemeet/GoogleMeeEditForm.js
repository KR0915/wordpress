import React, { useEffect, useState, useRef } from 'react';
import Datetime from 'react-datetime';
import 'react-datetime/css/react-datetime.css';
import DatePicker from 'react-datepicker';
import { useNavigate } from 'react-router-dom';
import Spinner from '../../../common/Spinner';
import MhInput from '../../../common/fields/MhInput';
import MhSelect from '../../../common/fields/MhSelect';
import MhSwitcher from '../../../common/fields/MhSwitcher';
import TimezoneList from '../../../common/fields/TimezoneList';
import MhDurationSelect from '../../../common/fields/MhDurationSelect';
import MhTextArea from '../../../common/fields/MhTextArea';
import MhCheckbox from '../../../common/fields/MhCheckbox';
import Editor from 'react-simple-wysiwyg';
import { isProActive } from '../../../../Helpers';
import { toast } from 'react-toastify';
const { __ } = wp.i18n;
import ImageUploader from '../../../common/ImageUploader';
import moment from 'moment-timezone';
import { langString } from '../../../../Helpers';

const GoogleMeeEditForm = ({ meetingId, meetingDetails }) => {
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

	// Define default weekdays array
	const defaultWeekdays = [
		{ label: langString('sunday'), value: 1, checked: false },
		{ label: langString('monday'), value: 2, checked: false },
		{ label: langString('tuesday'), value: 3, checked: false },
		{ label: langString('wednesday'), value: 4, checked: false },
		{ label: langString('thursday'), value: 5, checked: false },
		{ label: langString('friday'), value: 6, checked: false },
		{ label: langString('saturday'), value: 7, checked: false },
	];

	const handleWeekdays = (updatedOptions) => {
		// Update the formData state with the updated weekdays array
		setFormData(prevState => ({
			...prevState,
			weekdays: updatedOptions
		}));
	};

	const formatDate = (dateString, timezone) => {
		return dateString ? moment.tz(dateString, timezone).format() : null;
	};

	useEffect(() => {
		if (meetingDetails) {

			// Initialize the weekdays array based on the meeting details
			let weekdaysData = [...defaultWeekdays];
			
			// If meeting details has weekdays as an array of values
			if (Array.isArray(meetingDetails.settings?.weekdays)) {
				// Convert the array of values [1, 3, 5, 7] to checked weekdays
				weekdaysData = defaultWeekdays.map(day => ({
					...day,
					checked: meetingDetails.settings.weekdays.includes(day.value)
				}));
			} 
			// If meeting details has weekdays as an array of objects
			else if (Array.isArray(meetingDetails.settings?.weekdays) && 
				typeof meetingDetails.settings?.weekdays[0] === 'object') {
				weekdaysData = meetingDetails.settings.weekdays;
			}

			setFormData({
				title: meetingDetails.title,
				selected_platform: meetingDetails.settings.selected_platform,
				startDateTime: formatDate(meetingDetails.settings.startDateTime, meetingDetails.settings.meeting_timezone),
				end_date_time: meetingDetails.settings?.end_date_time || new Date(),
				meeting_timezone: meetingDetails.settings.meeting_timezone,
				password: meetingDetails.settings.password,
				duration_hours: meetingDetails.settings.duration_hours,
				duration_minutes: meetingDetails.settings.duration_minutes,
				hide_sidebar: meetingDetails.settings.hide_sidebar,
				hide_header_footer: meetingDetails.settings.hide_header_footer,
				enable_should_register: meetingDetails.settings.enable_should_register,
				enable_recurring_meeting: meetingDetails.settings.enable_recurring_meeting,
				recurrence_option: meetingDetails.settings?.recurrence_option || 1,
				end_type: meetingDetails.settings?.end_type || 'date',
				set_number_of_occurrences: meetingDetails.settings?.set_number_of_occurrences || 1,
				repeat_day: meetingDetails.settings?.repeat_day || 1,
				repeat_year: meetingDetails.settings?.repeat_year || 1,
				repeat_weekly: meetingDetails.settings?.repeat_weekly || 1,
				repeat_monthly: meetingDetails.settings?.repeat_monthly || 1,
				weekdays: weekdaysData,
				occurs_on_monthly: meetingDetails.settings?.occurs_on_monthly || 'day',
				set_month_order: meetingDetails.settings?.set_month_order || 1,
				set_monthly_weekday: meetingDetails.settings?.set_monthly_weekday || 1,
				meeting_summary: meetingDetails.settings.meeting_summary,
				duration_minutes: meetingDetails.settings.duration_minutes,
				reminder_time: meetingDetails.settings.reminder_time,
				event_status: meetingDetails.settings.event_status,
				send_updates: meetingDetails.settings.send_updates,
				transparency: meetingDetails.settings.transparency,
				event_visibility: meetingDetails.settings.event_visibility,
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
					end_date_time: formData.end_date_time,
					meeting_timezone: formData.meeting_timezone,
					duration_hours: formData.duration_hours,
					meeting_summary: formData.meeting_summary,
					duration_minutes: formData.duration_minutes,
					reminder_time: formData.reminder_time,
					event_status: formData.event_status,
					send_updates: formData.send_updates,
					transparency: formData.transparency,
					event_visibility: formData.event_visibility,
					hide_sidebar: formData.hide_sidebar,
					hide_header_footer: formData.hide_header_footer,
					password: formData.password,
					enable_should_register: formData.enable_should_register,
					enable_recurring_meeting: formData.enable_recurring_meeting,
					recurrence_option: formData.recurrence_option,
					repeat_day: formData.repeat_day,
					repeat_year: formData.repeat_year,
					end_type: formData.end_type,
					set_number_of_occurrences: formData.set_number_of_occurrences,
					repeat_weekly: formData.repeat_weekly,
					weekdays: formData.weekdays.filter(day => day.checked).map(day => day.value),
					repeat_monthly: formData.repeat_monthly,
					occurs_on_monthly: formData.occurs_on_monthly,
					set_month_order: formData.set_month_order,
					set_monthly_weekday: formData.set_monthly_weekday,
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

			if (response && response.error ) {
				toast.error(langString('failed_update_meeting'));
				setErrorMessage(response.error);
			}

			if (!response) {
				toast.error(langString('failed_update_meeting'));
				setErrorMessage(langString('check_google_meet_settings') );
			}

		} catch (error) {
			// Handle errors
			console.error(langString('api_error'), error);
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
	
	const defaultReminderOptions = [
		{ value: '1', label: langString('minutes_before_1') },
		{ value: '5', label: langString('minutes_before_5') },
		{ value: '15', label: langString('minutes_before_15')},
		{ value: '30', label: langString('minutes_before_30') },
	];

	const defaultEventStatusOptions = [
		{ value: 'confirmed', label: langString('confirmed') },
		{ value: 'tentative', label: langString('tentative') },
	];

	const sendStatusOptions = [
		{ value: 'all', label: langString('all') },
		{ value: 'externalOnly', label: langString('external_only') },
		{ value: 'none', label: langString('none') },
	];

	const transparencyOptions = [
		{ value: 'opaque', label: langString('opaque_block') },
		{ value: 'transparent', label: langString('transparent_not_block') },
	];

	const eventVisibilityOptions = [
		{ value: 'default', label: langString('default') },
		{ value: 'public', label: langString('public') },
		{ value: 'private', label: langString('private') },
	];

	// Recurrence Options
	const recurrenceOptions = [
		{ value: '1', label: langString('daily') },
		{ value: '2', label: langString('weekly') },
		{ value: '3', label: langString('monthly') },
		{ value: '4', label: langString('yearly') },
	];

	// set_month_order
	const monthOrder = [
		{ value: 1, label: langString('first_week') },
		{ value: 2, label: langString('second_week') },
		{ value: 3, label: langString('third_week') },
		{ value: 4, label: langString('fourth_week') },
		{ value: -1, label: langString('last_week') },
	];

	// set_monthly_weekday
	const monthlyWeekdays = [
		{ value: 'MO', label: langString('monday') },
		{ value: 'TU', label: langString('tuesday') },
		{ value: 'WE', label: langString('wednesday') },
		{ value: 'TH', label: langString('thursday')},
		{ value: 'FR', label: langString('friday') },
		{ value: 'SA', label: langString('saturday') },
		{ value: 'SU', label: langString('sunday') },
	];

	// Repeat monthly
	const repeatMonthly = [];
	for (let i = 1; i <= 12; i++) {
		repeatMonthly.push({ value: String(i), label: String(i) });
	}


	// Repeat Day
	const repeatDay = [];
	for (let i = 1; i <= 31; i++) {
		repeatDay.push({ value: String(i), label: String(i) });
	}

	// Repeat Year
	const repeatYear = [];
	for (let i = 1; i <= 12; i++) {
		repeatYear.push({ value: String(i), label: String(i) });
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
		{ value: 'never', label: langString('never')},
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

	return (
		<div>
			{errorMessage && (
				<div className="mhub_googlem_error error">
					<h3>{errorMessage}</h3>
					<span className="close-icon" onClick={handleCloseError}>✕</span>
				</div>
			)}
			<div className="mhub-googlem-meeting-form">
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
								label={langString('meeting_name')}
								description={langString('enter_meeting_name')}
								type="text"
								value={formData.title}
								onChange={(name, value) => handleChange(name, value)}
								name="title"
								required="yes"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<div className="mhub-form-group">
								<label> {langString('meeting_description')}<small className="description">{langString('meeting_description_help')} </small></label>
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
							<MhTextArea
								label={ langString('meeting_summary') }
								description={langString('meeting_summary_help') }
								value={formData.meeting_summary}
								onChange={(name, value) => handleChange(name, value)}
								name="meeting_summary"
							/>
						</div>

						<div className="mhub-col-lg-12">
							<ImageUploader
								imageUrl={imageUrl}
								setImageUrl={setImageUrl}
								setImageID={setImageID}
								label={langString('meeting_thumbnail')}
								description={langString('meeting_thumbnail_upload')}
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
								label={langString('duration')}
								description={langString('select_duration')}
								hours={formData.duration_hours}
								minutes={formData.duration_minutes}
								onChangeHours={(value) => handleChange('duration_hours', value)}
								onChangeMinutes={(value) => handleChange('duration_minutes', value)}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('recurring_meeting') }
								description={langString('enable_recurring')}
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
										description={langString('select_recurrence')}
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
							formData.enable_recurring_meeting && 4 == formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('repeat_every')}
										description={langString('repeat_every_help')}
										options={repeatYear}
										value={formData.repeat_year}
										onChange={(name, value) => handleChange(name, value)}
										name="repeat_year"
										SelectClass="zoom-recurrence-repeat"
										rightLabel="Years"
									/>
								</div>

							)
						}

						{
							formData.enable_recurring_meeting && 2 == formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('repeat_every')}
										description={langString('repeat_every_help')}
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
										label={langString('repeat_every')}
										description={langString('repeat_every_help')}
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
										description={langString('select_occurs_type')}
										options={occursMonthly}
										value={formData.occurs_on_monthly}
										onChange={(name, value) => handleChange(name, value)}
										name="occurs_on_monthly"
									/>
								</div>
							)
						}

						{
							formData.enable_recurring_meeting && 3 == formData.recurrence_option && 'weekdays' == formData.occurs_on_monthly && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('set_order')}
										description={langString('set_order_help')}
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
										description={langString('select_weekday')}
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
										label={langString('occurs_on')}
										description={langString('select_weekdays')}
										options={formData.weekdays || defaultWeekdays}
										onChange={handleWeekdays}
									/>
								</div>

							)
						}


						{
							formData.enable_recurring_meeting && 5 != formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('ends')}
										description={langString('select_end_type')}
										options={endType}
										value={formData.end_type}
										onChange={(name, value) => handleChange(name, value)}
										name="end_type"
									/>
								</div>

							)
						}

						{
							formData.enable_recurring_meeting && 'occurrences' == formData.end_type && 5 != formData.recurrence_option && isProActive() && (

								<div className="mhub-col-lg-12">
									<MhSelect
										label={langString('set_num_occurrences')}
										description={langString('set_num_occurrences_help')}
										options={numberOfOccurrences}
										value={formData.set_number_of_occurrences}
										onChange={(name, value) => handleChange(name, value)}
										name="set_number_of_occurrences"
									/>
								</div>

							)
						}

						{
							formData.enable_recurring_meeting && 'date' == formData.end_type && 5 != formData.recurrence_option && isProActive() && (
								<div className="mhub-col-lg-12">
									<div className="mhub-form-group">
										<label>
											{langString('set_end_date')}
											<small className="description">{langString('set_end_date_help')}</small>
										</label>

										<div className="input-wrapper">
											<DatePicker
												selected={formData.end_date_time}
												onChange={(date) => handleChange('end_date_time', date)}
												dateFormat="MM/dd/yyyy"
												minDate={new Date()}
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
								label={langString('should_register')}
								description={langString('should_register_help') }
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
								description={langString('password_help')}
								type="text"
								value={formData.password}
								onChange={(name, value) => handleChange(name, value)}
								name="password"
								required="no"
								maxLength={10}
								disabled={!isProActive()} 
          						isLocked={ !isProActive()}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('default_reminder_time')}
								description={langString('default_reminder_time_help') }
								options={defaultReminderOptions}
								value={formData.reminder_time}
								onChange={(name, value) => handleChange(name, value)}
								name="reminder_time"
								disabled={!isProActive()} 
          						isLocked={ !isProActive()}
							/>
						</div>
						
						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('default_event_status')}
								description={langString('default_event_status_meet')}
								options={defaultEventStatusOptions}
								value={formData.event_status}
								onChange={(name, value) => handleChange(name, value)}
								name="event_status"
								disabled={!isProActive()} 
          						isLocked={ !isProActive()}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('send_updates')}
								description={langString('send_updates_help')}
								options={sendStatusOptions}
								value={formData.send_updates}
								onChange={(name, value) => handleChange(name, value)}
								name="send_updates"
								disabled={!isProActive()} 
          						isLocked={ !isProActive()}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('transparency') }
								description={langString('transparency_help')}
								options={transparencyOptions}
								value={formData.transparency}
								onChange={(name, value) => handleChange(name, value)}
								name="transparency"
								disabled={!isProActive()} 
          						isLocked={ !isProActive()}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSelect
								label={langString('visibility_on_calendar')}
								description={langString('visibility_on_calendar_help') }
								options={eventVisibilityOptions}
								value={formData.event_visibility}
								onChange={(name, value) => handleChange(name, value)}
								name="event_visibility"
								disabled={!isProActive()} 
          						isLocked={ !isProActive()}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('hide_sidebar')}
								description={langString('hide_sidebar_help')}
								name="hide_sidebar"
								checked={formData.hide_sidebar}
								onChange={(name, value) => handleChange(name, value)}
							/>
						</div>

						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('hide_header_footer')}
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

export default GoogleMeeEditForm;
