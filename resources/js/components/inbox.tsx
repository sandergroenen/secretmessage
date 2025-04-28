import React, { useState, useEffect } from 'react';
import axios from 'axios';

// Define the Message interface
interface Message {
  id: string;
  sender_id: number;
  recipient_id: number;
  content: string;
  expiry_type: string;
  expiry_hours: number | null;
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

interface InboxProps {
  onSelectMessage: (message: Message) => void;
}

export default function Inbox({ onSelectMessage }: InboxProps) {
  const [messages, setMessages] = useState<Message[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedMessageId, setSelectedMessageId] = useState<string | null>(null);
  
  useEffect(() => {
    // Fetch messages from the API
    const fetchMessages = async () => {
      try {
        setLoading(true);
        const response = await axios.get('/messages');
        setMessages(response.data.messages);
      } catch (error) {
        console.error('Error fetching messages:', error);
      } finally {
        setLoading(false);
      }
    };
    
    fetchMessages();
    
    // Set up Echo for real-time updates
    if (window.Echo) {
      // Listen for new messages on the private channel
      const channel = window.Echo.private(`user.${window.userInfo?.id}`);
      
      channel.listen('.message.sent', (e: { message: Message }) => {
        setMessages(prevMessages => [e.message, ...prevMessages]);
      });
      
      channel.listen('.message.received', (e: { message: Message }) => {
        setMessages(prevMessages => 
          prevMessages.map(msg => 
            msg.id === e.message.id ? e.message : msg
          )
        );
      });
      
      return () => {
        channel.stopListening('.message.sent');
        channel.stopListening('.message.received');
      };
    }
  }, []);
  
  const handleSelectMessage = (message: Message) => {
    setSelectedMessageId(message.id);
    onSelectMessage(message);
    
    // Mark message as read if it hasn't been read yet
    if (!message.read_at) {
      axios.post(`/messages/${message.id}/read`);
    }
  };
  
  const formatTimestamp = (dateString: string) => {
    try {
      const date = new Date(dateString);
      const now = new Date();
      const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
      
      if (diffInHours < 24) {
        return diffInHours === 0 ? 'Just now' : `${diffInHours} hour${diffInHours === 1 ? '' : 's'} ago`;
      } else if (diffInHours < 48) {
        return 'Yesterday';
      } else {
        return date.toLocaleDateString();
      }
    } catch (e) {
      return 'Unknown time:' + e.message;
    }
  };
  
  return (
    <div className="p-4 h-full overflow-auto">
      <h2 className="text-lg font-semibold mb-4">Inbox</h2>
      
      {loading ? (
        <div className="flex justify-center items-center h-40">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
        </div>
      ) : messages.length === 0 ? (
        <div className="text-center text-gray-500 py-8">
          No messages in your inbox
        </div>
      ) : (
        <div className="space-y-2">
          {messages.map(message => (
            <div 
              key={message.id}
              className={`p-3 border rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer ${
                selectedMessageId === message.id ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' : ''
              } ${!message.read_at ? 'border-l-4 border-l-blue-500' : ''}`}
              onClick={() => handleSelectMessage(message)}
            >
              <div className="flex justify-between">
                <span className="font-medium">{message.sender?.name || 'Unknown'}</span>
                <span className="text-xs text-gray-500">{formatTimestamp(message.created_at)}</span>
              </div>
              <p className="text-sm text-gray-600 dark:text-gray-400 truncate mt-1">
                {message.read_at ? 'Read' : 'Unread'} - Encrypted message
              </p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
