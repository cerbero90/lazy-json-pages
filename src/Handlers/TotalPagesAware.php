<?php

namespace Cerbero\LazyJsonPages\Handlers;

use Cerbero\LazyJsonPages\Map;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use Traversable;

/**
 * The total pages aware handler.
 *
 */
class TotalPagesAware implements Handler
{
    /**
     * Determine whether the handler can handle the given map
     *
     * @param Map $map
     * @return bool
     */
    public function handles(Map $map): bool
    {
        return $map->pages > 0 && $map->perPage === null;
    }

    /**
     * Handle the given map
     *
     * @param Map $map
     * @return Traversable
     */
    public function handle(Map $map): Traversable
    {
        return (function () use ($map) {
            /** @var \GuzzleHttp\Psr7\Request $request */
            $request = $map->source->transferStats->getRequest();
            $responses = Http::pool(function (Pool $pool) use ($map, $request) {
                $requests = [];

                for ($page = 2; $page <= $map->pages; $page++) {
                    $uri = Uri::withQueryValue($request->getUri(), $map->pageName, $page);
                    $requests[] = $pool->withHeaders($request->getHeaders())->send($request->getMethod(), $uri);
                }

                return $requests;
            });

            if ($map->source->getBody()->tell() > 0) {
                yield from $map->source->json($map->path);
            } else {
                yield from LazyCollection::fromJson($map->source, $map->path);
            }

            foreach ($responses as $response) {
                yield from LazyCollection::fromJson($response, $map->path);
            }
        })();
    }
}
