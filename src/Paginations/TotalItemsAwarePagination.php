<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Exceptions\InvalidKeyException;
use Traversable;

/**
 * The pagination aware of the total number of items.
 */
class TotalItemsAwarePagination extends LengthAwarePagination
{
    /**
     * Determine whether the configuration matches this pagination.
     */
    public function matches(): bool
    {
        return $this->config->totalItemsKey !== null
            && $this->config->totalPagesKey === null
            && $this->config->perPage === null;
    }

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        $perPage = 0;
        $generator = $this->yieldItemsAndReturnKey($this->source->response(), $this->config->totalItemsKey);

        foreach ($generator as $item) {
            yield $item;
            ++$perPage;
        }

        if (!is_numeric($totalItems = $generator->getReturn())) {
            throw new InvalidKeyException($this->config->totalItemsKey);
        }

        $totalPages = $perPage > 0 ? (int) ceil(intval($totalItems) / $perPage) : 0;

        yield from $this->yieldItemsUntilPage($totalPages);
    }
}
