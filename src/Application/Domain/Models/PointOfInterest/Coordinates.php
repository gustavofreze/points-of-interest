<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Models\PointOfInterest;

use PointsOfInterest\Application\Domain\Models\Commons\ValueObject;
use PointsOfInterest\Application\Domain\Models\Commons\ValueObjectBehavior;

final readonly class Coordinates implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(public Coordinate $xCoordinate, public Coordinate $yCoordinate)
    {
    }

    public static function from(int $xCoordinate, int $yCoordinate): Coordinates
    {
        return new Coordinates(
            xCoordinate: Coordinate::from(value: $xCoordinate),
            yCoordinate: Coordinate::from(value: $yCoordinate)
        );
    }
}
