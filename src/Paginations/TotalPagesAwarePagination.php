<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Traversable;

/**
 * The pagination aware of the total number of pages.
 */
class TotalPagesAwarePagination extends LengthAwarePagination
{
    /**
     * Determine whether the configuration matches this pagination.
     */
    public function matches(): bool
    {
        return $this->config->totalPagesKey !== null
            && $this->config->perPage === null;
    }

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        yield from $this->yieldItemsUntilPage($this->config->totalPagesKey);
    }
}
