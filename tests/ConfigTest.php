<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJsonPages\Exceptions\LazyJsonPagesException;
use GuzzleHttp\Psr7\Request;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * The config tests.
 *
 * @runTestsInSeparateProcesses
 */
class ConfigTest extends TestCase
{
    /**
     * This method is called after each test.
     */
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function sets_options_through_fluent_methods()
    {
        $wrapper = Mockery::mock('overload:' . SourceWrapper::class);
        $wrapper->shouldReceive('json')->with('next_page')->andReturn(2);
        $wrapper->shouldReceive('json')->with('last_page')->andReturn('https://paginated-json-api.test?page_name=3');

        $source = new Request('GET', 'https://paginated-json-api.test');

        $config = new Config($source, 'path', function (Config $config) {
            $config
                ->pageName('page_name')
                ->firstPage(0)
                ->pages(123)
                ->items(321)
                ->perPage(200, 'per_page', 2)
                ->nextPage('next_page')
                ->lastPage('last_page')
                ->chunk(3)
                ->concurrency(4)
                ->timeout(-1)
                ->attempts(6)
                ->backoff(function (int $attempt) {
                    return $attempt;
                });
        });

        $this->assertSame('path', $config->path);
        $this->assertSame('page_name', $config->pageName);
        $this->assertSame(0, $config->firstPage);
        $this->assertSame(123, $config->pages);
        $this->assertSame(321, $config->items);
        $this->assertSame(2, $config->perPage);
        $this->assertSame('per_page', $config->perPageQuery);
        $this->assertSame(200, $config->perPageOverride);
        $this->assertSame(2, $config->nextPage);
        $this->assertSame('next_page', $config->nextPageKey);
        $this->assertSame(3, $config->lastPage);
        $this->assertSame(3, $config->chunk);
        $this->assertSame(4, $config->concurrency);
        $this->assertSame(0, $config->timeout);
        $this->assertSame(6, $config->attempts);
        $this->assertSame(7, ($config->backoff)(7));
    }

    /**
     * @test
     */
    public function sets_options_through_associative_array()
    {
        $wrapper = Mockery::mock('overload:' . SourceWrapper::class);
        $wrapper->shouldReceive('json')->with('next_page')->andReturn(2);
        $wrapper->shouldReceive('json')->with('last_page')->andReturn('https://paginated-json-api.test?page_name=3');

        $source = new Request('GET', 'https://paginated-json-api.test');

        $config = new Config($source, 'path', [
            'pageName' => 'page_name',
            'first_page' => 0,
            'pages' => 123,
            'items' => 321,
            'perPage' => [200, 'per_page', 2],
            'nextPage' => 'next_page',
            'lastPage' => 'last_page',
            'sync' => true,
            'concurrency' => 4,
            'timeout' => -1,
            'attempts' => 6,
            'backoff' => function (int $attempt) {
                return $attempt;
            }
        ]);

        $this->assertSame('path', $config->path);
        $this->assertSame('page_name', $config->pageName);
        $this->assertSame(0, $config->firstPage);
        $this->assertSame(123, $config->pages);
        $this->assertSame(321, $config->items);
        $this->assertSame(2, $config->perPage);
        $this->assertSame('per_page', $config->perPageQuery);
        $this->assertSame(200, $config->perPageOverride);
        $this->assertSame(2, $config->nextPage);
        $this->assertSame('next_page', $config->nextPageKey);
        $this->assertSame(3, $config->lastPage);
        $this->assertSame(1, $config->chunk);
        $this->assertSame(4, $config->concurrency);
        $this->assertSame(0, $config->timeout);
        $this->assertSame(6, $config->attempts);
        $this->assertSame(7, ($config->backoff)(7));
    }

    /**
     * @test
     */
    public function sets_the_total_pages_with_an_integer()
    {
        Mockery::mock('overload:' . SourceWrapper::class);

        $source = new Request('GET', 'https://paginated-json-api.test');

        $config = new Config($source, 'path', 123);

        $this->assertSame(123, $config->pages);
    }

    /**
     * @test
     */
    public function sets_the_total_pages_with_a_json_key()
    {
        $wrapper = Mockery::mock('overload:' . SourceWrapper::class);
        $wrapper->shouldReceive('json')->with('total_pages')->andReturn(3);

        $source = new Request('GET', 'https://paginated-json-api.test');

        $config = new Config($source, 'path', 'total_pages');

        $this->assertSame(3, $config->pages);
    }

    /**
     * @test
     */
    public function fails_when_bad_configurations_are_provided()
    {
        Mockery::mock('overload:' . SourceWrapper::class);

        $this->expectExceptionObject(new LazyJsonPagesException('The provided configuration is not valid.'));

        $source = new Request('GET', 'https://paginated-json-api.test');

        new Config($source, 'path', new stdClass());
    }

    /**
     * @test
     */
    public function fails_when_a_bad_option_is_provided()
    {
        Mockery::mock('overload:' . SourceWrapper::class);

        $this->expectExceptionObject(new LazyJsonPagesException('The key [bad] is not valid.'));

        $source = new Request('GET', 'https://paginated-json-api.test');

        new Config($source, 'path', ['bad' => true]);
    }
}
