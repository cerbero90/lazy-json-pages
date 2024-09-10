<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Services\ClientFactory;

/**
 * The trait to respect rate limits of APIs.
 */
trait RespectsRateLimits
{
    /**
     * Delay the execution to respect rate limits.
     */
    protected function respectRateLimits(): void
    {
        if (microtime(true) < $timestamp = $this->config->rateLimits?->resetAt() ?? 0.0) {
            ClientFactory::isFake() ? ClientFactory::$fakedRateLimits[] = $timestamp : time_sleep_until($timestamp);
        }
    }
}
