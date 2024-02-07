<?php

use Cerbero\LazyJsonPages\LazyJsonPages;

it('supports multiple sources', function (mixed $source, bool $requestsFirstPage) {
    $lazyCollection = LazyJsonPages::from($source)
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        ...$requestsFirstPage ? ['https://example.com/api/v1/users' => 'pagination/page1.json'] : [],
        'https://example.com/api/v1/users?page=2' => 'pagination/page2.json',
        'https://example.com/api/v1/users?page=3' => 'pagination/page3.json',
    ]);
})->with('sources');
