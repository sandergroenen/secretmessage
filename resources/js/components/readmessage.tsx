import React, { useState } from 'react';
import axios from 'axios';

// No props needed as message selection is handled via broadcasting
export default function ReadMessage() {
  const [messageId, setMessageId] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [showDecryptPopup, setShowDecryptPopup] = useState(false);
  const [privateKey, setPrivateKey] = useState('');
  const [foundMessageId, setFoundMessageId] = useState<string | null>(null);
  const [decryptError, setDecryptError] = useState('');
  
  const handleReadMessage = async () => {
    if (!messageId.trim()) {
      setError('Please enter the complete message ID');
      return;
    }
    
    setLoading(true);
    setError('');
    
    try {
      const response = await axios.get(`/message/check/${messageId}`);
      
      if (response.data.message_id) {
        setFoundMessageId(response.data.message_id);
        setShowDecryptPopup(true);
      } else {
        setError('No message found with that ID');
      }
    } catch (error) {
      console.error('Error fetching message:', error);
      setError('Failed to find message. Please check the ID and try again.');
    } finally {
      setLoading(false);
    }
  };
  
  const handleDecrypt = async () => {
    if (!privateKey.trim()) {
      setDecryptError('Please enter your private key');
      return;
    }
    
    setLoading(true);
    setDecryptError('');
    
    try {
      // Call the API to decrypt the message
      // The server will now broadcast the decrypted content via WebSockets
      await axios.post('/message/decrypt', {
        id: foundMessageId,
        private_key: privateKey,
      });
      
      // Mark message as read
      await axios.post(`/message/${foundMessageId}/markasread`);
      
      // The decrypted content will be received via WebSockets in the Message component directly
      
      // Close the popup
      setShowDecryptPopup(false);
      setPrivateKey('');
      
      console.log('Decryption request sent, message will be updated via WebSockets');
    } catch (error) {
      console.error('Error decrypting message:', error);
      setDecryptError('Failed to decrypt message. Please check your private key.');
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <div className="p-4 h-full overflow-auto">
      <h2 className="text-lg font-semibold mb-4">Read Secret Message</h2>
      
      {error && (
        <div className="mb-4 p-3 bg-red-100 text-red-800 rounded-md">
          {error}
        </div>
      )}
      
      <div className="space-y-4">
        <div>
          <label className="block text-sm font-medium mb-1">Message ID</label>
          <input
            type="text"
            className="w-full p-2 border rounded-md"
            placeholder="Enter the complete message ID"
            value={messageId}
            onChange={(e) => setMessageId(e.target.value)}
            disabled={loading}
          />
          <p className="text-xs text-gray-500 mt-1">
            Enter the complete message ID you received
          </p>
        </div>
        
        <button
          className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 w-full disabled:opacity-50 disabled:cursor-not-allowed"
          onClick={handleReadMessage}
          disabled={loading || !messageId.trim()}
        >
          {loading ? 'Loading...' : 'Read Message'}
        </button>
      </div>
      
      {/* Decrypt Popup */}
      {showDecryptPopup && foundMessageId && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
            <h3 className="text-lg font-semibold mb-4">
              Enter Private Key to Decrypt Message
            </h3>
            
            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
              This message is encrypted. Enter your private key to decrypt it.
            </p>
            
            <textarea
              className="w-full p-2 border rounded-md h-32 font-mono text-sm mb-4"
              placeholder="Paste your private key here"
              value={privateKey}
              onChange={(e) => setPrivateKey(e.target.value)}
              disabled={loading}
            />
            
            {decryptError && (
              <p className="text-red-500 text-sm mb-4">{decryptError}</p>
            )}
            
            <div className="flex justify-end space-x-2">
              <button
                className="px-4 py-2 border rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                onClick={() => {
                  setShowDecryptPopup(false);
                  setPrivateKey('');
                  setDecryptError('');
                }}
                disabled={loading}
              >
                Cancel
              </button>
              
              <button
                className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                onClick={handleDecrypt}
                disabled={loading || !privateKey.trim()}
              >
                {loading ? 'Decrypting...' : 'Decrypt Message'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
