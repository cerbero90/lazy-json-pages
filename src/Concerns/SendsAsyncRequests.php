<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Concerns;

use Generator;
use GuzzleHttp\Pool;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * The trait to send asynchronous HTTP requests.
 *
 * @property-read \Cerbero\LazyJsonPages\Sources\AnySource $source
 * @property-read \Cerbero\LazyJsonPages\Services\Book $book
 */
trait SendsAsyncRequests
{
    use RespectsRateLimits;
    use RetriesHttpRequests;

    /**
     * Fetch pages by sending asynchronous HTTP requests.
     *
     * @return Generator<int, ResponseInterface>
     */
    protected function fetchPagesAsynchronously(int $totalPages): Generator
    {
        $request = clone $this->source->request();
        $fromPage = $this->config->firstPage + 1;
        $toPage = $this->config->firstPage == 0 ? $totalPages - 1 : $totalPages;

        yield from $this->retry(function () use ($request, &$fromPage, $toPage) {
            foreach ($this->chunkRequestsBetweenPages($request, $fromPage, $toPage) as $requests) {
                yield from $this->pool($requests);
            }
        });
    }

    /**
     * Retrieve requests for the given pages in chunks.
     *
     * @return Generator<int, Generator<int, RequestInterface>>
     */
    protected function chunkRequestsBetweenPages(RequestInterface $request, int &$fromPage, int $toPage): Generator
    {
        while ($fromPage <= $toPage) {
            yield $this->yieldRequestsBetweenPages($request, $fromPage, $toPage);

            $this->respectRateLimits();
        }
    }

    /**
     * Yield the requests between the given pages.
     *
     * @return Generator<int, RequestInterface>
     */
    protected function yieldRequestsBetweenPages(RequestInterface $request, int &$fromPage, int $toPage): Generator
    {
        $chunkSize = min($this->config->async, $this->config->rateLimits?->threshold() ?? INF);

        for ($i = 0; $i < $chunkSize && $fromPage <= $toPage; $i++) {
            $page = $this->book->pullFailedPage() ?? $fromPage++;

            yield $page => $request->withUri($this->uriForPage($request->getUri(), (string) $page));
        }
    }

    /**
     * Send a pool of asynchronous requests.
     *
     * @param Generator<int, RequestInterface> $requests
     * @return Generator<int, ResponseInterface>
     * @throws Throwable
     */
    protected function pool(Generator $requests): Generator
    {
        $exception = null;

        $config = [
            'concurrency' => $this->config->async,
            'fulfilled' => fn(ResponseInterface $response, int $page) => $this->book->addPage($page, $response),
            'rejected' => function (Throwable $e, int $page) use (&$exception) {
                $this->book->addFailedPage($page);
                $exception = $e;
            },
        ];

        (new Pool($this->client, $requests, $config))->promise()->wait();

        if (isset($exception)) {
            throw $exception;
        }

        yield from $this->book->pullPages();
    }
}
