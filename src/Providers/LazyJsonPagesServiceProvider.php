<?php

namespace Cerbero\LazyJsonPages\Providers;

use Cerbero\LazyJsonPages\Macro;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\ServiceProvider;

/**
 * The service provider.
 *
 */
class LazyJsonPagesServiceProvider extends ServiceProvider
{
    /**
     * Execute logic after the service provider is booted.
     *
     * @return void
     */
    public function boot()
    {
        LazyCollection::macro('fromJsonPages', new Macro());
    }
}
