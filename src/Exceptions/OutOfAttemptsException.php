<?php

namespace Cerbero\LazyJsonPages\Exceptions;

use Cerbero\LazyJsonPages\Services\Outcome;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\LazyCollection;

/**
 * The exception thrown when an HTTP request failed too many times.
 */
class OutOfAttemptsException extends LazyJsonPagesException
{
    /**
     * The pages that caused the failure.
     *
     * @var int[]
     */
    public readonly array $failedPages;

    /**
     * The paginated items loaded before the failure.
     *
     * @var LazyCollection<int, mixed>
     */
    public readonly LazyCollection $items;

    /**
     * Instantiate the class.
     */
    public function __construct(TransferException $e, Outcome $outcome)
    {
        $this->failedPages = $outcome->pullFailedPages();
        $this->items = new LazyCollection(fn() => yield from $outcome->pullItems());

        parent::__construct($e->getMessage(), 0, $e);
    }
}
