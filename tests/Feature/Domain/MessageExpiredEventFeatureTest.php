<?php

namespace Tests\Feature\Domain\MessageExpiredEventFeatureTest;

use App\Domain\Message\Events\MessageExpiredEvent;
use App\Domain\Message\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MessageExpiredEventFeatureTest extends TestCase
{
    public function test_expired_message_is_deleted_when_event_is_fired()
    {
        Log::spy();
        // Create a real message
        $message = Message::create([
            'sender_id' => intVal(User::query()->whereNotNull('id')->inRandomOrder()->first()->id),
            'recipient_id' => intVal(User::query()->whereNotNull('id')->inRandomOrder()->first()->id),
            'content' => 'Test message content',
            'expires_at' => now()->subDay(), // Already expired
        ]);

        // Get the message ID before it's deleted
        $messageId = $message->id;

        // Verify the message was deleted
        $this->assertNotSoftDeleted('message', ['id' => $messageId]);

        // Dispatch the real event (no mocking)
        event(new MessageExpiredEvent($messageId));

        // Verify the message was deleted
        $this->assertSoftDeleted('message', ['id' => $messageId]);

        Log::shouldHaveReceived('info', ['ExpiredMessageListener: Message is expired, soft deleting message ID: ' . $messageId]);
    }
}
