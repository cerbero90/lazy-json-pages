<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Concerns\YieldsItemsByCursor;
use Psr\Http\Message\ResponseInterface;
use Traversable;

/**
 * The pagination aware of the cursor of the next page.
 */
class CursorAwarePagination extends Pagination
{
    use YieldsItemsByCursor;

    /**
     * Determine whether this pagination matches the configuration.
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
        yield from $this->yieldItemsByCursor(function (ResponseInterface $response) {
            yield from $generator = $this->yieldItemsAndGetKey($response, $this->config->cursorKey);

            return $generator->getReturn();
        });
    }
}
