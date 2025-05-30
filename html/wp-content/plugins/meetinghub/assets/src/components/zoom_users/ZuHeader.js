import React from "react";
import { Link } from 'react-router-dom';
const { __ } = wp.i18n;
import { langString } from "../../Helpers";

function ZuHeader() {
	return (
		<div className="header-area">
			<div className="header-wrapper">
				<h1>{ langString('all_users') }</h1>
				<div className="create-btn-wrapper">
					<Link className="create-user-btn" to="/user/create"> <span className="dashicons dashicons-plus-alt mr-2"></span>{langString('add_user')}</Link>
				</div>
			</div>
		</div>
	);
}

export default ZuHeader;
