<?php

namespace App\Domain\Listeners;

use App\Domain\Events\MessageExpiredEvent;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ExpiredMessageListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param MessageExpiredEvent $event
     * @return void
     */
    public function handle(MessageExpiredEvent $event): void
    {
        // Retrieve the message by ID
        $message = Message::find($event->messageId);

        // Explicitly check for null
        if ($message === null) {
            Log::warning('ExpiredMessageListener: Message not found with ID: ' . $event->messageId);
            return; // Exit early if message not found
        }

        Log::info('ExpiredMessageListener: Message is expired, soft deleting message ID: ' . $event->messageId);
        $message->delete();
    }
}
