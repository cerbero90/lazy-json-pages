<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJsonPages\Dtos\Config;
use Cerbero\LazyJsonPages\Paginations\AnyPagination;
use Cerbero\LazyJsonPages\Services\Api;
use Cerbero\LazyJsonPages\Sources\AnySource;
use Closure;
use Illuminate\Support\LazyCollection;
use IteratorAggregate;
use Traversable;

/**
 * The Lazy JSON Pages entry-point.
 *
 * @implements IteratorAggregate<int, mixed>
 */
final class LazyJsonPages implements IteratorAggregate
{
    /**
     * Instantiate the class statically.
     *
     * @param Closure(Api): void $configure
     * @return LazyCollection<int, mixed>
     */
    public static function from(mixed $source, Closure $configure): LazyCollection
    {
        $source = new AnySource($source);
        $configure($api = new Api($source));

        return new LazyCollection(fn() => yield from new self($source, $api->toConfig()));
    }

    /**
     * Instantiate the class.
     */
    private function __construct(
        private readonly AnySource $source,
        private readonly Config $config,
    ) {}

    /**
     * Retrieve the paginated items lazily.
     *
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        // yield each item within a loop - instead of using `yield from` - to ignore the actual item index
        // and ensure indexes continuity, otherwise the index of items always starts from 0 on every page.
        foreach (new AnyPagination($this->source, $this->config) as $item) {
            yield $item;
        }
    }
}
