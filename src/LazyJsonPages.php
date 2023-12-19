<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJsonPages\Dtos\Config;
use Cerbero\LazyJsonPages\Exceptions\LazyJsonPagesException;
use Cerbero\LazyJsonPages\Paginations\AnyPagination;
use Cerbero\LazyJsonPages\Services\ConfigFactory;
use Cerbero\LazyJsonPages\Sources\AnySource;
use Closure;
use Illuminate\Support\LazyCollection;
use IteratorAggregate;
use Throwable;
use Traversable;

/**
 * @implements IteratorAggregate<string|int, mixed>
 */
final class LazyJsonPages implements IteratorAggregate
{
    /**
     * @param Closure(ConfigFactory): void $configure
     * @return LazyCollection<string|int, mixed>
     */
    public static function from(mixed $source, Closure $configure): LazyCollection
    {
        $source = new AnySource($source);
        $configure($config = new ConfigFactory($source));

        return new LazyCollection(fn() => yield from new self($source, $config->make()));
    }

    private function __construct(
        private readonly AnySource $source,
        private readonly Config $config,
    ) {
    }

    /**
     * @return Traversable<string|int, mixed>
     */
    public function getIterator(): Traversable
    {
        try {
            yield from new AnyPagination($this->source, $this->config);
        } catch (Throwable $e) {
            throw LazyJsonPagesException::from($e);
        }
    }
}
