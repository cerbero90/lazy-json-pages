<?php

namespace Cerbero\LazyJsonPages\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * The bad response exception.
 *
 */
class BadResponseException extends LazyJsonPagesException
{
    /**
     * The failed response.
     *
     * @var Response
     */
    public $response;

    /**
     * Instantiate the class.
     *
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }
}
