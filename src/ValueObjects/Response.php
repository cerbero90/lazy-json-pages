<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\ValueObjects;

use Cerbero\JsonParser\JsonParser;

/**
 * The HTTP response.
 */
final class Response
{
    /**
     * The headers of the HTTP response.
     *
     * @var array<string, string> $headers
     */
    public readonly array $headers;

    /**
     * Instantiate the class.
     *
     * @param array<string, string> $headers
     */
    public function __construct(public readonly string $json, array $headers)
    {
        $this->headers = $this->normalizeHeaders($headers);
    }

    /**
     * Normalize the given headers.
     *
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalizedHeaders = [];

        foreach ($headers as $name => $value) {
            $normalizedHeaders[strtolower($name)] = $value;
        }

        return $normalizedHeaders;
    }

    /**
     * Retrieve a value from the body or a header.
     */
    public function get(string $key): mixed
    {
        return $this->hasHeader($key) ? $this->header($key) : $this->json($key);
    }

    /**
     * Determine whether the given header is set.
     */
    public function hasHeader(string $header): bool
    {
        return isset($this->headers[strtolower($header)]);
    }

    /**
     * Retrieve the given header.
     */
    public function header(string $header): ?string
    {
        return $this->headers[strtolower($header)] ?? null;
    }

    /**
     * Retrieve a value from the body.
     */
    public function json(string $key): mixed
    {
        $array = JsonParser::parse($this->json)->pointer($key)->toArray();

        return empty($array) ? null : current($array);
    }
}
