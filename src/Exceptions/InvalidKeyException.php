<?php

namespace Cerbero\LazyJsonPages\Exceptions;

/**
 * The exception thrown when a given JSON key does not contain a valid value.
 */
class InvalidKeyException extends LazyJsonPagesException
{
    /**
     * Instantiate the class.
     */
    public function __construct(public readonly string $key)
    {
        parent::__construct("The key [{$key}] does not contain a valid value.");
    }
}
