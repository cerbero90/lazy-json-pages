<?php

use Cerbero\LazyJsonPages\Exceptions\InvalidPageInPathException;
use Cerbero\LazyJsonPages\LazyJsonPages;

it('supports paginations with the current page in the URI path', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users/page/1')
        ->pageInPath()
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users/page/1' => 'pagination/page1.json',
        'https://example.com/api/v1/users/page/2' => 'pagination/page2.json',
        'https://example.com/api/v1/users/page/3' => 'pagination/page3.json',
    ]);
});

it('supports a custom pattern for paginations with the current page in the URI path', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users/page1')
        ->pageInPath('~/page(\d+)$~')
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users/page1' => 'pagination/page1.json',
        'https://example.com/api/v1/users/page2' => 'pagination/page2.json',
        'https://example.com/api/v1/users/page3' => 'pagination/page3.json',
    ]);
});

it('fails if it cannot capture the current page in the URI path', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/users')
        ->pageInPath()
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/users' => 'pagination/page1.json',
    ]);
})->throws(InvalidPageInPathException::class, 'The pattern [/(\d+)(?!.*\d)/] could not capture any page from the path [/users].');

it('supports paginations with offset', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->offset()
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?offset=5' => 'pagination/page2.json',
        'https://example.com/api/v1/users?offset=10' => 'pagination/page3.json',
    ]);
});

it('supports paginations with offset having 0 as first page', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->offset()
        ->firstPage(0)
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'paginationFirstPage0/page0.json',
        'https://example.com/api/v1/users?offset=5' => 'paginationFirstPage0/page1.json',
        'https://example.com/api/v1/users?offset=10' => 'paginationFirstPage0/page2.json',
    ]);
});

it('supports paginations with limit and offset', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users?limit=5')
        ->offset()
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users?limit=5' => 'pagination/page1.json',
        'https://example.com/api/v1/users?limit=5&offset=5' => 'pagination/page2.json',
        'https://example.com/api/v1/users?limit=5&offset=10' => 'pagination/page3.json',
    ]);
});

it('supports paginations with custom offset', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->offset('skip')
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?skip=5' => 'pagination/page2.json',
        'https://example.com/api/v1/users?skip=10' => 'pagination/page3.json',
    ]);
});
