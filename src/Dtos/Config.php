<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Dtos;

use Closure;

final class Config
{
    public function __construct(
        public readonly string $dot = "*",
        public readonly string $pointer = '',
        public readonly string $pageName = 'page',
        public readonly int $firstPage = 1,
        public readonly ?string $totalPagesKey = null,
        public readonly ?int $totalItems = null,
        public readonly ?int $perPage = null,
        public readonly ?string $perPageKey = null,
        public readonly ?int $perPageOverride = null,
        public readonly ?Closure $nextPage = null,
        public readonly ?string $nextPageKey = null,
        public readonly ?int $lastPage = null,
        public readonly int $async = 3,
        public readonly int $attempts = 3,
        public readonly ?Closure $backoff = null,
    ) {}
}
