<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Concerns\ResolvesPages;
use Cerbero\LazyJsonPages\Concerns\YieldsPaginatedItems;
use Cerbero\LazyJsonPages\Dtos\Config;
use Cerbero\LazyJsonPages\Services\Book;
use Cerbero\LazyJsonPages\Sources\Source;
use IteratorAggregate;
use Traversable;

/**
 * The abstract implementation of a pagination.
 *
 * @implements IteratorAggregate<int, mixed>
 */
abstract class Pagination implements IteratorAggregate
{
    use YieldsPaginatedItems;
    use ResolvesPages;

    /**
     * The collector of pages.
     */
    public readonly Book $book;

    /**
     * The number of items per page.
     */
    protected readonly int $itemsPerPage;

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     */
    abstract public function getIterator(): Traversable;

    /**
     * Instantiate the class.
     */
    final public function __construct(
        protected readonly Source $source,
        protected readonly Config $config,
    ) {
        $this->book = new Book();
    }

    /**
     * Determine whether the configuration matches this pagination.
     */
    public function matches(): bool
    {
        return true;
    }
}
