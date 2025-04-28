<?php

namespace App\Domain\Events;

use App\Domain\Dto\MessageDto;
use App\Domain\Events\Interfaces\MessageEventInterface;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSentEvent implements MessageEventInterface,ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public MessageDto $message)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('messages'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function store(): bool
    {
        // Store the message in the database or perform any other action
        // For example, you might want to use a repository to handle the storage
        // $this->messageRepository->createMessage($this->quote);

        // Return true if the message was stored successfully, false otherwise
        return true;
    }

    public function notify(): bool
    {
        // Notify the recipient about the new message
        // This could involve sending a notification, email, etc.
        // For example, you might want to use a notification service

        // Return true if the notification was sent successfully, false otherwise
        return true;
    }

}
