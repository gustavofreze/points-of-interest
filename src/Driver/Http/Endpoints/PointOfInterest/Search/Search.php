<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Search;

use PointsOfInterest\Domain\Ports\Outbound\Points;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Search\Dtos\Request;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Search\Dtos\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TinyBlocks\Http\Response as HttpResponse;

final readonly class Search implements RequestHandlerInterface
{
    public function __construct(private Points $points)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = new Request(parameters: $request->getQueryParams());
        $pointsOfInterest = $this->points->findAll();

        $pointsOfInterest = $request->noHasFilters()
            ? $pointsOfInterest
            : $pointsOfInterest->byProximity(
                referencePoint: $request->toReferencePoint(),
                maximumDistance: $request->toDistance()
            );

        $response = new Response(pointsOfInterest: $pointsOfInterest);

        return HttpResponse::ok(body: $response->toArray());
    }
}
