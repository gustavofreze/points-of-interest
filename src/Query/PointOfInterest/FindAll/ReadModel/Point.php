<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\PointOfInterest\FindAll\ReadModel;

final readonly class Point
{
    private function __construct(public int $xCoordinate, public int $yCoordinate)
    {
    }

    public static function from(int $xCoordinate, int $yCoordinate): Point
    {
        return new Point(xCoordinate: $xCoordinate, yCoordinate: $yCoordinate);
    }

    public function toArray(): array
    {
        return [
            'x_coordinate' => $this->xCoordinate,
            'y_coordinate' => $this->yCoordinate
        ];
    }
}
