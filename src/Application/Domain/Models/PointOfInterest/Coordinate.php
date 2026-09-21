<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Models\PointOfInterest;

use PointsOfInterest\Application\Domain\Exceptions\CoordinateOutOfRange;
use PointsOfInterest\Application\Domain\Models\Commons\ValueObject;
use PointsOfInterest\Application\Domain\Models\Commons\ValueObjectBehavior;

final readonly class Coordinate implements ValueObject
{
    use ValueObjectBehavior;

    private const int MAXIMUM = 4294967295;

    private function __construct(public int $value)
    {
    }

    public static function from(int $value): Coordinate
    {
        if ($value < 0 || $value > Coordinate::MAXIMUM) {
            throw new CoordinateOutOfRange(current: $value, maximum: Coordinate::MAXIMUM);
        }

        return new Coordinate(value: $value);
    }
}
