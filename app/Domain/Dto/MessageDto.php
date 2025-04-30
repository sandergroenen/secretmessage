<?php

namespace App\Domain\Dto;

//even though we have eloquent models which can do ->toArray method having a defined DTO model adds value by ensuring central place for type and attribute checking
class MessageDto
{
    public function __construct(
        public readonly int $senderId,
        public readonly int $recipientId,
        public readonly string $content,
        public readonly ?string $id = null,
        public readonly bool $isRead = false,
        public readonly ?string $readAt = null,
        public readonly ?string $expiresAt = null
    ) {
    }

    /**
     * Create a MessageDto from a Message model
     */
    public static function fromModel($message): self
    {
        return new self(
            senderId: $message->sender_id,
            recipientId: $message->recipient_id,
            content: $message->content,
            id: $message->id,
            isRead: $message->read_at ? true : false,
            readAt: $message->read_at ? $message->read_at->toIso8601String() : null,
            expiresAt: $message->expires_at ? $message->expires_at->toIso8601String() : null
        );
    }

    /**
     * Convert the DTO to an array
     */
    public function toArray(): array
    {
        return [
            'sender_id' => $this->senderId,
            'recipient_id' => $this->recipientId,
            'content' => $this->content,
            'id' => $this->id,
            'is_read' => $this->isRead,
            'read_at' => $this->readAt,
            'expires_at' => $this->expiresAt,
        ];
    }
}
