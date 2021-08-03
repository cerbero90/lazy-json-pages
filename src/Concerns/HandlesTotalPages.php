<?php

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Outcome;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Traversable;

/**
 * The trait to handle APIs with the number of total pages.
 *
 */
trait HandlesTotalPages
{
    /**
     * Handle APIs with the given number of total pages
     *
     * @param int $pages
     * @param Uri|null $uri
     * @return Traversable
     */
    protected function handleByTotalPages(int $pages, Uri $uri = null): Traversable
    {
        $uri = $uri ?: $this->config->source->request->getUri();
        $firstPageAlreadyFetched = strval($uri) == strval($this->config->source->request->getUri());
        $chunkedPages = $this->chunkPages($pages, $firstPageAlreadyFetched);
        $items = $this->fetchItemsAsynchronously($chunkedPages, $uri);

        if ($firstPageAlreadyFetched) {
            yield from $this->config->source->json($this->config->path);
        }

        yield from $items;
    }

    /**
     * Retrieve the given pages in chunks
     *
     * @param int $pages
     * @param bool $skipFirstPage
     * @return iterable
     */
    protected function chunkPages(int $pages, bool $skipFirstPage): iterable
    {
        $firstPage = $skipFirstPage ? $this->config->firstPage + 1 : $this->config->firstPage;
        $lastPage = $this->config->firstPage == 0 ? $pages - 1 : $pages;

        return LazyCollection::range($firstPage, $lastPage)->chunk($this->config->chunk ?: INF);
    }

    /**
     * Fetch items by performing asynchronous HTTP calls
     *
     * @param iterable $chunkedPages
     * @param Uri $uri
     * @return Traversable
     */
    protected function fetchItemsAsynchronously(iterable $chunkedPages, Uri $uri): Traversable
    {
        $client = new Client(['timeout' => $this->config->timeout]);

        foreach ($chunkedPages as $pages) {
            $outcome = $this->retry(function (Outcome $outcome) use ($uri, $client, $pages) {
                $pages = $outcome->pullFailedPages() ?: $pages;

                return $this->pool($client, $outcome, function () use ($uri, $pages) {
                    $request = clone $this->config->source->request;

                    foreach ($pages as $page) {
                        yield $page => $request->withUri(Uri::withQueryValue($uri, $this->config->pageName, $page));
                    }
                });
            });

            yield from $outcome->pullItems();
        }
    }

    /**
     * Retrieve the outcome of a pool of asynchronous requests
     *
     * @param Client $client
     * @param Outcome $outcome
     * @param callable $getRequests
     * @return Outcome
     */
    protected function pool(Client $client, Outcome $outcome, callable $getRequests): Outcome
    {
        $pool = new Pool($client, $getRequests(), [
            'concurrency' => $this->config->concurrency,
            'fulfilled' => function (ResponseInterface $response, int $page) use ($outcome) {
                $outcome->addItemsFromPage($page, $response, $this->config->path);
            },
            'rejected' => function (Throwable $e, int $page) use ($outcome) {
                $outcome->addFailedPage($page);
                throw $e;
            }
        ]);

        $pool->promise()->wait();

        return $outcome;
    }
}
