<?php

namespace App\Domain\Message\Events\Interfaces;

// Simple repository interface for message events that should also broadcast, forcing the implementation of the broadcastWith method to have explicit control over the data broadcasted instead of all properties present in the message model
interface MessageEventWithBroadCastInterface      
{
      /**
       * Get the data to broadcast.
       * @return array<string, mixed>
       */
      public function broadcastWith(): array;

}
