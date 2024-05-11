<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Concerns\YieldsItemsByLength;
use Traversable;

/**
 * The pagination aware of the total number of items.
 */
class TotalItemsAwarePagination extends Pagination
{
    use YieldsItemsByLength;

    /**
     * Determine whether this pagination matches the configuration.
     */
    public function matches(): bool
    {
        return $this->config->totalItemsKey !== null
            && $this->config->totalPagesKey === null
            && $this->config->lastPageKey === null;
    }

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        yield from $this->yieldItemsUntilKey($this->config->totalItemsKey, function(int $totalItems) {
            return $this->itemsPerPage > 0 ? (int) ceil($totalItems / $this->itemsPerPage) : 0;
        });
    }
}
