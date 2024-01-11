<?php

namespace Cerbero\LazyJsonPages\Concerns;

use Illuminate\Support\LazyCollection;
use Psr\Http\Message\UriInterface;
use Traversable;

/**
 * The trait used by paginations that are length-aware.
 *
 */
trait PaginationLengthAware
{
    use SendsAsyncRequests;

    /**
     * Yield items from the given pages.
     *
     * @return Traversable<int, mixed>
     */
    protected function itemsByTotalPages(int $pages, ?UriInterface $uri = null): Traversable
    {
        $uri ??= $this->source->request()->getUri();
        $firstPageAlreadyFetched = strval($uri) == strval($this->source->request()->getUri());
        $chunkedPages = $this->chunkPages($pages, $firstPageAlreadyFetched);
        $items = $this->fetchItemsAsynchronously($chunkedPages, $uri);

        if ($firstPageAlreadyFetched) {
            yield from $this->source->response()->pointer($this->config->pointer);
        }

        yield from $items;
    }

    /**
     * Retrieve the given pages in chunks.
     *
     * @return LazyCollection<int, int[]>
     */
    protected function chunkPages(int $pages, bool $shouldSkipFirstPage): LazyCollection
    {
        $firstPage = $shouldSkipFirstPage ? $this->config->firstPage + 1 : $this->config->firstPage;
        $lastPage = $this->config->firstPage == 0 ? $pages - 1 : $pages;

        return LazyCollection::range($firstPage, $lastPage)->chunk($this->config->async ?: INF);
    }
}
