<?php

namespace Tests\Unit\Domain\Message\Events;

use App\Domain\Message\Events\MessageExpiredEvent;
use Tests\TestCase;

class MessageExpiredEventTest extends TestCase
{
    public function test_it_contains_message_id(): void
    {
        // Create the event with a message ID
        $messageId = 'test-message-id';
        $event = new MessageExpiredEvent($messageId);

        // Check that the message ID is accessible
        $this->assertEquals($messageId, $event->messageId);

        // Check the broadcast data
        $data = $event->broadcastWith();
        $this->assertEquals($messageId, $data['messageId']);
    }
}
