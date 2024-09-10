<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Illuminate\Http\Client\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The Laravel HTTP client source.
 *
 * @property-read Request $source
 */
class LaravelClientRequest extends Source
{
    /**
     * Determine whether this class can handle the source.
     */
    public function matches(): bool
    {
        return $this->source instanceof Request;
    }

    /**
     * Retrieve the HTTP request.
     */
    public function request(): RequestInterface
    {
        return $this->source->toPsrRequest();
    }

    /**
     * Retrieve the HTTP response.
     *
     * @return ResponseInterface
     */
    public function response(): ResponseInterface
    {
        return $this->client->send($this->request());
    }
}
