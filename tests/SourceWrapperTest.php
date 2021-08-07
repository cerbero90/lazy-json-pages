<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJsonPages\Exceptions\LazyJsonPagesException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\Response;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * The source wrapper tests.
 *
 * @runTestsInSeparateProcesses
 */
class SourceWrapperTest extends TestCase
{
    use FixturesAware;

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
    public function fails_if_source_is_not_valid()
    {
        $this->expectExceptionObject(new LazyJsonPagesException('The provided JSON source is not valid.'));

        new SourceWrapper(123);
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function sets_response_and_request_from_a_psr7_request()
    {
        $source = new Request('GET', 'https://paginated-json-api.test');
        $client = Mockery::mock('overload:' . Client::class, ClientInterface::class);
        $response = $this->fixture('page1');

        $client->shouldReceive('send')->with($source)->andReturn($response);

        $wrapper = new SourceWrapper($source);

        $this->assertSame($source, $wrapper->original);
        $this->assertSame($source, $wrapper->request);
        $this->assertSame($response, $wrapper->response);
    }

    /**
     * @test
     */
    public function sets_response_and_request_from_a_laravel_http_client_response()
    {
        $response = $this->fixture('page1');
        $source = new Response($response);
        $request = new Request('GET', 'https://paginated-json-api.test');
        $source->transferStats = new TransferStats($request);

        $wrapper = new SourceWrapper($source);

        $this->assertSame($source, $wrapper->original);
        $this->assertSame($request, $wrapper->request);
        $this->assertSame($response, $wrapper->response);
    }

    /**
     * @test
     */
    public function fails_if_laravel_response_does_not_have_transfer_stats()
    {
        $this->expectException(LazyJsonPagesException::class);
        $this->expectExceptionMessage('The HTTP client response is not aware of the original request.');

        $source = new Response($this->fixture('page1'));

        new SourceWrapper($source);
    }
}
