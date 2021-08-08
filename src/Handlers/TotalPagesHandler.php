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
     * Determine whether the handler can handle the APIs configuration
     *
     * @return bool
     */
    public function matches(): bool
    {
        return $this->config->pages > 0 && $this->config->perPageQuery === null;
    }

    /**
     * Handle the APIs configuration
     *
     * @return Traversable
     */
    public function handle(): Traversable
    {
        yield from $this->handleByTotalPages($this->config->pages);
    }
}
