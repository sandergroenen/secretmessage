<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Add authorization for the private messages channel
Broadcast::channel('messages.{recipientId}', function ($user, $recipientId) {
    return (int) $user->id === (int) $recipientId;
});
