<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Concerns\YieldsItemsByLength;
use Traversable;

/**
 * The pagination aware of the total number of pages.
 */
class TotalPagesAwarePagination extends Pagination
{
    use YieldsItemsByLength;

    /**
     * Determine whether the configuration matches this pagination.
     */
    public function matches(): bool
    {
        return $this->config->totalPagesKey !== null;
    }

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        yield from $this->yieldItemsUntilKey($this->config->totalPagesKey);
    }
}
