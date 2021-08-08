<?php

namespace Cerbero\LazyJsonPages\Handlers;

use Cerbero\LazyJsonPages\Outcome;
use Cerbero\LazyJsonPages\SourceWrapper;
use GuzzleHttp\Psr7\Uri;
use Throwable;
use Traversable;

/**
 * The next page handler.
 *
 */
class NextPageHandler extends AbstractHandler
{
    /**
     * Determine whether the handler can handle the APIs configuration
     *
     * @return bool
     */
    public function matches(): bool
    {
        return !!$this->config->nextPageKey;
    }

    /**
     * Handle the APIs configuration
     *
     * @return Traversable
     */
    public function handle(): Traversable
    {
        yield from $this->retryYielding(function (Outcome $outcome) {
            try {
                yield from $this->handleByNextPage();
            } catch (Throwable $e) {
                $outcome->pullFailedPages();
                $outcome->addFailedPage($this->config->nextPage);
                throw $e;
            }
        });
    }

    /**
     * Handle APIs with next page
     *
     * @return Traversable
     */
    protected function handleByNextPage(): Traversable
    {
        $request = clone $this->config->source->request;

        yield from $this->config->source->json($this->config->path);

        while ($this->config->nextPage) {
            $uri = Uri::withQueryValue($request->getUri(), $this->config->pageName, $this->config->nextPage);
            $this->config->source = new SourceWrapper($request->withUri($uri));
            $this->config->nextPage($this->config->nextPageKey);
            yield from $this->handleByNextPage();
        }
    }
}
