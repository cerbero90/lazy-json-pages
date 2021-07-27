<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJsonPages\Providers\LazyJsonPagesServiceProvider;
use Orchestra\Testbench\TestCase;

/**
 * The package test suite.
 *
 */
class LazyJsonPagesTest extends TestCase
{
    /**
     * Retrieve the package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            LazyJsonPagesServiceProvider::class,
        ];
    }
}
