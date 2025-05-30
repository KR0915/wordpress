import React, { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import Spinner from "../common/Spinner";
import Modal from './Modal';
import moment from "moment";
import { toast } from 'react-toastify';
const { __ } = wp.i18n;
import { isProActive } from "../../Helpers";
import { langString } from "../../Helpers";


function MeetingList() {
	const [meetings, setMeetings] = useState([]);
	const [loading, setLoading] = useState(true);
	const [showModal, setShowModal] = useState(false);
	const [meetingToDelete, setMeetingToDelete] = useState(null);
	const [copyStatus, setCopyStatus] = useState(null);
	const [sortBy, setSortBy] = useState(''); // Column to be sorted
	const [sortOrder, setSortOrder] = useState('asc'); // Sort order: 'asc' or 'desc'
	const [currentPage, setCurrentPage] = useState(1);
	const [searchQuery, setSearchQuery] = useState(''); // State to store search query
	const [selectedMeetings, setSelectedMeetings] = useState([]); // State to store selected meeting IDs
	const itemsPerPage = 10; // Adjust this based on your preference
	const navigate = useNavigate();

	useEffect(() => {
		const fetchMeetings = async () => {
			try {
				const response = await wp.apiFetch({
					path: 'mhub/v1/meetings',
					method: 'GET',
				});

				if (Array.isArray(response)) {
					setMeetings(response);
				}
			} catch (error) {
				console.error( langString('api_error') , error);
			} finally {
				setLoading(false);
			}
		};
		fetchMeetings();
	}, []);

	const handleDeleteMeeting = (id) => {
		setMeetingToDelete(id);
		setShowModal(true);
	};

	const confirmDelete = async () => {
		const id = meetingToDelete;

		try {
			const response = await wp.apiFetch({
				path: `mhub/v1/meetings/${id}`,
				method: 'DELETE',
			});

			if (response.deleted) {
				toast.success( langString('meeting_deleted') );
			} else {
				toast.error( langString('meeting_delete_failed'));
			}

		} catch (error) {
			console.error( langString('api_error'), error);
		} finally {
			setShowModal(false);
			const items = meetings.filter((obj) => obj.id !== id);
			setMeetings(items);

			// Adjust current page when the data on the current page is deleted
			const updatedTotalPages = Math.ceil(items.length / itemsPerPage);
			if (currentPage > updatedTotalPages) {
				setCurrentPage(updatedTotalPages);
			}
		}
	};

	const closeProModal = () => {
		setShowModal(false);
	};

	const handleViewMeeting = (url) => {
		window.open(url, '_blank');
	};

	const handleCopyShortcode = (shortcode) => {
		const tempTextArea = document.createElement('textarea');
		tempTextArea.value = shortcode;
		document.body.appendChild(tempTextArea);

		tempTextArea.select();
		document.execCommand('copy');

		document.body.removeChild(tempTextArea);

		setCopyStatus( langString('Copied2') );

		// Reset copy status after a short delay
		setTimeout(() => setCopyStatus(null), 1500);
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
			case 'platform':
				return meeting.settings.selected_platform;
			case 'shortcode':
				return meeting.settings.selected_platform === 'zoom'
					? `[mhub-zoom-meeting id="${meeting.id}"/]`
					: `[mhub-jitsi-meeting id="${meeting.id}"/]`;
			default:
				return '';
		}
	};

	const filteredMeetings = getSortedMeetings().filter(meeting =>
		meeting.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
		moment(meeting.date).format("MMMM Do YYYY").toLowerCase().includes(searchQuery.toLowerCase()) ||
		meeting.settings.selected_platform.toLowerCase().includes(searchQuery.toLowerCase()) ||
		(meeting.settings.selected_platform === 'zoom' &&
			`[mhub-zoom-meeting id="${meeting.id}"/]`.toLowerCase().includes(searchQuery.toLowerCase())) ||
		(meeting.settings.selected_platform !== 'zoom' &&
			`[mhub-jitsi-meeting id="${meeting.id}"/]`.toLowerCase().includes(searchQuery.toLowerCase()))
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

	useEffect(() => {
		// Reset selected meetings when the page changes
		setSelectedMeetings([]);
	}, [currentPage]);

	const handleToggleAll = () => {
		const allMeetingsIds = currentMeetings.map((meeting) => meeting.id);
		const isAllSelected = selectedMeetings.length === allMeetingsIds.length;

		if (isAllSelected) {
			// Unselect all if all are currently selected
			setSelectedMeetings([]);
		} else {
			// Select all on the current pagination page
			setSelectedMeetings(allMeetingsIds);
		}
	};

	const handleDeleteSelected = async () => {
		setShowModal(true);
	};

	const confirmDeleteSelected = async () => {
		setLoading(true);
		setShowModal(false);
		try {
			// Assuming selectedMeetings is an array of meeting IDs
			const response = await wp.apiFetch({
				path: 'mhub/v1/meetings/delete-multiple',
				method: 'DELETE',
				data: {
					ids: selectedMeetings,
				},
			});


			// Optionally, you can handle the response data as needed
			if (Array.isArray(response)) {

				const deletedIndex = response.findIndex(result => result.status === 'deleted');

				// If "deleted" status is found, show the toast and return
				if (deletedIndex !== -1) {
					toast.success( langString('meetings_deleted') );
				} else {
					toast.success( langString('meetings_delete_failed') );
				}

				// Update table data after deletion
				const updatedMeetings = meetings.filter(meeting => !selectedMeetings.includes(meeting.id));
				setMeetings(updatedMeetings);

				// Adjust current page when the data on the current page is deleted
				const updatedTotalPages = Math.ceil(updatedMeetings.length / itemsPerPage);
				if (currentPage > updatedTotalPages) {
					setCurrentPage(updatedTotalPages);
				}

				// Clear selected meetings
				setSelectedMeetings([]);
			}
		} catch (error) {
			console.error( langString('api_error') , error);
		} finally {
			setLoading(false);
			setShowModal(false);
		}
	};

	if (loading) {
		return <Spinner />;
	}

	return (
		<div className="mhub-table-wrapper">
			{meetings.length ? (
				<div className="search-container">
					<input
						type="text"
						placeholder={ langString('search') }
						value={searchQuery}
						onChange={handleSearchChange}
						className="search-input"
					/>
				</div>
			) : ''}

			{selectedMeetings.length > 0 && (
				<div className="selected-meetings">
					{`${selectedMeetings.length} selected`}
					<div className="delete-wrapper">
						<i className="icon-meeting-hub-trash delete-selected-btn" onClick={handleDeleteSelected}></i>
					</div>
				</div>
			)}


			<div className={`${meetings.length ? 'meetinghub-meeting-list' : 'meetinghub-no-meeting'}`}>
				{meetings.length ? (
					<div className="table-container">
						<table className="meeting-hub-table mhub-meeting-dashboard-table">
							<thead>
								<tr>
									<th>
										{/* selectedMeetings.length > 0 */}
										<input
											type="checkbox"
											onChange={handleToggleAll}
											checked={filteredMeetings.length > 0 && selectedMeetings.length === currentMeetings.length}
											className="wcfm-checkbox"
										/>
									</th>
									<th onClick={() => handleSort('title')} className={sortBy === 'title' ? `sortable ${sortOrder}` : 'sortable'}>{ langString('topic') }</th>
									<th onClick={() => handleSort('date')} className={sortBy === 'date' ? `sortable ${sortOrder}` : 'sortable'}>{ langString('created_at') }</th>
									<th onClick={() => handleSort('platform')} className={sortBy === 'platform' ? `sortable ${sortOrder}` : 'sortable'}>{ langString('platform') }</th>
									<th onClick={() => handleSort('shortcode')} className={sortBy === 'shortcode' ? `sortable ${sortOrder}` : 'sortable'}>{ langString('shortcode') }</th>
									<th>{ langString('actions') }</th>
								</tr>
							</thead>
							<tbody>
								{currentMeetings.map(({ id, title, date, settings }) => (
									<tr key={id}>
										<td>
											<input
												type="checkbox"
												onChange={() => {
													const isSelected = selectedMeetings.includes(id);
													if (isSelected) {
														setSelectedMeetings(selectedMeetings.filter((selectedId) => selectedId !== id));
													} else {
														setSelectedMeetings([...selectedMeetings, id]);
													}
												}}
												checked={selectedMeetings.includes(id)}
												className="wcfm-checkbox"
											/>
										</td>
										<td>{title}</td>
										<td>{moment(date).format("MMMM Do YYYY")}</td>
										<td>
											{settings.selected_platform === 'jitsi_meet' ?  langString('jitsi_meet')  : settings.selected_platform === 'webex' ?  langString('webex')  : settings.selected_platform === 'google_meet' ?  langString('google_meet') :  langString('zoom') }
										</td>
										<td>
											{
												settings.selected_platform === 'zoom' ? (
													settings.meeting_type == 2 ? (
														`[mhub-zoom-meeting id="${id}" hide_details="no" hide_timer="no"${isProActive() ? ' hide_calendar="no"' : ''} /]`
													) : (
														`[mhub-zoom-webinar id="${id}" hide_details="no" hide_timer="no"${isProActive() ? ' hide_calendar="no"' : ''} /]`
													)
												) : settings.selected_platform === 'webex' ? (
													`[mhub-webex-meeting id="${id}" hide_details="no"${isProActive() ? ' hide_calendar="no"' : ''} /]`
												) : settings.selected_platform === 'google_meet' ? (
													`[mhub-google-meeting id="${id}" hide_details="no" hide_timer="no"${isProActive() ? ' hide_calendar="no"' : ''}/]`
												) : (
													`[mhub-jitsi-meeting id="${id}" hide_details="no" hide_timer="no"${isProActive() ? ' hide_calendar="no"' : ''}/]`
												)
											}

											<span
												className="shortcode-copy"
												onClick={() => handleCopyShortcode(
													settings.selected_platform === 'zoom'
														? (settings.meeting_type == 2
															? `[mhub-zoom-meeting id="${id}" hide_details="no" hide_timer="no"${isProActive() ? ' hide_calendar="no"' : ''}/]`
															: `[mhub-zoom-webinar id="${id}" hide_details="no" hide_timer="no"${isProActive() ? ' hide_calendar="no"' : ''}/]`)
													: settings.selected_platform === 'webex'
														? `[mhub-webex-meeting id="${id}" hide_details="no"${isProActive() ? ' hide_calendar="no"' : ''}/]`
													: settings.selected_platform === 'google_meet'
														? `[mhub-google-meeting id="${id}" hide_details="no" hide_timer="no"${isProActive() ? ' hide_calendar="no"' : ''}/]`
														: `[mhub-jitsi-meeting id="${id}" hide_details="no" hide_timer="no"${isProActive() ? ' hide_calendar="no"' : ''}/]`
												)}

											>
												<i className='dashicons dashicons-admin-page'></i>
											</span>

										</td>
										<td>
											<div className="action-wrapper">
												<button
													className="action-btn"
													key={`edit-${id}`}
													onClick={() => navigate(`/meeting/edit/${settings.selected_platform}/${id}`)}
												>
													<i className='icon-meeting-hub-edit'></i>
												</button>

												<button
													className="action-btn view-action-btn"
													key={`view-${id}`}
													onClick={() => handleViewMeeting(settings.url)}
												>
													<i className='icon-meeting-hub-eye'></i>
												</button>
												<button
													className="action-btn trash-action-btn"
													key={`trash-${id}`}
													onClick={() => handleDeleteMeeting(id)}
												>
													<i className='icon-meeting-hub-trash'></i>
												</button>
											</div>
										</td>
									</tr>
								))}
							</tbody>
						</table>
					</div>
				) : (
					<div className="empty-meeting-wrapper">
						<h1>{ langString('no_meetings_yet') }</h1>
						<div className="btn-wrapper">
							<Link className="create-meeting-btn" to="/meeting/create">
								<span className="dashicons dashicons-plus-alt2"></span>{ langString('create_new_meeting') }
							</Link>
						</div>
					</div>
				)}

				{!filteredMeetings.length && meetings.length ? (
					<div className="empty-meeting-wrapper">
						<p>{ langString('no_records') }</p>
					</div>
				) : ''}

				{meetings.length > itemsPerPage && (
					<div className="pagination">
						<span
							className={`page-link ${currentPage === 1 ? 'disabled' : ''}`}
							onClick={() => handlePageChange(currentPage - 1)}
						>
							&lt; { langString('previous') }

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
							{ langString('next') } &gt;
						</span>
					</div>
				)}

			</div>


			{copyStatus === 'Copied!' && (
				<span className="shortcode-copy-status">{ langString('copied_clipboard') }</span>
			)}

			{showModal && (
				<Modal
					title={selectedMeetings.length > 0 ?  langString('delete_all_meetings') :  langString('delete_meeting') }
					description={selectedMeetings.length > 0 ?  langString('confirm_delete_all') :  langString('confirm_delete_single') }
					confirmText={ langString('confirm_delete') }
					onConfirm={selectedMeetings.length > 0 ? confirmDeleteSelected : confirmDelete}
					cancelText={ langString('cancel') }
					onCancel={closeProModal}
				/>

			)}
		</div>
	);
}

export default MeetingList;
