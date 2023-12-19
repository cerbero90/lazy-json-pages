<?php

declare(strict_types=1);

use Cerbero\LazyJsonPages\LazyJsonPages;
use Illuminate\Support\LazyCollection;

(static function () {
    LazyCollection::macro('fromJsonPages', [LazyJsonPages::class, 'from']);
})();
