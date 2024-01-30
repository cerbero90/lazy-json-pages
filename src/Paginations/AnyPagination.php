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
        // CursorPagination::class,
        CustomPagination::class,
        // LastPageAwarePagination::class,
        // LinkHeaderPagination::class,
        TotalItemsAwarePagination::class,
        TotalPagesAwarePagination::class,
    ];

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
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
