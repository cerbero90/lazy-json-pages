<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Cerbero\LazyJsonPages\Exceptions\RequestNotSentException;
use Cerbero\LazyJsonPages\Services\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Http\Client\Response;

/**
 * The Laravel HTTP client source.
 *
 * @property-read Response $source
 */
class LaravelClientResponse extends Source
{
    /**
     * Determine whether this class can handle the source.
     */
    public function matches(): bool
    {
        return $this->source instanceof Response;
    }

    /**
     * Retrieve the HTTP request.
     */
    public function request(): RequestInterface
    {
        return $this->source->transferStats?->getRequest()
            ?: throw new RequestNotSentException();
    }

    /**
     * Retrieve the HTTP response.
     *
     * @return ResponseInterface
     */
    public function response(): ResponseInterface
    {
        return $this->source->toPsrResponse();
    }
}
