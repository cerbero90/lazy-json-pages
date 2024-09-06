<?php

namespace Cerbero\LazyJsonPages;

/**
 * Load items from any paginated JSON API into a lazy collection.
 */
function lazyJsonPages(mixed $source): LazyJsonPages
{
    return LazyJsonPages::from($source);
}
