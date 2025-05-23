<?php

namespace App\Domain\Message\Events;

use App\Domain\Message\Dto\MessageDto;
use App\Domain\Message\Events\Interfaces\MessageEventWithBroadCastInterface;
use App\Domain\Message\Models\Message;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDecryptedAndReceivedEvent implements ShouldBroadcast, MessageEventWithBroadCastInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The message DTO instance.
     *
     * @var MessageDto
     */
    public MessageDto $message;

    /**
     * Create a new event instance.
     */
    public function __construct(MessageDto $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('messages.' . $this->message->recipientId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MessageDecryptedAndReceived';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        // Format the expiration date to include timezone information
        $expiresAt = $this->message->expiresAt;
        if ($expiresAt) {
            // Always parse as string to ensure consistent handling
            $expiresAt = \Carbon\Carbon::parse($expiresAt);
            // Format with timezone information
            $expiresAt = $expiresAt->toIso8601String();
        }

        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->senderId,
            'recipient_id' => $this->message->recipientId,
            'content' => $this->message->content,
            'expires_at' => $expiresAt,
            'is_read' => $this->message->isRead,
            'read_at' => $this->message->readAt
        ];
    }
}
