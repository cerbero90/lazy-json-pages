<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Data;

/**
 * The API rate limit to respect.
 */
final class RateLimit
{
    /**
     * The number of requests sent before this rate limit resets.
     */
    private int $hits = 0;

    /**
     * The timestamp when this rate limit resets.
     */
    private ?float $resetsAt = null;

    /**
     * Instantiate the class.
     */
    public function __construct(
        public readonly int $requests,
        public readonly int $perSeconds,
    ) {}

    /**
     * Update the requests sent before this rate limit resets.
     */
    public function hit(): self
    {
        $this->hits++;
        $this->resetsAt ??= microtime(true) + $this->perSeconds;

        return $this;
    }

    /**
     * Retrieve the number of requests allowed before this rate limit resets.
     */
    public function threshold(): int
    {
        return $this->requests - $this->hits;
    }

    /**
     * Determine whether this rate limit was reached.
     */
    public function wasReached(): bool
    {
        return $this->hits == $this->requests;
    }

    /**
     * Retrieve the timestamp when this rate limit resets.
     */
    public function resetsAt(): ?float
    {
        return $this->resetsAt;
    }

    /**
     * Reset this rate limit.
     */
    public function reset(): self
    {
        $this->hits = 0;
        $this->resetsAt = null;

        return $this;
    }
}
