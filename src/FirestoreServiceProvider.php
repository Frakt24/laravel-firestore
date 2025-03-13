<?php

namespace Frakt24\LaravelPHPFirestore;

use Illuminate\Support\ServiceProvider;

class FirestoreServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/firestore.php' => config_path('firestore.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/firestore.php', 'firestore');

        $this->app->singleton(FirestoreService::class, function ($app) {
            return new FirestoreService(config('firestore'));
        });

        $this->app->alias(FirestoreService::class, 'firestore');
    }
}
