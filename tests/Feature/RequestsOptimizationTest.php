<?php

use Cerbero\LazyJsonPages\Exceptions\OutOfAttemptsException;
use Cerbero\LazyJsonPages\LazyJsonPages;
use Cerbero\LazyJsonPages\Services\ClientFactory;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

it('adds middleware for Guzzle', function () {
    $log = collect();

    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->middleware('log', Middleware::tap(fn() => $log->push('before'), fn() => $log->push('after')))
        ->onRequest(fn() => $log->push('onRequest'))
        ->onResponse(fn() => $log->push('onResponse'))
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?page=2' => 'pagination/page2.json',
        'https://example.com/api/v1/users?page=3' => 'pagination/page3.json',
    ]);

    expect($log)->sequence(...[
        'before',
        'onRequest',
        'after',
        'onResponse',
        'before',
        'onRequest',
        'after',
        'onResponse',
        'before',
        'onRequest',
        'after',
        'onResponse',
    ]);
});

it('handles transaction errors', function () {
    $log = collect();

    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->middleware('log', Middleware::tap(fn() => $log->push('before'), fn() => $log->push('after')))
        ->onError(fn() => $log->push('onError'))
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toFailRequest('https://example.com/api/v1/users');

    expect($log)->sequence('before', 'after', 'onError');
});

it('sends HTTP requests asynchronously', function () {
    $log = collect();

    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->onRequest(fn() => $log->push('sending'))
        ->onResponse(fn() => $log->push('sent'))
        ->async(3)
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?page=2' => 'pagination/page2.json',
        'https://example.com/api/v1/users?page=3' => 'pagination/page3.json',
    ]);

    expect($log)->sequence('sending', 'sent', 'sending', 'sending', 'sent', 'sent');
});

it('handles failures when sending HTTP requests asynchronously', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->async(3)
        ->attempts(3)
        ->backoff(fn() => 0)
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    $responses = [
        new Response(body: file_get_contents(fixture('paginationWith5Pages/page1.json'))),
        new RequestException('connection failed', new Request('GET', 'https://example.com/api/v1/users?page=2')),
        new RequestException('connection failed', new Request('GET', 'https://example.com/api/v1/users?page=2')),
        new RequestException('connection failed', new Request('GET', 'https://example.com/api/v1/users?page=2')),
        new RequestException('connection failed', new Request('GET', 'https://example.com/api/v1/users?page=2')),
        new RequestException('connection failed', new Request('GET', 'https://example.com/api/v1/users?page=2')),
        new RequestException('connection failed', new Request('GET', 'https://example.com/api/v1/users?page=2')),
        $e = new RequestException('connection failed', new Request('GET', 'https://example.com/api/v1/users?page=2')),
        new Response(body: file_get_contents(fixture('paginationWith5Pages/page3.json'))),
    ];

    ClientFactory::fake($responses, function() use ($lazyCollection, $e) {
        try {
            $lazyCollection->toArray();
        } catch (Throwable $exception) {
            expect($exception)
                ->toBeInstanceOf(OutOfAttemptsException::class)
                ->getPrevious()->toBe($e)
                ->failedPages->toContain(2)
                ->items->pluck('name')->all()->toContain('item11', 'item12', 'item13', 'item14', 'item15');
        }
    });
});

it('sets the timeouts', function () {
    LazyJsonPages::from('https://nonexisting.test')
        ->totalPages('meta.total_pages')
        ->connectionTimeout(0.01)
        ->requestTimeout(0.01)
        ->collect('data.*')
        ->each(fn() => true);
})->throws(ConnectException::class, 'Connection refused for URI https://nonexisting.test');

it('respects rate limits', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->totalPages('meta.total_pages')
        ->throttle(requests: 1, perSeconds: 1)
        ->collect('data.*');

    $responses = [
        new Response(body: file_get_contents(fixture('pagination/page1.json'))),
        new Response(body: file_get_contents(fixture('pagination/page2.json'))),
        new Response(body: file_get_contents(fixture('pagination/page3.json'))),
    ];

    $transactions = ClientFactory::fake($responses, function () use ($lazyCollection) {
        expect($lazyCollection)->sequence(...require fixture('items.php'));
        expect(ClientFactory::$fakedRateLimits)->toHaveCount(3);
    });

    $actualUris = array_map(fn(array $transaction) => (string) $transaction['request']->getUri(), $transactions);

    expect($actualUris)->toBe([
        'https://example.com/api/v1/users',
        'https://example.com/api/v1/users?page=2',
        'https://example.com/api/v1/users?page=3',
    ]);
});
