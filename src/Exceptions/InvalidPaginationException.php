<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Exceptions;

use Cerbero\LazyJsonPages\Paginations\Pagination;

/**
 * The exception to throw when the given pagination is invalid.
 */
class InvalidPaginationException extends LazyJsonPagesException
{
    /**
     * Instantiate the class.
     */
    public function __construct(public readonly string $class)
    {
        $pagination = Pagination::class;

        parent::__construct("The class [{$class}] should extend [{$pagination}].");
    }
}
