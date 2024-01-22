<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Cerbero\LazyJsonPages\Services\Client;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The Symfony request source.
 *
 * @property-read Request $source
 */
class SymfonyRequest extends Source
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
        return new Psr7Request(
            $this->source->getMethod(),
            $this->source->getUri(),
            $this->source->headers->all(),
            $this->source->getContent() ?: null,
        );
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
