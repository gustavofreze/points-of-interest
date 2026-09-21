<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\PointOfInterest\FindAll\Http;

use PointsOfInterest\Query\PointOfInterest\FindAll\PointsOfInterestFinding;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class FindPointsOfInterest implements RequestHandlerInterface
{
    private const string BASE_URI = '/points-of-interest';

    public function __construct(private PointsOfInterestFinding $pointsOfInterest)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = FindPointsOfInterestRequest::from(request: $request);

        return $this->pointsOfInterest
            ->findAll(keyset: $query->keyset, proximity: $query->proximity, comparisons: $query->comparisons)
            ->toResponse(baseUri: FindPointsOfInterest::BASE_URI);
    }
}
