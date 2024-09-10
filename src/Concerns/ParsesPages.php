<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\JsonParser\JsonParser;
use Cerbero\LazyJson\Pointers\DotsConverter;
use Generator;
use Psr\Http\Message\ResponseInterface;

/**
 * The trait to parse pages.
 */
trait ParsesPages
{
    /**
     * The number of items per page.
     */
    protected int $itemsPerPage;

    /**
     * Yield paginated items and the given key from the provided response.
     *
     * @return Generator<int, mixed>
     */
    protected function yieldItemsAndGetKey(ResponseInterface $response, string $key): Generator
    {
        $itemsPerPage = 0;
        $pointers = [$this->config->itemsPointer];

        if (($value = $response->getHeaderLine($key)) === '') {
            $pointers[DotsConverter::toPointer($key)] = fn(mixed $value) => (object) compact('value');
        }

        foreach (JsonParser::parse($response)->pointers($pointers) as $item) {
            if (is_object($item)) {
                /** @var object{value: mixed} $item */
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
        /** @phpstan-ignore-next-line */
        yield from JsonParser::parse($source)->pointer($this->config->itemsPointer);
    }
}
