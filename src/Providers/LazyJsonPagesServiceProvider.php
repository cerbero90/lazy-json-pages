<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Providers;

use Cerbero\LazyJsonPages\LazyJsonPages;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The service provider to integrate with Laravel.
 */
final class LazyJsonPagesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the services.
     */
    public function boot(): void
    {
        LazyJsonPages::globalMiddleware('laravel_events', Middleware::tap($this->sending(...), $this->sent(...)));
    }

    /**
     * Handle HTTP requests before they are sent.
     */
    private function sending(RequestInterface $request): void
    {
        event(new RequestSending(new Request($request)));
    }

    /**
     * Handle HTTP requests after they are sent.
     */
    private function sent(RequestInterface $request, array $options, PromiseInterface $promise): void
    {
        $clientRequest = new Request($request);

        $promise->then(
            fn(ResponseInterface $response) => event(new ResponseReceived($clientRequest, new Response($response))),
            fn() => event(new ConnectionFailed($clientRequest)),
        );
    }
}
