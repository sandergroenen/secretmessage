<?php

namespace App\Domain\Message\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;


class UserKey extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     */
    protected $fillable = [
        'user_id',
        'public_key',
        'private_key',
    ];

    /**
     * Get the user that owns the key.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
