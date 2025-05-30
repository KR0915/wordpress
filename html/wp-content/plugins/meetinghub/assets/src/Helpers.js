const config = Object.assign({}, window.mhubMeetingsData);


export function isLicenseActive() {
	return config.license;
}

export function isProActive() {
	return config.active;
}

export function langString( key ) {
    return config.strings[key];
}

export function zoomUsers() {
    const zoomUsersData = config.zoom_users;

    const zoomUsersArray = zoomUsersData.map(user => ({
        label: `${user.display_name} (${user.email})`,
        value: user.id
    }));

    return zoomUsersArray;
}

export function zoomDefaultUserId() {
    const zoomUsersData = config.zoom_users;

    if (zoomUsersData.length > 0) {
        return zoomUsersData[0].id;
    } else {
        return null;
    }
}

export const select2Styles = () => {
    return {
        control: (provided, state) => ({
            ...provided,
            width: '400px',
            borderRadius: '8px',
            border: state.isFocused ? "1px solid #997eff" : "1px solid #d1dbe8",
            boxShadow: state.isFocused ? "none" : "none",
            cursor: 'pointer',
            '&:hover': {
                border: "1px solid #997eff",
                boxShadow: "none"
            },
            
        }),
        menu: (provided) => ({
            ...provided,
            width: '400px',
            cursor: 'pointer',
        }),
        option: (provided, state) => ({
            ...provided,
            cursor: 'pointer', // Add cursor pointer
        }),
        singleValue: (provided) => ({
            ...provided,
            cursor: 'pointer', // Add cursor pointer
        }),
    };
};

