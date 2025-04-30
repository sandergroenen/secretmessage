<?php

namespace App\Services;

use App\Domain\Dto\MessageDto;
use App\Domain\Events\MessageSentEvent;
use App\Domain\Events\MessageDecryptedAndReceivedEvent;
use App\Models\Message;
use App\Models\User;
use Exception;

class MessageBrokerService
{
    public function __construct(
        protected EncryptionService $encryptionService,
        protected KeyManagementService $keyManagementService
    ) {}

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
        // Get the recipient's and its public key
        $recipient = User::findOrFail($recipientId);
        $publicKey = $this->keyManagementService->getUserPublicKey($recipient);

        if (!$publicKey) {
            // Recipient doesn't have a public key registered
            throw new Exception('Recipient does not have a public key registered');
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

        $message->markAsRead();

        return true;
    }

    /**
     * Decrypt a message for the recipient
     *
     * @param string $identifier The message identifier
     * @param string $privateKey The recipient's private key
     * @return \App\Domain\Dto\MessageDto The decrypted message 
     */
    public function decryptMessage(string $identifier, string $privateKey): MessageDto
    {
        $message = Message::where('id', $identifier)->firstOrFail();

        // Decrypt the message content with the recipient's private key
        $decryptedContent = $this->encryptionService->decrypt($message->content, $privateKey);

        // Create a DTO with the decrypted content
        return new MessageDto(
            senderId: $message->sender_id,
            recipientId: $message->recipient_id,
            content: $message->isExpired() ? 'This message expired at: ' . $message->expires_at : $decryptedContent,
            id: $message->id,
            isRead: $message->read_at ? true : false,
            readAt: $message->read_at,
            expiresAt: $message->expires_at
        );
    }
}
