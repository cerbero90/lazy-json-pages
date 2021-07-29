<?php

namespace Cerbero\LazyJsonPages\Handlers;

use Cerbero\LazyJsonPages\Map;
use Traversable;

/**
 * The abstract handler.
 *
 */
abstract class AbstractHandler
{
    /**
     * The JSON API map.
     *
     * @var Map
     */
    protected $map;

    /**
     * The HTTP request.
     *
     * @var \GuzzleHttp\Psr7\Request
     */
    protected $request;

    /**
     * Instantiate the class.
     *
     * @param Map $map
     */
    public function __construct(Map $map)
    {
        $this->map = $map;
        $this->request = $map->source->transferStats->getRequest();
    }

    /**
     * Determine whether the handler can handle the JSON API map
     *
     * @return bool
     */
    abstract public function matches(): bool;

    /**
     * Handle the JSON API map
     *
     * @return Traversable
     */
    abstract public function handle(): Traversable;
}
