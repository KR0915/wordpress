import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import "../../scss/settings/tab.scss";
import Spinner from "../common/Spinner";
import SettingIcons from "./SettingIcons";
import ConnectTab from "./google-meet/ConnectTab";
import ConfigTab from "./google-meet/ConfigTab";
import ShortcodesTab from "./google-meet/ShortcodesTab";
import { toast } from 'react-toastify';
import axios from "axios";
const { __ } = wp.i18n;
import { langString } from "../../Helpers";

function GoogleMeetSettings() {
	const storedTab = localStorage.getItem("mhub_google_meet_settings_active_tab");
	const [activeTab, setActiveTab] = useState(
		storedTab ? storedTab : "ConnectTab"
	);
	
	const [formData, setFormData] = useState({
		meeting_timezone: mhubMeetingsData.mhub_timezone,
		hide_sidebar: false,
		hide_header_footer: false,
		enable_should_register: false,
		enable_recurring_meeting: false,
		reminder_time:1,
		event_status: 'tentative',
		send_updates: 'none',
		transparency: 'transparent',
		event_visibility: 'default',
	});

	const [isLoading, setIsLoading] = useState(false);
	const [isGoogleLoading, setIsGoogleLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [saveButtonText, setSaveButtonText] = useState( langString('save_changes') );
	const [saveButtonClass, setSaveButtonClass] = useState("");
	const [fileName, setFileName] = useState("");
	const [isUploading, setIsUploading] = useState(false);
	const [isCredentialLoaded, setIsCredentialLoaded] = useState(false);
	const [isPermitted, setIsPermitted] = useState(false);
	const [error, setError] = useState(null);
	const [link, setLink] = useState("");
	const redirectUrl = mhubMeetingsData.google_meet_redirect_url;

	useEffect(() => {
		// Retrieve active tab from localStorage on component mount
		if (storedTab) {
			setActiveTab(storedTab);
		}
	}, []);

	useEffect(() => {
		const fetchGoogleMeetSettings = async () => {
		  setIsLoading(true);
		  try {
			const settingsResponse = await wp.apiFetch({
			  path: 'meetinghub/v2/settings/google_meet',
			  method: 'GET',
			});
	
			setFormData({
			  meeting_timezone: settingsResponse.meeting_timezone || formData.meeting_timezone,
			  hide_sidebar: settingsResponse.hide_sidebar || formData.hide_sidebar,
			  hide_header_footer: settingsResponse.hide_header_footer || formData.hide_header_footer,
			  reminder_time: settingsResponse.reminder_time || formData.reminder_time,
			  event_status: settingsResponse.event_status || formData.event_status,
			  send_updates: settingsResponse.send_updates || formData.send_updates,
			  transparency: settingsResponse.transparency || formData.transparency,
			  event_visibility: settingsResponse.event_visibility || formData.event_visibility,
			  enable_should_register: settingsResponse.enable_should_register || formData.enable_should_register,
			  enable_recurring_meeting: settingsResponse.enable_recurring_meeting || formData.enable_recurring_meeting,
			});
		  } catch (error) {
			console.error( langString('api_error') , error);
		  } finally {
			setIsLoading(false);
		  }
		};
	
		fetchGoogleMeetSettings();
	  }, []);

	// Fetching data
	useEffect(() => {
		const fetchData = async () => {
			try {
				setIsGoogleLoading(true);
				// Fetch credential status
				const credentialFormData = new FormData();
				credentialFormData.append("action", "mhub_check_google_meet_credential");
				credentialFormData.append("nonce", mhubMeetingsData.nonce);
				const credentialResponse = await axios.post(
					mhubMeetingsData.ajax_url,
					credentialFormData
				);
				const credentialLoaded = credentialResponse.data.success || false;
				setIsCredentialLoaded(credentialLoaded);

				// Fetch permission and link only if credential is loaded
				if (credentialLoaded) {
					const permissionFormData = new FormData();
					permissionFormData.append("action", "mhub_check_google_meet_permission");
					permissionFormData.append("nonce", mhubMeetingsData.nonce);
					const permissionResponse = await axios.post(
						mhubMeetingsData.ajax_url,
						permissionFormData
					);
					setIsPermitted(permissionResponse.data.data?.permission || false);

					const linkFormData = new FormData();
					linkFormData.append("action", "mhub_get_link");
					linkFormData.append("nonce", mhubMeetingsData.nonce);
					const linkResponse = await axios.post(
						mhubMeetingsData.ajax_url,
						linkFormData
					);
					setLink(linkResponse.data.data?.link || "");
					//setIsGoogleLoading(true);
				}
			} catch (err) {
				console.error( langString('error_fetching_data'), err);
				setError( langString('error_fetching_data') );
			} finally {
				setIsGoogleLoading(false);
			}
		};

		fetchData();
	}, []);

	const handleFileUpload = async (file) => {
			if (file?.type === "application/json") {
				setFileName(file.name);
				const formData = new FormData();
				formData.append("credential", file);
				formData.append("action", "mhub_upload_google_meet_credential");
				formData.append("_ajax_nonce", mhubMeetingsData.nonce);

				setIsUploading(true);
				try {
					const response = await axios.post(mhubMeetingsData.ajax_url, formData);
					if (response.data.success) {
						if (!link) {
							// Fetch the link after a successful upload
							try {
									const linkFormData = new FormData();
									linkFormData.append("action", "mhub_get_link");
									linkFormData.append("nonce", mhubMeetingsData.nonce);

									const linkResponse = await axios.post(
											mhubMeetingsData.ajax_url,
											linkFormData
									);

									const link = linkResponse.data.data?.link || "";
									setLink(link);

							} catch (err) {
									console.error( langString('error_fetching_link') , err);
							}
						}

						setIsCredentialLoaded(response.data.success);
						toast.success( langString('credential_uploaded') );
					}
				} catch (err) {
					toast.error( langString('credential_upload_failed') );
					console.error( langString('upload_error') , err);
					setError( langString('upload_failed_retry') );
				} finally {
					setIsUploading(false);
				}
			} else {
				alert( langString('invalid_json_file') );
			}
	};


	const handleResetCredential = async () => {
		try {
			setIsGoogleLoading(true);
				const formData = new FormData();
				formData.append("action", "mhub_reset_google_meet_credential");
				formData.append("nonce", mhubMeetingsData.nonce);

				// Send the reset request
				const response = await axios.post(mhubMeetingsData.ajax_url, formData);
				console.log(response);

				// If reset is successful, reset the credential state
				if (response.data.success) {
						setIsCredentialLoaded(false);  // Set to false after success
						setIsPermitted(false);  // Set to false after success
						setFileName("");  // Clear the filename
						toast.success( langString('credential_reset_success') );
				} else {
						setError( langString('credential_reset_failed') );
				}
		} catch (err) {
				console.error( langString('error_resetting_credential') , err);
				setError( langString('credential_reset_failed') );
		} finally {
			setIsGoogleLoading(false);
		}
	};


	const handleTabClick = (tabName) => {
		setActiveTab(tabName);
		// Store active tab in localStorage
		localStorage.setItem("mhub_google_meet_settings_active_tab", tabName);
	};

	const handleSubmit = async (event) => {
		event.preventDefault();
		setIsSaving(true);
		try {
			const response = await wp.apiFetch({
				path: "meetinghub/v2/settings/google_meet",
				method: "POST",
				data: {
					client_id: formData.client_id,
					client_secret: formData.client_secret,
					meeting_timezone: formData.meeting_timezone,
					hide_sidebar: formData.hide_sidebar,
					hide_header_footer: formData.hide_header_footer,
					reminder_time: formData.reminder_time,
					event_status: formData.event_status,
					send_updates: formData.send_updates,
					transparency: formData.transparency,
					event_visibility: formData.event_visibility,
					enable_should_register: formData.enable_should_register,
					enable_recurring_meeting: formData.enable_recurring_meeting,

				},
			});

			if (response && response.google_meet_settings_saved) {
				toast.success( langString('settings_saved') );
				setSaveButtonText( langString('saved') );
				setSaveButtonClass("saved");
				setTimeout(() => {
					setSaveButtonText( langString('save_changes') );
					setSaveButtonClass("");
				}, 2000);
			} else {
				toast.error( langString('failed_update_settings') );
			}
		} catch (error) {
			console.error( langString('api_error'), error);
		} finally {
			setIsSaving(false);
		}
	};

	const renderTabContent = () => {
		// Map tab names to corresponding components
		const tabComponents = {
			Configurations: (
				<ConfigTab formData={formData} setFormData={setFormData} isGoogleLoading={isGoogleLoading}
				isLoading={isLoading} />
			),
		};

		// Render the component for the active tab
		return tabComponents[activeTab] || null;
	};


	return (
		<div className="google-meet-settings-container">
			<Link to="/" className="back-button">
				&lt; { langString('back_to_settings') }
			</Link>

			<div className="main-wrapper">
				<div className="header">
					{SettingIcons.google_meet}
					<div className="title">{ langString('google_meet') }</div>
				</div>

				<div className="tab-wrapper">
					<div className="tab">
						<div
							className={`tab-item ${activeTab === "ConnectTab" ? "active" : ""
								}`}
							onClick={() => handleTabClick("ConnectTab")}
						>
						 { langString('connect') }

						</div>
						<div
							className={`tab-item ${activeTab === "Configurations" ? "active" : ""
								}`}
							onClick={() => handleTabClick("Configurations")}
						>
							{ langString('configurations') }
						</div>

						<div
							className={`tab-item ${activeTab === "Shortcodes" ? "active" : ""
								}`}
							onClick={() => handleTabClick("Shortcodes")}
							>
							{ langString('shortcodes') }
						</div>

					</div>
					<a
						className="create-meeting-btn"
						href={mhubMeetingsData.createMeetingUrl}
					>
						<span className="dashicons dashicons-plus-alt2"></span>
						{ langString('create_meeting') }
					</a>
				</div>

				
				<div className="google-meet-setting-form ">
					<div className="form-wrapper">
					{activeTab !== "ConnectTab" && activeTab !== "Shortcodes" && (
							<form onSubmit={handleSubmit}>
								{renderTabContent()}
								<div className="mhub-save-actions">
									<button
										type="submit"
										className={`setting-save-button ${saveButtonClass}`}
										disabled={isSaving}
									>
										{isSaving ?  langString('saving')  : saveButtonText}
									</button>
								</div>
							</form>
					)}

					{activeTab === "ConnectTab" && (
							<ConnectTab
								fileName={fileName}
								isUploading={isUploading}
								isCredentialLoaded={isCredentialLoaded}
								isPermitted={isPermitted}
								error={error}
								link={link}
								redirectUrl={redirectUrl}
								onFileUpload={handleFileUpload}
								onResetCredential={handleResetCredential}
								formData={formData} 
								setFormData={setFormData}
								isGoogleLoading={isGoogleLoading}
								isLoading={isLoading}
							/>
					)}

					{activeTab === "Shortcodes" && (
						<ShortcodesTab />
					)}

					</div>
				</div>
				
			</div>
		</div>
	);
}

export default GoogleMeetSettings;
