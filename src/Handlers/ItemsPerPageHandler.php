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
     * Determine whether the handler can handle the APIs configuration
     *
     * @return bool
     */
    public function matches(): bool
    {
        return $this->config->perPageQuery
            && $this->config->nextPageKey === null
            && ($this->config->items || $this->config->pages || $this->config->lastPage);
    }

    /**
     * Handle the APIs configuration
     *
     * @return Traversable
     */
    public function handle(): Traversable
    {
        $originalUri = $this->config->source->request->getUri();
        $pages = (int) ceil($this->countItems() / $this->config->perPageOverride);
        $uri = Uri::withQueryValue($originalUri, $this->config->perPageQuery, $this->config->perPageOverride);

        yield from $this->handleByTotalPages($pages, $uri);
    }

    /**
     * Retrieve the total number of items
     *
     * @return int
     */
    protected function countItems(): int
    {
        if ($this->config->items) {
            return $this->config->items;
        } elseif ($this->config->pages) {
            return $this->config->pages * $this->config->perPage;
        }

        $pages = $this->config->firstPage == 0 ? $this->config->lastPage + 1 : $this->config->lastPage;

        return $pages * $this->config->perPage;
    }
}
