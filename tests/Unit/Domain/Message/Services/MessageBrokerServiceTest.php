<?php

namespace Tests\Unit\Domain\Message\Services;

use App\Domain\Message\Dto\MessageDto;
use App\Domain\Message\Models\Message;
use App\Domain\Message\Repositories\Interfaces\MessageRepositoryInterface;
use App\Domain\Message\Services\EncryptionService;
use App\Domain\Message\Services\KeyManagementService;
use App\Domain\Message\Services\MessageBrokerService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Mockery;

class MessageBrokerServiceTest extends TestCase
{
    use DatabaseTransactions;

    private MessageBrokerService $messageBrokerService;
    /** @var MessageRepositoryInterface&Mockery\MockInterface|Mockery\PartialMock */
    private MessageRepositoryInterface $messageRepository;
    /** @var KeyManagementService&Mockery\MockInterface|Mockery\PartialMock */
    private KeyManagementService $keyManagementService;
    /** @var EncryptionService&Mockery\MockInterface|Mockery\PartialMock */
    private EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks for dependencies
        $this->messageRepository = Mockery::mock(MessageRepositoryInterface::class);
        $this->keyManagementService = Mockery::mock(KeyManagementService::class);
        $this->encryptionService = Mockery::mock(EncryptionService::class);
        // Create the service with mocks
        $this->messageBrokerService = new MessageBrokerService(
            $this->encryptionService,
            $this->keyManagementService,
            $this->messageRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_sends_a_message(): void
    {
        // Create sender and recipient users
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        
        // Test data
        $content = 'Test message content';
        $expiresAt = Carbon::now()->addDay();
        
        // Mock the recipient's public key
        $this->keyManagementService->shouldReceive('getUserPublicKey')
            ->once()
            ->withAnyArgs() // Use withAnyArgs instead of with($recipient) to avoid object comparison issues
            ->andReturn('recipient-public-key');
        
        // Mock the encryption
        $this->encryptionService->shouldReceive('encrypt')
            ->once()
            ->with($content, 'recipient-public-key')
            ->andReturn('encrypted-content');
        
        // Mock the message creation
        $mockMessageDto = new MessageDto(
            senderId: $sender->id,
            recipientId: $recipient->id,
            content: 'encrypted-content',
            id: 'mock-message-id',
            expiresAt: $expiresAt->toIso8601String()
        );
        
        $this->messageRepository->shouldReceive('createMessage')
            ->once()
            ->withArgs(function ($data) use ($sender, $recipient, $expiresAt) {
                return $data['sender_id'] == $sender->id &&
                       $data['recipient_id'] == $recipient->id &&
                       $data['content'] == 'encrypted-content' &&
                       $data['expires_at'] == $expiresAt;
            })
            ->andReturn($mockMessageDto);
        
        // Call the method
        $result = $this->messageBrokerService->sendMessage(
            $sender->id,
            $recipient->id,
            $content,
            $expiresAt->toDateTimeString()
        );
        
        // Assert the result is the message DTO
        $this->assertInstanceOf(MessageDto::class, $result);
        $this->assertEquals('mock-message-id', $result->id);
    }

    public function test_it_decrypts_a_message(): void
    {
        // Test data
        $messageId = 'test-message-id';
        $privateKey = 'test-private-key';
        $encryptedContent = 'encrypted-content';
        $decryptedContent = 'decrypted-content';
        
        // Create a mock message DTO instead of a Message model
        $mockMessageDto = new MessageDto(
            senderId: 1,
            recipientId: 2,
            content: $encryptedContent,
            id: $messageId
        );
        
        $this->messageRepository->shouldReceive('findMessageById')
            ->once()
            ->with($messageId)
            ->andReturn($mockMessageDto);
        
        // Mock the decryption
        $this->encryptionService->shouldReceive('decrypt')
            ->once()
            ->with($encryptedContent, $privateKey)
            ->andReturn($decryptedContent);
        
        // Mock the message DTO creation
        $mockMessageDto = new MessageDto(
            senderId: 1,
            recipientId: 2,
            content: $decryptedContent,
            id: $messageId
        );
        
        $this->messageRepository->shouldReceive('createMessageDto')
            ->once()
            ->with($messageId, $decryptedContent)
            ->andReturn($mockMessageDto);
        
        // Call the method
        $result = $this->messageBrokerService->decryptMessage($messageId, $privateKey);
        
        // Assert the result is the message DTO with decrypted content
        $this->assertInstanceOf(MessageDto::class, $result);
        $this->assertEquals($decryptedContent, $result->content);
    }
}
