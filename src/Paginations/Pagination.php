<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Concerns\ParsesPages;
use Cerbero\LazyJsonPages\Concerns\ResolvesPages;
use Cerbero\LazyJsonPages\Dtos\Config;
use Cerbero\LazyJsonPages\Services\Book;
use Cerbero\LazyJsonPages\Sources\AnySource;
use GuzzleHttp\Client;
use IteratorAggregate;
use Traversable;

/**
 * The abstract implementation of a pagination.
 *
 * @implements IteratorAggregate<int, mixed>
 */
abstract class Pagination implements IteratorAggregate
{
    use ParsesPages;
    use ResolvesPages;

    /**
     * The collector of pages.
     */
    public readonly Book $book;

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
        protected readonly AnySource $source,
        protected readonly Client $client,
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
