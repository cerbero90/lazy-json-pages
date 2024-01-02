<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Cerbero\JsonParser\Concerns\DetectsEndpoints;
use Cerbero\LazyJsonPages\ValueObjects\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
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
     * The HTTP request.
     */
    protected RequestInterface $request;

    /**
     * The HTTP response value object
     */
    protected Response $response;

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
        return $this->request ??= new Request('GET', $this->source, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Retrieve the HTTP response or part of it.
     *
     * @return ($key is string ? mixed : \Cerbero\LazyJsonPages\ValueObjects\Response)
     */
    public function response(?string $key = null): mixed
    {
        $this->response ??= (new Client())->send($this->request());

        return $key === null ? $this->response : $this->response->get($key);
    }
}
