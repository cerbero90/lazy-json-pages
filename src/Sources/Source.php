<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The abstract implementation of a source.
 */
abstract class Source
{
    final public function __construct(
        protected readonly mixed $source,
    ) {}

    /**
     * Determine whether this class can handle the source.
     */
    abstract public function matches(): bool;

    /**
     * Retrieve the HTTP request.
     */
    abstract public function request(): RequestInterface;

    /**
     * Retrieve the HTTP response.
     *
     * @return ResponseInterface
     */
    abstract public function response(): ResponseInterface;
}
