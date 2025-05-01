<?php

namespace Frakt24\LaravelPHPFirestore\Support;

use Frakt24\LaravelPHPFirestore\Auth\FirestoreCredentials;
use Frakt24\LaravelPHPFirestore\Core\FirestoreService;
use Frakt24\LaravelPHPFirestore\Models\Events\ModelCreating;
use Frakt24\LaravelPHPFirestore\Models\Events\ModelUpdating;
use Frakt24\LaravelPHPFirestore\Models\FirestoreModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/firestore.php',
            'firestore'
        );

        $this->app->singleton(FirestoreService::class, function ($app) {
            $config = $app['config']['firestore'];

            $credentials = new FirestoreCredentials([
                'type' => 'service_account',
                'project_id' => $config['project_id'],
                'private_key_id' => $config['private_key_id'],
                'private_key' => $config['private_key'],
                'client_email' => $config['client_email'],
                'client_id' => $config['client_id'],
                'auth_uri' => $config['auth_uri'] ?? 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => $config['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => $config['auth_provider_x509_cert_url']
                    ?? 'https://www.googleapis.com/oauth2/v1/certs',
                'client_x509_cert_url' => $config['client_x509_cert_url']
            ]);

            return new FirestoreService($credentials, [
                'database' => $config['database'] ?? '(default)',
                'retry' => $config['retry'] ?? true,
                'timeout' => $config['timeout'] ?? 10,
                'cache' => $config['cache'] ?? true,
                'cache_ttl' => $config['cache_ttl'] ?? 3600,
            ]);
        });

        // Register the facade
        $this->app->bind('firestore', function($app) {
            return $app->make(FirestoreService::class);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                __DIR__.'/../Config/firestore.php' => config_path('firestore.php'),
            ], 'firestore-config');

            // Add stubs for models
            $this->publishes([
                __DIR__.'/../stubs/firestore-model.stub' => base_path('stubs/firestore-model.stub'),
            ], 'firestore-stubs');
        }

        // Configure models
        $this->configureModels();

        // Register macros
        $this->registerCollectionMacros();

        // Boot events
        $this->bootEvents();
    }

    /**
     * Configure global model settings from config.
     */
    protected function configureModels(): void
    {
        $config = $this->app['config']['firestore.models'];

        // Set global model configurations
        FirestoreModel::setGlobalTimestamps($config['timestamps'] ?? true);
        FirestoreModel::setGlobalSoftDeletes($config['soft_deletes'] ?? true);
        FirestoreModel::setGlobalDateFormat($config['date_format'] ?? 'Y-m-d H:i:s');
        FirestoreModel::setGlobalDateColumns([
            'created_at' => $config['created_at'] ?? 'createdAt',
            'updated_at' => $config['updated_at'] ?? 'updatedAt',
            'deleted_at' => $config['deleted_at'] ?? 'deletedAt',
        ]);
    }

    /**
     * Register collection macros.
     */
    protected function registerCollectionMacros(): void
    {
        Collection::macro('toFirestore', function () {
            return $this->map(function ($value) {
                return $value instanceof FirestoreModel ? $value->toArray() : $value;
            })->all();
        });
    }

    /**
     * Boot model events.
     */
    protected function bootEvents(): void
    {
        Event::listen(ModelCreating::class, function ($event) {
            $model = $event->model;

            if ($model->usesTimestamps()) {
                $now = now();
                $model->setAttribute($model->getCreatedAtColumn(), $now);
                $model->setAttribute($model->getUpdatedAtColumn(), $now);
            }
        });

        Event::listen(ModelUpdating::class, function ($event) {
            $model = $event->model;

            if ($model->usesTimestamps()) {
                $model->setAttribute($model->getUpdatedAtColumn(), now());
            }
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            'firestore',
            FirestoreService::class,
        ];
    }
}
