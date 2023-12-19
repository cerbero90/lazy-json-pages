<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Cerbero\LazyJsonPages\Exceptions\UnsupportedSourceException;
use Generator;
use Traversable;

class AnySource implements Source
{
    /**
     * @var class-string<Source>[]
     */
    protected array $supportedSources = [
        CustomSource::class,
        Endpoint::class,
        LaravelClientResponse::class,
        Psr7Request::class,
    ];

    /**
     * The matching source.
     *
     * @var Source|null
     */
    protected ?Source $matchingSource;

    /**
     * Retrieve the JSON fragments
     *
     * @return Traversable<int, string>
     * @throws UnsupportedSourceException
     */
    public function getIterator(): Traversable
    {
        return $this->matchingSource();
    }

    /**
     * Retrieve the matching source
     *
     * @return Source
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
            yield new $source($this->source, $this->config);
        }
    }

    /**
     * Determine whether the JSON source can be handled
     *
     * @return bool
     */
    public function matches(): bool
    {
        return true;
    }

    /**
     * Retrieve the calculated size of the JSON source
     *
     * @return int|null
     */
    protected function calculateSize(): ?int
    {
        return $this->matchingSource()->size();
    }
}
