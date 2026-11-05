<?php

namespace Zuber\Kit\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Zuber\Kit\KitServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application              $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [KitServiceProvider::class];
    }
}
