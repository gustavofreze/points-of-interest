<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Throwable;

interface ExceptionHandler
{
    /**
     * Handles the given exception and returns an appropriate HTTP response.
     *
     * @param Throwable $exception The exception to be handled.
     * @return ResponseInterface The HTTP response generated for the exception.
     */
    public function handle(Throwable $exception): ResponseInterface;
}
