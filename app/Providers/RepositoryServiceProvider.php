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

        // Add other repository bindings here
    }

    public function boot(): void
    {
        //
    }
}