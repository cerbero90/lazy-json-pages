<?php

namespace Cerbero\LazyJsonPages\Services;

use Generator;
use Psr\Http\Message\ResponseInterface;

/**
 * The collector of pages.
 */
final class Book
{
    /**
     * The HTTP responses of the fetched pages.
     *
     * @var array<int, ResponseInterface>
     */
    private array $pages = [];

    /**
     * The pages unable to be fetched.
     *
     * @var int[]
     */
    private array $failedPages = [];

    /**
     * Add the HTTP response of the given page.
     */
    public function addPage(int $page, ResponseInterface $response): self
    {
        $this->pages[$page] = $response;

        return $this;
    }

    /**
     * Yield and forget each page.
     *
     * @return Generator<int, ResponseInterface>
     */
    public function pullPages(): Generator
    {
        ksort($this->pages);

        foreach ($this->pages as $page => $response) {
            yield $response;

            unset($this->pages[$page]);
        }
    }

    /**
     * Add the given failed page.
     */
    public function addFailedPage(int $page): self
    {
        $this->failedPages[] = $page;

        return $this;
    }

    /**
     * Retrieve and unset the failed pages.
     *
     * @return array<int, int>
     */
    public function pullFailedPages(): array
    {
        $failedPages = $this->failedPages;

        $this->failedPages = [];

        return $failedPages;
    }

    /**
     * Retrieve and unset the oldest failed page.
     */
    public function pullFailedPage(): ?int
    {
        return array_shift($this->failedPages);
    }
}
