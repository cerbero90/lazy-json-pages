<?php

namespace Cerbero\LazyJsonPages\Concerns;

use Cerbero\LazyJsonPages\Exceptions\InvalidPageInPathException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * The trait to resolve pages.
 */
trait ResolvesPages
{
    /**
     * Retrieve the page out of the given value.
     *
     * @return ($onlyNumerics is true ? int|null : string|int|null)
     */
    protected function toPage(mixed $value, bool $onlyNumerics = true): string|int|null
    {
        return match (true) {
            is_numeric($value) => (int) $value,
            !is_string($value) || $value === '' => null,
            !$parsedUri = parse_url($value) => $onlyNumerics ? null : $value,
            default => $this->pageFromParsedUri($parsedUri, $onlyNumerics),
        };
    }

    /**
     * Retrieve the page from the given parsed URI.
     *
     * @return ($onlyNumerics is true ? int|null : string|int|null)
     */
    protected function pageFromParsedUri(array $parsedUri, bool $onlyNumerics = true): string|int|null
    {
        if ($pattern = $this->config->pageInPath) {
            preg_match($pattern, $parsedUri['path'] ?? '', $matches);

            return $this->toPage($matches[1] ?? null, $onlyNumerics);
        }

        parse_str($parsedUri['query'] ?? '', $parameters);

        return $this->toPage($parameters[$this->config->pageName] ?? null, $onlyNumerics);
    }

    /**
     * Retrieve the URI for the given page.
     */
    protected function uriForPage(UriInterface $uri, string $page): UriInterface
    {
        if (!$pattern = $this->config->pageInPath) {
            return Uri::withQueryValue($uri, $this->config->pageName, $page);
        }

        if (!preg_match($pattern, $path = $uri->getPath(), $matches, PREG_OFFSET_CAPTURE)) {
            throw new InvalidPageInPathException($path, $pattern);
        }

        return $uri->withPath(substr_replace($path, $page, $matches[1][1], strlen($matches[1][0])));
    }
}
