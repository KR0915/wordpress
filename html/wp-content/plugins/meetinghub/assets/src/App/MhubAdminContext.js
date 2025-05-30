// MhubAdminContext.jsx
import React, { createContext, useContext, useState } from 'react';

// Create a context
const MhubAdminContext = createContext();

// Create a provider component
export const MhubAdminProvider = ({ children }) => {
  const [isModalOpen, setIsProModalOpen] = useState(false);

  const openProModal = () => setIsProModalOpen(true);
  const closeProModal = () => setIsProModalOpen(false);


  const contextValue = {
    isModalOpen,
    openProModal,
    closeProModal,
    // Add other functions here
  };

  return (
    <MhubAdminContext.Provider value={contextValue}>
      {children}
    </MhubAdminContext.Provider>
  );
};

// Custom hook to use the context
export const useMhubAdmin = () => useContext(MhubAdminContext);
