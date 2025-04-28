<?php

namespace App\Repositories;

use App\Models\Message;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use Carbon\Carbon;

class EloquentMessageRepository implements MessageRepositoryInterface
{
    public function createMessage(array $data): mixed
    {
        return Message::create([
            'content' => $data['content'],
            'recipient_id' => $data['recipient_id'],
            'sender_id' => $data['sender_id'],
            'expiry_type' => $data['expiry_type'],
            'expiry_time' => $data['expiry_time'] ?? null,
            // Other fields as needed
        ]);
    }

    public function findMessageById(string $id): mixed
    {
        return Message::where('id', $id)
            ->whereNull('read_at')
            ->first();
    }

    public function markMessageAsRead(string $id): bool
    {
        $message = Message::where('id', $id)->first();
        
        if (!$message) {
            return false;
        }
        
        $message->read_at = Carbon::now();
        $message->save();
        
        // If message is set to self-destruct after reading
        if ($message->expiry_type === 'read_once') {
            $this->scheduleMessageDeletion($message->id);
        }
        
        return true;
    }

    public function deleteMessage(string $id): bool
    {
        return Message::where('id', $id)->delete();
    }

    public function getUnreadMessagesForUser(int $userId): array
    {
        return Message::where('recipient_id', $userId)
            ->whereNull('read_at')
            ->where(function ($query) {
                $query->where('expiry_type', '!=', 'time_based')
                    ->orWhere(function ($q) {
                        $q->where('expiry_type', 'time_based')
                          ->where('expiry_time', '>', Carbon::now());
                    });
            })
            ->get()
            ->toArray();
    }

    // The generateUniqueIdentifier method is no longer needed as UUIDs are handled by the HasUuids trait

    private function scheduleMessageDeletion(int $messageId): void
    {
        // Logic to schedule message deletion
        // Could dispatch an event or directly schedule a job
    }
}