<?php

use Illuminate\Support\LazyCollection;

if (!function_exists('lazyJsonPages')) {
    /**
     * Load the given JSON source in a lazy collection.
     *
     * @param \Psr\Http\Message\RequestInterface|\Illuminate\Http\Client\Response $source
     * @param string $path
     * @param callable|array|string|int $config
     * @return LazyCollection
     */
    function lazyJsonPages($source, string $path, $config): LazyCollection
    {
        return LazyCollection::fromJsonPages($source, $path, $config);
    }
}
