import React, { useEffect, useState } from "react";
import './../scss/zoom/zoom_reports.scss';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import ZoomDailyReports from "../components/zoom_reports/ZoomDailyReports";
import Spinner from "../components/common/Spinner";
import ZoomAccountReports from "../components/zoom_reports/ZoomAccountReports";
const { __ } = wp.i18n;
import { langString } from "../Helpers";

function ZoomReportsApp() {
    const [activeTab, setActiveTab] = useState('daily');
    const [loading, setLoading] = useState(false);
    const [dailyMonthYear, setDailyMonthYear] = useState(new Date());
    const [dailyReportData, setDailyReportData] = useState({});
    const [accountReportData, setAccountReportData] = useState({});
    const [accountFromDate, setAccountFromDate] = useState(new Date());
    const [accountToDate, setAccountToDate] = useState(new Date());
    const [errorMessage, setErrorMessage] = useState('');

    const handleTabClick = (tab) => {
        setErrorMessage("");
        setActiveTab(tab);
    };

    const maxYear = new Date().getFullYear();
    const maxDate = new Date(maxYear, 11, 31);

    const handleDailyReportData = async () => {
        setLoading(true);
        setErrorMessage("");
        try {
            const month = dailyMonthYear.getMonth() + 1; // Months are zero-indexed in JS
            const year = dailyMonthYear.getFullYear();
            const response = await wp.apiFetch({
                path: `meetinghub/v2/zoom/reports?month=${month}&year=${year}`,
                method: 'GET',
            });

            if (response) {
                if (response.hasOwnProperty('error')) {
                    setErrorMessage(response.error.message);
                }

                if (response.hasOwnProperty('message')) {
                    setErrorMessage(response.message);
                }

                if (response.hasOwnProperty('dates')) {
                    setDailyReportData(response.dates);
                    setErrorMessage("");
                }
            }
            // Save the response data
        } catch (error) {
            console.error( langString('api_error'), error);
        } finally {
            setLoading(false);
        }
    };

    const handleAccountReportData = async () => {
        setLoading(true);
        setErrorMessage("");
    
        try {
            const fromDateFormatted = accountFromDate.toISOString().split('T')[0];
            const toDateFormatted = accountToDate.toISOString().split('T')[0];
    
            const response = await wp.apiFetch({
                path: `meetinghub/v2/zoom/reports/account?fromDate=${fromDateFormatted}&toDate=${toDateFormatted}`,
                method: 'GET',
            });

            if (response) {
                if (response.hasOwnProperty('error')) {
                    setErrorMessage(response.error.message);
                }
    
                if (response.hasOwnProperty('message')) {
                    setErrorMessage(response.message);
                }
    
                if (response.hasOwnProperty('users')) {
                    setAccountReportData(response.users);
                    setErrorMessage("");
                }
            }
        } catch (error) {
            console.error( langString('api_error'), error);
        } finally {
            setLoading(false);
        }
    };

    const handleCloseError = () => {
        setErrorMessage('');
    };

    return (
        <div id="mhub_zoom_reports">
            <div className="tabs-wrapper">
                <div className="tabs">
                    <div className={`tab-item ${activeTab === 'daily' ? 'active' : ''}`} onClick={() => handleTabClick('daily')}>
                        {langString('daily_report')}
                    </div>
                    <div className={`tab-item ${activeTab === 'account' ? 'active' : ''}`} onClick={() => handleTabClick('account')}>
                        {langString('account_report')}
                    </div>
                </div>
                {errorMessage && (
                    <div className="mhub_zoom_error error">
                        <h3>{errorMessage}</h3>
                        <span className="close-icon" onClick={handleCloseError}>✕</span>
                    </div>
                )}
                <div className="tab-content">
                    {activeTab === 'daily' && (
                        <div className="daily-report">
                            <div className="mhub-form-group reports-mhub-form-group">
                                <label>{langString('enter_date')}</label>
                                <div className="input-wrapper">
                                    <DatePicker
                                        selected={dailyMonthYear}
                                        onChange={(date) => setDailyMonthYear(date)}
                                        dateFormat="MM/yyyy"
                                        showMonthYearPicker
                                        className="form-control"
                                        maxDate={maxDate}
                                    />
                                </div>
                                <button className='report-show-btn' onClick={handleDailyReportData} disabled={loading}>{ langString('show')}
                                </button>
                            </div>
                        </div>
                    )}
                    {activeTab === 'account' && (
                        <div className="account-report">
                            <div className="mhub-form-group reports-mhub-form-group">
                                <label>{langString('get_account_report')}</label>
                                <div className="input-wrapper">
                                    <DatePicker
                                        selected={accountFromDate}
                                        onChange={(date) => setAccountFromDate(date)}
                                        dateFormat="MM/dd/yyyy"
                                    />
                                </div>
                                <label className="mrl-10">{langString('to')}</label>
                                <div className="input-wrapper">
                                    <DatePicker
                                        selected={accountToDate}
                                        onChange={(date) => setAccountToDate(date)}
                                        dateFormat="MM/dd/yyyy"
                                    />
                                </div>
                                <button className='report-show-btn' onClick={handleAccountReportData} disabled={loading}>{ langString('show')}
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {loading ? (
                <Spinner />
            ) : (
                activeTab === 'daily' ? (
                    dailyReportData.length > 0 ? (
                        <ZoomDailyReports dailyReportData={dailyReportData} />
                    ) : (
                        <div className='mhub-report-info'>
                            <div className="report-info-wrapper">
                                <p>{langString('select_date')}</p>
                            </div>
                        </div>
                    )
                ) : activeTab === 'account' && (
                    accountReportData.length > 0 ? (
                        <ZoomAccountReports accountReportData={accountReportData} />
                    ) : (
                        <div className='mhub-report-info'>
                            <div className="report-info-wrapper">
                                <p>{langString('select_valid_range')}</p>
                            </div>
                        </div>
                    )
                )
            )}
        </div>
    );
}

export default ZoomReportsApp;
