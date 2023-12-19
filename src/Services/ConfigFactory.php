<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Services;

use Cerbero\JsonParser\Concerns\DetectsEndpoints;
use Cerbero\LazyJsonPages\Dtos\Config;
use Cerbero\LazyJsonPages\Sources\AnySource;
use Closure;

final class ConfigFactory
{
    use DetectsEndpoints;

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
    private int $perPageOverride;

    /**
     * The next page of a simple or cursor pagination.
     *
     * @var string|int
     */
    private $nextPage;

    /**
     * The key holding the next page.
     *
     * @var string
     */
    private $nextPageKey;

    /**
     * The number of the last page.
     *
     * @var int
     */
    private $lastPage;

    /**
     * The number of pages to fetch per chunk.
     *
     * @var int
     */
    private $chunk;

    /**
     * The maximum number of concurrent async HTTP requests.
     *
     * @var int
     */
    private $concurrency = 10;

    /**
     * The timeout in seconds.
     *
     * @var int
     */
    private $timeout = 5;

    /**
     * The number of attempts to fetch pages.
     *
     * @var int
     */
    private $attempts = 3;

    /**
     * The backoff strategy.
     *
     * @var callable
     */
    private $backoff;

    public function __construct(private AnySource $source) {}

    /**
     * Set the dot-notation path to extract items from
     */
    public function dot(string $dot): self
    {
        $this->dot = $dot;

        return $this;
    }

    /**
     * Set the name of the page
     */
    public function pageName(string $name): self
    {
        $this->pageName = $name;

        return $this;
    }

    /**
     * Set the number of the first page
     *
     * @param int $page
     * @return self
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

        // $this->totalPages = $this->extractor->integerFromResponse($totalPages);

        return $this;
    }

    private function integerFromResponse(Closure|string $key, int $minimum = 0): int
    {
        return (int) max($minimum, match (true) {
            $key instanceof Closure => $key($this->source->response()),
            default => $this->source->response($key),
        });
    }

    /**
     * Retrieve an integer from the given value
     *
     * @param string|int $value
     * @param int $minimum
     * @return int
     */
    protected function resolveInt($value, int $minimum): int
    {
        return max($minimum, (int) $this->resolvePage($value));
    }

    /**
     * Retrieve the page value from the given presumed URL
     *
     * @param mixed $value
     * @return mixed
     */
    protected function resolvePage($value)
    {
        if (is_numeric($value)) {
            return (int) $value;
        } elseif (is_string($value) && $this->isEndpoint($value = $this->source->response($value))) {
            parse_str(parse_url($value, PHP_URL_QUERY), $query);
            $value = $query[$this->pageName];
        }

        return is_numeric($value) ? (int) $value : $value;
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
    public function nextPage(string $key): self
    {
        $this->nextPageKey = $key;
        $this->nextPage = $this->resolvePage($key);

        return $this;
    }

    private function pageFromResponse(Closure|string $key, int $minimum = 0): string|int
    {
        return (int) max($minimum, match (true) {
            $key instanceof Closure => $key($this->source->response()),
            default => $this->source->response($key),
        });
    }

    /**
     * Set the number of the last page
     *
     * @param string|int $page
     * @return self
     */
    public function lastPage($page): self
    {
        $this->lastPage = $this->resolvePage($page);

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
