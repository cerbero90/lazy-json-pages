<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJson\Concerns\EndpointAware;
use Illuminate\Http\Client\Response;

/**
 * The JSON map.
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
     * The query parameter holding the number of items per page.
     *
     * @var string
     */
    public $perPageQuery;

    /**
     * The number of items per page.
     *
     * @var int
     */
    public $perPage;

    /**
     * The next page(s) of a simple or cursor pagination.
     *
     * @var array|string|int
     */
    public $nextPage;

    /**
     * Instantiate the class.
     *
     * @param Response $source
     * @param string $path
     * @param callable|array|string|int $data
     */
    public function __construct(Response $source, string $path, $data)
    {
        $this->source = $source;
        $this->path = $path;
        $this->hydrateMap($data);
    }

    /**
     * Hydrate the map with the given data
     *
     * @param callable|array|string|int $data
     * @return void
     */
    protected function hydrateMap($data): void
    {
        if (is_callable($data)) {
            $data($this);
        } elseif (is_array($data)) {
            [$this->pageName => $value] = $data;
            $this->pages = $this->resolveInt($value);
        } else {
            $this->pages = $this->resolveInt($data);
        }
    }

    /**
     * Retrieve an integer from the given value
     *
     * @param string|int $value
     * @return int
     */
    protected function resolveInt($value): int
    {
        return intval(is_numeric($value) ? $value : $this->source->json($value));
    }

    /**
     * Set the page name
     *
     * @param string $name
     * @return static
     */
    public function pageName(string $name): static
    {
        $this->pageName = $name;

        return $this;
    }

    /**
     * Set the total number of pages
     *
     * @param string|int $pages
     * @return static
     */
    public function pages($pages): static
    {
        $this->pages = $this->resolveInt($pages);

        return $this;
    }

    /**
     * Set the total number of items
     *
     * @param string|int $items
     * @return static
     */
    public function items($items): static
    {
        $this->items = $this->resolveInt($items);

        return $this;
    }

    /**
     * Set the number of items per page and its query parameter
     *
     * @param string $query
     * @param int $perPage
     * @return static
     */
    public function perPage(string $query, int $perPage): static
    {
        $this->perPageQuery = $query;
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Set the next page
     *
     * @param string $key
     * @return static
     */
    public function nextPage(string $key): static
    {
        $nextPages = [];
        $page = $this->source->json($key);
        $hasManyPages = is_array($page);
        $isEndpoint = $this->isEndpoint($hasManyPages ? head($page) : $page);

        foreach ((array) $page as $value) {
            if ($isEndpoint) {
                parse_str(parse_url($value, PHP_URL_QUERY), $query);
                $value = $query[$this->pageName];
            }

            $nextPages[] = is_numeric($value) ? intval($value) : $value;
        }

        $this->nextPage = $hasManyPages ? $nextPages : head($nextPages);

        return $this;
    }
}
