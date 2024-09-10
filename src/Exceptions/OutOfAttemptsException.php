<?php

namespace Cerbero\LazyJsonPages\Exceptions;

use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\LazyCollection;

/**
 * The exception thrown when an HTTP request failed too many times.
 */
class OutOfAttemptsException extends LazyJsonPagesException
{
    /**
     * Instantiate the class.
     *
     * @param array<int, int> $failedPages
     * @param LazyCollection<int, mixed> $items
     */
    public function __construct(
        TransferException $e,
        public readonly array $failedPages,
        public readonly LazyCollection $items,
    ) {
        parent::__construct($e->getMessage(), 0, $e);
    }
}
