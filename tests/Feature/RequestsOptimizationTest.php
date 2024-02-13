<?php

use Cerbero\LazyJsonPages\LazyJsonPages;
use GuzzleHttp\Middleware;

it('adds middleware for Guzzle', function () {
    $log = collect();

    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->middleware('log', Middleware::tap(fn() => $log->push('before'), fn() => $log->push('after')))
        ->onRequest(fn() => $log->push('onRequest'))
        ->onResponse(fn() => $log->push('onResponse'))
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?page=2' => 'pagination/page2.json',
        'https://example.com/api/v1/users?page=3' => 'pagination/page3.json',
    ]);

    expect($log)->sequence(...[
        'before',
        'onRequest',
        'after',
        'onResponse',
        'before',
        'onRequest',
        'after',
        'onResponse',
        'before',
        'onRequest',
        'after',
        'onResponse',
    ]);
});

it('handles transaction errors', function () {
    $log = collect();

    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->middleware('log', Middleware::tap(fn() => $log->push('before'), fn() => $log->push('after')))
        ->onError(fn() => $log->push('onError'))
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toFailRequest('https://example.com/api/v1/users');

    expect($log)->sequence('before', 'after', 'onError');
});

it('sends HTTP requests asynchronously', function () {
    $log = collect();

    $lazyCollection = LazyJsonPages::from('https://example.com/api/v1/users')
        ->onRequest(fn() => $log->push('sending'))
        ->onResponse(fn() => $log->push('sent'))
        ->async(3)
        ->totalPages('meta.total_pages')
        ->collect('data.*');

    expect($lazyCollection)->toLoadItemsViaRequests([
        'https://example.com/api/v1/users' => 'pagination/page1.json',
        'https://example.com/api/v1/users?page=2' => 'pagination/page2.json',
        'https://example.com/api/v1/users?page=3' => 'pagination/page3.json',
    ]);

    expect($log)->sequence('sending', 'sent', 'sending', 'sending', 'sent', 'sent');
});
