<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Exceptions;

/**
 * The exception to throw when the given source is not supported.
 */
class UnsupportedSourceException extends LazyJsonPagesException
{
    /**
     * Instantiate the class.
     */
    public function __construct(public readonly mixed $source)
    {
        parent::__construct('The provided source is not supported.');
    }
}
