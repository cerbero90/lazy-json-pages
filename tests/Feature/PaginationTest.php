<?php

use Cerbero\LazyJsonPages\LazyJsonPages;

it('supports paginations aware of their total pages', function () {
    $expectedItems = require fixture('items.php');
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests($expectedItems, [
        'https://example.com/api/v1/users' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users?page=2' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users?page=3' => 'lengthAware/page3.json',
    ]);
});

it('supports paginations aware of their total items', function () {
    $expectedItems = require fixture('items.php');
    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->totalItems('meta.total_items')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests($expectedItems, [
        'https://example.com/api/v1/users' => 'lengthAware/page1.json',
        'https://example.com/api/v1/users?page=2' => 'lengthAware/page2.json',
        'https://example.com/api/v1/users?page=3' => 'lengthAware/page3.json',
    ]);
});
