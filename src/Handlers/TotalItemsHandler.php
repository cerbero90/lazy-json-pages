<?php

namespace Cerbero\LazyJsonPages\Handlers;

use Cerbero\LazyJsonPages\Concerns\HandlesTotalPages;
use Traversable;

/**
 * The total items handler.
 *
 */
class TotalItemsHandler extends AbstractHandler
{
    use HandlesTotalPages;

    /**
     * Determine whether the handler can handle the JSON API map
     *
     * @return bool
     */
    public function matches(): bool
    {
        return $this->map->items > 0
            && $this->map->pages === null
            && $this->map->perPageQuery === null;
    }

    /**
     * Handle the JSON API map
     *
     * @return Traversable
     */
    public function handle(): Traversable
    {
        $perPage = $this->map->perPage ?? count($this->map->source->json($this->map->path));
        $pages = $perPage > 0 ? (int) ceil($this->map->items / $perPage) : 0;

        return $this->handleByTotalPages($pages, $this->request->getUri());
    }
}
