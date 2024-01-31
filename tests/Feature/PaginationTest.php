<?php

use Cerbero\LazyJsonPages\Exceptions\InvalidPaginationException;
use Cerbero\LazyJsonPages\LazyJsonPages;

it('supports length-aware paginations', function (Closure $configure) {
    $lazyCollection = $configure(LazyJsonPages::from('https://example.com/api/v1/users'))
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users?page=2' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users?page=3' => 'lengthAware/page3.json',
    ]);
})->with('length-aware');

it('supports length-aware paginations having 0 as first page', function (Closure $configure) {
    $lazyCollection = $configure(LazyJsonPages::from('https://example.com/api/v1/users'))
        ->firstPage(0)
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'lengthAwareFirstPage0/page0.json',
        'https://example.com/api/v1/users?page=1' => 'lengthAwareFirstPage0/page1.json',
        'https://example.com/api/v1/users?page=2' => 'lengthAwareFirstPage0/page2.json',
    ]);
})->with('length-aware');

it('fails if an invalid custom pagination is provided', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->pagination('Invalid')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'lengthAware/page1.json',
    ]);
})->throws(InvalidPaginationException::class, 'The class [Invalid] should extend [Cerbero\LazyJsonPages\Paginations\Pagination].');
