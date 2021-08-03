<?php

namespace Cerbero\LazyJsonPages;

use Cerbero\LazyJsonPages\Exceptions\LazyJsonPagesException;
use GuzzleHttp\Client;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The source wrapper.
 *
 */
class SourceWrapper
{
    /**
     * The original source.
     *
     * @var RequestInterface|Response
     */
    public $original;

    /**
     * The HTTP request.
     *
     * @var RequestInterface
     */
    public $request;

    /**
     * The HTTP response.
     *
     * @var ResponseInterface
     */
    public $response;

    /**
     * The decoded JSON.
     *
     * @var array
     */
    protected $decodedJson;

    /**
     * Instantiate the class.
     *
     * @param RequestInterface|Response $source
     *
     * @throws LazyJsonPagesException
     */
    public function __construct($source)
    {
        if (!$source instanceof RequestInterface && !$source instanceof Response) {
            throw new LazyJsonPagesException('The provided JSON source is not valid.');
        }

        $this->original = $source;
        $this->request = $this->getSourceRequest();
        $this->response = $this->getSourceResponse();
    }

    /**
     * Retrieve the HTTP request of the source
     *
     * @return RequestInterface
     *
     * @throws LazyJsonPagesException
     */
    protected function getSourceRequest(): RequestInterface
    {
        if ($this->original instanceof RequestInterface) {
            return $this->original;
        } elseif (isset($this->original->transferStats)) {
            return $this->original->transferStats->getRequest();
        }

        throw new LazyJsonPagesException('The HTTP client response is not aware of the original request.');
    }

    /**
     * Retrieve the HTTP response of the source
     *
     * @return ResponseInterface
     */
    protected function getSourceResponse(): ResponseInterface
    {
        if ($this->original instanceof RequestInterface) {
            return (new Client())->send($this->original);
        }

        return $this->original->toPsrResponse();
    }

    /**
     * Retrieve a fragment of the decoded JSON
     *
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function json(string $path, $default = null)
    {
        if (!isset($this->decodedJson)) {
            $this->decodedJson = json_decode((string) $this->response->getBody(), true);
        }

        return Arr::get($this->decodedJson, $path, $default);
    }
}
