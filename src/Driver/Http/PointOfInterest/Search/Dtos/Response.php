<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Search\Dtos;

use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;
use PointsOfInterest\Driver\Http\Shared\HttpResponse;
use TinyBlocks\Serializer\SerializerAdapter;

final class Response implements HttpResponse
{
    use SerializerAdapter;

    public function __construct(private readonly PointsOfInterest $pointsOfInterest)
    {
    }

    public function toArray(): array
    {
        $mapper = fn(PointOfInterest $pointOfInterest) => [
            'name'  => $pointOfInterest->name->value,
            'point' => [
                'x_coordinate' => $pointOfInterest->xCoordinate->value,
                'y_coordinate' => $pointOfInterest->yCoordinate->value
            ]
        ];

        return array_map($mapper, $this->pointsOfInterest->values);
    }
}
