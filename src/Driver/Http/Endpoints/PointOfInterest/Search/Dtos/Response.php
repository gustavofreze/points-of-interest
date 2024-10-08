<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Search\Dtos;

use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;

final readonly class Response
{
    public function __construct(private PointsOfInterest $pointsOfInterest)
    {
    }

    public function toArray(): array
    {
        return $this->pointsOfInterest
            ->map(transformations: fn(PointOfInterest $pointOfInterest): array => [
                'name'  => $pointOfInterest->name->value,
                'point' => [
                    'x_coordinate' => $pointOfInterest->xCoordinate->value,
                    'y_coordinate' => $pointOfInterest->yCoordinate->value
                ]
            ])
            ->toArray();
    }
}
