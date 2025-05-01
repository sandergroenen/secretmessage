<?php

namespace App\Domain\Message\Dto;

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
     * Convert the DTO to an array
     * @return array<string, mixed>
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
