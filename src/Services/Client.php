<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Services;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;

/**
 * The client singleton.
 */
final class Client
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
     * The custom options.
     *
     * @var array<string, mixed>
     */
    private static array $options = [];

    /**
     * The client middleware.
     *
     * @var array<string, callable>
     */
    private static array $middleware = [];

    /**
     * The Guzzle client instance.
     */
    private static ?Guzzle $guzzle = null;

    /**
     * Retrieve the Guzzle client instance.
     */
    public static function instance(): Guzzle
    {
        if (self::$guzzle) {
            return self::$guzzle;
        }

        $options = array_replace_recursive(self::$defaultOptions, self::$options);
        $options['handler'] ??= HandlerStack::create();

        foreach (self::$middleware as $name => $middleware) {
            $options['handler']->push($middleware, $name);
        }

        return self::$guzzle = new Guzzle($options);
    }

    /**
     * Set the Guzzle client options.
     */
    public static function configure(array $options): void
    {
        self::$options = array_replace_recursive(self::$options, $options);
    }

    /**
     * Set the Guzzle client middleware.
     */
    public static function middleware(string $name, callable $middleware): void
    {
        self::$middleware[$name] = $middleware;
    }

    /**
     * Clean up the static values.
     */
    public static function reset(): void
    {
        self::$guzzle = null;
        self::$options = [];
        self::$middleware = [];
    }

    /**
     * Instantiate the class.
     */
    private function __construct()
    {
        // disable the constructor
    }
}
