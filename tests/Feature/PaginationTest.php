<?php

use Cerbero\LazyJsonPages\Exceptions\InvalidPaginationException;
use Cerbero\LazyJsonPages\LazyJsonPages;
use Cerbero\LazyJsonPages\Paginations\TotalPagesAwarePagination;

it('supports paginations aware of their total pages', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users?page=2' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users?page=3' => 'lengthAware/page3.json',
    ]);
});

it('supports paginations aware of their total items', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->totalItems('meta.total_items')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users?page=2' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users?page=3' => 'lengthAware/page3.json',
    ]);
});

it('supports custom paginations', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->pagination(TotalPagesAwarePagination::class)
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users?page=2' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users?page=3' => 'lengthAware/page3.json',
    ]);
});

it('fails if an invalid custom pagination is provided', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->pagination('Invalid')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'lengthAware/page1.json',
    ]);
})->throws(InvalidPaginationException::class, 'The class [Invalid] should extend [Cerbero\LazyJsonPages\Paginations\Pagination].');
