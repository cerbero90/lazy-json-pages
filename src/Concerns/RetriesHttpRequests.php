<?php

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Exceptions\OutOfAttemptsException;
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
     * @template TReturn
     *
     * @param (Closure(Outcome): TReturn) $callback
     * @return TReturn
     */
    protected function retry(Closure $callback): mixed
    {
        $attempt = 0;
        $remainingAttempts = $this->config->attempts;

        do {
            $attempt++;
            $remainingAttempts--;

            try {
                return $callback();
            } catch (TransferException $e) {
                if ($remainingAttempts > 0) {
                    $this->backoff($attempt);
                } else {
                    $this->outOfAttempts($e);
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
     * Throw the out of attempts exception.
     */
    protected function outOfAttempts(TransferException $e): never
    {
        throw new OutOfAttemptsException($e, $this->book->pullFailedPages(), function () {
            foreach ($this->book->pullPages() as $page) {
                yield from $this->yieldItemsFrom($page);
            }
        });
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
        $remainingAttempts = $this->config->attempts;

        do {
            $failed = false;
            $attempt++;
            $remainingAttempts--;

            try {
                yield from $callback();
            } catch (TransferException $e) {
                $failed = true;

                if ($remainingAttempts > 0) {
                    $this->backoff($attempt);
                } else {
                    $this->outOfAttempts($e);
                }
            }
        } while ($failed && $remainingAttempts > 0);
    }
}
