<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Cerbero\LazyJsonPages\Exceptions\UnsupportedSourceException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The aggregator of sources.
 */
class AnySource extends Source
{
    /**
     * @var class-string<Source>[]
     */
    protected array $supportedSources = [
        CustomSource::class,
        Endpoint::class,
        LaravelClientRequest::class,
        LaravelClientResponse::class,
        Psr7Request::class,
        SymfonyRequest::class,
    ];

    /**
     * The matching source.
     */
    protected readonly Source $matchingSource;

    /**
     * The cached HTTP response.
     */
    protected ?ResponseInterface $response;

    /**
     * Retrieve the HTTP request.
     */
    public function request(): RequestInterface
    {
        return $this->matchingSource()->request();
    }

    /**
     * Retrieve the matching source.
     *
     * @throws UnsupportedSourceException
     */
    protected function matchingSource(): Source
    {
        if (isset($this->matchingSource)) {
            return $this->matchingSource;
        }

        foreach ($this->supportedSources as $class) {
            $source = new $class($this->source, $this->client);

            if ($source->matches()) {
                return $this->matchingSource = $source;
            }
        }

        throw new UnsupportedSourceException($this->source);
    }

    /**
     * Retrieve the HTTP response.
     *
     * @return ResponseInterface
     */
    public function response(): ResponseInterface
    {
        return $this->response ??= $this->matchingSource()->response();
    }

    /**
     * Retrieve the HTTP response and forget it to save memory.
     */
    public function pullResponse(): ResponseInterface
    {
        $response = $this->response();

        $this->response = null;

        return $response;
    }
}
