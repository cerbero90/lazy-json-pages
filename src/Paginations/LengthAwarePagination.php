<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Concerns\SendsAsyncRequests;
use Cerbero\LazyJsonPages\Exceptions\InvalidPageException;
use Closure;
use Generator;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\UriInterface;

/**
 * The abstract implementation of a pagination that is aware of its length.
 */
abstract class LengthAwarePagination extends Pagination
{
    use SendsAsyncRequests;

    /**
     * Yield paginated items until the page resolved from the given key is reached.
     *
     * @param (Closure(int): int)|null $callback
     * @return Generator<int, mixed>
     */
    protected function yieldItemsUntilPage(string $key, ?Closure $callback = null): Generator
    {
        yield from $generator = $this->yieldItemsAndReturnKey($this->source->response(), $key);

        $page = $this->toPage($generator->getReturn());

        if (!is_int($page)) {
            throw new InvalidPageException($key);
        }

        $page = $callback ? $callback($page) : $page;

        foreach ($this->yieldPageResponsesUntil($page) as $pageResponse) {
            yield from $this->yieldItemsFrom($pageResponse);
        }
    }

    /**
     * Yield the HTTP page responses until given page.
     *
     * @return Generator<int, \Psr\Http\Message\ResponseInterface>
     */
    protected function yieldPageResponsesUntil(int $page, ?UriInterface $uri = null): Generator
    {
        $uri ??= $this->source->request()->getUri();
        $firstPageAlreadyFetched = strval($uri) == strval($this->source->request()->getUri());
        $chunkedPages = $this->chunkPages($page, $firstPageAlreadyFetched);

        yield from $this->fetchPagesAsynchronously($chunkedPages, $uri);
    }

    /**
     * Retrieve the given pages in chunks.
     *
     * @return LazyCollection<int, LazyCollection<int, int>>
     */
    protected function chunkPages(int $pages, bool $shouldSkipFirstPage): LazyCollection
    {
        if ($pages == 1 && $shouldSkipFirstPage) {
            return LazyCollection::empty();
        }

        $firstPage = $shouldSkipFirstPage ? $this->config->firstPage + 1 : $this->config->firstPage;
        $lastPage = $this->config->firstPage == 0 ? $pages - 1 : $pages;

        return LazyCollection::range($firstPage, $lastPage)->chunk($this->config->async);
    }
}
