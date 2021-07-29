<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJson\Concerns\EndpointAware;
use Illuminate\Http\Client\Response;

/**
 * The JSON API map.
 *
 */
class Map
{
    use EndpointAware;

    /**
     * The initial JSON source.
     *
     * @var Response
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
     * @param Response $source
     * @param string $path
     * @param callable|array|string|int $map
     */
    public function __construct(Response $source, string $path, $map)
    {
        $this->source = $source;
        $this->path = $path;
        $this->hydrateMap($map);
    }

    /**
     * Hydrate the map
     *
     * @param callable|array|string|int $map
     * @return void
     */
    protected function hydrateMap($map): void
    {
        if (is_callable($map)) {
            $map($this);
        } elseif (is_array($map)) {
            [$this->pageName => $value] = $map;
            $this->pages = $this->resolveInt($value, 1);
        } else {
            $this->pages = $this->resolveInt($map, 1);
        }
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
        $raw = is_numeric($value) ? $value : $this->source->json($value);

        return (int) max($raw, $minimum);
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
        $this->perPage = $query ? $firstPageItems : $perPage;
        $this->perPageQuery = $query;
        $this->perPageOverride = $query ? $perPage : null;

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
        $this->nextPage = $this->resolvePage($this->source->json($key));

        return $this;
    }

    /**
     * Retrieve the page value from the given presumed URL
     *
     * @param mixed $value
     * @return mixed
     */
    protected function resolvePage($value)
    {
        if ($this->isEndpoint($value)) {
            parse_str(parse_url($value, PHP_URL_QUERY), $query);
            $value = $query[$this->pageName];
        }

        return is_numeric($value) ? intval($value) : $value;
    }

    /**
     * Set the number of the last page
     *
     * @param string|int $page
     * @return self
     */
    public function lastPage($page): self
    {
        $raw = is_numeric($page) ? $page : $this->source->json($page);

        $this->lastPage = $this->resolvePage($raw);

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
        $this->timeout = $seconds;

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
        $this->attempts = $times;

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
