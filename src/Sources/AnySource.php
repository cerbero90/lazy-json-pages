<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Cerbero\LazyJsonPages\Exceptions\UnsupportedSourceException;
use Generator;
use Psr\Http\Message\RequestInterface;

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
        LaravelRequest::class,
        Psr7Request::class,
        SymfonyRequest::class,
    ];

    /**
     * The matching source.
     */
    protected ?Source $matchingSource;

    /**
     * Determine whether the JSON source can be handled
     */
    public function matches(): bool
    {
        return true;
    }

    public function request(): RequestInterface
    {
        return $this->matchingSource()->request();
    }

    /**
     * Retrieve the matching source
     *
     * @throws UnsupportedSourceException
     */
    protected function matchingSource(): Source
    {
        if (isset($this->matchingSource)) {
            return $this->matchingSource;
        }

        foreach ($this->sources() as $source) {
            if ($source->matches()) {
                return $this->matchingSource = $source;
            }
        }

        throw new UnsupportedSourceException($this->source);
    }

    /**
     * Retrieve all available sources
     *
     * @return Generator<int, Source>
     */
    protected function sources(): Generator
    {
        foreach ($this->supportedSources as $source) {
            yield new $source($this->source);
        }
    }

    public function response(?string $key = null): mixed
    {
        return $this->matchingSource()->response($key);
    }
}
