<?php

namespace PointsOfInterest\Mock;

use PointsOfInterest\Driver\Http\Shared\HttpResponseAdapter;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class ActionMock extends HttpResponseAdapter
{
    protected function handle(ServerRequestInterface $request): array
    {
        $code = intval($request->getQueryParams()['code']);
        throw new RuntimeException('Unexpected error.', $code);
    }
}
