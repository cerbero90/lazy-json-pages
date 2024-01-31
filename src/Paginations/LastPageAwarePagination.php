<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Traversable;

/**
 * The pagination aware of the number of the last page.
 */
class LastPageAwarePagination extends LengthAwarePagination
{
    /**
     * Determine whether the configuration matches this pagination.
     */
    public function matches(): bool
    {
        return $this->config->lastPageKey !== null;
    }

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        yield from $this->yieldItemsUntilKey($this->config->lastPageKey, function (int $page) {
            return $this->config->firstPage === 0 ? $page + 1 : $page;
        });
    }
}
