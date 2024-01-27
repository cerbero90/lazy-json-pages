<?php

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Services\Client;
use Generator;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Throwable;
use Traversable;

/**
 * The trait to send asynchronous HTTP requests.
 *
 */
trait SendsAsyncRequests
{
    use RetriesHttpRequests;

    /**
     * Fetch pages by performing asynchronous HTTP calls.
     *
     * @param LazyCollection<int, LazyCollection<int, int>> $chunkedPages
     * @return Traversable<int, ResponseInterface>
     */
    protected function fetchPagesAsynchronously(LazyCollection $chunkedPages, UriInterface $uri): Traversable
    {
        foreach ($chunkedPages as $pages) {
            $this->retry(fn() => $this->pool($uri, $pages->all())->promise()->wait());

            yield from $this->book->pullPages();
        }
    }

    /**
     * Retrieve a pool of asynchronous requests.
     *
     * @param array<int, int> $pages
     */
    protected function pool(UriInterface $uri, array $pages): Pool
    {
        return new Pool(Client::instance(), $this->yieldRequests($uri, $pages), [
            'concurrency' => $this->config->async,
            'fulfilled' => fn(ResponseInterface $response, int $page) => $this->book->addPage($page, $response),
            'rejected' => fn(Throwable $e, int $page) => $this->book->addFailedPage($page) && throw $e,
        ]);
    }

    /**
     * Retrieve a generator yielding the HTTP requests for the given pages.
     *
     * @param array<int, int> $pages
     * @return Generator<int, RequestInterface>
     */
    protected function yieldRequests(UriInterface $uri, array $pages): Generator
    {
        /** @var RequestInterface $request */
        $request = clone $this->source->request();
        $pages = $this->book->pullFailedPages() ?: $pages;

        foreach ($pages as $page) {
            yield $page => $request->withUri($this->uriForPage($uri, (string) $page));
        }
    }
}
