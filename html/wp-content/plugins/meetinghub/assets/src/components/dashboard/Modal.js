import React, { useRef, useEffect } from 'react';
import '../../scss/dashboard/_modal.scss';

const Modal = ({ title, description, confirmText, onConfirm, cancelText, onCancel }) => {
	const modalRef = useRef(null);

	useEffect(
		() => {
			const handleClickOutside = (event) => {
				if (modalRef.current && !modalRef.current.contains(event.target)) {
					// Clicked outside the modal, close it
					onCancel();
				}
			};
			// Add event listener when the component mounts
			document.addEventListener('mousedown', handleClickOutside);
			// Remove event listener when the component unmounts
			return () => {
				document.removeEventListener('mousedown', handleClickOutside);
			};
		}, [onCancel]
	);

	return (
		<div className="mhub-del-modal-overlay">
			<div className="mhub-del-modal" ref={modalRef}>
				<span className="close-button" onClick={onCancel}>
					&times;
				</span>
				<div className="modal-header">
					<h2>{title}</h2>
				</div>
				<div className="modal-body">
					<p>{description}</p>
				</div>
				<div className="modal-buttons">
					<button className="cancel-button" onClick={onCancel}>
						{cancelText}
					</button>
					<button className="confirm-button" onClick={onConfirm}>
						{confirmText}
					</button>
				</div>
			</div>
		</div>
	);
};

export default Modal;
