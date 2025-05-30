import React, { useState } from 'react';
import { langString } from '../../Helpers';

const ImageUploader = ({ imageUrl, setImageUrl, setImageID, label, description }) => {
    const openMediaUploader = (event) => {
        event.preventDefault(); // Prevent the default behavior
    
        const customUploader = window.wp.media({
            title: langString('choose_thumbnail'),
            button: {
                text: langString('upload_thumbnail'),
            },
            multiple: false,
        });
    
        customUploader.on('select', () => {
            const attachment = customUploader.state().get('selection').first().toJSON();
            setImageUrl(attachment.url);
            setImageID(attachment.id);
        });
    
        customUploader.open();
    };

    const resetImage = () => {
        setImageUrl('');
        setImageID('');
    };

    return (
        <div className="mhub-form-group">
            <label> {label} <small className="description">{description}</small></label>
            <div className="thumbnail-wrapper">
                <div className="meeting-thumbnail">
                    {imageUrl && (
                        <img src={imageUrl} alt={langString('meeting_thumbnail')} />
                    )}
                    { ! imageUrl && (
                        <button onClick={openMediaUploader} className='upload-thumbnail'> <span className="dashicons dashicons-cloud-upload"></span> {langString('upload_thumbnail')}</button>
                    )}
                    {imageUrl && (
                        <button onClick={resetImage} className='reset-thumbnail'> {langString('reset')}</button>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ImageUploader;
