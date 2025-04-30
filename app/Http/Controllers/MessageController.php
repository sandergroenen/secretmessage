<?php

namespace App\Http\Controllers;

use App\Domain\Events\MessageDecryptedAndReceivedEvent;
use App\Domain\Events\MessageExpiredEvent;
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
        
        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message,
        ]);
    }

    /**
     * Get a message by partial ID
     */
    public function getMessage(Request $request, $partialId)
    {
        // Ensure the partial ID is at least 5 characters long
        if (strlen($partialId) < 5) {
            return response()->json([
                'message' => null,
                'error' => 'Message ID must be at least 5 characters long',
            ], 400);
        }
        
        $user = Auth::user();
        
        // Find messages where the ID starts with the provided partial ID
        // and the user is the recipient
        $message = Message::where('id', 'like', $partialId . '%')
            ->where('recipient_id', $user->id)
            ->first();
        
        if (!$message) {
            return response()->json([
                'message' => null,
                'error' => 'No message found with that ID',
            ], 404);
        }
        
        // Load the sender relationship
        $message->load('sender:id,name,email');
        
        return response()->json([
            'message' => $message,
        ]);
    }

    /**
     * Check if a message exists by exact ID and return only the message ID
     */
    public function checkMessageId(Request $request, $messageId)
    {
        $user = Auth::user();
        
        // Find the message with the exact ID provided
        // and where the user is the recipient
        $message = Message::where('id', $messageId)
            ->where('recipient_id', $user->id)
            ->first();
        
        if (!$message) {
            return response()->json([
                'message_id' => null,
                'error' => 'No message found with that ID',
            ], 404);
        }
        
        // Only return the message ID
        return response()->json([
            'message_id' => $message->id,
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

        // Get the message to create a DTO and dispatch the event
        /**@var Message $message */
        $message = Message::where('id', $validated['id'])->firstOrFail();        
        $message->deleteIfExpired();

        // Decrypt the message content
        $messageDto = $this->messageBrokerService->decryptMessage(
            $validated['id'],
            $validated['private_key']
        );
        
        // Dispatch the event
        event(new MessageDecryptedAndReceivedEvent($messageDto));
        
        return response()->json([
            'message' => 'Message decrypted successfully',
            'content' => $messageDto->content,
        ]);
    }

    /**
     * Get a list of users (for recipient selection)
     */
    public function getLoggedInUserId()
    {
        $currentUser = Auth::user();
        
        return response()->json([
            'id' => $currentUser->id,
        ]);
    }

    /**
     * Get a list of users (for recipient selection)
     */
    public function getUsers()
    {
        $users = User::where('id', '!=', Auth::user()->id)->get(['id', 'name', 'email']);
        
        return response()->json([
            'users' => $users->toArray(),
        ]);
    }
    
    /**
     * Handle a message that has expired in the frontend
     */
    public function handleExpiredMessage(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
        ]);
        
        // Fire the MessageExpiredEvent
        event(new MessageExpiredEvent($validated['id']));
        
        return response()->json([
            'message' => 'Message expiration event triggered successfully',
        ]);
    }
}
