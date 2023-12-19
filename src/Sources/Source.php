<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Sources;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Source
{
    public function request(): RequestInterface;

    public function response(): ResponseInterface;
}
