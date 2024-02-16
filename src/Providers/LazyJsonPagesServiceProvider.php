<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Providers;

use Cerbero\LazyJsonPages\LazyJsonPages;
use Cerbero\LazyJsonPages\Middleware\Tap;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

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
        $fireEvents = Tap::once($this->onRequest(...), $this->onResponse(...), $this->onError(...));

        LazyJsonPages::globalMiddleware('laravel_events', $fireEvents);
    }

    /**
     * Handle HTTP requests before they are sent.
     */
    private function onRequest(RequestInterface $request): void
    {
        $this->app['events']->dispatch(new RequestSending(new Request($request)));
    }

    /**
     * Handle HTTP responses after they are received.
     */
    private function onResponse(ResponseInterface $response, RequestInterface $request): void
    {
        $this->app['events']->dispatch(new ResponseReceived(new Request($request), new Response($response)));
    }

    /**
     * Handle a transaction error.
     */
    private function onError(Throwable $e, RequestInterface $request): void
    {
        $this->app['events']->dispatch(new ConnectionFailed(new Request($request)));
    }
}
