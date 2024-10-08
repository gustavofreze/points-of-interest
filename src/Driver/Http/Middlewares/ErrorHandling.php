<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Middlewares;

use PointsOfInterest\Driver\Http\Endpoints\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final readonly class ErrorHandling implements MiddlewareInterface
{
    public function __construct(private ExceptionHandler $exceptionHandler)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle(request: $request);
        } catch (Throwable $exception) {
            return $this->exceptionHandler->handle(exception: $exception);
        }
    }
}
