import React from "react";
// import '../scss/dashboard/meeting.scss';
import { useEffect, useState } from "react";
import Spinner from "../components/common/Spinner";
import moment from "moment";
const { __ } = wp.i18n;
import axios from 'axios';
import { langString } from "../Helpers";

function MeetingListApp({ platform, meeting_type }) {
	const [meetings, setMeetings] = useState([]);
	const [loading, setLoading] = useState(true);
	const [sortBy, setSortBy] = useState('');
	const [sortOrder, setSortOrder] = useState('asc');
	const [currentPage, setCurrentPage] = useState(1);
	const [searchQuery, setSearchQuery] = useState('');
	const itemsPerPage = 10;

    useEffect(() => {
        const fetchData = async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'mhub_meeting_list');
                formData.append('nonce', mhub_frontend_data.nonce);

                const response = await axios.post(mhub_frontend_data.ajax_url, formData);

                if (response.data.success) {
                    if (Array.isArray(response.data.data)) {
                        const filteredMeetings = response.data.data.filter(meeting => {
                            if (platform === "zoom") {
                                return meeting.settings.selected_platform === platform && meeting.settings.meeting_type === meeting_type;
                            }
                            return meeting.settings.selected_platform === platform;
                        });
                        setMeetings(filteredMeetings);
                    }
                } else {
                    console.log(response.data.data);
                }
            } catch (error) {
                console.error( langString('error_fetching_data'), error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);


	const handleViewMeeting = (url) => {
		window.open(url, '_blank');
	};

	const handleSort = (column) => {
		if (sortBy === column) {
			setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
		} else {
			setSortBy(column);
			setSortOrder('asc');
		}
	};

	const getSortedMeetings = () => {
		const sortedMeetings = [...meetings].sort((a, b) => {
			const aValue = getSortableValue(a, sortBy);
			const bValue = getSortableValue(b, sortBy);

			return sortOrder === 'asc' ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
		});

		return sortedMeetings;
	};

	const getSortableValue = (meeting, column) => {
		switch (column) {
			case 'title':
				return meeting.title;
			case 'date':
				return moment(meeting.date).format("MMMM Do YYYY");
			default:
				return '';
		}
	};

	const filteredMeetings = getSortedMeetings().filter(meeting =>
		meeting.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
		moment(meeting.date).format("MMMM Do YYYY").toLowerCase().includes(searchQuery.toLowerCase())
	);

	const indexOfLastItem = currentPage * itemsPerPage;
	const indexOfFirstItem = indexOfLastItem - itemsPerPage;
	const currentMeetings = filteredMeetings.slice(indexOfFirstItem, indexOfLastItem);
	const totalPages = Math.ceil(filteredMeetings.length / itemsPerPage);


	const handlePageChange = (page) => {
		if (page < 1 || page > totalPages) {
			return; // If the page is out of bounds, do nothing
		}

		setCurrentPage(page);
	};

	const handleSearchChange = (event) => {
		setSearchQuery(event.target.value);
		setCurrentPage(1); // Reset current page when search query changes
	};


	if (loading) {
		return <Spinner />;
	}


	return (
        <div id="meeting_hub">
            <div className="mhub-common-dashboard">
                <div className="mhub-table-wrapper">
                {meetings.length ? (
                    <div className="search-container">
                        <input
                            type="text"
                            placeholder={ langString('search')}
                            value={searchQuery}
                            onChange={handleSearchChange}
                            className="search-input"
                        />
                    </div>
                ) : ''}

                <div className={`${meetings.length ? 'meetinghub-meeting-list' : 'meetinghub-no-meeting'}`}>
                    {meetings.length ? (
                        <div className="table-container">
                            <table className="meeting-hub-table">
                                <thead>
                                    <tr>
                                        <th onClick={() => handleSort('title')} className={sortBy === 'title' ? `sortable ${sortOrder}` : 'sortable'}>{ langString('topic')}</th>
                                        <th onClick={() => handleSort('date')} className={sortBy === 'date' ? `sortable ${sortOrder}` : 'sortable'}>{ langString('start_time')}</th>
                                        <th>{langString('actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {currentMeetings.map(({ id, title, date, settings }) => (
                                        <tr key={id}>
                                            <td>{title}</td>
                                            <td>{moment(settings.startDateTime).format("dddd, MMMM D, YYYY h:mm A")}</td>
                                            <td> 
                                                <button
													className="action-btn action-text-btn"
													key={`view-${id}`}
													onClick={() => handleViewMeeting(settings.url)}
												>
													{langString('meeting_link')}
												</button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="empty-meeting-wrapper">
                            { platform === "zoom" && meeting_type == 1 ? ( <h1>{ langString('no_webinar')}</h1>) : (<h1>{langString('no_meetings')}</h1>) }
                            
                        </div>
                    )}

                    {!filteredMeetings.length && meetings.length ? (
                        <div className="empty-meeting-wrapper">
                            <p>{langString('no_records')}</p>
                        </div>
                    ) : ''}

                    {meetings.length > itemsPerPage && (
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
        </div>
	);
}


export default MeetingListApp;

