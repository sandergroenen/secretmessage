<?php

namespace App\Domain\Events;

use App\Domain\Dto\MessageDto;
use App\Domain\Events\Interfaces\MessageEventInterface;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceivedEvent implements MessageEventInterface, ShouldBroadcast
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
        return 'MessageReceived';
    }

    public function store(): bool
    {
        // Find the message by id and mark it as read
        $message = Message::where('id', $this->message->id)->first();
        
        if (!$message) {
            return false;
        }
        
        // Update the message as read
        $message->is_read = true;
        $message->read_at = Carbon::now();
        $message->save();
        
        // If the message is set to self-destruct after reading, schedule it for deletion
        if ($message->expiry_type === 'read_once') {
            $message->scheduleForDeletion();
        }
        
        return true;
    }

    public function notify(): bool
    {
        // This event is already being broadcast to the 'messages' channel
        // The frontend will listen for this event to update the UI
        // For example, showing a self-destruct countdown for read_once messages
        
        // If additional notifications are needed (email, push, etc.), they could be added here
        
        return true;
    }

}
