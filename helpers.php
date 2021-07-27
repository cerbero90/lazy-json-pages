<?php

use Illuminate\Http\Client\Response;
use Illuminate\Support\LazyCollection;

if (!function_exists('lazyJsonPages')) {
    /**
     * Load the given JSON source in a lazy collection.
     *
     * @param Response $source
     * @param string $path
     * @param callable|array|string|int $map
     * @return LazyCollection
     */
    function lazyJsonPages(Response $source, string $path, $map): LazyCollection
    {
        return LazyCollection::fromJsonPages($source, $path, $map);
    }
}
