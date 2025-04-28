<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserKey extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'public_key',
        'private_key',
    ];

    /**
     * Get the user that owns the key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
