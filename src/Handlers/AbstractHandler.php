<?php

namespace Cerbero\LazyJsonPages\Handlers;

use Cerbero\LazyJsonPages\Concerns\RetriesHttpRequests;
use Cerbero\LazyJsonPages\Config;
use Traversable;

/**
 * The abstract handler.
 *
 */
abstract class AbstractHandler
{
    use RetriesHttpRequests;

    /**
     * The APIs configuration.
     *
     * @var Config
     */
    protected $config;

    /**
     * Instantiate the class.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Determine whether the handler can handle the APIs configuration
     *
     * @return bool
     */
    abstract public function matches(): bool;

    /**
     * Handle the APIs configuration
     *
     * @return Traversable
     */
    abstract public function handle(): Traversable;
}
