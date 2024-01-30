<?php

use Cerbero\LazyJsonPages\Exceptions\InvalidPageInPathException;
use Cerbero\LazyJsonPages\LazyJsonPages;

it('supports paginations with the current page in the URI path', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users/page/1')
        ->pageInPath()
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users/page/1' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users/page/2' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users/page/3' => 'lengthAware/page3.json',
    ]);
});

it('supports a custom pattern for paginations with the current page in the URI path', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users/page1')
        ->pageInPath('~/page(\d+)$~')
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users/page1' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users/page2' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users/page3' => 'lengthAware/page3.json',
    ]);
});

it('fails if it cannot capture the current page in the URI path', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/users')
        ->pageInPath()
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/users' => 'lengthAware/page1.json',
    ]);
})->throws(InvalidPageInPathException::class, 'The pattern [/(\d+)(?!.*\d)/] could not capture any page from the path [/users].');

it('supports paginations with offset', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->offset()
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users?offset=5' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users?offset=10' => 'lengthAware/page3.json',
    ]);
});

it('supports paginations with limit and offset', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users?limit=5')
        ->offset()
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users?limit=5' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users?limit=5&offset=5' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users?limit=5&offset=10' => 'lengthAware/page3.json',
    ]);
});

it('supports paginations with custom offset', function () {
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->offset('skip')
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users?skip=5' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users?skip=10' => 'lengthAware/page3.json',
    ]);
});
