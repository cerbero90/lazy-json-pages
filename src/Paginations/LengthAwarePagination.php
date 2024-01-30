<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Concerns\SendsAsyncRequests;
use Cerbero\LazyJsonPages\Exceptions\InvalidKeyException;
use Closure;
use Generator;
use Illuminate\Support\LazyCollection;

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
    protected function yieldItemsUntilKey(string $key, ?Closure $callback = null): Generator
    {
        yield from $generator = $this->yieldItemsAndReturnKey($this->source->response(), $key);

        $page = $this->toPage($generator->getReturn());

        if (!is_int($page)) {
            throw new InvalidKeyException($key);
        }

        $page = $callback ? $callback($page) : $page;

        yield from $this->yieldItemsUntilPage($page);
    }

    /**
     * Yield paginated items until the given page is reached.
     *
     * @return Generator<int, mixed>
     */
    protected function yieldItemsUntilPage(int $page): Generator
    {
        $uri = $this->source->request()->getUri();
        $chunkedPages = $this->chunkPages($page);

        foreach ($this->fetchPagesAsynchronously($chunkedPages, $uri) as $page) {
            yield from $this->yieldItemsFrom($page);
        }
    }

    /**
     * Retrieve the given pages in chunks.
     *
     * @return LazyCollection<int, LazyCollection<int, int>>
     */
    protected function chunkPages(int $pages): LazyCollection
    {
        $firstPage = $this->config->firstPage + 1;
        $lastPage = $this->config->firstPage == 0 ? $pages - 1 : $pages;

        return $firstPage > $lastPage
            ? LazyCollection::empty()
            : LazyCollection::range($firstPage, $lastPage)->chunk($this->config->async);
    }
}
