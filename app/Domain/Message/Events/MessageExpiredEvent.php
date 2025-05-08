<?php

namespace App\Domain\Message\Events;

use App\Domain\Message\Events\Interfaces\MessageEventWithBroadCastInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageExpiredEvent implements MessageEventWithBroadCastInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $messageId The ID of the expired message (which will be sent along with the event for the listener to use)
     */
    public function __construct(public string $messageId)
    {
        //
    }

    
    /**
     * Get the data to broadcast.
     * 
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'messageId' => $this->messageId
        ];
    }
}
