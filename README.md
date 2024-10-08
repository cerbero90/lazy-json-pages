# 📜 Lazy JSON Pages

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
use Illuminate\Support\LazyCollection;

LazyCollection::fromJsonPages($source)
    ->totalPages('pagination.total_pages')
    ->async(requests: 3)
    ->throttle(requests: 100, perMinutes: 1)
    ->collect('data.*');
```

Framework-agnostic API scraper to load items from any paginated JSON API into a [Laravel lazy collection](https://laravel.com/docs/collections#lazy-collections) via async HTTP requests.

> [!TIP]
> Need to read large JSON with no pagination in a memory-efficient way?
>
> Consider using [🐼 Lazy JSON](https://github.com/cerbero90/lazy-json) or [🧩 JSON Parser](https://github.com/cerbero90/json-parser) instead.


## 📦 Install

Via Composer:

``` bash
composer require cerbero/lazy-json-pages
```


## 🔮 Usage

* [👣 Basics](#-basics)
* [💧 Sources](#-sources)
* [🏛️ Pagination structure](#%EF%B8%8F-pagination-structure)
* [📏 Length-aware paginations](#-length-aware-paginations)
* [↪️ Cursor-aware paginations](#%EF%B8%8F-cursor-aware-paginations)
* [🔗 Link header paginations](#-link-header-paginations)
* [👽 Custom paginations](#-custom-paginations)
* [🚀 Requests optimization](#-requests-optimization)
* [💢 Errors handling](#-errors-handling)
* [🤝 Laravel integration](#-laravel-integration)


### 👣 Basics

Depending on our coding style, we can instantiate Lazy JSON Pages in 4 different ways:

```php
use Cerbero\LazyJsonPages\LazyJsonPages;
use Illuminate\Support\LazyCollection;

use function Cerbero\LazyJsonPages\lazyJsonPages;

// lazy collection macro
LazyCollection::fromJsonPages($source);

// classic instantiation
new LazyJsonPages($source);

// static method
LazyJsonPages::from($source);

// namespaced helper
lazyJsonPages($source);
```

The variable `$source` in our examples represents any [source](#-sources) that points to a paginated JSON API. Once we define the source, we can then chain methods to define how the API is paginated:

```php
$lazyCollection = LazyJsonPages::from($source)
    ->totalItems('pagination.total_items')
    ->offset()
    ->collect('results.*');
```

When calling `collect()`, we indicate that the pagination structure is defined and that we are ready to collect the paginated items within a [Laravel lazy collection](https://laravel.com/docs/collections#lazy-collections), where we can loop through the items one by one and apply filters and transformations in a memory-efficient way.


### 💧 Sources

A source is any means that can point to a paginated JSON API. A number of sources is supported by default:

- **endpoint URIs**, e.g. `https://example.com/api/v1/users` or any instance of `Psr\Http\Message\UriInterface`
- **PSR-7 requests**, i.e. any instance of `Psr\Http\Message\RequestInterface`
- **Laravel HTTP client requests**, i.e. any instance of `Illuminate\Http\Client\Request`
- **Laravel HTTP client responses**, i.e. any instance of `Illuminate\Http\Client\Response`
- **Laravel HTTP requests**, i.e. any instance of `Illuminate\Http\Request`
- **Symfony requests**, i.e. any instance of `Symfony\Component\HttpFoundation\Request`
- **user-defined sources**, i.e. any instance of `Cerbero\LazyJsonPages\Sources\Source`

Here are some examples of sources:

```php
// a simple URI string
$source = 'https://example.com/api/v1/users';

// any PSR-7 compatible request is supported, including Guzzle requests
$source = new GuzzleHttp\Psr7\Request('GET', 'https://example.com/api/v1/users');

// while being framework-agnostic, Lazy JSON Pages integrates well with Laravel
$source = Http::withToken($bearer)->get('https://example.com/api/v1/users');
```

If none of the above sources satifies our use case, we can implement our own source.

<details><summary><b>Click here to see how to implement a custom source.</b></summary>

