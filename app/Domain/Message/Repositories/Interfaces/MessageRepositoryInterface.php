<?php

namespace App\Domain\Message\Repositories\Interfaces;

use App\Domain\Message\Dto\MessageDto;

interface MessageRepositoryInterface
{
    /**
     * Create a new message
     *
     * @param array $data The message data
     * @return MessageDto|null The created message or null if failed
     */
    public function createMessage(array $data): ?MessageDto;
    
    /**
     * Find a message by its ID
     *
     * @param string $id The message ID
     * @return MessageDto|null The message or null if not found
     */
    public function findMessageById(string $id): ?MessageDto;
    
    /**
     * Find a message by exact ID where user is the recipient
     *
     * @param string $id The message ID
     * @param int $userId The user ID
     * @return MessageDto|null The message or null if not found
     */
    public function findMessageByExactId(string $id, int $userId): ?MessageDto;
    
    /**
     * Mark a message as read
     *
     * @param string $id The message ID
     * @return bool Whether the operation was successful
     */
    public function markMessageAsRead(string $id): bool;
    
    /**
     * Delete a message
     *
     * @param string $id The message ID
     * @return bool Whether the operation was successful
     */
    public function deleteMessage(string $id): bool;
    
    /**
     * Check if a message is expired and schedule it for deletion if needed
     *
     * @param string $id The message ID
     * @return void
     */
    public function checkAndHandleExpiredMessage(string $id): void;
    
    /**
     * Decrypt a message and create a DTO
     *
     * @param string $id The message ID
     * @param string $decryptedContent The decrypted content
     * @return MessageDto The message DTO
     */
    public function createMessageDto(string $id, string $decryptedContent): MessageDto;
}
