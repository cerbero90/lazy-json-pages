<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Middleware;

use Cerbero\LazyJsonPages\Services\TapCallbacks;
use Closure;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The middleware to handle an HTTP request before and after it is sent.
 */
final class Tap
{
    /**
     * The HTTP request.
     */
    private RequestInterface $request;

    /**
     * The Guzzle client configuration.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Instantiate the class statically to tap once.
     */
    public static function once(?Closure $onRequest = null, ?Closure $onResponse = null, ?Closure $onError = null): self
    {
        $callbacks = new TapCallbacks();
        $onRequest && $callbacks->onRequest($onRequest);
        $onResponse && $callbacks->onResponse($onResponse);
        $onError && $callbacks->onError($onError);

        return new self($callbacks);
    }

    /**
     * Instantiate the class.
     */
    public function __construct(private readonly TapCallbacks $callbacks) {}

    /**
     * Handle an HTTP request before and after it is sent.
     *
     * @param callable(RequestInterface, array): PromiseInterface $handler
     */
    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $config) use ($handler) {
            $this->request = $request;
            $this->config = $config;

            foreach ($this->callbacks->onRequestCallbacks() as $callback) {
                $callback($request, $config);
            }

            return $handler($request, $config)
                ->then($this->handleResponse(...))
                ->otherwise($this->handleError(...));
        };
    }

    /**
     * Handle the given response.
     */
    private function handleResponse(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->callbacks->onResponseCallbacks() as $callback) {
            $callback($response, $this->request, $this->config);
        }

        return $response;
    }

    /**
     * Handle the given transaction error.
     */
    private function handleError(mixed $reason): PromiseInterface
    {
        $exception = Create::exceptionFor($reason);
        $response = $reason instanceof RequestException ? $reason->getResponse() : null;

        foreach ($this->callbacks->onErrorCallbacks() as $callback) {
            $callback($exception, $this->request, $response, $this->config);
        }

        return Create::rejectionFor($reason);
    }
}
