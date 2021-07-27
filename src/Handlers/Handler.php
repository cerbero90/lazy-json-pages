<?php

namespace Cerbero\LazyJsonPages\Handlers;

use Cerbero\LazyJsonPages\Map;
use Traversable;

/**
 * The handler contract.
 *
 */
interface Handler
{
    /**
     * Determine whether the handler can handle the given map
     *
     * @param Map $map
     * @return bool
     */
    public function handles(Map $map): bool;

    /**
     * Handle the given map
     *
     * @param Map $map
     * @return Traversable
     */
    public function handle(Map $map): Traversable;
}
