# 🐼 Lazy JSON Pages

[![Author][ico-author]][link-author]
[![PHP Version][ico-php]][link-php]
[![Laravel Version][ico-laravel]][link-laravel]
[![Octane Compatibility][ico-octane]][link-octane]
[![Build Status][ico-actions]][link-actions]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![PSR-7][ico-psr7]][link-psr7]
[![PSR-12][ico-psr12]][link-psr12]
[![Total Downloads][ico-downloads]][link-downloads]

Framework agnostic package using asynchronous HTTP requests and generators to load paginated items of JSON APIs into [Laravel lazy collections](https://laravel.com/docs/collections#lazy-collections).

Need to load heavy JSON with no pagination? Consider using [Lazy JSON](https://github.com/cerbero90/lazy-json) instead.


## Install

In a Laravel application, all you need to do is requiring the package:

``` bash
composer require cerbero/lazy-json-pages
```

Otherwise, you also need to register the lazy collection macro:

``` php
use Cerbero\LazyJsonPages\Macro;
use Illuminate\Support\LazyCollection;

LazyCollection::macro('fromJsonPages', new Macro());
```

## Usage

- [Length-aware paginations](#length-aware-paginations)
- [Cursor and next-page paginations](#cursor-and-next-page-paginations)
- [Fine-tuning the pages fetching process](#fine-tuning-the-pages-fetching-process)

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

the path to the paginated items would be `data.results`.

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


### Length-aware paginations

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


### Cursor and next-page paginations

Some APIs show only the number or cursor of the next page in all pages. We can tackle this kind of pagination by indicating the JSON key holding the next page:

```php
$config->nextPage('next_page_key');
```

The JSON key may hold a number, a cursor or a URL, Lazy JSON Pages supports all of them.


### Fine-tuning the pages fetching process

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


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email andrea.marco.sartori@gmail.com instead of using the issue tracker.

## Credits

- [Andrea Marco Sartori][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-author]: https://img.shields.io/static/v1?label=author&message=cerbero90&color=50ABF1&logo=twitter&style=flat-square
[ico-php]: https://img.shields.io/packagist/php-v/cerbero/lazy-json-pages?color=%234F5B93&logo=php&style=flat-square
[ico-laravel]: https://img.shields.io/static/v1?label=laravel&message=%E2%89%A56.0&color=ff2d20&logo=laravel&style=flat-square
[ico-octane]: https://img.shields.io/static/v1?label=octane&message=compatible&color=ff2d20&logo=laravel&style=flat-square
[ico-version]: https://img.shields.io/packagist/v/cerbero/lazy-json-pages.svg?label=version&style=flat-square
[ico-actions]: https://img.shields.io/github/workflow/status/cerbero90/lazy-json-pages/build?style=flat-square&logo=github
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-psr7]: https://img.shields.io/static/v1?label=compliance&message=PSR-7&color=blue&style=flat-square
[ico-psr12]: https://img.shields.io/static/v1?label=compliance&message=PSR-12&color=blue&style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/cerbero90/lazy-json-pages.svg?style=flat-square&logo=scrutinizer
[ico-code-quality]: https://img.shields.io/scrutinizer/g/cerbero90/lazy-json-pages.svg?style=flat-square&logo=scrutinizer
[ico-downloads]: https://img.shields.io/packagist/dt/cerbero/lazy-json-pages.svg?style=flat-square

[link-author]: https://twitter.com/cerbero90
[link-php]: https://www.php.net
[link-laravel]: https://laravel.com
[link-octane]: https://github.com/laravel/octane
[link-packagist]: https://packagist.org/packages/cerbero/lazy-json-pages
[link-actions]: https://github.com/cerbero90/lazy-json-pages/actions?query=workflow%3Abuild
[link-psr7]: https://www.php-fig.org/psr/psr-7/
[link-psr12]: https://www.php-fig.org/psr/psr-12/
[link-scrutinizer]: https://scrutinizer-ci.com/g/cerbero90/lazy-json-pages/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/cerbero90/lazy-json-pages
[link-downloads]: https://packagist.org/packages/cerbero/lazy-json-pages
[link-contributors]: ../../contributors
