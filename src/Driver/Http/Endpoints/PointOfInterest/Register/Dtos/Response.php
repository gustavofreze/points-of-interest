<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register\Dtos;

use PointsOfInterest\Domain\Models\PointOfInterest;

final readonly class Response
{
    public function __construct(private PointOfInterest $pointOfInterest)
    {
    }

    public function toArray(): array
    {
        return [
            'name'  => $this->pointOfInterest->name->value,
            'point' => [
                'x_coordinate' => $this->pointOfInterest->xCoordinate->value,
                'y_coordinate' => $this->pointOfInterest->yCoordinate->value
            ]
        ];
    }
}
