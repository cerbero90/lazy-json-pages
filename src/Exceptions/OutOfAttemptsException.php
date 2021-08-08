<?php

namespace Cerbero\LazyJsonPages\Exceptions;

use Cerbero\LazyJsonPages\Outcome;
use Illuminate\Support\LazyCollection;
use Throwable;

/**
 * The out of attempts exception.
 *
 */
class OutOfAttemptsException extends LazyJsonPagesException
{
    /**
     * The original exception.
     *
     * @var Throwable
     */
    public $original;

    /**
     * The pages that caused the failure.
     *
     * @var array
     */
    public $failedPages;

    /**
     * The paginated items loaded before the failure.
     *
     * @var LazyCollection
     */
    public $items;

    /**
     * Instantiate the class.
     *
     * @param Throwable $original
     * @param Outcome $outcome
     */
    public function __construct(Throwable $original, Outcome $outcome)
    {
        $this->message = $original->getMessage();
        $this->original = $original;
        $this->failedPages = $outcome->pullFailedPages();
        $this->items = new LazyCollection(function () use ($outcome) {
            yield from $outcome->pullItems();
        });
    }
}
