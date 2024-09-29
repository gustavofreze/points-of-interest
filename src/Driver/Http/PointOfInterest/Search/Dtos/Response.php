<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Search\Dtos;

use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;
use PointsOfInterest\Driver\Http\Shared\HttpResponse;

final readonly class Response implements HttpResponse
{
    public function __construct(private PointsOfInterest $pointsOfInterest)
    {
    }

    public function toArray(): array
    {
        return $this->pointsOfInterest
            ->map(transformations: fn(PointOfInterest $pointOfInterest) => [
                'name'  => $pointOfInterest->name->value,
                'point' => [
                    'x_coordinate' => $pointOfInterest->xCoordinate->value,
                    'y_coordinate' => $pointOfInterest->yCoordinate->value
                ]
            ])
            ->toArray();
    }
}
