/* eslint-disable @typescript-eslint/no-explicit-any */
import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';

export default function Message() {
  // State variables
  const [content, setContent] = useState('');
  const [userId, setUserId] = useState<number | null>(null);
  const [messageId, setMessageId] = useState<string | null>(null);
  const [expiresAt, setExpiresAt] = useState<string | null>(null);
  const [timeRemaining, setTimeRemaining] = useState<{ days: number; hours: number; minutes: number; seconds: number } | null>(null);
  const [isExpiring, setIsExpiring] = useState(false);
  const [isExpired, setIsExpired] = useState(false);
  const [contentReplaced, setContentReplaced] = useState(false);

  // Get current user ID on component mount
  useEffect(() => {
    // Fetch the current user ID from the auth endpoint
    axios.get('/user').then(response => {
      setUserId(response.data.id);
    }).catch(error => {
      console.error('Error fetching user ID:', error);
    });
  }, []);

  // Set up WebSocket listener for decrypted messages
  useEffect(() => {
    if (!userId) return;

    console.log('Setting up WebSocket listener for user ID:', userId);

    // Listen for the MessageDecryptedAndReceived event on the private channel
    // @ts-expect-error echo is defined in other import
    const channel = window.Echo.private(`messages.${userId}`);

    channel.listen('.MessageDecryptedAndReceived', (event: any) => {
      console.log('Received decrypted message event:', event);
      
      // Reset expiration-related states when receiving a new message
      setIsExpiring(false);
      setIsExpired(false);
      setContentReplaced(false);
      
      // Set content directly from the event
      setContent(event.content);
      
      // Set expiration time if available
      if (event.expires_at) {
        setExpiresAt(event.expires_at);
      } else {
        setExpiresAt(null); // Explicitly reset if not available
      }
      
      // Store the message ID
      if (event.id) {
        setMessageId(event.id);
      }
    });

    // Cleanup function
    return () => {
      channel.stopListening('.MessageDecryptedAndReceived');
    };
  }, [userId]);

  // Function to trigger the expired message event
  const triggerExpiredMessageEvent = useCallback(async () => {
    if (!messageId) return;
    
    // Start the expiration animation
    setIsExpiring(true);
    
    // After fade-out animation completes, replace content
    setTimeout(() => {
      // Format the expiration date in the same way as the backend
      const expirationDate = expiresAt ? new Date(expiresAt).toLocaleString() : 'unknown date';
      setContent(`This message expired at: ${expirationDate}`);
      setContentReplaced(true);
      
      // After a brief delay, start fade-in animation
      setTimeout(() => {
        setIsExpired(true);
      }, 100);
    }, 1000); // 1 second for the fade-out animation to complete
    
    try {
      await axios.post('/message/expired', { id: messageId });
      // Message expiration event triggered successfully
    } catch (error) {
      console.error('Error triggering message expiration event:', error);
    }
  }, [messageId, expiresAt]);

  // Countdown timer effect
  useEffect(() => {
    if (!expiresAt) return;

    const calculateTimeRemaining = () => {
      if (!expiresAt) return { days: 0, hours: 0, minutes: 0, seconds: 0 };
      
      // Parse the expiration time with timezone info
      const expirationDate = new Date(expiresAt);
      
      // Get current time
      const now = new Date();
      
      // Calculate time difference in milliseconds
      const timeDiff = expirationDate.getTime() - now.getTime();
      
      // If time difference is negative, message has expired
      if (timeDiff <= 0) {
        return { days: 0, hours: 0, minutes: 0, seconds: 0 };
      }
      
      // Calculate remaining time components
      const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
      const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);
      
      
      return { days, hours, minutes, seconds };
    };
    
    // Initial calculation
    setTimeRemaining(calculateTimeRemaining());
    
    // Update countdown every second
    const timer = setInterval(() => {
      const remaining = calculateTimeRemaining();
      setTimeRemaining(remaining);
      
      // If all values are 0 and we haven't started expiring yet, clear the interval and trigger the event
      if (remaining.days === 0 && remaining.hours === 0 && 
          remaining.minutes === 0 && remaining.seconds === 0 && !isExpiring) {
        clearInterval(timer);
        triggerExpiredMessageEvent();
      }
    }, 1000);
    
    // Cleanup interval on unmount
    return () => clearInterval(timer);
  }, [expiresAt, triggerExpiredMessageEvent, isExpiring]);

  // Helper function to format countdown
  const formatCountdown = () => {
    if (!timeRemaining) return '';
    
    const { days, hours, minutes, seconds } = timeRemaining;
    return `${days}d:${hours.toString().padStart(2, '0')}h:${minutes.toString().padStart(2, '0')}m:${seconds.toString().padStart(2, '0')}s`;
  };
  
  // Helper function to determine countdown color
  const getCountdownColor = () => {
    if (!timeRemaining) return 'text-green-500';
    
    const { days, hours, minutes, seconds } = timeRemaining;
    const totalSeconds = days * 86400 + hours * 3600 + minutes * 60 + seconds;
    
    if (totalSeconds === 0) return 'text-red-500 font-bold';
    if (totalSeconds <= 3) return 'text-orange-500';
    return 'text-green-500';
  };

  return (
    <div className="p-4 h-full">
      <h2 className="text-xl font-semibold tracking-tight mb-4">
        Secret Message
      </h2>

      {!content ? (
        <div className="space-y-4">
          <p className="text-sm text-gray-600 dark:text-gray-400">
            Waiting for message content...
          </p>
        </div>
      ) : (
        <div>
          <div className={`p-3 border rounded-md bg-gray-50 dark:bg-gray-800 whitespace-pre-wrap transition-opacity duration-1000 ${
            isExpiring && !contentReplaced ? 'opacity-0' : // Fade out original content
            contentReplaced && !isExpired ? 'opacity-0' : // Keep new content hidden during transition
            'opacity-100' // Show content (either original or replaced)
          }`}>
            {content}
          </div>
          
          {expiresAt && !isExpired && (
            <div className="mt-4">
              <p className="text-sm">
                Message expires in: <span className={`${getCountdownColor()} text-xl font-medium`}>{formatCountdown()}</span>
              </p>
              <button
                onClick={triggerExpiredMessageEvent}
                className="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
              >
                Message read...
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
