<?php

namespace Cerbero\LazyJsonPages;

use Closure;
use Illuminate\Support\LazyCollection;

/**
 * Load items from any paginated JSON API into a lazy collection.
 *
 * @param mixed $source
 * @param Closure $config
 * @return LazyCollection
 */
function lazyJsonPages(mixed $source, Closure $config): LazyCollection
{
    return LazyCollection::fromJsonPages($source, $config);
}
