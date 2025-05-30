import React, { useEffect, useState } from "react";
const { __ } = wp.i18n;
import { langString } from "../../Helpers";

function ZoomDailyReports({ dailyReportData }) {
    const [loading, setLoading] = useState(true);
    const [sortBy, setSortBy] = useState('');
    const [sortOrder, setSortOrder] = useState('asc');
    const [currentPage, setCurrentPage] = useState(1);
    const [searchQuery, setSearchQuery] = useState('');
    const itemsPerPage = 10;

    useEffect(() => {
        setLoading(false);
    }, [dailyReportData]);

    const handleSort = (column) => {
        if (sortBy === column) {
            setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
        } else {
            setSortBy(column);
            setSortOrder('asc');
        }
    };

    const getSortedData = () => {
        if (!Array.isArray(dailyReportData)) {
            return [];
        }

        const sortedData = [...dailyReportData].sort((a, b) => {
            const aValue = getSortableValue(a, sortBy);
            const bValue = getSortableValue(b, sortBy);

            if (sortOrder === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });

        return sortedData;
    };

    const getSortableValue = (item, column) => {
        switch (column) {
            case 'date':
                return item.date;
            case 'meetings':
                return item.meetings;
            case 'new_users':
                return item.new_users;
            case 'participants':
                return item.participants;
            case 'meeting_minutes':
                return item.meeting_minutes;
            default:
                return '';
        }
    };

    const filteredData = getSortedData().filter(item =>
        item.date.toLowerCase().includes(searchQuery.toLowerCase()) ||
        item.meetings.toString().toLowerCase().includes(searchQuery.toLowerCase()) ||
        item.new_users.toString().toLowerCase().includes(searchQuery.toLowerCase()) ||
        item.participants.toString().toLowerCase().includes(searchQuery.toLowerCase()) ||
        item.meeting_minutes.toString().toLowerCase().includes(searchQuery.toLowerCase())
    );

    const indexOfLastItem = currentPage * itemsPerPage;
    const indexOfFirstItem = indexOfLastItem - itemsPerPage;
    const currentData = filteredData.slice(indexOfFirstItem, indexOfLastItem);
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);

    const handlePageChange = (page) => {
        if (page < 1 || page > totalPages) {
            return;
        }
        setCurrentPage(page);
    };

    const handleSearchChange = (event) => {
        setSearchQuery(event.target.value);
        setCurrentPage(1);
    };

    return (
        <div id="meeting_hub">
            <div className="mhub-common-dashboard">
                <div className="mhub-table-wrapper">
                    {dailyReportData.length ? (
                        <div className="search-container">
                            <input
                                type="text"
                                placeholder={langString('search')}
                                value={searchQuery}
                                onChange={handleSearchChange}
                                className="search-input"
                            />
                        </div>
                    ) : ''}

                    <div className={`${dailyReportData.length ? 'mhub-has-data-lists' : 'mhub-has-no-data'}`}>
                        {dailyReportData.length ? (
                            <div className="table-container">
                                <table className="meeting-hub-table">
                                    <thead>
                                        <tr>
                                            <th onClick={() => handleSort('date')} className={sortBy === 'date' ? `sortable ${sortOrder}` : 'sortable'}>{langString('date')}</th>
                                            <th onClick={() => handleSort('meetings')} className={sortBy === 'meetings' ? `sortable ${sortOrder}` : 'sortable'}>{langString('meetings')}</th>
                                            <th onClick={() => handleSort('new_users')} className={sortBy === 'new_users' ? `sortable ${sortOrder}` : 'sortable'}>{langString('new_users')}</th>
                                            <th onClick={() => handleSort('participants')} className={sortBy === 'participants' ? `sortable ${sortOrder}` : 'sortable'}>{langString('participants')}</th>
                                            <th onClick={() => handleSort('meeting_minutes')} className={sortBy === 'meeting_minutes' ? `sortable ${sortOrder}` : 'sortable'}>{langString('meeting_minutes')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {currentData.map((item, index) => (
                                            <tr key={index}>
                                                <td>{item.date}</td>
                                                <td>{item.meetings > 0 ? <strong style={{ color: '#4300FF', fontSize: '16px' }}>{item.meetings}</strong> : 0}</td>
                                                <td>{item.new_users > 0 ? <strong style={{ color: '#00A1B5', fontSize: '16px' }}>{item.new_users}</strong> : 0}</td>
                                                <td>{item.participants > 0 ? <strong style={{ color: '#00AF00', fontSize: '16px' }}>{item.participants}</strong> : 0}</td>
                                                <td>{item.meeting_minutes > 0 ? <strong style={{ color: 'red', fontSize: '16px' }}>{item.meeting_minutes}</strong> : 0}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="empty-user-wrapper">
                               <h1>{langString('no_report_data')}</h1>
                            </div>
                        )}
                    </div>

                    {!filteredData.length && dailyReportData.length ? (
                        <div className="empty-meeting-wrapper">
                          <p>{langString('no_records')}</p>
                        </div>
                    ) : ''}

                    {dailyReportData.length > itemsPerPage && (
                        <div className="pagination">
                            <span
                                className={`page-link ${currentPage === 1 ? 'disabled' : ''}`}
                                onClick={() => handlePageChange(currentPage - 1)}
                            >
                                &lt; {langString('previous')}
                            </span>
                            {[...Array(totalPages).keys()].map((page) => (
                                <span
                                    key={page + 1}
                                    className={`page-link ${currentPage === page + 1 ? 'active' : ''}`}
                                    onClick={() => handlePageChange(page + 1)}
                                >
                                    {page + 1}
                                </span>
                            ))}
                            <span
                                className={`page-link ${currentPage === totalPages ? 'disabled' : ''}`}
                                onClick={() => handlePageChange(currentPage + 1)}
                            >
                                {langString('next')} &gt;
                            </span>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

export default ZoomDailyReports;
