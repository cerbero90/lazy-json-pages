<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Exceptions\InvalidKeyException;
use Closure;
use Generator;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\ResponseInterface;

/**
 * The trait to yield items from length-aware paginations.
 */
trait YieldsItemsByLength
{
    use SendsAsyncRequests;

    /**
     * Yield paginated items until the page resolved from the given key is reached.
     *
     * @param (Closure(int): int) $callback
     * @return Generator<int, mixed>
     */
    protected function yieldItemsUntilKey(string $key, Closure $callback = null): Generator
    {
        yield from $this->yieldItemsUntilPage(function(ResponseInterface $response) use ($key, $callback) {
            yield from $generator = $this->yieldItemsAndGetKey($response, $key);

            if (!is_int($page = $this->toPage($generator->getReturn()))) {
                throw new InvalidKeyException($key);
            }

            return $callback ? $callback($page) : $page;
        });
    }

    /**
     * Yield paginated items until the resolved page is reached.
     *
     * @param (Closure(ResponseInterface): Generator<int, mixed>) $callback
     * @return Generator<int, mixed>
     */
    protected function yieldItemsUntilPage(Closure $callback): Generator
    {
        yield from $generator = $callback($this->source->pullResponse());

        $uri = $this->source->request()->getUri();
        $chunkedPages = $this->chunkPages($generator->getReturn());

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
