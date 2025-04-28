<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\KeyManagementService;
use App\Services\MessageBrokerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function __construct(
        protected KeyManagementService $keyManagementService,
        protected MessageBrokerService $messageBrokerService
    ) {
    }

    /**
     * Display the messages dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $hasKeys = $this->keyManagementService->hasPublicKey($user);
        
        return Inertia::render('dashboard', [
            'hasKeys' => $hasKeys,
        ]);
    }

    /**
     * Generate a new key pair for the authenticated user
     */
    public function generateKeys(Request $request)
    {
        $user = Auth::user();
        $keyPair = $this->keyManagementService->generateKeysForUser($user);
        
        // Return only once to the user - we don't store the private key
        return response()->json([
            'message' => 'Keys generated successfully. Save your private key securely!',
            'private_key' => $keyPair['private_key'],
            'public_key' => $keyPair['public_key'],
        ]);
    }

    /**
     * Get messages for the authenticated user
     */
    public function getMessages(Request $request)
    {
        $user = Auth::user();
        $sent = $request->query('sent', false);
        
        $messages = $this->messageBrokerService->getMessagesForUser($user->id, $sent);
        
        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Send a new message
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'content' => 'required|string',
            'expires_at' => 'required|date',
        ]);
        
        $user = Auth::user();
        
        $message = $this->messageBrokerService->sendMessage(
            $user->id,
            $validated['recipient_id'],
            $validated['content'],
            $validated['expires_at']
        );
        
        if (!$message) {
            return response()->json([
                'message' => 'Failed to send message. Recipient may not have a public key registered.',
            ], 400);
        }
        
        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message,
        ]);
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(Request $request, $id)
    {
        $success = $this->messageBrokerService->markMessageAsRead($id);
        
        if (!$success) {
            return response()->json([
                'message' => 'Failed to mark message as read',
            ], 400);
        }
        
        return response()->json([
            'message' => 'Message marked as read',
        ]);
    }

    /**
     * Decrypt a message
     */
    public function decryptMessage(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'private_key' => 'required|string',
        ]);
        
        $decryptedContent = $this->messageBrokerService->decryptMessage(
            $validated['id'],
            $validated['private_key']
        );
        
        if ($decryptedContent === null) {
            return response()->json([
                'message' => 'Failed to decrypt message',
            ], 400);
        }
        
        return response()->json([
            'message' => 'Message decrypted successfully',
            'content' => $decryptedContent,
        ]);
    }

    /**
     * Get a list of users (for recipient selection)
     */
    public function getUsers()
    {
        $currentUser = Auth::user();
        $users = User::where('id', '!=', $currentUser->id)->get(['id', 'name', 'email']);
        
        return response()->json([
            'users' => $users,
        ]);
    }
}
