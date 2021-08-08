<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJsonPages\Exceptions\LazyJsonPagesException;
use Cerbero\LazyJsonPages\Exceptions\OutOfAttemptsException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\LazyCollection;
use Mockery;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * The package test suite.
 *
 * @runTestsInSeparateProcesses
 */
class LazyJsonPagesTest extends TestCase
{
    use FixturesAware;

    /**
     * Setup the test case before any test
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        LazyCollection::macro('fromJsonPages', new Macro());
    }

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
    public function handles_total_pages()
    {
        $initialRequest = ['https://paginated-json-api.test' => $this->fixture('page1')];
        $asyncRequests = [
            'https://paginated-json-api.test?page=2' => $this->promiseFixture('page2'),
            'https://paginated-json-api.test?page=3' => $this->promiseFixture('page3'),
        ];
        $config = 'meta.pagination.total_pages';

        $this->assertAllItemsAreLazyLoaded($initialRequest, $asyncRequests, $config);
    }

    /**
     * Assert that all items are correctly lazy-loaded via the given configuration
     *
     * @param array $initialRequest
     * @param array $asyncRequests
     * @param mixed $config
     * @param array|null $expectedIds
     * @return void
     */
    protected function assertAllItemsAreLazyLoaded(
        array $initialRequest,
        array $asyncRequests,
        $config,
        array $expectedIds = null
    ): void {
        $source = new Request('GET', key($initialRequest));
        $client = Mockery::mock('overload:' . Client::class, ClientInterface::class);

        $client->shouldReceive('send')->with($source)->andReturn(reset($initialRequest));

        foreach ($asyncRequests as $url => $promise) {
            $client->shouldReceive('sendAsync')
                ->withArgs(function (Request $request) use ($url) {
                    return $request->getUri() == $url;
                })
                ->andReturn($promise);
        }

        $index = 0;
        $expectedIds = $expectedIds ?: range(1, 13);

        lazyJsonPages($source, 'data.results', $config)->each(function ($item) use (&$index, $expectedIds) {
            $this->assertSame($expectedIds[$index], $item['id']);
            $index++;
        });
    }

    /**
     * @test
     */
    public function handles_total_items()
    {
        $initialRequest = ['https://paginated-json-api.test' => $this->fixture('page1')];
        $asyncRequests = [
            'https://paginated-json-api.test?page=2' => $this->promiseFixture('page2'),
            'https://paginated-json-api.test?page=3' => $this->promiseFixture('page3'),
        ];
        $config = ['items' => 'meta.pagination.total_items'];

        $this->assertAllItemsAreLazyLoaded($initialRequest, $asyncRequests, $config);
    }

    /**
     * @test
     */
    public function handles_total_items_with_per_page()
    {
        $initialRequest = ['https://paginated-json-api.test' => $this->fixture('page1')];
        $asyncRequests = [
            'https://paginated-json-api.test?page=2' => $this->promiseFixture('page2'),
            'https://paginated-json-api.test?page=3' => $this->promiseFixture('page3'),
        ];
        $config = [
            'items' => 'meta.pagination.total_items',
            'per_page' => 5,
        ];

        $this->assertAllItemsAreLazyLoaded($initialRequest, $asyncRequests, $config);
    }

    /**
     * @test
     */
    public function handles_items_per_page_with_total_pages()
    {
        $initialRequest = ['https://paginated-json-api.test?page_size=1' => $this->fixture('per_page')];
        $asyncRequests = [
            'https://paginated-json-api.test?page_size=5&page=1' => $this->promiseFixture('page1'),
            'https://paginated-json-api.test?page_size=5&page=2' => $this->promiseFixture('page2'),
            'https://paginated-json-api.test?page_size=5&page=3' => $this->promiseFixture('page3'),
        ];
        $config = [
            'pages' => 'meta.pagination.total_pages',
            'per_page' => [5, 'page_size'],
        ];

        $this->assertAllItemsAreLazyLoaded($initialRequest, $asyncRequests, $config);
    }

