<?php

namespace Cerbero\LazyJsonPages\Handlers;

use Cerbero\LazyJsonPages\Concerns\HandlesTotalPages;
use GuzzleHttp\Psr7\Uri;
use Traversable;

/**
 * The items per page handler.
 *
 */
class ItemsPerPageHandler extends AbstractHandler
{
    use HandlesTotalPages;

    /**
     * Determine whether the handler can handle the JSON API map
     *
     * @return bool
     */
    public function matches(): bool
    {
        return $this->map->perPageQuery && ($this->map->pages > 0 || $this->map->items > 0);
    }

    /**
     * Handle the JSON API map
     *
     * @return Traversable
     */
    public function handle(): Traversable
    {
        $items = $this->map->items ?? $this->map->pages * $this->map->perPage;
        $pages = $this->map->perPageOverride > 0 ? (int) ceil($items / $this->map->perPageOverride) : 0;
        $uri = Uri::withQueryValue($this->request->getUri(), $this->map->perPageQuery, $this->map->perPageOverride);

        return $this->handleByTotalPages($pages, $uri, false);
    }
}
