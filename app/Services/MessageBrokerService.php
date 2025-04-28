<?php

namespace App\Services;

use App\Domain\Dto\MessageDto;
use App\Domain\Events\MessageSentEvent;
use App\Domain\Events\MessageReceivedEvent;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MessageBrokerService
{
    public function __construct(
        protected EncryptionService $encryptionService,
        protected KeyManagementService $keyManagementService
    ) {
    }
    
    /**
     * Send a message from one user to another
     *
     * @param int $senderId The ID of the sender
     * @param int $recipientId The ID of the recipient
     * @param string $content The message content (unencrypted)
     * @param string $expiresAt The timestamp when the message expires
     * @return Message|null The created message or null if failed
     */
    public function sendMessage(
        int $senderId,
        int $recipientId,
        string $content,
        string $expiresAt
    ): ?Message {
        // Get the recipient's public key
        $recipient = User::find($recipientId);
        $publicKey = $this->keyManagementService->getUserPublicKey($recipient);
        
        if (!$publicKey) {
            // Recipient doesn't have a public key registered
            return null;
        }
        
        // Encrypt the message content with the recipient's public key
        $encryptedContent = $this->encryptionService->encrypt($content, $publicKey);
        
        // Create the message
        $message = Message::create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'content' => $encryptedContent,
            'expires_at' => $expiresAt,
        ]);
        
        // Create a DTO for the event
        $messageDto = MessageDto::fromModel($message);
        
        // Dispatch the MessageSentEvent
        event(new MessageSentEvent($messageDto));
        
        return $message;
    }
    
    /**
     * Mark a message as read and handle self-destruction if needed
     *
     * @param string $identifier The message identifier
     * @return bool Whether the operation was successful
     */
    public function markMessageAsRead(string $identifier): bool
    {
        $message = Message::where('id', $identifier)->first();
        
        if (!$message) {
            return false;
        }
        
        // Create a DTO for the event
        $messageDto = MessageDto::fromModel($message);
        
        // Dispatch the MessageReceivedEvent
        event(new MessageReceivedEvent($messageDto));
        
        return true;
    }
    
    /**
     * Decrypt a message for the recipient
     *
     * @param string $identifier The message identifier
     * @param string $privateKey The recipient's private key
     * @return string|null The decrypted message content or null if failed
     */
    public function decryptMessage(string $identifier, string $privateKey): ?string
    {
        $message = Message::where('id', $identifier)->first();
        
        if (!$message) {
            return null;
        }
        
        try {
            // Decrypt the message content with the recipient's private key
            $decryptedContent = $this->encryptionService->decrypt($message->content, $privateKey);
            return $decryptedContent;
        } catch (\Exception $e) {
            // Decryption failed
            return null;
        }
    }
    
    /**
     * Get messages for a user
     *
     * @param int $userId The user ID
     * @param bool $sent Whether to get sent messages (true) or received messages (false)
     * @return \Illuminate\Database\Eloquent\Collection The messages
     */
    public function getMessagesForUser(int $userId, bool $sent = false)
    {
        $query = Message::query();
        
        if ($sent) {
            $query->where('sender_id', $userId);
        } else {
            $query->where('recipient_id', $userId);
        }
        
        // Don't include soft-deleted messages
        $query->whereNull('deleted_at');
        
        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');
        
        return $query->get();
    }
}
