<?php

use Cerbero\LazyJsonPages\LazyJsonPages;
use GuzzleHttp\Middleware;

it('adds middleware for Guzzle', function () {
    $log = [];
    $count = 0;
    $before = function() use (&$log, &$count) { $log[] = 'req' . ++$count; };
    $after = function() use (&$log, &$count) { $log[] = 'res' . $count; };

    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->middleware('log', Middleware::tap($before, $after))
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?page=2' => 'pagination/page2.json',
        'https://example.com/api/v1/users?page=3' => 'pagination/page3.json',
    ]);

    expect($log)->toBe(['req1', 'res1', 'req2', 'res2', 'req3', 'res3']);
});
