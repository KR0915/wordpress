import React from "react";
import { Link } from 'react-router-dom';
const { __ } = wp.i18n;
import { langString } from "../../Helpers";

function Header() {
	return (
		<div className="header-area">
			<div className="header-wrapper">
				<h1>{ langString('all_meetings') }</h1>
				<div className="create-btn-wrapper">
					<Link className="create-meeting-btn" to="/meeting/create"> <span className="dashicons dashicons-plus-alt mr-2"></span>{ langString('add_new') } </Link>
				</div>
			</div>
		</div>
	);
}

export default Header;
