<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Exceptions\UnsupportedPaginationException;
use Traversable;

/**
 * The aggregator of supported paginations.
 */
class AnyPagination extends Pagination
{
    /**
     * The supported paginations.
     *
     * @var class-string<Pagination>[]
     */
    protected array $supportedPaginations = [
        CursorPagination::class,
        CustomPagination::class,
        LastPageAwarePagination::class,
        // LinkHeaderPagination::class,
        TotalItemsAwarePagination::class,
        TotalPagesAwarePagination::class,
    ];

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     * @throws UnsupportedPaginationException
     */
    public function getIterator(): Traversable
    {
        // yield only items and not their related index to ensure indexes continuity
        // otherwise the actual indexes always start from 0 on every page.
        foreach ($this->matchingPagination() as $item) {
            yield $item;
        }
    }

    /**
     * Retrieve the pagination matching with the configuration.
     */
    protected function matchingPagination(): Pagination
    {
        foreach ($this->supportedPaginations as $class) {
            $pagination = new $class($this->source, $this->config);

            if ($pagination->matches()) {
                return $pagination;
            }
        }

        throw new UnsupportedPaginationException($this->config);
    }
}
