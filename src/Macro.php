<?php

namespace Cerbero\LazyJsonPages;

use Illuminate\Support\LazyCollection;

/**
 * The lazy collection macro.
 *
 */
class Macro
{
    /**
     * Load paginated items of the given JSON source in a lazy collection
     *
     * @param \Psr\Http\Message\RequestInterface|\Illuminate\Http\Client\Response $source
     * @param string $path
     * @param callable|array|string|int $config
     * @return LazyCollection
     */
    public function __invoke($source, string $path, $config): LazyCollection
    {
        return new LazyCollection(function () use ($source, $path, $config) {
            yield from new Source($source, $path, $config);
        });
    }
}
