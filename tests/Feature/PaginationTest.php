<?php

use Cerbero\LazyJsonPages\Exceptions\InvalidKeyException;
use Cerbero\LazyJsonPages\Exceptions\InvalidPaginationException;
use Cerbero\LazyJsonPages\Exceptions\UnsupportedPaginationException;
use Cerbero\LazyJsonPages\LazyJsonPages;
use Cerbero\LazyJsonPages\Services\ClientFactory;
use GuzzleHttp\Psr7\Response;

it('supports length-aware paginations', function (Closure $configure) {
    $lazyCollection = $configure(LazyJsonPages::from('https://example.com/api/v1/users'))
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?page=2' => 'pagination/page2.json',
        'https://example.com/api/v1/users?page=3' => 'pagination/page3.json',
    ], headers: [
        'X-Total-Pages' => 3,
        'X-Total-Items' => 14,
        'X-Last-Page' => 3,
        'Link' => '<https://example.com/api/v1/users?page=3>;rel="last"',
    ]);
})->with('length-aware');

it('supports length-aware paginations having 0 as first page', function (Closure $configure) {
    $lazyCollection = $configure(LazyJsonPages::from('https://example.com/api/v1/users'))
        ->firstPage(0)
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'paginationFirstPage0/page0.json',
        'https://example.com/api/v1/users?page=1' => 'paginationFirstPage0/page1.json',
        'https://example.com/api/v1/users?page=2' => 'paginationFirstPage0/page2.json',
    ], headers: [
        'X-Total-Pages' => 3,
        'X-Total-Items' => 14,
        'X-Last-Page' => 2,
        'Link' => '<https://example.com/api/v1/users?page=2>;rel="last"',
    ]);
})->with('length-aware');

it('supports cursor-aware paginations', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->cursor('meta.cursor')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?page=cursor1' => 'pagination/page2.json',
        'https://example.com/api/v1/users?page=cursor2' => 'pagination/page3.json',
    ]);
});

it('supports cursor-aware paginations with link header', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->linkHeader()
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?page=cursor1' => 'pagination/page2.json',
        'https://example.com/api/v1/users?page=cursor2' => 'pagination/page3.json',
    ], headers: (function() {
        yield ['Link' => '<https://example.com/api/v1/users?page=cursor1>;rel="next"'];
        yield ['Link' => '<https://example.com/api/v1/users?page=cursor2>;rel="next"'];
        yield ['Link' => ''];
    })());
});

it('loads only the first page if the link header does not contain links', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->linkHeader()
        ->collect('data.*');

    $responses = [
        new Response(body: file_get_contents(fixture('pagination/page1.json'))),
    ];

    $transactions = ClientFactory::fake($responses, fn() => expect($lazyCollection)->sequence(
        ['name' => 'item1'],
        ['name' => 'item2'],
        ['name' => 'item3'],
        ['name' => 'item4'],
        ['name' => 'item5'],
    ));

    expect($transactions)->toHaveCount(1);
    expect((string) $transactions[0]['request']->getUri())->toBe('https://example.com/api/v1/users');
});

it('fails if an invalid custom pagination is provided', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->pagination('Invalid')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
    ]);
})->throws(InvalidPaginationException::class, 'The class [Invalid] should extend [Cerbero\LazyJsonPages\Paginations\Pagination].');

it('fails if an invalid JSON key is provided', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->totalPages('invalid')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
    ]);
})->throws(InvalidKeyException::class, 'The key [invalid] does not contain a valid value.');

it('fails if a pagination is not supported', function () {
    LazyJsonPages::from('https://example.com/api/v1/users')
        ->collect('data.*')
        ->each(fn() => true);
})->throws(UnsupportedPaginationException::class, 'The provided configuration does not match with any supported pagination.');
