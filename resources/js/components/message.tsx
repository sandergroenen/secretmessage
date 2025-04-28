import React, { useState, useEffect } from 'react';
import axios from 'axios';

// Import the Message interface from the Inbox component
interface Message {
  id: string;
  sender_id: number;
  recipient_id: number;
  content: string;
  expires_at: string | null;
  read_at: string | null;
  created_at: string;
  updated_at: string;
  sender: {
    id: number;
    name: string;
    email: string;
  };
}

interface MessageProps {
  selectedMessage: Message | null;
}

export default function Message({ selectedMessage }: MessageProps) {
  const [decrypted, setDecrypted] = useState(false);
  const [decryptionKey, setDecryptionKey] = useState('');
  const [decryptedContent, setDecryptedContent] = useState('');
  const [timeRemaining, setTimeRemaining] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  
  // Reset state when selected message changes
  useEffect(() => {
    setDecrypted(false);
    setDecryptionKey('');
    setDecryptedContent('');
    setTimeRemaining('');
    setError('');
  }, [selectedMessage]);
  
  const handleDecrypt = async () => {
    if (!selectedMessage || !decryptionKey.trim()) {
      setError('Please enter your private key');
      return;
    }
    
    setLoading(true);
    setError('');
    
    try {
      // Call the API to decrypt the message
      const response = await axios.post('/messages/decrypt', {
        id: selectedMessage.id,
        private_key: decryptionKey,
      });
      
      setDecryptedContent(response.data.content);
      setDecrypted(true);
      
      // Start countdown for message expiry
      if (selectedMessage.expires_at) {
        startExpiryCountdown(selectedMessage.expires_at);
      }
    } catch (error) {
      console.error('Error decrypting message:', error);
      setError('Failed to decrypt message. Please check your private key.');
    } finally {
      setLoading(false);
    }
  };
  
  const startExpiryCountdown = (expiryDateString: string) => {
    const expiryDate = new Date(expiryDateString);
    
    const updateCountdown = () => {
      const now = new Date();
      
      if (expiryDate > now) {
        const diffInSeconds = Math.floor((expiryDate.getTime() - now.getTime()) / 1000);
        setTimeRemaining(`${diffInSeconds} seconds remaining`);
        
        // If less than 10 seconds, change to red text
        if (diffInSeconds <= 10) {
          document.getElementById('countdown-timer')?.classList.add('text-red-600');
        }
      } else {
        setTimeRemaining('Expired');
        clearInterval(timer);
        // Reset the message view after expiry
        setDecrypted(false);
        setDecryptionKey('');
        setDecryptedContent('');
      }
    };
    
    // Initial update
    updateCountdown();
    
    // Update every second
    const timer = setInterval(updateCountdown, 1000);
  };
  
  if (!selectedMessage) {
    return (
      <div className="p-4 h-full flex items-center justify-center">
        <p className="text-gray-500">Select a message to view its contents</p>
      </div>
    );
  }
  
  return (
    <div className="p-4 h-full">
      <h2 className="text-xl font-semibold tracking-tight mb-4">
        Message from {selectedMessage.sender?.name || 'Unknown'}
      </h2>
      
      {!decrypted ? (
        <div className="space-y-4">
          <p className="text-sm text-gray-600 dark:text-gray-400">
            This message is encrypted. Enter your private key to decrypt it.
          </p>
          <textarea
            className="w-full p-2 border rounded-md h-32 font-mono text-sm"
            placeholder="Paste your private key here"
            value={decryptionKey}
            onChange={(e) => setDecryptionKey(e.target.value)}
          />
          {error && <p className="text-red-500 text-sm">{error}</p>}
          <button 
            className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
            onClick={handleDecrypt}
            disabled={loading}
          >
            {loading ? 'Decrypting...' : 'Decrypt Message'}
          </button>
        </div>
      ) : (
        <div>
          {timeRemaining && (
            <div className="mb-4 p-2 bg-yellow-50 border border-yellow-200 rounded-md">
              <div className="flex justify-between items-center">
                <span className="text-sm font-medium">Self-destructing message:</span>
                <span id="countdown-timer" className="text-sm font-bold">{timeRemaining}</span>
              </div>
              <div className="mt-1 h-1 w-full bg-gray-200 rounded-full overflow-hidden">
                <div 
                  className="h-full bg-yellow-500" 
                  style={{ 
                    width: `${Math.min(100, parseInt(timeRemaining.split(' ')[0]) / 60 * 100)}%`,
                    transition: 'width 1s linear'
                  }}
                ></div>
              </div>
            </div>
          )}
          <div className="p-3 border rounded-md bg-gray-50 dark:bg-gray-800 whitespace-pre-wrap">
            {decryptedContent}
          </div>
        </div>
      )}
    </div>
  );
}
