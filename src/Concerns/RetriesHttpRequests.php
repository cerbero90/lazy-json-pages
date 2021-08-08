<?php

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Exceptions\OutOfAttemptsException;
use Cerbero\LazyJsonPages\Outcome;
use Throwable;

/**
 * The trait to retry HTTP requests when they fail.
 *
 */
trait RetriesHttpRequests
{
    /**
     * Retry to return the result of HTTP requests
     *
     * @param callable $callback
     * @return mixed
     */
    protected function retry(callable $callback)
    {
        $attempt = 0;
        $outcome = new Outcome();
        $remainingAttempts = $this->config->attempts;

        do {
            $attempt++;
            $remainingAttempts--;

            try {
                return $callback($outcome);
            } catch (Throwable $e) {
                if ($remainingAttempts > 0) {
                    $this->backoff($attempt);
                } else {
                    throw new OutOfAttemptsException($e, $outcome);
                }
            }
        } while ($remainingAttempts > 0);
    }

    /**
     * Execute the backoff strategy
     *
     * @param int $attempt
     * @return void
     */
    protected function backoff(int $attempt): void
    {
        $backoff = $this->config->backoff ?: function (int $attempt) {
            return ($attempt - 1) ** 2 * 1000;
        };

        usleep($backoff($attempt) * 1000);
    }

    /**
     * Retry to yield the result of HTTP requests
     *
     * @param callable $callback
     * @return mixed
     */
    protected function retryYielding(callable $callback)
    {
        $attempt = 0;
        $outcome = new Outcome();
        $remainingAttempts = $this->config->attempts;

        do {
            $failed = false;
            $attempt++;
            $remainingAttempts--;

            try {
                yield from $callback($outcome);
            } catch (Throwable $e) {
                $failed = true;

                if ($remainingAttempts > 0) {
                    $this->backoff($attempt);
                } else {
                    throw new OutOfAttemptsException($e, $outcome);
                }
            }
        } while ($failed && $remainingAttempts > 0);
    }
}
