<?php

namespace Cerbero\LazyJsonPages\Exceptions;

/**
 * The exception thrown when a source did not send any HTTP request.
 */
class RequestNotSentException extends LazyJsonPagesException
{
    /**
     * Instantiate the class.
     */
    public function __construct()
    {
        parent::__construct("The source did not send any HTTP request.");
    }
}