    /**
     * @test
     */
    public function handles_items_per_page_with_total_items()
    {
        $initialRequest = ['https://paginated-json-api.test?page_size=1' => $this->fixture('per_page')];
        $asyncRequests = [
            'https://paginated-json-api.test?page_size=5&page=1' => $this->promiseFixture('page1'),
            'https://paginated-json-api.test?page_size=5&page=2' => $this->promiseFixture('page2'),
            'https://paginated-json-api.test?page_size=5&page=3' => $this->promiseFixture('page3'),
        ];
        $config = [
            'items' => 'meta.pagination.total_items',
            'per_page' => [5, 'page_size'],
        ];

        $this->assertAllItemsAreLazyLoaded($initialRequest, $asyncRequests, $config);
    }

    /**
     * @test
     */
    public function handles_items_per_page_with_last_page()
    {
        $initialRequest = ['https://paginated-json-api.test?page_size=1' => $this->fixture('per_page')];
        $asyncRequests = [
            'https://paginated-json-api.test?page_size=5&page=1' => $this->promiseFixture('page1'),
            'https://paginated-json-api.test?page_size=5&page=2' => $this->promiseFixture('page2'),
            'https://paginated-json-api.test?page_size=5&page=3' => $this->promiseFixture('page3'),
        ];
        $config = [
            'last_page' => 'meta.pagination.last_page',
            'per_page' => [5, 'page_size'],
        ];

        $this->assertAllItemsAreLazyLoaded($initialRequest, $asyncRequests, $config);
    }

    /**
     * @test
     */
    public function handles_items_per_page_with_last_page_and_first_page_equal_to_0()
    {
        $initialRequest = ['https://paginated-json-api.test?page_size=1' => $this->fixture('per_page')];
        $asyncRequests = [
            'https://paginated-json-api.test?page_size=5&page=0' => $this->promiseFixture('page1'),
            'https://paginated-json-api.test?page_size=5&page=1' => $this->promiseFixture('page2'),
            'https://paginated-json-api.test?page_size=5&page=2' => $this->promiseFixture('page3'),
        ];
        $config = [
            'first_page' => 0,
            'last_page' => 'meta.pagination.last_page',
            'per_page' => [5, 'page_size'],
        ];

        $this->assertAllItemsAreLazyLoaded($initialRequest, $asyncRequests, $config);
    }

    /**
     * @test
     */
    public function handles_last_page()
    {
        $initialRequest = ['https://paginated-json-api.test' => $this->fixture('page1')];
        $asyncRequests = [
            'https://paginated-json-api.test?page=2' => $this->promiseFixture('page2'),
            'https://paginated-json-api.test?page=3' => $this->promiseFixture('page3'),
        ];
        $config = [
            'last_page' => 'meta.pagination.last_page',
        ];

        $this->assertAllItemsAreLazyLoaded($initialRequest, $asyncRequests, $config);
    }

    /**
     * @test
     */
    public function handles_last_page_and_first_page_equal_to_0()
    {
        $initialRequest = ['https://paginated-json-api.test' => $this->fixture('page1')];
        $asyncRequests = [
            'https://paginated-json-api.test?page=1' => $this->promiseFixture('page1'),
            'https://paginated-json-api.test?page=2' => $this->promiseFixture('page2'),
            'https://paginated-json-api.test?page=3' => $this->promiseFixture('page3'),
        ];
        $config = [
            'first_page' => 0,
            'last_page' => 'meta.pagination.last_page',
        ];
        $expectedIds = array_merge(range(1, 5), range(1, 13));

        $this->assertAllItemsAreLazyLoaded($initialRequest, $asyncRequests, $config, $expectedIds);
    }

