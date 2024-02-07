<?php

use Cerbero\LazyJsonPages\LazyJsonPages;
use Cerbero\LazyJsonPages\Paginations\TotalPagesAwarePagination;
use Cerbero\LazyJsonPages\Sources\CustomSourceSample;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\Request as LaravelClientRequest;
use Illuminate\Http\Client\Response as LaravelClientResponse;
use Illuminate\Http\Request as LaravelRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

dataset('sources', function () {
    $uri = 'https://example.com/api/v1/users';
    $psr7Request = new Psr7Request('GET', $uri);
    $psr7Response = new Psr7Response(body: file_get_contents(fixture('pagination/page1.json')));
    $laravelClientResponse = new LaravelClientResponse($psr7Response);
    $laravelClientResponse->transferStats = new TransferStats($psr7Request, $psr7Response);

    yield 'user-defined source' => [new CustomSourceSample(null), false];
    yield 'endpoint' => [$uri, true];
    yield 'Laravel client request' => [new LaravelClientRequest($psr7Request), true];
    yield 'Laravel client response' => [$laravelClientResponse, false];
    yield 'Laravel request' => [LaravelRequest::create($uri), true];
    yield 'PSR-7 request' => [$psr7Request, true];
    yield 'Symfony request' => [SymfonyRequest::create($uri), true];
});

dataset('length-aware', function () {
    yield 'total pages aware' => fn(LazyJsonPages $instance) => $instance->totalPages('meta.total_pages');
    yield 'total items aware' => fn(LazyJsonPages $instance) => $instance->totalItems('meta.total_items');
    yield 'last page aware' => fn(LazyJsonPages $instance) => $instance->lastPage('meta.last_page');
    yield 'custom pagination' => fn(LazyJsonPages $instance) => $instance->pagination(TotalPagesAwarePagination::class)->totalPages('meta.total_pages');
    yield 'total pages aware by header' => fn(LazyJsonPages $instance) => $instance->totalPages('X-Total-Pages');
    yield 'total items aware by header' => fn(LazyJsonPages $instance) => $instance->totalItems('X-Total-Items');
    yield 'last page aware by header' => fn(LazyJsonPages $instance) => $instance->lastPage('X-Last-Page');
    yield 'custom pagination by header' => fn(LazyJsonPages $instance) => $instance->pagination(TotalPagesAwarePagination::class)->totalPages('X-Total-Pages');
});
