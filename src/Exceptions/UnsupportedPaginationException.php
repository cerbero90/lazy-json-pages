<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Exceptions;

use Cerbero\LazyJsonPages\Data\Config;

/**
 * The exception to throw when a pagination is not supported.
 */
class UnsupportedPaginationException extends LazyJsonPagesException
{
    /**
     * Instantiate the class.
     */
    public function __construct(public readonly Config $config)
    {
        parent::__construct('The provided configuration does not match with any supported pagination.');
    }
}
