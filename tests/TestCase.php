<?php

namespace Frakt24\LaravelPHPFirestore\Tests;

use Frakt24\LaravelPHPFirestore\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Set up test environment variables
        $app['config']->set('firestore.project_id', 'test-project');
        $app['config']->set('firestore.credentials', [
            'type' => 'service_account',
            'project_id' => 'test-project',
            'private_key_id' => 'test-key-id',
            'private_key' => '-----BEGIN PRIVATE KEY-----\ntest\n-----END PRIVATE KEY-----\n',
            'client_email' => 'test@test-project.iam.gserviceaccount.com',
            'client_id' => '123456789',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/test@test-project.iam.gserviceaccount.com'
        ]);
    }
}
