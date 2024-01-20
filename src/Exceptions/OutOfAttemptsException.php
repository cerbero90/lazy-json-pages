<?php

namespace Cerbero\LazyJsonPages\Exceptions;

use Closure;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\LazyCollection;

/**
 * The exception thrown when an HTTP request failed too many times.
 */
class OutOfAttemptsException extends LazyJsonPagesException
{
    /**
     * The paginated items loaded before the failure.
     *
     * @var LazyCollection<int, mixed>
     */
    public readonly LazyCollection $items;

    /**
     * Instantiate the class.
     *
     * @param array<int, int> $failedPages
     * @param (Closure(): Generator<int, mixed>) $items
     */
    public function __construct(TransferException $e, public readonly array $failedPages, Closure $items)
    {
        $this->items = new LazyCollection($items);

        parent::__construct($e->getMessage(), 0, $e);
    }
}
