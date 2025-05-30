import React, { useEffect, useState } from "react";
import './../scss/zoom/zoom_recordings.scss';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import Spinner from "../components/common/Spinner";
import { zoomUsers } from "../Helpers";
import Select from 'react-select';
import { select2Styles } from "../Helpers";
import ZoomRecordings from "../components/recordings/ZoomRecordings";
const { __ } = wp.i18n;
import { langString } from "../Helpers";

function ZoomRecordingsApp() {
    const [loading, setLoading] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');
    const [selectedUser, setSelectedUser] = useState(null);
    const [recordingsData, setRecordingsData] = useState([]);
    const [recordingFromDate, setRecordingFromDate] = useState(new Date());
    const [recordingToDate, setRecordingToDate] = useState(new Date());

    const handleRecordingsData = async () => {
        setLoading(true);
        setErrorMessage("");
    
        try {
            const fromDateFormatted = recordingFromDate.toISOString().split('T')[0];
            const toDateFormatted = recordingToDate.toISOString().split('T')[0];

            if (!selectedUser) {
                setErrorMessage( langString('select_host'));
                return;
            }
    
            const response = await wp.apiFetch({
                path: `meetinghub/v2/zoom/recordings?fromDate=${fromDateFormatted}&toDate=${toDateFormatted}&host_id=${selectedUser.value}`,
                method: 'GET',
            });

            if (response && response.hasOwnProperty('meetings')) {
                setRecordingsData(response.meetings);
                if (response.total_records === 0) {
                    setErrorMessage(langString('no_recordings_range'));
                }
                
            } else {
                setRecordingsData([]);
            }

        } catch (error) {
            console.error(langString('api_error'), error);
            setErrorMessage(langString('recording_error'));
        } finally {
            setLoading(false);
        }
    };

    const handleCloseError = () => {
        setErrorMessage('');
    };

    return (
        <div id="mhub_zoom_recordings">
            {errorMessage && (
                <div className="mhub_zoom_error error recording-error">
                    <h3>{errorMessage}</h3>
                    <span className="close-icon" onClick={handleCloseError}>✕</span>
                </div>
            )}
            <div className="zoom-recordings-wrapper">
                <div className="header">
                    <div className="left-align">
                         <label>{langString('get_recording_period')}</label>
                        <div className="input-wrapper">
                            <DatePicker
                                selected={recordingFromDate}
                                onChange={setRecordingFromDate}
                                dateFormat="MM/dd/yyyy"
                            />
                        </div>
                        <label className="mrl-10">To</label>
                        <div className="input-wrapper">
                            <DatePicker
                                selected={recordingToDate}
                                onChange={setRecordingToDate}
                                dateFormat="MM/dd/yyyy"
                            />
                        </div>
                        <button 
                            className='report-show-btn' 
                            onClick={handleRecordingsData} 
                            disabled={loading}
                        >
                           {langString('show')}
                        </button>
                    </div>
                    <div className="right-align">
                        <Select
                            options={zoomUsers()}
                            onChange={setSelectedUser}
                            className="mhub-select2"
                            placeholder={langString('select_host_placeholder')}
                            styles={select2Styles()}
                        />
                    </div>
                </div>
            </div>

            {loading ? (
                <Spinner />
            ) : (
                recordingsData.length > 0 ? (
                    <ZoomRecordings recordingsData={recordingsData} />
                ) : (
                    <div className='mhub-recording-info'>
                        <div className="recording-info-wrapper">
                            <p>{langString('select_host_date') }</p>
                        </div>
                    </div>
                )
            )}
        </div>
    );
}

export default ZoomRecordingsApp;
