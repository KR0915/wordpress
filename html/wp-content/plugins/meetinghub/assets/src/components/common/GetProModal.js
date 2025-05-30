import React, { useState, useEffect } from 'react';
import { useMhubAdmin } from '../../App/MhubAdminContext';
import '../../scss/common/_get_pro.scss';
import CountdownTimer from './CountdownTimer';
import { langString } from '../../Helpers';

const GetProModal = () => {
  const { isModalOpen, closeProModal } = useMhubAdmin();

  const [startTime, setStartTime] = useState('2024-10-21 16:00:00');
  const [endTime, setEndTime] = useState('2024-12-10 24:00:00');
  const [showTimer, setShowTimer] = useState(false);

  // Automatically show timer if the times are valid
  const startCountdown = () => {
    const now = new Date();
    if (new Date(startTime) < new Date(endTime) && now < new Date(endTime)) {
      setShowTimer(true);
    } else {
      setShowTimer(false);
    }
  };

  useEffect(() => {
    startCountdown(); // Start countdown automatically
  }, []);

  const handleCheckout = () => {
    if (typeof mhubMeetingsData !== 'undefined') {
      // Determine the URL based on is_paying
      const url = mhubMeetingsData.is_paying
        ? mhubMeetingsData.pricing_url
        : mhubMeetingsData.checkout_url;

      if (url) {
        // If it's checkout_url, open in the same tab; otherwise, open in a new tab
        if (mhubMeetingsData.is_paying) {
          window.open(url, '_blank'); // Open in a new tab
        } else {
          window.location.href = url; // Redirect in the same tab
        }
      } else {
        console.error( langString('url_not_available') );
      }
    } else {
      console.error( langString('mhub_data_missing'));
    }
  };

  if (!isModalOpen) return null;


  return (
    <div className="mhub-modal-overlay" onClick={closeProModal}>
      <div className="mhub-modal" onClick={e => e.stopPropagation()}>
        <button className="mhub-modal-close" onClick={closeProModal}>×</button>
        <div className="mhub-modal-content">
          <h2> {langString('unlock_all_features')} 🎉</h2>
          
          {showTimer ? (
            <p className='ltd-title'> {langString('black_friday_ltd') }</p>
          ) : (
            <p> {langString('deal_dont_miss') }</p>
          )}

          {showTimer && (
            <CountdownTimer startTime={startTime} endTime={endTime} />
          )}

          {showTimer ? (
            <button className="mhub-cta-btn" onClick={handleCheckout}>
             {langString('claim') } 84% {langString('off') }
            </button>
          ) : (
            <button className="mhub-modal-button" onClick={handleCheckout}>
             {langString('get_premium') }
            </button>
          )}
  
        </div>
      </div>
    </div>
  );
};

export default GetProModal;
