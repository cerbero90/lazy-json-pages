<?php

namespace Cerbero\LazyJsonPages\Handlers;

use Cerbero\LazyJsonPages\Concerns\HandlesTotalPages;
use Traversable;

/**
 * The last page handler.
 *
 */
class LastPageHandler extends AbstractHandler
{
    use HandlesTotalPages;

    /**
     * Determine whether the handler can handle the JSON API map
     *
     * @return bool
     */
    public function matches(): bool
    {
        return $this->map->lastPage > 0 && $this->map->perPageQuery === null;
    }

    /**
     * Handle the JSON API map
     *
     * @return Traversable
     */
    public function handle(): Traversable
    {
        $pages = $this->map->firstPage == 0 ? $this->map->lastPage + 1 : $this->map->lastPage;

        return $this->handleByTotalPages($pages, $this->request->getUri());
    }
}
