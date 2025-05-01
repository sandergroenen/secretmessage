<?php
namespace App\Domain\Message\Services;

use phpseclib3\Crypt\RSA;

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
        $publicKey = RSA::load($publicKeyString);
        
        // Encrypt the message
        /** @disregard P1013  */
        $ciphertext =  $publicKey->encrypt($message);
        
        // Return base64 encoded ciphertext for storage/transmission
        return base64_encode($ciphertext);
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
        $privateKey = RSA::load($privateKeyString);
        
        // Decode the base64 encoded ciphertext
        $ciphertext = base64_decode($encryptedMessage);
        
        // Decrypt the message
        /** @disregard P1013  */
        return $privateKey->decrypt($ciphertext);
    }
    
    /**
     * Generate a new RSA key pair
     * 
     * @param int $bits The key size in bits
     * @return array An array containing the private and public keys as strings
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
