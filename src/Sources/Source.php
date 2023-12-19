<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Psr\Http\Message\RequestInterface;

abstract class Source
{
    public final function __construct(
        protected readonly mixed $source,
    ) {
    }

    abstract public function matches(): bool;

    abstract public function request(): RequestInterface;

    abstract public function response(?string $key = null): mixed;
}
