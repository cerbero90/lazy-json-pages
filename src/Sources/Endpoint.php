<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Cerbero\JsonParser\Concerns\DetectsEndpoints;
use Cerbero\LazyJsonPages\Services\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * The JSON endpoint source.
 *
 * @property-read UriInterface|string $source
 */
class Endpoint extends Source
{
    use DetectsEndpoints;

    /**
     * Determine whether this class can handle the source.
     */
    public function matches(): bool
    {
        return $this->source instanceof UriInterface
            || (is_string($this->source) && $this->isEndpoint($this->source));
    }

    /**
     * Retrieve the HTTP request.
     */
    public function request(): RequestInterface
    {
        return new Request('GET', $this->source);
    }

    /**
     * Retrieve the HTTP response.
     *
     * @return ResponseInterface
     */
    public function response(): ResponseInterface
    {
        return Client::instance()->send($this->request());
    }
}
