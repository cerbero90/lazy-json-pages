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
        // CustomSource::class,
        Endpoint::class,
        // LaravelClientRequest::class,
        // LaravelClientResponse::class,
        // LaravelRequest::class,
        // Psr7Request::class,
        // SymfonyRequest::class,
    ];

    /**
     * The matching source.
     */
    protected ?Source $matchingSource;

    /**
     * Determine whether this class can handle the source.
     */
    public function matches(): bool
    {
        return true;
    }

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
            $source = new $class($this->source);

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
        return $this->matchingSource()->response();
    }
}
