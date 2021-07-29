<?php

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Exceptions\BadResponseException;
use Closure;
use Throwable;

/**
 * The trait to handle failures.
 *
 */
trait HandlesFailures
{
    /**
     * Attempt to fetch pages multiple times
     *
     * @param callable $callback
     * @return mixed
     */
    protected function retry(callable $callback)
    {
        $backoff = Closure::fromCallable($this->map->backoff ?: function (int $attempt) {
            return ($attempt - 1) ** 2 * 1000;
        });

        return retry($this->map->attempts, function () use ($callback) {
            return $this->checkResponses($callback());
        }, $backoff);
    }

    /**
     * Check whether the given responses were successful
     *
     * @param mixed $responses
     * @return mixed
     */
    protected function checkResponses($responses): mixed
    {
        $wrap = is_array($responses) ? $responses : [$responses];

        foreach ($wrap as $response) {
            if ($response instanceof Throwable) {
                throw $response;
            } elseif ($response->failed()) {
                throw new BadResponseException($response);
            }
        }

        return $responses;
    }
}
