<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJson\Concerns\EndpointAware;
use Cerbero\LazyJsonPages\Exceptions\LazyJsonPagesException;
use Illuminate\Support\Str;

/**
 * The APIs configuration.
 *
 */
class Config
{
    use EndpointAware;

    /**
     * The source wrapper.
     *
     * @var SourceWrapper
     */
    public $source;

    /**
     * The path to extract items from.
     *
     * @var string
     */
    public $path;

    /**
     * The name of the page.
     *
     * @var string
     */
    public $pageName = 'page';

    /**
     * The number of the first page.
     *
     * @var int
     */
    public $firstPage = 1;

    /**
     * The total number of pages.
     *
     * @var int
     */
    public $pages;

    /**
     * The total number of items.
     *
     * @var int
     */
    public $items;

    /**
     * The number of items per page.
     *
     * @var int
     */
    public $perPage;

    /**
     * The query parameter holding the number of items per page.
     *
     * @var string
     */
    public $perPageQuery;

    /**
     * The new number of items per page.
     *
     * @var int
     */
    public $perPageOverride;

    /**
     * The next page of a simple or cursor pagination.
     *
     * @var string|int
     */
    public $nextPage;

    /**
     * The key holding the next page.
     *
     * @var string
     */
    public $nextPageKey;

    /**
     * The number of the last page.
     *
     * @var int
     */
    public $lastPage;

    /**
     * The number of pages to fetch per chunk.
     *
     * @var int
     */
    public $chunk;

    /**
     * The maximum number of concurrent async HTTP requests.
     *
     * @var int
     */
    public $concurrency = 10;

    /**
     * The timeout in seconds.
     *
     * @var int
     */
    public $timeout = 5;

    /**
     * The number of attempts to fetch pages.
     *
     * @var int
     */
    public $attempts = 3;

    /**
     * The backoff strategy.
     *
     * @var callable
     */
    public $backoff;

    /**
     * Instantiate the class.
     *
     * @param \Psr\Http\Message\RequestInterface|\Illuminate\Http\Client\Response $source
     * @param string $path
     * @param callable|array|string|int $config
     */
    public function __construct($source, string $path, $config)
    {
        $this->source = new SourceWrapper($source);
        $this->path = $path;
        $this->hydrateConfig($config);
    }

    /**
     * Hydrate the configuration
     *
     * @param callable|array|string|int $config
     * @return void
     *
     * @throws LazyJsonPagesException
     */
    protected function hydrateConfig($config): void
    {
        if (is_callable($config)) {
            $config($this);
        } elseif (is_array($config)) {
            $this->resolveConfig($config);
        } elseif (is_string($config) || is_numeric($config)) {
            $this->pages($config);
        } else {
            throw new LazyJsonPagesException('The provided configuration is not valid.');
        }
    }

    /**
     * Resolve the given configuration
     *
     * @param array $config
     * @return void
     *
     * @throws LazyJsonPagesException
     */
    protected function resolveConfig(array $config): void
    {
        foreach ($config as $key => $value) {
            if (method_exists($this, $method = Str::camel($key))) {
                $values = is_array($value) ? $value : [$value];
                call_user_func_array([$this, $method], $values);
            } else {
                throw new LazyJsonPagesException("The key [{$key}] is not valid.");
            }
        }
    }

    /**
     * Set the page name
     *
     * @param string $name
     * @return self
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
     *
     * @param string|int $pages
     * @return self
     */
    public function pages($pages): self
    {
        $this->pages = $this->resolveInt($pages, 1);

        return $this;
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
        return (int) max($minimum, $this->resolvePage($value));
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
        } elseif ($this->isEndpoint($value = $this->source->json($value))) {
            parse_str(parse_url($value, PHP_URL_QUERY), $query);
            $value = $query[$this->pageName];
        }

        return is_numeric($value) ? (int) $value : $value;
    }

    /**
     * Set the total number of items
     *
     * @param string|int $items
     * @return self
     */
    public function items($items): self
    {
        $this->items = $this->resolveInt($items, 0);

        return $this;
    }

    /**
     * Set the number of items per page and optionally override it
     *
     * @param int $perPage
     * @param string|null $query
     * @param int $firstPageItems
     * @return self
     */
    public function perPage(int $perPage, string $query = null, int $firstPageItems = 1): self
    {
        $this->perPage = max(1, $query ? $firstPageItems : $perPage);
        $this->perPageQuery = $query;
        $this->perPageOverride = $query ? max(1, $perPage) : null;

        return $this;
    }

    /**
     * Set the next page
     *
     * @param string $key
     * @return self
     */
    public function nextPage(string $key): self
    {
        $this->nextPageKey = $key;
        $this->nextPage = $this->resolvePage($key);

        return $this;
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
}
