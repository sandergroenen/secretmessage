<?php

namespace App\Domain\Message\Listeners;

use App\Domain\Message\Events\MessageExpiredEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Domain\Message\Repositories\Interfaces\MessageRepositoryInterface;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ExpiredMessageListener implements ShouldQueue
{
    use InteractsWithQueue;
    
    /**
     * Create a new listener instance.
     *
     * @param MessageRepositoryInterface $messageRepository
     */
    public function __construct(
        protected MessageRepositoryInterface $messageRepository
    ) {}

    /**
     * Handle the event.
     *
     * @param MessageExpiredEvent $event
     * @return void
     */
    public function handle(MessageExpiredEvent $event): void
    {
        // Retrieve the message by ID using the repository
        $message = $this->messageRepository->findMessageById($event->messageId);

        // Explicitly check for null
        if ($message === null) {
            Log::warning('ExpiredMessageListener: Message not found with ID: ' . $event->messageId);
            return; // Exit early if message not found
        }

        Log::info('ExpiredMessageListener: Message is expired, soft deleting message ID: ' . $event->messageId);
        $this->messageRepository->deleteMessage($event->messageId);
    }
}
