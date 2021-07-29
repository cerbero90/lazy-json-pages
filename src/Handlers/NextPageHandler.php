<?php

namespace Cerbero\LazyJsonPages\Handlers;

use Cerbero\LazyJsonPages\Concerns\HandlesFailures;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Http;
use Traversable;

/**
 * The next page handler.
 *
 */
class NextPageHandler extends AbstractHandler
{
    use HandlesFailures;

    /**
     * Determine whether the handler can handle the JSON API map
     *
     * @return bool
     */
    public function matches(): bool
    {
        return !!$this->map->nextPageKey;
    }

    /**
     * Handle the JSON API map
     *
     * @return Traversable
     */
    public function handle(): Traversable
    {
        yield from $this->map->source->json($this->map->path);

        while ($this->map->nextPage) {
            [$headers, $method] = [$this->request->getHeaders(), $this->request->getMethod()];
            $uri = Uri::withQueryValue($this->request->getUri(), $this->map->pageName, $this->map->nextPage);

            $this->map->source = $this->retry(function () use ($headers, $method, $uri) {
                return Http::withHeaders($headers)->timeout($this->map->timeout)->send($method, $uri);
            });

            $this->map->nextPage($this->map->nextPageKey);

            yield from $this->handle();
        }
    }
}
