<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached()) {
            throw new RuntimeException('Clear cached configuration before testing: php artisan config:clear');
        }

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $url = config("database.connections.{$connection}.url");
        if (filled($url) || ! (($connection === 'sqlite' && $database === ':memory:') || ($connection === 'pgsql' && $database === 'barangay_connect_test'))) {
            throw new RuntimeException('Tests may only use SQLite :memory: or PostgreSQL barangay_connect_test.');
        }

        return $app;
    }
}
