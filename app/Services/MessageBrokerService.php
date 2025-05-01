<?php

namespace App\Services;

use App\Domain\Dto\MessageDto;
use App\Domain\Events\MessageSentEvent;
use App\Domain\Events\MessageDecryptedAndReceivedEvent;
use App\Models\Message;
use App\Models\User;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use Exception;

class MessageBrokerService
{
    public function __construct(
        protected EncryptionService $encryptionService,
        protected KeyManagementService $keyManagementService,
        protected MessageRepositoryInterface $messageRepository
    ) {}

    /**
     * Send a message from one user to another
     *
     * @param int $senderId The ID of the sender
     * @param int $recipientId The ID of the recipient
     * @param string $content The message content (unencrypted)
     * @param string $expiresAt The timestamp when the message expires
     * @return MessageDto The created message
     */
    public function sendMessage(
        int $senderId,
        int $recipientId,
        string $content,
        string $expiresAt
    ): MessageDto {
        // Get the recipient's and its public key
        $recipient = User::findOrFail($recipientId);
        $publicKey = $this->keyManagementService->getUserPublicKey($recipient);

        if (!$publicKey) {
            // Recipient doesn't have a public key registered
            throw new Exception('Recipient does not have a public key registered');
        }

        // Encrypt the message content with the recipient's public key
        $encryptedContent = $this->encryptionService->encrypt($content, $publicKey);

        // Create the message using the repository
        $messageDto = $this->messageRepository->createMessage([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'content' => $encryptedContent,
            'expires_at' => $expiresAt,
        ]); 

        return $messageDto;
    }


    /**
     * Mark a message as read and handle self-destruction if needed
     *
     * @param string $identifier The message identifier
     * @return bool Whether the operation was successful
     */
    public function markMessageAsRead(string $identifier): bool
    {
        return $this->messageRepository->markMessageAsRead($identifier);
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
        $message = $this->messageRepository->findMessageById($identifier);
        
        if (!$message) {
            throw new Exception('Message not found');
        }

        // Decrypt the message content with the recipient's private key
        $decryptedContent = $this->encryptionService->decrypt($message->content, $privateKey);

        // Create a DTO with the decrypted content using the repository
        return $this->messageRepository->createMessageDto($identifier, $decryptedContent);
    }
}
