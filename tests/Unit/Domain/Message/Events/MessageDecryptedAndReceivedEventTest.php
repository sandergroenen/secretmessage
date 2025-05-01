<?php

namespace Tests\Unit\Domain\Message\Events;

use App\Domain\Message\Dto\MessageDto;
use App\Domain\Message\Events\MessageDecryptedAndReceivedEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MessageDecryptedAndReceivedEventTest extends TestCase
{
    public function test_it_broadcasts_with_correct_data(): void
    {
        // Create a message DTO
        $messageDto = new MessageDto(
            senderId: 1,
            recipientId: 2,
            content: 'Test message content',
            id: 'test-message-id',
            isRead: false,
            readAt: null,
            expiresAt: Carbon::now()->addDay()->toIso8601String()
        );

        // Create the event
        $event = new MessageDecryptedAndReceivedEvent($messageDto);

        // Check the broadcast channel
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertEquals('private-messages.2', $channels[0]->name);

        // Check the broadcast name
        $this->assertEquals('MessageDecryptedAndReceived', $event->broadcastAs());

        // Check the broadcast data
        $data = $event->broadcastWith();
        $this->assertEquals('test-message-id', $data['id']);
        $this->assertEquals(1, $data['sender_id']);
        $this->assertEquals(2, $data['recipient_id']);
        $this->assertEquals('Test message content', $data['content']);
        $this->assertFalse($data['is_read']);
        $this->assertNull($data['read_at']);
        $this->assertNotNull($data['expires_at']);
    }

    public function test_it_formats_expiration_date_correctly(): void
    {
        // Create a message DTO with a specific expiration date
        $expirationDate = '2025-05-01T12:00:00+00:00';
        $messageDto = new MessageDto(
            senderId: 1,
            recipientId: 2,
            content: 'Test message content',
            id: 'test-message-id',
            expiresAt: $expirationDate
        );

        // Create the event
        $event = new MessageDecryptedAndReceivedEvent($messageDto);

        // Get the broadcast data
        $data = $event->broadcastWith();

        // Check that the expiration date is formatted correctly
        $this->assertNotNull($data['expires_at']);
        $this->assertStringContainsString('2025-05-01T12:00:00+00:00', $data['expires_at']);
    }
}
