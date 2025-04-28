import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import Inbox from '@/components/inbox';
import Message from '@/components/message';
import NewMessage from '@/components/newmessage';
import AppLayout from '@/layouts/app-layout';
import { useState, useEffect } from 'react';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    hasKeys: boolean;
}

interface Message {
    id: number;
    identifier: string;
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

export default function Dashboard({ hasKeys }: DashboardProps) {
    const [selectedMessage, setSelectedMessage] = useState<Message | null>(null);
    const [showKeyModal, setShowKeyModal] = useState(!hasKeys);
    const [privateKey, setPrivateKey] = useState('');
    const [publicKey, setPublicKey] = useState('');
    const [generatingKeys, setGeneratingKeys] = useState(false);
    const [keysGenerated, setKeysGenerated] = useState(false);
    
    // Generate keys if user doesn't have them yet
    const generateKeys = async () => {
        if (generatingKeys) return;
        
        setGeneratingKeys(true);
        try {
            const response = await axios.post('/keys/generate');
            setPrivateKey(response.data.private_key);
            setPublicKey(response.data.public_key);
            setKeysGenerated(true);
        } catch (error) {
            console.error('Error generating keys:', error);
        } finally {
            setGeneratingKeys(false);
        }
    };
    
    // Handle message selection from Inbox
    const handleSelectMessage = (message: Message) => {
        setSelectedMessage(message);
    };
    
    // Close the key modal
    const closeKeyModal = () => {
        // Only allow closing if keys have been generated or user already has keys
        if (keysGenerated || hasKeys) {
            setShowKeyModal(false);
        }
    };
    
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            
            {/* Key Generation Modal */}
            {showKeyModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg max-w-2xl w-full max-h-[90vh] overflow-auto">
                        <h2 className="text-xl font-bold mb-4">Encryption Keys</h2>
                        
                        {!keysGenerated ? (
                            <div className="space-y-4">
                                <p>You need to generate encryption keys to send and receive secure messages.</p>
                                <button
                                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                    onClick={generateKeys}
                                    disabled={generatingKeys}
                                >
                                    {generatingKeys ? 'Generating...' : 'Generate Keys'}
                                </button>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="p-3 bg-yellow-100 text-yellow-800 rounded-md">
                                    <p className="font-bold">IMPORTANT: Save Your Private Key</p>
                                    <p>Your private key is only shown once and is not stored on the server. Save it securely.</p>
                                    <p>You will need it to decrypt messages sent to you.</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium mb-1">Your Private Key:</label>
                                    <textarea
                                        className="w-full p-2 border rounded-md h-32 font-mono text-sm"
                                        value={privateKey}
                                        readOnly
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium mb-1">Your Public Key:</label>
                                    <textarea
                                        className="w-full p-2 border rounded-md h-32 font-mono text-sm"
                                        value={publicKey}
                                        readOnly
                                    />
                                </div>
                                
                                <button
                                    className="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600"
                                    onClick={closeKeyModal}
                                >
                                    I've Saved My Private Key
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            )}
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-2">
                    <div className="border-sidebar-border/70 dark:border-sidebar-border relative aspect-video overflow-hidden rounded-xl border">
                        <Inbox onSelectMessage={handleSelectMessage} />
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border relative aspect-video overflow-hidden rounded-xl border">
                        <Message selectedMessage={selectedMessage} />
                    </div>
                </div>
                <div className="border-sidebar-border/70 dark:border-sidebar-border relative aspect-video overflow-hidden rounded-xl border">
                    <NewMessage />
                </div>
            </div>
        </AppLayout>
    );
}
