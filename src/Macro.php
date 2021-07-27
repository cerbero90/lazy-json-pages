<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJsonPages\Exceptions\LazyJsonPagesException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\LazyCollection;
use Throwable;

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
            try {
                yield from new Source($source, $path, $map);
            } catch (Throwable $e) {
                throw new LazyJsonPagesException($e->getMessage(), 0, $e);
            }
        });
    }
}
