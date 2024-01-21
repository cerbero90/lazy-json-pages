<?php

dataset('sources', function () {
    $sources = [
        'https://example.com/api/v1/users',
    ];

    foreach ($sources as $source) {
        yield $source;
    }
});
