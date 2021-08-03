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
     * Determine whether the handler can handle the APIs configuration
     *
     * @return bool
     */
    public function matches(): bool
    {
        return $this->config->lastPage > 0 && $this->config->perPageQuery === null;
    }

    /**
     * Handle the APIs configuration
     *
     * @return Traversable
     */
    public function handle(): Traversable
    {
        $pages = $this->config->firstPage == 0 ? $this->config->lastPage + 1 : $this->config->lastPage;

        yield from $this->handleByTotalPages($pages);
    }
}
