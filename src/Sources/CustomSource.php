<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The user-defined source.
 *
 * @property-read Source $source
 */
class CustomSource extends Source
{
    /**
     * Determine whether this class can handle the source.
     */
    public function matches(): bool
    {
        return $this->source instanceof Source;
    }

    /**
     * Retrieve the HTTP request.
     */
    public function request(): RequestInterface
    {
        return $this->source->setClient($this->client)->request();
    }

    /**
     * Retrieve the HTTP response.
     *
     * @return ResponseInterface
     */
    public function response(): ResponseInterface
    {
        return $this->source->setClient($this->client)->response();
    }
}
