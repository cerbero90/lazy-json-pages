<?php

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\JsonParser\JsonParser;
use Cerbero\LazyJsonPages\Services\Outcome;
use Generator;
use GuzzleHttp\Client;
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
     * Fetch items by performing asynchronous HTTP calls.
     *
     * @param LazyCollection<int, LazyCollection<int, int>> $chunkedPages
     * @return Traversable<int, mixed>
     */
    protected function fetchItemsAsynchronously(LazyCollection $chunkedPages, UriInterface $uri): Traversable
    {
        $client = new Client([
            'timeout' => $this->config->requestTimeout,
            'connect_timeout' => $this->config->connectionTimeout,
        ]);

        foreach ($chunkedPages as $pages) {
            $outcome = $this->retry(function (Outcome $outcome) use ($uri, $client, $pages): Outcome {
                $pages = $outcome->pullFailedPages() ?: $pages->all();

                return $this->pool($client, $outcome, $this->yieldRequests($uri, $pages));
            });

            yield from $outcome->pullItems();
        }
    }

    /**
     * Retrieve a generator yielding the HTTP requests for the given pages.
     *
     * @param int[] $pages
     * @return Generator<int, RequestInterface>
     */
    protected function yieldRequests(UriInterface $uri, array $pages): Generator
    {
        /** @var RequestInterface $request */
        $request = clone $this->source->request();

        foreach ($pages as $page) {
            $pageUri = Uri::withQueryValue($uri, $this->config->pageName, (string) $page);

            yield $page => $request->withUri($pageUri);
        }
    }

    /**
     * Retrieve the outcome of a pool of asynchronous requests.
     *
     * @param Generator<int, RequestInterface> $requests
     */
    protected function pool(Client $client, Outcome $outcome, Generator $requests): Outcome
    {
        $pool = new Pool($client, $requests, [
            'concurrency' => $this->config->async,
            'fulfilled' => function (ResponseInterface $response, int $page) use ($outcome) {
                /** @var Traversable<int, mixed> $items */
                $items = JsonParser::parse($response)->pointer($this->config->pointer);
                $outcome->addItemsFromPage($page, $items);
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
