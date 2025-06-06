import React, { useState, useRef, useEffect } from 'react';
import Datetime from 'react-datetime';
import 'react-datetime/css/react-datetime.css';
import { useNavigate } from "react-router-dom";
import MhSwitcher from '../../../common//fields/MhSwitcher';
import MhInput from '../../../common/fields/MhInput';
import { getRandomDomain } from '../../RandomDomainGenerator';
import { generateRandomRoom } from '../../RandomRoomGenerator';
import MhSelect from '../../../common/fields/MhSelect';
import TimezoneList from '../../../common/fields/TimezoneList';
import Editor from 'react-simple-wysiwyg';
import { isProActive } from '../../../../Helpers';
import TimePicker from 'react-time-picker';
import { toast } from 'react-toastify';
const { __ } = wp.i18n;
import ImageUploader from '../../../common/ImageUploader';
import moment from 'moment-timezone';
import { langString } from '../../../../Helpers';


const meetingVideoResolution = [
	{ value: "480", label: langString('res_480p') },
	{ value: "720", label: langString('res_720p') },
	{ value: "1080", label: langString('res_1080p')},
	{ value: "1440", label: langString('res_1440p')  },
	{ value: "2160", label: langString('res_2160p') },
	{ value: "4320", label: langString('res_4320p')  },
];

//Recurrence Options
const recurrenceOptions = [
	{ value: 'daily', label: langString('daily') },
	{ value: 'weekly', label: langString('weekly') },
	{ value: 'monthly', label: langString('monthly') },
	{ value: 'yearly', label: langString('yearly') },
];

// Set Monthly Weekday
const Weekdays = [
	{ value: 'sunday', label: langString('sunday')},
	{ value: 'monday', label: langString('monday') },
	{ value: 'tuesday', label: langString('tuesday')  },
	{ value: 'wednesday', label: langString('wednesday') },
	{ value: 'thursday', label: langString('thursday') },
	{ value: 'friday', label: langString('friday')  },
	{ value: 'saturday', label: langString('saturday')  },
];

// Set Yearly Months
const yearlyMonths = [
	{ value: 1, label: langString('month_january') },
	{ value: 2, label: langString('month_february') },
	{ value: 3, label: langString('month_march') },
	{ value: 4, label: langString('month_april') },
	{ value: 5, label: langString('month_may') },
	{ value: 6, label: langString('month_june') },
	{ value: 7, label: langString('month_july') },
	{ value: 8, label: langString('month_august') },
	{ value: 9, label: langString('month_september')  },
	{ value: 10, label: langString('month_october') },
	{ value: 11, label: langString('month_november')},
	{ value: 12, label: langString('month_december')  },
];

//Timezone
const Timezones = TimezoneList();

//Repeat Day
const repeatDay = [];
for (let i = 1; i <= 31; i++) {
	repeatDay.push({ value: String(i), label: i });
}

