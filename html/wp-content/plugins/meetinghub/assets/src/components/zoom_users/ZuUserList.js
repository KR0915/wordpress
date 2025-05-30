import React, { useEffect, useState } from "react";
import Spinner from "../common/Spinner";
import { Link, useNavigate } from "react-router-dom";
const { __ } = wp.i18n;
import { langString } from "../../Helpers";

function ZuUserList() {
	const [users, setUsers] = useState([]);
	const [loading, setLoading] = useState(true);
	const [sortBy, setSortBy] = useState('');
	const [sortOrder, setSortOrder] = useState('asc');
	const [currentPage, setCurrentPage] = useState(1);
	const [searchQuery, setSearchQuery] = useState('');
	const itemsPerPage = 10;

	const navigate = useNavigate();

	useEffect(() => {
		const fetchUsers = async () => {
			try {
				const response = await wp.apiFetch({
					path: 'meetinghub/v2/zoom/users',
					method: 'GET',
				});

				if (response && response.users && response.users.length > 0) {
					setUsers(response.users);
				}
			} catch (error) {
				console.error( langString('api_error'), error);
			} finally {
				setLoading(false);
			}
		};
		fetchUsers();
	}, []);

	const handleSort = (column) => {
		if (sortBy === column) {
			setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
		} else {
			setSortBy(column);
			setSortOrder('asc');
		}
	};

	const getSortedUsers = () => {
		const sortedUsers = [...users].sort((a, b) => {
			const aValue = getSortableValue(a, sortBy);
			const bValue = getSortableValue(b, sortBy);

			return sortOrder === 'asc' ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
		});

		return sortedUsers;
	};

	const getSortableValue = (user, column) => {
		switch (column) {
			case 'id':
				return user.id;
			case 'email':
				return user.email;
			case 'display_name':
				return user.display_name;
			case 'user_created_at':
				return user.user_created_at;
			case 'last_login_time':
				return user.last_login_time;
			case 'last_client_version':
				return user.last_client_version;
			case 'status':
				return user.status;
			default:
				return '';
		}
	};

	const filteredUsers = getSortedUsers().filter(user =>
		user.id.toLowerCase().includes(searchQuery.toLowerCase()) ||
		user.email.toLowerCase().includes(searchQuery.toLowerCase()) ||
		user.display_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
		user.user_created_at.toLowerCase().includes(searchQuery.toLowerCase()) ||
		user.last_login_time.toLowerCase().includes(searchQuery.toLowerCase()) ||
		user.last_client_version.toLowerCase().includes(searchQuery.toLowerCase()) ||
		user.status.toLowerCase().includes(searchQuery.toLowerCase())
	);

	const indexOfLastItem = currentPage * itemsPerPage;
	const indexOfFirstItem = indexOfLastItem - itemsPerPage;
	const currentUsers = filteredUsers.slice(indexOfFirstItem, indexOfLastItem);
	const totalPages = Math.ceil(filteredUsers.length / itemsPerPage);

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
		<div className="mhub-table-wrapper">
			{users.length ? (
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

			<div  className={`${users.length ? 'mhub-has-data-lists' : 'mhub-has-no-data'}`}>
				{users.length ? (
					<div className="table-container">
						<table className="meeting-hub-table">
							<thead>
								<tr>
									<th onClick={() => handleSort('id')} className={sortBy === 'id' ? `sortable ${sortOrder}` : 'sortable'}>{langString('user_id') }</th>
									<th onClick={() => handleSort('email')} className={sortBy === 'email' ? `sortable ${sortOrder}` : 'sortable'}>{langString('email')}</th>
									<th onClick={() => handleSort('display_name')} className={sortBy === 'display_name' ? `sortable ${sortOrder}` : 'sortable'}>{langString('name') }</th>
									<th onClick={() => handleSort('user_created_at')} className={sortBy === 'user_created_at' ? `sortable ${sortOrder}` : 'sortable'}>{langString('created_on') }</th>
									<th onClick={() => handleSort('last_login_time')} className={sortBy === 'last_login_time' ? `sortable ${sortOrder}` : 'sortable'}>{langString('last_login')}</th>
									<th onClick={() => handleSort('last_client_version')} className={sortBy === 'last_client_version' ? `sortable ${sortOrder}` : 'sortable'}>{langString('last_client') }</th>
									<th onClick={() => handleSort('status')} className={sortBy === 'status' ? `sortable ${sortOrder}` : 'sortable'}>{langString('status') }</th>
								</tr>
							</thead>
							<tbody>
								{/* Map over users array and render table rows */}
								{currentUsers.map(user => (
									<tr key={user.id}>
										<td>{user.id}</td>
										<td>{user.email}</td>
										<td>{user.display_name}</td>
										<td>{user.user_created_at}</td>
										<td>{user.last_login_time}</td>
										<td>{user.last_client_version}</td>
										<td>{user.status}</td>
									</tr>
								))}
							</tbody>
						</table>
					</div>
				) : (
					<div className="empty-user-wrapper">
						<h1>{langString('no_users_found')}</h1>
						<div className="btn-wrapper">
							<Link className="create-meeting-btn" to="/user/create">
								<span className="dashicons dashicons-plus-alt2"></span>{langString('create_new_user') }
							</Link>
						</div>
					</div>

				)}
			</div>

			{!filteredUsers.length && users.length ? (
				<div className="empty-meeting-wrapper">
					<p>{langString('no_records')}</p>
				</div>
			) : ''}

			{/* Pagination */}
			{users.length > itemsPerPage && (
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
	);
}

export default ZuUserList;
