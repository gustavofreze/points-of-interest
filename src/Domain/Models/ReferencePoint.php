<?php

namespace PointsOfInterest\Domain\Models;

use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectAdapter;

final readonly class ReferencePoint implements ValueObject
{
    use ValueObjectAdapter;

    public function __construct(public Coordinate $xCoordinate, public Coordinate $yCoordinate)
    {
    }

    public static function from(int $xCoordinate, int $yCoordinate): ReferencePoint
    {
        return new ReferencePoint(
            xCoordinate: new Coordinate(value: $xCoordinate),
            yCoordinate: new Coordinate(value: $yCoordinate)
        );
    }
}
