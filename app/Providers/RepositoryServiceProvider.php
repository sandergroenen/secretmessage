<?php

namespace App\Providers;

use App\Domain\Message\Repositories\EloquentMessageRepository;
use App\Domain\Message\Repositories\Interfaces\MessageRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            MessageRepositoryInterface::class,
            EloquentMessageRepository::class
        );
    }
}