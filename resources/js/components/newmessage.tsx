import React, { useState, useEffect } from 'react';
import axios from 'axios';

interface User {
  id: number;
  name: string;
  email: string;
}

export default function NewMessage() {
  const [users, setUsers] = useState<User[]>([]);
  const [recipientId, setRecipientId] = useState('');
  const [message, setMessage] = useState('');
  const [expirySeconds, setExpirySeconds] = useState('10');
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');
  const [loadingUsers, setLoadingUsers] = useState(true);
  const [messageId, setMessageId] = useState('');
  
  // Fetch users on component mount
  useEffect(() => {
    const fetchUsers = async () => {
      try {
        setLoadingUsers(true);
        const response = await axios.get('/users');
        setUsers(response.data.users);
      } catch (error) {
        console.error('Error fetching users:', error);
        setError('Failed to load users. Please try again later.');
      } finally {
        setLoadingUsers(false);
      }
    };
    
    fetchUsers();
  }, []);
  
  const handleSend = async () => {
    // Validate form
    if (!recipientId) {
      setError('Please select a recipient');
      return;
    }
    
    if (!message.trim()) {
      setError('Please enter a message');
      return;
    }
    
    setLoading(true);
    setError('');
    setSuccess(false);
    
    try {
      // Calculate expiry date based on selected seconds
      const expiryDate = new Date();
      expiryDate.setSeconds(expiryDate.getSeconds() + parseInt(expirySeconds));
      
      // Send the message to the API
      const response = await axios.post('/message', {
        recipient_id: recipientId,
        content: message,
        expires_at: expiryDate.toLocaleString('sv-SE').replace(' ', 'T'),
      });
      
      // Get the message ID from the response
      const messageId = response.data.data.id;
      
      // Reset form on success
      setRecipientId('');
      setMessage('');
      setExpirySeconds('10');
      setSuccess(true);
      setMessageId(messageId);
      
      // Clear success message after 10 seconds
      setTimeout(() => {
        setSuccess(false);
        setMessageId('');
      }, 10000);
    } catch (error) {
      console.error('Error sending message:', error);
      setError('Failed to send message. The recipient may not have generated encryption keys yet.');
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <div className="p-4 h-full overflow-auto">
      <h2 className="text-lg font-semibold mb-4">New Secret Message</h2>
      
      {success && (
        <div className="mb-4 p-3 bg-green-100 text-green-800 rounded-md">
          <p>Message sent successfully!</p>
          {messageId && (
            <p className="mt-1 font-mono text-sm">Message ID: {messageId}</p>
          )}
        </div>
      )}
      
      {error && (
        <div className="mb-4 p-3 bg-red-100 text-red-800 rounded-md">
          {error}
        </div>
      )}
      
      <div className="space-y-4">
        <div>
          <label className="block text-sm font-medium mb-1">Recipient</label>
          {loadingUsers ? (
            <div className="animate-pulse h-10 bg-gray-200 rounded-md"></div>
          ) : (
            <select
              className="w-full p-2 border rounded-md"
              value={recipientId}
              onChange={(e) => setRecipientId(e.target.value)}
              disabled={loading}
            >
              <option value="">Select a recipient</option>
              {users.map(user => (
                <option key={user.id} value={user.id.toString()}>
                  {user.name} ({user.email})
                </option>
              ))}
            </select>
          )}
        </div>
        
        <div>
          <label className="block text-sm font-medium mb-1">Message</label>
          <textarea
            className="w-full p-2 border rounded-md h-24"
            placeholder="Type your secret message here..."
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            disabled={loading}
          />
        </div>
        
        <div>
          <label className="block text-sm font-medium mb-1">Message Expiry Time (seconds)</label>
          <div className="mt-2">
            <select
              className="p-2 border rounded-md"
              value={expirySeconds}
              onChange={(e) => setExpirySeconds(e.target.value)}
              disabled={loading}
            >
              <option value="10">10 seconds</option>
              <option value="15">15 seconds</option>
              <option value="20">20 seconds</option>
              <option value="25">25 seconds</option>
              <option value="30">30 seconds</option>
              <option value="35">35 seconds</option>
              <option value="40">40 seconds</option>
              <option value="45">45 seconds</option>
              <option value="50">50 seconds</option>
              <option value="55">55 seconds</option>
              <option value="60">60 seconds</option>
            </select>
          </div>
        </div>
        
        <button
          className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 w-full disabled:opacity-50 disabled:cursor-not-allowed"
          onClick={handleSend}
          disabled={loading || loadingUsers}
        >
          {loading ? 'Sending...' : 'Send Secret Message'}
        </button>
      </div>
    </div>
  );
}
