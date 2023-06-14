<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Search;

use PointsOfInterest\Domain\Ports\Outbound\Points;
use PointsOfInterest\Driver\Http\PointOfInterest\Search\Dtos\Request;
use PointsOfInterest\Driver\Http\PointOfInterest\Search\Dtos\Response;
use PointsOfInterest\Driver\Http\Shared\HttpResponseAdapter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\HttpResponse;

final class Search extends HttpResponseAdapter
{
    public function __construct(private readonly Points $points)
    {
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = new Request(request: $request->getQueryParams());
        $pointsOfInterest = $this->points->findAll();

        $pointsOfInterest = $request->noHasFilters()
            ? $pointsOfInterest
            : $pointsOfInterest->byProximity(
                referencePoint: $request->toReferencePoint(),
                maximumDistance: $request->toDistance()
            );

        $response = new Response(pointsOfInterest: $pointsOfInterest);

        return HttpResponse::ok(data: $response->toArray());
    }
}
