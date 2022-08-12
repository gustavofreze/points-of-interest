<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Register;

use PointsOfInterest\Domain\Boundaries\Points;
use PointsOfInterest\Driver\Http\PointOfInterest\Register\Dtos\Request;
use PointsOfInterest\Driver\Http\PointOfInterest\Register\Dtos\Response;
use PointsOfInterest\Driver\Http\PointOfInterest\Register\Exceptions\PointOfInterestAlreadyExists;
use PointsOfInterest\Driver\Http\Shared\HttpResponseAdapter;
use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\HttpCode;

final class Register extends HttpResponseAdapter
{
    public function __construct(private readonly Points $points)
    {
    }

    protected function handle(ServerRequestInterface $request): array
    {
        $request = new Request(request: $this->requestWithParsedBody());
        $pointOfInterest = $request->toPointOfInterest();

        $result = $this->points->find(pointOfInterest: $pointOfInterest);

        if (!is_null($result)) {
            throw new PointOfInterestAlreadyExists(pointOfInterest: $pointOfInterest);
        }

        $this->points->save(pointOfInterest: $pointOfInterest);

        return $this
            ->withHttpCode(httpCode: HttpCode::CREATED)
            ->withResponse(httpResponse: (new Response(pointOfInterest: $pointOfInterest)))
            ->reply();
    }
}
