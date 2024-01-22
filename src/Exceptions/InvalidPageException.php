<?php

namespace Cerbero\LazyJsonPages\Exceptions;

/**
 * The exception thrown when a given JSON key does not contain a valid page.
 */
class InvalidPageException extends LazyJsonPagesException
{
    /**
     * Instantiate the class.
     */
    public function __construct(public string $key)
    {
        parent::__construct("The key [{$key}] does not contain a valid page.");
    }
}
