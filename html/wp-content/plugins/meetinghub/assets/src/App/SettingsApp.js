import React from "react";
import { Route, Routes } from "react-router-dom";
import JitsiSettings from "../components/settings/JitsiSettings";
import SettingsContent from "../components/settings/SettingsContent";
import ZoomSettings from "../components/settings/ZoomSettings";
import "../scss/settings/settings.scss";
import WebexSettings from "../components/settings/WebexSettings";
import 'react-toastify/dist/ReactToastify.css';
import { ToastContainer, toast } from 'react-toastify';
import { MhubAdminProvider } from "./MhubAdminContext";
import GetProModal from "../components/common/GetProModal";
import GoogleMeetSettings from "../components/settings/GoogleMeetSettings";


function SettingsApp() {

  return (
    <>
     <MhubAdminProvider>
        <Routes>
          <Route path="/" element={<SettingsContent />} />
          <Route path="jitsi" element={<JitsiSettings />} />
          <Route path="zoom" element={<ZoomSettings />} />
          <Route path="webex" element={<WebexSettings />} />
          <Route path="google_meet" element={<GoogleMeetSettings />} />
        </Routes>
        <GetProModal />
      </MhubAdminProvider>

      <ToastContainer
        position="top-right"
        autoClose={3000}
        hideProgressBar={false}
        newestOnTop={false}
        closeOnClick
        rtl={false}
        pauseOnFocusLoss
        draggable
        pauseOnHover
        theme="light"
        style={{ marginTop: '30px' }}
      />
    </>
  );
}

export default SettingsApp;