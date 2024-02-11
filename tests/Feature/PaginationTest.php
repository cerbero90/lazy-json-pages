<?php

use Cerbero\LazyJsonPages\Exceptions\InvalidPaginationException;
use Cerbero\LazyJsonPages\LazyJsonPages;

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

it('fails if an invalid custom pagination is provided', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->pagination('Invalid')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
    ]);
})->throws(InvalidPaginationException::class, 'The class [Invalid] should extend [Cerbero\LazyJsonPages\Paginations\Pagination].');