const JitsiForm = ({ selectedPlatform }) => {
	const navigate = useNavigate();
	const formRef = useRef(null);
	const hiddenSubmitRef = useRef(null);
	const [imageUrl, setImageUrl] = useState('');
	const [imageId, setImageID] = useState('');

	const [meetingDescription, setMeetingDescription] = useState('');

	function handleMeetingDescription(e) {
		setMeetingDescription(e.target.value);
	}

	const handleBack = () => {
		navigate('/');
	};

	var mhub_jitsi_settings = mhubMeetingsData.mhub_jitsi_settings;
	const defaultTimezone = mhub_jitsi_settings ? mhub_jitsi_settings.meeting_timezone : mhubMeetingsData.mhub_timezone;


	const [formData, setFormData] = useState({
		title: generateRandomRoom(10),
		height: 720,
		width: 1080,
		startDateTime: moment.tz(new Date(), defaultTimezone).format(),
		start_with_audio_muted: mhub_jitsi_settings ? mhub_jitsi_settings.yourself_muted : false,
		start_with_video_muted: mhub_jitsi_settings ? mhub_jitsi_settings.start_with_video_muted : false,
		start_with_screen_sharing: mhub_jitsi_settings ? mhub_jitsi_settings.start_with_screen_sharing : false,
		enable_inviting: mhub_jitsi_settings ? mhub_jitsi_settings.enable_inviting : false,
		audio_muted: mhub_jitsi_settings ? mhub_jitsi_settings.audio_muted : "",
		audio_only: mhub_jitsi_settings ? mhub_jitsi_settings.audio_only : false,
		start_silent: mhub_jitsi_settings ? mhub_jitsi_settings.start_silent : false,
		video_resolution: mhub_jitsi_settings ? mhub_jitsi_settings.video_resolution : "",
		max_full_resolution: mhub_jitsi_settings ? mhub_jitsi_settings.max_full_resolution : "",
		video_muted_after: mhub_jitsi_settings ? mhub_jitsi_settings.video_muted_after : "",
		enable_recording: mhub_jitsi_settings ? mhub_jitsi_settings.enable_recording : false,
		enable_simulcast: mhub_jitsi_settings ? mhub_jitsi_settings.enable_simulcast : false,
		enable_livestreaming: mhub_jitsi_settings ? mhub_jitsi_settings.enable_livestreaming : false,
		enable_welcome_page: mhub_jitsi_settings ? mhub_jitsi_settings.enable_welcome_page : false,
		enable_transcription: mhub_jitsi_settings ? mhub_jitsi_settings.enable_transcription : false,
		enable_outbound: mhub_jitsi_settings ? mhub_jitsi_settings.enable_outbound : false,
		enable_recurring_meeting: mhub_jitsi_settings ? mhub_jitsi_settings.enable_recurring_meeting : false,
		recurrence_option: 'daily',
		recurrence_time: '17:00',
		set_weekday: 'sunday',
		repeat_day: 1,
		set_yearly_month: 1,
		meeting_timezone: mhub_jitsi_settings ? mhub_jitsi_settings.meeting_timezone : mhubMeetingsData.mhub_timezone,
		enable_should_register: mhub_jitsi_settings ? mhub_jitsi_settings.enable_should_register : false,
		password: mhubMeetingsData.mhub_password,
		hide_sidebar: mhub_jitsi_settings ? mhub_jitsi_settings.hide_sidebar : false,
		hide_header_footer: mhub_jitsi_settings ? mhub_jitsi_settings.hide_header_footer : false,
		display_time_user_zone: 1,
	});

	const [isSaving, setIsSaving] = useState(false);

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
				path: 'mhub/v1/meetings',
				method: 'POST',
				data: {
					title: formData.title,
					height: formData.height,
					width: formData.width,
					startDateTime: formData.startDateTime,
					selected_platform: selectedPlatform,
					domain: getRandomDomain(),
					room_name: formData.title,
					start_with_audio_muted: formData.start_with_audio_muted,
					start_with_video_muted: formData.start_with_video_muted,
					start_with_screen_sharing: formData.start_with_screen_sharing,
					enable_inviting: formData.enable_inviting,
					audio_muted: formData.audio_muted,
					audio_only: formData.audio_only,
					start_silent: formData.start_silent,
					video_resolution: formData.video_resolution,
					max_full_resolution: formData.max_full_resolution,
					video_muted_after: formData.video_muted_after,
					enable_recording: formData.enable_recording,
					enable_simulcast: formData.enable_simulcast,
					enable_livestreaming: formData.enable_livestreaming,
					enable_welcome_page: formData.enable_welcome_page,
					enable_transcription: formData.enable_transcription,
					enable_outbound: formData.enable_outbound,
					enable_recurring_meeting: formData.enable_recurring_meeting,
					recurrence_option: formData.recurrence_option,
					recurrence_time: formData.recurrence_time,
					set_weekday: formData.set_weekday,
					repeat_day: formData.repeat_day,
					set_yearly_month: formData.set_yearly_month,
					meeting_timezone: formData.meeting_timezone,
					enable_should_register: formData.enable_should_register,
					password: formData.password,
					hide_sidebar: formData.hide_sidebar,
					hide_header_footer: formData.hide_header_footer,
					meeting_description: JSON.stringify({ content: meetingDescription }),
					image_url: imageUrl,
					image_id: imageId,
					display_time_user_zone: formData.display_time_user_zone,
				},

			});

			// Redirect to the home URL after successful submission
			if (response && response.meeting_inserted) {
				toast.success(langString('meeting_created'));
				navigate('/');

			} else {
				toast.error(langString('meeting_create_failed'));
			}

		} catch (error) {
			// Handle errors
			console.error(langString('api_error') , error);
		} finally {
			// Enable the button after API request is complete (success or error)
			setIsSaving(false);
		}
	};

	const handleStickySaveClick = () => {
		// Trigger form submission by calling submit() method on the form
		hiddenSubmitRef.current.click();
	};

	return (
		<div className="mhub-jitsi-meeting-form">
			{ ! mhubMeetingsData.hide_floating_create_btn  && (
				<div className='mhub-col-lg-12'>
					<div className="mhub-form-actions sticky-save-btn">
						<button type="button" className="save-meeting" disabled={isSaving} onClick={handleStickySaveClick}>
							{isSaving ? langString('creating') : langString('create_meeting') }
						</button>
					</div>
				</div>
			) }
			
			<div className="form-wrapper">
				<form className="form" onSubmit={handleSubmit} ref={formRef}>
					<div className="mhub-col-lg-12">
						<MhInput
							label={langString('meeting_name') }
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
							<label>{langString('meeting_description')} <small className="description"> {langString('meeting_description_help')}</small></label>
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
							label={langString('meeting_thumbnail')}
							description={langString('meeting_thumbnail_upload')}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('recurring_meeting')}
							description={langString('enable_recurring')}
							checked={formData.enable_recurring_meeting}
							onChange={(name, value) => handleChange(name, value)}
							name="enable_recurring_meeting"
							disabled={!isProActive()}
							isLocked={!isProActive()}
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
						formData.enable_recurring_meeting && 'weekly' == formData.recurrence_option && isProActive() && (
							<div className="mhub-col-lg-12">
								<MhSelect
									label={langString('set_weekday') }
									description={langString('select_which_day_week') }
									options={Weekdays}
									value={formData.set_weekday}
									onChange={(name, value) => handleChange(name, value)}
									name="set_weekday"
								/>
							</div>
						)
					}

					{
						formData.enable_recurring_meeting && 'yearly' == formData.recurrence_option && isProActive() && (
							<div className="mhub-col-lg-12">
								<MhSelect
									label={langString('repeat_month') }
									description={langString('repeat_month_hint') }
									options={yearlyMonths}
									value={formData.set_yearly_month}
									onChange={(name, value) => handleChange(name, value)}
									name="set_yearly_month"
								/>
							</div>

						)
					}

					{
						formData.enable_recurring_meeting &&
						(['monthly', 'yearly'].includes(formData.recurrence_option)) && isProActive() && (
							<div className="mhub-col-lg-12">
								<MhSelect
									label={langString('repeat_every') }
									description={langString('repeat_every_hint')}
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
						formData.enable_recurring_meeting && isProActive() && (

							<div className="mhub-col-lg-12">
								<div className="mhub-form-group">
									<label>
										{langString('recurrence_time')}
										<small className="description">{langString('recurrence_time_hint') }</small>
									</label>

									<div className="input-wrapper">
										<TimePicker
											onChange={(time) => handleChange('recurrence_time', time)}
											value={formData.recurrence_time}
											disableClock={true}
										/>
									</div>
								</div>
							</div>

						)
					}

					{
						!formData.enable_recurring_meeting && (
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

						)
					}

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('should_register') }
							description={langString('should_register_help')}
							checked={formData.enable_should_register}
							onChange={(name, value) => handleChange(name, value)}
							name="enable_should_register"
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhInput
							label={langString('password')}
							description={langString('password_help') }
							type="text"
							value={formData.password}
							onChange={(name, value) => handleChange(name, value)}
							name="password"
							required="no"
							maxLength={10}
							disabled={!isProActive()}
							isLocked={!isProActive()}
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
							label={langString('show_user_timezone') }
							description={langString('show_user_timezone_help') }
							checked={formData.display_time_user_zone}
							onChange={(name, value) => handleChange(name, value)}
							name="display_time_user_zone"
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhInput
							label={langString('meeting_height') }
							description={ langString('meeting_height_hint')}
							type="number"
							value={formData.height}
							onChange={(name, value) => handleChange(name, value)}
							name="height"
							required="no"
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhInput
							label={langString('meeting_width') }
							description={langString('meeting_width_hint') }
							type="number"
							value={formData.width}
							onChange={(name, value) => handleChange(name, value)}
							name="width"
							required="no"
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('yourself_muted') }
							description={langString('start_muted_audio') }
							checked={formData.start_with_audio_muted}
							onChange={(name, value) => handleChange(name, value)}
							name="start_with_audio_muted"
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('start_muted_video') }
							description={langString('start_with_muted_video') }
							checked={formData.start_with_video_muted}
							onChange={(name, value) => handleChange(name, value)}
							name="start_with_video_muted"
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('screen_sharing') }
							description={langString('screen_sharing_hint') }
							checked={formData.start_with_screen_sharing}
							onChange={(name, value) => handleChange(name, value)}
							name="start_with_screen_sharing"
						/>
					</div>

					{ (mhubMeetingsData.is_admin || (!mhubMeetingsData.is_admin && !mhub_jitsi_settings?.hide_inviting)) && (
						<div className="mhub-col-lg-12">
							<MhSwitcher
								label={langString('enable_inviting') }
								description={langString('attendee_can_invite') }
								checked={formData.enable_inviting}
								onChange={(name, value) => handleChange(name, value)}
								name="enable_inviting"
							/>
						</div>
					)}


					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('audio_only') }
							description={langString('start_audio_only') }
							name="audio_only"
							checked={formData.audio_only}
							onChange={(name, value) => handleChange(name, value)}
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('start_silent') }
							description={langString('disable_local_audio') }
							name="start_silent"
							checked={formData.start_silent}
							onChange={(name, value) => handleChange(name, value)}
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSelect
							label={langString('video_resolution') }
							description={langString('preferred_resolution')}
							options={meetingVideoResolution}
							value={formData.video_resolution}
							onChange={(name, value) => handleChange(name, value)}
							name="video_resolution"
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhInput
							label={langString('max_full_resolution')}
							description={langString('default_resolution_count') }
							type="number"
							value={formData.max_full_resolution}
							onChange={(name, value) => handleChange(name, value)}
							name="max_full_resolution"
							required="no"
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhInput
							label={langString('video_muted_after') }
							description={langString('video_muted_nth') }
							type="number"
							value={formData.video_muted_after}
							onChange={(name, value) => handleChange(name, value)}
							name="video_muted_after"
							required="no"
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhInput
							label={ langString('audio_muted_after') }
							description={langString('audio_muted_nth')}
							type="number"
							value={formData.audio_muted}
							onChange={(name, value) => handleChange(name, value)}
							name="audio_muted"
							required="no"
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('enable_recording') }
							description={langString('enable_recording_hint')}
							name="enable_recording"
							checked={formData.enable_recording}
							onChange={(name, value) => handleChange(name, value)}
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('enable_simulcast') }
							description={langString('simulcast_hint') }
							name="enable_simulcast"
							checked={formData.enable_simulcast}
							onChange={(name, value) => handleChange(name, value)}
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('enable_livestream') }
							description={langString('livestream_hint')}
							name="enable_livestreaming"
							checked={formData.enable_livestreaming}
							onChange={(name, value) => handleChange(name, value)}
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('enable_welcome') }
							description={langString('welcome_hint') }
							name="enable_welcome_page"
							checked={formData.enable_welcome_page}
							onChange={(name, value) => handleChange(name, value)}
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('enable_transcription') }
							description={langString('transcription_hint') }
							name="enable_transcription"
							checked={formData.enable_transcription}
							onChange={(name, value) => handleChange(name, value)}
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('enable_outbound') }
							description={langString('outbound_hint') }
							name="enable_outbound"
							checked={formData.enable_outbound}
							onChange={(name, value) => handleChange(name, value)}
							disabled={!isProActive()}
							isLocked={!isProActive()}
						/>
					</div>

					<div className="mhub-col-lg-12">
						<MhSwitcher
							label={langString('hide_sidebar') }
							description={langString('hide_sidebar_help')}
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
							{isSaving ? langString('creating') : langString('create_meeting') }
						</button>
					</div>
				</form>
			</div>
		</div>
	);
};

export default JitsiForm;
