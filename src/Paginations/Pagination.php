<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Dtos\Config;
use Cerbero\LazyJsonPages\Sources\AnySource;
use IteratorAggregate;
use Traversable;

/**
 * The abstract implementation of a pagination.
 *
 * @implements IteratorAggregate<string|int, mixed>
 */
abstract class Pagination implements IteratorAggregate
{
    /**
     * Determine whether the configuration matches this pagination.
     */
    abstract public function matches(): bool;

    /**
     * Yield the paginated items.
     *
     * @return Traversable<string|int, mixed>
     */
    abstract public function getIterator(): Traversable;

    final public function __construct(
        protected readonly AnySource $source,
        protected readonly Config $config,
    ) {}
}
