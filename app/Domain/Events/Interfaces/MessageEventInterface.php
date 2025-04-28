<?php

namespace App\Domain\Events\Interfaces;
interface MessageEventInterface      
{
      function store(): bool;
      function notify(): bool;

}