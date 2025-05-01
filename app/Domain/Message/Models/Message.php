<?php

namespace App\Domain\Message\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;
use App\Domain\Message\Events\MessageExpiredEvent;
use App\Domain\Message\Dto\MessageDto;
use App\Models\User;

class Message extends Model
{
    use HasFactory, SoftDeletes, HasUuids;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'message';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content',
        'recipient_id',
        'sender_id',
        'expires_at',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Get the sender of the message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the recipient of the message.
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Check if the message has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!is_null($this->expires_at) && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Mark the message as read.
     *
     * @return void
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->read_at = Carbon::now();
            $this->save();
            
        }
    }

    /**
     * Schedule the message for deletion.
     *
     * @return void
     */
    public function scheduleForDeletion(): void
    {
        event(new MessageExpiredEvent($this->id));
    }

    /**
     * Scope a query to only include messages for a specific recipient.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $recipientId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRecipient($query, $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    /**
     * Scope a query to only include messages that have not expired.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    /**
     * Scope a query to only include unread messages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Check if the message is readable by the given user.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function isReadableBy(User $user): bool
    {
        return $user->id === $this->recipient_id && !$this->isExpired();
    }
    
    /**
     * Delete the message if it is expired
     *
     * @return void
     */
    public function deleteIfExpired(): void
    {
        // Check if the message is deleted or expired
        $isDeleted = $this->deleted_at !== null;
        $isExpired = $this->isExpired();
        
        // If message is expired but not deleted, schedule it for deletion
        if ($isExpired && !$isDeleted) {
            $this->scheduleForDeletion();
        }            
      
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        // The HasUuids trait will automatically generate UUIDs for the primary key
    }
}
