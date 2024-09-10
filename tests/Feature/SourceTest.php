<?php

use Cerbero\LazyJsonPages\Exceptions\RequestNotSentException;
use Cerbero\LazyJsonPages\Exceptions\UnsupportedSourceException;
use Cerbero\LazyJsonPages\LazyJsonPages;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

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

it('fails if a source is not supported', function () {
    LazyJsonPages::from(123)
        ->totalPages('total_pages')
        ->collect('data.*')
        ->each(fn() => true);
})->throws(UnsupportedSourceException::class, 'The provided source is not supported.');

it('fails if a Laravel client response did not send the request', function () {
    $response = new Response(new Psr7Response(body: '{"cursor":"abc"}'));

    LazyJsonPages::from($response)
        ->cursor('cursor')
        ->collect('data.*')
        ->each(fn() => true);
})->throws(RequestNotSentException::class, 'The source did not send any HTTP request.');
