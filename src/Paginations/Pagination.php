<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Dtos\Config;
use Cerbero\LazyJsonPages\Sources\AnySource;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string|int, mixed>
 */
abstract class Pagination implements IteratorAggregate
{
    public final function __construct(
        protected readonly AnySource $source,
        protected readonly Config $config,
    ) {
    }

    /**
     * @return Traversable<string|int, mixed>
     */
    public function getIterator(): Traversable
    {
        yield 1;
    }
}