    /**
     * @test
     */
    public function handles_next_page()
    {
        $config = ['next_page' => 'meta.pagination.next_page'];
        $source = new Request('GET', 'https://paginated-json-api.test');
        $client = Mockery::mock('overload:' . Client::class, ClientInterface::class);

        $client->shouldReceive('send')
            ->withArgs(function (Request $request) {
                return $request->getUri() == 'https://paginated-json-api.test';
            })
            ->andReturn($this->fixture('page1'));

        $client->shouldReceive('send')
            ->withArgs(function (Request $request) {
                return $request->getUri() == 'https://paginated-json-api.test?page=2';
            })
            ->andReturn($this->fixture('page2'));

        $client->shouldReceive('send')
            ->withArgs(function (Request $request) {
                return $request->getUri() == 'https://paginated-json-api.test?page=3';
            })
            ->andReturn($this->fixture('page3'));

        $index = 0;
        $expectedIds = range(1, 13);

        lazyJsonPages($source, 'data.results', $config)->each(function ($item) use (&$index, $expectedIds) {
            $this->assertSame($expectedIds[$index], $item['id']);
            $index++;
        });
    }

    /**
     * @test
     */
    public function chunks_pages()
    {
        $initialRequest = ['https://paginated-json-api.test' => $this->fixture('page1')];
        $asyncRequests = [
            'https://paginated-json-api.test?page=2' => $this->promiseFixture('page2'),
            'https://paginated-json-api.test?page=3' => $this->promiseFixture('page3'),
        ];
        $config = ['items' => 'meta.pagination.total_items', 'chunk' => 1];

        $this->assertAllItemsAreLazyLoaded($initialRequest, $asyncRequests, $config);
    }

    /**
     * @test
     */
    public function fails_if_configuration_does_not_match_with_any_handler()
    {
        $this->expectException(LazyJsonPagesException::class);
        $this->expectExceptionMessage('Unable to load paginated items from the provided source.');

        $initialRequest = ['https://paginated-json-api.test' => $this->fixture('page1')];
        $config = [];

        $this->assertAllItemsAreLazyLoaded($initialRequest, [], $config);
    }

    /**
     * @test
     */
    public function handles_failures()
    {
        $source = new Request('GET', 'https://paginated-json-api.test');
        $client = Mockery::mock('overload:' . Client::class, ClientInterface::class);

        $client->shouldReceive('send')->with($source)->andReturn($this->fixture('page1'));

        $client->shouldReceive('sendAsync')
            ->withArgs(function (Request $request) {
                return $request->getUri() == 'https://paginated-json-api.test?page=2';
            })
            ->andReturn($this->promiseFixture('page2'));

        $client->shouldReceive('sendAsync')
            ->withArgs(function (Request $request) {
                return $request->getUri() == 'https://paginated-json-api.test?page=3';
            })
            ->andReturn(new Promise(function () {
                throw new Exception('foo');
            }));

        try {
            lazyJsonPages($source, 'data.results', 'meta.pagination.total_pages')->each(function () {
                //
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfAttemptsException::class, $e);
            $this->assertSame('foo', $e->getMessage());
            $this->assertSame([3], $e->failedPages);
            $this->assertSame(5, $e->items->count());
        }
    }

    /**
     * @test
     */
    public function handles_next_page_failures()
    {
        $config = ['next_page' => 'meta.pagination.next_page'];
        $source = new Request('GET', 'https://paginated-json-api.test');
        $client = Mockery::mock('overload:' . Client::class, ClientInterface::class);

        $client->shouldReceive('send')
            ->withArgs(function (Request $request) {
                return $request->getUri() == 'https://paginated-json-api.test';
            })
            ->andReturn($this->fixture('page1'));

        $client->shouldReceive('send')
            ->withArgs(function (Request $request) {
                return $request->getUri() == 'https://paginated-json-api.test?page=2';
            })
            ->andReturn($this->fixture('page2'));

        $client->shouldReceive('send')
            ->withArgs(function (Request $request) {
                return $request->getUri() == 'https://paginated-json-api.test?page=3';
            })
            ->andThrow(new Exception('foo'));

        try {
            lazyJsonPages($source, 'data.results', $config)->each(function () {
                //
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfAttemptsException::class, $e);
            $this->assertSame('foo', $e->getMessage());
            $this->assertSame([3], $e->failedPages);
            $this->assertSame(0, $e->items->count());
        }
    }
}
