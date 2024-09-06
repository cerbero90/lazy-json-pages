<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Concerns;

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
        if (microtime(true) < $timestamp = $this->config->rateLimits?->resetAt() ?? 0) {
            time_sleep_until($timestamp);
        }
    }
}
