<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Services;

use Closure;

/**
 * The collection of callbacks to run by the tap middleware.
 */
final class TapCallbacks
{
    /**
     * The callbacks to handle the sending request.
     *
     * @var Closure[]
     */
    private array $onRequestCallbacks = [];

    /**
     * The callbacks to handle the received response.
     *
     * @var Closure[]
     */
    private array $onResponseCallbacks = [];

    /**
     * The callbacks to handle a transaction error.
     *
     * @var Closure[]
     */
    private array $onErrorCallbacks = [];

    /**
     * Add the given callback to handle the sending request.
     *
     * @param Closure $callback
     */
    public function onRequest(Closure $callback): self
    {
        $this->onRequestCallbacks[] = $callback;

        return $this;
    }

    /**
     * Add the given callback to handle the received response.
     */
    public function onResponse(Closure $callback): self
    {
        $this->onResponseCallbacks[] = $callback;

        return $this;
    }

    /**
     * Add the given callback to handle a transaction error.
     */
    public function onError(Closure $callback): self
    {
        $this->onErrorCallbacks[] = $callback;

        return $this;
    }

    /**
     * Retrieve the callbacks to handle the sending request.
     *
     * @return Closure[]
     */
    public function onRequestCallbacks(): array
    {
        return $this->onRequestCallbacks;
    }

    /**
     * Retrieve the callbacks to handle the received response.
     *
     * @return Closure[]
     */
    public function onResponseCallbacks(): array
    {
        return $this->onResponseCallbacks;
    }

    /**
     * Retrieve the callbacks to handle a transaction error.
     *
     * @return Closure[]
     */
    public function onErrorCallbacks(): array
    {
        return $this->onErrorCallbacks;
    }
}
