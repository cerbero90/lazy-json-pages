<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Services;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The HTTP client factory.
 */
final class ClientFactory
{
    /**
     * The default options.
     *
     * @var array<string, mixed>
     */
    private static array $defaultOptions = [
        RequestOptions::CONNECT_TIMEOUT => 5,
        RequestOptions::READ_TIMEOUT => 5,
        RequestOptions::TIMEOUT => 5,
        RequestOptions::STREAM => true,
        RequestOptions::HEADERS => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
    ];

    /**
     * The global middleware.
     *
     * @var array<string, callable>
     */
    private static array $globalMiddleware = [];

    /**
     * The custom options.
     *
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * The local middleware.
     *
     * @var array<string, callable>
     */
    private array $middleware = [];

    /**
     * The callbacks to handle the sending request.
     *
     * @var Closure[]
     */
    private array $onRequestCallbacks = [];

    /**
     * The callbacks to handle the received response.
     *
     * @var Closure[]
     */
    private array $onResponseCallbacks = [];

    /**
     * The callbacks to handle a transaction error.
     *
     * @var Closure[]
     */
    private array $onErrorCallbacks = [];

    /**
     * Add a global middleware.
     */
    public static function globalMiddleware(string $name, callable $middleware): void
    {
        self::$globalMiddleware[$name] = $middleware;
    }

    /**
     * Fake HTTP requests for testing purposes.
     *
     * @param ResponseInterface[]|RequestException[] $responses
     * @return array<int, array<string, mixed>>
     */
    public static function fake(array $responses, Closure $callback): array
    {
        $transactions = [];

        $handler = HandlerStack::create(new MockHandler($responses));

        $handler->push(Middleware::history($transactions));

        self::$defaultOptions['handler'] = $handler;

        $callback();

        unset(self::$defaultOptions['handler']);

        return $transactions;
    }

    /**
     * Add the given Guzzle client option.
     */
    public function option(string $name, mixed $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Add the given Guzzle client middleware.
     */
    public function middleware(string $name, callable $middleware): self
    {
        $this->middleware[$name] = $middleware;

        return $this;
    }

    /**
     * Add the given callback to handle the sending request.
     */
    public function onRequest(Closure $callback): self
    {
        $this->onRequestCallbacks[] = $callback;

        return $this->tap();
    }

    /**
     * Add the middleware to handle a request before and after it is sent.
     */
    private function tap(): self
    {
        $this->middleware['lazy_json_pages_tap'] ??= function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler) {
                foreach ($this->onRequestCallbacks as $callback) {
                    $callback($request);
                }

                return $handler($request, $options)->then(function(ResponseInterface $response) use ($request) {
                    foreach ($this->onResponseCallbacks as $callback) {
                        $callback($response, $request);
                    }

                    return $response;
                }, function(mixed $reason) use ($request) {
                    $exception = Create::exceptionFor($reason);
                    $response = $reason instanceof RequestException ? $reason->getResponse() : null;

                    foreach ($this->onErrorCallbacks as $callback) {
                        $callback($exception, $request, $response);
                    }

                    return Create::rejectionFor($reason);
                });
            };
        };

        return $this;
    }

    /**
     * Add the given callback to handle the received response.
     */
    public function onResponse(Closure $callback): self
    {
        $this->onResponseCallbacks[] = $callback;

        return $this->tap();
    }

    /**
     * Add the given callback to handle a transaction error.
     */
    public function onError(Closure $callback): self
    {
        $this->onErrorCallbacks[] = $callback;

        return $this->tap();
    }

    /**
     * Retrieve a configured Guzzle client instance.
     */
    public function make(): Client
    {
        $options = array_replace_recursive(self::$defaultOptions, $this->options);
        $options['handler'] ??= HandlerStack::create();

        foreach ([...self::$globalMiddleware, ...$this->middleware] as $name => $middleware) {
            $options['handler']->push($middleware, $name);
        }

        return new Client($options);
    }
}
