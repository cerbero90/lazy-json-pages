<?php

namespace Cerbero\LazyJsonPages;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * The fixtures aware trait.
 *
 */
trait FixturesAware
{
    /**
     * Retrieve the given fixture as a response
     *
     * @param string $fixture
     * @return ResponseInterface
     */
    protected function fixture(string $fixture): ResponseInterface
    {
        $json = file_get_contents(__DIR__ . "/fixtures/{$fixture}.json");

        return new Response(200, [], $json);
    }

    /**
     * Retrieve the given fixture as a promise
     *
     * @param string $fixture
     * @return PromiseInterface
     */
    protected function promiseFixture(string $fixture): PromiseInterface
    {
        return $promise = new Promise(function () use ($fixture, &$promise) {
            $promise->resolve($this->fixture($fixture));
        });
    }
}
