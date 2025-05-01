<?php

namespace App\Domain\Message\Repositories;

use App\Domain\Message\Dto\MessageDto;
use App\Domain\Message\Models\Message;
use App\Domain\Message\Repositories\Interfaces\MessageRepositoryInterface;

class EloquentMessageRepository implements MessageRepositoryInterface
{
    /**
     * Create a new message
     *
     * @param array<string, mixed> $data The message data
     * @return MessageDto|null The created message DTO or null if failed
     */
    public function createMessage(array $data): ?MessageDto
    {
        $message = Message::create([
            'content' => $data['content'],
            'recipient_id' => $data['recipient_id'],
            'sender_id' => $data['sender_id'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);
        
        if (!$message) {
            return null;
        }
        
        return new MessageDto(
            senderId: (int)$message->sender_id,
            recipientId: (int)$message->recipient_id,
            content: $message->content,
            id: (string)$message->id,
            isRead: $message->read_at ? true : false,
            readAt: $message->read_at ? $message->read_at->toIso8601String() : null,
            expiresAt: $message->expires_at ? $message->expires_at->toIso8601String() : null
        );
    }

    /**
     * Find a message by its ID
     *
     * @param string $id The message ID
     * @return MessageDto|null The message DTO or null if not found
     */
    public function findMessageById(string $id): ?MessageDto
    {
        $message = Message::where('id', $id)->first();
        
        if (!$message) {
            return null;
        }
        
        return new MessageDto(
            senderId: (int)$message->sender_id,
            recipientId: (int)$message->recipient_id,
            content: $message->content,
            id: (string)$message->id,
            isRead: $message->read_at ? true : false,
            readAt: $message->read_at ? $message->read_at->toIso8601String() : null,
            expiresAt: $message->expires_at ? $message->expires_at->toIso8601String() : null
        );
    }
    
    /**
     * Find a message by exact ID where user is the recipient
     *
     * @param string $id The message ID
     * @param int $userId The user ID
     * @return MessageDto|null The message DTO or null if not found
     */
    public function findMessageByExactId(string $id, int $userId): ?MessageDto
    {
        $message = Message::where('id', $id)
            ->where('recipient_id', $userId)
            ->first();
            
        if (!$message) {
            return null;
        }
        
        return new MessageDto(
            senderId: (int)$message->sender_id,
            recipientId: (int)$message->recipient_id,
            content: $message->content,
            id: (string)$message->id,
            isRead: $message->read_at ? true : false,
            readAt: $message->read_at ? $message->read_at->toIso8601String() : null,
            expiresAt: $message->expires_at ? $message->expires_at->toIso8601String() : null
        );
    }
    


    /**
     * Mark a message as read
     *
     * @param string $id The message ID
     * @return bool Whether the operation was successful
     */
    public function markMessageAsRead(string $id): bool
    {
        $message = Message::where('id', $id)->first();
        
        if (!$message) {
            return false;
        }
        
        $message->markAsRead();
        
        return true;
    }

    /**
     * Delete a message
     *
     * @param string $id The message ID
     * @return bool Whether the operation was successful
     */
    public function deleteMessage(string $id): bool
    {
        return Message::where('id', $id)->delete();
    }
    
    /**
     * Check if a message is expired and schedule it for deletion if needed
     *
     * @param string $id The message ID
     * @return void
     */
    public function checkAndHandleExpiredMessage(string $id): void
    {
        $message = Message::where('id', $id)->first();
        
        if (!$message) {
            return;
        }
        
      $message->deleteIfExpired();
    }
    
    /**
     * Decrypt a message and create a DTO
     *
     * @param string $id The message ID
     * @param string $decryptedContent The decrypted content
     * @return MessageDto The message DTO
     */
    public function createMessageDto(string $id, string $decryptedContent): MessageDto
    {
        $message = Message::findOrFail($id);
        
        // Check if the message is expired
        $content = $message->isExpired() 
            ? 'This message expired at: ' . $message->expires_at 
            : $decryptedContent;
        
        return new MessageDto(
            senderId: (int)$message->sender_id,
            recipientId: (int)$message->recipient_id,
            content: $content,
            id: (string)$message->id,
            isRead: $message->read_at ? true : false,
            readAt: $message->read_at ? $message->read_at->toIso8601String() : null,
            expiresAt: $message->expires_at ? $message->expires_at->toIso8601String() : null
        );
    }

}
