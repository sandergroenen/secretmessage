<?php
namespace App\Domain\Message\Services;

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\RSA\PublicKey;

class EncryptionService {
    /**
     * Encrypt a message using RSA public key
     * 
     * @param string $message The message to encrypt
     * @param string $publicKeyString The public key as a string
     * @return string The encrypted message (base64 encoded)
     */
    public function encrypt(string $message, string $publicKeyString): string {
        // Load the public key from string
        /** @var PublicKey $publicKey */
        $publicKey = RSA::load($publicKeyString);
        
        // Encrypt the message
        $ciphertext = $publicKey->encrypt($message);
        
        // Return base64 encoded ciphertext for storage/transmission
        return base64_encode((string)$ciphertext);
    }
    
    /**
     * Decrypt a message using RSA private key
     * 
     * @param string $encryptedMessage The encrypted message (base64 encoded)
     * @param string $privateKeyString The private key as a string
     * @return string The decrypted message
     */
    public function decrypt(string $encryptedMessage, string $privateKeyString): string {
        // Load the private key from string
        /** @var PrivateKey $privateKey */
        $privateKey = RSA::load($privateKeyString);
        
        // Decode the base64 encoded ciphertext
        $ciphertext = base64_decode($encryptedMessage);
        
        // Decrypt the message
        $decrypted = $privateKey->decrypt($ciphertext);
        
        // Ensure we always return a string
        return is_string($decrypted) ? $decrypted : '';
    }
    
    /**
     * Generate a new RSA key pair
     * 
     * @param int $bits The key size in bits
     * @return array<string, string> An array containing the private and public keys as strings
     */
    public function generateKeyPair(int $bits = 2048): array {
        // Create a new private key
        $privateKey = RSA::createKey($bits);
        
        // Get the public key from the private key
        $publicKey = $privateKey->getPublicKey();
        
        // Return both keys as strings
        return [
            'private_key' => $privateKey->toString('PKCS8'),
            'public_key' => $publicKey->toString('PKCS8'),
        ];
    }
}
