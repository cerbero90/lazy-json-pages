<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Paginations;

use Cerbero\LazyJsonPages\Concerns\YieldsItemsByCursor;
use Cerbero\LazyJsonPages\Concerns\YieldsItemsByLength;
use Cerbero\LazyJsonPages\Exceptions\InvalidLinkHeaderException;
use Generator;
use Psr\Http\Message\ResponseInterface;
use Traversable;

/**
 * The pagination using a Link header.
 */
class LinkHeaderAwarePagination extends Pagination
{
    use YieldsItemsByCursor;
    use YieldsItemsByLength;

    /**
     * The Link header format.
     */
    public const FORMAT = '~<\s*(?<uri>[^\s>]+)\s*>.*?"\s*(?<rel>[^\s"]+)\s*"~';

    /**
     * Determine whether this pagination matches the configuration.
     */
    public function matches(): bool
    {
        return $this->config->hasLinkHeader
            && $this->config->totalItemsKey === null
            && $this->config->totalPagesKey === null
            && $this->config->lastPageKey === null;
    }

    /**
     * Yield the paginated items.
     *
     * @return Traversable<int, mixed>
     * @throws InvalidLinkHeaderException
     */
    public function getIterator(): Traversable
    {
        $links = $this->parseLinkHeader($this->source->response()->getHeaderLine('link'));

        yield from match (true) {
            isset($links['last']) => $this->yieldItemsByLastPage($links['last']),
            isset($links['next']) => $this->yieldItemsByNextLink(),
            default => $this->yieldItemsFrom($this->source->pullResponse()),
        };
    }

    /**
     * Retrieve the parsed Link header.
     *
     * @template TParsed of array{last?: int, next?: string|int}
     * @template TRelation of string|null
     *
     * @param TRelation $relation
     * @return (TRelation is null ? TParsed : string|int|null)
     */
    protected function parseLinkHeader(string $linkHeader, ?string $relation = null): array|string|int|null
    {
        $links = [];

        preg_match_all(static::FORMAT, $linkHeader, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $links[$match['rel']] = $this->toPage($match['uri'], $match['rel'] != 'next');
        }

        return $relation ? ($links[$relation] ?? null) : $links;
    }

    /**
     * Yield the paginated items by the given last page.
     *
     * @return Generator<int, mixed>
     */
    protected function yieldItemsByLastPage(int $lastPage): Generator
    {
        yield from $this->yieldItemsUntilPage(function (ResponseInterface $response) use ($lastPage) {
            yield from $this->yieldItemsFrom($response);

            return $this->config->firstPage === 0 ? $lastPage + 1 : $lastPage;
        });
    }

    /**
     * Yield the paginated items by the given next link.
     *
     * @return Generator<int, mixed>
     */
    protected function yieldItemsByNextLink(): Generator
    {
        yield from $this->yieldItemsByCursor(function (ResponseInterface $response) {
            yield from $this->yieldItemsFrom($response);

            return $this->parseLinkHeader($response->getHeaderLine('link'), 'next');
        });
    }
}
