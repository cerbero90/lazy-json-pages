<?php

namespace Cerbero\LazyJsonPages\Exceptions;

use Cerbero\LazyJsonPages\Services\Outcome;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\LazyCollection;

/**
 * The exception thrown when a given JSON key does not contain a valid page.
 */
class InvalidPageException extends LazyJsonPagesException
{
    /**
     * Instantiate the class.
     */
    public function __construct(public string $key)
    {
        parent::__construct("The key [{$key}] does not contain a valid page.");
    }
}
