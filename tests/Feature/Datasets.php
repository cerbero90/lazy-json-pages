<?php

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
    $psr7Response = new Psr7Response(body: file_get_contents(fixture('totalPages/page1.json')));
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
