<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Services;

use Cerbero\LazyJsonPages\Dtos\Config;
use Cerbero\LazyJsonPages\Sources\AnySource;
use Cerbero\LazyJsonPages\ValueObjects\Response;
use Closure;

final class ConfigFactory
{
    /**
     * The dot to extract items from.
     */
    private string $dot = '*';

    /**
     * The name of the page.
     */
    private string $pageName = 'page';

    /**
     * The number of the first page.
     */
    private int $firstPage = 1;

    /**
     * The total number of pages.
     */
    private ?int $totalPages = null;

    /**
     * The total number of items.
     */
    private ?int $totalItems = null;

    /**
     * The number of items per page.
     */
    private ?int $perPage = null;

    /**
     * The key holding the number of items per page.
     */
    private ?string $perPageKey = null;

    /**
     * The new number of items per page.
     */
    private ?int $perPageOverride = null;

    /**
     * The next page number, link or cursor.
     */
    private ?Closure $nextPage = null;

    /**
     * The key holding the next page.
     */
    private ?string $nextPageKey = null;

    /**
     * The number of the last page.
     */
    private ?int $lastPage = null;

    /**
     * The number of pages to fetch asynchronously per chunk.
     */
    private ?int $chunk = null;

    /**
     * The maximum number of concurrent async HTTP requests.
     */
    private int $concurrency = 10;

    /**
     * The timeout in seconds.
     */
    private int $timeout = 5;

    /**
     * The number of attempts to fetch pages.
     */
    private int $attempts = 3;

    /**
     * The backoff strategy.
     */
    private ?Closure $backoff = null;

    /**
     * Instantiate the class.
     */
    public function __construct(private AnySource $source) {}

    /**
     * Set the dot-notation path to extract items from.
     */
    public function dot(string $dot): self
    {
        $this->dot = $dot;

        return $this;
    }

    /**
     * Set the name of the page.
     */
    public function pageName(string $name): self
    {
        $this->pageName = $name;

        return $this;
    }

    /**
     * Set the number of the first page
     */
    public function firstPage(int $page): self
    {
        $this->firstPage = max(0, $page);

        return $this;
    }

    /**
     * Set the total number of pages
     */
    public function totalPages(Closure|string $totalPages): self
    {
        $this->totalPages = $this->integerFromResponse($totalPages, minimum: 1);

        return $this;
    }

    /**
     * Retrieve an integer from the response
     */
    private function integerFromResponse(Closure|string $key, int $minimum = 0): int
    {
        return (int) max($minimum, $this->valueFromResponse($key));
    }

    /**
     * Retrieve a value from the response
     */
    private function valueFromResponse(Closure|string $key): mixed
    {
        return $key instanceof Closure ? $key($this->source->response()) : $this->source->response($key);
    }

    /**
     * Set the total number of items
     */
    public function totalItems(Closure|string $totalItems): self
    {
        $this->totalItems = $this->integerFromResponse($totalItems);

        return $this;
    }

    /**
     * Set the number of items per page and optionally override it
     */
    public function perPage(int $perPage, ?string $key = null, int $firstPageItems = 1): self
    {
        $this->perPage = max(1, $key ? $firstPageItems : $perPage);
        $this->perPageKey = $key;
        $this->perPageOverride = $key ? max(1, $perPage) : null;

        return $this;
    }

    /**
     * Set the next page
     */
    public function nextPage(Closure|string $key): self
    {
        $this->nextPage = $key instanceof Closure ? $key : fn(Response $response) => $response->get($key);

        return $this;
    }

    /**
     * Set the number of the last page
     */
    public function lastPage(Closure|string $key): self
    {
        $this->lastPage = $this->integerFromResponse($key);

        return $this;
    }

    /**
     * Fetch pages synchronously
     *
     * @return self
     */
    public function sync(): self
    {
        return $this->chunk(1);
    }

    /**
     * Set the number of pages to fetch per chunk
     *
     * @param int $size
     * @return self
     */
    public function chunk(int $size): self
    {
        $this->chunk = max(1, $size);

        return $this;
    }

    /**
     * Set the maximum number of concurrent async HTTP requests
     *
     * @param int $max
     * @return self
     */
    public function concurrency(int $max): self
    {
        $this->concurrency = max(0, $max);

        return $this;
    }

    /**
     * Set the timeout in seconds
     *
     * @param int $seconds
     * @return self
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = max(0, $seconds);

        return $this;
    }

    /**
     * Set the number of attempts to fetch pages
     *
     * @param int $times
     * @return self
     */
    public function attempts(int $times): self
    {
        $this->attempts = max(1, $times);

        return $this;
    }

    /**
     * Set the backoff strategy
     *
     * @param callable $callback
     * @return self
     */
    public function backoff(callable $callback): self
    {
        $this->backoff = $callback;

        return $this;
    }

    public function make(): Config
    {
        return new Config(
            $this->dot,
            $this->pageName,
            $this->firstPage,
            $this->totalPages,
            $this->totalItems,
            $this->perPage,
            $this->perPageKey,
            $this->perPageOverride,
            $this->nextPage,
            $this->nextPageKey,
            $this->lastPage,
            $this->chunk,
            $this->concurrency,
            $this->timeout,
            $this->attempts,
            $this->backoff,
        );
    }
}