To implement a custom source, we need to extend `Source` and implement 2 methods:

```php
use Cerbero\LazyJsonPages\Sources\Source;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CustomSource extends Source
{
    public function request(): RequestInterface
    {
        // return a PSR-7 request
    }

    public function response(): ResponseInterface
    {
        // return a PSR-7 response
    }
}
```

The parent class `Source` gives us access to 2 properties:
- `$source`: the custom source for our use case
- `$client`: the Guzzle HTTP client

The methods to implement turn our custom source into a PSR-7 request and a PSR-7 response. Please refer to the [already existing sources](https://github.com/cerbero90/json-parser/tree/master/src/Sources) to see some implementations.

Once the custom source is implemented, we can instruct Lazy JSON Pages to use it:

```php
LazyJsonPages::from(new CustomSource($source));
```

If you find yourself implementing the same custom source in different projects, feel free to send a PR and we will consider to support your custom source by default. Thank you in advance for any contribution!
</details>


### 🏛️ Pagination structure

After defining the [source](#-sources), we need to let Lazy JSON Pages know what the paginated API looks like.

If the API uses a query parameter different from `page` to specify the current page - for example `?current_page=1` - we can chain the method `pageName()`:

```php
LazyJsonPages::from($source)->pageName('current_page');
```

Otherwise, if the number of the current page is present in the URI path - for example `https://example.com/users/page/1` - we can chain the method `pageInPath()`:

```php
LazyJsonPages::from($source)->pageInPath();
```

By default the last integer in the URI path is considered the page number. However we can customize the regular expression used to capture the page number, if need be:

```php
LazyJsonPages::from($source)->pageInPath('~/page/(\d+)$~');
```

Some API paginations may start with a page different from `1`. If that's the case, we can define the first page by chaining the method `firstPage()`:

```php
LazyJsonPages::from($source)->firstPage(0);
```

Now that we have customized the basic structure of the API, we can describe how items are paginated depending on whether the pagination is [length-aware](#-length-aware-paginations) or [cursor](#%EF%B8%8F-cursor-and-next-page-paginations) based.


### 📏 Length-aware paginations

The term "length-aware" indicates any pagination containing at least one of the following length information:
- the total number of pages
- the total number of items
- the number of the last page

Lazy JSON Pages only needs one of these details to work properly:

```php
LazyJsonPages::from($source)->totalPages('pagination.total_pages');

LazyJsonPages::from($source)->totalItems('pagination.total_items');

LazyJsonPages::from($source)->lastPage('pagination.last_page');
```

If the length information is nested in the JSON body, we can use dot-notation to indicate the level of nesting. For example, `pagination.total_pages` means that the total number of pages sits in the object `pagination`, under the key `total_pages`.

Otherwise, if the length information is displayed in the headers, we can use the same methods to gather it by simply defining the name of the header:

```php
LazyJsonPages::from($source)->totalPages('X-Total-Pages');

LazyJsonPages::from($source)->totalItems('X-Total-Items');

LazyJsonPages::from($source)->lastPage('X-Last-Page');
```

APIs can expose their length information in the form of numbers (`total_pages: 10`) or URIs (`last_page: "https://example.com?page=10"`), Lazy JSON Pages supports both.

If the pagination works with an offset, we can configure it with the `offset()` method. The value of the offset will be calculated based on the number of items present on the first page:

```php
// indicate that the offset is defined by the `offset` query parameter, e.g. ?offset=50
LazyJsonPages::from($source)
    ->totalItems('pagination.total_items')
    ->offset();

// indicate that the offset is defined by the `skip` query parameter, e.g. ?skip=50
LazyJsonPages::from($source)
    ->totalItems('pagination.total_items')
    ->offset('skip');
```


### ↪️ Cursor-aware paginations

Not all paginations are [length-aware](#-length-aware-paginations), some may be built in a way where each page has a cursor pointing to the next page.

We can tackle this kind of pagination by indicating the key or the header holding the cursor:

```php
LazyJsonPages::from($source)->cursor('pagination.cursor');

LazyJsonPages::from($source)->cursor('X-Cursor');
```

The cursor may be a number, a string or a URI: Lazy JSON Pages supports them all.


### 🔗 Link header paginations

Some paginated API responses include a header called `Link`. An example is [GitHub](https://api.github.com/repos/octocat/hello-world/issues?state=open): if we inspect the response headers, we can see the `Link` header looking like this:

```
<https://api.github.com/repositories/1296269/issues?state=open&page=2>; rel="next",
<https://api.github.com/repositories/1296269/issues?state=open&page=43>; rel="last"
```

To lazy-load items from a Link header pagination, we can chain the method `linkHeader()`:

```php
LazyJsonPages::from($source)->linkHeader();
```


### 👽 Custom paginations

Lazy JSON Pages provides several methods to extract items from the most popular pagination mechanisms. However if we need a custom solution, we can implement our own pagination.

<details><summary><b>Click here to see how to implement a custom pagination.</b></summary>

To implement a custom pagination, we need to extend `Pagination` and implement 1 method:

```php
use Cerbero\LazyJsonPages\Paginations\Pagination;
use Traversable;

class CustomPagination extends Pagination
{
    public function getIterator(): Traversable
    {
        // return a Traversable yielding the paginated items
    }
}
```

The parent class `Pagination` gives us access to 3 properties:
- `$source`: the [source](#-sources) pointing to the paginated JSON API
- `$client`: the Guzzle HTTP client
- `$config`: the configuration that we generated by chaining methods like `totalPages()`

The method `getIterator()` defines the logic to extract paginated items in a memory-efficient way. Please refer to the [already existing paginations](https://github.com/cerbero90/json-parser/tree/master/src/Paginations) to see some implementations.

Once the custom pagination is implemented, we can instruct Lazy JSON Pages to use it:

```php
LazyJsonPages::from($source)->pagination(CustomPagination::class);
```

If you find yourself implementing the same custom pagination in different projects, feel free to send a PR and we will consider to support your custom pagination by default. Thank you in advance for any contribution!
</details>


### 🚀 Requests optimization

Paginated APIs differ from each other, so Lazy JSON Pages lets us tweak our HTTP requests specifically for our use case.

By default HTTP requests are sent synchronously. If we want to send more than one request without waiting for the response, we can call the `async()` method and set the number of concurrent requests:

```php
LazyJsonPages::from($source)->async(requests: 5);
```

> [!NOTE]  
> Please note that asynchronous requests improve speed at the expense of memory, as more responses are going to be loaded at once.

Several APIs set rate limits to reduce the number of allowed requests for a period of time. We can instruct Lazy JSON Pages to respect such limits by throttling our requests:

```php
// we send a maximum of 3 requests per second, 60 per minute and 3,000 per hour
LazyJsonPages::from($source)
    ->throttle(requests: 3, perSeconds: 1)
    ->throttle(requests: 60, perMinutes: 1)
    ->throttle(requests: 3000, perHours: 1);
```

Internally, Lazy JSON Pages uses [Guzzle](https://docs.guzzlephp.org) as its HTTP client. We can customize the client behavior by adding as many [middleware](https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#middleware) as we need:

```php
LazyJsonPages::from($source)
    ->middleware('log_requests', $logRequests)
    ->middleware('cache_responses', $cacheResponses);
```

If we need a middleware to be added every time we invoke Lazy JSON Pages, we can add a global middleware:

```php
LazyJsonPages::globalMiddleware('fire_events', $fireEvents);
```

Sometimes writing [Guzzle middleware](https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#middleware) might be cumbersome. Alternatively Lazy JSON Pages provides convenient methods to fire callbacks when sending a request or receiving a response:

```php
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

LazyJsonPages::from($source)
    ->onRequest(fn(RequestInterface $request) => ...)
    ->onResponse(fn(ResponseInterface $response, RequestInterface $request) => ...);
```

We can also tweak the number of allowed seconds before an API connection times out or the allowed duration of the entire HTTP request (by default they are both set to 5 seconds):

```php
LazyJsonPages::from($source)
    ->connectionTimeout(7)
    ->requestTimeout(10);
```

If the 3rd party API is faulty or error-prone, we can indicate how many times we want to retry failing HTTP requests and the backoff strategy to compute the milliseconds to wait before retrying (by default failing requests are repeated 3 times after an exponential backoff of 100, 400 and 900 milliseconds):

```php
// repeat failing requests 5 times after a backoff of 1, 2, 3, 4 and 5 seconds
LazyJsonPages::from($source)
    ->attempts(5)
    ->backoff(fn(int $attempt) => $attempt * 1000);
```

### 💢 Errors handling

If something goes wrong during the scraping process, we can intercept the error and execute a custom logic to handle it:

```php
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

LazyJsonPages::from($source)
    ->onError(fn(Throwable $e, RequestInterface $request, ?ResponseInterface $response) => ...);
```

Any exception thrown by this package extends the `LazyJsonPagesException` class. This makes it easy to handle all exceptions in a single catch block:

```php
use Cerbero\LazyJsonPages\Exceptions\LazyJsonPagesException;

try {
    LazyJsonPages::from($source)->linkHeader()->collect()->each(...);
} catch (LazyJsonPagesException $e) {
    // handle any exception thrown by Lazy JSON Pages
}
```

For reference, here is a comprehensive table of all the exceptions thrown by this package:

|`Cerbero\LazyJsonPages\Exceptions\`|thrown when|
|---|---|
|`InvalidKeyException`|a JSON key does not contain a valid value|
|`InvalidPageInPathException`|a page cannot be found in the URI path|
|`InvalidPaginationException`|a pagination implementation is not valid|
|`OutOfAttemptsException`|an HTTP request failed too many times|
|`RequestNotSentException`|a JSON source didn't send any HTTP request|
|`UnsupportedPaginationException`|a pagination is not supported|
|`UnsupportedSourceException`|a JSON source is not supported|


### 🤝 Laravel integration

If used in a [Laravel](https://laravel.com) project, Lazy JSON Pages automatically fires events when:
- an HTTP request is about to be sent, by firing `Illuminate\Http\Client\Events\RequestSending`
- an HTTP response is received, by firing `Illuminate\Http\Client\Events\ResponseReceived`
- a connection failed, by firing `Illuminate\Http\Client\Events\ConnectionFailed`

This is especially handy for debugging tools like [Laravel Telescope](https://laravel.com/docs/telescope) or [Spatie Ray](https://spatie.be/docs/ray/installation-in-your-project/laravel) or for triggering the related event listeners.


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
[ico-phpstan]: https://img.shields.io/badge/level-max-success?style=flat-square&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAGb0lEQVR42u1Xe1BUZRS/y4Kg8oiR3FCCBUySESZBRCiaBnmEsOzeSzsg+KxYYO9dEEftNRqZjx40FRZkTpqmOz5S2LsXlEZBciatkQnHDGYaGdFy1EpGMHl/p/PdFlt2rk5O+J9n5nA/vtf5ned3lnlISpRhafBlLRLHCtJGVrB/ZBDsaw2lUqzReGAC46DstTYfnSCGUjaaDvgxACo6j3vUenNdImeRXqdnWV5az5rrnzeZznj8J+E5Ftsclhf3s4J4CS/oRx5Bvon8ZU65FGYQxAwcf85a7CeRz+C41THejueydCZ7AAK34nwv3kHP/oUKdOL4K7258fF7Cud427O48RQeGkIGJ77N8fZqlrcfRP4d/x90WQfHXLeBt9dTrSlwl3V65ynWLM1SEA2qbNQckbe4Xmww10Hmy3shid0CMcmlEJtSDsl5VZBdfAgMvI3uuR+moJqN6LaxmpsOBeLCDmTifCB92RcQmbAUJvtqALc5sQr8p86gYBCcFdBq9wOin7NQax6ewlB6rqLZHf23FP10y3lj6uJtEBg2HxiVCtzd3SEwMBCio6Nh9uzZ4O/vLwOZ4OUNM2NyIGPFrvuzBG//lRPs+VQ2k1ki+ePkd84bskz7YFpYgizEz88P8vPzYffu3dDS0gJNTU1QXV0NqampRK1WIwgfiE4qhOyig0rC+pCvK8QUoML7uJVHA5kcQUp3DSpqWjc3d/Dy8oKioiLo6uqCoaEhuHb1KvT09AAhBFpbW4lOpyMyyIBQSCmoUQLQzgniNvz+obB2HS2RwBgE6dOxCyJogmNkP2u1Wrhw4QJ03+iGrR9XEd3CTNBn6eCbo40wPDwMdXV1BF1DVG5qiEtboxSUP6J71+D3NwUAhLOIRQzm7lnnhYUv7QFv/yDZ/Lm5ubK2DVI9iZ8bR8JDtEB57lNzENQN6OjoIGlpabIVZsYaMTO+hrikRRA1JxmSX9hE7/sJtVyF38tKsUCVZxBhz9jI3wGT/QJlADzPAyXrnj0kInzGHQCRMyOg/ed2uHjxIuE4TgYQHq2DLJqumashY+lnsMC4GVC5do6XVuK9l+4SkN8y+GfYeVJn2g++U7QygPT0dBgYGIDvT58mnF5PQcjC83PzSF9fH7S1tZGEhAQZQOT8JaA317oIkM6jS8uVLSDzOQqg23Uh+MlkOf00Gg0cP34c+vv74URzM9n41gby/rvvkc7OThlATU3NCGYJUXt4QaLuTYwBcTSOBmj1RD7D4Tsix4ByOjZRF/zgupDEbgZ3j4ly/qekpND0o5aQ44HS4OAgsVqtI1gTZO01IbG0aP1bknnxCDUvArHi+B0lJSlzglTFYO2udF3Ql9TCrHn5oEIreHp6QlRUFJSUlJCqqipSWVlJ8vLyCGYIFS7HS3zGa87mv4lcjLwLlStlLTKYYUUAlvrlDGcW45wKxXX6aqHZNutM+1oQBHFTewAKkoH4+vqCj48PYAGS5yb5amjNoO+CU2SL53NKpDD0vxHHmOJir7L5xUvZgm0us2R142ScOIyVqYvlpWU4XoHIP8DXL2b+wjdWeXh6U2FjmIIKmbWAYPFRMus62h/geIvjOQYlpuDysQrLL6Ger49HgW8jqvXUhI7UvDb9iaSTDqHtyItiF5Suw5ewF/Nd8VJ6zlhsn06bEhwX4NyfCvuGEeRpTmh4mkG68yDpyuzB9EUcjU5awbAgncPlAeSdAQER0zCndzqVbeXC4qDsMpvGEYBXRnsDx4N3Auf1FCTjTIaVtY/QTmd0I8bBVm1kejEubUfO01vqImn3c49X7qpeqI9inIgtbpxK3YrKfIJCt+OeV2nfUVFR4ca4EkVENyA7gkYcMfB1R5MMmxZ7ez/2KF5SSN1yV+158UPsJT0ZBcI2bRLtIXGoYu5FerOUiJe1OfsL3XEWH43l2KS+iJF9+S4FpcNgsc+j8cT8H4o1bfPg/qkLt50uJ1RzdMsGg0UqwfEN114Pwb1CtWTGg+Y9U5ClK9x7xUWI7BI5VQVp0AVcQ3bZkQhmnEgdHhKyNSZe16crtBIlc7sIb6cRLft2PCgoKGjijBDtjrAQ7a3EdMsxzIRflAFIhPb6mHYmYwX+WBlPQgskhgVryyJCQyNyBLsBQdQ6fgsQhyt6MSOOsWZ7gbH8wETmgRKAijatNL8Ngm0xx4tLcsps0Wzx4al0jXlI40B/A3pa144MDtSgAAAAAElFTkSuQmCC
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
