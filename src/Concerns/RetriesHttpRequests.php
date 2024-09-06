<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Exceptions\OutOfAttemptsException;
use Generator;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\LazyCollection;

/**
 * The trait to retry HTTP requests when they fail.
 *
 * @property-read \Cerbero\LazyJsonPages\Data\Config $config
 * @property-read \Cerbero\LazyJsonPages\Services\Book $book
 */
trait RetriesHttpRequests
{
    /**
     * Retry to yield HTTP responses from the given callback.
     *
     * @param callable $callback
     * @return Generator<int, mixed>
     */
    protected function retry(callable $callback): Generator
    {
        $attempt = 0;
        $remainingAttempts = $this->config->attempts;

        do {
            $failed = false;
            ++$attempt;
            --$remainingAttempts;

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

    /**
     * Execute the backoff strategy.
     */
    protected function backoff(int $attempt): void
    {
        $backoff = $this->config->backoff ?: fn(int $attempt) => $attempt ** 2 * 100;

        usleep($backoff($attempt) * 1000);
    }

    /**
     * Throw the out of attempts exception.
     */
    protected function outOfAttempts(TransferException $e): never
    {
        throw new OutOfAttemptsException($e, $this->book->pullFailedPages(), new LazyCollection(function () {
            foreach ($this->book->pullPages() as $page) {
                yield from $this->yieldItemsFrom($page);
            }
        }));
    }
}
