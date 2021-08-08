<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJson\Source;
use Psr\Http\Message\ResponseInterface;
use Traversable;

/**
 * The pages fetching outcome.
 *
 */
class Outcome
{
    /**
     * The item generators.
     *
     * @var \Generator[]
     */
    protected $items = [];

    /**
     * The pages unable to be fetched.
     *
     * @var array
     */
    protected $failedPages = [];

    /**
     * Add the items from the given page
     *
     * @param int $page
     * @param ResponseInterface $response
     * @param string $path
     * @return self
     */
    public function addItemsFromPage(int $page, ResponseInterface $response, string $path): self
    {
        $this->items[$page] = (function () use ($response, $path) {
            yield from new Source($response, $path);
        })();

        return $this;
    }

    /**
     * Traverse and unset the items
     *
     * @return Traversable
     */
    public function pullItems(): Traversable
    {
        ksort($this->items);

        foreach ($this->items as $generator) {
            yield from $generator;
        }

        $this->items = [];
    }

    /**
     * Add the given page to the failed pages
     *
     * @param string|int $page
     * @return self
     */
    public function addFailedPage($page): self
    {
        $this->failedPages[] = $page;

        return $this;
    }

    /**
     * Retrieve and unset the failed pages
     *
     * @return array
     */
    public function pullFailedPages(): array
    {
        $failedPages = $this->failedPages;

        $this->failedPages = [];

        return $failedPages;
    }
}
