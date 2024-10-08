<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register;

use PointsOfInterest\Domain\Ports\Outbound\Points;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register\Dtos\Request;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register\Dtos\Response;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register\Exceptions\PointOfInterestAlreadyExists;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TinyBlocks\Http\HttpResponse;

final readonly class Register implements RequestHandlerInterface
{
    public function __construct(private Points $points)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = (array)json_decode($request->getBody()->__toString(), true);
        $request = new Request(payload: $payload);
        $pointOfInterest = $request->toPointOfInterest();

        $result = $this->points->find(pointOfInterest: $pointOfInterest);

        if (!is_null($result)) {
            throw new PointOfInterestAlreadyExists(pointOfInterest: $pointOfInterest);
        }

        $this->points->save(pointOfInterest: $pointOfInterest);
        $response = new Response(pointOfInterest: $pointOfInterest);

        return HttpResponse::created(data: $response->toArray());
    }
}
