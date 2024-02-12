<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The abstract implementation of a source.
 */
abstract class Source
{
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

    /**
     * Instantiate the class.
     */
    final public function __construct(
        protected readonly mixed $source,
        protected readonly Client $client,
    ) {}

    /**
     * Determine whether this class can handle the source.
     */
    public function matches(): bool
    {
        return true;
    }
}
