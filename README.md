# 🐼 Lazy JSON Pages

[![Author][ico-author]][link-author]
[![PHP Version][ico-php]][link-php]
[![Build Status][ico-actions]][link-actions]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![PHPStan Level][ico-phpstan]][link-phpstan]
[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![PER][ico-per]][link-per]
[![Total Downloads][ico-downloads]][link-downloads]

```php
$lazyCollection = LazyCollection::fromJsonPages($source, fn (Config $config) => $config
    ->dot('data.results')
    ->pages('total_pages')
    ->perPage(500, 'page_size')
    ->chunk(3)
    ->timeout(15)
    ->attempts(5)
    ->backoff(fn (int $attempt) => $attempt ** 2 * 100));
```

Framework-agnostic package to load items from any paginated JSON API into a [Laravel lazy collection](https://laravel.com/docs/collections#lazy-collections) via async HTTP requests.

Need to read large JSON with no pagination in a memory-efficient way? Consider using [🐼 Lazy JSON](https://github.com/cerbero90/lazy-json) or [🧩 JSON Parser](https://github.com/cerbero90/json-parser) instead.

## 📦 Install

Via Composer:

``` bash
composer require cerbero/lazy-json-pages
```

## 🔮 Usage

- [📏 Length-aware paginations](#-length-aware-paginations)
- [↪️ Cursor and next-page paginations](#-cursor-and-next-page-paginations)
- [🛠 Requests fine-tuning](#-requests-fine-tuning)
- [💢 Errors handling](#-errors-handling)

Loading paginated items of JSON APIs into a lazy collection is possible by calling the collection itself or the included helper:

```php
$items = LazyCollection::fromJsonPages($source, $path, $config);

$items = lazyJsonPages($source, $path, $config);
```

The source which paginated items are fetched from can be either a PSR-7 request or a Laravel HTTP client response:

```php
// the Guzzle request is just an example, any PSR-7 request can be used as well
$source = new GuzzleHttp\Psr7\Request('GET', 'https://paginated-json-api.test');

// Lazy JSON Pages integrates well with Laravel and supports its HTTP client responses
$source = Http::get('https://paginated-json-api.test');
```

Lazy JSON Pages only changes the page query parameter when fetching pages. This means that if the first request was authenticated (e.g. via bearer token), the requests to fetch the other pages will be authenticated as well.

The second argument, `$path`, is the key within JSON APIs holding the paginated items. The path supports dot-notation so if the key is nested, we can define its nesting levels with dots. For example, given the following JSON:

```json
{
    "data": {
        "results": [
            {
                "id": 1
            },
            {
                "id": 2
            }
        ]
    }
}
```

the path to the paginated items would be `data.results`. All nested JSON keys can be defined with dot-notation, including the keys to set in the configuration.

APIs are all different so Lazy JSON Pages allows us to define tailored configurations for each of them. The configuration can be set with the following variants:

```php
// assume that the integer indicates the number of pages
// to be used when the number is known (e.g. via previous HTTP request)
lazyJsonPages($source, $path, 10);

// assume that the string indicates the JSON key holding the number of pages
lazyJsonPages($source, $path, 'total_pages');

// set the config with an associative array
// both snake_case and camelCase keys are allowed
lazyJsonPages($source, $path, [
    'items' => 'total_items',
    'per_page' => 50,
]);

// set the config through its fluent methods
use Cerbero\LazyJsonPages\Config;

lazyJsonPages($source, $path, function (Config $config) {
    $config->items('total_items')->perPage(50);
});
```

The configuration depends on the type of pagination. Various paginations are supported, including length-aware and cursor paginations.


### 📏 Length-aware paginations

The term "length-aware" indicates all paginations that show at least one of the following numbers:
- the total number of pages
- the total number of items
- the number of the last page

Lazy JSON Pages only needs one of these numbers to work properly. When setting the number of items, we can also define the number of items shown per page (if we know it) to save some more memory. The following are all valid configurations:

```php
// configure the total number of pages:
$config = 10;
$config = 'total_pages';
$config = ['pages' => 'total_pages'];
$config->pages('total_pages');

// configure the total number of items:
$config = ['items' => 500];
$config = ['items' => 'total_items'];
$config = ['items' => 'total_items', 'per_page' => 50];
$config->items('total_items');
$config->items('total_items')->perPage(50);

// configure the number of the last page:
$config = ['last_page' => 10];
$config = ['last_page' => 'last_page_key'];
$config = ['last_page' => 'https://paginated-json-api.test?page=10'];
$config->lastPage(10);
$config->lastPage('last_page_key');
$config->lastPage('https://paginated-json-api.test?page=10');
```

Depending on the APIs, the last page may be indicated as a number or as a URL, Lazy JSON Pages supports both.

By default this package assumes that the name of the page query parameter is `page` and that the first page is `1`. If that is not the case, we can update the defaults by adding this configuration:

```php
$config->pageName('page_number')->firstPage(0);
// or
$config = [
    'page_name' => 'page_number',
    'first_page' => 0,
];
```

When dealing with a lot of data, it's a good idea to fetch only 1 item (or a few if 1 is not allowed) on the first page to count the total number of pages/items without wasting memory and then fetch all the calculated pages with many more items.

We can do that with the "per page" setting by passing:
- the new number of items to show per page
- the query parameter holding the number of items per page

```php
$source = new Request('GET', 'https://paginated-json-api.test?page_size=1');

$items = lazyJsonPages($source, $path, function (Config $config) {
    $config->pages('total_pages')->perPage(500, 'page_size');
});
```

Some APIs do not allow to request only 1 item per page, in these cases we can specify the number of items present on the first page as third argument:

```php
$source = new Request('GET', 'https://paginated-json-api.test?page_size=5');

$items = lazyJsonPages($source, $path, function (Config $config) {
    $config->pages('total_pages')->perPage(500, 'page_size', 5);
});
```

As always, we can either set the configuration through the `Config` object or with an associative array:

```php
$config = [
    'pages' => 'total_pages',
    'per_page' => [500, 'page_size', 5],
];
```

From now on we will just use the object-oriented version for brevity. Also note that the "per page" strategy can be used with any of the configurations seen so far:

```php
$config->pages('total_pages')->perPage(500, 'page_size');
// or
$config->items('total_items')->perPage(500, 'page_size');
// or
$config->lastPage('last_page_key')->perPage(500, 'page_size');
```


### ↪️ Cursor and next-page paginations

Some APIs show only the number or cursor of the next page in all pages. We can tackle this kind of pagination by indicating the JSON key holding the next page:

```php
$config->nextPage('next_page_key');
```

The JSON key may hold a number, a cursor or a URL, Lazy JSON Pages supports all of them.


### 🛠 Requests fine-tuning

Lazy JSON Pages provides a number of settings to adjust the way HTTP requests are sent to fetch pages. For example pages can be requested in chunks, so that only a few streams are kept in memory at once:

```php
$config->chunk(3);
```

The configuration above fetches 3 pages concurrently, loads the paginated items into a lazy collection and proceeds with the next 3 pages. Chunking benefits memory usage at the expense of speed, no chunking is set by default but it is recommended when dealing with a lot of data.

To minimize the memory usage Lazy JSON Pages can fetch pages synchronously, i.e. one by one, beware that this is also the slowest solution:

```php
$config->sync();
```

We can also set how many HTTP requests we want to send concurrently. By default 10 pages are fetched asynchronously:

```php
$config->concurrency(25);
```

Every HTTP request has a timeout of 5 seconds by default, but some APIs may be slow to respond. In this case we may need to set a higher timeout:

```php
$config->timeout(15);
```

When a request fails, it has up to 3 attempts to succeed. This number can of course be adjusted as needed:

```php
$config->attempts(5);
```

The backoff strategy allows us to wait some time before sending other requests when one page fails to be loaded. The package provides an exponential backoff by default, when a request fails it gets retried after 0, 1, 4, 9 seconds and so on. This strategy can also be overridden:

```php
$config->backoff(function (int $attempt) {
    return $attempt ** 2 * 100;
});
```

The above backoff strategy will wait for 100, 400, 900 milliseconds and so on.

Putting all together, this is one of the possible configurations:

```php
$source = new Request('GET', 'https://paginated-json-api.test?page_size=1');

$items = lazyJsonPages($source, 'data.results', function (Config $config) {
    $config
        ->pages('total_pages')
        ->perPage(500, 'page_size')
        ->chunk(3)
        ->timeout(15)
        ->attempts(5)
        ->backoff(fn (int $attempt) => $attempt ** 2 * 100);
});

$items
    ->filter(fn (array $item) => $this->isValid($item))
    ->map(fn (array $item) => $this->transform($item))
    ->each(fn (array $item) => $this->save($item));
```


### 💢 Errors handling

As seen above, we can mitigate potentially faulty HTTP requests with backoffs, timeouts and retries. When we reach the maximum number of attempts and a request keeps failing, an `OutOfAttemptsException` is thrown.

When caught, this exception provides information about what went wrong, including the actual exception that was thrown, the pages that failed to be fetched and the paginated items that were loaded before the failure happened:

```php
use Cerbero\LazyJsonPages\Exceptions\OutOfAttemptsException;

try {
    $items = lazyJsonPages($source, $path, $config);
} catch (OutOfAttemptsException $e) {
    // the actual exception that was thrown
    $e->original;
    // the pages that failed to be fetched
    $e->failedPages;
    // a LazyCollection with items loaded before the error
    $e->items;
}
```

## 📆 Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🧪 Testing

``` bash
composer test
```

## 💞 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## 🧯 Security

If you discover any security related issues, please email andrea.marco.sartori@gmail.com instead of using the issue tracker.

## 🏅 Credits

- [Andrea Marco Sartori][link-author]
- [All Contributors][link-contributors]

## ⚖️ License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-author]: https://img.shields.io/static/v1?label=author&message=cerbero90&color=50ABF1&logo=twitter&style=flat-square
[ico-php]: https://img.shields.io/packagist/php-v/cerbero/lazy-json-pages?color=%234F5B93&logo=php&style=flat-square
[ico-version]: https://img.shields.io/packagist/v/cerbero/lazy-json-pages.svg?label=version&style=flat-square
[ico-actions]: https://img.shields.io/github/actions/workflow/status/cerbero90/lazy-json-pages/build.yml?branch=master&style=flat-square&logo=github
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-per]: https://img.shields.io/static/v1?label=compliance&message=PER&color=blue&style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/cerbero90/lazy-json-pages.svg?style=flat-square&logo=scrutinizer
[ico-code-quality]: https://img.shields.io/scrutinizer/g/cerbero90/lazy-json-pages.svg?style=flat-square&logo=scrutinizer
[ico-phpstan]: https://img.shields.io/badge/level-max-success?style=flat-square&logo=data:image/png;base64,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
[ico-downloads]: https://img.shields.io/packagist/dt/cerbero/lazy-json-pages.svg?style=flat-square

[link-author]: https://twitter.com/cerbero90
[link-php]: https://www.php.net
[link-packagist]: https://packagist.org/packages/cerbero/lazy-json-pages
[link-actions]: https://github.com/cerbero90/lazy-json-pages/actions?query=workflow%3Abuild
[link-per]: https://www.php-fig.org/per/coding-style/
[link-scrutinizer]: https://scrutinizer-ci.com/g/cerbero90/lazy-json-pages/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/cerbero90/lazy-json-pages
[link-downloads]: https://packagist.org/packages/cerbero/lazy-json-pages
[link-phpstan]: https://phpstan.org/
[link-contributors]: ../../contributors
