<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Dtos;

final class Config
{
    public function __construct(
        public readonly string $dot,
        public readonly string $pageName,
        public readonly int $firstPage,
        public readonly ?int $totalPages,
        public readonly ?int $totalItems,
        public readonly ?int $perPage,
        public readonly ?string $perPageKey,
        public readonly int $perPageOverride,
        public readonly string|int $nextPage,
        public readonly string $nextPageKey,
        public readonly int $lastPage,
        public readonly int $chunk,
        public readonly int $concurrency,
        public readonly int $timeout,
        public readonly int $attempts,
    ) {}
}
