<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Concerns\PaginationLengthAware;
use Traversable;

/**
 * The pagination aware of the total number of pages.
 */
class TotalPagesAwarePagination extends Pagination
{
    use PaginationLengthAware;

    /**
     * Determine whether the configuration matches this pagination.
     */
    public function matches(): bool
    {
        return $this->config->totalPages !== null
            && $this->config->perPage === null;
    }

    /**
     * Yield the paginated items.
     *
     * @return Traversable<string|int, mixed>
     */
    public function getIterator(): Traversable
    {
        yield from $this->itemsByTotalPages($this->config->totalPages);
    }
}
