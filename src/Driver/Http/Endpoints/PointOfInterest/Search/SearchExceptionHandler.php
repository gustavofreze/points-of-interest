<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Search;

use PointsOfInterest\Driver\Http\Endpoints\ExceptionHandler;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\InvalidRequest;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TinyBlocks\Http\HttpResponse;

final class SearchExceptionHandler implements ExceptionHandler
{
    public function handle(Throwable $exception): ResponseInterface
    {
        $error = ['error' => $exception->getMessage()];

        return match (get_class($exception)) {
            InvalidRequest::class => HttpResponse::unprocessableEntity(data: ['error' => $exception->getErrors()]),
            default               => HttpResponse::internalServerError(data: $error)
        };
    }
}
