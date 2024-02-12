<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Services;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;

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
     * @var array<string, mixed>
     */
    private array $middleware = [];

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
     * @param \Psr\Http\Message\ResponseInterface[]|GuzzleHttp\Exception\RequestException[] $responses
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
     * Set the Guzzle client options.
     *
     * @param array<string, mixed> $options
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set the Guzzle client middleware.
     *
     * @param array<string, callable> $middleware
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;

        return $this;
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
