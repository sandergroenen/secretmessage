<?php

namespace Tests\Unit\Domain\Message\Services;

use App\Domain\Message\Models\UserKey;
use App\Domain\Message\Services\EncryptionService;
use App\Domain\Message\Services\KeyManagementService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Mockery;

class KeyManagementServiceTest extends TestCase
{
    use DatabaseTransactions;

    /** @var KeyManagementService&Mockery\MockInterface|Mockery\PartialMock */
    private KeyManagementService $keyManagementService;
    /** @var EncryptionService&Mockery\MockInterface|Mockery\PartialMock */
    private EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock of the EncryptionService
        $this->encryptionService = Mockery::mock(EncryptionService::class);
        
        // Create the KeyManagementService with the mock
        $this->keyManagementService = new KeyManagementService($this->encryptionService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_generates_keys_for_user(): void
    {
        // Create a user
        $user = User::factory()->create();
        
        // Mock the key pair that would be generated
        $mockKeyPair = [
            'public_key' => 'mock-public-key',
            'private_key' => 'mock-private-key',
        ];
        
        // Set up the mock to return the mock key pair
        $this->encryptionService->shouldReceive('generateKeyPair')
            ->once()
            ->andReturn($mockKeyPair);
        
        // Call the method
        $keyPair = $this->keyManagementService->generateKeysForUser($user);
        
        // Assert the key pair matches what was returned
        $this->assertEquals($mockKeyPair, $keyPair);
        
        // Assert the public key was stored in the database
        $this->assertDatabaseHas('user_keys', [
            'user_id' => $user->id,
            'public_key' => 'mock-public-key',
        ]);
    }

    public function test_it_gets_user_public_key(): void
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create a user key
        UserKey::create([
            'user_id' => $user->id,
            'public_key' => 'test-public-key',
        ]);
        
        // Get the public key
        $publicKey = $this->keyManagementService->getUserPublicKey($user);
        
        // Assert it matches
        $this->assertEquals('test-public-key', $publicKey);
    }

    public function test_it_returns_null_when_user_has_no_public_key(): void
    {
        // Create a user without a key
        $user = User::factory()->create();
        
        // Get the public key
        $publicKey = $this->keyManagementService->getUserPublicKey($user);
        
        // Assert it's null
        $this->assertNull($publicKey);
    }

    public function test_it_checks_if_user_has_public_key(): void
    {
        // Create a user
        $user = User::factory()->create();
        
        // Initially, the user should not have a key
        $this->assertFalse($this->keyManagementService->hasPublicKey($user));
        
        // Create a user key
        UserKey::create([
            'user_id' => $user->id,
            'public_key' => 'test-public-key',
        ]);
        
        // Now the user should have a key
        $this->assertTrue($this->keyManagementService->hasPublicKey($user));
    }
}
