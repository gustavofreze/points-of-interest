<?php

namespace PointsOfInterest\Driver\Http\Shared;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;
use TinyBlocks\Http\HttpCode;

abstract class HttpResponseAdapter
{
    protected Request $request;
    protected Response $response;
    private HttpCode $httpCode = HttpCode::OK;
    private ?HttpResponse $httpResponse = null;

    private const CONTENT_TYPE = 'application/json';

    abstract protected function handle(Request $request): array;

    public function __invoke(Request $request, Response $response): Response
    {
        $this->request = $request;
        $this->response = $response;

        try {
            return $this->replyWithSuccess();
        } catch (Throwable $exception) {
            return $this->replyWithException(exception: $exception);
        }
    }

    public function reply(): array
    {
        return $this->httpResponse ? $this->httpResponse->toArray() : [];
    }

    public function withHttpCode(HttpCode $httpCode): HttpResponseAdapter
    {
        $this->httpCode = $httpCode;

        return $this;
    }

    public function withResponse(HttpResponse $httpResponse): HttpResponseAdapter
    {
        $this->httpResponse = $httpResponse;

        return $this;
    }

    protected function requestWithParsedBody(): array
    {
        return json_decode($this->request->getBody()->__toString(), true);
    }

    private function replyWithSuccess(): Response
    {
        $data = $this->handle(request: $this->request);
        $this->response->getBody()->write(json_encode($data));

        return $this->response
            ->withStatus($this->httpCode->value)
            ->withHeader('Content-type', self::CONTENT_TYPE);
    }

    private function replyWithException(Throwable $exception): Response
    {
        $data = ['error' => $exception->getMessage()];
        $code = $exception->getCode();
        $httpCode = (empty($code) || !HttpCode::isHttpCode(httpCode: $code))
            ? HttpCode::INTERNAL_SERVER_ERROR
            : HttpCode::from($code);

        if ($exception instanceof HttpException) {
            $data = ['error' => $exception->getErrors()];
        }

        $this->response->getBody()->write(json_encode($data));

        return $this->response
            ->withStatus($httpCode->value)
            ->withHeader('Content-type', self::CONTENT_TYPE);
    }
}
