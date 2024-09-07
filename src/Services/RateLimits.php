<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Services;

use Cerbero\LazyJsonPages\Data\RateLimit;

/**
 * The rate limits aggregator.
 */
final class RateLimits
{
    /**
     * The API rate limits to respect.
     *
     * @var RateLimit[]
     */
    private array $rateLimits = [];

    /**
     * Add the given rate limit.
     */
    public function add(int $requests, int $perSeconds): self
    {
        $this->rateLimits[] = new RateLimit($requests, $perSeconds);

        return $this;
    }

    /**
     * Hit the rate limits with a request.
     */
    public function hit(): self
    {
        foreach ($this->rateLimits as $rateLimit) {
            $rateLimit->hit();
        }

        return $this;
    }

    /**
     * Retrieve the number of requests allowed before the next rate limit.
     */
    public function threshold(): int
    {
        $threshold = INF;

        foreach ($this->rateLimits as $rateLimit) {
            $threshold = min($rateLimit->threshold(), $threshold);
        }

        return (int) $threshold;
    }

    /**
     * Retrieve the timestamp after which it is possible to send new requests.
     */
    public function resetAt(): float
    {
        $timestamp = 0.0;

        foreach ($this->rateLimits as $rateLimit) {
            if ($rateLimit->wasReached()) {
                $timestamp = max($rateLimit->resetsAt() ?? 0.0, $timestamp);

                $rateLimit->reset();
            }
        }

        return $timestamp;
    }
}
