<?php

namespace PointsOfInterest\Mock;

use PointsOfInterest\Driver\Http\Shared\HttpResponseAdapter;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Psr\Http\Message\ResponseInterface;

final class ActionMock extends HttpResponseAdapter
{
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $code = intval($request->getQueryParams()['code']);
        throw new RuntimeException(message: 'Unexpected error.', code: $code);
    }
}
