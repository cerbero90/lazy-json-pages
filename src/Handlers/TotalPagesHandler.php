<?php

namespace Cerbero\LazyJsonPages\Handlers;

use Cerbero\LazyJsonPages\Concerns\HandlesTotalPages;
use Traversable;

/**
 * The total pages handler.
 *
 */
class TotalPagesHandler extends AbstractHandler
{
    use HandlesTotalPages;

    /**
     * Determine whether the handler can handle the JSON API map
     *
     * @return bool
     */
    public function matches(): bool
    {
        return $this->map->pages > 0 && $this->map->perPageQuery === null;
    }

    /**
     * Handle the JSON API map
     *
     * @return Traversable
     */
    public function handle(): Traversable
    {
        return $this->handleByTotalPages($this->map->pages, $this->request->getUri());
    }
}
