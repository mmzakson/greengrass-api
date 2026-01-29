<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\TravelPackageRepositoryInterface::class,
            \App\Repositories\Eloquent\TravelPackageRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\BookingRepositoryInterface::class,
            \App\Repositories\Eloquent\BookingRepository::class
        );

        // Add other repository bindings here
    }

    public function boot(): void
    {
        //
    }
}