<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Services;

use GuzzleHttp\Client as Guzzle;
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
     */
    private static array $options = [];

    /**
     * The Guzzle client instance.
     */
    private static ?Guzzle $guzzle = null;

    /**
     * Retrieve the Guzzle client instance.
     */
    public static function instance(): Guzzle
    {
        return self::$guzzle ??= new Guzzle(
            array_replace_recursive(self::$defaultOptions, self::$options),
        );
    }

    /**
     * Set the Guzzle client options.
     */
    public static function configure(array $options): void
    {
        self::$options = array_replace_recursive(self::$options, $options);
    }

    /**
     * Clean up the static values.
     */
    public static function reset(): void
    {
        self::$guzzle = null;
        self::$options = [];
    }

    /**
     * Instantiate the class.
     */
    private function __construct()
    {
        // disable the constructor
    }
}
