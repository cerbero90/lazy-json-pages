<?php

declare(strict_types=1);

namespace Cerbero\LazyJsonPages\Data;

/**
 * The dot value object.
 */
final class Dot
{
    /**
     * Instantiate the class.
     */
    public function __construct(public readonly string $dot) {}

    /**
     * Retrieve the JSON pointer of this dot.
     */
    public function toPointer(): string
    {
        $search = ['~', '/', '.', '*', '\\', '"'];
        $replace = ['~0', '~1', '/', '-', '\\\\', '\"'];

        return $this->dot == '*' ? '' : '/' . str_replace($search, $replace, $this->dot);
    }
}
