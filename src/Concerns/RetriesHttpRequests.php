<?php

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Exceptions\OutOfAttemptsException;
use Cerbero\LazyJsonPages\Outcome;
use Closure;
use Throwable;

/**
 * The trait to retry HTTP requests when they fail.
 *
 */
trait RetriesHttpRequests
{
    /**
     * Retry HTTP requests and keep track of their outcome
     *
     * @param callable $callback
     * @return mixed
     */
    protected function retry(callable $callback)
    {
        $attempt = 0;
        $outcome = new Outcome();
        $remainingAttempts = $this->config->attempts;
        $backoff = Closure::fromCallable($this->config->backoff ?: function (int $attempt) {
            return ($attempt - 1) ** 2 * 1000;
        });

        do {
            $attempt++;
            $remainingAttempts--;

            try {
                return $callback($outcome);
            } catch (Throwable $e) {
                if ($remainingAttempts > 0) {
                    usleep($backoff($attempt) * 1000);
                } else {
                    throw new OutOfAttemptsException($e, $outcome);
                }
            }
        } while ($remainingAttempts > 0);
    }
}
