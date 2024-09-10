<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Data;

use Cerbero\LazyJsonPages\Paginations\Pagination;
use Cerbero\LazyJsonPages\Services\RateLimits;
use Closure;

/**
 * The configuration
 *
 * @property-read class-string<Pagination> $pagination
 */
final class Config
{
    /**
     * The configuration options.
     */
    public const OPTION_PAGE_NAME = 'pageName';
    public const OPTION_PAGE_IN_PATH = 'pageInPath';
    public const OPTION_FIRST_PAGE = 'firstPage';
    public const OPTION_TOTAL_PAGES_KEY = 'totalPagesKey';
    public const OPTION_TOTAL_ITEMS_KEY = 'totalItemsKey';
    public const OPTION_CURSOR_KEY = 'cursorKey';
    public const OPTION_LAST_PAGE_KEY = 'lastPageKey';
    public const OPTION_OFFSET_KEY = 'offsetKey';
    public const OPTION_HAS_LINK_HEADER = 'hasLinkHeader';
    public const OPTION_PAGINATION = 'pagination';
    public const OPTION_RATE_LIMITS = 'rateLimits';
    public const OPTION_ASYNC = 'async';
    public const OPTION_ATTEMPTS = 'attempts';
    public const OPTION_BACKOFF = 'backoff';
    public const OPTION_ITEMS_POINTER = 'itemsPointer';

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
        public readonly bool $hasLinkHeader = false,
        public readonly ?string $pagination = null,
        public readonly ?RateLimits $rateLimits = null,
        public readonly int $async = 1,
        public readonly int $attempts = 3,
        public readonly ?Closure $backoff = null,
    ) {}
}
