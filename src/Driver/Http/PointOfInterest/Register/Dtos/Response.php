<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Register\Dtos;

use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Driver\Http\Shared\HttpResponse;
use TinyBlocks\Serializer\SerializerAdapter;

final class Response implements HttpResponse
{
    use SerializerAdapter;

    public function __construct(private readonly PointOfInterest $pointOfInterest)
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
