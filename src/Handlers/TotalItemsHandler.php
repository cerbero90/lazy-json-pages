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
     * Determine whether the handler can handle the APIs configuration
     *
     * @return bool
     */
    public function matches(): bool
    {
        return $this->config->items > 0
            && $this->config->pages === null
            && $this->config->perPageQuery === null;
    }

    /**
     * Handle the APIs configuration
     *
     * @return Traversable
     */
    public function handle(): Traversable
    {
        $perPage = $this->config->perPage ?? count($this->config->source->json($this->config->path));
        $pages = $perPage > 0 ? (int) ceil($this->config->items / $perPage) : 0;

        yield from $this->handleByTotalPages($pages);
    }
}
