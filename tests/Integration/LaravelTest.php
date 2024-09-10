<?php

use Cerbero\LazyJsonPages\LazyJsonPages;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Event;

it('fires Laravel HTTP client events on success', function() {
    Event::fake();

    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?page=2' => 'pagination/page2.json',
        'https://example.com/api/v1/users?page=3' => 'pagination/page3.json',
    ]);

    Event::assertDispatched(RequestSending::class, 3);
    Event::assertDispatched(ResponseReceived::class, 3);
});

it('fires Laravel HTTP client events on failure', function() {
    Event::fake();

    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toFailRequest('https://example.com/api/v1/users');

    Event::assertDispatched(RequestSending::class, 1);
    Event::assertDispatched(ConnectionFailed::class, 1);
});
