<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Dtos;

use Cerbero\LazyJsonPages\Paginations\Pagination;
use Closure;

/**
 * The configuration
 *
 * @property-read class-string<Pagination> $pagination
 */
final class Config
{
    /**
     * Instantiate the class.
     */
    public function __construct(
        public readonly string $itemsPointer,
        public readonly string $pageName = 'page',
        public readonly int $firstPage = 1,
        public readonly ?string $pageInPath = null,
        public readonly ?string $totalPagesKey = null,
        public readonly ?string $totalItemsKey = null,
        public readonly ?string $cursorKey = null,
        public readonly ?string $lastPageKey = null,
        public readonly ?string $offsetKey = null,
        public readonly ?string $pagination = null,
        public readonly int $async = 3,
        public readonly int $attempts = 3,
        public readonly ?Closure $backoff = null,
    ) {}
}
