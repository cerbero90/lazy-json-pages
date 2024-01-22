<?php

use Cerbero\LazyJsonPages\LazyJsonPages;

it('supports multiple sources', function (mixed $source, bool $requestsFirstPage) {
    $expectedItems = require fixture('items.php');
    $lazyCollection = LazyJsonPages::from($source)
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests($expectedItems, [
        ...$requestsFirstPage ? ['https://example.com/api/v1/users' => 'totalPages/page1.json'] : [],
        'https://example.com/api/v1/users?page=2' => 'totalPages/page2.json',
        'https://example.com/api/v1/users?page=3' => 'totalPages/page3.json',
    ]);
})->with('sources');
