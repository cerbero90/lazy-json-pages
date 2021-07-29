<?php

namespace Cerbero\LazyJsonPages\Concerns;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use Traversable;

/**
 * The trait to handle total pages.
 *
 */
trait HandlesTotalPages
{
    use HandlesFailures;

    /**
     * Handle the JSON API map by leveraging the total number of pages
     *
     * @param int $pages
     * @param Uri $uri
     * @param bool $includeSource
     * @return Traversable
     */
    protected function handleByTotalPages(int $pages, Uri $uri, bool $includeSource = true): Traversable
    {
        $responses = $this->retry(function () use ($pages, $uri, $includeSource) {
            return $this->fetchPagesAsynchronously($pages, $uri, $includeSource);
        });

        if ($includeSource) {
            array_unshift($responses, $this->map->source);
        }

        return $this->yieldFromResponses($responses);
    }

    /**
     * Fetch pages by calling JSON APIs asynchronously
     *
     * @param int $pages
     * @param Uri $uri
     * @param bool $skipFirstPage
     * @return \Illuminate\Http\Client\Response[]
     */
    protected function fetchPagesAsynchronously(int $pages, Uri $uri, bool $skipFirstPage = true): array
    {
        $pages = $this->map->firstPage == 0 ? $pages - 1 : $pages;
        $initialPage = $skipFirstPage ? $this->map->firstPage + 1 : $this->map->firstPage;

        return Http::pool(function (Pool $pool) use ($pages, $uri, $initialPage) {
            $requests = [];

            for ($page = $initialPage; $page <= $pages; $page++) {
                $uri = Uri::withQueryValue($uri, $this->map->pageName, $page);
                [$headers, $method] = [$this->request->getHeaders(), $this->request->getMethod()];
                $requests[] = $pool->withHeaders($headers)->timeout($this->map->timeout)->send($method, $uri);
            }

            return $requests;
        });
    }

    /**
     * Yield items from the given responses
     *
     * @param \Illuminate\Http\Client\Response[] $responses
     * @return Traversable
     */
    protected function yieldFromResponses(iterable $responses): Traversable
    {
        foreach ($responses as $response) {
            if ($response->getBody()->tell() > 0) {
                yield from $response->json($this->map->path);
            } else {
                yield from LazyCollection::fromJson($response, $this->map->path);
            }

            $response->close();
        }
    }
}
