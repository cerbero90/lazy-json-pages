<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Concerns;

use Closure;
use Generator;
use Psr\Http\Message\ResponseInterface;

/**
 * The trait to yield items from cursor-aware paginations.
 */
trait YieldsItemsByCursor
{
    /**
     * Yield the paginated items by following the cursor of each page.
     *
     * @param (Closure(ResponseInterface): Generator<int, mixed>) $callback
     * @return Generator<int, mixed>
     */
    protected function yieldItemsByCursor(Closure $callback): Generator
    {
        yield from $generator = $callback($this->source->pullResponse());

        $request = clone $this->source->request();

        while ($cursor = $this->toPage($generator->getReturn(), onlyNumerics: false)) {
            $uri = $this->uriForPage($request->getUri(), (string) $cursor);
            $response = $this->client->send($request->withUri($uri));

            yield from $generator = $callback($response);
        }
    }
}
