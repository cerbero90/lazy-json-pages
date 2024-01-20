<?php

namespace Cerbero\LazyJsonPages\Concerns;

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
            !($query = parse_url($value, PHP_URL_QUERY)) => $onlyNumerics ? null : $value,
            default => $this->pageFromQuery($query, $onlyNumerics),
        };
    }

    /**
     * Retrieve the page from the given query.
     *
     * @return ($onlyNumerics is true ? int|null : string|int|null)
     */
    protected function pageFromQuery(string $query, bool $onlyNumerics = true): string|int|null
    {
        parse_str($query, $parameters);

        return $this->toPage($parameters[$this->config->pageName] ?? null, $onlyNumerics);
    }
}
