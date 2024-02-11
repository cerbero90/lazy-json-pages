<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Exceptions\InvalidPaginationException;
use Traversable;

/**
 * The user-defined pagination.
 */
class CustomPagination extends Pagination
{
    /**
     * Determine whether the configuration matches this pagination.
     */
    public function matches(): bool
    {
        return $this->config->pagination !== null;
    }

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        if (!is_subclass_of($this->config->pagination, Pagination::class)) {
            throw new InvalidPaginationException($this->config->pagination);
        }

        yield from new $this->config->pagination($this->source, $this->config);
    }
}
