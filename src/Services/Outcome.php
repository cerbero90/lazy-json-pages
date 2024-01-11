<?php

namespace Cerbero\LazyJsonPages\Services;

use Generator;
use Traversable;

/**
 * The outcome of fetching paginated items.
 */
final class Outcome
{
    /**
     * The iterators yielding items from pages.
     *
     * @var array<int, Traversable<int, mixed>>
     */
    private array $itemsByPage = [];

    /**
     * The pages unable to be fetched.
     *
     * @var int[]
     */
    private array $failedPages = [];

    /**
     * Add the yielded items from the given page.
     *
     * @param Traversable<int, mixed> $items
     */
    public function addItemsFromPage(int $page, Traversable $items): self
    {
        $this->itemsByPage[$page] = $items;

        return $this;
    }

    /**
     * Traverse and unset the items.
     *
     * @return Generator<int, mixed>
     */
    public function pullItems(): Generator
    {
        ksort($this->itemsByPage);

        foreach ($this->itemsByPage as $page => $items) {
            yield from $items;

            unset($this->itemsByPage[$page]);
        }
    }

    /**
     * Add the given failed page.
     */
    public function addFailedPage(int $page): self
    {
        $this->failedPages[] = $page;

        return $this;
    }

    /**
     * Retrieve and unset the failed pages.
     *
     * @return int[]
     */
    public function pullFailedPages(): array
    {
        $failedPages = $this->failedPages;

        $this->failedPages = [];

        return $failedPages;
    }
}
