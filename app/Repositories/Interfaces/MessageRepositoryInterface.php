<?php

namespace App\Repositories\Interfaces;

interface MessageRepositoryInterface
{
    public function createMessage(array $data): mixed;
    public function findMessageById(string $id): mixed;
    public function markMessageAsRead(string $id): bool;
    public function deleteMessage(string $id): bool;
    public function getUnreadMessagesForUser(int $userId): array;
}