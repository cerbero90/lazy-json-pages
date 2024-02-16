<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Services;

use Cerbero\LazyJsonPages\Middleware\Tap;
use Cerbero\LazyJsonPages\Services\TapCallbacks;
use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * The HTTP client factory.
 */
final class ClientFactory
{
    /**
     * The default client configuration.
     *
     * @var array<string, mixed>
     */
    private static array $defaultConfig = [
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
     * The tap middleware callbacks.
     */
    private readonly TapCallbacks $tapCallbacks;

    /**
     * The custom client configuration.
     *
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * The local middleware.
     *
     * @var array<string, callable>
     */
    private array $middleware = [];

    /**
     * The requests throttling.
     *
     * @var array<int, int>
     */
    private array $throttling = [];

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

        self::$defaultConfig['handler'] = $handler;

        $callback();

        unset(self::$defaultConfig['handler']);

        return $transactions;
    }

    /**
     * Instantiate the class.
     */
    public function __construct()
    {
        $this->tapCallbacks = new TapCallbacks();
    }

    /**
     * Add the given option to the Guzzle client configuration.
     */
    public function config(string $name, mixed $value): self
    {
        $this->config[$name] = $value;

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
        $this->tapCallbacks->onRequest($callback);

        return $this->tap();
    }

    /**
     * Add the middleware to handle a request before and after it is sent.
     */
    private function tap(): self
    {
        $this->middleware['lazy_json_pages_tap'] ??= new Tap($this->tapCallbacks);

        return $this;
    }

    /**
     * Add the given callback to handle the received response.
     */
    public function onResponse(Closure $callback): self
    {
        $this->tapCallbacks->onResponse($callback);

        return $this->tap();
    }

    /**
     * Add the given callback to handle a transaction error.
     */
    public function onError(Closure $callback): self
    {
        $this->tapCallbacks->onError($callback);

        return $this->tap();
    }

    /**
     * Throttle the requests to respect rate limiting.
     */
    public function throttle(int $requests, int $seconds): self
    {
        $this->throttling[$seconds] = $requests;

        // $this->middleware['lazy_json_pages_throttle'] ??= Tap::once();

        return $this;
    }

    /**
     * Retrieve a configured Guzzle client instance.
     */
    public function make(): Client
    {
        $config = array_replace_recursive(self::$defaultConfig, $this->config);
        $config['handler'] ??= HandlerStack::create();

        foreach ([...self::$globalMiddleware, ...$this->middleware] as $name => $middleware) {
            $config['handler']->push($middleware, $name);
        }

        return new Client($config);
    }
}
