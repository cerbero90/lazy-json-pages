<?php

namespace Cerbero\LazyJsonPages\Exceptions;

/**
 * The exception thrown when a page cannot be found in the URI path.
 */
class InvalidPageInPathException extends LazyJsonPagesException
{
    /**
     * Instantiate the class.
     */
    public function __construct(public readonly string $path, public readonly string $pattern)
    {
        parent::__construct("The pattern [{$pattern}] could not capture any page from the path [{$path}].");
    }
}
