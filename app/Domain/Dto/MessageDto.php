<?php

namespace App\Domain\Dto;

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

    // public function __isset(String $name): bool
    // {
    //     return isset($this->jsonResponseQuote->{$name});
    // }

    // // Custom serialization method for PHP 8+
    // public function __serialize(): array
    // {
    //     // Extract all properties from QuoteJsonResponse for serialization
    //     $quoteData = [];
    //     $quoteData = [
    //         'apiName' => $this->jsonResponseQuote->apiName ?? '',
    //         'quote' => $this->jsonResponseQuote->quote ?? '',
    //         'author' => $this->jsonResponseQuote->author ?? '',
    //         'timeTaken' => $this->jsonResponseQuote->timeTaken ?? 0.0,
    //         'user' => $this->jsonResponseQuote->user ?? '',
    //         'error' => $this->jsonResponseQuote->error ?? false,
    //         'errorMessage' => $this->jsonResponseQuote->errorMessage ?? null,
    //         'isFastest' => $this->jsonResponseQuote->isFastest ?? null,
    //     ];

    //     // Return with a structure that maintains the relationship
    //     return [
    //         'quote' => $quoteData
    //     ];
    // }

    // // Custom deserialization method for PHP 8+
    // /** @phpstan-ignore-next-line */
    // public function __unserialize(array $data): void
    // {
    //     // Reconstruct QuoteJsonResponse from the serialized data
    //     if (isset($data['quote']) && is_array($data['quote'])) {
    //         $quote = $data['quote'];
    //         $this->jsonResponseQuote = new QuoteJsonResponse(
    //             $quote['apiName'] ?? '',
    //             $quote['quote'] ?? '',
    //             $quote['author'] ?? '',
    //             $quote['timeTaken'] ?? 0.0,
    //             $quote['user'] ?? '',
    //             $quote['error'] ?? false,
    //             $quote['errorMessage'] ?? null,
    //             $quote['isFastest'] ?? null
    //         );
    //     }
    // }
}
