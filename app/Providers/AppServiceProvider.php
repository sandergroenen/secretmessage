<?php

namespace App\Providers;

use App\Domain\Message\Events\MessageExpiredEvent;
use App\Domain\Message\Listeners\ExpiredMessageListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        //register event handler for all quote events
        Event::listen(
            MessageExpiredEvent::class,
            ExpiredMessageListener::class
        );
    }
}
