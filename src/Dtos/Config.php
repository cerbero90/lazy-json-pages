<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Dtos;

use Closure;

final class Config
{
    public function __construct(
        public readonly string $dot,
        public readonly string $pointer,
        public readonly string $pageName,
        public readonly int $firstPage,
        public readonly ?int $totalPages,
        public readonly ?int $totalItems,
        public readonly ?int $perPage,
        public readonly ?string $perPageKey,
        public readonly ?int $perPageOverride,
        public readonly ?Closure $nextPage,
        public readonly ?string $nextPageKey,
        public readonly ?int $lastPage,
        public readonly int $async,
        public readonly int $connectionTimeout,
        public readonly int $requestTimeout,
        public readonly int $attempts,
        public readonly ?Closure $backoff,
    ) {}
}
