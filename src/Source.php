<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJsonPages\Exceptions\LazyJsonPagesException;
use Cerbero\LazyJsonPages\Handlers;
use Illuminate\Http\Client\Response;
use IteratorAggregate;
use Traversable;

/**
 * The JSON source.
 *
 */
class Source implements IteratorAggregate
{
    /**
     * The traversable JSON.
     *
     * @var Traversable
     */
    protected $traversable;

    /**
     * The source handlers.
     *
     * @var array
     */
    protected $handlers = [
        Handlers\TotalPagesAware::class,
        // Handlers\TotalItemsAware::class,
        // Handlers\ItemsPerPageAware::class,
        // Handlers\NextPageAware::class,
        // Handlers\NextPagesAware::class,
    ];

    /**
     * Instantiate the class.
     *
     * @param Response $source
     * @param string $path
     * @param callable|array|string|int $map
     */
    public function __construct(Response $source, string $path, $map)
    {
        $this->traversable = $this->toTraversable(new Map($source, $path, $map));
    }

    /**
     * Turn the given mapped JSON into traversable items
     *
     * @param Map $map
     * @return Traversable
     *
     * @throws LazyJsonPagesException
     */
    protected function toTraversable(Map $map): Traversable
    {
        foreach ($this->handlers as $class) {
            /** @var Handlers\Handler $handler */
            $handler = new $class();

            if ($handler->handles($map)) {
                return $handler->handle($map);
            }
        }

        throw new LazyJsonPagesException('Unable to load paginated items from the provided source.');
    }

    /**
     * Retrieve the traversable items across all pages
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return $this->traversable;
    }
}
