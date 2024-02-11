<?php

use Cerbero\LazyJsonPages\Services\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(OrchestraTestCase::class, WithWorkbench::class)->in('Integration/LaravelTest.php');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toLoadItemsViaRequests', function (array $requests, Generator|array $headers = []) {
    $responses = $transactions = $expectedUris = [];
    $responseHeaders = $headers;

    foreach ($requests as $uri => $fixture) {
        if ($headers instanceof Generator) {
            $responseHeaders = $headers->current();
            $headers->valid() && $headers->next();
        }

        $responses[] = new Response(body: file_get_contents(fixture($fixture)), headers: $responseHeaders);
        $expectedUris[] = $uri;
    }

    $stack = HandlerStack::create(new MockHandler($responses));

    $stack->push(Middleware::history($transactions));

    Client::configure(['handler' => $stack]);

    $this->sequence(...require fixture('items.php'));

    $actualUris = array_map(fn(array $transaction) => (string) $transaction['request']->getUri(), $transactions);

    expect($actualUris)->toBe($expectedUris);
});

expect()->extend('toFailRequest', function (string $uri) {
    $transactions = [];

    $responses = [$exception = new RequestException('connection failed', new Request('GET', $uri))];

    $stack = HandlerStack::create(new MockHandler($responses));

    $stack->push(Middleware::history($transactions));

    Client::configure(['handler' => $stack]);

    try {
        iterator_to_array($this->value);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }

    expect($transactions)->toHaveCount(1);
    expect((string) $transactions[0]['request']->getUri())->toBe($uri);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function fixture(string $filename) {
    return __DIR__ . "/fixtures/{$filename}";
}
