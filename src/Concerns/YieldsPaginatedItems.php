<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\JsonParser\JsonParser;
use Cerbero\LazyJson\Pointers\DotsConverter;
use Generator;
use Psr\Http\Message\ResponseInterface;

/**
 * The trait to yield paginated items.
 */
trait YieldsPaginatedItems
{
    /**
     * Yield paginated items and the given key from the provided response.
     *
     * @return Generator<int, mixed>
     */
    protected function yieldItemsAndReturnKey(ResponseInterface $response, string $key): Generator
    {
        $itemsPerPage = 0;
        $pointers = [$this->config->pointer];

        if (($value = $response->getHeaderLine($key)) === '') {
            $pointers[DotsConverter::toPointer($key)] = fn(mixed $value) => (object) compact('value');
        }

        foreach (JsonParser::parse($response)->pointers($pointers) as $item) {
            if (is_object($item)) {
                $value = $item->value;
            } else {
                yield $item;
                ++$itemsPerPage;
            }
        }

        $this->itemsPerPage ??= $itemsPerPage;

        return $value;
    }

    /**
     * Yield paginated items from the given source.
     *
     * @return Generator<int, mixed>
     */
    protected function yieldItemsFrom(mixed $source): Generator
    {
        yield from JsonParser::parse($source)->pointer($this->config->pointer);
    }
}
