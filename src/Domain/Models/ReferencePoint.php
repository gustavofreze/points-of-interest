<?php

declare(strict_types=1);

namespace PointsOfInterest\Domain\Models;

use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class ReferencePoint implements ValueObject
{
    use ValueObjectBehavior;

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
