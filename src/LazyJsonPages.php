<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJson\Pointers\DotsConverter;
use Cerbero\LazyJsonPages\Dtos\Config;
use Cerbero\LazyJsonPages\Paginations\AnyPagination;
use Cerbero\LazyJsonPages\Sources\AnySource;
use Cerbero\LazyJsonPages\ValueObjects\Response;
use Closure;
use Illuminate\Support\LazyCollection;

/**
 * The Lazy JSON Pages entry-point
 */
final class LazyJsonPages
{
    /**
     * The source of the paginated API.
     */
    private readonly AnySource $source;

    /**
     * The raw configuration of the API pagination.
     *
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * Instantiate the class statically.
     */
    public static function from(mixed $source): self
    {
        return new self($source);
    }

    /**
     * Instantiate the class.
     */
    public function __construct(mixed $source)
    {
        $this->source = new AnySource($source);
    }

    /**
     * Set the name of the page.
     */
    public function pageName(string $name): self
    {
        $this->config['pageName'] = $name;

        return $this;
    }

    /**
     * Set the number of the first page.
     */
    public function firstPage(int $page): self
    {
        $this->config['firstPage'] = max(0, $page);

        return $this;
    }

    /**
     * Set the total number of pages.
     */
    public function totalPages(Closure|string $totalPages): self
    {
        $this->config['totalPages'] = $this->integerFromResponse($totalPages, minimum: 1);

        return $this;
    }

    /**
     * Retrieve an integer from the response.
     */
    private function integerFromResponse(Closure|string $key, int $minimum = 0): int
    {
        return (int) max($minimum, $this->valueFromResponse($key));
    }

    /**
     * Retrieve a value from the response.
     */
    private function valueFromResponse(Closure|string $key): mixed
    {
        return $key instanceof Closure ? $key($this->source->response()) : $this->source->response($key);
    }

    /**
     * Set the total number of items.
     */
    public function totalItems(Closure|string $totalItems): self
    {
        $this->config['totalItems'] = $this->integerFromResponse($totalItems);

        return $this;
    }

    /**
     * Set the number of items per page and optionally override it.
     */
    public function perPage(int $perPage, ?string $key = null, int $firstPageItems = 1): self
    {
        $this->config['perPage'] = max(1, $key ? $firstPageItems : $perPage);
        $this->config['perPageKey'] = $key;
        $this->config['perPageOverride'] = $key ? max(1, $perPage) : null;

        return $this;
    }

    /**
     * Set the next page.
     */
    public function nextPage(Closure|string $key): self
    {
        $this->config['nextPage'] = $key instanceof Closure ? $key : fn(Response $response) => $response->get($key);

        return $this;
    }

    /**
     * Set the number of the last page.
     */
    public function lastPage(Closure|string $key): self
    {
        $this->config['lastPage'] = $this->integerFromResponse($key);

        return $this;
    }

    /**
     * Fetch pages synchronously.
     */
    public function sync(): self
    {
        return $this->async(1);
    }

    /**
     * Set the maximum number of concurrent async HTTP requests.
     */
    public function async(int $max): self
    {
        $this->config['async'] = max(1, $max);

        return $this;
    }

    /**
     * Set the server connection timeout in seconds.
     */
    public function connectionTimeout(float $seconds): self
    {
        $this->config['connectionTimeout'] = max(0, $seconds);

        return $this;
    }

    /**
     * Set an HTTP request timeout in seconds.
     */
    public function requestTimeout(float $seconds): self
    {
        $this->config['requestTimeout'] = max(0, $seconds);

        return $this;
    }

    /**
     * Set the number of attempts to fetch pages.
     */
    public function attempts(int $times): self
    {
        $this->config['attempts'] = max(1, $times);

        return $this;
    }

    /**
     * Set the backoff strategy.
     */
    public function backoff(Closure $callback): self
    {
        $this->config['backoff'] = $callback;

        return $this;
    }

    /**
     * Retrieve a lazy collection yielding the paginated items.
     *
     * @return LazyCollection<int, mixed>
     */
    public function collect(string $dot = '*'): LazyCollection
    {
        $this->config['dot'] = $dot;
        $this->config['pointer'] = DotsConverter::toPointer($dot);

        return new LazyCollection(function () {
            $items = new AnyPagination($this->source, new Config(...$this->config));

            // yield each item within a loop - instead of using `yield from` - to ignore the actual item index
            // and ensure indexes continuity, otherwise the index of items always starts from 0 on every page.
            foreach ($items as $item) {
                yield $item;
            }
        });
    }
}
