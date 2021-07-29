<?php

namespace Cerbero\LazyJsonPages;

use Illuminate\Http\Client\Response;
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
     * @param Response $source
     * @param string $path
     * @param callable|array|string|int $map
     * @return LazyCollection
     */
    public function __invoke(Response $source, string $path, $map): LazyCollection
    {
        return new LazyCollection(function () use ($source, $path, $map) {
            yield from new Source($source, $path, $map);
        });
    }
}
