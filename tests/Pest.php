<?php

use Cerbero\LazyJsonPages\Services\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

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

// uses(Tests\TestCase::class)->in('Feature');

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

expect()->extend('toLoadItemsViaRequests', function (array $requests) {
    $responses = $transactions = $expectedUris = [];

    foreach ($requests as $uri => $fixture) {
        $responses[] = new Response(body: file_get_contents(fixture($fixture)));
        $expectedUris[] = $uri;
    }

    $stack = HandlerStack::create(new MockHandler($responses));

    $stack->push(Middleware::history($transactions));

    Client::configure(['handler' => $stack]);

    $this->sequence(...require fixture('items.php'));

    $actualUris = array_map(fn(array $transaction) => (string) $transaction['request']->getUri(), $transactions);

    expect($actualUris)->toBe($expectedUris);
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
