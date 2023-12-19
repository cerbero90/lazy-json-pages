<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Exceptions;

final class UnsupportedSourceException extends LazyJsonPagesException
{
    /**
     * @param mixed $source
     */
    public function __construct(public readonly mixed $source)
    {
        parent::__construct('The provided source is not supported');
    }
}
