<?php

namespace Tests\Unit\Domain\Message\Services;

use PHPUnit\Framework\TestCase;
use App\Domain\Message\Services\EncryptionService;

class EncryptionServiceTest extends TestCase
{
    private EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->encryptionService = new EncryptionService();
    }

    public function test_it_can_encrypt_and_decrypt_message(): void
    {
        // Generate a key pair for testing
        $keyPair = $this->encryptionService->generateKeyPair();
        $publicKey = $keyPair['public_key'];
        $privateKey = $keyPair['private_key'];

        // Test message
        $originalMessage = 'This is a secret message';

        // Encrypt the message
        $encryptedMessage = $this->encryptionService->encrypt($originalMessage, $publicKey);

        // Verify the encrypted message is different from the original
        $this->assertNotEquals($originalMessage, $encryptedMessage);

        // Decrypt the message
        $decryptedMessage = $this->encryptionService->decrypt($encryptedMessage, $privateKey);

        // Verify the decrypted message matches the original
        $this->assertEquals($originalMessage, $decryptedMessage);
    }

    public function test_it_generates_valid_key_pairs(): void
    {
        // Generate a key pair
        $keyPair = $this->encryptionService->generateKeyPair();

        // Verify the key pair contains both keys
        $this->assertArrayHasKey('private_key', $keyPair);
        $this->assertArrayHasKey('public_key', $keyPair);

        // Verify the keys are not empty
        $this->assertNotEmpty($keyPair['private_key']);
        $this->assertNotEmpty($keyPair['public_key']);

        // Verify the keys are different
        $this->assertNotEquals($keyPair['private_key'], $keyPair['public_key']);
    }

    public function test_it_cannot_decrypt_with_wrong_key(): void
    {
        // Generate two key pairs
        $keyPair1 = $this->encryptionService->generateKeyPair();
        $keyPair2 = $this->encryptionService->generateKeyPair();

        // Test message
        $originalMessage = 'This is a secret message';

        // Encrypt with first public key
        $encryptedMessage = $this->encryptionService->encrypt($originalMessage, $keyPair1['public_key']);

        // Try to decrypt with second private key (should throw an exception)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption error');
        
        // This should throw an exception
        $this->encryptionService->decrypt($encryptedMessage, $keyPair2['private_key']);
    }
}
