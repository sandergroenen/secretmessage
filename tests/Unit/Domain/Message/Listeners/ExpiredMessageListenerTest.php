<?php

namespace Tests\Unit\Domain\Message\Listeners;

use App\Domain\Message\Dto\MessageDto;
use App\Domain\Message\Events\MessageExpiredEvent;
use App\Domain\Message\Listeners\ExpiredMessageListener;
use App\Domain\Message\Repositories\Interfaces\MessageRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class ExpiredMessageListenerTest extends TestCase
{
    private ExpiredMessageListener $listener;
    /** @var MessageRepositoryInterface&Mockery\MockInterface|Mockery\PartialMock */
    private MessageRepositoryInterface $messageRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock for the message repository
        $this->messageRepository = Mockery::mock(MessageRepositoryInterface::class);
        
        // Create the listener with the mock repository
        $this->listener = new ExpiredMessageListener($this->messageRepository);
        
        // Fake the Log facade
        Log::spy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_deletes_message_when_expired(): void
    {
        // Create a message ID
        $messageId = 'test-message-id';
        
        // Create a mock message DTO
        $messageDto = new MessageDto(
            senderId: 1,
            recipientId: 2,
            content: 'Test message content',
            id: $messageId
        );
        
        // Mock the repository to return the message DTO
        $this->messageRepository->shouldReceive('findMessageById')
            ->once()
            ->with($messageId)
            ->andReturn($messageDto);
        
        // Mock the repository to delete the message
        $this->messageRepository->shouldReceive('deleteMessage')
            ->once()
            ->with($messageId)
            ->andReturn(true);
        
        // Create the event
        $event = new MessageExpiredEvent($messageId);
        
        // Handle the event
        $this->listener->handle($event);
        
        // Assert that the log was called
        Log::shouldHaveReceived('info')
            ->with('ExpiredMessageListener: Message is expired, soft deleting message ID: ' . $messageId);
            
        // Add an explicit assertion to avoid the test being marked as risky
        $this->assertTrue(true, 'Message deletion was processed');
    }

    public function test_it_logs_warning_when_message_not_found(): void
    {
        // Create a message ID
        $messageId = 'non-existent-message-id';
        
        // Mock the repository to return null (message not found)
        $this->messageRepository->shouldReceive('findMessageById')
            ->once()
            ->with($messageId)
            ->andReturn(null);
        
        // The delete method should not be called
        $this->messageRepository->shouldNotReceive('deleteMessage');
        
        // Create the event
        $event = new MessageExpiredEvent($messageId);
        
        // Handle the event
        $this->listener->handle($event);
        
        // Assert that the warning log was called
        Log::shouldHaveReceived('warning')
            ->with('ExpiredMessageListener: Message not found with ID: ' . $messageId);
            
        // Add an explicit assertion to avoid the test being marked as risky
        $this->assertTrue(true, 'Warning was logged for non-existent message');
    }
}
