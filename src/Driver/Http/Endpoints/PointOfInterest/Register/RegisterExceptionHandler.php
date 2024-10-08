<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register;

use PointsOfInterest\Domain\Exceptions\NegativeCoordinate;
use PointsOfInterest\Driver\Http\Endpoints\ExceptionHandler;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\InvalidRequest;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register\Exceptions\PointOfInterestAlreadyExists;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TinyBlocks\Http\HttpResponse;

final class RegisterExceptionHandler implements ExceptionHandler
{
    public function handle(Throwable $exception): ResponseInterface
    {
        $error = ['error' => $exception->getMessage()];

        return match (get_class($exception)) {
            InvalidRequest::class,              => HttpResponse::unprocessableEntity(
                data: ['error' => $exception->getErrors()]
            ),
            NegativeCoordinate::class,          => HttpResponse::unprocessableEntity(data: $error),
            PointOfInterestAlreadyExists::class => HttpResponse::conflict(data: $error),
            default                             => HttpResponse::internalServerError(data: $error)
        };
    }
}
