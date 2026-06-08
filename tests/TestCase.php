<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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
    }
}
