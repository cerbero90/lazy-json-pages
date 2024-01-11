<?php

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Exceptions\OutOfAttemptsException;
use Cerbero\LazyJsonPages\Services\Outcome;
use Closure;
use Generator;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\Sleep;

/**
 * The trait to retry HTTP requests when they fail.
 *
 */
trait RetriesHttpRequests
{
    /**
     * Retry to return HTTP responses from the given callback.
     *
     * @param Closure(Outcome) $callback
     */
    protected function retry(Closure $callback): mixed
    {
        $attempt = 0;
        $outcome = new Outcome();
        $remainingAttempts = $this->config->attempts;

        do {
            $attempt++;
            $remainingAttempts--;

            try {
                return $callback($outcome);
            } catch (TransferException $e) {
                if ($remainingAttempts > 0) {
                    $this->backoff($attempt);
                } else {
                    throw new OutOfAttemptsException($e, $outcome);
                }
            }
        } while ($remainingAttempts > 0);
    }

    /**
     * Execute the backoff strategy.
     */
    protected function backoff(int $attempt): void
    {
        $backoff = $this->config->backoff ?: fn(int $attempt) => $attempt ** 2 * 100;

        Sleep::for($backoff($attempt) * 1000)->microseconds();
    }

    /**
     * Retry to yield HTTP responses from the given callback.
     *
     * @param callable $callback
     * @return Generator<int, mixed>
     */
    protected function retryYielding(callable $callback): Generator
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
            } catch (TransferException $e) {
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
