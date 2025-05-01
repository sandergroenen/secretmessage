<?php

namespace App\Domain\Message\Controllers;

use App\Domain\Message\Events\MessageDecryptedAndReceivedEvent;
use App\Models\User;
use App\Domain\Message\Repositories\Interfaces\MessageRepositoryInterface;
use App\Domain\Message\Services\KeyManagementService;
use App\Domain\Message\Services\MessageBrokerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class MessageController extends Controller
{
    public function __construct(
        protected KeyManagementService $keyManagementService,
        protected MessageBrokerService $messageBrokerService,
        protected MessageRepositoryInterface $messageRepository
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
     * Get a message by ID
     */
    public function getMessage(Request $request, $messageId)
    {
        $user = Auth::user();
        
        // Find the message with the exact ID provided
        // and where the user is the recipient
        $message = $this->messageRepository->findMessageByExactId($messageId, $user->id);
        
        if (!$message) {
            return response()->json([
                'message' => null,
                'error' => 'No message found with that ID',
            ], 404);
        }
        
        // We can't load relationships on DTOs, so we'll need to include sender info in the DTO
        // or fetch it separately if needed
        
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
        $message = $this->messageRepository->findMessageByExactId($messageId, $user->id);
        
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
        $success = $this->messageRepository->markMessageAsRead($id);
        
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

        // Get the message using the repository
        $message = $this->messageRepository->findMessageById($validated['id']);
        
        if (!$message) {
            return response()->json([
                'message' => 'Message not found',
                'error' => 'No message found with that ID',
            ], 404);
        }
        
        // Check if the message is expired
        $this->messageRepository->checkAndHandleExpiredMessage($validated['id']);

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
        
        // Get the message using the repository
        $message = $this->messageRepository->findMessageById($validated['id']);
        
        if (!$message) {
            return response()->json([
                'message' => 'Message not found',
                'error' => 'No message found with that ID',
            ], 404);
        }
        
        // Check if the message is expired and handle it
        $this->messageRepository->checkAndHandleExpiredMessage($validated['id']);
        
        return response()->json([
            'message' => 'Message expiration handled successfully',
        ]);
    }
}
