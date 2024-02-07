<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Services\Client;
use Traversable;

/**
 * The pagination aware of the cursor of the next page.
 */
class CursorPagination extends Pagination
{
    /**
     * Determine whether the configuration matches this pagination.
     */
    public function matches(): bool
    {
        return $this->config->cursorKey !== null
            && $this->config->totalItemsKey === null
            && $this->config->totalPagesKey === null
            && $this->config->lastPageKey === null;
    }

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        yield from $generator = $this->yieldItemsAndReturnKey($this->source->response(), $this->config->cursorKey);

        $request = clone $this->source->request();

        while ($cursor = $this->toPage($generator->getReturn(), onlyNumerics: false)) {
            $uri = $this->uriForPage($request->getUri(), (string) $cursor);
            $response = Client::instance()->send($request->withUri($uri));

            yield from $generator = $this->yieldItemsAndReturnKey($response, $this->config->cursorKey);
        }
    }
}
