<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJson\Pointers\DotsConverter;
use Cerbero\LazyJsonPages\Data\Config;
use Cerbero\LazyJsonPages\Paginations\AnyPagination;
use Cerbero\LazyJsonPages\Services\ClientFactory;
use Cerbero\LazyJsonPages\Sources\AnySource;
use Closure;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Throwable;

/**
 * The Lazy JSON Pages entry-point
 */
final class LazyJsonPages
{
    /**
     * The HTTP client factory.
     */
    private readonly ClientFactory $client;

    /**
     * The raw configuration of the API pagination.
     *
     * @var array<Config::OPTION_*, mixed>
     */
    private array $config = [];

    /**
     * Add a global middleware.
     */
    public static function globalMiddleware(string $name, callable $middleware): void
    {
        ClientFactory::globalMiddleware($name, $middleware);
    }

    /**
     * Instantiate the class statically.
     */
    public static function from(mixed $source): self
    {
        return new self($source);
    }

    /**
     * Instantiate the class.
     */
    public function __construct(private readonly mixed $source)
    {
        $this->client = new ClientFactory();
    }

    /**
     * Set the name of the page.
     */
    public function pageName(string $name): self
    {
        $this->config[Config::OPTION_PAGE_NAME] = $name;

        return $this;
    }

    /**
     * Set the pattern to capture the page in the URI path.
     */
    public function pageInPath(string $pattern = '/(\d+)(?!.*\d)/'): self
    {
        $this->config[Config::OPTION_PAGE_IN_PATH] = $pattern;

        return $this;
    }

    /**
     * Set the number of the first page.
     */
    public function firstPage(int $page): self
    {
        $this->config[Config::OPTION_FIRST_PAGE] = max(0, $page);

        return $this;
    }

    /**
     * Set the total number of pages.
     */
    public function totalPages(string $key): self
    {
        $this->config[Config::OPTION_TOTAL_PAGES_KEY] = $key;

        return $this;
    }

    /**
     * Set the total number of items.
     */
    public function totalItems(string $key): self
    {
        $this->config[Config::OPTION_TOTAL_ITEMS_KEY] = $key;

        return $this;
    }

    /**
     * Set the cursor or next page.
     */
    public function cursor(string $key): self
    {
        $this->config[Config::OPTION_CURSOR_KEY] = $key;

        return $this;
    }

    /**
     * Set the number of the last page.
     */
    public function lastPage(string $key): self
    {
        $this->config[Config::OPTION_LAST_PAGE_KEY] = $key;

        return $this;
    }

    /**
     * Set the offset.
     */
    public function offset(string $key = 'offset'): self
    {
        $this->config[Config::OPTION_OFFSET_KEY] = $key;

        return $this;
    }

    /**
     * Set the Link header pagination.
     */
    public function linkHeader(): self
    {
        $this->config[Config::OPTION_HAS_LINK_HEADER] = true;

        return $this;
    }

    /**
     * Set the custom pagination.
     */
    public function pagination(string $class): self
    {
        $this->config[Config::OPTION_PAGINATION] = $class;

        return $this;
    }

    /**
     * Throttle the requests to respect rate limiting.
     */
    public function throttle(int $requests, int $perSeconds = 0, int $perMinutes = 0, int $perHours = 0): self
    {
        $seconds = $perSeconds + $perMinutes * 60 + $perHours * 3600;

        if ($requests > 0 && $seconds > 0) {
            $this->config[Config::OPTION_RATE_LIMITS] ??= $this->client->rateLimits;

            $this->client->throttle($requests, $seconds);
        }

        return $this;
    }

    /**
     * Set the maximum number of concurrent async HTTP requests.
     */
    public function async(int $requests): self
    {
        $this->config[Config::OPTION_ASYNC] = max(1, $requests);

        $this->client->config(RequestOptions::STREAM, $this->config[Config::OPTION_ASYNC] === 1);

        return $this;
    }

    /**
     * Set the server connection timeout in seconds.
     */
    public function connectionTimeout(float|int $seconds): self
    {
        $this->client->config(RequestOptions::CONNECT_TIMEOUT, max(0, $seconds));

        $this->client->config(RequestOptions::READ_TIMEOUT, max(0, $seconds));

        return $this;
    }

    /**
     * Set an HTTP request timeout in seconds.
     */
    public function requestTimeout(float|int $seconds): self
    {
        $this->client->config(RequestOptions::TIMEOUT, max(0, $seconds));

        return $this;
    }

    /**
     * Set the number of attempts to fetch pages.
     */
    public function attempts(int $times): self
    {
        $this->config[Config::OPTION_ATTEMPTS] = max(1, $times);

        return $this;
    }

    /**
     * Set the backoff strategy.
     */
    public function backoff(Closure $callback): self
    {
        $this->config[Config::OPTION_BACKOFF] = $callback;

        return $this;
    }

    /**
     * Add an HTTP client middleware.
     */
    public function middleware(string $name, callable $middleware): self
    {
        $this->client->middleware($name, $middleware);

        return $this;
    }

    /**
     * Handle the sending request.
     *
     * @param Closure(Request $request, array<string, mixed> $config): void $callback
     */
    public function onRequest(Closure $callback): self
    {
        $this->client->onRequest($callback);

        return $this;
    }

    /**
     * Handle the received response.
     *
     * @param Closure(Response $response, Request $request, array<string, mixed> $config): void $callback
     */
    public function onResponse(Closure $callback): self
    {
        $this->client->onResponse($callback);

        return $this;
    }

    /**
     * Handle a transaction error.
     *
     * @param Closure(Throwable $e, Request $request, ?Response $response, array<string, mixed> $config): void $callback
     */
    public function onError(Closure $callback): self
    {
        $this->client->onError($callback);

        return $this;
    }

    /**
     * Retrieve a lazy collection yielding the paginated items.
     *
     * @return LazyCollection<int, mixed>
     * @throws \Cerbero\LazyJsonPages\Exceptions\UnsupportedPaginationException
     */
    public function collect(string $dot = '*'): LazyCollection
    {
        $this->config[Config::OPTION_ITEMS_POINTER] = DotsConverter::toPointer($dot);

        return new LazyCollection(function () {
            $client = $this->client->make();
            $config = new Config(...$this->config); /** @phpstan-ignore-line */
            $source = (new AnySource($this->source))->setClient($client);

            yield from new AnyPagination($source, $client, $config);
        });
    }
}
