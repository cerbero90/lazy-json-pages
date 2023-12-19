<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Exceptions;

use Exception;
use Throwable;

class LazyJsonPagesException extends Exception
{
    public static function from(Throwable $e): static
    {
        return $e instanceof static ? $e : new static($e->getMessage());
    }
}
