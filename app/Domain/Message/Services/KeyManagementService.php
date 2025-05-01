<?php

namespace App\Domain\Message\Services;

use App\Models\User;
use App\Domain\Message\Models\UserKey;

class KeyManagementService
{
    protected EncryptionService $encryptionService;
    
    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }
    
    /**
     * Generate and store a new key pair for a user
     * 
     * @param User $user The user to generate keys for
     * @return array<string, string> The generated key pair with private key for display
     */
    public function generateKeysForUser(User $user): array
    {
        // Generate a new key pair
        $keyPair = $this->encryptionService->generateKeyPair();
        
        // Store the public key in the database
        // Note: We don't store the private key as it should be kept by the user
        UserKey::updateOrCreate(
            ['user_id' => $user->id],
            ['public_key' => $keyPair['public_key']]
        );
        
        // Return both keys for display to the user
        // The private key should be displayed only once and then discarded
        return $keyPair;
    }
    
    /**
     * Get a user's public key
     * 
     * @param User $user The user to get the public key for
     * @return string|null The user's public key or null if not found
     */
    public function getUserPublicKey(User $user): ?string
    {
        $userKey = UserKey::where('user_id', $user->id)->first();
        
        return $userKey ? $userKey->public_key : null;
    }
    
    /**
     * Check if a user has a public key registered
     * 
     * @param User $user The user to check
     * @return bool Whether the user has a public key
     */
    public function hasPublicKey(User $user): bool
    {
        return UserKey::where('user_id', $user->id)->exists();
    }
}
