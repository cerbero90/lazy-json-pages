<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The class to test the implementation of custom sources.
 */
class CustomSourceSample extends Source
{
    /**
     * Retrieve the HTTP request.
     */
    public function request(): RequestInterface
    {
        return new Request('GET', 'https://example.com/api/v1/users');
    }

    /**
     * Retrieve the HTTP response.
     *
     * @return ResponseInterface
     */
    public function response(): ResponseInterface
    {
        return new Response(body: file_get_contents(fixture('lengthAware/page1.json')));
    }
}
