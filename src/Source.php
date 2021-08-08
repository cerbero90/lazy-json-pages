<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJsonPages\Exceptions\LazyJsonPagesException;
use Cerbero\LazyJsonPages\Handlers;
use IteratorAggregate;
use Traversable;

/**
 * The JSON source.
 *
 */
class Source implements IteratorAggregate
{
    /**
     * The traversable items.
     *
     * @var Traversable
     */
    protected $traversable;

    /**
     * The pagination handlers.
     *
     * @var array
     */
    protected $handlers = [
        Handlers\TotalPagesHandler::class,
        Handlers\TotalItemsHandler::class,
        Handlers\ItemsPerPageHandler::class,
        Handlers\NextPageHandler::class,
        Handlers\LastPageHandler::class,
    ];

    /**
     * Instantiate the class.
     *
     * @param \Psr\Http\Message\RequestInterface|\Illuminate\Http\Client\Response $source
     * @param string $path
     * @param callable|array|string|int $config
     */
    public function __construct($source, string $path, $config)
    {
        $this->traversable = $this->toTraversable(new Config($source, $path, $config));
    }

    /**
     * Retrieve the traversable items depending on the given configuration
     *
     * @param Config $config
     * @return Traversable
     *
     * @throws LazyJsonPagesException
     */
    protected function toTraversable(Config $config): Traversable
    {
        foreach ($this->handlers as $class) {
            /** @var Handlers\AbstractHandler $handler */
            $handler = new $class($config);

            if ($handler->matches()) {
                return $handler->handle();
            }
        }

        throw new LazyJsonPagesException('Unable to load paginated items from the provided source.');
    }

    /**
     * Retrieve the traversable items
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return $this->traversable;
    }
}
