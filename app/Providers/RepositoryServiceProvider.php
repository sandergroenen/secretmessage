<?php

namespace App\Providers;

use App\Repositories\EloquentMessageRepository;
use App\Repositories\Interfaces\MessageRepositoryInterface;
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