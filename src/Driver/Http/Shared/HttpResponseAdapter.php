<?php

namespace PointsOfInterest\Driver\Http\Shared;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;
use TinyBlocks\Http\HttpCode;
use TinyBlocks\Http\HttpContentType;

abstract class HttpResponseAdapter
{
    protected Request $request;
    protected Response $response;

    abstract protected function handle(Request $request): Response;

    public function __invoke(Request $request, Response $response): Response
    {
        $this->request = $request;
        $this->response = $response;

        try {
            return $this->handle(request: $this->request);
        } catch (Throwable $exception) {
            return $this->handleException(exception: $exception);
        }
    }

    protected function requestWithParsedBody(): array
    {
        return json_decode($this->request->getBody()->__toString(), true);
    }

    private function handleException(Throwable $exception): Response
    {
        $data = ['error' => $exception->getMessage()];
        $code = $exception->getCode();
        $httpCode = (empty($code) || !HttpCode::isHttpCode(httpCode: $code))
            ? HttpCode::INTERNAL_SERVER_ERROR
            : HttpCode::from(value: $code);

        if ($exception instanceof HttpException) {
            $data = ['error' => $exception->getErrors()];
        }

        $this->response->getBody()->write(json_encode($data));

        return $this->response
            ->withStatus($httpCode->value)
            ->withHeader(HttpContentType::APPLICATION_JSON->key(), HttpContentType::APPLICATION_JSON->value);
    }
}
