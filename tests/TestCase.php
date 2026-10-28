<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Final safety check/override
        if (app()->environment() !== 'testing') {
            throw new \Exception('Tests MUST run in the testing environment to protect production data.');
        }

        if (config('database.default') !== 'sqlite') {
            throw new \Exception('Tests MUST use the sqlite connection.');
        }

        $this->withoutVite();
    }
}
